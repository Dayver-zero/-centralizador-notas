<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/csrf.php';

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['rol']) {
        case 'admin':
            header("Location: /centralizador_notas/view/admin/dashboard.php");
            break;
        case 'docente':
            header("Location: /centralizador_notas/view/docente/dashboard.php");
            break;
        case 'estudiante':
            header("Location: /centralizador_notas/view/estudiante/dashboard.php");
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesion - Instituto Tecnologico PACCIOLI</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <canvas id="canvas"></canvas>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="/centralizador_notas/view/img/escudo.jpg" alt="Escudo" class="logo">
                <h1>Instituto Tecnologico "PACCIOLI"</h1>
                <h2>Sistema Centralizador de Notas</h2>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                    switch ($_GET['error']) {
                        case 'complete': echo 'Complete todos los campos.'; break;
                        case 'invalid': echo 'Usuario o contrasena incorrectos.'; break;
                        case 'session': echo 'Debe iniciar sesion.'; break;
                        default: echo 'Error desconocido.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form action="/centralizador_notas/controller/LoginController.php" method="POST" class="login-form">
                <?php echo csrf_campo(); ?>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Usuario" required autofocus>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Contrasena" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesion
                </button>
            </form>

            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> Instituto Tecnologico "PACCIOLI"</p>
            </div>
        </div>
    </div>

    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
