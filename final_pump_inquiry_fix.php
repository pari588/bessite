<?php
require_once("config.inc.php");
require_once("core/core.inc.php");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Pump Inquiry Final Fix</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Pump Inquiry Trash/Delete Fix - Final Step</h1>
    <p>This script will rename the database table from <strong>bombay_pump_inquiry</strong> to <strong><?php echo $DB->pre; ?>pump_inquiry</strong></p>

    <?php
    echo '<div class="step">';
    echo '<h2>Step 1: Renaming Table</h2>';

    // Check if old table exists
    $DB->sql = "SHOW TABLES LIKE 'bombay_pump_inquiry'";
    $DB->dbRows();

    if ($DB->numRows == 0) {
        echo '<p class="success">✓ Table bombay_pump_inquiry does not exist (already renamed or not present)</p>';
    } else {
        echo '<p>Found table bombay_pump_inquiry. Renaming...</p>';

        // Rename the table
        $newTableName = $DB->pre . "pump_inquiry";
        $DB->sql = "RENAME TABLE `bombay_pump_inquiry` TO `$newTableName`";

        echo '<pre>Executing: ' . htmlspecialchars($DB->sql) . '</pre>';

        if ($DB->dbQuery()) {
            echo '<p class="success">✓ Table renamed successfully!</p>';

            // Verify
            $DB->sql = "SHOW TABLES LIKE '$newTableName'";
            $DB->dbRows();

            if ($DB->numRows > 0) {
                echo '<p class="success">✓ Verified: Table ' . htmlspecialchars($newTableName) . ' exists</p>';
            } else {
                echo '<p class="error">✗ ERROR: Could not verify new table</p>';
            }
        } else {
            echo '<p class="error">✗ ERROR: ' . $DB->con->error . '</p>';
        }
    }
    echo '</div>';

    echo '<div class="step">';
    echo '<h2>Step 2: Verification</h2>';

    // Show current table status
    $DB->sql = "SHOW TABLES LIKE '%pump_inquiry%'";
    $DB->dbRows();

    echo '<p>Tables containing "pump_inquiry":</p>';
    echo '<ul>';
    foreach ($DB->rows as $row) {
        $tableName = array_values($row)[0];
        echo '<li>' . htmlspecialchars($tableName) . '</li>';
    }
    echo '</ul>';

    echo '</div>';

    echo '<div class="step">';
    echo '<h2>Step 3: Next Steps</h2>';
    echo '<p>The following files have already been updated:</p>';
    echo '<ul>';
    echo '<li>✓ /xadmin/mod/pump-inquiry/x-pump-inquiry.inc.php - Updated TBL setting</li>';
    echo '<li>✓ /xadmin/mod/pump-inquiry/x-pump-inquiry-list.php - Updated table references</li>';
    echo '<li>✓ /xsite/mod/pump-inquiry/x-pump-inquiry-inc.php - Updated table references</li>';
    echo '<li>✓ /xadmin/core-admin/ajax.inc.php - Cleaned up debug logging</li>';
    echo '</ul>';
    echo '<p><strong>You can now test the trash/delete functionality in the admin panel!</strong></p>';
    echo '</div>';

    ?>

</body>
</html>
