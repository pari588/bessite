<?php
/**
 * HRMS Settings - Unified Settings Dashboard
 * Tabbed interface for all HRMS configuration
 */

// Set module vars
setModVars(array("MOD" => "hrms-settings", "FAV" => "cog"));
$modURL = ADMINURL . "/" . $MODSET['MODLINK'];
?>

<style>
/* Settings Dashboard */
.hrms-settings {
    max-width: 1400px;
    margin: 0 auto;
}

.settings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.settings-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.settings-header h1 svg {
    width: 28px;
    height: 28px;
    color: #3b82f6;
}

/* Tabs */
.settings-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 24px;
    overflow-x: auto;
    padding-bottom: 0;
}

.settings-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}

.settings-tab:hover {
    color: #3b82f6;
    background: #f8fafc;
}

.settings-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
    background: transparent;
}

.settings-tab svg {
    width: 18px;
    height: 18px;
}

/* Tab Content */
.tab-content {
    display: none;
    animation: fadeIn 0.2s ease;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Card */
.settings-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.card-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.card-body {
    padding: 20px;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    margin-bottom: 6px;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    font-size: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #1e293b;
    outline: none;
    transition: border-color 0.2s;
}

.form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-hint {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}

.form-row {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.form-row .form-group {
    flex: 1;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: #fff;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.btn-success {
    background: #22c55e;
    color: #fff;
}

.btn-success:hover {
    background: #16a34a;
}

.btn-danger {
    background: #ef4444;
    color: #fff;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.btn svg {
    width: 16px;
    height: 16px;
}

/* Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.data-table th {
    text-align: left;
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}

.data-table tr:hover td {
    background: #f8fafc;
}

.data-table .actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

/* Status Badge */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 9999px;
}

.badge-success {
    background: #dcfce7;
    color: #16a34a;
}

.badge-warning {
    background: #fef9c3;
    color: #ca8a04;
}

.badge-danger {
    background: #fee2e2;
    color: #dc2626;
}

.badge-info {
    background: #dbeafe;
    color: #2563eb;
}

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    margin: 20px;
    max-height: 90vh;
    overflow: hidden;
    transform: scale(0.95);
    transition: transform 0.2s;
}

.modal-overlay.active .modal {
    transform: scale(1);
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.modal-close {
    background: transparent;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #94a3b8;
}

.modal-close:hover {
    color: #475569;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(90vh - 150px);
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.empty-state svg {
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0 0 16px 0;
}

/* Toggle Switch */
.toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e2e8f0;
    border-radius: 24px;
    transition: .3s;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: .3s;
}

.toggle input:checked + .toggle-slider {
    background-color: #3b82f6;
}

.toggle input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

/* Info Box */
.info-box {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
}

.info-box svg {
    width: 20px;
    height: 20px;
    color: #0284c7;
    flex-shrink: 0;
}

.info-box p {
    margin: 0;
    font-size: 13px;
    color: #0369a1;
}

/* Loader */
.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .settings-tabs {
        padding: 0 10px;
    }

    .settings-tab {
        padding: 10px 14px;
        font-size: 13px;
    }

    .settings-tab span {
        display: none;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        flex-direction: column;
    }
}
</style>

<div class="hrms-settings">
    <!-- Header -->
    <div class="settings-header">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            HRMS Settings
        </h1>
    </div>

    <!-- Tabs -->
    <div class="settings-tabs">
        <button class="settings-tab active" data-tab="general" onclick="switchTab('general')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>General</span>
        </button>
        <button class="settings-tab" data-tab="holidays" onclick="switchTab('holidays')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Holidays</span>
        </button>
        <button class="settings-tab" data-tab="shifts" onclick="switchTab('shifts')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Shifts</span>
        </button>
        <button class="settings-tab" data-tab="leave-types" onclick="switchTab('leave-types')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Leave Types</span>
        </button>
        <button class="settings-tab" data-tab="biometric" onclick="switchTab('biometric')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
            <span>Biometric</span>
        </button>
        <button class="settings-tab" data-tab="email" onclick="switchTab('email')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>Email</span>
        </button>
    </div>

    <!-- Tab Contents -->

    <!-- General Settings -->
    <div class="tab-content active" id="tab-general">
        <div class="settings-card">
            <div class="card-header">
                <h3>General Attendance Settings</h3>
            </div>
            <div class="card-body">
                <form id="generalSettingsForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Work Start Time</label>
                            <input type="time" class="form-control" id="workStartTime" value="09:00">
                        </div>
                        <div class="form-group">
                            <label>Work End Time</label>
                            <input type="time" class="form-control" id="workEndTime" value="18:00">
                        </div>
                        <div class="form-group">
                            <label>Late Grace Minutes</label>
                            <input type="number" class="form-control" id="lateGraceMinutes" value="15" min="0" max="60">
                            <div class="form-hint">Minutes allowed after start time before marking late</div>
                        </div>
                        <div class="form-group">
                            <label>Late Count for 1 Day Deduction</label>
                            <input type="number" class="form-control" id="lateDeductionCount" value="3" min="1" max="10">
                            <div class="form-hint">Number of late marks = 1 day salary deduction</div>
                        </div>
                        <div class="form-group">
                            <label>Half Day Hours</label>
                            <input type="number" class="form-control" id="halfDayHours" value="4" min="1" max="6" step="0.5">
                            <div class="form-hint">Minimum hours for half-day attendance</div>
                        </div>
                        <div class="form-group">
                            <label>Full Day Hours</label>
                            <input type="number" class="form-control" id="fullDayHours" value="8" min="4" max="12" step="0.5">
                            <div class="form-hint">Minimum hours for full-day attendance</div>
                        </div>
                        <div class="form-group">
                            <label>Weekly Off Day</label>
                            <select class="form-control" id="weekOffDay">
                                <option value="0">Sunday</option>
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 24px;">
                        <button type="button" class="btn btn-primary" onclick="saveGeneralSettings()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Holidays Tab -->
    <div class="tab-content" id="tab-holidays">
        <div class="settings-card">
            <div class="card-header">
                <h3>Holiday Calendar <?php echo date('Y'); ?></h3>
                <button class="btn btn-primary btn-sm" onclick="openHolidayModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Add Holiday
                </button>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="data-table" id="holidaysTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Holiday Name</th>
                            <th>Type</th>
                            <th>Half Day</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="holidaysTableBody">
                        <tr><td colspan="5" class="loading"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Shifts Tab -->
    <div class="tab-content" id="tab-shifts">
        <div class="settings-card">
            <div class="card-header">
                <h3>Shift Master</h3>
                <button class="btn btn-primary btn-sm" onclick="openShiftModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Add Shift
                </button>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="data-table" id="shiftsTable">
                    <thead>
                        <tr>
                            <th>Shift Name</th>
                            <th>Code</th>
                            <th>Timing</th>
                            <th>Grace</th>
                            <th>Night Shift</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="shiftsTableBody">
                        <tr><td colspan="6" class="loading"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Leave Types Tab -->
    <div class="tab-content" id="tab-leave-types">
        <div class="settings-card">
            <div class="card-header">
                <h3>Leave Types</h3>
                <button class="btn btn-primary btn-sm" onclick="openLeaveTypeModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Add Leave Type
                </button>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="data-table" id="leaveTypesTable">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Code</th>
                            <th>Annual Allotment</th>
                            <th>Paid</th>
                            <th>Carry Forward</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leaveTypesTableBody">
                        <tr><td colspan="6" class="loading"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Biometric Tab -->
    <div class="tab-content" id="tab-biometric">
        <div class="settings-card">
            <div class="card-header">
                <h3>Biometric Device Integration</h3>
                <button class="btn btn-success btn-sm" onclick="bulkAssignBiometricIDs()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Bulk Assign IDs
                </button>
            </div>
            <div class="card-body">
                <div class="info-box" id="biometricStatus">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <p>Loading biometric status...</p>
                </div>

                <table class="data-table" id="biometricTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Biometric ID</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="biometricTableBody">
                        <tr><td colspan="5" class="loading"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Email Tab -->
    <div class="tab-content" id="tab-email">
        <div class="settings-card">
            <div class="card-header">
                <h3>Email Notification Settings</h3>
            </div>
            <div class="card-body">
                <form id="emailSettingsForm">
                    <div class="form-group">
                        <label>HR Master Email (for summary reports)</label>
                        <input type="email" class="form-control" id="hrMasterEmail" placeholder="hr@company.com">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Send Monthly Report On Day</label>
                            <select class="form-control" id="emailSendDay">
                                <?php for ($i = 1; $i <= 28; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>At Time (Hour)</label>
                            <select class="form-control" id="emailSendHour">
                                <?php for ($i = 0; $i < 24; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>:00</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                        <label class="toggle">
                            <input type="checkbox" id="enableAutoSend">
                            <span class="toggle-slider"></span>
                        </label>
                        <span>Enable automatic monthly salary slip emails</span>
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="button" class="btn btn-primary" onclick="saveEmailSettings()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Email Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Holiday Modal -->
<div class="modal-overlay" id="holidayModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="holidayModalTitle">Add Holiday</h3>
            <button class="modal-close" onclick="closeHolidayModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Date</label>
                <input type="date" class="form-control" id="holidayDate">
            </div>
            <div class="form-group">
                <label>Holiday Name</label>
                <input type="text" class="form-control" id="holidayName" placeholder="e.g., Republic Day">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select class="form-control" id="holidayType">
                    <option value="national">National Holiday</option>
                    <option value="company">Company Holiday</option>
                    <option value="optional">Optional Holiday</option>
                </select>
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                <label class="toggle">
                    <input type="checkbox" id="holidayHalfDay">
                    <span class="toggle-slider"></span>
                </label>
                <span>Half Day Holiday</span>
            </div>
            <input type="hidden" id="holidayID" value="0">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeHolidayModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveHoliday()">Save Holiday</button>
        </div>
    </div>
</div>

<!-- Shift Modal -->
<div class="modal-overlay" id="shiftModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="shiftModalTitle">Add Shift</h3>
            <button class="modal-close" onclick="closeShiftModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Shift Name</label>
                <input type="text" class="form-control" id="shiftName" placeholder="e.g., General Shift">
            </div>
            <div class="form-group">
                <label>Shift Code</label>
                <input type="text" class="form-control" id="shiftCode" placeholder="e.g., GEN" maxlength="10">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" class="form-control" id="shiftStartTime" value="09:00">
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" class="form-control" id="shiftEndTime" value="18:00">
                </div>
            </div>
            <div class="form-group">
                <label>Grace Minutes</label>
                <input type="number" class="form-control" id="shiftGraceMinutes" value="15" min="0" max="60">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                <label class="toggle">
                    <input type="checkbox" id="shiftIsNight">
                    <span class="toggle-slider"></span>
                </label>
                <span>Night Shift (crosses midnight)</span>
            </div>
            <input type="hidden" id="shiftID" value="0">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeShiftModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveShift()">Save Shift</button>
        </div>
    </div>
</div>

<!-- Leave Type Modal -->
<div class="modal-overlay" id="leaveTypeModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="leaveTypeModalTitle">Add Leave Type</h3>
            <button class="modal-close" onclick="closeLeaveTypeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Leave Type Name</label>
                <input type="text" class="form-control" id="leaveTypeName" placeholder="e.g., Earned Leave">
            </div>
            <div class="form-group">
                <label>Code</label>
                <input type="text" class="form-control" id="leaveTypeCode" placeholder="e.g., EL" maxlength="5">
            </div>
            <div class="form-group">
                <label>Annual Allotment (days)</label>
                <input type="number" class="form-control" id="leaveTypeAllotment" value="12" min="0">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                <label class="toggle">
                    <input type="checkbox" id="leaveTypeIsPaid" checked>
                    <span class="toggle-slider"></span>
                </label>
                <span>Paid Leave</span>
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                <label class="toggle">
                    <input type="checkbox" id="leaveTypeCarryForward">
                    <span class="toggle-slider"></span>
                </label>
                <span>Allow Carry Forward</span>
            </div>
            <div class="form-group" id="maxCarryForwardGroup" style="display:none;">
                <label>Max Carry Forward Days</label>
                <input type="number" class="form-control" id="leaveTypeMaxCarry" value="5" min="0">
            </div>
            <input type="hidden" id="leaveTypeID" value="0">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeLeaveTypeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveLeaveType()">Save Leave Type</button>
        </div>
    </div>
</div>

<script>
var HRMS_API = '<?php echo ADMINURL; ?>/mod/hrms-settings/x-hrms-settings.inc.php';

// Tab Switching
function switchTab(tabName) {
    $('.settings-tab').removeClass('active');
    $('.settings-tab[data-tab="' + tabName + '"]').addClass('active');
    $('.tab-content').removeClass('active');
    $('#tab-' + tabName).addClass('active');

    // Load data for the tab
    switch (tabName) {
        case 'general':
            loadGeneralSettings();
            break;
        case 'holidays':
            loadHolidays();
            break;
        case 'shifts':
            loadShifts();
            break;
        case 'leave-types':
            loadLeaveTypes();
            break;
        case 'biometric':
            loadBiometricStatus();
            break;
        case 'email':
            loadEmailSettings();
            break;
    }
}

// Initialize
$(function() {
    loadGeneralSettings();
});

// ============ GENERAL SETTINGS ============
function loadGeneralSettings() {
    $.post(HRMS_API, { xAction: 'getGeneralSettings' }, function(res) {
        if (res.err === 0) {
            $('#workStartTime').val(res.settings.work_start_time || '09:00');
            $('#workEndTime').val(res.settings.work_end_time || '18:00');
            $('#lateGraceMinutes').val(res.settings.late_grace_minutes || 15);
            $('#lateDeductionCount').val(res.settings.late_deduction_count || 3);
            $('#halfDayHours').val(res.settings.half_day_hours || 4);
            $('#fullDayHours').val(res.settings.full_day_hours || 8);
            $('#weekOffDay').val(res.settings.week_off_day || 0);
        }
    }, 'json');
}

function saveGeneralSettings() {
    $.post(HRMS_API, {
        xAction: 'saveGeneralSettings',
        workStartTime: $('#workStartTime').val(),
        workEndTime: $('#workEndTime').val(),
        lateGraceMinutes: $('#lateGraceMinutes').val(),
        lateDeductionCount: $('#lateDeductionCount').val(),
        halfDayHours: $('#halfDayHours').val(),
        fullDayHours: $('#fullDayHours').val(),
        weekOffDay: $('#weekOffDay').val()
    }, function(res) {
        alert(res.err === 0 ? 'Settings saved successfully' : (res.msg || 'Failed to save'));
    }, 'json');
}

// ============ HOLIDAYS ============
function loadHolidays() {
    $.post(HRMS_API, { xAction: 'getHolidays', year: new Date().getFullYear() }, function(res) {
        if (res.err === 0) {
            renderHolidays(res.holidays);
        }
    }, 'json');
}

function renderHolidays(holidays) {
    if (holidays.length === 0) {
        $('#holidaysTableBody').html('<tr><td colspan="5" class="empty-state"><p>No holidays added yet</p></td></tr>');
        return;
    }

    var html = '';
    holidays.forEach(function(h) {
        var typeLabel = h.ahType === 'national' ? 'badge-danger' : (h.ahType === 'optional' ? 'badge-warning' : 'badge-info');
        html += '<tr>';
        html += '<td>' + formatDate(h.ahDate) + '</td>';
        html += '<td><strong>' + h.ahName + '</strong></td>';
        html += '<td><span class="badge ' + typeLabel + '">' + (h.ahType || 'company') + '</span></td>';
        html += '<td>' + (h.isHalfDay == 1 ? '<span class="badge badge-warning">Half Day</span>' : '-') + '</td>';
        html += '<td class="actions">';
        html += '<button class="btn btn-secondary btn-sm" onclick="editHoliday(' + JSON.stringify(h).replace(/"/g, '&quot;') + ')">Edit</button>';
        html += '<button class="btn btn-danger btn-sm" onclick="deleteHoliday(' + h.ahID + ')">Delete</button>';
        html += '</td>';
        html += '</tr>';
    });
    $('#holidaysTableBody').html(html);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    var d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
}

function openHolidayModal() {
    $('#holidayModalTitle').text('Add Holiday');
    $('#holidayID').val(0);
    $('#holidayDate').val('');
    $('#holidayName').val('');
    $('#holidayType').val('company');
    $('#holidayHalfDay').prop('checked', false);
    $('#holidayModal').addClass('active');
}

function closeHolidayModal() {
    $('#holidayModal').removeClass('active');
}

function editHoliday(h) {
    $('#holidayModalTitle').text('Edit Holiday');
    $('#holidayID').val(h.ahID);
    $('#holidayDate').val(h.ahDate);
    $('#holidayName').val(h.ahName);
    $('#holidayType').val(h.ahType || 'company');
    $('#holidayHalfDay').prop('checked', h.isHalfDay == 1);
    $('#holidayModal').addClass('active');
}

function saveHoliday() {
    $.post(HRMS_API, {
        xAction: 'saveHoliday',
        ahID: $('#holidayID').val(),
        ahDate: $('#holidayDate').val(),
        ahName: $('#holidayName').val(),
        ahType: $('#holidayType').val(),
        isHalfDay: $('#holidayHalfDay').is(':checked') ? 1 : 0
    }, function(res) {
        if (res.err === 0) {
            closeHolidayModal();
            loadHolidays();
        } else {
            alert(res.msg || 'Failed to save');
        }
    }, 'json');
}

function deleteHoliday(ahID) {
    if (!confirm('Delete this holiday?')) return;
    $.post(HRMS_API, { xAction: 'deleteHoliday', ahID: ahID }, function(res) {
        if (res.err === 0) {
            loadHolidays();
        } else {
            alert(res.msg || 'Failed to delete');
        }
    }, 'json');
}

// ============ SHIFTS ============
function loadShifts() {
    $.post(HRMS_API, { xAction: 'getShifts' }, function(res) {
        if (res.err === 0) {
            renderShifts(res.shifts);
        }
    }, 'json');
}

function renderShifts(shifts) {
    var activeShifts = shifts.filter(function(s) { return s.status == 1; });
    if (activeShifts.length === 0) {
        $('#shiftsTableBody').html('<tr><td colspan="6" class="empty-state"><p>No shifts added yet</p></td></tr>');
        return;
    }

    var html = '';
    activeShifts.forEach(function(s) {
        html += '<tr>';
        html += '<td><strong>' + s.shiftName + '</strong></td>';
        html += '<td><span class="badge badge-info">' + (s.shiftCode || '-') + '</span></td>';
        html += '<td>' + formatTime(s.startTime) + ' - ' + formatTime(s.endTime) + '</td>';
        html += '<td>' + (s.graceMinutes || 15) + ' min</td>';
        html += '<td>' + (s.isNightShift == 1 ? '<span class="badge badge-warning">Yes</span>' : '-') + '</td>';
        html += '<td class="actions">';
        html += '<button class="btn btn-secondary btn-sm" onclick="editShift(' + JSON.stringify(s).replace(/"/g, '&quot;') + ')">Edit</button>';
        html += '<button class="btn btn-danger btn-sm" onclick="deleteShift(' + s.shiftID + ')">Delete</button>';
        html += '</td>';
        html += '</tr>';
    });
    $('#shiftsTableBody').html(html);
}

function formatTime(time) {
    if (!time) return '-';
    var parts = time.split(':');
    var h = parseInt(parts[0]);
    var m = parts[1];
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + ampm;
}

function openShiftModal() {
    $('#shiftModalTitle').text('Add Shift');
    $('#shiftID').val(0);
    $('#shiftName').val('');
    $('#shiftCode').val('');
    $('#shiftStartTime').val('09:00');
    $('#shiftEndTime').val('18:00');
    $('#shiftGraceMinutes').val(15);
    $('#shiftIsNight').prop('checked', false);
    $('#shiftModal').addClass('active');
}

function closeShiftModal() {
    $('#shiftModal').removeClass('active');
}

function editShift(s) {
    $('#shiftModalTitle').text('Edit Shift');
    $('#shiftID').val(s.shiftID);
    $('#shiftName').val(s.shiftName);
    $('#shiftCode').val(s.shiftCode);
    $('#shiftStartTime').val(s.startTime);
    $('#shiftEndTime').val(s.endTime);
    $('#shiftGraceMinutes').val(s.graceMinutes || 15);
    $('#shiftIsNight').prop('checked', s.isNightShift == 1);
    $('#shiftModal').addClass('active');
}

function saveShift() {
    $.post(HRMS_API, {
        xAction: 'saveShift',
        shiftID: $('#shiftID').val(),
        shiftName: $('#shiftName').val(),
        shiftCode: $('#shiftCode').val(),
        startTime: $('#shiftStartTime').val(),
        endTime: $('#shiftEndTime').val(),
        graceMinutes: $('#shiftGraceMinutes').val(),
        isNightShift: $('#shiftIsNight').is(':checked') ? 1 : 0
    }, function(res) {
        if (res.err === 0) {
            closeShiftModal();
            loadShifts();
        } else {
            alert(res.msg || 'Failed to save');
        }
    }, 'json');
}

function deleteShift(shiftID) {
    if (!confirm('Delete this shift?')) return;
    $.post(HRMS_API, { xAction: 'deleteShift', shiftID: shiftID }, function(res) {
        if (res.err === 0) {
            loadShifts();
        } else {
            alert(res.msg || 'Failed to delete');
        }
    }, 'json');
}

// ============ LEAVE TYPES ============
function loadLeaveTypes() {
    $.post(HRMS_API, { xAction: 'getLeaveTypes' }, function(res) {
        if (res.err === 0) {
            renderLeaveTypes(res.leaveTypes);
        }
    }, 'json');
}

function renderLeaveTypes(types) {
    var activeTypes = types.filter(function(t) { return t.status == 1; });
    if (activeTypes.length === 0) {
        $('#leaveTypesTableBody').html('<tr><td colspan="6" class="empty-state"><p>No leave types added yet</p></td></tr>');
        return;
    }

    var html = '';
    activeTypes.forEach(function(t) {
        html += '<tr>';
        html += '<td><strong>' + t.leaveTypeName + '</strong></td>';
        html += '<td><span class="badge badge-info">' + (t.leaveTypeCode || '-') + '</span></td>';
        html += '<td>' + (t.allotedLeave || 0) + ' days</td>';
        html += '<td>' + (t.isPaid == 1 ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>') + '</td>';
        html += '<td>' + (t.carryForward == 1 ? 'Max ' + (t.maxCarryForward || 0) + ' days' : '-') + '</td>';
        html += '<td class="actions">';
        html += '<button class="btn btn-secondary btn-sm" onclick="editLeaveType(' + JSON.stringify(t).replace(/"/g, '&quot;') + ')">Edit</button>';
        html += '<button class="btn btn-danger btn-sm" onclick="deleteLeaveType(' + t.leaveTypeID + ')">Delete</button>';
        html += '</td>';
        html += '</tr>';
    });
    $('#leaveTypesTableBody').html(html);
}

function openLeaveTypeModal() {
    $('#leaveTypeModalTitle').text('Add Leave Type');
    $('#leaveTypeID').val(0);
    $('#leaveTypeName').val('');
    $('#leaveTypeCode').val('');
    $('#leaveTypeAllotment').val(12);
    $('#leaveTypeIsPaid').prop('checked', true);
    $('#leaveTypeCarryForward').prop('checked', false);
    $('#leaveTypeMaxCarry').val(5);
    $('#maxCarryForwardGroup').hide();
    $('#leaveTypeModal').addClass('active');
}

function closeLeaveTypeModal() {
    $('#leaveTypeModal').removeClass('active');
}

$('#leaveTypeCarryForward').on('change', function() {
    $('#maxCarryForwardGroup').toggle($(this).is(':checked'));
});

function editLeaveType(t) {
    $('#leaveTypeModalTitle').text('Edit Leave Type');
    $('#leaveTypeID').val(t.leaveTypeID);
    $('#leaveTypeName').val(t.leaveTypeName);
    $('#leaveTypeCode').val(t.leaveTypeCode);
    $('#leaveTypeAllotment').val(t.allotedLeave || 12);
    $('#leaveTypeIsPaid').prop('checked', t.isPaid == 1);
    $('#leaveTypeCarryForward').prop('checked', t.carryForward == 1);
    $('#leaveTypeMaxCarry').val(t.maxCarryForward || 5);
    $('#maxCarryForwardGroup').toggle(t.carryForward == 1);
    $('#leaveTypeModal').addClass('active');
}

function saveLeaveType() {
    $.post(HRMS_API, {
        xAction: 'saveLeaveType',
        leaveTypeID: $('#leaveTypeID').val(),
        leaveTypeName: $('#leaveTypeName').val(),
        leaveTypeCode: $('#leaveTypeCode').val(),
        allotedLeave: $('#leaveTypeAllotment').val(),
        isPaid: $('#leaveTypeIsPaid').is(':checked') ? 1 : 0,
        carryForward: $('#leaveTypeCarryForward').is(':checked') ? 1 : 0,
        maxCarryForward: $('#leaveTypeMaxCarry').val()
    }, function(res) {
        if (res.err === 0) {
            closeLeaveTypeModal();
            loadLeaveTypes();
        } else {
            alert(res.msg || 'Failed to save');
        }
    }, 'json');
}

function deleteLeaveType(leaveTypeID) {
    if (!confirm('Delete this leave type?')) return;
    $.post(HRMS_API, { xAction: 'deleteLeaveType', leaveTypeID: leaveTypeID }, function(res) {
        if (res.err === 0) {
            loadLeaveTypes();
        } else {
            alert(res.msg || 'Failed to delete');
        }
    }, 'json');
}

// ============ BIOMETRIC ============
function loadBiometricStatus() {
    $.post(HRMS_API, { xAction: 'getBiometricStatus' }, function(res) {
        if (res.err === 0) {
            var statusHtml = '';
            if (res.camsTestMode) {
                statusHtml = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><p><strong>Test Mode:</strong> Camsunit integration is in test mode. Configure CAMS_STGID in config.inc.php to enable live mode. Employees with biometric IDs: <strong>' + res.withBiometric + '</strong> | Without: <strong>' + res.withoutBiometric + '</strong></p>';
            } else {
                statusHtml = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><p><strong>Live Mode:</strong> Camsunit integration is active. Employees with biometric IDs: <strong>' + res.withBiometric + '</strong> | Without: <strong>' + res.withoutBiometric + '</strong></p>';
                $('#biometricStatus').css({ background: '#f0fdf4', 'border-color': '#bbf7d0' }).find('svg').css('color', '#16a34a');
                $('#biometricStatus p').css('color', '#166534');
            }
            $('#biometricStatus').html(statusHtml);
            renderBiometricTable(res.employees);
        }
    }, 'json');
}

function renderBiometricTable(employees) {
    if (employees.length === 0) {
        $('#biometricTableBody').html('<tr><td colspan="5" class="empty-state"><p>No employees found</p></td></tr>');
        return;
    }

    var html = '';
    employees.forEach(function(e) {
        html += '<tr>';
        html += '<td><strong>' + (e.displayName || e.userName) + '</strong></td>';
        html += '<td>' + (e.employeeCode || '-') + '</td>';
        html += '<td>' + (e.department || '-') + '</td>';
        html += '<td>';
        if (e.biometricID) {
            html += '<span class="badge badge-success">' + e.biometricID + '</span>';
        } else {
            html += '<span class="badge badge-warning">Not Assigned</span>';
        }
        html += '</td>';
        html += '<td class="actions">';
        if (!e.biometricID) {
            html += '<button class="btn btn-primary btn-sm" onclick="assignBiometricID(' + e.userID + ')">Assign ID</button>';
        } else {
            html += '<button class="btn btn-secondary btn-sm" onclick="editBiometricID(' + e.userID + ', \'' + e.biometricID + '\')">Edit</button>';
        }
        html += '</td>';
        html += '</tr>';
    });
    $('#biometricTableBody').html(html);
}

function assignBiometricID(userID) {
    $.post(HRMS_API, { xAction: 'assignBiometricID', userID: userID }, function(res) {
        if (res.err === 0) {
            alert('Biometric ID assigned: ' + res.biometricID);
            loadBiometricStatus();
        } else {
            alert(res.msg || 'Failed to assign');
        }
    }, 'json');
}

function editBiometricID(userID, currentID) {
    var newID = prompt('Enter new biometric ID:', currentID);
    if (newID && newID !== currentID) {
        $.post(HRMS_API, { xAction: 'assignBiometricID', userID: userID, biometricID: newID }, function(res) {
            if (res.err === 0) {
                loadBiometricStatus();
            } else {
                alert(res.msg || 'Failed to update');
            }
        }, 'json');
    }
}

function bulkAssignBiometricIDs() {
    if (!confirm('This will assign biometric IDs to all employees who don\'t have one. Continue?')) return;
    $.post(HRMS_API, { xAction: 'bulkAssignBiometricIDs' }, function(res) {
        if (res.err === 0) {
            alert(res.msg);
            loadBiometricStatus();
        } else {
            alert(res.msg || 'Failed');
        }
    }, 'json');
}

// ============ EMAIL SETTINGS ============
function loadEmailSettings() {
    $.post(HRMS_API, { xAction: 'getEmailSettings' }, function(res) {
        if (res.err === 0) {
            var hrEmail = '';
            if (res.recipients && res.recipients.length > 0) {
                hrEmail = res.recipients[0].recipientEmail || '';
            }
            $('#hrMasterEmail').val(hrEmail);
            $('#emailSendDay').val(res.settings.email_send_day || 1);
            $('#emailSendHour').val(res.settings.email_send_hour || 9);
            $('#enableAutoSend').prop('checked', res.settings.email_auto_send == 1);
        }
    }, 'json');
}

function saveEmailSettings() {
    var recipients = [];
    var hrEmail = $('#hrMasterEmail').val().trim();
    if (hrEmail) {
        recipients.push({ name: 'HR Master', email: hrEmail, types: 'hr_master' });
    }

    $.post(HRMS_API, {
        xAction: 'saveEmailSettings',
        recipients: recipients,
        sendDay: $('#emailSendDay').val(),
        sendHour: $('#emailSendHour').val(),
        enableAutoSend: $('#enableAutoSend').is(':checked') ? 1 : 0
    }, function(res) {
        alert(res.err === 0 ? 'Email settings saved' : (res.msg || 'Failed to save'));
    }, 'json');
}

// Close modals on backdrop click
$('.modal-overlay').on('click', function(e) {
    if (e.target === this) {
        $(this).removeClass('active');
    }
});
</script>
