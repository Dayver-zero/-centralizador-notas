<div class="sidebar">
    <div class="menu-title">Panel Administrativo</div>

    <a href="/centralizador_notas/view/admin/dashboard.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></div>
    </a>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-calendar-alt"></i><span>Anios de Clase</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/anios_clase.php">Gestionar Anios</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-graduation-cap"></i><span>Carreras</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/gestion_carreras.php">Gestionar Carreras</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-book"></i><span>Materias</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/gestion_materias.php">Gestionar Materias</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-users"></i><span>Docentes</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/gestion_docentes.php">Gestionar Docentes</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-user-graduate"></i><span>Estudiantes</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/gestion_estudiantes.php">Gestionar Estudiantes</a>
        <a href="/centralizador_notas/view/admin/inscripciones.php">Inscribir en Cursos</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-chalkboard"></i><span>Cursos</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/gestion_cursos.php">Gestionar Cursos</a>
        <a href="/centralizador_notas/view/admin/asignaciones.php">Asignar Docentes</a>
        <a href="/centralizador_notas/view/admin/gestion_periodos.php">Gestionar Parciales</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-file-alt"></i><span>Historial Académico</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/historial_academico.php">Consultar Notas</a>
    </div>

    <div class="menu-item" onclick="toggleSubmenu(this)">
        <div class="menu-label"><i class="fas fa-comments"></i><span>Mensajes</span></div>
        <i class="fas fa-chevron-right arrow"></i>
    </div>
    <div class="submenu">
        <a href="/centralizador_notas/view/admin/mensajes.php">Bandeja de Entrada</a>
    </div>

    <a href="/centralizador_notas/logout.php" class="menu-item">
        <div class="menu-label"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesion</span></div>
    </a>
</div>
