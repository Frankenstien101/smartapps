<?php
session_start();
include '../../DB/dbcon.php';

/* === Site Handling (unchanged) === */
if (isset($_GET['site']) && isset($_GET['siteid'])) {
    $_SESSION['SITE_ID'] = $_GET['siteid'];
} elseif (!isset($_SESSION['SITE_NAME']) || !isset($_SESSION['SITE_ID'])) {
    $sql = "SELECT TOP 1 SITE_ID, COMPANY_ID FROM GC_USERS WHERE LINEID = :userid ORDER BY SITE_ID ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['userid' => $_SESSION['UserID']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $_SESSION['SITE_ID']   = $row['SITE_ID'];
        $_SESSION['COMPANY_ID']= $row['COMPANY_ID'];
        $allowedPages = ['dashboard','transactions','reports','settings','CreditCreation'];
        if (!isset($_GET['page']) || !in_array($_GET['page'],$allowedPages)) {
            header("Location: home.php?page=dashboard&company=".urlencode($row['COMPANY_ID'])."&siteid=".urlencode($row['SITE_ID']));
            exit();
        }
    } else {
        $_SESSION['SITE_ID'] = 'NO_SITE_ID';
    }
}
if (isset($_GET['site']) && isset($_GET['company']) && isset($_GET['siteid'])) {
    $_SESSION['SITE_NAME'] = $_GET['site'];
    $_SESSION['SITE_ID']   = $_GET['siteid'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="/Services/img/credit.ico">
    <title>Goods Credit</title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* === ORIGINAL STYLES PRESERVED === */
        .sidebar-dark-primary{
            background:linear-gradient(135deg,#021555ff 0%,#004b91ff 100%)!important;
        }
        .brand-text,.nav-link p,.info a{
            text-shadow:0 1px 2px rgba(255,255,255,.3);
        }
        .nav-item .nav-link:hover{
            background-color:rgba(241,238,238,.15)!important;
        }
        .bg-dark-orange{
            background-color:#f5c31dff!important;
        }
        .content-wrapper{
            position:absolute;top:0;left:0;right:0;height:90vh;overflow-y:auto;
            background:#f4f6f9;padding:2rem 2rem 2rem 0;z-index:1;transition:left .3s;
        }

        /* FILTER CARD */
        .filter-container{display:flex;justify-content:left;align-items:left;margin-top:5px;}
        .filter-container .card{font-size:9px;}
        .filter-container .card-body .row{display:flex;align-items:top;margin-top:0;}
        .filter-container label{font-weight:bold;font-size:9px;}
        .filter-container input,.filter-container select{font-size:9px;}

        /* MODAL BLUR */
        .modal-blur{
            position:fixed;top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,.25);backdrop-filter:blur(4px);
            z-index:1040;display:none;pointer-events:none;
        }

        /* Z-INDEX */
        .modal{z-index:1055!important;}
        .modal-backdrop{z-index:1050!important;}

        .modal-dialog{max-width:500px;}


        body.sidebar-collapse .content-wrapper {
    margin-left: 0 !important;
}

    </style>
</head>
<body class="hold-transition sidebar-mini">

<div class="wrapper">

    <!-- NAVBAR -->
    <nav class="main-header navbar navbar-expand navbar-light bg-dark-orange">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block"><a href="home.php?page=dashboard" class="nav-link">Home</a></li>
            <li class="nav-item d-none d-sm-inline-block"><a href="#" class="nav-link">Contact</a></li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Help</a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#">FAQ</a>
                    <a class="dropdown-item" href="#">Support</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Contact</a>
                </div>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class38="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                    <?= $_SESSION['SITE_ID'] ?? 'NO_SITE' ?> <i class="far fa-bell"></i>
                    <img src="/MainImg/icons8-user-50.png" alt="User" style="width:20px;height:20px;margin-left:5px;">
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="text-center">
                        <img src="/MainImg/icons8-user-48.png" alt="User" style="width:75px;height:75px;">
                    </div>
                    <span class="dropdown-header"><?= $_SESSION['Name_of_user'] ?></span>
                    <div class="dropdown-divider"></div>

                    <div class="d-flex justify-content-center">
                        <div class="dropdown px-2">
                            <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">Select Site</button>
                            <div class="dropdown-menu">
                                <?php
                                $query = "SELECT SITE_ID FROM GC_USERS WHERE LINEID = ? GROUP BY SITE_ID";
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$_SESSION['UserID']]);
                                foreach ($stmt as $data) {
                                    echo '<a class="dropdown-item" href="home.php?page=dashboard&siteid=' . urlencode($data['SITE_ID']) . '">'
                                        . htmlspecialchars($data['SITE_ID']) . '</a>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item"><i class="fas fa-users mr-2"></i> <?= $_SESSION['Role'] ?></a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item"><i class="fas fa-cog mr-2"></i> Account Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item bg-danger text-white text-center" onclick="confirmLogout();">Logout</a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- SIDEBAR -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4" style="position:fixed;">
        <a href="#" class="brand-link text-center">
            <img src="/Services/img/credit.ico" alt="Logo" style="width:80px;height:80px;">
            <span class="brand-text font-weight-bold d-block">GOODS CREDIT</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-1 d-flex">
                <div class="info"><a href="#" class="d-block"><?= $_SESSION['Company_Name'] ?></a></div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item"><a href="home.php?page=dashboard" class="nav-link d-flex align-items-center">
                        <img src="\GC\Home\img\dashboard.png" style="width:30px;height:30px;margin-right:10px;"><p class="mb-0">Dashboard</p></a></li>
                    <li class="nav-item"><a href="home.php?page=transactions" class="nav-link d-flex align-items-center">
                        <img src="\GC\Home\img\transaction.png" style="width:30px;height:30px;margin-right:10px;"><p class="mb-0">Transactions</p></a></li>
                    <li class="nav-item"><a href="home.php?page=reports" class="nav-link d-flex align-items-center">
                        <img src="\GC\Home\img\file.png" style="width:25px;height:25px;margin-right:10px;"><p class="mb-0">Reports</p></a></li>
                    <li class="nav-item"><a href="home.php?page=settings" class="nav-link d-flex align-items-center">
                        <img src="\GC\Home\img\settingset.png" style="width:30px;height:30px;margin-right:10px;"><p class="mb-0">Settings</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="content-wrapper p-4" style="margin-left:250px;position:relative;margin-top:0;margin-bottom:0;">
        <?php
        $role = $_SESSION['Role'] ?? '';
        $page = $_GET['page'] ?? 'dashboard';

        if ($role === 'ADMIN') {
            $allowed = ['dashboard','transactions','reports','settings','CreditCreation'];
            if (in_array($page,$allowed)) include "pages/{$page}.php";
            else echo "<h1 class='text-center'>Page not found</h1>";
        } elseif ($role === 'CASHIER') {
            $allowed = ['dashboard','transactions','reports','CreditCreation'];
            if (in_array($page,$allowed)) include "pages/{$page}.php";
            else echo "<h1 class='text-center'>Access denied</h1>";
        } else {
            echo "<h3 class='text-center'>Session expired. <a href='verify.php'>Login again</a></h3>";
            exit();
        }
        ?>
    </div>

    <!-- BLUR BACKGROUND -->
    <div class="modal-blur"></div>

    <!-- ==== MODALS (GLOBAL) ==== -->

    <!-- Load Transaction Modal -->
    <div class="modal fade" id="loadModal" tabindex="-1" role="dialog" aria-labelledby="loadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mt-1" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loadModalLabel">Load Transaction</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Here you can load a saved transaction or search existing ones.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Select Creditor Modal – WIDE + FROZEN HEADER -->
    <div class="modal fade" id="selectcreditor" tabindex="-1" role="dialog"
         aria-labelledby="selectCreditorLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"
             style="max-width:1300px;margin-top:60px;" role="document">
            <div class="modal-content shadow-lg border-0" style="border-radius:12px;font-size:9px;">
                <div class="modal-header bg-primary text-white py-2"
                     style="border-top-left-radius:12px;border-top-right-radius:12px;">
                    <h6 class="modal-title mb-0"><i class="fas fa-user-tie mr-2"></i> Select Creditor</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">

                    <!-- Search -->
                    <div class="form-group mb-3">
                        <div class="input-group" style="width:380px;">
                            <input type="text" id="creditorSearch" class="form-control form-control-sm"
                                   placeholder="Search by name or ID...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary btn-sm" type="button"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Creditors Table -->
                    <div id="creditors" class="card shadow-sm border-0" style="max-height:58vh;overflow:hidden;">
                        <div class="card-body p-0" style="overflow:hidden;">
                            <div class="table-responsive" style="max-height:58vh;overflow-y:auto;">
                                <table class="table table-striped table-hover table-bordered mb-0"
                                       style="font-size:9px;margin-bottom:0;">
                                    <thead class="thead-dark"
                                           style="position:sticky;top:0;z-index:10;background:#343a40;">
                                        <tr>
                                            <th style="width:8%;">SITE</th>
                                            <th style="width:15%;">DEPARTMENT</th>
                                            <th style="width:12%;">ID</th>
                                            <th style="width:25%;">NAME</th>
                                            <th style="width:15%;">CREDIT LIMIT</th>
                                            <th style="width:10%;">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody><!-- filled by JS --></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- ADMINLTE JS (required for sidebar collapse) -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
function confirmLogout(){
    if(confirm('Are you sure you want to logout?')) window.location.href='verify.php';
}

$(function(){
    // show custom blur when any modal opens
    $('.modal').on('show.bs.modal', function(){ $('.modal-blur').show(); });
    $('.modal').on('hidden.bs.modal', function(){
        $('.modal-blur').hide();
        // keep Bootstrap backdrop (just in case)
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right','');
    });

    // live search inside creditor modal
    $('#creditorSearch').on('keyup', function(){
        const q = this.value.toLowerCase();
        $('#creditors tbody tr').each(function(){
            const txt = this.textContent.toLowerCase();
            $(this).toggle(txt.includes(q));
        });
    });
});

$(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', function () {
  const body = document.body;
  const wrapper = document.querySelector('.content-wrapper');
  wrapper.style.marginLeft = body.classList.contains('sidebar-collapse') ? '0' : '250px';
});

</script>
</body>
</html>