<?php
/**
 * OCR Debugging Tools Hub
 * Central location for all OCR diagnostic tools
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>OCR Handler Debugging Tools</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        h1 { font-size: 2.5em; margin-bottom: 10px; }
        .subtitle { font-size: 1.1em; opacity: 0.9; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .card p {
            color: #666;
            line-height: 1.6;
            flex-grow: 1;
            margin-bottom: 15px;
        }
        .card-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: opacity 0.3s ease;
        }
        .button:hover {
            opacity: 0.9;
        }
        .info-box {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .info-box h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .step {
            margin-bottom: 20px;
            padding-left: 30px;
            position: relative;
        }
        .step::before {
            content: attr(data-step);
            position: absolute;
            left: 0;
            top: 0;
            background: #667eea;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9em;
        }
        .step strong { color: #333; }
        .code {
            background: #f5f5f5;
            border-left: 3px solid #667eea;
            padding: 10px 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔧 OCR Handler Debugging Tools</h1>
            <p class="subtitle">Comprehensive diagnostic tools for OCR processing issues</p>
        </header>

        <div class="grid">
            <!-- Live Log Checker -->
            <div class="card">
                <div class="card-icon">📊</div>
                <h2>Live Log Checker</h2>
                <p>Real-time monitoring of all OCR handler logs. Automatically refreshes every 5 seconds to show you logs as they're created.</p>
                <a href="check_handler_logs_now.php" class="button">Open Live Checker</a>
            </div>

            <!-- Handler Endpoint Tester -->
            <div class="card">
                <div class="card-icon">🧪</div>
                <h2>Endpoint Tester</h2>
                <p>Test the OCR handler endpoint with various scenarios. Verify connectivity, test file uploads, and check response codes.</p>
                <a href="test_handler_endpoint.php" class="button">Open Tester</a>
            </div>

            <!-- Diagnostic Dashboard -->
            <div class="card">
                <div class="card-icon">📈</div>
                <h2>Diagnostic Dashboard</h2>
                <p>Comprehensive system diagnostics including log status, system configuration, and detailed troubleshooting guide.</p>
                <a href="diagnose_ocr_handler.php" class="button">Open Dashboard</a>
            </div>
        </div>

        <div class="info-box">
            <h2>🚀 Quick Start Guide</h2>

            <div class="success">
                <strong>✅ What We Know:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>JavaScript is working correctly and handler is attached</li>
                    <li>PDF files are being uploaded successfully</li>
                    <li>Error message appears in browser: "✗ OCR: Tesseract processing failed"</li>
                    <li>But handler logs are NOT being created</li>
                </ul>
            </div>

            <div style="margin-top: 30px;">
                <p style="font-size: 1.1em; font-weight: 600; margin-bottom: 20px;">Follow these steps to debug:</p>

                <div class="step" data-step="1">
                    <strong>Open the Live Log Checker</strong>
                    <p>Click "Open Live Checker" above to view all OCR logs in real-time</p>
                </div>

                <div class="step" data-step="2">
                    <strong>Clear All Logs</strong>
                    <p>Click the red "🗑️ Clear All Logs" button to start fresh</p>
                </div>

                <div class="step" data-step="3">
                    <strong>Upload a Test PDF</strong>
                    <p>Go to Admin → Fuel Expenses → Add New → Upload a PDF file</p>
                </div>

                <div class="step" data-step="4">
                    <strong>Watch the Logs Update</strong>
                    <p>The log checker auto-refreshes - watch for log files to appear</p>
                </div>

                <div class="step" data-step="5">
                    <strong>Interpret the Results</strong>
                    <p>
                        <strong>If logs appear:</strong> ✅ Handler IS being called - check log content<br>
                        <strong>If no logs appear:</strong> ❌ Handler NOT being called - investigate network request
                    </p>
                </div>
            </div>

            <div class="warning" style="margin-top: 30px;">
                <strong>⚠️ Important:</strong>
                If you see the ultra-early log file (/tmp/ocr_handler_entry.log) appear, it means the handler file is definitely being executed.
                This will help us narrow down exactly where the process is failing.
            </div>
        </div>

        <div class="info-box" style="margin-top: 30px;">
            <h2>📋 Command Line Tools</h2>
            <p>For advanced users, you can also check logs directly from the terminal:</p>

            <p style="margin-top: 20px;"><strong>Clear all logs:</strong></p>
            <div class="code">rm -f /tmp/ocr_handler_entry.log /tmp/ocr_handler_start.log /tmp/ocr_handler.log /tmp/ocr_debug.log</div>

            <p style="margin-top: 20px;"><strong>View all logs:</strong></p>
            <div class="code">cat /tmp/ocr_handler_entry.log /tmp/ocr_handler_start.log /tmp/ocr_handler.log /tmp/ocr_debug.log</div>

            <p style="margin-top: 20px;"><strong>Watch logs in real-time:</strong></p>
            <div class="code">tail -f /tmp/ocr_handler_entry.log</div>
        </div>

        <div class="info-box" style="margin-top: 30px;">
            <h2>📚 Log Files Explained</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #eee;">
                    <th style="text-align: left; padding: 10px; color: #667eea;">Log File</th>
                    <th style="text-align: left; padding: 10px; color: #667eea;">Purpose</th>
                    <th style="text-align: left; padding: 10px; color: #667eea;">Indicates</th>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><code>/tmp/ocr_handler_entry.log</code></td>
                    <td style="padding: 10px;">Handler file execution</td>
                    <td style="padding: 10px;">✅ Handler is being loaded</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><code>/tmp/ocr_handler_start.log</code></td>
                    <td style="padding: 10px;">Handler block entry</td>
                    <td style="padding: 10px;">✅ xAction parameter received</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><code>/tmp/ocr_handler.log</code></td>
                    <td style="padding: 10px;">OCR function execution</td>
                    <td style="padding: 10px;">✅ processBillImageOCR() is running</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><code>/tmp/ocr_debug.log</code></td>
                    <td style="padding: 10px;">OCR core processing</td>
                    <td style="padding: 10px;">✅ Tesseract/pdftoppm details</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
