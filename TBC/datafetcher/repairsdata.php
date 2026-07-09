<?php
// api_repairs.php - Backend API for Repair Management with Branch Support
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
        case 'getRepairs':
            getRepairs($conn, $currentBranch, $userRole);
            break;
        case 'getRepairById':
            getRepairById($conn, $currentBranch);
            break;
        case 'getRepairStats':
            getRepairStats($conn, $currentBranch);
            break;
        case 'getRepairByCustomer':
            getRepairByCustomer($conn, $currentBranch);
            break;
        case 'getRepairByDevice':
            getRepairByDevice($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'createRepair':
            createRepair($conn, $data, $currentUser, $currentBranch);
            break;
        case 'updateRepairStatus':
            updateRepairStatus($conn, $data, $currentUser);
            break;
        case 'addRepairNote':
            addRepairNote($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateRepair':
            updateRepair($conn, $data, $currentUser);
            break;
        case 'completeRepair':
            completeRepair($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'deleteRepair':
            deleteRepair($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// REPAIR FUNCTIONS WITH BRANCH
// ============================================

function createRepair($conn, $data, $currentUser, $currentBranch) {
    $repairNo = $data['repair_no'] ?? 'RPR-' . date('Ymd') . '-' . rand(1000, 9999);
    $customerName = trim($data['customer_name'] ?? '');
    $customerPhone = $data['customer_phone'] ?? '';
    $customerEmail = $data['customer_email'] ?? '';
    $customerAddress = $data['customer_address'] ?? '';
    $deviceType = $data['device_type'] ?? '';
    $deviceBrand = $data['device_brand'] ?? '';
    $deviceModel = $data['device_model'] ?? '';
    $serialNumber = $data['serial_number'] ?? '';
    $issue = $data['issue'] ?? '';
    $estimatedCost = floatval($data['estimated_cost'] ?? 0);
    $estimatedDays = intval($data['estimated_days'] ?? 3);
    $notes = $data['notes'] ?? '';
    
    // Validate required fields
    if (empty($customerName)) {
        echo json_encode(['success' => false, 'message' => 'Customer name is required']);
        return;
    }
    
    if (empty($deviceType)) {
        echo json_encode(['success' => false, 'message' => 'Device type is required']);
        return;
    }
    
    if (empty($issue)) {
        echo json_encode(['success' => false, 'message' => 'Issue description is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        // Calculate estimated completion date
        $estimatedDate = date('Y-m-d', strtotime("+{$estimatedDays} days"));
        
        $query = "INSERT INTO Repairs 
                  (RepairNo, CustomerName, CustomerPhone, CustomerEmail, CustomerAddress,
                   DeviceType, DeviceBrand, DeviceModel, SerialNumber, Issue,
                   EstimatedCost, EstimatedDays, EstimatedCompletionDate, Status,
                   Notes, CreatedBy, CreatedAt, Branch)
                  VALUES 
                  (:no, :name, :phone, :email, :address,
                   :dtype, :dbrand, :dmodel, :serial, :issue,
                   :ecost, :edays, :edate, 'pending',
                   :notes, :user, GETDATE(), :branch)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':no' => $repairNo,
            ':name' => $customerName,
            ':phone' => $customerPhone,
            ':email' => $customerEmail,
            ':address' => $customerAddress,
            ':dtype' => $deviceType,
            ':dbrand' => $deviceBrand,
            ':dmodel' => $deviceModel,
            ':serial' => $serialNumber,
            ':issue' => $issue,
            ':ecost' => $estimatedCost,
            ':edays' => $estimatedDays,
            ':edate' => $estimatedDate,
            ':notes' => $notes,
            ':user' => $currentUser,
            ':branch' => $currentBranch
        ]);
        
        $repairId = $conn->lastInsertId();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Repair request created successfully',
            'repair_id' => $repairId,
            'repair_no' => $repairNo,
            'estimated_completion' => $estimatedDate,
            'branch' => $currentBranch
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getRepairs($conn, $currentBranch, $userRole) {
    $limit = intval($_GET['limit'] ?? 100);
    $status = $_GET['status'] ?? 'all';
    
    // Use string concatenation for status filter to avoid parameter issues
    $statusFilter = "";
    if ($status !== 'all') {
        $statusFilter = "AND r.Status = '$status'";
    }
    
    // Branch filter - staff can only see their branch, admin can see all
    $branchFilter = "";
    $params = [];
    
    if ($userRole !== 'admin') {
        $branchFilter = "AND r.Branch = :branch";
        $params[':branch'] = $currentBranch;
    }
    
    // Use string concatenation for TOP limit
    $query = "SELECT TOP $limit
                r.RepairID, r.RepairNo, r.CustomerName, r.CustomerPhone,
                r.DeviceType, r.DeviceBrand, r.DeviceModel, r.SerialNumber,
                r.Issue, r.EstimatedCost, r.ActualCost, r.EstimatedDays,
                FORMAT(r.EstimatedCompletionDate, 'yyyy-MM-dd') AS EstimatedCompletionDate,
                FORMAT(r.ActualCompletionDate, 'yyyy-MM-dd') AS ActualCompletionDate,
                r.Status, r.Notes,
                FORMAT(r.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                r.CreatedBy, r.Technician,
                FORMAT(r.UpdatedAt, 'yyyy-MM-dd HH:mm') AS UpdatedAt,
                r.Branch
              FROM Repairs r
              WHERE 1=1 $statusFilter $branchFilter
              ORDER BY r.RepairID DESC";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $repairs, 'count' => count($repairs)]);
}

function getRepairById($conn, $currentBranch) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Repair ID required']);
        return;
    }
    
    $query = "SELECT 
                r.*,
                FORMAT(r.EstimatedCompletionDate, 'yyyy-MM-dd') AS EstimatedCompletionDate,
                FORMAT(r.ActualCompletionDate, 'yyyy-MM-dd') AS ActualCompletionDate,
                FORMAT(r.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                FORMAT(r.UpdatedAt, 'yyyy-MM-dd HH:mm') AS UpdatedAt
              FROM Repairs r
              WHERE r.RepairID = :id AND r.Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id, ':branch' => $currentBranch]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($repair) {
        // Get repair notes
        $notesQuery = "SELECT 
                        NoteID, Note, NoteType,
                        FORMAT(CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                        CreatedBy
                      FROM RepairNotes
                      WHERE RepairID = :id
                      ORDER BY CreatedAt DESC";
        
        $stmt = $conn->prepare($notesQuery);
        $stmt->execute([':id' => $id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $repair, 'notes' => $notes]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Repair record not found']);
    }
}

function getRepairStats($conn, $currentBranch) {
    $query = "SELECT 
                COUNT(*) AS TotalRepairs,
                SUM(CASE WHEN Status = 'pending' THEN 1 ELSE 0 END) AS PendingRepairs,
                SUM(CASE WHEN Status = 'in_progress' THEN 1 ELSE 0 END) AS InProgressRepairs,
                SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) AS CompletedRepairs,
                SUM(CASE WHEN Status = 'cancelled' THEN 1 ELSE 0 END) AS CancelledRepairs,
                SUM(CASE WHEN Status = 'for_pickup' THEN 1 ELSE 0 END) AS ForPickupRepairs,
                ISNULL(SUM(ActualCost), 0) AS TotalRevenue,
                ISNULL(AVG(CAST(DATEDIFF(DAY, CreatedAt, ActualCompletionDate) AS FLOAT)), 0) AS AvgCompletionDays
              FROM Repairs
              WHERE Branch = :branch";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':branch' => $currentBranch]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get today's repairs for this branch
    $todayQuery = "SELECT COUNT(*) AS TodayRepairs 
                   FROM Repairs 
                   WHERE CAST(CreatedAt AS DATE) = CAST(GETDATE() AS DATE) 
                   AND Branch = :branch";
    $stmt = $conn->prepare($todayQuery);
    $stmt->execute([':branch' => $currentBranch]);
    $today = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['TodayRepairs'] = $today['TodayRepairs'] ?? 0;
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function getRepairByCustomer($conn, $currentBranch) {
    $phone = $_GET['phone'] ?? '';
    $name = $_GET['name'] ?? '';
    
    if (!$phone && !$name) {
        echo json_encode(['success' => false, 'message' => 'Customer phone or name required']);
        return;
    }
    
    $query = "SELECT 
                RepairID, RepairNo, DeviceType, DeviceBrand, DeviceModel,
                Issue, Status, EstimatedCost, ActualCost,
                FORMAT(CreatedAt, 'yyyy-MM-dd') AS CreatedAt,
                FORMAT(EstimatedCompletionDate, 'yyyy-MM-dd') AS EstimatedCompletionDate
              FROM Repairs
              WHERE (CustomerPhone = :phone OR CustomerName LIKE :name)
              AND Branch = :branch
              ORDER BY RepairID DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':phone' => $phone,
        ':name' => "%{$name}%",
        ':branch' => $currentBranch
    ]);
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $repairs]);
}

function getRepairByDevice($conn, $currentBranch) {
    $serialNumber = $_GET['serial'] ?? '';
    $deviceType = $_GET['type'] ?? '';
    
    if (!$serialNumber && !$deviceType) {
        echo json_encode(['success' => false, 'message' => 'Serial number or device type required']);
        return;
    }
    
    $query = "SELECT 
                RepairID, RepairNo, CustomerName, CustomerPhone,
                DeviceBrand, DeviceModel, Issue, Status,
                FORMAT(CreatedAt, 'yyyy-MM-dd') AS CreatedAt
              FROM Repairs
              WHERE (SerialNumber = :serial OR DeviceType LIKE :type)
              AND Branch = :branch
              ORDER BY RepairID DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':serial' => $serialNumber,
        ':type' => "%{$deviceType}%",
        ':branch' => $currentBranch
    ]);
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $repairs]);
}

function updateRepairStatus($conn, $data, $currentUser) {
    $repairId = $data['repair_id'] ?? 0;
    $status = $data['status'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$repairId) {
        echo json_encode(['success' => false, 'message' => 'Repair ID required']);
        return;
    }
    
    $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled', 'for_pickup'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $updateQuery = "UPDATE Repairs 
                        SET Status = :status, 
                            UpdatedAt = GETDATE(),
                            UpdatedBy = :user
                        WHERE RepairID = :id";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([
            ':status' => $status,
            ':user' => $currentUser,
            ':id' => $repairId
        ]);
        
        // Add status change note
        if (!empty($notes)) {
            $noteQuery = "INSERT INTO RepairNotes 
                         (RepairID, Note, NoteType, CreatedBy, CreatedAt)
                         VALUES 
                         (:id, :note, 'status_change', :user, GETDATE())";
            
            $stmt = $conn->prepare($noteQuery);
            $stmt->execute([
                ':id' => $repairId,
                ':note' => "Status changed to {$status}. " . $notes,
                ':user' => $currentUser
            ]);
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Repair status updated successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateRepair($conn, $data, $currentUser) {
    $repairId = $data['repair_id'] ?? 0;
    $actualCost = $data['actual_cost'] ?? null;
    $technician = $data['technician'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$repairId) {
        echo json_encode(['success' => false, 'message' => 'Repair ID required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $updateQuery = "UPDATE Repairs 
                        SET ActualCost = ISNULL(:cost, ActualCost),
                            Technician = :tech,
                            Notes = ISNULL(Notes + CHAR(10) + :note, :note),
                            UpdatedAt = GETDATE(),
                            UpdatedBy = :user
                        WHERE RepairID = :id";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([
            ':cost' => $actualCost,
            ':tech' => $technician,
            ':note' => $notes,
            ':user' => $currentUser,
            ':id' => $repairId
        ]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Repair updated successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function completeRepair($conn, $data, $currentUser) {
    $repairId = $data['repair_id'] ?? 0;
    $actualCost = floatval($data['actual_cost'] ?? 0);
    $notes = $data['notes'] ?? '';
    
    if (!$repairId) {
        echo json_encode(['success' => false, 'message' => 'Repair ID required']);
        return;
    }
    
    if ($actualCost <= 0) {
        echo json_encode(['success' => false, 'message' => 'Actual cost is required']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $completeQuery = "UPDATE Repairs 
                          SET Status = 'completed',
                              ActualCost = :cost,
                              ActualCompletionDate = GETDATE(),
                              UpdatedAt = GETDATE(),
                              UpdatedBy = :user
                          WHERE RepairID = :id";
        
        $stmt = $conn->prepare($completeQuery);
        $stmt->execute([
            ':cost' => $actualCost,
            ':user' => $currentUser,
            ':id' => $repairId
        ]);
        
        // Add completion note
        $noteQuery = "INSERT INTO RepairNotes 
                     (RepairID, Note, NoteType, CreatedBy, CreatedAt)
                     VALUES 
                     (:id, :note, 'completion', :user, GETDATE())";
        
        $stmt = $conn->prepare($noteQuery);
        $stmt->execute([
            ':id' => $repairId,
            ':note' => "Repair completed. Final cost: ₱" . number_format($actualCost, 2) . ". " . $notes,
            ':user' => $currentUser
        ]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Repair marked as completed']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function addRepairNote($conn, $data, $currentUser) {
    $repairId = $data['repair_id'] ?? 0;
    $note = $data['note'] ?? '';
    $noteType = $data['note_type'] ?? 'general';
    
    if (!$repairId || empty($note)) {
        echo json_encode(['success' => false, 'message' => 'Repair ID and note are required']);
        return;
    }
    
    $query = "INSERT INTO RepairNotes 
              (RepairID, Note, NoteType, CreatedBy, CreatedAt)
              VALUES 
              (:id, :note, :type, :user, GETDATE())";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':id' => $repairId,
        ':note' => $note,
        ':type' => $noteType,
        ':user' => $currentUser
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Note added successfully']);
}

function deleteRepair($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $repairId = $data['repair_id'] ?? $_GET['id'] ?? 0;
    
    if (!$repairId) {
        echo json_encode(['success' => false, 'message' => 'Repair ID required']);
        return;
    }
    
    // Check if repair is completed (can't delete completed repairs)
    $checkQuery = "SELECT Status FROM Repairs WHERE RepairID = :id";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':id' => $repairId]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($repair && $repair['Status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete completed repair']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        // Delete notes first
        $delNotes = "DELETE FROM RepairNotes WHERE RepairID = :id";
        $stmt = $conn->prepare($delNotes);
        $stmt->execute([':id' => $repairId]);
        
        // Delete repair
        $delRepair = "DELETE FROM Repairs WHERE RepairID = :id";
        $stmt = $conn->prepare($delRepair);
        $stmt->execute([':id' => $repairId]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Repair deleted successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>