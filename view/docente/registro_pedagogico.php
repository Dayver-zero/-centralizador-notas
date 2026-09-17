<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'] ?? '', ['docente', 'admin'], true)) {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';
require_once __DIR__ . '/../../model/AsistenciaModel.php';
require_once __DIR__ . '/../../model/RegistroConfigModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';

$estudiantesModel = new EstudiantesModel();
$cursosModel      = new CursosModel();
$notasModel       = new NotasModel();
$asistenciaModel  = new AsistenciaModel();
$parcialModel     = new ParcialPeriodoModel();

$cursoId   = isset($_GET['curso_id'])   ? (int) $_GET['curso_id']   : 0;
$materiaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$docenteId = (int) $_SESSION['referer_id'];
$esAdmin   = ($_SESSION['rol'] ?? '') === 'admin';

if (!$cursoId || !$materiaId) {
    // Sin curso/materia: el docente/rector elige antes de abrir la planilla
    $docModel = new DocentesModel();
    $selMateriasDoc = $docModel->getMaterias($docenteId);
    $selCursos = $cursosModel->getAll();
    $selCursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
    $selMateriasCurso = $selCursoId ? $cursosModel->getMateriasPorCurso($selCursoId) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Curso y Materia</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <style>
        .sel-wrap { position: relative; z-index: 1; max-width: 920px; margin: 80px auto 0; padding: 20px; }
        .sel-card { background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 4px 18px rgba(0,0,0,.15); }
        .sel-card h1 { margin: 0 0 6px; font-size: 1.25rem; }
        .sel-card .sel-intro { color: #52606d; margin: 0 0 18px; }
        .sel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 10px; }
        .sel-item { display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; text-decoration: none; color: inherit; transition: box-shadow .15s, transform .15s; }
        .sel-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,.14); transform: translateY(-1px); }
        .sel-item .t { font-weight: 600; }
        .sel-item .s { font-size: .8rem; color: #64748b; }
        .sel-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .sel-form label { display: block; margin-bottom: 5px; font-size: .82rem; font-weight: 700; color: #425466; }
        .sel-form select { padding: 9px 10px; border: 1px solid #cbd2d9; border-radius: 5px; min-width: 230px; background: #fff; color: #243447; }
        .sel-form .btn { margin-top: 2px; }
        body.dark-mode .sel-card { background: #17173f; color: #fff; }
        body.dark-mode .sel-card .sel-intro { color: rgba(255,255,255,.72); }
        body.dark-mode .sel-item { border-color: rgba(255,255,255,.2); }
        body.dark-mode .sel-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,.5); }
        body.dark-mode .sel-item .s { color: rgba(255,255,255,.6); }
        body.dark-mode .sel-form label { color: #fff; }
        body.dark-mode .sel-form select { background: #17173f; border-color: rgba(255,255,255,.2); color: #fff; }
    </style>
</head>
<body>
    <canvas id="canvas"></canvas>
    <?php include __DIR__ . '/../../includes/' . ($esAdmin ? 'menu_admin.php' : 'menu_docente.php'); ?>
    <div class="top-header">
        <div class="logo-area">
            <button id="sidebar-toggle" type="button" title="Desplegar o contraer el menu" aria-label="Desplegar o contraer el menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <img src="/centralizador_notas/view/img/escudo.jpg" alt="Logo"><span>Instituto Tecnologico PACCIOLI</span>
        </div>
        <div class="user-area">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username']); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>

    <main class="sel-wrap">
        <div class="sel-card">
            <h1>Selecciona tu planilla</h1>
            <p class="sel-intro">Elige el curso y la materia para abrir el Registro Pedagógico.</p>
            <?php if ($esAdmin): ?>
                <form method="GET" class="sel-form">
                    <div>
                        <label for="sel_curso">Curso</label>
                        <select name="curso_id" id="sel_curso" onchange="this.form.submit()" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($selCursos as $sc): ?>
                            <option value="<?php echo (int) $sc['id']; ?>" <?php echo $selCursoId === (int) $sc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sc['nombre'] . ' - ' . $sc['paralelo'] . ' (' . (int) $sc['gestion'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selMateriasCurso): ?>
                    <div>
                        <label for="sel_materia">Materia</label>
                        <select name="materia_id" id="sel_materia" onchange="this.form.submit()" required>
                            <option value="">-- Seleccionar materia --</option>
                            <?php foreach ($selMateriasCurso as $sm): ?>
                            <option value="<?php echo (int) $sm['id']; ?>">
                                <?php echo htmlspecialchars($sm['nombre'] . ' (' . $sm['codigo'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <?php if ($selMateriasDoc): ?>
                <div class="sel-grid">
                    <?php foreach ($selMateriasDoc as $sm):
                        $href = '/centralizador_notas/view/docente/registro_pedagogico.php?curso_id=' . (int) $sm['curso_id'] . '&materia_id=' . (int) $sm['materia_id'];
                    ?>
                    <a class="sel-item" href="<?php echo $href; ?>">
                        <div>
                            <div class="t"><?php echo htmlspecialchars($sm['materia']); ?></div>
                            <div class="s"><?php echo htmlspecialchars($sm['curso']); ?> - Gestión <?php echo (int) $sm['gestion']; ?> (<?php echo htmlspecialchars($sm['codigo']); ?>)</div>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="sin-datos">No tienes materias asignadas.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
<?php
    exit;
}

if (!$esAdmin && !$cursosModel->esDocenteAsignado($docenteId, $materiaId, $cursoId)) {
    header("Location: /centralizador_notas/view/docente/dashboard.php?error=forbidden");
    exit;
}

$curso      = $cursosModel->getById($cursoId);
$estudiantes = $estudiantesModel->getByCurso($cursoId);
$gestion    = (int) ($curso['gestion'] ?? date('Y'));

$carreraTipo    = $curso['carrera_tipo'] ?? 'anual';
$opcionesParcial = ParcialPeriodoModel::opciones($carreraTipo);
$estadosParcial  = $parcialModel->getEstados($cursoId, $materiaId, $gestion, $carreraTipo);
$parcialActivo   = $parcialModel->parcialActivo($cursoId, $materiaId, $gestion, $carreraTipo, $estadosParcial);
$etiquetaActivo  = $parcialActivo !== null ? ParcialPeriodoModel::etiqueta($parcialActivo) : '';
$periodoDefault  = $carreraTipo === 'semestral'
    ? semestreTexto($curso['semestre'] ?? 1)
    : ParcialPeriodoModel::añoTexto($curso['anio'] ?? 1);

// Configuracion editable de la hoja (registro_config)
$regConfigModel = new RegistroConfigModel();
$regCfg = $regConfigModel->getConfig($cursoId, $materiaId, $gestion);
$regCfg = $regCfg ?: [];

$regCarrera   = $regCfg['carrera']   ?? '';
$regAsignatura= $regCfg['asignatura'] ?? '';
$regCodigo    = $regCfg['codigo']    ?? '';
$regAnio      = $regCfg['anio']      ?? '';
$regPeriodo   = $regCfg['periodo']   ?? '';
$regDocente   = $regCfg['docente']   ?? '';
$regParalelo  = $regCfg['paralelo']  ?? '';
$regObserv    = $regCfg['observacion'] ?? '';
$regLogo      = $regCfg['logo']      ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'asistencia') {
        $asistenciaModel->registrarAsistencia(
            (int) $_POST['estudiante_id'], $cursoId, $materiaId,
            $_POST['fecha'], strtolower($_POST['estado']),
            $gestion
        );
        header("Location: /centralizador_notas/view/docente/registro_pedagogico.php?curso_id=$cursoId&materia_id=$materiaId&msg=asistencia");
        exit;
    } elseif ($accion === 'nota') {
        $actividad = $_POST['nombre_actividad'] ?? '';
        if (($_POST['tipo'] ?? '') === 'parcial') {
            if (!in_array($actividad, $opcionesParcial, true) || !$parcialModel->esAbierto($cursoId, $materiaId, $gestion, $actividad)) {
                header("Location: /centralizador_notas/view/docente/registro_pedagogico.php?curso_id=$cursoId&materia_id=$materiaId&msg=parcial_cerrado");
                exit;
            }
        }
        $notasModel->guardarNota(
            (int) $_POST['estudiante_id'], $cursoId, $materiaId,
            $_POST['tipo'], $actividad,
            (float) $_POST['nota'], $gestion
        );
        header("Location: /centralizador_notas/view/docente/registro_pedagogico.php?curso_id=$cursoId&materia_id=$materiaId&msg=nota");
        exit;
    }
}

$nombreDocente = $_SESSION['nombre_completo'] ?? ($esAdmin ? 'ADMINISTRADOR' : 'DOCENTE NO IDENTIFICADO');

$materia = null;
if ($materiaId) {
    $stmt = $conn->prepare("SELECT * FROM materias WHERE id = ?");
    $stmt->bind_param("i", $materiaId);
    $stmt->execute();
    $materia = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// 12 fechas de asistencia (si existen en BD; si no, placeholders F1..F12)
$fechasAsistencia = [];
$fechaStmt = $conn->prepare("SELECT DISTINCT fecha FROM asistencia WHERE curso_id = ? AND materia_id = ? AND gestion = ? ORDER BY fecha");
$fechaStmt->bind_param("iii", $cursoId, $materiaId, $gestion);
$fechaStmt->execute();
$fechaRes = $fechaStmt->get_result();
while ($fr = $fechaRes->fetch_assoc()) {
    $fechasAsistencia[] = $fr['fecha'];
}
$fechaStmt->close();
while (count($fechasAsistencia) < 12) {
    $fechasAsistencia[] = '';
}
$fechasAsistencia = array_slice($fechasAsistencia, 0, 12);

function getNota($notas, $tipo, $nombreActividad) {
    foreach ($notas as $n) {
        if (($n['tipo'] ?? '') === $tipo && ($n['nombre_actividad'] ?? '') === $nombreActividad) {
            return (float) $n['nota'];
        }
    }
    return null;
}

function fmtNota($v) {
    if ($v === null || $v === '') return '';
    return rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
}

function notaCelda($notas, $tipo, $nombre) {
    foreach ($notas as $n) {
        if (($n['tipo'] ?? '') === $tipo && ($n['nombre_actividad'] ?? '') === $nombre) {
            return fmtNota($n['nota']);
        }
    }
    return '';
}

function semestreTexto($n) {
    $map = [1 => 'PRIMER SEMESTRE', 2 => 'SEGUNDO SEMESTRE', 3 => 'TERCER SEMESTRE',
            4 => 'CUARTO SEMESTRE', 5 => 'QUINTO SEMESTRE', 6 => 'SEXTO SEMESTRE'];
    return $map[(int) $n] ?? 'PRIMER SEMESTRE';
}

function filaVacia($nro = '') {
?>
    <tr class="fila-vacia" data-nro="<?php echo $nro; ?>">
        <td class="reg-nro"><?php echo $nro; ?></td>
        <td colspan="2"></td>
        <td></td>
        <td></td><td></td>
        <?php for ($i = 0; $i < 1; $i++): ?><td></td><?php endfor; ?>
        <?php for ($i = 0; $i < 1; $i++): ?><td></td><?php endfor; ?>
        <?php for ($i = 0; $i < 2; $i++): ?><td></td><?php endfor; ?>
        <?php for ($i = 0; $i < 3; $i++): ?><td></td><?php endfor; ?>
    </tr>
<?php
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Pedagógico</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_pedagogico.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script defer src="/centralizador_notas/js/script_registro.js"></script>
    <script>window.APP_CSRF = '<?php echo csrf_token(); ?>'; window.APP_ACTIVO = '<?php echo htmlspecialchars(addslashes($parcialActivo ?? ''), ENT_QUOTES); ?>';</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</head>
<body>
    <canvas id="canvas"></canvas>
    <?php include __DIR__ . ($esAdmin ? '/../../includes/menu_admin.php' : '/../../includes/menu_docente.php'); ?>

    <div class="top-header">
        <div class="logo-area">
            <button id="sidebar-toggle" type="button" title="Desplegar o contraer el menu" aria-label="Desplegar o contraer el menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <img src="/centralizador_notas/view/img/escudo.jpg" alt="Logo"><span>Instituto Tecnologico PACCIOLI</span>
        </div>
        <div class="user-area">
            <span>Bienvenido, <?php echo htmlspecialchars($nombreDocente); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>

    <div class="reg-container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success" style="max-width:600px; margin:0 auto 15px;">
                <?php
                $msg = $_GET['msg'];
                if ($msg === 'asistencia') echo 'Asistencia registrada.';
                elseif ($msg === 'parcial_cerrado') echo '<div class="alert alert-error" style="display:inline-block;">Este parcial esta cerrado o no esta habilitado.</div>';
                else echo 'Nota guardada.';
                ?>
            </div>
        <?php endif; ?>

        <div class="reg-btns">
            <span id="reg-parcial-badge" style="display:inline-block; padding:4px 10px; border-radius:4px; font-size:12px; color:#fff; vertical-align:middle; margin-left:4px; background:<?php echo $parcialActivo !== null ? '#28a745' : '#6c757d'; ?>;">
                <?php echo $parcialActivo !== null ? htmlspecialchars($etiquetaActivo) . ' — ABIERTO' : 'Sin parcial abierto'; ?>
            </span>
            <button class="btn btn-primary" type="button" id="reg-enviar-btn" onclick="regParciales.enviar()"<?php echo $parcialActivo === null ? ' disabled' : ''; ?>><i class="fas fa-paper-plane"></i> Enviar parcial</button>
            <span class="reg-col-ctrl">
                <select id="col-bloque" title="Bloque para añadir/quitar columna">
                    <option value="conocer">CONOCER</option>
                    <option value="hacer">HACER</option>
                    <option value="ser">SER</option>
                    <option value="asistencia">ASISTENCIA</option>
                </select>
                <button class="btn btn-primary" type="button" onclick="regColumnas.add()"><i class="fas fa-plus"></i> Añadir columna</button>
                <button class="btn btn-secondary" type="button" onclick="regColumnas.remove()"><i class="fas fa-minus"></i> Quitar</button>
            </span>
            <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
            <button class="btn btn-success" type="button" onclick="regConfig.aplicar()"><i class="fas fa-save"></i> Aplicar cambios</button>
            <button class="btn btn-secondary" type="button" onclick="regConfig.descartar()"><i class="fas fa-undo"></i> Descartar</button>
            <a href="<?php echo $esAdmin ? '/centralizador_notas/view/admin/planillas_admin.php' : '/centralizador_notas/view/docente/dashboard.php'; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>

        <?php if ($curso && $materia): ?>
        <div class="reg-sheet">
            <!-- Título (3 líneas, calcado de REGISTRO.xlsx A1:AT3) -->
            <div class="reg-tit">INSTITUTO TECNOLÓGICO</div>
            <div class="reg-tit2">"PACCIOLI"</div>
            <div class="reg-tit3">REGISTRO PEDAGÓGICO <?php echo $gestion; ?></div>

            <!-- Tarjeta cabecera (3 filas, calcada de A5:AT7) -->
            <table class="reg-card">
                <tr>
                    <td rowspan="3" class="reg-card-logo">
                        <?php if ($regLogo): ?>
                            <img src="data:image/png;base64,<?php echo base64_encode($regLogo); ?>" alt="Escudo"
                                 class="editable-logo" title="Clic para cambiar la imagen">
                        <?php else: ?>
                            <img src="/centralizador_notas/view/img/escudo.jpg" alt="Escudo"
                                 class="editable-logo" title="Clic para cambiar la imagen">
                        <?php endif; ?>
                    </td>
                    <td rowspan="3" class="reg-card-rot"><span>CARRERA:</span></td>
                    <td rowspan="3" class="reg-card-big editable" data-campo="carrera"
                        title="Clic para editar"><?php echo htmlspecialchars($regCarrera !== '' ? $regCarrera : ($curso['carrera_nombre'] ?? '')); ?></td>
                    <td class="reg-card-lab">ASIGNATURA:</td>
                    <td class="reg-card-val editable" data-campo="asignatura"
                        title="Clic para editar"><?php echo htmlspecialchars($regAsignatura !== '' ? $regAsignatura : ($materia['nombre'] ?? '')); ?></td>
                    <td class="reg-card-lab">CODIGO:</td>
                    <td class="reg-card-val editable" data-campo="codigo"
                        title="Clic para editar"><?php echo htmlspecialchars($regCodigo !== '' ? $regCodigo : ($materia['codigo'] ?? '')); ?></td>
                    <td rowspan="3" class="reg-card-obs editable" data-campo="observacion"
                        title="Clic para editar"><?php echo htmlspecialchars($regObserv); ?></td>
                </tr>
                <tr>
                    <td class="reg-card-lab">AÑO:</td>
                    <td class="reg-card-val editable" data-campo="anio"
                        title="Clic para editar"><?php echo htmlspecialchars($regAnio !== '' ? $regAnio : (string) $gestion); ?></td>
                    <td class="reg-card-lab">PERIODO:</td>
                    <td class="reg-card-val editable" data-campo="periodo"
                        title="Clic para editar"><?php echo htmlspecialchars($regPeriodo !== '' ? $regPeriodo : $periodoDefault); ?></td>
                </tr>
                <tr>
                    <td class="reg-card-lab">DOCENTE:</td>
                    <td class="reg-card-val editable" data-campo="docente"
                        title="Clic para editar"><?php echo htmlspecialchars($regDocente !== '' ? $regDocente : $nombreDocente); ?></td>
                    <td class="reg-card-lab">PARALELO:</td>
                    <td class="reg-card-val editable" data-campo="paralelo"
                        title="Clic para editar"><?php echo htmlspecialchars($regParalelo !== '' ? $regParalelo : ($curso['paralelo'] ?? '')); ?></td>
                </tr>
            </table>
            <input type="file" id="logo-input" accept="image/png,image/jpeg,image/gif,image/webp" hidden>

            <!-- Tabla principal (46 columnas, cabecera de 4 filas calcada de A8:AT11) -->
            <div class="reg-scroll">
                <table class="reg-tabla">
                    <thead>
                        <tr class="reg-h1">
                            <th rowspan="4" class="reg-nro">Nro</th>
                            <th colspan="2" rowspan="4" class="reg-nombre">APELLIDOS Y NOMBRES</th>
                            <th colspan="1" class="reg-asistencia-tit">ASISTENCIA DE ESTUDIANTES</th>
                            <th rowspan="4" class="reg-asist">ASISTENCIA</th>
                            <th rowspan="4" class="reg-pct">PORCENTAJE DE ASISTENCIA</th>
                            <th colspan="1" class="reg-teoria-tit">TEORÍA (30%)</th>
                            <th colspan="3" class="reg-practica-tit">PRÁCTICA (70%)</th>
                            <th rowspan="3" class="reg-suma-tit">TEORIA</th>
                            <th rowspan="3" class="reg-suma-tit">PRACTICA</th>
                            <th rowspan="3" class="reg-suma-tit"><?php echo htmlspecialchars($etiquetaActivo !== '' ? $etiquetaActivo : 'PARCIAL'); ?></th>
                        </tr>
                        <tr class="reg-h2">
                            <th colspan="1" class="reg-cuarto">CUARTO PARCIAL</th>
                            <th colspan="1" class="reg-conocer-tit">CONOCER (30 %)</th>
                            <th colspan="1" class="reg-hacer-tit">HACER (60%)</th>
                            <th colspan="2" class="reg-ser-tit">SER (10%)</th>
                        </tr>
                        <tr class="reg-h3">
                            <th rowspan="2" class="reg-fecha"
                                    title="<?php echo $fechasAsistencia[0] ? htmlspecialchars($fechasAsistencia[0]) : 'F1'; ?>"
                                    colspan="1"><?php echo $fechasAsistencia[0] ? date('d/m/Y', strtotime($fechasAsistencia[0])) : 'F1'; ?></th>
                            <th colspan="1" class="reg-eval">(30 PTOS) EVALUACION TEORICA</th>
                            <th colspan="1" class="reg-proy">(60 PTOS) ENTREGA DE PROYECTO FINAL</th>
                            <th colspan="1" class="reg-ser-vert" data-nombre="PUNTUALIDAD Y/O DESEMPENO" title="Clic para editar el nombre">PUNTUALIDAD Y/O DESEMPENO</th>
<th colspan="1" class="reg-ser-vert" data-nombre="PUNTUALIDAD EN LA ENTREGA" title="Clic para editar el nombre">PUNTUALIDAD EN LA ENTREGA</th>
                        </tr>
                        <tr class="reg-h4">
<th class="reg-num reg-num-conocer" data-nombre="EVALUACION" data-pts="30" title="Clic para editar el nombre">EVALUACION</th>
<th class="reg-num reg-num-hacer" data-nombre="PRACTICA" data-pts="60" title="Clic para editar el nombre">PRACTICA</th>
<th class="reg-num reg-num-ser" data-nombre="PUNTUALIDAD Y/O DESEMPENO" data-pts="10" title="Clic para editar el nombre"></th>
<th class="reg-num reg-num-ser" data-nombre="PUNTUALIDAD EN LA ENTREGA" data-pts="10" title="Clic para editar el nombre"></th>
                            <th class="reg-suma-val">30</th>
                            <th class="reg-suma-val">70</th>
                            <th class="reg-suma-val">100</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $nro = 1;
                            foreach ($estudiantes as $est):
                                $estId = (int) $est['id'];
                                $allNotas = $notasModel->getNotasEstudiante($estId, $cursoId, $materiaId);
                                $asistencia = $asistenciaModel->getResumenAsistencia($estId, $cursoId, $materiaId, $gestion);
                                $fechasEst = $asistenciaModel->getAsistenciaEstudiante($estId, $cursoId, $materiaId, $gestion);
                                $fechasMap = [];
                                foreach ($fechasEst as $fe) { $fechasMap[$fe['fecha']] = $fe['estado']; }

                                $conocerSum = 0; $hacerSum = 0; $serSum = 0;
                                $tieneConocer1 = false;
                                $tieneHacer1 = false;
                                foreach ($allNotas as $n) {
                                    if (($n['tipo'] ?? '') === 'conocer') {
                                        $conocerSum += (float) $n['nota'];
                                        if (($n['nombre_actividad'] ?? '') === 'Conocer 1') $tieneConocer1 = true;
                                    } elseif (($n['tipo'] ?? '') === 'hacer') {
                                        $hacerSum += (float) $n['nota'];
                                        if (($n['nombre_actividad'] ?? '') === 'Hacer 1') $tieneHacer1 = true;
                                    } elseif (($n['tipo'] ?? '') === 'ser')    $serSum    += (float) $n['nota'];
                                }
                                $parcialDirecto = getNota($allNotas, 'parcial', $parcialActivo !== null ? $parcialActivo : 'Parcial');
                                if ($parcialDirecto !== null) {
                                    $teoria  = round($parcialDirecto * 0.30, 1);
                                    $practica = round($parcialDirecto * 0.70, 1);
                                    $parcial = $parcialDirecto;
                                } else {
                                    $teoria  = $tieneConocer1 ? round($conocerSum, 0) : null;
                                    $practica = $tieneHacer1 ? round($hacerSum + $serSum, 0) : null;
                                    $parcial = ($teoria !== null && $practica !== null) ? round($teoria + $practica, 0) : null;
                                }

                                $valConocer1 = notaCelda($allNotas, 'conocer', 'Conocer 1');
                                $valHacer1   = notaCelda($allNotas, 'hacer', 'Hacer 1');
                                $valSer1     = notaCelda($allNotas, 'ser', 'Ser 1');
                                $valSer2     = notaCelda($allNotas, 'ser', 'Ser 2');
                                $conocerRojos = $conocerSum > 30;
                                $hacerRojos   = $hacerSum > 60;
                                $serRojos     = $serSum > 10;
                            ?>
                            <tr data-estudiante="<?php echo $estId; ?>">
                                <td class="reg-nro"><?php echo $nro++; ?></td>
                                <td colspan="2" class="reg-nombre"><?php echo htmlspecialchars($est['nombre_completo']); ?></td>
                                <?php $fecha = $fechasAsistencia[0];
                                    $estado = $fecha ? ($fechasMap[$fecha] ?? '') : ''; ?>
                                    <td class="reg-asist-cel <?php echo $estado ? 'asist-' . strtolower($estado) : ''; ?>"
                                        tabindex="0"
                                        data-tipo="asistencia"
                                        data-fecha="<?php echo htmlspecialchars($fecha); ?>"
                                        data-est="<?php echo (int) $estId; ?>"
                                        title="<?php echo $fecha ? htmlspecialchars($fecha) : ''; ?>"
                                        onclick="regCelda.onClick(this)">
                                        <?php echo $estado ? strtoupper(substr($estado, 0, 1)) : ''; ?>
                                    </td>
                                <td class="reg-asist"><?php echo (int) $asistencia['total']; ?></td>
                                <td class="reg-pct"><?php echo $asistencia['porcentaje']; ?>%</td>
                                <td class="reg-conocer-cel<?php echo $conocerRojos && $valConocer1 !== '' ? ' reg-limit' : ''; ?>"
                                    tabindex="0"
                                    data-tipo="conocer"
                                    data-actividad="Conocer 1"
                                    data-est="<?php echo (int) $estId; ?>"
                                    onclick="regCelda.onClick(this)">
                                    <?php echo $valConocer1; ?>
                                </td>
                                <td class="reg-hacer-cel<?php echo $hacerRojos && $valHacer1 !== '' ? ' reg-limit' : ''; ?>"
                                    tabindex="0"
                                    data-tipo="hacer"
                                    data-actividad="Hacer 1"
                                    data-est="<?php echo (int) $estId; ?>"
                                    onclick="regCelda.onClick(this)">
                                    <?php echo $valHacer1; ?>
                                </td>
                                <td class="reg-ser-cel<?php echo $serRojos && $valSer1 !== '' ? ' reg-limit' : ''; ?>"
                                    tabindex="0"
                                    data-tipo="ser"
                                    data-actividad="Ser 1"
                                    data-est="<?php echo (int) $estId; ?>"
                                    onclick="regCelda.onClick(this)">
                                    <?php echo $valSer1; ?>
                                </td>
                                <td class="reg-ser-cel<?php echo $serRojos && $valSer2 !== '' ? ' reg-limit' : ''; ?>"
                                    tabindex="0"
                                    data-tipo="ser"
                                    data-actividad="Ser 2"
                                    data-est="<?php echo (int) $estId; ?>"
                                    onclick="regCelda.onClick(this)">
                                    <?php echo $valSer2; ?>
                                </td>
                                <td class="reg-suma-val<?php echo $teoria !== null && $conocerRojos ? ' reg-limit' : ''; ?>"><?php echo fmtNota($teoria); ?></td>
                                <td class="reg-suma-val<?php echo $practica !== null && ($hacerRojos || $serRojos) ? ' reg-limit' : ''; ?>"><?php echo fmtNota($practica); ?></td>
                                <td class="reg-suma-val reg-parcial"
                                    tabindex="0"
                                    data-tipo="parcial"
                                    data-actividad="<?php echo htmlspecialchars($parcialActivo ?? ''); ?>"
                                    data-est="<?php echo (int) $estId; ?>"
                                    onclick="regCelda.onClick(this)"<?php echo $parcialActivo === null ? ' title="No hay parcial abierto"' : ''; ?>>
                                    <?php echo fmtNota($parcial); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php for ($i = count($estudiantes); $i < 20; $i++): ?>
                                <?php filaVacia($nro++); ?>
                            <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- INDICADORES (calcado de A33:AT42) -->
            <div class="reg-indicadores">
                <div class="reg-ind-col">
                    <div class="reg-ind-tit">INDICADORES DE EVALUACION TEORICA (CONOCER)</div>
                    <ol>
                        <li>Dominan los principios fundamentales de la asignatura.</li>
                        <li>Comprenden los conceptos y su aplicación práctica.</li>
                        <li>Identifican las herramientas y técnicas correspondientes.</li>
                        <li>Argumentan y fundamentan sus respuestas con claridad.</li>
                        <li>Relacionan los contenidos con situaciones reales.</li>
                        <li>Analizan casos y resuelven problemas teóricos.</li>
                        <li>Demuestran dominio de los contenidos programáticos.</li>
                    </ol>
                </div>
                <div class="reg-ind-col">
                    <div class="reg-ind-tit">INDICADORES DE EVALUACION PRACTICA (HACER)</div>
                    <ol>
                        <li>Solucionan los problemas en entornos prácticos.</li>
                        <li>Elaboran proyectos y trabajos con calidad técnica.</li>
                        <li>Utilizan correctamente las herramientas de la asignatura.</li>
                        <li>Desarrollan las actividades prácticas asignadas.</li>
                        <li>Aplican los conocimientos en situaciones concretas.</li>
                        <li>Entregan el proyecto final cumpliendo los requisitos.</li>
                        <li>Demuestran creatividad e innovación en la solución.</li>
                        <li>Participan y colaboran activamente en equipo.</li>
                    </ol>
                    <div class="reg-ind-tit reg-ind-ser">INDICADORES DE EVALUACION ACTITUDINAL - SER (VALORES)</div>
                    <ol>
                        <li>Nivelación: demuestran actitud positiva y superación.</li>
                        <li>Puntualidad y/o desempeño: cumplen horarios y muestran compromiso.</li>
                        <li>Puntualidad en la entrega: cumplen los plazos establecidos.</li>
                    </ol>
                </div>
            </div>

            <!-- FOOTER (calcado de la fila 46) -->
            <div class="reg-footer">
                <div class="reg-footer-item"><span class="reg-borde-bajo">FECHA  DE ENTREGA: _______________________________</span></div>
                <div class="reg-footer-item"><span class="reg-borde-bajo">NOMBRE DEL DOCENTE: <?php echo htmlspecialchars($nombreDocente); ?></span></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>