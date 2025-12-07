<?php
/**
 * Direct OCR Test - Simulates fuel expense module OCR call
 */

// Set up paths - file is in xsite, need to go up one level to public_html
define('ROOTPATH', dirname(dirname(__FILE__)));
define('UPLOADPATH', ROOTPATH . '/uploads');
define('COREDIR', ROOTPATH . '/core');

// Include core
require_once(ROOTPATH . '/config.inc.php');
require_once(COREDIR . '/ocr.inc.php');

// Check if file upload test is happening
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['testFile'])) {
    header('Content-Type: application/json');

    $uploadedFile = $_FILES['testFile'];

    // Validate file
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Upload error: ' . $uploadedFile['error']
        ]);
        exit;
    }

    // Get temp path
    $tempPath = $uploadedFile['tmp_name'];

    echo "<!-- Testing OCR with temp file: $tempPath -->\n";

    // Process with OCR
    $result = processBillOCR($tempPath);

    // Return JSON
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    exit;
}

// Otherwise show form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct OCR Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        input[type="file"] { margin: 10px 0; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .result { margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 3px; }
        pre { overflow-x: auto; background: white; padding: 10px; border-radius: 3px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Direct OCR Test</h1>

        <p>Upload a fuel bill (PDF, JPG, PNG) to test PaddleOCR extraction:</p>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="testFile" accept=".pdf,.jpg,.jpeg,.png" required>
            <button type="submit">Test OCR</button>
        </form>

        <div id="result"></div>
    </div>

    <script>
        // If page loaded with result, show it
        window.addEventListener('load', function() {
            // Check if there's a result in the HTML
            var html = document.documentElement.innerHTML;
            if (html.includes('status')) {
                // Extract JSON from HTML comments
                var match = html.match(/<!-- Testing OCR with temp file:.*?-->\n([\s\S]*)/);
                if (match && match[1]) {
                    try {
                        var data = JSON.parse(match[1]);
                        var resultDiv = document.getElementById('result');

                        if (data.status === 'success') {
                            resultDiv.innerHTML = '<div class="result"><div class="success"><strong>✓ SUCCESS</strong></div>' +
                                '<p><strong>Date:</strong> ' + data.extractedData.date + ' (' + data.extractedData.dateConfidence + '% confidence)</p>' +
                                '<p><strong>Amount:</strong> ' + data.extractedData.amount + ' (' + data.extractedData.amountConfidence + '% confidence)</p>' +
                                '<p><strong>Overall Confidence:</strong> ' + data.overallConfidence + '%</p>' +
                                '<p><strong>OCR Engine:</strong> ' + data.ocrEngine + '</p>' +
                                '<p><strong>Text Preview:</strong></p><pre>' + data.rawText.substring(0, 300) + '...</pre>' +
                                '</div>';
                        } else {
                            resultDiv.innerHTML = '<div class="result"><div class="error"><strong>✗ ERROR</strong></div>' +
                                '<p>' + data.message + '</p></div>';
                        }
                    } catch(e) {
                        console.error('Failed to parse result');
                    }
                }
            }
        });
    </script>
</body>
</html>
