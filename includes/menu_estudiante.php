<div class="sidebar">
    <div class="menu-title">Panel Estudiante</div>

    <a href="/centralizador_notas/view/estudiante/dashboard.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></div>
    </a>

    <a href="/centralizador_notas/view/estudiante/ver_materias.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-book-open"></i><span>Mis Materias</span></div>
    </a>

    <a href="/centralizador_notas/view/estudiante/ver_notas.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-clipboard-check"></i><span>Mis Notas</span></div>
    </a>

    <a href="/centralizador_notas/view/estudiante/ver_asistencia.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-calendar-check"></i><span>Mi Asistencia</span></div>
    </a>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-comments"></i><span>Comunicacion</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/estudiante/mensajes.php">Bandeja de Entrada</a>
        <a href="/centralizador_notas/view/estudiante/enviar_mensaje.php">Enviar Mensaje</a>
    </div>

    <a href="/centralizador_notas/logout.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesion</span></div>
    </a>
</div>
