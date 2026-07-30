<?php
/**
 * Attendance Reports Page
 * Select and generate various attendance reports
 */

$currentMonth = date('n');
$currentYear = date('Y');

// Get departments
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT deptID, deptName FROM " . $DB->pre . "departments WHERE status=? ORDER BY deptName";
$departments = $DB->dbRows();
?>

<style>
.reports-page {
    max-width: 1400px;
    margin: 0 auto;
}

.reports-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.reports-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.reports-header p {
    color: #64748b;
    margin: 4px 0 0 0;
    font-size: 14px;
}

/* Report Type Cards */
.report-types {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

@media (max-width: 1024px) {
    .report-types { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
    .report-types { grid-template-columns: 1fr; }
}

.report-card {
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.report-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.report-card.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}

.report-card.selected::after {
    content: '';
    position: absolute;
    top: 12px;
    right: 12px;
    width: 24px;
    height: 24px;
    background: #3b82f6;
    border-radius: 50%;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' xmlns='http://www.w3.org/2000/svg'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E");
    background-size: 14px;
    background-position: center;
    background-repeat: no-repeat;
}

.report-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.report-icon svg {
    width: 28px;
    height: 28px;
}

.report-icon.daily { background: #dbeafe; color: #2563eb; }
.report-icon.monthly { background: #dcfce7; color: #16a34a; }
.report-icon.payroll { background: #fef3c7; color: #d97706; }
.report-icon.late { background: #fee2e2; color: #dc2626; }
.report-icon.overtime { background: #e0e7ff; color: #4f46e5; }
.report-icon.absent { background: #fce7f3; color: #db2777; }
.report-icon.master { background: #f0fdf4; color: #15803d; }

.report-card h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.report-card p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.report-card .report-fields {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.report-card .field-tag {
    display: inline-block;
    padding: 2px 8px;
    background: #f1f5f9;
    border-radius: 4px;
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
}

/* Filters Section */
.filters-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.filters-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filters-title svg {
    width: 20px;
    height: 20px;
    color: #64748b;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

@media (max-width: 1024px) {
    .filters-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
    .filters-grid { grid-template-columns: 1fr; }
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-input, .filter-select {
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    color: #1e293b;
    background: #fff;
    outline: none;
    transition: all 0.2s;
}

.filter-input:focus, .filter-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Preview Section */
.preview-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.preview-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.preview-actions {
    display: flex;
    gap: 12px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
}

.btn svg {
    width: 18px;
    height: 18px;
}

.btn-primary {
    background: #3b82f6;
    color: #fff;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-success {
    background: #22c55e;
    color: #fff;
}

.btn-success:hover {
    background: #16a34a;
}

.btn-secondary {
    background: #fff;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f8fafc;
}

.btn-danger {
    background: #ef4444;
    color: #fff;
}

.preview-body {
    padding: 24px;
    min-height: 300px;
    position: relative;
}

.preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
    color: #94a3b8;
}

.preview-placeholder svg {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.preview-placeholder p {
    font-size: 15px;
    margin: 0;
}

/* Data Table */
.data-table-wrapper {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table th {
    padding: 12px 16px;
    text-align: left;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}

.data-table tr:hover {
    background: #f8fafc;
}

.data-table .mono {
    font-family: 'JetBrains Mono', monospace;
}

.data-table .text-right {
    text-align: right;
}

.data-table .text-center {
    text-align: center;
}

/* Status badges */
.status-P { background: #dcfce7; color: #16a34a; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.status-A { background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.status-L { background: #dbeafe; color: #2563eb; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.status-LT { background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.status-H { background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.status-WO { background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.status-HD { background: #fae8ff; color: #a855f7; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }

/* Summary Stats */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 1024px) {
    .summary-stats { grid-template-columns: repeat(3, 1fr); }
}

.stat-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    text-align: center;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.stat-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 6px;
}

.stat-box.present .stat-value { color: #16a34a; }
.stat-box.absent .stat-value { color: #dc2626; }
.stat-box.late .stat-value { color: #d97706; }
.stat-box.leave .stat-value { color: #2563eb; }

/* Loading */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Muster Roll specific */
.muster-table {
    font-size: 11px;
}

.muster-table th, .muster-table td {
    padding: 8px 6px;
    text-align: center;
}

.muster-table th:nth-child(1),
.muster-table th:nth-child(2),
.muster-table th:nth-child(3),
.muster-table td:nth-child(1),
.muster-table td:nth-child(2),
.muster-table td:nth-child(3) {
    text-align: left;
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 1;
}

.muster-table th:nth-child(1),
.muster-table th:nth-child(2),
.muster-table th:nth-child(3) {
    background: #f8fafc;
}

.day-cell {
    min-width: 28px;
    font-weight: 500;
}
</style>

<div class="reports-page">
    <!-- Header -->
    <div class="reports-header">
        <div>
            <h1>Attendance Reports</h1>
            <p>Generate and export detailed attendance reports</p>
        </div>
        <a href="?mod=attendance&pg=dashboard" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Report Type Selection -->
    <div class="report-types">
        <div class="report-card selected" data-type="daily" onclick="selectReportType(this)">
            <div class="report-icon daily">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <h3>Daily Attendance</h3>
            <p>Detailed attendance for a specific date with check-in/out times, working hours, and status.</p>
            <div class="report-fields">
                <span class="field-tag">Check In</span>
                <span class="field-tag">Check Out</span>
                <span class="field-tag">Hours</span>
                <span class="field-tag">Status</span>
            </div>
        </div>

        <div class="report-card" data-type="monthly" onclick="selectReportType(this)">
            <div class="report-icon monthly">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <h3>Monthly Muster Roll</h3>
            <p>Traditional attendance register with day-wise status codes for the entire month.</p>
            <div class="report-fields">
                <span class="field-tag">Day 1-31</span>
                <span class="field-tag">Present</span>
                <span class="field-tag">Absent</span>
                <span class="field-tag">Payable</span>
            </div>
        </div>

        <div class="report-card" data-type="payroll" onclick="selectReportType(this)">
            <div class="report-icon payroll">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
            <h3>Payroll Summary</h3>
            <p>Attendance summary optimized for payroll processing with deductions and payable days.</p>
            <div class="report-fields">
                <span class="field-tag">Working Days</span>
                <span class="field-tag">Present</span>
                <span class="field-tag">LOP</span>
                <span class="field-tag">OT Hours</span>
            </div>
        </div>

        <div class="report-card" data-type="late_early" onclick="selectReportType(this)">
            <div class="report-icon late">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h3>Late / Early Report</h3>
            <p>Track late arrivals and early departures with reasons and approval status.</p>
            <div class="report-fields">
                <span class="field-tag">Late Mins</span>
                <span class="field-tag">Early Mins</span>
                <span class="field-tag">Reason</span>
                <span class="field-tag">Approval</span>
            </div>
        </div>

        <div class="report-card" data-type="overtime" onclick="selectReportType(this)">
            <div class="report-icon overtime">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <h3>Overtime Report</h3>
            <p>Track overtime hours with type, rate multipliers, and approval status.</p>
            <div class="report-fields">
                <span class="field-tag">OT Hours</span>
                <span class="field-tag">OT Type</span>
                <span class="field-tag">Rate</span>
                <span class="field-tag">Approved</span>
            </div>
        </div>

        <div class="report-card" data-type="absenteeism" onclick="selectReportType(this)">
            <div class="report-icon absent">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <h3>Absenteeism Report</h3>
            <p>Identify employees with high absence rates and absence patterns.</p>
            <div class="report-fields">
                <span class="field-tag">Total Absent</span>
                <span class="field-tag">Consecutive</span>
                <span class="field-tag">Pattern</span>
                <span class="field-tag">Dates</span>
            </div>
        </div>

        <div class="report-card" data-type="master" onclick="selectReportType(this)">
            <div class="report-icon master">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="2"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                    <line x1="9" y1="16" x2="15" y2="16"/>
                </svg>
            </div>
            <h3>Master Report</h3>
            <p>Comprehensive detailed attendance with all employee records, times, and status for a date range.</p>
            <div class="report-fields">
                <span class="field-tag">Employee</span>
                <span class="field-tag">Date</span>
                <span class="field-tag">In/Out</span>
                <span class="field-tag">Hours</span>
                <span class="field-tag">Late</span>
                <span class="field-tag">Status</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <h3 class="filters-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            Report Filters
        </h3>
        <div class="filters-grid">
            <div class="filter-group" id="dateFilterGroup">
                <label class="filter-label">Date</label>
                <input type="date" id="filterDate" class="filter-input" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-group" id="dateRangeGroup" style="display:none;">
                <label class="filter-label">From Date</label>
                <input type="date" id="filterFromDate" class="filter-input" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="filter-group" id="toDateGroup" style="display:none;">
                <label class="filter-label">To Date</label>
                <input type="date" id="filterToDate" class="filter-input" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-group" id="monthYearGroup" style="display:none;">
                <label class="filter-label">Month</label>
                <select id="filterMonth" class="filter-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $currentMonth ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group" id="yearGroup" style="display:none;">
                <label class="filter-label">Year</label>
                <select id="filterYear" class="filter-select">
                    <?php for ($y = $currentYear - 1; $y <= $currentYear; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $currentYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Department</label>
                <select id="filterDept" class="filter-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['deptID']; ?>"><?php echo htmlspecialchars($dept['deptName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="align-self: flex-end;">
                <button class="btn btn-primary" onclick="generateReport()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Generate Report
                </button>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="preview-section">
        <div class="preview-header">
            <div class="preview-title" id="previewTitle">Report Preview</div>
            <div class="preview-actions" id="exportActions" style="display:none;">
                <button class="btn btn-success" onclick="exportReport('excel')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Export Excel
                </button>
                <button class="btn btn-danger" onclick="exportReport('pdf')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Export PDF
                </button>
                <button class="btn btn-secondary" onclick="exportReport('csv')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    CSV
                </button>
            </div>
        </div>
        <div class="preview-body" id="previewBody">
            <div class="preview-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <line x1="10" y1="9" x2="8" y2="9"/>
                </svg>
                <p>Select a report type and click "Generate Report" to preview</p>
            </div>
        </div>
    </div>
</div>

<script>
var selectedReportType = 'daily';
var currentReportData = null;

function selectReportType(el) {
    // Update selection
    document.querySelectorAll('.report-card').forEach(function(card) {
        card.classList.remove('selected');
    });
    el.classList.add('selected');

    selectedReportType = el.dataset.type;

    // Update filter visibility based on report type
    updateFilterVisibility();
}

function updateFilterVisibility() {
    var dateGroup = document.getElementById('dateFilterGroup');
    var dateRangeGroup = document.getElementById('dateRangeGroup');
    var toDateGroup = document.getElementById('toDateGroup');
    var monthYearGroup = document.getElementById('monthYearGroup');
    var yearGroup = document.getElementById('yearGroup');

    // Hide all first
    dateGroup.style.display = 'none';
    dateRangeGroup.style.display = 'none';
    toDateGroup.style.display = 'none';
    monthYearGroup.style.display = 'none';
    yearGroup.style.display = 'none';

    switch (selectedReportType) {
        case 'daily':
            dateGroup.style.display = 'flex';
            break;
        case 'monthly':
        case 'payroll':
            monthYearGroup.style.display = 'flex';
            yearGroup.style.display = 'flex';
            break;
        case 'late_early':
        case 'overtime':
        case 'absenteeism':
        case 'master':
            dateRangeGroup.style.display = 'flex';
            toDateGroup.style.display = 'flex';
            break;
    }
}

function generateReport() {
    var params = {
        action: getActionForType(selectedReportType),
        deptID: document.getElementById('filterDept').value
    };

    // Add date params based on type
    switch (selectedReportType) {
        case 'daily':
            params.date = document.getElementById('filterDate').value;
            break;
        case 'monthly':
        case 'payroll':
            params.month = document.getElementById('filterMonth').value;
            params.year = document.getElementById('filterYear').value;
            break;
        default:
            params.fromDate = document.getElementById('filterFromDate').value;
            params.toDate = document.getElementById('filterToDate').value;
    }

    // Show loading
    document.getElementById('previewBody').innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Generating report...</p></div>';
    document.getElementById('exportActions').style.display = 'none';

    $.ajax({
        url: '<?php echo SITEURL; ?>/xadmin/mod/attendance/x-attendance-api.php',
        type: 'POST',
        data: params,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                currentReportData = res;
                renderReport(res);
                document.getElementById('exportActions').style.display = 'flex';
            } else {
                document.getElementById('previewBody').innerHTML = '<div class="preview-placeholder"><p>Error: ' + (res.message || 'Failed to generate report') + '</p></div>';
            }
        },
        error: function() {
            document.getElementById('previewBody').innerHTML = '<div class="preview-placeholder"><p>Error: Failed to connect to server</p></div>';
        }
    });
}

function getActionForType(type) {
    switch (type) {
        case 'daily': return 'getDashboardData';
        case 'monthly': return 'getMonthlyMuster';
        case 'payroll': return 'getPayrollSummary';
        case 'late_early': return 'getLateEarlyReport';
        case 'overtime': return 'getOvertimeReport';
        case 'absenteeism': return 'getAbsenteeismReport';
        case 'master': return 'getMasterReport';
        default: return 'getDashboardData';
    }
}

function renderReport(data) {
    var html = '';

    switch (selectedReportType) {
        case 'daily':
            html = renderDailyReport(data);
            break;
        case 'monthly':
            html = renderMonthlyMuster(data);
            break;
        case 'payroll':
            html = renderPayrollSummary(data);
            break;
        case 'late_early':
            html = renderLateEarlyReport(data);
            break;
        case 'overtime':
            html = renderOvertimeReport(data);
            break;
        case 'absenteeism':
            html = renderAbsenteeismReport(data);
            break;
        case 'master':
            html = renderMasterReport(data);
            break;
    }

    document.getElementById('previewBody').innerHTML = html;
    document.getElementById('previewTitle').textContent = getReportTitle();
}

function getReportTitle() {
    switch (selectedReportType) {
        case 'daily': return 'Daily Attendance Report';
        case 'monthly': return 'Monthly Muster Roll';
        case 'payroll': return 'Payroll Summary Report';
        case 'late_early': return 'Late / Early Checkout Report';
        case 'overtime': return 'Overtime Report';
        case 'absenteeism': return 'Absenteeism Report';
        case 'master': return 'Master Attendance Report';
        default: return 'Report Preview';
    }
}

function renderDailyReport(data) {
    if (!data.attendance || data.attendance.length === 0) {
        return '<div class="preview-placeholder"><p>No attendance records found for this date</p></div>';
    }

    // Summary stats
    var html = '<div class="summary-stats">';
    html += '<div class="stat-box"><div class="stat-value">' + data.kpis.total + '</div><div class="stat-label">Total</div></div>';
    html += '<div class="stat-box present"><div class="stat-value">' + data.kpis.present + '</div><div class="stat-label">Present</div></div>';
    html += '<div class="stat-box absent"><div class="stat-value">' + data.kpis.absent + '</div><div class="stat-label">Absent</div></div>';
    html += '<div class="stat-box late"><div class="stat-value">' + data.kpis.late + '</div><div class="stat-label">Late</div></div>';
    html += '<div class="stat-box leave"><div class="stat-value">' + data.kpis.leave + '</div><div class="stat-label">Leave</div></div>';
    html += '</div>';

    // Table
    html += '<div class="data-table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Emp Code</th><th>Employee Name</th><th>Department</th><th>Shift</th><th>Check In</th><th>Check Out</th><th>Working Hrs</th><th>Status</th></tr></thead>';
    html += '<tbody>';

    data.attendance.forEach(function(row) {
        var statusClass = 'P';
        if (row.status === 'Absent') statusClass = 'A';
        else if (row.status === 'Leave' || row.status === 'On Leave') statusClass = 'L';
        else if (row.status === 'Half day' || row.status === 'Half Day') statusClass = 'HD';
        html += '<tr>';
        html += '<td class="mono">' + (row.empCode || '-') + '</td>';
        html += '<td>' + row.name + '</td>';
        html += '<td>' + (row.department || '-') + '</td>';
        html += '<td>' + (row.shift || 'General') + '</td>';
        html += '<td class="mono">' + (row.checkIn || '-') + (row.isLate == 1 ? ' <span class="status-LT">+' + row.lateMinutes + 'm</span>' : '') + '</td>';
        html += '<td class="mono">' + (row.checkOut || '-') + '</td>';
        html += '<td class="text-center">' + (row.workingHours || '-') + '</td>';
        html += '<td><span class="status-' + statusClass + '">' + row.status + '</span></td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function renderMonthlyMuster(data) {
    if (!data.data || data.data.length === 0) {
        return '<div class="preview-placeholder"><p>No data found for this month</p></div>';
    }

    var daysInMonth = data.daysInMonth;

    var html = '<div class="data-table-wrapper"><table class="data-table muster-table">';
    html += '<thead><tr><th>Code</th><th>Name</th><th>Dept</th>';

    for (var d = 1; d <= daysInMonth; d++) {
        html += '<th class="day-cell">' + d + '</th>';
    }

    html += '<th>P</th><th>A</th><th>L</th><th>H</th><th>WO</th><th>Pay</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(row) {
        html += '<tr>';
        html += '<td class="mono">' + row.empCode + '</td>';
        html += '<td>' + row.name + '</td>';
        html += '<td>' + (row.department || '-') + '</td>';

        for (var d = 1; d <= daysInMonth; d++) {
            var status = row.days[d] || '-';
            html += '<td class="text-center"><span class="status-' + status + '">' + status + '</span></td>';
        }

        html += '<td class="text-center"><strong>' + row.summary.present + '</strong></td>';
        html += '<td class="text-center">' + row.summary.absent + '</td>';
        html += '<td class="text-center">' + row.summary.leave + '</td>';
        html += '<td class="text-center">' + row.summary.holiday + '</td>';
        html += '<td class="text-center">' + row.summary.weeklyOff + '</td>';
        html += '<td class="text-center"><strong>' + row.summary.payableDays + '</strong></td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function renderPayrollSummary(data) {
    if (!data.data || data.data.length === 0) {
        return '<div class="preview-placeholder"><p>No data found for this month</p></div>';
    }

    // Summary
    var html = '<div class="summary-stats">';
    html += '<div class="stat-box"><div class="stat-value">' + data.calendarDays + '</div><div class="stat-label">Calendar Days</div></div>';
    html += '<div class="stat-box"><div class="stat-value">' + data.workingDays + '</div><div class="stat-label">Working Days</div></div>';
    html += '<div class="stat-box"><div class="stat-value">' + data.holidays + '</div><div class="stat-label">Holidays</div></div>';
    html += '<div class="stat-box"><div class="stat-value">' + data.weeklyOffs + '</div><div class="stat-label">Weekly Offs</div></div>';
    html += '</div>';

    // Table
    html += '<div class="data-table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Code</th><th>Name</th><th>Dept</th><th>Working</th><th>Present</th><th>Absent</th><th>Leave</th><th>Half Day</th><th>Late</th><th>OT Hrs</th><th>Payable</th><th>Deduction</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(row) {
        html += '<tr>';
        html += '<td class="mono">' + row.empCode + '</td>';
        html += '<td>' + row.name + '</td>';
        html += '<td>' + (row.department || '-') + '</td>';
        html += '<td class="text-center">' + row.workingDays + '</td>';
        html += '<td class="text-center"><span class="status-P">' + row.presentDays + '</span></td>';
        html += '<td class="text-center"><span class="status-A">' + row.absentDays + '</span></td>';
        html += '<td class="text-center"><span class="status-L">' + (row.leaveDays || row.paidLeave || 0) + '</span></td>';
        html += '<td class="text-center"><span class="status-HD">' + row.halfDays + '</span></td>';
        html += '<td class="text-center"><span class="status-LT">' + row.lateDays + '</span></td>';
        html += '<td class="text-center">' + row.overtimeHours + '</td>';
        html += '<td class="text-center"><strong>' + row.payableDays + '</strong></td>';
        html += '<td class="text-center" style="color:#dc2626;"><strong>' + row.deductionDays + '</strong></td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function renderLateEarlyReport(data) {
    if (!data.data || data.data.length === 0) {
        return '<div class="preview-placeholder"><p>No late arrivals or early checkouts found for this period</p></div>';
    }

    var html = '<p style="margin-bottom:16px;color:#64748b;">Found ' + data.count + ' records</p>';
    html += '<div class="data-table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Date</th><th>Code</th><th>Name</th><th>Dept</th><th>Check In</th><th>Late (mins)</th><th>Check Out</th><th>Early (mins)</th><th>Reason</th><th>Status</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(row) {
        html += '<tr>';
        html += '<td>' + row.date + '</td>';
        html += '<td class="mono">' + row.empCode + '</td>';
        html += '<td>' + row.name + '</td>';
        html += '<td>' + (row.department || '-') + '</td>';
        html += '<td class="mono">' + row.actualIn + '</td>';
        html += '<td class="text-center">' + (row.lateMinutes > 0 ? '<span class="status-LT">' + row.lateMinutes + '</span>' : '-') + '</td>';
        html += '<td class="mono">' + row.actualOut + '</td>';
        html += '<td class="text-center">' + (row.earlyMinutes > 0 ? '<span class="status-LT">' + row.earlyMinutes + '</span>' : '-') + '</td>';
        html += '<td>' + row.reason + '</td>';
        html += '<td>' + row.status + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function renderOvertimeReport(data) {
    if (!data.data || data.data.length === 0) {
        return '<div class="preview-placeholder"><p>No overtime records found for this period</p></div>';
    }

    // Summary
    var html = '<div class="summary-stats" style="grid-template-columns:repeat(3,1fr);">';
    html += '<div class="stat-box"><div class="stat-value">' + data.totalOTHours + '</div><div class="stat-label">Total OT Hours</div></div>';
    html += '<div class="stat-box present"><div class="stat-value">' + data.approvedOTHours + '</div><div class="stat-label">Approved</div></div>';
    html += '<div class="stat-box late"><div class="stat-value">' + data.pendingOTHours + '</div><div class="stat-label">Pending</div></div>';
    html += '</div>';

    html += '<div class="data-table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Date</th><th>Code</th><th>Name</th><th>Dept</th><th>Check Out</th><th>OT Hours</th><th>Type</th><th>Rate</th><th>Approved</th><th>By</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(row) {
        html += '<tr>';
        html += '<td>' + row.date + '</td>';
        html += '<td class="mono">' + row.empCode + '</td>';
        html += '<td>' + row.name + '</td>';
        html += '<td>' + (row.department || '-') + '</td>';
        html += '<td class="mono">' + row.actualOut + '</td>';
        html += '<td class="text-center"><strong>' + row.otHours + '</strong></td>';
        html += '<td>' + row.otType + '</td>';
        html += '<td>' + row.otRate + '</td>';
        html += '<td>' + (row.approved === 'Yes' ? '<span class="status-P">Yes</span>' : '<span class="status-LT">Pending</span>') + '</td>';
        html += '<td>' + row.approvedBy + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function renderAbsenteeismReport(data) {
    if (!data.data || data.data.length === 0) {
        return '<div class="preview-placeholder"><p>No absenteeism data found for this period</p></div>';
    }

    var html = '<p style="margin-bottom:16px;color:#64748b;">Employees with absences during this period</p>';
    html += '<div class="data-table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Total Absent</th><th>Max Consecutive</th><th>Absent Dates</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(row) {
        html += '<tr>';
        html += '<td class="mono">' + row.empCode + '</td>';
        html += '<td>' + row.name + '</td>';
        html += '<td>' + (row.department || '-') + '</td>';
        html += '<td class="text-center"><span class="status-A">' + row.totalAbsent + '</span></td>';
        html += '<td class="text-center">' + row.consecutiveAbsent + '</td>';
        html += '<td style="font-size:11px;">' + row.absentDates + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function renderMasterReport(data) {
    if (!data.data || data.data.length === 0) {
        return '<div class="preview-placeholder"><p>No attendance records found for this period</p></div>';
    }

    // Summary stats
    var html = '<div class="summary-stats">';
    html += '<div class="stat-box"><div class="stat-value">' + data.totalRecords + '</div><div class="stat-label">Total Records</div></div>';
    html += '<div class="stat-box present"><div class="stat-value">' + data.totalPresent + '</div><div class="stat-label">Present</div></div>';
    html += '<div class="stat-box absent"><div class="stat-value">' + data.totalAbsent + '</div><div class="stat-label">Absent</div></div>';
    html += '<div class="stat-box late"><div class="stat-value">' + data.totalLate + '</div><div class="stat-label">Late</div></div>';
    html += '<div class="stat-box leave"><div class="stat-value">' + data.totalLeave + '</div><div class="stat-label">Leave</div></div>';
    html += '<div class="stat-box"><div class="stat-value">' + data.totalHours + '</div><div class="stat-label">Total Hours</div></div>';
    html += '</div>';

    // Table
    html += '<div class="data-table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Date</th><th>Day</th><th>Emp Code</th><th>Employee Name</th><th>Check In</th><th>Check Out</th><th>Scheduled In</th><th>Scheduled Out</th><th>Late (mins)</th><th>Early Out (mins)</th><th>Working Hrs</th><th>Status</th><th>Source</th><th>Remarks</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(row) {
        var statusClass = 'P';
        if (row.status === 'Absent') statusClass = 'A';
        else if (row.status === 'Leave' || row.status === 'On Leave') statusClass = 'L';
        else if (row.status === 'Half day' || row.status === 'Half Day') statusClass = 'HD';
        html += '<tr>';
        html += '<td>' + row.date + '</td>';
        html += '<td>' + row.dayName + '</td>';
        html += '<td class="mono">' + (row.empCode || '-') + '</td>';
        html += '<td>' + row.name + '</td>';
        html += '<td class="mono">' + (row.checkIn || '-') + '</td>';
        html += '<td class="mono">' + (row.checkOut || '-') + '</td>';
        html += '<td class="mono">' + (row.scheduledIn || '-') + '</td>';
        html += '<td class="mono">' + (row.scheduledOut || '-') + '</td>';
        html += '<td class="text-center">' + (row.lateMinutes > 0 ? '<span class="status-LT">' + row.lateMinutes + '</span>' : '-') + '</td>';
        html += '<td class="text-center">' + (row.earlyMinutes > 0 ? '<span class="status-LT">' + row.earlyMinutes + '</span>' : '-') + '</td>';
        html += '<td class="text-center">' + (row.workingHours || '-') + '</td>';
        html += '<td><span class="status-' + statusClass + '">' + row.status + '</span></td>';
        html += '<td>' + (row.source || '-') + '</td>';
        html += '<td style="font-size:11px;">' + (row.remarks || '-') + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function exportReport(format) {
    var params = '?format=' + format + '&type=' + selectedReportType;

    switch (selectedReportType) {
        case 'daily':
            params += '&from=' + document.getElementById('filterDate').value;
            params += '&to=' + document.getElementById('filterDate').value;
            break;
        case 'monthly':
        case 'payroll':
            var month = document.getElementById('filterMonth').value;
            var year = document.getElementById('filterYear').value;
            params += '&from=' + year + '-' + month.padStart(2, '0') + '-01';
            params += '&to=' + year + '-' + month.padStart(2, '0') + '-31';
            break;
        default:
            params += '&from=' + document.getElementById('filterFromDate').value;
            params += '&to=' + document.getElementById('filterToDate').value;
    }

    params += '&dept=' + document.getElementById('filterDept').value;

    window.open('<?php echo SITEURL; ?>/xadmin/mod/attendance/x-attendance-export.php' + params, '_blank');
}

// Initialize
$(document).ready(function() {
    updateFilterVisibility();
});
</script>
