<!-- pages/CreditCreation.php -->
<h3 class="mt-0">Credit Creation</h3>

<div class="filter-container">
    <div class="card text-bg-light" style="font-size:11px;text-align:left;">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 mb-1">
                <button class="btn btn-primary btn-sm" id="newTransBtn"><i class="fas fa-plus"></i> New Transaction</button>
                <button type="button" class="btn btn-primary btn-sm ml-1" data-toggle="modal" data-target="#loadModal">
                    <i class="fas fa-folder-open"></i> Load Transaction
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="container-fluid p-0">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-12 mb-0">
                        <label for="transaction_id" class="mb-0">TRANSACTION ID</label>
                        <input type="text" id="transaction_id" class="form-control form-control-sm" placeholder="Auto generated" readonly>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12 mb-0">
                        <label for="creditor_id" class="mb-0">CREDITOR ID</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="creditor_id" style="height:30px;font-size:8px;" class="form-control" readonly>
                            <button class="btn btn-primary ml-1" type="button" data-toggle="modal" data-target="#selectcreditor" style="height:30px;font-size:8px;">Select</button>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-6 col-12 mb-0">
                        <label for="status" class="mb-0">STATUS</label>
                        <input type="text" style="width:100px;" id="status" style="height:25px;font-size:8px;" class="form-control form-control-sm" value="DRAFT" readonly>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12 mb-0">
                        <label for="charge_id" class="mb-0">CHARGE ID</label>
                        <input type="text" id="charge_id" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 col-sm-6 col-12 mb-0">
                        <label for="creditorname" class="mb-0">CREDITOR NAME</label>
                        <input type="text" id="creditorname" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="col-md-6 col-sm-6 col-12 mb-0">
                        <label for="paymenttype" class="mb-0">PAYMENT TYPE</label>
                        <select id="paymenttype" class="form-control form-control-sm" style="max-width:100px;font-size:10px;height:30px;">
                            <option value="Credit">CREDIT</option>
                            <option value="Cash">CASH</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card text-bg-light" style="max-width:100%;height:54vh;margin-bottom:.5rem;font-size:10px;">
    <div class="card-header">
        <div class="col-md-3 col-sm-6 col-12 mb-0 d-flex align-items-center">
            <span class="me-2" style="font-size:12px;">Insert Item:</span>
            <input type="text" id="insertitem" style="width:150px;height:25px;font-size:12px;" class="form-control form-control-sm ml-1">
            <button type="button" class="btn btn-primary btn-sm ms-2 ml-1" id="scanBtn" style="height:30px;font-size:13px;">
                <i class="fas fa-qrcode"></i>
            </button>
        </div>
    </div>
 <div class="card-body p-0" style="max-height:55vh; overflow:hidden;">
    <div class="table-responsive" style="max-height:55vh; overflow-y:auto;">
        <table id="lineitems" class="table table-striped table-hover table-bordered table-sm mb-0" style="font-size:10px; width:100%;">
            <!-- FROZEN HEADER -->
            <thead class="thead-dark" style="position:sticky; top:0; z-index:10; background:#343a40; color:white;">
                <tr>
                    <th style="width:10%; min-width:80px;">ITEM ID</th>
                    <th style="width:12%; min-width:90px;">BARCODE</th>
                    <th style="width:25%; min-width:150px;">DESCRIPTION</th>
                    <th style="width:5%;  min-width:60px;" class="text-center">INV QTY</th>
                    <th style="width:5%;  min-width:60px;" class="text-center">QTY TO CHARGE</th>
                    <th style="width:5%;  min-width:50px;" class="text-center">UOM</th>
                    <th style="width:8%; min-width:50px;" class="text-center">PRICE</th>
                    <th style="width:8%; min-width:50px;" class="text-center">AMOUNT</th>
                    <th style="width:2%; min-width:20px;" class="text-center">LESS</th>
                    <th style="width:9%; min-width:90px;" class="text-center">LESS TYPE</th>
                    <th style="width:10%; min-width:70px;" class="text-right">LESS AMOUNT</th>
                    <th style="width:12%; min-width:90px;" class="text-right">TOTAL</th>
                    <th style="width:8%;  min-width:60px;" class="text-center">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <!-- Filled by JS -->
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="text-right mb-0">
    <button class="btn btn-success mb-2" onclick="exportToExcel()">Process</button>
</div>


<div id="loading" style="
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;">
    <div style="text-align:center;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <div style="margin-top:10px;">Loading Data...</div>
    </div>
</div>

<script>

  
function showLoader() {
    const transactionEl = document.getElementById('transaction_id');
    const warehouseEl = document.getElementById('warehouse');
    const warehousecode = warehouseEl ? warehouseEl.value.trim() : "";

    // ✅ Show loader only if both checks passed
    document.getElementById("loading").style.display = "flex";
    return true;
}


function hideLoader() {
    document.getElementById("loading").style.display = "none";
}


   document.addEventListener("DOMContentLoaded", function () {
  const newTransBtn = document.getElementById('newTransBtn');
  if (!newTransBtn) {
    alert("New Transaction button not found!");
    return;
  }

  newTransBtn.addEventListener('click', function (event) {
    event.preventDefault();

    // Get PHP session variables safely
    const companyId = <?php echo json_encode($_SESSION['COMPANY_ID'] ?? ''); ?>;
    const siteId = <?php echo json_encode($_SESSION['SITE_ID'] ?? ''); ?>;

    if (!companyId || !siteId) {
      alert('Session expired or missing. Please log in again.');
      window.location.href = '/login.php';
      return;
    }

    const baseURL = window.location.origin;
    const getTransURL = `${baseURL}/GC/datafetcher/transaction/creditcreation_data.php?action=get_new_transaction&company=${companyId}&siteid=${siteId}`;

    console.log("Fetching from:", getTransURL);

    // Modern fetch version
    const safeFetch = async (url) => {
      try {
        const response = await fetch(url);
        if (!response.ok) throw new Error("HTTP Error " + response.status);
        return await response.json();
      } catch (e) {
        console.warn("Fetch failed, trying XHR fallback...", e);
        // Fallback using XMLHttpRequest for older browsers
        return new Promise((resolve, reject) => {
          const xhr = new XMLHttpRequest();
          xhr.open("GET", url);
          xhr.onload = () => {
            if (xhr.status === 200) {
              try {
                resolve(JSON.parse(xhr.responseText));
              } catch (err) {
                reject(err);
              }
            } else reject(new Error("XHR Error " + xhr.status));
          };
          xhr.onerror = () => reject(new Error("XHR network error"));
          xhr.send();
        });
      }
    };

    safeFetch(getTransURL)
      .then(data => {
        if (!data || typeof data.count === 'undefined') {
          alert('No count value returned from server.');
          console.warn("Response data:", data);
          return;
        }

        const dttimeid = new Date().toISOString().slice(0, 19).replace(/[-:T]/g, '');
        const transaction_id = `TRN-${companyId}-${siteId}-${dttimeid}-${data.count}`;

        document.getElementById('transaction_id').value = transaction_id;
        document.getElementById('status').value = 'DRAFT';
        document.getElementById('charge_id').value = dttimeid;
        document.getElementById('creditor_id').value = '';
        document.getElementById('creditorname').value = '';
        document.getElementById('paymenttype').value = 'Credit';

        const insertURL = `${baseURL}/HomePage/datafetcher/transactions/Van_Loading_getdata.php?action=insertnewtrans&companyid=${companyId}&siteid=${siteId}&transactionid=${transaction_id}`;
        console.log("Inserting new transaction:", insertURL);

        return safeFetch(insertURL);
      })
      .then(insertResult => {
        if (insertResult && insertResult.success) {
          alert("✅ New Transaction Created Successfully!");
        } else if (insertResult) {
          alert("❌ Failed to insert transaction: " + (insertResult.error || 'Unknown error'));
        }
      })
      .catch(err => {
        alert("⚠️ Error during transaction creation: " + err.message);
        console.error(err);
      });
  });
});

document.addEventListener("DOMContentLoaded", function() {

    showLoader();

    loadcreditors();
    loaditems();

    hideLoader();
});
let loadedCreditors = []; // Keep a reference to the data (optional, for later use)

function loadcreditors() {
    const companyid = "<?php echo $_SESSION['COMPANY_ID'] ?? ''; ?>";
    const tbody = document.querySelector('#creditors tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

    fetch(`/GC/datafetcher/transaction/creditcreation_data.php?action=loadcreditors&company=${encodeURIComponent(companyid)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            loadedCreditors = data || [];
            tbody.innerHTML = ''; // Clear loader

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No creditors found.</td></tr>';
                return;
            }

            data.forEach(item => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td>${item.SITE_ID || ''}</td>
                    <td>${item.DEPARTMENT || ''}</td>
                    <td>${item.EMPLOYEE_ID || ''}</td>
                    <td>${item.EMPLOYEE_NAME || ''}</td>
                    <td class="text-right">
                        ${item.CURRENT_BALANCE 
                            ? parseFloat(item.CURRENT_BALANCE).toLocaleString('en-PH', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                              }) 
                            : '0.00'
                        }
                    </td>
                    <td>
                        <button class="btn btn-success btn-sm select-creditor-btn" 
                                style="height:30px;width:80px;font-size:9px;" 
                                title="Select Creditor">
                            <i class="fa fa-check"></i> Select
                        </button>
                    </td>
                `;

                // Attach data to the row for easy access
                tr.dataset.employeeId = item.EMPLOYEE_ID;
                tr.dataset.employeeName = item.EMPLOYEE_NAME;

                tbody.appendChild(tr);
            });

            // === ATTACH CLICK HANDLER TO ALL SELECT BUTTONS ===
            attachSelectCreditorHandlers();
        })
        .catch(err => {
            console.error('Error loading creditors:', err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading data.</td></tr>';
        });
}

/* ============================================================= */
/* === ATTACH CLICK HANDLER TO ALL "Select" BUTTONS === */
/* ============================================================= */
function attachSelectCreditorHandlers() {
    document.querySelectorAll('.select-creditor-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = this.closest('tr');

            const creditorId   = row.dataset.employeeId;
            const creditorName = row.dataset.employeeName;

            // === CORRECT FIELD IDs FROM YOUR HTML ===
            const creditorIdField   = document.getElementById('creditor_id');     // OK
            const creditorNameField = document.getElementById('creditorname');   // FIXED: was 'creditor_name'

            if (creditorIdField)   creditorIdField.value = creditorId;
            if (creditorNameField) creditorNameField.value = creditorName;

            // Trigger change events (useful for validation)
            [creditorIdField, creditorNameField].forEach(field => {
                if (field) field.dispatchEvent(new Event('change', { bubbles: true }));
            });

            // Close modal
            $('#selectcreditor').modal('hide');

            // Optional: Focus next field (e.g., insert item)
            document.getElementById('insertitem')?.focus();
        });
    });
}

 function formatCurrency(value) {
    return value ? Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
}


function loaditems() {
    const companyid = "<?php echo $_SESSION['COMPANY_ID'] ?? ''; ?>";
    const siteid = "<?php echo $_SESSION['SITE_ID'] ?? ''; ?>";

    const tbody = document.querySelector('#lineitems tbody');
    if (!tbody) return;

    tbody.innerHTML = ''; // Clear previous rows
  //  showLoader(); // Show loader before fetch

    fetch(`/GC/datafetcher/transaction/creditcreation_data.php?action=loaditems&company=${encodeURIComponent(companyid)}&siteid=${encodeURIComponent(siteid)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            loadedPOs = data;
            if (!data || data.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="11" class="text-center">No items found.</td>';
                tbody.appendChild(tr);
                return;
            }

            data.forEach((item, index) => {
    const tr = document.createElement('tr');

    tr.innerHTML = `
        <td>${item.ITEM_ID || ''}</td>
        <td>${item.BARCODE || ''}</td>
        <td>${item.DESCRIPTION || ''}</td>
        <td>${item.QTY || ''}</td>
        <td>
            <input type="number" min="0" max="${item.QTY || 0}" value="0"
                class="form-control form-control-sm qty-to-charge"
                style="width:60px; height:25px; font-size:10px;">
        </td>
        <td>
            <select class="form-control form-control-sm" style="max-width:100px;font-size:9px;height:25px;">
                <option value="PCS">PCS</option>
                <option value="PACK">PACK</option>
            </select> 
        </td>
        <td>${item.PRICE || 0}</td>
        <td class="price-total">${(item.PRICE * 0).toFixed(2)}</td> <!-- total cell -->
        <td>
            <input type="number" min="0" value="0"
                class="form-control form-control-sm discount"
                style="width:60px; height:25px; font-size:10px;">
        </td>
        <td>
            <select class="form-control form-control-sm" style="max-width:100px;font-size:9px;height:25px;">
                <option value="PERCENTAGE">PERCENTAGE</option>
                <option value="AMOUNT">AMOUNT</option>
            </select>
        </td>
    `;

    tbody.appendChild(tr);

    // --- Calculate price * qty dynamically ---
    const qtyInput = tr.querySelector('.qty-to-charge');
    const totalCell = tr.querySelector('.price-total');
    qtyInput.addEventListener('input', () => {
        const qty = parseFloat(qtyInput.value) || 0;
        const total = (item.PRICE || 0) * qty;
        totalCell.textContent = total.toFixed(2);
    });
});
        })
        .catch(err => {
            console.error('Error loading items:', err);
        })
        .finally(() => {
       //     hideLoader(); // Hide loader when done
        });
}


</script>

