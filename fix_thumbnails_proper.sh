#!/bin/bash

UPLOAD_PATH="/home/bombayengg/public_html/uploads/pump"
SIZES=(235 530)

echo "=== Fixing Pump Thumbnails (Proper Resize) ==="
echo ""

fixed=0

# List of images that need fixing
images=(
    "aquagold-50-30.webp"
    "champ-plus-ii.webp"
    "flomax-plus-ii.webp"
    "mini-marvel-ii.webp"
    "mini-master-ii.webp"
    "mini-masterplus-ii.webp"
    "swj100a-36-plus.webp"
    "swj100ap-36-plus.webp"
    "swj50a-30-plus.webp"
    "swj50ap-30-plus.webp"
)

for img in "${images[@]}"; do
    source_file="$UPLOAD_PATH/$img"

    if [ ! -f "$source_file" ]; then
        echo "⚠️  $img: Source file not found"
        continue
    fi

    # Resize to each size (maintain aspect ratio with white background)
    for size in "${SIZES[@]}"; do
        thumb_path="$UPLOAD_PATH/${size}_${size}_crop_100/$img"
        thumb_dir=$(dirname "$thumb_path")

        mkdir -p "$thumb_dir"

        # Use PHP to resize with white padding (not crop)
        php -r "
            \$source = '$source_file';
            \$dest = '$thumb_path';
            \$size = $size;

            \$img = imagecreatefromwebp(\$source);
            if (\$img) {
                \$w = imagesx(\$img);
                \$h = imagesy(\$img);

                // Create white canvas
                \$canvas = imagecreatetruecolor(\$size, \$size);
                \$white = imagecolorallocate(\$canvas, 255, 255, 255);
                imagefill(\$canvas, 0, 0, \$white);

                // Calculate scaling to fit image in canvas
                \$scale = min(\$size / \$w, \$size / \$h);
                \$new_w = intval(\$w * \$scale);
                \$new_h = intval(\$h * \$scale);

                // Center the image
                \$x = intval((\$size - \$new_w) / 2);
                \$y = intval((\$size - \$new_h) / 2);

                // Copy and resize image onto canvas
                imagecopyresampled(\$canvas, \$img, \$x, \$y, 0, 0, \$new_w, \$new_h, \$w, \$h);

                // Save as WebP
                imagewebp(\$canvas, \$dest, 85);
                imagedestroy(\$img);
                imagedestroy(\$canvas);
                echo '✅';
            } else {
                echo '❌';
            }
        " 2>/dev/null

        if [ $? -eq 0 ]; then
            echo " $img → ${size}x${size}"
            ((fixed++))
        else
            echo " ❌ Failed: $img → ${size}x${size}"
        fi
    done
done

echo ""
echo "=== Results ==="
echo "✅ Fixed: $fixed thumbnails"
echo "✨ Done!"
