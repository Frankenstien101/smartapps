<?php
// =====================================================
// FILE: device_management.php
// Complete Device Management with ALL Columns
// =====================================================

require_once "./DB/dbcon.php";

// =========================
// HANDLE AJAX REQUESTS
// =========================
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Get single device details
    if ($_GET['ajax'] === 'get_device') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$id) {
            echo json_encode(['error' => 'Invalid device ID']);
            exit();
        }
        
        $sql = "SELECT * FROM tc_devices WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($device) {
            if (isset($device['attributes']) && $device['attributes']) {
                $device['attributes_decoded'] = json_decode($device['attributes'], true);
            }
            echo json_encode(['success' => true, 'device' => $device]);
        } else {
            echo json_encode(['error' => 'Device not found']);
        }
        exit();
    }
    
    // Update device
    if ($_GET['ajax'] === 'update_device') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            echo json_encode(['error' => 'Invalid data']);
            exit();
        }
        
        $updateFields = [];
        $params = [':id' => $data['id']];
        
        $allowedFields = [
            'name', 'uniqueid', 'phone', 'model', 'contact', 
            'category', 'status', 'vehicle_type', 'company', 
            'site', 'sim', 'fuel_consumption_liter_per_km',
            'groupid', 'calendarid', 'expirationtime', 'balance', 'amount'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            echo json_encode(['error' => 'No fields to update']);
            exit();
        }
        
        $sql = "UPDATE tc_devices SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Device updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update device']);
        }
        exit();
    }
    
    // Toggle device status
    if ($_GET['ajax'] === 'toggle_status') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $disabled = isset($_GET['disabled']) ? intval($_GET['disabled']) : 0;
        
        $sql = "UPDATE tc_devices SET disabled = :disabled WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([':disabled' => $disabled, ':id' => $id])) {
            echo json_encode(['success' => true, 'message' => 'Device status updated']);
        } else {
            echo json_encode(['error' => 'Failed to update status']);
        }
        exit();
    }
}

// =========================
// MAIN QUERY - ALL COLUMNS
// =========================
$sql = "SELECT 
    id,
    name,
    uniqueid,
    lastupdate,
    positionid,
    groupid,
    attributes,
    phone,
    model,
    contact,
    category,
    disabled,
    status,
    expirationtime,
    motionstate,
    motiontime,
    motiondistance,
    overspeedstate,
    overspeedtime,
    overspeedgeofenceid,
    motionstreak,
    calendarid,
    motionpositionid,
    motionlatitude,
    motionlongitude,
    company,
    site,
    vehicle_type,
    fuel_consumption_liter_per_km,
    sim,
    data,
    amount,
    last_load,
    balance
FROM tc_devices 
ORDER BY name";

$stmt = $conn->prepare($sql);
$stmt->execute();
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalDevices = count($devices);
$activeDevices = 0;
$onlineDevices = 0;
$disabledDevices = 0;
$movingDevices = 0;
$overspeedDevices = 0;

foreach ($devices as $device) {
    if ($device['disabled'] == 0) $activeDevices++;
    if ($device['disabled'] == 1) $disabledDevices++;
    if (strtolower($device['status'] ?? '') === 'online') $onlineDevices++;
    if (($device['motionstate'] ?? 0) == 1) $movingDevices++;
    if (($device['overspeedstate'] ?? 0) == 1) $overspeedDevices++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Anubis - Complete Device Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #e0e0e0;
            overflow-x: hidden;
        }

        .anubis-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .anubis-header {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            border-radius: 20px;
            padding: 24px 30px;
            margin-bottom: 25px;
            border: 1px solid rgba(212, 175, 55, 0.15);
        }

        .header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .title-section h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #d4af37 0%, #f5e6a3 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .title-section p {
            color: #888;
            font-size: 14px;
        }

        .stats-section {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 12px 20px;
            text-align: center;
            border: 1px solid rgba(212,175,55,0.1);
            min-width: 100px;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: #d4af37;
        }

        .stat-card .label {
            font-size: 11px;
            color: #888;
            margin-top: 4px;
        }

        /* Search Bar */
        .anubis-search-bar {
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            border: 1px solid rgba(212,175,55,0.1);
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #d4af37;
        }

        #searchInput {
            width: 100%;
            padding: 12px 15px 12px 40px;
            background: #111;
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
        }

        #searchInput:focus {
            outline: none;
            border-color: rgba(212,175,55,0.6);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            background: #111;
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 12px;
            color: #ccc;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: rgba(212,175,55,0.15);
            border-color: rgba(212,175,55,0.5);
            color: #d4af37;
        }

        /* Table - Horizontal Scroll */
        .anubis-table-container {
            background: rgba(255,255,255,0.02);
            border-radius: 20px;
            border: 1px solid rgba(212,175,55,0.1);
            overflow-x: auto;
            overflow-y: visible;
        }

        .anubis-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            min-width: 1800px;
        }

        .anubis-table thead {
            background: rgba(0,0,0,0.5);
            border-bottom: 1px solid rgba(212,175,55,0.2);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .anubis-table th {
            padding: 14px 10px;
            text-align: left;
            font-weight: 600;
            color: #d4af37;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            white-space: nowrap;
        }

        .anubis-table th:hover {
            background: rgba(212,175,55,0.05);
        }

        .anubis-table td {
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
            white-space: nowrap;
        }

        .anubis-table tbody tr {
            transition: all 0.2s;
        }

        .anubis-table tbody tr:hover {
            background: rgba(212,175,55,0.05);
            cursor: pointer;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-online { background: rgba(34,197,94,0.2); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
        .badge-offline { background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
        .badge-active { background: rgba(34,197,94,0.2); color: #22c55e; }
        .badge-disabled { background: rgba(239,68,68,0.2); color: #ef4444; }
        .badge-moving { background: rgba(234,179,8,0.2); color: #eab308; }
        .badge-stopped { background: rgba(107,114,128,0.2); color: #9ca3af; }
        .badge-overspeed { background: rgba(239,68,68,0.3); color: #ef4444; }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #3a3a3a;
            transition: 0.3s;
            border-radius: 20px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider { background-color: #d4af37; }
        input:checked + .toggle-slider:before { transform: translateX(20px); }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #0f0f0f;
            border-radius: 24px;
            border: 1px solid rgba(212,175,55,0.3);
            max-width: 800px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(212,175,55,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: #0f0f0f;
        }
        .modal-header h3 { color: #d4af37; font-size: 20px; }
        .close-modal {
            background: none;
            border: none;
            color: #888;
            font-size: 28px;
            cursor: pointer;
        }
        .close-modal:hover { color: #d4af37; }
        .modal-body { padding: 24px; }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 12px;
        }
        .detail-item {
            display: flex;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 8px 0;
        }
        .detail-label {
            width: 140px;
            font-weight: 600;
            color: #d4af37;
            font-size: 12px;
        }
        .detail-value {
            flex: 1;
            color: #ccc;
            font-size: 12px;
            word-break: break-word;
        }
        
        .edit-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(212,175,55,0.15);
        }
        .edit-section h4 { color: #d4af37; margin-bottom: 15px; }
        .edit-field { margin-bottom: 12px; }
        .edit-field label { display: block; font-size: 12px; color: #888; margin-bottom: 5px; }
        .edit-field input, .edit-field select {
            width: 100%;
            padding: 10px;
            background: #111;
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 13px;
        }
        .save-btn {
            background: rgba(212,175,55,0.15);
            border: 1px solid rgba(212,175,55,0.3);
            padding: 10px 20px;
            border-radius: 12px;
            color: #d4af37;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .save-btn:hover { background: rgba(212,175,55,0.3); }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 20px;
            flex-wrap: wrap;
        }
        .page-btn {
            padding: 8px 14px;
            background: #111;
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 10px;
            color: #ccc;
            cursor: pointer;
        }
        .page-btn:hover, .page-btn.active {
            background: rgba(212,175,55,0.15);
            border-color: rgba(212,175,55,0.5);
            color: #d4af37;
        }

        /* Loading */
        .loading { text-align: center; padding: 40px; color: #d4af37; }

        /* Notification */
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 13px;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        .notification-success { background: rgba(34,197,94,0.9); color: white; }
        .notification-error { background: rgba(239,68,68,0.9); color: white; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        @media (max-width: 768px) {
            .anubis-container { padding: 10px; }
            .stat-card { padding: 8px 12px; min-width: 70px; }
            .stat-card .number { font-size: 18px; }
            .detail-grid { grid-template-columns: 1fr; }
            .detail-label { width: 120px; }
        }
    </style>
</head>
<body>

<div class="anubis-container">
    
    <!-- Header -->
    <div class="anubis-header">
        <div class="header-title">
            <div class="title-section">
                <h1><i class="fas fa-microchip"></i> Complete Device Details</h1>
                <p>Full device information</p>
            </div>
            <div class="stats-section">
                <div class="stat-card"><div class="number"><?= $totalDevices ?></div><div class="label">Total</div></div>
                <div class="stat-card"><div class="number"><?= $activeDevices ?></div><div class="label">Active</div></div>
                <div class="stat-card"><div class="number"><?= $disabledDevices ?></div><div class="label">Disabled</div></div>
                <div class="stat-card"><div class="number"><?= $onlineDevices ?></div><div class="label">Online</div></div>
                <div class="stat-card"><div class="number"><?= $movingDevices ?></div><div class="label">Moving</div></div>
                <div class="stat-card"><div class="number"><?= $overspeedDevices ?></div><div class="label">Overspeed</div></div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="anubis-search-bar">
        <div class="search-input-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search any field...">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="active">Active</button>
            <button class="filter-btn" data-filter="disabled">Disabled</button>
            <button class="filter-btn" data-filter="online">Online</button>
            <button class="filter-btn" data-filter="offline">Offline</button>
            <button class="filter-btn" data-filter="moving">Moving</button>
            <button class="filter-btn" data-filter="overspeed">Overspeed</button>
        </div>
    </div>
    
    <!-- Table - ALL COLUMNS -->
    <div class="anubis-table-container">
        <table class="anubis-table" id="devicesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th data-sort="name">Name</th>
                    <th data-sort="uniqueid">Unique ID</th>
                    <th>Status</th>
                    <th>Enabled</th>
                    <th data-sort="phone">Phone</th>
                    <th data-sort="model">Model</th>
                    <th data-sort="vehicle_type">Vehicle Type</th>
                    <th data-sort="company">Company</th>
                    <th data-sort="site">Site</th>
                    <th>Motion</th>
                    <th>Motion Dist</th>
                    <th>Overspeed</th>
                    <th>Last Update</th>
                    <th>Expiration</th>
                    <th>Balance</th>
                    <th>SIM</th>
                    <th>Contact</th>
                    <th>Category</th>
                    <th>Group ID</th>
                    <th>Calendar ID</th>
                    <th>Fuel Consump</th>
                    <th>Amount</th>
                    <th>Last Load</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach($devices as $device): 
                    $isOnline = strtolower($device['status'] ?? '') === 'online';
                    $isDisabled = $device['disabled'] == 1;
                    $isMoving = ($device['motionstate'] ?? 0) == 1;
                    $isOverspeed = ($device['overspeedstate'] ?? 0) == 1;
                    $lastupdate = !empty($device['lastupdate']) ? date('Y-m-d H:i', strtotime($device['lastupdate'])) : 'N/A';
                    $expiration = !empty($device['expirationtime']) ? date('Y-m-d', strtotime($device['expirationtime'])) : 'N/A';
                    
                    $typeIcons = ['Truck' => '🚛', 'Van' => '🚐', 'Car' => '🚗', 'SUV' => '🚙', 'Bus' => '🚌', 'Motorcycle' => '🏍️'];
                    $icon = $typeIcons[$device['vehicle_type']] ?? '🚗';
                ?>
                <tr data-device-id="<?= $device['id'] ?>" 
                    data-status="<?= $isOnline ? 'online' : 'offline' ?>"
                    data-disabled="<?= $isDisabled ? 1 : 0 ?>"
                    data-moving="<?= $isMoving ? 1 : 0 ?>"
                    data-overspeed="<?= $isOverspeed ? 1 : 0 ?>"
                    data-name="<?= strtolower(htmlspecialchars($device['name'] ?? '')) ?>"
                    data-uniqueid="<?= htmlspecialchars($device['uniqueid'] ?? '') ?>"
                    data-phone="<?= htmlspecialchars($device['phone'] ?? '') ?>"
                    data-model="<?= htmlspecialchars($device['model'] ?? '') ?>"
                    data-company="<?= htmlspecialchars($device['company'] ?? '') ?>"
                    data-site="<?= htmlspecialchars($device['site'] ?? '') ?>">
                    
                    <td><?= $device['id'] ?></td>
                    <td><strong><?= htmlspecialchars($device['name'] ?: 'N/A') ?></strong></td>
                    <td style="font-family: monospace; font-size: 11px;"><?= htmlspecialchars($device['uniqueid'] ?: 'N/A') ?></td>
                    <td><span class="badge <?= $isOnline ? 'badge-online' : 'badge-offline' ?>"><?= $isOnline ? 'ONLINE' : 'OFFLINE' ?></span></td>
                    <td><label class="toggle-switch"><input type="checkbox" class="device-toggle" data-id="<?= $device['id'] ?>" <?= !$isDisabled ? 'checked' : '' ?>><span class="toggle-slider"></span></label></td>
                    <td><?= htmlspecialchars($device['phone'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($device['model'] ?: 'N/A') ?></td>
                    <td><?= $icon ?> <?= htmlspecialchars($device['vehicle_type'] ?: 'Car') ?></td>
                    <td><?= htmlspecialchars($device['company'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($device['site'] ?: 'N/A') ?></td>
                    <td><span class="badge <?= $isMoving ? 'badge-moving' : 'badge-stopped' ?>"><?= $isMoving ? '🚚 Moving' : '🅿️ Stopped' ?></span></td>
                    <td><?= number_format($device['motiondistance'] ?? 0, 1) ?> km</td>
                    <td><?php if($isOverspeed): ?><span class="badge badge-overspeed">⚠️ Overspeed</span><?php else: ?>-<?php endif; ?></td>
                    <td style="font-size: 11px;"><?= $lastupdate ?></td>
                    <td style="font-size: 11px;"><?= $expiration ?></td>
                    <td><?= number_format($device['balance'] ?? 0, 2) ?></td>
                    <td><?= htmlspecialchars($device['sim'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($device['contact'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($device['category'] ?: 'N/A') ?></td>
                    <td><?= $device['groupid'] ?: '-' ?></td>
                    <td><?= $device['calendarid'] ?: '-' ?></td>
                    <td><?= $device['fuel_consumption_liter_per_km'] ?: '-' ?></td>
                    <td><?= number_format($device['amount'] ?? 0, 2) ?></td>
                    <td><?= !empty($device['last_load']) ? date('Y-m-d', strtotime($device['last_load'])) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="pagination" id="pagination"></div>
</div>

<!-- Device Details Modal -->
<div id="deviceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-truck"></i> Complete Device Details</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>

<script>
// =========================
// COMPLETE DEVICE MANAGEMENT
// =========================

let currentDevices = [];
let currentFilter = 'all';
let currentPage = 1;
const itemsPerPage = 20;

document.addEventListener('DOMContentLoaded', function() {
    loadDeviceData();
    setupEventListeners();
});

function loadDeviceData() {
    const rows = document.querySelectorAll('#tableBody tr');
    currentDevices = Array.from(rows).map(row => ({
        id: row.dataset.deviceId,
        name: row.dataset.name,
        uniqueid: row.dataset.uniqueid,
        phone: row.dataset.phone,
        model: row.dataset.model,
        company: row.dataset.company,
        site: row.dataset.site,
        status: row.dataset.status,
        disabled: row.dataset.disabled,
        moving: row.dataset.moving,
        overspeed: row.dataset.overspeed,
        element: row,
        online: row.dataset.status === 'online',
        active: row.dataset.disabled === '0'
    }));
    filterAndRender();
}

function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', function() { currentPage = 1; filterAndRender(); });
    
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            currentPage = 1;
            filterAndRender();
        });
    });
    
    document.querySelectorAll('.device-toggle').forEach(toggle => {
        toggle.addEventListener('change', function(e) {
            e.stopPropagation();
            toggleDeviceStatus(this.dataset.id, this.checked ? 0 : 1);
        });
    });
    
    document.querySelectorAll('#tableBody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('.toggle-switch')) {
                showDeviceDetails(this.dataset.deviceId);
            }
        });
    });
    
    document.querySelector('.close-modal').addEventListener('click', closeModal);
    window.addEventListener('click', function(e) { if (e.target === document.getElementById('deviceModal')) closeModal(); });
}

function filterAndRender() {
    let filtered = [...currentDevices];
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    if (searchTerm) {
        filtered = filtered.filter(device => 
            device.name.includes(searchTerm) ||
            device.uniqueid.includes(searchTerm) ||
            device.phone.includes(searchTerm) ||
            device.model.includes(searchTerm) ||
            device.company.includes(searchTerm) ||
            device.site.includes(searchTerm)
        );
    }
    
    switch(currentFilter) {
        case 'active': filtered = filtered.filter(d => d.active); break;
        case 'disabled': filtered = filtered.filter(d => !d.active); break;
        case 'online': filtered = filtered.filter(d => d.online); break;
        case 'offline': filtered = filtered.filter(d => !d.online); break;
        case 'moving': filtered = filtered.filter(d => d.moving == 1); break;
        case 'overspeed': filtered = filtered.filter(d => d.overspeed == 1); break;
    }
    
    const totalPages = Math.ceil(filtered.length / itemsPerPage);
    const start = (currentPage - 1) * itemsPerPage;
    const pageItems = filtered.slice(start, start + itemsPerPage);
    
    document.querySelectorAll('#tableBody tr').forEach(row => row.style.display = 'none');
    pageItems.forEach(item => { if (item.element) item.element.style.display = ''; });
    
    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const paginationDiv = document.getElementById('pagination');
    if (totalPages <= 1) { paginationDiv.innerHTML = ''; return; }
    
    let html = '';
    for (let i = 1; i <= Math.min(totalPages, 10); i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }
    paginationDiv.innerHTML = html;
    document.querySelectorAll('.page-btn').forEach(btn => {
        btn.addEventListener('click', function() { currentPage = parseInt(this.dataset.page); filterAndRender(); });
    });
}

async function toggleDeviceStatus(deviceId, disabled) {
    try {
        const response = await fetch(`/anubis/pages/device_management.php?ajax=toggle_status&id=${deviceId}&disabled=${disabled}`);
        const data = await response.json();
        if (data.success) {
            const device = currentDevices.find(d => d.id == deviceId);
            if (device) { device.active = disabled === 0; device.disabled = disabled; }
            filterAndRender();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        showNotification('Failed to update device status', 'error');
    }
}

async function showDeviceDetails(deviceId) {
    const modal = document.getElementById('deviceModal');
    const modalBody = document.getElementById('modalBody');
    modal.style.display = 'flex';
    modalBody.innerHTML = '<div class="loading">Loading device details...</div>';
    
    try {
        const response = await fetch(`/anubis/pages/device_management.php?ajax=get_device&id=${deviceId}`);
        const data = await response.json();
        if (data.success) {
            renderCompleteDeviceDetails(data.device);
        } else {
            modalBody.innerHTML = `<div class="loading">${data.error}</div>`;
        }
    } catch (error) {
        modalBody.innerHTML = '<div class="loading">Failed to load device details</div>';
    }
}

function renderCompleteDeviceDetails(device) {
    const format = (val) => val && val !== '' && val !== null ? val : '<span style="color:#666;">N/A</span>';
    const formatDate = (date) => date ? new Date(date).toLocaleString() : '<span style="color:#666;">N/A</span>';
    const formatNumber = (num) => num ? Number(num).toLocaleString() : '0';
    
    const html = `
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">ID:</div><div class="detail-value">${device.id}</div></div>
            <div class="detail-item"><div class="detail-label">Name:</div><div class="detail-value"><strong>${format(device.name)}</strong></div></div>
            <div class="detail-item"><div class="detail-label">Unique ID:</div><div class="detail-value"><code>${format(device.uniqueid)}</code></div></div>
            <div class="detail-item"><div class="detail-label">Status:</div><div class="detail-value"><span class="badge ${device.status === 'online' ? 'badge-online' : 'badge-offline'}">${device.status || 'UNKNOWN'}</span></div></div>
            <div class="detail-item"><div class="detail-label">Disabled:</div><div class="detail-value">${device.disabled == 1 ? 'Yes' : 'No'}</div></div>
            <div class="detail-item"><div class="detail-label">Phone:</div><div class="detail-value">${format(device.phone)}</div></div>
            <div class="detail-item"><div class="detail-label">Model:</div><div class="detail-value">${format(device.model)}</div></div>
            <div class="detail-item"><div class="detail-label">Contact:</div><div class="detail-value">${format(device.contact)}</div></div>
            <div class="detail-item"><div class="detail-label">Category:</div><div class="detail-value">${format(device.category)}</div></div>
            <div class="detail-item"><div class="detail-label">Vehicle Type:</div><div class="detail-value">${format(device.vehicle_type)}</div></div>
            <div class="detail-item"><div class="detail-label">Company:</div><div class="detail-value">${format(device.company)}</div></div>
            <div class="detail-item"><div class="detail-label">Site:</div><div class="detail-value">${format(device.site)}</div></div>
            <div class="detail-item"><div class="detail-label">SIM:</div><div class="detail-value">${format(device.sim)}</div></div>
            <div class="detail-item"><div class="detail-label">Group ID:</div><div class="detail-value">${device.groupid ?? '-'}</div></div>
            <div class="detail-item"><div class="detail-label">Calendar ID:</div><div class="detail-value">${device.calendarid ?? '-'}</div></div>
            <div class="detail-item"><div class="detail-label">Fuel Consumption:</div><div class="detail-value">${device.fuel_consumption_liter_per_km ?? '-'} L/km</div></div>
            <div class="detail-item"><div class="detail-label">Motion State:</div><div class="detail-value"><span class="badge ${device.motionstate == 1 ? 'badge-moving' : 'badge-stopped'}">${device.motionstate == 1 ? 'Moving' : 'Stopped'}</span></div></div>
            <div class="detail-item"><div class="detail-label">Motion Time:</div><div class="detail-value">${formatDate(device.motiontime)}</div></div>
            <div class="detail-item"><div class="detail-label">Motion Distance:</div><div class="detail-value">${formatNumber(device.motiondistance)} km</div></div>
            <div class="detail-item"><div class="detail-label">Motion Streak:</div><div class="detail-value">${device.motionstreak ?? 0}</div></div>
            <div class="detail-item"><div class="detail-label">Motion Position ID:</div><div class="detail-value">${device.motionpositionid ?? '-'}</div></div>
            <div class="detail-item"><div class="detail-label">Motion Latitude:</div><div class="detail-value">${device.motionlatitude ?? '-'}</div></div>
            <div class="detail-item"><div class="detail-label">Motion Longitude:</div><div class="detail-value">${device.motionlongitude ?? '-'}</div></div>
            <div class="detail-item"><div class="detail-label">Overspeed State:</div><div class="detail-value">${device.overspeedstate == 1 ? '<span class="badge badge-overspeed">Overspeed</span>' : 'Normal'}</div></div>
            <div class="detail-item"><div class="detail-label">Overspeed Time:</div><div class="detail-value">${formatDate(device.overspeedtime)}</div></div>
            <div class="detail-item"><div class="detail-label">Overspeed Geofence ID:</div><div class="detail-value">${device.overspeedgeofenceid ?? '-'}</div></div>
            <div class="detail-item"><div class="detail-label">Position ID:</div><div class="detail-value">${device.positionid ?? '-'}</div></div>
            <div class="detail-item"><div class="detail-label">Last Update:</div><div class="detail-value">${formatDate(device.lastupdate)}</div></div>
            <div class="detail-item"><div class="detail-label">Expiration Time:</div><div class="detail-value">${formatDate(device.expirationtime)}</div></div>
            <div class="detail-item"><div class="detail-label">Balance:</div><div class="detail-value">${formatNumber(device.balance)}</div></div>
            <div class="detail-item"><div class="detail-label">Amount:</div><div class="detail-value">${formatNumber(device.amount)}</div></div>
            <div class="detail-item"><div class="detail-label">Last Load:</div><div class="detail-value">${formatDate(device.last_load)}</div></div>
            <div class="detail-item"><div class="detail-label">Data:</div><div class="detail-value"><small>${device.data ? json_decode($device['data'] ?? '{}') : '-'}</small></div></div>
            <div class="detail-item"><div class="detail-label">Attributes:</div><div class="detail-value"><small>${device.attributes ? JSON.stringify(device.attributes_decoded || JSON.parse(device.attributes || '{}')) : '-'}</small></div></div>
        </div>
        
        <div class="edit-section">
            <h4><i class="fas fa-edit"></i> Quick Edit</h4>
            <form id="editDeviceForm">
                <input type="hidden" name="id" value="${device.id}">
                <div class="edit-field"><label>Device Name</label><input type="text" name="name" value="${escapeHtml(device.name || '')}"></div>
                <div class="edit-field"><label>Phone</label><input type="text" name="phone" value="${escapeHtml(device.phone || '')}"></div>
                <div class="edit-field"><label>Vehicle Type</label>
                    <select name="vehicle_type">
                        <option value="Truck" ${device.vehicle_type === 'Truck' ? 'selected' : ''}>🚛 Truck</option>
                        <option value="Van" ${device.vehicle_type === 'Van' ? 'selected' : ''}>🚐 Van</option>
                        <option value="Car" ${device.vehicle_type === 'Car' ? 'selected' : ''}>🚗 Car</option>
                        <option value="SUV" ${device.vehicle_type === 'SUV' ? 'selected' : ''}>🚙 SUV</option>
                        <option value="Bus" ${device.vehicle_type === 'Bus' ? 'selected' : ''}>🚌 Bus</option>
                    </select>
                </div>
                <div class="edit-field"><label>Company</label><input type="text" name="company" value="${escapeHtml(device.company || '')}"></div>
                <div class="edit-field"><label>Site</label><input type="text" name="site" value="${escapeHtml(device.site || '')}"></div>
                <div class="edit-field"><label>Contact</label><input type="text" name="contact" value="${escapeHtml(device.contact || '')}"></div>
                <div class="edit-field"><label>SIM</label><input type="text" name="sim" value="${escapeHtml(device.sim || '')}"></div>
                <div class="edit-field"><label>Fuel Consumption (L/km)</label><input type="number" step="0.1" name="fuel_consumption_liter_per_km" value="${device.fuel_consumption_liter_per_km || ''}"></div>
                <div class="edit-field"><label>Balance</label><input type="number" step="0.01" name="balance" value="${device.balance || 0}"></div>
                <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    `;
    
    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('editDeviceForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        try {
            const response = await fetch('/anubis/pages/device_management.php?ajax=update_device', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showNotification('Device updated successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(result.error, 'error');
            }
        } catch (error) {
            showNotification('Failed to update device', 'error');
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function closeModal() {
    document.getElementById('deviceModal').style.display = 'none';
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

</body>
</html>