<?php
session_start();

require_once __DIR__ . '/DB/dbcon.php';

$error = '';
$success = '';
$activeForm = 'login'; // 'login' or 'signup'

// Handle login form submission
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $conn->prepare("
            SELECT USERNAME, PASSWORD, ROLE, FULLNAME, BRANCH, STATUS
            FROM users
            WHERE USERNAME = :username
        ");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if account is approved
            if ($user['STATUS'] === 'PENDING') {
                $error = "Your account is pending approval. Please wait for admin approval.";
            } elseif ($user['STATUS'] === 'REJECTED') {
                $error = "Your account has been rejected. Please contact support.";
        
            } elseif ($user['PASSWORD'] === $password) {
                $_SESSION['username'] = $user['USERNAME'];
                $_SESSION['NAME'] = $user['FULLNAME'];
                $_SESSION['role'] = $user['ROLE'];
                $_SESSION['SITE'] = $user['BRANCH'];
                $_SESSION['user_logged_in'] = true;
                
                header("Location: /TBC/home.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . htmlspecialchars($e->getMessage());
    }
}

// Handle signup form submission
if (isset($_POST['signup'])) {
    $username = trim($_POST['signup_username'] ?? '');
    $password = $_POST['signup_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $branch = $_POST['branch'] ?? '';
    
    // Validation
    if (empty($username) || empty($password) || empty($fullname) || empty($branch)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if username already exists
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE USERNAME = :username");
            $checkStmt->bindParam(':username', $username, PDO::PARAM_STR);
            $checkStmt->execute();
            $exists = $checkStmt->fetchColumn();
            
            if ($exists > 0) {
                $error = "Username already exists. Please choose another.";
            } else {
                // Insert new user (status = PENDING, role = USER by default)
                $insertStmt = $conn->prepare("
                    INSERT INTO users (USERNAME, PASSWORD, FULLNAME, ROLE, STATUS, BRANCH)
                    VALUES (:username, :password, :fullname, 'TELE-CALLER', 'PENDING', :branch)
                ");
                $insertStmt->bindParam(':username', $username, PDO::PARAM_STR);
                $insertStmt->bindParam(':password', $password, PDO::PARAM_STR);
                $insertStmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
                $insertStmt->bindParam(':branch', $branch, PDO::PARAM_STR);
                
                if ($insertStmt->execute()) {
                    $success = "Account created successfully! Please wait for admin approval.";
                    $activeForm = 'login';
                } else {
                    $error = "Failed to create account. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TBC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Animated Calling Background */
        .calling-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, #0a0f1c 0%, #0a1a2e 50%, #0b0f1a 100%);
        }

        /* Animated waves / sound visualization */
        .wave-container {
            position: absolute;
            bottom: 20%;
            left: 0;
            width: 100%;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 12px;
            pointer-events: none;
        }

        .wave-bar {
            width: 12px;
            background: linear-gradient(to top, #00d2ff, #3a7bd5);
            border-radius: 20px;
            animation: soundWave 1.2s ease-in-out infinite alternate;
            box-shadow: 0 0 12px rgba(0, 210, 255, 0.6);
        }

        @keyframes soundWave {
            0% {
                height: 20px;
                opacity: 0.5;
            }
            100% {
                height: 120px;
                opacity: 1;
            }
        }

        /* Floating particles / calling signals */
        .signal-pulse {
            position: absolute;
            top: 30%;
            left: 15%;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(0, 210, 255, 0.1);
            border: 2px solid rgba(0, 210, 255, 0.4);
            animation: pulseRing 2.5s infinite;
        }

        .signal-pulse:nth-child(2) {
            top: 60%;
            left: 25%;
            width: 120px;
            height: 120px;
            animation-delay: 0.8s;
        }

        .signal-pulse:nth-child(3) {
            top: 20%;
            left: 70%;
            width: 100px;
            height: 100px;
            animation-delay: 1.5s;
        }

        .signal-pulse:nth-child(4) {
            top: 70%;
            left: 80%;
            width: 90px;
            height: 90px;
            animation-delay: 0.4s;
        }

        @keyframes pulseRing {
            0% {
                transform: scale(0.5);
                opacity: 0.6;
            }
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        /* Rotating phone icon */
        .phone-icon {
            position: absolute;
            left: 10%;
            top: 40%;
            font-size: 120px;
            color: rgba(0, 210, 255, 0.15);
            animation: rotatePhone 8s linear infinite;
            pointer-events: none;
        }

        @keyframes rotatePhone {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Glowing orb effect */
        .glow-orb {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0,210,255,0.15) 0%, rgba(0,210,255,0) 70%);
            border-radius: 50%;
            top: 50%;
            left: 20%;
            transform: translate(-50%, -50%);
            filter: blur(60px);
            animation: floatGlow 6s ease-in-out infinite;
        }

        @keyframes floatGlow {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
            50% { transform: translate(-50%, -55%) scale(1.1); opacity: 0.8; }
        }

        /* Main container */
        .login-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            min-height: 100vh;
            padding-right: 8%;
        }

        /* Right side card */
        .login-card {
            background: rgba(18, 25, 45, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 48px 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(0, 210, 255, 0.15);
            transition: transform 0.3s ease;
            animation: fadeSlideUp 0.6s ease-out;
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand i {
            font-size: 56px;
            background: linear-gradient(135deg, #00d2ff, #3a7bd5);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .brand h2 {
            font-size: 28px;
            font-weight: 700;
            margin-top: 12px;
            background: linear-gradient(135deg, #ffffff, #a0c4ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .brand p {
            color: #8e9aaf;
            font-size: 14px;
            margin-top: 6px;
        }

        /* Tab Styles */
        .form-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(0, 210, 255, 0.2);
        }

        .tab-btn {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            color: #8e9aaf;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-btn.active {
            color: #00d2ff;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #00d2ff;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { width: 0; }
            to { width: 100%; }
        }

        .tab-btn:hover:not(.active) {
            color: #5ce0ff;
        }

        /* Form styles */
        .form-panel {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .form-panel.active-panel {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            color: #e0e7ff;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            color: #5f7f9e;
            font-size: 16px;
        }

        .input-wrapper input, .input-wrapper select {
            width: 100%;
            padding: 12px 16px 12px 44px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(0, 210, 255, 0.2);
            border-radius: 24px;
            font-size: 14px;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }

        .input-wrapper select {
            cursor: pointer;
            appearance: none;
        }

        .input-wrapper select option {
            background: #1a2a3e;
            color: white;
        }

        .input-wrapper input:focus, .input-wrapper select:focus {
            border-color: #00d2ff;
            background: rgba(0, 210, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(0, 210, 255, 0.2);
        }

        .input-wrapper input::placeholder {
            color: #5f7f9e;
        }

        /* Submit button */
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            border: none;
            border-radius: 40px;
            color: white;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(0, 210, 255, 0.4);
        }

        /* Alert messages */
        .alert {
            padding: 12px 16px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(255, 85, 85, 0.15);
            border-left: 3px solid #ff5555;
            color: #ffb3b3;
        }

        .alert-success {
            background: rgba(85, 255, 170, 0.15);
            border-left: 3px solid #55ffaa;
            color: #b3ffd9;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                justify-content: center;
                padding-right: 5%;
                padding-left: 5%;
            }
            .login-card {
                padding: 36px 28px;
            }
            .wave-container {
                display: none;
            }
            .phone-icon {
                opacity: 0.2;
            }
        }
    </style>
</head>
<body>
    <div class="calling-bg"></div>
    
    <!-- Animated calling background elements -->
    <div class="wave-container" id="waveContainer"></div>
    <div class="signal-pulse"></div>
    <div class="signal-pulse"></div>
    <div class="signal-pulse"></div>
    <div class="signal-pulse"></div>
    <div class="phone-icon">
        <i class="fas fa-phone-alt"></i>
    </div>
    <div class="glow-orb"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="brand">
                <i class="fas fa-phone-volume"></i>
                <h2>TBC</h2>
                <p>Tele-calling application</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="form-tabs">
                <button type="button" class="tab-btn <?php echo $activeForm === 'login' ? 'active' : ''; ?>" data-form="login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <button type="button" class="tab-btn <?php echo $activeForm === 'signup' ? 'active' : ''; ?>" data-form="signup">
                    <i class="fas fa-user-plus"></i> Sign Up
                </button>
            </div>

            <!-- Login Form -->
            <div class="form-panel <?php echo $activeForm === 'login' ? 'active-panel' : ''; ?>" id="loginPanel">
                <form method="POST" action="">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" name="login" class="submit-btn">
                        <i class="fas fa-arrow-right-to-bracket"></i> Sign In
                    </button>
                </form>
            </div>

            <!-- Signup Form -->
            <div class="form-panel <?php echo $activeForm === 'signup' ? 'active-panel' : ''; ?>" id="signupPanel">
                <form method="POST" action="">
                    <div class="input-group">
                        <label for="fullname">Full Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user-circle"></i>
                            <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="signup_username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="signup_username" name="signup_username" placeholder="Choose a username" value="<?php echo isset($_POST['signup_username']) ? htmlspecialchars($_POST['signup_username']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="branch">Branch</label>
                        <div class="input-wrapper">
                            <i class="fas fa-building"></i>
                            <select id="branch" name="branch" required>
                                <option value="">Select Branch</option>
                                <option value="KOR" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'KOR') ? 'selected' : ''; ?>>KOR</option>
                                <option value="DVO" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'DVO') ? 'selected' : ''; ?>>DVO</option>
                                <option value="BXU" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'BXU') ? 'selected' : ''; ?>>BXU</option>
                                <option value="CDO" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'CDO') ? 'selected' : ''; ?>>CDO</option>
                                <option value="OZA" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'OZA') ? 'selected' : ''; ?>>OZA</option>
                                <option value="ZAM" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'ZAM') ? 'selected' : ''; ?>>ZAM</option>
                             </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="signup_password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="signup_password" name="signup_password" placeholder="Minimum 6 characters" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-check-circle"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                    </div>

                    <button type="submit" name="signup" class="submit-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                <div class="demo-hint" style="margin-top: 20px; text-align: center; font-size: 11px;">
                    <i class="fas fa-info-circle"></i> Account will be pending admin approval
                </div>
            </div>
        </div>
    </div>

    <script>
        // Generate animated wave bars dynamically
        const waveContainer = document.getElementById('waveContainer');
        const barCount = 40;
        for (let i = 0; i < barCount; i++) {
            const bar = document.createElement('div');
            bar.classList.add('wave-bar');
            const randomDelay = (Math.random() * 1).toFixed(2);
            const randomDuration = (0.8 + Math.random() * 0.8).toFixed(2);
            bar.style.animationDelay = `${randomDelay}s`;
            bar.style.animationDuration = `${randomDuration}s`;
            bar.style.height = `${15 + Math.random() * 40}px`;
            waveContainer.appendChild(bar);
        }

        // Tab switching functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const loginPanel = document.getElementById('loginPanel');
        const signupPanel = document.getElementById('signupPanel');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const formType = btn.getAttribute('data-form');
                
                // Update active tab
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Show corresponding panel
                if (formType === 'login') {
                    loginPanel.classList.add('active-panel');
                    signupPanel.classList.remove('active-panel');
                } else {
                    signupPanel.classList.add('active-panel');
                    loginPanel.classList.remove('active-panel');
                }
            });
        });

        // Floating particles effect on mouse move
        document.body.addEventListener('mousemove', (e) => {
            const orb = document.querySelector('.glow-orb');
            if (orb) {
                const x = e.clientX / window.innerWidth;
                const y = e.clientY / window.innerHeight;
                orb.style.transform = `translate(${x * 20 - 50}%, ${y * 20 - 55}%) scale(1.05)`;
            }
        });
    </script>
</body>
</html>