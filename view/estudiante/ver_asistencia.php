<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/AsistenciaModel.php';

$estudiantesModel = new EstudiantesModel();
$asistenciaModel = new AsistenciaModel();

$materias = $estudiantesModel->getMaterias($_SESSION['est_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Mi Asistencia</title>
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
        <h1>Mi Asistencia</h1>

        <?php foreach ($materias as $m):
            $resumen = $asistenciaModel->getResumenAsistencia($_SESSION['est_id'], $m['curso_id'], $m['materia_id']);
        ?>
        <h2 style="text-align:left; margin-top:25px;"><?php echo htmlspecialchars($m['materia']); ?></h2>
        <div class="cards-grid">
            <div class="card"><i class="fas fa-check-circle" style="color:#28a745;"></i><h3><?php echo $resumen['presente']; ?></h3><p>Presente</p></div>
            <div class="card"><i class="fas fa-times-circle" style="color:#dc3545;"></i><h3><?php echo $resumen['ausente']; ?></h3><p>Ausente</p></div>
            <div class="card"><i class="fas fa-exclamation-circle" style="color:#ffc107;"></i><h3><?php echo $resumen['justificado']; ?></h3><p>Justificado</p></div>
            <div class="card"><i class="fas fa-percentage" style="color:#007acc;"></i><h3><?php echo $resumen['porcentaje']; ?>%</h3><p>Porcentaje</p></div>
        </div>
        <?php endforeach; ?>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
