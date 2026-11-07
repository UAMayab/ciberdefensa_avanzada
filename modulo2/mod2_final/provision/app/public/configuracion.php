<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';
require __DIR__ . '/../inc/respaldo.php';

const SECTORES = ['Comercio minorista', 'Comercio electrónico', 'Manufactura', 'Servicios financieros', 'Salud', 'Educación', 'Gobierno',
    'Logística y transporte', 'Tecnología', 'Turismo y hospitalidad', 'Agroindustria', 'Energía', 'Telecomunicaciones', 'Construcción', 'Servicios profesionales'];

if (isset($_GET['exportar'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="respaldo-grc-' . date('Ymd-His') . '.json"');
    echo json_encode(respaldo_exportar(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        switch (post_str('accion')) {
            case 'perfil':
                $tol = (int) post_str('tolerancia_nivel');
                if (post_str('nombre') === '') {
                    flash('danger', 'El nombre de la organización es obligatorio.');
                } elseif ($tol < 1 || $tol > 5) {
                    flash('danger', 'Tolerancia no válida.');
                } else {
                    db()->prepare('UPDATE organizacion SET nombre=?, sector=?, objetivo_negocio=?, contexto=?, alcance=?, evaluador=?, tolerancia_nivel=? WHERE id=1')
                        ->execute([post_str('nombre'), post_str('sector'), post_str('objetivo_negocio'), post_str('contexto'), post_str('alcance'), post_str('evaluador'), $tol]);
                    flash('success', 'Perfil de la organización guardado.');
                }
                break;
            case 'ejemplo':
                respaldo_restaurar(respaldo_leer_json(__DIR__ . '/../inc/ejemplo.json'));
                flash('info', 'Ejemplo cargado (empresa ficticia). Recorre las pestañas para ver cómo se conectan; cuando quieras empezar tu análisis, usa «Vaciar todo».');
                break;
            case 'vaciar':
                if (post_str('palabra') !== 'BORRAR') {
                    flash('danger', 'Para vaciar la base escribe BORRAR en mayúsculas.');
                } else {
                    respaldo_restaurar(null);
                    flash('success', 'Base vaciada: puedes empezar desde cero.');
                }
                break;
            case 'importar':
                if (!isset($_FILES['respaldo']) || $_FILES['respaldo']['error'] !== UPLOAD_ERR_OK) {
                    flash('danger', 'Selecciona el archivo de respaldo (.json).');
                } else {
                    respaldo_restaurar(respaldo_leer_json($_FILES['respaldo']['tmp_name']));
                    flash('success', 'Respaldo restaurado.');
                }
                break;
        }
    } catch (Throwable $e) {
        flash('danger', 'No se pudo completar la operación: ' . $e->getMessage());
    }
    redirect('configuracion.php');
}

$org = q1('SELECT * FROM organizacion WHERE id = 1');
$niv = niveles();
$c = conteos();

layout_inicio('Configuración', 'configuracion');
echo render_ayuda('configuracion');
?>
<h1 class="h3 mb-3"><i class="bi bi-gear"></i> Configuración y respaldo</h1>

<div class="row g-4">
  <div class="col-lg-7">
    <form method="post" class="card">
      <?= csrf_field() ?><input type="hidden" name="accion" value="perfil">
      <div class="card-header"><strong><i class="bi bi-building"></i> Perfil de la organización</strong></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-7"><label class="form-label">Nombre de la organización *</label>
            <input class="form-control" name="nombre" maxlength="150" value="<?= h($org['nombre']) ?>" placeholder="Ej. Kaab Orgánicos, S.A. de C.V." required></div>
          <div class="col-md-5"><label class="form-label">Sector</label>
            <input class="form-control" name="sector" list="dl-sector" maxlength="100" value="<?= h($org['sector']) ?>" placeholder="Elige o escribe">
            <datalist id="dl-sector"><?php foreach (SECTORES as $s): ?><option value="<?= h($s) ?>"><?php endforeach; ?></datalist></div>
          <div class="col-12"><label class="form-label">Objetivo de negocio</label>
            <textarea class="form-control" name="objetivo_negocio" rows="2" placeholder="Qué quiere lograr la organización. Ej. «Vender miel orgánica en línea a todo México con entregas en menos de 72 h y sin fugas de datos de clientes.»"><?= h($org['objetivo_negocio']) ?></textarea></div>
          <div class="col-12"><label class="form-label">Contexto</label>
            <textarea class="form-control" name="contexto" rows="3" placeholder="Tamaño, ubicación, regulaciones aplicables (LFPDPPP, PCI DSS…), clientes, temporadas críticas, dependencias de terceros."><?= h($org['contexto']) ?></textarea></div>
          <div class="col-12"><label class="form-label">Alcance de la evaluación</label>
            <textarea class="form-control" name="alcance" rows="2" placeholder="Qué parte de la infraestructura evalúas y qué queda fuera."><?= h($org['alcance']) ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Evaluador (tu nombre)</label>
            <input class="form-control" name="evaluador" maxlength="150" value="<?= h($org['evaluador']) ?>" placeholder="Nombre y apellidos"></div>
          <div class="col-md-6"><label class="form-label">Tolerancia al riesgo</label>
            <select class="form-select" name="tolerancia_nivel">
              <?php foreach ($niv as $id => $n): ?><option value="<?= $id ?>" <?= (int) $org['tolerancia_nivel'] === $id ? 'selected' : '' ?>><?= h($n['nombre']) ?><?= $id === 3 ? ' (típico)' : '' ?></option><?php endforeach; ?></select>
            <div class="form-text">Nivel más alto que la organización acepta sin actuar.</div></div>
        </div>
      </div>
      <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar perfil</button></div>
    </form>
  </div>

  <div class="col-lg-5 d-flex flex-column gap-3">
    <div class="card border-primary">
      <div class="card-header"><strong><i class="bi bi-download"></i> Respaldo de tu trabajo</strong></div>
      <div class="card-body">
        <p class="small">Tu análisis vive <b>dentro de la máquina virtual</b>. Si ejecutas <code>vagrant destroy</code> se pierde. Descarga un respaldo antes de entregar y antes de destruir la VM.</p>
        <a class="btn btn-primary w-100 mb-3" href="configuracion.php?exportar=1"><i class="bi bi-download"></i> Descargar respaldo (.json)</a>
        <form method="post" enctype="multipart/form-data" data-confirm="Restaurar reemplaza TODO lo que hay ahora en el sistema. ¿Continuar?">
          <?= csrf_field() ?><input type="hidden" name="accion" value="importar">
          <label class="form-label small">Restaurar desde un respaldo</label>
          <div class="input-group"><input type="file" class="form-control" name="respaldo" accept=".json,application/json" required>
            <button class="btn btn-outline-primary">Restaurar</button></div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><strong><i class="bi bi-magic"></i> Ejemplo guiado</strong></div>
      <div class="card-body">
        <p class="small mb-2">Carga una empresa <b>ficticia</b> ya analizada (activos, vulnerabilidades, amenazas y riesgos) para ver cómo se conectan las pestañas y cómo se pintan el tablero y el reporte.</p>
        <form method="post" data-confirm="Cargar el ejemplo reemplaza TODO lo que hay ahora en el sistema. ¿Continuar?">
          <?= csrf_field() ?><input type="hidden" name="accion" value="ejemplo">
          <button class="btn btn-outline-primary btn-sm"><i class="bi bi-magic"></i> Cargar ejemplo</button>
        </form>
      </div>
    </div>

    <div class="card border-danger">
      <div class="card-header text-danger"><strong><i class="bi bi-exclamation-octagon"></i> Zona de peligro</strong></div>
      <div class="card-body">
        <p class="small mb-2">Elimina todos los activos, vulnerabilidades, amenazas y riesgos (<?= $c['activos'] ?> / <?= $c['vulnerabilidades'] ?> / <?= $c['amenazas'] ?> / <?= $c['riesgos'] ?>) y limpia el perfil. Descarga un respaldo antes.</p>
        <form method="post" class="input-group" data-confirm="Esto borra todo tu análisis. ¿Seguro?">
          <?= csrf_field() ?><input type="hidden" name="accion" value="vaciar">
          <input class="form-control" name="palabra" placeholder="Escribe BORRAR" autocomplete="off" required>
          <button class="btn btn-danger">Vaciar todo</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php layout_fin(); ?>
