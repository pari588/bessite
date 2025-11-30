#!/bin/bash

# Create output file
OUTPUT_FILE="/home/bombayengg/public_html/CG_ALL_MOTORS_EXTRACTED.txt"
> "$OUTPUT_FILE"

# Array of URLs to process
declare -a URLS=(
    "https://www.cgglobal.com/our_business/Industrial/high-low-voltage-ac-dc-motors/high-voltage-motors"
    "https://www.cgglobal.com/our_business/Industrial/high-low-voltage-ac-dc-motors/low-voltage-motors"
    "https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Energy-Efficient-Motors"
    "https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Area-LV"
    "https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/DC-Motors"
    "https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Areas-HV"
    "https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Special-Application-Motors"
)

echo "Extracting CG Global Motor Products..." | tee "$OUTPUT_FILE"
echo "======================================" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

for url in "${URLS[@]}"
do
    echo "Processing: $url" | tee -a "$OUTPUT_FILE"
    echo "---" >> "$OUTPUT_FILE"
    
    # Fetch the page
    curl -s -k "$url" > /tmp/cg_page.html 2>/dev/null
    
    # Extract product titles and descriptions
    grep -o '<h3[^>]*>[^<]*</h3>' /tmp/cg_page.html | sed 's/<[^>]*>//g' >> "$OUTPUT_FILE"
    grep -o '<p[^>]*>[^<]*</p>' /tmp/cg_page.html | sed 's/<[^>]*>//g' >> "$OUTPUT_FILE"
    
    echo "" >> "$OUTPUT_FILE"
done

echo "Extraction complete. Output saved to: $OUTPUT_FILE"
