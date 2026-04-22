<?php
// pages/others.php - Others / Miscellaneous Products Page
?>
<style>
    .others-container {
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
    .stat-icon.info { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    .stat-icon.secondary { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
    
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
    
    .search-box {
        position: relative;
        margin-bottom: 12px;
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
    
    .filter-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 6px 14px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-btn.active {
        background: #4f9eff;
        color: white;
        border-color: #4f9eff;
    }
    
    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
        position: relative;
    }
    
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .product-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1;
    }
    
    .badge-low-stock {
        background: #dc3545;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    
    .badge-out-stock {
        background: #6c757d;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    
    .product-image {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-image i {
        font-size: 60px;
        color: rgba(255,255,255,0.9);
    }
    
    .product-info {
        padding: 15px;
    }
    
    .product-name {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 5px;
        color: #1a2a3a;
    }
    
    .product-code {
        font-size: 11px;
        color: #6c7a91;
        font-family: monospace;
        margin-bottom: 8px;
    }
    
    .product-brand {
        font-size: 12px;
        color: #4f9eff;
        margin-bottom: 8px;
    }
    
    .product-price {
        font-size: 18px;
        font-weight: 800;
        color: #28a745;
        margin-bottom: 8px;
    }
    
    .product-stock {
        font-size: 12px;
        margin-bottom: 12px;
    }
    
    .stock-normal { color: #28a745; }
    .stock-low { color: #dc3545; font-weight: 600; }
    
    .product-actions {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        flex: 1;
        padding: 8px;
        font-size: 12px;
        border-radius: 10px;
    }
    
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
    
    /* Loading */
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
        padding: 60px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    /* Toast */
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
    
    @media (max-width: 576px) {
        .products-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="others-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-microchip"></i> Others / Miscellaneous</h4>
            <p class="text-muted mb-0">Manage other products and miscellaneous items</p>
        </div>
    </div>
    
    <button class="btn btn-primary btn-sm mb-3" style="width: 120px;" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus"></i> Add New
    </button>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="fas fa-box"></i></div>
            <div class="stat-value" id="totalProducts">0</div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-peso-sign"></i></div>
            <div class="stat-value" id="totalValue">₱0</div>
            <div class="stat-label">Total Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value" id="lowStockCount">0</div>
            <div class="stat-label">Low Stock (&lt;10)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalUnits">0</div>
            <div class="stat-label">Total Units</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, brand, or code...">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-category="all">All Items</button>
            <button class="filter-btn" data-category="Supplies">Supplies</button>
            <button class="filter-btn" data-category="Rental">Rental</button>
            <button class="filter-btn" data-category="Electronic Parts">Electronic Parts</button>
            <button class="filter-btn" data-category="Tools">Tools</button>
            <button class="filter-btn" data-category="Gadgets">Gadgets</button>
            <button class="filter-btn" data-category="Others">Others</button>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="products-grid" id="productsGrid">
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="mt-2">Loading items...</p>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Product Name *</label>
                    <input type="text" id="addProductName" class="form-control" placeholder="e.g., Electric Fan, Solar Light, Resistor Kit">
                </div>
                <div class="mb-3">
                    <label class="form-label">Category *</label>
                    <select id="addProductCategory" class="form-select">
                        <option value="">Select Category</option>
                        <option value="Supplies">Supplies</option>
                        <option value="Rental">Rental</option>
                        <option value="Electronic Parts">Electronic Parts</option>
                        <option value="Tools">Tools</option>
                        <option value="Gadgets">Gadgets</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Brand</label>
                    <input type="text" id="addProductBrand" class="form-control" placeholder="Brand name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Code</label>
                    <input type="text" id="addProductCode" class="form-control" placeholder="Auto-generated">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price (₱)</label>
                        <input type="number" id="addCostPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Selling Price (₱) *</label>
                        <input type="number" id="addSellingPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Initial Stock</label>
                    <input type="number" id="addInitialStock" class="form-control" value="0" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description / Notes</label>
                    <textarea id="addProductSpecs" class="form-control" rows="2" placeholder="Additional details..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmAddBtn">Add Item</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editProductId">
                <div class="mb-3">
                    <label class="form-label">Product Name *</label>
                    <input type="text" id="editProductName" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Category *</label>
                    <select id="editProductCategory" class="form-select">
                        <option value="">Select Category</option>
                        <option value="Supplies">Supplies</option>
                        <option value="Rental">Rental</option>
                        <option value="Electronic Parts">Electronic Parts</option>
                        <option value="Tools">Tools</option>
                        <option value="Gadgets">Gadgets</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Brand</label>
                    <input type="text" id="editProductBrand" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Code</label>
                    <input type="text" id="editProductCode" class="form-control">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price (₱)</label>
                        <input type="number" id="editCostPrice" class="form-control" step="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Selling Price (₱) *</label>
                        <input type="number" id="editSellingPrice" class="form-control" step="0.01">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Stock</label>
                    <input type="text" id="editCurrentStock" class="form-control" readonly style="background:#f8fafc;">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description / Notes</label>
                    <textarea id="editProductSpecs" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmEditBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Stock In Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-arrow-down"></i> Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="stockProductId">
                <div class="mb-3">
                    <label class="form-label">Product</label>
                    <input type="text" id="stockProductName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Stock</label>
                    <input type="text" id="currentStockDisplay" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity to Add *</label>
                    <input type="number" id="addQuantity" class="form-control" min="1" placeholder="Enter quantity">
                </div>
                <div class="mb-3">
                    <label class="form-label">Cost Price (₱)</label>
                    <input type="number" id="addStockCostPrice" class="form-control" step="0.01" placeholder="Cost per unit">
                </div>
                <div class="mb-3">
                    <label class="form-label">Supplier</label>
                    <input type="text" id="supplierName" class="form-control" placeholder="Supplier name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="stockNotes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmStockBtn">Add to Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/stockindata.php';

let allProducts = [];
let currentCategoryFilter = 'all';
let currentSearchTerm = '';

// Categories for Others page
const otherCategories = ['Supplies', 'Rental', 'Electronic Parts', 'Tools', 'Gadgets', 'Others'];

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

async function loadOtherProducts() {
    const result = await apiCall('getProducts');
    if (result.success && result.data) {
        // Filter only items from other categories (not mobile phones or accessories)
        allProducts = result.data.filter(p => {
            const category = p.Category || '';
            return otherCategories.some(cat => category.includes(cat)) || 
                   (!category.includes('Mobile') && !category.includes('Phone') && 
                    !category.includes('Flagship') && !category.includes('Mid-Range') && 
                    !category.includes('Budget') && !category.includes('Audio') && 
                    !category.includes('Charger') && !category.includes('Case') && 
                    !category.includes('Screen') && !category.includes('Powerbank') && 
                    !category.includes('Cable'));
        });
        calculateStats();
        filterAndDisplayProducts();
    } else {
        document.getElementById('productsGrid').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No items found</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus"></i> Add First Item
                </button>
            </div>
        `;
    }
}

async function addProduct() {
    const productName = document.getElementById('addProductName').value.trim();
    const category = document.getElementById('addProductCategory').value;
    const sellingPrice = parseFloat(document.getElementById('addSellingPrice').value) || 0;
    const initialStock = parseInt(document.getElementById('addInitialStock').value) || 0;
    const costPrice = parseFloat(document.getElementById('addCostPrice').value) || 0;
    const brand = document.getElementById('addProductBrand').value;
    const productCode = document.getElementById('addProductCode').value || 'MISC-' + Date.now();
    
    if (!productName || !category || sellingPrice <= 0) {
        showToast('Please fill in required fields', 'warning');
        return;
    }
    
    const data = {
        product_name: productName,
        brand: brand,
        category: category,
        selling_price: sellingPrice,
        cost_price: costPrice,
        initial_stock: initialStock,
        product_code: productCode,
        notes: document.getElementById('addProductSpecs').value
    };
    
    const result = await apiCall('addProduct', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
        clearAddForm();
        await loadOtherProducts();
    } else {
        showToast(result.message || 'Failed to add item', 'error');
    }
}

async function updateProduct() {
    const productId = document.getElementById('editProductId').value;
    const productName = document.getElementById('editProductName').value.trim();
    const category = document.getElementById('editProductCategory').value;
    const sellingPrice = parseFloat(document.getElementById('editSellingPrice').value) || 0;
    const costPrice = parseFloat(document.getElementById('editCostPrice').value) || 0;
    const brand = document.getElementById('editProductBrand').value;
    const productCode = document.getElementById('editProductCode').value;
    const notes = document.getElementById('editProductSpecs').value;
    
    if (!productId || !productName || !category || sellingPrice <= 0) {
        showToast('Please fill in required fields', 'warning');
        return;
    }
    
    const data = {
        product_id: parseInt(productId),
        product_name: productName,
        brand: brand,
        category: category,
        selling_price: sellingPrice,
        cost_price: costPrice,
        product_code: productCode,
        notes: notes
    };
    
    const result = await apiCall('updateProduct', 'PUT', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
        await loadOtherProducts();
    } else {
        showToast(result.message || 'Failed to update item', 'error');
    }
}

async function addStock() {
    const productId = document.getElementById('stockProductId').value;
    const quantity = parseInt(document.getElementById('addQuantity').value) || 0;
    const costPrice = parseFloat(document.getElementById('addStockCostPrice').value) || 0;
    const supplier = document.getElementById('supplierName').value;
    const notes = document.getElementById('stockNotes').value;
    
    if (!productId || quantity <= 0) {
        showToast('Please enter valid quantity', 'warning');
        return;
    }
    
    const data = {
        product_id: parseInt(productId),
        quantity: quantity,
        cost_price: costPrice,
        supplier: supplier,
        notes: notes
    };
    
    const result = await apiCall('addStock', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('stockModal')).hide();
        await loadOtherProducts();
    } else {
        showToast(result.message || 'Failed to add stock', 'error');
    }
}

// ============================================
// CALCULATIONS
// ============================================

function calculateStats() {
    const totalProducts = allProducts.length;
    
    let totalValue = 0;
    for (let i = 0; i < allProducts.length; i++) {
        const stock = parseFloat(allProducts[i].CurrentStock) || 0;
        const price = parseFloat(allProducts[i].SellingPrice) || 0;
        totalValue += stock * price;
    }
    
    let lowStockCount = 0;
    for (let i = 0; i < allProducts.length; i++) {
        const stock = parseFloat(allProducts[i].CurrentStock) || 0;
        if (stock < 10 && stock > 0) {
            lowStockCount++;
        }
    }
    
    let totalUnits = 0;
    for (let i = 0; i < allProducts.length; i++) {
        totalUnits += parseFloat(allProducts[i].CurrentStock) || 0;
    }
    
    document.getElementById('totalProducts').innerText = totalProducts;
    document.getElementById('totalValue').innerText = '₱' + formatNumber(totalValue);
    document.getElementById('lowStockCount').innerText = lowStockCount;
    document.getElementById('totalUnits').innerText = totalUnits;
}

// ============================================
// FILTER AND DISPLAY
// ============================================

function filterAndDisplayProducts() {
    let filtered = [...allProducts];
    
    if (currentSearchTerm) {
        filtered = filtered.filter(p => 
            p.ProductName.toLowerCase().includes(currentSearchTerm) ||
            (p.Brand && p.Brand.toLowerCase().includes(currentSearchTerm)) ||
            (p.ProductCode && p.ProductCode.toLowerCase().includes(currentSearchTerm)) ||
            (p.Category && p.Category.toLowerCase().includes(currentSearchTerm))
        );
    }
    
    if (currentCategoryFilter !== 'all') {
        filtered = filtered.filter(p => p.Category === currentCategoryFilter);
    }
    
    displayProducts(filtered);
}

function displayProducts(products) {
    const container = document.getElementById('productsGrid');
    
    if (products.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>No items found</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = products.map(product => {
        const currentStock = parseFloat(product.CurrentStock) || 0;
        const sellingPrice = parseFloat(product.SellingPrice) || 0;
        const isLowStock = currentStock < 10 && currentStock > 0;
        const isOutStock = currentStock === 0;
        const stockClass = isOutStock ? 'stock-low' : (isLowStock ? 'stock-low' : 'stock-normal');
        let stockText = '';
        if (isOutStock) {
            stockText = 'Out of Stock';
        } else if (isLowStock) {
            stockText = `Only ${currentStock} left!`;
        } else {
            stockText = `${currentStock} units`;
        }
        
        // Get icon based on category
        let iconClass = 'fa-microchip';
        const category = product.Category || '';
        if (category.includes('Supplies ')) iconClass = 'fa-box-open';
        else if (category.includes('Rental')) iconClass = 'fa-key'; 
        else if (category.includes('Electronic')) iconClass = 'fa-microchip';
        else if (category.includes('Tool')) iconClass = 'fa-tools';
        else if (category.includes('Gadget')) iconClass = 'fa-tablet-alt';
        else iconClass = 'fa-box';
        
        return `
            <div class="product-card">
                ${isLowStock ? `<div class="product-badge"><span class="badge-low-stock">Low Stock!</span></div>` : ''}
                ${isOutStock ? `<div class="product-badge"><span class="badge-out-stock">Out of Stock</span></div>` : ''}
                <div class="product-image">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.ProductName)}</div>
                    <div class="product-code">Code: ${product.ProductCode || 'N/A'}</div>
                    <div class="product-brand"><i class="fas fa-tag"></i> ${product.Brand || 'Generic'} | ${product.Category || 'Misc'}</div>
                    <div class="product-price">₱${formatNumber(sellingPrice)}</div>
                    <div class="product-stock">
                        <i class="fas fa-box"></i> Stock: <span class="${stockClass}">${stockText}</span>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="openStockModal(${product.ProductID}, '${escapeHtml(product.ProductName)}', ${currentStock})">
                            <i class="fas fa-plus"></i> Add Stock
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditModal(${product.ProductID})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// ============================================
// MODAL FUNCTIONS
// ============================================

function openStockModal(productId, productName, currentStock) {
    document.getElementById('stockProductId').value = productId;
    document.getElementById('stockProductName').value = productName;
    document.getElementById('currentStockDisplay').value = currentStock + ' units';
    document.getElementById('addQuantity').value = '';
    document.getElementById('addStockCostPrice').value = '';
    document.getElementById('supplierName').value = '';
    document.getElementById('stockNotes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('stockModal'));
    modal.show();
}

function openEditModal(productId) {
    const product = allProducts.find(p => p.ProductID == productId);
    if (!product) return;
    
    document.getElementById('editProductId').value = product.ProductID;
    document.getElementById('editProductName').value = product.ProductName;
    document.getElementById('editProductCategory').value = product.Category || '';
    document.getElementById('editProductBrand').value = product.Brand || '';
    document.getElementById('editProductCode').value = product.ProductCode || '';
    document.getElementById('editCostPrice').value = product.CostPrice || 0;
    document.getElementById('editSellingPrice').value = product.SellingPrice || 0;
    document.getElementById('editCurrentStock').value = product.CurrentStock + ' units';
    document.getElementById('editProductSpecs').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function clearAddForm() {
    document.getElementById('addProductName').value = '';
    document.getElementById('addProductCategory').value = '';
    document.getElementById('addProductBrand').value = '';
    document.getElementById('addProductCode').value = '';
    document.getElementById('addCostPrice').value = '';
    document.getElementById('addSellingPrice').value = '';
    document.getElementById('addInitialStock').value = '0';
    document.getElementById('addProductSpecs').value = '';
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

document.getElementById('searchInput').addEventListener('input', function(e) {
    currentSearchTerm = e.target.value.toLowerCase();
    filterAndDisplayProducts();
});

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentCategoryFilter = this.dataset.category;
        filterAndDisplayProducts();
    });
});

document.getElementById('confirmAddBtn').addEventListener('click', addProduct);
document.getElementById('confirmEditBtn').addEventListener('click', updateProduct);
document.getElementById('confirmStockBtn').addEventListener('click', addStock);

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadOtherProducts();
});
</script>