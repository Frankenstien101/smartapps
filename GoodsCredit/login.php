<?php
session_start();

 require_once __DIR__ . '/DB/dbcon.php';

$error = '';
$success = '';

// Handle login form submission
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? ''); // Trim whitespace
    $password = $_POST['password'] ?? '';

    try {
        // Prepare and execute query
        $stmt = $conn->prepare("
            SELECT 
                USERNAME,PASSWORDHASH,ROLE,FULLNAME,BranchName
            FROM users
                
            WHERE USERNAME = :username
        ");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR); // Bind username as a string
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Compare passwords (case-sensitive)
            if ($user['PASSWORDHASH'] === $password) {
                // Password matches
                $_SESSION['username'] = $user['USERNAME'];
                $_SESSION['NAME'] = $user['FULLNAME'];
                $_SESSION['Role'] = $user['ROLE']; 
                $_SESSION['branch_name'] = $user['BranchName'];

                if ($user['ROLE'] === 'admin') {
                      header("Location: /SIDJAN/home.php");
                } else {
                       header("Location: /SIDJAN/user.php");
                }
                // Redirect to homepage
             
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

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
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
            color: #00d2ff;
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

        /* Form styles */
        .input-group {
            margin-bottom: 24px;
        }

        .input-group label {
            display: block;
            color: #e0e7ff;
            font-size: 14px;
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
            font-size: 18px;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(0, 210, 255, 0.2);
            border-radius: 24px;
            font-size: 15px;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }

        .input-wrapper input:focus {
            border-color: #00d2ff;
            background: rgba(0, 210, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(0, 210, 255, 0.2);
        }

        .input-wrapper input::placeholder {
            color: #5f7f9e;
        }

        /* Options row */
        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 28px;
            font-size: 13px;
        }

        .checkbox-label {
            color: #a0b3d9;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #00d2ff;
        }

        .forgot-link {
            color: #00d2ff;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #5ce0ff;
            text-decoration: underline;
        }

        /* Login button */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            border: none;
            border-radius: 40px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-family: 'Inter', sans-serif;
        }

        .login-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(0, 210, 255, 0.4);
        }

        /* Alert messages */
        .alert {
            padding: 12px 16px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-size: 14px;
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

        /* Demo credentials hint */
        .demo-hint {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: 12px;
            color: #7b8cae;
        }

        .demo-hint strong {
            color: #00d2ff;
            font-weight: 500;
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

        <div class="options">
            <label class="checkbox-label">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" name="login" class="login-btn">
            <i class="fas fa-arrow-right-to-bracket"></i> Sign In
        </button>
    </form>

   
</div>
    </div>

    <!-- JavaScript to generate dynamic sound wave bars -->
    <script>
        // Generate animated wave bars dynamically
        const waveContainer = document.getElementById('waveContainer');
        const barCount = 40;
        for (let i = 0; i < barCount; i++) {
            const bar = document.createElement('div');
            bar.classList.add('wave-bar');
            // Randomize height range and animation delay for organic feel
            const randomDelay = (Math.random() * 1).toFixed(2);
            const randomDuration = (0.8 + Math.random() * 0.8).toFixed(2);
            bar.style.animationDelay = `${randomDelay}s`;
            bar.style.animationDuration = `${randomDuration}s`;
            bar.style.height = `${15 + Math.random() * 40}px`;
            waveContainer.appendChild(bar);
        }

        // Add floating particles effect on mouse move (subtle)
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