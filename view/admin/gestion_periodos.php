<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';

$cursosModel = new CursosModel();
$parcialModel = new ParcialPeriodoModel();
$adminId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion  = $_POST['accion'] ?? '';
    $cursoId = (int) ($_POST['curso_id'] ?? 0);
    $gestion = (int) ($_POST['gestion'] ?? 0);
    $parcial = $_POST['parcial'] ?? '';

    if ($cursoId && $parcial !== '') {
        if ($accion === 'abrir') {
            $parcialModel->abrir($cursoId, $gestion, $parcial, $adminId);
            header("Location: /centralizador_notas/view/admin/gestion_periodos.php?curso_id=$cursoId&msg=abierto");
            exit;
        } elseif ($accion === 'cerrar') {
            $parcialModel->cerrar($cursoId, $gestion, $parcial);
            header("Location: /centralizador_notas/view/admin/gestion_periodos.php?curso_id=$cursoId&msg=cerrado");
            exit;
        }
    }
}

$gestionFiltro = isset($_GET['gestion']) && $_GET['gestion'] !== '' ? (int) $_GET['gestion'] : null;
$cursos = $gestionFiltro ? $cursosModel->getByGestion($gestionFiltro) : $cursosModel->getAll();
$cursoSelId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

$resumenes = [];
foreach ($cursos as $c) {
    $resumenes[$c['id']] = $parcialModel->resumenCurso((int) $c['id'], (int) ($c['gestion'] ?? date('Y')), $c['carrera_tipo'] ?? 'anual');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Parciales</title>
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
        <h1>Gestionar Parciales (Temporadas)</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php echo $_GET['msg'] === 'abierto' ? 'Parcial abierto: los docentes ya pueden registrar sus notas.' : 'Parcial cerrado.'; ?>
            </div>
        <?php endif; ?>

        <p class="ayuda" style="margin-bottom:15px;">
            Abre un parcial para que los docentes del curso puedan registrar notas de ese parcial. Cuando todas las materias "envían" sus notas, el siguiente parcial se abre automáticamente.
        </p>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Carrera</th>
                        <th>Tipo</th>
                        <th>Gestión</th>
                        <th colspan="5">Parciales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cursos)): ?>
                        <tr><td colspan="9">No hay cursos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cursos as $c):
                            $tipo = $c['carrera_tipo'] ?? 'anual';
                            $res = $resumenes[$c['id']] ?? [];
                            ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong> - <?php echo htmlspecialchars($c['paralelo']); ?></td>
                            <td><?php echo htmlspecialchars($c['carrera_nombre']); ?></td>
                            <td><?php echo $tipo === 'semestral' ? 'Semestral' : 'Anual'; ?></td>
                            <td><?php echo (int) $c['gestion']; ?></td>
                            <?php foreach (ParcialPeriodoModel::opciones($tipo) as $parcial):
                                $d = $res[$parcial] ?? ['total' => 0, 'abiertos' => 0, 'enviados' => 0, 'cerrados' => 0];
                                $abiertos = (int) $d['abiertos'];
                                $enviados = (int) $d['enviados'];
                                $total    = (int) $d['total'];
                                $texto = $abiertos > 0 ? 'ABIERTO' : ($enviados >= $total && $total > 0 ? 'ENVIADO' : 'CERRADO');
                                $cls = $abiertos > 0 ? 'badge-abierto' : ($enviados >= $total && $total > 0 ? 'badge-enviado' : 'badge-cerrado');
                                ?>
                                <td style="text-align:center; vertical-align:middle;">
                                    <div>
                                        <span class="<?php echo $cls; ?>" style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; color:#fff; margin-bottom:4px;"><?php echo $texto; ?></span>
                                    </div>
                                    <form method="POST" style="display:inline;">
                                        <?php echo csrf_campo(); ?>
                                        <input type="hidden" name="accion" value="<?php echo $abiertos > 0 ? 'cerrar' : 'abrir'; ?>">
                                        <input type="hidden" name="curso_id" value="<?php echo (int) $c['id']; ?>">
                                        <input type="hidden" name="gestion" value="<?php echo (int) $c['gestion']; ?>">
                                        <input type="hidden" name="parcial" value="<?php echo htmlspecialchars($parcial); ?>">
                                        <button type="submit" class="btn <?php echo $abiertos > 0 ? 'btn-danger' : 'btn-primary'; ?>" style="padding:2px 8px; font-size:11px;">
                                            <?php echo $abiertos > 0 ? 'Cerrar' : 'Abrir'; ?>
                                        </button>
                                    </form>
                                </td>
                            <?php endforeach; ?>
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