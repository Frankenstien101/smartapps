<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';
requireRole('SalesRep');

// Ensure user_stores table exists
ensureUserStoresTable();

// Generate new call sheet
if (isset($_POST['generate'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");
    
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    if ($customer_id == 0) {
        $_SESSION['toast'] = ['message' => 'Please select a store.', 'type' => 'warning'];
        header("Location: call_sheet.php");
        exit;
    }

    // Security: verify this store is assigned to the SalesRep
    if (!in_array($customer_id, $_SESSION['assigned_stores'] ?? [])) {
        $_SESSION['toast'] = ['message' => 'You are not assigned to this store.', 'type' => 'danger'];
        header("Location: call_sheet.php");
        exit;
    }

    $sheet_number = generateCode('CS');
    $stmt = $pdo->prepare("INSERT INTO call_sheets (sheet_number, created_by, customer_id, status) VALUES (?, ?, ?, 'Draft')");
    $stmt->execute([$sheet_number, $_SESSION['user_id'], $customer_id]);
    $sheet_id = $pdo->lastInsertId();

    $products = $pdo->query("SELECT product_id, cost_price FROM products WHERE is_active=1")->fetchAll();
    foreach ($products as $prod) {
        $suggestion = getSuggestedOrder($prod['product_id'], $customer_id);
        if ($suggestion && $suggestion['suggested_qty'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO call_sheet_items (call_sheet_id, product_id, suggested_qty, final_qty, reason, unit_cost)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $sheet_id,
                $prod['product_id'],
                $suggestion['suggested_qty'],
                $suggestion['suggested_qty'],
                $suggestion['reason'],
                $prod['cost_price']
            ]);
        }
    }
    logCallSheetAction($sheet_id, 'GENERATE');
    $_SESSION['toast'] = ['message' => 'New call sheet generated successfully!', 'type' => 'success'];
    header("Location: call_sheet_edit.php?id=$sheet_id");
    exit;
}

// Fetch only stores assigned to this SalesRep
$assignedStoreIds = $_SESSION['assigned_stores'] ?? [];
if (empty($assignedStoreIds)) {
    $stores = [];
} else {
    $placeholders = implode(',', array_fill(0, count($assignedStoreIds), '?'));
    $stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customers WHERE is_active=1 AND customer_id IN ($placeholders) ORDER BY customer_name");
    $stmt->execute($assignedStoreIds);
    $stores = $stmt->fetchAll();
}

// Filtering – only show sheets created by this SalesRep AND for assigned stores
$where = "1=1";
$params = [];
if (!empty($_GET['status'])) {
    $where .= " AND cs.status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $where .= " AND cs.created_date >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where .= " AND cs.created_date <= ?";
    $params[] = $_GET['date_to'];
}
$where .= " AND cs.created_by = ?";
$params[] = $_SESSION['user_id'];

// Additional security: only show sheets for assigned stores
if (!empty($assignedStoreIds)) {
    $placeholders2 = implode(',', array_fill(0, count($assignedStoreIds), '?'));
    $where .= " AND cs.customer_id IN ($placeholders2)";
    $params = array_merge($params, $assignedStoreIds);
} else {
    // If no stores assigned, show no sheets (or show empty)
    $where .= " AND 1=0"; // force no results
}

$sql = "
    SELECT cs.*, u.full_name AS creator, c.customer_name AS store_name
    FROM call_sheets cs
    JOIN users u ON cs.created_by = u.user_id
    LEFT JOIN customers c ON cs.customer_id = c.customer_id
    WHERE $where
    ORDER BY cs.created_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sheets = $stmt->fetchAll();

include '../includes/header.php';
?>
<h2>Call Sheets</h2>

<!-- Filter Form -->
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="Draft" <?= isset($_GET['status']) && $_GET['status'] == 'Draft' ? 'selected' : '' ?>>Draft</option>
            <option value="Submitted" <?= isset($_GET['status']) && $_GET['status'] == 'Submitted' ? 'selected' : '' ?>>Submitted</option>
            <option value="Approved" <?= isset($_GET['status']) && $_GET['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
            <option value="Converted" <?= isset($_GET['status']) && $_GET['status'] == 'Converted' ? 'selected' : '' ?>>Converted</option>
            <option value="Rejected" <?= isset($_GET['status']) && $_GET['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $_GET['date_from'] ?? '' ?>" placeholder="From">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $_GET['date_to'] ?? '' ?>" placeholder="To">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="call_sheet.php" class="btn btn-sm btn-secondary">Clear</a>
    </div>
</form>

<!-- Generate New Call Sheet with Store Selection -->
<?php if (empty($stores)): ?>
    <div class="alert alert-warning">You have no stores assigned. Please contact your administrator.</div>
<?php else: ?>
    <form method="post" class="mb-3 row g-2">
        <?= csrfField() ?>
        <div class="col-auto">
            <label class="visually-hidden">Store</label>
            <select name="customer_id" class="form-select form-select-sm" required>
                <option value="">Select Store...</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?= $store['customer_id'] ?>"><?= htmlspecialchars($store['customer_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" name="generate" class="btn btn-success">Generate New Call Sheet</button>
        </div>
    </form>
<?php endif; ?>

<!-- Call Sheets Table -->
<table class="table table-bordered datatable">
    <thead>
        <tr><th>Sheet #</th><th>Store</th><th>Created</th><th>Created By</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($sheets as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['sheet_number']) ?></td>
            <td><?= htmlspecialchars($s['store_name'] ?? 'N/A') ?></td>
            <td><?= $s['created_date'] ?></td>
            <td><?= htmlspecialchars($s['creator']) ?></td>
            <td><span class="badge bg-<?= $s['status'] == 'Draft' ? 'secondary' : ($s['status'] == 'Submitted' ? 'warning' : ($s['status'] == 'Approved' ? 'success' : ($s['status'] == 'Converted' ? 'info' : 'danger'))) ?>"><?= $s['status'] ?></span></td>
            <td>
                <?php if ($s['status'] == 'Draft'): ?>
                    <a href="call_sheet_edit.php?id=<?= $s['call_sheet_id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <?php elseif ($s['status'] == 'Submitted'): ?>
                    <span class="text-muted">Waiting for approval</span>
                <?php elseif ($s['status'] == 'Approved'): ?>
                    <a href="purchase_order.php?sheet_id=<?= $s['call_sheet_id'] ?>" class="btn btn-sm btn-success">Convert to PO</a>
                <?php elseif ($s['status'] == 'Converted'): ?>
                    <span class="text-muted">PO created</span>
                <?php endif; ?>
                <a href="call_sheet_view.php?id=<?= $s['call_sheet_id'] ?>" class="btn btn-sm btn-info">View</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>