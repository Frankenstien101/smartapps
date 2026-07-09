<?php
// pages/new-call.php

if (!isset($_SESSION['username'])) {
    header("Location: /ploutus/login.php");
    exit();
}

// Database connection
require_once __DIR__ . '/../DB/dbcon.php';

// Fetch sellers with NULL handling
try {
    $stmt = $conn->prepare("SELECT DISTINCT 
        COALESCE(SELLER_ID, '') as SELLER_ID, 
        COALESCE(SELLER_NAME, 'Unknown') as SELLER_NAME 
    FROM [TBC].[dbo].[customers] 
    WHERE SITE = :site AND PRINCIPAL = :principal 
    AND SELLER_ID IS NOT NULL 
    ORDER BY SELLER_NAME");
    $stmt->bindValue(':site', $_SESSION['SITE'] ?? '');
    $stmt->bindValue(':principal', $_SESSION['PRINCIPAL'] ?? '');
    $stmt->execute();
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $sellers = [];
}

// Fetch all customers with NULL handling
try {
    $stmt = $conn->prepare("SELECT 
        COALESCE(CUSTOMER_ID, '') as CUSTOMER_ID,
        COALESCE(CUSTOMER_NAME, 'Unknown') as CUSTOMER_NAME,
        COALESCE(ADDRESS, '') as ADDRESS,
        COALESCE(PHONE_NUMBER, '') as PHONE_NUMBER,
        COALESCE(SELLER_ID, '') as SELLER_ID
    FROM [TBC].[dbo].[customers] 
    WHERE SITE = :site AND PRINCIPAL = :principal 
    ORDER BY CUSTOMER_NAME");
    $stmt->bindValue(':site', $_SESSION['SITE'] ?? '');
    $stmt->bindValue(':principal', $_SESSION['PRINCIPAL'] ?? '');
    $stmt->execute();
    $allCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allCustomers = [];
}
?>

<style>
    .call-questions-panel {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 16px;
        padding: 20px;
        height: 80vh;
        min-height: 550px;
        border: 1px solid #e2e8f0;
        overflow-y: auto;
    }
    
    .call-questions-panel::-webkit-scrollbar {
        width: 8px;
    }
    
    .call-questions-panel::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .call-questions-panel::-webkit-scrollbar-thumb {
        background: #cbd5e1;
    }
    
    .placeholder-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        height: 100%;
        min-height: 550px;
    }
    
    .placeholder-icon {
        font-size: 80px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
    
    /* Tab Styles */
    .script-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .tab-btn {
        background: transparent;
        border: none;
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s;
        border-radius: 8px 8px 0 0;
        position: relative;
    }
    
    .tab-btn:hover {
        color: #0284c8;
        background: #f1f5f9;
    }
    
    .tab-btn.active {
        color: #0284c8;
        background: white;
        border-bottom: 3px solid #0284c8;
    }
    
    /* Tab validation warning style */
    .tab-btn.incomplete {
        color: #dc2626;
        position: relative;
    }
    
    .tab-btn.incomplete::after {
        content: '!';
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc2626;
        color: white;
        font-size: 10px;
        font-weight: bold;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .tab-content.active-tab {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateX(10px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .question-item {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #0284c8;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.2s;
    }
    
    .question-item.incomplete-question {
        border-left-color: #dc2626;
        background: #fef2f2;
    }
    
    .question-text {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 15px;
        font-size: 15px;
    }
    
    .question-options {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .option-btn {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 8px 20px;
        border-radius: 25px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .option-btn:hover {
        background: #0284c8;
        color: white;
        border-color: #0284c8;
    }
    
    .option-btn.selected {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    
    .response-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
        margin-top: 8px;
        resize: vertical;
    }
    
    .response-input:focus {
        outline: none;
        border-color: #0284c8;
        box-shadow: 0 0 0 3px rgba(2,132,200,0.1);
    }
    
    .nav-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    
    .nav-btn {
        padding: 8px 24px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .nav-btn-prev {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
    }
    
    .nav-btn-prev:hover:not(:disabled) {
        background: #e2e8f0;
    }
    
    .nav-btn-next {
        background: linear-gradient(135deg, #0284c8, #38bdf8);
        border: none;
        color: white;
    }
    
    .nav-btn-next:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(2,132,200,0.3);
    }
    
    .nav-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .tab-progress {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin-top: 15px;
    }
    
    .progress-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #cbd5e1;
        transition: all 0.2s;
    }
    
    .progress-dot.active {
        background: #0284c8;
        width: 20px;
        border-radius: 4px;
    }
    
    .progress-dot.completed {
        background: #10b981;
    }
    
    .recording-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fee2e2;
        padding: 6px 14px;
        border-radius: 25px;
        font-size: 11px;
        color: #dc2626;
        font-weight: 500;
    }
    
    .recording-dot {
        width: 10px;
        height: 10px;
        background: #dc2626;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    .call-header-info {
        background: white;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        font-size: 12px;
    }
    
    .call-header-info span {
        font-weight: 600;
        color: #0284c8;
    }

    /* Result Modal Styles */
    .result-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .result-label {
        font-weight: 600;
        color: #0f172a;
        font-size: 13px;
    }
    
    .result-value {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .result-value.correct {
        background: #d1fae5;
        color: #065f46;
    }
    
    .result-value.incorrect {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .result-value.yes {
        background: #d1fae5;
        color: #065f46;
    }
    
    .result-value.no {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .result-value.matched {
        background: #d1fae5;
        color: #065f46;
    }
    
    .result-value.not-matched {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .result-value.na {
        background: #fef3c7;
        color: #92400e;
    }
    
    .call-duration-box {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        margin: 15px 0;
    }
    
    .call-duration-box span {
        font-size: 24px;
        font-weight: 700;
        font-family: monospace;
        color: #0284c8;
    }
    
    /* Customer Table Styles */
    .customer-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    
    .customer-table th,
    .customer-table td {
        padding: 10px 8px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .customer-table th {
        background: #f1f5f9;
        font-weight: 600;
        position: sticky;
        top: 0;
    }
    
    .customer-table tr {
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .customer-table tr:hover {
        background: #e2e8f0;
    }
    
    .customer-table tr.selected {
        background: #0284c8;
        color: white;
    }
    
    .customer-table tr.selected td {
        color: white;
    }
    
    /* Add Customer Button */
    .add-customer-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 10px;
    }
    
    .add-customer-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .add-customer-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Walk-in Checkbox */
    .walkin-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 10px 0;
        padding: 8px;
        background: #f1f5f9;
        border-radius: 8px;
    }
    
    .walkin-checkbox input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .walkin-checkbox label {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
    }
    
    /* Validation error message */
    .validation-error-msg {
        color: #dc2626;
        font-size: 11px;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .validation-error-msg i {
        font-size: 10px;
    }
    
    /* Tab completion summary */
    .tab-completion-summary {
        font-size: 11px;
        margin-top: 10px;
        text-align: center;
        color: #64748b;
    }
    
    .tab-completion-summary.complete {
        color: #10b981;
    }

    /* Force remove modal backdrop when needed */
    .modal-backdrop {
        transition: none !important;
    }

    .modal-backdrop.fade.show {
        opacity: 0.5;
    }

    /* Ensure body doesn't get stuck */
    body.modal-open {
        overflow: auto !important;
        padding-right: 0 !important;
    }
</style>

<div class="row">
    <!-- LEFT COLUMN - New Call Form -->
    <div class="col-md-5">
        <div class="new-call-container" style="max-width: 100%; margin: 0;">
            <!-- Header with Timer -->
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 2px solid #0284c8;">
                <h5 style="margin: 0; color: #0284c8;"><i class="fa fa-phone-volume"></i> NEW CALL</h5>
                <div id="callTimer" style="font-size: 20px; font-weight: 700; font-family: monospace; color: #dc2626;">00:00:00</div>
            </div>

            <!-- SELECT Button -->
            <div class="mb-3">
                <button class="btn btn-success" id="selectBtn" data-bs-toggle="modal" data-bs-target="#selectionModal" style="background: linear-gradient(135deg, #10b981, #059669); border: none; width: 100%;">
                    <i class="fa fa-user-plus"></i> Start New Call
                </button>
            </div>

            <!-- Seller Information -->
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">SELLER ID</label>
                    <input type="text" id="sellerId" class="form-control form-control-sm" readonly placeholder="Select seller" style="background: #f1f5f9; font-size: 13px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">SELLER NAME</label>
                    <input type="text" id="sellerName" class="form-control form-control-sm" readonly placeholder="Select seller" style="background: #f1f5f9; font-size: 13px;">
                </div>
            </div>

            <!-- Customer Information -->
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">CUSTOMER ID</label>
                    <input type="text" id="customerId" class="form-control form-control-sm" readonly placeholder="Select customer" style="background: #f1f5f9; font-size: 13px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">CUSTOMER NAME</label>
                    <input type="text" id="customerName" class="form-control form-control-sm" readonly placeholder="Select customer" style="background: #f1f5f9; font-size: 13px;">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label" style="font-size: 12px; font-weight: 600;">ADDRESS</label>
                <input type="text" id="address" class="form-control form-control-sm" readonly placeholder="Customer address" style="background: #f1f5f9; font-size: 13px;">
            </div>

            <!-- Phone Number with Update Button -->
            <div class="mb-2">
                <label class="form-label" style="font-size: 12px; font-weight: 600;">PHONE NUMBER</label>
                <div class="d-flex gap-2">
                    <input type="tel" id="phoneNum" class="form-control form-control-sm" placeholder="Enter phone number" style="width: 30%; font-size: 13px;">
                    <button class="btn btn-sm" id="updateBtn" style="background: linear-gradient(135deg, #0284c8, #38bdf8); color: white; border: none;">Update</button>
                </div>
                <span id="displayPhone" class="small text-primary mt-1 d-block"></span>
            </div>

            <hr>

            <!-- Invoice Date, Invoice Number, and Amount -->
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">INVOICE DATE</label>
                    <input type="date" id="invoiceDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" style="font-size: 13px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">INVOICE NUMBER</label>
                    <input type="text" id="invoiceNumber" class="form-control form-control-sm" placeholder="Enter invoice number" style="font-size: 13px;">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">AMOUNT</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">₱</span>
                        <input type="number" id="amount" class="form-control" placeholder="0.00" step="0.01" style="font-size: 13px;">
                    </div>
                </div>
            </div>

            <hr>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-md" id="completeCallBtn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none;">
                    <i class="fa fa-check-circle"></i> Confirm and Start Call
                </button>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN - Call Questions/Script with Tabs -->
    <div class="col-md-7">
        <div class="call-questions-panel" id="callQuestionsPanel">
            <!-- Placeholder Content -->
            <div id="placeholderContent" class="placeholder-content">
                <div class="placeholder-icon">
                    <i class="fa fa-phone-alt"></i>
                </div>
                <h5 style="color: #475569;">Ready to Start a Call?</h5>
                <p style="color: #64748b; font-size: 13px; max-width: 300px;">
                    Please select a seller and customer using the <strong>"Start New Call"</strong> button, then click <strong>"Confirm and Start Call"</strong> to begin the call session.
                </p>
                <div class="mt-3">
                    <i class="fa fa-arrow-left text-muted"></i>
                    <span class="text-muted" style="font-size: 12px;"> Start by clicking the button on the left</span>
                </div>
            </div>

            <!-- Call Questions Content -->
            <div id="questionsContent" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 style="margin: 0; color: #0284c8;"><i class="fa fa-clipboard-list"></i> CALL SCRIPT</h6>
                    <div class="recording-indicator" id="recordingIndicator">
                        <span class="recording-dot"></span>
                        <span>Recording in progress</span>
                    </div>
                </div>
                
                <div class="call-header-info">
                    <i class="fa fa-user-circle"></i> Speaking with: <span id="headerCustomerName">-</span> | 
                    <i class="fa fa-phone"></i> <span id="headerPhone">-</span>
                </div>
                
                <div class="script-tabs" id="scriptTabs"></div>
                
                <div id="tabsContainer"></div>
                
                <div class="nav-buttons">
                    <button class="nav-btn nav-btn-prev" id="prevTabBtn" disabled>
                        <i class="fa fa-chevron-left"></i> Back
                    </button>
                    <div class="tab-progress" id="tabProgress"></div>
                    <button class="nav-btn nav-btn-next" id="nextTabBtn">
                        Next <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
                
                <div class="tab-completion-summary" id="tabCompletionSummary"></div>
                
                <div class="mt-3 text-center">
                    <button class="btn btn-sm" id="endCallBtn" style="background: #dc2626; color: white; border: none; padding: 8px 30px;">
                        <i class="fa fa-phone-slash"></i> End Call
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Selection Modal -->
<div class="modal fade" id="selectionModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0284c8, #38bdf8); color: white;">
                <h5 class="modal-title"><i class="fa fa-user-check"></i> Select Seller & Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Seller Selection -->
                <label class="form-label fw-semibold mb-1">Select Seller:</label>
                <input type="text" id="searchSeller" class="form-control form-control-sm mb-2" placeholder="Search seller...">
                <div id="sellerListContainer" style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px;">
                    <?php if (!empty($sellers)): ?>
                        <?php foreach ($sellers as $seller): 
                            $sellerId = htmlspecialchars($seller['SELLER_ID'] ?? '', ENT_QUOTES, 'UTF-8');
                            $sellerName = htmlspecialchars($seller['SELLER_NAME'] ?? '', ENT_QUOTES, 'UTF-8');
                            $displayText = $sellerName . ' (' . $sellerId . ')';
                        ?>
                            <div class="seller-item p-2 border-bottom" 
                                 data-seller-id="<?= $sellerId ?>" 
                                 data-seller-name="<?= $sellerName ?>"
                                 style="cursor: pointer; font-size: 13px;">
                                <?= $displayText ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-3 text-center text-muted">No sellers found</div>
                    <?php endif; ?>
                </div>

                <hr class="my-3">

                <!-- Customer Selection with Add New Button -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-semibold mb-0">Select Customer:</label>
                    <button type="button" class="add-customer-btn" id="addCustomerBtn" disabled>
                        <i class="fa fa-plus"></i> Add New Customer
                    </button>
                </div>
                
                <input type="text" id="searchCustomer" class="form-control form-control-sm mb-2" placeholder="Search customer..." disabled>
                <div id="customerListContainer" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px;">
                    <div class="p-3 text-center text-muted">Select a seller first</div>
                </div>

                <!-- Selected Summary -->
                <div class="mt-3 p-2" style="background: #f8f9fa; border-radius: 6px; font-size: 12px;">
                    <strong>Selected:</strong><br>
                    👤 Seller: <span id="modalSelectedSeller">None</span><br>
                    🏢 Customer: <span id="modalSelectedCustomer">None</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="confirmSelectionBtn" style="background: linear-gradient(135deg, #0284c8, #38bdf8); color: white; border: none;">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Add New Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                <h5 class="modal-title"><i class="fa fa-user-plus"></i> Add New Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="walkin-checkbox">
                    <input type="checkbox" id="isWalkinCheckbox">
                    <label for="isWalkinCheckbox"> Walk-in Customer (No Store Code)</label>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">STORE CODE / CUSTOMER ID</label>
                    <input type="text" id="newCustomerId" class="form-control" placeholder="Enter store code">
                    <small class="text-muted">Leave empty if Walk-in is checked</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">STORE NAME / CUSTOMER NAME <span class="text-danger">*</span></label>
                    <input type="text" id="newCustomerName" class="form-control" placeholder="Enter store name" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">ADDRESS</label>
                    <textarea id="newCustomerAddress" class="form-control" rows="2" placeholder="Enter complete address"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">PHONE NUMBER</label>
                    <input type="tel" id="newCustomerPhone" class="form-control" placeholder="Enter phone number">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="saveNewCustomerBtn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none;">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- Call Results Modal -->
<div class="modal fade" id="callResultsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0284c8, #38bdf8); color: white;">
                <h5 class="modal-title"><i class="fa fa-clipboard-list"></i> CALL RESULT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <div class="result-item">
                    <span class="result-label">DIAL RESULT</span>
                    <span id="resultDialResult" class="result-value">-</span>
                </div>            
                <div class="result-item">
                    <span class="result-label">STORE NAME ACCURACY & VERIFICATION</span>
                    <span id="resultStoreAccuracy" class="result-value">-</span>
                </div>
                <div class="result-item">
                    <span class="result-label">IS PHONE NUMBER CORRECT?</span>
                    <span id="resultPhoneCorrect" class="result-value">-</span>
                </div>
                <div class="result-item">
                    <span class="result-label">VERIFICATION: STORE VISIT</span>
                    <span id="resultStoreVisit" class="result-value">-</span>
                </div>
                <div class="result-item">
                    <span class="result-label">VERIFICATION: PROD CALL</span>
                    <span id="resultProdCall" class="result-value">-</span>
                </div>
                <div class="result-item">
                    <span class="result-label">VERIFICATION: AMOUNT</span>
                    <span id="resultAmount" class="result-value">-</span>
                </div>
                <div class="result-item">
                    <span class="result-label">VERIFY AMOUNT</span>
                    <span id="resultVerifyAmount" class="result-value">-</span>
                </div>
                <div class="call-duration-box">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">CALL DURATION</div>
                    <span id="resultCallDuration">00:00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" id="submitCallBtn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 8px 30px;">
                    <i class="fa fa-check-circle"></i> SUBMIT CALL
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div class="modal fade" id="alertModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header" id="alertModalHeader" style="background: linear-gradient(135deg, #0284c8, #38bdf8); color: white;">
                <h5 class="modal-title"><i class="fa fa-info-circle"></i> <span id="alertModalTitle">Notification</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i id="alertModalIcon" class="fa fa-bell" style="font-size: 48px; color: #0284c8; margin-bottom: 15px;"></i>
                <p id="alertModalMessage" style="font-size: 14px; color: #0f172a;">Message here</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-sm" id="alertModalCloseBtn" style="background: linear-gradient(135deg, #0284c8, #38bdf8); color: white; border: none; padding: 6px 24px;">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #ef4444); color: white;">
                <h5 class="modal-title"><i class="fa fa-question-circle"></i> Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #dc2626; margin-bottom: 15px;"></i>
                <p id="confirmModalMessage" style="font-size: 14px; color: #0f172a;">Are you sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="confirmModalYesBtn" style="background: #dc2626; color: white; border: none; padding: 6px 24px;">Yes</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                <h5 class="modal-title"><i class="fa fa-check-circle"></i> Success</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fa fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 15px;"></i>
                <p id="successModalMessage" style="font-size: 14px; color: #0f172a;">Success!</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-sm" id="successModalCloseBtn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 6px 24px;">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
// Timer variables
let timerInterval = null;
let seconds = 0;
const callTimer = document.getElementById('callTimer');
let selectedSellerData = null;
let selectedCustomerData = null;
let allCustomers = <?= json_encode($allCustomers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let callActive = false;
let currentTab = 0;
const totalTabs = 6;
let allAnswers = {};

// Track which questions are required (options questions)
const requiredQuestionsByTab = {
    0: ['greeting1'],
    1: ['store_accuracy', 'phone_correct'],
    2: ['store_visit'],
    3: ['prod_call'],
    4: ['amount_verify'],
    5: [] // Closing tab has no required questions
};

// Track tab completion status
let tabCompleted = {
    0: false,
    1: false,
    2: false,
    3: false,
    4: false,
    5: true // Closing tab is always considered complete
};

// Function to check if call was answered
function isCallConnected() {
    return allAnswers['greeting1'] === 'Answered';
}

// REPLACE your existing showAlert function with this:
function showAlert(message, title = 'Notification', type = 'info') {
    // Remove any existing stuck backdrops first
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    
    const modal = new bootstrap.Modal(document.getElementById('alertModal'));
    document.getElementById('alertModalMessage').innerText = message;
    document.getElementById('alertModalTitle').innerText = title;
    const icon = document.getElementById('alertModalIcon');
    const header = document.getElementById('alertModalHeader');
    
    if (type === 'error') {
        icon.className = 'fa fa-exclamation-circle';
        icon.style.color = '#dc2626';
        header.style.background = 'linear-gradient(135deg, #dc2626, #ef4444)';
    } else if (type === 'success') {
        icon.className = 'fa fa-check-circle';
        icon.style.color = '#10b981';
        header.style.background = 'linear-gradient(135deg, #10b981, #059669)';
    } else {
        icon.className = 'fa fa-info-circle';
        icon.style.color = '#0284c8';
        header.style.background = 'linear-gradient(135deg, #0284c8, #38bdf8)';
    }
    
    modal.show();
    
    // Clean up when modal is hidden
    document.getElementById('alertModal').addEventListener('hidden.bs.modal', function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }, { once: true });
    
    document.getElementById('alertModalCloseBtn').onclick = () => {
        modal.hide();
        // Force remove backdrop
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }, 100);
    };
}

// REPLACE your existing showSuccess function with this:
function showSuccess(message) {
    // Remove any existing stuck backdrops first
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    document.getElementById('successModalMessage').innerText = message;
    modal.show();
    
    // Clean up when modal is hidden
    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }, { once: true });
    
    document.getElementById('successModalCloseBtn').onclick = () => {
        modal.hide();
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }, 100);
    };
}

function showConfirm(message, callback) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    document.getElementById('confirmModalMessage').innerText = message;
    modal.show();
    
    document.getElementById('confirmModalYesBtn').onclick = () => {
        modal.hide();
        if (callback) callback();
    };
}

// Check if a tab has all required questions answered
function isTabComplete(tabIndex) {
    // If call was not answered, only tab 0 is required
    if (!isCallConnected() && tabIndex !== 0) {
        return true; // Other tabs are optional when call not answered
    }
    
    // If call was answered or we're on tab 0, check normally
    const requiredQuestions = requiredQuestionsByTab[tabIndex] || [];
    for (const qId of requiredQuestions) {
        const answer = allAnswers[qId];
        if (!answer || answer === '') {
            return false;
        }
        // For conditional fields that depend on other answers, check if they should be required
        if (qId === 'store_name' && allAnswers['store_accuracy'] === 'No') {
            if (!allAnswers['store_name'] || allAnswers['store_name'] === '') return false;
        }
        if (qId === 'store_address' && allAnswers['store_accuracy'] === 'No') {
            if (!allAnswers['store_address'] || allAnswers['store_address'] === '') return false;
        }
        if (qId === 'correct_amount' && allAnswers['amount_verify'] === 'Not matched') {
            if (!allAnswers['correct_amount'] || allAnswers['correct_amount'] === '') return false;
        }
    }
    return true;
}

// Update all tab completion statuses and UI
function updateAllTabCompletion() {
    // If call was not answered, only tab 0 matters
    if (!isCallConnected()) {
        for (let i = 0; i < totalTabs; i++) {
            tabCompleted[i] = (i === 0) ? isTabComplete(0) : true;
        }
    } else {
        for (let i = 0; i < totalTabs; i++) {
            tabCompleted[i] = isTabComplete(i);
        }
    }
    updateTabButtonsUI();
    updateProgressDots(currentTab);
    updateCompletionSummary();
}

// Update tab button UI (show incomplete indicator)
function updateTabButtonsUI() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const callConnected = isCallConnected();
    
    tabButtons.forEach((btn, idx) => {
        if (idx < totalTabs) {
            if (tabCompleted[idx]) {
                btn.classList.remove('incomplete');
            } else if (idx !== 5) {
                // Only show incomplete for tabs that are required
                if (callConnected || idx === 0) {
                    btn.classList.add('incomplete');
                } else {
                    btn.classList.remove('incomplete');
                }
            } else {
                btn.classList.remove('incomplete');
            }
        }
    });
}

// Update completion summary text
function updateCompletionSummary() {
    const summaryDiv = document.getElementById('tabCompletionSummary');
    if (!summaryDiv) return;
    
    const callConnected = isCallConnected();
    
    if (!callConnected) {
        // Only check tab 0 completion
        if (tabCompleted[0]) {
            summaryDiv.innerHTML = '<i class="fa fa-check-circle"></i> Greeting section complete! You can end the call.';
            summaryDiv.className = 'tab-completion-summary complete';
        } else {
            summaryDiv.innerHTML = '<i class="fa fa-exclamation-circle"></i> Please complete the Greeting section before ending the call.';
            summaryDiv.className = 'tab-completion-summary';
        }
    } else {
        const completedCount = Object.values(tabCompleted).filter(v => v === true).length;
        const totalRequired = totalTabs - 1;
        
        if (completedCount === totalRequired) {
            summaryDiv.innerHTML = '<i class="fa fa-check-circle"></i> All sections complete! You can end the call.';
            summaryDiv.className = 'tab-completion-summary complete';
        } else {
            const remaining = totalRequired - completedCount;
            summaryDiv.innerHTML = `<i class="fa fa-exclamation-circle"></i> ${remaining} section(s) incomplete. Please complete all questions before ending the call.`;
            summaryDiv.className = 'tab-completion-summary';
        }
    }
}

// Check if all required tabs are complete before ending call
function isAllRequiredTabsComplete() {
    for (let i = 0; i < totalTabs - 1; i++) {
        if (!tabCompleted[i]) return false;
    }
    return true;
}

// Highlight incomplete questions in current tab
function highlightIncompleteQuestions() {
    const requiredQuestions = requiredQuestionsByTab[currentTab] || [];
    const questionItems = document.querySelectorAll('.question-item');
    
    questionItems.forEach(item => {
        const qId = item.getAttribute('data-question-id');
        if (requiredQuestions.includes(qId)) {
            const answer = allAnswers[qId];
            const isConditionallyRequired = checkIfConditionallyRequired(qId);
            
            if (isConditionallyRequired && (!answer || answer === '')) {
                item.classList.add('incomplete-question');
                // Add error message if not exists
                if (!item.querySelector('.validation-error-msg')) {
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'validation-error-msg';
                    errorMsg.innerHTML = '<i class="fa fa-exclamation-circle"></i> Please select an option';
                    item.appendChild(errorMsg);
                }
            } else {
                item.classList.remove('incomplete-question');
                const errorMsg = item.querySelector('.validation-error-msg');
                if (errorMsg) errorMsg.remove();
            }
        } else {
            item.classList.remove('incomplete-question');
            const errorMsg = item.querySelector('.validation-error-msg');
            if (errorMsg) errorMsg.remove();
        }
    });
}

// Check if a question is conditionally required based on previous answers
function checkIfConditionallyRequired(qId) {
    if (qId === 'store_name' && allAnswers['store_accuracy'] === 'No') return true;
    if (qId === 'store_address' && allAnswers['store_accuracy'] === 'No') return true;
    if (qId === 'correct_amount' && allAnswers['amount_verify'] === 'Not matched') return true;
    
    // For regular required questions
    const requiredQuestions = requiredQuestionsByTab[currentTab] || [];
    return requiredQuestions.includes(qId);
}

// Validate current tab before moving to next
function validateCurrentTab() {
    // If call was not answered, only tab 0 validation matters
    if (!isCallConnected() && currentTab !== 0) {
        return true; // Skip validation for other tabs when call not answered
    }
    
    const requiredQuestions = requiredQuestionsByTab[currentTab] || [];
    let missingQuestions = [];
    
    for (const qId of requiredQuestions) {
        const answer = allAnswers[qId];
        
        // Check conditional requirements
        if (qId === 'store_name' && allAnswers['store_accuracy'] === 'No') {
            if (!allAnswers['store_name'] || allAnswers['store_name'] === '') {
                missingQuestions.push('Correct Store Name');
            }
            continue;
        }
        if (qId === 'store_address' && allAnswers['store_accuracy'] === 'No') {
            if (!allAnswers['store_address'] || allAnswers['store_address'] === '') {
                missingQuestions.push('Correct Address');
            }
            continue;
        }
        if (qId === 'correct_amount' && allAnswers['amount_verify'] === 'Not matched') {
            if (!allAnswers['correct_amount'] || allAnswers['correct_amount'] === '') {
                missingQuestions.push('Correct Amount');
            }
            continue;
        }
        
        // Regular required check
        if (!answer || answer === '') {
            // Map question IDs to user-friendly names
            const nameMap = {
                'greeting1': 'Greeting/Answer Status',
                'store_accuracy': 'Store Name Accuracy',
                'phone_correct': 'Phone Number Correctness',
                'store_visit': 'Store Visit Verification',
                'prod_call': 'Product Call Verification',
                'amount_verify': 'Amount Verification'
            };
            missingQuestions.push(nameMap[qId] || qId);
        }
    }
    
    if (missingQuestions.length > 0) {
        showAlert(`Please complete the following questions before proceeding:\n• ${missingQuestions.join('\n• ')}`, 'Incomplete Section', 'error');
        highlightIncompleteQuestions();
        return false;
    }
    
    return true;
}

// Tab content data
const tabContents = [
    {
        title: "Greeting",
        questions: [{
            id: "greeting1",
            question: "Good Morning/Afternoon!\n\nTaga - INTRODUCE YOURSELF USING OUR STRONGEST BRANDS!\n\nAko po si (pangalan) mula sa Bluesun/WDC/Nebraska/KFI. Kami po ang nag bebenta ng Safeguard, Ariel, Silver Swan, Papa Ketchup, Datu Puti, Del Monte, Breadbox, Eden, Tang, Tambal ni unilab (biogesic, Neosep)",
            type: "options",
            options: ["Answered", "Cannot be reached", "Wrong number", "No answer"]
        }]
    },
    {
        title: "Verification",
        questions: [
            { id: "store_accuracy", question: "Tanong ko lang po kung (sabihin ang pangalang ng tindahan) po ang pangalan ng tindahan ninyo? at kung tama po ang address ninyo na (sabihin ang address)?", type: "options", options: ["Yes", "No"] },
            { id: "store_name", question: "Please enter the correct Store Name:", type: "conditional-input", placeholder: "Enter store name", dependsOn: "store_accuracy", showWhen: "No" },
            { id: "store_address", question: "Please enter the correct Address:", type: "conditional-textarea", placeholder: "Enter complete address", dependsOn: "store_accuracy", showWhen: "No" },
            { id: "phone_correct", question: "Is the phone number correct?", type: "options", options: ["Yes", "No"] }
        ]
    },
    {
        title: "Store Visit",
        questions: [{ id: "store_visit", question: "Salamat po mam/sir. Mangayo lang unta mig gamay na oras para magvalidate maam/sir. Nibisita ba ang among panel sa inyo gahapon maam/Sir (or last week for Neb)?", type: "options", options: ["Yes", "No"] }]
    },
    {
        title: "Prod Call",
        questions: [{ id: "prod_call", question: "Salamat maam/sir. Nipalit pud ba mo maam/Sir?", type: "options", options: ["Yes", "No", "Can't remember"] }]
    },
    {
        title: "Amount",
        questions: [
            { id: "amount_verify", question: "Mga pila pud na amount imong napalit maam/sir?\n\nIf within the range amount reflected in system/invoice – VERIFIED\n(and put the amount mentioned by store)\nIF NOT MATCHED – put INCORRECT AMOUNT;", type: "options", options: ["Can't remember", "Matched", "Not matched"] },
            { id: "correct_amount", question: "Please enter the correct Amount:", type: "conditional-input", placeholder: "Enter amount", dependsOn: "amount_verify", showWhen: "Not matched" }
        ]
    },
    {
        title: "Closing",
        questions: [{ id: "followup1", question: "Salamat sa pakig-istorya sa amoa, ma'am/sir. Maayong adlaw. Paalam.", type: "info" }]
    }
];

// Handle conditional fields
function handleConditionalFields(questionId, selectedValue) {
    const conditionalQuestions = document.querySelectorAll(`[data-depends-on="${questionId}"]`);
    conditionalQuestions.forEach(elem => {
        const parentDiv = elem.closest('.question-item');
        const showWhen = elem.getAttribute('data-show-when');
        if (selectedValue === showWhen) {
            parentDiv.style.display = 'block';
            parentDiv.style.animation = 'fadeIn 0.3s ease';
        } else {
            parentDiv.style.display = 'none';
            const input = parentDiv.querySelector('.response-input');
            if (input) input.value = '';
            const qId = parentDiv.getAttribute('data-question-id');
            if (qId) allAnswers[qId] = '';
        }
    });
    // Update completion status after conditional changes
    updateAllTabCompletion();
}

// Load tab content
function loadTab(tabIndex) {
    const container = document.getElementById('tabsContainer');
    if (!container) return;
    const tab = tabContents[tabIndex];
    if (!tab) return;
    
    let html = `<div class="tab-content active-tab" id="tab-${tabIndex}">`;
    tab.questions.forEach(q => {
        const savedValue = allAnswers[q.id] || '';
        let displayStyle = 'block';
        if (q.dependsOn) {
            const dependsValue = allAnswers[q.dependsOn] || '';
            if (dependsValue !== q.showWhen) displayStyle = 'none';
        }
        
        if (q.type === 'options') {
            html += `<div class="question-item" data-question-id="${q.id}" style="display: block;">
                        <div class="question-text">${q.question.replace(/\n/g, '<br>')}</div>
                        <div class="question-options">
                            ${q.options.map(opt => `<span class="option-btn ${savedValue === opt ? 'selected' : ''}" data-option="${opt}">${opt}</span>`).join('')}
                        </div>
                        <input type="hidden" class="question-answer" data-question-id="${q.id}" value="${savedValue.replace(/"/g, '&quot;')}">
                    </div>`;
        } else if (q.type === 'conditional-input') {
            html += `<div class="question-item" data-question-id="${q.id}" data-depends-on="${q.dependsOn}" data-show-when="${q.showWhen}" style="display: ${displayStyle};">
                        <div class="question-text">${q.question}</div>
                        <input type="text" class="response-input" placeholder="${q.placeholder}" data-question-id="${q.id}" value="${savedValue.replace(/"/g, '&quot;')}">
                    </div>`;
        } else if (q.type === 'conditional-textarea') {
            html += `<div class="question-item" data-question-id="${q.id}" data-depends-on="${q.dependsOn}" data-show-when="${q.showWhen}" style="display: ${displayStyle};">
                        <div class="question-text">${q.question}</div>
                        <textarea class="response-input" rows="3" placeholder="${q.placeholder}" data-question-id="${q.id}">${savedValue}</textarea>
                    </div>`;
        } else if (q.type === 'info') {
            html += `<div class="question-item" data-question-id="${q.id}" style="background: #f0fdf4; border-left-color: #10b981;">
                        <div class="question-text" style="color: #166534; font-size: 16px; text-align: center;">
                            <i class="fa fa-smile-wink" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                            ${q.question}
                        </div>
                    </div>`;
        }
    });
    html += `</div>`;
    container.innerHTML = html;
    
    document.querySelectorAll('.option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const parent = this.closest('.question-item');
            const questionId = parent.getAttribute('data-question-id');
            const selectedValue = this.getAttribute('data-option');
            parent.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            const hiddenInput = parent.querySelector('.question-answer');
            if (hiddenInput) {
                hiddenInput.value = selectedValue;
                allAnswers[questionId] = selectedValue;
            }
            
            // If greeting question is answered, update UI
            if (questionId === 'greeting1') {
                updateAllTabCompletion();
                // If answered, refresh the current tab
                if (selectedValue === 'Answered') {
                    loadTab(currentTab);
                }
            }
            
            handleConditionalFields(questionId, selectedValue);
            // Update completion after answer
            updateAllTabCompletion();
            highlightIncompleteQuestions();
        });
    });
    
    document.querySelectorAll('.response-input').forEach(input => {
        const questionId = input.getAttribute('data-question-id');
        input.addEventListener('input', function() { 
            allAnswers[questionId] = this.value;
            updateAllTabCompletion();
            highlightIncompleteQuestions();
        });
    });
    
    // Update tab buttons UI
    updateTabButtonsUI();
    updateProgressDots(tabIndex);
    updateNavButtons(tabIndex);
    highlightIncompleteQuestions();
    
    // Update active tab button styling
    document.querySelectorAll('.tab-btn').forEach((btn, idx) => {
        if (idx == tabIndex) btn.classList.add('active');
        else btn.classList.remove('active');
    });
}

function updateProgressDots(currentIndex) {
    const progressContainer = document.getElementById('tabProgress');
    if (!progressContainer) return;
    let dots = '';
    for (let i = 0; i < totalTabs; i++) {
        let dotClass = 'progress-dot';
        if (i === currentIndex) dotClass += ' active';
        if (tabCompleted[i] && i !== currentIndex) dotClass += ' completed';
        dots += `<div class="${dotClass}"></div>`;
    }
    progressContainer.innerHTML = dots;
}

function updateNavButtons(currentIndex) {
    const prevBtn = document.getElementById('prevTabBtn');
    const nextBtn = document.getElementById('nextTabBtn');
    if (prevBtn) prevBtn.disabled = currentIndex === 0;
    if (nextBtn) {
        if (currentIndex === totalTabs - 1) {
            nextBtn.style.display = 'none';
        } else {
            nextBtn.style.display = 'block';
            nextBtn.innerHTML = 'Next <i class="fa fa-chevron-right"></i>';
            nextBtn.style.background = 'linear-gradient(135deg, #0284c8, #38bdf8)';
        }
    }
}

function goToNextTab() { 
    if (currentTab < totalTabs - 1) {
        // Validate current tab before proceeding
        if (validateCurrentTab()) {
            currentTab++; 
            loadTab(currentTab);
        }
    }
}

function goToPrevTab() { 
    if (currentTab > 0) { 
        currentTab--; 
        loadTab(currentTab);
    }
}

// Show Results Modal (with validation before showing)
function showResultsModal() {
    const callConnected = isCallConnected();
    
    // If call was not answered, only check tab 0
    if (callConnected) {
        // Check if all required tabs are complete
        if (!isAllRequiredTabsComplete()) {
            let incompleteTabs = [];
            for (let i = 0; i < totalTabs - 1; i++) {
                if (!tabCompleted[i]) {
                    incompleteTabs.push(tabContents[i].title);
                }
            }
            showAlert(`Please complete all sections before ending the call.\n\nIncomplete: ${incompleteTabs.join(', ')}`, 'Cannot End Call', 'error');
            return;
        }
    } else {
        // If call not answered, only check tab 0
        if (!tabCompleted[0]) {
            showAlert('Please complete the Greeting section before ending the call.', 'Cannot End Call', 'error');
            return;
        }
    }
    
    const dialResult = allAnswers['greeting1'] === 'Answered' ? 'Answered' : (allAnswers['greeting1'] === 'Cannot be reached' ? 'Cannot be reached' : (allAnswers['greeting1'] === 'Wrong number' ? 'Wrong number' : (allAnswers['greeting1'] === 'No answer' ? 'No answer' : 'N/A')));
    document.getElementById('resultDialResult').textContent = dialResult;
    document.getElementById('resultDialResult').className = 'result-value ' + (dialResult === 'Answered' ? 'correct' : 'na');
    
    // Only show other results if call was answered
    if (callConnected) {
        document.getElementById('resultStoreAccuracy').textContent = allAnswers['store_accuracy'] || 'N/A';
        document.getElementById('resultPhoneCorrect').textContent = allAnswers['phone_correct'] || 'N/A';
        document.getElementById('resultStoreVisit').textContent = allAnswers['store_visit'] || 'N/A';
        document.getElementById('resultProdCall').textContent = allAnswers['prod_call'] || 'N/A';
        document.getElementById('resultAmount').textContent = allAnswers['amount_verify'] || 'N/A';
        
        const amountVerify = allAnswers['amount_verify'];
        const correctAmount = allAnswers['correct_amount'] || '';
        const resultVerifyAmount = document.getElementById('resultVerifyAmount');
        if (amountVerify === 'Not matched' && correctAmount) resultVerifyAmount.textContent = '₱ ' + correctAmount;
        else if (amountVerify === 'Matched') resultVerifyAmount.textContent = 'Verified';
        else resultVerifyAmount.textContent = amountVerify || 'N/A';
    } else {
        // Set N/A for other fields when call not answered
        document.getElementById('resultStoreAccuracy').textContent = 'N/A';
        document.getElementById('resultPhoneCorrect').textContent = 'N/A';
        document.getElementById('resultStoreVisit').textContent = 'N/A';
        document.getElementById('resultProdCall').textContent = 'N/A';
        document.getElementById('resultAmount').textContent = 'N/A';
        document.getElementById('resultVerifyAmount').textContent = 'N/A';
    }
    
    document.getElementById('resultCallDuration').textContent = callTimer.textContent;
    new bootstrap.Modal(document.getElementById('callResultsModal')).show();
}

// Submit Call - Insert into TBC_CALL_TRANSACTION table
function submitCall() {
    const sellerId = document.getElementById('sellerId').value;
    const customerId = document.getElementById('customerId').value;
    const customerName = document.getElementById('customerName').value;
    const phoneNum = document.getElementById('phoneNum').value;
    const address = document.getElementById('address').value;
    const invoiceDate = document.getElementById('invoiceDate').value;
    const invoiceNumber = document.getElementById('invoiceNumber').value;
    let amount = document.getElementById('amount').value;
    const duration = callTimer.textContent;
    const sellerName = document.getElementById('sellerName').value;
    
    if (typeof amount === 'string') {
        amount = amount.replace(/,/g, '');
    }
    const cleanAmount = parseFloat(amount) || 0;
    
    const dialResult = allAnswers['greeting1'] || '';
    const storeAccuracy = allAnswers['store_accuracy'] || '';
    const correctedStoreName = allAnswers['store_name'] || '';
    const correctedAddress = allAnswers['store_address'] || '';
    const phoneCorrect = allAnswers['phone_correct'] || '';
    const storeVisit = allAnswers['store_visit'] || '';
    const prodCall = allAnswers['prod_call'] || '';
    const amountVerify = allAnswers['amount_verify'] || '';
    let correctAmount = allAnswers['correct_amount'] || '';
    
    if (correctAmount && typeof correctAmount === 'string') {
        correctAmount = correctAmount.replace(/,/g, '');
        correctAmount = parseFloat(correctAmount) || 0;
    }
    
    let status = '';
    if (dialResult === 'Answered') status = 'Picked';
    else if (dialResult === 'Cannot be reached') status = 'Cant Be Reached';
    else if (dialResult === 'Wrong number') status = 'Wrong Number';
    else if (dialResult === 'No answer') status = 'No Answer';
    else status = 'Cancelled';
    
    let amountVerificationValue = '';
    let amountResultValue = '';
    
    if (amountVerify === 'Matched') {
        amountVerificationValue = cleanAmount;
        amountResultValue = 'MATCHED';
    } else if (amountVerify === 'Not matched') {
        amountVerificationValue = correctAmount;
        amountResultValue = 'NOT MATCHED';
    } else if (amountVerify === "Can't remember") {
        amountVerificationValue = 'NOT REMEMBERED';
        amountResultValue = 'NOT REMEMBERED';
    } else {
        amountVerificationValue = '';
        amountResultValue = '';
    }
    
    const callData = {
        branch: '<?php echo $_SESSION['SITE'] ?? ""; ?>',
        principal: '<?php echo $_SESSION['PRINCIPAL'] ?? ""; ?>',
        call_date: new Date().toISOString().split('T')[0],
        call_duration: duration,
        tele_caller: '<?php echo $_SESSION['NAME'] ?? ""; ?>',
        cu_id: customerId,
        customer: customerName,
        phone_number: phoneNum,
        address: address,
        van_id: sellerId,
        seller: sellerName,
        invoice_number: invoiceNumber,
        amount: cleanAmount,
        date_invoiced: invoiceDate,
        store_name_accuracy: storeAccuracy === 'Yes' ? 'CORRECT' : (storeAccuracy === 'No' ? 'NOT CORRECT' : ''),
        corrected_store_name: correctedStoreName,
        corrected_address: correctedAddress,
        is_phone_number_correct: phoneCorrect === 'Yes' ? 'YES' : (phoneCorrect === 'No' ? 'NO' : ''),
        store_visit: storeVisit === 'Yes' ? 'VISITED' : (storeVisit === 'No' ? 'NOT VISITED' : ''),
        prod_call: prodCall === 'Yes' ? 'YES' : (prodCall === 'No' ? 'NO' : (prodCall === "Can't remember" ? 'NOT REMEMBERED' : '')),
        amount_verification: amountVerificationValue,
        amount_result: amountResultValue,
        status: status,
        dial_result: dialResult
    };
    
    fetch('/TBC/page/save_call_transaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(callData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Call submitted successfully!');
            const resultsModal = bootstrap.Modal.getInstance(document.getElementById('callResultsModal'));
            if (resultsModal) resultsModal.hide();
            resetForm();
        } else {
            showAlert('Error: ' + data.message, 'Submission Error', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error submitting call: ' + error, 'Error', 'error');
    });
}

function resetForm() {
    callActive = false;
    currentTab = 0;
    allAnswers = {};
    // Reset tab completion status
    tabCompleted = {
        0: false,
        1: false,
        2: false,
        3: false,
        4: false,
        5: true
    };
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
    document.getElementById('sellerId').value = '';
    document.getElementById('sellerName').value = '';
    document.getElementById('customerId').value = '';
    document.getElementById('customerName').value = '';
    document.getElementById('address').value = '';
    document.getElementById('phoneNum').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('invoiceNumber').value = '';
    document.getElementById('displayPhone').textContent = '';
    document.getElementById('invoiceDate').value = new Date().toISOString().split('T')[0];
    seconds = 0;
    callTimer.textContent = '00:00:00';
    document.getElementById('placeholderContent').style.display = 'flex';
    document.getElementById('questionsContent').style.display = 'none';
    selectedSellerData = null;
    selectedCustomerData = null;
}

function startCall() {
    const sellerId = document.getElementById('sellerId').value;
    const customerId = document.getElementById('customerId').value;
    const invoiceNumber = document.getElementById('invoiceNumber').value;
    const amount = document.getElementById('amount').value;
 
    if (!sellerId) { showAlert('Please select a seller first', 'Validation Error', 'error'); return false; }
    if (!customerId) { showAlert('Please select a customer first', 'Validation Error', 'error'); return false; }
    if (!invoiceNumber) { showAlert('Please enter an invoice number', 'Validation Error', 'error'); return false; }
    if (!amount) { showAlert('Please enter an amount', 'Validation Error', 'error'); return false; }

    callActive = true;
    currentTab = 0;
    allAnswers = {};
    // Reset tab completion
    tabCompleted = {
        0: false,
        1: false,
        2: false,
        3: false,
        4: false,
        5: true
    };
    document.getElementById('headerCustomerName').innerText = document.getElementById('customerName').value || '-';
    document.getElementById('headerPhone').innerText = document.getElementById('phoneNum').value || '-';
    document.getElementById('placeholderContent').style.display = 'none';
    document.getElementById('questionsContent').style.display = 'block';
    
    // Generate tab buttons dynamically
    const scriptTabs = document.getElementById('scriptTabs');
    scriptTabs.innerHTML = '';
    tabContents.forEach((tab, idx) => {
        const btn = document.createElement('button');
        btn.className = 'tab-btn';
        if (idx === 0) btn.classList.add('active');
        btn.setAttribute('data-tab', idx);
        btn.innerText = tab.title;
        btn.addEventListener('click', function() {
            const callConnected = isCallConnected();
            // If call not answered and trying to go to another tab, show warning
            if (!callConnected && idx !== 0) {
                showAlert('Please complete the Greeting section first. Select "Answered" to proceed with the call script.', 'Cannot Switch Tab', 'error');
                return;
            }
            if (validateCurrentTab() || idx < currentTab) {
                currentTab = idx;
                loadTab(currentTab);
            } else {
                showAlert('Please complete all questions in the current section first.', 'Cannot Switch Tab', 'error');
            }
        });
        scriptTabs.appendChild(btn);
    });
    
    loadTab(0);
    startTimer();
    return true;
}

function startTimer() {
    if (timerInterval) return;
    timerInterval = setInterval(() => {
        seconds++;
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        callTimer.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }, 1000);
}

// Phone number display and UPDATE button with DB update
const phoneInput = document.getElementById('phoneNum');
const displayPhone = document.getElementById('displayPhone');
phoneInput.addEventListener('input', function() { displayPhone.textContent = this.value || ''; });

document.getElementById('updateBtn')?.addEventListener('click', function() {
    const phoneNum = document.getElementById('phoneNum').value;
    const customerId = document.getElementById('customerId').value;
    if (!phoneNum) { showAlert('Please enter phone number', 'Validation Error', 'error'); return; }
    if (!customerId) { showAlert('Please select a customer first', 'Validation Error', 'error'); return; }
    
    fetch('/TBC/page/update_phone.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: customerId, phone_number: phoneNum })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) showSuccess('Phone number updated successfully!');
        else showAlert('Update failed: ' + data.message, 'Error', 'error');
    })
    .catch(error => showAlert('Error: ' + error, 'Error', 'error'));
});

// Handle Seller Selection
function attachSellerEvents() {
    document.querySelectorAll('.seller-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.seller-item').forEach(i => {
                i.classList.remove('selected', 'bg-primary', 'text-white');
                i.style.background = '';
            });
            this.classList.add('selected', 'bg-primary', 'text-white');
            this.style.background = '#0284c8';
            selectedSellerData = { id: this.getAttribute('data-seller-id'), name: this.getAttribute('data-seller-name') };
            document.getElementById('modalSelectedSeller').innerText = `${selectedSellerData.name} (${selectedSellerData.id})`;
            
            const addBtn = document.getElementById('addCustomerBtn');
            if (addBtn) addBtn.disabled = false;
            
            const filteredCustomers = allCustomers.filter(c => c.SELLER_ID === selectedSellerData.id);
            const customerContainer = document.getElementById('customerListContainer');
            
            if (filteredCustomers.length > 0) {
                customerContainer.innerHTML = `
                    <table class="customer-table">
                        <thead>
                            <tr><th>CU ID</th><th>STORE NAME</th><th>ADDRESS</th></tr>
                        </thead>
                        <tbody>
                            ${filteredCustomers.map(c => `
                                <tr class="customer-item" 
                                    data-customer-id="${c.CUSTOMER_ID}" 
                                    data-customer-name="${(c.CUSTOMER_NAME || '').replace(/'/g, "\\'")}"
                                    data-address="${(c.ADDRESS || '').replace(/'/g, "\\'")}"
                                    data-phone="${c.PHONE_NUMBER || ''}">
                                    <td>${c.CUSTOMER_ID}</td>
                                    <td>${c.CUSTOMER_NAME}</td>
                                    <td>${c.ADDRESS || '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                attachCustomerEvents();
                document.getElementById('searchCustomer').disabled = false;
            } else {
                customerContainer.innerHTML = '<div class="p-3 text-center text-muted">No customers found for this seller. Click "Add New Customer" to add one.</div>';
                document.getElementById('searchCustomer').disabled = true;
                selectedCustomerData = null;
                document.getElementById('modalSelectedCustomer').innerText = 'None';
            }
        });
    });
}

// Handle Customer Selection - Table row click
function attachCustomerEvents() {
    document.querySelectorAll('.customer-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.customer-item').forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            selectedCustomerData = {
                id: this.getAttribute('data-customer-id'),
                name: this.getAttribute('data-customer-name'),
                address: this.getAttribute('data-address'),
                phone: this.getAttribute('data-phone')
            };
            document.getElementById('modalSelectedCustomer').innerText = `${selectedCustomerData.name} (${selectedCustomerData.id})`;
        });
    });
}

// Add New Customer - SAME STYLE as call transaction
document.getElementById('addCustomerBtn')?.addEventListener('click', function() {
    if (!selectedSellerData) {
        showAlert('Please select a seller first before adding a customer', 'Validation Error', 'error');
        return;
    }
    document.getElementById('newCustomerId').value = '';
    document.getElementById('newCustomerName').value = '';
    document.getElementById('newCustomerAddress').value = '';
    document.getElementById('newCustomerPhone').value = '';
    document.getElementById('isWalkinCheckbox').checked = false;
    document.getElementById('newCustomerId').disabled = false;
    new bootstrap.Modal(document.getElementById('addCustomerModal')).show();
});

// Walk-in checkbox handler
document.getElementById('isWalkinCheckbox')?.addEventListener('change', function(e) {
    const customerIdField = document.getElementById('newCustomerId');
    if (e.target.checked) {
        customerIdField.value = 'WALKIN';
        customerIdField.disabled = true;
    } else {
        customerIdField.value = '';
        customerIdField.disabled = false;
    }
});

// Save New Customer - SAME STYLE as submitCall() function
document.getElementById('saveNewCustomerBtn')?.addEventListener('click', function() {
    const isWalkin = document.getElementById('isWalkinCheckbox').checked;
    let customerId = document.getElementById('newCustomerId').value;
    const customerName = document.getElementById('newCustomerName').value;
    const address = document.getElementById('newCustomerAddress').value;
    const phone = document.getElementById('newCustomerPhone').value;
    
    if (!customerName) {
        showAlert('Please enter customer/store name', 'Validation Error', 'error');
        return;
    }
    
    if (!isWalkin && !customerId) {
        showAlert('Please enter a store code or check "Walk-in Customer"', 'Validation Error', 'error');
        return;
    }
    
    if (isWalkin) {
        customerId = 'WALKIN_' + Date.now();
    }
    
    const now = new Date();
    const callDate = now.toISOString().split('T')[0];
    
    // Prepare customer data - SAME STRUCTURE as call transaction
    const customerData = {
        customer_id: customerId,
        customer_name: customerName,
        address: address,
        phone_number: phone,
        seller_id: selectedSellerData.id,
        site: '<?php echo $_SESSION['SITE'] ?? ""; ?>',
        principal: '<?php echo $_SESSION['PRINCIPAL'] ?? ""; ?>'
    };
    
    // SAME FETCH STYLE as submitCall()
    fetch('/TBC/page/save_new_customer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(customerData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Customer added successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
            
            // Add to local allCustomers array
            allCustomers.push({
                CUSTOMER_ID: customerId,
                CUSTOMER_NAME: customerName,
                ADDRESS: address,
                PHONE_NUMBER: phone,
                SELLER_ID: selectedSellerData.id
            });
            
            // Refresh customer list
            const filteredCustomers = allCustomers.filter(c => c.SELLER_ID === selectedSellerData.id);
            const customerContainer = document.getElementById('customerListContainer');
            
            if (filteredCustomers.length > 0) {
                customerContainer.innerHTML = `
                    <table class="customer-table">
                        <thead>
                            <tr><th>CU ID</th><th>STORE NAME</th><th>ADDRESS</th></tr>
                        </thead>
                        <tbody>
                            ${filteredCustomers.map(c => `
                                <tr class="customer-item" 
                                    data-customer-id="${c.CUSTOMER_ID}" 
                                    data-customer-name="${(c.CUSTOMER_NAME || '').replace(/'/g, "\\'")}"
                                    data-address="${(c.ADDRESS || '').replace(/'/g, "\\'")}"
                                    data-phone="${c.PHONE_NUMBER || ''}">
                                    <td>${c.CUSTOMER_ID}</td>
                                    <td>${c.CUSTOMER_NAME}</td>
                                    <td>${c.ADDRESS || '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                attachCustomerEvents();
                document.getElementById('searchCustomer').disabled = false;
            }
            
            // Auto-select the new customer
            selectedCustomerData = {
                id: customerId,
                name: customerName,
                address: address,
                phone: phone
            };
            document.getElementById('modalSelectedCustomer').innerText = `${selectedCustomerData.name} (${selectedCustomerData.id})`;
            
        } else {
            showAlert('Error: ' + data.message, 'Error', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving customer: ' + error, 'Error', 'error');
    });
});

// Search functionality
document.getElementById('searchSeller')?.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.seller-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(term) ? 'block' : 'none';
    });
});

document.getElementById('searchCustomer')?.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.customer-item').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

// Confirm Selection
document.getElementById('confirmSelectionBtn')?.addEventListener('click', function() {
    if (!selectedSellerData) { showAlert('Please select a seller', 'Validation Error', 'error'); return; }
    if (!selectedCustomerData) { showAlert('Please select a customer', 'Validation Error', 'error'); return; }
    document.getElementById('sellerId').value = selectedSellerData.id;
    document.getElementById('sellerName').value = selectedSellerData.name;
    document.getElementById('customerId').value = selectedCustomerData.id;
    document.getElementById('customerName').value = selectedCustomerData.name;
    document.getElementById('address').value = selectedCustomerData.address || '';
    if (selectedCustomerData.phone) {
        document.getElementById('phoneNum').value = selectedCustomerData.phone;
        document.getElementById('displayPhone').textContent = selectedCustomerData.phone;
    }
    bootstrap.Modal.getInstance(document.getElementById('selectionModal')).hide();
});

// Event Listeners
document.getElementById('completeCallBtn')?.addEventListener('click', startCall);
document.getElementById('prevTabBtn')?.addEventListener('click', goToPrevTab);
document.getElementById('nextTabBtn')?.addEventListener('click', goToNextTab);
document.getElementById('endCallBtn')?.addEventListener('click', function() { 
    showConfirm('Are you sure you want to end this call?', showResultsModal); 
});
document.getElementById('submitCallBtn')?.addEventListener('click', submitCall);

// Modal open event
document.getElementById('selectionModal')?.addEventListener('shown.bs.modal', function() {
    attachSellerEvents();
    selectedSellerData = null;
    selectedCustomerData = null;
    document.getElementById('modalSelectedSeller').innerText = 'None';
    document.getElementById('modalSelectedCustomer').innerText = 'None';
    document.getElementById('searchCustomer').disabled = true;
    document.getElementById('customerListContainer').innerHTML = '<div class="p-3 text-center text-muted">Select a seller first</div>';
    document.getElementById('searchSeller').value = '';
    document.getElementById('searchCustomer').value = '';
    document.querySelectorAll('.seller-item').forEach(i => {
        i.classList.remove('selected', 'bg-primary', 'text-white');
        i.style.background = '';
        i.style.display = 'block';
    });
    const addBtn = document.getElementById('addCustomerBtn');
    if (addBtn) addBtn.disabled = true;
});
</script>