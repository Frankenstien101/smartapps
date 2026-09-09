<?php
// api_eod.php - End of Day API

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../DB/dbcon.php';

session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
$currentRole = $_SESSION['Role'] ?? $_SESSION['role'] ?? 'staff';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $action);
            break;
        case 'POST':
            handlePostRequest($conn, $action, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid request method']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($conn, $action) {
    global $currentBranch;
    
    switch ($action) {
        case 'getDailySales':
            getDailySales($conn, $currentBranch);
            break;
        case 'getEodReport':
            getEodReport($conn, $currentBranch);
            break;
        case 'getHistory':
            getEodHistory($conn, $currentBranch);
            break;
        case 'getSodAmount':
            getSodAmount($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'saveEod':
            saveEod($conn, $data, $currentUser, $currentBranch);
            break;
        case 'submitEod':
            submitEod($conn, $data, $currentUser, $currentBranch);
            break;
        case 'saveSod':
            saveSod($conn, $data, $currentUser, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function isAdmin() {
    global $currentRole;
    return strtolower($currentRole) === 'admin';
}

function getSodAmount($conn, $currentBranch) {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    $query = "SELECT ISNULL(SodAmount, 0) AS SodAmount, Notes AS SodNotes
              FROM StartOfDay
              WHERE CAST(SodDate AS DATE) = CAST(:date AS DATE)
              AND Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'has_sod' => $result ? true : false,
            'sod' => $result ? [
                'SodAmount' => floatval($result['SodAmount']),
                'SodNotes' => $result['SodNotes'] ?? ''
            ] : null,
            'date' => $date
        ]
    ]);
}

function saveSod($conn, $data, $currentUser, $currentBranch) {
    $sodDate = $data['date'] ?? date('Y-m-d');
    $sodAmount = floatval($data['sod_amount'] ?? 0);
    $notes = $data['notes'] ?? '';
    
    $checkQuery = "SELECT SodID FROM StartOfDay 
                   WHERE CAST(SodDate AS DATE) = CAST(:date AS DATE) 
                   AND Branch = :branch";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bindParam(':date', $sodDate);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $query = "UPDATE StartOfDay 
                  SET SodAmount = :amount, Notes = :notes, UpdatedBy = :user, UpdatedAt = GETDATE()
                  WHERE SodID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $existing['SodID']);
        $stmt->bindParam(':amount', $sodAmount);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':user', $currentUser);
        $stmt->execute();
    } else {
        $query = "INSERT INTO StartOfDay (SodDate, Branch, SodAmount, Notes, CreatedBy, CreatedAt)
                  VALUES (:date, :branch, :amount, :notes, :user, GETDATE())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':date', $sodDate);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->bindParam(':amount', $sodAmount);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':user', $currentUser);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'SOD saved']);
}

function getDailySales($conn, $currentBranch) {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    $query = "SELECT 
                ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'cash' THEN TotalAmount ELSE 0 END), 0) AS CashReceived,
                ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'card' THEN TotalAmount ELSE 0 END), 0) AS CardReceived,
                ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'gcash' THEN TotalAmount ELSE 0 END), 0) AS GcashReceived
              FROM Sales
              WHERE CAST(SaleDate AS DATE) = CAST(:date AS DATE)
              AND Branch = :branch
              AND Status != 'cancelled'";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sodQuery = "SELECT ISNULL(SodAmount, 0) AS SodAmount FROM StartOfDay
                 WHERE CAST(SodDate AS DATE) = CAST(:date AS DATE) AND Branch = :branch";
    $stmt = $conn->prepare($sodQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sodResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $sodAmount = $sodResult['SodAmount'] ?? 0;
    
    $depositQuery = "SELECT ISNULL(SUM(Amount), 0) AS TotalDeposits, COUNT(*) AS DepositCount
                     FROM Deposits
                     WHERE CAST(DepositDate AS DATE) = CAST(:date AS DATE) AND Branch = :branch";
    $stmt = $conn->prepare($depositQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $depositResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalDeposits = $depositResult['TotalDeposits'] ?? 0;
    $depositCount = $depositResult['DepositCount'] ?? 0;
    
    $expenseQuery = "SELECT ISNULL(SUM(Amount), 0) AS TotalExpenses, COUNT(*) AS ExpenseCount
                     FROM Expenses
                     WHERE CAST(ExpenseDate AS DATE) = CAST(:date AS DATE)
                     AND Branch = :branch AND Status = 'active'";
    $stmt = $conn->prepare($expenseQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $expenseResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalExpenses = floatval($expenseResult['TotalExpenses'] ?? 0);
    $expenseCount = intval($expenseResult['ExpenseCount'] ?? 0);
    
    $cashReceived = floatval($result['CashReceived'] ?? 0);
    $expectedCashOnHand = $sodAmount + $cashReceived - $totalDeposits - $totalExpenses;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'TotalSales' => floatval($result['TotalSales'] ?? 0),
                'TransactionCount' => intval($result['TransactionCount'] ?? 0),
                'CashReceived' => $cashReceived,
                'CardReceived' => floatval($result['CardReceived'] ?? 0),
                'GcashReceived' => floatval($result['GcashReceived'] ?? 0),
                'SodAmount' => $sodAmount,
                'TotalDeposits' => floatval($totalDeposits),
                'DepositCount' => intval($depositCount),
                'TotalExpenses' => $totalExpenses,
                'ExpenseCount' => $expenseCount,
                'ExpectedCashOnHand' => $expectedCashOnHand
            ],
            'date' => $date
        ]
    ]);
}

function getEodReport($conn, $currentBranch) {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    $sodQuery = "SELECT ISNULL(SodAmount, 0) AS SodAmount, Notes AS SodNotes
                 FROM StartOfDay
                 WHERE CAST(SodDate AS DATE) = CAST(:date AS DATE) AND Branch = :branch";
    $stmt = $conn->prepare($sodQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sodResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $sodAmount = $sodResult['SodAmount'] ?? 0;
    $sodNotes = $sodResult['SodNotes'] ?? '';
    
    $checkQuery = "SELECT EodID FROM EndOfDay 
                   WHERE CAST(EodDate AS DATE) = CAST(:date AS DATE) AND Branch = :branch";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $salesQuery = "SELECT 
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'cash' THEN TotalAmount ELSE 0 END), 0) AS CashReceived,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'card' THEN TotalAmount ELSE 0 END), 0) AS CardReceived,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'gcash' THEN TotalAmount ELSE 0 END), 0) AS GcashReceived
                  FROM Sales
                  WHERE CAST(SaleDate AS DATE) = CAST(:date AS DATE)
                  AND Branch = :branch AND Status != 'cancelled'";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $depositQuery = "SELECT ISNULL(SUM(Amount), 0) AS TotalDeposits, COUNT(*) AS DepositCount
                     FROM Deposits
                     WHERE CAST(DepositDate AS DATE) = CAST(:date AS DATE) AND Branch = :branch";
    $stmt = $conn->prepare($depositQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $depositResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalDeposits = floatval($depositResult['TotalDeposits'] ?? 0);
    $depositCount = intval($depositResult['DepositCount'] ?? 0);
    
    $expenseQuery = "SELECT ISNULL(SUM(Amount), 0) AS TotalExpenses, COUNT(*) AS ExpenseCount
                     FROM Expenses
                     WHERE CAST(ExpenseDate AS DATE) = CAST(:date AS DATE)
                     AND Branch = :branch AND Status = 'active'";
    $stmt = $conn->prepare($expenseQuery);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $expenseResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalExpenses = floatval($expenseResult['TotalExpenses'] ?? 0);
    $expenseCount = intval($expenseResult['ExpenseCount'] ?? 0);
    
    $cashReceived = floatval($sales['CashReceived'] ?? 0);
    $expectedCashOnHand = $sodAmount + $cashReceived - $totalDeposits - $totalExpenses;
    
    $eodData = null;
    if ($existing) {
        $eodQuery = "SELECT * FROM EndOfDay WHERE EodID = :id";
        $stmt = $conn->prepare($eodQuery);
        $stmt->bindParam(':id', $existing['EodID']);
        $stmt->execute();
        $eodData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($eodData) {
            $eodData['Bills'] = json_decode($eodData['Bills'], true) ?: [];
            $eodData['Coins'] = json_decode($eodData['Coins'], true) ?: [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'sales' => $sales,
            'sod' => ['SodAmount' => $sodAmount, 'SodNotes' => $sodNotes],
            'deposits' => ['TotalDeposits' => $totalDeposits, 'DepositCount' => $depositCount],
            'expenses' => ['TotalExpenses' => $totalExpenses, 'ExpenseCount' => $expenseCount],
            'eod' => $eodData,
            'date' => $date,
            'has_eod' => $existing ? true : false,
            'expected_cash_on_hand' => $expectedCashOnHand
        ]
    ]);
}

function saveEod($conn, $data, $currentUser, $currentBranch) {
    $eodDate = $data['date'] ?? date('Y-m-d');
    $eodId = $data['eod_id'] ?? null;
    $notes = $data['notes'] ?? '';
    $denominations = $data['denominations'] ?? [];
    $paymentBreakdown = $data['payment_breakdown'] ?? [];
    $sodAmount = floatval($data['sod_amount'] ?? 0);
    $totalDeposits = floatval($data['total_deposits'] ?? 0);
    $depositCount = intval($data['deposit_count'] ?? 0);
    $totalExpenses = floatval($data['total_expenses'] ?? 0);
    $expenseCount = intval($data['expense_count'] ?? 0);
    
    $salesQuery = "SELECT 
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'cash' THEN TotalAmount ELSE 0 END), 0) AS CashReceived,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'card' THEN TotalAmount ELSE 0 END), 0) AS CardReceived,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'gcash' THEN TotalAmount ELSE 0 END), 0) AS GcashReceived
                  FROM Sales
                  WHERE CAST(SaleDate AS DATE) = CAST(:date AS DATE)
                  AND Branch = :branch AND Status != 'cancelled'";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bindParam(':date', $eodDate);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $cashReceived = $paymentBreakdown['cash'] ?? $sales['CashReceived'] ?? 0;
    $cardReceived = $paymentBreakdown['card'] ?? $sales['CardReceived'] ?? 0;
    $gcashReceived = $paymentBreakdown['gcash'] ?? $sales['GcashReceived'] ?? 0;
    $totalSales = $sales['TotalSales'] ?? ($cashReceived + $cardReceived + $gcashReceived);
    $transactionCount = $sales['TransactionCount'] ?? 0;
    
    if ($sodAmount == 0) {
        $sodQuery = "SELECT ISNULL(SodAmount, 0) AS SodAmount FROM StartOfDay
                     WHERE CAST(SodDate AS DATE) = CAST(:date AS DATE) AND Branch = :branch";
        $stmt = $conn->prepare($sodQuery);
        $stmt->bindParam(':date', $eodDate);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->execute();
        $sodResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $sodAmount = $sodResult['SodAmount'] ?? 0;
    }
    
    $expectedCashOnHand = $sodAmount + $cashReceived - $totalDeposits - $totalExpenses;
    
    $totalBills = 0;
    $totalCoins = 0;
    $bills = [];
    $coins = [];
    
    $billDenoms = [1000, 500, 200, 100, 50, 20];
    $coinDenoms = [10, 5, 1, 0.25, 0.10, 0.05];
    
    foreach ($billDenoms as $denom) {
        $key = "bill_{$denom}";
        $quantity = isset($denominations[$key]) ? intval($denominations[$key]) : 0;
        if ($quantity > 0) {
            $amount = $denom * $quantity;
            $totalBills += $amount;
            $bills[] = ['denomination' => $denom, 'quantity' => $quantity, 'amount' => $amount];
        }
    }
    
    foreach ($coinDenoms as $denom) {
        $key = "coin_{$denom}";
        $quantity = isset($denominations[$key]) ? intval($denominations[$key]) : 0;
        if ($quantity > 0) {
            $amount = $denom * $quantity;
            $totalCoins += $amount;
            $coins[] = ['denomination' => $denom, 'quantity' => $quantity, 'amount' => $amount];
        }
    }
    
    $totalCashCounted = $totalBills + $totalCoins;
    $cashDifference = $totalCashCounted - $expectedCashOnHand;
    
    $billsJson = json_encode($bills);
    $coinsJson = json_encode($coins);
    
    if ($eodId) {
        $statusQuery = "SELECT Status FROM EndOfDay WHERE EodID = :id";
        $statusStmt = $conn->prepare($statusQuery);
        $statusStmt->bindParam(':id', $eodId);
        $statusStmt->execute();
        $row = $statusStmt->fetch(PDO::FETCH_ASSOC);
        if ($row && strtolower($row['Status']) === 'submitted' && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Only admin can edit a submitted report.']);
            return;
        }

        $query = "UPDATE EndOfDay 
                  SET TotalSales = :total_sales, TransactionCount = :trans_count,
                      CashReceived = :cash_received, CardReceived = :card_received, GcashReceived = :gcash_received,
                      SodAmount = :sod_amount, TotalDeposits = :total_deposits, DepositCount = :deposit_count,
                      TotalExpenses = :total_expenses, ExpenseCount = :expense_count,
                      ExpectedCashOnHand = :expected_cash_on_hand, CashOnHand = :cash_on_hand,
                      TotalBills = :total_bills, TotalCoins = :total_coins,
                      TotalCashCounted = :total_cash_counted, CashDifference = :cash_difference,
                      Bills = :bills, Coins = :coins, Notes = :notes,
                      Status = 'draft', UpdatedBy = :user, UpdatedAt = GETDATE()
                  WHERE EodID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $eodId);
    } else {
        $query = "INSERT INTO EndOfDay 
                  (EodDate, Branch, TotalSales, TransactionCount, 
                   CashReceived, CardReceived, GcashReceived,
                   SodAmount, TotalDeposits, DepositCount,
                   TotalExpenses, ExpenseCount,
                   ExpectedCashOnHand, CashOnHand,
                   TotalBills, TotalCoins, TotalCashCounted, CashDifference,
                   Bills, Coins, Notes, Status, CreatedBy, CreatedAt)
                  VALUES 
                  (:date, :branch, :total_sales, :trans_count,
                   :cash_received, :card_received, :gcash_received,
                   :sod_amount, :total_deposits, :deposit_count,
                   :total_expenses, :expense_count,
                   :expected_cash_on_hand, :cash_on_hand,
                   :total_bills, :total_coins, :total_cash_counted, :cash_difference,
                   :bills, :coins, :notes, 'draft', :user, GETDATE())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':date', $eodDate);
        $stmt->bindParam(':branch', $currentBranch);
    }
    
    $stmt->bindParam(':total_sales', $totalSales);
    $stmt->bindParam(':trans_count', $transactionCount);
    $stmt->bindParam(':cash_received', $cashReceived);
    $stmt->bindParam(':card_received', $cardReceived);
    $stmt->bindParam(':gcash_received', $gcashReceived);
    $stmt->bindParam(':sod_amount', $sodAmount);
    $stmt->bindParam(':total_deposits', $totalDeposits);
    $stmt->bindParam(':deposit_count', $depositCount);
    $stmt->bindParam(':total_expenses', $totalExpenses);
    $stmt->bindParam(':expense_count', $expenseCount);
    $stmt->bindParam(':expected_cash_on_hand', $expectedCashOnHand);
    $stmt->bindParam(':cash_on_hand', $expectedCashOnHand);
    $stmt->bindParam(':total_bills', $totalBills);
    $stmt->bindParam(':total_coins', $totalCoins);
    $stmt->bindParam(':total_cash_counted', $totalCashCounted);
    $stmt->bindParam(':cash_difference', $cashDifference);
    $stmt->bindParam(':bills', $billsJson);
    $stmt->bindParam(':coins', $coinsJson);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':user', $currentUser);
    $stmt->execute();
    
    $newEodId = $eodId ? $eodId : $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'EOD saved',
        'eod_id' => $newEodId
    ]);
}

function submitEod($conn, $data, $currentUser, $currentBranch) {
    // GET VALUES - FORCE ALL TO 0
    $eodDate = isset($data['date']) ? $data['date'] : date('Y-m-d');
    $eodId = isset($data['eod_id']) ? $data['eod_id'] : null;
    $notes = isset($data['notes']) ? $data['notes'] : '';
    $denominations = isset($data['denominations']) ? $data['denominations'] : [];
    $paymentBreakdown = isset($data['payment_breakdown']) ? $data['payment_breakdown'] : [];
    
    $sodAmount = isset($data['sod_amount']) ? floatval($data['sod_amount']) : 0;
    $totalDeposits = isset($data['total_deposits']) ? floatval($data['total_deposits']) : 0;
    $depositCount = isset($data['deposit_count']) ? intval($data['deposit_count']) : 0;
    $totalExpenses = isset($data['total_expenses']) ? floatval($data['total_expenses']) : 0;
    $expenseCount = isset($data['expense_count']) ? intval($data['expense_count']) : 0;
    
    // GET SALES
    $salesQuery = "SELECT 
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'cash' THEN TotalAmount ELSE 0 END), 0) AS CashReceived,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'card' THEN TotalAmount ELSE 0 END), 0) AS CardReceived,
                    ISNULL(SUM(CASE WHEN LOWER(PaymentMethod) = 'gcash' THEN TotalAmount ELSE 0 END), 0) AS GcashReceived
                  FROM Sales
                  WHERE CAST(SaleDate AS DATE) = CAST(:date AS DATE)
                  AND Branch = :branch AND Status != 'cancelled'";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bindParam(':date', $eodDate);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $cashReceived = isset($paymentBreakdown['cash']) ? floatval($paymentBreakdown['cash']) : (isset($sales['CashReceived']) ? floatval($sales['CashReceived']) : 0);
    $cardReceived = isset($paymentBreakdown['card']) ? floatval($paymentBreakdown['card']) : (isset($sales['CardReceived']) ? floatval($sales['CardReceived']) : 0);
    $gcashReceived = isset($paymentBreakdown['gcash']) ? floatval($paymentBreakdown['gcash']) : (isset($sales['GcashReceived']) ? floatval($sales['GcashReceived']) : 0);
    $totalSales = $cashReceived + $cardReceived + $gcashReceived;
    $transactionCount = isset($sales['TransactionCount']) ? intval($sales['TransactionCount']) : 0;
    
    // GET SOD
    if ($sodAmount == 0) {
        $sodQuery = "SELECT ISNULL(SodAmount, 0) AS SodAmount FROM StartOfDay
                     WHERE CAST(SodDate AS DATE) = CAST(:date AS DATE) AND Branch = :branch";
        $stmt = $conn->prepare($sodQuery);
        $stmt->bindParam(':date', $eodDate);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->execute();
        $sodResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $sodAmount = isset($sodResult['SodAmount']) ? floatval($sodResult['SodAmount']) : 0;
    }
    
    $expectedCashOnHand = $sodAmount + $cashReceived - $totalDeposits - $totalExpenses;
    
    // COUNT DENOMINATIONS
    $totalBills = 0;
    $totalCoins = 0;
    $bills = [];
    $coins = [];
    
    $billDenoms = [1000, 500, 200, 100, 50, 20];
    $coinDenoms = [10, 5, 1, 0.25, 0.10, 0.05];
    
    foreach ($billDenoms as $denom) {
        $key = "bill_{$denom}";
        $quantity = isset($denominations[$key]) ? intval($denominations[$key]) : 0;
        if ($quantity > 0) {
            $amount = $denom * $quantity;
            $totalBills += $amount;
            $bills[] = ['denomination' => $denom, 'quantity' => $quantity, 'amount' => $amount];
        }
    }
    
    foreach ($coinDenoms as $denom) {
        $key = "coin_{$denom}";
        $quantity = isset($denominations[$key]) ? intval($denominations[$key]) : 0;
        if ($quantity > 0) {
            $amount = $denom * $quantity;
            $totalCoins += $amount;
            $coins[] = ['denomination' => $denom, 'quantity' => $quantity, 'amount' => $amount];
        }
    }
    
    $totalCashCounted = $totalBills + $totalCoins;
    $cashDifference = $totalCashCounted - $expectedCashOnHand;
    
    $billsJson = json_encode($bills);
    $coinsJson = json_encode($coins);
    
    try {
        if ($eodId) {
            $statusQuery = "SELECT Status FROM EndOfDay WHERE EodID = :id";
            $statusStmt = $conn->prepare($statusQuery);
            $statusStmt->bindParam(':id', $eodId);
            $statusStmt->execute();
            $row = $statusStmt->fetch(PDO::FETCH_ASSOC);
            if ($row && strtolower($row['Status']) === 'submitted' && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Only admin can update a submitted report.']);
                return;
            }

            $query = "UPDATE EndOfDay SET 
                TotalSales = :total_sales,
                TransactionCount = :trans_count,
                CashReceived = :cash_received,
                CardReceived = :card_received,
                GcashReceived = :gcash_received,
                SodAmount = :sod_amount,
                TotalDeposits = :total_deposits,
                DepositCount = :deposit_count,
                TotalExpenses = :total_expenses,
                ExpenseCount = :expense_count,
                ExpectedCashOnHand = :expected_cash_on_hand,
                CashOnHand = :cash_on_hand,
                TotalBills = :total_bills,
                TotalCoins = :total_coins,
                TotalCashCounted = :total_cash_counted,
                CashDifference = :cash_difference,
                Bills = :bills,
                Coins = :coins,
                Notes = :notes,
                Status = 'submitted',
                SubmittedBy = :user,
                SubmittedAt = GETDATE(),
                UpdatedAt = GETDATE()
            WHERE EodID = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $eodId);
        } else {
            $query = "INSERT INTO EndOfDay (
                EodDate, Branch, TotalSales, TransactionCount,
                CashReceived, CardReceived, GcashReceived,
                SodAmount, TotalDeposits, DepositCount,
                TotalExpenses, ExpenseCount,
                ExpectedCashOnHand, CashOnHand,
                TotalBills, TotalCoins, TotalCashCounted, CashDifference,
                Bills, Coins, Notes, Status, CreatedBy, CreatedAt, SubmittedBy, SubmittedAt
            ) VALUES (
                :date, :branch, :total_sales, :trans_count,
                :cash_received, :card_received, :gcash_received,
                :sod_amount, :total_deposits, :deposit_count,
                :total_expenses, :expense_count,
                :expected_cash_on_hand, :cash_on_hand,
                :total_bills, :total_coins, :total_cash_counted, :cash_difference,
                :bills, :coins, :notes, 'submitted', :user, GETDATE(), :user, GETDATE()
            )";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':date', $eodDate);
            $stmt->bindParam(':branch', $currentBranch);
        }
        
        $stmt->bindParam(':total_sales', $totalSales);
        $stmt->bindParam(':trans_count', $transactionCount);
        $stmt->bindParam(':cash_received', $cashReceived);
        $stmt->bindParam(':card_received', $cardReceived);
        $stmt->bindParam(':gcash_received', $gcashReceived);
        $stmt->bindParam(':sod_amount', $sodAmount);
        $stmt->bindParam(':total_deposits', $totalDeposits);
        $stmt->bindParam(':deposit_count', $depositCount);
        $stmt->bindParam(':total_expenses', $totalExpenses);
        $stmt->bindParam(':expense_count', $expenseCount);
        $stmt->bindParam(':expected_cash_on_hand', $expectedCashOnHand);
        $stmt->bindParam(':cash_on_hand', $expectedCashOnHand);
        $stmt->bindParam(':total_bills', $totalBills);
        $stmt->bindParam(':total_coins', $totalCoins);
        $stmt->bindParam(':total_cash_counted', $totalCashCounted);
        $stmt->bindParam(':cash_difference', $cashDifference);
        $stmt->bindParam(':bills', $billsJson);
        $stmt->bindParam(':coins', $coinsJson);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':user', $currentUser);
        
        $stmt->execute();
        
        $newEodId = $eodId ? $eodId : $conn->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'EOD submitted successfully',
            'eod_id' => $newEodId
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
function getEodHistory($conn, $currentBranch) {
    $limit = intval($_GET['limit'] ?? 100);
    
    $query = "SELECT TOP $limit
                EodID, EodDate AS ReportDate, TotalSales, TransactionCount,
                CashReceived, CardReceived, GcashReceived,
                SodAmount, TotalDeposits, DepositCount,
                TotalExpenses, ExpenseCount,
                ExpectedCashOnHand, TotalCashCounted AS CashCounted,
                CashDifference, Status, Notes,
                FORMAT(CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(SubmittedAt, 'yyyy-MM-dd HH:mm') AS SubmittedAt
              FROM EndOfDay
              WHERE Branch = :branch
              ORDER BY EodDate DESC, CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $history]);
}
?>