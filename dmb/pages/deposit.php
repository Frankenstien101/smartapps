<?php
// pages/deposit.php - Deposit Transaction Management (No Approval)

$currentUser = $_SESSION['NAME'] ?? $_SESSION['username'] ?? 'Admin';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['BRANCH'] ?? 'Main Branch';
?>
<style>
    .deposit-container {
        width: 100%;
        max-width: 100%;
        padding: 15px 20px;
        overflow-x: hidden;
        box-sizing: border-box;
        background: #f0f4f9;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
        min-width: 0;
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
    
    .stat-icon.success { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .stat-icon.primary { background: rgba(79, 158, 255, 0.15); color: #4f9eff; }
    .stat-icon.info { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #1a2a3a;
        word-break: break-word;
    }
    
    .stat-label {
        font-size: 12px;
        color: #6c7a91;
        margin-top: 5px;
    }
    
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
        width: 100%;
    }
    
    .filter-group {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
        flex: 1;
    }
    
    .filter-group .form-group {
        flex: 0 0 auto;
        min-width: 150px;
    }
    
    .filter-group .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #4a5568;
    }
    
    .filter-group .form-group input,
    .filter-group .form-group select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        background: #fafcff;
    }
    
    .filter-section .btn {
        padding: 10px 22px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    
    .btn-primary { background: #4f9eff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-warning { background: #ffc107; color: #1a2a3a; }
    
    .btn-primary:hover { background: #3b8be8; }
    .btn-success:hover { background: #1e8e3a; }
    .btn-danger:hover { background: #c82333; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-warning:hover { background: #e0a800; }
    
    .card-custom {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .card-header {
        padding: 14px 20px;
        border-bottom: 1px solid #eef2f7;
        font-weight: 600;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .card-body {
        padding: 18px 20px;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        resize: vertical;
        background: #fafcff;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #4f9eff;
        box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.1);
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 5px;
    }
    
    .form-group label .required {
        color: #dc3545;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }
    
    .table {
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
    }
    
    .table th {
        background: #f8fafc;
        padding: 10px 14px;
        font-weight: 600;
        text-align: left;
        border-bottom: 2px solid #eef2f7;
    }
    
    .table td {
        padding: 10px 14px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .table tbody tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-info { background: #dbeafe; color: #2563eb; }
    .badge-success { background: #dcfce7; color: #10b981; }
    
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .text-muted { color: #6c7a91; }
    .mt-3 { margin-top: 15px; }
    .mb-3 { margin-bottom: 15px; }
    .mb-4 { margin-bottom: 20px; }
    .gap-2 { gap: 10px; }
    
    .d-flex { display: flex; }
    .align-items-center { align-items: center; }
    .justify-content-between { justify-content: space-between; }
    .flex-wrap { flex-wrap: wrap; }
    .flex-1 { flex: 1; }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 16px;
        max-width: 550px;
        width: 95%;
        padding: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 15px;
        border-bottom: 1px solid #eef2f7;
        margin-bottom: 20px;
    }
    
    .modal-header h4 {
        margin: 0;
        font-size: 18px;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6c7a91;
    }
    
    .modal-close:hover {
        color: #1a2a3a;
    }
    
    .modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding-top: 15px;
        border-top: 1px solid #eef2f7;
        margin-top: 20px;
    }
    
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .toast {
        padding: 14px 24px;
        border-radius: 12px;
        color: white;
        font-weight: 500;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
        max-width: 400px;
    }
    
    .toast.success { background: #10b981; }
    .toast.error { background: #ef4444; }
    .toast.info { background: #4f9eff; }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
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
    
    @media (max-width: 992px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .deposit-container {
            padding: 12px 15px;
        }
    }
    
    @media (max-width: 768px) {
        .filter-section {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-group {
            flex-direction: column;
        }
        .filter-group .form-group {
            min-width: unset;
            width: 100%;
        }
        .filter-section .btn {
            justify-content: center;
            width: 100%;
        }
        .form-row {
            grid-template-columns: 1fr;
        }
        .stats-row {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .stat-card {
            padding: 14px 16px;
        }
        .stat-value {
            font-size: 20px;
        }
        .card-body {
            padding: 14px 16px;
        }
        .deposit-container {
            padding: 10px 12px;
        }
    }
    
    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .stat-card {
            padding: 12px 14px;
        }
        .stat-value {
            font-size: 17px;
        }
        .stat-icon {
            width: 36px;
            height: 36px;
            font-size: 16px;
        }
        .modal-content {
            padding: 16px;
            margin: 10px;
        }
    }
</style>

<div class="deposit-container">
    
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 style="margin:0;font-size:22px;"><i class="fas fa-piggy-bank"></i> Deposit Transactions</h4>
            <p class="text-muted mb-0" style="font-size:14px;">Manage and track all deposit transactions</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn btn-primary" onclick="openAddDepositModal()">
                <i class="fas fa-plus"></i> New Deposit
            </button>
            <button class="btn btn-success" onclick="refreshData()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
    </div>
    
    <!-- FILTERS -->
    <div class="filter-section">
        <div class="filter-group">
            <div class="form-group">
                <label>Date From</label>
                <input type="date" id="dateFrom" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" id="dateTo" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Deposit Type</label>
                <select id="filterType">
                    <option value="all">All Types</option>
                    <option value="Cash Deposit">Cash Deposit</option>
                    <option value="Check Deposit">Check Deposit</option>
                    <option value="Online Transfer">Online Transfer</option>
                    <option value="GCash Transfer">GCash Transfer</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        <button class="btn btn-primary" onclick="loadDeposits()">
            <i class="fas fa-search"></i> Filter
        </button>
        <button class="btn btn-secondary" onclick="resetFilters()">
            <i class="fas fa-undo"></i> Reset
        </button>
    </div>
    
    <!-- STATS -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-value" id="totalDeposits">₱0.00</div>
            <div class="stat-label">Total Deposits</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-receipt"></i></div>
            <div class="stat-value" id="depositCount">0</div>
            <div class="stat-label">Number of Deposits</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value" id="avgDeposit">₱0.00</div>
            <div class="stat-label">Average Deposit</div>
        </div>
    </div>
    
    <!-- DEPOSITS TABLE -->
    <div class="card-custom">
        <div class="card-header">
            <span><i class="fas fa-list"></i> Deposit History</span>
            <span id="recordCount" style="font-size:13px;color:#6c7a91;">0 records</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="depositsTable">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Reference</th>
                            <th>Bank</th>
                            <th>Notes</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="depositsTableBody">
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="loading-spinner"></div> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<!-- ADD DEPOSIT MODAL -->
<div class="modal-overlay" id="addDepositModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-plus-circle"></i> New Deposit</h4>
            <button class="modal-close" onclick="closeModal('addDepositModal')">&times;</button>
        </div>
        <form id="depositForm" onsubmit="saveDeposit(event)">
            <div class="form-row">
                <div class="form-group">
                    <label>Deposit Date <span class="required">*</span></label>
                    <input type="date" id="depositDate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deposit Type <span class="required">*</span></label>
                    <select id="depositType" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="Cash Deposit">Cash Deposit</option>
                        <option value="Check Deposit">Check Deposit</option>
                        <option value="Online Transfer">Online Transfer</option>
                        <option value="GCash Transfer">GCash Transfer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input type="number" id="depositAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Reference No.</label>
                    <input type="text" id="depositReference" class="form-control" placeholder="Check # / Transaction ID">
                </div>
                <div class="form-group">
                    <label>Bank Name</label>
                    <input type="text" id="depositBank" class="form-control" placeholder="e.g., BDO, BPI">
                </div>
            </div>
            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea id="depositNotes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addDepositModal')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Deposit</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT DEPOSIT MODAL -->
<div class="modal-overlay" id="editDepositModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-edit"></i> Edit Deposit</h4>
            <button class="modal-close" onclick="closeModal('editDepositModal')">&times;</button>
        </div>
        <form id="editDepositForm" onsubmit="updateDeposit(event)">
            <input type="hidden" id="editDepositId">
            <div class="form-row">
                <div class="form-group">
                    <label>Deposit Date <span class="required">*</span></label>
                    <input type="date" id="editDepositDate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deposit Type <span class="required">*</span></label>
                    <select id="editDepositType" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="Cash Deposit">Cash Deposit</option>
                        <option value="Check Deposit">Check Deposit</option>
                        <option value="Online Transfer">Online Transfer</option>
                        <option value="GCash Transfer">GCash Transfer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input type="number" id="editDepositAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Reference No.</label>
                    <input type="text" id="editDepositReference" class="form-control" placeholder="Check # / Transaction ID">
                </div>
                <div class="form-group">
                    <label>Bank Name</label>
                    <input type="text" id="editDepositBank" class="form-control" placeholder="e.g., BDO, BPI">
                </div>
            </div>
            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea id="editDepositNotes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="deleteDeposit()"><i class="fas fa-trash"></i> Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editDepositModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW DEPOSIT MODAL -->
<div class="modal-overlay" id="viewDepositModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-eye"></i> Deposit Details</h4>
            <button class="modal-close" onclick="closeModal('viewDepositModal')">&times;</button>
        </div>
        <div id="viewDepositContent">
            <div class="text-center py-4">
                <div class="loading-spinner"></div>
                <p class="mt-2">Loading...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('viewDepositModal')">Close</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ============================================
// CONFIGURATION
// ============================================
const API_URL = '/dmb/datafetcher/api_deposit.php';
let currentEditId = null;

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatNumber(num) {
    if (num === undefined || num === null || isNaN(num)) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// API CALLS
// ============================================
async function apiCall(action, method = 'GET', data = null) {
    try {
        const options = { method, headers: { 'Content-Type': 'application/json' } };
        if (data) options.body = JSON.stringify(data);
        const response = await fetch(`${API_URL}?action=${action}`, options);
        const result = await response.json();
        if (!result.success) {
            showToast(result.message || 'API Error', 'error');
        }
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false };
    }
}

// ============================================
// LOAD DEPOSITS
// ============================================
async function loadDeposits() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const type = document.getElementById('filterType').value;
    
    let url = `getDeposits&date_from=${dateFrom}&date_to=${dateTo}`;
    if (type !== 'all') url += `&type=${encodeURIComponent(type)}`;
    
    const result = await apiCall(url);
    if (result.success) {
        renderDeposits(result.data);
        await loadSummary();
    }
}

function renderDeposits(deposits) {
    const tbody = document.getElementById('depositsTableBody');
    
    if (!deposits || deposits.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No deposits found</td></tr>';
        document.getElementById('recordCount').textContent = '0 records';
        return;
    }
    
    tbody.innerHTML = deposits.map(d => `
        <tr>
            <td><strong>${escapeHtml(d.DepositRef || 'N/A')}</strong></td>
            <td>${formatDate(d.DepositDate)}</td>
            <td><span class="badge badge-info">${escapeHtml(d.DepositType || 'N/A')}</span></td>
            <td style="font-weight:600;color:#28a745;">₱${formatNumber(d.Amount)}</td>
            <td>${escapeHtml(d.ReferenceNo || '-')}</td>
            <td>${escapeHtml(d.BankName || '-')}</td>
            <td>${escapeHtml(d.Notes ? d.Notes.substring(0, 30) + (d.Notes.length > 30 ? '...' : '') : '-')}</td>
            <td>${escapeHtml(d.CreatedBy || '-')}</td>
            <td>
                <button class="btn btn-primary" style="padding:4px 12px;font-size:12px;" onclick="viewDeposit(${d.DepositID})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-warning" style="padding:4px 12px;font-size:12px;" onclick="editDeposit(${d.DepositID})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('recordCount').textContent = `${deposits.length} records`;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// ============================================
// LOAD SUMMARY
// ============================================
async function loadSummary() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const result = await apiCall(`getDepositSummary&date_from=${dateFrom}&date_to=${dateTo}`);
    if (result.success) {
        const data = result.data.summary;
        document.getElementById('totalDeposits').textContent = '₱' + formatNumber(data.TotalAmount);
        document.getElementById('depositCount').textContent = data.TotalCount || 0;
        
        const avg = data.TotalCount > 0 ? data.TotalAmount / data.TotalCount : 0;
        document.getElementById('avgDeposit').textContent = '₱' + formatNumber(avg);
    }
}

// ============================================
// ADD DEPOSIT
// ============================================
function openAddDepositModal() {
    document.getElementById('depositForm').reset();
    document.getElementById('depositDate').value = new Date().toISOString().split('T')[0];
    openModal('addDepositModal');
}

async function saveDeposit(event) {
    event.preventDefault();
    
    const depositDate = document.getElementById('depositDate').value;
    const depositType = document.getElementById('depositType').value;
    const amount = document.getElementById('depositAmount').value;
    const reference = document.getElementById('depositReference').value;
    const bank = document.getElementById('depositBank').value;
    const notes = document.getElementById('depositNotes').value;
    
    if (!depositDate || !depositType || !amount) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    const result = await apiCall('addDeposit', 'POST', {
        deposit_date: depositDate,
        deposit_type: depositType,
        amount: parseFloat(amount),
        reference_no: reference,
        bank_name: bank,
        notes: notes
    });
    
    if (result.success) {
        showToast('Deposit added successfully!', 'success');
        closeModal('addDepositModal');
        loadDeposits();
    }
}

// ============================================
// VIEW DEPOSIT
// ============================================
async function viewDeposit(depositId) {
    const result = await apiCall(`getDepositById&id=${depositId}`);
    if (!result.success || !result.data) {
        showToast('Failed to load deposit details', 'error');
        return;
    }
    
    const d = result.data;
    const container = document.getElementById('viewDepositContent');
    
    container.innerHTML = `
        <div style="padding:10px 0;">
            <div class="form-row">
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Reference #</label>
                    <div style="font-weight:600;font-size:16px;">${escapeHtml(d.DepositRef || 'N/A')}</div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Date</label>
                    <div>${formatDate(d.DepositDate)}</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Type</label>
                    <div><span class="badge badge-info">${escapeHtml(d.DepositType)}</span></div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Amount</label>
                    <div style="font-weight:700;font-size:20px;color:#28a745;">₱${formatNumber(d.Amount)}</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Reference No.</label>
                    <div>${escapeHtml(d.ReferenceNo || 'N/A')}</div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Bank</label>
                    <div>${escapeHtml(d.BankName || 'N/A')}</div>
                </div>
            </div>
            <div class="form-group">
                <label style="font-size:12px;color:#6c7a91;">Notes</label>
                <div style="background:#f8fafc;padding:10px;border-radius:8px;">${escapeHtml(d.Notes || 'No notes')}</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Created By</label>
                    <div>${escapeHtml(d.CreatedBy || '-')}</div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;color:#6c7a91;">Created At</label>
                    <div>${d.CreatedAt ? new Date(d.CreatedAt).toLocaleString() : '-'}</div>
                </div>
            </div>
        </div>
    `;
    
    openModal('viewDepositModal');
}

// ============================================
// EDIT DEPOSIT
// ============================================
async function editDeposit(depositId) {
    currentEditId = depositId;
    
    const result = await apiCall(`getDepositById&id=${depositId}`);
    if (!result.success || !result.data) {
        showToast('Failed to load deposit details', 'error');
        return;
    }
    
    const d = result.data;
    document.getElementById('editDepositId').value = d.DepositID;
    document.getElementById('editDepositDate').value = d.DepositDate;
    document.getElementById('editDepositType').value = d.DepositType;
    document.getElementById('editDepositAmount').value = d.Amount;
    document.getElementById('editDepositReference').value = d.ReferenceNo || '';
    document.getElementById('editDepositBank').value = d.BankName || '';
    document.getElementById('editDepositNotes').value = d.Notes || '';
    
    openModal('editDepositModal');
}

async function updateDeposit(event) {
    event.preventDefault();
    
    const depositId = document.getElementById('editDepositId').value;
    const depositDate = document.getElementById('editDepositDate').value;
    const depositType = document.getElementById('editDepositType').value;
    const amount = document.getElementById('editDepositAmount').value;
    const reference = document.getElementById('editDepositReference').value;
    const bank = document.getElementById('editDepositBank').value;
    const notes = document.getElementById('editDepositNotes').value;
    
    if (!depositDate || !depositType || !amount) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    const result = await apiCall('updateDeposit', 'POST', {
        deposit_id: parseInt(depositId),
        deposit_date: depositDate,
        deposit_type: depositType,
        amount: parseFloat(amount),
        reference_no: reference,
        bank_name: bank,
        notes: notes
    });
    
    if (result.success) {
        showToast('Deposit updated successfully!', 'success');
        closeModal('editDepositModal');
        loadDeposits();
    }
}

// ============================================
// DELETE DEPOSIT
// ============================================
async function deleteDeposit() {
    const depositId = document.getElementById('editDepositId').value;
    
    if (!confirm('Are you sure you want to delete this deposit?')) return;
    
    const result = await apiCall(`deleteDeposit&id=${depositId}`, 'DELETE');
    if (result.success) {
        showToast('Deposit deleted successfully!', 'success');
        closeModal('editDepositModal');
        loadDeposits();
    }
}

// ============================================
// RESET FILTERS
// ============================================
function resetFilters() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('dateFrom').value = firstDay.toISOString().split('T')[0];
    document.getElementById('dateTo').value = today.toISOString().split('T')[0];
    document.getElementById('filterType').value = 'all';
    loadDeposits();
}

// ============================================
// REFRESH DATA
// ============================================
function refreshData() {
    showToast('Refreshing data...', 'info');
    loadDeposits();
}

// ============================================
// CLOSE MODALS ON BACKDROP CLICK
// ============================================
document.addEventListener('click', function(e) {
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    loadDeposits();
});
</script>