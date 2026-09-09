<?php
/**
 * Load Guard - Complete Device API
 * Returns ALL device details with serial number filter
 * 
 * Usage: 
 *   - Get all devices: api_device.php
 *   - Filter by serial: api_device.php?serial=ABC123
 *   - Filter by serial (partial): api_device.php?serial=ABC
 *   - Get single device: api_device.php?serial=ABC123&single=true
 */

// ============================================
// 1. DATABASE CONNECTION
// ============================================
$dbPath = __DIR__ . '/../../DB/dbcon.php';

if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection file not found'
    ]);
    exit;
}

include $dbPath;

// ============================================
// 2. CORS HEADERS
// ============================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================
// 3. CHECK CONNECTION
// ============================================
if (!isset($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// ============================================
// 4. GET PARAMETERS
// ============================================
$serial = isset($_GET['serial']) ? trim($_GET['serial']) : '';
$single = isset($_GET['single']) && $_GET['single'] === 'true';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
$offset = ($page - 1) * $limit;

// ============================================
// 5. BUILD QUERY
// ============================================
try {
    // Base query with ALL fields
    $sql = "SELECT 
        -- Device Identification
        LINEID,
        COMPANY_ID,
        SITE_ID,
        DEPARTMENT,
        PRINCIPAL,
        POSITION,
        
        -- Device Details
        BRAND,
        MODEL,
        IMEI,
        SERIAL,
        NUMBER,
        
        -- User Information
        PERSON_USING,
        PERSON_IMAGE,
        
        -- Load Information
        BALANCE,
        CONSUMED,
        LAST_LOAD_HISTORY,
        LOAD_STATUS,
        LOAD_TERMS,
        DATA_BALANCE_MIN,
        
        -- Status Information
        DEVICE_STATUS,
        REASON_CODE,
        STATUS,
        IS_COMPLIED,
        DATE_COMPLIED,
        
        -- Deployment Information
        DATE_DEPLOYED,
        DATE_SURRENDERED,
        
        -- Repair Information
        DAYS_TO_REPAIR,
        TEMPORARY_DEVICE,
        IT_RECOMMENDATION,
        
        -- Additional Information
        REMARKS,
        CHARGED_TO,
        
        CASE 
            WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
            THEN DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY) 
            ELSE NULL 
        END AS NEXT_LOAD_SCHEDULE,
        
        -- Days Left Until Next Load
        CASE 
            WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
            THEN DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) 
            ELSE NULL 
        END AS DAYS_LEFT,
        
        -- Load Status Display
        CASE 
            WHEN BALANCE < COALESCE(DATA_BALANCE_MIN, 0)
                OR LAST_LOAD_HISTORY IS NULL
                OR LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE())
                OR LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE())
            THEN 'FOR LOAD'
            WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
            AND DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) < 0 
            THEN 'OVERDUE'
            WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
            AND DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) <= 7 
            THEN 'CRITICAL'
            ELSE 'OK'
        END AS LOAD_STATUS_DISPLAY,
        
        -- Balance Status
        CASE 
            WHEN BALANCE <= 10 THEN 'CRITICAL'
            WHEN BALANCE <= 50 THEN 'LOW'
            WHEN BALANCE <= 100 THEN 'MEDIUM'
            ELSE 'GOOD'
        END AS BALANCE_STATUS,
        
        -- Days Since Last Load
        CASE 
            WHEN LAST_LOAD_HISTORY IS NOT NULL 
            THEN DATEDIFF(DAY, LAST_LOAD_HISTORY, CAST(GETDATE() AS DATE))
            ELSE NULL 
        END AS DAYS_SINCE_LAST_LOAD,
        
        -- Load Usage Percentage
        CASE 
            WHEN LOAD_TERMS IS NOT NULL AND LOAD_TERMS > 0 
            AND LAST_LOAD_HISTORY IS NOT NULL 
            THEN CAST(
                (DATEDIFF(DAY, LAST_LOAD_HISTORY, CAST(GETDATE() AS DATE)) * 100.0 / LOAD_TERMS) 
                AS DECIMAL(10,2)
            )
            ELSE NULL 
        END AS LOAD_USAGE_PERCENTAGE,
        
        -- Is Overdue Flag
        CASE 
            WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
            AND DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) < 0 
            THEN 1
            ELSE 0
        END AS IS_OVERDUE,
        
        -- Is Critical Flag
        CASE 
            WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
            AND DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) BETWEEN 0 AND 7 
            THEN 1
            ELSE 0
        END AS IS_CRITICAL

    FROM dbo.BS_Device
    WHERE 1=1";

    // Add serial filter
    if (!empty($serial)) {
        if ($single) {
            // Exact match for single device
            $sql .= " AND SERIAL = :serial";
        } else {
            // Partial match for search
            $sql .= " AND SERIAL LIKE :serial";
        }
    }

    // If single device, no pagination
    if ($single && !empty($serial)) {
        $sql .= " ORDER BY SERIAL";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':serial', $serial);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($device) {
            // Format the device data
            $device = formatDeviceData($device);
            
            echo json_encode([
                'success' => true,
                'message' => 'Device retrieved successfully',
                'data' => $device,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Device not found with serial: ' . $serial
            ]);
        }
        exit;
    }

    // For multiple devices with pagination
    // Count total
    $countSql = "SELECT COUNT(*) as total FROM dbo.BS_Device WHERE 1=1";
    if (!empty($serial)) {
        $countSql .= " AND SERIAL LIKE :serial";
    }

    $countStmt = $conn->prepare($countSql);
    if (!empty($serial)) {
        $serialParam = "%$serial%";
        $countStmt->bindParam(':serial', $serialParam);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Add pagination
    $sql .= " ORDER BY SERIAL OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $stmt = $conn->prepare($sql);
    if (!empty($serial)) {
        $serialParam = "%$serial%";
        $stmt->bindParam(':serial', $serialParam);
    }
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format each device
    $formattedDevices = array_map('formatDeviceData', $devices);

    // ============================================
    // 6. GET STATISTICS
    // ============================================
    $stats = [];

    // Total by status
    $statusStmt = $conn->query("SELECT DEVICE_STATUS, COUNT(*) as count FROM dbo.BS_Device GROUP BY DEVICE_STATUS");
    $stats['by_status'] = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // Total by load status
    $loadStatusStmt = $conn->query("SELECT LOAD_STATUS, COUNT(*) as count FROM dbo.BS_Device GROUP BY LOAD_STATUS");
    $stats['by_load_status'] = $loadStatusStmt->fetchAll(PDO::FETCH_ASSOC);

    // Total by site
    $siteStmt = $conn->query("SELECT SITE_ID, COUNT(*) as count FROM dbo.BS_Device GROUP BY SITE_ID");
    $stats['by_site'] = $siteStmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary statistics
    $summaryStmt = $conn->query("
        SELECT 
            COUNT(*) as total_devices,
            SUM(BALANCE) as total_balance,
            AVG(BALANCE) as avg_balance,
            MIN(BALANCE) as min_balance,
            MAX(BALANCE) as max_balance,
            COUNT(CASE WHEN (
                BALANCE < COALESCE(DATA_BALANCE_MIN, 0)
                OR LAST_LOAD_HISTORY IS NULL
                OR LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE())
                OR LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE())
            ) THEN 1 END) as for_load_count,
            COUNT(CASE WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
                AND DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) < 0 THEN 1 END) as overdue_count,
            COUNT(CASE WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
                AND DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) BETWEEN 0 AND 7 THEN 1 END) as critical_count
        FROM dbo.BS_Device
    ");
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['summary'] = [
        'total_devices' => (int)($summary['total_devices'] ?? 0),
        'total_balance' => (float)($summary['total_balance'] ?? 0),
        'avg_balance' => (float)($summary['avg_balance'] ?? 0),
        'min_balance' => (float)($summary['min_balance'] ?? 0),
        'max_balance' => (float)($summary['max_balance'] ?? 0),
        'for_load_count' => (int)($summary['for_load_count'] ?? 0),
        'overdue_count' => (int)($summary['overdue_count'] ?? 0),
        'critical_count' => (int)($summary['critical_count'] ?? 0)
    ];

    // ============================================
    // 7. RETURN RESPONSE
    // ============================================
    $response = [
        'success' => true,
        'message' => 'Devices retrieved successfully',
        'data' => [
            'devices' => $formattedDevices,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$totalCount,
                'total_pages' => ceil($totalCount / $limit),
                'has_next' => $page < ceil($totalCount / $limit),
                'has_prev' => $page > 1
            ],
            'filter' => [
                'serial' => $serial ?: 'No filter',
                'type' => $single ? 'exact' : 'partial'
            ],
            'statistics' => $stats,
            'timestamp' => date('Y-m-d H:i:s'),
            'total_devices' => (int)$totalCount,
            'returned_count' => count($formattedDevices)
        ]
    ];

    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// ============================================
// 8. HELPER FUNCTION - Format Device Data
// ============================================
function formatDeviceData($device) {
    if (!$device) return null;

    // Format dates
    $dateFields = [
        'DATE_DEPLOYED', 'DATE_SURRENDERED', 'DATE_COMPLIED', 
        'LAST_LOAD_HISTORY', 'NEXT_LOAD_SCHEDULE'
    ];
    foreach ($dateFields as $field) {
        if (!empty($device[$field])) {
            $device[$field] = date('Y-m-d', strtotime($device[$field]));
        }
    }

    // Format numeric values
    $numericFields = ['BALANCE', 'CONSUMED', 'LOAD_TERMS', 'DAYS_LEFT', 'DAYS_SINCE_LAST_LOAD', 'LOAD_USAGE_PERCENTAGE'];
    foreach ($numericFields as $field) {
        if (isset($device[$field])) {
            $device[$field] = $device[$field] !== null ? (float)$device[$field] : null;
        }
    }

    // Convert boolean flags
    $device['IS_OVERDUE'] = (bool)($device['IS_OVERDUE'] ?? 0);
    $device['IS_CRITICAL'] = (bool)($device['IS_CRITICAL'] ?? 0);
    $device['IS_COMPLIED'] = (bool)($device['IS_COMPLIED'] ?? 0);

    // Add formatted display values
    $device['BALANCE_DISPLAY'] = '₱' . number_format($device['BALANCE'] ?? 0, 2);
    $device['CONSUMED_DISPLAY'] = '₱' . number_format($device['CONSUMED'] ?? 0, 2);
    $device['LOAD_USAGE_DISPLAY'] = $device['LOAD_USAGE_PERCENTAGE'] !== null ? 
        number_format($device['LOAD_USAGE_PERCENTAGE'], 1) . '%' : 'N/A';

    // Add status badges
    $device['LOAD_STATUS_BADGE'] = getLoadStatusBadge($device['LOAD_STATUS_DISPLAY'] ?? 'UNKNOWN');
    $device['BALANCE_STATUS_BADGE'] = getBalanceStatusBadge($device['BALANCE_STATUS'] ?? 'UNKNOWN');

    return $device;
}

function getLoadStatusBadge($status) {
    $badges = [
        'OVERDUE' => ['class' => 'danger', 'icon' => '🔴', 'text' => 'Overdue', 'color' => '#EF5350'],
        'CRITICAL' => ['class' => 'warning', 'icon' => '🟡', 'text' => 'Critical', 'color' => '#FFA726'],
        'FOR LOAD' => ['class' => 'warning', 'icon' => '⚠️', 'text' => 'For Load', 'color' => '#FF8F00'],
        'OK' => ['class' => 'success', 'icon' => '✅', 'text' => 'OK', 'color' => '#81C784']
    ];
    return $badges[$status] ?? ['class' => 'info', 'icon' => 'ℹ️', 'text' => 'Unknown', 'color' => '#42A5F5'];
}

function getBalanceStatusBadge($status) {
    $badges = [
        'CRITICAL' => ['class' => 'danger', 'icon' => '🔴', 'text' => 'Critical Balance', 'color' => '#EF5350'],
        'LOW' => ['class' => 'warning', 'icon' => '🟡', 'text' => 'Low Balance', 'color' => '#FFA726'],
        'MEDIUM' => ['class' => 'info', 'icon' => '🔵', 'text' => 'Medium Balance', 'color' => '#42A5F5'],
        'GOOD' => ['class' => 'success', 'icon' => '✅', 'text' => 'Good Balance', 'color' => '#81C784']
    ];
    return $badges[$status] ?? ['class' => 'info', 'icon' => 'ℹ️', 'text' => 'Unknown', 'color' => '#42A5F5'];
}