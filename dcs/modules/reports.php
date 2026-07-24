<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
requireRole('Admin');

$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// ---------- Sales by Product ----------
$sales_stmt = $pdo->prepare("
    SELECT 
        p.product_name,
        SUM(s.quantity_sold) AS total_qty,
        SUM(s.total_amount) AS total_revenue
    FROM sales_history s
    JOIN products p ON s.product_id = p.product_id
    WHERE s.sales_date BETWEEN ? AND ?
    GROUP BY p.product_name
    ORDER BY total_revenue DESC
");
$sales_stmt->execute([$date_from, $date_to]);
$sales_report = $sales_stmt->fetchAll();

// ---------- Sales by Store ----------
$store_stmt = $pdo->prepare("
    SELECT 
        c.customer_name,
        COUNT(s.sales_id) AS transaction_count,
        SUM(s.total_amount) AS total_revenue,
        AVG(s.total_amount) AS avg_transaction
    FROM sales_history s
    JOIN customers c ON s.customer_id = c.customer_id
    WHERE s.sales_date BETWEEN ? AND ?
    GROUP BY c.customer_name
    ORDER BY total_revenue DESC
");
$store_stmt->execute([$date_from, $date_to]);
$store_report = $store_stmt->fetchAll();

// ---------- Low stock ----------
$inv_report = $pdo->query("
    SELECT p.product_name, i.current_stock, i.incoming_stock, p.reorder_level
    FROM inventory i
    JOIN products p ON i.product_id = p.product_id
    WHERE i.current_stock < p.reorder_level
")->fetchAll();

// ---------- Export CSV (Product Sales) ----------
if (isset($_GET['export'])) {
    $headers = ['Product', 'Qty Sold', 'Revenue'];
    $data = array_map(function($r) { return [$r['product_name'], $r['total_qty'], $r['total_revenue']]; }, $sales_report);
    exportToCSV($data, $headers, 'sales_report.csv');
}

include '../includes/header.php';
?>
<h2>Reports</h2>

<!-- Date Range Filter -->
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <label>From</label>
        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
    </div>
    <div class="col-auto">
        <label>To</label>
        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
    </div>
    <div class="col-auto align-self-end">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="reports.php?export=1&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn btn-success">Export CSV</a>
    </div>
</form>

<div class="row">
    <!-- Sales by Product -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Sales by Product (<?= $date_from ?> to <?= $date_to ?>)</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php if (empty($sales_report)): ?>
                        <tr><td colspan="3" class="text-center">No sales data</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales_report as $row): ?>
                            <tr><td><?= htmlspecialchars($row['product_name']) ?></td><td><?= $row['total_qty'] ?></td><td><?= number_format($row['total_revenue'], 2) ?></td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sales by Store -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Sales by Store (<?= $date_from ?> to <?= $date_to ?>)</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Store</th><th>Transactions</th><th>Revenue</th><th>Avg / Transaction</th></tr></thead>
                    <tbody>
                    <?php if (empty($store_report)): ?>
                        <tr><td colspan="4" class="text-center">No sales data</td></tr>
                    <?php else: ?>
                        <?php foreach ($store_report as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= $row['transaction_count'] ?></td>
                                <td><?= number_format($row['total_revenue'], 2) ?></td>
                                <td><?= number_format($row['avg_transaction'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alerts -->
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Low Stock Alerts</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Product</th><th>Current</th><th>Reorder Level</th></tr></thead>
                    <tbody>
                    <?php if (empty($inv_report)): ?>
                        <tr><td colspan="3" class="text-center">All stock levels are healthy.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inv_report as $row): ?>
                            <tr><td><?= htmlspecialchars($row['product_name']) ?></td><td><?= $row['current_stock'] ?></td><td><?= $row['reorder_level'] ?></td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Products Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Top 5 Products by Revenue</div>
            <div class="card-body">
                <canvas id="topChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = <?= json_encode($sales_report) ?>;
    const top5 = data.slice(0, 5);
    if (top5.length > 0) {
        const ctx = document.getElementById('topChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: top5.map(item => item.product_name),
                datasets: [{
                    label: 'Revenue',
                    data: top5.map(item => item.total_revenue),
                    backgroundColor: 'rgba(62, 147, 191, 0.6)',
                    borderColor: 'rgba(62, 147, 191, 1)',
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
        document.getElementById('topChart').parentElement.innerHTML = '<p class="text-muted">No sales data for the selected period.</p>';
    }
});
</script>
<?php include '../includes/footer.php'; ?>