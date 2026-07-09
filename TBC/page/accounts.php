<?php
// account-approval.php - Admin only page


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    die('<h2>Access Denied</h2><p>You are not authorized to access this page.</p>');
}
?>

<style>
.approval-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}
.approval-header {
    background: linear-gradient(135deg, #0284c8, #38bdf8);
    padding: 16px 20px;
    color: white;
}
.table-users {
    width: 100%;
    border-collapse: collapse;
}
.table-users th {
    background: #f1f5f9;
    padding: 12px 15px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
}
.table-users td {
    padding: 12px 15px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 13px;
}
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-active { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.btn-approve {
    background: #10b981;
    border: none;
    padding: 6px 16px;
    border-radius: 6px;
    color: white;
    font-size: 12px;
    cursor: pointer;
    margin-right: 5px;
}
.btn-reject {
    background: #ef4444;
    border: none;
    padding: 6px 16px;
    border-radius: 6px;
    color: white;
    font-size: 12px;
    cursor: pointer;
}
.filter-bar {
    background: #f8fafc;
    padding: 15px 20px;
    border-bottom: 1px solid #e2e8f0;
}
.empty-state {
    text-align: center;
    padding: 60px;
    color: #94a3b8;
}
</style>

<div class="approval-card">
    <div class="approval-header">
        <h5 class="mb-0"><i class="fa fa-user-check"></i> Account Approval</h5>
        <p class="mb-0 mt-1 small">Review and approve/reject pending user accounts</p>
    </div>

    <div class="filter-bar">
        <div class="row g-2">
            <div class="col-md-3">
                <select id="statusFilter" class="form-select form-select-sm">
                    <option value="all">All Users</option>
                    <option value="PENDING" selected>Pending Approval</option>
                    <option value="ACTIVE">Active</option>
                    <option value="REJECTED">Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search...">
            </div>
            <div class="col-md-2">
                <select id="branchFilter" class="form-select form-select-sm">
                    <option value="all">All Branches</option>
                    <option value="DVO">DVO</option>
                    <option value="MNL">MNL</option>
                    <option value="CEB">CEB</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary" id="refreshBtn" style="background:#0284c8;">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="table-users" id="usersTable">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Branch</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="usersTableBody">
                <tr><td colspan="7" class="empty-state"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Load users
function loadUsers() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    const branch = document.getElementById('branchFilter').value;
    
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="empty-state"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>';
    
    fetch(`/TBC/page/user_approval_api.php?status=${status}&search=${encodeURIComponent(search)}&branch=${branch}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.users.length > 0) {
                let html = '';
                data.users.forEach(u => {
                    let statusClass = u.STATUS === 'ACTIVE' ? 'status-active' : (u.STATUS === 'REJECTED' ? 'status-rejected' : 'status-pending');
                    html += `<tr>
                        <td>${u.LINEID}</td>
                        <td><strong>${escapeHtml(u.USERNAME)}</strong></td>
                        <td>${escapeHtml(u.FULLNAME)}</td>
                        <td><span class="badge ${u.ROLE === 'ADMIN' ? 'bg-danger' : 'bg-secondary'}">${u.ROLE}</span></td>
                        <td>${escapeHtml(u.BRANCH)}</td>
                        <td><span class="status-badge ${statusClass}">${u.STATUS}</span></td>
                        <td>`;
                    
                    if (u.STATUS === 'PENDING') {
                        html += `<button class="btn-approve" onclick="updateUser(${u.LINEID}, 'ACTIVE')"><i class="fa fa-check"></i> Approve</button>
                                 <button class="btn-reject" onclick="updateUser(${u.LINEID}, 'REJECTED')"><i class="fa fa-times"></i> Reject</button>`;
                    }
                    html += `</td></tr>`;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state"><i class="fa fa-users"></i><p>No users found</p></td></tr>';
            }
        })
        .catch(err => tbody.innerHTML = '<tr><td colspan="7" class="empty-state"><i class="fa fa-exclamation-circle"></i><p>Error loading</p></td></tr>');
}

// Update user status
function updateUser(userId, status) {
    if (!confirm(`Are you sure you want to ${status === 'ACTIVE' ? 'APPROVE' : 'REJECT'} this user?`)) return;
    
    fetch('/TBC/page/user_approval_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, status: status })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) loadUsers();
    })
    .catch(err => alert('Error: ' + err));
}

function escapeHtml(str) {
    if (!str) return '-';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Event listeners
document.getElementById('statusFilter').addEventListener('change', loadUsers);
document.getElementById('searchInput').addEventListener('keyup', loadUsers);
document.getElementById('branchFilter').addEventListener('change', loadUsers);
document.getElementById('refreshBtn').addEventListener('click', loadUsers);

// Load on page load
loadUsers();
</script>