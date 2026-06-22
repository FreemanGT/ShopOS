/**
 * Freeman Search — live product dropdown.
 *
 * Progressive enhancement: attaches a results dropdown to the theme's existing
 * search input(s) (targeted by the configurable `selector`). With JS off the
 * native form submits unchanged. Debounced fetch + AbortController, combobox
 * keyboard/ARIA, server-rendered price HTML.
 *
 * The `[freeman_search]` shortcode form (`.fc-search-form`) is upgraded further:
 * its visible box is replaced by a search icon that, on click, expands a
 * full-width bar below the header with the results beneath it. Any other matched
 * field keeps the classic anchored dropdown.
 */
(function () {
	'use strict';

	var cfg = window.FreemanSearch;
	if (!cfg || !cfg.selector) {
		return;
	}

	var idSeq = 0;

	function el(tag, cls) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		return n;
	}

	var ICON = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';

	// Close glyph drawn from the same stroke family as ICON so the two read as one set.
	var CLOSE_ICON = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';

	// What each result row renders. Defaults preserve the original look if the
	// localized payload predates these flags (image + price on, SKU off).
	var show = (cfg.show && typeof cfg.show === 'object') ? cfg.show : { image: true, price: true, sku: false };

	function enhance(input) {
		if (input.dataset.fcSearchBound) { return; }
		input.dataset.fcSearchBound = '1';

		var listId = 'fc-search-list-' + (++idSeq);
		var panel = el('div', 'fc-search-panel');
		panel.id = listId;
		panel.setAttribute('role', 'listbox');
		panel.hidden = true;

		var live = el('div', 'fc-search-live');
		live.setAttribute('aria-live', 'polite');
		live.style.position = 'absolute';
		live.style.width = '1px';
		live.style.height = '1px';
		live.style.overflow = 'hidden';
		live.style.clip = 'rect(0 0 0 0)';
		document.body.appendChild(live);

		input.setAttribute('role', 'combobox');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-expanded', 'false');
		input.setAttribute('aria-controls', listId);
		input.setAttribute('autocomplete', 'off');

		var controller = null;
		var timer = null;
		var items = [];
		var active = -1;

		// The shortcode form gets the icon/overlay treatment; everything else keeps
		// the classic anchored dropdown (unchanged behaviour).
		var form = input.closest('form.fc-search-form');
		var overlayMode = !!form;

		var trigger, overlay, bar;

		function open() {
			if (panel.hidden) {
				if (!overlayMode) { position(); }
				panel.hidden = false;
				input.setAttribute('aria-expanded', 'true');
			}
		}

		function close() {
			panel.hidden = true;
			input.setAttribute('aria-expanded', 'false');
			input.removeAttribute('aria-activedescendant');
			active = -1;
		}

		function setActive(next) {
			var opts = panel.querySelectorAll('[role="option"]');
			if (!opts.length) { return; }
			if (active > -1 && opts[active]) { opts[active].setAttribute('aria-selected', 'false'); }
			active = (next + opts.length) % opts.length;
			opts[active].setAttribute('aria-selected', 'true');
			input.setAttribute('aria-activedescendant', opts[active].id);
			opts[active].scrollIntoView({ block: 'nearest' });
		}

		function render(data) {
			panel.textContent = '';
			items = (data && data.items) || [];

			if (!items.length) {
				var empty = el('div', 'fc-search-empty');
				empty.textContent = cfg.labels.noResults;
				panel.appendChild(empty);
				live.textContent = cfg.labels.noResults;
				open();
				return;
			}

			items.forEach(function (it, i) {
				var a = el('a', 'fc-search-item');
				a.id = listId + '-opt-' + i;
				a.setAttribute('role', 'option');
				a.setAttribute('aria-selected', 'false');
				a.href = it.url;

				if (show.image && it.image) {
					var img = el('img', 'fc-search-item__img');
					img.src = it.image;
					img.alt = '';
					img.loading = 'lazy';
					a.appendChild(img);
				}
				var body = el('span', 'fc-search-item__body');
				var title = el('span', 'fc-search-item__title');
				title.textContent = it.title;
				body.appendChild(title);
				if (show.sku && it.sku) {
					var sku = el('span', 'fc-search-item__sku');
					sku.textContent = it.sku;
					body.appendChild(sku);
				}
				if (show.price) {
					var price = el('span', 'fc-search-item__price');
					price.innerHTML = it.price_html || '';
					body.appendChild(price);
				}
				a.appendChild(body);
				panel.appendChild(a);
			});

			if (data.more_url) {
				var more = el('a', 'fc-search-more');
				more.href = data.more_url;
				more.textContent = cfg.labels.seeAll;
				panel.appendChild(more);
			}

			live.textContent = items.length + '';
			open();
		}

		function run(term) {
			if (controller) { controller.abort(); }
			controller = new AbortController();

			var body = new URLSearchParams({ action: cfg.action, _ajax_nonce: cfg.nonce, q: term });
			fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', signal: controller.signal })
				.then(function (r) { return r.json(); })
				.then(function (json) { if (json && json.success) { render(json.data); } })
				.catch(function () { /* aborted or network — ignore */ });
		}

		input.addEventListener('input', function () {
			var term = input.value.trim();
			if (timer) { clearTimeout(timer); }
			if (term.length < cfg.minChars) { close(); return; }
			timer = setTimeout(function () { run(term); }, cfg.debounce);
		});

		input.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				if (overlayMode) { closeOverlay(); } else { close(); }
				return;
			}
			if (panel.hidden) { return; }
			if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
			else if (e.key === 'Enter') {
				var opts = panel.querySelectorAll('[role="option"]');
				if (active > -1 && opts[active]) { e.preventDefault(); window.location.href = opts[active].href; }
				// Otherwise let the native form submit → results page.
			}
		});

		input.addEventListener('focus', function () {
			if (input.value.trim().length >= cfg.minChars && items.length) { open(); }
		});

		if (overlayMode) {
			setupOverlay();
		} else {
			setupAnchored();
		}

		// --- Overlay mode: icon → full-width bar below the header. ---------------

		function setupOverlay() {
			trigger = el('button', 'fc-search-trigger');
			trigger.type = 'button';
			trigger.setAttribute('aria-label', cfg.labels.toggle);
			trigger.setAttribute('aria-expanded', 'false');
			trigger.innerHTML = ICON;

			overlay = el('div', 'fc-search-overlay');
			var scrim = el('div', 'fc-search-scrim');
			bar = el('div', 'fc-search-bar');
			var inner = el('div', 'fc-search-bar__inner');

			var iconHint = el('span', 'fc-search-bar__icon');
			iconHint.innerHTML = ICON;

			var closeBtn = el('button', 'fc-search-close');
			closeBtn.type = 'button';
			closeBtn.setAttribute('aria-label', cfg.labels.close);
			closeBtn.innerHTML = CLOSE_ICON;

			// Drop the icon in where the form was, then move the form into the bar so
			// its native GET submit (and the mobile Search button) are preserved.
			form.parentNode.insertBefore(trigger, form);
			inner.appendChild(iconHint);
			inner.appendChild(form);
			inner.appendChild(closeBtn);
			bar.appendChild(inner);
			panel.classList.add('fc-search-panel--overlay');
			bar.appendChild(panel);
			overlay.appendChild(scrim);
			overlay.appendChild(bar);
			document.body.appendChild(overlay);

			trigger.addEventListener('click', openOverlay);
			closeBtn.addEventListener('click', closeOverlay);
			scrim.addEventListener('click', closeOverlay);
			window.addEventListener('resize', function () { if (overlay.classList.contains('is-open')) { placeBar(); } });
			window.addEventListener('scroll', function () { if (overlay.classList.contains('is-open')) { placeBar(); } }, true);
		}

		// Anchor the bar's top to the trigger's bottom edge so it sits just below
		// the header. Exposed as a CSS var so the mobile full-screen rule can ignore
		// it (it pins the bar to the top of the viewport instead).
		function placeBar() {
			var r = trigger.getBoundingClientRect();
			overlay.style.setProperty('--fc-bar-top', Math.max(0, r.bottom) + 'px');
		}

		function openOverlay() {
			placeBar();
			overlay.classList.add('is-open');
			document.body.classList.add('fc-search-open');
			trigger.setAttribute('aria-expanded', 'true');
			input.focus();
			if (input.value.trim().length >= cfg.minChars && items.length) { open(); }
		}

		function closeOverlay() {
			close();
			overlay.classList.remove('is-open');
			document.body.classList.remove('fc-search-open');
			trigger.setAttribute('aria-expanded', 'false');
			trigger.focus();
		}

		// --- Anchored mode: classic dropdown pinned under the field. -------------

		function setupAnchored() {
			panel.style.position = 'fixed';
			document.body.appendChild(panel);

			document.addEventListener('click', function (e) {
				if (e.target !== input && !panel.contains(e.target)) { close(); }
			});
			window.addEventListener('resize', function () { if (!panel.hidden) { position(); } });
			window.addEventListener('scroll', function () { if (!panel.hidden) { position(); } }, true);
		}

		function position() {
			var r = input.getBoundingClientRect();
			panel.style.position = 'fixed'; // viewport coords — no scroll offset needed.
			panel.style.top = r.bottom + 'px';
			panel.style.left = r.left + 'px';
			panel.style.width = r.width + 'px';
		}
	}

	function init() {
		var inputs = document.querySelectorAll(cfg.selector);
		Array.prototype.forEach.call(inputs, enhance);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
