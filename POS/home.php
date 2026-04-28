<?php
// index.php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: /POS/login.php");
    exit();
}

if ($_SESSION['Role'] != 'admin') {
    header("Location: /POS/login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head> 
<link rel="icon" type="image/x-icon" href="img/pos.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<title>POS System</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        overflow-x: hidden;
        background: #f0f2f5;
    }
    
    /* Sidebar */
    #sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: #1f2937;
        color: #fff;
        transition: all 0.3s ease;
        z-index: 1000;
        overflow-y: auto;
    }
    
    /* Sidebar closed state on mobile */
    @media (max-width: 768px) {
        #sidebar {
            left: -260px;
        }
        #sidebar.active {
            left: 0;
        }
    }
    
    #sidebar .nav-link {
        color: #cbd5e1;
        padding: 10px 15px;
        border-radius: 8px;
        margin: 0px 0;
        transition: all 0.2s;
    }
    
    #sidebar .nav-link:hover {
        background: #374151;
        color: #fff;
    }
    
    /* Content */
    #content {
        margin-left: 260px;
        padding: 20px;
        transition: all 0.3s ease;
        max-height: 90vh;
    }
    
    @media (max-width: 768px) {
        #content {
            margin-left: 0;
        }
    }
    
    /* Navbar */
    .navbar {
        margin-left: 0px;
        transition: all 0.3s ease;
        position: fixed;
        top: 0;
        right: 0;
        left: 260px;
        z-index: 999;
        border-radius: 0;
        background: white !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    @media (max-width: 768px) {
        .navbar {
            margin-left: 0;
            left: 0;
        }
    }
    
    /* Push content down to account for fixed navbar */
    #content {
        margin-top: 70px;
    }
    
    .submenu {
        padding-left: 25px;
    }
    
    /* Toggle Button */
    .toggle-btn {
        background: transparent;
        border: none;
        font-size: 20px;
        color: #4a5568;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .toggle-btn:hover {
        background: #e2e8f0;
    }
    
    /* Logo styling */
    .sidebar-logo {
        text-align: center;
        padding: 20px 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 15px;
    }
    
    .sidebar-logo img {
        height: 100px;
        width: 100px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .sidebar-logo h5 {
        margin-top: 10px;
        font-size: 16px;
        color: white;
    }
    
    .sidebar-logo p {
        font-size: 11px;
        color: #8a99b4;
        margin-bottom: 0;
    }
    
    /* User avatar */
    .user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-name {
        font-size: 14px;
        font-weight: 500;
        color: #1e293b;
    }
    
    /* Overlay for mobile when sidebar is open */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 998;
        transition: all 0.3s ease;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    @media (min-width: 769px) {
        .sidebar-overlay {
            display: none !important;
        }
    }
    
    /* Scrollbar */
    ::-webkit-scrollbar {
        width: 5px;
    }
    
    ::-webkit-scrollbar-track {
        background: #1f2937;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #4f9eff;
        border-radius: 5px;
    }
    
    /* Chevron animation */
    .nav-link .fa-chevron-down {
        transition: transform 0.2s;
    }
    
    .nav-link[aria-expanded="true"] .fa-chevron-down {
        transform: rotate(180deg);
    }
</style>
</head>
<body>

<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="p-3">
    <div class="sidebar-logo">
        <img src="/img/def.png" alt="Logo" onerror="this.src='https://placehold.co/80x80/4f9eff/white?text=SJ'">
        <h5>POS</h5>
        <p>Branch</p>
        <p> <?php echo $_SESSION['branch_name'] ?? ''; ?></p>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="?page=dashboard">
                <i class="fa fa-tachometer-alt"></i> Dashboard
            </a>
        </li>

        <!-- SALES -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuSales" role="button" aria-expanded="false">
                <i class="fa fa-cash-register"></i> Sales
                <i class="fa fa-chevron-down float-end mt-1" style="font-size: 12px;"></i>
            </a>
            <div class="collapse submenu" id="menuSales">
                <a class="nav-link" href="?page=pos">POS / New Sale</a>
                <a class="nav-link" href="?page=installment">Installments</a>
                <a class="nav-link" href="?page=sales-transaction">Sales Transactions</a>
                <a class="nav-link" href="?page=addcustomer">Customers</a>
            </div>
        </li>

        <!-- PRODUCTS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuProducts" role="button" aria-expanded="false">
                <i class="fa fa-mobile-alt"></i> Devices & Accessories
                <i class="fa fa-chevron-down float-end mt-1" style="font-size: 12px;"></i>
            </a>
            <div class="collapse submenu" id="menuProducts">
                <a class="nav-link" href="?page=mobiles">Mobile Phones</a>
                <a class="nav-link" href="?page=accessories">Accessories</a>
                <a class="nav-link" href="?page=others">Others</a>
            </div>
        </li>

        <!-- INVENTORY -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuInventory" role="button" aria-expanded="false">
                <i class="fa fa-box"></i> Inventory
                <i class="fa fa-chevron-down float-end mt-1" style="font-size: 12px;"></i>
            </a>
            <div class="collapse submenu" id="menuInventory">
                <a class="nav-link" href="?page=stock-in">Stock In</a>
                <a class="nav-link" href="?page=stock-out">Stock Out</a>
                <a class="nav-link" href="?page=stocktransfer">Stock Transfer</a>
                <a class="nav-link" href="?page=inventorycount">Inventory Count</a>
            </div>
        </li>

        <!-- TRANSACTIONS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuTransactions" role="button" aria-expanded="false">
                <i class="fa fa-receipt"></i> Transactions
                <i class="fa fa-chevron-down float-end mt-1" style="font-size: 12px;"></i>
            </a>
            <div class="collapse submenu" id="menuTransactions">
                <a class="nav-link" href="?page=returns-warranty">Returns / Warranty</a>
                <a class="nav-link" href="?page=repairs">Repairs</a>
            </div>
        </li>

        <!-- USERS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuUsers" role="button" aria-expanded="false">
                <i class="fa fa-users"></i> Users
                <i class="fa fa-chevron-down float-end mt-1" style="font-size: 12px;"></i>
            </a>
            <div class="collapse submenu" id="menuUsers">
                <a class="nav-link" href="?page=users">User List</a>
            </div>
        </li>

        <!-- REPORTS -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#menuReports" role="button" aria-expanded="false">
                <i class="fa fa-chart-bar"></i> Reports
                <i class="fa fa-chevron-down float-end mt-1" style="font-size: 12px;"></i>
            </a>
            <div class="collapse submenu" id="menuReports">
                <a class="nav-link" href="?page=salesreport">Sales Report</a>
                <a class="nav-link" href="?page=installmentreport">Installment Report</a>
                <a class="nav-link" href="?page=inventoryreport">Inventory Report</a>
                <a class="nav-link" href="?page=stockinoutreport">Stock In/Out Report</a>
                <a class="nav-link" href="?page=adminreport">Branch Report</a>
            </div>
        </li>

        <!-- SETTINGS -->
        
    </ul>
       <a class="nav-link text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
    <i class="fa fa-sign-out-alt"></i> Logout
</a>
        
</div>


<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: #dc3545; color: white;">
                <h5 class="modal-title"><i class="fas fa-sign-out-alt"></i> Confirm Logout</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-question-circle fa-3x mb-3" style="color: #dc3545;"></i>
                    <h5>Are you sure you want to logout?</h5>
                    <p class="text-muted">You will be redirected to the login page.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="/POS/verify.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>


<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <button class="toggle-btn" id="toggleSidebar">
            <i class="fa fa-bars"></i>
        </button>
        
        <div class="ms-auto user-info">
            <span class="user-name">Welcome, <?php echo $_SESSION['NAME'] ?? 'Admin'; ?></span>
            <img src="/POS/MainImg/user.png" class="user-avatar" onerror="this.src='https://placehold.co/38x38/4f9eff/white?text=U'">
        </div>
    </div>
</nav>

<!-- Content -->
<div id="content">
    <?php
        $page = $_GET['page'] ?? 'dashboard';

        // Basic security: prevent directory traversal
        $page = basename($page);

        $file = "pages/" . $page . ".php";

        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="alert alert-warning m-3">';
            echo "<h3><i class='fa fa-exclamation-triangle'></i> Page not found</h3>";
            echo "<p>The requested page '{$page}' does not exist.</p>";
            echo '<a href="?page=dashboard" class="btn btn-primary">Go to Dashboard</a>';
            echo '</div>';
        }
    ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const navbar = document.querySelector('.navbar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Check if mobile view
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Function to open sidebar
    function openSidebar() {
        sidebar.classList.add('active');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Function to close sidebar
    function closeSidebar() {
        sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Function to toggle sidebar
    function toggleSidebar() {
        if (isMobile()) {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        } else {
            // Desktop behavior - toggle collapsed state
            if (sidebar.style.marginLeft === '-260px') {
                sidebar.style.marginLeft = '0';
                content.style.marginLeft = '260px';
                navbar.style.marginLeft = '260px';
                navbar.style.left = '0px';
            } else {
                sidebar.style.marginLeft = '-260px';
                content.style.marginLeft = '0';
                navbar.style.marginLeft = '0';
                navbar.style.left = '0';
            }
        }
    }
    
    // Add click event to toggle button
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            // Desktop mode
            closeSidebar();
            if (sidebar.style.marginLeft !== '-260px') {
                sidebar.style.marginLeft = '0';
                content.style.marginLeft = '260px';
                navbar.style.marginLeft = '260px';
                navbar.style.left = '0px';
            }
        } else {
            // Mobile mode - ensure sidebar is closed by default
            if (!sidebar.classList.contains('active')) {
                sidebar.style.marginLeft = '';
                content.style.marginLeft = '';
                navbar.style.marginLeft = '';
                navbar.style.left = '';
            }
        }
    });
    
    // Close sidebar when clicking on a link (mobile)
    const allLinks = document.querySelectorAll('.nav-link');
    allLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile() && sidebar.classList.contains('active')) {
                // Don't close if clicking on dropdown toggle
                if (!this.getAttribute('data-bs-toggle')) {
                    setTimeout(closeSidebar, 300);
                }
            }
        });
    });
    
    // Highlight active menu item
    const currentPage = '<?php echo $page; ?>';
    const menuLinks = document.querySelectorAll('.submenu .nav-link, .nav-item > .nav-link[href*="page="]');
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes('page=' + currentPage)) {
            link.style.background = '#374151';
            link.style.color = '#fff';
            
            // Expand parent collapse
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                const bsCollapse = new bootstrap.Collapse(parentCollapse, { toggle: false });
                bsCollapse.show();
            }
        }
    });
    
    // Dashboard highlight
    if (currentPage === 'dashboard') {
        const dashLink = document.querySelector('.nav-link[href="?page=dashboard"]');
        if (dashLink) {
            dashLink.style.background = '#374151';
            dashLink.style.color = '#fff';
        }
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMobile() && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });
});
</script>

</body>
</html>