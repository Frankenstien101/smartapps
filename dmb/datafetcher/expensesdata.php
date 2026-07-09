<?php
// api_expenses.php - Expenses Management API
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
        case 'getExpenses':
            getExpenses($conn, $currentBranch);
            break;
        case 'getExpenseTypes':
            getExpenseTypes($conn, $currentBranch);
            break;
        case 'getExpenseById':
            getExpenseById($conn);
            break;
        case 'getExpenseSummary':
            getExpenseSummary($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'addExpense':
            addExpense($conn, $data, $currentUser, $currentBranch);
            break;
        case 'addExpenseType':
            addExpenseType($conn, $data, $currentUser, $currentBranch);
            break;
        case 'updateExpense':
            updateExpense($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateExpenseType':
            updateExpenseType($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'deleteExpense':
            deleteExpense($conn);
            break;
        case 'deleteExpenseType':
            deleteExpenseType($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// EXPENSE FUNCTIONS
// ============================================

function getExpenses($conn, $currentBranch) {
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $typeId = $_GET['type_id'] ?? null;
    
    $query = "SELECT 
                e.ExpenseID,
                e.ExpenseDate,
                e.Amount,
                e.Notes,
                e.Status,
                e.CreatedBy,
                e.CreatedAt,
                et.ExpenseTypeID,
                et.TypeName,
                et.Description AS TypeDescription
              FROM Expenses e
              LEFT JOIN ExpenseTypes et ON e.ExpenseTypeID = et.ExpenseTypeID
              WHERE e.Branch = :branch
              AND e.Status = 'active'
              AND CAST(e.ExpenseDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)";
    
    if ($typeId) {
        $query .= " AND e.ExpenseTypeID = :type_id";
    }
    
    $query .= " ORDER BY e.ExpenseDate DESC, e.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    
    if ($typeId) {
        $stmt->bindParam(':type_id', $typeId);
    }
    
    $stmt->execute();
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $expenses,
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type_id' => $typeId
        ]
    ]);
}

function getExpenseTypes($conn, $currentBranch) {
    $query = "SELECT 
                ExpenseTypeID,
                TypeName,
                Description,
                Status,
                CreatedBy,
                CreatedAt
              FROM ExpenseTypes
              WHERE Branch = :branch
              AND Status = 'active'
              ORDER BY TypeName ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $types
    ]);
}

function getExpenseById($conn) {
    $expenseId = $_GET['id'] ?? 0;
    
    if (!$expenseId) {
        echo json_encode(['success' => false, 'message' => 'Expense ID required']);
        return;
    }
    
    $query = "SELECT 
                e.*,
                et.TypeName
              FROM Expenses e
              LEFT JOIN ExpenseTypes et ON e.ExpenseTypeID = et.ExpenseTypeID
              WHERE e.ExpenseID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $expenseId);
    $stmt->execute();
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $expense
    ]);
}

function getExpenseSummary($conn, $currentBranch) {
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    
    $totalQuery = "SELECT ISNULL(SUM(Amount), 0) AS TotalAmount,
                          COUNT(*) AS TotalCount
                   FROM Expenses
                   WHERE Branch = :branch
                   AND Status = 'active'
                   AND CAST(ExpenseDate AS DATE) BETWEEN CAST(:date_from AS DATE) AND CAST(:date_to AS DATE)";
    
    $stmt = $conn->prepare($totalQuery);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    $stmt->execute();
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => $totals,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]
    ]);
}

function addExpense($conn, $data, $currentUser, $currentBranch) {
    $expenseDate = $data['expense_date'] ?? date('Y-m-d');
    $expenseTypeId = $data['expense_type_id'] ?? 0;
    $amount = floatval($data['amount'] ?? 0);
    $notes = $data['notes'] ?? '';
    
    if (!$expenseTypeId) {
        echo json_encode(['success' => false, 'message' => 'Expense type is required']);
        return;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    $query = "INSERT INTO Expenses 
              (ExpenseDate, ExpenseTypeID, Amount, Notes, Branch, Status, CreatedBy, CreatedAt)
              VALUES 
              (:date, :type_id, :amount, :notes, :branch, 'active', :user, GETDATE())";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':date', $expenseDate);
    $stmt->bindParam(':type_id', $expenseTypeId);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':user', $currentUser);
    
    if ($stmt->execute()) {
        $expenseId = $conn->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Expense added successfully',
            'expense_id' => $expenseId,
            'data' => [
                'expense_date' => $expenseDate,
                'amount' => $amount,
                'notes' => $notes
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add expense'
        ]);
    }
}

function addExpenseType($conn, $data, $currentUser, $currentBranch) {
    $typeName = trim($data['type_name'] ?? '');
    $description = $data['description'] ?? '';
    
    if (!$typeName) {
        echo json_encode(['success' => false, 'message' => 'Type name is required']);
        return;
    }
    
    $checkQuery = "SELECT ExpenseTypeID FROM ExpenseTypes 
                   WHERE TypeName = :name AND Branch = :branch";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bindParam(':name', $typeName);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Expense type already exists']);
        return;
    }
    
    $query = "INSERT INTO ExpenseTypes 
              (TypeName, Description, Branch, Status, CreatedBy, CreatedAt)
              VALUES 
              (:name, :desc, :branch, 'active', :user, GETDATE())";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $typeName);
    $stmt->bindParam(':desc', $description);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':user', $currentUser);
    
    if ($stmt->execute()) {
        $typeId = $conn->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Expense type added successfully',
            'type_id' => $typeId,
            'data' => [
                'type_name' => $typeName,
                'description' => $description
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add expense type'
        ]);
    }
}

// ============================================
// UPDATE EXPENSE - FIXED
// ============================================
function updateExpense($conn, $data, $currentUser) {
    // Debug log
    error_log("=== UPDATE EXPENSE ===");
    error_log("Data received: " . print_r($data, true));
    
    $expenseId = isset($data['expense_id']) ? intval($data['expense_id']) : 0;
    $expenseDate = isset($data['expense_date']) ? $data['expense_date'] : null;
    $expenseTypeId = isset($data['expense_type_id']) ? intval($data['expense_type_id']) : null;
    $amount = isset($data['amount']) ? floatval($data['amount']) : null;
    $notes = isset($data['notes']) ? $data['notes'] : null;
    
    if (!$expenseId) {
        echo json_encode(['success' => false, 'message' => 'Expense ID required']);
        return;
    }
    
    // Build update query
    $updates = [];
    $params = [];
    
    if ($expenseDate !== null && $expenseDate !== '') {
        $updates[] = "ExpenseDate = ?";
        $params[] = $expenseDate;
    }
    if ($expenseTypeId !== null && $expenseTypeId > 0) {
        $updates[] = "ExpenseTypeID = ?";
        $params[] = $expenseTypeId;
    }
    if ($amount !== null && $amount > 0) {
        $updates[] = "Amount = ?";
        $params[] = $amount;
    }
    if ($notes !== null) {
        $updates[] = "Notes = ?";
        $params[] = $notes;
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    // Add updated by and id
    $updates[] = "UpdatedBy = ?";
    $params[] = $currentUser;
    $updates[] = "UpdatedAt = GETDATE()";
    $params[] = $expenseId; // For WHERE clause
    
    $query = "UPDATE Expenses SET " . implode(', ', $updates) . " WHERE ExpenseID = ?";
    
    error_log("Update Query: " . $query);
    error_log("Params: " . print_r($params, true));
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindParam($i + 1, $params[$i]);
    }
    
    if ($stmt->execute()) {
        error_log("Update successful for ID: " . $expenseId);
        echo json_encode([
            'success' => true,
            'message' => 'Expense updated successfully'
        ]);
    } else {
        $error = $stmt->errorInfo();
        error_log("Update failed: " . print_r($error, true));
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update expense: ' . $error[2]
        ]);
    }
}

function updateExpenseType($conn, $data, $currentUser) {
    $typeId = $data['type_id'] ?? 0;
    $typeName = $data['type_name'] ?? null;
    $description = $data['description'] ?? null;
    $status = $data['status'] ?? null;
    
    if (!$typeId) {
        echo json_encode(['success' => false, 'message' => 'Type ID required']);
        return;
    }
    
    $updates = [];
    $params = [':id' => $typeId, ':user' => $currentUser];
    
    if ($typeName !== null) {
        $updates[] = "TypeName = :name";
        $params[':name'] = $typeName;
    }
    if ($description !== null) {
        $updates[] = "Description = :desc";
        $params[':desc'] = $description;
    }
    if ($status !== null) {
        $updates[] = "Status = :status";
        $params[':status'] = $status;
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    $query = "UPDATE ExpenseTypes SET " . implode(', ', $updates) . ", UpdatedBy = :user, UpdatedAt = GETDATE() WHERE ExpenseTypeID = :id";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Expense type updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update expense type'
        ]);
    }
}

function deleteExpense($conn) {
    $expenseId = $_GET['id'] ?? 0;
    
    if (!$expenseId) {
        echo json_encode(['success' => false, 'message' => 'Expense ID required']);
        return;
    }
    
    $query = "UPDATE Expenses SET Status = 'deleted' WHERE ExpenseID = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $expenseId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete expense'
        ]);
    }
}

function deleteExpenseType($conn) {
    $typeId = $_GET['id'] ?? 0;
    
    if (!$typeId) {
        echo json_encode(['success' => false, 'message' => 'Type ID required']);
        return;
    }
    
    $checkQuery = "SELECT COUNT(*) AS count FROM Expenses WHERE ExpenseTypeID = :id AND Status = 'active'";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bindParam(':id', $typeId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete type with existing expenses']);
        return;
    }
    
    $query = "UPDATE ExpenseTypes SET Status = 'inactive' WHERE ExpenseTypeID = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $typeId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Expense type deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete expense type'
        ]);
    }
}
?>