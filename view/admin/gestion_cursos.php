<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$cursosModel = new CursosModel();
$carrerasModel = new CarrerasModel();
$gestionFiltro = isset($_GET['gestion']) && $_GET['gestion'] !== '' ? (int) $_GET['gestion'] : null;
$cursos = $gestionFiltro ? $cursosModel->getByGestion($gestionFiltro) : $cursosModel->getAll();
$carreras = $carrerasModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $carreraSel = null;
        foreach ($carreras as $c) {
            if ((int) $c['id'] === (int) $_POST['carrera_id']) { $carreraSel = $c; break; }
        }
        $semestre = ($carreraSel['tipo'] ?? 'anual') === 'semestral' ? (int) ($_POST['semestre'] ?? 1) : 1;
        $cursosModel->crear($_POST['nombre'], $_POST['anio'], $_POST['paralelo'], $_POST['carrera_id'], $_POST['gestion'], $semestre);
        header("Location: /centralizador_notas/view/admin/gestion_cursos.php?msg=created");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Cursos</title>
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
        <h1>Gestionar Cursos</h1>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
            <div class="alert alert-success">Curso creado exitosamente.</div>
        <?php endif; ?>

        <?php if ($gestionFiltro): ?>
            <div class="alert alert-info">Mostrando cursos de la gestion <?php echo (int) $gestionFiltro; ?> <a href="/centralizador_notas/view/admin/gestion_cursos.php" style="color:#fff; text-decoration:underline;">(ver todas)</a></div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Crear Nuevo Curso</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" placeholder="Ej: 1er Semestre Sistemas" required></div>
                <div class="form-group"><label>Anio</label><input type="number" name="anio" id="frmAnio" min="1" max="6" required></div>
                <div class="form-group"><label>Paralelo</label><input type="text" name="paralelo" value="A" required></div>
                <div class="form-group">
                    <label>Carrera</label>
                    <select name="carrera_id" id="frmCarrera" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo $c['id']; ?>" data-tipo="<?php echo htmlspecialchars($c['tipo']); ?>" data-duracion="<?php echo (int) $c['duracion']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="frmGestionWrap"><label>Gestion</label><input type="number" name="gestion" id="frmGestion" value="<?php echo date('Y'); ?>" required></div>
                <div class="form-group" id="frmSemestreWrap" style="display:none;"><label>Semestre</label><input type="number" name="semestre" value="1" min="1" max="2" required></div>
                <button type="submit" class="btn btn-success">Crear Curso</button>
            </form>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Anio</th><th>Paralelo</th><th>Carrera</th><th>Gestion</th><th>Semestre</th></tr></thead>
                <tbody>
                    <?php foreach ($cursos as $c): ?>
                    <tr>
                        <td><?php echo (int) $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                        <td><?php echo (int) $c['anio']; ?></td>
                        <td><?php echo htmlspecialchars($c['paralelo']); ?></td>
                        <td><?php echo htmlspecialchars($c['carrera_nombre']); ?></td>
                        <td><?php echo (int) $c['gestion']; ?></td>
                        <td><?php echo (int) $c['semestre']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    (function () {
        var sel = document.getElementById('frmCarrera');
        var anio = document.getElementById('frmAnio');
        var semWrap = document.getElementById('frmSemestreWrap');
        if (!sel || !anio || !semWrap) return;
        function actualizar() {
            var opt = sel.options[sel.selectedIndex];
            var tipo = opt && opt.getAttribute('data-tipo') ? opt.getAttribute('data-tipo') : '';
            var dur = opt && opt.getAttribute('data-duracion') ? parseInt(opt.getAttribute('data-duracion'), 10) : 3;
            semWrap.style.display = tipo === 'semestral' ? '' : 'none';
            anio.max = dur || 6;
            if (parseInt(anio.value, 10) > (dur || 6)) anio.value = '';
        }
        sel.addEventListener('change', actualizar);
        actualizar();
    })();
    </script>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
