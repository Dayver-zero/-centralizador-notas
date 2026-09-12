<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') { header("Location: /centralizador_notas/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/DocentesModel.php';

$model = new DocentesModel();
$materias = $model->getMaterias($_SESSION['docente_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Panel Docente</title>
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
        <h1>Panel Docente</h1>
        <h2><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></h2>

        <div class="cards-grid">
            <div class="card"><i class="fas fa-book-open"></i><h3><?php echo count($materias); ?></h3><p>Materias Asignadas</p></div>
        </div>

        <h2 style="margin-top:20px;">Mis Materias</h2>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Materia</th><th>Codigo</th><th>Curso</th><th>Paralelo</th><th>Gestion</th><th>Accion</th></tr></thead>
                <tbody>
                    <?php foreach ($materias as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['materia']); ?></td>
                        <td><?php echo htmlspecialchars($m['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($m['curso']); ?></td>
                        <td><?php echo $m['paralelo']; ?></td>
                        <td><?php echo $m['gestion']; ?></td>
                        <td>
                            <a href="/centralizador_notas/view/docente/ver_estudiantes.php?curso_id=<?php echo $m['curso_id']; ?>&materia_id=<?php echo $m['materia_id']; ?>" class="btn btn-primary">Ver Estudiantes</a>
                            <a href="/centralizador_notas/view/docente/registro_pedagogico.php?curso_id=<?php echo $m['curso_id']; ?>&materia_id=<?php echo $m['materia_id']; ?>" class="btn btn-success">Registro</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>
