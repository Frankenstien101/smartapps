<?php
// pages/installment-report.php - Installment Report Page
?>
<style>
    .report-container {
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
    }
    
    .date-range {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    
    .date-group {
        flex: 1;
        min-width: 150px;
    }
    
    .date-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #4a5568;
    }
    
    .date-group input, .date-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
    }
    
    .btn-primary {
        background: #4f9eff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    /* Tabs */
    .tabs-container {
        background: white;
        border-radius: 16px;
        margin-bottom: 20px;
        padding: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-wrap: wrap;
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
    
    /* Report Table */
    .report-table-container {
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
    
    .badge-active { background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-completed { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-overdue { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-returned { background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
    .summary-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-top: 20px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    
    .summary-row.total {
        font-size: 16px;
        font-weight: 800;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        margin-top: 10px;
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
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .date-range {
            flex-direction: column;
        }
        .date-group {
            width: 100%;
        }
        .tabs-container {
            flex-wrap: wrap;
        }
        .tab-btn {
            font-size: 12px;
            padding: 8px;
        }
        .table th, .table td {
            padding: 8px;
            font-size: 11px;
        }
    }
</style>

<div class="report-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-chart-line"></i> Installment Report</h4>
            <p class="text-muted mb-0">View and analyze installment performance</p>
        </div>
        <div>
            <button class="btn-secondary" onclick="exportReport()">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalLoanAmount">₱0</div>
            <div class="stat-label">Total Loan Amount</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="totalPaidAmount">₱0</div>
            <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-value" id="totalRemaining">₱0</div>
            <div class="stat-label">Remaining Balance</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-percent"></i></div>
            <div class="stat-value" id="collectionRate">0%</div>
            <div class="stat-label">Collection Rate</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="date-range">
            <div class="date-group">
                <label>Start Date</label>
                <input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="date-group">
                <label>End Date</label>
                <input type="date" id="endDate" value="<?php echo date('Y-m-t'); ?>">
            </div>
            <div class="date-group">
                <label>Status Filter</label>
                <select id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed/Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="returned">Returned</option>
                </select>
            </div>
            <div class="date-group">
                <button class="btn-primary" onclick="loadReport()" style="margin-top: 22px;">
                    <i class="fas fa-search"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs-container">
        <button class="tab-btn active" data-tab="summary">Summary</button>
        <button class="tab-btn" data-tab="customer">Per Customer</button>
        <button class="tab-btn" data-tab="payment-status">Payment Status</button>
        <button class="tab-btn" data-tab="details">Complete Details</button>
    </div>
    
    <!-- Report Table -->
    <div class="report-table-container">
        <div class="table-header">
            <span id="reportTitle"><i class="fas fa-chart-pie"></i> Installment Summary</span>
            <button class="btn-refresh" onclick="loadReport()" style="background:transparent; border:none; color:#4f9eff;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead id="tableHead">
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody">
                    <tr><td colspan="2" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                </tbody>
            </table>
        </div>
        <div class="summary-card" id="summaryCard">
            <div class="summary-row">
                <span>Total Installments:</span>
                <span id="totalInstallments">0</span>
            </div>
            <div class="summary-row">
                <span>Active Installments:</span>
                <span id="activeCount">0</span>
            </div>
            <div class="summary-row">
                <span>Completed Installments:</span>
                <span id="completedCount">0</span>
            </div>
            <div class="summary-row">
                <span>Overdue Installments:</span>
                <span id="overdueCount">0</span>
            </div>
            <div class="summary-row">
                <span>Returned Installments:</span>
                <span id="returnedCount">0</span>
            </div>
            <div class="summary-row total">
                <span>Collection Efficiency:</span>
                <span id="collectionEfficiency">0%</span>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/installmentreportdata.php';

let currentTab = 'summary';
let allInstallments = [];
let filteredInstallments = [];

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

async function loadInstallments() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const status = document.getElementById('statusFilter').value;
    
    const result = await apiCall(`getInstallments&start_date=${startDate}&end_date=${endDate}&status=${status}`);
    if (result.success && result.data) {
        allInstallments = result.data;
        filteredInstallments = [...allInstallments];
        displayCurrentTab();
        loadStats();
    } else {
        document.getElementById('reportTableBody').innerHTML = '<td><td colspan="2" class="text-center text-muted">No installment data available<\/td><\/tr>';
    }
}

async function loadStats() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    const result = await apiCall(`getInstallmentStats&start_date=${startDate}&end_date=${endDate}`);
    if (result.success && result.data) {
        const stats = result.data;
        document.getElementById('totalLoanAmount').innerHTML = '₱' + formatNumber(stats.TotalLoanAmount || 0);
        document.getElementById('totalPaidAmount').innerHTML = '₱' + formatNumber(stats.TotalPaidAmount || 0);
        document.getElementById('totalRemaining').innerHTML = '₱' + formatNumber(stats.TotalRemainingBalance || 0);
        document.getElementById('collectionRate').innerHTML = (stats.CollectionRate || 0) + '%';
        
        document.getElementById('totalInstallments').innerHTML = stats.TotalInstallments || 0;
        document.getElementById('activeCount').innerHTML = stats.ActiveInstallments || 0;
        document.getElementById('completedCount').innerHTML = stats.CompletedInstallments || 0;
        document.getElementById('overdueCount').innerHTML = stats.OverdueCount || 0;
        document.getElementById('returnedCount').innerHTML = stats.ReturnedInstallments || 0;
        
        const efficiency = stats.TotalLoanAmount > 0 ? ((stats.TotalPaidAmount / stats.TotalLoanAmount) * 100).toFixed(1) : 0;
        document.getElementById('collectionEfficiency').innerHTML = efficiency + '%';
    }
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displayCurrentTab() {
    if (currentTab === 'summary') {
        displaySummaryTab();
    } else if (currentTab === 'customer') {
        displayCustomerTab();
    } else if (currentTab === 'payment-status') {
        displayPaymentStatusTab();
    } else if (currentTab === 'details') {
        displayDetailsTab();
    }
}

function displaySummaryTab() {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Metric</th>
            <th>Value</th>
        </tr>
    `;
    
    const totalLoan = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.TotalAmount) || 0), 0);
    const totalPaid = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.PaidAmount) || 0), 0);
    const totalRemaining = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.RemainingBalance) || 0), 0);
    const activeCount = filteredInstallments.filter(i => i.Status === 'active').length;
    const completedCount = filteredInstallments.filter(i => i.Status === 'completed').length;
    const overdueCount = filteredInstallments.filter(i => i.Status === 'overdue').length;
    const returnedCount = filteredInstallments.filter(i => i.Status === 'returned').length;
    const avgMonthlyPayment = filteredInstallments.length > 0 ? 
        filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.MonthlyPayment) || 0), 0) / filteredInstallments.length : 0;
    const collectionRate = totalLoan > 0 ? ((totalPaid / totalLoan) * 100).toFixed(2) : 0;
    
    tbody.innerHTML = `
        <tr><td><strong>Total Loan Amount</strong><\/td><td>₱${formatNumber(totalLoan)}<\/td><\/tr>
        <tr><td><strong>Total Paid Amount</strong><\/td><td>₱${formatNumber(totalPaid)}<\/td><\/tr>
        <tr><td><strong>Total Remaining Balance</strong><\/td><td>₱${formatNumber(totalRemaining)}<\/td><\/tr>
        <tr><td><strong>Collection Rate</strong><\/td><td>${collectionRate}%<\/td><\/tr>
        <tr style="background:#f0fdf4;"><td><strong>Active Installments</strong><\/td><td>${activeCount}<\/td><\/tr>
        <tr style="background:#fef3c7;"><td><strong>Overdue Installments</strong><\/td><td>${overdueCount}<\/td><\/tr>
        <tr style="background:#dcfce7;"><td><strong>Completed Installments</strong><\/td><td>${completedCount}<\/td><\/tr>
        <tr style="background:#f1f5f9;"><td><strong>Returned Installments</strong><\/td><td>${returnedCount}<\/td><\/tr>
        <tr><td><strong>Average Monthly Payment</strong><\/td><td>₱${formatNumber(avgMonthlyPayment)}<\/td><\/tr>
        <tr><td><strong>Total Number of Installments</strong><\/td><td>${filteredInstallments.length}<\/td><\/tr>
    `;
    
    document.getElementById('reportTitle').innerHTML = '<i class="fas fa-chart-pie"></i> Installment Summary';
}

function displayCustomerTab() {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Installments</th>
            <th>Total Loan</th>
            <th>Total Paid</th>
            <th>Remaining</th>
            <th>Status</th>
        </tr>
    `;
    
    // Group by customer
    const customerMap = new Map();
    filteredInstallments.forEach(inst => {
        const name = inst.CustomerName;
        if (!customerMap.has(name)) {
            customerMap.set(name, {
                customer_name: name,
                phone: inst.CustomerPhone || '-',
                count: 0,
                total_loan: 0,
                total_paid: 0,
                remaining: 0,
                statuses: []
            });
        }
        const customer = customerMap.get(name);
        customer.count++;
        customer.total_loan += parseFloat(inst.TotalAmount) || 0;
        customer.total_paid += parseFloat(inst.PaidAmount) || 0;
        customer.remaining += parseFloat(inst.RemainingBalance) || 0;
        customer.statuses.push(inst.Status);
    });
    
    const customers = Array.from(customerMap.values());
    
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No customer data available<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = customers.map(customer => {
        let statusClass = 'badge-active';
        let statusText = 'ACTIVE';
        if (customer.remaining <= 0.01) {
            statusClass = 'badge-completed';
            statusText = 'PAID';
        } else if (customer.statuses.includes('overdue')) {
            statusClass = 'badge-overdue';
            statusText = 'OVERDUE';
        } else if (customer.statuses.includes('returned')) {
            statusClass = 'badge-returned';
            statusText = 'RETURNED';
        }
        
        return `
            <tr>
                <td><strong>${escapeHtml(customer.customer_name)}</strong><\/td>
                <td>${escapeHtml(customer.phone)}<\/td>
                <td>${customer.count}<\/td>
                <td>₱${formatNumber(customer.total_loan)}<\/td>
                <td>₱${formatNumber(customer.total_paid)}<\/td>
                <td>₱${formatNumber(customer.remaining)}<\/td>
                <td><span class="${statusClass}">${statusText}</span><\/td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('reportTitle').innerHTML = '<i class="fas fa-users"></i> Per Customer Report';
}

function displayPaymentStatusTab() {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Status</th>
            <th>Count</th>
            <th>Total Loan Amount</th>
            <th>Total Paid</th>
            <th>Remaining Balance</th>
            <th>Percentage</th>
        </tr>
    `;
    
    const statusGroups = {
        active: { count: 0, total_loan: 0, total_paid: 0, remaining: 0 },
        completed: { count: 0, total_loan: 0, total_paid: 0, remaining: 0 },
        overdue: { count: 0, total_loan: 0, total_paid: 0, remaining: 0 },
        returned: { count: 0, total_loan: 0, total_paid: 0, remaining: 0 }
    };
    
    filteredInstallments.forEach(inst => {
        const status = inst.Status === 'completed' ? 'completed' : 
                      (inst.Status === 'overdue' ? 'overdue' : 
                      (inst.Status === 'returned' ? 'returned' : 'active'));
        
        if (statusGroups[status]) {
            statusGroups[status].count++;
            statusGroups[status].total_loan += parseFloat(inst.TotalAmount) || 0;
            statusGroups[status].total_paid += parseFloat(inst.PaidAmount) || 0;
            statusGroups[status].remaining += parseFloat(inst.RemainingBalance) || 0;
        }
    });
    
    const totalAll = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.TotalAmount) || 0), 0);
    
    const statusDisplay = [
        { key: 'active', label: 'ACTIVE', class: 'badge-active', icon: 'fa-clock' },
        { key: 'overdue', label: 'OVERDUE', class: 'badge-overdue', icon: 'fa-exclamation-triangle' },
        { key: 'completed', label: 'PAID / COMPLETED', class: 'badge-completed', icon: 'fa-check-circle' },
        { key: 'returned', label: 'RETURNED', class: 'badge-returned', icon: 'fa-undo-alt' }
    ];
    
    if (filteredInstallments.length === 0) {
        tbody.innerHTML = '<td><td colspan="6" class="text-center text-muted">No payment status data available<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = statusDisplay.map(s => {
        const data = statusGroups[s.key];
        const percentage = totalAll > 0 ? ((data.total_loan / totalAll) * 100).toFixed(2) : 0;
        return `
            <tr>
                <td><span class="${s.class}"><i class="fas ${s.icon}"></i> ${s.label}</span><\/td>
                <td>${data.count}<\/td>
                <td>₱${formatNumber(data.total_loan)}<\/td>
                <td>₱${formatNumber(data.total_paid)}<\/td>
                <td>₱${formatNumber(data.remaining)}<\/td>
                <td><strong>${percentage}%</strong><\/td>
            </tr>
        `;
    }).join('');
    
    // Add summary row
    const totalPaidAll = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.PaidAmount) || 0), 0);
    const totalRemainingAll = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.RemainingBalance) || 0), 0);
    
    tbody.innerHTML += `
        <tr style="background: #f1f5f9; font-weight: bold;">
            <td>TOTAL<\/td>
            <td>${filteredInstallments.length}<\/td>
            <td>₱${formatNumber(totalAll)}<\/td>
            <td>₱${formatNumber(totalPaidAll)}<\/td>
            <td>₱${formatNumber(totalRemainingAll)}<\/td>
            <td>100%<\/td>
        </tr>
    `;
    
    document.getElementById('reportTitle').innerHTML = '<i class="fas fa-chart-bar"></i> Payment Status Report';
}

function displayDetailsTab() {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Receipt No</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Start Date</th>
            <th>Total Amount</th>
            <th>Monthly</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Next Due</th>
        </tr>
    `;
    
    if (filteredInstallments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No installment details available<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = filteredInstallments.map(inst => {
        let statusClass = 'badge-active';
        let statusText = 'ACTIVE';
        if (inst.Status === 'completed') {
            statusClass = 'badge-completed';
            statusText = 'PAID';
        } else if (inst.Status === 'overdue') {
            statusClass = 'badge-overdue';
            statusText = 'OVERDUE';
        } else if (inst.Status === 'returned') {
            statusClass = 'badge-returned';
            statusText = 'RETURNED';
        }
        
        return `
            <tr>
                <td><strong>${inst.InstallmentNo || 'N/A'}</strong><\/td>
                <td>${escapeHtml(inst.CustomerName)}<\/td>
                <td>${escapeHtml(inst.ProductName || 'Multiple Items')}<\/td>
                <td>${inst.StartDate || '-'}<\/td>
                <td>₱${formatNumber(inst.TotalAmount)}<\/td>
                <td>₱${formatNumber(inst.MonthlyPayment)}<\/td>
                <td>₱${formatNumber(inst.PaidAmount)}<\/td>
                <td>₱${formatNumber(inst.RemainingBalance)}<\/td>
                <td><span class="${statusClass}">${statusText}</span><\/td>
                <td>${inst.NextPaymentDate || '-'}<\/td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('reportTitle').innerHTML = '<i class="fas fa-list"></i> Complete Installment Details';
}

// ============================================
// TAB SWITCHING
// ============================================

function switchTab(tab) {
    currentTab = tab;
    
    // Update active tab styling
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-tab') === tab) {
            btn.classList.add('active');
        }
    });
    
    // Display the selected tab
    if (tab === 'summary') {
        displaySummaryTab();
    } else if (tab === 'customer') {
        displayCustomerTab();
    } else if (tab === 'payment-status') {
        displayPaymentStatusTab();
    } else if (tab === 'details') {
        displayDetailsTab();
    }
}

// ============================================
// LOAD REPORT
// ============================================

function loadReport() {
    document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="10" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>';
    loadInstallments();
}

// ============================================
// EXPORT FUNCTION
// ============================================

function exportReport() {
    if (filteredInstallments.length === 0) {
        showToast('No data to export', 'warning');
        return;
    }
    
    let csvRows = [];
    
    if (currentTab === 'summary') {
        const totalLoan = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.TotalAmount) || 0), 0);
        const totalPaid = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.PaidAmount) || 0), 0);
        const totalRemaining = filteredInstallments.reduce((sum, i) => sum + (parseFloat(i.RemainingBalance) || 0), 0);
        const activeCount = filteredInstallments.filter(i => i.Status === 'active').length;
        const completedCount = filteredInstallments.filter(i => i.Status === 'completed').length;
        const overdueCount = filteredInstallments.filter(i => i.Status === 'overdue').length;
        const returnedCount = filteredInstallments.filter(i => i.Status === 'returned').length;
        
        csvRows = [
            ['Metric', 'Value'],
            ['Total Loan Amount', formatNumber(totalLoan)],
            ['Total Paid Amount', formatNumber(totalPaid)],
            ['Total Remaining Balance', formatNumber(totalRemaining)],
            ['Collection Rate', totalLoan > 0 ? ((totalPaid / totalLoan) * 100).toFixed(2) + '%' : '0%'],
            ['Active Installments', activeCount],
            ['Overdue Installments', overdueCount],
            ['Completed Installments', completedCount],
            ['Returned Installments', returnedCount],
            ['Total Installments', filteredInstallments.length]
        ];
    } 
    else if (currentTab === 'customer') {
        const customerMap = new Map();
        filteredInstallments.forEach(inst => {
            const name = inst.CustomerName;
            if (!customerMap.has(name)) {
                customerMap.set(name, { 
                    name: name, 
                    phone: inst.CustomerPhone || '-', 
                    count: 0, 
                    total: 0, 
                    paid: 0 
                });
            }
            const c = customerMap.get(name);
            c.count++;
            c.total += parseFloat(inst.TotalAmount) || 0;
            c.paid += parseFloat(inst.PaidAmount) || 0;
        });
        
        csvRows = [['Customer Name', 'Phone', 'Installments', 'Total Loan', 'Total Paid', 'Remaining', 'Collection Rate']];
        customerMap.forEach(c => {
            const remaining = c.total - c.paid;
            const rate = c.total > 0 ? ((c.paid / c.total) * 100).toFixed(2) : 0;
            csvRows.push([
                c.name, c.phone, c.count, 
                formatNumber(c.total), 
                formatNumber(c.paid), 
                formatNumber(remaining),
                rate + '%'
            ]);
        });
    } 
    else if (currentTab === 'payment-status') {
        const statusGroups = { active: 0, completed: 0, overdue: 0, returned: 0 };
        const statusTotals = { active: 0, completed: 0, overdue: 0, returned: 0 };
        
        filteredInstallments.forEach(inst => {
            let status = inst.Status === 'completed' ? 'completed' : 
                        (inst.Status === 'overdue' ? 'overdue' : 
                        (inst.Status === 'returned' ? 'returned' : 'active'));
            statusGroups[status]++;
            statusTotals[status] += parseFloat(inst.TotalAmount) || 0;
        });
        
        csvRows = [['Status', 'Count', 'Total Loan Amount', 'Percentage']];
        const totalAll = Object.values(statusTotals).reduce((a, b) => a + b, 0);
        
        const statusList = [
            { key: 'active', label: 'ACTIVE' },
            { key: 'overdue', label: 'OVERDUE' },
            { key: 'completed', label: 'PAID / COMPLETED' },
            { key: 'returned', label: 'RETURNED' }
        ];
        
        statusList.forEach(s => {
            const percentage = totalAll > 0 ? ((statusTotals[s.key] / totalAll) * 100).toFixed(2) : 0;
            csvRows.push([s.label, statusGroups[s.key], formatNumber(statusTotals[s.key]), percentage + '%']);
        });
        
        csvRows.push(['TOTAL', filteredInstallments.length, formatNumber(totalAll), '100%']);
    } 
    else if (currentTab === 'details') {
        csvRows = [['Receipt No', 'Customer', 'Product', 'Start Date', 'Total Amount', 'Monthly Payment', 'Paid Amount', 'Remaining Balance', 'Status', 'Next Due Date']];
        
        filteredInstallments.forEach(inst => {
            let statusText = inst.Status === 'completed' ? 'PAID' : 
                            (inst.Status === 'overdue' ? 'OVERDUE' : 
                            (inst.Status === 'returned' ? 'RETURNED' : 'ACTIVE'));
            
            csvRows.push([
                inst.InstallmentNo || 'N/A',
                inst.CustomerName,
                inst.ProductName || 'Multiple Items',
                inst.StartDate || '-',
                formatNumber(inst.TotalAmount),
                formatNumber(inst.MonthlyPayment),
                formatNumber(inst.PaidAmount),
                formatNumber(inst.RemainingBalance),
                statusText,
                inst.NextPaymentDate || '-'
            ]);
        });
    }
    
    // Create CSV content
    const csvString = csvRows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
    
    // Add UTF-8 BOM for proper special character handling
    const blob = new Blob(["\uFEFF" + csvString], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `installment_report_${currentTab}_${new Date().toISOString().slice(0, 19)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showToast('Report exported successfully', 'success');
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

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        switchTab(tab);
    });
});

document.getElementById('startDate').addEventListener('change', function() {
    loadReport();
});

document.getElementById('endDate').addEventListener('change', function() {
    loadReport();
});

document.getElementById('statusFilter').addEventListener('change', function() {
    loadReport();
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadReport();
});
</script>