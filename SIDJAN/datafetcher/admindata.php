<?php
// admin_reports_api.php - Admin Reports API for Multi-Branch Reports (FIXED)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include '../DB/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// DATABASE CONNECTION
// ============================================
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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$userRole = $_SESSION['Role'] ?? 'staff';

// Only admin can access these reports
if ($userRole !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access', 'message' => 'Admin access required']);
    exit();
}

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

function handleGetRequest($conn, $action) {
    switch ($action) {
        case 'getBranches':
            getBranches($conn);
            break;
        case 'getSalesReport':
            getSalesReport($conn);
            break;
        case 'getInstallmentReport':
            getInstallmentReport($conn);
            break;
        case 'getStockReport':
            getStockReport($conn);
            break;
        case 'getUserActivityReport':
            getUserActivityReport($conn);
            break;
        case 'getDashboardSummary':
            getDashboardSummary($conn);
            break;
        case 'getTopProducts':
            getTopProducts($conn);
            break;
        case 'getPaymentMethodSummary':
            getPaymentMethodSummary($conn);
            break;
        case 'exportReport':
            exportReport($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action) {
    // For future POST endpoints
    echo json_encode(['error' => 'POST not implemented']);
}

// ============================================
// BRANCH FUNCTIONS
// ============================================

function getBranches($conn) {
    $query = "SELECT DISTINCT Branch FROM Products WHERE Branch IS NOT NULL AND Branch != ''
              UNION
              SELECT DISTINCT Branch FROM Sales WHERE Branch IS NOT NULL AND Branch != ''
              UNION
              SELECT DISTINCT Branch FROM Installments WHERE Branch IS NOT NULL AND Branch != ''
              ORDER BY Branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add "All Branches" option for the frontend
    echo json_encode(['success' => true, 'data' => $branches]);
}

// ============================================
// SALES REPORT (WITH PROPER BRANCH FILTERING)
// ============================================

// ============================================
// SALES REPORT (FIXED - Top Products branch filter)
// ============================================

function getSalesReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    // Build branch condition
    $branchCondition = "";
    $params = [':start' => $startDate, ':end' => $endDatePlus];
    
    if ($branch !== 'all' && !empty($branch)) {
        $branchCondition = "AND s.Branch = :branch";
        $params[':branch'] = $branch;
    }
    
    // Sales Summary
    $summaryQuery = "SELECT 
                        COUNT(*) as TotalTransactions,
                        ISNULL(SUM(TotalAmount), 0) as TotalSales,
                        ISNULL(AVG(TotalAmount), 0) as AverageTransaction,
                        ISNULL(SUM(AmountReceived), 0) as TotalReceived,
                        ISNULL(SUM(ChangeAmount), 0) as TotalChange,
                        COUNT(DISTINCT CustomerName) as UniqueCustomers
                     FROM Sales s
                     WHERE s.SaleDate >= :start AND s.SaleDate < :end $branchCondition";
    
    $stmt = $conn->prepare($summaryQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Daily Breakdown
    $dailyQuery = "SELECT 
                        CONVERT(VARCHAR, s.SaleDate, 23) as Date,
                        COUNT(*) as TransactionCount,
                        ISNULL(SUM(s.TotalAmount), 0) as TotalSales
                     FROM Sales s
                     WHERE s.SaleDate >= :start AND s.SaleDate < :end $branchCondition
                     GROUP BY CONVERT(VARCHAR, s.SaleDate, 23)
                     ORDER BY Date ASC";
    
    $stmt = $conn->prepare($dailyQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment Method Breakdown
    $paymentQuery = "SELECT 
                        s.PaymentMethod,
                        COUNT(*) as Count,
                        ISNULL(SUM(s.TotalAmount), 0) as Amount
                     FROM Sales s
                     WHERE s.SaleDate >= :start AND s.SaleDate < :end $branchCondition
                     GROUP BY s.PaymentMethod
                     ORDER BY Amount DESC";
    
    $stmt = $conn->prepare($paymentQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // FIXED: Top Products with proper branch filtering through Sales table
    $topProductsQuery = "SELECT TOP 10
                            si.ProductName,
                            SUM(si.Quantity) as TotalQuantity,
                            SUM(si.Total) as TotalAmount
                         FROM SaleItems si
                         INNER JOIN Sales s ON si.SaleID = s.SaleID
                         WHERE s.SaleDate >= :start AND s.SaleDate < :end $branchCondition
                         GROUP BY si.ProductName
                         ORDER BY TotalAmount DESC";
    
    $stmt = $conn->prepare($topProductsQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'daily_breakdown' => $daily,
        'payment_methods' => $paymentMethods,
        'top_products' => $topProducts,
        'filters_applied' => [
            'branch' => $branch,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'top_products_count' => count($topProducts)
        ]
    ]);
}

// ============================================
// INSTALLMENT REPORT (WITH PROPER BRANCH FILTERING)
// ============================================

function getInstallmentReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchCondition = "";
    $params = [':start' => $startDate, ':end' => $endDatePlus];
    
    if ($branch !== 'all' && !empty($branch)) {
        $branchCondition = "AND Branch = :branch";
        $params[':branch'] = $branch;
    }
    
    // Installment Summary
    $summaryQuery = "SELECT 
                        COUNT(*) as TotalInstallments,
                        ISNULL(SUM(TotalAmount), 0) as TotalLoanAmount,
                        ISNULL(SUM(PaidAmount), 0) as TotalPaidAmount,
                        ISNULL(SUM(RemainingBalance), 0) as TotalRemainingBalance,
                        SUM(CASE WHEN Status = 'active' THEN 1 ELSE 0 END) as ActiveCount,
                        SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) as CompletedCount,
                        SUM(CASE WHEN Status = 'overdue' THEN 1 ELSE 0 END) as OverdueCount,
                        SUM(CASE WHEN Status = 'returned' THEN 1 ELSE 0 END) as ReturnedCount
                     FROM Installments
                     WHERE StartDate >= :start AND StartDate < :end $branchCondition";
    
    $stmt = $conn->prepare($summaryQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Monthly Breakdown
    $monthlyQuery = "SELECT 
                        FORMAT(StartDate, 'yyyy-MM') as Month,
                        COUNT(*) as InstallmentCount,
                        ISNULL(SUM(TotalAmount), 0) as TotalLoanAmount,
                        ISNULL(SUM(PaidAmount), 0) as TotalPaidAmount
                     FROM Installments
                     WHERE StartDate >= :start AND StartDate < :end $branchCondition
                     GROUP BY FORMAT(StartDate, 'yyyy-MM')
                     ORDER BY Month ASC";
    
    $stmt = $conn->prepare($monthlyQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'monthly_breakdown' => $monthly,
        'filters_applied' => ['branch' => $branch, 'start_date' => $startDate, 'end_date' => $endDate]
    ]);
}

// ============================================
// STOCK REPORT (WITH PROPER BRANCH FILTERING)
// ============================================

function getStockReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    
    $branchCondition = "";
    $params = [];
    
    if ($branch !== 'all' && !empty($branch)) {
        $branchCondition = "WHERE Branch = :branch";
        $params[':branch'] = $branch;
    }
    
    $query = "SELECT 
                ProductID, ProductCode, ProductName, Category, Brand,
                ISNULL(AvailableQuantity, 0) as CurrentStock,
                CostPrice, SellingPrice,
                (ISNULL(AvailableQuantity, 0) * CostPrice) as TotalCostValue,
                (ISNULL(AvailableQuantity, 0) * SellingPrice) as TotalSellingValue
              FROM Products
              $branchCondition
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary
    $totalProducts = count($products);
    $totalStockValue = array_sum(array_column($products, 'TotalCostValue'));
    $totalSellingValue = array_sum(array_column($products, 'TotalSellingValue'));
    $totalUnits = array_sum(array_column($products, 'CurrentStock'));
    $lowStockCount = count(array_filter($products, function($p) { return $p['CurrentStock'] < 10; }));
    
    // Category Breakdown
    $categoryQuery = "SELECT 
                        Category,
                        COUNT(*) as ProductCount,
                        ISNULL(SUM(AvailableQuantity), 0) as TotalUnits,
                        ISNULL(SUM(AvailableQuantity * CostPrice), 0) as TotalValue
                      FROM Products
                      $branchCondition
                      GROUP BY Category
                      ORDER BY TotalValue DESC";
    
    $stmt = $conn->prepare($categoryQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $categoryBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_products' => $totalProducts,
            'total_stock_value' => $totalStockValue,
            'total_selling_value' => $totalSellingValue,
            'total_units' => $totalUnits,
            'low_stock_count' => $lowStockCount
        ],
        'products' => $products,
        'category_breakdown' => $categoryBreakdown,
        'filters_applied' => ['branch' => $branch]
    ]);
}

// ============================================
// USER ACTIVITY REPORT (WITH PROPER BRANCH FILTERING)
// ============================================

function getUserActivityReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchCondition = "";
    $params = [':start' => $startDate, ':end' => $endDatePlus];
    
    if ($branch !== 'all' && !empty($branch)) {
        $branchCondition = "AND Branch = :branch";
        $params[':branch'] = $branch;
    }
    
    $query = "SELECT 
                ActivityID, Username, FullName, Branch, Role,
                Action, Module, Description, Status,
                CONVERT(VARCHAR, ActivityDate, 120) as ActivityDate,
                IPAddress
              FROM UserActivity
              WHERE ActivityDate >= :start AND ActivityDate < :end $branchCondition
              ORDER BY ActivityDate DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary
    $summaryQuery = "SELECT 
                        COUNT(*) as TotalActivities,
                        COUNT(DISTINCT Username) as UniqueUsers,
                        SUM(CASE WHEN Status = 'success' THEN 1 ELSE 0 END) as SuccessCount,
                        SUM(CASE WHEN Status = 'failed' THEN 1 ELSE 0 END) as FailedCount
                     FROM UserActivity
                     WHERE ActivityDate >= :start AND ActivityDate < :end $branchCondition";
    
    $stmt = $conn->prepare($summaryQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'activities' => $activities,
        'filters_applied' => ['branch' => $branch, 'start_date' => $startDate, 'end_date' => $endDate]
    ]);
}

// ============================================
// DASHBOARD SUMMARY (WITH PROPER BRANCH FILTERING)
// ============================================

function getDashboardSummary($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchCondition = "";
    $params = [':start' => $startDate, ':end' => $endDatePlus];
    
    if ($branch !== 'all' && !empty($branch)) {
        $branchCondition = "AND Branch = :branch";
        $params[':branch'] = $branch;
    }
    
    // Sales Summary
    $salesQuery = "SELECT 
                        ISNULL(SUM(TotalAmount), 0) as TotalSales,
                        COUNT(*) as TransactionCount
                   FROM Sales
                   WHERE SaleDate >= :start AND SaleDate < :end $branchCondition";
    
    $stmt = $conn->prepare($salesQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Installment Summary
    $installQuery = "SELECT 
                        ISNULL(SUM(TotalAmount), 0) as TotalLoanAmount,
                        ISNULL(SUM(PaidAmount), 0) as TotalPaidAmount,
                        COUNT(*) as TotalInstallments
                     FROM Installments
                     WHERE StartDate >= :start AND StartDate < :end $branchCondition";
    
    $stmt = $conn->prepare($installQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $installments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Stock Summary
    $stockCondition = "";
    $stockParams = [];
    if ($branch !== 'all' && !empty($branch)) {
        $stockCondition = "WHERE Branch = :branch";
        $stockParams[':branch'] = $branch;
    }
    
    $stockQuery = "SELECT 
                        COUNT(*) as TotalProducts,
                        ISNULL(SUM(AvailableQuantity), 0) as TotalUnits,
                        ISNULL(SUM(AvailableQuantity * CostPrice), 0) as TotalStockValue
                   FROM Products
                   $stockCondition";
    
    $stmt = $conn->prepare($stockQuery);
    foreach ($stockParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // User Activity Summary
    $activityQuery = "SELECT 
                        COUNT(*) as TotalActivities
                     FROM UserActivity
                     WHERE ActivityDate >= :start AND ActivityDate < :end $branchCondition";
    
    $stmt = $conn->prepare($activityQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sales' => $sales,
        'installments' => $installments,
        'stock' => $stock,
        'activities' => $activity,
        'filters_applied' => ['branch' => $branch, 'start_date' => $startDate, 'end_date' => $endDate]
    ]);
}

function getTopProducts($conn) {
    // Similar implementation as sales report's top products
    getSalesReport($conn);
}

function getPaymentMethodSummary($conn) {
    // Similar implementation as sales report's payment methods
    getSalesReport($conn);
}

// ============================================
// EXPORT REPORT (WITH PROPER BRANCH FILTERING)
// ============================================

function exportReport($conn) {
    $reportType = $_GET['report_type'] ?? 'sales';
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    $format = $_GET['format'] ?? 'csv';
    
    $branchCondition = "";
    $params = [];
    
    if ($branch !== 'all' && !empty($branch)) {
        $branchCondition = "AND Branch = :branch";
        $params[':branch'] = $branch;
    }
    
    if ($reportType === 'sales') {
        $query = "SELECT 
                    ReceiptNo, CustomerName, TotalAmount, PaymentMethod,
                    CONVERT(VARCHAR, SaleDate, 120) as SaleDate
                  FROM Sales
                  WHERE SaleDate >= :start AND SaleDate < :end $branchCondition
                  ORDER BY SaleDate DESC";
        
        $params[':start'] = $startDate;
        $params[':end'] = $endDatePlus;
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "sales_report_{$startDate}_to_{$endDate}.csv";
        $headers = ['Receipt No', 'Customer Name', 'Total Amount', 'Payment Method', 'Sale Date'];
        
    } elseif ($reportType === 'installments') {
        $query = "SELECT 
                    InstallmentNo, CustomerName, TotalAmount, MonthlyPayment, PaidAmount, RemainingBalance,
                    Status, CONVERT(VARCHAR, StartDate, 120) as StartDate
                  FROM Installments
                  WHERE StartDate >= :start AND StartDate < :end $branchCondition
                  ORDER BY StartDate DESC";
        
        $params[':start'] = $startDate;
        $params[':end'] = $endDatePlus;
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "installment_report_{$startDate}_to_{$endDate}.csv";
        $headers = ['Installment No', 'Customer Name', 'Total Amount', 'Monthly Payment', 'Paid Amount', 'Remaining Balance', 'Status', 'Start Date'];
        
    } elseif ($reportType === 'stock') {
        $stockCondition = "";
        $stockParams = [];
        
        if ($branch !== 'all' && !empty($branch)) {
            $stockCondition = "WHERE Branch = :branch";
            $stockParams[':branch'] = $branch;
        }
        
        $query = "SELECT 
                    ProductCode, ProductName, Category, Brand, 
                    ISNULL(AvailableQuantity, 0) as CurrentStock,
                    CostPrice, SellingPrice,
                    (ISNULL(AvailableQuantity, 0) * CostPrice) as TotalValue
                  FROM Products
                  $stockCondition
                  ORDER BY ProductName";
        
        $stmt = $conn->prepare($query);
        foreach ($stockParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "stock_report_{$startDate}.csv";
        $headers = ['Product Code', 'Product Name', 'Category', 'Brand', 'Current Stock', 'Cost Price', 'Selling Price', 'Total Value'];
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid report type']);
        return;
    }
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            $cleanRow = array_map(function($cell) {
                return html_entity_decode(strip_tags($cell), ENT_QUOTES, 'UTF-8');
            }, array_values($row));
            fputcsv($output, $cleanRow);
        }
        fclose($output);
        exit();
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}
?>