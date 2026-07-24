<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
requireLogin();

$po_id = $_GET['id'] ?? 0;
if (!$po_id) die("Invalid PO.");

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

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order <?= htmlspecialchars($po['po_number']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        h1 { font-size: 24px; margin-bottom: 10px; }
        .header { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
<div class="header">
    <h1>Purchase Order #<?= htmlspecialchars($po['po_number']) ?></h1>
    <p><strong>Order Date:</strong> <?= $po['order_date'] ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($po['status']) ?></p>
    <p><strong>Store:</strong> <?= htmlspecialchars($po['store_name'] ?? 'N/A') ?></p>
    <p><strong>Created By:</strong> <?= htmlspecialchars($po['creator_name']) ?></p>
    <p><strong>Total Amount:</strong> <?= number_format($po['total_amount'], 2) ?></p>
</div>

<table>
    <thead>
        <tr><th>Product Code</th><th>Product</th><th>Quantity</th><th>Unit Cost</th><th>Line Total</th></tr>
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
</body>
</html>
<?php
$html = ob_get_clean();
generatePDF($html, 'po_' . $po['po_number'] . '.pdf');
?>