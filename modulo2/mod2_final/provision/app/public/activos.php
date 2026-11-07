<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

const PARTICIPACION = ['Directa', 'Indirecta'];
const CRITICIDAD = ['Baja', 'Media', 'Alta', 'Crítica'];
const CLASIFICACION = ['Pública', 'Interna', 'Confidencial', 'Restringida'];
const DEPARTAMENTOS = ['Dirección General', 'Tecnologías de la Información', 'Finanzas y Contabilidad', 'Recursos Humanos',
    'Operaciones', 'Ventas y Comercial', 'Logística', 'Atención a Clientes', 'Legal y Cumplimiento', 'Mercadotecnia', 'Compras'];
const SISTEMAS = ['Ubuntu Server 24.04 LTS', 'Ubuntu Server 22.04 LTS', 'Debian 12', 'Red Hat Enterprise Linux 9', 'Windows Server 2022',
    'Windows Server 2019', 'Windows Server 2016', 'Windows 11', 'Windows 10', 'macOS', 'Android', 'iOS', 'Firmware / appliance',
    'Servicio en la nube (SaaS)', 'No aplica'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (post_str('accion') === 'eliminar') {
            db()->prepare('DELETE FROM activos WHERE id = ?')->execute([(int) post_str('id')]);
            flash('success', 'Activo eliminado.');
        } else {
            $err = faltantes(['fecha' => 'Fecha', 'tipo_id' => 'Tipo de activo', 'nombre' => 'Nombre', 'departamento' => 'Departamento',
                'responsable' => 'Responsable', 'sistema_operativo' => 'Sistema operativo', 'descripcion_funcion' => 'Descripción de su función',
                'participacion' => 'Participación en el negocio']);
            if (!$err && !fecha_valida(post_str('fecha'))) $err = 'La fecha no es válida.';
            if (!$err && !in_array(post_str('participacion'), PARTICIPACION, true)) $err = 'Participación no válida.';
            if (!$err && !in_array(post_str('criticidad'), CRITICIDAD, true)) $err = 'Criticidad no válida.';
            if (!$err && !in_array(post_str('clasificacion'), CLASIFICACION, true)) $err = 'Clasificación no válida.';
            if ($err) {
                flash('danger', $err);
            } else {
                $vals = [post_str('fecha'), (int) post_str('tipo_id'), post_str('nombre'), post_str('departamento'), post_str('responsable'),
                    post_str('sistema_operativo'), post_str('descripcion_funcion'), post_str('participacion'),
                    post_str('criticidad'), post_str('clasificacion'), post_str('ubicacion')];
                $id = (int) post_str('id');
                if ($id > 0) {
                    db()->prepare('UPDATE activos SET fecha=?, tipo_id=?, nombre=?, departamento=?, responsable=?, sistema_operativo=?,
                        descripcion_funcion=?, participacion=?, criticidad=?, clasificacion=?, ubicacion=? WHERE id=?')->execute([...$vals, $id]);
                    flash('success', 'Activo ' . codigo('A', $id) . ' actualizado.');
                } else {
                    db()->prepare('INSERT INTO activos (fecha, tipo_id, nombre, departamento, responsable, sistema_operativo,
                        descripcion_funcion, participacion, criticidad, clasificacion, ubicacion) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute($vals);
                    flash('success', 'Activo ' . codigo('A', (int) db()->lastInsertId()) . ' registrado. Siguiente paso: sus vulnerabilidades.');
                }
            }
        }
    } catch (PDOException $e) {
        flash('danger', 'No se pudo guardar el activo: revisa los datos.');
    }
    redirect('activos.php');
}

$filas = q('SELECT a.*, t.nombre AS tipo,
        (SELECT COUNT(*) FROM vulnerabilidades v WHERE v.activo_id = a.id) AS n_vuln,
        (SELECT COUNT(*) FROM amenazas m JOIN vulnerabilidades v ON v.id = m.vulnerabilidad_id WHERE v.activo_id = a.id) AS n_amen,
        (SELECT COUNT(*) FROM riesgos r JOIN amenazas m ON m.id = r.amenaza_id JOIN vulnerabilidades v ON v.id = m.vulnerabilidad_id WHERE v.activo_id = a.id) AS n_riesgos
    FROM activos a JOIN cat_tipo_activo t ON t.id = a.tipo_id ORDER BY a.id');
$tipos = q('SELECT id, nombre FROM cat_tipo_activo ORDER BY id');

layout_inicio('Activos', 'activos');
echo render_ayuda('activos');
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><i class="bi bi-hdd-network"></i> Activos</h1>
  <div class="d-flex flex-wrap gap-2">
    <input type="search" class="form-control" style="width:16rem" placeholder="Buscar…" data-filtro-texto="#tabla-activos">
    <select class="form-select" style="width:13rem" data-filtro-col="tipo" data-filtro-tabla="#tabla-activos">
      <option value="">Todos los tipos</option>
      <?php foreach ($tipos as $t): ?><option><?= h($t['nombre']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalActivo"><i class="bi bi-plus-lg"></i> Nuevo activo</button>
  </div>
</div>

<?php if (!$filas): ?>
  <div class="empty-state"><i class="bi bi-hdd-network"></i><p>Aún no hay activos. Registra el primero: ¿qué necesita funcionar para que la organización cumpla su objetivo?</p></div>
<?php else: ?>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0" id="tabla-activos">
  <thead class="table-light"><tr>
    <th>Código</th><th>Activo</th><th>Tipo</th><th>Departamento / Responsable</th><th>Sistema operativo</th>
    <th>Participación</th><th>Criticidad</th><th class="text-center">Vuln.</th><th class="text-end">Acciones</th>
  </tr></thead>
  <tbody>
  <?php foreach ($filas as $a): ?>
    <tr data-tipo="<?= h($a['tipo']) ?>">
      <td class="text-nowrap"><span class="codigo"><?= codigo('A', $a['id']) ?></span><div class="small text-body-secondary"><?= fecha_es($a['fecha']) ?></div></td>
      <td><b><?= h($a['nombre']) ?></b><div class="small text-body-secondary celda-desc"><?= h($a['descripcion_funcion']) ?></div></td>
      <td><?= h($a['tipo']) ?></td>
      <td><?= h($a['departamento']) ?><div class="small text-body-secondary"><?= h($a['responsable']) ?></div></td>
      <td><?= h($a['sistema_operativo']) ?></td>
      <td><span class="badge text-bg-<?= $a['participacion'] === 'Directa' ? 'primary' : 'secondary' ?>"><?= h($a['participacion']) ?></span></td>
      <td><span class="badge text-bg-<?= ['Baja' => 'success', 'Media' => 'warning', 'Alta' => 'orange', 'Crítica' => 'danger'][$a['criticidad']] ?>"><?= h($a['criticidad']) ?></span>
          <div class="small text-body-secondary"><?= h($a['clasificacion']) ?></div></td>
      <td class="text-center"><?= $a['n_vuln'] > 0 ? '<a href="vulnerabilidades.php?activo=' . $a['id'] . '">' . $a['n_vuln'] . '</a>' : '<span class="text-warning" title="Sin vulnerabilidades registradas">0</span>' ?></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalActivo" data-edit="<?= h(json_encode($a, JSON_UNESCAPED_UNICODE)) ?>" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBorrar" data-borrar-id="<?= $a['id'] ?>" data-borrar-que="activo"
          data-borrar-nombre="<?= h(codigo('A', $a['id']) . ' · ' . $a['nombre']) ?>"
          data-borrar-cascada="<?= $a['n_vuln'] + $a['n_amen'] + $a['n_riesgos'] > 0 ? h("También se eliminarán {$a['n_vuln']} vulnerabilidad(es), {$a['n_amen']} amenaza(s) y {$a['n_riesgos']} riesgo(s) ligados a este activo.") : '' ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>

<div class="modal fade" id="modalActivo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="post" class="modal-content" data-form-modal>
      <?= csrf_field() ?><input type="hidden" name="id" value="">
      <div class="modal-header">
        <h5 class="modal-title" data-titulo-nuevo="Nuevo activo" data-titulo-editar="Editar activo"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Fecha de identificación *</label>
            <input type="date" class="form-control" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
          <div class="col-md-8"><label class="form-label">Tipo de activo *</label>
            <select class="form-select" name="tipo_id" required><option value="">Selecciona…</option>
              <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>"><?= h($t['nombre']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><label class="form-label">Nombre del activo *</label>
            <input class="form-control" name="nombre" maxlength="150" placeholder="Ej. Servidor del portal de clientes" required></div>
          <div class="col-md-6"><label class="form-label">Departamento *</label>
            <input class="form-control" name="departamento" list="dl-depto" maxlength="100" placeholder="Elige o escribe" required>
            <datalist id="dl-depto"><?php foreach (sugerencias('departamento', DEPARTAMENTOS) as $s): ?><option value="<?= h($s) ?>"><?php endforeach; ?></datalist></div>
          <div class="col-md-6"><label class="form-label">Responsable directo *</label>
            <input class="form-control" name="responsable" maxlength="150" placeholder="Nombre y puesto" required></div>
          <div class="col-md-6"><label class="form-label">Sistema operativo *</label>
            <input class="form-control" name="sistema_operativo" list="dl-so" maxlength="100" placeholder="Elige o escribe («No aplica» si no tiene)" required>
            <datalist id="dl-so"><?php foreach (sugerencias('sistema_operativo', SISTEMAS) as $s): ?><option value="<?= h($s) ?>"><?php endforeach; ?></datalist></div>
          <div class="col-md-6"><label class="form-label">Participación en la función del negocio *</label>
            <select class="form-select" name="participacion" required>
              <option value="Directa">Directa — si falla, el negocio se detiene o se degrada</option>
              <option value="Indirecta">Indirecta — apoya al negocio, pero hay alternativas</option></select></div>
          <div class="col-12"><label class="form-label">Descripción de su función *</label>
            <textarea class="form-control" name="descripcion_funcion" rows="2" placeholder="¿Qué hace este activo y para qué lo necesita el negocio?" required></textarea></div>
          <div class="col-12"><hr class="my-1"><div class="small text-body-secondary"><i class="bi bi-stars"></i> Campos sugeridos (ayudan a decidir el impacto después)</div></div>
          <div class="col-md-4"><label class="form-label">Criticidad</label>
            <select class="form-select" name="criticidad"><?php foreach (CRITICIDAD as $o): ?><option <?= $o === 'Media' ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-label">Clasificación de la información</label>
            <select class="form-select" name="clasificacion"><?php foreach (CLASIFICACION as $o): ?><option <?= $o === 'Interna' ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-label">Ubicación / dirección IP</label>
            <input class="form-control" name="ubicacion" maxlength="150" placeholder="Ej. Sala de servidores · 10.0.2.10"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
    </form>
  </div>
</div>
<?= modal_borrar() ?>
<?php layout_fin(); ?>
