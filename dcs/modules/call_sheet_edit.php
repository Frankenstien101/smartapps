<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';
ensureEmailColumn();
require_once '../config/mail.php';
requireRole('SalesRep');

$sheet_id = $_GET['id'] ?? 0;
if (!$sheet_id) die("Invalid sheet.");

// Fetch sheet with store info
$stmt = $pdo->prepare("
    SELECT cs.*, c.customer_name AS store_name
    FROM call_sheets cs
    LEFT JOIN customers c ON cs.customer_id = c.customer_id
    WHERE cs.call_sheet_id = ? AND cs.created_by = ?
");
$stmt->execute([$sheet_id, $_SESSION['user_id']]);
$sheet = $stmt->fetch();
if (!$sheet || $sheet['status'] != 'Draft') {
    die("Sheet not found or not editable.");
}

// Security: ensure the store is assigned to this SalesRep
if (!in_array($sheet['customer_id'], $_SESSION['assigned_stores'] ?? [])) {
    die("You are not assigned to this store.");
}

$storeName = $sheet['store_name'] ?? 'N/A';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    if (isset($_POST['update'])) {
        $oldItems = [];
        $newItems = [];
        foreach ($_POST['final_qty'] as $item_id => $qty) {
            $qty = max(0, (int)$qty);
            $stmtOld = $pdo->prepare("SELECT final_qty FROM call_sheet_items WHERE item_id = ?");
            $stmtOld->execute([$item_id]);
            $oldQty = $stmtOld->fetchColumn();
            if ($oldQty != $qty) {
                $oldItems[$item_id] = $oldQty;
                $newItems[$item_id] = $qty;
            }
            $stmt = $pdo->prepare("UPDATE call_sheet_items SET final_qty = ? WHERE item_id = ?");
            $stmt->execute([$qty, $item_id]);
        }
        if (!empty($oldItems)) {
            logCallSheetAction($sheet_id, 'EDIT_ITEMS', json_encode($oldItems), json_encode($newItems));
        }
        $_SESSION['toast'] = ['message' => 'Quantities updated successfully.', 'type' => 'success'];
        header("Location: call_sheet_edit.php?id=$sheet_id");
        exit;
    }

    if (isset($_POST['submit'])) {
        $pdo->prepare("UPDATE call_sheets SET status = 'Submitted' WHERE call_sheet_id = ?")->execute([$sheet_id]);
        logCallSheetAction($sheet_id, 'SUBMIT');
        $buyerEmail = $pdo->query("SELECT email FROM users WHERE role='Buyer' AND is_active=1")->fetchColumn();
        if ($buyerEmail) {
            $subject = "New Call Sheet Submitted: " . $sheet['sheet_number'];
            $body = "A new call sheet has been submitted for your approval. Click <a href='" . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "/modules/approval.php?id=$sheet_id'>here</a> to review.";
            sendEmail($buyerEmail, $subject, $body);
        }
        $_SESSION['toast'] = ['message' => 'Call sheet submitted for approval!', 'type' => 'success'];
        header("Location: call_sheet.php");
        exit;
    }
}

$itemsStmt = $pdo->prepare("
    SELECT ci.*, p.product_name, p.product_code
    FROM call_sheet_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.call_sheet_id = ?
");
$itemsStmt->execute([$sheet_id]);
$items = $itemsStmt->fetchAll();

include '../includes/header.php';
?>
<h2>Edit Call Sheet: <?= htmlspecialchars($sheet['sheet_number']) ?></h2>
<p><strong>Store:</strong> <?= htmlspecialchars($storeName) ?></p>
<form method="post">
    <?= csrfField() ?>
    <table class="table table-bordered">
        <thead>
            <tr><th>Product</th><th>Suggested Qty</th><th>Final Qty</th><th>Reason</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['suggested_qty'] ?></td>
                <td>
                    <input type="number" name="final_qty[<?= $item['item_id'] ?>]" value="<?= $item['final_qty'] ?>" class="form-control" min="0">
                </td>
                <td><?= htmlspecialchars($item['reason']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" name="update" class="btn btn-primary">Update Quantities</button>
    <button type="submit" name="submit" class="btn btn-success" onclick="return confirm('Submit this call sheet for buyer approval?')">Submit to Buyer</button>
    <a href="call_sheet.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>