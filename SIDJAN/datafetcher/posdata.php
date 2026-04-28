<?php
// posdata.php - Backend API for POS Transactions (Separate from Product Management)
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
            getProductsForPOS($conn, $currentBranch);
            break;
        case 'getSaleById':
            getSaleById($conn, $currentBranch);
            break;
        case 'getTodaySales':
            getTodaySales($conn, $currentBranch);
            break;
        case 'getSales':
            getSales($conn, $currentBranch, $userRole);
            break;
        case 'getReceipt':
            getReceipt($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'saveTransaction':
            saveTransaction($conn, $data, $currentUser, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// PRODUCT FUNCTIONS FOR POS
// ============================================

function getProductsForPOS($conn, $currentBranch) {
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT 
                p.ProductID, 
                p.ProductCode, 
                p.ProductName, 
                p.Category, 
                p.Brand, 
                p.CostPrice, 
                p.SellingPrice,
                p.ProductImagePath,
                ISNULL(p.AvailableQuantity, 0) as AvailableQuantity,
                ISNULL(p.TotalQuantity, 0) as TotalQuantity
              FROM Products p
              WHERE p.Branch = ? AND p.AvailableQuantity > 0";
    
    $params = [$currentBranch];
    
    if ($search !== '') {
        $query .= " AND (p.ProductName LIKE ? OR p.ProductCode LIKE ? OR p.Brand LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY p.ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Get units for serialized products
    foreach ($products as &$product) {
        $unitQuery = "SELECT 
                        UnitID, 
                        UnitNumber, 
                        IMEINumber, 
                        SerialNumber, 
                        Status 
                      FROM ProductUnits 
                      WHERE ProductID = ? AND Branch = ? AND Status = 'available'
                      ORDER BY UnitNumber";
        
        $unitStmt = $conn->prepare($unitQuery);
        $unitStmt->execute([$product['ProductID'], $currentBranch]);
        $units = $unitStmt->fetchAll();
        
        $product['Units'] = $units;
        $product['HasUnits'] = count($units) > 0;
        
        // For products with units, override AvailableQuantity with unit count
        if ($product['HasUnits']) {
            $product['AvailableQuantity'] = count($units);
        }
    }
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

// ============================================
// TRANSACTION FUNCTIONS
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
                $unit = $stmt->fetch();
                
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
                $product = $stmt->fetch();
                
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
                
                // Insert into SaleItems
                $itemQuery = "INSERT INTO SaleItems 
                             (SaleID, ProductID, ProductCode, ProductName, Quantity, Price, Total, 
                              UnitNumber, IMEINumber, SerialNumber, CreatedAt)
                             VALUES 
                             (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, GETDATE())";
                
                $stmt = $conn->prepare($itemQuery);
                $stmt->execute([
                    $saleId,
                    $unit['ProductID'],
                    $item['product_code'] ?? '',
                    $unit['ProductName'],
                    $unit['SellingPrice'],
                    $unit['SellingPrice'],
                    $unit['UnitNumber'],
                    $unit['IMEINumber'] ?? '',
                    $unit['SerialNumber'] ?? ''
                ]);
                
                // Log stock history
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
                    "POS Sale - Unit #{$unit['UnitNumber']} - Receipt: {$receiptNo}",
                    $currentUser,
                    $currentBranch
                ]);
                
            } else {
                // ===== BULK ITEM (QUANTITY-BASED) =====
                $productQuery = "SELECT ProductID, ProductCode, ProductName, AvailableQuantity, SellingPrice, CostPrice
                                FROM Products 
                                WHERE (ProductID = ? OR ProductCode = ?) AND Branch = ?";
                
                $stmt = $conn->prepare($productQuery);
                $stmt->execute([$item['id'], $item['product_code'] ?? '', $currentBranch]);
                $product = $stmt->fetch();
                
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
    $limit = intval($_GET['limit'] ?? 100);
    $status = $_GET['status'] ?? 'all';
    
    try {
        $statusFilter = $status !== 'all' ? "AND Status = :status" : "";
        $branchFilter = $userRole !== 'admin' ? "AND Branch = :branch" : "";
        
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
                  WHERE 1=1 $branchFilter $statusFilter
                  ORDER BY SaleDate DESC";
        
        $stmt = $conn->prepare($query);
        
        if ($userRole !== 'admin') {
            $stmt->bindParam(':branch', $currentBranch);
        }
        if ($status !== 'all') {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        $sales = $stmt->fetchAll();
        
        // Get item counts for each sale
        foreach ($sales as &$sale) {
            $itemCountQuery = "SELECT COUNT(*) as item_count, ISNULL(SUM(Quantity), 0) as total_quantity 
                              FROM SaleItems WHERE SaleID = ?";
            $stmt = $conn->prepare($itemCountQuery);
            $stmt->execute([$sale['SaleID']]);
            $counts = $stmt->fetch();
            $sale['ItemCount'] = $counts['item_count'];
            $sale['TotalQuantity'] = $counts['total_quantity'];
        }
        
        echo json_encode(['success' => true, 'data' => $sales, 'count' => count($sales)]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
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
                s.Status,
                si.SaleItemID,
                si.ProductCode,
                si.ProductName, 
                si.Quantity, 
                si.Price, 
                si.Total,
                si.UnitNumber,
                si.IMEINumber,
                si.SerialNumber
              FROM Sales s
              LEFT JOIN SaleItems si ON s.SaleID = si.SaleID
              WHERE s.SaleID = :id AND s.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $saleId, ':branch' => $currentBranch]);
    $sale = $stmt->fetchAll();
    
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
                'CreatedBy' => $sale[0]['CreatedBy'],
                'Status' => $sale[0]['Status']
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
                    'UnitNumber' => $row['UnitNumber'],
                    'IMEINumber' => $row['IMEINumber'],
                    'SerialNumber' => $row['SerialNumber']
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
    $result = $stmt->fetch();
    
    echo json_encode(['success' => true, 'data' => $result]);
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
                si.Total,
                si.UnitNumber,
                si.IMEINumber,
                si.SerialNumber
              FROM Sales s
              LEFT JOIN SaleItems si ON s.SaleID = si.SaleID
              WHERE s.ReceiptNo = :receipt AND s.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':receipt' => $receiptNo, ':branch' => $currentBranch]);
    $sale = $stmt->fetchAll();
    
    if ($sale && count($sale) > 0) {
        echo json_encode(['success' => true, 'data' => $sale]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
    }
}
?>