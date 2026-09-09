<?php
/**
 * Load Guard - Device Serial Viewer
 */

session_start();

// ============================================
// 1. DATABASE CONNECTION
// ============================================
$dbPath = __DIR__ . '/../../../DB/dbcon.php';

if (!file_exists($dbPath)) {
    die('Database connection file not found at: ' . $dbPath);
}

include $dbPath;

// ============================================
// 2. GET DEVICE BY LINEID
// ============================================
function getDeviceByLineId($conn, $lineId) {
    if (!$conn || !$lineId) {
        return null;
    }

    try {
        $sql = "SELECT
            LINEID, COMPANY_ID, SITE_ID, DEPARTMENT, PRINCIPAL, POSITION,
            BRAND, MODEL, IMEI, SERIAL, DATE_DEPLOYED, PERSON_USING,
            PERSON_IMAGE, NUMBER, BALANCE, CONSUMED, REMARKS,
            LAST_LOAD_HISTORY, LOAD_STATUS, LOAD_TERMS, DATA_BALANCE_MIN,
            DEVICE_STATUS, REASON_CODE, DATE_SURRENDERED, DAYS_TO_REPAIR,
            TEMPORARY_DEVICE, IT_RECOMMENDATION, CHARGED_TO, STATUS,
            IS_COMPLIED, DATE_COMPLIED,
            CASE 
                WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
                THEN DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY) 
                ELSE NULL 
            END AS NEXT_LOAD_SCHEDULE,
            CASE 
                WHEN LOAD_TERMS IS NOT NULL AND LAST_LOAD_HISTORY IS NOT NULL 
                THEN DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) 
                ELSE NULL 
            END AS DAYS_LEFT
        FROM dbo.BS_Device
        WHERE LINEID = :lineId";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':lineId', $lineId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

// ============================================
// 3. CHECK FOR DUPLICATE CONCERN TODAY
// ============================================
function checkDuplicateConcernToday($conn, $serial, $type) {
    if (!$conn || !$serial || !$type) {
        return false;
    }

    try {
        $sql = "SELECT COUNT(*) as count 
                FROM dbo.Concerns 
                WHERE SERIAL = :serial 
                  AND TYPE = :type 
                  AND DATE_RAISE = CAST(GETDATE() AS DATE)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':serial', $serial);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        error_log("Database error in checkDuplicateConcernToday: " . $e->getMessage());
        return false;
    }
}

// ============================================
// 4. SAVE IMAGE TO SERVER
// ============================================
function saveImageToServer($imageData, $lineId) {
    $uploadDir = __DIR__ . '/img/users/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $cleanLineId = preg_replace('/[^a-zA-Z0-9_-]/', '', $lineId);
    $filename = 'user_' . $cleanLineId . '_' . time() . '.jpg';
    $filePath = $uploadDir . $filename;
    
    if (strpos($imageData, 'base64,') !== false) {
        $imageData = substr($imageData, strpos($imageData, 'base64,') + 7);
    }
    
    $imageData = base64_decode($imageData);
    
    if ($imageData === false) {
        return false;
    }
    
    if (file_put_contents($filePath, $imageData) !== false) {
        return 'img/users/' . $filename;
    }
    
    return false;
}

// ============================================
// 5. UPDATE DEVICE IMAGE PATH
// ============================================
function updateDeviceImagePath($conn, $lineId, $imagePath) {
    if (!$conn || !$lineId || !$imagePath) {
        return false;
    }

    try {
        $sql = "UPDATE dbo.BS_Device SET PERSON_IMAGE = :image_path WHERE LINEID = :lineId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':image_path', $imagePath);
        $stmt->bindParam(':lineId', $lineId);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// ============================================
// 6. SET FLASH MESSAGE
// ============================================
function setFlashMessage($message) {
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// ============================================
// 7. MAIN EXECUTION
// ============================================
$device       = null;
$deviceFound  = false;
$isBoundDevice = false;
$boundLineId = null;
$concernMessage = getFlashMessage();

// ============================================
// PROCESS POST REQUESTS WITH REDIRECT
// ============================================

// Handle Bind Device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bind_lineid']) && !empty($_POST['bind_lineid'])) {
    $boundLineId = trim($_POST['bind_lineid']);
    $tempDevice = getDeviceByLineId($conn, $boundLineId);
    if ($tempDevice !== null && is_array($tempDevice)) {
        $_SESSION['bound_lineid'] = $boundLineId;
        setFlashMessage('✅ Device bound successfully!');
    } else {
        setFlashMessage('❌ Invalid LINEID. Please check and try again.');
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle Unbind
if (isset($_GET['unbind']) && $_GET['unbind'] === 'true') {
    unset($_SESSION['bound_lineid']);
    unset($_SESSION['image_uploaded']);
    setFlashMessage('🔓 Device unbound successfully.');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image']) && isset($_SESSION['bound_lineid'])) {
    $uploadToken = md5($_SESSION['bound_lineid'] . '_' . ($_POST['image_data'] ?? ''));
    
    if (!isset($_SESSION['last_upload_token']) || $_SESSION['last_upload_token'] !== $uploadToken) {
        $imageData = $_POST['image_data'] ?? '';
        $lineId = $_SESSION['bound_lineid'];
        
        if (!empty($imageData) && !empty($lineId)) {
            $imagePath = saveImageToServer($imageData, $lineId);
            
            if ($imagePath !== false) {
                if (updateDeviceImagePath($conn, $lineId, $imagePath)) {
                    $_SESSION['last_upload_token'] = $uploadToken;
                    $_SESSION['last_upload_time'] = time();
                    setFlashMessage('✅ Image uploaded successfully!');
                } else {
                    setFlashMessage('❌ Failed to update database.');
                }
            } else {
                setFlashMessage('❌ Failed to save image file.');
            }
        } else {
            setFlashMessage('❌ No image data received.');
        }
    } else {
        setFlashMessage('✅ Image already uploaded.');
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ============================================
// HANDLE RAISE CONCERN - FIXED: Get device data first
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['raise_concern'])) {
    // Get the bound LINEID from session
    $boundLineId = $_SESSION['bound_lineid'] ?? '';
    
    // Get device data using the bound LINEID
    $deviceData = getDeviceByLineId($conn, $boundLineId);
    
    if ($deviceData === null) {
        setFlashMessage('⚠️ Device not found. Please re-bind your device.');
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    
    $concernType = $_POST['concern_type'] ?? '';
    $concernText = trim($_POST['concern_text'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    
    $serial = $deviceData['SERIAL'] ?? '';
    $person = $deviceData['PERSON_USING'] ?? '';
    $number = $deviceData['NUMBER'] ?? '';

    if (empty($concernType)) {
        setFlashMessage('Please select a concern type.');
    } elseif (!$serial) {
        setFlashMessage('Serial number not found for this device.');
    } elseif (checkDuplicateConcernToday($conn, $serial, $concernType)) {
        setFlashMessage('⚠️ A concern of type "' . $concernType . '" has already been raised for this device today.');
    } else {
        try {
            $sql = "INSERT INTO dbo.Concerns 
                        (SERIAL, PERSON, NUMBER, DATE_RAISE, TIME_RAISE, TYPE, CONCERN_TEXT, REMARKS, STATUS)
                    VALUES 
                        (:serial, :person, :number, CAST(GETDATE() AS DATE), CAST(GETDATE() AS TIME), :type, :concern_text, :remarks, 'OPEN')";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':serial', $serial);
            $stmt->bindParam(':person', $person);
            $stmt->bindParam(':number', $number);
            $stmt->bindParam(':type', $concernType);
            $stmt->bindParam(':concern_text', $concernText);
            $stmt->bindParam(':remarks', $remarks);
            $stmt->execute();

            setFlashMessage('✅ Concern raised successfully!');
        } catch (PDOException $e) {
            setFlashMessage('❌ Error saving concern: ' . $e->getMessage());
        }
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ============================================
// LOAD DEVICE DATA (GET REQUEST)
// ============================================

// Check session for bound LINEID
if (isset($_SESSION['bound_lineid']) && !empty($_SESSION['bound_lineid'])) {
    $boundLineId = $_SESSION['bound_lineid'];
    $device = getDeviceByLineId($conn, $boundLineId);
    if ($device !== null && is_array($device)) {
        $deviceFound = true;
        $isBoundDevice = true;
    } else {
        unset($_SESSION['bound_lineid']);
        setFlashMessage('⚠️ Previously bound LINEID is no longer valid.');
        $concernMessage = getFlashMessage();
    }
}

// Check URL parameter for LINEID
if (!$deviceFound && isset($_GET['lineid']) && !empty($_GET['lineid'])) {
    $boundLineId = trim($_GET['lineid']);
    $tempDevice = getDeviceByLineId($conn, $boundLineId);
    if ($tempDevice !== null && is_array($tempDevice)) {
        $_SESSION['bound_lineid'] = $boundLineId;
        $device = $tempDevice;
        $deviceFound = true;
        $isBoundDevice = true;
        setFlashMessage('✅ Device loaded from URL.');
        $concernMessage = getFlashMessage();
    }
}

// Get flash message again in case it was set during GET
if (empty($concernMessage)) {
    $concernMessage = getFlashMessage();
}

// Variables for display
$osName      = php_uname('s');
$currentTime = date('Y-m-d H:i:s');
$showBindModal = !$deviceFound && !$isBoundDevice;
$baseImageUrl = '/lm/home/pages/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/LM/Home/img/loadguard2.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    
    <title>Load Guard</title>
    <style>
        /* ... all your existing styles ... */
        :root {
            --brown-dark: #3E2723;
            --brown-medium: #5D4037;
            --brown-light: #795548;
            --brown-lighter: #A1887F;
            --gold: #FFD700;
            --success: #2E7D32;
            --danger: #C62828;
            --warning: #E65100;
            --info: #2196F3;
            --text-light: #FFFFFF;
            --bg-dark: #1A100E;
            --bg-card: rgba(62, 39, 35, 0.95);
            --shadow: 0 8px 32px rgba(0,0,0,0.6);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-dark);
            background-image: linear-gradient(180deg, #1A100E 0%, #2C1810 100%);
            min-height: 100vh;
            padding: 16px;
            padding-top: calc(16px + var(--safe-top));
            padding-bottom: calc(16px + var(--safe-bottom));
            color: var(--text-light);
        }

        .container { max-width: 1000px; width: 100%; margin: 0 auto; }

        .header {
            display: flex; align-items: center; justify-content: space-between;
            padding-bottom: 20px; border-bottom: 2px solid rgba(139, 115, 85, 0.3);
            margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
        }

        .logo { display: flex; align-items: center; gap: 14px; }
        .logo-icon {
            width: 56px; height: 56px; background: linear-gradient(135deg, var(--brown-dark), var(--brown-medium));
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 900; color: var(--gold); border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .app-title { font-size: clamp(22px, 3vw, 32px); font-weight: 700; color: var(--gold); }
        .header-status { font-size: 13px; background: rgba(62, 39, 35, 0.6); padding: 8px 16px; border-radius: 20px; }

        .serial-section {
            background: var(--bg-card); border-radius: var(--border-radius);
            padding: 28px; text-align: center; border: 1px solid rgba(139, 115, 85, 0.2);
            box-shadow: var(--shadow); margin-bottom: 20px;
        }

        .qr-wrapper { display: inline-block; padding: 12px; background: #fff; border-radius: 16px; margin-bottom: 16px; box-shadow: 0 0 20px rgba(255, 215, 0, 0.4); }
        .qr-wrapper img { display: block; width: 150px; height: 150px; }

        .serial-number {
            font-size: clamp(26px, 5.5vw, 42px); font-weight: 800; font-family: 'Courier New', monospace;
            color: var(--gold); margin: 10px 0; word-break: break-all;
        }

        .device-card {
            background: var(--bg-card); border-radius: var(--border-radius);
            padding: 28px; border: 1px solid rgba(139, 115, 85, 0.2);
            box-shadow: var(--shadow); margin-bottom: 20px;
        }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 16px 0; }
        .info-item { background: rgba(0, 0, 0, 0.2); padding: 14px 16px; border-radius: 8px; border: 1px solid rgba(139, 115, 85, 0.08); }
        .info-item .label { display: block; font-size: 10px; color: var(--brown-lighter); margin-bottom: 4px; text-transform: uppercase; }
        .info-item .value { font-size: 16px; font-weight: 600; }

        .load-section {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px; margin: 16px 0; padding: 20px; background: rgba(0, 0, 0, 0.25); border-radius: 10px;
        }
        .load-item .value { font-size: 20px; font-weight: 700; color: var(--gold); }
        .load-item .value.urgent { color: var(--danger); }
        .load-item .value.warning { color: var(--warning); }
        .load-item .value.good { color: var(--success); }

        .btn { 
            padding: 12px 24px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; 
            display: inline-flex; align-items: center; gap: 8px; transition: var(--transition);
            width: 100%;
            justify-content: center;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.4); }
        .btn:active { transform: translateY(0); }
        .btn-primary { background: var(--brown-medium); color: var(--gold); }
        .btn-warning { background: var(--warning); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-outline { background: transparent; border: 1px solid rgba(255,255,255,0.3); color: var(--text-light); }
        .btn-camera { background: var(--info); color: white; }

        .modal-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.85); display: none; justify-content: center; align-items: center; 
            z-index: 1000; padding: 20px;
        }
        .modal-overlay.show { display: flex; }
        .modal { 
            background: var(--bg-card); padding: 32px; border-radius: 16px; 
            width: 100%; max-width: 500px; border: 1px solid rgba(139, 115, 85, 0.2);
            animation: modalSlideIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        .modal h3 { color: var(--gold); margin-bottom: 8px; font-size: 24px; }
        .modal p { color: var(--brown-lighter); margin-bottom: 20px; }
        .modal input { 
            width: 100%; padding: 14px; margin: 8px 0 20px; 
            border-radius: 8px; border: 1px solid rgba(139, 115, 85, 0.3); 
            background: rgba(0,0,0,0.4); color: #fff; font-size: 16px;
        }
        .modal input:focus { outline: none; border-color: var(--gold); }
        .modal textarea { width: 100%; height: 80px; margin: 10px 0; padding: 10px; border-radius: 8px; background: rgba(0,0,0,0.4); color: #fff; border: 1px solid rgba(139, 115, 85, 0.3); font-family: inherit; resize: vertical; }
        .modal textarea:focus { outline: none; border-color: var(--gold); }
        .modal .btn-group { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        .modal .btn-group .btn { width: auto; }
        
        .concern-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
        }
        .concern-type-item {
            position: relative;
        }
        .concern-type-item input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .concern-type-item label {
            display: block;
            padding: 12px 14px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(139, 115, 85, 0.3);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            color: var(--brown-lighter);
        }
        .concern-type-item label:hover {
            border-color: var(--gold);
            background: rgba(255, 215, 0, 0.05);
        }
        .concern-type-item input[type="radio"]:checked + label {
            border-color: var(--gold);
            background: rgba(255, 215, 0, 0.1);
            color: var(--gold);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.1);
        }
        .concern-type-item .icon {
            font-size: 20px;
            display: block;
            margin-bottom: 4px;
        }
        .concern-type-item .sub-text {
            display: block;
            font-size: 10px;
            color: var(--brown-lighter);
            font-weight: 400;
            margin-top: 2px;
        }
        
        .image-modal-content {
            max-width: 600px;
            text-align: center;
        }
        .image-modal-content img {
            max-width: 100%;
            max-height: 60vh;
            border-radius: 12px;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }
        .image-modal-content .btn-group {
            justify-content: center;
            margin-top: 16px;
        }
        .image-modal-content .btn-group .btn {
            width: auto;
        }
        
        .camera-modal-content {
            max-width: 600px;
            text-align: center;
        }
        .camera-modal-content video {
            width: 100%;
            max-height: 50vh;
            border-radius: 12px;
            background: #000;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }
        .camera-modal-content .camera-preview {
            margin: 16px 0;
        }
        .camera-modal-content .btn-group {
            justify-content: center;
            margin-top: 16px;
        }
        .camera-modal-content .btn-group .btn {
            width: auto;
        }
        .camera-placeholder {
            width: 100%;
            height: 200px;
            background: rgba(0,0,0,0.5);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            border: 2px dashed rgba(255,215,0,0.3);
            margin: 16px 0;
        }

        .toast { 
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); 
            background: rgba(0,0,0,0.9); color: #fff; padding: 12px 24px; 
            border-radius: 30px; display: none; z-index: 2000; 
            border: 1px solid rgba(255,215,0,0.2);
            max-width: 90%;
        }
        .toast.show { display: block; animation: fadeInUp 0.3s ease; }
        @keyframes fadeInUp {
            from { transform: translateX(-50%) translateY(20px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }

        .unbind-link {
            text-align: center; margin-top: 16px; font-size: 12px;
        }
        .unbind-link a { 
            color: var(--brown-lighter); text-decoration: underline; 
            cursor: pointer; opacity: 0.6; transition: var(--transition);
        }
        .unbind-link a:hover { opacity: 1; }

        .badge-bound {
            display: inline-block; background: var(--success); color: white;
            padding: 2px 12px; border-radius: 12px; font-size: 11px;
            margin-left: 8px;
        }

        .schedule-info {
            margin-top: 12px;
            padding: 16px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border-left: 3px solid var(--gold);
        }
        .schedule-info .label { font-size: 11px; color: var(--brown-lighter); text-transform: uppercase; }
        .schedule-info .value { font-size: 18px; font-weight: 700; color: var(--gold); }
        .schedule-info .sub { font-size: 13px; color: var(--brown-lighter); margin-top: 4px; }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            cursor: pointer;
            transition: var(--transition);
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-avatar .no-photo-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            background: var(--warning);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            border: 2px solid var(--bg-dark);
        }

        .concern-full-width {
            margin-top: 20px;
            width: 100%;
        }

        .remark-field {
            margin: 10px 0;
        }
        .remark-field label {
            display: block;
            font-size: 12px;
            color: var(--brown-lighter);
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .optional-text {
            font-size: 11px;
            color: var(--brown-lighter);
            font-weight: 400;
        }

        @media (max-width: 640px) { 
            .info-grid { grid-template-columns: 1fr 1fr; } 
            .load-section { grid-template-columns: 1fr 1fr; }
            .modal { padding: 20px; }
            .schedule-info .value { font-size: 16px; }
            .schedule-info { grid-template-columns: 1fr; }
            .concern-types { grid-template-columns: 1fr 1fr; }
            .concern-type-item label { font-size: 12px; padding: 10px; }
            .concern-type-item .icon { font-size: 16px; }
        }
        .logo-icon {
  width: 48px;          /* or whatever size you need */
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 50%;   /* keep the circular look if you want */
}

.logo-icon img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
              <div class="logo-icon">
          <img src="/LM/Home/img/loadguard2.png" alt="LG Logo">
        </div>
                <div>
                    <div class="app-title">Load Guard</div>
                    <div style="font-size:12px; color:var(--brown-lighter)">DEVICE CHECKING SYSTEM</div>
                </div>
            </div>
            <div class="header-status">
                <?= $deviceFound ? 'Connected' : 'Searching...' ?>
                <?php if ($isBoundDevice): ?>
                <span class="badge-bound">🔗 Bound</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Serial Section -->
        <div class="serial-section">
            <?php if ($deviceFound && !empty($device['SERIAL'])): ?>
            <div class="qr-wrapper">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($device['SERIAL']) ?>" alt="QR">
            </div>
            <?php endif; ?>

            <div style="font-size: 13px; letter-spacing: 2px; color: var(--brown-lighter);">DEVICE SERIAL NUMBER</div>
            <div class="serial-number" id="serialDisplay">
                <?= htmlspecialchars($device['SERIAL'] ?? 'No device bound') ?>
            </div>
            
        </div>

        <!-- Device Card -->
        <?php if ($deviceFound): ?>
        <div class="device-card">
            <div style="display: flex; gap: 15px; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; flex-wrap: wrap;">
                <div class="user-avatar" onclick="openImageModal('<?= htmlspecialchars($device['PERSON_IMAGE'] ?? '') ?>', '<?= htmlspecialchars($device['PERSON_USING']) ?>')">
                    <?php if (!empty($device['PERSON_IMAGE'])): ?>
                        <img src="<?= $baseImageUrl . htmlspecialchars($device['PERSON_IMAGE']) ?>" alt="<?= htmlspecialchars($device['PERSON_USING']) ?>">
                    <?php else: ?>
                        👤
                        <span class="no-photo-badge">📸</span>
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 20px; font-weight: 700;"><?= htmlspecialchars($device['PERSON_USING']) ?></div>
                    <div style="color: var(--gold); font-family: monospace;"><?= htmlspecialchars($device['NUMBER']) ?></div>
                    <?php if (empty($device['PERSON_IMAGE'])): ?>
                    <div style="margin-top: 4px;">
                        <button class="btn btn-camera" style="padding: 4px 12px; font-size: 12px; width: auto;" onclick="openCameraModal()">📸 Add Photo</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item"><span class="label">Site</span><span class="value"><?= htmlspecialchars($device['SITE_ID']) ?></span></div>
                <div class="info-item"><span class="label">Dept</span><span class="value"><?= htmlspecialchars($device['DEPARTMENT']) ?></span></div>
                <div class="info-item"><span class="label">Brand</span><span class="value"><?= htmlspecialchars($device['BRAND']) ?></span></div>
                <div class="info-item"><span class="label">Model</span><span class="value"><?= htmlspecialchars($device['MODEL']) ?></span></div>
                <div class="info-item"><span class="label">Serial</span><span class="value" style="font-family: monospace; font-size: 14px;"><?= htmlspecialchars($device['SERIAL']) ?></span></div>
                <div class="info-item"><span class="label">Status</span><span class="value"><?= htmlspecialchars($device['DEVICE_STATUS']) ?></span></div>
            </div>

            <div class="load-section">
                <div class="load-item"><div class="label">Balance</div><div class="value"><?= number_format((float)$device['BALANCE'], 2) ?> GB</div></div>
                <div class="load-item"><div class="label">Consumed</div><div class="value"><?= number_format((float)$device['CONSUMED'], 2) ?> GB</div></div>
                <div class="load-item"><div class="label">Load Status</div><div class="value"><?= htmlspecialchars($device['LOAD_STATUS']) ?></div></div>
                <div class="load-item">
                    <div class="label">Days Left</div>
                    <div class="value <?= ($device['DAYS_LEFT'] !== null && $device['DAYS_LEFT'] < 0) ? 'urgent' : (($device['DAYS_LEFT'] !== null && $device['DAYS_LEFT'] <= 7) ? 'warning' : 'good') ?>">
                        <?= $device['DAYS_LEFT'] !== null ? $device['DAYS_LEFT'] . ' days' : 'N/A' ?>
                    </div>
                </div>
            </div>

            <!-- Next Load Schedule -->
            <?php if (!empty($device['LAST_LOAD_HISTORY']) && !empty($device['LOAD_TERMS'])): ?>
            <div class="schedule-info">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <div class="label">📅 Last Load Date</div>
                        <div class="value" style="font-size: 16px;"><?= date('M d, Y', strtotime($device['LAST_LOAD_HISTORY'])) ?></div>
                    </div>
                    <div>
                        <div class="label">📆 Next Load</div>
                        <div class="value" style="font-size: 16px;">
                            <?php if ($device['NEXT_LOAD_SCHEDULE']): ?>
                                <?= date('M d, Y', strtotime($device['NEXT_LOAD_SCHEDULE'])) ?>
                                <?php if ($device['DAYS_LEFT'] !== null && $device['DAYS_LEFT'] < 0): ?>
                                    <span style="color: var(--danger); font-size: 14px;"> (Overdue)</span>
                                <?php elseif ($device['DAYS_LEFT'] !== null && $device['DAYS_LEFT'] <= 7): ?>
                                    <span style="color: var(--warning); font-size: 14px;"> (Due Soon)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="sub">
                    Load Terms: <?= htmlspecialchars($device['LOAD_TERMS']) ?> month(s)
                    <?php if ($device['DAYS_LEFT'] !== null && $device['DAYS_LEFT'] >= 0): ?>
                        | <?= $device['DAYS_LEFT'] ?> days remaining
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Raise Concern - Full Width -->
            <div class="concern-full-width">
                <button class="btn btn-warning" onclick="openConcernModal()">⚠️ Raise Concern</button>
            </div>
        </div>
        <?php elseif ($isBoundDevice && !$deviceFound): ?>
        <div class="device-card" style="text-align: center; border-color: var(--danger);">
            <h3 style="color: var(--danger);">Device Not Found</h3>
            <p>LINEID <b><?= htmlspecialchars($_SESSION['bound_lineid'] ?? '') ?></b> is not valid.</p>
            <button class="btn btn-warning" onclick="unbindDevice()" style="margin-top: 12px; width: auto; display: inline-flex;">🔓 Unbind & Re-enter</button>
        </div>
        <?php endif; ?>

        <!-- Unbind Link -->
        <?php if ($isBoundDevice): ?>
        <div class="unbind-link">
            <a onclick="unbindDevice()">🔓 Click to unbind this device</a>
        </div>
        <?php else: ?>
        <div class="unbind-link">
            <a onclick="openBindModal()">🔑 Click to bind a device</a>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div style="text-align: center; font-size: 11px; color: var(--brown-lighter); margin-top: 30px; opacity: 0.6;">
            Server Time: <?= $currentTime ?> | OS: <?= $osName ?>
        </div>
    </div>

    <!-- Bind Device Modal -->
    <div class="modal-overlay <?= $showBindModal ? 'show' : '' ?>" id="bindModal">
        <div class="modal">
            <h3>🔑 Bind Device</h3>
            <p>Enter the device key id to bind this device to your session.</p>
            <form method="POST" onsubmit="return validateBindForm(event)">
                <input type="text" id="bindLineId" name="bind_lineid" placeholder="Enter Line id (Request from I.T on site)" required autofocus>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline" onclick="closeBindModal()">Cancel</button>
                    <button type="submit" class="btn btn-success" id="bindSubmitBtn">🔗 Bind Device</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal-overlay" id="imageModal">
        <div class="modal image-modal-content">
            <h3 id="imageModalTitle">User Photo</h3>
            <div style="margin: 20px 0;">
                <img id="imageModalPreview" src="" alt="User Photo">
            </div>
            <div class="btn-group" style="justify-content: center;">
                <button class="btn btn-outline close-btn" onclick="closeImageModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Camera Modal -->
    <div class="modal-overlay" id="cameraModal">
        <div class="modal camera-modal-content">
            <h3>📸 Capture Photo</h3>
            <p style="font-size: 14px;">Take a photo of the user</p>
            
            <div class="camera-preview">
                <video id="video" autoplay playsinline style="display: none;"></video>
                <div id="cameraPlaceholder" class="camera-placeholder">
                    📷
                </div>
                <canvas id="canvas" style="display: none;"></canvas>
                <img id="capturedImage" style="display: none; width: 100%; max-height: 50vh; border-radius: 12px; border: 2px solid rgba(255, 215, 0, 0.3);">
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-outline" onclick="closeCameraModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="startCameraBtn" onclick="startCamera()">📷 Start Camera</button>
                <button type="button" class="btn btn-success" id="captureBtn" onclick="capturePhoto()" style="display: none;">✅ Capture</button>
                <button type="button" class="btn btn-warning" id="retakeBtn" onclick="retakePhoto()" style="display: none;">🔄 Retake</button>
                <button type="button" class="btn btn-success" id="uploadBtn" onclick="uploadPhoto()" style="display: none;">📤 Upload</button>
            </div>
        </div>
    </div>

    <!-- Concern Modal -->
    <div class="modal-overlay" id="concernModal">
        <div class="modal">
            <h3>⚠️ Raise a Concern</h3>
            <p style="font-size: 14px;">Select the type of concern:</p>
            
            <form method="POST" onsubmit="return validateConcernForm()">
                <div class="concern-types">
                    <div class="concern-type-item">
                        <input type="radio" id="concern_physical" name="concern_type" value="PHYSICAL">
                        <label for="concern_physical">
                            <span class="icon">🔧</span>
                            Physical
                            <span class="sub-text">Hardware damages, structural faults</span>
                        </label>
                    </div>
                    <div class="concern-type-item">
                        <input type="radio" id="concern_uix" name="concern_type" value="UIX">
                        <label for="concern_uix">
                            <span class="icon">📱</span>
                            UIX
                            <span class="sub-text">Interface glitches, app lags</span>
                        </label>
                    </div>
                    <div class="concern-type-item">
                        <input type="radio" id="concern_load" name="concern_type" value="LOAD">
                        <label for="concern_load">
                            <span class="icon">💰</span>
                            Load
                            <span class="sub-text">No load balance</span>
                        </label>
                    </div>
                    <div class="concern-type-item">
                        <input type="radio" id="concern_personal" name="concern_type" value="PERSONAL_PHONE">
                        <label for="concern_personal">
                            <span class="icon">📞</span>
                            Personal Phone
                            <span class="sub-text">Using personal phone</span>
                        </label>
                    </div>
                </div>

                <textarea name="concern_text" placeholder="Describe your concern in detail (Optional)"></textarea>
                
                <div class="remark-field">
                    <label>📝 Remarks <span class="optional-text">(Optional)</span></label>
                    <textarea name="remarks" placeholder="Additional remarks or notes..." style="height: 60px;"></textarea>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-outline" onclick="closeConcernModal()">Cancel</button>
                    <button type="submit" name="raise_concern" class="btn btn-warning">⚠️ Submit Concern</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // ============================================
        // BIND / UNBIND FUNCTIONS
        // ============================================
        function openBindModal() {
            document.getElementById('bindModal').classList.add('show');
            setTimeout(() => {
                document.getElementById('bindLineId').focus();
            }, 300);
        }

        function closeBindModal() {
            document.getElementById('bindModal').classList.remove('show');
        }

        function validateBindForm(event) {
            if (event) event.preventDefault();
            
            const input = document.getElementById('bindLineId');
            const submitBtn = document.getElementById('bindSubmitBtn');
            const lineId = input.value.trim();
            
            if (!lineId) {
                showToast('Please enter a valid LINEID');
                input.focus();
                return false;
            }
            
            submitBtn.innerHTML = '⏳ Saving...';
            submitBtn.disabled = true;
            
            const form = input.closest('form');
            
            try {
                localStorage.setItem('loadguard_lineid', lineId);
            } catch (e) {}
            
            form.submit();
            return true;
        }

        function unbindDevice() {
            if (confirm('Are you sure you want to unbind this device?')) {
                try {
                    localStorage.removeItem('loadguard_lineid');
                } catch (e) {}
                
                window.location.href = window.location.pathname + '?unbind=true';
            }
        }

        // ============================================
        // IMAGE MODAL FUNCTIONS
        // ============================================
        let stream = null;
        let capturedImageData = null;

        function openImageModal(imageUrl, personName) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('imageModalPreview');
            const title = document.getElementById('imageModalTitle');
            
            if (imageUrl && imageUrl.trim() !== '') {
                const basePath = '/lm/home/pages/';
                let fullUrl = imageUrl;
                
                if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                    fullUrl = basePath + imageUrl;
                    fullUrl = fullUrl.replace(/\\/g, '/');
                }
                
                img.src = fullUrl;
                img.alt = personName || 'User Photo';
                title.textContent = '📸 ' + (personName || 'User Photo');
                modal.classList.add('show');
            } else {
                showToast('❌ No photo available for this user. Click "Add Photo" to capture one.');
            }
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('show');
        }

        // ============================================
        // CAMERA FUNCTIONS
        // ============================================
        function openCameraModal() {
            document.getElementById('cameraModal').classList.add('show');
            document.getElementById('video').style.display = 'none';
            document.getElementById('canvas').style.display = 'none';
            document.getElementById('capturedImage').style.display = 'none';
            document.getElementById('cameraPlaceholder').style.display = 'flex';
            document.getElementById('startCameraBtn').style.display = 'inline-flex';
            document.getElementById('captureBtn').style.display = 'none';
            document.getElementById('retakeBtn').style.display = 'none';
            document.getElementById('uploadBtn').style.display = 'none';
            capturedImageData = null;
        }

        function closeCameraModal() {
            document.getElementById('cameraModal').classList.remove('show');
            stopCamera();
        }

        async function startCamera() {
            try {
                const video = document.getElementById('video');
                const placeholder = document.getElementById('cameraPlaceholder');
                
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' },
                    audio: false 
                });
                
                video.srcObject = stream;
                video.style.display = 'block';
                placeholder.style.display = 'none';
                document.getElementById('startCameraBtn').style.display = 'none';
                document.getElementById('captureBtn').style.display = 'inline-flex';
                
                await video.play();
                showToast('📷 Camera started');
            } catch (error) {
                console.error('Camera error:', error);
                showToast('❌ Could not access camera: ' + error.message);
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            const video = document.getElementById('video');
            video.srcObject = null;
            video.style.display = 'none';
        }

        function capturePhoto() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const capturedImg = document.getElementById('capturedImage');
            
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            capturedImageData = canvas.toDataURL('image/jpeg', 0.8);
            
            capturedImg.src = capturedImageData;
            capturedImg.style.display = 'block';
            video.style.display = 'none';
            
            document.getElementById('captureBtn').style.display = 'none';
            document.getElementById('retakeBtn').style.display = 'inline-flex';
            document.getElementById('uploadBtn').style.display = 'inline-flex';
            
            showToast('📸 Photo captured!');
        }

        function retakePhoto() {
            const video = document.getElementById('video');
            const capturedImg = document.getElementById('capturedImage');
            
            capturedImg.style.display = 'none';
            video.style.display = 'block';
            
            document.getElementById('captureBtn').style.display = 'inline-flex';
            document.getElementById('retakeBtn').style.display = 'none';
            document.getElementById('uploadBtn').style.display = 'none';
            
            capturedImageData = null;
            showToast('🔄 Retake photo');
        }

        function uploadPhoto() {
            if (!capturedImageData) {
                showToast('❌ No photo to upload');
                return;
            }
            
            showToast('📤 Uploading photo...');
            
            // Disable upload button to prevent multiple submits
            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '⏳ Uploading...';
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="upload_image" value="1">
                <input type="hidden" name="image_data" value="${capturedImageData}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // ============================================
        // CONCERN MODAL
        // ============================================
        function openConcernModal() { 
            document.getElementById('concernModal').classList.add('show'); 
        }
        
        function closeConcernModal() { 
            document.getElementById('concernModal').classList.remove('show'); 
        }

        function validateConcernForm() {
            const selected = document.querySelector('input[name="concern_type"]:checked');
            if (!selected) {
                showToast('⚠️ Please select a concern type');
                return false;
            }
            return true;
        }

        // ============================================
        // TOAST NOTIFICATION
        // ============================================
        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg; 
            t.classList.add('show');
            clearTimeout(t._timeout);
            t._timeout = setTimeout(() => t.classList.remove('show'), 3500);
        }

        // ============================================
        // INITIALIZATION
        // ============================================
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($concernMessage): ?>
            setTimeout(function() {
                showToast("<?= addslashes($concernMessage) ?>");
            }, 500);
            <?php endif; ?>

            if (document.getElementById('bindModal').classList.contains('show')) {
                setTimeout(() => {
                    document.getElementById('bindLineId').focus();
                }, 300);
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeBindModal();
                    closeConcernModal();
                    closeImageModal();
                    closeCameraModal();
                }
            });

            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        overlay.classList.remove('show');
                        if (overlay.id === 'cameraModal') {
                            stopCamera();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>