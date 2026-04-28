<?php
// pages/installments.php - Installment/Loan Management Page with Unit Search
?>
<style>
    .installment-container {
        padding: 0;
        width: 100%;
    }
    
    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    
    /* Form Section */
    .form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .card-header {
        background: white;
        border-bottom: 1px solid #eef2f7;
        padding: 15px 20px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-body {
        padding: 20px;
    }
    
    /* Customer Section */
    .selected-customer-display {
        background: #f0fdf4;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .selected-customer-text {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .selected-customer-text i {
        font-size: 24px;
        color: #28a745;
    }
    
    .customer-name-selected {
        font-weight: 700;
        color: #166534;
        font-size: 16px;
    }
    
    .remove-customer-selected {
        background: #fee2e2;
        color: #dc3545;
        border: none;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
    }
    
    .btn-select-customer {
        background: linear-gradient(135deg, #010d42 0%, #0502ac 100%);
        color: white;
        border: none;
        padding: 5px 15px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
    }
    
    /* Product Selection */
    .product-search-box {
        position: relative;
        margin-bottom: 15px;
    }
    
    .product-search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .product-search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .product-list-container {
        max-height: 350px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    
    .product-card {
        background: white;
        border-radius: 12px;
        margin-bottom: 10px;
        border: 1px solid #eef2f7;
        overflow: hidden;
    }
    
    .product-header {
        display: flex;
        padding: 12px;
        gap: 10px;
        cursor: pointer;
        background: #f8fafc;
    }
    
    .product-header:hover {
        background: #eef2ff;
    }
    
    .product-thumb {
        width: 50px;
        height: 50px;
        border-radius: 8px;
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
    
    .product-details {
        flex: 1;
    }
    
    .product-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
        color: #1a2a3a;
    }
    
    .product-code {
        font-size: 10px;
        color: #6c7a91;
        font-family: monospace;
    }
    
    .product-price {
        font-size: 14px;
        font-weight: 700;
        color: #28a745;
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
    
    /* Unit Search Box Styles */
    .unit-search-box {
        padding: 8px 12px;
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    
    .unit-search-box input {
        width: 100%;
        padding: 6px 10px 6px 28px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 11px;
    }
    
    .unit-search-box i {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 11px;
    }
    
    .units-list {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background: white;
    }
    
    .units-list.expanded {
        max-height: 250px;
        overflow-y: auto;
    }
    
    .no-units-found {
        padding: 15px;
        text-align: center;
        color: #6c7a91;
        font-size: 12px;
    }
    
    .unit-count-badge {
        font-size: 10px;
        background: #e2e8f0;
        padding: 2px 6px;
        border-radius: 20px;
        margin-left: 8px;
    }
    
    .unit-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
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
        font-size: 11px;
        color: #4f9eff;
    }
    
    .unit-imei, .unit-serial {
        font-size: 9px;
        font-family: monospace;
        color: #6c7a91;
        margin-top: 2px;
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
    
    .bulk-add-section {
        padding: 10px 12px;
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
        gap: 8px;
    }
    
    .bulk-qty-selector input {
        width: 60px;
        padding: 5px;
        text-align: center;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
    }
    
    .bulk-select-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 5px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
    }
    
    .expand-icon {
        transition: transform 0.2s;
    }
    
    .expand-icon.rotated {
        transform: rotate(180deg);
    }
    
    /* Selected Products List */
    .selected-products-list {
        margin-top: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        max-height: 250px;
        overflow-y: auto;
    }
    
    .selected-product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
    }
    
    .selected-product-item:last-child {
        border-bottom: none;
    }
    
    .selected-product-info {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .selected-product-thumb {
        width: 35px;
        height: 35px;
        border-radius: 6px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .selected-product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .selected-product-thumb i {
        font-size: 16px;
        color: #94a3b8;
    }
    
    .selected-product-details {
        flex: 1;
    }
    
    .selected-product-name {
        font-weight: 600;
        font-size: 13px;
    }
    
    .selected-product-price {
        font-size: 11px;
        color: #4f9eff;
    }
    
    .selected-product-imei, .selected-product-serial {
        font-size: 9px;
        color: #6c7a91;
        font-family: monospace;
    }
    
    .selected-product-qty {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .qty-btn {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        font-size: 12px;
    }
    
    .selected-product-remove {
        color: #dc3545;
        cursor: pointer;
        margin-left: 10px;
    }
    
    /* Calculation Results */
    .calculation-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
    }
    
    .calculation-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 13px;
    }
    
    .calculation-row.total {
        font-size: 16px;
        font-weight: 800;
        color: #1a2a3a;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        margin-top: 10px;
    }
    
    /* Installments Table */
    .installments-table-container {
        overflow-x: auto;
    }
    
    .installments-table {
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
    }
    
    .installments-table th {
        background: #f8fafc;
        padding: 12px;
        font-size: 12px;
        text-align: left;
    }
    
    .installments-table td {
        padding: 12px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .installments-table tr {
        cursor: pointer;
    }
    
    .installments-table tr:hover td {
        background: #f8fafc;
    }
    
    .badge-paid { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-pending { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-overdue { background: #fee2e2; color: #dc3545; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-active { background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-completed { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-returned { background: #6c757d; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    
    .search-filter {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .search-filter input, .search-filter select {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
    }
    
    .search-filter input {
        flex: 1;
        min-width: 200px;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .pagination button {
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .pagination button.active {
        background: #4f9eff;
        color: white;
        border-color: #4f9eff;
    }
    
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
    
    .pay-btn {
        background: #10b981;
        color: white;
        border: none;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        transition: all 0.2s;
    }
    
    .pay-btn:hover {
        background: #059669;
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
    
    /* Payment Modal */
    .payment-summary-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin: 15px 0;
    }
    
    .payment-summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 13px;
    }
    
    .payment-summary-total {
        border-top: 2px solid #e2e8f0;
        padding-top: 10px;
        margin-top: 5px;
        font-weight: 700;
        font-size: 16px;
    }
    
    /* Receipt Modal */
    .receipt-content {
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }
    
    .receipt-line-dashed {
        text-align: center;
        letter-spacing: 2px;
        margin: 5px 0;
    }
    
    .receipt-header {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin: 5px 0;
    }
    
    .receipt-total {
        border-top: 1px dashed #ccc;
        margin-top: 10px;
        padding-top: 10px;
    }
    
    .receipt-footer {
        text-align: center;
        margin-top: 15px;
    }
    
    .modal {
        z-index: 1050;
    }
    
    .modal-backdrop {
        z-index: 1040;
    }
    
    #paymentModal {
        z-index: 1060;
    }
    
    #receiptModal {
        z-index: 1070;
    }
    
    .toast-custom-container {
        z-index: 1080;
    }
    
    .modal.show {
        display: block;
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .installments-table th, .installments-table td {
            padding: 8px;
            font-size: 11px;
        }
        
        .modal-dialog {
            margin: 1rem;
        }
        
        .product-header {
            flex-wrap: wrap;
        }
        
        .bulk-add-section {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="installment-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-hand-holding-usd"></i> Installment / Loan Management</h4>
            <p class="text-muted mb-0">Create installment plans with products (Bulk or Serialized)</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalLoanAmount">₱0</div>
            <div class="stat-label">Total Loan Amount</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="activeInstallments">0</div>
            <div class="stat-label">Active Installments</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-value" id="overdueInstallments">0</div>
            <div class="stat-label">Overdue</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-percent"></i></div>
            <div class="stat-value" id="collectionRate">0%</div>
            <div class="stat-label">Collection Rate</div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column - Create New Installment -->
        <div class="col-lg-6 mb-4">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> New Installment Plan
                    <button class="btn-select-customer" onclick="openCustomerModal()">
                        <i class="fas fa-users"></i> Select Customer
                    </button>
                </div>
                <div class="card-body">
                    <div id="selectedCustomerDisplay" style="display: none;" class="selected-customer-display">
                        <div class="selected-customer-text">
                            <i class="fas fa-user-circle"></i>
                            <div>
                                <div class="customer-name-selected" id="selectedCustomerNameText">-</div>
                                <div class="small text-muted" id="selectedCustomerPhoneText"></div>
                                <div class="small mt-1" id="selectedCustomerStatsText"></div>
                            </div>
                        </div>
                        <button class="remove-customer-selected" onclick="clearSelectedCustomer()">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                    
                    <div id="manualCustomerForm">
                        <h6 class="mb-3"><i class="fas fa-user"></i> Customer Information</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input type="text" id="customerName" class="form-control form-control-sm" placeholder="Customer Name *">
                            </div>
                            <div class="col-md-6">
                                <input type="text" id="customerPhone" class="form-control form-control-sm" placeholder="Phone Number">
                            </div>
                            <div class="col-12">
                                <textarea id="customerAddress" class="form-control form-control-sm" rows="2" placeholder="Address"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mb-3"><i class="fas fa-box"></i> Select Products</h6>
                    <div class="product-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="productSearch" placeholder="Search by name, code...">
                    </div>
                    <div class="product-list-container" id="productListContainer">
                        <div class="text-center py-4"><div class="loading-spinner"></div> Loading products...</div>
                    </div>
                    
                    <div id="selectedProductsContainer" style="display: none;">
                        <h6 class="mb-2 mt-3"><i class="fas fa-shopping-cart"></i> Selected Items</h6>
                        <div class="selected-products-list" id="selectedProductsList"></div>
                    </div>
                    
                    <h6 class="mb-3 mt-3"><i class="fas fa-calculator"></i> Loan Terms</h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Down Payment (₱)</label>
                            <input type="number" id="downPayment" class="form-control form-control-sm" value="0" step="100" oninput="calculateTotal()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Interest Rate (%)</label>
                            <input type="number" id="interestRate" class="form-control form-control-sm" value="0" step="1" oninput="calculateTotal()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Penalty Rate (%)</label>
                            <input type="number" id="penaltyRate" class="form-control form-control-sm" value="0" step="1" oninput="calculateTotal()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Number of Months</label>
                            <select id="months" class="form-select form-select-sm" onchange="calculateTotal()">
                                <option value="3">3 months</option>
                                <option value="6">6 months</option>
                                <option value="9">9 months</option>
                                <option value="12" selected>12 months</option>
                                <option value="18">18 months</option>
                                <option value="24">24 months</option>
                                <option value="36">36 months</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="calculation-box" id="calculationResults" style="display: none;">
                        <div class="calculation-row"><span>Total Product Price:</span><span>₱<span id="totalPrice">0</span></span></div>
                        <div class="calculation-row"><span>Down Payment:</span><span>₱<span id="displayDownPayment">0</span></span></div>
                        <div class="calculation-row"><span>Loan Amount:</span><span>₱<span id="loanAmount">0</span></span></div>
                        <div class="calculation-row"><span>Interest (<span id="interestRateDisplay">0</span>%):</span><span>₱<span id="totalInterest">0</span></span></div>
                        <div class="calculation-row total"><span>Total Amount:</span><span>₱<span id="totalAmount">0</span></span></div>
                        <div class="calculation-row" style="color: #4f9eff;"><span>Monthly Payment:</span><span><strong>₱<span id="monthlyPayment">0</span></strong></span></div>
                    </div>
                    
                    <div class="mt-3"><textarea id="notes" class="form-control form-control-sm" rows="2" placeholder="Notes (optional)"></textarea></div>
                    <button class="btn btn-primary w-100 mt-3" id="createBtn" onclick="createInstallment()" disabled><i class="fas fa-save"></i> Create Installment Plan</button>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Payment Schedule Preview -->
        <div class="col-lg-6 mb-4">
            <div class="form-card">
                <div class="card-header"><i class="fas fa-calendar-alt"></i> Payment Schedule Preview</div>
                <div class="card-body" id="paymentSchedulePreview">
                    <div class="text-center py-4 text-muted"><i class="fas fa-calculator fa-2x mb-2 d-block"></i><p>Select products and enter loan terms to see payment schedule</p></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Installments List -->
    <div class="form-card">
        <div class="card-header"><i class="fas fa-list"></i> Active Installments</div>
        <div class="card-body">
            <div class="search-filter">
                <input type="text" id="installmentSearch" placeholder="Search by customer name or receipt no..." onkeyup="filterInstallments()">
                <select id="statusFilter" onchange="filterInstallments()">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="completed">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="returned">Returned</option>
                </select>
            </div>
            <div class="installments-table-container">
                <table class="installments-table">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Customer</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Monthly</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="installmentsTableBody">
                        <tr><td colspan="9" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="pagination"></div>
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

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-bill"></i> Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="paymentInstallmentId">
                <input type="hidden" id="paymentPaymentNo">
                
                <div class="mb-3">
                    <label class="form-label">Regular Amount</label>
                    <input type="text" id="paymentRegularAmount" class="form-control" readonly style="background: #f8fafc;">
                </div>
                
                <div class="mb-3" id="penaltyRow" style="display: none;">
                    <label class="form-label">Penalty Amount <span class="text-danger">(Late Payment)</span></label>
                    <input type="text" id="paymentPenaltyAmount" class="form-control" readonly style="background: #fef3c7; color: #d97706;">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Total Amount Due</label>
                    <input type="text" id="paymentAmount" class="form-control" readonly style="background: #f8fafc; font-weight: bold;">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select id="paymentMethod" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Reference Number</label>
                    <input type="text" id="paymentReference" class="form-control" placeholder="Reference number (optional)">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="paymentNotes" class="form-control" rows="2" placeholder="Notes (optional)"></textarea>
                </div>
                
                <div id="paymentSummaryBox" class="payment-summary-box" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmPaymentBtn">Confirm Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white;">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> Payment Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptContent" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-4">Loading receipt...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="printPaymentReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Installment Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Installment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/installmentdata.php';
const CUSTOMER_API_URL = '/SIDJAN/datafetcher/addcustomerdata.php';
const PRODUCT_API_URL = '/SIDJAN/datafetcher/productdata.php';

// Global variables
let allProducts = [];
let selectedProducts = [];
let allInstallments = [];
let filteredInstallments = [];
let allCustomers = [];
let selectedCustomer = null;
let currentPage = 1;
let itemsPerPage = 10;
let currentDetailsModal = null;
let currentPaymentModal = null;
let currentReceiptModal = null;
let currentConfirmModal = null;
let productUnits = {};
let unitSearchTerms = {};

// ============================================
// CUSTOM CONFIRM POPUP FUNCTION
// ============================================

function showConfirmPopup(title, message, onConfirm, onCancel = null) {
    // Remove existing modal if any
    if (currentConfirmModal) {
        try {
            currentConfirmModal.dispose();
        } catch(e) {}
        const existingModal = document.getElementById('confirmPopupModal');
        if (existingModal) existingModal.remove();
    }
    
    // Remove any existing backdrops
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(backdrop => backdrop.remove());
    
    const modalHtml = `
        <div class="modal fade" id="confirmPopupModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 10000;">
            <div class="modal-dialog modal-dialog-centered" style="z-index: 10001;">
                <div class="modal-content" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #1f2937, #2d3a4a); color: white; border-radius: 16px 16px 0 0;">
                        <h5 class="modal-title"><i class="fas fa-question-circle"></i> ${title}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 20px;">
                        <p style="white-space: pre-line; font-size: 14px; margin: 0;">${message}</p>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #eef2f7; padding: 15px 20px;">
                        <button class="btn btn-secondary" id="confirmPopupCancelBtn" style="padding: 8px 20px;">Cancel</button>
                        <button class="btn btn-primary" id="confirmPopupOkBtn" style="padding: 8px 20px; background: #4f9eff; border: none;">OK</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('confirmPopupModal');
    
    // Ensure the modal is appended to body and visible
    document.body.style.overflow = 'hidden';
    
    currentConfirmModal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });
    
    // Set up event handlers
    const okBtn = document.getElementById('confirmPopupOkBtn');
    const cancelBtn = document.getElementById('confirmPopupCancelBtn');
    
    const cleanup = () => {
        document.body.style.overflow = '';
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        if (modalElement) modalElement.remove();
    };
    
    okBtn.onclick = () => {
        try {
            currentConfirmModal.hide();
        } catch(e) {}
        setTimeout(() => {
            cleanup();
            if (onConfirm) onConfirm();
        }, 200);
    };
    
    cancelBtn.onclick = () => {
        try {
            currentConfirmModal.hide();
        } catch(e) {}
        setTimeout(() => {
            cleanup();
            if (onCancel) onCancel();
        }, 200);
    };
    
    modalElement.addEventListener('hidden.bs.modal', () => {
        cleanup();
    });
    
    currentConfirmModal.show();
}

// ============================================
// CUSTOM ALERT POPUP FUNCTION
// ============================================

function showAlertPopup(title, message, type = 'info', onClose = null) {
    // Remove existing modal if any
    const existingModal = document.getElementById('alertPopupModal');
    if (existingModal) existingModal.remove();
    
    // Remove any existing backdrops
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(backdrop => backdrop.remove());
    
    let icon = 'fa-info-circle';
    let iconColor = '#4f9eff';
    let headerBg = 'linear-gradient(135deg, #1f2937, #2d3a4a)';
    
    if (type === 'success') {
        icon = 'fa-check-circle';
        iconColor = '#28a745';
        headerBg = 'linear-gradient(135deg, #28a745, #1e7e34)';
    } else if (type === 'error') {
        icon = 'fa-exclamation-circle';
        iconColor = '#dc3545';
        headerBg = 'linear-gradient(135deg, #dc3545, #bd2130)';
    } else if (type === 'warning') {
        icon = 'fa-exclamation-triangle';
        iconColor = '#ffc107';
        headerBg = 'linear-gradient(135deg, #d97706, #b45309)';
    }
    
    const modalHtml = `
        <div class="modal fade" id="alertPopupModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 10000;">
            <div class="modal-dialog modal-dialog-centered" style="z-index: 10001;">
                <div class="modal-content" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="background: ${headerBg}; color: white; border-radius: 16px 16px 0 0;">
                        <h5 class="modal-title"><i class="fas ${icon}"></i> ${title}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 20px;">
                        <p style="font-size: 14px; margin: 0;">${message}</p>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #eef2f7; padding: 15px 20px;">
                        <button class="btn btn-primary" id="alertPopupCloseBtn" style="padding: 8px 30px; background: #4f9eff; border: none;">OK</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('alertPopupModal');
    
    // Ensure the modal is appended to body and visible
    document.body.style.overflow = 'hidden';
    
    const alertModal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });
    
    const closeBtn = document.getElementById('alertPopupCloseBtn');
    
    const cleanup = () => {
        document.body.style.overflow = '';
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        if (modalElement) modalElement.remove();
    };
    
    closeBtn.onclick = () => {
        try {
            alertModal.hide();
        } catch(e) {}
        setTimeout(() => {
            cleanup();
            if (onClose) onClose();
        }, 200);
    };
    
    modalElement.addEventListener('hidden.bs.modal', () => {
        cleanup();
    });
    
    alertModal.show();
}

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
        showAlertPopup('Network Error', error.message, 'error');
        return { success: false, message: error.message };
    }
}

async function loadProducts() {
    const result = await apiCall(PRODUCT_API_URL, 'getProducts');
    if (result.success && result.data) {
        allProducts = result.data;
        for (let product of allProducts) {
            await loadProductUnits(product.ProductID);
        }
        displayProducts(allProducts);
    } else {
        document.getElementById('productListContainer').innerHTML = '<div class="text-center py-4 text-muted">No products available</div>';
    }
}

async function loadProductUnits(productId) {
    try {
        const response = await fetch(PRODUCT_API_URL + '?action=getProductUnits&product_id=' + productId);
        const data = await response.json();
        if (data.success && data.data) {
            const availableUnits = data.data.filter(unit => 
                unit.Status === 'available' || unit.Status === 'Available'
            );
            productUnits[productId] = availableUnits;
        } else {
            productUnits[productId] = [];
        }
    } catch (error) {
        console.error('Error loading units:', error);
        productUnits[productId] = [];
    }
}

async function loadInstallments() {
    const result = await apiCall(API_URL, 'getInstallments');
    if (result.success && result.data) {
        allInstallments = result.data;
        filterInstallments();
    }
}

async function loadStats() {
    const result = await apiCall(API_URL, 'getInstallmentStats');
    if (result.success && result.data) {
        const stats = result.data;
        document.getElementById('totalLoanAmount').innerHTML = '₱' + formatNumber(stats.TotalLoanAmount || 0);
        document.getElementById('activeInstallments').innerHTML = stats.ActiveInstallments || 0;
        document.getElementById('overdueInstallments').innerHTML = stats.OverdueCount || 0;
        const collectionRate = stats.TotalLoanAmount > 0 ? ((stats.TotalPaidAmount / stats.TotalLoanAmount) * 100).toFixed(1) : 0;
        document.getElementById('collectionRate').innerHTML = collectionRate + '%';
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
        showAlertPopup('Validation Error', 'Please enter customer name', 'warning');
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
            showAlertPopup('Success', 'Customer added successfully', 'success');
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerEmail').value = '';
            document.getElementById('newCustomerAddress').value = '';
            await loadCustomers();
        } else {
            showAlertPopup('Error', result.message || 'Failed to add customer', 'error');
        }
    } catch (error) {
        showAlertPopup('Network Error', 'Network error occurred', 'error');
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
    document.getElementById('selectedCustomerNameText').innerText = customer.CustomerName;
    document.getElementById('selectedCustomerPhoneText').innerHTML = customer.Phone ? `Phone: ${customer.Phone}` : '';
    document.getElementById('selectedCustomerStatsText').innerHTML = `${customer.TotalPurchases || 0} purchases • ₱${formatNumber(customer.TotalSpent || 0)} spent`;
    document.getElementById('selectedCustomerDisplay').style.display = 'flex';
    document.getElementById('customerName').value = customer.CustomerName;
    document.getElementById('customerPhone').value = customer.Phone || '';
    document.getElementById('customerAddress').value = customer.Address || '';
    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    showAlertPopup('Customer Selected', `Selected: ${customer.CustomerName}`, 'success');
}

function clearSelectedCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerAddress').value = '';
    showAlertPopup('Customer Removed', 'Customer has been removed', 'info');
}

function useWalkInCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerAddress').value = '';
    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    showAlertPopup('Walk-in Customer', 'Using walk-in customer', 'info');
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
        <div class="customer-item-modal" onclick='selectCustomer(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
            <div>
                <div class="fw-bold">${escapeHtml(c.CustomerName)}</div>
                <div class="small text-muted">${c.Phone ? `<i class="fas fa-phone"></i> ${c.Phone}` : ''} ${c.Email ? `<i class="fas fa-envelope ms-2"></i> ${c.Email}` : ''}</div>
                <div class="small mt-1">${c.TotalPurchases || 0} purchases • ₱${formatNumber(c.TotalSpent || 0)} spent</div>
            </div>
            <i class="fas fa-chevron-right text-muted"></i>
        </div>
    `).join('');
}

// ============================================
// PRODUCT DISPLAY WITH UNIT SEARCH
// ============================================

function toggleUnits(productId) {
    const unitsDiv = document.getElementById('units-' + productId);
    const icon = document.getElementById('expand-icon-' + productId);
    if (unitsDiv) {
        unitsDiv.classList.toggle('expanded');
        if (icon) icon.classList.toggle('rotated');
        
        // Reset search when opening
        if (unitsDiv.classList.contains('expanded')) {
            unitSearchTerms[productId] = '';
            const searchInput = document.getElementById('unit-search-' + productId);
            if (searchInput) searchInput.value = '';
            filterUnits(productId);
        }
    }
}

function filterUnits(productId) {
    const searchTerm = document.getElementById('unit-search-' + productId)?.value.toLowerCase() || '';
    unitSearchTerms[productId] = searchTerm;
    
    const unitsContainer = document.getElementById('units-container-' + productId);
    if (!unitsContainer) return;
    
    const units = productUnits[productId] || [];
    
    let filteredUnits = units;
    if (searchTerm) {
        filteredUnits = units.filter(unit =>
            (unit.IMEINumber && unit.IMEINumber.toLowerCase().includes(searchTerm)) ||
            (unit.SerialNumber && unit.SerialNumber.toLowerCase().includes(searchTerm)) ||
            (unit.UnitNumber && unit.UnitNumber.toString().toLowerCase().includes(searchTerm))
        );
    }
    
    if (filteredUnits.length === 0) {
        unitsContainer.innerHTML = '<div class="no-units-found"><i class="fas fa-search"></i> No matching units found</div>';
        return;
    }
    
    unitsContainer.innerHTML = filteredUnits.map(unit => `
        <div class="unit-item">
            <div class="unit-info">
                <div class="unit-number">Unit #${unit.UnitNumber}</div>
                ${unit.IMEINumber ? `<div class="unit-imei">IMEI: ${unit.IMEINumber}</div>` : ''}
                ${unit.SerialNumber ? `<div class="unit-serial">Serial: ${unit.SerialNumber}</div>` : ''}
            </div>
            <button class="unit-select-btn" onclick="addSerializedUnit(${productId}, '${escapeHtml(productUnits[productId]?.productName || '')}', '${productUnits[productId]?.productCode || ''}', ${productUnits[productId]?.sellingPrice || 0}, ${unit.UnitID}, ${unit.UnitNumber}, '${unit.IMEINumber || ''}', '${unit.SerialNumber || ''}')">Select</button>
        </div>
    `).join('');
    
    // Update unit count display
    const countSpan = document.getElementById('unit-count-' + productId);
    if (countSpan) {
        countSpan.innerText = `(${filteredUnits.length} of ${units.length})`;
    }
}

function addBulkProduct(productId, productName, productCode, productPrice, quantity, availableQty) {
    const qty = parseInt(quantity);
    if (isNaN(qty) || qty < 1) {
        showAlertPopup('Invalid Quantity', 'Please enter a valid quantity', 'warning');
        return;
    }
    
    if (qty > availableQty) {
        showAlertPopup('Insufficient Stock', `Only ${availableQty} units available`, 'warning');
        return;
    }
    
    const existingIndex = selectedProducts.findIndex(p => p.id === productId && !p.unitId);
    
    if (existingIndex !== -1) {
        const newQty = selectedProducts[existingIndex].quantity + qty;
        if (newQty > availableQty) {
            showAlertPopup('Insufficient Stock', `Only ${availableQty} units available`, 'warning');
            return;
        }
        selectedProducts[existingIndex].quantity = newQty;
        selectedProducts[existingIndex].total = newQty * selectedProducts[existingIndex].price;
    } else {
        selectedProducts.push({
            id: productId,
            name: productName,
            code: productCode,
            price: parseFloat(productPrice),
            quantity: qty,
            total: qty * parseFloat(productPrice),
            stock: availableQty,
            isBulk: true
        });
    }
    
    refreshSelectedUI();
    showAlertPopup('Product Added', `Added ${qty} x ${productName}`, 'success');
}

function addSerializedUnit(productId, productName, productCode, productPrice, unitId, unitNumber, imei, serial) {
    const existingIndex = selectedProducts.findIndex(p => p.unitId === unitId);
    if (existingIndex !== -1) {
        showAlertPopup('Duplicate Item', 'This unit is already selected', 'warning');
        return;
    }
    
    selectedProducts.push({
        id: productId,
        unitId: unitId,
        unitNumber: unitNumber,
        name: productName,
        code: productCode,
        price: parseFloat(productPrice),
        quantity: 1,
        total: parseFloat(productPrice),
        imei: imei || '',
        serial: serial || '',
        isBulk: false
    });
    
    refreshSelectedUI();
    showAlertPopup('Unit Added', `Added: ${productName} (Unit #${unitNumber})`, 'success');
    
    // Remove the added unit from available list
    const units = productUnits[productId];
    if (units) {
        const unitIndex = units.findIndex(u => u.UnitID == unitId);
        if (unitIndex !== -1) {
            units.splice(unitIndex, 1);
            filterUnits(productId);
        }
    }
}

function displayProducts(productsList) {
    const container = document.getElementById('productListContainer');
    
    if (!productsList || productsList.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted">No products available</div>';
        return;
    }
    
    container.innerHTML = productsList.map(product => {
        const imageHtml = product.ProductImagePath 
            ? `<img src="${product.ProductImagePath}" alt="${escapeHtml(product.ProductName)}">`
            : `<i class="fas fa-box"></i>`;
        
        const units = productUnits[product.ProductID] || [];
        const availableUnits = units.filter(u => u.Status === 'available');
        const hasUnits = availableUnits.length > 0;
        const availableQty = product.AvailableQuantity || 0;
        const hasSerials = hasUnits && availableQty <= availableUnits.length;
        
        // Store product info for unit display
        if (hasSerials && productUnits[product.ProductID]) {
            productUnits[product.ProductID].productName = product.ProductName;
            productUnits[product.ProductID].productCode = product.ProductCode;
            productUnits[product.ProductID].sellingPrice = product.SellingPrice;
        }
        
        return `
            <div class="product-card">
                <div class="product-header" onclick="toggleUnits(${product.ProductID})">
                    <div class="product-thumb">${imageHtml}</div>
                    <div class="product-details">
                        <div class="product-name">
                            ${escapeHtml(product.ProductName)}
                            <span class="product-type-badge ${hasSerials ? 'serialized' : 'bulk'}">
                                ${hasSerials ? '📱 Serialized' : '📦 Bulk Item'}
                            </span>
                            ${hasSerials ? `<span class="unit-count-badge" id="unit-count-${product.ProductID}">(${availableUnits.length} units)</span>` : ''}
                        </div>
                        <div class="product-code">${product.ProductCode || 'N/A'}</div>
                        <div class="product-price">₱${formatNumber(product.SellingPrice)}</div>
                        <div class="mt-1">
                            <small class="text-muted">Available: ${availableQty} units</small>
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
                        ${availableUnits.length > 0 ? availableUnits.map(unit => `
                            <div class="unit-item">
                                <div class="unit-info">
                                    <div class="unit-number">Unit #${unit.UnitNumber}</div>
                                    ${unit.IMEINumber ? `<div class="unit-imei">IMEI: ${unit.IMEINumber}</div>` : ''}
                                    ${unit.SerialNumber ? `<div class="unit-serial">Serial: ${unit.SerialNumber}</div>` : ''}
                                </div>
                                <button class="unit-select-btn" onclick="addSerializedUnit(${product.ProductID}, '${escapeHtml(product.ProductName)}', '${product.ProductCode || ''}', ${product.SellingPrice}, ${unit.UnitID}, ${unit.UnitNumber}, '${unit.IMEINumber || ''}', '${unit.SerialNumber || ''}')">Select</button>
                            </div>
                        `).join('') : '<div class="no-units-found">No available units</div>'}
                    </div>
                </div>
                ` : `
                <div class="bulk-add-section">
                    <div class="bulk-qty-selector">
                        <label>Qty:</label>
                        <input type="number" id="bulk-qty-${product.ProductID}" value="1" min="1" max="${availableQty}">
                    </div>
                    <button class="bulk-select-btn" onclick="addBulkProduct(${product.ProductID}, '${escapeHtml(product.ProductName)}', '${product.ProductCode || ''}', ${product.SellingPrice}, document.getElementById('bulk-qty-${product.ProductID}').value, ${availableQty})">
                        <i class="fas fa-cart-plus"></i> Add
                    </button>
                </div>
                `}
            </div>
        `;
    }).join('');
}

function refreshSelectedUI() {
    displaySelectedProducts();
    calculateTotal();
}

function displaySelectedProducts() {
    const container = document.getElementById('selectedProductsContainer');
    const listContainer = document.getElementById('selectedProductsList');
    
    if (selectedProducts.length === 0) { 
        container.style.display = 'none'; 
        return; 
    }
    
    container.style.display = 'block';
    
    listContainer.innerHTML = selectedProducts.map((product, idx) => {
        const imageHtml = product.image 
            ? `<img src="${product.image}" alt="${escapeHtml(product.name)}">`
            : `<i class="fas fa-box"></i>`;
        
        return `
            <div class="selected-product-item">
                <div class="selected-product-info">
                    <div class="selected-product-thumb">${imageHtml}</div>
                    <div class="selected-product-details">
                        <div class="selected-product-name">${escapeHtml(product.name)}</div>
                        <div class="selected-product-price">₱${formatNumber(product.price)}</div>
                        ${product.imei ? `<div class="selected-product-imei">IMEI: ${product.imei}</div>` : ''}
                        ${product.serial ? `<div class="selected-product-serial">Serial: ${product.serial}</div>` : ''}
                    </div>
                </div>
                <div class="selected-product-qty">
                    ${product.isBulk ? `
                        <button class="qty-btn" onclick="updateBulkQuantity(${idx}, -1)">-</button>
                        <span>${product.quantity}</span>
                        <button class="qty-btn" onclick="updateBulkQuantity(${idx}, 1)">+</button>
                    ` : `
                        <span style="min-width: 30px; text-align: center;">1</span>
                    `}
                </div>
                <div class="selected-product-remove" onclick="removeSelectedProduct(${idx})">
                    <i class="fas fa-trash"></i>
                </div>
            </div>
        `;
    }).join('');
}

function updateBulkQuantity(index, change) {
    const product = selectedProducts[index];
    if (!product || !product.isBulk) return;
    
    const newQty = product.quantity + change;
    if (newQty < 1) {
        removeSelectedProduct(index);
        return;
    }
    
    const productData = allProducts.find(p => p.ProductID === product.id);
    const maxQty = productData?.AvailableQuantity || product.stock;
    
    if (newQty > maxQty) {
        showAlertPopup('Insufficient Stock', `Only ${maxQty} units available`, 'warning');
        return;
    }
    
    product.quantity = newQty;
    product.total = newQty * product.price;
    refreshSelectedUI();
}

function removeSelectedProduct(index) {
    const product = selectedProducts[index];
    const productName = product.name;
    
    // If removing a serialized unit, add it back to available units
    if (!product.isBulk && product.unitId) {
        const units = productUnits[product.id];
        if (units) {
            units.push({
                UnitID: product.unitId,
                UnitNumber: product.unitNumber,
                IMEINumber: product.imei || '',
                SerialNumber: product.serial || '',
                Status: 'available'
            });
            filterUnits(product.id);
        }
    }
    
    selectedProducts.splice(index, 1);
    refreshSelectedUI();
}

function calculateTotal() {
    if (selectedProducts.length === 0) {
        document.getElementById('calculationResults').style.display = 'none';
        document.getElementById('createBtn').disabled = true;
        resetPaymentSchedule();
        return;
    }
    
    const totalProductPrice = selectedProducts.reduce((sum, p) => sum + (p.price * p.quantity), 0);
    const downPayment = parseFloat(document.getElementById('downPayment').value) || 0;
    const interestRate = parseFloat(document.getElementById('interestRate').value) || 0;
    const months = parseInt(document.getElementById('months').value);
    const loanAmount = totalProductPrice - downPayment;
    const totalInterest = loanAmount * (interestRate / 100);
    const totalAmount = loanAmount + totalInterest;
    const monthlyPayment = totalAmount / months;
    
    document.getElementById('totalPrice').innerText = formatNumber(totalProductPrice);
    document.getElementById('displayDownPayment').innerText = formatNumber(downPayment);
    document.getElementById('loanAmount').innerText = formatNumber(loanAmount);
    document.getElementById('interestRateDisplay').innerText = interestRate;
    document.getElementById('totalInterest').innerText = formatNumber(totalInterest);
    document.getElementById('totalAmount').innerText = formatNumber(totalAmount);
    document.getElementById('monthlyPayment').innerText = formatNumber(monthlyPayment);
    document.getElementById('calculationResults').style.display = 'block';
    document.getElementById('createBtn').disabled = false;
    generatePaymentSchedule(monthlyPayment, months);
}

function resetPaymentSchedule() {
    const container = document.getElementById('paymentSchedulePreview');
    container.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-calculator fa-2x mb-2 d-block"></i><p>Select products and enter loan terms to see payment schedule</p></div>';
}

function generatePaymentSchedule(monthlyPayment, months) {
    const container = document.getElementById('paymentSchedulePreview');
    let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Due Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
    const today = new Date();
    for (let i = 1; i <= months; i++) {
        const dueDate = new Date(today); 
        dueDate.setMonth(today.getMonth() + i);
        const dateStr = dueDate.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
        html += `<tr><td>${i}</td><td>${dateStr}</td><td>₱${formatNumber(monthlyPayment)}</td><td><span class="badge-pending">Pending</span></td></tr>`;
    }
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

async function createInstallment() {
    const customerName = document.getElementById('customerName').value.trim();
    if (!customerName) { 
        showAlertPopup('Validation Error', 'Please enter customer name', 'warning'); 
        return; 
    }
    if (selectedProducts.length === 0) { 
        showAlertPopup('Validation Error', 'Please select at least one product', 'warning'); 
        return; 
    }
    
    const totalProductPrice = selectedProducts.reduce((sum, p) => sum + (p.price * p.quantity), 0);
    const downPayment = parseFloat(document.getElementById('downPayment').value) || 0;
    const interestRate = parseFloat(document.getElementById('interestRate').value) || 0;
    const penaltyRate = parseFloat(document.getElementById('penaltyRate').value) || 0;
    const months = parseInt(document.getElementById('months').value);
    
    const data = {
        customer_name: customerName,
        customer_phone: document.getElementById('customerPhone').value,
        customer_address: document.getElementById('customerAddress').value,
        products: selectedProducts.map(p => ({ 
            product_id: p.id, 
            product_name: p.name, 
            product_code: p.code, 
            quantity: p.quantity, 
            price: p.price, 
            total: p.price * p.quantity,
            unit_id: p.unitId || null,
            unit_number: p.unitNumber || null,
            imei: p.imei || '',
            serial: p.serial || '',
            is_bulk: p.isBulk || false
        })),
        total_product_price: totalProductPrice,
        down_payment: downPayment,
        interest_rate: interestRate,
        penalty_rate: penaltyRate,
        months: months,
        notes: document.getElementById('notes').value
    };
    
    const result = await apiCall(API_URL, 'createInstallment', 'POST', data);
    if (result.success) {
        showAlertPopup('Success', result.message, 'success');
        resetForm();
        await refreshAllData();
        await loadProducts();
    } else {
        showAlertPopup('Error', result.message, 'error');
    }
}

async function refreshAllData() {
    await Promise.all([loadInstallments(), loadStats()]);
}

function filterInstallments() {
    const searchTerm = document.getElementById('installmentSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    
    filteredInstallments = allInstallments.filter(inst => {
        const matchesSearch = searchTerm === '' || 
            (inst.CustomerName && inst.CustomerName.toLowerCase().includes(searchTerm)) || 
            (inst.InstallmentNo && inst.InstallmentNo.toLowerCase().includes(searchTerm));
        
        let matchesStatus = false;
        if (statusFilter === 'all') matchesStatus = true;
        else if (statusFilter === 'active') matchesStatus = inst.Status === 'active' || inst.Status === 'overdue';
        else if (statusFilter === 'returned') matchesStatus = inst.Status === 'returned';
        else matchesStatus = inst.Status === statusFilter;
        return matchesSearch && matchesStatus;
    });
    
    currentPage = 1;
    displayInstallmentsTable();
}

function displayInstallmentsTable() {
    const tbody = document.getElementById('installmentsTableBody');
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filteredInstallments.slice(start, start + itemsPerPage);
    
    if (paginated.length === 0) { 
        tbody.innerHTML = '</tr><td colspan="9" class="text-center text-muted">No installment records found<\/td><\/tr>'; 
        document.getElementById('pagination').innerHTML = ''; 
        return; 
    }
    
    tbody.innerHTML = paginated.map(inst => {
        let statusClass = '', statusText = '';
        if (inst.Status === 'returned') { statusClass = 'badge-returned'; statusText = 'RETURNED'; }
        else if (inst.Status === 'completed') { statusClass = 'badge-paid'; statusText = 'PAID'; }
        else if (inst.Status === 'overdue') { statusClass = 'badge-overdue'; statusText = 'OVERDUE'; }
        else if (inst.Status === 'active') {
            const remaining = parseFloat(inst.RemainingBalance);
            if (remaining <= 0.01) { statusClass = 'badge-paid'; statusText = 'PAID'; }
            else { statusClass = 'badge-active'; statusText = 'ACTIVE'; }
        } else { statusClass = 'badge-active'; statusText = 'ACTIVE'; }
        
        return `<tr style="cursor: pointer;" onclick="viewInstallmentDetails(${inst.InstallmentID})">
            <td><strong>${inst.InstallmentNo || 'N/A'}</strong><\/td>
            <td>${escapeHtml(inst.CustomerName)}<\/td>
            <td>${inst.ProductName || 'Multiple Items'}<\/td>
            <td>₱${formatNumber(inst.TotalAmount)}<\/td>
            <td>₱${formatNumber(inst.MonthlyPayment)}<\/td>
            <td>₱${formatNumber(inst.PaidAmount)}<\/td>
            <td>₱${formatNumber(inst.RemainingBalance)}<\/td>
            <td><span class="${statusClass}">${statusText}<\/span><\/td>
            <td><button class="pay-btn" onclick="event.stopPropagation(); viewInstallmentDetails(${inst.InstallmentID})"><i class="fas fa-eye"></i> View<\/button><\/td>
        <\/tr>`;
    }).join('');
    
    const totalPages = Math.ceil(filteredInstallments.length / itemsPerPage);
    let paginationHTML = '';
    for (let i = 1; i <= totalPages; i++) { paginationHTML += `<button class="${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`; }
    document.getElementById('pagination').innerHTML = paginationHTML;
}

function goToPage(page) { currentPage = page; displayInstallmentsTable(); }

async function viewInstallmentDetails(installmentId) {
    const result = await apiCall(API_URL, `getInstallmentById&id=${installmentId}`);
    if (result.success && result.data) { showInstallmentDetails(result.data, result.payments); }
}

// ============================================
// INSTALLMENT DETAILS MODAL WITH PRINT BUTTON
// ============================================

function showInstallmentDetails(installment, payments) {
    const modalBody = document.getElementById('detailsModalBody');
    const isFullyPaid = parseFloat(installment.RemainingBalance) <= 0.01 || installment.Status === 'completed';
    const statusDisplay = isFullyPaid ? 'PAID' : (installment.Status === 'active' ? 'ACTIVE' : 'OVERDUE');
    const statusClass = isFullyPaid ? 'badge-paid' : (installment.Status === 'active' ? 'badge-active' : 'badge-overdue');
    
    let html = `<div class="row mb-3">
        <div class="col-md-6">
            <strong>Receipt No:</strong> ${installment.InstallmentNo}<br>
            <strong>Customer:</strong> ${escapeHtml(installment.CustomerName)}<br>
            <strong>Phone:</strong> ${installment.CustomerPhone || '-'}<br>
            <strong>Address:</strong> ${installment.CustomerAddress || '-'}
        <\/div>
        <div class="col-md-6">
            <strong>Product(s):</strong> ${installment.ProductName}<br>
            <strong>Total Amount:</strong> ₱${formatNumber(installment.TotalAmount)}<br>
            <strong>Down Payment:</strong> ₱${formatNumber(installment.DownPayment)}<br>
            <strong>Monthly Payment:</strong> ₱${formatNumber(installment.MonthlyPayment)}<br>
            <strong>Paid Amount:</strong> ₱${formatNumber(installment.PaidAmount)}<br>
            <strong>Remaining Balance:</strong> ₱${formatNumber(installment.RemainingBalance)}<br>
            <strong>Status:</strong> <span class="${statusClass}">${statusDisplay}</span>
        <\/div>
    <\/div>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>`;
    
    if (payments && payments.length > 0) {
        payments.forEach(payment => {
            const isPaid = payment.Status === 'paid';
            const statusClass = isPaid ? 'badge-paid' : (payment.Status === 'overdue' ? 'badge-overdue' : 'badge-pending');
            const statusText = isPaid ? 'PAID' : (payment.Status === 'overdue' ? 'OVERDUE' : 'PENDING');
            
            html += `<tr>
                <td>${payment.PaymentNo}<\/td>
                <td>${payment.DueDate}<\/td>
                <td>₱${formatNumber(payment.Amount)}<\/td>
                <td><span class="${statusClass}">${statusText}</span><\/td>
                <td>${payment.PaymentDate || '-'}<\/td>
                <td>
                    ${!isPaid && !isFullyPaid ? 
                        `<button class="pay-btn" onclick="openPaymentModal(${installment.InstallmentID}, ${payment.PaymentNo}, ${payment.Amount})">Pay Now</button>` : 
                        (isPaid ? 
                            `<div class="d-flex gap-2">
                                <button class="btn btn-sm btn-success" disabled>✓ Paid</button>
                                <button class="btn btn-sm btn-primary" onclick="viewPaymentReceipt(${installment.InstallmentID}, ${payment.PaymentNo}, ${payment.Amount})">
                                    <i class="fas fa-print"></i> Print Receipt
                                </button>
                            </div>` : 
                            '-')
                    }
                 <\/td>
            <\/tr>`;
        });
    }
    
    html += `<\/tbody>
    <\/table><\/div>`;
    
    modalBody.innerHTML = html;
    
    if (currentDetailsModal) { currentDetailsModal.dispose(); }
    currentDetailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    currentDetailsModal.show();
}

// ============================================
// PAYMENT FUNCTIONS - GLOBAL SCOPE
// ============================================

async function openPaymentModal(installmentId, paymentNo, amount) {
    const result = await apiCall(API_URL, `getInstallmentById&id=${installmentId}`);
    if (result.success && result.data && result.payments) {
        const payment = result.payments.find(p => p.PaymentNo == paymentNo);
        if (payment) {
            const dueDate = new Date(payment.DueDate);
            const today = new Date();
            let penaltyAmount = 0;
            
            if (today > dueDate) {
                const diffTime = Math.abs(today - dueDate);
                const daysOverdue = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const monthsOverdue = Math.ceil(daysOverdue / 30);
                penaltyAmount = amount * (result.data.PenaltyRate / 100) * monthsOverdue;
            }
            
            const totalDue = amount + penaltyAmount;
            
            document.getElementById('paymentInstallmentId').value = installmentId;
            document.getElementById('paymentPaymentNo').value = paymentNo;
            document.getElementById('paymentRegularAmount').value = '₱' + formatNumber(amount);
            
            if (penaltyAmount > 0) {
                document.getElementById('paymentPenaltyAmount').value = '₱' + formatNumber(penaltyAmount);
                document.getElementById('penaltyRow').style.display = 'block';
            } else {
                document.getElementById('penaltyRow').style.display = 'none';
            }
            
            document.getElementById('paymentAmount').value = '₱' + formatNumber(totalDue);
            document.getElementById('paymentReference').value = '';
            document.getElementById('paymentNotes').value = '';
            document.getElementById('paymentMethod').value = 'cash';
            window.currentPenaltyAmount = penaltyAmount;
            
            const summaryBox = document.getElementById('paymentSummaryBox');
            summaryBox.innerHTML = `
                <div class="payment-summary-row"><strong>Regular Amount:</strong> <span>₱${formatNumber(amount)}</span></div>
                ${penaltyAmount > 0 ? `<div class="payment-summary-row text-danger"><strong>Penalty:</strong> <span>₱${formatNumber(penaltyAmount)}</span></div>` : ''}
                <div class="payment-summary-row payment-summary-total"><strong>Total to Pay:</strong> <strong class="text-success">₱${formatNumber(totalDue)}</strong></div>
            `;
            summaryBox.style.display = 'block';
            
            if (currentPaymentModal) { currentPaymentModal.dispose(); }
            currentPaymentModal = new bootstrap.Modal(document.getElementById('paymentModal'), {
                backdrop: 'static',
                keyboard: false
            });
            currentPaymentModal.show();
        }
    }
}

document.getElementById('confirmPaymentBtn').addEventListener('click', async function() {
    showConfirmPopup('Confirm Payment', 'Are you sure you want to record this payment?', async () => {
        await recordPayment();
    });
});

async function recordPayment() {
    const installmentId = document.getElementById('paymentInstallmentId').value;
    const paymentNo = document.getElementById('paymentPaymentNo').value;
    const regularAmountRaw = document.getElementById('paymentRegularAmount').value;
    const regularAmount = parseFloat(regularAmountRaw.replace('₱', '').replace(/,/g, '')) || 0;
    const penaltyAmount = window.currentPenaltyAmount || 0;
    
    if (!installmentId || !paymentNo) { 
        showAlertPopup('Error', 'Invalid payment data', 'error'); 
        return; 
    }
    
    if (penaltyAmount > 0) {
        showConfirmPopup('Late Payment Penalty', `This payment is overdue. A penalty of ₱${formatNumber(penaltyAmount)} will be applied.\n\nDo you want to proceed?`, async () => {
            await processPayment(installmentId, paymentNo, regularAmount, penaltyAmount);
        });
    } else {
        await processPayment(installmentId, paymentNo, regularAmount, penaltyAmount);
    }
}

async function processPayment(installmentId, paymentNo, regularAmount, penaltyAmount) {
    const data = {
        installment_id: parseInt(installmentId),
        payment_no: parseInt(paymentNo),
        amount: regularAmount,
        penalty_paid: penaltyAmount,
        payment_method: document.getElementById('paymentMethod').value,
        reference_no: document.getElementById('paymentReference').value,
        notes: document.getElementById('paymentNotes').value
    };
    
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const result = await apiCall(API_URL, 'recordPayment', 'POST', data);
    
    if (result.success) {
        showAlertPopup('Success', result.message, 'success');
        
        if (currentPaymentModal) { 
            currentPaymentModal.hide(); 
        }
        
        await refreshAllData();
        
        setTimeout(async () => {
            const freshResult = await apiCall(API_URL, `getInstallmentById&id=${installmentId}`);
            if (freshResult.success && freshResult.data && freshResult.payments) {
                const payment = freshResult.payments.find(p => p.PaymentNo == paymentNo);
                if (payment && payment.Status === 'paid') {
                    const installment = freshResult.data;
                    
                    let finalPenaltyAmount = 0;
                    const dueDate = new Date(payment.DueDate);
                    const paymentDateObj = new Date(payment.PaymentDate);
                    if (paymentDateObj > dueDate) {
                        const diffTime = Math.abs(paymentDateObj - dueDate);
                        const daysOverdue = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        const monthsOverdue = Math.ceil(daysOverdue / 30);
                        finalPenaltyAmount = payment.Amount * (installment.PenaltyRate / 100) * monthsOverdue;
                    }
                    
                    generateAndShowReceipt(
                        installmentId, 
                        paymentNo, 
                        payment.Amount, 
                        finalPenaltyAmount, 
                        payment.PaymentDate, 
                        payment.ReferenceNo
                    );
                }
            } else {
                generateAndShowReceipt(installmentId, paymentNo, regularAmount, penaltyAmount);
            }
        }, 300);
        
        if (currentDetailsModal && currentDetailsModal._element.classList.contains('show')) {
            await viewInstallmentDetails(parseInt(installmentId));
        }
        
        await loadStats();
        await loadInstallments();
        
    } else {
        showAlertPopup('Error', result.message || 'Payment failed', 'error');
    }
    
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = 'Confirm Payment';
}

// ============================================
// RECEIPT FUNCTIONS
// ============================================

async function viewPaymentReceipt(installmentId, paymentNo, amount) {
    const result = await apiCall(API_URL, `getInstallmentById&id=${installmentId}`);
    if (result.success && result.data && result.payments) {
        const payment = result.payments.find(p => p.PaymentNo == paymentNo);
        if (payment && payment.Status === 'paid') {
            const installment = result.data;
            
            let penaltyAmount = 0;
            const dueDate = new Date(payment.DueDate);
            const paymentDate = new Date(payment.PaymentDate);
            if (paymentDate > dueDate) {
                const diffTime = Math.abs(paymentDate - dueDate);
                const daysOverdue = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const monthsOverdue = Math.ceil(daysOverdue / 30);
                penaltyAmount = payment.Amount * (installment.PenaltyRate / 100) * monthsOverdue;
            }
            
            generateAndShowReceipt(installmentId, paymentNo, payment.Amount, penaltyAmount, payment.PaymentDate, payment.ReferenceNo);
        } else {
            showAlertPopup('Not Found', 'Receipt not found for this payment', 'warning');
        }
    }
}

function generateAndShowReceipt(installmentId, paymentNo, amount, penaltyAmount, paymentDate, referenceNo) {
    const now = new Date();
    const paidDate = paymentDate ? new Date(paymentDate).toLocaleString() : now.toLocaleString();
    const cashierName = '<?php echo $_SESSION["NAME"] ?? "Admin"; ?>';
    const paymentMethod = document.getElementById('paymentMethod')?.value.toUpperCase() || 'CASH';
    const refNo = referenceNo || document.getElementById('paymentReference')?.value || '';
    
    apiCall(API_URL, `getInstallmentById&id=${installmentId}`).then(result => {
        if (result.success && result.data) {
            const installment = result.data;
            const payments = result.payments;
            const currentPayment = payments.find(p => p.PaymentNo == paymentNo);
            const totalPaidSoFar = installment.PaidAmount || 0;
            const remainingBalance = installment.TotalAmount - totalPaidSoFar;
            const customerName = installment.CustomerName || document.getElementById('customerName').value || 'Walk-in Customer';
            const totalPaid = amount + (penaltyAmount || 0);
            
            let html = `
                <div class="receipt-content">
                    <div class="receipt-header">
                        <h6>SIDJAN</h6>
                        <small>Electronic Products Trading</small><br>
                        <small><?php echo $_SESSION["branch_name"] ?? "-"; ?></small>
                    </div>
                    <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
                    <div class="receipt-row"><span>Receipt No:</span><span><strong>PAY-${now.getTime()}</strong></span></div>
                    <div class="receipt-row"><span>Date:</span><span>${paidDate}</span></div>
                    <div class="receipt-row"><span>Customer:</span><span>${escapeHtml(customerName)}</span></div>
                    <div class="receipt-row"><span>Installment No:</span><span>${installment.InstallmentNo || 'N/A'}</span></div>
                    <div class="receipt-row"><span>Payment #:</span><span>${paymentNo}</span></div>
                    <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
                    <div class="receipt-row"><span>Regular Amount:</span><span>₱${formatNumber(amount)}</span></div>
                    ${penaltyAmount > 0 ? `<div class="receipt-row text-danger"><span>Penalty:</span><span>₱${formatNumber(penaltyAmount)}</span></div>` : ''}
                    <div class="receipt-row receipt-total"><strong>Total Paid:</strong><strong>₱${formatNumber(totalPaid)}</strong></div>
                    <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
                    <div class="receipt-row"><span>Payment Method:</span><span>${paymentMethod}</span></div>
                    ${refNo ? `<div class="receipt-row"><span>Reference:</span><span>${escapeHtml(refNo)}</span></div>` : ''}
                    <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
                    <div class="receipt-row"><span>Total Amount:</span><span>₱${formatNumber(installment.TotalAmount)}</span></div>
                    <div class="receipt-row"><span>Total Paid:</span><span>₱${formatNumber(totalPaidSoFar)}</span></div>
                    <div class="receipt-row receipt-total"><strong>Remaining Balance:</strong><strong class="${remainingBalance > 0 ? 'text-danger' : 'text-success'}">₱${formatNumber(remainingBalance)}</strong></div>
                    <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
                    <div class="receipt-footer text-center mt-2">
                        Cashier: ${cashierName}<br>
                        Thank you for your payment!<br>
                        ${remainingBalance > 0 ? `<small>Next payment due on your next schedule</small>` : '<strong>🎉 FULLY PAID! 🎉</strong>'}
                    </div>
                </div>
            `;
            
            document.getElementById('receiptContent').innerHTML = html;
            
            if (currentReceiptModal) { 
                currentReceiptModal.dispose(); 
            }
            currentReceiptModal = new bootstrap.Modal(document.getElementById('receiptModal'), {
                backdrop: 'static',
                keyboard: false
            });
            currentReceiptModal.show();
        } else {
            showAlertPopup('Error', 'Failed to load receipt data', 'error');
        }
    }).catch(error => {
        console.error('Error generating receipt:', error);
        showAlertPopup('Error', 'Error generating receipt', 'error');
    });
}

function printPaymentReceipt() {
    const content = document.getElementById('receiptContent').innerHTML;
    const w = window.open('', '_blank');
    w.document.write(`
        <html>
        <head>
            <title>Payment Receipt</title>
            <style>
                body { font-family: monospace; padding: 20px; }
                .receipt-content { max-width: 350px; margin: 0 auto; font-size: 12px; }
                .receipt-header { text-align: center; margin-bottom: 15px; }
                .receipt-line-dashed { text-align: center; letter-spacing: 2px; margin: 5px 0; }
                .receipt-row { display: flex; justify-content: space-between; margin: 5px 0; }
                .receipt-total { border-top: 1px dashed #ccc; margin-top: 10px; padding-top: 10px; }
                .receipt-footer { text-align: center; margin-top: 15px; }
                .text-danger { color: #dc3545; }
                .text-center { text-align: center; }
                .mt-2 { margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="receipt-content">${content}</div>
            <script>window.print(); setTimeout(function() { window.close(); }, 500);<\/script>
        </body>
        </html>
    `);
    w.document.close();
}

function resetForm() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerAddress').value = '';
    document.getElementById('downPayment').value = '0';
    document.getElementById('interestRate').value = '0';
    document.getElementById('penaltyRate').value = '0';
    document.getElementById('months').value = '12';
    document.getElementById('notes').value = '';
    document.getElementById('calculationResults').style.display = 'none';
    resetPaymentSchedule();
    selectedProducts = [];
    displaySelectedProducts();
    displayProducts(allProducts);
    document.getElementById('createBtn').disabled = true;
}

function formatNumber(value) {
    if (value === null || value === undefined) return '0.00';
    return parseFloat(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event Listeners
document.getElementById('productSearch').addEventListener('input', function() { 
    const term = this.value.toLowerCase();
    if (term.length < 2) {
        displayProducts(allProducts);
        return;
    }
    const filtered = allProducts.filter(p => 
        p.ProductName.toLowerCase().includes(term) ||
        (p.ProductCode && p.ProductCode.toLowerCase().includes(term))
    );
    displayProducts(filtered);
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    loadInstallments();
    loadStats();
    loadCustomers();
});
</script>