<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_TOPMGMT]);

$id = (int) ($_GET['id'] ?? $_POST['disposal_id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT ds.*, a.name AS asset_name, u.name AS requested_by_name
     FROM asset_disposals ds
     JOIN assets a ON a.asset_id = ds.asset_id
     LEFT JOIN users u ON u.user_id = ds.requested_by
     WHERE ds.disposal_id = :id'
);
$stmt->execute(['id' => $id]);
$disposal = $stmt->fetch();

if (!$disposal) {
    flash('error', 'Disposal request not found.');
    redirect(APP_URL . '/modules/disposals/list.php');
}
if ($disposal['status'] !== 'pending') {
    flash('info', 'This disposal request has already been reviewed.');
    redirect(APP_URL . '/modules/disposals/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $decision = $_POST['decision'] ?? '';
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        $errors[] = 'Invalid decision.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE asset_disposals SET status = :status, approved_by = :approved_by, approved_date = :approved_date,
                 disposal_date = :disposal_date WHERE disposal_id = :id'
            );
            $stmt->execute([
                'status'        => $decision,
                'approved_by'   => $_SESSION['user_id'],
                'approved_date' => date('Y-m-d'),
                'disposal_date' => $decision === 'approved' ? date('Y-m-d') : null,
                'id'            => $id,
            ]);
            if ($decision === 'approved') {
                recomputeAssetStatus($pdo, (int) $disposal['asset_id']);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        logActivity($pdo, $_SESSION['user_id'], 'Review Disposal', 'disposals', "Disposal #$id set to $decision.");
        flash('success', "Disposal request $decision.");
        redirect(APP_URL . '/modules/disposals/list.php');
    }
}

$pageTitle  = 'Review Disposal Request';
$activeMenu = 'disposals';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card" style="max-width:640px;">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="detail-grid mb-0">
        <div class="detail-item"><div class="detail-label">Asset</div><div class="detail-value"><?= e($disposal['asset_name']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Requested By</div><div class="detail-value"><?= e($disposal['requested_by_name'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Method</div><div class="detail-value"><?= e(ucfirst($disposal['method'])) ?></div></div>
        <div class="detail-item"><div class="detail-label">Requested On</div><div class="detail-value"><?= formatDate($disposal['request_date']) ?></div></div>
    </div>
    <p class="mt-2"><strong>Reason:</strong> <?= e($disposal['reason']) ?></p>

    <form method="post" action="" class="mt-2 d-flex gap-1">
        <?= csrfField() ?>
        <input type="hidden" name="disposal_id" value="<?= (int) $disposal['disposal_id'] ?>">
        <button type="submit" name="decision" value="approved" class="btn btn-primary" data-confirm="Approve disposal of this asset? This will mark it as disposed.">Approve Disposal</button>
        <button type="submit" name="decision" value="rejected" class="btn btn-danger" data-confirm="Reject this disposal request?">Reject</button>
        <a href="<?= APP_URL ?>/modules/disposals/list.php" class="btn btn-outline">Back</a>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
