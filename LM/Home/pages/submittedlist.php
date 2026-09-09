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
    <!-- SheetJS (XLSX) for export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>

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
            background: linear-gradient(90deg, #634c01, #c46b05);
            color: white;
            font-weight: 600;
            padding: 12px 18px;
            border-bottom: none;
        }
        .summary-card .card-header { background: linear-gradient(90deg, #663e01, #9e6702); }
        .summary-number {
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1;
        }
        .summary-label { font-size: 0.95rem; opacity: 0.9; }
        .card-body-scroll { height: calc(100vh - 320px); overflow-y: auto; padding: 1rem; }
        .table { font-size: 9.8px; margin-bottom: 0; }
        th { background: #f1f3f5; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
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
        .btn-export {
            background-color: #2c7da0;
            border-color: #2c7da0;
            color: white;
            border-radius: 8px;
            padding: 6px 16px;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-export:hover {
            background-color: #1f5e7e;
            border-color: #1f5e7e;
            color: white;
        }
        .pie-canvas { max-height: 160px; margin: 0 auto; }
        .filter-row { background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        @media (max-width: 992px) {
            .summary-number { font-size: 2rem; }
            .card-body-scroll { height: calc(100vh - 400px); }
        }
        .badge-submitted { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 20px; font-size: 10px; }
        .badge-pending { background-color: #dc3545; color: white; padding: 3px 8px; border-radius: 20px; font-size: 10px; }
    </style>
</head>
<body>

<div class="main-content">

    <h4 class="mb-4 text-dark px-3">Load Checking Results</h4>

    <!-- Filters & Summary -->
    <div class="row mb-4 mx-1">
        <!-- Date + Site + Search Filters -->
        <div class="col-lg-7">
            <div class="filter-row d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="small mb-1 text-muted">From</label>
                    <input type="date" id="datefrom" class="form-control form-control-sm mr-1" value="<?php echo date('Y-m-d'); ?>">
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
                    <button class="btn btn-export btn-sm mt-4" id="exportExcelBtn">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary + Pie -->
        <div class="col-lg-5">
            <div class="card summary-card h-100">
                <div class="card-header text-center">Today's Overview</div>
                <div class="card-body d-flex flex-wrap align-items-center justify-content-around py-3">
                    <div class="text-center px-4">
                        <div class="summary-number text-success" id="totalSubmitted">0</div>
                        <div class="summary-label">Submitted</div>
                    </div>
                    <div class="text-center px-4">
                        <div class="summary-number text-danger" id="totalToBeSubmitted">0</div>
                        <div class="summary-label">To Be Submitted</div>
                    </div>
                    
                    <div class="text-center" style="width: 180px;">
                        <canvas id="statusPie" class="pie-canvas"></canvas>
                        <small class="text-muted">Showing <span id="recordCount">0</span> records</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card mx-1" style="height: 550px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Checking Records</span>
            <small class="text-white-50">Showing <span id="recordCountHeader">0</span> records</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive card-body-scroll" style="height: 500px;">
                <table id="itemsTable" class="table table-hover table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SITE</th>
                            <th>DATE</th>
                            <th>USER</th>
                            <th>NUMBER</th>
                            <th>DATA BALANCE</th>
                            <th>DATA USAGE</th>
                            <th>IS SUBMITTED</th>
                            <th>LOAD IR</th>
                            <th>DEVICE IR</th>
                            <th>PHYSICAL OK</th>
                            <th>HAS GAMES</th>
                            <th>SYSTEM UPDATED</th>
                            <th>OTHER ISSUES</th>
                            <th>REMARKS</th>
                            <th>CHECKED BY</th>
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
    const selectedSite = document.getElementById('siteFilter').value;

    const submitted = allData.filter(item => (item.IS_SUBMIT || '').toUpperCase() === 'YES'
        && (!selectedSite || (item.SITE_ID || '') === selectedSite)).length;

    const totalForSite = await fetchTotalDevices(selectedSite || null);
    const toBeSubmitted = totalForSite - submitted;

    document.getElementById('totalSubmitted').textContent = submitted;
    document.getElementById('totalToBeSubmitted').textContent = Math.max(toBeSubmitted, 0);
    
    const filteredCount = allData.filter(item => !selectedSite || (item.SITE_ID||'') === selectedSite).length;
    document.getElementById('recordCount').textContent = filteredCount;
    document.getElementById('recordCountHeader').textContent = filteredCount;

    if (pieChart) pieChart.destroy();

    const ctx = document.getElementById('statusPie').getContext('2d');
    pieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Submitted', 'To Be Submitted'],
            datasets: [{
                data: [submitted, Math.max(toBeSubmitted, 0)],
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, color: '#333' } }
            }
        }
    });
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const selectedSite = document.getElementById('siteFilter').value;

    const filtered = allData.filter(item => {
        const matchesSearch = !search || [
            item.SITE_ID, item.NUMBER, item.USER, item.DATE_CHECKED, item.CHECKED_BY, item.REMARKS
        ].some(val => (val || '').toLowerCase().includes(search));

        const matchesSite = !selectedSite || (item.SITE_ID || '') === selectedSite;

        return matchesSearch && matchesSite;
    });

    renderTable(filtered);
    
    // Update record count in summary
    document.getElementById('recordCount').textContent = filtered.length;
    document.getElementById('recordCountHeader').textContent = filtered.length;
    
    // Update submitted count for filtered view
    const submittedInFiltered = filtered.filter(item => (item.IS_SUBMIT || '').toUpperCase() === 'YES').length;
    const totalForSite = allData.filter(item => !selectedSite || (item.SITE_ID||'') === selectedSite).length;
    const toBeSubmittedInFiltered = totalForSite - submittedInFiltered;
    
    if (pieChart) {
        pieChart.data.datasets[0].data = [submittedInFiltered, Math.max(toBeSubmittedInFiltered, 0)];
        pieChart.update();
    }
}

function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="16" class="text-center text-muted py-5">No matching records found</td></tr>';
        return;
    }

    data.forEach((item, index) => {
        const isSubmitted = (item.IS_SUBMIT || '').toUpperCase() === 'YES';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${index + 1}</td>
            <td><strong>${escapeHtml(item.SITE_ID || '-')}</strong></td>
            <td>${escapeHtml(item.DATE_CHECKED || '-')}</td>
            <td>${escapeHtml(item.USER || '-')}</td>
            <td>${escapeHtml(item.NUMBER || '-')}</td>
            <td>${escapeHtml(item.LOAD_BALANCE || '-')}</td>
            <td>${escapeHtml(item.DATA_USAGE || '-')}</td>
            <td class="${isSubmitted ? 'text-success font-weight-bold' : 'text-danger'}">
                ${isSubmitted ? '<span class="badge-submitted">✓ SUBMITTED</span>' : '<span class="badge-pending">⚠ PENDING</span>'}
            </td>
            <td>${escapeHtml(getLoadIRText(item) || '-')}</td>
            <td>${escapeHtml(getDeviceIRText(item) || '-')}</td>
            <td>${escapeHtml(item.IS_PHYSICAL_OK || '-')}</td>
            <td>${escapeHtml(item.HAS_GAMES || '-')}</td>
            <td>${escapeHtml(item.IS_SYSTEM_UPDATED || '-')}</td>
            <td style="max-width:200px; word-break:break-word; white-space:normal;">${escapeHtml(item.OTHER_ISSUES || '-')}</td>
            <td style="max-width:200px; word-break:break-word; white-space:normal;">${escapeHtml(item.REMARKS || '-')}</td>
            <td>${escapeHtml(item.CHECKED_BY || '-')}</td>
        `;
        tbody.appendChild(tr);
    });
}

function escapeHtml(str) {
    if (!str) return str;
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function parseDate(value) {
    if (!value) return null;
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function addMonths(date, months) {
    if (!date || typeof months !== 'number') return null;
    const d = new Date(date.getTime());
    d.setMonth(d.getMonth() + months);
    return d;
}

function getLoadIRText(item) {
    const balance = Number(item.LOAD_BALANCE ?? item.DEVICE_BALANCE ?? 0);
    const loadTerms = Number(item.LOAD_TERMS ?? 0);
    const lastLoadDate = parseDate(item.LAST_LOAD_HISTORY);
    let nextLoadDate = null;
    if (lastLoadDate && loadTerms > 0) {
        nextLoadDate = addMonths(lastLoadDate, loadTerms);
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const isTodayBeforeNext = nextLoadDate instanceof Date && today < nextLoadDate;
    const needsLoadIR = balance < 5 && isTodayBeforeNext;
    const isComplied = item.IS_COMPLIED === 'YES' || item.IS_COMPLIED === '1' || item.IS_COMPLIED === 1;
    if (!needsLoadIR) return 'NO';
    return isComplied ? 'LOAD IR COMPLIED' : 'LOAD IR PENDING';
}

function isDeviceStatusBad(status) {
    if (!status) return false;
    const badStatuses = ['DEFECTIVE', 'DAMAGED', 'REPAIR', 'BROKEN', 'FAULTY', 'FOR REPAIR', 'NOT WORKING', 'BAD'];
    const normalized = status.toString().trim().toUpperCase();
    return badStatuses.some(s => normalized.includes(s));
}

function getDeviceIRText(item) {
    const needsDeviceIR = isDeviceStatusBad(item.DEVICE_STATUS);
    const isComplied = item.DEVICE_IR_COMPLIED === 'YES' || item.DEVICE_IR_COMPLIED === '1' || item.DEVICE_IR_COMPLIED === 1;
    if (!needsDeviceIR) return 'NO';
    return isComplied ? 'DEVICE IR COMPLIED' : 'DEVICE IR PENDING';
}

function fetchTotalDevices(site = null) {
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    let url = `/LM/datafetcher/loadcheckingdata.php?action=totaldevices&company=${encodeURIComponent(companyId)}`;
    if (site) url += `&site=${encodeURIComponent(site)}`;
    return fetch(url)
        .then(res => {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then(data => {
            return data.total || 0;
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
    tbody.innerHTML = '<tr><td colspan="16" class="text-center py-5"><i class="fas fa-spinner fa-spin mr-2"></i>Loading data...</td></tr>';

    try {
        totalDevices = await fetchTotalDevices();

        const res = await fetch(`/LM/datafetcher/loadcheckingdata.php?action=loadcheckresult&company=${encodeURIComponent(companyId)}&datefrom=${encodeURIComponent(datefrom)}&dateto=${encodeURIComponent(dateto)}`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();

        allData = Array.isArray(data) ? data : [];
        uniqueSites.clear();
        allData.forEach(item => {
            if (item.SITE_ID) uniqueSites.add(item.SITE_ID);
        });

        populateSiteFilter();
        applyFilters();
        await updateSummary();
    } catch (err) {
        console.error(err);
            tbody.innerHTML = '<tr><td colspan="16" class="text-center text-danger py-5"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load data</td></tr>';
    }
}

// EXPORT FUNCTIONALITY - Exports filtered data with proper formatting
function exportToExcel() {
    // Get current filtered data based on search and site filter
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const selectedSite = document.getElementById('siteFilter').value;
    
    const filteredData = allData.filter(item => {
        const matchesSearch = !search || [
            item.SITE_ID, item.NUMBER, item.USER, item.DATE_CHECKED, item.CHECKED_BY, item.REMARKS
        ].some(val => (val || '').toLowerCase().includes(search));
        const matchesSite = !selectedSite || (item.SITE_ID || '') === selectedSite;
        return matchesSearch && matchesSite;
    });
    
    if (filteredData.length === 0) {
        alert('No data to export. Please adjust your filters.');
        return;
    }
    
    // Prepare header names (matching visible table columns)
    const headers = [
        'SL NO', 'SITE', 'DATE CHECKED', 'USER', 'NUMBER', 
        'DATA BALANCE (GB)', 'DATA USAGE (GB)', 'STATUS',
        'LOAD IR', 'DEVICE IR',
        'PHYSICAL OK', 'HAS GAMES', 'SYSTEM UPDATED', 
        'OTHER ISSUES', 'REMARKS', 'CHECKED BY'
    ];
    
    // Prepare rows
    const rows = filteredData.map((item, idx) => [
        idx + 1,
        item.SITE_ID || '-',
        item.DATE_CHECKED || '-',
        item.USER || '-',
        item.NUMBER || '-',
        item.LOAD_BALANCE || '-',
        item.DATA_USAGE || '-',
        (item.IS_SUBMIT || '').toUpperCase() === 'YES' ? 'SUBMITTED' : 'PENDING',
            getLoadIRText(item) || '-',
            getDeviceIRText(item) || '-',
        item.IS_PHYSICAL_OK || '-',
        item.HAS_GAMES || '-',
        item.IS_SYSTEM_UPDATED || '-',
        item.OTHER_ISSUES || '-',
        item.REMARKS || '-',
        item.CHECKED_BY || '-'
    ]);
    
    // Combine headers and rows
    const sheetData = [headers, ...rows];
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(sheetData);
    
    // Set column widths for better readability
    ws['!cols'] = [
        {wch:6},   // SL NO
        {wch:20},  // SITE
        {wch:12},  // DATE
        {wch:18},  // USER
        {wch:18},  // NUMBER
        {wch:14},  // DATA BALANCE
        {wch:14},  // DATA USAGE
        {wch:12},  // STATUS
        {wch:12},  // LOAD IR
        {wch:12},  // DEVICE IR
        {wch:12},  // PHYSICAL OK
        {wch:12},  // HAS GAMES
        {wch:14},  // SYSTEM UPDATED
        {wch:30},  // OTHER ISSUES
        {wch:30},  // REMARKS
        {wch:18}   // CHECKED BY
    ];
    
    // Style the header row
    const range = XLSX.utils.decode_range(ws['!ref'] || 'A1:P1');
    for (let C = range.s.c; C <= range.e.c; ++C) {
        const cellAddress = XLSX.utils.encode_cell({r:0, c:C});
        if (!ws[cellAddress]) continue;
        ws[cellAddress].s = {
            fill: { fgColor: { rgb: "634C01" }, patternType: "solid" },
            font: { color: { rgb: "FFFFFF" }, bold: true, sz: 11 },
            alignment: { horizontal: "center", vertical: "center" }
        };
    }
    
    // Add conditional formatting for STATUS column (column H = index 7)
    // Highlight SUBMITTED in green, PENDING in red
    for (let i = 1; i <= rows.length; i++) {
        const statusCell = XLSX.utils.encode_cell({r: i, c: 7});
        if (ws[statusCell]) {
            const statusValue = ws[statusCell].v;
            if (statusValue === 'SUBMITTED') {
                ws[statusCell].s = {
                    fill: { fgColor: { rgb: "C6EFCE" }, patternType: "solid" },
                    font: { color: { rgb: "006100" }, bold: true }
                };
            } else if (statusValue === 'PENDING') {
                ws[statusCell].s = {
                    fill: { fgColor: { rgb: "FFC7CE" }, patternType: "solid" },
                    font: { color: { rgb: "9C0006" }, bold: true }
                };
            }
        }
    }
    
    // Style data rows with alternating colors and borders
    for (let i = 1; i <= rows.length; i++) {
        const isEven = i % 2 === 0;
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const cellAddress = XLSX.utils.encode_cell({r: i, c: C});
            if (ws[cellAddress]) {
                if (!ws[cellAddress].s) ws[cellAddress].s = {};
                ws[cellAddress].s.alignment = { vertical: "center" };
                if (C === 0) ws[cellAddress].s.alignment = { horizontal: "center", vertical: "center" };
                if (isEven && !ws[cellAddress].s.fill) {
                    ws[cellAddress].s.fill = { fgColor: { rgb: "F2F2F2" }, patternType: "solid" };
                }
            }
        }
    }
    
    // Create workbook and save
    const wb = XLSX.utils.book_new();
    const dateRange = `${document.getElementById('datefrom').value} to ${document.getElementById('dateto').value}`;
    const sheetName = `Load_Check_${document.getElementById('datefrom').value}`;
    XLSX.utils.book_append_sheet(wb, ws, sheetName.substring(0, 31));
    
    // Generate filename with date range and site info
    const siteSuffix = selectedSite ? `_${selectedSite}` : '';
    const filename = `LoadChecking_Results${siteSuffix}_${document.getElementById('datefrom').value}_to_${document.getElementById('dateto').value}.xlsx`;
    XLSX.writeFile(wb, filename);
}

// Auto-load on page ready
document.addEventListener('DOMContentLoaded', () => {
    loaddeviceschecked();
    
    // Add export button event listener
    const exportBtn = document.getElementById('exportExcelBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportToExcel);
    }
});

// Filter events
document.getElementById('siteFilter')?.addEventListener('change', async () => {
    applyFilters();
    await updateSummary();
});

document.getElementById('datefrom')?.addEventListener('change', () => {
    loaddeviceschecked();
});

document.getElementById('dateto')?.addEventListener('change', () => {
    loaddeviceschecked();
});
</script>

</body>
</html>