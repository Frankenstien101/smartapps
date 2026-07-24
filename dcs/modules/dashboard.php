<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Build filters based on role
if ($role === 'Admin') {
    $whereSheets = "1=1";
    $whereSales = "1=1";
    $params = [];
} elseif ($role === 'SalesRep') {
    $assignedStores = $_SESSION['assigned_stores'] ?? [];
    if (empty($assignedStores)) {
        $whereSheets = "1=0";
        $whereSales = "1=0";
        $params = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedStores), '?'));
        $whereSheets = "cs.customer_id IN ($placeholders)";
        $whereSales = "s.customer_id IN ($placeholders)";
        $params = $assignedStores;
    }
} elseif ($role === 'Buyer') {
    $customer_id = $_SESSION['customer_id'] ?? 0;
    if (!$customer_id) {
        $whereSheets = "1=0";
        $whereSales = "1=0";
        $params = [];
    } else {
        $whereSheets = "cs.customer_id = ?";
        $whereSales = "s.customer_id = ?";
        $params = [$customer_id];
    }
} else {
    $whereSheets = "1=0";
    $whereSales = "1=0";
    $params = [];
}

// Total products (global, no filter)
$total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();

// Pending approvals
$pending_sql = "SELECT COUNT(*) FROM call_sheets cs WHERE cs.status = 'Submitted' AND $whereSheets";
$stmt = $pdo->prepare($pending_sql);
$stmt->execute($params);
$pending_calls = $stmt->fetchColumn();

// Approved orders (call sheets approved but not yet converted)
$approved_sql = "SELECT COUNT(*) FROM call_sheets cs WHERE cs.status = 'Approved' AND $whereSheets";
$stmt = $pdo->prepare($approved_sql);
$stmt->execute($params);
$approved_calls = $stmt->fetchColumn();

// Purchase Orders (from call sheets that match the filter)
$po_sql = "
    SELECT COUNT(*) 
    FROM purchase_orders po
    JOIN call_sheets cs ON po.call_sheet_id = cs.call_sheet_id
    WHERE $whereSheets
";
$stmt = $pdo->prepare($po_sql);
$stmt->execute($params);
$total_pos = $stmt->fetchColumn();

// ---------- EXTRA WIDGETS ----------
// Low stock count (global)
$lowStockCount = $pdo->query("
    SELECT COUNT(*) FROM inventory i
    JOIN products p ON i.product_id = p.product_id
    WHERE i.current_stock < p.reorder_level
")->fetchColumn();

// POs this month (global, or filter if needed)
$poThisMonth = $pdo->query("
    SELECT COUNT(*) FROM purchase_orders
    WHERE order_date >= DATEADD(month, -1, GETDATE())
")->fetchColumn();

// Top 3 stores by revenue (last 30 days)
$topStores = $pdo->query("
    SELECT TOP 3 c.customer_name, SUM(s.total_amount) AS revenue
    FROM sales_history s
    JOIN customers c ON s.customer_id = c.customer_id
    WHERE s.sales_date >= DATEADD(month, -1, GETDATE())
    GROUP BY c.customer_name
    ORDER BY revenue DESC
")->fetchAll();

// Recent sales (limit 5)
if ($role === 'SalesRep') {
    $sales_sql = "
        SELECT TOP 5 s.sales_date, p.product_name, s.quantity_sold, s.total_amount
        FROM sales_history s
        JOIN products p ON s.product_id = p.product_id
        WHERE s.created_by = ?
        ORDER BY s.sales_date DESC, s.recorded_at DESC
    ";
    $sales_stmt = $pdo->prepare($sales_sql);
    $sales_stmt->execute([$user_id]);
} elseif ($role === 'Buyer') {
    $sales_sql = "
        SELECT TOP 5 s.sales_date, p.product_name, s.quantity_sold, s.total_amount
        FROM sales_history s
        JOIN products p ON s.product_id = p.product_id
        WHERE s.customer_id = ?
        ORDER BY s.sales_date DESC, s.recorded_at DESC
    ";
    $sales_stmt = $pdo->prepare($sales_sql);
    $sales_stmt->execute([$_SESSION['customer_id']]);
} else {
    $sales_sql = "
        SELECT TOP 5 s.sales_date, p.product_name, s.quantity_sold, s.total_amount
        FROM sales_history s
        JOIN products p ON s.product_id = p.product_id
        ORDER BY s.sales_date DESC, s.recorded_at DESC
    ";
    $sales_stmt = $pdo->query($sales_sql);
}
$recent_sales = $sales_stmt->fetchAll();

// Chart data: Sales by product (last 30 days) – filtered by role/store
if ($role === 'Admin') {
    $chart_sql = "
        SELECT TOP 10 p.product_name, SUM(s.total_amount) AS revenue
        FROM sales_history s
        JOIN products p ON s.product_id = p.product_id
        WHERE s.sales_date >= DATEADD(month, -1, GETDATE())
        GROUP BY p.product_name
        ORDER BY revenue DESC
    ";
    $chartData = $pdo->query($chart_sql)->fetchAll();
} elseif ($role === 'SalesRep') {
    $assignedStores = $_SESSION['assigned_stores'] ?? [];
    if (empty($assignedStores)) {
        $chartData = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedStores), '?'));
        $chart_sql = "
            SELECT TOP 10 p.product_name, SUM(s.total_amount) AS revenue
            FROM sales_history s
            JOIN products p ON s.product_id = p.product_id
            WHERE s.sales_date >= DATEADD(month, -1, GETDATE())
            AND s.customer_id IN ($placeholders)
            GROUP BY p.product_name
            ORDER BY revenue DESC
        ";
        $stmt = $pdo->prepare($chart_sql);
        $stmt->execute($assignedStores);
        $chartData = $stmt->fetchAll();
    }
} elseif ($role === 'Buyer') {
    $customer_id = $_SESSION['customer_id'] ?? 0;
    if (!$customer_id) {
        $chartData = [];
    } else {
        $chart_sql = "
            SELECT TOP 10 p.product_name, SUM(s.total_amount) AS revenue
            FROM sales_history s
            JOIN products p ON s.product_id = p.product_id
            WHERE s.sales_date >= DATEADD(month, -1, GETDATE())
            AND s.customer_id = ?
            GROUP BY p.product_name
            ORDER BY revenue DESC
        ";
        $stmt = $pdo->prepare($chart_sql);
        $stmt->execute([$customer_id]);
        $chartData = $stmt->fetchAll();
    }
} else {
    $chartData = [];
}

include '../includes/header.php';
?>
<h2>Dashboard</h2>
<div class="row g-3">
    <div class="col-md-3">
        <div class="bs-stat ink">
            <div class="bs-stat-label"><i class="bi bi-box-seam"></i> Total Products</div>
            <div class="bs-stat-value"><?= $total_products ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="bs-stat sun">
            <div class="bs-stat-label"><i class="bi bi-hourglass-split"></i> Pending Approvals</div>
            <div class="bs-stat-value"><?= $pending_calls ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="bs-stat ok">
            <div class="bs-stat-label"><i class="bi bi-check2-circle"></i> Approved Orders</div>
            <div class="bs-stat-value"><?= $approved_calls ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="bs-stat sky">
            <div class="bs-stat-label"><i class="bi bi-receipt"></i> Purchase Orders</div>
            <div class="bs-stat-value"><?= $total_pos ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-3">
        <div class="bs-stat ink">
            <div class="bs-stat-label"><i class="bi bi-exclamation-triangle"></i> Low Stock Items</div>
            <div class="bs-stat-value"><?= $lowStockCount ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="bs-stat sun">
            <div class="bs-stat-label"><i class="bi bi-receipt"></i> POs (Last 30 Days)</div>
            <div class="bs-stat-value"><?= $poThisMonth ?></div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Top 10 Products by Revenue (Last 30 Days)</div>
            <div class="card-body">
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Top 3 Stores by Revenue (Last 30 Days)</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Store</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php if (empty($topStores)): ?>
                        <tr><td colspan="2" class="text-center">No data</td></tr>
                    <?php else: ?>
                        <?php foreach ($topStores as $ts): ?>
                            <tr><td><?= htmlspecialchars($ts['customer_name']) ?></td><td><?= number_format($ts['revenue'], 2) ?></td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Recent Sales</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Amount</th></tr></thead>
                    <tbody>
                    <?php if (empty($recent_sales)): ?>
                        <tr><td colspan="4" class="text-center">No recent sales</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td><?= $sale['sales_date'] ?></td>
                                <td><?= htmlspecialchars($sale['product_name']) ?></td>
                                <td><?= $sale['quantity_sold'] ?></td>
                                <td><?= number_format($sale['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = <?= json_encode($chartData) ?>;
    if (data.length > 0) {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.product_name),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: data.map(item => item.revenue),
                    backgroundColor: 'rgba(242, 169, 59, 0.6)',
                    borderColor: 'rgba(242, 169, 59, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    } else {
        document.getElementById('revenueChart').parentElement.innerHTML = '<p class="text-muted">No sales data available for the selected period.</p>';
    }
});
</script>
<?php include '../includes/footer.php'; ?>