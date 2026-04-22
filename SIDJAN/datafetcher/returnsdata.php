<?php
// api_returns.php - Backend API for Returns and Warranty Management
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
        "sqlsrv:Server=172.40.0.81;Database=SIDJAN",
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
    switch ($action) {
        case 'getReturns':
            getReturns($conn);
            break;
        case 'getWarrantyClaims':
            getWarrantyClaims($conn);
            break;
        case 'getReturnById':
            getReturnById($conn);
            break;
        case 'getWarrantyById':
            getWarrantyById($conn);
            break;
        case 'getReturnStats':
            getReturnStats($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'processReturn':
            processReturn($conn, $data, $currentUser);
            break;
        case 'submitWarranty':
            submitWarranty($conn, $data, $currentUser);
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

// ============================================
// RETURNS FUNCTIONS
// ============================================

function processReturn($conn, $data, $currentUser) {
    $transactionId = $data['transaction_id'] ?? 0;
    $transactionType = $data['transaction_type'] ?? 'sales'; // 'sales' or 'installment'
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
            // Get sale details
            $saleQuery = "SELECT ReceiptNo, CustomerName, TotalAmount, Status FROM Sales WHERE SaleID = :id";
            $stmt = $conn->prepare($saleQuery);
            $stmt->execute([':id' => $transactionId]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale) {
                throw new Exception('Sale transaction not found');
            }
            
            if ($sale['Status'] === 'returned') {
                throw new Exception('Transaction already returned');
            }
            
            // Update sale status to returned
            $updateSale = "UPDATE Sales SET Status = 'returned', UpdatedAt = GETDATE() WHERE SaleID = :id";
            $stmt = $conn->prepare($updateSale);
            $stmt->execute([':id' => $transactionId]);
            
            // Get sale items to restore stock
            $itemsQuery = "SELECT ProductID, Quantity FROM SaleItems WHERE SaleID = :id";
            $stmt = $conn->prepare($itemsQuery);
            $stmt->execute([':id' => $transactionId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore product stock
            foreach ($items as $item) {
                $restoreStock = "UPDATE Products SET CurrentStock = CurrentStock + :qty WHERE ProductID = :id";
                $stmt = $conn->prepare($restoreStock);
                $stmt->execute([':qty' => $item['Quantity'], ':id' => $item['ProductID']]);
            }
            
            $refundAmount = $sale['TotalAmount'];
            $receiptNo = $sale['ReceiptNo'];
            $customerName = $sale['CustomerName'];
            
        } elseif ($transactionType === 'installment') {
            // Get installment details
            $installmentQuery = "SELECT InstallmentNo, CustomerName, TotalAmount, PaidAmount, Status FROM Installments WHERE InstallmentID = :id";
            $stmt = $conn->prepare($installmentQuery);
            $stmt->execute([':id' => $transactionId]);
            $installment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$installment) {
                throw new Exception('Installment transaction not found');
            }
            
            if ($installment['Status'] === 'returned') {
                throw new Exception('Transaction already returned');
            }
            
            // Update installment status to returned
            $updateInstallment = "UPDATE Installments SET Status = 'returned', UpdatedAt = GETDATE() WHERE InstallmentID = :id";
            $stmt = $conn->prepare($updateInstallment);
            $stmt->execute([':id' => $transactionId]);
            
            $refundAmount = $installment['PaidAmount'];
            $receiptNo = $installment['InstallmentNo'];
            $customerName = $installment['CustomerName'];
        } else {
            throw new Exception('Invalid transaction type');
        }
        
        // Insert return record
        $returnNo = 'RTRN-' . date('Ymd') . '-' . rand(1000, 9999);
        $insertReturn = "INSERT INTO Returns 
                        (ReturnNo, TransactionID, TransactionType, ReceiptNo, CustomerName, 
                         Reason, RefundAmount, Status, Notes, CreatedBy, CreatedAt)
                        VALUES 
                        (:no, :tid, :ttype, :receipt, :customer,
                         :reason, :refund, 'pending', :notes, :user, GETDATE())";
        
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
            ':user' => $currentUser
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

function getReturns($conn) {
    $limit = $_GET['limit'] ?? 100;
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = $status !== 'all' ? "AND r.Status = :status" : "";
    
    $query = "SELECT TOP (:limit) 
                r.ReturnID, r.ReturnNo, r.TransactionID, r.TransactionType,
                r.ReceiptNo, r.CustomerName, r.Reason, r.RefundAmount,
                r.Status, r.Notes,
                FORMAT(r.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(r.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt,
                r.CreatedBy, r.ApprovedBy
              FROM Returns r
              WHERE 1=1 $statusFilter
              ORDER BY r.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $returns, 'count' => count($returns)]);
}

function getReturnById($conn) {
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
              WHERE r.ReturnID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);
    $return = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $return]);
}

function getReturnStats($conn) {
    $query = "SELECT 
                COUNT(*) AS TotalReturns,
                SUM(CASE WHEN Status = 'pending' THEN 1 ELSE 0 END) AS PendingReturns,
                SUM(CASE WHEN Status = 'approved' THEN 1 ELSE 0 END) AS ApprovedReturns,
                SUM(CASE WHEN Status = 'rejected' THEN 1 ELSE 0 END) AS RejectedReturns,
                ISNULL(SUM(RefundAmount), 0) AS TotalRefundAmount
              FROM Returns";
    
    $stmt = $conn->query($query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

// ============================================
// WARRANTY FUNCTIONS
// ============================================

function submitWarranty($conn, $data, $currentUser) {
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
            // Get sale details
            $saleQuery = "SELECT ReceiptNo, CustomerName, SaleDate FROM Sales WHERE SaleID = :id";
            $stmt = $conn->prepare($saleQuery);
            $stmt->execute([':id' => $transactionId]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale) {
                throw new Exception('Sale transaction not found');
            }
            
            $receiptNo = $sale['ReceiptNo'];
            $customerName = $sale['CustomerName'];
            $purchaseDate = $sale['SaleDate'];
            
        } elseif ($transactionType === 'installment') {
            // Get installment details
            $installmentQuery = "SELECT InstallmentNo, CustomerName, StartDate FROM Installments WHERE InstallmentID = :id";
            $stmt = $conn->prepare($installmentQuery);
            $stmt->execute([':id' => $transactionId]);
            $installment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$installment) {
                throw new Exception('Installment transaction not found');
            }
            
            $receiptNo = $installment['InstallmentNo'];
            $customerName = $installment['CustomerName'];
            $purchaseDate = $installment['StartDate'];
        } else {
            throw new Exception('Invalid transaction type');
        }
        
        // Calculate warranty expiry (1 year from purchase date)
        $expiryDate = date('Y-m-d', strtotime($purchaseDate . ' + 1 year'));
        
        // Insert warranty claim
        $warrantyNo = 'WRNT-' . date('Ymd') . '-' . rand(1000, 9999);
        $insertWarranty = "INSERT INTO WarrantyClaims 
                          (WarrantyNo, TransactionID, TransactionType, ReceiptNo, CustomerName,
                           Issue, WarrantyType, Status, ExpiryDate, Notes, CreatedBy, CreatedAt)
                          VALUES 
                          (:no, :tid, :ttype, :receipt, :customer,
                           :issue, :wtype, 'pending', :expiry, :notes, :user, GETDATE())";
        
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
            ':user' => $currentUser
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

function getWarrantyClaims($conn) {
    $limit = $_GET['limit'] ?? 100;
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = $status !== 'all' ? "AND w.Status = :status" : "";
    
    $query = "SELECT TOP (:limit) 
                w.WarrantyID, w.WarrantyNo, w.TransactionID, w.TransactionType,
                w.ReceiptNo, w.CustomerName, w.Issue, w.WarrantyType,
                w.Status, w.Resolution, w.Notes,
                FORMAT(w.ExpiryDate, 'yyyy-MM-dd') AS ExpiryDate,
                FORMAT(w.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(w.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt,
                w.CreatedBy, w.ApprovedBy
              FROM WarrantyClaims w
              WHERE 1=1 $statusFilter
              ORDER BY w.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $claims, 'count' => count($claims)]);
}

function getWarrantyById($conn) {
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
              WHERE w.WarrantyID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $claim]);
}
?>