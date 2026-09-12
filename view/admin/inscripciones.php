<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';

$estudiantesModel = new EstudiantesModel();
$cursosModel = new CursosModel();
$estudiantes = $estudiantesModel->getAll();
$cursos = $cursosModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $estudiantesModel->inscribirCurso($_POST['estudiante_id'], $_POST['curso_id'], $_POST['semestre'] ?? 1);
    header("Location: /centralizador_notas/view/admin/inscripciones.php?msg=created");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Inscribir Estudiantes</title>
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
        <h1>Inscribir Estudiante en Curso</h1>
        <?php if (isset($_GET['msg'])): ?><div class="alert alert-success">Estudiante inscrito exitosamente.</div><?php endif; ?>
        <div class="form-container">
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>Estudiante</label>
                    <select name="estudiante_id" required><?php foreach ($estudiantes as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre_completo']); ?> (<?php echo $e['ci']; ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" required><?php foreach ($cursos as $c): ?><option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?> - <?php echo htmlspecialchars($c['paralelo']); ?> (<?php echo (int) $c['gestion']; ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group"><label>Semestre</label><input type="number" name="semestre" value="1" min="1" max="2"></div>
                <button type="submit" class="btn btn-success">Inscribir</button>
            </form>
        </div>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
