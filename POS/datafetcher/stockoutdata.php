<?php
// /SIDJAN/datafetcher/stockoutdata.php - Direct Stock Out (No Approval) with Branch Support

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';
$userRole = $_SESSION['role'] ?? 'staff';

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
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}

function handleGetRequest($conn, $action) {
    global $currentBranch;
    
    switch ($action) {
        case 'getProducts':
            getProducts($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'processStockOut':
            processStockOut($conn, $data, $currentUser, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
}

// ============================================
// GET PRODUCTS WITH BRANCH FILTER
// ============================================

function getProducts($conn, $currentBranch) {
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT 
                ProductID, ProductCode, ProductName, Category, Brand,
                CurrentStock, CostPrice, SellingPrice
              FROM Products
              WHERE CurrentStock > 0 AND Branch = :branch";
    
    if ($search !== '') {
        $query .= " AND (ProductName LIKE :search OR ProductCode LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    $query .= " ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    if ($search !== '') {
        $stmt->bindValue(':search', "%{$search}%");
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

// ============================================
// DIRECT STOCK OUT WITH BRANCH SUPPORT
// ============================================

function processStockOut($conn, $data, $currentUser, $currentBranch) {
    $items = $data['items'] ?? [];
    $reason = $data['reason'] ?? '';
    $department = $data['department'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items to release']);
        return;
    }
    
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Reason is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $processedItems = [];
        
        // Process each item
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = intval($item['quantity']);
            
            // Get current product stock (with branch filter)
            $productQuery = "SELECT ProductName, CurrentStock, CostPrice, SellingPrice 
                            FROM Products WHERE ProductID = :id AND Branch = :branch";
            $stmt = $conn->prepare($productQuery);
            $stmt->execute([':id' => $productId, ':branch' => $currentBranch]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Product not found in this branch");
            }
            
            if ($product['CurrentStock'] < $quantity) {
                throw new Exception("Insufficient stock for {$product['ProductName']}. Available: {$product['CurrentStock']}");
            }
            
            $oldStock = $product['CurrentStock'];
            $newStock = $oldStock - $quantity;
            
            // Update product stock (DECREASE)
            $updateStock = "UPDATE Products 
                            SET CurrentStock = CurrentStock - :qty,
                                UpdatedAt = GETDATE()
                            WHERE ProductID = :pid AND Branch = :branch";
            $stmt = $conn->prepare($updateStock);
            $stmt->execute([
                ':qty' => $quantity,
                ':pid' => $productId,
                ':branch' => $currentBranch
            ]);
            
            // Record in stock history (negative quantity for stock out) with branch
            $historyQuery = "INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (:pid, :pname, :qty, :old, :new, 
                             :cost, :total, :notes, GETDATE(), :user, :branch)";
            
            $stmt = $conn->prepare($historyQuery);
            $stmt->execute([
                ':pid' => $productId,
                ':pname' => $product['ProductName'],
                ':qty' => -$quantity,
                ':old' => $oldStock,
                ':new' => $newStock,
                ':cost' => $product['CostPrice'],
                ':total' => $product['CostPrice'] * $quantity,
                ':notes' => "Stock Out - $reason - $notes",
                ':user' => $currentUser,
                ':branch' => $currentBranch
            ]);
            
            $processedItems[] = [
                'product_id' => $productId,
                'product_name' => $product['ProductName'],
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock
            ];
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Stock out processed successfully',
            'processed_items' => $processedItems,
            'branch' => $currentBranch
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>