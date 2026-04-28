<?php
// pages/inventory-count.php - Inventory Count Page
?>
<style>
    .inventory-count-container {
        padding: 0;
        width: 100%;
    }
    
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
    
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .btn-primary {
        background: #4f9eff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        background: #3b7bcb;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-warning {
        background: #ffc107;
        color: #1a2a3a;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .sessions-table-container {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .table-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eef2f7;
        font-weight: 600;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table {
        width: 100%;
        font-size: 13px;
        margin-bottom: 0;
    }
    
    .table th {
        background: #f8fafc;
        padding: 12px;
        font-weight: 600;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .table td {
        padding: 12px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .badge-in_progress { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-completed { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-pending { background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    
    .btn-action {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 11px;
        margin: 2px;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-continue { background: #4f9eff; color: white; }
    .btn-view { background: #6c757d; color: white; }
    .btn-delete { background: #dc3545; color: white; }
    
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
    
    .count-input {
        width: 100px;
        text-align: center;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px;
    }
    
    .count-input:focus {
        outline: none;
        border-color: #4f9eff;
        box-shadow: 0 0 0 2px rgba(79, 158, 255, 0.1);
    }
    
    .variance-positive { color: #dc3545; font-weight: bold; }
    .variance-negative { color: #28a745; font-weight: bold; }
    .variance-zero { color: #6c757d; }
    
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
    
    .progress-bar-container {
        background: #e2e8f0;
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
        margin-bottom: 5px;
    }
    
    .progress-bar {
        background: #4f9eff;
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s;
    }
    
    .search-box {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        width: 250px;
        font-size: 13px;
    }
    
    .category-filter {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: white;
        font-size: 13px;
    }
    
    .branch-badge {
        background: #e0e7ff;
        color: #4338ca;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .filter-section {
            flex-direction: column;
        }
        .table th, .table td {
            padding: 8px;
            font-size: 11px;
        }
        .count-input {
            width: 60px;
        }
        .search-box {
            width: 100%;
        }
    }
</style>

<div class="inventory-count-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-clipboard-list"></i> Inventory Count</h4>
            <p class="text-muted mb-0">Create and manage physical inventory counts</p>
        </div>
        <button class="btn-primary" onclick="openStartModal()">
            <i class="fas fa-plus"></i> New Count Session
        </button>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalSessions">0</div>
            <div class="stat-label">Total Sessions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-spinner"></i></div>
            <div class="stat-value" id="inProgressCount">0</div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="completedCount">0</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value" id="discrepancyItems">0</div>
            <div class="stat-label">Discrepancies</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <select id="statusFilter" class="form-select" style="width: 150px;">
            <option value="all">All Status</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
        </select>
        <button class="btn-primary" onclick="loadSessions()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    
    <!-- Sessions Table -->
    <div class="sessions-table-container">
        <div class="table-header">
            <span><i class="fas fa-list"></i> Count Sessions</span>
            <span class="branch-badge" id="currentBranchDisplay"></span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Session No</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="sessionsTableBody">
                    <tr><td colspan="7" class="text-center"><div class="loading-spinner"></div> Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Start Count Modal -->
<div class="modal fade" id="startModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-play-circle"></i> Start New Inventory Count</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Session Name</label>
                    <input type="text" id="sessionName" class="form-control" value="Inventory Count - <?php echo date('Y-m-d'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" id="sessionLocation" class="form-control" value="Main Warehouse">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="sessionNotes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmStartBtn">Start Count</button>
            </div>
        </div>
    </div>
</div>

<!-- Count Modal -->
<div class="modal fade" id="countModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="countModalTitle">Inventory Count</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentSessionId">
                
                <!-- Progress Cards -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card p-2 bg-light">
                            <small class="text-muted">Progress</small>
                            <div class="progress-bar-container mt-1">
                                <div class="progress-bar" id="countProgress" style="width: 0%"></div>
                            </div>
                            <span id="progressText" class="small">0%</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-2 bg-light">
                            <small class="text-muted">Items Counted</small>
                            <h5 id="countedItems" class="mb-0">0</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-2 bg-light">
                            <small class="text-muted">Discrepancies</small>
                            <h5 id="discrepancyCount" class="mb-0 text-warning">0</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-2 bg-light">
                            <small class="text-muted">Pending Items</small>
                            <h5 id="pendingCount" class="mb-0 text-secondary">0</h5>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" id="productSearch" class="search-box" placeholder="🔍 Search products..." onkeyup="filterProducts()">
                    </div>
                    <div class="col-md-6">
                        <select id="categoryFilter" class="category-filter" onchange="filterProducts()">
                            <option value="all">All Categories</option>
                        </select>
                    </div>
                </div>
                
                <!-- Products Table -->
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead style="position: sticky; top: 0; background: white;">
                            <tr>
                                <th>Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th class="text-center">System</th>
                                <th class="text-center">Counted</th>
                                <th class="text-center">Variance</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="countItemsBody">
                            <tr><td colspan="9" class="text-center py-5"><div class="loading-spinner"></div><br>Loading products...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="saveCountBtn">
                    <i class="fas fa-save"></i> Save Progress
                </button>
                <button class="btn btn-success" id="completeCountBtn">
                    <i class="fas fa-check-circle"></i> Complete & Adjust Inventory
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = window.location.origin + '/SIDJAN/datafetcher/inventorycountdata.php';

// Store current items for filtering
let currentItems = [];
let currentSession = null;

// ============================================
// API CALLS
// ============================================

async function apiCall(action, method = 'GET', data = null) {
    try {
        const options = { 
            method: method, 
            headers: { 'Content-Type': 'application/json' } 
        };
        if (data) options.body = JSON.stringify(data);
        const response = await fetch(`${API_URL}?action=${action}`, options);
        const result = await response.json();
        if (result.error) {
            console.error('API Error:', result.error);
            showToast(result.error, 'error');
            return { success: false, message: result.error };
        }
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message, 'error');
        return { success: false, message: error.message };
    }
}

async function loadStats() {
    const result = await apiCall('getCountSummary');
    if (result.success && result.data) {
        document.getElementById('totalSessions').innerText = result.data.TotalSessions || 0;
        document.getElementById('inProgressCount').innerText = result.data.InProgressCount || 0;
        document.getElementById('completedCount').innerText = result.data.CompletedCount || 0;
        document.getElementById('discrepancyItems').innerText = result.data.DiscrepancyItems || 0;
    }
}

async function loadSessions() {
    const status = document.getElementById('statusFilter').value;
    const result = await apiCall(`getCountSessions&status=${status}`);
    if (result.success && result.data) {
        displaySessions(result.data);
    } else {
        document.getElementById('sessionsTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No sessions found</td><\/tr>';
    }
}

async function startCount() {
    const data = {
        session_name: document.getElementById('sessionName').value,
        location: document.getElementById('sessionLocation').value,
        notes: document.getElementById('sessionNotes').value
    };
    
    const result = await apiCall('startCount', 'POST', data);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('startModal')).hide();
        loadSessions();
        loadStats();
    } else {
        showToast(result.message || 'Failed to start count', 'error');
    }
}

async function loadCountSession(sessionId) {
    const result = await apiCall(`getCountSessionById&id=${sessionId}`);
    if (result.success && result.data) {
        currentItems = result.items || [];
        currentSession = result.data;
        displayCountItems(currentItems, result.data);
        document.getElementById('currentSessionId').value = sessionId;
        populateCategoryFilter(currentItems);
        document.getElementById('currentBranchDisplay').innerHTML = `<i class="fas fa-store"></i> ${result.data.Branch || 'Current Branch'}`;
    } else {
        document.getElementById('countItemsBody').innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Failed to load products: ' + (result.message || 'Unknown error') + '</td><\/tr>';
    }
}

async function saveCount() {
    const sessionId = document.getElementById('currentSessionId').value;
    const items = [];
    
    document.querySelectorAll('.count-item-row').forEach(row => {
        const itemId = row.dataset.itemId;
        const countedStock = parseInt(row.querySelector('.count-input')?.value) || 0;
        const notes = row.querySelector('.count-notes')?.value || '';
        
        items.push({
            item_id: itemId,
            counted_stock: countedStock,
            notes: notes
        });
    });
    
    const result = await apiCall('saveCount', 'POST', { 
        session_id: sessionId, 
        items: items 
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        await loadCountSession(sessionId);
        loadSessions();
        loadStats();
    } else {
        showToast(result.message || 'Failed to save count', 'error');
    }
}

async function completeCount() {
    const sessionId = document.getElementById('currentSessionId').value;
    
    const confirmed = await showConfirmDialog(
        'Complete Inventory Count',
        'Are you sure you want to complete this count?\n\n' +
        '⚠️ This will:\n' +
        '• Update inventory levels based on your counts\n' +
        '• Mark this session as completed\n' +
        '• This action cannot be undone!\n\n' +
        'Do you want to proceed?'
    );
    
    if (!confirmed) return;
    
    const result = await apiCall('completeCount', 'POST', { 
        session_id: sessionId, 
        adjust_inventory: true 
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('countModal')).hide();
        loadSessions();
        loadStats();
    } else {
        showToast(result.message || 'Failed to complete count', 'error');
    }
}

async function updateSingleCount(itemId, countedStock, notes) {
    const result = await apiCall('updateCount', 'PUT', {
        item_id: itemId,
        counted_stock: countedStock,
        notes: notes
    });
    
    if (!result.success) {
        console.error('Auto-save failed:', result.message);
    }
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displaySessions(sessions) {
    const tbody = document.getElementById('sessionsTableBody');
    
    if (sessions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No sessions found</td><\/tr>';
        return;
    }
    
    tbody.innerHTML = sessions.map(session => {
        const totalItems = session.TotalItems || 0;
        const countedItems = session.CountedItems || 0;
        const progress = totalItems > 0 ? (countedItems / totalItems * 100).toFixed(1) : 0;
        const statusClass = session.Status === 'in_progress' ? 'badge-in_progress' : 'badge-completed';
        const statusText = session.Status === 'in_progress' ? 'IN PROGRESS' : 'COMPLETED';
        
        return `
            <tr>
                <td><strong>${escapeHtml(session.SessionNo)}</strong></td>
                <td>${escapeHtml(session.SessionName)}</td>
                <td>${session.Location || '-'}</td>
                <td>${session.StartDate || '-'}</td>
                <td style="min-width: 120px;">
                    <div class="progress-bar-container" style="width: 100px;">
                        <div class="progress-bar" style="width: ${progress}%"></div>
                    </div>
                    <small class="text-muted">${countedItems}/${totalItems}</small>
                </td>
                <td><span class="${statusClass}">${statusText}</span></td>
                <td>
                    ${session.Status === 'in_progress' 
                        ? `<button class="btn-action btn-continue" onclick="openCountModal(${session.SessionID})"><i class="fas fa-play"></i> Continue</button>`
                        : `<button class="btn-action btn-view" onclick="viewCountSession(${session.SessionID})"><i class="fas fa-eye"></i> View</button>`
                    }
                </td>
            </tr>
        `;
    }).join('');
}

function displayCountItems(items, session) {
    const tbody = document.getElementById('countItemsBody');
    const modalTitle = document.getElementById('countModalTitle');
    
    modalTitle.innerHTML = `<i class="fas fa-clipboard-list"></i> ${escapeHtml(session.SessionName)} - ${session.Location}`;
    
    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No products found</td><\/tr>';
        return;
    }
    
    let counted = 0;
    let discrepancies = 0;
    let pending = 0;
    
    tbody.innerHTML = items.map(item => {
        const isCounted = item.Status !== 'pending';
        if (isCounted) counted++;
        if (item.Variance !== 0 && isCounted) discrepancies++;
        if (item.Status === 'pending') pending++;
        
        let varianceClass = '';
        let varianceText = '';
        if (item.Variance > 0) {
            varianceClass = 'variance-positive';
            varianceText = `+${item.Variance}`;
        } else if (item.Variance < 0) {
            varianceClass = 'variance-negative';
            varianceText = `${item.Variance}`;
        } else {
            varianceClass = 'variance-zero';
            varianceText = '0';
        }
        
        const statusClass = item.Status === 'counted' ? 'badge-completed' : 'badge-pending';
        const statusText = item.Status === 'counted' ? 'COUNTED' : 'PENDING';
        
        return `
            <tr class="count-item-row" data-item-id="${item.ItemID}" data-category="${escapeHtml(item.Category || 'Uncategorized')}" data-product-name="${escapeHtml(item.ProductName).toLowerCase()}">
                <td><small>${escapeHtml(item.ProductCode || 'N/A')}</small></td>
                <td><strong>${escapeHtml(item.ProductName)}</strong></td>
                <td>${escapeHtml(item.Category || '-')}</td>
                <td>${escapeHtml(item.Brand || '-')}</td>
                <td class="text-center system-stock">${item.SystemStock}</td>
                <td class="text-center">
                    <input type="number" class="count-input" 
                           value="${item.CountedStock || 0}" min="0" 
                           onchange="autoSaveCount(${item.ItemID}, this.value, this)">
                </td>
                <td class="text-center"><span class="${varianceClass}">${varianceText}</span></td>
                <td class="text-center"><span class="${statusClass}">${statusText}</span></td>
                <td>
                    <input type="text" class="form-control form-control-sm count-notes" 
                           value="${escapeHtml(item.Notes || '')}" 
                           placeholder="Add notes..." style="width: 150px; font-size: 11px;"
                           onblur="autoSaveNotes(${item.ItemID}, this.value)">
                </td>
            </tr>
        `;
    }).join('');
    
    const totalItems = items.length;
    const progress = totalItems > 0 ? (counted / totalItems * 100).toFixed(1) : 0;
    
    document.getElementById('countProgress').style.width = progress + '%';
    document.getElementById('progressText').innerHTML = `${progress}% (${counted}/${totalItems})`;
    document.getElementById('countedItems').innerHTML = counted;
    document.getElementById('discrepancyCount').innerHTML = discrepancies;
    document.getElementById('pendingCount').innerHTML = pending;
}

function populateCategoryFilter(items) {
    const categories = new Set();
    items.forEach(item => {
        if (item.Category) categories.add(item.Category);
    });
    
    const filterSelect = document.getElementById('categoryFilter');
    filterSelect.innerHTML = '<option value="all">All Categories</option>' + 
        Array.from(categories).sort().map(cat => 
            `<option value="${escapeHtml(cat)}">${escapeHtml(cat)}</option>`
        ).join('');
}

function filterProducts() {
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    
    const rows = document.querySelectorAll('.count-item-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const productName = row.dataset.productName || '';
        const rowCategory = row.dataset.category || '';
        
        const matchesSearch = productName.includes(searchTerm);
        const matchesCategory = category === 'all' || rowCategory === category;
        
        if (matchesSearch && matchesCategory) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show message if no results
    const tbody = document.getElementById('countItemsBody');
    const noResultsRow = tbody.querySelector('.no-results-row');
    if (visibleCount === 0 && !noResultsRow) {
        tbody.insertAdjacentHTML('beforeend', '<tr class="no-results-row"><td colspan="9" class="text-center text-muted py-4">No products match your filters</td><\/tr>');
    } else if (visibleCount > 0 && noResultsRow) {
        noResultsRow.remove();
    }
}

// Auto-save functions
let autoSaveTimeout;
async function autoSaveCount(itemId, countedStock, inputElement) {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(async () => {
        const row = inputElement.closest('tr');
        const systemStock = parseInt(row.querySelector('.system-stock')?.innerText) || 0;
        const variance = countedStock - systemStock;
        const varianceSpan = row.querySelector('td:nth-child(7) span');
        const statusSpan = row.querySelector('td:nth-child(8) span');
        
        // Update variance display
        if (variance > 0) {
            varianceSpan.className = 'variance-positive';
            varianceSpan.innerText = `+${variance}`;
        } else if (variance < 0) {
            varianceSpan.className = 'variance-negative';
            varianceSpan.innerText = `${variance}`;
        } else {
            varianceSpan.className = 'variance-zero';
            varianceSpan.innerText = '0';
        }
        
        // Update status
        if (countedStock > 0) {
            statusSpan.className = 'badge-completed';
            statusSpan.innerText = 'COUNTED';
        } else {
            statusSpan.className = 'badge-pending';
            statusSpan.innerText = 'PENDING';
        }
        
        // Save to server
        await updateSingleCount(itemId, countedStock, '');
        
        // Update stats
        updateStatsAfterSave();
        
        // Show quick indicator
        inputElement.style.backgroundColor = '#e8f5e9';
        setTimeout(() => {
            inputElement.style.backgroundColor = '';
        }, 500);
    }, 500);
}

async function autoSaveNotes(itemId, notes) {
    const countedStock = parseInt(document.querySelector(`.count-item-row[data-item-id="${itemId}"] .count-input`).value) || 0;
    await updateSingleCount(itemId, countedStock, notes);
    
    // Show quick indicator
    const notesInput = document.querySelector(`.count-item-row[data-item-id="${itemId}"] .count-notes`);
    if (notesInput) {
        notesInput.style.backgroundColor = '#e8f5e9';
        setTimeout(() => {
            notesInput.style.backgroundColor = '';
        }, 500);
    }
}

function updateStatsAfterSave() {
    const counted = document.querySelectorAll('.count-item-row .badge-completed').length;
    const total = document.querySelectorAll('.count-item-row').length;
    const discrepancies = document.querySelectorAll('.count-item-row .variance-positive, .count-item-row .variance-negative').length;
    const pending = document.querySelectorAll('.count-item-row .badge-pending').length;
    
    const progress = total > 0 ? (counted / total * 100).toFixed(1) : 0;
    
    document.getElementById('countProgress').style.width = progress + '%';
    document.getElementById('progressText').innerHTML = `${progress}% (${counted}/${total})`;
    document.getElementById('countedItems').innerHTML = counted;
    document.getElementById('discrepancyCount').innerHTML = discrepancies;
    document.getElementById('pendingCount').innerHTML = pending;
}

// ============================================
// MODAL FUNCTIONS
// ============================================

function openStartModal() {
    document.getElementById('sessionName').value = 'Inventory Count - ' + new Date().toLocaleDateString();
    document.getElementById('sessionLocation').value = 'Main Warehouse';
    document.getElementById('sessionNotes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('startModal'));
    modal.show();
}

async function openCountModal(sessionId) {
    document.getElementById('countItemsBody').innerHTML = '<tr><td colspan="9" class="text-center py-5"><div class="loading-spinner"></div><br>Loading products...</td><\/tr>';
    document.getElementById('productSearch').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('countModal'));
    modal.show();
    
    await loadCountSession(sessionId);
    
    // Show save and complete buttons (in case they were hidden from view mode)
    document.getElementById('saveCountBtn').style.display = '';
    document.getElementById('completeCountBtn').style.display = '';
}

async function viewCountSession(sessionId) {
    await openCountModal(sessionId);
    // Make inputs readonly for viewing
    document.querySelectorAll('.count-input, .count-notes').forEach(el => {
        el.disabled = true;
    });
    document.getElementById('saveCountBtn').style.display = 'none';
    document.getElementById('completeCountBtn').style.display = 'none';
}

function showConfirmDialog(title, message) {
    return new Promise((resolve) => {
        const modalHtml = `
            <div class="modal fade" id="confirmModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p style="white-space: pre-line;">${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" id="confirmNo">Cancel</button>
                            <button class="btn btn-success" id="confirmYes">Yes, Complete</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        document.getElementById('confirmYes').onclick = () => {
            modal.hide();
            document.getElementById('confirmModal').remove();
            resolve(true);
        };
        
        document.getElementById('confirmNo').onclick = () => {
            modal.hide();
            document.getElementById('confirmModal').remove();
            resolve(false);
        };
        
        modal.show();
        
        modal._element.addEventListener('hidden.bs.modal', () => {
            if (document.getElementById('confirmModal')) {
                document.getElementById('confirmModal').remove();
            }
        });
    });
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function showToast(message, type = 'success', duration = 3000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'warning'} show`;
    toast.setAttribute('role', 'alert');
    toast.style.minWidth = '250px';
    toast.style.marginBottom = '10px';
    
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle' };
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${icons[type] || 'fa-info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: duration, autohide: true });
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

document.getElementById('confirmStartBtn')?.addEventListener('click', startCount);
document.getElementById('saveCountBtn')?.addEventListener('click', saveCount);
document.getElementById('completeCountBtn')?.addEventListener('click', completeCount);
document.getElementById('statusFilter')?.addEventListener('change', loadSessions);

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadSessions();
});
</script>