<?php
session_start();
include __DIR__ . '/../../DB/dbcon.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header for all responses
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============================================
// GET FOR LOAD DEVICES WITH IR STATUS
// ============================================
if ($action === 'forload') {
    try {
        $sql = "
            SELECT 
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
                LOAD_STATUS,
                YEARS_USING,
                REMARKS,
                DATE_ADDED,
                LOAD_TERMS,
                STATUS,
                CONSUMED,
                IS_COMPLIED,
                IR_COUNT,
                DATE_COMPLIED,
                DATA_BALANCE_MIN,
                IT_RECOMMENDATION,
                CHARGED_TO,
                DEVICE_STATUS,
                DATE_SURRENDERED,
                DAYS_TO_REPAIR,
                TEMPORARY_DEVICE,
                REASON_CODE,
                DEVICE_IR_COMPLIED,
                ATTACHMENT1,
                ATTACHMENT2,
                PERSON_IMAGE,
                CASE
                    WHEN BALANCE < COALESCE(DATA_BALANCE_MIN, 0) THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY IS NULL THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE()) THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE()) THEN 'FOR LOAD'
                    ELSE 'OK'
                END AS EFFECTIVE_LOAD_STATUS,
                CASE 
                    WHEN LAST_LOAD_HISTORY IS NOT NULL AND LOAD_TERMS IS NOT NULL 
                    THEN DATEADD(MONTH, CAST(LOAD_TERMS AS INT), CAST(LAST_LOAD_HISTORY AS DATE))
                    ELSE NULL 
                END AS NEXT_LOAD_SCHEDULE
            FROM BS_Device
            WHERE STATUS != 'INACTIVE'
            ORDER BY SITE_ID, DEPARTMENT, PERSON_USING
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = array_values(array_filter($results, function ($device) {
            return ($device['EFFECTIVE_LOAD_STATUS'] ?? 'OK') === 'FOR LOAD';
        }));

        foreach ($results as &$device) {
            $device['LOAD_STATUS'] = $device['EFFECTIVE_LOAD_STATUS'] ?? $device['LOAD_STATUS'];
        }
        unset($device);

        echo json_encode($results);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// ============================================
// SET LOAD IR COMPLIED (updates IS_COMPLIED column)
// ============================================
else if ($action === 'setloadircomplied') {
    $lineid = $_GET['lineid'] ?? null;

    if (!$lineid) {
        echo json_encode(['success' => false, 'message' => 'LINEID is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Check if device exists
        $checkSql = "SELECT LINEID, PERSON_USING, NUMBER, BALANCE, COMPANY_ID, SITE_ID FROM BS_Device WHERE LINEID = :LINEID";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([':LINEID' => $lineid]);
        $device = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            echo json_encode(['success' => false, 'message' => 'Device not found']);
            exit;
        }

        // Update IS_COMPLIED column for Load IR
        $updateSql = "
            UPDATE BS_Device 
            SET 
                IS_COMPLIED = 'YES',
                DATE_COMPLIED = GETDATE(),
                REMARKS = CASE 
                    WHEN REMARKS IS NULL OR REMARKS = '' THEN 'Load IR Complied'
                    WHEN REMARKS NOT LIKE '%Load IR Complied%' THEN REMARKS + ' | Load IR Complied'
                    ELSE REMARKS
                END
            WHERE LINEID = :LINEID
        ";

        $stmt = $conn->prepare($updateSql);
        $result = $stmt->execute([':LINEID' => $lineid]);
        
        if (!$result) {
            throw new Exception('Failed to update device');
        }

        // Log the Load IR compliance
        $logSql = "
            INSERT INTO BS_Checking_logs (
                COMPANY_ID,
                SITE_ID,
                DATE_CHECKED,
                [USER],
                NUMBER,
                LOAD_BALANCE,
                IS_SUBMIT,
                IS_PHYSICAL_OK,
                HAS_GAMES,
                IS_SYSTEM_UPDATED,
                OTHER_ISSUES,
                CHECKED_BY,
                ACTION_TYPE,
                ACTION_DETAILS
            )
            VALUES (
                :COMPANY_ID,
                :SITE_ID,
                GETDATE(),
                :USER,
                :NUMBER,
                :LOAD_BALANCE,
                'Yes',
                'Yes',
                'No',
                'Yes',
                'Load IR Compliance',
                :CHECKED_BY,
                'LOAD_IR_COMPLIED',
                'Device marked as Load IR Complied'
            )
        ";

        $stmtLog = $conn->prepare($logSql);
        $stmtLog->execute([
            ':COMPANY_ID' => $device['COMPANY_ID'] ?? null,
            ':SITE_ID' => $device['SITE_ID'] ?? null,
            ':USER' => $device['PERSON_USING'] ?? null,
            ':NUMBER' => $device['NUMBER'] ?? null,
            ':LOAD_BALANCE' => $device['BALANCE'] ?? null,
            ':CHECKED_BY' => $_SESSION['Name_of_user'] ?? 'SYSTEM'
        ]);

        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Device marked as Load IR complied successfully']);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ============================================
// SET DEVICE IR COMPLIED (updates DEVICE_IR_COMPLIED column)
// ============================================
else if ($action === 'setdeviceircomplied') {
    $lineid = $_GET['lineid'] ?? null;

    if (!$lineid) {
        echo json_encode(['success' => false, 'message' => 'LINEID is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Check if device exists
        $checkSql = "SELECT LINEID, PERSON_USING, NUMBER, BALANCE, COMPANY_ID, SITE_ID FROM BS_Device WHERE LINEID = :LINEID";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([':LINEID' => $lineid]);
        $device = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            echo json_encode(['success' => false, 'message' => 'Device not found']);
            exit;
        }

        // Update DEVICE_IR_COMPLIED column
        $updateSql = "
            UPDATE BS_Device 
            SET 
                DEVICE_IR_COMPLIED = 'YES',
                DATE_COMPLIED = GETDATE(),
                REMARKS = CASE 
                    WHEN REMARKS IS NULL OR REMARKS = '' THEN 'Device IR Complied'
                    WHEN REMARKS NOT LIKE '%Device IR Complied%' THEN REMARKS + ' | Device IR Complied'
                    ELSE REMARKS
                END
            WHERE LINEID = :LINEID
        ";

        $stmt = $conn->prepare($updateSql);
        $result = $stmt->execute([':LINEID' => $lineid]);
        
        if (!$result) {
            throw new Exception('Failed to update device');
        }

        // Log the Device IR compliance
        $logSql = "
            INSERT INTO BS_Checking_logs (
                COMPANY_ID,
                SITE_ID,
                DATE_CHECKED,
                [USER],
                NUMBER,
                LOAD_BALANCE,
                IS_SUBMIT,
                IS_PHYSICAL_OK,
                HAS_GAMES,
                IS_SYSTEM_UPDATED,
                OTHER_ISSUES,
                CHECKED_BY,
                ACTION_TYPE,
                ACTION_DETAILS
            )
            VALUES (
                :COMPANY_ID,
                :SITE_ID,
                GETDATE(),
                :USER,
                :NUMBER,
                :LOAD_BALANCE,
                'Yes',
                'Yes',
                'No',
                'Yes',
                'Device IR Compliance',
                :CHECKED_BY,
                'DEVICE_IR_COMPLIED',
                'Device marked as Device IR Complied (DEVICE_IR_COMPLIED = YES)'
            )
        ";

        $stmtLog = $conn->prepare($logSql);
        $stmtLog->execute([
            ':COMPANY_ID' => $device['COMPANY_ID'] ?? null,
            ':SITE_ID' => $device['SITE_ID'] ?? null,
            ':USER' => $device['PERSON_USING'] ?? null,
            ':NUMBER' => $device['NUMBER'] ?? null,
            ':LOAD_BALANCE' => $device['BALANCE'] ?? null,
            ':CHECKED_BY' => $_SESSION['Name_of_user'] ?? 'SYSTEM'
        ]);

        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Device marked as Device IR complied successfully']);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ============================================
// UPDATE LOAD STATUS FOR ALL DEVICES
// ============================================
else if ($action === 'update_load_status_all') {
    try {
        $conn->beginTransaction();

        $updateSql = "
            UPDATE BS_Device
            SET LOAD_STATUS = CASE
                WHEN BALANCE < COALESCE(DATA_BALANCE_MIN, 0)
                     OR LAST_LOAD_HISTORY IS NULL
                     OR LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE())
                     OR LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE())
                THEN 'FOR LOAD'
                ELSE 'OK'
            END
            WHERE STATUS != 'INACTIVE'
        ";

        $stmt = $conn->prepare($updateSql);
        $stmt->execute();
        $updatedCount = $stmt->rowCount();

        $conn->commit();

        echo json_encode([
            'success' => true, 
            'updated' => $updatedCount,
            'message' => "Updated $updatedCount devices"
        ]);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET IR SUMMARY (Dashboard stats)
// ============================================
else if ($action === 'getirsummary') {
    try {
        $sql = "
            SELECT 
                COUNT(*) AS TOTAL_DEVICES,
                SUM(CASE 
                    WHEN (
                        BALANCE < COALESCE(DATA_BALANCE_MIN, 0)
                        OR LAST_LOAD_HISTORY IS NULL
                        OR LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE())
                        OR LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE())
                    )
                    AND (IS_COMPLIED != 'YES' OR IS_COMPLIED IS NULL)
                    THEN 1 ELSE 0 
                END) AS PENDING_LOAD_IR,
                SUM(CASE 
                    WHEN (
                        BALANCE < COALESCE(DATA_BALANCE_MIN, 0)
                        OR LAST_LOAD_HISTORY IS NULL
                        OR LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE())
                        OR LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE())
                    )
                    AND IS_COMPLIED = 'YES'
                    THEN 1 ELSE 0 
                END) AS COMPLIED_LOAD_IR,
                SUM(CASE 
                    WHEN DEVICE_STATUS IN ('DEFECTIVE', 'DAMAGED', 'REPAIR', 'BROKEN', 'FAULTY', 'FOR REPAIR', 'NOT WORKING', 'BAD')
                        AND (DEVICE_IR_COMPLIED != 'YES' OR DEVICE_IR_COMPLIED IS NULL)
                    THEN 1 ELSE 0 
                END) AS PENDING_DEVICE_IR,
                SUM(CASE 
                    WHEN DEVICE_STATUS IN ('DEFECTIVE', 'DAMAGED', 'REPAIR', 'BROKEN', 'FAULTY', 'FOR REPAIR', 'NOT WORKING', 'BAD')
                        AND DEVICE_IR_COMPLIED = 'YES'
                    THEN 1 ELSE 0 
                END) AS COMPLIED_DEVICE_IR
            FROM BS_Device
            WHERE STATUS = 'ACTIVE'
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// TEST CONNECTION - For debugging
// ============================================
else if ($action === 'test') {
    try {
        $testSql = "SELECT GETDATE() AS server_time";
        $stmt = $conn->prepare($testSql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Connection successful',
            'server_time' => $result['server_time']
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage()
        ]);
        exit;
    }
}

// ============================================
// DEFAULT - Invalid action
// ============================================
else {
    echo json_encode([
        'error' => 'Invalid action',
        'available_actions' => [
            'forload',
            'setloadircomplied',
            'setdeviceircomplied',
            'update_load_status_all',
            'getirsummary',
            'test'
        ]
    ]);
    exit;
}
?>