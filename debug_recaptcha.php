<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config
require_once 'config.inc.php';
require_once 'core/core.inc.php';

echo "<h2>reCAPTCHA Debug Information</h2>";
echo "<pre>";

// Test 1: Check constants
echo "=== CONSTANTS ===\n";
echo "ROOTPATH: " . (defined('ROOTPATH') ? ROOTPATH : 'NOT DEFINED') . "\n";
echo "SITEURL: " . (defined('SITEURL') ? SITEURL : 'NOT DEFINED') . "\n";
echo "\n";

// Test 2: Check library path
echo "=== LIBRARY PATH CHECK ===\n";
$paths = array(
    "ROOTPATH method" => ROOTPATH . "/lib/recaptcha/autoload.php",
    "DOCUMENT_ROOT method" => $_SERVER["DOCUMENT_ROOT"] . "/lib/recaptcha/autoload.php",
);

foreach ($paths as $method => $path) {
    $exists = file_exists($path);
    echo "$method:\n";
    echo "  Path: $path\n";
    echo "  Exists: " . ($exists ? "YES ✓" : "NO ✗") . "\n";
    echo "\n";
}

// Test 3: Try to load library
echo "=== LIBRARY LOAD TEST ===\n";
$libPath = ROOTPATH . "/lib/recaptcha/autoload.php";
if (file_exists($libPath)) {
    require_once($libPath);
    echo "✓ Library loaded successfully\n\n";

    // Test 4: Create instance
    echo "=== CREATE RECAPTCHA INSTANCE ===\n";
    $secret = '6LeVCf0rAAAAABhzHVTK76BApoP66LDaXdYaUBN-';
    try {
        $recaptcha = new \ReCaptcha\ReCaptcha($secret);
        echo "✓ ReCaptcha instance created successfully\n";
        echo "Instance type: " . get_class($recaptcha) . "\n\n";
    } catch (Exception $e) {
        echo "✗ Error creating instance: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "✗ Library file not found at: $libPath\n\n";
}

// Test 5: Simulate a token verification (without actual token)
echo "=== VERIFICATION TEST ===\n";
echo "To test verification with an actual token:\n";
echo "1. Go to: /contact-us/\n";
echo "2. Open browser F12 console\n";
echo "3. Submit form and check console output\n";
echo "4. Check server error logs for verification results\n";

echo "</pre>";
?>
