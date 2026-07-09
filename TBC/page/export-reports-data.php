<?php
session_start();
require_once __DIR__ . '/../DB/dbcon.php';

// Check login
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get filter parameters from POST or GET
$dateFrom = $_REQUEST['date_from'] ?? date('Y-m-d');
$dateTo = $_REQUEST['date_to'] ?? date('Y-m-d');

// Check if this is an export request
$isExport = isset($_REQUEST['export']) && $_REQUEST['export'] == '1';

try {
    // Prepare SQL query
    $stmt = $conn->prepare("
        SELECT *
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        ORDER BY CALL_DATE DESC
    ");
    
    $stmt->execute([
        ':from' => $dateFrom,
        ':to' => $dateTo
    ]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If this is an export request, download CSV
    if ($isExport) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="call_report_' . date('Ymd_His') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers - All columns
        fputcsv($output, [
            'BRANCH',
            'PRINCIPAL',
            'CALL_ID',
            'CALL_DATE',
            'CALL_DURATION',
            'TELE_CALLER',
            'CU_ID',
            'CUSTOMER',
            'PHONE_NUMBER',
            'ADDRESS',
            'VAN_ID',
            'SELLER',
            'INVOICE_NUMBER',
            'AMOUNT',
            'DATE_INVOICED',
            'STORE_NAME_ACCURACY',
            'CORRECTED_STORE_NAME',
            'CORRECTED_ADDRESS',
            'IS_PHONE_NUMBER_CORRECT',
            'STORE_VISIT',
            'PROD_CALL',
            'AMOUNT_VERIFICATION',
            'AMOUNT_RESULT',
            'STATUS',
            'DIAL_RESULT',
            'CREATED_AT',
            'UPDATED_AT'
        ]);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['BRANCH'] ?? '',
                $row['PRINCIPAL'] ?? '',
                $row['CALL_ID'] ?? '',
                $row['CALL_DATE'] ?? '',
                $row['CALL_DURATION'] ?? '',
                $row['TELE_CALLER'] ?? '',
                $row['CU_ID'] ?? '',
                $row['CUSTOMER'] ?? '',
                $row['PHONE_NUMBER'] ?? '',
                $row['ADDRESS'] ?? '',
                $row['VAN_ID'] ?? '',
                $row['SELLER'] ?? '',
                $row['INVOICE_NUMBER'] ?? '',
                $row['AMOUNT'] ?? 0,
                $row['DATE_INVOICED'] ?? '',
                $row['STORE_NAME_ACCURACY'] ?? '',
                $row['CORRECTED_STORE_NAME'] ?? '',
                $row['CORRECTED_ADDRESS'] ?? '',
                $row['IS_PHONE_NUMBER_CORRECT'] ?? '',
                $row['STORE_VISIT'] ?? '',
                $row['PROD_CALL'] ?? '',
                $row['AMOUNT_VERIFICATION'] ?? '',
                $row['AMOUNT_RESULT'] ?? '',
                $row['STATUS'] ?? '',
                $row['DIAL_RESULT'] ?? '',
                $row['CREATED_AT'] ?? '',
                $row['UPDATED_AT'] ?? ''
            ]);
        }
        
        fclose($output);
        exit();
    }
    
    // Otherwise, return JSON for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'total' => count($data),
        'data' => $data,
        'date_from' => $dateFrom,
        'date_to' => $dateTo
    ]);
    
} catch (PDOException $e) {
    if ($isExport) {
        die("Export Error: " . $e->getMessage());
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>