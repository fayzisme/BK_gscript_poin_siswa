/* BK & SP · app.js */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        /* ── Sidebar toggle ── */
        const toggles = document.querySelectorAll('[data-sidebar-toggle]');
        const body = document.body;
        const sidebar = document.getElementById('appSidebar');
        let lastWidth = window.innerWidth;

        const isMobile = () => window.innerWidth < 992;

        /* Sinkronkan atribut aksesibilitas & tooltip mode rail */
        function syncAria() {
            const collapsed = body.classList.contains('sidebar-collapsed');
            toggles.forEach(t => t.setAttribute('aria-expanded', String(!collapsed)));
            // Saat minimize (desktop): beri tooltip judul menu utk setiap link
            if (sidebar) {
                sidebar.querySelectorAll('.nav-link').forEach(a => {
                    const label = a.querySelector('span');
                    const text = label ? label.textContent.trim() : '';
                    if (label) {
                        if (collapsed) {
                            a.setAttribute('title', text);
                            a.setAttribute('data-title', text);
                        } else {
                            a.removeAttribute('title');
                            a.removeAttribute('data-title');
                        }
                    }
                });
            }
        }

        function setSidebar(open) {
            if (isMobile()) {
                if (open) body.classList.add('sidebar-open');
                else body.classList.remove('sidebar-open');
            } else {
                if (open) body.classList.remove('sidebar-collapsed');
                else body.classList.add('sidebar-collapsed');
                // simpan preferensi pengguna (desktop)
                try { localStorage.setItem('sidebar-collapsed', String(!open)); } catch (e) {}
            }
            syncAria();
        }

        toggles.forEach(el => {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();          // cegah double-fire (button & backdrop)
                // "open" = sidebar sedang TERBUKA (expanded)
                const open = isMobile()
                    ? body.classList.contains('sidebar-open')        // mobile: ada class → terbuka
                    : !body.classList.contains('sidebar-collapsed'); // desktop: tidak collapsed → terbuka
                setSidebar(!open);
            });
        });

        // Backdrop click (mobile): tutup sidebar.
        // Tombol toggle juga punya data-sidebar-toggle & bisa menembak event
        // yang sama → stopPropagation di atas mencegah double-fire.
        const backdrop = document.querySelector('.sidebar-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', () => setSidebar(false));
        }

        // Pulihkan preferensi pengguna (desktop saja)
        if (!isMobile()) {
            try {
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    body.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        }
        syncAria();

        // Responsive
        window.addEventListener('resize', () => {
            if ((lastWidth < 992) !== (window.innerWidth < 992)) {
                body.classList.remove('sidebar-open');
                // saat pindah ke mobile, bersihkan state desktop (collapsed)
                // agar slide-in tidak terganggu oleh aturan CSS collapsed.
                if (isMobile()) body.classList.remove('sidebar-collapsed');
                lastWidth = window.innerWidth;
                syncAria();
            }
        });

        /* ── Toast helper global ── */
        window.showToast = function (message, type = 'success') {
            const cls = { success: 'text-bg-success', danger: 'text-bg-danger', warning: 'text-bg-warning' }[type] || 'text-bg-success';
            const el = document.createElement('div');
            el.className = 'toast align-items-center border-0 ' + cls;
            el.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div>';
            const container = document.getElementById('toastContainer');
            if (container) {
                container.appendChild(el);
                const t = new bootstrap.Toast(el, { delay: 3500 });
                t.show();
                el.addEventListener('hidden.bs.toast', () => el.remove());
            }
        };

        /* ── Flatpickr tanggal ── */
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('input[type="date"]').forEach(el => {
                try {
                    flatpickr(el, { dateFormat: 'Y-m-d', allowInput: true });
                } catch (e) { /* silent */ }
            });
        }
    });
})();