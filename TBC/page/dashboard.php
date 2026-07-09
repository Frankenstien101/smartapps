<?php
require_once __DIR__ . '/../DB/dbcon.php';

// Check login
if (!isset($_SESSION['username'])) {
    header("Location: /TBC/login.php");
    exit();
}

// Get date filters for dashboard
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Initialize variables with default values
$totalCalls = 0;
$totalAmount = 0;
$avgDuration = 0;
$successRate = 0;
$statusDistribution = [];
$storeAccuracy = [];
$phoneCorrectness = [];
$storeVisit = [];
$amountVerification = [];
$dailyTrends = [];
$topBranches = [];
$topSellers = [];

try {
    // First, get total count for percentage calculations
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $totalCountResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCount = $totalCountResult ? $totalCountResult['total_count'] : 1;
    
    // 1. Total Calls
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_calls
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCalls = $result ? $result['total_calls'] : 0;
    
    // 2. Total Amount
    $stmt = $conn->prepare("
        SELECT ISNULL(SUM(AMOUNT), 0) as total_amount
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalAmount = $result ? $result['total_amount'] : 0;
    
    // 3. Status Distribution
    $stmt = $conn->prepare("
        SELECT ISNULL(STATUS, 'Unknown') as STATUS, COUNT(*) as count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY STATUS
        ORDER BY count DESC
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $statusDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Store Name Accuracy
    $stmt = $conn->prepare("
        SELECT 
            ISNULL(STORE_NAME_ACCURACY, 'Unknown') as STORE_NAME_ACCURACY,
            COUNT(*) as count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY STORE_NAME_ACCURACY
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $storeAccuracyRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate percentages for store accuracy
    foreach ($storeAccuracyRaw as $item) {
        $item['percentage'] = ($item['count'] / $totalCount) * 100;
        $storeAccuracy[] = $item;
    }
    
    // 5. Phone Number Correctness
    $stmt = $conn->prepare("
        SELECT 
            ISNULL(IS_PHONE_NUMBER_CORRECT, 'Unknown') as IS_PHONE_NUMBER_CORRECT,
            COUNT(*) as count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY IS_PHONE_NUMBER_CORRECT
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $phoneCorrectnessRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate percentages for phone correctness
    foreach ($phoneCorrectnessRaw as $item) {
        $item['percentage'] = ($item['count'] / $totalCount) * 100;
        $phoneCorrectness[] = $item;
    }
    
    // 6. Store Visit Status
    $stmt = $conn->prepare("
        SELECT 
            ISNULL(STORE_VISIT, 'Unknown') as STORE_VISIT,
            COUNT(*) as count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY STORE_VISIT
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $storeVisit = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Amount Verification
    $stmt = $conn->prepare("
        SELECT 
            ISNULL(AMOUNT_VERIFICATION, 'Unknown') as AMOUNT_VERIFICATION,
            COUNT(*) as count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY AMOUNT_VERIFICATION
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $amountVerification = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. Daily Call Trends
    $stmt = $conn->prepare("
        SELECT 
            CAST(CALL_DATE AS DATE) as call_date,
            COUNT(*) as call_count,
            ISNULL(SUM(AMOUNT), 0) as daily_amount
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY CAST(CALL_DATE AS DATE)
        ORDER BY call_date ASC
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $dailyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 9. Top Branches
    $stmt = $conn->prepare("
        SELECT TOP 5
            ISNULL(BRANCH, 'Unknown') as BRANCH,
            COUNT(*) as call_count,
            ISNULL(SUM(AMOUNT), 0) as total_amount
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY BRANCH
        ORDER BY call_count DESC
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $topBranches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 10. Top Sellers
    $stmt = $conn->prepare("
        SELECT TOP 5
            ISNULL(SELLER, 'Unknown') as SELLER,
            COUNT(*) as call_count,
            ISNULL(SUM(AMOUNT), 0) as total_amount
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY SELLER
        ORDER BY total_amount DESC
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $topSellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 11. Average Call Duration
    $stmt = $conn->prepare("
        SELECT 
            CALL_DURATION
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        AND CALL_DURATION IS NOT NULL
        AND CALL_DURATION != ''
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $durations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalSeconds = 0;
    $durationCount = 0;
    foreach ($durations as $duration) {
        if ($duration['CALL_DURATION']) {
            $timeParts = explode(':', $duration['CALL_DURATION']);
            if (count($timeParts) >= 2) {
                $hours = intval($timeParts[0]);
                $minutes = intval($timeParts[1]);
                $seconds = count($timeParts) == 3 ? intval($timeParts[2]) : 0;
                $totalSecondsInSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
                $totalSeconds += $totalSecondsInSeconds;
                $durationCount++;
            }
        }
    }
    $avgDuration = $durationCount > 0 ? round($totalSeconds / $durationCount, 0) : 0;
    
    // 12. Success Rate
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN STATUS IN ('Picked', 'Completed', 'Success', 'Approved') THEN 1 ELSE 0 END) as success_count,
            COUNT(*) as total_count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $successData = $stmt->fetch(PDO::FETCH_ASSOC);
    $successRate = ($successData && $successData['total_count'] > 0) ? ($successData['success_count'] / $successData['total_count']) * 100 : 0;
    $successRate = round($successRate, 1);
    
    // 13. Amount Result Analysis
    $stmt = $conn->prepare("
        SELECT 
            ISNULL(AMOUNT_RESULT, 'Unknown') as AMOUNT_RESULT,
            COUNT(*) as count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY AMOUNT_RESULT
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $amountResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 14. Dial Result Analysis
    $stmt = $conn->prepare("
        SELECT 
            ISNULL(DIAL_RESULT, 'Unknown') as DIAL_RESULT,
            COUNT(*) as count
        FROM [TBC].[dbo].[TBC_CALL_TRANSACTION]
        WHERE CAST(CALL_DATE AS DATE) BETWEEN :from AND :to
        GROUP BY DIAL_RESULT
    ");
    $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
    $dialResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Transaction Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* FULLSCREEN STYLES - Removed body padding, made container full width */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f1f5f9;
            padding: 0;
            margin: 0;
        }
        
        .dashboard-container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
            width: 100%;
        }
        
        /* Optional: Remove the gradient header if you want it gone - keeping it but making it full width */
        .dashboard-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-title h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .header-title p {
            color: #94a3b8;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }
        
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.875rem;
        }
        
        .btn-filter, .btn-reset {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-filter {
            background: #3b82f6;
            color: white;
        }
        
        .btn-filter:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-reset {
            background: #64748b;
            color: white;
        }
        
        .btn-reset:hover {
            background: #475569;
            transform: translateY(-1px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .stat-trend {
            font-size: 0.75rem;
            margin-top: 8px;
            color: #10b981;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .data-table-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .data-table th {
            text-align: left;
            padding: 10px;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .data-table tr:hover {
            background: #f8fafc;
        }
        
        .amount-cell {
            font-weight: 600;
            color: #059669;
        }
        
        .error-message {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .tables-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header" style="margin-top: -30px;">
            <div class="header-title">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
            </div>
  
        </div>

        <!-- Filter Section Only - Removed the gradient header for fullscreen -->
        <div class="filter-section">
            <form class="filter-form" method="GET" action="">
                <input type="hidden" name="page" value="dashboard">
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="?page=dashboard&date_from=<?= date('Y-m-d', strtotime('-30 days')) ?>&date_to=<?= date('Y-m-d') ?>" class="btn-reset">
                    <i class="fas fa-sync-alt"></i> Last 30 Days
                </a>
            </form>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="stat-value"><?= number_format($totalCalls) ?></div>
                <div class="stat-label">Total Calls</div>
                <div class="stat-trend"><i class="fas fa-chart-line"></i> In selected period</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-value">₱<?= number_format($totalAmount, 2) ?></div>
                <div class="stat-label">Total Amount</div>
                <div class="stat-trend"><i class="fas fa-chart-line"></i> Sum of all transactions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?= number_format($avgDuration) ?> sec</div>
                <div class="stat-label">Avg Call Duration</div>
                <div class="stat-trend"><i class="fas fa-chart-line"></i> Average per call</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?= number_format($successRate, 1) ?>%</div>
                <div class="stat-label">Success Rate</div>
                <div class="stat-trend"><i class="fas fa-chart-line"></i> Calls with STATUS = Picked</div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-chart-bar"></i> Daily Call Trends</div>
                <div class="chart-container">
                    <canvas id="dailyTrendsChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-chart-pie"></i> Status Distribution</div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-check-double"></i> Quality Metrics</div>
                <div class="chart-container">
                    <canvas id="qualityChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-store"></i> Store Visit Distribution</div>
                <div class="chart-container">
                    <canvas id="visitChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-file-invoice-dollar"></i> Amount Result</div>
                <div class="chart-container">
                    <canvas id="amountResultChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-phone-volume"></i> Dial Result</div>
                <div class="chart-container">
                    <canvas id="dialResultChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="tables-grid">
            <div class="data-table-card">
                <div class="chart-title"><i class="fas fa-building"></i> Top 5 Branches</div>
                <table class="data-table">
                    <thead>
                        <tr><th>Branch</th><th>Calls</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topBranches)): ?>
                            <?php foreach ($topBranches as $branch): ?>
                            <tr>
                                <td><?= htmlspecialchars($branch['BRANCH']) ?></td>
                                <td><?= number_format($branch['call_count']) ?></td>
                                <td class="amount-cell">₱<?= number_format($branch['total_amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; padding: 20px;">No data available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="data-table-card">
                <div class="chart-title"><i class="fas fa-user-tie"></i> Top 5 Sellers</div>
                <table class="data-table">
                    <thead>
                        <tr><th>Seller</th><th>Calls</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topSellers)): ?>
                            <?php foreach ($topSellers as $seller): ?>
                            <tr>
                                <td><?= htmlspecialchars($seller['SELLER']) ?></td>
                                <td><?= number_format($seller['call_count']) ?></td>
                                <td class="amount-cell">₱<?= number_format($seller['total_amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; padding: 20px;">No data available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Daily Trends Chart
        const dailyData = <?= json_encode($dailyTrends) ?>;
        const dailyLabels = dailyData.map(d => d.call_date);
        const dailyCalls = dailyData.map(d => d.call_count);
        const dailyAmounts = dailyData.map(d => d.daily_amount);
        
        if (dailyLabels.length > 0) {
            new Chart(document.getElementById('dailyTrendsChart'), {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [
                        {
                            label: 'Number of Calls',
                            data: dailyCalls,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Amount (₱)',
                            data: dailyAmounts,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            yAxisID: 'y1',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { title: { display: true, text: 'Number of Calls' }, beginAtZero: true },
                        y1: { position: 'right', title: { display: true, text: 'Amount (₱)' }, grid: { drawOnChartArea: false }, beginAtZero: true }
                    }
                }
            });
        } else {
            document.getElementById('dailyTrendsChart').parentElement.innerHTML = '<div style="text-align: center; padding: 50px; color: #94a3b8;">No data available for the selected period</div>';
        }
        
        // Status Distribution Chart
        const statusData = <?= json_encode($statusDistribution) ?>;
        if (statusData.length > 0) {
            const statusLabels = statusData.map(s => s.STATUS);
            const statusCounts = statusData.map(s => s.count);
            new Chart(document.getElementById('statusChart'), {
                type: 'pie',
                data: { labels: statusLabels, datasets: [{ data: statusCounts, backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#6b7280'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
            });
        } else {
            document.getElementById('statusChart').parentElement.innerHTML = '<div style="text-align: center; padding: 50px; color: #94a3b8;">No status data available</div>';
        }
        
        // Quality Metrics Chart
        const storeAccuracy = <?= json_encode($storeAccuracy) ?>;
        const phoneCorrectness = <?= json_encode($phoneCorrectness) ?>;
        const allCategories = ['Correct', 'Not Correct', 'Unknown'];
        const storeData = [0, 0, 0];
        const phoneData = [0, 0, 0];
        
        storeAccuracy.forEach(item => {
            const val = item.STORE_NAME_ACCURACY;
            if (val === 'CORRECT') storeData[0] = item.percentage || 0;
            else if (val === 'NOT CORRECT') storeData[1] = item.percentage || 0;
            else storeData[2] = item.percentage || 0;
        });
        
        phoneCorrectness.forEach(item => {
            const val = item.IS_PHONE_NUMBER_CORRECT;
            if (val === 'YES') phoneData[0] = item.percentage || 0;
            else if (val === 'NO') phoneData[1] = item.percentage || 0;
            else phoneData[2] = item.percentage || 0;
        });
        
        new Chart(document.getElementById('qualityChart'), {
            type: 'bar',
            data: { labels: allCategories, datasets: [{ label: 'Store Name Accuracy (%)', data: storeData, backgroundColor: '#3b82f6' }, { label: 'Phone Number Correctness (%)', data: phoneData, backgroundColor: '#10b981' }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { title: { display: true, text: 'Percentage (%)' }, max: 100, beginAtZero: true } } }
        });
        
        // Store Visit Chart
        const visitData = <?= json_encode($storeVisit) ?>;
        if (visitData.length > 0) {
            const visitLabels = visitData.map(v => v.STORE_VISIT);
            const visitCounts = visitData.map(v => v.count);
            new Chart(document.getElementById('visitChart'), {
                type: 'bar',
                data: { labels: visitLabels, datasets: [{ label: 'Number of Records', data: visitCounts, backgroundColor: '#f59e0b' }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: 'Count' } } } }
            });
        } else {
            document.getElementById('visitChart').parentElement.innerHTML = '<div style="text-align: center; padding: 50px; color: #94a3b8;">No visit data available</div>';
        }
        
        // Amount Result Chart
        const amountResultData = <?= json_encode($amountResult) ?>;
        if (amountResultData.length > 0) {
            const amountLabels = amountResultData.map(a => a.AMOUNT_RESULT);
            const amountCounts = amountResultData.map(a => a.count);
            new Chart(document.getElementById('amountResultChart'), {
                type: 'pie',
                data: { labels: amountLabels, datasets: [{ data: amountCounts, backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#8b5cf6'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
            });
        }
        
        // Dial Result Chart
        const dialResultData = <?= json_encode($dialResult) ?>;
        if (dialResultData.length > 0) {
            const dialLabels = dialResultData.map(d => d.DIAL_RESULT);
            const dialCounts = dialResultData.map(d => d.count);
            new Chart(document.getElementById('dialResultChart'), {
                type: 'pie',
                data: { labels: dialLabels, datasets: [{ data: dialCounts, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
            });
        }
    </script>
</body>
</html>