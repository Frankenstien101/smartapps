<?php
// ==============================================
// FILE MANAGER - CHECKBOX SELECTION FOR ALL ITEMS
// Save this file as "bspifiles.php"
// ==============================================

error_reporting(0);
ini_set('display_errors', 0);

// Increase limits for large uploads
ini_set('upload_max_filesize', '2048M');
ini_set('post_max_size', '2048M');
ini_set('max_execution_time', '7200');
ini_set('memory_limit', '2048M');
ini_set('max_file_uploads', '500');

session_start();

define('ROOT_DIR', __DIR__ . '/Files');

if (!file_exists(ROOT_DIR)) {
    mkdir(ROOT_DIR, 0777, true);
}

define('PASSWORD_FILE', __DIR__ . '/.folder_passwords.json');

if (!file_exists(PASSWORD_FILE)) {
    file_put_contents(PASSWORD_FILE, json_encode(array()));
}

function getPasswordData() {
    $content = file_get_contents(PASSWORD_FILE);
    $data = json_decode($content, true);
    return is_array($data) ? $data : array();
}

function savePasswordData($data) {
    file_put_contents(PASSWORD_FILE, json_encode($data));
}

function setFolderPassword($folderPath, $password) {
    $data = getPasswordData();
    $relativePath = relativePath($folderPath);
    if (empty($password)) {
        unset($data[$relativePath]);
    } else {
        $data[$relativePath] = password_hash($password, PASSWORD_DEFAULT);
    }
    savePasswordData($data);
    return true;
}

function checkFolderPassword($folderPath, $password) {
    $data = getPasswordData();
    $relativePath = relativePath($folderPath);
    if (!isset($data[$relativePath])) return true;
    return password_verify($password, $data[$relativePath]);
}

function isFolderProtected($folderPath) {
    $data = getPasswordData();
    $relativePath = relativePath($folderPath);
    return isset($data[$relativePath]);
}

function getTotalStorage() {
    $total = disk_total_space(ROOT_DIR);
    $free = disk_free_space(ROOT_DIR);
    $used = $total - $free;
    return array('total' => $total, 'used' => $used, 'free' => $free);
}

function formatSizeLarge($bytes) {
    if ($bytes == 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = (int) floor(log($bytes, 1024));  // Cast to integer
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function safePath($path) {
    if ($path == '/' || $path == '' || $path == '.') {
        return ROOT_DIR;
    }
    $path = ltrim($path, '/');
    $path = rtrim($path, '/');
    $fullPath = ROOT_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    return $fullPath;
}

function relativePath($fullPath) {
    $relative = str_replace(ROOT_DIR, '', $fullPath);
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    return empty($relative) ? '/' : $relative;
}

function formatSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB');
    $i = (int) floor(log($bytes, 1024));  // Cast to integer
    return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
}

// Recursively zip a folder
function zipFolder($source, $destination) {
    if (!extension_loaded('zip')) {
        return false;
    }
    
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        return false;
    }
    
    $source = realpath($source);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($source) + 1);
        $zip->addFile($filePath, $relativePath);
    }
    
    $zip->close();
    return true;
}

// Handle folder download as ZIP (with password check)
if (isset($_GET['download_folder'])) {
    $folderPath = safePath($_GET['download_folder']);
    $password = isset($_GET['password']) ? $_GET['password'] : null;
    
    if ($folderPath && is_dir($folderPath)) {
        if (isFolderProtected($folderPath)) {
            if (!$password || !checkFolderPassword($folderPath, $password)) {
                echo '<!DOCTYPE html>
                <html>
                <head><title>Password Required</title>
                <style>
                    body { font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
                    .container { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); text-align: center; }
                    input { padding: 10px; margin: 10px 0; width: 100%; border: 1px solid #ddd; border-radius: 8px; }
                    button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
                </style>
                </head>
                <body>
                <div class="container">
                    <h2><i class="fas fa-lock"></i> Password Required</h2>
                    <p>This folder is password protected. Enter password to download:</p>
                    <form method="get" action="">
                        <input type="hidden" name="download_folder" value="' . htmlspecialchars($_GET['download_folder']) . '">
                        <input type="password" name="password" placeholder="Enter password" autofocus>
                        <button type="submit">Download ZIP</button>
                    </form>
                </div>
                </body>
                </html>';
                exit;
            }
        }
        
        $tempZip = sys_get_temp_dir() . '/' . basename($folderPath) . '_' . time() . '.zip';
        if (zipFolder($folderPath, $tempZip)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($folderPath) . '.zip"');
            header('Content-Length: ' . filesize($tempZip));
            readfile($tempZip);
            unlink($tempZip);
            exit;
        }
    }
}

// Handle file download (with password check for parent folder)
if (isset($_GET['download'])) {
    $filePath = safePath($_GET['download']);
    $password = isset($_GET['password']) ? $_GET['password'] : null;
    
    if ($filePath && is_file($filePath)) {
        $parentFolder = dirname($filePath);
        if (isFolderProtected($parentFolder)) {
            if (!$password || !checkFolderPassword($parentFolder, $password)) {
                echo '<!DOCTYPE html>
                <html>
                <head><title>Password Required</title>
                <style>
                    body { font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
                    .container { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); text-align: center; }
                    input { padding: 10px; margin: 10px 0; width: 100%; border: 1px solid #ddd; border-radius: 8px; }
                    button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
                </style>
                </head>
                <body>
                <div class="container">
                    <h2><i class="fas fa-lock"></i> Password Required</h2>
                    <p>This file is in a password protected folder. Enter password to download:</p>
                    <form method="get" action="">
                        <input type="hidden" name="download" value="' . htmlspecialchars($_GET['download']) . '">
                        <input type="password" name="password" placeholder="Enter password" autofocus>
                        <button type="submit">Download</button>
                    </form>
                </div>
                </body>
                </html>';
                exit;
            }
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'list') {
        $path = isset($input['path']) ? $input['path'] : '/';
        $password = isset($input['password']) ? $input['password'] : null;
        
        $fullPath = safePath($path);
        
        if (!file_exists($fullPath) || !is_dir($fullPath)) {
            echo json_encode(array('success' => false, 'error' => 'Directory not found'));
            exit;
        }
        
        if (isFolderProtected($fullPath)) {
            if (!$password || !checkFolderPassword($fullPath, $password)) {
                echo json_encode(array('success' => false, 'error' => 'password_required', 'protected' => true));
                exit;
            }
        }
        
        $items = scandir($fullPath);
        $result = array();
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            $stat = stat($itemPath);
            $result[] = array(
                'name' => $item,
                'fullPath' => relativePath($itemPath),
                'isDirectory' => is_dir($itemPath),
                'size' => isset($stat['size']) ? $stat['size'] : 0,
                'modified' => isset($stat['mtime']) ? $stat['mtime'] : time(),
                'isProtected' => is_dir($itemPath) ? isFolderProtected($itemPath) : false
            );
        }
        
        usort($result, function($a, $b) {
            if ($a['isDirectory'] == $b['isDirectory']) {
                return strcmp($a['name'], $b['name']);
            }
            return $a['isDirectory'] ? -1 : 1;
        });
        
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'items' => $result,
                'currentPath' => relativePath($fullPath) ?: '/'
            )
        ));
        exit;
    }
    
    if ($action == 'mkdir') {
        $parentPath = isset($input['parentPath']) ? $input['parentPath'] : '/';
        $name = isset($input['name']) ? $input['name'] : '';
        
        if (empty($name)) {
            echo json_encode(array('success' => false, 'error' => 'Invalid folder name'));
            exit;
        }
        
        if ($parentPath == '/') {
            $fullParent = ROOT_DIR;
        } else {
            $fullParent = ROOT_DIR . DIRECTORY_SEPARATOR . ltrim($parentPath, '/');
        }
        
        $newDir = $fullParent . DIRECTORY_SEPARATOR . $name;
        
        if (!file_exists($newDir)) {
            if (mkdir($newDir, 0777, true)) {
                echo json_encode(array('success' => true));
            } else {
                echo json_encode(array('success' => false, 'error' => 'Cannot create folder'));
            }
        } else {
            echo json_encode(array('success' => true));
        }
        exit;
    }
    
    if ($action == 'setPassword') {
        $itemPaths = isset($input['paths']) ? $input['paths'] : array();
        $password = isset($input['password']) ? $input['password'] : '';
        
        $successCount = 0;
        foreach ($itemPaths as $itemPath) {
            $fullPath = safePath($itemPath);
            if (file_exists($fullPath) && is_dir($fullPath)) {
                setFolderPassword($fullPath, $password);
                $successCount++;
            }
        }
        echo json_encode(array('success' => true, 'message' => 'Password updated for ' . $successCount . ' folder(s)'));
        exit;
    }
    
    if ($action == 'write') {
        $filePath = isset($input['path']) ? $input['path'] : '';
        $content = isset($input['content']) ? $input['content'] : '';
        $fullPath = safePath($filePath);
        
        $parentDir = dirname($fullPath);
        if (!file_exists($parentDir)) {
            mkdir($parentDir, 0777, true);
        }
        
        if (file_put_contents($fullPath, $content) !== false) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'error' => 'Cannot write file'));
        }
        exit;
    }
    
    if ($action == 'read') {
        $filePath = isset($input['path']) ? $input['path'] : '';
        $fullPath = safePath($filePath);
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            echo json_encode(array('success' => false, 'error' => 'File not found'));
            exit;
        }
        
        $size = filesize($fullPath);
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $editableExts = array('txt', 'md', 'js', 'json', 'html', 'css', 'xml', 'php', 'py', 'sh', 'ini', 'conf', 'csv', 'log');
        $isEditable = in_array($ext, $editableExts);
        $content = '';
        
        if ($size < 1048576 && $isEditable) {
            $content = file_get_contents($fullPath);
        }
        
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'content' => $content,
                'size' => $size,
                'isEditable' => $isEditable
            )
        ));
        exit;
    }
    
    echo json_encode(array('success' => false, 'error' => 'Unknown action'));
    exit;
}

// Handle file upload - PRESERVES FOLDER STRUCTURE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    header('Content-Type: application/json');
    
    $targetDir = isset($_POST['targetDir']) ? $_POST['targetDir'] : '/';
    $relativePath = isset($_POST['relativePath']) ? $_POST['relativePath'] : '';
    
    if ($targetDir == '/') {
        $fullDir = ROOT_DIR;
    } else {
        $fullDir = ROOT_DIR . DIRECTORY_SEPARATOR . ltrim($targetDir, '/');
    }
    
    if (!file_exists($fullDir) || !is_dir($fullDir)) {
        echo json_encode(array('success' => false, 'error' => 'Target directory not found'));
        exit;
    }
    
    $fileName = basename($_FILES['file']['name']);
    $tmpName = $_FILES['file']['tmp_name'];
    
    if (!empty($relativePath)) {
        $destFolder = $fullDir . DIRECTORY_SEPARATOR . $relativePath;
        if (!file_exists($destFolder)) {
            mkdir($destFolder, 0777, true);
        }
        $destPath = $destFolder . DIRECTORY_SEPARATOR . $fileName;
    } else {
        $destPath = $fullDir . DIRECTORY_SEPARATOR . $fileName;
    }
    
    if (move_uploaded_file($tmpName, $destPath)) {
        echo json_encode(array('success' => true, 'path' => $destPath));
    } else {
        echo json_encode(array('success' => false, 'error' => 'Failed to save file: ' . $fileName));
    }
    exit;
}

$storage = getTotalStorage();
$usedPercent = ($storage['total'] > 0) ? ($storage['used'] / $storage['total']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Files</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            height: 100vh;
            overflow: hidden;
        }
        .app-container {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .file-manager {
            width: 100%;
            height: 100%;
            max-width: 1600px;
            background: #ffffff;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .header {
            background: white;
            padding: 20px 32px;
            border-bottom: 1px solid #e9ecef;
        }
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }
        .storage-info {
            background: #f0f9ff;
            padding: 16px;
            border-radius: 16px;
            margin-top: 8px;
        }
        .storage-stats {
            display: flex;
            gap: 24px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .storage-stat {
            flex: 1;
            text-align: center;
        }
        .storage-stat .label {
            font-size: 0.75rem;
            color: #1e40af;
            margin-bottom: 4px;
        }
        .storage-stat .value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e3a8a;
        }
        .progress-bar-container {
            background: #bfdbfe;
            border-radius: 20px;
            overflow: hidden;
            height: 8px;
        }
        .progress-bar-fill {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 20px;
        }
        .toolbar {
            padding: 16px 32px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn {
            padding: 8px 20px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-secondary { background: white; color: #1e40af; border: 1px solid #bfdbfe; }
        .btn-secondary:hover { background: #eff6ff; }
        .btn-blue { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .btn-blue:hover { background: #bfdbfe; }
        .btn-green { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .btn-green:hover { background: #bbf7d0; }
        .btn-purple { background: #f3e8ff; color: #9333ea; border: 1px solid #e9d5ff; }
        .btn-purple:hover { background: #e9d5ff; }
        .btn-orange { background: #ffedd5; color: #ea580c; border: 1px solid #fed7aa; }
        .btn-orange:hover { background: #fed7aa; }
        .path-bar {
            flex: 1;
            background: white;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.875rem;
            color: #1e3a8a;
            font-family: monospace;
            border: 1px solid #bfdbfe;
            overflow-x: auto;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .back-button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .back-button:hover { background: #2563eb; }
        .drag-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(59, 130, 246, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .drag-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        .drag-overlay-content {
            text-align: center;
            color: white;
        }
        .drag-overlay-content i {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .main-content { display: flex; flex: 1; overflow: hidden; }
        .sidebar {
            width: 260px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            padding: 20px 0;
        }
        .sidebar-item {
            padding: 12px 24px;
            margin: 4px 12px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e40af;
        }
        .sidebar-item:hover { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .file-area { flex: 1; overflow-y: auto; padding: 24px; background: white; }
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }
        .file-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .file-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); border-color: #3b82f6; }
        .file-card.selected { background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%); border-color: #3b82f6; border-width: 2px; }
        .file-card.protected { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-color: #6366f1; }
        .checkbox-container {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 10;
        }
        .item-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #3b82f6;
        }
        .protected-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #6366f1;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .file-icon { font-size: 3rem; margin-bottom: 12px; margin-top: 20px; }
        .file-name { font-size: 0.875rem; font-weight: 500; color: #1e3a8a; word-break: break-word; margin-bottom: 8px; }
        .file-meta { font-size: 0.7rem; color: #64748b; }
        .upload-progress {
            position: fixed;
            bottom: 24px;
            left: 24px;
            right: 24px;
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }
        .upload-progress.hidden { display: none; }
        .progress-text {
            font-size: 0.875rem;
            color: #1e3a8a;
            margin-bottom: 8px;
        }
        .progress-bar {
            background: #bfdbfe;
            border-radius: 20px;
            overflow: hidden;
            height: 8px;
        }
        .progress-fill {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            width: 0%;
            height: 100%;
            transition: width 0.3s ease;
        }
        .toast {
            position: fixed; bottom: 24px; right: 24px;
            background: #1e293b; color: white;
            padding: 12px 24px; border-radius: 12px;
            z-index: 1100;
        }
        .toast.error { background: #ef4444; }
        .toast.success { background: #10b981; }
        .toast.info { background: #3b82f6; }
        .hidden { display: none; }
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state i { font-size: 4rem; margin-bottom: 16px; }
        
        @keyframes slideUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .feature-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 12px;
        }
        
        .select-all-container {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid #bfdbfe;
        }
        .select-all-container label {
            font-size: 0.75rem;
            color: #1e3a8a;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="app-container">
    <div class="file-manager">
        <div class="header">
            <h1><i class="fas fa-server"></i> My Files</h1>
            <div class="storage-info">
                <div class="storage-stats">
                    <div class="storage-stat"><div class="label">Total Capacity</div><div class="value"><?php echo formatSizeLarge($storage['total']); ?></div></div>
                    <div class="storage-stat"><div class="label">Used Space</div><div class="value"><?php echo formatSizeLarge($storage['used']); ?></div></div>
                    <div class="storage-stat"><div class="label">Free Space</div><div class="value"><?php echo formatSizeLarge($storage['free']); ?></div></div>
                    <div class="storage-stat"><div class="label">Usage</div><div class="value"><?php echo round($usedPercent, 1); ?>%</div></div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: <?php echo $usedPercent; ?>%"></div></div>
            </div>
        </div>
        
        <div class="toolbar">
            <button class="btn btn-primary" id="newFolderBtn"><i class="fas fa-folder-plus"></i> New Folder</button>
            <button class="btn btn-primary" id="newFileBtn"><i class="fas fa-file-plus"></i> New File</button>
            <button class="btn btn-green" id="uploadBtn"><i class="fas fa-folder-open"></i> Upload Folder</button>
            <button class="btn btn-purple" id="downloadSelectedBtn"><i class="fas fa-download"></i> Download Selected</button>
            <button class="btn btn-blue" id="setPasswordBtn"><i class="fas fa-lock"></i> Set Password</button>
            <div class="select-all-container">
                <input type="checkbox" id="selectAllCheckbox">
                <label for="selectAllCheckbox">Select All</label>
            </div>
            <div class="path-bar" id="currentPathDisplay">
                <i class="fas fa-folder"></i> <span id="currentPathText">/</span>
                <button class="back-button" id="backBtn"><i class="fas fa-arrow-left"></i> Back</button>
            </div>
        </div>
        
        <div class="main-content">
            <div class="sidebar">
                <div class="sidebar-item" data-path="/"><i class="fas fa-home"></i> Root Directory</div>
                <div class="sidebar-item" id="parentDirBtn"><i class="fas fa-level-up-alt"></i> Parent Directory</div>
            </div>
            <div class="file-area" id="fileArea">
                <div id="fileContainer"></div>
            </div>
        </div>
    </div>
</div>

<div id="dragOverlay" class="drag-overlay">
    <div class="drag-overlay-content">
        <i class="fas fa-folder-tree"></i>
        <h2>Drop Folder to Upload</h2>
        <p>✓ Preserves complete folder structure</p>
        <p>✓ All subfolders and files maintain their hierarchy</p>
        <p>✓ Uploads files one by one with progress</p>
    </div>
</div>

<div id="uploadProgress" class="upload-progress hidden">
    <div class="progress-text"><i class="fas fa-cloud-upload-alt"></i> <span id="uploadFileName"></span></div>
    <div class="progress-bar"><div class="progress-fill" id="uploadProgressFill"></div></div>
</div>

<div id="toast" class="toast hidden"></div>

<script>
    let currentPath = "/";
    let selectedItems = new Set();
    let currentItems = [];

    function showToast(msg, type = '') {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.className = 'toast ' + type;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 5000);
    }

    function apiCall(action, data, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '?action=' + action, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        callback(null, result.data || result);
                    } else {
                        callback(result.error || 'Unknown error', null, result);
                    }
                } catch(e) {
                    callback('Invalid response', null);
                }
            } else {
                callback('Request failed (Status: ' + xhr.status + ')', null);
            }
        };
        xhr.onerror = function() {
            callback('Network error', null);
        };
        xhr.send(JSON.stringify(data));
    }

    function loadDirectory(path, password = null) {
        const data = { path: path };
        if (password) data.password = password;
        
        apiCall('list', data, function(err, data, rawResult) {
            if (err) {
                if (rawResult && rawResult.protected) {
                    showPasswordPrompt(path);
                } else {
                    showToast('Error: ' + err, 'error');
                }
                return;
            }
            currentPath = data.currentPath;
            currentItems = data.items;
            selectedItems.clear();
            document.getElementById('selectAllCheckbox').checked = false;
            renderFiles();
            document.getElementById('currentPathText').innerText = data.currentPath || '/';
        });
    }

    function showPasswordPrompt(folderPath, callback) {
        const existingModal = document.querySelector('.password-modal');
        if (existingModal) existingModal.remove();
        
        const modal = document.createElement('div');
        modal.className = 'password-modal';
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:2000;';
        modal.innerHTML = `
            <div style="background:white;border-radius:24px;width:90%;max-width:400px;padding:24px;">
                <h3 style="margin-bottom:16px;color:#1e3a8a;"><i class="fas fa-lock"></i> Password Required</h3>
                <p style="margin-bottom:16px;color:#64748b;">This folder is password protected.</p>
                <input type="password" id="folderPassword" style="width:100%;padding:12px;border:2px solid #bfdbfe;border-radius:12px;margin-bottom:16px;" placeholder="Enter password" autofocus>
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <button class="btn btn-secondary" id="cancelPasswordBtn">Cancel</button>
                    <button class="btn btn-primary" id="submitPasswordBtn">Unlock</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        const closeModal = () => modal.remove();
        
        document.getElementById('cancelPasswordBtn').onclick = closeModal;
        document.getElementById('submitPasswordBtn').onclick = function() {
            const password = document.getElementById('folderPassword').value;
            closeModal();
            if (callback) {
                callback(password);
            } else {
                loadDirectory(folderPath, password);
            }
        };
        
        document.getElementById('folderPassword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('submitPasswordBtn').click();
            }
        });
        
        modal.onclick = function(e) {
            if (e.target === modal) closeModal();
        };
    }

    function goBack() {
        if (currentPath !== '/') {
            const parent = currentPath.split('/').slice(0, -1).join('/') || '/';
            loadDirectory(parent);
        } else {
            showToast('Already at root directory', 'error');
        }
    }

    function toggleItemSelection(fullPath, isChecked) {
        if (isChecked) {
            selectedItems.add(fullPath);
        } else {
            selectedItems.delete(fullPath);
        }
        updateSelectAllCheckbox();
        renderFiles();
    }
    
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAllCheckbox').checked;
        if (selectAll) {
            currentItems.forEach(item => {
                selectedItems.add(item.fullPath);
            });
        } else {
            selectedItems.clear();
        }
        renderFiles();
    }
    
    function updateSelectAllCheckbox() {
        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectedItems.size === currentItems.length && currentItems.length > 0) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else if (selectedItems.size > 0 && selectedItems.size < currentItems.length) {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
    }

    function downloadSelected() {
        if (selectedItems.size === 0) {
            showToast('Please select at least one item to download', 'error');
            return;
        }
        
        // Get first selected item
        const firstSelected = Array.from(selectedItems)[0];
        const item = currentItems.find(i => i.fullPath === firstSelected);
        
        if (item) {
            if (item.isDirectory) {
                if (item.isProtected) {
                    showPasswordPrompt(item.fullPath, function(password) {
                        window.location.href = '?download_folder=' + encodeURIComponent(item.fullPath) + '&password=' + encodeURIComponent(password);
                    });
                } else {
                    window.location.href = '?download_folder=' + encodeURIComponent(item.fullPath);
                }
            } else {
                // For files, check if parent folder is protected
                const parentFolder = item.fullPath.substring(0, item.fullPath.lastIndexOf('/'));
                const parentItem = currentItems.find(i => i.fullPath === parentFolder);
                const isParentProtected = parentItem ? parentItem.isProtected : false;
                
                if (isParentProtected) {
                    showPasswordPrompt(parentFolder, function(password) {
                        window.location.href = '?download=' + encodeURIComponent(item.fullPath) + '&password=' + encodeURIComponent(password);
                    });
                } else {
                    window.location.href = '?download=' + encodeURIComponent(item.fullPath);
                }
            }
            showToast('Download started...', 'success');
        } else {
            showToast('Selected item not found', 'error');
        }
    }

    function setPasswordOnSelected() {
        if (selectedItems.size === 0) {
            showToast('Please select at least one folder', 'error');
            return;
        }
        
        // Filter only folders
        const selectedFolders = Array.from(selectedItems).filter(path => {
            const item = currentItems.find(i => i.fullPath === path);
            return item && item.isDirectory;
        });
        
        if (selectedFolders.length === 0) {
            showToast('Please select at least one folder (not files)', 'error');
            return;
        }
        
        const modal = document.createElement('div');
        modal.className = 'password-modal';
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:2000;';
        modal.innerHTML = `
            <div style="background:white;border-radius:24px;width:90%;max-width:400px;padding:24px;">
                <h3 style="margin-bottom:16px;color:#1e3a8a;"><i class="fas fa-lock"></i> Set Password</h3>
                <p style="margin-bottom:16px;">Apply password to ${selectedFolders.length} selected folder(s)</p>
                <input type="password" id="newPassword" style="width:100%;padding:12px;border:2px solid #bfdbfe;border-radius:12px;margin-bottom:12px;" placeholder="New password">
                <input type="password" id="confirmPassword" style="width:100%;padding:12px;border:2px solid #bfdbfe;border-radius:12px;margin-bottom:16px;" placeholder="Confirm password">
                <p style="font-size:0.75rem;color:#64748b;margin-bottom:16px;">Leave empty to remove password protection</p>
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <button class="btn btn-secondary" id="cancelSetPasswordBtn">Cancel</button>
                    <button class="btn btn-primary" id="savePasswordBtn">Apply</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        const closeModal = () => modal.remove();
        
        document.getElementById('cancelSetPasswordBtn').onclick = closeModal;
        document.getElementById('savePasswordBtn').onclick = function() {
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (password !== confirm) {
                showToast('Passwords do not match', 'error');
                return;
            }
            
            apiCall('setPassword', { paths: selectedFolders, password: password }, function(err, data) {
                if (err) {
                    showToast(err, 'error');
                } else {
                    showToast(data.message || 'Password updated', 'success');
                    loadDirectory(currentPath);
                }
                closeModal();
            });
        };
        
        modal.onclick = function(e) {
            if (e.target === modal) closeModal();
        };
    }

    function editFile(filePath, fileName) {
        apiCall('read', { path: filePath }, function(err, data) {
            if (err) {
                showToast('Cannot open: ' + err, 'error');
                return;
            }
            if (!data.isEditable) {
                showToast('This file type cannot be edited', 'error');
                return;
            }
            
            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:1000;';
            modal.innerHTML = `
                <div style="background:white;border-radius:24px;width:90%;max-width:700px;max-height:80vh;display:flex;flex-direction:column;">
                    <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
                        <h3 style="color:#1e3a8a;"><i class="fas fa-edit"></i> Editing: ${escapeHtml(fileName)}</h3>
                    </div>
                    <div style="padding:24px;overflow-y:auto;flex:1;">
                        <textarea id="editContent" rows="20" style="width:100%;padding:12px;border:2px solid #bfdbfe;border-radius:12px;font-family:monospace;">${escapeHtml(data.content)}</textarea>
                    </div>
                    <div style="padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;gap:12px;justify-content:flex-end;">
                        <button class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                        <button class="btn btn-primary" id="saveEditBtn">Save Changes</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const closeModal = () => modal.remove();
            document.getElementById('cancelEditBtn').onclick = closeModal;
            
            document.getElementById('saveEditBtn').onclick = function() {
                const newContent = document.getElementById('editContent').value;
                apiCall('write', { path: filePath, content: newContent }, function(err, data) {
                    if (err) {
                        showToast('Save failed: ' + err, 'error');
                    } else {
                        showToast('File saved successfully!', 'success');
                        closeModal();
                        loadDirectory(currentPath);
                    }
                });
            };
            
            modal.onclick = function(e) {
                if (e.target === modal) closeModal();
            };
        });
    }

    function renderFiles() {
        const container = document.getElementById('fileContainer');
        if (!currentItems.length) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-folder-open"></i><p>Empty folder</p><small>📁 Click "Upload Folder" and select a folder<br>📂 All subfolders and files will maintain their structure</small></div>';
            return;
        }
        
        container.innerHTML = '<div class="grid-view" id="fileGrid"></div>';
        const grid = document.getElementById('fileGrid');
        
        currentItems.forEach(item => {
            const card = document.createElement('div');
            const isSelected = selectedItems.has(item.fullPath);
            card.className = `file-card ${isSelected ? 'selected' : ''} ${item.isProtected ? 'protected' : ''}`;
            const icon = item.isDirectory ? '<i class="fas fa-folder"></i>' : getFileIcon(item.name);
            const date = new Date(item.modified * 1000).toLocaleDateString();
            card.innerHTML = `
                <div class="checkbox-container">
                    <input type="checkbox" class="item-checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleItemSelection('${item.fullPath}', this.checked);">
                </div>
                ${item.isProtected ? '<div class="protected-badge"><i class="fas fa-lock"></i> Protected</div>' : ''}
                <div class="file-icon">${icon}</div>
                <div class="file-name">${escapeHtml(item.name)}${item.isProtected ? ' 🔒' : ''}</div>
                <div class="file-meta">${item.isDirectory ? 'Folder' : formatSize(item.size)}<br>${date}</div>
            `;
            card.onclick = (function(i) {
                return function(e) {
                    if (e.target.type !== 'checkbox') {
                        if (i.isDirectory) {
                            loadDirectory(i.fullPath);
                        } else {
                            editFile(i.fullPath, i.name);
                        }
                    }
                };
            })(item);
            grid.appendChild(card);
        });
    }

    function createFolder() {
        const name = prompt('Enter folder name:', 'NewFolder');
        if (!name || name.trim() === '') {
            showToast('Folder name cannot be empty', 'error');
            return;
        }
        
        apiCall('mkdir', { parentPath: currentPath, name: name }, function(err, data) {
            if (err) {
                showToast(err, 'error');
            } else {
                showToast('Folder created successfully', 'success');
                loadDirectory(currentPath);
            }
        });
    }

    function createFile() {
        const name = prompt('Enter file name:', 'newfile.txt');
        if (!name) return;
        const content = prompt('Enter file content:', '');
        let filePath = currentPath === '/' ? '/' + name : currentPath + '/' + name;
        filePath = filePath.replace(/\/\//g, '/');
        apiCall('write', { path: filePath, content: content || '' }, function(err, data) {
            if (err) {
                showToast(err, 'error');
            } else {
                showToast('File created', 'success');
                loadDirectory(currentPath);
            }
        });
    }

    async function uploadFolder() {
        const input = document.createElement('input');
        input.type = 'file';
        input.webkitdirectory = true;
        input.directory = true;
        input.multiple = true;
        
        input.onchange = async function(e) {
            const files = Array.from(e.target.files);
            if (files.length === 0) return;
            
            showToast(`Found ${files.length} files to upload`, 'success');
            
            const progressDiv = document.getElementById('uploadProgress');
            const fileNameSpan = document.getElementById('uploadFileName');
            const progressFill = document.getElementById('uploadProgressFill');
            
            progressDiv.classList.remove('hidden');
            
            let completed = 0;
            let failed = 0;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fullRelativePath = file.webkitRelativePath;
                const pathParts = fullRelativePath.split('/');
                pathParts.pop();
                const folderPath = pathParts.join('/');
                
                const percent = (i / files.length) * 100;
                fileNameSpan.innerHTML = `[${i+1}/${files.length}] Uploading: ${fullRelativePath} (${Math.round(percent)}%)`;
                progressFill.style.width = percent + '%';
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('targetDir', currentPath);
                formData.append('relativePath', folderPath);
                
                const success = await new Promise((resolve) => {
                    const xhr = new XMLHttpRequest();
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            try {
                                const result = JSON.parse(xhr.responseText);
                                resolve(result.success);
                            } catch(e) { resolve(false); }
                        } else { resolve(false); }
                    };
                    xhr.onerror = function() { resolve(false); };
                    xhr.open('POST', window.location.href, true);
                    xhr.send(formData);
                });
                
                if (success) {
                    completed++;
                } else {
                    failed++;
                }
            }
            
            progressDiv.classList.add('hidden');
            showToast(`Upload complete: ${completed} files uploaded, ${failed} failed`, completed > 0 ? 'success' : 'error');
            loadDirectory(currentPath);
        };
        
        input.click();
    }

    function setupDragAndDrop() {
        const dragOverlay = document.getElementById('dragOverlay');
        let dragCounter = 0;
        
        document.body.addEventListener('dragenter', function(e) {
            e.preventDefault();
            dragCounter++;
            dragOverlay.classList.add('active');
        });
        
        document.body.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dragCounter--;
            if (dragCounter === 0) {
                dragOverlay.classList.remove('active');
            }
        });
        
        document.body.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        document.body.addEventListener('drop', async function(e) {
            e.preventDefault();
            dragCounter = 0;
            dragOverlay.classList.remove('active');
            
            const items = e.dataTransfer.items;
            const files = [];
            
            async function traverseFileTree(entry, path = '') {
                if (entry.isFile) {
                    const file = await new Promise((resolve) => entry.file(resolve));
                    Object.defineProperty(file, 'webkitRelativePath', {
                        value: (path ? path + '/' : '') + file.name,
                        writable: false
                    });
                    files.push(file);
                } else if (entry.isDirectory) {
                    const reader = entry.createReader();
                    const entries = await new Promise((resolve) => {
                        reader.readEntries(resolve);
                    });
                    for (const childEntry of entries) {
                        await traverseFileTree(childEntry, (path ? path + '/' : '') + entry.name);
                    }
                }
            }
            
            for (let i = 0; i < items.length; i++) {
                const entry = items[i].webkitGetAsEntry();
                if (entry) {
                    await traverseFileTree(entry);
                }
            }
            
            if (files.length > 0) {
                showToast(`Found ${files.length} files to upload`, 'success');
                
                const progressDiv = document.getElementById('uploadProgress');
                const fileNameSpan = document.getElementById('uploadFileName');
                const progressFill = document.getElementById('uploadProgressFill');
                
                progressDiv.classList.remove('hidden');
                
                let completed = 0;
                let failed = 0;
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fullRelativePath = file.webkitRelativePath;
                    const pathParts = fullRelativePath.split('/');
                    pathParts.pop();
                    const folderPath = pathParts.join('/');
                    
                    const percent = (i / files.length) * 100;
                    fileNameSpan.innerHTML = `[${i+1}/${files.length}] Uploading: ${fullRelativePath} (${Math.round(percent)}%)`;
                    progressFill.style.width = percent + '%';
                    
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('targetDir', currentPath);
                    formData.append('relativePath', folderPath);
                    
                    const success = await new Promise((resolve) => {
                        const xhr = new XMLHttpRequest();
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                try {
                                    const result = JSON.parse(xhr.responseText);
                                    resolve(result.success);
                                } catch(e) { resolve(false); }
                            } else { resolve(false); }
                        };
                        xhr.onerror = function() { resolve(false); };
                        xhr.open('POST', window.location.href, true);
                        xhr.send(formData);
                    });
                    
                    if (success) {
                        completed++;
                    } else {
                        failed++;
                    }
                }
                
                progressDiv.classList.add('hidden');
                showToast(`Upload complete: ${completed} files uploaded, ${failed} failed`, completed > 0 ? 'success' : 'error');
                loadDirectory(currentPath);
            }
        });
    }

    function getFileIcon(name) {
        const ext = name.split('.').pop().toLowerCase();
        const icons = {
            'jpg': '<i class="fas fa-image"></i>', 'png': '<i class="fas fa-image"></i>', 'gif': '<i class="fas fa-image"></i>',
            'pdf': '<i class="fas fa-file-pdf"></i>', 'txt': '<i class="fas fa-file-alt"></i>',
            'zip': '<i class="fas fa-file-archive"></i>', 'rar': '<i class="fas fa-file-archive"></i>',
            'mp3': '<i class="fas fa-file-audio"></i>', 'mp4': '<i class="fas fa-file-video"></i>',
            'php': '<i class="fab fa-php"></i>', 'html': '<i class="fab fa-html5"></i>', 'css': '<i class="fab fa-css3-alt"></i>',
            'js': '<i class="fab fa-js"></i>', 'json': '<i class="fas fa-code"></i>'
        };
        return icons[ext] || '<i class="fas fa-file"></i>';
    }

    function formatSize(bytes) {
        if (bytes == 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    document.getElementById('newFolderBtn').onclick = createFolder;
    document.getElementById('newFileBtn').onclick = createFile;
    document.getElementById('uploadBtn').onclick = uploadFolder;
    document.getElementById('downloadSelectedBtn').onclick = downloadSelected;
    document.getElementById('setPasswordBtn').onclick = setPasswordOnSelected;
    document.getElementById('backBtn').onclick = goBack;
    document.getElementById('parentDirBtn').onclick = goBack;
    document.getElementById('selectAllCheckbox').onclick = toggleSelectAll;
    
    document.querySelectorAll('.sidebar-item[data-path]').forEach(el => {
        el.onclick = () => loadDirectory(el.getAttribute('data-path'));
    });
    
    setupDragAndDrop();
    loadDirectory('/');
</script>
</body>
</html>