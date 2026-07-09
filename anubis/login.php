<?php
session_start();
require_once __DIR__ . '/DB/dbcon.php';

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {

        $stmt = $conn->prepare("
            SELECT top 1
                USERNAME,
                PASSWORD,
                ROLE,
                NAME_OF_USER,
                u.site as SITE,
                u.company as COMPANY,
                FUEL_COST
            FROM tc_web_users u
            left join tc_site s on u.site = s.site and s.company = u.company
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
            $_SESSION['FUEL_COST'] = $user['FUEL_COST'];

            if ($user['ROLE'] === 'ADMIN') {
                header("Location: /anubis/home.php");
            } else {
                header("Location: /anubis/user.php");
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
<link rel="icon" type="image/x-icon" href="/anubis/mainimg/pl.ico">

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ANUBIS | Secure Tracking System</title>

<style>
:root {
    --gold: #d4af37;
    --dark-gold: #b8860b;
    --sand: #c2a36b;
    --night: #0b0f1a;
    --panel: rgba(10, 12, 20, 0.88);
    --text: #f5e6c8;
    --error: #ff4d4d;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Georgia', serif;
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: url('/anubis/mainimg/bg.jpg') no-repeat center center/cover;
    color: var(--text);
    position: relative;
}

/* dark desert overlay */
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: radial-gradient(circle at top, rgba(0,0,0,0.4), rgba(0,0,0,0.92));
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

    /* remove static border */
    border: 1px solid rgba(212,175,55,0.2);

    box-shadow: 0 0 25px rgba(212,175,55,0.15);
    backdrop-filter: blur(6px);

    /* glowing animation */
    animation: glowPulse 3s infinite ease-in-out;
}

.logo {
    text-align: center;
    margin-bottom: 25px;
}

.logo img {
    width: 130px;
    filter: drop-shadow(0 0 10px var(--gold));
}

.logo h1 {
    font-size: 28px;
    color: var(--gold);
    letter-spacing: 3px;
    margin-top: 10px;
}

.logo p {
    font-size: 12px;
    color: var(--sand);
}

label {
    font-size: 13px;
    color: var(--sand);
    display: block;
    margin-bottom: 5px;
    letter-spacing: 1px;
}

input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid rgba(212,175,55,0.3);
    background: rgba(0,0,0,0.4);
    color: var(--text);
}

input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 10px rgba(212,175,55,0.2);
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 20px rgba(212,175,55,0.6);
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    letter-spacing: 1px;
    color: black;

    /* base gold */
    background: linear-gradient(90deg, #b8860b, #d4af37, #f5e6a3, #d4af37, #b8860b);
    background-size: 300% 100%;

    animation: goldMove 4s linear infinite;

    transition: 1.8s;
    position: relative;
    overflow: hidden;
}

@keyframes goldMove {
    0% {
        background-position: 0% 50%;
    }
    100% {
        background-position: 300% 50%;
    }
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 15px rgba(212,175,55,0.4);
}

.error {
    display: none;
    background: rgba(255, 77, 77, 0.15);
    border: 1px solid var(--error);
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    color: var(--error);
    text-align: center;
}

.error.show {
    display: block;
}

.footer {
    text-align: center;
    font-size: 11px;
    color: var(--sand);
    padding: 15px;
    position: relative;
    z-index: 1;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    margin-bottom: 15px;
    color: var(--sand);
}

.checkbox input {
    width: auto;
}

@keyframes glowPulse {
    0% {
        box-shadow: 0 0 10px rgba(212,175,55,0.15),
                    0 0 20px rgba(212,175,55,0.10);
        border-color: rgba(212,175,55,0.2);
    }

    50% {
        box-shadow: 0 0 25px rgba(212,175,55,0.5),
                    0 0 50px rgba(212,175,55,0.25);
        border-color: rgba(212,175,55,0.6);
    }

    100% {
        box-shadow: 0 0 10px rgba(212,175,55,0.15),
                    0 0 20px rgba(212,175,55,0.10);
        border-color: rgba(212,175,55,0.2);
    }
}

</style>
</head>

<body>

<div class="container">
    <div class="card">

        <div class="logo">
            <img src="/anubis/mainimg/pl.png" alt="ANUBIS">
            <h1>ANUBIS</h1>
            <p>Guardian of Real-Time Tracking</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error show"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/anubis/login.php">

            <label>USERNAME</label>
            <input type="text" name="username" required>

            <label>PASSWORD</label>
            <input type="password" name="password" required>

            <div class="checkbox">
                <input type="checkbox" id="remember">
                <label for="remember">Bind this device</label>
            </div>

            <button type="submit" name="login">ENTER THE GATE</button>

        </form>

    </div>
</div>

<div class="footer">
    © <?= date('Y') ?> ANUBIS SYSTEM • All rights reserved
</div>

</body>
</html>