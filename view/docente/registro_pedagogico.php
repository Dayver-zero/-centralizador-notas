<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';
require_once __DIR__ . '/../../model/AsistenciaModel.php';
require_once __DIR__ . '/../../model/RegistroConfigModel.php';

$estudiantesModel = new EstudiantesModel();
$cursosModel      = new CursosModel();
$notasModel       = new NotasModel();
$asistenciaModel  = new AsistenciaModel();

$cursoId   = isset($_GET['curso_id'])   ? (int) $_GET['curso_id']   : 0;
$materiaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$docenteId = (int) $_SESSION['referer_id'];

if (!$cursoId || !$materiaId || !$cursosModel->esDocenteAsignado($docenteId, $materiaId, $cursoId)) {
    header("Location: /centralizador_notas/view/docente/dashboard.php?error=forbidden");
    exit;
}

$curso      = $cursosModel->getById($cursoId);
$estudiantes = $estudiantesModel->getByCurso($cursoId);
$gestion    = (int) ($curso['gestion'] ?? date('Y'));

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
        $notasModel->guardarNota(
            (int) $_POST['estudiante_id'], $cursoId, $materiaId,
            $_POST['tipo'], $_POST['nombre_actividad'],
            (float) $_POST['nota'], $gestion
        );
        header("Location: /centralizador_notas/view/docente/registro_pedagogico.php?curso_id=$cursoId&materia_id=$materiaId&msg=nota");
        exit;
    }
}

$nombreDocente = $_SESSION['nombre_completo'] ?? 'DOCENTE NO IDENTIFICADO';

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

function notaCelda($notas, $tipo, $nombre, $fallback) {
    foreach ($notas as $n) {
        if (($n['tipo'] ?? '') === $tipo && ($n['nombre_actividad'] ?? '') === $nombre) {
            return number_format((float) $n['nota'], 1);
        }
    }
    return $fallback > 0 ? number_format($fallback, 1) : '';
}

function semestreTexto($n) {
    $map = [1 => 'PRIMER SEMESTRE', 2 => 'SEGUNDO SEMESTRE', 3 => 'TERCER SEMESTRE',
            4 => 'CUARTO SEMESTRE', 5 => 'QUINTO SEMESTRE', 6 => 'SEXTO SEMESTRE'];
    return $map[(int) $n] ?? 'PRIMER SEMESTRE';
}

function filaVacia() {
?>
    <tr class="fila-vacia">
        <td></td>
        <td colspan="2"></td>
        <td></td>
        <td></td><td></td>
        <?php for ($i = 0; $i < 1; $i++): ?><td></td><?php endfor; ?>
        <?php for ($i = 0; $i < 1; $i++): ?><td></td><?php endfor; ?>
        <?php for ($i = 0; $i < 1; $i++): ?><td></td><?php endfor; ?>
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
    <script>window.APP_CSRF = '<?php echo csrf_token(); ?>';</script>
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
            <span>Bienvenido, <?php echo htmlspecialchars($nombreDocente); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>

    <div class="reg-container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success" style="max-width:600px; margin:0 auto 15px;">
                <?php echo $_GET['msg'] === 'asistencia' ? 'Asistencia registrada.' : 'Nota guardada.'; ?>
            </div>
        <?php endif; ?>

        <div class="reg-btns">
            <button class="btn btn-primary" onclick="abrirModal('modalAsistencia')"><i class="fas fa-calendar-check"></i> Registrar Asistencia</button>
            <button class="btn btn-success" onclick="abrirModal('modalParcial')"><i class="fas fa-star"></i> Registrar Parcial</button>
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
            <button class="btn btn-primary" type="button" onclick="abrirModal('modalEstudiante')"><i class="fas fa-user-plus"></i> Añadir alumno</button>
            <button class="btn btn-danger" type="button" onclick="regEstudiantes.quitarVacios()"><i class="fas fa-user-minus"></i> Quitar vacíos</button>
            <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
            <button class="btn btn-success" type="button" onclick="regConfig.aplicar()"><i class="fas fa-save"></i> Aplicar cambios</button>
            <button class="btn btn-secondary" type="button" onclick="regConfig.descartar()"><i class="fas fa-undo"></i> Descartar</button>
            <a href="/centralizador_notas/view/docente/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
                        title="Clic para editar"><?php echo htmlspecialchars($regPeriodo !== '' ? $regPeriodo : semestreTexto($curso['semestre'] ?? 1)); ?></td>
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
                            <th colspan="2" class="reg-practica-tit">PRÁCTICA (70%)</th>
                            <th rowspan="3" class="reg-suma-tit">TEORIA</th>
                            <th rowspan="3" class="reg-suma-tit">PRACTICA</th>
                            <th rowspan="3" class="reg-suma-tit">PRIMER PARCIAL</th>
                        </tr>
                        <tr class="reg-h2">
                            <th colspan="1" class="reg-cuarto">CUARTO PARCIAL</th>
                            <th colspan="1" class="reg-conocer-tit">CONOCER (30 %)</th>
                            <th colspan="1" class="reg-hacer-tit">HACER (60%)</th>
                            <th colspan="1" class="reg-ser-tit">SER (10%)</th>
                        </tr>
                        <tr class="reg-h3">
                            <th rowspan="2" class="reg-fecha"
                                    title="<?php echo $fechasAsistencia[0] ? htmlspecialchars($fechasAsistencia[0]) : 'F1'; ?>"
                                    colspan="1"><?php echo $fechasAsistencia[0] ? date('d/m', strtotime($fechasAsistencia[0])) : 'F1'; ?></th>
                            <th colspan="1" class="reg-eval">(30 PTOS) EVALUACION TEORICA</th>
                            <th colspan="1" class="reg-proy">(60 PTOS) ENTREGA DE PROYECTO FINAL</th>
                            <th class="reg-ser-vert">SER</th>
                        </tr>
                        <tr class="reg-h4">
                            <th class="reg-num reg-num-conocer">1</th>
                            <th class="reg-num reg-num-hacer">1</th>
                            <th class="reg-num reg-num-ser">1</th>
                            <th class="reg-suma-val">30</th>
                            <th class="reg-suma-val">70</th>
                            <th class="reg-suma-val">100</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($estudiantes)): ?>
                            <tr><td colspan="34" class="reg-empty">No hay estudiantes inscritos en este curso.</td></tr>
                        <?php else: ?>
                            <?php $nro = 1;
                            foreach ($estudiantes as $est):
                                $estId = (int) $est['id'];
                                $allNotas = $notasModel->getNotasEstudiante($estId, $cursoId, $materiaId);
                                $asistencia = $asistenciaModel->getResumenAsistencia($estId, $cursoId, $materiaId, $gestion);
                                $fechasEst = $asistenciaModel->getAsistenciaEstudiante($estId, $cursoId, $materiaId, $gestion);
                                $fechasMap = [];
                                foreach ($fechasEst as $fe) { $fechasMap[$fe['fecha']] = $fe['estado']; }

                                $conocerSum = 0; $hacerSum = 0; $serSum = 0;
                                foreach ($allNotas as $n) {
                                    if (($n['tipo'] ?? '') === 'conocer') $conocerSum += (float) $n['nota'];
                                    elseif (($n['tipo'] ?? '') === 'hacer') $hacerSum += (float) $n['nota'];
                                    elseif (($n['tipo'] ?? '') === 'ser')    $serSum    += (float) $n['nota'];
                                }
                                $parcialDirecto = getNota($allNotas, 'parcial', 'Parcial');
                                if ($parcialDirecto !== null) {
                                    $teoria  = round($parcialDirecto * 0.30, 1);
                                    $practica = round($parcialDirecto * 0.70, 1);
                                    $parcial = $parcialDirecto;
                                } else {
                                    $teoria  = $conocerSum > 0 ? round($conocerSum, 1) : null;
                                    $practica = $hacerSum > 0 ? round($hacerSum * 0.7 + $serSum * 0.1, 1) : null;
                                    $parcial = ($teoria !== null && $practica !== null) ? round($teoria + $practica, 1) : null;
                                }
                            ?>
                            <tr data-estudiante="<?php echo $estId; ?>">
                                <td class="reg-nro"><?php echo $nro++; ?></td>
                                <td colspan="2" class="reg-nombre"><?php echo htmlspecialchars($est['nombre_completo']); ?></td>
                                <?php $fecha = $fechasAsistencia[0];
                                    $estado = $fecha ? ($fechasMap[$fecha] ?? '') : ''; ?>
                                    <td class="reg-asist-cel <?php echo $estado ? 'asist-' . strtolower($estado) : ''; ?>"
                                        title="<?php echo $fecha ? htmlspecialchars($fecha) : ''; ?>"
                                        onclick="abrirModalAsistencia(<?php echo $estId; ?>, '<?php echo htmlspecialchars($fecha); ?>')">
                                        <?php echo $estado ? strtoupper(substr($estado, 0, 1)) : ''; ?>
                                    </td>
                                <td class="reg-asist"><?php echo (int) $asistencia['total']; ?></td>
                                <td class="reg-pct"><?php echo $asistencia['porcentaje']; ?>%</td>
                                <td class="reg-conocer-cel"
                                    onclick="abrirModalNota(<?php echo $estId; ?>, 'conocer', 'Conocer 1')">
                                    <?php echo notaCelda($allNotas, 'conocer', 'Conocer 1', $conocerSum); ?>
                                </td>
                                <td class="reg-hacer-cel"
                                    onclick="abrirModalNota(<?php echo $estId; ?>, 'hacer', 'Hacer 1')">
                                    <?php echo notaCelda($allNotas, 'hacer', 'Hacer 1', $hacerSum); ?>
                                </td>
                                <td class="reg-ser-cel"
                                    onclick="abrirModalNota(<?php echo $estId; ?>, 'ser', 'Ser 1')">
                                    <?php echo notaCelda($allNotas, 'ser', 'Ser 1', $serSum); ?>
                                </td>
                                <td class="reg-suma-val"><?php echo $teoria !== null ? number_format($teoria, 1) : ''; ?></td>
                                <td class="reg-suma-val"><?php echo $practica !== null ? number_format($practica, 1) : ''; ?></td>
                                <td class="reg-suma-val reg-parcial"
                                    onclick="abrirModalParcial(<?php echo $estId; ?>)">
                                    <?php echo $parcial !== null ? number_format($parcial, 1) : ''; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php for ($i = count($estudiantes); $i < 20; $i++): ?>
                                <?php filaVacia(); ?>
                            <?php endfor; ?>
                        <?php endif; ?>
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

    <!-- MODAL ASISTENCIA -->
    <div id="modalAsistencia" class="modal-overlay">
        <div class="modal">
            <h3>Registrar Asistencia</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="asistencia">
                <div class="form-group">
                    <label>Estudiante</label>
                    <select name="estudiante_id" id="modalAsistEstudiante" required>
                        <?php foreach ($estudiantes as $est): ?>
                        <option value="<?php echo (int) $est['id']; ?>"><?php echo htmlspecialchars($est['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha</label>
                    <input type="date" name="fecha" id="modalAsistFecha" required>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" required>
                        <option value="Presente">Presente</option>
                        <option value="Ausente">Ausente</option>
                        <option value="Justificado">Justificado</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModal('modalAsistencia')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL PARCIAL -->
    <div id="modalParcial" class="modal-overlay">
        <div class="modal">
            <h3>Registrar Nota Parcial</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="nota">
                <input type="hidden" name="tipo" value="parcial">
                <div class="form-group">
                    <label>Estudiante</label>
                    <select name="estudiante_id" id="modalParcEstudiante" required>
                        <?php foreach ($estudiantes as $est): ?>
                        <option value="<?php echo (int) $est['id']; ?>"><?php echo htmlspecialchars($est['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nombre Actividad</label>
                    <select name="nombre_actividad" id="modalParcActividad" required>
                        <option value="1er Parcial">1er Parcial</option>
                        <option value="2do Parcial">2do Parcial</option>
                        <option value="Parcial">Parcial Único</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nota (0 - 100)</label>
                    <input type="number" name="nota" id="modalParcNota" step="0.1" min="0" max="100" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModal('modalParcial')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL NOTA (CONOCER/HACER/SER) -->
    <div id="modalNota" class="modal-overlay">
        <div class="modal">
            <h3>Registrar Nota</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="nota">
                <input type="hidden" name="tipo" id="modalNotaTipo">
                <div class="form-group">
                    <label>Estudiante</label>
                    <select name="estudiante_id" id="modalNotaEstudiante" required>
                        <?php foreach ($estudiantes as $est): ?>
                        <option value="<?php echo (int) $est['id']; ?>"><?php echo htmlspecialchars($est['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nombre Actividad</label>
                    <input type="text" name="nombre_actividad" id="modalNotaActividad" required>
                </div>
                <div class="form-group">
                    <label>Nota</label>
                    <input type="number" name="nota" id="modalNotaNota" step="0.1" min="0" max="100" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModal('modalNota')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL AÑADIR ESTUDIANTE -->
    <div id="modalEstudiante" class="modal-overlay">
        <div class="modal">
            <h3>Añadir Alumno</h3>
            <form id="formEstudiante">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>APELLIDOS Y NOMBRES</label>
                    <input type="text" name="nombre" id="modalEstNombre" required
                           placeholder="Ej. Quispe Mamani, Juan Carlos">
                </div>
                <div class="form-group">
                    <label>C.I.</label>
                    <input type="text" name="ci" id="modalEstCi" required placeholder="1234567">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModal('modalEstudiante')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModal(id) {
        document.getElementById(id).classList.add('active');
    }
    function cerrarModal(id) {
        document.getElementById(id).classList.remove('active');
    }
    function abrirModalAsistencia(estudianteId, fecha) {
        document.getElementById('modalAsistEstudiante').value = estudianteId;
        if (fecha) document.getElementById('modalAsistFecha').value = fecha;
        abrirModal('modalAsistencia');
    }
    function abrirModalParcial(estudianteId) {
        document.getElementById('modalParcEstudiante').value = estudianteId;
        abrirModal('modalParcial');
    }
    function abrirModalNota(estudianteId, tipo, actividad) {
        document.getElementById('modalNotaEstudiante').value = estudianteId;
        document.getElementById('modalNotaTipo').value = tipo;
        document.getElementById('modalNotaActividad').value = actividad;
        document.getElementById('modalNotaNota').value = '';
        abrirModal('modalNota');
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(function(m) { m.classList.remove('active'); });
        }
    });
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
    </script>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>