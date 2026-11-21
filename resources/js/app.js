import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Transitions are switched off while the page boots (html.is-booting, set in <head> by
// partials/admin-theme): otherwise every colour and size Alpine applies right after load
// would visibly fade or slide on each refresh. Re-enable them once the first frames of the
// final layout are on screen.
requestAnimationFrame(() => requestAnimationFrame(() => {
    document.documentElement.classList.remove('is-booting');
}));

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
