<?php
// product_api.php - Backend API for Product Management (with Branch Support)
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
        case 'getProductUnits':
            getProductUnits($conn, $currentBranch);
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
        case 'getSales':
            getSales($conn, $currentBranch, $userRole);
            break;
        case 'getSaleById':
            getSaleById($conn, $currentBranch);
            break;
        case 'getTodaySales':
            getTodaySales($conn, $currentBranch);
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
        case 'saveTransaction':
            saveTransaction($conn, $data, $currentUser, $currentBranch);
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

function getProducts($conn, $currentBranch) {
    $query = "SELECT 
                p.ProductID, 
                p.ProductCode, 
                p.ProductName, 
                p.Category, 
                p.Brand, 
                p.CostPrice, 
                p.SellingPrice,
                p.ProductImagePath,
                p.Description,
                ISNULL(p.TotalQuantity, 0) as TotalQuantity,
                ISNULL(p.AvailableQuantity, 0) as AvailableQuantity,
                ISNULL(p.SoldQuantity, 0) as SoldQuantity
              FROM Products p
              WHERE p.Branch = ?
              ORDER BY p.ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also get units count for each product
    foreach ($products as &$product) {
        $unitStmt = $conn->prepare("SELECT COUNT(*) as unit_count FROM ProductUnits WHERE ProductID = ? AND Branch = ? AND Status = 'available'");
        $unitStmt->execute([$product['ProductID'], $currentBranch]);
        $unitCount = $unitStmt->fetch(PDO::FETCH_ASSOC);
        $product['HasUnits'] = $unitCount['unit_count'] > 0;
        $product['CurrentStock'] = $product['AvailableQuantity'];
    }
    
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
                ISNULL(TotalQuantity, 0) as TotalQuantity,
                ISNULL(AvailableQuantity, 0) as AvailableQuantity,
                ISNULL(SoldQuantity, 0) as SoldQuantity,
                CostPrice, 
                SellingPrice,
                ProductImagePath,
                Description
              FROM Products 
              WHERE ProductID = ? AND Branch = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$productId, $currentBranch]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $unitsQuery = "SELECT UnitID, UnitNumber, IMEINumber, SerialNumber, Status, Branch,
                              CONVERT(VARCHAR, CreatedAt, 120) AS CreatedAt
                       FROM ProductUnits
                       WHERE ProductID = ? AND Branch = ?
                       ORDER BY UnitNumber";
        $stmt = $conn->prepare($unitsQuery);
        $stmt->execute([$productId, $currentBranch]);
        $product['Units'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
}

function getProductUnits($conn, $currentBranch) {
    $productId = $_GET['product_id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    $query = "SELECT 
                UnitID, UnitNumber, IMEINumber, SerialNumber, Status,
                CONVERT(VARCHAR, CreatedAt, 120) AS CreatedAt
              FROM ProductUnits
              WHERE ProductID = ? AND Branch = ? AND Status = 'available'
              ORDER BY UnitNumber";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$productId, $currentBranch]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $units]);
}

function getLowStockProducts($conn, $currentBranch) {
    $threshold = $_GET['threshold'] ?? 10;
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Brand, 
                ISNULL(AvailableQuantity, 0) as CurrentStock, 
                SellingPrice,
                Category,
                ProductImagePath
              FROM Products 
              WHERE ISNULL(AvailableQuantity, 0) < ? AND Branch = ?
              ORDER BY AvailableQuantity ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$threshold, $currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

function addNewProduct($conn, $data, $currentUser, $currentBranch) {
    $productCode = $data['product_code'] ?? 'P' . date('YmdHis');
    $productName = trim($data['product_name'] ?? '');
    $category = $data['category'] ?? '';
    $brand = $data['brand'] ?? '';
    $description = $data['description'] ?? '';
    $costPrice = floatval($data['cost_price'] ?? 0);
    $sellingPrice = floatval($data['selling_price'] ?? 0);
    $productImage = $data['product_image'] ?? null;
    $units = $data['units'] ?? [];
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
    
    // Check if units have IMEI/Serial
    $hasSerials = false;
    foreach ($units as $unit) {
        if (!empty($unit['imei']) || !empty($unit['serial'])) {
            $hasSerials = true;
            break;
        }
    }
    
    // Check if product already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM Products WHERE ProductName = ? AND Branch = ?");
    $checkStmt->execute([$productName, $currentBranch]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Product with this name already exists in this branch']);
        return;
    }
    
    // Save image to file system
    $imagePath = null;
    if ($productImage && !empty($productImage) && strpos($productImage, 'data:image') === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/SIDJAN/uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = 'jpg';
        if (strpos($productImage, 'data:image/png') === 0) $extension = 'png';
        elseif (strpos($productImage, 'data:image/gif') === 0) $extension = 'gif';
        elseif (strpos($productImage, 'data:image/jpeg') === 0) $extension = 'jpg';
        
        $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $fullPath = $uploadDir . $filename;
        
        $imageData = explode(',', $productImage);
        if (isset($imageData[1])) {
            $imageContent = base64_decode($imageData[1]);
            if (file_put_contents($fullPath, $imageContent)) {
                $imagePath = '/SIDJAN/uploads/products/' . $filename;
            }
        }
    }
    
    $conn->beginTransaction();
    
    try {
        $totalQuantity = count($units);
        
        // Insert product
        $insertStmt = $conn->prepare("INSERT INTO Products 
                        (ProductCode, ProductName, Category, Brand, Description, ProductImagePath, 
                         CostPrice, SellingPrice, TotalQuantity, AvailableQuantity, SoldQuantity, 
                         Branch, CreatedBy, CreatedAt)
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, GETDATE())");
        
        $insertStmt->execute([
            $productCode, $productName, $category, $brand, $description, $imagePath,
            $costPrice, $sellingPrice, $totalQuantity, $totalQuantity,
            $currentBranch, $currentUser
        ]);
        
        $newProductId = $conn->lastInsertId();
        
        // If there are units with IMEI/Serial, add them to ProductUnits with branch
        if ($hasSerials && $totalQuantity > 0) {
            $unitStmt = $conn->prepare("INSERT INTO ProductUnits 
                          (ProductID, UnitNumber, IMEINumber, SerialNumber, Status, CostPrice, SellingPrice, Branch, CreatedBy, CreatedAt)
                          VALUES 
                          (?, ?, ?, ?, 'available', ?, ?, ?, ?, GETDATE())");
            
            $unitNumber = 1;
            foreach ($units as $unit) {
                $unitStmt->execute([
                    $newProductId, $unitNumber,
                    $unit['imei'] ?? '',
                    $unit['serial'] ?? '',
                    $costPrice, $sellingPrice, $currentBranch, $currentUser
                ]);
                $unitNumber++;
            }
            $message = "Product '{$productName}' added successfully with {$totalQuantity} unit(s) (with IMEI/Serial tracking)";
        } else {
            // Direct quantity mode - no individual unit tracking
            $message = "Product '{$productName}' added successfully with {$totalQuantity} unit(s) (bulk quantity)";
        }
        
        // Add stock history
        $totalCost = $totalQuantity * $costPrice;
        
        $historyStmt = $conn->prepare("INSERT INTO StockInHistory 
                        (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                         CostPrice, TotalCost, InvoiceNo, SupplierName, Notes, TransactionDate, AddedBy, Branch)
                        VALUES 
                        (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)");
        
        $historyStmt->execute([
            $newProductId, $productName, $totalQuantity, $totalQuantity,
            $costPrice, $totalCost, $invoiceNo, $supplierName, $description,
            $currentUser, $currentBranch
        ]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'product_id' => $newProductId,
            'product_code' => $productCode,
            'image_path' => $imagePath,
            'units_added' => $totalQuantity,
            'mode' => $hasSerials ? 'per_unit' : 'direct_quantity',
            'branch' => $currentBranch
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function addStockToProduct($conn, $data, $currentUser, $currentBranch) {
    $productId = $data['product_id'] ?? 0;
    $units = $data['units'] ?? [];
    $invoiceNo = $data['invoice_no'] ?? '';
    $supplierName = $data['supplier'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    if (empty($units)) {
        echo json_encode(['success' => false, 'message' => 'At least one unit is required']);
        return;
    }
    
    // Check if this is direct quantity mode (units have no IMEI/Serial)
    $hasSerials = false;
    foreach ($units as $unit) {
        if (!empty($unit['imei']) || !empty($unit['serial'])) {
            $hasSerials = true;
            break;
        }
    }
    
    // Get product details
    $productStmt = $conn->prepare("SELECT ProductName, TotalQuantity, AvailableQuantity, CostPrice, SellingPrice 
                                   FROM Products WHERE ProductID = ? AND Branch = ?");
    $productStmt->execute([$productId, $currentBranch]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $newUnitsCount = count($units);
        
        if ($hasSerials) {
            // ===== PER UNIT MODE: Add each unit as a separate row in ProductUnits with branch =====
            // Get current max unit number
            $maxUnitStmt = $conn->prepare("SELECT ISNULL(MAX(UnitNumber), 0) as MaxUnit FROM ProductUnits WHERE ProductID = ? AND Branch = ?");
            $maxUnitStmt->execute([$productId, $currentBranch]);
            $maxUnit = $maxUnitStmt->fetchColumn();
            
            $unitStmt = $conn->prepare("INSERT INTO ProductUnits 
                          (ProductID, UnitNumber, IMEINumber, SerialNumber, Status, CostPrice, SellingPrice, Branch, CreatedBy, CreatedAt)
                          VALUES 
                          (?, ?, ?, ?, 'available', ?, ?, ?, ?, GETDATE())");
            
            $unitNumber = $maxUnit + 1;
            foreach ($units as $unit) {
                $unitStmt->execute([
                    $productId, $unitNumber,
                    $unit['imei'] ?? '',
                    $unit['serial'] ?? '',
                    $product['CostPrice'], $product['SellingPrice'], $currentBranch, $currentUser
                ]);
                $unitNumber++;
            }
            
            // Update product quantities
            $updateStmt = $conn->prepare("UPDATE Products 
                            SET TotalQuantity = ISNULL(TotalQuantity, 0) + ?,
                                AvailableQuantity = ISNULL(AvailableQuantity, 0) + ?,
                                UpdatedAt = GETDATE()
                            WHERE ProductID = ? AND Branch = ?");
            $updateStmt->execute([$newUnitsCount, $newUnitsCount, $productId, $currentBranch]);
            
            $oldStock = $product['TotalQuantity'];
            $newStock = $oldStock + $newUnitsCount;
            
            // Log each unit addition
            $historyStmt = $conn->prepare("INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, InvoiceNo, SupplierName, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)");
            
            $historyStmt->execute([
                $productId, $product['ProductName'], $newUnitsCount, $oldStock, $newStock,
                $product['CostPrice'], $newUnitsCount * $product['CostPrice'], $invoiceNo, $supplierName, $notes,
                $currentUser, $currentBranch
            ]);
            
            $message = "Added {$newUnitsCount} new unit(s) with IMEI/Serial to {$product['ProductName']}";
            
        } else {
            // ===== DIRECT QUANTITY MODE: Just update product quantities (no unit tracking) =====
            $oldStock = $product['TotalQuantity'];
            $newStock = $oldStock + $newUnitsCount;
            
            // Update product quantities only
            $updateStmt = $conn->prepare("UPDATE Products 
                            SET TotalQuantity = ISNULL(TotalQuantity, 0) + ?,
                                AvailableQuantity = ISNULL(AvailableQuantity, 0) + ?,
                                UpdatedAt = GETDATE()
                            WHERE ProductID = ? AND Branch = ?");
            $updateStmt->execute([$newUnitsCount, $newUnitsCount, $productId, $currentBranch]);
            
            // Log stock history
            $historyStmt = $conn->prepare("INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, InvoiceNo, SupplierName, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)");
            
            $historyStmt->execute([
                $productId, $product['ProductName'], $newUnitsCount, $oldStock, $newStock,
                $product['CostPrice'], $newUnitsCount * $product['CostPrice'], $invoiceNo, $supplierName, $notes,
                $currentUser, $currentBranch
            ]);
            
            $message = "Added {$newUnitsCount} unit(s) to {$product['ProductName']} (Direct Quantity)";
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'units_added' => $newUnitsCount,
            'total_units' => $product['TotalQuantity'] + $newUnitsCount,
            'mode' => $hasSerials ? 'per_unit' : 'direct_quantity',
            'branch' => $currentBranch
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
    $productImage = $data['product_image'] ?? null;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    $imagePath = null;
    if ($productImage && !empty($productImage) && strpos($productImage, 'data:image') === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/SIDJAN/uploads/products/';
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
                $imagePath = '/SIDJAN/uploads/products/' . $filename;
            }
        }
    }
    
    try {
        if ($imagePath) {
            $stmt = $conn->prepare("UPDATE Products 
                      SET ProductName = ?, Category = ?, Brand = ?, 
                          CostPrice = ?, SellingPrice = ?, ProductImagePath = ?,
                          Description = ?, UpdatedAt = GETDATE(), UpdatedBy = ?
                      WHERE ProductID = ?");
            $stmt->execute([$productName, $category, $brand, $costPrice, $sellingPrice, $imagePath, $description, $currentUser, $productId]);
        } else {
            $stmt = $conn->prepare("UPDATE Products 
                      SET ProductName = ?, Category = ?, Brand = ?, 
                          CostPrice = ?, SellingPrice = ?, 
                          Description = ?, UpdatedAt = GETDATE(), UpdatedBy = ?
                      WHERE ProductID = ?");
            $stmt->execute([$productName, $category, $brand, $costPrice, $sellingPrice, $description, $currentUser, $productId]);
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
    
    // Check if product has sold units
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM ProductUnits WHERE ProductID = ? AND Status = 'sold'");
    $checkStmt->execute([$productId]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete product with sold units']);
        return;
    }
    
    // Get image path
    $imgStmt = $conn->prepare("SELECT ProductImagePath FROM Products WHERE ProductID = ?");
    $imgStmt->execute([$productId]);
    $product = $imgStmt->fetch(PDO::FETCH_ASSOC);
    
    $conn->beginTransaction();
    
    try {
        $conn->prepare("DELETE FROM ProductUnits WHERE ProductID = ?")->execute([$productId]);
        $conn->prepare("DELETE FROM Products WHERE ProductID = ?")->execute([$productId]);
        
        $conn->commit();
        
        if ($product && $product['ProductImagePath']) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . $product['ProductImagePath'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStockHistory($conn, $currentBranch) {
    $limit = intval($_GET['limit'] ?? 100);
    $productId = $_GET['product_id'] ?? null;
    
    try {
        if ($productId) {
            $query = "SELECT TOP $limit 
                        TransactionID, ProductID, ProductName, QuantityAdded, OldStock, NewStock,
                        CostPrice, TotalCost, InvoiceNo, SupplierName, Notes,
                        FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm:ss') AS TransactionDate,
                        AddedBy
                      FROM StockInHistory
                      WHERE ProductID = ? AND Branch = ?
                      ORDER BY TransactionDate DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute([$productId, $currentBranch]);
        } else {
            $query = "SELECT TOP $limit 
                        TransactionID, ProductID, ProductName, QuantityAdded, OldStock, NewStock,
                        CostPrice, TotalCost, InvoiceNo, SupplierName, Notes,
                        FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm:ss') AS TransactionDate,
                        AddedBy
                      FROM StockInHistory
                      WHERE Branch = ?
                      ORDER BY TransactionDate DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute([$currentBranch]);
        }
        
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
}

function getDashboardStats($conn, $currentBranch) {
    try {
        // Total Products
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Products WHERE Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalProducts = $stmt->fetchColumn();
        
        // Total Stock Value
        $stmt = $conn->prepare("SELECT ISNULL(SUM(ISNULL(TotalQuantity,0) * ISNULL(CostPrice,0)), 0) FROM Products WHERE Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalStockValue = $stmt->fetchColumn();
        
        // Low Stock Count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Products WHERE ISNULL(AvailableQuantity,0) < 10 AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $lowStockCount = $stmt->fetchColumn();
        
        // Total Units Added (30 days)
        $stmt = $conn->prepare("SELECT ISNULL(SUM(QuantityAdded), 0) FROM StockInHistory WHERE TransactionDate >= DATEADD(DAY, -30, GETDATE()) AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalUnitsAdded = $stmt->fetchColumn();
        
        // Total Units In Stock
        $stmt = $conn->prepare("SELECT ISNULL(SUM(ISNULL(TotalQuantity,0)), 0) FROM Products WHERE Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalUnitsInStock = $stmt->fetchColumn();
        
        // Total Sales 30 Days
        $stmt = $conn->prepare("SELECT ISNULL(SUM(TotalAmount), 0) FROM Sales WHERE SaleDate >= DATEADD(DAY, -30, GETDATE()) AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalSales30Days = $stmt->fetchColumn();
        
        // Transactions This Week
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Sales WHERE SaleDate >= DATEADD(DAY, -7, GETDATE()) AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $transactionsThisWeek = $stmt->fetchColumn();
        
        $stats = [
            'TotalProducts' => intval($totalProducts),
            'TotalStockValue' => floatval($totalStockValue),
            'LowStockCount' => intval($lowStockCount),
            'TotalUnitsAdded' => intval($totalUnitsAdded),
            'TotalUnitsInStock' => intval($totalUnitsInStock),
            'TotalSales30Days' => floatval($totalSales30Days),
            'TransactionsThisWeek' => intval($transactionsThisWeek)
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
}

// ============================================
// SALES / TRANSACTION FUNCTIONS
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
        // Insert into Sales table
        $salesQuery = "INSERT INTO Sales 
                      (ReceiptNo, CustomerName, CustomerPhone, TotalAmount, PaymentMethod, 
                       AmountReceived, ChangeAmount, SaleDate, CreatedBy, Status, Branch)
                      VALUES 
                      (?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, 'completed', ?)";
        
        $stmt = $conn->prepare($salesQuery);
        $stmt->execute([
            $receiptNo, $customerName, $customerPhone, $totalAmount, $paymentMethod,
            $amountReceived, $change, $currentUser, $currentBranch
        ]);
        
        $saleId = $conn->lastInsertId();
        
        foreach ($items as $item) {
            // Check if this is a serialized item (has unit_id)
            if (isset($item['unit_id']) && !empty($item['unit_id'])) {
                // ===== SERIALIZED ITEM (UNIT-BASED) =====
                $unitQuery = "SELECT u.UnitID, u.ProductID, u.Status, u.IMEINumber, u.SerialNumber, u.UnitNumber,
                                     p.ProductName, p.SellingPrice, p.CostPrice
                              FROM ProductUnits u
                              INNER JOIN Products p ON u.ProductID = p.ProductID
                              WHERE u.UnitID = ? AND u.Status = 'available' AND p.Branch = ?";
                
                $stmt = $conn->prepare($unitQuery);
                $stmt->execute([$item['unit_id'], $currentBranch]);
                $unit = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$unit) {
                    throw new Exception("Unit not found or already sold");
                }
                
                // Mark the unit as sold
                $updateUnitQuery = "UPDATE ProductUnits 
                                   SET Status = 'sold', 
                                       SoldAt = GETDATE(), 
                                       SoldTo = ?,
                                       SoldBy = ?,
                                       SaleID = ?
                                   WHERE UnitID = ?";
                
                $stmt = $conn->prepare($updateUnitQuery);
                $stmt->execute([
                    $customerName, $currentUser, $saleId, $item['unit_id']
                ]);
                
                // Get current product quantities
                $productQtyQuery = "SELECT AvailableQuantity, TotalQuantity FROM Products WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($productQtyQuery);
                $stmt->execute([$unit['ProductID'], $currentBranch]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $oldAvailable = $product['AvailableQuantity'];
                $newAvailable = $oldAvailable - 1;
                
                // Update product quantities
                $updateProductQuery = "UPDATE Products 
                                       SET AvailableQuantity = ?,
                                           SoldQuantity = ISNULL(SoldQuantity, 0) + 1,
                                           UpdatedAt = GETDATE()
                                       WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($updateProductQuery);
                $stmt->execute([$newAvailable, $unit['ProductID'], $currentBranch]);
                
                // Insert into SaleItems (without UnitNumber, IMEINumber, SerialNumber columns)
                $itemQuery = "INSERT INTO SaleItems 
                             (SaleID, ProductID, ProductCode, ProductName, Quantity, Price, Total, CreatedAt)
                             VALUES 
                             (?, ?, ?, ?, 1, ?, ?, GETDATE())";
                
                $stmt = $conn->prepare($itemQuery);
                $stmt->execute([
                    $saleId,
                    $unit['ProductID'],
                    $item['product_code'] ?? '',
                    $unit['ProductName'],
                    $unit['SellingPrice'],
                    $unit['SellingPrice']
                ]);
                
                // Log stock history with unit info in notes
                $historyQuery = "INSERT INTO StockInHistory 
                                (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                 CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                                VALUES 
                                (?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                
                $stmt = $conn->prepare($historyQuery);
                $stmt->execute([
                    $unit['ProductID'],
                    $unit['ProductName'],
                    -1,
                    $oldAvailable,
                    $newAvailable,
                    $unit['CostPrice'],
                    $unit['CostPrice'],
                    "POS Sale - Unit #{$unit['UnitNumber']} - IMEI: {$unit['IMEINumber']} - Receipt: {$receiptNo}",
                    $currentUser,
                    $currentBranch
                ]);
                
            } else {
                // ===== BULK ITEM (QUANTITY-BASED) =====
                $productQuery = "SELECT ProductID, ProductCode, ProductName, AvailableQuantity, SellingPrice, CostPrice
                                FROM Products 
                                WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($productQuery);
                $stmt->execute([$item['id'], $currentBranch]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Product not found: " . ($item['name'] ?? 'Unknown'));
                }
                
                // Check available quantity
                if ($product['AvailableQuantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: {$product['ProductName']}. Available: {$product['AvailableQuantity']}, Requested: {$item['quantity']}");
                }
                
                $itemQuery = "INSERT INTO SaleItems 
                             (SaleID, ProductID, ProductCode, ProductName, Quantity, Price, Total, CreatedAt)
                             VALUES 
                             (?, ?, ?, ?, ?, ?, ?, GETDATE())";
                
                $stmt = $conn->prepare($itemQuery);
                $stmt->execute([
                    $saleId,
                    $product['ProductID'],
                    $product['ProductCode'],
                    $product['ProductName'],
                    $item['quantity'],
                    $item['price'],
                    $item['total']
                ]);
                
                $oldAvailable = $product['AvailableQuantity'];
                $newAvailable = $oldAvailable - $item['quantity'];
                
                // Update product quantities
                $stockQuery = "UPDATE Products 
                              SET AvailableQuantity = ?,
                                  SoldQuantity = ISNULL(SoldQuantity, 0) + ?,
                                  UpdatedAt = GETDATE()
                              WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($stockQuery);
                $stmt->execute([
                    $newAvailable,
                    $item['quantity'],
                    $product['ProductID'],
                    $currentBranch
                ]);
                
                // Log stock history
                $historyQuery = "INSERT INTO StockInHistory 
                                (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                 CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                                VALUES 
                                (?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                
                $stmt = $conn->prepare($historyQuery);
                $stmt->execute([
                    $product['ProductID'],
                    $product['ProductName'],
                    -$item['quantity'],
                    $oldAvailable,
                    $newAvailable,
                    $item['price'],
                    $item['total'],
                    "POS Sale (Bulk) - Receipt: {$receiptNo}",
                    $currentUser,
                    $currentBranch
                ]);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction saved successfully',
            'receipt_no' => $receiptNo,
            'sale_id' => $saleId
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
                Status,
                Branch
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
    
    // Get sale info with items, and also get unit details from ProductUnits
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
                si.Total,
                pu.UnitNumber,
                pu.IMEINumber,
                pu.SerialNumber
              FROM Sales s
              LEFT JOIN SaleItems si ON s.SaleID = si.SaleID
              LEFT JOIN ProductUnits pu ON pu.SaleID = s.SaleID AND pu.ProductID = si.ProductID
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
                    'Total' => $row['Total'],
                    'UnitNumber' => $row['UnitNumber'] ?? null,
                    'IMEINumber' => $row['IMEINumber'] ?? null,
                    'SerialNumber' => $row['SerialNumber'] ?? null
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
?>