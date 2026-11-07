<?php
// Respaldo, restauración, ejemplo y vaciado. Todo el trabajo del estudiante vive en la VM:
// el JSON exportado es lo que lo salva de un `vagrant destroy`.
declare(strict_types=1);

const FORMATO_RESPALDO = 'grc-respaldo';

/** Tablas del estudiante, en orden de inserción (padres antes que hijos), con sus columnas. */
const TABLAS_RESPALDO = [
    'activos'          => ['id', 'fecha', 'tipo_id', 'nombre', 'departamento', 'responsable', 'sistema_operativo', 'descripcion_funcion',
                           'participacion', 'criticidad', 'clasificacion', 'ubicacion'],
    'vulnerabilidades' => ['id', 'activo_id', 'descripcion', 'cvss', 'cve', 'vector_cvss'],
    'amenazas'         => ['id', 'vulnerabilidad_id', 'fuente_id', 'nombre', 'descripcion'],
    'riesgos'          => ['id', 'amenaza_id', 'impacto', 'probabilidad', 'tratamiento_id', 'justificacion', 'estado_id', 'responsable',
                           'fecha_objetivo', 'control_propuesto'],
];
const COLUMNAS_ORG = ['nombre', 'sector', 'objetivo_negocio', 'contexto', 'alcance', 'evaluador', 'tolerancia_nivel'];

function respaldo_exportar(): array
{
    $d = ['formato' => FORMATO_RESPALDO, 'version' => 1, 'exportado' => date('c'),
          'organizacion' => q1('SELECT ' . implode(',', COLUMNAS_ORG) . ' FROM organizacion WHERE id = 1'), 'tablas' => []];
    foreach (TABLAS_RESPALDO as $t => $cols) {
        $d['tablas'][$t] = q("SELECT " . implode(',', $cols) . " FROM $t ORDER BY id");
    }
    return $d;
}

/** Reemplaza TODO el contenido por el del respaldo (o lo deja vacío si $d es null). Todo o nada. */
function respaldo_restaurar(?array $d): void
{
    if ($d !== null && (($d['formato'] ?? '') !== FORMATO_RESPALDO || ($d['version'] ?? 0) !== 1 || !is_array($d['tablas'] ?? null))) {
        throw new RuntimeException('El archivo no es un respaldo válido de este sistema.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach (array_reverse(array_keys(TABLAS_RESPALDO)) as $t) {
            $pdo->exec("DELETE FROM $t");
        }
        $org = $d['organizacion'] ?? [];
        $vals = [];
        foreach (COLUMNAS_ORG as $c) {
            $vals[] = $org[$c] ?? ($c === 'tolerancia_nivel' ? 3 : '');
        }
        $pdo->prepare('UPDATE organizacion SET ' . implode(' = ?, ', COLUMNAS_ORG) . ' = ? WHERE id = 1')->execute($vals);

        foreach (TABLAS_RESPALDO as $t => $cols) {
            $ins = $pdo->prepare("INSERT INTO $t (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
            foreach ($d['tablas'][$t] ?? [] as $fila) {
                $ins->execute(array_map(fn($c) => $fila[$c] ?? null, $cols));
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    // Los contadores vuelven a empezar tras el id más alto presente (A-001 si quedó vacío).
    foreach (array_keys(TABLAS_RESPALDO) as $t) {
        $pdo->exec("ALTER TABLE $t AUTO_INCREMENT = 1");
    }
}

function respaldo_leer_json(string $ruta): array
{
    $d = json_decode((string) file_get_contents($ruta), true);
    if (!is_array($d)) {
        throw new RuntimeException('El archivo no es un JSON válido.');
    }
    return $d;
}
