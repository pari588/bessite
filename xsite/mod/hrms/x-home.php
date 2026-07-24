<?php
requireHRMSLogin();

$userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
$isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
?>

<style>
/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    gap: var(--space-lg);
}

@media (min-width: 768px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-elevated) 100%);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    padding: var(--space-xl);
    margin-bottom: var(--space-xl);
    position: relative;
    overflow: hidden;
}

.welcome-banner::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, var(--accent-light) 0%, transparent 70%);
    transform: translate(30%, -30%);
}

.welcome-content {
    position: relative;
    z-index: 1;
}

.welcome-greeting {
    font-size: 14px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 8px;
}

.welcome-name {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-primary);
    letter-spacing: -0.5px;
    margin-bottom: 4px;
}

.welcome-date {
    font-size: 15px;
    color: var(--text-secondary);
}

.welcome-date strong {
    color: var(--accent);
}

/* Quick Stats Row */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-md);
    margin-bottom: var(--space-xl);
}

@media (min-width: 640px) {
    .quick-stats {
        grid-template-columns: repeat(4, 1fr);
    }
}

.quick-stat {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.quick-stat:hover {
    border-color: var(--stat-color, var(--border-light));
    transform: translateY(-2px);
}

.quick-stat::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--stat-color, var(--accent));
}

.quick-stat-icon {
    width: 40px;
    height: 40px;
    background: var(--stat-bg, var(--accent-light));
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--space-md);
}

.quick-stat-icon svg {
    width: 20px;
    height: 20px;
    color: var(--stat-color, var(--accent));
}

.quick-stat-value {
    font-size: 28px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 6px;
}

.quick-stat-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.quick-stat.present {
    --stat-color: var(--success);
    --stat-bg: var(--success-bg);
}

.quick-stat.absent {
    --stat-color: var(--error);
    --stat-bg: var(--error-bg);
}

.quick-stat.late {
    --stat-color: var(--warning);
    --stat-bg: var(--warning-bg);
}

.quick-stat.leave {
    --stat-color: var(--info);
    --stat-bg: var(--info-bg);
}

/* Section Cards */
.section-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border);
    gap: 10px;
    flex-wrap: wrap;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-primary);
    text-transform: uppercase;
    letter-spacing: 1px;
    white-space: nowrap;
}

.section-title span {
    display: inline;
}

.section-title-icon {
    width: 32px;
    height: 32px;
    background: var(--accent-light);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-title-icon svg {
    width: 16px;
    height: 16px;
    color: var(--accent);
}

.section-body {
    padding: var(--space-lg);
}

/* Attendance List */
.attendance-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.attendance-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: 12px 14px;
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    transition: all 0.2s ease;
}

.attendance-item:hover {
    background: var(--bg-elevated);
}

.attendance-date {
    width: 48px;
    height: 48px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.attendance-date-day {
    font-size: 16px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
}

.attendance-date-month {
    font-size: 9px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.attendance-info {
    flex: 1;
    min-width: 0;
}

.attendance-times {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--text-primary);
    font-family: var(--font-mono);
    font-weight: 500;
}

.attendance-times .separator {
    color: var(--text-muted);
}

.attendance-hours {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 4px;
}

/* Salary Slips List */
.salary-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.salary-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    transition: all 0.2s ease;
    cursor: pointer;
}

.salary-item:hover {
    background: var(--bg-elevated);
}

.salary-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.salary-month-badge {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, var(--accent-light), rgba(245, 158, 11, 0.05));
    border-radius: var(--radius-md);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.salary-month-text {
    font-size: 10px;
    font-weight: 700;
    color: var(--accent);
    text-transform: uppercase;
}

.salary-year-text {
    font-size: 9px;
    color: var(--text-muted);
}

.salary-details {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.salary-amount {
    font-size: 16px;
    font-weight: 700;
    font-family: var(--font-mono);
    color: var(--success);
}

/* Leave Balance */
.leave-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-md);
}

.leave-item {
    text-align: center;
    padding: var(--space-md);
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
}

.leave-value {
    font-size: 24px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--text-primary);
}

.leave-value.negative {
    color: var(--error);
}

.leave-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

/* Upcoming Birthdays */

.birthday-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.birthday-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    transition: all 0.2s ease;
}

.birthday-item:hover {
    background: var(--bg-elevated);
}

.birthday-item.today {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.15), rgba(244, 114, 182, 0.08));
    border: 1px solid rgba(236, 72, 153, 0.3);
}

.birthday-avatar {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #ec4899, #f472b6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}

.birthday-info {
    flex: 1;
    min-width: 0;
}

.birthday-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.birthday-dept {
    font-size: 11px;
    color: var(--text-muted);
}

.birthday-date {
    text-align: right;
    flex-shrink: 0;
}

.birthday-day {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-primary);
}

.birthday-month {
    font-size: 10px;
    color: var(--text-muted);
    text-transform: uppercase;
}

.birthday-today-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ec4899, #f472b6);
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Team Overview (for managers) */
.team-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

@media (min-width: 480px) {
    .team-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    .team-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.team-member {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    min-width: 0;
}

.team-avatar {
    width: 36px;
    height: 36px;
    background: var(--bg-elevated);
    border: 2px solid var(--border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    color: var(--accent);
    flex-shrink: 0;
}

.team-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.team-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.team-role {
    font-size: 10px;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-md);
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: var(--space-lg);
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    text-decoration: none;
    transition: all 0.3s ease;
}

.quick-action:hover {
    background: var(--bg-elevated);
    border-color: var(--accent);
    transform: translateY(-2px);
}

.quick-action-icon {
    width: 48px;
    height: 48px;
    background: var(--accent-light);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-action-icon svg {
    width: 24px;
    height: 24px;
    color: var(--accent);
}

.quick-action-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-primary);
    text-align: center;
}

/* Current Time Widget */
.time-widget {
    background: linear-gradient(135deg, var(--accent), var(--accent-muted));
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    text-align: center;
    margin-bottom: var(--space-xl);
}

.time-widget-label {
    font-size: 10px;
    font-weight: 600;
    color: rgba(0,0,0,0.5);
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 8px;
}

.time-widget-value {
    font-size: 32px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--bg-primary);
    line-height: 1;
}

/* Full Width Card */
.full-width-card {
    grid-column: 1 / -1;
}
</style>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-content">
        <div class="welcome-greeting">Welcome back</div>
        <div class="welcome-name"><?php echo htmlspecialchars($userName); ?></div>
        <div class="welcome-date">Today is <strong id="currentDate"></strong></div>
    </div>
</div>

<!-- Loading State -->
<div id="dashboardLoading" style="text-align: center; padding: 60px;">
    <div style="display: inline-flex; gap: 6px;">
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite;"></div>
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite; animation-delay: 0.2s;"></div>
        <div style="width: 10px; height: 10px; background: var(--accent); border-radius: 50%; animation: loaderPulse 1.4s ease-in-out infinite; animation-delay: 0.4s;"></div>
    </div>
    <p style="color: var(--text-muted); margin-top: 16px; font-size: 14px;">Loading dashboard...</p>
</div>

<!-- Dashboard Content -->
<div id="dashboardContent" style="display: none;">
    <!-- Quick Stats -->
    <div class="quick-stats" id="attendanceStats">
        <div class="quick-stat present">
            <div class="quick-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="quick-stat-value" id="presentDays">0</div>
            <div class="quick-stat-label">Present</div>
        </div>
        <div class="quick-stat absent">
            <div class="quick-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <div class="quick-stat-value" id="absentDays">0</div>
            <div class="quick-stat-label">Absent</div>
        </div>
        <div class="quick-stat late">
            <div class="quick-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="quick-stat-value" id="lateDays">0</div>
            <div class="quick-stat-label">Late</div>
        </div>
        <div class="quick-stat leave">
            <div class="quick-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="quick-stat-value" id="leaveDays">0</div>
            <div class="quick-stat-label">Leave</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Recent Attendance -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    Recent Attendance
                </div>
                <a href="<?php echo SITEURL; ?>/hrms/attendance/" class="hrms-btn hrms-btn-ghost" style="padding: 6px 12px; font-size: 11px;">
                    View All
                </a>
            </div>
            <div class="section-body">
                <div class="attendance-list" id="recentAttendance">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Recent Salary Slips -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    Salary Slips
                </div>
                <a href="<?php echo SITEURL; ?>/hrms/salary/" class="hrms-btn hrms-btn-ghost" style="padding: 6px 12px; font-size: 11px;">
                    View All
                </a>
            </div>
            <div class="section-body">
                <div class="salary-list" id="recentSalarySlips">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Leave Balance -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                    Leave Balance
                </div>
            </div>
            <div class="section-body">
                <div class="leave-grid" id="leaveBalance">
                    <div class="leave-item">
                        <div class="leave-value" id="paidLeave">--</div>
                        <div class="leave-label">Paid</div>
                    </div>
                    <div class="leave-item">
                        <div class="leave-value" id="casualLeave">--</div>
                        <div class="leave-label">Casual</div>
                    </div>
                    <div class="leave-item">
                        <div class="leave-value" id="sickLeave">--</div>
                        <div class="leave-label">Sick</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Birthdays -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon birthday-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    Upcoming Birthdays
                </div>
            </div>
            <div class="section-body">
                <div class="birthday-list" id="upcomingBirthdays">
                    <div class="hrms-empty"><p class="hrms-empty-text">Loading...</p></div>
                </div>
            </div>
        </div>

        <?php if ($isManager): ?>
        <!-- Team Overview (for managers) -->
        <div class="section-card full-width-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    Your Team<span style="letter-spacing:0;margin-left:4px;">(<span id="teamCount">0</span> members)</span>
                </div>
                <a href="<?php echo SITEURL; ?>/hrms/team/" class="hrms-btn hrms-btn-ghost" style="padding: 6px 12px; font-size: 11px;">
                    Manage Team
                </a>
            </div>
            <div class="section-body">
                <div class="team-grid" id="teamMembers">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    Quick Actions
                </div>
            </div>
            <div class="section-body">
                <div class="quick-actions">
                    <a href="<?php echo SITEURL; ?>/hrms/attendance/" class="quick-action">
                        <div class="quick-action-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <span class="quick-action-label">View Attendance</span>
                    </a>
                    <a href="<?php echo SITEURL; ?>/hrms/salary/" class="quick-action">
                        <div class="quick-action-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </div>
                        <span class="quick-action-label">Download Slip</span>
                    </a>
                    <a href="<?php echo SITEURL; ?>/hrms/documents/" class="quick-action">
                        <div class="quick-action-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <span class="quick-action-label">My Documents</span>
                    </a>
                    <a href="<?php echo SITEURL; ?>/hrms/leave/" class="quick-action">
                        <div class="quick-action-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                        <span class="quick-action-label">Apply Leave</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Set current date
    var now = new Date();
    var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    $('#currentDate').text(now.toLocaleDateString('en-IN', options));

    // Load dashboard data
    loadDashboard();
});

function loadDashboard() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getDashboard' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                renderDashboard(res.data);
            } else {
                alert(res.msg);
            }
        },
        error: function() {
            // Show empty state
            $('#dashboardLoading').html('<p style="color: var(--text-muted);">Failed to load dashboard. Please refresh.</p>');
        }
    });
}

function renderDashboard(data) {
    $('#dashboardLoading').hide();
    $('#dashboardContent').show();

    // Attendance Stats
    var stats = data.attendanceSummary || {};
    $('#presentDays').text(stats.presentDays || 0);
    $('#absentDays').text(stats.absentDays || 0);
    $('#lateDays').text(stats.lateDays || 0);
    $('#leaveDays').text(stats.leaveDays || 0);

    // Leave Balance (show negative values in red)
    var leave = data.leaveBalance || {};
    var paidVal = parseFloat(leave.paidLeave) || 0;
    var casualVal = parseFloat(leave.casualLeave) || 0;
    var sickVal = parseFloat(leave.sickLeave) || 0;

    $('#paidLeave').text(paidVal).toggleClass('negative', paidVal < 0);
    $('#casualLeave').text(casualVal).toggleClass('negative', casualVal < 0);
    $('#sickLeave').text(sickVal).toggleClass('negative', sickVal < 0);

    // Recent Attendance
    var attendance = data.recentAttendance || [];
    var attendanceHtml = '';
    if (attendance.length === 0) {
        attendanceHtml = '<div class="hrms-empty"><p class="hrms-empty-text">No attendance records yet</p></div>';
    } else {
        attendance.forEach(function(item) {
            var date = new Date(item.attendanceDate);
            var day = date.getDate();
            var month = date.toLocaleString('en', { month: 'short' });
            var checkIn = item.checkIn ? formatTime(item.checkIn) : '--:--';
            var checkOut = item.checkOut ? formatTime(item.checkOut) : '--:--';
            var hours = item.workingHours ? parseFloat(item.workingHours).toFixed(1) + ' hrs' : '';

            var statusClass = item.attendanceStatus || 'present';
            var lateTag = item.isLate == 1 ? ' <span class="hrms-status late">Late</span>' : '';

            attendanceHtml += `
                <div class="attendance-item">
                    <div class="attendance-date">
                        <div class="attendance-date-day">${day}</div>
                        <div class="attendance-date-month">${month}</div>
                    </div>
                    <div class="attendance-info">
                        <div class="attendance-times">
                            <span>${checkIn}</span>
                            <span class="separator">→</span>
                            <span>${checkOut}</span>
                            ${lateTag}
                        </div>
                        <div class="attendance-hours">${hours}</div>
                    </div>
                </div>
            `;
        });
    }
    $('#recentAttendance').html(attendanceHtml);

    // Recent Salary Slips
    var slips = data.recentSalarySlips || [];
    var slipsHtml = '';
    if (slips.length === 0) {
        slipsHtml = '<div class="hrms-empty"><p class="hrms-empty-text">No salary slips available</p></div>';
    } else {
        var months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        slips.forEach(function(slip) {
            var monthName = months[parseInt(slip.salaryMonth)] || slip.salaryMonth;
            var netSalary = parseFloat(slip.netSalary || 0).toLocaleString('en-IN');

            slipsHtml += `
                <div class="salary-item" onclick="window.location.href='${SITEURL}/hrms/salary/'">
                    <div class="salary-info">
                        <div class="salary-month-badge">
                            <div class="salary-month-text">${monthName}</div>
                            <div class="salary-year-text">${slip.salaryYear}</div>
                        </div>
                        <div class="salary-details">${monthName} ${slip.salaryYear} Salary</div>
                    </div>
                    <div class="salary-amount">₹${netSalary}</div>
                </div>
            `;
        });
    }
    $('#recentSalarySlips').html(slipsHtml);

    // Team Members (for managers)
    if (data.teamMembers) {
        $('#teamCount').text(data.teamCount || 0);
        var teamHtml = '';
        if (data.teamMembers.length === 0) {
            teamHtml = '<div class="hrms-empty" style="grid-column: 1/-1;"><p class="hrms-empty-text">No team members assigned</p></div>';
        } else {
            data.teamMembers.forEach(function(member) {
                var name = member.displayName || member.userName || 'Unknown';
                var initial = name.charAt(0).toUpperCase();
                teamHtml += `
                    <div class="team-member">
                        <div class="team-avatar">${initial}</div>
                        <div class="team-info">
                            <div class="team-name">${name}</div>
                            <div class="team-role">${member.designation || 'Employee'}</div>
                        </div>
                    </div>
                `;
            });
        }
        $('#teamMembers').html(teamHtml);
    }

    // Upcoming Birthdays
    if (data.upcomingBirthdays) {
        var birthdayHtml = '';
        if (data.upcomingBirthdays.length === 0) {
            birthdayHtml = '<div class="hrms-empty"><p class="hrms-empty-text">No upcoming birthdays</p></div>';
        } else {
            data.upcomingBirthdays.forEach(function(bday) {
                var name = bday.displayName || bday.userName || 'Unknown';
                var initial = name.charAt(0).toUpperCase();
                var isToday = bday.isToday;
                var itemClass = isToday ? 'birthday-item today' : 'birthday-item';

                birthdayHtml += `
                    <div class="${itemClass}">
                        <div class="birthday-avatar">${initial}</div>
                        <div class="birthday-info">
                            <div class="birthday-name">${name}</div>
                            <div class="birthday-dept">${bday.department || bday.designation || 'Employee'}</div>
                        </div>
                        <div class="birthday-date">
                            ${isToday ? '<span class="birthday-today-badge">Today!</span>' : `<div class="birthday-day">${bday.day}</div><div class="birthday-month">${bday.month}</div>`}
                        </div>
                    </div>
                `;
            });
        }
        $('#upcomingBirthdays').html(birthdayHtml);
    }
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
</script>
