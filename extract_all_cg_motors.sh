#!/bin/bash

OUTPUT_FILE="/home/bombayengg/public_html/CG_ALL_MOTORS_DATA.txt"
> "$OUTPUT_FILE"

echo "Extracting CG Global Motor Products from All Categories" >> "$OUTPUT_FILE"
echo "=========================================================" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Array of category URLs
declare -a CATEGORIES=(
    "High Voltage Motors|https://www.cgglobal.com/our_business/Industrial/high-low-voltage-ac-dc-motors/high-voltage-motors"
    "Low Voltage Motors|https://www.cgglobal.com/our_business/Industrial/high-low-voltage-ac-dc-motors/low-voltage-motors"
    "Energy Efficient Motors|https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Energy-Efficient-Motors"
    "Motors for Hazardous Area (LV)|https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Area-LV"
    "DC Motors|https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/DC-Motors"
    "Motors for Hazardous Areas (HV)|https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Areas-HV"
    "Special Application Motors|https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Special-Application-Motors"
)

for category_info in "${CATEGORIES[@]}"
do
    IFS='|' read -r category_name url <<< "$category_info"
    
    echo "Category: $category_name" >> "$OUTPUT_FILE"
    echo "URL: $url" >> "$OUTPUT_FILE"
    echo "---" >> "$OUTPUT_FILE"
    
    # Download the page
    echo "Downloading $category_name..."
    wget -q --no-check-certificate "$url" -O /tmp/cg_page.html 2>/dev/null
    
    if [ -f /tmp/cg_page.html ]; then
        # Extract product information
        # Look for product titles in various HTML patterns
        grep -oP '(?<=<h[2-4][^>]*>)[^<]+(?=</h[2-4]>)' /tmp/cg_page.html | head -20 >> "$OUTPUT_FILE"
        grep -oP '(?<=<title>)[^<]+' /tmp/cg_page.html >> "$OUTPUT_FILE"
        grep -oP '(?<=<div class="[^"]*product[^"]*"[^>]*>)[^<]*' /tmp/cg_page.html >> "$OUTPUT_FILE"
        grep -oP '<a[^>]*href=[^>]*>[^<]+</a>' /tmp/cg_page.html | sed 's/<[^>]*>//g' | sort -u | head -30 >> "$OUTPUT_FILE"
    fi
    
    echo "" >> "$OUTPUT_FILE"
    sleep 1
done

echo "Extraction complete: $OUTPUT_FILE"
