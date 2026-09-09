<?php
// fast_upload.php
ini_set('memory_limit', '512M');
set_time_limit(0); // No timeout for large files

// Include Composer autoload for PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// -------------------- CONNECTION --------------------
try {
    $conn = new PDO(
        "sqlsrv:server=tcp:bspidbservernew.database.windows.net,1433;Database=BSPIDBNEW",
        "sqladmin",
        'b$p1.@dm1n'
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// -------------------- UPLOAD HANDLING --------------------
$message = '';
$inserted = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "❌ Upload error: " . $file['error'];
    } else {
        $allowed = ['xlsx', 'xls'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $message = "❌ Please upload a valid Excel file (.xlsx or .xls).";
        } else {
            try {
                // Load the spreadsheet
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(); // Loads all rows into an array

                // Assume first row is header – skip it
                $headerSkipped = false;
                $dataRows = [];

                // Define table columns in correct order
                $columns = [
                    'COMPANY_ID', 'ITEM_ID', 'DESCRIPTION', 'CATEGORY', 'BRAND',
                    'CS_BARCODE', 'IT_BARCODE', 'IT_PER_CASE', 'IT_COST',
                    'CS_COST', 'STATUS', 'UPLOADID', 'WEIGHT', 'VOLUME'
                ];

                // If you want to map by header names instead of position, uncomment the next block
                // (and skip the first row, then map column index to column name)
                // For simplicity here we assume Excel columns are in the exact same order.

                // Build batch insert
                $batchSize = 500;
                $values = [];

                // Start from row 1 (0-based) -> row index 1 is the second row (if first is header)
                // If there is no header, change $startRow = 0
                $startRow = 1; // skip header

                for ($rowIdx = $startRow; $rowIdx < count($rows); $rowIdx++) {
                    $rowData = $rows[$rowIdx];
                    // Ensure we have enough columns; pad with null if missing
                    $rowData = array_pad($rowData, count($columns), null);

                    // Build an array of values, properly quoted or NULL
                    $rowValues = [];
                    foreach ($rowData as $colIdx => $cellValue) {
                        // Clean up string values
                        if (is_string($cellValue)) {
                            $cellValue = trim($cellValue);
                        }
                        // If empty string, treat as NULL (or you can keep empty)
                        if ($cellValue === '' || $cellValue === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            // For numeric fields, we keep them as numbers; for strings we quote
                            // All columns except maybe numeric ones: we'll just quote everything safely.
                            // Using PDO::quote ensures proper escaping.
                            $rowValues[] = $conn->quote((string)$cellValue);
                        }
                    }

                    $values[] = '(' . implode(',', $rowValues) . ')';

                    // When batch size reached, execute insert
                    if (count($values) >= $batchSize) {
                        $inserted += executeBatch($conn, $columns, $values);
                        $values = [];
                    }
                }

                // Insert remaining rows
                if (!empty($values)) {
                    $inserted += executeBatch($conn, $columns, $values);
                }

                $message = "✅ Upload complete! Inserted $inserted rows successfully.";

            } catch (Exception $e) {
                $message = "❌ Error processing file: " . $e->getMessage();
            }
        }
    }
}

/**
 * Execute a batch INSERT
 */
function executeBatch($conn, $columns, $values) {
    if (empty($values)) return 0;
    $columnList = implode(',', $columns);
    $sql = "INSERT INTO [Dash_Product_Master] ($columnList) VALUES " . implode(',', $values);
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    } catch (PDOException $e) {
        // Log error, but continue? For simplicity, we rethrow.
        throw new Exception("Batch insert failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fast Excel Upload to MSSQL</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .container { max-width: 600px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        input[type="file"] { margin: 10px 0; }
        input[type="submit"] { background: #007bff; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h2>📤 Upload Excel File to Product Master</h2>
    <?php if ($message): ?>
        <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <p>Select an Excel file (.xlsx or .xls) with columns in the following order:</p>
        <ul>
            <li>COMPANY_ID, ITEM_ID, DESCRIPTION, CATEGORY, BRAND, CS_BARCODE, IT_BARCODE, IT_PER_CASE, IT_COST, CS_COST, STATUS, UPLOADID, WEIGHT, VOLUME</li>
        </ul>
        <p><small>First row will be treated as header and skipped.</small></p>
        <input type="file" name="excel_file" accept=".xlsx,.xls" required>
        <br>
        <input type="submit" value="Upload & Insert">
    </form>
</div>
</body>
</html>