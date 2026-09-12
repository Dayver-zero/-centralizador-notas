<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/MensajesModel.php';

$model = new MensajesModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $model->responder($_POST['mensaje_id'], $_SESSION['referer_id'], 'admin', $_POST['respuesta']);
    header("Location: /centralizador_notas/view/admin/mensajes.php?msg=sent");
    exit;
}

$mensajes = $model->getMensajesRecibidos($_SESSION['referer_id'], 'admin');
$model->marcarTodosLeidos($_SESSION['referer_id'], 'admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Bandeja de Entrada</title>
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
        <h1>Bandeja de Entrada</h1>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sent'): ?><div class="alert alert-success">Respuesta enviada.</div><?php endif; ?>
        <?php if (empty($mensajes)): ?>
            <div class="alert alert-info">No tiene mensajes.</div>
        <?php else: ?>
            <?php foreach ($mensajes as $m): ?>
            <div style="background:rgba(255,255,255,0.08); border-radius:10px; padding:15px; margin-bottom:15px; border-left:4px solid #007acc;">
                <h3 style="color:#007acc; margin-bottom:5px;"><?php echo htmlspecialchars($m['asunto']); ?></h3>
                <p style="color:rgba(255,255,255,0.7); margin-bottom:5px;">De: <?php echo htmlspecialchars($m['emisor_nombre'] ?? 'Desconocido'); ?></p>
                <p><?php echo nl2br(htmlspecialchars($m['mensaje'])); ?></p>
                <?php foreach ($model->obtenerRespuestas($m['id']) as $r): ?>
                <div style="margin-top:8px; padding:8px 12px; background:rgba(0,122,204,0.15); border-radius:6px; border-left:3px solid #007acc;">
                    <strong>Respuesta:</strong> <?php echo nl2br(htmlspecialchars($r['respuesta'])); ?>
                    <small style="color:rgba(255,255,255,0.4); margin-left:8px;"><?php echo htmlspecialchars($r['fecha_respuesta']); ?></small>
                </div>
                <?php endforeach; ?>
                <small style="color:rgba(255,255,255,0.4);"><?php echo htmlspecialchars($m['fecha_envio']); ?></small>
                <form method="POST" style="margin-top:10px;">
                    <?php echo csrf_campo(); ?>
                    <input type="hidden" name="mensaje_id" value="<?php echo (int) $m['id']; ?>">
                    <textarea name="respuesta" placeholder="Escriba su respuesta..." style="width:100%; padding:8px; background:rgba(255,255,255,0.08); color:white; border:1px solid rgba(255,255,255,0.2); border-radius:6px; min-height:60px;" required></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top:8px;">Responder</button>
                </form>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
