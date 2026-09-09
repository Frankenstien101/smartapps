<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: /TBC/login.php");
    exit();
}

require_once __DIR__ . '/../DB/dbcon.php';

$message = '';
$messageType = '';
$selectedSite = $_SESSION['SITE'] ?? '';
$selectedPrincipal = $_SESSION['PRINCIPAL'] ?? '';

// ============================================
// SAVE CUSTOMER
// ============================================

if (isset($_POST['save_customer'])) {

    try {

        $sql = "
        INSERT INTO [TBC].[dbo].[customers]
        (
            SITE,
            PRINCIPAL,
            SELLER_ID,
            SELLER_NAME,
            CUSTOMER_ID,
            CUSTOMER_NAME,
            ADDRESS,
            PHONE_NUMBER
        )
        VALUES
        (
            :site,
            :principal,
            :seller_id,
            :seller_name,
            :customer_id,
            :customer_name,
            :address,
            :phone_number
        )
        ";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':site' => $selectedSite,
            ':principal' => $selectedPrincipal,
            ':seller_id' => $_POST['seller_id'],
            ':seller_name' => $_POST['seller_name'],
            ':customer_id' => $_POST['customer_id'],
            ':customer_name' => $_POST['customer_name'],
            ':address' => $_POST['address'],
            ':phone_number' => $_POST['phone_number']
        ]);

        $message = "Customer added successfully!";
        $messageType = "success";

    } catch (Exception $e) {

        $message = $e->getMessage();
        $messageType = "danger";
    }
}

// ============================================
// UPLOAD EXCEL
// ============================================

if (isset($_POST['upload_excel'])) {

    try {

        if (!isset($_FILES['excel_file'])) {
            throw new Exception("Please select excel file");
        }

        require_once __DIR__ . '/../../vendor/autoload.php'; // or require_once '/vendor/autoload.php';

        $file = $_FILES['excel_file']['tmp_name'];

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);

        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray();

        $inserted = 0;

        foreach ($rows as $index => $row) {

            // SKIP HEADER
            if ($index == 0) {
                continue;
            }

            if (empty($row[0]) && empty($row[4])) {
                continue;
            }

            $sql = "
            INSERT INTO [TBC].[dbo].[customers]
            (
                SITE,
                PRINCIPAL,
                SELLER_ID,
                SELLER_NAME,
                CUSTOMER_ID,
                CUSTOMER_NAME,
                ADDRESS,
                PHONE_NUMBER
            )
            VALUES
            (
                :site,
                :principal,
                :seller_id,
                :seller_name,
                :customer_id,
                :customer_name,
                :address,
                :phone_number
            )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':site' => $row[0] ?? '',
                ':principal' => $row[1] ?? '',
                ':seller_id' => $row[2] ?? '',
                ':seller_name' => $row[3] ?? '',
                ':customer_id' => $row[4] ?? '',
                ':customer_name' => $row[5] ?? '',
                ':address' => $row[6] ?? '',
                ':phone_number' => $row[7] ?? ''
            ]);

            $inserted++;
        }

        $message = "$inserted customers uploaded successfully!";
        $messageType = "success";

    } catch (Exception $e) {

        $message = $e->getMessage();
        $messageType = "danger";
    }
}
?>

<style>

.page-card{
    background:#fff;
    border-radius:14px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);
    overflow:hidden;
    border:none;
}

.page-header{
    background:#0f172a;
    color:#fff;
    padding:16px 20px;
}

.section-box{
    padding:20px;
    border-bottom:1px solid #e2e8f0;
}

.form-control{
    border-radius:8px;
    font-size:13px;
    padding:8px 12px;
    border:1px solid #dbe2ea;
}

.form-control:focus{
    border-color:#0284c8;
    box-shadow:none;
}

.form-label{
    font-size:12px;
    font-weight:600;
    margin-bottom:5px;
    color:#334155;
}

.btn-main{
    background:#0284c8;
    border:none;
    color:#fff;
    padding:9px 18px;
    border-radius:8px;
    font-size:13px;
    font-weight:600;
}

.btn-main:hover{
    background:#0369a1;
}

.section-title{
    font-size:15px;
    font-weight:700;
    margin-bottom:15px;
    color:#0f172a;
}

.note{
    font-size:11px;
    color:#64748b;
}

</style>

<div class="container-fluid mt-3">

    <?php if(!empty($message)): ?>

        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <div class="page-card">

        <div class="page-header">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    <i class="fa fa-users"></i>
                    Customer Management
                </h5>

            </div>

        </div>

        <!-- SIMPLE EXCEL UPLOAD -->

        <div class="section-box">

            <div class="section-title">
                <i class="fa fa-file-excel text-success"></i>
                Upload Excel
            </div>

            <form method="POST" enctype="multipart/form-data">

                <div class="row align-items-end">

                    <div class="col-md-9">

                        <label class="form-label">
                            Excel File
                        </label>

                        <input
                            type="file"
                            name="excel_file"
                            class="form-control"
                            accept=".xlsx,.xls"
                            required
                        >

                        <div class="note mt-2">
                            Format:
                            SITE | PRINCIPAL | SELLER_ID | SELLER_NAME |
                            CUSTOMER_ID | CUSTOMER_NAME | ADDRESS | PHONE_NUMBER
                        </div>

                    </div>

                    <div class="col-md-3">

                        <button
                            type="submit"
                            name="upload_excel"
                            class="btn-main w-100"
                        >
                            <i class="fa fa-upload"></i>
                            Upload Excel
                        </button>

                    </div>

                </div>

            </form>

        </div>

        <!-- MANUAL ADD -->

        <div class="section-box">

            <div class="section-title">
                <i class="fa fa-user-plus text-primary"></i>
                Add Customer Manually
            </div>

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-3">

                        <label class="form-label">
                            Site
                        </label>

                        <input
                            type="text"
                            name="site"
                            class="form-control"
                            value="<?= htmlspecialchars($selectedSite, ENT_QUOTES, 'UTF-8') ?>"
                            
                            required
                        >

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Principal
                        </label>

                        <input
                            type="text"
                            name="principal"
                            class="form-control"
                            value="<?= htmlspecialchars($selectedPrincipal, ENT_QUOTES, 'UTF-8') ?>"
                            
                        >

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Seller ID
                        </label>

                        <input
                            type="text"
                            name="seller_id"
                            class="form-control"
                        >

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Seller Name
                        </label>

                        <input
                            type="text"
                            name="seller_name"
                            class="form-control"
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Customer ID
                        </label>

                        <input
                            type="text"
                            name="customer_id"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-8">

                        <label class="form-label">
                            Customer Name
                        </label>

                        <input
                            type="text"
                            name="customer_name"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-8">

                        <label class="form-label">
                            Address
                        </label>

                        <input
                            type="text"
                            name="address"
                            class="form-control"
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone_number"
                            class="form-control"
                        >

                    </div>

                </div>

                <div class="text-end mt-4">

                    <button
                        type="submit"
                        name="save_customer"
                        class="btn-main"
                    >
                        <i class="fa fa-save"></i>
                        Save Customer
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
