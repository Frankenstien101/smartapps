<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
requireRole('Admin');

// Ensure the audit_log table exists
ensureAuditLogTable();

$filter_table = $_GET['table'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = "1=1";
$params = [];

if ($filter_table) {
    $where .= " AND table_name = ?";
    $params[] = $filter_table;
}
if ($filter_user) {
    $where .= " AND user_id = ?";
    $params[] = $filter_user;
}
if ($filter_action) {
    $where .= " AND action = ?";
    $params[] = $filter_action;
}
if ($date_from) {
    $where .= " AND created_at >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where .= " AND created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$sql = "
    SELECT a.*, u.full_name AS user_name
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE $where
    ORDER BY a.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get distinct tables, users, actions for filters – with error handling
try {
    $tables = $pdo->query("SELECT DISTINCT table_name FROM audit_log ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $tables = [];
}
$users = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name")->fetchAll();
$actions = ['INSERT', 'UPDATE', 'DELETE'];

include '../includes/header.php';
?>
<h2>Audit Log</h2>

<!-- Filter Form -->
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <label class="visually-hidden">Table</label>
        <select name="table" class="form-select form-select-sm">
            <option value="">All Tables</option>
            <?php foreach ($tables as $t): ?>
                <option value="<?= $t ?>" <?= $filter_table == $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="visually-hidden">User</label>
        <select name="user" class="form-select form-select-sm">
            <option value="">All Users</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['user_id'] ?>" <?= $filter_user == $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="visually-hidden">Action</label>
        <select name="action" class="form-select form-select-sm">
            <option value="">All Actions</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?= $a ?>" <?= $filter_action == $a ? 'selected' : '' ?>><?= $a ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="visually-hidden">Date From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
    </div>
    <div class="col-auto">
        <label class="visually-hidden">Date To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="audit_log.php" class="btn btn-sm btn-secondary">Clear</a>
    </div>
</form>

<?php if (empty($logs)): ?>
    <div class="alert alert-info">No audit logs found.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>IP</th>
                    <th>Old Data</th>
                    <th>New Data</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['created_at'] ?></td>
                    <td><?= htmlspecialchars($log['table_name']) ?></td>
                    <td><?= $log['record_id'] ?></td>
                    <td><span class="badge bg-<?= $log['action'] == 'INSERT' ? 'success' : ($log['action'] == 'UPDATE' ? 'warning' : 'danger') ?>"><?= $log['action'] ?></span></td>
                    <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td>
                        <?php if ($log['old_data']): ?>
                            <button class="btn btn-sm btn-outline-secondary view-data" data-type="old" data-data='<?= htmlspecialchars($log['old_data']) ?>'>View</button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['new_data']): ?>
                            <button class="btn btn-sm btn-outline-secondary view-data" data-type="new" data-data='<?= htmlspecialchars($log['new_data']) ?>'>View</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Modal for viewing data -->
<div class="modal fade" id="dataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dataModalTitle">Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="dataModalBody" style="max-height:400px; overflow-y:auto; white-space:pre-wrap; word-break:break-all;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('dataModal'));
    const title = document.getElementById('dataModalTitle');
    const body = document.getElementById('dataModalBody');

    document.querySelectorAll('.view-data').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            const raw = this.dataset.data;
            title.textContent = type === 'old' ? 'Old Data' : 'New Data';
            try {
                // Try to parse and pretty print JSON
                const parsed = JSON.parse(raw);
                body.textContent = JSON.stringify(parsed, null, 2);
            } catch (e) {
                // If not valid JSON, show as raw
                body.textContent = raw;
            }
            modal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>