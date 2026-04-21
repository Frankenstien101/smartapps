<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modern PHP Dashboard</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
    overflow-x: hidden;
}

/* Sidebar */
#sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background: #1f2937;
    color: #fff;
    transition: all 0.3s;
}

#sidebar .nav-link {
    color: #cbd5e1;
}

#sidebar .nav-link:hover {
    background: #374151;
    color: #fff;
}

/* Content */
#content {
    margin-left: 250px;
    padding: 20px;
}

/* Navbar */
.navbar {
    margin-left: 250px;
}

.submenu {
    padding-left: 20px;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar" class="p-3">
    <div class="text-center mb-3"> <img src="MainImg/logo.png" alt="Delivery Dash Logo" style="height: 120px; width: 120px;"> </div>
    <hr>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="#"><i class="fa fa-home"></i> Dashboard</a>
        </li>

        <!-- SALES -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuSales">
                <i class="fa fa-cash-register"></i> Sales
            </a>
            <div class="collapse submenu" id="menuSales">
                <a class="nav-link" href="#">POS / New Sale</a>
                <a class="nav-link" href="#">Sales Transactions</a>
                <a class="nav-link" href="#">Receipts</a>
            </div>
        </li>

        <!-- PRODUCTS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuProducts">
                <i class="fa fa-mobile-alt"></i> Devices & Accessories
            </a>
            <div class="collapse submenu" id="menuProducts">
                <a class="nav-link" href="#">Mobile Phones</a>
                <a class="nav-link" href="#">Accessories</a>
                <a class="nav-link" href="#">Brands</a>
                <a class="nav-link" href="#">Categories</a>
            </div>
        </li>

        <!-- INVENTORY -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuInventory">
                <i class="fa fa-box"></i> Inventory
            </a>
            <div class="collapse submenu" id="menuInventory">
                <a class="nav-link" href="#">Stock In</a>
                <a class="nav-link" href="#">Stock Out</a>
                <a class="nav-link" href="#">Stock Transfer</a>
                <a class="nav-link" href="#">Inventory Count</a>
            </div>
        </li>

        <!-- TRANSACTIONS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuTransactions">
                <i class="fa fa-receipt"></i> Transactions
            </a>
            <div class="collapse submenu" id="menuTransactions">
                <a class="nav-link" href="#">Sales History</a>
                <a class="nav-link" href="#">Returns / Warranty</a>
                <a class="nav-link" href="#">Repairs</a>
                <a class="nav-link" href="#">Installments</a>
            </div>
        </li>

        <!-- SUPPLIERS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuSuppliers">
                <i class="fa fa-truck"></i> Suppliers
            </a>
            <div class="collapse submenu" id="menuSuppliers">
                <a class="nav-link" href="#">Supplier List</a>
                <a class="nav-link" href="#">Purchase Orders</a>
                <a class="nav-link" href="#">Deliveries</a>
            </div>
        </li>

        <!-- USERS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuUsers">
                <i class="fa fa-users"></i> Users
            </a>
            <div class="collapse submenu" id="menuUsers">
                <a class="nav-link" href="#">User List</a>
                <a class="nav-link" href="#">Roles & Permissions</a>
            </div>
        </li>

        <!-- REPORTS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuReports">
                <i class="fa fa-chart-bar"></i> Reports
            </a>
            <div class="collapse submenu" id="menuReports">
                <a class="nav-link" href="#">Sales Report</a>
                <a class="nav-link" href="#">Inventory Report</a>
                <a class="nav-link" href="#">Profit Report</a>
            </div>
        </li>

        <!-- SETTINGS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuSettings">
                <i class="fa fa-cog"></i> Settings
            </a>
            <div class="collapse submenu" id="menuSettings">
                <a class="nav-link" href="#">General Settings</a>
                <a class="nav-link" href="#">System Setup</a>
            </div>
        </li>
    </ul>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary" id="toggleSidebar">
            <i class="fa fa-bars"></i>
        </button>

        <div class="ms-auto d-flex align-items-center">
            <span class="me-3">Welcome, <?php echo $_SESSION['username'] ?? 'Guest'; ?></span>
            <img src="https://via.placeholder.com/35" class="rounded-circle">
        </div>
    </div>
</nav>

<!-- Content -->
<div id="content">
    <?php
        $page = $_GET['page'] ?? 'dashboard.php';

        // basic security: prevent directory traversal
        $page = basename($page);

        $file = "pages/" . $page;

        if (file_exists($file)) {
            include $file;
        } else {
            echo "<h3>Page not found</h3>";
        }
    ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const navbar = document.querySelector('.navbar');

document.getElementById('toggleSidebar').addEventListener('click', () => {
    if (sidebar.style.marginLeft === '-250px') {
        sidebar.style.marginLeft = '0';
        content.style.marginLeft = '250px';
        navbar.style.marginLeft = '250px';
    } else {
        sidebar.style.marginLeft = '-250px';
        content.style.marginLeft = '0';
        navbar.style.marginLeft = '0';
    }
});
</script>

</body>
</html>
