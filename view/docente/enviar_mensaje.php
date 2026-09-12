<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/MensajesModel.php';

$estudiantesModel = new EstudiantesModel();
$mensajesModel = new MensajesModel();
$estudiantes = $estudiantesModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $mensajesModel->enviarMensaje($_SESSION['referer_id'], 'docente', $_POST['receptor_id'], 'estudiante', $_POST['asunto'], $_POST['mensaje']);
    header("Location: /centralizador_notas/view/docente/enviar_mensaje.php?msg=sent");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Enviar Mensaje</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
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
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>

    <div class="container">
        <h1>Enviar Mensaje</h1>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sent'): ?><div class="alert alert-success">Mensaje enviado.</div><?php endif; ?>
        <div class="form-container">
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>Destinatario (Estudiante)</label>
                    <select name="receptor_id" required>
                        <?php foreach ($estudiantes as $e): ?>
                        <option value="<?php echo (int) $e['id']; ?>"><?php echo htmlspecialchars($e['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Asunto</label><input type="text" name="asunto" required></div>
                <div class="form-group"><label>Mensaje</label><textarea name="mensaje" rows="5" required></textarea></div>
                <button type="submit" class="btn btn-success">Enviar</button>
            </form>
        </div>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
