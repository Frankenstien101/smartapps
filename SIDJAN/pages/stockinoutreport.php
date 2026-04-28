<?php
// pages/stock-report.php - Combined Stock In and Stock Out Report
?>
<style>
    .report-container {
        padding: 0;
        width: 100%;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
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
    
    .badge-stockin { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-stockout { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
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
        padding: 12px 16px;
        margin-bottom: 10px;
    }
    
    .toast.success { border-left-color: #28a745; }
    .toast.error { border-left-color: #dc3545; }
    
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
            <h4><i class="fas fa-exchange-alt"></i> Stock In / Out Report</h4>
            <p class="text-muted mb-0">View and analyze stock movement transactions</p>
        </div>
        <div>
            <button class="btn-secondary" onclick="exportReport()">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-value" id="totalStockIn">0</div>
            <div class="stat-label">Total Stock In</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-value" id="totalStockOut">0</div>
            <div class="stat-label">Total Stock Out</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="netMovement">0</div>
            <div class="stat-label">Net Movement</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-value" id="totalValue">₱0</div>
            <div class="stat-label">Total Value</div>
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
                <label>Transaction Type</label>
                <select id="typeFilter">
                    <option value="all">All Transactions</option>
                    <option value="stockin">Stock In Only</option>
                    <option value="stockout">Stock Out Only</option>
                </select>
            </div>
            <div class="date-group">
                <label>Search</label>
                <input type="text" id="searchInput" placeholder="Product name...">
            </div>
            <div class="date-group">
                <button class="btn-primary" onclick="loadReport()" style="margin-top: 22px;">
                    <i class="fas fa-search"></i> Generate
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs-container">
        <button class="tab-btn active" data-tab="all">All Transactions</button>
        <button class="tab-btn" data-tab="stockin">Stock In</button>
        <button class="tab-btn" data-tab="stockout">Stock Out</button>
        <button class="tab-btn" data-tab="summary">Summary by Product</button>
    </div>
    
    <!-- Report Table -->
    <div class="report-table-container">
        <div class="table-header">
            <span id="reportTitle"><i class="fas fa-list"></i> All Stock Transactions</span>
            <button class="btn-refresh" onclick="loadReport()" style="background:transparent; border:none; color:#4f9eff;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead id="tableHead">
                    <tr>
                        
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Old Stock</th>
                        <th>New Stock</th>
                        <th>Cost/Unit</th>
                        <th>Total Cost</th>
                        <th>Reference</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody">
                    <tr><td colspan="10" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                </tbody>
            </table>
        </div>
        <div class="summary-card" id="summaryCard">
            <div class="summary-row">
                <span>Total Stock In Quantity:</span>
                <span id="summaryStockIn">0</span>
            </div>
            <div class="summary-row">
                <span>Total Stock Out Quantity:</span>
                <span id="summaryStockOut">0</span>
            </div>
            <div class="summary-row total">
                <span>Net Change:</span>
                <span id="summaryNet">0</span>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/stockinoutdata.php';

let currentTab = 'all';
let allTransactions = [];
let filteredTransactions = [];

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
    const typeFilter = document.getElementById('typeFilter').value;
    const searchTerm = document.getElementById('searchInput').value;
    
    document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="10" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>';
    
    const result = await apiCall(`getStockMovements&start_date=${startDate}&end_date=${endDate}`);
    
    if (result.success && result.data) {
        allTransactions = result.data;
        filteredTransactions = filterTransactions(allTransactions, typeFilter, searchTerm);
        
        if (currentTab === 'all') {
            displayTransactionsTable(filteredTransactions);
            updateSummary(filteredTransactions);
        } else if (currentTab === 'stockin') {
            const stockInOnly = filteredTransactions.filter(t => t.QuantityAdded > 0);
            displayTransactionsTable(stockInOnly);
            updateSummary(stockInOnly);
        } else if (currentTab === 'stockout') {
            const stockOutOnly = filteredTransactions.filter(t => t.QuantityAdded < 0);
            displayTransactionsTable(stockOutOnly);
            updateSummary(stockOutOnly);
        } else if (currentTab === 'summary') {
            displaySummaryByProduct(filteredTransactions);
        }
        
        updateStats(result.summary);
    } else {
        document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="10" class="text-center text-muted">No data available<\/td><\/tr>';
    }
}

function filterTransactions(transactions, typeFilter, searchTerm) {
    let filtered = [...transactions];
    
    if (typeFilter === 'stockin') {
        filtered = filtered.filter(t => t.QuantityAdded > 0);
    } else if (typeFilter === 'stockout') {
        filtered = filtered.filter(t => t.QuantityAdded < 0);
    }
    
    if (searchTerm) {
        const term = searchTerm.toLowerCase();
        filtered = filtered.filter(t => 
            t.ProductName.toLowerCase().includes(term) ||
            (t.Notes && t.Notes.toLowerCase().includes(term))
        );
    }
    
    return filtered;
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displayTransactionsTable(transactions) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    thead.innerHTML = `
        <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Type</th>
            <th>Quantity</th>
            <th>Old Stock</th>
            <th>New Stock</th>
            <th>Cost/Unit</th>
            <th>Total Cost</th>
            <th>Reference</th>
            <th>By</th>
        </tr>
    `;
    
    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No transactions found<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = transactions.map(t => {
        const qty = parseFloat(t.QuantityAdded) || 0;
        const isStockIn = qty > 0;
        const typeClass = isStockIn ? 'badge-stockin' : 'badge-stockout';
        const typeText = isStockIn ? 'STOCK IN' : 'STOCK OUT';
        const qtyDisplay = isStockIn ? `+${qty.toLocaleString()}` : qty.toLocaleString();
        const qtyClass = isStockIn ? 'text-success' : 'text-danger';
        const oldStock = parseFloat(t.OldStock) || 0;
        const newStock = parseFloat(t.NewStock) || 0;
        
        return `
            <tr>
                <td><small>${t.TransactionDate || '-'}<\/small><\/td>
                <td><strong>${escapeHtml(t.ProductName)}<\/strong><\/td>
                <td><span class="${typeClass}">${typeText}<\/span><\/td>
                <td><span class="${qtyClass} fw-bold">${qtyDisplay}<\/span><\/td>
                <td>${oldStock.toLocaleString()}<\/td>
                <td>${newStock.toLocaleString()}<\/td>
                <td>₱${formatNumber(t.CostPrice)}<\/td>
                <td>₱${formatNumber(t.TotalCost)}<\/td>
                <td>${escapeHtml(t.Notes) || '-'}<\/td>
                <td>${t.AddedBy || '-'}<\/td>
            </tr>
        `;
    }).join('');
}

function displaySummaryByProduct(transactions) {
    const tbody = document.getElementById('reportTableBody');
    const thead = document.getElementById('tableHead');
    
    // Group by product
    const productMap = new Map();
    
    for (let i = 0; i < transactions.length; i++) {
        const t = transactions[i];
        const productId = t.ProductID;
        const qty = parseFloat(t.QuantityAdded) || 0;
        const cost = parseFloat(t.TotalCost) || 0;
        
        if (!productMap.has(productId)) {
            productMap.set(productId, {
                product_name: t.ProductName,
                stock_in: 0,
                stock_out: 0,
                total_cost: 0,
                transactions: 0
            });
        }
        const product = productMap.get(productId);
        
        if (qty > 0) {
            product.stock_in += qty;
            product.total_cost += cost;
        } else {
            product.stock_out += Math.abs(qty);
        }
        product.transactions++;
    }
    
    thead.innerHTML = `
        <tr>
            <th>Product</th>
            <th>Stock In</th>
            <th>Stock Out</th>
            <th>Net Change</th>
            <th>Total Cost</th>
            <th>Transactions</th>
        </tr>
    `;
    
    if (productMap.size === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No data available<\/td><\/tr>';
        return;
    }
    
    const products = Array.from(productMap.values());
    products.sort((a, b) => (b.stock_in + b.stock_out) - (a.stock_in + a.stock_out));
    
    tbody.innerHTML = products.map(p => {
        const netChange = p.stock_in - p.stock_out;
        let netClass = 'text-secondary';
        if (netChange > 0) netClass = 'text-success';
        else if (netChange < 0) netClass = 'text-danger';
        
        return `
            <tr>
                <td><strong>${escapeHtml(p.product_name)}<\/strong><\/td>
                <td><span class="text-success">+${p.stock_in.toLocaleString()}<\/span><\/td>
                <td><span class="text-danger">-${p.stock_out.toLocaleString()}<\/span><\/td>
                <td><span class="${netClass} fw-bold">${netChange > 0 ? '+' : ''}${netChange.toLocaleString()}<\/span><\/td>
                <td>₱${formatNumber(p.total_cost)}<\/td>
                <td>${p.transactions}<\/td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('reportTitle').innerHTML = '<i class="fas fa-chart-bar"></i> Summary by Product';
}

function updateSummary(transactions) {
    let totalStockIn = 0;
    let totalStockOut = 0;
    
    // Use proper number addition, not string concatenation
    for (let i = 0; i < transactions.length; i++) {
        const qty = parseFloat(transactions[i].QuantityAdded) || 0;
        if (qty > 0) {
            totalStockIn += qty;
        } else {
            totalStockOut += Math.abs(qty);
        }
    }
    
    const netChange = totalStockIn - totalStockOut;
    
    document.getElementById('summaryStockIn').innerHTML = totalStockIn.toLocaleString();
    document.getElementById('summaryStockOut').innerHTML = totalStockOut.toLocaleString();
    document.getElementById('summaryNet').innerHTML = netChange.toLocaleString();
    
    const netElement = document.getElementById('summaryNet');
    if (netChange > 0) {
        netElement.className = 'text-success fw-bold';
    } else if (netChange < 0) {
        netElement.className = 'text-danger fw-bold';
    } else {
        netElement.className = '';
    }
}

function updateStats(summary) {
    if (summary) {
        const totalStockIn = parseFloat(summary.total_stock_in) || 0;
        const totalStockOut = parseFloat(summary.total_stock_out) || 0;
        const totalValue = parseFloat(summary.total_value) || 0;
        const netMovement = totalStockIn - totalStockOut;
        
        document.getElementById('totalStockIn').innerHTML = totalStockIn.toLocaleString();
        document.getElementById('totalStockOut').innerHTML = totalStockOut.toLocaleString();
        document.getElementById('netMovement').innerHTML = netMovement.toLocaleString();
        document.getElementById('totalValue').innerHTML = '₱' + formatNumber(totalValue);
        
        const netElement = document.getElementById('netMovement');
        if (netMovement > 0) {
            netElement.className = 'text-success fw-bold';
        } else if (netMovement < 0) {
            netElement.className = 'text-danger fw-bold';
        } else {
            netElement.className = '';
        }
    }
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
    
    const titles = {
        'all': 'All Stock Transactions',
        'stockin': 'Stock In Transactions',
        'stockout': 'Stock Out Transactions',
        'summary': 'Summary by Product'
    };
    
    document.getElementById('reportTitle').innerHTML = `<i class="fas fa-list"></i> ${titles[tab]}`;
    
    const typeFilter = document.getElementById('typeFilter').value;
    const searchTerm = document.getElementById('searchInput').value;
    let filtered = filterTransactions(allTransactions, typeFilter, searchTerm);
    
    if (tab === 'all') {
        displayTransactionsTable(filtered);
        updateSummary(filtered);
    } else if (tab === 'stockin') {
        const stockInOnly = filtered.filter(t => t.QuantityAdded > 0);
        displayTransactionsTable(stockInOnly);
        updateSummary(stockInOnly);
    } else if (tab === 'stockout') {
        const stockOutOnly = filtered.filter(t => t.QuantityAdded < 0);
        displayTransactionsTable(stockOutOnly);
        updateSummary(stockOutOnly);
    } else if (tab === 'summary') {
        displaySummaryByProduct(filtered);
    }
}

// ============================================
// EXPORT FUNCTION
// ============================================

function exportReport() {
    if (filteredTransactions.length === 0) {
        showToast('No data to export', 'warning');
        return;
    }
    
    let csvRows = [['Date', 'Product', 'Type', 'Quantity', 'Old Stock', 'New Stock', 'Cost/Unit', 'Total Cost', 'Reference', 'By']];
    
    filteredTransactions.forEach(t => {
        const type = t.QuantityAdded > 0 ? 'STOCK IN' : 'STOCK OUT';
        csvRows.push([
            t.TransactionDate || '',
            t.ProductName,
            type,
            t.QuantityAdded,
            t.OldStock || 0,
            t.NewStock || 0,
            formatNumber(t.CostPrice),
            formatNumber(t.TotalCost),
            t.Notes || '',
            t.AddedBy || ''
        ]);
    });
    
    const csvString = csvRows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob(["\uFEFF" + csvString], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `stock_report_${new Date().toISOString().slice(0, 19)}.csv`;
    a.click();
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

document.getElementById('startDate').addEventListener('change', loadReport);
document.getElementById('endDate').addEventListener('change', loadReport);
document.getElementById('typeFilter').addEventListener('change', loadReport);
document.getElementById('searchInput').addEventListener('input', function() {
    const typeFilter = document.getElementById('typeFilter').value;
    const searchTerm = this.value;
    const filtered = filterTransactions(allTransactions, typeFilter, searchTerm);
    
    if (currentTab === 'all') {
        displayTransactionsTable(filtered);
        updateSummary(filtered);
    } else if (currentTab === 'stockin') {
        displayTransactionsTable(filtered.filter(t => t.QuantityAdded > 0));
        updateSummary(filtered.filter(t => t.QuantityAdded > 0));
    } else if (currentTab === 'stockout') {
        displayTransactionsTable(filtered.filter(t => t.QuantityAdded < 0));
        updateSummary(filtered.filter(t => t.QuantityAdded < 0));
    } else if (currentTab === 'summary') {
        displaySummaryByProduct(filtered);
    }
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadReport();
});
</script>