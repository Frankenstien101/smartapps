<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title>Load Checking</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<!-- QR Code Scanner -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
    .card-body-scroll { overflow-y: auto; max-width: 100%; height: 600px; }
    table { table-layout: auto; width: 100%; border-collapse: collapse; }
    table th, table td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 4px 8px; }
    .table-container::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
    .table-container::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
    .table-container::-webkit-scrollbar-thumb:hover { background: #555; }
    .card { border: 1px solid #dee2e6; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px; }
    .card-header { background-color: #e9ecef; font-weight: 600; padding: 6px 10px; font-size: 9px; }
    .error-message { color: red; font-size: 9px; margin-top: 5px; }
    .success-message { color: green; font-size: 9px; margin-top: 5px; }
    @media (max-width: 768px) { 
        .card { width: 100% !important; } 
    }
    .modern-input {
        border: 1px solid #d1d9e0;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-size: 9.5px !important;
        height: 28px;
        padding: 4px 10px;
    }
    .modern-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        outline: none;
    }
    .form-check-input:checked {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }
    .card { transition: box-shadow 0.2s; }
    .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .text-muted { color: #6b7280 !important; }

    .checklist-radio {
        transform: scale(1.3);
        margin-top: 0.15rem;
        cursor: pointer;
    }
    .form-check {
        min-width: 60px;
    }
    .form-check-label {
        user-select: none;
        color: #495057;
    }
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    @media (max-width: 576px) {
        .d-flex.gap-5 {
            gap: 3rem !important;
        }
        .checklist-radio {
            transform: scale(1.4);
        }
    }

    .row-older-than-10months {
        background-color: #e60728 !important;
    }
    .row-older-than-10months:hover {
        background-color: #dd0319 !important;
    }
    .row-older-than-10months td {
        background-color: #ee092b;
        color: #eeeced ;
    }
    .row-older-than-10months:hover td {
        background-color: #8f0311;
    }

    .btn-submitted {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: white !important;
        font-weight: bold;
        cursor: default !important;
        pointer-events: none !important;
    }

    .column-toggle {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        margin-left: auto;
    }
    .column-toggle label {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 8.5px;
        margin: 0;
        cursor: pointer;
        user-select: none;
    }
    .column-toggle input[type="checkbox"] {
        margin: 0;
        width: 14px;
        height: 14px;
        cursor: pointer;
    }
    .table-hidden {
        display: none !important;
    }
    
    .repair-fields {
        display: none;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 6px;
        margin-top: 5px;
    }
    .repair-fields.show {
        display: block;
    }
    
    .btn-qr {
        background: #0745b8;
        border: none;
        color: white;
    }
    .btn-qr:hover {
        background: #023475;
        color: white;
    }
    
    #qr-reader {
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }
    #qr-reader video {
        border-radius: 12px;
    }
    .qr-scanner-container {
        text-align: center;
        padding: 20px;
    }

    /* Balance input edited state - stays for 5 days */
    .balance-input.edited {
        background-color: #cce5ff !important;
        border: 2px solid #004085 !important;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    
    .balance-input.edited:focus {
        background-color: #b8d4ff !important;
        border-color: #002752 !important;
        box-shadow: 0 0 0 3px rgba(0, 64, 133, 0.25);
    }
</style>
</head>
<body>

<h3>LOAD CHECKING TRANSACTION</h3>

<div class="card text-bg-light" style="max-width: 100%; height: 750px; margin-bottom: 0.5rem; font-size: 9px;">
    <div class="card-header d-flex align-items-center py-1 px-2" style="min-height: 32px; flex-wrap: wrap; gap: 8px;">
        <div class="d-flex align-items-center gap-2 flex-wrap" style="flex: 1; min-width: 0;">
            <div class="input-group input-group-sm" style="width: 220px;">
                <div class="input-group-prepend">
                    <span class="input-group-text" style="font-size: 9px; padding: 0 6px;">Site</span>
                </div>
                <select class="custom-select custom-select-sm" id="siteFilter" style="font-size: 9px; height: 24px; padding: 0 6px;">
                    <option value="">All Sites</option>
                </select>
            </div>

            <div class="input-group input-group-sm" style="width: 220px;">
                <input type="text" class="form-control border-start-0 border-end-0 ml-1" placeholder="Search..." id="searchInput"
                style="font-size: 9px; height: 24px; padding: 2px 6px;">
            </div>
            
            <button class="btn btn-qr btn-sm" onclick="openQRScanner()" title="Scan QR Code to find device">
                📷 SCAN QR
            </button>
            
        </div>

        <div class="column-toggle">
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="1" checked> SITE</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="2" checked> DEPT</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="3" checked> PRINCIPAL</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="4" checked> POSITION</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="5" checked> BRAND</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="6" checked> MODEL</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="7" checked> SERIAL</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="8" checked> DATE</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="9" checked> USER</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="10" checked> LAST LOAD</label>
            <label><input type="checkbox" class="col-toggle-checkbox" data-col="11" checked> STATUS</label>
        </div>
    </div>

    <div class="card-body card-body-scroll p-2" style="height: calc(100% - 32px); overflow: auto;">
        <table id="itemsTable" class="table table-striped table-hover table-bordered table-sm mb-0" style="font-size: 9px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>SITE</th>
                    <th>DEPARTMENT</th>
                    <th>PRINCIPAL</th>
                    <th>POSITION</th>
                    <th>BRAND</th>
                    <th>MODEL</th>
                    <th>SERIAL</th>
                    <th>DATE DEPLOYED</th>
                    <th>USER</th>
                    <th>NUMBER</th>
                    <th>DATA(GB)</th>
                    <th>CONSUMED</th>
                    <th>LAST LOAD</th>
                    <th>LOAD STATUS</th>
                    <th>DEVICE STATUS</th>
                    <th>REASON CODE</th>
                    <th>IT RECOMMENDATION</th>
                    <th>CHARGED TO</th>
                    <th>REMARKS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="table-error" class="error-message text-danger small p-1"></div>
    <div id="table-success" class="success-message text-success small p-1"></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" role="dialog" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 75%;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            
            <div class="modal-header border-0 bg-light py-3 px-4">
                <h5 class="modal-title font-weight-bold" id="editDeviceModalLabel" style="font-size: 14px; color: #2c3e50;">
                    Device Submission
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 1.4rem;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body px-4 pb-4 pt-2" style="font-size: 9.5px; background: #f9fafb;">
                <form id="editDeviceForm">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Site</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_site" name="SITE_ID">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Department</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_dept" name="DEPARTMENT">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Principal</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_principal" name="PRINCIPAL">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Position</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_position" name="POSITION">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Brand</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_brand" name="BRAND">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Model</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_model" name="MODEL">
                                    </div>
                                </div>

                                <div class="col-6">
                                    <label class="font-weight-medium text-muted small d-block" style="font-size:12px;">
                                        Last Load History <small class="text-primary">(editable)</small>
                                    </label>
                                    <input type="date" class="form-control form-control-sm modern-input text-center" 
                                           id="edit_last_load" name="LAST_LOAD_HISTORY">
                                    <small class="text-muted d-block mt-1" style="font-size:10px;">
                                        Change only if the recorded date is incorrect
                                    </small>
                                </div>

                                <div class="col-6">
                                    <label class="font-weight-medium text-muted small d-block" style="font-size:12px;">Load Status (current)</label>
                                    <input type="text" class="form-control form-control-sm modern-input text-center font-weight-bold" 
                                           id="edit_load_status" readonly style="color:white;">
                                </div>

                                <div class="col-12 mt-1 mb-2">
                                    <small class="text-muted" id="load_terms_explanation" style="font-size:10.5px; line-height:1.3;"></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">IMEI</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_imei" name="IMEI">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Serial</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_serial" name="SERIAL">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Date Deployed</label>
                                        <input type="date" class="form-control form-control-sm modern-input" id="edit_date" name="DATE_DEPLOYED">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Person Using</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_user" name="PERSON_USING">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Number</label>
                                        <input type="text" class="form-control form-control-sm modern-input" id="edit_number" name="NUMBER">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <label class="font-weight-medium text-muted small d-block" style="font-size:12px;">
                                                Data Balance <span class="text-primary small">(Current)</span>
                                            </label>
                                            <input type="number" step="0.01" 
                                                   class="form-control form-control-sm modern-input text-center" 
                                                   id="edit_data_left" name="DATA_LEFT" 
                                                   placeholder="e.g. 1.8" autofocus>
                                        </div>
                                        <div class="col-6">
                                            <label class="font-weight-medium text-muted small d-block" style="font-size:12px;">
                                                Data Consumed <span class="text-primary small">(Used)</span>
                                            </label>
                                            <input type="number" step="0.01" 
                                                   class="form-control form-control-sm modern-input text-center" 
                                                   id="edit_data_consumed" name="DATA_CONSUMED" 
                                                   placeholder="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- DEVICE STATUS & REASON CODE - One Row -->
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="font-weight-medium text-muted small" style="font-size:12px;">Device Status</label>
                                                <select class="form-control form-control-sm modern-input" id="edit_device_status" name="DEVICE_STATUS" onchange="populateReasonCodes(this.value)">
                                                    <option value="Good condition" selected>Good condition</option>
                                                    <option value="Business Risk">Business Risk</option>
                                                    <option value="Damaged for Repair">Damaged for Repair</option>
                                                    <option value="Damaged for Disposal">Damaged for Disposal</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="font-weight-medium text-muted small" style="font-size:12px;">Reason Code</label>
                                                <select class="form-control form-control-sm modern-input" id="edit_reason_code" name="REASON_CODE">
                                                    <option value="">Select Reason Code</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Repair Fields (shown when Damaged for Repair is selected) -->
                                <div class="col-12 repair-fields" id="repairFields">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="form-group mb-2">
                                                <label class="font-weight-medium text-muted small" style="font-size:12px;">Date Surrendered</label>
                                                <input type="date" class="form-control form-control-sm modern-input" id="edit_date_surrendered" name="DATE_SURRENDERED" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group mb-2">
                                                <label class="font-weight-medium text-muted small" style="font-size:12px;">Days to Repair</label>
                                                <input type="number" class="form-control form-control-sm modern-input" id="edit_days_to_repair" name="DAYS_TO_REPAIR" placeholder="e.g. 5">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group mb-2">
                                                <label class="font-weight-medium text-muted small" style="font-size:12px;">Temporary Device Serial</label>
                                                <input type="text" class="form-control form-control-sm modern-input" id="edit_temporary_device" name="TEMPORARY_DEVICE" placeholder="Serial ID">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- IT RECOMMENDATION -->
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">IT Recommendation</label>
                                        <select class="form-control form-control-sm modern-input" id="edit_it_recommendation" name="IT_RECOMMENDATION">
                                            <option value="">Select</option>
                                            <option value="FOR REPLACEMENT">FOR REPLACEMENT</option>
                                            <option value="FOR REPAIR">FOR REPAIR</option>
                                            <option value="OK">OK</option>
                                            <option value="OBSOLETE">OBSOLETE</option>
                                            <option value="MONITOR">MONITOR</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- CHARGED TO -->
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Charged To</label>
                                        <select class="form-control form-control-sm modern-input" id="edit_charged_to" name="CHARGED_TO">
                                            <option value="">Select</option>
                                            <option value="COMPANY">COMPANY</option>
                                            <option value="SELLER">SELLER</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-medium text-muted small" style="font-size:12px;">Remarks</label>
                                        <div style="position:relative;">
                                            <textarea class="form-control form-control-sm modern-input" 
                                                      id="edit_remarks" name="REMARKS" rows="2" autocomplete="off"></textarea>
                                            <div id="remarksAutocomplete" class="list-group" 
                                                 style="position:absolute; left:0; right:0; top:100%; z-index:1200; display:none; max-height:180px; overflow:auto; box-shadow:0 6px 18px rgba(0,0,0,0.12);"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm bg-white mb-0 mt-3" style="border-radius: 10px;">
                        <div class="card-body py-4 px-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-medium mb-1" style="font-size: 11px; color: #495057;">
                                            Data Usage Submitted?
                                        </label>
                                        <div class="d-flex align-items-center gap-5">
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="data_submitted" id="data_yes" value="Yes" checked>
                                                <label class="form-check-label" for="data_yes" style="font-size: 11px; cursor: pointer;">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="data_submitted" id="data_no" value="No">
                                                <label class="form-check-label" for="data_no" style="font-size: 11px; cursor: pointer;">No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="font-weight-medium mb-1" style="font-size: 11px; color: #495057;">
                                            Physically OK?
                                        </label>
                                        <div class="d-flex align-items-center gap-5">
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="physically_ok" id="phys_ok_yes" value="Yes" checked>
                                                <label class="form-check-label" for="phys_ok_yes" style="font-size: 11px; cursor: pointer;">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="physically_ok" id="phys_ok_no" value="No">
                                                <label class="form-check-label" for="phys_ok_no" style="font-size: 11px; cursor: pointer;">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-medium mb-1" style="font-size: 11px; color: #495057;">
                                            Games Installed / Used?
                                        </label>
                                        <div class="d-flex align-items-center gap-5">
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="games" id="games_yes" value="Yes">
                                                <label class="form-check-label" for="games_yes" style="font-size: 11px; cursor: pointer;">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="games" id="games_no" value="No" checked>
                                                <label class="form-check-label" for="games_no" style="font-size: 11px; cursor: pointer;">No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="font-weight-medium mb-1" style="font-size: 11px; color: #495057;">
                                            System Updated?
                                        </label>
                                        <div class="d-flex align-items-center gap-5">
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="system_updated" id="sys_upd_yes" value="Yes" checked>
                                                <label class="form-check-label" for="sys_upd_yes" style="font-size: 11px; cursor: pointer;">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input checklist-radio" type="radio" name="system_updated" id="sys_upd_no" value="No">
                                                <label class="form-check-label" for="sys_upd_no" style="font-size: 11px; cursor: pointer;">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-1 mb-0">
                                <label class="font-weight-medium mb-2" style="font-size: 11px; color: #495057;">
                                    Other Issues / Notes
                                </label>
                                <textarea class="form-control modern-input" id="other_issues" name="OTHER_ISSUES" rows="3"
                                          placeholder="Enter any additional observations or comments..."
                                          style="font-size: 11px; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="modal-footer border-0 bg-light py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnUpdateOnly">UPDATE ONLY</button>
                    <button type="button" class="btn btn-success btn-sm px-4 shadow-sm" id="btnSaveChanges">SET AS SUBMITTED</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Audit History Modal -->
<div class="modal fade" id="auditHistoryModal" tabindex="-1" role="dialog" aria-labelledby="auditHistoryLabel" aria-hidden="true" >
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="width: 90vw; max-width: 1400px;">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="auditHistoryLabel">
                    Change History – Device <span id="historyLineId"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3" style="font-size: 0.9rem;">
                <div id="historyLoading" class="text-center my-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted">Loading change history...</p>
                </div>
                <div id="historyEmpty" class="alert alert-info d-none text-center my-4">
                    No field-level changes have been recorded for this device yet.<br>
                </div>
                <div class="table-responsive">
                    <table id="historyTable" class="table table-sm table-striped table-hover d-none">
                        <thead class="table-light">
                            <tr>
                                <th style="width:18%">When</th>
                                <th style="width:14%">By</th>
                                <th style="width:12%">Action</th>
                                <th style="width:12%">Field</th>
                                <th style="width:18%">Old Value</th>
                                <th style="width:18%">New Value</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #6f42c1;">
                <h5 class="modal-title font-weight-bold" style="color: white;">
                    📷 Scan QR Code
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color: white;">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="qr-scanner-container">
                    <p class="text-muted small">Position the QR code in front of the camera to scan</p>
                    <div id="qr-reader"></div>
                    <div id="qr-reader-results" class="mt-3"></div>
                    <button class="btn btn-secondary btn-sm mt-3" onclick="stopQRScanner()">Stop Scanning</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// ============================================
// QR SCANNER VARIABLES
// ============================================
let html5QrCode = null;
let isScanning = false;

// ============================================
// REASON CODES BY DEVICE STATUS
// ============================================
const reasonCodesByStatus = {
    'Good condition': [
        'No issues reported',
        'Working properly',
        'Regular maintenance done',
        'Device functioning normally'
    ],
    'Business Risk': [
        'Operationally Obsolete',
        'Technologically Deprecated',
        'Security-Compromised (End-of-Life)',
        'Productivity Drain / Liability'
    ],
    'Damaged for Repair': [
        'Broken Screen "Display cracked"',
        'Broken Screen "Touch unresponsive',
        'Broken Screen "Bleeding LCD/OLED"',
        'Broken Screen "Heavy screen burn-in" ',
        'Broken Screen "Glass lifting"',
        'Battery degraded',
        'Port loose/damaged',
        'Speaker muffled',
        'Camera blurry',
        'Buttons jammed'
    ],
    'Damaged for Disposal': [
        'Swollen battery',
        'Severe liquid damage',
        'Board level failure',
        'Chassis bent/warped',
        'IC short circuit'
    ]
};

// ============================================
// QR SCANNER FUNCTIONS
// ============================================
function openQRScanner() {
    if (!loadedPOs.length) {
        alert('Please load device data first. The page loads automatically on refresh.');
        return;
    }
    
    $('#qrScannerModal').modal('show');
    
    setTimeout(() => {
        startQRScanner();
    }, 500);
}

function startQRScanner() {
    if (isScanning) return;
    
    const readerContainer = document.getElementById('qr-reader');
    const resultsContainer = document.getElementById('qr-reader-results');
    resultsContainer.innerHTML = '';
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        resultsContainer.innerHTML = '<div class="alert alert-danger">Camera not supported on this device.</div>';
        return;
    }
    
    try {
        html5QrCode = new Html5Qrcode("qr-reader");
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        );
        
        isScanning = true;
        resultsContainer.innerHTML = '<div class="text-success small">📷 Camera started. Scan a QR code...</div>';
    } catch (err) {
        console.error('QR Scanner error:', err);
        resultsContainer.innerHTML = '<div class="alert alert-danger">Failed to start camera: ' + err.message + '</div>';
    }
}

function stopQRScanner() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            document.getElementById('qr-reader-results').innerHTML = '<div class="text-muted small">Scanner stopped.</div>';
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }
}

function onScanSuccess(decodedText, decodedResult) {
    stopQRScanner();
    
    const resultsContainer = document.getElementById('qr-reader-results');
    resultsContainer.innerHTML = '<div class="text-success">✅ QR Code detected: <strong>' + decodedText + '</strong></div>';
    
    findDeviceByQR(decodedText);
    
    setTimeout(() => {
        $('#qrScannerModal').modal('hide');
    }, 1500);
}

function onScanError(errorMessage) {
    // Silent error handling
}

function findDeviceByQR(qrData) {
    const searchTerm = qrData.trim().toUpperCase();
    let foundIndex = -1;
    
    for (let i = 0; i < loadedPOs.length; i++) {
        const item = loadedPOs[i];
        const serial = (item.SERIAL || '').toUpperCase();
        const number = (item.NUMBER || '').toUpperCase();
        const lineid = (item.LINEID || '').toUpperCase();
        const imei = (item.IMEI || '').toUpperCase();
        
        if (serial === searchTerm || 
            number === searchTerm || 
            lineid === searchTerm || 
            imei === searchTerm ||
            serial.includes(searchTerm) ||
            number.includes(searchTerm)) {
            foundIndex = i;
            break;
        }
    }
    
    if (foundIndex !== -1) {
        // Highlight the row
        document.querySelectorAll('#itemsTable tbody tr').forEach((row, idx) => {
            if (idx === foundIndex) {
                row.style.backgroundColor = '#ffc107';
                row.style.transition = 'background-color 0.5s';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 3000);
            }
        });
        
        // Open edit modal for the found device
        selectDevice(foundIndex);
        
        document.getElementById('table-success').textContent = `✅ Device found: ${loadedPOs[foundIndex].SERIAL || loadedPOs[foundIndex].NUMBER}`;
        setTimeout(() => {
            document.getElementById('table-success').textContent = '';
        }, 5000);
    } else {
        document.getElementById('table-error').textContent = `❌ No device found with QR: ${qrData}`;
        setTimeout(() => {
            document.getElementById('table-error').textContent = '';
        }, 5000);
        
        alert(`No device found with QR code: ${qrData}\n\nMake sure the device is loaded in the list.`);
    }
}

// ============================================
// POPULATE REASON CODES
// ============================================
function populateReasonCodes(deviceStatus) {
    const reasonCodeSelect = document.getElementById('edit_reason_code');
    const codes = reasonCodesByStatus[deviceStatus] || [];
    reasonCodeSelect.innerHTML = '<option value="">Select Reason Code</option>';
    codes.forEach(code => {
        const option = document.createElement('option');
        option.value = code;
        option.textContent = code;
        reasonCodeSelect.appendChild(option);
    });
    
    const repairFields = document.getElementById('repairFields');
    if (deviceStatus === 'Damaged for Repair') {
        repairFields.classList.add('show');
    } else {
        repairFields.classList.remove('show');
        document.getElementById('edit_date_surrendered').value = '';
        document.getElementById('edit_days_to_repair').value = '';
        document.getElementById('edit_temporary_device').value = '';
    }
}

// ============================================
// MARK EDITED BALANCE FIELD - NOW LASTS 5 DAYS
// ============================================
function markAsEdited(input) {
    const originalValue = input.dataset.original || '';
    const currentValue = input.value.trim();
    
    if (currentValue !== originalValue) {
        input.classList.add('edited');
        // Save to localStorage that this balance was edited today
        saveBalanceEditStatus(input.dataset.id);
    } else {
        input.classList.remove('edited');
    }
}

function saveBalanceEditStatus(lineId) {
    if (!lineId) return;
    
    const today = new Date().toISOString().split('T')[0];
    const key = `balance_edited_${today}`;
    let editedData = JSON.parse(localStorage.getItem(key) || '{}');
    editedData[lineId] = true;
    localStorage.setItem(key, JSON.stringify(editedData));
}

// UPDATED: Check for edits in the last 5 days
function checkEditedBalances() {
    // Check for the last 5 days
    const today = new Date();
    const editedData = {};
    
    // Collect all edited balances from the last 5 days
    for (let i = 0; i < 5; i++) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        const key = `balance_edited_${dateStr}`;
        
        try {
            const data = JSON.parse(localStorage.getItem(key) || '{}');
            Object.assign(editedData, data);
        } catch (e) {
            // Ignore invalid JSON
        }
    }
    
    // Apply the edited class to matching inputs
    document.querySelectorAll('.balance-input').forEach(input => {
        const lineId = input.dataset.id;
        if (lineId && editedData[lineId]) {
            input.classList.add('edited');
        }
    });
}

// Optional: Clean up old entries older than 5 days
function cleanOldBalanceEdits() {
    const today = new Date();
    // Keep only the last 5 days
    for (let i = 5; i < 365; i++) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        const key = `balance_edited_${dateStr}`;
        localStorage.removeItem(key);
    }
}

// ============================================
// GLOBAL VARIABLES
// ============================================
let loadedPOs = [];

const COLUMN_VISIBILITY_KEY = 'loadchecking_column_visibility';

const columnMap = {
    '1':  { index: 1,  name: 'SITE'          },
    '2':  { index: 2,  name: 'DEPARTMENT'    },
    '3':  { index: 3,  name: 'PRINCIPAL'     },
    '4':  { index: 4,  name: 'POSITION'      },
    '5':  { index: 5,  name: 'BRAND'         },
    '6':  { index: 6,  name: 'MODEL'         },
    '7':  { index: 7,  name: 'SERIAL'        },
    '8':  { index: 8,  name: 'DATE DEPLOYED' },
    '9':  { index: 9,  name: 'USER'          },
    '10': { index: 13, name: 'LAST LOAD'     },
    '11': { index: 14, name: 'LOAD STATUS'   }
};

function setRadio(name, value) {
    const radio = document.querySelector(`input[name="${name}"][value="${value}"]`);
    if (radio) {
        radio.checked = true;
    } else {
        const defaultValue = (name === 'games') ? 'No' : 'Yes';
        const defaultRadio = document.querySelector(`input[name="${name}"][value="${defaultValue}"]`);
        if (defaultRadio) defaultRadio.checked = true;
    }
}

function isForLoad(lastLoadDate, balance) {
    if (Number(balance) < 5) return true;
    if (lastLoadDate) {
        const lastLoad = new Date(lastLoadDate);
        const now = new Date();
        if (!isNaN(lastLoad)) {
            const diffMonths =
                (now.getFullYear() - lastLoad.getFullYear()) * 12 +
                (now.getMonth() - lastLoad.getMonth());
            if (diffMonths >= 6) return true;
        }
    }
    return false;
}

function updateMainTableRow(index, item) {
    const row = document.querySelector(`#itemsTable tbody tr:nth-child(${index + 1})`);
    if (!row) return;

    row.cells[1].textContent = item.SITE_ID || '';
    row.cells[2].textContent = item.DEPARTMENT || '';
    row.cells[3].textContent = item.PRINCIPAL || '';
    row.cells[4].textContent = item.POSITION || '';
    row.cells[5].textContent = item.BRAND || item.BARND || '';
    row.cells[6].textContent = item.MODEL || '';
    row.cells[7].textContent = item.SERIAL || '';
    row.cells[8].textContent = item.DATE_DEPLOYED || '';
    row.cells[9].textContent = item.PERSON_USING || '';
    row.cells[10].textContent = item.NUMBER || '';
    row.cells[15].textContent = item.DEVICE_STATUS || '';
    row.cells[16].textContent = item.REASON_CODE || '';
    row.cells[17].textContent = item.IT_RECOMMENDATION || '';
    row.cells[18].textContent = item.CHARGED_TO || '';

    const dataInput = row.cells[11].querySelector('input.balance-input');
    if (dataInput) {
        dataInput.value = Number(item.BALANCE ?? 0).toFixed(2);
        dataInput.dataset.old = item.BALANCE ?? '';
        dataInput.dataset.initial = Number(item.BALANCE ?? 0).toFixed(2);
        dataInput.dataset.original = item.BALANCE ?? '';
    }

    const consumedSpan = row.cells[12].querySelector('.consumed-data');
    if (consumedSpan && dataInput) {
        const initial = parseFloat(dataInput.dataset.initial) || 0;
        const current = parseFloat(dataInput.value) || 0;
        const consumed = Math.max(0, initial - current);
        consumedSpan.textContent = consumed.toFixed(2);
        consumedSpan.dataset.consumed = consumed.toFixed(2);
    }

    row.cells[13].textContent = item.LAST_LOAD_HISTORY || '-';

    const forLoad = isForLoad(item.LAST_LOAD_HISTORY, item.BALANCE);
    row.cells[14].textContent = forLoad ? 'FOR LOAD' : 'OK';
    row.cells[14].style.backgroundColor = forLoad ? 'red' : 'green';
    row.cells[14].style.color = 'white';
    row.cells[14].style.fontWeight = 'bold';
    row.cells[14].style.textAlign = 'center';

    const remarksInput = row.cells[19].querySelector('input.remarks-input');
    if (remarksInput) {
        remarksInput.value = (item.REMARKS || item.OTHER_ISSUES || '').toString().substring(0,120) || '';
    }
}

function loaddevices() {
    const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";
    const tbody = document.querySelector('#itemsTable tbody');
    if (!tbody) return;

    fetch(`/LM/datafetcher/loadcheckingdata.php?action=loaddevice&company=${encodeURIComponent(companyId)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            loadedPOs = data || [];
            tbody.innerHTML = '';
            if (loadedPOs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="21" class="text-center">No items found.</td></tr>`;
            } else {
                loadedPOs.forEach((item, index) => {
                    const forLoad = isForLoad(item.LAST_LOAD_HISTORY, item.BALANCE);
                    const loadStatus = forLoad ? 'FOR LOAD' : 'OK';
                    const isSubmittedToday = item.submitted_today === 1 ||
                                             item.submitted_today === '1' ||
                                             Number(item.submitted_today) === 1;

                    const isOlderThan10Months = isLastLoadOlderThan10Months(item.LAST_LOAD_HISTORY);
                    
                    let rowClass = '';
                    if (isOlderThan10Months) {
                        rowClass = 'row-older-than-10months';
                    }

                    let actionButtons = `
                        <button class="btn btn-info btn-sm py-0 px-2 me-1 history-btn"
                                style="font-size:8.5px;"
                                onclick="showAuditHistory('${item.LINEID}')"
                                title="View change history">
                            History
                        </button>
                        <button class="btn btn-primary btn-sm me-1"
                                style="font-size:9px; padding:2px 6px;"
                                onclick="selectDevice(${index})">
                            Select
                        </button>
                    `;
                    if (isSubmittedToday) {
                        actionButtons += `
                            <button class="btn btn-submitted btn-sm"
                                    style="font-size:9px; padding:2px 8px;"
                                    disabled title="Already submitted today">
                                ✓
                            </button>
                        `;
                    } else {
                        actionButtons += `
                            <button class="btn btn-success btn-sm submit-btn"
                                    style="font-size:9px; padding:2px 6px;"
                                    onclick="quickSubmitDevice(${index})"
                                    data-index="${index}"
                                    title="Mark as submitted (quick action)">
                                Submit
                            </button>
                        `;
                    }

                    const tr = document.createElement('tr');
                    if (rowClass) {
                        tr.className = rowClass;
                    }
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${item.SITE_ID || ''}</td>
                        <td>${item.DEPARTMENT || ''}</td>
                        <td>${item.PRINCIPAL || ''}</td>
                        <td>${item.POSITION || ''}</td>
                        <td>${item.BRAND || item.BARND || ''}</td>
                        <td>${item.MODEL || ''}</td>
                        <td>${item.SERIAL || ''}</td>
                        <td>${item.DATE_DEPLOYED || ''}</td>
                        <td>${item.PERSON_USING || ''}</td>
                        <td>${item.NUMBER || ''}</td>
                        <td style="text-align:center;">
                            <input type="number" step="0.01"
                                   class="form-control form-control-sm text-center balance-input"
                                   style="font-size:9px;height:22px; width:50px;padding:0;"
                                   value="${Number(item.BALANCE ?? 0).toFixed(2)}"
                                   data-id="${item.LINEID}"
                                   data-old="${item.BALANCE ?? ''}"
                                   data-initial="${Number(item.BALANCE ?? 0).toFixed(2)}"
                                   data-original="${item.BALANCE ?? ''}"
                                   onchange="markAsEdited(this)" />
                        </td>
                        <td style="text-align:center; font-weight:500;">
                            <span class="consumed-data" 
                                  data-consumed="${Number(item.CONSUMED ?? 0).toFixed(2)}">
                                ${Number(item.CONSUMED ?? 0).toFixed(2)}
                            </span>
                        </td>
                        <td>${item.LAST_LOAD_HISTORY || '-'}</td>
                        <td style="background-color:${forLoad ? 'red' : 'green'}; color:white; font-weight:bold; text-align:center;">
                            ${loadStatus}
                        </td>
                        <td>${item.DEVICE_STATUS || ''}</td>
                        <td>${item.REASON_CODE || ''}</td>
                        <td>${item.IT_RECOMMENDATION || ''}</td>
                        <td>${item.CHARGED_TO || ''}</td>
                        <td style="min-width:160px;">
                            <input type="text" class="form-control form-control-sm remarks-input"
                                   style="font-size:9px;height:22px;padding:0 6px;"
                                   value="${(item.REMARKS || item.OTHER_ISSUES || '').toString().substring(0,120) || ''}"
                                   data-index="${index}" />
                        </td>
                        <td>${actionButtons}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.querySelectorAll('.balance-input').forEach(input => {
                    updateTableConsumedData(input);
                });
                
                // Check and apply edited state - NOW CHECKS LAST 5 DAYS
                checkEditedBalances();
                
                // Optional: Clean up old entries (older than 5 days)
                cleanOldBalanceEdits();
            }

            initializeColumnVisibility();

            requestAnimationFrame(() => {
                populateSiteFilter();
                applyFilters();

                const today = new Date().toISOString().slice(0,10);
                fetch(`/LM/datafetcher/loadcheckingdata.php?action=today_submissions2&company=${encodeURIComponent(companyId)}&datefrom=${today}&dateto=${today}`)
                    .then(r => r.ok ? r.json() : Promise.reject(new Error('Failed')))
                    .then(logs => {
                        if (!Array.isArray(logs)) return;
                        const submittedNumbers = new Set(logs.map(l => (l.NUMBER || '').toString().trim()).filter(Boolean));
                        loadedPOs.forEach((item, idx) => {
                            const num = (item.NUMBER || '').toString().trim();
                            if (num && submittedNumbers.has(num)) {
                                item.submitted_today = 1;
                                const submitBtn = document.querySelector(`button.submit-btn[data-index="${idx}"]`);
                                if (submitBtn) {
                                    submitBtn.outerHTML = `<button class="btn btn-submitted btn-sm" style="font-size:9px; padding:2px 8px;" disabled title="Already submitted today">✓</button>`;
                                }
                            }
                        });
                    })
                    .catch(err => console.warn('today_submissions fetch failed', err));
            });
        })
        .catch(err => {
            console.error('Error loading items:', err);
            document.getElementById('table-error').textContent = 'Failed to load data.';
        });
}

function isLastLoadOlderThan10Months(lastLoadDate) {
    if (!lastLoadDate || lastLoadDate === '-') return false;
    
    let loadDate;
    
    if (lastLoadDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
        loadDate = new Date(lastLoadDate);
    } else {
        loadDate = new Date(lastLoadDate);
    }
    
    if (isNaN(loadDate.getTime())) return false;
    
    const today = new Date();
    const tenMonthsAgo = new Date();
    tenMonthsAgo.setMonth(today.getMonth() - 10);
    
    return loadDate < tenMonthsAgo;
}

async function showAuditHistory(lineid) {
    const modal = $('#auditHistoryModal');
    const historyLineIdEl = document.getElementById('historyLineId');
    const loadingEl = document.getElementById('historyLoading');
    const emptyEl = document.getElementById('historyEmpty');
    const tableEl = document.getElementById('historyTable');
    const tbody = tableEl.querySelector('tbody');

    historyLineIdEl.textContent = lineid;
    loadingEl.classList.remove('d-none');
    emptyEl.classList.add('d-none');
    tableEl.classList.add('d-none');
    tbody.innerHTML = '';

    try {
        const url = `/LM/datafetcher/loadcheckingdata.php?action=get_audit&lineid=${encodeURIComponent(lineid)}`;
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        loadingEl.classList.add('d-none');

        if (!data || data.success !== true) {
            throw new Error(data?.message || "Invalid response");
        }

        const logs = Array.isArray(data.logs) ? data.logs : [];
        const changeLogs = logs.filter(log => log.FieldName && log.FieldName.trim() !== '');

        if (changeLogs.length === 0) {
            emptyEl.classList.remove('d-none');
            modal.modal('show');
            return;
        }

        tableEl.classList.remove('d-none');
        changeLogs.forEach(log => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${new Date(log.ChangedAt).toLocaleString('en-PH', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true })}</td>
                <td>${log.ChangedBy || '—'}</td>
                <td><span class="badge badge-secondary">${log.ActionType || '—'}</span></td>
                <td>${log.FieldName || '—'}</td>
                <td class="text-danger font-weight-bold">${log.OldValue ?? '—'}</td>
                <td class="text-success font-weight-bold">${log.NewValue ?? '—'}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (err) {
        console.error("[History] Error:", err);
        loadingEl.innerHTML = `<div class="alert alert-danger"><strong>Failed to load history:</strong><br>${err.message}</div>`;
    }
    modal.modal('show');
}

function quickSubmitDevice(index) {
    const item = loadedPOs[index];
    if (!item) return;

    const row = document.querySelector(`#itemsTable tbody tr:nth-child(${index + 1})`);
    if (!row) return;

    const balanceInput = row.querySelector('.balance-input');
    const consumedSpan = row.querySelector('.consumed-data');
    const remarksInput = row.querySelector('.remarks-input');

    const currentBalance = balanceInput ? balanceInput.value.trim() : (item.BALANCE || '0');
    const currentConsumed = consumedSpan ? (consumedSpan.dataset.consumed || '0') : '0';

    const formData = {
        id: item.LINEID || '',
        SITE_ID: item.SITE_ID || '',
        DEPARTMENT: item.DEPARTMENT || '',
        PRINCIPAL: item.PRINCIPAL || '',
        POSITION: item.POSITION || '',
        BRAND: item.BRAND || item.BARND || '',
        MODEL: item.MODEL || '',
        IMEI: item.IMEI || '',
        SERIAL: item.SERIAL || '',
        DATE_DEPLOYED: item.DATE_DEPLOYED || '',
        PERSON_USING: item.PERSON_USING || '',
        NUMBER: item.NUMBER || '',
        REMARKS: item.REMARKS || '',
        BALANCE: currentBalance,
        DATA_USAGE: currentConsumed,
        DATA_SUBMITTED: 'Yes',
        PHYSICALLY_OK: 'Yes',
        GAMES: 'No',
        SYSTEM_UPDATED: 'Yes',
        OTHER_ISSUES: item.OTHER_ISSUES || '',
        CONSUMED: currentConsumed,
        LAST_LOAD_HISTORY: item.LAST_LOAD_HISTORY,
        DEVICE_STATUS: item.DEVICE_STATUS || '',
        REASON_CODE: item.REASON_CODE || '',
        DATE_SURRENDERED: item.DATE_SURRENDERED || '',
        DAYS_TO_REPAIR: item.DAYS_TO_REPAIR || '',
        TEMPORARY_DEVICE: item.TEMPORARY_DEVICE || '',
        IT_RECOMMENDATION: item.IT_RECOMMENDATION || '',
        CHARGED_TO: item.CHARGED_TO || ''
    };

    fetch('/LM/datafetcher/loadcheckingdata.php?action=update_device', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            loadedPOs[index] = { ...loadedPOs[index], ...formData };

            if (row) {
                row.style.backgroundColor = '#d4edda';
                setTimeout(() => row.style.backgroundColor = '', 1200);
            }

            const submitBtn = row.querySelector(`button.submit-btn[data-index="${index}"]`);
            if (submitBtn) {
                submitBtn.outerHTML = `<button class="btn btn-submitted btn-sm" style="font-size:9px; padding:2px 8px;" disabled title="Already submitted today">✓</button>`;
            }
        } else {
            alert('Submit failed: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Quick submit error:', err);
        alert('Error during quick submit. Please refresh and try again.');
    });
}

function populateSiteFilter() {
    const select = document.getElementById('siteFilter');
    if (!select) return;
    const sites = new Set();
    loadedPOs.forEach(item => {
        if (item.SITE_ID && item.SITE_ID.trim()) sites.add(item.SITE_ID.trim());
    });
    select.innerHTML = '<option value="">All Sites</option>';
    [...sites].sort().forEach(site => {
        const option = document.createElement('option');
        option.value = site;
        option.textContent = site;
        select.appendChild(option);
    });
}

function applyFilters() {
    const searchText = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
    const selectedSite = (document.getElementById('siteFilter')?.value || '').trim();

    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        if (row.cells.length < 2) return;
        const rowText = row.textContent.toLowerCase();
        const siteCell = row.cells[1];
        const rowSite = siteCell ? siteCell.textContent.trim() : '';
        const matchesSearch = rowText.includes(searchText);
        const matchesSite = !selectedSite || rowSite === selectedSite;
        row.style.display = (matchesSearch && matchesSite) ? '' : 'none';
    });
}

function selectDevice(index) {
    const item = loadedPOs[index];
    if (!item) {
        console.warn("No item at index:", index);
        return;
    }

    document.getElementById('edit_id').value         = item.LINEID || '';
    document.getElementById('edit_site').value       = item.SITE_ID || '';
    document.getElementById('edit_dept').value       = item.DEPARTMENT || '';
    document.getElementById('edit_principal').value  = item.PRINCIPAL || '';
    document.getElementById('edit_position').value   = item.POSITION || '';
    document.getElementById('edit_brand').value      = item.BRAND || item.BARND || '';
    document.getElementById('edit_model').value      = item.MODEL || '';
    document.getElementById('edit_imei').value       = item.IMEI || '';
    document.getElementById('edit_serial').value     = item.SERIAL || '';
    let dateVal = item.DATE_DEPLOYED || '';
    if (dateVal && dateVal.includes(' ')) dateVal = dateVal.split(' ')[0];
    document.getElementById('edit_date').value       = dateVal;
    document.getElementById('edit_user').value       = item.PERSON_USING || '';
    document.getElementById('edit_number').value     = item.NUMBER || '';
    document.getElementById('edit_data_left').value  = item.BALANCE || '';
    document.getElementById('edit_data_consumed').value = item.DATA_USAGE || '';
    document.getElementById('edit_remarks').value    = item.REMARKS || '';
    document.getElementById('edit_last_load').value  = item.LAST_LOAD_HISTORY || '';
    
    // Device Status
    document.getElementById('edit_device_status').value = item.DEVICE_STATUS || 'Good condition';
    
    // Populate Reason Codes based on status
    populateReasonCodes(item.DEVICE_STATUS || 'Good condition');
    
    // Set Reason Code value
    document.getElementById('edit_reason_code').value = item.REASON_CODE || '';
    
    // Repair fields
    document.getElementById('edit_date_surrendered').value = item.DATE_SURRENDERED || '';
    document.getElementById('edit_days_to_repair').value = item.DAYS_TO_REPAIR || '';
    document.getElementById('edit_temporary_device').value = item.TEMPORARY_DEVICE || '';
    
    // IT Recommendation & Charged To
    document.getElementById('edit_it_recommendation').value = item.IT_RECOMMENDATION || '';
    document.getElementById('edit_charged_to').value = item.CHARGED_TO || '';

    let lastLoadVal = '';
    if (item.LAST_LOAD_HISTORY) {
        lastLoadVal = item.LAST_LOAD_HISTORY.split(' ')[0]; 
    }
    document.getElementById('edit_last_load').value = lastLoadVal;

    const forLoad = isForLoad(lastLoadVal || item.LAST_LOAD_HISTORY, item.BALANCE);
    const loadStatus = forLoad ? 'FOR LOAD' : 'OK';

    const statusEl = document.getElementById('edit_load_status');
    statusEl.value = loadStatus;
    statusEl.style.backgroundColor = forLoad ? '#dc3545' : '#28a745';
    statusEl.style.color = 'white';

    const lastLoadInput = document.getElementById('edit_last_load');
    const currentBalanceInput = document.getElementById('edit_data_left');

    const updateStatusLive = () => {
        const newDate = lastLoadInput.value;
        const bal = parseFloat(currentBalanceInput.value) || 0;
        const needsLoad = isForLoad(newDate, bal);
        statusEl.value = needsLoad ? 'FOR LOAD' : 'OK';
        statusEl.style.backgroundColor = needsLoad ? '#dc3545' : '#28a745';
    };

    lastLoadInput.removeEventListener('change', updateStatusLive);
    lastLoadInput.addEventListener('change', updateStatusLive);

    const explanationEl = document.getElementById('load_terms_explanation');
    if (explanationEl) {
        let msg = forLoad ? 'Needs reload: ' : 'Currently OK: ';
        const bal = Number(item.BALANCE || 0);
        if (bal <= 0) msg += 'critical low/zero balance';
        else if (bal < 5) msg += 'low balance';
        if (lastLoadVal) {
            try {
                const last = new Date(lastLoadVal);
                const months = (new Date().getFullYear() - last.getFullYear()) * 12 +
                               (new Date().getMonth() - last.getMonth());
                if (months >= 6) msg += ' + No reload > Load Terms';
            } catch {}
        }
        explanationEl.textContent = msg.trim();
        explanationEl.style.color = forLoad ? '#c92a2a' : '#2f855a';
    }

    setRadio('data_submitted', item.DATA_SUBMITTED || 'Yes');
    setRadio('physically_ok', item.PHYSICALLY_OK || 'Yes');
    setRadio('games', item.GAMES || 'No');
    setRadio('system_updated', item.SYSTEM_UPDATED || 'Yes');
    document.getElementById('other_issues').value = item.OTHER_ISSUES || '';

    document.querySelectorAll('#itemsTable tbody tr').forEach(r => r.classList.remove('table-active'));
    document.querySelectorAll('#itemsTable tbody tr')[index]?.classList.add('table-active');

    const saveBtn = document.getElementById('btnSaveChanges');
    if (saveBtn) {
        const isSubmitted = item.submitted_today === 1 || Number(item.submitted_today) === 1;
        if (isSubmitted) {
            saveBtn.textContent = '✓';
            saveBtn.classList.remove('btn-success');
            saveBtn.classList.add('btn-submitted');
            saveBtn.disabled = true;
            saveBtn.title = 'Already submitted today';
        } else {
            saveBtn.textContent = 'SET AS SUBMITTED';
            saveBtn.classList.remove('btn-submitted');
            saveBtn.classList.add('btn-success');
            saveBtn.disabled = false;
            saveBtn.title = 'Set as submitted';
        }
    }

    $('#editDeviceModal').modal('show');
}

function autoComputeDataConsumed() {
    const dataLeftInput = document.getElementById('edit_data_left');
    const dataConsumedInput = document.getElementById('edit_data_consumed');
    if (!dataLeftInput || !dataConsumedInput) return;

    const current = parseFloat(dataLeftInput.value) || 0;
    const initial = parseFloat(dataLeftInput.dataset.initialBalance) || 0;
    const consumed = Math.max(0, initial - current);
    dataConsumedInput.value = consumed.toFixed(2);
}

function updateTableConsumedData(balanceInput) {
    if (!balanceInput) return;

    const row = balanceInput.closest('tr');
    if (!row) return;

    const span = row.querySelector('.consumed-data');
    if (!span) return;

    const current = parseFloat(balanceInput.value) || 0;
    const initial = parseFloat(balanceInput.dataset.initial) || 0;
    const consumed = Math.max(0, initial - current);

    if (balanceInput.value !== balanceInput.dataset.old) {
        span.textContent = consumed.toFixed(2);
        span.dataset.consumed = consumed.toFixed(2);
    }
}

function toggleColumn(colKey, show) {
    const col = columnMap[colKey];
    if (!col) return;

    const headerCells = document.querySelectorAll('#itemsTable thead th');
    if (headerCells[col.index]) {
        headerCells[col.index].classList.toggle('table-hidden', !show);
    }

    const bodyCells = document.querySelectorAll(`#itemsTable tbody td:nth-child(${col.index + 1})`);
    bodyCells.forEach(cell => {
        cell.classList.toggle('table-hidden', !show);
    });

    let visibility = JSON.parse(localStorage.getItem(COLUMN_VISIBILITY_KEY) || '{}');
    visibility[colKey] = show;
    localStorage.setItem(COLUMN_VISIBILITY_KEY, JSON.stringify(visibility));
}

function initializeColumnVisibility() {
    const saved = localStorage.getItem(COLUMN_VISIBILITY_KEY);
    const visibility = saved ? JSON.parse(saved) : {};

    document.querySelectorAll('.col-toggle-checkbox').forEach(checkbox => {
        const colKey = checkbox.dataset.col;
        const isVisible = visibility[colKey] !== false; 
        checkbox.checked = isVisible;
        toggleColumn(colKey, isVisible);
    });
}

document.getElementById('btnSaveChanges')?.addEventListener('click', () => {
    const bal = document.getElementById('edit_data_left').value.trim();
    if (!bal) {
        alert('Please insert current data balance');
        return;
    }

    const currentBalance  = document.getElementById('edit_data_left').value.trim();
    const currentConsumed = document.getElementById('edit_data_consumed').value.trim() || '0';

    const formData = {
        id: document.getElementById('edit_id').value || '',
        SITE_ID: document.getElementById('edit_site').value.trim(),
        DEPARTMENT: document.getElementById('edit_dept').value.trim(),
        PRINCIPAL: document.getElementById('edit_principal').value.trim(),
        POSITION: document.getElementById('edit_position').value.trim(),
        BRAND: document.getElementById('edit_brand').value.trim(),
        MODEL: document.getElementById('edit_model').value.trim(),
        IMEI: document.getElementById('edit_imei').value.trim(),
        SERIAL: document.getElementById('edit_serial').value.trim(),
        DATE_DEPLOYED: document.getElementById('edit_date').value,
        PERSON_USING: document.getElementById('edit_user').value.trim(),
        NUMBER: document.getElementById('edit_number').value.trim(),
        REMARKS: document.getElementById('edit_remarks').value.trim(),
        BALANCE: currentBalance,
        DATA_USAGE: currentConsumed,
        LAST_LOAD_HISTORY: document.getElementById('edit_last_load').value || null,
        DATA_SUBMITTED: document.querySelector('input[name="data_submitted"]:checked')?.value || 'Yes',
        PHYSICALLY_OK: document.querySelector('input[name="physically_ok"]:checked')?.value || 'Yes',
        GAMES: document.querySelector('input[name="games"]:checked')?.value || 'No',
        SYSTEM_UPDATED: document.querySelector('input[name="system_updated"]:checked')?.value || 'Yes',
        OTHER_ISSUES: document.getElementById('other_issues').value.trim(),
        CONSUMED: currentConsumed,
        DEVICE_STATUS: document.getElementById('edit_device_status').value || 'Good condition',
        REASON_CODE: document.getElementById('edit_reason_code').value || '',
        DATE_SURRENDERED: document.getElementById('edit_date_surrendered').value || '',
        DAYS_TO_REPAIR: document.getElementById('edit_days_to_repair').value || '',
        TEMPORARY_DEVICE: document.getElementById('edit_temporary_device').value || '',
        IT_RECOMMENDATION: document.getElementById('edit_it_recommendation').value || '',
        CHARGED_TO: document.getElementById('edit_charged_to').value || ''
    };

    fetch('/LM/datafetcher/loadcheckingdata.php?action=update_device', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Device submitted successfully');
            $('#editDeviceModal').modal('hide');

            const submittedId = document.getElementById('edit_id').value;
            const idx = loadedPOs.findIndex(item => item.LINEID == submittedId);
            if (idx !== -1) {
                loadedPOs[idx] = { ...loadedPOs[idx], ...formData };
                updateMainTableRow(idx, loadedPOs[idx]);

                const row = document.querySelector(`#itemsTable tbody tr:nth-child(${idx + 1})`);
                if (row) {
                    row.style.backgroundColor = '#d4edda';
                    setTimeout(() => row.style.backgroundColor = '', 1200);
                }

                const submitBtn = document.querySelector(`button.submit-btn[data-index="${idx}"]`);
                if (submitBtn) {
                    submitBtn.outerHTML = `<button class="btn btn-submitted btn-sm" style="font-size:9px; padding:2px 8px;" disabled title="Already submitted today">✓</button>`;
                }
            }
        } else {
            alert('Save failed: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error saving changes, Please refresh the page and try again');
    });
});

document.getElementById('btnUpdateOnly')?.addEventListener('click', () => {
    const bal = document.getElementById('edit_data_left').value.trim();
    if (!bal) {
        alert('Please insert current data balance');
        return;
    }

    const formData = {
        id: document.getElementById('edit_id').value || '',
        SITE_ID: document.getElementById('edit_site').value.trim(),
        DEPARTMENT: document.getElementById('edit_dept').value.trim(),
        PRINCIPAL: document.getElementById('edit_principal').value.trim(),
        POSITION: document.getElementById('edit_position').value.trim(),
        BRAND: document.getElementById('edit_brand').value.trim(),
        MODEL: document.getElementById('edit_model').value.trim(),
        IMEI: document.getElementById('edit_imei').value.trim(),
        SERIAL: document.getElementById('edit_serial').value.trim(),
        DATE_DEPLOYED: document.getElementById('edit_date').value,
        PERSON_USING: document.getElementById('edit_user').value.trim(),
        NUMBER: document.getElementById('edit_number').value.trim(),
        REMARKS: document.getElementById('edit_remarks').value.trim(),
        BALANCE: document.getElementById('edit_data_left').value.trim(),
        DATA_USAGE: document.getElementById('edit_data_consumed').value.trim(),
        LAST_LOAD_HISTORY: document.getElementById('edit_last_load').value || null,
        DATA_SUBMITTED: document.querySelector('input[name="data_submitted"]:checked')?.value || 'Yes',
        PHYSICALLY_OK: document.querySelector('input[name="physically_ok"]:checked')?.value || 'Yes',
        GAMES: document.querySelector('input[name="games"]:checked')?.value || 'No',
        SYSTEM_UPDATED: document.querySelector('input[name="system_updated"]:checked')?.value || 'Yes',
        OTHER_ISSUES: document.getElementById('other_issues').value.trim(),
        CONSUMED: document.getElementById('edit_data_consumed').value.trim() || '0',
        DEVICE_STATUS: document.getElementById('edit_device_status').value || 'Good condition',
        REASON_CODE: document.getElementById('edit_reason_code').value || '',
        DATE_SURRENDERED: document.getElementById('edit_date_surrendered').value || '',
        DAYS_TO_REPAIR: document.getElementById('edit_days_to_repair').value || '',
        TEMPORARY_DEVICE: document.getElementById('edit_temporary_device').value || '',
        IT_RECOMMENDATION: document.getElementById('edit_it_recommendation').value || '',
        CHARGED_TO: document.getElementById('edit_charged_to').value || ''
    };

    fetch('/LM/datafetcher/loadcheckingdata.php?action=updateonly', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Device details updated successfully');
            $('#editDeviceModal').modal('hide');

            const submittedId = document.getElementById('edit_id').value;
            const idx = loadedPOs.findIndex(item => item.LINEID == submittedId);
            if (idx !== -1) {
                loadedPOs[idx] = { ...loadedPOs[idx], ...formData };
                updateMainTableRow(idx, loadedPOs[idx]);

                const row = document.querySelector(`#itemsTable tbody tr:nth-child(${idx + 1})`);
                if (row) {
                    row.style.backgroundColor = '#d4edda';
                    setTimeout(() => row.style.backgroundColor = '', 1200);
                }
            }
        } else {
            alert('Save failed: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error saving changes, Please refresh the page and try again');
    });
});

// Clean up QR scanner when modal is closed
$('#qrScannerModal').on('hidden.bs.modal', function () {
    stopQRScanner();
});

document.addEventListener("DOMContentLoaded", () => {
    loaddevices();

    document.getElementById('searchInput')?.addEventListener('keyup', applyFilters);
    document.getElementById('siteFilter')?.addEventListener('change', applyFilters);

    $('#editDeviceModal').on('shown.bs.modal', function () {
        document.getElementById('edit_data_left')?.focus();
    });

    document.addEventListener('input', e => {
        if (e.target?.id === 'edit_data_left') autoComputeDataConsumed();
        if (e.target?.classList?.contains('balance-input')) updateTableConsumedData(e.target);
    });

    document.addEventListener('blur', e => {
        if (e.target?.id === 'edit_data_left') autoComputeDataConsumed();
        if (e.target?.classList?.contains('balance-input')) updateTableConsumedData(e.target);
    }, true);

    function focusNextBalance(currentInput) {
        const allInputs = document.querySelectorAll('.balance-input');
        const currentIndex = Array.from(allInputs).indexOf(currentInput);
        const nextInput = allInputs[currentIndex + 1];
        
        if (nextInput) {
            nextInput.focus();
            nextInput.select();
        }
    }

    document.addEventListener('keydown', e => {
        if (!e.target.classList.contains('balance-input')) return;
        
        if (e.key === 'Enter') {
            e.preventDefault();
            saveBalance(e.target);
        }
    });

    document.addEventListener('focusout', e => {
        if (!e.target.classList.contains('balance-input')) return;
        
        if (e.target.value.trim() === e.target.dataset.old) {
            return;
        }
        
        saveBalance(e.target);
    });

    function saveBalance(input) {
        const newValue = input.value.trim();
        const oldValue = input.dataset.old || '';

        if (!newValue || newValue === oldValue) {
            if (newValue !== oldValue) {
                input.value = oldValue;
            }
            return;
        }

        if (isNaN(parseFloat(newValue))) {
            alert('Please enter a valid number');
            input.value = oldValue;
            return;
        }

        const lineId = input.dataset.id;
        const consumedSpan = input.closest('tr')?.querySelector('.consumed-data');
        const consumed = consumedSpan ? (consumedSpan.dataset.consumed || '0') : '0';

        input.classList.add('saving');

        fetch('/LM/datafetcher/loadcheckingdata.php?action=update_balance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                id: lineId, 
                BALANCE: newValue,
                CONSUMED: consumed 
            })
        })
        .then(r => r.json())
        .then(resp => {
            input.classList.remove('saving');

            if (resp.success) {
                input.dataset.old = newValue;
                if (document.activeElement === input) {
                    focusNextBalance(input);
                }
            } else {
                alert(resp.message || 'Update failed');
                input.value = oldValue;
            }
        })
        .catch(err => {
            console.error(err);
            input.classList.remove('saving');
            alert('Error saving balance. Check your connection.');
            input.value = oldValue;
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('col-toggle-checkbox')) {
            const colKey = e.target.dataset.col;
            toggleColumn(colKey, e.target.checked);
        }
    });
});
</script>

</body>
</html>