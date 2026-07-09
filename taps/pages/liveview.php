<?php
require_once "./DB/dbcon.php";

// Check if this is an AJAX request by looking for the 'ajax' parameter
if (isset($_GET['ajax'])) {
    
    // Handle single geocode request
    if ($_GET['ajax'] === 'geocode') {
        header('Content-Type: application/json');
        
        // Rate limiting: store requests in session
        if (!isset($_SESSION['geocode_requests'])) {
            $_SESSION['geocode_requests'] = [];
        }
        
        // Clean old requests (older than 1 second)
        $now = microtime(true);
        $_SESSION['geocode_requests'] = array_filter($_SESSION['geocode_requests'], function($timestamp) use ($now) {
            return ($now - $timestamp) < 1;
        });
        
        // Limit to 1 request per second (Nominatim requirement)
        if (count($_SESSION['geocode_requests']) >= 1) {
            echo json_encode(['error' => 'Rate limited. Please wait before making more requests.']);
            exit();
        }
        
        // Log this request
        $_SESSION['geocode_requests'][] = $now;
        
        // Get parameters
        $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
        $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
        
        if (!$lat || !$lon) {
            echo json_encode(['error' => 'Missing lat or lon parameters']);
            exit();
        }
        
        // Check cache in session
        $cache_key = round($lat, 6) . ',' . round($lon, 6);
        if (isset($_SESSION['geocode_cache'][$cache_key])) {
            echo json_encode(['success' => true, 'address' => $_SESSION['geocode_cache'][$cache_key]]);
            exit();
        }
        
        // Set a custom User-Agent (REQUIRED by Nominatim)
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: VehicleTrackingSystem/1.0 (vehicle-tracker@example.com)\r\nAccept-Language: en\r\n",
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&zoom=18&addressdetails=1";
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            echo json_encode(['success' => false, 'address' => 'Address lookup failed']);
            exit();
        }
        
        $data = json_decode($response, true);
        
        // Format address nicely
        if ($data && isset($data['display_name'])) {
            $parts = explode(',', $data['display_name']);
            if (count($parts) > 3) {
                $address = implode(',', array_slice($parts, 0, 3));
            } else {
                $address = $data['display_name'];
            }
            $address = trim($address);
            
            // Store in cache
            if (!isset($_SESSION['geocode_cache'])) {
                $_SESSION['geocode_cache'] = [];
            }
            $_SESSION['geocode_cache'][$cache_key] = $address;
            
            echo json_encode(['success' => true, 'address' => $address]);
        } else {
            echo json_encode(['success' => false, 'address' => 'Unknown Location']);
        }
        exit();
    }
    
    // Handle batch geocoding
    if ($_GET['ajax'] === 'batch_geocode') {
        header('Content-Type: application/json');
        
        // Check cache first
        if (isset($_SESSION['batch_cache']) && isset($_SESSION['batch_cache']['data']) && isset($_SESSION['batch_cache']['expires'])) {
            if (time() < $_SESSION['batch_cache']['expires']) {
                echo json_encode($_SESSION['batch_cache']['data']);
                exit();
            }
        }
        
        // Rate limiting
        $now = time();
        if (isset($_SESSION['last_batch_geocode']) && ($now - $_SESSION['last_batch_geocode']) < 5) {
            // Return cached data if available
            if (isset($_SESSION['batch_cache']['data'])) {
                echo json_encode($_SESSION['batch_cache']['data']);
                exit();
            }
            echo json_encode(['error' => 'Rate limited. Please wait.']);
            exit();
        }
        
        $_SESSION['last_batch_geocode'] = $now;
        
        // Get all vehicles that need addresses
        $sql = "
            SELECT 
                d.id,
                p.latitude,
                p.longitude
            FROM tc_devices d
            LEFT JOIN tc_positions p ON d.positionid = p.id
            WHERE p.latitude IS NOT NULL 
            AND p.longitude IS NOT NULL
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $addresses = [];
        
        // Initialize cache if not exists
        if (!isset($_SESSION['batch_cache_data'])) {
            $_SESSION['batch_cache_data'] = [];
        }
        
        foreach ($vehicles as $vehicle) {
            $lat = $vehicle['latitude'];
            $lng = $vehicle['longitude'];
            $key = round($lat, 6) . ',' . round($lng, 6);
            
            // Check cache first
            if (isset($_SESSION['batch_cache_data'][$key])) {
                $addresses[$vehicle['id']] = $_SESSION['batch_cache_data'][$key];
                continue;
            }
            
            // Fetch from Nominatim with delay to respect rate limits
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18";
            
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: VehicleTrackingSystem/1.0 (vehicle-tracker@example.com)\r\nAccept-Language: en\r\n",
                    'timeout' => 3,
                    'ignore_errors' => true
                ]
            ];
            
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data && isset($data['display_name'])) {
                    $parts = explode(',', $data['display_name']);
                    $address = count($parts) > 3 ? implode(',', array_slice($parts, 0, 3)) : $data['display_name'];
                    $address = trim($address);
                    $addresses[$vehicle['id']] = $address;
                    $_SESSION['batch_cache_data'][$key] = $address;
                } else {
                    $addresses[$vehicle['id']] = 'Location unavailable';
                }
            } else {
                $addresses[$vehicle['id']] = 'Location unavailable';
            }
            
            // Sleep to respect rate limit (1 request per second)
            usleep(1000000); // 1 second delay
        }
        
        // Cache the result for 5 minutes
        $_SESSION['batch_cache'] = [
            'data' => $addresses,
            'expires' => time() + 300
        ];
        
        echo json_encode($addresses);
        exit();
    }
}

// Main query - Using ISNULL for SQL Server compatibility
$sql = "WITH LatestPositions AS (
    SELECT 
        deviceId,
        latitude,
        longitude,
        speed,
        address,
        fixtime,
        serverTime,
        valid,
        attributes,
        ROW_NUMBER() OVER (PARTITION BY deviceId ORDER BY serverTime DESC) as rn
    FROM tc_positions
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
)
SELECT
    d.id,
    ISNULL(d.name, 'Unknown Device') AS name,
    ISNULL(d.uniqueid, 'N/A') AS uniqueid,
    ISNULL(d.status, 'UNKNOWN') AS status,
    d.lastupdate,
    ISNULL(d.vehicle_type, 'Car') AS vehicle_type,
    CAST(lp.latitude AS FLOAT) AS latitude,
    CAST(lp.longitude AS FLOAT) AS longitude,
    ISNULL(lp.speed, 0) AS speed,
    ISNULL(lp.address, '') AS address,
    lp.fixtime,
    lp.serverTime,
    lp.valid,
    lp.attributes
FROM tc_devices d
LEFT JOIN LatestPositions lp ON d.id = lp.deviceId AND lp.rn = 1
WHERE lp.latitude IS NOT NULL AND lp.longitude IS NOT NULL
ORDER BY d.name
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
#content{padding:0!important;overflow:hidden;background:#000;}

.live-container{
    position:relative;
    width:100%;
    height:calc(100vh - 70px);
}

/* MAP */
#map{width:100%;height:100%;}

/* ZOOM AND REFRESH CONTROLS */
.live-controls{
    position:absolute;
    top:220px;
    right:15px;
    z-index:999;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.live-btn{
    width:44px;
    height:44px;
    border:none;
    border-radius:14px;
    background:rgba(0,0,0,.85);
    color:#d4af37;
    border:1px solid rgba(212,175,55,.2);
    cursor:pointer;
    transition: all 0.2s ease;
    font-size: 20px;
    font-weight: bold;
}

.live-btn:hover{
    background:rgba(212,175,55,.2);
    color:#d4af37;
    transform: scale(1.05);
}

/* MAP LAYER STYLES BUTTON & DROPDOWN */
.map-layer-container {
    position: absolute;
    top: 330px;
    right: 15px;
    z-index: 999;
}

.layer-style-btn {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 14px;
    background: rgba(0,0,0,.85);
    color: #d4af37;
    border: 1px solid rgba(212,175,55,.2);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.layer-style-btn:hover {
    background: rgba(212,175,55,.2);
    color: #d4af37;
    transform: scale(1.05);
}

/* Layer style dropdown menu */
.layer-dropdown {
    position: absolute;
    bottom: -205px;
    right: 0;
    background: rgba(0,0,0,0.92);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(212,175,55,0.3);
    border-radius: 16px;
    padding: 8px 0;
    min-width: 170px;
    display: none;
    flex-direction: column;
    gap: 4px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    animation: fadeInUp 0.2s ease;
}

.layer-dropdown.active {
    display: flex;
}

.layer-option {
    padding: 10px 16px;
    color: #e0e0e0;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.layer-option:hover {
    background: rgba(212,175,55,0.15);
    color: #d4af37;
}

.layer-option .layer-icon {
    font-size: 16px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Auto-refresh controls */
.refresh-controls {
    position: absolute;
    top: 50px;
    right: 15px;
    z-index: 999;
    background: rgba(0,0,0,0.85);
    border-radius: 14px;
    padding: 12px;
    border: 1px solid rgba(212,175,55,0.2);
    backdrop-filter: blur(10px);
    min-width: 130px;
}

.refresh-title {
    color: #d4af37;
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 8px;
    text-align: center;
    letter-spacing: 0.5px;
}

.refresh-option {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    color: #fff;
    font-size: 11px;
    cursor: pointer;
}

.refresh-option input[type="radio"] {
    accent-color: #d4af37;
    cursor: pointer;
    width: 14px;
    height: 14px;
}

.refresh-option label {
    cursor: pointer;
    color: #ccc;
    transition: color 0.2s;
}

.refresh-option:hover label {
    color: #d4af37;
}

.refresh-status {
    margin-top: 8px;
    padding-top: 6px;
    border-top: 1px solid rgba(212,175,55,0.15);
    font-size: 9px;
    color: #d4af37;
    text-align: center;
    font-weight: bold;
}

/* SIDEBAR - Full Version */
.live-sidebar{
    position:absolute;
    top:35px;
    left:15px;
    width:350px;
    max-height:calc(100vh - 130px);
    z-index:999;
    background:rgba(0,0,0,.88);
    border-radius:22px;
    border:1px solid rgba(212,175,55,.15);
    backdrop-filter:blur(10px);
    overflow:visible;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateX(0);
    opacity: 1;
}

/* Sidebar Hidden State - Collapsed Button */
.live-sidebar.collapsed {
    transform: translateX(-330px);
    opacity: 0;
    pointer-events: none;
}

/* Floating Toggle Button (appears when sidebar is hidden) */
.floating-toggle-btn {
    position: absolute;
    top: 50px;
    left: 15px;
    width: 50px;
    height: 50px;
    background: rgba(0,0,0,0.9);
    border: 1px solid rgba(212,175,55,0.4);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    cursor: pointer;
    z-index: 900;
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.floating-toggle-btn:hover {
    background: rgba(212,175,55,0.15);
    transform: scale(1.05);
    border-color: rgba(212,175,55,0.7);
}

.floating-toggle-btn .icon {
    font-size: 24px;
    color: #d4af37;
}

.floating-toggle-btn .count {
    font-size: 10px;
    color: #d4af37;
    margin-top: 2px;
    font-weight: bold;
}

.floating-toggle-btn.visible {
    display: flex;
}

/* Sidebar Header with Hide/Show Button */
.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(212, 175, 55, 0.15);
    background: rgba(0,0,0,0.6);
    border-radius: 22px 22px 0 0;
}

.sidebar-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #d4af37;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.5px;
}

.sidebar-title span {
    font-size: 18px;
}

.toggle-sidebar-btn {
    background: rgba(212, 175, 55, 0.15);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 12px;
    padding: 6px 12px;
    cursor: pointer;
    color: #d4af37;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.toggle-sidebar-btn:hover {
    background: rgba(212, 175, 55, 0.3);
    transform: scale(1.02);
}

/* Sidebar Content */
.sidebar-content {
    padding: 16px;
    transition: all 0.3s ease;
    overflow-y: auto;
    max-height: calc(95vh - 180px);
}

/* Scrollbar Styling */
.sidebar-content::-webkit-scrollbar {
    width: 5px;
}

.sidebar-content::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
}

.sidebar-content::-webkit-scrollbar-thumb {
    background: #d4af37;
    border-radius: 10px;
}

/* SEARCH */
.search-box{
    width:100%;
    padding:10px;
    border-radius:12px;
    border:1px solid rgba(212,175,55,.2);
    background:#111;
    color:#fff;
    margin-bottom:12px;
    transition: all 0.2s ease;
}

.search-box:focus {
    outline: none;
    border-color: rgba(212,175,55,0.6);
    box-shadow: 0 0 8px rgba(212,175,55,0.2);
}

/* CARD */
.vehicle-card{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(212,175,55,.08);
    border-radius:18px;
    padding:14px;
    margin-bottom:10px;
    cursor:pointer;
    transition:.2s;
}

.vehicle-card:hover{
    background:rgba(212,175,55,.08);
    transform: translateX(5px);
    border-color: rgba(212,175,55,0.2);
}

/* STATUS INDICATORS */
.status-online{
    background:#16a34a;
    color:#fff;
    padding:3px 10px;
    border-radius:20px;
    font-size:11px;
}

.status-offline{
    background:#dc2626;
    color:#fff;
    padding:3px 10px;
    border-radius:20px;
    font-size:11px;
}

/* Indicator icons styling */
.indicator-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 6px;
    font-size: 12px;
}

.battery-alarm {
    color: #ff4444;
    animation: pulse 1s ease-in-out infinite;
}

.engine-on {
    color: #44ff44;
}

.engine-off {
    color: #888888;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* TEXT */
.info{
    color:#ccc;
    font-size:12px;
    margin-top:8px;
    line-height:1.6;
}

/* MODERN POPUP CARD STYLES - FLEET MONITORING */
.custom-popup .leaflet-popup-content-wrapper {
    background: rgba(10, 10, 15, 0.95);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    border: 1px solid rgba(212, 175, 55, 0.3);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    padding: 0;
}

.custom-popup .leaflet-popup-content {
    margin: 0;
    min-width: 280px;
    max-width: 320px;
}

.custom-popup .leaflet-popup-tip {
    background: rgba(10, 10, 15, 0.95);
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.popup-card {
    padding: 14px 16px;
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}

.popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
    padding-bottom: 8px;
}

.popup-vehicle-name {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.3px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.popup-status-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 30px;
    background: #16a34a;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.popup-status-badge.offline {
    background: #dc2626;
}

.popup-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
}

.popup-detail-row {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    color: #e0e0e0;
}

.popup-detail-icon {
    width: 28px;
    font-size: 16px;
    text-align: center;
}

.popup-detail-text {
    flex: 1;
    font-weight: 500;
}

.popup-detail-text strong {
    color: #d4af37;
    font-weight: 600;
    margin-right: 6px;
}

.popup-address {
    background: rgba(0, 0, 0, 0.4);
    border-radius: 12px;
    padding: 8px 10px;
    margin-top: 6px;
    font-size: 11px;
    color: #aaa;
    border-left: 2px solid #d4af37;
    word-break: break-word;
}

.popup-footer {
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid rgba(212, 175, 55, 0.15);
    font-size: 10px;
    color: #888;
    display: flex;
    justify-content: space-between;
}

/* Grayscale filter for markers when ignition is off - FIXED */
.vehicle-marker-ignition-off {
    filter: grayscale(100%) brightness(0.6) !important;
}

/* Animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sidebar-content:not(.collapsed) {
    animation: slideIn 0.3s ease;
}
</style>

<div class="live-container">

<!-- ZOOM AND AUTO-REFRESH CONTROLS -->
<div class="live-controls">
    <button type="button" class="live-btn" onclick="map.zoomIn()">+</button>
    <button type="button" class="live-btn" onclick="map.zoomOut()">−</button>
</div>

<!-- MAP LAYER STYLES BUTTON -->
<div class="map-layer-container">
    <button type="button" class="layer-style-btn" id="layerStyleBtn">🗺️</button>
    <div class="layer-dropdown" id="layerDropdown">
        <div class="layer-option" data-layer="standard">
            <span class="layer-icon">🗺️</span> Standard Map
        </div>
        <div class="layer-option" data-layer="satellite">
            <span class="layer-icon">🛰️</span> Satellite
        </div>
        <div class="layer-option" data-layer="dark">
            <span class="layer-icon">🌙</span> Dark Mode
        </div>
        <div class="layer-option" data-layer="outdoor">
            <span class="layer-icon">🏔️</span> Outdoor
        </div>
        <div class="layer-option" data-layer="transport">
            <span class="layer-icon">🚆</span> Transport
        </div>
    </div>
</div>

<!-- Auto-refresh Radio Controls -->
<div class="refresh-controls">
    <div class="refresh-title">🔄 AUTO REFRESH</div>
    <div class="refresh-option">
        <input type="radio" name="refreshRate" id="refreshOff" value="off" checked>
        <label for="refreshOff">⏸️ Off</label>
    </div>
    <div class="refresh-option">
        <input type="radio" name="refreshRate" id="refresh5s" value="5">
        <label for="refresh5s">⚡ 5 seconds</label>
    </div>
    <div class="refresh-option">
        <input type="radio" name="refreshRate" id="refresh10s" value="10">
        <label for="refresh10s">🔁 10 seconds</label>
    </div>
    <div class="refresh-option">
        <input type="radio" name="refreshRate" id="refresh30s" value="30">
        <label for="refresh30s">🐌 30 seconds</label>
    </div>
    <div class="refresh-status" id="refreshStatus">
        ⚫ Auto-refresh off
    </div>
</div>

<!-- Floating Toggle Button (appears when sidebar is hidden) -->
<div class="floating-toggle-btn" id="floatingToggleBtn">
    <div class="icon">🚛</div>
    <div class="count"><?= count($vehicles) ?></div>
</div>

<!-- SIDEBAR -->
<div class="live-sidebar" id="liveSidebar">
    <div class="sidebar-header">
        <div class="sidebar-title">
            <span>🚛</span>
            <span style="font-size: 12px; opacity: 0.9;" id="vehicleCount">(<?= count($vehicles) ?> vehicles)</span>
        </div>
        <button type="button" class="toggle-sidebar-btn" id="toggleSidebarBtn">
            <span>Hide</span>
            <span>◀</span>
        </button>
    </div>
    <div class="sidebar-content" id="sidebarContent">
        <input type="text" class="search-box" id="searchInput" placeholder="🔍 Search device...">

        <?php foreach($vehicles as $row): ?>

        <?php
        $status = strtolower(trim($row['status'] ?? 'unknown'));

        if($status === '' || $status === 'unknown'){
            $status = 'offline';
        }

        $isOnline = ($status === 'online');
        $vehicleType = $row['vehicle_type'] ?? 'Car';
        $vehicleIcon = $vehicleIcons[$vehicleType] ?? $vehicleIcons['default'];
        
        // Parse attributes JSON to check for alarm powerCut and ignition status
        $hasPowerCut = false;
        $ignitionOn = null;
        
        if (!empty($row['attributes'])) {
            $attributes = is_string($row['attributes']) ? json_decode($row['attributes'], true) : $row['attributes'];
            if (is_array($attributes)) {
                $hasPowerCut = isset($attributes['alarm']) && $attributes['alarm'] === 'powerCut';
                $ignitionOn = isset($attributes['ignition']) ? $attributes['ignition'] : null;
            }
        }
        
        // Determine indicator icons
        $batteryIndicator = '';
        $engineIndicator = '';
        
        if ($hasPowerCut) {
            $batteryIndicator = '<span class="indicator-icon battery-alarm" title="Power Cut Alarm">🔴⚡</span>';
        }
        
        if ($ignitionOn === true) {
            $engineIndicator = '<span class="indicator-icon engine-on" title="Engine ON">🔧🟢</span>';
        } elseif ($ignitionOn === false) {
            $engineIndicator = '<span class="indicator-icon engine-off" title="Engine OFF">🔧⚫</span>';
        }
        ?>

        <div class="vehicle-card device-item"
        data-vehicle-id="<?= $row['id'] ?>"
        data-name="<?= strtolower($row['name']) ?>"
        data-lat="<?= $row['latitude'] ?>"
        data-lng="<?= $row['longitude'] ?>"
        data-ignition="<?= $ignitionOn === true ? 'on' : ($ignitionOn === false ? 'off' : 'unknown') ?>"
        onclick="focusVehicle(<?= $row['latitude'] ?>,<?= $row['longitude'] ?>)">

        <div style="display:flex;justify-content:space-between;align-items:center;">
            <b style="color:#fff"><?= $vehicleIcon ?> <?= htmlspecialchars($row['name']) ?></b>

            <div style="display: flex; align-items: center; gap: 8px;">
                <?= $batteryIndicator ?>
                <?= $engineIndicator ?>
                <span class="<?= $isOnline ? 'status-online' : 'status-offline' ?>">
                    <?= $isOnline ? 'ONLINE' : 'OFFLINE' ?>
                </span>
            </div>
        </div>

        <div class="info">
        📟 <?= htmlspecialchars($row['uniqueid']) ?><br>
        🚗 <span id="speed_card_<?= $row['id'] ?>"><?= round($row['speed'],1) ?></span> km/h<br>
        📍 <span id="addr_card_<?= $row['id'] ?>">Loading address...</span>
        </div>

        </div>

        <?php endforeach; ?>
    </div>
</div>

<!-- MAP -->
<div id="map"></div>

</div>

<script>

// =========================
// PREVENT ACCIDENTAL PAGE REFRESHES
// =========================
document.addEventListener('DOMContentLoaded', function() {
    // Make all buttons type="button" by default if no type specified
    document.querySelectorAll('button').forEach(btn => {
        if (!btn.type || btn.type === 'submit') {
            btn.type = 'button';
        }
    });
    
    // Prevent any form from submitting
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            console.warn('Form submission prevented:', form);
            e.preventDefault();
            return false;
        });
    });
});

// Prevent any link clicks that might cause refresh
document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (link && link.getAttribute('href') === '#') {
        e.preventDefault();
    }
});

const map = L.map('map',{zoomControl:false}).setView([7.0731,125.6128],12);

// Define available tile layers
const tileLayers = {
    
    standard: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: '© Esri'
    }),
    dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
        attribution: '© CartoDB'
    }),
    outdoor: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: '© OpenTopoMap'
    }),
    transport: L.tileLayer('https://{s}.tile.thunderforest.com/transport/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© Thunderforest'
    })
};

// Add default layer
let currentLayer = tileLayers.standard;
currentLayer.addTo(map);

// Layer dropdown functionality
const layerStyleBtn = document.getElementById('layerStyleBtn');
const layerDropdown = document.getElementById('layerDropdown');
let dropdownActive = false;

function showLayerDropdown() {
    layerDropdown.classList.add('active');
    dropdownActive = true;
}

function hideLayerDropdown() {
    layerDropdown.classList.remove('active');
    dropdownActive = false;
}

function toggleLayerDropdown(e) {
    e.stopPropagation();
    if (dropdownActive) {
        hideLayerDropdown();
    } else {
        showLayerDropdown();
    }
}

layerStyleBtn.addEventListener('click', toggleLayerDropdown);

// Change map layer function
function changeMapLayer(layerType) {
    // Remove current layer
    if (currentLayer) {
        map.removeLayer(currentLayer);
    }
    // Add new layer
    currentLayer = tileLayers[layerType];
    currentLayer.addTo(map);
    
    // Close dropdown after selection
    hideLayerDropdown();
}

// Add click handlers to layer options
document.querySelectorAll('.layer-option').forEach(option => {
    option.addEventListener('click', function(e) {
        e.stopPropagation();
        const layerType = this.dataset.layer;
        changeMapLayer(layerType);
    });
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const container = document.querySelector('.map-layer-container');
    if (container && !container.contains(event.target)) {
        if (dropdownActive) {
            hideLayerDropdown();
        }
    }
});

// Also close on hover out (mouse leave the container)
const layerContainer = document.querySelector('.map-layer-container');
if (layerContainer) {
    layerContainer.addEventListener('mouseleave', function() {
        if (dropdownActive) {
            hideLayerDropdown();
        }
    });
    
    // Keep dropdown open when hovering over it
    layerDropdown.addEventListener('mouseenter', function() {
        if (dropdownActive) {
            // stay open
        }
    });
}

const bounds = [];

// Store markers globally for search filtering
const markers = {};

// Store current vehicle data for dynamic updates
let currentVehicleData = {};

// Store ignition status for each vehicle
const ignitionStatusMap = {};

/* =========================
   ADDRESS CACHE
========================= */
const addressCache = {};
const vehicleAddresses = {};

/* =========================
   UPDATE ADDRESS UI
========================= */
function updateAddressUI(vehicleId, address) {

    if (!vehicleId) return;

    // Save address
    vehicleAddresses[vehicleId] = address;

    // =========================
    // UPDATE SIDEBAR
    // =========================
    const cardAddr =
        document.getElementById(`addr_card_${vehicleId}`);

    if (cardAddr) {
        cardAddr.innerHTML = address;
    }

    // =========================
    // UPDATE POPUP
    // =========================
    const popupAddr =
        document.getElementById(`popup_addr_${vehicleId}`);

    if (popupAddr) {
        popupAddr.innerHTML = "📍 " + address;
    }
}

/* =========================
   GET ADDRESS
========================= */
/* =========================
   IMPROVED GET ADDRESS WITH NEARBY CACHE
========================= */
function findNearbyCachedAddress(lat, lng, maxDistanceMeters = 50) {
    // Check if we have any cached address within maxDistanceMeters
    for (const [cachedKey, cachedAddress] of Object.entries(addressCache)) {
        const [cachedLat, cachedLng] = cachedKey.split(',').map(Number);
        
        // Calculate distance between coordinates (Haversine formula)
        const distance = getDistanceFromLatLonInMeters(lat, lng, cachedLat, cachedLng);
        
        if (distance <= maxDistanceMeters) {
            console.log(`Using nearby cached address (${distance.toFixed(1)}m away): ${cachedAddress}`);
            return cachedAddress;
        }
    }
    return null;
}

// Haversine formula to calculate distance between two coordinates
function getDistanceFromLatLonInMeters(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth's radius in meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

async function getAddress(lat, lng, vehicleId = null, retryCount = 0) {
    const exactKey = lat.toFixed(5) + "," + lng.toFixed(5);
    
    // Check exact cache first (fastest)
    if (addressCache[exactKey]) {
        updateAddressUI(vehicleId, addressCache[exactKey]);
        return addressCache[exactKey];
    }
    
    // Check nearby cache (within 50 meters)
    const nearbyAddress = findNearbyCachedAddress(lat, lng, 50);
    if (nearbyAddress) {
        // Store exact coordinate for future use
        addressCache[exactKey] = nearbyAddress;
        updateAddressUI(vehicleId, nearbyAddress);
        return nearbyAddress;
    }
    
    // If no cache, fetch from API
    try {
        const response = await fetch(`/anubis/pages/get_address.php?lat=${lat}&lon=${lng}`);
        const data = await response.json();
        
        if (data.success && data.address) {
            // Store exact and also round to lower precision for nearby matching
            addressCache[exactKey] = data.address;
            
            // Also store with lower precision (3 decimals = ~111 meters)
            const lowerPrecisionKey = lat.toFixed(3) + "," + lng.toFixed(3);
            if (!addressCache[lowerPrecisionKey]) {
                addressCache[lowerPrecisionKey] = data.address;
            }
            
            updateAddressUI(vehicleId, data.address);
            return data.address;
        } else {
            if (retryCount < 2) {
                setTimeout(() => {
                    getAddress(lat, lng, vehicleId, retryCount + 1);
                }, 2000);
            } else {
                updateAddressUI(vehicleId, "Address unavailable");
            }
        }
    } catch (error) {
        console.error("Geocode fetch error:", error);
        if (retryCount < 2) {
            setTimeout(() => {
                getAddress(lat, lng, vehicleId, retryCount + 1);
            }, 2000);
        } else {
            updateAddressUI(vehicleId, "Address unavailable");
        }
    }
}

/* =========================
   LOAD ALL ADDRESSES
========================= */
async function loadAllAddresses() {

    document
        .querySelectorAll('.device-item')
        .forEach(card => {

            const vehicleId =
                card.dataset.vehicleId;

            const lat =
                parseFloat(card.dataset.lat);

            const lng =
                parseFloat(card.dataset.lng);

            if (!isNaN(lat) && !isNaN(lng)) {

                getAddress(
                    lat,
                    lng,
                    vehicleId
                );
            }
        });
}

/* =========================
   UPDATE MARKER ICON BASED ON IGNITION
========================= */
function updateMarkerIcon(vehicleId, ignitionOn, vehicleType, vehicleName) {
    const marker = markers[vehicleId];
    if (!marker) return;
    
    const vehicleImage = getVehicleImage(vehicleType);
    const vehicleIcon = getVehicleIcon(vehicleType);
    const grayscaleClass = (ignitionOn === false) ? 'vehicle-marker-ignition-off' : '';
    
    const newIcon = L.divIcon({
        html: `<div style="position: relative; width: 48px; height: 58px;" class="${grayscaleClass}">
                    <div style="position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 6px; background: rgba(0,0,0,0.85); color: #d4af37; font-size: 11px; font-weight: bold; padding: 3px 10px; border-radius: 16px; border: 1px solid rgba(212,175,55,0.5); white-space: nowrap; backdrop-filter: blur(4px); font-family: system-ui; z-index: 1000;">
                        ${vehicleIcon} ${escapeHtml(vehicleName)}
                    </div>
                    <img src="${vehicleImage}" style="width: 48px; height: 48px; position: absolute; bottom: 0; left: 0; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                </div>`,
        iconSize: [48, 58],
        popupAnchor: [0, -48],
        className: `vehicle-marker-${vehicleId}`
    });
    
    marker.setIcon(newIcon);
}

/* =========================
   AUTO-REFRESH FUNCTIONALITY
========================= */
let refreshInterval = null;
let currentRefreshRate = 'off';

function startAutoRefresh(seconds) {
    // Clear existing interval
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    
    // If seconds is null or 'off', just stop
    if (!seconds || seconds === 'off') {
        document.getElementById('refreshStatus').innerHTML = '⚫ Auto-refresh off';
        return;
    }
    
    // Start new interval
    refreshInterval = setInterval(() => {
        refreshVehicleData();
    }, seconds * 1000);
    
    document.getElementById('refreshStatus').innerHTML = `🟢 Refreshing every ${seconds}s`;
}

/* =========================
   REFRESH VEHICLE DATA
========================= */
async function refreshVehicleData() {

    try {

        const response = await fetch(
            '/anubis/pages/get_vehicle_updates.php'
        );

        const updatedVehicles =
            await response.json();

        updatedVehicles.forEach(vehicle => {

            if (markers[vehicle.id]) {

                // =========================
                // UPDATE MARKER POSITION
                // =========================
                markers[vehicle.id]
                    .setLatLng([
                        vehicle.latitude,
                        vehicle.longitude
                    ]);

                // =========================
                // UPDATE ADDRESS
                // =========================
                getAddress(
                    vehicle.latitude,
                    vehicle.longitude,
                    vehicle.id
                );

                // =========================
                // UPDATE SIDEBAR SPEED
                // =========================
                const speedSpan =
                    document.getElementById(
                        `speed_card_${vehicle.id}`
                    );

                if (speedSpan) {
                    speedSpan.innerHTML =
                        vehicle.speed;
                }
                
                // =========================
                // UPDATE IGNITION STATUS AND MARKER COLOR
                // =========================
                const ignitionStatus = vehicle.ignition;
                if (ignitionStatus !== undefined) {
                    ignitionStatusMap[vehicle.id] = ignitionStatus;
                    updateMarkerIcon(vehicle.id, ignitionStatus, vehicle.vehicle_type, vehicle.name);
                }

                // =========================
                // UPDATE STORED DATA
                // =========================
                currentVehicleData[
                    vehicle.id
                ] = vehicle;
            }
        });

    } catch (error) {

        console.error(
            'Error refreshing vehicles:',
            error
        );
    }
}

function getVehicleImage(vehicleType) {
    const images = {
        'Truck': 'https://cdn-icons-png.flaticon.com/512/936/936810.png',
        'Van': 'https://cdn-icons-png.flaticon.com/512/743/743007.png',
        'Car': 'https://cdn-icons-png.flaticon.com/512/744/744465.png',
        'SUV': 'https://cdn-icons-png.flaticon.com/512/741/741407.png',
        'Bus': 'https://cdn-icons-png.flaticon.com/512/61/61168.png',
        'Motorcycle': 'https://cdn-icons-png.flaticon.com/512/10771/10771849.png'
    };
    return images[vehicleType] || images['Car'];
}

function getVehicleIcon(vehicleType) {
    const icons = {
        'Truck': '🚛',
        'Van': '🚐',
        'Car': '🚗',
        'SUV': '🚙',
        'Bus': '🚌',
        'Motorcycle': '🏍️'
    };
    return icons[vehicleType] || '🚗';
}

function createCustomIcon(
    vehicleId,
    vehicleName,
    vehicleImage,
    vehicleIcon,
    ignitionOn
) {
    const grayscaleClass = (ignitionOn === false) ? 'vehicle-marker-ignition-off' : '';

    return L.divIcon({

        html: `
        <div style="position: relative; width: 48px; height: 58px;" class="${grayscaleClass}">
            <div style="position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 6px; background: rgba(0,0,0,0.85); color: #d4af37; font-size: 11px; font-weight: bold; padding: 3px 10px; border-radius: 16px; border: 1px solid rgba(212,175,55,0.5); white-space: nowrap; backdrop-filter: blur(4px); font-family: system-ui; z-index: 1000;">
                ${vehicleIcon} ${escapeHtml(vehicleName)}
            </div>
            <img src="${vehicleImage}" style="width: 48px; height: 48px; position: absolute; bottom: 0; left: 0; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
        </div>
        `,

        iconSize: [48, 58],

        iconAnchor: [24, 50],

        popupAnchor: [0, -42],

        className: `vehicle-marker-${vehicleId}`
    });
}

function updateDynamicFieldsForVehicle(vehicleId, speed, lastupdate) {
    // Driving status
    const drivingStatusRow = document.getElementById(`driving_status_row_${vehicleId}`);
    if (drivingStatusRow) {
        const statusText = getDrivingStatus(speed, lastupdate);
        drivingStatusRow.innerHTML = `<div class="popup-detail-icon">🚦</div><div class="popup-detail-text"><strong>Status:</strong> ${statusText}</div>`;
    }
    
    // Fuel status
    const fuelStatusRow = document.getElementById(`fuel_status_row_${vehicleId}`);
    if (fuelStatusRow) {
        const fuelText = getFuelStatus(speed, lastupdate);
        fuelStatusRow.innerHTML = `<div class="popup-detail-icon">⛽</div><div class="popup-detail-text"><strong>Fuel Usage:</strong> ${fuelText}</div>`;
    }
    
    // ETA
    const etaRow = document.getElementById(`eta_row_${vehicleId}`);
    if (etaRow && speed > 0) {
        const exampleDistance = 50;
        const eta = calculateETA(exampleDistance, speed);
        etaRow.innerHTML = `<div class="popup-detail-icon">🕐</div><div class="popup-detail-text"><strong>ETA (50km):</strong> ${eta}</div>`;
    } else if (etaRow) {
        etaRow.innerHTML = `<div class="popup-detail-icon">🕐</div><div class="popup-detail-text"><strong>ETA (est):</strong> Stationary</div>`;
    }
    
    // Time since last update
    const timeSpan = document.getElementById(`time_since_${vehicleId}`);
    if (timeSpan) {
        timeSpan.innerHTML = `⏱️ ${getTimeSince(lastupdate)}`;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Listen to radio button changes
document.querySelectorAll('input[name="refreshRate"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.checked) {
            currentRefreshRate = this.value;
            if (currentRefreshRate === 'off') {
                startAutoRefresh(null);
            } else {
                startAutoRefresh(parseInt(currentRefreshRate));
            }
        }
    });
});

/* =========================
   SIDEBAR HIDE/SHOW FUNCTIONALITY
========================= */
const sidebar = document.getElementById('liveSidebar');
const toggleBtn = document.getElementById('toggleSidebarBtn');
const floatingBtn = document.getElementById('floatingToggleBtn');

let isSidebarVisible = true;

function toggleSidebar() {
    if (isSidebarVisible) {
        sidebar.classList.add('collapsed');
        floatingBtn.classList.add('visible');
        isSidebarVisible = false;
    } else {
        sidebar.classList.remove('collapsed');
        floatingBtn.classList.remove('visible');
        isSidebarVisible = true;
    }
}

toggleBtn.addEventListener('click', toggleSidebar);
floatingBtn.addEventListener('click', toggleSidebar);

// Helper functions
function getTimeSince(lastupdate) {
    if (!lastupdate) return "Unknown";
    const last = new Date(lastupdate).getTime();
    const now = new Date().getTime();
    const diffMs = now - last;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffDays > 0) return `${diffDays}d ago`;
    if (diffHours > 0) return `${diffHours}h ago`;
    if (diffMins > 0) return `${diffMins}m ago`;
    return "Just now";
}

function calculateETA(distanceKm, speedKmh) {
    if (speedKmh <= 0) return "N/A";
    const hours = distanceKm / speedKmh;
    if (hours < 0.5) return `${Math.round(hours * 60)} min`;
    if (hours < 24) return `${hours.toFixed(1)} hrs`;
    return `${(hours / 24).toFixed(1)} days`;
}

function getFuelStatus(speed, lastupdate) {
    const timeSince = getTimeSince(lastupdate);
    if (speed > 80) return "🔴 High consumption";
    if (speed > 40) return "🟡 Normal";
    if (speed > 0) return "🟢 Economical";
    if (timeSince.includes("Just now") || timeSince.includes("m ago")) return "⏹️ Idling";
    return "⚫ Unknown";
}

function getDrivingStatus(speed, lastupdate) {
    if (speed > 5) return "🚀 Moving";
    if (speed > 0) return "🐢 Slow moving";
    const timeSince = getTimeSince(lastupdate);
    if (timeSince.includes("Just now") || timeSince.includes("m ago")) return "🅿️ Parked";
    return "⏸️ Inactive";
}

<?php foreach($vehicles as $row): 
    $status = strtolower(trim($row['status'] ?? 'unknown'));
    if($status === '' || $status === 'unknown') $status = 'offline';
    $isOnline = ($status === 'online');
    $statusClass = $isOnline ? '' : 'offline';
    $statusText = $isOnline ? 'ONLINE' : 'OFFLINE';
    $vehicleType = $row['vehicle_type'] ?? 'Car';
    $vehicleIcon = $vehicleIcons[$vehicleType] ?? $vehicleIcons['default'];
    $vehicleImage = $vehicleImages[$vehicleType] ?? $vehicleImages['default'];
    
    // Parse ignition status from attributes
    $ignitionOn = null;
    if (!empty($row['attributes'])) {
        $attributes = is_string($row['attributes']) ? json_decode($row['attributes'], true) : $row['attributes'];
        if (is_array($attributes)) {
            $ignitionOn = isset($attributes['ignition']) ? $attributes['ignition'] : null;
        }
    }
    $grayscaleClass = ($ignitionOn === false) ? 'vehicle-marker-ignition-off' : '';
    
    // Format fixtime for display
    $fixtimeFormatted = !empty($row['fixtime']) ? date('H:i:s d/m/Y', strtotime($row['fixtime'])) : 'N/A';
    $lastupdateFormatted = !empty($row['lastupdate']) ? date('H:i:s d/m/Y', strtotime($row['lastupdate'])) : 'N/A';
    
    // Store ignition status in JS variable
    $ignitionJsValue = $ignitionOn === true ? 'true' : ($ignitionOn === false ? 'false' : 'null');
?>

// Create custom marker with vehicle type icon on map
const customIcon_<?= $row['id'] ?> = L.divIcon({
    html: `<div style="position: relative; width: 48px; height: 58px;" class="<?= $grayscaleClass ?>">
                <div style="position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 6px; background: rgba(0,0,0,0.85); color: #d4af37; font-size: 11px; font-weight: bold; padding: 3px 10px; border-radius: 16px; border: 1px solid rgba(212,175,55,0.5); white-space: nowrap; backdrop-filter: blur(4px); font-family: system-ui; z-index: 1000;">
                    <?= $vehicleIcon ?> <?= addslashes($row['name']) ?>
                </div>
                <img src="<?= $vehicleImage ?>" style="width: 48px; height: 48px; position: absolute; bottom: 0; left: 0; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
            </div>`,
    iconSize: [48, 58],
    popupAnchor: [0, -48],
    className: 'vehicle-marker-<?= $row['id'] ?>'
});

// Build modern popup HTML
const popupHtml_<?= $row['id'] ?> = `
<div class="popup-card">
    <div class="popup-header">
        <div class="popup-vehicle-name">
            <?= $vehicleIcon ?> <?= addslashes($row['name']) ?>
        </div>
        <div class="popup-status-badge <?= $statusClass ?>">
            <?= $statusText ?>
        </div>
    </div>
    <div class="popup-details">
        <div class="popup-detail-row">
            <div class="popup-detail-icon">📟</div>
            <div class="popup-detail-text"><strong>Device ID:</strong> <?= htmlspecialchars($row['uniqueid']) ?></div>
        </div>
        <div class="popup-detail-row">
            <div class="popup-detail-icon">⚡</div>
            <div class="popup-detail-text"><strong>Speed:</strong> <?= round($row['speed'],1) ?> km/h</div>
        </div>
        <div class="popup-detail-row" id="driving_status_row_<?= $row['id'] ?>">
            <div class="popup-detail-icon">🚦</div>
            <div class="popup-detail-text"><strong>Status:</strong> Calculating...</div>
        </div>
        <div class="popup-detail-row" id="fuel_status_row_<?= $row['id'] ?>">
            <div class="popup-detail-icon">⛽</div>
            <div class="popup-detail-text"><strong>Fuel Usage:</strong> Calculating...</div>
        </div>
        <div class="popup-detail-row" id="eta_row_<?= $row['id'] ?>">
            <div class="popup-detail-icon">🕐</div>
            <div class="popup-detail-text"><strong>ETA (est):</strong> Calculating...</div>
        </div>
        <div class="popup-detail-row">
            <div class="popup-detail-icon">🕒</div>
            <div class="popup-detail-text"><strong>Last fix:</strong> <?= $fixtimeFormatted ?></div>
        </div>
        <div class="popup-detail-row">
            <div class="popup-detail-icon">🔧</div>
            <div class="popup-detail-text"><strong>Ignition:</strong> <?= $ignitionOn === true ? '🟢 ON' : ($ignitionOn === false ? '🔴 OFF' : '⚫ Unknown') ?></div>
        </div>
    </div>
    <div class="popup-address" id="popup_addr_<?= $row['id'] ?>">
        🔍 Loading address...
    </div>
    <div class="popup-footer">
        <span>🔄 Updated: <?= $lastupdateFormatted ?></span>
        <span id="time_since_<?= $row['id'] ?>"></span>
    </div>
</div>
`;

// Create marker
const marker_<?= $row['id'] ?> = L.marker([<?= $row['latitude'] ?>,<?= $row['longitude'] ?>], {
    icon: customIcon_<?= $row['id'] ?>,
    riseOnHover: true
})
.bindPopup(popupHtml_<?= $row['id'] ?>, { className: 'custom-popup', maxWidth: 350 })
.addTo(map);

// Store marker in global object
markers[<?= $row['id'] ?>] = marker_<?= $row['id'] ?>;

// Store ignition status
ignitionStatusMap[<?= $row['id'] ?>] = <?= $ignitionJsValue ?>;

// Store initial vehicle data
currentVehicleData[<?= $row['id'] ?>] = {
    id: <?= $row['id'] ?>,
    name: "<?= addslashes($row['name']) ?>",
    speed: <?= $row['speed'] ?>,
    latitude: <?= $row['latitude'] ?>,
    longitude: <?= $row['longitude'] ?>,
    status: "<?= $status ?>",
    vehicle_type: "<?= $vehicleType ?>",
    ignition: <?= $ignitionJsValue ?>
};

// Update dynamic fields when popup opens
marker_<?= $row['id'] ?>.on('popupopen', function() {
    updateDynamicFieldsForVehicle(<?= $row['id'] ?>, <?= $row['speed'] ?>, "<?= $row['lastupdate'] ?>");
    if (vehicleAddresses[<?= $row['id'] ?>]) {
        const popupAddrElement = document.getElementById("popup_addr_<?= $row['id'] ?>");
        if (popupAddrElement && popupAddrElement.innerHTML.includes("Loading address")) {
            popupAddrElement.innerHTML = "📍 " + vehicleAddresses[<?= $row['id'] ?>];
        }
    }
});

// Initial update
setTimeout(() => {
    updateDynamicFieldsForVehicle(<?= $row['id'] ?>, <?= $row['speed'] ?>, "<?= $row['lastupdate'] ?>");
}, 100);

bounds.push([<?= $row['latitude'] ?>,<?= $row['longitude'] ?>]);

<?php endforeach; ?>

// Fit bounds to show all vehicles
if(bounds.length){
    map.fitBounds(bounds,{padding:[50,50]});
}

/* =========================
   LOAD ADDRESSES AFTER MAP READY
========================= */
setTimeout(() => {

    loadAllAddresses();

}, 1000);

/* SEARCH FUNCTIONALITY */
document.getElementById('searchInput').addEventListener('keyup', function(){
    let val = this.value.toLowerCase().trim();
    let visibleCount = 0;
    
    document.querySelectorAll('.device-item').forEach(el => {
        const vehicleId = el.dataset.vehicleId;
        const vehicleName = el.dataset.name;
        const matches = val === '' || vehicleName.includes(val);
        
        el.style.display = matches ? 'block' : 'none';
        
        if (markers[vehicleId]) {
            if (matches) {
                markers[vehicleId].addTo(map);
                visibleCount++;
            } else {
                markers[vehicleId].remove();
            }
        }
    });
    
    const vehicleCountSpan = document.getElementById('vehicleCount');
    if (vehicleCountSpan) {
        if (val === '') {
            vehicleCountSpan.innerHTML = `(<?= count($vehicles) ?> vehicles)`;
        } else {
            vehicleCountSpan.innerHTML = `(${visibleCount} of <?= count($vehicles) ?> vehicles)`;
        }
    }
    
    if (visibleCount > 0) {
        const visibleBounds = [];
        document.querySelectorAll('.device-item').forEach(el => {
            if (el.style.display !== 'none') {
                const lat = parseFloat(el.dataset.lat);
                const lng = parseFloat(el.dataset.lng);
                if (!isNaN(lat) && !isNaN(lng)) {
                    visibleBounds.push([lat, lng]);
                }
            }
        });
        
        if (visibleBounds.length > 0) {
            const boundsGroup = L.latLngBounds(visibleBounds);
            map.fitBounds(boundsGroup, { padding: [50, 50] });
        }
    }
});

/* FOCUS VEHICLE FUNCTION */
function focusVehicle(lat, lng){
    map.setView([lat, lng], 17);
}

</script>