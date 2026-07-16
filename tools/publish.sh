#!/usr/bin/env bash
#
# Publish a built release to the ShopOS update channel.
#
# Pushes the dist/ zip as a GitHub Release on the releases repo and updates
# manifest.json there — the theme/plugin updaters poll that manifest and
# surface the new version in the WordPress dashboard.
#
# Usage:
#   bash tools/publish.sh theme          # publish current theme version
#   bash tools/publish.sh core           # publish current core version
#   bash tools/publish.sh both
#
# Typical flow:
#   bash tools/release.sh theme 1.12.1 "Fix …"   # bump + changelog + build
#   bash tools/publish.sh theme                   # ship it
#
# Requires: gh (authenticated), jq. The releases repo is configured below.

set -euo pipefail

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
DIST="${ROOT}/dist"

RELEASES_REPO="FreemanGT/shopos-releases"
MANIFEST_BRANCH="main"

TARGET="${1:-}"
[[ "${TARGET}" =~ ^(theme|core|both)$ ]] || {
    echo "Usage: bash tools/publish.sh {theme|core|both}"
    exit 1
}

log()  { printf "\033[1;34m[publish]\033[0m %s\n" "$*"; }
fail() { printf "\033[1;31m[fail]\033[0m    %s\n" "$*"; exit 1; }

command -v gh >/dev/null 2>&1 || fail "gh CLI not found"
command -v jq >/dev/null 2>&1 || fail "jq not found"

get_version() {
    grep -i "^[[:space:]]*\*\?[[:space:]]*Version:" "$1" \
        | head -n 1 \
        | awk -F: '{gsub(/[[:space:]]/,"",$2); print $2}'
}

# Pull the latest changelog entry (the block under the first "## [") to use
# as release notes.
latest_changelog() {
    awk '/^## \[/{ n++; if (n==2) exit } n==1 { print }' "$1"
}

publish_one() {
    local product="$1" version="$2" zip="$3" changelog="$4"
    local tag="${product#shopos-}-${version}"          # theme-1.12.0 / core-1.42.0

    [[ -f "${zip}" ]] || fail "Missing ${zip} — run tools/build.sh first"

    # 1. GitHub Release with the zip attached (skip if the tag already exists).
    if gh release view "${tag}" --repo "${RELEASES_REPO}" >/dev/null 2>&1; then
        log "Release ${tag} already exists — re-uploading asset"
        gh release upload "${tag}" "${zip}" --clobber --repo "${RELEASES_REPO}"
    else
        log "Creating release ${tag}"
        latest_changelog "${changelog}" > /tmp/shopos-release-notes.md || true
        gh release create "${tag}" "${zip}" \
            --repo "${RELEASES_REPO}" \
            --title "${product} ${version}" \
            --notes-file /tmp/shopos-release-notes.md
    fi

    local package_url="https://github.com/${RELEASES_REPO}/releases/download/${tag}/$(basename "${zip}")"

    # 2. Update manifest.json on the releases repo (create it if absent).
    log "Updating manifest.json → ${product} ${version}"
    local api="repos/${RELEASES_REPO}/contents/manifest.json"
    local sha current
    if current="$( gh api "${api}?ref=${MANIFEST_BRANCH}" 2>/dev/null )"; then
        sha="$( jq -r '.sha' <<<"${current}" )"
        jq -r '.content' <<<"${current}" | base64 -d > /tmp/shopos-manifest.json
    else
        sha=""
        echo '{}' > /tmp/shopos-manifest.json
    fi

    jq --arg p "${product}" --arg v "${version}" --arg url "${package_url}" \
       --arg date "$(date +%Y-%m-%d)" --arg tag "${tag}" '
        .[$p] = {
            version:       $v,
            package:       $url,
            released:      $date,
            requires:      "6.0",
            tested:        "6.8",
            requires_php:  "8.0",
            changelog_url: ("https://github.com/FreemanGT/shopos-releases/releases/tag/" + $tag)
        }' /tmp/shopos-manifest.json > /tmp/shopos-manifest-new.json

    local args=( -X PUT "${api}"
        -f message="release: ${product} ${version}"
        -f branch="${MANIFEST_BRANCH}"
        -f content="$( base64 -i /tmp/shopos-manifest-new.json )" )
    [[ -n "${sha}" ]] && args+=( -f sha="${sha}" )
    gh api "${args[@]}" >/dev/null

    log "${product} ${version} published → ${package_url}"
}

if [[ "${TARGET}" == "theme" || "${TARGET}" == "both" ]]; then
    v="$( get_version "${ROOT}/shopos-theme/style.css" )"
    publish_one "shopos-theme" "${v}" "${DIST}/shopos-theme-${v}.zip" "${ROOT}/shopos-theme/CHANGELOG.md"
fi

if [[ "${TARGET}" == "core" || "${TARGET}" == "both" ]]; then
    v="$( get_version "${ROOT}/shopos-core/shopos-core.php" )"
    publish_one "shopos-core" "${v}" "${DIST}/shopos-core-${v}.zip" "${ROOT}/shopos-core/CHANGELOG.md"
fi

log "Done. Sites see the update within ~5 min (or instantly via Dashboard → Updates → Check Again)."
