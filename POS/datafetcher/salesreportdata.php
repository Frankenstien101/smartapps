<?php
// api_reports.php - Backend API for Sales Reports with Branch Support
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
        case 'getSalesReport':
            getSalesReport($conn, $currentBranch, $userRole);
            break;
        case 'getProductSalesReport':
            getProductSalesReport($conn, $currentBranch, $userRole);
            break;
        case 'getDailySales':
            getDailySales($conn, $currentBranch);
            break;
        case 'getMonthlySales':
            getMonthlySales($conn, $currentBranch);
            break;
        case 'getYearlySales':
            getYearlySales($conn, $currentBranch);
            break;
        case 'getTopProducts':
            getTopProducts($conn, $currentBranch);
            break;
        case 'getPaymentMethodReport':
            getPaymentMethodReport($conn, $currentBranch);
            break;
        case 'getProfitReport':
            getProfitReport($conn, $currentBranch);
            break;
        case 'getTaxReport':
            getTaxReport($conn, $currentBranch);
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
// REPORT FUNCTIONS WITH BRANCH
// ============================================

function getSalesReport($conn, $currentBranch, $userRole) {
    $period = $_GET['period'] ?? 'daily';
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Branch filter
    $branchFilter = "";
    $params = [];
    
    if ($userRole !== 'admin') {
        $branchFilter = "AND Branch = :branch";
        $params[':branch'] = $currentBranch;
    }
    
    if ($startDate && $endDate) {
        // Add one day to end date to include the full day
        $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
        $params[':start'] = $startDate;
        $params[':end'] = $endDatePlus;
        
        $query = "SELECT 
                    FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                    ISNULL(SUM(AmountReceived), 0) AS TotalReceived,
                    ISNULL(AVG(TotalAmount), 0) AS AverageTransaction,
                    PaymentMethod
                  FROM Sales
                  WHERE SaleDate >= :start AND SaleDate < :end
                  AND (Status != 'cancelled' OR Status IS NULL)
                  $branchFilter
                  GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd'), PaymentMethod
                  ORDER BY MIN(SaleDate) DESC";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
    } else {
        if ($period === 'daily') {
            $query = "SELECT 
                        FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                        ISNULL(SUM(AmountReceived), 0) AS TotalReceived
                      FROM Sales
                      WHERE SaleDate >= DATEADD(DAY, -30, GETDATE())
                      AND (Status != 'cancelled' OR Status IS NULL)
                      $branchFilter
                      GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd')
                      ORDER BY MIN(SaleDate) DESC";
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        } elseif ($period === 'weekly') {
            $query = "SELECT 
                        DATEPART(WEEK, SaleDate) AS WeekNumber,
                        MIN(FORMAT(SaleDate, 'yyyy-MM-dd')) AS StartDate,
                        MAX(FORMAT(SaleDate, 'yyyy-MM-dd')) AS EndDate,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales
                      FROM Sales
                      WHERE SaleDate >= DATEADD(MONTH, -3, GETDATE())
                      AND (Status != 'cancelled' OR Status IS NULL)
                      $branchFilter
                      GROUP BY DATEPART(WEEK, SaleDate)
                      ORDER BY MIN(SaleDate) DESC";
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        } elseif ($period === 'monthly') {
            $query = "SELECT 
                        FORMAT(SaleDate, 'yyyy-MM') AS Month,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales
                      FROM Sales
                      WHERE SaleDate >= DATEADD(YEAR, -1, GETDATE())
                      AND (Status != 'cancelled' OR Status IS NULL)
                      $branchFilter
                      GROUP BY FORMAT(SaleDate, 'yyyy-MM')
                      ORDER BY MIN(SaleDate) DESC";
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        } else {
            $query = "SELECT 
                        FORMAT(SaleDate, 'yyyy') AS Year,
                        COUNT(*) AS TransactionCount,
                        ISNULL(SUM(TotalAmount), 0) AS TotalSales
                      FROM Sales
                      WHERE (Status != 'cancelled' OR Status IS NULL)
                      $branchFilter
                      GROUP BY FORMAT(SaleDate, 'yyyy')
                      ORDER BY MIN(SaleDate) DESC";
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        }
    }
    
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary
    if ($startDate && $endDate) {
        $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
        $summaryParams = [':start' => $startDate, ':end' => $endDatePlus];
        if ($userRole !== 'admin') {
            $summaryParams[':branch'] = $currentBranch;
            $branchFilter = "AND Branch = :branch";
        } else {
            $branchFilter = "";
        }
        
        $summaryQuery = "SELECT 
                            ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                            COUNT(*) AS TotalTransactions,
                            ISNULL(AVG(TotalAmount), 0) AS AverageSale,
                            ISNULL(MAX(TotalAmount), 0) AS HighestSale,
                            ISNULL(MIN(TotalAmount), 0) AS LowestSale
                          FROM Sales
                          WHERE SaleDate >= :start AND SaleDate < :end
                          AND (Status != 'cancelled' OR Status IS NULL)
                          $branchFilter";
        
        $stmt = $conn->prepare($summaryQuery);
        foreach ($summaryParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
    } else {
        $summaryParams = [];
        if ($userRole !== 'admin') {
            $summaryParams[':branch'] = $currentBranch;
            $branchFilter = "AND Branch = :branch";
        } else {
            $branchFilter = "";
        }
        
        $summaryQuery = "SELECT 
                            ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                            COUNT(*) AS TotalTransactions,
                            ISNULL(AVG(TotalAmount), 0) AS AverageSale,
                            ISNULL(MAX(TotalAmount), 0) AS HighestSale,
                            ISNULL(MIN(TotalAmount), 0) AS LowestSale
                          FROM Sales
                          WHERE (Status != 'cancelled' OR Status IS NULL)
                          $branchFilter";
        
        $stmt = $conn->prepare($summaryQuery);
        foreach ($summaryParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
    }
    
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $report,
        'summary' => $summary
    ]);
}

function getProductSalesReport($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $limit = intval($_GET['limit'] ?? 50);
    
    // Add one day to end date to include full day
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    // Branch filter
    $branchFilter = "";
    $params = [':start' => $startDate, ':end' => $endDatePlus];
    
    if ($userRole !== 'admin') {
        $branchFilter = "AND s.Branch = :branch";
        $params[':branch'] = $currentBranch;
    }
    
    $query = "SELECT TOP $limit
                si.ProductCode,
                si.ProductName,
                SUM(si.Quantity) AS TotalQuantity,
                COUNT(DISTINCT si.SaleID) AS NumberOfSales,
                ISNULL(AVG(si.Price), 0) AS AveragePrice,
                ISNULL(SUM(si.Total), 0) AS TotalRevenue
              FROM SaleItems si
              INNER JOIN Sales s ON si.SaleID = s.SaleID
              WHERE s.SaleDate >= :start AND s.SaleDate < :end
              AND (s.Status != 'cancelled' OR s.Status IS NULL)
              $branchFilter
              GROUP BY si.ProductCode, si.ProductName
              ORDER BY SUM(si.Total) DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total revenue
    $totalParams = [':start' => $startDate, ':end' => $endDatePlus];
    if ($userRole !== 'admin') {
        $totalParams[':branch'] = $currentBranch;
        $branchFilter = "AND Branch = :branch";
    } else {
        $branchFilter = "";
    }
    
    $totalQuery = "SELECT ISNULL(SUM(TotalAmount), 0) AS TotalRevenue
                   FROM Sales
                   WHERE SaleDate >= :start AND SaleDate < :end
                   AND (Status != 'cancelled' OR Status IS NULL)
                   $branchFilter";
    
    $stmt = $conn->prepare($totalQuery);
    foreach ($totalParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'total_revenue' => $total['TotalRevenue'] ?? 0
    ]);
}

function getDailySales($conn, $currentBranch) {
    $days = intval($_GET['days'] ?? 30);
    
    $query = "SELECT TOP $days
                FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                DATENAME(dw, SaleDate) AS DayName,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(SUM(AmountReceived), 0) AS TotalReceived
              FROM Sales
              WHERE (Status != 'cancelled' OR Status IS NULL)
              AND Branch = :branch
              GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd'), DATENAME(dw, SaleDate)
              ORDER BY MAX(SaleDate) DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate trends
    $trendQuery = "SELECT 
                    ISNULL(SUM(CASE WHEN SaleDate >= DATEADD(DAY, -7, GETDATE()) THEN TotalAmount ELSE 0 END), 0) AS ThisWeek,
                    ISNULL(SUM(CASE WHEN SaleDate BETWEEN DATEADD(DAY, -14, GETDATE()) AND DATEADD(DAY, -8, GETDATE()) THEN TotalAmount ELSE 0 END), 0) AS LastWeek
                  FROM Sales
                  WHERE (Status != 'cancelled' OR Status IS NULL)
                  AND Branch = :branch";
    
    $stmt = $conn->prepare($trendQuery);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
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

function getMonthlySales($conn, $currentBranch) {
    $months = intval($_GET['months'] ?? 12);
    
    $query = "SELECT TOP $months
                FORMAT(SaleDate, 'yyyy-MM') AS Month,
                DATENAME(month, SaleDate) AS MonthName,
                YEAR(SaleDate) AS Year,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales
              FROM Sales
              WHERE (Status != 'cancelled' OR Status IS NULL)
              AND Branch = :branch
              GROUP BY FORMAT(SaleDate, 'yyyy-MM'), DATENAME(month, SaleDate), YEAR(SaleDate)
              ORDER BY MAX(SaleDate) DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
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
                  AND (Status != 'cancelled' OR Status IS NULL)
                  AND Branch = :branch
                  GROUP BY YEAR(SaleDate)";
    
    $stmt = $conn->prepare($yearQuery);
    $stmt->bindParam(':current', $currentYear);
    $stmt->bindParam(':last', $lastYear);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $yearly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $sales,
        'yearly_comparison' => $yearly
    ]);
}

function getYearlySales($conn, $currentBranch) {
    $query = "SELECT 
                YEAR(SaleDate) AS Year,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(AVG(TotalAmount), 0) AS AverageSale
              FROM Sales
              WHERE (Status != 'cancelled' OR Status IS NULL)
              AND Branch = :branch
              GROUP BY YEAR(SaleDate)
              ORDER BY YEAR(SaleDate) DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $sales]);
}

function getTopProducts($conn, $currentBranch) {
    $period = $_GET['period'] ?? 'month';
    $limit = intval($_GET['limit'] ?? 10);
    
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
    
    $query = "SELECT TOP $limit
                si.ProductCode,
                si.ProductName,
                SUM(si.Quantity) AS TotalSold,
                COUNT(DISTINCT si.SaleID) AS TransactionCount,
                ISNULL(SUM(si.Total), 0) AS Revenue
              FROM SaleItems si
              INNER JOIN Sales s ON si.SaleID = s.SaleID
              WHERE $dateCondition
              AND (s.Status != 'cancelled' OR s.Status IS NULL)
              AND s.Branch = :branch
              GROUP BY si.ProductCode, si.ProductName
              ORDER BY SUM(si.Total) DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

function getPaymentMethodReport($conn, $currentBranch) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    // Add one day to end date to include full day
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    // Get payment method breakdown
    $query = "SELECT 
                ISNULL(PaymentMethod, 'other') AS PaymentMethod,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalAmount,
                ISNULL(AVG(TotalAmount), 0) AS AverageAmount
              FROM Sales
              WHERE SaleDate >= :start AND SaleDate < :end
              AND (Status != 'cancelled' OR Status IS NULL)
              AND Branch = :branch
              GROUP BY PaymentMethod
              ORDER BY SUM(TotalAmount) DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total sales across all payment methods
    $totalSalesQuery = "SELECT ISNULL(SUM(TotalAmount), 0) AS TotalSales
                        FROM Sales
                        WHERE SaleDate >= :start AND SaleDate < :end
                        AND (Status != 'cancelled' OR Status IS NULL)
                        AND Branch = :branch";
    $stmt = $conn->prepare($totalSalesQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSales = floatval($totalResult['TotalSales'] ?? 0);
    
    // Calculate total transactions
    $totalTransactionsQuery = "SELECT COUNT(*) AS TotalTransactions
                               FROM Sales
                               WHERE SaleDate >= :start AND SaleDate < :end
                               AND (Status != 'cancelled' OR Status IS NULL)
                               AND Branch = :branch";
    $stmt = $conn->prepare($totalTransactionsQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $totalTransResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalTransactions = intval($totalTransResult['TotalTransactions'] ?? 0);
    
    // Calculate percentage and ensure numeric values
    foreach ($methods as &$method) {
        $method['TotalAmount'] = floatval($method['TotalAmount']);
        $method['AverageAmount'] = floatval($method['AverageAmount']);
        $method['TransactionCount'] = intval($method['TransactionCount']);
        
        if ($totalSales > 0) {
            $method['Percentage'] = round(($method['TotalAmount'] / $totalSales) * 100, 2);
        } else {
            $method['Percentage'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $methods,
        'summary' => [
            'total_sales' => round($totalSales, 2),
            'total_transactions' => $totalTransactions,
            'average_transaction' => $totalTransactions > 0 ? round($totalSales / $totalTransactions, 2) : 0
        ]
    ]);
}

function getProfitReport($conn, $currentBranch) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    // Add one day to end date to include full day
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    // Check if Products table has CostPrice column
    $checkColumnQuery = "SELECT COLUMN_NAME 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_NAME = 'Products' AND COLUMN_NAME = 'CostPrice'";
    $stmt = $conn->query($checkColumnQuery);
    $hasCostPrice = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    
    // Get sales revenue
    $salesQuery = "SELECT ISNULL(SUM(TotalAmount), 0) AS TotalRevenue
                   FROM Sales
                   WHERE SaleDate >= :start AND SaleDate < :end
                   AND (Status != 'cancelled' OR Status IS NULL)
                   AND Branch = :branch";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRevenue = floatval($sales['TotalRevenue'] ?? 0);
    
    // Get cost of goods sold
    if ($hasCostPrice) {
        $cogsQuery = "SELECT 
                        si.ProductID,
                        si.ProductName,
                        SUM(si.Quantity) AS QuantitySold,
                        ISNULL(AVG(p.CostPrice), 0) AS AvgCost,
                        SUM(si.Quantity) * ISNULL(AVG(p.CostPrice), 0) AS TotalCost,
                        SUM(si.Total) AS ItemRevenue
                      FROM SaleItems si
                      INNER JOIN Sales s ON si.SaleID = s.SaleID
                      LEFT JOIN Products p ON si.ProductID = p.ProductID
                      WHERE s.SaleDate >= :start AND s.SaleDate < :end
                      AND (s.Status != 'cancelled' OR s.Status IS NULL)
                      AND s.Branch = :branch
                      GROUP BY si.ProductID, si.ProductName
                      ORDER BY SUM(si.Quantity) * ISNULL(AVG(p.CostPrice), 0) DESC";
    } else {
        $cogsQuery = "SELECT 
                        si.ProductID,
                        si.ProductName,
                        SUM(si.Quantity) AS QuantitySold,
                        ISNULL(AVG(si.Price) * 0.7, 0) AS AvgCost,
                        SUM(si.Quantity) * (ISNULL(AVG(si.Price), 0) * 0.7) AS TotalCost,
                        SUM(si.Total) AS ItemRevenue
                      FROM SaleItems si
                      INNER JOIN Sales s ON si.SaleID = s.SaleID
                      WHERE s.SaleDate >= :start AND s.SaleDate < :end
                      AND (s.Status != 'cancelled' OR s.Status IS NULL)
                      AND s.Branch = :branch
                      GROUP BY si.ProductID, si.ProductName
                      ORDER BY SUM(si.Quantity) * (ISNULL(AVG(si.Price), 0) * 0.7) DESC";
    }
    
    $stmt = $conn->prepare($cogsQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $cogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalCost = 0;
    $itemRevenue = 0;
    $breakdown = [];
    
    foreach ($cogs as $item) {
        $cost = floatval($item['TotalCost'] ?? 0);
        $revenue = floatval($item['ItemRevenue'] ?? 0);
        $totalCost += $cost;
        $itemRevenue += $revenue;
        
        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
        
        $breakdown[] = [
            'ProductID' => $item['ProductID'],
            'ProductName' => $item['ProductName'],
            'QuantitySold' => intval($item['QuantitySold'] ?? 0),
            'TotalRevenue' => $revenue,
            'TotalCost' => $cost,
            'Profit' => $profit,
            'Margin' => round($margin, 2)
        ];
    }
    
    // Use the higher of the two revenue values
    $finalRevenue = max($totalRevenue, $itemRevenue);
    
    $grossProfit = $finalRevenue - $totalCost;
    $profitMargin = $finalRevenue > 0 ? ($grossProfit / $finalRevenue) * 100 : 0;
    
    if ($finalRevenue == 0 && $totalCost == 0) {
        $grossProfit = 0;
        $profitMargin = 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_revenue' => round($finalRevenue, 2),
            'total_cost' => round($totalCost, 2),
            'gross_profit' => round($grossProfit, 2),
            'profit_margin' => round($profitMargin, 2),
            'breakdown' => $breakdown,
            'has_cost_data' => $hasCostPrice
        ]
    ]);
}

function getTaxReport($conn, $currentBranch) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    // Add one day to end date to include full day
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $query = "SELECT 
                FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(SUM(TotalAmount) * 0.12 / 1.12, 0) AS VatAmount
              FROM Sales
              WHERE SaleDate >= :start AND SaleDate < :end
              AND (Status != 'cancelled' OR Status IS NULL)
              AND Branch = :branch
              GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd')
              ORDER BY MIN(SaleDate) DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $tax = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalVat = 0;
    $totalSales = 0;
    foreach ($tax as $row) {
        $totalVat += $row['VatAmount'];
        $totalSales += $row['TotalSales'];
    }
    
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
    global $currentBranch;
    
    $startDate = $data['start_date'] ?? date('Y-m-01');
    $endDate = $data['end_date'] ?? date('Y-m-t');
    $groupBy = $data['group_by'] ?? 'day';
    
    // Add one day to end date to include full day
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $query = "SELECT 
                FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                ISNULL(AVG(TotalAmount), 0) AS AverageSale
              FROM Sales
              WHERE SaleDate >= :start AND SaleDate < :end
              AND (Status != 'cancelled' OR Status IS NULL)
              AND Branch = :branch
              GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd')
              ORDER BY MIN(SaleDate) ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
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

function exportReport($conn, $currentBranch) {
    $type = $_GET['type'] ?? 'sales';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    // Add one day to end date to include full day
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
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
                  WHERE s.SaleDate >= :start AND s.SaleDate < :end
                  AND s.Branch = :branch
                  ORDER BY s.SaleDate DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDatePlus);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "sales_report_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        
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
                  WHERE s.SaleDate >= :start AND s.SaleDate < :end
                  AND s.Branch = :branch
                  GROUP BY si.ProductCode, si.ProductName
                  ORDER BY SUM(si.Total) DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDatePlus);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "product_report_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Product Code', 'Product Name', 'Quantity Sold', 'Total Revenue']);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    } elseif ($type === 'payment') {
        $query = "SELECT 
                    PaymentMethod,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(TotalAmount), 0) AS TotalAmount
                  FROM Sales
                  WHERE SaleDate >= :start AND SaleDate < :end
                  AND (Status != 'cancelled' OR Status IS NULL)
                  AND Branch = :branch
                  GROUP BY PaymentMethod
                  ORDER BY SUM(TotalAmount) DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDatePlus);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "payment_report_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Payment Method', 'Transaction Count', 'Total Amount']);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
}
?>