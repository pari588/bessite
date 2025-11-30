<?php
echo "DOWNLOADING & PROCESSING CROMPTON PUMP IMAGES\n";
echo "=============================================\n\n";

$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

// Map of pump titles to their image URLs from Crompton website
$pump_images = array(
    'Mini Everest Mini Pump' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/mini-everest-mini-pump/mini-everest-mini-pump-500x500.png',
    'AQUAGOLD DURA 150' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/aquagold-dura-150/aquagold-dura-150-500x500.png',
    'AQUAGOLD 150' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/aquagold-150/aquagold-150-500x500.png',
    'WIN PLUS I' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/win-plus-i/win-plus-i-500x500.png',
    'ULTIMO II' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/ultimo-ii/ultimo-ii-500x500.png',
    'ULTIMO I' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/ultimo-i/ultimo-i-500x500.png',
    'STAR PLUS I' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/star-plus-i/star-plus-i-500x500.png',
    'STAR DURA I' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/star-dura-i/star-dura-i-500x500.png',
    'PRIMO I' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/mini-pumps/primo-i/primo-i-500x500.png',
    
    'CMB10NV PLUS' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/dmb-cmb-pumps/cmb10nv-plus/cmb10nv-plus-500x500.png',
    'DMB10D PLUS' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/dmb-cmb-pumps/dmb10d-plus/dmb10d-plus-500x500.png',
    'DMB10DCSL' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/dmb-cmb-pumps/dmb10dcsl/dmb10dcsl-500x500.png',
    'CMB05NV PLUS' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/dmb-cmb-pumps/cmb05nv-plus/cmb05nv-plus-500x500.png',
    
    'SWJ1' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/shallow-well-pumps/swj1/swj1-500x500.png',
    'SWJ100AT-36 PLUS' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/shallow-well-pumps/swj100at-36-plus/swj100at-36-plus-500x500.png',
    'SWJ50AT-30 PLUS' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/shallow-well-pumps/swj50at-30-plus/swj50at-30-plus-500x500.png',
    
    '3W12AP1D' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/3-inch-borewell-submersibles/3w12ap1d/3w12ap1d-500x500.png',
    '3W10AP1D' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/3-inch-borewell-submersibles/3w10ap1d/3w10ap1d-500x500.png',
    '3W10AK1A' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/3-inch-borewell-submersibles/3w10ak1a/3w10ak1a-500x500.png',
    
    '4W7BU1AU' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/4-inch-borewell-submersibles/4w7bu1au/4w7bu1au-500x500.png',
    '4W14BU2EU' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/4-inch-borewell-submersibles/4w14bu2eu/4w14bu2eu-500x500.png',
    '4W10BU1AU' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/4-inch-borewell-submersibles/4w10bu1au/4w10bu1au-500x500.png',
    
    'OWE12(1PH)Z-28' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/openwell-pumps/owe12-1ph-z-28/owe12-1ph-z-28-500x500.png',
    'OWE052(1PH)Z-21FS' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/openwell-pumps/owe052-1ph-z-21fs/owe052-1ph-z-21fs-500x500.png',
    
    'Mini Force I' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/booster-pumps/mini-force-i/mini-force-i-500x500.png',
    'CFMSMB5D1.00-V24' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/booster-pumps/cfmsmb5d1-00-v24/cfmsmb5d1-00-v24-500x500.png',
    
    'ARMOR1.5-DSU' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/control-panels/armor1-5-dsu/armor1-5-dsu-500x500.png',
    'ARMOR1.0-CQU' => 'https://www.crompton.co.in/content/dam/crompton/in/products/agriculture/control-panels/armor1-0-cqu/armor1-0-cqu-500x500.png',
);

$upload_dir = '/home/bombayengg/public_html/uploads/pump/';
$thumb_dir = $upload_dir . '235_235_crop_100/';

echo "Step 1: Downloading Images\n";
echo "===========================\n";
echo "Total pumps to process: " . count($pump_images) . "\n\n";

$downloaded = 0;
$failed = 0;

foreach ($pump_images as $pump_name => $image_url) {
    // Create filename from pump name
    $filename = sanitize_filename($pump_name) . '.webp';
    $thumb_filename = sanitize_filename($pump_name) . '.webp';
    
    $full_path = $upload_dir . 'temp_' . basename($image_url);
    
    echo "Downloading: $pump_name\n";
    echo "  URL: $image_url\n";
    
    // Download the image
    $image_data = @file_get_contents($image_url);
    
    if ($image_data === false) {
        echo "  ✗ Failed to download\n\n";
        $failed++;
        continue;
    }
    
    // Save temporary file
    if (!file_put_contents($full_path, $image_data)) {
        echo "  ✗ Failed to save temporary file\n\n";
        $failed++;
        continue;
    }
    
    echo "  ✓ Downloaded\n";
    $downloaded++;
    
    // We'll convert in next step
    echo "  Temp file: temp_" . basename($image_url) . "\n\n";
}

echo str_repeat("=", 50) . "\n";
echo "DOWNLOAD SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✓ Successfully downloaded: $downloaded\n";
echo "✗ Failed to download: $failed\n\n";

echo "Next: Run image conversion script to convert to WebP and resize\n";

function sanitize_filename($filename) {
    $filename = str_replace(' ', '-', strtolower($filename));
    $filename = preg_replace('/[^a-z0-9-]/', '', $filename);
    return $filename;
}

$conn->close();
?>
