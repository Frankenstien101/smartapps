<?php

session_start();
include __DIR__ . '/../../DB/dbcon.php';

// Get all available devices for deployment
if (isset($_GET['action']) && $_GET['action'] === 'getavailable') {
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
                    BRAND,
                    MODEL,
                    IMEI,
                    SERIAL,
                    DATE_DEPLOYED,
                    LOAD_STATUS,
                    ISNULL(STATUS, 'IN USE') AS STATUS

                FROM dbo.BS_Device

                WHERE COMPANY_ID = :companyid AND ISNULL(STATUS, 'IN USE') = 'AVAILABLE'
                ORDER BY SITE_ID ASC";

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

// Process device deployment (update status to IN USE and set user details)
if (isset($_GET['action']) && $_GET['action'] === 'deploy') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    try {
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        $companyId = $data['company'] ?? '';
        $lineId = $data['lineId'] ?? '';
        $userName = $data['userName'] ?? '';
        $department = $data['department'] ?? '';
        $position = $data['position'] ?? '';
        $phoneNumber = $data['phoneNumber'] ?? '';
        $remarks = trim($data['remarks'] ?? '');

        if (empty($lineId) || empty($companyId)) {
            echo json_encode(['success' => false, 'message' => 'Missing required data']);
            exit();
        }

        // Update device with deployment details
        $sql = "UPDATE dbo.BS_Device 
                SET STATUS = 'IN USE',
                    PERSON_USING = ?,
                    DEPARTMENT = ?,
                    POSITION = ?,
                    NUMBER = ?,
                    LOAD_STATUS = 'IN USE'
                WHERE LINEID = ? 
                  AND COMPANY_ID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $userName,
            $department,
            $position,
            $phoneNumber,
            $lineId,
            $companyId
        ]);

        $affectedRows = $stmt->rowCount();

        if ($affectedRows > 0) {
            // Log the deployment transaction
            logDeploymentTransaction($conn, $lineId, $companyId, $userName, $department, $position, $phoneNumber, $remarks);
        }

        echo json_encode([
            'success' => true,
            'message' => "Device deployed successfully to $userName",
            'count' => $affectedRows
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Application error: ' . $e->getMessage()]);
    }
    exit();
}

// Helper function to log deployment to BS_Audit_Logs
function logDeploymentTransaction($conn, $lineId, $companyId, $userName, $department, $position, $phoneNumber, $remarks = '') {
    try {
        $timestamp = date('Y-m-d H:i:s');
        $processBy = $_SESSION['Name_of_user'] ?? $_SESSION['Username'] ?? $_SESSION['user_id'] ?? 'System';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $auditSql = "INSERT INTO dbo.BS_Audit_Logs 
                    (LINEID, ChangedBy, ChangedAt, ActionType, FieldName, OldValue, NewValue, Remarks, IpAddress)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $auditStmt = $conn->prepare($auditSql);

        // Log the main deployment action (status change)
        $deploymentRemarks = "Device deployed to $userName | Dept: $department | Position: $position | Phone: $phoneNumber";
        if ($remarks !== '') {
            $deploymentRemarks .= " | Notes: " . $remarks;
        }

        try {
            $auditStmt->execute([
                $lineId,
                $processBy,
                $timestamp,
                'DEVICE_DEPLOYMENT',
                'STATUS',
                'AVAILABLE',
                'IN USE',
                $deploymentRemarks,
                $ipAddress
            ]);
        } catch (Exception $e) {
            error_log("Failed to log deployment audit for device " . $lineId . ": " . $e->getMessage());
        }

    } catch (Exception $e) {
        error_log("Failed to log deployment transaction: " . $e->getMessage());
    }
}

?>
