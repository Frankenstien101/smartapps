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
