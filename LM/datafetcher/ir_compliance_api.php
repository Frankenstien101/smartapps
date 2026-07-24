<?php
session_start();
include __DIR__ . '/../../DB/dbcon.php';

header('Content-Type: application/json');

// Configuration
$uploadDir = __DIR__ . '/../../uploads/ir_compliance/';
$maxFileSize = 10 * 1024 * 1024; // 10MB
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function sanitizeFileName($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
    return $filename;
}

function uploadFile($file, $lineid, $prefix = '') {
    global $uploadDir, $maxFileSize, $allowedExtensions;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'File too large. Max size: 10MB'];
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)];
    }
    
    // Generate unique filename
    $timestamp = time();
    $safeName = sanitizeFileName(pathinfo($file['name'], PATHINFO_FILENAME));
    $newFilename = $prefix . $lineid . '_' . $timestamp . '.' . $ext;
    $destination = $uploadDir . $newFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'path' => '/uploads/ir_compliance/' . $newFilename,
            'filename' => $newFilename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($action) {
        case 'get_not_ok_devices':
            handleGetNotOkDevices($conn);
            break;
            
        case 'get_device_details':
            handleGetDeviceDetails($conn);
            break;
            
        case 'mark_complied':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed for this action');
            }
            handleMarkComplied($conn, $uploadDir);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ==================== HANDLER FUNCTIONS ====================

function handleGetNotOkDevices($conn) {
    try {
        $companyId = $_SESSION['Company_ID'] ?? null;
        
        $sql = "SELECT TOP 1000 
                    [LINEID], [COMPANY_ID], [SITE_ID], [DEPARTMENT], [PRINCIPAL], 
                    [POSITION], [BRAND], [MODEL], [IMEI], [SERIAL], 
                    [DATE_DEPLOYED], [PERSON_USING], [NUMBER], [BALANCE], 
                    [LAST_LOAD_HISTORY], [LOAD_STATUS], [YEARS_USING], [REMARKS], 
                    [DATE_ADDED], [LOAD_TERMS], [STATUS], [CONSUMED], 
                    [IS_COMPLIED], [IR_COUNT], [DATE_COMPLIED], [DATA_BALANCE_MIN], 
                    [IT_RECOMMENDATION], [CHARGED_TO], [DEVICE_STATUS], 
                    [DATE_SURRENDERED], [DAYS_TO_REPAIR], [TEMPORARY_DEVICE], 
                    [REASON_CODE], [DEVICE_IR_COMPLIED], [ATTACHMENT1], [ATTACHMENT2]
                FROM [BSPIDBNEW].[dbo].[BS_Device] 
                WHERE [DEVICE_STATUS] != 'Good Condition'";
        
        if (!empty($companyId)) {
            $sql .= " AND [COMPANY_ID] = :company_id";
        }
        
        $sql .= " ORDER BY [LINEID] DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($companyId)) {
            $stmt->bindParam(':company_id', $companyId);
        }
        
        $stmt->execute();
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert boolean/null values for consistent JSON response
        foreach ($devices as &$device) {
            $device['DEVICE_IR_COMPLIED'] = $device['DEVICE_IR_COMPLIED'] ? 1 : 0;
            $device['DEVICE_STATUS'] = $device['DEVICE_STATUS'] ?? 'Unknown';
            
            // Clean up attachment paths
            if (!empty($device['ATTACHMENT1'])) {
                $device['ATTACHMENT1'] = str_replace(['..', '//'], ['', '/'], $device['ATTACHMENT1']);
                if (strpos($device['ATTACHMENT1'], '/') !== 0) {
                    $device['ATTACHMENT1'] = '/' . $device['ATTACHMENT1'];
                }
            }
            if (!empty($device['ATTACHMENT2'])) {
                $device['ATTACHMENT2'] = str_replace(['..', '//'], ['', '/'], $device['ATTACHMENT2']);
                if (strpos($device['ATTACHMENT2'], '/') !== 0) {
                    $device['ATTACHMENT2'] = '/' . $device['ATTACHMENT2'];
                }
            }
        }
        
        echo json_encode($devices);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load devices: ' . $e->getMessage()]);
    }
}

function handleGetDeviceDetails($conn) {
    try {
        if (!isset($_GET['lineid']) || empty($_GET['lineid'])) {
            throw new Exception('Device ID is required');
        }
        
        $lineid = intval($_GET['lineid']);
        $companyId = $_SESSION['Company_ID'] ?? null;
        
        $sql = "SELECT 
                    [LINEID], [COMPANY_ID], [SITE_ID], [DEPARTMENT], [PRINCIPAL], 
                    [POSITION], [BRAND], [MODEL], [IMEI], [SERIAL], 
                    [DATE_DEPLOYED], [PERSON_USING], [NUMBER], [BALANCE], 
                    [LAST_LOAD_HISTORY], [LOAD_STATUS], [YEARS_USING], [REMARKS], 
                    [DATE_ADDED], [LOAD_TERMS], [STATUS], [CONSUMED], 
                    [IS_COMPLIED], [IR_COUNT], [DATE_COMPLIED], [DATA_BALANCE_MIN], 
                    [IT_RECOMMENDATION], [CHARGED_TO], [DEVICE_STATUS], 
                    [DATE_SURRENDERED], [DAYS_TO_REPAIR], [TEMPORARY_DEVICE], 
                    [REASON_CODE], [DEVICE_IR_COMPLIED], [ATTACHMENT1], [ATTACHMENT2]
                FROM [BSPIDBNEW].[dbo].[BS_Device] 
                WHERE [LINEID] = :lineid";
        
        if (!empty($companyId)) {
            $sql .= " AND [COMPANY_ID] = :company_id";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':lineid', $lineid);
        if (!empty($companyId)) {
            $stmt->bindParam(':company_id', $companyId);
        }
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            throw new Exception('Device not found or access denied');
        }
        
        echo json_encode($device);
        
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleMarkComplied($conn, $uploadDir) {
    try {
        // Validate input
        if (!isset($_POST['lineid']) || empty($_POST['lineid'])) {
            throw new Exception('Device ID is required');
        }
        
        $lineid = intval($_POST['lineid']);
        $isComplied = isset($_POST['is_complied']) ? intval($_POST['is_complied']) : 0;
        $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
        
        if ($isComplied !== 1) {
            throw new Exception('Please confirm IR compliance');
        }
        
        // Check if device exists and is not already complied
        $checkSql = "SELECT [LINEID], [DEVICE_IR_COMPLIED], [ATTACHMENT1], [ATTACHMENT2], [REMARKS] 
                     FROM [BSPIDBNEW].[dbo].[BS_Device] 
                     WHERE [LINEID] = :lineid";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':lineid', $lineid);
        $checkStmt->execute();
        $device = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            throw new Exception('Device not found');
        }
        
        if ($device['DEVICE_IR_COMPLIED'] == 1) {
            throw new Exception('Device is already marked as IR Complied');
        }
        
        // Process file uploads
        $attachment1Path = null;
        $attachment2Path = null;
        $uploadedFiles = [];
        
        // Handle Attachment 1 (File or Camera)
        if (isset($_FILES['attachment1']) && $_FILES['attachment1']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['attachment1'], $lineid, 'file1_');
            if ($result['success']) {
                $attachment1Path = $result['path'];
                $uploadedFiles[] = $result['filename'];
            } else {
                throw new Exception('Attachment 1 upload failed: ' . $result['message']);
            }
        }
        
        // Handle Attachment 2 (File or Camera)
        if (isset($_FILES['attachment2']) && $_FILES['attachment2']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['attachment2'], $lineid, 'file2_');
            if ($result['success']) {
                $attachment2Path = $result['path'];
                $uploadedFiles[] = $result['filename'];
            } else {
                throw new Exception('Attachment 2 upload failed: ' . $result['message']);
            }
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        $dateComplied = date('Y-m-d H:i:s');
        $currentRemarks = $device['REMARKS'] ?? '';
        
        // Build update query
        $sql = "UPDATE [BSPIDBNEW].[dbo].[BS_Device] 
                SET [DEVICE_IR_COMPLIED] = 1, 
                    [DATE_COMPLIED] = :date_complied,
                    [IR_COUNT] = ISNULL([IR_COUNT], 0) + 1";
        
        // Prepare parameters
        $params = [
            ':date_complied' => $dateComplied,
            ':lineid' => $lineid
        ];
        
        // Add remarks with timestamp
        if (!empty($remarks)) {
            $remarkEntry = " [IR Complied: " . date('Y-m-d H:i') . "] " . $remarks;
            if (!empty($currentRemarks)) {
                $sql .= ", [REMARKS] = CONCAT([REMARKS], :remark_entry)";
            } else {
                $sql .= ", [REMARKS] = :remark_entry";
            }
            $params[':remark_entry'] = $remarkEntry;
        }
        
        // Add attachments
        if ($attachment1Path) {
            $sql .= ", [ATTACHMENT1] = :attachment1";
            $params[':attachment1'] = $attachment1Path;
        }
        
        if ($attachment2Path) {
            $sql .= ", [ATTACHMENT2] = :attachment2";
            $params[':attachment2'] = $attachment2Path;
        }
        
        $sql .= " WHERE [LINEID] = :lineid";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$result) {
            throw new Exception('Failed to update device record');
        }
        
        // Commit transaction
        $conn->commit();
        
        // Get updated device data
        $selectSql = "SELECT [LINEID], [DEVICE_IR_COMPLIED], [DATE_COMPLIED], [ATTACHMENT1], [ATTACHMENT2], [REMARKS] 
                      FROM [BSPIDBNEW].[dbo].[BS_Device] 
                      WHERE [LINEID] = :lineid";
        $selectStmt = $conn->prepare($selectSql);
        $selectStmt->bindParam(':lineid', $lineid);
        $selectStmt->execute();
        $updatedDevice = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Device marked as IR Complied successfully',
            'device' => $updatedDevice,
            'attachments' => $uploadedFiles,
            'date_complied' => $dateComplied
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Clean up uploaded files on error
        if (isset($uploadedFiles) && !empty($uploadedFiles)) {
            foreach ($uploadedFiles as $file) {
                $filePath = $uploadDir . $file;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>