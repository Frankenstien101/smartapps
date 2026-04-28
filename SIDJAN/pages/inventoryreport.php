<?php
// pages/inventory-report.php - Inventory Report Page
?>
<style>
    .report-container {
        padding: 0;
        width: 100%;
    }
    
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
    
    .badge-low { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-out { background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-normal { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
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
            <h4><i class="fas fa-boxes"></i> Inventory Report</h4>
            <p class="text-muted mb-0">View and analyze inventory status</p>
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
            <div class="stat-icon primary"><i class="fas fa-box"></i></div>
            <div class="stat-value" id="totalProducts">0</div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-value" id="inventoryValue">₱0</div>
            <div class="stat-label">Inventory Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value" id="lowStockCount">0</div>
            <div class="stat-label">Low Stock</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalUnits">0</div>
            <div class="stat-label">Total Units</div>
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
                <label>Category</label>
                <select id="categoryFilter">
                    <option value="all">All Categories</option>
                    <option value="Mobile Phones">Mobile Phones</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Tablets">Tablets</option>
                    <option value="Wearables">Wearables</option>
                    <option value="Chargers">Chargers</option>
                    <option value="Cases">Cases</option>
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
        <button class="tab-btn" data-tab="lowstock">Low Stock</button>
        <button class="tab-btn" data-tab="category">By Category</button>
        <button class="tab-btn" data-tab="movement">Stock Movement</button>
        <button class="tab-btn" data-tab="products">Products</button>
    </div>
    
    <!-- Report Table -->
    <div class="report-table-container">
        <div class="table-header">
            <span id="reportTitle"><i class="fas fa-chart-pie"></i> Inventory Summary</span>
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
                <span>Out of Stock:</span>
                <span id="outOfStockCount">0</span>
            </div>
            <div class="summary-row">
                <span>Well Stocked:</span>
                <span id="wellStockedCount">0</span>
            </div>
            <div class="summary-row total">
                <span>Potential Profit:</span>
                <span id="potentialProfit">₱0</span>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/inventoryreportdata.php';

let currentTab = 'summary';
let inventoryData = [];


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

async function loadInventorySummary() {
    const result = await apiCall('getInventorySummary');
    if (result.success && result.data) {
        const data = result.data;
        // Update stats cards
        const totalProductsEl = document.getElementById('totalProducts');
        const inventoryValueEl = document.getElementById('inventoryValue');
        const lowStockCountEl = document.getElementById('lowStockCount');
        const totalUnitsEl = document.getElementById('totalUnits');
        
        if (totalProductsEl) totalProductsEl.innerHTML = data.TotalProducts || 0;
        if (inventoryValueEl) inventoryValueEl.innerHTML = '₱' + formatNumber(data.TotalInventoryValue);
        if (lowStockCountEl) lowStockCountEl.innerHTML = data.LowStockCount || 0;
        if (totalUnitsEl) totalUnitsEl.innerHTML = data.TotalUnits || 0;
        
        // Update summary card
        const outOfStockEl = document.getElementById('outOfStockCount');
        const wellStockedEl = document.getElementById('wellStockedCount');
        const potentialProfitEl = document.getElementById('potentialProfit');
        
        if (outOfStockEl) outOfStockEl.innerHTML = data.OutOfStockCount || 0;
        if (wellStockedEl) wellStockedEl.innerHTML = data.WellStockedCount || 0;
        if (potentialProfitEl) potentialProfitEl.innerHTML = '₱' + formatNumber(data.PotentialProfit);
    }
}

async function loadLowStockReport() {
    const result = await apiCall('getLowStockReport');
    if (result.success) {
        inventoryData = result.data;
        displayLowStockReport(result.data, result.summary);
    }
}

async function loadCategoryReport() {
    const result = await apiCall('getCategoryReport');
    if (result.success) {
        inventoryData = result.data;
        displayCategoryReport(result.data, result.total_value);
    }
}

async function loadStockMovementReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const result = await apiCall(`getStockMovementReport&start_date=${startDate}&end_date=${endDate}`);
    if (result.success) {
        inventoryData = result.data;
        displayStockMovementReport(result.data, result.summary);
    }
}

async function loadProductDetails() {
    const category = document.getElementById('categoryFilter').value;
    const result = await apiCall(`getProductDetails&category=${category}`);
    if (result.success) {
        inventoryData = result.data;
        displayProductDetails(result.data, result.summary);
    }
}

async function loadReport() {
    const tbody = document.getElementById('reportTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>';
    }
    
    if (currentTab === 'summary') {
        await loadInventorySummary();
        displaySummaryTab();
    } else if (currentTab === 'lowstock') {
        await loadLowStockReport();
    } else if (currentTab === 'category') {
        await loadCategoryReport();
    } else if (currentTab === 'movement') {
        await loadStockMovementReport();
    } else if (currentTab === 'products') {
        await loadProductDetails();
    }
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displaySummaryTab() {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    if (!tbody || !thead) return;
    
    thead.innerHTML = `
        <tr>
            <th>Metric</th>
            <th>Value</th>
        </tr>
    `;
    
    const totalProducts = document.getElementById('totalProducts')?.innerText || '0';
    const totalUnits = document.getElementById('totalUnits')?.innerText || '0';
    const inventoryValue = document.getElementById('inventoryValue')?.innerText || '₱0';
    const lowStockCount = document.getElementById('lowStockCount')?.innerText || '0';
    const outOfStockCount = document.getElementById('outOfStockCount')?.innerText || '0';
    const wellStockedCount = document.getElementById('wellStockedCount')?.innerText || '0';
    const potentialProfit = document.getElementById('potentialProfit')?.innerText || '₱0';
    
    tbody.innerHTML = `
        <tr><td><strong>Total Products</strong><\/td><td>${totalProducts}<\/td><\/tr>
        <tr><td><strong>Total Units in Stock</strong><\/td><td>${totalUnits}<\/td><\/tr>
        <tr><td><strong>Total Inventory Value</strong><\/td><td>${inventoryValue}<\/td><\/tr>
        <tr><td><strong>Low Stock Items (&lt;10)</strong><\/td><td>${lowStockCount}<\/td><\/tr>
        <tr><td><strong>Out of Stock Items</strong><\/td><td>${outOfStockCount}<\/td><\/tr>
        <tr><td><strong>Well Stocked Items</strong><\/td><td>${wellStockedCount}<\/td><\/tr>
        <tr><td><strong>Potential Profit</strong><\/td><td>${potentialProfit}<\/td><\/tr>
    `;
    
    const reportTitle = document.getElementById('reportTitle');
    if (reportTitle) reportTitle.innerHTML = '<i class="fas fa-chart-pie"></i> Inventory Summary';
}

function displayLowStockReport(data, summary) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    const summaryCard = document.getElementById('summaryCard');
    
    if (!tbody || !thead) return;
    
    thead.innerHTML = `
        <tr>
            <th>Product Code</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Current Stock</th>
            <th>Selling Price</th>
            <th>Total Value</th>
            <th>Status</th>
        </tr>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No low stock products found<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = data.map(product => {
        let statusClass = product.CurrentStock === 0 ? 'badge-out' : 'badge-low';
        let statusText = product.CurrentStock === 0 ? 'OUT OF STOCK' : 'LOW STOCK';
        
        return `
            <tr>
                <td>${product.ProductCode || 'N/A'}<\/td>
                <td><strong>${escapeHtml(product.ProductName)}<\/strong><\/td>
                <td>${product.Category || '-'}<\/td>
                <td>${product.Brand || '-'}<\/td>
                <td><span class="${statusClass}">${product.CurrentStock} units</span><\/td>
                <td>₱${formatNumber(product.SellingPrice)}<\/td>
                <td>₱${formatNumber(product.TotalValue)}<\/td>
                <td><span class="${statusClass}">${statusText}</span><\/td>
            </tr>
        `;
    }).join('');
    
    if (summaryCard && summary) {
        summaryCard.innerHTML = `
            <div class="summary-row">
                <span>Total Low Stock Items:</span>
                <span>${summary.total_low_stock}</span>
            </div>
            <div class="summary-row">
                <span>Total Units:</span>
                <span>${summary.total_units}</span>
            </div>
            <div class="summary-row total">
                <span>Total Value at Risk:</span>
                <span>₱${formatNumber(summary.total_value)}</span>
            </div>
        `;
    }
    
    const reportTitle = document.getElementById('reportTitle');
    if (reportTitle) reportTitle.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Low Stock Report';
}

function displayCategoryReport(data, totalValue) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    const summaryCard = document.getElementById('summaryCard');
    
    if (!tbody || !thead) return;
    
    thead.innerHTML = `
        <tr>
            <th>Category</th>
            <th>Products</th>
            <th>Total Units</th>
            <th>Total Value</th>
            <th>Average Price</th>
            <th>Percentage</th>
            <th>Potential Profit</th>
        </tr>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No category data available<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = data.map(cat => {
        const percentage = totalValue > 0 ? ((cat.TotalValue / totalValue) * 100).toFixed(2) : 0;
        return `
            <tr>
                <td><strong>${cat.Category || 'Uncategorized'}</strong><\/td>
                <td>${cat.ProductCount}<\/td>
                <td>${cat.TotalUnits}<\/td>
                <td>₱${formatNumber(cat.TotalValue)}<\/td>
                <td>₱${formatNumber(cat.AveragePrice)}<\/td>
                <td>${percentage}%<\/td>
                <td>₱${formatNumber(cat.PotentialProfit)}<\/td>
            </tr>
        `;
    }).join('');
    
    if (summaryCard) {
        summaryCard.innerHTML = `
            <div class="summary-row">
                <span>Total Categories:</span>
                <span>${data.length}</span>
            </div>
            <div class="summary-row">
                <span>Total Products:</span>
                <span>${data.reduce((sum, c) => sum + c.ProductCount, 0)}</span>
            </div>
            <div class="summary-row total">
                <span>Total Inventory Value:</span>
                <span>₱${formatNumber(totalValue)}</span>
            </div>
        `;
    }
    
    const reportTitle = document.getElementById('reportTitle');
    if (reportTitle) reportTitle.innerHTML = '<i class="fas fa-chart-bar"></i> Category Report';
}

function displayStockMovementReport(data, summary) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    const summaryCard = document.getElementById('summaryCard');
    
    if (!tbody || !thead) return;
    
    thead.innerHTML = `
        <tr>
            <th>Product</th>
            <th>Stock In</th>
            <th>Stock Out</th>
            <th>Net Change</th>
            <th>Transactions</th>
            <th>Total Cost</th>
        </tr>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No stock movement data available<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = data.map(item => {
        let netClass = item.NetChange >= 0 ? 'text-success' : 'text-danger';
        return `
            <tr>
                <td><strong>${escapeHtml(item.ProductName)}<\/strong><\/td>
                <td><span class="text-success">+${item.TotalStockIn || 0}</span><\/td>
                <td><span class="text-danger">-${item.TotalStockOut || 0}</span><\/td>
                <td><span class="${netClass}">${item.NetChange >= 0 ? '+' : ''}${item.NetChange || 0}</span><\/td>
                <td>${item.TransactionCount || 0}<\/td>
                <td>₱${formatNumber(item.TotalCostValue)}<\/td>
            </tr>
        `;
    }).join('');
    
    if (summaryCard && summary) {
        const netMovement = (summary.total_stock_in || 0) - (summary.total_stock_out || 0);
        summaryCard.innerHTML = `
            <div class="summary-row">
                <span>Total Stock In:</span>
                <span>+${summary.total_stock_in || 0}</span>
            </div>
            <div class="summary-row">
                <span>Total Stock Out:</span>
                <span>-${summary.total_stock_out || 0}</span>
            </div>
            <div class="summary-row">
                <span>Net Movement:</span>
                <span class="${netMovement >= 0 ? 'text-success' : 'text-danger'}">
                    ${netMovement >= 0 ? '+' : ''}${netMovement}
                </span>
            </div>
            <div class="summary-row total">
                <span>Total Cost Value:</span>
                <span>₱${formatNumber(summary.total_cost_value)}</span>
            </div>
        `;
    }
    
    const reportTitle = document.getElementById('reportTitle');
    if (reportTitle) reportTitle.innerHTML = '<i class="fas fa-exchange-alt"></i> Stock Movement Report';
}

function displayProductDetails(data, summary) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    const summaryCard = document.getElementById('summaryCard');
    
    if (!tbody || !thead) return;
    
    thead.innerHTML = `
        <tr>
            <th>Product Code</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Stock</th>
            <th>Cost Price</th>
            <th>Selling Price</th>
            <th>Profit/Unit</th>
            <th>Total Value</th>
        </tr>
    `;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No products found<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = data.map(product => {
        let stockClass = product.CurrentStock === 0 ? 'badge-out' : (product.CurrentStock < 10 ? 'badge-low' : 'badge-normal');
        let stockText = product.CurrentStock === 0 ? 'OUT' : (product.CurrentStock < 10 ? 'LOW' : 'OK');
        
        return `
            <tr>
                <td>${product.ProductCode || 'N/A'}<\/td>
                <td><strong>${escapeHtml(product.ProductName)}<\/strong><\/td>
                <td>${product.Category || '-'}<\/td>
                <td>${product.Brand || '-'}<\/td>
                <td><span class="${stockClass}">${product.CurrentStock} (${stockText})</span><\/td>
                <td>₱${formatNumber(product.CostPrice)}<\/td>
                <td>₱${formatNumber(product.SellingPrice)}<\/td>
                <td>₱${formatNumber(product.ProfitPerUnit)}<\/td>
                <td>₱${formatNumber(product.TotalValue)}<\/td>
            </tr>
        `;
    }).join('');
    
    if (summaryCard && summary) {
        summaryCard.innerHTML = `
            <div class="summary-row">
                <span>Total Products:</span>
                <span>${summary.total_products}</span>
            </div>
            <div class="summary-row">
                <span>Total Units:</span>
                <span>${summary.total_units}</span>
            </div>
            <div class="summary-row">
                <span>Total Value:</span>
                <span>₱${formatNumber(summary.total_value)}</span>
            </div>
            <div class="summary-row total">
                <span>Potential Profit:</span>
                <span>₱${formatNumber(summary.potential_profit)}</span>
            </div>
        `;
    }
    
    const reportTitle = document.getElementById('reportTitle');
    if (reportTitle) reportTitle.innerHTML = '<i class="fas fa-list"></i> Product Details';
}

// ============================================
// TAB SWITCHING
// ============================================

function switchTab(tab) {
    currentTab = tab;
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-tab') === tab) {
            btn.classList.add('active');
        }
    });
    
    loadReport();
}

// ============================================
// EXPORT FUNCTION
// ============================================

function exportReport() {
    let type = 'inventory';
    if (currentTab === 'lowstock') {
        type = 'lowstock';
    }
    window.open(`${API_URL}?action=exportReport&type=${type}`);
    showToast('Export started', 'success');
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
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} show`;
    toast.setAttribute('role', 'alert');
    toast.style.minWidth = '250px';
    toast.style.marginBottom = '10px';
    
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle' };
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${icons[type] || 'fa-info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
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

const categoryFilter = document.getElementById('categoryFilter');
if (categoryFilter) {
    categoryFilter.addEventListener('change', function() {
        if (currentTab === 'products') {
            loadProductDetails();
        }
    });
}

const startDate = document.getElementById('startDate');
const endDate = document.getElementById('endDate');
if (startDate) {
    startDate.addEventListener('change', function() {
        if (currentTab === 'movement') {
            loadStockMovementReport();
        }
    });
}
if (endDate) {
    endDate.addEventListener('change', function() {
        if (currentTab === 'movement') {
            loadStockMovementReport();
        }
    });
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadReport();
});
</script>