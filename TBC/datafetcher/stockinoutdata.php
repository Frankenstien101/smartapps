<?php
// api_stock_report.php - Backend API for Stock In/Out Report with Branch Support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include '../DB/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session to get branch info
session_start();
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
$userRole = $_SESSION['role'] ?? 'staff';

// Database connection
//try {
//    $conn = new PDO(
//        "sqlsrv:Server=172.40.0.81;Database=SIDJAN",
//        "sa",
//        'bspi.@dm1n'
//    );
//    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
//} catch (PDOException $e) {
//    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
//    exit();
//}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getStockMovements':
            getStockMovements($conn, $currentBranch, $userRole);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}

function getStockMovements($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    // Branch filter - staff can only see their branch, admin can see all
    $branchFilter = "";
    if ($userRole !== 'admin') {
        $branchFilter = "AND Branch = :branch";
    }
    
    // Get stock movements from StockInHistory
    $query = "SELECT 
                TransactionID,
                ProductID,
                ProductName,
                QuantityAdded,
                OldStock,
                NewStock,
                CostPrice,
                TotalCost,
                Notes,
                AddedBy,
                Branch,
                FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm:ss') AS TransactionDate
              FROM StockInHistory
              WHERE TransactionDate >= :start AND TransactionDate < :end
              $branchFilter
              ORDER BY TransactionDate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($userRole !== 'admin') {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary
    $totalStockIn = 0;
    $totalStockOut = 0;
    $totalValue = 0;
    
    foreach ($movements as $movement) {
        if ($movement['QuantityAdded'] > 0) {
            $totalStockIn += $movement['QuantityAdded'];
            $totalValue += $movement['TotalCost'];
        } else {
            $totalStockOut += abs($movement['QuantityAdded']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $movements,
        'summary' => [
            'total_stock_in' => $totalStockIn,
            'total_stock_out' => $totalStockOut,
            'total_value' => $totalValue,
            'branch' => $currentBranch
        ]
    ]);
}
?>