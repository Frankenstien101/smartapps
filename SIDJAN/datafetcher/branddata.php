<?php
// branddata.php - Backend API for Brand Management
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
    switch ($action) {
        case 'getBrands':
            getBrands($conn);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'addBrand':
            addBrand($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

function getBrands($conn) {
    $query = "SELECT BrandID, BrandName, Description, 
                     FORMAT(CreatedAt, 'yyyy-MM-dd HH:mm') AS CreatedAt
              FROM Brands 
              ORDER BY BrandName";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $brands = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $brands, 'count' => count($brands)]);
}

function addBrand($conn, $data, $currentUser) {
    $brandName = trim($data['brand_name'] ?? '');
    $description = $data['description'] ?? '';
    
    if (empty($brandName)) {
        echo json_encode(['success' => false, 'message' => 'Brand name is required']);
        return;
    }
    
    // Check if brand already exists
    $checkQuery = "SELECT COUNT(*) as count FROM Brands WHERE BrandName = :name";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([':name' => $brandName]);
    $exists = $stmt->fetch();
    
    if ($exists['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Brand already exists']);
        return;
    }
    
    $query = "INSERT INTO Brands (BrandName, Description, CreatedBy, CreatedAt) 
              VALUES (:name, :desc, :user, GETDATE())";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':name' => $brandName,
        ':desc' => $description,
        ':user' => $currentUser
    ]);
    
    $brandId = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Brand added successfully',
        'brand_id' => $brandId,
        'brand_name' => $brandName
    ]);
}
?>