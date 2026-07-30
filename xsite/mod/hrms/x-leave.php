<?php
requireHRMSLogin();

$userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
$userID = $_SESSION['HRMS_USER_ID'] ?? 0;
$isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
$isHRAdmin = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;
$canApplyForOthers = $isManager || $isHRAdmin;
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

/* Leave Balance Cards */
.balance-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-md);
    margin-bottom: var(--space-xl);
}

@media (min-width: 640px) {
    .balance-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 900px) {
    .balance-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

.balance-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    text-align: center;
}

.balance-card.total {
    background: linear-gradient(135deg, var(--accent), var(--accent-muted));
    border: none;
    color: white;
}

.balance-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--space-sm);
}

.balance-icon svg {
    width: 20px;
    height: 20px;
}

.balance-icon.casual { background: var(--info-bg); color: var(--info); }
.balance-icon.sick { background: var(--error-bg); color: var(--error); }
.balance-icon.earned { background: var(--success-bg); color: var(--success); }
.balance-icon.compoff { background: #fef3c7; color: #d97706; }
.balance-icon.total { background: rgba(255,255,255,0.2); color: white; }

/* Enhanced Balance Section */
.fy-badge {
    display: inline-block;
    padding: 4px 12px;
    background: var(--accent-light);
    color: var(--accent);
    font-size: 11px;
    font-weight: 700;
    border-radius: 100px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: auto;
}

.balance-detail-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-xl);
    overflow: hidden;
}

.balance-detail-header {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border);
}

.balance-detail-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.balance-table {
    width: 100%;
    border-collapse: collapse;
}

.balance-table th {
    background: var(--bg-secondary);
    padding: 12px 16px;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.balance-table td {
    padding: 14px 16px;
    font-size: 14px;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-light);
}

.balance-table tr:last-child td {
    border-bottom: none;
}

.balance-table tbody tr:hover {
    background: var(--bg-secondary);
}

.leave-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.leave-type-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.leave-type-dot.cl { background: #3498db; }
.leave-type-dot.sl { background: #e74c3c; }
.leave-type-dot.el { background: #27ae60; }
.leave-type-dot.compoff { background: #f39c12; }
.leave-type-dot.ml { background: #e74c3c; }
.leave-type-dot.other { background: #9b59b6; }

.balance-num {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.balance-num.available {
    color: var(--success);
}

.balance-num.available.negative {
    color: var(--error);
}

.balance-num.used {
    color: var(--error);
}

.balance-num.lapsed {
    color: var(--text-muted);
    text-decoration: line-through;
}

/* Negative balance styling */
.balance-value.negative {
    color: var(--error) !important;
}

.balance-card.total.negative {
    background: linear-gradient(135deg, var(--error), #b91c1c);
}

.negative-indicator {
    font-size: 10px;
    color: var(--error);
    display: block;
    margin-top: 2px;
}

/* Balance Warning Modal */
.balance-warning-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.balance-warning-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.balance-warning-content {
    position: relative;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    max-width: 420px;
    width: 100%;
    padding: var(--space-xl);
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.balance-warning-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--space-lg);
}

.balance-warning-icon svg {
    width: 32px;
    height: 32px;
    color: #d97706;
}

.balance-warning-title {
    font-size: 18px;
    font-weight: 800;
    color: #d97706;
    margin: 0 0 var(--space-sm);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.balance-warning-message {
    font-size: 14px;
    color: var(--text-secondary);
    margin: 0 0 var(--space-lg);
    line-height: 1.5;
}

.balance-warning-details {
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--space-md);
    margin-bottom: var(--space-lg);
    text-align: left;
}

.balance-warning-detail {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-light);
}

.balance-warning-detail:last-child {
    border-bottom: none;
}

.detail-label {
    font-size: 13px;
    color: var(--text-muted);
}

.detail-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
}

.detail-value.negative {
    color: var(--error);
}

.balance-warning-note {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0 0 var(--space-lg);
    font-style: italic;
}

.balance-warning-actions {
    display: flex;
    gap: var(--space-md);
}

.btn-warning-cancel,
.btn-warning-proceed {
    flex: 1;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.btn-warning-cancel {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border: 1px solid var(--border);
}

.btn-warning-cancel:hover {
    background: var(--bg-elevated);
}

.btn-warning-proceed {
    background: #d97706;
    color: white;
}

.btn-warning-proceed:hover {
    background: #b45309;
}

/* Comp-Off Section */
.compoff-section {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.03));
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    margin-bottom: var(--space-xl);
}

.compoff-header {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-bottom: var(--space-md);
}

.compoff-header svg {
    width: 20px;
    height: 20px;
    color: #d97706;
}

.compoff-title {
    font-size: 14px;
    font-weight: 700;
    color: #d97706;
}

.compoff-balance-row {
    display: flex;
    gap: var(--space-lg);
    flex-wrap: wrap;
}

.compoff-stat {
    background: var(--bg-card);
    border-radius: var(--radius-md);
    padding: var(--space-md);
    min-width: 100px;
    text-align: center;
}

.compoff-stat-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--text-primary);
}

.compoff-stat-label {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Encashment Section */
.encashment-section {
    background: linear-gradient(135deg, rgba(39, 174, 96, 0.08), rgba(39, 174, 96, 0.03));
    border: 1px solid rgba(39, 174, 96, 0.2);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    margin-bottom: var(--space-xl);
}

.encashment-header {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-bottom: var(--space-md);
}

.encashment-header svg {
    width: 20px;
    height: 20px;
    color: #27ae60;
}

.encashment-title {
    font-size: 14px;
    font-weight: 700;
    color: #27ae60;
}

.encashment-info {
    background: var(--bg-card);
    border-radius: var(--radius-md);
    padding: var(--space-md);
}

.encashment-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-light);
}

.encashment-row:last-child {
    border-bottom: none;
}

.encashment-label {
    font-size: 13px;
    color: var(--text-secondary);
}

.encashment-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

@media (max-width: 640px) {
    .balance-table {
        display: block;
        overflow-x: auto;
    }

    .balance-table th, .balance-table td {
        white-space: nowrap;
    }
}

.balance-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 4px;
}

.balance-card.total .balance-value {
    color: white;
}

.balance-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.balance-card.total .balance-label {
    color: rgba(255,255,255,0.8);
}

/* Tabs */
.tabs {
    display: flex;
    gap: var(--space-xs);
    margin-bottom: var(--space-lg);
    background: var(--bg-elevated);
    padding: 4px;
    border-radius: var(--radius-md);
}

.tab-btn {
    flex: 1;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    background: transparent;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.2s ease;
}

.tab-btn:hover {
    color: var(--text-primary);
}

.tab-btn.active {
    background: var(--bg-card);
    color: var(--accent);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Apply Leave Form */
.leave-form-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.leave-form-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.leave-form-header svg {
    width: 24px;
    height: 24px;
    color: var(--accent);
}

.leave-form-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
}

.leave-form-body {
    padding: var(--space-xl);
}

.form-row {
    display: grid;
    gap: var(--space-lg);
    margin-bottom: var(--space-lg);
}

@media (min-width: 640px) {
    .form-row.two-col {
        grid-template-columns: 1fr 1fr;
    }
}

.form-group {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.form-label.required::after {
    content: '*';
    color: var(--error);
    margin-left: 4px;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 12px 14px;
    font-size: 14px;
    font-family: inherit;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--bg-card);
    color: var(--text-primary);
    outline: none;
    transition: all 0.2s ease;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-light);
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

/* Apply For Section (Manager Only) */
.apply-for-section {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08), rgba(124, 58, 237, 0.03));
    border: 1px solid rgba(124, 58, 237, 0.2);
    border-radius: var(--radius-md);
    padding: var(--space-lg);
    margin-bottom: var(--space-lg);
}

.apply-for-header {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-bottom: var(--space-md);
}

.apply-for-header svg {
    width: 20px;
    height: 20px;
    color: #7c3aed;
}

.apply-for-title {
    font-size: 14px;
    font-weight: 600;
    color: #7c3aed;
}

.apply-for-badge {
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

.apply-for-toggle {
    display: flex;
    gap: var(--space-sm);
    margin-bottom: var(--space-md);
}

.apply-for-btn {
    flex: 1;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--bg-card);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.apply-for-btn svg {
    width: 18px;
    height: 18px;
}

.apply-for-btn:hover {
    border-color: #7c3aed;
}

.apply-for-btn.active {
    background: #7c3aed;
    border-color: #7c3aed;
    color: white;
}

.team-member-select {
    display: none;
}

.team-member-select.show {
    display: block;
}

.team-member-select .form-select {
    border-color: rgba(124, 58, 237, 0.3);
}

.team-member-select .form-select:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
}

.selected-employee-info {
    display: none;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: var(--space-md);
    margin-top: var(--space-md);
}

.selected-employee-info.show {
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.selected-employee-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: transparent;
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
}

.selected-employee-details h4 {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 2px 0;
}

.selected-employee-details p {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
}

/* Day Type Toggle */
.day-type-group {
    display: flex;
    gap: var(--space-sm);
}

.day-type-btn {
    flex: 1;
    padding: 10px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-card);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.day-type-btn:hover {
    border-color: var(--accent);
}

.day-type-btn.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
}

/* Submit Button */
.leave-form-footer {
    padding: var(--space-lg);
    background: var(--bg-secondary);
    display: flex;
    justify-content: flex-end;
    gap: var(--space-md);
}

.btn-submit {
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-submit svg {
    width: 18px;
    height: 18px;
}

.btn-submit.primary {
    background: var(--accent);
    color: white;
}

.btn-submit.primary:hover {
    background: var(--accent-hover);
}

.btn-submit.primary:disabled {
    background: var(--text-muted);
    cursor: not-allowed;
}

.btn-submit.secondary {
    background: var(--bg-card);
    color: var(--text-secondary);
    border: 1px solid var(--border);
}

/* Leave History */
.leave-history-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.leave-history-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.leave-history-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
}

.leave-list {
    max-height: 500px;
    overflow-y: auto;
}

.leave-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-md);
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border-light);
    transition: background 0.2s ease;
}

.leave-item:last-child {
    border-bottom: none;
}

.leave-item:hover {
    background: var(--bg-secondary);
}

.leave-type-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 700;
}

.leave-type-icon.casual { background: var(--info-bg); color: var(--info); }
.leave-type-icon.sick { background: var(--error-bg); color: var(--error); }
.leave-type-icon.earned { background: var(--success-bg); color: var(--success); }
.leave-type-icon.emergency { background: var(--warning-bg); color: var(--warning); }
.leave-type-icon.other { background: var(--bg-elevated); color: var(--text-muted); }

.leave-info {
    flex: 1;
    min-width: 0;
}

.leave-dates {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.leave-type-name {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.leave-reason {
    font-size: 12px;
    color: var(--text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.leave-meta {
    text-align: right;
    flex-shrink: 0;
}

.leave-days {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.leave-status {
    display: inline-block;
    padding: 4px 10px;
    font-size: 10px;
    font-weight: 700;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.leave-status.pending { background: var(--warning-bg); color: var(--warning); }
.leave-status.approved { background: var(--success-bg); color: var(--success); }
.leave-status.disapproved, .leave-status.rejected { background: var(--error-bg); color: var(--error); }
.leave-status.cancel, .leave-status.cancelled { background: var(--bg-elevated); color: var(--text-muted); }

.leave-actions {
    margin-top: var(--space-sm);
}

.btn-cancel-leave {
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    color: var(--error);
    background: var(--error-bg);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel-leave:hover {
    background: var(--error);
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: var(--space-2xl);
}

.empty-icon {
    width: 64px;
    height: 64px;
    background: var(--bg-elevated);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--space-lg);
}

.empty-icon svg {
    width: 28px;
    height: 28px;
    color: var(--text-muted);
}

.empty-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.empty-text {
    font-size: 13px;
    color: var(--text-muted);
}

/* Date Range Display */
.date-range-preview {
    margin-top: var(--space-md);
    padding: var(--space-md);
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    font-size: 13px;
    color: var(--text-secondary);
}

.date-range-preview strong {
    color: var(--text-primary);
}

/* Toast Notification */
.toast {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--text-primary);
    color: white;
    padding: 12px 24px;
    border-radius: var(--radius-md);
    font-size: 14px;
    font-weight: 500;
    z-index: 1001;
    opacity: 0;
    transition: all 0.3s ease;
}

.toast.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}

.toast.success { background: var(--success); }
.toast.error { background: var(--error); }

@media (min-width: 768px) {
    .toast {
        bottom: 40px;
    }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
    </div>
    <div>
        <h1 class="hrms-page-title">Leave Management</h1>
        <p class="hrms-page-subtitle">Apply for leave and view your leave history</p>
    </div>
</div>

<!-- Leave Balance Cards (Quick Overview) -->
<div class="balance-grid" id="balanceGrid">
    <div class="balance-card">
        <div class="balance-icon casual">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div class="balance-value" id="casualBalance">--</div>
        <div class="balance-label">Casual Leave</div>
    </div>
    <div class="balance-card">
        <div class="balance-icon sick">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="balance-value" id="sickBalance">--</div>
        <div class="balance-label">Sick Leave</div>
    </div>
    <div class="balance-card">
        <div class="balance-icon earned">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </div>
        <div class="balance-value" id="earnedBalance">--</div>
        <div class="balance-label">Earned Leave</div>
    </div>
    <div class="balance-card">
        <div class="balance-icon compoff">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="balance-value" id="compoffBalance">--</div>
        <div class="balance-label">Comp-Off</div>
    </div>
    <div class="balance-card total">
        <div class="balance-icon total">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="balance-value" id="totalBalance">--</div>
        <div class="balance-label">Total Available</div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" data-tab="apply">Apply Leave</button>
    <button class="tab-btn" data-tab="balance">Balance Details</button>
    <button class="tab-btn" data-tab="history">Leave History</button>
</div>

<!-- Apply Leave Tab -->
<div class="tab-content active" id="tab-apply">
    <div class="leave-form-card">
        <div class="leave-form-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="12" y1="18" x2="12" y2="12"></line>
                <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
            <span class="leave-form-title">Apply for Leave</span>
        </div>
        <form id="leaveForm">
            <div class="leave-form-body">
                <?php if ($canApplyForOthers): ?>
                <!-- Apply For Section (Manager/HR Admin Only) -->
                <div class="apply-for-section">
                    <div class="apply-for-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                        <span class="apply-for-title">Apply Leave For</span>
                        <span class="apply-for-badge"><?php echo $isHRAdmin ? 'HR Admin' : 'Manager'; ?></span>
                    </div>
                    <div class="apply-for-toggle">
                        <button type="button" class="apply-for-btn active" data-for="self">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Myself
                        </button>
                        <button type="button" class="apply-for-btn" data-for="team">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                            </svg>
                            Team Member
                        </button>
                    </div>
                    <div class="team-member-select" id="teamMemberSelect">
                        <select class="form-select" id="applyForUserID" name="applyForUserID">
                            <option value="">Select team member...</option>
                        </select>
                        <div class="selected-employee-info" id="selectedEmployeeInfo">
                            <div class="selected-employee-avatar" id="selectedEmployeeAvatar">--</div>
                            <div class="selected-employee-details">
                                <h4 id="selectedEmployeeName">-</h4>
                                <p id="selectedEmployeeDesignation">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-row two-col">
                    <div class="form-group">
                        <label class="form-label required">Leave Type</label>
                        <select class="form-select" id="leaveType" name="leaveType" required>
                            <option value="">Select leave type</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Day Type</label>
                        <div class="day-type-group">
                            <button type="button" class="day-type-btn active" data-type="1">Full Day</button>
                            <button type="button" class="day-type-btn" data-type="2">First Half</button>
                            <button type="button" class="day-type-btn" data-type="3">Second Half</button>
                        </div>
                        <input type="hidden" id="dayType" name="dayType" value="1">
                    </div>
                </div>

                <div class="form-row two-col">
                    <div class="form-group">
                        <label class="form-label required">From Date</label>
                        <input type="date" class="form-input" id="fromDate" name="fromDate" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">To Date</label>
                        <input type="date" class="form-input" id="toDate" name="toDate" required>
                    </div>
                </div>

                <div id="dateRangePreview" class="date-range-preview" style="display: none;">
                    Total: <strong id="totalDays">0</strong> day(s)
                </div>

                <div class="form-row" style="margin-top: var(--space-lg);">
                    <div class="form-group">
                        <label class="form-label required">Reason</label>
                        <textarea class="form-textarea" id="reason" name="reason" placeholder="Please provide a reason for your leave request..." required></textarea>
                    </div>
                </div>
            </div>
            <div class="leave-form-footer">
                <button type="button" class="btn-submit secondary" onclick="resetForm()">Clear</button>
                <button type="submit" class="btn-submit primary" id="submitBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Balance Details Tab -->
<div class="tab-content" id="tab-balance">
    <!-- Detailed Leave Balance Table -->
    <div class="balance-detail-card">
        <div class="balance-detail-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h3>Leave Balance Summary</h3>
            <span class="fy-badge" id="currentFYBadge">FY --</span>
        </div>
        <table class="balance-table">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Opening</th>
                    <th>Credited</th>
                    <th>Used</th>
                    <th>Encashed</th>
                    <th>Available</th>
                </tr>
            </thead>
            <tbody id="leaveBalanceTableBody">
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Comp-Off Balance Section -->
    <div class="compoff-section" id="compoffSection" style="display: none;">
        <div class="compoff-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="compoff-title">Compensatory Off Balance</span>
        </div>
        <div class="compoff-balance-row" id="compoffBalanceRow">
            <div class="compoff-stat">
                <div class="compoff-stat-value" id="compoffAvailable">0</div>
                <div class="compoff-stat-label">Available</div>
            </div>
            <div class="compoff-stat">
                <div class="compoff-stat-value" id="compoffPending">0</div>
                <div class="compoff-stat-label">Pending</div>
            </div>
            <div class="compoff-stat">
                <div class="compoff-stat-value" id="compoffUsed">0</div>
                <div class="compoff-stat-label">Used</div>
            </div>
            <div class="compoff-stat">
                <div class="compoff-stat-value" id="compoffExpired">0</div>
                <div class="compoff-stat-label">Expired</div>
            </div>
        </div>
    </div>

    <!-- Encashment Eligibility Section -->
    <div class="encashment-section" id="encashmentSection" style="display: none;">
        <div class="encashment-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 3h12M6 8h12M6 8l8 13M6 13h8"/>
            </svg>
            <span class="encashment-title">Leave Encashment Eligibility (Earned Leave)</span>
        </div>
        <div class="encashment-info">
            <div class="encashment-row">
                <span class="encashment-label">Current EL Balance</span>
                <span class="encashment-value" id="encashELBalance">--</span>
            </div>
            <div class="encashment-row">
                <span class="encashment-label">Max Can Carry Forward</span>
                <span class="encashment-value" id="encashMaxCarry">30 days</span>
            </div>
            <div class="encashment-row">
                <span class="encashment-label">Eligible for Encashment</span>
                <span class="encashment-value" id="encashEligible">--</span>
            </div>
            <div class="encashment-row" id="encashAmountRow" style="display: none;">
                <span class="encashment-label">Estimated Amount (100% of basic)</span>
                <span class="encashment-value" id="encashAmount">--</span>
            </div>
        </div>
    </div>
</div>

<!-- Leave History Tab -->
<div class="tab-content" id="tab-history">
    <div class="leave-history-card">
        <div class="leave-history-header">
            <span class="leave-history-title">Leave History</span>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <select class="form-select" id="historyMonth" style="width: auto; padding: 8px 12px; font-size: 13px;">
                    <option value="">All Months</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
                <select class="form-select" id="historyYear" style="width: auto; padding: 8px 12px; font-size: 13px;">
                    <option value="">All Years</option>
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
                        echo '<option value="' . $y . '">' . $y . '</option>';
                    }
                    ?>
                </select>
                <select class="form-select" id="historyFilter" style="width: auto; padding: 8px 12px; font-size: 13px;">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="disapproved">Rejected</option>
                </select>
            </div>
        </div>
        <div class="leave-list" id="leaveList">
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="empty-title">No leave records</div>
                <div class="empty-text">Your leave history will appear here</div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
var leaveTypes = [];
var leaveHistory = [];
var teamMembers = [];
var canApplyForOthers = <?php echo $canApplyForOthers ? 'true' : 'false'; ?>;
var applyingForSelf = true;

$(document).ready(function() {
    // Set minimum date to today
    var today = new Date().toISOString().split('T')[0];
    $('#fromDate').attr('min', today);
    $('#toDate').attr('min', today);

    // Load team members if manager/HR
    if (canApplyForOthers) {
        loadTeamMembers();
    }

    // Apply For toggle (Manager/HR only)
    $('.apply-for-btn').on('click', function() {
        var applyFor = $(this).data('for');
        $('.apply-for-btn').removeClass('active');
        $(this).addClass('active');

        if (applyFor === 'team') {
            applyingForSelf = false;
            $('#teamMemberSelect').addClass('show');
            $('#applyForUserID').prop('required', true);
        } else {
            applyingForSelf = true;
            $('#teamMemberSelect').removeClass('show');
            $('#applyForUserID').val('').prop('required', false);
            $('#selectedEmployeeInfo').removeClass('show');
        }
    });

    // Team member selection
    $('#applyForUserID').on('change', function() {
        var userID = $(this).val();
        if (userID) {
            var member = teamMembers.find(m => m.userID == userID);
            if (member) {
                var initials = member.userName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                $('#selectedEmployeeAvatar').text(initials);
                $('#selectedEmployeeName').text(member.userName);
                $('#selectedEmployeeDesignation').text(member.designation || 'Employee');
                $('#selectedEmployeeInfo').addClass('show');
            }
        } else {
            $('#selectedEmployeeInfo').removeClass('show');
        }
    });

    // Load data
    loadLeaveBalance();
    loadLeaveTypes();
    loadLeaveHistory();

    // Tab switching
    $('.tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // Day type toggle
    $('.day-type-btn').on('click', function() {
        $('.day-type-btn').removeClass('active');
        $(this).addClass('active');
        $('#dayType').val($(this).data('type'));
        calculateDays();
    });

    // Date change
    $('#fromDate, #toDate').on('change', function() {
        var fromDate = $('#fromDate').val();
        if (fromDate && !$('#toDate').val()) {
            $('#toDate').val(fromDate);
        }
        $('#toDate').attr('min', fromDate || today);
        calculateDays();
    });

    // Filter history
    $('#historyFilter, #historyMonth, #historyYear').on('change', function() {
        renderLeaveHistory();
    });

    // Form submit
    $('#leaveForm').on('submit', function(e) {
        e.preventDefault();
        submitLeaveRequest();
    });
});

function loadLeaveBalance() {
    // Load basic balance for quick view cards
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getLeaveBalance' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                var data = res.data;

                // Format and display casual balance
                var casualVal = parseFloat(data.casual) || 0;
                $('#casualBalance').text(casualVal).toggleClass('negative', casualVal < 0);

                // Format and display sick balance
                var sickVal = parseFloat(data.sick) || 0;
                $('#sickBalance').text(sickVal).toggleClass('negative', sickVal < 0);

                // Format and display earned balance
                var earnedVal = parseFloat(data.earned) || 0;
                $('#earnedBalance').text(earnedVal).toggleClass('negative', earnedVal < 0);

                // Format and display total balance
                var totalVal = parseFloat(data.total) || 0;
                $('#totalBalance').text(totalVal).toggleClass('negative', totalVal < 0);

                // Toggle total card background if negative
                if (totalVal < 0) {
                    $('.balance-card.total').addClass('negative');
                } else {
                    $('.balance-card.total').removeClass('negative');
                }
            }
        }
    });

    // Load detailed balance summary for Balance Details tab
    loadDetailedBalance();

    // Load comp-off balance
    loadCompOffBalance();

    // Load encashment eligibility
    loadEncashmentEligibility();
}

function loadDetailedBalance() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getLeaveBalanceSummary' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0 && res.data) {
                var data = res.data;

                // Update FY badge
                $('#currentFYBadge').text('FY ' + (data.financialYear || '--'));

                // Update comp-off in quick view
                var compoffBal = data.compOff ? data.compOff.available : 0;
                $('#compoffBalance').text(compoffBal);

                // Update total with comp-off included
                var total = parseFloat($('#casualBalance').text() || 0) +
                           parseFloat($('#sickBalance').text() || 0) +
                           parseFloat($('#earnedBalance').text() || 0) +
                           compoffBal;
                $('#totalBalance').text(total);

                // Build detailed table
                var tableHtml = '';
                if (data.balances && data.balances.length > 0) {
                    data.balances.forEach(function(bal) {
                        var dotClass = getLeaveTypeDotClass(bal.leaveTypeName);
                        var openingVal = parseFloat(bal.opening || bal.openingBalance || 0).toFixed(1);
                        var creditedVal = parseFloat(bal.credited || 0).toFixed(1);
                        var usedVal = parseFloat(bal.used || 0).toFixed(1);
                        var encashedVal = parseFloat(bal.encashed || 0).toFixed(1);
                        var availableVal = parseFloat(bal.available || bal.closingBalance || 0);
                        var isNegative = availableVal < 0;
                        var availableClass = isNegative ? 'balance-num available negative' : 'balance-num available';
                        tableHtml += `
                            <tr>
                                <td>
                                    <span class="leave-type-badge">
                                        <span class="leave-type-dot ${dotClass}"></span>
                                        ${bal.leaveTypeName}
                                    </span>
                                </td>
                                <td class="balance-num">${openingVal}</td>
                                <td class="balance-num">${creditedVal}</td>
                                <td class="balance-num used">${usedVal}</td>
                                <td class="balance-num">${encashedVal}</td>
                                <td class="${availableClass}">${availableVal.toFixed(1)}</td>
                            </tr>
                        `;
                    });
                } else {
                    tableHtml = '<tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No leave balance data available for this financial year.</td></tr>';
                }
                $('#leaveBalanceTableBody').html(tableHtml);
            } else {
                $('#leaveBalanceTableBody').html('<tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">Unable to load balance data.</td></tr>');
            }
        },
        error: function() {
            $('#leaveBalanceTableBody').html('<tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">Error loading data.</td></tr>');
        }
    });
}

function loadCompOffBalance() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getCompOffBalance' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0 && res.data) {
                var data = res.data;
                $('#compoffAvailable').text(data.available || 0);
                $('#compoffPending').text(data.pending || 0);
                $('#compoffUsed').text(data.used || 0);
                $('#compoffExpired').text(data.expired || 0);
                $('#compoffSection').show();
            }
        }
    });
}

function loadEncashmentEligibility() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getEncashmentEligibility' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0 && res.data) {
                var data = res.data;
                $('#encashELBalance').text(parseFloat(data.elBalance || 0).toFixed(1) + ' days');
                $('#encashMaxCarry').text(data.maxCarryForward + ' days');

                if (data.eligibleDays > 0) {
                    $('#encashEligible').text(data.eligibleDays.toFixed(1) + ' days').css('color', 'var(--success)');
                    if (data.estimatedAmount > 0) {
                        $('#encashAmount').text('Rs. ' + formatNumber(data.estimatedAmount));
                        $('#encashAmountRow').show();
                    }
                } else {
                    $('#encashEligible').text('Not eligible (balance must exceed ' + data.maxCarryForward + ' days)').css('color', 'var(--text-muted)');
                }
                $('#encashmentSection').show();
            }
        }
    });
}

function getLeaveTypeDotClass(typeName) {
    var name = typeName.toLowerCase();
    if (name.includes('casual')) return 'cl';
    if (name.includes('sick')) return 'sl';
    if (name.includes('medical')) return 'ml';
    if (name.includes('earned') || name.includes('privilege')) return 'el';
    if (name.includes('comp')) return 'compoff';
    return 'other';
}

function formatNumber(num) {
    return num.toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function loadTeamMembers() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php?ajax=getAllEmployees',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success && res.employees) {
                teamMembers = res.employees;
                var options = '<option value="">Select team member...</option>';
                res.employees.forEach(function(emp) {
                    options += '<option value="' + emp.userID + '">' + emp.userName + (emp.designation ? ' (' + emp.designation + ')' : '') + '</option>';
                });
                $('#applyForUserID').html(options);
            }
        }
    });
}

function loadLeaveTypes() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getLeaveTypes' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                leaveTypes = res.data;
                var options = '<option value="">Select leave type</option>';
                res.data.forEach(function(type) {
                    options += '<option value="' + type.leaveTypeID + '">' + type.leaveTypeName + '</option>';
                });
                $('#leaveType').html(options);
            }
        }
    });
}

function loadLeaveHistory() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getLeaveHistory' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                leaveHistory = res.data;
                renderLeaveHistory();
            }
        }
    });
}

function renderLeaveHistory() {
    var statusFilter = $('#historyFilter').val() || 'all';
    var monthFilter = $('#historyMonth').val();
    var yearFilter = $('#historyYear').val();

    var filtered = leaveHistory.filter(function(leave) {
        // Status filter
        if (statusFilter !== 'all' && leave.leaveStatus.toLowerCase() !== statusFilter.toLowerCase()) {
            return false;
        }

        // Month/Year filter
        if (monthFilter || yearFilter) {
            var leaveDate = new Date(leave.fromDate);
            if (monthFilter && (leaveDate.getMonth() + 1) !== parseInt(monthFilter)) {
                return false;
            }
            if (yearFilter && leaveDate.getFullYear() !== parseInt(yearFilter)) {
                return false;
            }
        }

        return true;
    });

    if (filtered.length === 0) {
        var emptyMsg = 'Your leave history will appear here';
        if (statusFilter !== 'all' || monthFilter || yearFilter) {
            emptyMsg = 'No leaves found for the selected filters';
        }
        $('#leaveList').html(`
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="empty-title">No leave records</div>
                <div class="empty-text">${emptyMsg}</div>
            </div>
        `);
        return;
    }

    var html = '';
    filtered.forEach(function(leave) {
        var iconClass = getLeaveIconClass(leave.leaveTypeName);
        var iconText = getLeaveIconText(leave.leaveTypeName);
        var statusClass = leave.leaveStatus.toLowerCase().replace(' ', '');
        var canCancel = leave.leaveStatus.toLowerCase() === 'pending';

        html += `
            <div class="leave-item">
                <div class="leave-type-icon ${iconClass}">${iconText}</div>
                <div class="leave-info">
                    <div class="leave-dates">${formatDate(leave.fromDate)} - ${formatDate(leave.toDate)}</div>
                    <div class="leave-type-name">${leave.leaveTypeName}</div>
                    <div class="leave-reason">${leave.reason || 'No reason provided'}</div>
                    ${canCancel ? `
                    <div class="leave-actions">
                        <button class="btn-cancel-leave" onclick="cancelLeave(${leave.leaveID})">Cancel Request</button>
                    </div>
                    ` : ''}
                </div>
                <div class="leave-meta">
                    <div class="leave-days">${leave.totalDays} day${leave.totalDays > 1 ? 's' : ''}</div>
                    <span class="leave-status ${statusClass}">${leave.leaveStatus}</span>
                </div>
            </div>
        `;
    });

    $('#leaveList').html(html);
}

function getLeaveIconClass(typeName) {
    var name = typeName.toLowerCase();
    if (name.includes('casual')) return 'casual';
    if (name.includes('sick') || name.includes('medical')) return 'sick';
    if (name.includes('earned') || name.includes('privilege')) return 'earned';
    if (name.includes('emergency')) return 'emergency';
    return 'other';
}

function getLeaveIconText(typeName) {
    var name = typeName.toLowerCase();
    if (name.includes('casual')) return 'CL';
    if (name.includes('sick')) return 'SL';
    if (name.includes('medical')) return 'ML';
    if (name.includes('earned') || name.includes('privilege')) return 'EL';
    if (name.includes('emergency')) return 'EM';
    return 'LV';
}

function formatDate(dateStr) {
    var date = new Date(dateStr);
    var options = { day: 'numeric', month: 'short', year: 'numeric' };
    return date.toLocaleDateString('en-IN', options);
}

function calculateDays() {
    var fromDate = $('#fromDate').val();
    var toDate = $('#toDate').val();
    var dayType = parseInt($('#dayType').val());

    if (!fromDate || !toDate) {
        $('#dateRangePreview').hide();
        return;
    }

    var from = new Date(fromDate);
    var to = new Date(toDate);
    var days = Math.ceil((to - from) / (1000 * 60 * 60 * 24)) + 1;

    if (days < 0) {
        $('#dateRangePreview').hide();
        return;
    }

    // If same day and half day selected
    if (days === 1 && dayType !== 1) {
        days = 0.5;
    }

    $('#totalDays').text(days);
    $('#dateRangePreview').show();
}

function submitLeaveRequest(skipWarningCheck) {
    // Validate team member selection if applying for others
    if (!applyingForSelf && !$('#applyForUserID').val()) {
        showToast('Please select a team member', 'error');
        return;
    }

    var formData = {
        leaveType: $('#leaveType').val(),
        fromDate: $('#fromDate').val(),
        toDate: $('#toDate').val(),
        applyForUserID: applyingForSelf ? '' : $('#applyForUserID').val(),
        dayType: $('#dayType').val(),
        reason: $('#reason').val()
    };

    if (!formData.leaveType || !formData.fromDate || !formData.toDate || !formData.reason) {
        showToast('Please fill all required fields', 'error');
        return;
    }

    // First check balance warning (unless skipping)
    if (!skipWarningCheck) {
        $('#submitBtn').prop('disabled', true).html('Checking...');

        $.ajax({
            url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
            type: 'POST',
            data: $.extend({ xAction: 'checkLeaveBalance' }, formData),
            dataType: 'json',
            success: function(res) {
                if (res.err == 0 && res.warning) {
                    // Show warning modal
                    showBalanceWarningModal(res.warning, function() {
                        // User confirmed, proceed with submission
                        submitLeaveRequest(true);
                    });
                    $('#submitBtn').prop('disabled', false).html(`
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Submit Request
                    `);
                } else {
                    // No warning, proceed directly
                    submitLeaveRequest(true);
                }
            },
            error: function() {
                // On error, proceed anyway
                submitLeaveRequest(true);
            }
        });
        return;
    }

    // Proceed with actual submission
    formData.xAction = 'applyLeave';
    $('#submitBtn').prop('disabled', true).html('Submitting...');

    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                var successMsg = 'Leave request submitted successfully!';
                if (res.warning) {
                    successMsg += ' (Note: Leave quota exceeded)';
                }
                showToast(successMsg, 'success');
                resetForm();
                loadLeaveHistory();
                loadLeaveBalance();
                // Switch to history tab
                $('.tab-btn[data-tab="history"]').click();
            } else {
                showToast(res.msg || 'Failed to submit leave request', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        },
        complete: function() {
            $('#submitBtn').prop('disabled', false).html(`
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Submit Request
            `);
        }
    });
}

function showBalanceWarningModal(warning, onConfirm) {
    var warningType = warning.type === 'over_limit' ? 'ALREADY EXCEEDED' : 'WILL EXCEED LIMIT';
    var modalHtml = `
        <div class="balance-warning-modal" id="balanceWarningModal">
            <div class="balance-warning-overlay"></div>
            <div class="balance-warning-content">
                <div class="balance-warning-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h3 class="balance-warning-title">${warningType}</h3>
                <p class="balance-warning-message">${warning.message}</p>
                <div class="balance-warning-details">
                    <div class="balance-warning-detail">
                        <span class="detail-label">Leave Type:</span>
                        <span class="detail-value">${warning.leaveTypeName}</span>
                    </div>
                    <div class="balance-warning-detail">
                        <span class="detail-label">Allocated:</span>
                        <span class="detail-value">${warning.allowed} days</span>
                    </div>
                    <div class="balance-warning-detail">
                        <span class="detail-label">Already Used:</span>
                        <span class="detail-value">${warning.used} days</span>
                    </div>
                    <div class="balance-warning-detail">
                        <span class="detail-label">Current Balance:</span>
                        <span class="detail-value ${warning.currentBalance < 0 ? 'negative' : ''}">${warning.currentBalance} days</span>
                    </div>
                    <div class="balance-warning-detail">
                        <span class="detail-label">After This Leave:</span>
                        <span class="detail-value negative">${warning.afterBalance} days</span>
                    </div>
                </div>
                <p class="balance-warning-note">This leave will be marked for manager approval with a note about quota exceeded.</p>
                <div class="balance-warning-actions">
                    <button type="button" class="btn-warning-cancel" onclick="closeBalanceWarningModal()">Cancel</button>
                    <button type="button" class="btn-warning-proceed" onclick="proceedWithLeave()">Proceed Anyway</button>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);
    window.balanceWarningCallback = onConfirm;
}

function closeBalanceWarningModal() {
    $('#balanceWarningModal').remove();
    window.balanceWarningCallback = null;
}

function proceedWithLeave() {
    var callback = window.balanceWarningCallback;
    closeBalanceWarningModal();
    if (callback) {
        callback();
    }
}

function cancelLeave(leaveID) {
    if (!confirm('Are you sure you want to cancel this leave request?')) {
        return;
    }

    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'cancelLeave', leaveID: leaveID },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                showToast('Leave request cancelled', 'success');
                loadLeaveHistory();
                loadLeaveBalance();
            } else {
                showToast(res.msg || 'Failed to cancel leave', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

function resetForm() {
    $('#leaveForm')[0].reset();
    $('.day-type-btn').removeClass('active').first().addClass('active');
    $('#dayType').val('1');
    $('#dateRangePreview').hide();
}

function showToast(message, type) {
    var $toast = $('#toast');
    $toast.text(message).removeClass('success error').addClass(type).addClass('show');

    setTimeout(function() {
        $toast.removeClass('show');
    }, 3000);
}
</script>
