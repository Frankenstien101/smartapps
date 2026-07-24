<?php
/**
 * Reset Database to Clean State (using DELETE to avoid FK constraints)
 * 
 * This script clears all transactional data and resets the system to a fresh state.
 * It keeps master data (products, customers, users) but resets inventory and sales history.
 * 
 * !! WARNING !! This will delete all call sheets, POs, sales records, and audit logs.
 * Backup your database before running this script.
 */

require_once __DIR__ . '/../config/database.php';

echo "<pre>";

try {
    // 1. Delete rows from child tables first (in correct order)
    echo "Deleting transactional data...\n";
    
    // Call sheet items (child of call_sheets)
    $pdo->exec("DELETE FROM call_sheet_items");
    echo "  - call_sheet_items cleared\n";
    
    // PO items (child of purchase_orders)
    $pdo->exec("DELETE FROM po_items");
    echo "  - po_items cleared\n";
    
    // Purchase orders (child of call_sheets)
    $pdo->exec("DELETE FROM purchase_orders");
    echo "  - purchase_orders cleared\n";
    
    // Call sheets
    $pdo->exec("DELETE FROM call_sheets");
    echo "  - call_sheets cleared\n";
    
    // Sales history
    $pdo->exec("DELETE FROM sales_history");
    echo "  - sales_history cleared\n";
    
    // Stock history
    $pdo->exec("DELETE FROM stock_history");
    echo "  - stock_history cleared\n";
    
    // Audit logs
    $pdo->exec("DELETE FROM audit_log");
    echo "  - audit_log cleared\n";
    
    // Call sheet logs
    $pdo->exec("DELETE FROM call_sheet_log");
    echo "  - call_sheet_log cleared\n";
    
    // Login attempts
    $pdo->exec("DELETE FROM login_attempts");
    echo "  - login_attempts cleared\n";
    
    // 2. Reset identity columns (if using IDENTITY)
    // Note: SQL Server uses DBCC CHECKIDENT
    $tablesIdentity = [
        'call_sheet_items' => 'item_id',
        'po_items' => 'po_item_id',
        'purchase_orders' => 'po_id',
        'call_sheets' => 'call_sheet_id',
        'sales_history' => 'sales_id',
        'stock_history' => 'history_id',
        'audit_log' => 'log_id',
        'call_sheet_log' => 'log_id',
        'login_attempts' => 'id'
    ];
    foreach ($tablesIdentity as $table => $column) {
        $pdo->exec("DBCC CHECKIDENT ('$table', RESEED, 0)");
        echo "  - reset identity for $table\n";
    }
    
    // 3. Reset inventory to initial seed values
    $inventoryData = [
        ['product_id' => 1, 'current_stock' => 25, 'incoming_stock' => 0],
        ['product_id' => 2, 'current_stock' => 60, 'incoming_stock' => 0],
        ['product_id' => 3, 'current_stock' => 18, 'incoming_stock' => 0],
        ['product_id' => 4, 'current_stock' => 30, 'incoming_stock' => 50],
        ['product_id' => 5, 'current_stock' => 40, 'incoming_stock' => 0],
        ['product_id' => 6, 'current_stock' => 10, 'incoming_stock' => 0],
        ['product_id' => 7, 'current_stock' => 55, 'incoming_stock' => 0],
        ['product_id' => 8, 'current_stock' => 8,  'incoming_stock' => 0],
        ['product_id' => 9, 'current_stock' => 70, 'incoming_stock' => 0],
        ['product_id' => 10, 'current_stock' => 20, 'incoming_stock' => 0],
        ['product_id' => 11, 'current_stock' => 65, 'incoming_stock' => 0],
        ['product_id' => 12, 'current_stock' => 12, 'incoming_stock' => 0],
    ];
    echo "\nResetting inventory...\n";
    foreach ($inventoryData as $inv) {
        $stmt = $pdo->prepare("UPDATE inventory SET current_stock = ?, incoming_stock = ?, last_updated = GETDATE() WHERE product_id = ?");
        $stmt->execute([$inv['current_stock'], $inv['incoming_stock'], $inv['product_id']]);
    }
    echo "  - inventory reset\n";

    // 4. Add sample sales history for the last 30 days
    echo "\nAdding sample sales history...\n";
    // We need to ensure we have a valid created_by user ID (assuming user_id=2 is jsantos)
    // If not, we'll look up user_id for username 'jsantos'
    $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = 'jsantos'");
    $userStmt->execute();
    $salesRepId = $userStmt->fetchColumn();
    if (!$salesRepId) {
        // fallback to first user with role SalesRep
        $userStmt = $pdo->prepare("SELECT TOP 1 user_id FROM users WHERE role = 'SalesRep'");
        $userStmt->execute();
        $salesRepId = $userStmt->fetchColumn();
    }
    if (!$salesRepId) {
        $salesRepId = 2; // hardcoded fallback
    }
    
    // Get customer IDs (assume they exist)
    $customerIds = [1,2,3,4]; // Puregold, Alturas, KCC, Gaisano (seed)
    
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        // Tide – steady sales
        $pdo->prepare("INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount, created_by) VALUES (1, ?, ?, 6 + (? % 5), 168.00, (6 + (? % 5)) * 168.00, ?)")
            ->execute([$customerIds[0], $date, $i, $i, $salesRepId]);
        // Pantene – high movement
        $pdo->prepare("INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount, created_by) VALUES (6, ?, ?, 4 + (? % 3), 198.00, (4 + (? % 3)) * 198.00, ?)")
            ->execute([$customerIds[1], $date, $i, $i, $salesRepId]);
        // Pampers – moderate
        if ($i % 2 == 0) {
            $pdo->prepare("INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount, created_by) VALUES (8, ?, ?, 3, 485.00, 3 * 485.00, ?)")
                ->execute([$customerIds[2], $date, $salesRepId]);
        }
        // Joy – slow
        if ($i % 3 == 0) {
            $pdo->prepare("INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount, created_by) VALUES (7, ?, ?, 5, 102.00, 5 * 102.00, ?)")
                ->execute([$customerIds[3], $date, $salesRepId]);
        }
    }
    echo "  - sample sales history added\n";

    // 5. Ensure users have email addresses for notifications
    echo "\nUpdating user emails (if missing)...\n";
    $pdo->exec("UPDATE users SET email = 'admin@example.com' WHERE username = 'admin' AND (email IS NULL OR email = '')");
    $pdo->exec("UPDATE users SET email = 'jsantos@example.com' WHERE username = 'jsantos' AND (email IS NULL OR email = '')");
    $pdo->exec("UPDATE users SET email = 'mreyes@example.com' WHERE username = 'mreyes' AND (email IS NULL OR email = '')");

    echo "\n✅ Database reset complete!\n";
    echo "You can now log in with:\n";
    echo "  - admin / admin123 (Admin)\n";
    echo "  - jsantos / admin123 (SalesRep)\n";
    echo "  - mreyes / admin123 (Buyer)\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>