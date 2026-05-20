<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BSPI Helpdesk - Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-outer: #353842;
      --bg-card: #242731;
      --input-bg: #323644;
      --accent: #2b81ff;
      --text-main: #ffffff;
      --text-muted: #8c93a1;
      --border-dash: #4b5563;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      background-color: var(--bg-outer);
      font-family: 'Inter', "Segoe UI", Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      -webkit-font-smoothing: antialiased;
    }

    .login-container {
      width: 100%;
      max-width: 960px;
      min-height: 600px;
      background-color: var(--bg-card);
      border-radius: 20px;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
      position: relative;
      overflow: hidden;
      display: flex;
      margin: 20px;
    }

    .bg-image-layer {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: #000000;
      background-image: url('https://drive.google.com/thumbnail?id=1ZboRWePsfAg__vxzfesqwdiDljDook4D&sz=w1000');
      background-repeat: no-repeat;
      background-position: right 5% center;
      background-size: 55%;
      filter: blur(1px);
      z-index: 1;
    }

    .bg-image-layer::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to right, var(--bg-card) 40%, rgba(36,39,49,0.9) 55%, rgba(0,0,0,0.1) 100%);
      z-index: 2;
    }

    .curve-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 3;
      pointer-events: none;
    }

    .content-panel {
      position: relative;
      z-index: 4;
      width: 55%;
      padding: 40px 50px;
      display: flex;
      flex-direction: column;
    }

    .top-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      color: var(--text-main);
      font-weight: 700;
      font-size: 15px;
      letter-spacing: 0.5px;
    }

    .logo-circle {
      width: 20px;
      height: 20px;
      background: var(--accent);
      border-radius: 50%;
    }

    .main-form-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      max-width: 400px;
    }

    .header-small {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--text-muted);
      margin-bottom: 12px;
      font-weight: 600;
    }

    .main-title {
      font-size: 38px;
      font-weight: 800;
      line-height: 1.15;
      margin: 0 0 12px 0;
      letter-spacing: -0.02em;
      color: var(--text-main);
    }

    .title-dot {
      color: var(--accent);
    }

    .motto {
      font-size: 13px;
      color: var(--text-muted);
      line-height: 1.6;
      margin: 0 0 35px 0;
      max-width: 95%;
    }

    .input-group {
      background: var(--input-bg);
      border-radius: 12px;
      padding: 10px 16px;
      margin-bottom: 16px;
      border: 1px solid transparent;
      transition: all 0.3s ease;
    }

    .input-group:focus-within {
      border-color: var(--accent);
      box-shadow: 0 0 0 1px var(--accent);
    }

    .input-group label {
      display: block;
      font-size: 11px;
      color: var(--text-muted);
      margin-bottom: 4px;
      font-weight: 500;
    }

    .input-group input {
      width: 100%;
      background: transparent;
      border: none;
      color: var(--text-main);
      font-size: 14px;
      outline: none;
      font-family: inherit;
    }

    .input-group input::placeholder {
      color: #6a7185;
    }

    .form-actions {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-top: 10px;
    }

    .btn-signin {
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 24px;
      padding: 14px 32px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
      transition: background 0.2s ease, transform 0.1s ease;
    }

    .btn-signin:hover {
      background: #1a6cdb;
    }

    .btn-signin:active {
      transform: scale(0.97);
    }

    .btn-signin:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    .footer-copy {
      font-size: 11px;
      color: #5c6275;
      font-weight: 500;
      margin-top: auto;
    }

    .error-box {
      background: rgba(220, 38, 38, 0.1);
      border: 1px solid rgba(220, 38, 38, 0.2);
      color: #f87171;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 20px;
      display: none;
    }

    /* Demo credentials hint */
    .demo-hint {
      background: rgba(43, 129, 255, 0.1);
      border: 1px solid rgba(43, 129, 255, 0.2);
      color: var(--text-muted);
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 12px;
      margin-top: 20px;
      text-align: center;
    }

    @media (max-width: 850px) {
      .login-container {
        flex-direction: column;
        max-width: 450px;
        min-height: auto;
      }
      .content-panel {
        width: 100%;
        padding: 40px;
      }
      .bg-image-layer {
        background-size: cover;
        background-position: center center;
      }
      .bg-image-layer::after {
        background: linear-gradient(to bottom, var(--bg-card) 50%, rgba(0,0,0,0.6) 100%);
      }
      .curve-overlay {
        display: none;
      }
      .main-title { font-size: 32px; }
      .footer-copy { margin-top: 40px; }
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="bg-image-layer"></div>
  <svg class="curve-overlay" viewBox="0 0 100 100" preserveAspectRatio="none">
    <path d="M48,0 C68,35 28,65 48,100" fill="none" stroke="var(--border-dash)" stroke-width="0.3" stroke-dasharray="1.2, 1.2" />
  </svg>

  <div class="content-panel">
    <div class="top-logo">
      <div class="logo-circle"></div>
      <span>BDSC Helpdesk</span>
    </div>
    
    <div class="main-form-area">
      <div class="header-small">Welcome Back</div>
      
      <h1 class="main-title">Bluesun Digital<br>Support Center<span class="title-dot">.</span></h1>
      <p class="motto">Ability to Think & Do, Ability to Lead & Teach, Ability to Check & Fix.</p>
      
      <div id="errorBox" class="error-box"></div>

      <div class="input-group">
        <label>Username</label>
        <input type="text" id="username" placeholder="Enter username" autocomplete="off" onkeydown="checkEnter(event)">
      </div>
      
      <div class="input-group">
        <label>Password</label>
        <input type="password" id="password" placeholder="••••••••" autocomplete="off" onkeydown="checkEnter(event)">
      </div>
      
      <div class="form-actions">
        <button class="btn-signin" id="btnLogin" onclick="doLogin()">Sign In</button>
      </div>

      <!-- Demo hint for local testing -->
      <div class="demo-hint">
        🔐 Demo credentials: username: <strong>admin</strong> / password: <strong>password</strong>
      </div>
    </div>

    <div class="footer-copy">
      © 2026 BSPI. All rights reserved.
    </div>
  </div>
</div>

<script>
  // Mock login function for local testing
  // Replace this with your actual authentication logic
  
  function checkEnter(e) {
    if (e.key === "Enter") {
      doLogin();
    }
  }

  function showError(msg) {
    const errBox = document.getElementById("errorBox");
    errBox.style.display = "block";
    errBox.textContent = msg;
  }

  function hideError() {
    const errBox = document.getElementById("errorBox");
    errBox.style.display = "none";
    errBox.textContent = "";
  }

  function doLogin() {
  const u = document.getElementById("username").value.trim();
  const p = document.getElementById("password").value.trim();
  const btn = document.getElementById("btnLogin");
  const errBox = document.getElementById("errorBox");

  errBox.style.display = "none";

  if (!u || !p) {
    showError("Please enter both username and password.");
    return;
  }

  btn.disabled = true;
  btn.textContent = "Authenticating...";

  google.script.run
    .withSuccessHandler(function(res) {
      if (!res || res.status !== "success") {
        showError(res.message || "Invalid credentials.");
        btn.disabled = false;
        btn.textContent = "Sign In";
        return;
      }
      btn.textContent = "Redirecting...";
      window.top.location.href = res.redirectUrl;
    })
    .withFailureHandler(function(err) {
      showError("Connection error. Please try again.");
      btn.disabled = false;
      btn.textContent = "Sign In";
    })
    .loginUser(u, p);
}

  // Optional: Clear error when typing
  document.getElementById("username").addEventListener("input", hideError);
  document.getElementById("password").addEventListener("input", hideError);
</script>

</body>
</html>