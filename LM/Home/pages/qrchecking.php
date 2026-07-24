<?php
if (!isset($_SESSION['Company_ID']) || empty($_SESSION['Company_ID'])) {
    header("Location: /LM/Home/verify.php");
    exit;
}

$companyId = $_SESSION['Company_ID'];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>QR Load Submission Scanner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <style>
        body { 
            background: #000408; 
            font-family: 'Segoe UI', sans-serif; 
        }
        #reader {
            width: 100%;
            max-width: 300px;
            height: 300px;
            margin: 20px auto;
            border: 4px solid #007bff;
            border-radius: 15px;
            overflow: hidden;
            background: #000;
            position: relative;
        }
        /* Camera switch button styling */
        .camera-switch-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
            backdrop-filter: blur(4px);
        }
        .camera-switch-btn:hover {
            background: rgba(0,123,255,0.8);
            transform: scale(1.05);
        }
        .scanner-container {
            position: relative;
            display: inline-block;
            width: 100%;
            text-align: center;
        }
        .result-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .device-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
        }
        .camera-status {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h5 class="mb-0">📷 Scan Device QR Code</h5>
                </div>
                <div class="card-body p-3">
                    <div class="scanner-container">
                        <div id="reader"></div>
                        <!-- Camera Switch Button -->
                        <button id="switchCameraBtn" class="camera-switch-btn" title="Switch Camera">
                            🔄
                        </button>
                    </div>
                    <div id="cameraStatus" class="camera-status">
                        📱 Using: Front Camera
                    </div>
                </div>
            </div>

            <!-- Result Area -->
            <div id="resultArea" class="result-box p-4 mt-4 d-none">
                <div id="status" class="text-center mb-3"></div>
                <div id="deviceInfo" class="device-info"></div>
                
                <div class="text-center mt-4">
                    <button id="scanAgainBtn" class="btn btn-outline-primary">Scan Another Device</button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Global variables
let html5QrCode = null;
let isScanning = false;
let currentCameraId = null;      // Store current camera ID
let availableCameras = [];        // Store all available cameras
let currentCameraIndex = 0;       // Track index for toggling

// Try to get available cameras and determine best front/back
async function getAvailableCameras() {
    try {
        // Check if enumerateDevices is supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            console.warn("enumerateDevices not supported");
            return [];
        }
        
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        
        console.log("Available cameras:", videoDevices);
        
        // Sort cameras: try to put back camera first, then front
        // Many devices label with "back", "rear", "environment", or "front", "user"
        const backCameras = [];
        const frontCameras = [];
        const otherCameras = [];
        
        for (const cam of videoDevices) {
            const label = cam.label.toLowerCase();
            if (label.includes('back') || label.includes('rear') || label.includes('environment')) {
                backCameras.push(cam);
            } else if (label.includes('front') || label.includes('user') || label.includes('face')) {
                frontCameras.push(cam);
            } else {
                otherCameras.push(cam);
            }
        }
        
        // Order: back cameras first, then front, then others
        let sortedCameras = [...backCameras, ...frontCameras, ...otherCameras];
        
        // If no labels to differentiate, just return all
        if (sortedCameras.length === 0 && videoDevices.length > 0) {
            sortedCameras = videoDevices;
        }
        
        return sortedCameras;
    } catch (err) {
        console.error("Error enumerating cameras:", err);
        return [];
    }
}

// Start scanner with specific camera ID or facing mode
async function startScannerWithCamera(cameraId = null, facingMode = "user") {
    // Safe stop if scanner exists
    if (html5QrCode) {
        try {
            await html5QrCode.stop();
        } catch(e) {
            console.log("Stop error (ignored):", e);
        }
        html5QrCode = null;
    }
    
    // Small delay to ensure cleanup
    await new Promise(resolve => setTimeout(resolve, 200));
    
    html5QrCode = new Html5Qrcode("reader");
    
    const config = {
        fps: 15,
        qrbox: { width: 280, height: 280 },
        aspectRatio: 1.0
    };
    
    let cameraConfig = {};
    
    if (cameraId && availableCameras.length > 0) {
        // Use specific camera ID
        cameraConfig = { deviceId: { exact: cameraId } };
        console.log("Using camera by deviceId:", cameraId);
    } else {
        // Use facing mode
        cameraConfig = { facingMode: facingMode };
        console.log("Using facingMode:", facingMode);
    }
    
    try {
        await html5QrCode.start(
            cameraConfig,
            config,
            onScanSuccess,
            (errorMessage) => {
                // Silent error handling for continuous scanning
                // Only log significant errors
                if (errorMessage && errorMessage.includes("NoMultiCameras")) {
                    console.debug("Camera error (non-critical):", errorMessage);
                }
            }
        );
        isScanning = true;
        
        // Update UI to show which camera is active
        if (cameraId && availableCameras.length > 0) {
            const activeCam = availableCameras.find(cam => cam.deviceId === cameraId);
            if (activeCam) {
                const label = activeCam.label.toLowerCase();
                if (label.includes('back') || label.includes('rear')) {
                    document.getElementById('cameraStatus').innerHTML = '📱 Using: Rear Camera 📸';
                } else if (label.includes('front')) {
                    document.getElementById('cameraStatus').innerHTML = '📱 Using: Front Camera 🤳';
                } else {
                    document.getElementById('cameraStatus').innerHTML = `📱 Using: ${activeCam.label.substring(0, 30) || 'Camera'}`;
                }
            } else {
                document.getElementById('cameraStatus').innerHTML = `📱 Using: Camera ${currentCameraIndex + 1}/${availableCameras.length}`;
            }
        } else {
            const modeText = facingMode === "environment" ? "Rear Camera 📸" : "Front Camera 🤳";
            document.getElementById('cameraStatus').innerHTML = `📱 Using: ${modeText}`;
        }
        
        console.log("✅ Camera started successfully");
    } catch (err) {
        console.error("Camera start failed:", err);
        document.getElementById('cameraStatus').innerHTML = `
            <span class="text-danger">⚠️ Camera error: ${err.message || 'Please allow permission'}</span>
        `;
        
        // Show error in result area if scanner not working
        const statusDiv = document.getElementById('status');
        if (statusDiv && !document.getElementById('resultArea').classList.contains('d-none')) {
            // Only show if result area is visible
        } else {
            // Create temporary alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger mt-2';
            alertDiv.innerHTML = 'Cannot access camera. Please allow camera permission and refresh.';
            document.querySelector('.card-body').appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }
    }
}

// Toggle camera function
async function toggleCamera() {
    if (!isScanning && html5QrCode) {
        // If not scanning but scanner exists, try to restart
        await initializeScannerWithToggle();
        return;
    }
    
    // If we have multiple cameras enumerated, cycle through them
    if (availableCameras.length >= 2) {
        // Cycle to next camera
        currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
        const nextCameraId = availableCameras[currentCameraIndex].deviceId;
        currentCameraId = nextCameraId;
        
        // Show switching status
        document.getElementById('cameraStatus').innerHTML = '🔄 Switching camera...';
        
        // Start scanner with the selected camera ID
        await startScannerWithCamera(nextCameraId);
    } 
    else if (availableCameras.length === 1) {
        // Only one camera available, toggle between facing modes as fallback
        const currentMode = document.getElementById('cameraStatus').innerHTML.includes('Front') ? 'front' : 'back';
        const newMode = currentMode === 'front' ? 'environment' : 'user';
        const modeDisplay = newMode === 'environment' ? 'Rear Camera 📸' : 'Front Camera 🤳';
        
        document.getElementById('cameraStatus').innerHTML = '🔄 Switching camera mode...';
        await startScannerWithCamera(null, newMode);
    }
    else {
        // No cameras enumerated properly, try to re-enumerate
        document.getElementById('cameraStatus').innerHTML = '🔄 Refreshing camera list...';
        await initializeScannerWithToggle();
    }
}

// Initialize scanner with camera enumeration and auto-select best camera
async function initializeScannerWithToggle() {
    // First, get available cameras
    availableCameras = await getAvailableCameras();
    
    if (availableCameras.length >= 2) {
        // Try to start with back camera if available, otherwise front
        const backCam = availableCameras.find(cam => 
            cam.label.toLowerCase().includes('back') || 
            cam.label.toLowerCase().includes('rear') ||
            cam.label.toLowerCase().includes('environment')
        );
        
        if (backCam) {
            currentCameraIndex = availableCameras.findIndex(cam => cam.deviceId === backCam.deviceId);
            currentCameraId = backCam.deviceId;
            await startScannerWithCamera(currentCameraId);
        } else {
            currentCameraIndex = 0;
            currentCameraId = availableCameras[0].deviceId;
            await startScannerWithCamera(currentCameraId);
        }
    } 
    else if (availableCameras.length === 1) {
        currentCameraId = availableCameras[0].deviceId;
        currentCameraIndex = 0;
        await startScannerWithCamera(currentCameraId);
    }
    else {
        // Fallback to facing mode if enumeration fails or no specific cameras found
        await startScannerWithCamera(null, "user");
    }
}

// QR Scan Success Handler
async function onScanSuccess(decodedText) {
    if (!isScanning) return;
    
    isScanning = false;
    
    // Stop scanner to prevent multiple scans
    if (html5QrCode) {
        try {
            await html5QrCode.stop();
        } catch(e) {
            console.log("Stop on success error:", e);
        }
    }
    
    // Process QR code
    await processQRCode(decodedText.trim());
}

// Process QR Code with backend submission
async function processQRCode(qrData) {
    const resultArea = document.getElementById('resultArea');
    const statusEl = document.getElementById('status');
    const infoEl = document.getElementById('deviceInfo');
    
    resultArea.classList.remove('d-none');
    
    statusEl.innerHTML = `
        <div class="spinner-border text-primary mb-2" role="status"></div>
        <p class="mb-1">Processing QR code...</p>
    `;
    
    try {
        const response = await fetch('/LM/datafetcher/loadcheckingdata.php?action=submit_by_qr', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                qr_code: qrData,
                company: "<?php echo $companyId; ?>"
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            statusEl.innerHTML = `<div class="alert alert-success">✅ SUBMITTED SUCCESSFULLY</div>`;
            infoEl.innerHTML = `
               <strong>👤 Person Using:</strong> ${escapeHtml(data.device?.PERSON_USING || 'N/A')}<br> 
               <strong>📞 Number:</strong> ${escapeHtml(data.device?.NUMBER || 'N/A')}<br>
               <strong>💰 Balance:</strong> ${escapeHtml(data.device?.BALANCE || 'N/A')}<br>
               <strong>⏱️ Last Load:</strong> ${escapeHtml(data.device?.LAST_LOAD_HISTORY || 'N/A')}<br>
               <strong>📅 Next Load:</strong> ${escapeHtml(data.device?.NEXT_LOAD_DATE || 'N/A')}<br>
               <hr>
               <small class="text-muted">Submitted at ${new Date().toLocaleString('en-PH')}</small>
            `;
        } else {
            statusEl.innerHTML = `<div class="alert alert-warning">⚠️ ${escapeHtml(data.message || 'Failed to submit')}</div>`;
            infoEl.innerHTML = `
                <strong>👤 Person Using:</strong> ${escapeHtml(data.device?.PERSON_USING || 'N/A')}<br>
                <strong>📞 Number:</strong> ${escapeHtml(data.device?.NUMBER || 'N/A')}<br>
                <strong>💰 Balance:</strong> ${escapeHtml(data.device?.BALANCE || 'N/A')}<br>
                <strong>⏱️ Last Load:</strong> ${escapeHtml(data.device?.LAST_LOAD_HISTORY || 'N/A')}<br>
                <strong>📅 Next Load:</strong> ${escapeHtml(data.device?.NEXT_LOAD_DATE || 'N/A')}<br>
            `;
        }
    } catch (error) {
        console.error("Network error:", error);
        statusEl.innerHTML = `<div class="alert alert-danger">⚠️ Network error. Please try again.</div>`;
        infoEl.innerHTML = `<small>Could not connect to server. Please check your connection.</small>`;
    }
    
    // Auto restart scanner after 10 seconds or user can click "Scan Another Device"
    setTimeout(() => {
        // Only auto-restart if result area is still visible and not manually closed
        if (!document.getElementById('resultArea').classList.contains('d-none')) {
            document.getElementById('resultArea').classList.add('d-none');
            restartScannerAfterDelay();
        }
    }, 10000);
}

// Helper function to restart scanner
async function restartScannerAfterDelay() {
    // Re-initialize with current camera settings
    if (availableCameras.length > 0 && currentCameraId) {
        await startScannerWithCamera(currentCameraId);
    } else if (availableCameras.length > 0 && availableCameras[currentCameraIndex]) {
        await startScannerWithCamera(availableCameras[currentCameraIndex].deviceId);
    } else {
        await startScannerWithCamera(null, "user");
    }
}

// Escape HTML to prevent XSS
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Scan Again Button handler
document.getElementById('scanAgainBtn').addEventListener('click', async () => {
    document.getElementById('resultArea').classList.add('d-none');
    
    // Restart scanner with same camera preference
    if (availableCameras.length > 0 && currentCameraId) {
        await startScannerWithCamera(currentCameraId);
    } else if (availableCameras.length > 0 && availableCameras[currentCameraIndex]) {
        await startScannerWithCamera(availableCameras[currentCameraIndex].deviceId);
    } else {
        await startScannerWithCamera(null, "user");
    }
});

// Camera Switch Button handler
document.getElementById('switchCameraBtn').addEventListener('click', async () => {
    // Disable button briefly to prevent multiple clicks
    const btn = document.getElementById('switchCameraBtn');
    btn.disabled = true;
    btn.style.opacity = '0.6';
    
    try {
        await toggleCamera();
    } catch (err) {
        console.error("Camera toggle error:", err);
        document.getElementById('cameraStatus').innerHTML = '<span class="text-danger">⚠️ Switch failed, retrying...</span>';
        setTimeout(() => {
            restartScannerAfterDelay();
        }, 1000);
    } finally {
        setTimeout(() => {
            btn.disabled = false;
            btn.style.opacity = '1';
        }, 1000);
    }
});

// Initialize on page load with enhanced camera discovery
window.onload = async () => {
    // Request camera permission first to populate device labels
    try {
        // Temporary stream to get permission and labels
        const tempStream = await navigator.mediaDevices.getUserMedia({ video: true });
        tempStream.getTracks().forEach(track => track.stop());
    } catch (err) {
        console.log("Permission needed or denied:", err);
    }
    
    // Get available cameras
    availableCameras = await getAvailableCameras();
    console.log("Found cameras:", availableCameras.length);
    
    if (availableCameras.length >= 2) {
        // Start with back camera if available
        const backCam = availableCameras.find(cam => 
            cam.label.toLowerCase().includes('back') || 
            cam.label.toLowerCase().includes('rear') ||
            cam.label.toLowerCase().includes('environment')
        );
        
        if (backCam) {
            currentCameraIndex = availableCameras.findIndex(cam => cam.deviceId === backCam.deviceId);
            currentCameraId = backCam.deviceId;
            await startScannerWithCamera(currentCameraId);
        } else {
            currentCameraIndex = 0;
            currentCameraId = availableCameras[0].deviceId;
            await startScannerWithCamera(currentCameraId);
        }
    } 
    else if (availableCameras.length === 1) {
        currentCameraId = availableCameras[0].deviceId;
        currentCameraIndex = 0;
        await startScannerWithCamera(currentCameraId);
    }
    else {
        // Fallback: try facing mode
        await startScannerWithCamera(null, "environment"); // Try back camera first
    }
};

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (html5QrCode) {
        html5QrCode.stop().catch(() => {});
    }
});
</script>

</body>
</html>