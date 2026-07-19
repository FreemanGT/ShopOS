/**
 * ShopOS Bundle Deals — builder repeater.
 *
 * No dependencies. Adds/removes bundle cards (cloning the #shopos-bundle-tpl
 * <template>), shows only the active type's panel, hides the shared targeting
 * for curated bundles (they target their own set), and keeps a plain-language
 * preview in sync as fields change. Row indices only need to be unique within
 * the form — Bundle_Config::sanitize() re-indexes on save — so new cards get a
 * running "n<counter>" index that never collides with the saved numeric ones.
 */
(function () {
	'use strict';

	var counter = 0;

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	function typeOf(card) {
		var sel = card.querySelector('[data-bundle-type]');
		return sel ? sel.value : 'tiered';
	}

	function applyType(card) {
		var type = typeOf(card);
		card.setAttribute('data-type', type);
		card.querySelectorAll('.shopos-bundle-panel').forEach(function (panel) {
			panel.style.display = (panel.getAttribute('data-panel') === type) ? '' : 'none';
		});
		var scope = card.querySelector('[data-scope]');
		if (scope) {
			// Curated bundles target their explicit set, not the shared scope.
			scope.style.display = (type === 'curated') ? 'none' : '';
		}
	}

	function num(card, name) {
		var el = card.querySelector('[name$="' + name + '"]');
		return el ? el.value : '';
	}

	function updatePreview(card) {
		var type = typeOf(card);
		var out = '';
		if (type === 'tiered') {
			var tiers = 0;
			card.querySelectorAll('[data-panel="tiered"] input[name$="[min]"]').forEach(function (i) {
				if (i.value !== '') { tiers++; }
			});
			out = tiers + ' ' + (tiers === 1 ? 'tier' : 'tiers');
		} else if (type === 'bogo') {
			out = 'Buy ' + (num(card, '[bogo][buy]') || '?') + ', get ' + (num(card, '[bogo][get]') || '?') + ' @ ' + (num(card, '[bogo][discount]') || '0') + '% off';
		} else if (type === 'curated') {
			var ids = (num(card, '[curated][products]') || '').split(',').filter(function (s) { return s.trim() !== ''; });
			out = 'Set of ' + ids.length + ', ' + (num(card, '[curated][amount]') || '0') + ' off';
		} else if (type === 'mixmatch') {
			out = 'Any ' + (num(card, '[mixmatch][need]') || '?') + ' for ' + (num(card, '[mixmatch][amount]') || '0');
		}
		var el = card.querySelector('[data-bundle-preview]');
		if (el) { el.textContent = out; }
	}

	function initCard(card) {
		applyType(card);
		updatePreview(card);
	}

	function addCard() {
		var tpl = document.getElementById('shopos-bundle-tpl');
		var list = document.getElementById('shopos-bundle-list');
		if (!tpl || !list) { return; }
		var html = tpl.innerHTML.replace(/__i__/g, 'n' + (counter++));
		var wrap = document.createElement('div');
		wrap.innerHTML = html.trim();
		var card = wrap.firstElementChild;
		if (!card) { return; }
		list.appendChild(card);
		initCard(card);
	}

	ready(function () {
		var list = document.getElementById('shopos-bundle-list');
		if (!list) { return; }

		list.querySelectorAll('.shopos-bundle-card').forEach(initCard);

		var add = document.getElementById('shopos-bundle-add');
		if (add) { add.addEventListener('click', addCard); }

		list.addEventListener('change', function (e) {
			var card = e.target.closest ? e.target.closest('.shopos-bundle-card') : null;
			if (!card) { return; }
			if (e.target.matches('[data-bundle-type]')) { applyType(card); }
			updatePreview(card);
		});
		list.addEventListener('input', function (e) {
			var card = e.target.closest ? e.target.closest('.shopos-bundle-card') : null;
			if (card) { updatePreview(card); }
		});
		list.addEventListener('click', function (e) {
			var rm = e.target.closest ? e.target.closest('[data-bundle-remove]') : null;
			if (rm) {
				e.preventDefault();
				var card = rm.closest('.shopos-bundle-card');
				if (card) { card.parentNode.removeChild(card); }
			}
		});
	});
})();
