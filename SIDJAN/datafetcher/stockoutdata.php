<?php
// /SIDJAN/datafetcher/stockoutdata.php - Direct Stock Out (No Approval) with Branch Support

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include '../DB/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
//try {
//    $conn = new PDO(
//        "sqlsrv:Server=172.40.0.81;Database=SIDJAN",
//        "sa",
//        'bspi.@dm1n'
//    );
//    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
//} catch (PDOException $e) {
//    echo json_encode(['success' => false, 'error' => 'Database connection failed', 'message' => $e->getMessage()]);
//    exit();
//}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $action);
            break;
        case 'POST':
            handlePostRequest($conn, $action, $currentUser);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error', 'message' => $e->getMessage()]);
}

function handleGetRequest($conn, $action) {
    global $currentBranch;
    
    switch ($action) {
        case 'getProducts':
            getProducts($conn, $currentBranch);
            break;
        case 'getProductUnits':
            getProductUnits($conn, $currentBranch);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
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
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
}

// ============================================
// GET PRODUCTS WITH BRANCH FILTER
// ============================================

function getProducts($conn, $currentBranch) {
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT 
                p.ProductID, 
                p.ProductCode, 
                p.ProductName, 
                p.Category, 
                p.Brand,
                p.AvailableQuantity as CurrentStock,
                p.CostPrice, 
                p.SellingPrice
              FROM Products p
              WHERE p.AvailableQuantity > 0 AND p.Branch = :branch";
    
    $params = [':branch' => $currentBranch];
    
    if ($search !== '') {
        $query .= " AND (p.ProductName LIKE :search OR p.ProductCode LIKE :search OR p.Brand LIKE :search OR p.Category LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    $query .= " ORDER BY p.ProductName";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Get units for products that have serialized items
    foreach ($products as &$product) {
        $unitQuery = "SELECT 
                        UnitID, 
                        UnitNumber, 
                        IMEINumber, 
                        SerialNumber, 
                        Status 
                      FROM ProductUnits 
                      WHERE ProductID = :pid AND Status = 'available'
                      ORDER BY UnitNumber";
        
        $unitStmt = $conn->prepare($unitQuery);
        $unitStmt->bindParam(':pid', $product['ProductID']);
        $unitStmt->execute();
        $units = $unitStmt->fetchAll();
        
        $product['Units'] = $units;
    }
    
    echo json_encode(['success' => true, 'data' => $products]);
}

// ============================================
// GET UNITS FOR SPECIFIC PRODUCT
// ============================================

function getProductUnits($conn, $currentBranch) {
    $productId = $_GET['product_id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    // First verify the product belongs to current branch
    $productCheck = "SELECT ProductID FROM Products WHERE ProductID = :pid AND Branch = :branch";
    $stmt = $conn->prepare($productCheck);
    $stmt->bindParam(':pid', $productId);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found in this branch']);
        return;
    }
    
    // Get available units for this product
    $unitQuery = "SELECT 
                    UnitID, 
                    UnitNumber, 
                    IMEINumber, 
                    SerialNumber, 
                    Status 
                  FROM ProductUnits 
                  WHERE ProductID = :pid AND Status = 'available'
                  ORDER BY UnitNumber";
    
    $stmt = $conn->prepare($unitQuery);
    $stmt->bindParam(':pid', $productId);
    $stmt->execute();
    $units = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $units]);
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
        
        foreach ($items as $item) {
            $productId = $item['product_id'];
            
            // Check if this is a unit-based (serialized) item
            if (isset($item['units']) && is_array($item['units']) && count($item['units']) > 0) {
                // Process serialized units
                $unitIds = $item['units'];
                $quantity = count($unitIds);
                
                // Get product info (verify branch)
                $productQuery = "SELECT 
                                    ProductID, 
                                    ProductName, 
                                    AvailableQuantity, 
                                    CostPrice, 
                                    SellingPrice 
                                FROM Products 
                                WHERE ProductID = :id AND Branch = :branch";
                $stmt = $conn->prepare($productQuery);
                $stmt->bindParam(':id', $productId);
                $stmt->bindParam(':branch', $currentBranch);
                $stmt->execute();
                $product = $stmt->fetch();
                
                if (!$product) {
                    throw new Exception("Product not found in this branch");
                }
                
                // Verify all units exist and are available - using positional parameters
                $questionMarks = str_repeat('?,', count($unitIds) - 1) . '?';
                $unitQuery = "SELECT UnitID, UnitNumber, Status 
                             FROM ProductUnits 
                             WHERE UnitID IN ($questionMarks) AND Status = 'available'";
                $stmt = $conn->prepare($unitQuery);
                
                // Bind each unit ID positionally
                foreach ($unitIds as $key => $unitId) {
                    $stmt->bindValue($key + 1, $unitId);
                }
                $stmt->execute();
                $availableUnits = $stmt->fetchAll();
                
                if (count($availableUnits) != count($unitIds)) {
                    $foundIds = array_column($availableUnits, 'UnitID');
                    $missingIds = array_diff($unitIds, $foundIds);
                    throw new Exception("Some units are not available for {$product['ProductName']}. Missing units: " . implode(', ', $missingIds));
                }
                
                // Update units status to 'released' - using positional parameters
                $updateUnits = "UPDATE ProductUnits 
                               SET Status = 'released', 
                                   SoldAt = GETDATE(),
                                   SoldTo = ?,
                                   SoldBy = ?,
                                   Notes = ?
                               WHERE UnitID IN ($questionMarks)";
                $stmt = $conn->prepare($updateUnits);
                
                // Bind parameters: first the 3 values, then the unit IDs
                $paramIndex = 1;
                $stmt->bindValue($paramIndex++, $department);
                $stmt->bindValue($paramIndex++, $currentUser);
                $stmt->bindValue($paramIndex++, "Stock Out - $reason - $notes");
                
                foreach ($unitIds as $unitId) {
                    $stmt->bindValue($paramIndex++, $unitId);
                }
                $stmt->execute();
                
                // Update product available quantity
                $oldStock = $product['AvailableQuantity'];
                $newStock = $oldStock - $quantity;
                
                $updateStock = "UPDATE Products 
                                SET AvailableQuantity = AvailableQuantity - :qty,
                                    UpdatedAt = GETDATE()
                                WHERE ProductID = :pid AND Branch = :branch";
                $stmt = $conn->prepare($updateStock);
                $stmt->bindParam(':qty', $quantity);
                $stmt->bindParam(':pid', $productId);
                $stmt->bindParam(':branch', $currentBranch);
                $stmt->execute();
                
                // Record in stock history
                $historyQuery = "INSERT INTO StockInHistory 
                                (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                 CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                                VALUES 
                                (?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                
                $stmt = $conn->prepare($historyQuery);
                $unitIdsString = implode(',', $unitIds);
                $historyNote = "Stock Out (Serialized) - $reason - Department: $department - Units: $unitIdsString - $notes";
                $totalCost = $product['CostPrice'] * $quantity;
                
                $stmt->execute([
                    $productId,
                    $product['ProductName'],
                    -$quantity,
                    $oldStock,
                    $newStock,
                    $product['CostPrice'],
                    $totalCost,
                    $historyNote,
                    $currentUser,
                    $currentBranch
                ]);
                
                $processedItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product['ProductName'],
                    'quantity' => $quantity,
                    'units' => $unitIds,
                    'unit_details' => $availableUnits,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock
                ];
                
            } else {
                // Process regular quantity-based item
                $quantity = intval($item['quantity']);
                
                if ($quantity <= 0) {
                    throw new Exception("Invalid quantity for product ID: $productId");
                }
                
                // Get product info
                $productQuery = "SELECT 
                                    ProductID, 
                                    ProductName, 
                                    AvailableQuantity, 
                                    CostPrice, 
                                    SellingPrice 
                                FROM Products 
                                WHERE ProductID = :id AND Branch = :branch";
                $stmt = $conn->prepare($productQuery);
                $stmt->bindParam(':id', $productId);
                $stmt->bindParam(':branch', $currentBranch);
                $stmt->execute();
                $product = $stmt->fetch();
                
                if (!$product) {
                    throw new Exception("Product not found in this branch");
                }
                
                if ($product['AvailableQuantity'] < $quantity) {
                    throw new Exception("Insufficient stock for {$product['ProductName']}. Available: {$product['AvailableQuantity']}, Requested: $quantity");
                }
                
                $oldStock = $product['AvailableQuantity'];
                $newStock = $oldStock - $quantity;
                
                // Update product stock
                $updateStock = "UPDATE Products 
                                SET AvailableQuantity = AvailableQuantity - :qty,
                                    UpdatedAt = GETDATE()
                                WHERE ProductID = :pid AND Branch = :branch";
                $stmt = $conn->prepare($updateStock);
                $stmt->bindParam(':qty', $quantity);
                $stmt->bindParam(':pid', $productId);
                $stmt->bindParam(':branch', $currentBranch);
                $stmt->execute();
                
                // Record in stock history
                $historyQuery = "INSERT INTO StockInHistory 
                                (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                 CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                                VALUES 
                                (?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                
                $stmt = $conn->prepare($historyQuery);
                $historyNote = "Stock Out (Bulk) - $reason - Department: $department - $notes";
                $totalCost = $product['CostPrice'] * $quantity;
                
                $stmt->execute([
                    $productId,
                    $product['ProductName'],
                    -$quantity,
                    $oldStock,
                    $newStock,
                    $product['CostPrice'],
                    $totalCost,
                    $historyNote,
                    $currentUser,
                    $currentBranch
                ]);
                
                $processedItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product['ProductName'],
                    'quantity' => $quantity,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock
                ];
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Stock out processed successfully',
            'processed_items' => $processedItems,
            'branch' => $currentBranch,
            'user' => $currentUser
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