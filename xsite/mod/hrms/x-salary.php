<?php
requireHRMSLogin();

$userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
?>

<style>
/* Page Header */
.page-header {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    margin-bottom: var(--space-xl);
}

.page-header-icon {
    width: 48px;
    height: 48px;
    background: var(--accent-light);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-header-icon svg {
    width: 24px;
    height: 24px;
    color: var(--accent);
}

/* Salary Cards Grid */
.salary-grid {
    display: grid;
    gap: var(--space-lg);
}

@media (min-width: 768px) {
    .salary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .salary-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Salary Card */
.salary-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s ease;
}

.salary-card:hover {
    border-color: var(--accent);
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.salary-card-header {
    background: linear-gradient(135deg, var(--bg-elevated), var(--bg-secondary));
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.salary-month {
    display: flex;
    flex-direction: column;
}

.salary-month-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
}

.salary-year {
    font-size: 13px;
    color: var(--text-muted);
}

.salary-status {
    padding: 6px 12px;
    font-size: 10px;
    font-weight: 700;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.salary-status.paid {
    background: var(--success-bg);
    color: var(--success);
}

.salary-status.slip_generated,
.salary-status.emailed {
    background: var(--info-bg);
    color: var(--info);
}

.salary-status.pending {
    background: var(--warning-bg);
    color: var(--warning);
}

.salary-card-body {
    padding: var(--space-lg);
}

.salary-amount-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-lg);
}

.salary-amount-label {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.salary-amount-value {
    font-size: 28px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--success);
}

.salary-breakdown {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding-top: var(--space-md);
    border-top: 1px solid var(--border);
}

.breakdown-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.breakdown-label {
    font-size: 10px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.breakdown-value {
    font-size: 14px;
    font-weight: 600;
    font-family: var(--font-mono);
    color: var(--text-primary);
}

.breakdown-value.deduction {
    color: var(--error);
}

.salary-card-footer {
    padding: var(--space-md) var(--space-lg);
    background: var(--bg-secondary);
    display: flex;
    gap: 10px;
}

.salary-btn {
    flex: 1;
    padding: 10px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
}

.salary-btn svg {
    width: 14px;
    height: 14px;
}

.salary-btn-primary {
    background: var(--accent);
    color: var(--bg-primary);
}

.salary-btn-primary:hover {
    background: var(--accent-muted);
}

.salary-btn-secondary {
    background: var(--bg-elevated);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.salary-btn-secondary:hover {
    border-color: var(--accent);
}

/* Slip Details Modal */
.slip-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: none;
    align-items: flex-start;
    justify-content: center;
    padding: var(--space-lg);
    overflow-y: auto;
}

.slip-modal.show {
    display: flex;
}

.slip-modal-content {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 600px;
    margin: var(--space-xl) 0;
    animation: modalSlide 0.3s ease;
}

.slip-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    background: var(--bg-card);
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
}

.slip-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
}

.slip-modal-close {
    width: 36px;
    height: 36px;
    background: var(--bg-elevated);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    transition: all 0.2s ease;
}

.slip-modal-close:hover {
    background: var(--error-bg);
    color: var(--error);
}

.slip-modal-body {
    padding: var(--space-xl);
}

/* Slip Details */
.slip-header {
    text-align: center;
    padding-bottom: var(--space-lg);
    border-bottom: 2px dashed var(--border);
    margin-bottom: var(--space-lg);
}

.slip-company {
    font-size: 18px;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 4px;
}

.slip-title {
    font-size: 14px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 2px;
}

.slip-section {
    margin-bottom: var(--space-lg);
}

.slip-section-title {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
}

.slip-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.slip-row-label {
    font-size: 14px;
    color: var(--text-secondary);
}

.slip-row-value {
    font-size: 14px;
    font-weight: 600;
    font-family: var(--font-mono);
    color: var(--text-primary);
}

.slip-row-value.credit {
    color: var(--success);
}

.slip-row-value.debit {
    color: var(--error);
}

.slip-total {
    display: flex;
    justify-content: space-between;
    padding: var(--space-md);
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    margin-top: var(--space-md);
}

.slip-total-label {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary);
}

.slip-total-value {
    font-size: 20px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--success);
}

.slip-footer {
    margin-top: var(--space-xl);
    padding-top: var(--space-lg);
    border-top: 2px dashed var(--border);
    text-align: center;
}

.slip-footer-text {
    font-size: 11px;
    color: var(--text-muted);
    line-height: 1.6;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: var(--space-2xl);
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: var(--bg-elevated);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--space-lg);
}

.empty-icon svg {
    width: 36px;
    height: 36px;
    color: var(--text-muted);
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.empty-text {
    font-size: 14px;
    color: var(--text-muted);
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
    </div>
    <div>
        <h1 class="hrms-page-title">Salary Slips</h1>
        <p class="hrms-page-subtitle">View and download your salary slips</p>
    </div>
</div>

<!-- Loading State -->
<div id="salaryLoading" style="text-align: center; padding: 60px;">
    <div style="display: inline-flex; gap: 6px;">
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite;"></div>
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite; animation-delay: 0.2s;"></div>
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite; animation-delay: 0.4s;"></div>
    </div>
    <p style="color: var(--text-muted); margin-top: 16px; font-size: 14px;">Loading salary slips...</p>
</div>

<!-- Salary Content -->
<div id="salaryContent" style="display: none;">
    <div class="salary-grid" id="salaryGrid">
        <!-- Populated by JS -->
    </div>
</div>

<!-- Slip Details Modal -->
<div class="slip-modal" id="slipModal">
    <div class="slip-modal-content">
        <div class="slip-modal-header">
            <div class="slip-modal-title" id="slipModalTitle">Salary Slip</div>
            <button class="slip-modal-close" onclick="closeSlipModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="slip-modal-body" id="slipModalBody">
            <!-- Populated by JS -->
        </div>
    </div>
</div>

<script>
var salarySlips = [];
var months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

$(document).ready(function() {
    loadSalarySlips();
});

function loadSalarySlips() {
    $('#salaryLoading').show();
    $('#salaryContent').hide();

    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getSalarySlips' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                salarySlips = res.data;
                renderSalarySlips(res.data);
            } else {
                alert(res.msg);
            }
        },
        error: function() {
            alert('Failed to load salary slips. Please try again.');
        },
        complete: function() {
            $('#salaryLoading').hide();
            $('#salaryContent').show();
        }
    });
}

function renderSalarySlips(data) {
    if (data.length === 0) {
        $('#salaryGrid').html(`
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
                <div class="empty-title">No salary slips yet</div>
                <div class="empty-text">Your salary slips will appear here once they are generated.</div>
            </div>
        `);
        return;
    }

    var html = '';
    data.forEach(function(slip, index) {
        var monthName = months[parseInt(slip.salaryMonth)] || slip.salaryMonth;
        var netSalary = parseFloat(slip.netSalary || 0);
        var totalEarnings = parseFloat(slip.totalEarnings || 0);
        var totalDeductions = parseFloat(slip.totalDeductions || 0);
        var status = slip.slipStatus || 'pending';
        var statusLabel = status.replace('_', ' ').charAt(0).toUpperCase() + status.replace('_', ' ').slice(1);

        html += `
            <div class="salary-card">
                <div class="salary-card-header">
                    <div class="salary-month">
                        <span class="salary-month-name">${monthName}</span>
                        <span class="salary-year">${slip.salaryYear}</span>
                    </div>
                    <span class="salary-status ${status}">${statusLabel}</span>
                </div>
                <div class="salary-card-body">
                    <div class="salary-amount-row">
                        <span class="salary-amount-label">Net Salary</span>
                        <span class="salary-amount-value">₹${netSalary.toLocaleString('en-IN')}</span>
                    </div>
                    <div class="salary-breakdown">
                        <div class="breakdown-item">
                            <span class="breakdown-label">Earnings</span>
                            <span class="breakdown-value">₹${totalEarnings.toLocaleString('en-IN')}</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Deductions</span>
                            <span class="breakdown-value deduction">-₹${totalDeductions.toLocaleString('en-IN')}</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Present Days</span>
                            <span class="breakdown-value">${slip.presentDays || '--'}</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Working Days</span>
                            <span class="breakdown-value">${slip.workingDays || '--'}</span>
                        </div>
                    </div>
                </div>
                <div class="salary-card-footer">
                    <button class="salary-btn salary-btn-secondary" onclick="viewSlipDetails(${index})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View
                    </button>
                    <button class="salary-btn salary-btn-primary" onclick="downloadSlipFromCard(${index})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Download
                    </button>
                </div>
            </div>
        `;
    });

    $('#salaryGrid').html(html);
}

function viewSlipDetails(index) {
    var slip = salarySlips[index];
    var monthName = months[parseInt(slip.salaryMonth)] || slip.salaryMonth;

    $('#slipModalTitle').text(monthName + ' ' + slip.salaryYear + ' Salary Slip');

    var html = `
        <div class="slip-header">
            <div class="slip-company">Bombay Engineering Syndicate</div>
            <div class="slip-title">Salary Slip</div>
        </div>

        <div class="slip-section">
            <div class="slip-section-title">Pay Period</div>
            <div class="slip-row">
                <span class="slip-row-label">Month</span>
                <span class="slip-row-value">${monthName} ${slip.salaryYear}</span>
            </div>
            <div class="slip-row">
                <span class="slip-row-label">Working Days</span>
                <span class="slip-row-value">${slip.workingDays || '--'}</span>
            </div>
            <div class="slip-row">
                <span class="slip-row-label">Days Present</span>
                <span class="slip-row-value">${slip.presentDays || '--'}</span>
            </div>
            <div class="slip-row">
                <span class="slip-row-label">Days Absent</span>
                <span class="slip-row-value">${slip.absentDays || '0'}</span>
            </div>
            <div class="slip-row">
                <span class="slip-row-label">Leave Taken</span>
                <span class="slip-row-value">${slip.leavesTaken || '0'}</span>
            </div>
        </div>

        <div class="slip-section">
            <div class="slip-section-title">Earnings</div>
            <div class="slip-row">
                <span class="slip-row-label">Basic Salary</span>
                <span class="slip-row-value credit">₹${parseFloat(slip.basicSalary || 0).toLocaleString('en-IN')}</span>
            </div>
            ${parseFloat(slip.hra || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">HRA</span>
                <span class="slip-row-value credit">₹${parseFloat(slip.hra).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            ${parseFloat(slip.conveyanceAllowance || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">Conveyance Allowance</span>
                <span class="slip-row-value credit">₹${parseFloat(slip.conveyanceAllowance).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            ${parseFloat(slip.medicalAllowance || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">Medical Allowance</span>
                <span class="slip-row-value credit">₹${parseFloat(slip.medicalAllowance).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            ${parseFloat(slip.specialAllowance || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">Special Allowance</span>
                <span class="slip-row-value credit">₹${parseFloat(slip.specialAllowance).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            ${parseFloat(slip.otherAllowance || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">Other Allowance</span>
                <span class="slip-row-value credit">₹${parseFloat(slip.otherAllowance).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            <div class="slip-total">
                <span class="slip-total-label">Total Earnings</span>
                <span class="slip-total-value" style="color: var(--success);">₹${parseFloat(slip.totalEarnings || 0).toLocaleString('en-IN')}</span>
            </div>
        </div>

        <div class="slip-section">
            <div class="slip-section-title">Deductions</div>
            ${parseFloat(slip.leaveDeductionAmount || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">Leave Deduction (${slip.leavesDeducted || 0} days)</span>
                <span class="slip-row-value debit">-₹${parseFloat(slip.leaveDeductionAmount).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            ${parseFloat(slip.advanceDeduction || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">Advance Deduction</span>
                <span class="slip-row-value debit">-₹${parseFloat(slip.advanceDeduction).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            ${parseFloat(slip.otherDeduction || 0) > 0 ? `
            <div class="slip-row">
                <span class="slip-row-label">Other Deductions</span>
                <span class="slip-row-value debit">-₹${parseFloat(slip.otherDeduction).toLocaleString('en-IN')}</span>
            </div>
            ` : ''}
            <div class="slip-total">
                <span class="slip-total-label">Total Deductions</span>
                <span class="slip-total-value" style="color: var(--error);">-₹${parseFloat(slip.totalDeductions || 0).toLocaleString('en-IN')}</span>
            </div>
        </div>

        <div class="slip-section">
            <div class="slip-total" style="background: var(--accent-light);">
                <span class="slip-total-label" style="font-size: 16px;">Net Salary</span>
                <span class="slip-total-value" style="font-size: 24px;">₹${parseFloat(slip.netSalary || 0).toLocaleString('en-IN')}</span>
            </div>
        </div>

        ${slip.paidOn ? `
        <div class="slip-section">
            <div class="slip-section-title">Payment Details</div>
            <div class="slip-row">
                <span class="slip-row-label">Paid On</span>
                <span class="slip-row-value">${slip.paidOn}</span>
            </div>
            <div class="slip-row">
                <span class="slip-row-label">Payment Mode</span>
                <span class="slip-row-value">${slip.paymentMode || '--'}</span>
            </div>
            ${slip.transactionRef ? `
            <div class="slip-row">
                <span class="slip-row-label">Transaction Ref</span>
                <span class="slip-row-value">${slip.transactionRef}</span>
            </div>
            ` : ''}
        </div>
        ` : ''}

        <div class="slip-footer">
            <p class="slip-footer-text">
                This is a computer-generated salary slip and does not require a signature.<br>
                For any discrepancies, please contact HR.
            </p>
        </div>

        <div class="slip-download-section" style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
            <button type="button" class="slip-download-btn" onclick="downloadSlipPDF(${index})" style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: linear-gradient(135deg, #1565C0 0%, #1976D2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3);
            ">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Download PDF
            </button>
        </div>
    `;

    $('#slipModalBody').html(html);
    $('#slipModal').addClass('show');
}

function downloadSlipPDF(index) {
    var slip = salarySlips[index];
    var btn = $('.slip-download-btn');
    var originalText = btn.html();

    btn.html('<span style="display: inline-flex; align-items: center; gap: 8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> Generating...</span>');
    btn.prop('disabled', true);

    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: {
            xAction: 'downloadSalarySlip',
            slipID: slip.slipID
        },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0 && res.downloadUrl) {
                // Open PDF in new tab
                window.open(res.downloadUrl, '_blank');
                btn.html('<span style="display: inline-flex; align-items: center; gap: 8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Downloaded!</span>');
                setTimeout(function() {
                    btn.html(originalText);
                    btn.prop('disabled', false);
                }, 2000);
            } else {
                alert(res.msg || 'Failed to generate PDF');
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        },
        error: function() {
            alert('Failed to generate PDF. Please try again.');
            btn.html(originalText);
            btn.prop('disabled', false);
        }
    });
}

function downloadSlipFromCard(index) {
    var slip = salarySlips[index];
    var btn = $('button[onclick="downloadSlipFromCard(' + index + ')"]');
    var originalHtml = btn.html();

    btn.html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle></svg>').prop('disabled', true);

    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: {
            xAction: 'downloadSalarySlip',
            slipID: slip.slipID
        },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0 && res.downloadUrl) {
                window.open(res.downloadUrl, '_blank');
            } else {
                alert(res.msg || 'Failed to generate PDF');
            }
        },
        error: function() {
            alert('Failed to generate PDF. Please try again.');
        },
        complete: function() {
            btn.html(originalHtml).prop('disabled', false);
        }
    });
}

function closeSlipModal() {
    $('#slipModal').removeClass('show');
}

// Close modal on outside click
$('#slipModal').on('click', function(e) {
    if (e.target === this) {
        closeSlipModal();
    }
});

// ESC key to close
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSlipModal();
    }
});
</script>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.slip-download-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
}
.slip-download-btn:active {
    transform: translateY(0);
}
.slip-download-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>
