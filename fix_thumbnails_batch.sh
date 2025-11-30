#!/bin/bash

UPLOAD_PATH="/home/bombayengg/public_html/uploads/pump"
SIZES=(235 530)

echo "=== Fixing Pump Thumbnails ==="
echo ""

fixed=0
failed=0

# List of images to resize
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

    source_size=$(du -h "$source_file" | cut -f1)

    # Resize to each size
    for size in "${SIZES[@]}"; do
        thumb_path="$UPLOAD_PATH/${size}_${size}_crop_100/$img"
        thumb_dir=$(dirname "$thumb_path")

        # Create directory if it doesn't exist
        mkdir -p "$thumb_dir"

        # Use PHP-CLI to resize using GD library
        php -r "
            \$source = '$source_file';
            \$dest = '$thumb_path';
            \$size = $size;

            \$img = imagecreatefromwebp(\$source);
            if (\$img) {
                \$w = imagesx(\$img);
                \$h = imagesy(\$img);

                if (\$w > \$h) {
                    \$crop_size = \$h;
                    \$x = ($w - \$crop_size) / 2;
                    \$y = 0;
                } else {
                    \$crop_size = \$w;
                    \$x = 0;
                    \$y = ($h - \$crop_size) / 2;
                }

                \$cropped = imagecrop(\$img, array('x' => intval(\$x), 'y' => intval(\$y), 'width' => \$crop_size, 'height' => \$crop_size));
                if (\$cropped) {
                    \$thumb = imagecreatetruecolor(\$size, \$size);
                    imagecopyresampled(\$thumb, \$cropped, 0, 0, 0, 0, \$size, \$size, \$crop_size, \$crop_size);
                    imagewebp(\$thumb, \$dest, 85);
                    imagedestroy(\$img);
                    imagedestroy(\$cropped);
                    imagedestroy(\$thumb);
                    echo '✅';
                }
            }
        " 2>/dev/null

        if [ $? -eq 0 ]; then
            thumb_size=$(du -h "$thumb_path" 2>/dev/null | cut -f1)
            echo " $img → ${size}x${size}: $thumb_size"
            ((fixed++))
        else
            echo " ❌ Failed: $img → ${size}x${size}"
            ((failed++))
        fi
    done
done

echo ""
echo "=== Results ==="
echo "✅ Fixed: $fixed"
echo "❌ Failed: $failed"
echo ""
echo "✨ Done!"
