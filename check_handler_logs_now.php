<?php
/**
 * Quick Log Checker
 * Shows current status of all OCR handler logs
 * Refresh this page after uploading to see logs update in real-time
 */

$tempDir = sys_get_temp_dir();
$logFiles = array(
    'Entry Log' => $tempDir . '/ocr_handler_entry.log',
    'Handler Start Log' => $tempDir . '/ocr_handler_start.log',
    'Handler Function Log' => $tempDir . '/ocr_handler.log',
    'OCR Debug Log' => $tempDir . '/ocr_debug.log',
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>OCR Handler Logs - Live Checker</title>
    <style>
        body {
            font-family: monospace;
            margin: 20px;
            background: #f5f5f5;
            line-height: 1.5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .log-card {
            border: 2px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #f9f9f9;
        }
        .log-card h3 { margin: 0 0 10px 0; }
        .status-missing { border-left: 4px solid #f44336; background: #ffebee; }
        .status-empty { border-left: 4px solid #ff9800; background: #fff3cd; }
        .status-ok { border-left: 4px solid #4caf50; background: #e8f5e9; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; margin: 5px 0; }
        .badge-missing { background: #f44336; color: white; }
        .badge-empty { background: #ff9800; color: white; }
        .badge-ok { background: #4caf50; color: white; }
        .log-content {
            background: #000;
            color: #0f0;
            padding: 10px;
            border-radius: 4px;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
            margin-top: 10px;
        }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .action-box { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 4px; }
        button { padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; font-weight: bold; }
        button:hover { background: #1976d2; }
        .summary { font-size: 16px; font-weight: bold; margin: 20px 0; }
        .timestamp { color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 OCR Handler Logs - Live Status</h1>

        <div class="info-box">
            <strong>ℹ️ How to Use This Page:</strong><br>
            1. Clear logs using the button below<br>
            2. Upload a PDF through the Fuel Expenses form<br>
            3. This page will auto-refresh or click "Refresh Now"<br>
            4. Watch the logs appear in real-time<br>
            5. Share the log contents for debugging
        </div>

        <div>
            <button onclick="location.reload()">🔄 Refresh Now</button>
            <button onclick="clearLogs()">🗑️ Clear All Logs</button>
            <span class="timestamp">Last updated: <?php echo date('Y-m-d H:i:s'); ?></span>
        </div>

        <div class="summary">
            Summary:
            <?php
            $totalLogs = 0;
            $existingLogs = 0;
            $emptyLogs = 0;

            foreach ($logFiles as $name => $path) {
                $totalLogs++;
                if (file_exists($path)) {
                    $existingLogs++;
                    $size = filesize($path);
                    if ($size == 0) {
                        $emptyLogs++;
                    }
                }
            }

            echo "$existingLogs out of $totalLogs logs exist";
            if ($existingLogs > 0) {
                echo " | " . $emptyLogs . " are empty";
            }
            ?>
        </div>

        <div class="status-grid">
            <?php
            foreach ($logFiles as $name => $path) {
                $exists = file_exists($path);
                $size = $exists ? filesize($path) : 0;
                $modified = $exists ? date('Y-m-d H:i:s', filemtime($path)) : 'N/A';

                if (!$exists) {
                    $statusClass = 'status-missing';
                    $badge = '<span class="badge badge-missing">❌ MISSING</span>';
                    $statusText = 'Log file does not exist';
                } elseif ($size == 0) {
                    $statusClass = 'status-empty';
                    $badge = '<span class="badge badge-empty">⚠️ EMPTY</span>';
                    $statusText = 'Log file exists but is empty';
                } else {
                    $statusClass = 'status-ok';
                    $badge = '<span class="badge badge-ok">✅ OK</span>';
                    $statusText = 'Log file exists and has content';
                }

                echo '<div class="log-card ' . $statusClass . '">';
                echo '<h3>' . htmlspecialchars($name) . '</h3>';
                echo $badge . '<br>';
                echo '<small>' . $statusText . '</small><br>';
                echo '<small>Path: ' . htmlspecialchars($path) . '</small><br>';
                echo '<small>Size: ' . $size . ' bytes</small><br>';
                echo '<small>Modified: ' . $modified . '</small>';

                if ($exists && $size > 0) {
                    $content = file_get_contents($path);
                    // Show last 20 lines
                    $lines = explode("\n", $content);
                    $lines = array_filter($lines);
                    $lines = array_slice($lines, -20);
                    $content = implode("\n", $lines);

                    echo '<div class="log-content">' . htmlspecialchars($content) . '</div>';
                }

                echo '</div>';
            }
            ?>
        </div>

        <div class="action-box">
            <strong>✅ Next Steps:</strong><br>
            1. If you see logs, handler IS being called - check the log content for errors<br>
            2. If no logs appear after upload, handler is NOT being called - check browser network tab<br>
            3. Share the log contents for detailed debugging
        </div>

        <div style="margin-top: 30px; padding: 15px; border: 2px dashed #999; border-radius: 4px;">
            <h3>Auto-Refresh Setup</h3>
            <p>Want this page to auto-refresh? Open your browser developer tools (F12) console and run:</p>
            <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 4px;">
setInterval(function() { location.reload(); }, 2000);
            </code>
            <p>This will refresh the page every 2 seconds so you can watch logs appear in real-time as you upload.</p>
        </div>
    </div>

    <script>
        function clearLogs() {
            if (!confirm('Clear all OCR logs?')) return;

            fetch('/get_ocr_logs.php?action=clear', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    alert(data.msg);
                    location.reload();
                })
                .catch(err => alert('Error: ' + err.message));
        }

        // Auto-refresh every 5 seconds
        setTimeout(function() {
            location.reload();
        }, 5000);
    </script>
</body>
</html>
