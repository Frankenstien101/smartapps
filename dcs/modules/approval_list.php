<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Buyer');

$buyer_store_id = $_SESSION['customer_id'] ?? null;

$sql = "
    SELECT cs.*, u.full_name AS creator, c.customer_name AS store_name
    FROM call_sheets cs
    JOIN users u ON cs.created_by = u.user_id
    LEFT JOIN customers c ON cs.customer_id = c.customer_id
    WHERE cs.status = 'Submitted'
";
if ($buyer_store_id) {
    $sql .= " AND cs.customer_id = ?";
    $params = [$buyer_store_id];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query($sql);
}
$sheets = $stmt->fetchAll();

include '../includes/header.php';
?>
<h2>Pending Approvals</h2>
<?php if (count($sheets) == 0): ?>
    <div class="alert alert-info">No pending approvals<?= $buyer_store_id ? ' for your store.' : '.' ?></div>
<?php else: ?>
    <table class="table table-bordered datatable">
        <thead>
            <tr><th>Sheet #</th><th>Store</th><th>Created</th><th>Created By</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($sheets as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['sheet_number']) ?></td>
                <td><?= htmlspecialchars($s['store_name'] ?? 'N/A') ?></td>
                <td><?= $s['created_date'] ?></td>
                <td><?= htmlspecialchars($s['creator']) ?></td>
                <td><a href="approval.php?id=<?= $s['call_sheet_id'] ?>" class="btn btn-sm btn-primary">Review</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>