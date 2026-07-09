<?php
// attendance.php

if (!isset($_SESSION['username'])) {
    header("Location: /taps/login.php");
    exit();
}

// Check if user has permission
if ($_SESSION['Role'] != 'ADMIN') {
    header("Location: /taps/login.php");
    exit();
}
?>

<div class="attendance-container" >

    <!-- Large Time Display -->
    <div class="time-display-wrapper">
        <div class="time-display">
            <div class="time-large" id="liveTime">00:00:00</div>
            <div class="date-large" id="liveDate"><?php echo date('l, F j, Y'); ?></div>
            <div class="time-status" id="timeStatus">System Ready</div>
        </div>
    </div>

    <!-- Camera + Buttons Row -->
    <div class="row g-4">

        <!-- Left: Camera -->
        <div class="col-lg-7">
            <div class="camera-wrapper">
                <div class="camera-container">
                    <video id="video" autoplay playsinline></video>
                    <canvas id="canvas"></canvas>

                    <!-- Face Frame Overlay -->
                    <div class="camera-overlay">
                        <div class="face-frame"></div>
                    </div>

                    <!-- Scan Status -->
                    <div class="scan-status">
                        <i class="fa fa-circle scanning"></i>
                        <span id="scanStatus">Waiting for face...</span>
                    </div>
                </div>

                <!-- Camera Controls -->
                <div class="camera-controls">
                    <button class="btn btn-camera" id="startCamera">
                        <i class="fa fa-play"></i> Start Camera
                    </button>
                    <button class="btn btn-camera" id="stopCamera">
                        <i class="fa fa-stop"></i> Stop
                    </button>
                    <button class="btn btn-capture" id="captureFace" disabled>
                        <i class="fa fa-camera"></i> Capture & Verify
                    </button>
                </div>

                <!-- Detected Employee Info -->
                <div class="employee-info" id="employeeInfo" style="display: none;">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar">
                                <i class="fa fa-user"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="emp-name" id="empName">John Doe</div>
                            <div class="emp-detail">
                                <span class="emp-id" id="empId">EMP-001</span>
                                <span class="ms-2" id="empDept">IT Department</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <span class="badge badge-status badge-in" id="empStatus">Verified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Time Buttons & Summary -->
        <div class="col-lg-5">

            <!-- Time Action Buttons -->
            <div class="time-buttons">
                <button class="time-btn" data-action="time-in" id="btnTimeIn">
                    <i class="fa fa-sign-in-alt"></i>
                    <div class="time-label">Time In</div>
                    <div class="time-value" id="timeInValue">--:--</div>
                </button>

                <button class="time-btn" data-action="break-in" id="btnBreakIn">
                    <i class="fa fa-coffee"></i>
                    <div class="time-label">Break In</div>
                    <div class="time-value" id="breakInValue">--:--</div>
                </button>

                <button class="time-btn" data-action="break-out" id="btnBreakOut">
                    <i class="fa fa-utensils"></i>
                    <div class="time-label">Break Out</div>
                    <div class="time-value" id="breakOutValue">--:--</div>
                </button>

                <button class="time-btn" data-action="time-out" id="btnTimeOut">
                    <i class="fa fa-sign-out-alt"></i>
                    <div class="time-label">Time Out</div>
                    <div class="time-value" id="timeOutValue">--:--</div>
                </button>

                <button class="time-btn" data-action="overtime-in" id="btnOvertimeIn">
                    <i class="fa fa-clock"></i>
                    <div class="time-label">Overtime In</div>
                    <div class="time-value" id="overtimeInValue">--:--</div>
                </button>

                <button class="time-btn" data-action="overtime-out" id="btnOvertimeOut">
                    <i class="fa fa-hourglass-end"></i>
                    <div class="time-label">Overtime Out</div>
                    <div class="time-value" id="overtimeOutValue">--:--</div>
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mt-3">
                <div class="col-4">
                    <div class="summary-card text-center">
                        <div class="summary-icon"><i class="fa fa-user-check"></i></div>
                        <div class="summary-number" id="totalPresent">0</div>
                        <div class="summary-label">Present</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="summary-card text-center">
                        <div class="summary-icon"><i class="fa fa-clock"></i></div>
                        <div class="summary-number" id="totalHours">0h</div>
                        <div class="summary-label">Hours</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="summary-card text-center">
                        <div class="summary-icon"><i class="fa fa-hourglass-half"></i></div>
                        <div class="summary-number" id="overtimeHours">0h</div>
                        <div class="summary-label">Overtime</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Log -->
    <div class="log-table">
        <div class="p-3 d-flex justify-content-between align-items-center border-bottom" style="border-color: var(--border);">
            <h5 class="mb-0" style="color: var(--maroon);">
                <i class="fa fa-list"></i> Today's Attendance Log
            </h5>
            <span style="color: var(--muted); font-size: 13px;">
                <i class="fa fa-refresh"></i> Auto-refresh
            </span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Action</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceLog">
                    <tr>
                        <td colspan="5" class="text-center" style="color: var(--muted);">
                            <i class="fa fa-info-circle"></i> No records for today yet
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Notification -->
<div class="notification" id="notification">
    <div class="d-flex align-items-center">
        <div class="notif-icon success" id="notifIcon">
            <i class="fa fa-check-circle"></i>
        </div>
        <div>
            <div class="notif-title" id="notifTitle">Success</div>
            <div class="notif-message" id="notifMessage">Action completed successfully</div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    /* ==========================================
       ATTENDANCE PAGE STYLES - FITS MAIN CONTENT
       ========================================== */

    :root {
        --maroon: #b84a4a;
        --dark-maroon: #8b2a2a;
        --light-maroon: #d46a6a;
        --bg: #0a0505;
        --panel: rgba(12, 8, 8, 0.95);
        --text: #d46a6a;
        --text-light: #e8a0a0;
        --muted: #996666;
        --border: rgba(184, 74, 74, 0.2);
    }

    .attendance-container {
        padding: 0;
        width: 100%;
        height: 100%;
        background: var(--bg);
        overflow: auto;
        position: relative;
    }

    /* ==========================================
       LARGE TIME DISPLAY - HIGHLIGHTED
       ========================================== */

    .time-display-wrapper {
        background: var(--panel);
        border-radius: 16px;
        border: 2px solid var(--border);
        padding: 20px 30px;
        margin-bottom: 25px;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 0 40px rgba(184, 74, 74, 0.05);
    }

    .time-display-wrapper::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at center, rgba(184, 74, 74, 0.03), transparent 70%);
        animation: timeGlow 4s ease-in-out infinite;
    }

    @keyframes timeGlow {
        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.1);
            opacity: 1;
        }
    }

    .time-display {
        position: relative;
        z-index: 1;
    }

    .time-large {
        font-size: 72px;
        font-weight: 700;
        color: var(--maroon);
        letter-spacing: 6px;
        text-shadow: 0 0 30px rgba(184, 74, 74, 0.2), 0 0 60px rgba(184, 74, 74, 0.1);
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }

    .time-large .blink-colon {
        animation: colonBlink 1s step-end infinite;
    }

    @keyframes colonBlink {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0.2;
        }
    }

    .date-large {
        font-size: 18px;
        color: var(--text-light);
        margin-top: 4px;
        letter-spacing: 2px;
        font-weight: 300;
    }

    .time-status {
        display: inline-block;
        margin-top: 10px;
        padding: 4px 20px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 500;
        background: rgba(184, 74, 74, 0.12);
        color: var(--text);
        border: 1px solid var(--border);
        letter-spacing: 1px;
    }

    .time-status.ready {
        color: #4caf50;
        border-color: rgba(76, 175, 80, 0.3);
        background: rgba(76, 175, 80, 0.08);
    }

    .time-status.scanning {
        color: #ffc107;
        border-color: rgba(255, 193, 7, 0.3);
        background: rgba(255, 193, 7, 0.08);
        animation: statusPulse 1s ease-in-out infinite;
    }

    .time-status.verified {
        color: var(--maroon);
        border-color: var(--border);
        background: rgba(184, 74, 74, 0.15);
    }

    @keyframes statusPulse {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* ==========================================
       CAMERA SECTION
       ========================================== */

    .camera-wrapper {
        background: var(--panel);
        border-radius: 16px;
        border: 1px solid var(--border);
        padding: 16px;
        position: relative;
        overflow: hidden;
    }

    .camera-container {
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        aspect-ratio: 4/3;
    }

    #video {
        width: 100%;
        height: 100%;
        display: block;
        background: #000;
        object-fit: cover;
    }

    #canvas {
        display: none;
    }

    .camera-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 2;
    }

    .face-frame {
        width: 160px;
        height: 160px;
        border: 3px solid var(--maroon);
        border-radius: 50%;
        box-shadow: 0 0 30px rgba(184, 74, 74, 0.3), inset 0 0 30px rgba(184, 74, 74, 0.1);
        animation: pulseFrame 2s ease-in-out infinite;
    }

    @keyframes pulseFrame {
        0%,
        100% {
            border-color: var(--maroon);
            box-shadow: 0 0 30px rgba(184, 74, 74, 0.3);
        }
        50% {
            border-color: var(--text-light);
            box-shadow: 0 0 50px rgba(184, 74, 74, 0.5);
        }
    }

    .scan-status {
        position: absolute;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.85);
        padding: 6px 18px;
        border-radius: 30px;
        color: var(--text);
        font-size: 12px;
        border: 1px solid var(--border);
        z-index: 3;
        backdrop-filter: blur(4px);
        white-space: nowrap;
    }

    .scan-status i {
        color: var(--maroon);
        margin-right: 6px;
    }

    .scan-status .scanning {
        animation: blink 1s infinite;
    }

    @keyframes blink {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0.2;
        }
    }

    .camera-controls {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .camera-controls .btn {
        padding: 8px 18px;
        border-radius: 30px;
        font-weight: 500;
        transition: 0.3s;
        font-size: 13px;
    }

    .btn-capture {
        background: var(--maroon);
        color: #fff;
        border: none;
    }

    .btn-capture:hover {
        background: var(--light-maroon);
        color: #fff;
        transform: scale(1.05);
    }

    .btn-capture:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
    }

    .btn-camera {
        background: transparent;
        color: var(--text);
        border: 1px solid var(--border);
    }

    .btn-camera:hover {
        background: rgba(184, 74, 74, 0.1);
        color: var(--text-light);
        border-color: var(--maroon);
    }

    /* ==========================================
       EMPLOYEE INFO
       ========================================== */

    .employee-info {
        background: rgba(184, 74, 74, 0.06);
        border-radius: 12px;
        border: 1px solid var(--border);
        padding: 14px 18px;
        margin-top: 12px;
    }

    .employee-info .avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: rgba(184, 74, 74, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: var(--maroon);
        border: 2px solid var(--border);
    }

    .employee-info .emp-name {
        color: var(--text);
        font-size: 17px;
        font-weight: 600;
    }

    .employee-info .emp-detail {
        color: var(--muted);
        font-size: 13px;
    }

    .employee-info .emp-id {
        color: var(--maroon);
        font-size: 12px;
        background: rgba(184, 74, 74, 0.1);
        padding: 2px 12px;
        border-radius: 20px;
        display: inline-block;
    }

    .badge-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-in {
        background: rgba(76, 175, 80, 0.2);
        color: #4caf50;
    }

    .badge-out {
        background: rgba(244, 67, 54, 0.2);
        color: #f44336;
    }

    .badge-break {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }

    .badge-overtime {
        background: rgba(33, 150, 243, 0.2);
        color: #2196f3;
    }

    /* ==========================================
       TIME BUTTONS
       ========================================== */

    .time-buttons {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .time-btn {
        padding: 14px 8px;
        border-radius: 14px;
        border: 2px solid var(--border);
        background: var(--panel);
        color: var(--text);
        font-weight: 600;
        transition: 0.3s;
        text-align: center;
        position: relative;
        overflow: hidden;
        font-size: 13px;
        cursor: pointer;
    }

    .time-btn:hover:not(.disabled) {
        border-color: var(--maroon);
        background: rgba(184, 74, 74, 0.08);
        transform: translateY(-2px);
    }

    .time-btn:active:not(.disabled) {
        transform: translateY(0px);
    }

    .time-btn i {
        font-size: 20px;
        display: block;
        margin-bottom: 4px;
        color: var(--maroon);
    }

    .time-btn .time-label {
        font-size: 12px;
        font-weight: 500;
    }

    .time-btn .time-value {
        font-size: 11px;
        color: var(--muted);
        margin-top: 2px;
        font-weight: 400;
    }

    .time-btn.active {
        border-color: var(--maroon);
        background: rgba(184, 74, 74, 0.15);
        box-shadow: 0 0 20px rgba(184, 74, 74, 0.08);
    }

    .time-btn.active::after {
        content: '✓';
        position: absolute;
        top: 4px;
        right: 8px;
        color: #4caf50;
        font-size: 14px;
    }

    .time-btn.disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }

    .time-btn.disabled:hover {
        transform: none;
        border-color: var(--border);
        background: var(--panel);
    }

    /* ==========================================
       SUMMARY CARDS
       ========================================== */

    .summary-card {
        background: var(--panel);
        border-radius: 14px;
        border: 1px solid var(--border);
        padding: 14px 10px;
        height: 100%;
        transition: 0.3s;
    }

    .summary-card:hover {
        border-color: var(--maroon);
    }

    .summary-card .summary-number {
        font-size: 26px;
        font-weight: 700;
        color: var(--text);
        line-height: 1.2;
    }

    .summary-card .summary-label {
        color: var(--muted);
        font-size: 12px;
        margin-top: 2px;
    }

    .summary-card .summary-icon {
        font-size: 22px;
        color: var(--maroon);
        opacity: 0.5;
        margin-bottom: 4px;
    }

    /* ==========================================
       LOG TABLE
       ========================================== */

    .log-table {
        background: var(--panel);
        border-radius: 16px;
        border: 1px solid var(--border);
        overflow: hidden;
        margin-top: 20px;
    }

    .log-table thead {
        background: rgba(184, 74, 74, 0.06);
    }

    .log-table thead th {
        color: var(--maroon);
        font-weight: 600;
        border-bottom: 2px solid var(--border);
        padding: 10px 14px;
        font-size: 13px;
    }

    .log-table tbody td {
        color: var(--text);
        border-bottom: 1px solid var(--border);
        padding: 10px 14px;
        vertical-align: middle;
        font-size: 13px;
    }

    .log-table tbody tr:hover {
        background: rgba(184, 74, 74, 0.04);
    }

    /* ==========================================
       NOTIFICATION
       ========================================== */

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: var(--panel);
        border: 2px solid var(--border);
        border-radius: 14px;
        padding: 14px 20px;
        min-width: 280px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
        transform: translateX(120%);
        transition: transform 0.4s ease;
        backdrop-filter: blur(10px);
    }

    .notification.show {
        transform: translateX(0);
    }

    .notification .notif-icon {
        font-size: 20px;
        margin-right: 12px;
    }

    .notification .notif-icon.success {
        color: #4caf50;
    }

    .notification .notif-icon.error {
        color: #f44336;
    }

    .notification .notif-icon.info {
        color: var(--maroon);
    }

    .notification .notif-title {
        font-weight: 600;
        color: var(--text);
        font-size: 14px;
    }

    .notification .notif-message {
        color: var(--muted);
        font-size: 12px;
    }

    /* ==========================================
       RESPONSIVE
       ========================================== */

    @media (max-width: 992px) {
        .time-large {
            font-size: 52px;
        }

        .time-buttons {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .time-large {
            font-size: 40px;
            letter-spacing: 3px;
        }

        .date-large {
            font-size: 15px;
        }

        .time-display-wrapper {
            padding: 14px 18px;
        }

        .time-buttons {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .time-btn {
            padding: 10px 6px;
            font-size: 11px;
        }

        .time-btn i {
            font-size: 16px;
        }

        .camera-container {
            aspect-ratio: 4/3;
        }

        .face-frame {
            width: 120px;
            height: 120px;
        }

        .notification {
            min-width: auto;
            right: 10px;
            left: 10px;
            top: 10px;
        }
    }

    @media (max-width: 480px) {
        .time-large {
            font-size: 30px;
        }

        .time-buttons {
            grid-template-columns: repeat(2, 1fr);
        }

        .time-display-wrapper {
            padding: 12px 14px;
        }

        .summary-card .summary-number {
            font-size: 20px;
        }

        .camera-controls .btn {
            font-size: 11px;
            padding: 6px 14px;
        }

        .scan-status {
            font-size: 10px;
            padding: 4px 12px;
            bottom: 10px;
        }
    }
</style>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

<script>
    // ==========================================
    // LIVE TIME DISPLAY
    // ==========================================

    function updateLiveTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('liveTime').innerHTML =
            `${hours}<span class="blink-colon">:</span>${minutes}<span class="blink-colon">:</span>${seconds}`;
    }

    // Update time every second
    updateLiveTime();
    setInterval(updateLiveTime, 1000);

    // Update date
    const now = new Date();
    document.getElementById('liveDate').textContent =
        now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // ==========================================
    // CAMERA & FACE RECOGNITION SIMULATION
    // ==========================================

    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('captureFace');
    const startBtn = document.getElementById('startCamera');
    const stopBtn = document.getElementById('stopCamera');
    const scanStatus = document.getElementById('scanStatus');
    const timeStatus = document.getElementById('timeStatus');

    let stream = null;
    let isCameraRunning = false;
    let isFaceDetected = false;
    let currentEmployee = null;

    // ==========================================
    // EMPLOYEE DATA (Simulated Database)
    // ==========================================

    const employees = [
        { id: 'EMP-001', name: 'John Doe', department: 'IT Department', avatar: 'JD' },
        { id: 'EMP-002', name: 'Jane Smith', department: 'HR Department', avatar: 'JS' },
        { id: 'EMP-003', name: 'Mike Johnson', department: 'Operations', avatar: 'MJ' },
        { id: 'EMP-004', name: 'Sarah Williams', department: 'Finance', avatar: 'SW' },
        { id: 'EMP-005', name: 'David Brown', department: 'IT Department', avatar: 'DB' }
    ];

    // ==========================================
    // TODAY'S ATTENDANCE DATA (Simulated)
    // ==========================================

    let attendanceData = [];
    let timeRecords = {};

    // ==========================================
    // CAMERA FUNCTIONS
    // ==========================================

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: 640, height: 480 }
            });
            video.srcObject = stream;
            await video.play();
            isCameraRunning = true;
            captureBtn.disabled = false;
            scanStatus.innerHTML = '<i class="fa fa-circle scanning"></i> Scanning for face...';
            timeStatus.textContent = 'Camera Active';
            timeStatus.className = 'time-status scanning';
            startBtn.disabled = true;
            stopBtn.disabled = false;
            showNotification('info', 'Camera Started', 'Camera is now active. Please look at the camera.');

            // Auto-scan after 3 seconds
            setTimeout(simulateFaceDetection, 3000);
        } catch (err) {
            showNotification('error', 'Camera Error', 'Unable to access camera. Please check permissions.');
            console.error('Camera error:', err);
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        video.srcObject = null;
        isCameraRunning = false;
        captureBtn.disabled = true;
        scanStatus.innerHTML = '<i class="fa fa-circle"></i> Camera stopped';
        timeStatus.textContent = 'Camera Off';
        timeStatus.className = 'time-status';
        startBtn.disabled = false;
        stopBtn.disabled = true;
        document.getElementById('employeeInfo').style.display = 'none';
        enableTimeButtons(false);
    }

    // ==========================================
    // FACE DETECTION SIMULATION
    // ==========================================

    function simulateFaceDetection() {
        if (!isCameraRunning) return;

        timeStatus.textContent = 'Scanning...';
        timeStatus.className = 'time-status scanning';

        const delay = 2000 + Math.random() * 3000;

        setTimeout(() => {
            if (!isCameraRunning) return;

            // 80% chance of detecting a face
            if (Math.random() < 0.8) {
                const emp = employees[Math.floor(Math.random() * employees.length)];
                currentEmployee = emp;
                isFaceDetected = true;

                document.getElementById('empName').textContent = emp.name;
                document.getElementById('empId').textContent = emp.id;
                document.getElementById('empDept').textContent = emp.department;
                document.getElementById('employeeInfo').style.display = 'block';
                document.getElementById('empStatus').textContent = 'Verified';
                document.getElementById('empStatus').className = 'badge badge-status badge-in';

                scanStatus.innerHTML = '<i class="fa fa-check-circle" style="color: #4caf50;"></i> Face detected: ' +
                emp.name;
                timeStatus.textContent = `Verified: ${emp.name}`;
                timeStatus.className = 'time-status verified';

                enableTimeButtons(true);
                showNotification('success', 'Face Verified', `Welcome ${emp.name}! You are now verified.`);

                addAttendanceLog(emp.name, emp.department, 'Face Scan', new Date().toLocaleTimeString(), 'Verified');

            } else {
                isFaceDetected = false;
                currentEmployee = null;
                document.getElementById('employeeInfo').style.display = 'none';
                scanStatus.innerHTML =
                '<i class="fa fa-exclamation-triangle" style="color: #f44336;"></i> No face detected. Please try again.';
                timeStatus.textContent = 'No face detected';
                timeStatus.className = 'time-status';
                enableTimeButtons(false);

                // Try again after 3 seconds
                setTimeout(simulateFaceDetection, 3000);
            }
        }, delay);
    }

    // ==========================================
    // BUTTON HANDLERS
    // ==========================================

    function enableTimeButtons(enabled) {
        const buttons = document.querySelectorAll('.time-btn');
        buttons.forEach(btn => {
            if (enabled) {
                btn.classList.remove('disabled');
                btn.disabled = false;
            } else {
                btn.classList.add('disabled');
                btn.disabled = true;
            }
        });
    }

    captureBtn.addEventListener('click', function() {
        if (!isCameraRunning) {
            showNotification('error', 'Camera Off', 'Please start the camera first.');
            return;
        }

        this.disabled = true;
        scanStatus.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Scanning...';
        timeStatus.textContent = 'Manual Scan...';
        timeStatus.className = 'time-status scanning';
        document.getElementById('employeeInfo').style.display = 'none';

        setTimeout(() => {
            simulateFaceDetection();
            this.disabled = false;
        }, 1500);
    });

    startBtn.addEventListener('click', startCamera);
    stopBtn.addEventListener('click', stopCamera);

    // ==========================================
    // TIME ACTION HANDLERS
    // ==========================================

    document.querySelectorAll('.time-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('disabled')) {
                showNotification('error', 'Not Verified', 'Please scan your face first.');
                return;
            }

            if (!currentEmployee) {
                showNotification('error', 'No Employee', 'Please scan your face first.');
                return;
            }

            const action = this.dataset.action;
            const now = new Date();
            const timeStr = now.toLocaleTimeString();

            const existing = attendanceData.find(
                entry => entry.employee === currentEmployee.name &&
                entry.action === action &&
                entry.date === now.toDateString()
            );

            if (existing) {
                showNotification('error', 'Already Done',
                    `${currentEmployee.name} already recorded ${action} today.`);
                return;
            }

            const record = {
                employee: currentEmployee.name,
                department: currentEmployee.department,
                action: action,
                time: timeStr,
                date: now.toDateString(),
                timestamp: now.getTime()
            };

            attendanceData.push(record);
            updateTimeButton(action, timeStr);
            addAttendanceLog(currentEmployee.name, currentEmployee.department, action, timeStr, 'Completed');
            updateSummary();

            const actionLabels = {
                'time-in': 'Time In',
                'time-out': 'Time Out',
                'break-in': 'Break In',
                'break-out': 'Break Out',
                'overtime-in': 'Overtime In',
                'overtime-out': 'Overtime Out'
            };

            showNotification('success', `${actionLabels[action] || action} Recorded`,
                `${currentEmployee.name} - ${timeStr}`);

            this.classList.add('active');
        });
    });

    // ==========================================
    // UPDATE UI FUNCTIONS
    // ==========================================

    function updateTimeButton(action, time) {
        const map = {
            'time-in': 'timeInValue',
            'time-out': 'timeOutValue',
            'break-in': 'breakInValue',
            'break-out': 'breakOutValue',
            'overtime-in': 'overtimeInValue',
            'overtime-out': 'overtimeOutValue'
        };

        const element = document.getElementById(map[action]);
        if (element) {
            element.textContent = time;
        }
        timeRecords[action] = time;
    }

    function addAttendanceLog(employee, department, action, time, status) {
        const tbody = document.getElementById('attendanceLog');

        const emptyRow = tbody.querySelector('td[colspan="5"]');
        if (emptyRow) {
            tbody.innerHTML = '';
        }

        const statusMap = {
            'Time In': 'badge-in',
            'Time Out': 'badge-out',
            'Break In': 'badge-break',
            'Break Out': 'badge-break',
            'Overtime In': 'badge-overtime',
            'Overtime Out': 'badge-overtime',
            'Face Scan': 'badge-in',
            'Completed': 'badge-in',
            'Verified': 'badge-in'
        };

        const statusClass = statusMap[action] || 'badge-in';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${employee}</strong></td>
            <td style="color: var(--muted);">${department}</td>
            <td><span class="badge badge-status ${statusClass}">${action}</span></td>
            <td style="color: var(--text);">${time}</td>
            <td><span class="badge badge-status ${status === 'Completed' || status === 'Verified' ? 'badge-in' : 'badge-out'}">${status}</span></td>
        `;

        tbody.prepend(row);

        while (tbody.children.length > 50) {
            tbody.removeChild(tbody.lastChild);
        }
    }

    function updateSummary() {
        const uniqueEmployees = new Set(attendanceData.map(d => d.employee));
        document.getElementById('totalPresent').textContent = uniqueEmployees.size;

        const timeIns = attendanceData.filter(d => d.action === 'time-in');
        const timeOuts = attendanceData.filter(d => d.action === 'time-out');

        let totalMinutes = 0;
        for (let i = 0; i < Math.min(timeIns.length, timeOuts.length); i++) {
            const inTime = new Date(timeIns[i].timestamp);
            const outTime = new Date(timeOuts[i].timestamp);
            totalMinutes += (outTime - inTime) / (1000 * 60);
        }

        const hours = Math.floor(totalMinutes / 60);
        const mins = Math.round(totalMinutes % 60);
        document.getElementById('totalHours').textContent = hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;

        const overtimeCount = attendanceData.filter(d =>
            d.action === 'overtime-in' || d.action === 'overtime-out'
        ).length;
        document.getElementById('overtimeHours').textContent = `${Math.floor(overtimeCount / 2)}h`;
    }

    // ==========================================
    // NOTIFICATION SYSTEM
    // ==========================================

    function showNotification(type, title, message) {
        const notification = document.getElementById('notification');
        const icon = document.getElementById('notifIcon');
        const titleEl = document.getElementById('notifTitle');
        const messageEl = document.getElementById('notifMessage');

        icon.className = 'notif-icon';

        if (type === 'success') {
            icon.classList.add('success');
            icon.innerHTML = '<i class="fa fa-check-circle"></i>';
        } else if (type === 'error') {
            icon.classList.add('error');
            icon.innerHTML = '<i class="fa fa-exclamation-circle"></i>';
        } else {
            icon.classList.add('info');
            icon.innerHTML = '<i class="fa fa-info-circle"></i>';
        }

        titleEl.textContent = title;
        messageEl.textContent = message;

        notification.classList.add('show');

        clearTimeout(window.notificationTimeout);
        window.notificationTimeout = setTimeout(() => {
            notification.classList.remove('show');
        }, 4000);
    }

    // ==========================================
    // INITIALIZATION
    // ==========================================

    enableTimeButtons(false);

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        setTimeout(startCamera, 1500);
    }

    // Auto-scan every 8 seconds
    setInterval(() => {
        if (isCameraRunning && !isFaceDetected) {
            simulateFaceDetection();
        }
    }, 8000);

    console.log('TAPS Attendance System loaded successfully!');
    console.log('👤 Employees loaded:', employees.length);
</script>