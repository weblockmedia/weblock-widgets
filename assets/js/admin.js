(function () {
    'use strict';

    var cfg = window.WLW_ADMIN || {};
    var previewTimer = null;

    function escapeAttr(val) {
        return String(val).replace(/"/g, '&quot;');
    }

    function getFieldValue(input) {
        if (input.type === 'checkbox') {
            return input.checked ? 'yes' : 'no';
        }
        if (input.tagName === 'TEXTAREA') {
            return input.value.split(/\r?\n+/).map(function (s) { return s.trim(); }).filter(Boolean).join(',');
        }
        return input.value;
    }

    function isDefault(input) {
        var def = input.getAttribute('data-default') || '';
        if (input.type === 'checkbox') {
            return getFieldValue(input) === def;
        }
        return String(input.value).trim() === String(def).trim();
    }

    function isEmpty(input) {
        if (input.type === 'checkbox') return false;
        return String(input.value).trim() === '';
    }

    function buildShortcode(form) {
        var name = form.getAttribute('data-shortcode');
        var inputs = form.querySelectorAll('[data-wlw-input]');
        var parts = [];
        var missingRequired = false;

        inputs.forEach(function (input) {
            var key = input.name;
            if (!key) return;
            if (input.hasAttribute('required') && isEmpty(input)) {
                missingRequired = true;
            }
            if (isEmpty(input)) return;
            if (isDefault(input)) return;
            var val = getFieldValue(input);
            parts.push(key + '="' + escapeAttr(val) + '"');
        });

        var sc = '[' + name + (parts.length ? ' ' + parts.join(' ') : '') + ']';
        return { shortcode: sc, missingRequired: missingRequired };
    }

    function refreshPreview(form, force) {
        var target = document.querySelector('[data-wlw-preview]');
        var codeInput = document.querySelector('[data-wlw-code]');
        if (!target || !codeInput) return;

        var outputMode = form.getAttribute('data-output') || 'shortcode';
        var result = buildShortcode(form);
        if (outputMode !== 'html') {
            codeInput.value = result.shortcode;
        }

        if (result.missingRequired) {
            target.innerHTML = '<p class="wlw-preview__hint">' + (cfg.i18n.missingReq || 'Töltsd ki a kötelező mezőket.') + '</p>';
            if (outputMode === 'html') { codeInput.value = ''; }
            return;
        }

        clearTimeout(previewTimer);
        previewTimer = setTimeout(function () {
            target.innerHTML = '<p class="wlw-preview__hint">' + (cfg.i18n.loading || 'Betöltés…') + '</p>';

            var body = new FormData();
            body.append('action', 'wlw_preview');
            body.append('nonce', cfg.nonce);
            body.append('shortcode', result.shortcode);

            fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.success && data.data && typeof data.data.html === 'string') {
                        var html = data.data.html || '';
                        target.innerHTML = html || '<p class="wlw-preview__hint">' + (cfg.i18n.previewErr || 'Üres válasz.') + '</p>';
                        if (outputMode === 'html') {
                            codeInput.value = html.trim();
                        }
                    } else {
                        target.innerHTML = '<p class="wlw-preview__hint wlw-preview__hint--err">' + (cfg.i18n.previewErr || 'Hiba.') + '</p>';
                    }
                })
                .catch(function () {
                    target.innerHTML = '<p class="wlw-preview__hint wlw-preview__hint--err">' + (cfg.i18n.previewErr || 'Hiba.') + '</p>';
                });
        }, force ? 0 : 600);
    }

    function flashCopied(button) {
        var original = button.innerHTML;
        button.innerHTML = '<span class="dashicons dashicons-yes"></span> ' + (cfg.i18n.copied || 'Másolva!');
        button.classList.add('is-copied');
        setTimeout(function () {
            button.innerHTML = original;
            button.classList.remove('is-copied');
        }, 1800);
    }

    function copyToClipboard(text, button) {
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            flashCopied(button);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { flashCopied(button); }, fallback);
        } else {
            fallback();
        }
    }

    function copyRichHtml(html, button) {
        // Plain text fallback: strip tags
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        var plain = (tmp.innerText || tmp.textContent || '').replace(/\n{3,}/g, '\n\n').trim();

        function legacyFallback() {
            // contentEditable div + selection + execCommand('copy')
            var div = document.createElement('div');
            div.contentEditable = 'true';
            div.innerHTML = html;
            div.style.position = 'fixed';
            div.style.left = '-9999px';
            div.style.top = '0';
            div.style.opacity = '0';
            document.body.appendChild(div);

            var range = document.createRange();
            range.selectNodeContents(div);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);

            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) {}
            sel.removeAllRanges();
            document.body.removeChild(div);

            if (ok) { flashCopied(button); }
            else    { copyToClipboard(plain, button); }
        }

        if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write && window.isSecureContext) {
            try {
                var item = new ClipboardItem({
                    'text/html':  new Blob([ html  ], { type: 'text/html'  }),
                    'text/plain': new Blob([ plain ], { type: 'text/plain' })
                });
                navigator.clipboard.write([ item ]).then(
                    function () { flashCopied(button); },
                    legacyFallback
                );
                return;
            } catch (e) {
                legacyFallback();
                return;
            }
        }
        legacyFallback();
    }

    function initPlaceSearch(container, form) {
        var query    = container.querySelector('[data-wlw-place-query]');
        var goBtn    = container.querySelector('[data-wlw-place-go]');
        var results  = container.querySelector('[data-wlw-place-results]');
        var hidden   = container.querySelector('input[data-wlw-input]');
        var selected = container.querySelector('[data-wlw-place-selected]');
        var nameEl   = container.querySelector('[data-wlw-place-name]');
        var idEl     = container.querySelector('[data-wlw-place-id]');
        var clearBtn = container.querySelector('[data-wlw-place-clear]');

        function setSelected(name, placeId) {
            hidden.value = placeId;
            nameEl.textContent = name;
            idEl.textContent = placeId;
            selected.hidden = false;
            results.hidden = true;
            results.innerHTML = '';
            query.value = '';
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function clear() {
            hidden.value = '';
            selected.hidden = true;
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function renderResults(list) {
            if (!list.length) {
                results.innerHTML = '<p class="wlw-place-search__empty">Nincs találat. Próbálj pontosabb keresést (cégnév + város).</p>';
                results.hidden = false;
                return;
            }
            results.innerHTML = '';
            list.forEach(function (p) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'wlw-place-search__item';
                btn.innerHTML =
                    '<span class="wlw-place-search__item-name">' + escapeHtml(p.name) + '</span>' +
                    (p.rating ? '<span class="wlw-place-search__item-rating">★ ' + p.rating.toFixed(1) + ' (' + p.count + ')</span>' : '') +
                    '<span class="wlw-place-search__item-addr">' + escapeHtml(p.address) + '</span>';
                btn.addEventListener('click', function () { setSelected(p.name, p.place_id); });
                results.appendChild(btn);
            });
            results.hidden = false;
        }

        function search() {
            var q = query.value.trim();
            if (q.indexOf('ChIJ') === 0 || q.indexOf('GhIJ') === 0) {
                setSelected(q, q);
                return;
            }
            if (q.length < 3) {
                results.innerHTML = '<p class="wlw-place-search__empty">Adj meg legalább 3 karaktert.</p>';
                results.hidden = false;
                return;
            }
            results.innerHTML = '<p class="wlw-place-search__loading">Keresés…</p>';
            results.hidden = false;

            var body = new FormData();
            body.append('action', 'wlw_search_place');
            body.append('nonce', cfg.nonce);
            body.append('query', q);

            fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.success && data.data && Array.isArray(data.data.results)) {
                        renderResults(data.data.results);
                    } else {
                        var msg = (data && data.data && data.data.message) || 'Hiba a keresésnél.';
                        results.innerHTML = '<p class="wlw-place-search__empty">' + escapeHtml(msg) + '</p>';
                    }
                })
                .catch(function () {
                    results.innerHTML = '<p class="wlw-place-search__empty">Hálózati hiba.</p>';
                });
        }

        goBtn.addEventListener('click', search);
        query.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); search(); }
        });
        clearBtn.addEventListener('click', clear);

        if (hidden.value) {
            nameEl.textContent = hidden.value;
            idEl.textContent = hidden.value;
            selected.hidden = false;
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initConfigurator() {
        var form = document.querySelector('[data-wlw-form]');
        if (!form) return;

        document.querySelectorAll('[data-wlw-place]').forEach(function (c) {
            initPlaceSearch(c, form);
        });

        form.addEventListener('input', function () { refreshPreview(form, false); });
        form.addEventListener('change', function () { refreshPreview(form, false); });

        var copyBtn = document.querySelector('[data-wlw-copy]');
        var codeInput = document.querySelector('[data-wlw-code]');
        if (copyBtn && codeInput) {
            copyBtn.addEventListener('click', function () {
                if (!codeInput.value) return;
                copyToClipboard(codeInput.value, copyBtn);
            });
        }

        var copyRichBtn = document.querySelector('[data-wlw-copy-rich]');
        var previewEl   = document.querySelector('[data-wlw-preview]');
        if (copyRichBtn && previewEl) {
            copyRichBtn.addEventListener('click', function () {
                var html = previewEl.innerHTML.trim();
                if (!html || previewEl.querySelector('.wlw-preview__hint')) {
                    return;
                }
                copyRichHtml(html, copyRichBtn);
            });
        }

        var refreshBtn = document.querySelector('[data-wlw-refresh]');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () { refreshPreview(form, true); });
        }

        refreshPreview(form, true);
    }

    function initGalleryFilter() {
        var gallery = document.querySelector('[data-wlw-gallery]');
        if (!gallery) return;
        var tabs    = document.querySelectorAll('.wlw-tab');
        var search  = document.getElementById('wlw-gallery-search');
        var cards   = gallery.querySelectorAll('.wlw-card');
        var empty   = gallery.querySelector('.wlw-gallery__empty');
        var state   = { cat: 'all', q: '' };

        function apply() {
            var q = state.q.trim().toLowerCase();
            var visible = 0;
            cards.forEach(function (card) {
                var matchCat = state.cat === 'all' || card.getAttribute('data-wlw-cat') === state.cat;
                var matchQ   = !q || (card.getAttribute('data-wlw-search') || '').indexOf(q) !== -1;
                var show = matchCat && matchQ;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (empty) empty.hidden = visible !== 0;
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) {
                    t.classList.remove('is-active');
                    t.setAttribute('aria-selected', 'false');
                });
                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');
                state.cat = tab.getAttribute('data-wlw-cat') || 'all';
                apply();
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                state.q = search.value;
                apply();
            });
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { search.value = ''; state.q = ''; apply(); }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initConfigurator();
        initGalleryFilter();
    });
})();
