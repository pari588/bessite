#!/usr/bin/env python3
"""
Motor Image Enhancement Script - Local Processing
- Advanced quality enhancement using PIL
- Smart background removal for images with solid backgrounds
- Converts to WebP format for optimal quality and file size
- No external API required
"""

import os
import sys
from pathlib import Path
from PIL import Image, ImageEnhance, ImageFilter, ImageOps
from datetime import datetime
import json
from typing import Tuple

# Configuration
MOTOR_DIR = Path("/home/bombayengg/public_html/uploads/motor")
ENHANCED_DIR = MOTOR_DIR / "enhanced"
LOG_FILE = Path("/home/bombayengg/public_html/motor_enhancement.log")
PROGRESS_FILE = Path("/home/bombayengg/public_html/motor_enhancement_progress.json")

# Image formats to process
VALID_FORMATS = {'.webp', '.jpg', '.jpeg', '.png'}

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

def detect_background_color(img: Image.Image) -> Tuple[int, int, int]:
    """
    Detect the most common background color in the image
    Assumes background is in corners/edges
    """
    try:
        # Resize for faster processing
        img_small = img.resize((100, 100))
        img_rgb = img_small.convert('RGB')

        # Sample pixels from edges (likely background)
        pixels = []

        # Top edge
        for x in range(100):
            pixels.append(img_rgb.getpixel((x, 0)))
            pixels.append(img_rgb.getpixel((x, 5)))

        # Bottom edge
        for x in range(100):
            pixels.append(img_rgb.getpixel((x, 99)))
            pixels.append(img_rgb.getpixel((x, 94)))

        # Left/Right edges
        for y in range(100):
            pixels.append(img_rgb.getpixel((0, y)))
            pixels.append(img_rgb.getpixel((5, y)))
            pixels.append(img_rgb.getpixel((99, y)))
            pixels.append(img_rgb.getpixel((94, y)))

        # Find most common color
        from collections import Counter
        color_counts = Counter(pixels)
        bg_color = color_counts.most_common(1)[0][0]

        return bg_color

    except:
        return (255, 255, 255)  # Default to white

def remove_background_smart(img: Image.Image) -> Image.Image:
    """
    Intelligently remove background from image
    - Detects background color
    - Converts to RGBA with transparent background
    - Handles antialiasing for smooth edges
    """
    try:
        # Convert to RGB first
        if img.mode != 'RGB':
            if img.mode == 'RGBA':
                # Create white background for RGBA images
                background = Image.new('RGB', img.size, (255, 255, 255))
                background.paste(img, mask=img.split()[3])
                img = background
            else:
                img = img.convert('RGB')

        # Detect background color
        bg_color = detect_background_color(img)

        # Convert to RGBA
        img_rgba = img.convert('RGBA')

        # Create mask based on background color
        # Allow some tolerance for edge detection
        data = img_rgba.getdata()

        # Tolerance for color matching (allows for slight variations)
        tolerance = 30

        new_data = []
        for pixel in data:
            r, g, b = pixel[:3]

            # Calculate distance from background color
            distance = ((r - bg_color[0])**2 + (g - bg_color[1])**2 + (b - bg_color[2])**2) ** 0.5

            # If pixel is similar to background, make transparent
            if distance < tolerance:
                new_data.append((r, g, b, 0))
            else:
                new_data.append((r, g, b, 255))

        img_rgba.putdata(new_data)

        # Apply slight dilation to remove semi-transparent pixels
        img_rgba = img_rgba.filter(ImageFilter.SMOOTH_MORE)

        return img_rgba

    except Exception as e:
        log_message(f"  Warning: Background removal encountered issue: {str(e)}")
        # Return RGBA version of original
        return img.convert('RGBA')

def enhance_image_quality(img: Image.Image) -> Image.Image:
    """
    Enhance image quality using multiple enhancement techniques
    """
    try:
        # Convert to RGB for enhancement
        if img.mode == 'RGBA':
            # Create white background for transparency
            background = Image.new('RGB', img.size, (255, 255, 255))
            background.paste(img, mask=img.split()[3])
            img_work = background
        else:
            img_work = img.convert('RGB')

        # 1. Enhance contrast (makes colors more distinct)
        enhancer = ImageEnhance.Contrast(img_work)
        img_work = enhancer.enhance(1.4)

        # 2. Enhance color saturation (makes colors more vivid)
        enhancer = ImageEnhance.Color(img_work)
        img_work = enhancer.enhance(1.2)

        # 3. Enhance sharpness (makes details crisp)
        enhancer = ImageEnhance.Sharpness(img_work)
        img_work = enhancer.enhance(1.5)

        # 4. Enhance brightness (makes image brighter)
        enhancer = ImageEnhance.Brightness(img_work)
        img_work = enhancer.enhance(1.1)

        # 5. Apply slight denoising via smoothing
        img_work = img_work.filter(ImageFilter.GaussianBlur(radius=0.3))

        # 6. Reapply sharpening after smoothing
        enhancer = ImageEnhance.Sharpness(img_work)
        img_work = enhancer.enhance(1.3)

        return img_work

    except Exception as e:
        log_message(f"  Warning: Enhancement failed: {str(e)}")
        return img

def process_image_enhanced(input_path: Path, output_path: Path) -> bool:
    """
    Process image with background removal and quality enhancement
    """
    try:
        log_message(f"🔄 Enhancing: {input_path.name}")

        # Open image
        img = Image.open(input_path)

        # Remove background
        img_no_bg = remove_background_smart(img)

        # Enhance quality
        img_enhanced = enhance_image_quality(img_no_bg)

        # Convert back to RGBA if it was processed with background removal
        if img_no_bg.mode == 'RGBA' and img_enhanced.mode == 'RGB':
            # Reapply transparency
            background = Image.new('RGBA', img_enhanced.size, (255, 255, 255, 0))
            background.paste(img_enhanced, mask=img_no_bg.split()[3] if img_no_bg.mode == 'RGBA' else None)
            img_enhanced = background

        # Save as WebP with high quality
        # PNG for RGBA, WebP for RGB
        if img_enhanced.mode == 'RGBA':
            img_enhanced.save(output_path.with_suffix('.png'), 'PNG', optimize=True)
            output_path = output_path.with_suffix('.png')
        else:
            img_enhanced.save(output_path, 'WEBP', quality=95, method=6)

        # Log results
        file_size_kb = output_path.stat().st_size / 1024
        img_size = img_enhanced.size
        log_message(f"✓ Successfully enhanced: {output_path.name} ({file_size_kb:.1f} KB) [{img_size[0]}x{img_size[1]}]")
        return True

    except Exception as e:
        log_message(f"✗ Error processing {input_path.name}: {str(e)}")
        import traceback
        log_message(f"  Traceback: {traceback.format_exc()}")
        return False

def get_main_images() -> list:
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
    log_message("MOTOR IMAGE ENHANCEMENT - LOCAL PROCESSING (No API Required)")
    log_message("=" * 100)

    try:
        create_enhanced_directory()

        # Get main images only
        images = get_main_images()
        log_message(f"\nFound {len(images)} images to process")
        log_message(f"Source directory: {MOTOR_DIR}")
        log_message(f"Output directory: {ENHANCED_DIR}")
        log_message(f"Processing: Background removal + Quality enhancement + WebP conversion\n")

        if not images:
            log_message("No images found to process!")
            return 0, 0

        # Process images
        successful = 0
        failed = 0

        for idx, image_path in enumerate(images, 1):
            output_path = ENHANCED_DIR / f"{image_path.stem}_enhanced.webp"

            print(f"\r[{idx}/{len(images)}] ", end="", flush=True)

            if process_image_enhanced(image_path, output_path):
                successful += 1
            else:
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
        log_message(f"  Original backup: motor_images_backup.tar.gz")
        log_message(f"{'=' * 100}\n")

        return successful, failed

    except Exception as e:
        log_message(f"Fatal error: {str(e)}")
        import traceback
        log_message(traceback.format_exc())
        sys.exit(1)

if __name__ == "__main__":
    main()
