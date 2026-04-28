<?php
// pages/stock-in.php - Stock Management with Two Modes: Direct Qty or Per Serial/IMEI
?>
<style>
    .stock-container {
        padding: 0;
        width: 100%;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 15px;
    }
    
    .stat-icon.blue { background: rgba(79, 158, 255, 0.15); color: #4f9eff; }
    .stat-icon.green { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .stat-icon.orange { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .stat-icon.red { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #1a2a3a;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6c7a91;
        margin-top: 5px;
    }
    
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 25px;
        margin-bottom: 30px;
    }
    
    @media (max-width: 992px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .card {
        background: white;
        border-radius: 20px;
        border: none;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 0;
    }
    
    .card-header {
        background: white;
        border-bottom: 1px solid #eef2f7;
        padding: 18px 25px;
        font-weight: 600;
        font-size: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-body {
        padding: 20px 25px;
    }
    
    .product-search {
        margin-bottom: 15px;
        position: relative;
    }
    
    .product-search input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .product-search i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .product-list {
        max-height: 750px;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    .product-list::-webkit-scrollbar {
        width: 5px;
    }
    
    .product-list::-webkit-scrollbar-track {
        background: #eef2f7;
        border-radius: 10px;
    }
    
    .product-list::-webkit-scrollbar-thumb {
        background: #4f9eff;
        border-radius: 10px;
    }
    
    .product-item {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .product-item:hover {
        background: #eef2ff;
        border-color: #4f9eff;
        transform: translateX(3px);
    }
    
    .product-item.selected {
        background: linear-gradient(135deg, #eef2ff, #e6edff);
        border-color: #4f9eff;
        border-left: 4px solid #4f9eff;
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
    
    .product-name {
        font-weight: 600;
        color: #1a2a3a;
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .product-category {
        font-size: 11px;
        color: #6c7a91;
        margin-bottom: 5px;
    }
    
    .unit-row {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .unit-row .input-group {
        flex: 1;
        min-width: 180px;
    }
    
    .unit-row .input-group input {
        border-radius: 8px 0 0 8px;
    }
    
    .unit-row .input-group button {
        border-radius: 0 8px 8px 0;
    }
    
    .unit-row > button {
        white-space: nowrap;
    }
    
    @media (max-width: 768px) {
        .unit-row {
            flex-direction: column;
        }
        .unit-row .input-group {
            width: 100%;
        }
        .unit-row > button {
            width: 100%;
        }
    }
    
    .product-stock {
        font-size: 12px;
    }
    
    .stock-low {
        color: #dc3545;
        font-weight: 600;
    }
    
    .stock-normal {
        color: #28a745;
    }
    
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #4a5568;
        margin-bottom: 6px;
    }
    
    .form-control, .form-select {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #4f9eff;
        box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.1);
    }
    
    .selected-info {
        background: #eef2ff;
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }
    
    .selected-info label {
        font-size: 12px;
        color: #6c7a91;
        margin-bottom: 3px;
    }
    
    .selected-info .value {
        font-weight: 600;
        color: #1a2a3a;
    }
    
    .btn-primary {
        background: #4f9eff;
        border: none;
        border-radius: 12px;
        padding: 12px;
        font-weight: 600;
        width: 100%;
    }
    
    .btn-primary:hover {
        background: #3a7fd9;
    }
    
    .btn-primary:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }
    
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 12px;
        padding: 8px 16px;
        font-size: 13px;
    }
    
    .btn-success:hover {
        background: #218838;
    }
    
    .preview-alert {
        background: #eef2ff;
        border-radius: 12px;
        padding: 12px;
        margin-top: 15px;
        font-size: 13px;
    }
    
    .history-section {
        margin-top: 30px;
    }
    
    .table-responsive {
        border-radius: 12px;
    }
    
    .table th {
        font-weight: 600;
        font-size: 12px;
        background: #f8fafc;
        padding: 12px;
    }
    
    .table td {
        font-size: 12px;
        vertical-align: middle;
        padding: 10px 12px;
    }
    
    .badge-quantity {
        background: #4f9eff;
        color: white;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    /* Mode Toggle */
    .mode-toggle {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        background: #f8fafc;
        padding: 8px;
        border-radius: 12px;
    }
    
    .mode-btn {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        background: transparent;
    }
    
    .mode-btn.active {
        background: #4f9eff;
        color: white;
    }
    
    .mode-btn:not(.active):hover {
        background: #eef2ff;
    }
    
    /* Direct Quantity Mode */
    .direct-qty-section {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    /* Fix Quagga scanner styling */
#scannerContainer {
    position: relative;
    overflow: hidden;
    background: #000;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#scannerContainer video,
#scannerContainer canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
}

#scannerContainer canvas.drawingBuffer {
    z-index: 5;
}

/* Ensure overlay stays on top of video but below controls */
.modal-body {
    position: relative;
}

.scanner-overlay {
    z-index: 10;
}

    /* Per Unit Mode */
    .per-unit-section {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .units-list {
        margin-top: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: white;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .unit-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border-bottom: 1px solid #eef2f7;
        font-size: 12px;
    }
    
    .unit-item:last-child {
        border-bottom: none;
    }
    
    .unit-item .unit-info {
        font-family: monospace;
    }
    
    .unit-item .unit-info i {
        margin-right: 5px;
        color: #4f9eff;
    }
    
    .unit-item .remove-unit-item {
        color: #dc3545;
        cursor: pointer;
    }
    
    .units-count {
        font-size: 11px;
        color: #4f9eff;
        margin-top: 8px;
        text-align: right;
    }
    
    /* Camera Modal */
    #barcodeScannerModal .modal-body {
        padding: 0;
    }
    
    #scannerVideo {
        width: 100%;
        background: #000;
        transform: scaleX(-1);
    }
    
    .scanner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border: 2px solid rgba(79, 158, 255, 0.5);
        margin: 20px;
        border-radius: 16px;
        pointer-events: none;
        box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);
    }
    
    .scanner-line {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        height: 2px;
        background: rgba(255,0,0,0.8);
        box-shadow: 0 0 5px red;
    }
    
    .scanner-instruction {
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
        text-align: center;
        color: white;
        background: rgba(0,0,0,0.7);
        padding: 8px;
        font-size: 12px;
    }
    
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
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
    
    .loading {
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
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .stat-value {
            font-size: 22px;
        }
        
        .product-item {
            flex-wrap: wrap;
        }
        
        .product-thumb {
            width: 40px;
            height: 40px;
        }
    }
</style>

<div class="stock-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-arrow-down"></i> Stock In</h4>
            <p class="text-muted mb-0">Add quantity to existing products</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row" id="statsContainer">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-box"></i></div>
            <div class="stat-value" id="totalProducts">-</div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-peso-sign"></i></div>
            <div class="stat-value" id="totalStockValue">-</div>
            <div class="stat-label">Total Stock Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value" id="lowStockCount">-</div>
            <div class="stat-label">Low Stock Items (&lt;10)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalUnitsAdded">-</div>
            <div class="stat-label">Units Added (30d)</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="main-grid">
        <!-- Left: Product List -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list"></i> Products</span>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> New Product
                </button>
            </div>
            <div class="card-body">
                <div class="product-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search products by name, brand, or category..." onkeyup="filterProducts()">
                </div>
                <div class="product-list" id="productList">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading products...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Add Stock Form -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-plus-circle"></i> Add Quantity to Stock</span>
            </div>
            <div class="card-body">
                <div id="selectedProductInfo" class="selected-info">
                    <label>Selected Product</label>
                    <div class="value" id="selectedProductDisplay">No product selected</div>
                </div>
                
                <!-- Mode Toggle -->
                <div class="mode-toggle">
                    <button class="mode-btn active" onclick="setAddStockMode('direct')">
                        <i class="fas fa-hashtag"></i> Direct Quantity
                    </button>
                    <button class="mode-btn" onclick="setAddStockMode('per-unit')">
                        <i class="fas fa-barcode"></i> Per Serial/IMEI
                    </button>
                </div>
                
                <!-- Direct Quantity Mode -->
                <div id="directMode" style="display: block;">
                    <div class="direct-qty-section">
                        <div class="mb-3">
                            <label class="form-label">Quantity to Add *</label>
                            <input type="number" id="directQuantity" class="form-control" min="1" placeholder="Enter quantity">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Generate Serial Numbers?</label>
                            <select id="generateSerialOption" class="form-select">
                                <option value="none">Don't generate serial numbers</option>
                                <option value="auto">Auto-generate serial numbers</option>
                                <option value="prefix">Generate with prefix</option>
                            </select>
                        </div>
                        <div id="serialPrefixDiv" style="display: none;" class="mb-3">
                            <label class="form-label">Serial Prefix</label>
                            <input type="text" id="serialPrefix" class="form-control" placeholder="e.g., SN">
                        </div>
                    </div>
                </div>
                
                <!-- Per Unit Mode (Multiple IMEI/Serial) -->
                <div id="perUnitMode" style="display: none;">
                    <div class="per-unit-section">
                        <label class="form-label">Add Units (each unit = 1 quantity with its own IMEI & Serial)</label>
                        <div class="unit-row">
                            <div class="input-group">
                                <input type="text" id="imeiInput" class="form-control" placeholder="IMEI Number">
                                <button class="btn btn-outline-primary" type="button" onclick="scanIMEI()" title="Scan IMEI Barcode">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="text" id="serialInput" class="form-control" placeholder="Serial Number">
                                <button class="btn btn-outline-primary" type="button" onclick="scanSerial()" title="Scan Serial Barcode">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="addUnit()">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                        <div class="units-list" id="unitsList">
                            <div class="text-center py-3 text-muted" id="noUnitsMsg">No units added. Add units to increase stock.</div>
                        </div>
                        <div class="units-count" id="unitsCount">Total Units: 0</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price (₱)</label>
                        <input type="number" id="costPrice" class="form-control" step="0.01" placeholder="Cost per unit">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Invoice No.</label>
                        <input type="text" id="invoiceNo" class="form-control" placeholder="INV-001">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" id="supplier" class="form-control" placeholder="Supplier name">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                </div>
                
                <div id="stockPreview" class="preview-alert" style="display: none;">
                    <i class="fas fa-calculator"></i> After adding: <strong id="newStockPreview">0</strong> new units
                </div>
                
                <button class="btn btn-primary" id="addStockBtn" disabled>
                    <i class="fas fa-save"></i> Add to Stock
                </button>
            </div>
        </div>
    </div>

    <!-- Stock History Section -->
    <div class="history-section">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-history"></i> Recent Stock-In History</span>
                <button class="btn btn-danger btn-sm" id="clearHistoryBtn" onclick="clearHistory()" style="display: none;">
                    <i class="fas fa-trash"></i> Clear History
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Old Stock</th>
                                <th>New Stock</th>
                                <th>Cost</th>
                                <th>Total</th>
                                <th>Supplier</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr><td colspan="9" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" id="newProductName" class="form-control" placeholder="e.g., iPhone 15 Pro">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        <select id="newCategory" class="form-select">
                            <option value="">Select Category</option>
                            <option value="Mobile Phones">Mobile Phones</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Tablets">Tablets</option>
                            <option value="Wearables">Wearables</option>
                            <option value="Chargers">Chargers</option>
                            <option value="Cases">Cases</option>
                            <option value="Headphones">Headphones</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Brand</label>
                        <div class="input-group">
                            <select id="newProductBrand" class="form-select">
                                <option value="">Select Brand</option>
                            </select>
                            <button class="btn btn-outline-primary" type="button" onclick="addNewBrand()" title="Add New Brand">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" onclick="refreshBrands()" title="Refresh Brands">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <small class="text-muted">Select existing or click + to add new brand</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Code</label>
                        <input type="text" id="newProductCode" class="form-control" placeholder="Auto-generated or manual">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price (₱)</label>
                        <input type="number" id="newCostPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Selling Price (₱) *</label>
                        <input type="number" id="newSellingPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="newDescription" class="form-control" rows="2" placeholder="Product description, specifications..."></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Invoice No.</label>
                        <input type="text" id="newInvoiceNo" class="form-control" placeholder="INV-001">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" id="newSupplier" class="form-control" placeholder="Supplier name">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProductBtn">Save Product</button>
            </div>
        </div>
    </div>
</div>

<!-- QuaggaJS for barcode scanning -->
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>

<script>
// API Configuration
const API_URL = '/SIDJAN/datafetcher/productdata.php';
const BRAND_API_URL = '/SIDJAN/datafetcher/branddata.php';

// Global variables
let selectedProduct = null;
let allProducts = [];
let addStockUnits = [];
let currentAddStockMode = 'direct';
let allBrands = [];

// Barcode scanner variables
// ============================================
// BARCODE SCANNING FUNCTIONS - FIXED OVERLAPPING
// ============================================

// ============================================
// BARCODE SCANNING FUNCTIONS - IMPROVED ACCURACY
// ============================================

let currentScanField = null;
let scanModalInstance = null;
let scannerActive = false;
let lastScannedCode = '';
let scanTimeout = null;

// Make sure showToast is defined globally
if (typeof showToast === 'undefined') {
    function showToast(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '1100';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.setAttribute('role', 'alert');
        toast.style.display = 'block';
        toast.style.minWidth = '250px';
        toast.style.marginBottom = '10px';
        toast.style.background = 'white';
        toast.style.borderRadius = '12px';
        toast.style.boxShadow = '0 5px 20px rgba(0,0,0,0.15)';
        toast.style.borderLeft = `4px solid ${type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107')}`;
        
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
        
        toast.innerHTML = `
            <div class="toast-header" style="border-bottom: none; padding: 10px;">
                <i class="fas ${icon}" style="color: ${type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107')}; margin-right: 10px;"></i>
                <strong class="me-auto">${type === 'success' ? 'Success' : (type === 'error' ? 'Error' : 'Warning')}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" style="padding: 10px;">${message}</div>
        `;
        
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000, autohide: true });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}

function scanIMEI() {
    openBarcodeScanner('IMEI', (result) => {
        const imeiInput = document.getElementById('imeiInput');
        if (imeiInput) {
            imeiInput.value = result;
            showToast('IMEI scanned: ' + result, 'success');
            const serialInput = document.getElementById('serialInput');
            if (serialInput) serialInput.focus();
        }
    });
}

function scanSerial() {
    openBarcodeScanner('Serial Number', (result) => {
        const serialInput = document.getElementById('serialInput');
        if (serialInput) {
            serialInput.value = result;
            showToast('Serial Number scanned: ' + result, 'success');
        }
    });
}

function openBarcodeScanner(fieldName, callback) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showToast('Camera access is not supported in this browser. Please enter manually.', 'warning');
        return;
    }
    
    currentScanField = callback;
    lastScannedCode = '';
    
    const modalHtml = `
        <div class="modal fade" id="barcodeScannerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 9999;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #1f2937, #2d3a4a); color: white;">
                        <h5 class="modal-title"><i class="fas fa-camera"></i> Scan ${fieldName}</h5>
                        <button type="button" class="btn-close btn-close-white" id="closeScannerBtn"></button>
                    </div>
                    <div class="modal-body" style="padding: 0; position: relative; min-height: 400px;">
                        <div id="scannerContainer" style="width: 100%; min-height: 400px; background: #000; position: relative;"></div>
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 10;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border: 2px solid rgba(79, 158, 255, 0.5); margin: 20px; border-radius: 16px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);"></div>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; height: 2px; background: rgba(255,0,0,0.8); box-shadow: 0 0 5px red;"></div>
                            <div style="position: absolute; bottom: 20px; left: 0; right: 0; text-align: center; color: white; background: rgba(0,0,0,0.7); padding: 8px; font-size: 12px;">
                                <i class="fas fa-qrcode"></i> Position barcode in the center of the red line
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row g-2">
                                <div class="col-8">
                                    <input type="text" id="manualBarcodeInput" class="form-control" placeholder="Or enter ${fieldName} manually">
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-primary w-100" id="submitManualBarcode">Submit</button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-secondary w-100" id="switchCameraBtn">
                                    <i class="fas fa-sync-alt"></i> Switch Camera
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const existingModal = document.getElementById('barcodeScannerModal');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('barcodeScannerModal');
    scanModalInstance = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });
    
    let currentCamera = 'environment';
    
    function startScanner() {
        if (scannerActive) {
            try { Quagga.stop(); } catch(e) {}
            scannerActive = false;
        }
        
        const container = document.getElementById('scannerContainer');
        if (!container) return;
        
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }
        
        Quagga.init({
            inputStream: {
                type: "LiveStream",
                target: container,
                constraints: {
                    facingMode: currentCamera,
                    width: { min: 800, max: 1920 },
                    height: { min: 600, max: 1080 }
                }
            },
            locator: {
                patchSize: "large",
                halfSample: false
            },
            numOfWorkers: 4,
            frequency: 15,
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader",
                    "ean_8_reader",
                    "code_39_reader",
                    "code_39_vin_reader",
                    "codabar_reader",
                    "upc_reader",
                    "upc_e_reader",
                    "i2of5_reader"
                ],
                debug: {
                    drawBoundingBox: true,
                    showFrequency: false,
                    drawScanline: true,
                    showPattern: false
                }
            },
            locate: true
        }, function(err) {
            if (err) {
                console.error("Quagga init error:", err);
                showToast("Failed to start scanner. Please try manual entry.", "error");
                return;
            }
            Quagga.start();
            scannerActive = true;
            
            setTimeout(function() {
                const video = container.querySelector('video');
                if (video) {
                    video.style.width = '100%';
                    video.style.height = 'auto';
                    video.style.objectFit = 'cover';
                    video.style.minHeight = '400px';
                }
            }, 100);
        });
        
        // Debounced scan handler to prevent multiple scans
        Quagga.onDetected(function(result) {
            if (result && result.codeResult && result.codeResult.code) {
                const scannedValue = result.codeResult.code;
                
                // Prevent duplicate scans within 2 seconds
                if (lastScannedCode === scannedValue) {
                    return;
                }
                
                if (scanTimeout) clearTimeout(scanTimeout);
                
                lastScannedCode = scannedValue;
                
                console.log("Scanned value:", scannedValue);
                console.log("Expected format:", fieldName);
                
                // Stop scanning
                try { Quagga.stop(); } catch(e) {}
                scannerActive = false;
                
                if (scanModalInstance) {
                    scanModalInstance.hide();
                }
                
                if (currentScanField) {
                    currentScanField(scannedValue);
                }
            }
        });
    }
    
    modalElement.addEventListener('shown.bs.modal', function() {
        setTimeout(function() {
            startScanner();
        }, 500);
    });
    
    const switchBtn = document.getElementById('switchCameraBtn');
    if (switchBtn) {
        switchBtn.onclick = function() {
            currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
            if (scannerActive) {
                try { Quagga.stop(); } catch(e) {}
                scannerActive = false;
            }
            setTimeout(function() {
                startScanner();
            }, 500);
            showToast("Switched camera", "info");
        };
    }
    
    const submitBtn = document.getElementById('submitManualBarcode');
    if (submitBtn) {
        submitBtn.onclick = function() {
            const manualValue = document.getElementById('manualBarcodeInput').value.trim();
            if (manualValue) {
                if (scanModalInstance) {
                    scanModalInstance.hide();
                }
                if (currentScanField) {
                    currentScanField(manualValue);
                }
            } else {
                showToast("Please enter a value", "warning");
            }
        };
    }
    
    const manualInput = document.getElementById('manualBarcodeInput');
    if (manualInput) {
        manualInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const manualValue = this.value.trim();
                if (manualValue) {
                    if (scanModalInstance) {
                        scanModalInstance.hide();
                    }
                    if (currentScanField) {
                        currentScanField(manualValue);
                    }
                }
            }
        });
    }
    
    const closeBtn = document.getElementById('closeScannerBtn');
    if (closeBtn) {
        closeBtn.onclick = function() {
            if (scanModalInstance) {
                scanModalInstance.hide();
            }
        };
    }
    
    modalElement.addEventListener('hidden.bs.modal', function() {
        if (scannerActive) {
            try { Quagga.stop(); } catch(e) {}
            scannerActive = false;
        }
        if (scanTimeout) clearTimeout(scanTimeout);
        modalElement.remove();
        scanModalInstance = null;
        currentScanField = null;
    });
    
    scanModalInstance.show();
}
// ============================================
// BRAND MANAGEMENT FUNCTIONS
// ============================================

async function loadBrands() {
    try {
        const response = await fetch(BRAND_API_URL + '?action=getBrands');
        const data = await response.json();
        if (data.success && data.data) {
            allBrands = data.data;
            updateBrandDropdown();
        }
    } catch (error) {
        console.error('Error loading brands:', error);
    }
}

function updateBrandDropdown() {
    const brandSelect = document.getElementById('newProductBrand');
    if (!brandSelect) return;
    
    const currentValue = brandSelect.value;
    
    brandSelect.innerHTML = '<option value="">Select Brand</option>';
    allBrands.forEach(brand => {
        brandSelect.innerHTML += `<option value="${escapeHtml(brand.BrandName)}">${escapeHtml(brand.BrandName)}</option>`;
    });
    
    if (currentValue) {
        brandSelect.value = currentValue;
    }
}

async function addNewBrand() {
    const brandName = prompt('Enter new brand name:');
    if (!brandName || brandName.trim() === '') {
        showToast('Brand name is required', 'warning');
        return;
    }
    
    try {
        const response = await fetch(BRAND_API_URL + '?action=addBrand', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ brand_name: brandName.trim() })
        });
        const result = await response.json();
        
        if (result.success) {
            showToast('Brand added successfully', 'success');
            await loadBrands();
        } else {
            showToast(result.message || 'Failed to add brand', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

function refreshBrands() {
    loadBrands();
}

// ============================================
// MODE SWITCHING
// ============================================

function setAddStockMode(mode) {
    currentAddStockMode = mode;
    
    // Update button styles
    document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
    if (mode === 'direct') {
        document.querySelector('.mode-btn:first-child').classList.add('active');
        document.getElementById('directMode').style.display = 'block';
        document.getElementById('perUnitMode').style.display = 'none';
    } else {
        document.querySelector('.mode-btn:last-child').classList.add('active');
        document.getElementById('directMode').style.display = 'none';
        document.getElementById('perUnitMode').style.display = 'block';
    }
    
    updateStockPreview();
}

// ============================================
// PER UNIT MODE FUNCTIONS
// ============================================

function addUnit() {
    const imei = document.getElementById('imeiInput').value.trim();
    const serial = document.getElementById('serialInput').value.trim();
    
    if (!imei && !serial) {
        showToast('Please enter either IMEI or Serial Number', 'warning');
        return;
    }
    
    // Check for duplicate IMEI/Serial in current units
    const isDuplicate = addStockUnits.some(unit => 
        (imei && unit.imei === imei) || (serial && unit.serial === serial)
    );
    
    if (isDuplicate) {
        showToast('This IMEI/Serial number has already been added', 'warning');
        return;
    }
    
    addStockUnits.push({ imei: imei, serial: serial });
    updateUnitsList();
    
    // Clear inputs and focus back to IMEI for next scan
    document.getElementById('imeiInput').value = '';
    document.getElementById('serialInput').value = '';
    document.getElementById('imeiInput').focus();
    
    showToast('Unit added successfully', 'success');
    updateStockPreview();
}

function removeUnit(index) {
    addStockUnits.splice(index, 1);
    updateUnitsList();
    showToast('Unit removed', 'info');
    updateStockPreview();
}

function updateUnitsList() {
    const container = document.getElementById('unitsList');
    const countSpan = document.getElementById('unitsCount');
    
    if (addStockUnits.length === 0) {
        container.innerHTML = '<div class="text-center py-3 text-muted" id="noUnitsMsg">No units added. Add units to increase stock.</div>';
        countSpan.innerHTML = 'Total Units: 0';
        return;
    }
    
    container.innerHTML = addStockUnits.map((unit, index) => `
        <div class="unit-item">
            <div class="unit-info">
                ${unit.imei ? `<i class="fas fa-qrcode"></i> IMEI: ${escapeHtml(unit.imei)}` : ''}
                ${unit.serial ? `<i class="fas fa-barcode"></i> Serial: ${escapeHtml(unit.serial)}` : ''}
            </div>
            <div class="remove-unit-item" onclick="removeUnit(${index})">
                <i class="fas fa-trash"></i>
            </div>
        </div>
    `).join('');
    
    countSpan.innerHTML = `Total Units: ${addStockUnits.length}`;
}

// ============================================
// DIRECT QUANTITY MODE
// ============================================

function getDirectQuantity() {
    const qty = parseInt(document.getElementById('directQuantity').value) || 0;
    const generateOption = document.getElementById('generateSerialOption').value;
    const prefix = document.getElementById('serialPrefix').value;
    
    if (qty <= 0) return [];
    
    if (generateOption === 'none') {
        return Array(qty).fill({ imei: '', serial: '' });
    } else {
        const units = [];
        for (let i = 1; i <= qty; i++) {
            let serial = '';
            if (generateOption === 'auto') {
                serial = 'SN' + Date.now() + String(i).padStart(4, '0');
            } else if (generateOption === 'prefix') {
                serial = (prefix || 'SN') + String(i).padStart(6, '0');
            }
            units.push({ imei: '', serial: serial });
        }
        return units;
    }
}

// ============================================
// API CALLS
// ============================================

async function apiCall(action, method = 'GET', data = null) {
    const options = { 
        method: method, 
        headers: { 'Content-Type': 'application/json' }
    };
    if (data) options.body = JSON.stringify(data);
    
    try {
        const response = await fetch(`${API_URL}?action=${action}`, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast('Network error: ' + error.message, 'error');
        return { success: false, message: 'Network error' };
    }
}

async function loadDashboardStats() {
    const result = await apiCall('getDashboardStats');
    if (result.success && result.data) {
        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(value || 0);
        };

        document.getElementById('totalProducts').innerText = (result.data.TotalProducts || 0).toLocaleString();
        document.getElementById('totalStockValue').innerText = formatCurrency(result.data.TotalStockValue);
        document.getElementById('lowStockCount').innerText = (result.data.LowStockCount || 0).toLocaleString();
        document.getElementById('totalUnitsAdded').innerText = (result.data.TotalUnitsAdded || 0).toLocaleString();
    }
}

async function loadProducts() {
    const result = await apiCall('getProducts');
    if (result.success) {
        allProducts = result.data;
        renderProductList(allProducts);
    }
}

async function loadStockHistory() {
    const result = await apiCall('getStockHistory');
    if (result.success) {
        renderHistoryTable(result.data);
        const clearBtn = document.getElementById('clearHistoryBtn');
        if (clearBtn) clearBtn.style.display = result.data.length > 0 ? 'inline-block' : 'none';
    }
}

async function addStock(productId, units, invoiceNo, supplier, notes) {
    const result = await apiCall('addStock', 'POST', {
        product_id: productId,
        units: units,
        invoice_no: invoiceNo,
        supplier: supplier,
        notes: notes
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        loadDashboardStats();
        loadProducts();
        loadStockHistory();
        return true;
    } else {
        showToast(result.message || 'Failed to add stock', 'error');
        return false;
    }
}

async function addNewProduct(productData) {
    const result = await apiCall('addProduct', 'POST', productData);
    
    if (result.success) {
        showToast(result.message, 'success');
        loadDashboardStats();
        loadProducts();
        loadStockHistory();
        return true;
    } else {
        showToast(result.message || 'Failed to add product', 'error');
        return false;
    }
}

async function clearHistory() {
    if (!confirm('Are you sure you want to clear all stock-in history?')) return;
    
    const result = await apiCall('clearHistory', 'DELETE', { confirm: true });
    if (result.success) {
        showToast('Stock history cleared', 'success');
        loadStockHistory();
    }
}

// ============================================
// RENDER FUNCTIONS
// ============================================

function renderProductList(products) {
    const container = document.getElementById('productList');
    
    if (!products || products.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No products found</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Add First Product
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = products.map(product => {
        let thumbStyle = '';
        if (product.ProductImagePath && product.ProductImagePath !== '') {
            thumbStyle = `style="background-image: url('${product.ProductImagePath}'); background-size: cover; background-position: center; background-repeat: no-repeat;"`;
        }
        
        const hasImage = product.ProductImagePath && product.ProductImagePath !== '';
        
        return `
            <div class="product-item" onclick="selectProduct(${product.ProductID})" data-id="${product.ProductID}">
                <div class="product-thumb" ${thumbStyle}>
                    ${!hasImage ? `<i class="fas fa-box" style="font-size: 24px; color: #94a3b8;"></i>` : ''}
                </div>
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.ProductName)}</div>
                    <div class="product-category">
                        <i class="fas fa-tag"></i> ${product.Category || 'Uncategorized'}
                        ${product.Brand ? ` | <i class="fas fa-building"></i> ${escapeHtml(product.Brand)}` : ''}
                    </div>
                    <div class="product-stock">
                        Stock: <span class="${product.AvailableQuantity < 10 ? 'stock-low' : 'stock-normal'}">${product.AvailableQuantity} / ${product.TotalQuantity} units</span>
                        ${product.AvailableQuantity < 10 ? '<span class="badge bg-danger ms-2">Low Stock!</span>' : ''}
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary">₱${parseFloat(product.SellingPrice || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>
            </div>
        `;
    }).join('');
}

function renderHistoryTable(history) {
    const tbody = document.getElementById('historyTableBody');
    
    if (!history || history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center empty-state"><i class="fas fa-history"></i> No stock-in history yet<\/td><\/tr>';
        return;
    }
    
    tbody.innerHTML = history.map(h => `
        <tr>
            <td><small>${h.TransactionDate || ''}</small></td>
            <td><strong>${escapeHtml(h.ProductName)}</strong></td>
            <td><span class="badge-quantity">+${h.QuantityAdded}</span></td>
            <td>${h.OldStock}</td>
            <td>${h.NewStock}</td>
            <td>₱${parseFloat(h.CostPrice || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td>₱${parseFloat(h.TotalCost || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td>${escapeHtml(h.SupplierName || '-')}</td>
            <td>${escapeHtml(h.InvoiceNo || '-')}</td>
        </tr>
    `).join('');
}

function selectProduct(productId) {
    const product = allProducts.find(p => p.ProductID == productId);
    if (!product) return;
    
    selectedProduct = product;
    
    document.querySelectorAll('.product-item').forEach(item => {
        item.classList.remove('selected');
        if (item.dataset.id == productId) item.classList.add('selected');
    });
    
    document.getElementById('selectedProductDisplay').innerHTML = `<strong>${escapeHtml(product.ProductName)}</strong>`;
    document.getElementById('addStockBtn').disabled = false;
    
    // Reset form
    addStockUnits = [];
    updateUnitsList();
    document.getElementById('directQuantity').value = '';
    document.getElementById('costPrice').value = product.CostPrice || 0;
    
    updateStockPreview();
}

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filtered = allProducts.filter(product => 
        product.ProductName.toLowerCase().includes(searchTerm) ||
        (product.Brand && product.Brand.toLowerCase().includes(searchTerm)) ||
        (product.Category && product.Category.toLowerCase().includes(searchTerm))
    );
    renderProductList(filtered);
}

function updateStockPreview() {
    let qty = 0;
    
    if (currentAddStockMode === 'direct') {
        qty = parseInt(document.getElementById('directQuantity').value) || 0;
    } else {
        qty = addStockUnits.length;
    }
    
    if (selectedProduct && qty > 0) {
        const newStock = (selectedProduct.TotalQuantity || 0) + qty;
        document.getElementById('newStockPreview').innerText = newStock;
        document.getElementById('stockPreview').style.display = 'block';
    } else {
        document.getElementById('stockPreview').style.display = 'none';
    }
}

// ============================================
// EVENT HANDLERS
// ============================================

document.getElementById('generateSerialOption').addEventListener('change', function() {
    document.getElementById('serialPrefixDiv').style.display = this.value === 'prefix' ? 'block' : 'none';
});

document.getElementById('directQuantity').addEventListener('input', updateStockPreview);

document.getElementById('addStockBtn').addEventListener('click', async function() {
    if (!selectedProduct) {
        showToast('Please select a product first', 'warning');
        return;
    }
    
    let units = [];
    
    if (currentAddStockMode === 'direct') {
        units = getDirectQuantity();
        if (units.length === 0) {
            showToast('Please enter a valid quantity', 'warning');
            return;
        }
    } else {
        if (addStockUnits.length === 0) {
            showToast('Please add at least one unit', 'warning');
            return;
        }
        units = addStockUnits;
    }
    
    const costPrice = parseFloat(document.getElementById('costPrice').value) || 0;
    const invoiceNo = document.getElementById('invoiceNo').value;
    const supplier = document.getElementById('supplier').value;
    const notes = document.getElementById('notes').value;
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    const success = await addStock(selectedProduct.ProductID, units, invoiceNo, supplier, notes);
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Add to Stock';
    
    if (success) {
        document.getElementById('invoiceNo').value = '';
        document.getElementById('supplier').value = '';
        document.getElementById('notes').value = '';
        document.getElementById('directQuantity').value = '';
        addStockUnits = [];
        updateUnitsList();
        updateStockPreview();
        document.getElementById('imeiInput').value = '';
        document.getElementById('serialInput').value = '';
    }
});

document.getElementById('saveProductBtn').addEventListener('click', async function() {
    const productName = document.getElementById('newProductName').value.trim();
    const category = document.getElementById('newCategory').value;
    const sellingPrice = parseFloat(document.getElementById('newSellingPrice').value) || 0;
    
    if (!productName || !category || sellingPrice <= 0) {
        showToast('Product name, category, and selling price are required', 'warning');
        return;
    }
    
    const productData = {
        product_name: productName,
        category: category,
        brand: document.getElementById('newProductBrand').value,
        product_code: document.getElementById('newProductCode').value || 'P' + Date.now(),
        cost_price: parseFloat(document.getElementById('newCostPrice').value) || 0,
        selling_price: sellingPrice,
        description: document.getElementById('newDescription').value,
        invoice_no: document.getElementById('newInvoiceNo').value,
        supplier_name: document.getElementById('newSupplier').value,
        units: []
    };
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    const success = await addNewProduct(productData);
    
    btn.disabled = false;
    btn.innerHTML = 'Save Product';
    
    if (success) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
        modal.hide();
        
        // Reset form
        document.getElementById('newProductName').value = '';
        document.getElementById('newCategory').value = '';
        document.getElementById('newProductBrand').value = '';
        document.getElementById('newProductCode').value = '';
        document.getElementById('newCostPrice').value = '';
        document.getElementById('newSellingPrice').value = '';
        document.getElementById('newInvoiceNo').value = '';
        document.getElementById('newSupplier').value = '';
        document.getElementById('newDescription').value = '';
    }
});

// Enter key to add unit
document.getElementById('imeiInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') addUnit();
});
document.getElementById('serialInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') addUnit();
});

// ============================================
// HELPER FUNCTIONS
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Create toast container if not exists
    if (!document.getElementById('toastContainer')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    loadDashboardStats();
    loadProducts();
    loadStockHistory();
    loadBrands();
    setAddStockMode('direct');
});
</script>