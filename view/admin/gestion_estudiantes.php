<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$model = new EstudiantesModel();
$estudiantes = $model->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $model->crear($_POST['ci'], $_POST['nombre'], $_POST['matricula'], $_POST['anio_ingreso'], $_POST['email'], $_POST['password']);
        header("Location: /centralizador_notas/view/admin/gestion_estudiantes.php?msg=created");
        exit;
    } elseif ($accion === 'editar') {
        $model->actualizar($_POST['id'], $_POST['ci'], $_POST['nombre'], $_POST['matricula'], $_POST['anio_ingreso'], $_POST['email'], $_POST['estado']);
        header("Location: /centralizador_notas/view/admin/gestion_estudiantes.php?msg=updated");
        exit;
    } elseif ($accion === 'eliminar') {
        $model->eliminar($_POST['id']);
        header("Location: /centralizador_notas/view/admin/gestion_estudiantes.php?msg=deleted");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Estudiantes</title>
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
        <h1>Gestionar Estudiantes</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['msg']) {
                    case 'created': echo 'Estudiante creado exitosamente.'; break;
                    case 'updated': echo 'Estudiante actualizado exitosamente.'; break;
                    case 'deleted': echo 'Estudiante eliminado exitosamente.'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Agregar Nuevo Estudiante</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>C.I.</label><input type="text" name="ci" required></div>
                <div class="form-group"><label>Nombre Completo</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Matricula</label><input type="text" name="matricula" required></div>
                <div class="form-group"><label>Anio de Ingreso</label><input type="number" name="anio_ingreso" value="<?php echo date('Y'); ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Contrasena</label><input type="password" name="password" required></div>
                <button type="submit" class="btn btn-success">Agregar Estudiante</button>
            </form>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr><th>ID</th><th>C.I.</th><th>Nombre</th><th>Matricula</th><th>Anio Ingreso</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($estudiantes as $e): ?>
                    <tr>
                        <td><?php echo (int) $e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['ci']); ?></td>
                        <td><?php echo htmlspecialchars($e['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($e['matricula']); ?></td>
                        <td><?php echo (int) $e['anio_ingreso']; ?></td>
                        <td><?php echo htmlspecialchars($e['estado']); ?></td>
                        <td>
                            <button class="btn btn-primary" onclick="editarEstudiante(<?php echo (int) $e['id']; ?>, '<?php echo htmlspecialchars($e['ci'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($e['nombre_completo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($e['matricula'], ENT_QUOTES); ?>', '<?php echo (int) $e['anio_ingreso']; ?>', '<?php echo htmlspecialchars($e['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($e['estado'], ENT_QUOTES); ?>')">Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este estudiante?')">
                                <?php echo csrf_campo(); ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo (int) $e['id']; ?>">
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
            <h3>Editar Estudiante</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group"><label>C.I.</label><input type="text" name="ci" id="editCi" required></div>
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="editNombre" required></div>
                <div class="form-group"><label>Matricula</label><input type="text" name="matricula" id="editMatricula" required></div>
                <div class="form-group"><label>Anio Ingreso</label><input type="number" name="anio_ingreso" id="editAnio" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail"></div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="editEstado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalEditar').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarEstudiante(id, ci, nombre, matricula, anio, email, estado) {
        document.getElementById('editId').value = id;
        document.getElementById('editCi').value = ci;
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editMatricula').value = matricula;
        document.getElementById('editAnio').value = anio;
        document.getElementById('editEmail').value = email;
        document.getElementById('editEstado').value = estado;
        document.getElementById('modalEditar').classList.add('active');
    }
    </script>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
