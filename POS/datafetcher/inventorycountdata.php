<?php
// api_inventory_count.php - Backend API for Inventory Count with Branch Support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Start session for user tracking
session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
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
            handlePostRequest($conn, $action, $currentUser);
            break;
        case 'PUT':
            handlePutRequest($conn, $action, $currentUser);
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
        case 'getProducts':
            getProducts($conn, $currentBranch);
            break;
        case 'getCountSessions':
            getCountSessions($conn, $currentBranch, $userRole);
            break;
        case 'getCountSessionById':
            getCountSessionById($conn, $currentBranch);
            break;
        case 'getCountItems':
            getCountItems($conn);
            break;
        case 'getCountSummary':
            getCountSummary($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'startCount':
            startCount($conn, $data, $currentUser, $currentBranch);
            break;
        case 'saveCount':
            saveCount($conn, $data, $currentUser);
            break;
        case 'completeCount':
            completeCount($conn, $data, $currentUser, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateCount':
            updateCount($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// PRODUCT FUNCTIONS WITH BRANCH
// ============================================

function getProducts($conn, $currentBranch) {
    $category = $_GET['category'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $categoryFilter = $category !== 'all' ? "AND Category = :category" : "";
    $searchFilter = $search !== '' ? "AND (ProductName LIKE :search OR ProductCode LIKE :search)" : "";
    
    $query = "SELECT 
                ProductID, ProductCode, ProductName, Category, Brand,
                CurrentStock, CostPrice, SellingPrice
              FROM Products
              WHERE Branch = :branch $categoryFilter $searchFilter
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    if ($category !== 'all') {
        $stmt->bindParam(':category', $category);
    }
    if ($search !== '') {
        $searchTerm = "%{$search}%";
        $stmt->bindParam(':search', $searchTerm);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

// ============================================
// COUNT SESSION FUNCTIONS WITH BRANCH
// ============================================

function startCount($conn, $data, $currentUser, $currentBranch) {
    $sessionName = $data['session_name'] ?? 'Inventory Count - ' . date('Y-m-d');
    $location = $data['location'] ?? 'Main Warehouse';
    $notes = $data['notes'] ?? '';
    
    $sessionNo = 'CNT-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $query = "INSERT INTO InventoryCountSessions 
              (SessionNo, SessionName, Location, Status, StartedBy, StartDate, Notes, Branch)
              VALUES 
              (:no, :name, :loc, 'in_progress', :user, GETDATE(), :notes, :branch)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':no' => $sessionNo,
        ':name' => $sessionName,
        ':loc' => $location,
        ':user' => $currentUser,
        ':notes' => $notes,
        ':branch' => $currentBranch
    ]);
    
    $sessionId = $conn->lastInsertId();
    
    // Get all products for this branch to count
    $productsQuery = "SELECT ProductID, ProductCode, ProductName, Category, Brand, CurrentStock
                      FROM Products
                      WHERE Branch = :branch
                      ORDER BY ProductName";
    
    $stmt = $conn->prepare($productsQuery);
    $stmt->execute([':branch' => $currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Insert count items for each product
    foreach ($products as $product) {
        $itemQuery = "INSERT INTO InventoryCountItems 
                     (SessionID, ProductID, ProductCode, ProductName, Category, Brand,
                      SystemStock, CountedStock, Status, CreatedAt, Branch)
                     VALUES 
                     (:sid, :pid, :pcode, :pname, :cat, :brand,
                      :sysstock, 0, 'pending', GETDATE(), :branch)";
        
        $stmt = $conn->prepare($itemQuery);
        $stmt->execute([
            ':sid' => $sessionId,
            ':pid' => $product['ProductID'],
            ':pcode' => $product['ProductCode'],
            ':pname' => $product['ProductName'],
            ':cat' => $product['Category'],
            ':brand' => $product['Brand'],
            ':sysstock' => $product['CurrentStock'],
            ':branch' => $currentBranch
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Count session started successfully',
        'session_id' => $sessionId,
        'session_no' => $sessionNo,
        'branch' => $currentBranch
    ]);
}

function getCountSessions($conn, $currentBranch, $userRole) {
    $limit = intval($_GET['limit'] ?? 50);
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = $status !== 'all' ? "AND Status = :status" : "";
    
    // Branch filter - staff can only see their branch, admin can see all
    $branchFilter = "";
    if ($userRole !== 'admin') {
        $branchFilter = "AND Branch = :branch";
    }
    
    $query = "SELECT TOP $limit
                SessionID, SessionNo, SessionName, Location, Status,
                FORMAT(StartDate, 'yyyy-MM-dd HH:mm') AS StartDate,
                FORMAT(CompletedDate, 'yyyy-MM-dd HH:mm') AS CompletedDate,
                StartedBy, CompletedBy, Branch,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE SessionID = s.SessionID) AS TotalItems,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE SessionID = s.SessionID AND Status = 'counted') AS CountedItems,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE SessionID = s.SessionID AND Status = 'verified') AS VerifiedItems,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE SessionID = s.SessionID AND SystemStock != CountedStock AND Status = 'counted') AS DiscrepancyCount,
                Notes
              FROM InventoryCountSessions s
              WHERE 1=1 $statusFilter $branchFilter
              ORDER BY SessionID DESC";
    
    $stmt = $conn->prepare($query);
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    if ($userRole !== 'admin') {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $sessions]);
}

function getCountSessionById($conn, $currentBranch) {
    $sessionId = $_GET['id'] ?? 0;
    
    if (!$sessionId) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        return;
    }
    
    $query = "SELECT 
                SessionID, SessionNo, SessionName, Location, Status,
                FORMAT(StartDate, 'yyyy-MM-dd HH:mm') AS StartDate,
                FORMAT(CompletedDate, 'yyyy-MM-dd HH:mm') AS CompletedDate,
                StartedBy, CompletedBy, Notes, Branch
              FROM InventoryCountSessions
              WHERE SessionID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        // Get count items
        $itemsQuery = "SELECT 
                        ItemID, ProductID, ProductCode, ProductName, Category, Brand,
                        SystemStock, CountedStock, Status, Variance,
                        Notes, CountedBy,
                        FORMAT(CountedAt, 'yyyy-MM-dd HH:mm') AS CountedAt
                      FROM InventoryCountItems
                      WHERE SessionID = :id
                      ORDER BY ProductName";
        
        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute([':id' => $sessionId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $session, 'items' => $items]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
    }
}

function getCountItems($conn) {
    $sessionId = $_GET['session_id'] ?? 0;
    $status = $_GET['status'] ?? 'all';
    
    if (!$sessionId) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        return;
    }
    
    $statusFilter = $status !== 'all' ? "AND Status = :status" : "";
    
    $query = "SELECT 
                ItemID, ProductID, ProductCode, ProductName, Category, Brand,
                SystemStock, CountedStock, Status, Variance,
                Notes, CountedBy,
                FORMAT(CountedAt, 'yyyy-MM-dd HH:mm') AS CountedAt
              FROM InventoryCountItems
              WHERE SessionID = :sid $statusFilter
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sid', $sessionId);
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = [
        'total_items' => count($items),
        'counted_items' => count(array_filter($items, function($i) { return $i['Status'] !== 'pending'; })),
        'pending_items' => count(array_filter($items, function($i) { return $i['Status'] === 'pending'; })),
        'verified_items' => count(array_filter($items, function($i) { return $i['Status'] === 'verified'; })),
        'discrepancy_count' => count(array_filter($items, function($i) { return $i['Variance'] != 0 && $i['Status'] !== 'pending'; }))
    ];
    
    echo json_encode(['success' => true, 'data' => $items, 'summary' => $summary]);
}

function saveCount($conn, $data, $currentUser) {
    $sessionId = $data['session_id'] ?? 0;
    $items = $data['items'] ?? [];
    
    if (!$sessionId) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        return;
    }
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items to save']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        foreach ($items as $item) {
            $itemId = $item['item_id'] ?? 0;
            $countedStock = intval($item['counted_stock'] ?? 0);
            $notes = $item['notes'] ?? '';
            
            // Get system stock for variance calculation
            $getQuery = "SELECT SystemStock FROM InventoryCountItems WHERE ItemID = :id";
            $stmt = $conn->prepare($getQuery);
            $stmt->execute([':id' => $itemId]);
            $systemStock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $variance = $countedStock - ($systemStock['SystemStock'] ?? 0);
            
            $updateQuery = "UPDATE InventoryCountItems 
                            SET CountedStock = :stock,
                                Variance = :variance,
                                Status = 'counted',
                                Notes = :notes,
                                CountedBy = :user,
                                CountedAt = GETDATE()
                            WHERE ItemID = :id";
            
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([
                ':stock' => $countedStock,
                ':variance' => $variance,
                ':notes' => $notes,
                ':user' => $currentUser,
                ':id' => $itemId
            ]);
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Count saved successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateCount($conn, $data, $currentUser) {
    $itemId = $data['item_id'] ?? 0;
    $countedStock = intval($data['counted_stock'] ?? 0);
    $notes = $data['notes'] ?? '';
    
    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Item ID required']);
        return;
    }
    
    $getQuery = "SELECT SystemStock FROM InventoryCountItems WHERE ItemID = :id";
    $stmt = $conn->prepare($getQuery);
    $stmt->execute([':id' => $itemId]);
    $systemStock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $variance = $countedStock - ($systemStock['SystemStock'] ?? 0);
    
    $updateQuery = "UPDATE InventoryCountItems 
                    SET CountedStock = :stock,
                        Variance = :variance,
                        Status = 'counted',
                        Notes = :notes,
                        CountedBy = :user,
                        CountedAt = GETDATE()
                    WHERE ItemID = :id";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        ':stock' => $countedStock,
        ':variance' => $variance,
        ':notes' => $notes,
        ':user' => $currentUser,
        ':id' => $itemId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Count updated successfully']);
}

function completeCount($conn, $data, $currentUser, $currentBranch) {
    $sessionId = $data['session_id'] ?? 0;
    $adjustInventory = $data['adjust_inventory'] ?? false;
    
    if (!$sessionId) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        // Update session status
        $updateSession = "UPDATE InventoryCountSessions 
                          SET Status = 'completed',
                              CompletedBy = :user,
                              CompletedDate = GETDATE()
                          WHERE SessionID = :id";
        
        $stmt = $conn->prepare($updateSession);
        $stmt->execute([
            ':user' => $currentUser,
            ':id' => $sessionId
        ]);
        
        // If adjust inventory is true, update product stocks
        if ($adjustInventory) {
            $itemsQuery = "SELECT ProductID, CountedStock FROM InventoryCountItems 
                          WHERE SessionID = :id AND Status = 'counted'";
            $stmt = $conn->prepare($itemsQuery);
            $stmt->execute([':id' => $sessionId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as $item) {
                $updateStock = "UPDATE Products 
                                SET CurrentStock = :stock,
                                    UpdatedAt = GETDATE()
                                WHERE ProductID = :pid AND Branch = :branch";
                $stmt = $conn->prepare($updateStock);
                $stmt->execute([
                    ':stock' => $item['CountedStock'],
                    ':pid' => $item['ProductID'],
                    ':branch' => $currentBranch
                ]);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $adjustInventory ? 'Count completed and inventory adjusted' : 'Count completed without inventory adjustment',
            'branch' => $currentBranch
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getCountSummary($conn, $currentBranch) {
    $query = "SELECT 
                COUNT(*) AS TotalSessions,
                SUM(CASE WHEN Status = 'in_progress' THEN 1 ELSE 0 END) AS InProgressCount,
                SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) AS CompletedCount,
                SUM(CASE WHEN Status = 'cancelled' THEN 1 ELSE 0 END) AS CancelledCount,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE Status = 'pending' AND Branch = :branch) AS PendingItems,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE Status = 'counted' AND Branch = :branch) AS CountedItems,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE Variance != 0 AND Status = 'counted' AND Branch = :branch) AS DiscrepancyItems
              FROM InventoryCountSessions
              WHERE Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $summary]);
}
?>