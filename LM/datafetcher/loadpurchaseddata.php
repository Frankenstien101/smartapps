<?php

ob_start();

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

session_start();
include __DIR__ . '/../../DB/dbcon.php';

function toSqlServerDate($inputDate) {
    if (empty($inputDate) || !is_string($inputDate)) {
        return null;
    }

    $clean = str_replace('T', ' ', trim($inputDate));

    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $clean)
             ?? DateTime::createFromFormat('Y-m-d H:i', $clean)
             ?? DateTime::createFromFormat('Y-m-d', $clean);

    if ($dateTime === false) {
        $dateTime = date_create($clean);
    }

    if ($dateTime === false) {
        return null;
    }

    return $dateTime->format('Y-m-d H:i:s');
}

$action = $_GET['action'] ?? '';

if ($action === 'addtopurchased') {
    header('Content-Type: application/json; charset=utf-8');

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid JSON data: ' . json_last_error_msg()
        ]);
        exit;
    }

    $device_id   = $input['device_id']   ?? null;
    $site_id     = $input['site_id']     ?? null;
    $number      = $input['number']      ?? null;
    $user        = $input['user']        ?? 'Unknown';
    $amount      = floatval($input['amount'] ?? 0);
    $dataadded   = floatval($input['dataadded'] ?? 0);
    $reference   = trim($input['reference'] ?? '');
    $load_date   = $input['load_date']   ?? null;

    if ($amount <= 0) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Amount must be greater than zero']);
        exit;
    }
    if ($dataadded <= 0) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Data added (GB) must be greater than zero']);
        exit;
    }
    if (empty($reference)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Reference / Transaction ID is required']);
        exit;
    }
    if (empty($number)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Mobile number is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // ────────────────────────────────────────────────
        // 1. Get previous LAST_LOAD_HISTORY
        // ────────────────────────────────────────────────
        $previousLoadHistory = null;

        $stmtCheck = $conn->prepare("
            SELECT LAST_LOAD_HISTORY 
            FROM BS_Device 
            WHERE NUMBER = ? 
              AND COMPANY_ID = ?
              AND (SITE_ID = ? OR ? IS NULL)
        ");

        $stmtCheck->execute([
            $number,
            $_SESSION['Company_ID'] ?? 'NA',
            $site_id,
            $site_id
        ]);

        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $lastLoadDateTime = null;
        if ($row && $row['LAST_LOAD_HISTORY']) {
            $lastLoadDateTime = new DateTime($row['LAST_LOAD_HISTORY']);
            $previousLoadHistory = $row['LAST_LOAD_HISTORY'];
        }

        $charged_to = 'SELLER';  // default

        $sixMonthsAgo = (new DateTime())->modify('-6 months');

        if (!$lastLoadDateTime || $lastLoadDateTime < $sixMonthsAgo) {
            $charged_to = 'COMPANY';
        }

        $insertPurchase = "
            INSERT INTO BS_Load_Purchases (
                COMPANY_ID, SITE_ID, NUMBER, PERSON, AMOUNT, DATA_ADDED,
                LOAD_DATE, REFERENCE, DATE_RECORDED, RECORDED_BY, 
                CHARGED_TO, PREVIOUS_LOAD_HISTORY
            ) VALUES (
                :COMPANY_ID, :SITE_ID, :NUMBER, :PERSON, :AMOUNT, :DATA_ADDED,
                :LOAD_DATE, :REFERENCE, CAST(GETDATE() AS DATE), :RECORDED_BY, 
                :CHARGED_TO, :PREVIOUS_LOAD_HISTORY
            )
        ";

        $stmtPurchase = $conn->prepare($insertPurchase);
        $insertOk = $stmtPurchase->execute([
            ':COMPANY_ID'           => $_SESSION['Company_ID'] ?? null,
            ':SITE_ID'              => $site_id,
            ':NUMBER'               => $number,
            ':PERSON'               => $user,
            ':AMOUNT'               => $amount,
            ':DATA_ADDED'           => $dataadded,
            ':LOAD_DATE'            => toSqlServerDate($load_date),
            ':REFERENCE'            => $reference,
            ':RECORDED_BY'          => $_SESSION['Name_of_user'] ?? 'SYSTEM',
            ':CHARGED_TO'           => $charged_to,
            ':PREVIOUS_LOAD_HISTORY' => $previousLoadHistory
        ]);

        if (!$insertOk) {
            throw new Exception("INSERT failed: " . implode(" | ", $stmtPurchase->errorInfo()));
        }

        $updateDevice = "
            UPDATE BS_Device
            SET 
                LOAD_STATUS       = 'OK',
                LAST_LOAD_HISTORY = ?,
                BALANCE           = ISNULL(BALANCE, 0) + ?,
                IS_COMPLIED       = ''
            WHERE NUMBER      = ?
              AND COMPANY_ID  = ?
              AND (SITE_ID = ? OR ? IS NULL)
        ";

        $stmtUpdate = $conn->prepare($updateDevice);
        $updateOk = $stmtUpdate->execute([
            toSqlServerDate($load_date),
            $dataadded,
            $number,
            $_SESSION['Company_ID'] ?? 'NA',
            $site_id,
            $site_id 
        ]);

        if (!$updateOk) {
            throw new Exception("UPDATE failed: " . implode(" | ", $stmtUpdate->errorInfo()));
        }

        $conn->commit();

        ob_end_clean();
        echo json_encode([
            'status'      => 'success',
            'message'     => 'Load purchase recorded successfully',
            'load_status' => 'OK'
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $conn->rollBack();

        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

ob_end_clean();
echo json_encode([
    'status'  => 'error',
    'message' => 'Invalid or missing action'
], JSON_UNESCAPED_UNICODE);
exit;