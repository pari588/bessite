<?php
/**
 * HRMS Employee Portal - Attendance Reports
 * Allows employees to generate and download their own attendance reports
 */

$userID = $_SESSION['HRMS_USER_ID'] ?? 0;
$userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
$isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
$isHRAdmin = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;
$canViewTeamReports = $isManager || $isHRAdmin;

$currentMonth = date('n');
$currentYear = date('Y');
?>

<style>
.reports-page {
    max-width: 900px;
    margin: 0 auto;
}

.reports-header {
    margin-bottom: 24px;
}

.reports-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 4px 0;
}

.reports-header p {
    font-size: 14px;
    color: var(--gray-500);
    margin: 0;
}

/* Report Cards */
.report-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 640px) {
    .report-cards { grid-template-columns: 1fr; }
}

.report-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s;
}

.report-card:hover {
    border-color: var(--blue-500);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.report-card.selected {
    border-color: var(--blue-500);
    background: var(--blue-50);
}

.report-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.report-card-icon svg {
    width: 24px;
    height: 24px;
}

.report-card-icon.monthly { background: var(--green-100); color: var(--green-500); }
.report-card-icon.summary { background: var(--blue-100); color: var(--blue-600); }
.report-card-icon.late { background: #fef3c7; color: #d97706; }
.report-card-icon.leave { background: #e0e7ff; color: #4f46e5; }

.report-card h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 6px 0;
}

.report-card p {
    font-size: 13px;
    color: var(--gray-500);
    margin: 0;
    line-height: 1.5;
}

/* Filters Section */
.filters-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
}

.filters-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filters-title svg {
    width: 18px;
    height: 18px;
    color: var(--gray-400);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
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
    font-weight: 500;
    color: var(--gray-600);
}

.filter-select, .filter-input {
    padding: 10px 14px;
    font-size: 14px;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    background: #fff;
    outline: none;
    transition: all 0.15s;
}

.filter-select:focus, .filter-input:focus {
    border-color: var(--blue-500);
    box-shadow: 0 0 0 3px var(--blue-100);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
}

.action-btn svg {
    width: 18px;
    height: 18px;
}

.action-btn.preview {
    background: var(--blue-600);
    color: #fff;
}

.action-btn.preview:hover {
    background: var(--blue-700);
}

.action-btn.excel {
    background: #16a34a;
    color: #fff;
}

.action-btn.excel:hover {
    background: #15803d;
}

.action-btn.pdf {
    background: #dc2626;
    color: #fff;
}

.action-btn.pdf:hover {
    background: #b91c1c;
}

/* Preview Section */
.preview-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    overflow: hidden;
}

.preview-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-100);
    background: var(--gray-50);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.preview-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-800);
}

.preview-body {
    padding: 20px;
    min-height: 200px;
}

.preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 24px;
    color: var(--gray-400);
}

.preview-placeholder svg {
    width: 48px;
    height: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.preview-placeholder p {
    font-size: 14px;
    margin: 0;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table th {
    padding: 12px 16px;
    text-align: left;
    background: var(--gray-50);
    border-bottom: 2px solid var(--gray-200);
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
}

.data-table tr:hover td {
    background: var(--gray-50);
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
}

.status-P { background: var(--green-100); color: var(--green-500); }
.status-A { background: var(--red-100); color: var(--red-500); }
.status-L { background: var(--blue-100); color: var(--blue-600); }
.status-LT { background: #fef3c7; color: #d97706; }
.status-H, .status-WO { background: var(--gray-100); color: var(--gray-500); }
.status-HD { background: #fae8ff; color: #a855f7; }

/* Summary Stats */
.summary-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

@media (max-width: 640px) {
    .summary-row { grid-template-columns: repeat(2, 1fr); }
}

.summary-item {
    background: var(--gray-50);
    border-radius: 8px;
    padding: 14px;
    text-align: center;
}

.summary-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1;
}

.summary-label {
    font-size: 11px;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

.summary-item.present .summary-value { color: var(--green-500); }
.summary-item.absent .summary-value { color: var(--red-500); }
.summary-item.late .summary-value { color: #d97706; }
.summary-item.leave .summary-value { color: var(--blue-600); }

/* Team Member Selector for Managers */
.team-selector-card {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08), rgba(124, 58, 237, 0.03));
    border: 1px solid rgba(124, 58, 237, 0.2);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
}

.team-selector-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.team-selector-header svg {
    width: 20px;
    height: 20px;
    color: #7c3aed;
}

.team-selector-title {
    font-size: 14px;
    font-weight: 600;
    color: #7c3aed;
}

.team-selector-badge {
    margin-left: auto;
    padding: 4px 10px;
    background: transparent;
    border: 1px solid #7c3aed;
    color: #7c3aed;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 100px;
}

.team-selector-content {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.team-selector-content .filter-select {
    flex: 1;
    min-width: 200px;
    border-color: rgba(124, 58, 237, 0.3);
}

.team-selector-content .filter-select:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
}

/* Loading */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--gray-200);
    border-top-color: var(--blue-600);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Muster table */
.muster-table-wrapper {
    overflow-x: auto;
}

.muster-table {
    font-size: 11px;
}

.muster-table th, .muster-table td {
    padding: 8px 6px;
    text-align: center;
    white-space: nowrap;
}

.muster-table th:first-child,
.muster-table td:first-child {
    text-align: left;
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 1;
}

.muster-table th:first-child {
    background: var(--gray-50);
}

.day-cell {
    min-width: 28px;
}
</style>

<div class="reports-page">
    <div class="reports-header">
        <h1>Attendance Reports</h1>
        <p>Generate and download <?php echo $canViewTeamReports ? 'attendance reports' : 'your attendance reports'; ?></p>
    </div>

    <?php if ($canViewTeamReports): ?>
    <!-- Team Member Selector (Manager/HR Admin Only) -->
    <div class="team-selector-card">
        <div class="team-selector-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
            <span class="team-selector-title">View Reports For</span>
            <span class="team-selector-badge"><?php echo $isHRAdmin ? 'HR Admin' : 'Manager'; ?></span>
        </div>
        <div class="team-selector-content">
            <select id="reportUserID" class="filter-select">
                <option value="<?php echo $userID; ?>">Myself (<?php echo htmlspecialchars($userName); ?>)</option>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Type Selection -->
    <div class="report-cards">
        <div class="report-card selected" data-type="monthly" onclick="selectReportType(this)">
            <div class="report-card-icon monthly">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <h3>Monthly Attendance</h3>
            <p>Day-wise attendance with status codes (P/A/L/H) for the selected month</p>
        </div>

        <div class="report-card" data-type="summary" onclick="selectReportType(this)">
            <div class="report-card-icon summary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <h3>Summary Report</h3>
            <p>Total present, absent, leave days with working hours summary</p>
        </div>

        <div class="report-card" data-type="late_early" onclick="selectReportType(this)">
            <div class="report-card-icon late">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h3>Late / Early Report</h3>
            <p>Details of late arrivals and early checkouts with minutes</p>
        </div>

        <div class="report-card" data-type="leave" onclick="selectReportType(this)">
            <div class="report-card-icon leave">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <path d="M9 14l2 2 4-4"/>
                </svg>
            </div>
            <h3>Leave History</h3>
            <p>All leaves taken with type, dates, and approval status</p>
        </div>

        <div class="report-card" data-type="detailed" onclick="selectReportType(this)">
            <div class="report-card-icon" style="background: #f0fdf4; color: #16a34a;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20V10"/>
                    <path d="M18 20V4"/>
                    <path d="M6 20v-4"/>
                </svg>
            </div>
            <h3>Detailed Attendance</h3>
            <p>Day-wise check-in, check-out times with total working hours</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <h3 class="filters-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            Select Period
        </h3>
        <!-- Monthly filters (for attendance reports) -->
        <div class="filters-grid" id="monthlyFilters">
            <div class="filter-group">
                <label class="filter-label">Month</label>
                <select id="filterMonth" class="filter-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $currentMonth ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Year</label>
                <select id="filterYear" class="filter-select">
                    <?php for ($y = $currentYear - 1; $y <= $currentYear; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $currentYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group" style="align-self: flex-end;">
                <button class="action-btn preview" onclick="generateReport()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Generate Report
                </button>
            </div>
        </div>
        <!-- Financial Year filter (for leave history) -->
        <div class="filters-grid" id="fyFilters" style="display:none;">
            <div class="filter-group">
                <label class="filter-label">Financial Year</label>
                <select id="filterFY" class="filter-select">
                    <?php
                    // Generate FY options: current FY and 2 previous years
                    // FY runs April to March, so if current month >= April, current FY started this year
                    $fyStartYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;
                    for ($fy = $fyStartYear; $fy >= $fyStartYear - 2; $fy--):
                        $fyLabel = $fy . '-' . ($fy + 1);
                        $selected = ($fy == $fyStartYear) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $fy; ?>" <?php echo $selected; ?>>FY <?php echo $fyLabel; ?> (Apr <?php echo $fy; ?> - Mar <?php echo $fy + 1; ?>)</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group" style="align-self: flex-end;">
                <button class="action-btn preview" onclick="generateReport()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Generate Report
                </button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons" id="downloadButtons" style="display:none;">
        <button class="action-btn excel" onclick="downloadReport('excel')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Download Excel
        </button>
        <button class="action-btn pdf" onclick="downloadReport('pdf')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Download PDF
        </button>
    </div>

    <!-- Preview Section -->
    <div class="preview-card">
        <div class="preview-header">
            <span class="preview-title" id="previewTitle">Report Preview</span>
        </div>
        <div class="preview-body" id="previewBody">
            <div class="preview-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <p>Select a report type and click "Generate Report" to preview</p>
            </div>
        </div>
    </div>
</div>

<script>
var selectedReportType = 'monthly';
var currentReportData = null;
var userID = <?php echo $userID; ?>;
var canViewTeamReports = <?php echo $canViewTeamReports ? 'true' : 'false'; ?>;
var teamMembers = [];

// Load team members on page load
$(document).ready(function() {
    if (canViewTeamReports) {
        loadTeamMembers();
    }
});

function loadTeamMembers() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php?ajax=getAllEmployees',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success && res.employees) {
                teamMembers = res.employees;
                var $select = $('#reportUserID');
                // Keep first option (Myself)
                var firstOption = $select.find('option:first').clone();
                $select.empty().append(firstOption);

                // Add All Employees (Master) option
                $select.append('<option value="all">All Employees (Master)</option>');

                // Add separator
                $select.append('<option disabled>─────────────</option>');

                // Add team members
                res.employees.forEach(function(emp) {
                    if (emp.userID != userID) {
                        $select.append('<option value="' + emp.userID + '">' + emp.userName + (emp.designation ? ' (' + emp.designation + ')' : '') + '</option>');
                    }
                });
            }
        }
    });
}

function getSelectedUserID() {
    if (canViewTeamReports) {
        var val = $('#reportUserID').val();
        if (val === 'all') return 'all';
        return parseInt(val) || userID;
    }
    return userID;
}

function selectReportType(el) {
    document.querySelectorAll('.report-card').forEach(function(card) {
        card.classList.remove('selected');
    });
    el.classList.add('selected');
    selectedReportType = el.dataset.type;

    // Toggle filters based on report type
    if (selectedReportType === 'leave') {
        document.getElementById('monthlyFilters').style.display = 'none';
        document.getElementById('fyFilters').style.display = 'grid';
    } else {
        document.getElementById('monthlyFilters').style.display = 'grid';
        document.getElementById('fyFilters').style.display = 'none';
    }
}

function generateReport() {
    var month = document.getElementById('filterMonth').value;
    var year = document.getElementById('filterYear').value;
    var fyYear = document.getElementById('filterFY').value;
    var targetUserID = getSelectedUserID();

    document.getElementById('previewBody').innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Generating report...</p></div>';
    document.getElementById('downloadButtons').style.display = 'none';

    // Use MODINCURL which is set by the CMS for proper module AJAX calls
    var ajaxUrl = (typeof MODINCURL !== 'undefined') ? MODINCURL : SITEURL + '/xsite/mod/hrms/x-hrms.inc.php';

    // Build request data based on report type
    var requestData = {
        xAction: 'getEmployeeReport',
        reportType: selectedReportType,
        targetUserID: targetUserID
    };

    // For leave reports, use FY; for others, use month/year
    if (selectedReportType === 'leave') {
        requestData.fyYear = fyYear;
    } else {
        requestData.month = month;
        requestData.year = year;
    }

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: requestData,
        dataType: 'json',
        success: function(res) {
            console.log('AJAX Response:', res);
            if (res.err === 0) {
                currentReportData = res;
                renderReport(res);
                document.getElementById('downloadButtons').style.display = 'flex';
            } else {
                document.getElementById('previewBody').innerHTML = '<div class="preview-placeholder"><p>Error: ' + (res.message || res.msg || 'Failed to generate report') + '</p></div>';
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', status, error);
            console.log('Response Text:', xhr.responseText);
            console.log('Status Code:', xhr.status);
            console.log('Response Headers:', xhr.getAllResponseHeaders());

            // Try to show the actual response
            var responsePreview = xhr.responseText ? xhr.responseText.substring(0, 500) : 'No response';
            document.getElementById('previewBody').innerHTML = '<div class="preview-placeholder"><p>Error: ' + status + ' - ' + error + '</p><p style="font-size:11px;color:#999;word-break:break-all;">Response: ' + responsePreview + '</p></div>';
        }
    });
}

function renderReport(data) {
    var html = '';

    switch (selectedReportType) {
        case 'monthly':
            html = renderMonthlyReport(data);
            document.getElementById('previewTitle').textContent = 'Monthly Attendance - ' + data.monthName;
            break;
        case 'summary':
            html = renderSummaryReport(data);
            document.getElementById('previewTitle').textContent = 'Summary Report - ' + data.monthName;
            break;
        case 'late_early':
            html = renderLateEarlyReport(data);
            document.getElementById('previewTitle').textContent = 'Late/Early Report - ' + data.monthName;
            break;
        case 'leave':
            html = renderLeaveReport(data);
            document.getElementById('previewTitle').textContent = 'Leave History - ' + (data.fyLabel || data.monthName);
            break;
        case 'detailed':
            html = renderDetailedReport(data);
            document.getElementById('previewTitle').textContent = 'Detailed Attendance - ' + data.monthName;
            break;
    }

    document.getElementById('previewBody').innerHTML = html;
}

function renderMonthlyReport(data) {
    // Check if this is a master report (all employees)
    if (data.isMaster && data.data) {
        return renderMasterMonthlyReport(data);
    }

    if (!data.days || Object.keys(data.days).length === 0) {
        return '<div class="preview-placeholder"><p>No attendance data found for this month</p></div>';
    }

    var daysInMonth = data.daysInMonth || 31;

    // Summary stats
    var html = '<div class="summary-row">';
    html += '<div class="summary-item present"><div class="summary-value">' + (data.summary.P || 0) + '</div><div class="summary-label">Present</div></div>';
    html += '<div class="summary-item absent"><div class="summary-value">' + (data.summary.A || 0) + '</div><div class="summary-label">Absent</div></div>';
    html += '<div class="summary-item late"><div class="summary-value">' + (data.summary.LT || 0) + '</div><div class="summary-label">Late</div></div>';
    html += '<div class="summary-item leave"><div class="summary-value">' + (data.summary.L || 0) + '</div><div class="summary-label">Leave</div></div>';
    html += '</div>';

    // Calendar grid
    html += '<div class="muster-table-wrapper"><table class="data-table muster-table">';
    html += '<thead><tr><th>Date</th>';

    // Week headers
    for (var d = 1; d <= daysInMonth; d++) {
        html += '<th class="day-cell">' + d + '</th>';
    }
    html += '</tr></thead>';

    html += '<tbody><tr><td>Status</td>';
    for (var d = 1; d <= daysInMonth; d++) {
        var status = data.days[d] || '-';
        html += '<td><span class="status-badge status-' + status + '">' + status + '</span></td>';
    }
    html += '</tr></tbody></table></div>';

    // Legend
    html += '<div style="margin-top:16px;padding:12px;background:var(--gray-50);border-radius:8px;font-size:12px;color:var(--gray-600);">';
    html += '<strong>Legend:</strong> ';
    html += '<span class="status-badge status-P">P</span> Present &nbsp;';
    html += '<span class="status-badge status-A">A</span> Absent &nbsp;';
    html += '<span class="status-badge status-L">L</span> Leave &nbsp;';
    html += '<span class="status-badge status-LT">LT</span> Late &nbsp;';
    html += '<span class="status-badge status-H">H</span> Holiday &nbsp;';
    html += '<span class="status-badge status-WO">WO</span> Weekly Off &nbsp;';
    html += '<span class="status-badge status-HD">HD</span> Half Day';
    html += '</div>';

    return html;
}

function renderMasterMonthlyReport(data) {
    var daysInMonth = data.daysInMonth || 31;

    // Summary stats
    var html = '<div class="summary-row">';
    html += '<div class="summary-item present"><div class="summary-value">' + (data.summary.P || 0) + '</div><div class="summary-label">Total Present</div></div>';
    html += '<div class="summary-item absent"><div class="summary-value">' + (data.summary.A || 0) + '</div><div class="summary-label">Total Absent</div></div>';
    html += '<div class="summary-item late"><div class="summary-value">' + (data.summary.LT || 0) + '</div><div class="summary-label">Total Late</div></div>';
    html += '<div class="summary-item leave"><div class="summary-value">' + (data.summary.L || 0) + '</div><div class="summary-label">Total Leave</div></div>';
    html += '</div>';

    // Muster roll table
    html += '<div class="muster-table-wrapper"><table class="data-table muster-table">';
    html += '<thead><tr><th>Emp Code</th><th>Name</th>';

    for (var d = 1; d <= daysInMonth; d++) {
        html += '<th class="day-cell">' + d + '</th>';
    }
    html += '<th>P</th><th>A</th><th>L</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(emp) {
        html += '<tr>';
        html += '<td style="white-space:nowrap;">' + emp.empCode + '</td>';
        html += '<td style="white-space:nowrap;">' + emp.empName + '</td>';

        for (var d = 1; d <= daysInMonth; d++) {
            var status = emp.days[d] || '-';
            html += '<td><span class="status-badge status-' + status + '">' + status + '</span></td>';
        }

        html += '<td><strong>' + (emp.summary.P || 0) + '</strong></td>';
        html += '<td>' + (emp.summary.A || 0) + '</td>';
        html += '<td>' + (emp.summary.L || 0) + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';

    // Legend
    html += '<div style="margin-top:16px;padding:12px;background:var(--gray-50);border-radius:8px;font-size:12px;color:var(--gray-600);">';
    html += '<strong>Legend:</strong> ';
    html += '<span class="status-badge status-P">P</span> Present &nbsp;';
    html += '<span class="status-badge status-A">A</span> Absent &nbsp;';
    html += '<span class="status-badge status-L">L</span> Leave &nbsp;';
    html += '<span class="status-badge status-LT">LT</span> Late &nbsp;';
    html += '<span class="status-badge status-H">H</span> Holiday &nbsp;';
    html += '<span class="status-badge status-WO">WO</span> Weekly Off &nbsp;';
    html += '<span class="status-badge status-HD">HD</span> Half Day';
    html += '</div>';

    return html;
}

function renderSummaryReport(data) {
    // Check if this is a master report (all employees)
    if (data.isMaster && data.data) {
        return renderMasterSummaryReport(data);
    }

    var html = '<div class="summary-row">';
    html += '<div class="summary-item"><div class="summary-value">' + (data.workingDays || 0) + '</div><div class="summary-label">Working Days</div></div>';
    html += '<div class="summary-item present"><div class="summary-value">' + (data.summary.P || 0) + '</div><div class="summary-label">Present</div></div>';
    html += '<div class="summary-item absent"><div class="summary-value">' + (data.summary.A || 0) + '</div><div class="summary-label">Absent</div></div>';
    html += '<div class="summary-item leave"><div class="summary-value">' + (data.summary.L || 0) + '</div><div class="summary-label">Leave</div></div>';
    html += '</div>';

    html += '<table class="data-table">';
    html += '<tbody>';
    html += '<tr><td><strong>Total Working Days</strong></td><td>' + (data.workingDays || 0) + '</td></tr>';
    html += '<tr><td><strong>Days Present</strong></td><td style="color:var(--green-500);">' + (data.summary.P || 0) + '</td></tr>';
    html += '<tr><td><strong>Days Absent</strong></td><td style="color:var(--red-500);">' + (data.summary.A || 0) + '</td></tr>';
    html += '<tr><td><strong>Days on Leave</strong></td><td style="color:var(--blue-600);">' + (data.summary.L || 0) + '</td></tr>';
    html += '<tr><td><strong>Half Days</strong></td><td>' + (data.summary.HD || 0) + '</td></tr>';
    html += '<tr><td><strong>Late Arrivals</strong></td><td style="color:#d97706;">' + (data.summary.LT || 0) + '</td></tr>';
    html += '<tr><td><strong>Holidays</strong></td><td>' + (data.summary.H || 0) + '</td></tr>';
    html += '<tr><td><strong>Weekly Offs</strong></td><td>' + (data.summary.WO || 0) + '</td></tr>';
    html += '<tr><td><strong>Total Working Hours</strong></td><td>' + (data.totalHours || 0) + ' hrs</td></tr>';
    html += '<tr><td><strong>Payable Days</strong></td><td><strong>' + (data.payableDays || 0) + '</strong></td></tr>';
    html += '</tbody></table>';

    return html;
}

function renderMasterSummaryReport(data) {
    var html = '<div class="summary-row">';
    html += '<div class="summary-item"><div class="summary-value">' + (data.totalEmployees || 0) + '</div><div class="summary-label">Employees</div></div>';
    html += '<div class="summary-item"><div class="summary-value">' + (data.workingDays || 0) + '</div><div class="summary-label">Working Days</div></div>';
    html += '<div class="summary-item present"><div class="summary-value">' + (data.summary.P || 0) + '</div><div class="summary-label">Total Present</div></div>';
    html += '<div class="summary-item"><div class="summary-value">' + (data.totalHours || 0) + '</div><div class="summary-label">Total Hours</div></div>';
    html += '</div>';

    html += '<table class="data-table">';
    html += '<thead><tr><th>Emp Code</th><th>Name</th><th>Present</th><th>Absent</th><th>Leave</th><th>Half Day</th><th>Late</th><th>Hours</th><th>Payable</th></tr></thead>';
    html += '<tbody>';

    data.data.forEach(function(emp) {
        html += '<tr>';
        html += '<td>' + emp.empCode + '</td>';
        html += '<td>' + emp.empName + '</td>';
        html += '<td style="color:var(--green-500);"><strong>' + emp.present + '</strong></td>';
        html += '<td style="color:var(--red-500);">' + emp.absent + '</td>';
        html += '<td style="color:var(--blue-600);">' + emp.leave + '</td>';
        html += '<td>' + emp.halfDay + '</td>';
        html += '<td style="color:#d97706;">' + emp.late + '</td>';
        html += '<td>' + emp.totalHours + '</td>';
        html += '<td><strong>' + emp.payableDays + '</strong></td>';
        html += '</tr>';
    });

    html += '</tbody></table>';

    return html;
}

function renderLateEarlyReport(data) {
    if (!data.records || data.records.length === 0) {
        return '<div class="preview-placeholder"><p>No late arrivals or early checkouts this month</p></div>';
    }

    var html = '<p style="margin-bottom:16px;color:var(--gray-600);">Found ' + data.records.length + ' records</p>';
    html += '<table class="data-table">';
    html += '<thead><tr><th>Date</th><th>Check In</th><th>Late By</th><th>Check Out</th><th>Early By</th><th>Status</th><th>Remark</th></tr></thead>';
    html += '<tbody>';

    data.records.forEach(function(row) {
        html += '<tr>';
        html += '<td>' + row.date + '</td>';
        html += '<td>' + (row.checkIn || '-') + '</td>';
        html += '<td>' + (row.lateMinutes > 0 ? '<span class="status-badge status-LT">' + row.lateMinutes + ' min</span>' : '-') + '</td>';
        html += '<td>' + (row.checkOut || '-') + '</td>';
        html += '<td>' + (row.earlyMinutes > 0 ? '<span class="status-badge status-LT">' + row.earlyMinutes + ' min</span>' : '-') + '</td>';
        html += '<td>' + row.status + '</td>';
        html += '<td style="color:var(--gray-500);font-size:12px;max-width:150px;">' + (row.reason || '-') + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    return html;
}

function renderLeaveReport(data) {
    if (!data.leaves || data.leaves.length === 0) {
        return '<div class="preview-placeholder"><p>No leaves taken in this financial year</p></div>';
    }

    // Calculate summary by leave type (approved leaves only)
    var leaveSummary = {};
    var totalDays = 0;
    var totalApproved = 0;
    var totalPending = 0;
    var totalRejected = 0;

    data.leaves.forEach(function(row) {
        var days = parseFloat(row.days) || 0;
        var status = (row.status || '').toLowerCase();

        if (status === 'approved') {
            if (!leaveSummary[row.leaveType]) {
                leaveSummary[row.leaveType] = 0;
            }
            leaveSummary[row.leaveType] += days;
            totalDays += days;
            totalApproved++;
        } else if (status === 'pending') {
            totalPending++;
        } else if (status === 'rejected' || status === 'disapproved') {
            totalRejected++;
        }
    });

    // Summary cards
    var html = '<div class="summary-row">';
    html += '<div class="summary-item present"><div class="summary-value">' + totalApproved + '</div><div class="summary-label">Approved</div></div>';
    html += '<div class="summary-item late"><div class="summary-value">' + totalPending + '</div><div class="summary-label">Pending</div></div>';
    html += '<div class="summary-item absent"><div class="summary-value">' + totalRejected + '</div><div class="summary-label">Rejected</div></div>';
    html += '<div class="summary-item leave"><div class="summary-value">' + totalDays + '</div><div class="summary-label">Days Taken</div></div>';
    html += '</div>';

    // Leave type breakdown (if there are approved leaves)
    if (Object.keys(leaveSummary).length > 0) {
        html += '<div style="margin-bottom:20px;padding:16px;background:var(--gray-50);border-radius:10px;">';
        html += '<div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:12px;">Leave Breakdown (Approved)</div>';
        html += '<div style="display:flex;flex-wrap:wrap;gap:12px;">';

        var typeColors = {
            'casual leave': '#3498db',
            'sick leave': '#e74c3c',
            'earned leave': '#27ae60',
            'privilege leave': '#27ae60',
            'comp off': '#f39c12',
            'maternity leave': '#e91e63',
            'paternity leave': '#9c27b0'
        };

        Object.keys(leaveSummary).forEach(function(type) {
            var days = leaveSummary[type];
            var typeLower = type.toLowerCase();
            var color = typeColors[typeLower] || '#6b7280';

            html += '<div style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:#fff;border-radius:8px;border:1px solid var(--gray-200);">';
            html += '<span style="width:10px;height:10px;border-radius:50%;background:' + color + ';"></span>';
            html += '<span style="font-size:13px;color:var(--gray-700);">' + type + '</span>';
            html += '<span style="font-size:14px;font-weight:700;color:var(--gray-900);">' + days + ' days</span>';
            html += '</div>';
        });

        html += '</div>';
        html += '<div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;">';
        html += '<span style="font-size:13px;font-weight:600;color:var(--gray-700);">Total Leaves Taken</span>';
        html += '<span style="font-size:18px;font-weight:700;color:var(--blue-600);">' + totalDays + ' days</span>';
        html += '</div>';
        html += '</div>';
    }

    // Leave history table
    html += '<table class="data-table">';
    html += '<thead><tr><th>Leave Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th></tr></thead>';
    html += '<tbody>';

    data.leaves.forEach(function(row) {
        var statusClass = row.status === 'approved' ? 'status-P' : (row.status === 'rejected' || row.status === 'disapproved' ? 'status-A' : 'status-LT');
        html += '<tr>';
        html += '<td>' + row.leaveType + '</td>';
        html += '<td>' + row.fromDate + '</td>';
        html += '<td>' + row.toDate + '</td>';
        html += '<td>' + row.days + '</td>';
        html += '<td>' + (row.reason || '-') + '</td>';
        html += '<td><span class="status-badge ' + statusClass + '">' + row.status + '</span></td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    return html;
}

function renderDetailedReport(data) {
    if (!data.records || data.records.length === 0) {
        return '<div class="preview-placeholder"><p>No attendance records found for this month</p></div>';
    }

    // Check if this is a master report
    var isMaster = data.isMaster || false;

    // Summary at top - different display for master vs individual
    var html = '<div class="summary-row">';
    if (isMaster) {
        html += '<div class="summary-item"><div class="summary-value">' + (data.totalEmployees || 0) + '</div><div class="summary-label">Employees</div></div>';
        html += '<div class="summary-item present"><div class="summary-value">' + (data.presentDays || 0) + '</div><div class="summary-label">Present</div></div>';
        html += '<div class="summary-item absent"><div class="summary-value">' + (data.absentDays || 0) + '</div><div class="summary-label">Absent</div></div>';
        html += '<div class="summary-item leave"><div class="summary-value">' + (data.leaveDays || 0) + '</div><div class="summary-label">Leave</div></div>';
        html += '<div class="summary-item"><div class="summary-value">' + (data.totalHours || '0.0') + '</div><div class="summary-label">Total Hours</div></div>';
    } else {
        html += '<div class="summary-item"><div class="summary-value">' + (data.totalDays || 0) + '</div><div class="summary-label">Total Days</div></div>';
        html += '<div class="summary-item present"><div class="summary-value">' + (data.presentDays || 0) + '</div><div class="summary-label">Present</div></div>';
        html += '<div class="summary-item"><div class="summary-value">' + (data.totalHours || '0.0') + '</div><div class="summary-label">Total Hours</div></div>';
        html += '<div class="summary-item"><div class="summary-value">' + (data.avgHours || '0.0') + '</div><div class="summary-label">Avg Hours/Day</div></div>';
    }
    html += '</div>';

    // Detailed table
    html += '<table class="data-table">';
    if (isMaster) {
        html += '<thead><tr><th>Emp Code</th><th>Name</th><th>Day</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Working Hours</th><th>Status</th><th>Remark</th></tr></thead>';
    } else {
        html += '<thead><tr><th>Day</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Working Hours</th><th>Status</th><th>Remark</th></tr></thead>';
    }
    html += '<tbody>';

    data.records.forEach(function(row) {
        var statusClass = 'status-' + (row.statusCode || 'P');
        html += '<tr>';
        if (isMaster) {
            html += '<td>' + (row.empCode || '-') + '</td>';
            html += '<td>' + (row.empName || '-') + '</td>';
        }
        html += '<td>' + row.dayName + '</td>';
        html += '<td>' + row.date + '</td>';
        html += '<td>' + (row.checkIn || '-') + '</td>';
        html += '<td>' + (row.checkOut || '-') + '</td>';
        html += '<td>' + (row.workingHours || '-') + '</td>';
        html += '<td><span class="status-badge ' + statusClass + '">' + row.status + '</span></td>';
        html += '<td style="color:var(--gray-500);font-size:12px;max-width:150px;">' + (row.remark || '-') + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    return html;
}

function downloadReport(format) {
    var month = document.getElementById('filterMonth').value;
    var year = document.getElementById('filterYear').value;
    var fyYear = document.getElementById('filterFY').value;
    var targetUserID = getSelectedUserID();

    var baseUrl = (typeof MODINCURL !== 'undefined') ? MODINCURL : SITEURL + '/xsite/mod/hrms/x-hrms.inc.php';
    var url = baseUrl + '?ajax=downloadReport' +
        '&format=' + format +
        '&type=' + selectedReportType +
        '&targetUserID=' + targetUserID;

    // Add appropriate date params based on report type
    if (selectedReportType === 'leave') {
        url += '&fyYear=' + fyYear;
    } else {
        url += '&month=' + month + '&year=' + year;
    }

    window.open(url, '_blank');
}
</script>
