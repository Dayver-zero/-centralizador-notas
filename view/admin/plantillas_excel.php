<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /centralizador_notas/index.php?error=session");
    exit;
}

$plantillas = [
    [
        'archivo' => 'CENTRALIZADOR.xlsx',
        'titulo' => 'Centralizador de notas',
        'descripcion' => 'Planilla general para consolidar las calificaciones.',
    ],
    [
        'archivo' => 'historial.xlsx',
        'titulo' => 'Historial académico',
        'descripcion' => 'Formato de historial o kardex del estudiante.',
    ],
    [
        'archivo' => 'REGISTRO.xlsx',
        'titulo' => 'Registro pedagógico',
        'descripcion' => 'Registro de notas, evaluaciones y asistencia.',
    ],
    [
        'archivo' => 'SISTEMAS.xlsx',
        'titulo' => 'Entrega de calificaciones',
        'descripcion' => 'Formato institucional para entregar calificaciones.',
    ],
];

$directorioPlantillas = realpath(__DIR__ . '/../../plantillas_centralizador');
foreach ($plantillas as &$plantilla) {
    $plantilla['ruta'] = $directorioPlantillas
        ? $directorioPlantillas . DIRECTORY_SEPARATOR . $plantilla['archivo']
        : '';
    $plantilla['disponible'] = $plantilla['ruta'] !== '' && is_file($plantilla['ruta']);
    $plantilla['tamano'] = $plantilla['disponible'] ? number_format(filesize($plantilla['ruta']) / 1024, 1) . ' KB' : '';
}
unset($plantilla);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantillas Excel</title>
    <link rel="stylesheet" href="/centralizador_notas/css/estilos_menu.css">
    <script defer src="/centralizador_notas/js/script_menu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <style>
        .plantillas-container { position: relative; z-index: 1; max-width: 1100px; min-height: calc(100vh - 70px); margin: 70px auto 0; padding: 28px 20px; }
        .plantillas-intro { margin-bottom: 24px; }
        .plantillas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; }
        .plantilla-card { display: flex; flex-direction: column; justify-content: space-between; min-height: 190px; padding: 22px; background: rgba(255, 255, 255, 0.94); border-radius: 8px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12); }
        .plantilla-card h2 { margin: 12px 0 8px; font-size: 1.15rem; color: #243447; }
        .plantilla-card p { margin: 0 0 18px; color: #52606d; line-height: 1.45; }
        .plantilla-icon { color: #16803c; font-size: 2rem; }
        .plantilla-meta { margin-bottom: 14px; color: #68737d; font-size: .9rem; }
        .plantilla-actions { display: flex; flex-wrap: wrap; gap: 9px; }
        .plantilla-actions a { padding: 9px 12px; border-radius: 5px; color: #fff; text-decoration: none; font-size: .9rem; }
        .plantilla-actions button { padding: 9px 12px; border: 0; border-radius: 5px; color: #fff; font: inherit; font-size: .9rem; cursor: pointer; }
        .plantilla-open { background: #2563eb; }
        .plantilla-download { background: #16803c; }
        .plantilla-preview-button { background: #6b4f2a; }
        .plantilla-unavailable { color: #9b2c2c; font-size: .9rem; }
        .preview-panel { margin-top: 28px; padding: 20px; overflow: hidden; background: rgba(255, 255, 255, 0.96); border-radius: 8px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12); }
        .preview-panel h2 { margin-top: 0; color: #243447; }
        .preview-status { color: #52606d; }
        .preview-scroll { overflow: auto; max-height: 620px; border: 1px solid #d9dee3; }
        .preview-scroll table { border-collapse: collapse; min-width: 100%; background: #fff; }
        .preview-scroll td, .preview-scroll th { min-width: 90px; padding: 7px 9px; border: 1px solid #cbd2d9; white-space: nowrap; font-size: .85rem; }
        .preview-scroll th { position: sticky; top: 0; background: #e8f0e8; }
        body.dark-mode .plantillas-container h1 { color: #fff; }
        body.dark-mode .plantillas-intro,
        body.dark-mode .plantilla-card p,
        body.dark-mode .plantilla-meta,
        body.dark-mode .preview-status { color: rgba(255, 255, 255, 0.72); }
        body.dark-mode .plantilla-card,
        body.dark-mode .preview-panel { background: rgba(255, 255, 255, 0.08); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); }
        body.dark-mode .plantilla-card h2,
        body.dark-mode .preview-panel h2 { color: #fff; }
        body.dark-mode .preview-scroll { border-color: rgba(255, 255, 255, 0.18); }
        body.dark-mode .preview-scroll table { background: #17173f; color: rgba(255, 255, 255, 0.9); }
        body.dark-mode .preview-scroll td { border-color: rgba(255, 255, 255, 0.18); color: rgba(255, 255, 255, 0.86); }
        body.dark-mode .preview-scroll th { background: #254d45; color: #fff; border-color: rgba(255, 255, 255, 0.22); }
        @media (max-width: 600px) { .plantillas-container { padding: 20px 14px; } }
    </style>
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

    <main class="plantillas-container">
        <h1>Plantillas Excel</h1>
        <p class="plantillas-intro">Accede a las cuatro planillas institucionales conservando su formato original y todas sus fórmulas.</p>

        <div class="plantillas-grid">
            <?php foreach ($plantillas as $plantilla): ?>
                <article class="plantilla-card">
                    <div>
                        <i class="fas fa-file-excel plantilla-icon" aria-hidden="true"></i>
                        <h2><?php echo htmlspecialchars($plantilla['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars($plantilla['descripcion']); ?></p>
                    </div>
                    <?php if ($plantilla['disponible']): ?>
                        <div>
                            <div class="plantilla-meta"><?php echo htmlspecialchars($plantilla['archivo']); ?> · <?php echo $plantilla['tamano']; ?></div>
                            <div class="plantilla-actions">
                                <button class="plantilla-preview-button" type="button" data-url="/centralizador_notas/plantillas_centralizador/<?php echo rawurlencode($plantilla['archivo']); ?>" data-title="<?php echo htmlspecialchars($plantilla['titulo'], ENT_QUOTES, 'UTF-8'); ?>" onclick="cargarPlantilla(this)"><i class="fas fa-eye"></i> Ver aquí</button>
                                <a class="plantilla-open" href="/centralizador_notas/plantillas_centralizador/<?php echo rawurlencode($plantilla['archivo']); ?>" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i> Abrir</a>
                                <a class="plantilla-download" href="/centralizador_notas/plantillas_centralizador/<?php echo rawurlencode($plantilla['archivo']); ?>" download><i class="fas fa-download"></i> Descargar</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="plantilla-unavailable">Archivo no disponible en el servidor.</div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <section class="preview-panel" aria-live="polite">
            <h2 id="preview-title">Previsualización</h2>
            <p id="preview-status" class="preview-status">Selecciona “Ver aquí” para visualizar una planilla dentro del administrador.</p>
            <div id="preview-content" class="preview-scroll" hidden></div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        async function cargarPlantilla(boton) {
            const status = document.getElementById('preview-status');
            const titulo = document.getElementById('preview-title');
            const contenido = document.getElementById('preview-content');
            titulo.textContent = 'Previsualización: ' + boton.dataset.title;
            status.textContent = 'Cargando planilla...';
            contenido.hidden = true;
            contenido.innerHTML = '';

            try {
                if (typeof XLSX === 'undefined') {
                    throw new Error('El visor de Excel no esta disponible.');
                }
                const respuesta = await fetch(boton.dataset.url);
                if (!respuesta.ok) throw new Error('No se pudo leer el archivo.');
                const libro = XLSX.read(await respuesta.arrayBuffer(), { type: 'array', cellFormula: true });
                const primeraHoja = libro.Sheets[libro.SheetNames[0]];
                contenido.innerHTML = XLSX.utils.sheet_to_html(primeraHoja, { editable: false });
                contenido.hidden = false;
                status.textContent = 'Hoja mostrada: ' + libro.SheetNames[0] + '. Descarga el archivo original para editarlo con todas sus fórmulas y formato.';
            } catch (error) {
                status.textContent = 'No se pudo cargar la previsualización. Usa “Descargar” para abrir el archivo en Excel.';
            }
        }
    </script>
    <script src="/centralizador_notas/js/fondo.js"></script>
</body>
</html>