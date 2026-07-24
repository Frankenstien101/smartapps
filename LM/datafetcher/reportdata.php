<?php
session_start();
include __DIR__ . '/../../DB/dbcon.php';

header('Content-Type: application/json');

if (!isset($_GET['action'])) {
    echo json_encode(['error' => 'No action specified']);
    exit();
}

$action = $_GET['action'];

if ($action === 'resigned') {
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    $companyId = trim($_GET['company'] ?? '');
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';

    if (empty($companyId) || empty($from) || empty($to)) {
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    try {
        // include whole day for to date
        $toWithEnd = date('Y-m-d 23:59:59', strtotime($to));

        $sql = "SELECT TOP (10000)
                    LINEID, COMPANY_ID, SITE_ID, IMEI, SERIAL, BRAND, MODEL,
                    NAME_OF_USER, DEVICE_STATUS, REMARKS, DATE_RESIGNED, PROCESS_BY
                FROM dbo.BS_Resigned
                WHERE COMPANY_ID = :company AND DATE_RESIGNED BETWEEN :fromdt AND :todt
                ORDER BY DATE_RESIGNED DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':company', $companyId, PDO::PARAM_STR);
        $stmt->bindParam(':fromdt', $from, PDO::PARAM_STR);
        $stmt->bindParam(':todt', $toWithEnd, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($rows ?: []);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Query error', 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'exportresigned') {
    if (!$conn || !($conn instanceof PDO)) {
        header('Content-Type: text/plain');
        echo 'Database connection failed';
        exit();
    }

    $companyId = trim($_GET['company'] ?? '');
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';

    if (empty($companyId) || empty($from) || empty($to)) {
        header('Content-Type: text/plain');
        echo 'Missing parameters';
        exit();
    }

    try {
        $toWithEnd = date('Y-m-d 23:59:59', strtotime($to));

        $sql = "SELECT LINEID, COMPANY_ID, SITE_ID, IMEI, SERIAL, BRAND, MODEL,
                    NAME_OF_USER, DEVICE_STATUS, REMARKS, DATE_RESIGNED, PROCESS_BY
                FROM dbo.BS_Resigned
                WHERE COMPANY_ID = :company AND DATE_RESIGNED BETWEEN :fromdt AND :todt
                ORDER BY DATE_RESIGNED DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':company', $companyId, PDO::PARAM_STR);
        $stmt->bindParam(':fromdt', $from, PDO::PARAM_STR);
        $stmt->bindParam(':todt', $toWithEnd, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Output CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="resigned_report_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        // header
        fputcsv($out, array_keys($rows[0] ?? ['LINEID','COMPANY_ID','SITE_ID','IMEI','SERIAL','BRAND','MODEL','NAME_OF_USER','DEVICE_STATUS','REMARKS','DATE_RESIGNED','PROCESS_BY']));
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo 'Export error: ' . $e->getMessage();
    }
    exit();
}

// default
echo json_encode(['error' => 'Unknown action']);
exit();
?>