<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Call Transaction | Ultra Compact</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: #f1f5f9;
            min-height: 100vh;
            padding: 16px;
        }

        /* ULTRA COMPACT PANEL - LEFT ALIGNED, MAX SPACE SAVINGS */
        .call-transaction-panel {
            max-width: 480px;
            width: 100%;
            background: transparent;
        }

        /* header - minimal */
        .panel-header {
            margin-bottom: 12px;
        }

        .badge-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .title-section h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0a0f1c;
            letter-spacing: -0.2px;
            line-height: 1.2;
        }

        .title-section p {
            font-size: 0.65rem;
            color: #475569;
            margin-top: 2px;
            font-weight: 500;
        }

        hr {
            margin: 8px 0 12px 0;
            border: none;
            height: 1px;
            background: #e2e8f0;
        }

        /* ULTRA COMPACT FORM - reduced gaps */
        .form-compact {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .field label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .field input, 
        .field select {
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 6px 10px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #0f172a;
            outline: none;
            transition: 0.15s;
        }

        .field input:focus, 
        .field select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.1);
        }

        .field input[readonly] {
            background: #f8fafc;
            color: #334155;
        }

        /* double row helper - tighter */
        .row-split {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .row-split .field {
            flex: 1;
        }

        /* phone field with inline button - more compact */
        .phone-action {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        .phone-action .field {
            flex: 3;
        }
        .update-btn {
            background: #1e293b;
            border: none;
            border-radius: 30px;
            padding: 6px 14px;
            font-weight: 600;
            font-size: 0.65rem;
            color: white;
            cursor: pointer;
            height: 34px;
            white-space: nowrap;
            margin-bottom: 2px;
        }
        .update-btn:hover {
            background: #0f172a;
        }

        /* confirm button - smaller but clear */
        .confirm-btn {
            background: #2563eb;
            border: none;
            border-radius: 30px;
            padding: 8px 16px;
            font-weight: 700;
            font-size: 0.8rem;
            color: white;
            cursor: pointer;
            width: 100%;
            margin-top: 6px;
            transition: 0.15s;
        }
        .confirm-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        /* tiny info */
        .info-tip {
            font-size: 0.55rem;
            text-align: left;
            margin-top: 10px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }

        /* extra small screen */
        @media (max-width: 500px) {
            body {
                padding: 10px;
            }
            .row-split {
                flex-direction: column;
                gap: 6px;
            }
            .phone-action {
                flex-direction: column;
                gap: 6px;
            }
            .update-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="call-transaction-panel">
    <!-- HEADER: minimal -->
    <div class="panel-header">
        <div class="badge-row">
            <div class="title-section">
                <h1>📞 CALL DETAILS</h1>
                <p>transaction & customer verification</p>
            </div>
        </div>
        <hr>
    </div>

    <!-- ULTRA COMPACT FORM - ready for more fields -->
    <div class="form-compact">
        <!-- Row: SELLER ID + SELLER NAME (1 line) -->
        <div class="row-split">
            <div class="field">
                <label>🆔 SELLER ID</label>
                <input type="text" id="seller_id" placeholder="Seller ID" value="SLR001" readonly>
            </div>
            <div class="field">
                <label>👤 SELLER NAME</label>
                <input type="text" id="seller_name" placeholder="Seller Name" value="FLORES, ARNAN" readonly>
            </div>
        </div>

        <!-- Row: STORE CODE + STORE NAME (1 line) -->
        <div class="row-split">
            <div class="field">
                <label>🏪 STORE CODE</label>
                <input type="text" id="store_code" placeholder="Store Code" value="SSV2A" readonly>
            </div>
            <div class="field">
                <label>📌 STORE NAME</label>
                <input type="text" id="store_name" placeholder="Store Name" value="SS VAN 2A" readonly>
            </div>
        </div>

        <!-- PHONE NUMBER with inline UPDATE button (update is for phone) -->
        <div class="phone-action">
            <div class="field">
                <label>📱 PHONE NUMBER</label>
                <input type="tel" id="phone_number" placeholder="+63 ..." value="+63 912 3456 789">
            </div>
            <button class="update-btn" id="updatePhoneBtn">⟳ UPDATE</button>
        </div>

        <!-- STATUS field (simple dropdown) -->
        <div class="field">
            <label>⚡ STATUS</label>
            <select id="status_select">
                <option value="Pending" selected>Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>

        <!-- INVOICE + AMOUNT row (split) -->
        <div class="row-split">
            <div class="field">
                <label>🧾 INVOICE</label>
                <input type="text" id="invoice" placeholder="INV-000" value="INV-240527">
            </div>
            <div class="field">
                <label>💰 AMOUNT</label>
                <input type="text" id="amount" placeholder="$0.00" value="$3,289.50">
            </div>
        </div>

        <!-- CUSTOMER SECTION (new - example of extra fields you can add) -->
        <div class="row-split">
            <div class="field">
                <label>🆔 CU ID</label>
                <input type="text" id="cu_id" placeholder="Customer ID" value="CU-8821">
            </div>
            <div class="field">
                <label>👤 CUSTOMER NAME</label>
                <input type="text" id="customer_name" placeholder="Customer Name" value="JUAN DELA CRUZ">
            </div>
        </div>

        <!-- START CALL main button -->
        <button class="confirm-btn" id="startCallBtn">📞 START CALL</button>

        <div class="info-tip">
            ⚡ UPDATE changes phone number | START CALL logs transaction data
        </div>
    </div>
</div>

<script>
    (function() {
        // Get DOM elements
        const updatePhoneBtn = document.getElementById('updatePhoneBtn');
        const startCallBtn = document.getElementById('startCallBtn');
        const phoneInput = document.getElementById('phone_number');
        const statusSelect = document.getElementById('status_select');
        
        // Helper: modern toast notification
        function showToast(message, isSuccess = true) {
            const toast = document.createElement('div');
            toast.innerText = message;
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.left = '50%';
            toast.style.transform = 'translateX(-50%)';
            toast.style.backgroundColor = isSuccess ? '#0f172a' : '#b91c1c';
            toast.style.color = 'white';
            toast.style.padding = '8px 18px';
            toast.style.borderRadius = '40px';
            toast.style.fontSize = '0.75rem';
            toast.style.fontWeight = '600';
            toast.style.backdropFilter = 'blur(8px)';
            toast.style.background = isSuccess ? 'rgba(15,23,42,0.92)' : 'rgba(185,28,28,0.92)';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            toast.style.zIndex = '999';
            toast.style.fontFamily = "'Inter', system-ui";
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.2s';
                setTimeout(() => toast.remove(), 250);
            }, 1800);
        }

        // UPDATE button: updates phone number and logs
        updatePhoneBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const newPhone = phoneInput.value.trim();
            if (newPhone) {
                console.log(`[PHONE UPDATE] ${newPhone} at ${new Date().toLocaleTimeString()}`);
                showToast(`✅ Phone updated: ${newPhone}`, true);
                // optional micro animation
                phoneInput.style.transform = 'scale(1.01)';
                setTimeout(() => phoneInput.style.transform = '', 120);
            } else {
                showToast(`⚠️ Please enter a phone number`, false);
            }
        });

        // START CALL: gathers all data and shows confirmation
        startCallBtn.addEventListener('click', (e) => {
            e.preventDefault();

            // Get all field values
            const sellerId = document.getElementById('seller_id').value.trim() || '—';
            const sellerName = document.getElementById('seller_name').value.trim() || '—';
            const storeCode = document.getElementById('store_code').value.trim() || '—';
            const storeName = document.getElementById('store_name').value.trim() || '—';
            const phoneNumber = phoneInput.value.trim() || '—';
            const status = statusSelect.value;
            const invoice = document.getElementById('invoice').value.trim() || '—';
            const amount = document.getElementById('amount').value.trim() || '$0';
            const cuId = document.getElementById('cu_id').value.trim() || '—';
            const customerName = document.getElementById('customer_name').value.trim() || '—';

            // Build call transaction object
            const callData = {
                seller_id: sellerId,
                seller_name: sellerName,
                store_code: storeCode,
                store_name: storeName,
                phone_number: phoneNumber,
                status: status,
                invoice: invoice,
                amount: amount,
                cu_id: cuId,
                customer_name: customerName,
                started_at: new Date().toISOString(),
                timestamp: new Date().toLocaleString()
            };

            // Log to console for debugging / backend integration
            console.log('📞 CALL TRANSACTION STARTED:', callData);
            
            // Show success toast with customer/seller summary
            showToast(`🎉 Call started: ${customerName} (${sellerName}) · ${amount}`, true);
            
            // Visual feedback on button
            startCallBtn.style.transform = 'scale(0.97)';
            setTimeout(() => startCallBtn.style.transform = '', 120);
            
            // =============================================
            // 🔧 YOU CAN ADD MORE FUNCTIONS HERE:
            // Example: send data to server via fetch
            /*
            fetch('/api/start_call.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(callData)
            })
            .then(response => response.json())
            .then(data => console.log('Server response:', data))
            .catch(err => console.error('Error:', err));
            */
            // =============================================
        });

        // Extra UX: pressing Enter on any input triggers START CALL
        const allInputs = document.querySelectorAll('input, select');
        allInputs.forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    startCallBtn.click();
                }
            });
        });

        // Optional: real-time phone format hint (basic)
        phoneInput.addEventListener('input', function(e) {
            let val = this.value.replace(/[^0-9+]/g, '');
            if (val.length > 0 && !val.startsWith('+')) {
                // just clean, no auto-format to avoid confusion
            }
        });
    })();
</script>

<!-- 
    🔧 EASY TO EXTEND: 
    - Add more fields below the "START CALL" button or inside .form-compact
    - Add new buttons, dropdowns, or toggles
    - Modify callData object in JavaScript to include new fields
    - Connect to real API by uncommenting fetch section
-->
</body>
</html>