<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
require_once '../includes/csrf.php';
requireRole('Admin');

ensureAuditLogTable();

$action = $_GET['action'] ?? 'list';

// ---------- Export CSV ----------
if (isset($_GET['export'])) {
    $products = $pdo->query("SELECT * FROM products ORDER BY product_name")->fetchAll();
    $headers = ['product_id', 'product_code', 'product_name', 'brand', 'category', 'unit_of_measure', 'cost_price', 'selling_price', 'reorder_level', 'lead_time_days', 'safety_stock', 'is_active'];
    exportToCSV($products, $headers, 'products.csv');
}

// ---------- Import CSV ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    if ($handle === false) {
        $_SESSION['toast'] = ['message' => 'Could not open CSV file.', 'type' => 'danger'];
        header("Location: products.php");
        exit;
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        $_SESSION['toast'] = ['message' => 'Invalid CSV file format.', 'type' => 'danger'];
        header("Location: products.php");
        exit;
    }

    $rowCount = 0;
    $errors = [];
    while (($row = fgetcsv($handle)) !== FALSE) {
        $rowCount++;
        if (count($row) < 11) {
            $errors[] = "Row $rowCount: insufficient columns.";
            continue;
        }
        $product_code = trim($row[0]);
        $product_name = trim($row[1]);
        $brand = trim($row[2]);
        $category = trim($row[3]);
        $unit_of_measure = trim($row[4]);
        $cost_price = (float) $row[5];
        $selling_price = (float) $row[6];
        $reorder_level = (int) $row[7];
        $lead_time_days = (int) $row[8];
        $safety_stock = (int) $row[9];
        $is_active = isset($row[10]) ? (int)$row[10] : 1;

        if (empty($product_code) || empty($product_name)) {
            $errors[] = "Row $rowCount: product_code and product_name are required.";
            continue;
        }

        $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_code = ?");
        $stmt->execute([$product_code]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Update
            $oldStmt = $pdo->prepare("SELECT * FROM products WHERE product_code = ?");
            $oldStmt->execute([$product_code]);
            $oldData = $oldStmt->fetch();

            $stmt = $pdo->prepare("UPDATE products SET product_name=?, brand=?, category=?, unit_of_measure=?, cost_price=?, selling_price=?, reorder_level=?, lead_time_days=?, safety_stock=?, is_active=? WHERE product_code=?");
            $stmt->execute([$product_name, $brand, $category, $unit_of_measure, $cost_price, $selling_price, $reorder_level, $lead_time_days, $safety_stock, $is_active, $product_code]);

            $newStmt = $pdo->prepare("SELECT * FROM products WHERE product_code = ?");
            $newStmt->execute([$product_code]);
            $newData = $newStmt->fetch();
            logAudit('products', $oldData['product_id'], 'UPDATE', $oldData, $newData);
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (product_code, product_name, brand, category, unit_of_measure, cost_price, selling_price, reorder_level, lead_time_days, safety_stock, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$product_code, $product_name, $brand, $category, $unit_of_measure, $cost_price, $selling_price, $reorder_level, $lead_time_days, $safety_stock, $is_active]);
            $new_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO inventory (product_id) VALUES (?)")->execute([$new_id]);

            $newStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
            $newStmt->execute([$new_id]);
            $newData = $newStmt->fetch();
            logAudit('products', $new_id, 'INSERT', null, $newData);
        }
    }
    fclose($handle);

    if (!empty($errors)) {
        $_SESSION['toast'] = ['message' => 'Import completed with errors: ' . implode('; ', $errors), 'type' => 'warning'];
    } else {
        $_SESSION['toast'] = ['message' => "Products imported successfully. $rowCount rows processed.", 'type' => 'success'];
    }
    header("Location: products.php");
    exit;
}

// ---------- Handle POST (Create/Update) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['csv_file'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    $product_code = trim($_POST['product_code']);
    $product_name = trim($_POST['product_name']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $uom = trim($_POST['unit_of_measure']);
    $cost = (float)$_POST['cost_price'];
    $selling = (float)$_POST['selling_price'];
    $reorder = (int)$_POST['reorder_level'];
    $lead_time = (int)$_POST['lead_time_days'];
    $safety = (int)$_POST['safety_stock'];
    $active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($product_code) || empty($product_name)) {
        $_SESSION['toast'] = ['message' => 'Product code and name are required.', 'type' => 'danger'];
        header("Location: products.php");
        exit;
    }

    if (isset($_POST['product_id']) && $_POST['product_id'] > 0) {
        // Update
        $id = (int)$_POST['product_id'];
        $oldStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $oldStmt->execute([$id]);
        $oldData = $oldStmt->fetch();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ? AND product_id != ?");
        $stmt->execute([$product_code, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['toast'] = ['message' => 'Product code already exists.', 'type' => 'danger'];
            header("Location: products.php");
            exit;
        }
        $stmt = $pdo->prepare("UPDATE products SET product_code=?, product_name=?, brand=?, category=?, unit_of_measure=?, cost_price=?, selling_price=?, reorder_level=?, lead_time_days=?, safety_stock=?, is_active=? WHERE product_id=?");
        $stmt->execute([$product_code, $product_name, $brand, $category, $uom, $cost, $selling, $reorder, $lead_time, $safety, $active, $id]);

        $newStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $newStmt->execute([$id]);
        $newData = $newStmt->fetch();
        logAudit('products', $id, 'UPDATE', $oldData, $newData);

        $_SESSION['toast'] = ['message' => 'Product updated successfully.', 'type' => 'success'];
    } else {
        // Insert
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $stmt->execute([$product_code]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['toast'] = ['message' => 'Product code already exists.', 'type' => 'danger'];
            header("Location: products.php");
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO products (product_code, product_name, brand, category, unit_of_measure, cost_price, selling_price, reorder_level, lead_time_days, safety_stock, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$product_code, $product_name, $brand, $category, $uom, $cost, $selling, $reorder, $lead_time, $safety, $active]);
        $new_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO inventory (product_id) VALUES (?)")->execute([$new_id]);

        $newStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $newStmt->execute([$new_id]);
        $newData = $newStmt->fetch();
        logAudit('products', $new_id, 'INSERT', null, $newData);

        $_SESSION['toast'] = ['message' => 'Product added successfully.', 'type' => 'success'];
    }
    header("Location: products.php");
    exit;
}

// ---------- Delete ----------
if ($action === 'delete' && isset($_GET['id'])) {
    if (!validateCSRFToken($_GET['csrf_token'] ?? '')) die("CSRF validation failed.");
    $id = (int)$_GET['id'];
    $oldStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $oldStmt->execute([$id]);
    $oldData = $oldStmt->fetch();
    if ($oldData) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        logAudit('products', $id, 'DELETE', $oldData, null);
        $_SESSION['toast'] = ['message' => 'Product deleted.', 'type' => 'warning'];
    } else {
        $_SESSION['toast'] = ['message' => 'Product not found.', 'type' => 'danger'];
    }
    header("Location: products.php");
    exit;
}

// ---------- Fetch products ----------
$products = $pdo->query("SELECT * FROM products ORDER BY product_name")->fetchAll();

// ---------- Edit ----------
$edit_product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_product = $stmt->fetch();
    if (!$edit_product) {
        $_SESSION['toast'] = ['message' => 'Product not found.', 'type' => 'danger'];
        header("Location: products.php");
        exit;
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Product Management</h2>
    <div>
        <a href="products.php?export=1" class="btn btn-sm btn-info">Export CSV</a>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import CSV</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" id="addProductBtn">Add Product</button>
    </div>
</div>

<table class="table table-bordered datatable" id="productsTable">
    <thead>
        <tr>
            <th>Code</th><th>Name</th><th>Brand</th><th>Category</th>
            <th>Cost</th><th>Sell</th><th>Reorder</th><th>Lead Time</th><th>Safety Stock</th>
            <th>Active</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['product_code']) ?></td>
        <td><?= htmlspecialchars($p['product_name']) ?></td>
        <td><?= htmlspecialchars($p['brand']) ?></td>
        <td><?= htmlspecialchars($p['category']) ?></td>
        <td><?= number_format($p['cost_price'], 2) ?></td>
        <td><?= number_format($p['selling_price'], 2) ?></td>
        <td><?= $p['reorder_level'] ?></td>
        <td><?= $p['lead_time_days'] ?></td>
        <td><?= $p['safety_stock'] ?></td>
        <td><?= $p['is_active'] ? 'Yes' : 'No' ?></td>
        <td>
            <a href="products.php?action=edit&id=<?= $p['product_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="products.php?action=delete&id=<?= $p['product_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Add/Edit Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle"><?= $edit_product ? 'Edit Product' : 'Add Product' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?= $edit_product['product_id'] ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Product Code <span class="text-danger">*</span></label>
                            <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($edit_product['product_code'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($edit_product['product_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Brand</label>
                            <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($edit_product['brand'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($edit_product['category'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Unit of Measure</label>
                            <input type="text" name="unit_of_measure" class="form-control" value="<?= htmlspecialchars($edit_product['unit_of_measure'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Cost Price</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= $edit_product['cost_price'] ?? 0 ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Selling Price</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" value="<?= $edit_product['selling_price'] ?? 0 ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="<?= $edit_product['reorder_level'] ?? 0 ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Lead Time (days)</label>
                            <input type="number" name="lead_time_days" class="form-control" value="<?= $edit_product['lead_time_days'] ?? 3 ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Safety Stock</label>
                            <input type="number" name="safety_stock" class="form-control" value="<?= $edit_product['safety_stock'] ?? 0 ?>">
                        </div>
                        <div class="col-md-12 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" <?= (!$edit_product || $edit_product['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Import Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                        <small class="text-muted">
                            Columns: product_code, product_name, brand, category, unit_of_measure, cost_price, selling_price, reorder_level, lead_time_days, safety_stock, is_active (1/0)<br>
                            First row must contain headers.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($edit_product): ?>
    var modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>