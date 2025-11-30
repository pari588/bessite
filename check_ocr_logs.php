<!DOCTYPE html>
<html>
<head>
    <title>OCR Debug Logs</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #4ec9b0; }
        .log-box { background: #252526; border: 1px solid #404040; padding: 15px; margin: 20px 0; border-radius: 4px; max-height: 600px; overflow-y: auto; }
        .error { color: #f48771; }
        .success { color: #6a9955; }
        .info { color: #569cd6; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; background: #0e639c; color: white; border: none; border-radius: 3px; }
        button:hover { background: #1177bb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 OCR Debug Logs</h1>

        <div>
            <button onclick="location.reload()">🔄 Refresh</button>
            <button onclick="clearLogs()">🗑️ Clear Logs</button>
        </div>

        <h2>Live Debug Log</h2>
        <div class="log-box" id="logContent">
            Loading...
        </div>

        <h2>File Information</h2>
        <div class="log-box" id="fileInfo">
            Loading...
        </div>
    </div>

    <script>
        function loadLogs() {
            fetch('get_ocr_logs.php')
                .then(resp => resp.json())
                .then(data => {
                    if (data.logs) {
                        let html = '<pre style="margin: 0;">';
                        data.logs.forEach(line => {
                            if (line.includes('ERROR') || line.includes('failed') || line.includes('Failed')) {
                                html += '<span class="error">' + escapeHtml(line) + '</span>\n';
                            } else if (line.includes('success') || line.includes('succeeded') || line.includes('YES')) {
                                html += '<span class="success">' + escapeHtml(line) + '</span>\n';
                            } else {
                                html += escapeHtml(line) + '\n';
                            }
                        });
                        html += '</pre>';
                        document.getElementById('logContent').innerHTML = html;
                    } else {
                        document.getElementById('logContent').innerHTML = '<span class="info">No logs yet. Try uploading a PDF.</span>';
                    }

                    if (data.fileInfo) {
                        let info = '<pre style="margin: 0;">';
                        for (let key in data.fileInfo) {
                            info += key + ': ' + escapeHtml(JSON.stringify(data.fileInfo[key])) + '\n';
                        }
                        info += '</pre>';
                        document.getElementById('fileInfo').innerHTML = info;
                    }
                });
        }

        function clearLogs() {
            if (confirm('Clear all logs?')) {
                fetch('get_ocr_logs.php?action=clear')
                    .then(() => loadLogs());
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        loadLogs();
        setInterval(loadLogs, 2000); // Refresh every 2 seconds
    </script>
</body>
</html>
