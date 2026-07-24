<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title>Load Checking</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" />

<style>
    .card-body-scroll { overflow-y: auto; max-width: 100%; height: 600px; }
    table { table-layout: auto; width: 100%; border-collapse: collapse; }
    table th, table td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 4px 8px; }
    .table-container::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
    .table-container::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
    .table-container::-webkit-scrollbar-thumb:hover { background: #555; }
    .card { border: 1px solid #dee2e6; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px; }
    .card-header { background-color: #e9ecef; font-weight: 600; padding: 6px 10px; font-size: 9px; }
    .error-message { color: red; font-size: 9px; margin-top: 5px; }
    .success-message { color: green; font-size: 9px; margin-top: 5px; }
    @media (max-width: 768px) { 
        .card { width: 100% !important; } 
    }
    .modern-input {
        border: 1px solid #d1d9e0;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-size: 9.5px !important;
        height: 28px;
        padding: 4px 10px;
    }
    .modern-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        outline: none;
    }
    .form-check-input:checked {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }
    .card { transition: box-shadow 0.2s; }
    .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .text-muted { color: #6b7280 !important; }


    /* Checklist enhancements */
.checklist-radio {
    transform: scale(1.3);
    margin-top: 0.15rem;
    cursor: pointer;
}

.form-check {
    min-width: 60px;
}

.form-check-label {
    user-select: none;
    color: #495057;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Better spacing on mobile */
@media (max-width: 576px) {
    .d-flex.gap-5 {
        gap: 3rem !important;
    }
    .checklist-radio {
        transform: scale(1.4);
    }
}

</style>
</head>
<body>
<h3>LOAD CHECKING RESULT</h3>
<div class="card text-bg-light" style="max-width: 100%; height: 750px; margin-bottom: 0.5rem; font-size: 9px;">

    <!-- CARD HEADER -->
    <div class="card-header d-flex align-items-center justify-content-between py-1 px-2" style="min-height:32px;">
        
        <div class="d-flex align-items-end gap-2">
            <div class="form-group mb-0">
                <input type="date" id="datefrom"
                       class="form-control form-control-sm"
                       value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group mb-0 ml-2">
                <input type="date" id="dateto"
                       class="form-control form-control-sm"
                       value="<?php echo date('Y-m-d'); ?>">
            </div>

            <button class="btn btn-primary btn-sm px-3 ml-2" onclick="loaddeviceschecked()">
                <i class="fas fa-filter mr-1"></i> Generate
            </button>
        </div>

    </div>

    <div class="card-body card-body-scroll p-2" style="height: calc(100% - 32px); overflow-y: auto;">
        <table id="itemsTable"
               class="table table-striped table-hover table-bordered table-sm mb-0"
               style="font-size: 9px;">
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
                    <th>IS PHYSICALLY OK</th>
                    <th>HAS GAMES</th>
                    <th>IS SYSTEM UPDATED</th>
                    <th>OTHER ISSUES</th>
                    <th>CHECKED BY</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="table-error" class="error-message text-danger small p-1"></div>
    <div id="table-success" class="success-message text-success small p-1"></div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>

<script>
let loadedPOs = [];

function setRadio(name, value) {
    const radio = document.querySelector(`input[name="${name}"][value="${value}"]`);
    if (radio) {
        radio.checked = true;
    } else {
        const defaultValue = (name === 'games') ? 'No' : 'Yes';
        const defaultRadio = document.querySelector(`input[name="${name}"][value="${defaultValue}"]`);
        if (defaultRadio) defaultRadio.checked = true;
        console.warn(`Radio not found for ${name}=${value} → defaulted to ${defaultValue}`);
    }
}
function isForLoad(lastLoadDate, balance) {
   
if (Number(balance) < 5) return true;

    if (lastLoadDate) {
        const lastLoad = new Date(lastLoadDate);
        const now = new Date();

        if (!isNaN(lastLoad)) {
            const diffMonths =
                (now.getFullYear() - lastLoad.getFullYear()) * 12 +
                (now.getMonth() - lastLoad.getMonth());

            if (diffMonths >= 6) return true;
        }
    }
    return false;
}

function getBalanceColor(balance) {
    const bal = Number(balance);
    if (bal < 1) return 'red';
    if (bal < 5) return 'yellow';
    return 'green';
}

function loaddeviceschecked() {
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const datefrom  = document.getElementById('datefrom').value;
    const dateto    = document.getElementById('dateto').value;

    const tbody = document.querySelector('#itemsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = ''; 

    tbody.innerHTML = `
        <tr>
            <td colspan="12" class="text-center">Loading...</td>
        </tr>
    `;

    fetch(`/LM/datafetcher/loadcheckingdata.php?action=loadcheckresult&company=${encodeURIComponent(companyId)}&datefrom=${encodeURIComponent(datefrom)}&dateto=${encodeURIComponent(dateto)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            tbody.innerHTML = ''; 

            if (!data || data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="13" class="text-center">No items found.</td>
                    </tr>
                `;
                return;
            }

            data.forEach((item, index) => {

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.SITE_ID || ''}</td>
                    <td>${item.DATE_CHECKED || ''}</td>
                    <td>${item.USER || ''}</td>
                    <td>${item.NUMBER || ''}</td>
                    <td>${item.LOAD_BALANCE || ''}</td>
                    <td>${item.DATA_USAGE || ''}</td>
                    <td>${item.IS_SUBMIT || ''}</td>
                    <td>${item.IS_PHYSICAL_OK || ''}</td>
                    <td>${item.HAS_GAMES || ''}</td>
                    <td>${item.IS_SYSTEM_UPDATED || ''}</td>
                    <td>${item.OTHER_ISSUES || ''}</td>
                    <td>${item.CHECKED_BY || ''}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error loading items:', err);
            tbody.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center text-danger">Failed to load data.</td>
                </tr>
            `;
        });
}

function exportToExcel() {
    if (!loadedPOs.length) {
        alert("No data to export!");
        return;
    }
    const worksheet = XLSX.utils.json_to_sheet(loadedPOs);

    const csvOutput = XLSX.utils.sheet_to_csv(worksheet);

    const blob = new Blob([csvOutput], { type: 'text/csv;charset=utf-8;' });

    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", ".csv");
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

</script>

  <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>

</body>
</html>