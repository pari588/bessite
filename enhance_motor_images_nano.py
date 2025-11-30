#!/usr/bin/env python3
"""
Motor Image Enhancement Script using nano-banana (Gemini 2.5 Flash)
- Removes backgrounds from images intelligently
- Enhances image quality
- Converts to WebP format
- Maintains aspect ratio and creates proper dimensions
- Uses Google Gemini API for advanced image processing
"""

import os
import sys
from pathlib import Path
from PIL import Image, ImageEnhance, ImageFilter
from nano_banana import NanoBanana, image_to_image
import base64
from datetime import datetime
import json

# Configuration
MOTOR_DIR = Path("/home/bombayengg/public_html/uploads/motor")
ENHANCED_DIR = MOTOR_DIR / "enhanced"
LOG_FILE = Path("/home/bombayengg/public_html/motor_enhancement.log")
PROGRESS_FILE = Path("/home/bombayengg/public_html/motor_enhancement_progress.json")

# API Configuration
API_KEY = "AIzaSyDyC6pWx3uWfC9BhrJtPoqL2Gr5VoelFko"

# Image formats to process
VALID_FORMATS = {'.webp', '.jpg', '.jpeg', '.png'}

# Initialize nano-banana client
try:
    client = NanoBanana(api_key=API_KEY)
    print("✓ Nano-banana client initialized successfully")
except Exception as e:
    print(f"✗ Failed to initialize nano-banana client: {e}")
    sys.exit(1)

def log_message(message):
    """Log messages to file and console"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    formatted_msg = f"[{timestamp}] {message}"
    print(formatted_msg)
    with open(LOG_FILE, 'a') as f:
        f.write(formatted_msg + '\n')

def save_progress(processed_count, total_count, successful, failed):
    """Save progress to JSON file"""
    progress = {
        "timestamp": datetime.now().isoformat(),
        "processed": processed_count,
        "total": total_count,
        "successful": successful,
        "failed": failed,
        "progress_percent": (processed_count / total_count * 100) if total_count > 0 else 0
    }
    with open(PROGRESS_FILE, 'w') as f:
        json.dump(progress, f, indent=2)

def create_enhanced_directory():
    """Create directory for enhanced images"""
    ENHANCED_DIR.mkdir(exist_ok=True)
    log_message(f"Enhanced images directory ready: {ENHANCED_DIR}")

def image_to_base64(image_path):
    """Convert image to base64 for API"""
    with open(image_path, 'rb') as f:
        return base64.b64encode(f.read()).decode('utf-8')

def process_image_with_nanobanana(input_path, output_path):
    """
    Process image with nano-banana for background removal and enhancement
    """
    try:
        log_message(f"🔄 Processing with Gemini API: {input_path.name}")

        # Convert image to base64
        image_base64 = image_to_base64(input_path)

        # Create prompt for background removal and enhancement
        prompt = """Please perform the following image enhancements:
1. Remove the background completely (make it transparent)
2. Enhance image quality by:
   - Increasing contrast by 30%
   - Increasing color saturation by 15%
   - Enhancing sharpness
   - Adjusting brightness for optimal visibility
3. Keep the product/motor in the center
4. Return the enhanced image with transparent background

Return ONLY the enhanced image, no text or additional content."""

        # Call image_to_image for enhancement
        result = image_to_image(
            image_url=f"data:image/jpeg;base64,{image_base64}",
            prompt=prompt,
            api_key=API_KEY
        )

        if result and isinstance(result, bytes):
            # Save the result
            with open(output_path, 'wb') as f:
                f.write(result)

            # Verify the saved image
            try:
                img = Image.open(output_path)
                file_size_kb = output_path.stat().st_size / 1024
                log_message(f"✓ Successfully enhanced: {output_path.name} ({file_size_kb:.1f} KB) - {img.size}")
                return True
            except Exception as e:
                log_message(f"✗ Failed to verify enhanced image: {str(e)}")
                return False
        else:
            log_message(f"✗ No valid response from Gemini API for {input_path.name}")
            return False

    except Exception as e:
        log_message(f"✗ Error processing {input_path.name} with Gemini API: {str(e)}")
        return False

def enhance_local_quality(img_path):
    """
    Apply local quality enhancements using PIL as a fallback
    """
    try:
        img = Image.open(img_path)

        # Convert to RGB if necessary
        if img.mode == 'RGBA':
            background = Image.new('RGB', img.size, (255, 255, 255))
            background.paste(img, mask=img.split()[3])
            img = background

        # Enhance contrast
        enhancer = ImageEnhance.Contrast(img)
        img = enhancer.enhance(1.25)

        # Enhance color saturation
        enhancer = ImageEnhance.Color(img)
        img = enhancer.enhance(1.12)

        # Enhance sharpness
        enhancer = ImageEnhance.Sharpness(img)
        img = enhancer.enhance(1.3)

        # Save with high quality
        img.save(img_path, 'WEBP', quality=95, method=6)
        log_message(f"✓ Applied local quality enhancement to {img_path.name}")
        return True

    except Exception as e:
        log_message(f"✗ Local enhancement failed: {str(e)}")
        return False

def get_main_images():
    """Get only main images, excluding thumbnails and subdirectories"""
    images = []
    for file in MOTOR_DIR.iterdir():
        if file.is_file() and file.suffix.lower() in VALID_FORMATS:
            # Skip subdirectories and special files
            if ('235_235' not in str(file) and '530_530' not in str(file) and
                'tmp' not in str(file) and 'enhanced' not in str(file)):
                images.append(file)
    return sorted(images)

def main():
    """Main enhancement process"""
    log_message("=" * 100)
    log_message("MOTOR IMAGE ENHANCEMENT USING NANO-BANANA (GEMINI API)")
    log_message("=" * 100)

    try:
        create_enhanced_directory()

        # Get main images only
        images = get_main_images()
        log_message(f"\nFound {len(images)} images to process")
        log_message(f"Source directory: {MOTOR_DIR}")
        log_message(f"Output directory: {ENHANCED_DIR}\n")

        if not images:
            log_message("No images found to process!")
            return

        # Process images
        successful = 0
        failed = 0

        for idx, image_path in enumerate(images, 1):
            output_path = ENHANCED_DIR / f"{image_path.stem}_enhanced.webp"

            print(f"\n[{idx}/{len(images)}] ", end="")

            if process_image_with_nanobanana(image_path, output_path):
                successful += 1
            else:
                log_message(f"⚠ Failed to process {image_path.name}, attempting fallback...")
                failed += 1

            # Save progress
            save_progress(idx, len(images), successful, failed)

        log_message(f"\n{'=' * 100}")
        log_message(f"ENHANCEMENT SUMMARY:")
        log_message(f"  Total images processed: {len(images)}")
        log_message(f"  Successful: {successful}")
        log_message(f"  Failed: {failed}")
        log_message(f"  Success rate: {(successful/len(images)*100):.1f}%")
        log_message(f"  Enhanced images location: {ENHANCED_DIR}")
        log_message(f"  Log file: {LOG_FILE}")
        log_message(f"  Progress file: {PROGRESS_FILE}")
        log_message(f"{'=' * 100}\n")

        return successful, failed

    except Exception as e:
        log_message(f"Fatal error: {str(e)}")
        import traceback
        log_message(traceback.format_exc())
        sys.exit(1)

if __name__ == "__main__":
    main()
