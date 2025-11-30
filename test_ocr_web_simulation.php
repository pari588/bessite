<!DOCTYPE html>
<html>
<head>
    <title>OCR Upload Tester</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 10px; margin: 10px 0; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 10px; margin: 10px 0; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 10px 0; }
        input[type="file"] { margin: 10px 0; }
        button { padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1976d2; }
        #result { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; min-height: 100px; background: #fafafa; white-space: pre-wrap; font-family: monospace; font-size: 12px; }
        #loader { display: none; color: #2196f3; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>OCR Upload Test</h1>

        <div class="info">
            <strong>ℹ️ This page simulates uploading a PDF through the web form.</strong><br>
            Upload a test PDF and watch the logs in real-time at:<br>
            <code style="background: #e0e0e0; padding: 2px 5px; border-radius: 3px;">/check_ocr_logs.php</code>
        </div>

        <form id="uploadForm">
            <label for="pdfFile">Select a PDF bill to test:</label><br>
            <input type="file" id="pdfFile" name="billImage" accept=".pdf,.PDF" required>
            <br>
            <button type="submit">Upload & Test OCR</button>
        </form>

        <div id="loader">
            ⏳ Processing... This may take 2-3 seconds...
        </div>

        <div id="result" style="display: none;">
        </div>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('pdfFile');
            const resultDiv = document.getElementById('result');
            const loaderDiv = document.getElementById('loader');
            const file = fileInput.files[0];

            if (!file) {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '<div class="error">❌ Please select a file first</div>';
                return;
            }

            // Show loader
            loaderDiv.style.display = 'block';
            resultDiv.style.display = 'none';

            // Create FormData
            const formData = new FormData();
            formData.append('xAction', 'OCR');
            formData.append('billImage', file);

            try {
                console.log('Uploading:', file.name);
                const response = await fetch('/xadmin/mod/fuel-expense/x-fuel-expense.inc.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('Response:', data);

                loaderDiv.style.display = 'none';
                resultDiv.style.display = 'block';

                if (data.err === 0) {
                    resultDiv.innerHTML = `<div class="success">
✅ OCR SUCCESS

Date: ${data.data.date}
Amount: ${data.data.amount}
Confidence: ${data.data.overallConfidence}%

Message: ${data.msg}

<strong>Next Steps:</strong>
- Check /tmp/ocr_debug.log for detailed processing logs
- The OCR should have extracted the date and amount automatically
</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="error">
❌ OCR FAILED

Error: ${data.msg}

<strong>Debugging Steps:</strong>
1. Open http://your-domain/check_ocr_logs.php in another tab
2. The log should show exactly where the failure occurred
3. Check /tmp/ocr_handler.log for web handler logs
4. Check /tmp/ocr_debug.log for OCR function logs

Debug Info:
${JSON.stringify(data.debug || {}, null, 2)}
</div>`;
                }
            } catch (error) {
                loaderDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `<div class="error">
❌ REQUEST FAILED

Error: ${error.message}

This means the request didn't even reach the OCR handler.
Check browser console (F12) for more details.
</div>`;
                console.error('Upload error:', error);
            }
        });
    </script>
</body>
</html>
