<?php
if (!isset($_SESSION['Company_ID']) || empty($_SESSION['Company_ID'])) {
    header("Location: /LM/Home/verify.php");
    exit;
}
$companyId = $_SESSION['Company_ID'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes - Device Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #744303 0%, #a15c01 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding-bottom: 30px;
        }

        .header-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border: none;
            overflow: hidden;
        }

        .header-card .card-header {
            background: linear-gradient(135deg, #723e02 0%, #b96a03 100%);
            color: white;
            padding: 20px 25px;
            border: none;
        }

        .header-card .card-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .header-card .card-body {
            padding: 25px;
        }

        .upload-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: none;
            transition: transform 0.3s ease;
        }

        .upload-card:hover {
            transform: translateY(-5px);
        }

        .upload-card .card-body {
            padding: 25px;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: none;
        }

        .qr-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            margin: 8px 0;
            background: white;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            height: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .qr-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        /* WALLPAPER STYLES - WITH EXTRA MARGINS TO PREVENT ZOOM CROPPING */
        .qr-card-for-image {
            width: 1200px;
            min-height: 2000px;
            padding: 100px 80px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            text-align: center;
            position: relative;
            box-shadow: none;
        }

        .qr-card-for-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(135deg, #ac6201 0%, #724b02 100%);
        }

        .qr-card-for-image .company-header {
            font-size: 1.8rem;
            color: #03135c;
            margin-bottom: 80px;
            font-weight: 600;
            letter-spacing: 2px;
        }

        .qr-card-for-image .qr-container {
            width: 450px;
            height: 450px;
            margin: 0 auto 50px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .qr-card-for-image .qr-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 30px 20px;
            color: #333;
            word-break: break-all;
            background: #f0f0f0;
            padding: 20px;
            border-radius: 15px;
        }

        .qr-card-for-image .serial-bottom {
            font-size: 1.3rem;
            color: #d32f2f;
            font-weight: 600;
            margin: 20px 0;
            padding: 15px;
            background: #fff5f5;
            border-radius: 15px;
        }

        .qr-card-for-image .site-badge {
            font-size: 1.3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 40px;
            display: inline-block;
            margin: 20px auto;
        }

        .qr-card-for-image .assigned-to {
            margin: 25px 0;
            font-size: 1.3rem;
            color: #666;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 15px;
        }

        .qr-card-for-image .detail-row {
            margin: 15px 0;
            font-size: 1.2rem;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .qr-card-for-image .footer-text {
            margin-top: 80px;
            font-size: 1rem;
            color: #999;
            border-top: 1px solid #e0e0e0;
            padding-top: 30px;
        }

        .qr-container {
            width: 170px;
            height: 170px;
            margin: 0 auto 12px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            padding: 10px;
            border-radius: 10px;
        }

        .qr-value {
            font-size: 0.95rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
            word-break: break-all;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #021e6b 0%, #025cc4 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 40px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #999;
        }

        .batch-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .card-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
            z-index: 10;
        }

        .card-action-btn {
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 5px;
            padding: 5px 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .card-action-btn:hover {
            background: white;
            transform: scale(1.05);
        }

        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .qr-card {
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        .progress-bar-custom {
            height: 4px;
            background: linear-gradient(135deg, #206301 0%, #015810 100%);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="container-fluid px-4 py-4">

        <div class="header-card no-print">
            <div class="card-header">
                <h4>
                    <i class="fas fa-qrcode"></i> QR Code Generator
                    <small class="float-right text-white-50">
                        <i class="fas fa-building"></i> Company ID: <?php echo htmlspecialchars($companyId); ?>
                    </small>
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number" id="totalCount">0</div>
                            <div class="text-muted">Total Devices</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number" id="displayCount">0</div>
                            <div class="text-muted">Currently Showing</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number" id="siteCount">0</div>
                            <div class="text-muted">Active Sites</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number" id="qrGenerated">0</div>
                            <div class="text-muted">QR Codes Generated</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="upload-card no-print">
            <div class="card-body">
                <h5 class="mb-3">
                    <i class="fas fa-cloud-upload-alt"></i> Generate QR Codes
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="custom-file">
                            <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" class="custom-file-input">
                            <label class="custom-file-label" for="excelFile">Choose Excel/CSV file</label>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Supported formats: .xlsx, .xls, .csv (Max 5MB)
                        </small>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-block" onclick="uploadExcel()">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-block" onclick="refreshDevices()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-danger btn-block" onclick="downloadAllCardsAsImages()">
                            <i class="fas fa-images"></i> Save as Wallpaper
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <a href="#" onclick="downloadTemplate()" class="text-primary">
                            <i class="fas fa-file-download"></i> Download Excel Template
                        </a>
                        <span class="text-muted mx-2">|</span>
                        <a href="#" onclick="clearAllFilters()" class="text-info">
                            <i class="fas fa-eraser"></i> Clear All Filters
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-card no-print">
            <div class="card-body">
                <h5 class="mb-3">
                    <i class="fas fa-filter"></i> Filter QR Codes
                </h5>
                <div class="row">
                    <div class="col-md-3">
                        <label>Site Filter</label>
                        <select id="siteFilter" class="form-control" onchange="filterQRcodes()">
                            <option value="">All Sites</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Search Device</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search..." onkeyup="filterQRcodes()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>Sort By</label>
                        <select id="sortBy" class="form-control" onchange="filterQRcodes()">
                            <option value="default">Default Order</option>
                            <option value="serial_asc">Serial (A-Z)</option>
                            <option value="serial_desc">Serial (Z-A)</option>
                            <option value="site_asc">Site (A-Z)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>View Mode</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary active" onclick="setViewMode('grid')">
                                <i class="fas fa-th-large"></i> Grid
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="setViewMode('list')">
                                <i class="fas fa-list"></i> List
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="batch-actions">
                            <button class="btn btn-sm btn-success" onclick="downloadSelectedCardsAsImages()">
                                <i class="fas fa-download"></i> Save Selected (0)
                            </button>
                            <button class="btn btn-sm btn-info" onclick="printQRCodes()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="selectAll()">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                                <i class="fas fa-square"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="progressBar" class="progress mb-3 d-none no-print" style="height: 4px;">
            <div class="progress-bar-custom" style="width: 0%"></div>
        </div>

        <div id="qrContainer" class="row"></div>

        <div id="loadingIndicator" class="text-center d-none">
            <div class="loading-spinner"></div>
            <p class="mt-2">Loading...</p>
        </div>

        <div id="noResults" class="text-center d-none">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No QR codes found.
            </div>
        </div>

    </div>
</div>

<script>
let allDevices = [];
let filteredDevices = [];
let currentViewMode = 'grid';
let selectedDevices = new Set();
let qrCodeInstances = [];

async function loadDevices() {
    showLoading(true);
    try {
        const companyId = '<?php echo $companyId; ?>';
        const response = await fetch(`/LM/datafetcher/loadcheckingdata.php?action=loaddevice&company=${encodeURIComponent(companyId)}`);
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const devices = await response.json();
        if (devices.error) throw new Error(devices.error);
        
        allDevices = Array.isArray(devices) ? devices : [];
        updateStatistics();
        populateSiteFilter();
        filterQRcodes();
        showNotification(`Loaded ${allDevices.length} devices`, 'success');
    } catch (err) {
        showNotification('Failed to load devices', 'error');
    } finally {
        showLoading(false);
    }
}

function updateStatistics() {
    document.getElementById('totalCount').textContent = allDevices.length;
    const uniqueSites = [...new Set(allDevices.map(d => d.SITE_ID).filter(Boolean))];
    document.getElementById('siteCount').textContent = uniqueSites.length;
}

function populateSiteFilter() {
    const sites = [...new Set(allDevices.map(d => d.SITE_ID).filter(Boolean))].sort();
    const select = document.getElementById('siteFilter');
    select.innerHTML = '<option value="">All Sites</option>';
    sites.forEach(site => {
        const option = document.createElement('option');
        option.value = site;
        option.textContent = site;
        select.appendChild(option);
    });
}

function filterQRcodes() {
    const selectedSite = document.getElementById('siteFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const sortBy = document.getElementById('sortBy').value;
    
    let filtered = [...allDevices];
    if (selectedSite) filtered = filtered.filter(d => d.SITE_ID === selectedSite);
    if (searchTerm) filtered = filtered.filter(d => (d.SERIAL || '').toLowerCase().includes(searchTerm));
    
    switch(sortBy) {
        case 'serial_asc': filtered.sort((a,b) => (a.SERIAL||'').localeCompare(b.SERIAL||'')); break;
        case 'serial_desc': filtered.sort((a,b) => (b.SERIAL||'').localeCompare(a.SERIAL||'')); break;
        case 'site_asc': filtered.sort((a,b) => (a.SITE_ID||'').localeCompare(b.SITE_ID||'')); break;
    }
    
    filteredDevices = filtered;
    document.getElementById('displayCount').textContent = filteredDevices.length;
    displayQRCodes(filteredDevices);
}

function displayQRCodes(devices) {
    const container = document.getElementById('qrContainer');
    const noResults = document.getElementById('noResults');
    
    if (!devices || devices.length === 0) {
        container.innerHTML = '';
        noResults.classList.remove('d-none');
        return;
    }
    
    noResults.classList.add('d-none');
    container.innerHTML = '';
    qrCodeInstances = [];
    
    devices.forEach((device, index) => {
        const qrValue = device.SERIAL || device.NUMBER || device.LINEID || `DEV-${index + 1}`;
        const isSelected = selectedDevices.has(qrValue);
        
        const cardHtml = `
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                <div class="qr-card" data-serial="${escapeHtml(qrValue)}" data-device-index="${index}">
                    <div class="card-actions no-print">
                        <button class="card-action-btn" onclick="saveCardAsImage(${index}, event)" title="Save as Wallpaper">
                            <i class="fas fa-image"></i>
                        </button>
                        <button class="card-action-btn" onclick="toggleSelectDevice('${escapeHtml(qrValue)}', event)">
                            <i class="fas ${isSelected ? 'fa-check-square text-success' : 'fa-square'}"></i>
                        </button>
                    </div>
                    <div class="qr-card-content">
                        <div id="qrcode-${index}" class="qr-container"></div>
                        <div class="qr-value">${escapeHtml(qrValue)}</div>
                        ${device.SITE_ID ? `<div class="site-badge">📍 ${escapeHtml(device.SITE_ID)}</div>` : ''}
                    </div>
                </div>
            </div>`;
        
        container.insertAdjacentHTML('beforeend', cardHtml);
        
        setTimeout(() => {
            const qrContainer = document.getElementById(`qrcode-${index}`);
            if (qrContainer) {
                new QRCode(qrContainer, {
                    text: qrValue,
                    width: 150,
                    height: 150,
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        }, 50);
    });
}

// CREATE WALLPAPER - WITH EXTRA PADDING TO PREVENT ZOOM CROPPING
function createWallpaperCard(device) {
    const qrValue = device.SERIAL || device.NUMBER || device.LINEID || 'DEVICE';
    const currentDate = new Date().toLocaleDateString('en-PH');
    
    const card = document.createElement('div');
    card.className = 'qr-card-for-image';
    card.style.position = 'absolute';
    card.style.left = '-9999px';
    card.style.top = '-9999px';
    
    let detailsHtml = '';
    
    if (device.SERIAL) {
        detailsHtml += `<div class="detail-row"><i class="fas fa-hashtag"></i> SERIAL: ${escapeHtml(device.SERIAL)}</div>`;
    }
    if (device.BRAND) {
        detailsHtml += `<div class="detail-row"><i class="fas fa-trademark"></i> Brand: ${escapeHtml(device.BRAND)}</div>`;
    }
    if (device.MODEL) {
        detailsHtml += `<div class="detail-row"><i class="fas fa-microchip"></i> Model: ${escapeHtml(device.MODEL)}</div>`;
    }
    if (device.LAST_LOAD_HISTORY && device.LAST_LOAD_HISTORY !== '0000-00-00') {
        detailsHtml += `<div class="detail-row"><i class="fas fa-calendar-alt"></i> Last Load: ${escapeHtml(device.LAST_LOAD_HISTORY)}</div>`;
        if (device.LOAD_TERMS) {
            const nextLoad = new Date(device.LAST_LOAD_HISTORY);
            nextLoad.setMonth(nextLoad.getMonth() + parseInt(device.LOAD_TERMS));
            detailsHtml += `<div class="detail-row"><i class="fas fa-clock"></i> Next Load: ${nextLoad.toLocaleDateString()} (Every ${device.LOAD_TERMS} month/s)</div>`;
        }
    }
    if (device.BALANCE) {
        detailsHtml += `<div class="detail-row" style="font-weight: bold; color: #28a745;"><i class="fas fa-coins"></i> Balance: ₱${parseFloat(device.BALANCE).toLocaleString()}</div>`;
    }
    
    card.innerHTML = `
        <div class="company-header">
            <i class="fas fa-qrcode"></i> DEVICE MANAGEMENT SYSTEM
        </div>
        <div id="temp-qr-${Date.now()}" class="qr-container"></div>
        <div class="qr-value">
            <strong>${escapeHtml(qrValue)}</strong>
        </div>
        ${device.SITE_ID ? `<div class="site-badge"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(device.SITE_ID)}</div>` : ''}
        ${device.PERSON_USING ? `<div class="assigned-to"><i class="fas fa-user-circle"></i> Assigned To: ${escapeHtml(device.PERSON_USING)}</div>` : ''}
        ${detailsHtml}
        <div class="footer-text">
            <i class="fas fa-qrcode"></i> Scan QR for Device Info<br>
 Generated: ${currentDate}
        </div>
    `;
    
    document.body.appendChild(card);
    
    const qrContainer = card.querySelector(`#temp-qr-${Date.now()}`);
    if (qrContainer) {
        new QRCode(qrContainer, {
            text: qrValue,
            width: 400,
            height: 400,
            correctLevel: QRCode.CorrectLevel.H
        });
    }
    
    return card;
}

async function saveCardAsImage(deviceIndex, event) {
    if (event) event.stopPropagation();
    const device = filteredDevices[deviceIndex];
    if (!device) return;
    
    showNotification('Generating wallpaper...', 'info');
    const captureCard = createWallpaperCard(device);
    
    try {
        await new Promise(resolve => setTimeout(resolve, 200));
        
        const canvas = await html2canvas(captureCard, {
            scale: 1.5,
            backgroundColor: '#ffffff',
            logging: false
        });
        
        const link = document.createElement('a');
        const safeName = (device.SERIAL || device.LINEID || 'device').replace(/[^a-zA-Z0-9_-]/g, '_');
        link.download = `${safeName}_wallpaper.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        showNotification('Wallpaper saved!', 'success');
    } catch (err) {
        showNotification('Failed: ' + err.message, 'error');
    } finally {
        document.body.removeChild(captureCard);
    }
}

async function downloadSelectedCardsAsImages() {
    if (selectedDevices.size === 0) {
        showNotification('Select at least one device', 'warning');
        return;
    }
    const devicesToSave = filteredDevices.filter(device => {
        const qrValue = device.SERIAL || device.NUMBER || device.LINEID;
        return selectedDevices.has(qrValue);
    });
    await saveCardsAsImagesBatch(devicesToSave);
}

async function downloadAllCardsAsImages() {
    if (filteredDevices.length === 0) {
        showNotification('No devices to save', 'warning');
        return;
    }
    await saveCardsAsImagesBatch(filteredDevices);
}

async function saveCardsAsImagesBatch(devices) {
    const zip = new JSZip();
    let successCount = 0;
    
    showProgressBar(true);
    updateProgressBar(0);
    showNotification(`Processing ${devices.length} wallpapers...`, 'info');
    
    for (let i = 0; i < devices.length; i++) {
        const device = devices[i];
        updateProgressBar(((i + 1) / devices.length) * 100);
        
        try {
            const captureCard = createWallpaperCard(device);
            document.body.appendChild(captureCard);
            await new Promise(resolve => setTimeout(resolve, 200));
            
            const canvas = await html2canvas(captureCard, {
                scale: 1.5,
                backgroundColor: '#ffffff',
                logging: false
            });
            
            const safeName = (device.SERIAL || device.LINEID || `device_${i + 1}`).replace(/[^a-zA-Z0-9_-]/g, '_');
            const base64Data = canvas.toDataURL('image/png').split(',')[1];
            zip.file(`${safeName}_wallpaper.png`, base64Data, { base64: true });
            
            successCount++;
            document.body.removeChild(captureCard);
        } catch (err) {
            console.error('Failed:', err);
        }
    }
    
    if (successCount > 0) {
        const content = await zip.generateAsync({ type: "blob" });
        saveAs(content, `Wallpapers_${new Date().toISOString().slice(0,10)}.zip`);
        showNotification(`Saved ${successCount} wallpapers!`, 'success');
    } else {
        showNotification('Failed to save wallpapers', 'error');
    }
    showProgressBar(false);
}

function toggleSelectDevice(serial, event) {
    event.stopPropagation();
    if (selectedDevices.has(serial)) {
        selectedDevices.delete(serial);
    } else {
        selectedDevices.add(serial);
    }
    updateSelectionUI();
    updateBatchActionsButton();
}

function selectAll() {
    filteredDevices.forEach(device => {
        const qrValue = device.SERIAL || device.NUMBER || device.LINEID;
        if (qrValue) selectedDevices.add(qrValue);
    });
    updateSelectionUI();
    updateBatchActionsButton();
}

function clearSelection() {
    selectedDevices.clear();
    updateSelectionUI();
    updateBatchActionsButton();
}

function updateSelectionUI() {
    document.querySelectorAll('.qr-card').forEach(card => {
        const serial = card.getAttribute('data-serial');
        const selectBtn = card.querySelector('.card-actions button:last-child i');
        if (serial && selectBtn) {
            selectBtn.className = selectedDevices.has(serial) ? 'fas fa-check-square text-success' : 'fas fa-square';
            card.style.border = selectedDevices.has(serial) ? '2px solid #28a745' : '1px solid #e0e0e0';
        }
    });
}

function updateBatchActionsButton() {
    const btn = document.querySelector('.batch-actions .btn-success');
    if (btn) btn.innerHTML = `<i class="fas fa-download"></i> Save Selected (${selectedDevices.size})`;
}

function setViewMode(mode) {
    currentViewMode = mode;
    filterQRcodes();
}

function showProgressBar(show) {
    const bar = document.getElementById('progressBar');
    if (show) bar.classList.remove('d-none');
    else bar.classList.add('d-none');
}

function updateProgressBar(percent) {
    const fill = document.querySelector('.progress-bar-custom');
    if (fill) fill.style.width = `${percent}%`;
}

async function uploadExcel() {
    const file = document.getElementById('excelFile').files[0];
    if (!file) return showNotification('Select Excel file', 'warning');
    
    showLoading(true);
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const workbook = XLSX.read(e.target.result, { type: 'array' });
            const jsonData = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], { header: 1 });
            const newDevices = [];
            for (let i = 1; i < jsonData.length; i++) {
                if (jsonData[i] && jsonData[i][0]) {
                    newDevices.push({
                        SERIAL: jsonData[i][0].toString().trim(),
                        SITE_ID: (jsonData[i][1] || '').toString().trim(),
                        PERSON_USING: (jsonData[i][2] || '').toString().trim()
                    });
                }
            }
            allDevices = [...newDevices, ...allDevices];
            updateStatistics();
            populateSiteFilter();
            filterQRcodes();
            showNotification(`Added ${newDevices.length} devices`, 'success');
        } catch (err) {
            showNotification('Excel error', 'error');
        } finally {
            showLoading(false);
        }
    };
    reader.readAsArrayBuffer(file);
}

function printQRCodes() {
    window.print();
}

function showLoading(show) {
    const loader = document.getElementById('loadingIndicator');
    if (show) loader.classList.remove('d-none');
    else loader.classList.add('d-none');
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `toast-notification alert alert-${type === 'error' ? 'danger' : 'success'}`;
    notification.innerHTML = `${message}<button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>`;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function refreshDevices() { location.reload(); }
function clearAllFilters() {
    document.getElementById('siteFilter').value = '';
    document.getElementById('searchInput').value = '';
    filterQRcodes();
}
function downloadTemplate() {
    const ws = XLSX.utils.aoa_to_sheet([['SERIAL_NUMBER', 'SITE_ID', 'PERSON_USING'], ['DEV001', 'SITE_A', 'John Doe']]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template');
    XLSX.writeFile(wb, 'QR_Template.xlsx');
}

document.querySelector('.custom-file-input')?.addEventListener('change', function(e) {
    const label = document.querySelector('.custom-file-label');
    if (label) label.textContent = e.target.files[0]?.name || 'Choose file';
});

window.onload = loadDevices;
</script>

</body>
</html>