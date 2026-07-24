
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Resignation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
  

        .full-screen-container {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            flex-shrink: 0;
            background: white;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        .content-area {
            flex: 1;
            overflow-y: auto;
            background: #f5f7fa;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        label {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }

        .table {
            font-size: 0.82rem;
        }

        .table td, .table th {
            padding: 0.5rem 0.65rem !important;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table-container {
            max-height: 470px;          /* scrollable height — adjust as needed */
            overflow-y: auto;
            overflow-x: hidden;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3.5rem;
            opacity: 0.4;
            margin-bottom: 1rem;
        }

        .loader {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loader.show {
            display: flex;
        }

        .success-message {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            min-width: 320px;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to   { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 576px) {
            .table-container {
                max-height: 400px;
            }
        }
    </style>
</head>
<body>

<div class="full-screen-container">

    <!-- Header -->
    <div class="header">
        <div class="d-flex justify-content-between align-items-center px-4 py-3">
            <h4 class="mb-0 text-primary">
                <i class="fas fa-exchange-alt mr-2"></i> Device Resignation
            </h4>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-area p-3 p-md-4">
        <div class="row">
            <div class="col-12">

                <!-- Filters Card -->
                <div class="card mb-4">
    <div class="card-header bg-white border-0">
        <h5 class="mb-0">
            <i class="fas fa-search text-info mr-2"></i> Find Devices
        </h5>
    </div>
    <div class="card-body pb-2">
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <!-- Search field - narrower -->
            <div style="width: 280px; min-width: 220px;">
                <label class="form-label mb-1">Search (IMEI / Serial / Model)</label>
                <input type="text" class="form-control form-control-sm " id="searchDevice" placeholder="Type to filter...">
            </div>

            <!-- Site dropdown - compact -->
            <div style="width: 180px;">
                <label class="form-label mb-1">Site</label>
                <select class="form-control form-control-sm ml-1" id="filterSite">
                    <option value="">All Sites</option>
                </select>
            </div>

            <!-- Status dropdown - compact -->
            <div style="width: 160px;">
                <label class="form-label mb-1">Status</label>
                <select class="form-control form-control-sm ml-2" id="filterStatus">
                    <option value="">ALL</option>
                    <option value="IN USE">IN USE</option>
                    <option value="AVAILABLE">AVAILABLE</option>
                </select>
            </div>

            <!-- Refresh button - small width, aligned to left -->
            <div>
                <button class="btn btn-info btn-sm px-4 ml-3" onclick="loadAvailableDevices()">
                    <i class="fas fa-sync mr-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>
</div>

                <!-- Available Devices Card -->
                <div class="card">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 text-muted">Available Devices</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-container">
                            <table id="devicesTable" class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>IMEI</th>
                                        <th>Serial</th>
                                        <th>Model</th>
                                        <th>Site</th>
                                        <th>User</th>
                                        <th>Dept</th>
                                        <th>Number</th>
                                        <th>Load Status</th>
                                        <th width="120">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="devicesTableBody">
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <p class="mt-3">Click "Refresh" to load devices</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Single Device Resign Modal -->
<div class="modal fade" id="deviceDetailsModal" tabindex="-1" role="dialog" aria-labelledby="deviceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deviceDetailsModalLabel">Device Details & Resignation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="deviceDetailsContent">
                <!-- Details + remarks will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="resignSingleDevice()">
                    <i class="fas fa-check-circle mr-1"></i> Resign This Device
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loader -->
<div class="loader" id="loader">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width: 3.5rem; height: 3.5rem;"></div>
        <div class="mt-3 text-primary font-weight-bold">Processing...</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<script>
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const allDevices = [];
    let currentDeviceForResign = null;

    document.addEventListener('DOMContentLoaded', () => {
        if (!companyId || companyId.trim() === '') {
            showMessage('Company ID not found. Please log in again.', 'danger');
            return;
        }
        loadAvailableDevices();
    });

    function loadAvailableDevices() {
        showLoader(true);
        const search = document.getElementById('searchDevice')?.value?.toLowerCase() || '';
        const site = document.getElementById('filterSite')?.value || '';
        const status = document.getElementById('filterStatus')?.value || '';

        fetch(`/LM/datafetcher/resigndata.php?action=getdevices&company=${encodeURIComponent(companyId)}`)
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(data => {
                if (!Array.isArray(data)) {
                    if (data?.error) throw new Error(data.error + (data.message ? ': ' + data.message : ''));
                    throw new Error('Invalid response format');
                }

                allDevices.length = 0;
                allDevices.push(...data);
                populateSites(data);
                filterAndDisplayDevices(search, site, status);
                showLoader(false);
            })
            .catch(err => {
                console.error(err);
                showMessage('Failed to load devices: ' + err.message, 'danger');
                showLoader(false);
            });
    }

    function populateSites(devices) {
        const sites = [...new Set(devices.map(d => d.SITE_ID).filter(Boolean))].sort();
        const select = document.getElementById('filterSite');
        const current = select.value;

        select.innerHTML = '<option value="">All Sites</option>';
        sites.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s;
            select.appendChild(opt);
        });
        select.value = current;
    }

    function filterAndDisplayDevices(search = '', site = '', status = '') {
        const filtered = allDevices.filter(d => {
            const matchSearch = !search ||
                d.IMEI?.toLowerCase().includes(search) ||
                d.SERIAL?.toLowerCase().includes(search) ||
                d.MODEL?.toLowerCase().includes(search) ||
                d.BRAND?.toLowerCase().includes(search) ||
                d.NAME_OF_USER?.toLowerCase().includes(search);;

            const matchSite  = !site || d.SITE_ID === site;
            const matchStatus = !status || d.STATUS === status;

            return matchSearch && matchSite && matchStatus;
        });

        displayDevices(filtered);
    }

    function displayDevices(devices) {
        const tbody = document.getElementById('devicesTableBody');

        if (!devices?.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p class="mt-3">No devices found</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = devices.map(device => `
            <tr>
                <td>${device.IMEI || '-'}</td>
                <td>${device.SERIAL || '-'}</td>
                <td>${device.BRAND || ''} ${device.MODEL || ''}</td>
                <td>${device.SITE_ID || '-'}</td>
                <td>${device.PERSON_USING || '-'}</td>
                <td>${device.DEPARTMENT || '-'}</td>
                <td>${device.NUMBER || '-'}</td>
                <td><span class="badge badge-info">${device.LOAD_STATUS || 'Unknown'}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewDeviceDetails('${device.LINEID}')">
                        <i class="fas fa-user-times mr-1"></i> Resign
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function viewDeviceDetails(lineId) {
        const device = allDevices.find(d => d.LINEID === lineId);
        if (!device) return;

        currentDeviceForResign = device;

        const content = `
            <table class="table table-sm">
                <tr><th width="40%">IMEI</th><td>${device.IMEI || '-'}</td></tr>
                <tr><th>Serial</th><td>${device.SERIAL || '-'}</td></tr>
                <tr><th>Brand</th><td>${device.BRAND || '-'}</td></tr>
                <tr><th>Model</th><td>${device.MODEL || '-'}</td></tr>
                <tr><th>Site</th><td>${device.SITE_ID || '-'}</td></tr>
                <tr><th>Department</th><td>${device.DEPARTMENT || '-'}</td></tr>
                <tr><th>Current User</th><td>${device.PERSON_USING || '-'}</td></tr>
                <tr><th>Phone Number</th><td>${device.NUMBER || '-'}</td></tr>
                <tr><th>Status</th><td><span class="badge badge-info">${device.LOAD_STATUS || '-'}</span></td></tr>
                <tr><th>Date Deployed</th><td>${device.DATE_DEPLOYED || '-'}</td></tr>
            </table>
            <div class="form-group mt-4">
                <label for="resignRemarks">Remarks <span class="text-danger">*</span></label>
                <textarea id="resignRemarks" class="form-control" rows="3" placeholder="Enter reason / remarks for resignation (required)" required></textarea>
            </div>
        `;

        document.getElementById('deviceDetailsContent').innerHTML = content;
        $('#deviceDetailsModal').modal('show');
    }

    function resignSingleDevice() {
        if (!currentDeviceForResign?.LINEID) {
            showMessage('No device selected', 'warning');
            return;
        }

        const remarks = document.getElementById('resignRemarks')?.value?.trim();
        if (!remarks) {
            alert('Please enter remarks for resignation');
            return;
        }

        if (!confirm(`Resign device ${currentDeviceForResign.IMEI || currentDeviceForResign.SERIAL || currentDeviceForResign.LINEID}?`)) {
            return;
        }

        showLoader(true);

        fetch('/LM/datafetcher/resigndata.php?action=resign', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lineId: currentDeviceForResign.LINEID,
                company: companyId,
                remarks
            })
        })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(data => {
            showLoader(false);
            if (data.success) {
                showMessage(data.message || 'Device resigned successfully', 'success');
                $('#deviceDetailsModal').modal('hide');
                currentDeviceForResign = null;
                loadAvailableDevices();
            } else {
                showMessage(data.message || 'Failed to resign device', 'danger');
            }
        })
        .catch(err => {
            showLoader(false);
            showMessage('Error: ' + err.message, 'danger');
        });
    }

    // Filter events
    ['input', 'change'].forEach(event => {
        ['searchDevice', 'filterSite', 'filterStatus'].forEach(id => {
            document.getElementById(id)?.addEventListener(event, () => {
                const s = document.getElementById('searchDevice').value.toLowerCase();
                const site = document.getElementById('filterSite').value;
                const status = document.getElementById('filterStatus').value;
                filterAndDisplayDevices(s, site, status);
            });
        });
    });

    function showLoader(show) {
        document.getElementById('loader').classList.toggle('show', show);
    }

    function showMessage(message, type = 'info') {
        const icons = {
            success: 'check-circle',
            danger: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show success-message" role="alert">
                <i class="fas fa-${icons[type] || 'info-circle'} mr-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert">×</button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', alert);

        setTimeout(() => {
            document.querySelectorAll('.success-message').forEach(el => el.remove());
        }, 5000);
    }
</script>

</body>
</html>