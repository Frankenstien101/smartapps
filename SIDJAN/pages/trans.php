<?php
// pages/trans.php - Point of Sale Transaction Page
?>
<style>
    .pos-container {
        padding: 0;
        width: 100%;
    }
    
    /* Cart Section */
    .cart-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .cart-header {
        background: #1f2937;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .cart-header h5 {
        margin: 0;
        font-size: 16px;
    }
    
    .cart-items {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #eef2f7;
        transition: background 0.2s;
    }
    
    .cart-item:hover {
        background: #f8fafc;
    }
    
    .cart-item-info {
        flex: 1;
    }
    
    .cart-item-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 3px;
    }
    
    .cart-item-code {
        font-size: 10px;
        color: #6c7a91;
        font-family: monospace;
    }
    
    .cart-item-price {
        font-size: 12px;
        color: #6c7a91;
    }
    
    .cart-item-qty {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .qty-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .qty-btn:hover {
        background: #4f9eff;
        color: white;
        border-color: #4f9eff;
    }
    
    .cart-item-qty span {
        min-width: 30px;
        text-align: center;
        font-weight: 600;
    }
    
    .cart-item-total {
        font-weight: 700;
        min-width: 80px;
        text-align: right;
    }
    
    .remove-item {
        color: #dc3545;
        cursor: pointer;
        margin-left: 10px;
        font-size: 14px;
    }
    
    .remove-item:hover {
        color: #bb2d3b;
    }
    
    .cart-summary {
        background: #f8fafc;
        padding: 15px 20px;
        border-top: 1px solid #eef2f7;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .summary-row.total {
        font-size: 18px;
        font-weight: 800;
        color: #1a2a3a;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        margin-top: 5px;
    }
    
    .tax-info {
        font-size: 11px;
        color: #6c7a91;
        text-align: right;
        margin-top: 5px;
    }
    
    /* Product Search Section */
    .product-search-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .search-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eef2f7;
    }
    
    .product-search-input {
        position: relative;
    }
    
    .product-search-input input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .product-search-input i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .barcode-input {
        margin-top: 10px;
    }
    
    .barcode-input input {
        font-family: monospace;
        font-size: 16px;
        letter-spacing: 1px;
    }
    
    .product-results {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .product-result-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .product-result-item:hover {
        background: #eef2ff;
    }
    
    .product-result-name {
        font-weight: 600;
        font-size: 14px;
    }
    
    .product-result-code {
        font-size: 10px;
        color: #6c7a91;
        font-family: monospace;
    }
    
    .product-result-price {
        font-size: 13px;
        color: #4f9eff;
        font-weight: 600;
    }
    
    .product-result-stock {
        font-size: 11px;
        color: #6c7a91;
    }
    
    /* Customer Section */
    .customer-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .customer-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eef2f7;
    }
    
    /* Payment Section */
    .payment-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .payment-body {
        padding: 20px;
    }
    
    .payment-methods {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .payment-method-btn {
        flex: 1;
        padding: 10px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .payment-method-btn.active {
        background: #4f9eff;
        color: white;
        border-color: #4f9eff;
    }
    
    .checkout-btn {
        background: #28a745;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px;
        font-weight: 600;
        width: 100%;
        font-size: 16px;
        transition: all 0.2s;
    }
    
    .checkout-btn:hover {
        background: #218838;
    }
    
    .checkout-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }
    
    .checkout-btn.loading {
        opacity: 0.7;
        pointer-events: none;
    }
    
    /* Receipt Modal */
    .receipt-content {
        font-family: monospace;
        font-size: 12px;
    }
    
    .receipt-line {
        text-align: center;
        margin: 5px 0;
    }
    
    /* Barcode scanner active effect */
    .barcode-active {
        border-color: #4f9eff !important;
        box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.2) !important;
    }
    
    .loading-products {
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
    .toast.info { border-left-color: #4f9eff; }
    
    @media (max-width: 768px) {
        .cart-item {
            flex-wrap: wrap;
        }
        
        .cart-item-total {
            min-width: auto;
        }
        
        .toast-container {
            bottom: 10px;
            right: 10px;
            left: 10px;
        }
        
        .toast {
            width: auto;
            min-width: auto;
        }
    }
</style>

<div class="pos-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-cash-register"></i> Point of Sale</h4>
            <p class="text-muted mb-0">Scan barcode or search products</p>
        </div>
        <div>
            <span class="badge bg-primary p-2">
                <i class="fas fa-clock"></i> <span id="currentTime"></span>
            </span>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Product Search & Customer -->
        <div class="col-lg-5">
            <!-- Product Search -->
            <div class="product-search-section">
                <div class="search-header">
                    <h6><i class="fas fa-search"></i> Search Products</h6>
                </div>
                <div class="card-body">
                    <div class="product-search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="productSearch" placeholder="Search by product name or category..." autocomplete="off">
                    </div>
                    <div class="product-search-input barcode-input mt-2">
                        <i class="fas fa-barcode"></i>
                        <input type="text" id="barcodeInput" placeholder="Scan or enter Product Code" autocomplete="off">
                    </div>
                    <div class="product-results mt-3" id="productResults">
                        <div class="loading-products">
                            <div class="loading-spinner"></div>
                            <p class="mt-2">Loading products...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="customer-section">
                <div class="customer-header">
                    <h6><i class="fas fa-user"></i> Customer Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-2">
                            <input type="text" id="customerName" class="form-control" placeholder="Customer Name">
                        </div>
                        <div class="col-md-4 mb-2">
                            <input type="text" id="customerPhone" class="form-control" placeholder="Phone">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Cart -->
        <div class="col-lg-7">
            <div class="cart-section">
                <div class="cart-header">
                    <h5><i class="fas fa-shopping-cart"></i> Shopping Cart</h5>
                    <button class="btn btn-sm btn-danger" onclick="clearCart()" id="clearCartBtn" style="display: none;">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>
                <div class="cart-items" id="cartItems">
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
                        <p>Cart is empty</p>
                        <p class="small">Search products or scan barcode to start</p>
                    </div>
                </div>
                <div class="cart-summary" id="cartSummary" style="display: none;">
                    <div class="summary-row">
                        <span>Total Amount:</span>
                        <span id="totalAmount">₱0.00</span>
                    </div>
                    <div class="tax-info">
                        <i class="fas fa-info-circle"></i> Prices already include 12% VAT
                    </div>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="payment-section">
                <div class="payment-body">
                    <div class="payment-methods">
                        <button class="payment-method-btn active" onclick="selectPaymentMethod('cash')">
                            <i class="fas fa-money-bill"></i> Cash
                        </button>
                        <button class="payment-method-btn" onclick="selectPaymentMethod('card')">
                            <i class="fas fa-credit-card"></i> Card
                        </button>
                        <button class="payment-method-btn" onclick="selectPaymentMethod('gcash')">
                            <i class="fas fa-mobile-alt"></i> GCash
                        </button>
                    </div>
                    
                    <div id="cashPaymentDiv" style="display: block;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount Received</label>
                                <input type="number" id="amountReceived" class="form-control" placeholder="0.00" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Change</label>
                                <input type="text" id="changeAmount" class="form-control" readonly style="background: #f8fafc;">
                            </div>
                        </div>
                    </div>
                    
                    <div id="cardPaymentDiv" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" placeholder="**** **** **** ****">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Card Holder Name</label>
                            <input type="text" class="form-control" placeholder="Name on card">
                        </div>
                    </div>
                    
                    <div id="gcashPaymentDiv" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">GCash Number</label>
                            <input type="text" class="form-control" placeholder="09XX XXX XXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" placeholder="Enter reference number">
                        </div>
                    </div>
                    
                    <button class="checkout-btn mt-3" id="checkoutBtn" onclick="processCheckout()" disabled>
                        <i class="fas fa-check-circle"></i> Complete Transaction
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: white; color: #1a2a3a;">
                <h5 class="modal-title">Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptContent">
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/stockindata.php';

// Cart array
let cart = [];
let selectedPaymentMethod = 'cash';
let products = [];

// ============================================
// API CALLS
// ============================================

async function loadProducts() {
    const container = document.getElementById('productResults');
    
    try {
        console.log('Fetching products from API...');
        const response = await fetch(`${API_URL}?action=getProducts`);
        const data = await response.json();
        
        console.log('API Response:', data);
        
        if (data.success && data.data && data.data.length > 0) {
            products = data.data;
            displayProductResults(products);
            showToast(`${products.length} products loaded successfully`, 'success');
        } else {
            // Fallback to sample data
            console.log('No products from API, using sample data');
            products = getSampleProducts();
            displayProductResults(products);
            showToast('Using sample products (database connection issue)', 'warning');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        products = getSampleProducts();
        displayProductResults(products);
        showToast('Using sample products - API connection failed', 'error');
    }
}

function getSampleProducts() {
    return [
        { ProductID: '1', ProductCode: 'PH-IP14P', ProductName: 'iPhone 14 Pro', Category: 'Mobile Phones', Brand: 'Apple', CurrentStock: 10, SellingPrice: 69990 },
        { ProductID: '2', ProductCode: 'PH-SS24', ProductName: 'Samsung Galaxy S24', Category: 'Mobile Phones', Brand: 'Samsung', CurrentStock: 8, SellingPrice: 64990 },
        { ProductID: '3', ProductCode: 'AC-AIRP', ProductName: 'AirPods Pro', Category: 'Accessories', Brand: 'Apple', CurrentStock: 25, SellingPrice: 18990 },
        { ProductID: '4', ProductCode: 'AC-BUDS2', ProductName: 'Samsung Buds2', Category: 'Accessories', Brand: 'Samsung', CurrentStock: 20, SellingPrice: 8990 },
        { ProductID: '5', ProductCode: 'TB-IPAD', ProductName: 'iPad Air', Category: 'Tablets', Brand: 'Apple', CurrentStock: 5, SellingPrice: 45990 },
        { ProductID: '6', ProductCode: 'PH-GP7', ProductName: 'Google Pixel 7', Category: 'Mobile Phones', Brand: 'Google', CurrentStock: 3, SellingPrice: 49990 },
        { ProductID: '7', ProductCode: 'AC-CH25', ProductName: 'Fast Charger 25W', Category: 'Accessories', Brand: 'Samsung', CurrentStock: 50, SellingPrice: 1290 },
        { ProductID: '8', ProductCode: 'AC-GLASS', ProductName: 'Tempered Glass', Category: 'Accessories', Brand: 'Various', CurrentStock: 100, SellingPrice: 299 }
    ];
}

// ============================================
// HELPER FUNCTION
// ============================================

function formatNumber(value) {
    if (value === undefined || value === null || isNaN(value)) return '0.00';
    return parseFloat(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ============================================
// DISPLAY PRODUCTS
// ============================================

function displayProductResults(productsList) {
    const container = document.getElementById('productResults');
    
    if (!productsList || productsList.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                <p>No products found</p>
                <p class="small">Add products to inventory first</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = productsList.map(p => {
        const price = parseFloat(p.SellingPrice) || 0;
        return `
            <div class="product-result-item" onclick='addToCart(${JSON.stringify(p)})'>
                <div>
                    <div class="product-result-name">${escapeHtml(p.ProductName)}</div>
                    <div class="product-result-code">Code: ${p.ProductCode || 'N/A'}</div>
                    <div class="product-result-stock">Stock: ${p.CurrentStock || 0} units</div>
                </div>
                <div class="product-result-price">₱${formatNumber(price)}</div>
            </div>
        `;
    }).join('');
}

// ============================================
// BARCODE SCANNER
// ============================================

document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const barcode = this.value.trim();
        if (barcode) {
            findProductByCode(barcode);
            this.value = '';
            this.classList.remove('barcode-active');
        }
    }
});

document.getElementById('barcodeInput').addEventListener('focus', function() {
    this.classList.add('barcode-active');
});

document.getElementById('barcodeInput').addEventListener('blur', function() {
    this.classList.remove('barcode-active');
});

function findProductByCode(productCode) {
    const product = products.find(p => 
        p.ProductCode && p.ProductCode.toLowerCase() === productCode.toLowerCase()
    );
    
    if (product) {
        addToCart(product);
        showToast(`Product found: ${product.ProductName}`, 'success');
    } else {
        showToast('Product not found. Check barcode and try again.', 'error');
    }
}

// ============================================
// SEARCH PRODUCTS
// ============================================

document.getElementById('productSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    if (searchTerm.length < 2) {
        displayProductResults(products);
        return;
    }
    
    const filtered = products.filter(p => 
        (p.ProductName && p.ProductName.toLowerCase().includes(searchTerm)) ||
        (p.Category && p.Category.toLowerCase().includes(searchTerm)) ||
        (p.Brand && p.Brand.toLowerCase().includes(searchTerm)) ||
        (p.ProductCode && p.ProductCode.toLowerCase().includes(searchTerm))
    );
    
    displayProductResults(filtered);
});

// ============================================
// CART FUNCTIONS
// ============================================

function addToCart(product) {
    // Ensure price is a number
    const productPrice = parseFloat(product.SellingPrice) || 0;
    
    const existingItem = cart.find(item => item.id === product.ProductID);
    
    if (existingItem) {
        if (existingItem.quantity + 1 > (product.CurrentStock || 999)) {
            showToast(`Only ${product.CurrentStock} units available in stock`, 'warning');
            return;
        }
        existingItem.quantity++;
        existingItem.total = existingItem.quantity * existingItem.price;
    } else {
        if (1 > (product.CurrentStock || 999)) {
            showToast('Product out of stock', 'warning');
            return;
        }
        cart.push({
            id: product.ProductID,
            name: product.ProductName,
            productCode: product.ProductCode,
            price: productPrice,
            quantity: 1,
            total: productPrice
        });
    }
    
    updateCartDisplay();
    showToast(`${product.ProductName} added to cart`, 'success');
}

function updateQuantity(id, change) {
    const item = cart.find(i => i.id === id);
    if (item) {
        const newQuantity = item.quantity + change;
        if (newQuantity <= 0) {
            removeFromCart(id);
        } else {
            item.quantity = newQuantity;
            item.total = item.quantity * item.price;
            updateCartDisplay();
        }
    }
}

function removeFromCart(id) {
    const item = cart.find(i => i.id === id);
    if (item) {
        cart = cart.filter(item => item.id !== id);
        updateCartDisplay();
        showToast(`${item.name} removed from cart`, 'info');
    }
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear entire cart?')) {
        cart = [];
        updateCartDisplay();
        showToast('Cart cleared', 'info');
    }
}

function updateCartDisplay() {
    const cartContainer = document.getElementById('cartItems');
    const cartSummary = document.getElementById('cartSummary');
    const clearBtn = document.getElementById('clearCartBtn');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
                <p>Cart is empty</p>
                <p class="small">Search products or scan barcode to start</p>
            </div>
        `;
        cartSummary.style.display = 'none';
        clearBtn.style.display = 'none';
        checkoutBtn.disabled = true;
        return;
    }
    
    cartContainer.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${escapeHtml(item.name)}</div>
                <div class="cart-item-code">Code: ${item.productCode || 'N/A'}</div>
                <div class="cart-item-price">₱${formatNumber(item.price)}</div>
            </div>
            <div class="cart-item-qty">
                <button class="qty-btn" onclick="updateQuantity('${item.id}', -1)">-</button>
                <span>${item.quantity}</span>
                <button class="qty-btn" onclick="updateQuantity('${item.id}', 1)">+</button>
            </div>
            <div class="cart-item-total">₱${formatNumber(item.total)}</div>
            <div class="remove-item" onclick="removeFromCart('${item.id}')">
                <i class="fas fa-trash"></i>
            </div>
        </div>
    `).join('');
    
    // Calculate total (products already include tax)
    const total = cart.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0);
    
    document.getElementById('totalAmount').innerHTML = `₱${formatNumber(total)}`;
    
    cartSummary.style.display = 'block';
    clearBtn.style.display = 'inline-block';
    checkoutBtn.disabled = false;
    
    calculateChange();
}

// ============================================
// PAYMENT METHODS
// ============================================

function selectPaymentMethod(method) {
    selectedPaymentMethod = method;
    
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    document.getElementById('cashPaymentDiv').style.display = method === 'cash' ? 'block' : 'none';
    document.getElementById('cardPaymentDiv').style.display = method === 'card' ? 'block' : 'none';
    document.getElementById('gcashPaymentDiv').style.display = method === 'gcash' ? 'block' : 'none';
}

document.getElementById('amountReceived').addEventListener('input', calculateChange);

function calculateChange() {
    const totalText = document.getElementById('totalAmount').innerText;
    const total = parseFloat(totalText.replace('₱', '').replace(/,/g, '')) || 0;
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    const change = received - total;
    
    document.getElementById('changeAmount').value = change >= 0 ? `₱${formatNumber(change)}` : 'Insufficient';
}

// ============================================
// CHECKOUT & SAVE TRANSACTION
// ============================================

async function processCheckout() {
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        return;
    }
    
    const totalText = document.getElementById('totalAmount').innerText;
    const total = parseFloat(totalText.replace('₱', '').replace(/,/g, '')) || 0;
    
    if (selectedPaymentMethod === 'cash') {
        const received = parseFloat(document.getElementById('amountReceived').value) || 0;
        if (received < total) {
            showToast('Insufficient amount received', 'error');
            return;
        }
    }
    
    // Disable checkout button and show loading
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    checkoutBtn.classList.add('loading');
    
    // Save transaction to database
    const success = await saveTransaction();
    
    if (success) {
        generateReceipt();
        // Clear cart after successful transaction
        cart = [];
        updateCartDisplay();
        document.getElementById('customerName').value = '';
        document.getElementById('customerPhone').value = '';
        document.getElementById('amountReceived').value = '';
        document.getElementById('productSearch').value = '';
        document.getElementById('barcodeInput').value = '';
    }
    
    // Reset button
    checkoutBtn.disabled = false;
    checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Complete Transaction';
    checkoutBtn.classList.remove('loading');
}

async function saveTransaction() {
    const totalText = document.getElementById('totalAmount').innerText;
    const total = parseFloat(totalText.replace('₱', '').replace(/,/g, '')) || 0;
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    const change = received - total;
    
    const transactionData = {
        receipt_no: 'INV-' + new Date().getTime(),
        customer: document.getElementById('customerName').value || 'Walk-in Customer',
        customer_phone: document.getElementById('customerPhone').value || '',
        amount: total,
        payment_method: selectedPaymentMethod,
        amount_received: received,
        change: change,
        items: cart.map(item => ({
            id: item.id,
            product_code: item.productCode,
            name: item.name,
            quantity: item.quantity,
            price: item.price,
            total: item.total
        }))
    };
    
    console.log('Saving transaction:', transactionData);
    
    try {
        const response = await fetch(`${API_URL}?action=saveTransaction`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(transactionData)
        });
        
        const result = await response.json();
        console.log('Save transaction response:', result);
        
        if (result.success) {
            showToast('Transaction saved successfully!', 'success');
            return true;
        } else {
            showToast(result.message || 'Failed to save transaction', 'error');
            return false;
        }
    } catch (error) {
        console.error('Error saving transaction:', error);
        showToast('Network error. Please try again.', 'error');
        return false;
    }
}

// ============================================
// RECEIPT FUNCTIONS
// ============================================

function generateReceipt() {
    const now = new Date();
    const receiptNumber = 'INV-' + now.getFullYear() + (now.getMonth() + 1) + now.getDate() + now.getTime();
    const customerName = document.getElementById('customerName').value || 'Walk-in Customer';
    const total = parseFloat(document.getElementById('totalAmount').innerText.replace('₱', '').replace(/,/g, '')) || 0;
    const vatAmount = total * 0.12 / 1.12; // VAT exclusive calculation
    
    let receiptHTML = `
        <div class="receipt-content">
            <div class="receipt-line"><strong>SIDJAN ELECTRONIC PRODUCTS TRADING</strong></div>
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="receipt-line">${now.toLocaleString()}</div>
            <div class="receipt-line">Receipt: ${receiptNumber}</div>
            <div class="receipt-line">Cashier: ${getCashierName()}</div>
            <div class="receipt-line">Customer: ${escapeHtml(customerName)}</div>
            <div class="receipt-line">${'-'.repeat(30)}</div>
    `;
    
    cart.forEach(item => {
        receiptHTML += `
            <div class="d-flex justify-content-between">
                <span>${escapeHtml(item.name)}</span>
                <span>₱${formatNumber(item.total)}</span>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>  x${item.quantity} @ ₱${formatNumber(item.price)}</span>
                <span>Code: ${item.productCode || 'N/A'}</span>
            </div>
        `;
    });
    
    receiptHTML += `
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="d-flex justify-content-between">
                <strong>TOTAL:</strong>
                <strong>₱${formatNumber(total)}</strong>
            </div>
            <div class="tax-info text-center small">
                (VAT Inclusive - VAT Amount: ₱${formatNumber(vatAmount)})
            </div>
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="receipt-line">Payment: ${selectedPaymentMethod.toUpperCase()}</div>
    `;
    
    if (selectedPaymentMethod === 'cash') {
        const received = parseFloat(document.getElementById('amountReceived').value) || 0;
        const change = received - total;
        receiptHTML += `
            <div class="d-flex justify-content-between">
                <span>Amount Received:</span>
                <span>₱${formatNumber(received)}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Change:</span>
                <span>₱${formatNumber(change)}</span>
            </div>
        `;
    }
    
    receiptHTML += `
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="receipt-line">Thank you for your purchase!</div>
            <div class="receipt-line">Please come again</div>
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="receipt-line small">For inquiries: 0912-345-6789</div>
        </div>
    `;
    
    document.getElementById('receiptContent').innerHTML = receiptHTML;
    
    // Show receipt modal
    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    modal.show();
}

function getCashierName() {
    return '<?php echo $_SESSION['NAME'] ?? 'Admin'; ?>';
}

function printReceipt() {
    const receiptContent = document.getElementById('receiptContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Receipt</title>
            <style>
                body { font-family: monospace; padding: 20px; }
                .receipt-content { max-width: 300px; margin: 0 auto; }
                .receipt-line { text-align: center; margin: 5px 0; }
                .d-flex { display: flex; justify-content: space-between; }
                .small { font-size: 10px; }
                .text-muted { color: #6c757d; }
                .tax-info { font-size: 9px; color: #6c757d; margin-top: 5px; }
            </style>
        </head>
        <body>
            <div class="receipt-content">${receiptContent}</div>
            <script>window.print();<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function showToast(message, type) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    toast.style.display = 'block';
    toast.style.marginBottom = '10px';
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas ${icons[type] || 'fa-info-circle'} me-2" style="color: ${type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107')}"></i>
            <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').innerHTML = now.toLocaleTimeString();
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    updateTime();
    setInterval(updateTime, 1000);
    
    // Focus on barcode input for scanning
    document.getElementById('barcodeInput').focus();
});
</script>