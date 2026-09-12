<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';

$cursoId   = isset($_GET['curso_id'])  ? (int) $_GET['curso_id']  : 0;
$materiaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$docenteId = (int) $_SESSION['referer_id'];

$cursosModel    = new CursosModel();
$estudiantesModel = new EstudiantesModel();

if (!$cursoId || !$materiaId || !$cursosModel->esDocenteAsignado($docenteId, $materiaId, $cursoId)) {
    header("Location: /centralizador_notas/view/docente/dashboard.php?error=forbidden");
    exit;
}

$curso     = $cursosModel->getById($cursoId);
$estudiantes = $estudiantesModel->getByCurso($cursoId);

$materia = null;
$stmt = $conn->prepare("SELECT * FROM materias WHERE id = ?");
$stmt->bind_param("i", $materiaId);
$stmt->execute();
$materia = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Estudiantes</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</head>
<body>
    <canvas id="canvas"></canvas>
    <?php include __DIR__ . '/../../includes/menu_docente.php'; ?>

    <div class="top-header">
        <div class="logo-area">
            <button id="sidebar-toggle" type="button" title="Desplegar o contraer el menu" aria-label="Desplegar o contraer el menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <img src="/centralizador_notas/view/img/escudo.jpg" alt="Logo"><span>Instituto Tecnologico PACCIOLI</span>
        </div>
        <div class="user-area">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>

    <div class="container">
        <h1>Estudiantes - <?php echo htmlspecialchars($materia['nombre'] ?? ''); ?></h1>
        <h2>Curso: <?php echo htmlspecialchars($curso['nombre'] ?? ''); ?> | Gestion: <?php echo (int) ($curso['gestion'] ?? 0); ?> | Paralelo: <?php echo htmlspecialchars($curso['paralelo'] ?? ''); ?></h2>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr><th>#</th><th>Nombre Completo</th><th>C.I.</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($estudiantes)): ?>
                        <tr><td colspan="3" style="text-align:center;">No hay estudiantes inscritos.</td></tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($estudiantes as $e): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($e['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($e['ci']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="/centralizador_notas/view/docente/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
