
<?php
session_start();
include __DIR__ . '/../../../DB/dbcon.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {

    // -------------------------------------------------
    //  LOAD CREDITORS (EMPLOYEES)
    // -------------------------------------------------
    if ($action === 'loadcreditors') {
        // ---- FIX 1: Use the same parameter name as JS ----
        $companyid = $_GET['company'] ?? '';   // ← matches &company=BSPI

        if (empty($companyid)) {
            echo json_encode(['success'=>false, 'error'=>'Missing company']);
            exit;
        }

        $sql = "SELECT 
                    SITE_ID,
                    DEPARTMENT,
                    EMPLOYEE_ID,
                    EMPLOYEE_NAME,
                    CURRENT_BALANCE
                FROM GC_EMPLOYEES 
                WHERE STATUS = 'ACTIVE'
                  AND COMPANY_ID = :companyid
                ORDER BY EMPLOYEE_NAME ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':companyid' => $companyid]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ---- OPTIONAL: Format CURRENT_BALANCE as string ----
        foreach ($items as &$item) {
            $item['CURRENT_BALANCE'] = number_format((float)$item['CURRENT_BALANCE'], 2, '.', '');
        }

        echo json_encode($items);
        exit;


        } else if ($action === 'loaditems') {
        $companyId = $_GET['company'] ?? '';
        $siteId    = $_GET['siteid'] ?? '';

       $sql = "
  SELECT 
                  *
                FROM GC_INVENTORY 

				LEFT JOIN GC_PRODUCT_MASTER ON GC_PRODUCT_MASTER.CODE = GC_INVENTORY.ITEM_ID AND GC_PRODUCT_MASTER.COMPANY_ID = GC_INVENTORY.COMPANY_ID AND GC_PRODUCT_MASTER.SITE_ID = GC_INVENTORY.SITE_ID
                WHERE 
                   GC_INVENTORY.COMPANY_ID = :company
                    AND GC_INVENTORY.SITE_ID = :siteid
                ORDER BY GC_INVENTORY.DESCRIPTION ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':company' => $companyId , ':siteid' => $siteId]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ---- OPTIONAL: Format CURRENT_BALANCE as string ----
   

        echo json_encode($items);
        exit;


         } else if ($action === 'get_new_transaction') {
        $companyId = $_GET['company'] ?? '';
        $siteId    = $_GET['siteid'] ?? '';

        $sql = "SELECT COUNT(*) + 1 AS total
                FROM GC_CHARGE_TRANSACTION
                WHERE SITE_ID = :siteid AND COMPANY_ID = :companyid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':siteid'=>$siteId, ':companyid'=>$companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success'=>true, 'count'=>$row['total'] ?? 1]);
        exit();

    }

    // ... (rest of your actions unchanged)


    

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
?>
