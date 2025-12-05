<?php
/**
 * Get OCR Debug Logs
 * Supports both single log file and multiple log files
 */

header('Content-Type: application/json');

$tempDir = sys_get_temp_dir();
$action = $_GET['action'] ?? '';
$specificLog = $_GET['log'] ?? '';

// Handle clear action
if ($action === 'clear') {
    $logFiles = array(
        $tempDir . '/ocr_debug.log',
        $tempDir . '/ocr_handler.log',
        $tempDir . '/ocr_handler_start.log',
    );

    $cleared = array();
    foreach ($logFiles as $file) {
        if (@unlink($file)) {
            $cleared[] = $file;
        }
    }

    echo json_encode(['success' => true, 'msg' => 'Cleared ' . count($cleared) . ' log files', 'cleared' => $cleared]);
    exit;
}

// If specific log requested
if ($specificLog) {
    $logFile = $specificLog;
    $exists = file_exists($logFile);

    if ($exists && is_readable($logFile)) {
        $content = file_get_contents($logFile);
    } else {
        $content = '';
    }

    echo json_encode([
        'exists' => $exists,
        'content' => $content,
        'logFile' => $logFile,
        'size' => $exists ? filesize($logFile) : 0,
        'lastModified' => $exists ? date('Y-m-d H:i:s', filemtime($logFile)) : 'N/A',
    ]);
    exit;
}

// Default: Return all log files status
$logFile = $tempDir . '/ocr_debug.log';
$logs = array();
$fileInfo = array(
    'logFile' => $logFile,
    'fileExists' => file_exists($logFile),
    'fileSize' => file_exists($logFile) ? filesize($logFile) : 0,
    'isReadable' => file_exists($logFile) ? is_readable($logFile) : false,
    'lastModified' => file_exists($logFile) ? date('Y-m-d H:i:s', filemtime($logFile)) : 'N/A',
    'phpUser' => function_exists('posix_getuid') ? posix_getuid() : 'unknown',
    'tempDir' => $tempDir,
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
