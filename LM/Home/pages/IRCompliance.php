<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devices For Load - IR Management</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        .status-ok {
            background-color: #28a745;
            color: white;
        }
        .status-forload {
            background-color: #dc3545;
            color: white;
        }
        .status-complied {
            background-color: #17a2b8;
            color: white;
        }
        .remarks-load-ir-complied {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }
        .remarks-load-ir-pending {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }
        .remarks-device-ir-complied {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }
        .remarks-device-ir-pending {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }
        .card-body-scroll {
            height: calc(100% - 32px);
            overflow-y: auto;
        }

        .modern-table-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
            padding: 8px 12px;
            border-radius: 6px 6px 0 0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .filter-label {
            font-size: 11px;
            font-weight: 500;
            color: #495057;
            margin-bottom: 0;
        }
        .site-select {
            min-width: 140px;
            font-size: 11px;
        }
        .search-input {
            width: 220px;
            font-size: 11px;
            border-radius: 6px;
            padding: 4px 10px;
        }
        .search-input::placeholder {
            color: #adb5bd;
        }
        .action-btn-group {
            display: flex;
            gap: 3px;
            flex-wrap: wrap;
        }
        .btn-sm-custom {
            padding: 2px 6px;
            font-size: 8px;
            line-height: 1.5;
            border-radius: 3px;
        }
        .ir-action-column {
            min-width: 80px;
        }
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
        }
        .device-count-badge {
            font-size: 11px;
            background: #e9ecef;
            padding: 2px 10px;
            border-radius: 12px;
            color: #495057;
        }
        /* Make table header sticky within the scrolling container */
        .card-body-scroll {
            position: relative;
        }
        .card-body-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <h2>IR Management</small></h2>
            </div>
        </div>

        <div class="form-row mb-2 align-items-center">
            <div class="col-auto">
                <label for="lastLoadFilter" class="mr-0 mb-0" style="font-size: 12px;">LAST LOAD:</label>
            </div>
            <div class="col-auto">
                <select id="lastLoadFilter" class="form-control form-control-sm">
                    <option value="0">All</option>
                    <option value="1">1 month ago or more</option>
                    <option value="3">3 months ago or more</option>
                    <option value="6">6 months ago or more</option>
                    <option value="9">9 months ago or more</option>
                    <option value="12">12 months ago or more</option>
                </select>
            </div>
            <div class="col-auto ml-3">
                <label class="mb-0" style="font-size: 12px;">FILTER:</label>
            </div>
            <div class="col-auto">
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksAll" value="all" checked>
                    <label class="form-check-label" for="remarksAll" style="font-size: 12px;">All</label>
                </div>
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksLoadIRPending" value="load_ir_pending">
                    <label class="form-check-label" for="remarksLoadIRPending" style="font-size: 12px;">Load IR Pending</label>
                </div>
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksLoadIRComplied" value="load_ir_complied">
                    <label class="form-check-label" for="remarksLoadIRComplied" style="font-size: 12px;">Load IR Complied</label>
                </div>
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksDeviceIRPending" value="device_ir_pending">
                    <label class="form-check-label" for="remarksDeviceIRPending" style="font-size: 12px;">Device IR Pending</label>
                </div>
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksDeviceIRComplied" value="device_ir_complied">
                    <label class="form-check-label" for="remarksDeviceIRComplied" style="font-size: 12px;">Device IR Complied</label>
                </div>
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksBadDevice" value="bad_device">
                    <label class="form-check-label" for="remarksBadDevice" style="font-size: 12px;">Bad Device Status</label>
                </div>
            </div>
        </div>

        <div class="card text-bg-light" style="max-width: 100%; height: 650px; margin-bottom: 0.5rem; font-size: 9px;">
             <div class="modern-table-header">
                <div class="filter-group">
                    <div class="d-flex align-items-center">
                        <span class="filter-label mr-2">Site:</span>
                        <select id="siteFilter" style="width: 150px;" class="form-control form-control-sm site-select">
                            <option value="">All Sites</option>
                        </select>
                        <span class="ml-2 device-count-badge" id="deviceCountBadge">0 devices</span>
                        <div class="mb-0 ml-2">
                            <button type="button" class="btn btn-warning btn-sm" onclick="refreshAllLoadStatus()">
                                <i class="fas fa-sync-alt me-2"></i> Refresh Load Status
                            </button>
                   
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-grow-1">
                        <input type="text" id="searchBox" class="form-control search-input ml-auto" 
                               placeholder="Search site, user, serial..." autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="card-body p-2">
                <div class="card-body-scroll">
                    <table id="itemsTable" class="table table-striped table-hover table-bordered table-sm mb-0" style="font-size: 9px;">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Site</th>
                                <th>Department</th>
                                <th>Principal</th>
                                <th>Position</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Serial</th>
                                <th>Date Deployed</th>
                                <th>User</th>
                                <th>Number</th>
                                <th class="text-right">Balance (GB)</th>
                                <th>Last Load</th>
                                <th>Next Load Schedule</th>
                                <th>Load Term(mo.)</th>
                                <th>Load IR Status</th>
                                <th>Load IR Action</th>
                                <th>Device Status</th>
                                <th>Reason Code</th>
                                <th>Device IR Status</th>
                                <th>Device IR Action</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="forLoadTable">
                            <tr>
                                <td colspan="22" class="text-center text-muted">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center py-1 px-2" style="min-height: 32px;">
            <button id="exportExcelBtn" class="btn btn-sm btn-success ml-auto" disabled>
                <i class="fas fa-file-excel mr-1"></i> Export to Excel
            </button>
        </div>
    </div>

    <!-- Confirmation Modal for Load IR Compliance -->
    <div class="modal fade" id="confirmLoadIRCompliedModal" tabindex="-1" role="dialog" aria-labelledby="confirmLoadIRModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="confirmLoadIRModalLabel">Confirm Load IR Compliance</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to mark this device as <strong>LOAD IR COMPLIED</strong>?<br>
                    <small class="text-muted">This will update the IS_COMPLIED column to 'YES'.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmLoadIRCompliedBtn">Yes, Mark as Load IR Complied</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Device IR Compliance -->
    <div class="modal fade" id="confirmDeviceIRCompliedModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeviceIRModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="confirmDeviceIRModalLabel">Confirm Device IR Compliance</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to mark this device as <strong>DEVICE IR COMPLIED</strong>?<br>
                    <small class="text-muted">This will update the DEVICE_IR_COMPLIED column to 'YES'.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmDeviceIRCompliedBtn">Yes, Mark as Device IR Complied</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let forLoadDevicesData = [];
        let currentFilterMonths = 0;
        let currentRemarksFilter = 'all';
        let currentSiteFilter = '';
        let currentSearchTerm = '';

        let pendingLoadIRLineId = null;
        let pendingDeviceIRLineId = null;

        // Define bad device statuses
        const BAD_DEVICE_STATUSES = ['DEFECTIVE', 'DAMAGED', 'REPAIR', 'BROKEN', 'FAULTY', 'FOR REPAIR', 'NOT WORKING', 'BAD'];

        // API Base URL
        const API_URL = '/LM/datafetcher/ir_backend.php';

        function monthsAgo(dateStr) {
            if (!dateStr || dateStr === 'Never' || dateStr.trim() === '') return 999;

            try {
                const lastDate = new Date(dateStr.trim());
                if (isNaN(lastDate.getTime())) return 999;

                const now = new Date();
                const yearDiff = now.getFullYear() - lastDate.getFullYear();
                const monthDiff = now.getMonth() - lastDate.getMonth();
                let totalMonths = yearDiff * 12 + monthDiff;

                if (now.getDate() < lastDate.getDate()) {
                    totalMonths--;
                }
                return totalMonths;
            } catch (e) {
                return 999;
            }
        }

        function isDeviceStatusBad(deviceStatus) {
            if (!deviceStatus) return false;
            const status = deviceStatus.toUpperCase().trim();
            return BAD_DEVICE_STATUSES.some(badStatus => status.includes(badStatus));
        }

        function populateSiteFilter() {
            const siteSelect = document.getElementById('siteFilter');
            const sites = new Set();

            forLoadDevicesData.forEach(item => {
                if (item.SITE_ID && item.SITE_ID.trim()) {
                    sites.add(item.SITE_ID.trim());
                }
            });

            siteSelect.innerHTML = '<option value="">All Sites</option>';

            Array.from(sites).sort().forEach(site => {
                const option = document.createElement('option');
                option.value = site;
                option.textContent = site;
                siteSelect.appendChild(option);
            });
        }

        function checkLoadIRStatus(item) {
            const balance = Number(item.BALANCE ?? 0);
            const nextScheduleStr = item.NEXT_LOAD_SCHEDULE ?? '';
            
            // Check IS_COMPLIED column for Load IR
            const isLoadIRComplied = item.IS_COMPLIED === "YES" || 
                                     item.IS_COMPLIED === "1" || 
                                     item.IS_COMPLIED === 1;

            let isTodayBeforeNext = false;
            if (nextScheduleStr && nextScheduleStr.trim() !== '') {
                try {
                    const [year, month, day] = nextScheduleStr.trim().split('-').map(Number);
                    const nextDate = new Date(year, month - 1, day);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (!isNaN(nextDate.getTime())) {
                        isTodayBeforeNext = today < nextDate;
                    }
                } catch (e) {}
            }

            const needsLoadIR = balance < 5 && isTodayBeforeNext;

            return {
                needsIR: needsLoadIR,
                isComplied: isLoadIRComplied,
                status: needsLoadIR ? (isLoadIRComplied ? 'COMPLIED' : 'PENDING') : ''
            };
        }

        function checkDeviceIRStatus(item) {
            // Check DEVICE_IR_COMPLIED column
            const isDeviceIRComplied = item.DEVICE_IR_COMPLIED === "YES" || 
                                       item.DEVICE_IR_COMPLIED === "1" || 
                                       item.DEVICE_IR_COMPLIED === 1;

            // Device IR is based on DEVICE_STATUS - if status is bad, needs IR
            const deviceStatus = item.DEVICE_STATUS || '';
            const needsDeviceIR = isDeviceStatusBad(deviceStatus);

            return {
                needsIR: needsDeviceIR,
                isComplied: isDeviceIRComplied,
                status: needsDeviceIR ? (isDeviceIRComplied ? 'COMPLIED' : 'PENDING') : ''
            };
        }

        function renderTable(filteredData) {
            const tbody = document.getElementById('forLoadTable');
            tbody.innerHTML = '';

            // Update device count
            document.getElementById('deviceCountBadge').textContent = filteredData.length + ' devices';

            if (filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="22" class="text-center text-muted">No matching devices</td></tr>`;
                return;
            }

            filteredData.forEach((item, index) => {
                const statusClass = item.LOAD_STATUS === 'FOR LOAD' ? 'status-forload' : 'status-ok';
                const loadIRStatus = checkLoadIRStatus(item);
                const deviceIRStatus = checkDeviceIRStatus(item);
                
                let remarksDisplay = item.REMARKS || '';
                
                // Load IR Status Display
                let loadIRStatusDisplay = '';
                let loadIRBadgeClass = '';
                let loadIRActions = '';

                if (loadIRStatus.needsIR) {
                    if (loadIRStatus.isComplied) {
                        loadIRStatusDisplay = 'Complied';
                        loadIRBadgeClass = 'remarks-load-ir-complied';
                        loadIRActions = `<span class="badge badge-success">✓ Done</span>`;
                    } else {
                        loadIRStatusDisplay = 'Pending';
                        loadIRBadgeClass = 'remarks-load-ir-pending';
                        loadIRActions = `
                            <button class="btn btn-sm btn-success btn-sm-custom" onclick="showLoadIRCompliedConfirmation('${item.LINEID}')">
                                <i class="fas fa-check mr-1"></i> Mark Complied
                            </button>
                        `;
                    }
                } else {
                    loadIRStatusDisplay = '';
                    loadIRBadgeClass = '';
                    loadIRActions = '';
                }

                // Device IR Status Display
                let deviceIRStatusDisplay = '';
                let deviceIRBadgeClass = '';
                let deviceIRActions = '';

                if (deviceIRStatus.needsIR) {
                    if (deviceIRStatus.isComplied) {
                        deviceIRStatusDisplay = 'Complied';
                        deviceIRBadgeClass = 'remarks-device-ir-complied';
                        deviceIRActions = `<span class="badge badge-success">✓ Done</span>`;
                    } else {
                        deviceIRStatusDisplay = 'Pending';
                        deviceIRBadgeClass = 'remarks-device-ir-pending';
                        deviceIRActions = `
                            <button class="btn btn-sm btn-success btn-sm-custom" onclick="showDeviceIRCompliedConfirmation('${item.LINEID}')">
                                <i class="fas fa-check mr-1"></i> Mark Complied
                            </button>
                        `;
                    }
                } else {
                    deviceIRStatusDisplay = '';
                    deviceIRBadgeClass = '';
                    deviceIRActions = '';
                }

                // Add Load IR to remarks if needed
                if (loadIRStatus.needsIR && !remarksDisplay.toUpperCase().includes('LOAD IR')) {
                    remarksDisplay = remarksDisplay ? remarksDisplay + ' [LOAD IR]' : '[LOAD IR]';
                }

                // Add Device IR to remarks if needed
                if (deviceIRStatus.needsIR && !remarksDisplay.toUpperCase().includes('DEVICE IR')) {
                    remarksDisplay = remarksDisplay ? remarksDisplay + ' [DEVICE IR]' : '[DEVICE IR]';
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center">${index + 1}</td>
                    <td>${item.SITE_ID || ''}</td>
                    <td>${item.DEPARTMENT || ''}</td>
                    <td>${item.PRINCIPAL || ''}</td>
                    <td>${item.POSITION || ''}</td>
                    <td>${item.BRAND || ''}</td>
                    <td>${item.MODEL || ''}</td>
                    <td>${item.SERIAL || ''}</td>
                    <td>${item.DATE_DEPLOYED || ''}</td>
                    <td>${item.PERSON_USING || ''}</td>
                    <td>${item.NUMBER || ''}</td>
                    <td class="text-right">${Number(item.BALANCE ?? 0).toFixed(2)}</td>
                    <td>${item.LAST_LOAD_HISTORY || ''}</td>
                    <td>${item.NEXT_LOAD_SCHEDULE || ''}</td>
                    <td>${item.LOAD_TERMS || ''}</td>
                    <td>
                        ${loadIRStatusDisplay ? `<span class="status-badge ${loadIRBadgeClass}">${loadIRStatusDisplay}</span>` : ''}
                    </td>
                    <td class="ir-action-column">
                        ${loadIRActions}
                    </td>
                    <td>${item.DEVICE_STATUS || ''}</td>
                    <td>${item.REASON_CODE || ''}</td>
                    <td>
                        ${deviceIRStatusDisplay ? `<span class="status-badge ${deviceIRBadgeClass}">${deviceIRStatusDisplay}</span>` : ''}
                    </td>
                    <td class="ir-action-column">
                        ${deviceIRActions}
                    </td>
                    <td>${remarksDisplay}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Load IR Compliance
        function showLoadIRCompliedConfirmation(lineid) {
            pendingLoadIRLineId = lineid;
            $('#confirmLoadIRCompliedModal').modal('show');
        }

        function setLoadIRComplied(lineid) {
            showLoading('Marking as Load IR Complied...');
            
            fetch(`${API_URL}?action=setloadircomplied&lineid=${lineid}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('Device marked as Load IR complied successfully.', 'success');
                        loadForLoadDevices();
                    } else {
                        showNotification('Failed to update: ' + (data.message || 'Unknown error'), 'danger');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showNotification('Error: ' + err.message, 'danger');
                })
                .finally(() => {
                    $('#confirmLoadIRCompliedModal').modal('hide');
                    pendingLoadIRLineId = null;
                    hideLoading();
                });
        }

        document.getElementById('confirmLoadIRCompliedBtn')?.addEventListener('click', () => {
            if (pendingLoadIRLineId) {
                setLoadIRComplied(pendingLoadIRLineId);
            }
        });

        // Device IR Compliance
        function showDeviceIRCompliedConfirmation(lineid) {
            pendingDeviceIRLineId = lineid;
            $('#confirmDeviceIRCompliedModal').modal('show');
        }

        function setDeviceIRComplied(lineid) {
            showLoading('Marking as Device IR Complied...');
            
            fetch(`${API_URL}?action=setdeviceircomplied&lineid=${lineid}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('Device marked as Device IR complied successfully.', 'success');
                        loadForLoadDevices();
                    } else {
                        showNotification('Failed to update: ' + (data.message || 'Unknown error'), 'danger');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showNotification('Error: ' + err.message, 'danger');
                })
                .finally(() => {
                    $('#confirmDeviceIRCompliedModal').modal('hide');
                    pendingDeviceIRLineId = null;
                    hideLoading();
                });
        }

        document.getElementById('confirmDeviceIRCompliedBtn')?.addEventListener('click', () => {
            if (pendingDeviceIRLineId) {
                setDeviceIRComplied(pendingDeviceIRLineId);
            }
        });

        // Test Connection
        function testConnection() {
            showLoading('Testing connection...');
            
            fetch(`${API_URL}?action=test`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification('✅ Connection successful! Server time: ' + data.server_time, 'success');
                    } else {
                        showNotification('❌ Connection failed: ' + data.message, 'danger');
                    }
                })
                .catch(err => {
                    showNotification('❌ Connection error: ' + err.message, 'danger');
                })
                .finally(() => {
                    hideLoading();
                });
        }

        // Loading overlay
        function showLoading(message) {
            hideLoading();
            
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            overlay.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div style="margin-top: 10px; font-weight: bold;">${message || 'Loading...'}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const existing = document.querySelector('.toast-notification');
            if (existing) {
                existing.remove();
            }

            const alertDiv = document.createElement('div');
            alertDiv.className = `toast-notification alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="close" onclick="this.parentElement.remove()" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        function applyFilter() {
            if (!forLoadDevicesData.length) return;

            const filtered = forLoadDevicesData.filter(item => {
                // Check last load filter
                if (currentFilterMonths !== 0) {
                    const months = monthsAgo(item.LAST_LOAD_HISTORY);
                    if (months < currentFilterMonths) return false;
                }

                // Check Load IR filter
                const loadIRStatus = checkLoadIRStatus(item);
                if (currentRemarksFilter === 'load_ir_pending') {
                    if (!(loadIRStatus.needsIR && !loadIRStatus.isComplied)) return false;
                } else if (currentRemarksFilter === 'load_ir_complied') {
                    if (!(loadIRStatus.needsIR && loadIRStatus.isComplied)) return false;
                }

                // Check Device IR filter
                const deviceIRStatus = checkDeviceIRStatus(item);
                if (currentRemarksFilter === 'device_ir_pending') {
                    if (!(deviceIRStatus.needsIR && !deviceIRStatus.isComplied)) return false;
                } else if (currentRemarksFilter === 'device_ir_complied') {
                    if (!(deviceIRStatus.needsIR && deviceIRStatus.isComplied)) return false;
                }

                // Check Bad Device Status filter
                if (currentRemarksFilter === 'bad_device') {
                    if (!isDeviceStatusBad(item.DEVICE_STATUS)) return false;
                }

                // Site filter
                if (currentSiteFilter) {
                    if ((item.SITE_ID || '').trim() !== currentSiteFilter) return false;
                }

                // Search filter
                if (currentSearchTerm) {
                    const term = currentSearchTerm.toLowerCase().trim();
                    const text = [
                        item.SITE_ID || '',
                        item.DEPARTMENT || '',
                        item.PRINCIPAL || '',
                        item.POSITION || '',
                        item.BRAND || '',
                        item.MODEL || '',
                        item.SERIAL || '',
                        item.PERSON_USING || '',
                        item.NUMBER || '',
                        item.REMARKS || '',
                        item.DEVICE_STATUS || ''
                    ].join(' ').toLowerCase();

                    if (!text.includes(term)) return false;
                }

                return true;
            });

            renderTable(filtered);
        }

        function loadForLoadDevices() {
            const tbody = document.getElementById('forLoadTable');
            const exportBtn = document.getElementById('exportExcelBtn');

            tbody.innerHTML = `<tr><td colspan="22" class="text-center text-muted">Loading...</td></tr>`;
            exportBtn.disabled = true;

            fetch(`${API_URL}?action=forload`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    forLoadDevicesData = data || [];
                    populateSiteFilter();
                    applyFilter();
                    exportBtn.disabled = false;
                })
                .catch(err => {
                    console.error('Error loading devices:', err);
                    tbody.innerHTML = `<tr><td colspan="22" class="text-center text-danger">Failed to load devices: ${err.message}</td></tr>`;
                    showNotification('Failed to load devices: ' + err.message, 'danger');
                });
        }

        function exportToExcel() {
            if (!forLoadDevicesData || forLoadDevicesData.length === 0) {
                showNotification('No data available to export.', 'warning');
                return;
            }

            const exportData = forLoadDevicesData.map((item, index) => {
                const loadIRStatus = checkLoadIRStatus(item);
                const deviceIRStatus = checkDeviceIRStatus(item);
                let remarksExport = item.REMARKS || '';
                
                let loadIRText = loadIRStatus.needsIR ? (loadIRStatus.isComplied ? 'LOAD IR COMPLIED' : 'LOAD IR PENDING') : '';
                let deviceIRText = deviceIRStatus.needsIR ? (deviceIRStatus.isComplied ? 'DEVICE IR COMPLIED' : 'DEVICE IR PENDING') : '';

                if (loadIRStatus.needsIR && !remarksExport.toUpperCase().includes('LOAD IR')) {
                    remarksExport = remarksExport ? remarksExport + ' [LOAD IR]' : '[LOAD IR]';
                }
                if (deviceIRStatus.needsIR && !remarksExport.toUpperCase().includes('DEVICE IR')) {
                    remarksExport = remarksExport ? remarksExport + ' [DEVICE IR]' : '[DEVICE IR]';
                }

                return {
                    "#": index + 1,
                    "Site": item.SITE_ID || "",
                    "Department": item.DEPARTMENT || "",
                    "Principal": item.PRINCIPAL || "",
                    "Position": item.POSITION || "",
                    "Brand": item.BRAND || "",
                    "Model": item.MODEL || "",
                    "Serial": item.SERIAL || "",
                    "Date Deployed": item.DATE_DEPLOYED || "",
                    "User": item.PERSON_USING || "",
                    "Number": item.NUMBER || "",
                    "Balance (GB)": Number(item.BALANCE ?? 0).toFixed(2),
                    "Last Load": item.LAST_LOAD_HISTORY || "",
                    "Next Load Schedule": item.NEXT_LOAD_SCHEDULE || "",
                    "Load Term (mo.)": item.LOAD_TERMS || "",
                    "Load IR Status": loadIRText,
                    "Device Status": item.DEVICE_STATUS || "",
                    "Reason Code": item.REASON_CODE || "",
                    "Device IR Status": deviceIRText,
                    "IS_COMPLIED": item.IS_COMPLIED || "NO",
                    "DEVICE_IR_COMPLIED": item.DEVICE_IR_COMPLIED || "NO",
                    "Remarks": remarksExport
                };
            });

            const ws = XLSX.utils.json_to_sheet(exportData);
            const colWidths = [];
            Object.keys(exportData[0] || {}).forEach((key, i) => {
                let maxLen = String(key).length;
                exportData.forEach(row => {
                    const val = String(row[key] || "");
                    if (val.length > maxLen) maxLen = val.length;
                });
                colWidths[i] = { wch: Math.min(maxLen + 3, 45) };
            });
            ws['!cols'] = colWidths;

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Devices For Load");

            const today = new Date().toISOString().slice(0,10).replace(/-/g, '');
            XLSX.writeFile(wb, `Devices_For_Load_${today}.xlsx`);
            
            showNotification('Export completed successfully!', 'success');
        }

        function refreshAllLoadStatus() {
            if (!confirm("This will recalculate and update LOAD_STATUS for ALL devices based on their current balance and last load date. Continue?")) {
                return;
            }

            showLoading('Updating load status for all devices...');

            fetch(`${API_URL}?action=update_load_status_all`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.success) {
                    showNotification(`Success! Updated ${resp.updated} devices.`, 'success');
                    loadForLoadDevices();
                } else {
                    showNotification(resp.message || 'Update failed', 'danger');
                }
            })
            .catch((err) => {
                showNotification('Network error: ' + err.message, 'danger');
            })
            .finally(() => {
                hideLoading();
            });
        }

        // Event Listeners
        document.getElementById('lastLoadFilter')?.addEventListener('change', (e) => {
            currentFilterMonths = parseInt(e.target.value, 10) || 0;
            applyFilter();
        });

        document.querySelectorAll('input[name="remarksFilter"]')?.forEach(radio => {
            radio.addEventListener('change', (e) => {
                currentRemarksFilter = e.target.value;
                applyFilter();
            });
        });

        document.getElementById('siteFilter')?.addEventListener('change', (e) => {
            currentSiteFilter = e.target.value.trim();
            applyFilter();
        });

        let searchTimeout;
        document.getElementById('searchBox')?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearchTerm = e.target.value.trim();
                applyFilter();
            }, 300);
        });

        document.getElementById('exportExcelBtn')?.addEventListener('click', exportToExcel);

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadForLoadDevices);
    </script>

</body>
</html>