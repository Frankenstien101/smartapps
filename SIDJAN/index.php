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
<div class="text-center mb-3">
    <img src="MainImg/logo.png" alt="Delivery Dash Logo" style="height: 120px; width: 120px;">
</div>    <hr>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="#"><i class="fa fa-home"></i> Dashboard</a>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menu1">
                <i class="fa fa-list"></i> Transactions
            </a>
            <div class="collapse submenu" id="menu1">
                <a class="nav-link" href="#">Sales Transaction</a>
                <a class="nav-link" href="#">Loan Application</a>
                <a class="nav-link" href="#">Payments</a>
            </div>
        </li>


        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menu2">
                <i class="fa fa-box"></i> Inventory
            </a>
            <div class="collapse submenu" id="menu2">
                <a class="nav-link" href="#">Products</a>
                <a class="nav-link" href="#">Categories</a>
                <a class="nav-link" href="#">Stock</a>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menu3">
                <i class="fa fa-users"></i> Users
            </a>
            <div class="collapse submenu" id="menu3">
                <a class="nav-link" href="#">User List</a>
                <a class="nav-link" href="#">Roles</a>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menu4">
                <i class="fa fa-cog"></i> Settings
            </a>
            <div class="collapse submenu" id="menu4">
                <a class="nav-link" href="#">General</a>
                <a class="nav-link" href="#">Security</a>
                <a class="nav-link" style="background-color: red;" href="#">Logout</a>
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
            <span class="me-3">Welcome, <?php echo $_SESSION['username'] ?? 'user'; ?></span>
            <img src="/mainimg/user.png" class="rounded-circle" style="height: 30px;">
        </div>
    </div>
</nav>

<!-- Content -->
<div id="content">
    <h2>Dashboard</h2>
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
