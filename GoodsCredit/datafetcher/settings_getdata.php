<?php
session_start();
include(__DIR__ . "/../../DB/dbcon.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {

    if ($action === 'saveonvanloaddetails') {
        $transactionId = $_GET['transactionid'] ?? '';
        $sellerid      = $_GET['sellerid'] ?? '';
        $barcode       = $_GET['barcode'] ?? '';
        $itemId        = $_GET['itemid'] ?? '';
        $description   = $_GET['description'] ?? '';
        $batch         = $_GET['batch'] ?? '';
        $cs            = $_GET['cs'] ?? 0;
        $sw            = $_GET['sw'] ?? 0;
        $it            = $_GET['it'] ?? 0;
        $price         = $_GET['price'] ?? 0;
        $itPerCase     = $_GET['itpercase'] ?? 0;
        $itPerSw       = $_GET['itpersw'] ?? 0;
        $sihit         = $_GET['sihit'] ?? 0;
        $totalcs       = $_GET['totalcs'] ?? 0;
        $totalit       = $_GET['totalit'] ?? 0;

        $sql = "INSERT INTO Aquila_Van_Loading_Details
                   (SELLER_ID, TRANSACTION_ID, BARCODE, ITEM_CODE, DESCRIPTION, 
                    BATCH, CS, SW, IT, PRICE, ITEM_PER_CASE, ITEM_PER_SW, 
                    SIH_IT, TOTAL_CS_AMOUNT, TOTAL_IT_AMOUNT ,IS_SYNCED )
                VALUES
                   (:sellerid, :transactionid, :barcode, :item_code, :description,
                    :batch, :cs, :sw, :it, :item_cost, :item_per_case, :item_per_sw,
                    :sih_it, :total_cs_amount, :total_it_amount , 0)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':sellerid'       => $sellerid,
            ':transactionid'  => $transactionId,
            ':barcode'        => $barcode,
            ':item_code'      => $itemId,
            ':description'    => $description,
            ':batch'          => $batch,
            ':cs'             => $cs,
            ':sw'             => $sw,
            ':it'             => $it,
            ':item_cost'      => $price,
            ':item_per_case'  => $itPerCase,
            ':item_per_sw'    => $itPerSw,
            ':sih_it'         => $sihit,
            ':total_cs_amount'=> $totalcs,
            ':total_it_amount'=> $totalit
        ]);

        echo json_encode(['success' => true, 'line_id' => $conn->lastInsertId()]);
        exit();

         } else if ($action === 'loadagents') {
    header('Content-Type: application/json; charset=utf-8'); // force JSON header

    $companyid = $_GET['companyid'] ?? '';
     $siteid = $_GET['siteid'] ?? '';

    $sql = "SELECT *
            FROM Dash_Agents 
            WHERE COMPANY_ID = :companyid
            AND SITE_ID = :siteid
            ORDER BY SUB_DA ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':companyid' => $companyid,
        ':siteid' => $siteid,

    ]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Always return JSON (even if empty)
    echo json_encode($items ?: []);
    exit();

} else if ($action === 'logoutagent') {
         $companyid = $_GET['companyid'] ?? '';
     $siteid = $_GET['siteid'] ?? '';
     $agent = $_GET['agent'] ?? '';

        $sql = " UPDATE
                 Dash_Agents 
                 SET IS_LOGIN = '0'
                 WHERE COMPANY_ID = :companyid
                 AND SITE_ID = :siteid 
                 AND SUB_DA = :agent";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
           ':companyid' => $companyid,
           ':siteid' => $siteid,
           ':agent' => $agent,
        ]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($items ?: []);
        exit();

    } else if (isset($_GET['action'])) {
      if (!$conn || !($conn instanceof PDO)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Database connection failed']);
        exit();
        }

       if ($_GET['action'] === 'vanalloc') {
        header('Content-Type: application/json');

        try {
            $companyId = $_GET['company'] ?? '';
            $siteid = $_GET['siteid'] ?? '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            $datefrom = $_GET['datefrom'] ?? null;
            $dateto = $_GET['dateto'] ?? null;
            $sellersRaw = $_GET['sellers'] ?? '';

            if (!$companyId || !$siteid || !$datefrom || !$dateto) {
                echo json_encode(['error' => true, 'message' => 'Missing required parameters']);
                exit();
            }

            $sellers = array_filter(array_map('trim', explode(',', $sellersRaw)), 'strlen');
            if (empty($sellers)) {
                echo json_encode(['error' => true, 'message' => 'No sellers selected']);
                exit();
            }
            $placeholders = implode(',', array_fill(0, count($sellers), '?'));

            // Count total matching records
      
             $countSql = "
               SELECT COUNT(*) AS total
                         FROM Aquila_Van_Loading_Transaction
                         WHERE COMPANY_ID = ?
                           AND SITE_ID = ?
                           AND DATE_CREATED BETWEEN ? AND ?
                           AND SELLER_ID IN ($placeholders)
            ";



            $countStmt = $conn->prepare($countSql);
            $countParams = array_merge([$companyId, $siteid, $datefrom, $dateto], $sellers);
            $countStmt->execute($countParams);
            $total = (int) $countStmt->fetchColumn();

            $offsetInt = (int)$offset;
            $limitInt = (int)$limit;

            // Fetch paginated data


        $dataSql = " SELECT
                [COMPANY_ID]
                 ,[SITE_ID]
                 ,[SELLER_ID]
                 ,[LOADING_ID]
                 ,[DATE_CREATED]
                 ,[AMOUNT]
                 ,[TOTAL_LINES]
                 ,[STATUS]
                 ,[IS_SYNC]
                 ,[WAREHOUSE_CODE]
                 ,[LOCKED_BY]
                 ,[RECON_ID]
                 ,[REMARKS]
                FROM [dbo].[Aquila_Van_Loading_Transaction]
               
                WHERE COMPANY_ID = ?
                  AND SITE_ID = ?

                  AND DATE_CREATED BETWEEN ? AND ?
       
              AND SELLER_ID IN ($placeholders)

                     AND STATUS != 'DRAFT'

                ORDER BY DATE_CREATED asc

                OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";


            $dataStmt = $conn->prepare($dataSql);
            $dataParams = array_merge([$companyId, $siteid, $datefrom, $dateto], $sellers);
            $dataStmt->execute($dataParams);
            $items = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'total' => $total,
                'data' => $items,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => true, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
        
    }
        /// load saved items
   




    } else {
        echo json_encode(['success'=>false, 'error'=>'No valid action']);
        exit();

    /// add to ledger 


    }

} catch (Exception $e) {
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    exit();
}
