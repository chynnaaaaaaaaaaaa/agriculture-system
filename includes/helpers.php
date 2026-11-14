<?php
declare(strict_types=1);

/* ------------------------------------------------------------------
 *  Configuration (.env file OR real environment variables)
 * ------------------------------------------------------------------ */
function load_env(string $file): void
{
    if (!is_file($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $GLOBALS['_APP_ENV'][trim($key)] = trim(trim($value), "\"'");
    }
}

function env(string $key, ?string $default = null): ?string
{
    $real = getenv($key);                       // deployment platforms set real env vars
    if ($real !== false && $real !== '') {
        return $real;
    }
    return $GLOBALS['_APP_ENV'][$key] ?? $default;
}

/* ------------------------------------------------------------------
 *  Output / URL helpers
 * ------------------------------------------------------------------ */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(env('APP_BASE_PATH', ''), '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function money(mixed $value): string
{
    return '₱' . number_format((float) $value, 2);
}

function num(mixed $value, int $decimals = 2): string
{
    return number_format((float) $value, $decimals);
}

/* ------------------------------------------------------------------
 *  Flash messages (success / error banners shown once)
 * ------------------------------------------------------------------ */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show" role="alert">'
              . e($f['message'])
              . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/* ------------------------------------------------------------------
 *  CSRF protection for every POST form
 * ------------------------------------------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Your session expired or the request was invalid. Please go back, refresh the page and try again.');
    }
}

/* ------------------------------------------------------------------
 *  Form helpers
 * ------------------------------------------------------------------ */
function invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function feedback(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<div class="invalid-feedback">' . e($errors[$field]) . '</div>' : '';
}

/** Build <option> tags from rows that contain 'id' and 'label'. */
function options(array $rows, mixed $selected, string $placeholder = '-- Select --'): string
{
    $html = '<option value="">' . e($placeholder) . '</option>';
    foreach ($rows as $r) {
        $sel = ((string) $selected === (string) $r['id']) ? ' selected' : '';
        $html .= '<option value="' . e($r['id']) . '"' . $sel . '>' . e($r['label']) . '</option>';
    }
    return $html;
}

/* ------------------------------------------------------------------
 *  Sorting + pagination helpers for list pages
 * ------------------------------------------------------------------ */
function query_with(array $changes): string
{
    return http_build_query(array_filter(
        array_merge($_GET, $changes),
        fn($v) => $v !== '' && $v !== null
    ));
}

function sort_th(string $label, string $key): string
{
    $current = $_GET['sort'] ?? '';
    $dir     = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
    $next    = ($current === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow   = $current === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<th><a class="text-decoration-none text-reset" href="?'
         . e(query_with(['sort' => $key, 'dir' => $next, 'page' => 1])) . '">'
         . e($label) . $arrow . '</a></th>';
}

function render_pagination(array $result): string
{
    if ($result['pages'] <= 1) {
        return '<p class="text-muted small mb-0">Showing ' . (int) $result['total'] . ' record(s)</p>';
    }
    $page  = $result['page'];
    $pages = $result['pages'];
    $html  = '<nav><ul class="pagination pagination-sm flex-wrap mb-0">';
    $item  = function (int $p, string $label, bool $disabled = false, bool $active = false): string {
        $cls = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
        return '<li class="' . $cls . '"><a class="page-link" href="?' . e(query_with(['page' => $p])) . '">'
             . $label . '</a></li>';
    };
    $html .= $item($page - 1, '&laquo;', $page <= 1);
    for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) {
        $html .= $item($p, (string) $p, false, $p === $page);
    }
    $html .= $item($page + 1, '&raquo;', $page >= $pages);
    $html .= '</ul></nav>';
    return '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">'
         . '<span class="text-muted small">Page ' . $page . ' of ' . $pages . ' &middot; '
         . (int) $result['total'] . ' record(s)</span>' . $html . '</div>';
}

/** Button that opens the shared "Are you sure?" delete dialog (admins only). */
function delete_button(string $actionUrl, int $id, string $name): string
{
    if (!Auth::isAdmin()) {
        return '';
    }
    return '<button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" '
         . 'data-bs-target="#deleteModal" data-action="' . e(url($actionUrl)) . '" '
         . 'data-id="' . $id . '" data-name="' . e($name) . '">Delete</button>';
}

/** Footer line for View pages: created/updated timestamps and users. */
function audit_html(array $a): string
{
    if (!$a) {
        return '';
    }
    $fmt = fn($ts) => $ts ? date('M d, Y h:i A', strtotime($ts)) : '-';
    return '<p class="text-muted small mt-3 mb-0">Created ' . e($fmt($a['created_at']))
         . ($a['created_by_name'] ? ' by ' . e($a['created_by_name']) : '')
         . ' &middot; Last updated ' . e($fmt($a['updated_at']))
         . ($a['updated_by_name'] ? ' by ' . e($a['updated_by_name']) : '') . '</p>';
}

/** Shared handler for every module's delete.php (POST only, admin only, CSRF-checked). */
function handle_delete(BaseModel $model, string $label, string $backPath, string $blockedBy): never
{
    Auth::requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($backPath);
    }
    csrf_verify();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        flash('danger', 'Invalid record selected.');
        redirect($backPath);
    }
    try {
        if ($model->delete($id)) {
            flash('success', "$label deleted successfully.");
        } else {
            flash('warning', "$label was not found. It may already have been deleted.");
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23503') {       // foreign-key violation
            flash('danger', "This $label cannot be deleted because $blockedBy. Remove or reassign those records first.");
        } else {
            error_log('Delete failed: ' . $e->getMessage());
            flash('danger', 'The record could not be deleted. Please try again.');
        }
    }
    redirect($backPath);
}
