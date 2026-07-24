<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';
require_once '../config/mail.php';
requireRole('Buyer');

$sheet_id = $_GET['id'] ?? 0;
if (!$sheet_id) die("Invalid sheet.");

$stmt = $pdo->prepare("SELECT * FROM call_sheets WHERE call_sheet_id = ? AND status = 'Submitted'");
$stmt->execute([$sheet_id]);
$sheet = $stmt->fetch();
if (!$sheet) die("Sheet not found or already processed.");

$itemsStmt = $pdo->prepare("
    SELECT ci.*, p.product_name
    FROM call_sheet_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.call_sheet_id = ?
");
$itemsStmt->execute([$sheet_id]);
$items = $itemsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    $action = $_POST['action'];
    $remarks = $_POST['remarks'] ?? '';

    $oldFinal = [];
    foreach ($items as $item) {
        $oldFinal[$item['item_id']] = $item['final_qty'];
    }
    $newFinal = [];
    foreach ($_POST['final_qty'] as $item_id => $qty) {
        $qty = max(0, (int)$qty);
        $newFinal[$item_id] = $qty;
        $pdo->prepare("UPDATE call_sheet_items SET final_qty = ? WHERE item_id = ?")->execute([$qty, $item_id]);
    }
    if ($oldFinal != $newFinal) {
        logCallSheetAction($sheet_id, 'BUYER_EDIT', json_encode($oldFinal), json_encode($newFinal));
    }

    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $pdo->prepare("
        UPDATE call_sheets 
        SET status = ?, buyer_remarks = ?, approved_by = ?, approved_date = GETDATE()
        WHERE call_sheet_id = ?
    ")->execute([$status, $remarks, $_SESSION['user_id'], $sheet_id]);

    logCallSheetAction($sheet_id, $status);

    // Send email to SalesRep
    $salesRepEmail = $pdo->prepare("SELECT u.email FROM users u JOIN call_sheets cs ON cs.created_by = u.user_id WHERE cs.call_sheet_id = ?");
    $salesRepEmail->execute([$sheet_id]);
    $to = $salesRepEmail->fetchColumn();
    if ($to) {
        $subject = "Call Sheet {$sheet['sheet_number']} $status";
        $body = "Your call sheet has been $status. Remarks: $remarks";
        sendEmail($to, $subject, $body);
    }

    $_SESSION['toast'] = ['message' => "Call sheet $status successfully.", 'type' => ($status === 'Approved' ? 'success' : 'danger')];
    header("Location: approval_list.php");
    exit;
}

include '../includes/header.php';
?>
<h2>Review Call Sheet: <?= htmlspecialchars($sheet['sheet_number']) ?></h2>
<form method="post">
    <?= csrfField() ?>
    <table class="table table-bordered">
        <thead>
            <tr><th>Product</th><th>Suggested</th><th>Final Qty (edit)</th><th>Reason</th></tr>
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
    <div class="mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="3"></textarea>
    </div>
    <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
    <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this call sheet?')">Reject</button>
    <a href="approval_list.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>