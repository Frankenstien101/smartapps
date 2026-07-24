<?php
$bs_current = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/csrf.php';  // For csrfField()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlueSun · Digital Call Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/bluesun.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
</head>
<body>
<div class="bs-shell">
    <aside class="bs-sidebar" id="bsSidebar">
        <div class="bs-brand">
            <span class="bs-brand-mark"><i class="bi bi-sun-fill" style="color:#0F2A3D;"></i></span>
            <span>
                <span class="bs-brand-text">Blue<strong>Sun</strong></span>
                <span class="bs-brand-sub">Call Sheet System</span>
            </span>
        </div>
        <nav class="bs-nav">
            <a class="bs-nav-link <?= $bs_current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <div class="bs-nav-divider">Catalog</div>
                <a class="bs-nav-link <?= $bs_current === 'products.php' ? 'active' : '' ?>" href="products.php">
                    <i class="bi bi-box-seam"></i> Products
                </a>
                <a class="bs-nav-link <?= $bs_current === 'customers.php' ? 'active' : '' ?>" href="customers.php">
                    <i class="bi bi-shop"></i> Customers
                </a>
                <a class="bs-nav-link <?= $bs_current === 'inventory.php' ? 'active' : '' ?>" href="inventory.php">
                    <i class="bi bi-boxes"></i> Inventory
                </a>
                <div class="bs-nav-divider">Administration</div>
                <a class="bs-nav-link <?= $bs_current === 'users.php' ? 'active' : '' ?>" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
                <a class="bs-nav-link <?= $bs_current === 'reports.php' ? 'active' : '' ?>" href="reports.php">
                    <i class="bi bi-bar-chart-line"></i> Reports
                </a>
                <a class="bs-nav-link <?= $bs_current === 'audit_log.php' ? 'active' : '' ?>" href="audit_log.php">
    <i class="bi bi-clipboard-check"></i> Audit Log
</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'SalesRep'): ?>
                <div class="bs-nav-divider">Field Sales</div>
                <a class="bs-nav-link <?= $bs_current === 'sales.php' ? 'active' : '' ?>" href="sales.php">
                    <i class="bi bi-cart-check"></i> Sales
                </a>
                <a class="bs-nav-link <?= in_array($bs_current, ['call_sheet.php','call_sheet_edit.php','call_sheet_view.php']) ? 'active' : '' ?>" href="call_sheet.php">
                    <i class="bi bi-clipboard-data"></i> Call Sheets
                </a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'Buyer'): ?>
                <div class="bs-nav-divider">Buying</div>
                <a class="bs-nav-link <?= in_array($bs_current, ['approval_list.php','approval.php']) ? 'active' : '' ?>" href="approval_list.php">
                    <i class="bi bi-check2-square"></i> Approvals
                </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'], ['Buyer', 'Admin'])): ?>
                <a class="bs-nav-link <?= in_array($bs_current, ['purchase_orders.php','po_view.php']) ? 'active' : '' ?>" href="purchase_orders.php">
                    <i class="bi bi-receipt"></i> Purchase Orders
                </a>
            <?php endif; ?>

            <div class="mt-3 px-3">
                <button id="darkToggle" class="btn btn-sm btn-outline-light w-100">
                    <i class="bi bi-moon"></i> Dark Mode
                </button>
            </div>
        </nav>
        <div class="bs-user">
            <div class="bs-user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <span class="bs-user-role"><?= htmlspecialchars($_SESSION['role']) ?></span>
            <?php if ($_SESSION['role'] === 'Buyer' && isset($_SESSION['store_name'])): ?>
                <div style="font-size:0.7rem; color:#7C93A3; margin-top:3px;">Store: <?= htmlspecialchars($_SESSION['store_name']) ?></div>
            <?php endif; ?>
            <a class="bs-logout" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Log out</a>
        </div>
    </aside>

    <main class="bs-main">
        <div class="bs-topbar">
            <button class="bs-topbar-toggle" onclick="document.getElementById('bsSidebar').classList.toggle('bs-open')">
                <i class="bi bi-list"></i>
            </button>
            <span class="bs-brand-text" style="color:#fff;">Blue<strong>Sun</strong></span>
            <span></span>
        </div>
        <div class="bs-content">
            <!-- Toast container -->
            <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;"></div>