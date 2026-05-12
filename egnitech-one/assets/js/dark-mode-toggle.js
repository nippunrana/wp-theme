/**
 * EgniTech One — Dark/Light Mode Toggle
 *
 * Respects OS preference by default, allows user override via toggle button.
 * Persists choice in localStorage.
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'egnitech-one-color-scheme';
    const htmlEl = document.documentElement;

    /**
     * Get the OS preferred scheme.
     */
    function getOSPreference() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    /**
     * Apply the color scheme via class/attribute and toggle button update.
     * Note: Inline script handles initial apply, this is only for toggle logic.
     */
    function applyScheme(scheme) {
        htmlEl.style.colorScheme = scheme;
        htmlEl.setAttribute('data-scheme', scheme);
        updateToggleButton(scheme);
    }

    /**
     * Update the toggle button icon and aria-label.
     */
    function updateToggleButton(scheme) {
        const btn = document.querySelector('.egnitech-dark-mode-toggle');
        if (!btn) return;

        const sunIcon = btn.querySelector('.icon-sun');
        const moonIcon = btn.querySelector('.icon-moon');

        if (scheme === 'dark') {
            btn.setAttribute('aria-label', 'Switch to light mode');
            if (sunIcon) sunIcon.style.display = 'block';
            if (moonIcon) moonIcon.style.display = 'none';
        } else {
            btn.setAttribute('aria-label', 'Switch to dark mode');
            if (sunIcon) sunIcon.style.display = 'none';
            if (moonIcon) moonIcon.style.display = 'block';
        }
    }

    /**
     * Toggle between light and dark mode.
     */
    function toggleScheme() {
        const current = htmlEl.getAttribute('data-scheme') || getOSPreference();
        const next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem(STORAGE_KEY, next);
        applyScheme(next);
    }

    /**
     * Initialize on DOM ready.
     */
    function init() {
        // Initial state update for toggle button.
        const currentScheme = htmlEl.getAttribute('data-scheme') || getOSPreference();
        updateToggleButton(currentScheme);

        // Bind toggle button.
        const btn = document.querySelector('.egnitech-dark-mode-toggle');
        if (btn) {
            btn.addEventListener('click', toggleScheme);
        }

        // Listen for OS preference changes (only if user hasn't explicitly chosen).
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            if (!localStorage.getItem(STORAGE_KEY)) {
                applyScheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
