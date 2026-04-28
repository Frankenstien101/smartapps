<?php
// pages/sales-report.php - Sales Report Page
?>
<style>
    .report-container {
        padding: 0;
        width: 100%;
    }
    
    /* Stats Cards */
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
        font-size: 28px;
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
        font-size: 18px;
        font-weight: 800;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        margin-top: 10px;
    }
    
    .trend-up { color: #10b981; }
    .trend-down { color: #dc3545; }
    
    .badge-cash { background: #dbeafe; color: #2563eb; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; font-weight: 600; }
    .badge-card { background: #dcfce7; color: #10b981; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; font-weight: 600; }
    .badge-gcash { background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; font-weight: 600; }
    .badge-secondary { background: #e2e8f0; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; font-weight: 600; }
    
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
            <h4><i class="fas fa-chart-bar"></i> Sales Report</h4>
            <p class="text-muted mb-0">View and analyze sales performance</p>
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
            <div class="stat-value" id="totalSales">₱0</div>
            <div class="stat-label">Total Sales</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-receipt"></i></div>
            <div class="stat-value" id="totalTransactions">0</div>
            <div class="stat-label">Transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-chart-simple"></i></div>
            <div class="stat-value" id="averageSale">₱0</div>
            <div class="stat-label">Average Sale</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-trend-up"></i></div>
            <div class="stat-value" id="trendValue">0%</div>
            <div class="stat-label">vs Last Week</div>
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
                <button class="btn-primary" onclick="loadReport()" style="margin-top: 22px;">
                    <i class="fas fa-search"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs-container">
        <button class="tab-btn active" data-tab="daily">Daily Sales</button>
        <button class="tab-btn" data-tab="product">Product Sales</button>
        <button class="tab-btn" data-tab="payment">Payment Methods</button>
        <button class="tab-btn" data-tab="profit">Profit Report</button>
        <button class="tab-btn" data-tab="tax">Tax Report</button>
    </div>
    
    <!-- Report Table -->
    <div class="report-table-container">
        <div class="table-header">
            <span id="reportTitle"><i class="fas fa-chart-line"></i> Daily Sales Report</span>
            <button class="btn-refresh" onclick="loadReport()" style="background:transparent; border:none; color:#4f9eff;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead id="tableHead">
                    <tr>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Total Sales</th>
                        <th>Average</th>
                    </th>
                </thead>
                <tbody id="reportTableBody">
                    <tr><td colspan="4" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                </tbody>
            </table>
        </div>
        <div class="summary-card" id="summaryCard">
            <div class="summary-row">
                <span>Total Sales:</span>
                <span id="summaryTotal">₱0.00</span>
            </div>
            <div class="summary-row">
                <span>Total Transactions:</span>
                <span id="summaryCount">0</span>
            </div>
            <div class="summary-row total">
                <span>Grand Total:</span>
                <span id="summaryGrandTotal">₱0.00</span>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/salesreportdata.php';

let currentTab = 'daily';

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

async function loadReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    // Show loading state
    document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="10" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>';
    
    if (currentTab === 'daily') {
        await loadDailySales(startDate, endDate);
    } else if (currentTab === 'product') {
        await loadProductSales(startDate, endDate);
    } else if (currentTab === 'payment') {
        await loadPaymentReport(startDate, endDate);
    } else if (currentTab === 'profit') {
        await loadProfitReport(startDate, endDate);
    } else if (currentTab === 'tax') {
        await loadTaxReport(startDate, endDate);
    }
}

async function loadDailySales(startDate, endDate) {
    const result = await apiCall(`getSalesReport&start_date=${startDate}&end_date=${endDate}`);
    if (result.success) {
        displayDailyReport(result.data, result.summary);
        updateStatsCards(result.summary);
    } else {
        showToast('Failed to load daily sales report', 'error');
    }
}

async function loadProductSales(startDate, endDate) {
    const result = await apiCall(`getProductSalesReport&start_date=${startDate}&end_date=${endDate}`);
    if (result.success) {
        displayProductReport(result.data, result.total_revenue);
        // Update stats cards with product report data
        document.getElementById('totalSales').innerHTML = `₱${formatNumber(result.total_revenue)}`;
        document.getElementById('totalTransactions').innerHTML = result.data.length;
        document.getElementById('averageSale').innerHTML = `₱${formatNumber(result.data.length > 0 ? result.total_revenue / result.data.length : 0)}`;
    } else {
        showToast('Failed to load product sales report', 'error');
    }
}

async function loadPaymentReport(startDate, endDate) {
    const result = await apiCall(`getPaymentMethodReport&start_date=${startDate}&end_date=${endDate}`);
    if (result.success) {
        displayPaymentReport(result.data);
        // Update stats cards with summary data
        if (result.summary) {
            document.getElementById('totalSales').innerHTML = `₱${formatNumber(result.summary.total_sales)}`;
            document.getElementById('totalTransactions').innerHTML = result.summary.total_transactions?.toLocaleString() || '0';
            document.getElementById('averageSale').innerHTML = `₱${formatNumber(result.summary.average_transaction)}`;
        }
    } else {
        showToast('Failed to load payment report', 'error');
    }
}

async function loadProfitReport(startDate, endDate) {
    const result = await apiCall(`getProfitReport&start_date=${startDate}&end_date=${endDate}`);
    if (result.success && result.data) {
        displayProfitReport(result.data);
        // Update stats cards with profit data
        document.getElementById('totalSales').innerHTML = `₱${formatNumber(result.data.total_revenue)}`;
        document.getElementById('totalTransactions').innerHTML = result.data.breakdown?.length || 0;
        document.getElementById('averageSale').innerHTML = `₱${formatNumber(result.data.gross_profit)}`;
    } else {
        showToast('Failed to load profit report', 'error');
    }
}

async function loadTaxReport(startDate, endDate) {
    const result = await apiCall(`getTaxReport&start_date=${startDate}&end_date=${endDate}`);
    if (result.success) {
        displayTaxReport(result.data, result.summary);
        // Update stats cards with tax data
        if (result.summary) {
            document.getElementById('totalSales').innerHTML = `₱${formatNumber(result.summary.total_sales)}`;
            document.getElementById('totalTransactions').innerHTML = result.data?.length || 0;
            document.getElementById('averageSale').innerHTML = `₱${formatNumber(result.summary.total_vat)}`;
        }
    } else {
        showToast('Failed to load tax report', 'error');
    }
}

async function loadTrend() {
    const result = await apiCall('getDailySales&days=14');
    if (result.success && result.trend) {
        const trend = result.trend.percentage_change;
        const trendElement = document.getElementById('trendValue');
        const trendIcon = trend >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        const trendColor = trend >= 0 ? 'trend-up' : 'trend-down';
        trendElement.innerHTML = `<i class="fas ${trendIcon} ${trendColor}"></i> ${Math.abs(trend)}%`;
    }
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displayDailyReport(data, summary) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Date</th>
            <th>Transactions</th>
            <th>Total Sales</th>
            <th>Average</th>
        </th>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No data available for selected period<\/td><\/tr>';
        updateSummaryCard(0, 0, 0);
        return;
    }
    
    let totalSales = 0;
    let totalTransactions = 0;
    
    tbody.innerHTML = data.map(row => {
        const sales = parseFloat(row.TotalSales) || 0;
        const transactions = parseInt(row.TransactionCount) || 0;
        totalSales += sales;
        totalTransactions += transactions;
        
        return `
            <tr>
                <td>${row.Date || '-'}<\/td>
                <td>${transactions.toLocaleString()}<\/td>
                <td>₱${formatNumber(sales)}<\/td>
                <td>₱${formatNumber(row.AverageTransaction || row.AverageSale)}<\/td>
            </tr>
        `;
    }).join('');
    
    if (summary) {
        updateSummaryCard(summary.TotalSales, summary.TotalTransactions, summary.AverageSale);
    } else {
        const avg = totalTransactions > 0 ? totalSales / totalTransactions : 0;
        updateSummaryCard(totalSales, totalTransactions, avg);
    }
}

function displayProductReport(data, totalRevenue) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Product Code</th>
            <th>Product Name</th>
            <th>Quantity Sold</th>
            <th>Number of Sales</th>
            <th>Average Price</th>
            <th>Total Revenue</th>
        </th>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No product sales data available<\/td><\/tr>';
        updateSummaryCard(0, 0, 0);
        return;
    }
    
    let totalQty = 0;
    tbody.innerHTML = data.map(row => {
        totalQty += parseInt(row.TotalQuantity) || 0;
        return `
            <tr>
                <td>${row.ProductCode || '-'}<\/td>
                <td>${escapeHtml(row.ProductName)}<\/td>
                <td>${(row.TotalQuantity || 0).toLocaleString()}<\/td>
                <td>${row.NumberOfSales || 0}<\/td>
                <td>₱${formatNumber(row.AveragePrice)}<\/td>
                <td>₱${formatNumber(row.TotalRevenue)}<\/td>
            </tr>
        `;
    }).join('');
    
    updateSummaryCard(totalRevenue, totalQty, data.length > 0 ? totalRevenue / data.length : 0);
}

function displayPaymentReport(data) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Payment Method</th>
            <th>Transactions</th>
            <th>Total Amount</th>
            <th>Average Amount</th>
            <th>Percentage</th>
        </th>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No payment data available for selected period<\/td><\/tr>';
        updateSummaryCard(0, 0, 0);
        return;
    }
    
    let totalAmount = 0;
    let totalTransactions = 0;
    
    tbody.innerHTML = data.map(row => {
        const amount = parseFloat(row.TotalAmount) || 0;
        const transactions = parseInt(row.TransactionCount) || 0;
        totalAmount += amount;
        totalTransactions += transactions;
        
        let badgeClass = '';
        let displayName = '';
        if (row.PaymentMethod === 'cash') {
            badgeClass = 'badge-cash';
            displayName = 'CASH';
        } else if (row.PaymentMethod === 'card') {
            badgeClass = 'badge-card';
            displayName = 'CARD';
        } else if (row.PaymentMethod === 'gcash') {
            badgeClass = 'badge-gcash';
            displayName = 'GCASH';
        } else {
            badgeClass = 'badge-secondary';
            displayName = row.PaymentMethod?.toUpperCase() || 'OTHER';
        }
        
        return `
            <tr>
                <td><span class="${badgeClass}">${displayName}</span><\/td>
                <td>${transactions.toLocaleString()}<\/td>
                <td>₱${formatNumber(amount)}<\/td>
                <td>₱${formatNumber(row.AverageAmount)}<\/td>
                <td><strong>${(row.Percentage || 0).toFixed(2)}%</strong><\/td>
            </tr>
        `;
    }).join('');
    
    updateSummaryCard(totalAmount, totalTransactions, totalTransactions > 0 ? totalAmount / totalTransactions : 0);
}

function displayProfitReport(data) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Product</th>
            <th>Quantity Sold</th>
            <th>Revenue</th>
            <th>Cost</th>
            <th>Profit</th>
            <th>Margin</th>
        </th>
    `;
    
    if (!data.breakdown || data.breakdown.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No profit data available for selected period<\/td><\/tr>';
        updateSummaryCard(0, 0, 0);
        return;
    }
    
    tbody.innerHTML = data.breakdown.map(row => {
        const profit = row.Profit || 0;
        const margin = row.Margin || 0;
        let marginClass = 'text-secondary';
        if (margin >= 20) marginClass = 'text-success';
        else if (margin >= 10) marginClass = 'text-warning';
        else if (margin > 0) marginClass = 'text-info';
        else if (margin < 0) marginClass = 'text-danger';
        
        return `
            <tr>
                <td>${escapeHtml(row.ProductName)}<\/td>
                <td>${row.QuantitySold || 0}<\/td>
                <td>₱${formatNumber(row.TotalRevenue)}<\/td>
                <td>₱${formatNumber(row.TotalCost)}<\/td>
                <td>₱${formatNumber(profit)}<\/td>
                <td><span class="${marginClass} fw-bold">${margin.toFixed(2)}%</span><\/td>
            </tr>
        `;
    }).join('');
    
    updateSummaryCard(data.total_revenue, data.breakdown.length, data.profit_margin);
}

function displayTaxReport(data, summary) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Date</th>
            <th>Transactions</th>
            <th>Total Sales (VAT Inclusive)</th>
            <th>VAT Amount (12%)</th>
        </th>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No tax data available<\/td><\/tr>';
        updateSummaryCard(0, 0, 0);
        return;
    }
    
    let totalSales = 0;
    let totalVat = 0;
    
    tbody.innerHTML = data.map(row => {
        totalSales += parseFloat(row.TotalSales) || 0;
        totalVat += parseFloat(row.VatAmount) || 0;
        return `
            <tr>
                <td>${row.Date || '-'}<\/td>
                <td>${row.TransactionCount || 0}<\/td>
                <td>₱${formatNumber(row.TotalSales)}<\/td>
                <td>₱${formatNumber(row.VatAmount)}<\/td>
            </tr>
        `;
    }).join('');
    
    if (summary) {
        updateSummaryCard(summary.total_sales, data.length, summary.total_vat);
    } else {
        updateSummaryCard(totalSales, data.length, totalVat);
    }
}

// ============================================
// SUMMARY AND STATS UPDATES
// ============================================

function updateSummaryCard(totalSales, totalTransactions, averageValue) {
    document.getElementById('summaryTotal').innerHTML = `₱${formatNumber(totalSales)}`;
    document.getElementById('summaryCount').innerHTML = totalTransactions.toLocaleString();
    document.getElementById('summaryGrandTotal').innerHTML = `₱${formatNumber(totalSales)}`;
    
    // Add/Update average row
    let avgRow = document.getElementById('avgTransactionRow');
    if (!avgRow) {
        const summaryCard = document.getElementById('summaryCard');
        const newRow = document.createElement('div');
        newRow.id = 'avgTransactionRow';
        newRow.className = 'summary-row';
        newRow.innerHTML = `
            <span>Average Value:</span>
            <span id="avgTransaction">₱0.00</span>
        `;
        const grandTotalRow = summaryCard.querySelector('.summary-row.total');
        if (grandTotalRow) {
            summaryCard.insertBefore(newRow, grandTotalRow);
        } else {
            summaryCard.appendChild(newRow);
        }
        avgRow = newRow;
    }
    document.getElementById('avgTransaction').innerHTML = `₱${formatNumber(averageValue)}`;
}

function updateStatsCards(summary) {
    if (summary) {
        document.getElementById('totalSales').innerHTML = `₱${formatNumber(summary.TotalSales)}`;
        document.getElementById('totalTransactions').innerHTML = (summary.TotalTransactions || 0).toLocaleString();
        document.getElementById('averageSale').innerHTML = `₱${formatNumber(summary.AverageSale)}`;
    }
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
    
    // Update report title
    const titles = {
        'daily': 'Daily Sales Report',
        'product': 'Product Sales Report',
        'payment': 'Payment Methods Report',
        'profit': 'Profit Report',
        'tax': 'Tax Report'
    };
    
    document.getElementById('reportTitle').innerHTML = `<i class="fas fa-chart-line"></i> ${titles[tab]}`;
    
    // Load the report
    loadReport();
}

// ============================================
// EXPORT FUNCTION
// ============================================

function exportReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    let type = 'sales';
    
    if (currentTab === 'product') {
        type = 'products';
    } else if (currentTab === 'payment') {
        type = 'payment';
    }
    
    window.open(`${API_URL}?action=exportReport&type=${type}&start_date=${startDate}&end_date=${endDate}`);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatNumber(value) {
    if (value === null || value === undefined || isNaN(value)) return '0.00';
    const num = parseFloat(value);
    return num.toLocaleString('en-PH', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
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

// Add click event listeners to tab buttons
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        switchTab(tab);
    });
});

// Add event listener for date filter changes
document.getElementById('startDate').addEventListener('change', function() {
    loadReport();
});

document.getElementById('endDate').addEventListener('change', function() {
    loadReport();
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadReport();
    loadTrend();
});
</script>