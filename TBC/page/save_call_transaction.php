<?php
// /TBC/page/save_call_transaction.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../DB/dbcon.php';

$data = json_decode(file_get_contents('php://input'), true);

// Get data from request
$branch = $data['branch'] ?? '';
$principal = $data['principal'] ?? '';
$callDate = $data['call_date'] ?? date('Y-m-d');
$callDuration = $data['call_duration'] ?? '00:00:00';
$teleCaller = $data['tele_caller'] ?? $_SESSION['NAME'] ?? '';
$cuId = $data['cu_id'] ?? '';
$customer = $data['customer'] ?? '';
$phoneNumber = $data['phone_number'] ?? '';
$address = $data['address'] ?? '';
$vanId = $data['van_id'] ?? '';  // Allow empty VAN ID
$seller = $data['seller'] ?? '';
$invoiceNumber = $data['invoice_number'] ?? '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$dateInvoiced = $data['date_invoiced'] ?? null;
$storeNameAccuracy = $data['store_name_accuracy'] ?? '';
$correctedStoreName = $data['corrected_store_name'] ?? '';
$correctedAddress = $data['corrected_address'] ?? '';
$isPhoneNumberCorrect = $data['is_phone_number_correct'] ?? '';
$storeVisit = $data['store_visit'] ?? '';
$prodCall = $data['prod_call'] ?? '';
$amountVerification = $data['amount_verification'] ?? '';
$amountResult = $data['amount_result'] ?? '';
$status = $data['status'] ?? 'Pending';
$dialResult = $data['dial_result'] ?? '';

// Handle empty values - convert empty string to NULL for database
if ($vanId === '') $vanId = null;
if ($address === '') $address = null;
if ($correctedStoreName === '') $correctedStoreName = null;
if ($correctedAddress === '') $correctedAddress = null;

if (empty($cuId) || empty($customer)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: CU ID or Customer']);
    exit();
}

try {
    // Generate CALL_ID
    $callId = date('YmdHis') . rand(100, 999);
    
    $stmt = $conn->prepare("
        INSERT INTO [dbo].[TBC_CALL_TRANSACTION] (
            BRANCH, PRINCIPAL, CALL_ID, CALL_DATE, CALL_DURATION, TELE_CALLER,
            CU_ID, CUSTOMER, PHONE_NUMBER, ADDRESS, VAN_ID, SELLER,
            INVOICE_NUMBER, AMOUNT, DATE_INVOICED,
            STORE_NAME_ACCURACY, CORRECTED_STORE_NAME, CORRECTED_ADDRESS,
            IS_PHONE_NUMBER_CORRECT, STORE_VISIT, PROD_CALL,
            AMOUNT_VERIFICATION, AMOUNT_RESULT, STATUS, DIAL_RESULT
        ) VALUES (
            :branch, :principal, :call_id, :call_date, :call_duration, :tele_caller,
            :cu_id, :customer, :phone_number, :address, :van_id, :seller,
            :invoice_number, :amount, :date_invoiced,
            :store_name_accuracy, :corrected_store_name, :corrected_address,
            :is_phone_number_correct, :store_visit, :prod_call,
            :amount_verification, :amount_result, :status, :dial_result
        )
    ");
    
    $stmt->bindValue(':branch', $branch);
    $stmt->bindValue(':principal', $principal);
    $stmt->bindValue(':call_id', $callId);
    $stmt->bindValue(':call_date', $callDate);
    $stmt->bindValue(':call_duration', $callDuration);
    $stmt->bindValue(':tele_caller', $teleCaller);
    $stmt->bindValue(':cu_id', $cuId);
    $stmt->bindValue(':customer', $customer);
    $stmt->bindValue(':phone_number', $phoneNumber);
    $stmt->bindValue(':address', $address);
    $stmt->bindValue(':van_id', $vanId);
    $stmt->bindValue(':seller', $seller);
    $stmt->bindValue(':invoice_number', $invoiceNumber);
    $stmt->bindValue(':amount', $amount);
    $stmt->bindValue(':date_invoiced', $dateInvoiced);
    $stmt->bindValue(':store_name_accuracy', $storeNameAccuracy);
    $stmt->bindValue(':corrected_store_name', $correctedStoreName);
    $stmt->bindValue(':corrected_address', $correctedAddress);
    $stmt->bindValue(':is_phone_number_correct', $isPhoneNumberCorrect);
    $stmt->bindValue(':store_visit', $storeVisit);
    $stmt->bindValue(':prod_call', $prodCall);
    $stmt->bindValue(':amount_verification', $amountVerification);
    $stmt->bindValue(':amount_result', $amountResult);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':dial_result', $dialResult);
    
    $stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Call saved successfully',
        'call_id' => $callId
    ]);
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>