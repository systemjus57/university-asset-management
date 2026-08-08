<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Access Denied</title>
<link rel="stylesheet" href="<?= APP_URL ?>/static/css/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card" style="text-align:center;">
        <h1 style="color:var(--color-danger);">403 — Access Denied</h1>
        <p>You do not have permission to view this page with your current role.</p>
        <a class="btn btn-primary" href="<?= APP_URL ?>/modules/dashboard/index.php">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
