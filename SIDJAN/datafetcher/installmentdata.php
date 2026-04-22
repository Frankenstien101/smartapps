<?php
// api_installment.php - Backend API for Installment/Loan Management with Returns & Warranty
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
    switch ($action) {
        case 'getProducts':
            getProducts($conn);
            break;
        case 'getInstallments':
            getInstallments($conn);
            break;
        case 'getInstallmentById':
            getInstallmentById($conn);
            break;
        case 'getCustomerInstallments':
            getCustomerInstallments($conn);
            break;
        case 'getInstallmentStats':
            getInstallmentStats($conn);
            break;
        case 'getPaymentHistory':
            getPaymentHistory($conn);
            break;
        // ============================================
        // RETURNS & WARRANTY ENDPOINTS
        // ============================================
        case 'getInstallmentReturns':
            getInstallmentReturns($conn);
            break;
        case 'getInstallmentWarrantyClaims':
            getInstallmentWarrantyClaims($conn);
            break;
        case 'getInstallmentReturnById':
            getInstallmentReturnById($conn);
            break;
        case 'getInstallmentWarrantyById':
            getInstallmentWarrantyById($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'createInstallment':
            createInstallment($conn, $data, $currentUser);
            break;
        case 'recordPayment':
            recordPayment($conn, $data, $currentUser);
            break;
        // ============================================
        // RETURNS & WARRANTY ENDPOINTS
        // ============================================
        case 'processInstallmentReturn':
            processInstallmentReturn($conn, $data, $currentUser);
            break;
        case 'submitInstallmentWarranty':
            submitInstallmentWarranty($conn, $data, $currentUser);
            break;
        case 'updateInstallmentReturnStatus':
            updateInstallmentReturnStatus($conn, $data, $currentUser);
            break;
        case 'updateInstallmentWarrantyStatus':
            updateInstallmentWarrantyStatus($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateInstallment':
            updateInstallment($conn, $data, $currentUser);
            break;
        case 'updatePaymentStatus':
            updatePaymentStatus($conn, $data, $currentUser);
            break;
        // ============================================
        // RETURNS & WARRANTY ENDPOINTS
        // ============================================
        case 'approveInstallmentReturn':
            approveInstallmentReturn($conn, $data, $currentUser);
            break;
        case 'approveInstallmentWarranty':
            approveInstallmentWarranty($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'deleteInstallment':
            deleteInstallment($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// PRODUCT FUNCTIONS
// ============================================

function getProducts($conn) {
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand, 
                CurrentStock, 
                SellingPrice
              FROM Products 
              WHERE CurrentStock > 0
              ORDER BY ProductName";
    
    $stmt = $conn->query($query);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

// ============================================
// INSTALLMENT FUNCTIONS
// ============================================

function createInstallment($conn, $data, $currentUser) {
    $customerName = trim($data['customer_name'] ?? '');
    $customerPhone = $data['customer_phone'] ?? '';
    $customerAddress = $data['customer_address'] ?? '';
    $products = $data['products'] ?? [];
    $totalProductPrice = floatval($data['total_product_price'] ?? 0);
    $downPayment = floatval($data['down_payment'] ?? 0);
    $interestRate = floatval($data['interest_rate'] ?? 0);
    $penaltyRate = floatval($data['penalty_rate'] ?? 0);
    $months = intval($data['months'] ?? 0);
    $notes = $data['notes'] ?? '';
    
    if (empty($customerName)) {
        echo json_encode(['success' => false, 'message' => 'Customer name is required']);
        return;
    }
    
    if (empty($products)) {
        echo json_encode(['success' => false, 'message' => 'At least one product is required']);
        return;
    }
    
    if ($months <= 0 || $months > 36) {
        echo json_encode(['success' => false, 'message' => 'Invalid number of months (1-36 months)']);
        return;
    }
    
    $loanAmount = $totalProductPrice - $downPayment;
    $totalInterest = $loanAmount * ($interestRate / 100);
    $totalAmount = $loanAmount + $totalInterest;
    $monthlyPayment = $totalAmount / $months;
    
    $productList = implode(', ', array_map(function($p) {
        return $p['product_name'] . ' (x' . $p['quantity'] . ')';
    }, $products));
    
    $installmentNo = 'INST-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $conn->beginTransaction();
    
    try {
        $query = "INSERT INTO Installments 
                  (InstallmentNo, CustomerName, CustomerPhone, CustomerAddress, 
                   ProductName, ProductPrice, DownPayment, LoanAmount,
                   InterestRate, PenaltyRate, TotalInterest, TotalAmount, Months, MonthlyPayment,
                   PaidAmount, RemainingBalance, Status, StartDate, NextPaymentDate, Notes, CreatedBy, CreatedAt)
                  VALUES 
                  (:no, :name, :phone, :address,
                   :pname, :pprice, :down, :loan,
                   :rate, :penalty, :interest, :total, :months, :monthly,
                   0, :remaining, 'active', GETDATE(), DATEADD(MONTH, 1, GETDATE()), :notes, :user, GETDATE())";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':no' => $installmentNo,
            ':name' => $customerName,
            ':phone' => $customerPhone,
            ':address' => $customerAddress,
            ':pname' => $productList,
            ':pprice' => $totalProductPrice,
            ':down' => $downPayment,
            ':loan' => $loanAmount,
            ':rate' => $interestRate,
            ':penalty' => $penaltyRate,
            ':interest' => $totalInterest,
            ':total' => $totalAmount,
            ':months' => $months,
            ':monthly' => $monthlyPayment,
            ':remaining' => $totalAmount,
            ':notes' => $notes,
            ':user' => $currentUser
        ]);
        
        $installmentId = $conn->lastInsertId();
        
        $paymentDate = new DateTime();
        $paymentDate->modify('+1 month');
        
        for ($i = 1; $i <= $months; $i++) {
            $dueDate = clone $paymentDate;
            $dueDate->modify('+' . ($i - 1) . ' months');
            
            $scheduleQuery = "INSERT INTO InstallmentPayments 
                             (InstallmentID, PaymentNo, DueDate, Amount, PenaltyAmount, Status, CreatedAt)
                             VALUES 
                             (:id, :no, :due, :amount, 0, 'pending', GETDATE())";
            
            $stmt = $conn->prepare($scheduleQuery);
            $stmt->execute([
                ':id' => $installmentId,
                ':no' => $i,
                ':due' => $dueDate->format('Y-m-d'),
                ':amount' => $monthlyPayment
            ]);
        }
        
        foreach ($products as $product) {
            $stockQuery = "UPDATE Products SET CurrentStock = CurrentStock - :qty WHERE ProductID = :id";
            $stmt = $conn->prepare($stockQuery);
            $stmt->execute([
                ':qty' => $product['quantity'],
                ':id' => $product['product_id']
            ]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Installment created successfully',
            'installment_id' => $installmentId,
            'installment_no' => $installmentNo,
            'monthly_payment' => $monthlyPayment,
            'total_amount' => $totalAmount,
            'months' => $months,
            'penalty_rate' => $penaltyRate
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getInstallments($conn) {
    $limit = $_GET['limit'] ?? 100;
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = "";
    if ($status !== 'all') {
        $statusFilter = "WHERE i.Status = :status";
    }
    
    $query = "SELECT TOP (:limit) 
                i.InstallmentID, 
                i.InstallmentNo, 
                i.CustomerName, 
                i.CustomerPhone,
                i.ProductName, 
                i.ProductPrice, 
                i.DownPayment, 
                i.LoanAmount,
                i.InterestRate, 
                i.TotalAmount, 
                i.Months, 
                i.MonthlyPayment,
                ISNULL(i.PaidAmount, 0) AS PaidAmount, 
                ISNULL(i.RemainingBalance, i.TotalAmount) AS RemainingBalance,
                i.Status,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate,
                FORMAT(i.CompletedDate, 'yyyy-MM-dd') AS CompletedDate,
                i.CreatedBy, 
                FORMAT(i.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt
              FROM Installments i
              $statusFilter
              ORDER BY i.InstallmentID DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update status based on remaining balance for active records
    foreach ($installments as &$inst) {
        if ($inst['Status'] !== 'returned' && $inst['Status'] !== 'completed') {
            if ($inst['RemainingBalance'] <= 0.01) {
                $inst['Status'] = 'completed';
            } elseif ($inst['NextPaymentDate'] && $inst['NextPaymentDate'] < date('Y-m-d')) {
                $inst['Status'] = 'overdue';
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $installments, 'count' => count($installments)]);
}

function getInstallmentById($conn) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Installment ID required']);
        return;
    }
    
    $query = "SELECT 
                i.InstallmentID, 
                i.InstallmentNo, 
                i.CustomerName, 
                i.CustomerPhone, 
                i.CustomerAddress,
                i.ProductID, 
                i.ProductName, 
                i.ProductPrice, 
                i.DownPayment, 
                i.LoanAmount,
                i.InterestRate, 
                i.TotalInterest, 
                i.TotalAmount, 
                i.Months, 
                i.MonthlyPayment,
                ISNULL(i.PaidAmount, 0) AS PaidAmount, 
                ISNULL(i.RemainingBalance, i.TotalAmount) AS RemainingBalance,
                i.Status,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate,
                FORMAT(i.CompletedDate, 'yyyy-MM-dd') AS CompletedDate,
                i.Notes, 
                i.CreatedBy, 
                FORMAT(i.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt
              FROM Installments i
              WHERE i.InstallmentID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);
    $installment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($installment) {
        $scheduleQuery = "SELECT 
                            PaymentID, 
                            PaymentNo, 
                            FORMAT(DueDate, 'yyyy-MM-dd') AS DueDate,
                            Amount, 
                            ISNULL(PaidAmount, 0) AS PaidAmount, 
                            Status,
                            FORMAT(PaymentDate, 'yyyy-MM-dd HH:mm') AS PaymentDate,
                            ReferenceNo, 
                            Notes
                          FROM InstallmentPayments
                          WHERE InstallmentID = :id
                          ORDER BY PaymentNo";
        
        $stmt = $conn->prepare($scheduleQuery);
        $stmt->execute([':id' => $id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $installment, 'payments' => $payments]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Installment not found']);
    }
}

function getCustomerInstallments($conn) {
    $phone = $_GET['phone'] ?? '';
    
    if (!$phone) {
        echo json_encode(['success' => false, 'message' => 'Customer phone required']);
        return;
    }
    
    $query = "SELECT 
                InstallmentID, InstallmentNo, ProductName, 
                TotalAmount, MonthlyPayment, PaidAmount, RemainingBalance,
                Status, FORMAT(NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate
              FROM Installments
              WHERE CustomerPhone = :phone
              ORDER BY InstallmentID DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':phone' => $phone]);
    $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $installments]);
}

function getInstallmentStats($conn) {
    $query = "SELECT 
                COUNT(*) AS TotalInstallments,
                SUM(CASE WHEN Status = 'active' AND Status != 'returned' THEN 1 ELSE 0 END) AS ActiveInstallments,
                SUM(CASE WHEN Status = 'completed' AND Status != 'returned' THEN 1 ELSE 0 END) AS CompletedInstallments,
                SUM(CASE WHEN Status = 'overdue' AND Status != 'returned' THEN 1 ELSE 0 END) AS OverdueInstallments,
                SUM(CASE WHEN Status = 'returned' THEN 1 ELSE 0 END) AS ReturnedInstallments,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN TotalAmount ELSE 0 END), 0) AS TotalLoanAmount,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN PaidAmount ELSE 0 END), 0) AS TotalPaidAmount,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN RemainingBalance ELSE 0 END), 0) AS TotalRemainingBalance
              FROM Installments
              WHERE Status IS NOT NULL";
    
    $stmt = $conn->query($query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get overdue count excluding returned
    $overdueQuery = "SELECT COUNT(*) AS OverdueCount 
                     FROM Installments 
                     WHERE Status = 'active' AND NextPaymentDate < GETDATE() AND Status != 'returned'";
    $stmt = $conn->query($overdueQuery);
    $overdue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['OverdueCount'] = $overdue['OverdueCount'] ?? 0;
    $stats['ReturnedInstallments'] = $stats['ReturnedInstallments'] ?? 0;
    
    echo json_encode(['success' => true, 'data' => $stats]);
}



function getReturnedInstallments($conn) {
    $limit = $_GET['limit'] ?? 100;
    
    $query = "SELECT TOP (:limit) 
                i.InstallmentID, 
                i.InstallmentNo, 
                i.CustomerName, 
                i.CustomerPhone,
                i.ProductName, 
                i.TotalAmount, 
                i.PaidAmount,
                i.Status,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                FORMAT(i.CompletedDate, 'yyyy-MM-dd') AS ReturnedDate,
                r.ReturnNo,
                r.Reason,
                r.RefundAmount,
                r.TotalRefund,
                FORMAT(r.CreatedAt, 'yyyy-MM-dd HH:mm') AS ReturnDate,
                r.CreatedBy AS ProcessedBy
              FROM Installments i
              INNER JOIN InstallmentReturns r ON i.InstallmentID = r.InstallmentID
              WHERE i.Status = 'returned'
              ORDER BY r.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $returns, 'count' => count($returns)]);
}

function getPaymentHistory($conn) {
    $installmentId = $_GET['installment_id'] ?? 0;
    
    if (!$installmentId) {
        echo json_encode(['success' => false, 'message' => 'Installment ID required']);
        return;
    }
    
    $query = "SELECT 
                PaymentID, PaymentNo, 
                FORMAT(DueDate, 'yyyy-MM-dd') AS DueDate,
                Amount, PaidAmount, Status,
                FORMAT(PaymentDate, 'yyyy-MM-dd HH:mm') AS PaymentDate,
                ReferenceNo, Notes
              FROM InstallmentPayments
              WHERE InstallmentID = :id
              ORDER BY PaymentNo";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $installmentId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $payments]);
}

function getInstallmentReturnDetails($conn) {
    $returnNo = $_GET['return_no'] ?? '';
    $installmentId = $_GET['installment_id'] ?? 0;
    
    if (!$returnNo && !$installmentId) {
        echo json_encode(['success' => false, 'message' => 'Return number or installment ID required']);
        return;
    }
    
    $query = "SELECT 
                r.*,
                i.ProductName,
                i.TotalAmount AS OriginalAmount,
                i.Months,
                i.MonthlyPayment,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate
              FROM InstallmentReturns r
              LEFT JOIN Installments i ON r.InstallmentID = i.InstallmentID
              WHERE (r.ReturnNo = :no OR r.InstallmentID = :id)
              ORDER BY r.CreatedAt DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':no' => $returnNo, ':id' => $installmentId]);
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $returns]);
}

function recordPayment($conn, $data, $currentUser) {
    $installmentId = $data['installment_id'] ?? 0;
    $paymentNo = $data['payment_no'] ?? 0;
    $amountPaid = floatval($data['amount'] ?? 0);
    $paymentMethod = $data['payment_method'] ?? 'cash';
    $referenceNo = $data['reference_no'] ?? '';
    $notes = $data['notes'] ?? '';
    $penaltyPaid = floatval($data['penalty_paid'] ?? 0);
    
    if (!$installmentId || !$paymentNo) {
        echo json_encode(['success' => false, 'message' => 'Installment ID and payment number required']);
        return;
    }
    
    // Check if installment is returned
    $checkStatusQuery = "SELECT Status FROM Installments WHERE InstallmentID = :id";
    $stmt = $conn->prepare($checkStatusQuery);
    $stmt->execute([':id' => $installmentId]);
    $statusCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statusCheck && $statusCheck['Status'] === 'returned') {
        echo json_encode(['success' => false, 'message' => 'Cannot record payment. This installment has been returned.']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $getPaymentQuery = "SELECT ip.PaymentID, ip.Amount, ip.DueDate, ip.Status, ip.PenaltyAmount,
                                  i.PenaltyRate, i.MonthlyPayment
                           FROM InstallmentPayments ip
                           JOIN Installments i ON ip.InstallmentID = i.InstallmentID
                           WHERE ip.InstallmentID = :id AND ip.PaymentNo = :no";
        $stmt = $conn->prepare($getPaymentQuery);
        $stmt->execute([':id' => $installmentId, ':no' => $paymentNo]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Payment record not found');
        }
        
        if ($payment['Status'] === 'paid') {
            throw new Exception('Payment already recorded');
        }
        
        $dueDate = new DateTime($payment['DueDate']);
        $today = new DateTime();
        $daysOverdue = 0;
        $penaltyAmount = 0;
        
        if ($today > $dueDate) {
            $interval = $dueDate->diff($today);
            $daysOverdue = $interval->days;
            $penaltyAmount = $payment['Amount'] * ($payment['PenaltyRate'] / 100) * ceil($daysOverdue / 30);
        }
        
        $totalPayment = $amountPaid;
        $actualPenaltyPaid = 0;
        
        if ($penaltyAmount > 0) {
            if ($amountPaid >= $payment['Amount'] + $penaltyAmount) {
                $actualPenaltyPaid = $penaltyAmount;
                $totalPayment = $payment['Amount'];
            } elseif ($amountPaid > $payment['Amount']) {
                $actualPenaltyPaid = $amountPaid - $payment['Amount'];
                $totalPayment = $payment['Amount'];
            } else {
                $totalPayment = $amountPaid;
                $actualPenaltyPaid = 0;
            }
        }
        
        $updatePayment = "UPDATE InstallmentPayments 
                          SET PaidAmount = :amount,
                              Status = 'paid',
                              PaymentDate = GETDATE(),
                              ReferenceNo = :ref,
                              Notes = :notes,
                              PenaltyAmount = :penalty_amount,
                              PenaltyPaid = :penalty_paid,
                              DaysOverdue = :days_overdue
                          WHERE InstallmentID = :id AND PaymentNo = :no";
        
        $stmt = $conn->prepare($updatePayment);
        $stmt->execute([
            ':amount' => $totalPayment,
            ':ref' => $referenceNo,
            ':notes' => $notes,
            ':penalty_amount' => $penaltyAmount,
            ':penalty_paid' => $actualPenaltyPaid,
            ':days_overdue' => $daysOverdue,
            ':id' => $installmentId,
            ':no' => $paymentNo
        ]);
        
        $getInstallment = "SELECT TotalAmount, PaidAmount, RemainingBalance, PenaltyAmount, TotalPenaltyPaid 
                          FROM Installments WHERE InstallmentID = :id";
        $stmt = $conn->prepare($getInstallment);
        $stmt->execute([':id' => $installmentId]);
        $installment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$installment) {
            throw new Exception('Installment record not found');
        }
        
        $totalAmount = floatval($installment['TotalAmount']);
        $currentPaid = floatval($installment['PaidAmount']);
        $currentPenaltyTotal = floatval($installment['TotalPenaltyPaid']);
        $newPaidAmount = $currentPaid + $totalPayment;
        $newPenaltyTotal = $currentPenaltyTotal + $actualPenaltyPaid;
        $newRemainingBalance = $totalAmount - $newPaidAmount;
        
        $checkAllPaymentsQuery = "SELECT COUNT(*) as pending_count 
                                  FROM InstallmentPayments 
                                  WHERE InstallmentID = :id AND Status = 'pending'";
        $stmt = $conn->prepare($checkAllPaymentsQuery);
        $stmt->execute([':id' => $installmentId]);
        $pendingResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasPendingPayments = ($pendingResult['pending_count'] > 0);
        
        $newStatus = 'active';
        $isFullyPaid = false;
        
        if ($newRemainingBalance <= 0.01 || !$hasPendingPayments) {
            $newStatus = 'completed';
            $isFullyPaid = true;
        } else {
            $checkOverdueQuery = "SELECT COUNT(*) as overdue_count 
                                  FROM InstallmentPayments 
                                  WHERE InstallmentID = :id AND Status = 'pending' AND DueDate < GETDATE()";
            $stmt = $conn->prepare($checkOverdueQuery);
            $stmt->execute([':id' => $installmentId]);
            $overdueResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($overdueResult['overdue_count'] > 0) {
                $newStatus = 'overdue';
            }
        }
        
        $updateInstallment = "UPDATE Installments 
                              SET PaidAmount = :paid,
                                  RemainingBalance = :remaining,
                                  Status = :status,
                                  PenaltyAmount = :penalty_total,
                                  TotalPenaltyPaid = :penalty_paid_total,
                                  UpdatedAt = GETDATE()
                              WHERE InstallmentID = :id";
        
        $stmt = $conn->prepare($updateInstallment);
        $stmt->execute([
            ':paid' => $newPaidAmount,
            ':remaining' => $newRemainingBalance,
            ':status' => $newStatus,
            ':penalty_total' => $penaltyAmount,
            ':penalty_paid_total' => $newPenaltyTotal,
            ':id' => $installmentId
        ]);
        
        if ($isFullyPaid) {
            $completeQuery = "UPDATE Installments 
                              SET CompletedDate = GETDATE(),
                                  NextPaymentDate = NULL
                              WHERE InstallmentID = :id";
            $stmt = $conn->prepare($completeQuery);
            $stmt->execute([':id' => $installmentId]);
        } else {
            $nextQuery = "SELECT TOP 1 DueDate FROM InstallmentPayments 
                          WHERE InstallmentID = :id AND Status = 'pending' 
                          ORDER BY PaymentNo";
            $stmt = $conn->prepare($nextQuery);
            $stmt->execute([':id' => $installmentId]);
            $next = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($next) {
                $updateNext = "UPDATE Installments SET NextPaymentDate = :date WHERE InstallmentID = :id";
                $stmt = $conn->prepare($updateNext);
                $stmt->execute([':date' => $next['DueDate'], ':id' => $installmentId]);
            }
        }
        
        $conn->commit();
        
        $message = $isFullyPaid ? 'Payment recorded successfully! Installment is now FULLY PAID!' : 'Payment recorded successfully';
        if ($actualPenaltyPaid > 0) {
            $message .= ' Penalty of ₱' . number_format($actualPenaltyPaid, 2) . ' was applied.';
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'remaining_balance' => $newRemainingBalance,
            'total_paid' => $newPaidAmount,
            'is_fully_paid' => $isFullyPaid,
            'status' => $newStatus,
            'penalty_applied' => $actualPenaltyPaid,
            'days_overdue' => $daysOverdue
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateInstallment($conn, $data, $currentUser) {
    $installmentId = $data['installment_id'] ?? 0;
    $status = $data['status'] ?? '';
    
    if (!$installmentId) {
        echo json_encode(['success' => false, 'message' => 'Installment ID required']);
        return;
    }
    
    $query = "UPDATE Installments SET Status = :status, UpdatedAt = GETDATE() WHERE InstallmentID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':status' => $status, ':id' => $installmentId]);
    
    echo json_encode(['success' => true, 'message' => 'Installment updated']);
}

function updatePaymentStatus($conn, $data, $currentUser) {
    $paymentId = $data['payment_id'] ?? 0;
    $status = $data['status'] ?? '';
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'message' => 'Payment ID required']);
        return;
    }
    
    $query = "UPDATE InstallmentPayments SET Status = :status WHERE PaymentID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':status' => $status, ':id' => $paymentId]);
    
    echo json_encode(['success' => true, 'message' => 'Payment status updated']);
}

function deleteInstallment($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $installmentId = $data['installment_id'] ?? $_GET['id'] ?? 0;
    
    if (!$installmentId) {
        echo json_encode(['success' => false, 'message' => 'Installment ID required']);
        return;
    }
    
    $checkQuery = "SELECT COUNT(*) as paid FROM InstallmentPayments 
                   WHERE InstallmentID = :id AND Status = 'paid'";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':id' => $installmentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['paid'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete installment with existing payments']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $delPayments = "DELETE FROM InstallmentPayments WHERE InstallmentID = :id";
        $stmt = $conn->prepare($delPayments);
        $stmt->execute([':id' => $installmentId]);
        
        $delInstallment = "DELETE FROM Installments WHERE InstallmentID = :id";
        $stmt = $conn->prepare($delInstallment);
        $stmt->execute([':id' => $installmentId]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Installment deleted successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ============================================
// INSTALLMENT RETURNS FUNCTIONS
// ============================================

function processInstallmentReturn($conn, $data, $currentUser) {
    $installmentId = $data['transaction_id'] ?? 0;
    $reason = $data['reason'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$installmentId) {
        echo json_encode(['success' => false, 'message' => 'Installment ID required']);
        return;
    }
    
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Reason for return is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        // Get installment details
        $installmentQuery = "SELECT InstallmentID, InstallmentNo, CustomerName, TotalAmount, 
                                    PaidAmount, RemainingBalance, Status, ProductID, ProductName
                            FROM Installments WHERE InstallmentID = :id";
        $stmt = $conn->prepare($installmentQuery);
        $stmt->execute([':id' => $installmentId]);
        $installment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$installment) {
            throw new Exception('Installment transaction not found');
        }
        
        if ($installment['Status'] === 'returned') {
            throw new Exception('Transaction already returned');
        }
        
        // Update installment status to returned
        $updateInstallment = "UPDATE Installments 
                              SET Status = 'returned', 
                                  UpdatedAt = GETDATE(),
                                  CompletedDate = NULL,
                                  NextPaymentDate = NULL
                              WHERE InstallmentID = :id";
        $stmt = $conn->prepare($updateInstallment);
        $stmt->execute([':id' => $installmentId]);
        
        // Get all pending payments and mark them as cancelled
        $updatePayments = "UPDATE InstallmentPayments 
                           SET Status = 'cancelled', 
                               Notes = ISNULL(Notes, '') + ' - Returned',
                               UpdatedAt = GETDATE()
                           WHERE InstallmentID = :id AND Status = 'pending'";
        $stmt = $conn->prepare($updatePayments);
        $stmt->execute([':id' => $installmentId]);
        
        // Get total paid amount (without penalty columns)
        $paidPaymentsQuery = "SELECT ISNULL(SUM(PaidAmount), 0) as TotalPaid
                              FROM InstallmentPayments 
                              WHERE InstallmentID = :id AND Status = 'paid'";
        $stmt = $conn->prepare($paidPaymentsQuery);
        $stmt->execute([':id' => $installmentId]);
        $paidData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalPaid = floatval($paidData['TotalPaid'] ?? 0);
        
        // Update paid payments status to refunded
        $updatePaidPayments = "UPDATE InstallmentPayments 
                               SET Status = 'refunded',
                                   Notes = ISNULL(Notes, '') + ' - Refunded due to return',
                                   UpdatedAt = GETDATE()
                               WHERE InstallmentID = :id AND Status = 'paid'";
        $stmt = $conn->prepare($updatePaidPayments);
        $stmt->execute([':id' => $installmentId]);
        
        // Restore product stock (since products were taken)
        if ($installment['ProductID']) {
            $restoreStock = "UPDATE Products SET CurrentStock = CurrentStock + 1 WHERE ProductID = :id";
            $stmt = $conn->prepare($restoreStock);
            $stmt->execute([':id' => $installment['ProductID']]);
        }
        
        // Insert return record (without penalty columns)
        $returnNo = 'RTRN-INST-' . date('Ymd') . '-' . rand(1000, 9999);
        $insertReturn = "INSERT INTO InstallmentReturns 
                        (ReturnNo, InstallmentID, ReceiptNo, CustomerName, 
                         Reason, RefundAmount, Status, Notes, CreatedBy, CreatedAt)
                        VALUES 
                        (:no, :iid, :receipt, :customer,
                         :reason, :refund, 'approved', :notes, :user, GETDATE())";
        
        $stmt = $conn->prepare($insertReturn);
        $stmt->execute([
            ':no' => $returnNo,
            ':iid' => $installmentId,
            ':receipt' => $installment['InstallmentNo'],
            ':customer' => $installment['CustomerName'],
            ':reason' => $reason,
            ':refund' => $totalPaid,
            ':notes' => $notes,
            ':user' => $currentUser
        ]);
        
        $returnId = $conn->lastInsertId();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Installment return processed successfully.' . number_format($totalPaid, 2),
            'return_id' => $returnId,
            'return_no' => $returnNo,
            'refund_amount' => $totalPaid
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function approveInstallmentReturn($conn, $data, $currentUser) {
    $returnId = $data['return_id'] ?? 0;
    $status = $data['status'] ?? 'approved';
    
    if (!$returnId) {
        echo json_encode(['success' => false, 'message' => 'Return ID required']);
        return;
    }
    
    $updateQuery = "UPDATE InstallmentReturns 
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
    
    echo json_encode(['success' => true, 'message' => 'Installment return ' . $status . ' successfully']);
}

function getInstallmentReturns($conn) {
    $limit = $_GET['limit'] ?? 100;
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = $status !== 'all' ? "AND r.Status = :status" : "";
    
    $query = "SELECT TOP (:limit) 
                r.ReturnID, r.ReturnNo, r.InstallmentID, r.ReceiptNo, 
                r.CustomerName, r.Reason, r.RefundAmount, r.Status, r.Notes,
                FORMAT(r.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(r.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt,
                r.CreatedBy, r.ApprovedBy
              FROM InstallmentReturns r
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

function getInstallmentReturnById($conn) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Return ID required']);
        return;
    }
    
    $query = "SELECT * FROM InstallmentReturns WHERE ReturnID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);
    $return = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $return]);
}

function updateInstallmentReturnStatus($conn, $data, $currentUser) {
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
    
    $updateQuery = "UPDATE InstallmentReturns SET Status = :status, UpdatedAt = GETDATE() WHERE ReturnID = :id";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([':status' => $status, ':id' => $returnId]);
    
    echo json_encode(['success' => true, 'message' => 'Return status updated']);
}

// ============================================
// INSTALLMENT WARRANTY FUNCTIONS
// ============================================

function submitInstallmentWarranty($conn, $data, $currentUser) {
    $installmentId = $data['transaction_id'] ?? 0;
    $issue = $data['issue'] ?? '';
    $warrantyType = $data['warranty_type'] ?? 'Repair';
    $notes = $data['notes'] ?? '';
    
    if (!$installmentId) {
        echo json_encode(['success' => false, 'message' => 'Installment ID required']);
        return;
    }
    
    if (empty($issue)) {
        echo json_encode(['success' => false, 'message' => 'Issue description is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $installmentQuery = "SELECT InstallmentNo, CustomerName, StartDate FROM Installments WHERE InstallmentID = :id";
        $stmt = $conn->prepare($installmentQuery);
        $stmt->execute([':id' => $installmentId]);
        $installment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$installment) {
            throw new Exception('Installment transaction not found');
        }
        
        $receiptNo = $installment['InstallmentNo'];
        $customerName = $installment['CustomerName'];
        $purchaseDate = $installment['StartDate'];
        
        $expiryDate = date('Y-m-d', strtotime($purchaseDate . ' + 1 year'));
        
        $warrantyNo = 'WRNT-INST-' . date('Ymd') . '-' . rand(1000, 9999);
        $insertWarranty = "INSERT INTO InstallmentWarrantyClaims 
                          (WarrantyNo, InstallmentID, ReceiptNo, CustomerName,
                           Issue, WarrantyType, Status, ExpiryDate, Notes, CreatedBy, CreatedAt)
                          VALUES 
                          (:no, :iid, :receipt, :customer,
                           :issue, :wtype, 'pending', :expiry, :notes, :user, GETDATE())";
        
        $stmt = $conn->prepare($insertWarranty);
        $stmt->execute([
            ':no' => $warrantyNo,
            ':iid' => $installmentId,
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

function approveInstallmentWarranty($conn, $data, $currentUser) {
    $warrantyId = $data['warranty_id'] ?? 0;
    $status = $data['status'] ?? 'approved';
    $resolution = $data['resolution'] ?? '';
    
    if (!$warrantyId) {
        echo json_encode(['success' => false, 'message' => 'Warranty ID required']);
        return;
    }
    
    $updateQuery = "UPDATE InstallmentWarrantyClaims 
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

function getInstallmentWarrantyClaims($conn) {
    $limit = $_GET['limit'] ?? 100;
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = $status !== 'all' ? "AND w.Status = :status" : "";
    
    $query = "SELECT TOP (:limit) 
                w.WarrantyID, w.WarrantyNo, w.InstallmentID, w.ReceiptNo,
                w.CustomerName, w.Issue, w.WarrantyType, w.Status, w.Resolution, w.Notes,
                FORMAT(w.ExpiryDate, 'yyyy-MM-dd') AS ExpiryDate,
                FORMAT(w.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(w.ApprovedAt, 'yyyy-MM-dd HH:mm') AS ApprovedAt,
                w.CreatedBy, w.ApprovedBy
              FROM InstallmentWarrantyClaims w
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

function getInstallmentWarrantyById($conn) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Warranty ID required']);
        return;
    }
    
    $query = "SELECT * FROM InstallmentWarrantyClaims WHERE WarrantyID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $claim]);
}

function updateInstallmentWarrantyStatus($conn, $data, $currentUser) {
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
    
    $updateQuery = "UPDATE InstallmentWarrantyClaims SET Status = :status, UpdatedAt = GETDATE() WHERE WarrantyID = :id";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([':status' => $status, ':id' => $warrantyId]);
    
    echo json_encode(['success' => true, 'message' => 'Warranty status updated']);
}
?>