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


// Add this to your reportsdata.php file

if (isset($_GET['action']) && $_GET['action'] === 'get_device_by_serial') {
    header('Content-Type: application/json');
    
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = $_GET['company'] ?? $_SESSION['Company_ID'] ?? '';
        $serial = $_GET['serial'] ?? '';

        if (empty($companyId)) {
            echo json_encode(['error' => 'Company ID is required']);
            exit();
        }

        if (empty($serial)) {
            echo json_encode(['error' => 'Serial number is required']);
            exit();
        }

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
            CONSUMED,
            REMARKS,
            LAST_LOAD_HISTORY,
            LOAD_STATUS,
            LOAD_TERMS,
            DATA_BALANCE_MIN,
            DEVICE_STATUS,
            REASON_CODE,
            DATE_SURRENDERED,
            DAYS_TO_REPAIR,
            TEMPORARY_DEVICE,
            IT_RECOMMENDATION,
            CHARGED_TO,
            STATUS,
            IS_COMPLIED,
            DATE_COMPLIED,
            DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY) AS NEXT_LOAD_SCHEDULE,
            DATEDIFF(DAY, CAST(GETDATE() AS DATE), DATEADD(MONTH, LOAD_TERMS, LAST_LOAD_HISTORY)) AS DAYS_LEFT,
            PERSON_IMAGE
        FROM dbo.BS_Device
        WHERE COMPANY_ID = :companyid
          AND SERIAL = :serial
          AND STATUS IN ('ACTIVE', 'IN USE')";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId);
        $stmt->bindParam(':serial', $serial);
        $stmt->execute();

        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($device) {
            echo json_encode(['success' => true, 'device' => $device]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Device not found']);
        }
        
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