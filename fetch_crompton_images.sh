#!/bin/bash

OUTPUT_DIR="/home/bombayengg/public_html/uploads/pump/crompton_images/"

echo "Attempting to fetch actual Crompton shallow well pump images..."
echo ""

# Try fetching with curl and proper headers
products=(
    "SWJ100AP-36 PLUS|swj100ap-36-plus"
    "SWJ100A-36 PLUS|swj100a-36-plus"
    "SWJ50AP-30 PLUS|swj50ap-30-plus"
    "SWJ50A-30 PLUS|swj50a-30-plus"
)

for product in "${products[@]}"; do
    IFS='|' read -r name slug <<< "$product"
    
    echo "Fetching: $name..."
    
    # Try to get image from Crompton website
    curl -s -L \
        -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" \
        -H "Accept: image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8" \
        -H "Referer: https://www.crompton.co.in/" \
        "https://www.crompton.co.in/cdn/shop/products/${slug}.jpg" \
        -o "${OUTPUT_DIR}${slug}_temp.jpg" 2>/dev/null
    
    # Check if file was downloaded
    if [ -f "${OUTPUT_DIR}${slug}_temp.jpg" ]; then
        SIZE=$(stat -f%z "${OUTPUT_DIR}${slug}_temp.jpg" 2>/dev/null || stat -c%s "${OUTPUT_DIR}${slug}_temp.jpg" 2>/dev/null)
        
        if [ "$SIZE" -gt 1000 ]; then
            echo "  ✓ Downloaded ($SIZE bytes)"
            mv "${OUTPUT_DIR}${slug}_temp.jpg" "${OUTPUT_DIR}${slug}.jpg"
        else
            echo "  ✗ Downloaded but too small (likely error page)"
            rm -f "${OUTPUT_DIR}${slug}_temp.jpg"
        fi
    else
        echo "  ✗ Failed to download"
    fi
done

echo ""
echo "Download complete!"
