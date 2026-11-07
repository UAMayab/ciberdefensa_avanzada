<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

const EVENTOS = ['Ransomware', 'Phishing y robo de credenciales', 'Inyección SQL', 'Fuerza bruta de credenciales', 'Explotación de vulnerabilidad sin parche',
    'Denegación de servicio (DoS)', 'Fuga de información por empleado interno', 'Error de configuración de un administrador', 'Pérdida o robo de dispositivo',
    'Falla de hardware o de disco', 'Corte de energía eléctrica', 'Incendio', 'Inundación o huracán', 'Falla del proveedor o de la nube', 'Malware instalado por el usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (post_str('accion') === 'eliminar') {
            db()->prepare('DELETE FROM amenazas WHERE id = ?')->execute([(int) post_str('id')]);
            flash('success', 'Amenaza eliminada.');
        } else {
            $err = faltantes(['vulnerabilidad_id' => 'Vulnerabilidad', 'fuente_id' => 'Fuente de la amenaza', 'nombre' => 'Nombre del evento', 'descripcion' => 'Descripción']);
            if ($err) {
                flash('danger', $err);
            } else {
                $vals = [(int) post_str('vulnerabilidad_id'), (int) post_str('fuente_id'), post_str('nombre'), post_str('descripcion')];
                $id = (int) post_str('id');
                if ($id > 0) {
                    db()->prepare('UPDATE amenazas SET vulnerabilidad_id=?, fuente_id=?, nombre=?, descripcion=? WHERE id=?')->execute([...$vals, $id]);
                    flash('success', 'Amenaza ' . codigo('T', $id) . ' actualizada.');
                } else {
                    db()->prepare('INSERT INTO amenazas (vulnerabilidad_id, fuente_id, nombre, descripcion) VALUES (?,?,?,?)')->execute($vals);
                    flash('success', 'Amenaza ' . codigo('T', (int) db()->lastInsertId()) . ' registrada. Siguiente paso: evaluarla en Riesgos.');
                }
            }
        }
    } catch (PDOException $e) {
        flash('danger', 'No se pudo guardar la amenaza: revisa los datos.');
    }
    redirect('amenazas.php' . (isset($_GET['pendientes']) ? '?pendientes=1' : ''));
}

$solo_pendientes = isset($_GET['pendientes']);
$pares = q('SELECT v.id, v.descripcion, v.cvss, a.id AS activo_id, a.nombre AS activo, a.criticidad
    FROM vulnerabilidades v JOIN activos a ON a.id = v.activo_id
    ' . ($solo_pendientes ? 'WHERE NOT EXISTS (SELECT 1 FROM amenazas m WHERE m.vulnerabilidad_id = v.id)' : '') . '
    ORDER BY v.cvss DESC, v.id');
$amenazas = [];
foreach (q('SELECT m.*, f.nombre AS fuente, r.id AS riesgo_id FROM amenazas m JOIN cat_fuente_amenaza f ON f.id = m.fuente_id
        LEFT JOIN riesgos r ON r.amenaza_id = m.id ORDER BY m.id') as $m) {
    $amenazas[$m['vulnerabilidad_id']][] = $m;
}
$fuentes = q('SELECT * FROM cat_fuente_amenaza ORDER BY id');
$n_total = (int) qv('SELECT COUNT(*) FROM vulnerabilidades');
$colores_fuente = ['Adversaria' => 'danger', 'Accidental' => 'warning', 'Estructural' => 'secondary', 'Ambiental' => 'info'];

layout_inicio('Amenazas', 'amenazas');
echo render_ayuda('amenazas');
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><i class="bi bi-lightning-charge"></i> Amenazas</h1>
  <div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" id="solo-pend" <?= $solo_pendientes ? 'checked' : '' ?> onchange="location.href='amenazas.php'+(this.checked?'?pendientes=1':'')">
    <label class="form-check-label" for="solo-pend">Mostrar solo pares sin amenazas</label>
  </div>
</div>

<?php if ($n_total === 0): ?>
  <div class="empty-state"><i class="bi bi-bug"></i><p>Para capturar amenazas necesitas pares Activo-Vulnerabilidad. Registra primero las <a href="vulnerabilidades.php">vulnerabilidades</a>.</p></div>
<?php elseif (!$pares): ?>
  <div class="empty-state"><i class="bi bi-check2-circle text-success"></i><p>Todos los pares Activo-Vulnerabilidad ya tienen al menos una amenaza.</p></div>
<?php endif; ?>

<?php foreach ($pares as $p): $lista = $amenazas[$p['id']] ?? []; ?>
<div class="card mb-3 par-card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <span class="codigo"><?= codigo('A', $p['activo_id']) ?></span> <b><?= h($p['activo']) ?></b>
    <i class="bi bi-arrow-left-right text-body-secondary"></i>
    <span class="codigo"><?= codigo('V', $p['id']) ?></span> <span class="flex-fill-min"><?= h($p['descripcion']) ?></span>
    <?= badge_cvss($p['cvss']) ?>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAmenaza" data-vuln="<?= $p['id'] ?>"
      data-par="<?= h(codigo('A', $p['activo_id']) . ' · ' . $p['activo'] . '  ⟷  ' . codigo('V', $p['id']) . ' · ' . $p['descripcion']) ?>"><i class="bi bi-plus-lg"></i> Amenaza</button>
  </div>
  <?php if (!$lista): ?>
    <div class="card-body text-body-secondary small"><i class="bi bi-circle text-warning"></i> Este par aún no tiene amenazas. ¿Quién o qué podría aprovechar esta debilidad?</div>
  <?php else: ?>
    <ul class="list-group list-group-flush">
    <?php foreach ($lista as $m): ?>
      <li class="list-group-item d-flex flex-wrap align-items-start gap-2">
        <span class="codigo mt-1"><?= codigo('T', $m['id']) ?></span>
        <span class="badge text-bg-<?= $colores_fuente[$m['fuente']] ?? 'secondary' ?> mt-1"><?= h($m['fuente']) ?></span>
        <div class="flex-grow-1"><b><?= h($m['nombre']) ?></b><div class="small text-body-secondary"><?= h($m['descripcion']) ?></div></div>
        <?= $m['riesgo_id'] ? '<a class="badge text-bg-light border mt-1" href="riesgos.php?ver=' . $m['riesgo_id'] . '"><i class="bi bi-check2"></i> ' . codigo('R', $m['riesgo_id']) . '</a>'
                            : '<a class="badge text-bg-warning mt-1 text-decoration-none" href="riesgos.php">Sin evaluar</a>' ?>
        <span class="text-nowrap">
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAmenaza" data-edit="<?= h(json_encode($m, JSON_UNESCAPED_UNICODE)) ?>"
            data-par="<?= h(codigo('A', $p['activo_id']) . ' · ' . $p['activo'] . '  ⟷  ' . codigo('V', $p['id']) . ' · ' . $p['descripcion']) ?>" title="Editar"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBorrar" data-borrar-id="<?= $m['id'] ?>" data-borrar-que="amenaza"
            data-borrar-nombre="<?= h(codigo('T', $m['id']) . ' · ' . $m['nombre']) ?>"
            data-borrar-cascada="<?= $m['riesgo_id'] ? h('También se eliminará el riesgo ' . codigo('R', $m['riesgo_id']) . ' evaluado para esta amenaza.') : '' ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
        </span>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="modal fade" id="modalAmenaza" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="post" class="modal-content" data-form-modal>
      <?= csrf_field() ?><input type="hidden" name="id" value=""><input type="hidden" name="vulnerabilidad_id" value="">
      <div class="modal-header">
        <h5 class="modal-title" data-titulo-nuevo="Nueva amenaza" data-titulo-editar="Editar amenaza"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-secondary py-2 small"><b>Par Activo ⟷ Vulnerabilidad:</b> <span id="amenaza-par"></span></div>
        <div class="row g-3">
          <div class="col-md-5"><label class="form-label">Fuente de la amenaza *</label>
            <select class="form-select" name="fuente_id" required><option value="">Selecciona…</option>
              <?php foreach ($fuentes as $f): ?><option value="<?= $f['id'] ?>" data-desc="<?= h($f['descripcion']) ?>"><?= h($f['nombre']) ?></option><?php endforeach; ?></select>
            <div class="form-text" id="fuente-desc">Tabla D-2 de NIST SP 800-30.</div></div>
          <div class="col-md-7"><label class="form-label">Evento de amenaza *</label>
            <input class="form-control" name="nombre" list="dl-eventos" maxlength="150" placeholder="Elige o escribe" required>
            <datalist id="dl-eventos"><?php foreach (EVENTOS as $e): ?><option value="<?= h($e) ?>"><?php endforeach; ?></datalist></div>
          <div class="col-12"><label class="form-label">Descripción *</label>
            <textarea class="form-control" name="descripcion" rows="3" placeholder="Cómo ocurriría: quién o qué la origina, cómo aprovecha la vulnerabilidad y qué le pasaría al activo." required></textarea></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
    </form>
  </div>
</div>
<?= modal_borrar() ?>
<?php layout_fin(); ?>
