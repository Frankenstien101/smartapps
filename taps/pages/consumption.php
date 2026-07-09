<?php

require_once "./DB/dbcon.php";

// ====================== DATE FILTER ======================
$filterDate = $_GET['date'] ?? date('Y-m-d');

$sql = "
WITH base AS (
    SELECT
        p.deviceId,
        p.latitude,
        p.longitude,
        p.speed,
        p.fixTime,
        p.attributes,
        LAG(p.latitude) OVER (PARTITION BY p.deviceId ORDER BY p.fixTime) AS prev_lat,
        LAG(p.longitude) OVER (PARTITION BY p.deviceId ORDER BY p.fixTime) AS prev_lng,
        LAG(p.attributes) OVER (PARTITION BY p.deviceId ORDER BY p.fixTime) AS prev_attributes
    FROM tc_positions p
    WHERE CAST(p.fixTime AS DATE) = CAST(? AS DATE)
),

distance_calc AS (
    SELECT
        deviceId,
        SUM(
            CASE 
                WHEN prev_lat IS NULL THEN 0
                ELSE SQRT(
                    POWER(latitude - prev_lat, 2) +
                    POWER(longitude - prev_lng, 2)
                ) * 111.32
            END
        ) AS distance_today_km,
        SUM(
            CASE 
                WHEN prev_lat IS NOT NULL 
                     AND CHARINDEX('\"ignition\":true', attributes) > 0
                     AND CHARINDEX('\"ignition\":true', prev_attributes) > 0
                THEN SQRT(
                    POWER(latitude - prev_lat, 2) +
                    POWER(longitude - prev_lng, 2)
                ) * 111.32
                ELSE 0
            END
        ) AS engine_on_distance_km
    FROM base
    GROUP BY deviceId
),

yesterday_distance AS (
    SELECT
        deviceId,
        SUM(
            CASE 
                WHEN prev_lat IS NULL THEN 0
                ELSE SQRT(
                    POWER(latitude - prev_lat, 2) +
                    POWER(longitude - prev_lng, 2)
                ) * 111.32
            END
        ) AS yesterday_distance_km
    FROM (
        SELECT
            deviceId,
            latitude,
            longitude,
            LAG(latitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lat,
            LAG(longitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lng
        FROM tc_positions
        WHERE CAST(fixTime AS DATE) = DATEADD(day, -1, CAST(? AS DATE))
    ) y
    GROUP BY deviceId
),

weekly_distance AS (
    SELECT
        deviceId,
        SUM(
            CASE 
                WHEN prev_lat IS NULL THEN 0
                ELSE SQRT(
                    POWER(latitude - prev_lat, 2) +
                    POWER(longitude - prev_lng, 2)
                ) * 111.32
            END
        ) AS weekly_distance_km
    FROM (
        SELECT
            deviceId,
            latitude,
            longitude,
            LAG(latitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lat,
            LAG(longitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lng
        FROM tc_positions
        WHERE fixTime >= DATEADD(day, -7, CAST(? AS DATE))
    ) w
    GROUP BY deviceId
),

monthly_distance AS (
    SELECT
        deviceId,
        SUM(
            CASE 
                WHEN prev_lat IS NULL THEN 0
                ELSE SQRT(
                    POWER(latitude - prev_lat, 2) +
                    POWER(longitude - prev_lng, 2)
                ) * 111.32
            END
        ) AS monthly_distance_km
    FROM (
        SELECT
            deviceId,
            latitude,
            longitude,
            LAG(latitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lat,
            LAG(longitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lng
        FROM tc_positions
        WHERE fixTime >= DATEADD(day, -30, CAST(? AS DATE))
    ) m
    GROUP BY deviceId
)

SELECT
    d.id,
    d.name,
    d.uniqueId,
    d.vehicle_type,
    d.status,
    d.lastUpdate,
    d.fuel_consumption_liter_per_km,
    p.latitude,
    p.longitude,
    p.speed AS current_speed,
    p.fixTime,
    ISNULL(dc.distance_today_km, 0) AS distance_today_km,
    ISNULL(dc.engine_on_distance_km, 0) AS engine_on_distance_km,
    ISNULL(yd.yesterday_distance_km, 0) AS yesterday_distance_km,
    ISNULL(wd.weekly_distance_km, 0) AS weekly_distance_km,
    ISNULL(md.monthly_distance_km, 0) AS monthly_distance_km
FROM tc_devices d
OUTER APPLY (
    SELECT TOP 1 * FROM tc_positions tp2 WHERE tp2.deviceId = d.id ORDER BY tp2.fixTime DESC
) p
LEFT JOIN distance_calc dc ON dc.deviceId = d.id
LEFT JOIN yesterday_distance yd ON yd.deviceId = d.id
LEFT JOIN weekly_distance wd ON wd.deviceId = d.id
LEFT JOIN monthly_distance md ON md.deviceId = d.id
WHERE p.latitude IS NOT NULL
  AND p.longitude IS NOT NULL
ORDER BY d.name
";

$stmt = $conn->prepare($sql);
$stmt->execute([$filterDate, $filterDate, $filterDate, $filterDate]);
$vehiclesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// PROCESS VEHICLE DATA
// =====================================================
$vehicles = [];
$totalFuelAll = 0;
$totalDistanceAll = 0;
$totalFuelCostAll = 0;
$fuelPricePerLiter = $_SESSION['FUEL_COST'] ?? 65.00;

if (empty($vehiclesRaw)) {
    echo "<div style='color:white; text-align:center; padding:50px;'>No vehicle position data found for selected date.</div>";
    exit;
}

foreach ($vehiclesRaw as $row) {
    // Get fuel rate
    $fuelRate = 0;
    if (isset($row['fuel_consumption_liter_per_km']) && !is_null($row['fuel_consumption_liter_per_km']) && $row['fuel_consumption_liter_per_km'] > 0) {
        $fuelRate = floatval($row['fuel_consumption_liter_per_km']);
    }
    
    if ($fuelRate <= 0) {
        $defaultRates = [
            'Truck' => 0.35, 'Bus' => 0.30, 'Van' => 0.18, 
            'SUV' => 0.14, 'Car' => 0.10, 'Motorcycle' => 0.04, 
            'default' => 0.12
        ];
        $vehicleType = isset($row['vehicle_type']) ? $row['vehicle_type'] : 'default';
        $fuelRate = isset($defaultRates[$vehicleType]) ? $defaultRates[$vehicleType] : $defaultRates['default'];
    }
    
    $distanceToday = isset($row['engine_on_distance_km']) ? floatval($row['engine_on_distance_km']) : 0;
    $distanceYesterday = isset($row['yesterday_distance_km']) ? floatval($row['yesterday_distance_km']) : 0;
    $distanceWeekly = isset($row['weekly_distance_km']) ? floatval($row['weekly_distance_km']) : 0;
    $distanceMonthly = isset($row['monthly_distance_km']) ? floatval($row['monthly_distance_km']) : 0;
    
    $fuelUsedToday = $distanceToday * $fuelRate;
    $fuelUsedYesterday = $distanceYesterday * $fuelRate;
    $fuelUsedWeekly = $distanceWeekly * $fuelRate;
    $fuelUsedMonthly = $distanceMonthly * $fuelRate;
    
    $fuelCostToday = $fuelUsedToday * $fuelPricePerLiter;
    $fuelCostYesterday = $fuelUsedYesterday * $fuelPricePerLiter;
    $fuelCostWeekly = $fuelUsedWeekly * $fuelPricePerLiter;
    $fuelCostMonthly = $fuelUsedMonthly * $fuelPricePerLiter;
    
    $fuelPer100km = $fuelRate * 100;
    
    // Efficiency rating
    if ($fuelRate <= 0.08) {
        $efficiencyRating = 'Excellent';
        $efficiencyColor = '#22c55e';
    } elseif ($fuelRate <= 0.12) {
        $efficiencyRating = 'Good';
        $efficiencyColor = '#84cc16';
    } elseif ($fuelRate <= 0.18) {
        $efficiencyRating = 'Average';
        $efficiencyColor = '#eab308';
    } elseif ($fuelRate <= 0.25) {
        $efficiencyRating = 'Poor';
        $efficiencyColor = '#f97316';
    } else {
        $efficiencyRating = 'Very Poor';
        $efficiencyColor = '#dc2626';
    }
    
    $hasConfiguredRate = isset($row['fuel_consumption_liter_per_km']) && floatval($row['fuel_consumption_liter_per_km']) > 0;
    $currentSpeed = isset($row['current_speed']) ? floatval($row['current_speed']) : 0;
    
    $vehicles[] = [
        'id' => $row['id'],
        'name' => isset($row['name']) ? $row['name'] : 'Unknown',
        'uniqueid' => isset($row['uniqueId']) ? $row['uniqueId'] : 'N/A',
        'vehicle_type' => isset($row['vehicle_type']) ? $row['vehicle_type'] : 'Unknown',
        'status' => isset($row['status']) ? $row['status'] : 'offline',
        'latitude' => isset($row['latitude']) ? $row['latitude'] : 0,
        'longitude' => isset($row['longitude']) ? $row['longitude'] : 0,
        'current_speed' => round($currentSpeed, 1),
        'fixtime' => isset($row['fixTime']) ? $row['fixTime'] : null,
        'lastupdate' => isset($row['lastUpdate']) ? $row['lastUpdate'] : null,
        'fuel_rate_l_per_km' => $fuelRate,
        'fuel_rate_l_per_100km' => round($fuelPer100km, 1),
        'has_configured_rate' => $hasConfiguredRate,
        'distance_today_km' => round($distanceToday, 2),
        'distance_yesterday_km' => round($distanceYesterday, 2),
        'distance_weekly_km' => round($distanceWeekly, 2),
        'distance_monthly_km' => round($distanceMonthly, 2),
        'fuel_used_today_l' => round($fuelUsedToday, 2),
        'fuel_used_yesterday_l' => round($fuelUsedYesterday, 2),
        'fuel_used_weekly_l' => round($fuelUsedWeekly, 2),
        'fuel_used_monthly_l' => round($fuelUsedMonthly, 2),
        'fuel_cost_today' => round($fuelCostToday, 2),
        'fuel_cost_yesterday' => round($fuelCostYesterday, 2),
        'fuel_cost_weekly' => round($fuelCostWeekly, 2),
        'fuel_cost_monthly' => round($fuelCostMonthly, 2),
        'efficiency_rating' => $efficiencyRating,
        'efficiency_color' => $efficiencyColor
    ];
    
    // Accumulate totals
    $totalFuelAll += $fuelUsedToday;
    $totalDistanceAll += $distanceToday;
    $totalFuelCostAll += $fuelCostToday;
}

// Sort by fuel consumption (highest first)
usort($vehicles, function($a, $b) {
    return $b['fuel_used_today_l'] <=> $a['fuel_used_today_l'];
});

// Vehicle images with TRANSPARENT backgrounds
$vehicleImages = [
    'Truck' => 'https://cdn-icons-png.flaticon.com/512/936/936810.png',
    'Van' => 'https://cdn-icons-png.flaticon.com/512/743/743007.png',
    'Car' => 'https://cdn-icons-png.flaticon.com/512/744/744465.png',
    'SUV' => 'https://cdn-icons-png.flaticon.com/512/741/741407.png',
    'Bus' => 'https://cdn-icons-png.flaticon.com/512/61/61168.png',
    'Motorcycle' => 'https://cdn-icons-png.flaticon.com/512/10771/10771849.png',
    'default' => 'https://cdn-icons-png.flaticon.com/512/744/744465.png'
];

$vehicleIcons = [
    'Truck' => '🚛',
    'Van' => '🚐',
    'Car' => '🚗',
    'SUV' => '🚙',
    'Bus' => '🚌',
    'Motorcycle' => '🏍️',
    'default' => '🚗'
];

function getVehicleIcon($type) {
    $icons = ['Truck' => '🚛', 'Van' => '🚐', 'Car' => '🚗', 'SUV' => '🚙', 'Bus' => '🚌', 'Motorcycle' => '🏍️'];
    return isset($icons[$type]) ? $icons[$type] : '🚗';
}

function getStatusBadge($status) {
    $statusLower = strtolower(trim($status ?? 'offline'));
    if ($statusLower === 'online') {
        return '<span class="status-badge online">● ONLINE</span>';
    }
    return '<span class="status-badge offline">● OFFLINE</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Fuel Consumption Tracker | Vehicle Dashboard</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0a0f; height: 100vh; overflow: hidden; }
        
        .dashboard-fuel { display: flex; height: 90vh; width: 100%; }
        
        .sidebar-fuel { width: 450px; background: linear-gradient(180deg, #0f0f14 0%, #0a0a0f 100%); border-right: 1px solid rgba(212, 175, 55, 0.2); display: flex; flex-direction: column; overflow: hidden; z-index: 10; }
        
        .fuel-header { padding: 20px; background: linear-gradient(135deg, #1a1a24, #0f0f14); border-bottom: 1px solid rgba(212, 175, 55, 0.2); }
        .fuel-header h1 { font-size: 20px; color: #d4af37; display: flex; align-items: center; gap: 10px; }
        .fuel-header p { font-size: 11px; color: #888; margin-top: 8px; }
        
        .date-filter { margin-top: 12px; display: flex; gap: 8px; }
        .date-filter input { flex: 1; padding: 8px 12px; background: rgba(255,255,255,0.08); border: 1px solid rgba(212,175,55,0.4); color: #fff; border-radius: 8px; }
        .date-filter button { padding: 8px 16px; background: #22c55e; color: white; border: none; border-radius: 8px; cursor: pointer; }
        
        .fuel-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px; }
        .summary-item { background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 10px; text-align: center; border: 1px solid rgba(212, 175, 55, 0.1); }
        .summary-item .summary-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .summary-item .summary-value { font-size: 18px; font-weight: bold; color: #d4af37; margin-top: 5px; }
        
        .search-area { padding: 15px 20px; background: rgba(0, 0, 0, 0.3); }
        .search-area input { width: 100%; padding: 10px 15px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 12px; color: #fff; font-size: 13px; outline: none; }
        
        .vehicle-list-fuel { flex: 1; overflow-y: auto; padding: 10px 15px 20px; }
        .vehicle-list-fuel::-webkit-scrollbar { width: 5px; }
        .vehicle-list-fuel::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); }
        .vehicle-list-fuel::-webkit-scrollbar-thumb { background: #d4af37; border-radius: 10px; }
        
        .fuel-card { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(212, 175, 55, 0.1); border-radius: 16px; padding: 14px; margin-bottom: 12px; cursor: pointer; transition: all 0.25s ease; position: relative; }
        .fuel-card:hover { background: rgba(212, 175, 55, 0.08); transform: translateX(5px); }
        .fuel-card.active { background: rgba(212, 175, 55, 0.12); border-color: rgba(212, 175, 55, 0.5); box-shadow: 0 0 15px rgba(212, 175, 55, 0.15); }
        
        .rank-badge { position: absolute; top: -8px; left: -8px; width: 28px; height: 28px; background: #d4af37; color: #0a0a0f; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
        .rank-badge.rank-1 { background: #ffd700; width: 32px; height: 32px; font-size: 14px; }
        .rank-badge.rank-2 { background: #c0c0c0; }
        .rank-badge.rank-3 { background: #cd7f32; }
        
        .card-header-fuel { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-right: 45px; }
        .vehicle-name-fuel { display: flex; align-items: center; gap: 8px; font-weight: 600; color: #fff; font-size: 15px; }
        
        .fuel-info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; }
        .fuel-label { color: #888; }
        .fuel-value { color: #d4af37; font-weight: 600; }
        .distance-value { color: #fff; }
        
        .fuel-progress { margin-top: 10px; height: 4px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; overflow: hidden; }
        .fuel-progress-bar { height: 100%; transition: width 0.3s ease; }
        
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .status-badge.online { background: #16a34a; color: white; }
        .status-badge.offline { background: #dc2626; color: white; }
        
        .map-panel { flex: 1; position: relative; background: #1a1a24; }
        #fuelMap { width: 100%; height: 100%; }
        
        .map-controls { position: absolute; bottom: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
        .map-btn { width: 44px; height: 44px; border: none; border-radius: 12px; background: rgba(0,0,0,0.85); color: #d4af37; font-size: 20px; cursor: pointer; backdrop-filter: blur(10px); border: 1px solid rgba(212,175,55,0.3); transition: all 0.2s; }
        .map-btn:hover { background: rgba(212,175,55,0.2); transform: scale(1.05); }
        
        .vehicle-detail-panel { position: absolute; bottom: 20px; left: 20px; right: 20px; background: rgba(0,0,0,0.92); backdrop-filter: blur(12px); border-radius: 20px; padding: 15px; border: 1px solid rgba(212,175,55,0.3); z-index: 1000; transform: translateY(100%); transition: transform 0.3s ease; pointer-events: none; max-height: 45%; overflow-y: auto; }
        .vehicle-detail-panel.show { transform: translateY(0); pointer-events: auto; }
        
        .detail-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid rgba(212,175,55,0.2); }
        .detail-title { font-size: 18px; font-weight: bold; color: #d4af37; }
        .close-detail { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; }
        
        .detail-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 15px; }
        .detail-item { text-align: center; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 12px; }
        .detail-item .detail-label { font-size: 10px; color: #888; }
        .detail-item .detail-value { font-size: 16px; font-weight: bold; color: #d4af37; margin-top: 4px; }
        
        .detail-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(212,175,55,0.15); }
        .detail-stat { text-align: center; padding: 6px; background: rgba(0,0,0,0.3); border-radius: 8px; }
        .detail-stat .stat-label { font-size: 9px; color: #888; }
        .detail-stat .stat-value { font-size: 12px; font-weight: bold; color: #d4af37; margin-top: 3px; }
        
        .leaflet-popup-content-wrapper { background: transparent !important; box-shadow: none !important; border: none !important; }
        .leaflet-popup-tip { background: rgba(15, 15, 20, 0.75) !important; }
        
        @media (max-width: 900px) {
            body, html { height: auto; overflow: auto; }
            .dashboard-fuel { flex-direction: column; height: auto; min-height: 100vh; }
            .sidebar-fuel { width: 100%; max-height: none; border-right: none; border-bottom: 1px solid rgba(212, 175, 55, 0.2); }
            .map-panel { width: 100%; height: auto; min-height: 55vh; }
            .fuel-summary { grid-template-columns: repeat(2, 1fr); }
            .detail-grid { grid-template-columns: repeat(2, 1fr); }
            .detail-stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<div class="dashboard-fuel">
    <!-- LEFT SIDEBAR -->
    <div class="sidebar-fuel">
        <div class="fuel-header">
            <h1><span>⛽</span> Fuel Consumption Tracker</h1>
            <p>Ranked by fuel usage · Based on fuel consumption data</p>
            <div class="date-filter">
                <input type="date" id="filterDate" value="<?= $filterDate ?>">
                <button onclick="applyDateFilter()">Apply</button>
            </div>
            <div class="fuel-summary">
                <div class="summary-item">
                    <div class="summary-label">Total Fuel Today</div>
                    <div class="summary-value"><?= number_format($totalFuelAll, 1) ?> L</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Distance</div>
                    <div class="summary-value"><?= number_format($totalDistanceAll, 1) ?> km</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Est. Cost Today</div>
                    <div class="summary-value">₱<?= number_format($totalFuelCostAll, 0) ?></div>
                </div>
            </div>
        </div>
        
        <div class="search-area">
            <input type="text" id="searchFuelInput" placeholder="🔍 Search vehicle by name...">
        </div>
        
        <div class="vehicle-list-fuel" id="vehicleListFuel">
            <?php $rank = 1; foreach ($vehicles as $vehicle): ?>
                <?php
                $icon = getVehicleIcon($vehicle['vehicle_type']);
                $rankClass = '';
                if ($rank == 1) $rankClass = 'rank-1';
                elseif ($rank == 2) $rankClass = 'rank-2';
                elseif ($rank == 3) $rankClass = 'rank-3';
                
                $maxFuel = !empty($vehicles) ? max(array_column($vehicles, 'fuel_used_today_l')) : 0;
                $fuelPercent = $maxFuel > 0 ? ($vehicle['fuel_used_today_l'] / $maxFuel) * 100 : 0;
                ?>
                <div class="fuel-card vehicle-card-fuel" data-vehicle-id="<?= $vehicle['id'] ?>" 
                     data-lat="<?= $vehicle['latitude'] ?>" data-lng="<?= $vehicle['longitude'] ?>">
                    <div class="rank-badge <?= $rankClass ?>">#<?= $rank++ ?></div>
                    <div class="card-header-fuel">
                        <div class="vehicle-name-fuel">
                            <span><?= $icon ?></span>
                            <span><?= htmlspecialchars($vehicle['name']) ?></span>
                        </div>
                        <?= getStatusBadge($vehicle['status']) ?>
                    </div>
                    <div class="fuel-info-row">
                        <span class="fuel-label">⛽ Fuel Used Today:</span>
                        <span class="fuel-value"><?= number_format($vehicle['fuel_used_today_l'], 1) ?> L</span>
                    </div>
                    <div class="fuel-info-row">
                        <span class="fuel-label">📏 Distance Today:</span>
                        <span class="distance-value"><?= number_format($vehicle['distance_today_km'], 1) ?> km</span>
                    </div>
                    <div class="fuel-info-row">
                        <span class="fuel-label">⚡ Fuel Rate:</span>
                        <span class="fuel-value"><?= $vehicle['fuel_rate_l_per_100km'] ?> L/100km</span>
                    </div>
                    <div class="fuel-info-row">
                        <span class="fuel-label">💰 Est. Cost Today:</span>
                        <span class="fuel-value">₱<?= number_format($vehicle['fuel_cost_today'], 0) ?></span>
                    </div>
                    <div class="fuel-progress">
                        <div class="fuel-progress-bar" style="width: <?= $fuelPercent ?>%; background: <?= $vehicle['efficiency_color'] ?>;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- RIGHT PANEL - MAP -->
    <div class="map-panel">
        <div id="fuelMap"></div>
        <div class="map-controls">
            <button class="map-btn" onclick="zoomIn()">+</button>
            <button class="map-btn" onclick="zoomOut()">−</button>
            <button class="map-btn" onclick="centerOnVehicle()">🎯</button>
        </div>

        <div class="vehicle-detail-panel" id="detailPanel">
            <div class="detail-header">
                <div class="detail-title" id="detailTitle">Select a vehicle</div>
                <button class="close-detail" onclick="closeDetailPanel()">✕</button>
            </div>
            <div id="detailContent"></div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('fuelMap').setView([7.0731, 125.6128], 12);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    let markers = {};
    let currentVehicleId = null;
    let currentLat = null;
    let currentLng = null;

    const vehiclesData = <?= json_encode($vehicles) ?>;
    const vehicleImages = <?= json_encode($vehicleImages) ?>;
    const vehicleIconsMap = <?= json_encode($vehicleIcons) ?>;

    function getVehicleIcon(type) {
        const icons = { 'Truck': '🚛', 'Van': '🚐', 'Car': '🚗', 'SUV': '🚙', 'Bus': '🚌', 'Motorcycle': '🏍️' };
        return icons[type] || '🚗';
    }

    function getMarkerIcon(vehicle) {
        const imgUrl = vehicleImages[vehicle.vehicle_type] || vehicleImages['default'];
        const iconChar = getVehicleIcon(vehicle.vehicle_type);
        const fuelUsed = vehicle.fuel_used_today_l;
        const borderColor = fuelUsed > 50 ? '#dc2626' : (fuelUsed > 20 ? '#f97316' : '#d4af37');
        
        const html = `
            <div style="position: relative; width: 50px; height: 60px; background: transparent;">
                <div style="position: absolute; bottom: 46px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.85); color: #ffd700; font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 10px; white-space: nowrap; border: 1px solid ${borderColor}; backdrop-filter: blur(4px);">
                    ${iconChar} ${vehicle.name.substring(0, 12)}
                </div>
                <img src="${imgUrl}" style="width: 44px; height: 44px; position: absolute; bottom: 0; left: 3px; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4)); border-radius: 8px; background: transparent;">
                ${fuelUsed > 50 ? '<div style="position: absolute; top: -8px; right: -8px; font-size: 14px;">⚠️</div>' : ''}
            </div>
        `;
        return L.divIcon({ html: html, iconSize: [50, 60], iconAnchor: [25, 50], popupAnchor: [0, -45], className: 'custom-marker' });
    }

    function createPopupContent(vehicle) {
        const statusColor = vehicle.status === 'online' ? '#16a34a' : '#dc2626';
        const statusText = vehicle.status === 'online' ? 'ONLINE' : 'OFFLINE';
        
        return `
            <div style="padding: 14px; min-width: 260px; background: rgba(15, 15, 20, 0.75); border-radius: 16px; color: #fff; backdrop-filter: blur(16px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid rgba(212,175,55,0.3); padding-bottom: 8px;">
                    <div style="font-size: 16px; font-weight: bold;">${getVehicleIcon(vehicle.vehicle_type)} ${vehicle.name}</div>
                    <div style="background: ${statusColor}; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold;">${statusText}</div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12.5px;">
                    <div>⚡ Speed: <strong style="color: #d4af37;">${vehicle.current_speed} km/h</strong></div>
                    <div>⛽ Fuel Rate: <strong style="color: #d4af37;">${vehicle.fuel_rate_l_per_100km} L/100km</strong></div>
                    <div>📏 Distance: <strong style="color: #d4af37;">${vehicle.distance_today_km} km</strong></div>
                    <div>⛽ Fuel Used: <strong style="color: #d4af37;">${vehicle.fuel_used_today_l} L</strong></div>
                    <div>💰 Cost Today: <strong style="color: #d4af37;">₱${vehicle.fuel_cost_today}</strong></div>
                    <div>⭐ Efficiency: <strong style="color: ${vehicle.efficiency_color};">${vehicle.efficiency_rating}</strong></div>
                </div>
            </div>
        `;
    }

    function addAllMarkers() {
        vehiclesData.forEach(vehicle => {
            const lat = parseFloat(vehicle.latitude);
            const lng = parseFloat(vehicle.longitude);
            
            if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                const marker = L.marker([lat, lng], { icon: getMarkerIcon(vehicle) })
                    .bindPopup(createPopupContent(vehicle), { className: 'custom-popup' })
                    .addTo(map);
                
                markers[vehicle.id] = { marker, lat, lng };
            }
        });
        
        const allBounds = [];
        Object.values(markers).forEach(m => {
            allBounds.push([m.lat, m.lng]);
        });
        if (allBounds.length > 0) {
            map.fitBounds(allBounds, { padding: [50, 50] });
        }
    }

    function showVehicleDetail(vehicle) {
        document.getElementById('detailTitle').innerHTML = `${getVehicleIcon(vehicle.vehicle_type)} ${vehicle.name}`;
        document.getElementById('detailContent').innerHTML = `
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Current Speed</div>
                    <div class="detail-value">${vehicle.current_speed} km/h</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Fuel Rate</div>
                    <div class="detail-value">${vehicle.fuel_rate_l_per_100km} L/100km</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Distance Today</div>
                    <div class="detail-value">${vehicle.distance_today_km} km</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Fuel Used Today</div>
                    <div class="detail-value">${vehicle.fuel_used_today_l} L</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Cost Today</div>
                    <div class="detail-value">₱${vehicle.fuel_cost_today}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Efficiency</div>
                    <div class="detail-value" style="color: ${vehicle.efficiency_color};">${vehicle.efficiency_rating}</div>
                </div>
            </div>
            <div class="detail-stats">
                <div class="detail-stat">
                    <div class="stat-label">Yesterday Distance</div>
                    <div class="stat-value">${vehicle.distance_yesterday_km} km</div>
                </div>
                <div class="detail-stat">
                    <div class="stat-label">Yesterday Fuel</div>
                    <div class="stat-value">${vehicle.fuel_used_yesterday_l} L</div>
                </div>
                <div class="detail-stat">
                    <div class="stat-label">Yesterday Cost</div>
                    <div class="stat-value">₱${vehicle.fuel_cost_yesterday}</div>
                </div>
                <div class="detail-stat">
                    <div class="stat-label">Weekly Fuel</div>
                    <div class="stat-value">${vehicle.fuel_used_weekly_l} L</div>
                </div>
                <div class="detail-stat">
                    <div class="stat-label">Monthly Fuel</div>
                    <div class="stat-value">${vehicle.fuel_used_monthly_l} L</div>
                </div>
                <div class="detail-stat">
                    <div class="stat-label">Has Configured Rate</div>
                    <div class="stat-value">${vehicle.has_configured_rate ? '✓ Yes' : '✗ No (Default)'}</div>
                </div>
            </div>
        `;
    }

    function focusOnVehicle(vehicleId) {
        const vehicle = vehiclesData.find(v => v.id == vehicleId);
        if (!vehicle) return;
        
        currentVehicleId = vehicleId;
        currentLat = parseFloat(vehicle.latitude);
        currentLng = parseFloat(vehicle.longitude);
        
        document.querySelectorAll('.fuel-card').forEach(card => {
            card.classList.remove('active');
            if (card.dataset.vehicleId == vehicleId) {
                card.classList.add('active');
            }
        });
        
        map.setView([currentLat, currentLng], 16);
        showVehicleDetail(vehicle);
        document.getElementById('detailPanel').classList.add('show');
    }

    function closeDetailPanel() {
        document.getElementById('detailPanel').classList.remove('show');
    }

    function zoomIn() { map.zoomIn(); }
    function zoomOut() { map.zoomOut(); }
    function centerOnVehicle() {
        if (currentLat && currentLng) {
            map.setView([currentLat, currentLng], 16);
        }
    }

    // Event listeners
    document.querySelectorAll('.vehicle-card-fuel').forEach(card => {
        card.addEventListener('click', function() {
            const vehicleId = parseInt(this.dataset.vehicleId);
            focusOnVehicle(vehicleId);
        });
    });

    document.getElementById('searchFuelInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.vehicle-card-fuel').forEach(card => {
            const name = card.querySelector('.vehicle-name-fuel span:last-child')?.innerText.toLowerCase() || '';
            card.style.display = name.includes(searchTerm) ? 'block' : 'none';
        });
    });

    function applyDateFilter() {
        const date = document.getElementById('filterDate').value;
        let url = window.location.pathname;
        let params = new URLSearchParams(window.location.search);
        params.set('date', date);
        window.location.href = url + '?' + params.toString();
    }

    // Initialize map
    addAllMarkers();
    
    if (vehiclesData.length > 0) {
        setTimeout(() => {
            focusOnVehicle(vehiclesData[0].id);
        }, 500);
    }
</script>
</body>
</html>