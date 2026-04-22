<?php
// pages/stock-in.php - Stock Management Frontend (Fixed for sidebar)
?>
<style>
    /* Dashboard specific styles that don't conflict with main page */
    .stock-container {
        padding: 0;
        width: 100%;
    }
    
    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 15px;
    }
    
    .stat-icon.blue { background: rgba(79, 158, 255, 0.15); color: #4f9eff; }
    .stat-icon.green { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .stat-icon.orange { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .stat-icon.red { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #1a2a3a;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6c7a91;
        margin-top: 5px;
    }
    
    /* Main Grid */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 25px;
        margin-bottom: 30px;
    }
    
    @media (max-width: 992px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Card Styles */
    .card {
        background: white;
        border-radius: 20px;
        border: none;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 0;
    }
    
    .card-header {
        background: white;
        border-bottom: 1px solid #eef2f7;
        padding: 18px 25px;
        font-weight: 600;
        font-size: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-body {
        padding: 20px 25px;
    }
    
    /* Product List */
    .product-search {
        margin-bottom: 15px;
        position: relative;
    }
    
    .product-search input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .product-search i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .product-list {
        max-height: 450px;
        overflow-y: auto;
    }
    
    .product-list::-webkit-scrollbar {
        width: 5px;
    }
    
    .product-list::-webkit-scrollbar-track {
        background: #eef2f7;
        border-radius: 10px;
    }
    
    .product-list::-webkit-scrollbar-thumb {
        background: #4f9eff;
        border-radius: 10px;
    }
    
    .product-item {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid #eef2f7;
    }
    
    .product-item:hover {
        background: #eef2ff;
        border-color: #4f9eff;
        transform: translateX(3px);
    }
    
    .product-item.selected {
        background: linear-gradient(135deg, #eef2ff, #e6edff);
        border-color: #4f9eff;
        border-left: 4px solid #4f9eff;
    }
    
    .product-name {
        font-weight: 600;
        color: #1a2a3a;
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .product-category {
        font-size: 11px;
        color: #6c7a91;
        margin-bottom: 5px;
    }
    
    .product-stock {
        font-size: 12px;
    }
    
    .stock-low {
        color: #dc3545;
        font-weight: 600;
    }
    
    .stock-normal {
        color: #28a745;
    }
    
    /* Form Styles */
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #4a5568;
        margin-bottom: 6px;
    }
    
    .form-control, .form-select {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #4f9eff;
        box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.1);
    }
    
    .selected-info {
        background: #eef2ff;
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }
    
    .selected-info label {
        font-size: 12px;
        color: #6c7a91;
        margin-bottom: 3px;
    }
    
    .selected-info .value {
        font-weight: 600;
        color: #1a2a3a;
    }
    
    .btn-primary {
        background: #4f9eff;
        border: none;
        border-radius: 12px;
        padding: 12px;
        font-weight: 600;
        width: 100%;
    }
    
    .btn-primary:hover {
        background: #3a7fd9;
    }
    
    .btn-primary:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }
    
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 12px;
        padding: 8px 16px;
        font-size: 13px;
    }
    
    .btn-success:hover {
        background: #218838;
    }
    
    /* Preview Alert */
    .preview-alert {
        background: #eef2ff;
        border-radius: 12px;
        padding: 12px;
        margin-top: 15px;
        font-size: 13px;
    }
    
    /* History Table */
    .history-section {
        margin-top: 30px;
    }
    
    .table-responsive {
        border-radius: 12px;
    }
    
    .table th {
        font-weight: 600;
        font-size: 12px;
        background: #f8fafc;
        padding: 12px;
    }
    
    .table td {
        font-size: 12px;
        vertical-align: middle;
        padding: 10px 12px;
    }
    
    .badge-quantity {
        background: #4f9eff;
        color: white;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    /* Modal */
    .modal-content {
        border-radius: 20px;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #4f9eff, #3a7fd9);
        color: white;
        border-radius: 20px 20px 0 0;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    /* Toast */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
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
    
    /* Loading */
    .loading {
        text-align: center;
        padding: 40px;
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
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    hr {
        margin: 20px 0;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .stat-value {
            font-size: 22px;
        }
        
        .card-header {
            padding: 12px 15px;
        }
        
        .card-body {
            padding: 15px;
        }
    }
</style>

<div class="stock-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-arrow-down"></i> Stock In</h4>
            <p class="text-muted mb-0">Add quantity to existing products or create new products</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row" id="statsContainer">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-box"></i></div>
            <div class="stat-value" id="totalProducts">-</div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-peso-sign"></i></div>
            <div class="stat-value" id="totalStockValue">-</div>
            <div class="stat-label">Total Stock Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value" id="lowStockCount">-</div>
            <div class="stat-label">Low Stock Items (&lt;10)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalUnitsAdded">-</div>
            <div class="stat-label">Units Added (30d)</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="main-grid">
        <!-- Left: Product List -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list"></i> Products</span>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> New Product
                </button>
            </div>
            <div class="card-body">
                <div class="product-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search products by name, brand..." onkeyup="filterProducts()">
                </div>
                <div class="product-list" id="productList">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading products...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Add Stock Form -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-plus-circle"></i> Add Quantity to Stock</span>
            </div>
            <div class="card-body">
                <div id="selectedProductInfo" class="selected-info">
                    <label>Selected Product</label>
                    <div class="value" id="selectedProductDisplay">No product selected</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Current Stock</label>
                        <div class="form-control bg-light" id="currentStockDisplay" style="background: #f8fafc;">-</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantity to Add *</label>
                        <input type="number" id="quantity" class="form-control" min="1" placeholder="Enter quantity">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price </label>
                        <input type="number" id="costPrice" class="form-control" step="0.01" placeholder="Cost per unit">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Invoice No.</label>
                        <input type="text" id="invoiceNo" class="form-control" placeholder="INV-001">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" id="supplier" class="form-control" placeholder="Supplier name">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                </div>
                
                <div id="stockPreview" class="preview-alert" style="display: none;">
                    <i class="fas fa-calculator"></i> After adding: <strong id="newStockPreview">0</strong> units
                </div>
                
                <button class="btn btn-primary" id="addStockBtn" disabled>
                    <i class="fas fa-save"></i> Add Quantity to Stock
                </button>
            </div>
        </div>
    </div>

    <!-- Stock History Section -->
    <div class="history-section">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-history"></i> Recent Stock-In History</span>
                <button class="btn btn-danger btn-sm" id="clearHistoryBtn" onclick="clearHistory()" style="display: none;">
                    <i class="fas fa-trash"></i> Clear History
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Old Stock</th>
                                <th>New Stock</th>
                                <th>Cost</th>
                                <th>Total</th>
                                <th>Supplier</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
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
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" id="newProductName" class="form-control" placeholder="e.g., iPhone 15 Pro">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        <select id="newCategory" class="form-select">
                            <option value="">Select Category</option>
                            <option value="Mobile Phones">Mobile Phones</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Tablets">Tablets</option>
                            <option value="Wearables">Wearables</option>
                            <option value="Chargers">Chargers</option>
                            <option value="Cases">Cases</option>
                            <option value="Headphones">Headphones</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" id="newBrand" class="form-control" placeholder="e.g., Apple, Samsung">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Initial Stock</label>
                        <input type="number" id="newInitialStock" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price </label>
                        <input type="number" id="newCostPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Selling Price</label>
                        <input type="number" id="newSellingPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Invoice No.</label>
                        <input type="text" id="newInvoiceNo" class="form-control" placeholder="INV-001">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" id="newSupplier" class="form-control" placeholder="Supplier name">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProductBtn">Save Product</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/stockindata.php';

// Global variables
let selectedProduct = null;
let allProducts = [];

// ============================================
// HELPER FUNCTIONS
// ============================================

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    toast.style.display = 'block';
    toast.style.minWidth = '250px';
    toast.style.marginBottom = '10px';
    
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    const bgColor = type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107');
    
    toast.innerHTML = `
        <div class="toast-header" style="border-bottom: none;">
            <i class="fas ${icon}" style="color: ${bgColor}; margin-right: 10px;"></i>
            <strong class="me-auto">${type === 'success' ? 'Success' : (type === 'error' ? 'Error' : 'Warning')}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000, autohide: true });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// ============================================
// API CALLS
// ============================================

async function apiCall(action, method = 'GET', data = null) {
    const options = { 
        method: method, 
        headers: { 'Content-Type': 'application/json' }
    };
    if (data) options.body = JSON.stringify(data);
    
    try {
        const response = await fetch(`${API_URL}?action=${action}`, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast('Network error: ' + error.message, 'error');
        return { success: false, message: 'Network error' };
    }
}

async function loadDashboardStats() {
    const result = await apiCall('getDashboardStats');
    if (result.success && result.data) {
        document.getElementById('totalProducts').innerText = result.data.TotalProducts || 0;
        document.getElementById('totalStockValue').innerText =  (result.data.TotalStockValue || 0).toLocaleString();
        document.getElementById('lowStockCount').innerText = result.data.LowStockCount || 0;
        document.getElementById('totalUnitsAdded').innerText = result.data.TotalUnitsAdded || 0;
    }
}

async function loadProducts() {
    const result = await apiCall('getProducts');
    if (result.success) {
        allProducts = result.data;
        renderProductList(allProducts);
    }
}

async function loadStockHistory() {
    const result = await apiCall('getStockHistory');
    if (result.success) {
        renderHistoryTable(result.data);
        const clearBtn = document.getElementById('clearHistoryBtn');
        if (clearBtn) clearBtn.style.display = result.data.length > 0 ? 'inline-block' : 'none';
    }
}

async function addStock(productId, quantity, costPrice, invoiceNo, supplier, notes) {
    const result = await apiCall('addStock', 'POST', {
        product_id: productId,
        quantity: quantity,
        cost_price: costPrice,
        invoice_no: invoiceNo,
        supplier: supplier,
        notes: notes
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        loadDashboardStats();
        loadProducts();
        loadStockHistory();
        return true;
    } else {
        showToast(result.message || 'Failed to add stock', 'error');
        return false;
    }
}

async function addNewProduct(productData) {
    const result = await apiCall('addProduct', 'POST', productData);
    
    if (result.success) {
        showToast(result.message, 'success');
        loadDashboardStats();
        loadProducts();
        loadStockHistory();
        return true;
    } else {
        showToast(result.message || 'Failed to add product', 'error');
        return false;
    }
}

async function clearHistory() {
    if (!confirm('Are you sure you want to clear all stock-in history?')) return;
    
    const result = await apiCall('clearHistory', 'DELETE');
    if (result.success) {
        showToast('Stock history cleared', 'success');
        loadStockHistory();
    }
}

// ============================================
// RENDER FUNCTIONS
// ============================================

function renderProductList(products) {
    const container = document.getElementById('productList');
    
    if (!products || products.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No products found</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Add First Product
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="product-item" onclick="selectProduct(${product.ProductID})" data-id="${product.ProductID}">
            <div class="d-flex justify-content-between align-items-start">
                <div style="flex: 1;">
                    <div class="product-name">${escapeHtml(product.ProductName)}</div>
                    <div class="product-category">
                        <i class="fas fa-tag"></i> ${product.Category || 'Uncategorized'}
                        ${product.Brand ? ` | <i class="fas fa-building"></i> ${escapeHtml(product.Brand)}` : ''}
                    </div>
                    <div class="product-stock">
                        Stock: <span class="${product.CurrentStock < 10 ? 'stock-low' : 'stock-normal'}">${product.CurrentStock} units</span>
                        ${product.CurrentStock < 10 ? '<span class="badge bg-danger ms-2">Low Stock!</span>' : ''}
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary">₱${parseFloat(product.CostPrice || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function renderHistoryTable(history) {
    const tbody = document.getElementById('historyTableBody');
    
    if (!history || history.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center empty-state">
                    <i class="fas fa-history"></i>
                    <p>No stock-in history yet</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = history.map(h => `
        <tr>
            <td><small>${h.TransactionDate || ''}</small></td>
            <td><strong>${escapeHtml(h.ProductName)}</strong></td>
            <td><span class="badge-quantity">+${h.QuantityAdded}</span></td>
            <td>${h.OldStock}</td>
            <td>${h.NewStock}</td>
             <td>₱${parseFloat(h.CostPrice || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td>₱${parseFloat(h.TotalCost || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td>${escapeHtml(h.SupplierName || '-')}</td>
            <td>${escapeHtml(h.InvoiceNo || '-')}</td>
        </tr>
    `).join('');
}

function selectProduct(productId) {
    const product = allProducts.find(p => p.ProductID == productId);
    if (!product) return;
    
    selectedProduct = product;
    
    // Update UI
    document.querySelectorAll('.product-item').forEach(item => {
        item.classList.remove('selected');
        if (item.dataset.id == productId) item.classList.add('selected');
    });
    
    document.getElementById('selectedProductDisplay').innerHTML = `<strong>${escapeHtml(product.ProductName)}</strong>`;
    document.getElementById('currentStockDisplay').innerHTML = `${product.CurrentStock} units`;
    document.getElementById('costPrice').value = product.CostPrice || 0;
    document.getElementById('addStockBtn').disabled = false;
    document.getElementById('quantity').value = '';
    document.getElementById('stockPreview').style.display = 'none';
}

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filtered = allProducts.filter(product => 
        product.ProductName.toLowerCase().includes(searchTerm) ||
        (product.Brand && product.Brand.toLowerCase().includes(searchTerm)) ||
        (product.Category && product.Category.toLowerCase().includes(searchTerm))
    );
    renderProductList(filtered);
}

function updateStockPreview() {
    const qty = parseInt(document.getElementById('quantity').value) || 0;
    if (selectedProduct && qty > 0) {
        const newStock = selectedProduct.CurrentStock + qty;
        document.getElementById('newStockPreview').innerText = newStock;
        document.getElementById('stockPreview').style.display = 'block';
    } else {
        document.getElementById('stockPreview').style.display = 'none';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// EVENT HANDLERS
// ============================================

document.getElementById('quantity').addEventListener('input', updateStockPreview);

document.getElementById('addStockBtn').addEventListener('click', async function() {
    if (!selectedProduct) {
        showToast('Please select a product first', 'warning');
        return;
    }
    
    const quantity = parseInt(document.getElementById('quantity').value);
    if (!quantity || quantity <= 0) {
        showToast('Please enter a valid quantity', 'warning');
        return;
    }
    
    const costPrice = parseFloat(document.getElementById('costPrice').value) || 0;
    const invoiceNo = document.getElementById('invoiceNo').value;
    const supplier = document.getElementById('supplier').value;
    const notes = document.getElementById('notes').value;
    
    const success = await addStock(selectedProduct.ProductID, quantity, costPrice, invoiceNo, supplier, notes);
    
    if (success) {
        document.getElementById('quantity').value = '';
        document.getElementById('invoiceNo').value = '';
        document.getElementById('supplier').value = '';
        document.getElementById('notes').value = '';
        document.getElementById('stockPreview').style.display = 'none';
        
        // Update selected product reference
        const updatedProduct = allProducts.find(p => p.ProductID == selectedProduct.ProductID);
        if (updatedProduct) {
            selectedProduct.CurrentStock = updatedProduct.CurrentStock;
            document.getElementById('currentStockDisplay').innerHTML = `${selectedProduct.CurrentStock} units`;
        }
    }
});

document.getElementById('saveProductBtn').addEventListener('click', async function() {
    const productName = document.getElementById('newProductName').value.trim();
    const category = document.getElementById('newCategory').value;
    
    if (!productName || !category) {
        showToast('Product name and category are required', 'warning');
        return;
    }
    
    const productData = {
        product_name: productName,
        category: category,
        brand: document.getElementById('newBrand').value,
        initial_stock: parseInt(document.getElementById('newInitialStock').value) || 0,
        cost_price: parseFloat(document.getElementById('newCostPrice').value) || 0,
        selling_price: parseFloat(document.getElementById('newSellingPrice').value) || 0,
        invoice_no: document.getElementById('newInvoiceNo').value,
        supplier_name: document.getElementById('newSupplier').value
    };
    
    const success = await addNewProduct(productData);
    
    if (success) {
        // Close modal and clear form
        const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
        modal.hide();
        
        document.getElementById('newProductName').value = '';
        document.getElementById('newCategory').value = '';
        document.getElementById('newBrand').value = '';
        document.getElementById('newInitialStock').value = '0';
        document.getElementById('newCostPrice').value = '';
        document.getElementById('newSellingPrice').value = '';
        document.getElementById('newInvoiceNo').value = '';
        document.getElementById('newSupplier').value = '';
    }
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadProducts();
    loadStockHistory();
});
</script>