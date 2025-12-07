<?php
/**
 * OCR Handler Diagnostic Tool
 * Helps identify why OCR handler isn't being called or responding
 */

?>
<!DOCTYPE html>
<html>
<head>
    <title>OCR Handler Diagnostic</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .section { margin: 20px 0; }
        .log-box { background: #f0f0f0; border: 1px solid #ddd; padding: 10px; border-radius: 4px; white-space: pre-wrap; font-size: 12px; max-height: 300px; overflow-y: auto; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 10px; margin: 10px 0; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 10px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 10px 0; }
        button { padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #1976d2; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .status-ok { color: #4caf50; font-weight: bold; }
        .status-error { color: #f44336; font-weight: bold; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"], input[type="text"] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; max-width: 300px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>OCR Handler Diagnostic Tool</h1>

        <div class="info">
            <strong>This tool helps diagnose why OCR uploads are failing.</strong><br>
            It checks logs, handlers, and allows you to test the upload process.
        </div>

        <!-- Log Files Section -->
        <h2>1. Log Files Status</h2>
        <div class="section">
            <p>Checking for OCR-related log files:</p>
            <table>
                <tr>
                    <th>Log File</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th>Last Modified</th>
                </tr>
                <?php
                $logFiles = array(
                    '/tmp/ocr_handler_start.log' => 'Handler Start Log',
                    '/tmp/ocr_handler.log' => 'Handler Function Log',
                    '/tmp/ocr_debug.log' => 'OCR Core Function Log',
                    sys_get_temp_dir() . '/ocr_handler_start.log' => 'Handler Start (Temp Dir)',
                    sys_get_temp_dir() . '/ocr_handler.log' => 'Handler Function (Temp Dir)',
                    sys_get_temp_dir() . '/ocr_debug.log' => 'OCR Core (Temp Dir)',
                );

                foreach ($logFiles as $path => $label) {
                    $exists = file_exists($path);
                    $size = $exists ? filesize($path) : 0;
                    $time = $exists ? date('Y-m-d H:i:s', filemtime($path)) : 'N/A';
                    $status = $exists ? '<span class="status-ok">EXISTS</span>' : '<span class="status-error">MISSING</span>';
                    echo "<tr><td>$label<br><code>$path</code></td><td>$status</td><td>$size bytes</td><td>$time</td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- System Checks -->
        <h2>2. System Configuration</h2>
        <div class="section">
            <table>
                <tr>
                    <th>Check</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>Temp Directory</td>
                    <td><?php echo sys_get_temp_dir(); ?></td>
                </tr>
                <tr>
                    <td>Temp Directory Writable</td>
                    <td><?php echo is_writable(sys_get_temp_dir()) ? '<span class="status-ok">YES</span>' : '<span class="status-error">NO</span>'; ?></td>
                </tr>
                <tr>
                    <td>Upload Directory</td>
                    <td><?php echo defined('UPLOADPATH') ? UPLOADPATH : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>Fuel Expense Upload Dir</td>
                    <td><?php echo defined('FUEL_EXPENSE_UPLOAD_DIR') ? FUEL_EXPENSE_UPLOAD_DIR : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>Handler File Path</td>
                    <td><code>/xadmin/mod/fuel-expense/x-fuel-expense.inc.php</code></td>
                </tr>
                <tr>
                    <td>Handler File Exists</td>
                    <td><?php echo file_exists(__DIR__ . '/xadmin/mod/fuel-expense/x-fuel-expense.inc.php') ? '<span class="status-ok">YES</span>' : '<span class="status-error">NO</span>'; ?></td>
                </tr>
            </table>
        </div>

        <!-- Log Viewer -->
        <h2>3. View Recent Logs</h2>
        <div class="section">
            <div class="form-group">
                <label>Select log to view:</label>
                <select id="logSelect" onchange="viewLog(this.value)">
                    <option value="">-- Choose a log --</option>
                    <option value="/tmp/ocr_handler_start.log">Handler Start Log</option>
                    <option value="/tmp/ocr_handler.log">Handler Function Log</option>
                    <option value="/tmp/ocr_debug.log">OCR Core Function Log</option>
                </select>
            </div>
            <button onclick="viewLog('/tmp/ocr_handler_start.log')">View Handler Start Log</button>
            <button onclick="viewLog('/tmp/ocr_handler.log')">View Handler Log</button>
            <button onclick="viewLog('/tmp/ocr_debug.log')">View OCR Debug Log</button>
            <button onclick="clearAllLogs()">Clear All Logs</button>
            <div id="logContent" class="log-box" style="display:none; margin-top: 10px;"></div>
        </div>

        <!-- Test Upload -->
        <h2>4. Test OCR Upload</h2>
        <div class="section">
            <div class="info">
                <strong>Upload a test PDF or image.</strong><br>
                This will send the file to the OCR handler and show you the raw response.
            </div>
            <form id="testForm">
                <div class="form-group">
                    <label for="testFile">Select a PDF or image file:</label>
                    <input type="file" id="testFile" name="billImage" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <button type="button" onclick="testUpload()">Test Upload</button>
            </form>
            <div id="testResult" style="display:none; margin-top: 20px;"></div>
        </div>

        <!-- Instructions -->
        <h2>5. What to Do</h2>
        <div class="section">
            <ol>
                <li><strong>Check Log Files Status</strong> - See which logs exist</li>
                <li><strong>View Recent Logs</strong> - Click buttons to see log contents</li>
                <li><strong>Clear All Logs</strong> - Start fresh before testing</li>
                <li><strong>Test Upload</strong> - Upload a PDF and check what happens</li>
                <li><strong>Share Results</strong> - Copy all log contents and share with debugging</li>
            </ol>
        </div>
    </div>

    <script>
        function viewLog(logFile) {
            if (!logFile) return;

            fetch('/get_ocr_logs.php?log=' + encodeURIComponent(logFile))
                .then(r => r.json())
                .then(data => {
                    var content = document.getElementById('logContent');
                    if (data.exists) {
                        content.textContent = data.content || '(empty log)';
                        content.style.display = 'block';
                    } else {
                        content.innerHTML = '<span class="status-error">Log file not found: ' + logFile + '</span>';
                        content.style.display = 'block';
                    }
                })
                .catch(err => {
                    document.getElementById('logContent').innerHTML = '<span class="status-error">Error reading log: ' + err.message + '</span>';
                    document.getElementById('logContent').style.display = 'block';
                });
        }

        function clearAllLogs() {
            if (!confirm('Clear all OCR logs?')) return;

            fetch('/get_ocr_logs.php?action=clear', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    alert(data.msg);
                    document.getElementById('logContent').innerHTML = '<span class="status-ok">Logs cleared!</span>';
                })
                .catch(err => alert('Error: ' + err.message));
        }

        function testUpload() {
            var fileInput = document.getElementById('testFile');
            var file = fileInput.files[0];

            if (!file) {
                alert('Please select a file');
                return;
            }

            var formData = new FormData();
            formData.append('xAction', 'OCR');
            formData.append('billImage', file);

            var result = document.getElementById('testResult');
            result.innerHTML = '<div class="info">Uploading ' + file.name + '...</div>';
            result.style.display = 'block';

            fetch('/xadmin/mod/fuel-expense/x-fuel-expense.inc.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(text => {
                console.log('Raw response:', text);
                try {
                    var data = JSON.parse(text);
                    if (data.err === 0) {
                        result.innerHTML = '<div class="success"><strong>✓ OCR SUCCESS</strong><br><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                    } else {
                        result.innerHTML = '<div class="error"><strong>✗ OCR FAILED</strong><br>Error: ' + data.msg + '<br><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                    }
                } catch (e) {
                    result.innerHTML = '<div class="error"><strong>✗ INVALID JSON RESPONSE</strong><br>Parse Error: ' + e.message + '<br><br><strong>Raw Response:</strong><br><pre>' + text.substring(0, 500) + '</pre></div>';
                }
            })
            .catch(err => {
                result.innerHTML = '<div class="error"><strong>✗ REQUEST FAILED</strong><br>' + err.message + '</div>';
            });
        }
    </script>
</body>
</html>
