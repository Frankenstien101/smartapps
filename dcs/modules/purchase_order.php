<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';

// Allow both Buyer and SalesRep
if (!isLoggedIn() || !in_array($_SESSION['role'], ['Buyer', 'SalesRep'])) {
    header("Location: ../login.php");
    exit;
}

$sheet_id = $_GET['sheet_id'] ?? 0;
if (!$sheet_id) {
    die("Invalid sheet.");
}

$stmt = $pdo->prepare("SELECT * FROM call_sheets WHERE call_sheet_id = ? AND status = 'Approved'");
$stmt->execute([$sheet_id]);
$sheet = $stmt->fetch();
if (!$sheet) {
    die("Call sheet not approved or already converted.");
}

// Check for items with final_qty > 0
$checkItems = $pdo->prepare("SELECT COUNT(*) FROM call_sheet_items WHERE call_sheet_id = ? AND final_qty > 0");
$checkItems->execute([$sheet_id]);
$itemCount = $checkItems->fetchColumn();

if ($itemCount == 0) {
    $_SESSION['toast'] = ['message' => 'Cannot create PO: No items with positive quantities.', 'type' => 'warning'];
    header("Location: call_sheet.php");
    exit;
}

// ---------- Handle POST (conversion) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }

    $po_number = generateCode('PO');
    $pdo->beginTransaction();
    try {
        // Insert PO
        $stmt = $pdo->prepare("
            INSERT INTO purchase_orders (po_number, call_sheet_id, order_date, status, created_by)
            VALUES (?, ?, GETDATE(), 'Pending', ?)
        ");
        $stmt->execute([$po_number, $sheet_id, $_SESSION['user_id']]);
        $po_id = $pdo->lastInsertId();

        // Copy items with final_qty > 0, fallback to product cost if unit_cost is zero
        $stmt = $pdo->prepare("
            INSERT INTO po_items (po_id, product_id, quantity, unit_cost, line_total)
            SELECT 
                ?,
                ci.product_id,
                ci.final_qty,
                CASE 
                    WHEN ci.unit_cost > 0 THEN ci.unit_cost 
                    ELSE p.cost_price 
                END AS unit_cost,
                ci.final_qty * CASE 
                    WHEN ci.unit_cost > 0 THEN ci.unit_cost 
                    ELSE p.cost_price 
                END AS line_total
            FROM call_sheet_items ci
            JOIN products p ON ci.product_id = p.product_id
            WHERE ci.call_sheet_id = ? AND ci.final_qty > 0
        ");
        $stmt->execute([$po_id, $sheet_id]);

        // Verify items were copied
        $checkInsert = $pdo->prepare("SELECT COUNT(*) FROM po_items WHERE po_id = ?");
        $checkInsert->execute([$po_id]);
        if ($checkInsert->fetchColumn() == 0) {
            throw new Exception("No items were copied to the PO.");
        }

        // Recalculate total
        $totalStmt = $pdo->prepare("SELECT SUM(line_total) AS total FROM po_items WHERE po_id = ?");
        $totalStmt->execute([$po_id]);
        $total_amount = $totalStmt->fetch()['total'] ?? 0;
        $pdo->prepare("UPDATE purchase_orders SET total_amount = ? WHERE po_id = ?")->execute([$total_amount, $po_id]);

        // Update call sheet status
        $pdo->prepare("UPDATE call_sheets SET status = 'Converted' WHERE call_sheet_id = ?")->execute([$sheet_id]);

        // Audit log
        logCallSheetAction($sheet_id, 'CONVERT_TO_PO', null, 'PO #' . $po_number);

        $pdo->commit();
        $_SESSION['toast'] = ['message' => 'Purchase Order created successfully! Total: ₱' . number_format($total_amount, 2), 'type' => 'success'];
        header("Location: po_view.php?id=$po_id");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['toast'] = ['message' => 'Error creating PO: ' . $e->getMessage(), 'type' => 'danger'];
        header("Location: call_sheet.php");
        exit;
    }
}

// ---------- GET: Show confirmation page (with full layout) ----------
include '../includes/header.php';
?>
<div class="card">
    <div class="card-body">
        <h3>Confirm Conversion</h3>
        <p>You are about to convert Call Sheet <strong><?= htmlspecialchars($sheet['sheet_number']) ?></strong> to a Purchase Order.</p>
        <p>This action cannot be undone.</p>
        <form method="post">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-success">Yes, Convert</button>
            <a href="call_sheet.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php
include '../includes/footer.php';
?>