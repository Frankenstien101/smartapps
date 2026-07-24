<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>Product Master</title>
  </head>
  <body>
    <h3>DEVICE LIST</h3>

      <!-- item details -->
    <div class="card text-bg-light" data-bs-spy="scroll" style="max-width: 100%; height:100%; margin-bottom: .5rem; Font-size: 10px;">
      <div class="card-header"></div>
      <div class="card-body" style="overflow-y: auto; max-width: 100%; height: 680px;"  >
        <table id="itemsTable" class="table table-striped table-hover table-bordered table-sm " style="font-size: 10px;">
          
  <thead>
    <tr>
<th>#</th>
<th>LINEID</th>
<th>COMPANY ID</th>
<th>SITE ID</th>
<th>DEPARTMENT</th>
<th>PRINCIPAL</th>
<th>POSITION</th>
<th>BRAND</th>
<th>MODEL</th>
<th>IMEI</th>
<th>SERIAL</th>
<th>DATE DEPLOYED</th>
<th>USER</th>
<th>NUMBER</th>
<th>LOAD BALANCE</th>
<th>LAST LOAD HISTORY</th>
<th>NEXT LOAD SCHEDULE</th>
<th>DAYS LEFT</th>
<th>LOAD STATUS</th>
    </tr>
  </thead>
  <tbody>
    <!-- Filled dynamically -->
  </tbody>

            <!-- ...repeat rows as needed... -->
          </tbody>
        </table>
      </div>
    </div>

    <div class="text-right mb-0">
<button class="btn btn-success mb-2" onclick="exportToExcel()">Export to Excel</button>  </div>

  <div id="loading" style="
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: none; /* hidden by default */
    justify-content: center;
    align-items: center;
    z-index: 9999;
">
    <div style="text-align:center;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <div style="margin-top:10px;">Loading Data...</div>
    </div>
</div>

<!-- Add this before your script that calls XLSX -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <!-- ...SCRIPT TO FILL TABLE WHEN PAGE LOADS... -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    loaditems();
});

let loadedPOs = []; // Global storage

function showLoader() {
    document.getElementById("loading").style.display = "flex";
}

function hideLoader() {
    document.getElementById("loading").style.display = "none";
}

function loaditems() {
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const tbody = document.querySelector('#itemsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = ''; // Clear previous rows
    showLoader(); // Show loader before fetch

    fetch(`/LM/datafetcher/reportsdata.php?action=loaddevice&company=${encodeURIComponent(companyId)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            loadedPOs = data;
            if (!data || data.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="11" class="text-center">No items found.</td>';
                tbody.appendChild(tr);
                return;
            }

        data.forEach((item, index) => {
    const tr = document.createElement('tr');

    // Parse values safely
    const balance = Number(item.BALANCE) || 0;
    const loadStatus = (item.LOAD_STATUS || '').trim().toUpperCase();
    const lastLoadStr = item.LAST_LOAD_HISTORY || '';

    // Date checks
    let isNeverLoaded = lastLoadStr.toLowerCase() === 'never' || !lastLoadStr;
    let isOldLoad = false;
    let lastLoadDate = null;

    if (!isNeverLoaded && lastLoadStr) {
        try {
            lastLoadDate = new Date(lastLoadStr);
            const sixMonthsAgo = new Date();
            sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
            isOldLoad = lastLoadDate < sixMonthsAgo;
        } catch (e) {
            // invalid date → treat as never
            isNeverLoaded = true;
        }
    }

    // Row-level attention highlight (yellow/orange tint)
    let rowClass = '';
    if (balance < 5 || isOldLoad || isNeverLoaded || loadStatus === 'FOR LOAD') {
        rowClass = 'table-warning';  // Bootstrap: light yellow/orange
        // Alternative: 'bg-light text-dark' or custom class
    }

    // Status cell class
    let statusClass = 'text-center';
    if (loadStatus === 'OK') {
        statusClass += ' bg-success text-white';
    } else if (loadStatus === 'FOR LOAD') {
        statusClass += ' bg-danger text-white';
    } else {
        statusClass += ' bg-secondary text-white';
    }

    // Balance class
    let balanceClass = balance < 5 ? 'text-danger fw-bold' : 'text-dark';

    // Last load class & display
    let lastLoadClass = '';
    let lastLoadDisplay = lastLoadStr;
    if (isNeverLoaded) {
        lastLoadDisplay = '<span class="text-muted">Never</span>';
        lastLoadClass = 'text-warning fw-bold';
    } else if (isOldLoad) {
        lastLoadClass = 'text-warning fw-bold';
    }

    tr.className = rowClass;

    tr.innerHTML = `
        <td class="text-center">${index + 1}</td>
        <td>${item.LINEID ?? ''}</td>
        <td>${item.COMPANY_ID ?? ''}</td>
        <td>${item.SITE_ID ?? ''}</td>
        <td>${item.DEPARTMENT ?? ''}</td>
        <td>${item.PRINCIPAL ?? ''}</td>
        <td>${item.POSITION ?? ''}</td>
        <td>${item.BRAND ?? ''}</td>
        <td>${item.MODEL ?? ''}</td>
        <td>${item.IMEI ?? ''}</td>
        <td>${item.SERIAL ?? ''}</td>
        <td>${item.DATE_DEPLOYED ?? ''}</td>
        <td>${item.PERSON_USING || item.USER || ''}</td>
        <td>${item.NUMBER ?? ''}</td>
        <td class="${balanceClass} text-end">${balance.toFixed(2)}</td>
        <td class="${lastLoadClass}">${lastLoadDisplay}</td>
        <td>${item.NEXT_LOAD_SCHEDULE ?? ''}</td>
        <td>${item.DAYS_LEFT ?? ''}</td>
        <td class="${statusClass}">${loadStatus || '—'}</td>
        <!-- Optional extra columns from your table (remarks, etc.) -->
        <!-- <td>${item.REMARKS ?? ''}</td> -->
        <!-- <td>${item.DATE_ADDED ?? ''}</td> -->
    `;

    tbody.appendChild(tr);
});
        })
        .catch(err => {
            console.error('Error loading items:', err);
        })
        .finally(() => {
            hideLoader(); // Hide loader when done
        });
}

function exportToExcel1() {
    if (!loadedPOs.length) {
        alert("No data to export!");
        return;
    }

    // Convert array of objects to worksheet
    const worksheet = XLSX.utils.json_to_sheet(loadedPOs);

    // Create workbook and add worksheet
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Details");

    // Export the Excel file
    XLSX.writeFile(workbook, "DeviceList.xlsx");
}

function exportToExcel() {
    if (!loadedPOs.length) {
        alert("No data to export!");
        return;
    }

    // Convert array of objects to worksheet
    const worksheet = XLSX.utils.json_to_sheet(loadedPOs);

    // Generate CSV content from worksheet
    const csvOutput = XLSX.utils.sheet_to_csv(worksheet);

    // Create a Blob with CSV data
    const blob = new Blob([csvOutput], { type: 'text/csv;charset=utf-8;' });

    // Create a download link and trigger click
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "DeviceList.csv");
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

</script>


  </body>
</html>