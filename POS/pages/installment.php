<?php
// pages/installments.php - Installment/Loan Management Page with Customer Selection, IMEI, Serial, Images
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
    
    .product-table-container {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    
    .product-select-table {
        width: 100%;
        font-size: 12px;
        border-collapse: collapse;
    }
    
    .product-select-table th {
        background: #f8fafc;
        padding: 10px;
        position: sticky;
        top: 0;
        font-size: 11px;
        text-align: left;
    }
    
    .product-select-table td {
        padding: 10px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
    }
    
    .product-select-table tr:hover td {
        background: #eef2ff;
    }
    
    .product-select-table tr.selected td {
        background: linear-gradient(135deg, #eef2ff, #e6edff);
    }
    
    .product-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .product-thumb i {
        font-size: 18px;
        color: #94a3b8;
    }
    
    .product-imei, .product-serial {
        font-family: monospace;
        font-size: 10px;
        color: #6c7a91;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
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
        padding: 10px 15px;
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
        width: 40px;
        height: 40px;
        border-radius: 8px;
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
        font-size: 18px;
        color: #94a3b8;
    }
    
    .selected-product-details {
        flex: 1;
    }
    
    .selected-product-name {
        font-weight: 600;
        font-size: 14px;
    }
    
    .selected-product-price {
        font-size: 12px;
        color: #4f9eff;
    }
    
    .selected-product-imei, .selected-product-serial {
        font-size: 10px;
        color: #6c7a91;
        font-family: monospace;
    }
    
    .selected-product-qty {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .qty-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
    }
    
    .selected-product-remove {
        color: #dc3545;
        cursor: pointer;
        margin-left: 15px;
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
    
    /* Payment Summary Box */
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
    
    .modal {
    z-index: 1050;
}

.modal-backdrop {
    z-index: 1040;
}

#paymentModal {
    z-index: 1060;
}

#paymentModal .modal-content {
    z-index: 1061;
}

.toast-custom-container {
    z-index: 1070;
}

/* Ensure modal shows on top */
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
        
        .product-select-table {
            font-size: 10px;
        }
        
        .product-select-table th,
        .product-select-table td {
            padding: 6px;
        }
    }
</style>

<div class="installment-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-hand-holding-usd"></i> Installment / Loan Management</h4>
            <p class="text-muted mb-0">Create installment plans with multiple products (IMEI/Serial included)</p>
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
                    <!-- Selected Customer Display -->
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
                    
                    <!-- Customer Information Form -->
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
                        <input type="text" id="productSearch" placeholder="Search by name, code, IMEI, or Serial...">
                    </div>
                    <div class="product-table-container">
                        <table class="product-select-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Code</th>
                                    <th>Product Name</th>
                                    <th>IMEI</th>
                                    <th>Serial</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                                <tr><td colspan="8" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                            </tbody>
                        </table>
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
                
                <!-- Payment Summary Box -->
                <div id="paymentSummaryBox" class="payment-summary-box" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmPaymentBtn">Confirm Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Installment Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Installment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
const API_URL = '/POS/datafetcher/installmentdata.php';
const CUSTOMER_API_URL = '/POS/datafetcher/addcustomerdata.php';
const PRODUCT_API_URL = '/POS/datafetcher/productdata.php';

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
        return { success: false, message: error.message };
    }
}

async function loadProducts() {
    const result = await apiCall(PRODUCT_API_URL, 'getProducts');
    if (result.success && result.data) {
        allProducts = result.data;
        displayProducts(allProducts);
    } else {
        document.getElementById('productTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted">No products available<\/td><\/tr>';
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
    document.getElementById('selectedCustomerNameText').innerText = customer.CustomerName;
    document.getElementById('selectedCustomerPhoneText').innerHTML = customer.Phone ? `Phone: ${customer.Phone}` : '';
    document.getElementById('selectedCustomerStatsText').innerHTML = `${customer.TotalPurchases || 0} purchases • ₱${formatNumber(customer.TotalSpent || 0)} spent`;
    document.getElementById('selectedCustomerDisplay').style.display = 'flex';
    document.getElementById('customerName').value = customer.CustomerName;
    document.getElementById('customerPhone').value = customer.Phone || '';
    document.getElementById('customerAddress').value = customer.Address || '';
    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
    showToast(`Customer selected: ${customer.CustomerName}`, 'success');
}

function clearSelectedCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerAddress').value = '';
    showToast('Customer removed', 'info');
}

function useWalkInCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').style.display = 'none';
    document.getElementById('customerName').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerAddress').value = '';
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
// PRODUCT DISPLAY WITH IMEI & SERIAL
// ============================================

function displayProducts(products) {
    const tbody = document.getElementById('productTableBody');
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    let filtered = products;
    
    if (searchTerm) {
        filtered = products.filter(p => 
            (p.ProductName && p.ProductName.toLowerCase().includes(searchTerm)) ||
            (p.ProductCode && p.ProductCode.toLowerCase().includes(searchTerm)) || 
            (p.Brand && p.Brand.toLowerCase().includes(searchTerm)) ||
            (p.IMEINumber && p.IMEINumber.toLowerCase().includes(searchTerm)) ||
            (p.SerialNumber && p.SerialNumber.toLowerCase().includes(searchTerm))
        );
    }
    
    if (filtered.length === 0) { 
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No products found<\/td><\/tr>'; 
        return; 
    }
    
    tbody.innerHTML = filtered.map(product => {
        const isSelected = selectedProducts.some(p => p.id === product.ProductID);
        const imageHtml = product.ProductImagePath 
            ? `<img src="${product.ProductImagePath}" alt="${escapeHtml(product.ProductName)}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">`
            : `<i class="fas fa-box" style="font-size: 18px; color: #94a3b8;"></i>`;
        
        return `
            <tr data-product-id="${product.ProductID}" class="${isSelected ? 'selected' : ''}" style="cursor: pointer;">
                <td><div class="product-thumb">${imageHtml}</div><\/td>
                <td>${product.ProductCode || 'N/A'}<\/td>
                <td><strong>${escapeHtml(product.ProductName)}<\/strong><\/td>
                <td class="product-imei">${product.IMEINumber || '-'}<\/td>
                <td class="product-serial">${product.SerialNumber || '-'}<\/td>
                <td>₱${formatNumber(product.SellingPrice)}<\/td>
                <td>${product.CurrentStock || 0}<\/td>
                <td>${isSelected ? '✓ Selected' : ''}<\/td>
            <\/tr>
        `;
    }).join('');
    
    document.querySelectorAll('#productTableBody tr').forEach(row => {
        row.addEventListener('click', (e) => {
            e.stopPropagation();
            const productId = parseInt(row.getAttribute('data-product-id'));
            toggleProduct(productId);
        });
    });
}

function toggleProduct(productId) {
    const product = allProducts.find(p => parseInt(p.ProductID) === productId);
    if (!product) return;

    const existingIndex = selectedProducts.findIndex(p => parseInt(p.id) === productId);

    if (existingIndex !== -1) {
        selectedProducts.splice(existingIndex, 1);
        showToast(`${product.ProductName} removed from cart`, 'info');
    } else {
        selectedProducts.push({
            id: productId,
            name: product.ProductName,
            code: product.ProductCode,
            price: parseFloat(product.SellingPrice),
            quantity: 1,
            stock: product.CurrentStock || 0,
            image: product.ProductImagePath,
            imei: product.IMEINumber,
            serial: product.SerialNumber
        });
        showToast(`${product.ProductName} added to cart`, 'success');
    }

    refreshSelectedUI();
}

function refreshSelectedUI() {
    displayProducts(allProducts);
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
    
    listContainer.innerHTML = selectedProducts.map(product => {
        const imageHtml = product.image 
            ? `<img src="${product.image}" alt="${escapeHtml(product.name)}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">`
            : `<i class="fas fa-box" style="font-size: 18px; color: #94a3b8;"></i>`;
        
        return `
            <div class="selected-product-item" data-product-id="${product.id}">
                <div class="selected-product-info">
                    <div class="selected-product-thumb">${imageHtml}</div>
                    <div class="selected-product-details">
                        <div class="selected-product-name">${escapeHtml(product.name)}</div>
                        <div class="selected-product-price">₱${formatNumber(product.price)}</div>
                        ${product.imei ? `<div class="selected-product-imei"><i class="fas fa-qrcode"></i> IMEI: ${product.imei}</div>` : ''}
                        ${product.serial ? `<div class="selected-product-serial"><i class="fas fa-barcode"></i> Serial: ${product.serial}</div>` : ''}
                    </div>
                </div>
                <div class="selected-product-qty">
                    <button class="qty-btn" onclick="event.stopPropagation(); updateQuantity(${product.id}, -1)">-</button>
                    <span>${product.quantity}</span>
                    <button class="qty-btn" onclick="event.stopPropagation(); updateQuantity(${product.id}, 1)">+</button>
                </div>
                <div class="selected-product-remove" onclick="event.stopPropagation(); removeSelectedProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </div>
            </div>
        `;
    }).join('');
}

function updateQuantity(productId, change) {
    const index = selectedProducts.findIndex(p => parseInt(p.id) === productId);
    if (index === -1) return;

    const product = selectedProducts[index];
    let newQty = product.quantity + change;

    if (newQty <= 0) {
        selectedProducts.splice(index, 1);
        showToast(`${product.name} removed`, 'info');
    } else if (newQty > product.stock) {
        showToast(`Only ${product.stock} available`, 'warning');
        return;
    } else {
        product.quantity = newQty;
    }

    refreshSelectedUI();
}

function removeSelectedProduct(productId) {
    const index = selectedProducts.findIndex(p => parseInt(p.id) === productId);
    if (index === -1) return;
    const name = selectedProducts[index].name;
    selectedProducts.splice(index, 1);
    showToast(`${name} removed`, 'info');
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
    if (!customerName) { showToast('Please enter customer name', 'warning'); return; }
    if (selectedProducts.length === 0) { showToast('Please select at least one product', 'warning'); return; }
    
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
            total: p.price * p.quantity 
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
        showToast(result.message, 'success');
        resetForm();
        await refreshAllData();
    } else {
        showToast(result.message, 'error');
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
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No installment records found<\/td><\/tr>'; 
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
            <td><strong>${inst.InstallmentNo || 'N/A'}<\/strong><\/td>
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

function showInstallmentDetails(installment, payments) {
    const modalBody = document.getElementById('detailsModalBody');
    const isFullyPaid = parseFloat(installment.RemainingBalance) <= 0.01 || installment.Status === 'completed';
    const statusDisplay = isFullyPaid ? 'PAID' : (installment.Status === 'active' ? 'ACTIVE' : 'OVERDUE');
    const statusClass = isFullyPaid ? 'badge-paid' : (installment.Status === 'active' ? 'badge-active' : 'badge-overdue');
    
    let html = `<div class="row mb-3"><div class="col-md-6">
        <strong>Receipt No:</strong> ${installment.InstallmentNo}<br>
        <strong>Customer:</strong> ${escapeHtml(installment.CustomerName)}<br>
        <strong>Phone:</strong> ${installment.CustomerPhone || '-'}<br>
        <strong>Address:</strong> ${installment.CustomerAddress || '-'}
    <\/div><div class="col-md-6">
        <strong>Product(s):</strong> ${installment.ProductName}<br>
        <strong>Total Amount:</strong> ₱${formatNumber(installment.TotalAmount)}<br>
        <strong>Down Payment:</strong> ₱${formatNumber(installment.DownPayment)}<br>
        <strong>Monthly Payment:</strong> ₱${formatNumber(installment.MonthlyPayment)}<br>
        <strong>Paid Amount:</strong> ₱${formatNumber(installment.PaidAmount)}<br>
        <strong>Remaining Balance:</strong> ₱${formatNumber(installment.RemainingBalance)}<br>
        <strong>Status:</strong> <span class="${statusClass}">${statusDisplay}</span>
    <\/div><\/div><div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Due Date</th><th>Amount</th><th>Status</th><th>Payment Date</th><th>Action</th></tr></thead><tbody>`;
    
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
                <td>${!isPaid && !isFullyPaid ? `<button class="pay-btn" onclick="openPaymentModal(${installment.InstallmentID}, ${payment.PaymentNo}, ${payment.Amount})">Pay Now</button>` : (isPaid ? '✓ Paid' : '-')}<\/td>
            <\/tr>`;
        });
    }
    html += `<\/tbody>\<\/div>`;
    modalBody.innerHTML = html;
    
    if (currentDetailsModal) { currentDetailsModal.dispose(); }
    currentDetailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    currentDetailsModal.show();
}

// ============================================
// PAYMENT MODAL - WORKING VERSION
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
            
            // Show summary box
            const summaryBox = document.getElementById('paymentSummaryBox');
            summaryBox.innerHTML = `
                <div class="payment-summary-row"><strong>Regular Amount:</strong> <span>₱${formatNumber(amount)}</span></div>
                ${penaltyAmount > 0 ? `<div class="payment-summary-row text-danger"><strong>Penalty:</strong> <span>₱${formatNumber(penaltyAmount)}</span></div>` : ''}
                <div class="payment-summary-row payment-summary-total"><strong>Total to Pay:</strong> <strong class="text-success">₱${formatNumber(totalDue)}</strong></div>
            `;
            summaryBox.style.display = 'block';
            
            if (currentPaymentModal) { currentPaymentModal.dispose(); }
            currentPaymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            currentPaymentModal.show();
        }
    }
}

document.getElementById('confirmPaymentBtn').addEventListener('click', async function() {
    if (confirm('Are you sure you want to record this payment?')) {
        await recordPayment();
    }
});

async function recordPayment() {
    const installmentId = document.getElementById('paymentInstallmentId').value;
    const paymentNo = document.getElementById('paymentPaymentNo').value;
    const regularAmountRaw = document.getElementById('paymentRegularAmount').value;
    const regularAmount = parseFloat(regularAmountRaw.replace('₱', '').replace(/,/g, '')) || 0;
    const penaltyAmount = window.currentPenaltyAmount || 0;
    
    if (!installmentId || !paymentNo) { showToast('Invalid payment data', 'error'); return; }
    
    if (penaltyAmount > 0) {
        if (!confirm(`This payment is overdue. A penalty of ₱${formatNumber(penaltyAmount)} will be applied.\n\nDo you want to proceed?`)) { return; }
    }
    
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
        showToast(result.message, 'success');
        if (currentPaymentModal) { currentPaymentModal.hide(); }
        await refreshAllData();
        if (currentDetailsModal && currentDetailsModal._element.classList.contains('show')) {
            await viewInstallmentDetails(parseInt(installmentId));
        }
    } else {
        showToast(result.message || 'Payment failed', 'error');
    }
    
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = 'Confirm Payment';
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

function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container-custom');
    if (!container) { 
        container = document.createElement('div'); 
        container.className = 'toast-container-custom position-fixed bottom-0 end-0 p-3'; 
        container.style.zIndex = '1070'; 
        document.body.appendChild(container); 
    }
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} show`;
    toast.setAttribute('role', 'alert');
    toast.style.minWidth = '250px';
    toast.style.marginBottom = '10px';
    toast.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
    toast.querySelector('.btn-close').addEventListener('click', () => toast.remove());
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event Listeners
document.getElementById('productSearch').addEventListener('input', function() { displayProducts(allProducts); });

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    loadInstallments();
    loadStats();
});
</script>