<?php
requireHRMSLogin();

$userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
$currentMonth = date('n');
$currentYear = date('Y');
?>

<style>
/* Page Header */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-md);
    margin-bottom: var(--space-xl);
}

.page-header-left {
    display: flex;
    align-items: center;
    gap: var(--space-md);
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

/* Month Selector */
.month-selector {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 8px;
}

.month-selector select {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 8px 12px;
    font-family: var(--font-sans);
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    outline: none;
    cursor: pointer;
}

.month-selector select:focus {
    border-color: var(--accent);
}

/* View Toggle */
.view-toggle {
    display: flex;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 4px;
}

.view-btn {
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-muted);
    background: transparent;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.view-btn svg {
    width: 16px;
    height: 16px;
}

.view-btn.active {
    background: var(--accent);
    color: #000;
}

/* Summary Cards */
.summary-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-md);
    margin-bottom: var(--space-xl);
}

@media (min-width: 640px) {
    .summary-row {
        grid-template-columns: repeat(5, 1fr);
    }
}

.summary-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--card-color, var(--accent));
}

.summary-value {
    font-size: 32px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 8px;
}

.summary-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.summary-card.working { --card-color: var(--text-secondary); }
.summary-card.present { --card-color: var(--success); }
.summary-card.absent { --card-color: var(--error); }
.summary-card.late { --card-color: var(--warning); }
.summary-card.leave { --card-color: var(--info); }

/* Calendar View */
.calendar-container {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    margin-bottom: var(--space-xl);
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border);
}

.calendar-header-cell {
    padding: 14px 8px;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    text-align: center;
}

.calendar-header-cell.weekend {
    color: var(--error);
    opacity: 0.7;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-cell {
    min-height: 90px;
    padding: 10px;
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.calendar-cell:nth-child(7n) {
    border-right: none;
}

.calendar-cell:hover:not(.empty):not(.future) {
    background: var(--bg-card-hover);
}

.calendar-cell.empty {
    background: var(--bg-secondary);
    opacity: 0.5;
    cursor: default;
}

.calendar-cell.today {
    background: var(--accent-light);
}

.calendar-cell.future {
    opacity: 0.4;
    cursor: default;
}

.calendar-cell.weekend {
    background: rgba(239, 68, 68, 0.03);
}

.calendar-date {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 6px;
}

.calendar-cell.today .calendar-date {
    color: var(--accent);
    font-weight: 800;
}

.calendar-cell.weekend .calendar-date {
    color: var(--error);
    opacity: 0.7;
}

.calendar-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-status-badge.present { background: var(--success-bg); color: var(--success); }
.calendar-status-badge.absent { background: var(--error-bg); color: var(--error); }
.calendar-status-badge.late { background: var(--warning-bg); color: var(--warning); }
.calendar-status-badge.leave { background: var(--info-bg); color: var(--info); }
.calendar-status-badge.half_day { background: var(--warning-bg); color: var(--warning); }
.calendar-status-badge.holiday { background: #fef3c7; color: #d97706; }
.calendar-status-badge.weekly_off { background: var(--bg-elevated); color: var(--text-disabled); }

.calendar-cell.holiday {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.calendar-cell.holiday .calendar-date {
    color: #d97706;
    font-weight: 700;
}

.holiday-name {
    font-size: 9px;
    color: #b45309;
    margin-top: 4px;
    font-weight: 600;
    line-height: 1.2;
}

.leave-name {
    font-size: 9px;
    color: var(--info);
    margin-top: 4px;
    font-weight: 600;
    line-height: 1.2;
}

.calendar-cell.leave {
    background: var(--info-bg);
}

.calendar-times {
    margin-top: auto;
    font-size: 10px;
    font-family: var(--font-mono);
    color: var(--text-muted);
}

.calendar-times .in-time {
    color: var(--success);
}

.calendar-times .out-time {
    color: var(--text-secondary);
}

.calendar-times.late .in-time {
    color: var(--warning);
}

/* Legend */
.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 14px 16px;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--text-muted);
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.legend-dot.present { background: var(--success); }
.legend-dot.absent { background: var(--error); }
.legend-dot.late { background: var(--warning); }
.legend-dot.leave { background: var(--info); }
.legend-dot.holiday { background: var(--text-muted); }

/* Attendance List View */
.attendance-list {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.list-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.list-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.download-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--accent);
    color: #000;
    font-size: 12px;
    font-weight: 600;
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s;
}

.download-btn:hover {
    opacity: 0.9;
}

.download-btn svg {
    width: 14px;
    height: 14px;
}

.attendance-row {
    display: grid;
    grid-template-columns: 100px 1fr 1fr auto;
    gap: var(--space-md);
    align-items: center;
    padding: 14px var(--space-lg);
    border-bottom: 1px solid var(--border);
    transition: all 0.2s ease;
}

@media (max-width: 640px) {
    .attendance-row {
        grid-template-columns: 80px 1fr auto;
    }
    .attendance-row .col-checkout {
        display: none;
    }
}

.attendance-row:hover {
    background: var(--bg-card-hover);
}

.attendance-row:last-child {
    border-bottom: none;
}

.col-date {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.date-day {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.date-weekday {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
}

.col-time {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.time-label {
    font-size: 10px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.time-value {
    font-size: 14px;
    font-family: var(--font-mono);
    font-weight: 500;
    color: var(--text-primary);
}

.time-value.late {
    color: var(--warning);
}

.col-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}

.status-badge {
    padding: 4px 10px;
    font-size: 10px;
    font-weight: 700;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.present { background: var(--success-bg); color: var(--success); }
.status-badge.absent { background: var(--error-bg); color: var(--error); }
.status-badge.late { background: var(--warning-bg); color: var(--warning); }
.status-badge.leave { background: var(--info-bg); color: var(--info); }
.status-badge.half_day { background: var(--warning-bg); color: var(--warning); }

.add-remark-btn {
    font-size: 11px;
    color: var(--accent);
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font-weight: 600;
}

.add-remark-btn:hover {
    text-decoration: underline;
}

/* Working Hours Chart */
.hours-chart {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    margin-bottom: var(--space-xl);
}

.hours-chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-lg);
}

.hours-chart-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.hours-chart-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--accent);
    font-family: var(--font-mono);
}

.hours-chart-value span {
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 500;
}

.hours-bar-container {
    height: 200px;
    position: relative;
}

/* Remark Modal */
.remark-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: var(--space-lg);
}

.remark-modal.show {
    display: flex;
}

.remark-modal-content {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    padding: var(--space-xl);
    width: 100%;
    max-width: 440px;
    animation: modalSlide 0.3s ease;
}

@keyframes modalSlide {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.remark-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-lg);
}

.remark-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
}

.remark-modal-close {
    width: 32px;
    height: 32px;
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

.remark-modal-close:hover {
    background: var(--error-bg);
    color: var(--error);
}

.remark-field {
    margin-bottom: var(--space-lg);
}

.remark-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.remark-select,
.remark-textarea {
    width: 100%;
    padding: 12px 14px;
    background: var(--bg-secondary);
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-size: 14px;
    color: var(--text-primary);
    outline: none;
    transition: all 0.2s ease;
}

.remark-textarea {
    min-height: 100px;
    resize: vertical;
}

.remark-select:focus,
.remark-textarea:focus {
    border-color: var(--accent);
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
    margin-bottom: 8px;
}

.empty-text {
    font-size: 14px;
    color: var(--text-muted);
}

/* Day Detail Modal */
.day-detail-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: var(--space-lg);
}

.day-detail-modal.show {
    display: flex;
}

.day-detail-content {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    padding: var(--space-xl);
    width: 100%;
    max-width: 380px;
    animation: modalSlide 0.3s ease;
}

.day-detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-lg);
    padding-bottom: var(--space-md);
    border-bottom: 1px solid var(--border);
}

.day-detail-date {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
}

.day-detail-weekday {
    font-size: 14px;
    color: var(--text-muted);
    margin-top: 4px;
}

.day-detail-close {
    width: 32px;
    height: 32px;
    background: var(--bg-elevated);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}

.day-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.day-detail-row:last-child {
    border-bottom: none;
}

.day-detail-label {
    font-size: 13px;
    color: var(--text-muted);
}

.day-detail-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    font-family: var(--font-mono);
}

.day-detail-value.late {
    color: var(--warning);
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div>
            <h1 class="hrms-page-title">Attendance</h1>
            <p class="hrms-page-subtitle">View your attendance history and add remarks</p>
        </div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <div class="view-toggle">
            <button class="view-btn active" id="calendarViewBtn" onclick="switchView('calendar')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Calendar
            </button>
            <button class="view-btn" id="listViewBtn" onclick="switchView('list')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                List
            </button>
        </div>
        <div class="month-selector">
            <select id="monthSelect">
                <?php
                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                for ($i = 1; $i <= 12; $i++) {
                    $selected = ($i == $currentMonth) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>{$months[$i-1]}</option>";
                }
                ?>
            </select>
            <select id="yearSelect">
                <?php
                for ($y = $currentYear - 1; $y <= $currentYear; $y++) {
                    $selected = ($y == $currentYear) ? 'selected' : '';
                    echo "<option value=\"$y\" $selected>$y</option>";
                }
                ?>
            </select>
        </div>
    </div>
</div>

<!-- Loading State -->
<div id="attendanceLoading" style="text-align: center; padding: 60px;">
    <div style="display: inline-flex; gap: 6px;">
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite;"></div>
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite; animation-delay: 0.2s;"></div>
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite; animation-delay: 0.4s;"></div>
    </div>
    <p style="color: var(--text-muted); margin-top: 16px; font-size: 14px;">Loading attendance...</p>
</div>

<!-- Attendance Content -->
<div id="attendanceContent" style="display: none;">
    <!-- Summary Cards -->
    <div class="summary-row" id="summaryRow">
        <div class="summary-card working">
            <div class="summary-value" id="workingDays">--</div>
            <div class="summary-label">Working Days</div>
        </div>
        <div class="summary-card present">
            <div class="summary-value" id="presentDays">--</div>
            <div class="summary-label">Present</div>
        </div>
        <div class="summary-card absent">
            <div class="summary-value" id="absentDays">--</div>
            <div class="summary-label">Absent</div>
        </div>
        <div class="summary-card late">
            <div class="summary-value" id="lateDays">--</div>
            <div class="summary-label">Late</div>
        </div>
        <div class="summary-card leave">
            <div class="summary-value" id="leaveDays">--</div>
            <div class="summary-label">Leave</div>
        </div>
    </div>

    <!-- Calendar View -->
    <div id="calendarView">
        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-header-cell">Mon</div>
                <div class="calendar-header-cell">Tue</div>
                <div class="calendar-header-cell">Wed</div>
                <div class="calendar-header-cell">Thu</div>
                <div class="calendar-header-cell">Fri</div>
                <div class="calendar-header-cell weekend">Sat</div>
                <div class="calendar-header-cell weekend">Sun</div>
            </div>
            <div class="calendar-grid" id="calendarGrid">
                <!-- Populated by JS -->
            </div>
            <div class="calendar-legend">
                <div class="legend-item"><span class="legend-dot present"></span> Present</div>
                <div class="legend-item"><span class="legend-dot absent"></span> Absent</div>
                <div class="legend-item"><span class="legend-dot late"></span> Late</div>
                <div class="legend-item"><span class="legend-dot leave"></span> Leave</div>
                <div class="legend-item"><span class="legend-dot holiday"></span> Holiday/Off</div>
            </div>
        </div>
    </div>

    <!-- List View -->
    <div id="listView" style="display:none;">
        <div class="attendance-list">
            <div class="list-header">
                <div class="list-title">Daily Breakdown</div>
                <button class="download-btn" onclick="downloadAttendance()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download
                </button>
            </div>
            <div id="attendanceListBody">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Day Detail Modal -->
<div class="day-detail-modal" id="dayDetailModal">
    <div class="day-detail-content">
        <div class="day-detail-header">
            <div>
                <div class="day-detail-date" id="detailDate"></div>
                <div class="day-detail-weekday" id="detailWeekday"></div>
            </div>
            <button class="day-detail-close" onclick="closeDayDetail()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div id="dayDetailBody">
            <!-- Populated by JS -->
        </div>
    </div>
</div>

<!-- Remark Modal -->
<div class="remark-modal" id="remarkModal">
    <div class="remark-modal-content">
        <div class="remark-modal-header">
            <div class="remark-modal-title">Add Remark</div>
            <button class="remark-modal-close" onclick="closeRemarkModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <form id="remarkForm">
            <input type="hidden" id="remarkAttendanceID" name="attendanceID">
            <div class="remark-field">
                <label class="remark-label">Reason Type</label>
                <select class="remark-select" id="remarkType" name="remarkType">
                    <option value="late_arrival">Late Arrival</option>
                    <option value="early_checkout">Early Checkout</option>
                    <option value="absence">Absence</option>
                    <option value="correction">Correction Request</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="remark-field">
                <label class="remark-label">Explanation</label>
                <textarea class="remark-textarea" id="remarkReason" name="reason" placeholder="Please provide a brief explanation..." required></textarea>
            </div>
            <button type="submit" class="hrms-btn hrms-btn-primary" style="width: 100%;">
                Submit Remark
            </button>
        </form>
    </div>
</div>

<script>
var attendanceData = [];
var attendanceMap = {};
var holidaysMap = {};
var currentView = 'calendar';

$(document).ready(function() {
    loadAttendance();

    $('#monthSelect, #yearSelect').on('change', function() {
        loadAttendance();
    });

    $('#remarkForm').on('submit', function(e) {
        e.preventDefault();
        submitRemark();
    });
});

function switchView(view) {
    currentView = view;
    if (view === 'calendar') {
        $('#calendarView').show();
        $('#listView').hide();
        $('#calendarViewBtn').addClass('active');
        $('#listViewBtn').removeClass('active');
    } else {
        $('#calendarView').hide();
        $('#listView').show();
        $('#calendarViewBtn').removeClass('active');
        $('#listViewBtn').addClass('active');
    }
}

var leavesMap = {};

function loadAttendance() {
    var month = $('#monthSelect').val();
    var year = $('#yearSelect').val();

    $('#attendanceLoading').show();
    $('#attendanceContent').hide();

    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getAttendance', month: month, year: year },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                attendanceData = res.data;
                holidaysMap = res.holidays || {};
                leavesMap = res.leaves || {};
                buildAttendanceMap(res.data);
                renderAttendance(res.data, month, year);
                renderCalendar(month, year);
            } else {
                alert(res.msg);
            }
        },
        error: function() {
            alert('Failed to load attendance. Please try again.');
        },
        complete: function() {
            $('#attendanceLoading').hide();
            $('#attendanceContent').show();
        }
    });
}

function buildAttendanceMap(data) {
    attendanceMap = {};
    data.forEach(function(item) {
        attendanceMap[item.attendanceDate] = item;
    });
}

function renderCalendar(month, year) {
    var firstDay = new Date(year, month - 1, 1);
    var lastDay = new Date(year, month, 0);
    var daysInMonth = lastDay.getDate();
    var startWeekday = firstDay.getDay();
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    // Adjust for Monday start (0=Mon, 6=Sun)
    startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

    var html = '';

    // Empty cells before first day
    for (var i = 0; i < startWeekday; i++) {
        html += '<div class="calendar-cell empty"></div>';
    }

    // Days of month
    var weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    for (var d = 1; d <= daysInMonth; d++) {
        var dateStr = year + '-' + String(month).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        var dateObj = new Date(year, month - 1, d);
        var dayOfWeek = dateObj.getDay();
        var isWeekend = dayOfWeek === 0; // Only Sunday is weekly off (Saturday is working day)
        var isToday = dateObj.getTime() === today.getTime();
        var isFuture = dateObj > today;
        var isHoliday = holidaysMap.hasOwnProperty(dateStr);
        var holidayName = isHoliday ? holidaysMap[dateStr] : '';
        var isOnLeave = leavesMap.hasOwnProperty(dateStr);
        var leaveInfo = isOnLeave ? leavesMap[dateStr] : null;

        var cellClass = 'calendar-cell';
        if (isHoliday) cellClass += ' holiday';
        else if (isOnLeave) cellClass += ' leave';
        else if (isWeekend) cellClass += ' weekend';
        if (isToday) cellClass += ' today';
        if (isFuture) cellClass += ' future';

        var att = attendanceMap[dateStr];
        var statusHtml = '';
        var timesHtml = '';
        var holidayHtml = '';

        if (isHoliday) {
            statusHtml = '<span class="calendar-status-badge holiday">Holiday</span>';
            holidayHtml = '<div class="holiday-name">' + holidayName + '</div>';
        } else if (isOnLeave) {
            // Show leave status with leave type
            var leaveLabel = leaveInfo.code || 'L';
            statusHtml = '<span class="calendar-status-badge leave">' + leaveLabel + '</span>';
            holidayHtml = '<div class="leave-name">' + (leaveInfo.type || 'Leave') + '</div>';
        } else if (isWeekend && !att) {
            statusHtml = '<span class="calendar-status-badge weekly_off">Off</span>';
        } else if (att) {
            var status = att.attendanceStatus;
            var isLate = att.isLate == 1;

            if (isLate) {
                statusHtml = '<span class="calendar-status-badge late">Late</span>';
            } else {
                statusHtml = '<span class="calendar-status-badge ' + status + '">' + formatStatus(status) + '</span>';
            }

            if (att.checkIn || att.checkOut) {
                var timeClass = isLate ? ' late' : '';
                timesHtml = '<div class="calendar-times' + timeClass + '">';
                if (att.checkIn) timesHtml += '<span class="in-time">' + formatTime(att.checkIn) + '</span>';
                if (att.checkIn && att.checkOut) timesHtml += ' - ';
                if (att.checkOut) timesHtml += '<span class="out-time">' + formatTime(att.checkOut) + '</span>';
                timesHtml += '</div>';
            }
        } else if (!isFuture && !isWeekend) {
            statusHtml = '<span class="calendar-status-badge absent">Absent</span>';
        }

        html += '<div class="' + cellClass + '" onclick="showDayDetail(\'' + dateStr + '\')">';
        html += '<div class="calendar-date">' + d + '</div>';
        html += statusHtml;
        html += holidayHtml;
        html += timesHtml;
        html += '</div>';
    }

    // Empty cells after last day
    var endWeekday = lastDay.getDay();
    endWeekday = endWeekday === 0 ? 6 : endWeekday - 1;
    for (var i = endWeekday + 1; i < 7; i++) {
        html += '<div class="calendar-cell empty"></div>';
    }

    $('#calendarGrid').html(html);
}

function renderAttendance(data, month, year) {
    // Calculate summary
    var summary = {
        working: 0,
        present: 0,
        absent: 0,
        late: 0,
        leave: 0
    };

    // Count leaves from leavesMap
    summary.leave = Object.keys(leavesMap).length;

    // Track dates that have attendance records
    var attendanceDates = {};
    data.forEach(function(item) {
        attendanceDates[item.attendanceDate] = true;
        if (item.attendanceStatus === 'present') summary.present++;
        else if (item.attendanceStatus === 'absent') summary.absent++;

        if (item.isLate == 1) summary.late++;
    });

    // Calculate working days and absent days (exclude weekends, holidays, and leaves)
    var daysInMonth = new Date(year, month, 0).getDate();
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    var absentCount = 0;
    for (var d = 1; d <= daysInMonth; d++) {
        var checkDate = new Date(year, month - 1, d);
        var dateStr = year + '-' + String(month).padStart(2, '0') + '-' + String(d).padStart(2, '0');

        // Only count past/today weekdays
        if (checkDate <= today && checkDate.getDay() !== 0 && checkDate.getDay() !== 6) {
            // Skip holidays
            if (holidaysMap.hasOwnProperty(dateStr)) continue;

            summary.working++;

            // Count as absent if no attendance record and not on leave
            if (!attendanceDates[dateStr] && !leavesMap.hasOwnProperty(dateStr)) {
                absentCount++;
            }
        }
    }

    // Use calculated absent count instead of from data
    summary.absent = absentCount;

    // Update summary cards
    $('#workingDays').text(summary.working);
    $('#presentDays').text(summary.present);
    $('#absentDays').text(summary.absent);
    $('#lateDays').text(summary.late);
    $('#leaveDays').text(summary.leave);

    // Render list
    var listHtml = '';
    var weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    if (data.length === 0) {
        listHtml = `
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="empty-title">No attendance records</div>
                <div class="empty-text">No attendance data for this month yet.</div>
            </div>
        `;
    } else {
        // Sort by date descending
        data.sort(function(a, b) {
            return new Date(b.attendanceDate) - new Date(a.attendanceDate);
        });

        data.forEach(function(item) {
            var date = new Date(item.attendanceDate);
            var dayNum = date.getDate();
            var weekday = weekdays[date.getDay()];
            var checkIn = item.checkIn ? formatTime(item.checkIn) : '--:--';
            var checkOut = item.checkOut ? formatTime(item.checkOut) : '--:--';
            var status = item.attendanceStatus || 'present';
            var isLate = item.isLate == 1;
            var lateMinutes = item.lateMinutes || 0;

            var statusLabel = formatStatus(status);
            if (isLate) {
                statusLabel = 'Late (' + lateMinutes + 'm)';
            }

            var hasRemark = item.lastRemark ? true : false;

            listHtml += `
                <div class="attendance-row">
                    <div class="col-date">
                        <span class="date-day">${dayNum}</span>
                        <span class="date-weekday">${weekday}</span>
                    </div>
                    <div class="col-time">
                        <span class="time-label">Check In</span>
                        <span class="time-value ${isLate ? 'late' : ''}">${checkIn}</span>
                    </div>
                    <div class="col-time col-checkout">
                        <span class="time-label">Check Out</span>
                        <span class="time-value">${checkOut}</span>
                    </div>
                    <div class="col-status">
                        <span class="status-badge ${status}">${statusLabel}</span>
                        ${(isLate || status === 'absent') ? `<button class="add-remark-btn" onclick="openRemarkModal(${item.attendanceID})">${hasRemark ? 'View Remark' : 'Add Remark'}</button>` : ''}
                    </div>
                </div>
            `;
        });
    }

    $('#attendanceListBody').html(listHtml);
}

function showDayDetail(dateStr) {
    var att = attendanceMap[dateStr];
    var dateObj = new Date(dateStr + 'T00:00:00');
    var weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var isHoliday = holidaysMap.hasOwnProperty(dateStr);
    var holidayName = isHoliday ? holidaysMap[dateStr] : '';

    $('#detailDate').text(dateObj.getDate() + ' ' + ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][dateObj.getMonth()] + ' ' + dateObj.getFullYear());
    $('#detailWeekday').text(weekdays[dateObj.getDay()]);

    var html = '';

    if (isHoliday) {
        html += '<div class="day-detail-row"><span class="day-detail-label">Status</span><span class="day-detail-value" style="color:#d97706;">Holiday</span></div>';
        html += '<div class="day-detail-row"><span class="day-detail-label">Occasion</span><span class="day-detail-value">' + holidayName + '</span></div>';
    } else if (att) {
        var status = att.isLate == 1 ? 'Late' : formatStatus(att.attendanceStatus);
        html += '<div class="day-detail-row"><span class="day-detail-label">Status</span><span class="day-detail-value">' + status + '</span></div>';
        html += '<div class="day-detail-row"><span class="day-detail-label">Check In</span><span class="day-detail-value' + (att.isLate == 1 ? ' late' : '') + '">' + (att.checkIn ? formatTime(att.checkIn) : '--:--') + '</span></div>';
        html += '<div class="day-detail-row"><span class="day-detail-label">Check Out</span><span class="day-detail-value">' + (att.checkOut ? formatTime(att.checkOut) : '--:--') + '</span></div>';
        html += '<div class="day-detail-row"><span class="day-detail-label">Working Hours</span><span class="day-detail-value">' + (att.workingHours ? att.workingHours + ' hrs' : '-') + '</span></div>';

        if (att.isLate == 1) {
            html += '<div class="day-detail-row"><span class="day-detail-label">Late By</span><span class="day-detail-value late">' + att.lateMinutes + ' mins</span></div>';
        }

        if (att.isLate == 1 || att.attendanceStatus === 'absent') {
            html += '<div style="margin-top:16px;"><button class="hrms-btn hrms-btn-secondary" style="width:100%;" onclick="closeDayDetail();openRemarkModal(' + att.attendanceID + ')">Add Remark</button></div>';
        }
    } else if (dateObj.getDay() === 0) {
        // Only Sunday is weekly off (Saturday is a working day)
        html += '<div class="day-detail-row"><span class="day-detail-label">Status</span><span class="day-detail-value">Weekly Off</span></div>';
    } else {
        html += '<div class="day-detail-row"><span class="day-detail-label">Status</span><span class="day-detail-value">No Record</span></div>';
    }

    $('#dayDetailBody').html(html);
    $('#dayDetailModal').addClass('show');
}

function closeDayDetail() {
    $('#dayDetailModal').removeClass('show');
}

function formatTime(datetime) {
    if (!datetime) return '--:--';
    var time = datetime.split(' ')[1] || datetime;
    var parts = time.split(':');
    var hours = parseInt(parts[0]);
    var minutes = parts[1];
    var ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    return hours + ':' + minutes + ' ' + ampm;
}

function formatStatus(status) {
    if (!status) return 'Present';
    return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
}

function openRemarkModal(attendanceID) {
    $('#remarkAttendanceID').val(attendanceID);
    $('#remarkReason').val('');
    $('#remarkModal').addClass('show');
}

function closeRemarkModal() {
    $('#remarkModal').removeClass('show');
}

function submitRemark() {
    var attendanceID = $('#remarkAttendanceID').val();
    var remarkType = $('#remarkType').val();
    var reason = $('#remarkReason').val().trim();

    if (!reason) {
        alert('Please provide an explanation');
        return;
    }

    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: {
            xAction: 'submitRemark',
            attendanceID: attendanceID,
            remarkType: remarkType,
            reason: reason
        },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                closeRemarkModal();
                loadAttendance();
                alert('Remark submitted successfully!');
            } else {
                alert(res.msg);
            }
        },
        error: function() {
            alert('Failed to submit remark. Please try again.');
        }
    });
}

function downloadAttendance() {
    var month = $('#monthSelect').val();
    var year = $('#yearSelect').val();
    window.open(SITEURL + '/xsite/mod/hrms/x-hrms.inc.php?action=downloadAttendancePDF&month=' + month + '&year=' + year, '_blank');
}

// Close modals on outside click
$('#remarkModal, #dayDetailModal').on('click', function(e) {
    if (e.target === this) {
        $(this).removeClass('show');
    }
});
</script>
