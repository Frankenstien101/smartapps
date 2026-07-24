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
                IS_COMPLIED
            FROM BS_Device
            WHERE COMPANY_ID = :company_id
            AND LOAD_STATUS = 'FOR LOAD'
            ORDER BY DATE_ADDED DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':company_id' => $company_id
        ]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    echo json_encode([]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
