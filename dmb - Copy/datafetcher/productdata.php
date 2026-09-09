<?php
// product_api.php - Backend API for Product Management (with Batch Processing)
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
// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Start session for user tracking
session_start();
$currentUser = $_SESSION['username'] ?? $_SESSION['NAME'] ?? 'system';
$currentBranch = $_SESSION['branch_name'] ?? $_SESSION['branch'] ?? 'Koronadal';
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
        case 'getProducts':
            getProducts($conn, $currentBranch);
            break;
        case 'getProductById':
            getProductById($conn, $currentBranch);
            break;
        case 'getProductUnits':
            getProductUnits($conn, $currentBranch);
            break;
        case 'getMultipleProductUnits':
            getMultipleProductUnits($conn, $currentBranch);
            break;
        case 'getLowStock':
            getLowStockProducts($conn, $currentBranch);
            break;
        case 'getDashboardStats':
            getDashboardStats($conn, $currentBranch);
            break;
        case 'getStockHistory':
            getStockHistory($conn, $currentBranch);
            break;
        case 'exportProducts':
            exportProducts($conn, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($conn, $action, $currentUser) {
    global $currentBranch;
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'addProduct':
            addNewProduct($conn, $data, $currentUser, $currentBranch);
            break;
        case 'addStock':
            addStockToProduct($conn, $data, $currentUser, $currentBranch);
            break;
        case 'processExcelUpload':
            processExcelUpload($conn, $data, $currentUser, $currentBranch);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($conn, $action, $currentUser) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'updateProduct':
            updateProduct($conn, $data, $currentUser);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'deleteProduct':
            deleteProduct($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

// ============================================
// EXPORT PRODUCTS TO EXCEL
// ============================================
function exportProducts($conn, $currentBranch) {
    // Get all products with their units
    $query = "SELECT 
                p.ProductName,
                p.Category,
                p.Brand,
                p.ProductCode,
                p.CostPrice,
                p.SellingPrice,
                p.TotalQuantity as InitialStock,
                p.Description,
                '' as Supplier,
                '' as InvoiceNo,
                '' as IMEI_Serial
              FROM Products p
              WHERE p.Branch = ?
              ORDER BY p.ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get units for serialized products
    $unitQuery = "SELECT 
                    u.ProductID,
                    u.IMEINumber,
                    u.SerialNumber,
                    p.ProductName
                  FROM ProductUnits u
                  INNER JOIN Products p ON u.ProductID = p.ProductID
                  WHERE u.Branch = ? AND u.Status = 'available'
                  ORDER BY p.ProductName, u.UnitNumber";
    
    $unitStmt = $conn->prepare($unitQuery);
    $unitStmt->execute([$currentBranch]);
    $units = $unitStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create Excel data with header
    $excelData = [];
    
    // Add header row
    $excelData[] = [
        'Product Name',
        'Category',
        'Brand',
        'Product Code',
        'Cost Price',
        'Selling Price',
        'Initial Stock',
        'Description',
        'Supplier',
        'Invoice No',
        'IMEI/Serial (Optional)'
    ];
    
    // Group units by product
    $unitsByProduct = [];
    foreach ($units as $unit) {
        if (!isset($unitsByProduct[$unit['ProductID']])) {
            $unitsByProduct[$unit['ProductID']] = [];
        }
        $unitsByProduct[$unit['ProductID']][] = $unit;
    }
    
    // Add product data
    foreach ($products as $product) {
        $productId = $product['ProductID'] ?? null;
        $hasUnits = isset($unitsByProduct[$productId]) && count($unitsByProduct[$productId]) > 0;
        
        if ($hasUnits) {
            // For serialized products, add each unit as a separate row
            foreach ($unitsByProduct[$productId] as $unit) {
                $excelData[] = [
                    $product['ProductName'],
                    $product['Category'],
                    $product['Brand'],
                    $product['ProductCode'],
                    $product['CostPrice'],
                    $product['SellingPrice'],
                    1, // Each unit is 1 stock
                    $product['Description'],
                    '', // Supplier
                    '', // Invoice No
                    !empty($unit['IMEINumber']) ? $unit['IMEINumber'] : $unit['SerialNumber']
                ];
            }
        } else {
            // For bulk products, add single row
            $excelData[] = [
                $product['ProductName'],
                $product['Category'],
                $product['Brand'],
                $product['ProductCode'],
                $product['CostPrice'],
                $product['SellingPrice'],
                $product['InitialStock'] ?? 0,
                $product['Description'],
                '', // Supplier
                '', // Invoice No
                ''  // IMEI/Serial
            ];
        }
    }
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Products_Export_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Create Excel file using PhpSpreadsheet if available, else fallback to CSV
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Use PhpSpreadsheet for proper Excel export
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = array_keys($excelData[0]);
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
            $sheet->getColumnDimension(chr(65 + $col))->setAutoSize(true);
        }
        
        // Set data
        foreach ($excelData as $rowIndex => $row) {
            $rowNum = $rowIndex + 2;
            $colIndex = 0;
            foreach ($row as $value) {
                $sheet->setCellValue(chr(65 + $colIndex) . $rowNum, $value);
                $colIndex++;
            }
        }
        
        // Add instructions sheet
        $instructionsSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Instructions');
        $spreadsheet->addSheet($instructionsSheet);
        $instructionsSheet->setCellValue('A1', 'INSTRUCTIONS FOR PRODUCT UPLOAD');
        $instructionsSheet->setCellValue('A3', 'Required Fields:');
        $instructionsSheet->setCellValue('A4', '  - Product Name: Name of the product (required)');
        $instructionsSheet->setCellValue('A5', '  - Category: Product category (required)');
        $instructionsSheet->setCellValue('A6', '  - Selling Price: Selling price per unit (required)');
        $instructionsSheet->setCellValue('A7', '  - Initial Stock: Starting quantity (default: 0)');
        $instructionsSheet->setCellValue('A9', 'Optional Fields:');
        $instructionsSheet->setCellValue('A10', '  - Brand: Product brand');
        $instructionsSheet->setCellValue('A11', '  - Product Code: SKU or product code');
        $instructionsSheet->setCellValue('A12', '  - Cost Price: Cost per unit');
        $instructionsSheet->setCellValue('A13', '  - Description: Product description');
        $instructionsSheet->setCellValue('A14', '  - Supplier: Supplier name');
        $instructionsSheet->setCellValue('A15', '  - Invoice No: Invoice reference');
        $instructionsSheet->setCellValue('A16', '  - IMEI/Serial: For serialized items (one per row)');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        
    } else {
        // Fallback to CSV if PhpSpreadsheet not installed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Products_Export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM
        
        foreach ($excelData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
    
    exit();
}

// ============================================
// PRODUCT FUNCTIONS
// ============================================

function getProducts($conn, $currentBranch) {
    $query = "SELECT 
                p.ProductID, 
                p.ProductCode, 
                p.ProductName, 
                p.Category, 
                p.Brand, 
                p.CostPrice, 
                p.SellingPrice,
                p.ProductImagePath,
                p.Description,
                ISNULL(p.Discount, 0) as Discount,
                ISNULL(p.Less, 0) as Less,
                ISNULL(p.TotalQuantity, 0) as TotalQuantity,
                ISNULL(p.AvailableQuantity, 0) as AvailableQuantity,
                ISNULL(p.SoldQuantity, 0) as SoldQuantity
              FROM Products p
              WHERE p.Branch = ?
              ORDER BY p.ProductName";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all product IDs for batch unit fetching
    $productIds = array_column($products, 'ProductID');
    
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $unitQuery = "SELECT ProductID, COUNT(*) as unit_count
                      FROM ProductUnits 
                      WHERE ProductID IN ($placeholders) AND Branch = ? AND Status = 'available'
                      GROUP BY ProductID";
        
        $unitStmt = $conn->prepare($unitQuery);
        $params = array_merge($productIds, [$currentBranch]);
        $unitStmt->execute($params);
        $unitData = $unitStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $unitLookup = [];
        foreach ($unitData as $unit) {
            $unitLookup[$unit['ProductID']] = $unit;
        }
        
        $unitDetailsQuery = "SELECT ProductID, UnitNumber, IMEINumber, SerialNumber
                            FROM ProductUnits 
                            WHERE ProductID IN ($placeholders) AND Branch = ? AND Status = 'available'
                            ORDER BY ProductID, UnitNumber";
        
        $unitDetailsStmt = $conn->prepare($unitDetailsQuery);
        $unitDetailsStmt->execute($params);
        $allUnits = $unitDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $unitsByProduct = [];
        foreach ($allUnits as $unit) {
            $pid = $unit['ProductID'];
            if (!isset($unitsByProduct[$pid])) {
                $unitsByProduct[$pid] = [];
            }
            $unitsByProduct[$pid][] = $unit;
        }
        
        foreach ($products as &$product) {
            $pid = $product['ProductID'];
            if (isset($unitLookup[$pid])) {
                $product['HasUnits'] = $unitLookup[$pid]['unit_count'] > 0;
                $product['UnitCount'] = $unitLookup[$pid]['unit_count'];
                
                if (isset($unitsByProduct[$pid])) {
                    $unitStrings = [];
                    foreach ($unitsByProduct[$pid] as $unit) {
                        $unitStr = "Unit #{$unit['UnitNumber']}";
                        if (!empty($unit['IMEINumber'])) {
                            $unitStr .= " (IMEI: {$unit['IMEINumber']})";
                        }
                        if (!empty($unit['SerialNumber'])) {
                            $unitStr .= " (Serial: {$unit['SerialNumber']})";
                        }
                        $unitStrings[] = $unitStr;
                    }
                    $product['UnitList'] = implode(', ', array_slice($unitStrings, 0, 3));
                    if (count($unitStrings) > 3) {
                        $product['UnitList'] .= '...';
                    }
                } else {
                    $product['UnitList'] = '';
                }
            } else {
                $product['HasUnits'] = false;
                $product['UnitCount'] = 0;
                $product['UnitList'] = '';
            }
            $product['CurrentStock'] = $product['AvailableQuantity'];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

function getProductById($conn, $currentBranch) {
    $productId = $_GET['id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Category, 
                Brand, 
                ISNULL(TotalQuantity, 0) as TotalQuantity,
                ISNULL(AvailableQuantity, 0) as AvailableQuantity,
                ISNULL(SoldQuantity, 0) as SoldQuantity,
                CostPrice, 
                SellingPrice,
                ProductImagePath,
                Description,
                ISNULL(Discount, 0) as Discount,
                ISNULL(Less, 0) as Less
              FROM Products 
              WHERE ProductID = ? AND Branch = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$productId, $currentBranch]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $unitsQuery = "SELECT UnitID, UnitNumber, IMEINumber, SerialNumber, Status,
                              Branch, CONVERT(VARCHAR, CreatedAt, 120) AS CreatedAt
                       FROM ProductUnits
                       WHERE ProductID = ? AND Branch = ?
                       ORDER BY UnitNumber";
        $stmt = $conn->prepare($unitsQuery);
        $stmt->execute([$productId, $currentBranch]);
        $product['Units'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $product['HasUnits'] = count($product['Units']) > 0;
        
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
}

function getProductUnits($conn, $currentBranch) {
    $productId = $_GET['product_id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    $query = "SELECT 
                UnitID, UnitNumber, IMEINumber, SerialNumber, Status, Branch,
                CONVERT(VARCHAR, CreatedAt, 120) AS CreatedAt
              FROM ProductUnits
              WHERE ProductID = ? AND Branch = ? AND Status = 'available'
              ORDER BY UnitNumber";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$productId, $currentBranch]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $units, 'count' => count($units)]);
}

function getMultipleProductUnits($conn, $currentBranch) {
    $productIds = $_GET['product_ids'] ?? '';
    
    if (empty($productIds)) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $ids = explode(',', $productIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $query = "SELECT 
                ProductID, UnitID, UnitNumber, IMEINumber, SerialNumber, Status,
                CONVERT(VARCHAR, CreatedAt, 120) AS CreatedAt
              FROM ProductUnits
              WHERE ProductID IN ($placeholders) AND Branch = ? AND Status = 'available'
              ORDER BY ProductID, UnitNumber";
    
    $stmt = $conn->prepare($query);
    $params = array_merge($ids, [$currentBranch]);
    $stmt->execute($params);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group units by product ID
    $groupedUnits = [];
    foreach ($units as $unit) {
        $pid = $unit['ProductID'];
        if (!isset($groupedUnits[$pid])) {
            $groupedUnits[$pid] = [];
        }
        $groupedUnits[$pid][] = $unit;
    }
    
    echo json_encode(['success' => true, 'data' => $groupedUnits]);
}

function getLowStockProducts($conn, $currentBranch) {
    $threshold = $_GET['threshold'] ?? 10;
    
    $query = "SELECT 
                ProductID, 
                ProductCode, 
                ProductName, 
                Brand, 
                ISNULL(AvailableQuantity, 0) as CurrentStock, 
                SellingPrice,
                Category,
                ProductImagePath
              FROM Products 
              WHERE ISNULL(AvailableQuantity, 0) < ? AND Branch = ?
              ORDER BY AvailableQuantity ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$threshold, $currentBranch]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
}

// ============================================
// DELETE PRODUCT - SIMPLE DELETE (NO SALES CHECK)
// ============================================

function deleteProduct($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? $_GET['id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    // Check if product exists
    $checkStmt = $conn->prepare("SELECT ProductName, ProductImagePath FROM Products WHERE ProductID = ?");
    $checkStmt->execute([$productId]);
    $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        // Delete product units first
        $deleteUnits = $conn->prepare("DELETE FROM ProductUnits WHERE ProductID = ?");
        $deleteUnits->execute([$productId]);
        $unitsDeleted = $deleteUnits->rowCount();
        
        // Delete the product
        $deleteProduct = $conn->prepare("DELETE FROM Products WHERE ProductID = ?");
        $deleteProduct->execute([$productId]);
        
        $conn->commit();
        
        // Delete product image if exists
        if ($product['ProductImagePath']) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . $product['ProductImagePath'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Product '{$product['ProductName']}' deleted successfully. {$unitsDeleted} unit(s) removed from inventory.",
            'units_deleted' => $unitsDeleted,
            'history_preserved' => true,
            'note' => 'Stock history remains intact in StockInHistory table.'
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        
        // If foreign key error, try to handle it
        if (strpos($e->getMessage(), 'REFERENCE') !== false || strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot delete product: It has references in other tables (e.g., sales, history).',
                'error_detail' => $e->getMessage()
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}
// ============================================
// PROCESS EXCEL UPLOAD
// ============================================

function processExcelUpload($conn, $data, $currentUser, $currentBranch) {
    $excelData = $data['data'] ?? [];
    
    if (empty($excelData)) {
        echo json_encode(['success' => false, 'message' => 'No data to process']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $added = 0;
        $updated = 0;
        $errors = [];
        $unitsAdded = 0;
        $productsProcessed = 0;
        
        foreach ($excelData as $row) {
            // Get values from TEMPLATE WITH NAME AND CATEGORY
            $productCode = trim($row['Product Code'] ?? '');
            $productName = trim($row['Product Name'] ?? '');
            $category = trim($row['Category'] ?? '');
            $brand = trim($row['Brand'] ?? '');
            $costPrice = floatval($row['Cost Price'] ?? 0);
            $sellingPrice = floatval($row['Selling Price'] ?? 0);
            $initialStock = intval($row['Initial Stock'] ?? 0);
            
            // Skip empty rows
            if (empty($productCode)) {
                $errors[] = "Product Code is required";
                continue;
            }
            
            // Use product name or fallback to code
            if (empty($productName)) {
                $productName = $productCode;
            }
            
            // Use category or default
            if (empty($category)) {
                $category = 'Others';
            }
            
            if ($sellingPrice <= 0) {
                $errors[] = "Selling price must be greater than 0 for product: $productCode";
                continue;
            }
            
            $description = '';
            $supplier = '';
            $invoiceNo = '';
            
            // Check if product exists by ProductCode
            $checkQuery = "SELECT ProductID, TotalQuantity, AvailableQuantity, CostPrice, SellingPrice, ProductName, Category 
                           FROM Products 
                           WHERE ProductCode = ? AND Branch = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute([$productCode, $currentBranch]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // ============================================
                // UPDATE EXISTING PRODUCT - Update Name and Category too
                // ============================================
                $productId = $existing['ProductID'];
                $oldTotal = $existing['TotalQuantity'];
                $oldAvailable = $existing['AvailableQuantity'];
                
                // Update product info with Name and Category
                $updateQuery = "UPDATE Products 
                                SET ProductName = ?,
                                    Category = ?,
                                    Brand = ?,
                                    CostPrice = ?,
                                    SellingPrice = ?,
                                    UpdatedAt = GETDATE(),
                                    UpdatedBy = ?
                                WHERE ProductID = ?";
                
                $stmt = $conn->prepare($updateQuery);
                $stmt->execute([
                    $productName, 
                    $category, 
                    $brand, 
                    $costPrice, 
                    $sellingPrice, 
                    $currentUser, 
                    $productId
                ]);
                
                // Add stock if initial stock > 0
                if ($initialStock > 0) {
                    // Update quantities
                    $stockQuery = "UPDATE Products 
                                   SET TotalQuantity = TotalQuantity + ?,
                                       AvailableQuantity = AvailableQuantity + ?
                                   WHERE ProductID = ?";
                    $stmt = $conn->prepare($stockQuery);
                    $stmt->execute([$initialStock, $initialStock, $productId]);
                    
                    $newTotal = $oldTotal + $initialStock;
                    $newAvailable = $oldAvailable + $initialStock;
                    
                    // Log stock history
                    $historyQuery = "INSERT INTO StockInHistory 
                                    (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                     CostPrice, TotalCost, SupplierName, InvoiceNo, Notes, TransactionDate, AddedBy, Branch)
                                    VALUES 
                                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                    
                    $stmt = $conn->prepare($historyQuery);
                    $totalCost = $costPrice * $initialStock;
                    $notes = "Excel upload - Stock updated";
                    $stmt->execute([
                        $productId, $productName, $initialStock, $oldTotal, $newTotal,
                        $costPrice, $totalCost, $supplier, $invoiceNo, $notes,
                        $currentUser, $currentBranch
                    ]);
                    
                    $unitsAdded += $initialStock;
                }
                
                $updated++;
                $productsProcessed++;
                
            } else {
                // ============================================
                // CREATE NEW PRODUCT with Name and Category
                // ============================================
                // Generate unique product code if needed
                $finalProductCode = $productCode;
                $codeCheck = "SELECT COUNT(*) as count FROM Products WHERE ProductCode = ? AND Branch = ?";
                $stmt = $conn->prepare($codeCheck);
                $stmt->execute([$finalProductCode, $currentBranch]);
                $codeExists = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($codeExists['count'] > 0) {
                    $finalProductCode = $productCode . '-' . rand(10, 99);
                }
                
                $insertQuery = "INSERT INTO Products 
                                (ProductCode, ProductName, Category, Brand, CostPrice, SellingPrice, 
                                 Description, TotalQuantity, AvailableQuantity, Branch, CreatedBy, CreatedAt)
                                VALUES 
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
                
                $stmt = $conn->prepare($insertQuery);
                $stmt->execute([
                    $finalProductCode, 
                    $productName, 
                    $category, 
                    $brand, 
                    $costPrice, 
                    $sellingPrice,
                    $description, 
                    $initialStock, 
                    $initialStock, 
                    $currentBranch, 
                    $currentUser
                ]);
                
                $productId = $conn->lastInsertId();
                
                // Log initial stock
                if ($initialStock > 0) {
                    $historyQuery = "INSERT INTO StockInHistory 
                                    (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                     CostPrice, TotalCost, SupplierName, InvoiceNo, Notes, TransactionDate, AddedBy, Branch)
                                    VALUES 
                                    (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                    
                    $stmt = $conn->prepare($historyQuery);
                    $totalCost = $costPrice * $initialStock;
                    $notes = "Excel upload - New product (bulk)";
                    $stmt->execute([
                        $productId, $productName, $initialStock, $initialStock,
                        $costPrice, $totalCost, $supplier, $invoiceNo, $notes,
                        $currentUser, $currentBranch
                    ]);
                    
                    $unitsAdded += $initialStock;
                }
                
                $added++;
                $productsProcessed++;
            }
        }
        
        $conn->commit();
        
        $message = "Successfully processed $productsProcessed products. $added new products added, $updated products updated. Total stock added: $unitsAdded units.";
        
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', $errors);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'added' => $added,
            'updated' => $updated,
            'units_added' => $unitsAdded,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error processing upload: ' . $e->getMessage()
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
// ADD NEW PRODUCT
// ============================================

function addNewProduct($conn, $data, $currentUser, $currentBranch) {
    $productCode = $data['product_code'] ?? 'P' . date('YmdHis');
    $productName = trim($data['product_name'] ?? '');
    $category = trim($data['category'] ?? '');
    $brand = $data['brand'] ?? '';
    $description = $data['description'] ?? '';
    $costPrice = floatval($data['cost_price'] ?? 0);
    $sellingPrice = floatval($data['selling_price'] ?? 0);
    $discount = floatval($data['discount'] ?? 0);
    $less = floatval($data['less'] ?? 0);
    $productImage = $data['product_image'] ?? null;
    $units = $data['units'] ?? [];  
    $invoiceNo = $data['invoice_no'] ?? '';
    $supplierName = $data['supplier_name'] ?? '';
    $initialStock = intval($data['initial_stock'] ?? 0);
    
    // Validate required fields
    if (empty($productName)) {
        echo json_encode(['success' => false, 'message' => 'Product name is required']);
        return;
    }
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        return;
    }
    
    if ($sellingPrice <= 0) {
        echo json_encode(['success' => false, 'message' => 'Selling price must be greater than 0']);
        return;
    }
    
    // Check if units have IMEI/Serial
    $hasSerials = false;
    foreach ($units as $unit) {
        if (!empty($unit['imei']) || !empty($unit['serial'])) {
            $hasSerials = true;
            break;
        }
    }
    
    // Check if product already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM Products WHERE ProductName = ? AND Branch = ?");
    $checkStmt->execute([$productName, $currentBranch]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Product with this name already exists in this branch']);
        return;
    }
    
    // Save image to file system
    $imagePath = null;
    if ($productImage && !empty($productImage) && strpos($productImage, 'data:image') === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/SIDJAN/uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = 'jpg';
        if (strpos($productImage, 'data:image/png') === 0) $extension = 'png';
        elseif (strpos($productImage, 'data:image/gif') === 0) $extension = 'gif';
        elseif (strpos($productImage, 'data:image/jpeg') === 0) $extension = 'jpg';
        
        $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $fullPath = $uploadDir . $filename;
        
        $imageData = explode(',', $productImage);
        if (isset($imageData[1])) {
            $imageContent = base64_decode($imageData[1]);
            if (file_put_contents($fullPath, $imageContent)) {
                $imagePath = '/SIDJAN/uploads/products/' . $filename;
            }
        }
    }
    
    $conn->beginTransaction();
    
    try {
        // Determine total quantity
        $totalQuantity = !empty($units) ? count($units) : $initialStock;
        
        // Insert product with Discount and Less fields
        $insertStmt = $conn->prepare("INSERT INTO Products 
                        (ProductCode, ProductName, Category, Brand, Description, ProductImagePath, 
                         CostPrice, SellingPrice, Discount, Less, TotalQuantity, AvailableQuantity, SoldQuantity, 
                         Branch, CreatedBy, CreatedAt)
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, GETDATE())");
        
        $insertStmt->execute([
            $productCode, 
            $productName, 
            $category, 
            $brand, 
            $description, 
            $imagePath,
            $costPrice, 
            $sellingPrice, 
            $discount, 
            $less, 
            $totalQuantity, 
            $totalQuantity,
            $currentBranch, 
            $currentUser
        ]);
        
        $newProductId = $conn->lastInsertId();
        
        // If there are units with IMEI/Serial, add them to ProductUnits with batch insert
        if ($hasSerials && !empty($units)) {
            // Batch insert for better performance
            $batchInsertQuery = "INSERT INTO ProductUnits 
                                (ProductID, UnitNumber, IMEINumber, SerialNumber, Status, CostPrice, SellingPrice, Branch, CreatedBy, CreatedAt)
                                VALUES ";
            
            $insertValues = [];
            $insertParams = [];
            $unitNumber = 1;
            
            foreach ($units as $unit) {
                $insertValues[] = "(?, ?, ?, ?, 'available', ?, ?, ?, ?, GETDATE())";
                $insertParams[] = $newProductId;
                $insertParams[] = $unitNumber;
                $insertParams[] = $unit['imei'] ?? '';
                $insertParams[] = $unit['serial'] ?? '';
                $insertParams[] = $costPrice;
                $insertParams[] = $sellingPrice;
                $insertParams[] = $currentBranch;
                $insertParams[] = $currentUser;
                $unitNumber++;
            }
            
            $batchInsertQuery .= implode(',', $insertValues);
            $unitStmt = $conn->prepare($batchInsertQuery);
            $unitStmt->execute($insertParams);
            
            $message = "Product '{$productName}' added successfully with " . count($units) . " unit(s) (with IMEI/Serial tracking)";
        } else {
            // Log initial stock for bulk mode
            if ($initialStock > 0) {
                $historyQuery = "INSERT INTO StockInHistory 
                                (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                                 CostPrice, TotalCost, SupplierName, InvoiceNo, Notes, TransactionDate, AddedBy, Branch)
                                VALUES 
                                (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                
                $stmt = $conn->prepare($historyQuery);
                $totalCost = $costPrice * $initialStock;
                $notes = "Initial stock added";
                $stmt->execute([
                    $newProductId, $productName, $initialStock, $initialStock,
                    $costPrice, $totalCost, $supplierName, $invoiceNo, $notes,
                    $currentUser, $currentBranch
                ]);
            }
            
            $message = "Product '{$productName}' added successfully with {$initialStock} unit(s) (bulk quantity)";
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'product_id' => $newProductId,
            'product_code' => $productCode,
            'product_name' => $productName,
            'category' => $category,
            'image_path' => $imagePath,
            'units_added' => $totalQuantity,
            'mode' => $hasSerials ? 'per_unit' : 'direct_quantity',
            'branch' => $currentBranch
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ============================================
// ADD STOCK TO PRODUCT
// ============================================

function addStockToProduct($conn, $data, $currentUser, $currentBranch) {
    $productId = $data['product_id'] ?? 0;
    $units = $data['units'] ?? [];
    $invoiceNo = $data['invoice_no'] ?? '';
    $supplierName = $data['supplier'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    if (empty($units)) {
        echo json_encode(['success' => false, 'message' => 'At least one unit is required']);
        return;
    }
    
    // Check if this is direct quantity mode (units have no IMEI/Serial)
    $hasSerials = false;
    foreach ($units as $unit) {
        if (!empty($unit['imei']) || !empty($unit['serial'])) {
            $hasSerials = true;
            break;
        }
    }
    
    // Get product details
    $productStmt = $conn->prepare("SELECT ProductName, TotalQuantity, AvailableQuantity, CostPrice, SellingPrice 
                                   FROM Products WHERE ProductID = ? AND Branch = ?");
    $productStmt->execute([$productId, $currentBranch]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        $newUnitsCount = count($units);
        
        if ($hasSerials) {
            // ===== PER UNIT MODE: Batch insert units =====
            // Get current max unit number
            $maxUnitStmt = $conn->prepare("SELECT ISNULL(MAX(UnitNumber), 0) as MaxUnit FROM ProductUnits WHERE ProductID = ? AND Branch = ?");
            $maxUnitStmt->execute([$productId, $currentBranch]);
            $maxUnit = $maxUnitStmt->fetchColumn();
            
            // Prepare batch insert
            $batchInsertQuery = "INSERT INTO ProductUnits 
                                (ProductID, UnitNumber, IMEINumber, SerialNumber, Status, CostPrice, SellingPrice, Branch, CreatedBy, CreatedAt)
                                VALUES ";
            
            $insertValues = [];
            $insertParams = [];
            $unitNumber = $maxUnit + 1;
            
            foreach ($units as $unit) {
                $insertValues[] = "(?, ?, ?, ?, 'available', ?, ?, ?, ?, GETDATE())";
                $insertParams[] = $productId;
                $insertParams[] = $unitNumber;
                $insertParams[] = $unit['imei'] ?? '';
                $insertParams[] = $unit['serial'] ?? '';
                $insertParams[] = $product['CostPrice'];
                $insertParams[] = $product['SellingPrice'];
                $insertParams[] = $currentBranch;
                $insertParams[] = $currentUser;
                $unitNumber++;
            }
            
            $batchInsertQuery .= implode(',', $insertValues);
            $unitStmt = $conn->prepare($batchInsertQuery);
            $unitStmt->execute($insertParams);
            
            // Update product quantities
            $updateStmt = $conn->prepare("UPDATE Products 
                            SET TotalQuantity = ISNULL(TotalQuantity, 0) + ?,
                                AvailableQuantity = ISNULL(AvailableQuantity, 0) + ?,
                                UpdatedAt = GETDATE()
                            WHERE ProductID = ? AND Branch = ?");
            $updateStmt->execute([$newUnitsCount, $newUnitsCount, $productId, $currentBranch]);
            
            $oldStock = $product['TotalQuantity'];
            $newStock = $oldStock + $newUnitsCount;
            
            // Log stock addition
            $historyStmt = $conn->prepare("INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, InvoiceNo, SupplierName, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)");
            
            $historyStmt->execute([
                $productId, $product['ProductName'], $newUnitsCount, $oldStock, $newStock,
                $product['CostPrice'], $newUnitsCount * $product['CostPrice'], $invoiceNo, $supplierName, $notes,
                $currentUser, $currentBranch
            ]);
            
            $message = "Added {$newUnitsCount} new unit(s) with IMEI/Serial to {$product['ProductName']}";
            
        } else {
            // ===== DIRECT QUANTITY MODE =====
            $oldStock = $product['TotalQuantity'];
            $newStock = $oldStock + $newUnitsCount;
            
            // Update product quantities only
            $updateStmt = $conn->prepare("UPDATE Products 
                            SET TotalQuantity = ISNULL(TotalQuantity, 0) + ?,
                                AvailableQuantity = ISNULL(AvailableQuantity, 0) + ?,
                                UpdatedAt = GETDATE()
                            WHERE ProductID = ? AND Branch = ?");
            $updateStmt->execute([$newUnitsCount, $newUnitsCount, $productId, $currentBranch]);
            
            // Log stock history
            $historyStmt = $conn->prepare("INSERT INTO StockInHistory 
                            (ProductID, ProductName, QuantityAdded, OldStock, NewStock, 
                             CostPrice, TotalCost, InvoiceNo, SupplierName, Notes, TransactionDate, AddedBy, Branch)
                            VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)");
            
            $historyStmt->execute([
                $productId, $product['ProductName'], $newUnitsCount, $oldStock, $newStock,
                $product['CostPrice'], $newUnitsCount * $product['CostPrice'], $invoiceNo, $supplierName, $notes,
                $currentUser, $currentBranch
            ]);
            
            $message = "Added {$newUnitsCount} unit(s) to {$product['ProductName']} (Direct Quantity)";
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'units_added' => $newUnitsCount,
            'total_units' => $product['TotalQuantity'] + $newUnitsCount,
            'mode' => $hasSerials ? 'per_unit' : 'direct_quantity',
            'branch' => $currentBranch
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ============================================
// UPDATE PRODUCT
// ============================================

function updateProduct($conn, $data, $currentUser) {
    $productId = $data['product_id'] ?? 0;
    $productName = $data['product_name'] ?? '';
    $category = $data['category'] ?? '';
    $brand = $data['brand'] ?? '';
    $costPrice = $data['cost_price'] ?? 0;
    $sellingPrice = $data['selling_price'] ?? 0;
    $discount = floatval($data['discount'] ?? 0);
    $less = floatval($data['less'] ?? 0);
    $description = $data['description'] ?? '';
    $productImage = $data['product_image'] ?? null;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    $imagePath = null;
    if ($productImage && !empty($productImage) && strpos($productImage, 'data:image') === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/SIDJAN/uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = 'jpg';
        if (strpos($productImage, 'data:image/png') === 0) {
            $extension = 'png';
        } elseif (strpos($productImage, 'data:image/gif') === 0) {
            $extension = 'gif';
        }
        
        $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $fullPath = $uploadDir . $filename;
        
        $imageData = explode(',', $productImage);
        if (isset($imageData[1])) {
            $imageContent = base64_decode($imageData[1]);
            if (file_put_contents($fullPath, $imageContent)) {
                $imagePath = '/SIDJAN/uploads/products/' . $filename;
            }
        }
    }
    
    try {
        if ($imagePath) {
            $stmt = $conn->prepare("UPDATE Products 
                      SET ProductName = ?, Category = ?, Brand = ?, 
                          CostPrice = ?, SellingPrice = ?, Discount = ?, Less = ?,
                          ProductImagePath = ?,
                          Description = ?, UpdatedAt = GETDATE(), UpdatedBy = ?
                      WHERE ProductID = ?");
            $stmt->execute([$productName, $category, $brand, $costPrice, $sellingPrice, $discount, $less, $imagePath, $description, $currentUser, $productId]);
        } else {
            $stmt = $conn->prepare("UPDATE Products 
                      SET ProductName = ?, Category = ?, Brand = ?, 
                          CostPrice = ?, SellingPrice = ?, Discount = ?, Less = ?,
                          Description = ?, UpdatedAt = GETDATE(), UpdatedBy = ?
                      WHERE ProductID = ?");
            $stmt->execute([$productName, $category, $brand, $costPrice, $sellingPrice, $discount, $less, $description, $currentUser, $productId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ============================================
// GET STOCK HISTORY
// ============================================

function getStockHistory($conn, $currentBranch) {
    $limit = intval($_GET['limit'] ?? 100);
    $productId = $_GET['product_id'] ?? null;
    
    try {
        if ($productId) {
            $query = "SELECT TOP $limit 
                        TransactionID, ProductID, ProductName, QuantityAdded, OldStock, NewStock,
                        CostPrice, TotalCost, InvoiceNo, SupplierName, Notes,
                        FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm:ss') AS TransactionDate,
                        AddedBy
                      FROM StockInHistory
                      WHERE ProductID = ? AND Branch = ?
                      ORDER BY TransactionDate DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute([$productId, $currentBranch]);
        } else {
            $query = "SELECT TOP $limit 
                        TransactionID, ProductID, ProductName, QuantityAdded, OldStock, NewStock,
                        CostPrice, TotalCost, InvoiceNo, SupplierName, Notes,
                        FORMAT(TransactionDate, 'yyyy-MM-dd HH:mm:ss') AS TransactionDate,
                        AddedBy
                      FROM StockInHistory
                      WHERE Branch = ?
                      ORDER BY TransactionDate DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute([$currentBranch]);
        }
        
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
}

// ============================================
// GET DASHBOARD STATS
// ============================================

function getDashboardStats($conn, $currentBranch) {
    try {
        // Total Products
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Products WHERE Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalProducts = $stmt->fetchColumn();
        
        // Total Stock Value
        $stmt = $conn->prepare("SELECT ISNULL(SUM(ISNULL(TotalQuantity,0) * ISNULL(CostPrice,0)), 0) FROM Products WHERE Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalStockValue = $stmt->fetchColumn();
        
        // Low Stock Count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Products WHERE ISNULL(AvailableQuantity,0) < 10 AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $lowStockCount = $stmt->fetchColumn();
        
        // Total Units Added (30 days)
        $stmt = $conn->prepare("SELECT ISNULL(SUM(QuantityAdded), 0) FROM StockInHistory WHERE TransactionDate >= DATEADD(DAY, -30, GETDATE()) AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalUnitsAdded = $stmt->fetchColumn();
        
        // Total Units In Stock
        $stmt = $conn->prepare("SELECT ISNULL(SUM(ISNULL(TotalQuantity,0)), 0) FROM Products WHERE Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalUnitsInStock = $stmt->fetchColumn();
        
        // Total Sales 30 Days
        $stmt = $conn->prepare("SELECT ISNULL(SUM(TotalAmount), 0) FROM Sales WHERE SaleDate >= DATEADD(DAY, -30, GETDATE()) AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $totalSales30Days = $stmt->fetchColumn();
        
        // Transactions This Week
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Sales WHERE SaleDate >= DATEADD(DAY, -7, GETDATE()) AND Branch = ?");
        $stmt->execute([$currentBranch]);
        $transactionsThisWeek = $stmt->fetchColumn();
        
        $stats = [
            'TotalProducts' => intval($totalProducts),
            'TotalStockValue' => floatval($totalStockValue),
            'LowStockCount' => intval($lowStockCount),
            'TotalUnitsAdded' => intval($totalUnitsAdded),
            'TotalUnitsInStock' => intval($totalUnitsInStock),
            'TotalSales30Days' => floatval($totalSales30Days),
            'TransactionsThisWeek' => intval($transactionsThisWeek)
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
}
?>