<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $volver = 'vulnerabilidades.php';
    try {
        if (post_str('accion') === 'eliminar') {
            db()->prepare('DELETE FROM vulnerabilidades WHERE id = ?')->execute([(int) post_str('id')]);
            flash('success', 'Vulnerabilidad eliminada.');
        } else {
            $err = faltantes(['activo_id' => 'Activo', 'descripcion' => 'Descripción', 'cvss' => 'Valor CVSS']);
            $cvss = str_replace(',', '.', post_str('cvss'));
            if (!$err && (!is_numeric($cvss) || (float) $cvss < 0 || (float) $cvss > 10)) $err = 'El valor CVSS debe estar entre 0.0 y 10.0.';
            $cve = strtoupper(post_str('cve'));
            if (!$err && $cve !== '' && !preg_match('/^CVE-\d{4}-\d{4,7}$/', $cve)) $err = 'El CVE debe tener el formato CVE-2024-12345 (o déjalo vacío).';
            if ($err) {
                flash('danger', $err);
            } else {
                $vals = [(int) post_str('activo_id'), post_str('descripcion'), round((float) $cvss, 1), $cve, post_str('vector_cvss')];
                $id = (int) post_str('id');
                if ($id > 0) {
                    db()->prepare('UPDATE vulnerabilidades SET activo_id=?, descripcion=?, cvss=?, cve=?, vector_cvss=? WHERE id=?')->execute([...$vals, $id]);
                    flash('success', 'Vulnerabilidad ' . codigo('V', $id) . ' actualizada.');
                } else {
                    db()->prepare('INSERT INTO vulnerabilidades (activo_id, descripcion, cvss, cve, vector_cvss) VALUES (?,?,?,?,?)')->execute($vals);
                    flash('success', 'Vulnerabilidad ' . codigo('V', (int) db()->lastInsertId()) . ' registrada. Siguiente paso: sus amenazas.');
                }
                $volver .= '?activo=' . $vals[0];
            }
        }
    } catch (PDOException $e) {
        flash('danger', 'No se pudo guardar la vulnerabilidad: revisa los datos.');
    }
    redirect($volver);
}

$activos = q('SELECT id, nombre FROM activos ORDER BY id');
$filtro = (int) ($_GET['activo'] ?? 0);
$filas = q('SELECT v.*, a.nombre AS activo,
        (SELECT COUNT(*) FROM amenazas m WHERE m.vulnerabilidad_id = v.id) AS n_amen,
        (SELECT COUNT(*) FROM riesgos r JOIN amenazas m ON m.id = r.amenaza_id WHERE m.vulnerabilidad_id = v.id) AS n_riesgos
    FROM vulnerabilidades v JOIN activos a ON a.id = v.activo_id ' . ($filtro ? 'WHERE v.activo_id = ?' : '') . ' ORDER BY v.cvss DESC, v.id', $filtro ? [$filtro] : []);

layout_inicio('Vulnerabilidades', 'vulnerabilidades');
echo render_ayuda('vulnerabilidades');
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><i class="bi bi-bug"></i> Vulnerabilidades</h1>
  <div class="d-flex flex-wrap gap-2">
    <form method="get"><select class="form-select" name="activo" style="width:17rem" onchange="this.form.submit()">
      <option value="">Todos los activos</option>
      <?php foreach ($activos as $a): ?><option value="<?= $a['id'] ?>" <?= $filtro === (int) $a['id'] ? 'selected' : '' ?>><?= h(codigo('A', $a['id']) . ' · ' . $a['nombre']) ?></option><?php endforeach; ?>
    </select></form>
    <?php if ($activos): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVuln" data-activo="<?= $filtro ?: '' ?>"><i class="bi bi-plus-lg"></i> Nueva vulnerabilidad</button>
    <?php endif; ?>
  </div>
</div>

<?php if (!$activos): ?>
  <div class="empty-state"><i class="bi bi-hdd-network"></i><p>Primero registra al menos un activo en la pestaña <a href="activos.php">Activos</a>.</p></div>
<?php elseif (!$filas): ?>
  <div class="empty-state"><i class="bi bi-bug"></i><p>Sin vulnerabilidades<?= $filtro ? ' para este activo' : '' ?>. Selecciona un activo y registra su primera debilidad.</p></div>
<?php else: ?>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>Código</th><th>Activo</th><th>Vulnerabilidad</th><th>CVSS</th><th class="text-center">Amenazas</th><th class="text-end">Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $v): ?>
    <tr>
      <td><span class="codigo"><?= codigo('V', $v['id']) ?></span></td>
      <td class="text-nowrap"><span class="codigo"><?= codigo('A', $v['activo_id']) ?></span><div class="small"><?= h($v['activo']) ?></div></td>
      <td><?= h($v['descripcion']) ?>
        <?php if ($v['cve'] !== ''): ?><div class="small"><a target="_blank" rel="noopener" href="https://nvd.nist.gov/vuln/detail/<?= h($v['cve']) ?>"><?= h($v['cve']) ?></a></div><?php endif; ?>
        <?php if ($v['vector_cvss'] !== ''): ?><div class="small text-body-secondary font-monospace"><?= h($v['vector_cvss']) ?></div><?php endif; ?></td>
      <td><?= badge_cvss($v['cvss']) ?></td>
      <td class="text-center"><?= $v['n_amen'] > 0 ? '<a href="amenazas.php">' . $v['n_amen'] . '</a>' : '<span class="text-warning" title="Sin amenazas">0</span>' ?></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalVuln" data-edit="<?= h(json_encode($v, JSON_UNESCAPED_UNICODE)) ?>" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBorrar" data-borrar-id="<?= $v['id'] ?>" data-borrar-que="vulnerabilidad"
          data-borrar-nombre="<?= h(codigo('V', $v['id']) . ' · ' . mb_strimwidth($v['descripcion'], 0, 70, '…')) ?>"
          data-borrar-cascada="<?= $v['n_amen'] + $v['n_riesgos'] > 0 ? h("También se eliminarán {$v['n_amen']} amenaza(s) y {$v['n_riesgos']} riesgo(s) ligados.") : '' ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>

<div class="modal fade" id="modalVuln" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="post" class="modal-content" data-form-modal>
      <?= csrf_field() ?><input type="hidden" name="id" value="">
      <div class="modal-header">
        <h5 class="modal-title" data-titulo-nuevo="Nueva vulnerabilidad" data-titulo-editar="Editar vulnerabilidad"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Activo afectado *</label>
            <select class="form-select" name="activo_id" required><option value="">Selecciona un activo…</option>
              <?php foreach ($activos as $a): ?><option value="<?= $a['id'] ?>"><?= h(codigo('A', $a['id']) . ' · ' . $a['nombre']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><label class="form-label">Descripción de la vulnerabilidad *</label>
            <textarea class="form-control" name="descripcion" rows="3" placeholder="Qué debilidad es, dónde está y por qué es un problema. Ej. «El formulario de rastreo no valida el parámetro guia y permite inyección SQL»" required></textarea></div>
          <div class="col-md-4"><label class="form-label">Valor CVSS (0.0 – 10.0) *</label>
            <input class="form-control" name="cvss" type="number" min="0" max="10" step="0.1" inputmode="decimal" required data-cvss>
            <div class="mt-2" data-cvss-badge><span class="text-body-secondary small">Escribe el valor para ver su severidad.</span></div></div>
          <div class="col-md-8"><label class="form-label">CVE <span class="text-body-secondary">(si existe)</span></label>
            <input class="form-control" name="cve" maxlength="20" placeholder="CVE-2021-44228" pattern="(?i)CVE-\d{4}-\d{4,7}">
            <div class="form-text">Búscalo en <a target="_blank" rel="noopener" href="https://nvd.nist.gov/vuln/search">nvd.nist.gov</a> y copia su puntaje CVSS 3.x.</div></div>
          <div class="col-12"><label class="form-label">Vector CVSS <span class="text-body-secondary">(opcional)</span></label>
            <input class="form-control font-monospace" name="vector_cvss" maxlength="120" placeholder="CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H">
            <div class="form-text">Sin CVE (configuración débil, falta de proceso): estima el puntaje con la <a target="_blank" rel="noopener" href="https://www.first.org/cvss/calculator/3.1">calculadora CVSS 3.1</a> y pega el vector.</div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
    </form>
  </div>
</div>
<?= modal_borrar() ?>
<?php layout_fin(); ?>
