<?php
// installmentdata.php - Backend API for Installment/Loan Management
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include '../DB/dbcon.php';

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
//    echo json_encode(['success' => false, 'error' => 'Database connection failed', 'message' => $e->getMessage()]);
//    exit();
//}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $action);
            break;
        case 'POST':
            handlePostRequest($conn, $action, $currentUser);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error', 'message' => $e->getMessage()]);
}

function handleGetRequest($conn, $action) {
    global $currentBranch;
    
    switch ($action) {
        case 'getProducts':
            getProducts($conn, $currentBranch);
            break;
        case 'getInstallments':
            getInstallments($conn, $currentBranch);
            break;
        case 'getInstallmentById':
            getInstallmentById($conn, $currentBranch);
            break;
        case 'getInstallmentStats':
            getInstallmentStats($conn, $currentBranch);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'createInstallment':
            createInstallment($conn, $data, $currentUser, $currentBranch);
            break;
        case 'recordPayment':
            recordPayment($conn, $data, $currentUser, $currentBranch);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
}

// ============================================
// GET PRODUCTS WITH AVAILABLE STOCK/UNITS
// ============================================

function getProducts($conn, $currentBranch) {
    $query = "SELECT 
                p.ProductID, 
                p.ProductCode, 
                p.ProductName, 
                p.Category, 
                p.Brand, 
                p.AvailableQuantity,
                p.SellingPrice,
                p.ProductImagePath
              FROM Products p
              WHERE p.Branch = :branch
              ORDER BY p.ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $products = $stmt->fetchAll();
    
    $result = [];
    foreach ($products as $product) {
        // Get ONLY available units (not on_installment, not sold, not transferred)
        $unitQuery = "SELECT COUNT(*) as unit_count 
                      FROM ProductUnits 
                      WHERE ProductID = :pid 
                        AND Branch = :branch 
                        AND Status = 'available'";
        $unitStmt = $conn->prepare($unitQuery);
        $unitStmt->execute([
            ':pid' => $product['ProductID'],
            ':branch' => $currentBranch
        ]);
        $unitCount = $unitStmt->fetch();
        
        $availableUnits = intval($unitCount['unit_count']);
        $bulkStock = intval($product['AvailableQuantity'] ?? 0);
        
        // IMPORTANT: Use availableUnits count for stock if product has units
        // If product has units, ignore bulk stock count
        if ($availableUnits > 0) {
            // Product has serialized units available
            $product['HasUnits'] = true;
            $product['AvailableQuantity'] = $availableUnits;
            $result[] = $product;
        } elseif ($bulkStock > 0 && $availableUnits == 0) {
            // Bulk product with no units
            $product['HasUnits'] = false;
            $result[] = $product;
        }
        // If both are 0, don't add to result
    }
    
    echo json_encode(['success' => true, 'data' => $result, 'count' => count($result)]);
}

// ============================================
// CREATE INSTALLMENT
// ============================================

function createInstallment($conn, $data, $currentUser, $currentBranch) {
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
    
    $productNames = [];
    foreach ($products as $p) {
        $productNames[] = $p['product_name'] . ' (x' . $p['quantity'] . ')';
    }
    $productList = implode(', ', $productNames);
    
    $installmentNo = 'INST-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $conn->beginTransaction();
    
    try {
        $query = "INSERT INTO Installments 
                  (InstallmentNo, CustomerName, CustomerPhone, CustomerAddress, 
                   ProductName, ProductPrice, DownPayment, LoanAmount,
                   InterestRate, PenaltyRate, TotalInterest, TotalAmount, Months, MonthlyPayment,
                   PaidAmount, RemainingBalance, Status, StartDate, NextPaymentDate, Notes, 
                   CreatedBy, CreatedAt, Branch)
                  VALUES 
                  (?, ?, ?, ?,
                   ?, ?, ?, ?,
                   ?, ?, ?, ?, ?, ?,
                   0, ?, 'active', GETDATE(), DATEADD(MONTH, 1, GETDATE()), ?, 
                   ?, GETDATE(), ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $installmentNo,
            $customerName,
            $customerPhone,
            $customerAddress,
            $productList,
            $totalProductPrice,
            $downPayment,
            $loanAmount,
            $interestRate,
            $penaltyRate,
            $totalInterest,
            $totalAmount,
            $months,
            $monthlyPayment,
            $totalAmount,
            $notes,
            $currentUser,
            $currentBranch
        ]);
        
        $installmentId = $conn->lastInsertId();
        
        // Create payment schedule
        $paymentDate = new DateTime();
        $paymentDate->modify('+1 month');
        
        $scheduleQuery = "INSERT INTO InstallmentPayments 
                         (InstallmentID, PaymentNo, DueDate, Amount, PenaltyAmount, Status, CreatedAt, Branch)
                         VALUES 
                         (?, ?, ?, ?, 0, 'pending', GETDATE(), ?)";
        
        $scheduleStmt = $conn->prepare($scheduleQuery);
        
        for ($i = 1; $i <= $months; $i++) {
            $dueDate = clone $paymentDate;
            $dueDate->modify('+' . ($i - 1) . ' months');
            
            $scheduleStmt->execute([
                $installmentId,
                $i,
                $dueDate->format('Y-m-d'),
                $monthlyPayment,
                $currentBranch
            ]);
        }
        
        // Process products and update stock
        foreach ($products as $product) {
            $isSerialized = !empty($product['unit_id']) && $product['unit_id'] > 0;
            
            if ($isSerialized) {
                // SERIALIZED ITEM
                $unitId = $product['unit_id'];
                
                // Get unit details
                $getUnitQuery = "SELECT UnitNumber, IMEINumber, SerialNumber 
                                FROM ProductUnits 
                                WHERE UnitID = ? AND Branch = ?";
                $stmt = $conn->prepare($getUnitQuery);
                $stmt->execute([$unitId, $currentBranch]);
                $unitData = $stmt->fetch();
                
                $unitNumber = $unitData['UnitNumber'] ?? '';
                $imei = $unitData['IMEINumber'] ?? '';
                $serial = $unitData['SerialNumber'] ?? '';
                
                // Mark unit as on_installment
                $updateUnitQuery = "UPDATE ProductUnits 
                                   SET Status = 'on_installment', 
                                       SoldTo = ?,
                                       SoldBy = ?,
                                       Notes = ?
                                   WHERE UnitID = ? AND Status = 'available'";
                
                $stmt = $conn->prepare($updateUnitQuery);
                $stmt->execute([
                    $customerName,
                    $currentUser,
                    "Installment sale - {$installmentNo}",
                    $unitId
                ]);
                
                // Update product stock
                $updateStockQuery = "UPDATE Products 
                                    SET AvailableQuantity = AvailableQuantity - 1,
                                        SoldQuantity = ISNULL(SoldQuantity, 0) + 1
                                    WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($updateStockQuery);
                $stmt->execute([
                    $product['product_id'],
                    $currentBranch
                ]);
                
                // Log stock history
                $logQuery = "INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                            SELECT 
                                ?, ?, -1, AvailableQuantity + 1, AvailableQuantity,
                                CostPrice, CostPrice, ?, GETDATE(), ?, ?
                            FROM Products 
                            WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($logQuery);
                $stmt->execute([
                    $product['product_id'],
                    $product['product_name'],
                    "Installment Sale - Unit #{$unitNumber} - {$installmentNo}",
                    $currentUser,
                    $currentBranch,
                    $product['product_id'],
                    $currentBranch
                ]);
                
            } else {
                // BULK ITEM
                $quantity = intval($product['quantity']);
                
                // Update product stock
                $updateStockQuery = "UPDATE Products 
                                    SET AvailableQuantity = AvailableQuantity - ?,
                                        SoldQuantity = ISNULL(SoldQuantity, 0) + ?
                                    WHERE ProductID = ? AND Branch = ?";
                $stmt = $conn->prepare($updateStockQuery);
                $stmt->execute([
                    $quantity,
                    $quantity,
                    $product['product_id'],
                    $currentBranch
                ]);
                
                // Log stock history
                $logQuery = "INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, Notes, TransactionDate, AddedBy, Branch)
                            SELECT 
                                ?, ?, -?, AvailableQuantity + ?, AvailableQuantity,
                                CostPrice, CostPrice * ?, ?, GETDATE(), ?, ?
                            FROM Products 
                            WHERE ProductID = ? AND Branch = ?";
                
                $stmt = $conn->prepare($logQuery);
                $stmt->execute([
                    $product['product_id'],
                    $product['product_name'],
                    $quantity,
                    $quantity,
                    $quantity,
                    "Installment Sale (Bulk) - {$installmentNo}",
                    $currentUser,
                    $currentBranch,
                    $product['product_id'],
                    $currentBranch
                ]);
            }
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
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ============================================
// GET INSTALLMENTS LIST
// ============================================

function getInstallments($conn, $currentBranch) {
    $limit = intval($_GET['limit'] ?? 100);
    $status = $_GET['status'] ?? 'all';
    
    $statusFilter = "";
    if ($status !== 'all') {
        $statusFilter = "AND i.Status = :status";
    }
    
    $query = "SELECT TOP $limit 
                i.InstallmentID, 
                i.InstallmentNo, 
                i.CustomerName, 
                i.CustomerPhone,
                i.ProductName, 
                i.ProductPrice, 
                i.DownPayment, 
                i.TotalAmount, 
                i.Months, 
                i.MonthlyPayment,
                ISNULL(i.PaidAmount, 0) AS PaidAmount, 
                ISNULL(i.RemainingBalance, i.TotalAmount) AS RemainingBalance,
                i.Status,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate,
                i.Branch
              FROM Installments i
              WHERE i.Branch = :branch $statusFilter
              ORDER BY i.InstallmentID DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch', $currentBranch);
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $installments = $stmt->fetchAll();
    
    foreach ($installments as &$inst) {
        if ($inst['Status'] !== 'returned' && $inst['Status'] !== 'completed') {
            if ($inst['RemainingBalance'] <= 0.01) {
                $inst['Status'] = 'completed';
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $installments, 'count' => count($installments)]);
}

// ============================================
// GET INSTALLMENT BY ID
// ============================================

function getInstallmentById($conn, $currentBranch) {
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
                i.ProductName, 
                i.ProductPrice, 
                i.DownPayment, 
                i.LoanAmount,
                i.InterestRate, 
                i.PenaltyRate,
                i.TotalInterest, 
                i.TotalAmount, 
                i.Months, 
                i.MonthlyPayment,
                ISNULL(i.PaidAmount, 0) AS PaidAmount, 
                ISNULL(i.RemainingBalance, i.TotalAmount) AS RemainingBalance,
                i.Status,
                FORMAT(i.StartDate, 'yyyy-MM-dd') AS StartDate,
                FORMAT(i.NextPaymentDate, 'yyyy-MM-dd') AS NextPaymentDate,
                i.Notes, 
                i.CreatedBy, 
                FORMAT(i.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                i.Branch
                
              FROM Installments i
              WHERE i.InstallmentID = :id AND i.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id, ':branch' => $currentBranch]);
    $installment = $stmt->fetch();
    
    if ($installment) {
        $scheduleQuery = "SELECT 
                            PaymentNo, 
                            FORMAT(DueDate, 'yyyy-MM-dd') AS DueDate,
                            Amount, 
                            Status,
                            FORMAT(PaymentDate, 'yyyy-MM-dd HH:mm') AS PaymentDate,
                            ReferenceNo, 
                            Notes
                          FROM InstallmentPayments
                          WHERE InstallmentID = :id
                          ORDER BY PaymentNo";
        
        $stmt = $conn->prepare($scheduleQuery);
        $stmt->execute([':id' => $id]);
        $payments = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $installment, 'payments' => $payments]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Installment not found']);
    }
}

// ============================================
// GET INSTALLMENT STATS
// ============================================

function getInstallmentStats($conn, $currentBranch) {
    $query = "SELECT 
                COUNT(*) AS TotalInstallments,
                SUM(CASE WHEN Status = 'active' AND Status != 'returned' THEN 1 ELSE 0 END) AS ActiveInstallments,
                SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) AS CompletedInstallments,
                SUM(CASE WHEN Status = 'overdue' AND Status != 'returned' THEN 1 ELSE 0 END) AS OverdueInstallments,
                SUM(CASE WHEN Status = 'returned' THEN 1 ELSE 0 END) AS ReturnedInstallments,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN TotalAmount ELSE 0 END), 0) AS TotalLoanAmount,
                ISNULL(SUM(CASE WHEN Status != 'returned' THEN PaidAmount ELSE 0 END), 0) AS TotalPaidAmount
              FROM Installments
              WHERE Branch = :branch AND Status IS NOT NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $stats = $stmt->fetch();
    
    // Count overdue
    $overdueQuery = "SELECT COUNT(*) AS OverdueCount 
                     FROM Installments 
                     WHERE Status = 'active' AND NextPaymentDate < GETDATE() 
                     AND Status != 'returned' AND Branch = :branch";
    $stmt = $conn->prepare($overdueQuery);
    $stmt->execute([':branch' => $currentBranch]);
    $overdue = $stmt->fetch();
    
    $stats['OverdueCount'] = $overdue['OverdueCount'] ?? 0;
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

// ============================================
// RECORD PAYMENT
// ============================================

function recordPayment($conn, $data, $currentUser, $currentBranch) {
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
    
    $checkStatusQuery = "SELECT Status FROM Installments WHERE InstallmentID = :id AND Branch = :branch";
    $stmt = $conn->prepare($checkStatusQuery);
    $stmt->execute([':id' => $installmentId, ':branch' => $currentBranch]);
    $statusCheck = $stmt->fetch();
    
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
        $payment = $stmt->fetch();
        
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
        $installment = $stmt->fetch();
        
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
        $pendingResult = $stmt->fetch();
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
            $overdueResult = $stmt->fetch();
            
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
            // Use TOP 1 without parameter
            $nextQuery = "SELECT TOP 1 DueDate FROM InstallmentPayments 
                          WHERE InstallmentID = :id AND Status = 'pending' 
                          ORDER BY PaymentNo";
            $stmt = $conn->prepare($nextQuery);
            $stmt->execute([':id' => $installmentId]);
            $next = $stmt->fetch();
            
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
?>