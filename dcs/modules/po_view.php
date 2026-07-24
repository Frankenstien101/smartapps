<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';

// Allow Buyer, SalesRep, and Admin
if (!isLoggedIn() || !in_array($_SESSION['role'], ['Buyer', 'SalesRep', 'Admin'])) {
    header("Location: ../login.php");
    exit;
}

$po_id = $_GET['id'] ?? 0;
if (!$po_id) die("Invalid PO.");

// Handle status change to Completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");
    // Only Admin or Buyer can complete
    if (!in_array($_SESSION['role'], ['Admin', 'Buyer'])) {
        $_SESSION['toast'] = ['message' => 'You do not have permission to complete this PO.', 'type' => 'danger'];
        header("Location: po_view.php?id=$po_id");
        exit;
    }
    $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Completed' WHERE po_id = ? AND status = 'Pending'");
    $stmt->execute([$po_id]);
    if ($stmt->rowCount() > 0) {
        logAudit('purchase_orders', $po_id, 'UPDATE', ['status' => 'Pending'], ['status' => 'Completed']);
        $_SESSION['toast'] = ['message' => 'Purchase Order marked as Completed.', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['message' => 'PO already completed or not found.', 'type' => 'warning'];
    }
    header("Location: po_view.php?id=$po_id");
    exit;
}

$stmt = $pdo->prepare("
    SELECT po.*, u.full_name AS creator_name, c.customer_name AS store_name
    FROM purchase_orders po
    JOIN users u ON po.created_by = u.user_id
    LEFT JOIN call_sheets cs ON po.call_sheet_id = cs.call_sheet_id
    LEFT JOIN customers c ON cs.customer_id = c.customer_id
    WHERE po.po_id = ?
");
$stmt->execute([$po_id]);
$po = $stmt->fetch();
if (!$po) die("PO not found.");

$itemsStmt = $pdo->prepare("
    SELECT pi.*, p.product_name, p.product_code
    FROM po_items pi
    JOIN products p ON pi.product_id = p.product_id
    WHERE pi.po_id = ?
");
$itemsStmt->execute([$po_id]);
$items = $itemsStmt->fetchAll();

include '../includes/header.php';
?>
<div class="d-flex justify-content-between">
    <h2>Purchase Order #<?= htmlspecialchars($po['po_number']) ?></h2>
    <div>
        <button class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="purchase_orders.php" class="btn btn-secondary">Back</a>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Order Date:</strong> <?= $po['order_date'] ?><br>
                <strong>Status:</strong> 
                <span class="badge bg-<?= $po['status'] == 'Pending' ? 'warning' : 'success' ?>">
                    <?= htmlspecialchars($po['status']) ?>
                </span>
                <br>
                <strong>Created By:</strong> <?= htmlspecialchars($po['creator_name']) ?>
            </div>
            <div class="col-md-4">
                <strong>Store:</strong> <?= htmlspecialchars($po['store_name'] ?? 'N/A') ?>
            </div>
            <div class="col-md-4 text-end">
                <strong>Total Amount:</strong> <?= number_format($po['total_amount'], 2) ?>
            </div>
        </div>

        <?php if ($po['status'] === 'Pending' && in_array($_SESSION['role'], ['Admin', 'Buyer'])): ?>
            <form method="post" class="mb-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn btn-success" onclick="return confirm('Mark this Purchase Order as Completed?')">
                    <i class="bi bi-check-circle"></i> Mark as Completed
                </button>
            </form>
        <?php endif; ?>

        <table class="table table-bordered">
            <thead>
                <tr><th>Product Code</th><th>Product Name</th><th>Quantity</th><th>Unit Cost</th><th>Line Total</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= htmlspecialchars($it['product_code']) ?></td>
                    <td><?= htmlspecialchars($it['product_name']) ?></td>
                    <td><?= $it['quantity'] ?></td>
                    <td><?= number_format($it['unit_cost'], 2) ?></td>
                    <td><?= number_format($it['line_total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="4" class="text-end">Total</th><th><?= number_format($po['total_amount'], 2) ?></th></tr>
            </tfoot>
        </table>
        <?php if ($po['call_sheet_id']): ?>
            <small>Generated from Call Sheet #<?= $po['call_sheet_id'] ?></small>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>