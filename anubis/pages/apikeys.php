<?php

$deviceApiKey = 'C4dE9fG2hJ7kL1mN8pQ3rS6tU0vWxY5z';
$deviceUrl = 'https://wish-yacht-logo-recognize.trycloudflare.com/anubis/API/api_devices.php?api_key=YOUR_API_KEY';

$routesApiKey = 'mQ7xR9pT2kV8nZ3bL5cD1aS6yH0wE4jF';
$routesUrl = 'https://wish-yacht-logo-recognize.trycloudflare.com/anubis/API/api_vehicle_locations.php?api_key=YOUR_API_KEY&from=DATE_FROM&to=DATE_TO';
?>

<style>
/* API Config Container - Fits into main content */
.api-config-section {
    padding: 20px;
}

.api-config-header {
    margin-bottom: 25px;
}

.api-config-header h3 {
    color: #d4af37;
    font-size: 20px;
    margin: 0 0 5px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.api-config-header p {
    color: #888;
    font-size: 13px;
    margin: 0;
}

/* Cards */
.api-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(212,175,55,0.15);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
}

.api-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(212,175,55,0.15);
}

.api-card-title i {
    font-size: 20px;
    color: #d4af37;
}

.api-card-title h4 {
    font-size: 16px;
    font-weight: 600;
    color: #d4af37;
    margin: 0;
}

.api-card-title span {
    font-size: 10px;
    background: rgba(212,175,55,0.15);
    padding: 2px 8px;
    border-radius: 20px;
    color: #d4af37;
}

/* Form Group */
.api-form-group {
    margin-bottom: 18px;
}

.api-form-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #ccc;
    margin-bottom: 6px;
}

.api-form-group label i {
    color: #d4af37;
    font-size: 11px;
}

.api-description {
    font-size: 10px;
    color: #666;
    margin-bottom: 8px;
    padding-left: 22px;
}

.api-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.api-input-wrapper input {
    flex: 1;
    padding: 10px 12px;
    background: #0a0a0a;
    border: 1px solid rgba(212,175,55,0.2);
    border-radius: 8px;
    color: #d4af37;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    transition: all 0.2s;
}

.api-input-wrapper input:focus {
    outline: none;
    border-color: rgba(212,175,55,0.5);
}

.api-copy-btn {
    background: rgba(212,175,55,0.1);
    border: 1px solid rgba(212,175,55,0.2);
    padding: 8px 12px;
    border-radius: 8px;
    color: #d4af37;
    cursor: pointer;
    transition: all 0.2s;
}

.api-copy-btn:hover {
    background: rgba(212,175,55,0.2);
    transform: scale(1.02);
}

/* Two column layout for larger screens */
.api-two-columns {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media (max-width: 900px) {
    .api-two-columns {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .api-config-section {
        padding: 15px;
    }
}

/* Toast notification */
.api-toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(34,197,94,0.9);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    z-index: 1000;
    animation: apiSlideIn 0.3s ease, apiFadeOut 2s ease forwards;
}

@keyframes apiSlideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes apiFadeOut {
    0% { opacity: 1; }
    70% { opacity: 1; }
    100% { opacity: 0; visibility: hidden; }
}
</style>

<div class="api-config-section">
    
    <div class="api-config-header">
        <h3>
            <i class="fas fa-key"></i> 
            API Configuration
        </h3>
        <p>Manage your API keys and endpoints for device and routes access</p>
    </div>

    <div class="api-two-columns">
        
        <!-- Card 1: Device API -->
        <div class="api-card">
            <div class="api-card-title">
                <i class="fas fa-microchip"></i>
                <h4>Device API</h4>
                <span>Vehicle Details</span>
            </div>

            <div class="api-form-group">
                <label><i class="fas fa-key"></i> API Key</label>
                <div class="api-description">Device API key for authenticating vehicle requests</div>
                <div class="api-input-wrapper">
                    <input type="text" id="deviceApiKey" value="<?= htmlspecialchars($deviceApiKey) ?>" readonly>
                    <button class="api-copy-btn" onclick="copyToClipboard('deviceApiKey')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="api-form-group">
                <label><i class="fas fa-link"></i> API URL</label>
                <div class="api-description">Base endpoint for device API calls</div>
                <div class="api-input-wrapper">
                    <input type="text" id="deviceUrl" value="<?= htmlspecialchars($deviceUrl) ?>" readonly>
                    <button class="api-copy-btn" onclick="copyToClipboard('deviceUrl')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 2: Routes API -->
        <div class="api-card">
            <div class="api-card-title">
                <i class="fas fa-route"></i>
                <h4>Routes API</h4>
                <span>Path & History</span>
            </div>

            <div class="api-form-group">
                <label><i class="fas fa-key"></i> API Key</label>
                <div class="api-description">Routes API key for accessing route and history data</div>
                <div class="api-input-wrapper">
                    <input type="text" id="routesApiKey" value="<?= htmlspecialchars($routesApiKey) ?>" readonly>
                    <button class="api-copy-btn" onclick="copyToClipboard('routesApiKey')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="api-form-group">
                <label><i class="fas fa-link"></i> API URL</label>
                <div class="api-description">Base endpoint for routes API calls</div>
                <div class="api-input-wrapper">
                    <input type="text" id="routesUrl" value="<?= htmlspecialchars($routesUrl) ?>" readonly>
                    <button class="api-copy-btn" onclick="copyToClipboard('routesUrl')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showToast('Copied to clipboard!');
    } catch (err) {
        navigator.clipboard.writeText(input.value).then(() => {
            showToast('Copied to clipboard!');
        }).catch(() => {
            showToast('Failed to copy');
        });
    }
    
    input.blur();
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'api-toast';
    toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 2000);
}
</script>