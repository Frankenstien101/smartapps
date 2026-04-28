<?php
// api.php - Backend API for POS, Stock Management, and Returns/Warranty with Branch Support
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
    global $currentBranch, $userRole;
    
    switch ($action) {
        // Product endpoints
        case 'getProducts':
            getProducts($conn, $currentBranch);
            break;
        case 'getProductById':
            getProductById($conn, $currentBranch);
            break;
        case 'searchProducts':
            searchProducts($conn, $currentBranch);
            break;
        case 'getLowStock':
            getLowStockProducts($conn, $currentBranch);
            break;
        
        // Stock endpoints
        case 'getStockHistory':
            getStockHistory($conn, $currentBranch);
            break;
        case 'getDashboardStats':
            getDashboardStats($conn, $currentBranch);
            break;
        
        // POS/Sales endpoints
        case 'getSales':
            getSales($conn, $currentBranch, $userRole);
            break;
        case 'getSaleById':
            getSaleById($conn, $currentBranch);
            break;
        case 'getTodaySales':
            getTodaySales($conn, $currentBranch);
            break;
        case 'getSalesReport':
            getSalesReport($conn, $currentBranch);
            break;
        case 'getReceipt':
            getReceipt($conn, $currentBranch);
            break;
        
        // Customer endpoints
        case 'getCustomers':
            getCustomers($conn, $currentBranch);
            break;
        case 'getCustomerById':
            getCustomerById($conn, $currentBranch);
            break;
        
        // Returns & Warranty endpoints
        case 'getReturns':
            getReturns($conn, $currentBranch, $userRole);
            break;
        case 'getWarrantyClaims':
            getWarrantyClaims($conn, $currentBranch, $userRole);
            break;
        case 'getReturnById':
            getReturnById($conn, $currentBranch);
            break;
        case 'getWarrantyById':
            getWarrantyById($conn, $currentBranch);
            break;
        case 'getReturnStats':
            getReturnStats($conn, $currentBranch);
            break;
        
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        // Stock endpoints
        case 'addStock':
            addStockToProduct($conn, $data, $currentUser, $currentBranch);
            break;
        case 'addProduct':
            addNewProduct($conn, $data, $currentUser, $currentBranch);
            break;
        
        // POS/Sales endpoints
        case 'saveTransaction':
            saveTransaction($conn, $data, $currentUser, $currentBranch);
            break;
        case 'addCustomer':
            addCustomer($conn, $data, $currentBranch);
            break;
        
        // Repair endpoints
        case 'addRepair':
            addRepair($conn, $data, $currentUser, $currentBranch);
            break;
        
        // Returns & Warranty endpoints
        case 'processReturn':
            processReturn($conn, $data, $currentUser, $currentBranch);
            break;
        case 'submitWarranty':
            submitWarranty($conn, $data, $currentUser, $currentBranch);
            break;
        case 'updateReturnStatus':
            updateReturnStatus($conn, $data, $currentUser);
            break;
        case 'updateWarrantyStatus':
            updateWarrantyStatus($conn, $data, $currentUser);
            break;
        
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateProduct':
            updateProduct($conn, $data);
            break;
        case 'updateStock':
            updateStockDirect($conn, $data, $currentUser);
            break;
        case 'updateSaleStatus':
            updateSaleStatus($conn, $data);
            break;
        
        // Returns & Warranty endpoints
        case 'approveReturn':
            approveReturn($conn, $data, $currentUser);
            break;
        case 'approveWarranty':
            approveWarranty($conn, $data, $currentUser);
            break;
        
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'clearHistory':
            clearStockHistory($conn);
            break;
        case 'deleteProduct':
            deleteProduct($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// PRODUCT FUNCTIONS WITH BRANCH
// ============================================

function getProducts($conn, $currentBranch) {
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand, 
                CurrentStock, 
                CostPrice, 
                SellingPrice,
                CAST(CurrentStock AS INT) AS StockLevel
              FROM Products 
              WHERE Branch = :branch
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

function getProductById($conn, $currentBranch) {
    $productId = $_GET['id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand, 
                CurrentStock, 
                CostPrice, 
                SellingPrice
              FROM Products 
              WHERE ProductID = :id AND Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $productId, ':branch' => $currentBranch]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
}

function searchProducts($conn, $currentBranch) {
    $search = $_GET['search'] ?? '';
    
    if (strlen($search) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand, 
                CurrentStock, 
                CostPrice, 
                SellingPrice
              FROM Products 
              WHERE (ProductName LIKE :search 
                 OR ProductCode LIKE :search
                 OR Brand LIKE :search
                 OR Category LIKE :search)
              AND Branch = :branch
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    $searchTerm = "%{$search}%";
    $stmt->execute([':search' => $searchTerm, ':branch' => $currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

function getLowStockProducts($conn, $currentBranch) {
    $threshold = $_GET['threshold'] ?? 10;
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Brand, 
                CurrentStock, 
                SellingPrice,
                Category
              FROM Products 
              WHERE CurrentStock < :threshold AND Branch = :branch
              ORDER BY CurrentStock ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':threshold' => $threshold, ':branch' => $currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

function addNewProduct($conn, $data, $currentUser, $currentBranch) {
    $productCode = $data['product_code'] ?? 'P' . date('YmdHis');
    $productName = trim($data['product_name'] ?? '');
    $category = $data['category'] ?? '';
    $brand = $data['brand'] ?? '';
    $initialStock = intval($data['initial_stock'] ?? 0);
    $costPrice = floatval($data['cost_price'] ?? 0);
    $sellingPrice = floatval($data['selling_price'] ?? 0);
    $invoiceNo = $data['invoice_no'] ?? '';
    $supplierName = $data['supplier_name'] ?? '';
    
    if (empty($productName)) {
        echo json_encode(['success' => false, 'message' => 'Product name is required']);
        return;
    }
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        return;
    }
    
    // Check if product already exists in this branch
    $checkQuery = "SELECT COUNT(*) as count FROM Products WHERE ProductName = :name AND Branch = :branch";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':name' => $productName, ':branch' => $currentBranch]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Product with this name already exists in this branch']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $insertQuery = "INSERT INTO Products 
                        (ProductCode, ProductName, Category, Brand, CurrentStock, CostPrice, SellingPrice, CreatedAt, UpdatedAt, Branch)
                        VALUES 
                        (:code, :name, :cat, :brand, :stock, :cost, :price, GETDATE(), GETDATE(), :branch)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->execute([
            ':code' => $productCode,
            ':name' => $productName,
            ':cat' => $category,
            ':brand' => $brand,
            ':stock' => $initialStock,
            ':cost' => $costPrice,
            ':price' => $sellingPrice,
            ':branch' => $currentBranch
        ]);
        
        $newProductId = $conn->lastInsertId();
        
        if ($initialStock > 0) {
            $totalCost = $initialStock * $costPrice;
            
            $historyQuery = "INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, InvoiceNo, SupplierName, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (:pid, :pname, :qty, 0, :newstock, 
                             :cost, :total, :inv, :supp, 'New product added', GETDATE(), :user, :branch)";
            
            $stmt = $conn->prepare($historyQuery);
            $stmt->execute([
                ':pid' => $newProductId,
                ':pname' => $productName,
                ':qty' => $initialStock,
                ':newstock' => $initialStock,
                ':cost' => $costPrice,
                ':total' => $totalCost,
                ':inv' => $invoiceNo,
                ':supp' => $supplierName,
                ':user' => $currentUser,
                ':branch' => $currentBranch
            ]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Product '{$productName}' added successfully",
            'product_id' => $newProductId,
            'product_code' => $productCode
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateProduct($conn, $data) {
    $productId = $data['product_id'] ?? 0;
    $productName = $data['product_name'] ?? '';
    $category = $data['category'] ?? '';
    $brand = $data['brand'] ?? '';
    $costPrice = $data['cost_price'] ?? 0;
    $sellingPrice = $data['selling_price'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    $query = "UPDATE Products 
              SET ProductName = :name,
                  Category = :cat,
                  Brand = :brand,
                  CostPrice = :cost,
                  SellingPrice = :price,
                  UpdatedAt = GETDATE()
              WHERE ProductID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':name' => $productName,
        ':cat' => $category,
        ':brand' => $brand,
        ':cost' => $costPrice,
        ':price' => $sellingPrice,
        ':id' => $productId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or product not found']);
    }
}

function deleteProduct($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? $_GET['id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    $checkQuery = "SELECT COUNT(*) as count FROM StockInHistory WHERE ProductID = :id";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':id' => $productId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete product with stock history']);
        return;
    }
    
    $query = "DELETE FROM Products WHERE ProductID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $productId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
}

// ============================================
// STOCK FUNCTIONS WITH BRANCH
// ============================================

function addStockToProduct($conn, $data, $currentUser, $currentBranch) {
    $productId = $data['product_id'] ?? 0;
    $quantity = $data['quantity'] ?? 0;
    $costPrice = $data['cost_price'] ?? 0;
    $invoiceNo = $data['invoice_no'] ?? '';
    $supplierName = $data['supplier'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $query = "SELECT CurrentStock, ProductName, CostPrice FROM Products WHERE ProductID = :id AND Branch = :branch";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $productId, ':branch' => $currentBranch]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        
        $oldStock = $product['CurrentStock'];
        $productName = $product['ProductName'];
        $newStock = $oldStock + $quantity;
        $totalCost = $quantity * ($costPrice > 0 ? $costPrice : $product['CostPrice']);
        $finalCostPrice = $costPrice > 0 ? $costPrice : $product['CostPrice'];
        
        $updateQuery = "UPDATE Products 
                        SET CurrentStock = :newStock, 
                            UpdatedAt = GETDATE() 
                        WHERE ProductID = :id AND Branch = :branch";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([':newStock' => $newStock, ':id' => $productId, ':branch' => $currentBranch]);
        
        $historyQuery = "INSERT INTO StockInHistory 
                        (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                         CostPrice, TotalCost, InvoiceNo, SupplierName, Notes, TransactionDate, AddedBy, Branch)
                        VALUES 
                        (:pid, :pname, :qty, :old, :new, 
                         :cost, :total, :inv, :supp, :notes, GETDATE(), :user, :branch)";
        
        $stmt = $conn->prepare($historyQuery);
        $stmt->execute([
            ':pid' => $productId,
            ':pname' => $productName,
            ':qty' => $quantity,
            ':old' => $oldStock,
            ':new' => $newStock,
            ':cost' => $finalCostPrice,
            ':total' => $totalCost,
            ':inv' => $invoiceNo,
            ':supp' => $supplierName,
            ':notes' => $notes,
            ':user' => $currentUser,
            ':branch' => $currentBranch
        ]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Added {$quantity} unit(s) to {$productName}",
            'new_stock' => $newStock,
            'product_name' => $productName
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateStockDirect($conn, $data, $currentUser) {
    $productId = $data['product_id'] ?? 0;
    $newStock = $data['new_stock'] ?? 0;
    $reason = $data['reason'] ?? 'Manual adjustment';
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    if ($newStock < 0) {
        echo json_encode(['success' => false, 'message' => 'Stock cannot be negative']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $query = "SELECT CurrentStock, ProductName, CostPrice FROM Products WHERE ProductID = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        
        $oldStock = $product['CurrentStock'];
        $productName = $product['ProductName'];
        $quantityDiff = $newStock - $oldStock;
        
        $updateQuery = "UPDATE Products SET CurrentStock = :stock, UpdatedAt = GETDATE() WHERE ProductID = :id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([':stock' => $newStock, ':id' => $productId]);
        
        if ($quantityDiff != 0) {
            $historyQuery = "INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, Notes, TransactionDate, AddedBy)
                            SELECT 
                                :pid, :pname, :qty, :old, :new,
                                CostPrice, CostPrice * ABS(:qty), :notes, GETDATE(), :user
                            FROM Products WHERE ProductID = :pid2";
            
            $stmt = $conn->prepare($historyQuery);
            $stmt->execute([
                ':pid' => $productId,
                ':pname' => $productName,
                ':qty' => $quantityDiff,
                ':old' => $oldStock,
                ':new' => $newStock,
                ':notes' => $reason . ' (Manual adjustment)',
                ':user' => $currentUser,
                ':pid2' => $productId
            ]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Stock updated from {$oldStock} to {$newStock}",
            'new_stock' => $newStock
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStockHistory($conn, $currentBranch) {
    $limit = $_GET['limit'] ?? 100;
    $productId = $_GET['product_id'] ?? null;
    
    if ($productId) {
        $query = "SELECT TOP (:limit) 
                    TransactionID, ProductID, ProductName, QuantityAdded, OldStock, NewStock,
                    CostPrice, TotalCost, InvoiceNo, SupplierName, Notes,
                    FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm:ss') AS TransactionDate,
                    AddedBy
                  FROM StockInHistory
                  WHERE ProductID = :productId AND Branch = :branch
                  ORDER BY TransactionDate DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':branch', $currentBranch);
    } else {
        $query = "SELECT TOP (:limit) 
                    TransactionID, ProductID, ProductName, QuantityAdded, OldStock, NewStock,
                    CostPrice, TotalCost, InvoiceNo, SupplierName, Notes,
                    FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm:ss') AS TransactionDate,
                    AddedBy
                  FROM StockInHistory
                  WHERE Branch = :branch
                  ORDER BY TransactionDate DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':branch', $currentBranch);
    }
    
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
}

function clearStockHistory($conn) {
    $confirm = $_GET['confirm'] ?? false;
    
    if (!$confirm) {
        echo json_encode(['success' => false, 'message' => 'Confirmation required']);
        return;
    }
    
    $query = "DELETE FROM StockInHistory";
    $rowCount = $conn->exec($query);
    
    echo json_encode(['success' => true, 'message' => "Stock history cleared. {$rowCount} records deleted."]);
}

function getDashboardStats($conn, $currentBranch) {
    $query = "SELECT 
                (SELECT COUNT(*) FROM Products WHERE Branch = :branch1) AS TotalProducts,
                (SELECT ISNULL(SUM(CurrentStock * CostPrice), 0) FROM Products WHERE Branch = :branch2) AS TotalStockValue,
                (SELECT COUNT(*) FROM Products WHERE CurrentStock < 10 AND Branch = :branch3) AS LowStockCount,
                (SELECT ISNULL(SUM(QuantityAdded), 0) FROM StockInHistory 
                 WHERE TransactionDate >= DATEADD(DAY, -30, GETDATE()) AND Branch = :branch4) AS TotalUnitsAdded,
                (SELECT ISNULL(SUM(TotalCost), 0) FROM StockInHistory 
                 WHERE TransactionDate >= DATEADD(DAY, -30, GETDATE()) AND Branch = :branch5) AS TotalValueAdded,
                (SELECT ISNULL(SUM(CurrentStock), 0) FROM Products WHERE Branch = :branch6) AS TotalUnitsInStock,
                (SELECT ISNULL(SUM(TotalAmount), 0) FROM Sales 
                 WHERE SaleDate >= DATEADD(DAY, -30, GETDATE()) AND Branch = :branch7) AS TotalSales30Days,
                (SELECT COUNT(*) FROM Sales WHERE SaleDate >= DATEADD(DAY, -7, GETDATE()) AND Branch = :branch8) AS TransactionsThisWeek,
                (SELECT COUNT(*) FROM Returns WHERE Branch = :branch9 AND Status = 'pending') AS PendingReturns,
                (SELECT COUNT(*) FROM WarrantyClaims WHERE Branch = :branch10 AND Status = 'pending') AS PendingWarranty
              FROM (SELECT 1 AS dummy) t";
    
    $stmt = $conn->prepare($query);
    
    // Bind each parameter individually
    $stmt->bindParam(':branch1', $currentBranch);
    $stmt->bindParam(':branch2', $currentBranch);
    $stmt->bindParam(':branch3', $currentBranch);
    $stmt->bindParam(':branch4', $currentBranch);
    $stmt->bindParam(':branch5', $currentBranch);
    $stmt->bindParam(':branch6', $currentBranch);
    $stmt->bindParam(':branch7', $currentBranch);
    $stmt->bindParam(':branch8', $currentBranch);
    $stmt->bindParam(':branch9', $currentBranch);
    $stmt->bindParam(':branch10', $currentBranch);
    
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

// ============================================
// POS / SALES FUNCTIONS WITH BRANCH
// ============================================

function saveTransaction($conn, $data, $currentUser, $currentBranch) {
    $receiptNo = $data['receipt_no'] ?? 'INV-' . date('YmdHis');
    $customerName = $data['customer'] ?? 'Walk-in Customer';
    $customerPhone = $data['customer_phone'] ?? '';
    $totalAmount = $data['amount'] ?? 0;
    $paymentMethod = $data['payment_method'] ?? 'cash';
    $items = $data['items'] ?? [];
    $amountReceived = $data['amount_received'] ?? 0;
    $change = $data['change'] ?? 0;
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items in transaction']);
        return;
    }
    
    if ($totalAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction amount']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $salesQuery = "INSERT INTO Sales 
                      (ReceiptNo, CustomerName, CustomerPhone, TotalAmount, PaymentMethod, 
                       AmountReceived, ChangeAmount, SaleDate, CreatedBy, Status, Branch)
                      VALUES 
                      (:receipt, :customer, :phone, :total, :payment, 
                       :received, :change, GETDATE(), :user, 'completed', :branch)";
        
        $stmt = $conn->prepare($salesQuery);
        $stmt->execute([
            ':receipt' => $receiptNo,
            ':customer' => $customerName,
            ':phone' => $customerPhone,
            ':total' => $totalAmount,
            ':payment' => $paymentMethod,
            ':received' => $amountReceived,
            ':change' => $change,
            ':user' => $currentUser,
            ':branch' => $currentBranch
        ]);
        
        $saleId = $conn->lastInsertId();
        
        foreach ($items as $item) {
            $productQuery = "SELECT ProductID, ProductCode, ProductName, CurrentStock, SellingPrice 
                            FROM Products 
                            WHERE (ProductID = :id OR ProductCode = :code) AND Branch = :branch";
            $stmt = $conn->prepare($productQuery);
            $stmt->execute([
                ':id' => $item['id'],
                ':code' => $item['product_code'] ?? '',
                ':branch' => $currentBranch
            ]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Product not found: " . ($item['name'] ?? 'Unknown'));
            }
            
            if ($product['CurrentStock'] < $item['quantity']) {
                throw new Exception("Insufficient stock for product: " . $product['ProductName']);
            }
            
            $itemQuery = "INSERT INTO SaleItems 
                         (SaleID, ProductID, ProductCode, ProductName, Quantity, Price, Total, CreatedAt)
                         VALUES 
                         (:saleId, :productId, :productCode, :productName, :qty, :price, :total, GETDATE())";
            
            $stmt = $conn->prepare($itemQuery);
            $stmt->execute([
                ':saleId' => $saleId,
                ':productId' => $product['ProductID'],
                ':productCode' => $product['ProductCode'],
                ':productName' => $product['ProductName'],
                ':qty' => $item['quantity'],
                ':price' => $item['price'],
                ':total' => $item['total']
            ]);
            
            $stockQuery = "UPDATE Products 
                          SET CurrentStock = CurrentStock - :qty,
                              UpdatedAt = GETDATE()
                          WHERE ProductID = :id AND Branch = :branch";
            $stmt = $conn->prepare($stockQuery);
            $stmt->execute([
                ':qty' => $item['quantity'],
                ':id' => $product['ProductID'],
                ':branch' => $currentBranch
            ]);
            
            $newStock = $product['CurrentStock'] - $item['quantity'];
            $historyQuery = "INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (:pid, :pname, :qty, :old, :new, 
                             :cost, :total, :notes, GETDATE(), :user, :branch)";
            
            $stmt = $conn->prepare($historyQuery);
            $stmt->execute([
                ':pid' => $product['ProductID'],
                ':pname' => $product['ProductName'],
                ':qty' => -$item['quantity'],
                ':old' => $product['CurrentStock'],
                ':new' => $newStock,
                ':cost' => $item['price'],
                ':total' => $item['total'],
                ':notes' => "POS Sale - Receipt: {$receiptNo}",
                ':user' => $currentUser,
                ':branch' => $currentBranch
            ]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction saved successfully',
            'receipt_no' => $receiptNo,
            'sale_id' => $saleId,
            'transaction' => [
                'receipt_no' => $receiptNo,
                'customer' => $customerName,
                'total' => $totalAmount,
                'payment_method' => $paymentMethod,
                'date' => date('Y-m-d H:i:s'),
                'items_count' => count($items)
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getSales($conn, $currentBranch, $userRole) {
    $limit = intval($_GET['limit'] ?? 50);
    
    $branchFilter = "";
    if ($userRole !== 'admin') {
        $branchFilter = "AND Branch = :branch";
    }
    
    $query = "SELECT TOP $limit
                SaleID, 
                ReceiptNo, 
                CustomerName, 
                CustomerPhone, 
                TotalAmount, 
                PaymentMethod, 
                AmountReceived, 
                ChangeAmount,
                FORMAT(SaleDate, 'yyyy-MM-dd HH:mm:ss') AS SaleDate,
                CreatedBy,
                Status
              FROM Sales
              WHERE 1=1 $branchFilter
              ORDER BY SaleDate DESC";
    
    $stmt = $conn->prepare($query);
    if ($userRole !== 'admin') {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $sales]);
}

function getSaleById($conn, $currentBranch) {
    $saleId = $_GET['id'] ?? 0;
    
    if (!$saleId) {
        echo json_encode(['success' => false, 'message' => 'Sale ID required']);
        return;
    }
    
    $query = "SELECT 
                s.SaleID, 
                s.ReceiptNo, 
                s.CustomerName, 
                s.CustomerPhone, 
                s.TotalAmount,
                s.PaymentMethod, 
                s.AmountReceived, 
                s.ChangeAmount,
                FORMAT(s.SaleDate, 'yyyy-MM-dd HH:mm:ss') AS SaleDate,
                s.CreatedBy,
                si.SaleItemID,
                si.ProductCode,
                si.ProductName, 
                si.Quantity, 
                si.Price, 
                si.Total
              FROM Sales s
              LEFT JOIN SaleItems si ON s.SaleID = si.SaleID
              WHERE s.SaleID = :id AND s.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $saleId, ':branch' => $currentBranch]);
    $sale = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($sale && count($sale) > 0) {
        $result = [
            'sale' => [
                'SaleID' => $sale[0]['SaleID'],
                'ReceiptNo' => $sale[0]['ReceiptNo'],
                'CustomerName' => $sale[0]['CustomerName'],
                'CustomerPhone' => $sale[0]['CustomerPhone'],
                'TotalAmount' => $sale[0]['TotalAmount'],
                'PaymentMethod' => $sale[0]['PaymentMethod'],
                'AmountReceived' => $sale[0]['AmountReceived'],
                'ChangeAmount' => $sale[0]['ChangeAmount'],
                'SaleDate' => $sale[0]['SaleDate'],
                'CreatedBy' => $sale[0]['CreatedBy']
            ],
            'items' => []
        ];
        
        foreach ($sale as $row) {
            if ($row['SaleItemID']) {
                $result['items'][] = [
                    'ProductCode' => $row['ProductCode'],
                    'ProductName' => $row['ProductName'],
                    'Quantity' => $row['Quantity'],
                    'Price' => $row['Price'],
                    'Total' => $row['Total']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
    }
}

function getTodaySales($conn, $currentBranch) {
    $query = "SELECT 
                ISNULL(SUM(TotalAmount), 0) AS TodaySales,
                COUNT(*) AS TransactionCount,
                ISNULL(SUM(AmountReceived), 0) AS TotalReceived
              FROM Sales
              WHERE CAST(SaleDate AS DATE) = CAST(GETDATE() AS DATE)
              AND Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $result]);
}

function getSalesReport($conn, $currentBranch) {
    $period = $_GET['period'] ?? 'daily';
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    if ($startDate && $endDate) {
        $endDatePlus = date('Y-m-d', strtotime($endDate . ' +1 day'));
        
        $query = "SELECT 
                    FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                    PaymentMethod
                  FROM Sales
                  WHERE SaleDate >= :start AND SaleDate < :end
                  AND Branch = :branch
                  GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd'), PaymentMethod
                  ORDER BY MIN(SaleDate) DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDatePlus);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->execute();
    } else {
        $query = "SELECT 
                    FORMAT(SaleDate, 'yyyy-MM-dd') AS Date,
                    COUNT(*) AS TransactionCount,
                    ISNULL(SUM(TotalAmount), 0) AS TotalSales,
                    ISNULL(SUM(AmountReceived), 0) AS TotalReceived
                  FROM Sales
                  WHERE SaleDate >= DATEADD(DAY, -30, GETDATE())
                  AND Branch = :branch
                  GROUP BY FORMAT(SaleDate, 'yyyy-MM-dd')
                  ORDER BY MIN(SaleDate) DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':branch', $currentBranch);
        $stmt->execute();
    }
    
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $report]);
}

function getReceipt($conn, $currentBranch) {
    $receiptNo = $_GET['receipt_no'] ?? '';
    
    if (!$receiptNo) {
        echo json_encode(['success' => false, 'message' => 'Receipt number required']);
        return;
    }
    
    $query = "SELECT 
                s.SaleID, 
                s.ReceiptNo, 
                s.CustomerName, 
                s.CustomerPhone, 
                s.TotalAmount,
                s.PaymentMethod, 
                s.AmountReceived, 
                s.ChangeAmount,
                FORMAT(s.SaleDate, 'yyyy-MM-dd HH:mm:ss') AS SaleDate,
                si.ProductCode,
                si.ProductName, 
                si.Quantity, 
                si.Price, 
                si.Total
              FROM Sales s
              LEFT JOIN SaleItems si ON s.SaleID = si.SaleID
              WHERE s.ReceiptNo = :receipt AND s.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':receipt' => $receiptNo, ':branch' => $currentBranch]);
    $sale = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($sale && count($sale) > 0) {
        echo json_encode(['success' => true, 'data' => $sale]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
    }
}

function updateSaleStatus($conn, $data) {
    $saleId = $data['sale_id'] ?? 0;
    $status = $data['status'] ?? '';
    
    if (!$saleId || !$status) {
        echo json_encode(['success' => false, 'message' => 'Sale ID and status required']);
        return;
    }
    
    $validStatuses = ['completed', 'cancelled', 'refunded', 'returned'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $query = "UPDATE Sales SET Status = :status WHERE SaleID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':status' => $status, ':id' => $saleId]);
    
    echo json_encode(['success' => true, 'message' => 'Sale status updated']);
}

// ============================================
// CUSTOMER FUNCTIONS WITH BRANCH
// ============================================

function getCustomers($conn, $currentBranch) {
    $query = "SELECT 
                CustomerID, 
                CustomerName, 
                Phone, 
                Email, 
                Address,
                ISNULL((SELECT COUNT(*) FROM Sales WHERE CustomerPhone = c.Phone AND Branch = :branch), 0) AS TotalPurchases,
                ISNULL((SELECT SUM(TotalAmount) FROM Sales WHERE CustomerPhone = c.Phone AND Branch = :branch), 0) AS TotalSpent
              FROM Customers c
              ORDER BY CustomerName";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $customers]);
}

function getCustomerById($conn, $currentBranch) {
    $customerId = $_GET['id'] ?? 0;
    
    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'Customer ID required']);
        return;
    }
    
    $query = "SELECT * FROM Customers WHERE CustomerID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $customer]);
}

function addCustomer($conn, $data, $currentBranch) {
    $customerName = $data['customer_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    
    if (empty($customerName)) {
        echo json_encode(['success' => false, 'message' => 'Customer name is required']);
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

// ============================================
// REPAIR FUNCTIONS WITH BRANCH
// ============================================

function addRepair($conn, $data, $currentUser, $currentBranch) {
    $customerName = $data['customer_name'] ?? '';
    $customerPhone = $data['customer_phone'] ?? '';
    $deviceType = $data['device_type'] ?? '';
    $issue = $data['issue'] ?? '';
    $estimatedCost = $data['estimated_cost'] ?? 0;
    $notes = $data['notes'] ?? '';
    
    if (empty($customerName) || empty($deviceType)) {
        echo json_encode(['success' => false, 'message' => 'Customer name and device type are required']);
        return;
    }
    
    $repairNo = 'RPR-' . date('Ymd') . '-' . rand(100, 999);
    
    $query = "INSERT INTO Repairs 
              (RepairNo, CustomerName, CustomerPhone, DeviceType, Issue, Status, EstimatedCost, Notes, CreatedBy, CreatedAt, Branch)
              VALUES 
              (:repairNo, :customer, :phone, :device, :issue, 'pending', :cost, :notes, :user, GETDATE(), :branch)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':repairNo' => $repairNo,
        ':customer' => $customerName,
        ':phone' => $customerPhone,
        ':device' => $deviceType,
        ':issue' => $issue,
        ':cost' => $estimatedCost,
        ':notes' => $notes,
        ':user' => $currentUser,
        ':branch' => $currentBranch
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Repair request created',
        'repair_no' => $repairNo,
        'repair_id' => $conn->lastInsertId()
    ]);
}

// ============================================
// RETURNS FUNCTIONS WITH BRANCH
// ============================================

function processReturn($conn, $data, $currentUser, $currentBranch) {
    $transactionId = $data['transaction_id'] ?? 0;
    $transactionType = $data['transaction_type'] ?? 'sales';
    $reason = $data['reason'] ?? '';
    $notes = $data['notes'] ?? '';
    $refundAmount = $data['refund_amount'] ?? 0;
    
    if (!$transactionId) {
        echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
        return;
    }
    
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Reason for return is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        if ($transactionType === 'sales') {
            $saleQuery = "SELECT ReceiptNo, CustomerName, TotalAmount, Status FROM Sales WHERE SaleID = :id AND Branch = :branch";
            $stmt = $conn->prepare($saleQuery);
            $stmt->execute([':id' => $transactionId, ':branch' => $currentBranch]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale) {
                throw new Exception('Sale transaction not found');
            }
            
            if ($sale['Status'] === 'returned') {
                throw new Exception('Transaction already returned');
            }
            
            $updateSale = "UPDATE Sales SET Status = 'returned', UpdatedAt = GETDATE() WHERE SaleID = :id";
            $stmt = $conn->prepare($updateSale);
            $stmt->execute([':id' => $transactionId]);
            
            $itemsQuery = "SELECT ProductID, Quantity FROM SaleItems WHERE SaleID = :id";
            $stmt = $conn->prepare($itemsQuery);
            $stmt->execute([':id' => $transactionId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as $item) {
                $restoreStock = "UPDATE Products SET CurrentStock = CurrentStock + :qty WHERE ProductID = :id AND Branch = :branch";
                $stmt = $conn->prepare($restoreStock);
                $stmt->execute([':qty' => $item['Quantity'], ':id' => $item['ProductID'], ':branch' => $currentBranch]);
            }
            
            $refundAmount = $sale['TotalAmount'];
            $receiptNo = $sale['ReceiptNo'];
            $customerName = $sale['CustomerName'];
        } else {
            throw new Exception('Invalid transaction type');
        }
        
        $returnNo = 'RTRN-' . date('Ymd') . '-' . rand(1000, 9999);
        $insertReturn = "INSERT INTO Returns 
                        (ReturnNo, TransactionID, TransactionType, ReceiptNo, CustomerName, 
                         Reason, RefundAmount, Status, Notes, CreatedBy, CreatedAt, Branch)
                        VALUES 
                        (:no, :tid, :ttype, :receipt, :customer,
                         :reason, :refund, 'pending', :notes, :user, GETDATE(), :branch)";
        
        $stmt = $conn->prepare($insertReturn);
        $stmt->execute([
            ':no' => $returnNo,
            ':tid' => $transactionId,
            ':ttype' => $transactionType,
            ':receipt' => $receiptNo,
            ':customer' => $customerName,
            ':reason' => $reason,
            ':refund' => $refundAmount,
            ':notes' => $notes,
            ':user' => $currentUser,
            ':branch' => $currentBranch
        ]);
        
        $returnId = $conn->lastInsertId();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Return processed successfully',
            'return_id' => $returnId,
            'return_no' => $returnNo,
            'refund_amount' => $refundAmount
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function approveReturn($conn, $data, $currentUser) {
    $returnId = $data['return_id'] ?? 0;
    $status = $data['status'] ?? 'approved';
    
    if (!$returnId) {
        echo json_encode(['success' => false, 'message' => 'Return ID required']);
        return;
    }
    
    $updateQuery = "UPDATE Returns 
                    SET Status = :status, 
                        ApprovedBy = :user, 
                        ApprovedAt = GETDATE(),
                        UpdatedAt = GETDATE()
                    WHERE ReturnID = :id";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        ':status' => $status,
        ':user' => $currentUser,
        ':id' => $returnId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Return ' . $status . ' successfully']);
}

function getReturns($conn, $currentBranch, $userRole) {
    $limit = intval($_GET['limit'] ?? 100);
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = $status !== 'all' ? "AND r.Status = '$status'" : "";
    
    $branchFilter = "";
    if ($userRole !== 'admin') {
        $branchFilter = "AND r.Branch = :branch";
    }
    
    $query = "SELECT TOP $limit
                r.ReturnID, r.ReturnNo, r.TransactionID, r.TransactionType,
                r.ReceiptNo, r.CustomerName, r.Reason, r.RefundAmount,
                r.Status, r.Notes,
                FORMAT(r.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(r.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt,
                r.CreatedBy, r.ApprovedBy,
                r.Branch
              FROM Returns r
              WHERE 1=1 $statusFilter $branchFilter
              ORDER BY r.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    if ($userRole !== 'admin') {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $returns, 'count' => count($returns)]);
}

function getReturnById($conn, $currentBranch) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Return ID required']);
        return;
    }
    
    $query = "SELECT 
                r.*,
                FORMAT(r.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(r.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt
              FROM Returns r
              WHERE r.ReturnID = :id AND r.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id, ':branch' => $currentBranch]);
    $return = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $return]);
}

function getReturnStats($conn, $currentBranch) {
    $query = "SELECT 
                COUNT(*) AS TotalReturns,
                SUM(CASE WHEN Status = 'pending' THEN 1 ELSE 0 END) AS PendingReturns,
                SUM(CASE WHEN Status = 'approved' THEN 1 ELSE 0 END) AS ApprovedReturns,
                SUM(CASE WHEN Status = 'rejected' THEN 1 ELSE 0 END) AS RejectedReturns,
                ISNULL(SUM(RefundAmount), 0) AS TotalRefundAmount
              FROM Returns
              WHERE Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function updateReturnStatus($conn, $data, $currentUser) {
    $returnId = $data['return_id'] ?? 0;
    $status = $data['status'] ?? '';
    
    if (!$returnId || !$status) {
        echo json_encode(['success' => false, 'message' => 'Return ID and status required']);
        return;
    }
    
    $validStatuses = ['pending', 'approved', 'rejected', 'completed'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $updateQuery = "UPDATE Returns SET Status = :status, UpdatedAt = GETDATE() WHERE ReturnID = :id";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([':status' => $status, ':id' => $returnId]);
    
    echo json_encode(['success' => true, 'message' => 'Return status updated']);
}

// ============================================
// WARRANTY FUNCTIONS WITH BRANCH
// ============================================

function submitWarranty($conn, $data, $currentUser, $currentBranch) {
    $transactionId = $data['transaction_id'] ?? 0;
    $transactionType = $data['transaction_type'] ?? 'sales';
    $issue = $data['issue'] ?? '';
    $warrantyType = $data['warranty_type'] ?? 'Repair';
    $notes = $data['notes'] ?? '';
    
    if (!$transactionId) {
        echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
        return;
    }
    
    if (empty($issue)) {
        echo json_encode(['success' => false, 'message' => 'Issue description is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        if ($transactionType === 'sales') {
            $saleQuery = "SELECT ReceiptNo, CustomerName, SaleDate FROM Sales WHERE SaleID = :id AND Branch = :branch";
            $stmt = $conn->prepare($saleQuery);
            $stmt->execute([':id' => $transactionId, ':branch' => $currentBranch]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale) {
                throw new Exception('Sale transaction not found');
            }
            
            $receiptNo = $sale['ReceiptNo'];
            $customerName = $sale['CustomerName'];
            $purchaseDate = $sale['SaleDate'];
        } else {
            throw new Exception('Invalid transaction type');
        }
        
        $expiryDate = date('Y-m-d', strtotime($purchaseDate . ' + 1 year'));
        
        $warrantyNo = 'WRNT-' . date('Ymd') . '-' . rand(1000, 9999);
        $insertWarranty = "INSERT INTO WarrantyClaims 
                          (WarrantyNo, TransactionID, TransactionType, ReceiptNo, CustomerName,
                           Issue, WarrantyType, Status, ExpiryDate, Notes, CreatedBy, CreatedAt, Branch)
                          VALUES 
                          (:no, :tid, :ttype, :receipt, :customer,
                           :issue, :wtype, 'pending', :expiry, :notes, :user, GETDATE(), :branch)";
        
        $stmt = $conn->prepare($insertWarranty);
        $stmt->execute([
            ':no' => $warrantyNo,
            ':tid' => $transactionId,
            ':ttype' => $transactionType,
            ':receipt' => $receiptNo,
            ':customer' => $customerName,
            ':issue' => $issue,
            ':wtype' => $warrantyType,
            ':expiry' => $expiryDate,
            ':notes' => $notes,
            ':user' => $currentUser,
            ':branch' => $currentBranch
        ]);
        
        $warrantyId = $conn->lastInsertId();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Warranty claim submitted successfully',
            'warranty_id' => $warrantyId,
            'warranty_no' => $warrantyNo,
            'expiry_date' => $expiryDate
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function approveWarranty($conn, $data, $currentUser) {
    $warrantyId = $data['warranty_id'] ?? 0;
    $status = $data['status'] ?? 'approved';
    $resolution = $data['resolution'] ?? '';
    
    if (!$warrantyId) {
        echo json_encode(['success' => false, 'message' => 'Warranty ID required']);
        return;
    }
    
    $updateQuery = "UPDATE WarrantyClaims 
                    SET Status = :status, 
                        Resolution = :resolution,
                        ApprovedBy = :user, 
                        ApprovedAt = GETDATE(),
                        UpdatedAt = GETDATE()
                    WHERE WarrantyID = :id";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        ':status' => $status,
        ':resolution' => $resolution,
        ':user' => $currentUser,
        ':id' => $warrantyId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Warranty claim ' . $status . ' successfully']);
}

function getWarrantyClaims($conn, $currentBranch, $userRole) {
    $limit = intval($_GET['limit'] ?? 100);
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = $status !== 'all' ? "AND w.Status = '$status'" : "";
    
    $branchFilter = "";
    if ($userRole !== 'admin') {
        $branchFilter = "AND w.Branch = :branch";
    }
    
    $query = "SELECT TOP $limit
                w.WarrantyID, w.WarrantyNo, w.TransactionID, w.TransactionType,
                w.ReceiptNo, w.CustomerName, w.Issue, w.WarrantyType,
                w.Status, w.Resolution, w.Notes,
                FORMAT(w.ExpiryDate, 'yyyy-MM-dd') AS ExpiryDate,
                FORMAT(w.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(w.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt,
                w.CreatedBy, w.ApprovedBy,
                w.Branch
              FROM WarrantyClaims w
              WHERE 1=1 $statusFilter $branchFilter
              ORDER BY w.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    if ($userRole !== 'admin') {
        $stmt->bindParam(':branch', $currentBranch);
    }
    $stmt->execute();
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $claims, 'count' => count($claims)]);
}

function getWarrantyById($conn, $currentBranch) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Warranty ID required']);
        return;
    }
    
    $query = "SELECT 
                w.*,
                FORMAT(w.ExpiryDate, 'yyyy-MM-dd') AS ExpiryDate,
                FORMAT(w.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(w.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt
              FROM WarrantyClaims w
              WHERE w.WarrantyID = :id AND w.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id, ':branch' => $currentBranch]);
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $claim]);
}

function updateWarrantyStatus($conn, $data, $currentUser) {
    $warrantyId = $data['warranty_id'] ?? 0;
    $status = $data['status'] ?? '';
    
    if (!$warrantyId || !$status) {
        echo json_encode(['success' => false, 'message' => 'Warranty ID and status required']);
        return;
    }
    
    $validStatuses = ['pending', 'approved', 'rejected', 'completed'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $updateQuery = "UPDATE WarrantyClaims SET Status = :status, UpdatedAt = GETDATE() WHERE WarrantyID = :id";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([':status' => $status, ':id' => $warrantyId]);
    
    echo json_encode(['success' => true, 'message' => 'Warranty status updated']);
}
?>