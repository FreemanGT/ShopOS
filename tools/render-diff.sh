#!/usr/bin/env bash
#
# render-diff.sh — flag-off render-identity check (decisions §11 Ruling 7.3).
#
# Fetches a page, normalizes the request-varying noise (nonces, timestamps,
# asset cache-busters), and diffs it against a saved snapshot — the named
# mechanism for proving "flag off = the current render, byte-identical"
# before every ShopOS Line template/flag PR merges (§11.5), and re-run
# before each flag-on.
#
# Usage:
#   bash tools/render-diff.sh snapshot <url> <out-file>   # fetch → normalize → save
#   bash tools/render-diff.sh diff <file-a> <file-b>      # diff two snapshots
#   bash tools/render-diff.sh compare <url> <saved-file>  # fetch → normalize → diff vs saved
#
# Exit codes: 0 = identical (or snapshot saved), 1 = differences, 2 = usage/fetch error.
#
# Normalization (deliberately minimal — every rule is a request-varying token,
# never content):
#   - WP nonces:            _wpnonce=<hex>, "nonce":"<hex>", "_wpnonce":"<hex>"
#   - asset cache-busters:  ?ver=<anything> / &ver=<anything> (version bumps are
#                           not render changes; asset CONTENT identity is a
#                           separate check)
#   - unix timestamps in query strings: [?&]t=<10+ digits>
#   - Elementor page-render comments (contain render time)
#
# Scope note (§11 Ruling 7.3): wp-env runs Elementor FREE, so identity there
# covers the parent-fallback path only; the authoritative flag-off identity
# run is against staging-with-Elementor-Pro.

set -euo pipefail

MODE="${1:-}"

normalize() {
    sed -E \
        -e 's/_wpnonce=[a-zA-Z0-9]+/_wpnonce=NONCE/g' \
        -e 's/"(_wpnonce|nonce)":"[a-zA-Z0-9]+"/"\1":"NONCE"/g' \
        -e 's/([?&])ver=[^"&'\'' >]*/\1ver=VER/g' \
        -e 's/([?&])t=[0-9]{10,}/\1t=TS/g' \
        -e 's/<!-- Elementor[^>]*-->//g'
}

# Fetch → normalize → write. No pipeline: an `exit` inside a piped function
# only kills its own subshell, so a failed curl would silently hand diff an
# empty file instead of aborting.
fetch_norm() {
    local url="$1" out="$2" raw
    raw="$(mktemp)"
    if ! curl -sfL --max-time 30 -o "${raw}" "${url}"; then
        rm -f "${raw}"
        echo "render-diff: fetch failed: ${url}" >&2
        exit 2
    fi
    normalize < "${raw}" > "${out}"
    rm -f "${raw}"
}

case "${MODE}" in
    snapshot)
        URL="${2:-}"; OUT="${3:-}"
        [[ -n "${URL}" && -n "${OUT}" ]] || { echo "Usage: render-diff.sh snapshot <url> <out-file>" >&2; exit 2; }
        fetch_norm "${URL}" "${OUT}"
        echo "snapshot saved: ${OUT} ($(wc -c < "${OUT}" | tr -d ' ') bytes normalized)"
        ;;
    diff)
        A="${2:-}"; B="${3:-}"
        [[ -f "${A}" && -f "${B}" ]] || { echo "Usage: render-diff.sh diff <file-a> <file-b>" >&2; exit 2; }
        if diff -u "${A}" "${B}"; then
            echo "render-diff: IDENTICAL"
        else
            echo "render-diff: DIFFERENCES FOUND" >&2
            exit 1
        fi
        ;;
    compare)
        URL="${2:-}"; SAVED="${3:-}"
        [[ -n "${URL}" && -f "${SAVED}" ]] || { echo "Usage: render-diff.sh compare <url> <saved-file>" >&2; exit 2; }
        TMP="$(mktemp)"
        trap 'rm -f "${TMP}"' EXIT
        fetch_norm "${URL}" "${TMP}"
        if diff -u "${SAVED}" "${TMP}"; then
            echo "render-diff: IDENTICAL"
        else
            echo "render-diff: DIFFERENCES FOUND" >&2
            exit 1
        fi
        ;;
    *)
        echo "Usage: bash tools/render-diff.sh {snapshot|diff|compare} ..." >&2
        exit 2
        ;;
esac
