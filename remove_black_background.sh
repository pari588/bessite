#!/bin/bash

# Remove black background from DMB-CMB pump images using ImageMagick
# Convert to transparent background and save as WebP

UPLOAD_DIR="/home/bombayengg/public_html/uploads/pump"

echo "=== Removing Black Background from DMB-CMB Images ==="
echo ""

# Array of image files
images=("cmb10nv-plus.webp" "dmb10d-plus.webp" "dmb10dcsl.webp" "cmb05nv-plus.webp")

for image in "${images[@]}"; do
    input_file="$UPLOAD_DIR/$image"
    temp_file="/tmp/${image%.webp}_temp.png"

    echo "Processing: $image"

    if [ -f "$input_file" ]; then
        # Convert WebP to PNG with transparent background (remove black)
        # -background none: transparent background
        # -alpha on: enable alpha channel
        # -transparent black: convert black to transparent
        convert "$input_file" \
            -alpha on \
            -transparent black \
            -fuzz 5% \
            "$temp_file"

        # Convert back to WebP with quality 90
        convert "$temp_file" \
            -quality 90 \
            -define webp:lossless=false \
            "$input_file"

        if [ $? -eq 0 ]; then
            size=$(ls -lh "$input_file" | awk '{print $5}')
            echo "  ✓ Removed black background and optimized: $size"
        else
            echo "  ✗ Failed to process image"
        fi

        # Clean up temp file
        rm -f "$temp_file"
    else
        echo "  ✗ File not found: $input_file"
    fi

    echo ""
done

echo "=== Verification ==="
echo ""
for image in "${images[@]}"; do
    file="$UPLOAD_DIR/$image"
    if [ -f "$file" ]; then
        size=$(ls -lh "$file" | awk '{print $5}')
        echo "✓ $image ($size)"
    fi
done
