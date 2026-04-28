<?php
// pages/returns-warranty.php - Returns and Warranty Management
?>
<style>
    .returns-container {
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
    
    /* Tabs */
    .tabs-container {
        background: white;
        border-radius: 16px;
        margin-bottom: 20px;
        padding: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
    }
    
    .tab-btn {
        flex: 1;
        padding: 12px;
        border: none;
        background: transparent;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .tab-btn.active {
        background: #4f9eff;
        color: white;
    }
    
    /* Search Section */
    .search-section {
        background: white;
        border-radius: 16px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .search-box {
        position: relative;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    /* Transactions Table */
    .transactions-table-container {
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
    
    .badge-cash { background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-installment { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-returned { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-warranty { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-pending { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-approved { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-rejected { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
    .btn-action {
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 11px;
        margin: 2px;
        cursor: pointer;
        border: none;
    }
    
    .btn-return { background: #f59e0b; color: white; }
    .btn-warranty { background: #10b981; color: white; }
    .btn-view { background: #4f9eff; color: white; }
    
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
    
    .return-summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
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
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
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
        .tabs-container {
            flex-wrap: wrap;
        }
        .tab-btn {
            font-size: 12px;
            padding: 8px;
        }
    }
</style>

<div class="returns-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-undo-alt"></i> Returns & Warranty</h4>
            <p class="text-muted mb-0">Manage product returns and warranty claims</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-exchange-alt"></i></div>
            <div class="stat-value" id="totalReturns">0</div>
            <div class="stat-label">Total Returns</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-shield-alt"></i></div>
            <div class="stat-value" id="activeWarranty">0</div>
            <div class="stat-label">Active Warranty</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-clock"></i></div>
            <div class="stat-value" id="pendingReturns">0</div>
            <div class="stat-label">Pending Returns</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalRefund">₱0</div>
            <div class="stat-label">Total Refunded</div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs-container">
        <button class="tab-btn active" onclick="switchTab('sales')">Cash Sales</button>
        <button class="tab-btn" onclick="switchTab('installment')">Installment Sales</button>
        <button class="tab-btn" onclick="switchTab('returns')">Returns List</button>
        <button class="tab-btn" onclick="switchTab('warranty')">Warranty Claims</button>
    </div>
    
    <!-- Search Section -->
    <div class="search-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by receipt number, customer name, or product...">
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="transactions-table-container">
        <div class="table-header" id="tableHeader">Cash Sales Transactions</div>
        <div class="table-responsive">
            <table class="table">
                <thead id="tableHead">
                    <tr>
                        <th>Receipt No.</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Payment Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr><td colspan="8" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-undo-alt"></i> Process Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="returnTransactionId">
                <input type="hidden" id="returnTransactionType">
                <div class="return-summary" id="returnSummary"></div>
                <div class="mb-3">
                    <label class="form-label">Reason for Return *</label>
                    <select id="returnReason" class="form-select">
                        <option value="">Select Reason</option>
                        <option value="Defective Product">Defective Product</option>
                        <option value="Wrong Item Delivered">Wrong Item Delivered</option>
                        <option value="Damaged on Arrival">Damaged on Arrival</option>
                        <option value="Not as Described">Not as Described</option>
                        <option value="Customer Changed Mind">Customer Changed Mind</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Refund Amount</label>
                    <input type="text" id="refundAmount" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="returnNotes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning" id="confirmReturnBtn">Process Return</button>
            </div>
        </div>
    </div>
</div>

<!-- Warranty Modal -->
<div class="modal fade" id="warrantyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shield-alt"></i> Warranty Claim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="warrantyTransactionId">
                <input type="hidden" id="warrantyTransactionType">
                <div class="return-summary" id="warrantySummary"></div>
                <div class="mb-3">
                    <label class="form-label">Issue Description *</label>
                    <textarea id="warrantyIssue" class="form-control" rows="3" placeholder="Describe the issue with the product..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Claim Type</label>
                    <select id="warrantyType" class="form-select">
                        <option value="Repair">Repair</option>
                        <option value="Replacement">Replacement</option>
                        <option value="Refund">Refund</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="warrantyNotes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" id="confirmWarrantyBtn">Submit Claim</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/stockindata.php';
const INSTALLMENT_API_URL = '/POS/datafetcher/installmentdata.php';

let currentTab = 'sales';
let allSales = [];
let allInstallments = [];
let filteredData = [];

// ============================================
// API CALLS
// ============================================

async function apiCall(url, action, method = 'GET', data = null) {
    try {
        const options = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (data) options.body = JSON.stringify(data);
        const response = await fetch(`${url}?action=${action}`, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false };
    }
}

async function loadSales() {
    const result = await apiCall(API_URL, 'getSales');
    if (result.success && result.data) {
        allSales = result.data;
        filterAndDisplay();
    }
}

async function loadInstallments() {
    const result = await apiCall(INSTALLMENT_API_URL, 'getInstallments');
    if (result.success && result.data) {
        allInstallments = result.data;
        filterAndDisplay();
    }
}

// FIXED: Correct function names for return and warranty
async function processCashReturn(transactionId, data) {
    const result = await apiCall(API_URL, 'processReturn', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        await loadSales();
        return true;
    } else {
        showToast(result.message || 'Failed to process return', 'error');
        return false;
    }
}

async function processInstallmentReturn(transactionId, data) {
    const result = await apiCall(INSTALLMENT_API_URL, 'processInstallmentReturn', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        await loadInstallments();
        return true;
    } else {
        showToast(result.message || 'Failed to process return', 'error');
        return false;
    }
}

async function submitCashWarranty(transactionId, data) {
    const result = await apiCall(API_URL, 'submitWarranty', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        return true;
    } else {
        showToast(result.message || 'Failed to submit warranty', 'error');
        return false;
    }
}

async function submitInstallmentWarranty(transactionId, data) {
    const result = await apiCall(INSTALLMENT_API_URL, 'submitInstallmentWarranty', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        return true;
    } else {
        showToast(result.message || 'Failed to submit warranty', 'error');
        return false;
    }
}

// ============================================
// FILTER AND DISPLAY
// ============================================

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    const headers = {
        'sales': 'Cash Sales Transactions',
        'installment': 'Installment Sales Transactions',
        'returns': 'Return Requests',
        'warranty': 'Warranty Claims'
    };
    document.getElementById('tableHeader').innerText = headers[tab];
    
    filterAndDisplay();
}

function filterAndDisplay() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    if (currentTab === 'sales') {
        filteredData = allSales.filter(sale => {
            const matchSearch = searchTerm === '' || 
                (sale.ReceiptNo && sale.ReceiptNo.toLowerCase().includes(searchTerm)) ||
                (sale.CustomerName && sale.CustomerName.toLowerCase().includes(searchTerm));
            return matchSearch;
        });
        displaySalesTable();
    } else if (currentTab === 'installment') {
        filteredData = allInstallments.filter(inst => {
            const matchSearch = searchTerm === '' || 
                (inst.InstallmentNo && inst.InstallmentNo.toLowerCase().includes(searchTerm)) ||
                (inst.CustomerName && inst.CustomerName.toLowerCase().includes(searchTerm));
            return matchSearch;
        });
        displayInstallmentTable();
    } else if (currentTab === 'returns') {
        displayReturnsList();
    } else if (currentTab === 'warranty') {
        displayWarrantyList();
    }
    
    updateStats();
}

function displaySalesTable() {
    const tbody = document.getElementById('tableBody');
    
    if (filteredData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center empty-state"><i class="fas fa-inbox"></i><p>No transactions found</p><\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = filteredData.map(sale => {
        const isReturned = sale.Status === 'returned';
        const statusClass = isReturned ? 'badge-returned' : 'badge-approved';
        const statusText = isReturned ? 'RETURNED' : (sale.Status === 'completed' ? 'COMPLETED' : sale.Status?.toUpperCase() || 'COMPLETED');
        
        return `
            <tr>
                <td><strong>${sale.ReceiptNo || 'N/A'}</strong><\/td>
                <td><small>${sale.SaleDate || ''}<\/small><\/td>
                <td>${escapeHtml(sale.CustomerName || 'Walk-in')}<\/td>
                <td>${sale.ProductName || 'Multiple Items'}<\/td>
                <td>₱${formatNumber(sale.TotalAmount)}<\/td>
                <td><span class="badge-cash">CASH</span><\/td>
                <td><span class="${statusClass}">${statusText}<\/span><\/td>
                <td>
                    ${!isReturned ? `<button class="btn-action btn-return" onclick="openReturnModal('sales', ${sale.SaleID}, '${sale.ReceiptNo}', '${escapeHtml(sale.CustomerName)}', ${sale.TotalAmount})">Return</button>` : ''}
                    <button class="btn-action btn-warranty" onclick="openWarrantyModal('sales', ${sale.SaleID}, '${sale.ReceiptNo}', '${escapeHtml(sale.CustomerName)}')">Warranty</button>
                    <button class="btn-action btn-view" onclick="viewTransactionDetails('sales', ${sale.SaleID})">View</button>
                <\/td>
            <\/tr>
        `;
    }).join('');
}

function displayInstallmentTable() {
    const tbody = document.getElementById('tableBody');
    
    if (filteredData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center empty-state"><i class="fas fa-inbox"></i><p>No transactions found</p><\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = filteredData.map(inst => {
        const isReturned = inst.Status === 'returned';
        const statusClass = isReturned ? 'badge-returned' : (inst.Status === 'completed' ? 'badge-approved' : 'badge-pending');
        const statusText = isReturned ? 'RETURNED' : (inst.Status === 'completed' ? 'PAID' : inst.Status?.toUpperCase() || 'ACTIVE');
        
        return `
            <tr>
                <td><strong>${inst.InstallmentNo || 'N/A'}</strong><\/td>
                <td><small>${inst.StartDate || ''}<\/small><\/td>
                <td>${escapeHtml(inst.CustomerName)}<\/td>
                <td>${inst.ProductName || 'Multiple Items'}<\/td>
                <td>₱${formatNumber(inst.TotalAmount)}<\/td>
                <td><span class="badge-installment">INSTALLMENT</span><\/td>
                <td><span class="${statusClass}">${statusText}<\/span><\/td>
                <td>
                    ${!isReturned ? `<button class="btn-action btn-return" onclick="openReturnModal('installment', ${inst.InstallmentID}, '${inst.InstallmentNo}', '${escapeHtml(inst.CustomerName)}', ${inst.TotalAmount})">Return</button>` : ''}
                    <button class="btn-action btn-warranty" onclick="openWarrantyModal('installment', ${inst.InstallmentID}, '${inst.InstallmentNo}', '${escapeHtml(inst.CustomerName)}')">Warranty</button>
                    <button class="btn-action btn-view" onclick="viewInstallmentDetails(${inst.InstallmentID})">View</button>
                <\/td>
            <\/tr>
        `;
    }).join('');
}

function displayReturnsList() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = `
        <tr><td colspan="8" class="text-center empty-state">
            <i class="fas fa-exchange-alt"></i>
            <p>Return requests will appear here</p>
        <\/td><\/tr>
    `;
}

function displayWarrantyList() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = `
        <tr><td colspan="8" class="text-center empty-state">
            <i class="fas fa-shield-alt"></i>
            <p>Warranty claims will appear here after submission</p>
        <\/td><\/tr>
    `;
}

// ============================================
// STATS CALCULATION
// ============================================

function updateStats() {
    const totalReturns = allSales.filter(s => s.Status === 'returned').length + 
                        allInstallments.filter(i => i.Status === 'returned').length;
    
    const pendingReturns = 0;
    
    let totalRefund = 0;
    allSales.filter(s => s.Status === 'returned').forEach(s => totalRefund += parseFloat(s.TotalAmount) || 0);
    allInstallments.filter(i => i.Status === 'returned').forEach(i => totalRefund += parseFloat(i.TotalAmount) || 0);
    
    document.getElementById('totalReturns').innerText = totalReturns;
    document.getElementById('activeWarranty').innerText = 0;
    document.getElementById('pendingReturns').innerText = pendingReturns;
    document.getElementById('totalRefund').innerHTML = '₱' + formatNumber(totalRefund);
}

// ============================================
// MODAL FUNCTIONS
// ============================================

function openReturnModal(type, id, receiptNo, customerName, amount) {
    document.getElementById('returnTransactionId').value = id;
    document.getElementById('returnTransactionType').value = type;
    document.getElementById('returnSummary').innerHTML = `
        <div><strong>Receipt:</strong> ${receiptNo}</div>
        <div><strong>Customer:</strong> ${customerName}</div>
        <div><strong>Amount:</strong> ₱${formatNumber(amount)}</div>
    `;
    document.getElementById('refundAmount').value = '₱' + formatNumber(amount);
    document.getElementById('returnReason').value = '';
    document.getElementById('returnNotes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('returnModal'));
    modal.show();
}

function openWarrantyModal(type, id, receiptNo, customerName) {
    document.getElementById('warrantyTransactionId').value = id;
    document.getElementById('warrantyTransactionType').value = type;
    document.getElementById('warrantySummary').innerHTML = `
        <div><strong>Receipt:</strong> ${receiptNo}</div>
        <div><strong>Customer:</strong> ${customerName}</div>
    `;
    document.getElementById('warrantyIssue').value = '';
    document.getElementById('warrantyType').value = 'Repair';
    document.getElementById('warrantyNotes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('warrantyModal'));
    modal.show();
}

async function viewTransactionDetails(type, id) {
    if (type === 'sales') {
        const result = await apiCall(API_URL, `getSaleById&id=${id}`);
        if (result.success && result.data) {
            showTransactionDetails(result.data);
        }
    }
}

function showTransactionDetails(data) {
    const sale = data.sale;
    const items = data.items || [];
    
    let html = `
        <div class="modal fade" id="detailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Transaction Details - ${sale.ReceiptNo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Receipt:</strong> ${sale.ReceiptNo}<br>
                                <strong>Customer:</strong> ${escapeHtml(sale.CustomerName)}<br>
                                <strong>Date:</strong> ${sale.SaleDate}
                            </div>
                            <div class="col-md-6">
                                <strong>Total Amount:</strong> ₱${formatNumber(sale.TotalAmount)}<br>
                                <strong>Payment Method:</strong> ${sale.PaymentMethod?.toUpperCase()}<br>
                                <strong>Status:</strong> ${sale.Status || 'Completed'}
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>
                                <tbody>
    `;
    
    items.forEach(item => {
        html += `<tr>
            <td>${escapeHtml(item.ProductName)}</td>
            <td>${item.Quantity}</td>
            <td>₱${formatNumber(item.Price)}</td>
            <td>₱${formatNumber(item.Total)}</td>
        </tr>`;
    });
    
    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const existingModal = document.getElementById('detailsModal');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', html);
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

async function viewInstallmentDetails(id) {
    const result = await apiCall(INSTALLMENT_API_URL, `getInstallmentById&id=${id}`);
    if (result.success && result.data) {
        const installment = result.data;
        const payments = result.payments || [];
        
        let html = `
            <div class="modal fade" id="detailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Installment Details - ${installment.InstallmentNo}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Receipt:</strong> ${installment.InstallmentNo}<br>
                                    <strong>Customer:</strong> ${escapeHtml(installment.CustomerName)}<br>
                                    <strong>Start Date:</strong> ${installment.StartDate}
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Amount:</strong> ₱${formatNumber(installment.TotalAmount)}<br>
                                    <strong>Monthly Payment:</strong> ₱${formatNumber(installment.MonthlyPayment)}<br>
                                    <strong>Status:</strong> ${installment.Status?.toUpperCase()}
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Payment #</th><th>Due Date</th><th>Amount</th><th>Status</th><th>Payment Date</th></tr></thead>
                                    <tbody>
        `;
        
        payments.forEach(p => {
            html += `<tr>
                <td>${p.PaymentNo}</td>
                <td>${p.DueDate}</td>
                <td>₱${formatNumber(p.Amount)}</td>
                <td><span class="badge-${p.Status === 'paid' ? 'approved' : 'pending'}">${p.Status?.toUpperCase()}</span></td>
                <td>${p.PaymentDate || '-'}</td>
            </tr>`;
        });
        
        html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const existingModal = document.getElementById('detailsModal');
        if (existingModal) existingModal.remove();
        
        document.body.insertAdjacentHTML('beforeend', html);
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        modal.show();
    }
}

// ============================================
// BUTTON HANDLERS - FIXED FUNCTION NAMES
// ============================================

document.getElementById('confirmReturnBtn').addEventListener('click', async function() {
    const id = document.getElementById('returnTransactionId').value;
    const type = document.getElementById('returnTransactionType').value;
    const reason = document.getElementById('returnReason').value;
    const notes = document.getElementById('returnNotes').value;
    
    if (!reason) {
        showToast('Please select a reason for return', 'warning');
        return;
    }
    
    const data = {
        transaction_id: parseInt(id),
        transaction_type: type,
        reason: reason,
        notes: notes
    };
    
    let success = false;
    if (type === 'sales') {
        success = await processCashReturn(id, data);
    } else if (type === 'installment') {
        success = await processInstallmentReturn(id, data);
    }
    
    if (success) {
        bootstrap.Modal.getInstance(document.getElementById('returnModal')).hide();
    }
});

document.getElementById('confirmWarrantyBtn').addEventListener('click', async function() {
    const id = document.getElementById('warrantyTransactionId').value;
    const type = document.getElementById('warrantyTransactionType').value;
    const issue = document.getElementById('warrantyIssue').value;
    const warrantyType = document.getElementById('warrantyType').value;
    const notes = document.getElementById('warrantyNotes').value;
    
    if (!issue) {
        showToast('Please describe the issue', 'warning');
        return;
    }
    
    const data = {
        transaction_id: parseInt(id),
        transaction_type: type,
        issue: issue,
        warranty_type: warrantyType,
        notes: notes
    };
    
    let success = false;
    if (type === 'sales') {
        success = await submitCashWarranty(id, data);
    } else if (type === 'installment') {
        success = await submitInstallmentWarranty(id, data);
    }
    
    if (success) {
        bootstrap.Modal.getInstance(document.getElementById('warrantyModal')).hide();
    }
});

// ============================================
// HELPER FUNCTIONS
// ============================================

document.getElementById('searchInput').addEventListener('input', function() {
    filterAndDisplay();
});

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
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadSales();
    loadInstallments();
});
</script>