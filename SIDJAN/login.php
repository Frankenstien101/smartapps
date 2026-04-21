<?php
session_start();

include 'DB/dbcon.php';

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? ''); // Trim whitespace
    $password = $_POST['password'] ?? '';

    try {
        // Prepare and execute query
        $stmt = $conn->prepare("
            SELECT 
                u.[UserID],
                u.[Username],
                u.[Password],
                u.[Role],
                u.[Name_of_user],
                u.[Company],
                u.[Site],
                u.[Status],
                c.[ID] AS Company_ID,
                c.[CODE],
                c.[NAME] AS Company_Name,
                c.[ADDRESS],
                c.[STATUS] AS Company_Status,
                c.[KEY_LETTER],
                c.[REPORT_HEADER],
                c.[REPORT_SUB_HEADER],
                c.[REPORT_SUB_HEADER2]
            FROM 
                [dbo].[Aquila_Users] u
            INNER JOIN 
                [dbo].[Aquila_COMPANY] c
                ON u.Company = c.ID 
            WHERE u.Username = :username
        ");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR); // Bind username as a string
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Compare passwords (case-sensitive)
            if ($user['Password'] === $password) {
                // Password matches
                $_SESSION['username'] = $username;
                $_SESSION['Name_of_user'] = $user['Name_of_user'];
                $_SESSION['Company_Name'] = $user['Company_Name'];
                $_SESSION['UserID'] = $user['UserID'];
                $_SESSION['Company_ID'] = $user['Company_ID']; 
                $_SESSION['Role'] = $user['Role']; 

                // Redirect to homepage
                header("Location: HomePage/home.php");
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
?>

<!doctype html>
<html lang="en">
  <head>
    <link rel="icon" type="image/x-icon" href="MainImg/bscr.ico">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | BLUESYS DMS</title>
    <style>
      :root {
        --primary: #0d05a1ff;         /* Indigo */
        --primary-light: #050891ff;
        --primary-dark: #080066ff;
        --accent: #10b981;         /* Emerald */
        --text: #f8fafc;           /* Light gray */
        --text-light: #e2e8f0;
        --text-muted: #94a3b8;
        --bg-dark: #0f172a;       /* Dark slate */
        --card-bg: rgba(15, 23, 42, 0.9);
        --error: #ef4444;
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background-image: url('/SIDJAN/mainimg/BG.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
      }

      body::before {
        content: "";
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(2, 6, 23, 0.85);  /* Darker overlay */
        z-index: 0;
      }

      .container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        position: relative;
        z-index: 1;
      }

      .card {
        width: 100%;
        max-width: 420px;
        background: var(--card-bg);
        border-radius: 12px;
        backdrop-filter: blur(8px);
        padding: 2.5rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 50px rgba(0, 0, 0, 0.2);
        
      }

      .logo {
        text-align: center;
        margin-bottom: 2rem;
      }

      .logo img {
        height: 48px;
        filter: brightness(1.1);
      }

      .logo h1 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-top: 0.75rem;
        color: var(--text);
        letter-spacing: 0.5px;
      }

      h2 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1.75rem;
        text-align: center;
        color: var(--text);
      }

      .form-group {
        margin-bottom: 1.5rem;
      }

      label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: var(--text-light);
      }

      input {
        width: 100%;
        padding: 0.875rem;
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        font-size: 0.9375rem;
        color: var(--text);
        transition: all 0.25s ease;
      }

      input::placeholder {
        color: var(--text-muted);
      }

      input:focus {
        outline: none;
        border-color: var(--primary);
        background: rgba(30, 41, 59, 0.8);
        box-shadow: 0 0 0 2px rgba(12, 6, 122, 0.25);
      }

      button {
        width: 100%;
        padding: 0.875rem;
        background-color: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.9375rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        margin-top: 0.5rem;
      }

      button:hover {
        background-color: var(--primary-dark);
        transform: translateY(-1px);
      }

      .error {
        color: var(--error);
        background: rgba(239, 68, 68, 0.15);
        font-size: 0.875rem;
        margin: 1.25rem 0;
        padding: 0.875rem;
        border-radius: 8px;
        text-align: center;
        display: none;
      }

      .error.show {
        display: block;
      }

      .footer {
        text-align: center;
        padding: 1.5rem;
        font-size: 0.75rem;
        color: var(--text-muted);
        position: relative;
        z-index: 1;
      }

      .checkbox-container {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
      }

      .checkbox-container input {
        width: auto;
        margin-right: 0.75rem;
        accent-color: var(--primary);
      }

      .checkbox-container label {
        margin-bottom: 0;
        color: var(--text-light);
        font-size: 0.875rem;
      }

      .nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
        background-color: rgba(3, 35, 109, 0.8);
        backdrop-filter: blur(12px);
        position: relative;
        z-index: 1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }

      .nav-logo {
        display: flex;
        align-items: center;
      }

      .nav-logo img {
        height: 36px;
        margin-right: 0.75rem;
      }

      .nav-logo span {
        font-weight: 600;
        color: var(--text);
        font-size: 1.05rem;
      }

      .nav-links {
        display: flex;
        gap: 1.5rem;
      }

      .nav-links a {
        text-decoration: none;
        color: var(--text-light);
        font-size: 0.875rem;
        font-weight: 500;
        transition: color 0.2s ease;
      }

      .nav-links a:hover {
        color: var(--accent);
      }

      @media (max-width: 640px) {
        .nav {
          padding: 1rem;
          flex-direction: column;
          gap: 0.75rem;
        }
        
        .nav-links {
          gap: 1rem;
        }
        
        .card {
          padding: 2rem 1.5rem;
        }
      }
    </style>
  </head>

  <body>


    <div class="container">
      <div class="card">
        <div class="logo">
          <img src="/SIDJAN/mainimg/logo.png" alt=" Logo" Style = "height:105px; width:100px">
          <h1>POINT OF SALE</h1>
        </div>

        <?php if (!empty($error)): ?>
          <div class="error show"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="DMS.php">
          <div class="form-group">
            <label for="username">Username</label>
            <input 
              type="text" 
              id="username"
              name="username" 
              placeholder="Enter your username" 
              required 
              autofocus
            >
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              placeholder="Enter your password" 
              required>
          </div>

          <div class="checkbox-container">
            <input type="checkbox" id="remember">
            <label for="remember">Remember this device</label>
          </div>

          <button type="submit" name="login">Sign In</button>
        </form>
      </div>
    </div>

    <div class="footer">
      © <?= date('Y') ?> BLUESYS. All rights reserved.
    </div>
  </body>
</html>