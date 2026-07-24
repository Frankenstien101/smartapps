<?php

use Symfony\Component\Console\Logger\ConsoleLogger;

session_start();
include __DIR__ . '/../../DB/dbcon.php';

function logAuditChange(
    PDO $conn,
    string $lineid,
    string $actionType,
    array $changes = [],
    ?string $remarks = null
) {
    $changedBy = $_SESSION['Name_of_user'] ?? $_SESSION['username'] ?? 'SYSTEM';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO BS_Audit_Logs
        (LINEID, ChangedBy, ActionType, FieldName, OldValue, NewValue, Remarks, IpAddress)
        VALUES (:lid, :by, :act, :fld, :old, :new, :rem, :ip)
    ");

    foreach ($changes as $field => $data) {
        $stmt->execute([
            ':lid'  => $lineid,
            ':by'   => $changedBy,
            ':act'  => $actionType,
            ':fld'  => $field,
            ':old'  => $data['old'] ?? '',
            ':new'  => $data['new'] ?? '',
            ':rem'  => $remarks,
            ':ip'   => $ip
        ]);
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'loaddevice') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = $_GET['company'] ?? '';

        if (empty($companyId)) {
            echo json_encode(['error' => 'Company ID is required']);
            exit();
        }

        $compare = "cl.NUMBER = d.NUMBER";

        $sql = "
            SELECT
                d.*,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM BS_Checking_logs cl
                        WHERE {$compare}
                          AND CONVERT(date, cl.DATE_CHECKED) = CONVERT(date, GETDATE())
                    ) THEN 1 
                    ELSE 0 
                END AS submitted_today
            FROM BS_Device d
            WHERE d.COMPANY_ID = :companyid
              AND d.STATUS IN ('ACTIVE', 'IN USE')
            ORDER BY d.SITE_ID ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($items ?: []);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Application error', 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'updateonly') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $lineid = $input['id'] ?? null;

    if (!$lineid) {
        echo json_encode(['success' => false, 'message' => 'LINEID is required']);
        exit;
    }

    try {
        $stmtOld = $conn->prepare("SELECT * FROM BS_Device WHERE LINEID = :id");
        $stmtOld->execute([':id' => $lineid]);
        $old = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: [];

        $changes = [];
        $fields = ['SITE_ID','DEPARTMENT','PRINCIPAL','POSITION','BRAND','MODEL',
                   'IMEI','SERIAL','DATE_DEPLOYED','PERSON_USING','NUMBER',
                   'BALANCE','REMARKS','CONSUMED','LAST_LOAD_HISTORY',
                   'DEVICE_STATUS','REASON_CODE','DATE_SURRENDERED','DAYS_TO_REPAIR','TEMPORARY_DEVICE',
                   'IT_RECOMMENDATION','CHARGED_TO'];

        foreach ($fields as $f) {
            $oldV = trim((string)($old[$f] ?? ''));
            $newV = trim((string)($input[$f] ?? ''));
            if (is_numeric($oldV) && is_numeric($newV)) {
                if ((float)$oldV !== (float)$newV) {
                    $changes[$f] = ['old' => $oldV, 'new' => $newV];
                }
            } elseif ($oldV !== $newV) {
                $changes[$f] = ['old' => $oldV ?: null, 'new' => $newV ?: null];
            }
        }

        if (!empty($changes)) {
            logAuditChange(
                $conn,
                $lineid,
                'UPDATE_ONLY',
                $changes,
                $input['REMARKS'] ?? $input['OTHER_ISSUES'] ?? null
            );
        }

        $updateSql = "
            UPDATE BS_Device
            SET
                SITE_ID       = :SITE_ID,
                DEPARTMENT    = :DEPARTMENT,
                PRINCIPAL     = :PRINCIPAL,
                POSITION      = :POSITION,
                BRAND         = :BRAND,
                MODEL         = :MODEL,
                IMEI          = :IMEI,
                SERIAL        = :SERIAL,
                DATE_DEPLOYED = :DATE_DEPLOYED,
                PERSON_USING  = :PERSON_USING,
                NUMBER        = :NUMBER,
                BALANCE       = :BALANCE,
                REMARKS       = :REMARKS,
                CONSUMED      = :CONSUMED,
                LAST_LOAD_HISTORY = :LAST_LOAD_HISTORY,
                DEVICE_STATUS = :DEVICE_STATUS,
                REASON_CODE   = :REASON_CODE,
                DATE_SURRENDERED = :DATE_SURRENDERED,
                DAYS_TO_REPAIR = :DAYS_TO_REPAIR,
                TEMPORARY_DEVICE = :TEMPORARY_DEVICE,
                IT_RECOMMENDATION = :IT_RECOMMENDATION,
                CHARGED_TO    = :CHARGED_TO
            WHERE LINEID = :LINEID
        ";

        $stmt = $conn->prepare($updateSql);
        $stmt->execute([
            ':SITE_ID'       => $input['SITE_ID']       ?? '',
            ':DEPARTMENT'    => $input['DEPARTMENT']    ?? '',
            ':PRINCIPAL'     => $input['PRINCIPAL']     ?? '',
            ':POSITION'      => $input['POSITION']      ?? '',
            ':BRAND'         => $input['BRAND']         ?? '',
            ':MODEL'         => $input['MODEL']         ?? '',
            ':IMEI'          => $input['IMEI']          ?? '',
            ':SERIAL'        => $input['SERIAL']        ?? '',
            ':DATE_DEPLOYED' => $input['DATE_DEPLOYED'] ?? '',
            ':PERSON_USING'  => $input['PERSON_USING']  ?? '',
            ':NUMBER'        => $input['NUMBER']        ?? '',
            ':BALANCE'       => $input['BALANCE']       ?? '',
            ':CONSUMED'      => $input['CONSUMED']      ?? '',
            ':REMARKS'       => $input['REMARKS']       ?? '',
            ':LAST_LOAD_HISTORY' => $input['LAST_LOAD_HISTORY'] ?? '',
            ':DEVICE_STATUS' => $input['DEVICE_STATUS'] ?? '',
            ':REASON_CODE'   => $input['REASON_CODE']   ?? '',
            ':DATE_SURRENDERED' => $input['DATE_SURRENDERED'] ?? '',
            ':DAYS_TO_REPAIR' => $input['DAYS_TO_REPAIR'] ?? '',
            ':TEMPORARY_DEVICE' => $input['TEMPORARY_DEVICE'] ?? '',
            ':IT_RECOMMENDATION' => $input['IT_RECOMMENDATION'] ?? '',
            ':CHARGED_TO'    => $input['CHARGED_TO']    ?? '',
            ':LINEID'        => $lineid
        ]);

        echo json_encode(['success' => true]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

if ($action === 'update_device') {
    header('Content-Type: application/json');

    $companyId = $_SESSION['Company_ID'] ?? '';

    if (empty($companyId)) {
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $lineid = $input['id'] ?? null;

    if (!$lineid) {
        echo json_encode(['success' => false, 'message' => 'LINEID is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        $stmtOld = $conn->prepare("SELECT * FROM BS_Device WHERE LINEID = :id");
        $stmtOld->execute([':id' => $lineid]);
        $old = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: [];

        $changes = [];
        $fields = ['SITE_ID','DEPARTMENT','PRINCIPAL','POSITION','BRAND','MODEL',
                   'IMEI','SERIAL','DATE_DEPLOYED','PERSON_USING','NUMBER',
                   'BALANCE','REMARKS','CONSUMED','LAST_LOAD_HISTORY',
                   'DEVICE_STATUS','REASON_CODE','DATE_SURRENDERED','DAYS_TO_REPAIR','TEMPORARY_DEVICE',
                   'IT_RECOMMENDATION','CHARGED_TO'];

        foreach ($fields as $f) {
            $oldV = trim((string)($old[$f] ?? ''));
            $newV = trim((string)($input[$f] ?? ''));
            if (is_numeric($oldV) && is_numeric($newV)) {
                if ((float)$oldV !== (float)$newV) {
                    $changes[$f] = ['old' => $oldV, 'new' => $newV];
                }
            } elseif ($oldV !== $newV) {
                $changes[$f] = ['old' => $oldV ?: null, 'new' => $newV ?: null];
            }
        }

        if (!empty($changes)) {
            logAuditChange(
                $conn,
                $lineid,
                'UPDATE_DEVICE',
                $changes,
                $input['OTHER_ISSUES'] ?? $input['REMARKS'] ?? null
            );
        }

        $updateSql = "
            UPDATE BS_Device
            SET
                SITE_ID       = :SITE_ID,
                DEPARTMENT    = :DEPARTMENT,
                PRINCIPAL     = :PRINCIPAL,
                POSITION      = :POSITION,
                BRAND         = :BRAND,
                MODEL         = :MODEL,
                IMEI          = :IMEI,
                SERIAL        = :SERIAL,
                DATE_DEPLOYED = :DATE_DEPLOYED,
                PERSON_USING  = :PERSON_USING,
                NUMBER        = :NUMBER,
                BALANCE       = :BALANCE,
                REMARKS       = :REMARKS,
                CONSUMED      = :CONSUMED,   
                LAST_LOAD_HISTORY = :LAST_LOAD_HISTORY,
                DEVICE_STATUS = :DEVICE_STATUS,
                REASON_CODE   = :REASON_CODE,
                DATE_SURRENDERED = :DATE_SURRENDERED,
                DAYS_TO_REPAIR = :DAYS_TO_REPAIR,
                TEMPORARY_DEVICE = :TEMPORARY_DEVICE,
                IT_RECOMMENDATION = :IT_RECOMMENDATION,
                CHARGED_TO    = :CHARGED_TO
            WHERE LINEID = :LINEID
        ";

        $stmt = $conn->prepare($updateSql);
        $stmt->execute([
            ':SITE_ID'       => $input['SITE_ID']       ?? '',
            ':DEPARTMENT'    => $input['DEPARTMENT']    ?? '',
            ':PRINCIPAL'     => $input['PRINCIPAL']     ?? '',
            ':POSITION'      => $input['POSITION']      ?? '',
            ':BRAND'         => $input['BRAND']         ?? '',
            ':MODEL'         => $input['MODEL']         ?? '',
            ':IMEI'          => $input['IMEI']          ?? '',
            ':SERIAL'        => $input['SERIAL']        ?? '',
            ':DATE_DEPLOYED' => $input['DATE_DEPLOYED'] ?? '',
            ':PERSON_USING'  => $input['PERSON_USING']  ?? '',
            ':NUMBER'        => $input['NUMBER']        ?? '',
            ':BALANCE'       => $input['BALANCE']       ?? '',
            ':REMARKS'       => $input['REMARKS']       ?? '',
            ':CONSUMED'      => $input['CONSUMED']      ?? '',
            ':LAST_LOAD_HISTORY' => $input['LAST_LOAD_HISTORY'] ?? '',
            ':DEVICE_STATUS' => $input['DEVICE_STATUS'] ?? '',
            ':REASON_CODE'   => $input['REASON_CODE']   ?? '',
            ':DATE_SURRENDERED' => $input['DATE_SURRENDERED'] ?? '',
            ':DAYS_TO_REPAIR' => $input['DAYS_TO_REPAIR'] ?? '',
            ':TEMPORARY_DEVICE' => $input['TEMPORARY_DEVICE'] ?? '',
            ':IT_RECOMMENDATION' => $input['IT_RECOMMENDATION'] ?? '',
            ':CHARGED_TO'    => $input['CHARGED_TO']    ?? '',
            ':LINEID'        => $lineid
        ]);

        $insertSql = "
            INSERT INTO BS_Checking_logs (
                COMPANY_ID,
                SITE_ID,
                DATE_CHECKED,
                [USER],
                NUMBER,
                LOAD_BALANCE,
                DATA_USAGE,
                IS_SUBMIT,
                IS_PHYSICAL_OK,
                HAS_GAMES,
                IS_SYSTEM_UPDATED,
                OTHER_ISSUES,
                CHECKED_BY,
                REMARKS
            )
            VALUES (
                :COMPANY_ID,
                :SITE_ID,
                GETDATE(),
                :USER,
                :NUMBER,
                :LOAD_BALANCE,
                :DATA_USAGE,
                :IS_SUBMIT,
                :IS_PHYSICAL_OK,
                :HAS_GAMES,
                :IS_SYSTEM_UPDATED,
                :OTHER_ISSUES,
                :CHECKED_BY,
                :REMARKS
            )
        ";

        $stmtLog = $conn->prepare($insertSql);
        $stmtLog->execute([
            ':COMPANY_ID'        => $_SESSION['Company_ID'] ?? null,
            ':SITE_ID'           => $input['SITE_ID'] ?? null,
            ':USER'              => $input['PERSON_USING'] ?? null,
            ':NUMBER'            => $input['NUMBER'] ?? null,
            ':LOAD_BALANCE'      => $input['BALANCE'] ?? null,
            ':DATA_USAGE'        => $input['DATA_USAGE'] ?? null,
            ':IS_SUBMIT'         => $input['DATA_SUBMITTED'] ?? 'Yes',
            ':IS_PHYSICAL_OK'    => $input['PHYSICALLY_OK'] ?? 'Yes',
            ':HAS_GAMES'         => $input['GAMES'] ?? 'No',
            ':IS_SYSTEM_UPDATED' => $input['SYSTEM_UPDATED'] ?? 'Yes',
            ':OTHER_ISSUES'      => $input['OTHER_ISSUES'] ?? null,
            ':CHECKED_BY'        => $_SESSION['Name_of_user'] ?? 'SYSTEM',
            ':REMARKS'           => $input['REMARKS']
        ]);

        $conn->commit();

        echo json_encode(['success' => true]);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

if ($action === 'today_submissions') {
    header('Content-Type: application/json');

    $company = $_GET['company'] ?? '';
    $datefrom = $_GET['datefrom'] ?? date('Y-m-d');
    $dateto   = $_GET['dateto']   ?? date('Y-m-d');

    if (empty($company)) {
        echo json_encode(['error' => 'Company ID is required']);
        exit;
    }

    try {
        $sql = "
            SELECT 
                NUMBER,
                SITE_ID,
                [USER],
                DATE_CHECKED,
                IS_SUBMIT,
                LOAD_BALANCE,
                DATA_USAGE,
                OTHER_ISSUES,
                CHECKED_BY
            FROM BS_Checking_logs
            WHERE COMPANY_ID = :company
              AND CONVERT(date, DATE_CHECKED) BETWEEN :datefrom AND :dateto
            ORDER BY SITE_ID, DATE_CHECKED DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':company'    => $company,
            ':datefrom'   => $datefrom,
            ':dateto'     => $dateto
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    } catch (PDOException $e) {
        echo json_encode([
            'error'   => 'Database error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'today_submissions2') {
    header('Content-Type: application/json');

    $company = $_GET['company'] ?? '';
    $datefrom = $_GET['datefrom'] ?? date('Y-m-d');
    $dateto   = $_GET['dateto']   ?? date('Y-m-d');

    if (empty($company)) {
        echo json_encode(['error' => 'Company ID is required']);
        exit;
    }

    try {
        $sql = "
            SELECT 
                NUMBER,
                SITE_ID,
                [USER],
                DATE_CHECKED,
                IS_SUBMIT,
                LOAD_BALANCE,
                DATA_USAGE,
                OTHER_ISSUES,
                CHECKED_BY
            FROM BS_Checking_logs
            WHERE COMPANY_ID = :company
              AND CONVERT(date, DATE_CHECKED) BETWEEN :datefrom AND :dateto
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':company'    => $company,
            ':datefrom'   => $datefrom,
            ':dateto'     => $dateto
      
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    } catch (PDOException $e) {
        echo json_encode([
            'error'   => 'Database error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'totaldevices') {
    header('Content-Type: application/json');

    $company = $_GET['company'] ?? $_SESSION['Company_ID'] ?? '';
    $site = $_GET['site'] ?? null;
    if (empty($company)) {
        echo json_encode(['total' => 0]);
        exit;
    }

    try {
        if ($site) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM BS_Device WHERE COMPANY_ID = :company AND SITE_ID = :site");
            $stmt->execute([':company' => $company, ':site' => $site]);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM BS_Device WHERE COMPANY_ID = :company");
            $stmt->execute([':company' => $company]);
        }
        $total = (int) $stmt->fetchColumn();
        echo json_encode(['total' => $total]);
    } catch (PDOException $e) {
        echo json_encode(['total' => 0, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'setascomplied') {
    header('Content-Type: application/json');

    $lineid = $_GET['lineid'] ?? '';

    if (empty($lineid)) {
        
        echo json_encode(['success' => false, 'message' => 'Missing lineid']);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            UPDATE BS_Device
            SET IS_COMPLIED = 'YES' , DATE_COMPLIED = GETDATE()
            WHERE LINEID = :lid
        ");
        $stmt->execute([':lid' => $lineid]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully updated'
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

if ($action === 'loadcheckresult') {
    header('Content-Type: application/json');

    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    try {
        $companyId = $_GET['company'] ?? '';
        $datefrom  = $_GET['datefrom'] ?? '';
        $dateto    = $_GET['dateto'] ?? '';

        if (empty($companyId) || empty($datefrom) || empty($dateto)) {
            echo json_encode(['error' => 'Missing required parameters']);
            exit;
        }

        $sql = "
            SELECT *
            FROM BS_Checking_logs
            WHERE COMPANY_ID = :companyid
              AND DATE_CHECKED BETWEEN :datefrom AND :dateto
            ORDER BY SITE_ID ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId, PDO::PARAM_STR);
        $stmt->bindParam(':datefrom', $datefrom, PDO::PARAM_STR);
        $stmt->bindParam(':dateto', $dateto, PDO::PARAM_STR);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($items);

    } catch (PDOException $e) {
        echo json_encode([
            'error' => 'Database error',
            'message' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Application error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'update_balance') {
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['id']) || !isset($data['BALANCE'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $lineid = $data['id'];
    $newBalance = $data['BALANCE'];
    $newConsumed = $data['CONSUMED'] ?? 0;

    try {
        $stmtOld = $conn->prepare("SELECT BALANCE, CONSUMED FROM BS_Device WHERE LINEID = :id");
        $stmtOld->execute([':id' => $lineid]);
        $oldRow = $stmtOld->fetch(PDO::FETCH_ASSOC);

        $oldBalance = $oldRow ? $oldRow['BALANCE'] : null;
        $oldConsumed = $oldRow ? $oldRow['CONSUMED'] : null;

        $changes = [];
        if ($oldBalance !== null && (string)$oldBalance !== (string)$newBalance) {
            $changes['BALANCE'] = ['old' => $oldBalance, 'new' => $newBalance];
        }

        if (!empty($changes)) {
            logAuditChange($conn, $lineid, 'BALANCE_UPDATE', $changes);
        }

        $sql = "
           UPDATE BS_Device
            SET
                LOAD_STATUS = CASE
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE())
                        THEN 'FOR LOAD'
                    WHEN BALANCE < DATA_BALANCE_MIN 
                        THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -LOAD_TERMS, GETDATE())
                     AND BALANCE >= DATA_BALANCE_MIN
                        THEN 'OK'
                    WHEN LAST_LOAD_HISTORY >= DATEADD(MONTH, -LOAD_TERMS, GETDATE())
                     AND BALANCE >= DATA_BALANCE_MIN
                        THEN 'OK'
                    ELSE 'OK'
                END 
                , BALANCE = :BALANCE
                , CONSUMED = :CONSUMED
                WHERE LINEID = :id
              
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id'      => $lineid,
            ':BALANCE' => $newBalance,
            ':CONSUMED' => $newConsumed
        ]);

        echo json_encode(['success' => true]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

if ($action === 'update_load_status_all') {
    header('Content-Type: application/json');

    try {
        $sql = "
            UPDATE BS_Device
            SET
                LOAD_STATUS = CASE
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE())
                        THEN 'FOR LOAD'
                    WHEN BALANCE < DATA_BALANCE_MIN 
                        THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -LOAD_TERMS, GETDATE())
                     AND BALANCE >= DATA_BALANCE_MIN
                        THEN 'OK'
                    WHEN LAST_LOAD_HISTORY >= DATEADD(MONTH, -LOAD_TERMS, GETDATE())
                     AND BALANCE >= DATA_BALANCE_MIN
                        THEN 'OK'
                    ELSE 'OK'
                END
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $rowCount = $stmt->rowCount();

        echo json_encode([
            'success'   => true,
            'message'   => "Successfully refreshed LOAD_STATUS for $rowCount devices.",
            'updated'   => $rowCount
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
}

if ($action === 'get_audit') {
    header('Content-Type: application/json');
    
    $lineid = $_GET['lineid'] ?? '';
    if (empty($lineid)) {
        echo json_encode(['success' => false, 'message' => 'Missing lineid']);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT 
                AuditID,
                ChangedAt,
                ChangedBy,
                ActionType,
                FieldName,
                OldValue,
                NewValue,
                Remarks
            FROM BS_Audit_Logs
            WHERE LINEID = :lid
            ORDER BY ChangedAt DESC
        ");
        $stmt->execute([':lid' => $lineid]);
        
        echo json_encode([
            'success' => true,
            'logs'    => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'forload') {
    $company_id = $_SESSION['Company_ID'] ?? '';

    $sql = "
        SELECT 
            LINEID,
            SITE_ID,
            DEPARTMENT,
            PRINCIPAL,
            POSITION,
            BRAND,
            MODEL,
            SERIAL,
            DATE_DEPLOYED,
            PERSON_USING,
            NUMBER,
            BALANCE,
            LOAD_STATUS,
            LAST_LOAD_HISTORY,
            DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY) AS NEXT_LOAD_SCHEDULE,
            LOAD_TERMS,
            IS_COMPLIED,
            DATA_BALANCE_MIN,
            DEVICE_STATUS,
            REASON_CODE,
            DATE_SURRENDERED,
            DAYS_TO_REPAIR,
            TEMPORARY_DEVICE,
            IT_RECOMMENDATION,
            CHARGED_TO
        FROM BS_Device
        WHERE COMPANY_ID = :company_id
          AND LOAD_STATUS = 'FOR LOAD'
          AND STATUS IN ('ACTIVE', 'IN USE')
        ORDER BY DATE_ADDED DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':company_id' => $company_id]);

    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $devices = array_filter($devices, fn($d) => $d['LOAD_STATUS'] === 'FOR LOAD');

    echo json_encode(array_values($devices));
    exit;
}

if ($action === 'unsubmitted_devices') {
    header('Content-Type: application/json');

    $company = $_GET['company'] ?? $_SESSION['Company_ID'] ?? '';
    $date    = $_GET['date']    ?? date('Y-m-d');
    $site    = $_GET['site']    ?? null;

    if (empty($company)) {
        echo json_encode(['error' => 'Company ID is required']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    try {
        $sql = "
            SELECT TOP 1000
                d.LINEID,
                d.COMPANY_ID,
                d.SITE_ID,
                d.DEPARTMENT,
                d.PRINCIPAL,
                d.POSITION,
                d.BRAND,
                d.MODEL,
                d.IMEI,
                d.SERIAL,
                d.DATE_DEPLOYED,
                d.PERSON_USING,
                d.NUMBER,
                d.BALANCE,
                d.LAST_LOAD_HISTORY,
                d.LOAD_STATUS,
                d.YEARS_USING,
                d.REMARKS,
                d.DATE_ADDED,
                d.LOAD_TERMS,
                d.DEVICE_STATUS,
                d.REASON_CODE,
                d.DATE_SURRENDERED,
                d.DAYS_TO_REPAIR,
                d.TEMPORARY_DEVICE,
                d.IT_RECOMMENDATION,
                d.CHARGED_TO,
                cl.LINEID               AS Submitted_Log_LINEID,
                cl.DATE_CHECKED         AS Submitted_Date,
                cl.[USER]               AS Submitted_User,
                cl.LOAD_BALANCE         AS Submitted_Load_Balance,
                cl.DATA_USAGE           AS Submitted_Data_Usage,
                cl.IS_SUBMIT            AS Submitted_IS_SUBMIT,
                cl.IS_PHYSICAL_OK       AS Submitted_Physical_OK,
                cl.HAS_GAMES            AS Submitted_Has_Games,
                cl.IS_SYSTEM_UPDATED    AS Submitted_System_Updated,
                cl.OTHER_ISSUES         AS Submitted_Other_Issues,
                cl.CHECKED_BY           AS Submitted_Checked_By
            FROM [dbo].[BS_Device] d
            LEFT JOIN [dbo].[BS_Checking_logs] cl
                ON cl.NUMBER = d.NUMBER
               AND CONVERT(date, cl.DATE_CHECKED) = :checkdate
            WHERE d.COMPANY_ID = :company
              AND cl.LINEID IS NULL
              AND d.NUMBER IS NOT NULL
              AND TRIM(d.NUMBER) <> ''
        ";

        if ($site !== null && $site !== '') {
            $sql .= " AND d.SITE_ID = :site";
        }

        $sql .= "
            ORDER BY 
                d.SITE_ID,
                d.DEPARTMENT,
                d.PERSON_USING,
                d.NUMBER,
                d.LINEID
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bindValue(':company',   $company,   PDO::PARAM_STR);
        $stmt->bindValue(':checkdate', $date,      PDO::PARAM_STR);

        if ($site !== null && $site !== '') {
            $stmt->bindValue(':site', $site, PDO::PARAM_STR);
        }

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['status'] = 'Unsubmitted';
        }
        unset($row);

        echo json_encode($rows);
    } catch (PDOException $e) {
        echo json_encode([
            'error'   => 'Database error',
            'message' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error'   => 'Server error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'update_remarks') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $lineid = $input['id'] ?? null;
    if (!$lineid) {
        echo json_encode(['success' => false, 'message' => 'LINEID is required']);
        exit;
    }

    try {
        $stmtOld = $conn->prepare("SELECT REMARKS FROM BS_Device WHERE LINEID = :id");
        $stmtOld->execute([':id' => $lineid]);
        $old = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: [];

        $oldV = trim((string)($old['REMARKS'] ?? ''));
        $newV = trim((string)($input['REMARKS'] ?? ''));

        if ($oldV !== $newV) {
            $changes = ['REMARKS' => ['old' => $oldV ?: null, 'new' => $newV ?: null]];
            logAuditChange($conn, $lineid, 'UPDATE_REMARKS', $changes, $newV ?: null);
        }

        $stmt = $conn->prepare("UPDATE BS_Device SET REMARKS = :REMARKS WHERE LINEID = :LINEID");
        $stmt->execute([':REMARKS' => $newV ?: null, ':LINEID' => $lineid]);

        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'get_remarks_values') {
    header('Content-Type: application/json');

    $company = $_GET['company'] ?? null;
    try {
        $sql = "SELECT DISTINCT RTRIM(LTRIM(REMARKS)) AS REMARKS FROM BS_Device WHERE REMARKS IS NOT NULL AND RTRIM(LTRIM(REMARKS)) <> ''";
        if ($company) {
            $sql .= " AND COMPANY_ID = :company";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':company' => $company]);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(array_values(array_filter($rows, function($r){ return trim($r) !== ''; })));
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'DB error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'submit_by_qr') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);

    $qrCode  = trim($input['qr_code'] ?? '');
    $company = $input['company'] ?? $_SESSION['Company_ID'] ?? '';

    if (empty($qrCode)) {
        echo json_encode(['success' => false, 'message' => 'QR Code is empty']);
        exit;
    }
    if (empty($company)) {
        echo json_encode(['success' => false, 'message' => 'Company ID is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            SELECT 
                LINEID,
                NUMBER,
                SITE_ID,
                BRAND,
                MODEL,
                SERIAL,
                IMEI,
                LAST_LOAD_HISTORY,
                BALANCE,
                REMARKS,
                PERSON_USING,
                LOAD_TERMS,
                DEVICE_STATUS,
                REASON_CODE,
                DATE_SURRENDERED,
                DAYS_TO_REPAIR,
                TEMPORARY_DEVICE,
                IT_RECOMMENDATION,
                CHARGED_TO
            FROM BS_Device 
            WHERE COMPANY_ID = ?
              AND (
                    CAST(LINEID AS VARCHAR(50)) = ? 
                 OR NUMBER = ? 
                 OR SERIAL = ? 
                 OR IMEI   = ?
              )
        ");

        $stmt->execute([$company, $qrCode, $qrCode, $qrCode, $qrCode]);

        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            $conn->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Device not found with this QR code: ' . htmlspecialchars($qrCode)
            ]);
            exit;
        }

        $lineid = $device['LINEID'];
        
        $next_load_date = null;
        $load_terms = $device['LOAD_TERMS'] ?? 1;
        
        if (!empty($device['LAST_LOAD_HISTORY']) && $device['LAST_LOAD_HISTORY'] != '0000-00-00') {
            $next_load_date = date('Y-m-d', strtotime("+{$load_terms} months", strtotime($device['LAST_LOAD_HISTORY'])));
        }

        $checkToday = $conn->prepare("
            SELECT 1 FROM BS_Checking_logs 
            WHERE NUMBER = ? 
              AND CONVERT(date, DATE_CHECKED) = CONVERT(date, GETDATE())
        ");
        $checkToday->execute([$device['NUMBER']]);

        if ($checkToday->fetch()) {
            $conn->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'This device has already been submitted today.',
                'device'  => [
                    'NUMBER'            => $device['NUMBER'],
                    'SITE_ID'           => $device['SITE_ID'],
                    'BRAND'             => $device['BRAND'],
                    'MODEL'             => $device['MODEL'],
                    'SERIAL'            => $device['SERIAL'],
                    'LAST_LOAD_HISTORY' => $device['LAST_LOAD_HISTORY'],
                    'BALANCE'           => $device['BALANCE'],
                    'NEXT_LOAD_DATE'    => $next_load_date,
                    'LOAD_TERMS'        => $load_terms,
                    'PERSON_USING'      => $device['PERSON_USING'],
                    'DEVICE_STATUS'     => $device['DEVICE_STATUS'] ?? '',
                    'REASON_CODE'       => $device['REASON_CODE'] ?? '',
                    'DATE_SURRENDERED'  => $device['DATE_SURRENDERED'] ?? '',
                    'DAYS_TO_REPAIR'    => $device['DAYS_TO_REPAIR'] ?? '',
                    'TEMPORARY_DEVICE'  => $device['TEMPORARY_DEVICE'] ?? '',
                    'IT_RECOMMENDATION' => $device['IT_RECOMMENDATION'] ?? '',
                    'CHARGED_TO'        => $device['CHARGED_TO'] ?? ''
                ]
            ]);
            exit;
        }

        logAuditChange(
            $conn,
            $lineid,
            'QR_SUBMIT',
            [],
            'Submitted via QR Code Scanner - QR: ' . $qrCode
        );

        $insertLog = $conn->prepare("
            INSERT INTO BS_Checking_logs (
                COMPANY_ID, SITE_ID, DATE_CHECKED, [USER], NUMBER,
                LOAD_BALANCE, DATA_USAGE, 
                IS_SUBMIT, IS_PHYSICAL_OK, HAS_GAMES, IS_SYSTEM_UPDATED,
                OTHER_ISSUES, CHECKED_BY, REMARKS
            ) VALUES (
                ?, ?, GETDATE(), ?, ?,
                ?, 0,
                'Yes', 'Yes', 'No', 'Yes',
                ?, ?, 'Submitted via QR Code Scanner'
            )
        ");

        $insertLog->execute([
            $company,
            $device['SITE_ID'] ?? '',
            $device['PERSON_USING'] ?? 'QR Scan',
            $device['NUMBER'] ?? '',
            $device['BALANCE'] ?? 0,
            $device['OTHER_ISSUES'] ?? '',
            $_SESSION['Name_of_user'] ?? 'QR Scanner'
        ]);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '✅ Device successfully submitted via QR',
            'device'  => [
                'NUMBER'            => $device['NUMBER'],
                'SITE_ID'           => $device['SITE_ID'],
                'BRAND'             => $device['BRAND'],
                'MODEL'             => $device['MODEL'],
                'SERIAL'            => $device['SERIAL'],
                'LAST_LOAD_HISTORY' => $device['LAST_LOAD_HISTORY'],
                'BALANCE'           => $device['BALANCE'],
                'NEXT_LOAD_DATE'    => $next_load_date,
                'LOAD_TERMS'        => $load_terms,
                'PERSON_USING'      => $device['PERSON_USING'],
                'DEVICE_STATUS'     => $device['DEVICE_STATUS'] ?? '',
                'REASON_CODE'       => $device['REASON_CODE'] ?? '',
                'DATE_SURRENDERED'  => $device['DATE_SURRENDERED'] ?? '',
                'DAYS_TO_REPAIR'    => $device['DAYS_TO_REPAIR'] ?? '',
                'TEMPORARY_DEVICE'  => $device['TEMPORARY_DEVICE'] ?? '',
                'IT_RECOMMENDATION' => $device['IT_RECOMMENDATION'] ?? '',
                'CHARGED_TO'        => $device['CHARGED_TO'] ?? ''
            ]
        ]);

    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Default response for invalid action
echo json_encode(['error' => 'Invalid action specified']);
exit;
?>