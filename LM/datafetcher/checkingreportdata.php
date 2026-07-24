<?php
// device_checking_backend.php

session_start();
include __DIR__ . '/../../DB/dbcon.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============================================
// ACTION: Get device checking results
// ============================================
if ($action === 'get_checking_results') {
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = $_GET['company'] ?? '';
        $dateFrom  = $_GET['datefrom'] ?? date('Y-m-d');
        $dateTo    = $_GET['dateto']   ?? date('Y-m-d');

        if (empty($companyId)) {
            echo json_encode(['error' => 'Company ID is required']);
            exit();
        }

        $sql = "
            SELECT 
                d.LINEID,
                d.COMPANY_ID,
                d.SITE_ID,
                d.DEPARTMENT,
                d.PRINCIPAL,
                d.DATE_DEPLOYED,
                d.PERSON_USING AS [USER],
                d.NUMBER,
                d.BALANCE AS LOAD_BALANCE,
                d.LAST_LOAD_HISTORY,
                d.CONSUMED AS DATA_USAGE,
                d.REMARKS,
                d.DEVICE_STATUS,
                d.REASON_CODE,
                d.DATE_SURRENDERED,
                d.DAYS_TO_REPAIR,
                d.TEMPORARY_DEVICE,
                d.IT_RECOMMENDATION,
                d.CHARGED_TO,
                d.STATUS,
                d.BRAND,
                d.MODEL,
                d.SERIAL,
                d.IMEI,
                d.DEPARTMENT,
                d.PRINCIPAL,
                d.POSITION,
                CASE 
                    WHEN cl.NUMBER IS NOT NULL THEN 'Yes' 
                    ELSE 'No' 
                END AS IS_SUBMIT,
                cl.DATE_CHECKED,
                cl.IS_PHYSICAL_OK,
                cl.HAS_GAMES,
                cl.IS_SYSTEM_UPDATED,
                cl.OTHER_ISSUES,
                cl.CHECKED_BY,
                cl.LINEID AS LOG_LINEID
            FROM BS_Device d
            LEFT JOIN BS_Checking_logs cl 
                ON cl.NUMBER = d.NUMBER 
               AND cl.COMPANY_ID = d.COMPANY_ID
               AND CAST(cl.DATE_CHECKED AS DATE) BETWEEN CAST(:datefrom AS DATE) AND CAST(:dateto AS DATE)
            WHERE d.COMPANY_ID = :companyid AND d.STATUS IN ('ACTIVE' , 'IN USE')
            ORDER BY d.SITE_ID ASC, d.NUMBER ASC, cl.DATE_CHECKED DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId);
        $stmt->bindParam(':datefrom', $dateFrom);
        $stmt->bindParam(':dateto', $dateTo);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Remove duplicate devices (keep only one row per NUMBER)
        $result = [];
        $seen = [];
        foreach ($items as $row) {
            $number = $row['NUMBER'] ?? '';
            if (!isset($seen[$number])) {
                $seen[$number] = true;

                // Clear checking fields if not submitted in the date range
                if ($row['IS_SUBMIT'] === 'No' || empty($row['DATE_CHECKED'])) {
                    $row['DATE_CHECKED'] = '';
                    $row['IS_PHYSICAL_OK'] = '';
                    $row['HAS_GAMES'] = '';
                    $row['IS_SYSTEM_UPDATED'] = '';
                    $row['OTHER_ISSUES'] = '';
                    $row['CHECKED_BY'] = '';
                    $row['LOG_LINEID'] = null;
                }

                $result[] = $row;
            }
        }

        echo json_encode($result);
        
    } catch (PDOException $e) {
        echo json_encode([
            'error' => 'Database error', 
            'message' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Application error', 
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Default
echo json_encode(['error' => 'Invalid action specified']);
exit();
?>