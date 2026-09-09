<?php
// sales_api.php - Sales Transactions API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include database connection
include '../DB/dbcon.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Start session for user tracking
session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Isulan Branch';
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
    global $currentBranch, $userRole;
    
    switch ($action) {
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
            echo json_encode(['error' => 'Invalid action: ' . $action]);
            break;
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
            break;
    }
}

function getSales($conn, $currentBranch, $userRole) {
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $limit = max(1, min(intval($_GET['limit'] ?? 200), 1000));
    
    $branchFilter = "";
    $dateFilter = "";
    $params = [];
    
    if ($userRole !== 'admin') {
        $branchFilter = "AND Branch = :branch";
        $params[':branch'] = $currentBranch;
    }
    
    if (!empty($startDate)) {
        $dateFilter .= " AND SaleDate >= :start_datetime";
        $params[':start_datetime'] = $startDate . ' 00:00:00';
    }
    
    if (!empty($endDate)) {
        // Use an exclusive next-day boundary so rows with fractional seconds on
        // the end date are included as well.
        $dateFilter .= " AND SaleDate < DATEADD(DAY, 1, CAST(:end_date AS date))";
        $params[':end_date'] = $endDate;
    }
    
    // A selected date range must return the complete range. TOP was previously
    // applied before client-side pagination and silently discarded older sales.
    $topClause = (!empty($startDate) || !empty($endDate)) ? '' : "TOP $limit ";

    $query = "SELECT $topClause
                SaleID, 
                ReceiptNo, 
                CustomerName, 
                CustomerPhone, 
                TotalAmount, 
                PaymentMethod, 
                AmountReceived, 
                ChangeAmount,
                CONVERT(VARCHAR(10), SaleDate, 120) AS SaleDate,
                CreatedBy,
                Status,
                Branch
              FROM Sales 
              WHERE 1=1 $branchFilter $dateFilter
              ORDER BY SaleDate DESC";
    
    $stmt = $conn->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
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
                CONVERT(VARCHAR(10), s.SaleDate, 120) AS SaleDate,
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
                      (?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, 'completed', ?)";
        
        $stmt = $conn->prepare($salesQuery);
        $stmt->execute([
            $receiptNo, $customerName, $customerPhone, $totalAmount, $paymentMethod,
            $amountReceived, $change, $currentUser, $currentBranch
        ]);
        
        $saleId = $conn->lastInsertId();
        
        foreach ($items as $item) {
            if (isset($item['unit_id']) && !empty($item['unit_id'])) {
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
                
                $productQtyQuery = "SELECT AvailableQuantity, TotalQuantity FROM Products WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($productQtyQuery);
                $stmt->execute([$unit['ProductID'], $currentBranch]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $oldAvailable = $product['AvailableQuantity'];
                $newAvailable = $oldAvailable - 1;
                
                $updateProductQuery = "UPDATE Products 
                                       SET AvailableQuantity = ?,
                                           SoldQuantity = ISNULL(SoldQuantity, 0) + 1,
                                           UpdatedAt = GETDATE()
                                       WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($updateProductQuery);
                $stmt->execute([$newAvailable, $unit['ProductID'], $currentBranch]);
                
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
                $productQuery = "SELECT ProductID, ProductCode, ProductName, AvailableQuantity, SellingPrice, CostPrice
                                FROM Products 
                                WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($productQuery);
                $stmt->execute([$item['id'], $currentBranch]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Product not found: " . ($item['name'] ?? 'Unknown'));
                }
                
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
?>
