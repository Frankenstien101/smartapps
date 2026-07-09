<?php
session_start();
require_once __DIR__ . '/DB/dbcon.php';

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {

        $stmt = $conn->prepare("
            SELECT TOP 1
                USERNAME,
                PASSWORD,
                ROLE,
                NAME_OF_USER,
                SITE,
                COMPANY
            FROM users 
            WHERE USERNAME = :username
        ");

        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['PASSWORD'] === $password) {

            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['NAME'] = $user['NAME_OF_USER'];
            $_SESSION['Role'] = $user['ROLE'];
            $_SESSION['SITE'] = $user['SITE'];
            $_SESSION['COMPANY'] = $user['COMPANY'];

            if ($user['ROLE'] === 'ADMIN') {
                header("Location: /taps/home.php");
            } else {
                header("Location: /taps/user.php");
            }

            exit();

        } else {
            $error = "Access denied. Invalid credentials.";
        }

    } catch (PDOException $e) {
        $error = "System error: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="/taps/mainimg/taps.ico">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TAPS | Time and Payroll System</title>

    <style>
        :root {
            --maroon: #862d2d;
            --dark-maroon: #4d1a1a;
            --light-maroon: #b84a4a;
            --black: #0a0a0a;
            --dark-black: #1a0c0c;
            --panel: rgba(20, 10, 10, 0.92);
            --text: #f0e6e6;
            --error: #ff4d4d;
            --shadow: #4d1e1e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--dark-black);
            color: var(--text);
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 30% 20%, rgba(134, 45, 45, 0.15), rgba(10, 10, 10, 0.95));
            z-index: 0;
        }

        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
            position: relative;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 440px;
            background: var(--panel);
            border-radius: 12px;
            padding: 2.5rem;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(134, 45, 45, 0.3);
            box-shadow: 0 0 30px rgba(134, 45, 45, 0.2);
            backdrop-filter: blur(8px);
            animation: glowPulse 3s infinite ease-in-out;
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo img {
            width: 200px;
            height: 210px;
            filter: drop-shadow(0 0 15px rgba(134, 45, 45, 0.6));
        }

        .logo h1 {
            font-size: 32px;
            color: var(--light-maroon);
            letter-spacing: 4px;
            margin-top: 10px;
            text-shadow: 0 0 20px rgba(134, 45, 45, 0.3);
        }

        .logo .subtitle {
            font-size: 13px;
            color: #b88a8a;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        .logo .tagline {
            font-size: 11px;
            color: #664444;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        label {
            font-size: 13px;
            color: #b88a8a;
            display: block;
            margin-bottom: 5px;
            letter-spacing: 1px;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid rgba(134, 45, 45, 0.3);
            background: rgba(0, 0, 0, 0.5);
            color: var(--text);
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--light-maroon);
            box-shadow: 0 0 15px rgba(134, 45, 45, 0.3);
            background: rgba(0, 0, 0, 0.7);
        }

        input::placeholder {
            color: #664444;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            letter-spacing: 2px;
            color: white;
            background: linear-gradient(90deg, #4d1a1a, #862d2d, #b84a4a, #862d2d, #4d1a1a);
            background-size: 300% 100%;
            animation: maroonMove 4s linear infinite;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
            font-size: 14px;
            text-transform: uppercase;
        }

        @keyframes maroonMove {
            0% {
                background-position: 0% 50%;
            }
            100% {
                background-position: 300% 50%;
            }
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(134, 45, 45, 0.5);
        }

        button:active {
            transform: translateY(0px);
        }

        .error {
            display: none;
            background: rgba(255, 77, 77, 0.12);
            border: 1px solid var(--error);
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            color: var(--error);
            text-align: center;
            font-size: 13px;
        }

        .error.show {
            display: block;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            color: #664444;
            padding: 15px;
            position: relative;
            z-index: 1;
            letter-spacing: 1px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            margin-bottom: 15px;
            color: #b88a8a;
        }

        .checkbox input {
            width: auto;
            margin-bottom: 0;
            accent-color: var(--maroon);
        }

        .checkbox label {
            margin-bottom: 0;
            cursor: pointer;
        }

        @keyframes glowPulse {
            0% {
                box-shadow: 0 0 15px rgba(134, 45, 45, 0.15);
                border-color: rgba(134, 45, 45, 0.2);
            }
            50% {
                box-shadow: 0 0 35px rgba(134, 45, 45, 0.4);
                border-color: rgba(134, 45, 45, 0.5);
            }
            100% {
                box-shadow: 0 0 15px rgba(134, 45, 45, 0.15);
                border-color: rgba(134, 45, 45, 0.2);
            }
        }

        /* clock icon in corner */
        .clock-icon {
            position: fixed;
            bottom: 60px;
            right: 30px;
            font-size: 40px;
            color: rgba(134, 45, 45, 0.15);
            z-index: 0;
            animation: floatClock 6s ease-in-out infinite;
        }

        @keyframes floatClock {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-10px) rotate(5deg);
            }
        }

        @media (max-width: 480px) {
            .card {
                padding: 1.8rem;
            }
            .logo h1 {
                font-size: 26px;
            }
            .logo img {
                width: 100px;
            }
            .clock-icon {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="clock-icon">
        <i class="fas fa-clock"></i>
    </div>

    <div class="container">
        <div class="card">

            <div class="logo">
                <img src="/taps/mainimg/logo.png" alt="TAPS">
                <div class="subtitle">Time and Payroll System</div>
                <div class="tagline">Secure · Accurate · Reliable</div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error show"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/taps/login.php">

                <label>USERNAME</label>
                <input type="text" name="username" placeholder="Enter your username" required>

                <label>PASSWORD</label>
                <input type="password" name="password" placeholder="Enter your password" required>

                <div class="checkbox">
                    <input type="checkbox" id="remember">
                    <label for="remember">Remember this device</label>
                </div>

                <button type="submit" name="login">
                    <i class="fas fa-clock"></i> Clock In
                </button>

            </form>

        </div>
    </div>

    <div class="footer">
        © <?= date('Y') ?> TAPS - Time and Payroll System • All rights reserved
    </div>

    <!-- Font Awesome for clock icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</body>
</html>