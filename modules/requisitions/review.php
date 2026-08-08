<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$id = (int) ($_GET['id'] ?? $_POST['requisition_id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT r.*, d.department_name, c.category_name, u.name AS requester_name
     FROM requisitions r
     LEFT JOIN departments d ON d.department_id = r.department_id
     LEFT JOIN categories c ON c.category_id = r.category_id
     LEFT JOIN users u ON u.user_id = r.requester_id
     WHERE r.requisition_id = :id'
);
$stmt->execute(['id' => $id]);
$req = $stmt->fetch();

if (!$req) {
    flash('error', 'Requisition not found.');
    redirect(APP_URL . '/modules/requisitions/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $decision = $_POST['decision'] ?? '';

    $allowedTransitions = [
        'pending'  => ['approved', 'rejected'],
        'approved' => ['issued'],
    ];
    if (!in_array($decision, $allowedTransitions[$req['status']] ?? [], true)) {
        $errors[] = 'Invalid action for the current status of this requisition.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE requisitions SET status = :status, reviewed_by = :reviewed_by, reviewed_date = :reviewed_date WHERE requisition_id = :id'
        );
        $stmt->execute([
            'status'        => $decision,
            'reviewed_by'   => $_SESSION['user_id'],
            'reviewed_date' => date('Y-m-d'),
            'id'            => $id,
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'Review Requisition', 'requisitions', "Requisition #$id set to $decision.");
        flash('success', "Requisition marked as $decision.");
        redirect(APP_URL . '/modules/requisitions/list.php');
    }
}

$pageTitle  = 'Review Requisition';
$activeMenu = 'requisitions';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card" style="max-width:640px;">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="detail-grid mb-0">
        <div class="detail-item"><div class="detail-label">Department</div><div class="detail-value"><?= e($req['department_name'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Requester</div><div class="detail-value"><?= e($req['requester_name'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Category</div><div class="detail-value"><?= e($req['category_name'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Quantity</div><div class="detail-value"><?= (int) $req['quantity'] ?></div></div>
        <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value"><?= formatDate($req['requisition_date']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><?= statusBadge($req['status']) ?></div></div>
    </div>
    <p class="mt-2"><strong>Purpose:</strong> <?= e($req['purpose']) ?></p>
    <?php if (!empty($req['description'])): ?><p><strong>Details:</strong> <?= nl2br(e($req['description'])) ?></p><?php endif; ?>

    <form method="post" action="" class="mt-2 d-flex gap-1 flex-wrap">
        <?= csrfField() ?>
        <input type="hidden" name="requisition_id" value="<?= (int) $req['requisition_id'] ?>">
        <?php if ($req['status'] === 'pending'): ?>
            <button type="submit" name="decision" value="approved" class="btn btn-primary" data-confirm="Approve this requisition?">Approve</button>
            <button type="submit" name="decision" value="rejected" class="btn btn-danger" data-confirm="Reject this requisition?">Reject</button>
        <?php elseif ($req['status'] === 'approved'): ?>
            <button type="submit" name="decision" value="issued" class="btn btn-accent" data-confirm="Mark this requisition as issued?">Mark Issued</button>
        <?php else: ?>
            <p class="text-muted">No further action available for this requisition.</p>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/modules/requisitions/list.php" class="btn btn-outline">Back</a>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
