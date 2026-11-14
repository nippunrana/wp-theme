/**
 * EgniTech One — palette suggestions on Theme Options > General > Appearance.
 * Data comes from inc/palette/palette-admin.php (window.egnitechPalettes).
 */
(function () {
    'use strict';

    var data = window.egnitechPalettes;
    var root = document.getElementById('egnitech-palette-suggestions');
    var notice = document.getElementById('egnitech-palette-notice');
    var replaceToggle = document.getElementById('egnitech-palette-replace');
    if (!data || !root) return;

    var t = data.i18n;
    var busy = false;

    /* A light-dark(#a, #b) value shows as a diagonal split, like the overview swatches. */
    function swatchBackground(value) {
        var m = /light-dark\(\s*(#[0-9a-f]+)\s*,\s*(#[0-9a-f]+)\s*\)/i.exec(value || '');
        return m ? 'linear-gradient(135deg, ' + m[1] + ' 50%, ' + m[2] + ' 50%)' : value;
    }

    function isCurrent(colors) {
        return data.slots.every(function (slug) {
            return !(slug in colors) || colors[slug] === data.current[slug];
        });
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    }

    function card(palette, isHistory) {
        var item = el('div', 'egnitech-palette-card');
        var swatches = el('div', 'egnitech-palette-card-swatches');
        data.slots.forEach(function (slug) {
            if (!palette.colors[slug]) return;
            var dot = el('span', 'egnitech-palette-card-dot');
            dot.style.background = swatchBackground(palette.colors[slug]);
            dot.title = slug;
            swatches.appendChild(dot);
        });
        item.appendChild(swatches);
        item.appendChild(el('span', 'egnitech-palette-card-label', palette.label));

        var current = isCurrent(palette.colors);
        var button = el('button', 'egnitech-btn egnitech-btn-secondary', current ? t.inUse : (isHistory ? t.restore : t.apply));
        button.type = 'button';
        button.disabled = current || busy;
        button.addEventListener('click', function () {
            // A history entry is an exact snapshot, so it always replaces every colour.
            apply(palette, isHistory || (replaceToggle && replaceToggle.checked));
        });
        item.appendChild(button);
        return item;
    }

    function section(title, palettes, message, isHistory) {
        var wrap = el('div', 'egnitech-palette-section');
        wrap.appendChild(el('h4', 'egnitech-palette-section-title', title));
        if (message) wrap.appendChild(el('p', 'egnitech-palette-message', message));
        if (palettes.length) {
            var grid = el('div', 'egnitech-palette-grid');
            palettes.forEach(function (p) { grid.appendChild(card(p, isHistory)); });
            wrap.appendChild(grid);
        }
        root.appendChild(wrap);
    }

    function render() {
        root.textContent = '';
        section(t.fromLogo, data.logo.palettes, data.logo.message, false);
        section(t.presets, data.presets, '', false);
        if (data.history.length) section(t.history, data.history, '', true);
    }

    function showNotice(text, isError) {
        if (!notice) return;
        notice.textContent = text;
        notice.className = 'egnitech-palette-notice' + (isError ? ' is-error' : '');
        notice.hidden = !text;
    }

    function post(action, fields) {
        var body = new FormData();
        body.append('action', action);
        body.append('security', data.nonce);
        Object.keys(fields).forEach(function (k) { body.append(k, fields[k]); });
        return fetch(data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); });
    }

    /* Keep the "Theme Color Palette" overview in step without a page reload. */
    function updateOverview() {
        document.querySelectorAll('.egnitech-palette-swatch[data-slug]').forEach(function (swatch) {
            var color = swatch.querySelector('.egnitech-palette-color');
            var value = data.current[swatch.getAttribute('data-slug')];
            if (color && value) color.style.background = swatchBackground(value);
        });
    }

    function apply(palette, replace) {
        busy = true;
        render();
        showNotice(t.applying, false);
        post('egnitech_one_apply_palette', {
            colors: JSON.stringify(palette.colors),
            label: palette.label,
            replace: replace ? '1' : ''
        }).then(function (res) {
            if (!res.success) throw new Error((res.data && res.data.message) || t.failed);
            data.current = res.data.current;
            data.history = res.data.history;
            updateOverview();
            var text = t.applied;
            if (res.data.skipped.length) text += ' ' + t.skipped.replace('%s', res.data.skipped.join(', '));
            showNotice(text, false);
        }).catch(function (err) {
            showNotice(err.message || t.failed, true);
        }).then(function () {
            busy = false;
            render();
        });
    }

    /* A new logo picked under Branding & Logo gets its own suggestions straight away, before saving. */
    var logoInput = document.getElementById('egnitech_one_light_logo_id');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            data.logo = { palettes: [], message: t.reading };
            render();
            post('egnitech_one_logo_palettes', { attachment_id: logoInput.value || '0' }).then(function (res) {
                if (!res.success) throw new Error(t.failed);
                data.logo = res.data;
            }).catch(function (err) {
                data.logo = { palettes: [], message: err.message };
            }).then(render);
        });
    }

    render();
})();
