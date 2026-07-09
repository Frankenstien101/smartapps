<?php
require_once "./DB/dbcon.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['Role'] != 'ADMIN') {
    header("Location: /anubis/login.php");
    exit();
}

// Helper function to safely escape output
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Handle device operations
$message = '';
$message_type = '';

// Add Device
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = $_POST['name'] ?? '';
        $uniqueid = $_POST['uniqueid'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $model = $_POST['model'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $category = $_POST['category'] ?? '';
        $company = $_POST['company'] ?? '';
        $site = $_POST['site'] ?? '';
        $vehicle_type = $_POST['vehicle_type'] ?? '';
        $fuel_consumption = $_POST['fuel_consumption'] ?? 0;
        
        try {
            $sql = "INSERT INTO tc_devices (name, uniqueid, phone, model, contact, category, company, site, vehicle_type, fuel_consumption_liter_per_km, status, disabled) 
                    VALUES (:name, :uniqueid, :phone, :model, :contact, :category, :company, :site, :vehicle_type, :fuel_consumption, 1, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':uniqueid' => $uniqueid,
                ':phone' => $phone,
                ':model' => $model,
                ':contact' => $contact,
                ':category' => $category,
                ':company' => $company,
                ':site' => $site,
                ':vehicle_type' => $vehicle_type,
                ':fuel_consumption' => $fuel_consumption
            ]);
            $message = "Device added successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error adding device: " . $e->getMessage();
            $message_type = "danger";
        }
    }
    
    // Update Device
    elseif ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'] ?? '';
        $uniqueid = $_POST['uniqueid'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $model = $_POST['model'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $category = $_POST['category'] ?? '';
        $company = $_POST['company'] ?? '';
        $site = $_POST['site'] ?? '';
        $vehicle_type = $_POST['vehicle_type'] ?? '';
        $fuel_consumption = $_POST['fuel_consumption'] ?? 0;
        $status = $_POST['status'] ?? 1;
        
        try {
            $sql = "UPDATE tc_devices SET 
                    name = :name,
                    uniqueid = :uniqueid,
                    phone = :phone,
                    model = :model,
                    contact = :contact,
                    category = :category,
                    company = :company,
                    site = :site,
                    vehicle_type = :vehicle_type,
                    fuel_consumption_liter_per_km = :fuel_consumption,
                    status = :status
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':uniqueid' => $uniqueid,
                ':phone' => $phone,
                ':model' => $model,
                ':contact' => $contact,
                ':category' => $category,
                ':company' => $company,
                ':site' => $site,
                ':vehicle_type' => $vehicle_type,
                ':fuel_consumption' => $fuel_consumption,
                ':status' => $status,
                ':id' => $id
            ]);
            $message = "Device updated successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error updating device: " . $e->getMessage();
            $message_type = "danger";
        }
    }
    
    // Delete Device
    elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        try {
            $sql = "DELETE FROM tc_devices WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            $message = "Device deleted successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error deleting device: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Get all devices
$sql_devices = "SELECT * FROM tc_devices ORDER BY name";
$stmt_devices = $conn->prepare($sql_devices);
$stmt_devices->execute();
$devices = $stmt_devices->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .devices-container {
        padding: 20px;
        background: transparent;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .page-header h2 {
        color: #d4af37;
        font-size: 24px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-gold {
        background: linear-gradient(135deg, #d4af37, #b8941f);
        border: none;
        color: #000;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-gold:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        color: #000;
    }
    
    .btn-outline-gold {
        background: transparent;
        border: 1px solid rgba(212, 175, 55, 0.5);
        color: #d4af37;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-outline-gold:hover {
        background: rgba(212, 175, 55, 0.1);
        border-color: #d4af37;
        color: #d4af37;
    }
    
    .btn-outline-danger {
        background: transparent;
        border: 1px solid rgba(220, 53, 69, 0.5);
        color: #dc3545;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-outline-danger:hover {
        background: rgba(220, 53, 69, 0.1);
        border-color: #dc3545;
        color: #dc3545;
    }
    
    .devices-table {
        background: rgba(10, 10, 15, 0.95);
        border-radius: 12px;
        border: 1px solid rgba(212, 175, 55, 0.2);
        overflow-x: auto;
    }
    
    .devices-table table {
        width: 100%;
        color: #e0e0e0;
        background: transparent;
    }
    
    .devices-table th {
        background: rgba(212, 175, 55, 0.1);
        color: #d4af37;
        padding: 15px;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.2);
    }
    
    .devices-table td {
        padding: 12px 15px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        font-size: 13px;
        background: transparent;
        color: #e0e0e0;
    }
    
    .devices-table tr {
        background: transparent;
    }
    
    .devices-table tr:hover {
        background: rgba(212, 175, 55, 0.05);
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .status-active {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }
    
    .status-inactive {
        background: rgba(220, 53, 69, 0.2);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    /* Modal Styling */
    .modal-custom .modal-content {
        background: #0f0f0f;
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 12px;
        color: #e0e0e0;
    }
    
    .modal-custom .modal-header {
        border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        padding: 15px 20px;
    }
    
    .modal-custom .modal-header h5 {
        color: #d4af37;
    }
    
    .modal-custom .modal-body {
        padding: 20px;
    }
    
    .modal-custom .modal-footer {
        border-top: 1px solid rgba(212, 175, 55, 0.2);
        padding: 15px 20px;
    }
    
    .form-label {
        color: #d4af37;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .form-control, .form-select {
        background: #0a0a0a;
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        padding: 8px 12px;
    }
    
    .form-control:focus, .form-select:focus {
        background: #111;
        border-color: #d4af37;
        box-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
        color: #fff;
    }
    
    .form-control::placeholder {
        color: #666;
    }
    
    .alert-custom {
        border-radius: 10px;
        border: none;
        margin-bottom: 20px;
    }
    
    .search-box {
        background: #0a0a0a;
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 8px 15px;
        color: #fff;
        width: 250px;
    }
    
    .search-box:focus {
        border-color: #d4af37;
        outline: none;
    }
    
    .table-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .btn-close-white {
        filter: brightness(0) invert(1);
    }
    
    .btn-secondary {
        background: #2a2a2a;
        border: none;
        color: #ccc;
    }
    
    .btn-secondary:hover {
        background: #3a3a3a;
        color: #fff;
    }
    
    @media (max-width: 768px) {
        .devices-table {
            font-size: 12px;
        }
        
        .devices-table th,
        .devices-table td {
            padding: 8px 10px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .search-box {
            width: 100%;
        }
    }
</style>

<div class="devices-container">
    
    <div class="page-header">
        <h2>
            <i class="fas fa-microchip"></i> 
            Device Management
        </h2>
        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
            <i class="fas fa-plus"></i> Add New Device
        </button>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="table-actions">
        <input type="text" id="searchInput" class="search-box" placeholder="🔍 Search devices..." autocomplete="off">
        <select id="statusFilter" class="form-select" style="width: auto;">
            <option value="all">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>
    
    <div class="devices-table">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Site</th>
                    <th>Name</th>
                    <th>Tracker ID</th>
                    <th>Number</th>
                    <th>Model</th>
                    <th>Vehicle Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="devicesTableBody">
                <?php foreach ($devices as $device): ?>
                <tr data-status="<?= safe($device['status'] ?? '') ?>">
                    <td><?= safe($device['id'] ?? '') ?></td>
                    <td><?= safe($device['site'] ?? '') ?></td>
                    <td><?= safe($device['name'] ?? '') ?></td>
                    <td><code><?= safe($device['uniqueId'] ?? '') ?></code></td>
                    <td><?= safe($device['phone'] ?? '') ?></td>
                    <td><?= safe($device['model'] ?? '') ?></td>
                    <td><?= safe($device['vehicle_type'] ?? '') ?></td>
                    <td>
                        <span class="status-badge <?= ($device['status'] ?? 0) == 1 ? 'status-active' : 'status-inactive' ?>">
                            <?= ($device['status'] ?? 0) == 1 ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <button class="btn-outline-gold edit-btn" 
                                data-id="<?= safe($device['id'] ?? '') ?>"
                                data-name="<?= safe($device['name'] ?? '') ?>"
                                data-uniqueid="<?= safe($device['uniqueId'] ?? '') ?>"
                                data-phone="<?= safe($device['phone'] ?? '') ?>"
                                data-model="<?= safe($device['model'] ?? '') ?>"
                                data-contact="<?= safe($device['contact'] ?? '') ?>"
                                data-category="<?= safe($device['category'] ?? '') ?>"
                                data-company="<?= safe($device['company'] ?? '') ?>"
                                data-site="<?= safe($device['site'] ?? '') ?>"
                                data-vehicle_type="<?= safe($device['vehicle_type'] ?? '') ?>"
                                data-fuel_consumption="<?= safe($device['fuel_consumption_liter_per_km'] ?? '') ?>"
                                data-status="<?= safe($device['status'] ?? '') ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#editDeviceModal">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-outline-danger delete-btn" 
                                data-id="<?= safe($device['id']) ?>"
                                data-name="<?= safe($device['name']) ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteDeviceModal">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
         </table>
    </div>
</div>

<!-- Add Device Modal -->
<div class="modal fade modal-custom" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Add New Device
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Device Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tracker ID *</label>
                            <input type="text" class="form-control" name="uniqueid" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-control" name="model">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" name="contact">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" name="company" value="<?= safe($_SESSION['COMPANY'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site/Location</label>
                            <input type="text" class="form-control" name="site">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vehicle Type</label>
                            <select class="form-select" name="vehicle_type">
                                <option value="">Select Type</option>
                                <option value="Truck">Truck</option>
                                <option value="Van">Van</option>
                                <option value="Car">Car</option>
                                <option value="SUV">SUV</option>
                                <option value="Bus">Bus</option>
                                <option value="Motorcycle">Motorcycle</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fuel Consumption (L/km)</label>
                            <input type="number" step="0.01" class="form-control" name="fuel_consumption" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Add Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Device Modal -->
<div class="modal fade modal-custom" id="editDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Device
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Device Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tracker ID *</label>
                            <input type="text" class="form-control" name="uniqueid" id="edit_uniqueid" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-control" name="model" id="edit_model">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" name="contact" id="edit_contact">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" id="edit_category">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" name="company" id="edit_company" value="<?= safe($_SESSION['COMPANY'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site/Location</label>
                            <input type="text" class="form-control" name="site" id="edit_site">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vehicle Type</label>
                            <select class="form-select" name="vehicle_type" id="edit_vehicle_type">
                                <option value="">Select Type</option>
                                <option value="Truck">Truck</option>
                                <option value="Van">Van</option>
                                <option value="Car">Car</option>
                                <option value="SUV">SUV</option>
                                <option value="Bus">Bus</option>
                                <option value="Motorcycle">Motorcycle</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fuel Consumption (L/km)</label>
                            <input type="number" step="0.01" class="form-control" name="fuel_consumption" id="edit_fuel_consumption">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Update Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Device Modal -->
<div class="modal fade modal-custom" id="deleteDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete device <strong id="delete_name"></strong>?</p>
                    <p class="text-danger small">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search and Filter functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    filterTable();
});

document.getElementById('statusFilter').addEventListener('change', function() {
    filterTable();
});

function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#devicesTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.getAttribute('data-status');
        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = statusFilter === 'all' || status === statusFilter;
        
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Edit button data population
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id || '';
        document.getElementById('edit_name').value = this.dataset.name || '';
        document.getElementById('edit_uniqueid').value = this.dataset.uniqueid || '';
        document.getElementById('edit_phone').value = this.dataset.phone || '';
        document.getElementById('edit_model').value = this.dataset.model || '';
        document.getElementById('edit_contact').value = this.dataset.contact || '';
        document.getElementById('edit_category').value = this.dataset.category || '';
        document.getElementById('edit_company').value = <?= json_encode($_SESSION['COMPANY'] ?? '') ?>;
        document.getElementById('edit_site').value = this.dataset.site || '';
        document.getElementById('edit_vehicle_type').value = this.dataset.vehicle_type || '';
        document.getElementById('edit_fuel_consumption').value = this.dataset.fuel_consumption || 0;
        document.getElementById('edit_status').value = this.dataset.status || 1;
    });
});

// Delete button data population
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_id').value = this.dataset.id || '';
        document.getElementById('delete_name').textContent = this.dataset.name || '';
    });
});
</script>