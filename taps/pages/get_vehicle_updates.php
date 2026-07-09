<?php
// get_vehicle_updates.php - Debug version
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Temporarily output errors to browser
header('Content-Type: application/json');

try {
    // Try different database paths
    $dbPaths = [
        __DIR__ . "/DB/dbcon.php",
        __DIR__ . "/../DB/dbcon.php",
        "DB/dbcon.php",
        "./DB/dbcon.php"
    ];
    
    $dbFound = false;
    foreach ($dbPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $dbFound = true;
            break;
        }
    }
    
    if (!$dbFound) {
        throw new Exception("Database file not found. Tried: " . implode(', ', $dbPaths));
    }
    
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }
    
    $sql = "
    SELECT
        d.id,
        COALESCE(d.name, 'Unknown Device') AS name,
        COALESCE(d.uniqueid, 'N/A') AS uniqueid,
        COALESCE(d.status, 'UNKNOWN') AS status,
        d.lastupdate,
        COALESCE(d.vehicle_type, 'Car') AS vehicle_type,
        p.latitude,
        p.longitude,
        COALESCE(p.speed, 0) AS speed,
        COALESCE(p.address, '') AS address,
        p.fixtime
    FROM tc_devices d
    LEFT JOIN tc_positions p ON d.id = p.deviceId
    WHERE p.latitude IS NOT NULL
    AND p.longitude IS NOT NULL
    ORDER BY d.name
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [];
    foreach ($vehicles as $vehicle) {
        $status = strtolower(trim($vehicle['status'] ?? 'unknown'));
        if ($status === '' || $status === 'unknown') {
            $status = 'offline';
        }
        
        $response[] = [
            'id' => (int)$vehicle['id'],
            'name' => $vehicle['name'],
            'uniqueid' => $vehicle['uniqueid'],
            'status' => $status === 'online' ? 'online' : 'offline',
            'lastupdate' => $vehicle['lastupdate'],
            'vehicle_type' => $vehicle['vehicle_type'] ?? 'Car',
            'latitude' => (float)$vehicle['latitude'],
            'longitude' => (float)$vehicle['longitude'],
            'speed' => (float)$vehicle['speed'],
            'address' => $vehicle['address'],
            'fixtime' => $vehicle['fixtime']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
?>