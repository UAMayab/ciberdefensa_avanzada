<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

$riesgos = riesgos_todos();
$c = conteos();
$g = nivel_global($riesgos);
$org = q1('SELECT * FROM organizacion WHERE id = 1');
$niv = niveles();
$tol = (int) $org['tolerancia_nivel'];

// Distribución de riesgos vigentes por nivel, tratamiento y estado.
$por_nivel = array_fill(1, 5, 0);
$por_trat = [];
$por_estado = [];
$sin_plan = 0;
foreach ($riesgos as $r) {
    $por_estado[$r['estado']] = ($por_estado[$r['estado']] ?? 0) + 1;
    if ((int) $r['vigente'] === 1) {
        $por_nivel[(int) $r['nivel']]++;
        $por_trat[$r['tratamiento']] = ($por_trat[$r['tratamiento']] ?? 0) + 1;
        if ((int) $r['nivel'] > $tol && (int) $r['estado_id'] === 1) {
            $sin_plan++;
        }
    }
}
$vigentes = array_sum($por_nivel);

layout_inicio('Inicio', 'inicio');
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <h1 class="h3 mb-0">Estado de riesgo de la organización</h1>
    <div class="text-body-secondary"><?= $org['nombre'] !== '' ? h($org['nombre']) . ($org['sector'] !== '' ? ' · ' . h($org['sector']) : '') : 'Aún no has definido la organización' ?></div>
  </div>
  <div class="text-body-secondary small"><i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i') ?></div>
</div>

<?php if ($c['activos'] === 0): ?>
<div class="card border-primary mb-4">
  <div class="card-body">
    <h2 class="h5"><i class="bi bi-signpost-split text-primary"></i> Primeros pasos</h2>
    <p class="mb-3">Este sistema te guía por el proceso de evaluación de riesgos de NIST SP 800-30. Sigue el orden de las pestañas:</p>
    <ol class="steps">
      <li><a href="configuracion.php">Configuración</a>: nombre de la organización, objetivo de negocio, contexto y tolerancia al riesgo.</li>
      <li><a href="activos.php">Activos</a>: inventario de lo que hay que proteger.</li>
      <li><a href="vulnerabilidades.php">Vulnerabilidades</a>: las debilidades de cada activo, con su puntaje CVSS.</li>
      <li><a href="amenazas.php">Amenazas</a>: quién o qué podría aprovechar cada debilidad.</li>
      <li><a href="riesgos.php">Riesgos</a>: impacto, probabilidad, tratamiento y justificación.</li>
      <li><a href="reporte.php">Reporte</a>: la vista que te sirve para escribir tu resumen ejecutivo.</li>
    </ol>
    <form method="post" action="configuracion.php" class="d-flex flex-wrap align-items-center gap-2">
      <?= csrf_field() ?><input type="hidden" name="accion" value="ejemplo">
      <button class="btn btn-outline-primary btn-sm"><i class="bi bi-magic"></i> Cargar un ejemplo para ver cómo funciona</button>
      <span class="text-body-secondary small">Es una empresa ficticia; puedes vaciarla después en Configuración.</span>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="card h-100 global-card">
      <div class="card-body d-flex flex-column">
        <div class="text-uppercase small text-body-secondary mb-2">Nivel de riesgo global
          <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="El mayor entre el promedio de los niveles de los riesgos vigentes y el nivel máximo menos uno. Un riesgo Cerrado no cuenta."></i></div>
        <?php if ($g): $n = $niv[$g['nivel']]; ?>
          <div class="global-level" style="background:<?= h($n['color']) ?>;color:<?= h($n['color_texto']) ?>"><?= h($n['nombre']) ?></div>
          <div class="scale mt-3" aria-hidden="true">
            <?php foreach ($niv as $id => $x): ?>
              <span class="scale-seg <?= $id === $g['nivel'] ? 'on' : '' ?>" style="background:<?= h($x['color']) ?>"></span>
            <?php endforeach; ?>
          </div>
          <div class="scale-labels"><span>Muy bajo</span><span>Muy alto</span></div>
          <p class="small text-body-secondary mt-3 mb-0">
            Calculado sobre <b><?= $g['n'] ?></b> riesgo<?= $g['n'] === 1 ? '' : 's' ?> vigente<?= $g['n'] === 1 ? '' : 's' ?>.
            Nivel máximo: <b><?= h($niv[$g['maximo']]['nombre']) ?></b>. Tolerancia de la organización: <b><?= h($niv[$tol]['nombre']) ?></b>.
          </p>
          <?php if ($sin_plan > 0): ?>
            <div class="alert alert-danger py-2 small mt-3 mb-0"><i class="bi bi-exclamation-octagon"></i>
              <b><?= $sin_plan ?></b> riesgo<?= $sin_plan === 1 ? '' : 's' ?> por encima de la tolerancia sin plan (estado Abierto).</div>
          <?php endif; ?>
        <?php else: ?>
          <div class="global-level bg-secondary-subtle text-body-secondary">Sin evaluar</div>
          <p class="small text-body-secondary mt-3 mb-0">El nivel global aparecerá cuando evalúes tu primer riesgo en la pestaña <a href="riesgos.php">Riesgos</a>.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="row g-3 h-100">
      <?php foreach ([
          ['activos.php', 'bi-hdd-network', 'Activos identificados', $c['activos'], 'primary'],
          ['vulnerabilidades.php', 'bi-bug', 'Vulnerabilidades', $c['vulnerabilidades'], 'warning'],
          ['amenazas.php', 'bi-lightning-charge', 'Amenazas', $c['amenazas'], 'info'],
          ['riesgos.php', 'bi-exclamation-triangle', 'Riesgos', $c['riesgos'], 'danger'],
      ] as [$url, $icono, $etq, $n, $color]): ?>
        <div class="col-6">
          <a class="card stat-card h-100 text-decoration-none" href="<?= $url ?>">
            <div class="card-body d-flex align-items-center gap-3">
              <span class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><i class="bi <?= $icono ?>"></i></span>
              <span><span class="stat-num"><?= $n ?></span><span class="d-block text-body-secondary"><?= $etq ?></span></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-xl-7">
    <div class="card h-100">
      <div class="card-header"><strong><i class="bi bi-grid-3x3-gap"></i> Mapa de calor · impacto × probabilidad</strong></div>
      <div class="card-body"><?= render_heatmap($riesgos) ?></div>
    </div>
  </div>
  <div class="col-xl-5 d-flex flex-column gap-3">
    <div class="card">
      <div class="card-header"><strong><i class="bi bi-bar-chart"></i> Riesgos vigentes por nivel</strong></div>
      <div class="card-body">
        <?php if ($vigentes === 0): ?>
          <p class="text-body-secondary mb-0">Todavía no hay riesgos evaluados.</p>
        <?php else: ?>
          <div class="progress stacked-bar mb-3" role="img" aria-label="Distribución por nivel">
            <?php foreach (array_reverse($por_nivel, true) as $id => $k): if ($k > 0): ?>
              <div class="progress-bar" style="width:<?= $k / $vigentes * 100 ?>%;background:<?= h($niv[$id]['color']) ?>;color:<?= h($niv[$id]['color_texto']) ?>"><?= $k ?></div>
            <?php endif; endforeach; ?>
          </div>
          <?php foreach (array_reverse($por_nivel, true) as $id => $k): ?>
            <div class="d-flex justify-content-between small py-1 border-bottom">
              <span><span class="dot" style="background:<?= h($niv[$id]['color']) ?>"></span> <?= h($niv[$id]['nombre']) ?></span><b><?= $k ?></b>
            </div>
          <?php endforeach; ?>
          <div class="row mt-3 small">
            <div class="col-6"><div class="text-uppercase text-body-secondary mb-1">Tratamiento</div>
              <?php foreach ($por_trat as $t => $k): ?><div class="d-flex justify-content-between"><span><?= h($t) ?></span><b><?= $k ?></b></div><?php endforeach; ?></div>
            <div class="col-6"><div class="text-uppercase text-body-secondary mb-1">Estado</div>
              <?php foreach ($por_estado as $t => $k): ?><div class="d-flex justify-content-between"><span><?= h($t) ?></span><b><?= $k ?></b></div><?php endforeach; ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><strong><i class="bi bi-list-check"></i> Pendientes del análisis</strong></div>
      <ul class="list-group list-group-flush">
        <?php foreach ([
            ['activos.php', $c['activos_sin_vuln'], 'activo(s) sin vulnerabilidades registradas', 'vulnerabilidades.php'],
            ['vulnerabilidades.php', $c['vuln_sin_amenaza'], 'vulnerabilidad(es) sin amenazas', 'amenazas.php?pendientes=1'],
            ['amenazas.php', $c['amenazas_sin_riesgo'], 'amenaza(s) sin evaluar como riesgo', 'riesgos.php'],
        ] as [, $k, $txt, $ir]): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= $k === 0 ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-warning"></i>' ?> <?= $k ?> <?= $txt ?></span>
            <?php if ($k > 0): ?><a class="btn btn-sm btn-outline-primary" href="<?= $ir ?>">Ir</a><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?= render_ayuda('inicio') ?>
<?php layout_fin(); ?>
