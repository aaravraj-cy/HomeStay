/* Sonam Homestay - Simple vanilla JS (no jQuery) */

document.addEventListener('DOMContentLoaded', function () {
    // AOS animation
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 600, once: true });
    }

    // Dark mode
    var root = document.documentElement;
    var saved = localStorage.getItem('sn-theme') || 'light';
    root.setAttribute('data-theme', saved);
    updateThemeIcon(saved);

    var themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('sn-theme', next);
            updateThemeIcon(next);
        });
    }

    function updateThemeIcon(theme) {
        var icon = document.querySelector('#themeToggle i');
        if (!icon) return;
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    // Password show/hide
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = this.parentElement.querySelector('input');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            var i = this.querySelector('i');
            if (i) i.classList.toggle('fa-eye');
            if (i) i.classList.toggle('fa-eye-slash');
        });
    });

    // Mobile sidebar
    document.querySelectorAll('.mobile-sidebar-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var side = document.getElementById('snSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (side) side.classList.add('open');
            if (overlay) overlay.classList.add('show');
        });
    });
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', function () {
            var side = document.getElementById('snSidebar');
            if (side) side.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // Date min = today
    var today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(function (el) {
        if (!el.getAttribute('min')) el.setAttribute('min', today);
    });

    // Swiper
    if (typeof Swiper !== 'undefined') {
        if (document.querySelector('.featured-swiper')) {
            new Swiper('.featured-swiper', {
                slidesPerView: 1,
                spaceBetween: 20,
                breakpoints: {
                    576: { slidesPerView: 2 },
                    992: { slidesPerView: 3 }
                }
            });
        }
        if (document.querySelector('.detail-swiper')) {
            new Swiper('.detail-swiper', {
                slidesPerView: 1,
                navigation: {
                    nextEl: '.detail-next',
                    prevEl: '.detail-prev'
                }
            });
        }
    }

    // Simple booking price calc
    var roomSelect = document.getElementById('roomSelect');
    var checkIn = document.getElementById('book_check_in');
    var checkOut = document.getElementById('book_check_out');
    function calcPrice() {
        if (!roomSelect) return;
        var opt = roomSelect.options[roomSelect.selectedIndex];
        if (!opt) return;
        var price = parseFloat(opt.getAttribute('data-price') || 0);
        var cleaning = parseFloat(opt.getAttribute('data-cleaning') || 0);
        var nights = 1;
        if (checkIn && checkOut && checkIn.value && checkOut.value) {
            var d1 = new Date(checkIn.value);
            var d2 = new Date(checkOut.value);
            nights = Math.max(1, Math.round((d2 - d1) / 86400000));
        }
        var subtotal = price * nights;
        var service = Math.round(subtotal * 0.05 * 100) / 100;
        var total = subtotal + cleaning + service;
        var elN = document.getElementById('calcNights');
        var elS = document.getElementById('calcSubtotal');
        var elC = document.getElementById('calcCleaning');
        var elV = document.getElementById('calcService');
        var elT = document.getElementById('calcTotal');
        if (elN) elN.textContent = nights;
        if (elS) elS.textContent = '₹' + subtotal.toFixed(2);
        if (elC) elC.textContent = '₹' + cleaning.toFixed(2);
        if (elV) elV.textContent = '₹' + service.toFixed(2);
        if (elT) elT.textContent = '₹' + total.toFixed(2);
    }
    if (roomSelect) roomSelect.addEventListener('change', calcPrice);
    if (checkIn) checkIn.addEventListener('change', calcPrice);
    if (checkOut) checkOut.addEventListener('change', calcPrice);
});
