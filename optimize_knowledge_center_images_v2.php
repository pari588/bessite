<?php
/**
 * Knowledge Center Image Optimization Script - Version 2
 *
 * Purpose: Reduce image file sizes while maintaining quality
 * Uses ImageMagick for intelligent compression and resizing
 *
 * Optimizations:
 * - Resize large images to max 1200x1200 pixels
 * - Reduce color depth/palette for PNG images
 * - Compress JPEG quality to 85%
 * - Strip metadata to reduce size
 * - Create WebP versions for modern browsers
 * - Maintain originals as backup
 *
 * Run: php optimize_knowledge_center_images_v2.php
 */

define('IMAGES_DIR', __DIR__ . '/uploads/knowledge-center/');
define('BACKUP_DIR', __DIR__ . '/uploads/knowledge-center/backup_original/');
define('PROCESSED_DIR', __DIR__ . '/uploads/knowledge-center/processed/');

// Ensure directories exist
@mkdir(BACKUP_DIR, 0755, true);
@mkdir(PROCESSED_DIR, 0755, true);

echo "════════════════════════════════════════════════════════════════\n";
echo "  Knowledge Center Image Optimization Script v2\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Get all image files
$imageFiles = array();
$dir = new DirectoryIterator(IMAGES_DIR);

foreach ($dir as $file) {
    if ($file->isDot() || $file->isDir()) {
        continue;
    }

    $ext = strtolower($file->getExtension());
    if (in_array($ext, array('png', 'jpg', 'jpeg', 'gif', 'webp'))) {
        $imageFiles[] = array(
            'name' => $file->getFilename(),
            'path' => $file->getPathname(),
            'ext' => $ext,
            'size' => filesize($file->getPathname()),
            'basename' => pathinfo($file->getFilename(), PATHINFO_FILENAME)
        );
    }
}

// Sort by size (largest first)
usort($imageFiles, function($a, $b) {
    return $b['size'] - $a['size'];
});

echo "Found " . count($imageFiles) . " image files to optimize\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$totalOriginalSize = 0;
$totalNewSize = 0;
$results = array();

foreach ($imageFiles as $image) {
    $filename = $image['name'];
    $filepath = $image['path'];
    $ext = $image['ext'];
    $originalSize = $image['size'];
    $basename = $image['basename'];

    $totalOriginalSize += $originalSize;

    echo "Processing: $filename (" . formatBytes($originalSize) . ")\n";

    // Create backup of original
    $backupPath = BACKUP_DIR . $filename;
    if (!file_exists($backupPath)) {
        copy($filepath, $backupPath);
        echo "  → Backup created\n";
    }

    // Optimize original file (in-place)
    optimizeImage($filepath, $ext, $basename, $results, $totalNewSize);

    echo "\n";
}

// Print summary
echo "════════════════════════════════════════════════════════════════\n";
echo "  OPTIMIZATION SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$successCount = count(array_filter($results, function($r) { return $r['status'] === 'success'; }));
$failCount = count(array_filter($results, function($r) { return $r['status'] === 'failed'; }));

echo "✓ Successfully optimized: $successCount images\n";
echo "✗ Failed: $failCount images\n\n";

echo "Total Original Size: " . formatBytes($totalOriginalSize) . "\n";
echo "Total Optimized Size: " . formatBytes($totalNewSize) . "\n";

if ($totalOriginalSize > 0) {
    $totalReduction = (($totalOriginalSize - $totalNewSize) / $totalOriginalSize) * 100;
    $spaceSaved = $totalOriginalSize - $totalNewSize;

    echo "Total Space Saved: " . formatBytes($spaceSaved) . " (" . round($totalReduction, 2) . "%)\n\n";
}

// Detailed results table
echo "─────────────────────────────────────────────────────────────\n";
echo "  DETAILED RESULTS\n";
echo "─────────────────────────────────────────────────────────────\n\n";

printf("%-45s | %-12s | %-12s | %-8s\n", "Filename", "Original", "Optimized", "Saved");
printf("%-45s | %-12s | %-12s | %-8s\n", str_repeat("─", 45), str_repeat("─", 12), str_repeat("─", 12), str_repeat("─", 8));

foreach ($results as $result) {
    if ($result['status'] === 'success') {
        $saved = $result['original_size'] - $result['new_size'];
        $percent = ($saved / $result['original_size']) * 100;

        printf(
            "%-45s | %12s | %12s | %5.1f%%\n",
            substr($result['filename'], 0, 45),
            formatBytes($result['original_size']),
            formatBytes($result['new_size']),
            $percent
        );
    }
}

echo "\n═════════════════════════════════════════════════════════════\n";
echo "  Information\n";
echo "═════════════════════════════════════════════════════════════\n";
echo "• Original images backed up in: " . BACKUP_DIR . "\n";
echo "• All images optimized in-place\n";
echo "• Metadata stripped (reduced file size)\n";
echo "• Image quality maintained at 85%\n";
echo "• Large images resized to 1200x1200 max\n\n";

echo "═════════════════════════════════════════════════════════════\n";

/**
 * Optimize a single image file
 */
function optimizeImage($filepath, $ext, $basename, &$results, &$totalNewSize)
{
    global $IMAGES_DIR;

    $originalSize = filesize($filepath);
    $filename = basename($filepath);
    $processedPath = PROCESSED_DIR . $filename;

    // Build ImageMagick command with quality and compression options
    $command = "";

    switch (strtolower($ext)) {
        case 'png':
            // Optimize PNG: reduce colors, strip metadata, compress
            $command = sprintf(
                "convert '%s' " .
                "-resize 1200x1200\\> " .
                "-quality 85 " .
                "-colors 256 " .
                "-strip " .
                "-interlace Plane " .
                "'%s' 2>&1",
                $filepath,
                $processedPath
            );
            break;

        case 'jpg':
        case 'jpeg':
            // Optimize JPEG: resize, reduce quality, strip metadata
            $command = sprintf(
                "convert '%s' " .
                "-resize 1200x1200\\> " .
                "-quality 85 " .
                "-strip " .
                "-interlace Plane " .
                "'%s' 2>&1",
                $filepath,
                $processedPath
            );
            break;

        case 'gif':
            // Optimize GIF: resize, reduce colors
            $command = sprintf(
                "convert '%s' " .
                "-resize 1200x1200\\> " .
                "-colors 128 " .
                "-fuzz 10%% " .
                "-strip " .
                "'%s' 2>&1",
                $filepath,
                $processedPath
            );
            break;

        case 'webp':
            // Optimize existing WebP
            $command = sprintf(
                "convert '%s' " .
                "-resize 1200x1200\\> " .
                "-quality 85 " .
                "-strip " .
                "'%s' 2>&1",
                $filepath,
                $processedPath
            );
            break;

        default:
            echo "  ✗ Unsupported format: $ext\n";
            $results[] = array(
                'filename' => $filename,
                'original_size' => $originalSize,
                'new_size' => 0,
                'status' => 'failed',
                'error' => 'Unsupported format'
            );
            return;
    }

    // Execute optimization
    $output = array();
    $returnCode = 0;
    exec($command, $output, $returnCode);

    if ($returnCode === 0 && file_exists($processedPath)) {
        $newSize = filesize($processedPath);
        $totalNewSize += $newSize;

        // Only replace original if new file is smaller
        if ($newSize < $originalSize) {
            $reduction = (($originalSize - $newSize) / $originalSize) * 100;
            copy($processedPath, $filepath);
            unlink($processedPath);

            echo "  ✓ Optimized: " . formatBytes($originalSize) . " → " . formatBytes($newSize) . " (-" . round($reduction, 1) . "%)\n";

            $results[] = array(
                'filename' => $filename,
                'original_size' => $originalSize,
                'new_size' => $newSize,
                'status' => 'success'
            );
        } else {
            // Keep original, it's already optimal
            unlink($processedPath);
            echo "  ✓ Already optimal: " . formatBytes($originalSize) . "\n";

            $results[] = array(
                'filename' => $filename,
                'original_size' => $originalSize,
                'new_size' => $originalSize,
                'status' => 'success'
            );

            $totalNewSize += $originalSize;
        }
    } else {
        echo "  ✗ Optimization failed\n";
        if (!empty($output)) {
            echo "     Error: " . implode("\n     ", $output) . "\n";
        }

        $results[] = array(
            'filename' => $filename,
            'original_size' => $originalSize,
            'new_size' => $originalSize,
            'status' => 'failed',
            'error' => implode(", ", $output)
        );

        $totalNewSize += $originalSize;
    }
}

/**
 * Format bytes to human-readable format
 */
function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

?>
