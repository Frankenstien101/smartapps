<?php
// pages/trans.php - Point of Sale Transaction Page with Images, IMEI, Serial
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
    
    .cart-item-imei, .cart-item-serial {
        font-size: 9px;
        color: #6c7a91;
        font-family: monospace;
        margin-top: 2px;
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
    
    .search-header h6 {
        margin: 0;
        font-weight: 600;
    }
    
    .product-search-input {
        position: relative;
        margin: 0 15px 15px 15px;
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
        max-height: 350px;
        overflow-y: auto;
        padding: 0 15px 15px 15px;
    }
    
    .product-result-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
        transition: all 0.2s;
        gap: 12px;
    }
    
    .product-result-item:hover {
        background: #eef2ff;
        border-radius: 10px;
    }
    
    .product-thumb {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .product-thumb i {
        font-size: 24px;
        color: #94a3b8;
    }
    
    .product-info {
        flex: 1;
    }
    
    .product-result-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 3px;
    }
    
    .product-result-code {
        font-size: 10px;
        color: #6c7a91;
        font-family: monospace;
        margin-bottom: 2px;
    }
    
    .product-result-imei, .product-result-serial {
        font-size: 9px;
        color: #6c7a91;
        font-family: monospace;
        margin-top: 2px;
    }
    
    .product-result-stock {
        font-size: 10px;
        margin-top: 3px;
    }
    
    .product-result-price {
        font-size: 14px;
        color: #4f9eff;
        font-weight: 700;
        text-align: right;
        flex-shrink: 0;
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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .customer-header h6 {
        margin: 0;
        font-weight: 600;
    }
    
    .btn-select-customer {
        background: linear-gradient(135deg, #1437d4 0%, #100de9 100%);
        color: white;
        border: none;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
    }
    
    .btn-select-customer:hover {
        transform: translateY(-1px);
    }
    
    .customer-info-display {
        background: #f0fdf4;
        border-radius: 10px;
        padding: 12px 15px;
        margin: 0 15px 15px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .customer-info-text {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .customer-info-text i {
        font-size: 20px;
        color: #28a745;
    }
    
    .customer-name-display {
        font-weight: 600;
        color: #166534;
    }
    
    .customer-phone-display {
        font-size: 12px;
        color: #4b5563;
    }
    
    .customer-purchases {
        font-size: 11px;
        background: #dcfce7;
        padding: 2px 8px;
        border-radius: 20px;
    }
    
    .remove-customer-btn {
        background: #fee2e2;
        color: #dc3545;
        border: none;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 11px;
        cursor: pointer;
    }
    
    .manual-customer-input {
        padding: 0 15px 15px 15px;
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
    
    /* Receipt Modal */
    .receipt-content {
        font-family: monospace;
        font-size: 12px;
    }
    
    .receipt-line {
        text-align: center;
        margin: 5px 0;
    }
    
    /* Customer Modal */
    .customer-list-modal {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .customer-item-modal {
        padding: 12px 15px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .customer-item-modal:hover {
        background: #f8fafc;
    }
    
    .quick-add-section {
        background: #f8fafc;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 15px;
    }
    
    .barcode-active {
        border-color: #4f9eff !important;
        box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.2) !important;
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
        min-width: 280px;
    }
    
    .toast.success { border-left-color: #28a745; }
    .toast.error { border-left-color: #dc3545; }
    .toast.warning { border-left-color: #ffc107; }
    
    @media (max-width: 768px) {
        .cart-item {
            flex-wrap: wrap;
        }
        .customer-info-text {
            flex-direction: column;
            align-items: flex-start;
        }
        .product-result-item {
            flex-wrap: wrap;
        }
        .product-thumb {
            width: 40px;
            height: 40px;
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
                <div class="product-search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" id="productSearch" placeholder="Search by name, code, IMEI, or Serial..." autocomplete="off">
                </div>
                <div class="product-search-input barcode-input">
                    <i class="fas fa-barcode"></i>
                    <input type="text" id="barcodeInput" placeholder="Scan or enter Product Code" autocomplete="off">
                </div>
                <div class="product-results" id="productResults">
                    <div class="text-center py-4">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading products...</p>
                    </div>
                </div>
            </div>

            <!-- Customer Information with Selection -->
            <div class="customer-section">
                <div class="customer-header">
                    <h6><i class="fas fa-user"></i> Customer Information</h6>
                    <button class="btn-select-customer" onclick="openCustomerModal()">
                        <i class="fas fa-users"></i> Select Customer
                    </button>
                </div>
                
                <!-- Selected Customer Display -->
                <div id="selectedCustomerDisplay" style="display: none;" class="customer-info-display">
                    <div class="customer-info-text">
                        <i class="fas fa-user-circle"></i>
                        <div>
                            <span class="customer-name-display" id="displayCustomerName">-</span>
                            <span class="customer-phone-display" id="displayCustomerPhone"></span>
                            <div><span class="customer-purchases" id="displayCustomerPurchases">0 purchases</span></div>
                        </div>
                    </div>
                    <button class="remove-customer-btn" onclick="clearSelectedCustomer()">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                
                <!-- Manual Customer Input -->
                <div class="manual-customer-input">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <input type="text" id="customerName" class="form-control" placeholder="Customer Name (Walk-in)">
                        </div>
                        <div class="col-md-4">
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
                        <button class="payment-method-btn active" onclick="selectPaymentMethod('cash', this)">
                            <i class="fas fa-money-bill"></i> Cash
                        </button>
                        <button class="payment-method-btn" onclick="selectPaymentMethod('card', this)">
                            <i class="fas fa-credit-card"></i> Card
                        </button>
                        <button class="payment-method-btn" onclick="selectPaymentMethod('gcash', this)">
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
                    </div>
                    
                    <div id="gcashPaymentDiv" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">GCash Number</label>
                            <input type="text" class="form-control" placeholder="09XX XXX XXXX">
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

<!-- Customer Selection Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1f2937, #2d3a4a); color: white;">
                <h5 class="modal-title"><i class="fas fa-users"></i> Select Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Quick Add Customer -->
                <div class="quick-add-section">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" id="newCustomerName" class="form-control" placeholder="New customer name *">
                        </div>
                        <div class="col-md-3">
                            <input type="tel" id="newCustomerPhone" class="form-control" placeholder="Phone">
                        </div>
                        <div class="col-md-3">
                            <input type="email" id="newCustomerEmail" class="form-control" placeholder="Email">
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-primary w-100" onclick="quickAddCustomer()">
                                <i class="fas fa-plus"></i> 
                            </button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input type="text" id="newCustomerAddress" class="form-control" placeholder="Address">
                    </div>
                </div>
                
                <!-- Search Existing Customers -->
                <div class="search-box mb-3" style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="text" id="customerSearch" class="form-control" placeholder="Search existing customers..." style="padding-left: 35px;" onkeyup="filterCustomerList()">
                </div>
                
                <!-- Customer List -->
                <div class="customer-list-modal" id="customerListModal">
                    <div class="text-center py-4">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading customers...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="useWalkInCustomer()">
                    <i class="fas fa-walking"></i> Use Walk-in Customer
                </button>
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
            <div class="modal-body" id="receiptContent"></div>
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
const API_URL = '/POS/datafetcher/stockindata.php';
const CUSTOMER_API_URL = '/POS/datafetcher/addcustomerdata.php';
const PRODUCT_API_URL = '/POS/datafetcher/productdata.php';

// Global variables
let cart = [];
let selectedPaymentMethod = 'cash';
let products = [];
let allCustomers = [];
let selectedCustomer = null;

// ============================================
// API CALLS
// ============================================

async function loadProducts() {
    try {
        // Use product_api.php for products with images, IMEI, Serial
        const response = await fetch(`${PRODUCT_API_URL}?action=getProducts`);
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            products = data.data;
            displayProductResults(products);
        } else {
            showToast('No products found', 'warning');
            document.getElementById('productResults').innerHTML = '<div class="text-center py-4 text-muted">No products found</div>';
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showToast('Failed to load products', 'error');
    }
}

async function loadCustomers() {
    try {
        const response = await fetch(`${CUSTOMER_API_URL}?action=getCustomers`);
        const data = await response.json();
        
        if (data.success && data.data) {
            allCustomers = data.data;
            displayCustomerList();
        }
    } catch (error) {
        console.error('Error loading customers:', error);
    }
}

async function quickAddCustomer() {
    const customerName = document.getElementById('newCustomerName').value.trim();
    if (!customerName) {
        showToast('Please enter customer name', 'warning');
        return;
    }
    
    try {
        const response = await fetch(`${CUSTOMER_API_URL}?action=addCustomer`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                customer_name: customerName,
                phone: document.getElementById('newCustomerPhone').value.trim(),
                email: document.getElementById('newCustomerEmail').value.trim(),
                address: document.getElementById('newCustomerAddress').value.trim()
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Customer added successfully', 'success');
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerEmail').value = '';
            document.getElementById('newCustomerAddress').value = '';
            await loadCustomers();
        } else {
            showToast(result.message || 'Failed to add customer', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

// ============================================
// CUSTOMER SELECTION
// ============================================

function openCustomerModal() {
    loadCustomers();
    new bootstrap.Modal(document.getElementById('customerModal')).show();
}

function selectCustomer(customer) {
    selectedCustomer = customer;
    
    document.getElementById('displayCustomerName').innerText = customer.CustomerName;
    document.getElementById('displayCustomerPhone').innerHTML = customer.Phone ? ` • ${customer.Phone}` : '';
    document.getElementById('displayCustomerPurchases').innerHTML = `${customer.TotalPurchases || 0} purchases • ₱${formatNumber(customer.TotalSpent || 0)} spent`;
    document.getElementById('selectedCustomerDisplay').style.display = 'flex';
    document.getElementById('customerName').value = customer.CustomerName;
    document.getElementById('customerPhone').value = customer.Phone || '';
    
    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    showToast(`Customer selected: ${customer.CustomerName}`, 'success');
}

function clearSelectedCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    showToast('Customer removed', 'info');
}

function useWalkInCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    showToast('Using walk-in customer', 'info');
}

function filterCustomerList() {
    const searchTerm = document.getElementById('customerSearch').value.toLowerCase();
    const filtered = allCustomers.filter(c => 
        c.CustomerName.toLowerCase().includes(searchTerm) ||
        (c.Phone && c.Phone.includes(searchTerm)) ||
        (c.Email && c.Email.toLowerCase().includes(searchTerm))
    );
    displayCustomerList(filtered);
}

function displayCustomerList(customers = null) {
    const list = customers || allCustomers;
    const container = document.getElementById('customerListModal');
    
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted">No customers found</div>';
        return;
    }
    
    container.innerHTML = list.map(c => `
        <div class="customer-item-modal" onclick='selectCustomer(${JSON.stringify(c)})'>
            <div>
                <div class="fw-bold">${escapeHtml(c.CustomerName)}</div>
                <div class="small text-muted">
                    ${c.Phone ? `<i class="fas fa-phone"></i> ${c.Phone}` : ''}
                    ${c.Email ? `<i class="fas fa-envelope ms-2"></i> ${c.Email}` : ''}
                </div>
                <div class="small mt-1">
                    ${c.TotalPurchases || 0} purchases • ₱${formatNumber(c.TotalSpent || 0)} spent
                </div>
            </div>
            <i class="fas fa-chevron-right text-muted"></i>
        </div>
    `).join('');
}

// ============================================
// DISPLAY PRODUCTS WITH IMAGES, IMEI, SERIAL
// ============================================

function displayProductResults(productsList) {
    const container = document.getElementById('productResults');
    
    if (!productsList || productsList.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted">No products found</div>';
        return;
    }
    
    container.innerHTML = productsList.map(p => {
        const imageHtml = p.ProductImagePath 
            ? `<img src="${p.ProductImagePath}" alt="${escapeHtml(p.ProductName)}">`
            : `<i class="fas fa-box"></i>`;
        
        const stockClass = p.CurrentStock < 10 ? 'text-danger' : 'text-success';
        
        return `
            <div class="product-result-item" onclick='addToCart(${JSON.stringify(p)})'>
                <div class="product-thumb">
                    ${imageHtml}
                </div>
                <div class="product-info">
                    <div class="product-result-name">${escapeHtml(p.ProductName)}</div>
                    <div class="product-result-code">Code: ${p.ProductCode || 'N/A'}</div>
                    ${p.IMEINumber ? `<div class="product-result-imei"><i class="fas fa-qrcode"></i> IMEI: ${p.IMEINumber}</div>` : ''}
                    ${p.SerialNumber ? `<div class="product-result-serial"><i class="fas fa-barcode"></i> Serial: ${p.SerialNumber}</div>` : ''}
                    <div class="product-result-stock ${stockClass}">
                        <i class="fas fa-boxes"></i> Stock: ${p.CurrentStock || 0} units
                    </div>
                </div>
                <div class="product-result-price">₱${formatNumber(p.SellingPrice)}</div>
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
            // Search by product code, IMEI, or Serial
            const product = products.find(p => 
                (p.ProductCode && p.ProductCode.toLowerCase() === barcode.toLowerCase()) ||
                (p.IMEINumber && p.IMEINumber === barcode) ||
                (p.SerialNumber && p.SerialNumber === barcode)
            );
            if (product) {
                addToCart(product);
                this.value = '';
                showToast(`Added: ${product.ProductName}`, 'success');
            } else {
                showToast('Product not found', 'error');
            }
        }
    }
});

// ============================================
// SEARCH PRODUCTS (includes IMEI and Serial)
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
        (p.ProductCode && p.ProductCode.toLowerCase().includes(searchTerm)) ||
        (p.IMEINumber && p.IMEINumber.toLowerCase().includes(searchTerm)) ||
        (p.SerialNumber && p.SerialNumber.toLowerCase().includes(searchTerm))
    );
    
    displayProductResults(filtered);
});

// ============================================
// CART FUNCTIONS (includes IMEI/Serial in cart)
// ============================================

function addToCart(product) {
    const productPrice = parseFloat(product.SellingPrice) || 0;
    const existingItem = cart.find(item => item.id === product.ProductID);
    
    if (existingItem) {
        if (existingItem.quantity + 1 > (product.CurrentStock || 999)) {
            showToast(`Only ${product.CurrentStock} units available`, 'warning');
            return;
        }
        existingItem.quantity++;
        existingItem.total = existingItem.quantity * existingItem.price;
    } else {
        cart.push({
            id: product.ProductID,
            name: product.ProductName,
            productCode: product.ProductCode,
            price: productPrice,
            quantity: 1,
            total: productPrice,
            imei: product.IMEINumber,
            serial: product.SerialNumber,
            image: product.ProductImagePath
        });
    }
    
    updateCartDisplay();
    showToast(`${product.ProductName} added to cart`, 'success');
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
                ${item.imei ? `<div class="cart-item-imei"><i class="fas fa-qrcode"></i> IMEI: ${item.imei}</div>` : ''}
                ${item.serial ? `<div class="cart-item-serial"><i class="fas fa-barcode"></i> Serial: ${item.serial}</div>` : ''}
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
    
    const total = cart.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0);
    document.getElementById('totalAmount').innerHTML = `₱${formatNumber(total)}`;
    
    cartSummary.style.display = 'block';
    clearBtn.style.display = 'inline-block';
    checkoutBtn.disabled = false;
    calculateChange();
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
    cart = cart.filter(item => item.id !== id);
    updateCartDisplay();
    showToast('Item removed from cart', 'info');
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear entire cart?')) {
        cart = [];
        updateCartDisplay();
        showToast('Cart cleared', 'info');
    }
}

// ============================================
// PAYMENT METHODS
// ============================================

function selectPaymentMethod(method, btn) {
    selectedPaymentMethod = method;
    
    document.querySelectorAll('.payment-method-btn').forEach(b => {
        b.classList.remove('active');
    });
    btn.classList.add('active');
    
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
// CHECKOUT
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
    
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const success = await saveTransaction();
    
    if (success) {
        generateReceipt();
        cart = [];
        updateCartDisplay();
        document.getElementById('amountReceived').value = '';
        document.getElementById('productSearch').value = '';
        document.getElementById('barcodeInput').value = '';
    }
    
    checkoutBtn.disabled = false;
    checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Complete Transaction';
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
            total: item.total,
            imei: item.imei || '',
            serial: item.serial || ''
        }))
    };
    
    try {
        const response = await fetch(`${API_URL}?action=saveTransaction`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(transactionData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Transaction completed!', 'success');
            return true;
        } else {
            showToast(result.message || 'Failed to save transaction', 'error');
            return false;
        }
    } catch (error) {
        showToast('Network error', 'error');
        return false;
    }
}

// ============================================
// RECEIPT FUNCTIONS
// ============================================

function generateReceipt() {
    const now = new Date();
    const receiptNumber = 'INV-' + now.getTime();
    const customerName = document.getElementById('customerName').value || 'Walk-in Customer';
    const total = parseFloat(document.getElementById('totalAmount').innerText.replace('₱', '').replace(/,/g, '')) || 0;
    
    let html = `
        <div class="receipt-content">
            <div class="receipt-line"><strong>SIDJAN ELECTRONIC</strong></div>
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="receipt-line">${now.toLocaleString()}</div>
            <div class="receipt-line">Receipt: ${receiptNumber}</div>
            <div class="receipt-line">Customer: ${escapeHtml(customerName)}</div>
            <div class="receipt-line">${'-'.repeat(30)}</div>
    `;
    
    cart.forEach(item => {
        html += `
            <div class="d-flex justify-content-between">
                <span>${escapeHtml(item.name)}</span>
                <span>₱${formatNumber(item.total)}</span>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>  x${item.quantity} @ ₱${formatNumber(item.price)}</span>
            </div>
            ${item.imei ? `<div class="small text-muted">IMEI: ${item.imei}</div>` : ''}
            ${item.serial ? `<div class="small text-muted">Serial: ${item.serial}</div>` : ''}
        `;
    });
    
    html += `
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="d-flex justify-content-between">
                <strong>TOTAL:</strong>
                <strong>₱${formatNumber(total)}</strong>
            </div>
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="receipt-line">Payment: ${selectedPaymentMethod.toUpperCase()}</div>
    `;
    
    if (selectedPaymentMethod === 'cash') {
        const received = parseFloat(document.getElementById('amountReceived').value) || 0;
        const change = received - total;
        html += `
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
    
    html += `
            <div class="receipt-line">${'-'.repeat(30)}</div>
            <div class="receipt-line">Thank you for your purchase!</div>
            <div class="receipt-line">Please come again</div>
        </div>
    `;
    
    document.getElementById('receiptContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}

function printReceipt() {
    const content = document.getElementById('receiptContent').innerHTML;
    const w = window.open('', '_blank');
    w.document.write(`<html><head><title>Receipt</title><style>body{font-family:monospace;padding:20px;}.receipt-content{max-width:300px;margin:0 auto;}.d-flex{display:flex;justify-content:space-between;}</style></head><body><div class="receipt-content">${content}</div><script>window.print();<\/script></body></html>`);
    w.document.close();
}

// ============================================
// HELPER FUNCTIONS
// ============================================

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
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-header">
            <strong class="me-auto">${type.toUpperCase()}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000, autohide: true });
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function updateTime() {
    document.getElementById('currentTime').innerHTML = new Date().toLocaleTimeString();
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    updateTime();
    setInterval(updateTime, 1000);
    document.getElementById('barcodeInput').focus();
});
</script>