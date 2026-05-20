<?php header('ngrok-skip-browser-warning: true'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartApps — Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
        
        .glass {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 
                       0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .logo-gradient {
            background: linear-gradient(90deg, #60a5fa, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-200 min-h-screen">

    <!-- Top Navigation -->
    <nav class="border-b border-zinc-800 bg-zinc-950/80 backdrop-blur-lg sticky top-0 z-50">
        <div class="max-w-screen-2xl mx-auto px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">S</div>
                <div>
                    <span class="text-2xl font-semibold tracking-tighter logo-gradient">SmartApps</span>
                </div>
            </div>
            
            <div class="flex items-center gap-8 text-sm">
                <a href="#" class="hover:text-white transition-colors">Apps</a>
                <a href="/installers.php" class="hover:text-white transition-colors">Downloads</a>
                <a href="/Services/contact.php" class="hover:text-white transition-colors">Contact</a>
                <a href="/Services/abouts.php" class="hover:text-white transition-colors">About</a>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-zinc-900 text-xs px-3 py-1.5 rounded-2xl border border-zinc-700 flex items-center gap-2">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    Online
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-screen-2xl mx-auto px-8 py-12">
        <!-- Header -->
        <div class="mb-12">
            <h1 class="text-5xl font-semibold tracking-tighter mb-3">
                Welcome back
            </h1>
            <p class="text-zinc-400 text-lg">
                Select an application to continue
            </p>
        </div>

        <!-- Apps Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            
            <!-- POS -->
            <a href="/SIDJAN/login.php" class="group">
                <div class="glass border border-zinc-700 rounded-3xl p-8 card-hover h-full flex flex-col">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-8 text-4xl shadow-lg">
                        💰
                    </div>
                    <h3 class="text-2xl font-semibold mb-2">POS System</h3>
                    <p class="text-zinc-400 flex-1">Point of Sale • Sales • Receipts</p>
                    <div class="mt-6 flex items-center text-blue-400 text-sm font-medium group-hover:gap-2 transition-all">
                        Launch Application 
                        <span class="text-lg transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

            <!-- Stock Inventory -->
            <a href="/it_equipment_inventory/index.php" class="group">
                <div class="glass border border-zinc-700 rounded-3xl p-8 card-hover h-full flex flex-col">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-cyan-600 rounded-2xl flex items-center justify-center mb-8 text-4xl shadow-lg">
                        📦
                    </div>
                    <h3 class="text-2xl font-semibold mb-2">Stock Inventory</h3>
                    <p class="text-zinc-400 flex-1">Inventory Management • Stock Tracking • Reports</p>
                    <div class="mt-6 flex items-center text-blue-400 text-sm font-medium group-hover:gap-2 transition-all">
                        Launch Application 
                        <span class="text-lg transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

            <!-- TBC / Goods Credit -->
            <a href="/GoodsCredit/login.php" class="group">
                <div class="glass border border-zinc-700 rounded-3xl p-8 card-hover h-full flex flex-col">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mb-8 text-4xl shadow-lg">
                        📋
                    </div>
                    <h3 class="text-2xl font-semibold mb-2">Goods Credit</h3>
                    <p class="text-zinc-400 flex-1">Credit Management • Goods Tracking</p>
                    <div class="mt-6 flex items-center text-blue-400 text-sm font-medium group-hover:gap-2 transition-all">
                        Launch Application 
                        <span class="text-lg transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>


             <!-- Ploutus -->
            <a href="/ploutus/login.php" class="group">
                <div class="glass border border-zinc-700 rounded-3xl p-8 card-hover h-full flex flex-col">
                    <div class="w-16 h-16 bg-gradient-to-br from-white  to-warm-gray-500 to-warm-gray-600 rounded-2xl flex items-center justify-center mb-8 text-4xl shadow-lg">
                        🏦
                    </div>
                    <h3 class="text-2xl font-semibold mb-2">Ploutus</h3>
                    <p class="text-zinc-400 flex-1">Accounting Management • Business Accounting Tracking</p>
                    <div class="mt-6 flex items-center text-blue-400 text-sm font-medium group-hover:gap-2 transition-all">
                        Launch Application 
                        <span class="text-lg transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>


              <!-- My Files -->
            <a href="/myfiles/bspifiles.php" class="group">
                <div class="glass border border-zinc-700 rounded-3xl p-8 card-hover h-full flex flex-col">
                    <div class="w-16 h-16 bg-gradient-to-br from-white  to-warm-gray-500 to-warm-gray-600 rounded-2xl flex items-center justify-center mb-8 text-4xl shadow-lg">
                        📁
                    </div>
                    <h3 class="text-2xl font-semibold mb-2">My Files</h3>
                    <p class="text-zinc-400 flex-1">File Management</p>
                    <div class="mt-6 flex items-center text-blue-400 text-sm font-medium group-hover:gap-2 transition-all">
                        Launch Application 
                        <span class="text-lg transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

        </div>

        <!-- Footer -->
        <footer class="mt-20 text-center text-zinc-500 text-sm">
            © <?php echo date("Y"); ?> SmartApps • Streamlined Efficiency
        </footer>
    </div>

    <script>
        // Optional: Keyboard shortcut hint (like Cloudflare)
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement.tagName !== "INPUT") {
                e.preventDefault();
                alert("🔍 Quick search coming soon...");
            }
        });
    </script>
</body>
</html>