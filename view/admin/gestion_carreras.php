<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$model = new CarrerasModel();
$carreras = $model->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $model->crear($_POST['nombre'], $_POST['duracion']);
        header("Location: /centralizador_notas/view/admin/gestion_carreras.php?msg=created");
        exit;
    } elseif ($accion === 'editar') {
        $model->actualizar($_POST['id'], $_POST['nombre'], $_POST['duracion'], $_POST['estado']);
        header("Location: /centralizador_notas/view/admin/gestion_carreras.php?msg=updated");
        exit;
    } elseif ($accion === 'eliminar') {
        $model->eliminar($_POST['id']);
        header("Location: /centralizador_notas/view/admin/gestion_carreras.php?msg=deleted");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Carreras</title>
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
        <h1>Gestionar Carreras</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['msg']) {
                    case 'created': echo 'Carrera creada exitosamente.'; break;
                    case 'updated': echo 'Carrera actualizada exitosamente.'; break;
                    case 'deleted': echo 'Carrera eliminada exitosamente.'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Crear Nueva Carrera</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Duracion</label>
                    <input type="text" name="duracion" value="4 anos" required>
                </div>
                <button type="submit" class="btn btn-success">Crear Carrera</button>
            </form>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Duracion</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carreras as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($c['duracion']); ?></td>
                        <td><?php echo htmlspecialchars($c['estado']); ?></td>
                        <td>
                            <button class="btn btn-primary" onclick="editarCarrera(<?php echo (int) $c['id']; ?>, '<?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['duracion'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['estado'], ENT_QUOTES); ?>')">Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar esta carrera?')">
                                <?php echo csrf_campo(); ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <h3>Editar Carrera</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="editNombre" required>
                </div>
                <div class="form-group">
                    <label>Duracion</label>
                    <input type="text" name="duracion" id="editDuracion" required>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="editEstado">
                        <option value="activa">Activa</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalEditar').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarCarrera(id, nombre, duracion, estado) {
        document.getElementById('editId').value = id;
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editDuracion').value = duracion;
        document.getElementById('editEstado').value = estado;
        document.getElementById('modalEditar').classList.add('active');
    }
    </script>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
