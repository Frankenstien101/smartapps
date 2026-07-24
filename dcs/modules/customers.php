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
    $customers = $pdo->query("SELECT * FROM customers ORDER BY customer_name")->fetchAll();
    $headers = ['customer_id', 'customer_name', 'contact_person', 'contact_number', 'address', 'is_active'];
    exportToCSV($customers, $headers, 'customers.csv');
}

// ---------- Import CSV ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    $headers = fgetcsv($handle); // skip header row
    while (($row = fgetcsv($handle)) !== FALSE) {
        $customer_name = trim($row[0]);
        $contact_person = trim($row[1]);
        $contact_number = trim($row[2]);
        $address = trim($row[3]);
        $is_active = isset($row[4]) ? (int)$row[4] : 1;

        $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE customer_name = ?");
        $stmt->execute([$customer_name]);
        $exists = $stmt->fetch();

        if ($exists) {
            $oldStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_name = ?");
            $oldStmt->execute([$customer_name]);
            $oldData = $oldStmt->fetch();

            $stmt = $pdo->prepare("UPDATE customers SET contact_person=?, contact_number=?, address=?, is_active=? WHERE customer_name=?");
            $stmt->execute([$contact_person, $contact_number, $address, $is_active, $customer_name]);

            $newStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_name = ?");
            $newStmt->execute([$customer_name]);
            $newData = $newStmt->fetch();
            logAudit('customers', $oldData['customer_id'], 'UPDATE', $oldData, $newData);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (customer_name, contact_person, contact_number, address, is_active) VALUES (?,?,?,?,?)");
            $stmt->execute([$customer_name, $contact_person, $contact_number, $address, $is_active]);
            $new_id = $pdo->lastInsertId();
            $newStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
            $newStmt->execute([$new_id]);
            $newData = $newStmt->fetch();
            logAudit('customers', $new_id, 'INSERT', null, $newData);
        }
    }
    fclose($handle);
    $_SESSION['toast'] = ['message' => 'Customers imported successfully.', 'type' => 'success'];
    header("Location: customers.php");
    exit;
}

// ---------- Handle POST (Create/Update) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['csv_file'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    $customer_name = trim($_POST['customer_name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if (isset($_POST['customer_id']) && $_POST['customer_id'] > 0) {
        $id = (int)$_POST['customer_id'];
        $oldStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $oldStmt->execute([$id]);
        $oldData = $oldStmt->fetch();

        $stmt = $pdo->prepare("UPDATE customers SET customer_name=?, contact_person=?, contact_number=?, address=?, is_active=? WHERE customer_id=?");
        $stmt->execute([$customer_name, $contact_person, $contact_number, $address, $active, $id]);

        $newStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $newStmt->execute([$id]);
        $newData = $newStmt->fetch();
        logAudit('customers', $id, 'UPDATE', $oldData, $newData);

        $_SESSION['toast'] = ['message' => 'Customer updated successfully.', 'type' => 'success'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO customers (customer_name, contact_person, contact_number, address, is_active) VALUES (?,?,?,?,?)");
        $stmt->execute([$customer_name, $contact_person, $contact_number, $address, $active]);
        $new_id = $pdo->lastInsertId();
        $newStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $newStmt->execute([$new_id]);
        $newData = $newStmt->fetch();
        logAudit('customers', $new_id, 'INSERT', null, $newData);

        $_SESSION['toast'] = ['message' => 'Customer added successfully.', 'type' => 'success'];
    }
    header("Location: customers.php");
    exit;
}

// ---------- Delete ----------
if ($action === 'delete' && isset($_GET['id'])) {
    if (!validateCSRFToken($_GET['csrf_token'] ?? '')) die("CSRF validation failed.");
    $id = (int)$_GET['id'];
    $oldStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $oldStmt->execute([$id]);
    $oldData = $oldStmt->fetch();
    if ($oldData) {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->execute([$id]);
        logAudit('customers', $id, 'DELETE', $oldData, null);
        $_SESSION['toast'] = ['message' => 'Customer deleted.', 'type' => 'warning'];
    } else {
        $_SESSION['toast'] = ['message' => 'Customer not found.', 'type' => 'danger'];
    }
    header("Location: customers.php");
    exit;
}

// ---------- Fetch customers ----------
$customers = $pdo->query("SELECT * FROM customers ORDER BY customer_name")->fetchAll();

// ---------- Edit ----------
$edit_customer = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_customer = $stmt->fetch();
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Customer / Store Management</h2>
    <div>
        <a href="customers.php?export=1" class="btn btn-sm btn-info">Export CSV</a>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import CSV</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">Add Customer</button>
    </div>
</div>

<table class="table table-bordered datatable">
    <thead>
        <tr>
            <th>Customer / Store Name</th><th>Contact Person</th><th>Contact Number</th>
            <th>Address</th><th>Active</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($customers as $c): ?>
    <tr>
        <td><?= htmlspecialchars($c['customer_name']) ?></td>
        <td><?= htmlspecialchars($c['contact_person']) ?></td>
        <td><?= htmlspecialchars($c['contact_number']) ?></td>
        <td><?= htmlspecialchars($c['address']) ?></td>
        <td><?= $c['is_active'] ? 'Yes' : 'No' ?></td>
        <td>
            <a href="customers.php?action=edit&id=<?= $c['customer_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="customers.php?action=delete&id=<?= $c['customer_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Add/Edit Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_customer ? 'Edit Customer' : 'Add Customer' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($edit_customer): ?>
                        <input type="hidden" name="customer_id" value="<?= $edit_customer['customer_id'] ?>">
                    <?php endif; ?>
                    <div class="mb-2">
                        <label>Customer / Store Name</label>
                        <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($edit_customer['customer_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($edit_customer['contact_person'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($edit_customer['contact_number'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($edit_customer['address'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" <?= (!$edit_customer || $edit_customer['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                    <h5 class="modal-title">Import Customers (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                        <small class="text-muted">
                            Columns: customer_name, contact_person, contact_number, address, is_active (1/0)<br>
                            The first row must contain column headers.
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
    <?php if ($edit_customer): ?>
    var modal = new bootstrap.Modal(document.getElementById('customerModal'));
    modal.show();
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>