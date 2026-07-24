<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';
requireRole('Admin');

ensureAuditLogTable();

$filter = $_GET['filter'] ?? '';
$where = "1=1";
if ($filter === 'low') {
    $where = "COALESCE(i.current_stock, 0) < p.reorder_level";
}

if (isset($_GET['add']) && isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    $check = $pdo->prepare("SELECT product_id FROM inventory WHERE product_id = ?");
    $check->execute([$product_id]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO inventory (product_id, current_stock, incoming_stock, last_updated) VALUES (?, 0, 0, GETDATE())")->execute([$product_id]);
        $_SESSION['toast'] = ['message' => 'Inventory record created. Add stock below.', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['message' => 'Inventory already exists for this product.', 'type' => 'warning'];
    }
    header("Location: inventory.php" . ($filter ? "?filter=$filter" : ""));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    $product_id = (int)$_POST['product_id'];
    $new_stock = max(0, (int)$_POST['current_stock']);
    $incoming = max(0, (int)$_POST['incoming_stock']);

    $oldStmt = $pdo->prepare("SELECT * FROM inventory WHERE product_id = ?");
    $oldStmt->execute([$product_id]);
    $oldData = $oldStmt->fetch();

    $check = $pdo->prepare("SELECT product_id FROM inventory WHERE product_id = ?");
    $check->execute([$product_id]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO inventory (product_id, current_stock, incoming_stock, last_updated) VALUES (?, 0, 0, GETDATE())")->execute([$product_id]);
    }

    $stmt = $pdo->prepare("UPDATE inventory SET current_stock = ?, incoming_stock = ?, last_updated = GETDATE() WHERE product_id = ?");
    $stmt->execute([$new_stock, $incoming, $product_id]);

    $pdo->prepare("INSERT INTO stock_history (product_id, transaction_type, quantity, reference, created_by) VALUES (?, 'ADJUST', ?, 'Manual update', ?)")
        ->execute([$product_id, $new_stock, $_SESSION['user_id']]);

    $newStmt = $pdo->prepare("SELECT * FROM inventory WHERE product_id = ?");
    $newStmt->execute([$product_id]);
    $newData = $newStmt->fetch();
    logAudit('inventory', $product_id, 'UPDATE', $oldData, $newData);

    $_SESSION['toast'] = ['message' => 'Inventory updated successfully.', 'type' => 'success'];
    header("Location: inventory.php" . ($filter ? "?filter=$filter" : ""));
    exit;
}

$inventory = $pdo->query("
    SELECT 
        p.product_id,
        p.product_code,
        p.product_name,
        p.reorder_level,
        COALESCE(i.current_stock, 0) AS current_stock,
        COALESCE(i.incoming_stock, 0) AS incoming_stock,
        i.last_updated,
        CASE WHEN i.product_id IS NULL THEN 0 ELSE 1 END AS has_inventory
    FROM products p
    LEFT JOIN inventory i ON p.product_id = i.product_id
    WHERE $where AND p.is_active = 1
    ORDER BY p.product_name
")->fetchAll();

include '../includes/header.php';
?>
<h2>Inventory Management</h2>

<div class="mb-3">
    <a href="inventory.php" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">All Items</a>
    <a href="inventory.php?filter=low" class="btn btn-sm <?= $filter === 'low' ? 'btn-danger' : 'btn-outline-danger' ?>">Low Stock Only</a>
</div>

<table class="table table-bordered datatable" id="inventoryTable">
    <thead>
        <tr>
            <th>Product Code</th>
            <th>Product Name</th>
            <th>Current Stock</th>
            <th>Incoming Stock</th>
            <th>Reorder Level</th>
            <th>Status</th>
            <th>Last Updated</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($inventory as $inv):
        $negativeStock = ($inv['current_stock'] < 0);
        $lowStock = ($inv['current_stock'] >= 0 && $inv['current_stock'] < $inv['reorder_level']);
        $rowClass = '';
        if ($negativeStock) {
            $rowClass = 'table-danger fw-bold';
        } elseif ($lowStock) {
            $rowClass = 'table-warning';
        }
        $hasInventory = $inv['has_inventory'];
    ?>
    <tr class="<?= $rowClass ?>">
        <td><?= htmlspecialchars($inv['product_code']) ?></td>
        <td><?= htmlspecialchars($inv['product_name']) ?></td>
        <td <?= $negativeStock ? 'style="color:red;font-weight:bold;"' : '' ?>><?= $inv['current_stock'] ?></td>
        <td><?= $inv['incoming_stock'] ?></td>
        <td><?= $inv['reorder_level'] ?></td>
        <td>
            <?php if ($negativeStock): ?>
                <span class="badge bg-danger fw-bold">NEGATIVE STOCK</span>
            <?php elseif ($lowStock): ?>
                <span class="badge bg-warning text-dark">Low Stock</span>
            <?php else: ?>
                <span class="badge bg-success">OK</span>
            <?php endif; ?>
        </td>
        <td><?= $hasInventory ? $inv['last_updated'] : 'N/A' ?></td>
        <td>
            <?php if ($hasInventory): ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?= $inv['product_id'] ?>">Update</button>
                <div class="modal fade" id="updateModal<?= $inv['product_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="product_id" value="<?= $inv['product_id'] ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Inventory: <?= htmlspecialchars($inv['product_name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Current Stock</label>
                                        <input type="number" name="current_stock" class="form-control" value="<?= $inv['current_stock'] ?>" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Incoming Stock</label>
                                        <input type="number" name="incoming_stock" class="form-control" value="<?= $inv['incoming_stock'] ?>" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Reorder Level (readonly)</label>
                                        <input type="text" class="form-control" value="<?= $inv['reorder_level'] ?>" readonly>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_stock" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="inventory.php?add=1&product_id=<?= $inv['product_id'] ?><?= $filter ? "&filter=$filter" : "" ?>" class="btn btn-sm btn-success">Add Inventory</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function() {
    $('#inventoryTable').DataTable({
        "pageLength": 25,
        "order": [[1, "asc"]]
    });
});
</script>

<?php include '../includes/footer.php'; ?>