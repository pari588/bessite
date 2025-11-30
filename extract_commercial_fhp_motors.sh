#!/bin/bash

# Commercial/FHP Category URLs
declare -a URLS=(
    "https://www.cgglobal.com/our_business/Commercial/Commercial-FHP-Motors"
)

OUTPUT_FILE="/home/bombayengg/public_html/COMMERCIAL_FHP_MOTORS_EXTRACTED.txt"
> "$OUTPUT_FILE"

echo "Extracting Commercial/FHP Motor Products from CG Global" >> "$OUTPUT_FILE"
echo "========================================================" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

for url in "${URLS[@]}"
do
    echo "Downloading: $url" | tee -a "$OUTPUT_FILE"
    echo "---" >> "$OUTPUT_FILE"
    
    # Download the page
    wget -q --no-check-certificate "$url" -O /tmp/commercial_fhp.html 2>/dev/null
    
    if [ -f /tmp/commercial_fhp.html ]; then
        # Extract all text content
        cat /tmp/commercial_fhp.html | grep -o '<[^>]*>' -v | grep -v '^[[:space:]]*$' >> "$OUTPUT_FILE" 2>/dev/null
        
        # Also extract links and titles
        grep -o '<a[^>]*href="[^"]*"[^>]*>[^<]*</a>' /tmp/commercial_fhp.html | sed 's/<[^>]*>//g' | sort -u >> "$OUTPUT_FILE"
        
        echo "" >> "$OUTPUT_FILE"
    fi
done

echo "Raw extraction complete: $OUTPUT_FILE"
