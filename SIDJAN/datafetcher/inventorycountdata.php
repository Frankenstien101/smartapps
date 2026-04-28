<?php
// api_inventory_count.php - Backend API for Inventory Count using AvailableQuantity
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? $_SESSION['Branch'] ?? 'Main Branch';
$currentBranchID = $_SESSION['BranchID'] ?? $_SESSION['branch_id'] ?? null;
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
    global $currentBranch, $currentBranchID, $userRole;
    
    switch ($action) {
        case 'getProducts':
            getProducts($conn, $currentBranch, $currentBranchID);
            break;
        case 'getCountSessions':
            getCountSessions($conn, $currentBranch, $currentBranchID, $userRole);
            break;
        case 'getCountSessionById':
            getCountSessionById($conn, $currentBranch, $currentBranchID);
            break;
        case 'getCountItems':
            getCountItems($conn);
            break;
        case 'getCountSummary':
            getCountSummary($conn, $currentBranch, $currentBranchID);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch, $currentBranchID;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'startCount':
            startCount($conn, $data, $currentUser, $currentBranch, $currentBranchID);
            break;
        case 'saveCount':
            saveCount($conn, $data, $currentUser);
            break;
        case 'completeCount':
            completeCount($conn, $data, $currentUser, $currentBranch, $currentBranchID);
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
// PRODUCT FUNCTIONS - USING AVAILABLEQUANTITY
// ============================================

function getProducts($conn, $currentBranch, $currentBranchID) {
    $sql = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand,
                AvailableQuantity,
                CostPrice, 
                SellingPrice,
                SerialNumber,
                IMEINumber
            FROM Products
            WHERE 1=1";
    
    $params = [];
    
    if ($currentBranchID) {
        $sql .= " AND BranchID = ?";
        $params[] = $currentBranchID;
    } elseif ($currentBranch) {
        $sql .= " AND Branch = ?";
        $params[] = $currentBranch;
    }
    
    $sql .= " ORDER BY ProductName";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'data' => $products,
        'branch' => $currentBranch,
        'branch_id' => $currentBranchID,
        'total_products' => count($products)
    ]);
}

// ============================================
// COUNT SESSION FUNCTIONS - USING AVAILABLEQUANTITY
// ============================================

function startCount($conn, $data, $currentUser, $currentBranch, $currentBranchID) {
    $sessionName = $data['session_name'] ?? 'Inventory Count - ' . date('Y-m-d');
    $location = $data['location'] ?? 'Main Warehouse';
    $notes = $data['notes'] ?? '';
    
    $sessionNo = 'CNT-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $branchValue = $currentBranchID ?: $currentBranch;
    
    // Create session
    $sql = "INSERT INTO InventoryCountSessions 
            (SessionNo, SessionName, Location, Status, StartedBy, StartDate, Notes, Branch)
            VALUES (?, ?, ?, 'in_progress', ?, GETDATE(), ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$sessionNo, $sessionName, $location, $currentUser, $notes, $branchValue]);
    
    $sessionId = $conn->lastInsertId();
    
    // Get all products with AvailableQuantity as SystemStock
    $productsSql = "SELECT 
                        ProductID, 
                        ProductCode, 
                        ProductName, 
                        Category, 
                        Brand,
                        AvailableQuantity
                    FROM Products
                    WHERE 1=1";
    
    $params = [];
    
    if ($currentBranchID) {
        $productsSql .= " AND BranchID = ?";
        $params[] = $currentBranchID;
    } elseif ($currentBranch) {
        $productsSql .= " AND Branch = ?";
        $params[] = $currentBranch;
    }
    
    $productsSql .= " ORDER BY ProductName";
    
    $stmt = $conn->prepare($productsSql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No products found for this branch',
            'branch' => $branchValue,
            'branch_id' => $currentBranchID
        ]);
        return;
    }
    
    // Insert count items for each product
    $insertSql = "INSERT INTO InventoryCountItems 
                 (SessionID, ProductID, ProductCode, ProductName, Category, Brand,
                  SystemStock, CountedStock, Status, CreatedAt, Branch)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'pending', GETDATE(), ?)";
    
    $insertStmt = $conn->prepare($insertSql);
    $productsAdded = 0;
    
    foreach ($products as $product) {
        // Use AvailableQuantity as SystemStock
        $systemStock = $product['AvailableQuantity'];
        
        $insertStmt->execute([
            $sessionId,
            $product['ProductID'],
            $product['ProductCode'],
            $product['ProductName'],
            $product['Category'],
            $product['Brand'],
            $systemStock,  // This is AvailableQuantity from Products table
            $branchValue
        ]);
        $productsAdded++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Count session started with ' . $productsAdded . ' products',
        'session_id' => $sessionId,
        'session_no' => $sessionNo,
        'branch' => $branchValue,
        'total_products' => $productsAdded,
        'available_quantity_used' => true
    ]);
}

function getCountSessions($conn, $currentBranch, $currentBranchID, $userRole) {
    $limit = intval($_GET['limit'] ?? 50);
    $status = $_GET['status'] ?? 'all';
    
    $branchValue = $currentBranchID ?: $currentBranch;
    
    $sql = "SELECT 
                s.SessionID, s.SessionNo, s.SessionName, s.Location, s.Status,
                CONVERT(VARCHAR(19), s.StartDate, 120) AS StartDate,
                CONVERT(VARCHAR(19), s.CompletedDate, 120) AS CompletedDate,
                s.StartedBy, s.CompletedBy, s.Branch, s.Notes,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE SessionID = s.SessionID) AS TotalItems,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE SessionID = s.SessionID AND Status = 'counted') AS CountedItems,
                (SELECT COUNT(*) FROM InventoryCountItems WHERE SessionID = s.SessionID AND SystemStock != CountedStock AND Status = 'counted') AS DiscrepancyCount
            FROM InventoryCountSessions s
            WHERE 1=1";
    
    $params = [];
    
    if ($status !== 'all') {
        $sql .= " AND s.Status = ?";
        $params[] = $status;
    }
    
    if ($userRole !== 'admin') {
        $sql .= " AND s.Branch = ?";
        $params[] = $branchValue;
    }
    
    $sql .= " ORDER BY s.SessionID DESC";
    
    if ($limit > 0 && $limit < 999999) {
        $sql = str_replace("SELECT", "SELECT TOP $limit", $sql);
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $sessions]);
}

function getCountSessionById($conn, $currentBranch, $currentBranchID) {
    $sessionId = $_GET['id'] ?? 0;
    
    if (!$sessionId) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        return;
    }
    
    $sql = "SELECT 
                SessionID, SessionNo, SessionName, Location, Status,
                CONVERT(VARCHAR(19), StartDate, 120) AS StartDate,
                CONVERT(VARCHAR(19), CompletedDate, 120) AS CompletedDate,
                StartedBy, CompletedBy, Notes, Branch
            FROM InventoryCountSessions
            WHERE SessionID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if ($session) {
        $itemsSql = "SELECT 
                        ItemID, ProductID, ProductCode, ProductName, Category, Brand,
                        SystemStock, CountedStock, Status, Variance,
                        Notes, CountedBy,
                        CONVERT(VARCHAR(19), CountedAt, 120) AS CountedAt
                    FROM InventoryCountItems
                    WHERE SessionID = ?
                    ORDER BY ProductName";
        
        $stmt = $conn->prepare($itemsSql);
        $stmt->execute([$sessionId]);
        $items = $stmt->fetchAll();
        
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
    
    $sql = "SELECT 
                ItemID, ProductID, ProductCode, ProductName, Category, Brand,
                SystemStock, CountedStock, Status, Variance,
                Notes, CountedBy,
                CONVERT(VARCHAR(19), CountedAt, 120) AS CountedAt
            FROM InventoryCountItems
            WHERE SessionID = ?";
    
    $params = [$sessionId];
    
    if ($status !== 'all') {
        $sql .= " AND Status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY ProductName";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    $summary = [
        'total_items' => count($items),
        'counted_items' => count(array_filter($items, function($i) { return $i['Status'] !== 'pending'; })),
        'pending_items' => count(array_filter($items, function($i) { return $i['Status'] === 'pending'; })),
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
            
            $getSql = "SELECT SystemStock FROM InventoryCountItems WHERE ItemID = ?";
            $getStmt = $conn->prepare($getSql);
            $getStmt->execute([$itemId]);
            $systemStockRow = $getStmt->fetch();
            $systemStock = $systemStockRow ? $systemStockRow['SystemStock'] : 0;
            
            $variance = $countedStock - $systemStock;
            
            $updateSql = "UPDATE InventoryCountItems 
                         SET CountedStock = ?,
                             Variance = ?,
                             Status = CASE WHEN ? > 0 THEN 'counted' ELSE 'pending' END,
                             Notes = ?,
                             CountedBy = ?,
                             CountedAt = GETDATE()
                         WHERE ItemID = ?";
            
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$countedStock, $variance, $countedStock, $notes, $currentUser, $itemId]);
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
    
    $getSql = "SELECT SystemStock FROM InventoryCountItems WHERE ItemID = ?";
    $getStmt = $conn->prepare($getSql);
    $getStmt->execute([$itemId]);
    $systemStockRow = $getStmt->fetch();
    $systemStock = $systemStockRow ? $systemStockRow['SystemStock'] : 0;
    
    $variance = $countedStock - $systemStock;
    
    $updateSql = "UPDATE InventoryCountItems 
                 SET CountedStock = ?,
                     Variance = ?,
                     Status = CASE WHEN ? > 0 THEN 'counted' ELSE 'pending' END,
                     Notes = ?,
                     CountedBy = ?,
                     CountedAt = GETDATE()
                 WHERE ItemID = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$countedStock, $variance, $countedStock, $notes, $currentUser, $itemId]);
    
    echo json_encode(['success' => true, 'message' => 'Count updated successfully']);
}

function completeCount($conn, $data, $currentUser, $currentBranch, $currentBranchID) {
    $sessionId = $data['session_id'] ?? 0;
    $adjustInventory = $data['adjust_inventory'] ?? false;
    
    if (!$sessionId) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $branchValue = $currentBranchID ?: $currentBranch;
        
        $updateSessionSql = "UPDATE InventoryCountSessions 
                            SET Status = 'completed',
                                CompletedBy = ?,
                                CompletedDate = GETDATE()
                            WHERE SessionID = ?";
        
        $updateStmt = $conn->prepare($updateSessionSql);
        $updateStmt->execute([$currentUser, $sessionId]);
        
        if ($adjustInventory) {
            // Get all counted items
            $itemsSql = "SELECT ProductID, CountedStock FROM InventoryCountItems 
                        WHERE SessionID = ? AND Status = 'counted'";
            $itemsStmt = $conn->prepare($itemsSql);
            $itemsStmt->execute([$sessionId]);
            $items = $itemsStmt->fetchAll();
            
            foreach ($items as $item) {
                // Update AvailableQuantity in Products table
                $updateStockSql = "UPDATE Products 
                                  SET AvailableQuantity = ?,
                                      UpdatedAt = GETDATE(),
                                      UpdatedBy = ?
                                  WHERE ProductID = ?";
                
                $params = [$item['CountedStock'], $currentUser, $item['ProductID']];
                
                if ($currentBranchID) {
                    $updateStockSql .= " AND BranchID = ?";
                    $params[] = $currentBranchID;
                } elseif ($currentBranch) {
                    $updateStockSql .= " AND Branch = ?";
                    $params[] = $currentBranch;
                }
                
                $updateStockStmt = $conn->prepare($updateStockSql);
                $updateStockStmt->execute($params);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $adjustInventory ? 'Count completed and AvailableQuantity updated' : 'Count completed without inventory adjustment',
            'branch' => $branchValue,
            'items_updated' => count($items ?? [])
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getCountSummary($conn, $currentBranch, $currentBranchID) {
    $branchValue = $currentBranchID ?: $currentBranch;
    
    $sql = "SELECT 
                COUNT(*) AS TotalSessions,
                SUM(CASE WHEN Status = 'in_progress' THEN 1 ELSE 0 END) AS InProgressCount,
                SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) AS CompletedCount,
                SUM(CASE WHEN Status = 'cancelled' THEN 1 ELSE 0 END) AS CancelledCount
            FROM InventoryCountSessions
            WHERE Branch = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$branchValue]);
    $sessionSummary = $stmt->fetch();
    
    $itemsSql = "SELECT 
                    SUM(CASE WHEN Status = 'pending' THEN 1 ELSE 0 END) AS PendingItems,
                    SUM(CASE WHEN Status = 'counted' THEN 1 ELSE 0 END) AS CountedItems,
                    SUM(CASE WHEN Variance != 0 AND Status = 'counted' THEN 1 ELSE 0 END) AS DiscrepancyItems
                FROM InventoryCountItems
                WHERE Branch = ?";
    
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->execute([$branchValue]);
    $itemsSummary = $itemsStmt->fetch();
    
    $summary = array_merge($sessionSummary ?: [], $itemsSummary ?: []);
    
    $summary['TotalSessions'] = $summary['TotalSessions'] ?? 0;
    $summary['InProgressCount'] = $summary['InProgressCount'] ?? 0;
    $summary['CompletedCount'] = $summary['CompletedCount'] ?? 0;
    $summary['CancelledCount'] = $summary['CancelledCount'] ?? 0;
    $summary['PendingItems'] = $summary['PendingItems'] ?? 0;
    $summary['CountedItems'] = $summary['CountedItems'] ?? 0;
    $summary['DiscrepancyItems'] = $summary['DiscrepancyItems'] ?? 0;
    
    echo json_encode(['success' => true, 'data' => $summary]);
}
?>