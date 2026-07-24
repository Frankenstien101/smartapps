<?php

session_start();
include __DIR__ . '/../../DB/dbcon.php';

// PRODUCT MASTER REPORT
if (isset($_GET['action']) && $_GET['action'] === 'loaddevice') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = $_GET['company'];

        $sql = "SELECT
    LINEID,
    COMPANY_ID,
    SITE_ID,
    DEPARTMENT,
    PRINCIPAL,
    POSITION,
    BRAND,
    MODEL,
    IMEI,
    SERIAL,
    DATE_DEPLOYED,
    PERSON_USING,
    NUMBER,
    BALANCE,
    LAST_LOAD_HISTORY,

    DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY) AS NEXT_LOAD_SCHEDULE,

    DATEDIFF(
        DAY,
        CAST(GETDATE() AS DATE),
        DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)
    ) AS DAYS_LEFT,

    LOAD_STATUS

FROM dbo.BS_Device

                WHERE COMPANY_ID = :companyid
                ORDER BY SITE_ID ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($items ?: []);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Application error', 'message' => $e->getMessage()]);
    }
    exit();
}


if (isset($_GET['action']) && $_GET['action'] === 'devicechecking') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = $_GET['company'];
        $datefrom = $_GET['datefrom'];
        $dateto = $_GET['dateto'];

        $sql = "SELECT * FROM BS_Checking_logs 
                WHERE COMPANY_ID = :companyid AND DATE_CHECKED BETWEEN :datefrom AND :dateto
                ORDER BY SITE_ID ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId);
        $stmt->bindParam(':datefrom', $datefrom);
        $stmt->bindParam(':dateto', $dateto);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($items ?: []);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Application error', 'message' => $e->getMessage()]);
    }
    exit();
}



if (isset($_GET['action']) && $_GET['action'] === 'purchasedhistory') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = $_GET['company'];
        $datefrom = $_GET['datefrom'];
        $dateto = $_GET['dateto'];

        $sql = "SELECT * FROM BS_Load_Purchases 
                WHERE COMPANY_ID = :companyid AND DATE_RECORDED BETWEEN :datefrom AND :dateto
                ORDER BY SITE_ID ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId);
        $stmt->bindParam(':datefrom', $datefrom);
        $stmt->bindParam(':dateto', $dateto);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($items ?: []);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Application error', 'message' => $e->getMessage()]);
    }
    exit();
}