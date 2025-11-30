<?php
// Clear PHP Opcache if enabled
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ Opcache cleared successfully!\n";
} else {
    echo "✗ Opcache is not enabled or opcache_reset() is not available.\n";
}

// Show opcache status
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "\nOpcache Status:\n";
    echo "- Enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    echo "- Used Memory: " . ($status['memory_usage']['used_memory'] / 1024 / 1024) . " MB\n";
} else {
    echo "Opcache status function not available.\n";
}
?>
