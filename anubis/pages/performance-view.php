<?php
// =====================================================
// performance.php - Vehicle Performance Dashboard
// =====================================================

require_once "./DB/dbcon.php";

// ====================== DATE FILTER ======================
$filterDate = $_GET['date'] ?? date('Y-m-d');

// =====================================================
// MAIN QUERY - CTE BASED FOR SQL SERVER 2014
// =====================================================
$sql = "
WITH base AS (
    SELECT
        p.deviceId,
        p.latitude,
        p.longitude,
        p.speed,
        -- numeric-safe conversions for speed to avoid NULL/format issues
        TRY_CONVERT(float, p.speed) AS speed_num,
        p.fixTime,
        p.attributes,
        LAG(TRY_CONVERT(float, p.speed)) OVER (PARTITION BY p.deviceId ORDER BY p.fixTime) AS prev_speed_num,
        LAG(p.latitude) OVER (PARTITION BY p.deviceId ORDER BY p.fixTime) AS prev_lat,
        LAG(p.longitude) OVER (PARTITION BY p.deviceId ORDER BY p.fixTime) AS prev_lng,
        LAG(p.attributes) OVER (PARTITION BY p.deviceId ORDER BY p.fixTime) AS prev_attributes
    FROM tc_positions p
    WHERE CAST(p.fixTime AS DATE) = CAST(? AS DATE)
),

metrics AS (
    SELECT
        deviceId,
        SUM(CASE WHEN speed_num > 0 THEN 1 ELSE 0 END) AS moving_points,
        SUM(CASE WHEN speed_num = 0 THEN 1 ELSE 0 END) AS idle_points,
        MAX(speed_num) AS max_speed_today,
        AVG(CASE WHEN speed_num > 0 THEN speed_num END) AS avg_speed_today,
        SUM(CASE WHEN prev_speed_num IS NOT NULL AND prev_speed_num - speed_num > 20 THEN 1 ELSE 0 END) AS sudden_brakes,
        SUM(CASE WHEN speed_num = 0 THEN 0.5 ELSE 0 END) AS idle_minutes,
        SUM(CASE WHEN speed_num BETWEEN 1 AND 20 THEN 0.5 ELSE 0 END) AS traffic_minutes,
        SUM(CASE WHEN speed_num > 100 THEN 1 ELSE 0 END) AS overspeeding_count,
        MAX(CASE WHEN speed_num > 100 THEN speed_num ELSE 0 END) AS max_overspeed,
        -- Calculate engine off minutes based on ignition status
        SUM(
            CASE 
                WHEN CHARINDEX('\"ignition\":false', attributes) > 0 
                     OR CHARINDEX('\"ignition\": false', attributes) > 0
                THEN 0.5 
                ELSE 0 
            END
        ) AS engine_off_minutes
    FROM base
    GROUP BY deviceId
),

distance AS (
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
        ) AS distance_today_km
    FROM base
    GROUP BY deviceId
),

operating_hours AS (
    SELECT
        deviceId,
        SUM(CASE WHEN speed_num > 0 THEN 1 ELSE 0 END) * 0.00833 AS operating_hours_today
    FROM base
    GROUP BY deviceId
),

-- Engine on distance (only when ignition is true in consecutive points)
engine_on_distance AS (
    SELECT
        deviceId,
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
)

SELECT
    d.id,
    d.name,
    d.uniqueid,
    d.vehicle_type,
    d.status,
    d.lastUpdate,
    d.fuel_consumption_liter_per_km,
    p.latitude,
    p.longitude,
    p.speed AS current_speed,
    p.fixTime,
    p.attributes AS current_attributes,
    ISNULL(dt.distance_today_km, 0) AS distance_today_km,
    ISNULL(eod.engine_on_distance_km, 0) AS engine_on_distance_km,
    ISNULL(m.max_speed_today, 0) AS max_speed_today,
    ISNULL(m.avg_speed_today, 0) AS avg_speed_today,
    ISNULL(m.sudden_brakes, 0) AS sudden_brakes_today,
    ISNULL(m.idle_minutes, 0) AS idle_minutes,
    ISNULL(m.traffic_minutes, 0) AS traffic_minutes,
    ISNULL(m.overspeeding_count, 0) AS overspeeding_count_today,
    ISNULL(m.max_overspeed, 0) AS max_overspeed,
    ISNULL(m.engine_off_minutes, 0) AS engine_off_minutes_today,
    ISNULL(oh.operating_hours_today, 0) AS operating_hours_today
FROM tc_devices d
-- ensure we get the latest position per device (positionId may be stale)
OUTER APPLY (
    SELECT TOP 1 * FROM tc_positions tp2 WHERE tp2.deviceId = d.id ORDER BY tp2.fixTime DESC
) p
LEFT JOIN metrics m ON m.deviceId = d.id
LEFT JOIN distance dt ON dt.deviceId = d.id
LEFT JOIN engine_on_distance eod ON eod.deviceId = d.id
LEFT JOIN operating_hours oh ON oh.deviceId = d.id
WHERE p.latitude IS NOT NULL
  AND p.longitude IS NOT NULL
ORDER BY dt.distance_today_km DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$filterDate]);
$vehiclesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// PROCESS VEHICLE DATA
// =====================================================
$vehicles = [];
$totalDistanceToday = 0;
$totalFuelToday = 0;
$totalOverspeeding = 0;
$totalSuddenBrakes = 0;
$totalIdleMinutes = 0;
$totalTrafficMinutes = 0;
$totalEngineOffMinutes = 0;

$fuelPricePerLiter = $_SESSION['FUEL_COST'] ?? 65.00; // Default to 65 if not set

function getPerformanceRating($value, $type) {
    switch($type) {
        case 'fuel':
            if ($value <= 10) return ['Excellent', '#22c55e'];
            if ($value <= 15) return ['Good', '#84cc16'];
            if ($value <= 20) return ['Average', '#eab308'];
            if ($value <= 30) return ['Poor', '#f97316'];
            return ['Very Poor', '#dc2626'];
        case 'brakes':
            if ($value <= 2) return ['Excellent', '#22c55e'];
            if ($value <= 5) return ['Good', '#84cc16'];
            if ($value <= 10) return ['Average', '#eab308'];
            if ($value <= 20) return ['Poor', '#f97316'];
            return ['Critical', '#dc2626'];
        case 'idle':
            if ($value <= 30) return ['Excellent', '#22c55e'];
            if ($value <= 60) return ['Good', '#84cc16'];
            if ($value <= 120) return ['Average', '#eab308'];
            return ['Poor', '#f97316'];
        case 'engine_off':
            if ($value <= 30) return ['Excellent', '#22c55e'];
            if ($value <= 60) return ['Good', '#84cc16'];
            if ($value <= 120) return ['Average', '#eab308'];
            return ['High', '#f97316'];
        default:
            return ['Normal', '#888'];
    }
}

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
    
    // Use engine-on distance for fuel calculation (only when ignition is on)
    $distanceToday = isset($row['engine_on_distance_km']) ? floatval($row['engine_on_distance_km']) : 0;
    $fuelUsedToday = $distanceToday * $fuelRate;
    $fuelCostToday = $fuelUsedToday * $fuelPricePerLiter;
    
    $overspeedingCount = isset($row['overspeeding_count_today']) ? intval($row['overspeeding_count_today']) : 0;
    $suddenBrakes = isset($row['sudden_brakes_today']) ? intval($row['sudden_brakes_today']) : 0;
    $idleMinutes = isset($row['idle_minutes']) ? floatval($row['idle_minutes']) : 0;
    $trafficMinutes = isset($row['traffic_minutes']) ? floatval($row['traffic_minutes']) : 0;
    $trafficHours = round($trafficMinutes / 60, 1);
    $operatingHours = isset($row['operating_hours_today']) ? floatval($row['operating_hours_today']) : 0;
    $maxSpeedToday = isset($row['max_speed_today']) ? floatval($row['max_speed_today']) : 0;
    $avgSpeedToday = isset($row['avg_speed_today']) ? floatval($row['avg_speed_today']) : 0;
    $maxOverspeed = isset($row['max_overspeed']) ? floatval($row['max_overspeed']) : 0;
    $currentSpeed = isset($row['current_speed']) ? floatval($row['current_speed']) : 0;
    
    // Engine off minutes from the query (based on ignition status)
    $engineOffMinutes = isset($row['engine_off_minutes_today']) ? floatval($row['engine_off_minutes_today']) : 0;
    
    $vehicleId = isset($row['id']) ? $row['id'] : 0;
    
    // Weekly and Monthly distance (using engine-on distance for consistency)
    $distanceWeek = 0;
    $distanceMonth = 0;
    
    if ($vehicleId > 0) {
        // Weekly distance (engine-on only)
        $weekSql = "
            SELECT 
                ISNULL(SUM(
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
                ), 0) AS total_km
            FROM (
                SELECT
                    latitude,
                    longitude,
                    attributes,
                    LAG(latitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lat,
                    LAG(longitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lng,
                    LAG(attributes) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_attributes
                FROM tc_positions
                WHERE deviceId = ? AND fixTime >= DATEADD(day, -7, CAST(? AS DATE))
            ) t
        ";
        $weekStmt = $conn->prepare($weekSql);
        $weekStmt->execute([$vehicleId, $filterDate]);
        $weekRow = $weekStmt->fetch(PDO::FETCH_ASSOC);
        $distanceWeek = isset($weekRow['total_km']) ? floatval($weekRow['total_km']) : 0;
        
        // Monthly distance (engine-on only)
        $monthSql = "
            SELECT 
                ISNULL(SUM(
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
                ), 0) AS total_km
            FROM (
                SELECT
                    latitude,
                    longitude,
                    attributes,
                    LAG(latitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lat,
                    LAG(longitude) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_lng,
                    LAG(attributes) OVER (PARTITION BY deviceId ORDER BY fixTime) AS prev_attributes
                FROM tc_positions
                WHERE deviceId = ? AND fixTime >= DATEADD(day, -30, CAST(? AS DATE))
            ) t
        ";
        $monthStmt = $conn->prepare($monthSql);
        $monthStmt->execute([$vehicleId, $filterDate]);
        $monthRow = $monthStmt->fetch(PDO::FETCH_ASSOC);
        $distanceMonth = isset($monthRow['total_km']) ? floatval($monthRow['total_km']) : 0;
    }
    
    $parkingMinutes = $idleMinutes;
    // Convert minutes -> hours for display (round 1 decimal)
    $idleHours = round($idleMinutes / 60, 1);
    $parkingHours = round($parkingMinutes / 60, 1);
    $engineOffHours = round($engineOffMinutes / 60, 1);
    
    $rating = getPerformanceRating($fuelRate * 100, 'fuel');
    $brakeRating = getPerformanceRating($suddenBrakes, 'brakes');
    $idleRating = getPerformanceRating($idleMinutes, 'idle');
    $engineOffRating = getPerformanceRating($engineOffMinutes, 'engine_off');
    
    // Performance score (penalize engine off time as well)
    $score = 100;
    $score -= min(30, $overspeedingCount * 2);
    $score -= min(25, $suddenBrakes * 2);
    $score -= min(25, $idleMinutes / 2);
    $score -= min(20, $trafficMinutes / 3);
    $score -= min(15, $engineOffMinutes / 4); // Penalize excessive engine off time
    $score = max(0, min(100, $score));
    
    $overallRating = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Good' : ($score >= 40 ? 'Average' : ($score >= 20 ? 'Poor' : 'Critical')));
    $overallColor = $score >= 80 ? '#22c55e' : ($score >= 60 ? '#84cc16' : ($score >= 40 ? '#eab308' : ($score >= 20 ? '#f97316' : '#dc2626')));
    
    // Check current ignition status
    $currentIgnition = false;
    if (isset($row['current_attributes'])) {
        $currentIgnition = (strpos($row['current_attributes'], '"ignition":true') !== false || 
                           strpos($row['current_attributes'], '"ignition": true') !== false);
    }
    
    $vehicles[] = [
        'id' => $vehicleId,
        'name' => isset($row['name']) ? $row['name'] : 'Unknown',
        'uniqueid' => isset($row['uniqueid']) ? $row['uniqueid'] : 'N/A',
        'vehicle_type' => isset($row['vehicle_type']) ? $row['vehicle_type'] : 'Unknown',
        'status' => isset($row['status']) ? $row['status'] : 'offline',
        'latitude' => isset($row['latitude']) ? $row['latitude'] : 0,
        'longitude' => isset($row['longitude']) ? $row['longitude'] : 0,
        'current_speed' => round($currentSpeed, 1),
        'current_ignition' => $currentIgnition,
        'avg_speed_today' => round($avgSpeedToday, 1),
        'max_speed_today' => round($maxSpeedToday, 1),
        'distance_today_km' => round($distanceToday, 1),
        'distance_week_km' => round($distanceWeek, 1),
        'distance_month_km' => round($distanceMonth, 1),
        'fuel_rate_l_per_100km' => round($fuelRate * 100, 1),
        'fuel_used_today_l' => round($fuelUsedToday, 1),
        'fuel_cost_today' => round($fuelCostToday, 0),
        'overspeeding_count' => $overspeedingCount,
        'max_overspeed' => round($maxOverspeed, 1),
        'sudden_brakes' => $suddenBrakes,
        'idle_minutes' => $idleHours,
        'parking_minutes' => $parkingHours,
        'engine_off_minutes' => $engineOffHours,
        'engine_off_rating' => $engineOffRating[0],
        'engine_off_color' => $engineOffRating[1],
        'traffic_minutes' => $trafficHours,
        'operating_hours' => round($operatingHours, 1),
        'performance_score' => round($score),
        'performance_rating' => $overallRating,
        'performance_color' => $overallColor,
        'fuel_rating' => $rating[0],
        'fuel_color' => $rating[1],
        'brake_rating' => $brakeRating[0],
        'brake_color' => $brakeRating[1],
        'idle_rating' => $idleRating[0],
        'idle_color' => $idleRating[1]
    ];
    
    // ACCUMULATE TOTALS FOR SUMMARY
    $totalDistanceToday += $distanceToday;
    $totalFuelToday += $fuelUsedToday;
    $totalOverspeeding += $overspeedingCount;
    $totalSuddenBrakes += $suddenBrakes;
    $totalIdleMinutes += $idleMinutes;
    $totalTrafficMinutes += $trafficMinutes;
    $totalEngineOffMinutes += $engineOffMinutes;
}

// Sort by performance score (worst first)
usort($vehicles, function($a, $b) {
    return $a['performance_score'] <=> $b['performance_score'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Performance Dashboard | Idling · Brakes · Overspeeding · Traffic</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0a0f; height: 100vh; overflow: hidden; }
        .performance-dashboard { display: flex; height: 90vh; width: 100%;}
        
        .performance-sidebar { width: 520px; background: linear-gradient(180deg, #0f0f14 0%, #0a0a0f 100%); border-right: 1px solid rgba(212, 175, 55, 0.2); display: flex; flex-direction: column; overflow: hidden; z-index: 10; }
        .performance-header { padding: 20px; background: linear-gradient(135deg, #1a1a24, #0f0f14); border-bottom: 1px solid rgba(212, 175, 55, 0.2); }
        .performance-header h1 { font-size: 20px; color: #d4af37; display: flex; align-items: center; gap: 10px; }
        .performance-header p { font-size: 11px; color: #888; margin-top: 8px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 15px; }
        .stat-card { background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 8px; text-align: center; border: 1px solid rgba(212, 175, 55, 0.1); }
        .stat-card .stat-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .stat-card .stat-value { font-size: 16px; font-weight: bold; color: #d4af37; margin-top: 4px; }
        
        .search-area { padding: 15px 20px; background: rgba(0, 0, 0, 0.3); }
        .search-area input { width: 100%; padding: 10px 15px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 12px; color: #fff; font-size: 13px; outline: none; }
        
        .vehicle-list { flex: 1; overflow-y: auto; padding: 10px 15px 20px; }
        .vehicle-list::-webkit-scrollbar { width: 5px; }
        .vehicle-list::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); }
        .vehicle-list::-webkit-scrollbar-thumb { background: #d4af37; border-radius: 10px; }
        
        .perf-card { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(212, 175, 55, 0.1); border-radius: 16px; padding: 14px; margin-bottom: 12px; cursor: pointer; transition: all 0.25s ease; position: relative; }
        .perf-card:hover { background: rgba(212, 175, 55, 0.08); transform: translateX(5px); }
        .perf-card.active { background: rgba(212, 175, 55, 0.12); border-color: rgba(212, 175, 55, 0.5); }
        
        .score-circle { position: absolute; top: 12px; right: 12px; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; color: white; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-right: 55px; }
        .vehicle-name { display: flex; align-items: center; gap: 8px; font-weight: 600; color: #fff; font-size: 15px; }
        
        .metric-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .metric-item { text-align: center; padding: 5px; background: rgba(0, 0, 0, 0.3); border-radius: 8px; }
        .metric-label { color: #888; font-size: 9px; }
        .metric-value { color: #d4af37; font-weight: bold; font-size: 12px; margin-top: 3px; }
        
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .status-badge.online { background: #16a34a; color: white; }
        .status-badge.offline { background: #dc2626; color: white; }
        
        .map-panel { flex: 1; position: relative; background: #1a1a24; }
        #performanceMap { width: 100%; height: 100%; }
        
        .map-controls { position: absolute; bottom: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
        .map-btn { width: 44px; height: 44px; border: none; border-radius: 12px; background: rgba(0,0,0,0.85); color: #d4af37; font-size: 20px; cursor: pointer; }

        @media (max-width: 900px) {
            body, html { height: auto; overflow: auto; }
            .performance-dashboard { flex-direction: column; height: auto; min-height: 100vh; }
            .performance-sidebar { width: 100%; max-height: none; border-right: none; border-bottom: 1px solid rgba(212, 175, 55, 0.2); }
            .map-panel { width: 100%; height: auto; min-height: auto; display: flex; flex-direction: column; }
            #performanceMap { min-height: 55vh; height: auto; order: 2; }
            .detail-panel { position: relative; transform: none; pointer-events: auto; order: 1; width: calc(100% - 40px); margin: 20px; max-height: none; }
            .map-controls { position: absolute; bottom: 20px; right: 20px; }
            .performance-header { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .metric-row { grid-template-columns: repeat(2, 1fr); }
            .detail-metrics { grid-template-columns: repeat(2, 1fr); }
            .warning-section { grid-template-columns: 1fr; }
            .perf-card { margin-bottom: 14px; }
        }
        
        .detail-panel { position: absolute; bottom: 20px; left: 20px; right: 20px; background: rgba(0,0,0,0.92); backdrop-filter: blur(12px); border-radius: 20px; padding: 15px; border: 1px solid rgba(212,175,55,0.3); z-index: 1000; transform: translateY(100%); transition: transform 0.3s ease; pointer-events: none; max-height: 45%; overflow-y: auto; }
        .detail-panel.show { transform: translateY(0); pointer-events: auto; }
        .detail-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid rgba(212,175,55,0.2); }
        .detail-title { font-size: 18px; font-weight: bold; color: #d4af37; }
        .close-detail { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; }
        
        .detail-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 15px; }
        .detail-metric { text-align: center; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 12px; }
        .detail-metric .label { font-size: 10px; color: #888; }
        .detail-metric .value { font-size: 16px; font-weight: bold; color: #d4af37; margin-top: 4px; }
        
        .warning-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(212,175,55,0.15); }
        .warning-item { display: flex; align-items: center; gap: 8px; font-size: 11px; padding: 6px; border-radius: 8px; background: rgba(0,0,0,0.3); }
        .warning-icon { font-size: 14px; }
        
        /* Make marker images transparent */
        .leaflet-div-icon {
            background: transparent !important;
            border: none !important;
        }
        
        img[src*="flaticon"] {
            background: transparent !important;
            mix-blend-mode: normal;
        }
        
        .ignition-badge {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: 6px;
        }
        .ignition-on { background: #22c55e; box-shadow: 0 0 4px #22c55e; }
        .ignition-off { background: #dc2626; box-shadow: 0 0 4px #dc2626; }
    </style>
</head>
<body>
<div class="performance-dashboard">
    <div class="performance-sidebar">
        <div class="performance-header">
            <h1><span>📊</span> Vehicle Performance</h1>
                <p>Ranked by performance score · Idling · Sudden Brakes · Overspeeding · Traffic</p>
                <div class="date-filter" style="margin-top:10px;">
                    <input type="date" id="filterDate" value="<?= $filterDate ?>" style="padding:8px 12px; background:rgba(255,255,255,0.08); border:1px solid rgba(212,175,55,0.4); color:#fff; border-radius:8px;">
                    <button onclick="applyDateFilter()" style="padding:8px 12px; background:#22c55e; color:white; border:none; border-radius:8px; cursor:pointer; margin-left:8px;">Apply</button>
                </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-label">Total Distance</div><div class="stat-value"><?= number_format($totalDistanceToday, 1) ?> km</div></div>
                <div class="stat-card"><div class="stat-label">Total Fuel</div><div class="stat-value"><?= number_format($totalFuelToday, 1) ?> L</div></div>
                <div class="stat-card"><div class="stat-label">Overspeeding</div><div class="stat-value"><?= $totalOverspeeding ?></div></div>
                <div class="stat-card"><div class="stat-label">Sudden Brakes</div><div class="stat-value"><?= $totalSuddenBrakes ?></div></div>
            </div>
        </div>
        <div class="search-area"><input type="text" id="searchPerf" placeholder="🔍 Search vehicle..."></div>
        <div class="vehicle-list" id="vehicleList">
            <?php if (count($vehicles) > 0): ?>
                <?php foreach ($vehicles as $vehicle): ?>
                    <?php $icon = getVehicleIcon($vehicle['vehicle_type']); ?>
                    <div class="perf-card vehicle-card" data-vehicle-id="<?= $vehicle['id'] ?>"
                         data-lat="<?= $vehicle['latitude'] ?>" data-lng="<?= $vehicle['longitude'] ?>">
                        <div class="score-circle" style="background: <?= $vehicle['performance_color'] ?>80; border: 2px solid <?= $vehicle['performance_color'] ?>;">
                            <?= $vehicle['performance_score'] ?>
                        </div>
                        <div class="card-header">
                            <div class="vehicle-name">
                                <span><?= $icon ?></span>
                                <span><?= htmlspecialchars($vehicle['name']) ?></span>
                                <span class="ignition-badge <?= $vehicle['current_ignition'] ? 'ignition-on' : 'ignition-off' ?>" title="<?= $vehicle['current_ignition'] ? 'Engine ON' : 'Engine OFF' ?>"></span>
                            </div>
                            <?= getStatusBadge($vehicle['status']) ?>
                        </div>
                        <div class="metric-row">
                            <div class="metric-item"><div class="metric-label">📏 Distance</div><div class="metric-value"><?= $vehicle['distance_today_km'] ?> km</div></div>
                            <div class="metric-item"><div class="metric-label">⛽ Fuel</div><div class="metric-value"><?= $vehicle['fuel_used_today_l'] ?> L</div></div>
                            <div class="metric-item"><div class="metric-label">⚡ Avg Speed</div><div class="metric-value"><?= $vehicle['avg_speed_today'] ?> km/h</div></div>
                            <div class="metric-item"><div class="metric-label">🛑 Brakes</div><div class="metric-value" style="color: <?= $vehicle['brake_color'] ?>"><?= $vehicle['sudden_brakes'] ?></div></div>
                            <div class="metric-item"><div class="metric-label">⚠️ Overspeed</div><div class="metric-value" style="color: <?= $vehicle['overspeeding_count'] > 0 ? '#f97316' : '#888' ?>"><?= $vehicle['overspeeding_count'] ?></div></div>
                            <div class="metric-item"><div class="metric-label">⏱️ Engine Off</div><div class="metric-value" style="color: <?= $vehicle['engine_off_color'] ?>"><?= $vehicle['engine_off_minutes'] ?> hrs</div></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color: #888; text-align: center; padding: 20px;">No vehicle data available</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="map-panel">
        <div id="performanceMap"></div>
        <div class="map-controls">
            <button class="map-btn" onclick="zoomIn()">+</button>
            <button class="map-btn" onclick="zoomOut()">−</button>
            <button class="map-btn" onclick="centerVehicle()">🎯</button>
        </div>
        <div class="detail-panel" id="detailPanel">
            <div class="detail-header">
                <div class="detail-title" id="detailTitle">Select a vehicle</div>
                <button class="close-detail" onclick="closeDetail()">✕</button>
            </div>
            <div id="detailContent"></div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('performanceMap').setView([7.0731, 125.6128], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    
    const vehiclesData = <?= json_encode($vehicles) ?>;
    const vehicleImages = <?= json_encode($vehicleImages) ?>;
    
    let markers = {};
    let currentVehicleId = null;
    let currentLat = null;
    let currentLng = null;
    
    function getVehicleIcon(type) {
        const icons = { 'Truck': '🚛', 'Van': '🚐', 'Car': '🚗', 'SUV': '🚙', 'Bus': '🚌', 'Motorcycle': '🏍️' };
        return icons[type] || '🚗';
    }
    
    function getMarkerIcon(vehicle) {
        const imgUrl = vehicleImages[vehicle.vehicle_type] || vehicleImages['default'];
        const iconChar = getVehicleIcon(vehicle.vehicle_type);
        const ignitionColor = vehicle.current_ignition ? '#22c55e' : '#dc2626';
        const ignitionText = vehicle.current_ignition ? 'ON' : 'OFF';
        
        const html = `<div style="position: relative; width: 50px; height: 60px; background: transparent;">
            <div style="position: absolute; bottom: 46px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.85); color: #ffd700; font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 10px; white-space: nowrap; border: 1px solid ${ignitionColor}; backdrop-filter: blur(4px);">
                ${iconChar} ${vehicle.name.substring(0, 12)} <span style="color: ${ignitionColor};">⚡${ignitionText}</span>
            </div>
            <img src="${imgUrl}" style="width: 44px; height: 44px; position: absolute; bottom: 0; left: 3px; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4)); border-radius: 8px; background: transparent; mix-blend-mode: multiply;">
            ${vehicle.overspeeding_count > 0 ? '<div style="position: absolute; top: -8px; right: -8px; font-size: 14px;">⚠️</div>' : ''}
        </div>`;
        return L.divIcon({ html: html, iconSize: [50, 60], iconAnchor: [25, 50], popupAnchor: [0, -45], className: 'custom-marker' });
    }
    
    function addMarkers() {
        vehiclesData.forEach(v => {
            if (v.latitude && v.longitude && v.latitude != 0) {
                const marker = L.marker([v.latitude, v.longitude], { icon: getMarkerIcon(v) })
                    .bindPopup(`<b>${v.name}</b><br>📏 ${v.distance_today_km} km | ⛽ ${v.fuel_used_today_l} L<br>🛑 ${v.sudden_brakes} brakes | ⚠️ ${v.overspeeding_count} overspeed<br>⚡ Engine: ${v.current_ignition ? 'ON' : 'OFF'}`)
                    .addTo(map);
                markers[v.id] = { marker, lat: v.latitude, lng: v.longitude };
            }
        });
        const bounds = Object.values(markers).map(m => [m.lat, m.lng]);
        if (bounds.length) map.fitBounds(bounds, { padding: [50, 50] });
    }
    
    function showVehicleDetail(vehicle) {
        document.getElementById('detailTitle').innerHTML = `${getVehicleIcon(vehicle.vehicle_type)} ${vehicle.name} <span style="font-size:12px;">⚡ ${vehicle.current_ignition ? 'Engine ON' : 'Engine OFF'}</span>`;
        document.getElementById('detailContent').innerHTML = `
            <div class="detail-metrics">
                <div class="detail-metric"><div class="label">📏 Distance</div><div class="value">${vehicle.distance_today_km} km</div></div>
                <div class="detail-metric"><div class="label">⛽ Fuel Used</div><div class="value">${vehicle.fuel_used_today_l} L</div></div>
                <div class="detail-metric"><div class="label">💰 Fuel Cost</div><div class="value">₱${vehicle.fuel_cost_today}</div></div>
                <div class="detail-metric"><div class="label">⚡ Avg Speed</div><div class="value">${vehicle.avg_speed_today} km/h</div></div>
                <div class="detail-metric"><div class="label">🏁 Max Speed</div><div class="value">${vehicle.max_speed_today} km/h</div></div>
                <div class="detail-metric"><div class="label">📊 Performance</div><div class="value" style="color:${vehicle.performance_color}">${vehicle.performance_score}/100</div></div>
                <div class="detail-metric"><div class="label">⏱️ Operating Hrs</div><div class="value">${vehicle.operating_hours} hrs</div></div>
                <div class="detail-metric"><div class="label">🔋 Engine Off</div><div class="value" style="color:${vehicle.engine_off_color}">${vehicle.engine_off_minutes} hrs</div></div>
            </div>
            <div class="warning-section">
                <div class="warning-item"><span class="warning-icon">⚠️</span> <span>Overspeeding: <strong>${vehicle.overspeeding_count}</strong> times<br><small>Max: ${vehicle.max_overspeed} km/h</small></span></div>
                <div class="warning-item"><span class="warning-icon">🛑</span> <span>Sudden Brakes: <strong style="color:${vehicle.brake_color}">${vehicle.sudden_brakes}</strong><br><small>${vehicle.brake_rating}</small></span></div>
                <div class="warning-item"><span class="warning-icon">⏸️</span> <span>Idling: <strong>${vehicle.idle_minutes}</strong> hrs<br><small>Parking: ${vehicle.parking_minutes} hrs</small></span></div>
                <div class="warning-item"><span class="warning-icon">🚦</span> <span>Traffic: <strong>${vehicle.traffic_minutes}</strong> hrs<br><small>Speed &lt; 20 km/h</small></span></div>
                <div class="warning-item"><span class="warning-icon">⛽</span> <span>Fuel Rate: <strong>${vehicle.fuel_rate_l_per_100km}</strong> L/100km<br><small>${vehicle.fuel_rating}</small></span></div>
                <div class="warning-item"><span class="warning-icon">🎯</span> <span>Overall: <strong style="color:${vehicle.performance_color}">${vehicle.performance_rating}</strong><br><small>Score: ${vehicle.performance_score}/100</small></span></div>
            </div>
        `;
        document.getElementById('detailPanel').classList.add('show');
    }
    
    function focusVehicle(vehicleId) {
        const vehicle = vehiclesData.find(v => v.id == vehicleId);
        if (!vehicle) return;
        currentVehicleId = vehicleId;
        currentLat = parseFloat(vehicle.latitude);
        currentLng = parseFloat(vehicle.longitude);
        document.querySelectorAll('.perf-card').forEach(c => c.classList.remove('active'));
        document.querySelector(`.perf-card[data-vehicle-id="${vehicleId}"]`)?.classList.add('active');
        map.setView([currentLat, currentLng], 16);
        showVehicleDetail(vehicle);
    }
    
    function closeDetail() { document.getElementById('detailPanel').classList.remove('show'); }
    function zoomIn() { map.zoomIn(); }
    function zoomOut() { map.zoomOut(); }
    function centerVehicle() { if (currentLat && currentLng) map.setView([currentLat, currentLng], 16); }
    
    document.querySelectorAll('.vehicle-card').forEach(card => {
        card.addEventListener('click', function() { focusVehicle(parseInt(this.dataset.vehicleId)); });
    });
    
    document.getElementById('searchPerf').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.vehicle-card').forEach(card => {
            const name = card.querySelector('.vehicle-name span:last-child')?.innerText.toLowerCase() || '';
            card.style.display = name.includes(term) ? 'block' : 'none';
        });
    });
    
    addMarkers();
    if (vehiclesData.length > 0) setTimeout(() => focusVehicle(vehiclesData[0].id), 500);

    function applyDateFilter() {
        const date = document.getElementById('filterDate').value;
        let url = window.location.pathname;
        let params = new URLSearchParams(window.location.search);
        params.set('date', date);
        window.location.href = url + '?' + params.toString();
    }
</script>
</body>
</html>