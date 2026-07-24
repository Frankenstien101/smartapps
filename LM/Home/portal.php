<?php
session_start();

include '../../DB/dbcon.php';

if (!isset($_SESSION['Name_of_user']) || empty($_SESSION['Name_of_user'])) {
    header("Location: /LM/Home/verify.php");
    exit;
}

$checksession = $_SESSION['Company_ID'] ?? '';

if (empty($checksession)) {
    header("Location: /LM/Home/verify.php");
    exit;
}

?>
<!doctype html>

<html lang="en">
<head>
<!-- In <head> -->

  <link rel="icon" type="image/x-icon" href="/MainImg/loadgurad2.ICO">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <title>Load Guard</title>
</head>
<body class="hold-transition sidebar-mini">

<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-dark bg-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
          <i class="fas fa-bars"></i>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="portal.php?page=dashboard" class="nav-link">Home</a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">Contact</a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown2" role="button" data-toggle="dropdown">
          Help
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown2">
          <a class="dropdown-item" href="#">FAQ</a>
          <a class="dropdown-item" href="#">Support</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="#">Contact</a>
        </div>
      </li>
    </ul>

<ul class="navbar-nav ml-auto">

  <li class="nav-item dropdown">
    
    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
<?= $_SESSION['SITE_NAME'] ?? 'NO SITE' ?> &nbsp;

    <i class="far fa-bell"></i>
      <img src="/MainImg/icons8-user-50.png" alt="User Icon" style="width:20px; height:20px; margin-left:5px;">
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
      <div class="text-center">
        <img src="/MainImg/icons8-user-48.png" alt="User Icon" style="width:75px; height:75px;">
      </div>
      <span class="dropdown-header">
        <?php echo $_SESSION['Name_of_user']; ?>
      </span>

      <div class="dropdown-divider"></div>

  <div class="d-flex justify-content-center">
      <div class="dropdown px-2">
  <button type="button" class="btn btn-success dropdown-toggle mb-1 mt-1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
    Select Site
  </button>

  <div class="dropdown-menu">
    
            <?php
            $query = "SELECT 
                USER_ID,
                SITE_ID,
                SITE_NAME
            FROM OK_User_Site  
            WHERE USER_ID = '" . $_SESSION['UserID'] . "' 
            GROUP BY USER_ID, SITE_ID, SITE_NAME";
            $query_result = $conn->query($query);

            foreach ($query_result as $data) {
                echo '<a class="dropdown-item" href="portal.php?page=dashboard'
                   . '&site=' . urlencode($data['SITE_NAME'])
                   . '&siteid=' . urlencode($data['SITE_ID']) . '">'
                   . htmlspecialchars($data['SITE_NAME']) . '</a>';
            }

            
            
            ?>

  </div>
    </div>
</div>

      <div class="dropdown-divider"></div>
      <a href="#" class="dropdown-item">
        <i class="fas fa-users mr-2"></i> <?php echo $_SESSION['Role']; ?> &nbsp;
      </a>
      <div class="dropdown-divider"></div>
      <a href="?page=account_settings" class="dropdown-item">
    <i class="fas fa-cog mr-2"></i> Account Settings
      </a> 
      <div class="dropdown-divider"></div>
      <a href="#" class="dropdown-item dropdown-footer bg-danger text-white text-center" onclick="confirmLogout();">
        Logout Account
      </a>
    </div>
  </li>
</ul>

<script>
  function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
      window.location.href = 'verify.php';
    }
  }
</script>

        </div>
      </li>
    </ul>
  </nav>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/x-icon" href="\LM\Home\img\loadguard2.png">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <title>Delivery Dash</title>
  <style>
   
    .sidebar-dark-primary {
      background: linear-gradient(135deg, rgb(59, 40, 4) 0%, rgba(119, 65, 3, 0.92) 100%) !important;
    }
    
    .brand-text, .nav-link p, .info a {
      text-shadow: 0 1px 2px rgba(224, 219, 219, 0.3);
    }
    
    .nav-item .nav-link:hover {
      background-color: rgba(12, 0, 0, 0.15) !important;
    }

    .bg-dark-orange {
  background-color: #ca5303ff !important; /* dark orange */
}
  </style>
</head>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="#" class="brand-link text-center">
      <img src="\LM\Home\img\LoadGuard2.png" alt="Logo" style="width: 170px; height: 150px;">
    </a>

    <div class="sidebar">

<div class="user-panel mt-3 pb-3 mb-1 d-flex">
    <div class="info">
        <span class="d-block text-white">
            <?php echo htmlspecialchars($_SESSION['Company_Name'] ?? 'Company'); ?>
        </span>
    </div>
</div>

   <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <li class="nav-item">
            <a href="portal.php?page=dashboard" class="nav-link d-flex align-items-center">
              <img src="\LM\Home\img\analysis.png" style=" width: 30px; height: 30px; margin-right: 10px;">
              <p class="mb-0">Dashboard</p>
            </a>
          </li>

          <li class="nav-item">
          <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] === 'ADMIN') { ?>
              <a href="portal.php?page=transactions" class="nav-link d-flex align-items-center">
          <?php } elseif (isset($_SESSION['Role']) && $_SESSION['Role'] === 'HR') { ?>
              <a href="portal.php?page=transactions-hr" class="nav-link d-flex align-items-center">
          <?php } else { ?>
              <a href="portal.php?page=transactions-fin" class="nav-link d-flex align-items-center">
          <?php } ?>

              <img src="\LM\Home\img\transaction.png" 
                   style="width: 30px; height: 35px; margin-right: 10px;">
              <p class="mb-0">Transactions</p>
          </a>
      </li>

          <li class="nav-item">
            <a href="portal.php?page=reports" class="nav-link d-flex align-items-center">
              <img src="\LM\Home\img\documents.png" style="width: 30px; height: 30px; margin-right: 10px;">
              <p class="mb-0">Reports</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="portal.php?page=#" class="nav-link d-flex align-items-center">
              <img src="\LM\Home\img\settings.png" style="width: 30px; height: 30px; margin-right: 10px;">
              <p class="mb-0">Settings</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper p-4" style="overflow-y: auto; max-height: 90vh;">
    <?php
    $role = $_SESSION['Role'] ?? '';
    
    if ($role === 'ADMIN') {
        // Admin pages logic
        $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
        $allowedPages = ['dashboard', 'transactions', 'reports2' , 'settings' , 'loadchecking', 'addnewdevice', 'loadrequest', 'loadcheckresult', 'loadpurchase', 'reports', 'devicelist', 'loadcheckingreport', 'purchasereport', 'submittedlist' , 'unsubmitted' , 'resign' , 'deploy', 'report_resigned','account_settings', 'qrchecking', 'QR-generator', 'devicestatus'];
        if (in_array($page, $allowedPages)) {
            include "pages/{$page}.php";
        } else {
            include "pages/error.php";
        }
    } elseif ($role === 'FINANCE') {
        // Encoder pages logic
        $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
        $allowedPages = ['dashboard', 'transactions', 'transactions-fin', 'reports2' , 'settings' , 'loadchecking', 'addnewdevice', 'loadrequest', 'loadcheckresult', 'loadpurchase', 'reports', 'devicelist', 'loadcheckingreport', 'purchasereport', 'submittedlist' , 'unsubmitted' , 'resign' , 'deploy', 'report_resigned','account_settings'];
        if (in_array($page, $allowedPages)) {
            include "pages/{$page}.php";
        } else {
            echo "<h1 class='text-center'>Page not found or not allowed</h1>";
        }
    } elseif ($role === 'HR') {
        // IRA pages logic
        $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
        $allowedPages = ['dashboard', 'transactions', 'transactions-hr','reports2' , 'settings' , 'loadchecking', 'addnewdevice', 'loadrequest', 'loadcheckresult', 'loadpurchase', 'reports', 'devicelist', 'loadcheckingreport', 'purchasereport', 'submittedlist' , 'unsubmitted' , 'resign' , 'deploy', 'report_resigned','account_settings'];

        if (in_array($page, $allowedPages)) {
          
        include "pages/{$page}.php";

        } else {

        include "pages/error.php";

        }
   
    } else {
        // Default case
        echo "<h3 class='text-center'>
                Session expired or account logged out, 
                <a href='verify.php' onclick='handleLoginClick(event)'>Login again</a> to continue
              </h3>";
        echo "<script>
        function handleLoginClick(event) {
            event.preventDefault();
            window.location.href = 'verify.php';
        }
        </script>";
        exit();
    }
    ?>
  </div>
</div>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
  function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
      window.location.href = 'verify.php';
    }
  }
  
  $(document).on('click', '#click_me', function(){
    let site_id = $(this).data("id");
    $("#site_name").text(site_id);
    alert(site_id);
  });
</script>
</body>
</html>