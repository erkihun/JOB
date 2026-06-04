import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Scroll-reveal — GPU-only (opacity + transform), zero dependencies
(function () {
    var els = document.querySelectorAll('.scroll-animate');
    if (!els.length) return;

    function done(el) { el.classList.add('sa-done'); }

    if (!('IntersectionObserver' in window)) {
        els.forEach(done);
        return;
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var el = e.target;
            el.classList.add('in-view');
            el.addEventListener('animationend', function () { done(el); }, { once: true });
            io.unobserve(el);
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

    els.forEach(function (el) { io.observe(el); });
}());
