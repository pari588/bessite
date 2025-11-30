<?php
// Clear PHP OPcache
if (extension_loaded('Zend OPcache')) {
    opcache_reset();
    echo "OPcache cleared\n";
}

// Clear system cache
system('sync');
system('echo 3 > /proc/sys/vm/drop_caches 2>/dev/null || true');

echo "System cache cleared\n";
echo "Cache clearing complete\n";
?>
