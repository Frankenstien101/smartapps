<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/helpers.php';
requireRole('Admin');

ensureCustomerIdColumn();
ensureUserStoresTable();
ensureAuditLogTable();

$action = $_GET['action'] ?? 'list';
$error = '';

$allStores = $pdo->query("SELECT customer_id, customer_name FROM customers WHERE is_active = 1 ORDER BY customer_name")->fetchAll();

// Handle POST (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF validation failed.");

    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'];
    $active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = $_POST['password'] ?? '';
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $assigned_stores = $_POST['assigned_stores'] ?? [];

    // Basic email validation (optional)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($role === 'Buyer' && !$customer_id) {
        $error = "A store must be selected for a Buyer account.";
    } elseif ($role === 'SalesRep' && empty($assigned_stores)) {
        $error = "At least one store must be assigned to a SalesRep account.";
    } else {
        $valid_roles = ['Admin', 'SalesRep', 'Buyer'];
        if (!in_array($role, $valid_roles)) {
            $error = "Invalid role selected.";
        } elseif ($username === '' || $full_name === '') {
            $error = "Username and full name are required.";
        } else {
            if (isset($_POST['user_id']) && $_POST['user_id'] > 0) {
                $user_id = (int)$_POST['user_id'];
                if ($user_id === (int)$_SESSION['user_id'] && $role !== 'Admin') {
                    $error = "You can't change your own role away from Admin.";
                } elseif ($user_id === (int)$_SESSION['user_id'] && !$active) {
                    $error = "You can't deactivate your own account.";
                } else {
                    $oldStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $oldStmt->execute([$user_id]);
                    $oldData = $oldStmt->fetch();

                    if ($new_password !== '') {
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, is_active=?, password_hash=?, customer_id=? WHERE user_id=?");
                        $stmt->execute([$username, $full_name, $email, $role, $active, $hash, $customer_id, $user_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, is_active=?, customer_id=? WHERE user_id=?");
                        $stmt->execute([$username, $full_name, $email, $role, $active, $customer_id, $user_id]);
                    }

                    if ($role === 'SalesRep') {
                        $pdo->prepare("DELETE FROM user_stores WHERE user_id = ?")->execute([$user_id]);
                        foreach ($assigned_stores as $store_id) {
                            $pdo->prepare("INSERT INTO user_stores (user_id, customer_id) VALUES (?, ?)")->execute([$user_id, $store_id]);
                        }
                    } else {
                        $pdo->prepare("DELETE FROM user_stores WHERE user_id = ?")->execute([$user_id]);
                    }

                    $newStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $newStmt->execute([$user_id]);
                    $newData = $newStmt->fetch();
                    logAudit('users', $user_id, 'UPDATE', $oldData, $newData);

                    $_SESSION['toast'] = ['message' => 'User updated successfully.', 'type' => 'success'];
                    header("Location: users.php");
                    exit;
                }
            } else {
                if ($new_password === '') {
                    $error = "Password is required for a new account.";
                } else {
                    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $check->execute([$username]);
                    if ($check->fetchColumn() > 0) {
                        $error = "That username is already taken.";
                    } else {
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active, customer_id) VALUES (?,?,?,?,?,?,?)");
                        $stmt->execute([$username, $hash, $full_name, $email, $role, $active, $customer_id]);
                        $user_id = $pdo->lastInsertId();

                        if ($role === 'SalesRep') {
                            foreach ($assigned_stores as $store_id) {
                                $pdo->prepare("INSERT INTO user_stores (user_id, customer_id) VALUES (?, ?)")->execute([$user_id, $store_id]);
                            }
                        }

                        $newStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                        $newStmt->execute([$user_id]);
                        $newData = $newStmt->fetch();
                        logAudit('users', $user_id, 'INSERT', null, $newData);

                        $_SESSION['toast'] = ['message' => 'User added successfully.', 'type' => 'success'];
                        header("Location: users.php");
                        exit;
                    }
                }
            }
        }
    }
}

// Deactivate/Activate
if ($action === 'deactivate' && isset($_GET['id'])) {
    if (!validateCSRFToken($_GET['csrf_token'] ?? '')) die("CSRF validation failed.");
    $id = (int)$_GET['id'];
    if ($id === (int)$_SESSION['user_id']) {
        $error = "You can't deactivate your own account.";
    } else {
        $oldStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $oldStmt->execute([$id]);
        $oldData = $oldStmt->fetch();
        $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?")->execute([$id]);
        $newStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $newStmt->execute([$id]);
        $newData = $newStmt->fetch();
        logAudit('users', $id, 'UPDATE', $oldData, $newData);
        $_SESSION['toast'] = ['message' => 'User deactivated.', 'type' => 'warning'];
        header("Location: users.php");
        exit;
    }
}
if ($action === 'activate' && isset($_GET['id'])) {
    if (!validateCSRFToken($_GET['csrf_token'] ?? '')) die("CSRF validation failed.");
    $id = (int)$_GET['id'];
    $oldStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $oldStmt->execute([$id]);
    $oldData = $oldStmt->fetch();
    $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?")->execute([$id]);
    $newStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $newStmt->execute([$id]);
    $newData = $newStmt->fetch();
    logAudit('users', $id, 'UPDATE', $oldData, $newData);
    $_SESSION['toast'] = ['message' => 'User activated.', 'type' => 'success'];
    header("Location: users.php");
    exit;
}

// Fetch users with email
$users = $pdo->query("
    SELECT u.*, c.customer_name AS store_name
    FROM users u
    LEFT JOIN customers c ON u.customer_id = c.customer_id
    ORDER BY u.full_name
")->fetchAll();

foreach ($users as &$user) {
    if ($user['role'] === 'SalesRep') {
        $stmt = $pdo->prepare("SELECT customer_id FROM user_stores WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $user['assigned_stores'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $user['assigned_stores'] = [];
    }
}

$edit_user = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_user = $stmt->fetch();
    if ($edit_user) {
        $stmt = $pdo->prepare("SELECT customer_id FROM user_stores WHERE user_id = ?");
        $stmt->execute([$edit_user['user_id']]);
        $edit_user['assigned_stores'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

include '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>User Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">Add User</button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<table class="table table-bordered datatable">
    <thead>
        <tr><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Store</th><th>Assigned Stores (SalesRep)</th><th>Active</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['full_name']) ?></td>
        <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
        <td><?= htmlspecialchars($u['store_name'] ?? 'N/A') ?></td>
        <td>
            <?php if ($u['role'] === 'SalesRep'): ?>
                <?php 
                    $storeNames = [];
                    foreach ($u['assigned_stores'] as $sid) {
                        $s = $pdo->prepare("SELECT customer_name FROM customers WHERE customer_id = ?");
                        $s->execute([$sid]);
                        $storeNames[] = $s->fetchColumn();
                    }
                    echo htmlspecialchars(implode(', ', $storeNames)) ?: 'None';
                ?>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </td>
        <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
        <td>
            <a href="users.php?action=edit&id=<?= $u['user_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <?php if ((int)$u['user_id'] !== (int)$_SESSION['user_id']): ?>
                <?php if ($u['is_active']): ?>
                    <a href="users.php?action=deactivate&id=<?= $u['user_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this user?')">Deactivate</a>
                <?php else: ?>
                    <a href="users.php?action=activate&id=<?= $u['user_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="btn btn-sm btn-success">Activate</a>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-muted">(you)</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_user ? 'Edit User' : 'Add User' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
                    <?php endif; ?>
                    <div class="mb-2">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($edit_user['username'] ?? '') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($edit_user['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>" placeholder="user@example.com">
                        <small class="text-muted">Required for email notifications.</small>
                    </div>
                    <div class="mb-2">
                        <label>Role</label>
                        <select name="role" class="form-select" required>
                            <?php foreach (['Admin', 'SalesRep', 'Buyer'] as $r): ?>
                                <option value="<?= $r ?>" <?= (isset($edit_user) && $edit_user['role'] === $r) ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Assigned Store (for Buyer)</label>
                        <select name="customer_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($allStores as $store): ?>
                                <option value="<?= $store['customer_id'] ?>" <?= (isset($edit_user) && $edit_user['customer_id'] == $store['customer_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($store['customer_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Required for Buyer role.</small>
                    </div>
                    <div class="mb-2" id="assignedStoresDiv">
                        <label>Assigned Stores (for SalesRep)</label>
                        <select name="assigned_stores[]" class="form-select" multiple size="5">
                            <?php foreach ($allStores as $store): ?>
                                <option value="<?= $store['customer_id'] ?>" <?= (isset($edit_user) && in_array($store['customer_id'], $edit_user['assigned_stores'] ?? [])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($store['customer_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl to select multiple stores. Required for SalesRep.</small>
                    </div>
                    <div class="mb-2">
                        <label><?= $edit_user ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                        <input type="password" name="password" class="form-control" <?= $edit_user ? '' : 'required' ?>>
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" <?= (!$edit_user || $edit_user['is_active']) ? 'checked' : '' ?>>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.querySelector('select[name="role"]');
    const assignedDiv = document.getElementById('assignedStoresDiv');
    function toggleAssignedStores() {
        if (roleSelect.value === 'SalesRep') {
            assignedDiv.style.display = 'block';
        } else {
            assignedDiv.style.display = 'none';
        }
    }
    roleSelect.addEventListener('change', toggleAssignedStores);
    toggleAssignedStores();
});
</script>

<?php
if ($edit_user) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        });
    </script>";
}
include '../includes/footer.php';
?>