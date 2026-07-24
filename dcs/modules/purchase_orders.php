<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
requireLogin();

$buyer_store_id = (isset($_SESSION['role']) && $_SESSION['role'] === 'Buyer') ? ($_SESSION['customer_id'] ?? null) : null;

// Filter inputs
$filter_store = $_GET['store'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$where = "1=1";
$params = [];

if ($buyer_store_id) {
    $where .= " AND cs.customer_id = ?";
    $params[] = $buyer_store_id;
}
if ($filter_store) {
    $where .= " AND cs.customer_id = ?";
    $params[] = $filter_store;
}
if ($filter_status) {
    $where .= " AND po.status = ?";
    $params[] = $filter_status;
}
if ($filter_date_from) {
    $where .= " AND po.order_date >= ?";
    $params[] = $filter_date_from;
}
if ($filter_date_to) {
    $where .= " AND po.order_date <= ?";
    $params[] = $filter_date_to . ' 23:59:59';
}

$sql = "
    SELECT po.*, u.full_name AS creator_name, c.customer_name AS store_name
    FROM purchase_orders po
    JOIN users u ON po.created_by = u.user_id
    LEFT JOIN call_sheets cs ON po.call_sheet_id = cs.call_sheet_id
    LEFT JOIN customers c ON cs.customer_id = c.customer_id
    WHERE $where
    ORDER BY po.order_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pos = $stmt->fetchAll();

// For dropdowns
$stores = $pdo->query("SELECT customer_id, customer_name FROM customers WHERE is_active=1 ORDER BY customer_name")->fetchAll();

include '../includes/header.php';
?>
<h2>Purchase Orders</h2>

<!-- Filter Form -->
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="store" class="form-select form-select-sm">
            <option value="">All Stores</option>
            <?php foreach ($stores as $s): ?>
                <option value="<?= $s['customer_id'] ?>" <?= $filter_store == $s['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['customer_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Completed" <?= $filter_status == 'Completed' ? 'selected' : '' ?>>Completed</option>
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date_from) ?>" placeholder="From">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date_to) ?>" placeholder="To">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="purchase_orders.php" class="btn btn-sm btn-secondary">Clear</a>
    </div>
</form>

<?php if (empty($pos)): ?>
    <div class="alert alert-info">No purchase orders found.</div>
<?php else: ?>
    <table class="table table-bordered datatable">
        <thead>
            <tr><th>PO #</th><th>Store</th><th>Order Date</th><th>Created By</th><th>Status</th><th>Total Amount</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pos as $po): ?>
            <tr>
                <td><?= htmlspecialchars($po['po_number']) ?></td>
                <td><?= htmlspecialchars($po['store_name'] ?? 'N/A') ?></td>
                <td><?= $po['order_date'] ?></td>
                <td><?= htmlspecialchars($po['creator_name']) ?></td>
                <td><span class="badge bg-<?= $po['status'] == 'Pending' ? 'warning' : 'success' ?>"><?= htmlspecialchars($po['status']) ?></span></td>
                <td><?= number_format($po['total_amount'], 2) ?></td>
                <td><a href="po_view.php?id=<?= $po['po_id'] ?>" class="btn btn-sm btn-primary">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>