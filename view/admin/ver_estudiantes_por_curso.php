<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$cursosModel = new CursosModel();
$estudiantesModel = new EstudiantesModel();

$cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : null;
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : null;

$curso = $cursoId ? $cursosModel->getById($cursoId) : null;
$estudiantes = ($cursoId && $anio) ? $estudiantesModel->getByCurso($cursoId) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Estudiantes por Curso</title>
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
        <h1>Estudiantes - <?php echo htmlspecialchars($curso['nombre'] ?? ''); ?> (<?php echo htmlspecialchars($curso['carrera_nombre'] ?? ''); ?>)</h1>
        <h2>Gestion <?php echo $anio ? (int) $anio : ''; ?></h2>

        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Nombre Completo</th><th>C.I.</th><th>Matricula</th><th>Anio Ingreso</th></tr></thead>
                <tbody>
                    <?php if (empty($estudiantes)): ?>
                        <tr><td colspan="4" style="text-align:center;">No hay estudiantes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($estudiantes as $e): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($e['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($e['ci']); ?></td>
                            <td><?php echo htmlspecialchars($e['matricula']); ?></td>
                            <td><?php echo (int) $e['anio_ingreso']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
