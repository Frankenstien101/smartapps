<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devices For Load</title>

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
        .remarks-ir {
            background-color: #fd7e14; 
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

        /* Modern header styling */
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
    </style>
</head>
<body>

    <h2>FOR LOAD DEVICES</h2>

    <div class="container-fluid mt-3">

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
                <label class="mb-0" style="font-size: 12px;">REMARKS:</label>
            </div>
            <div class="col-auto">
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksAll" value="all" checked>
                    <label class="form-check-label" for="remarksAll" style="font-size: 12px;">All</label>
                </div>
                <div class="form-check form-check-inline" style="margin-bottom: 0;">
                    <input class="form-check-input" type="radio" name="remarksFilter" id="remarksIR" value="ir">
                    <label class="form-check-label" for="remarksIR" style="font-size: 12px;">IR only</label>
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
                        <div class="mb-0 ml-2" style="width: 450px ;">
                         <button type="button" class="btn btn-warning" onclick="refreshAllLoadStatus()">
                           <i class="fas fa-sync-alt me-2"></i> Refresh All Load Status
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
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="forLoadTable">
                            <tr>
                                <td colspan="17" class="text-center text-muted">Loading...</td>
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

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmCompliedModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to mark this device as <strong>COMPLIED</strong>?<br>
                    <small class="text-muted">This action cannot be easily undone.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmCompliedBtn">Yes, Mark as Complied</button>
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

        // For complied confirmation modal
        let pendingLineId = null;

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

        function renderTable(filteredData) {
            const tbody = document.getElementById('forLoadTable');
            tbody.innerHTML = '';

            if (filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="17" class="text-center text-muted">No matching devices</td></tr>`;
                return;
            }

            filteredData.forEach((item, index) => {
                const statusClass = item.LOAD_STATUS === 'FOR LOAD' ? 'status-forload' : 'status-ok';

                // ── Remarks: show saved REMARKS column with IR badge if balance < 5 AND today < next load schedule ──
                let remarks = item.REMARKS || '';
                let remarksDisplay = remarks;
                let remarksClass = '';

                const balance = Number(item.BALANCE ?? 0);
                const nextScheduleStr = item.NEXT_LOAD_SCHEDULE ?? '';

                let isTodayBeforeNext = false;

                if (nextScheduleStr && nextScheduleStr.trim() !== '') {
                    try {
                        const [year, month, day] = nextScheduleStr.trim().split('-').map(Number);
                        const nextDate = new Date(year, month - 1, day);

                        const today = new Date();
                        today.setHours(0, 0, 0, 0); // compare only dates

                        if (!isNaN(nextDate.getTime())) {
                            isTodayBeforeNext = today < nextDate;
                        }
                    } catch (e) {
                        isTodayBeforeNext = false;
                    }
                }

                if (balance < 5 && isTodayBeforeNext) {
                    if (!remarksDisplay.toUpperCase().includes('IR')) {
                        remarksDisplay = remarksDisplay ? remarksDisplay + ' [IR]' : 'IR';
                    }
                    remarksClass = 'remarks-ir';
                }
                // ────────────────────────────────────────────────────────────────

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
                    <td class="text-right">${balance.toFixed(2)}</td>
                    <td>${item.LAST_LOAD_HISTORY ?? ''}</td>
                    <td>${item.NEXT_LOAD_SCHEDULE ?? ''}</td>
                    <td>${item.LOAD_TERMS ?? ''}</td>
                    <td><span class="${remarksClass}">${remarksDisplay}</span></td>
                    <td>
                        ${remarksDisplay === "IR" && item.IS_COMPLIED !== "YES" ? 
                            `<button class="btn btn-sm btn-success" onclick="showCompliedConfirmation('${item.LINEID}')">
                                <i class="fas fa-arrow-right mr-1"></i> Complied
                            </button>` 
                          : (remarksDisplay === "IR" ? 
                                '<button class="btn btn-sm btn-secondary disabled">Already Complied</button>' 
                              : '')
                        }
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function showCompliedConfirmation(lineid) {
            pendingLineId = lineid;
            $('#confirmCompliedModal').modal('show');
        }

        function setascomplied(lineid) {
            fetch('/LM/datafetcher/loadcheckingdata.php?action=setascomplied&lineid=' + lineid)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Device marked as complied successfully.');
                        loadForLoadDevices();
                    } else {
                        alert('Failed to update: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error communicating with server.');
                })
                .finally(() => {
                    $('#confirmCompliedModal').modal('hide');
                    pendingLineId = null;
                });
        }

        // Confirm button in modal
        document.getElementById('confirmCompliedBtn')?.addEventListener('click', () => {
            if (pendingLineId) {
                setascomplied(pendingLineId);
            }
        });

        function applyFilter() {
            if (!forLoadDevicesData.length) return;

            const filtered = forLoadDevicesData.filter(item => {
                // Check last load filter
                if (currentFilterMonths !== 0) {
                    const months = monthsAgo(item.LAST_LOAD_HISTORY);
                    if (months < currentFilterMonths) return false;
                }

                // Check remarks filter
                if (currentRemarksFilter === 'ir') {
                    const balance = Number(item.BALANCE ?? 0);
                    const nextScheduleStr = item.NEXT_LOAD_SCHEDULE ?? '';
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

                    if (!(balance < 5 && isTodayBeforeNext)) return false;
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
                        item.REMARKS || ''
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

            tbody.innerHTML = `<tr><td colspan="17" class="text-center text-muted">Loading...</td></tr>`;
            exportBtn.disabled = true;

            fetch('/LM/datafetcher/loadcheckingdata.php?action=forload')
                .then(res => res.json())
                .then(data => {
                    forLoadDevicesData = data || [];
                    populateSiteFilter();
                    applyFilter();
                    exportBtn.disabled = false;
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = `<tr><td colspan="17" class="text-center text-danger">Failed to load devices</td></tr>`;
                });
        }

        function requestLoad(id, personUsing, number, balance, lastLoad, siteId, btn) {
            btn.disabled = true;

            fetch(`/LM/datafetcher/load_request.php?action=loadrequest`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    device_id: id, 
                    person_using: personUsing, 
                    number: number, 
                    balance: balance, 
                    last_load: lastLoad,
                    SITE_ID: siteId
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    alert(`Load status updated: ${res.load_status}`);
                    loadForLoadDevices();
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Server error');
                btn.disabled = false;
            });
        }

        function exportToExcel() {
    if (!forLoadDevicesData || forLoadDevicesData.length === 0) {
        alert("No data available to export.");
        return;
    }

    const exportData = forLoadDevicesData.map((item, index) => {
        let remarksExport = item.REMARKS || '';
        let statusExport = ''; // Default to empty

        const balance = Number(item.BALANCE ?? 0);
        const nextScheduleStr = item.NEXT_LOAD_SCHEDULE ?? '';

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

        // Check if device needs IR (balance < 5 and today before next schedule)
        const needsIR = balance < 5 && isTodayBeforeNext;

        if (needsIR) {
            if (!remarksExport.toUpperCase().includes('IR')) {
                remarksExport = remarksExport ? remarksExport + ' [IR]' : 'IR';
            }
        }

        // Check if device is complied
        const isComplied = 
            item.IS_COMPLIED === "YES" || 
            item.IS_COMPLIED === "1" || 
            item.IS_COMPLIED === 1 ||
            item.DEVICE_IR_COMPLIED === "YES" ||
            item.DEVICE_IR_COMPLIED === "1" ||
            item.DEVICE_IR_COMPLIED === 1 ||
            item.COMPLIED === "YES" ||
            item.COMPLIED === "1" ||
            item.COMPLIED === 1 ||
            (item.LOAD_STATUS && item.LOAD_STATUS.toUpperCase() === "COMPLIED") ||
            (item.REMARKS && item.REMARKS.toUpperCase().includes("COMPLIED"));

        // 🔥 ONLY set status if complied, otherwise leave empty
        if (isComplied) {
            statusExport = "Complied";
        }
        // Otherwise statusExport stays empty (null)

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
            "Balance (GB)": balance.toFixed(2),
            "Last Load": item.LAST_LOAD_HISTORY || "",
            "Next Load Schedule": item.NEXT_LOAD_SCHEDULE || "",
            "Load Term (mo.)": item.LOAD_TERMS || "",
            "Remarks": remarksExport,
            "Status": statusExport  // Only "Complied" or empty
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
}

        function refreshAllLoadStatus() {
            if (!confirm("This will recalculate and update LOAD_STATUS for ALL devices based on their current balance and last load date. Continue?")) {
                return;
            }

            fetch('/LM/datafetcher/loadcheckingdata.php?action=update_load_status_all', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                  //  alert(`Success! Updated ${resp.updated} devices.`);
                    location.reload();
                } else {
                    alert(resp.message || 'Update failed');
                }
            })
            .catch(() => {
                alert('Network error. Please try again.');
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

        document.addEventListener('DOMContentLoaded', loadForLoadDevices);
    </script>

</body>
</html>