<?php
// ir_compliance_report.php
// IR Compliance Report
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title>IR Compliance Report</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
 
    .header-box {
        background: white;
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border: 1px solid #e9edf4;
    }
    
    .header-box h4 {
        margin: 0;
        color: #1a2332;
        font-weight: 600;
    }
    
    .header-box h4 i {
        color: #667eea;
        margin-right: 10px;
    }
    
    .header-box .badge-count {
        background: #667eea;
        color: white;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
    }
    
    .table-box {
        background: white;
        border-radius: 12px;
        border: 1px solid #e9edf4;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    
    .table-box .table-header {
        padding: 14px 20px;
        border-bottom: 1px solid #e9edf4;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafbfc;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .table-box .table-header span { font-weight: 600; font-size: 14px; color: #1a2332; }
    .table-box .table-header .count { color: #667eea; }
    
    .table-scroll {
        overflow-y: auto;
        max-height: 520px;
    }
    
    .table-box table {
        font-size: 12px;
        margin-bottom: 0;
        width: 100%;
    }
    
    .table-box table th {
        background: #f8fafc;
        color: #4a5568;
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        padding: 10px 14px;
        border-bottom: 2px solid #e9edf4;
        position: sticky;
        top: 0;
        z-index: 5;
        white-space: nowrap;
    }
    
    .table-box table td {
        padding: 8px 14px;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
    }
    
    .table-box table tbody tr:hover { background: #f7fafc; }
    
    .badge-status {
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-good { background: #c6f6d5; color: #22543d; }
    .badge-bad { background: #fed7d7; color: #9b2c2c; }
    .badge-pending { background: #fefcbf; color: #744210; }
    .badge-ir-complied { background: #c6f6d5; color: #22543d; }
    .badge-ir-pending { background: #fefcbf; color: #744210; }
    
    .attachment-link {
        display: inline-block;
        padding: 2px 10px;
        background: #edf2f7;
        border-radius: 6px;
        font-size: 10px;
        color: #4a5568;
        cursor: pointer;
        text-decoration: none;
    }
    
    .attachment-link:hover {
        background: #667eea;
        color: white;
        text-decoration: none;
    }
    
    .export-area {
        padding: 14px 20px;
        border-top: 1px solid #e9edf4;
        text-align: right;
        background: #fafbfc;
    }
    
    .btn-export {
        background: #48bb78;
        border: none;
        color: white;
        border-radius: 8px;
        padding: 8px 24px;
        font-weight: 600;
        font-size: 13px;
    }
    
    .btn-export:hover { background: #38a169; color: white; }
    
    .loading-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255,255,255,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #a0aec0;
    }
    
    .empty-state i { font-size: 40px; display: block; margin-bottom: 12px; }
    .empty-state h6 { color: #4a5568; }
    
    /* Table search wrapper */
    .table-search-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 20px;
        background: #fafbfc;
        border-bottom: 1px solid #e9edf4;
        flex-wrap: wrap;
    }
    
    .table-search-wrapper label {
        margin: 0;
        font-weight: 500;
        color: #4a5568;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .table-search-wrapper input {
        border-radius: 8px;
        border: 1.5px solid #e2e8f0;
        font-size: 13px;
        padding: 6px 15px;
        height: 38px;
        background: white;
        flex: 1;
        min-width: 200px;
        max-width: 400px;
    }
    
    .table-search-wrapper input:focus {
        border-color: #667eea;
        outline: none;
        background: white;
    }
    
    .table-search-wrapper .search-results {
        font-size: 12px;
        color: #6b7a8f;
    }
    
    @media (max-width: 768px) {
        .table-search-wrapper {
            flex-direction: column;
            align-items: stretch;
        }
        .table-search-wrapper input {
            max-width: 100%;
        }
        .table-search-wrapper .btn {
            align-self: flex-start;
        }
    }
</style>
</head>
<body>

<!-- HEADER -->
<div class="header-box">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h4><i class="fas fa-clipboard-check"></i> IR Compliance Report</h4>
        <div>
            <span class="badge-count" id="recordCount">0 Records</span>
            <button class="btn btn-sm btn-outline-secondary ml-2" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="table-box">
    <div class="table-header">
        <span><i class="fas fa-list text-primary mr-2"></i> Device List</span>
        <span class="count" id="totalCount">Total: 0</span>
    </div>
    
    <!-- Table Search ONLY -->
    <div class="table-search-wrapper">
        <label>
            <i class="fas fa-search"></i> Filter:
            <input type="text" id="tableSearchInput" placeholder="Type to filter table..." onkeyup="filterTable()" />
        </label>
        <span class="search-results" id="searchResults"></span>
        <button class="btn btn-sm btn-outline-secondary ml-auto" onclick="clearTableSearch()">
            <i class="fas fa-times"></i> Clear
        </button>
    </div>
    
    <div class="table-scroll">
        <table id="devicesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>LINEID</th>
                    <th>NUMBER</th>
                    <th>USER</th>
                    <th>SITE</th>
                    <th>DEPARTMENT</th>
                    <th>BRAND</th>
                    <th>MODEL</th>
                    <th>SERIAL</th>
                    <th>IMEI</th>
                    <th>STATUS</th>
                    <th>IR COMPLIED</th>
                    <th>ATTACHMENTS</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    
    <div id="tableError" class="text-center text-danger py-2" style="display:none;"></div>
    
    <div class="export-area">
        <button class="btn-export" onclick="exportToExcel()">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button>
    </div>
</div>

<!-- LOADING -->
<div class="loading-overlay" id="loading">
    <div style="text-align:center;">
        <div class="spinner-border text-primary" style="width:40px;height:40px;"></div>
        <div style="margin-top:12px;color:#4a5568;">Loading...</div>
    </div>
</div>

<!-- ATTACHMENT MODAL -->
<div class="modal fade" id="attachmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="fas fa-paperclip mr-2"></i> Attachment</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="attachImage" style="max-width:100%;max-height:400px;display:none;border-radius:8px;">
                <div id="attachFallback" style="display:none;">
                    <i class="fas fa-file fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Preview not available</p>
                    <a href="#" target="_blank" class="btn btn-primary btn-sm" id="attachOpenLink">Open File</a>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                <a href="#" class="btn btn-success btn-sm" id="attachDownloadLink"><i class="fas fa-download"></i> Download</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let loadedDevices = [];
const API_BASE = '/LM/datafetcher/ir_compliance_api.php';
const BASE_URL = window.location.origin;

function showLoader() { document.getElementById('loading').style.display = 'flex'; }
function hideLoader() { document.getElementById('loading').style.display = 'none'; }

function getFullAttachmentUrl(path) {
    if (!path) return null;
    
    let cleanPath = decodeURIComponent(path);
    cleanPath = cleanPath.replace(/\.\./g, '');
    cleanPath = cleanPath.replace(/\/\//g, '/');
    
    if (cleanPath.startsWith('http://') || cleanPath.startsWith('https://')) {
        return cleanPath;
    }
    
    if (cleanPath.startsWith('/uploads/')) {
        return BASE_URL + cleanPath;
    }
    
    if (cleanPath.startsWith('uploads/')) {
        return BASE_URL + '/' + cleanPath;
    }
    
    const filename = cleanPath.split('/').pop();
    return BASE_URL + '/uploads/ir_compliance/' + filename;
}

function loadDevices() {
    const tbody = document.querySelector('#devicesTable tbody');
    tbody.innerHTML = '';
    document.getElementById('tableError').style.display = 'none';
    document.getElementById('searchResults').textContent = '';
    showLoader();

    fetch(API_BASE + '?action=get_not_ok_devices')
        .then(r => r.json())
        .then(data => {
            loadedDevices = data || [];
            
            document.getElementById('recordCount').textContent = loadedDevices.length + ' Records';
            document.getElementById('totalCount').textContent = 'Total: ' + loadedDevices.length;

            if (!loadedDevices.length) {
                tbody.innerHTML = `<tr><td colspan="13"><div class="empty-state"><i class="fas fa-inbox"></i><h6>No devices found</h6></div></td></tr>`;
                hideLoader();
                return;
            }

            loadedDevices.forEach((d, i) => {
                let sClass = 'badge-pending';
                if (d.DEVICE_STATUS === 'Good Condition') sClass = 'badge-good';
                else if (d.DEVICE_STATUS !== 'Good Condition') sClass = 'badge-bad';

                let irClass = d.DEVICE_IR_COMPLIED ? 'badge-ir-complied' : 'badge-ir-pending';
                let irText = d.DEVICE_IR_COMPLIED ? 'Complied' : 'Pending';

                let att = '<span class="text-muted" style="font-size:10px;">None</span>';
                if (d.ATTACHMENT1 || d.ATTACHMENT2) {
                    att = '';
                    if (d.ATTACHMENT1) {
                        const url1 = getFullAttachmentUrl(d.ATTACHMENT1);
                        att += `<a href="${url1}" target="_blank" class="attachment-link" data-attach="${encodeURIComponent(d.ATTACHMENT1)}"><i class="fas fa-paperclip"></i> 1</a> `;
                    }
                    if (d.ATTACHMENT2) {
                        const url2 = getFullAttachmentUrl(d.ATTACHMENT2);
                        att += `<a href="${url2}" target="_blank" class="attachment-link" data-attach="${encodeURIComponent(d.ATTACHMENT2)}"><i class="fas fa-paperclip"></i> 2</a>`;
                    }
                }

                tbody.innerHTML += `
                    <tr>
                        <td>${i+1}</td>
                        <td><b>${d.LINEID || ''}</b></td>
                        <td><b>${d.NUMBER || '-'}</b></td>
                        <td>${d.PERSON_USING || '-'}</td>
                        <td>${d.SITE_ID || '-'}</td>
                        <td>${d.DEPARTMENT || '-'}</td>
                        <td>${d.BRAND || '-'}</td>
                        <td>${d.MODEL || '-'}</td>
                        <td style="font-size:10px;">${d.SERIAL || '-'}</td>
                        <td style="font-size:10px;">${d.IMEI || '-'}</td>
                        <td><span class="badge-status ${sClass}">${d.DEVICE_STATUS || 'Unknown'}</span></td>
                        <td><span class="badge-status ${irClass}">${irText}</span></td>
                        <td>${att}</td>
                    </tr>
                `;
            });
            
            document.getElementById('tableSearchInput').value = '';
            document.getElementById('searchResults').textContent = 'Showing ' + loadedDevices.length + ' entries';
            
            hideLoader();
        })
        .catch(e => {
            console.error('Error:', e);
            document.getElementById('tableError').style.display = 'block';
            document.getElementById('tableError').textContent = 'Error loading data: ' + e.message;
            hideLoader();
        });
}

function filterTable() {
    const input = document.getElementById('tableSearchInput');
    const filter = input.value.toLowerCase().trim();
    const table = document.getElementById('devicesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    let visibleCount = 0;
    
    if (filter === '') {
        for (let i = 0; i < rows.length; i++) {
            rows[i].style.display = '';
            visibleCount++;
        }
        document.getElementById('searchResults').textContent = 'Showing ' + visibleCount + ' entries';
        return;
    }
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        if (cells.length === 0 || cells[0].colSpan > 1) {
            row.style.display = '';
            continue;
        }
        
        for (let j = 1; j < cells.length; j++) {
            const cell = cells[j];
            const text = cell.textContent || cell.innerText;
            if (text.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        if (found) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    }
    
    document.getElementById('searchResults').textContent = 'Showing ' + visibleCount + ' of ' + loadedDevices.length + ' entries';
}

function clearTableSearch() {
    document.getElementById('tableSearchInput').value = '';
    filterTable();
}

function exportToExcel() {
    if (typeof XLSX === 'undefined') { alert('Excel library not loaded'); return; }
    if (!loadedDevices.length) { alert('No data to export'); return; }

    const data = loadedDevices.map(d => ({
        'LINEID': d.LINEID || '',
        'Number': d.NUMBER || '',
        'User': d.PERSON_USING || '',
        'Site': d.SITE_ID || '',
        'Department': d.DEPARTMENT || '',
        'Brand': d.BRAND || '',
        'Model': d.MODEL || '',
        'Serial': d.SERIAL || '',
        'IMEI': d.IMEI || '',
        'Status': d.DEVICE_STATUS || '',
        'IR Complied': d.DEVICE_IR_COMPLIED ? 'YES' : 'NO',
        'Date Complied': d.DATE_COMPLIED || '',
        'Remarks': d.REMARKS || '',
        'Attachment 1': d.ATTACHMENT1 ? getFullAttachmentUrl(d.ATTACHMENT1) : '',
        'Attachment 2': d.ATTACHMENT2 ? getFullAttachmentUrl(d.ATTACHMENT2) : ''
    }));

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'IR Compliance');
    XLSX.writeFile(wb, 'IR_Compliance_Report_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.xlsx');
}

// Attachment view - open in new tab
document.addEventListener('click', function(e) {
    const link = e.target.closest('.attachment-link');
    if (link) {
        e.preventDefault();
        const path = link.dataset.attach;
        if (!path) return;
        const url = getFullAttachmentUrl(path);
        window.open(url, '_blank');
    }
});

// Real-time search on table
document.getElementById('tableSearchInput').addEventListener('keyup', filterTable);

document.addEventListener('DOMContentLoaded', function() {
    loadDevices();
});
</script>
</body>
</html>