<?php header('ngrok-skip-browser-warning: true'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartApps Workspace</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background:
                radial-gradient(circle at top, rgba(37,99,235,0.12), transparent 35%),
                #09090b;
        }

        .glass {
            background: rgba(24, 24, 27, 0.78);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .card-hover {
            transition: all 0.35s ease;
        }

        .card-hover:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(255,255,255,0.18);
            box-shadow:
                0 20px 25px -5px rgb(0 0 0 / 0.35),
                0 8px 10px -6px rgb(0 0 0 / 0.35);
        }

        .logo-glow {
            box-shadow: 0 0 20px rgba(255,255,255,0.08);
        }

        .app-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgb(63 63 70);
            background: rgba(255,255,255,0.03);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .app-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .launch-btn {
            transition: all 0.25s ease;
        }

        .group:hover .launch-btn {
            transform: translateX(5px);
        }
    </style>
</head>

<body class="text-zinc-200 min-h-screen">

<!-- NAVIGATION -->
<nav class="border-b border-zinc-800 bg-zinc-950/80 backdrop-blur-xl sticky top-0 z-50">
    <div class="max-w-screen-2xl mx-auto px-8 py-4 flex items-center justify-between">

        <!-- LOGO -->
        <div class="flex items-center gap-4">

            <div class="w-12 h-12 rounded-2xl overflow-hidden border border-zinc-700 logo-glow">
                <img
                    src="/img/smartapps.png"
                    alt="SmartApps Logo"
                    class="w-full h-full object-cover"
                >
            </div>

            <div>
                <h1 class="text-xl font-semibold tracking-tight text-white">
                    SmartApps Workspace
                </h1>

                <p class="text-xs text-zinc-500">
                    Unified Business Platform
                </p>
            </div>
        </div>

        <!-- MENU -->
        <div class="hidden md:flex items-center gap-8 text-sm text-zinc-400">
            <a href="#" class="hover:text-white transition">Apps</a>
            <a href="/installers.php" class="hover:text-white transition">Downloads</a>
            <a href="/Services/contact.php" class="hover:text-white transition">Contact</a>
            <a href="/Services/abouts.php" class="hover:text-white transition">About</a>
        </div>

        <!-- STATUS -->
        <div class="bg-zinc-900 border border-zinc-700 rounded-2xl px-4 py-2 text-xs flex items-center gap-2">
            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
            All Systems Online
        </div>

    </div>
</nav>

<!-- MAIN -->
<div class="max-w-screen-2xl mx-auto px-8 py-12">

    <!-- HEADER -->
    <div class="mb-12">
        <h1 class="text-5xl font-semibold tracking-tighter mb-3 text-white">
            Welcome Back
        </h1>

        <p class="text-zinc-400 text-lg">
            Select an application to continue
        </p>
    </div>

    <!-- APPLICATION GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

        <!-- INVENTORY -->
        <a href="/it_equipment_inventory/index.php" class="group">
            <div class="glass border border-zinc-800 rounded-3xl p-8 card-hover h-full flex flex-col">

                <div class="app-icon mb-8">
                    <img src="/img/bsms.png" alt="Inventory">
                </div>

                <h3 class="text-2xl font-semibold mb-2 text-white">
                    Stock Inventory
                </h3>

                <p class="text-zinc-400 flex-1">
                    Inventory Management • Stock Tracking • Reports
                </p>

            </div>
        </a>

        <!-- GOODS CREDIT -->
        <a href="/TBC/login.php" class="group">
            <div class="glass border border-zinc-800 rounded-3xl p-8 card-hover h-full flex flex-col">

                <div class="app-icon mb-8">
                    <img src="/img/tbc.png" alt="Tele Caller App">
                </div>

                <h3 class="text-2xl font-semibold mb-2 text-white">
                    Tele Caller App
                </h3>

                <p class="text-zinc-400 flex-1">
                    Tele Calling Application • Customer Engagement
                </p>
            </p>

            </div>
        </a>

        <!-- PLOUTUS -->
        <a href="/ploutus/login.php" class="group">
            <div class="glass border border-zinc-800 rounded-3xl p-8 card-hover h-full flex flex-col">

                <div class="app-icon mb-8">
                    <img src="/ploutus/mainimg/pl.png" alt="Ploutus">
                </div>

                <h3 class="text-2xl font-semibold mb-2 text-white">
                    Ploutus
                </h3>

                <p class="text-zinc-400 flex-1">
                    Accounting Management • Financial System
                </p>

            </div>
        </a>

        <!-- MY FILES -->
        <a href="/myfiles/bspifiles.php" class="group">
            <div class="glass border border-zinc-800 rounded-3xl p-8 card-hover h-full flex flex-col">

                <div class="app-icon mb-8">
                    <img src="/img/files.png" alt="Files">
                </div>

                <h3 class="text-2xl font-semibold mb-2 text-white">
                    My Files
                </h3>

                <p class="text-zinc-400 flex-1">
                    File Storage • Document Management
                </p>

            </div>
        </a>

        <!-- ANUBIS -->
        <a href="/anubis/login.php" class="group">
            <div class="glass border border-zinc-800 rounded-3xl p-8 card-hover h-full flex flex-col">

                <div class="app-icon mb-8">
                    <img src="/anubis/mainimg/pl.png" alt="ANUBIS">
                </div>

                <h3 class="text-2xl font-semibold mb-2 text-white">
                    Anubis
                </h3>

                <p class="text-zinc-400 flex-1">
                    Tracking Management • Real-Time Updates
                </p>
            </div>
        </a>


         <!-- ANUBIS -->
        <a href="/asset" class="group">
            <div class="glass border border-zinc-800 rounded-3xl p-8 card-hover h-full flex flex-col">

                <div class="app-icon mb-8">
                    <img src="/img/asst.png" alt="asset">
                </div>

                <h3 class="text-2xl font-semibold mb-2 text-white">
                    Asset Management
                </h3>

                <p class="text-zinc-400 flex-1">
                    Asset Tracking • IT Inventory Management
                </p>
            </div>
        </a>

    </div>

    <!-- FOOTER -->
    <footer class="mt-20 text-center text-zinc-500 text-sm">
        © <?php echo date("Y"); ?> SmartApps Workspace • Unified Business Ecosystem
    </footer>

</div>

</body>
</html>