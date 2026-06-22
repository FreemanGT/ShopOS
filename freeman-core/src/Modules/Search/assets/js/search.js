/**
 * Freeman Search — live product dropdown.
 *
 * Progressive enhancement: attaches a results dropdown to the theme's existing
 * search input(s) (targeted by the configurable `selector`). With JS off the
 * native form submits unchanged. Debounced fetch + AbortController, combobox
 * keyboard/ARIA, server-rendered price HTML.
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

	function enhance(input) {
		if (input.dataset.fcSearchBound) { return; }
		input.dataset.fcSearchBound = '1';

		var listId = 'fc-search-list-' + (++idSeq);
		var panel = el('div', 'fc-search-panel');
		panel.id = listId;
		panel.setAttribute('role', 'listbox');
		panel.hidden = true;
		document.body.appendChild(panel);

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

		function position() {
			var r = input.getBoundingClientRect();
			panel.style.position = 'fixed'; // viewport coords — no scroll offset needed.
			panel.style.top = r.bottom + 'px';
			panel.style.left = r.left + 'px';
			panel.style.width = r.width + 'px';
		}

		function close() {
			panel.hidden = true;
			input.setAttribute('aria-expanded', 'false');
			input.removeAttribute('aria-activedescendant');
			active = -1;
		}

		function open() {
			if (panel.hidden) {
				position();
				panel.hidden = false;
				input.setAttribute('aria-expanded', 'true');
			}
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

				if (it.image) {
					var img = el('img', 'fc-search-item__img');
					img.src = it.image;
					img.alt = '';
					img.loading = 'lazy';
					a.appendChild(img);
				}
				var body = el('span', 'fc-search-item__body');
				var title = el('span', 'fc-search-item__title');
				title.textContent = it.title;
				var price = el('span', 'fc-search-item__price');
				price.innerHTML = it.price_html || '';
				body.appendChild(title);
				body.appendChild(price);
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
			if (panel.hidden) { return; }
			if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
			else if (e.key === 'Enter') {
				var opts = panel.querySelectorAll('[role="option"]');
				if (active > -1 && opts[active]) { e.preventDefault(); window.location.href = opts[active].href; }
			} else if (e.key === 'Escape') { close(); }
		});

		input.addEventListener('focus', function () {
			if (input.value.trim().length >= cfg.minChars && items.length) { open(); }
		});

		document.addEventListener('click', function (e) {
			if (e.target !== input && !panel.contains(e.target)) { close(); }
		});
		window.addEventListener('resize', function () { if (!panel.hidden) { position(); } });
		window.addEventListener('scroll', function () { if (!panel.hidden) { position(); } }, true);
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
