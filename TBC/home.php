<?php
// index.php — Tele-Back Check (Modern Navy/Ocean Theme)

session_start();

if (!isset($_SESSION['username'])) {
    header("Location: /tbc/login.php");
    exit();
}

// ==================== DATABASE CONNECTION ====================
require_once __DIR__ . '/DB/dbcon.php';

// Check if user is ADMIN
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';

// Set default session values if not exists
if (!isset($_SESSION['SITE'])) {
    $_SESSION['SITE'] = 'NO SITE ASSIGNED';
}
if (!isset($_SESSION['PRINCIPAL'])) {
    $_SESSION['PRINCIPAL'] = 'KFI';
}

// Fetch Sites & Principals
try {
    $stmt = $conn->query("
        SELECT DISTINCT [SITE], [PRINCIPAL]
        FROM [TBC].[dbo].[sites]
        ORDER BY [SITE], [PRINCIPAL]
    ");

    $sitesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sites = array_unique(array_column($sitesData, 'SITE'));
    $principals = array_unique(array_column($sitesData, 'PRINCIPAL'));

} catch (Exception $e) {
    $sites = ["DVO", "MNL", "CEB"];
    $principals = ["SS", "ABC"];
    error_log($e->getMessage());
}

// Current selections
// SITE: Only ADMIN can change, non-ADMIN uses session value
// PRINCIPAL: Both ADMIN and non-ADMIN can change
if ($isAdmin) {
    // Admin can change site and principal
    $currentSite = $_POST['site'] ?? ($_GET['site'] ?? $_SESSION['SITE']);
    $currentPrin = $_POST['princ'] ?? ($_GET['princ'] ?? $_SESSION['PRINCIPAL']);
    $_SESSION['SITE'] = $currentSite;
    $_SESSION['PRINCIPAL'] = $currentPrin;
    $siteSelectDisabled = '';
    $princSelectDisabled = '';
} else {
    // Non-admin: SITE is locked (from session), PRINCIPAL is enabled
    $currentSite = $_SESSION['SITE'];
    $currentPrin = $_POST['princ'] ?? ($_GET['princ'] ?? $_SESSION['PRINCIPAL']);
    $_SESSION['PRINCIPAL'] = $currentPrin;
    $siteSelectDisabled = 'disabled';
    $princSelectDisabled = ''; // PRINCIPAL is enabled for non-admin
}

// Routing
$page = $_GET['page'] ?? 'dashboard';
$page = basename($page);

// Get sidebar state from cookie (default: expanded)
$sidebarCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';

function h($s){
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tele-Back Check | <?= h(ucfirst($page)) ?></title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --bg: #f8fafc;
        --surface: #ffffff;
        --surface-2: #f1f5f9;
        --border: #e2e8f0;
        --text: #0f172a;
        --text-2: #475569;
        --navy: #0f172a;
        --navy-light: #1e2937;
        --ocean: #0284c8;
        --sky: #38bdf8;
        --accent: #22d3ee;
        --sidebar-width: 268px;
        --sidebar-collapsed-width: 78px;
    }

    [data-theme="dark"] {
        --bg: #0f172a;
        --surface: #1e2937;
        --surface-2: #334155;
        --border: #475569;
        --text: #f1f5f9;
        --text-2: #cbd5e1;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'DM Sans', sans-serif;
        font-size: 14.5px;
        color: var(--text);
        background: var(--bg);
        margin: 0;
        overflow-x: hidden;
    }

    /* SIDEBAR */
    #sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: linear-gradient(180deg, var(--navy) 0%, var(--navy-light) 100%);
        color: #e0f2fe;
        z-index: 1000;
        box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
        overflow-y: auto;
    }

    #sidebar.collapsed { width: var(--sidebar-collapsed-width); }
    #sidebar.collapsed .sidebar-brand-text,
    #sidebar.collapsed .nav-link span,
    #sidebar.collapsed .nav-section,
    #sidebar.collapsed .sidebar-footer .nav-link span { display: none; }
    
    #sidebar.collapsed .brand-logo { margin: 0 auto; }
    #sidebar.collapsed .sidebar-brand { justify-content: center !important; padding: 24px 8px; }
    #sidebar.collapsed .nav-link { justify-content: center; padding: 12px; margin: 4px; }
    #sidebar.collapsed .nav-link i { margin: 0; font-size: 1.3rem; }

    .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); flex-shrink: 0; transition: padding 0.3s ease; }
    .brand-logo { width: 48px; height: 48px; background: linear-gradient(135deg, var(--ocean), var(--sky)); border-radius: 14px; display: grid; place-items: center; font-size: 22px; flex-shrink: 0; }
    .brand-text { transition: opacity 0.2s ease; }

    .sidebar-menu { flex: 1; overflow-y: auto; overflow-x: hidden; padding-bottom: 100px; }
    .sidebar-menu::-webkit-scrollbar { width: 6px; }
    .sidebar-menu::-webkit-scrollbar-track { background: transparent; }
    .sidebar-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.18); border-radius: 20px; }

    .nav-section { padding: 20px 20px 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #7dd3fc; white-space: nowrap; }
    .nav-link { color: #e0f2fe; padding: 12px 20px; margin: 4px 8px; border-radius: 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: all .25s ease; white-space: nowrap; }
    .nav-link:hover { background: rgba(255,255,255,0.12); transform: translateX(6px); color: #fff; }
    .nav-link.active-link { background: rgba(56,189,248,0.2); border-left: 4px solid var(--sky); font-weight: 600; }
    .nav-link i { width: 22px; text-align: center; font-size: 1.1rem; }

    .sidebar-footer { position: sticky; bottom: 0; width: 100%; padding: 12px; border-top: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.96); backdrop-filter: blur(10px); }

    /* NAVBAR */
    .navbar {
        position: fixed;
        top: 0;
        left: var(--sidebar-width);
        right: 0;
        height: 70px;
        background: rgba(255,255,255,0.97);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--border);
        z-index: 999;
        padding: 0 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #sidebar.collapsed ~ .navbar { left: var(--sidebar-collapsed-width); }

    /* CONTENT */
    #content {
        margin-left: var(--sidebar-width);
        margin-top: 70px;
        padding: 32px;
        min-height: calc(100vh - 70px);
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #sidebar.collapsed ~ #content { margin-left: var(--sidebar-collapsed-width); }

    .kpi-card { background: var(--surface); border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(15,23,42,0.08); border: 1px solid var(--border); }
    .welcome-banner { background: linear-gradient(135deg, var(--navy), var(--navy-light)); color: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
    .theme-toggle { width: 40px; height: 40px; border-radius: 50%; background: var(--surface-2); border: 1px solid var(--border); display: grid; place-items: center; cursor: pointer; }

    /* Mobile Responsive */
    .mobile-menu-btn { display: none; }

    @media (max-width:768px) {
        :root { --sidebar-width: 280px; --sidebar-collapsed-width: 0px; }
        #sidebar { left: -280px; transition: left 0.3s ease; }
        #sidebar.mobile-open { left: 0; }
        .navbar { left: 0 !important; }
        #content { margin-left: 0 !important; padding: 20px; }
        .mobile-menu-btn { display: flex; }
        .desktop-toggle { display: none; }
    }

    @media (min-width:769px) {
        .mobile-only { display: none !important; }
        .desktop-only { display: flex; }
    }

    /* Disabled select styling */
    select:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        background: #e9ecef;
    }
    
    /* Site indicator for non-admin */
    .site-badge {
        background: #e2e8f0;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #0f172a;
        display: none;
    }
    </style>
</head>

<body>

<!-- MOBILE OVERLAY -->
<div class="position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-50 d-none" id="sidebarOverlay" style="z-index:998;"></div>

<!-- SIDEBAR -->
<div id="sidebar" class="<?= $sidebarCollapsed ? 'collapsed' : '' ?>">
    <div class="sidebar-brand d-flex align-items-center gap-3 <?= $sidebarCollapsed ? 'justify-content-center' : '' ?>">
        <div class="brand-logo"><i class="fa fa-headset"></i></div>
        <div class="brand-text <?= $sidebarCollapsed ? 'd-none' : '' ?>">
            <div class="h5 mb-0 text-white" id="brandText">Tele Caller App</div>
            <small class="opacity-75" id="brandDesc">Bluesun Philippines Inc</small>
        </div>
    </div>

    <div class="sidebar-menu">
        <div class="p-3">
            <div class="nav-section <?= $sidebarCollapsed ? 'd-none' : '' ?>">MAIN</div>
            <a href="?page=dashboard" class="nav-link <?= $page==='dashboard' ? 'active-link' : '' ?>" data-tooltip="Dashboard">
                <i class="fa fa-house"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Dashboard</span>
            </a>

            <div class="nav-section mt-3 <?= $sidebarCollapsed ? 'd-none' : '' ?>">CALLS</div>
            <a href="?page=newcall" class="nav-link" data-tooltip="New Call"><i class="fa fa-phone-volume"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>New Call</span></a>
            <a href="?page=previous-calls" class="nav-link" data-tooltip="Previous Calls"><i class="fa fa-clock-rotate-left"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Previous Calls</span></a>

            <div class="nav-section mt-3 <?= $sidebarCollapsed ? 'd-none' : '' ?>">CUSTOMERS</div>
            <a href="?page=add-customers" class="nav-link" data-tooltip="Add Customer"><i class="fa fa-user-plus"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Add Customer</span></a>

            <div class="nav-section mt-3 <?= $sidebarCollapsed ? 'd-none' : '' ?>">ANALYTICS</div>
            <a href="?page=report" class="nav-link" data-tooltip="Reports"><i class="fa fa-file-lines"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Reports</span></a>
            <a href="?page=call-report" class="nav-link" data-tooltip="Performance"><i class="fa fa-chart-line"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Performance</span></a>

            <div class="nav-section mt-3 <?= $sidebarCollapsed ? 'd-none' : '' ?>">SYSTEM</div>
            <a href="?page=settings" class="nav-link" data-tooltip="Settings"><i class="fa fa-gear"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Settings</span></a>
            <?php if ($isAdmin): ?>
            <a href="?page=accounts" class="nav-link" data-tooltip="Account Approval">
                <i class="fa fa-circle-check"></i>
                <span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Account Approval</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar-footer">
        <a href="#" class="nav-link text-danger m-0" data-bs-toggle="modal" data-bs-target="#logoutModal" data-tooltip="Logout">
            <i class="fa fa-right-from-bracket"></i><span <?= $sidebarCollapsed ? 'class="d-none"' : '' ?>>Logout</span>
        </a>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-link text-dark fs-4 mobile-menu-btn" id="mobileMenuToggle"><i class="fa fa-bars"></i></button>
        <button class="btn btn-link text-dark desktop-toggle" id="desktopCollapseToggle" style="padding: 8px;">
            <i class="fa <?= $sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left' ?>"></i>
        </button>
    </div>

    <div class="ms-auto d-flex align-items-center gap-3">
        <div class="theme-toggle" id="themeToggle"><i class="fa fa-moon"></i></div>

        <form method="post" class="d-flex gap-2 align-items-center">
            <!-- SITE SELECTOR - Only Admin can change -->
            <div class="d-flex align-items-center gap-1">
                <select name="site" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()" <?= $siteSelectDisabled ?>>
    <?php foreach($sites as $s): ?>
        <option value="<?= h($s) ?>" <?= $s === $currentSite ? 'selected' : '' ?>><?= h($s) ?></option>
    <?php endforeach; ?>
</select>
                <?php if (!$isAdmin): ?>
                    <small class="text-muted" style="font-size: 10px;">
                        <i class="fa fa-lock"></i>
                    </small>
                <?php endif; ?>
            </div>

            <!-- PRINCIPAL SELECTOR - Enabled for both Admin and Non-Admin -->
            <select name="princ" class="form-select form-select-sm" style="width:100px" onchange="this.form.submit()" <?= $princSelectDisabled ?>>
                <?php foreach($principals as $p): ?>
                    <option value="<?= h($p) ?>" <?= $p === $currentPrin ? 'selected' : '' ?>><?= h($p) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Display current site for non-admin -->
        <?php if (!$isAdmin): ?>
            <span class="site-badge">
                <i class="fa fa-building"></i> <?= h($currentSite) ?>
            </span>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold"><?= h($_SESSION['NAME']) ?></span>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-size:14px;font-weight:700;">
                <?= strtoupper(substr($_SESSION['NAME'],0,2)) ?>
            </div>
        </div>
    </div>
</nav>

<!-- CONTENT -->
<div id="content">
<?php
$page = $_GET['page'] ?? 'dashboard';
$page = basename($page);
$file = "page/" . $page . ".php";
if (file_exists($file)) {
    include $file;
} else {
    echo '<div class="alert alert-warning"><h4><i class="fa fa-triangle-exclamation"></i> Page Not Found</h4><p>The requested page does not exist.</p></div>';
}
?>
</div>

<!-- LOGOUT MODAL -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <h5>Sign Out?</h5>
                <p class="text-muted">You will be redirected to login.</p>
                <a href="/tbc/verify.php" class="btn btn-danger">Yes, Sign Out</a>
                <button class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

function setSidebarCookie(collapsed) {
    document.cookie = `sidebar_collapsed=${collapsed}; path=/; max-age=${60*60*24*365}`;
}

function updateDesktopSidebar(collapsed) {
    if (window.innerWidth >= 769) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            document.getElementById('brandText').style.display = 'none';
            document.getElementById('brandDesc').style.display = 'none';
            document.getElementById('sidebarOverlay').classList.remove('d-none');
        } else {
            sidebar.classList.remove('collapsed');
            document.getElementById('sidebarOverlay').classList.add('d-none');
            document.getElementById('brandText').style.display = 'block';
            document.getElementById('brandDesc').style.display = 'block';
        }
        const toggleBtn = document.getElementById('desktopCollapseToggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) icon.className = collapsed ? 'fa fa-angles-right' : 'fa fa-angles-left';
        }
        setSidebarCookie(collapsed);
    }
}

document.getElementById('desktopCollapseToggle')?.addEventListener('click', function(e) {
    e.preventDefault();
    updateDesktopSidebar(!sidebar.classList.contains('collapsed'));
});

document.getElementById('mobileMenuToggle')?.addEventListener('click', function(e) {
    e.preventDefault();
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('d-none');
    document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
});

overlay?.addEventListener('click', function() {
    sidebar.classList.remove('mobile-open');
    overlay.classList.add('d-none');
    document.body.style.overflow = '';
});

document.querySelectorAll('#sidebar .nav-link').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.add('d-none');
            document.body.style.overflow = '';
        }
    });
});

let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        if (window.innerWidth >= 769) {
            sidebar.classList.remove('mobile-open');
            overlay?.classList.add('d-none');
            document.body.style.overflow = '';
            const savedState = document.cookie.split('; ').find(row => row.startsWith('sidebar_collapsed='));
            const isCollapsed = savedState ? savedState.split('=')[1] === 'true' : false;
            if (isCollapsed) sidebar.classList.add('collapsed');
            else sidebar.classList.remove('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('mobile-open');
            overlay?.classList.add('d-none');
            document.body.style.overflow = '';
        }
    }, 150);
});

document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth >= 769) {
        const savedState = document.cookie.split('; ').find(row => row.startsWith('sidebar_collapsed='));
        const isCollapsed = savedState ? savedState.split('=')[1] === 'true' : false;
        if (isCollapsed) sidebar.classList.add('collapsed');
    } else {
        sidebar.classList.remove('collapsed');
        sidebar.classList.remove('mobile-open');
    }
});

// Theme Toggle
const themeToggle = document.getElementById('themeToggle');
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.setAttribute('data-theme','dark');
    themeToggle.innerHTML = '<i class="fa fa-sun"></i>';
}
themeToggle.addEventListener('click', () => {
    if (document.documentElement.hasAttribute('data-theme')) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme','light');
        themeToggle.innerHTML = '<i class="fa fa-moon"></i>';
    } else {
        document.documentElement.setAttribute('data-theme','dark');
        localStorage.setItem('theme','dark');
        themeToggle.innerHTML = '<i class="fa fa-sun"></i>';
    }
});
</script>
</body>
</html>