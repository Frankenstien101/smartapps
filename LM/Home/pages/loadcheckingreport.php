<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title>Device Checking Result</title>
<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- XLSX for export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<style>

    .filter-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 0.75rem;
    }
    
    .card {
        border: 1px solid #d0d7de;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        border-radius: 10px;
        background: white;
    }
    .card-header {
        background-color: #f0f3f8;
        font-weight: 600;
        padding: 6px 12px;
        font-size: 10px;
        border-bottom: 1px solid #d0d7de;
    }
    .date-filter-body {
        padding: 8px 12px 10px 12px;
    }
    .date-filter-body .header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }
    .date-filter-body input[type="date"],
    .date-filter-body button {
        font-size: 9px;
        padding: 3px 8px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }
    .btn-primary {
        background: #2a6f97;
        border: none;
    }
    .btn-primary:hover { background: #1e4e6f; }
    .btn-success {
    background: #2b7a4b;
    border: none;
    margin-left: auto;
}
    .btn-success:hover { background: #1e5c38; }
    
    /* Summary Cards */
    .summary-card {
        background: white;
        border-radius: 8px;
        padding: 6px 12px;
        border-left: 4px solid #2a6f97;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .summary-card .label {
        color: #5e6f8d;
        font-weight: 500;
        letter-spacing: 0.3px;
        font-size: 8px;
    }
    .summary-card .value {
        font-size: 18px;
        font-weight: 700;
        color: #1a2639;
        line-height: 1.2;
    }
    .summary-card.submitted { border-left-color: #28a745; }
    .summary-card.not-submitted { border-left-color: #dc3545; }
    .summary-card.physical-ok { border-left-color: #17a2b8; }
    
    /* Table with fixed header */
    .table-wrapper {
        position: relative;
        overflow: auto;
        height: 550px;
    }
    
    .table-wrapper table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    
    .table-wrapper table thead th {
        position: sticky;
        top: 0;
        background: #e9edf4;
        z-index: 10;
        border-bottom: 2px solid #dee2e6;
        padding: 4px 8px;
        white-space: nowrap;
    }
    
    .table-wrapper table tbody td {
        padding: 4px 8px;
        border: 1px solid #dee2e6;
        white-space: nowrap;
    }
    
    .table-wrapper table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    .table-wrapper table tbody tr:hover {
        background-color: #e9ecef;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0 8px;
        border-radius: 30px;
        font-weight: 500;
    }
    .badge-submitted {
        background: #d4edda;
        color: #155724;
    }
    .badge-notsubmitted {
        background: #f8d7da;
        color: #721c24;
    }
    
    .toast { pointer-events: auto; }
    #loading {
        position: fixed;
        top:0;left:0;width:100%;height:100%;
        background: rgba(255,255,255,0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    .error-message { color: #b02a37; font-size: 9px; margin: 4px 0; }
    .success-message { color: #0f6b3d; font-size: 9px; margin: 4px 0; }
    
    @media (max-width:768px) {
        .filter-summary-row {
            flex-direction: column !important;
        }
        .filter-summary-row .filter-card {
            width: 100% !important;
        }
        .filter-summary-row .summary-cards {
            width: 100% !important;
        }
        .filter-summary-row .charts-container {
            width: 100% !important;
        }
    }
</style>
</head>
<body>

<h3>📋 DEVICE CHECKING RESULT</h3>

<!-- Filter, Summary Cards & Charts - All in One Row -->
<div class="filter-summary-row" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; align-items: stretch;">
    <!-- Filter Card -->
    <div class="card filter-card" style="flex: 0 0 auto; min-width: 280px;">
        <div class="card-header">📅 SELECT DATE FILTER</div>
        <div class="card-body date-filter-body">
            <div class="header">
                <label>From:</label>
                <input type="date" id="datefrom" value="<?php echo date('Y-m-d'); ?>" />
                <label>to</label>
                <input type="date" id="dateto" value="<?php echo date('Y-m-d'); ?>" />
                <button class="btn btn-primary btn-sm" onclick="loaditems()">⟳ GENERATE</button>
            </div>
            <div id="date-error" class="error-message"></div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards" style="flex: 1; display: flex; gap: 8px; flex-wrap: wrap; min-width: 300px;">
        <div class="summary-card" style="flex: 1; min-width: 80px;">
            <div class="label">📦 Total</div>
            <div class="value" id="totalDevices">0</div>
        </div>
        <div class="summary-card submitted" style="flex: 1; min-width: 80px;">
            <div class="label">✅ Submitted</div>
            <div class="value" id="submittedCount">0</div>
        </div>
        <div class="summary-card not-submitted" style="flex: 1; min-width: 80px;">
            <div class="label">⏳ Not Sub</div>
            <div class="value" id="notSubmittedCount">0</div>
        </div>
        <div class="summary-card physical-ok" style="flex: 1; min-width: 80px;">
            <div class="label">🔧 Physical OK</div>
            <div class="value" id="physicalOkCount">0</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-container" style="flex: 0 0 auto; display: flex; gap: 10px; min-width: 200px;">
        <div style="width: 120px; height: 100px;"><canvas id="submissionChart"></canvas></div>
        <div style="width: 120px; height: 100px;"><canvas id="physicalChart"></canvas></div>
    </div>
</div>

<!-- Table Card -->
<div class="card" style="max-width:100%; margin-bottom:0.5rem;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>📋 Device Records</span>
        <button class="btn btn-success btn-sm" onclick="exportToExcel()">⬇ Export Excel</button>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table id="itemsTable" class="table table-striped table-hover table-bordered table-sm" style="font-size:9px; margin:0;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>LINEID</th>
                        <th>COMPANY ID</th>
                        <th>SITE ID</th>
                        <th>DEPARTMENT</th>
                        <th>PRINCIPAL</th>
                        <th>DATE CHECKED</th>
                        <th>DATE DEPLOYED</th>
                        <th>USER</th>
                        <th>NUMBER</th>
                        <th>LAST LOAD DATE</th>
                        <th>LOAD BALANCE</th>
                        <th>DATA USAGE</th>
                        <th>IS SUBMITTED</th>
                        <th>IS PHYSICAL_OK</th>
                        <th>HAS GAMES</th>
                        <th>IS SYSTEM UPDATED</th>
                        <th>OTHER ISSUES</th>
                        <th>DEVICE STATUS</th>
                        <th>REASON CODE</th>
                        <th>DATE SURRENDERED</th>
                        <th>DAYS TO REPAIR</th>
                        <th>TEMPORARY DEVICE</th>
                        <th>IT RECOMMENDATION</th>
                        <th>CHARGED TO</th>
                        <th>REMARKS</th>
                        <th>CHECKED BY</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div id="table-error" class="error-message px-2"></div>
    <div id="table-success" class="success-message px-2"></div>
</div>

<!-- Toast -->
<div aria-live="polite" aria-atomic="true" style="position: fixed; bottom: 80px; right: 20px; min-width: 250px; z-index: 1080;">
    <div class="toast" id="poprocessed" data-delay="3000">
        <div class="toast-header bg-success text-white">
            <strong class="mr-auto">📤 Export</strong>
            <small>now</small>
            <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">&times;</button>
        </div>
        <div class="toast-body">Report generated – download started.</div>
    </div>
</div>

<!-- Loader -->
<div id="loading"><div style="text-align:center;"><div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div><div style="margin-top:10px;">Loading...</div></div></div>

<script>
    // ============================================
    // GLOBAL VARIABLES
    // ============================================
    let loadedPOs = [];
    let chartSubmission = null;
    let chartPhysical = null;

    // ============================================
    // HELPER FUNCTIONS
    // ============================================
    function showLoader() { 
        document.getElementById('loading').style.display = 'flex'; 
    }
    
    function hideLoader() { 
        document.getElementById('loading').style.display = 'none'; 
    }

    // ============================================
    // RENDER TABLE
    // ============================================
    function renderTable(data) {
        const tbody = document.querySelector('#itemsTable tbody');
        tbody.innerHTML = '';
        
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="23" class="text-center">No items found.</td></tr>';
            return;
        }
        
        data.forEach((item, idx) => {
            const tr = document.createElement('tr');
            const submitted = (item.IS_SUBMIT || '').toLowerCase() === 'yes';
            const statusBadge = submitted 
                ? '<span class="status-badge badge-submitted">✅ Yes</span>'
                : '<span class="status-badge badge-notsubmitted">⏳ No</span>';

            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td>${item.LINEID || ''}</td>
                <td>${item.COMPANY_ID || ''}</td>
                <td>${item.SITE_ID || ''}</td>
                <td>${item.DEPARTMENT || ''}</td>
                <td>${item.PRINCIPAL || ''}</td>
                <td>${item.DATE_CHECKED || ''}</td>
                <td>${item.DATE_DEPLOYED || ''}</td>
                <td>${item.USER || ''}</td>
                <td>${item.NUMBER || ''}</td>
                <td>${item.LAST_LOAD_HISTORY || ''}</td>
                <td>${item.LOAD_BALANCE || ''}</td>
                <td>${item.DATA_USAGE || ''}</td>
                <td>${statusBadge}</td>
                <td>${item.IS_PHYSICAL_OK || ''}</td>
                <td>${item.HAS_GAMES || ''}</td>
                <td>${item.IS_SYSTEM_UPDATED || ''}</td>
                <td>${item.OTHER_ISSUES || ''}</td>
                <td>${item.DEVICE_STATUS || ''}</td>
                <td>${item.REASON_CODE || ''}</td>
                <td>${item.DATE_SURRENDERED || ''}</td>
                <td>${item.DAYS_TO_REPAIR || ''}</td>
                <td>${item.TEMPORARY_DEVICE || ''}</td>
                <td>${item.IT_RECOMMENDATION || ''}</td>
                <td>${item.CHARGED_TO || ''}</td>
                <td>${item.REMARKS || ''}</td>
                <td>${item.CHECKED_BY || ''}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // ============================================
    // UPDATE SUMMARY AND CHARTS
    // ============================================
    function updateSummaryAndCharts(data) {
        const total = data.length;
        const submitted = data.filter(d => (d.IS_SUBMIT || '').toLowerCase() === 'yes').length;
        const notSubmitted = total - submitted;
        const physicalOk = data.filter(d => (d.IS_PHYSICAL_OK || '').toLowerCase() === 'yes').length;

        document.getElementById('totalDevices').textContent = total;
        document.getElementById('submittedCount').textContent = submitted;
        document.getElementById('notSubmittedCount').textContent = notSubmitted;
        document.getElementById('physicalOkCount').textContent = physicalOk;

        // Update Charts
        const ctx1 = document.getElementById('submissionChart').getContext('2d');
        const ctx2 = document.getElementById('physicalChart').getContext('2d');

        if (chartSubmission) chartSubmission.destroy();
        if (chartPhysical) chartPhysical.destroy();

        chartSubmission = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Submitted', 'Not Submitted'],
                datasets: [{
                    data: [submitted, notSubmitted],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            boxWidth: 8, 
                            font: { size: 7 },
                            padding: 4
                        } 
                    }
                }
            }
        });

        chartPhysical = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Physical OK', 'Not OK / Unknown'],
                datasets: [{
                    data: [physicalOk, total - physicalOk],
                    backgroundColor: ['#17a2b8', '#ffc107'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            boxWidth: 8, 
                            font: { size: 7 },
                            padding: 4
                        } 
                    }
                }
            }
        });
    }

    // ============================================
    // LOAD DATA FROM BACKEND
    // ============================================
    function loaditems() {
        const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
        const dateFrom = document.getElementById('datefrom').value;
        const dateTo = document.getElementById('dateto').value;

        if (!dateFrom || !dateTo) {
            document.getElementById('date-error').textContent = 'Please select both dates.';
            return;
        }
        document.getElementById('date-error').textContent = '';

        const tbody = document.querySelector('#itemsTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        showLoader();

        fetch(`/LM/datafetcher/checkingreportdata.php?action=get_checking_results&company=${encodeURIComponent(companyId)}&datefrom=${encodeURIComponent(dateFrom)}&dateto=${encodeURIComponent(dateTo)}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                loadedPOs = data;
                renderTable(loadedPOs);
                updateSummaryAndCharts(loadedPOs);
                
                document.getElementById('table-success').textContent = `Loaded ${data.length} records`;
                setTimeout(() => {
                    document.getElementById('table-success').textContent = '';
                }, 3000);
            })
            .catch(err => {
                console.error('Error loading items:', err);
                document.getElementById('table-error').textContent = 'Error loading data: ' + err.message;
            })
            .finally(() => {
                hideLoader();
            });
    }

    // ============================================
    // EXPORT TO EXCEL - Direct from table data
    // ============================================
    function exportToExcel() {
        if (!loadedPOs.length) {
            alert("No data to export!");
            return;
        }

        const table = document.getElementById('itemsTable');
        const rows = table.querySelectorAll('tbody tr');
        
        if (rows.length === 0 || (rows.length === 1 && rows[0].textContent.includes('No items found'))) {
            alert("No data to export!");
            return;
        }

        const headers = [];
        const headerCells = table.querySelectorAll('thead th');
        headerCells.forEach(th => {
            headers.push(th.textContent.trim());
        });

        const data = [];
        rows.forEach(row => {
            const rowData = {};
            const cells = row.querySelectorAll('td');
            cells.forEach((td, index) => {
                if (index < headers.length) {
                    if (headers[index] === 'IS SUBMITTED') {
                        const badge = td.querySelector('.status-badge');
                        rowData[headers[index]] = badge ? badge.textContent.trim() : td.textContent.trim();
                    } else {
                        rowData[headers[index]] = td.textContent.trim();
                    }
                }
            });
            data.push(rowData);
        });

        const ws = XLSX.utils.json_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "DeviceCheck");
        XLSX.writeFile(wb, "DeviceCheckingResult.xlsx");
        
        $('#poprocessed').toast('show');
    }

    // ============================================
    // VIEW UNSUBMITTED DEVICES
    // ============================================
    function viewUnsubmitted() {
        const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
        const dateFrom = document.getElementById('datefrom').value;
        const dateTo = document.getElementById('dateto').value;

        showLoader();
        
        fetch(`/LM/datafetcher/checkingreportdata.php?action=get_unsubmitted_devices&company=${encodeURIComponent(companyId)}&datefrom=${encodeURIComponent(dateFrom)}&dateto=${encodeURIComponent(dateTo)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                if (data.length === 0) {
                    alert('All devices have been submitted for the selected date range!');
                } else {
                    alert(`Found ${data.length} devices not submitted.\n\nCheck the table for details.`);
                    const unsubmittedNumbers = data.map(d => d.NUMBER);
                    const filteredData = loadedPOs.filter(d => unsubmittedNumbers.includes(d.NUMBER));
                    renderTable(filteredData);
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
            })
            .finally(() => {
                hideLoader();
            });
    }

    // ============================================
    // AUTO-LOAD ON PAGE READY
    // ============================================
    document.addEventListener("DOMContentLoaded", function() {
        loaditems();
    });
</script>

<!-- Bootstrap JS for toast -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>

</body>
</html>