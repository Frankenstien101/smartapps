<?php
// api_reports.php - Backend API for Sales Reports
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
        "sqlsrv:Server=172.40.0.81;Database=SIDJAN",
        "sa",
        'bspi.@dm1n'
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit();
}

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
    switch ($action) {
        case 'getSalesReport':
            getSalesReport($conn);
            break;
        case 'getProductSalesReport':
            getProductSalesReport($conn);
            break;
        case 'getDailySales':
            getDailySales($conn);
            break;
        case 'getMonthlySales':
            getMonthlySales($conn);
            break;
        case 'getYearlySales':
            getYearlySales($conn);
            break;
        case 'getTopProducts':
            getTopProducts($conn);
            break;
        case 'getPaymentMethodReport':
            getPaymentMethodReport($conn);
            break;
        case 'getProfitReport':
            getProfitReport($conn);
            break;
        case 'getTaxReport':
            getTaxReport($conn);
            break;
        case 'exportReport':
            exportReport($conn);
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
// REPORT FUNCTIONS
// ============================================

function getSalesReport($conn) {
    $period = $_GET['period'] ?? 'daily';
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    if ($startDate && $endDate) {
        $query = "SELECT 
                    FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                    ISNULL(SUM(AmountReceived), 0) AS TotalReceived,
                    ISNULL(AVG(TotalAmount), 0) AS AverageTransaction,
                    PaymentMethod
                  FROM Sales
                  WHERE SaleDate BETWEEN :start AND :end
                  AND Status != 'cancelled'
                  GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd'), PaymentMethod
                  ORDER BY Date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    } else {
        if ($period === 'daily') {
            $query = "SELECT 
                        FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                        ISNULL(SUM(AmountReceived), 0) AS TotalReceived
                      FROM Sales
                      WHERE SaleDate >= DATEADD(DAY, -30, GETDATE())
                      AND Status != 'cancelled'
                      GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd')
                      ORDER BY Date DESC";
            $stmt = $conn->query($query);
        } elseif ($period === 'weekly') {
            $query = "SELECT 
                        DATEPART(WEEK, SaleDate) AS WeekNumber,
                        MIN(FORMAT(SaleDate, 'yyyy-MM-dd')) AS StartDate,
                        MAX(FORMAT(SaleDate, 'yyyy-MM-dd')) AS EndDate,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales
                      FROM Sales
                      WHERE SaleDate >= DATEADD(MONTH, -3, GETDATE())
                      AND Status != 'cancelled'
                      GROUP BY DATEPART(WEEK, SaleDate)
                      ORDER BY WeekNumber DESC";
            $stmt = $conn->query($query);
        } elseif ($period === 'monthly') {
            $query = "SELECT 
                        FORMAT(SaleDate, 'yyyy-MM') AS Month,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales
                      FROM Sales
                      WHERE SaleDate >= DATEADD(YEAR, -1, GETDATE())
                      AND Status != 'cancelled'
                      GROUP BY FORMAT(SaleDate, 'yyyy-MM')
                      ORDER BY Month DESC";
            $stmt = $conn->query($query);
        } else {
            $query = "SELECT 
                        FORMAT(SaleDate, 'yyyy') AS Year,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales
                      FROM Sales
                      WHERE Status != 'cancelled'
                      GROUP BY FORMAT(SaleDate, 'yyyy')
                      ORDER BY Year DESC";
            $stmt = $conn->query($query);
        }
    }
    
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary
    $summaryQuery = "SELECT 
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                        COUNT(*) AS TotalTransactions,
                        ISNULL(AVG(TotalAmount), 0) AS AverageSale,
                        ISNULL(MAX(TotalAmount), 0) AS HighestSale,
                        ISNULL(MIN(TotalAmount), 0) AS LowestSale
                      FROM Sales
                      WHERE Status != 'cancelled'";
    
    $stmt = $conn->query($summaryQuery);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $report,
        'summary' => $summary
    ]);
}

function getDailySales($conn) {
    $days = $_GET['days'] ?? 30;
    
    $query = "SELECT TOP (:days)
                FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                DATENAME(dw, SaleDate) AS DayName,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(SUM(AmountReceived), 0) AS TotalReceived
              FROM Sales
              WHERE Status != 'cancelled'
              GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd'), DATENAME(dw, SaleDate)
              ORDER BY SaleDate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate trends
    $trendQuery = "SELECT 
                    ISNULL(SUM(CASE WHEN SaleDate >= DATEADD(DAY, -7, GETDATE()) THEN TotalAmount ELSE 0 END), 0) AS ThisWeek,
                    ISNULL(SUM(CASE WHEN SaleDate BETWEEN DATEADD(DAY, -14, GETDATE()) AND DATEADD(DAY, -8, GETDATE()) THEN TotalAmount ELSE 0 END), 0) AS LastWeek
                  FROM Sales
                  WHERE Status != 'cancelled'";
    
    $stmt = $conn->query($trendQuery);
    $trend = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $percentageChange = 0;
    if ($trend['LastWeek'] > 0) {
        $percentageChange = (($trend['ThisWeek'] - $trend['LastWeek']) / $trend['LastWeek']) * 100;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $sales,
        'trend' => [
            'this_week' => $trend['ThisWeek'],
            'last_week' => $trend['LastWeek'],
            'percentage_change' => round($percentageChange, 2)
        ]
    ]);
}

function getMonthlySales($conn) {
    $months = $_GET['months'] ?? 12;
    
    $query = "SELECT TOP (:months)
                FORMAT(SaleDate, 'yyyy-MM') AS Month,
                DATENAME(month, SaleDate) AS MonthName,
                YEAR(SaleDate) AS Year,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales
              FROM Sales
              WHERE Status != 'cancelled'
              GROUP BY FORMAT(SaleDate, 'yyyy-MM'), DATENAME(month, SaleDate), YEAR(SaleDate)
              ORDER BY Year DESC, MONTH(SaleDate) DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':months', $months, PDO::PARAM_INT);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate yearly comparison
    $currentYear = date('Y');
    $lastYear = $currentYear - 1;
    
    $yearQuery = "SELECT 
                    YEAR(SaleDate) AS Year,
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales
                  FROM Sales
                  WHERE YEAR(SaleDate) IN (:current, :last)
                  AND Status != 'cancelled'
                  GROUP BY YEAR(SaleDate)";
    
    $stmt = $conn->prepare($yearQuery);
    $stmt->execute([':current' => $currentYear, ':last' => $lastYear]);
    $yearly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $sales,
        'yearly_comparison' => $yearly
    ]);
}

function getYearlySales($conn) {
    $query = "SELECT 
                YEAR(SaleDate) AS Year,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(AVG(TotalAmount), 0) AS AverageSale
              FROM Sales
              WHERE Status != 'cancelled'
              GROUP BY YEAR(SaleDate)
              ORDER BY Year DESC";
    
    $stmt = $conn->query($query);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $sales]);
}

function getProductSalesReport($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $limit = $_GET['limit'] ?? 50;
    
    $query = "SELECT TOP (:limit)
                si.ProductCode,
                si.ProductName,
                SUM(si.Quantity) AS TotalQuantity,
                COUNT(DISTINCT si.SaleID) AS NumberOfSales,
                ISNULL(AVG(si.Price), 0) AS AveragePrice,
                ISNULL(SUM(si.Total), 0) AS TotalRevenue
              FROM SaleItems si
              INNER JOIN Sales s ON si.SaleID = s.SaleID
              WHERE s.SaleDate BETWEEN :start AND :end
              AND s.Status != 'cancelled'
              GROUP BY si.ProductCode, si.ProductName
              ORDER BY TotalRevenue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total revenue
    $totalQuery = "SELECT ISNULL(SUM(TotalAmount), 0) AS TotalRevenue
                   FROM Sales
                   WHERE SaleDate BETWEEN :start AND :end
                   AND Status != 'cancelled'";
    
    $stmt = $conn->prepare($totalQuery);
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'total_revenue' => $total['TotalRevenue'] ?? 0
    ]);
}

function getTopProducts($conn) {
    $period = $_GET['period'] ?? 'month'; // week, month, year, all
    $limit = $_GET['limit'] ?? 10;
    
    switch ($period) {
        case 'week':
            $dateCondition = "s.SaleDate >= DATEADD(WEEK, -1, GETDATE())";
            break;
        case 'month':
            $dateCondition = "s.SaleDate >= DATEADD(MONTH, -1, GETDATE())";
            break;
        case 'year':
            $dateCondition = "s.SaleDate >= DATEADD(YEAR, -1, GETDATE())";
            break;
        default:
            $dateCondition = "1=1";
    }
    
    $query = "SELECT TOP (:limit)
                si.ProductCode,
                si.ProductName,
                SUM(si.Quantity) AS TotalSold,
                COUNT(DISTINCT si.SaleID) AS TransactionCount,
                ISNULL(SUM(si.Total), 0) AS Revenue
              FROM SaleItems si
              INNER JOIN Sales s ON si.SaleID = s.SaleID
              WHERE $dateCondition
              AND s.Status != 'cancelled'
              GROUP BY si.ProductCode, si.ProductName
              ORDER BY Revenue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

function getPaymentMethodReport($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    $query = "SELECT 
                PaymentMethod,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalAmount,
                ISNULL(AVG(TotalAmount), 0) AS AverageAmount,
                (ISNULL(SUM(TotalAmount), 0) * 100.0 / NULLIF((SELECT SUM(TotalAmount) FROM Sales WHERE SaleDate BETWEEN :start AND :end AND Status != 'cancelled'), 0)) AS Percentage
              FROM Sales
              WHERE SaleDate BETWEEN :start AND :end
              AND Status != 'cancelled'
              GROUP BY PaymentMethod
              ORDER BY TotalAmount DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $methods]);
}

function getProfitReport($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    // Get sales revenue
    $salesQuery = "SELECT ISNULL(SUM(TotalAmount), 0) AS TotalRevenue
                   FROM Sales
                   WHERE SaleDate BETWEEN :start AND :end
                   AND Status != 'cancelled'";
    $stmt = $conn->prepare($salesQuery);
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get cost of goods sold from products table
    $cogsQuery = "SELECT 
                    si.ProductID,
                    si.ProductName,
                    SUM(si.Quantity) AS QuantitySold,
                    AVG(p.CostPrice) AS AvgCost,
                    SUM(si.Quantity) * AVG(p.CostPrice) AS TotalCost
                  FROM SaleItems si
                  INNER JOIN Sales s ON si.SaleID = s.SaleID
                  LEFT JOIN Products p ON si.ProductID = p.ProductID
                  WHERE s.SaleDate BETWEEN :start AND :end
                  AND s.Status != 'cancelled'
                  GROUP BY si.ProductID, si.ProductName
                  ORDER BY TotalCost DESC";
    
    $stmt = $conn->prepare($cogsQuery);
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $cogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalCost = array_sum(array_column($cogs, 'TotalCost'));
    $totalRevenue = $sales['TotalRevenue'];
    $grossProfit = $totalRevenue - $totalCost;
    $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'profit_margin' => round($profitMargin, 2),
            'breakdown' => $cogs
        ]
    ]);
}

function getTaxReport($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    $query = "SELECT 
                FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(SUM(TotalAmount) * 0.12 / 1.12, 0) AS VatAmount
              FROM Sales
              WHERE SaleDate BETWEEN :start AND :end
              AND Status != 'cancelled'
              GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd')
              ORDER BY Date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $tax = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalVat = array_sum(array_column($tax, 'VatAmount'));
    $totalSales = array_sum(array_column($tax, 'TotalSales'));
    
    echo json_encode([
        'success' => true,
        'data' => $tax,
        'summary' => [
            'total_sales' => $totalSales,
            'total_vat' => $totalVat,
            'vat_rate' => 12
        ]
    ]);
}

function generateCustomReport($conn, $data) {
    $startDate = $data['start_date'] ?? date('Y-m-01');
    $endDate = $data['end_date'] ?? date('Y-m-t');
    $groupBy = $data['group_by'] ?? 'day'; // day, week, month
    
    $query = "SELECT 
                FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(AVG(TotalAmount), 0) AS AverageSale
              FROM Sales
              WHERE SaleDate BETWEEN :start AND :end
              AND Status != 'cancelled'
              GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd')
              ORDER BY Date ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $report,
        'parameters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'group_by' => $groupBy
        ]
    ]);
}

function exportReport($conn) {
    $type = $_GET['type'] ?? 'sales';
    $format = $_GET['format'] ?? 'csv';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    if ($type === 'sales') {
        $query = "SELECT 
                    s.ReceiptNo,
                    FORMAT(s.SaleDate, 'yyyy-MM-dd HH:mm') AS SaleDate,
                    s.CustomerName,
                    s.PaymentMethod,
                    s.TotalAmount,
                    s.AmountReceived,
                    s.ChangeAmount,
                    s.Status
                  FROM Sales s
                  WHERE s.SaleDate BETWEEN :start AND :end
                  ORDER BY s.SaleDate DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "sales_report_{$startDate}_to_{$endDate}.csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Receipt No', 'Date', 'Customer', 'Payment Method', 'Total Amount', 'Amount Received', 'Change', 'Status']);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    } elseif ($type === 'products') {
        $query = "SELECT 
                    si.ProductCode,
                    si.ProductName,
                    SUM(si.Quantity) AS QuantitySold,
                    SUM(si.Total) AS TotalRevenue
                  FROM SaleItems si
                  INNER JOIN Sales s ON si.SaleID = s.SaleID
                  WHERE s.SaleDate BETWEEN :start AND :end
                  GROUP BY si.ProductCode, si.ProductName
                  ORDER BY TotalRevenue DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "product_report_{$startDate}_to_{$endDate}.csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Product Code', 'Product Name', 'Quantity Sold', 'Total Revenue']);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
}
?>