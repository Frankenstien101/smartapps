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
            background-color: #fd7e14; /* orange for attention */
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
            
            <!-- Modern Table Header with Site + Search -->
            <div class="modern-table-header">
                <div class="filter-group">
                    <div class="d-flex align-items-center">
                        <span class="filter-label mr-2">Site:</span>
                        <select id="siteFilter" class="form-control form-control-sm site-select">
                            <option value="">All Sites</option>
                        </select>
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
                                <th>Is Complied</th>
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

    <!-- Purchase Load Modal (copied from your other file) -->
    <div class="modal fade" id="purchaseLoadModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modalTitle">Purchase Load</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="purchaseForm">
                        <input type="hidden" id="modal_device_id">
                        <input type="hidden" id="modal_site_id">

                        <div class="form-group mb-2">
                            <label class="small mb-1">Mobile Number</label>
                            <input type="text" class="form-control form-control-sm" id="modal_number" readonly>
                        </div>

                        <div class="form-group mb-2">
                            <label class="small mb-1">Current Balance (GB)</label>
                            <input type="text" class="form-control form-control-sm" id="modal_balance" readonly>
                        </div>

                        <div class="form-group mb-2">
                            <label class="small mb-1">Amount Purchased (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" class="form-control form-control-sm" id="amount" required>
                        </div>

                        <div class="form-group mb-2">
                            <label class="small mb-1">Data Added <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" class="form-control form-control-sm" id="dataadded" required>
                        </div>

                        <div class="form-group mb-2">
                            <label class="small mb-1">Reference / Transaction ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="reference" required placeholder="e.g. GCASH-123456789">
                        </div>

                        <div class="form-group mb-1">
                            <label class="small mb-1">Date / Time</label>
                            <input type="datetime-local" class="form-control form-control-sm" id="load_date" value="">
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-md" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-md" id="btnConfirmPurchase">Confirm Purchase</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let forLoadDevicesData = [];
        let currentFilterMonths = 0;
        let currentRemarksFilter = 'all';
        let currentSiteFilter = '';
        let currentSearchTerm = '';
        let currentRowData = null; // For purchase modal

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

                // Remarks + IR logic
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
                        today.setHours(0, 0, 0, 0);

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
                                <td>${item.IS_COMPLIED ?? ''}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="openPurchaseModal(
                                        '${item.LINEID}',
                                        '${item.PERSON_USING ?? ''}',
                                        '${item.NUMBER ?? ''}',
                                        '${balance}',
                                        '${item.SITE_ID ?? ''}',
                                        this
                                    )">
                                        <i class="fas fa-shopping-cart mr-1"></i>Purchase
                                    </button>
                                </td>
                            `;
                tbody.appendChild(tr);
            });
        }

        // ── Purchase Modal Functions (copied/adapted from your other file) ────────────────────────────────
        function openPurchaseModal(deviceId, user, number, balance, siteId, btn) {
            currentRowData = { deviceId, user, number, balance, siteId };

            document.getElementById('modal_device_id').value = deviceId;
            document.getElementById('modal_site_id').value    = siteId;
            document.getElementById('modal_number').value     = number;
            document.getElementById('modal_balance').value    = balance;

            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('load_date').value = now.toISOString().slice(0,16);

            $('#purchaseLoadModal').modal('show');
        }

        document.getElementById('btnConfirmPurchase')?.addEventListener('click', function() {
            const amount    = document.getElementById('amount').value.trim();
            const reference = document.getElementById('reference').value.trim();
            const loadDate  = document.getElementById('load_date').value;
            const dataadded = document.getElementById('dataadded').value;

            if (!amount || parseFloat(amount) <= 0) {
                alert('Please enter a valid purchase amount.');
                return;
            }
            if (!reference) {
                alert('Please enter the reference / transaction ID.');
                return;
            }
            if (!dataadded) {
                alert('Please enter the data added.');
                return;
            }

            const payload = {
                action: 'purchase_load',
                site_id:   currentRowData.siteId,
                device_id: currentRowData.deviceId,
                user: currentRowData.user,
                number: currentRowData.number,
                amount:    parseFloat(amount),
                reference: reference,
                dataadded: dataadded,
                load_date: loadDate || null
            };

            this.disabled = true;
            this.innerText = "Saving...";

            fetch('/LM/datafetcher/loadpurchaseddata.php?action=addtopurchased', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    $('#purchaseLoadModal').modal('hide');
                    loadForLoadDevices(); 
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Server connection error');
            })
            .finally(() => {
                this.disabled = false;
                this.innerText = "Confirm Purchase";
            });
        });
        // ────────────────────────────────────────────────────────────────────────────────────────────────

        function applyFilter() {
            if (!forLoadDevicesData.length) return;

            const filtered = forLoadDevicesData.filter(item => {
                if (currentFilterMonths !== 0) {
                    const months = monthsAgo(item.LAST_LOAD_HISTORY);
                    if (months < currentFilterMonths) return false;
                }

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

                if (currentSiteFilter) {
                    if ((item.SITE_ID || '').trim() !== currentSiteFilter) return false;
                }

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

        function exportToExcel() {
            if (!forLoadDevicesData || forLoadDevicesData.length === 0) {
                alert("No data available to export.");
                return;
            }

            const exportData = forLoadDevicesData.map((item, index) => {
                let remarksExport = item.REMARKS || '';

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

                if (balance < 5 && isTodayBeforeNext) {
                    if (!remarksExport.toUpperCase().includes('IR')) {
                        remarksExport = remarksExport ? remarksExport + ' [IR]' : 'IR';
                    }
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
                    "Balance (GB)": balance.toFixed(2),
                    "Last Load": item.LAST_LOAD_HISTORY || "",
                    "Next Load Schedule": item.NEXT_LOAD_SCHEDULE || "",
                    "Load Term (mo.)": item.LOAD_TERMS || "",
                    "Remarks": remarksExport,
                    "Status": item.LOAD_STATUS || ""
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