<?php
/**
 * General-purpose helpers shared across every module.
 */

/** Escape for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Trim + strip tags for a raw text input value before it touches the DB. */
function clean($value): string
{
    return trim(strip_tags((string) $value));
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Abort a POST handler if the CSRF token is missing/invalid. */
function requireCsrf(): void
{
    if (!verifyCsrf()) {
        http_response_code(400);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}

function formatMoney($amount): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }
    return '$' . number_format((float) $amount, 2);
}

function formatDate(?string $date): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d M Y', $ts) : '—';
}

function formatDateTime(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d M Y, H:i', $ts) : '—';
}

/**
 * Renders a colour-coded status badge.
 * Green = active/positive outcome, amber = in-progress/pending, red = negative/final.
 */
function statusBadge(?string $status): string
{
    if ($status === null || $status === '') {
        return '—';
    }
    $key = strtolower($status);
    $success = ['active', 'found', 'approved', 'completed', 'issued', 'returned', 'success'];
    $warning = ['under_repair', 'pending', 'in_progress', 'repair'];
    $danger  = ['disposed', 'missing', 'rejected', 'damaged', 'inactive', 'failed'];

    if (in_array($key, $success, true)) {
        $class = 'badge-success';
    } elseif (in_array($key, $warning, true)) {
        $class = 'badge-warning';
    } elseif (in_array($key, $danger, true)) {
        $class = 'badge-danger';
    } else {
        $class = 'badge-neutral';
    }

    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/** Inline line-style SVG icons used across the sidebar, topbar, and dashboard tiles. */
function icon(string $name, string $class = ''): string
{
    $paths = [
        'dashboard'     => '<path d="M3 10.5 12 4l9 6.5"/><path d="M5 9.5V19a1 1 0 0 0 1 1h4v-5a2 2 0 0 1 2-2v0a2 2 0 0 1 2 2v5h4a1 1 0 0 0 1-1V9.5"/>',
        'assets'        => '<path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'allocations'   => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3.5h6a1 1 0 0 1 1 1V6H8V4.5a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6"/>',
        'transfers'     => '<path d="m7 8-4 4 4 4"/><path d="M3 12h13"/><path d="m17 4 4 4-4 4"/><path d="M21 8H8"/>',
        'maintenance'   => '<path d="M14.7 6.3a4 4 0 0 1-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 1 5.4-5.4l-2.6 2.6-2-2 2.6-2.6Z"/>',
        'audits'        => '<path d="m20 6-11 11-5-5"/>',
        'disposals'     => '<path d="M4 7h16"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/>',
        'requisitions'  => '<path d="M8 3h6l4 4v13a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14 3v4h4"/><path d="M9 13h6M9 17h6"/>',
        'reports'       => '<path d="M4 19V9M11 19V5M18 19v-7"/><path d="M2 19h20"/>',
        'users'         => '<circle cx="9" cy="8" r="3.2"/><path d="M2.8 19a6.2 6.2 0 0 1 12.4 0"/><path d="M16 8.2a3.2 3.2 0 1 1 0 6.1"/><path d="M17 13.8c2 .5 3.2 1.8 3.2 3.6"/>',
        'departments'   => '<path d="M4 21V6l8-3 8 3v15"/><path d="M4 21h16"/><path d="M9 21v-5h6v5"/><path d="M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/>',
        'categories'    => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/>',
        'locations'     => '<path d="M12 21s7-6.3 7-11.5A7 7 0 0 0 5 9.5C5 14.7 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/>',
        'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a1.7 1.7 0 0 0 .34 1.87l.06.06a2.06 2.06 0 1 1-2.9 2.9l-.07-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.56v.17a2.06 2.06 0 1 1-4.12 0v-.09a1.7 1.7 0 0 0-1.1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2.06 2.06 0 1 1-2.9-2.9l.06-.07a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.03h-.17a2.06 2.06 0 1 1 0-4.12h.09a1.7 1.7 0 0 0 1.55-1.1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2.06 2.06 0 1 1 2.9-2.9l.07.06a1.7 1.7 0 0 0 1.87.34h.08a1.7 1.7 0 0 0 1.03-1.56v-.17a2.06 2.06 0 1 1 4.12 0v.09a1.7 1.7 0 0 0 1.03 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2.06 2.06 0 1 1 2.9 2.9l-.06.07a1.7 1.7 0 0 0-.34 1.87v.08a1.7 1.7 0 0 0 1.56 1.03h.17a2.06 2.06 0 1 1 0 4.12h-.09a1.7 1.7 0 0 0-1.55 1.03Z"/>',
        'bell'          => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9Z"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
        'logout'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'profile'       => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'menu'          => '<path d="M3 6h18M3 12h18M3 18h18"/>',
    ];
    $path = $paths[$name] ?? '';
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/** Resolves the active app logo: admin-uploaded logo if set, else the bundled default. */
function appLogoUrl(PDO $pdo): string
{
    $logoPath = getSetting($pdo, 'logo_path', '');
    return $logoPath !== '' ? $logoPath : APP_URL . '/static/images/logo.webp';
}

/** Records an entry in activity_logs for the audit trail / dashboard feed. */
function logActivity(PDO $pdo, ?int $userId, string $action, string $module, string $description = ''): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO activity_logs (user_id, action, module, description, ip_address) VALUES (:user_id, :action, :module, :description, :ip)'
    );
    $stmt->execute([
        'user_id'     => $userId,
        'action'      => $action,
        'module'      => $module,
        'description' => $description,
        'ip'          => clientIp(),
    ]);
}

function logLogin(PDO $pdo, ?int $userId, string $emailAttempted, string $status): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO login_logs (user_id, email_attempted, status, ip_address, user_agent) VALUES (:user_id, :email, :status, :ip, :ua)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'email'   => $emailAttempted,
        'status'  => $status,
        'ip'      => clientIp(),
        'ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}

/** Reads all settings once per request and caches them in a static array. */
function getAllSettings(PDO $pdo): array
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

function getSetting(PDO $pdo, string $key, string $default = ''): string
{
    $all = getAllSettings($pdo);
    return $all[$key] ?? $default;
}

function setSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute(['k' => $key, 'v' => $value]);
}

/**
 * Recomputes an asset's status from its related records rather than letting
 * it be hand-edited: approved disposal wins, then any open maintenance
 * ticket, otherwise the asset is active. Called after any action that
 * touches maintenance or disposals for the asset.
 */
function recomputeAssetStatus(PDO $pdo, int $assetId): void
{
    $disposed = $pdo->prepare("SELECT 1 FROM asset_disposals WHERE asset_id = :id AND status = 'approved' LIMIT 1");
    $disposed->execute(['id' => $assetId]);
    if ($disposed->fetch()) {
        $pdo->prepare("UPDATE assets SET status = 'disposed' WHERE asset_id = :id")->execute(['id' => $assetId]);
        return;
    }

    $underRepair = $pdo->prepare(
        "SELECT 1 FROM asset_maintenance WHERE asset_id = :id AND status IN ('pending','in_progress') LIMIT 1"
    );
    $underRepair->execute(['id' => $assetId]);
    if ($underRepair->fetch()) {
        $pdo->prepare("UPDATE assets SET status = 'under_repair' WHERE asset_id = :id")->execute(['id' => $assetId]);
        return;
    }

    $pdo->prepare("UPDATE assets SET status = 'active' WHERE asset_id = :id")->execute(['id' => $assetId]);
}

/** Simple required-field validator used by every add/edit handler. Returns list of error strings. */
function validateRequired(array $data, array $requiredFields): array
{
    $errors = [];
    foreach ($requiredFields as $field => $label) {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            $errors[] = "$label is required.";
        }
    }
    return $errors;
}
