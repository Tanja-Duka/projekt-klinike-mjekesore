/* ============================================================
   main.js — small helpers shared across redesigned pages
   - Mobile nav toggle
   - Active link highlight (data-page attribute on <body>)
   - Search dropdown demo
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    // Mobile nav
    const burger = document.getElementById('navHamburger');
    const menu   = document.getElementById('navMenu');
    if (burger && menu) {
        burger.addEventListener('click', () => menu.classList.toggle('open'));
    }

    // Highlight active link based on body[data-page]
    const page = document.body.dataset.page;
    if (page) {
        document.querySelectorAll(`[data-link="${page}"]`).forEach(a => a.classList.add('active'));
    }

    // Demo search dropdown
    const searchInput = document.getElementById('navSearch');
    const searchResults = document.getElementById('searchResults');
    if (searchInput && searchResults) {
        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim().length > 0) searchResults.classList.add('show');
        });
        searchInput.addEventListener('input', () => {
            if (searchInput.value.trim().length > 0) searchResults.classList.add('show');
            else searchResults.classList.remove('show');
        });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.navbar-search')) searchResults.classList.remove('show');
        });
    }

    // Time slot picker (reserve)
    document.querySelectorAll('.time-slots').forEach(group => {
        group.addEventListener('click', (e) => {
            const slot = e.target.closest('.time-slot');
            if (!slot || slot.classList.contains('unavailable')) return;
            group.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            slot.classList.add('selected');
        });
    });
});
