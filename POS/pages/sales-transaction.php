
<style>
    .transactions-container {
        padding: 0;
        width: 100%;
        overflow-x: auto;
    }
    
    /* Filter Section */
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
    
    /* Transaction Table */
    .transactions-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .card-header {
        background: white;
        border-bottom: 1px solid #eef2f7;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .card-header h5 {
        margin: 0;
        font-weight: 600;
    }
    
    .search-box {
        position: relative;
        width: 250px;
    }
    
    .search-box input {
        width: 100%;
        padding: 8px 12px 8px 35px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
    }
    
    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table {
        margin-bottom: 0;
        min-width: 600px;
    }
    
    .table th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 12px;
        padding: 12px;
        border-bottom: 1px solid #eef2f7;
    }
    
    .table td {
        font-size: 13px;
        padding: 12px;
        vertical-align: middle;
    }
    
    .badge-payment {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-cash { background: #dbeafe; color: #2563eb; }
    .badge-card { background: #dcfce7; color: #10b981; }
    .badge-gcash { background: #fef3c7; color: #d97706; }
    
    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-completed { background: #dcfce7; color: #10b981; }
    .badge-cancelled { background: #fee2e2; color: #dc3545; }
    
    .view-btn {
        background: transparent;
        border: none;
        color: #4f9eff;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .view-btn:hover {
        background: #eef2ff;
    }
    
    .pagination {
        padding: 15px 20px;
        border-top: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .pagination .page-item .page-link {
        border-radius: 8px;
        margin: 0 2px;
        color: #4a5568;
        font-size: 12px;
    }
    
    .pagination .page-item.active .page-link {
        background: #4f9eff;
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
    
    /* Modal Styles - Fixed for better display */
    .modal-content {
        border-radius: 20px;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .modal-dialog {
        max-width: 500px;
        margin: 1.75rem auto;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #1f2937, #2d3a4a);
        color: white;
        border-radius: 20px 20px 0 0;
    }
    
    .receipt-content {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        max-width: 100%;
        word-wrap: break-word;
    }
    
    .receipt-line {
        text-align: center;
        margin: 8px 0;
    }
    
    .receipt-line-dashed {
        text-align: center;
        letter-spacing: 2px;
        color: #666;
    }
    
    .receipt-header {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .receipt-header h6 {
        margin: 0;
        font-weight: bold;
    }
    
    .receipt-header small {
        font-size: 10px;
        color: #666;
    }
    
    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin: 5px 0;
    }
    
    .receipt-items {
        margin: 10px 0;
    }
    
    .receipt-item {
        display: flex;
        justify-content: space-between;
        margin: 3px 0;
    }
    
    .receipt-item-details {
        font-size: 10px;
        color: #666;
        margin-left: 10px;
    }
    
    .receipt-total {
        border-top: 1px dashed #ccc;
        margin-top: 10px;
        padding-top: 10px;
    }
    
    .receipt-footer {
        text-align: center;
        margin-top: 15px;
        font-size: 10px;
    }
    
    /* Date filter */
    .date-filter {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .date-filter input {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .card-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-box {
            width: 100%;
        }
        
        .date-filter {
            width: 100%;
        }
        
        .date-filter input {
            flex: 1;
        }
        
        .table td, .table th {
            padding: 8px;
        }
        
        .modal-dialog {
            margin: 1rem;
            max-width: calc(100% - 2rem);
        }
    }
    
    /* Print styles */
    @media print {
        .filter-section, .card-header, .pagination, .modal-footer {
            display: none;
        }
        
        .transactions-card {
            box-shadow: none;
        }
        
        body {
            background: white;
            
        }
        
        .modal {
            position: static;
            display: block;
        }
        
        .modal-dialog {
            max-width: 100%;
            margin: 0;
        }
        
        .modal-content {
            box-shadow: none;
        }
    }
</style>

<div class="transactions-container" style="overflow-y: auto;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-receipt"></i> Sales Transactions</h4>
            <p class="text-muted mb-0">View and manage all sales transactions</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary btn-sm" onclick="printReport()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
    
    <!-- Stats Row -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalSales">₱0</div>
            <div class="stat-label">Total Sales</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-exchange-alt"></i></div>
            <div class="stat-value" id="totalTransactions">0</div>
            <div class="stat-label">Total Transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value" id="todaySales">₱0</div>
            <div class="stat-label">Today's Sales</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="row align-items-center g-2">
            <div class="col-md-4">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by receipt or customer..." onkeyup="filterTransactions()">
                </div>
            </div>
            <div class="col-md-4">
                <div class="date-filter">
                    <input type="date" id="startDate" class="form-control form-control-sm" placeholder="Start Date">
                    <span>to</span>
                    <input type="date" id="endDate" class="form-control form-control-sm" placeholder="End Date">
                </div>
            </div>
            <div class="col-md-4">
                <select id="paymentFilter" class="form-select form-select-sm" onchange="filterTransactions()">
                    <option value="all">All Payment Methods</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="gcash">GCash</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="transactions-card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Transaction History</h5>
            <button class="btn btn-sm btn-primary" onclick="refreshTransactions()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Receipt No.</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Payment</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="loading-spinner"></div> Loading...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="pagination">
            <!-- Pagination will be inserted here -->
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> Transaction Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" id="transactionDetails">
                <div class="text-center py-4">
                    <div class="loading-spinner"></div> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-sm" onclick="printTransaction()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/stockindata.php';

// Global variables
let allTransactions = [];
let currentPage = 1;
let itemsPerPage = 15;
let currentReceiptHTML = '';

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
        return { success: false, message: error.message };
    }
}

async function loadTransactions() {
    const result = await apiCall('getSales');
    if (result.success && result.data) {
        allTransactions = result.data;
        calculateStats();
        filterAndDisplayTransactions();
    } else {
        document.getElementById('transactionsTableBody').innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    No transactions found
                <\/td>
          
        `;
    }
}

async function loadTransactionDetails(saleId) {
    const result = await apiCall(`getSaleById&id=${saleId}`);
    if (result.success && result.data) {
        generateReceiptHTML(result.data);
    } else {
        document.getElementById('transactionDetails').innerHTML = `
            <div class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>
                Failed to load transaction details
            </div>
        `;
    }
}

async function loadTodaySales() {
    const result = await apiCall('getTodaySales');
    if (result.success && result.data) {
        const todayAmount = parseFloat(result.data.TodaySales || 0);
        document.getElementById('todaySales').innerHTML = '₱' + formatNumber(todayAmount);
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatNumber(value) {
    if (value === null || value === undefined) return '0.00';
    return parseFloat(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function calculateStats() {
    let totalSales = 0;
    allTransactions.forEach(t => {
        let amount = 0;
        if (typeof t.TotalAmount === 'number') {
            amount = t.TotalAmount;
        } else if (typeof t.TotalAmount === 'string') {
            amount = parseFloat(t.TotalAmount) || 0;
        }
        totalSales += amount;
    });
    
    document.getElementById('totalSales').innerHTML = '₱' + formatNumber(totalSales);
    document.getElementById('totalTransactions').innerHTML = allTransactions.length;
}

// ============================================
// FILTER AND DISPLAY
// ============================================

function filterTransactions() {
    currentPage = 1;
    filterAndDisplayTransactions();
}

function filterAndDisplayTransactions() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const paymentFilter = document.getElementById('paymentFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    let filtered = [...allTransactions];
    
    // Filter by search term
    if (searchTerm) {
        filtered = filtered.filter(t => 
            (t.ReceiptNo && t.ReceiptNo.toLowerCase().includes(searchTerm)) ||
            (t.CustomerName && t.CustomerName.toLowerCase().includes(searchTerm))
        );
    }
    
    // Filter by payment method
    if (paymentFilter !== 'all') {
        filtered = filtered.filter(t => t.PaymentMethod === paymentFilter);
    }
    
    // Filter by date range
    if (startDate) {
        filtered = filtered.filter(t => {
            const transDate = t.SaleDate ? t.SaleDate.split(' ')[0] : '';
            return transDate >= startDate;
        });
    }
    if (endDate) {
        filtered = filtered.filter(t => {
            const transDate = t.SaleDate ? t.SaleDate.split(' ')[0] : '';
            return transDate <= endDate;
        });
    }
    
    // Update stats for filtered results
    let filteredTotal = 0;
    filtered.forEach(t => {
        let amount = 0;
        if (typeof t.TotalAmount === 'number') {
            amount = t.TotalAmount;
        } else if (typeof t.TotalAmount === 'string') {
            amount = parseFloat(t.TotalAmount) || 0;
        }
        filteredTotal += amount;
    });
    document.getElementById('totalSales').innerHTML = '₱' + formatNumber(filteredTotal);
    document.getElementById('totalTransactions').innerHTML = filtered.length;
    
    // Pagination
    const totalPages = Math.ceil(filtered.length / itemsPerPage);
    const start = (currentPage - 1) * itemsPerPage;
    const paginatedItems = filtered.slice(start, start + itemsPerPage);
    
    displayTransactions(paginatedItems);
    displayPagination(totalPages);
}

function displayTransactions(transactions) {
    const tbody = document.getElementById('transactionsTableBody');
    
    if (!transactions || transactions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    No transactions found
                <\/td>
            
        `;
        return;
    }
    
    tbody.innerHTML = transactions.map(transaction => {
        const paymentClass = transaction.PaymentMethod === 'cash' ? 'badge-cash' : 
                            (transaction.PaymentMethod === 'card' ? 'badge-card' : 'badge-gcash');
        const statusClass = transaction.Status === 'completed' ? 'badge-completed' : 'badge-cancelled';
        const amount = parseFloat(transaction.TotalAmount || 0);
        
        return `
            <tr>
                <td><strong>${escapeHtml(transaction.ReceiptNo || 'N/A')}</strong></td>
                <td><small>${transaction.SaleDate || ''}</small></td>
                <td>${escapeHtml(transaction.CustomerName || 'Walk-in Customer')}</td>
                <td><span class="badge-payment ${paymentClass}">${(transaction.PaymentMethod || 'cash').toUpperCase()}</span></td>
                <td class="fw-bold">₱${formatNumber(amount)}</td>
                <td><span class="badge-status ${statusClass}">${transaction.Status || 'completed'}</span></td>
                <td>
                    <button class="view-btn" onclick="viewTransaction(${transaction.SaleID})">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function displayPagination(totalPages) {
    const container = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let paginationHTML = `
        <div class="d-flex justify-content-between align-items-center w-100">
            <div>
                <small class="text-muted">Page ${currentPage} of ${totalPages}</small>
            </div>
            <div>
                <ul class="pagination mb-0">
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>
                    </li>
    `;
    
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>
        `;
    }
    
    paginationHTML += `
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>
                    </li>
                </ul>
            </div>
        </div>
    `;
    
    container.innerHTML = paginationHTML;
}

function changePage(page) {
    currentPage = page;
    filterAndDisplayTransactions();
}

function generateReceiptHTML(data) {
    const sale = data.sale;
    const items = data.items || [];
    
    if (!sale) {
        document.getElementById('transactionDetails').innerHTML = `
            <div class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>
                Transaction not found
            </div>
        `;
        return;
    }
    
    const subtotal = items.reduce((sum, i) => sum + (parseFloat(i.Total) || 0), 0);
    const tax = subtotal * 0.12;
    const total = parseFloat(sale.TotalAmount) || subtotal;
    const saleDate = sale.SaleDate ? sale.SaleDate.split(' ')[0] : '';
    const saleTime = sale.SaleDate ? sale.SaleDate.split(' ')[1] : '';
    
    let receiptHTML = `
        <div class="receipt-content" style="max-width: 350px; margin: 0 auto;">
            <div class="receipt-header">
                <h6>POS</h6>
                <small>-</small><br>
                <small>Tel: 0912-345-6789</small>
            </div>
            <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-row">
                <span>Receipt No:</span>
                <span><strong>${escapeHtml(sale.ReceiptNo)}</strong></span>
            </div>
            <div class="receipt-row">
                <span>Date:</span>
                <span>${saleDate} ${saleTime}</span>
            </div>
            <div class="receipt-row">
                <span>Customer:</span>
                <span>${escapeHtml(sale.CustomerName || 'Walk-in Customer')}</span>
            </div>
            <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="fw-bold mb-1">ITEMS:</div>
    `;
    
    items.forEach(item => {
        const qty = parseInt(item.Quantity) || 0;
        const price = parseFloat(item.Price) || 0;
        const totalPrice = parseFloat(item.Total) || 0;
        
        receiptHTML += `
            <div class="receipt-item">
                <span>${escapeHtml(item.ProductName)}</span>
                <span>₱${formatNumber(totalPrice)}</span>
            </div>
            <div class="receipt-item-details">
                ${qty} x ₱${formatNumber(price)}
                <span style="float: right;">Code: ${escapeHtml(item.ProductCode || 'N/A')}</span>
            </div>
        `;
    });
    
    receiptHTML += `
            <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-row">
                <span>Subtotal:</span>
                <span>₱${formatNumber(subtotal)}</span>
            </div>
            <div class="receipt-row">
                <span>Tax (12%):</span>
                <span>₱${formatNumber(tax)}</span>
            </div>
            <div class="receipt-row receipt-total">
                <strong>TOTAL:</strong>
                <strong>₱${formatNumber(total)}</strong>
            </div>
            <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-row">
                <span>Payment Method:</span>
                <span>${(sale.PaymentMethod || 'cash').toUpperCase()}</span>
            </div>
    `;
    
    if (sale.PaymentMethod === 'cash') {
        const received = parseFloat(sale.AmountReceived) || total;
        const change = parseFloat(sale.ChangeAmount) || (received - total);
        receiptHTML += `
            <div class="receipt-row">
                <span>Amount Received:</span>
                <span>₱${formatNumber(received)}</span>
            </div>
            <div class="receipt-row">
                <span>Change:</span>
                <span>₱${formatNumber(change)}</span>
            </div>
        `;
    }
    
    receiptHTML += `
            <div class="receipt-line-dashed">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-footer">
                Cashier: ${escapeHtml(sale.CreatedBy || 'Admin')}<br>
                Thank you for your purchase!<br>
                Please come again
            </div>
        </div>
    `;
    
    currentReceiptHTML = receiptHTML;
    document.getElementById('transactionDetails').innerHTML = receiptHTML;
}

async function viewTransaction(saleId) {
    const modalEl = document.getElementById('transactionModal');
    const modal = new bootstrap.Modal(modalEl);
    
    document.getElementById('transactionDetails').innerHTML = `
        <div class="text-center py-4">
            <div class="loading-spinner"></div> Loading...
        </div>
    `;
    
    modal.show();
    await loadTransactionDetails(saleId);
}

function printTransaction() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Receipt</title>
            <style>
                body {
                    font-family: 'Courier New', monospace;
                    padding: 20px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background: white;
                }
                .receipt-content {
                    max-width: 350px;
                    width: 100%;
                    margin: 0 auto;
                    font-size: 12px;
                }
                .receipt-header { text-align: center; margin-bottom: 15px; }
                .receipt-header h6 { margin: 0; font-weight: bold; }
                .receipt-header small { font-size: 10px; color: #666; }
                .receipt-line-dashed { text-align: center; letter-spacing: 2px; color: #666; margin: 5px 0; }
                .receipt-row { display: flex; justify-content: space-between; margin: 5px 0; }
                .receipt-item { display: flex; justify-content: space-between; margin: 3px 0; }
                .receipt-item-details { font-size: 10px; color: #666; margin-left: 10px; margin-bottom: 5px; }
                .receipt-total { border-top: 1px dashed #ccc; margin-top: 10px; padding-top: 10px; }
                .receipt-footer { text-align: center; margin-top: 15px; font-size: 10px; }
                .fw-bold { font-weight: bold; }
            </style>
        </head>
        <body>
            ${currentReceiptHTML}
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

async function refreshTransactions() {
    const refreshBtn = document.querySelector('.card-header .btn-primary');
    const originalHtml = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    await loadTransactions();
    await loadTodaySales();
    
    refreshBtn.innerHTML = originalHtml;
    refreshBtn.disabled = false;
    showToast('Transactions refreshed', 'success');
}

function printReport() {
    window.print();
}

function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-custom-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-custom-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} show`;
    toast.setAttribute('role', 'alert');
    toast.style.minWidth = '250px';
    toast.style.marginBottom = '10px';
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
    
    toast.querySelector('.btn-close').addEventListener('click', () => toast.remove());
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    loadTransactions();
    loadTodaySales();
    setDefaultDates();
    
    document.getElementById('startDate').addEventListener('change', filterTransactions);
    document.getElementById('endDate').addEventListener('change', filterTransactions);
});

function setDefaultDates() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${year}-${month}-${day}`;
    
    // Set start date to today
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (startDateInput && !startDateInput.value) {
        startDateInput.value = todayFormatted;
    }
    
    if (endDateInput && !endDateInput.value) {
        endDateInput.value = todayFormatted;
    }
}
</script>