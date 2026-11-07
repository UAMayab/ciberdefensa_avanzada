<?php
// Arranque común: sesión, base de datos, CSRF, mensajes flash y utilidades.
declare(strict_types=1);

session_start();
date_default_timezone_set('America/Merida');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/config.php';
        $pdo = new PDO(
            "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4",
            $c['db_user'],
            $c['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

/** Todas las filas de una consulta preparada. */
function q(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Una fila (o null). */
function q1(string $sql, array $params = []): ?array
{
    return q($sql, $params)[0] ?? null;
}

/** Un valor escalar. */
function qv(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Código legible: A-001, V-003, T-012, R-004. */
function codigo(string $prefijo, $id): string
{
    return sprintf('%s-%03d', $prefijo, (int) $id);
}

function fecha_es(?string $f): string
{
    return $f ? date('d/m/Y', strtotime($f)) : '—';
}

// ---------------------------------------------------------------- CSRF

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Solicitud no válida (token CSRF). Recarga la página e inténtalo de nuevo.');
    }
}

// ---------------------------------------------------------------- mensajes flash

function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = [$tipo, $msg];
}

function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$tipo, $msg]) {
        $out .= '<div class="alert alert-' . h($tipo) . ' alert-dismissible fade show" role="alert">'
            . h($msg) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------------- entrada

/** Campo de texto del POST, recortado. */
function post_str(string $k): string
{
    return trim((string) ($_POST[$k] ?? ''));
}

/** Valida campos obligatorios; devuelve un mensaje de error o null. */
function faltantes(array $etiquetas): ?string
{
    $vacios = [];
    foreach ($etiquetas as $campo => $etiqueta) {
        if (post_str($campo) === '') {
            $vacios[] = $etiqueta;
        }
    }
    return $vacios ? 'Faltan campos obligatorios: ' . implode(', ', $vacios) . '.' : null;
}

function fecha_valida(string $f): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}

/** Opciones sugeridas para los campos de texto libre con <datalist>. */
function sugerencias(string $columna, array $base): array
{
    $permitidas = ['departamento', 'sistema_operativo'];
    if (!in_array($columna, $permitidas, true)) {
        return $base;
    }
    $usadas = array_column(q("SELECT DISTINCT $columna AS v FROM activos WHERE $columna <> '' ORDER BY v"), 'v');
    return array_values(array_unique(array_merge($base, $usadas)));
}
