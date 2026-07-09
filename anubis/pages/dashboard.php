<?php
require_once "./DB/dbcon.php";

// ================= DATE FILTER =================
$date_filter = $_GET['date_filter'] ?? 'week';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

switch($date_filter) {
    case 'today': $start_date = $end_date = date('Y-m-d'); break;
    case 'yesterday': $start_date = $end_date = date('Y-m-d', strtotime('-1 day')); break;
    case 'week': $start_date = date('Y-m-d', strtotime('-7 days')); break;
    case 'month': $start_date = date('Y-m-d', strtotime('-30 days')); break;
}

$range = "fixtime BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";

// ================= CORE =================
$total_vehicles = (int)$conn->query("SELECT COUNT(*) FROM tc_devices WHERE disabled=0")->fetchColumn();

$active_vehicles = (int)$conn->query("
    SELECT COUNT(DISTINCT deviceid)
    FROM tc_positions
    WHERE fixtime >= DATEADD(HOUR,-2,GETDATE())
")->fetchColumn();

$pos_count = (int)$conn->query("SELECT COUNT(*) FROM tc_positions WHERE $range")->fetchColumn();

$total_distance = round($pos_count * 0.40, 2);

// avg speed
$avg_speed = round((float)$conn->query("
    SELECT AVG(speed) FROM tc_positions
    WHERE speed > 5 AND $range
")->fetchColumn(),1);

// utilization
$utilization = $total_vehicles ? round(($active_vehicles/$total_vehicles)*100,1) : 0;

// fuel rate
$avg_fuel_rate = (float)$conn->query("
    SELECT AVG(CAST(fuel_consumption_liter_per_km AS FLOAT))
    FROM tc_devices WHERE disabled=0
")->fetchColumn();
if(!$avg_fuel_rate) $avg_fuel_rate = 0.25;

$estimated_fuel = round($total_distance * $avg_fuel_rate,2);

// ================= HOURLY =================
$hourly = [];
for($h=0;$h<24;$h++){
    $hourly[] = (int)$conn->query("
        SELECT COUNT(*) FROM tc_positions
        WHERE DATEPART(hour,fixtime)=$h AND $range
    ")->fetchColumn();
}

// ================= SPEED =================
$speed_labels = ['0-20','21-40','41-60','61-80','81+'];
$r = [0,20,40,60,80,999];
$speed = [];

for($i=0;$i<5;$i++){
    $speed[] = (int)$conn->query("
        SELECT COUNT(*) FROM tc_positions
        WHERE speed>{$r[$i]} AND speed<={$r[$i+1]} AND $range
    ")->fetchColumn();
}

// ================= DAILY =================
$days=[]; $daily=[]; $fuel=[];
for($i=6;$i>=0;$i--){
    $d=date('Y-m-d',strtotime("-$i days"));

    $c=(int)$conn->query("
        SELECT COUNT(*) FROM tc_positions
        WHERE CAST(fixtime AS DATE)='$d'
    ")->fetchColumn();

    $days[] = date('D',strtotime($d));
    $daily[] = $c;
    $fuel[] = round($c*0.40*$avg_fuel_rate,2);
}

// ================= TOP VEHICLES =================
$top = $conn->query("
    SELECT TOP 10 d.name, COUNT(p.id) c
    FROM tc_positions p
    JOIN tc_devices d ON p.deviceid=d.id
    WHERE $range
    GROUP BY d.name ORDER BY c DESC
")->fetchAll(PDO::FETCH_ASSOC);

$top_labels=[]; $top_data=[];
foreach($top as $t){
    $top_labels[] = substr($t['name'],0,15);
    $top_data[] = $t['c'];
}

// ================= TYPE =================
$type = $conn->query("
    SELECT vehicle_type, COUNT(*) c
    FROM tc_devices
    WHERE disabled=0
    GROUP BY vehicle_type
")->fetchAll(PDO::FETCH_ASSOC);

$type_labels = array_column($type,'vehicle_type');
$type_data = array_column($type,'c');
if(!$type_labels){ $type_labels=['No Data']; $type_data=[1]; }

// ================= IDLING vs MOVING =================
$idle = (int)$conn->query("
    SELECT COUNT(*) FROM tc_positions
    WHERE speed<=5 AND $range
")->fetchColumn();

$moving = (int)$conn->query("
    SELECT COUNT(*) FROM tc_positions
    WHERE speed>5 AND $range
")->fetchColumn();

// ================= OVERSPEED =================
$overspeed = [];
for($i=6;$i>=0;$i--){
    $d=date('Y-m-d',strtotime("-$i days"));

    $overspeed[] = (int)$conn->query("
        SELECT COUNT(*) FROM tc_positions
        WHERE speed>80 AND CAST(fixtime AS DATE)='$d'
    ")->fetchColumn();
}

// ================= ONLINE =================
$offline = max($total_vehicles-$active_vehicles,0);
?>

<style>
.dashboard-analytics{
    padding:20px;
    background:linear-gradient(180deg,#0a0a0f,#050508);
    height:90vh;
    overflow-y:auto;
    color:#e0d4b3;
}
.filter-bar{
    display:flex;
    justify-content:space-between;
    padding:12px;
    background:rgba(10,10,15,0.9);
    border:1px solid rgba(212,175,55,0.3);
    border-radius:12px;
    margin-bottom:20px;
}
.analytics-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(420px,1fr));
    gap:18px;
}
.chart-box{
    background:rgba(15,15,25,0.92);
    border:1px solid rgba(212,175,55,0.2);
    border-radius:16px;
    padding:15px;
}
.chart-header{color:#d4af37;margin-bottom:10px;}
.kpi-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{padding:14px;background:rgba(212,175,55,0.08);border-radius:12px;text-align:center;}
.kpi-value{font-size:24px;color:#d4af37;}
</style>

<div class="dashboard-analytics">

<div class="filter-bar">
    <div style="color:#d4af37;font-weight:bold;">DASHBOARD</div>
</div>

<div class="kpi-bar">
    <div class="kpi-card"><div class="kpi-value"><?= $total_distance ?> km</div><div>Distance</div></div>
    <div class="kpi-card"><div class="kpi-value"><?= $avg_speed ?> km/h</div><div>Speed</div></div>
    <div class="kpi-card"><div class="kpi-value"><?= $estimated_fuel ?> L</div><div>Fuel</div></div>
    <div class="kpi-card"><div class="kpi-value"><?= $utilization ?>%</div><div>Utilization</div></div>
</div>

<div class="analytics-grid">

<div class="chart-box"><div class="chart-header">24H Activity</div><canvas id="c1"></canvas></div>
<div class="chart-box"><div class="chart-header">Speed</div><canvas id="c2"></canvas></div>
<div class="chart-box"><div class="chart-header">Daily</div><canvas id="c3"></canvas></div>
<div class="chart-box"><div class="chart-header">Fuel</div><canvas id="c4"></canvas></div>
<div class="chart-box"><div class="chart-header">Fleet</div><canvas id="c5"></canvas></div>
<div class="chart-box"><div class="chart-header">Top Vehicles</div><canvas id="c6"></canvas></div>
<div class="chart-box"><div class="chart-header">Type</div><canvas id="c7"></canvas></div>
<div class="chart-box"><div class="chart-header">Fuel Consumers</div><canvas id="c8"></canvas></div>
<div class="chart-box"><div class="chart-header">Online</div><canvas id="c9"></canvas></div>
<div class="chart-box"><div class="chart-header">Idling</div><canvas id="c10"></canvas></div>
<div class="chart-box"><div class="chart-header">Overspeed</div><canvas id="c11"></canvas></div>
<div class="chart-box"><div class="chart-header">Utilization</div><canvas id="c12"></canvas></div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(c1,{type:'line',data:{labels:[...Array(24).keys()],datasets:[{data:<?=json_encode($hourly)?>}]}});

new Chart(c2,{type:'bar',data:{labels:<?=json_encode($speed_labels)?>,datasets:[{data:<?=json_encode($speed)?>}]}});

new Chart(c3,{type:'bar',data:{labels:<?=json_encode($days)?>,datasets:[{data:<?=json_encode($daily)?>}]}});

new Chart(c4,{type:'line',data:{labels:<?=json_encode($days)?>,datasets:[{data:<?=json_encode($fuel)?>}]}});

new Chart(c5,{type:'doughnut',data:{labels:<?=json_encode($type_labels)?>,datasets:[{data:<?=json_encode($type_data)?>}]}});

new Chart(c6,{type:'bar',data:{labels:<?=json_encode($top_labels)?>,datasets:[{data:<?=json_encode($top_data)?>}]}});

new Chart(c7,{type:'bar',data:{labels:<?=json_encode($type_labels)?>,datasets:[{data:<?=json_encode($type_data)?>}]}});

new Chart(c8,{type:'bar',data:{labels:<?=json_encode($top_labels)?>,datasets:[{data:<?=json_encode($top_data)?>}]}});

new Chart(c9,{type:'pie',data:{labels:['Online','Offline'],datasets:[{data:[<?=$active_vehicles?>,<?=$offline?>]}]}});

new Chart(c10,{type:'pie',data:{labels:['Idle','Moving'],datasets:[{data:[<?=$idle?>,<?=$moving?>]}]}});

new Chart(c11,{type:'bar',data:{labels:<?=json_encode($days)?>,datasets:[{data:<?=json_encode($overspeed)?>}]}});

new Chart(c12,{type:'line',data:{labels:<?=json_encode($days)?>,datasets:[{data:Array(7).fill(<?=$utilization?>)}]}});
</script>