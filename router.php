<?php
// router.php - Full working version

// Get the requested path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = ltrim($path, '/');

// Debug mode (remove after testing)
// echo "Debug: Looking for '$path'<br>";

// If it's an actual file (CSS, JS, images), serve it directly
if ($path && file_exists($path) && !is_dir($path)) {
    return false;
}

// Handle SIDJAN routes specifically
if (strpos($path, 'SIDJAN/') === 0) {
    // Extract the page name after SIDJAN/
    $page = substr($path, 7); // Remove 'SIDJAN/'
    
    // Remove any query string or trailing slashes
    $page = rtrim($page, '/');
    if (strpos($page, '?') !== false) {
        $page = substr($page, 0, strpos($page, '?'));
    }
    
    // Default to home if empty
    if (empty($page)) {
        $page = 'home';
    }
    
    // Check if the PHP file exists
    $file = __DIR__ . '/SIDJAN/' . $page . '.php';
    if (file_exists($file)) {
        include $file;
        exit;
    } else {
        http_response_code(404);
        echo "404 - File not found: SIDJAN/{$page}.php";
        exit;
    }
}

// Handle root level PHP files without extension
if ($path && !strpos($path, '.') && file_exists($path . '.php')) {
    include $path . '.php';
    exit;
}

// Handle root index
if (empty($path) || $path == 'index') {
    if (file_exists('index.php')) {
        include 'index.php';
        exit;
    }
}

// If nothing matched, 404
http_response_code(404);
echo "404 - Page not found: " . htmlspecialchars($path);
?>