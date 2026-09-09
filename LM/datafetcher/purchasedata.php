<?php
session_start();
include __DIR__ . '/../../DB/dbcon.php';

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $action = $_GET['action'] ?? '';

    if ($action === 'forload') {

        $company_id = $_SESSION['Company_ID'] ?? '';

      

        $sql = "
            SELECT 
                LINEID,
                SITE_ID,
                DEPARTMENT,
                PRINCIPAL,
                POSITION,
                BRAND,
                MODEL,
                SERIAL,
                DATE_DEPLOYED,
                PERSON_USING,
                NUMBER,
                BALANCE,
                LOAD_STATUS,
                LAST_LOAD_HISTORY,
                IS_COMPLIED,
                CASE
                    WHEN BALANCE < COALESCE(DATA_BALANCE_MIN, 0) THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY IS NULL THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -10, GETDATE()) THEN 'FOR LOAD'
                    WHEN LAST_LOAD_HISTORY < DATEADD(MONTH, -CAST(COALESCE(LOAD_TERMS, 0) AS INT), GETDATE()) THEN 'FOR LOAD'
                    ELSE 'OK'
                END AS EFFECTIVE_LOAD_STATUS
            FROM BS_Device
            WHERE COMPANY_ID = :company_id
            ORDER BY DATE_ADDED DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':company_id' => $company_id
        ]);

        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $devices = array_values(array_filter($devices, function ($device) {
            return ($device['EFFECTIVE_LOAD_STATUS'] ?? 'OK') === 'FOR LOAD';
        }));

        foreach ($devices as &$device) {
            $device['LOAD_STATUS'] = $device['EFFECTIVE_LOAD_STATUS'] ?? $device['LOAD_STATUS'];
        }
        unset($device);

        echo json_encode($devices);
        exit;
    }

    echo json_encode([]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
