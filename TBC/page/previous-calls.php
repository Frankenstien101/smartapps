<?php

// ============================================
// AJAX REQUEST - MUST BE AT TOP BEFORE OUTPUT
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_call') {
session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../DB/dbcon.php';

    if (!isset($_SESSION['username'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Not authenticated'
        ]);
        exit();
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid ID'
        ]);
        exit();
    }

    try {

        $sql = "SELECT *
                FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
                WHERE LINE_ID = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $call = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($call) {

            echo json_encode([
                'success' => true,
                'call' => $call
            ]);

        } else {

            echo json_encode([
                'success' => false,
                'message' => 'Call not found'
            ]);
        }

    } catch (Exception $e) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit();
}

// ============================================
// NORMAL PAGE
// ============================================

if (!isset($_SESSION['username'])) {
    header("Location: /TBC/login.php");
    exit();
}

require_once __DIR__ . '/../DB/dbcon.php';

// ============================================
// FILTERS - Only Date filters for server-side
// ============================================

$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$teleCaller = isset($_SESSION['NAME']) ? $_SESSION['NAME'] : '';

$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$page = max($page, 1);

$limit = 50; // Increased limit to show more records for client-side search
$offset = ($page - 1) * $limit;

// ============================================
// BUILD WHERE CLAUSE - Only date and telecaller
// ============================================

$where = [];
$params = [];

$where[] = "1=1";

if (!empty($teleCaller)) {
    $where[] = "TELE_CALLER = :teleCaller";
    $params[':teleCaller'] = $teleCaller;
}

if (!empty($dateFrom)) {
    $where[] = "CAST(CALL_DATE AS DATE) >= :dateFrom";
    $params[':dateFrom'] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "CAST(CALL_DATE AS DATE) <= :dateTo";
    $params[':dateTo'] = $dateTo;
}

$whereSql = implode(" AND ", $where);

// ============================================
// TOTAL COUNT
// ============================================

$countSql = "
SELECT COUNT(*) AS total
FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
WHERE $whereSql
";

$stmt = $conn->prepare($countSql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);

$totalRecords = $row['total'];
$totalPages = ceil($totalRecords / $limit);

// ============================================
// FETCH DATA
// ============================================

$sql = "
SELECT
    LINE_ID,
    CALL_ID,
    CALL_DATE,
    CALL_DURATION,
    TELE_CALLER,
    CU_ID,
    CUSTOMER,
    PHONE_NUMBER,
    ADDRESS,
    SELLER,
    INVOICE_NUMBER,
    AMOUNT,
    STATUS,
    BRANCH,
    PRINCIPAL,
    DIAL_RESULT,
    STORE_NAME_ACCURACY,
    CORRECTED_STORE_NAME,
    CORRECTED_ADDRESS,
    IS_PHONE_NUMBER_CORRECT,
    STORE_VISIT,
    PROD_CALL,
    AMOUNT_VERIFICATION,
    AMOUNT_RESULT
FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
WHERE $whereSql
ORDER BY CALL_DATE DESC, LINE_ID DESC
OFFSET :offset ROWS
FETCH NEXT :limit ROWS ONLY
";

$stmt = $conn->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt->execute();

$calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.calls-card{
    background:white;
    border-radius:16px;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
    overflow:hidden;
}

.calls-header{
    background:linear-gradient(135deg,#0284c8,#38bdf8);
    padding:16px 20px;
    color:white;
}

.filter-bar{
    background:#f8fafc;
    padding:15px 20px;
    border-bottom:1px solid #e2e8f0;
}

.table-calls{
    width:100%;
    border-collapse:collapse;
}

.table-calls th{
    background:#f1f5f9;
    padding:12px 15px;
    text-align:left;
    font-size:12px;
    font-weight:600;
    color:#475569;
    border-bottom:1px solid #e2e8f0;
    position:sticky;
    top:0;
    z-index:10;
}

.table-calls td{
    padding:12px 15px;
    border-bottom:1px solid #e2e8f0;
    font-size:13px;
}

.table-calls tbody tr{
    transition:all 0.2s;
}

.table-calls tbody tr:hover{
    background:#f8fafc;
    cursor:pointer;
}

/* Hidden row class for search filtering */
.table-calls tbody tr.hidden-row {
    display: none;
}

.status-badge{
    display:inline-block;
    padding:4px 12px;
    border-radius:20px;
    font-size:11px;
    font-weight:600;
}

.status-picked{
    background:#d1fae5;
    color:#065f46;
}

.status-cant{
    background:#fee2e2;
    color:#991b1b;
}

.status-wrong{
    background:#fef3c7;
    color:#92400e;
}

.status-no-answer{
    background:#e2e8f0;
    color:#475569;
}

.btn-filter{
    background:#0284c8;
    border:none;
    padding:6px 20px;
    border-radius:8px;
    color:white;
    font-size:12px;
    cursor:pointer;
}

.btn-reset{
    background:#e2e8f0;
    border:none;
    padding:6px 20px;
    border-radius:8px;
    color:#475569;
    font-size:12px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}

.btn-view{
    background:transparent;
    border:1px solid #0284c8;
    padding:4px 12px;
    border-radius:6px;
    color:#0284c8;
    font-size:11px;
    cursor:pointer;
    transition:all 0.2s;
}

.btn-view:hover{
    background:#0284c8;
    color:white;
}

.pagination{
    display:flex;
    justify-content:center;
    gap:5px;
    padding:15px;
    border-top:1px solid #e2e8f0;
}

.page-link{
    padding:5px 12px;
    border-radius:6px;
    border:1px solid #e2e8f0;
    color:#475569;
    text-decoration:none;
    font-size:13px;
    transition:all 0.2s;
}

.page-link:hover{
    background:#0284c8;
    color:white;
    border-color:#0284c8;
}

.page-link.active{
    background:#0284c8;
    color:white;
    border-color:#0284c8;
}

.empty-state{
    text-align:center;
    padding:60px;
    color:#94a3b8;
}

.search-highlight {
    background-color: #fef08a;
    padding: 0 2px;
    border-radius: 3px;
}

.search-stats {
    font-size: 12px;
    background: #f1f5f9;
    padding: 5px 12px;
    border-radius: 20px;
    color: #475569;
}

.search-stats strong {
    color: #0284c8;
}

.clear-search-btn {
    background: #e2e8f0;
    border: none;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.clear-search-btn:hover {
    background: #cbd5e1;
}

/* Search input styling */
.search-input-wrapper {
    position: relative;
}

.search-input-wrapper input {
    padding-right: 35px;
}

.search-input-wrapper .search-clear-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #94a3b8;
    font-size: 14px;
    display: none;
}

.search-input-wrapper .search-clear-icon:hover {
    color: #dc2626;
}

/* Export Button */
.export-bottom-section {
    padding: 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.btn-export-table {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.btn-export-table:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.export-info {
    font-size: 12px;
    color: #64748b;
    background: white;
    padding: 6px 12px;
    border-radius: 8px;
}

.export-info i {
    color: #22c55e;
}
</style>

<div class="calls-card">

    <div class="calls-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fa fa-clock-rotate-left"></i>
                Previous Calls
            </h5>

            <div class="d-flex gap-2 align-items-center">
                <span class="search-stats" id="searchStats">
                    <i class="fa fa-database"></i> Total: <?= number_format($totalRecords) ?> calls
                </span>
                <span class="badge bg-light text-dark" id="visibleCount">
                    Showing: <?= count($calls) ?> records
                </span>
            </div>
        </div>
    </div>

    <div class="filter-bar">

        <form method="GET" id="filterForm" class="row g-2 align-items-end">

            <input type="hidden" name="page" value="previous-calls">

            <div class="col-md-4">
                <label class="form-label small mb-0">
                    <i class="fa fa-search"></i> Quick Search (Client-side)
                </label>
                <div class="search-input-wrapper">
                    <input
                        type="text"
                        id="quickSearchInput"
                        class="form-control form-control-sm"
                        placeholder="Search by customer, phone, seller, invoice..."
                        autocomplete="off"
                    >
                    <i class="fa fa-times-circle search-clear-icon" id="clearSearchIcon"></i>
                </div>
                <small class="text-muted" style="font-size: 10px;">
                    <i class="fa fa-info-circle"></i> Searches Customer, Phone, Seller, Invoice, CU ID
                </small>
            </div>

            <div class="col-md-2">
                <label class="form-label small mb-0">Date From</label>
                <input
                    type="date"
                    name="date_from"
                    id="dateFrom"
                    class="form-control form-control-sm"
                    value="<?= htmlspecialchars($dateFrom) ?>"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label small mb-0">Date To</label>
                <input
                    type="date"
                    name="date_to"
                    id="dateTo"
                    class="form-control form-control-sm"
                    value="<?= htmlspecialchars($dateTo) ?>"
                >
            </div>

            <div class="col-md-4">
                <button type="submit" class="btn-filter">
                    <i class="fa fa-calendar-alt"></i>
                    Apply Date Filter
                </button>

                <button type="button" id="todayBtn" class="btn-reset ms-1">
                    <i class="fa fa-calendar-day"></i>
                    Today
                </button>

                <a href="?page=previous-calls" class="btn-reset ms-1">
                    <i class="fa fa-undo"></i>
                    Reset All
                </a>
            </div>

        </form>

    </div>

    <div style="overflow-x:auto; max-height: 55vh; overflow-y: auto;">

        <table class="table-calls" id="callsTable">

            <thead>
                <tr>
                    <th>DATE</th>
                    <th>CUSTOMER / CU ID</th>
                    <th>PHONE</th>
                    <th>SELLER</th>
                    <th>BRANCH</th>
                    <th>INVOICE</th>
                    <th>AMOUNT</th>
                    <th>STATUS</th>
                    <th></th>
                </tr>
            </thead>

            <tbody id="callsTableBody">

                <?php if(count($calls) > 0): ?>

                    <?php foreach($calls as $call): ?>

                    <tr class="call-row" 
                        data-call-id="<?= $call['LINE_ID'] ?>"
                        data-store-accuracy="<?= htmlspecialchars($call['STORE_NAME_ACCURACY'] ?? '') ?>"
                        data-corrected-store="<?= htmlspecialchars($call['CORRECTED_STORE_NAME'] ?? '') ?>"
                        data-corrected-address="<?= htmlspecialchars($call['CORRECTED_ADDRESS'] ?? '') ?>"
                        data-phone-correct="<?= htmlspecialchars($call['IS_PHONE_NUMBER_CORRECT'] ?? '') ?>"
                        data-store-visit="<?= htmlspecialchars($call['STORE_VISIT'] ?? '') ?>"
                        data-prod-call="<?= htmlspecialchars($call['PROD_CALL'] ?? '') ?>"
                        data-amount-verification="<?= htmlspecialchars($call['AMOUNT_VERIFICATION'] ?? '') ?>"
                        data-amount-result="<?= htmlspecialchars($call['AMOUNT_RESULT'] ?? '') ?>">

                        <td class="call-date">
                            <?= date('M d, Y', strtotime($call['CALL_DATE'])) ?>
                            <br>
                            <small class="text-muted"><?= $call['CALL_DURATION'] ?></small>
                        </td>

                        <td class="call-customer">
                            <strong class="customer-name">
                                <?= htmlspecialchars(substr($call['CUSTOMER'] ?? '-', 0, 40)) ?>
                            </strong>
                            <br>
                            <small class="text-muted cu-id"><?= $call['CU_ID'] ?? '-' ?></small>
                        </td>

                        <td class="call-phone"><?= htmlspecialchars($call['PHONE_NUMBER'] ?? '-') ?></td>

                        <td class="call-seller">
                            <?= htmlspecialchars(substr($call['SELLER'] ?? '-', 0, 25)) ?>
                        </td>

                        <td class="call-branch"><?= $call['BRANCH'] ?? '-' ?></td>

                        <td class="call-invoice"><?= htmlspecialchars($call['INVOICE_NUMBER'] ?? '-') ?></td>

                        <td class="text-end call-amount">
                            ₱ <?= number_format($call['AMOUNT'] ?? 0, 2) ?>
                        </td>

                        <td class="call-status">
                            <?php
                            $statusClass = 'status-wrong';
                            if ($call['STATUS'] == 'Picked') $statusClass = 'status-picked';
                            elseif ($call['STATUS'] == 'Cant Be Reached') $statusClass = 'status-cant';
                            elseif ($call['STATUS'] == 'No Answer') $statusClass = 'status-no-answer';
                            ?>
                            <span class="status-badge <?= $statusClass ?>">
                                <?= $call['STATUS'] ?? 'Pending' ?>
                            </span>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="btn-view view-call"
                                data-id="<?= $call['LINE_ID'] ?>"
                            >
                                <i class="fa fa-eye"></i>
                                View
                            </button>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa fa-inbox" style="font-size: 48px; color: #cbd5e1;"></i>
                            <p class="mt-2">No calls found for the selected date range.</p>
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <?php if($totalPages > 1): ?>
    <div class="pagination">
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=previous-calls&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&page_num=<?= $i ?>" 
               class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <!-- EXPORT SECTION UNDER THE TABLE -->
    <div class="export-bottom-section">
        <div class="export-info">
            <i class="fa fa-info-circle"></i> 
            <span id="exportInfoText">Exporting <?= count($calls) ?> records from current view</span>
        </div>
        <button type="button" id="exportTableBtn" class="btn-export-table">
            <i class="fa fa-file-excel"></i> Export Table Data (CSV)
        </button>
    </div>

</div>

<!-- MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fa fa-info-circle"></i>
                    Call Details
                </h5>
                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                ></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin"></i>
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script>

// ============================================
// EXPORT TABLE DATA FUNCTION (WITH VERIFICATION RESULTS)
// ============================================

function exportTableToCSV() {
    // Get all visible rows (not hidden by search)
    const visibleRows = Array.from(document.querySelectorAll('#callsTableBody tr.call-row')).filter(row => {
        return !row.classList.contains('hidden-row');
    });
    
    if (visibleRows.length === 0) {
        alert('No data to export. Please adjust your search/filter criteria.');
        return;
    }
    
    // Define CSV headers with verification columns
    const headers = [
        'DATE',
        'CUSTOMER NAME',
        'CU ID',
        'PHONE NUMBER',
        'SELLER',
        'BRANCH',
        'INVOICE NUMBER',
        'AMOUNT',
        'STATUS',
        'DURATION',
        'STORE NAME ACCURACY',
        'CORRECTED STORE NAME',
        'CORRECTED ADDRESS',
        'PHONE NUMBER CORRECT',
        'STORE VISIT',
        'PRODUCT CALL',
        'AMOUNT VERIFICATION',
        'AMOUNT RESULT'
    ];
    
    // Prepare CSV data
    const csvData = [];
    
    // Add headers
    csvData.push(headers.map(h => `"${h}"`).join(','));
    
    // Add rows with verification data
    visibleRows.forEach(row => {
        const date = row.querySelector('.call-date')?.innerText.split('\n')[0].trim() || '';
        const duration = row.querySelector('.call-date')?.querySelector('small')?.innerText.trim() || '';
        const customerName = row.querySelector('.customer-name')?.innerText.trim() || '';
        const cuId = row.querySelector('.cu-id')?.innerText.trim() || '';
        const phone = row.querySelector('.call-phone')?.innerText.trim() || '';
        const seller = row.querySelector('.call-seller')?.innerText.trim() || '';
        const branch = row.querySelector('.call-branch')?.innerText.trim() || '';
        const invoice = row.querySelector('.call-invoice')?.innerText.trim() || '';
        const amount = row.querySelector('.call-amount')?.innerText.trim().replace('₱', '').trim() || '0.00';
        const status = row.querySelector('.call-status')?.innerText.trim() || '';
        
        // Get verification data from data attributes
        const storeAccuracy = row.getAttribute('data-store-accuracy') || '';
        const correctedStoreName = row.getAttribute('data-corrected-store') || '';
        const correctedAddress = row.getAttribute('data-corrected-address') || '';
        const phoneCorrect = row.getAttribute('data-phone-correct') || '';
        const storeVisit = row.getAttribute('data-store-visit') || '';
        const prodCall = row.getAttribute('data-prod-call') || '';
        const amountVerification = row.getAttribute('data-amount-verification') || '';
        const amountResult = row.getAttribute('data-amount-result') || '';
        
        // Escape fields that might contain commas or quotes
        const escapeCSV = (field) => {
            if (field === undefined || field === null) return '';
            const stringField = String(field);
            if (stringField.includes(',') || stringField.includes('"') || stringField.includes('\n') || stringField.includes('\r')) {
                return '"' + stringField.replace(/"/g, '""') + '"';
            }
            return stringField;
        };
        
        const rowData = [
            escapeCSV(date),
            escapeCSV(customerName),
            escapeCSV(cuId),
            escapeCSV(phone),
            escapeCSV(seller),
            escapeCSV(branch),
            escapeCSV(invoice),
            escapeCSV(amount),
            escapeCSV(status),
            escapeCSV(duration),
            escapeCSV(storeAccuracy),
            escapeCSV(correctedStoreName),
            escapeCSV(correctedAddress),
            escapeCSV(phoneCorrect),
            escapeCSV(storeVisit),
            escapeCSV(prodCall),
            escapeCSV(amountVerification),
            escapeCSV(amountResult)
        ];
        
        csvData.push(rowData.join(','));
    });
    
    // Create and download CSV file
    const csvContent = csvData.join('\n');
    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    // Generate filename with current date
    const now = new Date();
    const dateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    const timeStr = `${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}${String(now.getSeconds()).padStart(2, '0')}`;
    const filename = `calls_export_${dateStr}_${timeStr}.csv`;
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// ============================================
// CLIENT-SIDE SEARCH FUNCTIONALITY
// ============================================

const searchInput = document.getElementById('quickSearchInput');
const clearIcon = document.getElementById('clearSearchIcon');
const tableBody = document.getElementById('callsTableBody');
const rows = document.querySelectorAll('#callsTableBody tr.call-row');
const visibleCountSpan = document.getElementById('visibleCount');
const searchStatsSpan = document.getElementById('searchStats');
const exportInfoText = document.getElementById('exportInfoText');

// Function to escape regex special characters
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Function to highlight text
function highlightText(text, searchTerm) {
    if (!searchTerm || searchTerm.trim() === '') return text;
    const escapedTerm = escapeRegExp(searchTerm);
    const regex = new RegExp(`(${escapedTerm})`, 'gi');
    return text.replace(regex, '<mark class="search-highlight">$1</mark>');
}

// Function to update export info text
function updateExportInfo(visibleCount) {
    if (exportInfoText) {
        if (visibleCount === 0) {
            exportInfoText.innerHTML = '<i class="fa fa-exclamation-triangle"></i> No records to export - adjust search';
        } else {
            exportInfoText.innerHTML = `<i class="fa fa-file-excel"></i> Exporting ${visibleCount} record(s) with full verification details`;
        }
    }
}

// Function to perform search on table rows
function performSearch() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;
    
    // Show/hide clear icon
    if (searchTerm !== '') {
        clearIcon.style.display = 'block';
    } else {
        clearIcon.style.display = 'none';
    }
    
    rows.forEach(row => {
        // Skip empty rows or "no data" rows
        if (!row.cells || row.cells.length === 0) return;
        
        // Get searchable text from relevant columns
        const customerText = (row.querySelector('.customer-name')?.innerText || '').toLowerCase();
        const cuIdText = (row.querySelector('.cu-id')?.innerText || '').toLowerCase();
        const phoneText = (row.querySelector('.call-phone')?.innerText || '').toLowerCase();
        const sellerText = (row.querySelector('.call-seller')?.innerText || '').toLowerCase();
        const invoiceText = (row.querySelector('.call-invoice')?.innerText || '').toLowerCase();
        const amountText = (row.querySelector('.call-amount')?.innerText || '').toLowerCase();
        const statusText = (row.querySelector('.call-status')?.innerText || '').toLowerCase();
        const dateText = (row.querySelector('.call-date')?.innerText || '').toLowerCase();
        
        // Check if any field matches the search term
        const matches = searchTerm === '' || 
            customerText.includes(searchTerm) ||
            cuIdText.includes(searchTerm) ||
            phoneText.includes(searchTerm) ||
            sellerText.includes(searchTerm) ||
            invoiceText.includes(searchTerm) ||
            amountText.includes(searchTerm) ||
            statusText.includes(searchTerm) ||
            dateText.includes(searchTerm);
        
        if (matches) {
            row.classList.remove('hidden-row');
            visibleCount++;
            
            // Apply highlighting if search term exists
            if (searchTerm !== '') {
                // Highlight customer name
                const customerNameSpan = row.querySelector('.customer-name');
                if (customerNameSpan) {
                    const originalText = customerNameSpan.innerText;
                    customerNameSpan.innerHTML = highlightText(originalText, searchTerm);
                }
                
                // Highlight CU ID
                const cuIdSpan = row.querySelector('.cu-id');
                if (cuIdSpan) {
                    const originalText = cuIdSpan.innerText;
                    cuIdSpan.innerHTML = highlightText(originalText, searchTerm);
                }
                
                // Highlight phone
                const phoneCell = row.querySelector('.call-phone');
                if (phoneCell) {
                    const originalText = phoneCell.innerText;
                    phoneCell.innerHTML = highlightText(originalText, searchTerm);
                }
                
                // Highlight seller
                const sellerCell = row.querySelector('.call-seller');
                if (sellerCell) {
                    const originalText = sellerCell.innerText;
                    sellerCell.innerHTML = highlightText(originalText, searchTerm);
                }
                
                // Highlight invoice
                const invoiceCell = row.querySelector('.call-invoice');
                if (invoiceCell) {
                    const originalText = invoiceCell.innerText;
                    invoiceCell.innerHTML = highlightText(originalText, searchTerm);
                }
            } else {
                // Restore original text without highlights
                restoreOriginalText(row);
            }
        } else {
            row.classList.add('hidden-row');
            // Restore original text for hidden rows
            restoreOriginalText(row);
        }
    });
    
    // Update visible count display
    const totalRows = rows.length;
    if (searchTerm === '') {
        visibleCountSpan.innerHTML = `Showing: ${totalRows} records`;
        searchStatsSpan.innerHTML = `<i class="fa fa-database"></i> Total: ${totalRows} calls`;
        updateExportInfo(totalRows);
    } else {
        visibleCountSpan.innerHTML = `Showing: ${visibleCount} of ${totalRows} records`;
        searchStatsSpan.innerHTML = `<i class="fa fa-search"></i> Found: ${visibleCount} matching calls`;
        updateExportInfo(visibleCount);
    }
    
    // Show empty message if no results
    const emptyRow = document.querySelector('#callsTableBody tr.empty-state-row');
    if (visibleCount === 0 && totalRows > 0) {
        if (!emptyRow) {
            const newEmptyRow = document.createElement('tr');
            newEmptyRow.className = 'empty-state-row';
            newEmptyRow.innerHTML = `
                <td colspan="9" class="empty-state">
                    <i class="fa fa-search" style="font-size: 48px; color: #cbd5e1;"></i>
                    <p class="mt-2">No matching calls found for "<strong>${escapeHtml(searchTerm)}</strong>"</p>
                    <button class="btn-reset mt-2" onclick="clearSearch()">
                        <i class="fa fa-times"></i> Clear Search
                    </button>
                </td>
            `;
            tableBody.appendChild(newEmptyRow);
        } else {
            emptyRow.style.display = '';
            const msgSpan = emptyRow.querySelector('p strong');
            if (msgSpan) msgSpan.innerText = searchTerm;
        }
    } else if (emptyRow) {
        emptyRow.remove();
    }
}

// Helper function to restore original text (remove highlights)
function restoreOriginalText(row) {
    const customerNameSpan = row.querySelector('.customer-name');
    if (customerNameSpan && customerNameSpan.innerHTML !== customerNameSpan.innerText) {
        customerNameSpan.innerHTML = customerNameSpan.innerText;
    }
    
    const cuIdSpan = row.querySelector('.cu-id');
    if (cuIdSpan && cuIdSpan.innerHTML !== cuIdSpan.innerText) {
        cuIdSpan.innerHTML = cuIdSpan.innerText;
    }
    
    const phoneCell = row.querySelector('.call-phone');
    if (phoneCell && phoneCell.innerHTML !== phoneCell.innerText) {
        phoneCell.innerHTML = phoneCell.innerText;
    }
    
    const sellerCell = row.querySelector('.call-seller');
    if (sellerCell && sellerCell.innerHTML !== sellerCell.innerText) {
        sellerCell.innerHTML = sellerCell.innerText;
    }
    
    const invoiceCell = row.querySelector('.call-invoice');
    if (invoiceCell && invoiceCell.innerHTML !== invoiceCell.innerText) {
        invoiceCell.innerHTML = invoiceCell.innerText;
    }
}

// Escape HTML helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Clear search function
function clearSearch() {
    searchInput.value = '';
    performSearch();
    searchInput.focus();
}

// Event listeners for search
if (searchInput) {
    searchInput.addEventListener('input', function() {
        performSearch();
    });
    
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Escape') {
            clearSearch();
        }
    });
}

if (clearIcon) {
    clearIcon.addEventListener('click', function() {
        clearSearch();
    });
}

// Export button event listener
document.getElementById('exportTableBtn')?.addEventListener('click', exportTableToCSV);

// TODAY BUTTON
document.getElementById('todayBtn').addEventListener('click', function () {
    let today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="date_from"]').value = today;
    document.querySelector('input[name="date_to"]').value = today;
    document.getElementById('filterForm').submit();
});

// ============================================
// VIEW DETAILS - AJAX
// ============================================
document.querySelectorAll('.view-call').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        let id = this.getAttribute('data-id');
        let modalBody = document.getElementById('modalBody');
        modalBody.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';
        
        fetch('/TBC/page/previous-calls.php?ajax=get_call&id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let c = data.call;
                    modalBody.innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Call ID:</strong><br>${c.CALL_ID || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Call Date:</strong><br>${c.CALL_DATE || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Duration:</strong><br>${c.CALL_DURATION || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Tele Caller:</strong><br>${c.TELE_CALLER || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Customer:</strong><br>${c.CUSTOMER || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>CU ID:</strong><br>${c.CU_ID || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Phone Number:</strong><br>${c.PHONE_NUMBER || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Seller:</strong><br>${c.SELLER || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Invoice Number:</strong><br>${c.INVOICE_NUMBER || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Amount:</strong><br>₱ ${parseFloat(c.AMOUNT || 0).toFixed(2)}
                            </div>
                            <div class="col-md-6">
                                <strong>Branch:</strong><br>${c.BRANCH || '-'}
                            </div>
                            <div class="col-md-6">
                                <strong>Principal:</strong><br>${c.PRINCIPAL || '-'}
                            </div>
                            <div class="col-md-12">
                                <strong>Address:</strong><br>${c.ADDRESS || '-'}
                            </div>
                        </div>
                        <hr>
                        <h5 class="mb-3"><i class="fa fa-clipboard-check text-primary"></i> Verification Results</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <strong>Store Name Accuracy</strong><br>
                                    <span class="text-primary">${c.STORE_NAME_ACCURACY || '-'}</span>
                                    ${c.CORRECTED_STORE_NAME ? `<br><small class="text-muted">Corrected: ${c.CORRECTED_STORE_NAME}</small>` : ''}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <strong>Phone Number Correct</strong><br>
                                    <span class="text-primary">${c.IS_PHONE_NUMBER_CORRECT || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <strong>Store Visit</strong><br>
                                    <span class="text-primary">${c.STORE_VISIT || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <strong>Product Call</strong><br>
                                    <span class="text-primary">${c.PROD_CALL || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <strong>Amount Verification</strong><br>
                                    <span class="text-primary">${c.AMOUNT_VERIFICATION || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <strong>Amount Result</strong><br>
                                    <span class="text-primary">${c.AMOUNT_RESULT || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="border rounded p-3 bg-light">
                                    <strong>Status</strong><br>
                                    <span class="badge bg-primary">${c.STATUS || '-'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                modalBody.innerHTML = '<div class="alert alert-danger">Error loading call details</div>';
            });
        
        new bootstrap.Modal(document.getElementById('viewModal')).show();
    });
});

// Add click handler to table rows for easy viewing
document.querySelectorAll('#callsTableBody .call-row').forEach(row => {
    row.addEventListener('click', function(e) {
        // Don't trigger if clicking the view button
        if (e.target.classList.contains('btn-view') || e.target.closest('.btn-view')) {
            return;
        }
        const viewBtn = this.querySelector('.view-call');
        if (viewBtn) {
            viewBtn.click();
        }
    });
});

// Initialize - no search on load
document.addEventListener('DOMContentLoaded', function() {
    // Store original content for each row
    rows.forEach(row => {
        if (row.cells && row.cells.length > 0) {
            // Store original text content for each searchable cell
            const customerNameSpan = row.querySelector('.customer-name');
            if (customerNameSpan) customerNameSpan.setAttribute('data-original', customerNameSpan.innerText);
            
            const cuIdSpan = row.querySelector('.cu-id');
            if (cuIdSpan) cuIdSpan.setAttribute('data-original', cuIdSpan.innerText);
            
            const phoneCell = row.querySelector('.call-phone');
            if (phoneCell) phoneCell.setAttribute('data-original', phoneCell.innerText);
            
            const sellerCell = row.querySelector('.call-seller');
            if (sellerCell) sellerCell.setAttribute('data-original', sellerCell.innerText);
            
            const invoiceCell = row.querySelector('.call-invoice');
            if (invoiceCell) invoiceCell.setAttribute('data-original', invoiceCell.innerText);
        }
    });
    
    // Initialize export info text
    updateExportInfo(rows.length);
});
</script>