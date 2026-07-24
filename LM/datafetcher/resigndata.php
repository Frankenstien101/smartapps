<?php

session_start();
include __DIR__ . '/../../DB/dbcon.php';

// Get all devices for resignation
if (isset($_GET['action']) && $_GET['action'] === 'getdevices') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = trim($_GET['company'] ?? '');

        if (empty($companyId)) {
            echo json_encode(['error' => 'Company ID is required']);
            exit();
        }

        $sql = "SELECT
                    LINEID,
                    COMPANY_ID,
                    SITE_ID,
                    DEPARTMENT,
                    PRINCIPAL,
                    POSITION,
                    BRAND,
                    MODEL,
                    IMEI,
                    SERIAL,
                    DATE_DEPLOYED,
                    PERSON_USING,
                    NUMBER,
                    BALANCE,
                    LAST_LOAD_HISTORY,
                    DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY) AS NEXT_LOAD_SCHEDULE,
                    DATEDIFF(
                        DAY,
                        CAST(GETDATE() AS DATE),
                        DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)
                    ) AS DAYS_LEFT,
                    LOAD_STATUS,
                    ISNULL(STATUS, 'IN USE') AS STATUS

                FROM dbo.BS_Device

                WHERE COMPANY_ID = :companyid
                ORDER BY SITE_ID ASC, PERSON_USING ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId, PDO::PARAM_STR);
        $stmt->execute();

        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($devices ?: []);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Application error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Process device resignation (update status to AVAILABLE)
// Accepts either a single 'lineId' or an array 'lineIds'. Also accepts optional 'remarks'.
if (isset($_GET['action']) && $_GET['action'] === 'resign') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    try {
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        $companyId = $data['company'] ?? '';
        $remarks = trim($data['remarks'] ?? '');

        // Support single or multiple line ids
        if (isset($data['lineId']) && $data['lineId'] !== '') {
            $lineIds = [ $data['lineId'] ];
        } else {
            $lineIds = $data['lineIds'] ?? [];
        }

        if (empty($lineIds) || !is_array($lineIds)) {
            echo json_encode(['success' => false, 'message' => 'No devices provided']);
            exit();
        }

        // Prepare the IN clause for multiple line IDs
        $placeholders = implode(',', array_fill(0, count($lineIds), '?'));
        
        $sql = "UPDATE dbo.BS_Device 
                SET STATUS = 'AVAILABLE',
                    PERSON_USING = NULL,
                    POSITION = NULL,
                    PRINCIPAL = NULL,
                    DEPARTMENT = NULL
                WHERE LINEID IN ($placeholders) 
                  AND COMPANY_ID = ?";

        $stmt = $conn->prepare($sql);
        
        // Bind values
        foreach ($lineIds as $key => $lineId) {
            // bind as string/int depending on value
            $stmt->bindValue($key + 1, $lineId, is_numeric($lineId) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(count($lineIds) + 1, $companyId, PDO::PARAM_STR);

        $stmt->execute();
        $affectedRows = $stmt->rowCount();

        // Log the resignation transaction (pass remarks)
        logResignationTransaction($conn, $lineIds, $companyId, $remarks);
        
        // Log to audit log
        logToAuditLog($conn, $lineIds, $companyId, $remarks);

        echo json_encode([
            'success' => true,
            'message' => "$affectedRows device(s) have been resigned successfully",
            'count' => $affectedRows
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Application error: ' . $e->getMessage()]);
    }
    exit();
}

// Helper function to log resignation transaction
function logResignationTransaction($conn, $lineIds, $companyId, $remarks = '') {
    try {
        $timestamp = date('Y-m-d H:i:s');
        $processBy = $_SESSION['Name_of_user'] ?? $_SESSION['Username'] ?? $_SESSION['user_id'] ?? 'System';

        // Fetch device details for the provided line IDs and company
        $placeholders = implode(',', array_fill(0, count($lineIds), '?'));
        $selectSql = "SELECT LINEID, COMPANY_ID, SITE_ID, IMEI, SERIAL, BRAND, MODEL, 
                             PERSON_USING, LOAD_STATUS
                      FROM dbo.BS_Device
                      WHERE LINEID IN ($placeholders) AND COMPANY_ID = ?";

        $selectStmt = $conn->prepare($selectSql);
        $i = 1;
        foreach ($lineIds as $lineId) {
            $selectStmt->bindValue($i++, $lineId, is_numeric($lineId) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $selectStmt->bindValue($i, $companyId, PDO::PARAM_STR);
        $selectStmt->execute();
        $devices = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

        // Map fetched devices by LINEID for quick lookup
        $deviceMap = [];
        foreach ($devices as $device) {
            $deviceMap[(string)$device['LINEID']] = $device;
        }

        // Insert each device (or a minimal stub) into BS_Resigned log table
        $logSql = "INSERT INTO dbo.BS_Resigned 
                   (LINEID, COMPANY_ID, SITE_ID, IMEI, SERIAL, BRAND, MODEL, 
                    NAME_OF_USER, LOAD_STATUS, DEVICE_STATUS, REMARKS, DATE_RESIGNED, 
                    PROCESS_BY, DATE_PROCESSED)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $logStmt = $conn->prepare($logSql);

        foreach ($lineIds as $lineId) {
            $device = $deviceMap[(string)$lineId] ?? null;
            $line = $lineId;
            $company = $companyId;
            $site = $device['SITE_ID'] ?? null;
            $imei = $device['IMEI'] ?? null;
            $serial = $device['SERIAL'] ?? null;
            $brand = $device['BRAND'] ?? null;
            $model = $device['MODEL'] ?? null;
            $nameOfUser = $device['NAME_OF_USER'] ?? null;
            $loadStatus = $device['LOAD_STATUS'] ?? null;

            try {
                $logStmt->execute([
                    $line,
                    $company,
                    $site,
                    $imei,
                    $serial,
                    $brand,
                    $model,
                    $nameOfUser,
                    $loadStatus,
                    'RESIGNED',
                    ($remarks !== '' ? $remarks : 'Device resigned and status changed to AVAILABLE'),
                    $timestamp,
                    $processBy,
                    $timestamp
                ]);
            } catch (Exception $e) {
                // Log error but continue with next
                error_log("Failed to log resignation for device " . $line . ": " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        // Silently continue if logging fails
        error_log("Failed to log resignation transaction: " . $e->getMessage());
    }
}

// Helper function to log to BS_Audit_Logs
function logToAuditLog($conn, $lineIds, $companyId, $remarks = '') {
    try {
        $timestamp = date('Y-m-d H:i:s');
        $processBy = $_SESSION['Name_of_user'] ?? $_SESSION['Username'] ?? $_SESSION['user_id'] ?? 'System';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $auditSql = "INSERT INTO dbo.BS_Audit_Logs 
                    (LINEID, ChangedBy, ChangedAt, ActionType, FieldName, OldValue, NewValue, Remarks, IpAddress)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $auditStmt = $conn->prepare($auditSql);

        foreach ($lineIds as $lineId) {
            try {
                $auditStmt->execute([
                    $lineId,
                    $processBy,
                    $timestamp,
                    'DEVICE_RESIGNATION',
                    'STATUS',
                    'IN USE',
                    'AVAILABLE',
                    ($remarks !== '' ? $remarks : 'Device resigned'),
                    $ipAddress
                ]);
            } catch (Exception $e) {
                // Log error but continue
                error_log("Failed to log to audit log for device " . $lineId . ": " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        // Silently continue if logging fails
        error_log("Failed to insert audit log entry: " . $e->getMessage());
    }
}

?>
