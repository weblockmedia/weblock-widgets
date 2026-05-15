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

        var result = buildShortcode(form);
        codeInput.value = result.shortcode;

        if (result.missingRequired) {
            target.innerHTML = '<p class="wlw-preview__hint">' + (cfg.i18n.missingReq || 'Töltsd ki a kötelező mezőket.') + '</p>';
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
                        target.innerHTML = data.data.html || '<p class="wlw-preview__hint">' + (cfg.i18n.previewErr || 'Üres válasz.') + '</p>';
                    } else {
                        target.innerHTML = '<p class="wlw-preview__hint wlw-preview__hint--err">' + (cfg.i18n.previewErr || 'Hiba.') + '</p>';
                    }
                })
                .catch(function () {
                    target.innerHTML = '<p class="wlw-preview__hint wlw-preview__hint--err">' + (cfg.i18n.previewErr || 'Hiba.') + '</p>';
                });
        }, force ? 0 : 600);
    }

    function copyToClipboard(text, button) {
        var done = function () {
            var original = button.innerHTML;
            button.innerHTML = '<span class="dashicons dashicons-yes"></span> ' + (cfg.i18n.copied || 'Másolva!');
            button.classList.add('is-copied');
            setTimeout(function () {
                button.innerHTML = original;
                button.classList.remove('is-copied');
            }, 1800);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, function () { fallback(); });
        } else {
            fallback();
        }

        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            done();
        }
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

        var refreshBtn = document.querySelector('[data-wlw-refresh]');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () { refreshPreview(form, true); });
        }

        refreshPreview(form, true);
    }

    document.addEventListener('DOMContentLoaded', initConfigurator);
})();
