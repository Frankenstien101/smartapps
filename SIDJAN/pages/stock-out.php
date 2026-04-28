<?php
// pages/stock-out.php - Stock Out Page (Direct - No Approval) with Unit Selection
?>
<style>
    .stockout-container {
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
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .search-box {
        position: relative;
        flex: 2;
        min-width: 200px;
    }
    
    .search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
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
    
    .btn-success {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-outline-secondary {
        background: transparent;
        border: 1px solid #6c757d;
        color: #6c757d;
        padding: 6px 12px;
        border-radius: 8px;
        cursor: pointer;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .product-card {
        background: white;
        border-radius: 16px;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
        position: relative;
    }
    
    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .product-name {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 5px;
    }
    
    .product-code {
        font-size: 11px;
        color: #6c7a91;
        font-family: monospace;
        margin-bottom: 8px;
    }
    
    .product-stock {
        font-size: 12px;
        margin-bottom: 8px;
    }
    
    .stock-normal { color: #28a745; }
    .stock-low { color: #dc3545; font-weight: 600; }
    
    .product-price {
        font-size: 16px;
        font-weight: 800;
        color: #4f9eff;
        margin-bottom: 10px;
    }
    
    .product-imei, .product-serial {
        font-size: 10px;
        font-family: monospace;
        color: #6c7a91;
        margin-top: 3px;
    }
    
    .units-list {
        margin-top: 10px;
        border-top: 1px solid #eef2f7;
        padding-top: 10px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .unit-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 5px;
        border-bottom: 1px solid #eef2f7;
        font-size: 11px;
    }
    
        .units-list-container {
        position: relative;
        margin-top: 10px;
        border-top: 1px solid #eef2f7;
        padding-top: 10px;
    }
    
    .units-scrollable {
        max-height: 200px;
        overflow-y: auto;
        margin-bottom: 10px;
    }
    
    .units-scrollable::-webkit-scrollbar {
        width: 6px;
    }
    
    .units-scrollable::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .units-scrollable::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .units-scrollable::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    .fixed-add-button {
        position: sticky;
        bottom: 0;
        background: white;
        padding-top: 10px;
        margin-top: 10px;
        border-top: 1px solid #eef2f7;
        z-index: 10;
    }
    
    .select-btn {
        width: 100%;
        padding: 10px;
        background: #4f9eff;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .select-btn:hover {
        background: #3b8adf;
        transform: translateY(-1px);
    }
    
    .select-btn.selected {
        background: #dc3545;
    }
    
    .select-btn.selected:hover {
        background: #c82333;
    }

    .unit-checkbox {
        margin-right: 10px;
    }
    
    .unit-info {
        flex: 1;
    }
    
    .unit-number {
        font-weight: 600;
        color: #4f9eff;
    }
    
    .unit-select-btn {
        background: #4f9eff;
        color: white;
        border: none;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 10px;
        cursor: pointer;
    }
    
    .quantity-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 10px;
    }
    
    .select-btn {
        width: 100%;
        padding: 8px;
        background: #4f9eff;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
    }
    
    .select-btn.selected {
        background: #dc3545;
    }
    
    .cart-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
    
    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #eef2f7;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .cart-item-name {
        font-weight: 600;
        flex: 2;
        min-width: 120px;
    }
    
    .cart-item-qty {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 100px;
        justify-content: center;
    }
    
    .btn-qty {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
    }
    
    .btn-qty:hover {
        background: #4f9eff;
        color: white;
        border-color: #4f9eff;
    }
    
    .qty-number {
        font-weight: 600;
        min-width: 30px;
        text-align: center;
    }
    
    .cart-item-price {
        min-width: 90px;
        text-align: right;
    }
    
    .cart-item-total {
        min-width: 100px;
        text-align: right;
        font-weight: 700;
    }
    
    .cart-item-remove {
        min-width: 40px;
        text-align: center;
        color: #dc3545;
        cursor: pointer;
        font-size: 16px;
    }
    
    .cart-total {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        font-size: 18px;
        font-weight: 800;
    }
    
    .reason-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
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
    .toast.warning { border-left-color: #ffc107; }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .products-grid {
            grid-template-columns: 1fr;
        }
        .filter-section {
            flex-direction: column;
        }
        .search-box {
            width: 100%;
        }
    }
</style>

<div class="stockout-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-arrow-up"></i> Stock Out</h4>
            <p class="text-muted mb-0">Select products/units to release from stock</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-boxes"></i></div>
            <div class="stat-value" id="totalProducts">0</div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="selectedCount">0</div>
            <div class="stat-label">Selected Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalQuantity">0</div>
            <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-value" id="totalAmount">₱0</div>
            <div class="stat-label">Total Amount</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search products by name, code, IMEI, or serial...">
        </div>
        <button class="btn-primary" onclick="loadProducts()">Refresh</button>
    </div>
    
    <!-- Products Grid -->
    <div class="products-grid" id="productsGrid">
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="mt-2">Loading products...</p>
        </div>
    </div>
    
    <!-- Selected Items Cart -->
    <div class="cart-section" id="cartSection" style="display: none;">
        <h5><i class="fas fa-shopping-cart"></i> Selected Items</h5>
        <div id="cartItems"></div>
        <div class="cart-total">
            <span>GRAND TOTAL:</span>
            <span id="grandTotal">₱0.00</span>
        </div>
    </div>
    
    <!-- Reason Section -->
    <div class="reason-section" id="reasonSection" style="display: none;">
        <h5><i class="fas fa-info-circle"></i> Release Information</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Reason for Release *</label>
                <select id="releaseReason" class="form-select">
                    <option value="">Select Reason</option>
                    <option value="Sales">Sales / Customer Order</option>
                    <option value="Return to Supplier">Return to Supplier</option>
                    <option value="Damaged">Damaged / Defective</option>
                    <option value="Expired">Expired Products</option>
                    <option value="Internal Use">Internal Use</option>
                    <option value="Transfer">Stock Transfer</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Department/Requested By</label>
                <input type="text" id="requestDepartment" class="form-control" placeholder="Department name">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Notes</label>
                <textarea id="releaseNotes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
            </div>
        </div>
        <button class="btn btn-success w-100" id="submitReleaseBtn" onclick="processStockOut()">
            <i class="fas fa-check-circle"></i> Process Stock Out
        </button>
    </div>
</div>

<script>
// API Configuration
// API Configuration
const API_URL = '/SIDJAN/datafetcher/stockoutdata.php';
const PRODUCT_API_URL = '/SIDJAN/datafetcher/productdata.php';

let allProducts = [];
let allUnits = {};
let selectedItems = [];

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

async function loadProducts() {
    const search = document.getElementById('searchInput').value;
    // Fix: Pass search parameter to API
    const result = await apiCall(PRODUCT_API_URL, `getProducts&search=${encodeURIComponent(search)}`, 'GET');
    
    if (result.success && result.data) {
        allProducts = result.data;
        
        // Load units for each product that might have serialized items
        for (let product of allProducts) {
            await loadProductUnits(product.ProductID);
        }
        
        displayProducts(allProducts);
        updateStats();
    } else {
        document.getElementById('productsGrid').innerHTML = '<div class="text-center py-5 text-muted">No products found</div>';
    }
}

async function loadProductUnits(productId) {
    try {
        const result = await apiCall(PRODUCT_API_URL, `getProductUnits&product_id=${productId}`, 'GET');
        if (result.success && result.data) {
            // Fix: Check if result.data is array or has units property
            const units = Array.isArray(result.data) ? result.data : (result.data.units || []);
            allUnits[productId] = units.filter(u => u.Status === 'Available' || u.Status === 'available');
        } else {
            allUnits[productId] = [];
        }
    } catch (error) {
        console.error('Error loading units:', error);
        allUnits[productId] = [];
    }
}

async function processStockOut() {
    const reason = document.getElementById('releaseReason').value;
    const department = document.getElementById('requestDepartment').value;
    const notes = document.getElementById('releaseNotes').value;
    
    if (!reason) {
        showToast('Please select a reason', 'warning');
        return;
    }
    
    if (selectedItems.length === 0) {
        showToast('No items selected', 'warning');
        return;
    }
    
    const submitBtn = document.getElementById('submitReleaseBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const data = {
        reason: reason,
        department: department,
        notes: notes,
        items: selectedItems.map(item => ({
            product_id: item.id,
            quantity: item.isBulk ? item.quantity : item.units.length,
            units: item.isBulk ? [] : item.units.map(u => u.unitId)
        }))
    };
    
    const result = await apiCall(API_URL, 'processStockOut', 'POST', data);
    
    if (result.success) {
        showToast(result.message, 'success');
        
        // Clear selection
        selectedItems = [];
        updateCartDisplay();
        updateStats();
        
        // Clear form
        document.getElementById('releaseReason').value = '';
        document.getElementById('requestDepartment').value = '';
        document.getElementById('releaseNotes').value = '';
        document.getElementById('reasonSection').style.display = 'none';
        
        // Reload products to show updated stock
        await loadProducts();
    } else {
        showToast(result.message || 'Failed to process stock out', 'error');
    }
    
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Process Stock Out';
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displayProducts(products) {
    const container = document.getElementById('productsGrid');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    let filtered = products;
    if (searchTerm) {
        filtered = products.filter(p => 
            p.ProductName.toLowerCase().includes(searchTerm) ||
            (p.ProductCode && p.ProductCode.toLowerCase().includes(searchTerm)) ||
            (p.Brand && p.Brand.toLowerCase().includes(searchTerm)) ||
            (p.Category && p.Category.toLowerCase().includes(searchTerm))
        );
    }
    
    if (filtered.length === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted">No products found</div>';
        return;
    }
    
    container.innerHTML = filtered.map(product => {
        const availableQty = product.CurrentStock || 0;
        const units = allUnits[product.ProductID] || [];
        const hasUnits = units.length > 0;
        const isSelected = selectedItems.find(i => i.id === product.ProductID);
        
        return `
            <div class="product-card" data-id="${product.ProductID}">
                <div class="product-name">${escapeHtml(product.ProductName)}</div>
                <div class="product-code">Code: ${product.ProductCode || 'N/A'}</div>
                <div class="product-brand">${product.Brand || 'No brand'} | ${product.Category || 'Uncategorized'}</div>
                <div class="product-stock ${availableQty < 10 ? 'stock-low' : 'stock-normal'}">
                    Available: ${availableQty} units
                </div>
                <div class="product-price">₱${formatNumber(product.SellingPrice)}</div>
                
                ${!isSelected ? `
                    ${hasUnits ? `
                        <div class="units-list-container">
                            <div class="units-scrollable">
                                <div class="small text-muted mb-2">Select units to release:</div>
                                ${units.map(unit => `
                                    <div class="unit-item">
                                        <input type="checkbox" class="unit-checkbox" id="unit_${unit.UnitID}" value="${unit.UnitID}" 
                                               data-unit-number="${unit.UnitNumber}" data-imei="${unit.IMEINumber || ''}" data-serial="${unit.SerialNumber || ''}">
                                        <div class="unit-info">
                                            <div class="unit-number">Unit #${unit.UnitNumber}</div>
                                            ${unit.IMEINumber ? `<div class="small">IMEI: ${unit.IMEINumber}</div>` : ''}
                                            ${unit.SerialNumber ? `<div class="small">Serial: ${unit.SerialNumber}</div>` : ''}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="fixed-add-button">
                                <button class="select-btn" onclick="addSelectedUnitsToCart(${product.ProductID}, '${escapeHtml(product.ProductName)}', ${product.SellingPrice})">
                                    <i class="fas fa-cart-plus"></i> Add Selected Units
                                </button>
                            </div>
                        </div>
                    ` : `
                        <input type="number" id="qty_${product.ProductID}" class="quantity-input" placeholder="Quantity" min="1" max="${availableQty}" value="1">
                        <button class="select-btn" onclick="addToCart(${product.ProductID}, '${escapeHtml(product.ProductName)}', ${product.SellingPrice}, ${availableQty})">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    `}
                ` : `
                    <div class="alert alert-success text-center mb-2">
                        <i class="fas fa-check-circle"></i> Added (${isSelected.isBulk ? `Qty: ${isSelected.quantity}` : `Units: ${isSelected.units.length}`})
                    </div>
                    <button class="select-btn selected" onclick="removeFromCart(${product.ProductID})" style="background:#dc3545;">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                `}
            </div>
        `;
    }).join('');
}

function addSelectedUnitsToCart(productId, productName, price) {
    // Fix: Use a more specific selector to find checkboxes within the correct product card
    const productCard = document.querySelector(`.product-card[data-id="${productId}"]`);
    if (!productCard) {
        showToast('Product card not found', 'error');
        return;
    }
    
    // Find all checked checkboxes within this product card
    const checkboxes = productCard.querySelectorAll('.unit-checkbox:checked');
    const selectedUnits = [];
    
    checkboxes.forEach(cb => {
        const unitId = parseInt(cb.value);
        const unitNumber = cb.dataset.unitNumber;
        const imei = cb.dataset.imei;
        const serial = cb.dataset.serial;
        
        selectedUnits.push({ unitId, unitNumber, imei, serial });
    });
    
    if (selectedUnits.length === 0) {
        showToast('Please select at least one unit', 'warning');
        return;
    }
    
    const existing = selectedItems.find(i => i.id === productId);
    if (existing) {
        showToast('Product already in cart. Remove existing to add different units.', 'warning');
        return;
    }
    
    selectedItems.push({
        id: productId,
        name: productName,
        price: price,
        quantity: selectedUnits.length,
        units: selectedUnits,
        isBulk: false
    });
    
    updateCartDisplay();
    displayProducts(allProducts);
    updateStats();
    showToast(`${selectedUnits.length} unit(s) of ${productName} added to cart`, 'success');
}

function addToCart(id, name, price, maxStock) {
    const qtyInput = document.getElementById(`qty_${id}`);
    let quantity = parseInt(qtyInput?.value) || 1;
    
    if (quantity < 1) quantity = 1;
    
    if (quantity > maxStock) {
        showToast(`Only ${maxStock} units available`, 'error');
        return;
    }
    
    const existing = selectedItems.find(i => i.id === id);
    if (existing) {
        showToast('Item already in cart', 'warning');
        return;
    }
    
    selectedItems.push({
        id: id,
        name: name,
        price: price,
        quantity: quantity,
        max_stock: maxStock,
        isBulk: true,
        units: []
    });
    
    updateCartDisplay();
    displayProducts(allProducts);
    updateStats();
    showToast(`${quantity} x ${name} added to cart`, 'success');
}

function updateCartDisplay() {
    const cartSection = document.getElementById('cartSection');
    const reasonSection = document.getElementById('reasonSection');
    const cartContainer = document.getElementById('cartItems');
    
    if (selectedItems.length === 0) {
        cartSection.style.display = 'none';
        reasonSection.style.display = 'none';
        return;
    }
    
    cartSection.style.display = 'block';
    reasonSection.style.display = 'block';
    
    let grandTotal = 0;
    
    cartContainer.innerHTML = selectedItems.map((item, idx) => {
        const total = item.quantity * item.price;
        grandTotal += total;
        
        let unitsInfo = '';
        if (!item.isBulk && item.units && item.units.length > 0) {
            unitsInfo = `<div class="small text-muted mt-1">
                ${item.units.map(u => `Unit #${u.unitNumber} ${u.imei ? `(IMEI: ${u.imei})` : ''} ${u.serial ? `(Serial: ${u.serial})` : ''}`).join(', ')}
            </div>`;
        }
        
        return `
            <div class="cart-item">
                <div class="cart-item-name">
                    ${escapeHtml(item.name)}
                    ${unitsInfo}
                </div>
                <div class="cart-item-qty">
                    ${item.isBulk ? `
                        <button class="btn-qty" onclick="updateQuantity(${item.id}, -1)">-</button>
                        <span class="qty-number">${item.quantity}</span>
                        <button class="btn-qty" onclick="updateQuantity(${item.id}, 1)">+</button>
                    ` : `
                        <span class="qty-number">${item.quantity} unit(s)</span>
                    `}
                </div>
                <div class="cart-item-price">₱${formatNumber(item.price)}</div>
                <div class="cart-item-total">₱${formatNumber(total)}</div>
                <div class="cart-item-remove" onclick="removeFromCart(${item.id})">
                    <i class="fas fa-trash"></i>
                </div>
            </div>
        `;
    }).join('');
    
    document.getElementById('grandTotal').innerHTML = `₱${formatNumber(grandTotal)}`;
    updateStats();
}

function updateQuantity(id, change) {
    const item = selectedItems.find(i => i.id === id);
    if (item && item.isBulk) {
        const newQty = item.quantity + change;
        if (newQty < 1) {
            removeFromCart(id);
        } else if (newQty > item.max_stock) {
            showToast(`Only ${item.max_stock} units available`, 'warning');
        } else {
            item.quantity = newQty;
            updateCartDisplay();
            displayProducts(allProducts);
            updateStats();
        }
    }
}

function removeFromCart(id) {
    selectedItems = selectedItems.filter(i => i.id !== id);
    updateCartDisplay();
    displayProducts(allProducts);
    updateStats();
    showToast('Item removed from cart', 'info');
}

function updateStats() {
    const totalProductsElement = document.getElementById('totalProducts');
    if (totalProductsElement) {
        totalProductsElement.innerText = allProducts.length;
    }
    
    document.getElementById('selectedCount').innerText = selectedItems.length;
    
    // Calculate total quantity including both bulk and serialized items
    const totalQty = selectedItems.reduce((sum, i) => {
        if (i.isBulk) {
            return sum + i.quantity;
        } else {
            return sum + (i.units ? i.units.length : 0);
        }
    }, 0);
    
    const totalAmount = selectedItems.reduce((sum, i) => {
        const qty = i.isBulk ? i.quantity : (i.units ? i.units.length : 0);
        return sum + (qty * i.price);
    }, 0);
    
    document.getElementById('totalQuantity').innerText = totalQty;
    document.getElementById('totalAmount').innerHTML = `₱${formatNumber(totalAmount)}`;
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
    
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px; padding: 8px;">
            <i class="fas ${icons[type] || 'fa-info-circle'}" style="font-size: 18px;"></i>
            <div style="flex: 1;">${message}</div>
            <i class="fas fa-times" style="cursor: pointer;" onclick="this.closest('.toast').remove()"></i>
        </div>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        if (toast && toast.remove) toast.remove();
    }, 3000);
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
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadProducts();
    }, 500);
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
});
</script>