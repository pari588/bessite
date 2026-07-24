<?php
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

echo "=== camsUser TABLE ===\n";
$result = $db->query("SELECT * FROM camsUser ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "Total: " . $result->num_rows . " users\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | UserID: {$row['user_id']} | Name: {$row['first_name']} {$row['last_name']} | Type: {$row['type']}\n";
    }
} else {
    echo "No users found\n";
}

echo "\n=== camsPunch TABLE ===\n";
$result = $db->query("SELECT * FROM camsPunch ORDER BY punch_time DESC LIMIT 15");
if ($result && $result->num_rows > 0) {
    echo "Last 15 punches:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['punch_time']} | User: {$row['user_id']} | PunchType: {$row['punch_type']} | ActualType: {$row['actual_punch_type']}\n";
    }
} else {
    echo "No punches found\n";
}

echo "\n=== Employees with biometricID ===\n";
$result = $db->query("SELECT userID, displayName, biometricID FROM mx_x_admin_user WHERE biometricID IS NOT NULL AND biometricID != '' AND status = 1 ORDER BY biometricID");
if ($result && $result->num_rows > 0) {
    echo "Total: " . $result->num_rows . " employees with biometricID\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "UserID: {$row['userID']} | {$row['displayName']} | BiometricID: {$row['biometricID']}\n";
    }
} else {
    echo "No employees with biometricID found\n";
}

echo "\n=== mx_attendance Recent ===\n";
$result = $db->query("SELECT a.attendanceDate, u.displayName, a.checkIn, a.checkOut, a.source, a.attendanceStatus
FROM mx_attendance a
JOIN mx_x_admin_user u ON a.userID = u.userID
ORDER BY a.attendanceDate DESC, a.checkIn DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $in = $row['checkIn'] ?: 'N/A';
        $out = $row['checkOut'] ?: 'N/A';
        echo "{$row['attendanceDate']}: {$row['displayName']} | In: $in | Out: $out | {$row['source']} | {$row['attendanceStatus']}\n";
    }
}

echo "\nDone.\n";
?>
