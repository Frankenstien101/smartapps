<?php
// pages/stock_transfer.php - Stock Transfer Management Page (FIXED)
?>
<style>
    .transfer-container {
        padding: 0;
        width: 100%;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    
    .form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .card-header {
        background: white;
        border-bottom: 1px solid #eef2f7;
        padding: 15px 20px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .search-box {
        position: relative;
        margin-bottom: 15px;
    }
    
    .search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
    }
    
    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .product-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    
    .product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
        transition: background 0.2s;
        gap: 10px;
    }
    
    .product-item:hover {
        background: #eef2ff;
    }
    
    .product-item.selected {
        background: linear-gradient(135deg, #eef2ff, #e6edff);
        border-left: 3px solid #4f9eff;
    }
    
    .product-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .product-thumb i {
        font-size: 18px;
        color: #94a3b8;
    }
    
    .product-info {
        flex: 1;
    }
    
    .product-name {
        font-weight: 600;
        font-size: 13px;
    }
    
    .product-code {
        font-size: 10px;
        color: #6c7a91;
    }
    
    .product-stock {
        font-size: 10px;
    }
    
    .product-price {
        font-size: 12px;
        font-weight: 600;
        color: #4f9eff;
        text-align: right;
    }
    
    .selected-items-list {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-top: 10px;
    }
    
    .selected-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        gap: 10px;
    }
    
    .selected-item:last-child {
        border-bottom: none;
    }
    
    .selected-item-info {
        flex: 1;
    }
    
    .selected-item-name {
        font-weight: 600;
        font-size: 13px;
    }
    
    .selected-item-qty {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .qty-input {
        width: 60px;
        padding: 4px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        text-align: center;
    }
    
    .remove-item {
        color: #dc3545;
        cursor: pointer;
    }
    
    .transfer-table-container {
        overflow-x: auto;
    }
    
    .transfer-table {
        width: 100%;
        font-size: 12px;
        border-collapse: collapse;
    }
    
    .transfer-table th {
        background: #f8fafc;
        padding: 10px;
        font-size: 11px;
        text-align: left;
    }
    
    .transfer-table td {
        padding: 10px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .badge-pending { background: #fef3c7; color: #d97706; padding: 3px 8px; border-radius: 20px; font-size: 10px; display: inline-block; }
    .badge-approved { background: #dbeafe; color: #2563eb; padding: 3px 8px; border-radius: 20px; font-size: 10px; display: inline-block; }
    .badge-completed { background: #dcfce7; color: #10b981; padding: 3px 8px; border-radius: 20px; font-size: 10px; display: inline-block; }
    .badge-rejected { background: #fee2e2; color: #dc3545; padding: 3px 8px; border-radius: 20px; font-size: 10px; display: inline-block; }
    .badge-cancelled { background: #e2e8f0; color: #6c7a91; padding: 3px 8px; border-radius: 20px; font-size: 10px; display: inline-block; }
    
    .action-btn {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        border: none;
        cursor: pointer;
        margin: 2px;
    }
    
    .btn-approve { background: #10b981; color: white; }
    .btn-reject { background: #ef4444; color: white; }
    .btn-receive { background: #3b82f6; color: white; }
    .btn-view { background: #6c757d; color: white; }
    .btn-cancel { background: #f59e0b; color: white; }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #e2e8f0;
        border-radius: 50%;
        border-top-color: #4f9eff;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
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
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .pagination button {
        padding: 5px 10px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .pagination button.active {
        background: #4f9eff;
        color: white;
        border-color: #4f9eff;
    }
    
    .toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1100;
    }
    
    .toast {
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        border-left: 4px solid;
        min-width: 250px;
        margin-bottom: 10px;
    }
    
    .toast.success { border-left-color: #28a745; }
    .toast.error { border-left-color: #dc3545; }
    .toast.warning { border-left-color: #ffc107; }
    
    .toast-header {
        padding: 8px 12px;
        border-bottom: 1px solid #eef2f7;
    }
    
    .toast-body {
        padding: 8px 12px;
    }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .selected-item {
            flex-wrap: wrap;
        }
    }
</style>

<div class="transfer-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-exchange-alt"></i> Stock Transfer Management</h4>
            <p class="text-muted mb-0">Transfer stock between branches with approval workflow</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalTransfers">0</div>
            <div class="stat-label">Total Transfers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-value" id="pendingCount">0</div>
            <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="completedCount">0</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-boxes"></i></div>
            <div class="stat-value" id="totalItems">0</div>
            <div class="stat-label">Items Transferred</div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column - Create Transfer -->
        <div class="col-lg-5 mb-4">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> New Stock Transfer
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Destination Branch *</label>
                        <select id="toBranch" class="form-select">
                            <option value="">Select Branch</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Search Products</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="productSearch" placeholder="Search by name, code, IMEI, or Serial...">
                        </div>
                        <div class="product-list" id="productList">
                            <div class="text-center py-4"><div class="loading-spinner"></div> Loading products...</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Items to Transfer</label>
                        <div id="selectedItemsContainer" style="display: none;">
                            <div class="selected-items-list" id="selectedItemsList"></div>
                        </div>
                        <div id="emptySelectedMsg" class="text-muted text-center py-3">No items selected</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea id="transferNotes" class="form-control" rows="2" placeholder="Reason for transfer..."></textarea>
                    </div>
                    
                    <button class="btn btn-primary w-100" id="createTransferBtn" onclick="createTransfer()" disabled>
                        <i class="fas fa-paper-plane"></i> Request Transfer
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Transfer History -->
        <div class="col-lg-7 mb-4">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Transfer History
                    <div class="d-flex gap-2">
                        <select id="statusFilter" class="form-select form-select-sm" style="width: 130px;" onchange="loadTransfers()">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="loadTransfers()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="transfer-table-container">
                        <table class="transfer-table">
                            <thead>
                                <tr>
                                    <th>Transfer No</th>
                                    <th>From → To</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="transfersTableBody">
                                <tr><td colspan="6" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination mt-3" id="pagination"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transfer Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <div class="text-center py-4"><div class="loading-spinner"></div> Loading...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="actionTransferId">
                <input type="hidden" id="actionType">
                <div class="mb-3" id="reasonField" style="display: none;">
                    <label class="form-label">Reason</label>
                    <textarea id="actionReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
                </div>
                <div id="actionMessage"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/stocktransferdata.php';

// Global variables
let allProducts = [];
let selectedItems = [];
let allTransfers = [];
let currentPage = 1;
let itemsPerPage = 10;

// ============================================
// API CALLS
// ============================================

async function apiCall(action, method = 'GET', data = null) {
    try {
        const options = { 
            method: method, 
            headers: { 'Content-Type': 'application/json' }
        };
        if (data) options.body = JSON.stringify(data);
        
        let url = `${API_URL}?action=${action}`;
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false, message: error.message };
    }
}

async function loadProducts() {
    const result = await apiCall('getProducts');
    if (result.success && result.data) {
        allProducts = result.data;
        displayProducts(allProducts);
    } else {
        document.getElementById('productList').innerHTML = '<div class="text-center py-4 text-muted">No products available</div>';
    }
}

async function loadBranches() {
    const result = await apiCall('getBranches');
    if (result.success && result.data) {
        const select = document.getElementById('toBranch');
        select.innerHTML = '<option value="">Select Branch</option>';
        result.data.forEach(branch => {
            select.innerHTML += `<option value="${branch}">${branch}</option>`;
        });
    }
}

async function loadTransfers() {
    const status = document.getElementById('statusFilter').value;
    const result = await apiCall(`getTransfers&status=${status}&limit=100`);
    if (result.success && result.data) {
        allTransfers = result.data;
        displayTransfers();
    }
}

async function loadStats() {
    const result = await apiCall('getTransferStats');
    if (result.success && result.data) {
        const stats = result.data;
        document.getElementById('totalTransfers').innerText = stats.TotalTransfers || 0;
        document.getElementById('pendingCount').innerText = stats.PendingCount || 0;
        document.getElementById('completedCount').innerText = stats.CompletedCount || 0;
        document.getElementById('totalItems').innerText = stats.TotalItemsTransferred || 0;
    }
}

async function createTransfer() {
    const toBranch = document.getElementById('toBranch').value;
    const notes = document.getElementById('transferNotes').value;
    
    if (!toBranch) {
        showToast('Please select destination branch', 'warning');
        return;
    }
    
    if (selectedItems.length === 0) {
        showToast('Please add items to transfer', 'warning');
        return;
    }
    
    const data = {
        to_branch: toBranch,
        notes: notes,
        items: selectedItems.map(item => ({
            product_id: item.id,
            product_name: item.name,
            product_code: item.code,
            quantity: item.quantity,
            imei: item.imei || '',
            serial: item.serial || ''
        }))
    };
    
    const result = await apiCall('createTransfer', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        resetForm();
        loadTransfers();
        loadStats();
    } else {
        showToast(result.message, 'error');
    }
}

async function approveTransfer(transferId) {
    const result = await apiCall('approveTransfer', 'POST', { transfer_id: transferId });
    if (result.success) {
        showToast(result.message, 'success');
        loadTransfers();
        loadStats();
        return true;
    } else {
        showToast(result.message, 'error');
        return false;
    }
}

async function rejectTransfer(transferId, reason) {
    const result = await apiCall('rejectTransfer', 'POST', { transfer_id: transferId, reason: reason });
    if (result.success) {
        showToast(result.message, 'success');
        loadTransfers();
        loadStats();
        return true;
    } else {
        showToast(result.message, 'error');
        return false;
    }
}

async function receiveTransfer(transferId) {
    const result = await apiCall('receiveTransfer', 'POST', { transfer_id: transferId });
    if (result.success) {
        showToast(result.message, 'success');
        loadTransfers();
        loadStats();
        loadProducts();
        return true;
    } else {
        showToast(result.message, 'error');
        return false;
    }
}

async function cancelTransfer(transferId, reason) {
    const result = await apiCall('cancelTransfer', 'POST', { transfer_id: transferId, reason: reason });
    if (result.success) {
        showToast(result.message, 'success');
        loadTransfers();
        loadStats();
        return true;
    } else {
        showToast(result.message, 'error');
        return false;
    }
}

async function viewTransferDetails(transferId) {
    const result = await apiCall(`getTransferById&id=${transferId}`);
    if (result.success && result.data) {
        showTransferDetails(result.data, result.items, result.history);
    } else {
        showToast('Failed to load transfer details', 'error');
    }
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displayProducts(products) {
    const container = document.getElementById('productList');
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    
    let filtered = products;
    if (searchTerm) {
        filtered = products.filter(p => 
            (p.ProductName && p.ProductName.toLowerCase().includes(searchTerm)) ||
            (p.ProductCode && p.ProductCode.toLowerCase().includes(searchTerm)) ||
            (p.IMEINumber && p.IMEINumber.toLowerCase().includes(searchTerm)) ||
            (p.SerialNumber && p.SerialNumber.toLowerCase().includes(searchTerm))
        );
    }
    
    if (filtered.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted">No products available</div>';
        return;
    }
    
    container.innerHTML = filtered.map(product => {
        const isSelected = selectedItems.some(s => s.id === product.ProductID);
        const imageHtml = product.ProductImagePath 
            ? `<img src="${product.ProductImagePath}" alt="${escapeHtml(product.ProductName)}">`
            : `<i class="fas fa-box"></i>`;
        
        return `
            <div class="product-item ${isSelected ? 'selected' : ''}" onclick="addToTransfer(${product.ProductID})">
                <div class="product-thumb">${imageHtml}</div>
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.ProductName)}</div>
                    <div class="product-code">${product.ProductCode || 'N/A'}</div>
                    <div class="product-stock">Stock: ${product.CurrentStock} units</div>
                </div>
                <div class="product-price">₱${formatNumber(product.SellingPrice)}</div>
            </div>
        `;
    }).join('');
}

function displaySelectedItems() {
    const container = document.getElementById('selectedItemsList');
    const containerDiv = document.getElementById('selectedItemsContainer');
    const emptyMsg = document.getElementById('emptySelectedMsg');
    const createBtn = document.getElementById('createTransferBtn');
    
    if (selectedItems.length === 0) {
        containerDiv.style.display = 'none';
        emptyMsg.style.display = 'block';
        createBtn.disabled = true;
        return;
    }
    
    containerDiv.style.display = 'block';
    emptyMsg.style.display = 'none';
    createBtn.disabled = false;
    
    container.innerHTML = selectedItems.map((item, index) => `
        <div class="selected-item">
            <div class="selected-item-info">
                <div class="selected-item-name">${escapeHtml(item.name)}</div>
                <div class="small text-muted">${item.code || 'N/A'} | Stock: ${item.stock}</div>
            </div>
            <div class="selected-item-qty">
                <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${item.stock}" onchange="updateItemQuantity(${index}, this.value)">
                <span>/ ${item.stock}</span>
                <div class="remove-item" onclick="removeFromTransfer(${index})">
                    <i class="fas fa-trash"></i>
                </div>
            </div>
        </div>
    `).join('');
}

function displayTransfers() {
    const tbody = document.getElementById('transfersTableBody');
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = allTransfers.slice(start, start + itemsPerPage);
    
    if (paginated.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No transfer records found<\/td><\/tr>';
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    
    tbody.innerHTML = paginated.map(transfer => {
        let statusClass = '';
        let statusText = '';
        
        switch(transfer.Status) {
            case 'pending': statusClass = 'badge-pending'; statusText = 'PENDING'; break;
            case 'approved': statusClass = 'badge-approved'; statusText = 'APPROVED'; break;
            case 'completed': statusClass = 'badge-completed'; statusText = 'COMPLETED'; break;
            case 'rejected': statusClass = 'badge-rejected'; statusText = 'REJECTED'; break;
            case 'cancelled': statusClass = 'badge-cancelled'; statusText = 'CANCELLED'; break;
            default: statusClass = 'badge-pending'; statusText = transfer.Status.toUpperCase();
        }
        
        let actionButtons = '';
        if (transfer.Status === 'pending') {
            actionButtons = `<button class="action-btn btn-approve" onclick="openActionModal(${transfer.TransferID}, 'approve')">Approve</button>
                             <button class="action-btn btn-reject" onclick="openActionModal(${transfer.TransferID}, 'reject')">Reject</button>`;
        } else if (transfer.Status === 'approved') {
            actionButtons = `<button class="action-btn btn-receive" onclick="openActionModal(${transfer.TransferID}, 'receive')">Receive</button>
                             <button class="action-btn btn-cancel" onclick="openActionModal(${transfer.TransferID}, 'cancel')">Cancel</button>`;
        }
        
        return `
            <tr>
                <td><strong>${transfer.TransferNo}</strong><\/td>
                <td>${transfer.FromBranch || '-'} → ${transfer.ToBranch || '-'}<\/td>
                <td>${transfer.ItemCount || 0} items (${transfer.TotalQuantity || 0} qty)<\/td>
                <td><span class="${statusClass}">${statusText}</span><\/td>
                <td>${transfer.RequestedAt || '-'}<\/td>
                <td>
                    <button class="action-btn btn-view" onclick="viewTransferDetails(${transfer.TransferID})">View</button>
                    ${actionButtons}
                <\/td>
            <\/tr>
        `;
    }).join('');
    
    const totalPages = Math.ceil(allTransfers.length / itemsPerPage);
    let paginationHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        paginationHTML += `<button class="${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }
    document.getElementById('pagination').innerHTML = paginationHTML;
}

function showTransferDetails(transfer, items, history) {
    const modalBody = document.getElementById('detailsModalBody');
    
    let statusClass = '';
    switch(transfer.Status) {
        case 'pending': statusClass = 'badge-pending'; break;
        case 'approved': statusClass = 'badge-approved'; break;
        case 'completed': statusClass = 'badge-completed'; break;
        case 'rejected': statusClass = 'badge-rejected'; break;
        default: statusClass = 'badge-pending';
    }
    
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Transfer No:</strong> ${transfer.TransferNo}<br>
                <strong>From Branch:</strong> ${transfer.FromBranch || '-'}<br>
                <strong>To Branch:</strong> ${transfer.ToBranch || '-'}<br>
                <strong>Status:</strong> <span class="${statusClass}">${transfer.Status.toUpperCase()}</span>
            </div>
            <div class="col-md-6">
                <strong>Requested By:</strong> ${transfer.RequestedBy || '-'}<br>
                <strong>Requested At:</strong> ${transfer.RequestedAt || '-'}<br>
                ${transfer.ApprovedBy ? `<strong>Approved By:</strong> ${transfer.ApprovedBy}<br>` : ''}
                ${transfer.ApprovedAt ? `<strong>Approved At:</strong> ${transfer.ApprovedAt}<br>` : ''}
                ${transfer.ReceivedBy ? `<strong>Received By:</strong> ${transfer.ReceivedBy}<br>` : ''}
                ${transfer.ReceivedAt ? `<strong>Received At:</strong> ${transfer.ReceivedAt}<br>` : ''}
            </div>
        </div>
    `;
    
    if (transfer.Notes) {
        html += `<div class="alert alert-info"><strong>Notes:</strong> ${escapeHtml(transfer.Notes)}</div>`;
    }
    if (transfer.RejectionReason) {
        html += `<div class="alert alert-danger"><strong>Rejection Reason:</strong> ${escapeHtml(transfer.RejectionReason)}</div>`;
    }
    
    html += `<h6>Items</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Product</th><th>Code</th><th>Quantity</th><th>IMEI</th><th>Serial</th></tr></thead>
                <tbody>`;
    
    if (items && items.length > 0) {
        items.forEach(item => {
            html += `<tr>
                <td>${escapeHtml(item.ProductName)}</td>
                <td>${item.ProductCode || '-'}</td>
                <td>${item.Quantity}</td>
                <td>${item.IMEINumber || '-'}</td>
                <td>${item.SerialNumber || '-'}</td>
            </tr>`;
        });
    }
    
    html += `</tbody></table></div>`;
    
    if (history && history.length > 0) {
        html += `<h6 class="mt-3">Activity History</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Action</th><th>By</th><th>Date</th><th>Notes</th></tr></thead>
                <tbody>`;
        
        history.forEach(h => {
            html += `<tr>
                <td><span class="badge bg-secondary">${h.Action}</span></td>
                <td>${h.ActionBy || '-'}</td>
                <td>${h.ActionAt || '-'}</td>
                <td>${h.Notes || '-'}</td>
            </tr>`;
        });
        
        html += `</tbody></table></div>`;
    }
    
    modalBody.innerHTML = html;
    
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

// ============================================
// TRANSFER ITEM FUNCTIONS
// ============================================

function addToTransfer(productId) {
    const product = allProducts.find(p => p.ProductID == productId);
    if (!product) return;
    
    const existingIndex = selectedItems.findIndex(i => i.id === productId);
    
    if (existingIndex !== -1) {
        selectedItems.splice(existingIndex, 1);
        showToast(`${product.ProductName} removed from transfer`, 'info');
    } else {
        selectedItems.push({
            id: productId,
            name: product.ProductName,
            code: product.ProductCode,
            quantity: 1,
            stock: product.CurrentStock,
            imei: product.IMEINumber,
            serial: product.SerialNumber
        });
        showToast(`${product.ProductName} added to transfer`, 'success');
    }
    
    displayProducts(allProducts);
    displaySelectedItems();
}

function updateItemQuantity(index, newQuantity) {
    const qty = parseInt(newQuantity);
    if (isNaN(qty) || qty < 1) {
        selectedItems[index].quantity = 1;
    } else if (qty > selectedItems[index].stock) {
        showToast(`Only ${selectedItems[index].stock} units available`, 'warning');
        selectedItems[index].quantity = selectedItems[index].stock;
    } else {
        selectedItems[index].quantity = qty;
    }
    
    displaySelectedItems();
}

function removeFromTransfer(index) {
    const removedItem = selectedItems[index];
    selectedItems.splice(index, 1);
    showToast(`${removedItem.name} removed from transfer`, 'info');
    displayProducts(allProducts);
    displaySelectedItems();
}

// ============================================
// ACTION MODAL FUNCTIONS
// ============================================

let currentActionTransferId = null;
let currentActionType = null;

function openActionModal(transferId, actionType) {
    currentActionTransferId = transferId;
    currentActionType = actionType;
    
    const title = document.getElementById('actionModalTitle');
    const reasonField = document.getElementById('reasonField');
    const actionMessage = document.getElementById('actionMessage');
    const confirmBtn = document.getElementById('confirmActionBtn');
    
    let titleText = '';
    let messageText = '';
    
    switch(actionType) {
        case 'approve':
            titleText = 'Approve Transfer';
            messageText = 'Are you sure you want to approve this transfer?';
            reasonField.style.display = 'none';
            confirmBtn.className = 'btn btn-success';
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Approve';
            break;
        case 'reject':
            titleText = 'Reject Transfer';
            messageText = 'Please provide a reason for rejecting this transfer:';
            reasonField.style.display = 'block';
            confirmBtn.className = 'btn btn-danger';
            confirmBtn.innerHTML = '<i class="fas fa-times"></i> Reject';
            break;
        case 'receive':
            titleText = 'Receive Transfer';
            messageText = 'Are you sure you want to receive this transfer? The inventory will be updated.';
            reasonField.style.display = 'none';
            confirmBtn.className = 'btn btn-primary';
            confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Receive';
            break;
        case 'cancel':
            titleText = 'Cancel Transfer';
            messageText = 'Please provide a reason for cancelling this transfer:';
            reasonField.style.display = 'block';
            confirmBtn.className = 'btn btn-warning';
            confirmBtn.innerHTML = '<i class="fas fa-ban"></i> Cancel';
            break;
        default:
            return;
    }
    
    title.innerText = titleText;
    actionMessage.innerHTML = `<div class="alert alert-info">${messageText}</div>`;
    document.getElementById('actionReason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    modal.show();
}

document.getElementById('confirmActionBtn').addEventListener('click', async function() {
    const transferId = currentActionTransferId;
    const actionType = currentActionType;
    const reason = document.getElementById('actionReason').value;
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('actionModal'));
    
    let success = false;
    
    switch(actionType) {
        case 'approve':
            if (confirm('Confirm approval?')) {
                success = await approveTransfer(transferId);
            }
            break;
        case 'reject':
            if (!reason) {
                showToast('Please provide a rejection reason', 'warning');
                return;
            }
            if (confirm('Confirm rejection?')) {
                success = await rejectTransfer(transferId, reason);
            }
            break;
        case 'receive':
            if (confirm('Confirm receiving this transfer? This will update inventory.')) {
                success = await receiveTransfer(transferId);
            }
            break;
        case 'cancel':
            if (!reason) {
                showToast('Please provide a cancellation reason', 'warning');
                return;
            }
            if (confirm('Confirm cancellation?')) {
                success = await cancelTransfer(transferId, reason);
            }
            break;
    }
    
    if (success) {
        modal.hide();
    }
});

// ============================================
// HELPER FUNCTIONS
// ============================================

function goToPage(page) {
    currentPage = page;
    displayTransfers();
}

function resetForm() {
    selectedItems = [];
    document.getElementById('toBranch').value = '';
    document.getElementById('transferNotes').value = '';
    document.getElementById('productSearch').value = '';
    displaySelectedItems();
    displayProducts(allProducts);
}

function formatNumber(value) {
    if (!value && value !== 0) return '0.00';
    return parseFloat(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
    toast.innerHTML = `
        <div class="toast-header">
            <strong class="me-auto">${type.toUpperCase()}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000, autohide: true });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// ============================================
// EVENT LISTENERS
// ============================================

document.getElementById('productSearch').addEventListener('input', function() {
    displayProducts(allProducts);
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    loadBranches();
    loadTransfers();
    loadStats();
});
</script>