<div class="sidebar">
    <div class="menu-title">Panel Docente</div>

    <a href="/centralizador_notas/view/docente/dashboard.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></div>
    </a>

    <a href="/centralizador_notas/view/docente/lista_materias.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-book-open"></i><span>Mis Materias</span></div>
    </a>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-file-alt"></i><span>Gestion Academica</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/docente/registro_pedagogico.php">Registro Pedagogico</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-comments"></i><span>Comunicacion</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/docente/mensajes.php">Bandeja de Entrada</a>
        <a href="/centralizador_notas/view/docente/enviar_mensaje.php">Enviar Mensaje</a>
    </div>

    <a href="/centralizador_notas/logout.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesion</span></div>
    </a>
</div>
