<?php
session_start();
session_destroy();
header("Location: /centralizador_notas/login.php");
exit;
