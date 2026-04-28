<?php
// addcustomerdata.php - Backend API for Customer Management
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Start session for user tracking
session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Koronadal';
$userRole = $_SESSION['role'] ?? 'staff';

// ============================================
// API ROUTES
// ============================================
try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $action);
            break;
        case 'POST':
            handlePostRequest($conn, $action, $currentUser, $currentBranch);
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
        case 'getCustomers':
            getCustomers($conn, $currentBranch);
            break;
        case 'getCustomerById':
            getCustomerById($conn, $currentBranch);
            break;
        case 'getCustomerStats':
            getCustomerStats($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser, $currentBranch) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'addCustomer':
            addCustomer($conn, $data, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateCustomer':
            updateCustomer($conn, $data);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'deleteCustomer':
            deleteCustomer($conn, $data);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// CUSTOMER FUNCTIONS - FIXED (NO duplicate parameters)
// ============================================

function getCustomers($conn, $currentBranch) {
    $search = $_GET['search'] ?? '';
    $limit = intval($_GET['limit'] ?? 100);
    
    // FIXED: Use DECLARE variable to avoid multiple parameter bindings
    $query = "
        DECLARE @branch VARCHAR(100) = :branch;
        DECLARE @search VARCHAR(100) = :search;
        
        SELECT TOP $limit
            CustomerID, 
            CustomerName, 
            Phone, 
            Email, 
            Address,
            FORMAT(CreatedAt, 'yyyy-MM-dd') AS CreatedAt,
            Branch,
            ISNULL((SELECT COUNT(*) FROM Sales WHERE CustomerPhone = c.Phone AND Branch = @branch), 0) AS TotalPurchases,
            ISNULL((SELECT SUM(TotalAmount) FROM Sales WHERE CustomerPhone = c.Phone AND Branch = @branch), 0) AS TotalSpent
        FROM Customers c
        WHERE (Branch = @branch OR Branch IS NULL)
        AND (CustomerName LIKE '%' + @search + '%' OR Phone LIKE '%' + @search + '%' OR Email LIKE '%' + @search + '%')
        ORDER BY CustomerName";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':search', $search);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $customers, 'count' => count($customers)]);
}

function getCustomerById($conn, $currentBranch) {
    $customerId = $_GET['id'] ?? 0;
    
    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
        return;
    }
    
    $query = "
        DECLARE @branch VARCHAR(100) = :branch;
        DECLARE @id INT = :id;
        
        SELECT 
            CustomerID, 
            CustomerName, 
            Phone, 
            Email, 
            Address,
            FORMAT(CreatedAt, 'yyyy-MM-dd') AS CreatedAt,
            Branch,
            ISNULL((SELECT COUNT(*) FROM Sales WHERE CustomerPhone = c.Phone AND Branch = @branch), 0) AS TotalPurchases,
            ISNULL((SELECT SUM(TotalAmount) FROM Sales WHERE CustomerPhone = c.Phone AND Branch = @branch), 0) AS TotalSpent
        FROM Customers c
        WHERE CustomerID = @id AND (Branch = @branch OR Branch IS NULL)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->bindParam(':id', $customerId);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo json_encode(['success' => true, 'data' => $customer]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
    }
}

function getCustomerStats($conn, $currentBranch) {
    // FIXED: Use simple subqueries without multiple parameters
    $query = "
        DECLARE @branch VARCHAR(100) = :branch;
        
        SELECT 
            (SELECT COUNT(*) FROM Customers WHERE Branch = @branch OR Branch IS NULL) AS TotalCustomers,
            (SELECT ISNULL(SUM(TotalSpent), 0) FROM (
                SELECT ISNULL(SUM(TotalAmount), 0) AS TotalSpent
                FROM Customers c
                LEFT JOIN Sales s ON c.Phone = s.CustomerPhone AND s.Branch = @branch
                WHERE c.Branch = @branch OR c.Branch IS NULL
                GROUP BY c.CustomerID
            ) AS Spending) AS TotalSpent,
            (SELECT ISNULL(SUM(TotalPurchases), 0) FROM (
                SELECT COUNT(*) AS TotalPurchases
                FROM Customers c
                LEFT JOIN Sales s ON c.Phone = s.CustomerPhone AND s.Branch = @branch
                WHERE c.Branch = @branch OR c.Branch IS NULL
                GROUP BY c.CustomerID
            ) AS Purchasing) AS TotalPurchases,
            (SELECT COUNT(*) FROM Customers 
             WHERE (Branch = @branch OR Branch IS NULL) 
             AND MONTH(CreatedAt) = MONTH(GETDATE()) 
             AND YEAR(CreatedAt) = YEAR(GETDATE())) AS NewThisMonth";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function addCustomer($conn, $data, $currentBranch) {
    $customerName = trim($data['customer_name'] ?? '');
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    
    if (empty($customerName)) {
        echo json_encode(['success' => false, 'message' => 'Customer name is required']);
        return;
    }
    
    // Check if customer with same phone already exists
    if (!empty($phone)) {
        $checkQuery = "SELECT COUNT(*) as count FROM Customers WHERE Phone = :phone AND (Branch = :branch OR Branch IS NULL)";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':phone' => $phone, ':branch' => $currentBranch]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Customer with this phone number already exists']);
            return;
        }
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    $query = "INSERT INTO Customers (CustomerName, Phone, Email, Address, CreatedAt, Branch)
              VALUES (:name, :phone, :email, :address, GETDATE(), :branch)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':name' => $customerName,
        ':phone' => $phone,
        ':email' => $email,
        ':address' => $address,
        ':branch' => $currentBranch
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Customer added successfully',
        'customer_id' => $conn->lastInsertId()
    ]);
}

function updateCustomer($conn, $data) {
    $customerId = $data['customer_id'] ?? 0;
    $customerName = trim($data['customer_name'] ?? '');
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    
    if (!$customerId || empty($customerName)) {
        echo json_encode(['success' => false, 'message' => 'Customer ID and name are required']);
        return;
    }
    
    // Check if phone exists for another customer
    if (!empty($phone)) {
        $checkQuery = "SELECT COUNT(*) as count FROM Customers WHERE Phone = :phone AND CustomerID != :id";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':phone' => $phone, ':id' => $customerId]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Another customer already has this phone number']);
            return;
        }
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    $query = "UPDATE Customers 
              SET CustomerName = :name, 
                  Phone = :phone, 
                  Email = :email, 
                  Address = :address 
              WHERE CustomerID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':name' => $customerName,
        ':phone' => $phone,
        ':email' => $email,
        ':address' => $address,
        ':id' => $customerId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
}

function deleteCustomer($conn, $data) {
    $customerId = $data['customer_id'] ?? 0;
    
    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'Customer ID required']);
        return;
    }
    
    // Check if customer has sales records
    $checkQuery = "SELECT COUNT(*) as count FROM Sales s 
                   INNER JOIN Customers c ON s.CustomerPhone = c.Phone 
                   WHERE c.CustomerID = :id";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':id' => $customerId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete customer with existing sales records']);
        return;
    }
    
    $query = "DELETE FROM Customers WHERE CustomerID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $customerId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
    }
}
?>