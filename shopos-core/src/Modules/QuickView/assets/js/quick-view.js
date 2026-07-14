/**
 * ShopOS Quick View — trigger + slide-in drawer controller.
 *
 * Click a `.shopos-qv-trigger` on any product card → fetch the drawer content
 * from admin-AJAX → inject into the footer drawer shell → slide it in from
 * the inline-end edge (left on RTL). Fires `shopos_core_quick_view_loaded`
 * (jQuery + native CustomEvent) after injecting, so VariationSwatches and
 * other listeners can (re)initialise the injected buy box.
 *
 * No dependencies. The drawer shell is server-rendered by Frontend.php;
 * this file only toggles state and swaps content.
 */
(function () {
	'use strict';

	var CFG = window.ShopOSQuickView || {};

	var root = null;       // .shopos-quick-view
	var panel = null;      // .shopos-quick-view__panel
	var content = null;    // [data-shopos-qv-content]
	var lastTrigger = null;
	var cache = {};        // product id -> html

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	function announceLoaded() {
		// jQuery event — VariationSwatches (and other WC plugins) bind their
		// quick-view re-init listeners via jQuery, which native dispatch
		// doesn't reach.
		if (window.jQuery) {
			window.jQuery(document).trigger('shopos_core_quick_view_loaded');
		}
		document.dispatchEvent(new CustomEvent('shopos_core_quick_view_loaded'));
	}

	function setContent(html) {
		content.innerHTML = html;
	}

	// Inject drawer HTML and re-wire dependent UI: VariationSwatches re-binds
	// the buy box on the announce event, and card-slider.js auto-inits the
	// drawer gallery (the .shopos-card-slider) via its MutationObserver.
	function inject(html) {
		setContent(html);
		announceLoaded();
	}

	function showMessage(text) {
		var p = document.createElement('p');
		p.className = 'shopos-quick-view__message';
		p.textContent = text || '';
		content.innerHTML = '';
		content.appendChild(p);
	}

	function open(productId, trigger) {
		if (!root) { return; }
		lastTrigger = trigger || null;

		root.classList.add('is-open');
		root.setAttribute('aria-hidden', 'false');
		document.documentElement.classList.add('shopos-qv-lock');
		panel.focus();

		if (cache[productId]) {
			inject(cache[productId]);
			return;
		}

		showMessage((CFG.labels || {}).loading);

		var body = new FormData();
		body.append('action', CFG.action || '');
		body.append('_ajax_nonce', CFG.nonce || '');
		body.append('product_id', String(productId));

		window.fetch(CFG.ajaxUrl || '', { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (!json || !json.success || !json.data || !json.data.html) {
					throw new Error('quick-view');
				}
				cache[productId] = json.data.html;
				// The shopper may have closed the drawer (or opened another
				// product) while this request was in flight — only inject
				// when the drawer is still open.
				if (root.classList.contains('is-open')) {
					inject(json.data.html);
				}
			})
			.catch(function () {
				if (root.classList.contains('is-open')) {
					showMessage((CFG.labels || {}).error);
				}
			});
	}

	function close() {
		if (!root || !root.classList.contains('is-open')) { return; }
		root.classList.remove('is-open');
		root.setAttribute('aria-hidden', 'true');
		document.documentElement.classList.remove('shopos-qv-lock');
		if (lastTrigger && typeof lastTrigger.focus === 'function') {
			lastTrigger.focus();
		}
		lastTrigger = null;
	}

	function trapTab(e) {
		var focusable = panel.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);
		if (!focusable.length) { e.preventDefault(); return; }
		var first = focusable[0];
		var last = focusable[focusable.length - 1];
		if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}

	ready(function () {
		root = document.getElementById('shopos-quick-view');
		if (!root) { return; }
		panel = root.querySelector('.shopos-quick-view__panel');
		content = root.querySelector('[data-shopos-qv-content]');
		if (!panel || !content) { return; }

		// Delegated — triggers may arrive later (Infinite Scroll pages,
		// AJAX-filtered grids).
		document.addEventListener('click', function (e) {
			var trigger = e.target.closest ? e.target.closest('.shopos-qv-trigger') : null;
			if (trigger) {
				e.preventDefault();
				e.stopPropagation();
				open(parseInt(trigger.getAttribute('data-shopos-qv') || '0', 10), trigger);
				return;
			}
			if (e.target.closest && e.target.closest('[data-shopos-qv-close]')) {
				close();
			}
		});

		document.addEventListener('keydown', function (e) {
			if (!root.classList.contains('is-open')) { return; }
			if (e.key === 'Escape') { close(); return; }
			if (e.key === 'Tab') { trapTab(e); }
		});
	});
})();
