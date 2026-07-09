<?php
// pages/get_call.php
error_reporting(0);
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Include database connection
$conn = null;
require_once __DIR__ . '/../DB/dbcon.php';

// Check if connection exists
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

try {
    $sql = "SELECT * FROM [TBC].[dbo].[TBC_CALL_TRANSACTION] WHERE LINE_ID = " . $id;
    $stmt = $conn->query($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query failed']);
        exit();
    }
    
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($call) {
        echo json_encode(['success' => true, 'call' => $call]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Call not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>