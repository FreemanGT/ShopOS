/**
 * Card Image Effects — gallery-slider mode.
 *
 * Progressive enhancement over a native CSS scroll-snap slider: prev/next
 * arrows and mouse drag-to-scroll. Swipe is the native scroll-snap (touch +
 * trackpad, RTL-correct).
 *
 * Seamless infinite loop: the first and last slides are cloned onto the
 * opposite ends. Stepping past an edge animates one slide smoothly onto a clone
 * (so the wrap looks like any other transition), then — once scrolling settles
 * — silently snaps to the matching real slide. This covers both arrow clicks
 * and native swipe past an edge.
 *
 * Positioning: smooth navigation uses scrollIntoView (RTL-correct, and only at
 * interaction time, when the card is already on-screen, so it never scrolls the
 * page). Instant repositioning (initial start + clone snap) adjusts the
 * viewport's own scrollLeft by a measured screen-space delta — RTL-correct and
 * page-safe (it never moves an off-screen card into view on load).
 *
 * Re-initialises sliders appended by Infinite Scroll / Quick View via a
 * MutationObserver. Controls live inside the product-link anchor, so every
 * control click is stopped from navigating, and a click right after a drag is
 * suppressed.
 */
(function () {
	'use strict';

	var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var DRAG_THRESHOLD = 6;
	var SETTLE_MS = 120;

	function slice(list) {
		return Array.prototype.slice.call(list);
	}

	// Index of the slide whose centre is nearest the viewport centre.
	function activeIndex(vp, slides) {
		var r = vp.getBoundingClientRect();
		var center = r.left + r.width / 2;
		var best = 0;
		var bestDist = Infinity;
		for (var i = 0; i < slides.length; i++) {
			var sr = slides[i].getBoundingClientRect();
			var dist = Math.abs(sr.left + sr.width / 2 - center);
			if (dist < bestDist) {
				bestDist = dist;
				best = i;
			}
		}
		return best;
	}

	// Smooth scroll a slide to the inline-start edge (RTL-correct). Safe at
	// interaction time — the card is on-screen, so block:'nearest' is a no-op
	// vertically and the page never moves.
	function scrollToSlide(slide, smooth) {
		slide.scrollIntoView({
			behavior: (!smooth || REDUCED) ? 'auto' : 'smooth',
			block: 'nearest',
			inline: 'start'
		});
	}

	// Instant reposition by adjusting the viewport's own scrollLeft by the
	// screen-space delta between the slide and the viewport. RTL-correct in
	// modern browsers (scrollLeft's effect direction is consistent) and, unlike
	// scrollIntoView, it never scrolls the page to an off-screen card.
	function snapInstant(vp, slide) {
		vp.scrollLeft += slide.getBoundingClientRect().left - vp.getBoundingClientRect().left;
	}

	function stop(e) {
		e.preventDefault();
		e.stopPropagation();
	}

	function init(slider) {
		if (slider.getAttribute('data-fc-init')) {
			return;
		}
		slider.setAttribute('data-fc-init', '1');

		var vp = slider.querySelector('.fc-card-slider__viewport');
		var real = vp ? slice(vp.children) : [];
		if (!vp || real.length < 2) {
			return;
		}

		// Clone the two ends so a step past an edge has a real-looking slide to
		// animate onto before we snap back to the twin.
		var headClone = real[0].cloneNode(true);
		var tailClone = real[real.length - 1].cloneNode(true);
		[headClone, tailClone].forEach(function (c) {
			c.setAttribute('aria-hidden', 'true');
			c.setAttribute('data-fc-clone', '1');
		});
		vp.insertBefore(tailClone, real[0]); // tail clone before the first real
		vp.appendChild(headClone);           // head clone after the last real

		var slides = slice(vp.children);     // [tailClone, ...real, headClone]
		var FIRST = 1;                       // first real DOM index
		var LAST = real.length;              // last real DOM index

		// Start on the first real slide (instant, page-safe).
		snapInstant(vp, slides[FIRST]);

		// Arrows — step ±1 from the current centred slide, animated.
		var prev = slider.querySelector('[data-fc-slider-prev]');
		var next = slider.querySelector('[data-fc-slider-next]');
		if (prev) {
			prev.addEventListener('click', function (e) {
				stop(e);
				scrollToSlide(slides[activeIndex(vp, slides) - 1] || slides[0], true);
			});
		}
		if (next) {
			next.addEventListener('click', function (e) {
				stop(e);
				scrollToSlide(slides[activeIndex(vp, slides) + 1] || slides[slides.length - 1], true);
			});
		}

		// Once scrolling settles on a clone, snap silently to its real twin —
		// covers arrow steps and native swipe past an edge alike.
		var settle;
		vp.addEventListener('scroll', function () {
			clearTimeout(settle);
			settle = setTimeout(function () {
				var i = activeIndex(vp, slides);
				if (i === 0) {
					snapInstant(vp, slides[LAST]);
				} else if (i === slides.length - 1) {
					snapInstant(vp, slides[FIRST]);
				}
			}, SETTLE_MS);
		}, { passive: true });

		// Mouse drag-to-scroll (touch / trackpad already scroll natively).
		var down = false;
		var moved = false;
		var startX = 0;
		var startScroll = 0;
		vp.addEventListener('pointerdown', function (e) {
			if (e.pointerType !== 'mouse') {
				return;
			}
			down = true;
			moved = false;
			startX = e.clientX;
			startScroll = vp.scrollLeft;
		});
		vp.addEventListener('pointermove', function (e) {
			if (!down) {
				return;
			}
			var dx = e.clientX - startX;
			if (Math.abs(dx) > DRAG_THRESHOLD) {
				moved = true;
			}
			vp.scrollLeft = startScroll - dx;
		});
		window.addEventListener('pointerup', function () {
			if (down && moved) {
				scrollToSlide(slides[activeIndex(vp, slides)], true);
			}
			down = false;
		});
		// Suppress the link navigation fired by the click after a drag.
		slider.addEventListener('click', function (e) {
			if (moved) {
				moved = false;
				e.preventDefault();
				e.stopPropagation();
			}
		}, true);
	}

	function boot(root) {
		var scope = root && root.querySelectorAll ? root : document;
		var sliders = scope.querySelectorAll('[data-fc-card-slider]:not([data-fc-init])');
		Array.prototype.forEach.call(sliders, init);
	}

	// Initial pass.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			boot(document);
		});
	} else {
		boot(document);
	}
	window.addEventListener('load', function () {
		boot(document);
	});

	// Re-init Infinite-Scroll- / Quick-View-appended cards.
	if (window.MutationObserver) {
		var observer = new MutationObserver(function (mutations) {
			for (var i = 0; i < mutations.length; i++) {
				if (mutations[i].addedNodes && mutations[i].addedNodes.length) {
					boot(document);
					return;
				}
			}
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}
})();
