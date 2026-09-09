<?php
session_start();
include __DIR__ . 'DB/dbcon.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid input data']);
    exit();
}

// Validate required fields
if (empty($input['full_name']) || empty($input['username'])) {
    echo json_encode(['error' => 'Full name and username are required']);
    exit();
}

try {
    // Get current username from session
    $currentUsername = $_SESSION['username'];
    
    // Check if username is being changed and if it already exists
    if ($currentUsername !== $input['username']) {
        $checkSql = "SELECT COUNT(*) as count FROM dbo.BS_Users WHERE USERNAME = :username AND USERNAME != :currentUsername";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':username', $input['username'], PDO::PARAM_STR);
        $checkStmt->bindParam(':currentUsername', $currentUsername, PDO::PARAM_STR);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['error' => 'Username already exists']);
            exit();
        }
    }
    
    // Build update query
    if (!empty($input['password'])) {
        // Update with password
        // Note: You should use proper password hashing
        // This assumes your database stores hashed passwords
        $hashedPassword = ($input['password']);
        
        $sql = "UPDATE dbo.BS_Users 
                SET FULLNAME = :full_name, 
                    USERNAME = :username, 
                    PASSWORD = :password 
                WHERE USERNAME = :currentUsername";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
    } else {
        // Update without password
        $sql = "UPDATE dbo.BS_Users 
                SET FULLNAME = :full_name, 
                    USERNAME = :username 
                WHERE USERNAME = :currentUsername";
        
        $stmt = $conn->prepare($sql);
    }
    
    // Bind common parameters
    $stmt->bindParam(':full_name', $input['full_name'], PDO::PARAM_STR);
    $stmt->bindParam(':username', $input['username'], PDO::PARAM_STR);
    $stmt->bindParam(':currentUsername', $currentUsername, PDO::PARAM_STR);
    
    // Execute update
    if ($stmt->execute()) {
        // Update session variables if username or name changed
        $_SESSION['username'] = $input['username'];
        $_SESSION['Name_of_user'] = $input['full_name'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to update profile']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Application error: ' . $e->getMessage()]);
}
?>