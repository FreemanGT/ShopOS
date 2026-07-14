#!/usr/bin/env bash
#
# Build clean, distributable zips for ShopOS Theme and ShopOS Core.
#
# Usage:
#   bash tools/build.sh            # builds both zips into dist/
#   bash tools/build.sh theme      # only the theme
#   bash tools/build.sh core       # only the core plugin
#   bash tools/build.sh all        # theme + core
#
# Output:
#   dist/shopos-theme-<version>.zip
#   dist/shopos-core-<version>.zip
#
# Requires: zip, rsync. Optionally uses msgfmt to (re)compile .po → .mo.

set -euo pipefail

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
DIST="${ROOT}/dist"
mkdir -p "${DIST}"

TARGET="${1:-all}"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

log()  { printf "\033[1;34m[build]\033[0m %s\n" "$*"; }
warn() { printf "\033[1;33m[warn]\033[0m  %s\n" "$*"; }
fail() { printf "\033[1;31m[fail]\033[0m  %s\n" "$*"; exit 1; }

require() {
    command -v "$1" >/dev/null 2>&1 || fail "Missing dependency: $1"
}

get_version() {
    local file="$1"
    grep -i "^[[:space:]]*\*\?[[:space:]]*Version:" "$file" \
        | head -n 1 \
        | awk -F: '{gsub(/[[:space:]]/,"",$2); print $2}'
}

compile_mo() {
    # Rebuild every .mo next to its .po so a release always contains the
    # freshest translations.
    local dir="$1"
    if ! command -v msgfmt >/dev/null 2>&1; then
        warn "msgfmt not found — skipping .po → .mo compile in ${dir}"
        return
    fi
    find "${dir}" -type f -name "*.po" -print0 | while IFS= read -r -d '' po; do
        local mo="${po%.po}.mo"
        msgfmt -o "${mo}" "${po}" >/dev/null
    done
}

minify_assets() {
    # Emit sibling .min.{js,css} files next to every source asset inside the
    # given staging dir. Uses the esbuild CLI if node_modules has it; logs
    # and returns a warning otherwise so the build still ships. PHP enqueue
    # helpers fall back to the un-minified file when .min.* is absent.
    local dir="$1"
    local esbuild="${ROOT}/node_modules/.bin/esbuild"
    if [[ ! -x "${esbuild}" ]]; then
        warn "esbuild not installed — skipping minification in ${dir} (run: npm install)"
        return
    fi

    local count=0
    while IFS= read -r -d '' f; do
        case "${f}" in
            *.min.js|*.min.css) continue ;;
        esac
        local out="${f%.*}.min.${f##*.}"
        if "${esbuild}" --minify --log-level=error "${f}" > "${out}" 2>/dev/null; then
            count=$((count + 1))
        else
            warn "minify failed: ${f}"
            rm -f "${out}"
        fi
    done < <(find "${dir}" -type f \( -name "*.js" -o -name "*.css" \) -print0)
    log "minified ${count} asset(s) in ${dir}"
}

# Common exclusions passed to `zip -x`.
COMMON_EXCLUDES=(
    "*/node_modules/*"
    "*/vendor/*"
    "*/.git/*"
    "*/.github/*"
    "*/.idea/*"
    "*/.vscode/*"
    "*/tests/*"
    "*/.phpunit.result.cache"
    "*.DS_Store"
    "*.map"
    "*/composer.lock"
    "*/package-lock.json"
    "*/yarn.lock"
    "*/.editorconfig"
    "*/.gitignore"
    "*/.phpcs.xml.dist"
)

stage_dir() {
    # Rsync a source tree into a staging dir, applying COMMON_EXCLUDES.
    local src="$1" dst="$2"
    require rsync
    rm -rf "${dst}"
    mkdir -p "${dst}"
    local excludes=()
    for e in "${COMMON_EXCLUDES[@]}"; do
        excludes+=( "--exclude=${e#*/}" )
    done
    rsync -a "${excludes[@]}" "${src}/" "${dst}/"
}

# ---------------------------------------------------------------------------
# Build targets
# ---------------------------------------------------------------------------

build_theme() {
    local src="${ROOT}/shopos-theme"
    local version; version="$( get_version "${src}/style.css" )"
    [[ -n "${version}" ]] || fail "Could not read theme version from style.css"
    local out="${DIST}/shopos-theme-${version}.zip"
    log "Building theme zip → ${out}"

    compile_mo "${src}/languages"

    local stage; stage="$( mktemp -d )"
    stage_dir "${src}" "${stage}/shopos-theme"
    minify_assets "${stage}/shopos-theme"
    (
        cd "${stage}"
        rm -f "${out}"
        zip -qr "${out}" "shopos-theme"
    )
    rm -rf "${stage}"
}

build_core() {
    local src="${ROOT}/shopos-core"
    local version; version="$( get_version "${src}/shopos-core.php" )"
    [[ -n "${version}" ]] || fail "Could not read core plugin version"
    local out="${DIST}/shopos-core-${version}.zip"
    log "Building core plugin zip → ${out}"

    compile_mo "${src}/languages"

    local stage; stage="$( mktemp -d )"
    stage_dir "${src}" "${stage}/shopos-core"
    # Remove tooling that only matters at dev-time.
    rm -rf "${stage}/shopos-core/tools"
    rm -f  "${stage}/shopos-core/composer.json"
    minify_assets "${stage}/shopos-core"
    (
        cd "${stage}"
        rm -f "${out}"
        zip -qr "${out}" "shopos-core"
    )
    rm -rf "${stage}"
}

# ---------------------------------------------------------------------------
# Preflight: lint every PHP file + run offline activation simulation.
# Any error here aborts the build so we never ship a broken zip.
# ---------------------------------------------------------------------------

preflight() {
    log "Preflight: php -l on every source file"
    local fails=0
    while IFS= read -r -d '' f; do
        if ! php -l "$f" >/dev/null 2>&1; then
            php -l "$f" || true
            fails=$((fails + 1))
        fi
    done < <(find "${ROOT}/shopos-core" "${ROOT}/shopos-theme" -type f -name "*.php" \
                 -not -path "*/node_modules/*" -not -path "*/vendor/*" -print0)
    [[ "${fails}" -eq 0 ]] || fail "${fails} PHP file(s) failed to parse — refusing to build"

    if [[ -f "${ROOT}/tools/activation-sim.php" ]]; then
        log "Preflight: activation simulation"
        # Silence stubbed-WP warnings; only surface real failures (non-zero exit).
        php -d error_reporting=0 "${ROOT}/tools/activation-sim.php" >/dev/null 2>&1 \
            || fail "Activation simulation failed — run \`php tools/activation-sim.php\` for details"
    fi
    log "Preflight: OK"
}

# ---------------------------------------------------------------------------
# Dispatch
# ---------------------------------------------------------------------------

require zip

preflight

case "${TARGET}" in
    theme)  build_theme ;;
    core)   build_core ;;
    all)    build_theme; build_core ;;
    *)      fail "Unknown target: ${TARGET}. Try: theme | core | all" ;;
esac

log "Done. Artifacts in ${DIST}/"
ls -lh "${DIST}"
