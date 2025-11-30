<?php
require_once("config.inc.php");
require_once("core/core.inc.php");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Pump Inquiry Diagnosis</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .error { color: red; }
        .success { color: green; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; max-width: 100%; }
    </style>
</head>
<body>
    <h1>Pump Inquiry Diagnosis</h1>

    <?php
    echo '<h2>1. Database Table Status</h2>';

    $DB->sql = "SHOW TABLES LIKE '%pump_inquiry%'";
    $DB->dbRows();

    echo '<p>Tables with "pump_inquiry" in name:</p>';
    if ($DB->numRows > 0) {
        echo '<ul>';
        foreach ($DB->rows as $row) {
            $tableName = array_values($row)[0];
            echo '<li>' . htmlspecialchars($tableName) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="error">No pump inquiry tables found!</p>';
    }

    // Check which table actually has data
    echo '<h2>2. Data Status in Each Table</h2>';

    $tables_to_check = ['bombay_pump_inquiry', 'mx_pump_inquiry', 'pump_inquiry'];
    foreach ($tables_to_check as $tbl) {
        $DB->sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tbl'";
        $DB->dbRow();

        if (isset($DB->row['cnt']) && $DB->row['cnt'] > 0) {
            $DB->sql = "SELECT COUNT(*) as cnt FROM `$tbl`";
            $DB->dbRow();
            $count = $DB->row['cnt'] ?? 0;
            echo '<p><strong>' . htmlspecialchars($tbl) . '</strong>: ' . $count . ' records</p>';
        }
    }

    // Check setModVars value
    echo '<h2>3. setModVars Configuration</h2>';
    $inc_file = "/home/bombayengg/public_html/xadmin/mod/pump-inquiry/x-pump-inquiry.inc.php";
    echo '<p>File: ' . htmlspecialchars($inc_file) . '</p>';

    if (file_exists($inc_file)) {
        $content = file_get_contents($inc_file);
        if (preg_match('/"TBL"\s*=>\s*"([^"]+)"/', $content, $matches)) {
            echo '<p class="success">TBL value: "' . htmlspecialchars($matches[1]) . '"</p>';
        }
        if (preg_match('/"PK"\s*=>\s*"([^"]+)"/', $content, $matches)) {
            echo '<p class="success">PK value: "' . htmlspecialchars($matches[1]) . '"</p>';
        }
    }

    // Check list page references
    echo '<h2>4. List Page Table References</h2>';
    $list_file = "/home/bombayengg/public_html/xadmin/mod/pump-inquiry/x-pump-inquiry-list.php";

    if (file_exists($list_file)) {
        $content = file_get_contents($list_file);
        preg_match_all('/FROM\s+`([^`]+)`/i', $content, $matches);

        if (!empty($matches[1])) {
            echo '<p>Tables referenced in list page:</p>';
            echo '<ul>';
            foreach (array_unique($matches[1]) as $tbl) {
                echo '<li>' . htmlspecialchars($tbl) . '</li>';
            }
            echo '</ul>';
        }
    }

    // Test a sample record retrieval
    echo '<h2>5. Sample Data Retrieval Test</h2>';

    // Determine which table to use
    $test_table = null;
    foreach (['mx_pump_inquiry', 'bombay_pump_inquiry'] as $tbl) {
        $DB->sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tbl'";
        $DB->dbRow();

        if (isset($DB->row['cnt']) && $DB->row['cnt'] > 0) {
            $test_table = $tbl;
            break;
        }
    }

    if ($test_table) {
        echo '<p>Using table: <strong>' . htmlspecialchars($test_table) . '</strong></p>';

        $DB->sql = "SELECT * FROM `$test_table` LIMIT 1";
        $DB->dbRow();

        if ($DB->numRows > 0) {
            echo '<p class="success">Sample record found:</p>';
            echo '<pre>';
            print_r($DB->row);
            echo '</pre>';
        } else {
            echo '<p>No records found in table</p>';
        }
    } else {
        echo '<p class="error">Could not find pump inquiry table</p>';
    }

    // Show database prefix
    echo '<h2>6. Database Configuration</h2>';
    echo '<p>Database Prefix ($DB->pre): <strong>' . htmlspecialchars($DB->pre) . '</strong></p>';
    ?>

</body>
</html>
