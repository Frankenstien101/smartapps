<?php
// pages/allreport.php - Comprehensive Report

// Get session variables
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
$userRole = $_SESSION['role'] ?? 'staff';

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$branch = $_GET['branch'] ?? $currentBranch;
$reportType = $_GET['report_type'] ?? 'all';

// Include database connection
include './DB/dbcon.php';

// ============================================
// FETCH REPORT DATA - SIMPLIFIED STYLE
// ============================================

// Get branches for filter
$branchesQuery = "SELECT DISTINCT Branch FROM Sales UNION SELECT DISTINCT Branch FROM Expenses UNION SELECT DISTINCT Branch FROM Deposits";
$branches = $conn->query($branchesQuery)->fetchAll(PDO::FETCH_ASSOC);

// Get Sales Data
$salesQuery = "SELECT 
                    s.SaleID,
                    s.ReceiptNo,
                    s.CustomerName,
                    s.TotalAmount,
                    s.PaymentMethod,
                    s.AmountReceived,
                    s.ChangeAmount,
                    s.SaleDate,
                    s.CreatedBy,
                    s.Branch
                FROM Sales s
                WHERE CONVERT(DATE, s.SaleDate) BETWEEN CONVERT(DATE, '$dateFrom') AND CONVERT(DATE, '$dateTo')
                AND (s.Status != 'cancelled' OR s.Status IS NULL)";

if ($branch != 'all') {
    $salesQuery .= " AND s.Branch = '$branch'";
}

$salesQuery .= " ORDER BY s.SaleDate DESC";
$sales = $conn->query($salesQuery)->fetchAll(PDO::FETCH_ASSOC);

// Get Deposits Data
$depositsQuery = "SELECT 
                    d.DepositID,
                    d.DepositRef,
                    d.DepositDate,
                    d.DepositType,
                    d.Amount,
                    d.ReferenceNo,
                    d.BankName,
                    d.Notes,
                    d.CreatedBy,
                    d.Branch
                FROM Deposits d
                WHERE CONVERT(DATE, d.DepositDate) BETWEEN CONVERT(DATE, '$dateFrom') AND CONVERT(DATE, '$dateTo')";

if ($branch != 'all') {
    $depositsQuery .= " AND d.Branch = '$branch'";
}

$depositsQuery .= " ORDER BY d.DepositDate DESC";
$deposits = $conn->query($depositsQuery)->fetchAll(PDO::FETCH_ASSOC);

// Get Expenses Data
$expensesQuery = "SELECT 
                    e.ExpenseID,
                    e.ExpenseDate,
                    e.ExpenseTypeID,
                    e.Amount as PlannedAmount,
                    e.Amount as ActualAmount,
                    e.Notes,
                    e.CreatedBy,
                    e.Branch,
                    (SELECT TypeName FROM ExpenseTypes et WHERE et.ExpenseTypeID = e.ExpenseTypeID) AS TypeName
                FROM Expenses e
                WHERE CONVERT(DATE, e.ExpenseDate) BETWEEN CONVERT(DATE, '$dateFrom') AND CONVERT(DATE, '$dateTo')
                AND (e.Status = 'active' OR e.Status IS NULL)";

if ($branch != 'all') {
    $expensesQuery .= " AND e.Branch = '$branch'";
}

$expensesQuery .= " ORDER BY e.ExpenseDate DESC";
$expenses = $conn->query($expensesQuery)->fetchAll(PDO::FETCH_ASSOC);

// Get EOD Data
$eodQuery = "SELECT 
                e.EodID,
                e.EodDate,
                ISNULL(e.TotalSales, 0) as TotalSales,
                ISNULL(e.TransactionCount, 0) as TransactionCount,
                ISNULL(e.CashReceived, 0) as CashReceived,
                ISNULL(e.CardReceived, 0) as CardReceived,
                ISNULL(e.GcashReceived, 0) as GcashReceived,
                ISNULL(e.SodAmount, 0) as SodAmount,
                ISNULL(e.TotalDeposits, 0) as TotalDeposits,
                ISNULL(e.DepositCount, 0) as DepositCount,
                ISNULL(e.TotalExpenses, 0) as TotalExpenses,
                ISNULL(e.ExpenseCount, 0) as ExpenseCount,
                ISNULL(e.ExpectedCashOnHand, 0) as ExpectedCashOnHand,
                ISNULL(e.TotalCashCounted, 0) as TotalCashCounted,
                ISNULL(e.CashDifference, 0) as CashDifference,
                e.Status,
                e.CreatedBy,
                e.Branch
            FROM EndOfDay e
            WHERE CONVERT(DATE, e.EodDate) BETWEEN CONVERT(DATE, '$dateFrom') AND CONVERT(DATE, '$dateTo')";

if ($branch != 'all') {
    $eodQuery .= " AND e.Branch = '$branch'";
}

$eodQuery .= " ORDER BY e.EodDate DESC";
$eods = $conn->query($eodQuery)->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalSales = array_sum(array_column($sales, 'TotalAmount'));
$totalDeposits = array_sum(array_column($deposits, 'Amount'));
$totalExpenses = array_sum(array_column($expenses, 'ActualAmount'));
$totalEodCount = count($eods);
?>

<!-- ============================================ -->
<!-- HTML AND STYLES SAME AS BEFORE -->
<!-- ============================================ -->
<style>
    .report-container {
        padding: 0;
        width: 100%;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
        min-width: 0;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #1a2a3a;
        word-break: break-word;
    }
    
    .stat-value.sales { color: #28a745; }
    .stat-value.deposits { color: #dc3545; }
    .stat-value.expenses { color: #ff9800; }
    .stat-value.eod { color: #4f9eff; }
    
    .stat-label {
        font-size: 12px;
        color: #6c7a91;
        margin-top: 5px;
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
    .btn-excel { background: #217346; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    
    .btn-primary:hover { background: #3b8be8; }
    .btn-excel:hover { background: #1a5c38; }
    .btn-secondary:hover { background: #5a6268; }
    
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
    }
    
    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }
    
    .table {
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
    }
    
    .table th {
        background: #f8fafc;
        padding: 10px 14px;
        font-weight: 600;
        text-align: left;
        border-bottom: 2px solid #eef2f7;
    }
    
    .table td {
        padding: 10px 14px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .table tbody tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-info { background: #dbeafe; color: #2563eb; }
    .badge-success { background: #dcfce7; color: #10b981; }
    
    .badge-status.submitted { background: #dcfce7; color: #10b981; }
    .badge-status.draft { background: #e2e8f0; color: #475569; }
    
    .text-center { text-align: center; }
    .text-muted { color: #6c7a91; }
    .mt-2 { margin-top: 10px; }
    .mb-4 { margin-bottom: 20px; }
    .gap-2 { gap: 10px; }
    
    .d-flex { display: flex; }
    .align-items-center { align-items: center; }
    .justify-content-between { justify-content: space-between; }
    .flex-wrap { flex-wrap: wrap; }
    
    .no-data {
        text-align: center;
        padding: 30px;
        color: #6c7a91;
    }
    
    .export-btn {
        background: #217346;
        color: white;
        border: none;
        padding: 4px 14px;
        border-radius: 6px;
        font-size: 11px;
        cursor: pointer;
        font-weight: 600;
    }
    
    .export-btn:hover {
        background: #1a5c38;
    }
    
    @media (max-width: 992px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
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
        .stats-row {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .stat-card {
            padding: 14px 16px;
        }
        .stat-value {
            font-size: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .stat-card {
            padding: 12px 14px;
        }
        .stat-value {
            font-size: 17px;
        }
    }
</style>

<div class="report-container">
    
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 style="margin:0;font-size:22px;"><i class="fas fa-file-alt"></i> Comprehensive Report</h4>
            <p class="text-muted mb-0" style="font-size:14px;">Sales, Deposits, Expenses &amp; EOD Summary</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-excel" onclick="exportReport('all')" style="padding:10px 22px;">
                <i class="fas fa-file-excel"></i> Export All
            </button>
        </div>
    </div>
    
    <!-- FILTERS -->
    <div class="filter-section">
        <div class="filter-group">
            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from" id="dateFrom" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to" id="dateTo" value="<?php echo $dateTo; ?>">
            </div>
            <div class="form-group">
                <label>Branch</label>
                <select name="branch" id="branchSelect">
                    <option value="all" <?php echo $branch == 'all' ? 'selected' : ''; ?>>All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?php echo $b['Branch']; ?>" <?php echo $branch == $b['Branch'] ? 'selected' : ''; ?>>
                            <?php echo $b['Branch']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Report Type</label>
                <select name="report_type" id="reportTypeSelect">
                    <option value="all" <?php echo $reportType == 'all' ? 'selected' : ''; ?>>All Reports</option>
                    <option value="sales" <?php echo $reportType == 'sales' ? 'selected' : ''; ?>>Sales Only</option>
                    <option value="deposits" <?php echo $reportType == 'deposits' ? 'selected' : ''; ?>>Deposits Only</option>
                    <option value="expenses" <?php echo $reportType == 'expenses' ? 'selected' : ''; ?>>Expenses Only</option>
                    <option value="eod" <?php echo $reportType == 'eod' ? 'selected' : ''; ?>>EOD Only</option>
                </select>
            </div>
        </div>
        <button class="btn btn-primary" onclick="applyFilters()">
            <i class="fas fa-search"></i> Filter
        </button>
        <button class="btn btn-secondary" onclick="resetFilters()">
            <i class="fas fa-undo"></i> Reset
        </button>
    </div>
    
    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value sales">₱<?php echo number_format($totalSales, 2); ?></div>
            <div class="stat-label">Total Sales</div>
        </div>
        <div class="stat-card">
            <div class="stat-value deposits">₱<?php echo number_format($totalDeposits, 2); ?></div>
            <div class="stat-label">Total Deposits</div>
        </div>
        <div class="stat-card">
            <div class="stat-value expenses">₱<?php echo number_format($totalExpenses, 2); ?></div>
            <div class="stat-label">Total Expenses</div>
        </div>
        <div class="stat-card">
            <div class="stat-value eod"><?php echo $totalEodCount; ?></div>
            <div class="stat-label">EOD Reports</div>
        </div>
    </div>
    
    <!-- SALES -->
    <?php if ($reportType == 'all' || $reportType == 'sales'): ?>
    <div class="card-custom">
        <div class="card-header">
            <span><i class="fas fa-shopping-cart"></i> Sales</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span style="font-size:13px;color:#6c7a91;"><?php echo count($sales); ?> records</span>
                <button class="export-btn" onclick="exportReport('sales')">
                    <i class="fas fa-file-excel"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Branch</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr><td colspan="7" class="no-data">No sales found</td></tr>
                        <?php else: ?>
                            <?php foreach ($sales as $row): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['SaleDate'])); ?></td>
                                <td><strong><?php echo $row['ReceiptNo']; ?></strong></td>
                                <td><?php echo $row['CustomerName']; ?></td>
                                <td style="font-weight:600;color:#28a745;">₱<?php echo number_format($row['TotalAmount'], 2); ?></td>
                                <td><span class="badge badge-info"><?php echo ucfirst($row['PaymentMethod']); ?></span></td>
                                <td><?php echo $row['Branch']; ?></td>
                                <td><?php echo $row['CreatedBy']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- DEPOSITS -->
    <?php if ($reportType == 'all' || $reportType == 'deposits'): ?>
    <div class="card-custom">
        <div class="card-header">
            <span><i class="fas fa-money-bill-wave"></i> Deposits</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span style="font-size:13px;color:#6c7a91;"><?php echo count($deposits); ?> records</span>
                <button class="export-btn" onclick="exportReport('deposits')">
                    <i class="fas fa-file-excel"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Bank</th>
                            <th>Branch</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deposits)): ?>
                            <tr><td colspan="7" class="no-data">No deposits found</td></tr>
                        <?php else: ?>
                            <?php foreach ($deposits as $row): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['DepositDate'])); ?></td>
                                <td><strong><?php echo $row['DepositRef']; ?></strong></td>
                                <td><?php echo $row['DepositType']; ?></td>
                                <td style="font-weight:600;color:#dc3545;">₱<?php echo number_format($row['Amount'], 2); ?></td>
                                <td><?php echo $row['BankName'] ?? '-'; ?></td>
                                <td><?php echo $row['Branch']; ?></td>
                                <td><?php echo $row['CreatedBy']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- EXPENSES -->
    <?php if ($reportType == 'all' || $reportType == 'expenses'): ?>
    <div class="card-custom">
        <div class="card-header">
            <span><i class="fas fa-receipt"></i> Expenses</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span style="font-size:13px;color:#6c7a91;"><?php echo count($expenses); ?> records</span>
                <button class="export-btn" onclick="exportReport('expenses')">
                    <i class="fas fa-file-excel"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Planned</th>
                            <th>Actual</th>
                            <th>Variance</th>
                            <th>Branch</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr><td colspan="7" class="no-data">No expenses found</td></tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $row): 
                                $variance = $row['PlannedAmount'] - $row['ActualAmount'];
                            ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['ExpenseDate'])); ?></td>
                                <td><?php echo $row['TypeName'] ?? 'Unknown'; ?></td>
                                <td>₱<?php echo number_format($row['PlannedAmount'], 2); ?></td>
                                <td style="font-weight:600;color:#ff9800;">₱<?php echo number_format($row['ActualAmount'], 2); ?></td>
                                <td style="color:<?php echo $variance > 0 ? '#28a745' : '#dc3545'; ?>">
                                    ₱<?php echo number_format($variance, 2); ?>
                                </td>
                                <td><?php echo $row['Branch']; ?></td>
                                <td><?php echo $row['CreatedBy']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- EOD -->
    <?php if ($reportType == 'all' || $reportType == 'eod'): ?>
    <div class="card-custom">
        <div class="card-header">
            <span><i class="fas fa-cash-register"></i> End of Day Reports</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span style="font-size:13px;color:#6c7a91;"><?php echo count($eods); ?> records</span>
                <button class="export-btn" onclick="exportReport('eod')">
                    <i class="fas fa-file-excel"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Sales</th>
                            <th>Cash Received</th>
                            <th>Deposits</th>
                            <th>Expenses</th>
                            <th>Expected Cash</th>
                            <th>Cash Counted</th>
                            <th>Difference</th>
                            <th>Status</th>
                            <th>Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eods)): ?>
                            <tr><td colspan="10" class="no-data">No EOD reports found</td></tr>
                        <?php else: ?>
                            <?php foreach ($eods as $row): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['EodDate'])); ?></td>
                                <td>₱<?php echo number_format($row['TotalSales'], 2); ?></td>
                                <td>₱<?php echo number_format($row['CashReceived'], 2); ?></td>
                                <td style="color:#dc3545;">₱<?php echo number_format($row['TotalDeposits'], 2); ?></td>
                                <td style="color:#ff9800;">₱<?php echo number_format($row['TotalExpenses'], 2); ?></td>
                                <td style="color:#4f9eff;">₱<?php echo number_format($row['ExpectedCashOnHand'], 2); ?></td>
                                <td>₱<?php echo number_format($row['TotalCashCounted'], 2); ?></td>
                                <td style="color:<?php echo $row['CashDifference'] > 0 ? '#28a745' : '#dc3545'; ?>">
                                    ₱<?php echo number_format($row['CashDifference'], 2); ?>
                                </td>
                                <td><span class="badge-status <?php echo $row['Status']; ?>"><?php echo strtoupper($row['Status']); ?></span></td>
                                <td><?php echo $row['Branch']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<script>
// ============================================
// FILTER FUNCTIONS
// ============================================
function applyFilters() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const branch = document.getElementById('branchSelect').value;
    const reportType = document.getElementById('reportTypeSelect').value;
    
    window.location.href = '?page=allreport&date_from=' + dateFrom + '&date_to=' + dateTo + '&branch=' + branch + '&report_type=' + reportType;
}

function resetFilters() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    const dateFrom = firstDay.toISOString().split('T')[0];
    const dateTo = today.toISOString().split('T')[0];
    
    window.location.href = '?page=allreport&date_from=' + dateFrom + '&date_to=' + dateTo + '&branch=all&report_type=all';
}

// ============================================
// EXPORT FUNCTION
// ============================================
function exportReport(type) {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const branch = document.getElementById('branchSelect').value;
    const reportType = document.getElementById('reportTypeSelect').value;
    
    window.open(`/dmb/datafetcher/allreportdata.php?action=exportReport&date_from=${dateFrom}&date_to=${dateTo}&branch=${branch}&export_type=${type}`);
    showToast('Export started', 'success');
}

function showToast(message, type) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-' + (type === 'success' ? 'success' : 'danger') + ' show';
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
}
</script>