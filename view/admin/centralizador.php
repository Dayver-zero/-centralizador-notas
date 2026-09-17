<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /centralizador_notas/index.php?error=session');
    exit;
}

require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/PlanillasModel.php';
require_once __DIR__ . '/../../includes/csrf.php';

$cursosModel = new CursosModel();
$model = new PlanillasModel();
$cursoId = (int) ($_GET['curso_id'] ?? 0);
$materiaId = (int) ($_GET['materia_id'] ?? 0);

$data = $cursoId ? $model->datosCentralizador($cursoId) : null;
$curso = $data ? $data['curso'] : null;
$materias = $data ? $data['materias'] : [];
$asignaciones = $data ? $data['asignaciones'] : [];
$central = $data ? $data['central'] : [];
$inscritos = $data ? $data['inscritos'] : 0;
$aprobados = $data ? $data['aprobados'] : 0;
$reprobados = $data ? $data['reprobados'] : 0;
$abandonos = $data ? $data['abandonos'] : 0;

function notaEstado(array $materias) {
    $proms = [];
    foreach ($materias as $prom) {
        if ($prom !== null && $prom !== '') $proms[] = (float) $prom;
    }
    if (!$proms) return '';
    return min($proms) >= 61 ? 'APROBADO' : 'REPROBADO';
}

function pct($cant, $total) {
    return $total ? number_format($cant * 100 / $total, 1) : '0';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centralizador de Notas</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_historial.css?v=6">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script defer src="/centralizador_notas/js/script_planillas.js?v=10"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <style>
        .centralizador-container { position: relative; z-index: 1; margin: 70px auto 0; padding: 28px 20px; max-width: 1250px; }
        .centralizador-container h1 { color: #222; }
        .centralizador-sheet { background: #fff; color: #000; border: 2px solid #000; border-radius: 0; padding: 14px 16px 22px; box-shadow: 0 4px 18px rgba(0,0,0,.3); }
        body.dark-mode .centralizador-container h1 { color: #fff; }
        body.dark-mode .centralizador-sheet { background: #fff; }

        .cen-top { display: flex; justify-content: space-between; align-items: center; border: 1px solid #000; padding: 3px 8px; font-size: 8pt; margin-bottom: 6px; }
        .cen-cod { font-weight: 400; }
        .cen-cod strong { font-size: 9pt; }

        .cen-banda { display: flex; align-items: stretch; justify-content: space-between; gap: 8px; margin: 0 0 6px; }
        .cen-banda .cen-titulo { flex: 1; font-size: 16pt; font-weight: 700; letter-spacing: 3px; text-align: center; border: 1px solid #000; padding: 12px 8px; line-height: 1.25; }
        .cen-banda .cen-libro-folio { border: 1px solid #000; min-width: 185px; font-size: 8pt; padding: 5px 10px; display: flex; flex-direction: column; justify-content: center; gap: 6px; box-sizing: border-box; }
        .cen-banda .cen-libro-folio .lf-row { display: flex; align-items: baseline; gap: 4px; white-space: nowrap; }
        .cen-banda .cen-libro-folio .lf-val { border-bottom: 1px solid #000; flex: 1; padding: 0 8px; }

        .cen-ciudad { display: flex; justify-content: space-between; align-items: baseline; border: 1px solid #000; font-size: 8pt; padding: 3px 8px; margin-bottom: 6px; }
        .cen-ciudad .cit { font-weight: 700; }
        .cen-ciudad .tur-val { border-bottom: 1px solid #000; padding: 0 24px; }

        .cen-inst { display: flex; gap: 14px; flex-wrap: wrap; border: 1px solid #000; font-size: 8pt; padding: 4px 8px; align-items: baseline; }
        .cen-inst .cen-lbl { font-weight: 700; white-space: nowrap; }
        .cen-inst .cen-val { border-bottom: 1px solid #000; padding: 0 14px; min-width: 80px; display: inline-block; }

        .cen-table-wrap { overflow-x: auto; margin-top: 0; }
        .centralizador-table { width: 100%; min-width: 1000px; border-collapse: collapse; border: 1px solid #000; font-size: 7.5pt; }
        .centralizador-table th, .centralizador-table td { border: 1px solid #000 !important; padding: 3px 5px; color: #000; text-align: center; white-space: nowrap; background: #fff; vertical-align: middle; font-weight: 400; }
        .centralizador-table th { background: #e8eef4; font-weight: 700; font-size: 7pt; }
        .centralizador-table th.vert { writing-mode: vertical-rl; text-orientation: mixed; transform: rotate(180deg); height: 150px; min-width: 30px; padding: 4px 2px; }
        .centralizador-table .th-labels { text-align: left; font-weight: 700; padding: 5px 8px; background: #e8eef4; white-space: normal; min-width: 130px; }
        .centralizador-table .th-labels .lab { line-height: 1.55; font-size: 7pt; }
        .centralizador-table .th-labels .lab span { border-bottom: 1px solid #000; padding: 0 4px; font-weight: 400; }
        .centralizador-table .th-nro { width: 30px; }
        .centralizador-table .th-nombre { min-width: 200px; }
        .centralizador-table .th-ci { width: 74px; }
        .centralizador-table .th-cod { font-size: 6.5pt; }
        .centralizador-table .th-estado { width: 62px; }
        .centralizador-table .th-obs { width: 80px; }
        .centralizador-table .th-mat { min-width: 34px; }
        .centralizador-table tbody tr td { min-height: 22px; }
        .centralizador-table .nombre { min-width: 200px; text-align: left; font-weight: 600; padding-left: 6px; }
        .centralizador-table .estado { font-weight: 700; font-size: 6.5pt; }
        .centralizador-table td[contenteditable="true"] { cursor: text; background: #fff; outline: 0; transition: background .15s, box-shadow .15s; }
        .centralizador-table td[contenteditable="true"]:hover { background: #eef6ff; box-shadow: inset 0 0 0 1px #0a6cd6; }
        .centralizador-table td[contenteditable="true"]:focus { background: #fff9db; box-shadow: inset 0 0 0 2px #0a6cd6; outline: none; }
        .sin-datos { text-align: center; padding: 18px 8px; font-style: italic; }

        .cen-sello { display: flex; justify-content: space-between; margin: 22px 30px 4px; gap: 20px; }
        .cen-sello .firma { text-align: center; width: 220px; }
        .cen-sello .firma-linea { border-bottom: 2px solid #000; margin-bottom: 5px; margin-top: 55px; }
        .cen-sello .firma-label { font-size: 7.5pt; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }

        .cen-title { text-align: center; font-size: 13pt; font-weight: 700; letter-spacing: 2px; border: 1px solid #000; padding: 5px 8px; margin: 14px 80px 0; color: #000; background: #f4f7fa; }

        .cen-mat-grid { margin: 12px 80px 0; }
        .cen-mat-row { display: flex; gap: 12px; margin-bottom: 10px; }
        .cen-mat { flex: 1; border: 1px solid #000; text-align: center; padding: 8px 6px; }
        .cen-mat .doc { font-size: 7.5pt; font-weight: 700; }
        .cen-mat .mat { font-size: 7.5pt; margin-top: 3px; }

        .cen-estadisticas { border: 1px solid #000; font-size: 7.5pt; margin: 14px 200px 0; }
        .cen-estadisticas table { width: 100%; border-collapse: collapse; }
        .cen-estadisticas th, .cen-estadisticas td { border: 1px solid #000; padding: 3px 6px; text-align: left; font-size: 7.5pt; }
        .cen-estadisticas th { background: #d6e3bc; font-weight: 700; text-align: center; }
        .cen-estadisticas td:nth-child(2), .cen-estadisticas td:nth-child(3) { text-align: center; width: 60px; }
        .cen-estadisticas .cen-est-titulo { background: #d6e3bc; font-weight: 700; text-align: center; font-size: 8pt; letter-spacing: 1px; }

        .centralizador-actions { margin: 16px 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .centralizador-actions a { padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 7px; transition: transform .2s, box-shadow .2s; }
        .centralizador-actions a:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }

        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            .centralizador-container { margin: 0 !important; padding: 0 !important; }
            .centralizador-container h1 { display: none !important; }
            .centralizador-sheet { border: none !important; box-shadow: none !important; border-radius: 0 !important; padding: 0 !important; }
            .centralizador-actions, .planilla-tools, .planilla-aviso { display: none !important; }
            .cen-top, .cen-banda, .cen-banda .cen-titulo, .cen-banda .cen-libro-folio, .cen-ciudad, .cen-inst, .cen-title { border: 1px solid #000 !important; }
            .cen-mat { border: 1px solid #000 !important; }
            .centralizador-table { border: 1px solid #000 !important; font-size: 7pt; }
            .centralizador-table th, .centralizador-table td { border: 1px solid #000 !important; color: #000 !important; background: #fff !important; }
            .centralizador-table th { background: #e8eef4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .cen-estadisticas, .cen-estadisticas th, .cen-estadisticas td { border: 1px solid #000 !important; }
            .cen-estadisticas th, .cen-estadisticas .cen-est-titulo { background: #d6e3bc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <canvas id="canvas"></canvas>
    <?php include __DIR__ . '/../../includes/menu_admin.php'; ?>
    <div class="top-header">
        <div class="logo-area"><button id="sidebar-toggle" type="button" title="Desplegar o contraer el menu" aria-label="Desplegar o contraer el menu" aria-expanded="false"><i class="fas fa-bars"></i></button><img src="/centralizador_notas/view/img/escudo.jpg" alt="Logo"><span>Instituto Tecnologico PACCIOLI</span></div>
        <div class="user-area"><span>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></span><button id="modo-btn" title="Cambiar modo">🌙</button></div>
    </div>
    <main class="centralizador-container">
        <h1>Centralizador de Notas</h1>
        <?php if ($curso): ?>
            <div class="centralizador-actions">
                <a class="btn btn-secondary" href="/centralizador_notas/view/admin/planillas_admin.php?curso_id=<?php echo $cursoId; ?>&materia_id=<?php echo $materiaId; ?>"><i class="fas fa-arrow-left"></i> Volver a planillas</a>
                <a class="btn btn-success" href="#" onclick="window.print(); return false;"><i class="fas fa-print"></i> Imprimir</a>
                <form method="POST" action="/centralizador_notas/controller/ExportarPlanillaController.php" class="export-form">
                    <input type="hidden" name="tipo" value="centralizador">
                    <input type="hidden" name="curso_id" value="<?php echo $cursoId; ?>">
                    <?php echo csrf_campo(); ?>
                    <button type="submit" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar Excel</button>
                </form>
            </div>
            <div class="centralizador-sheet">
                <div class="planilla-tools">
                    <button type="button" class="btn btn-primary" data-planilla-accion="editar" data-hoja="centralizador-hoja"><i class="fas fa-pen"></i> Editar celdas</button>
                    <button type="button" class="btn btn-success" data-planilla-accion="guardar" data-hoja="centralizador-hoja"><i class="fas fa-save"></i> Guardar cambios</button>
                    <button type="button" class="btn btn-secondary" data-planilla-accion="restablecer" data-hoja="centralizador-hoja"><i class="fas fa-undo"></i> Restablecer tabla</button>
                    <span class="tools-sep"></span>
                    <button type="button" class="btn btn-success" data-planilla-accion="add-col" data-hoja="centralizador-hoja"><i class="fas fa-plus"></i> Agregar columna</button>
                    <button type="button" class="btn btn-secondary" data-planilla-accion="remove-col" data-hoja="centralizador-hoja"><i class="fas fa-minus"></i> Quitar columna</button>
                    <button type="button" class="btn btn-success" data-planilla-accion="add-row" data-hoja="centralizador-hoja"><i class="fas fa-user-plus"></i> Agregar fila</button>
                    <button type="button" class="btn btn-secondary" data-planilla-accion="remove-row" data-hoja="centralizador-hoja"><i class="fas fa-user-minus"></i> Quitar fila</button>
                </div>

                <div class="cen-top">
                    <span class="cen-ciud">Cochabamba - Bolivia</span>
                    <span class="cen-cod">Código de Registro: <strong>80850061</strong></span>
                </div>

                <div class="cen-banda">
                    <div class="cen-titulo">CENTRALIZADOR DE CALIFICACIONES</div>
                    <div class="cen-libro-folio">
                        <div class="lf-row">LIBRO N° <span class="lf-val">&nbsp;</span></div>
                        <div class="lf-row">FOLIO N° <span class="lf-val">&nbsp;</span></div>
                    </div>
                </div>

                <div class="cen-ciudad">
                    <span>COCHABAMBA - BOLIVIA</span>
                    <span class="cit">TURNO: <span class="tur-val">&nbsp;</span></span>
                </div>

                <div class="cen-inst">
                    <span><span class="cen-lbl">INSTITUCIÓN:</span> <span class="cen-val">&nbsp;</span></span>
                    <span><span class="cen-lbl">R.M.:</span> <span class="cen-val">&nbsp;</span></span>
                    <span><span class="cen-lbl">CARÁCTER:</span> <span class="cen-val">&nbsp;</span></span>
                </div>

                <div class="cen-table-wrap">
                <table id="centralizador-hoja" class="centralizador-table" data-planilla-hoja data-tipo="centralizador" data-clave="centralizador-<?php echo $cursoId; ?>">
                    <thead>
                        <tr class="hdr-alto">
                            <th colspan="2" class="th-labels">
                                <div class="lab">GESTIÓN: <span><?php echo htmlspecialchars((int) ($curso['gestion'] ?? date('Y'))); ?></span></div>
                                <div class="lab">NIVEL: <span><?php echo htmlspecialchars(ucwords($curso['carrera_tipo'] ?? 'anual')); ?></span></div>
                                <div class="lab">CARRERA: <span><?php echo htmlspecialchars($curso['carrera_nombre'] ?? ''); ?></span></div>
                                <div class="lab">RÉGIMEN: <span><?php echo htmlspecialchars(strtoupper($curso['carrera_duracion'] ?? ($curso['carrera_tipo'] ?? 'ANUAL'))); ?></span></div>
                                <div class="lab">CURSO: <span><?php echo htmlspecialchars($curso['nombre'] ?? ''); ?></span></div>
                            </th>
                            <th rowspan="2" class="vert th-ci">CÉDULA DE IDENTIDAD</th>
                            <?php foreach ($materias as $mat): ?>
                            <th class="th-cod"><?php echo htmlspecialchars($mat['codigo'] ?? ''); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr class="hdr-bajo">
                            <th class="th-nro" data-fijo="true">N°</th>
                            <th class="th-nombre" data-fijo="true">NÓMINA ESTUDIANTES</th>
                            <?php foreach ($materias as $mat): ?>
                            <th class="vert th-mat"><?php echo htmlspecialchars($mat['nombre']); ?></th>
                            <?php endforeach; ?>
                            <th class="vert th-estado">ESTADO</th>
                            <th class="vert th-obs">OBSERVACIONES</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
                    $nro = 0;
                    foreach ($central as $est):
                        $nro++;
?>
                        <tr>
                            <td><?php echo $nro; ?></td>
                            <td class="nombre"><?php echo htmlspecialchars($est['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($est['ci'] ?? ''); ?></td>
                            <?php foreach ($materias as $mat): ?>
                            <td><?php echo isset($est['materias'][(int) $mat['id']]) && $est['materias'][(int) $mat['id']] !== null ? number_format((float) $est['materias'][(int) $mat['id']], 1) : ''; ?></td>
                            <?php endforeach; ?>
                            <td class="estado"><?php echo notaEstado($est['materias']); ?></td>
                            <td><?php echo notaEstado($est['materias']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$central): ?><tr><td colspan="<?php echo count($materias) + 5; ?>" class="sin-datos">No hay notas registradas.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                </div>

                <div class="cen-sello">
                    <div class="firma"><div class="firma-linea"></div><div class="firma-label">JEFE(A) DE CARRERA</div></div>
                    <div class="firma"><div class="firma-linea"></div><div class="firma-label">DIRECTOR(A) ACADÉMICO(A)</div></div>
                    <div class="firma"><div class="firma-linea"></div><div class="firma-label">RECTOR(A)</div></div>
                </div>

                <div class="cen-title">CENTRALIZADOR DE CALIFICACIONES</div>

                <div class="cen-mat-grid">
<?php
                    $bloques = [];
                    foreach ($asignaciones as $asg) {
                        $bloques[] = ['doc' => $asg['docente_nombre'] ?? '', 'mat' => $asg['materia_nombre'] ?? ''];
                    }
                    if (!$bloques) $bloques = [['doc' => '&nbsp;', 'mat' => '&nbsp;']];
?>
                    <?php foreach (array_chunk($bloques, 3) as $fila): ?>
                    <div class="cen-mat-row">
                        <?php foreach (array_pad($fila, 3, ['doc' => '&nbsp;', 'mat' => '&nbsp;']) as $bloque): ?>
                        <div class="cen-mat">
                            <div class="doc"><?php echo htmlspecialchars($bloque['doc']); ?></div>
                            <div class="mat"><?php echo htmlspecialchars($bloque['mat']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="cen-estadisticas">
                    <table>
                        <tr><td colspan="3" class="cen-est-titulo">ESTADÍSTICAS</td></tr>
                        <tr><th>DETALLE</th><th>CANTIDAD</th><th>%</th></tr>
                        <tr><td>ESTUDIANTES INSCRITOS</td><td><?php echo $inscritos; ?></td><td>100%</td></tr>
                        <tr><td>ESTUDIANTES APROBADOS</td><td><?php echo $aprobados; ?></td><td><?php echo pct($aprobados, $inscritos) , '%'; ?></td></tr>
                        <tr><td>ESTUDIANTES REPROBADOS</td><td><?php echo $reprobados; ?></td><td><?php echo pct($reprobados, $inscritos), '%'; ?></td></tr>
                        <tr><td>ABANDONO</td><td><?php echo $abandonos; ?></td><td><?php echo pct($abandonos, $inscritos), '%'; ?></td></tr>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <?php
                $selCursos = $cursosModel->getAll();
                $selCursoId = (int) ($_GET['curso_id'] ?? 0);
                $selMateriasSel = $selCursoId ? $cursosModel->getMateriasPorCurso($selCursoId) : [];
            ?>
            <form method="GET" class="filtro-form">
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" id="selCurso" onchange="this.form.submit()" required>
                        <option value="">-- Seleccionar curso --</option>
                        <?php foreach ($selCursos as $sc): ?>
                        <option value="<?php echo (int) $sc['id']; ?>" <?php echo $selCursoId === (int) $sc['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sc['nombre'] . ' - ' . $sc['paralelo'] . ' (' . (int) $sc['gestion'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selMateriasSel): ?>
                <div class="form-group">
                    <label>Materia</label>
                    <select name="materia_id" id="selMateria" onchange="this.form.submit()" required>
                        <option value="">-- Seleccionar materia --</option>
                        <?php foreach ($selMateriasSel as $sm): ?>
                        <option value="<?php echo (int) $sm['id']; ?>">
                            <?php echo htmlspecialchars($sm['nombre'] . ' (' . $sm['codigo'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Consultar</button>
                <?php endif; ?>
            </form>
            <p class="sin-datos">Selecciona un curso y una materia para ver el centralizador.</p>
        <?php endif; ?>
    </main>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>