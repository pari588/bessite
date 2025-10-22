<?php
// WebP support verification script - place in your website root

// Check if server supports WebP conversion
$webpSupported = function_exists('imagewebp');
$gdInstalled = function_exists('imagecreatetruecolor');

// Check if browser supports WebP
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$browserSupport = strpos($acceptHeader, 'image/webp') !== false;

// Path to sample image
$sampleJpg = 'uploads/home/adobestock_115653070.jpeg'; // Adjust this to a real path on your server
$webpVersion = 'uploads/home/adobestock_115653070.jpeg.webp'; // WebP version that should exist

// Print header
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>WebP Support Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .good { color: green; font-weight: bold; }
        .bad { color: red; font-weight: bold; }
        .info { color: blue; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-section { background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>WebP Support Test</h1>';

// Server Support Tests
echo '<div class="test-section">
        <h2>1. Server Support</h2>';

echo '<p>GD Library Installed: <span class="' . ($gdInstalled ? 'good' : 'bad') . '">' . 
     ($gdInstalled ? 'YES' : 'NO') . '</span></p>';

echo '<p>WebP Function Available: <span class="' . ($webpSupported ? 'good' : 'bad') . '">' . 
     ($webpSupported ? 'YES' : 'NO') . '</span></p>';

if (!$gdInstalled) {
    echo '<p class="bad">GD Library is not installed. WebP conversion requires GD.</p>';
}

if (!$webpSupported) {
    echo '<p class="bad">WebP functions are not available. Your server may need PHP recompiled with WebP support.</p>';
}

echo '</div>';

// Browser Support Tests
echo '<div class="test-section">
        <h2>2. Browser Support</h2>';

echo '<p>Your browser ' . ($browserSupport ? '<span class="good">SUPPORTS</span>' : 
     '<span class="bad">DOES NOT SUPPORT</span>') . ' WebP via Accept header.</p>';

echo '<p>User Agent: <span class="info">' . htmlspecialchars($userAgent) . '</span></p>';

// WebP display test
echo '<div class="test-section">
        <h2>3. Image Display Test</h2>';

// Show current image serving logic
echo '<p>When the browser requests an image, this is what happens:</p>';
echo '<ul>';
if ($browserSupport && $webpSupported) {
    echo '<li class="good">Browser supports WebP and server can convert - WebP versions should be served</li>';
} elseif (!$browserSupport && $webpSupported) {
    echo '<li class="info">Browser does not support WebP, but server can convert - Original images will be served</li>';
} elseif ($browserSupport && !$webpSupported) {
    echo '<li class="bad">Browser supports WebP, but server cannot convert - Original images will be served</li>';
} else {
    echo '<li class="bad">Neither browser nor server supports WebP - Original images will be served</li>';
}
echo '</ul>';

// Test image display
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $sampleJpg)) {
    echo '<p>Testing with image: ' . htmlspecialchars($sampleJpg) . '</p>';
    
    // Check if WebP version exists
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $webpVersion)) {
        echo '<p class="good">WebP version exists and should be served to compatible browsers.</p>';
    } else {
        echo '<p class="bad">WebP version does not exist. Run the conversion script first.</p>';
    }
    
    // Show both versions for comparison
    echo '<div style="display: flex; gap: 20px;">';
    echo '<div><h3>Original Image</h3><img src="/' . htmlspecialchars($sampleJpg) . '" alt="Original"></div>';
    echo '<div><h3>Processed Image (WebP if supported)</h3>';
    echo '<picture><source srcset="/' . htmlspecialchars($webpVersion) . '" type="image/webp">';
    echo '<img src="/' . htmlspecialchars($sampleJpg) . '" alt="WebP version"></picture></div>';
    echo '</div>';
} else {
    echo '<p class="bad">Sample image not found. Update the $sampleJpg path in this script.</p>';
}

echo '</div>';

// Summary
echo '<div class="test-section">
        <h2>Summary</h2>';

if ($gdInstalled && $webpSupported && $browserSupport) {
    echo '<p class="good">Your server can generate WebP images and your browser supports them. WebP images should be properly served.</p>';
} elseif ($gdInstalled && $webpSupported && !$browserSupport) {
    echo '<p class="info">Your server can generate WebP images but your current browser does not support them. Try with Chrome, Edge, Firefox, or a newer browser.</p>';
} elseif (!$webpSupported) {
    echo '<p class="bad">Your server does not support WebP conversion. Contact your hosting provider for assistance.</p>';
}

echo '</div>';

echo '</div></body></html>';