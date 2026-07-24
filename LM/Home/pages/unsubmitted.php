<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Load Checking - Results</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
      
                .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        @media (min-width: 1200px) {
            .main-content { padding: 1.5rem; }
        }
        h4 { font-weight: 600; letter-spacing: -0.5px; margin-bottom: 1.5rem; }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
        .card-header {
            background: linear-gradient(90deg, #834b01, #a35701);
            color: white;
            font-weight: 600;
            padding: 12px 18px;
            border-bottom: none;
        }
        .summary-number {
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1;
        }
        .summary-label { font-size: 0.95rem; opacity: 0.9; }
        .card-body-scroll { height: calc(100vh - 320px); overflow-y: auto; padding: 1rem; }
        .table { font-size: 9.8px; margin-bottom: 0; }
        th { background: #f1f3f5; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
        th { white-space: nowrap; }
        td { vertical-align: middle; }
        .form-control-sm, .custom-select-sm {
            border-radius: 8px;
            height: 32px;
            font-size: 0.9rem;
        }
        .input-group-text { border-radius: 8px 0 0 8px; background: #e9ecef; }
        .btn-primary {
            border-radius: 8px;
            padding: 6px 16px;
            font-weight: 500;
        }
        .pie-canvas { max-height: 140px; margin: 0 auto; }
        .filter-row { background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        @media (max-width: 992px) {
            .summary-number { font-size: 2rem; }
            .card-body-scroll { height: calc(100vh - 400px); }
        }
    </style>
</head>
<body>

<div class="main-content">

    <h4 class="mb-4 text-dark px-3">DEVICE MONITORING (NOT SUBMITTED)</h4>

    <!-- Filters & Summary -->
    <div class="row mb-4 mx-1">
        <!-- Date + Site + Search Filters -->
        <div class="col-lg-7">
            <div class="filter-row d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="small mb-1 text-muted">From</label>
                    <input type="date" id="datefrom" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label class="small mb-1 text-muted">To</label>
                    <input type="date" id="dateto" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label class="small mb-1 text-muted">Site</label>
                    <select id="siteFilter" class="custom-select custom-select-sm">
                        <option value="">All Sites</option>
                        <!-- Populated dynamically -->
                    </select>
                </div>
                <div class="flex-grow-1" style="min-width: 220px;">
                    <label class="small mb-1 text-muted">Search</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Site / Number / User / Date..." onkeyup="applyFilters()">
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm mt-4" onclick="loaddeviceschecked()">
                        <i class="fas fa-sync-alt mr-1"></i> Refresh
                    </button>
                </div>
                <div>
                    <button class="btn btn-outline-secondary btn-sm mt-4 ml-2" onclick="exportCSV()" id="exportBtn">
                        <i class="fas fa-file-export mr-1"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-lg-5">
            <div class="filter-row d-flex flex-column align-items-center">
                <div style="width: 100%; padding: 0;">
                    <div class="text-center px-2">
                        <div class="summary-number text-success" id="totalSubmitted" style="font-size:1.8rem">0</div>
                        <div class="summary-label" style="font-size:0.85rem">Submitted</div>
                    </div>
                    <div class="text-center px-2 my-2">
                        <div class="summary-number text-danger" id="totalToBeSubmitted" style="font-size:1.8rem">0</div>
                        <div class="summary-label" style="font-size:0.85rem">Not Submitted</div>
                    </div>
                    <div style="width: 120px; margin: 6px auto;">
                        <canvas id="statusPie" class="pie-canvas d-none"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card mx-1">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Checking Records</span>
        </div>
        <div class="card-body p-0" style = "height: 500px;">
            <div class="table-responsive card-body-scroll mb-0" style = "height: 490px;">
                <table id="itemsTable"  class="table table-hover table-bordered mb-5">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SITE</th>
                            <th>DATE</th>
                            <th>USER</th>
                            <th>NUMBER</th>
                            <th>DATA BALANCE</th>
                            <th>IS SUBMITTED</th>
                            <th>OTHER ISSUES</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Globals
let allData = [];
let pieChart = null;
let uniqueSites = new Set();
let totalDevices = 0;
let lastFiltered = [];

function populateSiteFilter() {
    const select = document.getElementById('siteFilter');
    select.innerHTML = '<option value="">All Sites</option>';
    uniqueSites.forEach(site => {
        const opt = document.createElement('option');
        opt.value = site;
        opt.textContent = site;
        select.appendChild(opt);
    });
}

async function updateSummary() {
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const selectedSite = document.getElementById('siteFilter').value;

    // allData here contains devices that are NOT submitted (unsubmitted list)
    const notSubmittedCount = allData.filter(item => !selectedSite || (item.SITE_ID || '') === selectedSite).length;
    const totalForSite = await fetchTotalDevices(companyId, selectedSite || null);
    const submitted = Math.max((totalForSite - notSubmittedCount), 0);

    document.getElementById('totalSubmitted').textContent = submitted;
    document.getElementById('totalToBeSubmitted').textContent = notSubmittedCount;

    if (pieChart) pieChart.destroy();

    const ctx = document.getElementById('statusPie').getContext('2d');
    pieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Submitted', 'Not Submitted'],
            datasets: [{
                data: [submitted, notSubmittedCount],
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 10 }, color: '#333', padding: 8 } }
            }
        }
    });
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const selectedSite = document.getElementById('siteFilter').value;

    const filtered = allData.filter(item => {
        const matchesSearch = !search || [
            item.SITE_ID, item.NUMBER, item.PERSON_USING, item.DATE_ADDED, item.REMARKS
        ].some(val => (val || '').toLowerCase().includes(search));

        const matchesSite = !selectedSite || (item.SITE_ID || '') === selectedSite;

        return matchesSearch && matchesSite;
    });

    lastFiltered = filtered;
    renderTable(filtered);
}

function exportCSV() {
    const data = (lastFiltered && lastFiltered.length) ? lastFiltered : allData;
    if (!data || data.length === 0) {
        alert('No data to export');
        return;
    }

    // Match the table columns exactly
    const headers = ['#', 'SITE', 'DATE', 'USER', 'NUMBER', 'DATA BALANCE', 'IS SUBMITTED', 'OTHER ISSUES'];

    // Map to device fields matching table display
    const rows = data.map((item, index) => [
        index + 1,
        item.SITE_ID || '',
        item.DATE_ADDED || '',
        item.PERSON_USING || '',
        item.NUMBER || '',
        item.BALANCE ?? '',
        'NO',
        item.REMARKS || ''
    ]);

    const csvContent = [headers, ...rows].map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\r\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    const filename = `unsubmitted_devices_${new Date().toISOString().slice(0,10)}.csv`;
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5">No matching records found</td></tr>';
        return;
    }

    data.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${index + 1}</td>
            <td><strong>${item.SITE_ID || '-'}</strong></td>
            <td>${item.DATE_ADDED || '-'}</td>
            <td>${item.PERSON_USING || '-'}</td>
            <td>${item.NUMBER || '-'}</td>
            <td>${item.BALANCE ?? '-'}</td>
            <td class="text-danger">NO</td>
            <td>${item.REMARKS }</td>
        `;
        tbody.appendChild(tr);
    });
}

function fetchTotalDevices(companyId, site=null) {
    let url = `/LM/datafetcher/loadcheckingdata.php?action=totaldevices&company=${encodeURIComponent(companyId)}`;
    if (site) url += `&site=${encodeURIComponent(site)}`;
    return fetch(url)
        .then(res => {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then(data => {
            return data.total || 0; // Assume response { total: number }
        })
        .catch(err => {
            console.error('Failed to fetch total devices:', err);
            return 0;
        });
}

async function loaddeviceschecked() {
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const datefrom  = document.getElementById('datefrom').value;
    const dateto    = document.getElementById('dateto').value;

    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5">Loading data...</td></tr>';

    try {
        totalDevices = await fetchTotalDevices(companyId);

        const res = await fetch(`/LM/datafetcher/loadcheckingdata.php?action=unsubmitted_devices&company=${encodeURIComponent(companyId)}&datefrom=${encodeURIComponent(datefrom)}&dateto=${encodeURIComponent(dateto)}`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();

        allData = Array.isArray(data) ? data : [];
        uniqueSites.clear();
        allData.forEach(item => {
            if (item.SITE_ID) uniqueSites.add(item.SITE_ID);
        });

        populateSiteFilter();
        applyFilters(); // renders table with current filters
        await updateSummary();
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-5">Failed to load data</td></tr>';
    }
}

// Auto-load on page ready
document.addEventListener('DOMContentLoaded', () => {
    loaddeviceschecked();
});

// Filter events
document.getElementById('siteFilter')?.addEventListener('change', async () => {
    applyFilters();
    await updateSummary();
});
</script>

</body>
</html>