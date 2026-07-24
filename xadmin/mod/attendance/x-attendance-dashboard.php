<?php
/**
 * Attendance Master Dashboard
 * Real-time attendance overview with KPIs, charts, and quick actions
 */

// Get today's date info
$today = date('Y-m-d');
$currentMonth = date('n');
$currentYear = date('Y');
$monthName = date('F Y');

// Get departments for filter
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT deptID, deptName FROM " . $DB->pre . "departments WHERE status=? ORDER BY deptName";
$departments = $DB->dbRows();

// Get shifts for filter
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT shiftID, shiftName, shiftCode FROM " . $DB->pre . "shift_master WHERE status=? ORDER BY shiftName";
$shifts = $DB->dbRows();
?>

<style>
/* Dashboard Layout */
.att-dashboard {
    padding: 0;
}

.dash-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}

.dash-header-left h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.dash-header-left p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.dash-header-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.dash-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
}

.dash-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.dash-btn.primary {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}

.dash-btn.primary:hover {
    background: #2563eb;
}

.dash-btn svg {
    width: 18px;
    height: 18px;
}

/* Filter Bar */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding: 16px 20px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 24px;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-label {
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
}

.filter-select, .filter-input {
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
    min-width: 140px;
}

.filter-select:focus, .filter-input:focus {
    border-color: #3b82f6;
    background: #fff;
}

.filter-btn {
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    border: none;
}

.filter-btn.apply {
    background: #3b82f6;
    color: #fff;
}

.filter-btn.reset {
    background: #f1f5f9;
    color: #64748b;
}

/* KPI Cards Row */
.kpi-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 1200px) {
    .kpi-row { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .kpi-row { grid-template-columns: repeat(2, 1fr); }
}

.kpi-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--kpi-color, #3b82f6);
}

.kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    background: var(--kpi-bg, #eff6ff);
}

.kpi-icon svg {
    width: 22px;
    height: 22px;
    color: var(--kpi-color, #3b82f6);
}

.kpi-value {
    font-size: 32px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 4px;
}

.kpi-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.kpi-trend.up {
    background: #dcfce7;
    color: #16a34a;
}

.kpi-trend.down {
    background: #fee2e2;
    color: #dc2626;
}

.kpi-card.total { --kpi-color: #6366f1; --kpi-bg: #eef2ff; }
.kpi-card.present { --kpi-color: #22c55e; --kpi-bg: #dcfce7; }
.kpi-card.absent { --kpi-color: #ef4444; --kpi-bg: #fee2e2; }
.kpi-card.late { --kpi-color: #f59e0b; --kpi-bg: #fef3c7; }
.kpi-card.leave { --kpi-color: #3b82f6; --kpi-bg: #dbeafe; }

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}

@media (max-width: 1024px) {
    .charts-grid { grid-template-columns: 1fr; }
}

.chart-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
}

.chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.chart-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.chart-actions {
    display: flex;
    gap: 8px;
}

.chart-btn {
    padding: 6px 12px;
    font-size: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    color: #64748b;
    cursor: pointer;
}

.chart-btn.active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}

.chart-body {
    height: 280px;
    position: relative;
}

/* Live Attendance Table */
.live-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 24px;
}

.live-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.live-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #dcfce7;
    color: #16a34a;
    font-size: 11px;
    font-weight: 600;
    border-radius: 20px;
}

.live-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #16a34a;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.live-table {
    width: 100%;
    border-collapse: collapse;
}

.live-table th {
    padding: 12px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.live-table td {
    padding: 14px 16px;
    font-size: 14px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
}

.live-table tr:last-child td {
    border-bottom: none;
}

.live-table tr:hover {
    background: #f8fafc;
}

.emp-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.emp-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}

.emp-info strong {
    display: block;
    font-weight: 600;
    color: #1e293b;
}

.emp-info span {
    font-size: 12px;
    color: #94a3b8;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 20px;
    text-transform: uppercase;
}

.status-badge.present { background: #dcfce7; color: #16a34a; }
.status-badge.absent { background: #fee2e2; color: #dc2626; }
.status-badge.late { background: #fef3c7; color: #d97706; }
.status-badge.leave { background: #dbeafe; color: #2563eb; }
.status-badge.half-day { background: #fae8ff; color: #a855f7; }

.time-cell {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
}

.time-cell.late {
    color: #d97706;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .quick-actions { grid-template-columns: repeat(2, 1fr); }
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-action:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.quick-action-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-action-icon svg {
    width: 20px;
    height: 20px;
    color: #64748b;
}

.quick-action-text {
    flex: 1;
}

.quick-action-text strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.quick-action-text span {
    font-size: 12px;
    color: #94a3b8;
}

/* Alert Cards */
.alerts-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 1024px) {
    .alerts-row { grid-template-columns: 1fr; }
}

.alert-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
}

.alert-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.alert-card-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.alert-card-title svg {
    width: 18px;
    height: 18px;
}

.alert-count {
    background: #fee2e2;
    color: #dc2626;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.alert-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.alert-item:last-child {
    border-bottom: none;
}

.alert-item-avatar {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
}

.alert-item-text {
    flex: 1;
    font-size: 13px;
    color: #475569;
}

.alert-item-text strong {
    color: #1e293b;
}

.alert-item-time {
    font-size: 11px;
    color: #94a3b8;
}

/* Export Modal */
.export-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.export-modal.show {
    display: flex;
}

.export-modal-content {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    overflow: auto;
}

.export-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.export-modal-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.export-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f1f5f9;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.export-modal-body {
    padding: 24px;
}

.export-option {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.export-option:hover {
    border-color: #3b82f6;
}

.export-option.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}

.export-option-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.export-option-icon.excel { background: #dcfce7; color: #16a34a; }
.export-option-icon.pdf { background: #fee2e2; color: #dc2626; }
.export-option-icon.csv { background: #e0f2fe; color: #0284c7; }

.export-option-icon svg {
    width: 24px;
    height: 24px;
}

.export-option-info strong {
    display: block;
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.export-option-info span {
    font-size: 13px;
    color: #64748b;
}

.export-form-group {
    margin-bottom: 16px;
}

.export-form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    margin-bottom: 6px;
}

.export-form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

.export-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Loading State */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
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
</style>

<div class="att-dashboard">
    <!-- Header -->
    <div class="dash-header">
        <div class="dash-header-left">
            <h1>Attendance Dashboard</h1>
            <p>Real-time attendance monitoring for <?php echo $monthName; ?></p>
        </div>
        <div class="dash-header-actions">
            <button class="dash-btn" onclick="refreshDashboard()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                </svg>
                Refresh
            </button>
            <button class="dash-btn primary" onclick="showExportModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export Report
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label class="filter-label">Date:</label>
            <input type="date" id="filterDate" class="filter-input" value="<?php echo $today; ?>">
        </div>
        <div class="filter-group">
            <label class="filter-label">Department:</label>
            <select id="filterDept" class="filter-select">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['deptID']; ?>"><?php echo htmlspecialchars($dept['deptName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Shift:</label>
            <select id="filterShift" class="filter-select">
                <option value="">All Shifts</option>
                <?php foreach ($shifts as $shift): ?>
                <option value="<?php echo $shift['shiftID']; ?>"><?php echo htmlspecialchars($shift['shiftName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Status:</label>
            <select id="filterStatus" class="filter-select">
                <option value="">All Status</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="leave">On Leave</option>
                <option value="half_day">Half Day</option>
            </select>
        </div>
        <button class="filter-btn apply" onclick="applyFilters()">Apply</button>
        <button class="filter-btn reset" onclick="resetFilters()">Reset</button>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-row" id="kpiRow">
        <div class="kpi-card total">
            <div class="kpi-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <div class="kpi-value" id="kpiTotal">--</div>
            <div class="kpi-label">Total Employees</div>
        </div>
        <div class="kpi-card present">
            <div class="kpi-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="kpi-value" id="kpiPresent">--</div>
            <div class="kpi-label">Present Today</div>
            <div class="kpi-trend up" id="kpiPresentTrend">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                </svg>
                <span>--%</span>
            </div>
        </div>
        <div class="kpi-card absent">
            <div class="kpi-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <div class="kpi-value" id="kpiAbsent">--</div>
            <div class="kpi-label">Absent Today</div>
        </div>
        <div class="kpi-card late">
            <div class="kpi-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="kpi-value" id="kpiLate">--</div>
            <div class="kpi-label">Late Arrivals</div>
        </div>
        <div class="kpi-card leave">
            <div class="kpi-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div class="kpi-value" id="kpiLeave">--</div>
            <div class="kpi-label">On Leave</div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Attendance Trend</div>
                <div class="chart-actions">
                    <button class="chart-btn active" onclick="setTrendPeriod('week')">Week</button>
                    <button class="chart-btn" onclick="setTrendPeriod('month')">Month</button>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Today's Breakdown</div>
            </div>
            <div class="chart-body">
                <canvas id="breakdownChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="quick-action" onclick="location.href='?mod=attendance&pg=add-edit'">
            <div class="quick-action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </div>
            <div class="quick-action-text">
                <strong>Manual Entry</strong>
                <span>Add attendance manually</span>
            </div>
        </div>
        <div class="quick-action" onclick="location.href='?mod=attendance&pg=remarks'">
            <div class="quick-action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                </svg>
            </div>
            <div class="quick-action-text">
                <strong>Review Remarks</strong>
                <span id="pendingRemarksCount">0 pending</span>
            </div>
        </div>
        <div class="quick-action" onclick="location.href='?mod=attendance&pg=reports'">
            <div class="quick-action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div class="quick-action-text">
                <strong>Generate Reports</strong>
                <span>Daily, Monthly, Payroll</span>
            </div>
        </div>
        <div class="quick-action" onclick="location.href='?mod=attendance&pg=holidays'">
            <div class="quick-action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </div>
            <div class="quick-action-text">
                <strong>Manage Holidays</strong>
                <span>Add/Edit holidays</span>
            </div>
        </div>
    </div>

    <!-- Alerts Row -->
    <div class="alerts-row" style="grid-template-columns: repeat(4, 1fr);">
        <div class="alert-card">
            <div class="alert-card-header">
                <div class="alert-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Late Arrivals Today
                </div>
                <span class="alert-count" id="lateCount">0</span>
            </div>
            <ul class="alert-list" id="lateList">
                <li class="alert-item">
                    <span style="color:#94a3b8;font-size:13px;">No late arrivals today</span>
                </li>
            </ul>
        </div>
        <div class="alert-card">
            <div class="alert-card-header">
                <div class="alert-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    Absent Today
                </div>
                <span class="alert-count" id="absentCount">0</span>
            </div>
            <ul class="alert-list" id="absentList">
                <li class="alert-item">
                    <span style="color:#94a3b8;font-size:13px;">No absences today</span>
                </li>
            </ul>
        </div>
        <div class="alert-card">
            <div class="alert-card-header">
                <div class="alert-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    On Leave Today
                </div>
                <span class="alert-count" style="background:#dbeafe;color:#2563eb;" id="leaveCount">0</span>
            </div>
            <ul class="alert-list" id="leaveList">
                <li class="alert-item">
                    <span style="color:#94a3b8;font-size:13px;">No one on leave today</span>
                </li>
            </ul>
        </div>
        <div class="alert-card">
            <div class="alert-card-header">
                <div class="alert-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    Pending Remarks
                </div>
                <span class="alert-count" id="remarksCount">0</span>
            </div>
            <ul class="alert-list" id="remarksList">
                <li class="alert-item">
                    <span style="color:#94a3b8;font-size:13px;">No pending remarks</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Live Attendance Table -->
    <div class="live-section">
        <div class="live-header">
            <div class="live-title">
                Today's Attendance
                <span class="live-badge">LIVE</span>
            </div>
            <div>
                <input type="text" id="searchEmployee" class="filter-input" placeholder="Search employee..." style="min-width:200px;">
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="live-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Shift</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Working Hrs</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr>
                        <td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">
                            <div class="spinner" style="margin:0 auto 12px;"></div>
                            Loading attendance data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="export-modal" id="exportModal">
    <div class="export-modal-content">
        <div class="export-modal-header">
            <div class="export-modal-title">Export Report</div>
            <button class="export-modal-close" onclick="closeExportModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="export-modal-body">
            <div class="export-option selected" data-format="excel" onclick="selectExportFormat(this)">
                <div class="export-option-icon excel">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="export-option-info">
                    <strong>Excel (.xlsx)</strong>
                    <span>Spreadsheet with formatting</span>
                </div>
            </div>
            <div class="export-option" data-format="pdf" onclick="selectExportFormat(this)">
                <div class="export-option-icon pdf">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="export-option-info">
                    <strong>PDF Report</strong>
                    <span>Print-ready document</span>
                </div>
            </div>
            <div class="export-option" data-format="csv" onclick="selectExportFormat(this)">
                <div class="export-option-icon csv">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="export-option-info">
                    <strong>CSV File</strong>
                    <span>For payroll import</span>
                </div>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

            <div class="export-form-group">
                <label class="export-form-label">Report Type</label>
                <select id="exportReportType" class="export-form-select">
                    <option value="daily">Daily Attendance</option>
                    <option value="monthly">Monthly Muster Roll</option>
                    <option value="payroll">Payroll Summary</option>
                    <option value="late_early">Late/Early Report</option>
                    <option value="overtime">Overtime Report</option>
                    <option value="absenteeism">Absenteeism Report</option>
                </select>
            </div>
            <div class="export-form-group">
                <label class="export-form-label">Date Range</label>
                <div style="display:flex;gap:8px;">
                    <input type="date" id="exportFromDate" class="export-form-select" value="<?php echo date('Y-m-01'); ?>">
                    <input type="date" id="exportToDate" class="export-form-select" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="export-form-group">
                <label class="export-form-label">Department</label>
                <select id="exportDept" class="export-form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['deptID']; ?>"><?php echo htmlspecialchars($dept['deptName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="export-modal-footer">
            <button class="dash-btn" onclick="closeExportModal()">Cancel</button>
            <button class="dash-btn primary" onclick="generateExport()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var trendChart, breakdownChart;
var selectedExportFormat = 'excel';

$(document).ready(function() {
    loadDashboardData();
    initCharts();

    // Auto-refresh every 60 seconds
    setInterval(loadDashboardData, 60000);

    // Search filter
    $('#searchEmployee').on('input', function() {
        filterTable($(this).val());
    });
});

function loadDashboardData() {
    var date = $('#filterDate').val();
    var deptID = $('#filterDept').val();
    var shiftID = $('#filterShift').val();
    var status = $('#filterStatus').val();

    $.ajax({
        url: '<?php echo SITEURL; ?>/xadmin/mod/attendance/x-attendance-api.php',
        type: 'POST',
        data: {
            action: 'getDashboardData',
            date: date,
            deptID: deptID,
            shiftID: shiftID,
            status: status
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                updateKPIs(res.kpis);
                updateAlerts(res.alerts);
                updateTable(res.attendance);
                updateCharts(res.chartData);
            }
        },
        error: function() {
            console.error('Failed to load dashboard data');
        }
    });
}

function updateKPIs(kpis) {
    $('#kpiTotal').text(kpis.total || 0);
    $('#kpiPresent').text(kpis.present || 0);
    $('#kpiAbsent').text(kpis.absent || 0);
    $('#kpiLate').text(kpis.late || 0);
    $('#kpiLeave').text(kpis.leave || 0);

    var presentPct = kpis.total > 0 ? Math.round((kpis.present / kpis.total) * 100) : 0;
    $('#kpiPresentTrend span').text(presentPct + '%');
}

function updateAlerts(alerts) {
    // Late arrivals
    $('#lateCount').text(alerts.late ? alerts.late.length : 0);
    if (alerts.late && alerts.late.length > 0) {
        var lateHtml = '';
        alerts.late.slice(0, 5).forEach(function(item) {
            lateHtml += '<li class="alert-item">' +
                '<div class="alert-item-avatar">' + getInitials(item.name) + '</div>' +
                '<div class="alert-item-text"><strong>' + item.name + '</strong> - ' + item.lateBy + ' mins late</div>' +
                '<div class="alert-item-time">' + item.checkIn + '</div>' +
                '</li>';
        });
        $('#lateList').html(lateHtml);
    }

    // Absent
    $('#absentCount').text(alerts.absent ? alerts.absent.length : 0);
    if (alerts.absent && alerts.absent.length > 0) {
        var absentHtml = '';
        alerts.absent.slice(0, 5).forEach(function(item) {
            absentHtml += '<li class="alert-item">' +
                '<div class="alert-item-avatar">' + getInitials(item.name) + '</div>' +
                '<div class="alert-item-text"><strong>' + item.name + '</strong> - ' + (item.department || 'N/A') + '</div>' +
                '</li>';
        });
        $('#absentList').html(absentHtml);
    }

    // On Leave
    $('#leaveCount').text(alerts.leave ? alerts.leave.length : 0);
    if (alerts.leave && alerts.leave.length > 0) {
        var leaveHtml = '';
        alerts.leave.slice(0, 5).forEach(function(item) {
            leaveHtml += '<li class="alert-item">' +
                '<div class="alert-item-avatar">' + getInitials(item.name) + '</div>' +
                '<div class="alert-item-text"><strong>' + item.name + '</strong> - ' + (item.leaveType || 'Leave') + '</div>' +
                '</li>';
        });
        $('#leaveList').html(leaveHtml);
    } else {
        $('#leaveList').html('<li class="alert-item"><span style="color:#94a3b8;font-size:13px;">No one on leave today</span></li>');
    }

    // Remarks
    $('#remarksCount').text(alerts.remarks ? alerts.remarks.length : 0);
    $('#pendingRemarksCount').text((alerts.remarks ? alerts.remarks.length : 0) + ' pending');
    if (alerts.remarks && alerts.remarks.length > 0) {
        var remarksHtml = '';
        alerts.remarks.slice(0, 5).forEach(function(item) {
            remarksHtml += '<li class="alert-item">' +
                '<div class="alert-item-avatar">' + getInitials(item.name) + '</div>' +
                '<div class="alert-item-text"><strong>' + item.name + '</strong> - ' + item.type + '</div>' +
                '<div class="alert-item-time">' + item.date + '</div>' +
                '</li>';
        });
        $('#remarksList').html(remarksHtml);
    }
}

function updateTable(attendance) {
    if (!attendance || attendance.length === 0) {
        $('#attendanceTableBody').html('<tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">No attendance records for selected filters</td></tr>');
        return;
    }

    var html = '';
    attendance.forEach(function(row) {
        var statusClass = row.status.toLowerCase().replace(' ', '-');
        // Map "on-leave" to "leave" for CSS class
        if (statusClass === 'on-leave') statusClass = 'leave';
        var isLate = row.isLate == 1;

        html += '<tr data-name="' + row.name.toLowerCase() + '">' +
            '<td>' +
                '<div class="emp-cell">' +
                    '<div class="emp-avatar">' + getInitials(row.name) + '</div>' +
                    '<div class="emp-info">' +
                        '<strong>' + row.name + '</strong>' +
                        '<span>' + (row.empCode || '') + '</span>' +
                    '</div>' +
                '</div>' +
            '</td>' +
            '<td>' + (row.department || '-') + '</td>' +
            '<td>' + (row.shift || 'General') + '</td>' +
            '<td class="time-cell' + (isLate ? ' late' : '') + '">' + (row.checkIn || '--:--') + (isLate ? ' <span style="color:#d97706;font-size:10px;">+' + row.lateMinutes + 'm</span>' : '') + '</td>' +
            '<td class="time-cell">' + (row.checkOut || '--:--') + '</td>' +
            '<td>' + (row.workingHours ? row.workingHours + ' hrs' : '-') + '</td>' +
            '<td><span class="status-badge ' + statusClass + '">' + row.status + '</span></td>' +
            '<td>' +
                '<button class="dash-btn" style="padding:6px 10px;font-size:12px;" onclick="viewEmployee(' + row.userID + ')">View</button>' +
            '</td>' +
            '</tr>';
    });

    $('#attendanceTableBody').html(html);
}

function initCharts() {
    // Trend Chart
    var trendCtx = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Present',
                data: [],
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Absent',
                data: [],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Late',
                data: [],
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 20 }
                }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Breakdown Chart
    var breakdownCtx = document.getElementById('breakdownChart').getContext('2d');
    breakdownChart = new Chart(breakdownCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Leave'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#3b82f6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { usePointStyle: true, padding: 15 }
                }
            },
            cutout: '65%'
        }
    });
}

function updateCharts(chartData) {
    if (!chartData) return;

    // Update trend chart
    if (chartData.trend) {
        trendChart.data.labels = chartData.trend.labels;
        trendChart.data.datasets[0].data = chartData.trend.present;
        trendChart.data.datasets[1].data = chartData.trend.absent;
        trendChart.data.datasets[2].data = chartData.trend.late;
        trendChart.update();
    }

    // Update breakdown chart
    if (chartData.breakdown) {
        breakdownChart.data.datasets[0].data = [
            chartData.breakdown.present || 0,
            chartData.breakdown.absent || 0,
            chartData.breakdown.late || 0,
            chartData.breakdown.leave || 0
        ];
        breakdownChart.update();
    }
}

function setTrendPeriod(period) {
    $('.chart-actions .chart-btn').removeClass('active');
    event.target.classList.add('active');
    // Reload chart data with new period
    loadDashboardData();
}

function filterTable(query) {
    query = query.toLowerCase();
    $('#attendanceTableBody tr').each(function() {
        var name = $(this).data('name') || '';
        $(this).toggle(name.indexOf(query) > -1);
    });
}

function applyFilters() {
    loadDashboardData();
}

function resetFilters() {
    $('#filterDate').val('<?php echo $today; ?>');
    $('#filterDept').val('');
    $('#filterShift').val('');
    $('#filterStatus').val('');
    loadDashboardData();
}

function refreshDashboard() {
    loadDashboardData();
}

function viewEmployee(userID) {
    window.location.href = '?mod=attendance&pg=list&user=' + userID;
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2).toUpperCase();
}

// Export Modal Functions
function showExportModal() {
    $('#exportModal').addClass('show');
}

function closeExportModal() {
    $('#exportModal').removeClass('show');
}

function selectExportFormat(el) {
    $('.export-option').removeClass('selected');
    $(el).addClass('selected');
    selectedExportFormat = $(el).data('format');
}

function generateExport() {
    var reportType = $('#exportReportType').val();
    var fromDate = $('#exportFromDate').val();
    var toDate = $('#exportToDate').val();
    var deptID = $('#exportDept').val();

    var url = '<?php echo SITEURL; ?>/xadmin/mod/attendance/x-attendance-export.php?' +
        'format=' + selectedExportFormat +
        '&type=' + reportType +
        '&from=' + fromDate +
        '&to=' + toDate +
        '&dept=' + deptID;

    window.open(url, '_blank');
    closeExportModal();
}

// Close modal on outside click
$('#exportModal').on('click', function(e) {
    if (e.target === this) closeExportModal();
});
</script>
