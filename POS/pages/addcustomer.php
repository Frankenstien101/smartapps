<?php
// pages/customers.php - Customers Management Page with Sales & Installment History
?>
<style>
    .customers-container {
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
    
    /* Customers Table */
    .customers-table-container {
        background: white;
        border-radius: 16px;
        overflow-x: auto;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .customers-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }
    
    .customers-table th {
        background: #f8fafc;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #1a2a3a;
        border-bottom: 2px solid #e2e8f0;
        font-size: 13px;
    }
    
    .customers-table td {
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        color: #4a5568;
        font-size: 14px;
    }
    
    .customers-table tr:hover {
        background: #f8fafc;
    }
    
    .customer-name {
        font-weight: 600;
        color: #1a2a3a;
    }
    
    .customer-phone {
        font-family: monospace;
        font-size: 13px;
    }
    
    .badge-purchases {
        background: #e8f0fe;
        color: #4f9eff;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-total {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-edit {
        background: rgba(79, 158, 255, 0.1);
        color: #4f9eff;
    }
    
    .btn-edit:hover {
        background: #4f9eff;
        color: white;
    }
    
    .btn-delete {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .btn-delete:hover {
        background: #dc3545;
        color: white;
    }
    
    .btn-view {
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
    }
    
    .btn-view:hover {
        background: #17a2b8;
        color: white;
    }
    
    /* History Tables */
    .history-table {
        width: 100%;
        font-size: 12px;
        border-collapse: collapse;
    }
    
    .history-table th {
        background: #f8fafc;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
    }
    
    .history-table td {
        padding: 10px;
        border-bottom: 1px solid #eef2f7;
    }
    
    .history-table tr:hover td {
        background: #f8fafc;
    }
    
    .badge-payment {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }
    
    .badge-cash { background: #dbeafe; color: #2563eb; }
    .badge-card { background: #dcfce7; color: #10b981; }
    .badge-gcash { background: #fef3c7; color: #d97706; }
    
    .badge-installment-active { background: #dbeafe; color: #2563eb; }
    .badge-installment-completed { background: #dcfce7; color: #10b981; }
    .badge-installment-overdue { background: #fee2e2; color: #dc3545; }
    .badge-installment-returned { background: #6c757d; color: white; }
    
    .tab-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 10px;
    }
    
    .tab-btn {
        background: none;
        border: none;
        padding: 8px 20px;
        cursor: pointer;
        font-weight: 500;
        color: #6c7a91;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .tab-btn.active {
        background: #4f9eff;
        color: white;
    }
    
    .tab-btn:hover:not(.active) {
        background: #eef2ff;
        color: #4f9eff;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
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
    
    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .section-title {
        font-size: 14px;
        font-weight: 600;
        margin: 15px 0 10px 0;
        color: #1a2a3a;
    }
    
    .summary-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .summary-item {
        text-align: center;
        flex: 1;
    }
    
    .summary-label {
        font-size: 11px;
        color: #6c7a91;
        margin-bottom: 5px;
    }
    
    .summary-value {
        font-size: 18px;
        font-weight: 800;
        color: #1a2a3a;
    }
    
    .summary-value.sales { color: #4f9eff; }
    .summary-value.installment { color: #28a745; }
    .summary-value.total { color: #764ba2; }
    
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
    
    @media (max-width: 768px) {
        .customers-table th,
        .customers-table td {
            padding: 10px;
            font-size: 12px;
        }
        
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .history-table {
            font-size: 10px;
        }
        
        .history-table th,
        .history-table td {
            padding: 6px;
        }
        
        .tab-btn {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .summary-box {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="customers-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-users"></i> Customers</h4>
            <p class="text-muted mb-0">Manage your customer database</p>
        </div>
    </div>
    
    <button class="btn btn-primary btn-sm mb-3" style="width: 140px;" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="fas fa-user-plus"></i> Add Customer
    </button>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-value" id="totalCustomers">0</div>
            <div class="stat-label">Total Customers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalSpent">₱0</div>
            <div class="stat-label">Total Spent (All)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-value" id="totalPurchases">0</div>
            <div class="stat-label">Total Purchases</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-calendar"></i></div>
            <div class="stat-value" id="newThisMonth">0</div>
            <div class="stat-label">New This Month</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, phone, or email...">
        </div>
    </div>
    
    <!-- Customers Table -->
    <div class="customers-table-container">
        <table class="customers-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Purchases</th>
                    <th>Total Spent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customersTableBody">
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading customers...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD CUSTOMER MODAL -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Customer Name *</label>
                    <input type="text" id="addCustomerName" class="form-control" placeholder="Enter full name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" id="addCustomerPhone" class="form-control" placeholder="e.g., 09123456789">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="addCustomerEmail" class="form-control" placeholder="customer@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea id="addCustomerAddress" class="form-control" rows="2" placeholder="Enter address"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmAddCustomerBtn">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT CUSTOMER MODAL -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editCustomerId">
                <div class="mb-3">
                    <label class="form-label">Customer Name *</label>
                    <input type="text" id="editCustomerName" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" id="editCustomerPhone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="editCustomerEmail" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea id="editCustomerAddress" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmEditCustomerBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- VIEW CUSTOMER MODAL with Sales & Installment History -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> Customer Details & Transaction History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Customer Info -->
                <div class="text-center mb-3">
                    <i class="fas fa-user-circle" style="font-size: 64px; color: #4f9eff;"></i>
                </div>
                <table class="table table-borderless">
                    <tr><td style="width: 35%;"><strong>Customer ID:</strong></td><td id="viewCustomerId">-</td></tr>
                    <tr><td><strong>Name:</strong></td><td id="viewCustomerName">-</td></tr>
                    <tr><td><strong>Phone:</strong></td><td id="viewCustomerPhone">-</td></tr>
                    <tr><td><strong>Email:</strong></td><td id="viewCustomerEmail">-</td></tr>
                    <tr><td><strong>Address:</strong></td><td id="viewCustomerAddress">-</td></tr>
                    <tr><td><strong>Member Since:</strong></td><td id="viewCreatedAt">-</td></tr>
                </table>
                
                <!-- Financial Summary -->
                <div class="summary-box">
                    <div class="summary-item">
                        <div class="summary-label">Sales Purchases</div>
                        <div class="summary-value sales" id="salesCount">0</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Sales Spent</div>
                        <div class="summary-value sales" id="salesSpent">₱0</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Installment Loans</div>
                        <div class="summary-value installment" id="installmentCount">0</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Installment Paid</div>
                        <div class="summary-value installment" id="installmentPaid">₱0</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Purchases</div>
                        <div class="summary-value total" id="totalTransactionCount">0</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Spent</div>
                        <div class="summary-value total" id="totalCustomerSpent">₱0</div>
                    </div>
                </div>
                
                <hr>
                
                <!-- Tabs -->
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('sales')"><i class="fas fa-shopping-cart"></i> Sales Transactions</button>
                    <button class="tab-btn" onclick="switchTab('installment')"><i class="fas fa-hand-holding-usd"></i> Installment Loans</button>
                </div>
                
                <!-- Sales Transactions Tab -->
                <div id="salesTab" class="tab-content active">
                    <div class="section-title"><i class="fas fa-history"></i> Sales Transaction History</div>
                    <div class="table-responsive">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Date</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="salesHistoryBody">
                                <tr><td colspan="5" class="text-center py-3"><div class="loading-spinner" style="width: 20px; height: 20px;"></div> Loading transactions...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Installment Transactions Tab -->
                <div id="installmentTab" class="tab-content">
                    <div class="section-title"><i class="fas fa-hand-holding-usd"></i> Installment Loan History</div>
                    <div class="table-responsive">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Loan No</th>
                                    <th>Date</th>
                                    <th>Product(s)</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="installmentHistoryBody">
                                <tr><td colspan="7" class="text-center py-3"><div class="loading-spinner" style="width: 20px; height: 20px;"></div> Loading installment records...</td></tr>
                            </tbody>
                        </table>
                    </div>
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
const API_URL = '/POS/datafetcher/stockindata.php';
const CUSTOMER_API_URL = '/POS/datafetcher/addcustomerdata.php';
const INSTALLMENT_API_URL = '/POS/datafetcher/installmentdata.php';

let allCustomers = [];
let currentSearchTerm = '';
let currentCustomerId = null;

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

async function loadCustomers() {
    const result = await apiCall(CUSTOMER_API_URL, 'getCustomers');
    if (result.success && result.data) {
        allCustomers = result.data;
        await loadAllCustomerFinancials();
        filterAndDisplayCustomers();
    } else {
        document.getElementById('customersTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No customers found</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                        <i class="fas fa-user-plus"></i> Add First Customer
                    </button>
                </td>
            </tr>
        `;
    }
}

async function loadAllCustomerFinancials() {
    // Load sales data for all customers
    const salesResult = await apiCall(API_URL, 'getSales');
    // Load installment data for all customers
    const installmentResult = await apiCall(INSTALLMENT_API_URL, 'getInstallments');
    
    for (let customer of allCustomers) {
        let salesTotal = 0;
        let salesCount = 0;
        let installmentPaid = 0;
        let installmentCount = 0;
        
        // Calculate sales totals
        if (salesResult.success && salesResult.data) {
            const customerSales = salesResult.data.filter(sale => 
                (sale.CustomerPhone && sale.CustomerPhone === customer.Phone) ||
                (sale.CustomerName && sale.CustomerName === customer.CustomerName)
            );
            salesTotal = customerSales.reduce((sum, sale) => sum + (parseFloat(sale.TotalAmount) || 0), 0);
            salesCount = customerSales.length;
        }
        
        // Calculate installment paid amounts
        if (installmentResult.success && installmentResult.data) {
            const customerInstallments = installmentResult.data.filter(inst => 
                (inst.CustomerPhone && inst.CustomerPhone === customer.Phone) ||
                (inst.CustomerName && inst.CustomerName === customer.CustomerName)
            );
            installmentPaid = customerInstallments.reduce((sum, inst) => sum + (parseFloat(inst.PaidAmount) || 0), 0);
            installmentCount = customerInstallments.length;
        }
        
        // Store calculated values
        customer.SalesTotal = salesTotal;
        customer.SalesCount = salesCount;
        customer.InstallmentPaid = installmentPaid;
        customer.InstallmentCount = installmentCount;
        customer.TotalSpentAmount = salesTotal + installmentPaid;
        customer.TotalTransactionsCount = salesCount + installmentCount;
    }
    
    calculateStats();
}

async function loadCustomerSales(customerName, customerPhone) {
    const result = await apiCall(API_URL, 'getSales');
    if (result.success && result.data) {
        const salesHistory = result.data.filter(sale => 
            (sale.CustomerPhone && sale.CustomerPhone === customerPhone) ||
            (sale.CustomerName && sale.CustomerName === customerName)
        );
        const salesTotal = salesHistory.reduce((sum, sale) => sum + (parseFloat(sale.TotalAmount) || 0), 0);
        const salesCount = salesHistory.length;
        
        displaySalesHistory(salesHistory);
        return { salesTotal, salesCount };
    } else {
        document.getElementById('salesHistoryBody').innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No sales transactions found</td></tr>';
        return { salesTotal: 0, salesCount: 0 };
    }
}

async function loadCustomerInstallments(customerName, customerPhone) {
    try {
        const result = await apiCall(INSTALLMENT_API_URL, 'getInstallments');
        if (result.success && result.data) {
            const installmentHistory = result.data.filter(inst => 
                (inst.CustomerPhone && inst.CustomerPhone === customerPhone) ||
                (inst.CustomerName && inst.CustomerName === customerName)
            );
            const installmentPaid = installmentHistory.reduce((sum, inst) => sum + (parseFloat(inst.PaidAmount) || 0), 0);
            const installmentCount = installmentHistory.length;
            
            displayInstallmentHistory(installmentHistory);
            return { installmentPaid, installmentCount };
        } else {
            document.getElementById('installmentHistoryBody').innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">No installment records found</td></tr>';
            return { installmentPaid: 0, installmentCount: 0 };
        }
    } catch (error) {
        console.error('Error loading installments:', error);
        document.getElementById('installmentHistoryBody').innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">Unable to load installment data</td></tr>';
        return { installmentPaid: 0, installmentCount: 0 };
    }
}

async function addCustomer() {
    const customerName = document.getElementById('addCustomerName').value.trim();
    const phone = document.getElementById('addCustomerPhone').value.trim();
    const email = document.getElementById('addCustomerEmail').value.trim();
    const address = document.getElementById('addCustomerAddress').value.trim();
    
    if (!customerName) {
        showToast('Please enter customer name', 'warning');
        return;
    }
    
    if (email && !isValidEmail(email)) {
        showToast('Please enter a valid email address', 'warning');
        return;
    }
    
    const data = {
        customer_name: customerName,
        phone: phone,
        email: email,
        address: address
    };
    
    const result = await apiCall(CUSTOMER_API_URL, 'addCustomer', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
        clearAddForm();
        await loadCustomers();
    } else {
        showToast(result.message || 'Failed to add customer', 'error');
    }
}

async function updateCustomer() {
    const customerId = document.getElementById('editCustomerId').value;
    const customerName = document.getElementById('editCustomerName').value.trim();
    const phone = document.getElementById('editCustomerPhone').value.trim();
    const email = document.getElementById('editCustomerEmail').value.trim();
    const address = document.getElementById('editCustomerAddress').value.trim();
    
    if (!customerId || !customerName) {
        showToast('Please enter customer name', 'warning');
        return;
    }
    
    const data = {
        customer_id: parseInt(customerId),
        customer_name: customerName,
        phone: phone,
        email: email,
        address: address
    };
    
    const result = await apiCall(CUSTOMER_API_URL, 'updateCustomer', 'PUT', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('editCustomerModal')).hide();
        await loadCustomers();
    } else {
        showToast(result.message || 'Failed to update customer', 'error');
    }
}

// ============================================
// CALCULATIONS
// ============================================

function calculateStats() {
    const totalCustomers = allCustomers.length;
    
    let totalSpentAll = 0;
    let totalPurchasesAll = 0;
    let newThisMonth = 0;
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    
    for (let i = 0; i < allCustomers.length; i++) {
        const spent = allCustomers[i].TotalSpentAmount || 0;
        const purchases = allCustomers[i].TotalTransactionsCount || 0;
        totalSpentAll += spent;
        totalPurchasesAll += purchases;
        
        if (allCustomers[i].CreatedAt) {
            const createdDate = new Date(allCustomers[i].CreatedAt);
            if (createdDate.getMonth() === currentMonth && createdDate.getFullYear() === currentYear) {
                newThisMonth++;
            }
        }
    }
    
    document.getElementById('totalCustomers').innerText = totalCustomers;
    document.getElementById('totalSpent').innerHTML = '₱' + formatNumber(totalSpentAll);
    document.getElementById('totalPurchases').innerText = totalPurchasesAll;
    document.getElementById('newThisMonth').innerText = newThisMonth;
}

// ============================================
// FILTER AND DISPLAY
// ============================================

function filterAndDisplayCustomers() {
    let filtered = [...allCustomers];
    
    if (currentSearchTerm) {
        filtered = filtered.filter(c => 
            (c.CustomerName && c.CustomerName.toLowerCase().includes(currentSearchTerm)) ||
            (c.Phone && c.Phone.includes(currentSearchTerm)) ||
            (c.Email && c.Email.toLowerCase().includes(currentSearchTerm))
        );
    }
    
    displayCustomers(filtered);
}

function displayCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    
    if (customers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>No customers found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td>${customer.CustomerID}</td>
            <td class="customer-name">${escapeHtml(customer.CustomerName)}</td>
            <td class="customer-phone">${customer.Phone || '-'}</td>
            <td>${customer.Email || '-'}</td>
            <td><small>${escapeHtml(customer.Address) || '-'}</small></td>
            <td><span class="badge-purchases">${customer.TotalTransactionsCount || 0}</span></td>
            <td><strong class="badge-total">₱${formatNumber(customer.TotalSpentAmount || 0)}</strong></td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon btn-view" onclick="viewCustomer(${customer.CustomerID})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon btn-edit" onclick="editCustomer(${customer.CustomerID})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteCustomer(${customer.CustomerID}, '${escapeHtml(customer.CustomerName)}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function displaySalesHistory(salesHistory) {
    const tbody = document.getElementById('salesHistoryBody');
    
    if (!salesHistory || salesHistory.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No sales transactions found</td></tr>';
        return;
    }
    
    tbody.innerHTML = salesHistory.map(sale => {
        const paymentClass = sale.PaymentMethod === 'cash' ? 'badge-cash' : 
                            (sale.PaymentMethod === 'card' ? 'badge-card' : 'badge-gcash');
        return `
            <tr>
                <td><strong>${escapeHtml(sale.ReceiptNo)}</strong></td>
                <td>${sale.SaleDate || '-'}</td>
                <td><span class="badge-payment ${paymentClass}">${(sale.PaymentMethod || 'cash').toUpperCase()}</span></td>
                <td><strong>₱${formatNumber(sale.TotalAmount)}</strong></td>
                <td><span class="badge-payment badge-cash">${sale.Status || 'completed'}</span></td>
            </tr>
        `;
    }).join('');
}

function displayInstallmentHistory(installmentHistory) {
    const tbody = document.getElementById('installmentHistoryBody');
    
    if (!installmentHistory || installmentHistory.length === 0) {
        tbody.innerHTML = '<table><td colspan="7" class="text-center py-3 text-muted">No installment records found</tr>';
        return;
    }
    
    tbody.innerHTML = installmentHistory.map(inst => {
        let statusClass = '';
        let statusText = '';
        
        if (inst.Status === 'returned') {
            statusClass = 'badge-installment-returned';
            statusText = 'RETURNED';
        } else if (inst.Status === 'completed' || inst.RemainingBalance <= 0.01) {
            statusClass = 'badge-installment-completed';
            statusText = 'PAID';
        } else if (inst.Status === 'overdue') {
            statusClass = 'badge-installment-overdue';
            statusText = 'OVERDUE';
        } else {
            statusClass = 'badge-installment-active';
            statusText = 'ACTIVE';
        }
        
        return `
            <tr>
                <td><strong>${escapeHtml(inst.InstallmentNo)}</strong></td>
                <td>${inst.StartDate || '-'}</td>
                <td>${escapeHtml(inst.ProductName) || 'Multiple Items'}</td>
                <td><strong>₱${formatNumber(inst.TotalAmount)}</strong></td>
                <td>₱${formatNumber(inst.PaidAmount)}</td>
                <td>₱${formatNumber(inst.RemainingBalance)}</td>
                <td><span class="badge-payment ${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }).join('');
}

// ============================================
// TAB FUNCTIONS
// ============================================

function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update tab contents
    document.getElementById('salesTab').classList.remove('active');
    document.getElementById('installmentTab').classList.remove('active');
    
    if (tab === 'sales') {
        document.getElementById('salesTab').classList.add('active');
    } else {
        document.getElementById('installmentTab').classList.add('active');
    }
}

// ============================================
// MODAL FUNCTIONS
// ============================================

async function viewCustomer(customerId) {
    const customer = allCustomers.find(c => c.CustomerID == customerId);
    if (!customer) return;
    
    currentCustomerId = customerId;
    
    document.getElementById('viewCustomerId').innerText = customer.CustomerID;
    document.getElementById('viewCustomerName').innerText = customer.CustomerName;
    document.getElementById('viewCustomerPhone').innerText = customer.Phone || 'Not provided';
    document.getElementById('viewCustomerEmail').innerText = customer.Email || 'Not provided';
    document.getElementById('viewCustomerAddress').innerText = customer.Address || 'Not provided';
    document.getElementById('viewCreatedAt').innerText = customer.CreatedAt ? new Date(customer.CreatedAt).toLocaleDateString() : 'N/A';
    
    // Load data for summary
    document.getElementById('salesHistoryBody').innerHTML = '<tr><td colspan="5" class="text-center py-3"><div class="loading-spinner" style="width: 20px; height: 20px;"></div> Loading transactions...</td></tr>';
    document.getElementById('installmentHistoryBody').innerHTML = '<tr><td colspan="7" class="text-center py-3"><div class="loading-spinner" style="width: 20px; height: 20px;"></div> Loading installment records...</td></tr>';
    
    const salesData = await loadCustomerSales(customer.CustomerName, customer.Phone);
    const installmentData = await loadCustomerInstallments(customer.CustomerName, customer.Phone);
    
    // Update summary box
    document.getElementById('salesCount').innerText = salesData.salesCount;
    document.getElementById('salesSpent').innerHTML = '₱' + formatNumber(salesData.salesTotal);
    document.getElementById('installmentCount').innerText = installmentData.installmentCount;
    document.getElementById('installmentPaid').innerHTML = '₱' + formatNumber(installmentData.installmentPaid);
    document.getElementById('totalTransactionCount').innerText = salesData.salesCount + installmentData.installmentCount;
    document.getElementById('totalCustomerSpent').innerHTML = '₱' + formatNumber(salesData.salesTotal + installmentData.installmentPaid);
    
    // Reset to sales tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector('.tab-btn').classList.add('active');
    document.getElementById('salesTab').classList.add('active');
    document.getElementById('installmentTab').classList.remove('active');
    
    const modal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
    modal.show();
}

function editCustomer(customerId) {
    const customer = allCustomers.find(c => c.CustomerID == customerId);
    if (!customer) return;
    
    document.getElementById('editCustomerId').value = customer.CustomerID;
    document.getElementById('editCustomerName').value = customer.CustomerName;
    document.getElementById('editCustomerPhone').value = customer.Phone || '';
    document.getElementById('editCustomerEmail').value = customer.Email || '';
    document.getElementById('editCustomerAddress').value = customer.Address || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
    modal.show();
}

async function deleteCustomer(customerId, customerName) {
    if (confirm(`Are you sure you want to delete "${customerName}"? This action cannot be undone.`)) {
        const result = await apiCall(CUSTOMER_API_URL, 'deleteCustomer', 'DELETE', { customer_id: customerId });
        if (result.success) {
            showToast(result.message, 'success');
            await loadCustomers();
        } else {
            showToast(result.message || 'Failed to delete customer', 'error');
        }
    }
}

function clearAddForm() {
    document.getElementById('addCustomerName').value = '';
    document.getElementById('addCustomerPhone').value = '';
    document.getElementById('addCustomerEmail').value = '';
    document.getElementById('addCustomerAddress').value = '';
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatNumber(value) {
    if (value === null || value === undefined || isNaN(value)) return '0.00';
    return parseFloat(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
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
    filterAndDisplayCustomers();
});

document.getElementById('confirmAddCustomerBtn').addEventListener('click', addCustomer);
document.getElementById('confirmEditCustomerBtn').addEventListener('click', updateCustomer);

// Phone number formatting
document.getElementById('addCustomerPhone').addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);
    this.value = value;
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadCustomers();
});
</script>