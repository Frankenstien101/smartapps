<?php
// pages/admin_reports.php - Admin Reports Dashboard
// Only accessible by admin users
?>
<style>
    .admin-reports-container {
        padding: 0;
        width: 100%;
    }
    
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
    .stat-icon.danger { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    
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
    
    .filter-row {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    
    .filter-group {
        flex: 1;
        min-width: 180px;
    }
    
    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 5px;
        display: block;
    }
    
    .filter-group select, .filter-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
    }
    
    .btn-apply {
        background: #4f9eff;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-export {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .report-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 10px;
    }
    
    .tab-btn {
        padding: 10px 24px;
        background: transparent;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .tab-btn.active {
        background: #4f9eff;
        color: white;
    }
    
    .tab-btn:hover:not(.active) {
        background: #eef2ff;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .report-table-container {
        background: white;
        border-radius: 16px;
        overflow-x: auto;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .report-table {
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
    }
    
    .report-table th {
        background: #f8fafc;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .report-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
    }
    
    .report-table tr:hover td {
        background: #f8fafc;
    }
    
    .chart-container {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .chart-container h5 {
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    canvas {
        max-height: 300px;
        width: 100%;
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
    
    .text-right {
        text-align: right;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .stat-value {
            font-size: 20px;
        }
        
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="admin-reports-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-chart-line"></i> Admin Reports Dashboard</h4>
            <p class="text-muted mb-0">View and analyze reports across all branches</p>
        </div>
        <button class="btn-export" onclick="exportReport()">
            <i class="fas fa-download"></i> Export Report
        </button>
    </div>
    
    <!-- Filter Card -->
    <div class="filter-card">
        <div class="filter-row">
            <div class="filter-group">
                <label><i class="fas fa-store"></i> Select Branch</label>
                <select id="branchSelect">
                    <option value="all">All Branches</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> Start Date</label>
                <input type="date" id="startDate">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> End Date</label>
                <input type="date" id="endDate">
            </div>
            <div class="filter-group">
                <button class="btn-apply" onclick="applyFilters()">
                    <i class="fas fa-sync-alt"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stats Overview -->
    <div class="stats-row" id="statsOverview">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalSales">₱0</div>
            <div class="stat-label">Total Sales</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-value" id="transactionCount">0</div>
            <div class="stat-label">Transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="stat-value" id="totalLoanAmount">₱0</div>
            <div class="stat-label">Total Loans</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-boxes"></i></div>
            <div class="stat-value" id="totalStockValue">₱0</div>
            <div class="stat-label">Stock Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-clock"></i></div>
            <div class="stat-value" id="totalActivities">0</div>
            <div class="stat-label">User Activities</div>
        </div>
    </div>
    
    <!-- Report Tabs -->
    <div class="report-tabs">
        <button class="tab-btn active" onclick="switchTab('sales')">Sales Report</button>
        <button class="tab-btn" onclick="switchTab('installments')">Installment Report</button>
        <button class="tab-btn" onclick="switchTab('stock')">Stock Report</button>
        <button class="tab-btn" onclick="switchTab('activities')">User Activity</button>
    </div>
    
    <!-- Sales Report Tab -->
    <div id="salesTab" class="tab-content active">
        <div class="chart-container">
            <h5><i class="fas fa-chart-line"></i> Daily Sales Trend</h5>
            <canvas id="salesChart" style="max-height: 300px;"></canvas>
        </div>
        
        <div class="stats-row" id="salesStats">
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value" id="avgTransaction">₱0</div>
                <div class="stat-label">Average Transaction</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-credit-card"></i></div>
                <div class="stat-value" id="cashSales">₱0</div>
                <div class="stat-label">Cash Sales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fas fa-mobile-alt"></i></div>
                <div class="stat-value" id="gcashSales">₱0</div>
                <div class="stat-label">GCash Sales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-chart-simple"></i></div>
                <div class="stat-value" id="cardSales">₱0</div>
                <div class="stat-label">Card Sales</div>
            </div>
        </div>
        
        <div class="chart-container">
            <h5><i class="fas fa-crown"></i> Top Selling Products</h5>
            <canvas id="topProductsChart" style="max-height: 300px;"></canvas>
        </div>
        
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Total Sales</th>
                        <th>Average</th>
                    </tr>
                </thead>
                <tbody id="dailySalesTable"></tbody>
            </table>
        </div>
    </div>
    
    <!-- Installment Report Tab -->
    <div id="installmentsTab" class="tab-content">
        <div class="chart-container">
            <h5><i class="fas fa-chart-bar"></i> Monthly Installment Trends</h5>
            <canvas id="installmentChart" style="max-height: 300px;"></canvas>
        </div>
        
        <div class="stats-row" id="installmentStats">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-credit-card"></i></div>
                <div class="stat-value" id="totalLoan">₱0</div>
                <div class="stat-label">Total Loan Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value" id="totalPaid">₱0</div>
                <div class="stat-label">Amount Paid</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-value" id="totalRemaining">₱0</div>
                <div class="stat-label">Remaining Balance</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value" id="collectionRate">0%</div>
                <div class="stat-label">Collection Rate</div>
            </div>
        </div>
        
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Installments</th>
                        <th>Loan Amount</th>
                        <th>Amount Paid</th>
                        <th>Collection Rate</th>
                    </tr>
                </thead>
                <tbody id="monthlyInstallmentTable"></tbody>
            </table>
        </div>
    </div>
    
    <!-- Stock Report Tab -->
    <div id="stockTab" class="tab-content">
        <div class="stats-row" id="stockStats">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-box"></i></div>
                <div class="stat-value" id="totalProducts">0</div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-boxes"></i></div>
                <div class="stat-value" id="totalUnits">0</div>
                <div class="stat-label">Total Units</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value" id="lowStockItems">0</div>
                <div class="stat-label">Low Stock Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value" id="sellingValue">₱0</div>
                <div class="stat-label">Selling Value</div>
            </div>
        </div>
        
        <div class="chart-container">
            <h5><i class="fas fa-chart-pie"></i> Stock by Category</h5>
            <canvas id="categoryChart" style="max-height: 300px;"></canvas>
        </div>
        
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Stock</th>
                        <th>Cost Price</th>
                        <th>Selling Price</th>
                        <th>Total Value</th>
                    </tr>
                </thead>
                <tbody id="stockTable"></tbody>
            </table>
        </div>
    </div>
    
    <!-- User Activity Tab -->
    <div id="activitiesTab" class="tab-content">
        <div class="stats-row" id="activityStats">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value" id="totalActivitiesCount">0</div>
                <div class="stat-label">Total Activities</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value" id="successActivities">0</div>
                <div class="stat-label">Successful</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value" id="failedActivities">0</div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-users"></i></div>
                <div class="stat-value" id="uniqueUsersCount">0</div>
                <div class="stat-label">Unique Users</div>
            </div>
        </div>
        
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Branch</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="activityTable"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// API Configuration
const API_URL = '/POS/datafetcher/admindata.php';

let salesChart = null;
let topProductsChart = null;
let installmentChart = null;
let categoryChart = null;

// ============================================
// API CALLS
// ============================================

async function apiCall(action, params = {}) {
    try {
        let url = `${API_URL}?action=${action}`;
        for (let key in params) {
            url += `&${key}=${encodeURIComponent(params[key])}`;
        }
        const response = await fetch(url);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false };
    }
}

async function loadBranches() {
    const result = await apiCall('getBranches');
    if (result.success && result.data) {
        const select = document.getElementById('branchSelect');
        result.data.forEach(branch => {
            select.innerHTML += `<option value="${branch}">${branch}</option>`;
        });
    }
}

async function loadDashboardSummary() {
    const branch = document.getElementById('branchSelect').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    const result = await apiCall('getDashboardSummary', { branch, start_date: startDate, end_date: endDate });
    if (result.success) {
        document.getElementById('totalSales').innerHTML = '₱' + formatNumber(result.sales?.TotalSales || 0);
        document.getElementById('transactionCount').innerText = result.sales?.TransactionCount || 0;
        document.getElementById('totalLoanAmount').innerHTML = '₱' + formatNumber(result.installments?.TotalLoanAmount || 0);
        document.getElementById('totalStockValue').innerHTML = '₱' + formatNumber(result.stock?.TotalStockValue || 0);
        document.getElementById('totalActivities').innerText = result.activities?.TotalActivities || 0;
    }
}

async function loadSalesReport() {
    const branch = document.getElementById('branchSelect').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    const result = await apiCall('getSalesReport', { branch, start_date: startDate, end_date: endDate });
    if (result.success) {
        // Update stats
        document.getElementById('avgTransaction').innerHTML = '₱' + formatNumber(result.summary?.AverageTransaction || 0);
        
        // Payment methods
        let cashSales = 0, gcashSales = 0, cardSales = 0;
        (result.payment_methods || []).forEach(m => {
            if (m.PaymentMethod === 'cash') cashSales = m.Amount;
            else if (m.PaymentMethod === 'gcash') gcashSales = m.Amount;
            else if (m.PaymentMethod === 'card') cardSales = m.Amount;
        });
        document.getElementById('cashSales').innerHTML = '₱' + formatNumber(cashSales);
        document.getElementById('gcashSales').innerHTML = '₱' + formatNumber(gcashSales);
        document.getElementById('cardSales').innerHTML = '₱' + formatNumber(cardSales);
        
        // Daily sales table
        const dailyTbody = document.getElementById('dailySalesTable');
        if (result.daily_breakdown && result.daily_breakdown.length > 0) {
            dailyTbody.innerHTML = result.daily_breakdown.map(day => `
                <tr>
                    <td>${day.Date}</td>
                    <td>${day.TransactionCount}</td>
                    <td>₱${formatNumber(day.TotalSales)}</td>
                    <td>₱${formatNumber(day.TotalSales / day.TransactionCount)}</td>
                </tr>
            `).join('');
        } else {
            dailyTbody.innerHTML = '<tr><td colspan="4" class="text-center">No data available</td></tr>';
        }
        
        // Update sales chart
        updateSalesChart(result.daily_breakdown || []);
        
        // Update top products chart
        updateTopProductsChart(result.top_products || []);
    }
}

async function loadInstallmentReport() {
    const branch = document.getElementById('branchSelect').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    const result = await apiCall('getInstallmentReport', { branch, start_date: startDate, end_date: endDate });
    if (result.success) {
        document.getElementById('totalLoan').innerHTML = '₱' + formatNumber(result.summary?.TotalLoanAmount || 0);
        document.getElementById('totalPaid').innerHTML = '₱' + formatNumber(result.summary?.TotalPaidAmount || 0);
        document.getElementById('totalRemaining').innerHTML = '₱' + formatNumber(result.summary?.TotalRemainingBalance || 0);
        
        const collectionRate = result.summary?.TotalLoanAmount > 0 
            ? ((result.summary.TotalPaidAmount / result.summary.TotalLoanAmount) * 100).toFixed(1)
            : 0;
        document.getElementById('collectionRate').innerHTML = collectionRate + '%';
        
        // Monthly table
        const monthlyTbody = document.getElementById('monthlyInstallmentTable');
        if (result.monthly_breakdown && result.monthly_breakdown.length > 0) {
            monthlyTbody.innerHTML = result.monthly_breakdown.map(month => {
                const rate = month.TotalLoanAmount > 0 ? ((month.TotalPaidAmount / month.TotalLoanAmount) * 100).toFixed(1) : 0;
                return `
                    <tr>
                        <td>${month.Month}</td>
                        <td>${month.InstallmentCount}</td>
                        <td>₱${formatNumber(month.TotalLoanAmount)}</td>
                        <td>₱${formatNumber(month.TotalPaidAmount)}</td>
                        <td>${rate}%</td>
                    </tr>
                `;
            }).join('');
        } else {
            monthlyTbody.innerHTML = '<tr><td colspan="5" class="text-center">No data available</td></tr>';
        }
        
        updateInstallmentChart(result.monthly_breakdown || []);
    }
}

async function loadStockReport() {
    const branch = document.getElementById('branchSelect').value;
    
    const result = await apiCall('getStockReport', { branch });
    if (result.success) {
        document.getElementById('totalProducts').innerText = result.summary?.total_products || 0;
        document.getElementById('totalUnits').innerText = result.summary?.total_units || 0;
        document.getElementById('lowStockItems').innerText = result.summary?.low_stock_count || 0;
        document.getElementById('sellingValue').innerHTML = '₱' + formatNumber(result.summary?.total_selling_value || 0);
        
        // Stock table
        const stockTbody = document.getElementById('stockTable');
        if (result.products && result.products.length > 0) {
            stockTbody.innerHTML = result.products.map(product => `
                <tr>
                    <td>${product.ProductCode || '-'}</td>
                    <td>${escapeHtml(product.ProductName)}</td>
                    <td>${product.Category || '-'}</td>
                    <td>${product.Brand || '-'}</td>
                    <td>${product.CurrentStock}</td>
                    <td>₱${formatNumber(product.CostPrice)}</td>
                    <td>₱${formatNumber(product.SellingPrice)}</td>
                    <td>₱${formatNumber(product.TotalCostValue)}</td>
                </tr>
            `).join('');
        } else {
            stockTbody.innerHTML = '<tr><td colspan="8" class="text-center">No data available</td></tr>';
        }
        
        updateCategoryChart(result.category_breakdown || []);
    }
}

async function loadActivityReport() {
    const branch = document.getElementById('branchSelect').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    const result = await apiCall('getUserActivityReport', { branch, start_date: startDate, end_date: endDate });
    if (result.success) {
        document.getElementById('totalActivitiesCount').innerText = result.summary?.TotalActivities || 0;
        document.getElementById('successActivities').innerText = result.summary?.SuccessCount || 0;
        document.getElementById('failedActivities').innerText = result.summary?.FailedCount || 0;
        document.getElementById('uniqueUsersCount').innerText = result.summary?.UniqueUsers || 0;
        
        const activityTbody = document.getElementById('activityTable');
        if (result.activities && result.activities.length > 0) {
            activityTbody.innerHTML = result.activities.map(activity => `
                <tr>
                    <td><small>${activity.ActivityDate || '-'}</small></td>
                    <td>${escapeHtml(activity.Username)}</td>
                    <td>${activity.Branch || '-'}</td>
                    <td><span class="badge bg-secondary">${activity.Action || '-'}</span></td>
                    <td>${activity.Module || '-'}</td>
                    <td><small>${escapeHtml(activity.Description || '-')}</small></td>
                    <td><span class="badge ${activity.Status === 'success' ? 'bg-success' : 'bg-danger'}">${activity.Status || '-'}</span></td>
                </tr>
            `).join('');
        } else {
            activityTbody.innerHTML = '<tr><td colspan="7" class="text-center">No data available</td></tr>';
        }
    }
}

// ============================================
// CHART FUNCTIONS
// ============================================

function updateSalesChart(data) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    if (salesChart) salesChart.destroy();
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.Date),
            datasets: [{
                label: 'Daily Sales (₱)',
                data: data.map(d => d.TotalSales),
                borderColor: '#4f9eff',
                backgroundColor: 'rgba(79, 158, 255, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
}

function updateTopProductsChart(data) {
    const ctx = document.getElementById('topProductsChart').getContext('2d');
    if (topProductsChart) topProductsChart.destroy();
    
    topProductsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.ProductName.length > 20 ? d.ProductName.substring(0,20)+'...' : d.ProductName),
            datasets: [{
                label: 'Revenue (₱)',
                data: data.map(d => d.TotalAmount),
                backgroundColor: '#f59e0b',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
}

function updateInstallmentChart(data) {
    const ctx = document.getElementById('installmentChart').getContext('2d');
    if (installmentChart) installmentChart.destroy();
    
    installmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.Month),
            datasets: [
                {
                    label: 'Loan Amount (₱)',
                    data: data.map(d => d.TotalLoanAmount),
                    backgroundColor: '#4f9eff',
                    borderRadius: 8
                },
                {
                    label: 'Paid Amount (₱)',
                    data: data.map(d => d.TotalPaidAmount),
                    backgroundColor: '#28a745',
                    borderRadius: 8
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
}

function updateCategoryChart(data) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    if (categoryChart) categoryChart.destroy();
    
    categoryChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(d => d.Category || 'Uncategorized'),
            datasets: [{
                data: data.map(d => d.TotalValue),
                backgroundColor: ['#4f9eff', '#28a745', '#f59e0b', '#dc3545', '#8b5cf6', '#06b6d4', '#ec4899']
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    if (tab === 'sales') {
        document.querySelector('.tab-btn').classList.add('active');
        document.getElementById('salesTab').classList.add('active');
        loadSalesReport();
    } else if (tab === 'installments') {
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
        document.getElementById('installmentsTab').classList.add('active');
        loadInstallmentReport();
    } else if (tab === 'stock') {
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
        document.getElementById('stockTab').classList.add('active');
        loadStockReport();
    } else if (tab === 'activities') {
        document.querySelectorAll('.tab-btn')[3].classList.add('active');
        document.getElementById('activitiesTab').classList.add('active');
        loadActivityReport();
    }
}

async function applyFilters() {
    await loadDashboardSummary();
    
    const activeTab = document.querySelector('.tab-content.active').id;
    if (activeTab === 'salesTab') await loadSalesReport();
    else if (activeTab === 'installmentsTab') await loadInstallmentReport();
    else if (activeTab === 'stockTab') await loadStockReport();
    else if (activeTab === 'activitiesTab') await loadActivityReport();
}

async function exportReport() {
    const branch = document.getElementById('branchSelect').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const activeTab = document.querySelector('.tab-content.active').id;
    
    let reportType = 'sales';
    if (activeTab === 'installmentsTab') reportType = 'installments';
    else if (activeTab === 'stockTab') reportType = 'stock';
    
    window.location.href = `${API_URL}?action=exportReport&report_type=${reportType}&branch=${branch}&start_date=${startDate}&end_date=${endDate}&format=csv`;
}

function setDefaultDates() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
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

function showToast(message, type) {
    // Simple alert for now, can be replaced with toast
    console.log(message, type);
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    setDefaultDates();
    loadBranches();
    loadDashboardSummary();
    loadSalesReport();
});
</script>