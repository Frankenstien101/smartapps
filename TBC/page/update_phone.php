<?php
// update_phone.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../DB/dbcon.php';

$data = json_decode(file_get_contents('php://input'), true);
$customerId = $data['customer_id'] ?? '';
$phoneNumber = $data['phone_number'] ?? '';

if (empty($customerId) || empty($phoneNumber)) {
    echo json_encode(['success' => false, 'message' => 'Missing customer ID or phone number']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE [TBC].[dbo].[customers] SET PHONE_NUMBER = :phone WHERE CUSTOMER_ID = :customer_id");
    $stmt->bindValue(':phone', $phoneNumber);
    $stmt->bindValue(':customer_id', $customerId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Phone number updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>