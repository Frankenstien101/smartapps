<!-- REPORT SECTION STARTS HERE -->
<?php
// Get filter parameters
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
?>

<style>
.export-wrap { 
    padding: 20px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
    margin-top: -30px;
}

.export-header {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: white;
    padding: 20px 24px;
    border-radius: 16px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.header-title h3 {
    margin: 0 0 5px 0;
    font-size: 1.5rem;
}

.header-title p {
    margin: 0;
    color: #94a3b8;
    font-size: 0.875rem;
}

.total-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    display: inline-block;
    margin-top: 8px;
}

.export-filter {
    background: white;
    padding: 20px;
    border-radius: 16px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
}

.export-filter input {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.875rem;
}

.btn-filter, .btn-reset {
    background: #3b82f6;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    font-size: 0.875rem;
}

.btn-filter:hover, .btn-reset:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-export {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
}

.btn-export:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.btn-reset {
    background: #64748b;
}

.export-table-wrap {
    background: white;
    border-radius: 16px;
    overflow: auto;
    height: calc(75vh - 260px);
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.export-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.export-table th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #1e293b;
    padding: 12px;
    white-space: nowrap;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
}

.export-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    white-space: nowrap;
}

.badge-status {
    background: #e2e8f0;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.amount-cell {
    font-weight: 600;
    color: #059669;
}

.empty-message {
    text-align: center;
    padding: 60px;
    color: #94a3b8;
}

.error-message {
    background: #fee2e2;
    border-left: 4px solid #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: #991b1b;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading::before {
    content: '';
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #e2e8f0;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
    vertical-align: middle;
}
</style>

<div class="export-wrap">
    <div class="export-header">
        <div class="header-title">
            <h3><i class="fa fa-file-export"></i> Call Report Export</h3>
            <p>View and export call transaction records</p>
            <div class="total-badge" id="totalBadge">
                <i class="fa fa-database"></i> Loading...
            </div>
        </div>
        
        <button onclick="exportReport()" class="btn-export">
            <i class="fa fa-file-excel"></i> Download CSV Report
        </button>
    </div>
    
    <div class="export-filter">
        <div class="filter-group">
            <label>📅 From Date</label>
            <input type="date" name="date_from" id="dateFrom" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="filter-group">
            <label>📅 To Date</label>
            <input type="date" name="date_to" id="dateTo" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <button onclick="loadData()" class="btn-filter">
            <i class="fa fa-search"></i> Apply Filter
        </button>
        <button onclick="resetFilters()" class="btn-reset">
            <i class="fa fa-refresh"></i> Reset
        </button>
    </div>
    
    <div class="export-table-wrap">
        <table class="export-table" id="dataTable">
            <thead>
                <tr>
                    <th>BRANCH</th>
                    <th>PRINCIPAL</th>
                    <th>CALL ID</th>
                    <th>CALL DATE</th>
                    <th>CALL DURATION</th>
                    <th>TELE CALLER</th>
                    <th>CU ID</th>
                    <th>CUSTOMER</th>
                    <th>PHONE NUMBER</th>
                    <th>ADDRESS</th>
                    <th>SELLER ID</th>
                    <th>SELLER</th>
                    <th>INVOICE NUMBER</th>
                    <th>AMOUNT</th>
                    <th>DATE INVOICED</th>
                    <th>STORE NAME ACCURACY</th>
                    <th>CORRECTED STORE NAME</th>
                    <th>CORRECTED ADDRESS</th>
                    <th>IS PHONE NUMBER CORRECT</th>
                    <th>STORE VISIT</th>
                    <th>PROD CALL</th>
                    <th>AMOUNT VERIFICATION</th>
                    <th>AMOUNT RESULT</th>
                    <th>STATUS</th>
                    <th>DIAL RESULT</th>
                    <th>CREATED AT</th>
                    <th>UPDATED AT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="27" class="loading">Loading data...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Function to export report
function exportReport() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    if (!dateFrom || !dateTo) {
        showError('Please select both from and to dates');
        return;
    }
    
    // Redirect to download
    window.location.href = `/TBC/page/export-reports-data.php?export=1&date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`;
}

// Function to load data via AJAX
async function loadData() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    if (!dateFrom || !dateTo) {
        showError('Please select both from and to dates');
        return;
    }
    
    // Show loading state
    const tbody = document.querySelector('#dataTable tbody');
    tbody.innerHTML = '<tr><td colspan="27" class="loading">Loading data...</td></tr>';
    
    try {
        const response = await fetch('/TBC/page/export-reports-data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            updateTable(result.data);
            updateTotal(result.total);
            
            // Update URL without reloading
            const url = new URL(window.location.href);
            url.searchParams.set('date_from', dateFrom);
            url.searchParams.set('date_to', dateTo);
            window.history.pushState({}, '', url);
        } else {
            console.error('Error loading data:', result.error);
            showError(result.error || 'Failed to load data');
            tbody.innerHTML = '<tr><td colspan="27" class="empty-message">Error loading data. Please try again.</td></tr>';
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showError('Failed to load data. Please check if the server is running.');
        tbody.innerHTML = '<tr><td colspan="27" class="empty-message">Failed to load data. Please try again.</td></tr>';
    }
}

// Function to update table with data
function updateTable(data) {
    const tbody = document.querySelector('#dataTable tbody');
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="27" class="empty-message">No records found for the selected date range</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(row => `
        <tr>
            <td>${escapeHtml(row.BRANCH || '')}</td>
            <td>${escapeHtml(row.PRINCIPAL || '')}</td>
            <td>${escapeHtml(row.CALL_ID || '')}</td>
            <td>${escapeHtml(row.CALL_DATE || '')}</td>
            <td>${escapeHtml(row.CALL_DURATION || '')}</td>
            <td>${escapeHtml(row.TELE_CALLER || '')}</td>
            <td>${escapeHtml(row.CU_ID || '')}</td>
            <td>${escapeHtml(row.CUSTOMER || '')}</td>
            <td>${escapeHtml(row.PHONE_NUMBER || '')}</td>
            <td>${escapeHtml(row.ADDRESS || '')}</td>
            <td>${escapeHtml(row.VAN_ID || '')}</td>
            <td>${escapeHtml(row.SELLER || '')}</td>
            <td>${escapeHtml(row.INVOICE_NUMBER || '')}</td>
            <td class="amount-cell">₱${formatNumber(row.AMOUNT || 0)}</td>
            <td>${escapeHtml(row.DATE_INVOICED || '')}</td>
            <td>${escapeHtml(row.STORE_NAME_ACCURACY || '')}</td>
            <td>${escapeHtml(row.CORRECTED_STORE_NAME || '')}</td>
            <td>${escapeHtml(row.CORRECTED_ADDRESS || '')}</td>
            <td>${escapeHtml(row.IS_PHONE_NUMBER_CORRECT || '')}</td>
            <td>${escapeHtml(row.STORE_VISIT || '')}</td>
            <td>${escapeHtml(row.PROD_CALL || '')}</td>
            <td>${escapeHtml(row.AMOUNT_VERIFICATION || '')}</td>
            <td>${escapeHtml(row.AMOUNT_RESULT || '')}</td>
            <td><span class="badge-status">${escapeHtml(row.STATUS || '')}</span></td>
            <td>${escapeHtml(row.DIAL_RESULT || '')}</td>
            <td>${escapeHtml(row.CREATED_AT || '')}</td>
            <td>${escapeHtml(row.UPDATED_AT || '')}</td>
        </tr>
    `).join('');
}

// Function to update total badge
function updateTotal(total) {
    const totalBadge = document.getElementById('totalBadge');
    if (totalBadge) {
        totalBadge.innerHTML = `<i class="fa fa-database"></i> Total Records: ${total.toLocaleString()}`;
    }
}

// Function to reset filters
function resetFilters() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const currentDate = `${year}-${month}-${day}`;
    
    document.getElementById('dateFrom').value = currentDate;
    document.getElementById('dateTo').value = currentDate;
    loadData();
}

// Helper function to escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Helper function to format numbers
function formatNumber(num) {
    return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}

// Function to show error message
function showError(message) {
    // Remove existing error messages
    const existingError = document.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `⚠️ ${escapeHtml(message)}`;
    
    const filterSection = document.querySelector('.export-filter');
    if (filterSection) {
        filterSection.parentNode.insertBefore(errorDiv, filterSection.nextSibling);
        
        // Remove error after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadData();
});
</script>
<!-- REPORT SECTION ENDS HERE -->