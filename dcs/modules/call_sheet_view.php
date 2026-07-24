<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

$sheet_id = $_GET['id'] ?? 0;
if (!$sheet_id) die("Invalid sheet.");

// Fetch sheet with store and creator info
$stmt = $pdo->prepare("
    SELECT cs.*, u.full_name AS creator, ub.full_name AS approver, c.customer_name AS store_name
    FROM call_sheets cs
    JOIN users u ON cs.created_by = u.user_id
    LEFT JOIN users ub ON cs.approved_by = ub.user_id
    LEFT JOIN customers c ON cs.customer_id = c.customer_id
    WHERE cs.call_sheet_id = ?
");
$stmt->execute([$sheet_id]);
$sheet = $stmt->fetch();
if (!$sheet) die("Sheet not found.");

// Permission checks
if ($_SESSION['role'] === 'SalesRep') {
    // SalesRep can only view their own sheets AND only if the store is assigned
    if ($sheet['created_by'] != $_SESSION['user_id']) {
        die("You do not have permission to view this sheet.");
    }
    if (!in_array($sheet['customer_id'], $_SESSION['assigned_stores'] ?? [])) {
        die("You are not assigned to this store.");
    }
} elseif ($_SESSION['role'] === 'Buyer') {
    // Buyer can only view sheets for their assigned store
    if ($sheet['customer_id'] != $_SESSION['customer_id']) {
        die("You do not have permission to view this sheet.");
    }
}
// Admin can view everything

$itemsStmt = $pdo->prepare("
    SELECT ci.*, p.product_name, p.product_code
    FROM call_sheet_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.call_sheet_id = ?
    ORDER BY p.product_name
");
$itemsStmt->execute([$sheet_id]);
$items = $itemsStmt->fetchAll();

$total_cost = 0;
foreach ($items as $it) {
    $total_cost += $it['final_qty'] * $it['unit_cost'];
}

include '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center">
    <h2>Call Sheet: <?= htmlspecialchars($sheet['sheet_number']) ?></h2>
    <a href="call_sheet.php" class="btn btn-secondary">Back</a>
</div>
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <strong>Status:</strong>
                <span class="badge bg-<?= $sheet['status'] == 'Draft' ? 'secondary' : ($sheet['status'] == 'Submitted' ? 'warning' : ($sheet['status'] == 'Approved' ? 'success' : ($sheet['status'] == 'Converted' ? 'info' : 'danger'))) ?>">
                    <?= htmlspecialchars($sheet['status']) ?>
                </span>
                <br>
                <strong>Store:</strong> <?= htmlspecialchars($sheet['store_name'] ?? 'N/A') ?>
            </div>
            <div class="col-md-4">
                <strong>Created By:</strong> <?= htmlspecialchars($sheet['creator']) ?><br>
                <strong>Created Date:</strong> <?= $sheet['created_date'] ?>
            </div>
            <div class="col-md-4">
                <?php if ($sheet['approved_by']): ?>
                    <strong>Reviewed By:</strong> <?= htmlspecialchars($sheet['approver']) ?><br>
                    <strong>Reviewed Date:</strong> <?= $sheet['approved_date'] ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($sheet['buyer_remarks'])): ?>
            <div class="mt-3">
                <strong>Buyer Remarks:</strong>
                <p><?= nl2br(htmlspecialchars($sheet['buyer_remarks'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<table class="table table-bordered datatable">
    <thead>
        <tr><th>Product Code</th><th>Product</th><th>Suggested Qty</th><th>Final Qty</th><th>Reason</th><th>Unit Cost</th><th>Line Total</th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['product_code']) ?></td>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td><?= $item['suggested_qty'] ?></td>
            <td><?= $item['final_qty'] ?></td>
            <td><?= htmlspecialchars($item['reason']) ?></td>
            <td><?= number_format($item['unit_cost'], 2) ?></td>
            <td><?= number_format($item['final_qty'] * $item['unit_cost'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="6" class="text-end">Total</th><th><?= number_format($total_cost, 2) ?></th></tr>
    </tfoot>
</table>
<?php include '../includes/footer.php'; ?>