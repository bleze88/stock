(function () {
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (!toggle || !nav) {
        return;
    }
    toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
})();

(function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
})();

(function () {
    var tables = document.querySelectorAll('.table-scroll');
    if (!tables.length) {
        return;
    }
    tables.forEach(function (el) {
        var wrapper = el.closest('.table-responsive') || el;
        function update() {
            var scrollable = el.scrollWidth > el.clientWidth + 1;
            var atStart = el.scrollLeft <= 1;
            var atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;
            wrapper.classList.toggle('can-scroll-right', scrollable && !atEnd);
            wrapper.classList.toggle('can-scroll-left', scrollable && !atStart);
        }
        update();
        el.addEventListener('scroll', update);
        window.addEventListener('resize', update);
    });
})();

(function () {
    var colorInput = document.querySelector('[data-color-input]');
    var swatches = document.querySelectorAll('[data-color-presets] .color-swatch');
    if (!colorInput || !swatches.length) {
        return;
    }
    swatches.forEach(function (btn) {
        btn.addEventListener('click', function () {
            colorInput.value = btn.dataset.color;
        });
    });
})();
