<?php
// Map of pump titles to their Crompton store URLs
$pump_urls = array(
    'Mini Everest Mini Pump' => 'https://www.crompton.co.in/products/mini-everest-mini-pump',
    'AQUAGOLD DURA 150' => 'https://www.crompton.co.in/products/aquagold-dura-150',
    'AQUAGOLD 150' => 'https://www.crompton.co.in/products/aquagold-150',
    'WIN PLUS I' => 'https://www.crompton.co.in/products/win-plus-i',
);

echo "FINDING REAL CROMPTON IMAGE URLS\n";
echo "=================================\n\n";

foreach ($pump_urls as $pump_name => $product_url) {
    echo "Product: $pump_name\n";
    echo "URL: $product_url\n";
    
    $html = @file_get_contents($product_url);
    
    if ($html) {
        // Look for image URLs
        preg_match_all('/"image":\s*"([^"]*)"/', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $img_url) {
                echo "  Image: $img_url\n";
            }
        } else {
            echo "  No images found in JSON\n";
        }
    } else {
        echo "  Failed to fetch product page\n";
    }
    
    echo "\n";
}
?>
