<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>SmartApps | Compact Suite</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: radial-gradient(circle at 10% 20%, #0a0f1a, #05080c);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            color: #eef5ff;
            line-height: 1.4;
            overflow-x: hidden;
        }

        /* smaller scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #101520;
        }
        ::-webkit-scrollbar-thumb {
            background: #2c3e66;
            border-radius: 12px;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 1.2rem 1.5rem 1rem;
        }

        /* compact header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 0.6rem 1.2rem;
            background: rgba(15, 25, 45, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 2rem;
            border: 1px solid rgba(72, 120, 200, 0.3);
            box-shadow: 0 6px 14px -8px rgba(0, 0, 0, 0.5);
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF, #8ab3ff, #4f9eff);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
        }
        .logo::after {
            content: "⚡";
            font-size: 1rem;
            background: none;
            -webkit-background-clip: unset;
            color: #5d9eff;
            margin-left: 5px;
            display: inline-block;
        }

        .nav-links {
            display: flex;
            gap: 1.2rem;
        }
        .nav-links a {
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            color: #cfdfff;
            transition: all 0.2s;
            padding: 0.3rem 0;
            border-bottom: 1.5px solid transparent;
        }
        .nav-links a:hover {
            color: white;
            border-bottom-color: #4f9eff;
        }

        /* main content - compact spacing */
        main {
            animation: fadeUp 0.5s ease-out;
        }
        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(to right, #f0f6ff, #bbd4ff);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .intro-text {
            text-align: center;
            max-width: 550px;
            margin: 0 auto 1.6rem auto;
            font-size: 0.9rem;
            color: #b9ceff;
            background: rgba(30, 45, 75, 0.3);
            backdrop-filter: blur(4px);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            border: 1px solid rgba(80, 140, 220, 0.3);
        }

        /* cards grid - tighter but still airy */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.2rem;
            margin: 1.2rem 0 1.8rem;
        }

        /* compact card design */
        .app-card {
            background: linear-gradient(145deg, rgba(18, 28, 45, 0.85), rgba(10, 16, 28, 0.9));
            backdrop-filter: blur(6px);
            border-radius: 1.3rem;
            padding: 1rem 0.9rem 1rem;
            transition: all 0.25s ease;
            border: 1px solid rgba(70, 130, 220, 0.25);
            box-shadow: 0 8px 18px -8px rgba(0, 0, 0, 0.4);
            text-align: center;
            position: relative;
        }
        .app-card:hover {
            transform: translateY(-5px);
            border-color: rgba(80, 158, 255, 0.6);
            box-shadow: 0 12px 22px -10px #1e3a8a50;
            background: linear-gradient(145deg, rgba(25, 40, 65, 0.9), rgba(12, 20, 35, 0.95));
        }

        /* smaller logos */
        .app-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            margin: 0 auto 0.6rem;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.3));
            transition: transform 0.15s;
        }
        .app-card:hover .app-logo {
            transform: scale(1.02);
        }

        .app-name {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.2px;
            background: linear-gradient(115deg, #f0f5ff, #b8d0ff);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 0.3rem;
        }
        .app-description {
            font-size: 0.75rem;
            color: #b4c9f0;
            margin-bottom: 1rem;
            font-weight: 400;
            min-height: 36px;
            line-height: 1.3;
        }

        /* smaller, sleek buttons */
        .app-button {
            display: inline-block;
            background: rgba(20, 40, 70, 0.7);
            backdrop-filter: blur(4px);
            padding: 0.4rem 1rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.7rem;
            letter-spacing: 0.3px;
            color: #d6e6ff;
            border: 1px solid #2f5490;
            transition: all 0.2s;
        }
        .app-button:hover {
            background: #2c5a9e;
            color: white;
            border-color: #7aaeff;
            transform: scale(1.01);
        }

        /* fallback name for cards without explicit h2 (PA Order/LoadGuard) */
        .app-name-fallback {
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(115deg, #eef3ff, #bfd6ff);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin: 0.3rem 0 0.2rem;
        }

        footer {
            margin-top: 2rem;
            text-align: center;
            padding: 1rem 0 0.3rem;
            border-top: 1px solid rgba(70, 110, 170, 0.4);
            font-size: 0.7rem;
            color: #8aa3cf;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* responsive compactness */
        @media (max-width: 750px) {
            .container {
                padding: 0.9rem;
            }
            h1 {
                font-size: 1.8rem;
            }
            header {
                border-radius: 1.5rem;
                padding: 0.5rem 1rem;
            }
            .cards-container {
                gap: 0.9rem;
                grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            }
            .app-card {
                padding: 0.8rem 0.7rem;
            }
            .app-logo {
                width: 48px;
                height: 48px;
            }
            .app-name {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 500px) {
            .cards-container {
                grid-template-columns: 1fr 1fr;
                gap: 0.8rem;
            }
            .app-description {
                font-size: 0.7rem;
                min-height: 32px;
            }
            .nav-links {
                gap: 0.9rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="logo">SMARTAPPS</div>
        <div class="nav-links">
            <a href="/installers.php">Download</a>
            <a href="/Services/contact.php">Contact</a>
            <a href="/Services/abouts.php">About</a>
            <a href="#">Services</a>
        </div>
    </header>


        <div class="cards-container">
            <!-- DMS -->
            <div class="app-card">
                <img src="/Services/img/bscr.png" alt="DMS" class="app-logo" onerror="this.src='https://placehold.co/56x56/1e2a4a/white?text=D'">
                <div class="app-name">POS</div>
                <p class="app-description">Point of Sale system</p>
                <a href="/SIDJAN/login.php" class="app-button">Visit →</a>
            </div>
           
        </div>
    </main>

    <footer>
        © <?php echo date("Y"); ?> SmartApps — streamlined efficiency
    </footer>
</div>

<script>
    (function() {
        // graceful fallback for any broken images -> elegant letter placeholder (compact style)
        const images = document.querySelectorAll('.app-logo');
        images.forEach(img => {
            img.addEventListener('error', function(e) {
                let parentCard = img.closest('.app-card');
                let appTitleElem = parentCard?.querySelector('.app-name');
                let initial = 'A';
                if (appTitleElem) {
                    let text = appTitleElem.innerText.trim();
                    initial = text.charAt(0).toUpperCase() || 'A';
                } else {
                    initial = '?';
                }
                const svgPlaceholder = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' fill='%231a2a47'/%3E%3Ctext x='40' y='52' font-size='36' font-family='system-ui, sans-serif' fill='%2374a9ff' text-anchor='middle' dominant-baseline='middle' font-weight='600'%3E${initial}%3C/text%3E%3C/svg%3E`;
                if (!img.src.startsWith('data:image/svg')) {
                    img.src = svgPlaceholder;
                }
                img.style.background = '#0f182a';
                img.style.objectFit = 'contain';
            });
        });
    })();
</script>
</body>
</html>