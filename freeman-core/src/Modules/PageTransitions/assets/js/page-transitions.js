/**
 * Freeman Page Transitions — loading-overlay controller (layer A).
 *
 * Shows a scrim + spinner the moment an interaction starts a full-page
 * navigation, so the wait between click and new document has visible
 * feedback. The cross-document fade (layer B) is pure CSS — see
 * page-transitions.css.
 *
 * Triggers:
 *   - pagination links (ProductSlider grid, WooCommerce, Elementor, blocks)
 *   - product-search form submits ([freeman_search] palette + native forms)
 *   - `window.FreemanPageTransitions.show()` — called by ShopFilters'
 *     navigate() (soft dependency: ShopFilters no-ops when this module is
 *     disabled).
 *
 * Safety: a bfcache restore (pageshow persisted) hides the overlay, as does
 * an 8s timeout — so an aborted/failed navigation can never strand a dead
 * scrim over an interactive page.
 */
(function () {
    'use strict';

    var CFG = window.FreemanPTConfig || {};

    var PAGINATION_SELECTOR = [
        '.cs-pagination a[href]',
        'nav.woocommerce-pagination a[href]',
        '.woocommerce-pagination a[href]',
        '.elementor-pagination a[href]',
        '.wc-block-pagination a[href]',
        '.wp-block-query-pagination a[href]',
        'ul.page-numbers a[href]'
    ].join(', ');

    var SEARCH_FORM_SELECTOR = 'form.fc-search-form, form[role="search"]';

    var overlay = null;
    var hideTimer = 0;

    function buildOverlay() {
        var el = document.createElement('div');
        el.className = 'fpt-overlay';
        el.setAttribute('aria-hidden', 'true');
        var box = document.createElement('div');
        box.className = 'fpt-box';
        var spinner = document.createElement('div');
        spinner.className = 'fpt-spinner';
        var label = document.createElement('div');
        label.className = 'fpt-label';
        label.textContent = CFG.label || 'Loading…';
        box.appendChild(spinner);
        box.appendChild(label);
        el.appendChild(box);
        return el;
    }

    function show() {
        if (!overlay) { overlay = buildOverlay(); }
        if (!overlay.parentNode) { document.body.appendChild(overlay); }
        // Two frames so the appended node paints at opacity 0 first and the
        // scrim fades in instead of popping.
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                if (overlay) { overlay.classList.add('fpt-overlay--on'); }
            });
        });
        clearTimeout(hideTimer);
        hideTimer = setTimeout(hide, 8000);
    }

    function hide() {
        clearTimeout(hideTimer);
        if (overlay && overlay.parentNode) { overlay.parentNode.removeChild(overlay); }
        if (overlay) { overlay.classList.remove('fpt-overlay--on'); }
    }

    // True only for a plain left-click that will actually navigate this tab.
    function isPlainNavigation(e, link) {
        if (e.defaultPrevented) { return false; }
        if (typeof e.button === 'number' && e.button !== 0) { return false; }
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return false; }
        if (link.target && link.target !== '_self') { return false; }
        if (link.hasAttribute('download')) { return false; }
        if (link.origin && link.origin !== location.origin) { return false; }
        return true;
    }

    document.addEventListener('click', function (e) {
        var link = e.target && e.target.closest ? e.target.closest(PAGINATION_SELECTOR) : null;
        if (!link || !isPlainNavigation(e, link)) { return; }
        show();
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.matches || !form.matches(SEARCH_FORM_SELECTOR)) { return; }
        if (e.defaultPrevented) { return; }
        if (form.target && form.target !== '_self') { return; }
        show();
    });

    // A bfcache restore re-shows the old page exactly as it was frozen —
    // including a visible overlay. Hide it so Back never lands on a scrim.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { hide(); }
    });

    window.FreemanPageTransitions = { show: show, hide: hide };
})();
