<?php
/**
 * Get OCR Debug Logs
 */

header('Content-Type: application/json');

$logFile = sys_get_temp_dir() . '/ocr_debug.log';
$action = $_GET['action'] ?? '';

if ($action === 'clear') {
    @unlink($logFile);
    echo json_encode(['success' => true]);
    exit;
}

$logs = array();
$fileInfo = array(
    'logFile' => $logFile,
    'fileExists' => file_exists($logFile),
    'fileSize' => file_exists($logFile) ? filesize($logFile) : 0,
    'isReadable' => file_exists($logFile) ? is_readable($logFile) : false,
    'lastModified' => file_exists($logFile) ? date('Y-m-d H:i:s', filemtime($logFile)) : 'N/A',
    'phpUser' => function_exists('posix_getuid') ? posix_getuid() : 'unknown',
    'tempDir' => sys_get_temp_dir(),
);

if (file_exists($logFile) && is_readable($logFile)) {
    $content = file_get_contents($logFile);
    $logs = array_filter(array_map('trim', explode("\n", $content)));
    // Keep only last 100 lines
    $logs = array_slice($logs, -100);
}

echo json_encode([
    'logs' => $logs,
    'fileInfo' => $fileInfo
]);
?>
