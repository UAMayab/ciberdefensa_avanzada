<?php
// Modelo de riesgo (NIST SP 800-30 Rev. 1): niveles, escalas, matriz y nivel global.
declare(strict_types=1);

/** Niveles 1-5 indexados por id: nombre, color, color_texto. */
function niveles(): array
{
    static $n = null;
    if ($n === null) {
        $n = [];
        foreach (q('SELECT * FROM cat_nivel ORDER BY id') as $r) {
            $n[(int) $r['id']] = $r;
        }
    }
    return $n;
}

/** Matriz [probabilidad][impacto] => nivel (1-5). */
function matriz(): array
{
    static $m = null;
    if ($m === null) {
        $m = [];
        foreach (q('SELECT * FROM matriz_riesgo') as $r) {
            $m[(int) $r['probabilidad']][(int) $r['impacto']] = (int) $r['nivel_id'];
        }
    }
    return $m;
}

/** Escala de 'impacto' o 'probabilidad' indexada por valor. */
function escala(string $tipo): array
{
    $e = [];
    foreach (q('SELECT valor, nombre, descripcion FROM escala WHERE tipo = ? ORDER BY valor', [$tipo]) as $r) {
        $e[(int) $r['valor']] = $r;
    }
    return $e;
}

function badge_nivel(int $nivel): string
{
    $n = niveles()[$nivel];
    return '<span class="badge badge-nivel" style="background:' . h($n['color']) . ';color:' . h($n['color_texto']) . '">'
        . h($n['nombre']) . '</span>';
}

/** Severidad CVSS v3.1 (escala cualitativa oficial de FIRST). */
function cvss_severidad(float $s): array
{
    if ($s == 0.0) return ['Ninguna', '#6c757d', '#ffffff'];
    if ($s < 4.0)  return ['Baja',    '#2e9e5b', '#ffffff'];
    if ($s < 7.0)  return ['Media',   '#f4c430', '#1f2937'];
    if ($s < 9.0)  return ['Alta',    '#f08a24', '#1f2937'];
    return ['Crítica', '#d64545', '#ffffff'];
}

function badge_cvss($s): string
{
    [$etq, $bg, $fg] = cvss_severidad((float) $s);
    return '<span class="badge badge-nivel" style="background:' . $bg . ';color:' . $fg . '">'
        . number_format((float) $s, 1) . ' · ' . $etq . '</span>';
}

/**
 * Nivel de riesgo global de la organización (1-5) a partir de los riesgos vigentes.
 * Regla: el mayor entre el promedio redondeado y (nivel máximo − 1). Así un solo riesgo Muy alto
 * nunca queda diluido entre muchos riesgos bajos. Devuelve null si no hay riesgos vigentes.
 */
function nivel_global(array $riesgos): ?array
{
    $niv = [];
    foreach ($riesgos as $r) {
        if ((int) $r['vigente'] === 1) {
            $niv[] = (int) $r['nivel'];
        }
    }
    if (!$niv) {
        return null;
    }
    $prom = array_sum($niv) / count($niv);
    $max = max($niv);
    $global = max((int) round($prom), $max - 1);
    return ['nivel' => max(1, $global), 'promedio' => $prom, 'maximo' => $max, 'n' => count($niv)];
}

/** Todos los riesgos con su cadena completa, del más alto al más bajo. */
function riesgos_todos(): array
{
    return q('SELECT * FROM v_riesgos ORDER BY nivel DESC, cvss DESC, id');
}

/** Conteos del inventario y de lo que falta por analizar. */
function conteos(): array
{
    return [
        'activos'           => (int) qv('SELECT COUNT(*) FROM activos'),
        'vulnerabilidades'  => (int) qv('SELECT COUNT(*) FROM vulnerabilidades'),
        'amenazas'          => (int) qv('SELECT COUNT(*) FROM amenazas'),
        'riesgos'           => (int) qv('SELECT COUNT(*) FROM riesgos'),
        'activos_sin_vuln'  => (int) qv('SELECT COUNT(*) FROM activos a WHERE NOT EXISTS (SELECT 1 FROM vulnerabilidades v WHERE v.activo_id = a.id)'),
        'vuln_sin_amenaza'  => (int) qv('SELECT COUNT(*) FROM vulnerabilidades v WHERE NOT EXISTS (SELECT 1 FROM amenazas t WHERE t.vulnerabilidad_id = v.id)'),
        'amenazas_sin_riesgo' => (int) qv('SELECT COUNT(*) FROM amenazas t WHERE NOT EXISTS (SELECT 1 FROM riesgos r WHERE r.amenaza_id = t.id)'),
    ];
}

/** Icono (Bootstrap Icons) de cada estado: la forma de la ficha cuenta el estado además del color. */
function icono_estado(int $estado_id): string
{
    return [1 => 'bi-circle-fill', 2 => 'bi-gear-fill', 3 => 'bi-shield-check', 4 => 'bi-hand-thumbs-up-fill', 5 => 'bi-x-circle'][$estado_id] ?? 'bi-circle';
}

/** Heatmap 5×5 (probabilidad en filas, impacto en columnas) con los riesgos como fichas. */
function render_heatmap(array $riesgos, bool $enlaces = true): string
{
    $m = matriz();
    $niv = niveles();
    $pe = escala('probabilidad');
    $ie = escala('impacto');

    $celdas = [];
    foreach ($riesgos as $r) {
        $celdas[(int) $r['probabilidad']][(int) $r['impacto']][] = $r;
    }

    $o = '<div class="heatmap-wrap"><div class="heatmap-ylabel"><span>Probabilidad</span></div><div class="heatmap-main">';
    $o .= '<table class="heatmap" role="grid" aria-label="Mapa de calor de riesgos: probabilidad por impacto"><tbody>';
    for ($p = 5; $p >= 1; $p--) {
        $o .= '<tr><th scope="row" class="heatmap-axis"><span class="num">' . $p . '</span>' . h($pe[$p]['nombre']) . '</th>';
        for ($i = 1; $i <= 5; $i++) {
            $n = $niv[$m[$p][$i]];
            $o .= '<td class="heatmap-cell" style="--cell:' . h($n['color']) . '" title="Probabilidad ' . h($pe[$p]['nombre'])
                . ' × Impacto ' . h($ie[$i]['nombre']) . ' = ' . h($n['nombre']) . '"><div class="heatmap-chips">';
            foreach ($celdas[$p][$i] ?? [] as $r) {
                $cls = 'risk-chip st-' . (int) $r['estado_id'];
                $tip = codigo('R', $r['id']) . ' · ' . $r['amenaza'] . ' (' . $r['activo'] . ') · ' . $r['estado'];
                $txt = '<i class="bi ' . icono_estado((int) $r['estado_id']) . '"></i> ' . h(codigo('R', $r['id']));
                $o .= $enlaces
                    ? '<a class="' . $cls . '" href="riesgos.php?ver=' . (int) $r['id'] . '" data-bs-toggle="tooltip" title="' . h($tip) . '">' . $txt . '</a>'
                    : '<span class="' . $cls . '" title="' . h($tip) . '">' . $txt . '</span>';
            }
            $o .= '</div></td>';
        }
        $o .= '</tr>';
    }
    $o .= '</tbody><tfoot><tr><td></td>';
    for ($i = 1; $i <= 5; $i++) {
        $o .= '<th scope="col" class="heatmap-axis"><span class="num">' . $i . '</span>' . h($ie[$i]['nombre']) . '</th>';
    }
    $o .= '</tr></tfoot></table><div class="heatmap-xlabel">Impacto</div></div></div>';

    // Leyenda: estado de cada riesgo (la forma de la ficha) y nivel (el color del fondo).
    $o .= '<div class="heatmap-legend"><span class="text-body-secondary">Estado:</span>';
    foreach (q('SELECT id, nombre FROM cat_estado_riesgo ORDER BY id') as $e) {
        $o .= '<span class="risk-chip st-' . (int) $e['id'] . '"><i class="bi ' . icono_estado((int) $e['id']) . '"></i> ' . h($e['nombre']) . '</span>';
    }
    $o .= '</div>';
    return $o;
}
