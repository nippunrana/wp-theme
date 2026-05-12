/**
 * EgniTech One — Reading Progress Bar
 *
 * @package EgniTech_One
 * @since   1.2.0
 */
(function () {
    'use strict';

    var bar = document.createElement('div');
    bar.className = 'egnitech-progress-bar';
    document.body.appendChild(bar);

    var ticking = false;

    function updateProgress() {
        var scrollTop = window.scrollY;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;

        if (docHeight <= 0) {
            bar.style.width = '0%';
            return;
        }

        var progress = Math.min((scrollTop / docHeight) * 100, 100);
        bar.style.width = progress + '%';
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                updateProgress();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // Initial update.
    updateProgress();
})();
