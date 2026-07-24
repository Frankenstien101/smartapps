<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Deployment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>

        .full-screen-container {
            min-height: 85vh;
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
            max-height: 500px;
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
            <h4 class="mb-0 text-success">
                <i class="fas fa-mobile-alt mr-2"></i> Device Deployment
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
                            <i class="fas fa-search text-info mr-2"></i> Find Available Devices
                        </h5>
                    </div>
                    <div class="card-body pb-2">
                        <div class="d-flex flex-wrap gap-3 align-items-end">
                            <!-- Search field -->
                            <div style="width: 280px; min-width: 220px;">
                                <label class="form-label mb-1">Search (IMEI / Serial / Model)</label>
                                <input type="text" class="form-control form-control-sm" id="searchDevice" placeholder="Type to filter...">
                            </div>

                            <!-- Site dropdown -->
                            <div style="width: 180px;">
                                <label class="form-label mb-1">Site</label>
                                <select class="form-control form-control-sm ml-1" id="filterSite">
                                    <option value="">All Sites</option>
                                </select>
                            </div>

                            <!-- Refresh button -->
                            <div>
                                <button class="btn btn-success btn-sm px-4 ml-3" onclick="loadAvailableDevices()">
                                    <i class="fas fa-sync mr-1"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Devices Card -->
                <div class="card">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 text-muted">Available Devices for Deployment</h5>
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
                                        <th>Status</th>
                                        <th width="120">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="devicesTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
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

<!-- Device Deployment Modal -->
<div class="modal fade" id="deploymentModal" tabindex="-1" role="dialog" aria-labelledby="deploymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="deploymentModalLabel">Deploy Device</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="deploymentContent">
                <!-- Device details + deployment form will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="deployDevice()">
                    <i class="fas fa-check-circle mr-1"></i> Deploy Device
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loader -->
<div class="loader" id="loader">
    <div class="text-center">
        <div class="spinner-border text-success" style="width: 3.5rem; height: 3.5rem;"></div>
        <div class="mt-3 text-success font-weight-bold">Processing...</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<script>
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const allDevices = [];
    let currentDeviceForDeploy = null;

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

        fetch(`/LM/datafetcher/deploydata.php?action=getavailable&company=${encodeURIComponent(companyId)}`)
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
                filterAndDisplayDevices(search, site);
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

    function filterAndDisplayDevices(search = '', site = '') {
        const filtered = allDevices.filter(d => {
            const matchSearch = !search ||
                d.IMEI?.toLowerCase().includes(search) ||
                d.SERIAL?.toLowerCase().includes(search) ||
                d.MODEL?.toLowerCase().includes(search) ||
                d.BRAND?.toLowerCase().includes(search);

            const matchSite  = !site || d.SITE_ID === site;

            return matchSearch && matchSite;
        });

        displayDevices(filtered);
    }

    function displayDevices(devices) {
        const tbody = document.getElementById('devicesTableBody');

        if (!devices?.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p class="mt-3">No available devices found</p>
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
                <td><span class="badge badge-success">${device.STATUS || 'AVAILABLE'}</span></td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="viewDeploymentForm('${device.LINEID}')">
                        <i class="fas fa-arrow-right mr-1"></i> Deploy
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function viewDeploymentForm(lineId) {
        const device = allDevices.find(d => d.LINEID === lineId);
        if (!device) return;

        currentDeviceForDeploy = device;

        const content = `
            <table class="table table-sm mb-4">
                <tr><th width="40%">IMEI</th><td>${device.IMEI || '-'}</td></tr>
                <tr><th>Serial</th><td>${device.SERIAL || '-'}</td></tr>
                <tr><th>Brand</th><td>${device.BRAND || '-'}</td></tr>
                <tr><th>Model</th><td>${device.MODEL || '-'}</td></tr>
                <tr><th>Site</th><td>${device.SITE_ID || '-'}</td></tr>
            </table>

            <h6 class="mb-3 text-primary font-weight-bold">Deployment Details</h6>
            
            <div class="form-group">
                <label for="deployUser">User Name <span class="text-danger">*</span></label>
                <input type="text" id="deployUser" class="form-control form-control-sm" placeholder="Enter user name" required>
            </div>

            <div class="form-group">
                <label for="deployDepartment">Department <span class="text-danger">*</span></label>
                <input type="text" id="deployDepartment" class="form-control form-control-sm" placeholder="Enter department" required>
            </div>

            <div class="form-group">
                <label for="deployPosition">Position <span class="text-danger">*</span></label>
                <input type="text" id="deployPosition" class="form-control form-control-sm" placeholder="Enter position" required>
            </div>

            <div class="form-group">
                <label for="deployPhoneNumber">Phone Number <span class="text-danger">*</span></label>
                <input type="text" id="deployPhoneNumber" class="form-control form-control-sm" placeholder="Enter phone number" required>
            </div>

            <div class="form-group">
                <label for="deployRemarks">Remarks</label>
                <textarea id="deployRemarks" class="form-control form-control-sm" rows="2" placeholder="Optional remarks..."></textarea>
            </div>
        `;

        document.getElementById('deploymentContent').innerHTML = content;
        $('#deploymentModal').modal('show');
    }

    function deployDevice() {
        if (!currentDeviceForDeploy?.LINEID) {
            showMessage('No device selected', 'warning');
            return;
        }

        const userName = document.getElementById('deployUser')?.value?.trim();
        const department = document.getElementById('deployDepartment')?.value?.trim();
        const position = document.getElementById('deployPosition')?.value?.trim();
        const phoneNumber = document.getElementById('deployPhoneNumber')?.value?.trim();
        const remarks = document.getElementById('deployRemarks')?.value?.trim();

        if (!userName || !department || !position || !phoneNumber) {
            alert('Please fill in all required fields');
            return;
        }

        if (!confirm(`Deploy device ${currentDeviceForDeploy.IMEI || currentDeviceForDeploy.SERIAL} to ${userName}?`)) {
            return;
        }

        showLoader(true);

        fetch('/LM/datafetcher/deploydata.php?action=deploy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lineId: currentDeviceForDeploy.LINEID,
                company: companyId,
                userName,
                department,
                position,
                phoneNumber,
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
                showMessage(data.message || 'Device deployed successfully', 'success');
                $('#deploymentModal').modal('hide');
                currentDeviceForDeploy = null;
                loadAvailableDevices();
            } else {
                showMessage(data.message || 'Failed to deploy device', 'danger');
            }
        })
        .catch(err => {
            showLoader(false);
            showMessage('Error: ' + err.message, 'danger');
        });
    }

    // Filter events
    ['input', 'change'].forEach(event => {
        ['searchDevice', 'filterSite'].forEach(id => {
            document.getElementById(id)?.addEventListener(event, () => {
                const s = document.getElementById('searchDevice').value.toLowerCase();
                const site = document.getElementById('filterSite').value;
                filterAndDisplayDevices(s, site);
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
