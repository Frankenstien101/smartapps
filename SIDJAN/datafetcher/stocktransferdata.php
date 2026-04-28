<?php
// stock_transfer_api.php - Stock Transfer Management API (UPDATED WITH BULK SUPPORT)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
//    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
//    exit();
//}

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
    
    // Get all product IDs for batch unit fetching
    $productIds = array_column($products, 'ProductID');
    
    if (!empty($productIds)) {
        // Batch fetch units for all products
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        // Get unit count and details
        $unitQuery = "SELECT ProductID, COUNT(*) as unit_count, 
                             STRING_AGG(CONVERT(NVARCHAR, UnitNumber), ',') as unit_numbers
                      FROM ProductUnits 
                      WHERE ProductID IN ($placeholders) AND Branch = ? AND Status = 'available'
                      GROUP BY ProductID";
        
        $unitStmt = $conn->prepare($unitQuery);
        $params = array_merge($productIds, [$currentBranch]);
        $unitStmt->execute($params);
        $unitData = $unitStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create lookup array
        $unitLookup = [];
        foreach ($unitData as $unit) {
            $unitLookup[$unit['ProductID']] = $unit;
        }
        
        // Also fetch individual units for display
        $unitDetailsQuery = "SELECT ProductID, UnitNumber, IMEINumber, SerialNumber
                            FROM ProductUnits 
                            WHERE ProductID IN ($placeholders) AND Branch = ? AND Status = 'available'
                            ORDER BY ProductID, UnitNumber";
        
        $unitDetailsStmt = $conn->prepare($unitDetailsQuery);
        $unitDetailsStmt->execute($params);
        $allUnits = $unitDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group units by product
        $unitsByProduct = [];
        foreach ($allUnits as $unit) {
            $pid = $unit['ProductID'];
            if (!isset($unitsByProduct[$pid])) {
                $unitsByProduct[$pid] = [];
            }
            $unitsByProduct[$pid][] = $unit;
        }
        
        // Attach unit info to products
        foreach ($products as &$product) {
            $pid = $product['ProductID'];
            if (isset($unitLookup[$pid]) && $unitLookup[$pid]['unit_count'] > 0) {
                $product['HasUnits'] = true;
                $product['UnitCount'] = $unitLookup[$pid]['unit_count'];
                $product['IsSerialized'] = true;
                // Override AvailableQuantity with actual unit count for serialized products
                $product['AvailableQuantity'] = $unitLookup[$pid]['unit_count'];
                $product['CurrentStock'] = $unitLookup[$pid]['unit_count'];
                $product['Units'] = $unitsByProduct[$pid] ?? [];
            } else {
                $product['HasUnits'] = false;
                $product['UnitCount'] = 0;
                $product['IsSerialized'] = false;
                $product['Units'] = [];
                $product['CurrentStock'] = $product['AvailableQuantity'];
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

function getBranches($conn, $currentBranch) {
    $query = "SELECT DISTINCT BranchCode FROM Branches WHERE BranchCode IS NOT NULL AND BranchCode != ? ORDER BY BranchCode ASC";
    
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
        $productId = $item['product_id'];
        $requestedQty = $item['quantity'];
        $isBulk = $item['is_bulk'] ?? true;
        $unitIds = $item['units'] ?? [];
        
        if ($isBulk) {
            $checkQuery = "SELECT AvailableQuantity as CurrentStock, ProductName FROM Products WHERE ProductID = ? AND Branch = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute([$productId, $currentBranch]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => "Product not found: {$item['product_name']}"]);
                return;
            }
            
            if ($product['CurrentStock'] < $requestedQty) {
                echo json_encode(['success' => false, 'message' => "Insufficient stock for {$product['ProductName']}. Available: {$product['CurrentStock']}, Requested: {$requestedQty}"]);
                return;
            }
        } else {
            if (empty($unitIds)) {
                echo json_encode(['success' => false, 'message' => "No units selected for {$item['product_name']}"]);
                return;
            }
            
            $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
            $checkUnitsQuery = "SELECT COUNT(*) as count FROM ProductUnits 
                               WHERE UnitID IN ($placeholders) AND Branch = ? AND Status = 'available'";
            $stmt = $conn->prepare($checkUnitsQuery);
            $params = array_merge($unitIds, [$currentBranch]);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if ($result['count'] != count($unitIds)) {
                echo json_encode(['success' => false, 'message' => "Some units are not available for {$item['product_name']}"]);
                return;
            }
        }
    }
    
    $transferNo = 'TRF-' . date('YmdHis') . '-' . rand(100, 999);
    
    $conn->beginTransaction();
    
    try {
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $isBulk = $item['is_bulk'] ?? true;
            $unitIds = $item['units'] ?? [];
            
            // Get product details
            $productQuery = "SELECT ProductName, ProductCode, SellingPrice FROM Products WHERE ProductID = ?";
            $stmt = $conn->prepare($productQuery);
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            // Insert into StockTransfers
            $insertQuery = "INSERT INTO StockTransfers 
                            (TransferNo, FromBranch, ToBranch, Status, RequestedBy, Notes, 
                             ProductID, ProductName, ProductCode, Quantity, CreatedAt)
                            VALUES 
                            (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, GETDATE())";
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->execute([
                $transferNo,
                $currentBranch,
                $toBranch,
                $currentUser,
                $notes,
                $productId,
                $product['ProductName'],
                $product['ProductCode'],
                $quantity
            ]);
            
            $transferId = $conn->lastInsertId();
            
            if (!$isBulk && !empty($unitIds)) {
                // FOR SERIALIZED ITEMS: Get unit details and save to transfer items
                $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
                $getUnitsQuery = "SELECT UnitID, UnitNumber, IMEINumber, SerialNumber 
                                 FROM ProductUnits 
                                 WHERE UnitID IN ($placeholders) AND Branch = ?";
                $stmt = $conn->prepare($getUnitsQuery);
                $params = array_merge($unitIds, [$currentBranch]);
                $stmt->execute($params);
                $units = $stmt->fetchAll();
                
                // Debug: Check if units were found
                error_log("Units found for transfer: " . json_encode($units));
                
                foreach ($units as $unit) {
                    $itemQuery = "INSERT INTO StockTransferItems 
                                  (TransferID, ProductID, ProductName, ProductCode, Quantity, 
                                   UnitNumber, IMEINumber, SerialNumber)
                                  VALUES (?, ?, ?, ?, 1, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($itemQuery);
                    $stmt->execute([
                        $transferId,
                        $productId,
                        $product['ProductName'],
                        $product['ProductCode'],
                        $unit['UnitNumber'],
                        $unit['IMEINumber'] ?? '',
                        $unit['SerialNumber'] ?? ''
                    ]);
                }
            } else {
                // FOR BULK ITEMS
                $itemQuery = "INSERT INTO StockTransferItems 
                              (TransferID, ProductID, ProductName, ProductCode, Quantity)
                              VALUES (?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($itemQuery);
                $stmt->execute([
                    $transferId,
                    $productId,
                    $product['ProductName'],
                    $product['ProductCode'],
                    $quantity
                ]);
            }
            
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
            'transfer_no' => $transferNo,
            'item_count' => count($items)
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
    
    try {
        // Get main transfer info
        $query = "SELECT 
                    TransferID, TransferNo, FromBranch, ToBranch, Status,
                    RequestedBy, ApprovedBy, ReceivedBy,
                    Notes, RejectionReason,
                    CONVERT(VARCHAR, RequestedAt, 120) AS RequestedAt,
                    CONVERT(VARCHAR, ApprovedAt, 120) AS ApprovedAt,
                    CONVERT(VARCHAR, ReceivedAt, 120) AS ReceivedAt
                  FROM StockTransfers 
                  WHERE TransferID = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$transferId]);
        $transfer = $stmt->fetch();
        
        if (!$transfer) {
            echo json_encode(['success' => false, 'message' => 'Transfer not found']);
            return;
        }
        
        // Get transfer items
        $itemsQuery = "SELECT 
                        ItemID, TransferID, ProductID, ProductName, ProductCode, Quantity
                      FROM StockTransferItems 
                      WHERE TransferID = ?";
        
        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute([$transferId]);
        $items = $stmt->fetchAll();
        
        // Get history
        $historyQuery = "SELECT 
                          HistoryID, TransferID, Action, ActionBy, Notes,
                          CONVERT(VARCHAR, ActionAt, 120) AS ActionAt
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
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
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
    
    $getQuery = "SELECT 
                    t.TransferID, t.TransferNo, t.FromBranch, t.ToBranch, t.Status,
                    ti.ItemID, ti.ProductID, ti.ProductName, ti.ProductCode, ti.Quantity,
                    ti.UnitNumber, ti.IMEINumber, ti.SerialNumber
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
    
    $transfer = $transfers[0];
    
    $conn->beginTransaction();
    
    try {
        foreach ($transfers as $item) {
            $isSerialized = !empty($item['UnitNumber']);
            
            // First, check if product already exists in destination branch by ProductCode
            $checkDestProductQuery = "SELECT ProductID, ProductCode, AvailableQuantity 
                                     FROM Products 
                                     WHERE ProductCode = ? AND Branch = ?";
            $stmt = $conn->prepare($checkDestProductQuery);
            $stmt->execute([$item['ProductCode'], $transfer['ToBranch']]);
            $destProduct = $stmt->fetch();
            
            // If product doesn't exist, create it
            if (!$destProduct) {
                $getSourceProductQuery = "SELECT ProductCode, ProductName, Category, Brand, CostPrice, SellingPrice 
                                         FROM Products WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($getSourceProductQuery);
                $stmt->execute([$item['ProductID'], $transfer['FromBranch']]);
                $sourceProduct = $stmt->fetch();
                
                $createProductQuery = "INSERT INTO Products 
                                    (ProductCode, ProductName, Category, Brand, AvailableQuantity,
                                     TotalQuantity, SoldQuantity, CostPrice, SellingPrice, Branch, CreatedAt, UpdatedAt)
                                    VALUES 
                                    (?, ?, ?, ?, 0, 0, 0, ?, ?, ?, GETDATE(), GETDATE())";
                $stmt = $conn->prepare($createProductQuery);
                $stmt->execute([
                    $sourceProduct['ProductCode'],
                    $sourceProduct['ProductName'],
                    $sourceProduct['Category'],
                    $sourceProduct['Brand'],
                    $sourceProduct['CostPrice'],
                    $sourceProduct['SellingPrice'],
                    $transfer['ToBranch']
                ]);
                
                $newProductId = $conn->lastInsertId();
            } else {
                $newProductId = $destProduct['ProductID'];
            }
            
            if ($isSerialized) {
                // ===== SERIALIZED ITEM TRANSFER =====
                // Check if unit already exists in destination
                $checkUnitQuery = "SELECT UnitID FROM ProductUnits 
                                  WHERE UnitNumber = ? AND ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($checkUnitQuery);
                $stmt->execute([$item['UnitNumber'], $newProductId, $transfer['ToBranch']]);
                $existingUnit = $stmt->fetch();
                
                if (!$existingUnit) {
                    // Create unit in destination branch
                    $insertUnitQuery = "INSERT INTO ProductUnits 
                                       (ProductID, UnitNumber, IMEINumber, SerialNumber, Status, 
                                        CostPrice, SellingPrice, Branch, CreatedBy, CreatedAt, TransferredFrom)
                                       VALUES 
                                       (?, ?, ?, ?, 'available', 
                                        (SELECT CostPrice FROM Products WHERE ProductID = ?),
                                        (SELECT SellingPrice FROM Products WHERE ProductID = ?),
                                        ?, ?, GETDATE(), ?)";
                    
                    $stmt = $conn->prepare($insertUnitQuery);
                    $stmt->execute([
                        $newProductId,
                        $item['UnitNumber'],
                        $item['IMEINumber'] ?? '',
                        $item['SerialNumber'] ?? '',
                        $newProductId,
                        $newProductId,
                        $transfer['ToBranch'],
                        $currentUser,
                        $transfer['FromBranch']
                    ]);
                }
                
                // Mark source unit as transferred
                $updateSourceUnitQuery = "UPDATE ProductUnits 
                                         SET Status = 'transferred', 
                                             TransferredTo = ?, 
                                             TransferredAt = GETDATE()
                                         WHERE UnitNumber = ? AND ProductID = ? AND Branch = ? AND Status = 'available'";
                $stmt = $conn->prepare($updateSourceUnitQuery);
                $stmt->execute([$transfer['ToBranch'], $item['UnitNumber'], $item['ProductID'], $transfer['FromBranch']]);
                
                // Update product quantity based on unit count
                $updateQtyQuery = "UPDATE Products 
                                  SET AvailableQuantity = (SELECT COUNT(*) FROM ProductUnits WHERE ProductID = ? AND Branch = ? AND Status = 'available'),
                                      TotalQuantity = (SELECT COUNT(*) FROM ProductUnits WHERE ProductID = ? AND Branch = ?)
                                  WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($updateQtyQuery);
                $stmt->execute([
                    $newProductId, $transfer['ToBranch'],
                    $newProductId, $transfer['ToBranch'],
                    $newProductId, $transfer['ToBranch']
                ]);
                
                // Also deduct from source (1 unit)
                $deductSourceQuery = "UPDATE Products 
                                     SET AvailableQuantity = AvailableQuantity - 1,
                                         TotalQuantity = TotalQuantity - 1
                                     WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($deductSourceQuery);
                $stmt->execute([$item['ProductID'], $transfer['FromBranch']]);
                
            } else {
                // ===== BULK ITEM TRANSFER =====
                // Check if this is an existing product that might have been created as serialized before
                // If it has units, don't add bulk stock
                $checkUnitsQuery = "SELECT COUNT(*) as unit_count FROM ProductUnits WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($checkUnitsQuery);
                $stmt->execute([$newProductId, $transfer['ToBranch']]);
                $unitCheck = $stmt->fetch();
                
                if ($unitCheck['unit_count'] > 0) {
                    // Product has units, don't add bulk stock
                    $message = "Product already has units. Skipping bulk stock addition.";
                } else {
                    // Add bulk stock
                    $updateStockQuery = "UPDATE Products 
                                         SET AvailableQuantity = AvailableQuantity + ?,
                                             TotalQuantity = TotalQuantity + ?
                                         WHERE ProductID = ? AND Branch = ?";
                    $stmt = $conn->prepare($updateStockQuery);
                    $stmt->execute([$item['Quantity'], $item['Quantity'], $newProductId, $transfer['ToBranch']]);
                    
                    // Deduct from source
                    $deductStockQuery = "UPDATE Products 
                                         SET AvailableQuantity = AvailableQuantity - ?,
                                             TotalQuantity = TotalQuantity - ?
                                         WHERE ProductID = ? AND Branch = ?";
                    $stmt = $conn->prepare($deductStockQuery);
                    $stmt->execute([$item['Quantity'], $item['Quantity'], $item['ProductID'], $transfer['FromBranch']]);
                }
            }
        }
        
        // Clean up duplicate products in destination branch (same ProductCode)
        $cleanDuplicatesQuery = "WITH CTE AS (
                                    SELECT ProductID, ProductCode, Branch,
                                           ROW_NUMBER() OVER (PARTITION BY ProductCode, Branch ORDER BY ProductID) as rn
                                    FROM Products
                                    WHERE Branch = ?
                                )
                                DELETE FROM Products WHERE ProductID IN (
                                    SELECT ProductID FROM CTE WHERE rn > 1
                                )";
        $stmt = $conn->prepare($cleanDuplicatesQuery);
        $stmt->execute([$transfer['ToBranch']]);
        
        // Update transfer status to completed
        $updateQuery = "UPDATE StockTransfers 
                        SET Status = 'completed', 
                            ReceivedBy = ?, 
                            ReceivedAt = GETDATE()
                        WHERE TransferID = ?";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([$currentUser, $transferId]);
        
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