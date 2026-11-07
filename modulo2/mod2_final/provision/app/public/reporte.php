<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

$riesgos = riesgos_todos();
$org = q1('SELECT * FROM organizacion WHERE id = 1');
$tol = (int) $org['tolerancia_nivel'];
$niv = niveles();

// Exportación CSV (UTF-8 con BOM para que Excel respete los acentos).
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte-riesgos-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    // Neutraliza fórmulas: Excel interpretaría como fórmula una celda que empiece con = + - @
    $seguro = fn($v) => is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false ? "'" . $v : $v;
    fputcsv($out, ['Riesgo', 'Activo', 'Vulnerabilidad', 'CVSS', 'Amenaza', 'Fuente', 'Impacto', 'Probabilidad', 'Nivel', 'Tratamiento',
        'Justificación', 'Estado', 'Responsable', 'Fecha objetivo', 'Control propuesto'], ',', '"', '');
    foreach ($riesgos as $r) {
        fputcsv($out, array_map($seguro, [codigo('R', $r['id']), codigo('A', $r['activo_id']) . ' ' . $r['activo'], codigo('V', $r['vulnerabilidad_id']) . ' ' . $r['vulnerabilidad'],
            $r['cvss'], codigo('T', $r['amenaza_id']) . ' ' . $r['amenaza'], $r['fuente'], $r['impacto'], $r['probabilidad'], $r['nivel_nombre'], $r['tratamiento'],
            $r['justificacion'], $r['estado'], $r['responsable'], $r['fecha_objetivo'] ?? '', $r['control_propuesto'] ?? '']), ',', '"', '');
    }
    exit;
}

$c = conteos();
$g = nivel_global($riesgos);
$vigentes = array_values(array_filter($riesgos, fn($r) => (int) $r['vigente'] === 1));
$altos = array_values(array_filter($vigentes, fn($r) => (int) $r['nivel'] >= 4));
$aceptados_fuera = array_values(array_filter($vigentes, fn($r) => (int) $r['nivel'] > $tol && (int) $r['tratamiento_id'] === 2));
$abiertos_fuera = array_values(array_filter($vigentes, fn($r) => (int) $r['nivel'] > $tol && (int) $r['estado_id'] === 1));
$por_nivel = array_fill(1, 5, 0);
foreach ($vigentes as $r) $por_nivel[(int) $r['nivel']]++;

layout_inicio('Reporte', 'reporte');
echo '<div class="d-print-none">' . render_ayuda('reporte') . '</div>';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 d-print-none">
  <h1 class="h3 mb-0"><i class="bi bi-file-earmark-text"></i> Reporte de riesgos</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="reporte.php?csv=1"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir / PDF</button>
  </div>
</div>

<article class="reporte">
  <header class="reporte-head">
    <div class="small text-uppercase text-body-secondary">Evaluación de riesgos · NIST SP 800-30 Rev. 1</div>
    <h2 class="h3 mb-1"><?= $org['nombre'] !== '' ? h($org['nombre']) : 'Organización sin nombre' ?></h2>
    <div class="text-body-secondary"><?= h($org['sector']) ?><?= $org['evaluador'] !== '' ? ' · Evaluó: ' . h($org['evaluador']) : '' ?> · <?= date('d/m/Y') ?></div>
  </header>

  <?php if ($org['objetivo_negocio'] || $org['contexto'] || $org['alcance']): ?>
  <section class="row g-3 mb-4">
    <?php foreach (['objetivo_negocio' => 'Objetivo de negocio', 'contexto' => 'Contexto', 'alcance' => 'Alcance'] as $k => $etq): if ($org[$k]): ?>
      <div class="col-md-4"><div class="small text-uppercase text-body-secondary"><?= $etq ?></div><div><?= nl2br(h($org[$k])) ?></div></div>
    <?php endif; endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if (!$riesgos): ?>
    <div class="empty-state"><i class="bi bi-file-earmark-text"></i><p>Todavía no hay riesgos que reportar. Evalúa tus amenazas en la pestaña <a href="riesgos.php">Riesgos</a>.</p></div>
  <?php else: ?>
  <section class="row g-3 mb-4 align-items-stretch">
    <div class="col-md-4"><div class="card h-100"><div class="card-body text-center">
      <div class="small text-uppercase text-body-secondary mb-2">Nivel de riesgo global</div>
      <?php if ($g): ?><div class="global-level" style="background:<?= h($niv[$g['nivel']]['color']) ?>;color:<?= h($niv[$g['nivel']]['color_texto']) ?>"><?= h($niv[$g['nivel']]['nombre']) ?></div>
      <?php else: ?><div class="global-level bg-secondary-subtle text-body-secondary">Sin riesgos vigentes</div><?php endif; ?>
      <div class="small text-body-secondary mt-2">Tolerancia de la organización: <b><?= h($niv[$tol]['nombre']) ?></b></div>
    </div></div></div>
    <div class="col-md-8"><div class="card h-100"><div class="card-body">
      <div class="row text-center">
        <?php foreach ([['Activos', $c['activos']], ['Vulnerabilidades', $c['vulnerabilidades']], ['Amenazas', $c['amenazas']], ['Riesgos', $c['riesgos']]] as [$etq, $n]): ?>
          <div class="col-3"><div class="stat-num"><?= $n ?></div><div class="small text-body-secondary"><?= $etq ?></div></div>
        <?php endforeach; ?>
      </div>
      <hr>
      <div class="d-flex flex-wrap gap-3 justify-content-center">
        <?php foreach (array_reverse($por_nivel, true) as $id => $k): ?>
          <span><span class="dot" style="background:<?= h($niv[$id]['color']) ?>"></span> <?= h($niv[$id]['nombre']) ?>: <b><?= $k ?></b></span>
        <?php endforeach; ?>
      </div>
    </div></div></div>
  </section>

  <section class="mb-4">
    <h3 class="h5">Lectura rápida</h3>
    <ul class="mb-0">
      <li>Se identificaron <b><?= $c['activos'] ?></b> activos, <b><?= $c['vulnerabilidades'] ?></b> vulnerabilidades y <b><?= $c['amenazas'] ?></b> amenazas, que dieron lugar a <b><?= $c['riesgos'] ?></b> riesgos evaluados.</li>
      <?php if ($g): ?><li>El nivel de riesgo global es <b><?= h($niv[$g['nivel']]['nombre']) ?></b>; el riesgo vigente más alto es <b><?= h($niv[$g['maximo']]['nombre']) ?></b>.</li><?php endif; ?>
      <?php if ($altos): ?><li><b><?= count($altos) ?></b> riesgo(s) en nivel Alto o Muy alto: <?= h(implode('; ', array_map(fn($r) => codigo('R', $r['id']) . ' ' . $r['amenaza'], array_slice($altos, 0, 4)))) ?><?= count($altos) > 4 ? '…' : '' ?>.</li><?php endif; ?>
      <?php if ($aceptados_fuera): ?><li class="text-danger"><i class="bi bi-exclamation-triangle"></i> <b><?= count($aceptados_fuera) ?></b> riesgo(s) <b>aceptado(s) por encima de la tolerancia</b>: <?= h(implode(', ', array_map(fn($r) => codigo('R', $r['id']), $aceptados_fuera))) ?>. Una aceptación así necesita la autorización de la dirección.</li><?php endif; ?>
      <?php if ($abiertos_fuera): ?><li class="text-danger"><i class="bi bi-exclamation-triangle"></i> <b><?= count($abiertos_fuera) ?></b> riesgo(s) por encima de la tolerancia siguen <b>Abiertos</b>, sin plan de tratamiento: <?= h(implode(', ', array_map(fn($r) => codigo('R', $r['id']), $abiertos_fuera))) ?>.</li><?php endif; ?>
      <?php if ($c['amenazas_sin_riesgo'] > 0): ?><li class="text-body-secondary"><?= $c['amenazas_sin_riesgo'] ?> amenaza(s) aún sin evaluar: el análisis no está completo.</li><?php endif; ?>
    </ul>
  </section>

  <section class="mb-4 salto-evitar">
    <h3 class="h5">Mapa de calor</h3>
    <?= render_heatmap($riesgos, false) ?>
  </section>

  <section>
    <h3 class="h5">Registro de riesgos <span class="text-body-secondary fs-6">(de mayor a menor nivel)</span></h3>
    <div class="table-responsive">
    <table class="table table-sm align-middle tabla-reporte">
      <thead class="table-light"><tr><th>Riesgo</th><th>Qué puede pasar</th><th class="text-center">I × P</th><th>Nivel</th><th>Tratamiento y justificación</th><th>Estado</th></tr></thead>
      <tbody>
      <?php foreach ($riesgos as $r):
          $alerta = (int) $r['vigente'] === 1 && (int) $r['nivel'] > $tol && (int) $r['tratamiento_id'] === 2; ?>
        <tr style="--borde:<?= h($niv[(int) $r['nivel']]['color']) ?>">
          <td class="text-nowrap fila-nivel"><span class="codigo"><?= codigo('R', $r['id']) ?></span></td>
          <td><b><?= h($r['amenaza']) ?></b> <span class="badge text-bg-light border"><?= h($r['fuente']) ?></span>
              <div class="small">Activo: <?= h($r['activo']) ?></div>
              <div class="small text-body-secondary">Vulnerabilidad: <?= h($r['vulnerabilidad']) ?> (CVSS <?= number_format((float) $r['cvss'], 1) ?>)</div></td>
          <td class="text-center text-nowrap"><?= $r['impacto'] ?> × <?= $r['probabilidad'] ?></td>
          <td><?= badge_nivel((int) $r['nivel']) ?></td>
          <td><b><?= h($r['tratamiento']) ?></b><?= $alerta ? ' <span class="badge text-bg-danger" title="Aceptado por encima de la tolerancia">fuera de tolerancia</span>' : '' ?>
              <div class="small"><?= nl2br(h($r['justificacion'])) ?></div>
              <?php if ($r['control_propuesto']): ?><div class="small text-body-secondary">Control: <?= h($r['control_propuesto']) ?></div><?php endif; ?>
              <?php if ($r['responsable'] !== '' || $r['fecha_objetivo']): ?><div class="small text-body-secondary"><?= h($r['responsable']) ?><?= $r['fecha_objetivo'] ? ' · ' . fecha_es($r['fecha_objetivo']) : '' ?></div><?php endif; ?></td>
          <td><span class="risk-chip st-<?= $r['estado_id'] ?>"><i class="bi <?= icono_estado((int) $r['estado_id']) ?>"></i> <?= h($r['estado']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
  <?php endif; ?>
</article>
<?php layout_fin(); ?>
