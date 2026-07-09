<?php
// This will show the actual error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

try {
    // Try to include your api.php
    require __DIR__ . '/api.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
}