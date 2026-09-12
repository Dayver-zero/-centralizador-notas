<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';

$carrerasModel = new CarrerasModel();
$docentesModel = new DocentesModel();
$estudiantesModel = new EstudiantesModel();
$cursosModel = new CursosModel();

$totalCarreras = count($carrerasModel->getAll());
$totalDocentes = count($docentesModel->getAll());
$totalEstudiantes = count($estudiantesModel->getAll());
$totalCursos = count($cursosModel->getAll());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</head>
<body>
    <canvas id="canvas"></canvas>

    <?php include __DIR__ . '/../../includes/menu_admin.php'; ?>

    <div class="top-header">
        <div class="logo-area">
            <button id="sidebar-toggle" type="button" title="Desplegar o contraer el menu" aria-label="Desplegar o contraer el menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <img src="/centralizador_notas/view/img/escudo.jpg" alt="Logo"><span>Instituto Tecnologico PACCIOLI</span>
        </div>
        <div class="user-area">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>

    <div class="container">
        <h1>Panel Administrativo</h1>
        <h2>Vista general del sistema</h2>

        <div class="cards-grid">
            <div class="card">
                <i class="fas fa-graduation-cap"></i>
                <h3><?php echo $totalCarreras; ?></h3>
                <p>Carreras</p>
            </div>
            <div class="card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3><?php echo $totalDocentes; ?></h3>
                <p>Docentes</p>
            </div>
            <div class="card">
                <i class="fas fa-user-graduate"></i>
                <h3><?php echo $totalEstudiantes; ?></h3>
                <p>Estudiantes</p>
            </div>
            <div class="card">
                <i class="fas fa-chalkboard"></i>
                <h3><?php echo $totalCursos; ?></h3>
                <p>Cursos</p>
            </div>
        </div>

        <h2 style="margin-top:20px;">Accesos Rapidos</h2>
        <div class="cards-grid">
            <a href="/centralizador_notas/view/admin/gestion_carreras.php" style="text-decoration:none;">
                <div class="card"><i class="fas fa-plus-circle"></i><h3>+</h3><p>Crear Carrera</p></div>
            </a>
            <a href="/centralizador_notas/view/admin/gestion_docentes.php" style="text-decoration:none;">
                <div class="card"><i class="fas fa-user-plus"></i><h3>+</h3><p>Agregar Docente</p></div>
            </a>
            <a href="/centralizador_notas/view/admin/gestion_estudiantes.php" style="text-decoration:none;">
                <div class="card"><i class="fas fa-user-plus"></i><h3>+</h3><p>Agregar Estudiante</p></div>
            </a>
            <a href="/centralizador_notas/view/admin/gestion_cursos.php" style="text-decoration:none;">
                <div class="card"><i class="fas fa-plus-circle"></i><h3>+</h3><p>Crear Curso</p></div>
            </a>
        </div>
    </div>

    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
