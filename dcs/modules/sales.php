<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';

ensureSalesHistoryCreatedByColumn();
ensureUserStoresTable();
ensureAuditLogTable();

requireRole('SalesRep');

$products = $pdo->query("
    SELECT p.product_id, p.product_name, COALESCE(i.current_stock, 0) AS current_stock
    FROM products p
    LEFT JOIN inventory i ON p.product_id = i.product_id
    WHERE p.is_active = 1
    ORDER BY p.product_name
")->fetchAll();

$assignedStoreIds = $_SESSION['assigned_stores'] ?? [];
if (empty($assignedStoreIds)) {
    $customers = [];
    $noStoresMessage = true;
} else {
    $placeholders = implode(',', array_fill(0, count($assignedStoreIds), '?'));
    $stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customers WHERE is_active=1 AND customer_id IN ($placeholders) ORDER BY customer_name");
    $stmt->execute($assignedStoreIds);
    $customers = $stmt->fetchAll();
    $noStoresMessage = false;
}

// ---------- Handle POST (record sales) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $sales_date = $_POST['sales_date'] ?? date('Y-m-d');
    $products_data = $_POST['products'] ?? [];

    if ($customer_id && !in_array($customer_id, $assignedStoreIds)) {
        $_SESSION['toast'] = ['message' => 'You are not assigned to this store.', 'type' => 'danger'];
        header("Location: sales.php");
        exit;
    }

    $hasProduct = false;
    foreach ($products_data as $item) {
        if (!empty($item['product_id']) && !empty($item['quantity']) && $item['quantity'] > 0) {
            $hasProduct = true;
            break;
        }
    }
    if (!$hasProduct) {
        $_SESSION['toast'] = ['message' => 'Please add at least one product with quantity > 0.', 'type' => 'warning'];
        header("Location: sales.php");
        exit;
    }

    $errors = [];
    $successCount = 0;

    foreach ($products_data as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $unit_price = (float)($item['unit_price'] ?? 0);

        if ($product_id == 0 || $quantity <= 0 || $unit_price <= 0) {
            continue;
        }

        $stockCheck = $pdo->prepare("SELECT current_stock FROM inventory WHERE product_id = ?");
        $stockCheck->execute([$product_id]);
        $currentStock = (int)$stockCheck->fetchColumn();

        if ($currentStock < $quantity) {
            $productName = $pdo->prepare("SELECT product_name FROM products WHERE product_id = ?");
            $productName->execute([$product_id]);
            $name = $productName->fetchColumn();
            $errors[] = "Insufficient stock for $name. Available: $currentStock";
            continue;
        }

        $total = $quantity * $unit_price;

        $stmt = $pdo->prepare("
            INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$product_id, $customer_id, $sales_date, $quantity, $unit_price, $total, $_SESSION['user_id']]);
        $sale_id = $pdo->lastInsertId();

        $pdo->prepare("UPDATE inventory SET current_stock = current_stock - ? WHERE product_id = ?")->execute([$quantity, $product_id]);
        $pdo->prepare("INSERT INTO stock_history (product_id, transaction_type, quantity, reference, created_by) VALUES (?, 'OUT', ?, 'Sales', ?)")
            ->execute([$product_id, $quantity, $_SESSION['user_id']]);

        logAudit('sales_history', $sale_id, 'INSERT', null, [
            'product_id' => $product_id,
            'customer_id' => $customer_id,
            'sales_date' => $sales_date,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'total' => $total
        ]);

        $successCount++;
    }

    if (!empty($errors)) {
        $msg = "Sales recorded: $successCount item(s). Errors: " . implode('; ', $errors);
        $_SESSION['toast'] = ['message' => $msg, 'type' => 'warning'];
    } else {
        $_SESSION['toast'] = ['message' => "$successCount product(s) recorded successfully.", 'type' => 'success'];
    }
    header("Location: sales.php");
    exit;
}

// ---------- Filters for sales history ----------
$filter_store = $_GET['store'] ?? '';
$filter_product = $_GET['product'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$where_sales = "1=1";
$params_sales = [];

if ($filter_store) {
    $where_sales .= " AND s.customer_id = ?";
    $params_sales[] = $filter_store;
}
if ($filter_product) {
    $where_sales .= " AND s.product_id = ?";
    $params_sales[] = $filter_product;
}
if ($filter_date_from) {
    $where_sales .= " AND s.sales_date >= ?";
    $params_sales[] = $filter_date_from;
}
if ($filter_date_to) {
    $where_sales .= " AND s.sales_date <= ?";
    $params_sales[] = $filter_date_to . ' 23:59:59';
}

if ($_SESSION['role'] === 'Admin') {
    $sql = "
        SELECT s.*, p.product_name, c.customer_name, u.full_name AS recorded_by
        FROM sales_history s
        JOIN products p ON s.product_id = p.product_id
        LEFT JOIN customers c ON s.customer_id = c.customer_id
        LEFT JOIN users u ON s.created_by = u.user_id
        WHERE $where_sales
        ORDER BY s.sales_date DESC, s.recorded_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_sales);
    $sales = $stmt->fetchAll();
} else {
    // SalesRep sees only their own sales
    $where_sales .= " AND s.created_by = ?";
    $params_sales[] = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT s.*, p.product_name, c.customer_name, u.full_name AS recorded_by
        FROM sales_history s
        JOIN products p ON s.product_id = p.product_id
        LEFT JOIN customers c ON s.customer_id = c.customer_id
        LEFT JOIN users u ON s.created_by = u.user_id
        WHERE $where_sales
        ORDER BY s.sales_date DESC, s.recorded_at DESC
    ");
    $stmt->execute($params_sales);
    $sales = $stmt->fetchAll();
}

// ---------- For filter dropdowns ----------
$allStores = $pdo->query("SELECT customer_id, customer_name FROM customers WHERE is_active=1 ORDER BY customer_name")->fetchAll();
$allProducts = $pdo->query("SELECT product_id, product_name FROM products WHERE is_active=1 ORDER BY product_name")->fetchAll();

include '../includes/header.php';
?>

<h2>Sales Entry</h2>

<?php if ($noStoresMessage): ?>
    <div class="alert alert-warning">You have no stores assigned. Please contact your administrator to assign stores.</div>
<?php else: ?>

<div class="card mb-4">
    <div class="card-header">Record Multiple Sales</div>
    <div class="card-body">
        <form method="post" id="salesForm">
            <?= csrfField() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label>Customer (Store)</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select Store</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Date</label>
                    <input type="date" name="sales_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="salesTable">
                    <thead>
                        <tr>
                            <th style="width:40%;">Product</th>
                            <th style="width:20%;">Quantity</th>
                            <th style="width:20%;">Unit Price</th>
                            <th style="width:10%;">Total</th>
                            <th style="width:10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesRows">
                        <tr class="product-row">
                            <td>
                                <select name="products[0][product_id]" class="form-select product-select" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['product_id'] ?>" data-stock="<?= $p['current_stock'] ?>">
                                            <?= htmlspecialchars($p['product_name']) ?> (Stock: <?= $p['current_stock'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="products[0][quantity]" class="form-control qty-input" min="1" value="1" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" name="products[0][unit_price]" class="form-control price-input" min="0.01" required>
                            </td>
                            <td class="row-total text-end">0.00</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row" disabled>Remove</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
                            <td class="text-end" id="grandTotal">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-auto">
                    <button type="button" class="btn btn-secondary" id="addRow">+ Add Product</button>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Submit All Sales</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Filter form for sales history -->
<div class="card mb-3">
    <div class="card-header">Filter Sales History</div>
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-auto">
                <select name="store" class="form-select form-select-sm">
                    <option value="">All Stores</option>
                    <?php foreach ($allStores as $s): ?>
                        <option value="<?= $s['customer_id'] ?>" <?= isset($_GET['store']) && $_GET['store'] == $s['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['customer_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="product" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    <?php foreach ($allProducts as $p): ?>
                        <option value="<?= $p['product_id'] ?>" <?= isset($_GET['product']) && $_GET['product'] == $p['product_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['product_name']) ?></option>
                    <?php endforeach; ?>
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
                <a href="sales.php" class="btn btn-sm btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<h3>Sales History</h3>
<table class="table table-sm datatable">
    <thead>
        <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Customer (Store)</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <th>Recorded By</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($sales)): ?>
        <tr><td colspan="<?= $_SESSION['role'] === 'Admin' ? 7 : 6 ?>" class="text-center">No sales found.</td></tr>
    <?php else: ?>
        <?php foreach ($sales as $s): ?>
            <tr>
                <td><?= $s['sales_date'] ?></td>
                <td><?= htmlspecialchars($s['product_name']) ?></td>
                <td><?= htmlspecialchars($s['customer_name'] ?? 'N/A') ?></td>
                <td><?= $s['quantity_sold'] ?></td>
                <td><?= number_format($s['unit_price'], 2) ?></td>
                <td><?= number_format($s['total_amount'], 2) ?></td>
                <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <td><?= htmlspecialchars($s['recorded_by'] ?? 'Unknown') ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;

    function updateRowTotal(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const total = qty * price;
        row.querySelector('.row-total').textContent = total.toFixed(2);
        updateGrandTotal();
    }

    function updateGrandTotal() {
        let grand = 0;
        document.querySelectorAll('.row-total').forEach(function(el) {
            grand += parseFloat(el.textContent) || 0;
        });
        document.getElementById('grandTotal').textContent = grand.toFixed(2);
    }

    document.getElementById('addRow').addEventListener('click', function() {
        const tbody = document.getElementById('salesRows');
        const firstRow = tbody.querySelector('.product-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('select, input').forEach(function(el) {
            const name = el.getAttribute('name');
            if (name) {
                el.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            el.value = '';
            if (el.classList.contains('qty-input')) el.value = 1;
        });

        newRow.querySelector('.row-total').textContent = '0.00';

        const removeBtn = newRow.querySelector('.remove-row');
        removeBtn.disabled = false;

        newRow.querySelectorAll('.qty-input, .price-input').forEach(function(el) {
            el.addEventListener('input', function() {
                updateRowTotal(newRow);
            });
        });

        tbody.appendChild(newRow);
        rowIndex++;
        updateGrandTotal();
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row') && !e.target.disabled) {
            const row = e.target.closest('.product-row');
            if (document.querySelectorAll('.product-row').length > 1) {
                row.remove();
                updateGrandTotal();
            } else {
                alert('You must have at least one product row.');
            }
        }
    });

    document.querySelectorAll('.qty-input, .price-input').forEach(function(el) {
        el.addEventListener('input', function() {
            const row = el.closest('.product-row');
            updateRowTotal(row);
        });
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const selected = e.target.options[e.target.selectedIndex];
            const stock = selected.getAttribute('data-stock') || 0;
            // Optionally display stock warning
        }
    });

    updateGrandTotal();
});
</script>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>