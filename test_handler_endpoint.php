<?php
/**
 * Simple test to verify OCR handler endpoint is responding
 * This script tests the basic connectivity to the handler
 */

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test OCR Handler Endpoint</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .test { margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9; }
        .test h3 { margin-top: 0; color: #555; }
        button { padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px 0; }
        button:hover { background: #1976d2; }
        .loading { color: #2196f3; font-weight: bold; }
        .success { color: #4caf50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 10px; margin: 10px 0; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>OCR Handler Endpoint Test</h1>

        <div class="info">
            <strong>This page tests if the OCR handler endpoint is responding.</strong><br>
            It sends a minimal request to the handler and shows you the raw response.
        </div>

        <!-- Test 1: Handler Reachability -->
        <div class="test">
            <h3>Test 1: Handler Endpoint Reachability</h3>
            <p>Tests if the handler file exists and responds to requests.</p>
            <button onclick="testReachability()">Test Handler Reachability</button>
            <div id="test1Result"></div>
        </div>

        <!-- Test 2: Handler with xAction Parameter -->
        <div class="test">
            <h3>Test 2: Handler with OCR Action</h3>
            <p>Tests if the handler recognizes the OCR action.</p>
            <button onclick="testOCRAction()">Test OCR Action</button>
            <div id="test2Result"></div>
        </div>

        <!-- Test 3: Handler with File Upload -->
        <div class="test">
            <h3>Test 3: Handler with File Upload</h3>
            <p>Upload a PDF to test the complete OCR flow.</p>
            <input type="file" id="testFile" accept=".pdf,.jpg,.jpeg,.png">
            <button onclick="testFileUpload()">Upload & Test</button>
            <div id="test3Result"></div>
        </div>

        <!-- Test 4: Check Logs Created -->
        <div class="test">
            <h3>Test 4: Check If Logs Were Created</h3>
            <p>Checks if the handler created any log files after testing.</p>
            <button onclick="checkLogs()">Check Logs</button>
            <div id="test4Result"></div>
        </div>

        <!-- Logs Viewer -->
        <div class="test">
            <h3>View Logs</h3>
            <button onclick="viewLog('/tmp/ocr_handler_start.log')">View Handler Start Log</button>
            <button onclick="viewLog('/tmp/ocr_handler.log')">View Handler Log</button>
            <button onclick="viewLog('/tmp/ocr_debug.log')">View Debug Log</button>
            <div id="logsResult"></div>
        </div>
    </div>

    <script>
        function showStatus(elementId, status, message, details = '') {
            var elem = document.getElementById(elementId);
            var statusClass = status === 'loading' ? 'loading' : (status === 'success' ? 'success' : 'error');
            var html = '<p class="' + statusClass + '">' + message + '</p>';
            if (details) {
                html += '<pre>' + escapeHtml(details) + '</pre>';
            }
            elem.innerHTML = html;
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function testReachability() {
            showStatus('test1Result', 'loading', 'Testing handler reachability...');

            fetch('/xadmin/mod/fuel-expense/x-fuel-expense.inc.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: ''
            })
            .then(r => {
                var contentType = r.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return r.json().then(data => ({ok: r.ok, data: data, isJson: true}));
                } else {
                    return r.text().then(text => ({ok: r.ok, data: text, isJson: false}));
                }
            })
            .then(result => {
                var details = 'Status: ' + (result.ok ? 'OK' : 'ERROR') + '\n';
                details += 'Content-Type: ' + (result.isJson ? 'JSON' : 'TEXT') + '\n\n';
                details += 'Response:\n' + JSON.stringify(result.data, null, 2);

                showStatus('test1Result', 'success', '✓ Handler is reachable', details);
            })
            .catch(err => {
                showStatus('test1Result', 'error', '✗ Handler not reachable: ' + err.message);
            });
        }

        function testOCRAction() {
            showStatus('test2Result', 'loading', 'Testing OCR action...');

            var formData = new FormData();
            formData.append('xAction', 'OCR');

            fetch('/xadmin/mod/fuel-expense/x-fuel-expense.inc.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                var details = JSON.stringify(data, null, 2);

                if (data.err === 0) {
                    showStatus('test2Result', 'success', '✓ Handler recognized OCR action', details);
                } else {
                    showStatus('test2Result', 'error', '✗ Handler returned error: ' + data.msg, details);
                }
            })
            .catch(err => {
                showStatus('test2Result', 'error', '✗ Error: ' + err.message);
            });
        }

        function testFileUpload() {
            var file = document.getElementById('testFile').files[0];
            if (!file) {
                alert('Please select a file');
                return;
            }

            showStatus('test3Result', 'loading', 'Uploading ' + file.name + '...');

            var formData = new FormData();
            formData.append('xAction', 'OCR');
            formData.append('billImage', file);

            fetch('/xadmin/mod/fuel-expense/x-fuel-expense.inc.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(text => {
                try {
                    var data = JSON.parse(text);
                    var details = 'Status: ' + (data.err === 0 ? 'SUCCESS' : 'FAILED') + '\n';
                    details += 'Message: ' + data.msg + '\n\n';
                    details += 'Full Response:\n' + JSON.stringify(data, null, 2);

                    if (data.err === 0) {
                        showStatus('test3Result', 'success', '✓ File upload and OCR successful', details);
                    } else {
                        showStatus('test3Result', 'error', '✗ OCR failed: ' + data.msg, details);
                    }
                } catch (e) {
                    showStatus('test3Result', 'error', '✗ Invalid JSON response: ' + e.message, 'Raw response:\n' + text.substring(0, 500));
                }
            })
            .catch(err => {
                showStatus('test3Result', 'error', '✗ Upload failed: ' + err.message);
            });
        }

        function checkLogs() {
            showStatus('test4Result', 'loading', 'Checking logs...');

            fetch('/get_ocr_logs.php?log=/tmp/ocr_handler_start.log')
            .then(r => r.json())
            .then(data => {
                var details = '';

                details += 'Handler Start Log (/tmp/ocr_handler_start.log):\n';
                details += '  Exists: ' + (data.exists ? 'YES' : 'NO') + '\n';
                details += '  Size: ' + data.size + ' bytes\n';
                details += '  Modified: ' + data.lastModified + '\n';

                if (data.exists && data.content) {
                    details += '  Content:\n' + data.content + '\n\n';
                } else {
                    details += '  (empty or not found)\n\n';
                }

                // Check other logs too
                return fetch('/get_ocr_logs.php?log=/tmp/ocr_handler.log').then(r => r.json()).then(handlerLog => {
                    details += 'Handler Function Log (/tmp/ocr_handler.log):\n';
                    details += '  Exists: ' + (handlerLog.exists ? 'YES' : 'NO') + '\n';
                    details += '  Size: ' + handlerLog.size + ' bytes\n';

                    return fetch('/get_ocr_logs.php?log=/tmp/ocr_debug.log').then(r => r.json()).then(debugLog => {
                        details += '\nOCR Debug Log (/tmp/ocr_debug.log):\n';
                        details += '  Exists: ' + (debugLog.exists ? 'YES' : 'NO') + '\n';
                        details += '  Size: ' + debugLog.size + ' bytes\n';

                        var status = (data.exists || handlerLog.exists || debugLog.exists) ? 'success' : 'error';
                        var msg = status === 'success' ? '✓ Logs found' : '✗ No logs found - handler may not be called';

                        showStatus('test4Result', status, msg, details);
                    });
                });
            })
            .catch(err => {
                showStatus('test4Result', 'error', '✗ Error: ' + err.message);
            });
        }

        function viewLog(logFile) {
            document.getElementById('logsResult').innerHTML = '<p class="loading">Loading ' + logFile + '...</p>';

            fetch('/get_ocr_logs.php?log=' + encodeURIComponent(logFile))
            .then(r => r.json())
            .then(data => {
                var html = '<h4>' + logFile + '</h4>';
                if (data.exists) {
                    html += '<p>Size: ' + data.size + ' bytes | Modified: ' + data.lastModified + '</p>';
                    html += '<pre>' + (data.content || '(empty)') + '</pre>';
                } else {
                    html += '<p class="error">Log file not found</p>';
                }
                document.getElementById('logsResult').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('logsResult').innerHTML = '<p class="error">Error: ' + err.message + '</p>';
            });
        }

        // Auto-check logs on page load
        window.addEventListener('load', function() {
            console.log('Page loaded, you can start testing');
        });
    </script>
</body>
</html>
