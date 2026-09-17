<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /centralizador_notas/index.php?error=session');
    exit;
}

require_once __DIR__ . '/../../model/CursosModel.php';

$cursosModel = new CursosModel();
$cursos = $cursosModel->getAll();
$cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
$materiaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$curso = $cursoId ? $cursosModel->getById($cursoId) : null;
$materias = $curso ? $cursosModel->getMateriasPorCurso($cursoId) : [];
$materiaValida = false;
foreach ($materias as $materia) {
    if ((int) $materia['id'] === $materiaId) {
        $materiaValida = true;
        break;
    }
}
if (!$materiaValida) $materiaId = 0;

function enlacePlanilla($ruta, array $params) {
    return $ruta . '?' . http_build_query(array_filter($params, static function ($valor) {
        return $valor !== null && $valor !== '' && (int) $valor !== 0;
    }));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planillas del Administrador</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <style>
        .planillas-admin { position: relative; z-index: 1; max-width: 1180px; margin: 70px auto 0; padding: 28px 20px; }
        .planillas-admin h1 { margin-bottom: 8px; }
        .planillas-admin-intro { margin-bottom: 22px; }
        .planillas-filtros { display: flex; flex-wrap: wrap; align-items: end; gap: 12px; padding: 16px; margin-bottom: 24px; background: rgba(255,255,255,.94); border-radius: 8px; box-shadow: 0 3px 12px rgba(0,0,0,.12); }
        .planillas-filtros label { display: block; margin-bottom: 5px; font-size: .82rem; font-weight: 700; color: #425466; }
        .planillas-filtros select { min-width: 230px; padding: 9px 10px; border: 1px solid #cbd2d9; border-radius: 5px; background: #fff; color: #243447; }
        .planillas-grid-admin { display: grid; grid-template-columns: repeat(auto-fit, minmax(245px, 1fr)); gap: 18px; }
        .planilla-admin-card { display: flex; flex-direction: column; justify-content: space-between; min-height: 190px; padding: 22px; background: rgba(255,255,255,.94); border-radius: 8px; box-shadow: 0 3px 12px rgba(0,0,0,.12); }
        .planilla-admin-card i { color: #16803c; font-size: 2rem; }
        .planilla-admin-card h2 { margin: 13px 0 8px; color: #243447; font-size: 1.1rem; }
        .planilla-admin-card p { color: #52606d; line-height: 1.45; }
        .planilla-admin-card a { display: inline-block; margin-top: 18px; padding: 10px 13px; border-radius: 5px; background: #16803c; color: #fff; text-decoration: none; text-align: center; }
        .planilla-admin-card a.disabled { background: #9aa4ad; pointer-events: none; }
        body.dark-mode .planillas-admin h1 { color: #fff; }
        body.dark-mode .planillas-admin-intro { color: rgba(255,255,255,.72); }
        body.dark-mode .planillas-filtros, body.dark-mode .planilla-admin-card { background: rgba(255,255,255,.08); box-shadow: 0 4px 20px rgba(0,0,0,.3); }
        body.dark-mode .planillas-filtros label, body.dark-mode .planilla-admin-card h2 { color: #fff; }
        body.dark-mode .planilla-admin-card p { color: rgba(255,255,255,.72); }
        body.dark-mode .planillas-filtros select { background: #17173f; border-color: rgba(255,255,255,.2); color: #fff; }
    </style>
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

    <main class="planillas-admin">
        <h1>Planillas institucionales</h1>
        <p class="planillas-admin-intro">Selecciona los datos de trabajo y usa las copias HTML editables dentro del sistema. Ninguna opcion abre Excel.</p>

        <form method="GET" class="planillas-filtros">
            <div>
                <label for="curso_id">Curso</label>
                <select name="curso_id" id="curso_id" onchange="this.form.submit()" required>
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $item): ?>
                        <option value="<?php echo (int) $item['id']; ?>" <?php echo $cursoId === (int) $item['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['nombre'] . ' - ' . $item['paralelo'] . ' (' . $item['gestion'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($curso): ?>
                <div>
                    <label for="materia_id">Materia</label>
                    <select name="materia_id" id="materia_id" onchange="this.form.submit()" required>
                        <option value="">Seleccionar materia</option>
                        <?php foreach ($materias as $item): ?>
                            <option value="<?php echo (int) $item['id']; ?>" <?php echo $materiaId === (int) $item['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item['nombre'] . ' (' . $item['codigo'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </form>

        <div class="planillas-grid-admin">
            <article class="planilla-admin-card">
                <div><i class="fas fa-table"></i><h2>REGISTRO pedagógico</h2><p>La planilla institucional que ya funciona como el registro del profesor.</p></div>
                <a class="<?php echo $materiaId ? '' : 'disabled'; ?>" href="<?php echo htmlspecialchars(enlacePlanilla('/centralizador_notas/view/docente/registro_pedagogico.php', ['curso_id' => $cursoId, 'materia_id' => $materiaId])); ?>">Entrar a la planilla</a>
            </article>
            <article class="planilla-admin-card">
                <div><i class="fas fa-list-check"></i><h2>SISTEMAS / entrega</h2><p>Copia HTML del formato institucional de entrega de calificaciones para el curso y materia.</p></div>
                <a class="<?php echo $materiaId ? '' : 'disabled'; ?>" href="<?php echo htmlspecialchars(enlacePlanilla('/centralizador_notas/view/admin/historial_academico.php', ['modo' => 'curso', 'curso_id' => $cursoId, 'materia_id' => $materiaId])); ?>">Entrar a la planilla</a>
            </article>
            <article class="planilla-admin-card">
                <div><i class="fas fa-layer-group"></i><h2>CENTRALIZADOR</h2><p>Resumen HTML de las notas del curso, con acceso directo al registro editable para corregir datos.</p></div>
                <a class="<?php echo $materiaId ? '' : 'disabled'; ?>" href="<?php echo htmlspecialchars(enlacePlanilla('/centralizador_notas/view/admin/centralizador.php', ['curso_id' => $cursoId, 'materia_id' => $materiaId])); ?>">Entrar a la planilla</a>
            </article>
        </div>
    </main>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
