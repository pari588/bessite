<?php
/**
 * Salary Processing Page
 * Accessible by HR Admin and Accounts Person only
 */

// Check access
$canAccess = ($_SESSION['HRMS_IS_HR_ADMIN'] ?? false) || ($_SESSION['HRMS_IS_ACCOUNTS'] ?? false);
if (!$canAccess) {
    header('Location: ' . SITEURL . '/hrms/home/');
    exit;
}

// Get employees list for dropdowns
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT userID, displayName FROM `" . $DB->pre . "x_admin_user` WHERE status=? ORDER BY displayName";
$employees = $DB->dbRows();
?>

<style>
.salary-header {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.salary-filters {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.salary-filters select {
    padding: 10px 16px;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    min-width: 120px;
}

.salary-actions {
    display: flex;
    gap: 8px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.summary-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    padding: 20px;
}

.summary-card-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.summary-card-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-900);
}

.summary-card-value.green { color: var(--green-500); }
.summary-card-value.red { color: var(--red-500); }
.summary-card-value.blue { color: var(--blue-600); }

.salary-table-wrapper {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    overflow: hidden;
}

.salary-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.salary-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--gray-200);
    white-space: nowrap;
}

.salary-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.salary-table tbody tr:hover {
    background: var(--gray-50);
}

.salary-table .emp-name {
    font-weight: 600;
    color: var(--gray-900);
}

.salary-table .emp-dept {
    font-size: 12px;
    color: var(--gray-500);
}

.salary-table .amount {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    text-align: right;
}

.salary-table .amount.deduction {
    color: var(--red-500);
}

.salary-table .amount.net {
    color: var(--green-500);
    font-size: 15px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 9999px;
    text-transform: uppercase;
}

.status-badge.pending {
    background: var(--yellow-100);
    color: #b45309;
}

.status-badge.paid {
    background: var(--green-100);
    color: var(--green-500);
}

.status-badge.slip_generated {
    background: var(--blue-100);
    color: var(--blue-600);
}

.paid-input {
    width: 100px;
    padding: 6px 10px;
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    font-size: 14px;
    font-family: 'JetBrains Mono', monospace;
    text-align: right;
}

.paid-input:focus {
    border-color: var(--blue-500);
    outline: none;
    box-shadow: 0 0 0 3px var(--blue-100);
}

.action-btns {
    display: flex;
    gap: 6px;
}

.action-btn {
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
}

.action-btn.pay {
    background: var(--green-500);
    color: #fff;
}

.action-btn.pay:hover {
    background: #16a34a;
}

.action-btn.slip {
    background: var(--blue-600);
    color: #fff;
}

.action-btn.slip:hover {
    background: var(--blue-700);
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.table-footer-total {
    font-size: 14px;
    color: var(--gray-600);
}

.table-footer-total strong {
    font-size: 18px;
    color: var(--gray-900);
}

.table-scroll {
    overflow-x: auto;
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
    max-width: 450px;
    margin: 20px;
    transform: scale(0.95);
    transition: transform 0.2s;
}

.modal-overlay.active .modal {
    transform: scale(1);
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.form-row {
    margin-bottom: 16px;
}

.form-row label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 6px;
}

.form-row input,
.form-row select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 15px;
}

.form-row input:focus,
.form-row select:focus {
    border-color: var(--blue-500);
    outline: none;
    box-shadow: 0 0 0 3px var(--blue-100);
}

/* Payroll Tabs */
.payroll-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 0;
}

.payroll-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 14px;
    font-weight: 500;
    color: var(--gray-500);
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: -1px;
}

.payroll-tab:hover {
    color: var(--gray-700);
}

.payroll-tab.active {
    color: var(--blue-600);
    border-bottom-color: var(--blue-600);
}

.payroll-tab svg {
    width: 18px;
    height: 18px;
}

.payroll-tab-content {
    display: none;
}

.payroll-tab-content.active {
    display: block;
}

/* Advances Section */
.advances-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.advances-stats {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.advance-stat {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.advance-stat-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.advance-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
}

.advance-stat-value.red { color: var(--red-500); }
.advance-stat-value.green { color: var(--green-500); }

.progress-bar {
    width: 80px;
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
    margin-top: 4px;
}

.progress-bar-fill {
    height: 100%;
    background: var(--green-500);
    border-radius: 3px;
    transition: width 0.3s;
}

.form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 15px;
    background: #fff;
}

.form-select:focus {
    border-color: var(--blue-500);
    outline: none;
    box-shadow: 0 0 0 3px var(--blue-100);
}

@media (max-width: 768px) {
    .salary-header {
        flex-direction: column;
        align-items: stretch;
    }

    .salary-filters {
        justify-content: space-between;
    }

    .salary-table {
        font-size: 13px;
    }

    .salary-table th,
    .salary-table td {
        padding: 10px 12px;
    }

    .payroll-tabs {
        overflow-x: auto;
    }

    .advances-header {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="page-header">
    <h1>Payroll Management</h1>
    <p>Process salaries, manage advances, and track deductions</p>
</div>

<!-- Tabs -->
<div class="payroll-tabs">
    <button class="payroll-tab active" data-tab="processing" onclick="switchPayrollTab('processing')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        Salary Processing
    </button>
    <button class="payroll-tab" data-tab="advances" onclick="switchPayrollTab('advances')">
        <svg viewBox="0 0 16 16" fill="currentColor" style="width:18px;height:18px"><path d="M4 3.06h2.726c1.22 0 2.12.575 2.325 1.724H4v1.051h5.051C8.855 7.001 8 7.558 6.788 7.558H4v1.317L8.437 14h2.11L6.095 8.884h.855c2.316-.018 3.465-1.476 3.688-3.049H12V4.784h-1.345c-.08-.778-.357-1.335-.793-1.732H12V2H4z"/></svg>
        Salary Advances
    </button>
    <button class="payroll-tab" data-tab="history" onclick="switchPayrollTab('history')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Payment History
    </button>
</div>

<!-- Salary Processing Tab -->
<div class="payroll-tab-content active" id="tab-processing">

<!-- Filters -->
<div class="salary-header">
    <div class="salary-filters">
        <select id="monthSelect">
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
        <select id="yearSelect">
            <?php
            $currentYear = date('Y');
            for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
                $selected = ($y == $currentYear) ? 'selected' : '';
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
        </select>
        <button type="button" class="btn btn-primary" onclick="loadSalaryData()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M21 12a9 9 0 11-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
            Load
        </button>
    </div>
    <div class="salary-actions">
        <button type="button" class="btn btn-secondary" onclick="exportToExcel()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export
        </button>
        <button type="button" class="btn btn-primary" onclick="openCMSModal()" id="cmsBtn" style="background:#f97316;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            ICICI CMS
        </button>
        <button type="button" class="btn btn-secondary" onclick="openCMSSettingsModal()" title="Bank Settings" style="padding:8px 12px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="summary-card-label">Total Employees</div>
        <div class="summary-card-value" id="totalEmployees">-</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Gross Salary</div>
        <div class="summary-card-value blue" id="totalGross">-</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Total Deductions</div>
        <div class="summary-card-value red" id="totalDeductions">-</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Net Payable</div>
        <div class="summary-card-value green" id="totalNet">-</div>
    </div>
</div>

<!-- Salary Table -->
<div class="salary-table-wrapper">
    <div class="table-scroll">
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Gross</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th>Deductions</th>
                    <th>Net Salary</th>
                    <th>Pay Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="salaryTableBody">
                <tr>
                    <td colspan="10" class="text-center" style="padding:40px;">
                        <div class="loading">
                            <div class="spinner"></div>
                            <p class="mt-4 text-muted">Loading salary data...</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div class="table-footer-total">
            <span id="paidCount">0</span> of <span id="empCount">0</span> employees paid
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="markAllPaid()" id="markAllBtn" disabled>
                Mark All as Paid
            </button>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Mark Salary as Paid</h3>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <label>Employee</label>
                <input type="text" id="modalEmpName" readonly style="background:var(--gray-50)">
            </div>
            <div class="form-row">
                <label>Net Salary (Calculated)</label>
                <input type="text" id="modalNetSalary" readonly style="background:var(--gray-50)">
            </div>
            <div class="form-row">
                <label>Amount to Pay</label>
                <input type="number" id="modalPayAmount" step="0.01" min="0">
            </div>
            <div class="form-row">
                <label>Payment Mode</label>
                <select id="modalPayMode">
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>
            <div class="form-row">
                <label>Transaction Reference</label>
                <input type="text" id="modalTxnRef" placeholder="UTR / Cheque No / Reference">
            </div>
            <input type="hidden" id="modalUserID">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="confirmPayment()">Confirm Payment</button>
        </div>
    </div>
</div>

</div><!-- End tab-processing -->

<!-- Salary Advances Tab -->
<div class="payroll-tab-content" id="tab-advances">
    <div class="advances-header">
        <div class="advances-stats">
            <div class="advance-stat">
                <span class="advance-stat-label">Active Advances</span>
                <span class="advance-stat-value" id="activeAdvances">-</span>
            </div>
            <div class="advance-stat">
                <span class="advance-stat-label">Total Outstanding</span>
                <span class="advance-stat-value red" id="totalOutstanding">-</span>
            </div>
            <div class="advance-stat">
                <span class="advance-stat-label">This Month Deductions</span>
                <span class="advance-stat-value green" id="monthlyDeductions">-</span>
            </div>
        </div>
        <button class="btn btn-primary" onclick="openAdvanceModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M12 5v14M5 12h14"/></svg>
            Give Advance
        </button>
    </div>

    <!-- Advances Table -->
    <div class="salary-table-wrapper">
        <div class="table-scroll">
            <table class="salary-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Advance Date</th>
                        <th>Amount</th>
                        <th>Deduction</th>
                        <th>Repaid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="advancesTableBody">
                    <tr>
                        <td colspan="8" class="text-center" style="padding:40px;">
                            <p class="text-muted">Click on "Salary Advances" tab to load data</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment History Tab -->
<div class="payroll-tab-content" id="tab-history">
    <div class="salary-header">
        <div class="salary-filters">
            <select id="historyMonthSelect">
                <option value="">All Months</option>
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
            <select id="historyYearSelect">
                <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= $currentYear - 3; $y--) {
                    $selected = ($y == $currentYear) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
            <select id="historyEmployeeSelect">
                <option value="">All Employees</option>
                <?php
                foreach ($employees as $emp) {
                    echo '<option value="' . $emp['userID'] . '">' . htmlspecialchars($emp['displayName']) . '</option>';
                }
                ?>
            </select>
            <button type="button" class="btn btn-primary" onclick="loadPaymentHistory()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M21 12a9 9 0 11-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                Load
            </button>
        </div>
        <div class="salary-actions">
            <button type="button" class="btn btn-secondary" onclick="exportHistoryToExcel()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </button>
        </div>
    </div>

    <!-- History Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-label">Total Payments</div>
            <div class="summary-card-value" id="historyTotalPayments">-</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-label">Total Amount Paid</div>
            <div class="summary-card-value green" id="historyTotalAmount">-</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-label">Total Deductions</div>
            <div class="summary-card-value red" id="historyTotalDeductions">-</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-label">Months Processed</div>
            <div class="summary-card-value blue" id="historyMonthsProcessed">-</div>
        </div>
    </div>

    <!-- History Table -->
    <div class="salary-table-wrapper">
        <div class="table-scroll">
            <table class="salary-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Employee</th>
                        <th>Gross Salary</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Amount Paid</th>
                        <th>Payment Mode</th>
                        <th>Paid On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr>
                        <td colspan="9" class="text-center" style="padding:40px;">
                            <p class="text-muted">Click "Load" to view payment history</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Give Advance Modal -->
<div class="modal-overlay" id="advanceModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="advanceModalTitle">Give Salary Advance</h3>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <label>Employee</label>
                <select id="advanceEmployee" class="form-select">
                    <option value="">Select Employee</option>
                    <?php
                    $DB->vals = array(1);
                    $DB->types = "i";
                    $DB->sql = "SELECT userID, displayName FROM `" . $DB->pre . "x_admin_user` WHERE status=? ORDER BY displayName";
                    $employees = $DB->dbRows();
                    foreach ($employees as $emp) {
                        echo '<option value="' . $emp['userID'] . '">' . htmlspecialchars($emp['displayName']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-row">
                <label>Advance Amount (₹)</label>
                <input type="number" id="advanceAmount" min="1" step="1" placeholder="Enter amount">
            </div>
            <div class="form-row">
                <label>Advance Date</label>
                <input type="date" id="advanceDate" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-row">
                <label>Reason</label>
                <input type="text" id="advanceReason" placeholder="Reason for advance">
            </div>
            <div class="form-row">
                <label>Deduction Frequency</label>
                <select id="deductionFrequency" onchange="toggleDeductionOptions()">
                    <option value="one_time">One-time (Full amount in next salary)</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="quarterly">Quarterly (Every 3 months)</option>
                    <option value="half_yearly">Half-Yearly (Every 6 months)</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom (Every N months)</option>
                </select>
            </div>
            <div class="form-row" id="customMonthsRow" style="display:none;">
                <label>Deduct Every (months)</label>
                <input type="number" id="customMonths" min="1" max="12" step="1" value="2" placeholder="e.g., 2">
            </div>
            <div class="form-row" id="emiRow">
                <label>Deduction Amount per Installment (₹)</label>
                <input type="number" id="monthlyEMI" min="1" step="1" placeholder="Amount per deduction">
                <small class="text-muted" id="emiInfo"></small>
            </div>
            <div class="form-row">
                <label>Start Deduction From</label>
                <div style="display:flex; gap:10px;">
                    <select id="deductFromMonth" style="flex:1;">
                        <?php
                        $months = ['January', 'February', 'March', 'April', 'May', 'June',
                                   'July', 'August', 'September', 'October', 'November', 'December'];
                        $nextMonth = date('n') + 1;
                        if ($nextMonth > 12) $nextMonth = 1;
                        foreach ($months as $i => $month) {
                            $selected = (($i + 1) == $nextMonth) ? 'selected' : '';
                            echo "<option value='" . ($i + 1) . "' $selected>$month</option>";
                        }
                        ?>
                    </select>
                    <select id="deductFromYear" style="flex:1;">
                        <?php
                        $currentYear = date('Y');
                        $nextMonthYear = (date('n') == 12) ? $currentYear + 1 : $currentYear;
                        for ($y = $currentYear; $y <= $currentYear + 2; $y++) {
                            $selected = ($y == $nextMonthYear) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <input type="hidden" id="editAdvanceID" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAdvanceModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveAdvance()">Save Advance</button>
        </div>
    </div>
</div>

<!-- Record Repayment Modal -->
<div class="modal-overlay" id="repaymentModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Record Repayment</h3>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <label>Employee</label>
                <input type="text" id="repayEmpName" readonly style="background:var(--gray-50)">
            </div>
            <div class="form-row">
                <label>Outstanding Balance</label>
                <input type="text" id="repayBalance" readonly style="background:var(--gray-50)">
                <input type="hidden" id="repayBalanceRaw" value="0">
            </div>
            <div class="form-row">
                <label>Repayment Type</label>
                <select id="repayType" onchange="toggleRepaymentType()">
                    <option value="installment">Regular Installment</option>
                    <option value="partial">Partial Payment</option>
                    <option value="full">Full & Final Settlement</option>
                </select>
            </div>
            <div class="form-row" id="repayAmountRow">
                <label>Repayment Amount (₹)</label>
                <input type="number" id="repayAmount" min="1" step="1">
            </div>
            <div class="form-row">
                <label>Payment Mode</label>
                <select id="repayMode">
                    <option value="salary_deduction">Salary Deduction</option>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="upi">UPI</option>
                    <option value="cheque">Cheque</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-row">
                <label>Remarks</label>
                <input type="text" id="repayRemarks" placeholder="Optional remarks">
            </div>
            <input type="hidden" id="repayAdvanceID" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeRepaymentModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveRepayment()">Record Payment</button>
        </div>
    </div>
</div>

<!-- ICICI CMS File Generation Modal -->
<div class="modal-overlay" id="cmsModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header" style="background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;">
            <h3 style="display:flex;align-items:center;gap:10px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:24px;height:24px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Generate ICICI CMS File
            </h3>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <label>Payment Date (DD-MM-YYYY)</label>
                <input type="text" id="cmsPaymentDate" value="<?php echo date('d-m-Y'); ?>" placeholder="DD-MM-YYYY">
            </div>

            <div id="cmsSummaryBox" style="background:var(--gray-50);border-radius:8px;padding:16px;margin:16px 0;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <div style="font-size:12px;color:var(--gray-500);text-transform:uppercase;">Valid Employees</div>
                        <div style="font-size:24px;font-weight:700;color:var(--green-500);" id="cmsValidCount">-</div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:var(--gray-500);text-transform:uppercase;">Invalid (Excluded)</div>
                        <div style="font-size:24px;font-weight:700;color:var(--red-500);" id="cmsInvalidCount">-</div>
                    </div>
                    <div style="grid-column:span 2;">
                        <div style="font-size:12px;color:var(--gray-500);text-transform:uppercase;">Total Amount</div>
                        <div style="font-size:28px;font-weight:700;color:var(--blue-600);" id="cmsTotalAmount">-</div>
                    </div>
                </div>
            </div>

            <div id="cmsInvalidList" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;margin-bottom:16px;">
                <div style="font-weight:600;color:#dc2626;margin-bottom:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Excluded Employees (Missing Bank Details)
                </div>
                <ul id="cmsInvalidEmployees" style="margin:0;padding-left:20px;font-size:13px;color:#7f1d1d;"></ul>
            </div>

            <div style="background:#eff6ff;border:1px solid #3b82f6;border-radius:8px;padding:12px;margin:16px 0;">
                <p style="margin:0;font-size:12px;color:#1e40af;">
                    <i class="fa fa-info-circle" style="margin-right:6px;"></i>
                    <strong>Note:</strong> Download this .txt file and encrypt it using the ICICI AES Encryptor (JAR) before uploading to the CMS portal.
                </p>
            </div>

            <div id="cmsPreviewBox" style="display:none;background:#000;border-radius:8px;padding:16px;margin-top:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="color:#888;font-size:12px;">Preview (first 5 lines)</span>
                    <button type="button" onclick="toggleCMSPreview()" style="color:#888;background:transparent;border:none;cursor:pointer;font-size:12px;">Hide</button>
                </div>
                <pre id="cmsPreviewContent" style="color:#22c55e;font-family:monospace;font-size:12px;margin:0;overflow-x:auto;white-space:pre;"></pre>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCMSModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="downloadCMSFile()" id="downloadCMSBtn" style="background:#f97316;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:4px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download CMS File
            </button>
        </div>
    </div>
</div>

<!-- ICICI Bank Settings Modal -->
<div class="modal-overlay" id="cmsSettingsModal">
    <div class="modal" style="max-width:450px;">
        <div class="modal-header">
            <h3>ICICI Bank Settings</h3>
        </div>
        <div class="modal-body">
            <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px;">
                Configure your company's ICICI Bank details for CMS file generation. These settings are used in the header record of the CMS file.
            </p>
            <div class="form-row">
                <label>Company Code (ICICI Assigned)</label>
                <input type="text" id="settingsCompanyCode" placeholder="e.g., BOMENG001">
                <small style="color:var(--gray-500);font-size:11px;">Get this from your ICICI Corporate Banking portal</small>
            </div>
            <div class="form-row">
                <label>Debit Account Number</label>
                <input type="text" id="settingsDebitAccount" placeholder="e.g., 123456789012">
                <small style="color:var(--gray-500);font-size:11px;">Company's ICICI current account for salary debits</small>
            </div>
            <div class="form-row">
                <label>Company Name</label>
                <input type="text" id="settingsCompanyName" value="BOMBAY ENGINEERING SYNDICATE">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCMSSettingsModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveCMSSettings()">Save Settings</button>
        </div>
    </div>
</div>

<script>
var salaryData = [];
var advancesData = [];
var currentMonth = <?php echo date('n'); ?>;
var currentYear = <?php echo date('Y'); ?>;

$(function() {
    loadSalaryData();
});

function loadSalaryData() {
    currentMonth = $('#monthSelect').val();
    currentYear = $('#yearSelect').val();

    $('#salaryTableBody').html('<tr><td colspan="10" class="text-center" style="padding:40px;"><div class="loading"><div class="spinner"></div><p class="mt-4 text-muted">Loading salary data...</p></div></td></tr>');

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'getSalaryProcessingList',
        month: currentMonth,
        year: currentYear
    }, function(res) {
        console.log('Salary API Response:', res);
        if (res.err === 0) {
            salaryData = res.employees;
            renderSalaryTable(res);
            updateSummary(res);
        } else {
            $('#salaryTableBody').html('<tr><td colspan="10" class="text-center" style="padding:40px;color:var(--red-500);">' + res.msg + '</td></tr>');
        }
    }, 'json').fail(function(xhr, status, error) {
        console.error('Salary API Error:', status, error, xhr.responseText);
        $('#salaryTableBody').html('<tr><td colspan="10" class="text-center" style="padding:40px;color:var(--red-500);">Failed to load data</td></tr>');
    });
}

function renderSalaryTable(res) {
    var html = '';
    var paidCount = 0;

    if (res.employees.length === 0) {
        html = '<tr><td colspan="10" class="text-center" style="padding:40px;">No employees found</td></tr>';
    } else {
        res.employees.forEach(function(emp, i) {
            var statusClass = emp.slipStatus || 'pending';
            var statusText = statusClass.replace('_', ' ');
            if (emp.slipStatus === 'paid' || emp.slipStatus === 'slip_generated') paidCount++;

            html += '<tr data-userid="' + emp.userID + '">';
            html += '<td><div class="emp-name">' + emp.employeeName + '</div><div class="emp-dept">' + (emp.department || '-') + '</div></td>';
            html += '<td class="amount">' + formatCurrency(emp.basicSalary) + '</td>';
            html += '<td>' + emp.presentDays + '/' + emp.workingDays + '</td>';
            html += '<td>' + emp.absentDays + '</td>';
            html += '<td>' + emp.lateDays + '</td>';
            html += '<td class="amount deduction">-' + formatCurrency(emp.totalDeductions) + '</td>';
            html += '<td class="amount net">' + formatCurrency(emp.netSalary) + '</td>';
            html += '<td><input type="number" class="paid-input" value="' + (emp.paidAmount || emp.netSalary) + '" data-userid="' + emp.userID + '" ' + (emp.slipStatus === 'paid' || emp.slipStatus === 'slip_generated' ? 'disabled' : '') + '></td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
            html += '<td class="action-btns">';

            if (emp.slipStatus !== 'paid' && emp.slipStatus !== 'slip_generated') {
                html += '<button class="action-btn pay" onclick="openPaymentModal(' + i + ')">Pay</button>';
            } else if (emp.slipStatus === 'paid') {
                html += '<button class="action-btn slip" onclick="generateSlip(' + emp.userID + ')">Slip</button>';
            } else {
                html += '<span style="color:var(--green-500);font-size:12px;">Done</span>';
            }

            html += '</td>';
            html += '</tr>';
        });
    }

    $('#salaryTableBody').html(html);
    $('#paidCount').text(paidCount);
    $('#empCount').text(res.employees.length);
    $('#markAllBtn').prop('disabled', paidCount === res.employees.length);
}

function updateSummary(res) {
    $('#totalEmployees').text(res.employees.length);
    $('#totalGross').text(formatCurrency(res.totalGross));
    $('#totalDeductions').text(formatCurrency(res.totalDeductions));
    $('#totalNet').text(formatCurrency(res.totalNet));
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}

function openPaymentModal(index) {
    var emp = salaryData[index];
    $('#modalEmpName').val(emp.employeeName);
    $('#modalNetSalary').val(formatCurrency(emp.netSalary));
    $('#modalPayAmount').val(emp.netSalary);
    $('#modalUserID').val(emp.userID);
    $('#modalPayMode').val('Bank Transfer');
    $('#modalTxnRef').val('');
    $('#paymentModal').addClass('active');
}

function closePaymentModal() {
    $('#paymentModal').removeClass('active');
}

function confirmPayment() {
    var userID = $('#modalUserID').val();
    var paidAmount = $('#modalPayAmount').val();
    var paymentMode = $('#modalPayMode').val();
    var transactionRef = $('#modalTxnRef').val();

    if (!paidAmount || paidAmount <= 0) {
        alert('Please enter a valid amount');
        return;
    }

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'markSalaryPaid',
        userID: userID,
        month: currentMonth,
        year: currentYear,
        paidAmount: paidAmount,
        paymentMode: paymentMode,
        transactionRef: transactionRef
    }, function(res) {
        if (res.err === 0) {
            closePaymentModal();
            loadSalaryData();
        } else {
            alert(res.msg);
        }
    }, 'json');
}

function generateSlip(userID) {
    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'generateSalarySlip',
        userID: userID,
        month: currentMonth,
        year: currentYear
    }, function(res) {
        if (res.err === 0) {
            alert('Salary slip generated successfully');
            loadSalaryData();
        } else {
            alert(res.msg);
        }
    }, 'json');
}

function markAllPaid() {
    if (!confirm('Mark all pending salaries as paid with their calculated net amounts?')) return;

    var pending = salaryData.filter(function(e) {
        return e.slipStatus !== 'paid' && e.slipStatus !== 'slip_generated';
    });

    var processed = 0;
    pending.forEach(function(emp) {
        $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
            xAction: 'markSalaryPaid',
            userID: emp.userID,
            month: currentMonth,
            year: currentYear,
            paidAmount: emp.netSalary,
            paymentMode: 'Bank Transfer',
            transactionRef: ''
        }, function(res) {
            processed++;
            if (processed === pending.length) {
                loadSalaryData();
            }
        }, 'json');
    });
}

function exportToExcel() {
    var month = $('#monthSelect option:selected').text();
    var year = $('#yearSelect').val();

    var csv = 'Employee,Department,Gross Salary,Present Days,Absent Days,Late Days,Deductions,Net Salary,Status\n';
    salaryData.forEach(function(emp) {
        csv += '"' + emp.employeeName + '","' + (emp.department || '') + '",' + emp.basicSalary + ',' + emp.presentDays + ',' + emp.absentDays + ',' + emp.lateDays + ',' + emp.totalDeductions + ',' + emp.netSalary + ',"' + (emp.slipStatus || 'pending') + '"\n';
    });

    var blob = new Blob([csv], { type: 'text/csv' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'Salary_' + month + '_' + year + '.csv';
    a.click();
}

// Close modal on backdrop click
$('#paymentModal').on('click', function(e) {
    if (e.target === this) closePaymentModal();
});

// ========== Tab Switching ==========
function switchPayrollTab(tab) {
    $('.payroll-tab').removeClass('active');
    $('.payroll-tab[data-tab="' + tab + '"]').addClass('active');
    $('.payroll-tab-content').removeClass('active');
    $('#tab-' + tab).addClass('active');

    if (tab === 'advances') {
        loadAdvances();
    } else if (tab === 'history') {
        loadPaymentHistory();
    }
}

// ========== Payment History Functions ==========
var historyData = [];

function loadPaymentHistory() {
    var month = $('#historyMonthSelect').val();
    var year = $('#historyYearSelect').val();
    var userID = $('#historyEmployeeSelect').val();

    $('#historyTableBody').html('<tr><td colspan="9" class="text-center" style="padding:40px;"><div class="loading"><div class="spinner"></div><p class="mt-4 text-muted">Loading payment history...</p></div></td></tr>');

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'getPaymentHistory',
        month: month,
        year: year,
        userID: userID
    }, function(res) {
        if (res.err === 0) {
            historyData = res.payments;
            renderHistoryTable(res);
            updateHistorySummary(res);
        } else {
            $('#historyTableBody').html('<tr><td colspan="9" class="text-center" style="padding:40px;color:var(--red-500);">' + res.msg + '</td></tr>');
        }
    }, 'json').fail(function() {
        $('#historyTableBody').html('<tr><td colspan="9" class="text-center" style="padding:40px;color:var(--red-500);">Failed to load history</td></tr>');
    });
}

function renderHistoryTable(res) {
    var html = '';
    var months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    if (res.payments.length === 0) {
        html = '<tr><td colspan="9" class="text-center" style="padding:40px;"><p class="text-muted">No payment records found for the selected period</p></td></tr>';
    } else {
        res.payments.forEach(function(pay) {
            var statusClass = pay.slipStatus || 'pending';
            var statusText = statusClass.replace('_', ' ');
            var monthYear = months[parseInt(pay.salaryMonth)] + ' ' + pay.salaryYear;

            html += '<tr>';
            html += '<td><strong>' + monthYear + '</strong></td>';
            html += '<td><div class="emp-name">' + pay.employeeName + '</div><div class="emp-dept">' + (pay.department || '-') + '</div></td>';
            html += '<td class="amount">' + formatCurrency(pay.grossSalary) + '</td>';
            html += '<td class="amount deduction">-' + formatCurrency(pay.totalDeductions) + '</td>';
            html += '<td class="amount">' + formatCurrency(pay.netSalary) + '</td>';
            html += '<td class="amount net">' + formatCurrency(pay.amountPaid) + '</td>';
            html += '<td>' + (pay.paymentMode || '-') + '</td>';
            html += '<td>' + (pay.paidOn ? formatDate(pay.paidOn) : '-') + '</td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
            html += '</tr>';
        });
    }

    $('#historyTableBody').html(html);
}

function updateHistorySummary(res) {
    $('#historyTotalPayments').text(res.stats.totalPayments);
    $('#historyTotalAmount').text(formatCurrency(res.stats.totalAmountPaid));
    $('#historyTotalDeductions').text(formatCurrency(res.stats.totalDeductions));
    $('#historyMonthsProcessed').text(res.stats.monthsProcessed);
}

function exportHistoryToExcel() {
    var year = $('#historyYearSelect').val();

    if (historyData.length === 0) {
        alert('No data to export. Please load payment history first.');
        return;
    }

    var months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var csv = 'Month,Employee,Department,Gross Salary,Deductions,Net Salary,Amount Paid,Payment Mode,Transaction Ref,Paid On,Status\n';

    historyData.forEach(function(pay) {
        var monthYear = months[parseInt(pay.salaryMonth)] + ' ' + pay.salaryYear;
        csv += '"' + monthYear + '","' + pay.employeeName + '","' + (pay.department || '') + '",' +
               pay.grossSalary + ',' + pay.totalDeductions + ',' + pay.netSalary + ',' + pay.amountPaid + ',"' +
               (pay.paymentMode || '') + '","' + (pay.transactionRef || '') + '","' + (pay.paidOn || '') + '","' +
               (pay.slipStatus || 'pending') + '"\n';
    });

    var blob = new Blob([csv], { type: 'text/csv' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'Payment_History_' + year + '.csv';
    a.click();
}

// ========== Advances Functions ==========
function loadAdvances() {
    $('#advancesTableBody').html('<tr><td colspan="8" class="text-center" style="padding:40px;"><div class="loading"><div class="spinner"></div><p class="mt-4 text-muted">Loading advances...</p></div></td></tr>');

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'getSalaryAdvances'
    }, function(res) {
        if (res.err === 0) {
            advancesData = res.advances;
            renderAdvancesTable(res);
            updateAdvanceStats(res);
        } else {
            $('#advancesTableBody').html('<tr><td colspan="8" class="text-center" style="padding:40px;color:var(--red-500);">' + res.msg + '</td></tr>');
        }
    }, 'json').fail(function() {
        $('#advancesTableBody').html('<tr><td colspan="8" class="text-center" style="padding:40px;color:var(--red-500);">Failed to load advances</td></tr>');
    });
}

function renderAdvancesTable(res) {
    var html = '';

    if (res.advances.length === 0) {
        html = '<tr><td colspan="8" class="text-center" style="padding:40px;"><p class="text-muted">No salary advances found</p></td></tr>';
    } else {
        res.advances.forEach(function(adv) {
            var statusClass = adv.advanceStatus;
            var progressPct = parseFloat(adv.advanceAmount) > 0 ? Math.round((parseFloat(adv.totalDeducted || 0) / parseFloat(adv.advanceAmount)) * 100) : 0;

            // Format deduction info
            var deductionInfo = '';
            var freq = adv.deductionFrequency || 'monthly';
            if (freq === 'one_time') {
                deductionInfo = 'One-time';
            } else {
                var freqLabels = {
                    'monthly': '/month',
                    'quarterly': '/quarter',
                    'half_yearly': '/6 months',
                    'yearly': '/year',
                    'custom': '/custom'
                };
                deductionInfo = formatCurrency(adv.monthlyDeduction) + '<br><small style="color:var(--gray-500)">' + freqLabels[freq] + '</small>';
            }

            html += '<tr>';
            html += '<td><div class="emp-name">' + adv.employeeName + '</div>';
            if (adv.reason) {
                html += '<div style="font-size:11px;color:var(--gray-500)">' + adv.reason + '</div>';
            }
            html += '</td>';
            html += '<td>' + formatDate(adv.advanceDate) + '</td>';
            html += '<td class="amount">' + formatCurrency(adv.advanceAmount) + '</td>';
            html += '<td class="amount">' + deductionInfo + '</td>';
            html += '<td class="amount" style="color:var(--green-500)">' + formatCurrency(adv.totalDeducted || 0) + '</td>';
            html += '<td>';
            html += '<div class="amount" style="color:var(--red-500)">' + formatCurrency(adv.remainingAmount || 0) + '</div>';
            html += '<div class="progress-bar"><div class="progress-bar-fill" style="width:' + progressPct + '%"></div></div>';
            html += '</td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + statusClass.replace('_', ' ') + '</span></td>';
            html += '<td class="action-btns">';

            if (adv.advanceStatus === 'repaying' || adv.advanceStatus === 'approved') {
                html += '<button class="action-btn pay" onclick="openRepaymentModal(' + adv.advanceID + ')">Repay</button>';
            }
            if (adv.advanceStatus === 'pending') {
                html += '<button class="action-btn slip" onclick="approveAdvance(' + adv.advanceID + ')">Approve</button>';
            }
            if (adv.advanceStatus === 'cleared' || adv.advanceStatus === 'completed') {
                html += '<span style="color:var(--green-500);font-size:12px;">Cleared</span>';
            }

            html += '</td>';
            html += '</tr>';
        });
    }

    $('#advancesTableBody').html(html);
}

function updateAdvanceStats(res) {
    $('#activeAdvances').text(res.stats.activeCount || 0);
    $('#totalOutstanding').text(formatCurrency(res.stats.totalOutstanding || 0));
    $('#monthlyDeductions').text(formatCurrency(res.stats.monthlyDeductions || 0));
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    var d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function openAdvanceModal() {
    $('#advanceModalTitle').text('Give Salary Advance');
    $('#advanceEmployee').val('');
    $('#advanceAmount').val('');
    $('#advanceDate').val(new Date().toISOString().split('T')[0]);
    $('#advanceReason').val('');
    $('#deductionFrequency').val('monthly');
    $('#customMonths').val('2');
    $('#monthlyEMI').val('');
    $('#emiInfo').text('');
    $('#editAdvanceID').val('');
    toggleDeductionOptions();
    $('#advanceModal').addClass('active');
}

function closeAdvanceModal() {
    $('#advanceModal').removeClass('active');
}

function toggleDeductionOptions() {
    var freq = $('#deductionFrequency').val();

    if (freq === 'one_time') {
        $('#emiRow').hide();
        $('#customMonthsRow').hide();
    } else if (freq === 'custom') {
        $('#emiRow').show();
        $('#customMonthsRow').show();
    } else {
        $('#emiRow').show();
        $('#customMonthsRow').hide();
    }

    updateEmiInfo();
}

function getFrequencyLabel(freq) {
    var labels = {
        'one_time': 'One-time',
        'monthly': 'Monthly',
        'quarterly': 'Quarterly',
        'half_yearly': 'Half-Yearly',
        'yearly': 'Yearly',
        'custom': 'Custom'
    };
    return labels[freq] || freq;
}

function getFrequencyMonths(freq) {
    var months = {
        'one_time': 0,
        'monthly': 1,
        'quarterly': 3,
        'half_yearly': 6,
        'yearly': 12,
        'custom': parseInt($('#customMonths').val()) || 1
    };
    return months[freq] || 1;
}

// Calculate deduction info
function updateEmiInfo() {
    var amount = parseFloat($('#advanceAmount').val()) || 0;
    var emi = parseFloat($('#monthlyEMI').val()) || 0;
    var freq = $('#deductionFrequency').val();

    if (freq === 'one_time') {
        $('#emiInfo').text('Full amount will be deducted in next salary');
        return;
    }

    if (amount > 0 && emi > 0) {
        var installments = Math.ceil(amount / emi);
        var freqMonths = getFrequencyMonths(freq);
        var totalMonths = installments * freqMonths;

        var freqLabel = getFrequencyLabel(freq);
        if (freq === 'custom') {
            freqLabel = 'Every ' + freqMonths + ' month(s)';
        }

        $('#emiInfo').text(installments + ' installments (' + freqLabel + ') ≈ ' + totalMonths + ' months');
    } else {
        $('#emiInfo').text('');
    }
}

$('#advanceAmount, #monthlyEMI, #customMonths').on('input', updateEmiInfo);
$('#deductionFrequency').on('change', updateEmiInfo);

function saveAdvance() {
    var userID = $('#advanceEmployee').val();
    var amount = $('#advanceAmount').val();
    var advanceDate = $('#advanceDate').val();
    var reason = $('#advanceReason').val();
    var deductionFrequency = $('#deductionFrequency').val();
    var customMonths = $('#customMonths').val();
    var monthlyEMI = $('#monthlyEMI').val();
    var deductFromMonth = $('#deductFromMonth').val();
    var deductFromYear = $('#deductFromYear').val();
    var advanceID = $('#editAdvanceID').val();

    if (!userID) {
        alert('Please select an employee');
        return;
    }
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }
    if (deductionFrequency !== 'one_time' && (!monthlyEMI || monthlyEMI <= 0)) {
        alert('Please enter deduction amount per installment');
        return;
    }

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'saveSalaryAdvance',
        advanceID: advanceID,
        userID: userID,
        advanceAmount: amount,
        advanceDate: advanceDate,
        reason: reason,
        deductionFrequency: deductionFrequency,
        customMonths: deductionFrequency === 'custom' ? customMonths : getFrequencyMonths(deductionFrequency),
        monthlyDeduction: deductionFrequency === 'one_time' ? amount : monthlyEMI,
        deductFromMonth: deductFromMonth,
        deductFromYear: deductFromYear
    }, function(res) {
        if (res.err === 0) {
            closeAdvanceModal();
            loadAdvances();
            alert('Salary advance saved successfully');
        } else {
            alert(res.msg || 'Failed to save advance');
        }
    }, 'json');
}

function approveAdvance(advanceID) {
    if (!confirm('Approve this salary advance?')) return;

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'approveSalaryAdvance',
        advanceID: advanceID
    }, function(res) {
        if (res.err === 0) {
            loadAdvances();
        } else {
            alert(res.msg || 'Failed to approve');
        }
    }, 'json');
}

function openRepaymentModal(advanceID) {
    var adv = advancesData.find(function(a) { return a.advanceID == advanceID; });
    if (!adv) return;

    var balance = parseFloat(adv.remainingAmount || 0);

    $('#repayEmpName').val(adv.employeeName);
    $('#repayBalance').val(formatCurrency(balance));
    $('#repayBalanceRaw').val(balance);
    $('#repayType').val('installment');
    $('#repayAmount').val(adv.monthlyDeduction || balance);
    $('#repayMode').val('salary_deduction');
    $('#repayRemarks').val('');
    $('#repayAdvanceID').val(advanceID);
    toggleRepaymentType();
    $('#repaymentModal').addClass('active');
}

function toggleRepaymentType() {
    var repayType = $('#repayType').val();
    var balance = parseFloat($('#repayBalanceRaw').val()) || 0;

    if (repayType === 'full') {
        // Full & Final - set amount to balance and disable field
        $('#repayAmount').val(balance);
        $('#repayAmountRow').hide();
        $('#repayRemarks').attr('placeholder', 'Full & Final Settlement');
    } else if (repayType === 'installment') {
        // Regular installment - show field with default EMI
        var adv = advancesData.find(function(a) { return a.advanceID == $('#repayAdvanceID').val(); });
        $('#repayAmount').val(adv ? (adv.monthlyDeduction || balance) : balance);
        $('#repayAmountRow').show();
        $('#repayRemarks').attr('placeholder', 'Optional remarks');
    } else {
        // Partial payment - show field, clear amount
        $('#repayAmount').val('');
        $('#repayAmountRow').show();
        $('#repayRemarks').attr('placeholder', 'Optional remarks');
    }
}

function closeRepaymentModal() {
    $('#repaymentModal').removeClass('active');
}

function saveRepayment() {
    var advanceID = $('#repayAdvanceID').val();
    var repayType = $('#repayType').val();
    var amount = $('#repayAmount').val();
    var mode = $('#repayMode').val();
    var remarks = $('#repayRemarks').val();

    // For full settlement, use balance amount
    if (repayType === 'full') {
        amount = $('#repayBalanceRaw').val();
        if (!remarks) {
            remarks = 'Full & Final Settlement';
        }
    }

    if (!amount || amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }

    var confirmMsg = 'Record this repayment?';
    if (repayType === 'full') {
        confirmMsg = 'This will close the advance as FULLY PAID. Continue?';
    }

    if (!confirm(confirmMsg)) return;

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'recordAdvanceRepayment',
        advanceID: advanceID,
        repaymentAmount: amount,
        repaymentMode: mode,
        repaymentType: repayType,
        remarks: remarks
    }, function(res) {
        if (res.err === 0) {
            closeRepaymentModal();
            loadAdvances();
            var msg = repayType === 'full' ? 'Advance fully settled!' : 'Repayment recorded successfully';
            alert(msg);
        } else {
            alert(res.msg || 'Failed to record repayment');
        }
    }, 'json');
}

// Close modals on backdrop click
$('#advanceModal, #repaymentModal, #cmsModal, #cmsSettingsModal').on('click', function(e) {
    if (e.target === this) {
        $(this).removeClass('active');
    }
});

// ========== ICICI CMS File Generation ==========
var cmsFileContent = '';
var cmsFileName = '';

function openCMSModal() {
    // Check if there are any paid salaries
    var paidEmployees = salaryData.filter(function(e) {
        return e.slipStatus === 'paid' || e.slipStatus === 'slip_generated';
    });

    if (paidEmployees.length === 0) {
        alert('No paid salaries found. Please mark salaries as paid first before generating CMS file.');
        return;
    }

    $('#cmsModal').addClass('active');
    $('#cmsPaymentDate').val(formatDateDMY(new Date()));

    // Generate CMS preview
    generateCMSPreview();
}

function closeCMSModal() {
    $('#cmsModal').removeClass('active');
    cmsFileContent = '';
    cmsFileName = '';
}

function formatDateDMY(date) {
    var d = date.getDate().toString().padStart(2, '0');
    var m = (date.getMonth() + 1).toString().padStart(2, '0');
    var y = date.getFullYear();
    return d + '-' + m + '-' + y;
}

function generateCMSPreview() {
    var paymentDate = $('#cmsPaymentDate').val();

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'generateICICICMS',
        month: currentMonth,
        year: currentYear,
        paymentDate: paymentDate
    }, function(res) {
        if (res.err === 0) {
            cmsFileContent = res.content;
            cmsFileName = res.filename;

            // Update summary
            $('#cmsValidCount').text(res.summary.totalEmployees);
            $('#cmsInvalidCount').text(res.summary.invalidCount);
            $('#cmsTotalAmount').text(res.summary.formattedAmount);

            // Show invalid list if any
            if (res.summary.invalidCount > 0) {
                var invalidHtml = '';
                res.summary.invalid.forEach(function(inv) {
                    invalidHtml += '<li>' + inv.name + ' - ' + inv.reason + '</li>';
                });
                $('#cmsInvalidEmployees').html(invalidHtml);
                $('#cmsInvalidList').show();
            } else {
                $('#cmsInvalidList').hide();
            }

            // Show preview (first 5 lines)
            var lines = res.content.split('\n');
            var previewLines = lines.slice(0, Math.min(5, lines.length));
            $('#cmsPreviewContent').text(previewLines.join('\n'));
            $('#cmsPreviewBox').show();

            $('#downloadCMSBtn').prop('disabled', false);
        } else {
            alert(res.msg);
            $('#cmsValidCount').text('0');
            $('#cmsInvalidCount').text('0');
            $('#cmsTotalAmount').text('-');
            $('#downloadCMSBtn').prop('disabled', true);

            if (res.msg.indexOf('Bank settings') > -1) {
                closeCMSModal();
                openCMSSettingsModal();
            }
        }
    }, 'json').fail(function() {
        alert('Failed to generate CMS file');
    });
}

function toggleCMSPreview() {
    $('#cmsPreviewBox').toggle();
}

function downloadCMSFile() {
    if (!cmsFileContent) {
        alert('Please wait for the file to be generated');
        return;
    }

    // Re-generate with current payment date
    var paymentDate = $('#cmsPaymentDate').val();

    $('#downloadCMSBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'generateICICICMS',
        month: currentMonth,
        year: currentYear,
        paymentDate: paymentDate
    }, function(res) {
        $('#downloadCMSBtn').prop('disabled', false).html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:4px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Download CMS File');

        if (res.err === 0) {
            // Plain text file
            var blob = new Blob([res.content], { type: 'text/plain' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = res.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            closeCMSModal();
            alert('CMS file downloaded: ' + res.filename + '\n\nSummary:\n- Employees: ' + res.summary.totalEmployees + '\n- Total Amount: ' + res.summary.formattedAmount + '\n\nRemember to encrypt this file using ICICI AES Encryptor before uploading.');
        } else {
            alert(res.msg);
        }
    }, 'json');
}

// ========== ICICI Bank Settings ==========
function openCMSSettingsModal() {
    // Load current settings
    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'getICICIBankSettings'
    }, function(res) {
        if (res.err === 0) {
            $('#settingsCompanyCode').val(res.settings.companyCode || '');
            $('#settingsDebitAccount').val(res.settings.debitAccount || '');
            $('#settingsCompanyName').val(res.settings.companyName || 'BOMBAY ENGINEERING SYNDICATE');
        }
        $('#cmsSettingsModal').addClass('active');
    }, 'json').fail(function() {
        $('#cmsSettingsModal').addClass('active');
    });
}

function closeCMSSettingsModal() {
    $('#cmsSettingsModal').removeClass('active');
}

function saveCMSSettings() {
    var companyCode = $('#settingsCompanyCode').val().trim();
    var debitAccount = $('#settingsDebitAccount').val().trim();
    var companyName = $('#settingsCompanyName').val().trim();

    if (!companyCode) {
        alert('Please enter Company Code');
        $('#settingsCompanyCode').focus();
        return;
    }
    if (!debitAccount) {
        alert('Please enter Debit Account Number');
        $('#settingsDebitAccount').focus();
        return;
    }

    $.post(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php', {
        xAction: 'saveICICIBankSettings',
        companyCode: companyCode,
        debitAccount: debitAccount,
        companyName: companyName
    }, function(res) {
        if (res.err === 0) {
            alert('Bank settings saved successfully');
            closeCMSSettingsModal();
        } else {
            alert(res.msg || 'Failed to save settings');
        }
    }, 'json');
}
</script>
