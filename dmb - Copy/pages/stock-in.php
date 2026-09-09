<?php
// pages/stock-in.php - Stock Management (Bulk Upload Support)
?>
<style>
    .stock-container {
        padding: 0;
        width: 100%;
    }

    .btn-excel-export {
        background: #0d6efd;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-excel-export:hover {
        background: #0b5ed7;
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
    .stat-icon.purple { background: rgba(156, 39, 176, 0.15); color: #9c27b0; }
    
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
        flex-wrap: wrap;
        gap: 10px;
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
    
    .btn-excel-upload {
        background: #217346;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 8px 16px;
        font-size: 13px;
        cursor: pointer;
    }
    
    .btn-excel-upload:hover {
        background: #1a5c38;
    }
    
    .btn-excel-download {
        background: #ff9800;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 8px 16px;
        font-size: 13px;
        cursor: pointer;
    }
    
    .btn-excel-download:hover {
        background: #e68900;
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
    
    .direct-qty-section {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    /* Excel Upload Modal */
    .upload-area {
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .upload-area:hover {
        border-color: #4f9eff;
        background: #f8fafc;
    }
    
    .upload-area.dragover {
        border-color: #4f9eff;
        background: #eef2ff;
    }
    
    .upload-area i {
        font-size: 48px;
        color: #94a3b8;
        margin-bottom: 15px;
    }
    
    .upload-area .upload-text {
        font-size: 16px;
        color: #4a5568;
    }
    
    .upload-area .upload-subtext {
        font-size: 13px;
        color: #6c7a91;
        margin-top: 5px;
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
    
    .bulk-upload-info {
        background: #eef2ff;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
        font-size: 13px;
        border-left: 4px solid #4f9eff;
    }
    
    .bulk-upload-info i {
        color: #4f9eff;
        margin-right: 8px;
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
        .upload-area {
            padding: 20px;
        }
    }
</style>

<div class="stock-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-arrow-down"></i> Stock In</h4>
            <p class="text-muted mb-0">Add quantity to existing products</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn btn-excel-upload" onclick="openExcelUploadModal()">
                <i class="fas fa-file-excel"></i> Upload Excel
            </button>
            <button class="btn btn-excel-download" onclick="downloadExcelTemplate()">
                <i class="fas fa-download"></i> Download Template
            </button>
            <button class="btn btn-excel-export" onclick="exportProducts()">
                <i class="fas fa-file-export"></i> Export Data
            </button>
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
                
                <div class="direct-qty-section">
                    <div class="mb-3">
                        <label class="form-label">Quantity to Add *</label>
                        <input type="number" id="directQuantity" class="form-control" min="1" placeholder="Enter quantity">
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
                            <tr><td colspan="9" class="text-center"><div class="loading-spinner"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ADD PRODUCT MODAL - WITH NAME AND CATEGORY -->
<!-- ============================================ -->
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
                        <label class="form-label">Product Code <span class="text-danger">*</span></label>
                        <input type="text" id="newProductCode" class="form-control" placeholder="e.g., PH-001">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" id="newProductName" class="form-control" placeholder="e.g., iPhone 15 Pro">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" id="newCategory" class="form-control" placeholder="e.g., Electronics">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price (₱)</label>
                        <input type="number" id="newCostPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Selling Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" id="newSellingPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Initial Stock Quantity</label>
                        <input type="number" id="newStockQty" class="form-control" min="0" placeholder="0">
                    </div>
                </div>
                
                <div class="alert alert-info small mt-2">
                    <i class="fas fa-info-circle"></i> 
                    All fields marked with <span class="text-danger">*</span> are required.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProductBtn">Save Product</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- EXCEL UPLOAD MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="excelUploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #217346, #1a5c38); color: white;">
                <h5 class="modal-title"><i class="fas fa-file-excel"></i> Upload Excel File</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Upload an Excel file (.xlsx, .xls, .csv) with your products. 
                    <a href="#" onclick="downloadExcelTemplate()" style="color:#217346; font-weight:600;">
                        <i class="fas fa-download"></i> Download template
                    </a>
                </div>
                
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('excelFile').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div class="upload-text">Click to upload or drag and drop</div>
                    <div class="upload-subtext">Supported formats: .xlsx, .xls, .csv</div>
                    <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" style="display:none;" onchange="handleExcelFile(this)">
                </div>
                
                <div id="uploadPreview" style="display: none; margin-top: 15px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-excel" style="color:#217346;"></i> <strong id="fileName"></strong></span>
                        <span id="fileSize"></span>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar" id="uploadProgress" style="width: 0%; background: #217346;"></div>
                    </div>
                    <div id="uploadStatus" class="mt-2"></div>
                </div>
                
                <div id="previewTableContainer" style="display: none; margin-top: 15px; max-height: 300px; overflow-y: auto;">
                    <table class="table table-bordered table-sm">
                        <thead id="previewTableHead"></thead>
                        <tbody id="previewTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" id="processExcelBtn" onclick="processExcelUpload()" style="display:none;">
                    <i class="fas fa-upload"></i> Process Upload
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// API Configuration
const API_URL = '/dmb/datafetcher/productdata.php';
const BRAND_API_URL = '/dmb/datafetcher/branddata.php';

// Global variables
let selectedProduct = null;
let allProducts = [];
let addStockUnits = [];
let currentAddStockMode = 'direct';
let allBrands = [];
let excelData = [];

// ============================================
// TOAST FUNCTION
// ============================================
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '99999';
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
    toast.style.padding = '0';
    toast.style.overflow = 'hidden';
    
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    const color = type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107');
    
    toast.innerHTML = `
        <div style="padding: 12px 15px; display: flex; align-items: center; border-bottom: none;">
            <i class="fas ${icon}" style="color: ${color}; margin-right: 12px; font-size: 18px;"></i>
            <div style="flex: 1; font-weight: 500;">${message}</div>
            <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #999; line-height: 1;">&times;</button>
        </div>
    `;
    
    container.appendChild(toast);
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 300);
        }
    }, 5000);
}

// ============================================
// EXPORT PRODUCTS DATA
// ============================================
function exportProducts() {
    showToast('Exporting products...', 'info');
    window.open('/dmb/datafetcher/productdata.php?action=exportProducts');
    setTimeout(() => {
        showToast('Export started!', 'success');
    }, 1000);
}

// ============================================
// DOWNLOAD EXCEL TEMPLATE - WITH NAME AND CATEGORY
// ============================================
function downloadExcelTemplate() {
    const template = [
        ['Product Code', 'Product Name', 'Category', 'Cost Price', 'Selling Price', 'Initial Stock'],
        ['PH-001', 'iPhone 15 Pro', 'Electronics', '45000', '55000', '10'],
        ['PH-002', 'Samsung Galaxy S24', 'Electronics', '40000', '50000', '5'],
        ['AC-001', 'Air Conditioner', 'Appliances', '12000', '15000', '20'],
        ['CH-001', 'Chocolate Bar', 'Food', '500', '800', '50'],
        ['', '', '', '', '', '']
    ];
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(template);
    
    ws['!cols'] = [
        { wch: 20 },
        { wch: 25 },
        { wch: 20 },
        { wch: 20 },
        { wch: 20 },
        { wch: 20 }
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Products');
    
    const instructions = [
        ['INSTRUCTIONS FOR BULK PRODUCT UPLOAD'],
        [''],
        ['This template is for BULK product upload.'],
        ['Products will be added with the specified quantity directly to stock.'],
        [''],
        ['Required Fields:'],
        ['  - Product Code: Unique product code (required)'],
        ['  - Product Name: Name of the product (required)'],
        ['  - Category: Product category (required)'],
        ['  - Selling Price: Selling price per unit (required)'],
        [''],
        ['Optional Fields:'],
        ['  - Cost Price: Cost per unit'],
        ['  - Initial Stock: Starting quantity (default: 0)'],
        [''],
        ['Notes:'],
        ['  - Empty rows are skipped'],
        ['  - Product Code must be unique']
    ];
    const ws2 = XLSX.utils.aoa_to_sheet(instructions);
    XLSX.utils.book_append_sheet(wb, ws2, 'Instructions');
    
    const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
    const blob = new Blob([wbout], { type: 'application/octet-stream' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Bulk_Product_Upload_Template.xlsx';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Template downloaded successfully!', 'success');
}

// ============================================
// OPEN EXCEL UPLOAD MODAL
// ============================================
function openExcelUploadModal() {
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('previewTableContainer').style.display = 'none';
    document.getElementById('processExcelBtn').style.display = 'none';
    document.getElementById('excelFile').value = '';
    excelData = [];
    const modal = new bootstrap.Modal(document.getElementById('excelUploadModal'));
    modal.show();
}

// ============================================
// HANDLE EXCEL FILE
// ============================================
function handleExcelFile(input) {
    const file = input.files[0];
    if (!file) return;
    
    const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'];
    if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/)) {
        showToast('Please upload a valid Excel file (.xlsx, .xls, .csv)', 'error');
        return;
    }
    
    document.getElementById('uploadPreview').style.display = 'block';
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    document.getElementById('uploadProgress').style.width = '30%';
    document.getElementById('uploadStatus').innerHTML = '<span class="text-primary">Reading file...</span>';
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });
            
            if (jsonData.length === 0) {
                document.getElementById('uploadStatus').innerHTML = '<span class="text-danger">No data found in the file</span>';
                return;
            }
            
            excelData = jsonData;
            document.getElementById('uploadProgress').style.width = '100%';
            document.getElementById('uploadStatus').innerHTML = `<span class="text-success">Loaded ${excelData.length} rows. Click "Process Upload" to import.</span>`;
            document.getElementById('processExcelBtn').style.display = 'inline-block';
            
            displayExcelPreview(jsonData);
            
        } catch (error) {
            console.error('Error reading file:', error);
            document.getElementById('uploadStatus').innerHTML = `<span class="text-danger">Error reading file: ${error.message}</span>`;
            showToast('Error reading file', 'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

// ============================================
// DISPLAY EXCEL PREVIEW
// ============================================
function displayExcelPreview(data) {
    const container = document.getElementById('previewTableContainer');
    const thead = document.getElementById('previewTableHead');
    const tbody = document.getElementById('previewTableBody');
    
    if (!data || data.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    
    const headers = Object.keys(data[0]);
    thead.innerHTML = `<tr>${headers.map(h => `<th>${escapeHtml(h)}</th>`).join('')}</tr>`;
    
    const displayRows = data.slice(0, 10);
    tbody.innerHTML = displayRows.map(row => {
        return `<tr>${headers.map(h => `<td>${escapeHtml(String(row[h] || ''))}</td>`).join('')}</tr>`;
    }).join('');
    
    if (data.length > 10) {
        tbody.innerHTML += `<tr><td colspan="${headers.length}" class="text-muted text-center">... and ${data.length - 10} more rows</td></tr>`;
    }
}

// ============================================
// PROCESS EXCEL UPLOAD - WITH NAME AND CATEGORY
// ============================================
async function processExcelUpload() {
    if (!excelData || excelData.length === 0) {
        showToast('No data to process', 'warning');
        return;
    }
    
    const btn = document.getElementById('processExcelBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    document.getElementById('uploadStatus').innerHTML = '<span class="text-primary">Processing data...</span>';
    
    try {
        // Map all fields including name and category
        const mappedData = excelData.map(row => {
            const productCode = String(row['Product Code'] || '').trim();
            const productName = String(row['Product Name'] || '').trim();
            const category = String(row['Category'] || '').trim();
            const costPrice = parseFloat(row['Cost Price']) || 0;
            const sellingPrice = parseFloat(row['Selling Price']) || 0;
            const initialStock = parseInt(row['Initial Stock']) || 0;
            
            return {
                'Product Code': productCode,
                'Product Name': productName || productCode, // Fallback to code if name is empty
                'Category': category || 'Others', // Default category if empty
                'Cost Price': costPrice,
                'Selling Price': sellingPrice,
                'Initial Stock': initialStock
            };
        }).filter(row => row['Product Code'] !== '' && row['Selling Price'] > 0);
        
        if (mappedData.length === 0) {
            showToast('No valid products found in the file. Please check required fields.', 'warning');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload"></i> Process Upload';
            return;
        }
        
        const result = await apiCall('processExcelUpload', 'POST', { data: mappedData });
        
        if (result.success) {
            document.getElementById('uploadStatus').innerHTML = `<span class="text-success">✅ ${result.message}</span>`;
            showToast(result.message, 'success');
            
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('excelUploadModal'));
                if (modal) modal.hide();
                loadDashboardStats();
                loadProducts();
                loadStockHistory();
            }, 2000);
        } else {
            document.getElementById('uploadStatus').innerHTML = `<span class="text-danger">❌ ${result.message}</span>`;
            showToast(result.message || 'Failed to process upload', 'error');
        }
    } catch (error) {
        console.error('Error processing upload:', error);
        document.getElementById('uploadStatus').innerHTML = `<span class="text-danger">Error: ${error.message}</span>`;
        showToast('Error processing upload', 'error');
    }
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload"></i> Process Upload';
}

// ============================================
// DRAG AND DROP FOR EXCEL UPLOAD
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById('excelFile');
                input.files = files;
                handleExcelFile(input);
            }
        });
    }
});

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

// ============================================
// LOAD FUNCTIONS
// ============================================
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

async function loadBrands() {
    try {
        const response = await fetch(BRAND_API_URL + '?action=getBrands');
        const data = await response.json();
        if (data.success && data.data) {
            allBrands = data.data;
        }
    } catch (error) {
        console.error('Error loading brands:', error);
    }
}

// ============================================
// ADD STOCK - BULK MODE
// ============================================
async function addStock(productId, quantity, invoiceNo, supplier, notes) {
    // Create unit array with empty IMEI/Serial (bulk mode)
    const units = [];
    for (let i = 0; i < quantity; i++) {
        units.push({ imei: '', serial: '' });
    }
    
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

// ============================================
// ADD NEW PRODUCT - WITH NAME AND CATEGORY
// ============================================
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
                    <div class="product-name" style="font-size: 16px; font-weight: 700;">${escapeHtml(product.ProductCode || 'N/A')}</div>
                    <div class="product-category">
                        ${product.ProductName ? `<i class="fas fa-tag"></i> ${escapeHtml(product.ProductName)}` : ''}
                        ${product.Category ? ` | ${escapeHtml(product.Category)}` : ''}
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
        tbody.innerHTML = '<tr><td colspan="9" class="text-center empty-state"><i class="fas fa-history"></i> No stock-in history yet</td></tr>';
        return;
    }
    
    tbody.innerHTML = history.map(h => `
        <tr>
            <td><small>${h.TransactionDate || ''}</small></td>
            <td><strong>${escapeHtml(h.ProductName)}</strong></td>
            <td><span class="badge-quantity">
             ${h.QuantityAdded > 0 ? `+${h.QuantityAdded}` : `${h.QuantityAdded}`}
             </span></td>
            <td>${h.OldStock}</td>
            <td>${h.NewStock}</td>
            <td>₱${parseFloat(h.CostPrice || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td>₱${parseFloat(h.TotalCost || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td>${escapeHtml(h.SupplierName || '-')}</td>
            <td>${escapeHtml(h.InvoiceNo || '-')}</td>
        </tr>
    `).join('');
}

// ============================================
// SELECT PRODUCT
// ============================================
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

// ============================================
// STOCK PREVIEW
// ============================================
function updateStockPreview() {
    const qty = parseInt(document.getElementById('directQuantity').value) || 0;
    
    if (selectedProduct && qty > 0) {
        const newStock = (selectedProduct.TotalQuantity || 0) + qty;
        document.getElementById('newStockPreview').innerText = newStock;
        document.getElementById('stockPreview').style.display = 'block';
    } else {
        document.getElementById('stockPreview').style.display = 'none';
    }
}

// ============================================
// ADD STOCK BUTTON
// ============================================
document.getElementById('addStockBtn').addEventListener('click', async function() {
    if (!selectedProduct) {
        showToast('Please select a product first', 'warning');
        return;
    }
    
    const quantity = parseInt(document.getElementById('directQuantity').value) || 0;
    if (quantity <= 0) {
        showToast('Please enter a valid quantity', 'warning');
        return;
    }
    
    const costPrice = parseFloat(document.getElementById('costPrice').value) || 0;
    const invoiceNo = document.getElementById('invoiceNo').value;
    const supplier = document.getElementById('supplier').value;
    const notes = document.getElementById('notes').value;
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    const success = await addStock(selectedProduct.ProductID, quantity, invoiceNo, supplier, notes);
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Add to Stock';
    
    if (success) {
        document.getElementById('invoiceNo').value = '';
        document.getElementById('supplier').value = '';
        document.getElementById('notes').value = '';
        document.getElementById('directQuantity').value = '';
        updateStockPreview();
    }
});

// ============================================
// SAVE PRODUCT - WITH NAME AND CATEGORY
// ============================================
document.getElementById('saveProductBtn').addEventListener('click', async function() {
    const productCode = document.getElementById('newProductCode').value.trim();
    const productName = document.getElementById('newProductName').value.trim();
    const category = document.getElementById('newCategory').value.trim();
    const costPrice = parseFloat(document.getElementById('newCostPrice').value) || 0;
    const sellingPrice = parseFloat(document.getElementById('newSellingPrice').value) || 0;
    const stockQty = parseInt(document.getElementById('newStockQty').value) || 0;
    
    if (!productCode) {
        showToast('Product code is required', 'warning');
        document.getElementById('newProductCode').focus();
        return;
    }
    
    if (!productName) {
        showToast('Product name is required', 'warning');
        document.getElementById('newProductName').focus();
        return;
    }
    
    if (!category) {
        showToast('Category is required', 'warning');
        document.getElementById('newCategory').focus();
        return;
    }
    
    if (sellingPrice <= 0) {
        showToast('Selling price must be greater than 0', 'warning');
        document.getElementById('newSellingPrice').focus();
        return;
    }
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    const productData = {
        product_code: productCode,
        product_name: productName,
        category: category,
        cost_price: costPrice,
        selling_price: sellingPrice,
        description: '',
        invoice_no: '',
        supplier_name: '',
        initial_stock: stockQty,
        units: [] // Empty units array for bulk mode
    };
    
    const success = await addNewProduct(productData);
    
    btn.disabled = false;
    btn.innerHTML = 'Save Product';
    
    if (success) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
        modal.hide();
        
        document.getElementById('newProductCode').value = '';
        document.getElementById('newProductName').value = '';
        document.getElementById('newCategory').value = '';
        document.getElementById('newCostPrice').value = '';
        document.getElementById('newSellingPrice').value = '';
        document.getElementById('newStockQty').value = '';
    }
});

// ============================================
// CLEAR HISTORY
// ============================================
async function clearHistory() {
    if (!confirm('Are you sure you want to clear all stock-in history?')) return;
    
    const result = await apiCall('clearHistory', 'DELETE', { confirm: true });
    if (result.success) {
        showToast('Stock history cleared', 'success');
        loadStockHistory();
    }
}

// ============================================
// ESCAPE HTML
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
    
    // Add event listener for quantity input
    document.getElementById('directQuantity').addEventListener('input', updateStockPreview);
});
</script>