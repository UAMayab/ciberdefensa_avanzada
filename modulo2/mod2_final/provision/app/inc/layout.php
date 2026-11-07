<?php
// Cabecera y pie comunes: barra de navegación con las cinco pestañas + inicio + configuración.
declare(strict_types=1);

require_once __DIR__ . '/riesgo.php';
require_once __DIR__ . '/ayuda.php';

function layout_inicio(string $titulo, string $activa, string $clase_body = ''): void
{
    $org = q1('SELECT nombre FROM organizacion WHERE id = 1');
    $nombre_org = $org['nombre'] !== '' ? $org['nombre'] : 'Organización sin nombre';
    $c = conteos();

    $tabs = [
        'inicio'           => ['index.php',            'bi-speedometer2',       'Inicio',           null],
        'activos'          => ['activos.php',          'bi-hdd-network',        'Activos',          $c['activos']],
        'vulnerabilidades' => ['vulnerabilidades.php', 'bi-bug',                'Vulnerabilidades', $c['vulnerabilidades']],
        'amenazas'         => ['amenazas.php',         'bi-lightning-charge',   'Amenazas',         $c['amenazas']],
        'riesgos'          => ['riesgos.php',          'bi-exclamation-triangle', 'Riesgos',        $c['riesgos']],
        'reporte'          => ['reporte.php',          'bi-file-earmark-text',  'Reporte',          null],
    ];
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($titulo) ?> · Gestión de Riesgo</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/vendor/icons/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/app.css">
</head>
<body class="<?= h($clase_body) ?>">
<nav class="navbar navbar-expand-lg navbar-dark app-nav sticky-top">
  <div class="container-xl">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <i class="bi bi-shield-check"></i>
      <span class="d-flex flex-column lh-1"><span class="brand-main">Gobernanza y Gestión de Riesgo</span><small class="brand-sub"><?= h($nombre_org) ?></small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto gap-lg-1">
        <?php foreach ($tabs as $k => [$url, $icono, $etq, $n]): ?>
          <li class="nav-item">
            <a class="nav-link <?= $k === $activa ? 'active' : '' ?>" href="<?= $url ?>" <?= $k === $activa ? 'aria-current="page"' : '' ?>>
              <i class="bi <?= $icono ?>"></i> <?= $etq ?><?php if ($n !== null): ?> <span class="badge rounded-pill nav-count"><?= $n ?></span><?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
        <li class="nav-item">
          <a class="nav-link <?= $activa === 'configuracion' ? 'active' : '' ?>" href="configuracion.php" title="Configuración y respaldo"><i class="bi bi-gear"></i><span class="d-lg-none"> Configuración</span></a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<main class="container-xl py-4">
<?= flash_render() ?>
<?php
}

function layout_fin(array $extra_json = []): void
{
    $datos = ['matriz' => matriz(), 'niveles' => niveles()] + $extra_json;
    ?>
</main>
<footer class="container-xl pb-4 text-body-secondary small d-print-none">
  Metodología: NIST SP 800-30 Rev. 1 · Laboratorio académico, Módulo II · Ciberdefensa Avanzada
</footer>
<script id="datos-app" type="application/json"><?= json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}

/** Modal genérico de confirmación de borrado (lo reutilizan todas las pestañas). */
function modal_borrar(): string
{
    return '<div class="modal fade" id="modalBorrar" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="post" class="modal-content">'
        . csrf_field()
        . '<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" id="borrar-id">'
        . '<div class="modal-header"><h5 class="modal-title"><i class="bi bi-trash text-danger"></i> Eliminar <span id="borrar-que"></span></h5>'
        . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>'
        . '<div class="modal-body"><p class="mb-1">Vas a eliminar <b id="borrar-nombre"></b>.</p><p class="mb-0 text-danger" id="borrar-cascada"></p></div>'
        . '<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>'
        . '<button class="btn btn-danger">Eliminar</button></div></form></div></div>';
}
