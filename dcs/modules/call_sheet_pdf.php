<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
requireLogin();

$sheet_id = $_GET['id'] ?? 0;
if (!$sheet_id) die("Invalid sheet.");

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

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Call Sheet <?= htmlspecialchars($sheet['sheet_number']) ?></title>
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
    <h1>Call Sheet: <?= htmlspecialchars($sheet['sheet_number']) ?></h1>
    <p><strong>Store:</strong> <?= htmlspecialchars($sheet['store_name'] ?? 'N/A') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($sheet['status']) ?></p>
    <p><strong>Created By:</strong> <?= htmlspecialchars($sheet['creator']) ?> on <?= $sheet['created_date'] ?></p>
    <?php if ($sheet['approved_by']): ?>
        <p><strong>Approved By:</strong> <?= htmlspecialchars($sheet['approver']) ?> on <?= $sheet['approved_date'] ?></p>
    <?php endif; ?>
    <?php if (!empty($sheet['buyer_remarks'])): ?>
        <p><strong>Remarks:</strong> <?= nl2br(htmlspecialchars($sheet['buyer_remarks'])) ?></p>
    <?php endif; ?>
</div>

<table>
    <thead>
        <tr><th>Product Code</th><th>Product</th><th>Suggested Qty</th><th>Final Qty</th><th>Unit Cost</th><th>Line Total</th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['product_code']) ?></td>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td><?= $item['suggested_qty'] ?></td>
            <td><?= $item['final_qty'] ?></td>
            <td><?= number_format($item['unit_cost'], 2) ?></td>
            <td><?= number_format($item['final_qty'] * $item['unit_cost'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="5" class="text-end">Total</th><th><?= number_format($total_cost, 2) ?></th></tr>
    </tfoot>
</table>
</body>
</html>
<?php
$html = ob_get_clean();
generatePDF($html, 'call_sheet_' . $sheet['sheet_number'] . '.pdf');
?>