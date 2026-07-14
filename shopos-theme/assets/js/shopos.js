/*
 * ShopOS Theme — tiny front-end enhancer.
 * Kept intentionally small: module JS belongs in ShopOS Core.
 */
(function () {
    'use strict';

    // Flag JS availability so CSS can style enhanced vs. basic states.
    document.documentElement.classList.add('shopos-js');

    // Expose a minimal theme event bus for modules / cart drawers to hook into.
    if (!window.ShopOS) {
        window.ShopOS = {
            version: '1.0.0',
            on: function (event, cb) {
                document.addEventListener('shopos:' + event, cb);
            },
            emit: function (event, detail) {
                document.dispatchEvent(new CustomEvent('shopos:' + event, { detail: detail }));
            }
        };
    }
})();
