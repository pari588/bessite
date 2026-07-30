<?php
/**
 * HRMS Team Management Page
 * For managers to view and manage their team's attendance, leaves, and requests
 */

requireHRMSLogin();

// Check if user is a manager or HR Admin (master admin)
$isHRAdmin = isHRMasterAdmin();
if (empty($_SESSION['HRMS_IS_MANAGER']) && !$isHRAdmin) {
    header('Location: ' . SITEURL . '/hrms/home/');
    exit;
}

$managerID = $_SESSION['HRMS_USER_ID'];
$managerName = $_SESSION['HRMS_USER_NAME'];
?>

<div class="hrms-page team-page">
    <!-- Page Header -->
    <div class="page-header">
        <h1>Team Management</h1>
        <p class="subtitle">View and manage your team's attendance and requests</p>
    </div>

    <!-- Quick Stats -->
    <div class="team-stats">
        <div class="stat-card">
            <div class="stat-icon present">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value" id="totalTeam">-</span>
                <span class="stat-label">Team Members</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value" id="presentToday">-</span>
                <span class="stat-label">Present Today</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value" id="lateToday">-</span>
                <span class="stat-label">Late Today</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value" id="absentToday">-</span>
                <span class="stat-label">Absent Today</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-btn active" data-tab="attendance">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Attendance
            </button>
            <button class="tab-btn" data-tab="leaves">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="9" y1="15" x2="15" y2="15"/>
                </svg>
                Leave Requests
            </button>
            <button class="tab-btn" data-tab="remarks">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                </svg>
                Remarks
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="date-filter">
        <div class="filter-group">
            <label>Month</label>
            <select id="filterMonth">
                <?php
                $months = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
                $currentMonth = date('n');
                foreach ($months as $i => $month) {
                    $selected = ($i + 1 == $currentMonth) ? 'selected' : '';
                    echo "<option value='" . ($i + 1) . "' $selected>$month</option>";
                }
                ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Year</label>
            <select id="filterYear">
                <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
                    $selected = ($y == $currentYear) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
        </div>
        <button class="btn btn-primary" onclick="loadTabContent()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23,4 23,10 17,10"/>
                <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
            </svg>
            Refresh
        </button>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Attendance Tab -->
        <div id="attendanceTab" class="tab-pane active">
            <div id="attendanceContainer" class="team-list">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading team attendance...</p>
                </div>
            </div>
        </div>

        <!-- Leave Requests Tab -->
        <div id="leavesTab" class="tab-pane" style="display: none;">
            <div id="leavesContainer" class="leaves-list">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading leave requests...</p>
                </div>
            </div>
        </div>

        <!-- Remarks Tab -->
        <div id="remarksTab" class="tab-pane" style="display: none;">
            <div id="remarksContainer" class="remarks-list">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading remarks...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Detail Modal -->
<div id="employeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container employee-modal">
        <div class="modal-header">
            <div class="employee-header-info">
                <div class="employee-avatar" id="modalAvatar">JS</div>
                <div>
                    <h2 id="modalEmployeeName">Employee Name</h2>
                    <p id="modalEmployeeRole" class="text-muted">Designation</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeEmployeeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="employeeAttendanceDetail" class="attendance-detail">
                <!-- Attendance details will be loaded here -->
            </div>

            <!-- Manager Actions Section -->
            <div class="manager-actions-section">
                <h4 class="section-title">Manager Actions</h4>

                <!-- Date Selector -->
                <div class="form-group">
                    <label class="form-label">Select Date</label>
                    <input type="date" id="actionDate" class="form-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Action Tabs -->
                <div class="action-tabs">
                    <button class="action-tab active" data-action="attendance" onclick="switchActionTab('attendance')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                        Mark Attendance
                    </button>
                    <button class="action-tab" data-action="remark" onclick="switchActionTab('remark')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        Add Remark
                    </button>
                </div>

                <!-- Mark Attendance Form -->
                <div id="attendanceForm" class="action-form">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="attendanceStatus" class="form-input">
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="half_day">Half Day</option>
                            <option value="leave">On Leave</option>
                        </select>
                    </div>
                    <div class="form-row" id="timeInputs">
                        <div class="form-group">
                            <label class="form-label">Check In</label>
                            <input type="time" id="checkInTime" class="form-input" value="09:30">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Check Out</label>
                            <input type="time" id="checkOutTime" class="form-input" value="18:30">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea id="attendanceRemarks" class="form-input form-textarea" rows="2" placeholder="Add any notes about this attendance..."></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" onclick="submitAttendance()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Save Attendance
                    </button>
                </div>

                <!-- Add Remark Form -->
                <div id="remarkForm" class="action-form" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Remark Type</label>
                        <select id="remarkType" class="form-input">
                            <option value="manager_note">General Note</option>
                            <option value="late_warning">Late Warning</option>
                            <option value="absent_notice">Absent Notice</option>
                            <option value="performance">Performance Note</option>
                            <option value="appreciation">Appreciation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remark</label>
                        <textarea id="remarkText" class="form-input form-textarea" rows="3" placeholder="Enter your remark..." required></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" onclick="submitRemark()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        Add Remark
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.team-page {
    padding: 1rem;
    padding-bottom: 100px;
}

.page-header {
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.page-header .subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

/* Team Stats */
.team-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon svg {
    width: 20px;
    height: 20px;
}

.stat-icon.present {
    background: rgba(245, 158, 11, 0.15);
    color: var(--accent);
}

.stat-icon.success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.stat-icon.warning {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.stat-icon.danger {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    font-family: 'JetBrains Mono', monospace;
}

.stat-label {
    font-size: 0.6875rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Tabs */
.tabs-container {
    margin-bottom: 1rem;
}

.tabs {
    display: flex;
    gap: 0.5rem;
    background: var(--surface-bg);
    padding: 0.375rem;
    border-radius: 12px;
    overflow-x: auto;
}

.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.tab-btn svg {
    width: 16px;
    height: 16px;
}

.tab-btn:hover {
    color: var(--text-primary);
}

.tab-btn.active {
    background: var(--card-bg);
    color: var(--accent);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Date Filter */
.date-filter {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    flex: 1;
    min-width: 100px;
}

.filter-group label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group select {
    padding: 0.625rem 0.875rem;
    background: var(--surface-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 0.875rem;
    cursor: pointer;
}

.filter-group select:focus {
    outline: none;
    border-color: var(--accent);
}

.date-filter .btn {
    padding: 0.625rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
}

.date-filter .btn svg {
    width: 16px;
    height: 16px;
}

/* Team List */
.team-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.team-member-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.team-member-card:hover {
    border-color: var(--accent);
    transform: translateX(4px);
}

.member-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #ffffff;
    border: 1px solid #1f2937;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    flex-shrink: 0;
}

.member-info {
    flex: 1;
    min-width: 0;
}

.member-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.member-role {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
}

.member-status {
    padding: 0.25rem 0.625rem;
    border-radius: 100px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.member-status.present {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.member-status.late {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.member-status.absent {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.member-status.leave {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.member-stats {
    display: flex;
    gap: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}

.member-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.member-stat-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    font-family: 'JetBrains Mono', monospace;
}

.member-stat-label {
    font-size: 0.625rem;
    color: var(--text-muted);
    text-transform: uppercase;
}

/* Leave Requests */
.leaves-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.leave-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
}

.leave-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.leave-employee {
    display: flex;
    align-items: center;
    gap: 0.625rem;
}

.leave-employee-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid #1f2937;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #1f2937;
}

.leave-employee-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
}

.leave-status {
    padding: 0.25rem 0.625rem;
    border-radius: 100px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
}

.leave-status.pending {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.leave-status.approved {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.leave-status.rejected {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.leave-details {
    background: var(--surface-bg);
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
}

.leave-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.375rem 0;
    font-size: 0.8125rem;
}

.leave-detail-row:not(:last-child) {
    border-bottom: 1px solid var(--border-color);
}

.leave-detail-label {
    color: var(--text-muted);
}

.leave-detail-value {
    color: var(--text-primary);
    font-weight: 500;
}

.leave-reason {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    font-style: italic;
    margin-bottom: 0.75rem;
}

.leave-actions {
    display: flex;
    gap: 0.5rem;
}

.leave-actions .btn {
    flex: 1;
    padding: 0.5rem;
    font-size: 0.8125rem;
}

/* Remarks List */
.remarks-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.remark-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
}

.remark-header {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    margin-bottom: 0.75rem;
}

.remark-date {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-left: auto;
}

.remark-issue {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.remark-issue .issue-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
}

.remark-issue .issue-badge.late {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.remark-issue .issue-badge.absent {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.remark-text {
    font-size: 0.875rem;
    color: var(--text-primary);
    line-height: 1.5;
    padding: 0.75rem;
    background: var(--surface-bg);
    border-radius: 8px;
}

/* Loading & Empty States */
.loading-state, .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--text-muted);
    text-align: center;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--border-color);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state svg {
    width: 48px;
    height: 48px;
    opacity: 0.5;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    font-size: 0.875rem;
    margin: 0;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-container {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}

.employee-header-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.employee-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #1f2937;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.employee-header-info h2 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.employee-header-info .text-muted {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin: 0;
}

.modal-close {
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}

.modal-close svg {
    width: 18px;
    height: 18px;
}

.modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    flex: 1;
}

.attendance-detail .detail-section {
    margin-bottom: 1.25rem;
}

.attendance-detail .section-title {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
}

.attendance-detail .summary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.attendance-detail .summary-item {
    background: var(--surface-bg);
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
}

.attendance-detail .summary-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    font-family: 'JetBrains Mono', monospace;
}

.attendance-detail .summary-label {
    font-size: 0.6875rem;
    color: var(--text-muted);
    text-transform: uppercase;
}

/* Buttons */
.btn {
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-primary {
    background: var(--accent);
    color: #000;
}

.btn-primary:hover {
    background: var(--accent-hover);
}

.btn-secondary {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: var(--hover-bg);
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

.btn-block {
    width: 100%;
}

/* Manager Actions Section */
.manager-actions-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.manager-actions-section .section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.action-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.action-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 0.75rem;
    background: var(--surface-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}

.action-tab svg {
    width: 16px;
    height: 16px;
}

.action-tab:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}

.action-tab.active {
    background: var(--accent);
    border-color: var(--accent);
    color: #000;
}

.action-form {
    background: var(--surface-bg);
    border-radius: 10px;
    padding: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-muted);
    margin-bottom: 0.375rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    color: var(--text-primary);
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    outline: none;
    transition: all 0.2s;
    font-family: inherit;
}

.form-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
}

.form-textarea {
    resize: vertical;
    min-height: 60px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.btn svg {
    width: 16px;
    height: 16px;
}

/* Toast notification */
.toast {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--text-primary);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    z-index: 1100;
    animation: toastIn 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toast.success {
    background: #10b981;
}

.toast.error {
    background: #ef4444;
}

@keyframes toastIn {
    from {
        transform: translateX(-50%) translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}

/* Responsive */
@media (min-width: 600px) {
    .team-stats {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 480px) {
    .date-filter {
        flex-direction: column;
    }

    .filter-group {
        width: 100%;
    }

    .date-filter .btn {
        width: 100%;
    }

    .tabs {
        gap: 0.25rem;
    }

    .tab-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
    }

    .tab-btn svg {
        display: none;
    }
}
</style>

<script>
let currentTab = 'attendance';
let teamData = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    loadTeamStats();
    loadTabContent();
});

function initializeTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            switchTab(targetTab);
        });
    });
}

function switchTab(tabName) {
    currentTab = tabName;

    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });

    // Update tab panes
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.style.display = 'none';
    });
    document.getElementById(tabName + 'Tab').style.display = 'block';

    // Load content
    loadTabContent();
}

function loadTeamStats() {
    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php?ajax=getTeamStats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalTeam').textContent = data.stats.totalMembers || 0;
                document.getElementById('presentToday').textContent = data.stats.presentToday || 0;
                document.getElementById('lateToday').textContent = data.stats.lateToday || 0;
                document.getElementById('absentToday').textContent = data.stats.absentToday || 0;
            }
        })
        .catch(console.error);
}

function loadTabContent() {
    const month = document.getElementById('filterMonth').value;
    const year = document.getElementById('filterYear').value;

    switch (currentTab) {
        case 'attendance':
            loadTeamAttendance(month, year);
            break;
        case 'leaves':
            loadLeaveRequests(month, year);
            break;
        case 'remarks':
            loadRemarks(month, year);
            break;
    }
}

function loadTeamAttendance(month, year) {
    const container = document.getElementById('attendanceContainer');
    container.innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Loading team attendance...</p></div>';

    fetch(`<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php?ajax=getTeamAttendance&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.team && data.team.length > 0) {
                teamData = data.team;
                renderTeamAttendance(data.team);
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                        </svg>
                        <h3>No Team Members</h3>
                        <p>You don't have any team members assigned.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="empty-state"><h3>Error</h3><p>Failed to load team attendance</p></div>';
        });
}

function renderTeamAttendance(team) {
    const container = document.getElementById('attendanceContainer');
    container.innerHTML = team.map(member => `
        <div class="team-member-card" onclick="viewEmployeeDetail(${member.userID})">
            <div class="member-header">
                <div class="member-avatar">${getInitials(member.userName)}</div>
                <div class="member-info">
                    <h3 class="member-name">${escapeHtml(member.userName)}</h3>
                    <p class="member-role">${escapeHtml(member.designation || 'Employee')}</p>
                </div>
                <span class="member-status ${member.todayStatus}">${member.todayStatus}</span>
            </div>
            <div class="member-stats">
                <div class="member-stat">
                    <span class="member-stat-value">${member.presentDays || 0}</span>
                    <span class="member-stat-label">Present</span>
                </div>
                <div class="member-stat">
                    <span class="member-stat-value">${member.absentDays || 0}</span>
                    <span class="member-stat-label">Absent</span>
                </div>
                <div class="member-stat">
                    <span class="member-stat-value">${member.lateDays || 0}</span>
                    <span class="member-stat-label">Late</span>
                </div>
                <div class="member-stat">
                    <span class="member-stat-value">${member.leaveDays || 0}</span>
                    <span class="member-stat-label">Leave</span>
                </div>
            </div>
        </div>
    `).join('');
}

function loadLeaveRequests(month, year) {
    const container = document.getElementById('leavesContainer');
    container.innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Loading leave requests...</p></div>';

    fetch(`<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php?ajax=getTeamLeaveRequests&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.leaves && data.leaves.length > 0) {
                renderLeaveRequests(data.leaves);
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                        </svg>
                        <h3>No Leave Requests</h3>
                        <p>No leave requests for this period.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="empty-state"><h3>Error</h3><p>Failed to load leave requests</p></div>';
        });
}

function renderLeaveRequests(leaves) {
    const container = document.getElementById('leavesContainer');
    container.innerHTML = leaves.map(leave => `
        <div class="leave-card">
            <div class="leave-header">
                <div class="leave-employee">
                    <div class="leave-employee-avatar">${getInitials(leave.userName)}</div>
                    <span class="leave-employee-name">${escapeHtml(leave.userName)}</span>
                </div>
                <span class="leave-status ${leave.status}">${leave.status}</span>
            </div>
            <div class="leave-details">
                <div class="leave-detail-row">
                    <span class="leave-detail-label">Leave Type</span>
                    <span class="leave-detail-value">${escapeHtml(leave.leaveType)}</span>
                </div>
                <div class="leave-detail-row">
                    <span class="leave-detail-label">Duration</span>
                    <span class="leave-detail-value">${leave.fromDate} - ${leave.toDate}</span>
                </div>
                <div class="leave-detail-row">
                    <span class="leave-detail-label">Days</span>
                    <span class="leave-detail-value">${leave.totalDays} day(s)</span>
                </div>
            </div>
            ${leave.reason ? `<p class="leave-reason">"${escapeHtml(leave.reason)}"</p>` : ''}
            ${leave.status === 'pending' ? `
                <div class="leave-actions">
                    <button class="btn btn-success" onclick="approveLeave(${leave.leaveID})">Approve</button>
                    <button class="btn btn-danger" onclick="rejectLeave(${leave.leaveID})">Reject</button>
                </div>
            ` : ''}
        </div>
    `).join('');
}

function loadRemarks(month, year) {
    const container = document.getElementById('remarksContainer');
    container.innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Loading remarks...</p></div>';

    fetch(`<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php?ajax=getTeamRemarks&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.remarks && data.remarks.length > 0) {
                renderRemarks(data.remarks);
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                        </svg>
                        <h3>No Remarks</h3>
                        <p>No attendance remarks for this period.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="empty-state"><h3>Error</h3><p>Failed to load remarks</p></div>';
        });
}

function renderRemarks(remarks) {
    const container = document.getElementById('remarksContainer');
    container.innerHTML = remarks.map(remark => `
        <div class="remark-card">
            <div class="remark-header">
                <div class="member-avatar" style="width:32px;height:32px;font-size:0.75rem;">${getInitials(remark.userName)}</div>
                <span class="member-name" style="font-size:0.875rem;">${escapeHtml(remark.userName)}</span>
                <span class="remark-date">${formatDate(remark.attendanceDate)}</span>
            </div>
            <div class="remark-issue">
                <span class="issue-badge ${remark.issueType}">${remark.issueType}</span>
                <span>${remark.issueDetails || ''}</span>
            </div>
            <div class="remark-text">${escapeHtml(remark.remark)}</div>
        </div>
    `).join('');
}

let selectedEmployeeID = null;

function viewEmployeeDetail(userID) {
    const member = teamData?.find(m => m.userID === userID);
    if (!member) return;

    selectedEmployeeID = userID;

    document.getElementById('modalAvatar').textContent = getInitials(member.userName);
    document.getElementById('modalEmployeeName').textContent = member.userName;
    document.getElementById('modalEmployeeRole').textContent = member.designation || 'Employee';

    const detailContainer = document.getElementById('employeeAttendanceDetail');
    detailContainer.innerHTML = `
        <div class="detail-section">
            <h4 class="section-title">Monthly Summary</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">${member.presentDays || 0}</div>
                    <div class="summary-label">Present</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${member.absentDays || 0}</div>
                    <div class="summary-label">Absent</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${member.lateDays || 0}</div>
                    <div class="summary-label">Late</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${member.leaveDays || 0}</div>
                    <div class="summary-label">Leave</div>
                </div>
            </div>
        </div>
        <div class="detail-section">
            <h4 class="section-title">Today's Status</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">${member.checkIn || '-'}</div>
                    <div class="summary-label">Check In</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${member.checkOut || '-'}</div>
                    <div class="summary-label">Check Out</div>
                </div>
            </div>
        </div>
    `;

    // Reset action forms
    document.getElementById('actionDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('attendanceStatus').value = 'present';
    document.getElementById('checkInTime').value = '09:30';
    document.getElementById('checkOutTime').value = '18:30';
    document.getElementById('attendanceRemarks').value = '';
    document.getElementById('remarkType').value = 'manager_note';
    document.getElementById('remarkText').value = '';
    switchActionTab('attendance');

    document.getElementById('employeeModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Switch between action tabs
function switchActionTab(tab) {
    document.querySelectorAll('.action-tab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.action === tab);
    });

    document.getElementById('attendanceForm').style.display = tab === 'attendance' ? 'block' : 'none';
    document.getElementById('remarkForm').style.display = tab === 'remark' ? 'block' : 'none';
}

// Toggle time inputs based on attendance status
document.getElementById('attendanceStatus')?.addEventListener('change', function() {
    const timeInputs = document.getElementById('timeInputs');
    if (this.value === 'absent' || this.value === 'leave') {
        timeInputs.style.display = 'none';
    } else {
        timeInputs.style.display = 'grid';
    }
});

// Load attendance when date changes
document.getElementById('actionDate')?.addEventListener('change', function() {
    if (selectedEmployeeID) {
        loadAttendanceForDate(selectedEmployeeID, this.value);
    }
});

// Fetch attendance for specific date
function loadAttendanceForDate(employeeID, date) {
    fetch(`<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php?ajax=getTeamMemberAttendance&employeeID=${employeeID}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const att = data.attendance;
                if (att) {
                    // Populate form with existing attendance
                    document.getElementById('attendanceStatus').value = att.attendanceStatus || 'present';
                    document.getElementById('checkInTime').value = att.checkIn ? extractTimeForInput(att.checkIn) : '09:30';
                    document.getElementById('checkOutTime').value = att.checkOut ? extractTimeForInput(att.checkOut) : '18:30';

                    // Toggle time inputs visibility
                    const timeInputs = document.getElementById('timeInputs');
                    if (att.attendanceStatus === 'absent' || att.attendanceStatus === 'leave') {
                        timeInputs.style.display = 'none';
                    } else {
                        timeInputs.style.display = 'grid';
                    }

                    // Update display in popup
                    updateAttendanceDisplay(att);
                } else {
                    // No attendance record - reset to defaults
                    document.getElementById('attendanceStatus').value = 'present';
                    document.getElementById('checkInTime').value = '09:30';
                    document.getElementById('checkOutTime').value = '18:30';
                    document.getElementById('timeInputs').style.display = 'grid';
                    updateAttendanceDisplay(null);
                }
            }
        })
        .catch(console.error);
}

// Update the attendance display in the popup
function updateAttendanceDisplay(att) {
    const checkInDisplay = att?.checkIn ? formatTime(att.checkIn) : '-';
    const checkOutDisplay = att?.checkOut ? formatTime(att.checkOut) : '-';
    const statusDisplay = att ? (att.attendanceStatus === 'present' ? (att.isLate ? 'Late' : 'Present') : att.attendanceStatus) : 'No record';

    const dateLabel = document.getElementById('actionDate').value;
    const formattedDate = new Date(dateLabel).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });

    const detailContainer = document.getElementById('employeeAttendanceDetail');
    const member = teamData?.find(m => m.userID === selectedEmployeeID);

    detailContainer.innerHTML = `
        <div class="detail-section">
            <h4 class="section-title">Monthly Summary</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">${member?.presentDays || 0}</div>
                    <div class="summary-label">Present</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${member?.absentDays || 0}</div>
                    <div class="summary-label">Absent</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${member?.lateDays || 0}</div>
                    <div class="summary-label">Late</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${member?.leaveDays || 0}</div>
                    <div class="summary-label">Leave</div>
                </div>
            </div>
        </div>
        <div class="detail-section">
            <h4 class="section-title">Status for ${formattedDate}</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">${checkInDisplay}</div>
                    <div class="summary-label">Check In</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">${checkOutDisplay}</div>
                    <div class="summary-label">Check Out</div>
                </div>
            </div>
            <div style="text-align:center;margin-top:0.5rem;font-size:0.8125rem;color:var(--text-muted);">
                Status: <span style="font-weight:600;color:var(--text-primary);text-transform:capitalize;">${statusDisplay}</span>
            </div>
        </div>
    `;
}

// Format time/datetime string to h:mm A for display
function formatTime(timeStr) {
    if (!timeStr) return '-';
    // Handle both datetime (2026-01-05 10:39:37) and time (10:39:37) formats
    const date = new Date(timeStr.replace(' ', 'T')); // Convert to ISO format for proper parsing
    if (!isNaN(date.getTime())) {
        // Valid date - format it
        let hours = date.getHours();
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }
    // Fallback for time-only string (HH:MM:SS)
    const parts = timeStr.split(' ');
    const timePart = parts.length > 1 ? parts[1] : parts[0];
    const [hrs, mins] = timePart.split(':');
    const h = parseInt(hrs);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 || 12;
    return `${hour12}:${mins} ${ampm}`;
}

// Extract time in HH:MM format for input fields from datetime string
function extractTimeForInput(datetimeStr) {
    if (!datetimeStr) return '09:30';
    // Handle datetime format: "2026-01-05 10:39:37" or "10:39:37"
    const parts = datetimeStr.split(' ');
    const timePart = parts.length > 1 ? parts[1] : parts[0];
    return timePart.substring(0, 5); // Return HH:MM
}

// Submit attendance
function submitAttendance() {
    if (!selectedEmployeeID) {
        showToast('No employee selected', 'error');
        return;
    }

    const attendanceDate = document.getElementById('actionDate').value;
    const attendanceStatus = document.getElementById('attendanceStatus').value;
    const checkIn = document.getElementById('checkInTime').value;
    const checkOut = document.getElementById('checkOutTime').value;
    const remarks = document.getElementById('attendanceRemarks').value;

    const formData = new URLSearchParams();
    formData.append('xAction', 'markTeamAttendance');
    formData.append('employeeID', selectedEmployeeID);
    formData.append('attendanceDate', attendanceDate);
    formData.append('attendanceStatus', attendanceStatus);
    if (attendanceStatus !== 'absent' && attendanceStatus !== 'leave') {
        formData.append('checkIn', checkIn);
        formData.append('checkOut', checkOut);
    }
    if (remarks) {
        formData.append('remarks', remarks);
    }

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.err === 0) {
            showToast(data.msg || 'Attendance saved successfully', 'success');
            document.getElementById('attendanceRemarks').value = '';
            // Refresh team data
            loadTeamStats();
            loadTabContent();
        } else {
            showToast(data.msg || 'Failed to save attendance', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to save attendance', 'error');
    });
}

// Submit remark
function submitRemark() {
    if (!selectedEmployeeID) {
        showToast('No employee selected', 'error');
        return;
    }

    const attendanceDate = document.getElementById('actionDate').value;
    const remarkType = document.getElementById('remarkType').value;
    const remarks = document.getElementById('remarkText').value.trim();

    if (!remarks) {
        showToast('Please enter a remark', 'error');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('xAction', 'addTeamRemark');
    formData.append('employeeID', selectedEmployeeID);
    formData.append('attendanceDate', attendanceDate);
    formData.append('remarkType', remarkType);
    formData.append('remarks', remarks);

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.err === 0) {
            showToast(data.msg || 'Remark added successfully', 'success');
            document.getElementById('remarkText').value = '';
            // Refresh remarks tab if we're on it
            if (currentTab === 'remarks') {
                loadTabContent();
            }
        } else {
            showToast(data.msg || 'Failed to add remark', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to add remark', 'error');
    });
}

// Toast notification
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function closeEmployeeModal() {
    document.getElementById('employeeModal').style.display = 'none';
    document.body.style.overflow = '';
}

function approveLeave(leaveID) {
    if (!confirm('Approve this leave request?')) return;

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `xAction=approveLeave&leaveID=${leaveID}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTabContent();
        } else {
            alert(data.message || 'Failed to approve leave');
        }
    })
    .catch(console.error);
}

function rejectLeave(leaveID) {
    const reason = prompt('Reason for rejection (optional):');
    if (reason === null) return;

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `xAction=rejectLeave&leaveID=${leaveID}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTabContent();
        } else {
            alert(data.message || 'Failed to reject leave');
        }
    })
    .catch(console.error);
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Modal close handlers
document.getElementById('employeeModal').addEventListener('click', function(e) {
    if (e.target === this) closeEmployeeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEmployeeModal();
});
</script>
