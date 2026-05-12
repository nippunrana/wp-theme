/**
 * EgniTech One — Scroll-to-Top Button
 *
 * @package EgniTech_One
 * @since   1.1.0
 */
(function () {
    'use strict';

    // Create button element.
    var btn = document.createElement('button');
    btn.className = 'egnitech-scroll-top';
    btn.setAttribute('aria-label', 'Scroll to top');
    btn.setAttribute('type', 'button');
    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>';

    document.body.appendChild(btn);

    // Show / hide on scroll.
    var threshold = 300;
    var ticking = false;

    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                if (window.scrollY > threshold) {
                    btn.classList.add('visible');
                } else {
                    btn.classList.remove('visible');
                }
                ticking = false;
            });
            ticking = true;
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });

    // Smooth scroll to top.
    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
