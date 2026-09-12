<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /centralizador_notas/index.php?error=session"); exit; }

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../model/MensajesModel.php';

$model = new MensajesModel();
$msgId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($msgId <= 0) { header("Location: /centralizador_notas/index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $model->responder($_POST['mensaje_id'], $_SESSION['referer_id'], $_SESSION['rol'], $_POST['respuesta']);
    header("Location: /centralizador_notas/view/ver_respuestas.php?id=" . (int) $_POST['mensaje_id'] . "&msg=sent");
    exit;
}

$mensaje    = $model->obtenerMensaje($msgId);
$respuestas = $model->obtenerRespuestas($msgId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Responder Mensaje</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
</head>
<body>
    <div class="container">
        <h2>Responder Mensaje</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sent'): ?>
            <div class="alert alert-success">Respuesta enviada.</div>
        <?php endif; ?>

        <?php if (!$mensaje): ?>
            <div class="alert alert-info">Mensaje no encontrado.</div>
        <?php else: ?>
            <div class="tarjeta-mensaje" style="background:rgba(255,255,255,0.08); border-radius:10px; padding:15px; margin-bottom:15px;">
                <h3 style="color:#007acc; margin-bottom:5px;"><?php echo htmlspecialchars($mensaje['asunto']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?></p>
                <small style="color:rgba(255,255,255,0.4);"><?php echo htmlspecialchars($mensaje['fecha_envio']); ?></small>
            </div>

            <h3>Respuestas</h3>
            <?php if (empty($respuestas)): ?>
                <div class="alert alert-info">Sin respuestas.</div>
            <?php else: ?>
                <?php foreach ($respuestas as $r): ?>
                <div style="margin-bottom:10px; padding:10px 14px; background:rgba(0,122,204,0.15); border-radius:6px; border-left:3px solid #007acc;">
                    <strong><?php echo htmlspecialchars($r['emisor']); ?> dijo:</strong><br>
                    <?php echo nl2br(htmlspecialchars($r['respuesta'])); ?>
                    <small style="color:rgba(255,255,255,0.4); margin-left:8px;"><?php echo htmlspecialchars($r['fecha_respuesta']); ?></small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" style="margin-top:15px;">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="mensaje_id" value="<?php echo (int) $msgId; ?>">
                <div class="form-group">
                    <label>Tu respuesta:</label>
                    <textarea name="respuesta" rows="4" style="width:100%; padding:8px; background:rgba(255,255,255,0.08); color:white; border:1px solid rgba(255,255,255,0.2); border-radius:6px;" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar Respuesta</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
