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
    }
    
    .table td {
        padding: 12px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .badge-in_progress { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-completed { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-pending { background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
    .btn-action {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 11px;
        margin: 2px;
        cursor: pointer;
        border: none;
    }
    
    .btn-continue { background: #4f9eff; color: white; }
    .btn-view { background: #6c757d; color: white; }
    
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
    }
    
    .progress-bar {
        background: #4f9eff;
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s;
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
        <button class="btn-primary" onclick="loadSessions()">Refresh</button>
    </div>
    
    <!-- Sessions Table -->
    <div class="sessions-table-container">
        <div class="table-header">
            <span><i class="fas fa-list"></i> Count Sessions</span>
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
                    <tr><td colspan="7" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>
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
                    <textarea id="sessionNotes" class="form-control" rows="2"></textarea>
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
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card p-2">
                            <small class="text-muted">Progress</small>
                            <div class="progress-bar-container mt-1">
                                <div class="progress-bar" id="countProgress" style="width: 0%"></div>
                            </div>
                            <span id="progressText">0%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-2">
                            <small class="text-muted">Items Counted</small>
                            <h5 id="countedItems">0</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-2">
                            <small class="text-muted">Discrepancies</small>
                            <h5 id="discrepancyCount">0</h5>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>System Stock</th>
                                <th>Counted Stock</th>
                                <th>Variance</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="countItemsBody">
                            <tr><td colspan="8" class="text-center">Loading...<\/td><\/tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="saveCountBtn">Save Progress</button>
                <button class="btn btn-success" id="completeCountBtn">Complete & Adjust Inventory</button>
            </div>
        </div>
    </div>
</div>

<script>
// API Configuration
const API_URL = '/POS/datafetcher/inventorycountdata.php';

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
        showToast(error.message, 'error');
        return { success: false };
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
        document.getElementById('sessionsTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted">No sessions found<\/td><\/tr>';
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
        displayCountItems(result.data, result.items);
        document.getElementById('currentSessionId').value = sessionId;
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
    
    const result = await apiCall('saveCount', 'POST', { session_id: sessionId, items: items });
    if (result.success) {
        showToast(result.message, 'success');
        loadCountSession(sessionId);
        loadSessions();
        loadStats();
    } else {
        showToast(result.message || 'Failed to save count', 'error');
    }
}

async function completeCount(sessionId) {
    if (!confirm('Are you sure you want to complete this count? This will update inventory levels based on your counts.')) return;
    
    const result = await apiCall('completeCount', 'POST', { session_id: sessionId, adjust_inventory: true });
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('countModal')).hide();
        loadSessions();
        loadStats();
    } else {
        showToast(result.message || 'Failed to complete count', 'error');
    }
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displaySessions(sessions) {
    const tbody = document.getElementById('sessionsTableBody');
    
    if (sessions.length === 0) {
        tbody.innerHTML = '<td><td colspan="7" class="text-center text-muted">No sessions found<\/td><\/tr>';
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
                <td><strong>${session.SessionNo}</strong><\/td>
                <td>${escapeHtml(session.SessionName)}<\/td>
                <td>${session.Location || '-'}<\/td>
                <td>${session.StartDate || '-'}<\/td>
                <td>
                    <div class="progress-bar-container" style="width: 100px;">
                        <div class="progress-bar" style="width: ${progress}%"></div>
                    </div>
                    <small>${countedItems}/${totalItems}</small>
                <\/td>
                <td><span class="${statusClass}">${statusText}</span><\/td>
                <td>
                    ${session.Status === 'in_progress' 
                        ? `<button class="btn-action btn-continue" onclick="openCountModal(${session.SessionID})"><i class="fas fa-play"></i> Continue</button>`
                        : `<button class="btn-action btn-view" onclick="viewCountSession(${session.SessionID})"><i class="fas fa-eye"></i> View</button>`
                    }
                <\/td>
            </tr>
        `;
    }).join('');
}

function displayCountItems(session, items) {
    const tbody = document.getElementById('countItemsBody');
    const modalTitle = document.getElementById('countModalTitle');
    
    modalTitle.innerHTML = `<i class="fas fa-clipboard-list"></i> ${escapeHtml(session.SessionName)} - ${session.Location}`;
    
    if (!items || items.length === 0) {
        tbody.innerHTML = '<td><td colspan="8" class="text-center text-muted">No items found<\/td><\/tr>';
        return;
    }
    
    let counted = 0;
    let discrepancies = 0;
    
    tbody.innerHTML = items.map(item => {
        const isCounted = item.Status !== 'pending';
        if (isCounted) counted++;
        if (item.Variance !== 0 && isCounted) discrepancies++;
        
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
            <tr class="count-item-row" data-item-id="${item.ItemID}">
                <td>${item.ProductCode || 'N/A'}<\/td>
                <td><strong>${escapeHtml(item.ProductName)}<\/strong><\/td>
                <td>${item.Category || '-'}<\/td>
                <td>${item.SystemStock}<\/td>
                <td>
                    <input type="number" class="form-control form-control-sm count-input" 
                           value="${item.CountedStock || 0}" min="0" 
                           ${item.Status === 'counted' ? '' : ''}
                           style="width: 100px;">
                <\/td>
                <td><span class="${varianceClass}">${varianceText}</span><\/td>
                <td><span class="${statusClass}">${statusText}</span><\/td>
                <td>
                    <input type="text" class="form-control form-control-sm count-notes" 
                           value="${escapeHtml(item.Notes || '')}" 
                           placeholder="Notes" style="width: 150px;">
                <\/td>
            </tr>
        `;
    }).join('');
    
    const totalItems = items.length;
    const progress = totalItems > 0 ? (counted / totalItems * 100).toFixed(1) : 0;
    
    document.getElementById('countProgress').style.width = progress + '%';
    document.getElementById('progressText').innerHTML = `${progress}% (${counted}/${totalItems})`;
    document.getElementById('countedItems').innerHTML = `${counted}/${totalItems}`;
    document.getElementById('discrepancyCount').innerHTML = discrepancies;
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
    document.getElementById('countItemsBody').innerHTML = '<tr><td colspan="8" class="text-center"><div class="loading-spinner"></div> Loading...<\/td><\/tr>';
    
    const modal = new bootstrap.Modal(document.getElementById('countModal'));
    modal.show();
    
    await loadCountSession(sessionId);
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

// ============================================
// HELPER FUNCTIONS
// ============================================

function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} show`;
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

document.getElementById('confirmStartBtn').addEventListener('click', startCount);
document.getElementById('saveCountBtn').addEventListener('click', saveCount);
document.getElementById('completeCountBtn').addEventListener('click', function() {
    const sessionId = document.getElementById('currentSessionId').value;
    completeCount(sessionId);
});
document.getElementById('statusFilter').addEventListener('change', loadSessions);

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadSessions();
});
</script>