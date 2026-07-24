<?php
function logError($message, $context = []) {
    $logEntry = date('Y-m-d H:i:s') . " - " . $message . " " . json_encode($context) . PHP_EOL;
    error_log($logEntry, 3, __DIR__ . '/../logs/error.log');
}

function logInfo($message, $context = []) {
    $logEntry = date('Y-m-d H:i:s') . " - INFO - " . $message . " " . json_encode($context) . PHP_EOL;
    error_log($logEntry, 3, __DIR__ . '/../logs/info.log');
}