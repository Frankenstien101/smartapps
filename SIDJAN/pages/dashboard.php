<?php
// pages/dashboard.php - Dashboard with real backend data
?>
<style>
    .dashboard-stats {
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
        width: 55px;
        height: 55px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }
    
    .stat-icon.primary { background: rgba(79, 158, 255, 0.15); color: #4f9eff; }
    .stat-icon.success { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .stat-icon.warning { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .stat-icon.danger { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .stat-icon.info { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #1a2a3a;
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6c7a91;
        margin-top: 8px;
    }
    
    /* Dashboard Cards */
    .dashboard-card {
        background: white;
        border-radius: 16px;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        transition: all 0.3s ease;
    }
    
    .dashboard-card .card-header {
        background: white;
        border-bottom: 1px solid #eef2f7;
        padding: 15px 20px;
        border-radius: 16px 16px 0 0;
        font-weight: 600;
    }
    
    .dashboard-card .card-body {
        padding: 20px;
    }
    
    /* Activity Items */
    .activity-item {
        padding: 12px 0;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    
    .activity-icon.stock-in { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .activity-icon.sale { background: rgba(79, 158, 255, 0.15); color: #4f9eff; }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 3px;
    }
    
    .activity-time {
        font-size: 11px;
        color: #94a3b8;
    }
    
    /* Low Stock Items */
    .low-stock-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #eef2f7;
    }
    
    .low-stock-item:last-child {
        border-bottom: none;
    }
    
    .low-stock-name {
        font-weight: 600;
        font-size: 14px;
    }
    
    .low-stock-code {
        font-size: 10px;
        color: #6c7a91;
        font-family: monospace;
    }
    
    .low-stock-qty {
        font-size: 13px;
        color: #dc3545;
        font-weight: 600;
    }
    
    /* Loading Spinner */
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
    
    /* Refresh Button */
    .refresh-btn {
        background: transparent;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 6px 12px;
        font-size: 12px;
        transition: all 0.2s;
    }
    
    .refresh-btn:hover {
        background: #f1f5f9;
        border-color: #4f9eff;
    }
    
    .refresh-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Chart containers */
    .chart-container {
        position: relative;
        min-height: 300px;
        width: 100%;
    }
    
    canvas {
        max-width: 100%;
        height: auto;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stat-value {
            font-size: 22px;
        }
        .stat-icon {
            width: 45px;
            height: 45px;
            font-size: 20px;
        }
        .chart-container {
            min-height: 250px;
        }
    }
    
    @media (max-width: 576px) {
        .stat-value {
            font-size: 18px;
        }
        .stat-label {
            font-size: 11px;
        }
        .stat-card {
            padding: 12px;
        }
    }
    
    /* Toast */
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
        min-width: 280px;
    }
    
    .toast.success { border-left-color: #28a745; }
    .toast.error { border-left-color: #dc3545; }
    .toast.warning { border-left-color: #ffc107; }
</style>

<div class="dashboard-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4><i class="fas fa-chart-line"></i> Dashboard Overview</h4>
        <button class="refresh-btn" id="refreshBtn" onclick="refreshDashboard()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-box"></i></div>
                <div class="stat-value" id="totalProducts">-</div>
                <div class="stat-label">Total Products</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-peso-sign"></i></div>
                <div class="stat-value" id="totalStockValue">-</div>
                <div class="stat-label">Total Stock Value</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value" id="lowStockCount">-</div>
                <div class="stat-label">Low Stock Items (&lt;10)</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value" id="totalUnitsInStock">-</div>
                <div class="stat-label">Total Units In Stock</div>
            </div>
        </div>
    </div>
    
    <!-- Today's Sales Row -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-value" id="todaySales">-</div>
                <div class="stat-label">Today's Sales</div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-receipt"></i></div>
                <div class="stat-value" id="todayTransactions">-</div>
                <div class="stat-label">Today's Transactions</div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Sales Overview (Last 7 Days)
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Product Distribution
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="productChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity and Low Stock Row -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Stock Activity
                </div>
                <div class="card-body" id="recentActivity">
                    <div class="text-center py-4">
                        <div class="loading-spinner"></div>
                        <p class="mt-2 text-muted">Loading activity...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
                    <span class="badge bg-danger ms-2" id="lowStockBadge">0</span>
                </div>
                <div class="card-body" id="lowStockList">
                    <div class="text-center py-4">
                        <div class="loading-spinner"></div>
                        <p class="mt-2 text-muted">Loading alerts...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Summary Table -->
    <div class="row">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-table"></i> Inventory Summary
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Stock</th>
                                    <th>Selling Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="loading-spinner"></div> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/stockindata.php';

// Chart instances
let salesChart = null;
let productChart = null;

// Global data
let products = [];
let lowStockItems = [];

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
        return { success: false, message: error.message };
    }
}

async function loadDashboardStats() {
    const result = await apiCall('getDashboardStats');
    if (result.success && result.data) {
        document.getElementById('totalProducts').innerText = result.data.TotalProducts || 0;
        document.getElementById('totalStockValue').innerText = '₱' + (result.data.TotalStockValue || 0).toLocaleString();
        document.getElementById('lowStockCount').innerText = result.data.LowStockCount || 0;
        document.getElementById('totalUnitsInStock').innerText = result.data.TotalUnitsInStock || 0;
    }
}

async function loadTodaySales() {
    const result = await apiCall('getTodaySales');
    if (result.success && result.data) {
        document.getElementById('todaySales').innerText = '₱' + (result.data.TodaySales || 0).toLocaleString();
        document.getElementById('todayTransactions').innerText = result.data.TransactionCount || 0;
    }
}

async function loadProducts() {
    const result = await apiCall('getProducts');
    if (result.success && result.data) {
        products = result.data;
        renderProductsTable(products);
        
        // Update product distribution chart
        updateProductChart(products);
    }
}

async function loadStockHistory() {
    const result = await apiCall('getStockHistory', 'GET', null, { limit: 10 });
    if (result.success && result.data) {
        renderRecentActivity(result.data);
    }
}

async function loadLowStockProducts() {
    const result = await apiCall('getLowStock');
    if (result.success && result.data) {
        lowStockItems = result.data;
        renderLowStockItems(lowStockItems);
    }
}

async function loadSalesReport() {
    const result = await apiCall('getSalesReport');
    if (result.success && result.data) {
        updateSalesChart(result.data);
    }
}

// ============================================
// RENDER FUNCTIONS
// ============================================

function renderProductsTable(productsList) {
    const tbody = document.getElementById('productsTableBody');
    
    if (!productsList || productsList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = productsList.slice(0, 15).map(product => `
        <tr>
            <td><span class="font-monospace small">${product.ProductCode || 'N/A'}</span></td>
            <td><strong>${escapeHtml(product.ProductName)}</strong></td>
            <td>${product.Category || '-'}</td>
            <td>${product.Brand || '-'}</td>
            <td>
                <span class="${(product.CurrentStock || 0) < 10 ? 'text-danger fw-bold' : ''}">
                    ${product.CurrentStock || 0} units
                </span>
                ${(product.CurrentStock || 0) < 10 ? '<span class="badge bg-danger ms-1">Low</span>' : ''}
            </td>
            <td>₱${(product.SellingPrice || 0).toLocaleString()}</td>
            <td>
                <a href="?page=stock-in" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i> Restock
                </a>
            </td>
        </table>
    `).join('');
}

function renderRecentActivity(activities) {
    const container = document.getElementById('recentActivity');
    
    if (!activities || activities.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                <p>No recent activity</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = activities.slice(0, 10).map(activity => {
        const isStockIn = activity.QuantityAdded > 0;
        const icon = isStockIn ? 'fa-arrow-down' : 'fa-shopping-cart';
        const type = isStockIn ? 'stock-in' : 'sale';
        const actionText = isStockIn ? `Added +${activity.QuantityAdded} units` : `Sold ${Math.abs(activity.QuantityAdded)} units`;
        
        return `
            <div class="activity-item">
                <div class="activity-icon ${type}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">${escapeHtml(activity.ProductName)}</div>
                    <div class="activity-time">
                        ${actionText} | 
                        Old: ${activity.OldStock} → New: ${activity.NewStock} |
                        ${activity.SupplierName ? 'Supplier: ' + escapeHtml(activity.SupplierName) : ''}
                    </div>
                </div>
                <div class="activity-time">
                    ${activity.TransactionDate ? activity.TransactionDate.substring(0, 16) : ''}
                </div>
            </div>
        `;
    }).join('');
}

function renderLowStockItems(items) {
    const container = document.getElementById('lowStockList');
    const badge = document.getElementById('lowStockBadge');
    
    if (!items || items.length === 0) {
        badge.innerText = '0';
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                <p>All products have sufficient stock</p>
            </div>
        `;
        return;
    }
    
    badge.innerText = items.length;
    container.innerHTML = items.map(item => `
        <div class="low-stock-item">
            <div>
                <div class="low-stock-name">${escapeHtml(item.ProductName)}</div>
                <div class="low-stock-code">Code: ${item.ProductCode || 'N/A'}</div>
                <div class="small text-muted">Category: ${item.Category || '-'}</div>
            </div>
            <div class="text-end">
                <div class="low-stock-qty">${item.CurrentStock} units left</div>
                <a href="?page=stock-in" class="btn btn-sm btn-danger mt-1">
                    <i class="fas fa-plus"></i> Restock Now
                </a>
            </div>
        </div>
    `).join('');
}

// ============================================
// CHARTS
// ============================================

function initCharts() {
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const productCtx = document.getElementById('productChart').getContext('2d');
    
    if (salesChart) salesChart.destroy();
    if (productChart) productChart.destroy();
    
    // Sales Chart - will be updated with real data
    salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Sales (₱)',
                data: [0, 0, 0, 0, 0, 0, 0],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                borderWidth: 2,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#fff',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 } } },
                tooltip: { callbacks: { label: function(ctx) { return '₱' + ctx.raw.toLocaleString(); } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return '₱' + v.toLocaleString(); }, font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
    
    // Product Chart - will be updated with real data
    productChart = new Chart(productCtx, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 10 } }
            }
        }
    });
}

function updateSalesChart(salesData) {
    if (!salesChart) return;
    
    // Process sales data for last 7 days
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const dailySales = new Array(7).fill(0);
    
    if (salesData && salesData.length > 0) {
        salesData.forEach(day => {
            // Find matching day
            for (let i = 0; i < days.length; i++) {
                if (day.Date && day.Date.includes(days[i]) || i === new Date().getDay()) {
                    dailySales[i] = day.TotalSales || 0;
                    break;
                }
            }
        });
    }
    
    salesChart.data.datasets[0].data = dailySales;
    salesChart.update();
}

function updateProductChart(productsList) {
    if (!productChart) return;
    
    // Count products by category
    const categoryCount = {};
    productsList.forEach(product => {
        const category = product.Category || 'Others';
        categoryCount[category] = (categoryCount[category] || 0) + 1;
    });
    
    const labels = Object.keys(categoryCount);
    const data = Object.values(categoryCount);
    const colors = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];
    
    productChart.data.labels = labels;
    productChart.data.datasets[0].data = data;
    productChart.data.datasets[0].backgroundColor = colors.slice(0, labels.length);
    productChart.update();
}

function resizeCharts() {
    if (salesChart) salesChart.resize();
    if (productChart) productChart.resize();
}

// ============================================
// REFRESH FUNCTION
// ============================================

async function refreshDashboard() {
    const refreshBtn = document.getElementById('refreshBtn');
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    
    try {
        await Promise.all([
            loadDashboardStats(),
            loadTodaySales(),
            loadProducts(),
            loadStockHistory(),
            loadLowStockProducts(),
            loadSalesReport()
        ]);
        showToast('Dashboard refreshed successfully', 'success');
    } catch (error) {
        console.error('Refresh error:', error);
        showToast('Error refreshing dashboard', 'error');
    } finally {
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

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

// Watch sidebar toggle for chart resize
function watchSidebarToggle() {
    const toggleBtn = document.getElementById('toggleSidebar');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => setTimeout(resizeCharts, 300));
    }
    
    const observer = new MutationObserver(() => setTimeout(resizeCharts, 300));
    const sidebar = document.getElementById('sidebar');
    if (sidebar) observer.observe(sidebar, { attributes: true, attributeFilter: ['style'] });
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    refreshDashboard();
    watchSidebarToggle();
    
    // Auto refresh every 60 seconds
    setInterval(refreshDashboard, 60000);
});
</script>