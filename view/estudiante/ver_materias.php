<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$model = new EstudiantesModel();
$materias = $model->getMaterias($_SESSION['est_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Mis Materias</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</head>
<body>
    <canvas id="canvas"></canvas>
    <?php include __DIR__ . '/../../includes/menu_estudiante.php'; ?>
    <div class="top-header">
        <div class="logo-area">
            <button id="sidebar-toggle" type="button" title="Desplegar o contraer el menu" aria-label="Desplegar o contraer el menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <img src="/centralizador_notas/view/img/escudo.jpg" alt="Logo"><span>Instituto Tecnologico PACCIOLI</span>
        </div>
        <div class="user-area">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['est_name']); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>
    <div class="container">
        <h1>Mis Materias</h1>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Carrera</th><th>Materia</th><th>Codigo</th><th>Curso</th><th>Anio</th><th>Paralelo</th><th>Docente</th></tr></thead>
                <tbody>
                    <?php if (empty($materias)): ?>
                        <tr><td colspan="7" style="text-align:center;">No tiene materias asignadas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($materias as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['carrera']); ?></td>
                            <td><?php echo htmlspecialchars($m['materia']); ?></td>
                            <td><?php echo htmlspecialchars($m['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($m['curso']); ?></td>
                            <td><?php echo $m['anio']; ?></td>
                            <td><?php echo $m['paralelo']; ?></td>
                            <td><?php echo htmlspecialchars($m['docente']); ?></td>
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
