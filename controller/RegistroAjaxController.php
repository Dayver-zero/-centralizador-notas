<?php
session_start();

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../model/CursosModel.php';
require_once __DIR__ . '/../model/RegistroConfigModel.php';
require_once __DIR__ . '/../model/EstudiantesModel.php';

header('Content-Type: application/json; charset=utf-8');

function jsonSalida($ok, $error = '') {
    echo json_encode(['ok' => $ok, 'error' => $error]);
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'docente') {
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
$docenteId = (int) $_SESSION['referer_id'];

if (!$cursoId || !$materiaId) {
    jsonSalida(false, 'Faltan curso o materia.');
}

$cursosModel = new CursosModel();
if (!$cursosModel->esDocenteAsignado($docenteId, $materiaId, $cursoId)) {
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

if ($accion === 'quitar_vacios') {
    $estudiantesModel = new EstudiantesModel();
    $borrados = $estudiantesModel->quitarVacios($cursoId, $materiaId);
    echo json_encode(['ok' => true, 'borrados' => $borrados]);
    exit;
}

jsonSalida(false, 'Accion desconocida.');