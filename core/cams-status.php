<?php
/**
 * CAMS Status Check - Temporary diagnostic page
 */
header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

echo "=== CAMS CONFIGURATION ===\n";
echo "STGID: " . CAMS_STGID . "\n";
echo "AUTH_TOKEN: " . CAMS_AUTH_TOKEN . "\n";
echo "SECURITY_KEY: " . CAMS_SECURITY_KEY . "\n";
echo "TEST_MODE: " . (CAMS_TEST_MODE ? "ON" : "OFF") . "\n\n";

echo "=== camsDevice TABLE ===\n";
$result = $db->query("SELECT * FROM camsDevice");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Serial: {$row['serial_number']}\n";
        echo "Auth Token (DB): {$row['auth_token']}\n";
        echo "Label: {$row['label_name']}\n";
        echo "Status: {$row['status']}\n";
        echo "---\n";
    }
} else {
    echo "NO DEVICES REGISTERED!\n";
}

echo "\n=== TOKEN MATCH CHECK ===\n";
$result = $db->query("SELECT auth_token FROM camsDevice WHERE serial_number = '" . $db->real_escape_string(CAMS_STGID) . "'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $dbToken = $row['auth_token'];
    $configToken = CAMS_AUTH_TOKEN;
    echo "DB Token: $dbToken\n";
    echo "Config Token: $configToken\n";
    echo "Match: " . ($dbToken === $configToken ? "YES ✓" : "NO ✗ - MISMATCH!") . "\n";
} else {
    echo "Device not found in camsDevice table!\n";
}

echo "\n=== camsPunch TABLE ===\n";
$result = $db->query("SELECT COUNT(*) as cnt FROM camsPunch");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total punches: {$row['cnt']}\n";
}

$result = $db->query("SELECT * FROM camsPunch ORDER BY punch_time DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "\nLast 5 punches:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['punch_time']} - User: {$row['user_id']} - Type: {$row['punch_type']}\n";
    }
}

echo "\n=== EMPLOYEES WITH BIOMETRIC ID ===\n";
$result = $db->query("SELECT userID, displayName, biometricID, autoAttendance FROM mx_x_admin_user WHERE status = 1 AND biometricID IS NOT NULL AND biometricID != '' ORDER BY displayName");
if ($result && $result->num_rows > 0) {
    echo "Count: {$result->num_rows}\n\n";
    while ($row = $result->fetch_assoc()) {
        $auto = $row['autoAttendance'] ? ' [AUTO]' : '';
        echo "  #{$row['userID']}: {$row['displayName']} -> BiometricID: {$row['biometricID']}{$auto}\n";
    }
} else {
    echo "No employees with biometric ID assigned!\n";
}

echo "\n=== RECENT mx_attendance ===\n";
$result = $db->query("SELECT a.attendanceDate, u.displayName, a.checkIn, a.checkOut, a.source, a.attendanceStatus
FROM mx_attendance a
JOIN mx_x_admin_user u ON a.userID = u.userID
ORDER BY a.attendanceDate DESC, a.checkIn DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "{$row['attendanceDate']}: {$row['displayName']} | In: " . ($row['checkIn'] ?: 'N/A') . " | Out: " . ($row['checkOut'] ?: 'N/A') . " | {$row['source']} | {$row['attendanceStatus']}\n";
    }
} else {
    echo "No attendance records!\n";
}
?>
