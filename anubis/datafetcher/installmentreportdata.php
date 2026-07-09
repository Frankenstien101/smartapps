<?php
// api_installment_report.php - Backend API for Installment Reports with Branch Support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include '../DB/dbcon.php';

// Handle preflight requests
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

// Start session to get user branch
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
        case 'getInstallments':
            getInstallments($conn, $currentBranch, $userRole);
            break;
        case 'getInstallmentStats':
            getInstallmentStats($conn, $currentBranch, $userRole);
            break;
        case 'getInstallmentSummary':
            getInstallmentSummary($conn, $currentBranch, $userRole);
            break;
        case 'getCustomerSummary':
            getCustomerSummary($conn, $currentBranch, $userRole);
            break;
        case 'getStatusBreakdown':
            getStatusBreakdown($conn, $currentBranch, $userRole);
            break;
        case 'getInstallmentDetails':
            getInstallmentDetails($conn, $currentBranch, $userRole);
            break;
        case 'getPaymentHistory':
            getPaymentHistory($conn);
            break;
        case 'getOverdueInstallments':
            getOverdueInstallments($conn, $currentBranch, $userRole);
            break;
        case 'exportReport':
            exportReport($conn, $currentBranch, $userRole);
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
// MAIN INSTALLMENT FUNCTIONS WITH BRANCH FILTER
// ============================================

function getInstallments($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $status = $_GET['status'] ?? 'all';
    $branch = $_GET['branch'] ?? 'current';
    
    // Add one day to end date to include full day
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    // Branch filter logic
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND i.Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = ""; // No branch filter for admin
    } else {
        $branchFilter = "AND i.Branch = :branch";
    }
    
    $statusFilter = $status !== 'all' ? "AND i.Status = :status" : "";
    
    $query = "SELECT 
                i.InstallmentID, 
                i.InstallmentNo, 
                i.CustomerName, 
                i.CustomerPhone,
                i.ProductName, 
                i.ProductPrice, 
                i.DownPayment, 
                i.LoanAmount,
                i.InterestRate, 
                i.PenaltyRate,
                i.TotalInterest,
                i.TotalAmount, 
                i.Months, 
                i.MonthlyPayment,
                ISNULL(i.PaidAmount, 0) AS PaidAmount, 
                ISNULL(i.RemainingBalance, i.TotalAmount) AS RemainingBalance,
                i.Status,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate,
                FORMAT(i.CompletedDate, 'yyyy-MM-dd') AS CompletedDate,
                i.Notes, 
                i.CreatedBy, 
                FORMAT(i.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                i.Branch,
                (SELECT COUNT(*) FROM InstallmentPayments WHERE InstallmentID = i.InstallmentID AND Status = 'paid') AS PaymentsMade,
                (SELECT COUNT(*) FROM InstallmentPayments WHERE InstallmentID = i.InstallmentID) AS TotalPayments
              FROM Installments i
              WHERE i.StartDate >= :start AND i.StartDate < :end
              $branchFilter
              $statusFilter
              ORDER BY i.InstallmentID DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update display status
    foreach ($installments as &$inst) {
        if ($inst['Status'] === 'returned') {
            $inst['display_status'] = 'RETURNED';
        } elseif ($inst['RemainingBalance'] <= 0.01) {
            $inst['Status'] = 'completed';
            $inst['display_status'] = 'PAID';
        } elseif ($inst['Status'] === 'overdue') {
            $inst['display_status'] = 'OVERDUE';
        } elseif ($inst['Status'] === 'active') {
            $inst['display_status'] = 'ACTIVE';
        } else {
            $inst['display_status'] = strtoupper($inst['Status']);
        }
    }
    
    echo json_encode(['success' => true, 'data' => $installments, 'count' => count($installments)]);
}

function getInstallmentStats($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $branch = $_GET['branch'] ?? 'current';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    // Branch filter logic
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = "";
    } else {
        $branchFilter = "AND Branch = :branch";
    }
    
    $query = "SELECT 
                COUNT(*) AS TotalInstallments,
                SUM(CASE WHEN Status = 'active' AND Status != 'returned' THEN 1 ELSE 0 END) AS ActiveInstallments,
                SUM(CASE WHEN Status = 'completed' AND Status != 'returned' THEN 1 ELSE 0 END) AS CompletedInstallments,
                SUM(CASE WHEN Status = 'overdue' AND Status != 'returned' THEN 1 ELSE 0 END) AS OverdueInstallments,
                SUM(CASE WHEN Status = 'returned' THEN 1 ELSE 0 END) AS ReturnedInstallments,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN TotalAmount ELSE 0 END), 0) AS TotalLoanAmount,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN PaidAmount ELSE 0 END), 0) AS TotalPaidAmount,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN RemainingBalance ELSE 0 END), 0) AS TotalRemainingBalance,
                ISNULL(AVG(CASE WHEN Status != 'returned' THEN MonthlyPayment ELSE NULL END), 0) AS AvgMonthlyPayment
              FROM Installments
              WHERE StartDate >= :start AND StartDate < :end
              $branchFilter";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get overdue count
    $overdueQuery = "SELECT COUNT(*) AS OverdueCount 
                     FROM Installments 
                     WHERE Status = 'active' AND NextPaymentDate < GETDATE() 
                     AND Status != 'returned'
                     AND StartDate >= :start AND StartDate < :end
                     $branchFilter";
    
    $stmt = $conn->prepare($overdueQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $overdue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['OverdueCount'] = $overdue['OverdueCount'] ?? 0;
    $stats['ReturnedInstallments'] = $stats['ReturnedInstallments'] ?? 0;
    
    // Calculate collection rate
    $stats['CollectionRate'] = $stats['TotalLoanAmount'] > 0 
        ? round(($stats['TotalPaidAmount'] / $stats['TotalLoanAmount']) * 100, 2) 
        : 0;
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

// ============================================
// REPORT SPECIFIC FUNCTIONS WITH BRANCH FILTER
// ============================================

function getInstallmentSummary($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $branch = $_GET['branch'] ?? 'current';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = "";
    } else {
        $branchFilter = "AND Branch = :branch";
    }
    
    $query = "SELECT 
                COUNT(*) AS TotalInstallments,
                ISNULL(SUM(TotalAmount), 0) AS TotalLoanAmount,
                ISNULL(SUM(PaidAmount), 0) AS TotalPaidAmount,
                ISNULL(SUM(RemainingBalance), 0) AS TotalRemainingBalance,
                ISNULL(AVG(MonthlyPayment), 0) AS AvgMonthlyPayment,
                ISNULL(SUM(TotalInterest), 0) AS TotalInterestEarned,
                ISNULL(AVG(InterestRate), 0) AS AvgInterestRate,
                MIN(TotalAmount) AS MinLoanAmount,
                MAX(TotalAmount) AS MaxLoanAmount,
                MIN(Months) AS MinMonths,
                MAX(Months) AS MaxMonths
              FROM Installments
              WHERE StartDate >= :start AND StartDate < :end
              AND Status != 'returned'
              $branchFilter";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get monthly breakdown
    $monthlyQuery = "SELECT 
                        FORMAT(StartDate, 'yyyy-MM') AS Month,
                        COUNT(*) AS InstallmentCount,
                        ISNULL(SUM(TotalAmount), 0) AS MonthlyLoanAmount,
                        ISNULL(SUM(PaidAmount), 0) AS MonthlyPaidAmount
                      FROM Installments
                      WHERE StartDate >= :start AND StartDate < :end
                      AND Status != 'returned'
                      $branchFilter
                      GROUP BY FORMAT(StartDate, 'yyyy-MM')
                      ORDER BY Month ASC";
    
    $stmt = $conn->prepare($monthlyQuery);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'monthly_breakdown' => $monthly
    ]);
}

function getCustomerSummary($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $branch = $_GET['branch'] ?? 'current';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = "";
    } else {
        $branchFilter = "AND Branch = :branch";
    }
    
    $query = "SELECT 
                CustomerName,
                CustomerPhone,
                Branch,
                COUNT(*) AS InstallmentCount,
                ISNULL(SUM(TotalAmount), 0) AS TotalLoanAmount,
                ISNULL(SUM(PaidAmount), 0) AS TotalPaidAmount,
                ISNULL(SUM(RemainingBalance), 0) AS TotalRemainingBalance,
                MAX(CASE WHEN Status = 'overdue' THEN 1 ELSE 0 END) AS HasOverdue,
                MAX(CASE WHEN Status = 'active' THEN 1 ELSE 0 END) AS HasActive,
                MAX(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) AS HasCompleted
              FROM Installments
              WHERE StartDate >= :start AND StartDate < :end
              $branchFilter
              GROUP BY CustomerName, CustomerPhone, Branch
              ORDER BY TotalLoanAmount DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine overall status for each customer
    foreach ($customers as &$customer) {
        if ($customer['HasOverdue'] == 1) {
            $customer['OverallStatus'] = 'overdue';
        } elseif ($customer['HasActive'] == 1) {
            $customer['OverallStatus'] = 'active';
        } elseif ($customer['HasCompleted'] == 1) {
            $customer['OverallStatus'] = 'completed';
        } else {
            $customer['OverallStatus'] = 'unknown';
        }
        $customer['CollectionRate'] = $customer['TotalLoanAmount'] > 0 
            ? round(($customer['TotalPaidAmount'] / $customer['TotalLoanAmount']) * 100, 2) 
            : 0;
    }
    
    echo json_encode(['success' => true, 'data' => $customers]);
}

function getStatusBreakdown($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $branch = $_GET['branch'] ?? 'current';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = "";
    } else {
        $branchFilter = "AND Branch = :branch";
    }
    
    $query = "SELECT 
                Status,
                COUNT(*) AS Count,
                ISNULL(SUM(TotalAmount), 0) AS TotalLoanAmount,
                ISNULL(SUM(PaidAmount), 0) AS TotalPaidAmount,
                ISNULL(SUM(RemainingBalance), 0) AS TotalRemainingBalance,
                ISNULL(AVG(MonthlyPayment), 0) AS AvgMonthlyPayment,
                ISNULL(AVG(InterestRate), 0) AS AvgInterestRate
              FROM Installments
              WHERE StartDate >= :start AND StartDate < :end
              $branchFilter
              GROUP BY Status
              ORDER BY 
                CASE Status 
                    WHEN 'active' THEN 1 
                    WHEN 'overdue' THEN 2 
                    WHEN 'completed' THEN 3 
                    WHEN 'returned' THEN 4 
                    ELSE 5 
                END";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalAll = array_sum(array_column($breakdown, 'TotalLoanAmount'));
    foreach ($breakdown as &$status) {
        $status['Percentage'] = $totalAll > 0 ? round(($status['TotalLoanAmount'] / $totalAll) * 100, 2) : 0;
    }
    
    echo json_encode(['success' => true, 'data' => $breakdown, 'total_all' => $totalAll]);
}

function getInstallmentDetails($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $status = $_GET['status'] ?? 'all';
    $branch = $_GET['branch'] ?? 'current';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND i.Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = "";
    } else {
        $branchFilter = "AND i.Branch = :branch";
    }
    
    $statusFilter = $status !== 'all' ? "AND i.Status = :status" : "";
    
    $query = "SELECT 
                i.InstallmentID,
                i.InstallmentNo,
                i.CustomerName,
                i.CustomerPhone,
                i.ProductName,
                i.TotalAmount,
                i.MonthlyPayment,
                i.PaidAmount,
                i.RemainingBalance,
                i.Status,
                i.InterestRate,
                i.TotalInterest,
                i.Months,
                i.DownPayment,
                i.Branch,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate,
                FORMAT(i.CompletedDate, 'yyyy-MM-dd') AS CompletedDate,
                (SELECT COUNT(*) FROM InstallmentPayments WHERE InstallmentID = i.InstallmentID AND Status = 'paid') AS PaymentsMade,
                (SELECT COUNT(*) FROM InstallmentPayments WHERE InstallmentID = i.InstallmentID) AS TotalPayments
              FROM Installments i
              WHERE i.StartDate >= :start AND i.StartDate < :end
              $branchFilter
              $statusFilter
              ORDER BY i.StartDate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $details, 'count' => count($details)]);
}

function getPaymentHistory($conn) {
    $installmentId = $_GET['installment_id'] ?? 0;
    
    if (!$installmentId) {
        echo json_encode(['success' => false, 'message' => 'Installment ID required']);
        return;
    }
    
    $query = "SELECT 
                PaymentID,
                PaymentNo,
                FORMAT(DueDate, 'yyyy-MM-dd') AS DueDate,
                Amount,
                PaidAmount,
                Status,
                PenaltyAmount,
                PenaltyPaid,
                DaysOverdue,
                ReferenceNo,
                Notes,
                FORMAT(PaymentDate, 'yyyy-MM-dd HH:mm') AS PaymentDate
              FROM InstallmentPayments
              WHERE InstallmentID = :id
              ORDER BY PaymentNo";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $installmentId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $payments]);
}

function getOverdueInstallments($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $branch = $_GET['branch'] ?? 'current';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND i.Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = "";
    } else {
        $branchFilter = "AND i.Branch = :branch";
    }
    
    $query = "SELECT 
                i.InstallmentID,
                i.InstallmentNo,
                i.CustomerName,
                i.CustomerPhone,
                i.ProductName,
                i.TotalAmount,
                i.MonthlyPayment,
                i.PaidAmount,
                i.RemainingBalance,
                i.Branch,
                FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate,
                DATEDIFF(DAY, i.NextPaymentDate, GETDATE()) AS DaysOverdue,
                (SELECT COUNT(*) FROM InstallmentPayments WHERE InstallmentID = i.InstallmentID AND Status = 'pending' AND DueDate < GETDATE()) AS OverduePaymentsCount
              FROM Installments i
              WHERE i.Status = 'active' 
                AND i.NextPaymentDate < GETDATE()
                AND i.StartDate >= :start AND i.StartDate < :end
                $branchFilter
              ORDER BY i.NextPaymentDate ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDatePlus);
    if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $overdue, 'count' => count($overdue)]);
}

function generateCustomReport($conn, $data) {
    $startDate = $data['start_date'] ?? date('Y-m-01');
    $endDate = $data['end_date'] ?? date('Y-m-t');
    $groupBy = $data['group_by'] ?? 'month';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    if ($groupBy === 'month') {
        $query = "SELECT 
                    FORMAT(StartDate, 'yyyy-MM') AS Period,
                    COUNT(*) AS InstallmentCount,
                    ISNULL(SUM(TotalAmount), 0) AS TotalLoanAmount,
                    ISNULL(SUM(PaidAmount), 0) AS TotalPaidAmount,
                    ISNULL(SUM(RemainingBalance), 0) AS TotalRemainingBalance
                  FROM Installments
                  WHERE StartDate >= :start AND StartDate < :end
                  GROUP BY FORMAT(StartDate, 'yyyy-MM')
                  ORDER BY Period ASC";
    } else {
        $query = "SELECT 
                    FORMAT(StartDate, 'yyyy') AS Period,
                    COUNT(*) AS InstallmentCount,
                    ISNULL(SUM(TotalAmount), 0) AS TotalLoanAmount,
                    ISNULL(SUM(PaidAmount), 0) AS TotalPaidAmount,
                    ISNULL(SUM(RemainingBalance), 0) AS TotalRemainingBalance
                  FROM Installments
                  WHERE StartDate >= :start AND StartDate < :end
                  GROUP BY FORMAT(StartDate, 'yyyy')
                  ORDER BY Period ASC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':start' => $startDate, ':end' => $endDatePlus]);
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

function exportReport($conn, $currentBranch, $userRole) {
    $type = $_GET['type'] ?? 'summary';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    $branch = $_GET['branch'] ?? 'current';
    $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
    
    $branchFilter = "";
    if ($branch === 'current') {
        $branchFilter = "AND Branch = :branch";
    } elseif ($branch === 'all' && $userRole === 'admin') {
        $branchFilter = "";
    } else {
        $branchFilter = "AND Branch = :branch";
    }
    
    if ($type === 'summary') {
        $query = "SELECT 
                    i.InstallmentNo,
                    i.CustomerName,
                    i.CustomerPhone,
                    i.ProductName,
                    i.TotalAmount,
                    i.MonthlyPayment,
                    i.PaidAmount,
                    i.RemainingBalance,
                    i.Status,
                    i.Branch,
                    FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                    FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate
                  FROM Installments i
                  WHERE i.StartDate >= :start AND i.StartDate < :end
                  $branchFilter
                  ORDER BY i.StartDate DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDatePlus);
        if ($branch === 'current' || ($branch !== 'all' && $userRole !== 'admin')) {
            $stmt->bindParam(':branch', $currentBranch);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "installment_report_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Installment No', 'Customer Name', 'Phone', 'Product', 'Total Amount', 'Monthly Payment', 'Paid Amount', 'Remaining Balance', 'Status', 'Branch', 'Start Date', 'Next Due Date']);
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['InstallmentNo'],
                $row['CustomerName'],
                $row['CustomerPhone'],
                $row['ProductName'],
                number_format($row['TotalAmount'], 2),
                number_format($row['MonthlyPayment'], 2),
                number_format($row['PaidAmount'], 2),
                number_format($row['RemainingBalance'], 2),
                $row['Status'],
                $row['Branch'],
                $row['StartDate'],
                $row['NextPaymentDate']
            ]);
        }
        
        fclose($output);
        exit();
    }
}
?>