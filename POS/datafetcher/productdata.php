<?php
// product_api.php - Backend API for Product Management with IMEI, Serial, Image
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
        case 'getDashboardStats':
            getDashboardStats($conn, $currentBranch);
            break;

            case 'getStockHistory':
            getStockHistory($conn, $currentBranch);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'addProduct':
            addNewProduct($conn, $data, $currentUser, $currentBranch);
            break;

             case 'addStock':
            addStockToProduct($conn, $data, $currentUser, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateProduct':
            updateProduct($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'deleteProduct':
            deleteProduct($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// PRODUCT FUNCTIONS
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
                ProductImagePath,
                IMEINumber,
                SerialNumber,
                Description,
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
                SellingPrice,
                ProductImagePath,
                IMEINumber,
                SerialNumber,
                Description
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
                SellingPrice,
                ProductImagePath,
                IMEINumber,
                SerialNumber
              FROM Products 
              WHERE (ProductName LIKE :search 
                 OR ProductCode LIKE :search
                 OR Brand LIKE :search
                 OR Category LIKE :search
                 OR IMEINumber LIKE :search
                 OR SerialNumber LIKE :search)
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
                Category,
                ProductImagePath,
                IMEINumber,
                SerialNumber
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
    $description = $data['description'] ?? '';
    $productImage = $data['product_image'] ?? null;
    $imeiNumber = $data['imei_number'] ?? '';
    $serialNumber = $data['serial_number'] ?? '';
    
    if (empty($productName)) {
        echo json_encode(['success' => false, 'message' => 'Product name is required']);
        return;
    }
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        return;
    }
    
    // Check if product already exists
    $checkQuery = "SELECT COUNT(*) as count FROM Products WHERE ProductName = :name AND Branch = :branch";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':name' => $productName, ':branch' => $currentBranch]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Product with this name already exists in this branch']);
        return;
    }
    
    // Check if IMEI already exists
    if (!empty($imeiNumber)) {
        $checkIMEI = "SELECT COUNT(*) as count FROM Products WHERE IMEINumber = :imei";
        $stmt = $conn->prepare($checkIMEI);
        $stmt->execute([':imei' => $imeiNumber]);
        $imeiExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($imeiExists['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'IMEI number already exists in another product']);
            return;
        }
    }
    
    // Check if Serial already exists
    if (!empty($serialNumber)) {
        $checkSerial = "SELECT COUNT(*) as count FROM Products WHERE SerialNumber = :serial";
        $stmt = $conn->prepare($checkSerial);
        $stmt->execute([':serial' => $serialNumber]);
        $serialExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($serialExists['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Serial number already exists in another product']);
            return;
        }
    }
    
    // ============================================
    // SAVE IMAGE TO FILE SYSTEM
    // ============================================
    $imagePath = null;
    if ($productImage && !empty($productImage) && strpos($productImage, 'data:image') === 0) {
        // Define upload directory (adjust path as needed)
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/POS/uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Determine file extension
        $extension = 'jpg';
        if (strpos($productImage, 'data:image/png') === 0) {
            $extension = 'png';
        } elseif (strpos($productImage, 'data:image/gif') === 0) {
            $extension = 'gif';
        } elseif (strpos($productImage, 'data:image/jpeg') === 0) {
            $extension = 'jpg';
        }
        
        // Generate unique filename
        $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $fullPath = $uploadDir . $filename;
        
        // Extract base64 data and save to file
        $imageData = explode(',', $productImage);
        if (isset($imageData[1])) {
            $imageContent = base64_decode($imageData[1]);
            if (file_put_contents($fullPath, $imageContent)) {
                $imagePath = '/POS/uploads/products/' . $filename;
            }
        }
    }
    
    $conn->beginTransaction();
    
    try {
        // Insert with file path
        $insertQuery = "INSERT INTO Products 
                        (ProductCode, ProductName, Category, Brand, CurrentStock, CostPrice, SellingPrice, 
                         ProductImagePath, Description, IMEINumber, SerialNumber, CreatedAt, UpdatedAt, Branch, CreatedBy)
                        VALUES 
                        (:code, :name, :cat, :brand, :stock, :cost, :price, 
                         :imagepath, :desc, :imei, :serial, GETDATE(), GETDATE(), :branch, :user)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->execute([
            ':code' => $productCode,
            ':name' => $productName,
            ':cat' => $category,
            ':brand' => $brand,
            ':stock' => $initialStock,
            ':cost' => $costPrice,
            ':price' => $sellingPrice,
            ':imagepath' => $imagePath,
            ':desc' => $description,
            ':imei' => $imeiNumber,
            ':serial' => $serialNumber,
            ':branch' => $currentBranch,
            ':user' => $currentUser
        ]);
        
        $newProductId = $conn->lastInsertId();
        
        // Add stock history if initial stock > 0
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
            'product_code' => $productCode,
            'image_path' => $imagePath
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateProduct($conn, $data, $currentUser) {
    $productId = $data['product_id'] ?? 0;
    $productName = $data['product_name'] ?? '';
    $category = $data['category'] ?? '';
    $brand = $data['brand'] ?? '';
    $costPrice = $data['cost_price'] ?? 0;
    $sellingPrice = $data['selling_price'] ?? 0;
    $description = $data['description'] ?? '';
    $imeiNumber = $data['imei_number'] ?? '';
    $serialNumber = $data['serial_number'] ?? '';
    $productImage = $data['product_image'] ?? null;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    // Check if IMEI already exists on another product
    if (!empty($imeiNumber)) {
        $checkIMEI = "SELECT COUNT(*) as count FROM Products WHERE IMEINumber = :imei AND ProductID != :id";
        $stmt = $conn->prepare($checkIMEI);
        $stmt->execute([':imei' => $imeiNumber, ':id' => $productId]);
        $imeiExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($imeiExists['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'IMEI number already exists in another product']);
            return;
        }
    }
    
    // Check if Serial already exists on another product
    if (!empty($serialNumber)) {
        $checkSerial = "SELECT COUNT(*) as count FROM Products WHERE SerialNumber = :serial AND ProductID != :id";
        $stmt = $conn->prepare($checkSerial);
        $stmt->execute([':serial' => $serialNumber, ':id' => $productId]);
        $serialExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($serialExists['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Serial number already exists in another product']);
            return;
        }
    }
    
    // Handle image update
    $imagePath = null;
    if ($productImage && !empty($productImage) && strpos($productImage, 'data:image') === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/POS/uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = 'jpg';
        if (strpos($productImage, 'data:image/png') === 0) {
            $extension = 'png';
        } elseif (strpos($productImage, 'data:image/gif') === 0) {
            $extension = 'gif';
        }
        
        $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $fullPath = $uploadDir . $filename;
        
        $imageData = explode(',', $productImage);
        if (isset($imageData[1])) {
            $imageContent = base64_decode($imageData[1]);
            if (file_put_contents($fullPath, $imageContent)) {
                $imagePath = '/POS/uploads/products/' . $filename;
            }
        }
    }
    
    try {
        if ($imagePath) {
            $query = "UPDATE Products 
                      SET ProductName = :name,
                          Category = :cat,
                          Brand = :brand,
                          CostPrice = :cost,
                          SellingPrice = :price,
                          ProductImagePath = :imagepath,
                          Description = :desc,
                          IMEINumber = :imei,
                          SerialNumber = :serial,
                          UpdatedAt = GETDATE(),
                          UpdatedBy = :user
                      WHERE ProductID = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':name' => $productName,
                ':cat' => $category,
                ':brand' => $brand,
                ':cost' => $costPrice,
                ':price' => $sellingPrice,
                ':imagepath' => $imagePath,
                ':desc' => $description,
                ':imei' => $imeiNumber,
                ':serial' => $serialNumber,
                ':user' => $currentUser,
                ':id' => $productId
            ]);
        } else {
            $query = "UPDATE Products 
                      SET ProductName = :name,
                          Category = :cat,
                          Brand = :brand,
                          CostPrice = :cost,
                          SellingPrice = :price,
                          Description = :desc,
                          IMEINumber = :imei,
                          SerialNumber = :serial,
                          UpdatedAt = GETDATE(),
                          UpdatedBy = :user
                      WHERE ProductID = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':name' => $productName,
                ':cat' => $category,
                ':brand' => $brand,
                ':cost' => $costPrice,
                ':price' => $sellingPrice,
                ':desc' => $description,
                ':imei' => $imeiNumber,
                ':serial' => $serialNumber,
                ':user' => $currentUser,
                ':id' => $productId
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
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
    
    // Get image path to delete file
    $imgQuery = "SELECT ProductImagePath FROM Products WHERE ProductID = :id";
    $stmt = $conn->prepare($imgQuery);
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $product['ProductImagePath']) {
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . $product['ProductImagePath'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
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
?>