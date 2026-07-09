<?php
// index.php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: /anubis/login.php");
    exit();
}

if ($_SESSION['Role'] != 'ADMIN') {
    header("Location: /anubis/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<link rel="icon" type="image/x-icon" href="/anubis/mainimg/pl.ico">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>ANUBIS</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

:root{
    --gold:#d4af37;
    --gold-dark:#8b6b1b;
    --bg:#050505;
    --panel:rgba(12,12,12,.95);
    --text:#f6e7b0;
    --muted:#bfa76a;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    overflow-x:hidden;
    background:
    linear-gradient(rgba(0,0,0,.86), rgba(0,0,0,.92));
    background-size:cover;
    background-position:center;
    background-attachment:fixed;
    color:var(--text);
    font-family:'Segoe UI',sans-serif;
}

/* SCROLLBAR */

::-webkit-scrollbar{
    width:6px;
}

::-webkit-scrollbar-thumb{
    background:var(--gold);
    border-radius:20px;
}

::-webkit-scrollbar-track{
    background:#111;
}

/* SIDEBAR */

#sidebar{
    width:270px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:rgba(7,7,7,.96);
    backdrop-filter:blur(12px);
    border-right:1px solid rgba(212,175,55,.15);
    transition:all .3s ease;
    z-index:1000;
    overflow-y:auto;
    display: flex;
    flex-direction: column;

}

@media(max-width:768px){
    #sidebar{
        left:-270px;
    }

    #sidebar.active{
        left:0;
    }
}

/* SIDEBAR LOGO WITH GLOWING EFFECT */
.sidebar-logo{
    text-align:center;
    padding:25px 10px;
    border-bottom:1px solid rgba(212,175,55,.12);
    margin-bottom:15px;
}

.sidebar-logo img{
    width:135px;
    height:135px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid rgba(212,175,55,.2);
    box-shadow:0 0 25px rgba(212,175,55,.18);
    transition: all 0.3s ease;
}

/* Glowing effect outside the logo */
.sidebar-logo img:hover {
    border-color: rgba(212,175,55,0.6);
    box-shadow: 
        0 0 20px rgba(212,175,55,0.3),
        0 0 40px rgba(212,175,55,0.2),
        0 0 60px rgba(212,175,55,0.1);
    transform: scale(1.02);
}

/* Continuous glowing animation (optional) */
@keyframes glowPulse {
    0% {
        box-shadow: 0 0 20px rgba(212,175,55,0.15);
    }
    50% {
        box-shadow: 0 0 35px rgba(212,175,55,0.3),
                    0 0 50px rgba(212,175,55,0.15);
    }
    100% {
        box-shadow: 0 0 20px rgba(212,175,55,0.15);
    }
}

/* Apply animation to logo (optional - remove if not wanted) */
.sidebar-logo img {
    animation: glowPulse 3s ease-in-out infinite;
}

.sidebar-logo h5{
    margin-top:12px;
    color:var(--gold);
    font-weight:700;
    letter-spacing:5px;
}

.sidebar-logo p{
    color:var(--muted);
    font-size:11px;
    letter-spacing:1px;
}

/* Sidebar Navigation - takes remaining space */
.sidebar-nav {
    flex: 1;
    
}

/* NAV LINKS */

#sidebar .nav-link{
    color:#d6c28a;
    padding:12px 16px;
    border-radius:12px;
    margin:4px 0;
    transition:all .25s ease;
    font-size:14px;
    border:1px solid transparent;
}

#sidebar .nav-link:hover{
    background:rgba(212,175,55,.08);
    border-color:rgba(212,175,55,.15);
    color:#fff2c7;
    transform:translateX(4px);
}

#sidebar .nav-link i{
    width:24px;
    color:var(--gold);
}

.submenu{
    padding-left:20px;
}

/* Mobile User Info - Bottom of Sidebar */
.mobile-user-bottom {
    display: none;
    padding: 15px 20px;
    margin-top: auto;
    border-top: 1px solid rgba(212, 175, 55, 0.15);
    background: rgba(0, 0, 0, 0.3);
}

.mobile-user-bottom .user-name-bottom {
    color: var(--gold);
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

.navbar{
    position:fixed;
    top:0;
    left:270px;
    right:0;
    z-index:999;
    background:rgba(8,8,8,.92)!important;
    backdrop-filter:blur(10px);
    border-bottom:1px solid rgba(212,175,55,.12);
    padding:14px 18px;
}

@media(max-width:768px){
    .navbar{
        left:0;
    }
}

/* CONTENT */

#content{
    margin-left:270px;
    margin-top:80px;
    padding:22px;
    transition:all .3s ease;
}

@media(max-width:768px){
    #content{
        margin-left:0;
    }
}

/* TOGGLE */

.toggle-btn{
    border:none;
    background:rgba(212,175,55,.08);
    color:var(--gold);
    width:42px;
    height:42px;
    border-radius:12px;
    transition:.25s;
}

.toggle-btn:hover{
    background:rgba(212,175,55,.18);
    transform:scale(1.05);
}

/* USER - Hidden on mobile */
.user-avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid rgba(212,175,55,.25);
}

.user-name{
    color:var(--text);
    font-size:14px;
    font-weight:600;
}

.user-info{
    display:flex;
    align-items:center;
    gap:12px;
}

@media (max-width: 768px) {
    .user-info {
        display: none !important;
    }
}

/* COMPANY */

.company-name{
    color:var(--gold);
    font-size:15px;
    font-weight:700;
    letter-spacing:1px;
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

.sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    z-index:998;
}

.sidebar-overlay.active{
    display:block;
}

/* ACTIVE MENU */

.active-custom{
    background:rgba(212,175,55,.12)!important;
    border:1px solid rgba(212,175,55,.2)!important;
    color:#fff!important;
}

/* MODAL */

.modal-content{
    background:#111!important;
    color:var(--text);
    border:1px solid rgba(212,175,55,.15);
}

.modal-header{
    border-bottom:1px solid rgba(212,175,55,.12)!important;
}

.modal-footer{
    border-top:1px solid rgba(212,175,55,.12)!important;
}

.btn-danger{
    border:none;
}

.btn-secondary{
    background:#2c2c2c;
    border:none;
}

/* CHEVRON */

.fa-chevron-down{
    transition:.25s;
}

.nav-link[aria-expanded="true"] .fa-chevron-down{
    transform:rotate(180deg);
}

</style>

</head>

<body>

<!-- MOBILE OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->

<div id="sidebar" class="p-3">

    <div class="sidebar-logo">

        <img src="/anubis/mainimg/pl.png" alt="ANUBIS">

        <h5>ANUBIS</h5>

        <p>Tracking Management</p>

    </div>

    <div class="sidebar-nav">
        <ul class="nav flex-column">

            <li class="nav-item">
                <a class="nav-link" href="?page=dashboard">
                    <i class="fa fa-chart-line"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">

                <a class="nav-link" data-bs-toggle="collapse" href="#menuTransactions">

                    <i class="fa fa-eye"></i> Tracking

                    <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>

                </a>

                <div class="collapse submenu" id="menuTransactions">

                    <a class="nav-link" href="?page=liveview">Live view</a>

                    <a class="nav-link" href="?page=route-replay">Route Replay</a>

                    <a class="nav-link" href="?page=consumption">Consumption View</a>

                    <a class="nav-link" href="?page=performance-view">Performance View</a>

                </div>

            </li>

            <li class="nav-item">

                <a class="nav-link" data-bs-toggle="collapse" href="#menuDevices">

                    <i class="fa fa-location"></i> Devices

                    <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>

                </a>

                <div class="collapse submenu" id="menuDevices">

                    <a class="nav-link" href="?page=managedevices">Manage Devices</a>

                    <a class="nav-link" href="?page=maintenance">Maintenance</a>

                    <a class="nav-link" href="?page=monitoring">Monitoring</a>

                </div>

            </li>

            <li class="nav-item">

                <a class="nav-link" data-bs-toggle="collapse" href="#menureports">

                    <i class="fa fa-file"></i> Reports

                    <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>

                </a>

                <div class="collapse submenu" id="menureports">

                    <a class="nav-link" href="?page=devices">Device list</a>

                    <a class="nav-link" href="?page=trackings">Trackings</a>

                    <a class="nav-link" href="?page=fuel-consumption">Fuel Consumption</a>

                    <a class="nav-link" href="?page=performance">Performance</a>

                    <a class="nav-link" href="?page=trips">Trips</a>

                    <a class="nav-link" href="?page=maintenance">Maintenance</a>

                    <a class="nav-link" href="?page=monitoring">Monitoring</a>

                    <a class="nav-link" href="?page=alerts">Alerts</a>

                </div>

            </li>

             <li class="nav-item">

                <a class="nav-link" data-bs-toggle="collapse" href="#menuSettings">

                    <i class="fa fa-gear"></i> Settings

                    <i class="fa fa-chevron-down float-end mt-1" style="font-size:12px;"></i>

                </a>

                <div class="collapse submenu" id="menuSettings">

                    <a class="nav-link" href="?page=users">Users</a>

                    <a class="nav-link" href="?page=apikeys">API Keys</a>   

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

            <div class="modal-header bg-danger text-white">

                <h5 class="modal-title">

                    <i class="fas fa-sign-out-alt"></i> Confirm Logout

                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body text-center">

                <i class="fas fa-question-circle fa-3x mb-3 text-warning"></i>

                <h5>Are you sure you want to logout?</h5>

                <p class="text-secondary">
                    You will be redirected to login page.
                </p>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                    Cancel

                </button>

                <a href="/anubis/verify.php"
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

                <i class="fa-solid fa-eye"></i>

                <?php echo $_SESSION['COMPANY'] ?? ''; ?>

            </p>

        </div>

        <div class="ms-auto user-info">

            <span class="user-name">

                Welcome, <?php echo $_SESSION['NAME'] ?? 'Admin'; ?>

            </span>

            <img src="/anubis/MainImg/user.png"
                 class="user-avatar">

        </div>

    </div>

</nav>

<!-- CONTENT -->

<div id="content" style="height:calc(100vh - 85px); overflow:hidden; padding:15px;">

<?php

$page = $_GET['page'] ?? 'dashboard';

$page = basename($page);

$file = "pages/" . $page . ".php";

if(file_exists($file)){

    // LIVE VIEW PAGE FULLSCREEN STYLE
    if($page == 'liveview'){

        echo '
        <style>

            #content{
                padding:0 !important;
                margin-top:70px;
                height:calc(100vh - 70px);
                overflow:hidden;
                background:#000;
            }

            .liveview-wrapper{
                width:100%;
                height:100%;
                position:relative;
                overflow:hidden;
                border-radius:0;
            }

        </style>

        <div class=\"liveview-wrapper\">';

        include $file;

        echo '</div>';

    }else{

        // NORMAL PAGES
        echo '<div class=\"container-fluid py-2\">';

        include $file;

        echo '</div>';
    }

}else{

    echo '
    <div class="container-fluid">

        <div class="alert alert-warning border-0 shadow-sm rounded-4">

            <h4 class="mb-2">
                <i class="fa fa-exclamation-triangle text-warning"></i>
                Page not found
            </h4>

            <p class="mb-3">
                The requested page does not exist.
            </p>

            <a href="?page=dashboard" class="btn btn-dark rounded-3">
                <i class="fa fa-home"></i>
                Back to Dashboard
            </a>

        </div>

    </div>';

}

?>

</div>

<!-- SCRIPTS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function(){

    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const navbar = document.querySelector('.navbar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function isMobile(){
        return window.innerWidth <= 768;
    }

    function openSidebar(){
        sidebar.classList.add('active');
        overlay.classList.add('active');
    }

    function closeSidebar(){
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }

    toggleBtn.addEventListener('click', function(){

        if(isMobile()){

            if(sidebar.classList.contains('active')){
                closeSidebar();
            }else{
                openSidebar();
            }

        }else{

            if(sidebar.style.marginLeft === '-270px'){

                sidebar.style.marginLeft = '0';
                content.style.marginLeft = '270px';
                navbar.style.left = '270px';

            }else{

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

        if(href && href.includes('page=' + currentPage)){

            link.classList.add('active-custom');

            const parentCollapse = link.closest('.collapse');

            if(parentCollapse){

                const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                    toggle:false
                });

                bsCollapse.show();
            }
        }

    });

});

</script>

</body>
</html>