<?php
session_start();

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../model/CursosModel.php';
require_once __DIR__ . '/../model/RegistroConfigModel.php';
require_once __DIR__ . '/../model/EstudiantesModel.php';
require_once __DIR__ . '/../model/ParcialPeriodoModel.php';
require_once __DIR__ . '/../model/NotasModel.php';
require_once __DIR__ . '/../model/AsistenciaModel.php';

header('Content-Type: application/json; charset=utf-8');

function jsonSalida($ok, $error = '', $extra = []) {
    $salida = ['ok' => $ok, 'error' => $error];
    if (is_array($extra) && $extra) {
        $salida += $extra;
    }
    echo json_encode($salida);
    exit;
}

function recalcFilaPara($estudianteId, $cursoId, $materiaId, $gestion, $carreraTipo, $label) {
    $notasModel = new NotasModel();
    $allNotas = $notasModel->getNotasEstudiante($estudianteId, $cursoId, $materiaId);

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

    $parcialDirecto = null;
    foreach ($allNotas as $n) {
        if (($n['tipo'] ?? '') === 'parcial' && ($n['nombre_actividad'] ?? '') === $label) {
            // Solo el parcial escrito a mano (origen=manual) se respeta como tal;
            // el autogenerado (origen=auto) se recalcula desde los componentes.
            if (($n['origen'] ?? 'manual') === 'manual') {
                $parcialDirecto = (float) $n['nota'];
                break;
            }
        }
    }

    if ($parcialDirecto !== null) {
        $teoria  = round($parcialDirecto * 0.30, 1);
        $practica = round($parcialDirecto * 0.70, 1);
        $parcial = $parcialDirecto;
    } else {
        $teoria   = $tieneConocer1 ? round($conocerSum, 0) : null;
        $practica = $tieneHacer1   ? round($hacerSum + $serSum, 0) : null;
        $parcial  = ($teoria !== null && $practica !== null) ? round($teoria + $practica, 0) : null;
    }

    return [
        'teoria'  => $teoria !== null ? (float) $teoria : null,
        'practica' => $practica !== null ? (float) $practica : null,
        'parcial' => $parcial !== null ? (float) $parcial : null,
    ];
}

function recalcFila($estudianteId, $cursoId, $materiaId, $gestion, $carreraTipo) {
    $parcialModel = new ParcialPeriodoModel();
    $activo = $parcialModel->parcialActivo($cursoId, $materiaId, $gestion, $carreraTipo);
    $label = $activo !== null ? $activo : 'Parcial';
    return recalcFilaPara($estudianteId, $cursoId, $materiaId, $gestion, $carreraTipo, $label);
}

if (!in_array($_SESSION['rol'] ?? '', ['docente', 'admin'], true)) {
    http_response_code(401);
    jsonSalida(false, 'Sesion no valida.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonSalida(false, 'Metodo no permitido.');
}

csrf_validar();

$cursoId   = isset($_POST['curso_id'])   ? (int) $_POST['curso_id']   : 0;
$materiaId = isset($_POST['materia_id']) ? (int) $_POST['materia_id'] : 0;
$docenteId = (int) ($_SESSION['referer_id'] ?? 0);

if (!$cursoId || !$materiaId) {
    jsonSalida(false, 'Faltan curso o materia.');
}

$cursosModel = new CursosModel();
if (($_SESSION['rol'] ?? '') !== 'admin' && !$cursosModel->esDocenteAsignado($docenteId, $materiaId, $cursoId)) {
    http_response_code(403);
    jsonSalida(false, 'No autorizado para este curso/materia.');
}

$curso = $cursosModel->getById($cursoId);
if (!$curso) {
    jsonSalida(false, 'Curso no encontrado.');
}
$gestion = (int) ($curso['gestion'] ?? date('Y'));

$accion = $_POST['accion'] ?? '';

if ($accion === 'aplicar_cambios') {
    $logoBinario = null;
    $logoRaw = $_POST['logo'] ?? '';
    if ($logoRaw !== '') {
        if (!preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $logoRaw)) {
            jsonSalida(false, 'Formato de imagen no valido.');
        }
        $binario = base64_decode(substr($logoRaw, strpos($logoRaw, ',') + 1), true);
        if ($binario === false || $binario === '') {
            jsonSalida(false, 'Datos de imagen corruptos.');
        }
        if (strlen($binario) > 2 * 1024 * 1024) {
            jsonSalida(false, 'La imagen supera los 2MB.');
        }
        $logoBinario = $binario;
    }

    $model = new RegistroConfigModel();
    $ok = $model->aplicarConfig($cursoId, $materiaId, $gestion, $_POST, $logoBinario);

    if ($ok) {
        jsonSalida(true);
    }
    jsonSalida(false, 'No se pudo guardar (error de base de datos).');
}

if ($accion === 'añadir_estudiante') {
    $nombre = trim($_POST['nombre'] ?? '');
    $ci     = trim($_POST['ci'] ?? '');

    if ($nombre === '') {
        jsonSalida(false, 'Debe escribir el nombre del estudiante.');
    }
    if ($ci === '') {
        jsonSalida(false, 'Debe escribir la C.I. del estudiante.');
    }

    $estudiantesModel = new EstudiantesModel();

    $existente = $estudiantesModel->buscarPorCi($ci);
    if ($existente) {
        $estudianteId = (int) $existente['id'];
        if ($estudiantesModel->estaInscrito($estudianteId, $cursoId)) {
            jsonSalida(false, 'El estudiante ya esta inscrito en este curso.');
        }
        $estudiantesModel->inscribirCurso($estudianteId, $cursoId, (int) ($curso['semestre'] ?? 1));
        jsonSalida(true);
    }

    $passwordTemporal = 'Tmp' . random_int(100000, 999999);
    $creado = $estudiantesModel->crear(
        $ci,
        $nombre,
        '',
        $gestion,
        '',
        $passwordTemporal
    );

    if (!$creado) {
        jsonSalida(false, 'No se pudo registrar el estudiante (verifique los datos).');
    }

    $nuevo = $estudiantesModel->buscarPorCi($ci);
    if (!$nuevo) {
        jsonSalida(false, 'No se pudo localizar al estudiante registrado.');
    }
    $estudianteId = (int) $nuevo['id'];
    $estudiantesModel->inscribirCurso($estudianteId, $cursoId, (int) ($curso['semestre'] ?? 1));
    jsonSalida(true);
}

if ($accion === 'enviar_parcial') {
    $parcialModel = new ParcialPeriodoModel();
    $parcial = $_POST['parcial'] ?? '';

    if (!in_array($parcial, ParcialPeriodoModel::opciones($curso['carrera_tipo'] ?? 'anual'), true)) {
        jsonSalida(false, 'Parcial no valido.');
    }
    if (!$parcialModel->esAbierto($cursoId, $materiaId, $gestion, $parcial)) {
        jsonSalida(false, 'Este parcial no esta abierto o ya fue enviado.');
    }

    // Genera desde el registro la nota parcial de cada estudiante para que el
    // historial academico salga del registro pedagogico. Reglas:
    //  - nota manual digitada en la planilla (origen=manual): se respeta siempre
    //  - nota auto-generada (origen=auto): se recalcula con los componentes al reenviar
    //  - sin datos suficientes queda en blanco (y la fila auto previa se retira)
    $estudiantesModel = new EstudiantesModel();
    $notasModel = new NotasModel();
    $carreraTipo = $curso['carrera_tipo'] ?? 'anual';
    foreach ($estudiantesModel->getByCurso($cursoId) as $est) {
        $estudianteId = (int) $est['id'];
        $notas = $notasModel->getNotasEstudiante($estudianteId, $cursoId, $materiaId);
        $fila = $notasModel->getParcialFilaDe($notas, $parcial);

        if ($fila !== null && ($fila['origen'] ?? 'manual') === 'manual') {
            continue;
        }

        $recalc = recalcFilaPara($estudianteId, $cursoId, $materiaId, $gestion, $carreraTipo, $parcial);
        if ($recalc['parcial'] === null) {
            if ($fila !== null) {
                $notasModel->eliminarNota($estudianteId, $cursoId, $materiaId, 'parcial', $parcial);
            }
            continue;
        }
        $notasModel->guardarNota($estudianteId, $cursoId, $materiaId, 'parcial', $parcial, $recalc['parcial'], $gestion, 'auto');
    }

    if ($parcialModel->enviar($cursoId, $materiaId, $gestion, $parcial, $docenteId)) {
        jsonSalida(true);
    }
    jsonSalida(false, 'No se pudo enviar el parcial.');
}

if ($accion === 'guardar_nota') {
    $tipo = $_POST['tipo'] ?? '';
    if (!in_array($tipo, ['conocer', 'hacer', 'ser', 'parcial'], true)) {
        jsonSalida(false, 'Tipo de nota no valido.');
    }
    $nombreActividad = trim($_POST['nombre_actividad'] ?? '');
    if ($nombreActividad === '') {
        jsonSalida(false, 'Falta el nombre de la actividad.');
    }
    $nota = isset($_POST['nota']) ? (float) $_POST['nota'] : -1;
    if ($nota < 0 || $nota > 100) {
        jsonSalida(false, 'La nota debe estar entre 0 y 100.');
    }
    $estudianteId = isset($_POST['estudiante_id']) ? (int) $_POST['estudiante_id'] : 0;
    if (!$estudianteId) {
        jsonSalida(false, 'Falta el estudiante.');
    }
    if ($tipo === 'parcial') {
        if (!in_array($nombreActividad, ParcialPeriodoModel::opciones($curso['carrera_tipo'] ?? 'anual'), true)) {
            jsonSalida(false, 'Parcial no valido.');
        }
        $parcialModel = new ParcialPeriodoModel();
        if (!$parcialModel->esAbierto($cursoId, $materiaId, $gestion, $nombreActividad)) {
            jsonSalida(false, 'Este parcial no esta abierto o ya fue enviado.');
        }
    }
    $notasModel = new NotasModel();
    if ($notasModel->guardarNota($estudianteId, $cursoId, $materiaId, $tipo, $nombreActividad, $nota, $gestion)) {
        if ($tipo === 'parcial') {
            $recalc = recalcFila($estudianteId, $cursoId, $materiaId, $gestion, $curso['carrera_tipo'] ?? 'anual');
            $recalc['celda'] = $recalc['parcial'] !== null ? number_format($recalc['parcial'], 1) : '';
        } else {
            $recalc = recalcFila($estudianteId, $cursoId, $materiaId, $gestion, $curso['carrera_tipo'] ?? 'anual');
            $recalc['celda'] = number_format($nota, 1);
        }
        jsonSalida(true, '', ['recalc' => $recalc]);
    }
    jsonSalida(false, 'No se pudo guardar la nota.');
}

if ($accion === 'guardar_asistencia') {
    $estado = $_POST['estado'] ?? '';
    if (!in_array($estado, ['Presente', 'Ausente', 'Justificado'], true)) {
        jsonSalida(false, 'Estado de asistencia no valido.');
    }
    $fecha = trim($_POST['fecha'] ?? '');
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        jsonSalida(false, 'Fecha no valida.');
    }
    $estudianteId = isset($_POST['estudiante_id']) ? (int) $_POST['estudiante_id'] : 0;
    if (!$estudianteId) {
        jsonSalida(false, 'Falta el estudiante.');
    }
    $asistenciaModel = new AsistenciaModel();
    if ($asistenciaModel->registrarAsistencia($estudianteId, $cursoId, $materiaId, $fecha, $estado, $gestion)) {
        $resumen = $asistenciaModel->getResumenAsistencia($estudianteId, $cursoId, $materiaId, $gestion);
        $recalc = [
            'total'      => (int) $resumen['total'],
            'porcentaje' => (float) $resumen['porcentaje'],
            'letra'      => strtoupper(substr($estado, 0, 1)),
            'estado'     => $estado,
        ];
        jsonSalida(true, '', ['recalc' => $recalc]);
    }
    jsonSalida(false, 'No se pudo guardar la asistencia.');
}

if ($accion === 'quitar_vacios') {
    $estudiantesModel = new EstudiantesModel();
    $borrados = $estudiantesModel->quitarVacios($cursoId, $materiaId);
    echo json_encode(['ok' => true, 'borrados' => $borrados]);
    exit;
}

jsonSalida(false, 'Accion desconocida.');