<?php

require_once "./DB/dbcon.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['Role'] != 'ADMIN') {
    header("Location: /anubis/login.php");
    exit();
}

// Helper functions
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Handle user operations
$message = '';
$message_type = '';

// Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name_of_user = $_POST['name_of_user'] ?? '';
        $role = $_POST['role'] ?? 'USER';
        $status = $_POST['status'] ?? 'ACTIVE';
        $company = $_POST['company'] ?? '';
        $site = $_POST['site'] ?? '';
        
        $check_sql = "SELECT COUNT(*) FROM tc_web_users WHERE USERNAME = :username";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([':username' => $username]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists > 0) {
            $message = "Username already exists!";
            $message_type = "danger";
        } else {
            try {
                $hashed_password = hashPassword($password);
                $sql = "INSERT INTO tc_web_users (USERNAME, PASSWORD, NAME_OF_USER, ROLE, STATUS, COMPANY, SITE) 
                        VALUES (:username, :password, :name_of_user, :role, :status, :company, :site)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':name_of_user' => $name_of_user,
                    ':role' => $role,
                    ':status' => $status,
                    ':company' => $company,
                    ':site' => $site
                ]);
                $message = "User added successfully!";
                $message_type = "success";
            } catch (PDOException $e) {
                $message = "Error adding user: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
    
    // Update User
    elseif ($_POST['action'] == 'edit') {
        $lineid = $_POST['lineid'];
        $username = trim($_POST['username'] ?? '');
        $name_of_user = $_POST['name_of_user'] ?? '';
        $role = $_POST['role'] ?? 'USER';
        $status = $_POST['status'] ?? 'ACTIVE';
        $company = $_POST['company'] ?? '';
        $site = $_POST['site'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        try {
            if (!empty($new_password)) {
                $hashed_password = hashPassword($new_password);
                $sql = "UPDATE tc_web_users SET 
                        USERNAME = :username,
                        PASSWORD = :password,
                        NAME_OF_USER = :name_of_user,
                        ROLE = :role,
                        STATUS = :status,
                        COMPANY = :company,
                        SITE = :site
                        WHERE LINEID = :lineid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':name_of_user' => $name_of_user,
                    ':role' => $role,
                    ':status' => $status,
                    ':company' => $company,
                    ':site' => $site,
                    ':lineid' => $lineid
                ]);
            } else {
                $sql = "UPDATE tc_web_users SET 
                        USERNAME = :username,
                        NAME_OF_USER = :name_of_user,
                        ROLE = :role,
                        STATUS = :status,
                        COMPANY = :company,
                        SITE = :site
                        WHERE LINEID = :lineid";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':name_of_user' => $name_of_user,
                    ':role' => $role,
                    ':status' => $status,
                    ':company' => $company,
                    ':site' => $site,
                    ':lineid' => $lineid
                ]);
            }
            $message = "User updated successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error updating user: " . $e->getMessage();
            $message_type = "danger";
        }
    }
    
    // Delete User
    elseif ($_POST['action'] == 'delete') {
        $lineid = $_POST['lineid'];
        
        try {
            $sql = "DELETE FROM tc_web_users WHERE LINEID = :lineid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':lineid' => $lineid]);
            $message = "User deleted successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error deleting user: " . $e->getMessage();
            $message_type = "danger";
        }
    }
    
    // Toggle Status
    elseif ($_POST['action'] == 'toggle_status') {
        $lineid = $_POST['lineid'];
        $current_status = $_POST['current_status'];
        $new_status = ($current_status == 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        
        try {
            $sql = "UPDATE tc_web_users SET STATUS = :status WHERE LINEID = :lineid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':status' => $new_status, ':lineid' => $lineid]);
            $message = "Status updated successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error updating status: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Get all users
$sql_users = "SELECT LINEID, USERNAME, NAME_OF_USER, ROLE, STATUS, COMPANY, SITE FROM tc_web_users ORDER BY LINEID";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Container styles - fits into main page */
.users-section {
    padding: 20px;
}

.users-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.users-header h3 {
    color: #d4af37;
    font-size: 20px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-gold {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    border: none;
    color: #000;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 13px;
}

.btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
    color: #000;
}

.btn-outline-gold {
    background: transparent;
    border: 1px solid rgba(212, 175, 55, 0.5);
    color: #d4af37;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-size: 11px;
}

.btn-outline-gold:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: #d4af37;
    color: #d4af37;
}

.btn-outline-danger {
    background: transparent;
    border: 1px solid rgba(220, 53, 69, 0.5);
    color: #dc3545;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-size: 11px;
}

.btn-outline-danger:hover {
    background: rgba(220, 53, 69, 0.1);
    border-color: #dc3545;
    color: #dc3545;
}

.btn-outline-success {
    background: transparent;
    border: 1px solid rgba(40, 167, 69, 0.5);
    color: #28a745;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-size: 11px;
}

.btn-outline-success:hover {
    background: rgba(40, 167, 69, 0.1);
    border-color: #28a745;
    color: #28a745;
}

.users-table-wrapper {
    background: rgba(10, 10, 15, 0.95);
    border-radius: 12px;
    border: 1px solid rgba(212, 175, 55, 0.2);
    overflow-x: auto;
}

.users-table {
    width: 100%;
    color: #e0e0e0;
    background: transparent;
    font-size: 13px;
}

.users-table th {
    background: rgba(212, 175, 55, 0.1);
    color: #d4af37;
    padding: 12px 15px;
    font-weight: 600;
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
}

.users-table td {
    padding: 10px 15px;
    border-bottom: 1px solid rgba(212, 175, 55, 0.1);
    vertical-align: middle;
}

.users-table tr:hover {
    background: rgba(212, 175, 55, 0.05);
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.status-active {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-inactive {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.role-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.role-admin {
    background: rgba(212, 175, 55, 0.2);
    color: #d4af37;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.role-user {
    background: rgba(108, 117, 125, 0.2);
    color: #adb5bd;
    border: 1px solid rgba(108, 117, 125, 0.3);
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.form-label {
    color: #d4af37;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 5px;
}

.form-control, .form-select {
    background: #0a0a0a;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 6px;
    color: #fff;
    font-size: 13px;
    padding: 6px 10px;
}

.form-control:focus, .form-select:focus {
    background: #111;
    border-color: #d4af37;
    box-shadow: 0 0 3px rgba(212, 175, 55, 0.3);
    color: #fff;
}

.alert-custom {
    border-radius: 8px;
    padding: 10px 15px;
    margin-bottom: 20px;
    font-size: 13px;
}

.search-box {
    background: #0a0a0a;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 6px;
    padding: 6px 12px;
    color: #fff;
    width: 200px;
    font-size: 13px;
}

.search-box:focus {
    border-color: #d4af37;
    outline: none;
}

.table-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: rgba(255,255,255,0.03);
    border-radius: 10px;
    padding: 12px;
    border: 1px solid rgba(212,175,55,0.1);
}

.stat-card .icon {
    font-size: 20px;
    color: #d4af37;
    margin-bottom: 5px;
}

.stat-card .number {
    font-size: 24px;
    font-weight: 700;
    color: #fff;
}

.stat-card .label {
    font-size: 10px;
    color: #888;
    margin-top: 3px;
}

.modal-custom .modal-content {
    background: #0f0f0f;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 12px;
}

.modal-custom .modal-header {
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
    padding: 12px 15px;
}

.modal-custom .modal-header h5 {
    color: #d4af37;
}

.modal-custom .modal-body {
    padding: 15px;
}

.modal-custom .modal-footer {
    border-top: 1px solid rgba(212, 175, 55, 0.2);
    padding: 12px 15px;
}

.btn-close-white {
    filter: brightness(0) invert(1);
}

.btn-secondary {
    background: #2a2a2a;
    border: none;
    color: #ccc;
    font-size: 12px;
    padding: 6px 12px;
}

.btn-secondary:hover {
    background: #3a3a3a;
    color: #fff;
}

@media (max-width: 768px) {
    .users-section {
        padding: 10px;
    }
    .users-table th, .users-table td {
        padding: 6px 8px;
        font-size: 11px;
    }
    .action-buttons {
        flex-direction: column;
    }
    .search-box {
        width: 100%;
    }
}
</style>

<div class="users-section">
    
    <div class="users-header">
        <h3>
            <i class="fas fa-users"></i> 
            User Management
        </h3>
        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= safe($message) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" style="font-size: 10px;"></button>
        </div>
    <?php endif; ?>
    
    <!-- Stats -->
    <?php
        $total_users = count($users);
        $active_users = 0;
        $admin_users = 0;
        foreach ($users as $user) {
            if ($user['STATUS'] == 'ACTIVE') $active_users++;
            if ($user['ROLE'] == 'ADMIN') $admin_users++;
        }
    ?>
    <div class="stats-grid">
        <div class="stat-card"><div class="icon"><i class="fas fa-users"></i></div><div class="number"><?= $total_users ?></div><div class="label">Total Users</div></div>
        <div class="stat-card"><div class="icon"><i class="fas fa-user-check"></i></div><div class="number"><?= $active_users ?></div><div class="label">Active</div></div>
        <div class="stat-card"><div class="icon"><i class="fas fa-user-shield"></i></div><div class="number"><?= $admin_users ?></div><div class="label">Admins</div></div>
    </div>
    
    <div class="table-actions">
        <input type="text" id="searchInput" class="search-box" placeholder="Search users...">
        <select id="roleFilter" class="form-select" style="width: auto;">
            <option value="all">All Roles</option>
            <option value="ADMIN">Admin</option>
            <option value="USER">User</option>
        </select>
        <select id="statusFilter" class="form-select" style="width: auto;">
            <option value="all">All Status</option>
            <option value="ACTIVE">Active</option>
            <option value="INACTIVE">Inactive</option>
        </select>
    </div>
    
    <div class="users-table-wrapper">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Company</th>
                    <th>Site</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php foreach ($users as $user): ?>
                <tr data-role="<?= safe($user['ROLE']) ?>" data-status="<?= safe($user['STATUS']) ?>">
                    <td><?= safe($user['LINEID']) ?></td>
                    <td><strong><?= safe($user['USERNAME']) ?></strong></td>
                    <td><?= safe($user['NAME_OF_USER']) ?></td>
                    <td><span class="role-badge <?= $user['ROLE'] == 'ADMIN' ? 'role-admin' : 'role-user' ?>"><?= safe($user['ROLE']) ?></span></td>
                    <td><span class="status-badge <?= $user['STATUS'] == 'ACTIVE' ? 'status-active' : 'status-inactive' ?>"><?= safe($user['STATUS']) ?></span></td>
                    <td><?= safe($user['COMPANY']) ?></td>
                    <td><?= safe($user['SITE']) ?></td>
                    <td class="action-buttons">
                        <button class="btn-outline-gold edit-btn" 
                                data-lineid="<?= safe($user['LINEID']) ?>" 
                                data-username="<?= safe($user['USERNAME']) ?>" 
                                data-name="<?= safe($user['NAME_OF_USER']) ?>" 
                                data-role="<?= safe($user['ROLE']) ?>" 
                                data-status="<?= safe($user['STATUS']) ?>" 
                                data-company="<?= safe($user['COMPANY']) ?>" 
                                data-site="<?= safe($user['SITE']) ?>" 
                                data-bs-toggle="modal" data-bs-target="#editUserModal">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-outline-danger delete-btn" 
                                data-lineid="<?= safe($user['LINEID']) ?>" 
                                data-username="<?= safe($user['USERNAME']) ?>" 
                                data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="lineid" value="<?= safe($user['LINEID']) ?>">
                            <input type="hidden" name="current_status" value="<?= safe($user['STATUS']) ?>">
                            <button type="submit" class="btn-outline-success">
                                <i class="fas fa-exchange-alt"></i> <?= $user['STATUS'] == 'ACTIVE' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade modal-custom" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2"><label class="form-label">Username</label><input type="text" class="form-control" name="username" required></div>
                    <div class="mb-2"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
                    <div class="mb-2"><label class="form-label">Full Name</label><input type="text" class="form-control" name="name_of_user"></div>
                    <div class="mb-2"><label class="form-label">Role</label><select class="form-select" name="role"><option value="USER">User</option><option value="ADMIN">Admin</option></select></div>
                    <div class="mb-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="ACTIVE">Active</option><option value="INACTIVE">Inactive</option></select></div>
                    <div class="mb-2"><label class="form-label">Company</label><input type="text" class="form-control" name="company"></div>
                    <div class="mb-2"><label class="form-label">Site</label><input type="text" class="form-control" name="site"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade modal-custom" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="lineid" id="edit_lineid">
                    <div class="mb-2"><label class="form-label">Username</label><input type="text" class="form-control" name="username" id="edit_username" required></div>
                    <div class="mb-2"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" placeholder="Leave blank to keep current"></div>
                    <div class="mb-2"><label class="form-label">Full Name</label><input type="text" class="form-control" name="name_of_user" id="edit_name"></div>
                    <div class="mb-2"><label class="form-label">Role</label><select class="form-select" name="role" id="edit_role"><option value="USER">User</option><option value="ADMIN">Admin</option></select></div>
                    <div class="mb-2"><label class="form-label">Status</label><select class="form-select" name="status" id="edit_status"><option value="ACTIVE">Active</option><option value="INACTIVE">Inactive</option></select></div>
                    <div class="mb-2"><label class="form-label">Company</label><input type="text" class="form-control" name="company" id="edit_company"></div>
                    <div class="mb-2"><label class="form-label">Site</label><input type="text" class="form-control" name="site" id="edit_site"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade modal-custom" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="lineid" id="delete_lineid">
                    <p>Delete user <strong id="delete_username"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('searchInput').addEventListener('keyup', function() { filterTable(); });
document.getElementById('roleFilter').addEventListener('change', function() { filterTable(); });
document.getElementById('statusFilter').addEventListener('change', function() { filterTable(); });

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#usersTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matchesSearch = text.includes(search);
        const matchesRole = role === 'all' || row.dataset.role === role;
        const matchesStatus = status === 'all' || row.dataset.status === status;
        row.style.display = (matchesSearch && matchesRole && matchesStatus) ? '' : 'none';
    });
}

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_lineid').value = this.dataset.lineid;
        document.getElementById('edit_username').value = this.dataset.username;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_role').value = this.dataset.role;
        document.getElementById('edit_status').value = this.dataset.status;
        document.getElementById('edit_company').value = this.dataset.company;
        document.getElementById('edit_site').value = this.dataset.site;
    });
});

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_lineid').value = this.dataset.lineid;
        document.getElementById('delete_username').textContent = this.dataset.username;
    });
});
</script>