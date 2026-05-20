
<?php
session_start();
include(__DIR__ . "/../../DB/dbcon.php");
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '512M');
set_time_limit(300); // sometimes necessary for Azure

$action = $_GET['action'] ?? '';
$isall = $_GET['isall'] ?? '';

try {
    if ($action === 'saveonvanloaddetails') {
        header('Content-Type: application/json');

        // Retrieve parameters from GET
        $transactionId = $_GET['transactionid'] ?? '';
        $sellerid = $_GET['sellerid'] ?? '';
        $barcode = $_GET['barcode'] ?? '';
        $itemId = $_GET['itemid'] ?? '';
        $description = $_GET['description'] ?? '';
        $batch = $_GET['batch'] ?? '';
        $cs = $_GET['cs'] ?? 0;
        $sw = $_GET['sw'] ?? 0;
        $it = $_GET['it'] ?? 0;
        $price = $_GET['price'] ?? 0;
        $itPerCase = $_GET['itpercase'] ?? 0;
        $itPerSw = $_GET['itpersw'] ?? 0;
        $sihit = $_GET['sihit'] ?? 0;
        $totalcs = $_GET['totalcs'] ?? 0;
        $totalit = $_GET['totalit'] ?? 0;

        // Prepare SQL insert statement
        $sql = "INSERT INTO Aquila_Van_Loading_Details (
                    SELLER_ID, TRANSACTION_ID, BARCODE, ITEM_CODE, DESCRIPTION, BATCH,
                    CS, SW, IT, PRICE, ITEM_PER_CASE, ITEM_PER_SW, SIH_IT,
                    TOTAL_CS_AMOUNT, TOTAL_IT_AMOUNT, IS_SYNCED
                ) VALUES (
                    :sellerid, :transactionid, :barcode, :item_code, :description, :batch,
                    :cs, :sw, :it, :item_cost, :item_per_case, :item_per_sw, :sih_it,
                    :total_cs_amount, :total_it_amount, 0
                )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':sellerid' => $sellerid,
            ':transactionid' => $transactionId,
            ':barcode' => $barcode,
            ':item_code' => $itemId,
            ':description' => $description,
            ':batch' => $batch,
            ':cs' => $cs,
            ':sw' => $sw,
            ':it' => $it,
            ':item_cost' => $price,
            ':item_per_case' => $itPerCase,
            ':item_per_sw' => $itPerSw,
            ':sih_it' => $sihit,
            ':total_cs_amount' => $totalcs,
            ':total_it_amount' => $totalit
        ]);

        echo json_encode([
            'success' => true,
            'line_id' => $conn->lastInsertId()
        ]);
        exit();

    } elseif ($action === 'deliveryperformance') {
        // Generate CSV for delivery performance
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="van_allocation.csv"');

        $companyId = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? null;
        $dateto = $_GET['dateto'] ?? null;

        // SQL to fetch data
        $dataSql = "SELECT 
                        COMPANY_ID, SITE_ID, t.SELLER_ID, t.DATE_CREATED, d.TRANSACTION_ID,
                        d.BARCODE, d.ITEM_CODE, d.DESCRIPTION, d.BATCH, d.CS, d.SW, d.IT, d.PRICE,
                        d.ITEM_PER_CASE, d.ITEM_PER_SW, d.SIH_IT, d.TOTAL_CS_AMOUNT, d.TOTAL_IT_AMOUNT, t.STATUS
                    FROM 
                        Aquila_Van_Loading_Details d
                    INNER JOIN 
                        Aquila_Van_Loading_Transaction t ON t.LOADING_ID = d.TRANSACTION_ID
                    WHERE 
                        t.COMPANY_ID = ? AND t.SITE_ID = ? AND t.DATE_CREATED BETWEEN ? AND ? AND t.STATUS != 'DRAFT'
                    ORDER BY t.DATE_CREATED DESC";

        $dataStmt = $conn->prepare($dataSql);
        $dataStmt->execute([$companyId, $siteid, $datefrom, $dateto]);

        // Output CSV
        $output = fopen('php://output', 'w');

        // CSV header row
        fputcsv($output, [
            'COMPANY_ID', 'SITE_ID', 'SELLER_ID', 'DATE_CREATED', 'TRANSACTION_ID', 
            'BARCODE', 'ITEM_CODE', 'DESCRIPTION', 'BATCH', 'CS', 'SW', 'IT', 
            'PRICE', 'ITEM_PER_CASE', 'ITEM_PER_SW', 'SIH_IT', 'TOTAL_CS_AMOUNT', 
            'TOTAL_IT_AMOUNT', 'STATUS'
        ]);

        // Fetch rows and output
        while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            // Force ITEM_CODE as text for Excel
            $row['ITEM_CODE'] = '="' . $row['ITEM_CODE'] . '"';
            fputcsv($output, $row);
        }

        fclose($output);
        exit();

    } elseif ($action === 'loadagents') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';

        // Query 1: Agent Details

        // Query 2: Agent Performance Summary
        $stmt = $conn->prepare("SELECT 
                                    COMPANY_ID, SITE_ID, AGENT_ID, USERNAME, DELIVERY_DATE, ENTRY_BAT_PERCENTAGE, EXIT_BAT_PERCENTAGE,
                                    TIME_ENTRY, TIME_EXIT, STATUS, LOGIN_ID, TIME_SPENT
                                FROM Dash_Agent_Performance_Summary
                                WHERE 
                                    COMPANY_ID = :companyid
                                    AND DELIVERY_DATE BETWEEN :datefrom AND :dateto
                                    AND STATUS = 'COMPLETE'
                                GROUP BY 
                                    COMPANY_ID, SITE_ID, AGENT_ID, USERNAME, DELIVERY_DATE, ENTRY_BAT_PERCENTAGE, EXIT_BAT_PERCENTAGE,
                                    TIME_ENTRY, TIME_EXIT, STATUS, LOGIN_ID, TIME_SPENT");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // Query 4: Delivery Summary
        $stmt = $conn->prepare("SELECT 
                                    [DISTRIBUTOR_CODE], [BRANCH_CODE], [BRANCH], ORDER_DATE, [DATE] AS INVOICE_DATE,
                                    Dash_Plan_Batch_Details.DATE_TO_DELIVER AS DATE_DELIVERED, [SALES_REP], [SELLER_NAME], AGENT_ID, BATCH,
                                    Dash_Plan_Batch_Details.STATUS,
                                    CASE WHEN SUM(ISNULL([RETURN_AMOUNT], 0)) = 0 THEN 'NO' ELSE 'YES' END AS HAS_RETURN,
                                    PRFR_Invoice_Detailed.CUSTOMER_ID, PRFR_Invoice_Detailed.CUSTOMER_NAME,
                                    [DOCUMENT_NUMBER], SUM([SALES_AMOUNT]) AS TOTAL,
                                    PG_LOCAL_SUBSEGMENT,
                                    MAX(Dash_Agent_Performance_Detailed.STORE_ENTRY) AS STORE_ENTRY,
                                    MAX(Dash_Agent_Performance_Detailed.STORE_EXIT) AS STORE_EXIT,
                                    MAX(Dash_Agent_Performance_Detailed.STORE_TIME_SPENT) AS STORE_TIME_SPENT
                                FROM [dbo].[PRFR_Invoice_Detailed]
                                LEFT JOIN Dash_Plan_Batch_Details ON Dash_Plan_Batch_Details.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Plan_Batch_Details.COMPANY_ID
                                LEFT JOIN Dash_Returns ON Dash_Returns.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Returns.COMPANY_ID AND Dash_Returns.IT_BARCODE = PRFR_Invoice_Detailed.IT_BARCODE
                                LEFT JOIN Dash_Agent_Performance_Detailed ON Dash_Agent_Performance_Detailed.DELIVERY_DATE = Dash_Plan_Batch_Details.DATE_TO_DELIVER AND Dash_Agent_Performance_Detailed.STORE_CODE = PRFR_Invoice_Detailed.CUSTOMER_ID AND Dash_Agent_Performance_Detailed.COMPANY_ID = PRFR_Invoice_Detailed.DISTRIBUTOR_CODE
                                WHERE Dash_Plan_Batch_Details.COMPANY_ID = :companyid AND Dash_Plan_Batch_Details.DATE_TO_DELIVER BETWEEN :datefrom AND :dateto
                                GROUP BY [DISTRIBUTOR_CODE], [BRANCH_CODE], [BRANCH], ORDER_DATE, [DATE], Dash_Plan_Batch_Details.DATE_TO_DELIVER,
                                [SALES_REP], [SELLER_NAME], AGENT_ID, BATCH, Dash_Plan_Batch_Details.STATUS, 
                                PRFR_Invoice_Detailed.CUSTOMER_ID, PRFR_Invoice_Detailed.CUSTOMER_NAME, [DOCUMENT_NUMBER], PG_LOCAL_SUBSEGMENT");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Output all data as JSON
        echo json_encode([
            "Agent Performance Summary" => $warehouses,
            "Delivery Result Summary" => $summary,
        ], JSON_UNESCAPED_UNICODE);
        exit();
        


        } elseif ($action === 'loadagentsdetailed') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';

        // Query 1: Agent Details
        $stmt = $conn->prepare("SELECT 
                                    COMPANY_ID, SITE_ID, -- Now from Dash_Customer_Master (c)
                                    SITE_NAME, DATE_TO_DELIVER, AGENT, STORE_ENTRY, STORE_EXIT, STORE_TIME_SPENT,
                                    CUSTOMER_ID, CUSTOMER_NAME, PHONE, ADDRESS, IMAGE1, LATITUDE, LONGITUDE, STATUS,
                                    SUB_BATCH, SUB_DA, IS_RECEIVED, VEHICLE_IDS, IS_DROP_STATUS
                                FROM (
                                    SELECT 
                                        b.COMPANY_ID,
                                        a.SITE_ID,
                                        Dash_Sites.SITE_NAME,
                                        b.DATE_TO_DELIVER,
                                        a.AGENT,
                                        d.STORE_ENTRY,
                                        d.STORE_EXIT,
                                        d.STORE_TIME_SPENT,
                                        b.CUSTOMER_ID,
                                        b.CUSTOMER_NAME,
                                        c.PHONE,
                                        c.ADDRESS,
                                        c.IMAGE1,
                                        c.LATITUDE,
                                        c.LONGITUDE,
                                        b.STATUS,
                                        b.SUB_BATCH,
                                        b.SUB_DA,
                                        b.IS_RECEIVED,
                                        b.VEHICLE_IDS,
                                        b.IS_DROP_STATUS,
                                        ROW_NUMBER() OVER (PARTITION BY b.CUSTOMER_ID, b.COMPANY_ID ORDER BY d.STORE_EXIT DESC) AS rn
                                    FROM Dash_Plan_Batch_Transaction a
                                    JOIN Dash_Plan_Batch_Details b ON a.BATCH_ID = b.BATCH AND a.COMPANY_ID = b.COMPANY_ID
                                    LEFT JOIN Dash_Customer_Master c ON c.CODE = b.CUSTOMER_ID AND c.COMPANY_ID = b.COMPANY_ID
                                    LEFT JOIN Dash_Agent_Performance_Detailed d ON b.CUSTOMER_ID = d.STORE_CODE AND b.DATE_TO_DELIVER = d.DELIVERY_DATE AND b.COMPANY_ID = d.COMPANY_ID
                                    LEFT JOIN Dash_Sites ON Dash_Sites.SITE_ID = a.SITE_ID
                                    WHERE b.DATE_TO_DELIVER BETWEEN :datefrom AND :dateto
                                      AND a.STATUS = 'PROCESSED'
                                      AND b.COMPANY_ID = :companyid
                                ) AS subquery
                                WHERE rn = 1;");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query 2: Agent Performance Summary
        $stmt = $conn->prepare("SELECT 
                                    COMPANY_ID, SITE_ID, AGENT_ID, USERNAME, DELIVERY_DATE, ENTRY_BAT_PERCENTAGE, EXIT_BAT_PERCENTAGE,
                                    TIME_ENTRY, TIME_EXIT, STATUS, LOGIN_ID, TIME_SPENT
                                FROM Dash_Agent_Performance_Summary
                                WHERE 
                                    COMPANY_ID = :companyid
                                    AND DELIVERY_DATE BETWEEN :datefrom AND :dateto
                                    AND STATUS = 'COMPLETE'
                                GROUP BY 
                                    COMPANY_ID, SITE_ID, AGENT_ID, USERNAME, DELIVERY_DATE, ENTRY_BAT_PERCENTAGE, EXIT_BAT_PERCENTAGE,
                                    TIME_ENTRY, TIME_EXIT, STATUS, LOGIN_ID, TIME_SPENT");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query 3: Vehicles Data
        $stmt = $conn->prepare("SELECT 
                                    [DISTRIBUTOR_CODE], [BRANCH_CODE], [BRANCH], ORDER_DATE, [DATE] AS INVOICE_DATE,
                                    DATE_TO_DELIVER AS DATE_DELIVERED, [SALES_REP], [SELLER_NAME], AGENT_ID, BATCH,
                                    Dash_Plan_Batch_Details.STATUS,
                                    CASE WHEN ISNULL([RETURN_AMOUNT], 0) = 0 THEN 'NO' ELSE 'YES' END AS HAS_RETURN,
                                    PRFR_Invoice_Detailed.CUSTOMER_ID, PRFR_Invoice_Detailed.CUSTOMER_NAME,
                                    [NAME] AS ITEM_ID, [SCHEME_CODE], [SCHEME_SLAB_DESCRIPTION], [SCHEME_GROUP_NAME],
                                    PRFR_Invoice_Detailed.IT_BARCODE, [SW_BARCODE], [DESCRIPTION], [BRAND], [ITEM_CATEGORY],
                                    [BRANDFORM], [TRADE_CHANNEL], [DOCUMENT_NUMBER], [CS], [AMOUNT], [DISCOUNT_VALUE],
                                    [SCHEME_VALUE], [SALES_EX_VAT], [VAT_AMOUNT], [SALES_AMOUNT],
                                    ISNULL([QTY_RETURN], 0) AS QTY_RETURN,
                                    ISNULL([RETURN_AMOUNT], 0) AS RETURN_AMOUNT,
                                    [MONTHLY_TRANSACTION], [PG_LOCAL_SUBSEGMENT], [SALES_SUPERVISOR],
                                    [ITEM_QTY], [GIV], [NIV], [ITEM_QTY_CS], [ITEM_QTY_SW], [ITEM_QTY_IT]
                                FROM [dbo].[PRFR_Invoice_Detailed]
                                LEFT JOIN Dash_Plan_Batch_Details ON Dash_Plan_Batch_Details.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Plan_Batch_Details.COMPANY_ID
                                LEFT JOIN Dash_Returns ON Dash_Returns.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Returns.COMPANY_ID AND Dash_Returns.IT_BARCODE = PRFR_Invoice_Detailed.IT_BARCODE
                                WHERE Dash_Plan_Batch_Details.COMPANY_ID = :companyid AND DATE_TO_DELIVER BETWEEN :datefrom AND :dateto");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query 4: Delivery Summary
        $stmt = $conn->prepare("SELECT 
                                    [DISTRIBUTOR_CODE], [BRANCH_CODE], [BRANCH], ORDER_DATE, [DATE] AS INVOICE_DATE,
                                    Dash_Plan_Batch_Details.DATE_TO_DELIVER AS DATE_DELIVERED, [SALES_REP], [SELLER_NAME], AGENT_ID, BATCH,
                                    Dash_Plan_Batch_Details.STATUS,
                                    CASE WHEN SUM(ISNULL([RETURN_AMOUNT], 0)) = 0 THEN 'NO' ELSE 'YES' END AS HAS_RETURN,
                                    PRFR_Invoice_Detailed.CUSTOMER_ID, PRFR_Invoice_Detailed.CUSTOMER_NAME,
                                    [DOCUMENT_NUMBER], SUM([SALES_AMOUNT]) AS TOTAL,
                                    PG_LOCAL_SUBSEGMENT,
                                    MAX(Dash_Agent_Performance_Detailed.STORE_ENTRY) AS STORE_ENTRY,
                                    MAX(Dash_Agent_Performance_Detailed.STORE_EXIT) AS STORE_EXIT,
                                    MAX(Dash_Agent_Performance_Detailed.STORE_TIME_SPENT) AS STORE_TIME_SPENT
                                FROM [dbo].[PRFR_Invoice_Detailed]
                                LEFT JOIN Dash_Plan_Batch_Details ON Dash_Plan_Batch_Details.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Plan_Batch_Details.COMPANY_ID
                                LEFT JOIN Dash_Returns ON Dash_Returns.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Returns.COMPANY_ID AND Dash_Returns.IT_BARCODE = PRFR_Invoice_Detailed.IT_BARCODE
                                LEFT JOIN Dash_Agent_Performance_Detailed ON Dash_Agent_Performance_Detailed.DELIVERY_DATE = Dash_Plan_Batch_Details.DATE_TO_DELIVER AND Dash_Agent_Performance_Detailed.STORE_CODE = PRFR_Invoice_Detailed.CUSTOMER_ID AND Dash_Agent_Performance_Detailed.COMPANY_ID = PRFR_Invoice_Detailed.DISTRIBUTOR_CODE
                                WHERE Dash_Plan_Batch_Details.COMPANY_ID = :companyid AND Dash_Plan_Batch_Details.DATE_TO_DELIVER BETWEEN :datefrom AND :dateto
                                GROUP BY [DISTRIBUTOR_CODE], [BRANCH_CODE], [BRANCH], ORDER_DATE, [DATE], Dash_Plan_Batch_Details.DATE_TO_DELIVER,
                                [SALES_REP], [SELLER_NAME], AGENT_ID, BATCH, Dash_Plan_Batch_Details.STATUS, 
                                PRFR_Invoice_Detailed.CUSTOMER_ID, PRFR_Invoice_Detailed.CUSTOMER_NAME, [DOCUMENT_NUMBER], PG_LOCAL_SUBSEGMENT");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Output all data as JSON
        echo json_encode([
            "Agent Performance Detailed" => $agents,
            "Delivery Result Detailed" => $stores,
        ], JSON_UNESCAPED_UNICODE);
        exit();
        }

// so report

   elseif ($action === 'soreport') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';
    
        // Query 5: SO Report
        $stmt = $conn->prepare("SELECT 
                                    [COMPANY_ID], [SITE_ID], [UPLOAD_BY_USER_ID], [DIST_NAME], [BRANCH_NAME], [SELLER_TYPE], [SELLER_NAME], 
                                    [CUSTOMER_NAME], [STORE_CODE], [CHANNEL_NAME], [SUB_CHANNEL_NAME], [ORDER_DATE], [ORDER_ID],
                                    [PRD_SKU_CODE], [PRD_SKU_NAME], [BARCODE], [CS_QTY], [QTY_PIECE], [PRICE_PIECE], [SCHEME_CODE],
                                    [SCHEME_DESC], [ORDER_VALUE_WITHOUTSCHEME], [SCHEME_VALUE], [ORDER_VALUE], [ORDER_SOURCE], [IS_PLAN]
                                FROM [dbo].[PRFR_SO_UPLOAD]
                                WHERE COMPANY_ID = :companyid AND ORDER_DATE BETWEEN :datefrom AND :dateto");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $soreport = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Output all data as JSON
        echo json_encode([
            "SO Report" => $soreport
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    

    elseif ($action === 'deliveryplan' && $isall === 'true'  ) {
    $companyid = $_GET['companyid'] ?? '';
    $siteid    = $_GET['siteid'] ?? '';
    $datefrom  = $_GET['datefrom'] ?? '';
    $dateto    = $_GET['dateto'] ?? '';

    $stmt = $conn->prepare("SELECT 
                Dash_Plan_Batch_Details.COMPANY_ID,
                Dash_Plan_Batch_Details.SITE_ID,
                [BATCH],
                [INVOICE_NUMBER],
                [TOTAL_AMOUNT],
                [INVOICE_VOLUME],
                [DISTANCE],
                [DISTANCE_IN_DECIMAL],
                Dash_Plan_Batch_Details.STATUS,
                Dash_Plan_Batch_Details.DATE_TO_DELIVER,
                [STORE_LAT],
                [STORE_LONG],
                [CUSTOMER_ID],
                [CUSTOMER_NAME],
                [AGENT_ID],
                VEHICLE_ID
        FROM [dbo].[Dash_Plan_Batch_Details]
        LEFT JOIN Dash_Plan_Batch_Transaction 
            ON Dash_Plan_Batch_Transaction.BATCH_ID = Dash_Plan_Batch_Details.BATCH
        WHERE Dash_Plan_Batch_Details.COMPANY_ID = :companyid 
          AND Dash_Plan_Batch_Details.DATE_TO_DELIVER BETWEEN :datefrom AND :dateto");

    $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Delivery_Plan.csv"');

    $out = fopen('php://output', 'w');
    if (!empty($rows)) {
        // write header row
        fputcsv($out, array_keys($rows[0]));
        // write data rows
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit();
}


 elseif ($action === 'deliveryplan' && $isall === 'false'  ) {
    $companyid = $_GET['companyid'] ?? '';
    $siteid    = $_GET['siteid'] ?? '';
    $datefrom  = $_GET['datefrom'] ?? '';
    $dateto    = $_GET['dateto'] ?? '';

    $stmt = $conn->prepare("SELECT 
                Dash_Plan_Batch_Details.COMPANY_ID,
                Dash_Plan_Batch_Details.SITE_ID,
                [BATCH],
                [INVOICE_NUMBER],
                [TOTAL_AMOUNT],
                [INVOICE_VOLUME],
                [DISTANCE],
                [DISTANCE_IN_DECIMAL],
                Dash_Plan_Batch_Details.STATUS,
                Dash_Plan_Batch_Details.DATE_TO_DELIVER,
                [STORE_LAT],
                [STORE_LONG],
                [CUSTOMER_ID],
                [CUSTOMER_NAME],
                [AGENT_ID],
                VEHICLE_ID
        FROM [dbo].[Dash_Plan_Batch_Details]
        LEFT JOIN Dash_Plan_Batch_Transaction 
            ON Dash_Plan_Batch_Transaction.BATCH_ID = Dash_Plan_Batch_Details.BATCH
        WHERE Dash_Plan_Batch_Details.COMPANY_ID = :companyid 
        AND Dash_Plan_Batch_Details.SITE_ID = :siteid 
          AND Dash_Plan_Batch_Details.DATE_TO_DELIVER BETWEEN :datefrom AND :dateto");

    $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid,':siteid' => $siteid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Delivery_Plan.csv"');

    $out = fopen('php://output', 'w');
    if (!empty($rows)) {
        // write header row
        fputcsv($out, array_keys($rows[0]));
        // write data rows
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit();
}

     elseif ($action === 'result') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';

        // Query 1: Agent Details
        $stmt = $conn->prepare("SELECT 
                        [DISTRIBUTOR_CODE],
                        [BRANCH_CODE],
                        [BRANCH],
                        ORDER_DATE,
                        [DATE] AS INVOICE_DATE,
                        Dash_Plan_Batch_Details.DATE_TO_DELIVER AS DATE_DELIVERED,
                        [SALES_REP],
                        [SELLER_NAME],
                        AGENT_ID,
                        BATCH,
                        Dash_Plan_Batch_Details.STATUS,
                        CASE 
                            WHEN SUM(ISNULL([RETURN_AMOUNT], 0)) = 0 THEN 'NO' 
                            ELSE 'YES' 
                        END AS HAS_RETURN,
                        PRFR_Invoice_Detailed.CUSTOMER_ID,
                        PRFR_Invoice_Detailed.CUSTOMER_NAME,
                        [DOCUMENT_NUMBER],
                        SUM([SALES_AMOUNT]) AS TOTAL,
                        PG_LOCAL_SUBSEGMENT,
                        MAX(Dash_Agent_Performance_Detailed.STORE_ENTRY) AS STORE_ENTRY,
                        MAX(Dash_Agent_Performance_Detailed.STORE_EXIT) AS STORE_EXIT,
                        MAX(Dash_Agent_Performance_Detailed.STORE_TIME_SPENT) AS STORE_TIME_SPENT
                    FROM [dbo].[PRFR_Invoice_Detailed]
                    LEFT JOIN Dash_Plan_Batch_Details 
                        ON Dash_Plan_Batch_Details.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER 
                        AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Plan_Batch_Details.COMPANY_ID
                    LEFT JOIN Dash_Returns 
                        ON Dash_Returns.INVOICE_NUMBER = PRFR_Invoice_Detailed.DOCUMENT_NUMBER 
                        AND PRFR_Invoice_Detailed.DISTRIBUTOR_CODE = Dash_Returns.COMPANY_ID 
                        AND Dash_Returns.IT_BARCODE = PRFR_Invoice_Detailed.IT_BARCODE
                    LEFT JOIN Dash_Agent_Performance_Detailed
                        ON Dash_Agent_Performance_Detailed.DELIVERY_DATE = Dash_Plan_Batch_Details.DATE_TO_DELIVER
                        AND Dash_Agent_Performance_Detailed.STORE_CODE = PRFR_Invoice_Detailed.CUSTOMER_ID
                        AND Dash_Agent_Performance_Detailed.COMPANY_ID = PRFR_Invoice_Detailed.DISTRIBUTOR_CODE
                    WHERE Dash_Plan_Batch_Details.COMPANY_ID = :companyid
                        AND Dash_Plan_Batch_Details.DATE_TO_DELIVER BETWEEN :datefrom AND :dateto
                    GROUP BY 
                        [DISTRIBUTOR_CODE],
                        [BRANCH_CODE],
                        [BRANCH],
                        ORDER_DATE,
                        [DATE],
                        Dash_Plan_Batch_Details.DATE_TO_DELIVER,
                        [SALES_REP],
                        [SELLER_NAME],
                        AGENT_ID,
                        BATCH,
                        Dash_Plan_Batch_Details.STATUS,
                        PRFR_Invoice_Detailed.CUSTOMER_ID,
                        PRFR_Invoice_Detailed.CUSTOMER_NAME,
                        [DOCUMENT_NUMBER],
                        PG_LOCAL_SUBSEGMENT");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query 2: Agent Performance Summary
    
        // Output all data as JSON
        echo json_encode([
            "Details" => $agents,

        ], JSON_UNESCAPED_UNICODE);
        exit();
    
        }

     elseif ($action === 'orderprep' && $isall === 'true') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';

        // Query 1: Agent Details
        $stmt = $conn->prepare("SELECT Dash_SO_Plan_Batch_Details.COMPANY_ID, 
                      Dash_SO_Plan_Batch_Details.SITE_ID,
                      Dash_SO_Plan_Batch_Details.SO_PLAN_NUMBER,
                      [SO_NUMBER],
                      VEHICLE_ID,
                      SO_PICK_BATCH,
                      [CUSTOMER_ID],
                      [CUSTOMER_NAME],
                      [TOTAL_AMOUNT],
                      [STORE_LAT],
                      [STORE_LONG],
                      [ORDER_DATE],
                      Dash_SO_Plan_Batch_Details.STATUS,
                      [SUB_BATCH],
                      [SUB_DA],
                      [VEHICLE_IDS]
               FROM [dbo].[Dash_SO_Plan_Batch_Details] 
               LEFT JOIN Dash_SO_Plan_Transaction 
               ON Dash_SO_Plan_Transaction.SO_PLAN_NUMBER = Dash_SO_Plan_Batch_Details.SO_PLAN_NUMBER 
               WHERE Dash_SO_Plan_Batch_Details.COMPANY_ID = :companyid
               AND ORDER_DATE BETWEEN :datefrom AND :dateto 
               AND Dash_SO_Plan_Batch_Details.STATUS != 'NEW'");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query 2: Agent Performance Summary
    
        // Output all data as JSON
        echo json_encode([
            "Details" => $agents,

        ], JSON_UNESCAPED_UNICODE);
        exit();
     }

     elseif ($action === 'orderprep' && $isall === 'false') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';

        // Query 1: Agent Details
        $stmt = $conn->prepare("SELECT Dash_SO_Plan_Batch_Details.COMPANY_ID, 
                      Dash_SO_Plan_Batch_Details.SITE_ID,
                      Dash_SO_Plan_Batch_Details.SO_PLAN_NUMBER,
                      [SO_NUMBER],
                      VEHICLE_ID,
                      SO_PICK_BATCH,
                      [CUSTOMER_ID],
                      [CUSTOMER_NAME],
                      [TOTAL_AMOUNT],
                      [STORE_LAT],
                      [STORE_LONG],
                      [ORDER_DATE],
                      Dash_SO_Plan_Batch_Details.STATUS,
                      [SUB_BATCH],
                      [SUB_DA],
                      [VEHICLE_IDS]
               FROM [dbo].[Dash_SO_Plan_Batch_Details] 
               LEFT JOIN Dash_SO_Plan_Transaction 
               ON Dash_SO_Plan_Transaction.SO_PLAN_NUMBER = Dash_SO_Plan_Batch_Details.SO_PLAN_NUMBER 
               WHERE Dash_SO_Plan_Batch_Details.COMPANY_ID = :companyid
               AND Dash_SO_Plan_Batch_Details.SITE_ID = :siteid
               AND ORDER_DATE BETWEEN :datefrom AND :dateto 
               AND Dash_SO_Plan_Batch_Details.STATUS != 'NEW'");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid , ':siteid' => $siteid]);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query 2: Agent Performance Summary
    
        // Output all data as JSON
        echo json_encode([
            "Details" => $agents,

        ], JSON_UNESCAPED_UNICODE);
        exit();
     }

      elseif ($action === 'freight') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';

        // Query 1: Agent Details
        $stmt = $conn->prepare("SELECT 
        S.SITE_NAME,
        D.SITE_ID,
        AVG(D.DailyTruckCount) AS Average_Trucks_Used_Per_Day,
        AVG(D.AverageInvoiceDrops) AS Average_Invoice_Drops_Per_Day,
        CONVERT(VARCHAR(8), DATEADD(SECOND, AVG(DATEDIFF(SECOND, 0, TRY_CONVERT(time, A.TIME_ENTRY))), 0), 108) AS Average_Market_Entry_Time,
        CONVERT(VARCHAR(8), DATEADD(SECOND, AVG(DATEDIFF(SECOND, 0, TRY_CONVERT(time, A.TIME_EXIT))), 0), 108) AS Average_Market_Exit_Time
    FROM (
        SELECT 
            SITE_ID,
            CAST([ORDER_DATE] AS DATE) AS DeliveryDate,
            COUNT(DISTINCT VEHICLE_ID) AS DailyTruckCount,
            SUM(NUM_OF_INVOICES) AS AverageInvoiceDrops,
            COMPANY_ID
        FROM [dbo].[Dash_Plan_Batch_Transaction]
        WHERE ORDER_DATE BETWEEN :datefrom AND :dateto
          AND VEHICLE_ID IS NOT NULL
          AND STATUS = 'PROCESSED'
        GROUP BY SITE_ID, CAST([ORDER_DATE] AS DATE), COMPANY_ID
    ) AS D
    JOIN [dbo].[Dash_Sites] AS S ON D.SITE_ID = S.SITE_ID
    LEFT JOIN [dbo].[Dash_Agent_Performance_Summary] AS A 
        ON D.SITE_ID = A.SITE_ID AND D.DeliveryDate = A.DELIVERY_DATE
    WHERE 
        TRY_CONVERT(time, A.TIME_ENTRY) IS NOT NULL
        AND TRY_CONVERT(time, A.TIME_EXIT) IS NOT NULL
        AND D.COMPANY_ID = :companyid
    GROUP BY D.SITE_ID, S.SITE_NAME
    ORDER BY S.SITE_NAME;");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query 2: Agent Performance Summary
    
        $stmt = $conn->prepare("SELECT 
                        S.SITE_NAME,
                        D.SITE_ID,
                        D.DeliveryDate,
                        D.DailyTruckCount AS Trucks_Used_On_Day,
                        D.AverageInvoiceDrops AS Invoice_Drops_On_Day,
                        CONVERT(VARCHAR(8), DATEADD(SECOND, AVG(DATEDIFF(SECOND, 0, TRY_CONVERT(time, A.TIME_ENTRY))), 0), 108) AS Average_Market_Entry_Time,
                        CONVERT(VARCHAR(8), DATEADD(SECOND, AVG(DATEDIFF(SECOND, 0, TRY_CONVERT(time, A.TIME_EXIT))), 0), 108) AS Average_Market_Exit_Time
                    FROM (
                        SELECT 
                            SITE_ID,
                            CAST([ORDER_DATE] AS DATE) AS DeliveryDate,
                            COUNT(DISTINCT VEHICLE_ID) AS DailyTruckCount,
                            SUM(NUM_OF_INVOICES) AS AverageInvoiceDrops,
                            COMPANY_ID
                        FROM [dbo].[Dash_Plan_Batch_Transaction]
                        WHERE ORDER_DATE BETWEEN :datefrom AND :dateto
                          AND VEHICLE_ID IS NOT NULL
                          AND STATUS = 'PROCESSED'
                        GROUP BY SITE_ID, CAST([ORDER_DATE] AS DATE), COMPANY_ID
                    ) AS D
                    JOIN [dbo].[Dash_Sites] AS S ON D.SITE_ID = S.SITE_ID
                    LEFT JOIN [dbo].[Dash_Agent_Performance_Summary] AS A 
                        ON D.SITE_ID = A.SITE_ID AND D.DeliveryDate = A.DELIVERY_DATE
                    WHERE 
                        TRY_CONVERT(time, A.TIME_ENTRY) IS NOT NULL
                        AND TRY_CONVERT(time, A.TIME_EXIT) IS NOT NULL
                        AND D.COMPANY_ID = :companyid
                    GROUP BY 
                        D.SITE_ID, 
                        S.SITE_NAME,
                        D.DeliveryDate,
                        D.DailyTruckCount,
                        D.AverageInvoiceDrops
                    ORDER BY 
                      
                        D.DeliveryDate;");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Output all data as JSON
        echo json_encode([
            "Summary" => $agents,
            "Details" => $details,
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

        elseif ($action === 'crossdock') {
        // Load agent data and output JSON
        header('Content-Type: application/json; charset=utf-8');

        $companyid = $_GET['companyid'] ?? '';
        $siteid = $_GET['siteid'] ?? '';
        $datefrom = $_GET['datefrom'] ?? '';
        $dateto = $_GET['dateto'] ?? '';

        // Query 1: Agent Details


        // Query 2: Agent Performance Summary
    
        $stmt = $conn->prepare("WITH OrderedPoints AS (
                                SELECT
                                    AGENT_ID,
                                    DELIVERY_DATE,
                                    LAT_CAPTURED,
                                    LONG_CAPTURED,
                                    TIME_STAMP,
                                    ROW_NUMBER() OVER (PARTITION BY AGENT_ID, DELIVERY_DATE ORDER BY TIME_STAMP) AS rn
                                FROM [dbo].[Dash_Agent_Time_Stamp]
                            ),
                            
                            DistancePairs AS (
                                SELECT
                                    a.AGENT_ID,
                                    a.DELIVERY_DATE,
                                    geography::Point(a.LAT_CAPTURED, a.LONG_CAPTURED, 4326) AS PointA,
                                    geography::Point(b.LAT_CAPTURED, b.LONG_CAPTURED, 4326) AS PointB
                                FROM OrderedPoints a
                                INNER JOIN OrderedPoints b 
                                    ON a.AGENT_ID = b.AGENT_ID
                                    AND a.DELIVERY_DATE = b.DELIVERY_DATE
                                    AND a.rn = b.rn - 1
                            ),
                            
                            TotalDistances AS (
                                SELECT
                                    AGENT_ID,
                                    DELIVERY_DATE,
                                    ROUND(SUM(PointA.STDistance(PointB)) / 1000.0, 2) AS TotalDistanceKm  -- total distance in kilometers
                                FROM DistancePairs
                                GROUP BY AGENT_ID, DELIVERY_DATE
                            )
                            
                            SELECT 
                                xd.[COMPANY_ID],
                                xd.[SITE_ID],
                                xd.[BATCH],
                                xd.[DELIVERY_AMOUNT],
                                xd.[AGENT],
                                xd.[VEHICLE],
                                xd.[TRANSACTION_DATE],
                            
                                CONVERT(varchar(8), xd.[WH_ENTRY], 108) AS WAREHOUSE_ENTRY,
                                CONVERT(varchar(8), xd.[DEPARTURE_TIME], 108) AS DEPARTURE_TIME,
                                CONVERT(varchar(8), xd.[ARRIVAL_TIME], 108) AS CROSS_DOCK_ARRIVAL_TIME,
                                CONVERT(varchar(8), xd.[XD_EXIT], 108) AS CROSS_DOCK_EXIT,
                                CONVERT(varchar(8), xd.[WH_RETURN], 108) AS WAREHOUSE_RETURN_TIME,
                            
                                td.TotalDistanceKm AS DISTANCE_TRAVELLED_IN_KM,
                            
                                ROUND(
                                    td.TotalDistanceKm * ISNULL(v.CONSUMPTION_PER_100_METERS, 0) / 100, 
                                    2
                                ) AS FUEL_CONSUMED_IN_LITER
                            
                            FROM [dbo].[Dash_XDock_Status] xd
                            LEFT JOIN TotalDistances td 
                                ON xd.AGENT = td.AGENT_ID 
                                AND xd.TRANSACTION_DATE = td.DELIVERY_DATE
                            LEFT JOIN [dbo].[Dash_Vehicles] v
                                ON xd.VEHICLE = v.PLATE_NUM
                            
                            WHERE xd.TRANSACTION_DATE BETWEEN :datefrom AND :dateto AND  xd.[COMPANY_ID] = :companyid
                            
                            ORDER BY xd.AGENT, xd.TRANSACTION_DATE;");
        $stmt->execute([':datefrom' => $datefrom, ':dateto' => $dateto, ':companyid' => $companyid]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Output all data as JSON
        echo json_encode([
            "Details" => $details,
        ], JSON_UNESCAPED_UNICODE);
        exit();

    } else {
        // Invalid or missing action
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No valid action']);
        exit();
    }
} catch (Exception $e) {
    // Handle exceptions
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
?>