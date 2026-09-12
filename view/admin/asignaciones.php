<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$cursosModel = new CursosModel();
$docentesModel = new DocentesModel();
$materiasModel = new MateriasModel();

$docentes = $docentesModel->getAll();
$materias = $materiasModel->getAll();
$cursos = $cursosModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $cursosModel->asignarDocenteMateria($_POST['docente_id'], $_POST['materia_id'], $_POST['curso_id']);
    header("Location: /centralizador_notas/view/admin/asignaciones.php?msg=created");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Asignar Docentes a Materias</title>
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
        <h1>Asignar Docente a Materia/Curso</h1>
        <?php if (isset($_GET['msg'])): ?><div class="alert alert-success">Asignacion creada exitosamente.</div><?php endif; ?>
        <div class="form-container">
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>Docente</label>
                    <select name="docente_id" required><?php foreach ($docentes as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre_completo']); ?></option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Materia</label>
                    <select name="materia_id" required><?php foreach ($materias as $m): ?><option value="<?php echo (int) $m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?> (<?php echo htmlspecialchars($m['codigo']); ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" required><?php foreach ($cursos as $c): ?><option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?> - <?php echo htmlspecialchars($c['paralelo']); ?></option><?php endforeach; ?></select>
                </div>
                <button type="submit" class="btn btn-success">Asignar</button>
            </form>
        </div>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
