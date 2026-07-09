<?php
// =====================================================
// get_address.php - Saves ONLY New API Results (No Duplicates)
// =====================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =========================
// CONFIGURATION
// =========================
define('CACHE_FILE', __DIR__ . '/geocode_cache.json');
define('CACHE_EXPIRY', 86400 * 30); // 30 DAYS
define('REQUEST_DELAY', 1); // 1 SECOND
define('USER_AGENT', 'VehicleTrackingSystem/1.0');
define('NEARBY_RADIUS_METERS', 50); // Coordinates within 50 meters considered same location

// =========================
// CREATE CACHE FILE IF NOT EXISTS
// =========================
if (!file_exists(CACHE_FILE)) {
    file_put_contents(CACHE_FILE, json_encode([], JSON_PRETTY_PRINT));
}

// =========================
// LOAD CACHE
// =========================
$cache = json_decode(file_get_contents(CACHE_FILE), true);
if (!is_array($cache)) {
    $cache = [];
}

// =========================
// CLEAN OLD CACHE ENTRIES
// =========================
$currentTime = time();
$cacheCleaned = false;

foreach ($cache as $key => $value) {
    if (($currentTime - $value['timestamp']) > CACHE_EXPIRY) {
        unset($cache[$key]);
        $cacheCleaned = true;
    }
}

if ($cacheCleaned) {
    saveCache($cache);
}

// =========================
// SAVE CACHE FUNCTION
// =========================
function saveCache($cache) {
    file_put_contents(CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT));
}

// =========================
// HAVERSINE FORMULA - Calculate distance between coordinates
// =========================
function distanceInMeters($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth's radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// =========================
// CHECK IF COORDINATES ALREADY EXIST IN CACHE (within radius)
// Returns existing address if found, null otherwise
// =========================
function findExistingCoordinates($lat, $lon, $cache, $maxDistance = NEARBY_RADIUS_METERS) {
    foreach ($cache as $key => $value) {
        $parts = explode(',', $key);
        if (count($parts) != 2) continue;
        
        $cachedLat = (float)$parts[0];
        $cachedLon = (float)$parts[1];
        $distance = distanceInMeters($lat, $lon, $cachedLat, $cachedLon);
        
        if ($distance <= $maxDistance) {
            return [
                'address' => $value['address'],
                'distance' => $distance,
                'cached_key' => $key
            ];
        }
    }
    return null;
}

// =========================
// REVERSE GEOCODE FUNCTION
// =========================
function reverseGeocode($lat, $lon) {
    $url = "https://nominatim.openstreetmap.org/reverse?" . http_build_query([
        'format' => 'jsonv2',
        'lat' => $lat,
        'lon' => $lon,
        'zoom' => 18,
        'addressdetails' => 1
    ]);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'User-Agent: ' . USER_AGENT,
            'Accept-Language: en'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // =========================
    // CURL ERROR
    // =========================
    if ($curlError) {
        return [
            'success' => false,
            'message' => 'CURL ERROR',
            'debug' => $curlError
        ];
    }

    // =========================
    // HTTP ERROR
    // =========================
    if ($httpCode != 200) {
        return [
            'success' => false,
            'message' => 'HTTP ERROR',
            'http_code' => $httpCode
        ];
    }

    // =========================
    // JSON DECODE
    // =========================
    $data = json_decode($response, true);
    
    if (!$data) {
        return [
            'success' => false,
            'message' => 'INVALID JSON RESPONSE'
        ];
    }

    // =========================
    // API ERROR
    // =========================
    if (isset($data['error'])) {
        return [
            'success' => false,
            'message' => $data['error']
        ];
    }

    // =========================
    // ADDRESS NOT FOUND
    // =========================
    if (!isset($data['display_name'])) {
        return [
            'success' => false,
            'message' => 'ADDRESS NOT FOUND'
        ];
    }

    // =========================
    // SHORTEN ADDRESS
    // =========================
    $displayName = $data['display_name'];
    $parts = explode(',', $displayName);
    
    if (count($parts) > 4) {
        $displayName = implode(',', array_slice($parts, 0, 4));
    }

    return [
        'success' => true,
        'address' => trim($displayName),
        'full_address' => $data['display_name']
    ];
}

// =========================
// MAIN PROCESSING
// =========================

// Get parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : 0;

// Validate parameters
if ($lat == 0 || $lon == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'INVALID LATITUDE OR LONGITUDE'
    ], JSON_PRETTY_PRINT);
    exit();
}

// Generate exact cache key (5 decimal places ~ 1.1 meters)
$exactKey = round($lat, 5) . ',' . round($lon, 5);

// =========================
// CHECK EXACT CACHE FIRST
// =========================
if (isset($cache[$exactKey])) {
    echo json_encode([
        'success' => true,
        'cached' => true,
        'new_api_call' => false,
        'address' => $cache[$exactKey]['address'],
        'message' => 'Address retrieved from exact cache (no API call made)'
    ], JSON_PRETTY_PRINT);
    exit();
}

// =========================
// CHECK IF COORDINATES ALREADY EXIST (within radius)
// IMPORTANT: This prevents duplicate API calls for nearby coordinates
// =========================
$existingCoordinates = findExistingCoordinates($lat, $lon, $cache);
if ($existingCoordinates) {
    // Store exact coordinate reference to existing address (no new API call)
    $cache[$exactKey] = [
        'address' => $existingCoordinates['address'],
        'timestamp' => time(),
        'referenced_from' => $existingCoordinates['cached_key'],
        'distance_from_reference' => round($existingCoordinates['distance'], 1)
    ];
    saveCache($cache);
    
    echo json_encode([
        'success' => true,
        'cached' => true,
        'new_api_call' => false,
        'address' => $existingCoordinates['address'],
        'distance_from_existing' => round($existingCoordinates['distance'], 1) . ' meters',
        'message' => 'Address retrieved from nearby cached coordinates (no API call made)'
    ], JSON_PRETTY_PRINT);
    exit();
}

// =========================
// NO EXISTING CACHE FOUND - MAKE NEW API CALL ONLY
// This only executes when coordinates are truly new (not within 50m of any cached location)
// =========================

// Rate limiting delay before API call
sleep(REQUEST_DELAY);

// Fetch from Nominatim (NEW API CALL)
$result = reverseGeocode($lat, $lon);

// Save to cache ONLY if successful AND it's a new unique address
if ($result['success']) {
    $cache[$exactKey] = [
        'address' => $result['address'],
        'timestamp' => time(),
        'full_address' => $result['full_address'] ?? $result['address']
    ];
    saveCache($cache);
    
    echo json_encode([
        'success' => true,
        'cached' => false,
        'new_api_call' => true,
        'address' => $result['address'],
        'message' => 'New address fetched from API and saved to cache (no duplicate nearby coordinates found)'
    ], JSON_PRETTY_PRINT);
} else {
    // API call failed - return error without saving to cache
    echo json_encode([
        'success' => false,
        'new_api_call' => true,
        'saved_to_cache' => false,
        'message' => $result['message']
    ], JSON_PRETTY_PRINT);
}

exit();
?>