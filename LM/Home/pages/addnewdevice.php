<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Device Management — Instant Updates</title>

<!-- Bootstrap 4 & Font Awesome -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
  html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; background: #f4f6f9; }
  .full-screen-container { height: 88vh; display: flex; flex-direction: column; }
  .header { flex-shrink: 0; padding: 1rem 1.5rem; background: white; border-bottom: 1px solid #dee2e6; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
  .content-area { flex: 1; overflow-y: auto; padding: 1.5rem; }
  .card { border-radius: 10px; transition: all 0.2s; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
  .card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
  label { font-size: 12px; font-weight: 600; margin-bottom: 4px; display: block; }
  .status-ok { background-color: #d4edda; color: #155724; font-weight: 600; }
  .status-load { background-color: #f8d7da; color: #721c24; font-weight: bold; }
  .low-balance { color: #e67e22; font-weight: bold; }
  .card-body { padding: 1.25rem; }
  .card-title { font-size: 1.1rem; margin-bottom: 0.75rem; font-weight: 600; }
  .card-text { font-size: 0.85rem; margin-bottom: 0.4rem; color: #495057; }
  .badge { font-size: 0.75rem; padding: 0.4em 0.8em; }
  #loading, #noDevices, #errorMessage { min-height: 50vh; display: flex; align-items: center; justify-content: center; flex-direction: column; }
  .edit-icon { cursor: pointer; color: #6c757d; transition: 0.2s; margin-left: 8px; }
  .edit-icon:hover { color: #007bff; }
  .filter-section { background: white; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
  .site-badge { cursor: pointer; transition: all 0.2s; }
  .site-badge:hover { transform: scale(1.05); }
  .site-badge.active { background-color: #007bff !important; color: white !important; }
  .device-detail-row { display: flex; justify-content: space-between; font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem; }
  .detail-label { font-weight: 600; }
  .card-footer-actions { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e9ecef; display: flex; gap: 0.5rem; }
  .btn-sm-custom { font-size: 0.7rem; padding: 0.25rem 0.5rem; }
  .toast-notify { position: fixed; bottom: 20px; right: 20px; z-index: 9999; min-width: 260px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
  .card-updating { opacity: 0.6; transition: 0.1s; pointer-events: none; }
  .header-actions { display: flex; gap: 0.5rem; align-items: center; }
</style>
</head>
<body>

<div class="full-screen-container">
  <div class="header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fas fa-tablet-alt mr-2 text-primary"></i> Device Management
      </h4>
      <div class="header-actions">
        <button class="btn btn-info btn-sm" id="exportBtn">
          <i class="fas fa-file-export mr-1"></i> Export
        </button>
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addDeviceModal">
          <i class="fas fa-plus mr-1"></i> Add Device
        </button>
      </div>
    </div>
  </div>

  <div class="content-area container-fluid">
    <!-- Filter Section -->
    <div class="filter-section">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="mb-0"><i class="fas fa-filter mr-1"></i> Filter by Site:</label>
        <button class="btn btn-link btn-sm" id="clearFilterBtn">Clear Filter</button>
      </div>
      <div id="siteFilterContainer" class="d-flex flex-wrap gap-2" style="gap: 0.5rem;"></div>
      <div class="mt-2 text-muted small" id="filterStats"></div>
    </div>

    <div id="deviceList" class="row"></div>
    <div id="loading" class="d-none my-5">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
      <p class="mt-3 text-muted">Loading devices...</p>
    </div>
    <div id="noDevices" class="alert alert-info text-center my-5 d-none">
      <i class="fas fa-info-circle fa-2x mb-3 d-block text-info"></i>
      No devices found yet.<br>Add your first device using the button above.
    </div>
    <div id="errorMessage" class="alert alert-danger text-center my-5 d-none"></div>
  </div>
</div>

<!-- Add Device Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title"><i class="fas fa-plus-circle mr-2"></i> Add New Device</h6>
        <button type="button" class="close text-white" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body">
        <form id="addDeviceForm">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Site <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="SITE_ID" required>
            </div>
            <div class="form-group col-md-4">
              <label>Department</label>
              <input type="text" class="form-control form-control-sm" id="DEPARTMENT">
            </div>
            <div class="form-group col-md-4">
              <label>Principal</label>
              <input type="text" class="form-control form-control-sm" id="PRINCIPAL">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>Position</label><input type="text" class="form-control form-control-sm" id="POSITION"></div>
            <div class="form-group col-md-4"><label>Brand</label><input type="text" class="form-control form-control-sm" id="BRAND"></div>
            <div class="form-group col-md-4"><label>Model</label><input type="text" class="form-control form-control-sm" id="MODEL"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>IMEI</label><input type="text" class="form-control form-control-sm" id="IMEI"></div>
            <div class="form-group col-md-4"><label>Serial</label><input type="text" class="form-control form-control-sm" id="SERIAL"></div>
            <div class="form-group col-md-4"><label>Date Deployed <span class="text-danger">*</span></label><input type="date" class="form-control form-control-sm" id="DATE_DEPLOYED" required></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>User</label><input type="text" class="form-control form-control-sm" id="PERSON_USING"></div>
            <div class="form-group col-md-4"><label>Mobile Number</label><input type="text" class="form-control form-control-sm" id="NUMBER"></div>
            <div class="form-group col-md-2"><label>Initial Balance (GB) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control form-control-sm" id="BALANCE" min="0" required></div>
            <div class="form-group col-md-2"><label>Months to Load <span class="text-danger">*</span></label><input type="number" class="form-control form-control-sm" id="MONTHTOLOAD" min="1" required></div>
          </div>
          <div class="form-group">
            <label>Remarks</label>
            <textarea class="form-control form-control-sm" id="REMARKS" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" id="btnSaveDevice">
          <i class="fas fa-save mr-1"></i> Save Device
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Device Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title"><i class="fas fa-edit mr-2"></i> Edit Device Details</h6>
        <button type="button" class="close text-white" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body">
        <form id="editDeviceForm">
          <input type="hidden" id="edit_LINEID">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Site <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="edit_SITE_ID" required>
            </div>
            <div class="form-group col-md-4">
              <label>Department</label>
              <input type="text" class="form-control form-control-sm" id="edit_DEPARTMENT">
            </div>
            <div class="form-group col-md-4">
              <label>Principal</label>
              <input type="text" class="form-control form-control-sm" id="edit_PRINCIPAL">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>Position</label><input type="text" class="form-control form-control-sm" id="edit_POSITION"></div>
            <div class="form-group col-md-4"><label>Brand</label><input type="text" class="form-control form-control-sm" id="edit_BRAND"></div>
            <div class="form-group col-md-4"><label>Model</label><input type="text" class="form-control form-control-sm" id="edit_MODEL"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>IMEI</label><input type="text" class="form-control form-control-sm" id="edit_IMEI"></div>
            <div class="form-group col-md-4"><label>Serial</label><input type="text" class="form-control form-control-sm" id="edit_SERIAL"></div>
            <div class="form-group col-md-4"><label>Date Deployed <span class="text-danger">*</span></label><input type="date" class="form-control form-control-sm" id="edit_DATE_DEPLOYED" required></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>User</label><input type="text" class="form-control form-control-sm" id="edit_PERSON_USING"></div>
            <div class="form-group col-md-4"><label>Mobile Number</label><input type="text" class="form-control form-control-sm" id="edit_NUMBER"></div>
            <div class="form-group col-md-2"><label>Balance (GB)</label><input type="number" step="0.01" class="form-control form-control-sm" id="edit_BALANCE" min="0"></div>
            <div class="form-group col-md-2"><label>Load Terms (months)</label><input type="number" class="form-control form-control-sm" id="edit_LOAD_TERMS" min="1"></div>
            <div class="form-group col-md-2"><label>Data Balance Min (GB)</label><input type="number" step="0.01" class="form-control form-control-sm" id="edit_DATA_BALANCE_MIN" min="0"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Status</label>
              <select class="form-control form-control-sm" id="edit_STATUS">
                <option value="ACTIVE">ACTIVE</option>
                <option value="INACTIVE">INACTIVE</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Remarks</label>
            <textarea class="form-control form-control-sm" id="edit_REMARKS" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" id="btnUpdateDevice">
          <i class="fas fa-save mr-1"></i> Update Device
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let allDevices = [];
let currentSiteFilter = '';

// Helper: show toast notification
function showToast(message, type = 'success') {
  const toastDiv = document.createElement('div');
  toastDiv.className = `toast-notify alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
  toastDiv.role = 'alert';
  toastDiv.innerHTML = `
    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} mr-2"></i> ${message}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  `;
  document.body.appendChild(toastDiv);
  setTimeout(() => { if(toastDiv) toastDiv.remove(); }, 3000);
}

// Export function - includes status
function exportDevices() {
  if (allDevices.length === 0) {
    showToast('No devices to export', 'danger');
    return;
  }

  // Get devices to export (respect filter)
  let devicesToExport = allDevices;
  if (currentSiteFilter) {
    devicesToExport = allDevices.filter(d => d.SITE_ID === currentSiteFilter);
  }

  if (devicesToExport.length === 0) {
    showToast('No devices match the current filter', 'danger');
    return;
  }

  // Prepare CSV data
  const headers = [
    'LINEID', 'Site', 'Department', 'Principal', 'Position', 'Brand', 'Model',
    'IMEI', 'Serial', 'Date Deployed', 'User', 'Mobile Number', 'Balance (GB)',
    'Load Terms', 'Data Balance Min', 'Status', 'Remarks', 'Last Load History'
  ];

  const rows = devicesToExport.map(device => {
    const status = (device.STATUS == 1 || device.STATUS === 'ACTIVE' || device.STATUS === 'IN USE') ? 'ACTIVE' : 'INACTIVE';
    return [
      device.LINEID || '',
      device.SITE_ID || '',
      device.DEPARTMENT || '',
      device.PRINCIPAL || '',
      device.POSITION || '',
      device.BRAND || '',
      device.MODEL || '',
      device.IMEI || '',
      device.SERIAL || '',
      device.DATE_DEPLOYED || '',
      device.PERSON_USING || '',
      device.NUMBER || '',
      Number(device.BALANCE || 0).toFixed(2),
      device.LOAD_TERMS || '',
      device.DATA_BALANCE_MIN || '',
      status,
      device.REMARKS || '',
      device.LAST_LOAD_HISTORY || 'Never'
    ];
  });

  // Create CSV content
  const csvContent = [
    headers.join(','),
    ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
  ].join('\n');

  // Create download
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  const fileName = `device_export_${new Date().toISOString().split('T')[0]}${currentSiteFilter ? '_' + currentSiteFilter : ''}.csv`;
  link.setAttribute('download', fileName);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);

  showToast(`Exported ${devicesToExport.length} devices successfully!`, 'success');
}

// Render single device card (returns DOM element)
function createDeviceCard(device) {
  const balance = Number(device.BALANCE || 0);
  const forLoad = balance < 5 || !device.LAST_LOAD_HISTORY;
  const loadStatusClass = forLoad ? 'status-load' : 'status-ok';
  const loadStatusText = forLoad ? 'FOR LOAD' : 'OK';
  
  const isActive = (device.STATUS == 1 || device.STATUS === 'ACTIVE' || device.STATUS === 'IN USE');
  const deviceStatusClass = isActive ? 'badge-success' : 'badge-danger';
  const deviceStatusText = isActive ? 'ACTIVE' : 'INACTIVE';
  
  const imeiDisplay = device.IMEI ? device.IMEI.substring(0, 15) + (device.IMEI.length > 15 ? '...' : '') : 'N/A';
  const serialDisplay = device.SERIAL ? device.SERIAL.substring(0, 12) + (device.SERIAL.length > 12 ? '...' : '') : 'N/A';
  
  const cardDiv = document.createElement('div');
  cardDiv.className = 'col-md-6 col-lg-4 col-xl-3 mb-4 device-card-item';
  cardDiv.setAttribute('data-lineid', device.LINEID);
  cardDiv.setAttribute('data-site', device.SITE_ID || '');
  
  cardDiv.innerHTML = `
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="badge ${deviceStatusClass} status-badge">${deviceStatusText}</span>
            <span class="badge ${loadStatusClass} ml-1 load-badge">${loadStatusText}</span>
          </div>
          <i class="fas fa-edit edit-icon" data-lineid="${device.LINEID}" title="Edit Device"></i>
        </div>
        <h6 class="card-title">
          <i class="fas fa-sim-card mr-2 text-primary"></i> ${device.NUMBER || 'No Number'}
        </h6>
        <p class="card-text mb-2">
          <strong>Balance:</strong> <span class="balance-value ${balance < 5 ? 'low-balance' : ''}">${balance.toFixed(2)} GB</span>
        </p>
        <p class="card-text mb-2"><strong>User:</strong> ${device.PERSON_USING || '-'}</p>
        <p class="card-text mb-2"><strong>Department:</strong> ${device.DEPARTMENT || '-'}</p>
        <div class="device-detail-row"><span class="detail-label">IMEI:</span><span><small>${imeiDisplay}</small></span></div>
        <div class="device-detail-row"><span class="detail-label">Serial:</span><span><small>${serialDisplay}</small></span></div>
        <div class="device-detail-row"><span class="detail-label">Model:</span><span><small>${device.MODEL || '-'}</small></span></div>
        <div class="device-detail-row"><span class="detail-label">Brand:</span><span><small>${device.BRAND || '-'}</small></span></div>
        <p class="card-text mb-1 small text-muted mt-2"><strong>Deployed:</strong> ${device.DATE_DEPLOYED || '-'}</p>
        <p class="card-text mb-1 small text-muted"><strong>Last Load:</strong> ${device.LAST_LOAD_HISTORY || 'Never'}</p>
        <div class="card-footer-actions">
          ${isActive ? 
            `<button class="btn btn-sm btn-outline-danger flex-fill deactivate-btn" data-lineid="${device.LINEID}" data-number="${device.NUMBER || ''}"><i class="fas fa-ban mr-1"></i> Deactivate</button>` :
            `<button class="btn btn-sm btn-outline-success flex-fill activate-btn" data-lineid="${device.LINEID}" data-number="${device.NUMBER || ''}"><i class="fas fa-check mr-1"></i> Activate</button>`
          }
          <button class="btn btn-sm btn-outline-info edit-btn-secondary" data-lineid="${device.LINEID}"><i class="fas fa-edit mr-1"></i> Edit</button>
        </div>
      </div>
    </div>
  `;
  return cardDiv;
}

// Update filter badges without full re-render
function updateSiteFilterUI() {
  const sites = [...new Set(allDevices.map(d => d.SITE_ID).filter(s => s && s.trim()))];
  const container = document.getElementById('siteFilterContainer');
  if (sites.length === 0) {
    container.innerHTML = '<span class="text-muted">No sites available</span>';
    return;
  }
  container.innerHTML = sites.map(site => `
    <span class="badge badge-pill site-badge px-3 py-2 mr-2 mb-2 ${currentSiteFilter === site ? 'active badge-primary' : 'badge-secondary'}" data-site="${site}">
      <i class="fas fa-map-marker-alt mr-1"></i> ${site}
    </span>
  `).join('');
  
  document.querySelectorAll('.site-badge').forEach(el => {
    el.addEventListener('click', () => {
      currentSiteFilter = el.dataset.site;
      updateSiteFilterUI();
      applyFilterToDOM();
    });
  });
}

// Apply filter by showing/hiding existing cards (no full rebuild)
function applyFilterToDOM() {
  const allCardContainers = document.querySelectorAll('.device-card-item');
  let visibleCount = 0;
  allCardContainers.forEach(card => {
    const siteAttr = card.getAttribute('data-site');
    if (!currentSiteFilter || siteAttr === currentSiteFilter) {
      card.style.display = '';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });
  const stats = document.getElementById('filterStats');
  if (currentSiteFilter) {
    stats.innerHTML = `<i class="fas fa-chart-line mr-1"></i> Showing ${visibleCount} of ${allDevices.length} devices for site: <strong>${currentSiteFilter}</strong>`;
  } else {
    stats.innerHTML = `<i class="fas fa-chart-line mr-1"></i> Showing all ${allDevices.length} devices`;
  }
  if (visibleCount === 0 && allDevices.length > 0) {
    const noMatch = document.getElementById('noDevicesMatch');
    if(!noMatch) {
      const warnDiv = document.createElement('div');
      warnDiv.id = 'noDevicesMatch';
      warnDiv.className = 'col-12 text-center alert alert-warning mt-3';
      warnDiv.innerText = 'No devices match the selected site filter.';
      document.getElementById('deviceList').appendChild(warnDiv);
    }
  } else {
    const existingWarn = document.getElementById('noDevicesMatch');
    if(existingWarn) existingWarn.remove();
  }
}

// Replace a single device card in the DOM (update only that device)
function replaceDeviceCard(updatedDevice) {
  const existingCard = document.querySelector(`.device-card-item[data-lineid="${updatedDevice.LINEID}"]`);
  const newCard = createDeviceCard(updatedDevice);
  if (existingCard) {
    existingCard.replaceWith(newCard);
    attachCardEventHandlers(newCard);
  } else {
    // if not present (filter might hide it, but we add to container)
    const container = document.getElementById('deviceList');
    const insertBefore = null;
    container.appendChild(newCard);
    attachCardEventHandlers(newCard);
    // re-run filter to keep visibility
    applyFilterToDOM();
  }
  // also update in-memory copy
  const index = allDevices.findIndex(d => d.LINEID == updatedDevice.LINEID);
  if (index !== -1) allDevices[index] = updatedDevice;
  else allDevices.push(updatedDevice);
  updateSiteFilterUI(); // in case site changed
  applyFilterToDOM();
}

// Remove device card (for deactivate/activate we just update status, but for completeness)
function removeDeviceCard(lineid) {
  const card = document.querySelector(`.device-card-item[data-lineid="${lineid}"]`);
  if (card) card.remove();
  allDevices = allDevices.filter(d => d.LINEID != lineid);
  updateSiteFilterUI();
  applyFilterToDOM();
}

// Attach event listeners for buttons on a specific card
function attachCardEventHandlers(cardElement) {
  const activateBtn = cardElement.querySelector('.activate-btn');
  const deactivateBtn = cardElement.querySelector('.deactivate-btn');
  const editBtns = cardElement.querySelectorAll('.edit-icon, .edit-btn-secondary');
  
  if (activateBtn) {
    activateBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleDeviceStatusInline(activateBtn.dataset.lineid, 1, activateBtn.dataset.number);
    });
  }
  if (deactivateBtn) {
    deactivateBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleDeviceStatusInline(deactivateBtn.dataset.lineid, 0, deactivateBtn.dataset.number);
    });
  }
  editBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      openEditModal(btn.dataset.lineid);
    });
  });
}

// Optimistic status toggle + API call, update card in-place
function toggleDeviceStatusInline(lineid, newStatus, number) {
  const actionWord = newStatus === 1 ? 'ACTIVATE' : 'DEACTIVATE';
  if (!confirm(`Are you sure you want to ${actionWord} this device?`)) return;
  
  const cardDiv = document.querySelector(`.device-card-item[data-lineid="${lineid}"]`);
  if (cardDiv) cardDiv.classList.add('card-updating');
  
  fetch('/LM/datafetcher/updatestatus.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ lineid: lineid, is_active: newStatus })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      // Update local device object
      const deviceIndex = allDevices.findIndex(d => d.LINEID == lineid);
      if (deviceIndex !== -1) {
        const updatedDevice = { ...allDevices[deviceIndex] };
        updatedDevice.STATUS = newStatus === 1 ? 1 : 0;
        // Replace card with new status
        replaceDeviceCard(updatedDevice);
      }
      showToast(`Device ${number || ''} ${newStatus === 1 ? 'activated' : 'deactivated'}`, 'success');
    } else {
      showToast(res.message || 'Failed to update status', 'danger');
    }
  })
  .catch(err => {
    console.error(err);
    showToast('Error updating device status', 'danger');
  })
  .finally(() => {
    if (cardDiv) cardDiv.classList.remove('card-updating');
  });
}

// Edit modal & update with DOM replacement (no full refresh)
function openEditModal(lineid) {
  const device = allDevices.find(d => d.LINEID == lineid);
  if (!device) { showToast('Device not found', 'danger'); return; }
  
  document.getElementById('edit_LINEID').value = device.LINEID;
  document.getElementById('edit_SITE_ID').value = device.SITE_ID || '';
  document.getElementById('edit_DEPARTMENT').value = device.DEPARTMENT || '';
  document.getElementById('edit_PRINCIPAL').value = device.PRINCIPAL || '';
  document.getElementById('edit_POSITION').value = device.POSITION || '';
  document.getElementById('edit_BRAND').value = device.BRAND || '';
  document.getElementById('edit_MODEL').value = device.MODEL || '';
  document.getElementById('edit_IMEI').value = device.IMEI || '';
  document.getElementById('edit_SERIAL').value = device.SERIAL || '';
  document.getElementById('edit_DATE_DEPLOYED').value = device.DATE_DEPLOYED ? device.DATE_DEPLOYED.split('T')[0] : '';
  document.getElementById('edit_PERSON_USING').value = device.PERSON_USING || '';
  document.getElementById('edit_NUMBER').value = device.NUMBER || '';
  document.getElementById('edit_BALANCE').value = device.BALANCE || 0;
  document.getElementById('edit_DATA_BALANCE_MIN').value = device.DATA_BALANCE_MIN || 0;
  document.getElementById('edit_LOAD_TERMS').value = device.LOAD_TERMS || 1;
  document.getElementById('edit_REMARKS').value = device.REMARKS || '';
  const isActive = (device.STATUS == 1 || device.STATUS === 'ACTIVE' || device.STATUS === 'IN USE');
  document.getElementById('edit_STATUS').value = isActive ? 'ACTIVE' : 'INACTIVE';
  
  $('#editDeviceModal').modal('show');
}

function updateDeviceHandler() {
  const lineid = document.getElementById('edit_LINEID').value;
  const updatedData = {
    lineid: lineid,
    SITE_ID: document.getElementById('edit_SITE_ID').value.trim(),
    DEPARTMENT: document.getElementById('edit_DEPARTMENT').value.trim(),
    PRINCIPAL: document.getElementById('edit_PRINCIPAL').value.trim(),
    POSITION: document.getElementById('edit_POSITION').value.trim(),
    BRAND: document.getElementById('edit_BRAND').value.trim(),
    MODEL: document.getElementById('edit_MODEL').value.trim(),
    IMEI: document.getElementById('edit_IMEI').value.trim(),
    SERIAL: document.getElementById('edit_SERIAL').value.trim(),
    DATE_DEPLOYED: document.getElementById('edit_DATE_DEPLOYED').value,
    PERSON_USING: document.getElementById('edit_PERSON_USING').value.trim(),
    NUMBER: document.getElementById('edit_NUMBER').value.trim(),
    BALANCE: parseFloat(document.getElementById('edit_BALANCE').value) || 0,
    LOAD_TERMS: parseInt(document.getElementById('edit_LOAD_TERMS').value) || 1,
    DATA_BALANCE_MIN: parseInt(document.getElementById('edit_DATA_BALANCE_MIN').value) || 0,
    REMARKS: document.getElementById('edit_REMARKS').value.trim(),
    STATUS_TEXT: document.getElementById('edit_STATUS').value
  };
  if (!updatedData.SITE_ID) return showToast('Site is required', 'danger');
  if (!updatedData.DATE_DEPLOYED) return showToast('Date Deployed is required', 'danger');
  
  const cardDiv = document.querySelector(`.device-card-item[data-lineid="${lineid}"]`);
  if (cardDiv) cardDiv.classList.add('card-updating');
  
  fetch('/LM/datafetcher/updatestatus.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(updatedData)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      // Merge updated fields into existing device object
      const index = allDevices.findIndex(d => d.LINEID == lineid);
      if (index !== -1) {
        const oldDevice = allDevices[index];
        const newDevice = { ...oldDevice, ...updatedData, STATUS: updatedData.STATUS_TEXT === 'ACTIVE' ? 1 : 0 };
        replaceDeviceCard(newDevice);
      }
      showToast('Device updated successfully!', 'success');
      $('#editDeviceModal').modal('hide');
    } else {
      showToast(res.message || 'Failed to update device', 'danger');
    }
  })
  .catch(err => { showToast('Error updating device', 'danger'); })
  .finally(() => { if (cardDiv) cardDiv.classList.remove('card-updating'); });
}

function addDeviceHandler() {
  const data = {
    SITE_ID: document.getElementById('SITE_ID').value.trim(),
    DEPARTMENT: document.getElementById('DEPARTMENT').value.trim(),
    PRINCIPAL: document.getElementById('PRINCIPAL').value.trim(),
    POSITION: document.getElementById('POSITION').value.trim(),
    BRAND: document.getElementById('BRAND').value.trim(),
    MODEL: document.getElementById('MODEL').value.trim(),
    IMEI: document.getElementById('IMEI').value.trim(),
    SERIAL: document.getElementById('SERIAL').value.trim(),
    DATE_DEPLOYED: document.getElementById('DATE_DEPLOYED').value,
    PERSON_USING: document.getElementById('PERSON_USING').value.trim(),
    NUMBER: document.getElementById('NUMBER').value.trim(),
    BALANCE: parseFloat(document.getElementById('BALANCE').value) || 0,
    MONTHTOLOAD: parseInt(document.getElementById('MONTHTOLOAD').value) || 1,
    REMARKS: document.getElementById('REMARKS').value.trim()
  };
  if (!data.SITE_ID) return showToast('Site is required', 'danger');
  if (!data.DATE_DEPLOYED) return showToast('Date Deployed is required', 'danger');
  if (data.BALANCE <= 0) return showToast('Initial Balance must be greater than 0', 'danger');
  if (!data.MONTHTOLOAD) return showToast('Months to Load required', 'danger');
  
  fetch('/LM/datafetcher/adddevice.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success && res.device) {
      const newDevice = res.device;
      allDevices.push(newDevice);
      const newCard = createDeviceCard(newDevice);
      document.getElementById('deviceList').appendChild(newCard);
      attachCardEventHandlers(newCard);
      updateSiteFilterUI();
      applyFilterToDOM();
      showToast('Device added successfully!', 'success');
      $('#addDeviceModal').modal('hide');
      document.getElementById('addDeviceForm').reset();
    } else {
      showToast(res.message || 'Failed to add device', 'danger');
    }
  })
  .catch(err => { showToast('Error saving device', 'danger'); });
}

function initialLoadDevices() {
  const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
  const loading = document.getElementById('loading');
  const noDevices = document.getElementById('noDevices');
  const errorMsg = document.getElementById('errorMessage');
  loading.classList.remove('d-none');
  noDevices.classList.add('d-none');
  errorMsg.classList.add('d-none');
  
  fetch(`/LM/datafetcher/updatestatus.php?action=loaddevice&company=${encodeURIComponent(companyId)}`, { cache: 'no-store' })
    .then(response => response.json())
    .then(data => {
      loading.classList.add('d-none');
      if (!Array.isArray(data) || data.length === 0) {
        noDevices.classList.remove('d-none');
        allDevices = [];
        document.getElementById('deviceList').innerHTML = '';
        updateSiteFilterUI();
        applyFilterToDOM();
        return;
      }
      allDevices = data;
      const container = document.getElementById('deviceList');
      container.innerHTML = '';
      allDevices.forEach(device => {
        const card = createDeviceCard(device);
        container.appendChild(card);
        attachCardEventHandlers(card);
      });
      updateSiteFilterUI();
      applyFilterToDOM();
    })
    .catch(error => {
      loading.classList.add('d-none');
      errorMsg.innerHTML = `<i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>Failed to load devices: ${error.message}`;
      errorMsg.classList.remove('d-none');
    });
}

document.addEventListener('DOMContentLoaded', () => {
  initialLoadDevices();
  document.getElementById('btnSaveDevice').addEventListener('click', addDeviceHandler);
  document.getElementById('btnUpdateDevice').addEventListener('click', updateDeviceHandler);
  document.getElementById('clearFilterBtn').addEventListener('click', () => {
    currentSiteFilter = '';
    updateSiteFilterUI();
    applyFilterToDOM();
  });
  document.getElementById('exportBtn').addEventListener('click', exportDevices);
});
</script>
</body>
</html>