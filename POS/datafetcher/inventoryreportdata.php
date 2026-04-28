<?php
// api_inventory_report.php - Backend API for Inventory Reports with Branch Support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// DATABASE CONNECTION
// ============================================
try {
    $conn = new PDO(
        "sqlsrv:Server=172.40.0.81;Database=POS",
        "sa",
        'bspi.@dm1n'
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit();
}

// Start session for user tracking
session_start();
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
$userRole = $_SESSION['role'] ?? 'staff';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================
// API ROUTES
// ============================================
try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $action);
            break;
        case 'POST':
            handlePostRequest($conn, $action);
            break;
        default:
            echo json_encode(['error' => 'Invalid request method']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}

// ============================================
// HANDLER FUNCTIONS
// ============================================

function handleGetRequest($conn, $action) {
    global $currentBranch, $userRole;
    
    switch ($action) {
        case 'getInventorySummary':
            getInventorySummary($conn, $currentBranch);
            break;
        case 'getLowStockReport':
            getLowStockReport($conn, $currentBranch);
            break;
        case 'getStockMovementReport':
            getStockMovementReport($conn, $currentBranch);
            break;
        case 'getCategoryReport':
            getCategoryReport($conn, $currentBranch);
            break;
        case 'getBrandReport':
            getBrandReport($conn, $currentBranch);
            break;
        case 'getProductDetails':
            getProductDetails($conn, $currentBranch);
            break;
        case 'getInventoryValueReport':
            getInventoryValueReport($conn, $currentBranch);
            break;
        case 'getStockInReport':
            getStockInReport($conn, $currentBranch);
            break;
        case 'getStockOutReport':
            getStockOutReport($conn, $currentBranch);
            break;
        case 'getTopProducts':
            getTopProducts($conn, $currentBranch);
            break;
        case 'exportReport':
            exportReport($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'generateCustomReport':
            generateCustomReport($conn, $data);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// INVENTORY REPORT FUNCTIONS WITH BRANCH
// ============================================

function getInventorySummary($conn, $currentBranch) {
    $query = "SELECT 
                COUNT(*) AS TotalProducts,
                ISNULL(SUM(CurrentStock), 0) AS TotalUnits,
                ISNULL(SUM(CurrentStock * SellingPrice), 0) AS TotalInventoryValue,
                ISNULL(SUM(CurrentStock * CostPrice), 0) AS TotalCostValue,
                ISNULL(AVG(SellingPrice), 0) AS AveragePrice,
                COUNT(CASE WHEN CurrentStock = 0 THEN 1 END) AS OutOfStockCount,
                COUNT(CASE WHEN CurrentStock < 10 AND CurrentStock > 0 THEN 1 END) AS LowStockCount,
                COUNT(CASE WHEN CurrentStock >= 10 THEN 1 END) AS WellStockedCount
              FROM Products
              WHERE Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate potential profit
    $summary['PotentialProfit'] = $summary['TotalInventoryValue'] - $summary['TotalCostValue'];
    
    echo json_encode(['success' => true, 'data' => $summary]);
}

function getLowStockReport($conn, $currentBranch) {
    $threshold = $_GET['threshold'] ?? 10;
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand, 
                CurrentStock, 
                CostPrice, 
                SellingPrice,
                (SellingPrice - CostPrice) AS ProfitPerUnit,
                (CurrentStock * SellingPrice) AS TotalValue
              FROM Products 
              WHERE CurrentStock <= :threshold AND Branch = :branch
              ORDER BY CurrentStock ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':threshold' => $threshold, ':branch' => $currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = [
        'total_low_stock' => count($products),
        'total_value' => array_sum(array_column($products, 'TotalValue')),
        'total_units' => array_sum(array_column($products, 'CurrentStock'))
    ];
    
    echo json_encode(['success' => true, 'data' => $products, 'summary' => $summary]);
}

function getStockMovementReport($conn, $currentBranch) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $query = "SELECT 
                ProductID,
                ProductName,
                SUM(CASE WHEN QuantityAdded > 0 THEN QuantityAdded ELSE 0 END) AS TotalStockIn,
                SUM(CASE WHEN QuantityAdded < 0 THEN ABS(QuantityAdded) ELSE 0 END) AS TotalStockOut,
                SUM(QuantityAdded) AS NetChange,
                COUNT(*) AS TransactionCount,
                SUM(TotalCost) AS TotalCostValue
              FROM StockInHistory
              WHERE TransactionDate >= :start AND TransactionDate < :end
              AND Branch = :branch
              GROUP BY ProductID, ProductName
              ORDER BY NetChange DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':start' => $startDate, ':end' => $endDatePlus, ':branch' => $currentBranch]);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = [
        'total_stock_in' => array_sum(array_column($movements, 'TotalStockIn')),
        'total_stock_out' => array_sum(array_column($movements, 'TotalStockOut')),
        'total_transactions' => array_sum(array_column($movements, 'TransactionCount')),
        'total_cost_value' => array_sum(array_column($movements, 'TotalCostValue'))
    ];
    
    echo json_encode(['success' => true, 'data' => $movements, 'summary' => $summary]);
}

function getCategoryReport($conn, $currentBranch) {
    $query = "SELECT 
                Category,
                COUNT(*) AS ProductCount,
                ISNULL(SUM(CurrentStock), 0) AS TotalUnits,
                ISNULL(SUM(CurrentStock * SellingPrice), 0) AS TotalValue,
                ISNULL(AVG(SellingPrice), 0) AS AveragePrice,
                ISNULL(SUM(CurrentStock * CostPrice), 0) AS TotalCost,
                (ISNULL(SUM(CurrentStock * SellingPrice), 0) - ISNULL(SUM(CurrentStock * CostPrice), 0)) AS PotentialProfit
              FROM Products
              WHERE Branch = :branch
              GROUP BY Category
              ORDER BY TotalValue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalValue = array_sum(array_column($categories, 'TotalValue'));
    foreach ($categories as &$cat) {
        $cat['Percentage'] = $totalValue > 0 ? round(($cat['TotalValue'] / $totalValue) * 100, 2) : 0;
    }
    
    echo json_encode(['success' => true, 'data' => $categories, 'total_value' => $totalValue]);
}

function getBrandReport($conn, $currentBranch) {
    $query = "SELECT 
                Brand,
                COUNT(*) AS ProductCount,
                ISNULL(SUM(CurrentStock), 0) AS TotalUnits,
                ISNULL(SUM(CurrentStock * SellingPrice), 0) AS TotalValue,
                ISNULL(AVG(SellingPrice), 0) AS AveragePrice
              FROM Products
              WHERE Brand IS NOT NULL AND Brand != '' AND Branch = :branch
              GROUP BY Brand
              ORDER BY TotalValue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $brands]);
}

function getProductDetails($conn, $currentBranch) {
    $category = $_GET['category'] ?? 'all';
    $brand = $_GET['brand'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $limit = intval($_GET['limit'] ?? 100);
    
    $categoryFilter = $category !== 'all' ? "AND Category = :category" : "";
    $brandFilter = $brand !== 'all' ? "AND Brand = :brand" : "";
    $searchFilter = $search !== '' ? "AND (ProductName LIKE :search OR ProductCode LIKE :search)" : "";
    
    $query = "SELECT TOP $limit
                ProductID, ProductCode, ProductName, Category, Brand,
                CurrentStock, CostPrice, SellingPrice,
                (SellingPrice - CostPrice) AS ProfitPerUnit,
                (CurrentStock * SellingPrice) AS TotalValue,
                (CurrentStock * CostPrice) AS TotalCost,
                CreatedAt, UpdatedAt
              FROM Products
              WHERE Branch = :branch $categoryFilter $brandFilter $searchFilter
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    if ($category !== 'all') $stmt->bindParam(':category', $category);
    if ($brand !== 'all') $stmt->bindParam(':brand', $brand);
    if ($search !== '') {
        $searchTerm = "%{$search}%";
        $stmt->bindParam(':search', $searchTerm);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = [
        'total_products' => count($products),
        'total_units' => array_sum(array_column($products, 'CurrentStock')),
        'total_value' => array_sum(array_column($products, 'TotalValue')),
        'total_cost' => array_sum(array_column($products, 'TotalCost')),
        'potential_profit' => array_sum(array_column($products, 'TotalValue')) - array_sum(array_column($products, 'TotalCost'))
    ];
    
    echo json_encode(['success' => true, 'data' => $products, 'summary' => $summary]);
}

function getInventoryValueReport($conn, $currentBranch) {
    $query = "SELECT 
                FORMAT(CreatedAt, 'yyyy-MM-dd') AS Date,
                COUNT(*) AS ProductsAdded,
                ISNULL(SUM(CurrentStock * SellingPrice), 0) AS InventoryValue,
                ISNULL(SUM(CurrentStock * CostPrice), 0) AS CostValue
              FROM Products
              WHERE Branch = :branch
              GROUP BY FORMAT(CreatedAt, 'yyyy-MM-dd')
              ORDER BY Date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $values]);
}

function getStockInReport($conn, $currentBranch) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $query = "SELECT 
                TransactionID,
                ProductName,
                QuantityAdded,
                OldStock,
                NewStock,
                CostPrice,
                TotalCost,
                InvoiceNo,
                SupplierName,
                Notes,
                FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm') AS TransactionDate,
                AddedBy
              FROM StockInHistory
              WHERE TransactionDate >= :start AND TransactionDate < :end
              AND QuantityAdded > 0
              AND Branch = :branch
              ORDER BY TransactionDate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':start' => $startDate, ':end' => $endDatePlus, ':branch' => $currentBranch]);
    $stockIn = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = [
        'total_quantity' => array_sum(array_column($stockIn, 'QuantityAdded')),
        'total_cost' => array_sum(array_column($stockIn, 'TotalCost')),
        'total_transactions' => count($stockIn)
    ];
    
    echo json_encode(['success' => true, 'data' => $stockIn, 'summary' => $summary]);
}

function getStockOutReport($conn, $currentBranch) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $query = "SELECT 
                TransactionID,
                ProductName,
                ABS(QuantityAdded) AS QuantityOut,
                OldStock,
                NewStock,
                Notes,
                FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm') AS TransactionDate,
                AddedBy
              FROM StockInHistory
              WHERE TransactionDate >= :start AND TransactionDate < :end
              AND QuantityAdded < 0
              AND Branch = :branch
              ORDER BY TransactionDate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':start' => $startDate, ':end' => $endDatePlus, ':branch' => $currentBranch]);
    $stockOut = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = [
        'total_quantity' => array_sum(array_column($stockOut, 'QuantityOut')),
        'total_transactions' => count($stockOut)
    ];
    
    echo json_encode(['success' => true, 'data' => $stockOut, 'summary' => $summary]);
}

function getTopProducts($conn, $currentBranch) {
    $type = $_GET['type'] ?? 'value'; // value, quantity, profit
    $limit = intval($_GET['limit'] ?? 10);
    
    if ($type === 'value') {
        $query = "SELECT TOP $limit
                    ProductID, ProductCode, ProductName, Category, Brand,
                    CurrentStock, SellingPrice,
                    (CurrentStock * SellingPrice) AS InventoryValue
                  FROM Products
                  WHERE Branch = :branch
                  ORDER BY InventoryValue DESC";
    } elseif ($type === 'quantity') {
        $query = "SELECT TOP $limit
                    ProductID, ProductCode, ProductName, Category, Brand,
                    CurrentStock, SellingPrice
                  FROM Products
                  WHERE Branch = :branch
                  ORDER BY CurrentStock DESC";
    } else {
        $query = "SELECT TOP $limit
                    ProductID, ProductCode, ProductName, Category, Brand,
                    CurrentStock, CostPrice, SellingPrice,
                    (SellingPrice - CostPrice) AS ProfitPerUnit,
                    (CurrentStock * (SellingPrice - CostPrice)) AS PotentialProfit
                  FROM Products
                  WHERE Branch = :branch
                  ORDER BY PotentialProfit DESC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

function generateCustomReport($conn, $data) {
    global $currentBranch;
    
    $startDate = $data['start_date'] ?? date('Y-m-01');
    $endDate = $data['end_date'] ?? date('Y-m-t');
    $reportType = $data['report_type'] ?? 'movement';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    if ($reportType === 'movement') {
        $query = "SELECT 
                    FORMAT(TransactionDate, 'yyyy-MM-dd') AS Date,
                    SUM(CASE WHEN QuantityAdded > 0 THEN QuantityAdded ELSE 0 END) AS StockIn,
                    SUM(CASE WHEN QuantityAdded < 0 THEN ABS(QuantityAdded) ELSE 0 END) AS StockOut,
                    SUM(QuantityAdded) AS NetChange
                  FROM StockInHistory
                  WHERE TransactionDate >= :start AND TransactionDate < :end
                  AND Branch = :branch
                  GROUP BY FORMAT(TransactionDate, 'yyyy-MM-dd')
                  ORDER BY Date ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':start' => $startDate, ':end' => $endDatePlus, ':branch' => $currentBranch]);
        $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $query = "SELECT 
                    ProductName,
                    Category,
                    Brand,
                    CurrentStock,
                    SellingPrice,
                    (CurrentStock * SellingPrice) AS TotalValue
                  FROM Products
                  WHERE Branch = :branch
                  ORDER BY TotalValue DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':branch' => $currentBranch]);
        $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'data' => $report]);
}

function exportReport($conn, $currentBranch) {
    $type = $_GET['type'] ?? 'inventory';
    $format = $_GET['format'] ?? 'csv';
    
    if ($type === 'inventory') {
        $query = "SELECT 
                    ProductCode, ProductName, Category, Brand,
                    CurrentStock, CostPrice, SellingPrice,
                    (CurrentStock * SellingPrice) AS TotalValue
                  FROM Products
                  WHERE Branch = :branch
                  ORDER BY ProductName";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':branch' => $currentBranch]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "inventory_report_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Product Code', 'Product Name', 'Category', 'Brand', 'Current Stock', 'Cost Price', 'Selling Price', 'Total Value']);
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['ProductCode'],
                $row['ProductName'],
                $row['Category'],
                $row['Brand'],
                $row['CurrentStock'],
                number_format($row['CostPrice'], 2),
                number_format($row['SellingPrice'], 2),
                number_format($row['TotalValue'], 2)
            ]);
        }
        
        fclose($output);
        exit();
    } elseif ($type === 'lowstock') {
        $query = "SELECT 
                    ProductCode, ProductName, Category, Brand, CurrentStock, SellingPrice
                  FROM Products
                  WHERE CurrentStock < 10 AND Branch = :branch
                  ORDER BY CurrentStock ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':branch' => $currentBranch]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "low_stock_report_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Product Code', 'Product Name', 'Category', 'Brand', 'Current Stock', 'Selling Price']);
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['ProductCode'],
                $row['ProductName'],
                $row['Category'],
                $row['Brand'],
                $row['CurrentStock'],
                number_format($row['SellingPrice'], 2)
            ]);
        }
        
        fclose($output);
        exit();
    }
}
?>