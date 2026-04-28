<?php
// pages/stock-in.php - Stock Management Frontend with IMEI, Serial, Image, and Camera Switch
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
        max-height: 450px;
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
    
    /* Image Upload */
    .image-upload-area {
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8fafc;
    }
    
    .image-upload-area:hover {
        border-color: #4f9eff;
        background: #eef2ff;
    }
    
    .image-preview {
        margin-top: 15px;
        text-align: center;
        position: relative;
    }
    
    .image-preview img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }
    
    .image-actions {
        margin-top: 10px;
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .image-actions button {
        padding: 4px 12px;
        font-size: 12px;
        border-radius: 8px;
    }
    
    /* Camera Modal */
    .camera-container {
        position: relative;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
    
    #video {
        width: 100%;
        border-radius: 16px;
        background: #1a2a3a;
        transform: scaleX(-1);
    }
    
    #canvas {
        display: none;
    }
    
    .camera-controls {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .camera-controls button {
        min-width: 100px;
    }
    
    .switch-camera-btn {
        background: #6c757d;
    }
    
    .switch-camera-btn:hover {
        background: #5a6268;
    }
    
    .modal-content {
        border-radius: 20px;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #4f9eff, #3a7fd9);
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
        
        .camera-controls button {
            min-width: 80px;
            font-size: 12px;
        }
    }
</style>

<div class="stock-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-arrow-down"></i> Stock In</h4>
            <p class="text-muted mb-0">Add quantity to existing products or create new products</p>
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
                    <input type="text" id="searchInput" placeholder="Search products by name, brand, IMEI, or Serial..." onkeyup="filterProducts()">
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
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Current Stock</label>
                        <div class="form-control bg-light" id="currentStockDisplay" style="background: #f8fafc;">-</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantity to Add *</label>
                        <input type="number" id="quantity" class="form-control" min="1" placeholder="Enter quantity">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price</label>
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
                    <i class="fas fa-calculator"></i> After adding: <strong id="newStockPreview">0</strong> units
                </div>
                
                <button class="btn btn-primary" id="addStockBtn" disabled>
                    <i class="fas fa-save"></i> Add Quantity to Stock
                </button>
            </div>
        </div>
    </div>

    <!-- Stock History Section -->
    <div class="history-section">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-history"></i> Recent Stock-In History</span>
                <button class="btn btn-danger btn-sm" id="clearHistoryBtn" onclick="clearHistory()" style="display: none;"> <i class="fas fa-trash"></i> Clear History
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

<!-- Add Product Modal with IMEI, Serial, Image -->
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
                        <input type="text" id="newBrand" class="form-control" placeholder="e.g., Apple, Samsung">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Code</label>
                        <input type="text" id="newProductCode" class="form-control" placeholder="Auto-generated or manual">
                    </div>
                </div>
                
                <!-- Product Image -->
                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <div class="image-upload-area" onclick="document.getElementById('productImage').click()">
                        <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                        <p class="mb-0 small">Click to upload product image</p>
                        <p class="small text-muted">or</p>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); openCameraModal()">
                            <i class="fas fa-camera"></i> Take Photo
                        </button>
                    </div>
                    <input type="file" id="productImage" class="d-none" accept="image/*" onchange="previewImage(this)">
                    <div class="image-preview" id="imagePreview" style="display: none;">
                        <img id="previewImg" src="" alt="Product preview">
                        <div class="image-actions">
                            <button class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="openCameraModal()">
                                <i class="fas fa-camera"></i> Retake
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing & Stock -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Initial Stock</label>
                        <input type="number" id="newInitialStock" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cost Price</label>
                        <input type="number" id="newCostPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Selling Price</label>
                        <input type="number" id="newSellingPrice" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                </div>
                
                <!-- IMEI and Serial Numbers -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">IMEI Number</label>
                        <input type="text" id="newIMEI" class="form-control" placeholder="15-digit IMEI number">
                        <small class="text-muted">For mobile phones with unique identifier</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" id="newSerial" class="form-control" placeholder="Product serial number">
                        <small class="text-muted">Unique serial number for warranty</small>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="newDescription" class="form-control" rows="2" placeholder="Product description, specifications..."></textarea>
                </div>
                
                <!-- Supplier Info -->
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

<!-- Camera Modal with Switch Camera Option -->
<div class="modal fade" id="cameraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-camera"></i> Take Photo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="camera-container">
                    <video id="video" autoplay playsinline></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                </div>
                <div class="camera-controls mt-3">
                    <button class="btn btn-secondary switch-camera-btn" onclick="switchCamera()">
                        <i class="fas fa-sync-alt"></i> Switch Camera
                    </button>
                    <button class="btn btn-primary" onclick="takePhoto()">
                        <i class="fas fa-camera"></i> Capture
                    </button>
                    <button class="btn btn-secondary" onclick="stopCamera()" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/productdata.php';

// Global variables
let selectedProduct = null;
let allProducts = [];
let mediaStream = null;
let capturedImageData = null;
let currentFacingMode = 'environment'; // 'user' for front, 'environment' for back

// ============================================
// HELPER FUNCTIONS
// ============================================

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    toast.style.display = 'block';
    toast.style.minWidth = '250px';
    toast.style.marginBottom = '10px';
    
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    const bgColor = type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107');
    
    toast.innerHTML = `
        <div class="toast-header" style="border-bottom: none;">
            <i class="fas ${icon}" style="color: ${bgColor}; margin-right: 10px;"></i>
            <strong class="me-auto">${type === 'success' ? 'Success' : (type === 'error' ? 'Error' : 'Warning')}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000, autohide: true });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// ============================================
// CAMERA FUNCTIONS WITH SWITCH SUPPORT
// ============================================

async function openCameraModal() {
    const cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
    cameraModal.show();
    await startCamera();
}

async function startCamera() {
    stopCamera();
    
    const constraints = {
        video: {
            facingMode: { exact: currentFacingMode }
        }
    };
    
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
        const video = document.getElementById('video');
        video.srcObject = mediaStream;
        // Remove mirror effect for back camera
        if (currentFacingMode === 'environment') {
            video.style.transform = 'scaleX(1)';
        } else {
            video.style.transform = 'scaleX(-1)';
        }
    } catch (error) {
        console.error('Camera error with exact facingMode:', error);
        // Fallback: try without exact constraint
        try {
            const fallbackConstraints = {
                video: {
                    facingMode: currentFacingMode
                }
            };
            mediaStream = await navigator.mediaDevices.getUserMedia(fallbackConstraints);
            const video = document.getElementById('video');
            video.srcObject = mediaStream;
            if (currentFacingMode === 'environment') {
                video.style.transform = 'scaleX(1)';
            } else {
                video.style.transform = 'scaleX(-1)';
            }
        } catch (fallbackError) {
            console.error('Camera error fallback:', fallbackError);
            showToast('Unable to access camera. Please check permissions.', 'error');
        }
    }
}

async function switchCamera() {
    // Toggle between front and back camera
    currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
    await startCamera();
    showToast(`Switched to ${currentFacingMode === 'environment' ? 'Back' : 'Front'} camera`, 'info');
}

function stopCamera() {
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
        mediaStream = null;
    }
}

function takePhoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw the video frame to canvas
    if (currentFacingMode === 'user') {
        // For front camera, mirror the image back to normal
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        context.setTransform(1, 0, 0, 1, 0, 0);
    } else {
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
    }
    
    capturedImageData = canvas.toDataURL('image/jpeg', 0.8);
    
    const previewImg = document.getElementById('previewImg');
    previewImg.src = capturedImageData;
    document.getElementById('imagePreview').style.display = 'block';
    document.querySelector('.image-upload-area').style.display = 'none';
    
    stopCamera();
    bootstrap.Modal.getInstance(document.getElementById('cameraModal')).hide();
    showToast('Photo captured successfully!', 'success');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            capturedImageData = e.target.result;
            document.getElementById('previewImg').src = capturedImageData;
            document.getElementById('imagePreview').style.display = 'block';
            document.querySelector('.image-upload-area').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    capturedImageData = null;
    document.getElementById('imagePreview').style.display = 'none';
    document.querySelector('.image-upload-area').style.display = 'block';
    document.getElementById('productImage').value = '';
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
        document.getElementById('totalUnitsAdded').innerText = formatCurrency(result.data.TotalUnitsAdded);
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

async function addStock(productId, quantity, costPrice, invoiceNo, supplier, notes) {
    const result = await apiCall('addStock', 'POST', {
        product_id: productId,
        quantity: quantity,
        cost_price: costPrice,
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
                        Stock: <span class="${product.CurrentStock < 10 ? 'stock-low' : 'stock-normal'}">${product.CurrentStock} units</span>
                        ${product.CurrentStock < 10 ? '<span class="badge bg-danger ms-2">Low Stock!</span>' : ''}
                    </div>
                    ${product.IMEINumber ? `<div class="small text-muted"><i class="fas fa-qrcode"></i> IMEI: ${product.IMEINumber}</div>` : ''}
                    ${product.SerialNumber ? `<div class="small text-muted"><i class="fas fa-barcode"></i> Serial: ${product.SerialNumber}</div>` : ''}
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
    document.getElementById('currentStockDisplay').innerHTML = `${product.CurrentStock} units`;
    document.getElementById('costPrice').value = product.CostPrice || 0;
    document.getElementById('addStockBtn').disabled = false;
    document.getElementById('quantity').value = '';
    document.getElementById('stockPreview').style.display = 'none';
}

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filtered = allProducts.filter(product => 
        product.ProductName.toLowerCase().includes(searchTerm) ||
        (product.Brand && product.Brand.toLowerCase().includes(searchTerm)) ||
        (product.Category && product.Category.toLowerCase().includes(searchTerm)) ||
        (product.IMEINumber && product.IMEINumber.toLowerCase().includes(searchTerm)) ||
        (product.SerialNumber && product.SerialNumber.toLowerCase().includes(searchTerm))
    );
    renderProductList(filtered);
}

function updateStockPreview() {
    const qty = parseInt(document.getElementById('quantity').value) || 0;
    if (selectedProduct && qty > 0) {
        const newStock = (selectedProduct.CurrentStock || 0) + qty;
        document.getElementById('newStockPreview').innerText = newStock;
        document.getElementById('stockPreview').style.display = 'block';
    } else {
        document.getElementById('stockPreview').style.display = 'none';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// EVENT HANDLERS
// ============================================

document.getElementById('quantity').addEventListener('input', updateStockPreview);

document.getElementById('addStockBtn').addEventListener('click', async function() {
    if (!selectedProduct) {
        showToast('Please select a product first', 'warning');
        return;
    }
    
    const quantity = parseInt(document.getElementById('quantity').value);
    if (!quantity || quantity <= 0) {
        showToast('Please enter a valid quantity', 'warning');
        return;
    }
    
    const costPrice = parseFloat(document.getElementById('costPrice').value) || 0;
    const invoiceNo = document.getElementById('invoiceNo').value;
    const supplier = document.getElementById('supplier').value;
    const notes = document.getElementById('notes').value;
    
    const success = await addStock(selectedProduct.ProductID, quantity, costPrice, invoiceNo, supplier, notes);
    
    if (success) {
        document.getElementById('quantity').value = '';
        document.getElementById('invoiceNo').value = '';
        document.getElementById('supplier').value = '';
        document.getElementById('notes').value = '';
        document.getElementById('stockPreview').style.display = 'none';
    }
});

document.getElementById('saveProductBtn').addEventListener('click', async function() {
    const productName = document.getElementById('newProductName').value.trim();
    const category = document.getElementById('newCategory').value;
    
    if (!productName || !category) {
        showToast('Product name and category are required', 'warning');
        return;
    }
    
    const productData = {
        product_name: productName,
        category: category,
        brand: document.getElementById('newBrand').value,
        product_code: document.getElementById('newProductCode').value || 'P' + Date.now(),
        initial_stock: parseInt(document.getElementById('newInitialStock').value) || 0,
        cost_price: parseFloat(document.getElementById('newCostPrice').value) || 0,
        selling_price: parseFloat(document.getElementById('newSellingPrice').value) || 0,
        invoice_no: document.getElementById('newInvoiceNo').value,
        supplier_name: document.getElementById('newSupplier').value,
        description: document.getElementById('newDescription').value,
        product_image: capturedImageData || null,
        imei_number: document.getElementById('newIMEI').value,
        serial_number: document.getElementById('newSerial').value
    };
    
    const success = await addNewProduct(productData);
    
    if (success) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
        modal.hide();
        
        // Reset form
        document.getElementById('newProductName').value = '';
        document.getElementById('newCategory').value = '';
        document.getElementById('newBrand').value = '';
        document.getElementById('newProductCode').value = '';
        document.getElementById('newInitialStock').value = '0';
        document.getElementById('newCostPrice').value = '';
        document.getElementById('newSellingPrice').value = '';
        document.getElementById('newInvoiceNo').value = '';
        document.getElementById('newSupplier').value = '';
        document.getElementById('newDescription').value = '';
        document.getElementById('newIMEI').value = '';
        document.getElementById('newSerial').value = '';
        removeImage();
    }
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadProducts();
    loadStockHistory();
});

window.addEventListener('beforeunload', function() {
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
    }
});
</script>