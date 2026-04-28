<?php
// api_returns.php - Backend API for Returns and Warranty Management with Branch Support
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
// RETURNS FUNCTIONS WITH BRANCH
// ============================================

function processReturn($conn, $data, $currentUser, $currentBranch) {
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
                $restoreStock = "UPDATE Products SET CurrentStock = CurrentStock + :qty WHERE ProductID = :id AND Branch = :branch";
                $stmt = $conn->prepare($restoreStock);
                $stmt->execute([':qty' => $item['Quantity'], ':id' => $item['ProductID'], ':branch' => $currentBranch]);
            }
            
            $refundAmount = $sale['TotalAmount'];
            $receiptNo = $sale['ReceiptNo'];
            $customerName = $sale['CustomerName'];
            
        } elseif ($transactionType === 'installment') {
            // Get installment details
            $installmentQuery = "SELECT InstallmentNo, CustomerName, TotalAmount, PaidAmount, Status FROM Installments WHERE InstallmentID = :id AND Branch = :branch";
            $stmt = $conn->prepare($installmentQuery);
            $stmt->execute([':id' => $transactionId, ':branch' => $currentBranch]);
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
            'refund_amount' => $refundAmount,
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
    
    // Build status filter
    $statusFilter = "";
    if ($status !== 'all') {
        $statusFilter = "AND r.Status = '$status'";
    }
    
    // Branch filter
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
    $stmt->execute([':branch' => $currentBranch]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $stats]);
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
            // Get sale details
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
            
        } elseif ($transactionType === 'installment') {
            // Get installment details
            $installmentQuery = "SELECT InstallmentNo, CustomerName, StartDate FROM Installments WHERE InstallmentID = :id AND Branch = :branch";
            $stmt = $conn->prepare($installmentQuery);
            $stmt->execute([':id' => $transactionId, ':branch' => $currentBranch]);
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
            'expiry_date' => $expiryDate,
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

function getWarrantyClaims($conn, $currentBranch, $userRole) {
    $limit = intval($_GET['limit'] ?? 100);
    $status = $_GET['status'] ?? 'all';
    
    // Build status filter
    $statusFilter = "";
    if ($status !== 'all') {
        $statusFilter = "AND w.Status = '$status'";
    }
    
    // Branch filter
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
?>