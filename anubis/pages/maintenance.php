<?php
// =====================================================
// maintenance.php - SIM Card & Device Maintenance Dashboard
// Data balance in GB format, AUTO-APPLY filters
// FIXED: number_format() null handling
// =====================================================

require_once "./DB/dbcon.php";

// =====================================================
// HANDLE FORM SUBMISSIONS
// =====================================================
$message = '';
$messageType = '';

// Helper function to safely format numbers
function safeNumberFormat($value, $decimals = 2) {
    if ($value === null || $value === '') return '0.00';
    return number_format(floatval($value), $decimals);
}

function safeNumberFormatNoDecimals($value) {
    if ($value === null || $value === '') return '0';
    return number_format(floatval($value), 0);
}

// Update SIM data for a device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_sim') {
        $device_id = intval($_POST['device_id']);
        $sim = $_POST['sim'];
        $data_balance_gb = floatval($_POST['data_balance_gb'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $last_load = !empty($_POST['last_load']) ? $_POST['last_load'] : null;
        
        // Convert GB to MB for storage (assuming data column stores MB)
        $data_balance_mb = $data_balance_gb * 1024;
        
        $updateSql = "
            UPDATE tc_devices 
            SET sim = ?, 
                data = ?, 
                amount = ?, 
                last_load = ?
            WHERE id = ?
        ";
        $updateStmt = $conn->prepare($updateSql);
        $result = $updateStmt->execute([$sim, $data_balance_mb, $amount, $last_load, $device_id]);
        $message = $result ? "SIM data updated successfully!" : "Error updating SIM data";
        $messageType = $result ? 'success' : 'error';
    }
    
    // Record new load (top-up)
    if ($_POST['action'] === 'record_load') {
        $device_id = intval($_POST['device_id']);
        $load_amount = floatval($_POST['load_amount'] ?? 0);
        $data_added_gb = floatval($_POST['data_added_gb'] ?? 0);
        $load_date = $_POST['load_date'];
        
        // Convert GB to MB
        $data_added_mb = $data_added_gb * 1024;
        
        // Get current values and update
        $getSql = "SELECT ISNULL(data, 0) AS data, ISNULL(amount, 0) AS amount FROM tc_devices WHERE id = ?";
        $getStmt = $conn->prepare($getSql);
        $getStmt->execute([$device_id]);
        $current = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        $newDataBalanceMb = ($current['data'] ?? 0) + $data_added_mb;
        $totalAmount = ($current['amount'] ?? 0) + $load_amount;
        
        $updateSql = "
            UPDATE tc_devices 
            SET data = ?, 
                amount = ?, 
                last_load = ?
            WHERE id = ?
        ";
        $updateStmt = $conn->prepare($updateSql);
        $result = $updateStmt->execute([$newDataBalanceMb, $totalAmount, $load_date, $device_id]);
        $message = $result ? "Load recorded successfully! +" . number_format($data_added_gb, 1) . " GB added." : "Error recording load";
        $messageType = $result ? 'success' : 'error';
    }
    
    // Bulk update data balance
    if ($_POST['action'] === 'bulk_balance') {
        $device_ids = $_POST['device_ids'] ?? [];
        $new_balance_gb = floatval($_POST['new_balance_gb'] ?? 0);
        $new_balance_mb = $new_balance_gb * 1024;
        
        if (!empty($device_ids)) {
            $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
            $updateSql = "UPDATE tc_devices SET data = ? WHERE id IN ($placeholders)";
            $updateStmt = $conn->prepare($updateSql);
            $params = array_merge([$new_balance_mb], $device_ids);
            $result = $updateStmt->execute($params);
            $message = $result ? "Data balances updated for " . count($device_ids) . " devices!" : "Error updating balances";
            $messageType = $result ? 'success' : 'error';
        }
    }
    
    // Extend expiry (update last_load to today, which extends expiry by 1 year)
    if ($_POST['action'] === 'extend_expiry') {
        $device_ids = $_POST['device_ids'] ?? [];
        $new_load_date = date('Y-m-d');
        
        if (!empty($device_ids)) {
            $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
            $updateSql = "UPDATE tc_devices SET last_load = ? WHERE id IN ($placeholders)";
            $updateStmt = $conn->prepare($updateSql);
            $params = array_merge([$new_load_date], $device_ids);
            $result = $updateStmt->execute($params);
            $message = $result ? "Expiry extended for " . count($device_ids) . " devices! New expiry: 1 year from today." : "Error extending expiry";
            $messageType = $result ? 'success' : 'error';
        }
    }
    
    // Bulk record load for selected devices
    if ($_POST['action'] === 'bulk_record_load') {
        $device_ids = json_decode($_POST['device_ids'], true);
        $load_amount = floatval($_POST['load_amount'] ?? 0);
        $data_added_gb = floatval($_POST['data_added_gb'] ?? 0);
        $load_date = $_POST['load_date'];
        $data_added_mb = $data_added_gb * 1024;
        
        $successCount = 0;
        foreach ($device_ids as $device_id) {
            $getSql = "SELECT ISNULL(data, 0) AS data, ISNULL(amount, 0) AS amount FROM tc_devices WHERE id = ?";
            $getStmt = $conn->prepare($getSql);
            $getStmt->execute([$device_id]);
            $current = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            $newDataBalanceMb = ($current['data'] ?? 0) + $data_added_mb;
            $totalAmount = ($current['amount'] ?? 0) + $load_amount;
            
            $updateSql = "UPDATE tc_devices SET data = ?, amount = ?, last_load = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            if ($updateStmt->execute([$newDataBalanceMb, $totalAmount, $load_date, $device_id])) {
                $successCount++;
            }
        }
        $message = "Bulk load completed: $successCount devices updated";
        $messageType = 'success';
    }
}

// =====================================================
// GET ALL DEVICES (no server-side filtering)
// =====================================================
$sql = "
    SELECT 
        id,
        name,
        uniqueid,
        phone,
        model,
        status,
        lastupdate,
        vehicle_type,
        company,
        site,
        sim,
        ISNULL(data, 0) AS data_balance_mb,
        ISNULL(amount, 0) AS total_load_amount,
        last_load,
        CASE 
            WHEN last_load IS NOT NULL THEN DATEDIFF(DAY, last_load, GETDATE())
            ELSE NULL
        END AS days_since_load,
        CASE 
            WHEN last_load IS NOT NULL THEN DATEADD(YEAR, 1, last_load)
            ELSE NULL
        END AS expiry_date,
        CASE 
            WHEN last_load IS NOT NULL THEN DATEDIFF(DAY, GETDATE(), DATEADD(YEAR, 1, last_load))
            ELSE NULL
        END AS days_until_expiry,
        CASE 
            WHEN ISNULL(data, 0) >= 1024 THEN CAST(ROUND(ISNULL(data, 0) / 1024, 1) AS VARCHAR) + ' GB'
            WHEN ISNULL(data, 0) > 0 THEN CAST(CAST(ISNULL(data, 0) AS INT) AS VARCHAR) + ' MB'
            ELSE '0 MB'
        END AS data_display,
        ROUND(ISNULL(data, 0) / 1024.0, 1) AS data_balance_gb,
        CASE 
            WHEN last_load IS NULL THEN 'NEVER_LOADED'
            WHEN DATEADD(YEAR, 1, last_load) < GETDATE() THEN 'EXPIRED'
            WHEN DATEDIFF(DAY, GETDATE(), DATEADD(YEAR, 1, last_load)) <= 30 THEN 'EXPIRING_SOON'
            WHEN ISNULL(data, 0) <= 100 THEN 'DATA_CRITICAL'
            WHEN ISNULL(data, 0) <= 500 THEN 'DATA_LOW'
            WHEN DATEDIFF(DAY, last_load, GETDATE()) > 90 THEN 'NO_LOAD_90D'
            ELSE 'OK'
        END AS alert_status
    FROM tc_devices
    ORDER BY 
        CASE 
            WHEN last_load IS NULL OR DATEADD(YEAR, 1, last_load) < GETDATE() THEN 0
            WHEN ISNULL(data, 0) <= 100 THEN 1
            ELSE 2
        END,
        name
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// CALCULATE SUMMARY STATISTICS
// =====================================================
$totalDevices = count($devices);
$activeDevices = 0;
$criticalData = 0;
$lowData = 0;
$expiredCount = 0;
$expiringCount = 0;
$noLoadCount = 0;
$totalDataBalanceGb = 0;
$totalLoadAmount = 0;

foreach ($devices as $device) {
    if (($device['status'] ?? '') === 'online') $activeDevices++;
    
    $dataBalanceGb = floatval($device['data_balance_gb'] ?? 0);
    $totalDataBalanceGb += $dataBalanceGb;
    $totalLoadAmount += floatval($device['total_load_amount'] ?? 0);
    
    $dataBalanceMb = floatval($device['data_balance_mb'] ?? 0);
    if ($dataBalanceMb <= 100 && $dataBalanceMb > 0) $criticalData++;
    elseif ($dataBalanceMb <= 500 && $dataBalanceMb > 0) $lowData++;
    
    $alertStatus = $device['alert_status'] ?? '';
    if ($alertStatus === 'EXPIRED') $expiredCount++;
    if ($alertStatus === 'EXPIRING_SOON') $expiringCount++;
    if ($alertStatus === 'NO_LOAD_90D') $noLoadCount++;
}

// Get unique SIM cards for filter
$simCards = array_unique(array_filter(array_column($devices, 'sim')));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM Management & Maintenance Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0a0f; color: #e0e0e0; }
        
        .container { height: 90vh; padding: 20px; max-width: 1600px; margin: 0 auto; }
        
        .header { margin-bottom: 25px; }
        .header h1 { font-size: 28px; color: #d4af37; display: flex; align-items: center; gap: 12px; }
        .header p { color: #888; margin-top: 8px; font-size: 14px; }
        
        .alert { padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #22c55e; }
        .alert-error { background: rgba(220, 38, 38, 0.15); border: 1px solid #dc2626; color: #dc2626; }
        .alert-warning { background: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #f59e0b; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 15px; border: 1px solid rgba(212, 175, 55, 0.15); }
        .stat-card .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 24px; font-weight: bold; color: #d4af37; margin-top: 8px; }
        .stat-card .sub { font-size: 10px; color: #666; margin-top: 5px; }
        
        .filters-bar { background: rgba(255, 255, 255, 0.03); border-radius: 16px; padding: 15px 20px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; border: 1px solid rgba(212, 175, 55, 0.1); }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group input, .filter-group select { background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 10px; padding: 8px 12px; color: #fff; font-size: 13px; outline: none; min-width: 150px; }
        .filter-group input:focus, .filter-group select:focus { border-color: #d4af37; }
        .filter-group input::placeholder { color: #666; }
        .reset-btn { background: rgba(255, 255, 255, 0.1); color: #fff; border: none; border-radius: 10px; padding: 8px 20px; cursor: pointer; font-weight: bold; }
        .reset-btn:hover { background: rgba(255, 255, 255, 0.2); }
        
        .action-buttons { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .btn { padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-primary { background: #d4af37; color: #0a0a0f; }
        .btn-secondary { background: rgba(255, 255, 255, 0.08); color: #fff; border: 1px solid rgba(212, 175, 55, 0.3); }
        .btn-warning { background: #f97316; color: #fff; }
        .btn-success { background: #22c55e; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.05); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #1a1a24; border-radius: 20px; padding: 25px; max-width: 500px; width: 90%; border: 1px solid rgba(212,175,55,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { color: #d4af37; }
        .modal-close { background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
        .modal input, .modal select { width: 100%; padding: 10px; margin-bottom: 15px; background: rgba(0,0,0,0.5); border: 1px solid rgba(212,175,55,0.2); border-radius: 8px; color: #fff; }
        
        .table-container { overflow-x: auto; border-radius: 16px; border: 1px solid rgba(212, 175, 55, 0.1); background: rgba(255, 255, 255, 0.02); height: 40vh; max-height: calc(90vh - 380px); overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        th { background: rgba(0, 0, 0, 0.3); color: #d4af37; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 10; }
        tr:hover { background: rgba(212, 175, 55, 0.05); cursor: pointer; }
        
        .checkbox-col { width: 30px; text-align: center; }
        input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #d4af37; }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .badge-critical { background: #dc2626; color: white; }
        .badge-low { background: #f97316; color: white; }
        .badge-expired { background: #991b1b; color: white; }
        .badge-expiring { background: #f59e0b; color: #0a0a0f; }
        .badge-no-load { background: #6b7280; color: white; }
        .badge-ok { background: #22c55e; color: white; }
        .badge-never { background: #4a4a4a; color: white; }
        
        .status-online { color: #22c55e; }
        .status-offline { color: #dc2626; }
        
        .selection-toolbar { background: rgba(212, 175, 55, 0.1); border-radius: 12px; padding: 10px 15px; margin-bottom: 15px; display: none; align-items: center; gap: 15px; flex-wrap: wrap; }
        .selection-toolbar.show { display: flex; }
        .selection-count { color: #d4af37; font-weight: bold; }
        
        .no-results { text-align: center; padding: 40px; color: #888; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filter-group input, .filter-group select { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📱 SIM Management & Maintenance</h1>
        <p>Monitor data balance (GB), track load history, manage expiry (1 year from last load) · Filters auto-apply</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $messageType === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card"><div class="label">Total Devices</div><div class="value"><?= $totalDevices ?></div><div class="sub"><?= $activeDevices ?> online</div></div>
        <div class="stat-card"><div class="label">Total Data Balance</div><div class="value"><?= safeNumberFormat($totalDataBalanceGb, 1) ?> GB</div><div class="sub">Combined</div></div>
        <div class="stat-card"><div class="label">Total Load Amount</div><div class="value">₱<?= safeNumberFormat($totalLoadAmount) ?></div><div class="sub">All time</div></div>
        <div class="stat-card"><div class="label">Critical Data</div><div class="value"><?= $criticalData ?></div><div class="sub">≤100 MB left</div></div>
        <div class="stat-card"><div class="label">Low Data</div><div class="value"><?= $lowData ?></div><div class="sub">101-500 MB</div></div>
        <div class="stat-card"><div class="label">Expired/Expiring</div><div class="value"><?= $expiredCount + $expiringCount ?></div><div class="sub"><?= $expiredCount ?> expired, <?= $expiringCount ?> soon</div></div>
    </div>
    
    <!-- Filters Bar - Auto-apply (no submit button) -->
    <div class="filters-bar">
        <div class="filter-group">
            <label>🔍 Search</label>
            <input type="text" id="searchInput" placeholder="Name, SIM, Phone..." onkeyup="filterTable()">
        </div>
        <div class="filter-group">
            <label>📊 Data Balance</label>
            <select id="dataFilter" onchange="filterTable()">
                <option value="">All</option>
                <option value="critical">Critical (≤100 MB)</option>
                <option value="low">Low (101-500 MB)</option>
                <option value="medium">Medium (501 MB - 2 GB)</option>
                <option value="good">Good (>2 GB)</option>
                <option value="zero">Zero Balance</option>
            </select>
        </div>
        <div class="filter-group">
            <label>📅 Expiry Status</label>
            <select id="expiryFilter" onchange="filterTable()">
                <option value="">All</option>
                <option value="expired">Expired</option>
                <option value="expiring_3">Expiring in 3 months</option>
                <option value="expiring_6">Expiring in 6 months</option>
                <option value="expiring_9">Expiring in 9 months</option>
                <option value="expiring_12">Expiring in 12 months</option>
                <option value="valid">Valid</option>
            </select>
        </div>
        <div class="filter-group">
            <label>⏰ Last Load</label>
            <select id="lastLoadFilter" onchange="filterTable()">
                <option value="0">Any time</option>
                <option value="3">> 3 months ago</option>
                <option value="6">> 6 months ago</option>
                <option value="9">> 9 months ago</option>
                <option value="10">> 10 months ago</option>
                <option value="12">> 12 months ago</option>
            </select>
        </div>
        <div class="filter-group">
            <label>📱 SIM Card</label>
            <select id="simFilter" onchange="filterTable()">
                <option value="">All SIMs</option>
                <?php foreach ($simCards as $sim): ?>
                    <option value="<?= htmlspecialchars($sim) ?>"><?= htmlspecialchars($sim) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>🟢 Status</label>
            <select id="statusFilter" onchange="filterTable()">
                <option value="">All</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
            </select>
        </div>
        <div class="filter-group">
            <button class="reset-btn" onclick="resetFilters()">Reset Filters</button>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
        <button class="btn btn-primary" onclick="openModal('addLoadModal')">➕ Record Load/Top-up</button>
        <button class="btn btn-secondary" onclick="openBulkModal('balanceModal')">💾 Bulk Update Data Balance</button>
        <button class="btn btn-warning" onclick="openBulkModal('expiryModal')">📅 Bulk Extend Expiry (1 year)</button>
        <button class="btn btn-success" onclick="exportToCSV()">📥 Export to CSV</button>
    </div>
    
    <!-- Selection Toolbar -->
    <div class="selection-toolbar" id="selectionToolbar">
        <span class="selection-count" id="selectionCount">0 selected</span>
        <button class="btn btn-success" onclick="bulkRecordLoad()">➕ Record Load for Selected</button>
        <button class="btn btn-warning" onclick="bulkExtendExpiry()">📅 Extend Expiry (reset last_load to today)</button>
        <button class="btn btn-secondary" onclick="clearSelection()">Clear</button>
    </div>
    
    <!-- Devices Table -->
    <div class="table-container">
        <table id="devicesTable">
            <thead>
                <tr>
                    <th class="checkbox-col"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                    <th>Device Name</th>
                    <th>SIM Number</th>
                    <th>Data Balance</th>
                    <th>Total Load (₱)</th>
                    <th>Last Load</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Alert</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($devices as $device): 
                    $alertClass = '';
                    $alertStatus = $device['alert_status'] ?? '';
                    switch($alertStatus) {
                        case 'EXPIRED': $alertClass = 'badge-expired'; break;
                        case 'EXPIRING_SOON': $alertClass = 'badge-expiring'; break;
                        case 'DATA_CRITICAL': $alertClass = 'badge-critical'; break;
                        case 'DATA_LOW': $alertClass = 'badge-low'; break;
                        case 'NO_LOAD_90D': $alertClass = 'badge-no-load'; break;
                        case 'NEVER_LOADED': $alertClass = 'badge-never'; break;
                        default: $alertClass = 'badge-ok';
                    }
                    $statusClass = ($device['status'] ?? '') === 'online' ? 'status-online' : 'status-offline';
                    $expiryDisplay = !empty($device['expiry_date']) ? date('Y-m-d', strtotime($device['expiry_date'])) : 'Never loaded';
                    $lastLoadDisplay = !empty($device['last_load']) ? date('Y-m-d', strtotime($device['last_load'])) : 'Never';
                    $alertText = str_replace('_', ' ', $alertStatus);
                    $dataBalanceMb = floatval($device['data_balance_mb'] ?? 0);
                    $dataBalanceGb = floatval($device['data_balance_gb'] ?? 0);
                    $dataBalanceDisplay = $dataBalanceGb >= 1 ? number_format($dataBalanceGb, 1) . ' GB' : ($dataBalanceMb > 0 ? number_format($dataBalanceMb, 0) . ' MB' : '0 MB');
                    $balanceColor = $dataBalanceMb <= 100 ? '#dc2626' : ($dataBalanceMb <= 500 ? '#f97316' : '#d4af37');
                    $daysSinceLoad = $device['days_since_load'] ?? null;
                    $daysUntilExpiry = $device['days_until_expiry'] ?? null;
                ?>
                    <tr data-name="<?= strtolower(htmlspecialchars($device['name'] ?? '')) ?>"
                        data-sim="<?= strtolower(htmlspecialchars($device['sim'] ?? '')) ?>"
                        data-data-balance="<?= $dataBalanceMb ?>"
                        data-expiry-days="<?= $daysUntilExpiry ?? 9999 ?>"
                        data-last-load-days="<?= $daysSinceLoad ?? 9999 ?>"
                        data-status="<?= strtolower($device['status'] ?? '') ?>"
                        data-alert="<?= $alertStatus ?>"
                        data-phone="<?= strtolower($device['phone'] ?? '') ?>"
                        onclick="editDevice(<?= $device['id'] ?>, '<?= htmlspecialchars($device['name'] ?? '') ?>', '<?= htmlspecialchars($device['sim'] ?? '') ?>', <?= $dataBalanceGb ?>, <?= $device['total_load_amount'] ?? 0 ?>, '<?= $lastLoadDisplay ?>')">
                        <td class="checkbox-col" onclick="event.stopPropagation()"><input type="checkbox" class="device-checkbox" value="<?= $device['id'] ?>" onchange="updateSelection()"></td>
                        <td><strong><?= htmlspecialchars($device['name'] ?? 'Unknown') ?></strong><br><small class="<?= $statusClass ?>"><?= $device['status'] ?? 'unknown' ?></small></td>
                        <td><?= htmlspecialchars($device['sim'] ?? '—') ?></td>
                        <td><strong style="color: <?= $balanceColor ?>"><?= $dataBalanceDisplay ?></strong></td>
                        <td>₱<?= safeNumberFormat($device['total_load_amount'] ?? 0) ?></td>
                        <td><small><?= $lastLoadDisplay ?></small><br><small><?= ($daysSinceLoad && $daysSinceLoad < 9999) ? $daysSinceLoad . ' days ago' : '' ?></small></td>
                        <td><small><?= $expiryDisplay ?></small><br><small><?= ($daysUntilExpiry && $daysUntilExpiry < 9999) ? ($daysUntilExpiry > 0 ? $daysUntilExpiry . ' days left' : 'Expired') : '' ?></small></td>
                        <td><?= ucfirst($device['status'] ?? 'unknown') ?></td>
                        <td><span class="badge <?= $alertClass ?>"><?= $alertText ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="noResults" class="no-results" style="display: none;">No devices match the selected filters</div>
    </div>
</div>

<!-- Modals -->
<div id="addLoadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>📞 Record SIM Load/Top-up</h3><button class="modal-close" onclick="closeModal('addLoadModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="record_load">
            <label>Select Device</label>
            <select name="device_id" required>
                <option value="">-- Select Device --</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?= $device['id'] ?>"><?= htmlspecialchars($device['name'] ?? 'Unknown') ?> (<?= htmlspecialchars($device['sim'] ?? 'No SIM') ?>)</option>
                <?php endforeach; ?>
            </select>
            <label>Load Amount (₱)</label>
            <input type="number" name="load_amount" step="0.01" required placeholder="Enter amount">
            <label>Data Added (GB)</label>
            <input type="number" name="data_added_gb" step="0.1" required placeholder="Enter data in GB">
            <label>Load Date</label>
            <input type="date" name="load_date" value="<?= date('Y-m-d') ?>" required>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Record Load</button>
        </form>
    </div>
</div>

<div id="editSimModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>✏️ Edit SIM Information</h3><button class="modal-close" onclick="closeModal('editSimModal')">×</button></div>
        <form method="POST" id="editSimForm">
            <input type="hidden" name="action" value="update_sim">
            <input type="hidden" name="device_id" id="edit_device_id">
            <label>Device Name</label>
            <input type="text" id="edit_device_name" readonly disabled style="opacity:0.7">
            <label>SIM Number</label>
            <input type="text" name="sim" id="edit_sim" placeholder="09XXXXXXXXX">
            <label>Data Balance (GB)</label>
            <input type="number" name="data_balance_gb" id="edit_data_balance" step="0.1" placeholder="Data in GB">
            <label>Total Load Amount (₱)</label>
            <input type="number" name="amount" id="edit_amount" step="0.01">
            <label>Last Load Date</label>
            <input type="date" name="last_load" id="edit_last_load">
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Save Changes</button>
        </form>
    </div>
</div>

<div id="expiryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>📅 Bulk Extend Expiry (1 year)</h3><button class="modal-close" onclick="closeModal('expiryModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="extend_expiry">
            <input type="hidden" name="device_ids" id="bulk_expiry_ids">
            <p>This will update the last_load date to today, which extends the expiry by 1 year from today.</p>
            <p style="font-size: 12px; color: #f97316; margin-top: 10px;">⚠️ Only use this when SIM has been reloaded!</p>
            <button type="submit" class="btn btn-warning" style="width: 100%; margin-top: 15px;">Extend Expiry for Selected</button>
        </form>
    </div>
</div>

<div id="balanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>💾 Bulk Update Data Balance</h3><button class="modal-close" onclick="closeModal('balanceModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="bulk_balance">
            <input type="hidden" name="device_ids" id="bulk_balance_ids">
            <label>New Data Balance (GB)</label>
            <input type="number" name="new_balance_gb" step="0.1" required placeholder="Enter data in GB">
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Update Balances</button>
        </form>
    </div>
</div>

<script>
    let selectedDevices = [];
    
    function filterTable() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const dataFilter = document.getElementById('dataFilter').value;
        const expiryFilter = document.getElementById('expiryFilter').value;
        const lastLoadFilter = document.getElementById('lastLoadFilter').value;
        const simFilter = document.getElementById('simFilter').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        
        const rows = document.querySelectorAll('#tableBody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            let show = true;
            
            // Search filter
            if (searchTerm) {
                const name = row.getAttribute('data-name') || '';
                const sim = row.getAttribute('data-sim') || '';
                const phone = row.getAttribute('data-phone') || '';
                if (!name.includes(searchTerm) && !sim.includes(searchTerm) && !phone.includes(searchTerm)) {
                    show = false;
                }
            }
            
            // Data balance filter
            if (show && dataFilter) {
                const dataBalance = parseFloat(row.getAttribute('data-data-balance') || 0);
                switch(dataFilter) {
                    case 'critical': if (dataBalance > 100) show = false; break;
                    case 'low': if (dataBalance < 101 || dataBalance > 500) show = false; break;
                    case 'medium': if (dataBalance < 501 || dataBalance > 2048) show = false; break;
                    case 'good': if (dataBalance <= 2048) show = false; break;
                    case 'zero': if (dataBalance > 0) show = false; break;
                }
            }
            
            // Expiry filter
            if (show && expiryFilter) {
                const expiryDays = parseInt(row.getAttribute('data-expiry-days') || 9999);
                switch(expiryFilter) {
                    case 'expired': if (expiryDays > 0) show = false; break;
                    case 'expiring_3': if (expiryDays < 0 || expiryDays > 90) show = false; break;
                    case 'expiring_6': if (expiryDays < 0 || expiryDays > 180) show = false; break;
                    case 'expiring_9': if (expiryDays < 0 || expiryDays > 270) show = false; break;
                    case 'expiring_12': if (expiryDays < 0 || expiryDays > 365) show = false; break;
                    case 'valid': if (expiryDays <= 0) show = false; break;
                }
            }
            
            // Last load filter
            if (show && lastLoadFilter !== '0') {
                const lastLoadDays = parseInt(row.getAttribute('data-last-load-days') || 9999);
                const months = parseInt(lastLoadFilter);
                const daysThreshold = months * 30;
                if (lastLoadDays <= daysThreshold) show = false;
            }
            
            // SIM filter
            if (show && simFilter) {
                const sim = row.getAttribute('data-sim') || '';
                if (!sim.includes(simFilter)) show = false;
            }
            
            // Status filter
            if (show && statusFilter) {
                const status = row.getAttribute('data-status') || '';
                if (status !== statusFilter) show = false;
            }
            
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // Show/hide no results message
        const noResults = document.getElementById('noResults');
        if (visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
        
        // Clear select all when filtering
        const selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = false;
        updateSelection();
    }
    
    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('dataFilter').value = '';
        document.getElementById('expiryFilter').value = '';
        document.getElementById('lastLoadFilter').value = '0';
        document.getElementById('simFilter').value = '';
        document.getElementById('statusFilter').value = '';
        filterTable();
    }
    
    function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
    
    function openBulkModal(modalId) {
        if (selectedDevices.length === 0) { alert('Please select at least one device first'); return; }
        document.getElementById(modalId).style.display = 'flex';
        if (modalId === 'expiryModal') document.getElementById('bulk_expiry_ids').value = JSON.stringify(selectedDevices);
        if (modalId === 'balanceModal') document.getElementById('bulk_balance_ids').value = JSON.stringify(selectedDevices);
    }
    
    function editDevice(id, name, sim, dataBalanceGb, amount, lastLoad) {
        document.getElementById('edit_device_id').value = id;
        document.getElementById('edit_device_name').value = name;
        document.getElementById('edit_sim').value = sim !== '—' ? sim : '';
        document.getElementById('edit_data_balance').value = dataBalanceGb || 0;
        document.getElementById('edit_amount').value = amount || 0;
        document.getElementById('edit_last_load').value = lastLoad !== 'Never' ? lastLoad : '';
        openModal('editSimModal');
    }
    
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.device-checkbox');
        const visibleRows = document.querySelectorAll('#tableBody tr[style="display: "]');
        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            if (row && row.style.display !== 'none') {
                cb.checked = selectAll.checked;
            }
        });
        updateSelection();
    }
    
    function updateSelection() {
        const checkboxes = document.querySelectorAll('.device-checkbox');
        selectedDevices = Array.from(checkboxes).filter(cb => {
            const row = cb.closest('tr');
            return cb.checked && row && row.style.display !== 'none';
        }).map(cb => parseInt(cb.value));
        const toolbar = document.getElementById('selectionToolbar');
        const countSpan = document.getElementById('selectionCount');
        if (selectedDevices.length > 0) { toolbar.classList.add('show'); countSpan.textContent = selectedDevices.length + ' selected'; }
        else { toolbar.classList.remove('show'); }
    }
    
    function clearSelection() {
        document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateSelection();
    }
    
    function bulkRecordLoad() {
        if (selectedDevices.length === 0) { alert('Please select devices first'); return; }
        const amount = prompt('Enter load amount (₱) for selected devices:', '100');
        const data = prompt('Enter data added (GB) for selected devices:', '10');
        if (amount && data && !isNaN(amount) && !isNaN(data)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input name="action" value="bulk_record_load">
                <input name="device_ids" value='${JSON.stringify(selectedDevices)}'>
                <input name="load_amount" value="${amount}">
                <input name="data_added_gb" value="${data}">
                <input name="load_date" value="${new Date().toISOString().split('T')[0]}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function bulkExtendExpiry() {
        if (selectedDevices.length === 0) { alert('Please select devices first'); return; }
        if (confirm('This will set last_load to today, extending expiry by 1 year. Continue?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input name="action" value="extend_expiry"><input name="device_ids" value='${JSON.stringify(selectedDevices)}'>`;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function exportToCSV() {
        let csv = "Device Name,SIM Number,Data Balance (GB),Total Load (₱),Last Load,Expiry Date,Status,Alert\n";
        const rows = document.querySelectorAll('#tableBody tr');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 8) {
                    const name = cells[1].innerText.split('\n')[0].trim();
                    const sim = cells[2].innerText.trim();
                    const dataBalance = cells[3].innerText.trim();
                    const totalLoad = cells[4].innerText.trim();
                    const lastLoad = cells[5].innerText.split('\n')[0].trim();
                    const expiry = cells[6].innerText.split('\n')[0].trim();
                    const status = cells[7].innerText.trim();
                    const alert = cells[8].innerText.trim();
                    csv += `"${name}","${sim}","${dataBalance}","${totalLoad}","${lastLoad}","${expiry}","${status}","${alert}"\n`;
                }
            }
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `sim_management_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }
    
    window.onclick = function(event) { if (event.target.classList.contains('modal')) event.target.style.display = 'none'; }
    
    // Apply filters on page load
    document.addEventListener('DOMContentLoaded', filterTable);
</script>
</body>
</html>