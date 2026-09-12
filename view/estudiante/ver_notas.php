<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/NotasModel.php';

$notasModel = new NotasModel();
$notas = $notasModel->getResumenNotas($_SESSION['est_id']);

// Agrupar notas por materia
$porMateria = [];
foreach ($notas as $n) {
    $key = $n['materia'];
    if (!isset($porMateria[$key])) {
        $porMateria[$key] = ['codigo' => $n['codigo'], 'notas' => []];
    }
    $porMateria[$key]['notas'][] = $n;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Mis Notas</title>
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
        <h1>Mis Notas</h1>

        <?php if (empty($porMateria)): ?>
            <div class="alert alert-info">No tiene notas registradas aun.</div>
        <?php else: ?>
            <?php foreach ($porMateria as $materia => $data): ?>
            <h2 style="text-align:left; margin-top:25px;"><?php echo htmlspecialchars($materia); ?> <small style="color:rgba(255,255,255,0.5);">(<?php echo $data['codigo']; ?>)</small></h2>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>Tipo</th><th>Actividad</th><th>Nota</th><th>Gestion</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['notas'] as $n): ?>
                        <tr>
                            <td><?php echo ucfirst($n['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($n['nombre_actividad']); ?></td>
                            <td><strong><?php echo number_format($n['nota'], 1); ?></strong></td>
                            <td><?php echo $n['gestion']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
