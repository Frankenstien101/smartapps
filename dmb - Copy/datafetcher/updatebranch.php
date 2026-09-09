<?php
// /dmb/datafetcher/updatebranch.php - Update branch in session

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$branch = $data['branch'] ?? '';
$branchName = $data['branch_name'] ?? $branch;

if (empty($branch)) {
    echo json_encode(['success' => false, 'message' => 'Branch name is required']);
    exit();
}

// Update session variables
$_SESSION['branch_name'] = $branch;
$_SESSION['BRANCH'] = $branch;
$_SESSION['branch_display'] = $branchName;

// Also update any other branch-related session variables
if (isset($_SESSION['current_branch'])) {
    $_SESSION['current_branch'] = $branch;
}

echo json_encode([
    'success' => true,
    'message' => 'Branch updated successfully',
    'branch' => $branch,
    'branch_name' => $branchName
]);
?>