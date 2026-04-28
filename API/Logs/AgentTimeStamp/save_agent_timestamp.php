
<?php

header('ngrok-skip-browser-warning: true');

/**
 * API: Save Complete Agent Timestamp Data (WITH UPDATE SUPPORT)
 * Matches ALL fields from Dash_Agent_Time_Stamp table
 * Endpoint: /API/Logs/AgentTimeStamp/save_agent_timestamp.php
 * Method: POST
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Sync-ID");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow POST and PUT
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use POST or PUT.']);
    exit();
}

// Define log directory
define('LOG_DIR', __DIR__);

// Create directory if not exists
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit();
}

// Get sync_id from header or body
$syncId = $_SERVER['HTTP_X_SYNC_ID'] ?? $input['sync_id'] ?? null;

// Extract records
if (isset($input['records']) && is_array($input['records'])) {
    $records = $input['records'];
} elseif (isset($input[0])) {
    $records = $input;
} else {
    $records = [$input];
}

// Validate records
if (empty($records)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No records found']);
    exit();
}

// If no sync_id provided, generate one based on first agent and date
if (!$syncId && isset($records[0]['AGENT_ID'])) {
    $syncId = date('Y-m-d') . '_' . $records[0]['AGENT_ID'];
} elseif (!$syncId && isset($records[0]['agent_id'])) {
    $syncId = date('Y-m-d') . '_' . $records[0]['agent_id'];
} elseif (!$syncId) {
    $syncId = 'sync_' . date('Ymd_His');
}

// Check if file with this sync_id already exists
$existingFile = null;
$files = glob(LOG_DIR . '/*.json');

foreach ($files as $file) {
    $content = json_decode(file_get_contents($file), true);
    if ($content && isset($content['metadata']['sync_info']['sync_id']) && $content['metadata']['sync_info']['sync_id'] === $syncId) {
        $existingFile = $file;
        break;
    }
}

// Process all records
$savedRecords = [];
$errors = [];
$currentTimestamp = date('Y-m-d H:i:s');
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
$currentUnixTimestamp = time();
$currentMicrotime = microtime(true);

foreach ($records as $index => $record) {
    // Extract ALL fields from the table (case-insensitive)
    $lineId = $record['LINE_ID'] ?? $record['line_id'] ?? null;
    $companyId = $record['COMPANY_ID'] ?? $record['company_id'] ?? null;
    $siteId = $record['SITE_ID'] ?? $record['site_id'] ?? null;
    $agentId = $record['AGENT_ID'] ?? $record['agent_id'] ?? null;
    $vehicleId = $record['VEHICLE_ID'] ?? $record['vehicle_id'] ?? null;
    $deliveryDate = $record['DELIVERY_DATE'] ?? $record['delivery_date'] ?? null;
    $latCaptured = $record['LAT_CAPTURED'] ?? $record['lat_captured'] ?? null;
    $longCaptured = $record['LONG_CAPTURED'] ?? $record['long_captured'] ?? null;
    $timeStamp = $record['TIME_STAMP'] ?? $record['time_stamp'] ?? null;
    $batteryPercentage = $record['BATTERY_PERCENTAGE'] ?? $record['battery_percentage'] ?? null;
    $gpsAccuracy = $record['GPS_ACCURACY'] ?? $record['gps_accuracy'] ?? null;
    $timeMinutes = $record['TIME_MINUTES'] ?? $record['time_minutes'] ?? null;
    
    // Validate required field
    if (!$agentId) {
        $errors[] = "Record $index: Missing AGENT_ID";
        continue;
    }
    
    // Parse the provided TIME_STAMP or use current
    $providedTimestamp = $timeStamp;
    
    if ($providedTimestamp) {
        $timestampParsed = strtotime($providedTimestamp);
        if ($timestampParsed === false) {
            $timestampForRecord = $currentTimestamp;
            $timestampUnix = $currentUnixTimestamp;
            $timestampDate = $currentDate;
            $timestampTime = $currentTime;
            $timestampYear = date('Y');
            $timestampMonth = date('m');
            $timestampDay = date('d');
            $timestampHour = date('H');
            $timestampMinute = date('i');
            $timestampSecond = date('s');
        } else {
            $timestampForRecord = $providedTimestamp;
            $timestampUnix = $timestampParsed;
            $timestampDate = date('Y-m-d', $timestampParsed);
            $timestampTime = date('H:i:s', $timestampParsed);
            $timestampYear = date('Y', $timestampParsed);
            $timestampMonth = date('m', $timestampParsed);
            $timestampDay = date('d', $timestampParsed);
            $timestampHour = date('H', $timestampParsed);
            $timestampMinute = date('i', $timestampParsed);
            $timestampSecond = date('s', $timestampParsed);
        }
    } else {
        $timestampForRecord = $currentTimestamp;
        $timestampUnix = $currentUnixTimestamp;
        $timestampDate = $currentDate;
        $timestampTime = $currentTime;
        $timestampYear = date('Y');
        $timestampMonth = date('m');
        $timestampDay = date('d');
        $timestampHour = date('H');
        $timestampMinute = date('i');
        $timestampSecond = date('s');
    }
    
    // Prepare complete record with ALL table fields
    $savedRecord = [
        'LINE_ID' => $lineId,
        'COMPANY_ID' => $companyId,
        'SITE_ID' => $siteId,
        'AGENT_ID' => $agentId,
        'VEHICLE_ID' => $vehicleId,
        'DELIVERY_DATE' => $deliveryDate ?? $timestampDate,
        'LAT_CAPTURED' => $latCaptured !== null ? (float)$latCaptured : null,
        'LONG_CAPTURED' => $longCaptured !== null ? (float)$longCaptured : null,
        'TIME_STAMP' => $timestampForRecord,
        'BATTERY_PERCENTAGE' => $batteryPercentage !== null ? (float)$batteryPercentage : null,
        'GPS_ACCURACY' => $gpsAccuracy !== null ? (float)$gpsAccuracy : null,
        'TIME_MINUTES' => $timeMinutes !== null ? (float)$timeMinutes : null,
        
        'timestamp_details' => [
            'original' => $providedTimestamp ?? $currentTimestamp,
            'formatted' => $timestampForRecord,
            'unix' => $timestampUnix,
            'date' => $timestampDate,
            'time' => $timestampTime,
            'year' => (int)$timestampYear,
            'month' => (int)$timestampMonth,
            'day' => (int)$timestampDay,
            'hour' => (int)$timestampHour,
            'minute' => (int)$timestampMinute,
            'second' => (int)$timestampSecond,
            'iso_8601' => date('c', $timestampUnix),
            'mysql_datetime' => $timestampForRecord,
            'timezone' => date_default_timezone_get(),
            'weekday' => date('l', $timestampUnix),
            'week_number' => (int)date('W', $timestampUnix),
            'day_of_year' => (int)date('z', $timestampUnix),
            'is_weekend' => (date('N', $timestampUnix) >= 6),
            'quarter' => (int)ceil(date('n', $timestampUnix) / 3)
        ]
    ];
    
    $savedRecords[] = $savedRecord;
}

// Check if any valid records
if (empty($savedRecords)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No valid records to save', 'errors' => $errors]);
    exit();
}

// Prepare final data
$output = [
    'metadata' => [
        'file_info' => [
            'created_at' => $currentTimestamp,
            'created_at_unix' => $currentUnixTimestamp,
            'created_at_microtime' => $currentMicrotime,
            'created_at_date' => $currentDate,
            'created_at_time' => $currentTime,
            'timezone' => date_default_timezone_get(),
            'source_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ],
        'sync_info' => [
            'sync_id' => $syncId,
            'total_records' => count($savedRecords),
            'valid_records' => count($savedRecords),
            'invalid_records' => count($errors),
            'errors' => $errors,
            'last_sync_date' => $currentTimestamp,
            'update_count' => $existingFile ? ($existingFile ? 1 : 0) : 0
        ],
        'table_schema' => 'dbo.Dash_Agent_Time_Stamp',
        'fields_included' => [
            'LINE_ID', 'COMPANY_ID', 'SITE_ID', 'AGENT_ID', 'VEHICLE_ID',
            'DELIVERY_DATE', 'LAT_CAPTURED', 'LONG_CAPTURED', 'TIME_STAMP',
            'BATTERY_PERCENTAGE', 'GPS_ACCURACY', 'TIME_MINUTES'
        ]
    ],
    'records' => $savedRecords
];

// Save or Update file
if ($existingFile) {
    // UPDATE existing file
    if (file_put_contents($existingFile, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'action' => 'updated',
            'message' => count($savedRecords) . ' record(s) UPDATED in existing file',
            'file' => basename($existingFile),
            'file_path' => $existingFile,
            'sync_id' => $syncId,
            'total_records' => count($savedRecords),
            'saved_at' => $currentTimestamp,
            'update_count' => 1
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update file']);
    }
} else {
    // CREATE new file
    $firstAgent = preg_replace('/[^a-zA-Z0-9_-]/', '_', $savedRecords[0]['AGENT_ID']);
    $fileTimestamp = date('Ymd_His');
    $filename = 'AgentTimeStamp_' . $firstAgent . '_' . $fileTimestamp . '.json';
    $filePath = LOG_DIR . '/' . $filename;
    
    $output['metadata']['file_info']['filename'] = $filename;
    
    if (file_put_contents($filePath, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'action' => 'created',
            'message' => count($savedRecords) . ' record(s) saved to NEW file',
            'file' => $filename,
            'file_path' => $filePath,
            'sync_id' => $syncId,
            'total_records' => count($savedRecords),
            'saved_at' => $currentTimestamp
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create file']);
    }
}
?>