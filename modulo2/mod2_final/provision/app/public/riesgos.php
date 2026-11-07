<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (post_str('accion') === 'eliminar') {
            db()->prepare('DELETE FROM riesgos WHERE id = ?')->execute([(int) post_str('id')]);
            flash('success', 'Riesgo eliminado. La amenaza queda de nuevo «sin evaluar».');
        } else {
            $err = faltantes(['amenaza_id' => 'Amenaza', 'impacto' => 'Impacto', 'probabilidad' => 'Probabilidad', 'tratamiento_id' => 'Tratamiento',
                'justificacion' => 'Justificación del tratamiento', 'estado_id' => 'Estado']);
            $f = post_str('fecha_objetivo');
            if (!$err && $f !== '' && !fecha_valida($f)) $err = 'La fecha objetivo no es válida.';
            if (!$err && !in_array((int) post_str('impacto'), range(1, 5), true)) $err = 'Impacto fuera de rango (1-5).';
            if (!$err && !in_array((int) post_str('probabilidad'), range(1, 5), true)) $err = 'Probabilidad fuera de rango (1-5).';
            if ($err) {
                flash('danger', $err);
            } else {
                $vals = [(int) post_str('impacto'), (int) post_str('probabilidad'), (int) post_str('tratamiento_id'), post_str('justificacion'),
                    (int) post_str('estado_id'), post_str('responsable'), $f !== '' ? $f : null, post_str('control_propuesto') ?: null];
                $id = (int) post_str('id');
                if ($id > 0) {
                    db()->prepare('UPDATE riesgos SET impacto=?, probabilidad=?, tratamiento_id=?, justificacion=?, estado_id=?, responsable=?,
                        fecha_objetivo=?, control_propuesto=? WHERE id=?')->execute([...$vals, $id]);
                    flash('success', 'Riesgo ' . codigo('R', $id) . ' actualizado.');
                } else {
                    db()->prepare('INSERT INTO riesgos (impacto, probabilidad, tratamiento_id, justificacion, estado_id, responsable, fecha_objetivo,
                        control_propuesto, amenaza_id) VALUES (?,?,?,?,?,?,?,?,?)')->execute([...$vals, (int) post_str('amenaza_id')]);
                    flash('success', 'Riesgo ' . codigo('R', (int) db()->lastInsertId()) . ' registrado; ya aparece en el mapa de calor del Inicio.');
                }
            }
        }
    } catch (PDOException $e) {
        flash('danger', 'No se pudo guardar el riesgo (¿esa amenaza ya tiene un riesgo evaluado?).');
    }
    redirect('riesgos.php');
}

$org = q1('SELECT tolerancia_nivel FROM organizacion WHERE id = 1');
$riesgos = riesgos_todos();
$pendientes = q('SELECT m.id, m.nombre, f.nombre AS fuente, v.id AS vid, v.descripcion AS vuln, v.cvss, a.id AS aid, a.nombre AS activo
    FROM amenazas m JOIN cat_fuente_amenaza f ON f.id = m.fuente_id JOIN vulnerabilidades v ON v.id = m.vulnerabilidad_id JOIN activos a ON a.id = v.activo_id
    WHERE NOT EXISTS (SELECT 1 FROM riesgos r WHERE r.amenaza_id = m.id) ORDER BY v.cvss DESC, m.id');
$tratamientos = q('SELECT * FROM cat_tratamiento ORDER BY id');
$estados = q('SELECT * FROM cat_estado_riesgo ORDER BY id');
$imp = escala('impacto');
$prob = escala('probabilidad');
$niv = niveles();

layout_inicio('Riesgos', 'riesgos');
echo render_ayuda('riesgos');
?>
<h1 class="h3 mb-3"><i class="bi bi-exclamation-triangle"></i> Riesgos</h1>

<?php if ($pendientes): ?>
<div class="card border-warning mb-4">
  <div class="card-header bg-warning-subtle"><strong><i class="bi bi-hourglass-split"></i> Amenazas por evaluar (<?= count($pendientes) ?>)</strong>
    <span class="text-body-secondary small ms-2">Cada una es una tupla Activo-Vulnerabilidad-Amenaza que aún no tiene riesgo.</span></div>
  <ul class="list-group list-group-flush">
  <?php foreach ($pendientes as $p):
      $ctx = ['activo' => codigo('A', $p['aid']) . ' · ' . $p['activo'], 'vuln' => codigo('V', $p['vid']) . ' · ' . $p['vuln'],
              'amenaza' => codigo('T', $p['id']) . ' · ' . $p['nombre'] . ' (' . $p['fuente'] . ')']; ?>
    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
      <span class="codigo"><?= codigo('T', $p['id']) ?></span>
      <span class="flex-fill-min"><b><?= h($p['nombre']) ?></b>
        <span class="small text-body-secondary d-block"><?= h($p['activo']) ?> ⟷ <?= h($p['vuln']) ?></span></span>
      <?= badge_cvss($p['cvss']) ?>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalRiesgo" data-amenaza="<?= $p['id'] ?>" data-contexto="<?= h(json_encode($ctx, JSON_UNESCAPED_UNICODE)) ?>">
        <i class="bi bi-speedometer"></i> Evaluar</button>
    </li>
  <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if (!$riesgos): ?>
  <div class="empty-state"><i class="bi bi-exclamation-triangle"></i><p><?= $pendientes ? 'Evalúa tu primera amenaza con el botón «Evaluar».' : 'Aún no hay amenazas que evaluar. Registra primero las <a href="amenazas.php">amenazas</a>.' ?></p></div>
<?php else: ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <input type="search" class="form-control" style="width:16rem" placeholder="Buscar…" data-filtro-texto="#tabla-riesgos">
  <select class="form-select" style="width:12rem" data-filtro-col="nivel" data-filtro-tabla="#tabla-riesgos"><option value="">Todos los niveles</option>
    <?php foreach (array_reverse($niv, true) as $n): ?><option><?= h($n['nombre']) ?></option><?php endforeach; ?></select>
  <select class="form-select" style="width:12rem" data-filtro-col="estado" data-filtro-tabla="#tabla-riesgos"><option value="">Todos los estados</option>
    <?php foreach ($estados as $e): ?><option><?= h($e['nombre']) ?></option><?php endforeach; ?></select>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0" id="tabla-riesgos">
  <thead class="table-light"><tr><th>Riesgo</th><th>Activo · Vulnerabilidad · Amenaza</th><th class="text-center">I</th><th class="text-center">P</th><th>Nivel</th><th>Tratamiento</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($riesgos as $r):
      $ctx = ['activo' => codigo('A', $r['activo_id']) . ' · ' . $r['activo'], 'vuln' => codigo('V', $r['vulnerabilidad_id']) . ' · ' . $r['vulnerabilidad'],
              'amenaza' => codigo('T', $r['amenaza_id']) . ' · ' . $r['amenaza'] . ' (' . $r['fuente'] . ')']; ?>
    <tr data-nivel="<?= h($r['nivel_nombre']) ?>" data-estado="<?= h($r['estado']) ?>" data-riesgo-id="<?= $r['id'] ?>">
      <td><span class="codigo"><?= codigo('R', $r['id']) ?></span></td>
      <td><b><?= h($r['amenaza']) ?></b><div class="small text-body-secondary"><?= h($r['activo']) ?> ⟷ <?= h(mb_strimwidth($r['vulnerabilidad'], 0, 90, '…')) ?></div></td>
      <td class="text-center"><?= $r['impacto'] ?></td><td class="text-center"><?= $r['probabilidad'] ?></td>
      <td><?= badge_nivel((int) $r['nivel']) ?></td>
      <td><?= h($r['tratamiento']) ?><div class="small text-body-secondary celda-desc" title="<?= h($r['justificacion']) ?>"><?= h($r['justificacion']) ?></div></td>
      <td><span class="risk-chip st-<?= $r['estado_id'] ?>"><i class="bi <?= icono_estado((int) $r['estado_id']) ?>"></i> <?= h($r['estado']) ?></span></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRiesgo" data-contexto="<?= h(json_encode($ctx, JSON_UNESCAPED_UNICODE)) ?>"
          data-edit="<?= h(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBorrar" data-borrar-id="<?= $r['id'] ?>" data-borrar-que="riesgo"
          data-borrar-nombre="<?= h(codigo('R', $r['id']) . ' · ' . $r['amenaza']) ?>" data-borrar-cascada="" title="Eliminar"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>

<div class="modal fade" id="modalRiesgo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form method="post" class="modal-content" data-form-modal>
      <?= csrf_field() ?><input type="hidden" name="id" value=""><input type="hidden" name="amenaza_id" value="">
      <div class="modal-header">
        <h5 class="modal-title" data-titulo-nuevo="Evaluar riesgo" data-titulo-editar="Editar riesgo"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-secondary small py-2">
          <div><b>Activo:</b> <span id="ctx-activo"></span></div>
          <div><b>Vulnerabilidad:</b> <span id="ctx-vuln"></span></div>
          <div><b>Amenaza:</b> <span id="ctx-amenaza"></span></div>
        </div>
        <div class="row g-3">
          <div class="col-lg-4">
            <h6 class="text-uppercase small text-body-secondary">1 · Evaluación</h6>
            <label class="form-label">Probabilidad * <span class="text-body-secondary">(que ocurra y cause daño)</span></label>
            <select class="form-select" name="probabilidad" required data-escala><option value="">Selecciona…</option>
              <?php foreach ($prob as $v => $e): ?><option value="<?= $v ?>" data-desc="<?= h($e['descripcion']) ?>"><?= $v ?> · <?= h($e['nombre']) ?></option><?php endforeach; ?></select>
            <div class="form-text mb-3" data-escala-desc="probabilidad">Elige un valor para ver su significado.</div>
            <label class="form-label">Impacto * <span class="text-body-secondary">(si ocurre)</span></label>
            <select class="form-select" name="impacto" required data-escala><option value="">Selecciona…</option>
              <?php foreach ($imp as $v => $e): ?><option value="<?= $v ?>" data-desc="<?= h($e['descripcion']) ?>"><?= $v ?> · <?= h($e['nombre']) ?></option><?php endforeach; ?></select>
            <div class="form-text mb-3" data-escala-desc="impacto">Elige un valor para ver su significado.</div>
            <div class="nivel-vivo" id="nivel-vivo"><span class="text-body-secondary small">El nivel de riesgo aparece al elegir probabilidad e impacto.</span></div>
          </div>
          <div class="col-lg-8">
            <h6 class="text-uppercase small text-body-secondary">2 · Tratamiento (NIST SP 800-30 / 800-39)</h6>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Tratamiento *</label>
                <select class="form-select" name="tratamiento_id" required><option value="">Selecciona…</option>
                  <?php foreach ($tratamientos as $t): ?><option value="<?= $t['id'] ?>" data-desc="<?= h($t['descripcion']) ?>"><?= h($t['nombre']) ?></option><?php endforeach; ?></select>
                <div class="form-text" id="trat-desc">Elige una respuesta para ver su definición.</div></div>
              <div class="col-md-6"><label class="form-label">Estado *</label>
                <select class="form-select" name="estado_id" required>
                  <?php foreach ($estados as $e): ?><option value="<?= $e['id'] ?>" data-desc="<?= h($e['descripcion']) ?>"><?= h($e['nombre']) ?></option><?php endforeach; ?></select>
                <div class="form-text" id="estado-desc"></div></div>
              <div class="col-12"><div id="sugerencia-trat" class="alert alert-info py-2 small mb-0 d-none"></div></div>
              <div class="col-12"><label class="form-label">Justificación de la decisión *</label>
                <textarea class="form-control" name="justificacion" rows="3" required placeholder="¿Por qué este tratamiento y no otro? Considera el costo del control frente al daño, la tolerancia de la organización y la evidencia de la probabilidad."></textarea></div>
              <div class="col-12"><hr class="my-1"><div class="small text-body-secondary"><i class="bi bi-stars"></i> Campos sugeridos (plan de tratamiento)</div></div>
              <div class="col-md-6"><label class="form-label">Responsable del tratamiento</label>
                <input class="form-control" name="responsable" maxlength="150" placeholder="Nombre y puesto"></div>
              <div class="col-md-6"><label class="form-label">Fecha objetivo</label>
                <input type="date" class="form-control" name="fecha_objetivo"></div>
              <div class="col-12"><label class="form-label">Control o acción propuesta</label>
                <textarea class="form-control" name="control_propuesto" rows="2" placeholder="Ej. Aplicar parches mensuales, segmentar la red, contratar un seguro cibernético…"></textarea></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar riesgo</button></div>
    </form>
  </div>
</div>
<?= modal_borrar() ?>
<?php layout_fin(['tolerancia' => (int) $org['tolerancia_nivel'], 'abrir_riesgo' => (int) ($_GET['ver'] ?? 0)]); ?>
