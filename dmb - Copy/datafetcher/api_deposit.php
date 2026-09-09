<?php
// api_deposit.php - Deposit Transaction API (No Approval)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// DATABASE CONNECTION
// ============================================
include '../DB/dbcon.php';

// Start session for user tracking
session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['BRANCH'] ?? 'Main Branch';

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
            handlePostRequest($conn, $action, $currentUser);
            break;
        case 'PUT':
            handlePutRequest($conn, $action, $currentUser);
            break;
        case 'DELETE':
            handleDeleteRequest($conn, $action);
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
    global $currentBranch;
    
    switch ($action) {
        case 'getDeposits':
            getDeposits($conn, $currentBranch);
            break;
        case 'getDepositById':
            getDepositById($conn);
            break;
        case 'getDepositSummary':
            getDepositSummary($conn, $currentBranch);
            break;
        case 'getDepositTypes':
            getDepositTypes($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'addDeposit':
            addDeposit($conn, $data, $currentUser, $currentBranch);
            break;
        case 'updateDeposit':
            updateDeposit($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'deleteDeposit':
            deleteDeposit($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// DEPOSIT FUNCTIONS
// ============================================

function getDeposits($conn, $currentBranch) {
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $type = $_GET['type'] ?? 'all';
    
    $query = "SELECT 
                d.DepositID,
                d.DepositRef,
                d.DepositDate,
                d.DepositType,
                d.Amount,
                d.ReferenceNo,
                d.BankName,
                d.Notes,
                d.CreatedBy,
                d.CreatedAt
              FROM Deposits d
              WHERE d.Branch = :branch
              AND CAST(d.DepositDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)";
    
    if ($type !== 'all') {
        $query .= " AND d.DepositType = :type";
    }
    
    $query .= " ORDER BY d.DepositDate DESC, d.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    
    if ($type !== 'all') {
        $stmt->bindParam(':type', $type);
    }
    
    $stmt->execute();
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $deposits,
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type' => $type
        ]
    ]);
}

function getDepositById($conn) {
    $depositId = $_GET['id'] ?? 0;
    
    if (!$depositId) {
        echo json_encode(['success' => false, 'message' => 'Deposit ID required']);
        return;
    }
    
    $query = "SELECT * FROM Deposits WHERE DepositID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $depositId);
    $stmt->execute();
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($deposit) {
        echo json_encode([
            'success' => true,
            'data' => $deposit
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Deposit not found'
        ]);
    }
}

function getDepositSummary($conn, $currentBranch) {
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    
    // Total deposits
    $totalQuery = "SELECT 
                      ISNULL(SUM(Amount), 0) AS TotalAmount,
                      COUNT(*) AS TotalCount
                   FROM Deposits
                   WHERE Branch = :branch
                   AND CAST(DepositDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)";
    
    $stmt = $conn->prepare($totalQuery);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    $stmt->execute();
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // By type
    $typeQuery = "SELECT 
                    DepositType,
                    ISNULL(SUM(Amount), 0) AS TotalAmount,
                    COUNT(*) AS Count
                  FROM Deposits
                  WHERE Branch = :branch
                  AND CAST(DepositDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)
                  GROUP BY DepositType";
    
    $stmt = $conn->prepare($typeQuery);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    $stmt->execute();
    $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'TotalAmount' => floatval($totals['TotalAmount'] ?? 0),
                'TotalCount' => intval($totals['TotalCount'] ?? 0)
            ],
            'by_type' => $byType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]
    ]);
}

function getDepositTypes($conn) {
    $query = "SELECT 
                TypeName,
                Description
              FROM DepositTypes
              WHERE Status = 'active'
              ORDER BY TypeName ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $types
    ]);
}

function addDeposit($conn, $data, $currentUser, $currentBranch) {
    $depositDate = $data['deposit_date'] ?? date('Y-m-d');
    $depositType = $data['deposit_type'] ?? '';
    $amount = floatval($data['amount'] ?? 0);
    $referenceNo = trim($data['reference_no'] ?? '');
    $bankName = trim($data['bank_name'] ?? '');
    $notes = $data['notes'] ?? '';
    
    if (!$depositType) {
        echo json_encode(['success' => false, 'message' => 'Deposit type is required']);
        return;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    // Generate deposit reference number
    $depositRef = 'DEP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $query = "INSERT INTO Deposits 
              (DepositDate, DepositType, Amount, ReferenceNo, BankName, Notes, 
               Branch, CreatedBy, CreatedAt, DepositRef)
              VALUES 
              (:date, :type, :amount, :ref, :bank, :notes, 
               :branch, :user, GETDATE(), :deposit_ref)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':date', $depositDate);
    $stmt->bindParam(':type', $depositType);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':ref', $referenceNo);
    $stmt->bindParam(':bank', $bankName);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':user', $currentUser);
    $stmt->bindParam(':deposit_ref', $depositRef);
    
    if ($stmt->execute()) {
        $depositId = $conn->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Deposit added successfully',
            'deposit_id' => $depositId,
            'deposit_ref' => $depositRef,
            'data' => [
                'deposit_date' => $depositDate,
                'deposit_type' => $depositType,
                'amount' => $amount,
                'reference_no' => $referenceNo,
                'notes' => $notes
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add deposit'
        ]);
    }
}

function updateDeposit($conn, $data, $currentUser) {
    $depositId = $data['deposit_id'] ?? 0;
    $depositDate = $data['deposit_date'] ?? null;
    $depositType = $data['deposit_type'] ?? null;
    $amount = $data['amount'] ?? null;
    $referenceNo = $data['reference_no'] ?? null;
    $bankName = $data['bank_name'] ?? null;
    $notes = $data['notes'] ?? null;
    
    if (!$depositId) {
        echo json_encode(['success' => false, 'message' => 'Deposit ID required']);
        return;
    }
    
    $updates = [];
    $params = [':id' => $depositId, ':user' => $currentUser];
    
    if ($depositDate !== null) {
        $updates[] = "DepositDate = :date";
        $params[':date'] = $depositDate;
    }
    if ($depositType !== null) {
        $updates[] = "DepositType = :type";
        $params[':type'] = $depositType;
    }
    if ($amount !== null) {
        $updates[] = "Amount = :amount";
        $params[':amount'] = $amount;
    }
    if ($referenceNo !== null) {
        $updates[] = "ReferenceNo = :ref";
        $params[':ref'] = $referenceNo;
    }
    if ($bankName !== null) {
        $updates[] = "BankName = :bank";
        $params[':bank'] = $bankName;
    }
    if ($notes !== null) {
        $updates[] = "Notes = :notes";
        $params[':notes'] = $notes;
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    $query = "UPDATE Deposits SET " . implode(', ', $updates) . ", UpdatedBy = :user, UpdatedAt = GETDATE() WHERE DepositID = :id";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Deposit updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update deposit'
        ]);
    }
}

function deleteDeposit($conn) {
    $depositId = $_GET['id'] ?? 0;
    
    if (!$depositId) {
        echo json_encode(['success' => false, 'message' => 'Deposit ID required']);
        return;
    }
    
    $query = "DELETE FROM Deposits WHERE DepositID = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $depositId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Deposit deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete deposit'
        ]);
    }
}
?>