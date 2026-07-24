<?php
require_once './config/database.php';
require_once './includes/auth.php';
require_once './includes/csrf.php';
require_once './includes/helpers.php';

redirectIfLoggedIn();

$error = '';
$ip = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF validation failed.";
    } elseif (!checkLoginAttempts($ip)) {
        $error = "Too many login attempts. Please try again in 15 minutes.";
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        logLoginAttempt($ip);

        if (!empty($username) && !empty($password)) {
            // Fetch user along with their assigned store (if any)
            $stmt = $pdo->prepare("
                SELECT u.*, c.customer_name AS store_name
                FROM users u
                LEFT JOIN customers c ON u.customer_id = c.customer_id
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                clearLoginAttempts($ip);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['customer_id'] = $user['customer_id'];  // store the store ID
                $_SESSION['store_name'] = $user['store_name'];    // store the store name

                // Load assigned stores for SalesRep
                if ($user['role'] === 'SalesRep') {
                    $stmtAssigned = $pdo->prepare("SELECT customer_id FROM user_stores WHERE user_id = ?");
                    $stmtAssigned->execute([$user['user_id']]);
                    $assigned = $stmtAssigned->fetchAll(PDO::FETCH_COLUMN);
                    $_SESSION['assigned_stores'] = $assigned;
                } else {
                    $_SESSION['assigned_stores'] = [];
                }

                header("Location: modules/dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Please fill in all fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlueSun · Digital Call Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bluesun.css">
</head>
<body>
<div class="bs-login-shell">
    <div class="bs-login-brand">
        <div>
            <span class="bs-brand-mark"><i class="bi bi-sun-fill" style="color:#0F2A3D;"></i></span>
        </div>
        <div class="bs-login-tagline">
            Stock the right amount.<br>
            <span class="accent">Every time.</span>
        </div>
        <div style="position:relative;z-index:1;">
            <p style="max-width:380px; color:#B9CBD8; font-size:0.92rem; line-height:1.6;">
                BlueSun's call sheets turn sales history, lead time, and safety stock
                into a suggested order — reviewed by a buyer before it ever becomes a PO.
            </p>
            <span class="bs-brand-sub" style="color:#7C93A3;">BlueSun Philippines &middot; Digital Call Sheet System</span>
        </div>
    </div>
    <div class="bs-login-form-side">
        <div class="bs-login-card">
            <h2 style="margin-bottom:0.2rem;">Sign in</h2>
            <p style="color:var(--ink-soft); font-size:0.9rem; margin-bottom:1.6rem;">Use your BlueSun account to continue.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn w-100" style="background:var(--sun); color:var(--ink); font-weight:700;">
                    Sign in <i class="bi bi-arrow-right"></i>
                </button>
            </form>
            <hr>
            
        </div>
    </div>
</div>
</body>
</html>