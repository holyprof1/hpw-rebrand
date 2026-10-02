/* Holyprofweb site script: theme toggle and mobile menu. Search lives in live-search.js. */
(function () {
    'use strict';

    var root = document.documentElement;

    // ---- Theme (light / dark; follows the system until the reader chooses) ----
    var themeBtn = document.getElementById('theme-toggle');

    function effectiveTheme() {
        var t = root.getAttribute('data-theme');
        if (t === 'dark' || t === 'light') return t;
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function paintToggle() {
        if (themeBtn) themeBtn.setAttribute('aria-pressed', effectiveTheme() === 'dark' ? 'true' : 'false');
    }

    if (themeBtn) {
        paintToggle();
        themeBtn.addEventListener('click', function () {
            var next = effectiveTheme() === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            root.style.colorScheme = next;
            try { localStorage.setItem('hpw-theme', next); } catch (e) {}
            paintToggle();
        });
    }

    // ---- Mobile menu ----
    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('site-navigation');

    function setMenu(open) {
        if (!toggle || !nav) return;
        nav.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            setMenu(!nav.classList.contains('is-open'));
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setMenu(false);
        });
        document.addEventListener('click', function (e) {
            if (nav.classList.contains('is-open') && !nav.contains(e.target) && !toggle.contains(e.target)) setMenu(false);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 860) setMenu(false);
        });
    }
})();
