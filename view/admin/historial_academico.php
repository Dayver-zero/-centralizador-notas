<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';

$cursosModel     = new CursosModel();
$materiasModel   = new MateriasModel();
$estudiantesModel = new EstudiantesModel();
$notasModel      = new NotasModel();

$modo        = $_GET['modo'] ?? 'curso';
$cursos      = $cursosModel->getAll();
$estudiantes = $estudiantesModel->getAll();
$gestion     = isset($_GET['gestion']) && $_GET['gestion'] !== '' ? (int) $_GET['gestion'] : (int) date('Y');

function notaFinalParcial($primero, $segundo) {
    if ($primero === null && $segundo === null) return null;
    if ($primero === null) $primero = 0;
    if ($segundo === null) $segundo = 0;
    return round(($primero + $segundo) / 2, 1);
}

function fechaBoletin($ts) {
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

function semestrePalabra($n) {
    $map = [1 => 'PRIMERO', 2 => 'SEGUNDO', 3 => 'TERCERO',
            4 => 'CUARTO', 5 => 'QUINTO', 6 => 'SEXTO'];
    return $map[(int) $n] ?? 'PRIMERO';
}

function fechaAdmision($anio) {
    $a = (int) $anio;
    return '01/02/' . $a;
}

function fechaConclusion($anio) {
    $a = (int) $anio;
    return '01/02/' . ($a + 3);
}

// ===== MODO CURSO/MATERIA (Entrega de Calificaciones - formato RUBEN SISTEMAS.xlsx) =====
$calendario = null; // tabla de entregas
if ($modo === 'curso') {
    $cursoSel    = $cursosModel->getById(isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0);
    $materiaSel  = null;
    $materiasCurso = $cursoSel ? $cursosModel->getMateriasPorCurso((int) $cursoSel['id']) : [];

    if ($cursoSel && isset($_GET['materia_id'])) {
        $materiaSel = null;
        foreach ($materiasCurso as $mc) {
            if ((int) $mc['id'] === (int) $_GET['materia_id']) { $materiaSel = $mc; break; }
        }
    }

    if ($cursoSel && $materiaSel) {
        $docenteStmt = $conn->prepare("SELECT d.nombre_completo FROM docente_materia_curso dmc
                                       JOIN docentes d ON d.id = dmc.docente_id
                                       WHERE dmc.curso_id = ? AND dmc.materia_id = ? LIMIT 1");
        $docenteStmt->bind_param("ii", $cursoSel['id'], $materiaSel['id']);
        $docenteStmt->execute();
        $docenteRow = $docenteStmt->get_result()->fetch_assoc();
        $docenteStmt->close();
        $cursoSel['docente_nombre'] = $docenteRow['nombre_completo'] ?? 'Asignado';

        $filas = $notasModel->getParcialesPorCurso((int) $cursoSel['id'], (int) $materiaSel['id']);
        $calendario = [];
        foreach ($filas as $f) {
            $id = (int) $f['estudiante_id'];
            if (!isset($calendario[$id])) {
                $calendario[$id] = [
                    'nombre' => $f['nombre_completo'],
                    'matricula' => $f['matricula'],
                    'primero' => null,
                    'segundo' => null,
                ];
            }
            $act = $f['nombre_actividad'];
            if ($act === '1er Parcial' && $f['nota'] !== null) $calendario[$id]['primero'] = (float) $f['nota'];
            elseif ($act === '2do Parcial' && $f['nota'] !== null) $calendario[$id]['segundo'] = (float) $f['nota'];
            elseif ($act === 'Parcial' && $f['nota'] !== null && $calendario[$id]['primero'] === null) $calendario[$id]['primero'] = (float) $f['nota'];
        }
        ksort($calendario);
    }
}

// ===== MODO ESTUDIANTE (Kardex - formato historial.xlsx) =====
$kardex = null;
if ($modo === 'estudiante' && isset($_GET['estudiante_id'])) {
    $estId   = (int) $_GET['estudiante_id'];
    $est     = $estudiantesModel->getById($estId);
    $notas   = $est ? $notasModel->getResumenNotas($estId) : [];
    $kardex  = ['estudiante' => $est, 'filas' => []];
    if ($est) {
        $grupos = [];
        foreach ($notas as $n) {
            if (($n['tipo'] ?? '') !== 'parcial') continue;
            $act = $n['nombre_actividad'];
            if (!in_array($act, ['1er Parcial', '2do Parcial', 'Parcial'], true)) continue;
            $clave = (int) $n['materia_id'] . '|' . (int) $n['gestion'] . '|' . (int) $n['curso_id'];
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'gestion' => (int) $n['gestion'],
                    'semestre' => (int) ($n['semestre'] ?? 0),
                    'codigo' => $n['codigo'],
                    'materia' => $n['materia'],
                    'curso' => $n['curso'],
                    'primero' => null,
                    'segundo' => null,
                    'nota' => null,
                ];
            }
            if ($act === '1er Parcial') $grupos[$clave]['primero'] = (float) $n['nota'];
            elseif ($act === '2do Parcial') $grupos[$clave]['segundo'] = (float) $n['nota'];
            else $grupos[$clave]['nota'] = (float) $n['nota'];
        }
        foreach ($grupos as $g) {
            if ($g['nota'] === null) {
                $g['nota'] = notaFinalParcial($g['primero'], $g['segundo']);
            }
            $kardex['filas'][] = $g;
        }
        usort($kardex['filas'], function ($a, $b) {
            if ($a['gestion'] !== $b['gestion']) return $a['gestion'] - $b['gestion'];
            return strcmp($a['materia'], $b['materia']);
        });

        $acum = 0; $n = 0; $aprobadas = 0;
        foreach ($kardex['filas'] as $f) {
            if ($f['nota'] !== null) {
                $acum += $f['nota'];
                $n++;
                if ($f['nota'] >= 61) $aprobadas++;
            }
        }
        $kardex['promedio'] = $n > 0 ? round($acum / $n, 1) : 0;
        $kardex['aprobadas'] = $aprobadas;
        $kardex['carrera'] = $est && $notas ? ($notas[0]['carrera_nombre'] ?? '') : '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Académico</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_historial.css">
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

    <div class="historial-container">
        <h1>Historial Académico</h1>

        <!-- Selector de modo -->
        <div class="modo-switch">
            <a href="?modo=curso" class="btn <?php echo $modo === 'curso' ? 'btn-primary' : 'btn-secondary'; ?>">Por Curso / Materia</a>
            <a href="?modo=estudiante" class="btn <?php echo $modo === 'estudiante' ? 'btn-primary' : 'btn-secondary'; ?>">Por Estudiante</a>
        </div>

        <?php if ($modo === 'curso'): ?>
            <form method="GET" class="filtro-form">
                <input type="hidden" name="modo" value="curso">
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" id="selCurso" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?php echo (int) $c['id']; ?>"
                                <?php echo $cursoSel && (int) $c['id'] === (int) $cursoSel['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nombre']); ?> - <?php echo htmlspecialchars($c['paralelo']); ?> (<?php echo (int) $c['gestion']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Materia</label>
                    <select name="materia_id" id="selMateria" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($materiasCurso as $mc): ?>
                            <option value="<?php echo (int) $mc['id']; ?>"
                                <?php echo $materiaSel && (int) $mc['id'] === (int) $materiaSel['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mc['nombre']); ?> (<?php echo htmlspecialchars($mc['codigo']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Consultar</button>
            </form>

            <?php if ($calendario !== null): ?>
                <!-- ENTREGA DE CALIFICACIONES (calco de RUBEN SISTEMAS.xlsx) -->
                <div class="sheet">
                    <div class="sheet-head">
                        <img src="/centralizador_notas/view/img/escudo.jpg" alt="Escudo" class="sheet-logo">
                        <div class="sheet-school">
                            <div class="inst-1">INSTITUTO TECNOLÓGICO</div>
                            <div class="inst-2">"PACCIOLI"</div>
                        </div>
                    </div>

                    <div class="sheet-title">ENTREGA DE CALIFICACIONES</div>

                    <table class="tabla-fields">
                        <tr>
                            <td class="f-lbl">CARRERA:</td>
                            <td class="f-val"><?php echo htmlspecialchars($cursoSel['carrera_nombre'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <td class="f-lbl">MATERIA:</td>
                            <td class="f-val"><?php echo htmlspecialchars($materiaSel['nombre']); ?></td>
                        </tr>
                        <tr>
                            <td class="f-lbl">TURNO:</td>
                            <td class="f-val">MAÑANA</td>
                        </tr>
                        <tr>
                            <td class="f-lbl">NOTA DE APROBACION:</td>
                            <td class="f-val">61</td>
                        </tr>
                        <tr>
                            <td class="f-lbl">SEMESTRE:</td>
                            <td class="f-val"><?php echo semestrePalabra($cursoSel['anio'] ?? $cursoSel['semestre'] ?? 1); ?></td>
                        </tr>
                        <tr>
                            <td class="f-lbl">GESTIÓN:</td>
                            <td class="f-val"><?php echo (int) ($cursoSel['gestion'] ?? $gestion); ?></td>
                        </tr>
                        <tr>
                            <td class="f-lbl">DOOCENTE</td>
                            <td class="f-val"><?php echo htmlspecialchars($cursoSel['docente_nombre'] ?? 'Asignado'); ?></td>
                        </tr>
                    </table>

                    <table class="tabla-entrega">
                        <thead>
                            <tr>
                                <th class="th-nro">N°</th>
                                <th class="th-nombre">APELLIDOS Y NOMBRES</th>
                                <th>1er PARCIAL</th>
                                <th>2do PARCIAL</th>
                                <th>PROMEDIO</th>
                                <th>INSTANCIA</th>
                                <th>NOTA FINAL</th>
                                <th>OBSERBACION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($calendario)): ?>
                                <tr><td colspan="8" class="sin-datos">No hay notas registradas para este curso/materia.</td></tr>
                            <?php else: ?>
                                <?php $n = 1; foreach ($calendario as $d): ?>
                                <?php
                                    $promedio = notaFinalParcial($d['primero'], $d['segundo']);
                                    $notaFin  = $promedio !== null ? round($promedio, 0) : null;
                                    $obs      = $notaFin !== null ? ($notaFin >= 61 ? 'APROBADO' : 'REPROBADO') : '';
                                ?>
                                <tr>
                                    <td><?php echo $n++; ?></td>
                                    <td class="td-nombre"><?php echo htmlspecialchars($d['nombre']); ?></td>
                                    <td><?php echo $d['primero'] !== null ? number_format($d['primero'], 1) : ''; ?></td>
                                    <td><?php echo $d['segundo'] !== null ? number_format($d['segundo'], 1) : ''; ?></td>
                                    <td><?php echo $promedio !== null ? number_format($promedio, 1) : ''; ?></td>
                                    <td>REGULAR</td>
                                    <td><?php echo $notaFin !== null ? (int) $notaFin : ''; ?></td>
                                    <td><?php echo $obs; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="firmas">
                        <div class="firma">
                            <div class="firma-linea"></div>
                            <div class="firma-label">FIRMA DEL DOCENTE</div>
                        </div>
                        <div class="firma">
                            <div class="firma-linea"></div>
                            <div class="firma-label">FIRMA DE LA INSTITUCIÓN</div>
                        </div>
                    </div>
                </div>
                <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
            <?php endif; ?>

        <?php else: ?>
            <!-- Kardex por estudiante (calco de historial.xlsx) -->
            <form method="GET" class="filtro-form">
                <input type="hidden" name="modo" value="estudiante">
                <div class="form-group">
                    <label>Estudiante</label>
                    <select name="estudiante_id" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($estudiantes as $e): ?>
                            <option value="<?php echo (int) $e['id']; ?>"
                                <?php echo isset($_GET['estudiante_id']) && (int) $_GET['estudiante_id'] === (int) $e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nombre_completo']); ?> (<?php echo htmlspecialchars($e['ci']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Consultar</button>
            </form>

            <?php if ($kardex !== null && $kardex['estudiante']): ?>
                <!-- HISTORIAL ACADÉMICO / KARDEX -->
                <div class="sheet">
                    <div class="sheet-head">
                        <img src="/centralizador_notas/view/img/escudo.jpg" alt="Escudo" class="sheet-logo">
                        <div class="sheet-school">
                            <div class="inst-1">INSTITUTO TECNOLÓGICO</div>
                            <div class="inst-2">"PACCIOLI"</div>
                        </div>
                    </div>

                    <div class="sheet-title">HISTORIAL ACADÉMICO</div>

                    <table class="kardex-enc">
                        <tr>
                            <td class="enc-lab">INSTITUTO:</td>
                            <td class="enc-val">INSTITUTO TECNOLÓGICO PACCIOLI</td>
                            <td colspan="2" class="enc-vacio"></td>
                        </tr>
                        <tr>
                            <td class="enc-lab">MATRICULA:</td>
                            <td class="enc-val"><?php echo htmlspecialchars($kardex['estudiante']['matricula'] ?? ''); ?></td>
                            <td colspan="2" class="enc-vacio"></td>
                        </tr>
                        <tr>
                            <td class="enc-lab">ESTUDIANTE:</td>
                            <td class="enc-val"><?php echo htmlspecialchars($kardex['estudiante']['nombre_completo'] ?? ''); ?></td>
                            <td colspan="2" class="enc-vacio"></td>
                        </tr>
                        <tr>
                            <td class="enc-lab">CARRERA:</td>
                            <td class="enc-val"><?php echo htmlspecialchars($kardex['carrera']); ?></td>
                            <td class="enc-lab">CEDULA DE IDENTIDAD:</td>
                            <td class="enc-val"><?php echo htmlspecialchars($kardex['estudiante']['ci'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <td class="enc-lab">NIVEL DE FORMACIÓN:</td>
                            <td class="enc-val">TÉCNICO SUPERIOR</td>
                            <td class="enc-lab">FECHA DE ADMISIÓN:</td>
                            <td class="enc-val"><?php echo fechaAdmision($kardex['estudiante']['anio_ingreso'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <td class="enc-lab">RÉGIMEN:</td>
                            <td class="enc-val">ANUALIZADO</td>
                            <td class="enc-lab">FECHA DE CONCLUSIÓN:</td>
                            <td class="enc-val"><?php echo fechaConclusion($kardex['estudiante']['anio_ingreso'] ?? 0); ?></td>
                        </tr>
                    </table>

                    <table class="tabla-kardex">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>GESTIÓN ACADÉMICA</th>
                                <th>SEMESTRE/AÑO</th>
                                <th>CÓDIGO</th>
                                <th>ASIGNATURA</th>
                                <th>PRE REQUISITO</th>
                                <th>NOTA</th>
                                <th>PRUEBA RECUP.</th>
                                <th>OBSERVACIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kardex['filas'])): ?>
                                <tr><td colspan="9" class="sin-datos">El estudiante no tiene notas registradas.</td></tr>
                            <?php else: ?>
                                <?php $n = 1; foreach ($kardex['filas'] as $f): ?>
                                <?php
                                    $nota   = $f['nota'];
                                    $notaFin = $nota !== null ? round($nota, 0) : null;
                                    $obs    = $notaFin !== null ? ($notaFin >= 61 ? 'APROBADO' : 'REPROBADO') : '';
                                ?>
                                <tr>
                                    <td><?php echo $n++; ?></td>
                                    <td><?php echo (int) $f['gestion']; ?></td>
                                    <td><?php echo $f['semestre'] > 0 ? semestrePalabra($f['semestre']) : ''; ?></td>
                                    <td><?php echo htmlspecialchars($f['codigo']); ?></td>
                                    <td class="td-nombre"><?php echo htmlspecialchars($f['materia']); ?></td>
                                    <td>-</td>
                                    <td><?php echo $nota !== null ? number_format($nota, 1) : ''; ?></td>
                                    <td></td>
                                    <td><?php echo $obs; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="kardex-lugar">
                        <span class="k-lbl">Lugar y fecha:</span>
                        <span class="k-val">Punata, <?php echo fechaBoletin(time()); ?></span>
                    </div>

                    <div class="firma-autoridad">
                        <div class="fa-linea"></div>
                        <div class="fa-label">Firma de autoridad Academica</div>
                    </div>

                    <table class="tabla-escala">
                        <tr>
                            <td></td>
                            <td colspan="3" class="es-tit">ESCALA DE VALORACIÓN</td>
                            <td></td>
                            <td></td>
                            <td colspan="3" class="es-der">Carga horaria:&nbsp;&nbsp;3600hrs.</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>61 a 100</td>
                            <td colspan="2">APROBADO</td>
                            <td></td>
                            <td></td>
                            <td colspan="3" class="es-der">Asignaturas aprobadas:&nbsp;&nbsp;<?php echo (int) $kardex['aprobadas']; ?>/<?php echo count($kardex['filas']); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>0 a 60</td>
                            <td colspan="2">REPROBADO</td>
                            <td colspan="2">Sello del Instituto</td>
                            <td colspan="3" class="es-der">Promedio de Calificaciones:&nbsp;&nbsp;<?php echo number_format($kardex['promedio'], 1); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>61</td>
                            <td colspan="2">NOTA MÍNIMA</td>
                            <td></td>
                            <td></td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="9" class="es-nota">Cualquier raspadura o enmienda invalida el presente documento.</td>
                        </tr>
                    </table>
                </div>
                <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>