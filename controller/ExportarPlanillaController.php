<?php
session_start();

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/XlsxWriter.php';
require_once __DIR__ . '/../model/PlanillasModel.php';

if (!in_array($_SESSION['rol'] ?? '', ['admin'], true)) {
    http_response_code(403);
    die('Acceso denegado.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Metodo no permitido.');
}
csrf_validar();

$model = new PlanillasModel();

function colLetter($n) {
    $s = '';
    $c = (int) $n;
    while ($c > 0) {
        $c--;
        $s = chr(65 + ($c % 26)) . $s;
        $c = intdiv($c, 26);
    }
    return $s;
}

function notaFormateada($v, $dec = 1) {
    return $v !== null && $v !== '' ? number_format((float) $v, $dec) : '';
}

// ===== ENTREGA DE CALIFICACIONES =====
function construirEntrega($xlsx, $datos, $model) {
    $stTit  = $xlsx->addStyle(['b' => true, 'sz' => 16, 'h' => 'c']);
    $stLbl  = $xlsx->addStyle(['b' => true, 'sz' => 11, 'h' => 'l']);
    $stVal  = $xlsx->addStyle(['sz' => 11, 'h' => 'l']);
    $stHdr  = $xlsx->addStyle(['b' => true, 'sz' => 11, 'h' => 'c', 'border' => 1, 'fill' => 1]);
    $stCel  = $xlsx->addStyle(['sz' => 11, 'h' => 'c', 'border' => 1]);
    $stCelL = $xlsx->addStyle(['sz' => 11, 'h' => 'l', 'border' => 1]);
    $stFir  = $xlsx->addStyle(['b' => true, 'sz' => 11, 'h' => 'c']);

    $ciclo = $datos['ciclo'];
    $n = count($ciclo);
    $colPromedio = 4 + $n;      // N°(1) + Apellidos(2-3) + parciales(4..3+n)
    $colInstancia = $colPromedio + 1;
    $colNotaFinal = $colPromedio + 2;
    $colObs = $colPromedio + 3;
    $last = $colObs;

    $xlsx->addSheet('Entrega de Calificaciones', 'landscape');
    $xlsx->setColWidth('A', 5);
    $xlsx->setColWidth('B', 25);
    $xlsx->setColWidth('C', 25);
    for ($i = 1; $i <= $n; $i++) {
        $xlsx->setColWidth(colLetter(3 + $i), 12);
    }
    $xlsx->setColWidth(colLetter($colPromedio), 12);
    $xlsx->setColWidth(colLetter($colInstancia), 12);
    $xlsx->setColWidth(colLetter($colNotaFinal), 13);
    $xlsx->setColWidth(colLetter($colObs), 16);

    $curso = $datos['curso'];
    $materia = $datos['materia'];

    // Titulo + campos
    $xlsx->merge('A6:' . colLetter($last) . '6')
         ->setCell('A6', 'ENTREGA DE CALIFICACIONES', $stTit);
    $fila = 7;
    $etiquetas = ['CARRERA:', 'MATERIA:', 'TURNO:', 'NOTA DE APROBACION:', 'SEMESTRE:', 'GESTIÓN:', 'DOCENTE:'];
    $valoresCampos = [
        $curso['carrera_nombre'] ?? '',
        $materia['nombre'] ?? '',
        $datos['turno'],
        (string) $datos['notaAprobacion'],
        $model !== null ? $model->semestrePalabra($curso['anio'] ?? $curso['semestre'] ?? 1) : 'PRIMERO',
        (string) $datos['gestion'],
        $curso['docente_nombre'] ?? 'Asignado',
    ];
    foreach ($etiquetas as $i => $etq) {
        $xlsx->setCell('A' . $fila, $etq, $stLbl);
        $xlsx->setCell('C' . $fila, $valoresCampos[$i], $stVal);
        $xlsx->setRowHeight($fila, 18);
        $fila++;
    }

    // Cabecera de tabla
    $h = 14;
    $xlsx->setCell('A' . $h, 'N°', $stHdr);
    $xlsx->merge('B' . $h . ':C' . $h)->setCell('B' . $h, 'APELLIDOS Y NOMBRES', $stHdr);
    foreach ($ciclo as $pc) {
        $lab = $pc === '4to Parcial' ? '4TO PARCIAL' : strtoupper($pc);
        $col = 3 + array_search($pc, $ciclo, true) + 1;
        $xlsx->setCell(colLetter($col) . $h, $lab, $stHdr);
    }
    $xlsx->setCell(colLetter($colPromedio) . $h, 'PROMEDIO', $stHdr);
    $xlsx->setCell(colLetter($colInstancia) . $h, 'INSTANCIA', $stHdr);
    $xlsx->setCell(colLetter($colNotaFinal) . $h, 'NOTA FINAL', $stHdr);
    $xlsx->setCell(colLetter($colObs) . $h, 'OBSERVACIÓN', $stHdr);
    $xlsx->setRowHeight($h, 24);

    // Datos
    $f = 15;
    $num = 1;
    foreach ($datos['calendario'] as $d) {
        $xlsx->setCell('A' . $f, $num, $stCel);
        $xlsx->setCell('B' . $f, $d['nombre'], $stCelL);
        foreach ($ciclo as $i => $pc) {
            $v = isset($d['notas'][$pc]) ? $d['notas'][$pc] : null;
            $xlsx->setCell(colLetter(4 + $i) . $f, $v === null ? '' : (float) $v, $stCel);
        }
        $xlsx->setCell(colLetter($colPromedio) . $f, $d['final'] !== null ? (float) $d['final'] : '', $stCel);
        $xlsx->setCell(colLetter($colInstancia) . $f, 'REGULAR', $stCel);
        $notaFin = $d['final'] !== null ? (int) round($d['final'], 0) : null;
        $xlsx->setCell(colLetter($colNotaFinal) . $f, $notaFin !== null ? $notaFin : '', $stCel);
        $obs = $notaFin !== null ? ($notaFin >= 61 ? 'APROBADO' : 'REPROBADO') : '';
        $xlsx->setCell(colLetter($colObs) . $f, $obs, $stCel);
        $xlsx->setRowHeight($f, 18);
        $num++;
        $f++;
    }

    // Firmas
    $ff = $f + 1;
    $xlsx->merge('B' . $ff . ':C' . $ff)->setCell('B' . $ff, 'FIRMA DEL DOCENTE', $stFir);
    $xlsx->merge('F' . $ff . ':G' . $ff)->setCell('F' . $ff, 'FIRMA DE LA INSTITUCIÓN', $stFir);
    $xlsx->setRowHeight($ff, 26);
}

// ===== CENTRALIZADOR DE CALIFICACIONES =====
function construirCentralizador($xlsx, $datos, $model) {
    $stTit  = $xlsx->addStyle(['b' => true, 'sz' => 16, 'h' => 'c']);
    $stSub  = $xlsx->addStyle(['b' => true, 'sz' => 13, 'h' => 'c']);
    $stLbl  = $xlsx->addStyle(['b' => true, 'sz' => 9, 'h' => 'l']);
    $stVal  = $xlsx->addStyle(['sz' => 9, 'h' => 'l']);
    $stHdr  = $xlsx->addStyle(['b' => true, 'sz' => 8, 'h' => 'c', 'border' => 1, 'fill' => 1]);
    $stHdrWrap = $xlsx->addStyle(['b' => true, 'sz' => 8, 'h' => 'l', 'border' => 1, 'fill' => 1, 'wrap' => true]);
    $stHdrVert = $xlsx->addStyle(['b' => true, 'sz' => 8, 'h' => 'c', 'border' => 1, 'fill' => 1, 'rot' => 90]);
    $stCel  = $xlsx->addStyle(['sz' => 9, 'h' => 'c', 'border' => 1]);
    $stCelL = $xlsx->addStyle(['sz' => 9, 'h' => 'l', 'border' => 1]);
    $stCelN = $xlsx->addStyle(['sz' => 9, 'h' => 'l', 'border' => 1]);
    $stFir  = $xlsx->addStyle(['b' => true, 'sz' => 8, 'h' => 'c']);
    $stMat  = $xlsx->addStyle(['sz' => 8, 'h' => 'c', 'border' => 1]);
    $stEstV = $xlsx->addStyle(['b' => true, 'sz' => 8, 'h' => 'c', 'border' => 1, 'fill' => 2]);
    $stEst  = $xlsx->addStyle(['sz' => 8, 'h' => 'c', 'border' => 1]);

    $materias = $datos['materias'];
    $nm = count($materias);
    $last = 5 + $nm;            // N° + NOMINA + CEDULA + materias + ESTADO + OBS
    $cMat = 4;                  // primera columna de materias
    $cEstado = 4 + $nm;
    $cObs = 5 + $nm;

    $xlsx->addSheet('Centralizador de Calificaciones', 'landscape');
    $xlsx->setColWidth('A', 16);
    $xlsx->setColWidth('B', 30);
    $xlsx->setColWidth('C', 16);
    for ($i = 0; $i < $nm; $i++) {
        $xlsx->setColWidth(colLetter($cMat + $i), 9);
    }
    $xlsx->setColWidth(colLetter($cEstado), 11);
    $xlsx->setColWidth(colLetter($cObs), 11);

    $curso = $datos['curso'];

    // Encabezado
    $xlsx->merge('A1:' . colLetter($last - 3) . '1')->setCell('A1', 'COCHABAMBA - BOLIVIA', $stVal);
    $xlsx->merge(colLetter($last - 2) . '1:' . colLetter($last) . '1')
         ->setCell(colLetter($last - 2) . '1', 'Código de Registro: ' . $datos['codigoRegistro'], $stVal);
    $xlsx->setRowHeight(1, 20);

    $xlsx->merge('B2:' . colLetter($last - 2) . '2')->setCell('B2', 'CENTRALIZADOR DE CALIFICACIONES', $stTit);
    $xlsx->merge(colLetter($last - 1) . '2:' . colLetter($last) . '2')->setCell(colLetter($last - 1) . '2', 'LIBRO N°', $stLbl);
    $xlsx->merge(colLetter($last - 1) . '3:' . colLetter($last) . '3')->setCell(colLetter($last - 1) . '3', 'FOLIO N°', $stLbl);
    $xlsx->setRowHeight(2, 34);
    $xlsx->setRowHeight(3, 18);

    // Turno
    $xlsx->setCell('A4', 'TURNO:', $stLbl);
    $xlsx->merge('B4:' . colLetter($last - 2) . '4')->setCell('B4', 'MAÑANA', $stVal);
    $xlsx->setRowHeight(4, 18);

    // Institucion / R.M. / Caracter
    $segW = max(1, intdiv($last, 3));
    $grupos = ['INSTITUCIÓN:', 'R.M.:', 'CARÁCTER:'];
    foreach ($grupos as $g => $grp) {
        $c1 = $g * $segW + 1;
        $cVal1 = $c1 + 1;
        $cVal2 = min(($g + 1) * $segW, $last);
        $xlsx->setCell(colLetter($c1) . '5', $grp, $stLbl);
        if ($cVal1 <= $cVal2) {
            $xlsx->merge(colLetter($cVal1) . '5:' . colLetter($cVal2) . '5')->setCell(colLetter($cVal1) . '5', '', $stVal);
        }
    }
    $xlsx->setRowHeight(5, 18);

    // Cabecera de tabla (dos filas)
    $h1 = 7; $h2 = 8;
    $bloque = "GESTIÓN: {$datos['gestion']}\n"
            . 'NIVEL: ' . ucwords($curso['carrera_tipo'] ?? 'anual') . "\n"
            . 'CARRERA: ' . ($curso['carrera_nombre'] ?? '') . "\n"
            . 'RÉGIMEN: ' . strtoupper($curso['carrera_duracion'] ?? ($curso['carrera_tipo'] ?? 'ANUAL')) . "\n"
            . 'CURSO: ' . ($curso['nombre'] ?? '');
    $xlsx->merge('A' . $h1 . ':A' . $h2)->setCell('A' . $h1, $bloque, $stHdrWrap);
    $xlsx->setCell('A' . $h2, 'N°', $stHdr);
    $xlsx->setCell('B' . $h2, 'NÓMINA ESTUDIANTES', $stHdr);
    $xlsx->merge('C' . $h1 . ':C' . $h2)->setCell('C' . $h1, 'CÉDULA DE IDENTIDAD', $stHdr);
    foreach ($materias as $i => $mat) {
        $xlsx->setCell(colLetter($cMat + $i) . $h1, $mat['codigo'] ?? '', $stHdr);
        $xlsx->setCell(colLetter($cMat + $i) . $h2, $mat['nombre'] ?? '', $stHdrVert);
    }
    $xlsx->setCell(colLetter($cEstado) . $h2, 'ESTADO', $stHdr);
    $xlsx->setCell(colLetter($cObs) . $h2, 'OBSERVACIONES', $stHdr);
    $xlsx->setRowHeight($h1, 70);
    $xlsx->setRowHeight($h2, 90);

    // Datos
    $f = 9;
    $num = 1;
    foreach ($datos['central'] as $est) {
        $xlsx->setCell('A' . $f, $num, $stCel);
        $xlsx->setCell('B' . $f, $est['nombre'], $stCelN);
        $xlsx->setCell('C' . $f, $est['ci'] ?? '', $stCel);
        foreach ($materias as $i => $mat) {
            $v = $est['materias'][(int) $mat['id']] ?? null;
            $xlsx->setCell(colLetter($cMat + $i) . $f, $v === null || $v === '' ? '' : (float) $v, $stCel);
        }
        $xlsx->setCell(colLetter($cEstado) . $f, $est['estado'] ?? '', $stCel);
        $xlsx->setCell(colLetter($cObs) . $f, $est['estado'] ?? '', $stCel);
        $xlsx->setRowHeight($f, 16);
        $num++;
        $f++;
    }

    // Firmas (jefe/director/rector)
    $ff = $f + 2;
    $xlsx->setCell('A' . $ff, 'JEFE(A) DE CARRERA', $stFir);
    $xlsx->setCell('C' . $ff, 'DIRECTOR(A) ACADÉMICO(A)', $stFir);
    $xlsx->setCell('E' . $ff, 'RECTOR(A)', $stFir);
    $xlsx->setRowHeight($ff, 22);

    // Titulo de la seccion de firmas de docentes
    $tt = $ff + 2;
    $xlsx->merge('A' . $tt . ':' . colLetter($last) . $tt)->setCell('A' . $tt, 'CENTRALIZADOR DE CALIFICACIONES', $stSub);
    $xlsx->setRowHeight($tt, 22);

    // Bloque docente/materia
    $bloques = [];
    foreach ($datos['asignaciones'] as $asg) {
        $bloques[] = ['doc' => $asg['docente_nombre'] ?? '', 'mat' => $asg['materia_nombre'] ?? ''];
    }
    if (!$bloques) $bloques = [['doc' => '', 'mat' => '']];
    $bw = max(1, intdiv($last, 3));
    $rb = $tt + 1;
    foreach (array_chunk($bloques, 3) as $filaBloques) {
        $col0 = 0;
        foreach (array_pad($filaBloques, 3, ['doc' => '', 'mat' => '']) as $j => $b) {
            $c1 = $col0 + 1;
            $c2 = min($c1 + $bw - 1, $last);
            $xlsx->merge(colLetter($c1) . $rb . ':' . colLetter($c2) . $rb)
                 ->setCell(colLetter($c1) . $rb, ($b['doc'] !== '' ? $b['doc'] . "\n" : '') . $b['mat'], $stMat);
            $col0 = $c2;
        }
        $xlsx->setRowHeight($rb, 34);
        $rb++;
    }

    // Estadisticas
    $re = $rb + 1;
    $xlsx->merge('A' . $re . ':' . colLetter($last - 2) . $re)->setCell('A' . $re, 'ESTADÍSTICAS', $stEstV);
    $xlsx->setRowHeight($re, 18);
    $re++;
    $xlsx->setCell('A' . $re, 'DETALLE', $stEstV);
    $xlsx->setCell(colLetter($last - 1) . $re, 'CANTIDAD', $stEstV);
    $xlsx->setCell(colLetter($last) . $re, '%', $stEstV);
    $re++;
    $filasEst = [
        ['ESTUDIANTES INSCRITOS', $datos['inscritos'], '100%'],
        ['ESTUDIANTES APROBADOS', $datos['aprobados'], $model->pct($datos['aprobados'], $datos['inscritos']) . '%'],
        ['ESTUDIANTES REPROBADOS', $datos['reprobados'], $model->pct($datos['reprobados'], $datos['inscritos']) . '%'],
        ['ABANDONO', $datos['abandonos'], $model->pct($datos['abandonos'], $datos['inscritos']) . '%'],
    ];
    foreach ($filasEst as $fe) {
        $xlsx->setCell('A' . $re, $fe[0], $stEst);
        $xlsx->setCell(colLetter($last - 1) . $re, $fe[1], $stEst);
        $xlsx->setCell(colLetter($last) . $re, $fe[2], $stEst);
        $re++;
    }
}

// ===== HISTORIAL ACADEMICO / KARDEX =====
function construirHistorial($xlsx, $kardex, $model) {
    $stTit  = $xlsx->addStyle(['b' => true, 'sz' => 16, 'h' => 'c']);
    $stLbl  = $xlsx->addStyle(['b' => true, 'sz' => 11, 'h' => 'l']);
    $stVal  = $xlsx->addStyle(['sz' => 11, 'h' => 'l']);
    $stHdr  = $xlsx->addStyle(['b' => true, 'sz' => 11, 'h' => 'c', 'border' => 1, 'fill' => 1]);
    $stCel  = $xlsx->addStyle(['sz' => 11, 'h' => 'c', 'border' => 1]);
    $stCelL = $xlsx->addStyle(['sz' => 11, 'h' => 'l', 'border' => 1]);
    $stObs  = $xlsx->addStyle(['b' => true, 'sz' => 10, 'h' => 'c', 'border' => 1]);
    $stDer  = $xlsx->addStyle(['b' => true, 'sz' => 10, 'h' => 'r']);
    $stEsc  = $xlsx->addStyle(['sz' => 10, 'h' => 'c', 'border' => 1]);
    $stEscL = $xlsx->addStyle(['sz' => 10, 'h' => 'l', 'border' => 1]);

    $est = $kardex['estudiante'];

    $xlsx->addSheet('Historial Académico', 'portrait');
    $anchos = [6, 16, 13, 10, 28, 13, 9, 11, 13];
    foreach ($anchos as $i => $w) {
        $xlsx->setColWidth(colLetter($i + 1), $w);
    }

    $xlsx->merge('A6:I6')->setCell('A6', 'HISTORIAL ACADÉMICO', $stTit);
    $xlsx->setRowHeight(6, 30);

    $f = 7;
    $enc = [
        ['INSTITUTO:', 'INSTITUTO TECNOLÓGICO PACCIOLI', '', ''],
        ['MATRICULA:', $est['matricula'] ?? '', '', ''],
        ['ESTUDIANTE:', $est['nombre_completo'] ?? '', '', ''],
        ['CARRERA:', $kardex['carrera'], 'CEDULA DE IDENTIDAD:', $est['ci'] ?? ''],
        ['NIVEL DE FORMACIÓN:', 'TÉCNICO SUPERIOR', 'FECHA DE ADMISIÓN:', $model->fechaAdmision($est['anio_ingreso'] ?? 0)],
        ['RÉGIMEN:', 'ANUALIZADO', 'FECHA DE CONCLUSIÓN:', $model->fechaConclusion($est['anio_ingreso'] ?? 0)],
    ];
    foreach ($enc as $row) {
        $xlsx->setCell('A' . $f, $row[0], $stLbl);
        $xlsx->setCell('B' . $f, $row[1], $stVal);
        if ($row[2] !== '') {
            $xlsx->setCell('D' . $f, $row[2], $stLbl);
            $xlsx->setCell('E' . $f, $row[3], $stVal);
        }
        $xlsx->setRowHeight($f, 16);
        $f++;
    }

    // Cabecera tabla
    $h = 15;
    $headers = ['N°', 'GESTIÓN ACADÉMICA', 'SEMESTRE/AÑO', 'CÓDIGO', 'ASIGNATURA', 'PRE REQUISITO', 'NOTA', 'PRUEBA RECUP.', 'OBSERVACIONES'];
    foreach ($headers as $i => $hd) {
        $xlsx->setCell(colLetter($i + 1) . $h, $hd, $stHdr);
    }
    $xlsx->setRowHeight($h, 24);

    // Datos
    $f = 16;
    $num = 1;
    foreach ($kardex['filas'] as $fila) {
        $nota = $fila['nota'];
        $notaFin = $nota !== null ? (int) round($nota, 0) : null;
        $obs = $notaFin !== null ? ($notaFin >= 61 ? 'APROBADO' : 'REPROBADO') : '';
        $xlsx->setCell('A' . $f, $num, $stCel);
        $xlsx->setCell('B' . $f, $fila['gestion'], $stCel);
        $xlsx->setCell('C' . $f, $fila['semestre'] > 0 ? $model->semestrePalabra($fila['semestre']) : '', $stCel);
        $xlsx->setCell('D' . $f, $fila['codigo'], $stCel);
        $xlsx->setCell('E' . $f, $fila['materia'], $stCelL);
        $xlsx->setCell('F' . $f, '-', $stCel);
        $xlsx->setCell('G' . $f, $nota !== null ? (float) $nota : '', $stCel);
        $xlsx->setCell('H' . $f, '', $stCel);
        $xlsx->setCell('I' . $f, $obs, $stCel);
        $xlsx->setRowHeight($f, 16);
        $num++;
        $f++;
    }

    // Pie
    $pf = $f + 1;
    $xlsx->merge('A' . $pf . ':I' . $pf)
         ->setCell('A' . $pf, 'Lugar y fecha: Punata, ' . $model->fechaBoletin(time()), $stVal);
    $xlsx->setRowHeight($pf, 20);

    $ff = $pf + 2;
    $xlsx->merge('C' . $ff . ':E' . $ff)->setCell('C' . $ff, 'Firma de autoridad Academica', $stObs);
    $xlsx->setRowHeight($ff, 24);

    // Escala de valoracion
    $es = $ff + 2;
    $xlsx->merge('B' . $es . ':D' . $es)->setCell('B' . $es, 'ESCALA DE VALORACIÓN', $stObs);
    $xlsx->merge('G' . $es . ':I' . $es)->setCell('G' . $es, 'Carga horaria: 3600hrs.', $stDer);
    $xlsx->setCell('A' . $es, '', $stEsc);
    $xlsx->setRowHeight($es, 16);
    $es++;
    $xlsx->merge('B' . $es . ':D' . $es)->setCell('B' . $es, '61 a 100 / APROBADO', $stEsc);
    $xlsx->merge('G' . $es . ':I' . $es)->setCell('G' . $es, 'Asignaturas aprobadas: ' . (int) $kardex['aprobadas'] . '/' . count($kardex['filas']), $stDer);
    $xlsx->setCell('A' . $es, '', $stEsc);
    $xlsx->setRowHeight($es, 16);
    $es++;
    $xlsx->merge('B' . $es . ':D' . $es)->setCell('B' . $es, '0 a 60 / REPROBADO', $stEsc);
    $xlsx->merge('E' . $es . ':F' . $es)->setCell('E' . $es, 'Sello del Instituto', $stEsc);
    $xlsx->merge('G' . $es . ':I' . $es)->setCell('G' . $es, 'Promedio de Calificaciones: ' . number_format($kardex['promedio'], 1), $stDer);
    $xlsx->setCell('A' . $es, '', $stEsc);
    $xlsx->setRowHeight($es, 16);
    $es++;
    $xlsx->merge('B' . $es . ':D' . $es)->setCell('B' . $es, '61 / NOTA MÍNIMA', $stEsc);
    $xlsx->setCell('A' . $es, '', $stEsc);
    $xlsx->setRowHeight($es, 16);
    $es++;
    $xlsx->merge('A' . $es . ':I' . $es)
         ->setCell('A' . $es, 'Cualquier raspadura o enmienda invalida el presente documento.', $stEscL);
    $xlsx->setRowHeight($es, 18);
}

// ===== DESPACHO =====
$tipo = $_POST['tipo'] ?? '';

switch ($tipo) {
    case 'entrega':
        $cursoId = (int) ($_POST['curso_id'] ?? 0);
        $materiaId = (int) ($_POST['materia_id'] ?? 0);
        $datos = $model->datosEntrega($cursoId, $materiaId);
        if (!$datos) {
            http_response_code(404);
            die('Datos no encontrados.');
        }
        $xlsx = new XlsxWriter();
        construirEntrega($xlsx, $datos, $model);
        $archivoNombre = 'Entrega_de_Calificaciones.xlsx';
        $etiqueta = 'Entrega de Calificaciones';
        break;

    case 'centralizador':
        $cursoId = (int) ($_POST['curso_id'] ?? 0);
        $datos = $model->datosCentralizador($cursoId);
        if (!$datos) {
            http_response_code(404);
            die('Datos no encontrados.');
        }
        $xlsx = new XlsxWriter();
        construirCentralizador($xlsx, $datos, $model);
        $archivoNombre = 'Centralizador_de_Calificaciones.xlsx';
        $etiqueta = 'Centralizador de Calificaciones';
        break;

    case 'historial':
        $estudianteId = (int) ($_POST['estudiante_id'] ?? 0);
        $kardex = $model->datosHistorial($estudianteId);
        if (!$kardex || !$kardex['estudiante']) {
            http_response_code(404);
            die('Estudiante no encontrado.');
        }
        $xlsx = new XlsxWriter();
        construirHistorial($xlsx, $kardex, $model);
        $archivoNombre = 'Historial_Academico.xlsx';
        $etiqueta = 'Historial Académico';
        break;

    default:
        http_response_code(400);
        die('Tipo de planilla no valido.');
}

$tmp = tempnam(sys_get_temp_dir(), 'plxlsx');
if (!$xlsx->build($tmp)) {
    @unlink($tmp);
    http_response_code(500);
    die('No se pudo generar el archivo.');
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $archivoNombre . '"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
@unlink($tmp);
exit;