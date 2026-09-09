<?php
// pages/eod.php - End of Day Report with Invoice Print

$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
?>
<style>
    .eod-container {
        padding: 0;
        width: 100%;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
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
    .stat-icon.info { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    .stat-icon.warning { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .stat-icon.orange { background: rgba(255, 152, 0, 0.15); color: #ff9800; }
    .stat-icon.teal { background: rgba(0, 150, 136, 0.15); color: #009688; }
    .stat-icon.danger { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .stat-icon.purple { background: rgba(156, 39, 176, 0.15); color: #9c27b0; }
    .stat-icon.gcash { background: rgba(0, 150, 136, 0.15); color: #009688; }
    
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #1a2a3a;
        word-break: break-word;
    }
    
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
    
    .date-group {
        flex: 1;
        min-width: 150px;
    }
    
    .date-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #4a5568;
    }
    
    .date-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
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
    
    .btn-success {
        background: #28a745;
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
    
    .btn-submit {
        background: #10b981;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-print {
        background: #6f42c1;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-print:hover {
        background: #5a32a3;
    }
    
    .card-custom {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .card-header {
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
    
    .card-body {
        padding: 20px;
    }
    
    .denomination-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
    }
    
    .denom-item {
        background: #f8fafc;
        border-radius: 10px;
        padding: 10px;
        text-align: center;
        border: 1px solid #eef2f7;
    }
    
    .denom-item input {
        width: 100%;
        padding: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        text-align: center;
        font-size: 14px;
        margin-top: 5px;
    }
    
    .denom-amount {
        font-weight: 700;
        color: #4f9eff;
        margin-top: 5px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eef2f7;
    }
    
    .summary-row.total {
        font-size: 18px;
        font-weight: 800;
        border-top: 2px solid #4f9eff;
        padding-top: 15px;
        margin-top: 10px;
        border-bottom: none;
    }
    
    .summary-row.difference {
        font-size: 16px;
        font-weight: 700;
        border-bottom: none;
        padding-top: 10px;
        background: #f8fafc;
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 5px;
    }
    
    .badge-draft { background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .badge-submitted { background: #dcfce7; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    
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
        z-index: 9999;
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
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 16px;
        max-width: 1000px;
        width: 95%;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
        padding: 16px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-body {
        padding: 20px 24px;
        overflow-y: auto;
        flex: 1;
    }
    
    .modal-footer {
        padding: 12px 24px;
        border-top: 1px solid #eef2f7;
        text-align: right;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6c7a91;
    }
    
    /* Print Styles */
    @media print {
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        #printArea {
            display: block !important;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 9999;
            padding: 30px;
            overflow-y: auto;
        }
        body * {
            visibility: hidden;
        }
        #printArea, #printArea * {
            visibility: visible;
        }
        .receipt-content {
            max-width: 320px;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }
        .stat-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        .card-custom {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        .summary-row.difference {
            background: #f5f5f5 !important;
        }
    }
    
    .print-only {
        display: none;
    }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .filter-section {
            flex-direction: column;
        }
        .date-group {
            width: 100%;
        }
        .denomination-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .modal-content {
            margin: 10px;
            max-height: 95vh;
        }
    }
</style>

<div class="eod-container">
    
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
        <div>
            <h4><i class="fas fa-cash-register"></i> End of Day Report</h4>
            <p class="text-muted mb-0">Cashier reconciliation with deposits & expenses</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-print" onclick="printEod()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn-secondary" onclick="loadHistory()">
                <i class="fas fa-history"></i> History
            </button>
        </div>
    </div>
    
    <!-- FILTERS -->
    <div class="filter-section no-print">
        <div class="date-group">
            <label>Select Date</label>
            <input type="date" id="eodDate" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="date-group">
            <label>Start of Day Amount</label>
            <input type="text" id="sodAmount" placeholder="0.00" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;" oninput="formatSodInput(this)">
        </div>
        <button class="btn-primary" onclick="saveSod()" style="background:#009688;">
            <i class="fas fa-save"></i> Save SOD
        </button>
        <button class="btn-primary" onclick="loadEod()">
            <i class="fas fa-search"></i> Load Report
        </button>
        <button class="btn-success" onclick="saveEod()">
            <i class="fas fa-save"></i> Save Draft
        </button>
        <button class="btn-submit" onclick="submitEod()">
            <i class="fas fa-check-circle"></i> Submit
        </button>
    </div>
    
    <!-- STATS CARDS -->
    <div class="stats-row no-print" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value" id="totalSales">₱0</div>
            <div class="stat-label">Total Sales</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-receipt"></i></div>
            <div class="stat-value" id="transactionCount">0</div>
            <div class="stat-label">Transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-money-bill"></i></div>
            <div class="stat-value" id="cashReceived">₱0</div>
            <div class="stat-label">Cash Received</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gcash"><i class="fas fa-mobile-alt"></i></div>
            <div class="stat-value" id="gcashReceived">₱0</div>
            <div class="stat-label">GCash Received</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-credit-card"></i></div>
            <div class="stat-value" id="cardReceived">₱0</div>
            <div class="stat-label">Card Payments</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-value" id="totalDeposits">₱0</div>
            <div class="stat-label">Total Deposits</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-receipt"></i></div>
            <div class="stat-value" id="totalExpenses">₱0</div>
            <div class="stat-label">Total Expenses</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-sun"></i></div>
            <div class="stat-value" id="sodDisplay">₱0</div>
            <div class="stat-label">Start of Day</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-calculator"></i></div>
            <div class="stat-value" id="expectedCash">₱0</div>
            <div class="stat-label">Expected Cash</div>
        </div>
    </div>
    
    <!-- MAIN CONTENT (Screen) -->
    <div class="row no-print">
        <div class="col-lg-7">
            <div class="card-custom">
                <div class="card-header">
                    <span><i class="fas fa-money-bill-wave"></i> Bill Denomination</span>
                    <span class="badge bg-primary" id="totalBills">₱0</span>
                </div>
                <div class="card-body">
                    <div class="denomination-grid" id="billsContainer"></div>
                </div>
            </div>
            
            <div class="card-custom mt-3">
                <div class="card-header">
                    <span><i class="fas fa-coins"></i> Coins Denomination</span>
                    <span class="badge bg-primary" id="totalCoins">₱0</span>
                </div>
                <div class="card-body">
                    <div class="denomination-grid" id="coinsContainer"></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="card-custom">
                <div class="card-header">
                    <span><i class="fas fa-file-invoice"></i> EOD Summary</span>
                    <span id="eodStatus" class="badge-draft">DRAFT</span>
                </div>
                <div class="card-body">
                    <div class="summary-row"><span>Total Sales</span><span id="summarySales">₱0.00</span></div>
                    <div class="summary-row"><span>Transaction Count</span><span id="summaryTransactions">0</span></div>
                    <div class="summary-row"><span>Cash Received</span><span id="summaryCash">₱0.00</span></div>
                    <div class="summary-row"><span>Card Payments</span><span id="summaryCard">₱0.00</span></div>
                    <div class="summary-row"><span>GCash Payments</span><span id="summaryGcash">₱0.00</span></div>
                    
                    <div style="background:#e0f2f1;border-radius:10px;padding:10px 14px;margin:10px 0;border:1px solid #b2dfdb;">
                        <div style="font-weight:600;font-size:13px;color:#00695c;margin-bottom:8px;"><i class="fas fa-sun"></i> Start of Day</div>
                        <div class="summary-row" style="border-bottom:1px solid #b2dfdb;padding:6px 0;"><span>SOD Amount</span><span style="font-weight:700;color:#009688;" id="summarySod">₱0.00</span></div>
                        <div class="summary-row" style="border-bottom:1px solid #b2dfdb;padding:6px 0;"><span>+ Cash Received</span><span id="summaryCashReceived">₱0.00</span></div>
                        <div class="summary-row" style="border-bottom:1px solid #b2dfdb;padding:6px 0;"><span style="color:#dc3545;">- Total Deposits</span><span style="color:#dc3545;font-weight:700;" id="summaryDeposits">₱0.00</span></div>
                        <div class="summary-row" style="padding:6px 0;border-bottom:none;"><span style="color:#dc3545;">- Total Expenses</span><span style="color:#dc3545;font-weight:700;" id="summaryExpenses">₱0.00</span></div>
                    </div>
                    
                    <div class="summary-row" style="font-weight:700;font-size:16px;border-bottom:2px solid #ff9800;padding:10px 0;">
                        <span style="color:#ff9800;">Expected Cash On Hand</span>
                        <span style="color:#ff9800;" id="summaryExpectedCash">₱0.00</span>
                    </div>
                    <div class="summary-row"><span>Total Bills</span><span id="summaryBills">₱0.00</span></div>
                    <div class="summary-row"><span>Total Coins</span><span id="summaryCoins">₱0.00</span></div>
                    <div class="summary-row" style="font-weight:700;border-bottom:2px solid #28a745;">
                        <span style="color:#28a745;">Total Cash Counted</span>
                        <span style="color:#28a745;" id="summaryCashCounted">₱0.00</span>
                    </div>
                    
                    <div class="summary-row difference"><span style="font-weight:700;">Cash Difference</span><span id="summaryDifference" style="font-weight:700;">₱0.00</span></div>
                    <div class="summary-row total"><span>Grand Total (All Payments)</span><span id="summaryGrandTotal">₱0.00</span></div>
                    
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea id="eodNotes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal-overlay" id="historyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5><i class="fas fa-history"></i> EOD History</h5>
            <button class="modal-close" onclick="closeHistory()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Sales</th>
                            <th>Cash Received</th>
                            <th>GCash</th>
                            <th>Card</th>
                            <th>Deposits</th>
                            <th>Expenses</th>
                            <th>Expected Cash</th>
                            <th>Cash Counted</th>
                            <th>Difference</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <tr><td colspan="12" class="text-center"><div class="loading-spinner"></div> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeHistory()">Close</button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- PRINT AREA - INVOICE STYLE -->
<!-- ============================================ -->
<div id="printArea" style="display:none;">
    <div class="receipt-content" style="font-family: 'Courier New', monospace; max-width: 320px; margin: 0 auto; padding: 15px; background: white; font-size: 12px;">
        
        <!-- HEADER -->
        <div style="text-align:center;padding-bottom:10px;border-bottom:1px dashed #999;margin-bottom:10px;">
            <h3 style="margin:0;font-size:18px;font-weight:bold;"><?php echo $currentBranch; ?></h3>
            <p style="margin:2px 0;font-size:11px;color:#666;">End of Day Report</p>
            <p style="margin:2px 0;font-size:10px;color:#666;" id="printDate"></p>
            <p style="margin:2px 0;font-size:10px;color:#666;">Status: <span id="printStatus">DRAFT</span></p>
            <p style="margin:2px 0;font-size:10px;color:#666;">Cashier: <?php echo $currentUser; ?></p>
        </div>
        
        <!-- STATS -->
        <div style="margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Total Sales</span>
                <span style="font-weight:bold;" id="printTotalSales">₱0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Transactions</span>
                <span style="font-weight:bold;" id="printTransactionCount">0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Cash Received</span>
                <span style="font-weight:bold;" id="printCashReceived">₱0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>GCash Received</span>
                <span style="font-weight:bold;color:#009688;" id="printGcashReceived">₱0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Card Payments</span>
                <span style="font-weight:bold;color:#9c27b0;" id="printCardReceived">₱0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Deposits</span>
                <span style="font-weight:bold;color:#dc3545;" id="printTotalDeposits">₱0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Expenses</span>
                <span style="font-weight:bold;color:#dc3545;" id="printTotalExpenses">₱0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Expected Cash</span>
                <span style="font-weight:bold;color:#ff9800;" id="printExpectedCash">₱0</span>
            </div>
        </div>
        
        <div style="text-align:center;letter-spacing:2px;color:#999;font-size:11px;padding:4px 0;">- - - - - - - - - - - - - -</div>
        
        <!-- BILLS -->
        <div style="margin:5px 0;">
            <div style="font-weight:bold;font-size:11px;margin-bottom:3px;">Bills</div>
            <table style="width:100%;border-collapse:collapse;font-size:10px;">
                <tbody id="printBillsTable"></tbody>
            </table>
            <div style="display:flex;justify-content:space-between;padding:3px 0;font-weight:bold;border-top:1px dashed #ccc;margin-top:3px;">
                <span>Total Bills</span>
                <span id="printTotalBills">₱0</span>
            </div>
        </div>
        
        <div style="text-align:center;letter-spacing:2px;color:#999;font-size:11px;padding:4px 0;">- - - - - - - - - - - - - -</div>
        
        <!-- COINS -->
        <div style="margin:5px 0;">
            <div style="font-weight:bold;font-size:11px;margin-bottom:3px;">Coins</div>
            <table style="width:100%;border-collapse:collapse;font-size:10px;">
                <tbody id="printCoinsTable"></tbody>
            </table>
            <div style="display:flex;justify-content:space-between;padding:3px 0;font-weight:bold;border-top:1px dashed #ccc;margin-top:3px;">
                <span>Total Coins</span>
                <span id="printTotalCoins">₱0</span>
            </div>
        </div>
        
        <div style="text-align:center;letter-spacing:2px;color:#999;font-size:11px;padding:4px 0;">- - - - - - - - - - - - - -</div>
        
        <!-- SUMMARY -->
        <div style="margin:5px 0;">
            <div style="font-weight:bold;font-size:11px;margin-bottom:3px;">Summary</div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Total Sales</span>
                <span id="printSummarySales">₱0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Transactions</span>
                <span id="printSummaryTransactions">0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Cash Received</span>
                <span id="printSummaryCash">₱0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Card Payments</span>
                <span id="printSummaryCard">₱0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>GCash Payments</span>
                <span id="printSummaryGcash">₱0.00</span>
            </div>
            
            <!-- SOD -->
            <div style="background:#e0f2f1;padding:8px;margin:8px 0;border-radius:4px;border:1px solid #b2dfdb;">
                <div style="font-weight:bold;font-size:10px;color:#00695c;margin-bottom:3px;">START OF DAY</div>
                <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:10px;border-bottom:1px dotted #b2dfdb;">
                    <span>SOD Amount</span>
                    <span style="font-weight:bold;color:#009688;" id="printSummarySod">₱0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:10px;border-bottom:1px dotted #b2dfdb;">
                    <span>+ Cash Received</span>
                    <span id="printSummaryCashReceived">₱0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:10px;border-bottom:1px dotted #b2dfdb;">
                    <span style="color:#dc3545;">- Deposits</span>
                    <span style="color:#dc3545;font-weight:bold;" id="printSummaryDeposits">₱0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:10px;">
                    <span style="color:#dc3545;">- Expenses</span>
                    <span style="color:#dc3545;font-weight:bold;" id="printSummaryExpenses">₱0.00</span>
                </div>
            </div>
            
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px;font-weight:bold;border-bottom:2px solid #ff9800;color:#ff9800;">
                <span>Expected Cash</span>
                <span id="printSummaryExpectedCash">₱0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Total Bills</span>
                <span id="printSummaryBills">₱0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px dotted #eee;">
                <span>Total Coins</span>
                <span id="printSummaryCoins">₱0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:12px;font-weight:bold;border-bottom:2px solid #28a745;color:#28a745;">
                <span>Cash Counted</span>
                <span id="printSummaryCashCounted">₱0.00</span>
            </div>
            
            <div style="display:flex;justify-content:space-between;padding:8px 10px;background:#f8fafc;border-radius:4px;margin:8px 0;font-weight:bold;font-size:13px;">
                <span>Cash Difference</span>
                <span id="printSummaryDifference">₱0.00</span>
            </div>
            
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:2px solid #4f9eff;margin-top:5px;font-size:14px;font-weight:bold;">
                <span>GRAND TOTAL</span>
                <span style="color:#4f9eff;" id="printSummaryGrandTotal">₱0.00</span>
            </div>
        </div>
        
        <div style="text-align:center;letter-spacing:2px;color:#999;font-size:11px;padding:4px 0;">- - - - - - - - - - - - - -</div>
        
        <!-- NOTES -->
        <div style="margin:5px 0;">
            <div style="font-weight:bold;font-size:10px;">Notes:</div>
            <div id="printNotes" style="padding:5px;background:#f8fafc;border-radius:4px;min-height:20px;font-size:10px;border:1px solid #eee;"></div>
        </div>
        
        <div style="text-align:center;letter-spacing:2px;color:#999;font-size:11px;padding:4px 0;">- - - - - - - - - - - - - -</div>
        
        <!-- FOOTER -->
        <div style="text-align:center;font-size:9px;color:#999;margin-top:8px;padding-top:8px;border-top:1px dashed #ccc;">
            <p style="margin:2px 0;">Printed on: <span id="printTimestamp"></span></p>
            <p style="margin:2px 0;">Thank you!</p>
            <p style="margin:2px 0;">This is a computer-generated report</p>
        </div>
        
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ============================================
// CONFIGURATION
// ============================================
const API_URL = '/dmb/datafetcher/endofdaydata.php';
const billDenominations = [1000, 500, 200, 100, 50, 20];
const coinDenominations = [10, 5, 1, 0.25, 0.10, 0.05];

let currentEodId = null;
let currentDate = '';
let eodData = {};
let sodAmount = 0;
let totalDeposits = 0;
let depositCount = 0;
let totalExpenses = 0;
let expenseCount = 0;

let paymentBreakdown = { cash: 0, card: 0, gcash: 0 };

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatNumber(num) {
    if (num === undefined || num === null || isNaN(num)) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function parseCurrency(value) {
    if (typeof value !== 'string') return parseFloat(value) || 0;
    const normalized = value.replace(/,/g, '').trim();
    return normalized === '' ? 0 : parseFloat(normalized) || 0;
}

function formatSodInput(input) {
    const raw = input.value.replace(/[^0-9.,]/g, '');
    const parts = raw.split('.');
    let integerPart = parts[0].replace(/,/g, '');
    integerPart = integerPart.replace(/^0+(?=\d)/, '');
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (parts.length > 1) {
        const decimalPart = parts[1].slice(0, 2);
        input.value = `${integerPart}.${decimalPart}`;
    } else {
        input.value = integerPart;
    }
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
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

function closeHistory() {
    const modal = document.getElementById('historyModal');
    if (modal) modal.classList.remove('active');
}

function openHistory() {
    const modal = document.getElementById('historyModal');
    if (modal) modal.classList.add('active');
}

// ============================================
// API CALLS
// ============================================
async function apiCall(action, method = 'GET', data = null) {
    try {
        const options = { method, headers: { 'Content-Type': 'application/json' } };
        if (data) options.body = JSON.stringify(data);
        const response = await fetch(`${API_URL}?action=${action}`, options);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        if (action !== 'getSodAmount') {
            showToast('Network error. Please check your connection.', 'error');
        }
        return { success: false, error: error.message };
    }
}

// ============================================
// SOD FUNCTIONS
// ============================================
async function loadSod() {
    const date = document.getElementById('eodDate').value;
    
    try {
        const result = await apiCall(`getSodAmount&date=${date}`);
        
        if (result.success && result.data && result.data.has_sod) {
            sodAmount = result.data.sod.SodAmount || 0;
            document.getElementById('sodAmount').value = sodAmount;
        } else {
            sodAmount = 0;
            document.getElementById('sodAmount').value = '';
        }
        document.getElementById('sodDisplay').innerHTML = '₱' + formatNumber(sodAmount);
    } catch (error) {
        console.error('loadSod error:', error);
        sodAmount = 0;
        document.getElementById('sodAmount').value = '';
        document.getElementById('sodDisplay').innerHTML = '₱0.00';
    }
}

async function saveSod() {
    const date = document.getElementById('eodDate').value;
    const amount = document.getElementById('sodAmount').value;
    const sodValue = parseCurrency(amount);
    if (sodValue < 0) {
        showToast('Please enter a valid SOD amount', 'error');
        return;
    }
    const result = await apiCall('saveSod', 'POST', {
        date: date,
        sod_amount: sodValue,
        notes: 'SOD entry'
    });
    if (result.success) {
        sodAmount = parseFloat(amount);
        showToast('Start of Day saved successfully!', 'success');
        updateSummary();
    } else {
        showToast(result.message || 'Failed to save SOD', 'error');
    }
}

// ============================================
// RENDER DENOMINATIONS
// ============================================
function renderDenominations(containerId, denominations, type) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    let total = 0;
    
    denominations.forEach(denom => {
        const key = `${type}_${denom}`;
        const value = eodData[key] || 0;
        const amount = denom * value;
        total += amount;
        
        const div = document.createElement('div');
        div.className = 'denom-item';
        div.innerHTML = `
            <div class="denom-label">₱${denom}</div>
            <input type="number" min="0" step="1" value="${value}" 
                   data-type="${type}" data-denom="${denom}"
                   onchange="updateDenomination(this)">
            <div class="denom-amount">₱${formatNumber(amount)}</div>
        `;
        container.appendChild(div);
    });
    
    const totalId = type === 'bill' ? 'totalBills' : 'totalCoins';
    document.getElementById(totalId).textContent = `₱${formatNumber(total)}`;
    
    if (type === 'bill') {
        eodData._totalBills = total;
    } else {
        eodData._totalCoins = total;
    }
    eodData._totalCashCounted = (eodData._totalBills || 0) + (eodData._totalCoins || 0);
    
    return total;
}

function updateDenomination(input) {
    const type = input.dataset.type;
    const denom = parseFloat(input.dataset.denom);
    const value = parseInt(input.value) || 0;
    const key = `${type}_${denom}`;
    eodData[key] = value;
    const parent = input.closest('.denom-item');
    const amountDisplay = parent.querySelector('.denom-amount');
    amountDisplay.textContent = `₱${formatNumber(denom * value)}`;
    calculateTotals();
    updateSummary();
}

function calculateTotals() {
    let totalBills = 0, totalCoins = 0;
    billDenominations.forEach(d => { const val = eodData[`bill_${d}`] || 0; totalBills += d * val; });
    coinDenominations.forEach(d => { const val = eodData[`coin_${d}`] || 0; totalCoins += d * val; });
    const totalCashCounted = totalBills + totalCoins;
    document.getElementById('totalBills').textContent = `₱${formatNumber(totalBills)}`;
    document.getElementById('totalCoins').textContent = `₱${formatNumber(totalCoins)}`;
    eodData._totalBills = totalBills;
    eodData._totalCoins = totalCoins;
    eodData._totalCashCounted = totalCashCounted;
    return { totalBills, totalCoins, totalCashCounted };
}

function updateSummary() {
    // Get values directly from the data, not from DOM
    const sales = parseFloat(document.getElementById('totalSales').textContent.replace(/[₱,]/g, '')) || 0;
    const transactions = parseInt(document.getElementById('transactionCount').textContent) || 0;
    const cashReceived = parseFloat(document.getElementById('cashReceived').textContent.replace(/[₱,]/g, '')) || 0;
    const gcashReceived = parseFloat(document.getElementById('gcashReceived').textContent.replace(/[₱,]/g, '')) || 0;
    const cardReceived = parseFloat(document.getElementById('cardReceived').textContent.replace(/[₱,]/g, '')) || 0;
    const totalReceived = cashReceived + cardReceived + gcashReceived;
    const cashCounted = eodData._totalCashCounted || 0;
    const bills = eodData._totalBills || 0;
    const coins = eodData._totalCoins || 0;
    const sod = parseCurrency(document.getElementById('sodAmount').value) || sodAmount || 0;
    
    // Use the global variables directly
    const deposits = totalDeposits;
    const expenses = totalExpenses;
    
    const expectedCashOnHand = sod + cashReceived - deposits - expenses;
    const cashDifference = cashCounted - expectedCashOnHand;
    
    // Update all displays
    document.getElementById('sodDisplay').innerHTML = '₱' + formatNumber(sod);
    document.getElementById('expectedCash').innerHTML = '₱' + formatNumber(expectedCashOnHand);
    document.getElementById('totalDeposits').innerHTML = '₱' + formatNumber(deposits);
    document.getElementById('totalExpenses').innerHTML = '₱' + formatNumber(expenses);
    
    document.getElementById('summarySales').textContent = `₱${formatNumber(sales)}`;
    document.getElementById('summaryTransactions').textContent = transactions;
    document.getElementById('summaryCash').textContent = `₱${formatNumber(cashReceived)}`;
    document.getElementById('summaryCard').textContent = `₱${formatNumber(cardReceived)}`;
    document.getElementById('summaryGcash').textContent = `₱${formatNumber(gcashReceived)}`;
    document.getElementById('summarySod').textContent = `₱${formatNumber(sod)}`;
    document.getElementById('summaryCashReceived').textContent = `₱${formatNumber(cashReceived)}`;
    document.getElementById('summaryDeposits').textContent = `₱${formatNumber(deposits)}`;
    document.getElementById('summaryExpenses').textContent = `₱${formatNumber(expenses)}`;
    document.getElementById('summaryExpectedCash').textContent = `₱${formatNumber(expectedCashOnHand)}`;
    document.getElementById('summaryBills').textContent = `₱${formatNumber(bills)}`;
    document.getElementById('summaryCoins').textContent = `₱${formatNumber(coins)}`;
    document.getElementById('summaryCashCounted').textContent = `₱${formatNumber(cashCounted)}`;
    document.getElementById('summaryDifference').textContent = `₱${formatNumber(cashDifference)}`;
    document.getElementById('summaryGrandTotal').textContent = `₱${formatNumber(totalReceived)}`;
    
    const diffEl = document.getElementById('summaryDifference');
    if (Math.abs(cashDifference) > 0.01) {
        diffEl.style.color = cashDifference > 0 ? '#10b981' : '#ef4444';
    } else {
        diffEl.style.color = '#1a2a3a';
    }
}

async function loadEod() {
    const date = document.getElementById('eodDate').value;
    currentDate = date;
    showToast('Loading report...', 'info');
    
    await loadSod();
    
    const salesResult = await apiCall(`getDailySales&date=${date}`);
    if (salesResult.success && salesResult.data) {
        const data = salesResult.data.summary;
        
        document.getElementById('totalSales').innerHTML = '₱' + formatNumber(data.TotalSales);
        document.getElementById('transactionCount').innerText = data.TransactionCount || 0;
        document.getElementById('cashReceived').innerHTML = '₱' + formatNumber(data.CashReceived);
        document.getElementById('gcashReceived').innerHTML = '₱' + formatNumber(data.GcashReceived || 0);
        document.getElementById('cardReceived').innerHTML = '₱' + formatNumber(data.CardReceived || 0);
        
        paymentBreakdown = { 
            cash: data.CashReceived || 0, 
            card: data.CardReceived || 0, 
            gcash: data.GcashReceived || 0 
        };
        
        totalDeposits = data.TotalDeposits || 0;
        depositCount = data.DepositCount || 0;
        totalExpenses = data.TotalExpenses || 0;
        expenseCount = data.ExpenseCount || 0;
        
        document.getElementById('totalDeposits').innerHTML = '₱' + formatNumber(totalDeposits);
        document.getElementById('totalExpenses').innerHTML = '₱' + formatNumber(totalExpenses);
        
        if (data.SodAmount !== undefined) {
            sodAmount = data.SodAmount || 0;
            document.getElementById('sodAmount').value = formatNumber(sodAmount);
            document.getElementById('sodDisplay').innerHTML = '₱' + formatNumber(sodAmount);
        }
        if (data.ExpectedCashOnHand !== undefined) {
            document.getElementById('expectedCash').innerHTML = '₱' + formatNumber(data.ExpectedCashOnHand);
        }
    }
    
    eodData = {};
    
    const eodResult = await apiCall(`getEodReport&date=${date}`);
    if (eodResult.success && eodResult.data) {
        const data = eodResult.data;
        
        // OVERRIDE with EOD data
        if (data.expenses) {
            totalExpenses = data.expenses.TotalExpenses || 0;
            expenseCount = data.expenses.ExpenseCount || 0;
        }
        
        if (data.deposits) {
            totalDeposits = data.deposits.TotalDeposits || 0;
            depositCount = data.deposits.DepositCount || 0;
        }
        
        if (data.has_eod && data.eod) {
            currentEodId = data.eod.EodID;
            const status = data.eod.Status || 'draft';
            const statusEl = document.getElementById('eodStatus');
            statusEl.className = status === 'submitted' ? 'badge-submitted' : 'badge-draft';
            statusEl.textContent = status === 'submitted' ? 'SUBMITTED' : 'DRAFT';
            
            if (data.eod.Bills && Array.isArray(data.eod.Bills)) {
                data.eod.Bills.forEach(bill => {
                    if (bill.denomination && bill.quantity !== undefined) {
                        eodData[`bill_${bill.denomination}`] = bill.quantity;
                    }
                });
            }
            
            if (data.eod.Coins && Array.isArray(data.eod.Coins)) {
                data.eod.Coins.forEach(coin => {
                    if (coin.denomination && coin.quantity !== undefined) {
                        eodData[`coin_${coin.denomination}`] = coin.quantity;
                    }
                });
            }
            
            document.getElementById('eodNotes').value = data.eod.Notes || '';
            if (data.eod.SodAmount !== undefined && data.eod.SodAmount > 0) {
                sodAmount = data.eod.SodAmount;
                document.getElementById('sodAmount').value = sodAmount;
            }
        } else {
            currentEodId = null;
            document.getElementById('eodStatus').className = 'badge-draft';
            document.getElementById('eodStatus').textContent = 'DRAFT';
            document.getElementById('eodNotes').value = '';
        }
    }
    
    renderDenominations('billsContainer', billDenominations, 'bill');
    renderDenominations('coinsContainer', coinDenominations, 'coin');
    calculateTotals();
    updateSummary();
    showToast('Report loaded successfully', 'success');
}
// ============================================
// SAVE EOD
// ============================================
async function saveEod() {
    const date = document.getElementById('eodDate').value;
    const notes = document.getElementById('eodNotes').value;
    const sod = parseCurrency(document.getElementById('sodAmount').value) || sodAmount || 0;
    
    const denomData = {};
    document.querySelectorAll('#billsContainer .denom-item input, #coinsContainer .denom-item input').forEach(input => {
        const type = input.dataset.type;
        const denom = parseFloat(input.dataset.denom);
        const value = parseInt(input.value) || 0;
        if (value > 0) {
            denomData[`${type}_${denom}`] = value;
        }
    });
    
    const payload = {
        date: date,
        eod_id: currentEodId,
        denominations: denomData,
        notes: notes,
        payment_breakdown: {
            cash: paymentBreakdown.cash || 0,
            card: paymentBreakdown.card || 0,
            gcash: paymentBreakdown.gcash || 0
        },
        sod_amount: sod,
        total_deposits: totalDeposits,
        deposit_count: depositCount,
        total_expenses: totalExpenses,
        expense_count: expenseCount
    };
    
    const result = await apiCall('saveEod', 'POST', payload);
    if (result.success) {
        currentEodId = result.eod_id;
        showToast('Draft saved successfully!', 'success');
        document.getElementById('eodStatus').className = 'badge-draft';
        document.getElementById('eodStatus').textContent = 'DRAFT';
    } else {
        showToast(result.message || 'Failed to save draft', 'error');
    }
}

// ============================================
// SUBMIT EOD
// ============================================
async function submitEod() {
    if (!confirm('Are you sure you want to submit this EOD report? This cannot be undone.')) return;
    
    const date = document.getElementById('eodDate').value;
    const notes = document.getElementById('eodNotes').value;
    const sod = parseCurrency(document.getElementById('sodAmount').value) || sodAmount || 0;
    
    const denomData = {};
    document.querySelectorAll('#billsContainer .denom-item input, #coinsContainer .denom-item input').forEach(input => {
        const type = input.dataset.type;
        const denom = parseFloat(input.dataset.denom);
        const value = parseInt(input.value) || 0;
        if (value > 0) {
            denomData[`${type}_${denom}`] = value;
        }
    });
    
    const payload = {
        date: date,
        eod_id: currentEodId,
        denominations: denomData,
        notes: notes,
        payment_breakdown: {
            cash: paymentBreakdown.cash || 0,
            card: paymentBreakdown.card || 0,
            gcash: paymentBreakdown.gcash || 0
        },
        sod_amount: sod,
        total_deposits: totalDeposits,
        deposit_count: depositCount,
        total_expenses: totalExpenses,
        expense_count: expenseCount
    };
    
    const result = await apiCall('submitEod', 'POST', payload);
    if (result.success) {
        currentEodId = result.eod_id;
        showToast('EOD submitted successfully!', 'success');
        document.getElementById('eodStatus').className = 'badge-submitted';
        document.getElementById('eodStatus').textContent = 'SUBMITTED';
    } else {
        showToast(result.message || 'Failed to submit EOD', 'error');
    }
}

// ============================================
// LOAD HISTORY
// ============================================
async function loadHistory() {
    openHistory();
    document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="12" class="text-center"><div class="loading-spinner"></div> Loading...</td></tr>';
    const result = await apiCall('getHistory');
    const tbody = document.getElementById('historyTableBody');
    if (result.success && result.data && result.data.length > 0) {
        tbody.innerHTML = result.data.map(row => {
            const cashDiff = (row.CashCounted || 0) - (row.ExpectedCashOnHand || 0);
            return `<tr>
                <td>${row.ReportDate}</td>
                <td>₱${formatNumber(row.TotalSales || 0)}</td>
                <td>₱${formatNumber(row.CashReceived || 0)}</td>
                <td>₱${formatNumber(row.GcashReceived || 0)}</td>
                <td>₱${formatNumber(row.CardReceived || 0)}</td>
                <td>₱${formatNumber(row.TotalDeposits || 0)}</td>
                <td>₱${formatNumber(row.TotalExpenses || 0)}</td>
                <td>₱${formatNumber(row.ExpectedCashOnHand || 0)}</td>
                <td>₱${formatNumber(row.CashCounted || 0)}</td>
                <td style="color:${cashDiff > 0 ? '#10b981' : cashDiff < 0 ? '#ef4444' : '#1a2a3a'}">₱${formatNumber(cashDiff)}</td>
                <td><span class="${row.Status === 'submitted' ? 'badge-submitted' : 'badge-draft'}">${row.Status.toUpperCase()}</span></td>
                <td><button class="btn-primary" style="padding:4px 12px;font-size:12px;" onclick="loadHistoryEod('${row.ReportDate}')"><i class="fas fa-eye"></i> View</button></td>
            </tr>`;
        }).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center">No history found</td></tr>';
    }
}

async function loadHistoryEod(date) {
    document.getElementById('eodDate').value = date;
    closeHistory();
    await loadEod();
}

// ============================================
// PRINT FUNCTION
// ============================================
function printEod() {
    updatePrintArea();
    window.print();
}

function updatePrintArea() {
    const date = document.getElementById('eodDate').value;
    const dateObj = new Date(date + 'T00:00:00');
    const formattedDate = dateObj.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const status = document.getElementById('eodStatus').textContent;
    const notes = document.getElementById('eodNotes').value;
    
    document.getElementById('printDate').textContent = formattedDate;
    document.getElementById('printStatus').textContent = status;
    
    document.getElementById('printTotalSales').textContent = document.getElementById('totalSales').textContent;
    document.getElementById('printTransactionCount').textContent = document.getElementById('transactionCount').textContent;
    document.getElementById('printCashReceived').textContent = document.getElementById('cashReceived').textContent;
    document.getElementById('printGcashReceived').textContent = document.getElementById('gcashReceived').textContent;
    document.getElementById('printCardReceived').textContent = document.getElementById('cardReceived').textContent;
    document.getElementById('printTotalDeposits').textContent = document.getElementById('totalDeposits').textContent;
    document.getElementById('printTotalExpenses').textContent = document.getElementById('totalExpenses').textContent;
    document.getElementById('printExpectedCash').textContent = document.getElementById('expectedCash').textContent;
    
    document.getElementById('printSummarySales').textContent = document.getElementById('summarySales').textContent;
    document.getElementById('printSummaryTransactions').textContent = document.getElementById('summaryTransactions').textContent;
    document.getElementById('printSummaryCash').textContent = document.getElementById('summaryCash').textContent;
    document.getElementById('printSummaryCard').textContent = document.getElementById('summaryCard').textContent;
    document.getElementById('printSummaryGcash').textContent = document.getElementById('summaryGcash').textContent;
    document.getElementById('printSummarySod').textContent = document.getElementById('summarySod').textContent;
    document.getElementById('printSummaryCashReceived').textContent = document.getElementById('summaryCashReceived').textContent;
    document.getElementById('printSummaryDeposits').textContent = document.getElementById('summaryDeposits').textContent;
    document.getElementById('printSummaryExpenses').textContent = document.getElementById('summaryExpenses').textContent;
    document.getElementById('printSummaryExpectedCash').textContent = document.getElementById('summaryExpectedCash').textContent;
    document.getElementById('printSummaryBills').textContent = document.getElementById('summaryBills').textContent;
    document.getElementById('printSummaryCoins').textContent = document.getElementById('summaryCoins').textContent;
    document.getElementById('printSummaryCashCounted').textContent = document.getElementById('summaryCashCounted').textContent;
    document.getElementById('printSummaryDifference').textContent = document.getElementById('summaryDifference').textContent;
    document.getElementById('printSummaryGrandTotal').textContent = document.getElementById('summaryGrandTotal').textContent;
    
    const diffColor = document.getElementById('summaryDifference').style.color;
    document.getElementById('printSummaryDifference').style.color = diffColor || '#1a2a3a';
    
    const billsTable = document.getElementById('printBillsTable');
    billsTable.innerHTML = '';
    let totalBills = 0;
    billDenominations.forEach(denom => {
        const value = eodData[`bill_${denom}`] || 0;
        const amount = denom * value;
        totalBills += amount;
        if (value > 0) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="padding:2px 4px;text-align:left;">₱${denom}</td>
                <td style="padding:2px 4px;text-align:center;">${value}</td>
                <td style="padding:2px 4px;text-align:right;">₱${formatNumber(amount)}</td>
            `;
            billsTable.appendChild(row);
        }
    });
    document.getElementById('printTotalBills').textContent = '₱' + formatNumber(totalBills);
    
    const coinsTable = document.getElementById('printCoinsTable');
    coinsTable.innerHTML = '';
    let totalCoins = 0;
    coinDenominations.forEach(denom => {
        const value = eodData[`coin_${denom}`] || 0;
        const amount = denom * value;
        totalCoins += amount;
        if (value > 0) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="padding:2px 4px;text-align:left;">₱${denom}</td>
                <td style="padding:2px 4px;text-align:center;">${value}</td>
                <td style="padding:2px 4px;text-align:right;">₱${formatNumber(amount)}</td>
            `;
            coinsTable.appendChild(row);
        }
    });
    document.getElementById('printTotalCoins').textContent = '₱' + formatNumber(totalCoins);
    
    document.getElementById('printNotes').textContent = notes || 'No notes';
    
    const now = new Date();
    document.getElementById('printTimestamp').textContent = now.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    loadEod();
});

document.getElementById('historyModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeHistory();
});
</script>