<?php
session_start();
include __DIR__ . '/../../DB/dbcon.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    // Required fields check
    if (empty($data['SITE_ID'])) {
        throw new Exception("SITE_ID is required");
    }
    if (empty($data['DATE_DEPLOYED'])) {
        throw new Exception("DATE_DEPLOYED is required");
    }
    if (!isset($data['BALANCE']) || $data['BALANCE'] === '' || floatval($data['BALANCE']) < 0) {
        throw new Exception("BALANCE must be a non-negative number");
    }
    if (!isset($data['MONTHTOLOAD']) || $data['MONTHTOLOAD'] === '' || (int)$data['MONTHTOLOAD'] < 1) {
        throw new Exception("MONTHTOLOAD must be a positive integer");
    }

    $params = [
        ':company_id'       => $_SESSION['Company_ID'] ?? null,
        ':site_id'          => trim($data['SITE_ID'] ?? ''),
        ':department'       => trim($data['DEPARTMENT'] ?? '') ?: null,
        ':principal'        => trim($data['PRINCIPAL'] ?? '') ?: null,
        ':position'         => trim($data['POSITION'] ?? '') ?: null,
        ':brand'            => trim($data['BRAND'] ?? '') ?: null,
        ':model'            => trim($data['MODEL'] ?? '') ?: null,
        ':imei'             => trim($data['IMEI'] ?? '') ?: null,
        ':serial'           => trim($data['SERIAL'] ?? '') ?: null,
        ':date_deployed'    => $data['DATE_DEPLOYED'] ?: null,
        ':person_using'     => trim($data['PERSON_USING'] ?? '') ?: null,
        ':number'           => trim($data['NUMBER'] ?? '') ?: null,
        ':balance'          => floatval($data['BALANCE'] ?? 0),
        ':remarks'          => trim($data['REMARKS'] ?? '') ?: null,

        // Default / computed values
        ':load_status'      => 'NEW',
        ':status'           => 'AVAILABLE',
        ':years_using'      => 0,
        ':last_load_history'=> null,
        ':consumed'         => 0.00,
        ':is_complied'      => 0,
        ':ir_count'         => 0,
        ':load_terms'       => (int)($data['MONTHTOLOAD'] ?? 1),
    ];

    $sql = "
    INSERT INTO dbo.BS_Device (
        COMPANY_ID, SITE_ID, DEPARTMENT, PRINCIPAL, POSITION,
        BRAND, MODEL, IMEI, SERIAL, DATE_DEPLOYED,
        PERSON_USING, NUMBER, BALANCE, REMARKS,
        DATE_ADDED, LOAD_STATUS, STATUS,
        YEARS_USING, LAST_LOAD_HISTORY, CONSUMED,
        IS_COMPLIED, IR_COUNT, LOAD_TERMS
    ) VALUES (
        :company_id, :site_id, :department, :principal, :position,
        :brand, :model, :imei, :serial, :date_deployed,
        :person_using, :number, :balance, :remarks,
        GETDATE(), :load_status, :status,
        :years_using, :last_load_history, :consumed,
        :is_complied, :ir_count, :load_terms
    )";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $newLineId = $conn->lastInsertId();

    echo json_encode([
        'success'  => true,
        'message'  => 'Device added successfully',
        'lineid'   => $newLineId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}