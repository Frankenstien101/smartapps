<?php
// pages/mobile-phones.php - Mobile Phones Inventory Page with Serialized Support
?>
<style>
    .mobile-phones-container {
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
        max-height: 150px;
        overflow-y: auto;
        padding: 5px;
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
    
    .badge-serialized {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #4f9eff;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        z-index: 1;
    }
    
    .product-image {
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }
    
    .product-image.default-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
    
    /* Units List */
    .units-list {
        margin-top: 10px;
        border-top: 1px solid #eef2f7;
        padding-top: 8px;
        max-height: 115px;
        overflow-y: auto;
    }
    
    .unit-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #eef2f7;
        font-size: 10px;
    }
    
    .unit-number {
        font-weight: 600;
        color: #4f9eff;
    }
    
    .unit-status {
        font-size: 9px;
        padding: 2px 6px;
        border-radius: 10px;
    }
    
    .unit-status.available { background: #d4edda; color: #155724; }
    .unit-status.sold { background: #f8d7da; color: #721c24; }
    .unit-status.transferred { background: #fff3cd; color: #856404; }
    
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
    
    /* Units Table */
    .units-table {
        font-size: 12px;
    }
    
    .units-table th {
        background: #f8fafc;
        font-size: 11px;
    }
    
    .badge-available { background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; }
    .badge-sold { background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; }
    .badge-transferred { background: #ffc107; color: #212529; padding: 2px 8px; border-radius: 12px; font-size: 10px; }
    
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
        left: auto;
        z-index: 1100;
    }
    
    .toast {
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        border-left: 4px solid;
        min-width: 250px;
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
        
        .toast-container {
            left: 20px;
            right: 20px;
        }
    }
</style>

<div class="mobile-phones-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-mobile-alt"></i> Mobile Phones</h4>
            <p class="text-muted mb-0">Manage your mobile phone inventory with IMEI/Serial tracking</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-box"></i></div>
            <div class="stat-value" id="totalPhones">0</div>
            <div class="stat-label">Total Phones</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-dollar-sign"></i></div>
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
            <input type="text" id="searchInput" placeholder="Search by name, brand, code, IMEI, or Serial...">
        </div>
        <div class="filter-buttons" id="brandFilterContainer">
            <button class="filter-btn active" data-brand="all">All Brands</button>
            <!-- Dynamic brand buttons will be loaded here -->
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="products-grid" id="productsGrid">
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="mt-2">Loading products...</p>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Mobile Phone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Product Name *</label>
                    <input type="text" id="addProductName" class="form-control" placeholder="e.g., iPhone 15 Pro">
                </div>
                <div class="mb-3">
                    <label class="form-label">Brand *</label>
                    <select id="addProductBrand" class="form-select">
                        <option value="">Select Brand</option>
                    </select>
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
                    <label class="form-label">Initial Stock *</label>
                    <input type="number" id="addInitialStock" class="form-control" value="1" min="1">
                    <small class="text-muted">Each unit will have its own IMEI/Serial for tracking</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">IMEI Number (for first unit)</label>
                    <input type="text" id="addIMEI" class="form-control" placeholder="15-digit IMEI">
                </div>
                <div class="mb-3">
                    <label class="form-label">Serial Number (for first unit)</label>
                    <input type="text" id="addSerial" class="form-control" placeholder="Serial number">
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select id="addProductCategory" class="form-select">
                        <option value="Mobile Phones">Mobile Phones</option>
                        <option value="Flagship">Flagship</option>
                        <option value="Mid-Range">Mid-Range</option>
                        <option value="Budget">Budget</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specifications (Optional)</label>
                    <textarea id="addProductSpecs" class="form-control" rows="2" placeholder="RAM, Storage, Camera, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmAddBtn">Add Product</button>
            </div>
        </div>
    </div>
</div>

<!-- UNITS MODAL (for viewing serialized items) -->
<div class="modal fade" id="unitsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list"></i> Units - <span id="unitsProductName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm units-table">
                        <thead>
                            <tr>
                                <th>Unit #</th>
                                <th>IMEI Number</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody id="unitsTableBody">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                     </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/productdata.php';
const BRAND_API_URL = '/SIDJAN/datafetcher/branddata.php';

let allProducts = [];
let allUnits = {};
let allBrands = [];
let currentBrandFilter = 'all';
let currentSearchTerm = '';

// ============================================
// API CALLS
// ============================================

async function apiCall(url, action, method = 'GET', data = null) {
    try {
        const options = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (data) options.body = JSON.stringify(data);
        const response = await fetch(`${url}?action=${action}`, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false };
    }
}

async function loadBrands() {
    const result = await apiCall(BRAND_API_URL, 'getBrands');
    if (result.success && result.data) {
        allBrands = result.data;
        renderBrandFilters();
        updateBrandDropdown();
    }
}

function renderBrandFilters() {
    const container = document.getElementById('brandFilterContainer');
    if (!container) return;
    
    let html = '<button class="filter-btn active" data-brand="all">All Brands</button>';
    
    allBrands.forEach(brand => {
        html += `<button class="filter-btn" data-brand="${escapeHtml(brand.BrandName)}">${escapeHtml(brand.BrandName)}</button>`;
    });
    
    container.innerHTML = html;
    
    // Re-attach event listeners
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentBrandFilter = this.dataset.brand;
            filterAndDisplayProducts();
        });
    });
}

function updateBrandDropdown() {
    const brandSelect = document.getElementById('addProductBrand');
    if (!brandSelect) return;
    
    brandSelect.innerHTML = '<option value="">Select Brand</option>';
    allBrands.forEach(brand => {
        brandSelect.innerHTML += `<option value="${escapeHtml(brand.BrandName)}">${escapeHtml(brand.BrandName)}</option>`;
    });
}

async function loadMobilePhones() {
    const result = await apiCall(API_URL, 'getProducts');
    if (result.success && result.data) {
        // Filter only mobile phones
        allProducts = result.data.filter(p => p.Category === 'Mobile Phones' || p.Category === 'Flagship' || p.Category === 'Mid-Range' || p.Category === 'Budget');
        
        // Load units for each product
        for (let product of allProducts) {
            await loadProductUnits(product.ProductID);
        }
        
        calculateStats();
        filterAndDisplayProducts();
    } else {
        document.getElementById('productsGrid').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No mobile phones found</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus"></i> Add First Phone
                </button>
            </div>
        `;
    }
}

async function loadProductUnits(productId) {
    try {
        const result = await apiCall(API_URL, `getProductUnits&product_id=${productId}`);
        if (result.success && result.data) {
            allUnits[productId] = Array.isArray(result.data) ? result.data : [];
        } else {
            allUnits[productId] = [];
        }
    } catch (error) {
        allUnits[productId] = [];
    }
}

async function addProduct() {
    const productName = document.getElementById('addProductName').value.trim();
    const brand = document.getElementById('addProductBrand').value;
    const sellingPrice = parseFloat(document.getElementById('addSellingPrice').value) || 0;
    const initialStock = parseInt(document.getElementById('addInitialStock').value) || 1;
    const costPrice = parseFloat(document.getElementById('addCostPrice').value) || 0;
    const category = document.getElementById('addProductCategory').value;
    const productCode = document.getElementById('addProductCode').value || 'PH-' + Date.now();
    const imei = document.getElementById('addIMEI').value.trim();
    const serial = document.getElementById('addSerial').value.trim();
    
    if (!productName || !brand || sellingPrice <= 0 || initialStock <= 0) {
        showToast('Please fill in all required fields', 'warning');
        return;
    }
    
    // Create units array
    const units = [];
    for (let i = 1; i <= initialStock; i++) {
        units.push({
            unit_number: i,
            imei: i === 1 ? imei : '',
            serial: i === 1 ? serial : ''
        });
    }
    
    const data = {
        product_name: productName,
        brand: brand,
        category: category,
        selling_price: sellingPrice,
        cost_price: costPrice,
        product_code: productCode,
        units: units,
        notes: document.getElementById('addProductSpecs').value
    };
    
    const result = await apiCall(API_URL, 'addProduct', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
        clearAddForm();
        await loadMobilePhones();
    } else {
        showToast(result.message || 'Failed to add product', 'error');
    }
}

async function viewUnits(productId, productName) {
    document.getElementById('unitsProductName').innerText = productName;
    document.getElementById('unitsTableBody').innerHTML = '<tr><td colspan="5" class="text-center"><div class="loading-spinner"></div> Loading units...<\/td><\/tr>';
    
    const modal = new bootstrap.Modal(document.getElementById('unitsModal'));
    modal.show();
    
    const result = await apiCall(API_URL, `getProductUnits&product_id=${productId}`);
    
    if (result.success && result.data) {
        const units = Array.isArray(result.data) ? result.data : [];
        
        if (units.length === 0) {
            document.getElementById('unitsTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-muted">No units found<\/td><\/tr>';
        } else {
            document.getElementById('unitsTableBody').innerHTML = units.map(unit => {
                let statusClass = '';
                let statusText = unit.Status || 'available';
                if (statusText === 'available') statusClass = 'badge-available';
                else if (statusText === 'sold') statusClass = 'badge-sold';
                else if (statusText === 'transferred') statusClass = 'badge-transferred';
                
                return `
                    <tr>
                        <td>#${unit.UnitNumber || '-'}<\/td>
                        <td><small>${unit.IMEINumber || '-'}<\/small><\/td>
                        <td><small>${unit.SerialNumber || '-'}<\/small><\/td>
                        <td><span class="${statusClass}">${statusText.toUpperCase()}<\/span><\/td>
                        <td><small>${unit.CreatedAt ? unit.CreatedAt.split(' ')[0] : '-'}<\/small><\/td>
                    <\/tr>
                `;
            }).join('');
        }
    } else {
        document.getElementById('unitsTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load units<\/td><\/tr>';
    }
}

// ============================================
// CALCULATIONS
// ============================================

function calculateStats() {
    const totalPhones = allProducts.length;
    
    let totalValue = 0;
    for (let i = 0; i < allProducts.length; i++) {
        const product = allProducts[i];
        const units = allUnits[product.ProductID] || [];
        const stock = units.length > 0 ? units.length : (parseFloat(product.CurrentStock) || 0);
        const price = parseFloat(product.SellingPrice) || 0;
        totalValue += stock * price;
    }
    
    let lowStockCount = 0;
    for (let i = 0; i < allProducts.length; i++) {
        const product = allProducts[i];
        const units = allUnits[product.ProductID] || [];
        const stock = units.length > 0 ? units.length : (parseFloat(product.CurrentStock) || 0);
        if (stock < 10 && stock > 0) {
            lowStockCount++;
        }
    }
    
    let totalUnits = 0;
    for (let i = 0; i < allProducts.length; i++) {
        const product = allProducts[i];
        const units = allUnits[product.ProductID] || [];
        totalUnits += units.length > 0 ? units.length : (parseFloat(product.CurrentStock) || 0);
    }
    
    document.getElementById('totalPhones').innerText = totalPhones;
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
            (p.ProductCode && p.ProductCode.toLowerCase().includes(currentSearchTerm))
        );
    }
    
    if (currentBrandFilter !== 'all') {
        filtered = filtered.filter(p => p.Brand === currentBrandFilter);
    }
    
    displayProducts(filtered);
}

function getProductImageHtml(product) {
    if (product.ProductImagePath && product.ProductImagePath !== '') {
        return `<img src="${product.ProductImagePath}" 
                       alt="${escapeHtml(product.ProductName)}" 
                       onerror="this.parentElement.classList.add('default-bg'); this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-mobile-alt\'></i>';">`;
    } else {
        return `<i class="fas fa-mobile-alt"></i>`;
    }
}

function displayProducts(products) {
    const container = document.getElementById('productsGrid');
    
    if (products.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>No products found</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = products.map(product => {
        const units = allUnits[product.ProductID] || [];
        const hasUnits = units.length > 0;
        const currentStock = hasUnits ? units.length : (parseFloat(product.CurrentStock) || 0);
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
        
        const hasImage = product.ProductImagePath && product.ProductImagePath !== '';
        const imageHtml = getProductImageHtml(product);
        
        // Show first few units
        let unitsHtml = '';
        if (hasUnits && units.length > 0) {
            const displayUnits = units.slice(0, 3);
            unitsHtml = `
                <div class="units-list">
                    ${displayUnits.map(unit => `
                        <div class="unit-item">
                            <span class="unit-number">Unit #${unit.UnitNumber}</span>
                            <span class="unit-status ${unit.Status === 'available' ? 'available' : (unit.Status === 'sold' ? 'sold' : 'transferred')}">${unit.Status || 'available'}</span>
                        </div>
                    `).join('')}
                    ${units.length > 3 ? `<div class="unit-item text-muted">+ ${units.length - 3} more units...</div>` : ''}
                </div>
            `;
        }
        
        return `
            <div class="product-card">
                ${isLowStock ? `<div class="product-badge"><span class="badge-low-stock">Low Stock!</span></div>` : ''}
                ${isOutStock ? `<div class="product-badge"><span class="badge-out-stock">Out of Stock</span></div>` : ''}
                ${hasUnits ? `<div class="badge-serialized"><i class="fas fa-microchip"></i> Serialized</div>` : ''}
                <div class="product-image ${!hasImage ? 'default-bg' : ''}" style="${hasImage ? `background-image: url('${product.ProductImagePath}'); background-size: cover; background-position: center;` : ''}">
                    ${imageHtml}
                </div>
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.ProductName)}</div>
                    <div class="product-code">Code: ${product.ProductCode || 'N/A'}</div>
                    <div class="product-brand"><i class="fas fa-tag"></i> ${product.Brand || 'Unknown'}</div>
                    <div class="product-price">₱${formatNumber(sellingPrice)}</div>
                    <div class="product-stock">
                        <i class="fas fa-box"></i> Stock: <span class="${stockClass}">${stockText}</span>
                    </div>
                    ${unitsHtml}
                    <div class="product-actions">
                        ${hasUnits ? `
                            <button class="btn btn-sm btn-outline-info" onclick="viewUnits(${product.ProductID}, '${escapeHtml(product.ProductName)}')">
                                <i class="fas fa-list"></i> View Units
                            </button>
                        ` : ''}
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

function openEditModal(productId) {
    const product = allProducts.find(p => p.ProductID == productId);
    if (!product) return;
    
    // Build dynamic edit modal
    const modalHtml = `
        <div class="modal fade" id="dynamicEditModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Mobile Phone</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editProductId" value="${product.ProductID}">
                        <div class="mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" id="editProductName" class="form-control" value="${escapeHtml(product.ProductName)}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand *</label>
                            <select id="editProductBrand" class="form-select">
                                <option value="">Select Brand</option>
                                ${allBrands.map(brand => `<option value="${escapeHtml(brand.BrandName)}" ${product.Brand === brand.BrandName ? 'selected' : ''}>${escapeHtml(brand.BrandName)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Code</label>
                            <input type="text" id="editProductCode" class="form-control" value="${product.ProductCode || ''}">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost Price (₱)</label>
                                <input type="number" id="editCostPrice" class="form-control" step="0.01" value="${product.CostPrice || 0}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Selling Price (₱) *</label>
                                <input type="number" id="editSellingPrice" class="form-control" step="0.01" value="${product.SellingPrice || 0}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select id="editProductCategory" class="form-select">
                                <option value="Mobile Phones" ${product.Category === 'Mobile Phones' ? 'selected' : ''}>Mobile Phones</option>
                                <option value="Flagship" ${product.Category === 'Flagship' ? 'selected' : ''}>Flagship</option>
                                <option value="Mid-Range" ${product.Category === 'Mid-Range' ? 'selected' : ''}>Mid-Range</option>
                                <option value="Budget" ${product.Category === 'Budget' ? 'selected' : ''}>Budget</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specifications</label>
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
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('dynamicEditModal');
    if (existingModal) existingModal.remove();
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('dynamicEditModal'));
    modal.show();
    
    // Add event listener for save
    document.getElementById('confirmEditBtn').addEventListener('click', async function() {
        const data = {
            product_id: parseInt(document.getElementById('editProductId').value),
            product_name: document.getElementById('editProductName').value.trim(),
            brand: document.getElementById('editProductBrand').value,
            category: document.getElementById('editProductCategory').value,
            product_code: document.getElementById('editProductCode').value,
            cost_price: parseFloat(document.getElementById('editCostPrice').value) || 0,
            selling_price: parseFloat(document.getElementById('editSellingPrice').value) || 0,
            description: document.getElementById('editProductSpecs').value
        };
        
        if (!data.product_name || !data.brand || data.selling_price <= 0) {
            showToast('Please fill in required fields', 'warning');
            return;
        }
        
        const result = await apiCall(API_URL, 'updateProduct', 'PUT', data);
        if (result.success) {
            showToast(result.message, 'success');
            modal.hide();
            await loadMobilePhones();
        } else {
            showToast(result.message || 'Failed to update product', 'error');
        }
    });
}

function clearAddForm() {
    document.getElementById('addProductName').value = '';
    document.getElementById('addProductCode').value = '';
    document.getElementById('addCostPrice').value = '';
    document.getElementById('addSellingPrice').value = '';
    document.getElementById('addInitialStock').value = '1';
    document.getElementById('addProductCategory').value = 'Mobile Phones';
    document.getElementById('addIMEI').value = '';
    document.getElementById('addSerial').value = '';
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

let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentSearchTerm = e.target.value.toLowerCase();
        filterAndDisplayProducts();
    }, 300);
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadBrands();
    loadMobilePhones();
});
</script>