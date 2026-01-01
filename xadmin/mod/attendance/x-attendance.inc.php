<?php
/**
 * Attendance Module Controller
 * Handles biometric attendance data and employee remarks
 */

// Add attendance record (manual entry or biometric sync)
function addAttendance()
{
    global $DB;

    $userID = intval($_POST["userID"]);
    $attendanceDate = cleanTitle($_POST["attendanceDate"]);

    // Check for duplicate
    $DB->vals = array($userID, $attendanceDate, 1);
    $DB->types = "isi";
    $DB->sql = "SELECT attendanceID FROM " . $DB->pre . "attendance WHERE userID=? AND attendanceDate=? AND status=?";
    $existing = $DB->dbRow();

    if ($existing) {
        setResponse(array("err" => 1, "msg" => "Attendance record already exists for this date"));
        return;
    }

    // Sanitize inputs
    $_POST["userID"] = $userID;
    $_POST["attendanceDate"] = $attendanceDate;
    if (isset($_POST["checkIn"])) $_POST["checkIn"] = cleanTitle($_POST["checkIn"]);
    if (isset($_POST["checkOut"])) $_POST["checkOut"] = cleanTitle($_POST["checkOut"]);
    if (isset($_POST["attendanceStatus"])) $_POST["attendanceStatus"] = cleanTitle($_POST["attendanceStatus"]);
    if (isset($_POST["remarks"])) $_POST["remarks"] = cleanTitle($_POST["remarks"]);
    $_POST["source"] = "manual";

    // Calculate working hours if both check-in and check-out exist
    if (!empty($_POST["checkIn"]) && !empty($_POST["checkOut"])) {
        $checkIn = strtotime($_POST["checkIn"]);
        $checkOut = strtotime($_POST["checkOut"]);
        if ($checkOut > $checkIn) {
            $_POST["workingHours"] = round(($checkOut - $checkIn) / 3600, 2);
        }
    }

    // Calculate late/early status
    calculateLateEarly($_POST);

    $DB->table = $DB->pre . "attendance";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $attendanceID = $DB->insertID;
        setResponse(array("err" => 0, "param" => "id=$attendanceID"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Update attendance record
function updateAttendance()
{
    global $DB;

    $attendanceID = intval($_POST["attendanceID"]);

    // Sanitize inputs
    if (isset($_POST["checkIn"])) $_POST["checkIn"] = cleanTitle($_POST["checkIn"]);
    if (isset($_POST["checkOut"])) $_POST["checkOut"] = cleanTitle($_POST["checkOut"]);
    if (isset($_POST["attendanceStatus"])) $_POST["attendanceStatus"] = cleanTitle($_POST["attendanceStatus"]);
    if (isset($_POST["remarks"])) $_POST["remarks"] = cleanTitle($_POST["remarks"]);

    // Calculate working hours if both check-in and check-out exist
    if (!empty($_POST["checkIn"]) && !empty($_POST["checkOut"])) {
        $checkIn = strtotime($_POST["checkIn"]);
        $checkOut = strtotime($_POST["checkOut"]);
        if ($checkOut > $checkIn) {
            $_POST["workingHours"] = round(($checkOut - $checkIn) / 3600, 2);
        }
    }

    // Calculate late/early status
    calculateLateEarly($_POST);

    $DB->table = $DB->pre . "attendance";
    $DB->data = $_POST;
    if ($DB->dbUpdate("attendanceID=?", "i", array($attendanceID))) {
        setResponse(array("err" => 0, "param" => "id=$attendanceID"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Calculate late arrival and early checkout
function calculateLateEarly(&$data)
{
    global $DB;

    // Get HRMS settings
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT settingKey, settingValue FROM " . $DB->pre . "hrms_settings WHERE status=?";
    $settings = $DB->dbRows();

    $settingsArr = array();
    foreach ($settings as $s) {
        $settingsArr[$s['settingKey']] = $s['settingValue'];
    }

    $workStart = $settingsArr['work_start_time'] ?? '09:00';
    $workEnd = $settingsArr['work_end_time'] ?? '18:00';
    $lateGrace = intval($settingsArr['late_grace_minutes'] ?? 15);
    $earlyGrace = intval($settingsArr['early_checkout_grace_minutes'] ?? 15);

    $data['isLate'] = 0;
    $data['isEarlyCheckout'] = 0;
    $data['lateMinutes'] = 0;
    $data['earlyMinutes'] = 0;

    // Check late arrival
    if (!empty($data['checkIn'])) {
        $checkInTime = strtotime(date('H:i', strtotime($data['checkIn'])));
        $scheduledIn = strtotime($workStart);
        $graceEnd = $scheduledIn + ($lateGrace * 60);

        if ($checkInTime > $graceEnd) {
            $data['isLate'] = 1;
            $data['lateMinutes'] = round(($checkInTime - $scheduledIn) / 60);
        }
    }

    // Check early checkout
    if (!empty($data['checkOut'])) {
        $checkOutTime = strtotime(date('H:i', strtotime($data['checkOut'])));
        $scheduledOut = strtotime($workEnd);
        $graceStart = $scheduledOut - ($earlyGrace * 60);

        if ($checkOutTime < $graceStart) {
            $data['isEarlyCheckout'] = 1;
            $data['earlyMinutes'] = round(($scheduledOut - $checkOutTime) / 60);
        }
    }
}

// Add attendance remark (late reason, early checkout reason)
function addAttendanceRemark()
{
    global $DB;

    $attendanceID = intval($_POST["attendanceID"]);
    $userID = intval($_POST["userID"]);
    $remarkType = cleanTitle($_POST["remarkType"]);
    $reason = cleanTitle($_POST["reason"]);
    $submittedBy = $_SESSION[SITEURL]["MXID"];

    $DB->table = $DB->pre . "attendance_remarks";
    $DB->data = array(
        "attendanceID" => $attendanceID,
        "userID" => $userID,
        "remarkType" => $remarkType,
        "reason" => $reason,
        "submittedBy" => $submittedBy,
        "reviewStatus" => "pending"
    );

    if ($DB->dbInsert()) {
        $remarkID = $DB->insertID;
        setResponse(array("err" => 0, "alert" => "Remark submitted successfully", "param" => "remarkID=$remarkID"));
    } else {
        setResponse(array("err" => 1, "msg" => "Failed to add remark"));
    }
}

// Review attendance remark (manager approval)
function reviewAttendanceRemark()
{
    global $DB;

    $remarkID = intval($_POST["remarkID"]);
    $reviewStatus = cleanTitle($_POST["reviewStatus"]); // approved/rejected
    $reviewNote = cleanTitle($_POST["reviewNote"] ?? "");
    $reviewedBy = $_SESSION[SITEURL]["MXID"];

    $DB->table = $DB->pre . "attendance_remarks";
    $DB->data = array(
        "reviewedBy" => $reviewedBy,
        "reviewedAt" => date('Y-m-d H:i:s'),
        "reviewStatus" => $reviewStatus,
        "reviewNote" => $reviewNote
    );

    if ($DB->dbUpdate("remarkID=?", "i", array($remarkID))) {
        setResponse(array("err" => 0, "alert" => "Remark " . $reviewStatus . " successfully"));
    } else {
        setResponse(array("err" => 1, "msg" => "Failed to update remark"));
    }
}

// Get attendance summary for a user in a month
function getAttendanceSummary()
{
    global $DB;

    $userID = intval($_POST["userID"]);
    $month = intval($_POST["month"]);
    $year = intval($_POST["year"]);

    $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate));

    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT
                    COUNT(*) as totalRecords,
                    SUM(CASE WHEN attendanceStatus='present' THEN 1 ELSE 0 END) as presentDays,
                    SUM(CASE WHEN attendanceStatus='absent' THEN 1 ELSE 0 END) as absentDays,
                    SUM(CASE WHEN attendanceStatus='half_day' THEN 1 ELSE 0 END) as halfDays,
                    SUM(CASE WHEN attendanceStatus='leave' THEN 1 ELSE 0 END) as leaveDays,
                    SUM(CASE WHEN isLate=1 THEN 1 ELSE 0 END) as lateDays,
                    SUM(CASE WHEN isEarlyCheckout=1 THEN 1 ELSE 0 END) as earlyCheckoutDays,
                    SUM(workingHours) as totalHours
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate>=? AND attendanceDate<=? AND status=?";
    $summary = $DB->dbRow();

    return array("err" => 0, "data" => $summary);
}

// Get pending remarks for manager review
function getPendingRemarks()
{
    global $DB;

    $managerID = $_SESSION[SITEURL]["MXID"];

    // Get team members managed by this manager
    $DB->vals = array($managerID, 1, 'pending', 1);
    $DB->types = "iisi";
    $DB->sql = "SELECT AR.*, A.attendanceDate, U.displayName
                FROM " . $DB->pre . "attendance_remarks AR
                INNER JOIN " . $DB->pre . "attendance A ON AR.attendanceID = A.attendanceID
                INNER JOIN " . $DB->pre . "x_admin_user U ON AR.userID = U.userID
                WHERE U.managerID=? AND U.status=? AND AR.reviewStatus=? AND AR.status=?
                ORDER BY AR.submittedAt DESC";
    $remarks = $DB->dbRows();

    return array("err" => 0, "data" => $remarks, "count" => $DB->numRows);
}

// Sync attendance from biometric (called by camsunit callback)
function syncBiometricAttendance($data)
{
    global $DB;

    $biometricID = $data['biometricID'];
    $punchTime = $data['punchTime'];
    $punchDate = date('Y-m-d', strtotime($punchTime));
    $punchTimeOnly = date('Y-m-d H:i:s', strtotime($punchTime));

    // Find user by biometric ID
    $DB->vals = array($biometricID, 1);
    $DB->types = "si";
    $DB->sql = "SELECT userID FROM " . $DB->pre . "x_admin_user WHERE biometricID=? AND status=?";
    $user = $DB->dbRow();

    if (!$user) {
        return array("err" => 1, "msg" => "User not found for biometric ID: $biometricID");
    }

    $userID = $user['userID'];

    // Check if attendance record exists for today
    $DB->vals = array($userID, $punchDate, 1);
    $DB->types = "isi";
    $DB->sql = "SELECT * FROM " . $DB->pre . "attendance WHERE userID=? AND attendanceDate=? AND status=?";
    $existing = $DB->dbRow();

    if ($existing) {
        // Update checkout time
        $data = array(
            "checkOut" => $punchTimeOnly,
            "source" => "biometric",
            "syncedAt" => date('Y-m-d H:i:s')
        );

        // Calculate working hours
        $checkIn = strtotime($existing['checkIn']);
        $checkOut = strtotime($punchTimeOnly);
        if ($checkOut > $checkIn) {
            $data['workingHours'] = round(($checkOut - $checkIn) / 3600, 2);
        }

        calculateLateEarly($data);

        $DB->table = $DB->pre . "attendance";
        $DB->data = $data;
        $DB->dbUpdate("attendanceID=?", "i", array($existing['attendanceID']));

        return array("err" => 0, "msg" => "Checkout updated", "attendanceID" => $existing['attendanceID']);
    } else {
        // Create new attendance with check-in
        $data = array(
            "userID" => $userID,
            "attendanceDate" => $punchDate,
            "checkIn" => $punchTimeOnly,
            "attendanceStatus" => "present",
            "source" => "biometric",
            "syncedAt" => date('Y-m-d H:i:s')
        );

        calculateLateEarly($data);

        $DB->table = $DB->pre . "attendance";
        $DB->data = $data;
        if ($DB->dbInsert()) {
            return array("err" => 0, "msg" => "Check-in recorded", "attendanceID" => $DB->insertID);
        }
    }

    return array("err" => 1, "msg" => "Failed to sync attendance");
}

// Router
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addAttendance();
                break;
            case "UPDATE":
                updateAttendance();
                break;
            case "addRemark":
                addAttendanceRemark();
                break;
            case "reviewRemark":
                reviewAttendanceRemark();
                break;
            case "getSummary":
                $MXRES = getAttendanceSummary();
                break;
            case "getPendingRemarks":
                $MXRES = getPendingRemarks();
                break;
            case "mxDelFile":
                $param = array("dir" => "attendance", "tbl" => "attendance", "pk" => "attendanceID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "attendance", "PK" => "attendanceID", "UDIR" => array()));
}
