<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
require_once __DIR__ . '/../DB/dbcon.php';

try {
    // Check if customer already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM [TBC].[dbo].[customers] WHERE CUSTOMER_ID = ? AND SITE = ? AND PRINCIPAL = ?");
    $checkStmt->execute([$data['customer_id'], $data['site'], $data['principal']]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] > 0) {
        // Update existing customer
        $stmt = $conn->prepare("
            UPDATE [TBC].[dbo].[customers] 
            SET CUSTOMER_NAME = ?, 
                ADDRESS = ?, 
                PHONE_NUMBER = ?, 
                SELLER_ID = ?,
            WHERE CUSTOMER_ID = ? AND SITE = ? AND PRINCIPAL = ?
        ");
        
        $stmt->execute([
            $data['customer_name'],
            $data['address'],
            $data['phone_number'],
            $data['seller_id'],
            $data['customer_id'],
            $data['site'],
            $data['principal']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
    } else {
        // Insert new customer
        $stmt = $conn->prepare("
            INSERT INTO [TBC].[dbo].[customers] 
            (CUSTOMER_ID, CUSTOMER_NAME, ADDRESS, PHONE_NUMBER, SELLER_ID, SITE, PRINCIPAL) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['customer_id'],
            $data['customer_name'],
            $data['address'],
            $data['phone_number'],
            $data['seller_id'],
            $data['site'],
            $data['principal']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>