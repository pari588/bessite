<?php
// Simple test to verify PHP can write logs
$logFile = sys_get_temp_dir() . '/test_php_write.log';
$result = @file_put_contents($logFile, "Test write at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

if ($result === false) {
    echo "FAILED to write to $logFile\n";
} else {
    echo "SUCCESS: Wrote " . $result . " bytes to $logFile\n";
    echo "File contents:\n";
    echo file_get_contents($logFile);
}
?>
