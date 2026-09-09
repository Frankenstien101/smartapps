<?php

$gcashNumber = $_SESSION['gcash_number'] ?? $_SESSION['GCASH_NUMBER'] ?? '0999-999-9999';
$cashierName = $_SESSION['NAME'] ?? $_SESSION['username'] ?? 'Admin';
$branchName = $_SESSION['branch_name'] ?? $_SESSION['BRANCH'] ?? 'Main Branch';
?>
<style>
    .pos-container {
        padding: 0;
        width: 100%;
    }
    
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
    
    .cart-item-name .discount-badge-small {
        font-size: 10px;
        background: #dc3545;
        color: white;
        padding: 1px 6px;
        border-radius: 12px;
        margin-left: 5px;
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
    
    .cart-item-imei,
    .cart-item-serial {
        font-size: 9px;
        color: #6c7a91;
        font-family: monospace;
        margin-top: 2px;
    }
    
    .cart-item-qty {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .qty-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
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
    
    .cart-item-total.original {
        text-decoration: line-through;
        color: #999;
        font-size: 12px;
        margin-right: 5px;
    }
    
    .cart-item-total.discounted {
        color: #28a745;
    }
    
    .remove-item {
        color: #dc3545;
        cursor: pointer;
        margin-left: 10px;
        font-size: 14px;
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
    
    .summary-row.subtotal {
        color: #6c7a91;
        font-size: 14px;
    }
    
    .summary-row.discount-row {
        color: #dc3545;
        font-weight: 600;
        font-size: 16px;
        border-top: 1px dashed #dc3545;
        padding-top: 10px;
        margin-top: 5px;
    }
    
    .summary-row.final-total {
        font-size: 20px;
        font-weight: 800;
        color: #28a745;
        border-top: 2px solid #28a745;
        padding-top: 10px;
        margin-top: 5px;
    }
    
    .tax-info {
        font-size: 11px;
        color: #6c7a91;
        text-align: right;
        margin-top: 5px;
    }
    
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
        max-height: 450px;
        overflow-y: auto;
        padding: 0 15px 15px 15px;
    }
    
    .product-card {
        background: white;
        border-radius: 16px;
        margin-bottom: 15px;
        border: 1px solid #eef2f7;
        overflow: hidden;
    }
    
    .product-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .product-header {
        display: flex;
        padding: 15px;
        gap: 12px;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
    }
    
    .product-header:hover {
        background: #eef2ff;
    }
    
    .product-thumb {
        width: 60px;
        height: 60px;
        border-radius: 12px;
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
        font-size: 30px;
        color: #94a3b8;
    }
    
    .product-details {
        flex: 1;
    }
    
    .product-name {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 4px;
        color: #1a2a3a;
    }
    
    .product-code {
        font-size: 11px;
        color: #6c7a91;
        font-family: monospace;
        margin-bottom: 4px;
    }
    
    .product-price {
        font-size: 16px;
        font-weight: 800;
        color: #28a745;
    }
    
    .product-price.original {
        text-decoration: line-through;
        color: #999;
        font-size: 13px;
        margin-left: 8px;
        font-weight: 400;
    }
    
    .product-stock-badge {
        font-size: 11px;
        background: #e2e8f0;
        padding: 2px 8px;
        border-radius: 20px;
        display: inline-block;
    }
    
    .product-type-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 8px;
    }
    
    .product-type-badge.serialized {
        background: #dbeafe;
        color: #2563eb;
    }
    
    .product-type-badge.bulk {
        background: #dcfce7;
        color: #10b981;
    }
    
    .discount-badge {
        background: #dc3545;
        color: white;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 5px;
        font-weight: 700;
    }
    
    .discount-badge.less-badge {
        background: #d97706;
    }
    
    .discount-badge.both-badge {
        background: #16a34a;
    }
    
    .units-list {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background: white;
    }
    
    .units-list.expanded {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .unit-search-box {
        padding: 10px 12px;
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    
    .unit-search-box input {
        width: 100%;
        padding: 8px 12px 8px 32px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 12px;
    }
    
    .unit-search-box i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 12px;
    }
    
    .unit-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
    }
    
    .unit-item:hover {
        background: #eef2ff;
    }
    
    .unit-info {
        flex: 1;
    }
    
    .unit-number {
        font-weight: 600;
        font-size: 12px;
        color: #4f9eff;
    }
    
    .unit-imei,
    .unit-serial {
        font-size: 10px;
        font-family: monospace;
        color: #6c7a91;
        margin-top: 2px;
    }
    
    .unit-add-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 11px;
        cursor: pointer;
    }
    
    .unit-add-btn:hover {
        background: #218838;
    }
    
    .bulk-add-section {
        padding: 12px 15px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .bulk-qty-selector {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .bulk-qty-selector input {
        width: 70px;
        padding: 6px;
        text-align: center;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    
    .bulk-add-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 6px 20px;
        border-radius: 8px;
        cursor: pointer;
    }
    
    .bulk-add-btn:hover {
        background: #218838;
    }
    
    .expand-icon {
        transition: transform 0.2s;
    }
    
    .expand-icon.rotated {
        transform: rotate(180deg);
    }
    
    .unit-count-badge {
        font-size: 11px;
        background: #e2e8f0;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 8px;
    }
    
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
        background: linear-gradient(135deg, #010d42 0%, #0502ac 100%);
        color: white;
        border: none;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
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
        cursor: pointer;
    }
    
    .checkout-btn:hover {
        background: #218838;
    }
    
    .checkout-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }
    
    .receipt-content {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        max-width: 350px;
        margin: 0 auto;
        word-wrap: break-word;
    }
    
    .receipt-header {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .receipt-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: bold;
    }
    
    .receipt-header small {
        font-size: 10px;
        color: #666;
    }
    
    .receipt-line-dashed {
        text-align: center;
        letter-spacing: 2px;
        color: #666;
        margin: 5px 0;
    }
    
    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin: 5px 0;
    }
    
    .receipt-footer {
        text-align: center;
        margin-top: 15px;
        font-size: 10px;
    }
    
    .mt-2 {
        margin-top: 10px;
    }
    
    .customer-list-modal {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .customer-item-modal {
        padding: 12px 15px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
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
    
    .search-box-custom {
        position: relative;
        margin-bottom: 15px;
    }
    
    .search-box-custom i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .search-box-custom input {
        width: 100%;
        padding: 10px 12px 10px 35px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
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
    
    .no-results {
        padding: 20px;
        text-align: center;
        color: #6c7a91;
    }
    
    .gcash-qr-container {
        background: white;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 250px;
    }
    
    .gcash-qr-container img {
        max-width: 100%;
        height: auto;
    }
    
    .gcash-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .gcash-brand i {
        font-size: 32px;
        color: #00a65a;
    }
    
    .gcash-brand span {
        font-size: 20px;
        font-weight: 800;
        color: #0073bf;
    }
    
    .gcash-number-display {
        font-size: 16px;
        font-weight: 700;
        color: #0073bf;
        letter-spacing: 1px;
    }
    
    @media (max-width: 768px) {
        .cart-item { flex-wrap: wrap; }
        .product-header { flex-wrap: wrap; }
        .product-thumb { width: 50px; height: 50px; }
        .bulk-add-section { flex-direction: column; align-items: stretch; }
        .payment-methods { flex-direction: column; }
    }
</style>

<div class="pos-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-cash-register"></i> Point of Sale</h4>
            <p class="text-muted mb-0">Bulk items = select quantity | Serialized items = search by IMEI/Serial</p>
        </div>
        <div>
            <span class="badge bg-primary p-2">
                <i class="fas fa-clock"></i> <span id="currentTime"></span>
            </span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
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
                    <input type="text" id="barcodeInput" placeholder="Scan barcode, IMEI, or Serial..." autocomplete="off">
                </div>
                <div class="product-results" id="productResults">
                    <div class="text-center py-4">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading products...</p>
                    </div>
                </div>
            </div>

            <div class="customer-section" style="display: none;" id="customerSection">
                <div class="customer-header">
                    <h6><i class="fas fa-user"></i> Customer Information</h6>
                    <button class="btn-select-customer" onclick="openCustomerModal()">
                        <i class="fas fa-users"></i> Select Customer
                    </button>
                </div>

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

                <div class="manual-customer-input">
                    <div class="row g-2">
                        <div class="col-md-8"><input type="text" id="customerName" class="form-control" placeholder="Customer Name (Walk-in)"></div>
                        <div class="col-md-4"><input type="text" id="customerPhone" class="form-control" placeholder="Phone"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="cart-section">
                <div class="cart-header">
                    <h5><i class="fas fa-shopping-cart"></i> Shopping Cart</h5>
                    <button class="btn btn-sm btn-danger" onclick="clearCart()" id="clearCartBtn" style="display: none;"><i class="fas fa-trash"></i> Clear</button>
                </div>
                <div class="cart-items" id="cartItems">
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
                        <p>Cart is empty</p>
                        <p class="small">Search products above to start</p>
                    </div>
                </div>
                <div class="cart-summary" id="cartSummary" style="display: none;">
                    <div class="summary-row subtotal"><span>Subtotal:</span><span id="subtotalAmount">₱0.00</span></div>
                    <div class="summary-row discount-row" id="discountRow" style="display: none;">
                        <span>Total Discount:</span>
                        <span id="discountAmount">-₱0.00</span>
                    </div>
                    <div class="summary-row final-total"><span>Total Amount:</span><span id="totalAmount">₱0.00</span></div>
                    <div class="tax-info"><i class="fas fa-info-circle"></i> Prices already include 12% VAT</div>
                </div>
            </div>

            <div class="payment-section">
                <div class="payment-body">
                    <div class="payment-methods">
                        <button class="payment-method-btn active" onclick="selectPaymentMethod('cash', this)"><i class="fas fa-money-bill"></i> Cash</button>
                        <button class="payment-method-btn" onclick="selectPaymentMethod('card', this)"><i class="fas fa-credit-card"></i> Card</button>
                        <button class="payment-method-btn" onclick="selectPaymentMethod('gcash', this)"><i class="fas fa-mobile-alt"></i> GCash</button>
                    </div>
                    <div id="cashPaymentDiv" style="display: block;">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Amount Received</label><input type="number" id="amountReceived" class="form-control" placeholder="0.00" step="0.01"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Change</label><input type="text" id="changeAmount" class="form-control" readonly style="background:#f8fafc;"></div>
                        </div>
                    </div>
                    <div id="cardPaymentDiv" style="display: none;">
                        <div class="mb-3"><label class="form-label">Card Number</label><input type="text" class="form-control" placeholder="**** **** **** ****"></div>
                    </div>
                    <div id="gcashPaymentDiv" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">GCash Number</label>
                            <input type="text" id="gcashNumber" class="form-control" placeholder="09XX XXX XXXX" value="<?php echo $gcashNumber; ?>">
                            <small class="text-muted">Store's GCash number for reference</small>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-success w-100" onclick="showGCashQR()">
                                <i class="fas fa-qrcode"></i> Show QR Code to Pay
                            </button>
                        </div>
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle"></i> 
                            Customer scans the QR code and pays via GCash. Confirm payment after receiving the payment.
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

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1f2937, #2d3a4a); color: white;">
                <h5 class="modal-title"><i class="fas fa-users"></i> Select Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="quick-add-section">
                    <div class="row g-2">
                        <div class="col-md-5"><input type="text" id="newCustomerName" class="form-control" placeholder="New customer name *"></div>
                        <div class="col-md-3"><input type="tel" id="newCustomerPhone" class="form-control" placeholder="Phone"></div>
                        <div class="col-md-3"><input type="email" id="newCustomerEmail" class="form-control" placeholder="Email"></div>
                        <div class="col-md-1"><button class="btn btn-primary w-100" onclick="quickAddCustomer()"><i class="fas fa-plus"></i></button></div>
                    </div>
                    <div class="mt-2"><input type="text" id="newCustomerAddress" class="form-control" placeholder="Address"></div>
                </div>
                <div class="search-box-custom"><i class="fas fa-search"></i><input type="text" id="customerSearch" placeholder="Search existing customers..." onkeyup="filterCustomerList()"></div>
                <div class="customer-list-modal" id="customerListModal"><div class="text-center py-4"><div class="loading-spinner"></div> Loading customers...</div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="useWalkInCustomer()"><i class="fas fa-walking"></i> Use Walk-in Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #4f9eff, #2563eb); color: white;">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> Sales Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptContent" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-4">Loading receipt...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- GCash QR Code Modal -->
<div class="modal fade" id="gcashQRModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0073bf, #00a65a); color: white;">
                <h5 class="modal-title"><i class="fas fa-qrcode"></i> Scan to Pay with GCash</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="gcash-brand">
                    <i class="fas fa-mobile-alt"></i>
                    <span>GCash</span>
                    <i class="fas fa-check-circle" style="color:#00a65a;"></i>
                </div>
                
                <div class="gcash-qr-container" id="gcashQRContainer">
                    <div class="text-center">
                        <div class="loading-spinner"></div>
                        <p class="mt-2 text-muted">Loading QR code...</p>
                    </div>
                </div>
                
                <div class="mt-3">
                    <span id="gcashQRAmount" style="display: none; color: #00a65a; font-weight: 800;">₱0.00</span>
                    
                    <div class="alert alert-info small" style="display: none;">
                        <i class="fas fa-info-circle"></i> 
                        GCash Number: <strong class="gcash-number-display" id="gcashNumberDisplay"><?php echo $gcashNumber; ?></strong>
                    </div>
                    
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="gcashConfirmed">
                        <label class="form-check-label" for="gcashConfirmed">
                            <i class="fas fa-check-circle text-success"></i> 
                            I confirm that the customer has paid via GCash
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" onclick="confirmGCashPayment()">
                    <i class="fas fa-check-circle"></i> Confirm Payment Received
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// DISCOUNT CALCULATION FUNCTIONS
// ============================================

function calculateFinalPrice(originalPrice, discountPercent, lessAmount) {
    let price = parseFloat(originalPrice) || 0;
    const discount = parseFloat(discountPercent) || 0;
    const less = parseFloat(lessAmount) || 0;
    
    // Apply percentage discount first
    if (discount > 0) {
        price = price - (price * (discount / 100));
    }
    
    // Then subtract exact amount (Less)
    if (less > 0) {
        price = price - less;
    }
    
    // Ensure price doesn't go below 0
    return Math.max(0, price);
}

function getDiscountLabel(discountPercent, lessAmount) {
    const discount = parseFloat(discountPercent) || 0;
    const less = parseFloat(lessAmount) || 0;
    let labels = [];
    
    if (discount > 0) {
        labels.push(discount + '%');
    }
    if (less > 0) {
        labels.push('₱' + formatNumber(less) + ' off');
    }
    
    if (labels.length === 0) return '';
    if (labels.length === 1) return labels[0];
    return labels.join(' + ');
}

function getDiscountBadgeClass(discountPercent, lessAmount) {
    const discount = parseFloat(discountPercent) || 0;
    const less = parseFloat(lessAmount) || 0;
    
    if (discount > 0 && less > 0) return 'both-badge';
    if (less > 0) return 'less-badge';
    if (discount > 0) return '';
    return '';
}

// ============================================
// CONFIGURATION
// ============================================
const API_URL = '/dmb/datafetcher/stockindata.php';
const CUSTOMER_API_URL = '/dmb/datafetcher/addcustomerdata.php';
const PRODUCT_API_URL = '/dmb/datafetcher/productdata.php';

// ============================================
// GCASH QR CODE
// ============================================
let gcashPaymentConfirmed = false;
const STORE_GCASH_NUMBER = '<?php echo $gcashNumber; ?>';

function showGCashQR() {
    const totalText = document.getElementById('totalAmount').innerText;
    const amount = parseFloat(totalText.replace('₱', '').replace(/,/g, '')) || 0;
    
    if (amount <= 0) {
        showToast('Please add items to cart first', 'warning');
        return;
    }
    
    const gcashNumber = document.getElementById('gcashNumber').value.trim() || STORE_GCASH_NUMBER;
    
    document.getElementById('gcashNumberDisplay').textContent = gcashNumber;
    document.getElementById('gcashQRAmount').textContent = '₱' + formatNumber(amount);
    
    const qrImageUrl = '/dmb/qrimg.JPG';
    const container = document.getElementById('gcashQRContainer');
    
    container.innerHTML = `
        <div class="text-center">
            <div class="loading-spinner"></div>
            <p class="mt-2 text-muted">Loading QR code...</p>
        </div>
    `;
    
    const img = new Image();
    img.onload = function() {
        container.innerHTML = `
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <img src="${qrImageUrl}?t=${new Date().getTime()}" 
                     alt="GCash QR Code" 
                     style="width: 220px; height: 220px; object-fit: contain;">
                <div class="mt-3">
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i> 
                        Scan with GCash app and enter amount
                    </p>
                    <div class="alert alert-success small">
                        <i class="fas fa-check-circle"></i> 
                        Amount: <strong>₱${formatNumber(amount)}</strong>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-phone"></i> 
                        GCash Number: <strong>${escapeHtml(gcashNumber)}</strong>
                    </div>
                </div>
            </div>
        `;
    };
    
    img.onerror = function() {
        container.innerHTML = `
            <div class="alert alert-danger text-center" style="width: 100%;">
                <i class="fas fa-exclamation-circle fa-2x d-block mb-2"></i>
                <h6>QR Code Image Not Found</h6>
                <p class="small">Please save your GCash QR code as:</p>
                <code>/dmb/qrimg.JPG</code>
                <p class="small mt-2">Or update the path in the code.</p>
            </div>
        `;
    };
    
    img.src = qrImageUrl + '?t=' + new Date().getTime();
    
    const modal = new bootstrap.Modal(document.getElementById('gcashQRModal'));
    modal.show();
}

function confirmGCashPayment() {
    const confirmed = document.getElementById('gcashConfirmed');
    if (!confirmed.checked) {
        showToast('Please confirm that the customer has paid via GCash', 'warning');
        return;
    }
    
    gcashPaymentConfirmed = true;
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('gcashQRModal'));
    if (modal) {
        modal.hide();
    }
    
    showToast('GCash payment confirmed! Processing transaction...', 'success');
    
    setTimeout(() => {
        processCheckout();
    }, 500);
}

// ============================================
// GLOBAL VARIABLES
// ============================================
let cart = [];
let selectedPaymentMethod = 'cash';
let products = [];
let allCustomers = [];
let selectedCustomer = null;
let productUnits = {};
let currentSearchTerm = '';
let unitSearchTerms = {};

// ============================================
// API CALLS
// ============================================

async function loadProducts() {
    try {
        const response = await fetch(PRODUCT_API_URL + '?action=getProducts');
        const data = await response.json();

        if (data.success && data.data && data.data.length > 0) {
            products = data.data;
            console.log('Products loaded with discounts:', products);
            
            // Load units for products that have them
            const productIds = products.map(p => p.ProductID).join(',');
            if (productIds) {
                await loadMultipleProductUnits(productIds);
            }
            
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

async function loadMultipleProductUnits(productIds) {
    if (!productIds) return;
    
    try {
        const response = await fetch(PRODUCT_API_URL + '?action=getMultipleProductUnits&product_ids=' + productIds);
        const data = await response.json();
        if (data.success && data.data) {
            productUnits = data.data;
            console.log('Units loaded for products:', productUnits);
        } else {
            productUnits = {};
        }
    } catch (error) {
        console.error('Error loading units:', error);
        productUnits = {};
    }
}

async function loadCustomers() {
    try {
        const response = await fetch(CUSTOMER_API_URL + '?action=getCustomers');
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
        const response = await fetch(CUSTOMER_API_URL + '?action=addCustomer', {
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
    document.getElementById('displayCustomerPhone').innerHTML = customer.Phone ? ' • ' + customer.Phone : '';
    document.getElementById('displayCustomerPurchases').innerHTML = (customer.TotalPurchases || 0) + ' purchases • ₱' + formatNumber(customer.TotalSpent || 0);
    document.getElementById('selectedCustomerDisplay').style.display = 'flex';
    document.getElementById('customerName').value = customer.CustomerName;
    document.getElementById('customerPhone').value = customer.Phone || '';
    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    showToast('Customer selected: ' + customer.CustomerName, 'success');
}

function clearSelectedCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    showToast('Customer removed', 'info');
}

function useWalkInCustomer() {
    clearSelectedCustomer();
    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    showToast('Using walk-in customer', 'info');
}

function filterCustomerList() {
    const term = document.getElementById('customerSearch').value.toLowerCase();
    const filtered = allCustomers.filter(c =>
        c.CustomerName.toLowerCase().includes(term) || (c.Phone && c.Phone.includes(term))
    );
    displayCustomerList(filtered);
}

function displayCustomerList(customers) {
    const list = customers || allCustomers;
    const container = document.getElementById('customerListModal');

    if (!list || list.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted">No customers found</div>';
        return;
    }

    container.innerHTML = list.map(c => `
        <div class="customer-item-modal" onclick='selectCustomer(${JSON.stringify(c).replace(/'/g, "&apos;")})'>
            <div>
                <div class="fw-bold">${escapeHtml(c.CustomerName)}</div>
                <div class="small text-muted">${c.Phone || ''}</div>
            </div>
            <i class="fas fa-chevron-right text-muted"></i>
        </div>
    `).join('');
}

// ============================================
// PRODUCT FUNCTIONS WITH BOTH DISCOUNT TYPES
// ============================================

function toggleUnits(productId) {
    const unitsDiv = document.getElementById('units-' + productId);
    const icon = document.getElementById('expand-icon-' + productId);
    if (unitsDiv) {
        if (unitsDiv.classList.contains('expanded')) {
            unitsDiv.classList.remove('expanded');
            if (icon) icon.classList.remove('rotated');
        } else {
            unitsDiv.classList.add('expanded');
            if (icon) icon.classList.add('rotated');
            unitSearchTerms[productId] = '';
            const searchInput = document.getElementById('unit-search-' + productId);
            if (searchInput) searchInput.value = '';
            displayFilteredUnits(productId);
        }
    }
}

function filterUnits(productId) {
    const searchTerm = document.getElementById('unit-search-' + productId).value.toLowerCase();
    unitSearchTerms[productId] = searchTerm;
    displayFilteredUnits(productId);
}

function displayFilteredUnits(productId) {
    const product = products.find(p => p.ProductID == productId);
    if (!product) return;

    const units = productUnits[productId] || [];
    const searchTerm = unitSearchTerms[productId] || '';
    
    let filteredUnits = units;
    if (searchTerm) {
        filteredUnits = units.filter(unit =>
            (unit.IMEINumber && unit.IMEINumber.toLowerCase().includes(searchTerm)) ||
            (unit.SerialNumber && unit.SerialNumber.toLowerCase().includes(searchTerm)) ||
            (unit.UnitNumber && unit.UnitNumber.toString().includes(searchTerm))
        );
    }

    const unitsContainer = document.getElementById('units-container-' + productId);
    if (!unitsContainer) return;

    if (filteredUnits.length === 0) {
        unitsContainer.innerHTML = '<div class="no-results">No matching units found</div>';
        return;
    }

    const discount = parseFloat(product.Discount) || 0;
    const less = parseFloat(product.Less) || 0;

    unitsContainer.innerHTML = filteredUnits.map(unit => `
        <div class="unit-item" data-unit-id="${unit.UnitID}">
            <div class="unit-info">
                <div class="unit-number">Unit #${unit.UnitNumber}</div>
                ${unit.IMEINumber ? `<div class="unit-imei"><i class="fas fa-qrcode"></i> IMEI: ${unit.IMEINumber}</div>` : ''}
                ${unit.SerialNumber ? `<div class="unit-serial"><i class="fas fa-barcode"></i> Serial: ${unit.SerialNumber}</div>` : ''}
                ${discount > 0 ? `<div class="unit-serial" style="color: #2563eb;"><i class="fas fa-percent"></i> ${discount}% discount</div>` : ''}
                ${less > 0 ? `<div class="unit-serial" style="color: #d97706;"><i class="fas fa-tag"></i> ₱${formatNumber(less)} off</div>` : ''}
            </div>
            <button class="unit-add-btn" onclick="addUnitToCart(${product.ProductID}, '${escapeHtml(product.ProductName)}', '${product.ProductCode || ''}', ${product.SellingPrice}, ${discount}, ${less}, ${unit.UnitID}, ${unit.UnitNumber}, '${unit.IMEINumber || ''}', '${unit.SerialNumber || ''}')">
                <i class="fas fa-cart-plus"></i> Add
            </button>
        </div>
    `).join('');
    
    const countSpan = document.getElementById('unit-count-' + productId);
    if (countSpan) {
        countSpan.innerText = `(${filteredUnits.length} of ${units.length} units)`;
    }
}

function addBulkToCart(productId, productName, productCode, productPrice, discountPercent, lessAmount, quantity, availableQty) {
    const qty = parseInt(quantity);
    if (isNaN(qty) || qty < 1) {
        showToast('Please enter a valid quantity', 'warning');
        return;
    }

    if (qty > availableQty) {
        showToast('Only ' + availableQty + ' units available', 'warning');
        return;
    }

    const discount = parseFloat(discountPercent) || 0;
    const less = parseFloat(lessAmount) || 0;
    const finalPrice = calculateFinalPrice(productPrice, discount, less);

    const existingIndex = cart.findIndex(item => item.id === productId && !item.unitId);

    if (existingIndex !== -1) {
        const newQty = cart[existingIndex].quantity + qty;
        if (newQty > availableQty) {
            showToast('Only ' + availableQty + ' units available', 'warning');
            return;
        }
        cart[existingIndex].quantity = newQty;
        cart[existingIndex].total = newQty * cart[existingIndex].discountedPrice;
        cart[existingIndex].originalTotal = newQty * cart[existingIndex].price;
    } else {
        cart.push({
            id: productId,
            name: productName,
            productCode: productCode,
            price: parseFloat(productPrice),
            discountPercent: discount,
            lessAmount: less,
            discountedPrice: finalPrice,
            quantity: qty,
            total: qty * finalPrice,
            originalTotal: qty * parseFloat(productPrice),
            isBulk: true
        });
    }

    updateCartDisplay();
    const discountLabel = getDiscountLabel(discount, less);
    showToast('Added ' + qty + ' x ' + productName + (discountLabel ? ' (with ' + discountLabel + ')' : ''), 'success');
}

function addUnitToCart(productId, productName, productCode, productPrice, discountPercent, lessAmount, unitId, unitNumber, imei, serial) {
    const existingItem = cart.find(item => item.unitId === unitId);
    if (existingItem) {
        showToast('This unit is already in cart', 'warning');
        return;
    }

    const discount = parseFloat(discountPercent) || 0;
    const less = parseFloat(lessAmount) || 0;
    const finalPrice = calculateFinalPrice(productPrice, discount, less);

    cart.push({
        id: productId,
        unitId: unitId,
        unitNumber: unitNumber,
        name: productName,
        productCode: productCode,
        price: parseFloat(productPrice),
        discountPercent: discount,
        lessAmount: less,
        discountedPrice: finalPrice,
        quantity: 1,
        total: finalPrice,
        originalTotal: parseFloat(productPrice),
        imei: imei || '',
        serial: serial || '',
        isBulk: false
    });

    updateCartDisplay();
    const discountLabel = getDiscountLabel(discount, less);
    showToast('Added: ' + productName + ' (Unit #' + unitNumber + ')' + (discountLabel ? ' (with ' + discountLabel + ')' : ''), 'success');
    
    const units = productUnits[productId];
    if (units) {
        const unitIndex = units.findIndex(u => u.UnitID == unitId);
        if (unitIndex !== -1) {
            units.splice(unitIndex, 1);
            displayFilteredUnits(productId);
        }
    }
}

function displayProductResults(productsList) {
    const container = document.getElementById('productResults');

    if (!productsList || productsList.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted">No products found</div>';
        return;
    }

    container.innerHTML = productsList.map(product => {
        const imageHtml = product.ProductImagePath ?
            `<img src="${product.ProductImagePath}" alt="${escapeHtml(product.ProductName)}">` :
            `<i class="fas fa-box"></i>`;

        const units = productUnits[product.ProductID] || [];
        const hasUnits = units.length > 0;
        const availableQty = product.AvailableQuantity || product.CurrentStock || 0;
        const discount = parseFloat(product.Discount) || 0;
        const less = parseFloat(product.Less) || 0;
        const finalPrice = calculateFinalPrice(product.SellingPrice, discount, less);
        const hasDiscount = discount > 0 || less > 0;
        
        // Build discount badges
        let discountBadges = '';
        if (discount > 0) {
            discountBadges += `<span class="discount-badge">-${discount}%</span>`;
        }
        if (less > 0) {
            discountBadges += `<span class="discount-badge less-badge">-₱${formatNumber(less)}</span>`;
        }

        const hasSerials = hasUnits && availableQty <= units.length;

        return `
            <div class="product-card">
                <div class="product-header" onclick="toggleUnits(${product.ProductID})">
                    <div class="product-thumb">${imageHtml}</div>
                    <div class="product-details">
                        <div class="product-name">
                            ${escapeHtml(product.ProductName)}
                            <span class="product-type-badge ${hasSerials ? 'serialized' : 'bulk'}">
                                ${hasSerials ? '📱 Individual' : '📦 Bulk'}
                            </span>
                            ${hasSerials ? `<span class="unit-count-badge" id="unit-count-${product.ProductID}">(${units.length} units)</span>` : ''}
                            ${discountBadges}
                        </div>
                        <div class="product-code">${product.ProductCode || 'N/A'}</div>
                        <div class="product-price">
                            ₱${formatNumber(finalPrice)}
                            ${hasDiscount ? `<span class="product-price original">₱${formatNumber(product.SellingPrice)}</span>` : ''}
                        </div>
                        <div class="mt-1">
                            <span class="product-stock-badge">
                                <i class="fas fa-boxes"></i> Available: ${availableQty} units
                            </span>
                        </div>
                    </div>
                    <div><i class="fas fa-chevron-down expand-icon" id="expand-icon-${product.ProductID}"></i></div>
                </div>
                ${hasSerials ? `
                    <div class="units-list" id="units-${product.ProductID}">
                        <div class="unit-search-box" style="position: relative;">
                            <i class="fas fa-search"></i>
                            <input type="text" id="unit-search-${product.ProductID}" placeholder="Search by IMEI, Serial, or Unit #..." onkeyup="filterUnits(${product.ProductID})">
                        </div>
                        <div id="units-container-${product.ProductID}">
                            <div class="text-center py-3">Loading units...</div>
                        </div>
                    </div>
                ` : `
                    <div class="bulk-add-section">
                        <div class="bulk-qty-selector">
                            <label>Quantity:</label>
                            <input type="number" id="bulk-qty-${product.ProductID}" value="1" min="1" max="${availableQty}">
                        </div>
                        <button class="bulk-add-btn" onclick="addBulkToCart(${product.ProductID}, '${escapeHtml(product.ProductName)}', '${product.ProductCode || ''}', ${product.SellingPrice}, ${discount}, ${less}, document.getElementById('bulk-qty-${product.ProductID}').value, ${availableQty})">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                `}
            </div>
        `;
    }).join('');

    // Render units for products that have them
    productsList.forEach(product => {
        const units = productUnits[product.ProductID] || [];
        if (units.length > 0) {
            const container = document.getElementById('units-container-' + product.ProductID);
            if (container) {
                const discount = parseFloat(product.Discount) || 0;
                const less = parseFloat(product.Less) || 0;
                container.innerHTML = units.map(unit => `
                    <div class="unit-item" data-unit-id="${unit.UnitID}">
                        <div class="unit-info">
                            <div class="unit-number">Unit #${unit.UnitNumber}</div>
                            ${unit.IMEINumber ? `<div class="unit-imei"><i class="fas fa-qrcode"></i> IMEI: ${unit.IMEINumber}</div>` : ''}
                            ${unit.SerialNumber ? `<div class="unit-serial"><i class="fas fa-barcode"></i> Serial: ${unit.SerialNumber}</div>` : ''}
                            ${discount > 0 ? `<div class="unit-serial" style="color: #2563eb;"><i class="fas fa-percent"></i> ${discount}% discount</div>` : ''}
                            ${less > 0 ? `<div class="unit-serial" style="color: #d97706;"><i class="fas fa-tag"></i> ₱${formatNumber(less)} off</div>` : ''}
                        </div>
                        <button class="unit-add-btn" onclick="addUnitToCart(${product.ProductID}, '${escapeHtml(product.ProductName)}', '${product.ProductCode || ''}', ${product.SellingPrice}, ${discount}, ${less}, ${unit.UnitID}, ${unit.UnitNumber}, '${unit.IMEINumber || ''}', '${unit.SerialNumber || ''}')">
                            <i class="fas fa-cart-plus"></i> Add
                        </button>
                    </div>
                `).join('');
            }
        }
    });
}

// ============================================
// BARCODE SCANNER
// ============================================

document.getElementById('barcodeInput').addEventListener('keypress', async function(e) {
    if (e.key === 'Enter') {
        const barcode = this.value.trim();
        if (barcode) {
            let foundUnit = null;
            let foundProduct = null;

            for (let product of products) {
                const units = productUnits[product.ProductID] || [];
                const unit = units.find(u =>
                    (u.IMEINumber && u.IMEINumber === barcode) || 
                    (u.SerialNumber && u.SerialNumber === barcode)
                );
                if (unit) {
                    foundUnit = unit;
                    foundProduct = product;
                    break;
                }
            }

            if (foundUnit && foundProduct) {
                const discount = parseFloat(foundProduct.Discount) || 0;
                const less = parseFloat(foundProduct.Less) || 0;
                addUnitToCart(foundProduct.ProductID, foundProduct.ProductName, foundProduct.ProductCode || '', foundProduct.SellingPrice, discount, less, foundUnit.UnitID, foundUnit.UnitNumber, foundUnit.IMEINumber || '', foundUnit.SerialNumber || '');
                this.value = '';
                return;
            }

            const product = products.find(p => p.ProductCode === barcode);
            if (product && (product.AvailableQuantity || product.CurrentStock || 0) > 0) {
                const discount = parseFloat(product.Discount) || 0;
                const less = parseFloat(product.Less) || 0;
                const availableQty = product.AvailableQuantity || product.CurrentStock || 0;
                addBulkToCart(product.ProductID, product.ProductName, product.ProductCode || '', product.SellingPrice, discount, less, 1, availableQty);
                this.value = '';
                return;
            }

            showToast('No product or unit found', 'error');
        }
    }
});

// ============================================
// SEARCH PRODUCTS
// ============================================

document.getElementById('productSearch').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    currentSearchTerm = term;
    
    if (term.length < 2) {
        displayProductResults(products);
        return;
    }

    const filtered = products.filter(p =>
        p.ProductName.toLowerCase().includes(term) ||
        (p.ProductCode && p.ProductCode.toLowerCase().includes(term)) ||
        (p.Brand && p.Brand.toLowerCase().includes(term))
    );
    
    const productIdsWithMatchingUnits = [];
    for (let product of products) {
        const units = productUnits[product.ProductID] || [];
        const hasMatchingUnit = units.some(u =>
            (u.IMEINumber && u.IMEINumber.toLowerCase().includes(term)) ||
            (u.SerialNumber && u.SerialNumber.toLowerCase().includes(term)) ||
            (u.UnitNumber && u.UnitNumber.toString().includes(term))
        );
        if (hasMatchingUnit && !filtered.some(p => p.ProductID === product.ProductID)) {
            productIdsWithMatchingUnits.push(product);
        }
    }
    
    const finalResults = [...filtered, ...productIdsWithMatchingUnits];
    displayProductResults(finalResults);
});

// ============================================
// CART FUNCTIONS WITH BOTH DISCOUNT TYPES
// ============================================

function updateCartItemQuantity(index, change) {
    const item = cart[index];
    if (!item) return;

    if (!item.isBulk) {
        showToast('Serialized items quantity cannot be changed', 'warning');
        return;
    }

    const newQty = item.quantity + change;
    if (newQty < 1) {
        removeFromCart(index);
        return;
    }

    const product = products.find(p => p.ProductID === item.id);
    if (product && newQty > (product.AvailableQuantity || product.CurrentStock || 0)) {
        showToast('Only ' + (product.AvailableQuantity || product.CurrentStock || 0) + ' units available', 'warning');
        return;
    }

    item.quantity = newQty;
    item.total = newQty * item.discountedPrice;
    item.originalTotal = newQty * item.price;
    updateCartDisplay();
}

function removeFromCart(index) {
    const item = cart[index];
    if (!item.isBulk && item.unitId) {
        const units = productUnits[item.id];
        if (units) {
            units.push({
                UnitID: item.unitId,
                UnitNumber: item.unitNumber,
                IMEINumber: item.imei,
                SerialNumber: item.serial,
                Status: 'available'
            });
            units.sort((a, b) => a.UnitNumber - b.UnitNumber);
            displayFilteredUnits(item.id);
        }
    }
    cart.splice(index, 1);
    updateCartDisplay();
    showToast('Item removed from cart', 'info');
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear entire cart?')) {
        cart.forEach(item => {
            if (!item.isBulk && item.unitId) {
                const units = productUnits[item.id];
                if (units) {
                    units.push({
                        UnitID: item.unitId,
                        UnitNumber: item.unitNumber,
                        IMEINumber: item.imei,
                        SerialNumber: item.serial,
                        Status: 'available'
                    });
                    units.sort((a, b) => a.UnitNumber - b.UnitNumber);
                    displayFilteredUnits(item.id);
                }
            }
        });
        cart = [];
        updateCartDisplay();
        showToast('Cart cleared', 'info');
    }
}

function updateCartDisplay() {
    const cartDiv = document.getElementById('cartItems');
    const summary = document.getElementById('cartSummary');
    const clearBtn = document.getElementById('clearCartBtn');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (cart.length === 0) {
        cartDiv.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i><p>Cart is empty</p></div>';
        summary.style.display = 'none';
        clearBtn.style.display = 'none';
        checkoutBtn.disabled = true;
        document.getElementById('discountRow').style.display = 'none';
        return;
    }

    cartDiv.innerHTML = cart.map((item, idx) => {
        const hasDiscount = item.discountPercent > 0 || item.lessAmount > 0;
        const discountLabel = getDiscountLabel(item.discountPercent, item.lessAmount);
        const badgeClass = getDiscountBadgeClass(item.discountPercent, item.lessAmount);
        
        return `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">
                        ${escapeHtml(item.name)}
                        ${hasDiscount ? `<span class="discount-badge ${badgeClass}" style="font-size:10px;color:white;padding:1px 6px;border-radius:12px;margin-left:5px;">${discountLabel}</span>` : ''}
                    </div>
                    <div class="cart-item-code">${item.productCode || 'N/A'}</div>
                    ${item.unitNumber ? `<div class="cart-item-code">Unit #${item.unitNumber}</div>` : ''}
                    ${item.imei ? `<div class="cart-item-imei"><i class="fas fa-qrcode"></i> IMEI: ${item.imei}</div>` : ''}
                    ${item.serial ? `<div class="cart-item-serial"><i class="fas fa-barcode"></i> Serial: ${item.serial}</div>` : ''}
                    <div class="cart-item-price">
                        ₱${formatNumber(item.price)} each
                        ${hasDiscount ? `→ ₱${formatNumber(item.discountedPrice)}` : ''}
                    </div>
                </div>
                <div class="cart-item-qty">
                    ${item.isBulk ? `
                        <button class="qty-btn" onclick="updateCartItemQuantity(${idx}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button class="qty-btn" onclick="updateCartItemQuantity(${idx}, 1)">+</button>
                    ` : `
                        <span style="min-width: 30px; text-align: center;">1</span>
                    `}
                </div>
                <div>
                    ${hasDiscount ? `<div class="cart-item-total original">₱${formatNumber(item.originalTotal)}</div>` : ''}
                    <div class="cart-item-total ${hasDiscount ? 'discounted' : ''}">₱${formatNumber(item.total)}</div>
                </div>
                <div class="remove-item" onclick="removeFromCart(${idx})"><i class="fas fa-trash"></i></div>
            </div>
        `;
    }).join('');

    // Calculate totals
    const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
    const totalDiscount = cart.reduce((sum, i) => sum + ((i.price - i.discountedPrice) * i.quantity), 0);
    const finalTotal = cart.reduce((sum, i) => sum + i.total, 0);

    document.getElementById('subtotalAmount').innerHTML = '₱' + formatNumber(subtotal);
    
    if (totalDiscount > 0) {
        document.getElementById('discountRow').style.display = 'flex';
        document.getElementById('discountAmount').innerHTML = '-₱' + formatNumber(totalDiscount);
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
    
    document.getElementById('totalAmount').innerHTML = '₱' + formatNumber(finalTotal);
    summary.style.display = 'block';
    clearBtn.style.display = 'inline-block';
    checkoutBtn.disabled = false;
    calculateChange();
}

// ============================================
// PAYMENT METHODS
// ============================================

function selectPaymentMethod(method, btn) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('cashPaymentDiv').style.display = method === 'cash' ? 'block' : 'none';
    document.getElementById('cardPaymentDiv').style.display = method === 'card' ? 'block' : 'none';
    document.getElementById('gcashPaymentDiv').style.display = method === 'gcash' ? 'block' : 'none';
    
    if (method === 'gcash') {
        document.getElementById('gcashConfirmed').checked = false;
        gcashPaymentConfirmed = false;
    }
}

document.getElementById('amountReceived').addEventListener('input', calculateChange);

function calculateChange() {
    const totalText = document.getElementById('totalAmount').innerText;
    const total = parseFloat(totalText.replace('₱', '').replace(/,/g, '')) || 0;
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    const change = received - total;
    document.getElementById('changeAmount').value = change >= 0 ? '₱' + formatNumber(change) : 'Insufficient';
}

// ============================================
// CHECKOUT WITH BOTH DISCOUNT TYPES
// ============================================

async function processCheckout() {
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        return;
    }

    const total = parseFloat(document.getElementById('totalAmount').innerText.replace('₱', '').replace(/,/g, '')) || 0;

    if (selectedPaymentMethod === 'gcash') {
        const confirmed = document.getElementById('gcashConfirmed');
        if (!confirmed.checked) {
            showToast('Please confirm GCash payment first', 'warning');
            return;
        }
    }

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
        document.getElementById('gcashConfirmed').checked = false;
        gcashPaymentConfirmed = false;
        await loadProducts();
    }

    checkoutBtn.disabled = false;
    checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Complete Transaction';
}

async function saveTransaction() {
    const finalTotal = parseFloat(document.getElementById('totalAmount').innerText.replace('₱', '').replace(/,/g, '')) || 0;
    const subtotal = parseFloat(document.getElementById('subtotalAmount').innerText.replace('₱', '').replace(/,/g, '')) || 0;
    const totalDiscount = subtotal - finalTotal;
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    const customerName = document.getElementById('customerName').value || 'Walk-in Customer';
    const customerPhone = document.getElementById('customerPhone').value || '';
    
    let gcashReference = '';
    if (selectedPaymentMethod === 'gcash') {
        gcashReference = document.getElementById('gcashNumber').value.trim() || STORE_GCASH_NUMBER;
    }

    const transactionData = {
        receipt_no: 'INV-' + new Date().getTime(),
        customer: customerName,
        customer_phone: customerPhone,
        subtotal: subtotal,
        total_discount: totalDiscount,
        amount: finalTotal,
        payment_method: selectedPaymentMethod,
        amount_received: selectedPaymentMethod === 'cash' ? received : finalTotal,
        change: selectedPaymentMethod === 'cash' ? received - finalTotal : 0,
        gcash_reference: gcashReference,
        cashier_name: '<?php echo $cashierName; ?>',
        items: cart.map(item => ({
            id: item.id,
            unit_id: item.unitId || null,
            name: item.name,
            product_code: item.productCode,
            quantity: item.quantity,
            price: item.price,
            discount_percent: item.discountPercent || 0,
            less_amount: item.lessAmount || 0,
            discounted_price: item.discountedPrice,
            total: item.total,
            original_total: item.originalTotal || item.total,
            imei: item.imei || '',
            serial: item.serial || '',
            is_bulk: item.isBulk || false
        }))
    };

    try {
        const response = await fetch(API_URL + '?action=saveTransaction', {
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
// RECEIPT FUNCTIONS WITH BOTH DISCOUNT TYPES
// ============================================

function generateReceipt() {
    const now = new Date();
    const receiptNumber = 'INV-' + now.getTime();
    const customerName = document.getElementById('customerName').value || 'Walk-in Customer';
    const finalTotal = parseFloat(document.getElementById('totalAmount').innerText.replace('₱', '').replace(/,/g, '')) || 0;
    const subtotal = parseFloat(document.getElementById('subtotalAmount').innerText.replace('₱', '').replace(/,/g, '')) || 0;
    const totalDiscount = subtotal - finalTotal;
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    const change = received - finalTotal;
    const cashierName = '<?php echo $cashierName; ?>';
    const branchName = '<?php echo $branchName; ?>';
    const gcashNumber = document.getElementById('gcashNumber').value.trim() || STORE_GCASH_NUMBER;
    
    let productsHtml = '';
    if (cart && cart.length > 0) {
        productsHtml = '<div class="receipt-line-dashed">- - - - - - - - - - - -</div>';
        productsHtml += '<div style="font-weight: bold; margin-bottom: 5px;">Products Purchased:</div>';
        cart.forEach(item => {
            const hasDiscount = item.discountPercent > 0 || item.lessAmount > 0;
            const discountLabel = getDiscountLabel(item.discountPercent, item.lessAmount);
            
            productsHtml += `
                <div class="receipt-row" style="font-size: 10px;">
                    <span>${escapeHtml(item.name)} ${item.unitNumber ? `(Unit #${item.unitNumber})` : `(x${item.quantity})`}</span>
                    <span>₱${formatNumber(item.total)}</span>
                </div>
                <div class="receipt-row" style="font-size: 9px; color: #666;">
                    <span>${item.quantity} x ₱${formatNumber(item.price)}</span>
                    <span>Code: ${escapeHtml(item.productCode || 'N/A')}</span>
                </div>
            `;
            
            if (hasDiscount) {
                productsHtml += `
                    <div class="receipt-row" style="font-size: 9px; color: #dc3545;">
                        <span>Discount:</span>
                        <span>${discountLabel} → ₱${formatNumber(item.discountedPrice)} each</span>
                    </div>
                `;
            }
            
            if (item.imei) {
                productsHtml += `<div class="receipt-row" style="font-size: 9px; color: #666;"><span>IMEI:</span><span>${escapeHtml(item.imei)}</span></div>`;
            }
            if (item.serial) {
                productsHtml += `<div class="receipt-row" style="font-size: 9px; color: #666;"><span>Serial:</span><span>${escapeHtml(item.serial)}</span></div>`;
            }
        });
        productsHtml += '<div class="receipt-line-dashed">- - - - - - - - - - - -</div>';
    }
    
    let receiptHTML = `
        <div class="receipt-content">
            <div class="receipt-header">
                <h3>Damit ni Beybi</h3>
                <small>${branchName}</small>
            </div>
            <div class="receipt-line-dashed">- - - - - - - - - - - -</div>
            <div class="receipt-row"><span>Receipt No:</span><span><strong>${receiptNumber}</strong></span></div>
            <div class="receipt-row"><span>Date:</span><span>${now.toLocaleString()}</span></div>
            <div class="receipt-row"><span>Customer:</span><span>${escapeHtml(customerName)}</span></div>
            ${productsHtml}
            <div class="receipt-row"><span>Subtotal:</span><span>₱${formatNumber(subtotal)}</span></div>
    `;
    
    if (totalDiscount > 0) {
        receiptHTML += `
            <div class="receipt-row" style="color: #dc3545;"><span>Total Discount:</span><span>-₱${formatNumber(totalDiscount)}</span></div>
        `;
    }
    
    receiptHTML += `
            <div class="receipt-row" style="font-weight: 800; font-size: 14px; border-top: 1px dashed #666; padding-top: 5px;">
                <span>Total:</span>
                <span>₱${formatNumber(finalTotal)}</span>
            </div>
            <div class="receipt-line-dashed">- - - - - - - - - - - -</div>
            <div class="receipt-row"><span>Payment Method:</span><span>${selectedPaymentMethod.toUpperCase()}</span></div>
    `;
    
    if (selectedPaymentMethod === 'cash') {
        receiptHTML += `
            <div class="receipt-row"><span>Amount Received:</span><span>₱${formatNumber(received)}</span></div>
            <div class="receipt-row"><span>Change:</span><span>₱${formatNumber(change)}</span></div>
        `;
    }
    
    if (selectedPaymentMethod === 'gcash') {
        receiptHTML += `
            <div class="receipt-row"><span>GCash Number:</span><span>${escapeHtml(gcashNumber)}</span></div>
            <div class="receipt-row" style="color:#00a65a;"><span>Status:</span><span>✅ PAID</span></div>
        `;
    }
    
    receiptHTML += `
            <div class="receipt-line-dashed">- - - - - - - - - - - -</div>
            <div class="receipt-footer text-center mt-2">
                Cashier: ${cashierName}<br>
                Thank you for your purchase!<br>
                Please come again
            </div>
        </div>
    `;
    
    document.getElementById('receiptContent').innerHTML = receiptHTML;
    const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
    receiptModal.show();
}

function printReceipt() {
    const content = document.getElementById('receiptContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Damit ni Beybi - Sales Receipt</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Courier New', monospace;
                    margin: 0;
                    padding: 0;
                    background: white;
                }
                .receipt-container {
                    display: flex;
                    justify-content: center;
                    width: 100%;
                }
                .receipt-content {
                    max-width: 350px;
                    width: 100%;
                    margin: 0 30px;
                    font-size: 12px;
                }
                .receipt-header { text-align: center; margin-bottom: 15px; }
                .receipt-header h3 { margin: 0; font-size: 20px; font-weight: bold; }
                .receipt-header small { font-size: 10px; color: #666; }
                .receipt-line-dashed { text-align: center; letter-spacing: 2px; color: #666; margin: 5px 0; }
                .receipt-row { display: flex; justify-content: space-between; margin: 5px 0; }
                .receipt-footer { text-align: center; margin-top: 15px; font-size: 10px; }
                .text-center { text-align: center; }
                .mt-2 { margin-top: 10px; }
                @media print {
                    body { padding: 0; margin: 0; }
                    .receipt-content { margin: 0 30px; }
                    @page { margin: 0; }
                }
            </style>
        </head>
        <body>
            <div class="receipt-container">${content}</div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
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
    loadCustomers();
    updateTime();
    setInterval(updateTime, 1000);
    document.getElementById('barcodeInput').focus();
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>