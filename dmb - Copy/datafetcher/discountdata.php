<?php
// discountdata.php - Discount Management API (with Percentage & Exact Value)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// DATABASE CONNECTION
// ============================================
include '../DB/dbcon.php';

// Start session for user tracking
session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Main Branch';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

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
    global $currentBranch;
    
    switch ($action) {
        case 'getProducts':
            getProducts($conn, $currentBranch);
            break;
        case 'getBranches':
            getBranches($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'saveDiscounts':
            saveDiscounts($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// GET PRODUCTS WITH DISCOUNT (INCLUDING LESS)
// ============================================
function getProducts($conn, $currentBranch) {
    $branch = $_GET['branch'] ?? $currentBranch;
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand, 
                SellingPrice,
                ISNULL(AvailableQuantity, 0) as AvailableQuantity,
                ISNULL(Discount, 0) as Discount,
                ISNULL(Less, 0) as Less,  -- ADDED LESS FIELD
                Branch
              FROM Products
              WHERE 1=1";
    
    $params = [];
    
    if ($branch !== 'all') {
        $query .= " AND Branch = ?";
        $params[] = $branch;
    }
    
    if ($search !== '') {
        $query .= " AND (ProductCode LIKE ? OR ProductName LIKE ? OR Brand LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY ProductCode";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ]);
}

// ============================================
// GET BRANCHES
// ============================================
function getBranches($conn) {
    $query = "SELECT DISTINCT Branch FROM Products ORDER BY Branch";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'data' => $branches
    ]);
}

// ============================================
// SAVE DISCOUNTS (WITH BOTH DISCOUNT AND LESS)
// ============================================
function saveDiscounts($conn, $data, $currentUser) {
    $discounts = $data['discounts'] ?? [];
    
    if (empty($discounts)) {
        echo json_encode(['success' => false, 'message' => 'No discounts to save']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $updated = 0;
        $errors = [];
        
        foreach ($discounts as $discountData) {
            $productId = $discountData['product_id'] ?? 0;
            $discount = floatval($discountData['discount'] ?? 0);
            $less = floatval($discountData['less'] ?? 0);  // ADDED LESS VALUE
            
            if ($productId <= 0) {
                $errors[] = "Invalid product ID: $productId";
                continue;
            }
            
            // Validate discount
            if ($discount < 0 || $discount > 100) {
                $errors[] = "Discount percentage must be between 0-100 for product ID: $productId";
                continue;
            }
            
            if ($less < 0) {
                $errors[] = "Less amount cannot be negative for product ID: $productId";
                continue;
            }
            
            // Update both Discount and Less fields
            $query = "UPDATE Products 
                      SET Discount = ?,
                          Less = ?,
                          UpdatedBy = ?,
                          UpdatedAt = GETDATE()
                      WHERE ProductID = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$discount, $less, $currentUser, $productId]);
            
            $updated++;
        }
        
        $conn->commit();
        
        $message = "Successfully updated discounts for {$updated} product(s)";
        if (!empty($errors)) {
            $message .= " with " . count($errors) . " error(s)";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'updated' => $updated,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error saving discounts: ' . $e->getMessage()
        ]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// ADD SINGLE DISCOUNT UPDATE (OPTIONAL)
// ============================================
// You can also add this function if you need to update a single product
function updateSingleDiscount($conn, $data, $currentUser) {
    $productId = intval($data['product_id'] ?? 0);
    $discount = floatval($data['discount'] ?? 0);
    $less = floatval($data['less'] ?? 0);
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    if ($discount < 0 || $discount > 100) {
        echo json_encode(['success' => false, 'message' => 'Discount must be between 0-100']);
        return;
    }
    
    if ($less < 0) {
        echo json_encode(['success' => false, 'message' => 'Less amount cannot be negative']);
        return;
    }
    
    try {
        $query = "UPDATE Products 
                  SET Discount = ?,
                      Less = ?,
                      UpdatedBy = ?,
                      UpdatedAt = GETDATE()
                  WHERE ProductID = ?";
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$discount, $less, $currentUser, $productId]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Discount updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update discount'
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>