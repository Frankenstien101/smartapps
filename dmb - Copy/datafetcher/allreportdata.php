<?php
// allreportdata.php - Data Fetcher for Comprehensive Report Export
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../DB/dbcon.php';

session_start();
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';

$action = $_GET['action'] ?? '';

try {
    if ($action === 'exportReport') {
        exportReport($conn);
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}

function exportReport($conn) {
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $branch = $_GET['branch'] ?? 'all';
    $type = $_GET['export_type'] ?? 'all';
    
    // Get Sales
    $salesQuery = "SELECT ReceiptNo, CustomerName, TotalAmount, PaymentMethod, SaleDate, CreatedBy, Branch 
                   FROM Sales 
                   WHERE CAST(SaleDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)
                   AND Status != 'cancelled'";
    if ($branch != 'all') $salesQuery .= " AND Branch = :branch";
    $salesQuery .= " ORDER BY SaleDate DESC";
    
    $stmt = $conn->prepare($salesQuery);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    if ($branch != 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Deposits
    $depositsQuery = "SELECT DepositRef, DepositDate, DepositType, Amount, BankName, Notes, CreatedBy, Branch 
                      FROM Deposits 
                      WHERE CAST(DepositDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)";
    if ($branch != 'all') $depositsQuery .= " AND Branch = :branch";
    $depositsQuery .= " ORDER BY DepositDate DESC";
    
    $stmt = $conn->prepare($depositsQuery);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    if ($branch != 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Expenses
    $expensesQuery = "SELECT e.ExpenseDate, e.Amount as PlannedAmount, e.Amount as ActualAmount, e.Notes, e.CreatedBy, e.Branch,
                             (SELECT TypeName FROM ExpenseTypes WHERE ExpenseTypeID = e.ExpenseTypeID) AS TypeName
                      FROM Expenses e
                      WHERE CAST(e.ExpenseDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)
                      AND e.Status = 'active'";
    if ($branch != 'all') $expensesQuery .= " AND e.Branch = :branch";
    $expensesQuery .= " ORDER BY e.ExpenseDate DESC";
    
    $stmt = $conn->prepare($expensesQuery);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    if ($branch != 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get EOD
    $eodQuery = "SELECT EodDate, TotalSales, CashReceived, TotalDeposits, TotalExpenses, 
                        ExpectedCashOnHand, TotalCashCounted, CashDifference, Status, Branch
                 FROM EndOfDay
                 WHERE CAST(EodDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)";
    if ($branch != 'all') $eodQuery .= " AND Branch = :branch";
    $eodQuery .= " ORDER BY EodDate DESC";
    
    $stmt = $conn->prepare($eodQuery);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    if ($branch != 'all') $stmt->bindParam(':branch', $branch);
    $stmt->execute();
    $eods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalSales = array_sum(array_column($sales, 'TotalAmount'));
    $totalDeposits = array_sum(array_column($deposits, 'Amount'));
    $totalExpenses = array_sum(array_column($expenses, 'ActualAmount'));
    $totalEodCount = count($eods);
    
    // Clear buffers and set headers
    while (ob_get_level()) ob_end_clean();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_Report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");
    
    switch ($type) {
        case 'sales':
            fputcsv($output, ['SALES REPORT']);
            fputcsv($output, ['Date', 'Receipt #', 'Customer', 'Amount', 'Payment Method', 'Branch', 'Created By']);
            foreach ($sales as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['SaleDate'])),
                    $row['ReceiptNo'],
                    $row['CustomerName'],
                    number_format($row['TotalAmount'], 2),
                    $row['PaymentMethod'],
                    $row['Branch'],
                    $row['CreatedBy']
                ]);
            }
            break;
            
        case 'deposits':
            fputcsv($output, ['DEPOSITS REPORT']);
            fputcsv($output, ['Date', 'Reference #', 'Type', 'Amount', 'Bank', 'Notes', 'Branch', 'Created By']);
            foreach ($deposits as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['DepositDate'])),
                    $row['DepositRef'],
                    $row['DepositType'],
                    number_format($row['Amount'], 2),
                    $row['BankName'] ?? '',
                    $row['Notes'] ?? '',
                    $row['Branch'],
                    $row['CreatedBy']
                ]);
            }
            break;
            
        case 'expenses':
            fputcsv($output, ['EXPENSES REPORT']);
            fputcsv($output, ['Date', 'Type', 'Planned', 'Actual', 'Variance', 'Notes', 'Branch', 'Created By']);
            foreach ($expenses as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['ExpenseDate'])),
                    $row['TypeName'] ?? 'Unknown',
                    number_format($row['PlannedAmount'], 2),
                    number_format($row['ActualAmount'], 2),
                    number_format($row['PlannedAmount'] - $row['ActualAmount'], 2),
                    $row['Notes'] ?? '',
                    $row['Branch'],
                    $row['CreatedBy']
                ]);
            }
            break;
            
        case 'eod':
            fputcsv($output, ['END OF DAY REPORTS']);
            fputcsv($output, ['Date', 'Total Sales', 'Cash Received', 'Deposits', 'Expenses', 'Expected Cash', 'Cash Counted', 'Difference', 'Status', 'Branch']);
            foreach ($eods as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['EodDate'])),
                    number_format($row['TotalSales'], 2),
                    number_format($row['CashReceived'], 2),
                    number_format($row['TotalDeposits'], 2),
                    number_format($row['TotalExpenses'], 2),
                    number_format($row['ExpectedCashOnHand'], 2),
                    number_format($row['TotalCashCounted'], 2),
                    number_format($row['CashDifference'], 2),
                    $row['Status'],
                    $row['Branch']
                ]);
            }
            break;
            
        default:
            // Full Report
            fputcsv($output, ['COMPREHENSIVE REPORT']);
            fputcsv($output, ['Branch: ' . ($branch == 'all' ? 'ALL BRANCHES' : $branch)]);
            fputcsv($output, ['Date Range: ' . $dateFrom . ' to ' . $dateTo]);
            fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            fputcsv($output, ['SUMMARY']);
            fputcsv($output, ['Total Sales', number_format($totalSales, 2)]);
            fputcsv($output, ['Total Deposits', number_format($totalDeposits, 2)]);
            fputcsv($output, ['Total Expenses', number_format($totalExpenses, 2)]);
            fputcsv($output, ['EOD Reports', $totalEodCount]);
            fputcsv($output, []);
            
            fputcsv($output, ['SALES REPORT']);
            fputcsv($output, ['Date', 'Receipt #', 'Customer', 'Amount', 'Payment Method', 'Branch', 'Created By']);
            foreach ($sales as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['SaleDate'])),
                    $row['ReceiptNo'],
                    $row['CustomerName'],
                    number_format($row['TotalAmount'], 2),
                    $row['PaymentMethod'],
                    $row['Branch'],
                    $row['CreatedBy']
                ]);
            }
            fputcsv($output, []);
            
            fputcsv($output, ['DEPOSITS REPORT']);
            fputcsv($output, ['Date', 'Reference #', 'Type', 'Amount', 'Bank', 'Notes', 'Branch', 'Created By']);
            foreach ($deposits as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['DepositDate'])),
                    $row['DepositRef'],
                    $row['DepositType'],
                    number_format($row['Amount'], 2),
                    $row['BankName'] ?? '',
                    $row['Notes'] ?? '',
                    $row['Branch'],
                    $row['CreatedBy']
                ]);
            }
            fputcsv($output, []);
            
            fputcsv($output, ['EXPENSES REPORT']);
            fputcsv($output, ['Date', 'Type', 'Planned', 'Actual', 'Variance', 'Notes', 'Branch', 'Created By']);
            foreach ($expenses as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['ExpenseDate'])),
                    $row['TypeName'] ?? 'Unknown',
                    number_format($row['PlannedAmount'], 2),
                    number_format($row['ActualAmount'], 2),
                    number_format($row['PlannedAmount'] - $row['ActualAmount'], 2),
                    $row['Notes'] ?? '',
                    $row['Branch'],
                    $row['CreatedBy']
                ]);
            }
            fputcsv($output, []);
            
            fputcsv($output, ['END OF DAY REPORTS']);
            fputcsv($output, ['Date', 'Total Sales', 'Cash Received', 'Deposits', 'Expenses', 'Expected Cash', 'Cash Counted', 'Difference', 'Status', 'Branch']);
            foreach ($eods as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['EodDate'])),
                    number_format($row['TotalSales'], 2),
                    number_format($row['CashReceived'], 2),
                    number_format($row['TotalDeposits'], 2),
                    number_format($row['TotalExpenses'], 2),
                    number_format($row['ExpectedCashOnHand'], 2),
                    number_format($row['TotalCashCounted'], 2),
                    number_format($row['CashDifference'], 2),
                    $row['Status'],
                    $row['Branch']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit();
}
?>