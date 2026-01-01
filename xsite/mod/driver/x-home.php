<?php
if (!isset($_SESSION['DRIVER_LOGIN_OTP'])) {
    echo "<script>window.location.href = SITEURL+'/driver/login/'; </script>";
    exit;
}

$DB->vals = array(1, $_SESSION['USER_ID']);
$DB->types = "ii";
$DB->sql = "SELECT * FROM `" . $DB->pre . "user` WHERE status=? AND userID=?";
$userData = $DB->dbRow();

/*
 * DRIVER OVERTIME LOGIC:
 *
 * Mark In  = Early overtime (driver comes BEFORE 10 AM)
 *            - Creates a record with fromTime, calculates overtime from fromTime to 10 AM
 *
 * Mark Out = Late overtime (driver works PAST 8 PM)
 *            - Can work independently without Mark In
 *            - If no record exists for today, creates one with fromTime = 8 PM (standard end time)
 *            - Records toTime when driver marks out (even after midnight)
 *            - Calculates overtime from 8 PM to toTime
 *
 * Scenario 1: Driver comes early (7 AM) and leaves on time (8 PM)
 *             -> Mark In at 7 AM, Mark Out at 8 PM
 *             -> Overtime = 7 AM to 10 AM = 3 hours
 *
 * Scenario 2: Driver comes on time (10 AM) and leaves late (1 AM next day)
 *             -> Mark Out at 1 AM
 *             -> System creates record with fromTime = 8 PM, toTime = 1 AM
 *             -> Overtime = 8 PM to 1 AM = 5 hours + dinner + taxi allowance
 *
 * Scenario 3: Driver comes early (7 AM) and leaves late (11 PM)
 *             -> Mark In at 7 AM, Mark Out at 11 PM
 *             -> Overtime = (7 AM to 10 AM) + (8 PM to 11 PM) = 3 + 3 = 6 hours
 */

$currentHour = (int)date('H');
$currentTime = date('H:i');
$checkDate = date('Y-m-d');

// Get driver's shift timings from user settings
$driverShiftStart = isset($userData['userFromTime']) ? substr($userData['userFromTime'], 0, 5) : '10:00'; // e.g., "09:00" or "10:00"
$driverShiftEnd = isset($userData['userToTime']) ? substr($userData['userToTime'], 0, 5) : '20:00'; // e.g., "19:00" or "20:00"

// Check if today is driver's weekly off day
$todayDayOfWeek = date('N'); // 1=Monday, 7=Sunday
$DB->vals = array(1, $_SESSION['USER_ID'], $todayDayOfWeek);
$DB->types = "iii";
$DB->sql = "SELECT * FROM `" . $DB->pre . "user_off_days` WHERE status=? AND userID=? AND weekdayNo=?";
$DB->dbRow();
$isOffDay = ($DB->numRows > 0);

// Determine time windows based on driver's specific shift timings
$isBeforeMorningCutoff = ($currentHour < 6);  // Before 6 AM (still previous day's overtime window)
$isEarlyOvertimeWindow = ($currentHour >= 6 && $currentTime < $driverShiftStart);  // 6 AM to shift start (early overtime)
$isLateOvertimeWindow = ($currentTime >= $driverShiftEnd || $currentHour < 6);   // After shift end or before 6 AM (late overtime)

// Determine which date is relevant for overtime
// - Before 6 AM: Yesterday's shift might still be open
// - After 6 AM: Today's shift
$relevantDate = ($currentHour < 6) ? date('Y-m-d', strtotime('-1 day')) : $checkDate;

// Check for open shift ONLY for the relevant date (today or yesterday if before 6 AM)
// An "open shift" means the driver manually marked in (recordType=2) and hasn't marked out yet
// Cron-created records (recordType=1) with toTime=NULL are NOT considered open shifts
$DB->vals = array(1, $_SESSION['USER_ID'], $relevantDate);
$DB->types = "iis";
$DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=? AND userID=? AND dmDate=? AND (toTime IS NULL OR toTime = '' OR toTime = '0000-00-00 00:00:00') AND recordType = 2 ORDER BY driverManagementID DESC LIMIT 1";
$openShift = $DB->dbRow();
$hasOpenShift = ($DB->numRows > 0);

// Check if already completed overtime for the relevant date
$DB->vals = array(1, $_SESSION['USER_ID'], $relevantDate);
$DB->types = "iis";
$DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=? AND userID=? AND dmDate=? AND toTime IS NOT NULL AND toTime != '' AND toTime != '0000-00-00 00:00:00' ORDER BY driverManagementID DESC LIMIT 1";
$completedRecord = $DB->dbRow();
$hasCompletedRecord = ($DB->numRows > 0);

// Also check today's date specifically for Mark In logic
$DB->vals = array(1, $_SESSION['USER_ID'], $checkDate);
$DB->types = "iis";
$DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=? AND userID=? AND dmDate=? ORDER BY driverManagementID DESC LIMIT 1";
$todayRecord = $DB->dbRow();
$hasTodayRecord = ($DB->numRows > 0);

// Check if today's record is a cron-created one that can be updated with early check-in
$isCronRecordWithoutManualUpdate = ($hasTodayRecord && $todayRecord['recordType'] == 1 &&
    ($todayRecord['toTime'] == '' || $todayRecord['toTime'] == NULL || $todayRecord['toTime'] == '0000-00-00 00:00:00'));

// Determine what to show
$driverManagement = $hasOpenShift ? $openShift : ($hasCompletedRecord ? $completedRecord : $todayRecord);
$driverManagementID = intval($driverManagement['driverManagementID'] ?? 0);

// Status flags - only show "Marked In" card if there's an actually open shift for relevant date
$hasActiveOpenShift = $hasOpenShift;

if ($isOffDay) {
    // WEEKLY OFF DAY - Different logic
    // Show Mark In if: No record for today OR has open shift that needs marking out
    // Show Mark Out if: Has open shift (marked in but not out yet) OR it's past shift end time

    $showMarkIn = !$hasOpenShift && !$hasCompletedRecord && ($currentHour >= 6);
    $showMarkOut = $hasOpenShift || ($isLateOvertimeWindow && $hasTodayRecord && !$hasCompletedRecord);
} else {
    // NORMAL WORKING DAY
    // Show Mark Out button if:
    // 1. There's an open shift for the relevant date (marked in but not out yet), OR
    // 2. It's late overtime window AND no completed record exists for the relevant date
    $showMarkOut = $hasOpenShift || ($isLateOvertimeWindow && !$hasCompletedRecord);

    // Show Mark In button if:
    // 1. It's early overtime window (6 AM - shift start), AND
    // 2. Either no record exists for today, OR a cron-created record exists that can be updated, AND
    // 3. Not showing Mark Out
    $showMarkIn = !$showMarkOut && $isEarlyOvertimeWindow && (!$hasTodayRecord || $isCronRecordWithoutManualUpdate);
}

// For display - only show if there's an active open shift
$markInTime = $hasActiveOpenShift ? date('h:i A', strtotime($openShift['fromTime'])) : null;
$markInDate = $hasActiveOpenShift ? date('D, M d', strtotime($openShift['dmDate'])) : null;
?>

<script type="text/javascript" src="<?php echo $TPL->modUrl; ?>/js/x-driver.inc.js"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@300;400;500;600;700&display=swap');

:root {
    --primary: #1a1a1a;
    --accent: #157bba;
    --accent-dark: #0e5a8a;
    --accent-light: #e8f4fc;
    --success: #059669;
    --success-light: #ecfdf5;
    --surface: #f8f9fa;
    --surface-elevated: #ffffff;
    --text-primary: #1a1a1a;
    --text-secondary: #4b5563;
    --text-muted: #9ca3af;
    --border: #e5e7eb;
    --border-light: #f3f4f6;
}

.driver-home-wrapper * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.driver-home-wrapper {
    min-height: 100vh;
    min-height: 100dvh;
    max-height: 100vh;
    max-height: 100dvh;
    background: var(--surface);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

/* Header */
.driver-header {
    background: var(--surface-elevated);
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-light);
    position: relative;
    z-index: 100;
    flex-shrink: 0;
}

.header-logo {
    height: 28px;
    width: auto;
}

.header-date {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 500;
    color: var(--text-muted);
    letter-spacing: 0.5px;
}

/* Main Content */
.driver-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: fadeIn 0.5s ease-out;
    overflow: hidden;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Profile Card */
.profile-card {
    background: var(--surface-elevated);
    border-radius: 16px;
    padding: 20px;
    width: 100%;
    max-width: 320px;
    text-align: center;
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.05),
        0 10px 15px -3px rgba(0, 0, 0, 0.08);
    margin-bottom: 12px;
    position: relative;
    overflow: hidden;
}

/* Subtle accent line */
.profile-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--accent-dark));
}

.avatar-container {
    position: relative;
    display: inline-block;
    margin-bottom: 12px;
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--surface);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status indicator on avatar */
.status-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid var(--surface-elevated);
    background: var(--text-muted);
}

.status-dot.active {
    background: var(--success);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(5, 150, 105, 0); }
}

.driver-name {
    font-family: 'DM Serif Display', Georgia, serif;
    font-size: 20px;
    font-weight: 400;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.driver-location {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.driver-location svg {
    width: 12px;
    height: 12px;
    opacity: 0.6;
}

/* Time Display */
.time-display {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
}

.current-time {
    font-family: 'DM Serif Display', Georgia, serif;
    font-size: 32px;
    color: var(--text-primary);
    letter-spacing: -1px;
}

.current-date {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 2px;
}

/* Status Card */
.status-card {
    background: var(--surface-elevated);
    border-radius: 12px;
    padding: 12px 16px;
    width: 100%;
    max-width: 320px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.status-card.marked-in {
    background: var(--success-light);
    border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.status-label {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.status-value {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--success);
}

/* Action Button */
.action-card {
    width: 100%;
    max-width: 320px;
}

.driver-home-wrapper .btn1,
.action-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    width: 100% !important;
    padding: 16px 24px !important;
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%) !important;
    color: #fff !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px !important;
    text-decoration: none !important;
    border: none !important;
    border-radius: 14px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 14px rgba(21, 123, 186, 0.35) !important;
    position: relative !important;
    overflow: hidden !important;
}

.driver-home-wrapper .btn1:hover,
.action-btn:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 24px rgba(21, 123, 186, 0.4) !important;
}

.driver-home-wrapper .btn1:active,
.action-btn:active {
    transform: translateY(-1px) !important;
}

.driver-home-wrapper .btn1.mark-out,
.action-btn.mark-out {
    background: linear-gradient(135deg, var(--success) 0%, #047857 100%) !important;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35) !important;
}

.driver-home-wrapper .btn1.mark-out:hover,
.action-btn.mark-out:hover {
    box-shadow: 0 8px 24px rgba(5, 150, 105, 0.4) !important;
}

/* Disabled state for buttons */
.driver-home-wrapper .btn1.disabled,
.action-btn.disabled {
    background: #9ca3af !important;
    box-shadow: none !important;
    cursor: not-allowed !important;
    transform: none !important;
    pointer-events: none;
}

.driver-home-wrapper .btn1.disabled:hover,
.action-btn.disabled:hover {
    transform: none !important;
    box-shadow: none !important;
}

.action-btn svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.action-btn h4 {
    margin: 0 !important;
    font-size: inherit !important;
    font-weight: inherit !important;
}

/* Hindi text styling */
.hindi-text {
    font-size: 12px;
    margin-top: 2px;
    opacity: 0.9;
    display: block;
}

.btn-content {
    text-align: left;
}

/* Footer */
.driver-footer {
    padding: 12px 16px;
    text-align: center;
    flex-shrink: 0;
}

.footer-text {
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    color: var(--text-muted);
    letter-spacing: 0.5px;
}

/* Logout Button */
.logout-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 10px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text-secondary);
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}

.logout-btn:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #dc2626;
}

.logout-btn svg {
    width: 14px;
    height: 14px;
}

/* Error/Success message styling */
.driver-home-wrapper .e {
    text-align: center;
    margin-top: 16px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: var(--accent);
    min-height: 20px;
}

/* Responsive */
@media (max-width: 420px) {
    .driver-content {
        padding: 12px;
    }

    .profile-card {
        padding: 16px;
        border-radius: 14px;
    }

    .current-time {
        font-size: 28px;
    }

    .current-date {
        font-size: 10px;
    }

    .action-btn {
        padding: 14px 20px !important;
        font-size: 14px !important;
    }

    .avatar {
        width: 50px;
        height: 50px;
    }

    .driver-name {
        font-size: 18px;
    }

    .status-card {
        padding: 10px 14px;
    }
}
</style>

<div class="driver-home-wrapper">
    <!-- Header -->
    <header class="driver-header">
        <img src="<?php echo SITEURL; ?>/images/logo.png" alt="Bombay Engineering Syndicate" class="header-logo">
        <a href="javascript:void(0);" class="logout-btn" id="driver-logout">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Logout
        </a>
    </header>

    <!-- Main Content -->
    <main class="driver-content">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="avatar-container">
                <img src="<?php echo SITEURL; ?>/images/img_avatar.png" alt="<?php echo htmlspecialchars($userData['userName'], ENT_QUOTES, 'UTF-8'); ?>" class="avatar">
                <span class="status-dot <?php echo $isMarkedIn ? 'active' : ''; ?>"></span>
            </div>

            <h2 class="driver-name"><?php echo htmlspecialchars($userData['userName'], ENT_QUOTES, 'UTF-8'); ?></h2>

            <p class="driver-location">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <?php echo htmlspecialchars($userData['userCity'] ?? 'Mumbai', ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <div class="time-display">
                <div class="current-time" id="liveTime"><?php echo date("h:i A"); ?></div>
                <div class="current-date"><?php echo date("l, F d, Y"); ?></div>
            </div>
        </div>

        <!-- Status Card (if marked in earlier and shift is still open) -->
        <?php if ($hasActiveOpenShift): ?>
        <div class="status-card marked-in">
            <div class="status-row">
                <span class="status-label">Marked In</span>
                <span class="status-value"><?php echo $markInDate . ' - ' . $markInTime; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Button -->
        <div class="action-card">
            <?php if ($showMarkOut): ?>
                <a href="javascript:void(0);" class="btn1 action-btn mark-out" id="mark-out" rel="<?php echo $driverManagementID; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <div class="btn-content">
                        <h4>Mark Out</h4>
                        <span class="hindi-text">ओवर टाइम मार्क-आउट करे</span>
                    </div>
                </a>
            <?php elseif ($showMarkIn): ?>
                <a href="javascript:void(0);" class="btn1 action-btn" id="mark-in">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <div class="btn-content">
                        <h4>Mark In</h4>
                        <span class="hindi-text">ओवर टाइम मार्क-इन करे (सुबह 10 बजे से पहले)</span>
                    </div>
                </a>
            <?php else: ?>
                <div class="status-card">
                    <div class="status-row" style="justify-content: center;">
                        <span class="status-label" style="color: #6b7280;">
                            <?php if ($hasTodayRecord): ?>
                                आज का ओवरटाइम पूरा हो गया है
                            <?php else: ?>
                                ओवरटाइम विंडो बंद है (10 AM - 8 PM)
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="driver-footer">
        <p class="footer-text">Bombay Engineering Syndicate &copy; <?php echo date('Y'); ?></p>
    </footer>
</div>

<script type="text/javascript">
// Live clock update
function updateClock() {
    const now = new Date();
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const timeString = hours.toString().padStart(2, '0') + ':' + minutes + ' ' + ampm;
    document.getElementById('liveTime').textContent = timeString;
}

setInterval(updateClock, 1000);
updateClock();
</script>
