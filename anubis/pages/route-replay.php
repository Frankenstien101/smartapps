<?php
require_once "./DB/dbcon.php";

// Get all devices for dropdown
$sql_devices = "SELECT id, name, uniqueid, vehicle_type FROM tc_devices ORDER BY name";
$stmt_devices = $conn->prepare($sql_devices);
$stmt_devices->execute();
$devices = $stmt_devices->fetchAll(PDO::FETCH_ASSOC);

// Get selected device and dates
$selected_device = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d') . 'T05:00';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d') . 'T17:00';

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date .= 'T05:00';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $end_date .= 'T17:00';
}

$start_date_sql = str_replace('T', ' ', $start_date);
$end_date_sql = str_replace('T', ' ', $end_date);
if (strlen($start_date_sql) === 16) {
    $start_date_sql .= ':00';
}
if (strlen($end_date_sql) === 16) {
    $end_date_sql .= ':00';
}

// Get device name for the selected device
$device_name = '';
if ($selected_device > 0) {
    $sql_dev = "SELECT name, vehicle_type FROM tc_devices WHERE id = ?";
    $stmt_dev = $conn->prepare($sql_dev);
    $stmt_dev->execute([$selected_device]);
    $device_info = $stmt_dev->fetch(PDO::FETCH_ASSOC);
    $device_name = $device_info ? $device_info['name'] : '';
    $vehicle_type = $device_info ? $device_info['vehicle_type'] : 'Car';
} else {
    $vehicle_type = 'Car';
}

// Fetch positions for selected device
$positions = [];
if ($selected_device > 0) {
    $sql_pos = "SELECT 
                    p.id,
                    p.latitude,
                    p.longitude,
                    p.speed,
                    p.address,
                    p.fixtime,
                    p.attributes
                FROM tc_positions p
                WHERE p.deviceid = ? 
                AND p.fixtime BETWEEN ? AND ?
                AND p.latitude IS NOT NULL 
                AND p.longitude IS NOT NULL
                ORDER BY p.fixtime ASC";
    
    $stmt_pos = $conn->prepare($sql_pos);
    $stmt_pos->execute([$selected_device, $start_date_sql, $end_date_sql]);
    $positions = $stmt_pos->fetchAll(PDO::FETCH_ASSOC);
    
    $filtered_positions = [];
    $last_activity = null;
    $still_count = 0;
    
    foreach ($positions as $position) {
        $attributes = json_decode($position['attributes'], true);
        $current_activity = $attributes['activity'] ?? null;
        
        if ($current_activity === 'still') {
            if ($last_activity !== 'still') {
                $filtered_positions[] = $position;
            }
        } else {
            $filtered_positions[] = $position;
        }
        
        $last_activity = $current_activity;
    }
    
    $positions = $filtered_positions;
}

// Vehicle type icons mapping
$vehicleIcons = [
    'Truck' => '🚛',
    'Van' => '🚐',
    'Car' => '🚗',
    'SUV' => '🚙',
    'Bus' => '🚌',
    'Motorcycle' => '🏍️',
    'default' => '🚗'
];

// Vehicle type image URLs for map markers
$vehicleImages = [
    'Truck' => 'https://cdn-icons-png.flaticon.com/512/936/936810.png',
    'Van' => 'https://cdn-icons-png.flaticon.com/512/743/743007.png',
    'Car' => 'https://cdn-icons-png.flaticon.com/512/744/744465.png',
    'SUV' => 'https://cdn-icons-png.flaticon.com/512/741/741407.png',
    'Bus' => 'https://cdn-icons-png.flaticon.com/512/61/61168.png',
    'Motorcycle' => 'https://cdn-icons-png.flaticon.com/512/10771/10771849.png',
    'default' => 'https://cdn-icons-png.flaticon.com/512/744/744465.png'
];

// Get the icon for the selected vehicle
$selectedVehicleIcon = $vehicleIcons[$vehicle_type] ?? $vehicleIcons['default'];
$selectedVehicleImage = $vehicleImages[$vehicle_type] ?? $vehicleImages['default'];
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
    /* ── Scope everything under #route-replay-root so we don't bleed into home.php ── */
    #route-replay-root {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: calc(95vh - 60px);
        min-height: 0;
        background: #0a0a0f;
        color: #fff;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow: hidden;
        position: relative;
    }

    /* Custom Zoom Controls - Right Side Styling */
    #route-replay-root .custom-zoom-controls {
        position: absolute;
        top: 90px;
        right: 20px;
        z-index: 400;
        display: flex;
        flex-direction: column;
        gap: 8px;
        background: rgba(10, 10, 15, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 8px;
        border: 1px solid rgba(212, 175, 55, 0.3);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    #route-replay-root .zoom-btn {
        width: 40px;
        height: 40px;
        background: rgba(0, 0, 0, 0.7);
        border: 1px solid rgba(212, 175, 55, 0.4);
        border-radius: 8px;
        color: #d4af37;
        font-size: 20px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    #route-replay-root .zoom-btn:hover {
        background: rgba(212, 175, 55, 0.2);
        border-color: #d4af37;
        transform: scale(1.05);
    }

    /* Keep the moving vehicle marker above map tiles */
    #route-replay-root .moving-marker {
        z-index: 99999 !important;
        pointer-events: none;
    }

    /* Map Layer Controls - Right Side */
    #route-replay-root .layer-controls {
        position: absolute;
        top: 10px;
        right: 20px;
        z-index: 400;
        background: rgba(10, 10, 15, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        border: 1px solid rgba(212, 175, 55, 0.3);
        padding: 8px;
        min-width: 160px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    #route-replay-root .layer-toggle {
        width: 100%;
        background: rgba(212, 175, 55, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 8px 12px;
        color: #d4af37;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    #route-replay-root .layer-toggle:hover {
        background: rgba(212, 175, 55, 0.2);
        border-color: #d4af37;
    }

    #route-replay-root .layer-dropdown {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        margin-top: 8px;
        border-radius: 8px;
    }

    #route-replay-root .layer-dropdown.open {
        max-height: 200px;
    }

    #route-replay-root .layer-option {
        width: 100%;
        background: rgba(0, 0, 0, 0.6);
        border: none;
        border-radius: 6px;
        padding: 6px 10px;
        margin-bottom: 4px;
        color: #ccc;
        font-size: 11px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s ease;
    }

    #route-replay-root .layer-option:hover {
        background: rgba(212, 175, 55, 0.15);
        color: #d4af37;
        transform: translateX(3px);
    }

    #route-replay-root .layer-option.active {
        background: rgba(212, 175, 55, 0.25);
        color: #d4af37;
        border-left: 2px solid #d4af37;
    }

    /* Hide default Leaflet zoom control */
    #route-replay-root .leaflet-control-zoom {
        display: none !important;
    }

    /* Force Leaflet controls inside map container */
    #route-replay-root .leaflet-control-container {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    /* Re-enable clicking ONLY on controls */
    #route-replay-root .leaflet-control {
        pointer-events: auto;
    }

    /* Ensure other map elements remain interactive */
    #route-replay-root .leaflet-pane,
    #route-replay-root .leaflet-top,
    #route-replay-root .leaflet-bottom {
        z-index: auto;
    }

    /* ── Control Bar (replaces replay-header) ─────────────────── */
    #route-replay-root .rr-controls {
        flex-shrink: 0;
        background: rgba(10, 10, 15, 0.98);
        border-bottom: 1px solid rgba(212, 175, 55, 0.25);
        padding: 10px 18px;
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        z-index: 100;
    }

    #route-replay-root .rr-title {
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin-right: 8px;
    }

    #route-replay-root .rr-title h2 {
        font-size: 16px;
        color: #d4af37;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    #route-replay-root .rr-title p {
        font-size: 10px;
        color: #666;
        margin: 2px 0 0 0;
    }

    #route-replay-root .rr-field {
        display: flex;
        flex-direction: column;
        gap: 3px;
        position: relative;
    }

    #route-replay-root .rr-field label {
        font-size: 10px;
        color: #d4af37;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 600;
    }

    /* Searchable Select Styles */
    #route-replay-root .searchable-select {
        position: relative;
        min-width: 220px;
    }

    #route-replay-root .searchable-select-input {
        background: rgba(0, 0, 0, 0.7);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 5px 30px 5px 10px;
        color: #fff;
        font-size: 12px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.2s;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #route-replay-root .searchable-select-input:hover,
    #route-replay-root .searchable-select-input:focus {
        border-color: rgba(212, 175, 55, 0.7);
    }

    #route-replay-root .searchable-select-arrow {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #d4af37;
        font-size: 12px;
        pointer-events: none;
    }

    #route-replay-root .searchable-select-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(10, 10, 15, 0.98);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        margin-top: 4px;
        max-height: 250px;
        overflow-y: auto;
        z-index: 10000;
        display: none;
    }

    #route-replay-root .searchable-select-dropdown.open {
        display: block;
    }

    #route-replay-root .searchable-select-search {
        padding: 8px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        position: sticky;
        top: 0;
        background: rgba(10, 10, 15, 0.98);
        z-index: 1000;
    }

    #route-replay-root .searchable-select-search input {
        width: 100%;
        background: rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 6px;
        padding: 6px 10px;
        color: #fff;
        font-size: 12px;
        outline: none;
    }

    #route-replay-root .searchable-select-search input:focus {
        border-color: rgba(212, 175, 55, 0.7);
    }

    #route-replay-root .searchable-select-options {
        max-height: 180px;
        overflow-y: auto;
    }

    #route-replay-root .searchable-select-option {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 12px;
        color: #ccc;
        transition: all 0.15s ease;
        border-bottom: 1px solid rgba(212, 175, 55, 0.05);
    }

    #route-replay-root .searchable-select-option:hover {
        background: rgba(212, 175, 55, 0.1);
        color: #d4af37;
    }

    #route-replay-root .searchable-select-option.selected {
        background: rgba(212, 175, 55, 0.2);
        color: #d4af37;
        font-weight: 500;
    }

    #route-replay-root .searchable-select-option.hidden {
        display: none;
    }

    #route-replay-root .rr-field select,
    #route-replay-root input[type="date"],
    #route-replay-root input[type="datetime-local"] {
        background: rgba(0, 0, 0, 0.7);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 10px 12px;
        color: #fff;
        font-size: 12px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    }

    #route-replay-root select:hover,
    #route-replay-root input[type="date"]:hover,
    #route-replay-root input[type="datetime-local"]:hover,
    #route-replay-root select:focus,
    #route-replay-root input[type="date"]:focus,
    #route-replay-root input[type="datetime-local"]:focus {
        border-color: rgba(212, 175, 55, 0.8);
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.08);
    }

    #route-replay-root .rr-btn-load {
        background: linear-gradient(135deg, #d4af37, #b8941f);
        border: none;
        border-radius: 8px;
        padding: 6px 18px;
        color: #000;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: opacity 0.2s, transform 0.15s;
        align-self: flex-end;
    }

    #route-replay-root .rr-btn-load:hover {
        opacity: 0.88;
        transform: translateY(-1px);
    }

    /* ── Stats strip ──────────────────────────────────────────── */
    #route-replay-root .rr-stats {
        flex-shrink: 0;
        background: rgba(0, 0, 0, 0.45);
        border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        padding: 6px 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        font-size: 11px;
        color: #ccc;
    }

    #route-replay-root .rr-stats .s-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #route-replay-root .rr-stats .s-item i {
        color: #d4af37;
        font-size: 12px;
    }

    #route-replay-root .rr-stats .s-item strong {
        color: #d4af37;
    }

    /* ── Map area ────────────────────────────────────────────── */
    #route-replay-root .rr-map-wrap {
        flex: 1;
        min-height: 0;
        position: relative;
        margin-left: 0;
        transition: margin-left 0.3s ease;
    }

    #route-replay-root .rr-map-wrap.sidebar-visible {
        margin-left: 0px;
    }

    #route-replay-root #rr-map {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }

    /* FLOATING SIDEBAR TOGGLE BUTTON */
    #route-replay-root .floating-sidebar-btn {
        position: absolute;
        bottom: 20px;
        left: 20px;
        z-index: 450;
        background: linear-gradient(135deg, #d4af37, #b8941f);
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        color: #0a0a0f;
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        transition: all 0.2s ease;
        backdrop-filter: blur(5px);
    }

    #route-replay-root .floating-sidebar-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3);
    }

    /* ── Timeline Items in Sidebar (DETAILED BUTTONS) ── */
    #route-replay-root .tl-detailed-btn {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 8px 12px;
        background: rgba(212, 175, 55, 0.08);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 6px;
    }

    #route-replay-root .tl-detailed-btn:hover {
        background: rgba(212, 175, 55, 0.15);
        border-color: #d4af37;
        transform: translateX(3px);
    }

    #route-replay-root .tl-detailed-btn.active-pill {
        background: rgba(212, 175, 55, 0.25);
        border-color: #d4af37;
        border-left: 3px solid #d4af37;
    }

    #route-replay-root .tl-time {
        color: #d4af37;
        font-weight: 600;
        font-size: 11px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #route-replay-root .tl-time i {
        font-size: 10px;
    }

    #route-replay-root .tl-details {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 10px;
        color: #aaa;
    }

    #route-replay-root .tl-details span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    #route-replay-root .tl-details i {
        font-size: 9px;
        color: #4ecdc4;
    }

    #route-replay-root .tl-details strong {
        color: #d4af37;
    }

    /* Waypoints Toggle Button for Mobile */
    #route-replay-root .toggle-waypoints-btn {
        background: rgba(212, 175, 55, 0.15);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 6px 10px;
        color: #d4af37;
        font-size: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    #route-replay-root .toggle-waypoints-btn:hover {
        background: rgba(212, 175, 55, 0.25);
    }

    /* ── Sidebar Control Panel (Desktop: left, Mobile: bottom sheet) ── */
    #route-replay-root .rr-slider-panel {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 380px;
        background: rgba(8, 8, 13, 0.98);
        backdrop-filter: blur(18px);
        border-right: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 0;
        padding: 0;
        z-index: 30;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.4);
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease;
        transform: translateX(-100%);
    }

    #route-replay-root .rr-slider-panel.visible {
        transform: translateX(0);
    }

    /* Mobile Styles - Sidebar as Bottom Sheet */
    @media (max-width: 768px) {
        #route-replay-root .rr-slider-panel {
            top: auto;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: auto;
            max-height: 80vh;
            border-right: none;
            border-top: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 20px 20px 0 0;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
        }
        
        #route-replay-root .rr-slider-panel.visible {
            transform: translateY(0);
        }
        
        #route-replay-root .sp-header {
            padding: 12px 16px;
            cursor: pointer;
            border-radius: 20px 20px 0 0;
        }
        
        #route-replay-root .sp-header h3 {
            font-size: 14px;
        }
        
        #route-replay-root .sp-content {
            max-height: calc(80vh - 60px);
            overflow-y: auto;
        }
        
        #route-replay-root .floating-sidebar-btn {
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            right: auto;
            padding: 10px 18px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 460;
        }
        
        #route-replay-root .floating-sidebar-btn.hide-btn-mobile {
            bottom: auto;
            top: 10px;
            left: 10px;
            transform: none;
            background: rgba(0,0,0,0.85);
            padding: 8px 12px;
            font-size: 11px;
        }
        
        #route-replay-root .custom-zoom-controls { top: 80px; right: 20px; }
        #route-replay-root .layer-controls { top: 10px; right: 20px; min-width: 140px; }
        #route-replay-root .searchable-select { min-width: 180px; }
        #route-replay-root .rr-controls { padding: 8px 12px; gap: 8px; }
        #route-replay-root { height: calc(100vh - 55px); }
        
        /* Waypoints section header with toggle */
        #route-replay-root .waypoints-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
    }

    #route-replay-root .sp-header {
        flex-shrink: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        gap: 8px;
    }

    #route-replay-root .sp-header h3 {
        font-size: 12px;
        color: #d4af37;
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 700;
    }

    #route-replay-root .sp-close-btn {
        background: none;
        border: none;
        color: #d4af37;
        cursor: pointer;
        font-size: 18px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, color 0.2s ease;
    }

    #route-replay-root .sp-close-btn:hover {
        transform: scale(1.15);
        color: #fff;
    }

    #route-replay-root .sp-time {
        font-size: 11px;
        color: #ccc;
        background: rgba(255,255,255,0.06);
        padding: 3px 10px;
        border-radius: 20px;
    }

    #route-replay-root .sp-time span {
        color: #d4af37;
        font-weight: 600;
    }

    #route-replay-root .sp-content {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    #route-replay-root .sp-content::-webkit-scrollbar {
        width: 6px;
    }

    #route-replay-root .sp-content::-webkit-scrollbar-track {
        background: rgba(212, 175, 55, 0.05);
        border-radius: 3px;
    }

    #route-replay-root .sp-content::-webkit-scrollbar-thumb {
        background: rgba(212, 175, 55, 0.3);
        border-radius: 3px;
    }

    #route-replay-root .sp-content::-webkit-scrollbar-thumb:hover {
        background: rgba(212, 175, 55, 0.5);
    }

    #route-replay-root input[type="range"] {
        width: 100%;
        height: 4px;
        -webkit-appearance: none;
        background: rgba(212, 175, 55, 0.25);
        border-radius: 4px;
        outline: none;
        margin: 4px 0;
        cursor: pointer;
    }

    #route-replay-root input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        background: #d4af37;
        border-radius: 50%;
        box-shadow: 0 0 8px rgba(212, 175, 55, 0.7);
        cursor: pointer;
    }

    #route-replay-root .sp-btns {
        display: flex;
        gap: 8px;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    #route-replay-root .sp-btns button {
        flex: 1;
        background: rgba(212, 175, 55, 0.12);
        border: 1px solid rgba(212, 175, 55, 0.28);
        border-radius: 6px;
        padding: 8px 10px;
        color: #d4af37;
        font-size: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    #route-replay-root .sp-btns button:hover {
        background: rgba(212, 175, 55, 0.28);
        transform: translateY(-2px);
    }

    /* ── Speedometer Analog ──────────────────────────────────── */
    #route-replay-root .speedometer-container {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 12px;
    }

    #route-replay-root .speedometer-gauge {
        width: 120px;
        height: 120px;
        position: relative;
        flex-shrink: 0;
    }

    #route-replay-root .speedometer-gauge svg {
        width: 100%;
        height: 100%;
        filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
    }

    #route-replay-root .speedometer-needle {
        transform-origin: 100px 100px;
        transform: rotate(0deg);
        transition: transform 0.3s ease-out;
    }

    #route-replay-root .speedometer-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    #route-replay-root .speedometer-info > div {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    #route-replay-root .speedometer-info div {
        font-size: 11px;
        color: #ccc;
    }

    #route-replay-root .speedometer-info .speed-value {
        font-size: 18px;
        font-weight: bold;
        color: #d4af37;
    }

    #route-replay-root .speedometer-info .metric-label {
        font-size: 9px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #route-replay-root .speedometer-info .metric-value {
        font-size: 14px;
        font-weight: 600;
        color: #4ecdc4;
    }

    /* Vehicle image grayscale when ignition is off */
    #route-replay-root .moving-marker-icon.ignition-off img {
        filter: grayscale(100%) brightness(0.7) !important;
    }

    /* ── Empty state ─────────────────────────────────────────── */
    #route-replay-root .rr-empty {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #555;
        pointer-events: none;
        z-index: 5;
    }

    #route-replay-root .rr-empty i { font-size: 48px; color: rgba(212,175,55,0.2); }
    #route-replay-root .rr-empty p { font-size: 14px; }
</style>

<div id="route-replay-root">

    <!-- Control Bar -->
    <div class="rr-controls">
        <div class="rr-title">
            <h2><i class="fas fa-route"></i> Route Replay</h2>
            <p>Historical journey viewer</p>
        </div>

        <div class="rr-field">
            <label><i class="fas fa-truck"></i> Vehicle</label>
            <div class="searchable-select" id="vehicle-select-container">
                <div class="searchable-select-input" id="vehicle-select-input">
                    <?php if ($selected_device > 0): ?>
                        <?php 
                        $selected_name = '';
                        foreach ($devices as $d) {
                            if ($d['id'] == $selected_device) {
                                $selected_name = htmlspecialchars($d['name']) . ' (' . htmlspecialchars($d['uniqueid']) . ')';
                                break;
                            }
                        }
                        echo $selected_name;
                        ?>
                    <?php else: ?>
                        — Select vehicle —
                    <?php endif; ?>
                </div>
                <i class="fas fa-chevron-down searchable-select-arrow"></i>
                <div class="searchable-select-dropdown" id="vehicle-dropdown">
                    <div class="searchable-select-search">
                        <input type="text" id="vehicle-search" placeholder="Search vehicle..." autocomplete="off">
                    </div>
                    <div class="searchable-select-options" id="vehicle-options">
                        <div class="searchable-select-option" data-value="0">— Select vehicle —</div>
                        <?php foreach ($devices as $d): ?>
                            <div class="searchable-select-option" data-value="<?= $d['id'] ?>" <?= $selected_device == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['uniqueid']) ?>)
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="rr-field">
            <label><i class="fas fa-calendar-alt"></i> Start</label>
            <input type="datetime-local" id="rr-start" value="<?= htmlspecialchars($start_date) ?>" step="60">
        </div>

        <div class="rr-field">
            <label><i class="fas fa-calendar-check"></i> End</label>
            <input type="datetime-local" id="rr-end" value="<?= htmlspecialchars($end_date) ?>" step="60">
        </div>

        <button class="rr-btn-load" id="rr-load-btn">
            <i class="fas fa-search"></i> Load
        </button>
    </div>

    <!-- Stats Strip -->
    <div class="rr-stats">
        <div class="s-item"><i class="fas fa-road"></i> Distance: <strong id="rr-dist">0</strong> km</div>
        <div class="s-item"><i class="fas fa-tachometer-alt"></i> Max Speed: <strong id="rr-maxspd">0</strong> km/h</div>
        <div class="s-item"><i class="fas fa-chart-line"></i> Avg Speed: <strong id="rr-avgspd">0</strong> km/h</div>
        <div class="s-item"><i class="fas fa-map-pin"></i> Points: <strong id="rr-pts">0</strong></div>
    </div>

    <!-- Map -->
    <div class="rr-map-wrap" id="mapWrap">
        <div id="rr-map"></div>

        <!-- Floating Sidebar Toggle Button -->
        <button class="floating-sidebar-btn" id="floatingSidebarBtn">
            <i class="fas fa-list-ul"></i>
            <span>Show Timeline</span>
        </button>

        <!-- Custom Zoom Controls - Right Side -->
        <div class="custom-zoom-controls">
            <button class="zoom-btn" id="zoom-in-btn">
                <i class="fas fa-plus"></i>
            </button>
            <button class="zoom-btn" id="zoom-out-btn">
                <i class="fas fa-minus"></i>
            </button>
        </div>

        <!-- Map Layer/Style Controls - Right Side -->
        <div class="layer-controls">
            <button class="layer-toggle" id="layer-toggle-btn">
                <span><i class="fas fa-layer-group"></i> Map Style</span>
                <i class="fas fa-chevron-down" id="layer-chevron"></i>
            </button>
            <div class="layer-dropdown" id="layer-dropdown">
                <button class="layer-option active" data-layer="standard">
                    <i class="fas fa-map"></i> Standard Map
                </button>
                <button class="layer-option" data-layer="satellite">
                    <i class="fas fa-satellite"></i> Satellite
                </button>
                <button class="layer-option" data-layer="dark">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
                <button class="layer-option" data-layer="outdoor">
                    <i class="fas fa-hiking"></i> Outdoor
                </button>
                <button class="layer-option" data-layer="traffic">
                    <i class="fas fa-car"></i> Traffic
                </button>
            </div>
        </div>

        <?php if (empty($positions) && $selected_device == 0): ?>
        <div class="rr-empty" id="rr-empty">
            <i class="fas fa-route"></i>
            <p>Select a vehicle and date range, then click Load</p>
        </div>
        <?php elseif (empty($positions) && $selected_device > 0): ?>
        <div class="rr-empty" id="rr-empty">
            <i class="fas fa-exclamation-circle"></i>
            <p>No position data found for the selected date range</p>
        </div>
        <?php endif; ?>

        <!-- Slider Panel (Sidebar) -->
        <div class="rr-slider-panel" id="rr-slider-panel">
            <div class="sp-header">
                <h3><i class="fas fa-sliders-h"></i> Journey Timeline</h3>
                <div style="display: flex; gap: 8px;">
                    <button class="sp-close-btn" id="sp-close-btn">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="sp-time"><i class="far fa-clock"></i> <span id="rr-cur-time">—</span></div>
                </div>
            </div>
            <div class="sp-content">
                <input type="range" id="rr-slider" min="0" max="100" value="0" step="1">
                <div class="sp-btns">
                    <button id="rr-play"><i class="fas fa-play"></i> Play</button>
                    <button id="rr-pause"><i class="fas fa-pause"></i> Pause</button>
                    <button id="rr-reset"><i class="fas fa-stop"></i> Reset</button>
                </div>
                <div class="speedometer-container">
                    <div class="speedometer-gauge" id="speedometer">
                        <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="100" cy="100" r="95" fill="rgba(10,10,15,0.9)" stroke="rgba(212,175,55,0.5)" stroke-width="2"/>
                            <g id="speedometer-scale">
                                <circle cx="100" cy="100" r="80" fill="none" stroke="rgba(212,175,55,0.15)" stroke-width="30" stroke-dasharray="251.3 251.3" stroke-dashoffset="0" opacity="0.5"/>
                                <g stroke="rgba(212,175,55,0.6)" stroke-width="1.5" stroke-linecap="round">
                                    <line x1="100" y1="20" x2="100" y2="30" />
                                    <line x1="159.1" y1="27.3" x2="154.1" y2="34.1" />
                                    <line x1="196.6" y1="65.5" x2="189.6" y2="70.5" />
                                    <line x1="213.4" y1="118.5" x2="204.9" y2="118.5" />
                                    <line x1="196.6" y1="171.5" x2="189.6" y2="166.5" />
                                    <line x1="159.1" y1="209.7" x2="154.1" y2="202.9" />
                                    <line x1="100" y1="227" x2="100" y2="217" />
                                    <line x1="40.9" y1="209.7" x2="45.9" y2="202.9" />
                                    <line x1="3.4" y1="171.5" x2="10.4" y2="166.5" />
                                </g>
                                <text x="100" y="45" text-anchor="middle" fill="rgba(212,175,55,0.8)" font-size="9" font-weight="bold">0</text>
                                <text x="175" y="85" text-anchor="middle" fill="rgba(212,175,55,0.7)" font-size="8">50</text>
                                <text x="185" y="130" text-anchor="middle" fill="rgba(212,175,55,0.7)" font-size="8">100</text>
                                <text x="175" y="175" text-anchor="middle" fill="rgba(212,175,55,0.7)" font-size="8">150</text>
                                <text x="100" y="190" text-anchor="middle" fill="rgba(212,175,55,0.7)" font-size="8">200</text>
                            </g>
                            <g id="needle-group">
                                <line class="speedometer-needle" x1="100" y1="100" x2="100" y2="50" stroke="#d4af37" stroke-width="3" stroke-linecap="round"/>
                                <circle cx="100" cy="100" r="6" fill="#d4af37"/>
                                <circle cx="100" cy="100" r="4" fill="rgba(10,10,15,0.95)"/>
                            </g>
                            <circle cx="100" cy="100" r="3" fill="rgba(212,175,55,0.5)"/>
                        </svg>
                    </div>
                    <div class="speedometer-info">
                        <div>
                            <div class="metric-label">Current Speed</div>
                            <div class="speed-value"><span id="rr-cur-spd">0</span> km/h</div>
                        </div>
                        <div>
                            <div class="metric-label">Distance Traveled</div>
                            <div class="metric-value"><span id="rr-dist-traveled">0.0</span> km</div>
                        </div>
                        <div>
                            <div class="metric-label">Fuel Consumed</div>
                            <div class="metric-value"><span id="rr-fuel-consumed">0.00</span> L</div>
                        </div>
                        <div style="font-size: 9px; color: #888; margin-top: 4px;">
                            <span id="ignition-status">🔌 Ignition: <strong style="color: #d4af37;">—</strong></span>
                        </div>
                    </div>
                </div>
                
                <!-- Journey Timeline Section - DETAILED BUTTONS with time, speed, distance, fuel -->
                <div style="flex-shrink: 0; border-top: 1px solid rgba(212, 175, 55, 0.2); padding-top: 12px; margin-top: 8px;">
                    <div class="waypoints-header" id="waypointsHeader">
                        <h4 style="font-size: 11px; color: #d4af37; text-transform: uppercase; letter-spacing: 0.6px; margin: 0; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-map-marker-alt"></i> Waypoints
                        </h4>
                        <button class="toggle-waypoints-btn" id="toggleWaypointsBtn">
                            <i class="fas fa-chevron-up"></i>
                            <span>Hide</span>
                        </button>
                    </div>
                    <div id="rr-tl-list" style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Vehicle type icon mapping for moving marker ──
    const vehicleTypeIcons = {
        'Truck': '🚛',
        'Van': '🚐',
        'Car': '🚗',
        'SUV': '🚙',
        'Bus': '🚌',
        'Motorcycle': '🏍️',
        'default': '🚗'
    };
    
    const vehicleTypeImages = {
        'Truck': 'https://cdn-icons-png.flaticon.com/512/936/936810.png',
        'Van': 'https://cdn-icons-png.flaticon.com/512/743/743007.png',
        'Car': 'https://cdn-icons-png.flaticon.com/512/744/744465.png',
        'SUV': 'https://cdn-icons-png.flaticon.com/512/741/741407.png',
        'Bus': 'https://cdn-icons-png.flaticon.com/512/61/61168.png',
        'Motorcycle': 'https://cdn-icons-png.flaticon.com/512/10771/10771849.png',
        'default': 'https://cdn-icons-png.flaticon.com/512/744/744465.png'
    };
    
    const selectedVehicleType = '<?= $vehicle_type ?>';
    const deviceName = '<?= addslashes($device_name) ?>';
    const vehicleIcon = vehicleTypeIcons[selectedVehicleType] || vehicleTypeIcons['default'];
    const vehicleImage = vehicleTypeImages[selectedVehicleType] || vehicleTypeImages['default'];
    
    // ── Searchable Select Logic ─────────────────────────────────
    const container = document.getElementById('vehicle-select-container');
    const input = document.getElementById('vehicle-select-input');
    const dropdown = document.getElementById('vehicle-dropdown');
    const searchInput = document.getElementById('vehicle-search');
    const optionsContainer = document.getElementById('vehicle-options');
    let selectedValue = <?= $selected_device ?: 0 ?>;
    
    input.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            searchInput.focus();
        }
    });
    
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });
    
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const options = optionsContainer.querySelectorAll('.searchable-select-option');
        options.forEach(option => {
            const text = option.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                option.classList.remove('hidden');
            } else {
                option.classList.add('hidden');
            }
        });
    });
    
    const options = optionsContainer.querySelectorAll('.searchable-select-option');
    options.forEach(option => {
        option.addEventListener('click', (e) => {
            e.stopPropagation();
            const value = option.dataset.value;
            const text = option.textContent;
            
            options.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
            input.textContent = text;
            selectedValue = value;
            dropdown.classList.remove('open');
            searchInput.value = '';
            options.forEach(opt => opt.classList.remove('hidden'));
            
            if (value && value !== '0') {
                const startDate = document.getElementById('rr-start').value;
                const endDate = document.getElementById('rr-end').value;
                window.location.href = `?page=route-replay&device_id=${value}&start_date=${startDate}&end_date=${endDate}`;
            }
        });
    });
    
    // ── Map init ───────────────────────────────────────────────
    const map = L.map('rr-map', { zoomControl: false }).setView([7.0731, 125.6128], 12);
    
    const layers = {
        standard: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© Esri',
            maxZoom: 18
        }),
        dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© CartoDB',
            maxZoom: 19
        }),
        outdoor: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenTopoMap',
            maxZoom: 17
        }),
        traffic: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        })
    };
    
    let currentLayer = layers.standard;
    currentLayer.addTo(map);
    
    function zoomIn() { map.zoomIn(); }
    function zoomOut() { map.zoomOut(); }
    
    function changeLayer(layerName) {
        map.removeLayer(currentLayer);
        currentLayer = layers[layerName];
        currentLayer.addTo(map);
        document.querySelectorAll('.layer-option').forEach(opt => {
            if (opt.dataset.layer === layerName) {
                opt.classList.add('active');
            } else {
                opt.classList.remove('active');
            }
        });
    }
    
    const mapEl = document.getElementById('rr-map');
    ['mousedown','wheel','touchstart','dblclick','click','contextmenu'].forEach(evt => {
        mapEl.addEventListener(evt, e => e.stopPropagation(), { passive: false });
    });
    
    // ── Custom Moving Marker ──
    const movingCarIcon = L.divIcon({
        html: `<div style="position: relative; width: 48px; height: 58px;">
                    <div style="position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 6px; background: rgba(0,0,0,0.85); color: #d4af37; font-size: 11px; font-weight: bold; padding: 3px 10px; border-radius: 16px; border: 1px solid rgba(212,175,55,0.5); white-space: nowrap; backdrop-filter: blur(4px);">
                        ${vehicleIcon} ${deviceName}
                    </div>
                    <div class="moving-marker-icon" style="width: 48px; height: 48px; position: absolute; bottom: 0; left: 0; transform-origin: 24px 24px; transition: transform 0.3s ease;">
                        <img src="${vehicleImage}" style="width: 100%; height: 100%; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                    </div>
                </div>`,
        iconSize: [48, 58],
        popupAnchor: [0, -48],
        className: 'moving-marker'
    });
    
    const startIcon = L.divIcon({ html: '<div style="font-size:26px">🚩</div>', iconSize: [26,26], className: '' });
    const endIcon   = L.divIcon({ html: '<div style="font-size:26px">🏁</div>', iconSize: [26,26], className: '' });
    const wpIcon    = L.divIcon({ html: '<div style="font-size:13px;opacity:.6">📍</div>', iconSize: [13,13], className: '' });
    
    // ── State ─────────────────────────────────────────────────
    let positions = [];
    let displayPositions = [];
    let routeLine = null, movMarker = null, startMk = null, endMk = null, wpMarkers = [];
    let curIdx = 0, animTimer = null, markerMoveAnim = null, playing = false;
    let waypointsVisible = true;
    
    // Sidebar elements
    const sliderPanel = document.getElementById('rr-slider-panel');
    const mapWrap = document.getElementById('mapWrap');
    const floatingBtn = document.getElementById('floatingSidebarBtn');
    const closeBtn = document.getElementById('sp-close-btn');
    const toggleWaypointsBtn = document.getElementById('toggleWaypointsBtn');
    const tlList = document.getElementById('rr-tl-list');
    
    function showSidebar() {
        sliderPanel.classList.add('visible');
        mapWrap.classList.add('sidebar-visible');
        floatingBtn.querySelector('span').textContent = 'Hide Timeline';
        floatingBtn.querySelector('i').className = 'fas fa-times';
        
        // On mobile, move button to top-left when sidebar is open
        if (window.innerWidth <= 768) {
            floatingBtn.classList.add('hide-btn-mobile');
        }
    }
    
    function hideSidebar() {
        sliderPanel.classList.remove('visible');
        mapWrap.classList.remove('sidebar-visible');
        floatingBtn.querySelector('span').textContent = 'Show Timeline';
        floatingBtn.querySelector('i').className = 'fas fa-list-ul';
        
        // On mobile, move button back to bottom center when sidebar is closed
        if (window.innerWidth <= 768) {
            floatingBtn.classList.remove('hide-btn-mobile');
        }
    }
    
    floatingBtn.addEventListener('click', () => {
        if (sliderPanel.classList.contains('visible')) {
            hideSidebar();
        } else {
            showSidebar();
        }
    });
    
    closeBtn.addEventListener('click', hideSidebar);
    
    // Toggle waypoints visibility
    function toggleWaypoints() {
        waypointsVisible = !waypointsVisible;
        if (waypointsVisible) {
            tlList.style.display = 'flex';
            toggleWaypointsBtn.innerHTML = '<i class="fas fa-chevron-up"></i><span>Hide</span>';
        } else {
            tlList.style.display = 'none';
            toggleWaypointsBtn.innerHTML = '<i class="fas fa-chevron-down"></i><span>Show</span>';
        }
    }
    
    toggleWaypointsBtn.addEventListener('click', toggleWaypoints);
    
    // Handle resize for mobile orientation changes
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            if (sliderPanel.classList.contains('visible')) {
                floatingBtn.classList.add('hide-btn-mobile');
            } else {
                floatingBtn.classList.remove('hide-btn-mobile');
            }
        } else {
            floatingBtn.classList.remove('hide-btn-mobile');
        }
    });
    
    function haversine(la1, lo1, la2, lo2) {
        const R = 6371, d2r = Math.PI/180;
        const dLa = (la2-la1)*d2r, dLo = (lo2-lo1)*d2r;
        const a = Math.sin(dLa/2)**2 + Math.cos(la1*d2r)*Math.cos(la2*d2r)*Math.sin(dLo/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }
    
    function calcStats(pts) {
        let dist = 0, maxSpd = 0, sumSpd = 0;
        for (let i = 1; i < pts.length; i++)
            dist += haversine(pts[i-1].latitude, pts[i-1].longitude, pts[i].latitude, pts[i].longitude);
        pts.forEach(p => { const s = parseFloat(p.speed)||0; sumSpd += s; if (s > maxSpd) maxSpd = s; });
        return { dist: dist.toFixed(1), maxSpd: maxSpd.toFixed(1), avgSpd: (sumSpd/pts.length).toFixed(1), count: pts.length };
    }

    function simplifyPositions(pts, minDistanceMeters = 5) {
        if (!pts.length) return [];
        const filtered = [pts[0]];
        for (let i = 1; i < pts.length; i++) {
            const last = filtered[filtered.length - 1];
            const d = haversine(last.latitude, last.longitude, pts[i].latitude, pts[i].longitude) * 1000;
            if (d > minDistanceMeters) {
                filtered.push(pts[i]);
            }
        }
        return filtered;
    }
    
    function clearLayers() {
        [routeLine, movMarker, startMk, endMk].forEach(l => l && map.removeLayer(l));
        wpMarkers.forEach(m => map.removeLayer(m));
        wpMarkers = []; routeLine = movMarker = startMk = endMk = null;
    }
    
    function getBearing(lat1, lon1, lat2, lon2) {
        const toRad = Math.PI / 180;
        const toDeg = 180 / Math.PI;
        const dLon = (lon2 - lon1) * toRad;
        const y = Math.sin(dLon) * Math.cos(lat2 * toRad);
        const x = Math.cos(lat1 * toRad) * Math.sin(lat2 * toRad) -
                  Math.sin(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.cos(dLon);
        return (Math.atan2(y, x) * toDeg + 360) % 360;
    }

    function isNearPosition(p1, p2, thresholdMeters = 5) {
        return haversine(p1.latitude, p1.longitude, p2.latitude, p2.longitude) * 1000 <= thresholdMeters;
    }

    function setMarkerRotation(angle) {
        if (!movMarker) return;
        const el = movMarker.getElement();
        if (!el) return;
        const iconEl = el.querySelector('.moving-marker-icon');
        if (iconEl) {
            const correctedAngle = angle + 90 + 180;
            iconEl.style.transform = `rotate(${correctedAngle}deg)`;
        }
    }

    function animateMarkerTo(targetLatLng, duration = 500) {
        if (!movMarker) return;
        if (markerMoveAnim) cancelAnimationFrame(markerMoveAnim);
        const startTime = performance.now();
        const startLatLng = movMarker.getLatLng();
        const startLat = startLatLng.lat;
        const startLng = startLatLng.lng;
        const endLat = targetLatLng[0];
        const endLng = targetLatLng[1];

        const ease = t => t < 0.5 ? 2*t*t : -1 + (4 - 2*t)*t;

        const step = time => {
            const elapsed = time - startTime;
            const t = Math.min(1, elapsed / duration);
            const eased = ease(t);
            const lat = startLat + (endLat - startLat) * eased;
            const lng = startLng + (endLng - startLng) * eased;
            movMarker.setLatLng([lat, lng]);
            if (t < 1) {
                markerMoveAnim = requestAnimationFrame(step);
            } else {
                markerMoveAnim = null;
                movMarker.setLatLng(targetLatLng);
            }
        };

        markerMoveAnim = requestAnimationFrame(step);
    }

    function updateMarker(idx) {
    if (!positions.length || idx >= positions.length) return;
    const p = positions[idx];
    
    const attributes = typeof p.attributes === 'string' ? JSON.parse(p.attributes) : p.attributes;
    const ignitionStatus = attributes && attributes.ignition !== undefined ? attributes.ignition : null;
    
    if (movMarker) {
        let angle = 0;
        if (idx > 0) {
            const prev = positions[idx - 1];
            angle = getBearing(prev.latitude, prev.longitude, p.latitude, p.longitude);
            if (!isNearPosition(prev, p, 5)) {
                setMarkerRotation(angle);
                animateMarkerTo([p.latitude, p.longitude], 550);
            }
        } else if (positions.length > 1) {
            const next = positions[1];
            angle = getBearing(p.latitude, p.longitude, next.latitude, next.longitude);
            setMarkerRotation(angle);
            animateMarkerTo([p.latitude, p.longitude], 550);
        }
        
        const iconEl = movMarker.getElement();
        if (iconEl) {
            const imgEl = iconEl.querySelector('.moving-marker-icon');
            if (imgEl) {
                if (ignitionStatus === false || ignitionStatus === 0) {
                    imgEl.classList.add('ignition-off');
                } else {
                    imgEl.classList.remove('ignition-off');
                }
            }
        }
    }
    
    const speed = parseFloat(p.speed) || 0;
    document.getElementById('rr-cur-time').textContent = new Date(p.fixtime).toLocaleString();
    document.getElementById('rr-cur-spd').textContent = speed.toFixed(1);
    
    let distanceTraveled = 0;
    if (idx > 0) {
        for (let i = 0; i < idx; i++) {
            distanceTraveled += haversine(
                positions[i].latitude, positions[i].longitude,
                positions[i + 1].latitude, positions[i + 1].longitude
            );
        }
    }
    document.getElementById('rr-dist-traveled').textContent = distanceTraveled.toFixed(1);
    
    const fuelConsumption = 8;
    const fuelConsumed = distanceTraveled / fuelConsumption;
    document.getElementById('rr-fuel-consumed').textContent = fuelConsumed.toFixed(2);
    
    const ignitionStatusEl = document.getElementById('ignition-status');
    if (ignitionStatus === true || ignitionStatus === 1) {
        ignitionStatusEl.innerHTML = '🔌 Ignition: <strong style="color: #4ecdc4;">ON</strong>';
    } else if (ignitionStatus === false || ignitionStatus === 0) {
        ignitionStatusEl.innerHTML = '🔌 Ignition: <strong style="color: #ff6b6b;">OFF</strong>';
    } else {
        ignitionStatusEl.innerHTML = '🔌 Ignition: <strong style="color: #d4af37;">—</strong>';
    }
    
    // FIXED: Speedometer needle - 0 km/h points UP (12 o'clock), 200 km/h points DOWN (6 o'clock)
    const maxSpeed = 200;
    let needleAngle = (speed / maxSpeed) * 180;  // 0° at 0 km/h (up), 180° at 200 km/h (down)
    needleAngle = Math.max(0, Math.min(180, needleAngle));
    
    const needle = document.querySelector('.speedometer-needle');
    if (needle) {
        needle.style.transform = `rotate(${needleAngle}deg)`;
    }
    
    document.getElementById('rr-slider').value = (idx / (positions.length - 1)) * 100;
    
    document.querySelectorAll('.tl-detailed-btn').forEach((btn, i) => {
        if (parseInt(btn.dataset.index) === idx) {
            btn.classList.add('active-pill');
        } else {
            btn.classList.remove('active-pill');
        }
    });
}

    function startPlay() {
        if (playing) return;
        if (curIdx >= positions.length - 1) { curIdx = 0; updateMarker(0); }
        playing = true;
        animTimer = setInterval(() => {
            if (curIdx < positions.length - 1) {
                curIdx++;
                updateMarker(curIdx);
            } else {
                stopPlay();
            }
        }, 600);
    }

    function stopPlay() {
        clearInterval(animTimer); animTimer = null; playing = false;
        if (markerMoveAnim) cancelAnimationFrame(markerMoveAnim);
        markerMoveAnim = null;
    }

    function resetPlay() {
        stopPlay(); curIdx = 0; updateMarker(0);
        if (positions.length) map.panTo([positions[0].latitude, positions[0].longitude]);
    }
    
    // Build detailed timeline buttons with time, speed, distance, fuel
    function buildTimeline(pts) {
        const list = document.getElementById('rr-tl-list');
        list.innerHTML = '';
        const step = Math.max(1, Math.floor(pts.length / 40));
        
        // Pre-calculate cumulative distances for each point
        const cumulativeDist = [0];
        for (let i = 1; i < pts.length; i++) {
            cumulativeDist[i] = cumulativeDist[i-1] + haversine(pts[i-1].latitude, pts[i-1].longitude, pts[i].latitude, pts[i].longitude);
        }
        
        for (let i = 0; i < pts.length; i += step) {
            const p = pts[i];
            const d = new Date(p.fixtime);
            const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const speedVal = parseFloat(p.speed) || 0;
            const distToPoint = cumulativeDist[i];
            const fuelToPoint = distToPoint / 8;
            
            const btn = document.createElement('div');
            btn.className = 'tl-detailed-btn';
            btn.setAttribute('data-index', i);
            btn.innerHTML = `
                <div class="tl-time">
                    <i class="fas fa-clock"></i> ${timeStr}
                </div>
                <div class="tl-details">
                    <span><i class="fas fa-tachometer-alt"></i> Speed: <strong>${speedVal.toFixed(1)}</strong> km/h</span>
                    <span><i class="fas fa-road"></i> Dist: <strong>${distToPoint.toFixed(1)}</strong> km</span>
                    <span><i class="fas fa-gas-pump"></i> Fuel: <strong>${fuelToPoint.toFixed(2)}</strong> L</span>
                </div>
            `;
            btn.onclick = (function(idx) {
                return function() {
                    stopPlay();
                    curIdx = idx;
                    updateMarker(idx);
                    map.setView([pts[idx].latitude, pts[idx].longitude], 16);
                };
            })(i);
            list.appendChild(btn);
        }
    }
    
    function renderRoute(pts) {
        clearLayers();
        positions = pts;
        displayPositions = simplifyPositions(pts, 5);
        
        const s = calcStats(positions);
        document.getElementById('rr-dist').textContent   = s.dist;
        document.getElementById('rr-maxspd').textContent = s.maxSpd;
        document.getElementById('rr-avgspd').textContent = s.avgSpd;
        document.getElementById('rr-pts').textContent    = s.count;
        
        const lls = displayPositions.map(p => [p.latitude, p.longitude]);
        routeLine = L.polyline(lls, { color: '#d4af37', weight: 4, opacity: 0.9 }).addTo(map);
        
        startMk = L.marker([positions[0].latitude, positions[0].longitude], { icon: startIcon })
            .bindPopup(`<b>🚀 Start</b><br>${new Date(positions[0].fixtime).toLocaleString()}`).addTo(map);
        endMk = L.marker([positions[positions.length-1].latitude, positions[positions.length-1].longitude], { icon: endIcon })
            .bindPopup(`<b>🏁 End</b><br>${new Date(positions[positions.length-1].fixtime).toLocaleString()}`).addTo(map);
        
        for (let i = 0; i < displayPositions.length; i += 10) {
            wpMarkers.push(L.marker([displayPositions[i].latitude, displayPositions[i].longitude], { icon: wpIcon })
                .bindPopup(`📍 Pt ${i+1} · ${parseFloat(displayPositions[i].speed).toFixed(1)} km/h`)
                .addTo(map));
        }
        
        movMarker = L.marker(lls[0], { icon: movingCarIcon, pane: 'markerPane', zIndexOffset: 1000 }).addTo(map);
        curIdx = 0; updateMarker(0);
        buildTimeline(positions);
        
        map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
        
        setTimeout(() => { startMk.openPopup(); setTimeout(() => startMk.closePopup(), 2500); }, 600);
    }
    
    <?php if (!empty($positions)): ?>
    renderRoute(<?= json_encode($positions) ?>);
    <?php endif; ?>
    
    // ── UI events ─────────────────────────────────────────────
    document.getElementById('rr-load-btn').addEventListener('click', () => {
        const dev = selectedValue;
        if (!dev || dev === '0') { alert('Please select a vehicle'); return; }
        const s = document.getElementById('rr-start').value;
        const e = document.getElementById('rr-end').value;
        window.location.href = `?page=route-replay&device_id=${dev}&start_date=${s}&end_date=${e}`;
    });
    
    document.getElementById('zoom-in-btn').addEventListener('click', zoomIn);
    document.getElementById('zoom-out-btn').addEventListener('click', zoomOut);
    
    const layerToggleBtn = document.getElementById('layer-toggle-btn');
    const layerDropdown = document.getElementById('layer-dropdown');
    const layerChevron = document.getElementById('layer-chevron');
    let layerMenuOpen = false;
    
    layerToggleBtn.addEventListener('click', () => {
        layerMenuOpen = !layerMenuOpen;
        layerDropdown.classList.toggle('open', layerMenuOpen);
        layerChevron.classList.toggle('fa-chevron-down', !layerMenuOpen);
        layerChevron.classList.toggle('fa-chevron-up', layerMenuOpen);
    });
    
    document.querySelectorAll('.layer-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const layerName = btn.dataset.layer;
            changeLayer(layerName);
            layerMenuOpen = false;
            layerDropdown.classList.remove('open');
            layerChevron.classList.remove('fa-chevron-up');
            layerChevron.classList.add('fa-chevron-down');
        });
    });
    
    document.getElementById('rr-slider').addEventListener('input', function() {
        stopPlay();
        curIdx = Math.floor((parseFloat(this.value)/100) * (positions.length-1));
        updateMarker(curIdx);
        map.panTo([positions[curIdx].latitude, positions[curIdx].longitude]);
    });
    
    document.getElementById('rr-play').addEventListener('click', startPlay);
    document.getElementById('rr-pause').addEventListener('click', stopPlay);
    document.getElementById('rr-reset').addEventListener('click', resetPlay);
    
    // Auto-show sidebar when positions are loaded
    <?php if (!empty($positions)): ?>
    setTimeout(function() {
        showSidebar();
    }, 500);
    <?php endif; ?>
    
})();
</script>