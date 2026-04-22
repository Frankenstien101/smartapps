<?php
// pages/repairs.php - Repair Management Page
?>
<style>
    .repairs-container {
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
    }
    
    .search-box {
        position: relative;
        flex: 1;
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
    }
    
    /* Buttons */
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
    
    /* Repairs Table */
    .repairs-table-container {
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
    
    .badge-pending { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-in_progress { background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-completed { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-for_pickup { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-cancelled { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
    .btn-action {
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 11px;
        margin: 2px;
        cursor: pointer;
        border: none;
    }
    
    .btn-view { background: #4f9eff; color: white; }
    .btn-edit { background: #f59e0b; color: white; }
    .btn-delete { background: #dc3545; color: white; }
    
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
    }
</style>

<div class="repairs-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-tools"></i> Repair Management</h4>
            <p class="text-muted mb-0">Manage device repair requests</p>
        </div>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-plus"></i> New Repair Request
        </button>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalRepairs">0</div>
            <div class="stat-label">Total Repairs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-value" id="pendingRepairs">0</div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-spinner"></i></div>
            <div class="stat-value" id="inProgressRepairs">0</div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="completedRepairs">0</div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by customer, repair #, or device...">
        </div>
        <select id="statusFilter" class="filter-select" onchange="filterRepairs()">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="for_pickup">For Pickup</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    
    <!-- Repairs Table -->
    <div class="repairs-table-container">
        
       <div class="table-header">
            <span><i class="fas fa-list"></i> Repair Requests</span>
            <button class="btn-refresh" onclick="loadRepairs()" style="background:transparent; border:none; color:#4f9eff;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Repair #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Device</th>
                        <th>Issue</th>
                        <th>Est. Cost</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="repairsTableBody">
                    <tr><td colspan="8" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
    </div>
</div>

<!-- Add/Edit Repair Modal -->
<div class="modal fade" id="repairModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle"></i> New Repair Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="repairId">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="fas fa-user"></i> Customer Information</h6>
                        <div class="mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" id="customerName" class="form-control" placeholder="Full name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" id="customerPhone" class="form-control" placeholder="Contact number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" id="customerEmail" class="form-control" placeholder="Email address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea id="customerAddress" class="form-control" rows="2" placeholder="Complete address"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="fas fa-mobile-alt"></i> Device Information</h6>
                        <div class="mb-3">
                            <label class="form-label">Device Type *</label>
                            <select id="deviceType" class="form-select">
                                <option value="">Select Device Type</option>
                                <option value="Mobile Phone">Mobile Phone</option>
                                <option value="Tablet">Tablet</option>
                                <option value="Laptop">Laptop</option>
                                <option value="Desktop">Desktop</option>
                                <option value="Smartwatch">Smartwatch</option>
                                <option value="Headphones">Headphones</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" id="deviceBrand" class="form-control" placeholder="e.g., Apple, Samsung">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" id="deviceModel" class="form-control" placeholder="Model number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Serial Number</label>
                            <input type="text" id="serialNumber" class="form-control" placeholder="IMEI or Serial #">
                        </div>
                    </div>
                </div>
                
                <h6 class="mb-3"><i class="fas fa-clipboard-list"></i> Repair Details</h6>
                <div class="mb-3">
                    <label class="form-label">Issue Description *</label>
                    <textarea id="issue" class="form-control" rows="3" placeholder="Describe the problem in detail..."></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Estimated Cost (₱)</label>
                        <input type="number" id="estimatedCost" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estimated Days</label>
                        <input type="number" id="estimatedDays" class="form-control" value="3" min="1" placeholder="Days">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Technician</label>
                        <input type="text" id="technician" class="form-control" placeholder="Assigned technician">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Additional Notes</label>
                    <textarea id="notes" class="form-control" rows="2" placeholder="Any additional information..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="saveRepairBtn">Save Repair Request</button>
            </div>
        </div>
    </div>
</div>

<!-- View Repair Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Repair Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="printRepair()"><i class="fas fa-print"></i> Print</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Update Repair Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusRepairId">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select id="newStatus" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="for_pickup">For Pickup</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="mb-3" id="actualCostDiv" style="display:none;">
                    <label class="form-label">Actual Cost (₱)</label>
                    <input type="number" id="actualCost" class="form-control" step="0.01" placeholder="Final repair cost">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="statusNotes" class="form-control" rows="2" placeholder="Add notes about this update..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="updateStatusBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/repairsdata.php';

let allRepairs = [];
let filteredRepairs = [];
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

async function loadRepairs() {
    const result = await apiCall('getRepairs');
    if (result.success && result.data) {
        allRepairs = result.data;
        filterRepairs();
        loadStats();
    } else {
        document.getElementById('repairsTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted">No repair records found<\/td><\/tr>';
    }
}

async function loadStats() {
    const result = await apiCall('getRepairStats');
    if (result.success && result.data) {
        const stats = result.data;
        document.getElementById('totalRepairs').innerText = stats.TotalRepairs || 0;
        document.getElementById('pendingRepairs').innerText = stats.PendingRepairs || 0;
        document.getElementById('inProgressRepairs').innerText = stats.InProgressRepairs || 0;
        document.getElementById('completedRepairs').innerText = stats.CompletedRepairs || 0;
    }
}

async function createRepair() {
    const data = {
        customer_name: document.getElementById('customerName').value.trim(),
        customer_phone: document.getElementById('customerPhone').value,
        customer_email: document.getElementById('customerEmail').value,
        customer_address: document.getElementById('customerAddress').value,
        device_type: document.getElementById('deviceType').value,
        device_brand: document.getElementById('deviceBrand').value,
        device_model: document.getElementById('deviceModel').value,
        serial_number: document.getElementById('serialNumber').value,
        issue: document.getElementById('issue').value.trim(),
        estimated_cost: parseFloat(document.getElementById('estimatedCost').value) || 0,
        estimated_days: parseInt(document.getElementById('estimatedDays').value) || 3,
        notes: document.getElementById('notes').value
    };
    
    if (!data.customer_name || !data.device_type || !data.issue) {
        showToast('Please fill in required fields', 'warning');
        return;
    }
    
    const result = await apiCall('createRepair', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('repairModal')).hide();
        clearForm();
        await loadRepairs();
    } else {
        showToast(result.message || 'Failed to create repair', 'error');
    }
}

async function updateRepairStatus() {
    const repairId = document.getElementById('statusRepairId').value;
    const status = document.getElementById('newStatus').value;
    const notes = document.getElementById('statusNotes').value;
    
    if (status === 'completed') {
        const actualCost = parseFloat(document.getElementById('actualCost').value) || 0;
        if (actualCost <= 0) {
            showToast('Please enter actual cost for completed repair', 'warning');
            return;
        }
        
        const completeData = {
            repair_id: parseInt(repairId),
            actual_cost: actualCost,
            notes: notes
        };
        
        const result = await apiCall('completeRepair', 'PUT', completeData);
        if (result.success) {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            await loadRepairs();
        } else {
            showToast(result.message || 'Failed to complete repair', 'error');
        }
    } else {
        const data = {
            repair_id: parseInt(repairId),
            status: status,
            notes: notes
        };
        
        const result = await apiCall('updateRepairStatus', 'POST', data);
        if (result.success) {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            await loadRepairs();
        } else {
            showToast(result.message || 'Failed to update status', 'error');
        }
    }
}

async function deleteRepair(repairId) {
    if (!confirm('Are you sure you want to delete this repair request?')) return;
    
    const result = await apiCall('deleteRepair', 'DELETE', { repair_id: repairId });
    if (result.success) {
        showToast(result.message, 'success');
        await loadRepairs();
    } else {
        showToast(result.message || 'Failed to delete repair', 'error');
    }
}

// ============================================
// FILTER AND DISPLAY
// ============================================

function filterRepairs() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    
    filteredRepairs = allRepairs.filter(repair => {
        const matchesSearch = searchTerm === '' || 
            (repair.CustomerName && repair.CustomerName.toLowerCase().includes(searchTerm)) ||
            (repair.RepairNo && repair.RepairNo.toLowerCase().includes(searchTerm)) ||
            (repair.DeviceType && repair.DeviceType.toLowerCase().includes(searchTerm));
        
        const matchesStatus = statusFilter === 'all' || repair.Status === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    currentPage = 1;
    displayRepairsTable();
}

function displayRepairsTable() {
    const tbody = document.getElementById('repairsTableBody');
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filteredRepairs.slice(start, start + itemsPerPage);
    
    if (paginated.length === 0) {
        tbody.innerHTML = '<td><td colspan="8" class="text-center text-muted">No repair records found<\/td><\/tr>';
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    
    tbody.innerHTML = paginated.map(repair => {
        let statusClass = '';
        let statusText = '';
        
        switch(repair.Status) {
            case 'pending': statusClass = 'badge-pending'; statusText = 'PENDING'; break;
            case 'in_progress': statusClass = 'badge-in_progress'; statusText = 'IN PROGRESS'; break;
            case 'completed': statusClass = 'badge-completed'; statusText = 'COMPLETED'; break;
            case 'for_pickup': statusClass = 'badge-for_pickup'; statusText = 'FOR PICKUP'; break;
            case 'cancelled': statusClass = 'badge-cancelled'; statusText = 'CANCELLED'; break;
            default: statusClass = 'badge-pending'; statusText = 'PENDING';
        }
        
        return `<tr>
            <td><strong>${repair.RepairNo || 'N/A'}</strong><\/td>
            <td><small>${repair.CreatedAt || ''}<\/small><\/td>
            <td>${escapeHtml(repair.CustomerName)}<\/td>
            <td>${repair.DeviceBrand ? repair.DeviceBrand + ' ' : ''}${repair.DeviceType || ''}<\/td>
            <td>${repair.Issue ? repair.Issue.substring(0, 50) + (repair.Issue.length > 50 ? '...' : '') : '-'}<\/td>
            <td>₱${formatNumber(repair.EstimatedCost)}<\/td>
            <td><span class="${statusClass}">${statusText}<\/span><\/td>
            <td>
                <button class="btn-action btn-view" onclick="viewRepair(${repair.RepairID})"><i class="fas fa-eye"></i><\/button>
                <button class="btn-action btn-edit" onclick="openStatusModal(${repair.RepairID}, '${repair.Status}')"><i class="fas fa-exchange-alt"></i><\/button>
                <button class="btn-action btn-delete" onclick="deleteRepair(${repair.RepairID})"><i class="fas fa-trash"><\/i><\/button>
            <\/td>
        <\/tr>`;
    }).join('');
    
    const totalPages = Math.ceil(filteredRepairs.length / itemsPerPage);
    let paginationHTML = '<div class="d-flex justify-content-center gap-1">';
    for (let i = 1; i <= totalPages; i++) {
        paginationHTML += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-secondary'}" onclick="goToPage(${i})">${i}</button>`;
    }
    paginationHTML += '</div>';
    document.getElementById('pagination').innerHTML = paginationHTML;
}

function goToPage(page) {
    currentPage = page;
    displayRepairsTable();
}

// ============================================
// MODAL FUNCTIONS
// ============================================

function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> New Repair Request';
    document.getElementById('saveRepairBtn').innerHTML = 'Save Repair Request';
    clearForm();
    const modal = new bootstrap.Modal(document.getElementById('repairModal'));
    modal.show();
}

function openStatusModal(repairId, currentStatus) {
    document.getElementById('statusRepairId').value = repairId;
    document.getElementById('newStatus').value = currentStatus;
    document.getElementById('statusNotes').value = '';
    document.getElementById('actualCostDiv').style.display = currentStatus === 'completed' ? 'block' : 'none';
    document.getElementById('actualCost').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

async function viewRepair(repairId) {
    const result = await apiCall(`getRepairById&id=${repairId}`);
    if (result.success && result.data) {
        displayRepairDetails(result.data, result.notes);
    }
}

function displayRepairDetails(repair, notes) {
    let statusClass = '';
    switch(repair.Status) {
        case 'pending': statusClass = 'badge-pending'; break;
        case 'in_progress': statusClass = 'badge-in_progress'; break;
        case 'completed': statusClass = 'badge-completed'; break;
        case 'for_pickup': statusClass = 'badge-for_pickup'; break;
        case 'cancelled': statusClass = 'badge-cancelled'; break;
        default: statusClass = 'badge-pending';
    }
    
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card p-3 mb-2">
                    <h6><i class="fas fa-user"></i> Customer Information</h6>
                    <p><strong>Name:</strong> ${escapeHtml(repair.CustomerName)}<br>
                    <strong>Phone:</strong> ${repair.CustomerPhone || '-'}<br>
                    <strong>Email:</strong> ${repair.CustomerEmail || '-'}<br>
                    <strong>Address:</strong> ${repair.CustomerAddress || '-'}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3 mb-2">
                    <h6><i class="fas fa-mobile-alt"></i> Device Information</h6>
                    <p><strong>Type:</strong> ${repair.DeviceType || '-'}<br>
                    <strong>Brand:</strong> ${repair.DeviceBrand || '-'}<br>
                    <strong>Model:</strong> ${repair.DeviceModel || '-'}<br>
                    <strong>Serial/IMEI:</strong> ${repair.SerialNumber || '-'}</p>
                </div>
            </div>
        </div>
        <div class="card p-3 mb-3">
            <h6><i class="fas fa-clipboard-list"></i> Repair Details</h6>
            <p><strong>Repair #:</strong> ${repair.RepairNo}<br>
            <strong>Date Received:</strong> ${repair.CreatedAt}<br>
            <strong>Issue:</strong> ${escapeHtml(repair.Issue)}<br>
            <strong>Estimated Cost:</strong> ₱${formatNumber(repair.EstimatedCost)}<br>
            <strong>Actual Cost:</strong> ₱${formatNumber(repair.ActualCost)}<br>
            <strong>Estimated Completion:</strong> ${repair.EstimatedCompletionDate || '-'}<br>
            <strong>Actual Completion:</strong> ${repair.ActualCompletionDate || '-'}<br>
            <strong>Technician:</strong> ${repair.Technician || '-'}<br>
            <strong>Status:</strong> <span class="${statusClass}">${repair.Status?.toUpperCase()}</span></p>
            <p><strong>Notes:</strong><br>${escapeHtml(repair.Notes) || '-'}</p>
        </div>
    `;
    
    if (notes && notes.length > 0) {
        html += `<div class="card p-3">
            <h6><i class="fas fa-history"></i> Activity Log</h6>
            <div class="timeline">`;
        notes.forEach(note => {
            html += `<div class="mb-2 pb-2 border-bottom">
                <small class="text-muted">${note.CreatedAt}</small>
                <p class="mb-0">${escapeHtml(note.Note)}</p>
                <small>By: ${note.CreatedBy}</small>
            </div>`;
        });
        html += `</div></div>`;
    }
    
    document.getElementById('viewModalBody').innerHTML = html;
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();
}

function printRepair() {
    const content = document.getElementById('viewModalBody').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Repair Details</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; }
                h6 { color: #2563eb; margin-bottom: 10px; }
                .badge-pending { background: #fef3c7; color: #d97706; padding: 3px 8px; border-radius: 12px; }
                .badge-in_progress { background: #dbeafe; color: #2563eb; padding: 3px 8px; border-radius: 12px; }
                .badge-completed { background: #dcfce7; color: #10b981; padding: 3px 8px; border-radius: 12px; }
            </style>
        </head>
        <body>
            <h2 style="text-align:center;">Repair Request Details</h2>
            ${content}
            <p style="text-align:center; margin-top:30px;">Generated on ${new Date().toLocaleString()}</p>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function clearForm() {
    document.getElementById('repairId').value = '';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerEmail').value = '';
    document.getElementById('customerAddress').value = '';
    document.getElementById('deviceType').value = '';
    document.getElementById('deviceBrand').value = '';
    document.getElementById('deviceModel').value = '';
    document.getElementById('serialNumber').value = '';
    document.getElementById('issue').value = '';
    document.getElementById('estimatedCost').value = '';
    document.getElementById('estimatedDays').value = '3';
    document.getElementById('technician').value = '';
    document.getElementById('notes').value = '';
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatNumber(value) {
    if (value === null || value === undefined || isNaN(value)) return '0.00';
    return parseFloat(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

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

document.getElementById('searchInput').addEventListener('input', filterRepairs);
document.getElementById('saveRepairBtn').addEventListener('click', createRepair);
document.getElementById('updateStatusBtn').addEventListener('click', updateRepairStatus);
document.getElementById('newStatus').addEventListener('change', function() {
    document.getElementById('actualCostDiv').style.display = this.value === 'completed' ? 'block' : 'none';
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadRepairs();
});
</script>
</body>
</html>