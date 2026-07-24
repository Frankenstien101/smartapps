<?php

http_response_code(404);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>404 - Page Not Found</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #0f0f17;
      --text: #010b18;
      --primary: #865801;
      --primary-dark: #775803;
      --gray: #64748b;
      --dark-gray: #334155;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    .container {
      text-align: center;
      max-width: 480px;
    }

    .error-code {
      font-size: 9rem;
      font-weight: 700;
      line-height: 0.9;
      background: linear-gradient(90deg, #ac7602, #4d3b00);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 1rem;
    }

    h1 {
      font-size: 2.25rem;
      margin-bottom: 1rem;
      color: black;
    }

    .subtitle {
      font-size: 1.15rem;
      color: var(--gray);
      margin-bottom: 2.5rem;
    }

    .btn-group {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn {
      padding: 0.9rem 1.8rem;
      font-size: 1.05rem;
      font-weight: 500;
      border-radius: 0.6rem;
      text-decoration: none;
      transition: all 0.22s ease;
      cursor: pointer;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
      width: 10px;
    }

    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(2, 3, 58, 0.25);
    }

    .btn-outline {
      background: transparent;
      border: 1px solid var(--dark-gray);
      color: var(--text);
    }

    .btn-outline:hover {
      background: rgba(255,255,255,0.06);
      border-color: var(--gray);
    }

    .illustration {
      margin: 3rem 0 1rem;
      opacity: 0.9;
    }

    .small {
      font-size: 0.95rem;
      color: var(--gray);
      margin-top: 3rem;
    }

    @media (max-width: 480px) {
      .error-code { font-size: 6rem; }
      h1 { font-size: 1.8rem; }
      .btn { padding: 0.85rem 1.5rem; }
    }
  </style>
</head>
<body >

  <div class="container" >
    <div class="error-code">404</div>
    <h1>Page Not Found</h1>
    <p class="subtitle">
      The page you're looking for doesn't exist or has been moved.  
    </p>

    <div class="btn-group">
      <button class="btn btn-primary" onclick="history.back()" style = "width:120px">
        ← Go Back
      </button>
      
    </div>

    <div class="small">
      If you believe this is an error, please contact support.
    </div>
  </div>

  <script>
    window.addEventListener('load', () => {
      if (history.length <= 1) {
        document.querySelector('.btn-primary').style.display = 'none';
      }
    });
  </script>

</body>
</html>