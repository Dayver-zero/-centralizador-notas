// Menu lateral oculto por defecto (se despliega al hacer clic en la hamburguesa)
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    const logoArea = document.querySelector('.logo-area');
    let btn = document.getElementById('sidebar-toggle');
    if (logoArea && !btn) {
        btn = document.createElement('button');
        btn.id = 'sidebar-toggle';
        btn.type = 'button';
        btn.title = 'Mostrar menu';
        btn.setAttribute('aria-label', 'Mostrar u ocultar menu');
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        logoArea.insertBefore(btn, logoArea.firstChild);
    }

    const menuTitle = sidebar.querySelector('.menu-title');
    let closeBtn = document.getElementById('sidebar-close');
    if (menuTitle && !closeBtn) {
        closeBtn = document.createElement('button');
        closeBtn.id = 'sidebar-close';
        closeBtn.type = 'button';
        closeBtn.title = 'Cerrar menu';
        closeBtn.setAttribute('aria-label', 'Cerrar menu');
        closeBtn.innerHTML = '&times;';
        menuTitle.appendChild(closeBtn);
    }

    document.documentElement.classList.add('sidebar-ready');

    function setSidebar(open) {
        document.body.classList.toggle('sidebar-open', open);
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (btn) {
        btn.addEventListener('click', function () {
            setSidebar(!document.body.classList.contains('sidebar-open'));
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () { setSidebar(false); });
    }

    document.addEventListener('click', function (e) {
        if (!document.body.classList.contains('sidebar-open')) return;
        if (e.target.closest('.sidebar') || e.target.closest('#sidebar-toggle')) return;
        setSidebar(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setSidebar(false);
    });
});

function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector('.arrow');

    if (submenu && submenu.classList.contains('submenu')) {
        submenu.classList.toggle('active');
        if (arrow) {
            arrow.style.transform = submenu.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
        }
    }
}

// Modo oscuro / claro
const modoBtn = document.getElementById('modo-btn');
if (modoBtn) {
    // Cargar preferencia guardada
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        modoBtn.textContent = '☀️';
    }

    modoBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        modoBtn.textContent = isDark ? '☀️' : '🌙';
        localStorage.setItem('darkMode', isDark);
    });
}
