<?php
session_start();

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

header("Location: /centralizador_notas/login.php");
exit;
