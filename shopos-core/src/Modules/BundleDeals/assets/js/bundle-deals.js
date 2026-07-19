/**
 * ShopOS Bundle Deals — storefront block controller.
 *
 * No dependencies. Handles the frequently-bought-together "add bundle to cart"
 * action: collect the checked set members, POST them to the admin-AJAX
 * endpoint, then ask WooCommerce to refresh its cart fragments. The tiered
 * table and mix-&-match progress bar are server-rendered; this file only wires
 * the interactive FBT box.
 */
(function () {
	'use strict';

	var CFG = window.ShopOSBundleDeals || {};

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	function checkedIds(box) {
		var ids = [];
		box.querySelectorAll('.shopos-ui-bundle__set-check:checked').forEach(function (cb) {
			var v = parseInt(cb.value, 10);
			if (v > 0) { ids.push(v); }
		});
		return ids;
	}

	function refreshFragments() {
		if (window.jQuery) {
			window.jQuery(document.body).trigger('wc_fragment_refresh');
			window.jQuery(document.body).trigger('added_to_cart');
		}
	}

	function addBundle(box, button) {
		var ids = checkedIds(box);
		if (!ids.length) { return; }

		button.setAttribute('disabled', 'disabled');

		var body = new FormData();
		body.append('action', CFG.action || '');
		body.append('_ajax_nonce', CFG.nonce || '');
		ids.forEach(function (id) { body.append('products[]', String(id)); });

		window.fetch(CFG.ajaxUrl || '', { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (res) { return res.json(); })
			.then(function (json) {
				button.removeAttribute('disabled');
				if (json && json.success) {
					refreshFragments();
					box.classList.add('is-added');
				}
			})
			.catch(function () {
				button.removeAttribute('disabled');
			});
	}

	function syncAddState(box) {
		var button = box.querySelector('[data-shopos-bundle-add]');
		if (button) {
			button.toggleAttribute('disabled', checkedIds(box).length === 0);
		}
	}

	ready(function () {
		var boxes = document.querySelectorAll('.shopos-ui-bundle__fbt');
		boxes.forEach(function (box) {
			syncAddState(box);
			box.addEventListener('change', function (e) {
				if (e.target.matches('.shopos-ui-bundle__set-check')) {
					syncAddState(box);
				}
			});
			box.addEventListener('click', function (e) {
				var button = e.target.closest ? e.target.closest('[data-shopos-bundle-add]') : null;
				if (button) {
					e.preventDefault();
					addBundle(box, button);
				}
			});
		});
	});
})();
