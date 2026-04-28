<?php
// admin_reports_api.php - Admin Reports API for Multi-Branch Reports
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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



// ============================================
// BRANCH FUNCTIONS
// ============================================

function getBranches($conn) {
    $query = "SELECT DISTINCT Branch FROM Products WHERE Branch IS NOT NULL
              UNION
              SELECT DISTINCT Branch FROM Sales WHERE Branch IS NOT NULL
              UNION
              SELECT DISTINCT Branch FROM Installments WHERE Branch IS NOT NULL
              UNION
              SELECT 'Main Branch' AS Branch
              ORDER BY Branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['success' => true, 'data' => $branches]);
}

// ============================================
// SALES REPORT
// ============================================

function getSalesReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = $branch !== 'all' ? "AND Branch = :branch" : "";
    
    // Sales Summary
    $summaryQuery = "SELECT 
                        COUNT(*) as TotalTransactions,
                        ISNULL(SUM(TotalAmount), 0) as TotalSales,
                        ISNULL(AVG(TotalAmount), 0) as AverageTransaction,
                        ISNULL(SUM(AmountReceived), 0) as TotalReceived,
                        ISNULL(SUM(ChangeAmount), 0) as TotalChange,
                        COUNT(DISTINCT CustomerName) as UniqueCustomers
                     FROM Sales
                     WHERE SaleDate >= :start AND SaleDate < :end $branchFilter";
    
    $stmt = $conn->prepare($summaryQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Daily Breakdown
    $dailyQuery = "SELECT 
                        CONVERT(VARCHAR, SaleDate, 23) as Date,
                        COUNT(*) as TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) as TotalSales
                     FROM Sales
                     WHERE SaleDate >= :start AND SaleDate < :end $branchFilter
                     GROUP BY CONVERT(VARCHAR, SaleDate, 23)
                     ORDER BY Date ASC";
    
    $stmt = $conn->prepare($dailyQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment Method Breakdown
    $paymentQuery = "SELECT 
                        PaymentMethod,
                        COUNT(*) as Count,
                        ISNULL(SUM(TotalAmount), 0) as Amount
                     FROM Sales
                     WHERE SaleDate >= :start AND SaleDate < :end $branchFilter
                     GROUP BY PaymentMethod
                     ORDER BY Amount DESC";
    
    $stmt = $conn->prepare($paymentQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top Products
    $topProductsQuery = "SELECT TOP 10
                            si.ProductName,
                            SUM(si.Quantity) as TotalQuantity,
                            SUM(si.Total) as TotalAmount
                         FROM SaleItems si
                         INNER JOIN Sales s ON si.SaleID = s.SaleID
                         WHERE s.SaleDate >= :start AND s.SaleDate < :end $branchFilter
                         GROUP BY si.ProductName
                         ORDER BY TotalAmount DESC";
    
    $stmt = $conn->prepare($topProductsQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'daily_breakdown' => $daily,
        'payment_methods' => $paymentMethods,
        'top_products' => $topProducts
    ]);
}

// ============================================
// INSTALLMENT REPORT
// ============================================

function getInstallmentReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = $branch !== 'all' ? "AND Branch = :branch" : "";
    
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
                     WHERE StartDate >= :start AND StartDate < :end $branchFilter";
    
    $stmt = $conn->prepare($summaryQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Monthly Breakdown
    $monthlyQuery = "SELECT 
                        FORMAT(StartDate, 'yyyy-MM') as Month,
                        COUNT(*) as InstallmentCount,
                        ISNULL(SUM(TotalAmount), 0) as TotalLoanAmount,
                        ISNULL(SUM(PaidAmount), 0) as TotalPaidAmount
                     FROM Installments
                     WHERE StartDate >= :start AND StartDate < :end $branchFilter
                     GROUP BY FORMAT(StartDate, 'yyyy-MM')
                     ORDER BY Month ASC";
    
    $stmt = $conn->prepare($monthlyQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top Customers by Loan Amount
    $topCustomersQuery = "SELECT TOP 10
                            CustomerName,
                            COUNT(*) as LoanCount,
                            ISNULL(SUM(TotalAmount), 0) as TotalLoanAmount,
                            ISNULL(SUM(PaidAmount), 0) as TotalPaidAmount,
                            ISNULL(SUM(RemainingBalance), 0) as RemainingBalance
                         FROM Installments
                         WHERE StartDate >= :start AND StartDate < :end $branchFilter
                         GROUP BY CustomerName
                         ORDER BY TotalLoanAmount DESC";
    
    $stmt = $conn->prepare($topCustomersQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'monthly_breakdown' => $monthly,
        'top_customers' => $topCustomers
    ]);
}

// ============================================
// STOCK REPORT
// ============================================

function getStockReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $branchFilter = $branch !== 'all' ? "WHERE Branch = :branch" : "";
    
    $query = "SELECT 
                ProductID, ProductCode, ProductName, Category, Brand,
                CurrentStock, CostPrice, SellingPrice,
                (CurrentStock * CostPrice) as TotalCostValue,
                (CurrentStock * SellingPrice) as TotalSellingValue
              FROM Products
              $branchFilter
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary
    $totalProducts = count($products);
    $totalStockValue = array_sum(array_column($products, 'TotalCostValue'));
    $totalSellingValue = array_sum(array_column($products, 'TotalSellingValue'));
    $lowStockCount = count(array_filter($products, function($p) { return $p['CurrentStock'] < 10; }));
    
    // Category Breakdown
    $categoryQuery = "SELECT 
                        Category,
                        COUNT(*) as ProductCount,
                        ISNULL(SUM(CurrentStock), 0) as TotalUnits,
                        ISNULL(SUM(CurrentStock * CostPrice), 0) as TotalValue
                      FROM Products
                      $branchFilter
                      GROUP BY Category
                      ORDER BY TotalValue DESC";
    
    $stmt = $conn->prepare($categoryQuery);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $categoryBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_products' => $totalProducts,
            'total_stock_value' => $totalStockValue,
            'total_selling_value' => $totalSellingValue,
            'low_stock_count' => $lowStockCount
        ],
        'products' => $products,
        'category_breakdown' => $categoryBreakdown
    ]);
}

// ============================================
// USER ACTIVITY REPORT
// ============================================

function getUserActivityReport($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = $branch !== 'all' ? "AND Branch = :branch" : "";
    
    $query = "SELECT 
                ua.ActivityID, ua.Username, ua.FullName, ua.Branch, ua.Role,
                ua.Action, ua.Module, ua.Description, ua.Status,
                CONVERT(VARCHAR, ua.ActivityDate, 120) as ActivityDate,
                ua.IPAddress
              FROM UserActivity ua
              WHERE ua.ActivityDate >= :start AND ua.ActivityDate < :end $branchFilter
              ORDER BY ua.ActivityDate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary
    $summaryQuery = "SELECT 
                        COUNT(*) as TotalActivities,
                        COUNT(DISTINCT Username) as UniqueUsers,
                        SUM(CASE WHEN Status = 'success' THEN 1 ELSE 0 END) as SuccessCount,
                        SUM(CASE WHEN Status = 'failed' THEN 1 ELSE 0 END) as FailedCount
                     FROM UserActivity
                     WHERE ActivityDate >= :start AND ActivityDate < :end $branchFilter";
    
    $stmt = $conn->prepare($summaryQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'activities' => $activities
    ]);
}

// ============================================
// DASHBOARD SUMMARY
// ============================================

function getDashboardSummary($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = $branch !== 'all' ? "AND Branch = :branch" : "";
    $branchFilterSales = $branch !== 'all' ? "AND Branch = :branch" : "";
    $branchFilterInstall = $branch !== 'all' ? "AND Branch = :branch" : "";
    
    // Sales Summary
    $salesQuery = "SELECT 
                        ISNULL(SUM(TotalAmount), 0) as TotalSales,
                        COUNT(*) as TransactionCount,
                        ISNULL(AVG(TotalAmount), 0) as AverageSale
                   FROM Sales
                   WHERE SaleDate >= :start AND SaleDate < :end $branchFilterSales";
    
    $stmt = $conn->prepare($salesQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Installment Summary
    $installQuery = "SELECT 
                        ISNULL(SUM(TotalAmount), 0) as TotalLoanAmount,
                        ISNULL(SUM(PaidAmount), 0) as TotalPaidAmount,
                        COUNT(*) as TotalInstallments
                     FROM Installments
                     WHERE StartDate >= :start AND StartDate < :end $branchFilterInstall";
    
    $stmt = $conn->prepare($installQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $installments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Stock Summary
    $stockQuery = "SELECT 
                        COUNT(*) as TotalProducts,
                        ISNULL(SUM(CurrentStock), 0) as TotalUnits,
                        ISNULL(SUM(CurrentStock * CostPrice), 0) as TotalStockValue
                   FROM Products";
    if ($branch !== 'all') {
        $stockQuery .= " WHERE Branch = :branch";
    }
    
    $stmt = $conn->prepare($stockQuery);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // User Activity Summary
    $activityQuery = "SELECT 
                        COUNT(*) as TotalActivities
                     FROM UserActivity
                     WHERE ActivityDate >= :start AND ActivityDate < :end";
    if ($branch !== 'all') {
        $activityQuery .= " AND Branch = :branch";
    }
    
    $stmt = $conn->prepare($activityQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sales' => $sales,
        'installments' => $installments,
        'stock' => $stock,
        'activities' => $activity
    ]);
}

// ============================================
// TOP PRODUCTS
// ============================================

function getTopProducts($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    $limit = intval($_GET['limit'] ?? 10);
    
    $branchFilter = $branch !== 'all' ? "AND s.Branch = :branch" : "";
    
    $query = "SELECT TOP $limit
                si.ProductName,
                SUM(si.Quantity) as TotalQuantitySold,
                SUM(si.Total) as TotalRevenue
              FROM SaleItems si
              INNER JOIN Sales s ON si.SaleID = s.SaleID
              WHERE s.SaleDate >= :start AND s.SaleDate < :end $branchFilter
              GROUP BY si.ProductName
              ORDER BY TotalRevenue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

// ============================================
// PAYMENT METHOD SUMMARY
// ============================================

function getPaymentMethodSummary($conn) {
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = $branch !== 'all' ? "AND Branch = :branch" : "";
    
    $query = "SELECT 
                PaymentMethod,
                COUNT(*) as TransactionCount,
                ISNULL(SUM(TotalAmount), 0) as TotalAmount
              FROM Sales
              WHERE SaleDate >= :start AND SaleDate < :end $branchFilter
              GROUP BY PaymentMethod
              ORDER BY TotalAmount DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $methods]);
}

// ============================================
// EXPORT REPORT
// ============================================

function exportReport($conn) {
    $reportType = $_GET['report_type'] ?? 'sales';
    $branch = $_GET['branch'] ?? 'all';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    $format = $_GET['format'] ?? 'csv';
    
    $branchFilter = $branch !== 'all' ? "AND Branch = :branch" : "";
    
    if ($reportType === 'sales') {
        $query = "SELECT 
                    s.ReceiptNo, s.CustomerName, s.TotalAmount, s.PaymentMethod,
                    CONVERT(VARCHAR, s.SaleDate, 120) as SaleDate,
                    STUFF((
                        SELECT ', ' + ProductName + ' x' + CAST(Quantity AS VARCHAR)
                        FROM SaleItems 
                        WHERE SaleID = s.SaleID
                        FOR XML PATH('')
                    ), 1, 2, '') as Items
                  FROM Sales s
                  WHERE s.SaleDate >= :start AND s.SaleDate < :end $branchFilter
                  ORDER BY s.SaleDate DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDatePlus);
        if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "sales_report_{$startDate}_to_{$endDate}.csv";
        $headers = ['Receipt No', 'Customer Name', 'Total Amount', 'Payment Method', 'Sale Date', 'Items'];
        
    } elseif ($reportType === 'installments') {
        $query = "SELECT 
                    InstallmentNo, CustomerName, TotalAmount, MonthlyPayment, PaidAmount, RemainingBalance,
                    Status, CONVERT(VARCHAR, StartDate, 120) as StartDate
                  FROM Installments
                  WHERE StartDate >= :start AND StartDate < :end $branchFilter
                  ORDER BY StartDate DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDatePlus);
        if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "installment_report_{$startDate}_to_{$endDate}.csv";
        $headers = ['Installment No', 'Customer Name', 'Total Amount', 'Monthly Payment', 'Paid Amount', 'Remaining Balance', 'Status', 'Start Date'];
        
    } elseif ($reportType === 'stock') {
        $branchFilterStock = $branch !== 'all' ? "WHERE Branch = :branch" : "";
        
        $query = "SELECT 
                    ProductCode, ProductName, Category, Brand, CurrentStock, CostPrice, SellingPrice,
                    (CurrentStock * CostPrice) as TotalValue
                  FROM Products
                  $branchFilterStock
                  ORDER BY ProductName";
        
        $stmt = $conn->prepare($query);
        if ($branch !== 'all') $stmt->bindParam(':branch', $branch);
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
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            // Clean data for CSV
            $cleanRow = array_map(function($cell) {
                return html_entity_decode(strip_tags($cell), ENT_QUOTES, 'UTF-8');
            }, $row);
            fputcsv($output, $cleanRow);
        }
        fclose($output);
        exit();
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}
?>
