<?php
// user_approval_api.php - Single backend file for user approval

session_start();
header('Content-Type: application/json');

// Check if user is logged in and is ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

require_once __DIR__ . '/../DB/dbcon.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // FETCH USERS
        $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $branch = isset($_GET['branch']) ? trim($_GET['branch']) : 'all';
        
        $where = [];
        $params = [];
        
        $where[] = "1=1";
        
        if ($status !== 'all') {
            $where[] = "STATUS = :status";
            $params[':status'] = $status;
        }
        
        if ($branch !== 'all') {
            $where[] = "BRANCH = :branch";
            $params[':branch'] = $branch;
        }
        
        if (!empty($search)) {
            $where[] = "(USERNAME LIKE :search OR FULLNAME LIKE :search OR BRANCH LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $whereSql = implode(" AND ", $where);
        
        $sql = "
            SELECT LINEID, USERNAME, FULLNAME, ROLE, STATUS, BRANCH
            FROM [TBC].[dbo].[users]
            WHERE $whereSql
            ORDER BY CASE WHEN STATUS = 'PENDING' THEN 1 ELSE 2 END, LINEID DESC
        ";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
        
    } elseif ($method === 'POST') {
        // UPDATE USER STATUS
        $input = json_decode(file_get_contents('php://input'), true);
        
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        $status = isset($input['status']) ? trim($input['status']) : '';
        
        if (!in_array($status, ['ACTIVE', 'REJECTED'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }
        
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit();
        }
        
        // Check current status
        $checkSql = "SELECT STATUS, FULLNAME FROM [TBC].[dbo].[users] WHERE LINEID = :id";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $checkStmt->execute();
        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        
        if ($user['STATUS'] !== 'PENDING') {
            echo json_encode(['success' => false, 'message' => "User already {$user['STATUS']}"]);
            exit();
        }
        
        // Update status
        $sql = "UPDATE [TBC].[dbo].[users] SET STATUS = :status WHERE LINEID = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $action = $status === 'ACTIVE' ? 'APPROVED' : 'REJECTED';
            echo json_encode([
                'success' => true,
                'message' => "User '{$user['FULLNAME']}' has been {$action}"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>