<?php
session_start();
include __DIR__ . '/../../DB/dbcon.php';

$action = $_GET['action'] ?? '';

if ($action === 'loadrequest') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
        exit;
    }

    $lineid       = $input['device_id'] ?? null;
    $personUsing  = $input['person_using'] ?? null;
    $number       = $input['number'] ?? null;
    $balance      = $input['balance'] ?? null;
    $lastLoad     = $input['last_load'] ?? null;
    $siteId       = $input['SITE_ID'] ?? null; 

    if (!$lineid) {
        echo json_encode(['status' => 'error', 'message' => 'Device ID (LINEID) is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        $insertSql = "
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
                CHECKED_BY
            )
            VALUES (
                :COMPANY_ID,
                :SITE_ID,
                GETDATE(),
                :USER,
                :NUMBER,
                :LOAD_BALANCE,
                :IS_SUBMIT,
                :IS_PHYSICAL_OK,
                :HAS_GAMES,
                :IS_SYSTEM_UPDATED,
                :OTHER_ISSUES,
                :CHECKED_BY
            )
        ";

        $stmtLog = $conn->prepare($insertSql);
        $stmtLog->execute([
            ':COMPANY_ID'        => $_SESSION['Company_ID'] ?? null,
            ':SITE_ID'           => $siteId,
            ':USER'              => $personUsing,
            ':NUMBER'            => $number,
            ':LOAD_BALANCE'      => $balance,
            ':IS_SUBMIT'         => 'Yes',      
            ':IS_PHYSICAL_OK'    => 'Yes',      
            ':HAS_GAMES'         => 'No',       
            ':IS_SYSTEM_UPDATED' => 'Yes',      
            ':OTHER_ISSUES'      => null,
            ':CHECKED_BY'        => $_SESSION['Name_of_user'] ?? 'SYSTEM'
        ]);

        $conn->commit();

        echo json_encode(['status' => 'success', 'load_status' => 'FOR LOAD']);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// NEW: Get FOR LOAD devices with IR status
else if ($action === 'forload') {
    header('Content-Type: application/json');

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
                    WHEN BALANCE < COALESCE(DATA_BALANCE_MIN, 5) THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY IS NULL THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE()) THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE()) THEN 'FOR LOAD'
                    ELSE 'OK'
                END AS EFFECTIVE_LOAD_STATUS,
                CASE 
                    WHEN LAST_LOAD_HISTORY IS NOT NULL AND LOAD_TERMS IS NOT NULL 
                    THEN DATEADD(MONTH, CAST(COALESCE(LOAD_TERMS, 0) AS INT), CAST(LAST_LOAD_HISTORY AS DATE))
                    ELSE NULL 
                END AS NEXT_LOAD_SCHEDULE
            FROM BS_Device
            WHERE STATUS = 'ACTIVE'
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

// NEW: Mark device as COMPLIED (for IR)
else if ($action === 'setascomplied') {
    header('Content-Type: application/json');

    $lineid = $_GET['lineid'] ?? null;

    if (!$lineid) {
        echo json_encode(['success' => false, 'message' => 'LINEID is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Update IS_COMPLIED and DEVICE_IR_COMPLIED
        $updateSql = "
            UPDATE BS_Device 
            SET 
                IS_COMPLIED = 'YES',
                DEVICE_IR_COMPLIED = 'YES',
                DATE_COMPLIED = GETDATE(),
                REMARKS = CASE 
                    WHEN REMARKS IS NULL OR REMARKS = '' THEN 'IR Complied'
                    WHEN REMARKS NOT LIKE '%IR Complied%' THEN REMARKS + ' | IR Complied'
                    ELSE REMARKS
                END
            WHERE LINEID = :LINEID
        ";

        $stmt = $conn->prepare($updateSql);
        $stmt->execute([':LINEID' => $lineid]);

        // Log the compliance action
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
            SELECT 
                COMPANY_ID,
                SITE_ID,
                GETDATE(),
                PERSON_USING,
                NUMBER,
                BALANCE,
                'Yes',
                'Yes',
                'No',
                'Yes',
                'IR Compliance',
                :CHECKED_BY,
                'IR_COMPLIED',
                'Device marked as IR Complied'
            FROM BS_Device
            WHERE LINEID = :LINEID
        ";

        $stmtLog = $conn->prepare($logSql);
        $stmtLog->execute([
            ':LINEID' => $lineid,
            ':CHECKED_BY' => $_SESSION['Name_of_user'] ?? 'SYSTEM'
        ]);

        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Device marked as complied successfully']);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// NEW: Update LOAD_STATUS for all devices
else if ($action === 'update_load_status_all') {
    header('Content-Type: application/json');

    try {
        $conn->beginTransaction();

        // Update LOAD_STATUS based on balance and last load date
        $updateSql = "
            UPDATE BS_Device
            SET LOAD_STATUS = CASE
                -- FOR LOAD if balance < 5 OR last load is more than 30 days ago
                WHEN BALANCE < 5 
                     OR LAST_LOAD_HISTORY IS NULL 
                     OR DATEDIFF(DAY, LAST_LOAD_HISTORY, GETDATE()) > 30
                THEN 'FOR LOAD'
                -- OK if balance >= 5 and last load is within 30 days
                WHEN BALANCE >= 5 
                     AND LAST_LOAD_HISTORY IS NOT NULL 
                     AND DATEDIFF(DAY, LAST_LOAD_HISTORY, GETDATE()) <= 30
                THEN 'OK'
                ELSE LOAD_STATUS
            END
            WHERE STATUS = 'ACTIVE'
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// NEW: Get IR Status for a specific device
else if ($action === 'getirstatus') {
    header('Content-Type: application/json');

    $lineid = $_GET['lineid'] ?? null;

    if (!$lineid) {
        echo json_encode(['success' => false, 'message' => 'LINEID is required']);
        exit;
    }

    try {
        $sql = "
            SELECT 
                LINEID,
                BALANCE,
                LAST_LOAD_HISTORY,
                LOAD_TERMS,
                IS_COMPLIED,
                DEVICE_IR_COMPLIED,
                DATE_COMPLIED,
                REMARKS,
                -- Calculate days since last load
                DATEDIFF(DAY, LAST_LOAD_HISTORY, GETDATE()) AS DAYS_SINCE_LAST_LOAD,
                -- Calculate days until next load
                DATEDIFF(DAY, GETDATE(), DATEADD(MONTH, CAST(LOAD_TERMS AS INT), CAST(LAST_LOAD_HISTORY AS DATE))) AS DAYS_UNTIL_NEXT_LOAD
            FROM BS_Device
            WHERE LINEID = :LINEID
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':LINEID' => $lineid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Device not found']);
            exit;
        }

        // Determine IR status
        $balance = floatval($result['BALANCE'] ?? 0);
        $daysUntilNext = intval($result['DAYS_UNTIL_NEXT_LOAD'] ?? 0);
        $isComplied = $result['IS_COMPLIED'] === 'YES' || $result['DEVICE_IR_COMPLIED'] === 'YES';

        $needsIR = $balance < 5 && $daysUntilNext > 0;
        $irStatus = $needsIR ? ($isComplied ? 'COMPLIED' : 'PENDING') : 'NONE';

        echo json_encode([
            'success' => true,
            'data' => $result,
            'ir_status' => $irStatus,
            'needs_ir' => $needsIR,
            'is_complied' => $isComplied
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// NEW: Get IR Summary (counts of pending and complied)
else if ($action === 'getirsummary') {
    header('Content-Type: application/json');

    try {
        // Get devices that need IR (balance < 5 and next load is in the future)
        $sql = "
            SELECT 
                COUNT(*) AS TOTAL_DEVICES,
                SUM(CASE 
                    WHEN BALANCE < 5 
                        AND DATEADD(MONTH, CAST(LOAD_TERMS AS INT), CAST(LAST_LOAD_HISTORY AS DATE)) > GETDATE()
                        AND (IS_COMPLIED != 'YES' OR DEVICE_IR_COMPLIED != 'YES')
                    THEN 1 ELSE 0 
                END) AS PENDING_IR,
                SUM(CASE 
                    WHEN BALANCE < 5 
                        AND DATEADD(MONTH, CAST(LOAD_TERMS AS INT), CAST(LAST_LOAD_HISTORY AS DATE)) > GETDATE()
                        AND (IS_COMPLIED = 'YES' OR DEVICE_IR_COMPLIED = 'YES')
                    THEN 1 ELSE 0 
                END) AS COMPLIED_IR
            FROM BS_Device
            WHERE STATUS = 'ACTIVE'
            AND LOAD_STATUS = 'FOR LOAD'
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid action']);
    exit;
}
?>