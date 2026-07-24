<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Resigned Devices Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-primary"><i class="fas fa-file-csv mr-2"></i> Resigned Devices Report</h4>
        <div>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.history.back()">Back</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="filterForm" class="form-inline">
                <label class="mr-2">From</label>
                <input type="date" id="fromDate" class="form-control form-control-sm mr-3" value="<?php echo date('Y-m-d'); ?>"/>

                <label class="mr-2">To</label>
                <input type="date" id="toDate" class="form-control form-control-sm mr-3" value="<?php echo date('Y-m-d'); ?>"/>

                <button type="button" class="btn btn-primary btn-sm mr-2" onclick="loadResigned()">Refresh</button>
                <button type="button" class="btn btn-success btn-sm" id="exportCsv">Export CSV</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:600px; overflow:auto; font-size:smaller;">
                <table class="table table-sm table-bordered mb-0" id="resignedTable">
                    <thead class="thead-light">
                        <tr>
                            <th>LINEID</th>
                            <th>COMPANY_ID</th>
                            <th>SITE_ID</th>
                            <th>IMEI</th>
                            <th>SERIAL</th>
                            <th>BRAND</th>
                            <th>MODEL</th>
                            <th>NAME_OF_USER</th>
                            <th>DEVICE_STATUS</th>
                            <th>REMARKS</th>
                            <th>DATE_RESIGNED</th>
                            <th>PROCESS_BY</th>
                        </tr>
                    </thead>
                    <tbody id="resignedBody">
                        <tr><td colspan="12" class="text-center py-4 text-muted">Set a date range and click Refresh</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";

    document.addEventListener('DOMContentLoaded', () => {
        loadResigned()
        document.getElementById('exportCsv').addEventListener('click', () => {
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            if (!from || !to) { alert('Please set both From and To dates before exporting.'); return; }
            const url = `/LM/datafetcher/reportdata.php?action=exportresigned&company=${encodeURIComponent(companyId)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
            window.location = url;
        });
    });

    function loadResigned() {
        const from = document.getElementById('fromDate').value;
        const to = document.getElementById('toDate').value;
        if (!from || !to) { alert('Please set both From and To dates'); return; }

        fetch(`/LM/datafetcher/reportdata.php?action=resigned&company=${encodeURIComponent(companyId)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`)
            .then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
            .then(data => {
                if (!Array.isArray(data)) { console.error(data); alert('Invalid response'); return; }
                const tbody = document.getElementById('resignedBody');
                if (!data.length) {
                    tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-muted">No resigned records found for the selected range.</td></tr>';
                    return;
                }
                tbody.innerHTML = data.map(r => `
                    <tr>
                        <td>${escapeHtml(r.LINEID)}</td>
                        <td>${escapeHtml(r.COMPANY_ID)}</td>
                        <td>${escapeHtml(r.SITE_ID)}</td>
                        <td>${escapeHtml(r.IMEI)}</td>
                        <td>${escapeHtml(r.SERIAL)}</td>
                        <td>${escapeHtml(r.BRAND)}</td>
                        <td>${escapeHtml(r.MODEL)}</td>
                        <td>${escapeHtml(r.NAME_OF_USER)}</td>
                        <td>${escapeHtml(r.DEVICE_STATUS)}</td>
                        <td>${escapeHtml(r.REMARKS)}</td>
                        <td>${escapeHtml(r.DATE_RESIGNED)}</td>
                        <td>${escapeHtml(r.PROCESS_BY)}</td>
                    </tr>
                `).join('');
            })
            .catch(err => { console.error(err); alert('Failed to load report: '+err.message); });
    }

    function escapeHtml(s) { return (s===null||s===undefined)?'':String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
</body>
</html>