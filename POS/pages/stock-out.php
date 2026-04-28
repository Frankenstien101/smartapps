<?php
// pages/stock-out.php - Stock Out Page (Direct - No Approval)
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
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
        font-size: 13px;
        margin-bottom: 10px;
    }
    
    .stock-normal { color: #28a745; }
    .stock-low { color: #dc3545; font-weight: 600; }
    
    .product-price {
        font-size: 16px;
        font-weight: 800;
        color: #4f9eff;
        margin-bottom: 10px;
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
        transition: all 0.2s;
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
    
    .cart-item-remove:hover {
        color: #bb2d3b;
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
        .cart-item {
            flex-wrap: wrap;
        }
        .cart-item-name {
            flex: 100%;
        }
    }
</style>

<div class="stockout-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-arrow-up"></i> Stock Out</h4>
            <p class="text-muted mb-0">Select products and enter quantity to release from stock</p>
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
            <div class="stat-label">Total Quantity</div>
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
            <input type="text" id="searchInput" placeholder="Search products by name or code...">
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
const API_URL = '/POS/datafetcher/stockoutdata.php';

let allProducts = [];
let selectedItems = [];

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

async function loadProducts() {
    const search = document.getElementById('searchInput').value;
    let url = 'getProducts';
    if (search) {
        url += `&search=${encodeURIComponent(search)}`;
    }
    
    const result = await apiCall(url);
    if (result.success && result.data) {
        allProducts = result.data;
        displayProducts(allProducts);
        updateStats();
    } else {
        document.getElementById('productsGrid').innerHTML = '<div class="text-center py-5 text-muted">No products found</div>';
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
            quantity: item.quantity,
            unit_price: item.price
        }))
    };
    
    const result = await apiCall('processStockOut', 'POST', data);
    
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
    
    if (products.length === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted">No products found</div>';
        return;
    }
    
    container.innerHTML = products.map(product => {
        const isSelected = selectedItems.find(i => i.id === product.ProductID);
        const currentStock = product.CurrentStock || 0;
        const stockClass = currentStock < 10 ? 'stock-low' : 'stock-normal';
        const stockText = currentStock < 10 ? `Low Stock: ${currentStock} left!` : `Stock: ${currentStock} units`;
        
        return `
            <div class="product-card" data-id="${product.ProductID}">
                <div class="product-name">${escapeHtml(product.ProductName)}</div>
                <div class="product-code">Code: ${product.ProductCode || 'N/A'}</div>
                <div class="product-brand"><i class="fas fa-tag"></i> ${product.Brand || 'No brand'} | ${product.Category || 'Uncategorized'}</div>
                <div class="product-stock ${stockClass}">${stockText}</div>
                <div class="product-price">₱${formatNumber(product.SellingPrice)}</div>
                ${!isSelected ? `
                    <input type="number" id="qty_${product.ProductID}" class="quantity-input" placeholder="Quantity" min="1" max="${currentStock}">
                    <button class="select-btn" onclick="addToCart(${product.ProductID}, '${escapeHtml(product.ProductName)}', ${product.SellingPrice}, ${currentStock})">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                ` : `
                    <div class="alert alert-success text-center mb-0">
                        <i class="fas fa-check-circle"></i> Added (Qty: ${isSelected.quantity})
                    </div>
                    <button class="select-btn selected" onclick="removeFromCart(${product.ProductID})" style="background:#dc3545; margin-top:10px;">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                `}
            </div>
        `;
    }).join('');
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
    
    cartContainer.innerHTML = selectedItems.map(item => {
        const total = item.quantity * item.price;
        grandTotal += total;
        return `
            <div class="cart-item">
                <div class="cart-item-name">${escapeHtml(item.name)}</div>
                <div class="cart-item-qty">
                    <button class="btn-qty" onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span class="qty-number">${item.quantity}</span>
                    <button class="btn-qty" onclick="updateQuantity(${item.id}, 1)">+</button>
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

function updateStats() {
    document.getElementById('totalProducts').innerText = allProducts.length;
    document.getElementById('selectedCount').innerText = selectedItems.length;
    
    const totalQty = selectedItems.reduce((sum, i) => sum + i.quantity, 0);
    const totalAmount = selectedItems.reduce((sum, i) => sum + (i.quantity * i.price), 0);
    
    document.getElementById('totalQuantity').innerText = totalQty;
    document.getElementById('totalAmount').innerHTML = `₱${formatNumber(totalAmount)}`;
}

// ============================================
// CART FUNCTIONS
// ============================================

function addToCart(id, name, price, maxStock) {
    const qtyInput = document.getElementById(`qty_${id}`);
    const quantity = parseInt(qtyInput?.value) || 0;
    
    if (quantity <= 0) {
        showToast('Please enter quantity', 'warning');
        return;
    }
    
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
        max_stock: maxStock
    });
    
    updateCartDisplay();
    displayProducts(allProducts);
    showToast(`${name} added to cart`, 'success');
}

function updateQuantity(id, change) {
    const item = selectedItems.find(i => i.id === id);
    if (item) {
        const newQty = item.quantity + change;
        if (newQty < 1) {
            removeFromCart(id);
        } else if (newQty > item.max_stock) {
            showToast(`Only ${item.max_stock} units available`, 'warning');
        } else {
            item.quantity = newQty;
            updateCartDisplay();
            displayProducts(allProducts);
        }
    }
}

function removeFromCart(id) {
    selectedItems = selectedItems.filter(i => i.id !== id);
    updateCartDisplay();
    displayProducts(allProducts);
    showToast('Item removed from cart', 'info');
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

document.getElementById('searchInput').addEventListener('input', function() {
    loadProducts();
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
});
</script>