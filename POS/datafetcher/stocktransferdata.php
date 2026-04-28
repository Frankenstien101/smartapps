<?php
// stock_transfer_api.php - Stock Transfer Management API (COMPLETE WORKING VERSION)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    global $currentBranch, $userRole;
    
    switch ($action) {
        case 'getProducts':
            getProductsForTransfer($conn, $currentBranch);
            break;
        case 'getTransfers':
            getTransfers($conn, $currentBranch, $userRole);
            break;
        case 'getTransferById':
            getTransferById($conn);
            break;
        case 'getTransferStats':
            getTransferStats($conn, $currentBranch, $userRole);
            break;
        case 'getBranches':
            getBranches($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'createTransfer':
            createTransfer($conn, $data, $currentUser, $currentBranch);
            break;
        case 'approveTransfer':
            approveTransfer($conn, $data, $currentUser);
            break;
        case 'rejectTransfer':
            rejectTransfer($conn, $data, $currentUser);
            break;
        case 'receiveTransfer':
            receiveTransfer($conn, $data, $currentUser);
            break;
        case 'cancelTransfer':
            cancelTransfer($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// PRODUCT FUNCTIONS
// ============================================

function getProductsForTransfer($conn, $currentBranch) {
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                CurrentStock, 
                SellingPrice,
                ProductImagePath,
                IMEINumber,
                SerialNumber
              FROM Products 
              WHERE Branch = ? AND CurrentStock > 0
              ORDER BY ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentBranch]);
    $products = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

function getBranches($conn, $currentBranch) {
    $query = "SELECT DISTINCT BranchCode,BranchID   FROM Branches WHERE BranchCode IS NOT NULL AND BranchCode != ? ORDER BY BranchID ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentBranch]);
    $branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['success' => true, 'data' => $branches, 'current_branch' => $currentBranch]);
}

// ============================================
// TRANSFER FUNCTIONS
// ============================================

function createTransfer($conn, $data, $currentUser, $currentBranch) {
    $toBranch = $data['to_branch'] ?? '';
    $items = $data['items'] ?? [];
    $notes = $data['notes'] ?? '';
    
    if (empty($toBranch)) {
        echo json_encode(['success' => false, 'message' => 'Destination branch is required']);
        return;
    }
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items to transfer']);
        return;
    }
    
    // Check stock availability
    foreach ($items as $item) {
        $checkQuery = "SELECT CurrentStock, ProductName FROM Products WHERE ProductID = ? AND Branch = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$item['product_id'], $currentBranch]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => "Product not found: {$item['product_name']}"]);
            return;
        }
        
        if ($product['CurrentStock'] < $item['quantity']) {
            echo json_encode(['success' => false, 'message' => "Insufficient stock for {$product['ProductName']}. Available: {$product['CurrentStock']}"]);
            return;
        }
    }
    
    $transferNo = 'TRF-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $conn->beginTransaction();
    
    try {
        // For each item, create a separate transfer record
        foreach ($items as $item) {
            $insertQuery = "INSERT INTO StockTransfers 
                            (TransferNo, FromBranch, ToBranch, Status, RequestedBy, Notes, ProductID, ProductName, ProductCode, Quantity, CreatedAt)
                            VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, GETDATE())";
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->execute([
                $transferNo,
                $currentBranch,
                $toBranch,
                $currentUser,
                $notes,
                $item['product_id'],
                $item['product_name'],
                $item['product_code'] ?? '',
                $item['quantity']
            ]);
            
            $transferId = $conn->lastInsertId();
            
            // Insert transfer items (for reference)
            $itemQuery = "INSERT INTO StockTransferItems 
                          (TransferID, ProductID, ProductName, ProductCode, Quantity, IMEINumber, SerialNumber)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($itemQuery);
            $stmt->execute([
                $transferId,
                $item['product_id'],
                $item['product_name'],
                $item['product_code'] ?? '',
                $item['quantity'],
                $item['imei'] ?? '',
                $item['serial'] ?? ''
            ]);
            
            // Log history
            $historyQuery = "INSERT INTO StockTransferHistory (TransferID, Action, ActionBy, Notes)
                             VALUES (?, 'created', ?, ?)";
            $stmt = $conn->prepare($historyQuery);
            $stmt->execute([$transferId, $currentUser, $notes]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Stock transfer request created successfully',
            'transfer_no' => $transferNo
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getTransfers($conn, $currentBranch, $userRole) {
    $status = $_GET['status'] ?? 'all';
    $limit = 100;
    
    try {
        if ($userRole === 'admin') {
            if ($status === 'all') {
                $query = "SELECT TOP $limit 
                            TransferID, TransferNo, FromBranch, ToBranch, Status,
                            RequestedBy, 
                            CONVERT(VARCHAR, RequestedAt, 120) AS RequestedAt,
                            ProductName, Quantity, Notes,
                            (SELECT COUNT(*) FROM StockTransferItems WHERE TransferID = t.TransferID) AS ItemCount,
                            (SELECT ISNULL(SUM(Quantity), 0) FROM StockTransferItems WHERE TransferID = t.TransferID) AS TotalQuantity
                          FROM StockTransfers t
                          ORDER BY TransferID DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute();
            } else {
                $query = "SELECT TOP $limit 
                            TransferID, TransferNo, FromBranch, ToBranch, Status,
                            RequestedBy, 
                            CONVERT(VARCHAR, RequestedAt, 120) AS RequestedAt,
                            ProductName, Quantity, Notes,
                            (SELECT COUNT(*) FROM StockTransferItems WHERE TransferID = t.TransferID) AS ItemCount,
                            (SELECT ISNULL(SUM(Quantity), 0) FROM StockTransferItems WHERE TransferID = t.TransferID) AS TotalQuantity
                          FROM StockTransfers t
                          WHERE Status = ?
                          ORDER BY TransferID DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute([$status]);
            }
        } else {
            if ($status === 'all') {
                $query = "SELECT TOP $limit 
                            TransferID, TransferNo, FromBranch, ToBranch, Status,
                            RequestedBy, 
                            CONVERT(VARCHAR, RequestedAt, 120) AS RequestedAt,
                            ProductName, Quantity, Notes,
                            (SELECT COUNT(*) FROM StockTransferItems WHERE TransferID = t.TransferID) AS ItemCount,
                            (SELECT ISNULL(SUM(Quantity), 0) FROM StockTransferItems WHERE TransferID = t.TransferID) AS TotalQuantity
                          FROM StockTransfers t
                          WHERE FromBranch = ? OR ToBranch = ?
                          ORDER BY TransferID DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute([$currentBranch, $currentBranch]);
            } else {
                $query = "SELECT TOP $limit 
                            TransferID, TransferNo, FromBranch, ToBranch, Status,
                            RequestedBy, 
                            CONVERT(VARCHAR, RequestedAt, 120) AS RequestedAt,
                            ProductName, Quantity, Notes,
                            (SELECT COUNT(*) FROM StockTransferItems WHERE TransferID = t.TransferID) AS ItemCount,
                            (SELECT ISNULL(SUM(Quantity), 0) FROM StockTransferItems WHERE TransferID = t.TransferID) AS TotalQuantity
                          FROM StockTransfers t
                          WHERE (FromBranch = ? OR ToBranch = ?) AND Status = ?
                          ORDER BY TransferID DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute([$currentBranch, $currentBranch, $status]);
            }
        }
        
        $transfers = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $transfers, 'count' => count($transfers)]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
}

function getTransferById($conn) {
    $transferId = $_GET['id'] ?? 0;
    
    if (!$transferId) {
        echo json_encode(['success' => false, 'message' => 'Transfer ID required']);
        return;
    }
    
    // Get main transfer info
    $query = "SELECT 
                t.TransferID, t.TransferNo, t.FromBranch, t.ToBranch, t.Status,
                t.RequestedBy, t.ApprovedBy, t.ReceivedBy,
                t.Notes, t.RejectionReason,
                CONVERT(VARCHAR, t.RequestedAt, 120) AS RequestedAt,
                CONVERT(VARCHAR, t.ApprovedAt, 120) AS ApprovedAt,
                CONVERT(VARCHAR, t.ReceivedAt, 120) AS ReceivedAt
              FROM StockTransfers t
              WHERE t.TransferID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$transferId]);
    $transfer = $stmt->fetch();
    
    if (!$transfer) {
        echo json_encode(['success' => false, 'message' => 'Transfer not found']);
        return;
    }
    
    // Get transfer items
    $itemsQuery = "SELECT 
                    ItemID, ProductID, ProductName, ProductCode, Quantity,
                    IMEINumber, SerialNumber
                  FROM StockTransferItems
                  WHERE TransferID = ?";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->execute([$transferId]);
    $items = $stmt->fetchAll();
    
    // Get history
    $historyQuery = "SELECT 
                      HistoryID, Action, ActionBy, 
                      CONVERT(VARCHAR, ActionAt, 120) AS ActionAt, 
                      Notes
                    FROM StockTransferHistory
                    WHERE TransferID = ?
                    ORDER BY HistoryID ASC";
    
    $stmt = $conn->prepare($historyQuery);
    $stmt->execute([$transferId]);
    $history = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'data' => $transfer, 
        'items' => $items,
        'history' => $history
    ]);
}

function approveTransfer($conn, $data, $currentUser) {
    $transferId = $data['transfer_id'] ?? 0;
    
    if (!$transferId) {
        echo json_encode(['success' => false, 'message' => 'Transfer ID required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $updateQuery = "UPDATE StockTransfers 
                        SET Status = 'approved', 
                            ApprovedBy = ?, 
                            ApprovedAt = GETDATE()
                        WHERE TransferID = ? AND Status = 'pending'";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([$currentUser, $transferId]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception('Transfer not found or already processed');
        }
        
        $historyQuery = "INSERT INTO StockTransferHistory (TransferID, Action, ActionBy)
                         VALUES (?, 'approved', ?)";
        $stmt = $conn->prepare($historyQuery);
        $stmt->execute([$transferId, $currentUser]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Transfer approved successfully']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function rejectTransfer($conn, $data, $currentUser) {
    $transferId = $data['transfer_id'] ?? 0;
    $reason = $data['reason'] ?? '';
    
    if (!$transferId) {
        echo json_encode(['success' => false, 'message' => 'Transfer ID required']);
        return;
    }
    
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $updateQuery = "UPDATE StockTransfers 
                        SET Status = 'rejected', 
                            RejectionReason = ?
                        WHERE TransferID = ? AND Status = 'pending'";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([$reason, $transferId]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception('Transfer not found or already processed');
        }
        
        $historyQuery = "INSERT INTO StockTransferHistory (TransferID, Action, ActionBy, Notes)
                         VALUES (?, 'rejected', ?, ?)";
        $stmt = $conn->prepare($historyQuery);
        $stmt->execute([$transferId, $currentUser, $reason]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Transfer rejected']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function receiveTransfer($conn, $data, $currentUser) {
    $transferId = $data['transfer_id'] ?? 0;
    
    if (!$transferId) {
        echo json_encode(['success' => false, 'message' => 'Transfer ID required']);
        return;
    }
    
    // Get transfer details with items
    $getQuery = "SELECT 
                    t.TransferID, t.TransferNo, t.FromBranch, t.ToBranch, t.Status,
                    ti.ProductID, ti.ProductName, ti.ProductCode, ti.Quantity, 
                    ti.IMEINumber, ti.SerialNumber
                  FROM StockTransfers t
                  INNER JOIN StockTransferItems ti ON t.TransferID = ti.TransferID
                  WHERE t.TransferID = ? AND t.Status = 'approved'";
    
    $stmt = $conn->prepare($getQuery);
    $stmt->execute([$transferId]);
    $transfers = $stmt->fetchAll();
    
    if (empty($transfers)) {
        echo json_encode(['success' => false, 'message' => 'Transfer not found or not approved']);
        return;
    }
    
    $transfer = $transfers[0]; // First row has branch info
    
    $conn->beginTransaction();
    
    try {
        foreach ($transfers as $item) {
            // Check if product exists in destination branch
            $checkProductQuery = "SELECT ProductID, CurrentStock, ProductCode, ProductName, 
                                         Category, Brand, CostPrice, SellingPrice, 
                                         ProductImagePath, Description
                                  FROM Products 
                                  WHERE ProductCode = ? AND Branch = ?";
            
            $stmt = $conn->prepare($checkProductQuery);
            $stmt->execute([$item['ProductCode'], $transfer['ToBranch']]);
            $existingProduct = $stmt->fetch();
            
            if ($existingProduct) {
                // PRODUCT EXISTS - UPDATE STOCK QUANTITY
                $updateStockQuery = "UPDATE Products 
                                     SET CurrentStock = CurrentStock + ?, 
                                         UpdatedAt = GETDATE()
                                     WHERE ProductID = ?";
                
                $stmt = $conn->prepare($updateStockQuery);
                $stmt->execute([$item['Quantity'], $existingProduct['ProductID']]);
                
                // Log stock update
                $logQuery = "INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (?, ?, ?, ?, ?, ?, ?, 'Stock Transfer Received', GETDATE(), ?, ?)";
                
                $oldStock = $existingProduct['CurrentStock'];
                $newStock = $oldStock + $item['Quantity'];
                
                $stmt = $conn->prepare($logQuery);
                $stmt->execute([
                    $existingProduct['ProductID'],
                    $item['ProductName'],
                    $item['Quantity'],
                    $oldStock,
                    $newStock,
                    $existingProduct['CostPrice'],
                    $item['Quantity'] * $existingProduct['CostPrice'],
                    $currentUser,
                    $transfer['ToBranch']
                ]);
                
            } else {
                // PRODUCT DOES NOT EXIST - CREATE NEW PRODUCT
                // Get full product details from source branch
                $sourceProductQuery = "SELECT ProductCode, ProductName, Category, Brand, 
                                              CostPrice, SellingPrice, ProductImagePath, 
                                              IMEINumber, SerialNumber, Description
                                       FROM Products 
                                       WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($sourceProductQuery);
                $stmt->execute([$item['ProductID'], $transfer['FromBranch']]);
                $sourceProduct = $stmt->fetch();
                
                if ($sourceProduct) {
                    // Insert new product in destination branch with received quantity
                    $insertProductQuery = "INSERT INTO Products 
                                          (ProductCode, ProductName, Category, Brand, CurrentStock, 
                                           CostPrice, SellingPrice, ProductImagePath, IMEINumber, 
                                           SerialNumber, Description, Branch, CreatedAt, UpdatedAt, CreatedBy)
                                          VALUES 
                                          (?, ?, ?, ?, ?,
                                           ?, ?, ?, ?, ?,
                                           ?, ?, GETDATE(), GETDATE(), ?)";
                    
                    $stmt = $conn->prepare($insertProductQuery);
                    $stmt->execute([
                        $sourceProduct['ProductCode'],
                        $sourceProduct['ProductName'],
                        $sourceProduct['Category'],
                        $sourceProduct['Brand'],
                        $item['Quantity'],
                        $sourceProduct['CostPrice'],
                        $sourceProduct['SellingPrice'],
                        $sourceProduct['ProductImagePath'],
                        $item['IMEINumber'] ?: $sourceProduct['IMEINumber'],
                        $item['SerialNumber'] ?: $sourceProduct['SerialNumber'],
                        $sourceProduct['Description'],
                        $transfer['ToBranch'],
                        $currentUser
                    ]);
                    
                    $newProductId = $conn->lastInsertId();
                    
                    // Log new product creation
                    $logQuery = "INSERT INTO StockInHistory 
                                (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                 CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                                VALUES 
                                (?, ?, ?, 0, ?, ?, ?, 'New product from stock transfer', GETDATE(), ?, ?)";
                    
                    $stmt = $conn->prepare($logQuery);
                    $stmt->execute([
                        $newProductId,
                        $item['ProductName'],
                        $item['Quantity'],
                        $item['Quantity'],
                        $sourceProduct['CostPrice'],
                        $item['Quantity'] * $sourceProduct['CostPrice'],
                        $currentUser,
                        $transfer['ToBranch']
                    ]);
                }
            }
            
            // DEDUCT STOCK FROM SOURCE BRANCH (always do this)
            $deductStockQuery = "UPDATE Products 
                                 SET CurrentStock = CurrentStock - ?
                                 WHERE ProductID = ? AND Branch = ?";
            
            $stmt = $conn->prepare($deductStockQuery);
            $stmt->execute([$item['Quantity'], $item['ProductID'], $transfer['FromBranch']]);
            
            // Log deduction from source branch
            $sourceCheckQuery = "SELECT CurrentStock, ProductName, CostPrice 
                                FROM Products 
                                WHERE ProductID = ? AND Branch = ?";
            $stmt = $conn->prepare($sourceCheckQuery);
            $stmt->execute([$item['ProductID'], $transfer['FromBranch']]);
            $sourceProductAfter = $stmt->fetch();
            
            if ($sourceProductAfter) {
                $deductLogQuery = "INSERT INTO StockInHistory 
                                  (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                   CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                                  VALUES 
                                  (?, ?, ?, ?, ?, ?, ?, 'Stock Transfer Sent', GETDATE(), ?, ?)";
                
                $oldStock = $sourceProductAfter['CurrentStock'] + $item['Quantity'];
                $stmt = $conn->prepare($deductLogQuery);
                $stmt->execute([
                    $item['ProductID'],
                    $item['ProductName'],
                    -$item['Quantity'],
                    $oldStock,
                    $sourceProductAfter['CurrentStock'],
                    $sourceProductAfter['CostPrice'],
                    $item['Quantity'] * $sourceProductAfter['CostPrice'],
                    $currentUser,
                    $transfer['FromBranch']
                ]);
            }
        }
        
        // Update transfer status to completed
        $updateQuery = "UPDATE StockTransfers 
                        SET Status = 'completed', 
                            ReceivedBy = ?, 
                            ReceivedAt = GETDATE()
                        WHERE TransferID = ?";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([$currentUser, $transferId]);
        
        // Log history
        $historyQuery = "INSERT INTO StockTransferHistory (TransferID, Action, ActionBy)
                         VALUES (?, 'received', ?)";
        $stmt = $conn->prepare($historyQuery);
        $stmt->execute([$transferId, $currentUser]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Stock transfer received and inventory updated successfully'
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function cancelTransfer($conn, $data, $currentUser) {
    $transferId = $data['transfer_id'] ?? 0;
    $reason = $data['reason'] ?? '';
    
    if (!$transferId) {
        echo json_encode(['success' => false, 'message' => 'Transfer ID required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $updateQuery = "UPDATE StockTransfers 
                        SET Status = 'cancelled'
                        WHERE TransferID = ? AND Status IN ('pending', 'approved')";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([$transferId]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception('Transfer not found or cannot be cancelled');
        }
        
        $historyQuery = "INSERT INTO StockTransferHistory (TransferID, Action, ActionBy, Notes)
                         VALUES (?, 'cancelled', ?, ?)";
        $stmt = $conn->prepare($historyQuery);
        $stmt->execute([$transferId, $currentUser, $reason]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Transfer cancelled']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getTransferStats($conn, $currentBranch, $userRole) {
    try {
        if ($userRole === 'admin') {
            $totalStmt = $conn->query("SELECT COUNT(*) FROM StockTransfers");
            $total = $totalStmt->fetchColumn();
            
            $pendingStmt = $conn->query("SELECT COUNT(*) FROM StockTransfers WHERE Status = 'pending'");
            $pending = $pendingStmt->fetchColumn();
            
            $completedStmt = $conn->query("SELECT COUNT(*) FROM StockTransfers WHERE Status = 'completed'");
            $completed = $completedStmt->fetchColumn();
            
            $itemsStmt = $conn->query("SELECT ISNULL(SUM(Quantity), 0) FROM StockTransfers WHERE Status = 'completed'");
            $items = $itemsStmt->fetchColumn();
            
        } else {
            $totalStmt = $conn->prepare("SELECT COUNT(*) FROM StockTransfers WHERE FromBranch = ? OR ToBranch = ?");
            $totalStmt->execute([$currentBranch, $currentBranch]);
            $total = $totalStmt->fetchColumn();
            
            $pendingStmt = $conn->prepare("SELECT COUNT(*) FROM StockTransfers WHERE (FromBranch = ? OR ToBranch = ?) AND Status = 'pending'");
            $pendingStmt->execute([$currentBranch, $currentBranch]);
            $pending = $pendingStmt->fetchColumn();
            
            $completedStmt = $conn->prepare("SELECT COUNT(*) FROM StockTransfers WHERE (FromBranch = ? OR ToBranch = ?) AND Status = 'completed'");
            $completedStmt->execute([$currentBranch, $currentBranch]);
            $completed = $completedStmt->fetchColumn();
            
            $itemsStmt = $conn->prepare("SELECT ISNULL(SUM(Quantity), 0) FROM StockTransfers WHERE (FromBranch = ? OR ToBranch = ?) AND Status = 'completed'");
            $itemsStmt->execute([$currentBranch, $currentBranch]);
            $items = $itemsStmt->fetchColumn();
        }
        
        $stats = [
            'TotalTransfers' => intval($total),
            'PendingCount' => intval($pending),
            'ApprovedCount' => 0,
            'CompletedCount' => intval($completed),
            'RejectedCount' => 0,
            'CancelledCount' => 0,
            'TotalItemsTransferred' => intval($items)
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
}
?>