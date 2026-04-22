<?php
// api_users.php - Backend API for User Management
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
        case 'getUsers':
            getUsers($conn);
            break;
        case 'getUserById':
            getUserById($conn);
            break;
        case 'getUserStats':
            getUserStats($conn);
            break;
        case 'getRoles':
            getRoles($conn);
            break;
        case 'getPermissions':
            getPermissions($conn);
            break;
        case 'getUserActivity':
            getUserActivity($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'createUser':
            createUser($conn, $data, $currentUser);
            break;
        case 'loginUser':
            loginUser($conn, $data);
            break;
        case 'changePassword':
            changePassword($conn, $data, $currentUser);
            break;
        case 'resetPassword':
            resetPassword($conn, $data, $currentUser);
            break;
        case 'logUserActivity':
            logUserActivity($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateUser':
            updateUser($conn, $data, $currentUser);
            break;
        case 'updateUserRole':
            updateUserRole($conn, $data, $currentUser);
            break;
        case 'toggleUserStatus':
            toggleUserStatus($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'deleteUser':
            deleteUser($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// USER FUNCTIONS
// ============================================

function createUser($conn, $data, $currentUser) {
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $fullname = trim($data['fullname'] ?? '');
    $email = trim($data['email'] ?? '');
    $role = $data['role'] ?? 'staff';
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? '';
    
    // Validate required fields
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        return;
    }
    
    if (empty($password)) { 
        echo json_encode(['success' => false, 'message' => 'Password is required']); 
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }
    
    if (empty($fullname)) {
        echo json_encode(['success' => false, 'message' => 'Full name is required']);
        return;
    }
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    
    // Check if username exists
    $checkQuery = "SELECT COUNT(*) as count FROM Users WHERE Username = :username";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':username' => $username]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        return;
    }
    
    // Hash password (in production, use password_hash())
    $hashedPassword =  $password;
    
    $query = "INSERT INTO Users 
              (Username, PasswordHash, FullName, Email, Role, Phone, Address, IsActive, CreatedBy, CreatedAt)
              VALUES 
              (:username, :password, :fullname, :email, :role, :phone, :address, 1, :createdby, GETDATE())";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashedPassword,
        ':fullname' => $fullname,
        ':email' => $email,
        ':role' => $role,
        ':phone' => $phone,
        ':address' => $address,
        ':createdby' => $currentUser
    ]);
    
    $userId = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'user_id' => $userId
    ]);
}

function getUsers($conn) {
    $limit = $_GET['limit'] ?? 100;
    $role = $_GET['role'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    
    $roleFilter = $role !== 'all' ? "AND u.Role = :role" : "";
    $statusFilter = $status !== 'all' ? "AND u.IsActive = :status" : "";
    
    $query = "SELECT TOP (:limit) 
                u.UserID, u.Username, u.FullName, u.Email, u.Role, 
                u.Phone, u.Address, u.IsActive,
                FORMAT(u.LastLogin, 'yyyy-MM-dd HH:mm') AS LastLogin,
                FORMAT(u.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                u.CreatedBy,
                (SELECT COUNT(*) FROM UserActivity WHERE UserID = u.UserID AND ActivityDate >= DATEADD(DAY, -30, GETDATE())) AS RecentActivities
              FROM Users u
              WHERE 1=1 $roleFilter $statusFilter
              ORDER BY u.UserID DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    if ($role !== 'all') {
        $stmt->bindParam(':role', $role);
    }
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $users, 'count' => count($users)]);
}

function getUserById($conn) {
    $userId = $_GET['id'] ?? 0;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    $query = "SELECT 
                u.UserID, u.Username, u.FullName, u.Email, u.Role, 
                u.Phone, u.Address, u.IsActive,
                FORMAT(u.LastLogin, 'yyyy-MM-dd HH:mm') AS LastLogin,
                FORMAT(u.CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt,
                u.CreatedBy
              FROM Users u
              WHERE u.UserID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Get user activity
        $activityQuery = "SELECT TOP 10 
                            ActivityID, Activity, ActivityType, IPAddress,
                            FORMAT(ActivityDate, 'yyyy-MM-dd HH:mm') AS ActivityDate
                          FROM UserActivity
                          WHERE UserID = :id
                          ORDER BY ActivityDate DESC";
        
        $stmt = $conn->prepare($activityQuery);
        $stmt->execute([':id' => $userId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $user, 'activities' => $activities]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

function updateUser($conn, $data, $currentUser) {
    $userId = $data['user_id'] ?? 0;
    $fullname = $data['fullname'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? '';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    $query = "UPDATE Users 
              SET FullName = :fullname,
                  Email = :email,
                  Phone = :phone,
                  Address = :address,
                  UpdatedAt = GETDATE(),
                  UpdatedBy = :user
              WHERE UserID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':fullname' => $fullname,
        ':email' => $email,
        ':phone' => $phone,
        ':address' => $address,
        ':user' => $currentUser,
        ':id' => $userId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
}

function updateUserRole($conn, $data, $currentUser) {
    $userId = $data['user_id'] ?? 0;
    $role = $data['role'] ?? '';
    
    if (!$userId || !$role) {
        echo json_encode(['success' => false, 'message' => 'User ID and role required']);
        return;
    }
    
    $validRoles = ['admin', 'manager', 'staff', 'technician', 'viewer'];
    if (!in_array($role, $validRoles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        return;
    }
    
    $query = "UPDATE Users 
              SET Role = :role,
                  UpdatedAt = GETDATE(),
                  UpdatedBy = :user
              WHERE UserID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':role' => $role,
        ':user' => $currentUser,
        ':id' => $userId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'User role updated successfully']);
}

function toggleUserStatus($conn, $data, $currentUser) {
    $userId = $data['user_id'] ?? 0;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    $query = "UPDATE Users 
              SET IsActive = CASE WHEN IsActive = 1 THEN 0 ELSE 1 END,
                  UpdatedAt = GETDATE(),
                  UpdatedBy = :user
              WHERE UserID = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user' => $currentUser,
        ':id' => $userId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'User status toggled successfully']);
}

function deleteUser($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? $_GET['id'] ?? 0;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    // Prevent deleting own account
    session_start();
    $currentUser = $_SESSION['username'] ?? '';
    
    $checkQuery = "SELECT Username FROM Users WHERE UserID = :id";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['Username'] === $currentUser) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        // Delete user activity first
        $delActivity = "DELETE FROM UserActivity WHERE UserID = :id";
        $stmt = $conn->prepare($delActivity);
        $stmt->execute([':id' => $userId]);
        
        // Delete user
        $delUser = "DELETE FROM Users WHERE UserID = :id";
        $stmt = $conn->prepare($delUser);
        $stmt->execute([':id' => $userId]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function loginUser($conn, $data) {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $ipAddress = $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'];
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password required']);
        return;
    }
    
    $query = "SELECT UserID, Username, PasswordHash, FullName, Email, Role, IsActive 
              FROM Users 
              WHERE Username = :username";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        return;
    }
    
    if ($user['IsActive'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Account is disabled. Please contact administrator.']);
        return;
    }
    
    if (!password_verify($password, $user['PasswordHash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        return;
    }
    
    // Update last login
    $updateLogin = "UPDATE Users SET LastLogin = GETDATE() WHERE UserID = :id";
    $stmt = $conn->prepare($updateLogin);
    $stmt->execute([':id' => $user['UserID']]);
    
    // Log activity
    $logActivity = "INSERT INTO UserActivity (UserID, Activity, ActivityType, IPAddress, ActivityDate)
                    VALUES (:id, 'User logged in', 'login', :ip, GETDATE())";
    $stmt = $conn->prepare($logActivity);
    $stmt->execute([':id' => $user['UserID'], ':ip' => $ipAddress]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'user_id' => $user['UserID'],
            'username' => $user['Username'],
            'fullname' => $user['FullName'],
            'email' => $user['Email'],
            'role' => $user['Role']
        ]
    ]);
}

function changePassword($conn, $data, $currentUser) {
    $userId = $data['user_id'] ?? 0;
    $oldPassword = $data['old_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    
    if (!$userId || empty($oldPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'User ID, old password, and new password required']);
        return;
    }
    
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
        return;
    }
    
    $query = "SELECT PasswordHash FROM Users WHERE UserID = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    if (!password_verify($oldPassword, $user['PasswordHash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    $newHash = ($newPassword);
    
    $updateQuery = "UPDATE Users 
                    SET PasswordHash = :password,
                        UpdatedAt = GETDATE(),
                        UpdatedBy = :user
                    WHERE UserID = :id";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        ':password' => $newHash,
        ':user' => $currentUser,
        ':id' => $userId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
}

function resetPassword($conn, $data, $currentUser) {
    $userId = $data['user_id'] ?? 0;
    $newPassword = $data['new_password'] ?? 'password123';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    $newHash = ($newPassword);
    
    $updateQuery = "UPDATE Users 
                    SET PasswordHash = :password,
                        UpdatedAt = GETDATE(),
                        UpdatedBy = :user
                    WHERE UserID = :id";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        ':password' => $newHash,
        ':user' => $currentUser,
        ':id' => $userId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Password reset successfully. New password: ' . $newPassword]);
}

function getRoles($conn) {
    $roles = [
        ['role' => 'admin', 'description' => 'Full system access', 'permissions' => 'all'],
        ['role' => 'manager', 'description' => 'Manage users, inventory, sales', 'permissions' => 'manage_all'],
        ['role' => 'staff', 'description' => 'Process sales, view inventory', 'permissions' => 'sales_inventory'],
        ['role' => 'technician', 'description' => 'Manage repairs only', 'permissions' => 'repairs_only'],
        ['role' => 'viewer', 'description' => 'Read-only access', 'permissions' => 'read_only']
    ];
    
    echo json_encode(['success' => true, 'data' => $roles]);
}

function getPermissions($conn) {
    $permissions = [
        ['id' => 1, 'name' => 'view_dashboard', 'description' => 'View dashboard'],
        ['id' => 2, 'name' => 'manage_sales', 'description' => 'Process and view sales'],
        ['id' => 3, 'name' => 'manage_inventory', 'description' => 'Manage products and stock'],
        ['id' => 4, 'name' => 'manage_repairs', 'description' => 'Manage repair requests'],
        ['id' => 5, 'name' => 'manage_installments', 'description' => 'Manage installment plans'],
        ['id' => 6, 'name' => 'manage_users', 'description' => 'Manage system users'],
        ['id' => 7, 'name' => 'view_reports', 'description' => 'View reports'],
        ['id' => 8, 'name' => 'manage_settings', 'description' => 'Manage system settings']
    ];
    
    echo json_encode(['success' => true, 'data' => $permissions]);
}

function getUserStats($conn) {
    $query = "SELECT 
                COUNT(*) AS TotalUsers,
                SUM(CASE WHEN Role = 'admin' THEN 1 ELSE 0 END) AS AdminCount,
                SUM(CASE WHEN Role = 'manager' THEN 1 ELSE 0 END) AS ManagerCount,
                SUM(CASE WHEN Role = 'staff' THEN 1 ELSE 0 END) AS StaffCount,
                SUM(CASE WHEN Role = 'technician' THEN 1 ELSE 0 END) AS TechnicianCount,
                SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) AS ActiveUsers,
                SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) AS InactiveUsers,
                SUM(CASE WHEN LastLogin >= DATEADD(DAY, -7, GETDATE()) THEN 1 ELSE 0 END) AS ActiveThisWeek
              FROM Users";
    
    $stmt = $conn->query($query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function getUserActivity($conn) {
    $userId = $_GET['user_id'] ?? 0;
    $limit = $_GET['limit'] ?? 50;
    
    if ($userId) {
        $query = "SELECT TOP (:limit) 
                    ActivityID, Activity, ActivityType, IPAddress,
                    FORMAT(ActivityDate, 'yyyy-MM-dd HH:mm') AS ActivityDate
                  FROM UserActivity
                  WHERE UserID = :id
                  ORDER BY ActivityDate DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    } else {
        $query = "SELECT TOP (:limit) 
                    ua.ActivityID, ua.Activity, ua.ActivityType, ua.IPAddress,
                    FORMAT(ua.ActivityDate, 'yyyy-MM-dd HH:mm') AS ActivityDate,
                    u.Username, u.FullName
                  FROM UserActivity ua
                  LEFT JOIN Users u ON ua.UserID = u.UserID
                  ORDER BY ua.ActivityDate DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $activities]);
}

function logUserActivity($conn, $data, $currentUser) {
    $userId = $data['user_id'] ?? 0;
    $activity = $data['activity'] ?? '';
    $activityType = $data['activity_type'] ?? 'general';
    $ipAddress = $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'];
    
    if (!$userId || empty($activity)) {
        echo json_encode(['success' => false, 'message' => 'User ID and activity required']);
        return;
    }
    
    $query = "INSERT INTO UserActivity (UserID, Activity, ActivityType, IPAddress, ActivityDate)
              VALUES (:id, :activity, :type, :ip, GETDATE())";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':id' => $userId,
        ':activity' => $activity,
        ':type' => $activityType,
        ':ip' => $ipAddress
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Activity logged']);
}
?>