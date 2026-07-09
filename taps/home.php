<?php
// index.php (admin dashboard)
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: /taps/login.php");
    exit();
}

if ($_SESSION['Role'] != 'ADMIN') {
    header("Location: /taps/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <link rel="icon" type="image/x-icon" href="/taps/mainimg/taps.ico">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TAPS | Time and Payroll System</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --maroon: #862d2d;
            --dark-maroon: #4d1a1a;
            --light-maroon: #b84a4a;
            --bg: #0a0505;
            --panel: rgba(12, 8, 8, 0.95);
            --text: #f0e6e6;
            --muted: #b88a8a;
            --shadow: #4d1e1e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            overflow-x: hidden;
            background: linear-gradient(rgba(10, 5, 5, 0.92), rgba(10, 5, 5, 0.96));
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--maroon);
            border-radius: 20px;
        }

        ::-webkit-scrollbar-track {
            background: #111;
        }

        /* SIDEBAR */
        #sidebar {
            width: 270px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: rgba(34, 0, 0, 0.96);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(134, 45, 45, 0.2);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 768px) {
            #sidebar {
                left: -270px;
            }

            #sidebar.active {
                left: 0;
            }
        }

        /* SIDEBAR LOGO */
        .sidebar-logo {
            text-align: center;
            padding: 25px 10px;
            border-bottom: 1px solid rgba(134, 45, 45, 0.15);
            margin-bottom: 15px;
        }

        .sidebar-logo img {
            width: 135px;
            height: 135px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(134, 45, 45, 0.3);
            box-shadow: 0 0 25px rgba(134, 45, 45, 0.2);
            transition: all 0.3s ease;
            animation: glowPulse 3s ease-in-out infinite;
        }

        .sidebar-logo img:hover {
            border-color: rgba(134, 45, 45, 0.6);
            box-shadow: 0 0 20px rgba(134, 45, 45, 0.3), 0 0 40px rgba(134, 45, 45, 0.2);
            transform: scale(1.02);
        }

        @keyframes glowPulse {
            0% {
                box-shadow: 0 0 20px rgba(134, 45, 45, 0.15);
            }
            50% {
                box-shadow: 0 0 35px rgba(134, 45, 45, 0.3), 0 0 50px rgba(134, 45, 45, 0.15);
            }
            100% {
                box-shadow: 0 0 20px rgba(134, 45, 45, 0.15);
            }
        }

        .sidebar-logo h5 {
            margin-top: 12px;
            color: var(--light-maroon);
            font-weight: 700;
            letter-spacing: 5px;
        }

        .sidebar-logo .subtitle {
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 1px;
        }

        .sidebar-nav {
            flex: 1;
        }

        /* NAV LINKS */
        #sidebar .nav-link {
            color: #d4b5b5;
            padding: 12px 16px;
            border-radius: 12px;
            margin: 4px 0;
            transition: all 0.25s ease;
            font-size: 14px;
            border: 1px solid transparent;
        }

        #sidebar .nav-link:hover {
            background: rgba(134, 45, 45, 0.12);
            border-color: rgba(134, 45, 45, 0.2);
            color: #f5e6e6;
            transform: translateX(4px);
        }

        #sidebar .nav-link i {
            width: 24px;
            color: var(--light-maroon);
        }

        .submenu {
            padding-left: 20px;
        }

        /* Mobile User Bottom */
        .mobile-user-bottom {
            display: none;
            padding: 15px 20px;
            margin-top: auto;
            border-top: 1px solid rgba(134, 45, 45, 0.15);
            background: rgba(0, 0, 0, 0.3);
        }

        .mobile-user-bottom .user-name-bottom {
            color: var(--light-maroon);
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mobile-user-bottom .user-name-bottom i {
            font-size: 14px;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .mobile-user-bottom {
                display: block;
            }
        }

        /* NAVBAR */
        .navbar {
            position: fixed;
            top: 0;
            left: 270px;
            right: 0;
            z-index: 999;
            background: rgba(8, 5, 5, 0.92) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(134, 45, 45, 0.15);
            padding: 14px 18px;
        }

        @media (max-width: 768px) {
            .navbar {
                left: 0;
            }
        }

        /* CONTENT */
        #content {
            margin-left: 270px;
            margin-top: 80px;
            padding: 22px;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            #content {
                margin-left: 0;
            }
        }

        /* TOGGLE */
        .toggle-btn {
            border: none;
            background: rgba(134, 45, 45, 0.12);
            color: var(--light-maroon);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            transition: 0.25s;
        }

        .toggle-btn:hover {
            background: rgba(134, 45, 45, 0.25);
            transform: scale(1.05);
        }

        /* USER */
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(134, 45, 45, 0.3);
        }

        .user-name {
            color: var(--text);
            font-size: 14px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @media (max-width: 768px) {
            .user-info {
                display: none !important;
            }
        }

        /* COMPANY */
        .company-name {
            color: var(--light-maroon);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .company-name {
                font-size: 12px;
            }
            .company-name p {
                margin-bottom: 0;
            }
        }

        /* OVERLAY */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 998;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ACTIVE MENU */
        .active-custom {
            background: rgba(134, 45, 45, 0.15) !important;
            border: 1px solid rgba(134, 45, 45, 0.25) !important;
            color: #fff !important;
        }

        /* MODAL */
        .modal-content {
            background: #1a0c0c !important;
            color: var(--text);
            border: 1px solid rgba(134, 45, 45, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(134, 45, 45, 0.15) !important;
        }

        .modal-footer {
            border-top: 1px solid rgba(134, 45, 45, 0.15) !important;
        }

        .btn-danger {
            border: none;
            background: var(--maroon);
        }

        .btn-danger:hover {
            background: var(--light-maroon);
        }

        .btn-secondary {
            background: #2c1a1a;
            border: none;
        }

        .btn-secondary:hover {
            background: #3d2525;
        }

        /* CHEVRON */
        .fa-chevron-down {
            transition: 0.25s;
        }

        .nav-link[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        /* STAT CARDS */
        .stat-card {
            background: rgba(26, 12, 12, 0.8);
            border: 1px solid rgba(134, 45, 45, 0.15);
            border-radius: 16px;
            padding: 20px;
            transition: 0.3s;
            backdrop-filter: blur(8px);
        }

        .stat-card:hover {
            border-color: rgba(134, 45, 45, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(134, 45, 45, 0.1);
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            color: var(--light-maroon);
            opacity: 0.8;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
        }

        .stat-card .stat-label {
            color: var(--muted);
            font-size: 0.9rem;
        }
    </style>

</head>

<body>

    <!-- MOBILE OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div id="sidebar" class="p-3">

        <div class="sidebar-logo">

            <img src="/taps/mainimg/logo.png" alt="TAPS">

            <div class="subtitle">Time and Payroll System</div>

        </div>

        <div class="sidebar-nav">
            <ul class="nav flex-column">

                <li class="nav-item">
                    <a class="nav-link" href="?page=dashboard">
                        <i class="fa fa-chart-line"></i> Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menuTime">
                        <i class="fa fa-clock"></i> Time Management
                        <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>
                    </a>
                    <div class="collapse submenu" id="menuTime">
                        <a class="nav-link" href="?page=attendance">Attendance</a>
                        <a class="nav-link" href="?page=timesheets">Timesheets</a>
                        <a class="nav-link" href="?page=overtime">Overtime</a>
                        <a class="nav-link" href="?page=leave">Leave Management</a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menuPayroll">
                        <i class="fa fa-coins"></i> Payroll
                        <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>
                    </a>
                    <div class="collapse submenu" id="menuPayroll">
                        <a class="nav-link" href="?page=payroll-process">Process Payroll</a>
                        <a class="nav-link" href="?page=payroll-history">Payroll History</a>
                        <a class="nav-link" href="?page=deductions">Deductions</a>
                        <a class="nav-link" href="?page=reports">Reports</a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menuEmployees">
                        <i class="fa fa-users"></i> Employees
                        <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>
                    </a>
                    <div class="collapse submenu" id="menuEmployees">
                        <a class="nav-link" href="?page=employee-list">Employee List</a>
                        <a class="nav-link" href="?page=add-employee">Add Employee</a>
                        <a class="nav-link" href="?page=departments">Departments</a>
                        <a class="nav-link" href="?page=positions">Positions</a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menuSettings">
                        <i class="fa fa-gear"></i> Settings
                        <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>
                    </a>
                    <div class="collapse submenu" id="menuSettings">
                        <a class="nav-link" href="?page=users">Users</a>
                        <a class="nav-link" href="?page=company-settings">Company Settings</a>
                        <a class="nav-link" href="?page=payroll-settings">Payroll Settings</a>
                    </div>
                </li>

            </ul>
        </div>

        <!-- Logout Link -->
        <a class="nav-link text-danger mt-3"
           href="#"
           data-bs-toggle="modal"
           data-bs-target="#logoutModal">

            <i class="fa fa-sign-out-alt"></i> Logout

        </a>

        <!-- Mobile User Name at Bottom -->
        <div class="mobile-user-bottom">
            <div class="user-name-bottom">
                <i class="fa fa-user-circle"></i> <?php echo $_SESSION['NAME'] ?? 'Admin'; ?>
            </div>
        </div>

    </div>

    <!-- LOGOUT MODAL -->
    <div class="modal fade" id="logoutModal" tabindex="-1">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header" style="background: var(--maroon); color: white;">

                    <h5 class="modal-title">

                        <i class="fas fa-sign-out-alt"></i> Confirm Logout

                    </h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body text-center">

                    <i class="fas fa-question-circle fa-3x mb-3" style="color: var(--light-maroon);"></i>

                    <h5>Are you sure you want to logout?</h5>

                    <p class="text-secondary">
                        You will be redirected to the login page.
                    </p>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <a href="/taps/logout.php"
                       class="btn btn-danger">

                        Logout

                    </a>

                </div>

            </div>

        </div>

    </div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">

        <div class="container-fluid">

            <button class="toggle-btn" id="toggleSidebar">

                <i class="fa fa-bars"></i>

            </button>

            <div class="company-name mt-3 ms-3">

                <p>

                    <i class="fa-regular fa-clock"></i>

                    <?php echo $_SESSION['COMPANY'] ?? 'TAPS System'; ?>

                </p>

            </div>

            <div class="ms-auto user-info">

                <span class="user-name">

                    Welcome, <?php echo $_SESSION['NAME'] ?? 'Admin'; ?>

                </span>

                <img src="/taps/mainimg/user.png"
                     class="user-avatar"
                     alt="User Avatar">

            </div>

        </div>

    </nav>

    <!-- CONTENT -->
    <div id="content" style="height:calc(100vh - 85px); overflow:hidden; padding:15px;">

        <?php

        $page = $_GET['page'] ?? 'dashboard';
        $page = basename($page);
        $file = "pages/" . $page . ".php";

        if (file_exists($file)) {

            // Special pages (fullscreen)
            if ($page == 'attendance' || $page == 'timesheets') {

                echo '
                <style>
                    #content {
                        padding: 0 !important;
                        margin-top: 70px;
                        height: calc(100vh - 70px);
                        overflow: hidden;
                        background: var(--bg);
                    }

                    .page-wrapper {
                        width: 100%;
                        height: 100%;
                        position: relative;
                        overflow: hidden;
                        border-radius: 0;
                    }
                </style>

                <div class="page-wrapper">';

                include $file;

                echo '</div>';
            } else {

                // Normal pages
                echo '<div class="container-fluid py-2">';

                include $file;

                echo '</div>';
            }
        } else {

            // Page not found
            echo '
            <div class="container-fluid">

                <div class="alert alert-warning border-0 shadow-sm rounded-4" style="background: rgba(134, 45, 45, 0.1); border: 1px solid rgba(134, 45, 45, 0.2);">

                    <h4 class="mb-2" style="color: var(--light-maroon);">
                        <i class="fa fa-exclamation-triangle" style="color: var(--light-maroon);"></i>
                        Page not found
                    </h4>

                    <p class="mb-3 text-muted">
                        The requested page does not exist.
                    </p>

                    <a href="?page=dashboard" class="btn" style="background: var(--maroon); color: white; border-radius: 8px;">
                        <i class="fa fa-home"></i>
                        Back to Dashboard
                    </a>

                </div>

            </div>';
        }

        ?>

    </div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const navbar = document.querySelector('.navbar');
            const toggleBtn = document.getElementById('toggleSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function isMobile() {
                return window.innerWidth <= 768;
            }

            function openSidebar() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }

            toggleBtn.addEventListener('click', function() {

                if (isMobile()) {

                    if (sidebar.classList.contains('active')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }

                } else {

                    if (sidebar.style.marginLeft === '-270px') {

                        sidebar.style.marginLeft = '0';
                        content.style.marginLeft = '270px';
                        navbar.style.left = '270px';

                    } else {

                        sidebar.style.marginLeft = '-270px';
                        content.style.marginLeft = '0';
                        navbar.style.left = '0';

                    }

                }

            });

            overlay.addEventListener('click', closeSidebar);

            // ACTIVE MENU
            const currentPage = '<?php echo $page; ?>';
            const menuLinks = document.querySelectorAll('.nav-link');

            menuLinks.forEach(link => {

                const href = link.getAttribute('href');

                if (href && href.includes('page=' + currentPage)) {

                    link.classList.add('active-custom');

                    const parentCollapse = link.closest('.collapse');

                    if (parentCollapse) {

                        const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                            toggle: false
                        });

                        bsCollapse.show();
                    }
                }

            });

        });
    </script>

</body>

</html>