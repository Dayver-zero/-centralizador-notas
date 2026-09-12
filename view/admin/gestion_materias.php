<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$model = new MateriasModel();
$carrerasModel = new CarrerasModel();
$materias = $model->getAll();
$carreras = $carrerasModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') { $model->crear($_POST['nombre'], $_POST['codigo'], $_POST['carrera_id']); header("Location: /centralizador_notas/view/admin/gestion_materias.php?msg=created"); exit; }
    elseif ($accion === 'eliminar') { $model->eliminar($_POST['id']); header("Location: /centralizador_notas/view/admin/gestion_materias.php?msg=deleted"); exit; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Gestionar Materias</title>
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
        <h1>Gestionar Materias</h1>
        <?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><?php echo $_GET['msg'] === 'created' ? 'Materia creada.' : 'Materia eliminada.'; ?></div><?php endif; ?>
        <div class="form-container">
            <h3>Crear Nueva Materia</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Codigo</label><input type="text" name="codigo" required></div>
                <div class="form-group"><label>Carrera</label><select name="carrera_id" required><?php foreach ($carreras as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="btn btn-success">Crear Materia</button>
            </form>
        </div>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Codigo</th><th>Carrera</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($materias as $m): ?>
                    <tr>
                        <td><?php echo (int) $m['id']; ?></td>
                        <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($m['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($m['carrera_nombre']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?')">
                                <?php echo csrf_campo(); ?>
                                <input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
