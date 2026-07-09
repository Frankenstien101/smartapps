<?php
// pages/discount.php - Discount Management (with Percentage & Exact Value)
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
?>
<style>
    .discount-container {
        padding: 0;
        width: 100%;
    }
    
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
        width: 100%;
    }
    
    .filter-group {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
        flex: 1;
    }
    
    .filter-group .form-group {
        flex: 0 0 auto;
        min-width: 150px;
    }
    
    .filter-group .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #4a5568;
    }
    
    .filter-group .form-group input,
    .filter-group .form-group select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        background: #fafcff;
    }
    
    .filter-section .btn {
        padding: 10px 22px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    
    .btn-primary { background: #4f9eff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-warning { background: #ffc107; color: #1a2a3a; }
    .btn-purple { background: #8b5cf6; color: white; }
    
    .btn-primary:hover { background: #3b8be8; }
    .btn-success:hover { background: #1e8e3a; }
    .btn-danger:hover { background: #c82333; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-warning:hover { background: #e0a800; }
    .btn-purple:hover { background: #7c3aed; }
    
    .card-custom {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .card-header {
        padding: 14px 20px;
        border-bottom: 1px solid #eef2f7;
        font-weight: 600;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .card-body {
        padding: 18px 20px;
        overflow-x: auto;
    }
    
    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }
    
    .table {
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
        min-width: 800px;
    }
    
    .table th {
        background: #f8fafc;
        padding: 10px 14px;
        font-weight: 600;
        text-align: left;
        border-bottom: 2px solid #eef2f7;
        white-space: nowrap;
    }
    
    .table td {
        padding: 10px 14px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .table tbody tr:hover {
        background: #f8fafc;
    }
    
    .discount-input {
        width: 70px;
        padding: 6px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        text-align: center;
        font-size: 14px;
    }
    
    .discount-input:focus {
        outline: none;
        border-color: #4f9eff;
        box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.1);
    }
    
    .discount-input.has-discount {
        background: #dbeafe;
        border-color: #2563eb;
    }
    
    .discount-input.has-less {
        background: #fef3c7;
        border-color: #d97706;
    }
    
    .discount-input.has-both {
        background: #dcfce7;
        border-color: #16a34a;
    }
    
    .badge-discount {
        background: #dbeafe;
        color: #2563eb;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-less {
        background: #fef3c7;
        color: #d97706;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-both {
        background: #dcfce7;
        color: #16a34a;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-no-discount {
        background: #e2e8f0;
        color: #475569;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
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
        top: 20px;
        right: 20px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .toast {
        padding: 14px 24px;
        border-radius: 12px;
        color: white;
        font-weight: 500;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
        max-width: 400px;
    }
    
    .toast.success { background: #10b981; }
    .toast.error { background: #ef4444; }
    .toast.info { background: #4f9eff; }
    .toast.warning { background: #f59e0b; }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .no-data {
        text-align: center;
        padding: 30px;
        color: #6c7a91;
    }
    
    .search-box {
        position: relative;
    }
    
    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .search-box input {
        padding-left: 35px;
    }
    
    .final-price {
        font-weight: 700;
    }
    
    .final-price.has-discount {
        color: #2563eb;
    }
    
    .final-price.has-less {
        color: #d97706;
    }
    
    .final-price.has-both {
        color: #16a34a;
    }
    
    .discount-summary {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .discount-summary .stat {
        background: #f8fafc;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        color: #475569;
    }
    
    .discount-summary .stat strong {
        color: #1a2a3a;
    }
    
    @media (max-width: 768px) {
        .filter-section {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-group {
            flex-direction: column;
        }
        .filter-group .form-group {
            min-width: unset;
            width: 100%;
        }
        .filter-section .btn {
            justify-content: center;
            width: 100%;
        }
        .table th, .table td {
            padding: 6px 8px;
            font-size: 11px;
        }
        .discount-input {
            width: 50px;
            font-size: 12px;
            padding: 4px 4px;
        }
        .discount-summary {
            flex-direction: column;
            gap: 5px;
        }
    }
</style>

<div class="discount-container">
    
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 style="margin:0;font-size:22px;"><i class="fas fa-percent"></i> Discount Management</h4>
            <p class="text-muted mb-0" style="font-size:14px;">Set percentage discount (% off) and exact value discount (₱ off) per product</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn btn-purple" onclick="applyBulkDiscount()">
                <i class="fas fa-layer-group"></i> Bulk Apply
            </button>
            <button class="btn btn-danger" onclick="clearSelectedDiscounts()">
                <i class="fas fa-times-circle"></i> Clear Selected
            </button>
            <button class="btn btn-success" onclick="saveAllDiscounts()">
                <i class="fas fa-save"></i> Save All
            </button>
        </div>
    </div>
    
    <!-- FILTERS -->
    <div class="filter-section">
        <div class="filter-group">
            <div class="form-group">
                <label>Branch</label>
                <select id="branchFilter">
                    <option value="all">All Branches</option>
                    <option value="<?php echo $currentBranch; ?>" selected><?php echo $currentBranch; ?></option>
                </select>
            </div>
            <div class="form-group search-box">
                <label>Search Product</label>
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by code or name..." onkeyup="filterProducts()">
            </div>
            <div class="form-group">
                <label>Show</label>
                <select id="filterDiscount" onchange="filterProducts()">
                    <option value="all">All Products</option>
                    <option value="has_discount">With Discount</option>
                    <option value="no_discount">No Discount</option>
                    <option value="has_percent">Percentage Only</option>
                    <option value="has_less">Exact Value Only</option>
                    <option value="has_both">Both Discounts</option>
                </select>
            </div>
        </div>
        <button class="btn btn-primary" onclick="loadProducts()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
    
    <!-- PRODUCTS TABLE -->
    <div class="card-custom">
        <div class="card-header">
            <span><i class="fas fa-list"></i> Products</span>
            <div class="discount-summary">
                <span class="stat" id="recordCount">0 records</span>
                <span class="stat" id="discountSummary">No discounts</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="productTable">
                    <thead>
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Original Price</th>
                            <th>Stock</th>
                            <th>Discount %</th>
                            <th>Less (₱)</th>
                            <th>Final Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <tr>
                            <td colspan="12" class="text-center">
                                <div class="loading-spinner"></div> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<!-- Bulk Discount Modal -->
<div class="modal fade" id="bulkDiscountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: white; border-radius:16px 16px 0 0;">
                <h5 class="modal-title"><i class="fas fa-layer-group"></i> Bulk Apply Discount</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Applying to <strong id="bulkCount">0</strong> selected products
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Percentage Discount (%)</label>
                    <input type="number" id="bulkPercent" class="form-control" placeholder="e.g. 10" min="0" max="100" step="0.01">
                    <small class="text-muted">Leave empty to skip</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Exact Value Discount (₱)</label>
                    <input type="number" id="bulkLess" class="form-control" placeholder="e.g. 50" min="0" step="0.01">
                    <small class="text-muted">Leave empty to skip</small>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="bulkOverride">
                    <label class="form-check-label" for="bulkOverride">
                        Override existing discounts
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-purple" onclick="applyBulkDiscountConfirm()">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ============================================
// CONFIGURATION
// ============================================
const API_URL = '/dmb/datafetcher/discountdata.php';

let allProducts = [];
let selectedProducts = [];
let currentBranch = '<?php echo $currentBranch; ?>';

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatNumber(num) {
    if (num === undefined || num === null || isNaN(num)) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function calculateFinalPrice(price, discountPercent, lessAmount) {
    let finalPrice = parseFloat(price) || 0;
    const discount = parseFloat(discountPercent) || 0;
    const less = parseFloat(lessAmount) || 0;
    
    if (discount > 0) {
        finalPrice = finalPrice - (finalPrice * (discount / 100));
    }
    if (less > 0) {
        finalPrice = finalPrice - less;
    }
    return Math.max(0, finalPrice);
}

function getDiscountType(discount, less) {
    const hasPercent = parseFloat(discount) > 0;
    const hasLess = parseFloat(less) > 0;
    
    if (hasPercent && hasLess) return 'both';
    if (hasPercent) return 'percent';
    if (hasLess) return 'less';
    return 'none';
}

function getBadgeHTML(discount, less) {
    const type = getDiscountType(discount, less);
    const d = parseFloat(discount) || 0;
    const l = parseFloat(less) || 0;
    
    switch(type) {
        case 'percent':
            return `<span class="badge-discount">${d}% OFF</span>`;
        case 'less':
            return `<span class="badge-less">₱${formatNumber(l)} OFF</span>`;
        case 'both':
            return `<span class="badge-both">${d}% + ₱${formatNumber(l)} OFF</span>`;
        default:
            return `<span class="badge-no-discount">No Discount</span>`;
    }
}

// ============================================
// API CALLS
// ============================================
async function apiCall(action, method = 'GET', data = null) {
    try {
        const options = { method, headers: { 'Content-Type': 'application/json' } };
        if (data) options.body = JSON.stringify(data);
        const response = await fetch(`${API_URL}?action=${action}`, options);
        const result = await response.json();
        if (!result.success) {
            showToast(result.message || 'API Error', 'error');
        }
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false };
    }
}

// ============================================
// LOAD PRODUCTS
// ============================================
async function loadProducts() {
    const branch = document.getElementById('branchFilter').value;
    const search = document.getElementById('searchInput').value.trim();
    
    const result = await apiCall(`getProducts&branch=${branch}&search=${encodeURIComponent(search)}`);
    
    if (result.success) {
        allProducts = result.data;
        renderProducts(allProducts);
        updateSummary(allProducts);
    }
}

function renderProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    
    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="no-data">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map((product, index) => {
        const sellingPrice = parseFloat(product.SellingPrice) || 0;
        const discount = parseFloat(product.Discount) || 0;
        const less = parseFloat(product.Less) || 0;
        const finalPrice = calculateFinalPrice(sellingPrice, discount, less);
        const type = getDiscountType(discount, less);
        
        let inputClass = '';
        if (type === 'percent') inputClass = 'has-discount';
        else if (type === 'less') inputClass = 'has-less';
        else if (type === 'both') inputClass = 'has-both';
        
        let finalPriceClass = 'final-price';
        if (type === 'percent') finalPriceClass += ' has-discount';
        else if (type === 'less') finalPriceClass += ' has-less';
        else if (type === 'both') finalPriceClass += ' has-both';
        
        return `
            <tr>
                <td>
                    <input type="checkbox" class="product-checkbox" data-index="${index}" onchange="updateSelectedCount()">
                </td>
                <td><strong>${escapeHtml(product.ProductCode || 'N/A')}</strong></td>
                <td>${escapeHtml(product.ProductName || 'N/A')}</td>
                <td>${escapeHtml(product.Category || '-')}</td>
                <td>${escapeHtml(product.Brand || '-')}</td>
                <td style="font-weight:600;color:#28a745;">₱${formatNumber(sellingPrice)}</td>
                <td>${product.AvailableQuantity || 0}</td>
                <td>
                    <input type="number" class="discount-input ${inputClass}" 
                           value="${discount}" min="0" max="100" step="0.01"
                           data-product-id="${product.ProductID}"
                           data-field="discount"
                           onchange="updateDiscount(${product.ProductID}, this, 'discount')"
                           onkeyup="updateDiscount(${product.ProductID}, this, 'discount')">
                    <span style="font-size:11px;color:#6c7a91;">%</span>
                </td>
                <td>
                    <input type="number" class="discount-input ${inputClass}" 
                           value="${less}" min="0" step="0.01"
                           data-product-id="${product.ProductID}"
                           data-field="less"
                           onchange="updateDiscount(${product.ProductID}, this, 'less')"
                           onkeyup="updateDiscount(${product.ProductID}, this, 'less')">
                    <span style="font-size:11px;color:#6c7a91;">₱</span>
                </td>
                <td>
                    <span class="${finalPriceClass}">₱${formatNumber(finalPrice)}</span>
                </td>
                <td>${getBadgeHTML(discount, less)}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="quickClear(${product.ProductID})" title="Clear discounts" style="padding:3px 8px;font-size:11px;">
                        <i class="fas fa-undo"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    // Reset select all
    document.getElementById('selectAll').checked = false;
    updateSelectedCount();
}

// ============================================
// UPDATE SUMMARY
// ============================================
function updateSummary(products) {
    const total = products.length;
    let percentCount = 0;
    let lessCount = 0;
    let bothCount = 0;
    
    products.forEach(p => {
        const d = parseFloat(p.Discount) || 0;
        const l = parseFloat(p.Less) || 0;
        if (d > 0 && l > 0) bothCount++;
        else if (d > 0) percentCount++;
        else if (l > 0) lessCount++;
    });
    
    document.getElementById('recordCount').textContent = `${total} records`;
    
    let summaryParts = [];
    if (percentCount > 0) summaryParts.push(`<strong>${percentCount}</strong> with % off`);
    if (lessCount > 0) summaryParts.push(`<strong>${lessCount}</strong> with ₱ off`);
    if (bothCount > 0) summaryParts.push(`<strong>${bothCount}</strong> with both`);
    if (summaryParts.length === 0) summaryParts.push('No discounts');
    
    document.getElementById('discountSummary').innerHTML = summaryParts.join(' | ');
}

// ============================================
// FILTER PRODUCTS
// ============================================
function filterProducts() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const filterType = document.getElementById('filterDiscount').value;
    
    let filtered = allProducts;
    
    // Search filter
    if (search) {
        filtered = filtered.filter(p => 
            (p.ProductCode && p.ProductCode.toLowerCase().includes(search)) ||
            (p.ProductName && p.ProductName.toLowerCase().includes(search)) ||
            (p.Brand && p.Brand.toLowerCase().includes(search))
        );
    }
    
    // Discount filter
    if (filterType !== 'all') {
        filtered = filtered.filter(p => {
            const d = parseFloat(p.Discount || 0);
            const l = parseFloat(p.Less || 0);
            const type = getDiscountType(d, l);
            
            switch(filterType) {
                case 'has_discount': return type !== 'none';
                case 'no_discount': return type === 'none';
                case 'has_percent': return type === 'percent' || type === 'both';
                case 'has_less': return type === 'less' || type === 'both';
                case 'has_both': return type === 'both';
                default: return true;
            }
        });
    }
    
    renderProducts(filtered);
    updateSummary(filtered);
}

// ============================================
// SELECT ALL / UPDATE SELECTED
// ============================================
function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    selectedProducts = [];
    document.querySelectorAll('.product-checkbox:checked').forEach(cb => {
        const index = parseInt(cb.dataset.index);
        if (allProducts[index]) {
            selectedProducts.push(allProducts[index]);
        }
    });
}

function getSelectedIds() {
    const ids = [];
    document.querySelectorAll('.product-checkbox:checked').forEach(cb => {
        const index = parseInt(cb.dataset.index);
        if (allProducts[index]) {
            ids.push(allProducts[index].ProductID);
        }
    });
    return ids;
}

// ============================================
// UPDATE DISCOUNT
// ============================================
function updateDiscount(productId, input, field) {
    const value = parseFloat(input.value) || 0;
    const product = allProducts.find(p => p.ProductID === productId);
    if (!product) return;
    
    // Update the product data
    if (field === 'discount') {
        product.Discount = value;
    } else {
        product.Less = value;
    }
    
    // Update the final price display
    const tr = input.closest('tr');
    if (tr) {
        const discount = parseFloat(product.Discount) || 0;
        const less = parseFloat(product.Less) || 0;
        const sellingPrice = parseFloat(product.SellingPrice) || 0;
        const finalPrice = calculateFinalPrice(sellingPrice, discount, less);
        
        // Update final price cell
        const finalPriceCell = tr.querySelector('td:nth-child(10) span');
        if (finalPriceCell) {
            finalPriceCell.textContent = '₱' + formatNumber(finalPrice);
            const type = getDiscountType(discount, less);
            finalPriceCell.className = 'final-price';
            if (type === 'percent') finalPriceCell.classList.add('has-discount');
            else if (type === 'less') finalPriceCell.classList.add('has-less');
            else if (type === 'both') finalPriceCell.classList.add('has-both');
        }
        
        // Update badge
        const badgeCell = tr.querySelector('td:nth-child(11)');
        if (badgeCell) {
            badgeCell.innerHTML = getBadgeHTML(discount, less);
        }
        
        // Update input styling
        const inputs = tr.querySelectorAll('.discount-input');
        const hasDiscount = discount > 0;
        const hasLess = less > 0;
        let inputClass = '';
        if (hasDiscount && hasLess) inputClass = 'has-both';
        else if (hasDiscount) inputClass = 'has-discount';
        else if (hasLess) inputClass = 'has-less';
        
        inputs.forEach(inp => {
            inp.className = 'discount-input ' + inputClass;
        });
    }
    
    // Update summary
    updateSummary(allProducts);
}

// ============================================
// QUICK CLEAR
// ============================================
function quickClear(productId) {
    if (!confirm('Clear all discounts for this product?')) return;
    
    const product = allProducts.find(p => p.ProductID === productId);
    if (product) {
        product.Discount = 0;
        product.Less = 0;
        
        // Update inputs
        const inputs = document.querySelectorAll(`input[data-product-id="${productId}"]`);
        inputs.forEach(inp => {
            inp.value = 0;
            const field = inp.dataset.field;
            updateDiscount(productId, inp, field);
        });
        
        showToast('Discounts cleared', 'success');
    }
}

// ============================================
// BULK APPLY DISCOUNT
// ============================================
function applyBulkDiscount() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showToast('Please select at least one product', 'warning');
        return;
    }
    
    document.getElementById('bulkCount').textContent = selectedIds.length;
    document.getElementById('bulkPercent').value = '';
    document.getElementById('bulkLess').value = '';
    document.getElementById('bulkOverride').checked = false;
    
    new bootstrap.Modal(document.getElementById('bulkDiscountModal')).show();
}

function applyBulkDiscountConfirm() {
    const percent = parseFloat(document.getElementById('bulkPercent').value) || 0;
    const less = parseFloat(document.getElementById('bulkLess').value) || 0;
    const override = document.getElementById('bulkOverride').checked;
    
    if (percent === 0 && less === 0) {
        showToast('Please enter at least one discount value', 'warning');
        return;
    }
    
    if (percent > 100) {
        showToast('Percentage cannot exceed 100%', 'error');
        return;
    }
    
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showToast('No products selected', 'warning');
        return;
    }
    
    // Apply to selected products
    selectedIds.forEach(id => {
        const product = allProducts.find(p => p.ProductID === id);
        if (product) {
            if (override || product.Discount === 0) {
                product.Discount = percent;
            }
            if (override || product.Less === 0) {
                product.Less = less;
            }
            
            // Update inputs
            const discountInput = document.querySelector(`input[data-product-id="${id}"][data-field="discount"]`);
            const lessInput = document.querySelector(`input[data-product-id="${id}"][data-field="less"]`);
            
            if (discountInput) {
                discountInput.value = product.Discount;
                updateDiscount(id, discountInput, 'discount');
            }
            if (lessInput) {
                lessInput.value = product.Less;
                updateDiscount(id, lessInput, 'less');
            }
        }
    });
    
    bootstrap.Modal.getInstance(document.getElementById('bulkDiscountModal')).hide();
    showToast(`Applied discounts to ${selectedIds.length} products`, 'success');
    updateSummary(allProducts);
}

// ============================================
// CLEAR SELECTED DISCOUNTS
// ============================================
function clearSelectedDiscounts() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showToast('Please select at least one product', 'warning');
        return;
    }
    
    if (!confirm(`Clear all discounts for ${selectedIds.length} selected products?`)) return;
    
    selectedIds.forEach(id => {
        const product = allProducts.find(p => p.ProductID === id);
        if (product) {
            product.Discount = 0;
            product.Less = 0;
            
            const inputs = document.querySelectorAll(`input[data-product-id="${id}"]`);
            inputs.forEach(inp => {
                inp.value = 0;
                const field = inp.dataset.field;
                updateDiscount(id, inp, field);
            });
        }
    });
    
    showToast(`Cleared discounts for ${selectedIds.length} products`, 'success');
}

// ============================================
// SAVE ALL DISCOUNTS
// ============================================
async function saveAllDiscounts() {
    // Collect all discounts
    const discounts = [];
    document.querySelectorAll('.discount-input[data-field="discount"]').forEach(input => {
        const productId = parseInt(input.dataset.productId);
        const discount = parseFloat(input.value) || 0;
        // Find the less input for this product
        const lessInput = document.querySelector(`input[data-product-id="${productId}"][data-field="less"]`);
        const less = parseFloat(lessInput?.value) || 0;
        
        discounts.push({
            product_id: productId,
            discount: discount,
            less: less
        });
    });
    
    if (discounts.length === 0) {
        showToast('No products to save', 'warning');
        return;
    }
    
    const btn = document.querySelector('.btn-success');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    const result = await apiCall('saveDiscounts', 'POST', { discounts: discounts });
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save All';
    
    if (result.success) {
        showToast(result.message, 'success');
        loadProducts();
    }
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
});

// Refresh when branch changes
document.getElementById('branchFilter').addEventListener('change', function() {
    loadProducts();
});
</script>