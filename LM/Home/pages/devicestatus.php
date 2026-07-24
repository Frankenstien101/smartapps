<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Device IR Compliance — Non-Good Condition Status</title>

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
  .status-bad { background-color: #f8d7da; color: #721c24; font-weight: bold; }
  .status-ok { background-color: #d4edda; color: #155724; font-weight: 600; }
  .status-pending { background-color: #fff3cd; color: #856404; font-weight: 600; }
  .card-body { padding: 1.25rem; }
  .card-title { font-size: 1.1rem; margin-bottom: 0.75rem; font-weight: 600; }
  .card-text { font-size: 0.85rem; margin-bottom: 0.4rem; color: #495057; }
  .badge { font-size: 0.75rem; padding: 0.4em 0.8em; }
  #loading, #noDevices, #errorMessage { min-height: 50vh; display: flex; align-items: center; justify-content: center; flex-direction: column; }
  .filter-section { background: white; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
  .device-detail-row { display: flex; justify-content: space-between; font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem; }
  .detail-label { font-weight: 600; }
  .card-footer-actions { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e9ecef; display: flex; gap: 0.5rem; flex-wrap: wrap; }
  .btn-sm-custom { font-size: 0.7rem; padding: 0.25rem 0.5rem; }
  .toast-notify { position: fixed; bottom: 20px; right: 20px; z-index: 9999; min-width: 260px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
  .card-updating { opacity: 0.6; transition: 0.1s; pointer-events: none; }
  .header-actions { display: flex; gap: 0.5rem; align-items: center; }
  .compliance-badge { cursor: pointer; }
  .attachment-preview { max-width: 100px; max-height: 100px; margin-top: 5px; border-radius: 4px; border: 1px solid #dee2e6; padding: 3px; }
  .camera-container { position: relative; width: 100%; max-width: 400px; margin: 10px 0; }
  #videoElement1, #videoElement2 { width: 100%; border-radius: 8px; border: 2px solid #dee2e6; background: #000; }
  .camera-controls { display: flex; gap: 0.5rem; margin-top: 8px; flex-wrap: wrap; }
  .attachment-section { border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f8f9fa; }
  .attachment-section .section-title { font-weight: 600; color: #495057; margin-bottom: 10px; }
  .preview-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
  .preview-item { position: relative; border: 1px solid #dee2e6; border-radius: 4px; padding: 5px; background: white; }
  .preview-item img { max-width: 80px; max-height: 80px; border-radius: 4px; }
  .preview-item .remove-btn { position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; border-radius: 50%; background: #dc3545; color: white; border: none; font-size: 12px; cursor: pointer; line-height: 20px; text-align: center; }
</style>
</head>
<body>

<div class="full-screen-container">
  <div class="header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fas fa-exclamation-triangle mr-2 text-danger"></i> Device IR Compliance
        <span class="badge badge-danger ml-2" id="deviceCountBadge">0</span>
      </h4>
      <div class="header-actions">
        <button class="btn btn-info btn-sm" id="refreshBtn">
          <i class="fas fa-sync mr-1"></i> Refresh
        </button>
        <button class="btn btn-success btn-sm" id="exportBtn">
          <i class="fas fa-file-export mr-1"></i> Export
        </button>
      </div>
    </div>
    <div class="mt-2">
      <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Showing devices with <strong>DEVICE_STATUS != 'Good Condition'</strong></small>
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
      <i class="fas fa-check-circle fa-2x mb-3 d-block text-success"></i>
      All devices are in good standing!<br>No devices with status other than 'Good Condition' found.
    </div>
    <div id="errorMessage" class="alert alert-danger text-center my-5 d-none"></div>
  </div>
</div>

<!-- IR Compliance Modal -->
<div class="modal fade" id="irComplianceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-warning text-dark">
        <h6 class="modal-title"><i class="fas fa-clipboard-check mr-2"></i> IR Compliance</h6>
        <button type="button" class="close" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ir_lineid">
        <div class="row mb-3">
          <div class="col-md-3">
            <label><i class="fas fa-phone mr-1"></i> Device Number</label>
            <p class="font-weight-bold" id="ir_device_number">-</p>
          </div>
          <div class="col-md-3">
            <label><i class="fas fa-user mr-1"></i> User</label>
            <p id="ir_device_user">-</p>
          </div>
          <div class="col-md-3">
            <label><i class="fas fa-map-marker-alt mr-1"></i> Site</label>
            <p id="ir_device_site">-</p>
          </div>
          <div class="col-md-3">
            <label><i class="fas fa-info-circle mr-1"></i> Status</label>
            <p><span class="badge status-bad" id="ir_device_status">-</span></p>
          </div>
        </div>
        
        <hr>
        
        <div class="form-group">
          <label><i class="fas fa-check-circle mr-1"></i> Mark as IR Complied</label>
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="ir_complied_check">
            <label class="custom-control-label" for="ir_complied_check">Yes, this device is now IR Complied</label>
          </div>
        </div>
        
        <!-- Attachment 1 -->
        <div class="attachment-section">
          <div class="section-title">
            <i class="fas fa-paperclip mr-1"></i> Attachment 1 <span class="text-muted">(Optional)</span>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="ir_attachment1_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                <label class="custom-file-label" for="ir_attachment1_file">Choose file...</label>
              </div>
            </div>
            <div class="col-md-6">
              <button class="btn btn-outline-danger btn-sm camera-toggle-btn" data-target="camera1">
                <i class="fas fa-camera mr-1"></i> Use Camera
              </button>
            </div>
          </div>
          
          <!-- Camera Section 1 -->
          <div id="camera1" class="camera-section mt-3 d-none">
            <div class="camera-container">
              <video id="videoElement1" autoplay playsinline></video>
              <div class="camera-controls">
                <button class="btn btn-danger btn-sm capture-btn" data-target="videoElement1" data-attachment="attachment1">
                  <i class="fas fa-camera mr-1"></i> Capture Photo
                </button>
                <button class="btn btn-secondary btn-sm close-camera-btn" data-target="camera1">
                  <i class="fas fa-times mr-1"></i> Close Camera
                </button>
              </div>
            </div>
            <canvas id="canvasElement1" class="d-none"></canvas>
          </div>
          
          <!-- Preview 1 -->
          <div id="preview1" class="preview-container"></div>
        </div>
        
        <!-- Attachment 2 -->
        <div class="attachment-section">
          <div class="section-title">
            <i class="fas fa-paperclip mr-1"></i> Attachment 2 <span class="text-muted">(Optional)</span>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="ir_attachment2_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                <label class="custom-file-label" for="ir_attachment2_file">Choose file...</label>
              </div>
            </div>
            <div class="col-md-6">
              <button class="btn btn-outline-danger btn-sm camera-toggle-btn" data-target="camera2">
                <i class="fas fa-camera mr-1"></i> Use Camera
              </button>
            </div>
          </div>
          
          <!-- Camera Section 2 -->
          <div id="camera2" class="camera-section mt-3 d-none">
            <div class="camera-container">
              <video id="videoElement2" autoplay playsinline></video>
              <div class="camera-controls">
                <button class="btn btn-danger btn-sm capture-btn" data-target="videoElement2" data-attachment="attachment2">
                  <i class="fas fa-camera mr-1"></i> Capture Photo
                </button>
                <button class="btn btn-secondary btn-sm close-camera-btn" data-target="camera2">
                  <i class="fas fa-times mr-1"></i> Close Camera
                </button>
              </div>
            </div>
            <canvas id="canvasElement2" class="d-none"></canvas>
          </div>
          
          <!-- Preview 2 -->
          <div id="preview2" class="preview-container"></div>
        </div>
        
        <div class="form-group">
          <label><i class="fas fa-sticky-note mr-1"></i> Remarks / Notes</label>
          <textarea class="form-control form-control-sm" id="ir_remarks" rows="3" placeholder="Add any notes about the IR compliance..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button class="btn btn-success btn-sm" id="btnMarkComplied">
          <i class="fas fa-check mr-1"></i> Mark as IR Complied
        </button>
      </div>
    </div>
  </div>
</div>

<!-- View Attachment Modal -->
<div class="modal fade" id="viewAttachmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-info text-white">
        <h6 class="modal-title"><i class="fas fa-eye mr-2"></i> Attachment Preview</h6>
        <button type="button" class="close text-white" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body text-center" id="attachmentPreviewContent">
        <!-- Content loaded dynamically -->
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const API_BASE = '/LM/datafetcher/ir_compliance_api.php';
const BASE_URL = window.location.origin; // Gets http://localhost:3000

let allDevices = [];
let currentSiteFilter = '';
let cameraStreams = {
    camera1: null,
    camera2: null
};
let capturedImages = {
    attachment1: null,
    attachment2: null
};

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

// Export function
function exportDevices() {
  if (allDevices.length === 0) {
    showToast('No devices to export', 'danger');
    return;
  }

  let devicesToExport = allDevices;
  if (currentSiteFilter) {
    devicesToExport = allDevices.filter(d => d.SITE_ID === currentSiteFilter);
  }

  if (devicesToExport.length === 0) {
    showToast('No devices match the current filter', 'danger');
    return;
  }

  const headers = [
    'LINEID', 'Site', 'Department', 'Principal', 'Position', 'Brand', 'Model',
    'IMEI', 'Serial', 'User', 'Mobile Number', 'Balance (GB)',
    'Device Status', 'IR Complied', 'Date Complied', 'Remarks'
  ];

  const rows = devicesToExport.map(device => {
    const irComplied = device.DEVICE_IR_COMPLIED ? 'YES' : 'NO';
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
      device.PERSON_USING || '',
      device.NUMBER || '',
      Number(device.BALANCE || 0).toFixed(2),
      device.DEVICE_STATUS || 'Unknown',
      irComplied,
      device.DATE_COMPLIED || '',
      device.REMARKS || ''
    ];
  });

  const csvContent = [
    headers.join(','),
    ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
  ].join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  const fileName = `ir_not_good_export_${new Date().toISOString().split('T')[0]}.csv`;
  link.setAttribute('download', fileName);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);

  showToast(`Exported ${devicesToExport.length} devices successfully!`, 'success');
}

// View attachment - FIXED: Use absolute URL from root
function viewAttachment(attachmentPath) {
    if (!attachmentPath) {
        showToast('No attachment available', 'danger');
        return;
    }
    
    // Decode the path if it was URL encoded
    let cleanPath = decodeURIComponent(attachmentPath);
    
    // Remove any leading dots, double slashes, or encoded characters
    cleanPath = cleanPath.replace(/\.\./g, '').replace(/\/\//g, '/');
    
    // Build the full URL
    let fullUrl;
    if (cleanPath.startsWith('/uploads/')) {
        // Use absolute URL from root
        fullUrl = BASE_URL + cleanPath;
    } else if (cleanPath.startsWith('uploads/')) {
        // If it doesn't start with /, add it
        fullUrl = BASE_URL + '/' + cleanPath;
    } else if (!cleanPath.startsWith('http')) {
        // If it's a relative path, assume it's in the uploads directory
        const filename = cleanPath.split('/').pop();
        fullUrl = BASE_URL + '/uploads/ir_compliance/' + filename;
    } else {
        fullUrl = cleanPath;
    }
    
    console.log('Viewing attachment:', fullUrl);
    
    const content = document.getElementById('attachmentPreviewContent');
    
    // Check if it's an image by extension
    const ext = fullUrl.split('.').pop().toLowerCase();
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    
    if (imageExts.includes(ext)) {
        content.innerHTML = `
            <div class="text-center">
                <img src="${fullUrl}" class="img-fluid" style="max-height: 500px; max-width: 100%;" alt="Attachment" 
                     onerror="this.onerror=null; this.alt='Image not found'; this.style.display='none'; 
                              document.getElementById('imgError').style.display='block';">
                <div id="imgError" style="display:none; color:red; margin-top:10px;">
                    <i class="fas fa-exclamation-triangle"></i> Image not found at: ${fullUrl}
                </div>
                <div class="mt-2">
                    <small class="text-muted">${fullUrl.split('/').pop()}</small>
                </div>
            </div>
        `;
    } else {
        content.innerHTML = `
            <i class="fas fa-file fa-4x text-muted mb-3 d-block"></i>
            <p>File: ${fullUrl.split('/').pop()}</p>
            <a href="${fullUrl}" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt mr-1"></i> Open File
            </a>
            <a href="${fullUrl}" download class="btn btn-success btn-sm ml-2">
                <i class="fas fa-download mr-1"></i> Download
            </a>
        `;
    }
    
    $('#viewAttachmentModal').modal('show');
}

// Render single device card
function createDeviceCard(device) {
  const isNotGood = device.DEVICE_STATUS !== 'Good Condition';
  const statusClass = isNotGood ? 'status-bad' : 'status-ok';
  const statusText = device.DEVICE_STATUS || 'Unknown';
  
  const irComplied = device.DEVICE_IR_COMPLIED === 1 || device.DEVICE_IR_COMPLIED === '1' || device.DEVICE_IR_COMPLIED === true;
  const irBadgeClass = irComplied ? 'badge-success' : 'badge-warning';
  const irBadgeText = irComplied ? 'IR COMPLIED' : 'PENDING IR';
  
  const cardDiv = document.createElement('div');
  cardDiv.className = 'col-md-6 col-lg-4 col-xl-3 mb-4 device-card-item';
  cardDiv.setAttribute('data-lineid', device.LINEID);
  cardDiv.setAttribute('data-site', device.SITE_ID || '');
  
  // Clean up attachment paths
  let attachment1 = device.ATTACHMENT1 || '';
  let attachment2 = device.ATTACHMENT2 || '';
  attachment1 = attachment1.replace(/\/\//g, '/');
  attachment2 = attachment2.replace(/\/\//g, '/');
  
  cardDiv.innerHTML = `
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="badge ${statusClass} status-badge">${statusText}</span>
            <span class="badge ${irBadgeClass} ml-1 compliance-badge">${irBadgeText}</span>
          </div>
          <span class="badge badge-secondary">#${device.LINEID || 'N/A'}</span>
        </div>
        <h6 class="card-title">
          <i class="fas fa-sim-card mr-2 text-primary"></i> ${device.NUMBER || 'No Number'}
        </h6>
        <p class="card-text mb-2">
          <strong>User:</strong> ${device.PERSON_USING || '-'}
        </p>
        <p class="card-text mb-2">
          <strong>Department:</strong> ${device.DEPARTMENT || '-'}
        </p>
        <p class="card-text mb-2">
          <strong>Site:</strong> ${device.SITE_ID || '-'}
        </p>
        <div class="device-detail-row">
          <span class="detail-label">Brand:</span>
          <span><small>${device.BRAND || '-'}</small></span>
        </div>
        <div class="device-detail-row">
          <span class="detail-label">Model:</span>
          <span><small>${device.MODEL || '-'}</small></span>
        </div>
        <div class="device-detail-row">
          <span class="detail-label">Balance:</span>
          <span><small>${Number(device.BALANCE || 0).toFixed(2)} GB</small></span>
        </div>
        ${attachment1 || attachment2 ? `
          <div class="mt-2">
            <small class="text-muted"><i class="fas fa-paperclip mr-1"></i> Attachments:</small>
            ${attachment1 ? `<span class="badge badge-info attachment-badge" data-attachment="${encodeURIComponent(attachment1)}" style="cursor:pointer;">File 1</span>` : ''}
            ${attachment2 ? `<span class="badge badge-info attachment-badge" data-attachment="${encodeURIComponent(attachment2)}" style="cursor:pointer;">File 2</span>` : ''}
          </div>
        ` : ''}
        <div class="card-footer-actions">
          ${!irComplied ? `
            <button class="btn btn-sm btn-warning flex-fill mark-complied-btn" data-lineid="${device.LINEID}" data-number="${device.NUMBER || ''}">
              <i class="fas fa-clipboard-check mr-1"></i> Mark IR Complied
            </button>
          ` : `
            <button class="btn btn-sm btn-outline-success flex-fill" disabled>
              <i class="fas fa-check-circle mr-1"></i> Already Complied
            </button>
          `}
        </div>
      </div>
    </div>
  `;
  return cardDiv;
}

// Update filter badges
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

// Apply filter
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
  
  document.getElementById('deviceCountBadge').textContent = visibleCount;
  
  const stats = document.getElementById('filterStats');
  if (currentSiteFilter) {
    stats.innerHTML = `<i class="fas fa-chart-line mr-1"></i> Showing ${visibleCount} of ${allDevices.length} devices for site: <strong>${currentSiteFilter}</strong>`;
  } else {
    stats.innerHTML = `<i class="fas fa-chart-line mr-1"></i> Showing all ${allDevices.length} devices with status != 'Good Condition'`;
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

// Replace a single device card
function replaceDeviceCard(updatedDevice) {
  const existingCard = document.querySelector(`.device-card-item[data-lineid="${updatedDevice.LINEID}"]`);
  const newCard = createDeviceCard(updatedDevice);
  if (existingCard) {
    existingCard.replaceWith(newCard);
    attachCardEventHandlers(newCard);
  } else {
    const container = document.getElementById('deviceList');
    container.appendChild(newCard);
    attachCardEventHandlers(newCard);
    applyFilterToDOM();
  }
  const index = allDevices.findIndex(d => d.LINEID == updatedDevice.LINEID);
  if (index !== -1) allDevices[index] = updatedDevice;
  else allDevices.push(updatedDevice);
  updateSiteFilterUI();
  applyFilterToDOM();
}

// Attach event listeners for buttons on a specific card
function attachCardEventHandlers(cardElement) {
  const markBtn = cardElement.querySelector('.mark-complied-btn');
  const attachmentBadges = cardElement.querySelectorAll('.attachment-badge');
  
  if (markBtn) {
    markBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      openIRComplianceModal(markBtn.dataset.lineid);
    });
  }
  
  attachmentBadges.forEach(badge => {
    badge.addEventListener('click', (e) => {
      e.stopPropagation();
      // Get the raw path (already encoded)
      const rawPath = badge.dataset.attachment;
      viewAttachment(rawPath);
    });
  });
}

// Open IR Compliance Modal
function openIRComplianceModal(lineid) {
  const device = allDevices.find(d => d.LINEID == lineid);
  if (!device) { showToast('Device not found', 'danger'); return; }
  
  document.getElementById('ir_lineid').value = lineid;
  document.getElementById('ir_device_number').textContent = device.NUMBER || 'N/A';
  document.getElementById('ir_device_user').textContent = device.PERSON_USING || 'N/A';
  document.getElementById('ir_device_site').textContent = device.SITE_ID || 'N/A';
  document.getElementById('ir_device_status').textContent = device.DEVICE_STATUS || 'Unknown';
  document.getElementById('ir_remarks').value = '';
  document.getElementById('ir_complied_check').checked = false;
  
  // Reset attachments
  ['ir_attachment1_file', 'ir_attachment2_file'].forEach(id => {
    document.getElementById(id).value = '';
    const label = document.querySelector(`[for="${id}"]`);
    if (label) label.textContent = 'Choose file...';
  });
  
  capturedImages = { attachment1: null, attachment2: null };
  document.getElementById('preview1').innerHTML = '';
  document.getElementById('preview2').innerHTML = '';
  
  // Close all cameras
  closeAllCameras();
  
  $('#irComplianceModal').modal('show');
}

// Camera functions
async function startCamera(cameraId, videoElementId) {
  try {
    if (cameraStreams[cameraId]) {
      cameraStreams[cameraId].getTracks().forEach(track => track.stop());
      cameraStreams[cameraId] = null;
    }
    
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'environment' },
      audio: false 
    });
    
    cameraStreams[cameraId] = stream;
    const video = document.getElementById(videoElementId);
    video.srcObject = stream;
    await video.play();
    
    const cameraSection = document.getElementById(cameraId);
    cameraSection.classList.remove('d-none');
    
    const toggleBtn = document.querySelector(`[data-target="${cameraId}"]`);
    toggleBtn.textContent = 'Close Camera';
    toggleBtn.classList.remove('btn-outline-danger');
    toggleBtn.classList.add('btn-danger');
    
  } catch (err) {
    showToast('Unable to access camera: ' + err.message, 'danger');
  }
}

function closeCamera(cameraId) {
  if (cameraStreams[cameraId]) {
    cameraStreams[cameraId].getTracks().forEach(track => track.stop());
    cameraStreams[cameraId] = null;
  }
  
  const cameraSection = document.getElementById(cameraId);
  cameraSection.classList.add('d-none');
  
  const toggleBtn = document.querySelector(`[data-target="${cameraId}"]`);
  toggleBtn.textContent = 'Use Camera';
  toggleBtn.classList.remove('btn-danger');
  toggleBtn.classList.add('btn-outline-danger');
}

function closeAllCameras() {
  ['camera1', 'camera2'].forEach(id => closeCamera(id));
}

function capturePhoto(videoElementId, attachmentKey, previewId) {
  const video = document.getElementById(videoElementId);
  const canvasId = videoElementId.replace('videoElement', 'canvasElement');
  const canvas = document.getElementById(canvasId);
  
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0);
  
  const imageData = canvas.toDataURL('image/png');
  capturedImages[attachmentKey] = imageData;
  
  const preview = document.getElementById(previewId);
  preview.innerHTML = `
    <div class="preview-item">
      <img src="${imageData}" alt="Captured photo">
      <button class="remove-btn" onclick="removeAttachment('${attachmentKey}', '${previewId}')">&times;</button>
    </div>
  `;
  
  const cameraId = attachmentKey === 'attachment1' ? 'camera1' : 'camera2';
  closeCamera(cameraId);
  
  showToast('Photo captured successfully!', 'success');
}

function removeAttachment(attachmentKey, previewId) {
  capturedImages[attachmentKey] = null;
  document.getElementById(previewId).innerHTML = '';
  
  const fileInputId = attachmentKey === 'attachment1' ? 'ir_attachment1_file' : 'ir_attachment2_file';
  document.getElementById(fileInputId).value = '';
  const label = document.querySelector(`[for="${fileInputId}"]`);
  if (label) label.textContent = 'Choose file...';
}

// Mark as IR Complied
function markAsIRComplied() {
  const lineid = document.getElementById('ir_lineid').value;
  const isComplied = document.getElementById('ir_complied_check').checked;
  const remarks = document.getElementById('ir_remarks').value.trim();
  
  if (!isComplied) {
    showToast('Please check the box to confirm IR compliance', 'danger');
    return;
  }
  
  if (!confirm('Are you sure you want to mark this device as IR Complied?')) {
    return;
  }
  
  const formData = new FormData();
  formData.append('lineid', lineid);
  formData.append('is_complied', '1');
  formData.append('remarks', remarks);
  
  // Process Attachment 1
  const fileInput1 = document.getElementById('ir_attachment1_file');
  if (fileInput1.files.length > 0) {
    formData.append('attachment1', fileInput1.files[0]);
  } else if (capturedImages.attachment1) {
    const imageData = capturedImages.attachment1;
    const byteString = atob(imageData.split(',')[1]);
    const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }
    const blob = new Blob([ab], { type: mimeString });
    const fileName = `ir_capture1_${lineid}_${new Date().getTime()}.png`;
    formData.append('attachment1', blob, fileName);
  }
  
  // Process Attachment 2
  const fileInput2 = document.getElementById('ir_attachment2_file');
  if (fileInput2.files.length > 0) {
    formData.append('attachment2', fileInput2.files[0]);
  } else if (capturedImages.attachment2) {
    const imageData = capturedImages.attachment2;
    const byteString = atob(imageData.split(',')[1]);
    const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }
    const blob = new Blob([ab], { type: mimeString });
    const fileName = `ir_capture2_${lineid}_${new Date().getTime()}.png`;
    formData.append('attachment2', blob, fileName);
  }
  
  const cardDiv = document.querySelector(`.device-card-item[data-lineid="${lineid}"]`);
  if (cardDiv) cardDiv.classList.add('card-updating');
  
  fetch(`${API_BASE}?action=mark_complied`, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(res => {
    if (res.success) {
      const index = allDevices.findIndex(d => d.LINEID == lineid);
      if (index !== -1) {
        const updatedDevice = { ...allDevices[index] };
        updatedDevice.DEVICE_IR_COMPLIED = 1;
        updatedDevice.DATE_COMPLIED = new Date().toISOString().split('T')[0];
        if (res.device) {
          if (res.device.ATTACHMENT1) updatedDevice.ATTACHMENT1 = res.device.ATTACHMENT1;
          if (res.device.ATTACHMENT2) updatedDevice.ATTACHMENT2 = res.device.ATTACHMENT2;
        }
        replaceDeviceCard(updatedDevice);
      }
      showToast('Device marked as IR Complied successfully!', 'success');
      $('#irComplianceModal').modal('hide');
    } else {
      showToast(res.message || 'Failed to update IR compliance', 'danger');
    }
  })
  .catch(err => {
    console.error(err);
    showToast('Error updating IR compliance', 'danger');
  })
  .finally(() => {
    if (cardDiv) cardDiv.classList.remove('card-updating');
  });
}

function loadDevices() {
  const loading = document.getElementById('loading');
  const noDevices = document.getElementById('noDevices');
  const errorMsg = document.getElementById('errorMessage');
  loading.classList.remove('d-none');
  noDevices.classList.add('d-none');
  errorMsg.classList.add('d-none');
  
  fetch(`${API_BASE}?action=get_not_ok_devices`, { cache: 'no-store' })
    .then(response => response.json())
    .then(data => {
      loading.classList.add('d-none');
      if (!Array.isArray(data) || data.length === 0) {
        noDevices.classList.remove('d-none');
        allDevices = [];
        document.getElementById('deviceList').innerHTML = '';
        document.getElementById('deviceCountBadge').textContent = '0';
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
      document.getElementById('deviceCountBadge').textContent = allDevices.length;
      updateSiteFilterUI();
      applyFilterToDOM();
    })
    .catch(error => {
      loading.classList.add('d-none');
      errorMsg.innerHTML = `<i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>Failed to load devices: ${error.message}`;
      errorMsg.classList.remove('d-none');
    });
}

// File input label update
document.addEventListener('change', function(e) {
  if (e.target.id === 'ir_attachment1_file') {
    const fileInput = e.target;
    const label = document.querySelector('[for="ir_attachment1_file"]');
    if (fileInput.files.length > 0) {
      label.textContent = fileInput.files[0].name;
      if (capturedImages.attachment1) {
        capturedImages.attachment1 = null;
        document.getElementById('preview1').innerHTML = '';
      }
    } else {
      label.textContent = 'Choose file...';
    }
  }
  
  if (e.target.id === 'ir_attachment2_file') {
    const fileInput = e.target;
    const label = document.querySelector('[for="ir_attachment2_file"]');
    if (fileInput.files.length > 0) {
      label.textContent = fileInput.files[0].name;
      if (capturedImages.attachment2) {
        capturedImages.attachment2 = null;
        document.getElementById('preview2').innerHTML = '';
      }
    } else {
      label.textContent = 'Choose file...';
    }
  }
});

document.addEventListener('DOMContentLoaded', () => {
  loadDevices();
  
  document.getElementById('refreshBtn').addEventListener('click', () => {
    loadDevices();
    showToast('Refreshing device list...', 'success');
  });
  
  document.getElementById('exportBtn').addEventListener('click', exportDevices);
  document.getElementById('clearFilterBtn').addEventListener('click', () => {
    currentSiteFilter = '';
    updateSiteFilterUI();
    applyFilterToDOM();
  });
  
  document.querySelectorAll('.camera-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const cameraSection = document.getElementById(targetId);
      
      if (cameraSection.classList.contains('d-none')) {
        const videoId = targetId === 'camera1' ? 'videoElement1' : 'videoElement2';
        startCamera(targetId, videoId);
      } else {
        closeCamera(targetId);
      }
    });
  });
  
  document.querySelectorAll('.capture-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const videoId = btn.dataset.target;
      const attachmentKey = btn.dataset.attachment;
      const previewId = attachmentKey === 'attachment1' ? 'preview1' : 'preview2';
      capturePhoto(videoId, attachmentKey, previewId);
    });
  });
  
  document.querySelectorAll('.close-camera-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      closeCamera(targetId);
    });
  });
  
  document.getElementById('btnMarkComplied').addEventListener('click', markAsIRComplied);
  
  $('#irComplianceModal').on('hidden.bs.modal', function () {
    closeAllCameras();
    capturedImages = { attachment1: null, attachment2: null };
    document.getElementById('preview1').innerHTML = '';
    document.getElementById('preview2').innerHTML = '';
  });
});
</script>
</body>
</html>