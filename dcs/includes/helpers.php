<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Ensure the login_attempts table exists.
 * Creates it if missing.
 */
function ensureLoginAttemptsTable() {
    global $pdo;
    try {
        $check = $pdo->query("SELECT OBJECT_ID('login_attempts', 'U')");
        $exists = $check->fetchColumn();
        if ($exists === false || $exists === null) {
            $pdo->exec("
                CREATE TABLE login_attempts (
                    id INT IDENTITY PRIMARY KEY,
                    ip VARCHAR(45),
                    attempt_time DATETIME DEFAULT GETDATE()
                )
            ");
        }
    } catch (PDOException $e) {
        error_log("Failed to create login_attempts table: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Ensure the call_sheet_log table exists.
 * Creates it if missing.
 */
function ensureCallSheetLogTable() {
    global $pdo;
    try {
        $check = $pdo->query("SELECT OBJECT_ID('call_sheet_log', 'U')");
        $exists = $check->fetchColumn();
        if ($exists === false || $exists === null) {
            $pdo->exec("
                CREATE TABLE call_sheet_log (
                    log_id INT IDENTITY(1,1) PRIMARY KEY,
                    call_sheet_id INT NOT NULL REFERENCES call_sheets(call_sheet_id),
                    user_id INT NOT NULL REFERENCES users(user_id),
                    action VARCHAR(50) NOT NULL,
                    old_values VARCHAR(MAX),
                    new_values VARCHAR(MAX),
                    created_at DATETIME DEFAULT GETDATE()
                )
            ");
        }
    } catch (PDOException $e) {
        error_log("Failed to create call_sheet_log table: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Ensure the email column exists in the users table.
 * Adds it if missing.
 */
function ensureEmailColumn() {
    global $pdo;
    try {
        $check = $pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'email'
        ");
        if ($check->fetchColumn() === false) {
            $pdo->exec("ALTER TABLE users ADD email VARCHAR(100) NULL");
        }
    } catch (PDOException $e) {
        error_log("Failed to add email column: " . $e->getMessage());
    }
}

/**
 * Ensure the customer_id column exists in the users table.
 * Adds it if missing.
 */
function ensureCustomerIdColumn() {
    global $pdo;
    try {
        $check = $pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'customer_id'
        ");
        if ($check->fetchColumn() === false) {
            $pdo->exec("ALTER TABLE users ADD customer_id INT NULL REFERENCES customers(customer_id)");
        }
    } catch (PDOException $e) {
        error_log("Failed to add customer_id column: " . $e->getMessage());
    }
}

// Audit logging for call sheets
function logCallSheetAction($sheet_id, $action, $old = null, $new = null) {
    global $pdo;
    ensureCallSheetLogTable();
    $stmt = $pdo->prepare("INSERT INTO call_sheet_log (call_sheet_id, user_id, action, old_values, new_values) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$sheet_id, $_SESSION['user_id'], $action, $old, $new]);
}

// CSV export helper
function exportToCSV($data, $headers, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Rate limiting
function checkLoginAttempts($ip) {
    global $pdo;
    ensureLoginAttemptsTable();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempt_time > DATEADD(minute, -15, GETDATE())");
    $stmt->execute([$ip]);
    $count = $stmt->fetchColumn();
    return $count < 5;
}

function logLoginAttempt($ip) {
    global $pdo;
    ensureLoginAttemptsTable();
    $pdo->prepare("INSERT INTO login_attempts (ip, attempt_time) VALUES (?, GETDATE())")->execute([$ip]);
}

function clearLoginAttempts($ip) {
    global $pdo;
    ensureLoginAttemptsTable();
    $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
}

/**
 * Ensure the user_stores table exists (many-to-many for SalesRep assigned stores).
 * Creates it if missing.
 */
function ensureUserStoresTable() {
    global $pdo;
    try {
        $check = $pdo->query("SELECT OBJECT_ID('user_stores', 'U')");
        $exists = $check->fetchColumn();
        if ($exists === false || $exists === null) {
            $pdo->exec("
                CREATE TABLE user_stores (
                    user_id INT NOT NULL REFERENCES users(user_id),
                    customer_id INT NOT NULL REFERENCES customers(customer_id),
                    PRIMARY KEY (user_id, customer_id)
                )
            ");
        }
    } catch (PDOException $e) {
        error_log("Failed to create user_stores table: " . $e->getMessage());
    }
}

/**
 * Ensure the created_by column exists in the sales_history table.
 * Adds it if missing.
 */
function ensureSalesHistoryCreatedByColumn() {
    global $pdo;
    try {
        $check = $pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'sales_history' AND COLUMN_NAME = 'created_by'
        ");
        if ($check->fetchColumn() === false) {
            $pdo->exec("ALTER TABLE sales_history ADD created_by INT NULL REFERENCES users(user_id)");
        }
    } catch (PDOException $e) {
        error_log("Failed to add created_by column to sales_history: " . $e->getMessage());
    }
}

/**
 * Ensure the products_log table exists (legacy – kept for compatibility).
 */
function ensureProductsLogTable() {
    global $pdo;
    $check = $pdo->query("SELECT OBJECT_ID('products_log', 'U')");
    if ($check->fetchColumn() === false || $check->fetchColumn() === null) {
        $pdo->exec("
            CREATE TABLE products_log (
                log_id INT IDENTITY(1,1) PRIMARY KEY,
                product_id INT NOT NULL,
                action VARCHAR(20) NOT NULL,
                old_data VARCHAR(MAX),
                new_data VARCHAR(MAX),
                user_id INT REFERENCES users(user_id),
                created_at DATETIME DEFAULT GETDATE()
            )
        ");
    }
}

/**
 * Ensure the audit_log table exists.
 * Creates it if missing.
 */
function ensureAuditLogTable() {
    global $pdo;
    try {
        $check = $pdo->query("SELECT OBJECT_ID('audit_log', 'U')");
        if ($check->fetchColumn() === false) {
            $pdo->exec("
                CREATE TABLE audit_log (
                    log_id INT IDENTITY(1,1) PRIMARY KEY,
                    table_name VARCHAR(50) NOT NULL,
                    record_id INT NOT NULL,
                    action VARCHAR(20) NOT NULL,
                    old_data VARCHAR(MAX),
                    new_data VARCHAR(MAX),
                    user_id INT REFERENCES users(user_id),
                    ip_address VARCHAR(45),
                    user_agent VARCHAR(255),
                    created_at DATETIME DEFAULT GETDATE()
                )
            ");
            $pdo->exec("CREATE INDEX idx_audit_table ON audit_log(table_name)");
            $pdo->exec("CREATE INDEX idx_audit_record ON audit_log(record_id)");
            $pdo->exec("CREATE INDEX idx_audit_user ON audit_log(user_id)");
            $pdo->exec("CREATE INDEX idx_audit_date ON audit_log(created_at)");
        }
    } catch (PDOException $e) {
        error_log("Failed to create audit_log table: " . $e->getMessage());
    }
}

/**
 * Log an audit action.
 *
 * @param string $table_name  The table being modified.
 * @param int    $record_id   The ID of the record.
 * @param string $action      INSERT, UPDATE, DELETE.
 * @param array|null $old_data  The old data (for UPDATE/DELETE).
 * @param array|null $new_data  The new data (for INSERT/UPDATE).
 */
function logAudit($table_name, $record_id, $action, $old_data = null, $new_data = null) {
    global $pdo;
    ensureAuditLogTable();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (table_name, record_id, action, old_data, new_data, user_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $table_name,
        $record_id,
        $action,
        $old_data ? json_encode($old_data) : null,
        $new_data ? json_encode($new_data) : null,
        $_SESSION['user_id'] ?? null,
        $ip,
        $ua
    ]);
}
/**
 * Generate a PDF from HTML and output it.
 * Uses Dompdf – requires composer require dompdf/dompdf.
 *
 * @param string $html     HTML content.
 * @param string $filename Output filename.
 */
function generatePDF($html, $filename) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename);
    exit;
}