<?php
// pages/user-list.php - User Management Page
?>
<style>
    .users-container {
        padding: 0;
        width: 100%;
    }
    
    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 15px;
        transition: transform 0.2s;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 10px;
    }
    
    .stat-icon.primary { background: rgba(79, 158, 255, 0.15); color: #4f9eff; }
    .stat-icon.success { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .stat-icon.warning { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .stat-icon.danger { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .stat-icon.info { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #1a2a3a;
    }
    
    .stat-label {
        font-size: 12px;
        color: #6c7a91;
        margin-top: 5px;
    }
    
    /* Filter Section */
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .search-box {
        position: relative;
        flex: 2;
        min-width: 200px;
    }
    
    .search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .filter-select {
        padding: 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 13px;
        background: white;
        min-width: 130px;
    }
    
    .btn-add {
        background: #4f9eff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-add:hover {
        background: #3a7fd9;
    }
    
    /* Users Table */
    .users-table-container {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .table-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eef2f7;
        font-weight: 600;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table {
        width: 100%;
        font-size: 13px;
        margin-bottom: 0;
    }
    
    .table th {
        background: #f8fafc;
        padding: 12px;
        font-weight: 600;
    }
    
    .table td {
        padding: 12px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .badge-admin { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-manager { background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-staff { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-technician { background: #fce7f3; color: #db2777; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-viewer { background: #e0e7ff; color: #4f46e5; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
    .badge-active { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-inactive { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
    .btn-action {
        padding: 5px 8px;
        border-radius: 6px;
        font-size: 11px;
        margin: 2px;
        cursor: pointer;
        border: none;
    }
    
    .btn-edit { background: #f59e0b; color: white; }
    .btn-role { background: #4f9eff; color: white; }
    .btn-status { background: #10b981; color: white; }
    .btn-delete { background: #dc3545; color: white; }
    .btn-reset { background: #6c757d; color: white; }
    
    /* Modal Styles */
    .modal-content {
        border-radius: 20px;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #1f2937, #2d3a4a);
        color: white;
        border-radius: 20px 20px 0 0;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .form-label {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 5px;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 30px;
        height: 30px;
        border: 3px solid #e2e8f0;
        border-radius: 50%;
        border-top-color: #4f9eff;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .empty-state {
        text-align: center;
        padding: 60px;
        color: #94a3b8;
    }
    
    .toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        left: 20px;
        z-index: 1100;
    }
    
    .toast {
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        border-left: 4px solid;
    }
    
    .toast.success { border-left-color: #28a745; }
    .toast.error { border-left-color: #dc3545; }
    .toast.warning { border-left-color: #ffc107; }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .table th, .table td {
            padding: 8px;
            font-size: 11px;
        }
        .filter-section {
            flex-direction: column;
        }
        .filter-select, .search-box, .btn-add {
            width: 100%;
        }
    }
</style>

<div class="users-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-users"></i> User Management</h4>
            <p class="text-muted mb-0">Manage system users and permissions</p>
        </div>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-value" id="totalUsers">0</div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
            <div class="stat-value" id="activeUsers">0</div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-user-shield"></i></div>
            <div class="stat-value" id="adminCount">0</div>
            <div class="stat-label">Administrators</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="activeThisWeek">0</div>
            <div class="stat-label">Active This Week</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, username, or email...">
        </div>
        <select id="roleFilter" class="filter-select" onchange="filterUsers()">
            <option value="all">All Roles</option>
            <option value="admin">Admin</option>
            <option value="manager">Manager</option>
            <option value="staff">Staff</option>
            <option value="technician">Technician</option>
            <option value="viewer">Viewer</option>
        </select>
        <select id="statusFilter" class="filter-select" onchange="filterUsers()">
            <option value="all">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>
    
    <!-- Users Table -->
    <div class="users-table-container">
        <div class="table-header">
            <span><i class="fas fa-list"></i> System Users</span>
            <button class="btn-refresh" onclick="loadUsers()" style="background:transparent; border:none; color:#4f9eff;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr><td colspan="8" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-plus"></i> Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="userId">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" id="username" class="form-control" placeholder="Username">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" id="fullname" class="form-control" placeholder="Full name">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" id="email" class="form-control" placeholder="Email address">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" id="phone" class="form-control" placeholder="Phone number">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea id="address" class="form-control" rows="2" placeholder="Address"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role *</label>
                        <select id="role" class="form-select">
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                            <option value="technician">Technician</option>
                            <option value="viewer">Viewer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" id="password" class="form-control" placeholder="Password">
                        <small class="text-muted">Leave blank to keep current password (for edit)</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="saveUserBtn">Save User</button>
            </div>
        </div>
    </div>
</div>

<!-- Change Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-tag"></i> Change User Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="roleUserId">
                <div class="mb-3">
                    <label class="form-label">User</label>
                    <input type="text" id="roleUserName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Role</label>
                    <select id="newRole" class="form-select">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <option value="technician">Technician</option>
                        <option value="viewer">Viewer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="updateRoleBtn">Update Role</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key"></i> Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="resetUserId">
                <div class="mb-3">
                    <label class="form-label">User</label>
                    <input type="text" id="resetUserName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="text" id="newPassword" class="form-control" value="password123">
                    <small class="text-muted">Default: password123</small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning" id="confirmResetBtn">Reset Password</button>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/userdata.php';

let allUsers = [];
let filteredUsers = [];
let currentPage = 1;
let itemsPerPage = 15;

// ============================================
// API CALLS
// ============================================

async function apiCall(action, method = 'GET', data = null) {
    try {
        const options = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (data) options.body = JSON.stringify(data);
        const response = await fetch(`${API_URL}?action=${action}`, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false };
    }
}

async function loadUsers() {
    const result = await apiCall('getUsers');
    if (result.success && result.data) {
        allUsers = result.data;
        filterUsers();
        loadStats();
    } else {
        document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted">No users found<\/td><\/tr>';
    }
}

async function loadStats() {
    const result = await apiCall('getUserStats');
    if (result.success && result.data) {
        const stats = result.data;
        document.getElementById('totalUsers').innerText = stats.TotalUsers || 0;
        document.getElementById('activeUsers').innerText = stats.ActiveUsers || 0;
        document.getElementById('adminCount').innerText = stats.AdminCount || 0;
        document.getElementById('activeThisWeek').innerText = stats.ActiveThisWeek || 0;
    }
}

async function createUser() {
    const data = {
        username: document.getElementById('username').value.trim(),
        password: document.getElementById('password').value,
        fullname: document.getElementById('fullname').value.trim(),
        email: document.getElementById('email').value.trim(),
        role: document.getElementById('role').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value
    };
    
    if (!data.username || !data.password || !data.fullname || !data.email) {
        showToast('Please fill in required fields', 'warning');
        return;
    }
    
    const result = await apiCall('createUser', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
        clearForm();
        await loadUsers();
    } else {
        showToast(result.message || 'Failed to create user', 'error');
    }
}

async function updateUser() {
    const userId = document.getElementById('userId').value;
    const data = {
        user_id: parseInt(userId),
        fullname: document.getElementById('fullname').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value
    };
    
    const result = await apiCall('updateUser', 'PUT', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
        await loadUsers();
    } else {
        showToast(result.message || 'Failed to update user', 'error');
    }
}

async function updateUserRole() {
    const userId = document.getElementById('roleUserId').value;
    const newRole = document.getElementById('newRole').value;
    
    const data = {
        user_id: parseInt(userId),
        role: newRole
    };
    
    const result = await apiCall('updateUserRole', 'PUT', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('roleModal')).hide();
        await loadUsers();
    } else {
        showToast(result.message || 'Failed to update role', 'error');
    }
}

async function toggleUserStatus(userId, currentStatus) {
    const action = currentStatus == 1 ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} this user?`)) return;
    
    const result = await apiCall('toggleUserStatus', 'PUT', { user_id: userId });
    if (result.success) {
        showToast(result.message, 'success');
        await loadUsers();
    } else {
        showToast(result.message || 'Failed to toggle status', 'error');
    }
}

async function resetUserPassword() {
    const userId = document.getElementById('resetUserId').value;
    const newPassword = document.getElementById('newPassword').value;
    
    const data = {
        user_id: parseInt(userId),
        new_password: newPassword
    };
    
    const result = await apiCall('resetPassword', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('resetModal')).hide();
    } else {
        showToast(result.message || 'Failed to reset password', 'error');
    }
}

async function deleteUser(userId, username) {
    if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) return;
    
    const result = await apiCall('deleteUser', 'DELETE', { user_id: userId });
    if (result.success) {
        showToast(result.message, 'success');
        await loadUsers();
    } else {
        showToast(result.message || 'Failed to delete user', 'error');
    }
}

async function viewUser(userId) {
    const result = await apiCall(`getUserById&id=${userId}`);
    if (result.success && result.data) {
        displayUserDetails(result.data, result.activities);
    }
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function filterUsers() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    filteredUsers = allUsers.filter(user => {
        const matchesSearch = searchTerm === '' || 
            (user.FullName && user.FullName.toLowerCase().includes(searchTerm)) ||
            (user.Username && user.Username.toLowerCase().includes(searchTerm)) ||
            (user.Email && user.Email.toLowerCase().includes(searchTerm));
        
        const matchesRole = roleFilter === 'all' || user.Role === roleFilter;
        const matchesStatus = statusFilter === 'all' || user.IsActive == statusFilter;
        
        return matchesSearch && matchesRole && matchesStatus;
    });
    
    currentPage = 1;
    displayUsersTable();
}

function displayUsersTable() {
    const tbody = document.getElementById('usersTableBody');
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filteredUsers.slice(start, start + itemsPerPage);
    
    if (paginated.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No users found<\/td><\/tr>';
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    
    tbody.innerHTML = paginated.map(user => {
        let roleClass = '';
        switch(user.Role) {
            case 'admin': roleClass = 'badge-admin'; break;
            case 'manager': roleClass = 'badge-manager'; break;
            case 'staff': roleClass = 'badge-staff'; break;
            case 'technician': roleClass = 'badge-technician'; break;
            default: roleClass = 'badge-viewer';
        }
        
        const statusClass = user.IsActive == 1 ? 'badge-active' : 'badge-inactive';
        const statusText = user.IsActive == 1 ? 'ACTIVE' : 'INACTIVE';
        
        return `<tr>
            <td>${user.UserID}<\/td>
            <td><strong>${escapeHtml(user.Username)}<\/strong><\/td>
            <td>${escapeHtml(user.FullName)}<\/td>
            <td>${escapeHtml(user.Email)}<\/td>
            <td><span class="${roleClass}">${user.Role?.toUpperCase()}<\/span><\/td>
            <td><span class="${statusClass}">${statusText}<\/span><\/td>
            <td><small>${user.LastLogin || '-'}<\/small><\/td>
            <td>
                <button class="btn-action btn-edit" onclick="openEditModal(${user.UserID})" title="Edit"><i class="fas fa-edit"><\/i><\/button>
                <button class="btn-action btn-role" onclick="openRoleModal(${user.UserID}, '${escapeHtml(user.FullName)}', '${user.Role}')" title="Change Role"><i class="fas fa-user-tag"><\/i><\/button>
                <button class="btn-action btn-status" onclick="toggleUserStatus(${user.UserID}, ${user.IsActive})" title="${user.IsActive == 1 ? 'Deactivate' : 'Activate'}"><i class="fas fa-${user.IsActive == 1 ? 'ban' : 'check-circle'}"><\/i><\/button>
                <button class="btn-action btn-reset" onclick="openResetModal(${user.UserID}, '${escapeHtml(user.FullName)}')" title="Reset Password"><i class="fas fa-key"><\/i><\/button>
                <button class="btn-action btn-delete" onclick="deleteUser(${user.UserID}, '${escapeHtml(user.Username)}')" title="Delete"><i class="fas fa-trash"><\/i><\/button>
                <button class="btn-action btn-view" onclick="viewUser(${user.UserID})" title="View Details"><i class="fas fa-eye"><\/i><\/button>
            <\/td>
        <\/tr>`;
    }).join('');
    
    const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
    let paginationHTML = '<div class="d-flex justify-content-center gap-1">';
    for (let i = 1; i <= totalPages; i++) {
        paginationHTML += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-secondary'}" onclick="goToPage(${i})">${i}</button>`;
    }
    paginationHTML += '</div>';
    document.getElementById('pagination').innerHTML = paginationHTML;
}

function goToPage(page) {
    currentPage = page;
    displayUsersTable();
}

function displayUserDetails(user, activities) {
    let roleClass = '';
    switch(user.Role) {
        case 'admin': roleClass = 'badge-admin'; break;
        case 'manager': roleClass = 'badge-manager'; break;
        case 'staff': roleClass = 'badge-staff'; break;
        case 'technician': roleClass = 'badge-technician'; break;
        default: roleClass = 'badge-viewer';
    }
    
    let html = `
        <div class="row">
            <div class="col-md-6">
                <div class="card p-3 mb-2">
                    <h6><i class="fas fa-user"></i> Personal Information</h6>
                    <p><strong>Full Name:</strong> ${escapeHtml(user.FullName)}<br>
                    <strong>Username:</strong> ${escapeHtml(user.Username)}<br>
                    <strong>Email:</strong> ${escapeHtml(user.Email)}<br>
                    <strong>Phone:</strong> ${user.Phone || '-'}<br>
                    <strong>Address:</strong> ${user.Address || '-'}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3 mb-2">
                    <h6><i class="fas fa-shield-alt"></i> Account Information</h6>
                    <p><strong>Role:</strong> <span class="${roleClass}">${user.Role?.toUpperCase()}</span><br>
                    <strong>Status:</strong> ${user.IsActive == 1 ? '<span class="badge-active">ACTIVE</span>' : '<span class="badge-inactive">INACTIVE</span>'}<br>
                    <strong>Last Login:</strong> ${user.LastLogin || '-'}<br>
                    <strong>Created:</strong> ${user.CreatedAt}<br>
                    <strong>Created By:</strong> ${user.CreatedBy || '-'}</p>
                </div>
            </div>
        </div>
    `;
    
    if (activities && activities.length > 0) {
        html += `<div class="card p-3 mt-2">
            <h6><i class="fas fa-history"></i> Recent Activity</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Activity</th><th>Type</th><th>IP Address</th></tr></thead>
                    <tbody>`;
        activities.forEach(activity => {
            html += `<tr>
                <td><small>${activity.ActivityDate}</small></td>
                <td>${escapeHtml(activity.Activity)}</td>
                <td>${activity.ActivityType}</td>
                <td>${activity.IPAddress || '-'}</td>
            </tr>`;
        });
        html += `</tbody></table></div></div>`;
    }
    
    document.getElementById('viewModalBody').innerHTML = html;
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();
}

// ============================================
// MODAL FUNCTIONS
// ============================================

function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add New User';
    document.getElementById('saveUserBtn').innerHTML = 'Create User';
    document.getElementById('saveUserBtn').onclick = createUser;
    clearForm();
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

function openEditModal(userId) {
    const user = allUsers.find(u => u.UserID == userId);
    if (!user) return;
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit User';
    document.getElementById('saveUserBtn').innerHTML = 'Update User';
    document.getElementById('saveUserBtn').onclick = updateUser;
    
    document.getElementById('userId').value = user.UserID;
    document.getElementById('username').value = user.Username;
    document.getElementById('fullname').value = user.FullName;
    document.getElementById('email').value = user.Email;
    document.getElementById('phone').value = user.Phone || '';
    document.getElementById('address').value = user.Address || '';
    document.getElementById('role').value = user.Role;
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = 'Leave blank to keep current password';
    
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

function openRoleModal(userId, userName, currentRole) {
    document.getElementById('roleUserId').value = userId;
    document.getElementById('roleUserName').value = userName;
    document.getElementById('newRole').value = currentRole;
    
    const modal = new bootstrap.Modal(document.getElementById('roleModal'));
    modal.show();
}

function openResetModal(userId, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserName').value = userName;
    document.getElementById('newPassword').value = 'password123';
    
    const modal = new bootstrap.Modal(document.getElementById('resetModal'));
    modal.show();
}

function clearForm() {
    document.getElementById('userId').value = '';
    document.getElementById('username').value = '';
    document.getElementById('fullname').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('address').value = '';
    document.getElementById('role').value = 'staff';
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = 'Password';
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.style.display = 'block';
    toast.style.marginBottom = '10px';
    
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle' };
    
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas ${icons[type] || 'fa-info-circle'} me-2"></i>
            <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000, autohide: true });
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// EVENT LISTENERS
// ============================================

document.getElementById('searchInput').addEventListener('input', filterUsers);
document.getElementById('updateRoleBtn').addEventListener('click', updateUserRole);
document.getElementById('confirmResetBtn').addEventListener('click', resetUserPassword);

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});
</script>