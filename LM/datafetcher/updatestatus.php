<?php
session_start();
include __DIR__ . '/../../DB/dbcon.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* ====================== LOAD DEVICES ====================== */
if ($action === 'loaddevice') {
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $companyId = $_GET['company'] ?? '';
        if (empty($companyId)) {
            echo json_encode(['error' => 'Company ID is required']);
            exit();
        }

        $sql = "
            SELECT d.*, COALESCE(d.STATUS, 'ACTIVE') AS STATUS
            FROM BS_Device d
            WHERE d.COMPANY_ID = :companyid
            ORDER BY d.SITE_ID ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':companyid', $companyId);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($items ?: []);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    exit();
}

/* ====================== UPDATE DEVICE (Full Edit) ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['Company_ID'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if this is a status toggle operation (has 'is_active' field)
    if (isset($input['is_active'])) {
        $lineid = $input['lineid'] ?? null;
        $newStatus = isset($input['is_active']) ? (int)$input['is_active'] : null;

        if (!$lineid || !in_array($newStatus, [0, 1])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        try {
            $statusValue = $newStatus === 1 ? 'ACTIVE' : 'INACTIVE';

            $stmt = $conn->prepare("UPDATE BS_Device SET STATUS = :status WHERE LINEID = :lineid");
            $stmt->execute([
                ':status' => $statusValue,
                ':lineid' => $lineid
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Device status updated successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Check if this is a full device update (has 'STATUS_TEXT' field or multiple fields)
    if (isset($input['lineid']) && (isset($input['STATUS_TEXT']) || isset($input['SITE_ID']))) {
        $lineid = $input['lineid'] ?? null;
        
        if (!$lineid) {
            echo json_encode(['success' => false, 'message' => 'Device ID is required']);
            exit;
        }
        
        try {
            // Map STATUS_TEXT to numeric or text status
            $statusText = $input['STATUS_TEXT'] ?? 'ACTIVE';
            $statusValue = ($statusText === 'ACTIVE') ? 'ACTIVE' : 'INACTIVE';
            
            $sql = "UPDATE BS_Device SET 
                        SITE_ID = :site_id,
                        DEPARTMENT = :department,
                        PRINCIPAL = :principal,
                        POSITION = :position,
                        BRAND = :brand,
                        MODEL = :model,
                        IMEI = :imei,
                        SERIAL = :serial,
                        DATE_DEPLOYED = :date_deployed,
                        PERSON_USING = :person_using,
                        NUMBER = :number,
                        BALANCE = :balance,
                        LOAD_TERMS = :load_terms,
                        DATA_BALANCE_MIN = :data_balance_min,
                        REMARKS = :remarks,
                        STATUS = :status
                    WHERE LINEID = :lineid";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':site_id' => $input['SITE_ID'] ?? '',
                ':department' => $input['DEPARTMENT'] ?? '',
                ':principal' => $input['PRINCIPAL'] ?? '',
                ':position' => $input['POSITION'] ?? '',
                ':brand' => $input['BRAND'] ?? '',
                ':model' => $input['MODEL'] ?? '',
                ':imei' => $input['IMEI'] ?? '',
                ':serial' => $input['SERIAL'] ?? '',
                ':date_deployed' => $input['DATE_DEPLOYED'] ?? null,
                ':person_using' => $input['PERSON_USING'] ?? '',
                ':number' => $input['NUMBER'] ?? '',
                ':balance' => $input['BALANCE'] ?? 0,
                ':load_terms' => $input['LOAD_TERMS'] ?? 1,
                ':data_balance_min' => $input['DATA_BALANCE_MIN'] ?? 0,
                ':remarks' => $input['REMARKS'] ?? '',
                ':status' => $statusValue,
                ':lineid' => $lineid
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Device updated successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // If none of the above conditions matched
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

// Default response for GET requests without action
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>