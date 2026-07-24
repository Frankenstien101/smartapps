<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title>Stock ledger</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />

<!-- SHEETJS (XLSX) LIBRARY - this fixes the XLSX is not defined error -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
    #pagination {
        overflow-x: auto;
        white-space: nowrap;
    }
    #pagination .page-item {
        flex: 0 0 auto;
    }
    .card-body-scroll {
        overflow-y: auto;
        max-width: 100%;
        height: 600px;
    }
    table {
        table-layout: auto;
        width: 100%;
        border-collapse: collapse;
    }
    table th, table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 4px 8px;
    }
    .table-container {
        overflow: auto;
    }
    .table-container::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .table-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    .table-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    .seller-table {
        font-size: 9px;
        width: 100%;
        border: 1px solid #dee2e6;
    }
    .seller-table th, .seller-table td {
        padding: 2px 6px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    .seller-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .card {
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }
    .card-header {
        background-color: #e9ecef;
        font-weight: 600;
        padding: 6px 10px;
        font-size: 9px;
    }
    .filter-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 0.5rem;
    }
    .date-filter-body {
        padding: 8px;
    }
    .date-filter-body .header {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .date-filter-body input[type="date"],
    .date-filter-body button {
        font-size: 9px;
        padding: 3px 6px;
    }
    .error-message {
        color: red;
        font-size: 9px;
        margin-top: 5px;
    }
    .success-message {
        color: green;
        font-size: 9px;
        margin-top: 5px;
    }
    .sortable {
        cursor: pointer;
    }
    .sortable:hover {
        background-color: #f8f9fa;
    }
    .sort-asc::after {
        content: " ↑";
    }
    .sort-desc::after {
        content: " ↓";
    }
    @media (max-width: 768px) {
        .filter-container {
            flex-direction: column;
        }
        .card {
            width: 100% !important;
        }
    }
</style>
</head>
<body>
<h3>LOAD PURCHASED HISTORY</h3>

<div class="filter-container">
   
    <div class="card text-bg-light mb-1 justify-content-between" style="width: 24%; font-size: 9px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>SELECT DATE FILTER</span>
        </div>
        <div class="card-body date-filter-body">
            <div class="header">
                <label>Date From:</label>
                <input type="date" id="datefrom" value="<?php echo date('Y-m-d'); ?>" />
                <label>to</label>
                <input type="date" id="dateto" value="<?php echo date('Y-m-d'); ?>" />
                <button class="btn btn-primary btn-sm" onclick="loaditems()">GENERATE</button>
            </div>
            <div id="date-error" class="error-message"></div>
        </div>
    </div>
</div>

<div class="card text-bg-light" style="max-width: 100%; height: 630px; margin-bottom: 0.5rem; font-size: 9px;">
    <div class="card-header"></div>
    <div class="card-body card-body-scroll">
        <table id="itemsTable" class="table table-striped table-hover table-bordered table-sm" style="font-size: 9px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>LINEID</th>
                    <th>COMPANY ID</th>
                    <th>SITE ID</th>
                    <th>NUMBER</th>
                    <th>USER</th>
                    <th>AMOUNT PURCHASED</th>
                    <th>DATA PURCHASED</th>
                    <th>REFERENCE</th>
                    <th>DATE LOADED</th>
                    <th>LAST LOAD DATE</th>
                    <th>DATE RECORDED</th>
                    <th>RECORDED BY</th>
                    <th>CHARGED TO</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div id="table-error" class="error-message"></div>
    <div id="table-success" class="success-message"></div>
</div>

<div class="text-right mb-0">
    <button class="btn btn-success btn-sm mb-2" onclick="exportToExcel()">Export Data</button>
</div>

<div id="loading" style="
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;">
    <div style="text-align:center;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <div style="margin-top:10px;">Loading Data...</div>
    </div>
</div>

<div aria-live="polite" aria-atomic="true" style="position: fixed; bottom: 80px; right: 20px; min-width: 250px; z-index: 1080; pointer-events: none;">
  <div class="toast" id="poprocessed" data-delay="3000">
    <div class="toast-header bg-success text-white">
      <strong class="mr-auto">Exporting Data</strong>
      <small>Just now</small>
      <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">×</button>
    </div>
    <div class="toast-body">
      Generating Report, Data will be sent to download list. 
    </div>
  </div>
</div>

<script>

let loadedPOs = []; // Global storage

function showLoader() {
    document.getElementById("loading").style.display = "flex";
}

function hideLoader() {
    document.getElementById("loading").style.display = "none";
}

function loaditems() {
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const dateFrom = document.getElementById('datefrom').value;
    const dateTo = document.getElementById('dateto').value;

    const tbody = document.querySelector('#itemsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    showLoader();

    fetch(`/LM/datafetcher/reportsdata.php?action=purchasedhistory&company=${encodeURIComponent(companyId)}&datefrom=${encodeURIComponent(dateFrom)}&dateto=${encodeURIComponent(dateTo)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            loadedPOs = data;
            if (!data || data.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="14" class="text-center">No items found.</td>';
                tbody.appendChild(tr);
                return;
            }

            data.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.LINEID || ''}</td>
                    <td>${item.COMPANY_ID || ''}</td>
                    <td>${item.SITE_ID || ''}</td>
                    <td>${item.NUMBER || ''}</td>
                    <td>${item.PERSON || ''}</td>
                    <td>${item.AMOUNT || ''}</td>
                    <td>${item.DATA_ADDED || ''}</td>
                    <td>${item.REFERENCE || ''}</td>
                    <td>${item.LOAD_DATE || ''}</td>
                    <td>${item.PREVIOUS_LOAD_HISTORY || ''}</td>
                    <td>${item.DATE_RECORDED || ''}</td>
                    <td>${item.RECORDED_BY || ''}</td>
                    <td>${item.CHARGED_TO || ''}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error loading items:', err);
            document.getElementById('table-error').textContent = 'Failed to load data. Please try again.';
        })
        .finally(() => {
            hideLoader();
        });
}

function exportToExcel() {
    if (typeof XLSX === 'undefined') {
        alert("Excel export library failed to load. Please refresh the page.");
        return;
    }

    if (!loadedPOs.length) {
        alert("No data to export! Please generate the report first.");
        return;
    }

    // Optional: show toast
    const toastEl = document.getElementById("poprocessed");
    toastEl.classList.add("show");

    setTimeout(() => {
        try {
            // Create worksheet from the loaded data
            const worksheet = XLSX.utils.json_to_sheet(loadedPOs);

            // Optional: set column widths (you can adjust these)
            worksheet['!cols'] = [
                { wch: 5 },   // #
                { wch: 12 },  // LINEID
                { wch: 12 },  // COMPANY_ID
                { wch: 10 },  // SITE_ID
                { wch: 15 },  // NUMBER
                { wch: 20 },  // PERSON/USER
                { wch: 15 },  // AMOUNT
                { wch: 12 },  // DATA PURCHASED
                { wch: 25 },  // REFERENCE
                { wch: 12 },  // DATE LOADED
                { wch: 18 },  // LAST LOAD DATE
                { wch: 12 },  // DATE RECORDED
                { wch: 15 },  // RECORDED BY
                { wch: 15 }   // CHARGED TO
            ];

            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Purchase History");

            // Generate filename with current date
            const today = new Date().toISOString().slice(0,10).replace(/-/g, '');
            XLSX.writeFile(workbook, `PurchaseHistory_${today}.xlsx`);

            // Hide toast after a delay
            setTimeout(() => {
                toastEl.classList.remove("show");
            }, 4000);
        } catch (e) {
            console.error("Export failed:", e);
            alert("Failed to generate Excel file. Please check console for details.");
        }
    }, 400);
}

// Optional: load data on page load with today's date range
document.addEventListener("DOMContentLoaded", function () {
    loaditems(); // auto-load today's purchases
});

</script>

</body>
</html>