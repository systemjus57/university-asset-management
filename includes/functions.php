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

/**
 * Notification list for the topbar bell: items awaiting the current user's
 * action (role-scoped pending counts) plus recent decisions on things they
 * personally submitted (their own requisitions/disposals/maintenance
 * reports), so a submitter finds out what happened to their request and
 * not just approvers seeing what's queued up.
 */
function getPendingAlerts(PDO $pdo): array
{
    if (!isLoggedIn()) {
        return [];
    }

    $alerts = [];
    $isHead = hasRole([ROLE_HEAD]);
    $deptId = $_SESSION['department_id'] ?? null;
    $userId = (int) $_SESSION['user_id'];

    // English pluralizes with a trailing 's'; Somali nouns don't inflect for count here, so the suffix is skipped.
    $plural = fn (int $count) => (activeLanguage() === 'en' && $count !== 1) ? 's' : '';

    // ---- Pending items awaiting this user's action (role-scoped counts) ----
    if ($isHead) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM asset_maintenance m JOIN assets a ON a.asset_id = m.asset_id
             WHERE m.status IN ('pending','in_progress') AND a.department_id = :dept"
        );
        $stmt->execute(['dept' => $deptId]);
        $maintenanceCount = (int) $stmt->fetchColumn();
    } else {
        $maintenanceCount = (int) $pdo->query("SELECT COUNT(*) FROM asset_maintenance WHERE status IN ('pending','in_progress')")->fetchColumn();
    }
    if ($maintenanceCount > 0) {
        $alerts[] = ['count' => $maintenanceCount, 'text' => $maintenanceCount . ' ' . t('alert.maintenance') . $plural($maintenanceCount), 'url' => APP_URL . '/modules/maintenance/list.php'];
    }

    if (hasRole([ROLE_ADMIN, ROLE_OFFICER, ROLE_TOPMGMT])) {
        $disposalCount = (int) $pdo->query("SELECT COUNT(*) FROM asset_disposals WHERE status = 'pending'")->fetchColumn();
        if ($disposalCount > 0) {
            $alerts[] = ['count' => $disposalCount, 'text' => $disposalCount . ' ' . t('alert.disposal') . $plural($disposalCount), 'url' => APP_URL . '/modules/disposals/list.php'];
        }
    }

    if (hasRole([ROLE_ADMIN, ROLE_OFFICER])) {
        $requisitionCount = (int) $pdo->query("SELECT COUNT(*) FROM requisitions WHERE status = 'pending'")->fetchColumn();
    } elseif ($isHead) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requisitions WHERE status = 'pending' AND department_id = :dept");
        $stmt->execute(['dept' => $deptId]);
        $requisitionCount = (int) $stmt->fetchColumn();
    } else {
        $requisitionCount = 0;
    }
    if ($requisitionCount > 0) {
        $alerts[] = ['count' => $requisitionCount, 'text' => $requisitionCount . ' ' . t('alert.requisition') . $plural($requisitionCount), 'url' => APP_URL . '/modules/requisitions/list.php'];
    }

    // ---- Recent decisions on things this user personally submitted (last 7 days) ----
    $stmt = $pdo->prepare(
        "SELECT r.status, c.category_name FROM requisitions r
         LEFT JOIN categories c ON c.category_id = r.category_id
         WHERE r.requester_id = :uid AND r.status IN ('approved','rejected','issued')
           AND r.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY r.updated_at DESC LIMIT 5"
    );
    $stmt->execute(['uid' => $userId]);
    foreach ($stmt->fetchAll() as $r) {
        $subject = $r['category_name'] ?? t('nav.requisitions');
        $alerts[] = ['count' => 1, 'text' => sprintf(t('alert.requisition.' . $r['status']), $subject), 'url' => APP_URL . '/modules/requisitions/list.php'];
    }

    $stmt = $pdo->prepare(
        "SELECT ds.status, a.name AS asset_name FROM asset_disposals ds
         JOIN assets a ON a.asset_id = ds.asset_id
         WHERE ds.requested_by = :uid AND ds.status IN ('approved','rejected')
           AND ds.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY ds.updated_at DESC LIMIT 5"
    );
    $stmt->execute(['uid' => $userId]);
    foreach ($stmt->fetchAll() as $r) {
        $alerts[] = ['count' => 1, 'text' => sprintf(t('alert.disposal.' . $r['status']), $r['asset_name']), 'url' => APP_URL . '/modules/disposals/list.php'];
    }

    $stmt = $pdo->prepare(
        "SELECT a.name AS asset_name FROM asset_maintenance m
         JOIN assets a ON a.asset_id = m.asset_id
         WHERE m.reported_by = :uid AND m.status = 'completed'
           AND m.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY m.updated_at DESC LIMIT 5"
    );
    $stmt->execute(['uid' => $userId]);
    foreach ($stmt->fetchAll() as $r) {
        $alerts[] = ['count' => 1, 'text' => sprintf(t('alert.maintenance.completed'), $r['asset_name']), 'url' => APP_URL . '/modules/maintenance/list.php'];
    }

    return $alerts;
}

/**
 * Runs one of the Reports module's five report queries and returns flat,
 * display-ready rows. Shared by the CSV and PDF exporters so the query
 * logic (and its department scoping) lives in exactly one place.
 */
function getReportRows(PDO $pdo, string $report, bool $isHead, ?int $deptId, string $dateFrom, string $dateTo): array
{
    $labels = [
        'by_department'    => 'Assets by Department',
        'by_category'      => 'Assets by Category',
        'by_status'        => 'Assets by Status',
        'maintenance_cost' => 'Maintenance Cost Report',
        'disposals'        => 'Disposal Report',
    ];
    $label = $labels[$report] ?? $labels['by_department'];

    $header = [];
    $rows   = [];

    if ($report === 'by_department') {
        $header = ['Department', 'Asset Count', 'Total Value'];
        $sql = "SELECT d.department_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.purchase_cost),0) AS total_value
                FROM departments d LEFT JOIN assets a ON a.department_id = d.department_id
                " . ($isHead ? 'WHERE d.department_id = :dept' : '') . "
                GROUP BY d.department_id, d.department_name ORDER BY d.department_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isHead ? ['dept' => $deptId] : []);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['department_name'], (string) $r['asset_count'], number_format((float) $r['total_value'], 2)];
        }
    } elseif ($report === 'by_category') {
        $header = ['Category', 'Asset Count', 'Total Value'];
        $where = $isHead ? 'WHERE a.department_id = :dept' : '';
        $sql = "SELECT c.category_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.purchase_cost),0) AS total_value
                FROM categories c LEFT JOIN assets a ON a.category_id = c.category_id $where
                GROUP BY c.category_id, c.category_name ORDER BY c.category_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isHead ? ['dept' => $deptId] : []);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['category_name'], (string) $r['asset_count'], number_format((float) $r['total_value'], 2)];
        }
    } elseif ($report === 'by_status') {
        $header = ['Status', 'Asset Count', 'Total Value'];
        $where = $isHead ? 'WHERE department_id = :dept' : '';
        $sql = "SELECT status, COUNT(*) AS asset_count, COALESCE(SUM(purchase_cost),0) AS total_value FROM assets $where GROUP BY status";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isHead ? ['dept' => $deptId] : []);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [ucwords(str_replace('_', ' ', $r['status'])), (string) $r['asset_count'], number_format((float) $r['total_value'], 2)];
        }
    } elseif ($report === 'maintenance_cost') {
        $header = ['Asset', 'Completed Date', 'Cost', 'Technician/Vendor'];
        $where = ["m.status = 'completed'"];
        $params = [];
        if ($isHead) { $where[] = 'a.department_id = :dept'; $params['dept'] = $deptId; }
        if ($dateFrom !== '') { $where[] = 'm.completed_date >= :from'; $params['from'] = $dateFrom; }
        if ($dateTo !== '')   { $where[] = 'm.completed_date <= :to';   $params['to'] = $dateTo; }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT a.name AS asset_name, m.completed_date, m.cost, m.technician_vendor
                FROM asset_maintenance m JOIN assets a ON a.asset_id = m.asset_id $whereSql ORDER BY m.completed_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['asset_name'], formatDate($r['completed_date']), number_format((float) $r['cost'], 2), $r['technician_vendor'] ?? '—'];
        }
    } elseif ($report === 'disposals') {
        $header = ['Asset', 'Method', 'Status', 'Requested Date', 'Disposal Date', 'Reason'];
        $where = [];
        $params = [];
        if ($isHead) { $where[] = 'a.department_id = :dept'; $params['dept'] = $deptId; }
        if ($dateFrom !== '') { $where[] = 'ds.request_date >= :from'; $params['from'] = $dateFrom; }
        if ($dateTo !== '')   { $where[] = 'ds.request_date <= :to';   $params['to'] = $dateTo; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT a.name AS asset_name, ds.method, ds.status, ds.request_date, ds.disposal_date, ds.reason
                FROM asset_disposals ds JOIN assets a ON a.asset_id = ds.asset_id $whereSql ORDER BY ds.request_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['asset_name'], ucfirst($r['method']), ucfirst($r['status']), formatDate($r['request_date']), formatDate($r['disposal_date']), $r['reason'] ?? '—'];
        }
    }

    return ['label' => $label, 'header' => $header, 'rows' => $rows];
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
