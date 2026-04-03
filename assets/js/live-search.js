(function () {
    'use strict';

    var overlay = document.getElementById('live-search-overlay');
    var input = document.getElementById('live-search-input');
    var results = document.getElementById('live-search-results');
    var defaultEl = document.getElementById('live-search-default');
    var trigger = document.getElementById('header-search-trigger');
    var closeBtn = document.getElementById('live-search-close');
    var backdrop = document.getElementById('live-search-backdrop');
    var recentWrap = document.getElementById('live-search-recent-wrap');
    var recentEl = document.getElementById('live-search-recent');
    var config = window.holyprofwebSearch || {};
    var timer = null;
    var currentXHR = null;

    if (!overlay || !input || !results) return;

    function escHTML(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function openOverlay() {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('search-open');
        renderRecentSearches();
        setTimeout(function () { input.focus(); }, 40);
    }

    function closeOverlay() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('search-open');
        input.value = '';
        results.innerHTML = '';
        results.style.display = 'none';
        if (defaultEl) defaultEl.style.display = '';
    }

    function saveRecentSearch(query) {
        if (!query) return;
        try {
            var items = JSON.parse(localStorage.getItem('hpw_recent_searches') || '[]');
            items = items.filter(function (entry) { return entry !== query; });
            items.unshift(query);
            localStorage.setItem('hpw_recent_searches', JSON.stringify(items.slice(0, 6)));
        } catch (err) {}
    }

    function renderRecentSearches() {
        if (!recentWrap || !recentEl) return;
        try {
            var items = JSON.parse(localStorage.getItem('hpw_recent_searches') || '[]');
            if (!items.length) {
                recentWrap.hidden = true;
                recentEl.innerHTML = '';
                return;
            }
            recentWrap.hidden = false;
            recentEl.innerHTML = items.map(function (item) {
                return '<a href="/?s=' + encodeURIComponent(item) + '" class="live-search-trending-pill">' + escHTML(item) + '</a>';
            }).join('');
        } catch (err) {
            recentWrap.hidden = true;
        }
    }

    function renderResults(data, query) {
        var html = '';

        if (data.posts && data.posts.length) {
            html += '<div class="ls-group"><p class="ls-group-label">Articles</p><ul class="ls-list">';
            data.posts.forEach(function (post) {
                html += '<li class="ls-item">' +
                    '<a href="' + escHTML(post.url) + '" class="ls-item-link">' +
                    '<img src="' + escHTML(post.thumb_url) + '" alt="" class="ls-thumb" loading="lazy" />' +
                    '<span class="ls-item-body">' +
                    (post.category_name ? '<span class="ls-item-cat">' + escHTML(post.category_name) + '</span>' : '') +
                    '<span class="ls-item-title">' + escHTML(post.title) + '</span>' +
                    (post.excerpt ? '<span class="ls-item-excerpt">' + escHTML(post.excerpt) + '</span>' : '') +
                    '</span></a></li>';
            });
            html += '</ul></div>';
        }

        if (data.categories && data.categories.length) {
            html += '<div class="ls-group"><p class="ls-group-label">Categories</p><ul class="ls-list ls-list--cats">';
            data.categories.forEach(function (cat) {
                html += '<li class="ls-item ls-item--cat"><a href="' + escHTML(cat.url) + '" class="ls-item-link ls-item-link--cat"><span>' + escHTML(cat.name) + '</span><span class="ls-cat-count">' + escHTML(cat.count) + '</span></a></li>';
            });
            html += '</ul></div>';
        }

        if (data.suggestions && data.suggestions.length) {
            html += '<div class="ls-group"><p class="ls-group-label">Suggestions</p><div class="ls-suggestions">';
            data.suggestions.forEach(function (term) {
                html += '<a href="/?s=' + encodeURIComponent(term) + '" class="ls-suggestion-pill">' + escHTML(term) + '</a>';
            });
            html += '</div></div>';
        }

        if (!html) {
            html = '<div class="ls-no-results"><p>No instant results for <strong>' + escHTML(query) + '</strong>.</p><a href="/?s=' + encodeURIComponent(query) + '" class="ls-full-search">View full results</a></div>';
        }

        html += '<div class="ls-footer"><a href="/?s=' + encodeURIComponent(query) + '" class="ls-full-search">See all results for &ldquo;' + escHTML(query) + '&rdquo;</a></div>';
        results.innerHTML = html;
        results.style.display = '';
        if (defaultEl) defaultEl.style.display = 'none';
    }

    function doSearch(query) {
        if (!query || query.length < 2) {
            results.innerHTML = '';
            results.style.display = 'none';
            if (defaultEl) defaultEl.style.display = '';
            renderRecentSearches();
            return;
        }

        if (currentXHR) {
            currentXHR.abort();
            currentXHR = null;
        }

        results.innerHTML = '<div class="ls-loading">Searching...</div>';
        results.style.display = '';
        if (defaultEl) defaultEl.style.display = 'none';

        currentXHR = new XMLHttpRequest();
        currentXHR.open(
            'GET',
            (config.ajaxurl || '/wp-admin/admin-ajax.php') +
            '?action=holyprofweb_search&q=' + encodeURIComponent(query) +
            '&nonce=' + encodeURIComponent(config.nonce || ''),
            true
        );
        currentXHR.onreadystatechange = function () {
            if (currentXHR.readyState !== 4) return;
            if (currentXHR.status !== 200) return;
            try {
                var json = JSON.parse(currentXHR.responseText);
                if (json.success) renderResults(json.data, query);
            } catch (err) {}
        };
        currentXHR.send();
    }

    if (trigger) trigger.addEventListener('click', openOverlay);
    if (closeBtn) closeBtn.addEventListener('click', closeOverlay);
    if (backdrop) backdrop.addEventListener('click', closeOverlay);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && overlay.classList.contains('is-open')) closeOverlay();
    });

    input.addEventListener('input', function () {
        var query = input.value.trim();
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
            doSearch(query);
        }, 220);
    });

    input.addEventListener('keydown', function (event) {
        var query = input.value.trim();
        if (event.key === 'Enter' && query) {
            saveRecentSearch(query);
            window.location.href = '/?s=' + encodeURIComponent(query);
        }
    });

    document.addEventListener('click', function (event) {
        var pill = event.target.closest('.ls-suggestion-pill, .live-search-trending-pill');
        if (pill) saveRecentSearch((pill.textContent || '').trim());
    });
})();
