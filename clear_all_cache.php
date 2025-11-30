<?php
// Clear OPcache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache cleared\n";
}

// Clear APC cache if available
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "✓ APC cache cleared\n";
}

// Clear file stat cache
clearstatcache(true);
echo "✓ File stat cache cleared\n";

echo "\n✓ All caches cleared successfully!\n";
echo "\nBrowser cache: Users should hard refresh (Ctrl+F5 or Cmd+Shift+R)\n";
?>
