<?php
// pages/expenses.php - Expenses Management
?>
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        background: #f0f4f9;
    }
    
    .expenses-container {
        width: 100%;
        max-width: 100%;
        padding: 15px 20px;
        overflow-x: hidden;
        box-sizing: border-box;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        min-width: 0;
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
    
    .stat-icon.danger { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .stat-icon.warning { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .stat-icon.info { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    .stat-icon.primary { background: rgba(79, 158, 255, 0.15); color: #4f9eff; }
    .stat-icon.success { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    
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
    .btn-excel { background: #217346; color: white; }
    
    .btn-primary:hover { background: #3b8be8; }
    .btn-success:hover { background: #1e8e3a; }
    .btn-danger:hover { background: #c82333; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-warning:hover { background: #e0a800; }
    .btn-excel:hover { background: #1a5c38; }
    
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
    
    .text-center { text-align: center; }
    .text-muted { color: #6c7a91; }
    
    .badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-active { background: #dcfce7; color: #10b981; }
    
    .mt-3 { margin-top: 15px; }
    .mb-3 { margin-bottom: 15px; }
    .mb-4 { margin-bottom: 20px; }
    .gap-2 { gap: 10px; }
    
    .d-flex { display: flex; }
    .align-items-center { align-items: center; }
    .justify-content-between { justify-content: space-between; }
    .flex-wrap { flex-wrap: wrap; }
    
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
        max-width: 500px;
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
        .expenses-container {
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
        .expenses-container {
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

<!-- ============================================ -->
<!-- HTML CONTENT -->
<!-- ============================================ -->
<div class="expenses-container">
    
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 style="margin:0;font-size:22px;"><i class="fas fa-coins"></i> Expenses Management</h4>
            <p class="text-muted mb-0" style="font-size:14px;">Track and manage all business expenses</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn btn-excel" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <button class="btn btn-primary" onclick="openAddExpenseModal()">
                <i class="fas fa-plus"></i> Add Expense
            </button>
            <button class="btn btn-warning" onclick="openAddTypeModal()">
                <i class="fas fa-tag"></i> Add Type
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
                <label>Expense Type</label>
                <select id="filterType">
                    <option value="">All Types</option>
                </select>
            </div>
        </div>
        <button class="btn btn-primary" onclick="loadExpenses()">
            <i class="fas fa-search"></i> Filter
        </button>
        <button class="btn btn-secondary" onclick="resetFilters()">
            <i class="fas fa-undo"></i> Reset
        </button>
    </div>
    
    <!-- STATS -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-value" id="totalExpenses">₱0.00</div>
            <div class="stat-label">Total Expenses</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-receipt"></i></div>
            <div class="stat-value" id="expenseCount">0</div>
            <div class="stat-label">Number of Expenses</div>
        </div>

    </div>
    <div class="stat-value" id="avgExpense" style="display: none;">₱0.00</div>
     <div class="stat-value" id="typeCount" style="display: none;">0</div>
    <!-- EXPENSES HISTORY TABLE -->
    <div class="card-custom">
        <div class="card-header">
            <span><i class="fas fa-list"></i> Expenses History</span>
            <span id="recordCount" style="font-size:13px;color:#6c7a91;">0 records</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Notes</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTableBody">
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="loading-spinner"></div> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<!-- ============================================ -->
<!-- ADD EXPENSE MODAL -->
<!-- ============================================ -->
<div class="modal-overlay" id="addExpenseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-plus-circle"></i> Add New Expense</h4>
            <button class="modal-close" onclick="closeModal('addExpenseModal')">&times;</button>
        </div>
        <form id="expenseForm" onsubmit="saveExpense(event)">
            <div class="form-group">
                <label>Expense Date <span class="required">*</span></label>
                <input type="date" id="expenseDate" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Expense Type <span class="required">*</span></label>
                <select id="expenseTypeId" class="form-control" required>
                    <option value="">Select Type</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input type="number" id="expenseAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea id="expenseNotes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addExpenseModal')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- ADD EXPENSE TYPE MODAL -->
<!-- ============================================ -->
<div class="modal-overlay" id="addTypeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-tag"></i> Add Expense Type</h4>
            <button class="modal-close" onclick="closeModal('addTypeModal')">&times;</button>
        </div>
        <form id="typeForm" onsubmit="saveExpenseType(event)">
            <div class="form-group">
                <label>Type Name <span class="required">*</span></label>
                <input type="text" id="typeName" class="form-control" placeholder="e.g., Utilities" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="typeDescription" class="form-control" rows="2" placeholder="Brief description..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addTypeModal')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Type</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- EDIT EXPENSE MODAL -->
<!-- ============================================ -->
<div class="modal-overlay" id="editExpenseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-edit"></i> Edit Expense</h4>
            <button class="modal-close" onclick="closeModal('editExpenseModal')">&times;</button>
        </div>
        <form id="editExpenseForm" onsubmit="updateExpense(event)">
            <input type="hidden" id="editExpenseId">
            <div class="form-group">
                <label>Expense Date <span class="required">*</span></label>
                <input type="date" id="editExpenseDate" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Expense Type <span class="required">*</span></label>
                <select id="editExpenseTypeId" class="form-control" required>
                    <option value="">Select Type</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input type="number" id="editExpenseAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea id="editExpenseNotes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="deleteExpense()"><i class="fas fa-trash"></i> Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editExpenseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- TOAST CONTAINER -->
<!-- ============================================ -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ============================================
// CONFIGURATION - FIXED API URL
// ============================================
const API_URL = '/dmb/datafetcher/expensesdata.php';
let expenseTypes = [];
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

// Close modal on click outside
document.addEventListener('click', function(e) {
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
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
        let result;
        try {
            result = await response.json();
        } catch (error) {
            throw new Error(`The expense server returned an invalid response (HTTP ${response.status}). Please check the server error log.`);
        }
        if (!result || typeof result !== 'object') {
            throw new Error('The expense server returned an invalid response.');
        }
        if (!response.ok || !result.success) {
            showToast(result.message || result.error || 'API Error', 'error');
            return { ...result, success: false };
        }
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false };
    }
}

// ============================================
// LOAD EXPENSE TYPES
// ============================================
async function loadExpenseTypes() {
    const result = await apiCall('getExpenseTypes');
    if (result.success) {
        expenseTypes = result.data;
        populateTypeSelects(expenseTypes);
        document.getElementById('typeCount').textContent = expenseTypes.length;
        return expenseTypes;
    }
    return [];
}

function populateTypeSelects(types) {
    const selects = ['expenseTypeId', 'editExpenseTypeId', 'filterType'];
    selects.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;
        
        const currentValue = select.value;
        select.innerHTML = '<option value="">Select Type</option>';
        types.forEach(type => {
            const option = document.createElement('option');
            option.value = type.ExpenseTypeID;
            option.textContent = type.TypeName;
            select.appendChild(option);
        });
        if (currentValue) {
            select.value = currentValue;
        }
    });
}

// ============================================
// LOAD EXPENSES
// ============================================
async function loadExpenses() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const typeId = document.getElementById('filterType').value;
    
    let url = `getExpenses&date_from=${dateFrom}&date_to=${dateTo}`;
    if (typeId) url += `&type_id=${typeId}`;
    
    const result = await apiCall(url);
    if (result.success) {
        renderExpenses(result.data);
        await loadSummary();
    }
}

function renderExpenses(expenses) {
    const tbody = document.getElementById('expensesTableBody');
    
    if (!expenses || expenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No expenses found</td></tr>';
        document.getElementById('recordCount').textContent = '0 records';
        return;
    }
    
    tbody.innerHTML = expenses.map(exp => `
        <tr>
            <td>${formatDate(exp.ExpenseDate)}</td>
            <td><span class="badge badge-active">${escapeHtml(exp.TypeName || 'Unknown')}</span></td>
            <td style="font-weight:600;color:#dc3545;">₱${formatNumber(exp.Amount)}</td>
            <td>${escapeHtml(exp.Notes || '-')}</td>
            <td>${escapeHtml(exp.CreatedBy || '-')}</td>
            <td>
                <button class="btn btn-primary" style="padding:4px 12px;font-size:12px;" onclick="editExpense(${exp.ExpenseID})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('recordCount').textContent = `${expenses.length} records`;
}

// ============================================
// LOAD SUMMARY
// ============================================
async function loadSummary() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const result = await apiCall(`getExpenseSummary&date_from=${dateFrom}&date_to=${dateTo}`);
    if (result.success) {
        const data = result.data;
        document.getElementById('totalExpenses').textContent = '₱' + formatNumber(data.summary.TotalAmount);
        document.getElementById('expenseCount').textContent = data.summary.TotalCount || 0;
        
        const days = Math.max(1, Math.ceil((new Date(dateTo) - new Date(dateFrom)) / (1000 * 60 * 60 * 24)));
        const avg = (data.summary.TotalAmount || 0) / days;
        document.getElementById('avgExpense').textContent = '₱' + formatNumber(avg);
    }
}

// ============================================
// ADD EXPENSE
// ============================================
function openAddExpenseModal() {
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseDate').value = new Date().toISOString().split('T')[0];
    openModal('addExpenseModal');
}

async function saveExpense(event) {
    event.preventDefault();
    
    const expenseDate = document.getElementById('expenseDate').value;
    const expenseTypeId = document.getElementById('expenseTypeId').value;
    const amount = document.getElementById('expenseAmount').value;
    const notes = document.getElementById('expenseNotes').value;
    
    if (!expenseDate || !expenseTypeId || !amount) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    const result = await apiCall('addExpense', 'POST', {
        expense_date: expenseDate,
        expense_type_id: parseInt(expenseTypeId),
        amount: parseFloat(amount),
        notes: notes
    });
    
    if (result.success) {
        showToast('Expense added successfully!', 'success');
        closeModal('addExpenseModal');
        loadExpenses();
    }
}

// ============================================
// EDIT EXPENSE
// ============================================
// ============================================
// EDIT EXPENSE - FIXED
// ============================================
async function editExpense(expenseId) {
    currentEditId = expenseId;
    
    const result = await apiCall(`getExpenseById&id=${expenseId}`);
    if (!result.success || !result.data) {
        showToast('Failed to load expense details', 'error');
        return;
    }
    
    const exp = result.data;
    document.getElementById('editExpenseId').value = exp.ExpenseID;
    document.getElementById('editExpenseDate').value = exp.ExpenseDate;
    document.getElementById('editExpenseTypeId').value = exp.ExpenseTypeID;
    document.getElementById('editExpenseAmount').value = exp.Amount;
    document.getElementById('editExpenseNotes').value = exp.Notes || '';
    
    openModal('editExpenseModal');
}

async function updateExpense(event) {
    event.preventDefault();
    
    const expenseId = document.getElementById('editExpenseId').value;
    const expenseDate = document.getElementById('editExpenseDate').value;
    const expenseTypeId = document.getElementById('editExpenseTypeId').value;
    const amount = document.getElementById('editExpenseAmount').value;
    const notes = document.getElementById('editExpenseNotes').value;
    
    if (!expenseDate || !expenseTypeId || !amount) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    const result = await apiCall('updateExpense', 'POST', {
        expense_id: parseInt(expenseId),
        expense_date: expenseDate,
        expense_type_id: parseInt(expenseTypeId),
        amount: parseFloat(amount),
        notes: notes
    });
    
    if (result.success) {
        showToast('Expense updated successfully!', 'success');
        closeModal('editExpenseModal');
        loadExpenses();
    }
}

async function deleteExpense() {
    const expenseId = document.getElementById('editExpenseId').value;
    
    if (!confirm('Are you sure you want to delete this expense?')) return;
    
    const result = await apiCall(`deleteExpense&id=${expenseId}`, 'DELETE');
    if (result.success) {
        showToast('Expense deleted successfully!', 'success');
        closeModal('editExpenseModal');
        loadExpenses();
    }
}

// ============================================
// ADD EXPENSE TYPE
// ============================================
function openAddTypeModal() {
    document.getElementById('typeForm').reset();
    openModal('addTypeModal');
}

async function saveExpenseType(event) {
    event.preventDefault();
    
    const typeName = document.getElementById('typeName').value.trim();
    const description = document.getElementById('typeDescription').value.trim();
    
    if (!typeName) {
        showToast('Please enter a type name', 'error');
        return;
    }
    
    const result = await apiCall('addExpenseType', 'POST', {
        type_name: typeName,
        description: description
    });
    
    if (result.success) {
        showToast('Expense type added successfully!', 'success');
        closeModal('addTypeModal');
        await loadExpenseTypes();
        populateTypeSelects(expenseTypes);
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
    document.getElementById('filterType').value = '';
    loadExpenses();
}

// ============================================
// EXPORT TO EXCEL
// ============================================
function exportToExcel() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const typeSelect = document.getElementById('filterType');
    const typeName = typeSelect.options[typeSelect.selectedIndex]?.text || 'All Types';
    
    const fromDate = new Date(dateFrom + 'T00:00:00').toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
    const toDate = new Date(dateTo + 'T00:00:00').toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
    
    const tbody = document.getElementById('expensesTableBody');
    const rows = tbody.querySelectorAll('tr');
    
    if (rows.length === 0 || rows[0].textContent.includes('No expenses found')) {
        showToast('No data to export', 'error');
        return;
    }
    
    let csv = [];
    const branchName = '<?php echo $_SESSION['branch_name'] ?? $_SESSION['BRANCH'] ?? 'Main Branch'; ?>';
    
    csv.push(`"${branchName}"`);
    csv.push('"EXPENSES REPORT"');
    csv.push(`"Date Range: ${fromDate} to ${toDate}"`);
    if (typeName !== 'All Types') {
        csv.push(`"Filter: ${typeName}"`);
    }
    csv.push('');
    
    const totalExpenses = document.getElementById('totalExpenses').textContent;
    const expenseCount = document.getElementById('expenseCount').textContent;
    const avgExpense = document.getElementById('avgExpense').textContent;
    
    csv.push('"SUMMARY"');
    csv.push(`"Total Expenses","${totalExpenses}"`);
    csv.push(`"Number of Expenses","${expenseCount}"`);
    csv.push(`"Average per Day","${avgExpense}"`);
    csv.push('');
    csv.push('"DETAILED EXPENSES"');
    csv.push('');
    csv.push('"Date","Expense Type","Amount","Notes","Created By"');
    
    rows.forEach(row => {
        if (row.textContent.includes('No expenses found')) return;
        if (row.textContent.includes('Loading')) return;
        
        const cols = row.querySelectorAll('td');
        if (cols.length < 5) return;
        
        const date = cols[0]?.textContent.trim() || '';
        const type = cols[1]?.textContent.trim() || '';
        const amount = cols[2]?.textContent.trim() || '₱0.00';
        const notes = cols[3]?.textContent.trim() || '';
        const createdBy = cols[4]?.textContent.trim() || '';
        
        const cleanAmount = amount.replace('₱', '').replace(/,/g, '').trim();
        
        csv.push(`"${date}","${type}","${cleanAmount}","${notes.replace(/"/g, '""')}","${createdBy}"`);
    });
    
    csv.push('');
    csv.push(`"Generated on: ${new Date().toLocaleString()}"`);
    csv.push('"Report generated by: <?php echo $_SESSION['NAME'] ?? $_SESSION['username'] ?? 'System'; ?>"');
    
    const csvContent = csv.join('\n');
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `Expenses_Report_${dateFrom}_to_${dateTo}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showToast('Export completed successfully!', 'success');
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', async function() {
    await loadExpenseTypes();
    loadExpenses();
});
</script>
