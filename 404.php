<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Page Not Found</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Poppins', Roboto, 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #1e3ece 0%, #013f5c 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px 80px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .error-code {
            font-size: 140px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #e0d4ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 16px;
            letter-spacing: -4px;
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            color: white;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
        }

        a {
            display: inline-block;
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            transition: all 0.2s ease;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        @media (max-width: 600px) {
            .container {
                padding: 40px 30px;
            }
            .error-code {
                font-size: 90px;
                letter-spacing: -2px;
            }
            h1 {
                font-size: 22px;
            }
            p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <a href="/">← Back to Home</a>
    </div>
</body>

</html>