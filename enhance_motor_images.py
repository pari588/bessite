#!/usr/bin/env python3
"""
Motor Image Enhancement Script using nano-banana
- Removes backgrounds from images
- Enhances image quality
- Preserves original images
- Converts to WebP format for optimal quality
"""

import os
import sys
from pathlib import Path
from PIL import Image
import subprocess
from nano_banana import Banana

# Configuration
MOTOR_DIR = Path("/home/bombayengg/public_html/uploads/motor")
ENHANCED_DIR = Path("/home/bombayengg/public_html/uploads/motor/enhanced")
LOG_FILE = Path("/home/bombayengg/public_html/motor_enhancement.log")

# Image formats to process
VALID_FORMATS = {'.webp', '.jpg', '.jpeg', '.png'}

def log_message(message):
    """Log messages to file and console"""
    print(message)
    with open(LOG_FILE, 'a') as f:
        f.write(message + '\n')

def create_enhanced_directory():
    """Create directory for enhanced images"""
    ENHANCED_DIR.mkdir(exist_ok=True)
    log_message(f"Enhanced images directory ready: {ENHANCED_DIR}")

def process_image_with_nanobanana(input_path, output_path):
    """
    Process image with nano-banana for background removal and enhancement
    """
    try:
        log_message(f"Processing: {input_path.name}")

        # Initialize Banana client
        banana = Banana()

        # Read image
        with open(input_path, 'rb') as f:
            image_data = f.read()

        # Process with nano-banana for background removal
        # nano-banana API for background removal
        result = banana.call(
            "remove-background",
            {
                "image": image_data,
                "return_dict": True,
                "type": "auto"
            }
        )

        if result.get('success'):
            # Get the processed image
            output_image_data = result.get('image_data')

            # Open processed image for enhancement
            from io import BytesIO
            img = Image.open(BytesIO(output_image_data))

            # Enhance quality
            img = enhance_image_quality(img)

            # Convert to WebP with high quality
            img.save(output_path, 'WEBP', quality=90, method=6)
            log_message(f"✓ Successfully enhanced and saved: {output_path.name}")
            return True
        else:
            log_message(f"✗ Failed to process with nano-banana: {input_path.name}")
            return False

    except Exception as e:
        log_message(f"✗ Error processing {input_path.name}: {str(e)}")
        return False

def enhance_image_quality(img):
    """
    Enhance image quality using PIL
    """
    from PIL import ImageEnhance

    # Convert to RGB if necessary
    if img.mode in ('RGBA', 'LA', 'P'):
        background = Image.new('RGB', img.size, (255, 255, 255))
        background.paste(img, mask=img.split()[-1] if img.mode == 'RGBA' else None)
        img = background

    # Enhance contrast
    enhancer = ImageEnhance.Contrast(img)
    img = enhancer.enhance(1.2)

    # Enhance color saturation
    enhancer = ImageEnhance.Color(img)
    img = enhancer.enhance(1.1)

    # Enhance sharpness
    enhancer = ImageEnhance.Sharpness(img)
    img = enhancer.enhance(1.3)

    # Enhance brightness slightly
    enhancer = ImageEnhance.Brightness(img)
    img = enhancer.enhance(1.05)

    return img

def get_main_images():
    """Get only main images, excluding thumbnails and subdirectories"""
    images = []
    for ext in VALID_FORMATS:
        # Get files from motor root directory only
        for file in MOTOR_DIR.glob(f'*{ext}'):
            if file.is_file():
                # Skip subdirectories and tmp files
                if '235_235' not in str(file) and '530_530' not in str(file) and 'tmp' not in str(file):
                    images.append(file)
    return sorted(images)

def main():
    """Main enhancement process"""
    log_message("=" * 80)
    log_message(f"Motor Image Enhancement Started - {os.popen('date').read().strip()}")
    log_message("=" * 80)

    try:
        create_enhanced_directory()

        # Get main images only
        images = get_main_images()
        log_message(f"\nFound {len(images)} images to process\n")

        if not images:
            log_message("No images found to process!")
            return

        # Process images
        successful = 0
        failed = 0

        for idx, image_path in enumerate(images, 1):
            output_path = ENHANCED_DIR / f"{image_path.stem}_enhanced.webp"

            log_message(f"[{idx}/{len(images)}] Processing: {image_path.name}")

            if process_image_with_nanobanana(image_path, output_path):
                successful += 1
            else:
                failed += 1
                # Fallback: just convert to WebP with quality enhancement
                log_message(f"Attempting fallback enhancement for: {image_path.name}")
                try:
                    img = Image.open(image_path)
                    img = enhance_image_quality(img)
                    img.save(output_path, 'WEBP', quality=90, method=6)
                    log_message(f"✓ Fallback enhancement successful: {output_path.name}")
                    successful += 1
                except Exception as e:
                    log_message(f"✗ Fallback also failed: {str(e)}")
                    failed += 1

        log_message(f"\n{'=' * 80}")
        log_message(f"Enhancement Summary:")
        log_message(f"  Total images processed: {len(images)}")
        log_message(f"  Successful: {successful}")
        log_message(f"  Failed: {failed}")
        log_message(f"  Enhanced images location: {ENHANCED_DIR}")
        log_message(f"{'=' * 80}\n")

    except Exception as e:
        log_message(f"Fatal error: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()
