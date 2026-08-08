<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/modules/assigned/list.php');
}
requireCsrf();

$assignId = (int) ($_POST['assign_id'] ?? 0);
$outcome  = $_POST['outcome'] ?? 'returned';
if (!in_array($outcome, ['returned', 'repair'], true)) {
    $outcome = 'returned';
}

$stmt = $pdo->prepare('SELECT * FROM asset_assigned WHERE assign_id = :id AND status = "active"');
$stmt->execute(['id' => $assignId]);
$row = $stmt->fetch();

if ($row) {
    $pdo->prepare('UPDATE asset_assigned SET return_date = :d, status = :status WHERE assign_id = :id')
        ->execute(['d' => date('Y-m-d'), 'status' => $outcome, 'id' => $assignId]);
    logActivity($pdo, $_SESSION['user_id'], 'Update Allocation', 'assigned', "Allocation #$assignId marked as $outcome.");
    flash('success', 'Allocation record updated.');
} else {
    flash('error', 'Allocation record not found or already closed.');
}

redirect(APP_URL . '/modules/assigned/list.php');
