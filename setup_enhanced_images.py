#!/usr/bin/env python3
"""
Setup Enhanced Motor Images
- Generate thumbnails (235x235 and 530x530)
- Replace original images with enhanced versions
- Create backup of old images before replacement
- Restore capability if needed
"""

import os
import sys
import shutil
from pathlib import Path
from PIL import Image
from datetime import datetime

# Configuration
MOTOR_DIR = Path("/home/bombayengg/public_html/uploads/motor")
ENHANCED_DIR = MOTOR_DIR / "enhanced"
THUMBNAIL_235_DIR = MOTOR_DIR / "235_235_crop_100"
THUMBNAIL_530_DIR = MOTOR_DIR / "530_530_crop_100"
ORIGINAL_BACKUP_DIR = MOTOR_DIR / "original_backup"
LOG_FILE = Path("/home/bombayengg/public_html/image_replacement.log")

def log_message(message):
    """Log messages to file and console"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    formatted_msg = f"[{timestamp}] {message}"
    print(formatted_msg)
    with open(LOG_FILE, 'a') as f:
        f.write(formatted_msg + '\n')

def create_thumbnail(image_path: Path, output_path: Path, size: tuple) -> bool:
    """
    Create a thumbnail from an image
    """
    try:
        img = Image.open(image_path)

        # Convert RGBA to RGB for compatibility
        if img.mode == 'RGBA':
            background = Image.new('RGB', img.size, (255, 255, 255))
            background.paste(img, mask=img.split()[3])
            img = background

        # Create thumbnail with center crop
        img.thumbnail(size, Image.Resampling.LANCZOS)

        # Add padding if needed to reach exact size
        if img.size != size:
            padded = Image.new('RGB', size, (255, 255, 255))
            offset = ((size[0] - img.size[0]) // 2, (size[1] - img.size[1]) // 2)
            padded.paste(img, offset)
            img = padded

        # Save as WebP
        img.save(output_path, 'WEBP', quality=90, method=6)
        return True

    except Exception as e:
        log_message(f"  Error creating thumbnail: {str(e)}")
        return False

def main():
    """Main setup process"""
    log_message("=" * 100)
    log_message("SETUP ENHANCED MOTOR IMAGES - Generate Thumbnails & Replace Originals")
    log_message("=" * 100)

    try:
        # Create directories
        THUMBNAIL_235_DIR.mkdir(exist_ok=True)
        THUMBNAIL_530_DIR.mkdir(exist_ok=True)
        ORIGINAL_BACKUP_DIR.mkdir(exist_ok=True)

        log_message(f"Directories ready:")
        log_message(f"  Enhanced images: {ENHANCED_DIR}")
        log_message(f"  Thumbnails (235x235): {THUMBNAIL_235_DIR}")
        log_message(f"  Thumbnails (530x530): {THUMBNAIL_530_DIR}")
        log_message(f"  Original backup: {ORIGINAL_BACKUP_DIR}\n")

        # Get enhanced PNG and WebP images
        enhanced_images = list(ENHANCED_DIR.glob("*_enhanced.png"))
        enhanced_images.sort()

        log_message(f"Found {len(enhanced_images)} enhanced images to process\n")

        # Process each enhanced image
        successful = 0
        failed = 0

        for idx, enhanced_path in enumerate(enhanced_images, 1):
            # Get original filename (remove "_enhanced" suffix)
            original_name = enhanced_path.name.replace("_enhanced.png", "")

            print(f"\r[{idx}/{len(enhanced_images)}] Processing: {original_name}", end="", flush=True)

            try:
                # Backup original if it exists
                original_paths = list(MOTOR_DIR.glob(f"{original_name}*"))
                original_paths = [p for p in original_paths if p.is_file() and
                                 '235_235' not in str(p) and '530_530' not in str(p) and
                                 'enhanced' not in str(p) and 'tmp' not in str(p)]

                for orig_path in original_paths:
                    backup_path = ORIGINAL_BACKUP_DIR / orig_path.name
                    if not backup_path.exists():
                        shutil.copy2(orig_path, backup_path)

                # Create thumbnails from enhanced image
                thumb_235_path = THUMBNAIL_235_DIR / f"235_235_crop_{original_name}.webp"
                thumb_530_path = THUMBNAIL_530_DIR / f"530_530_crop_{original_name}.webp"

                # Generate 235x235 thumbnail
                if create_thumbnail(enhanced_path, thumb_235_path, (235, 235)):
                    log_message(f"  ✓ Created 235x235 thumbnail: {thumb_235_path.name}")
                else:
                    log_message(f"  ✗ Failed to create 235x235 thumbnail")

                # Generate 530x530 thumbnail
                if create_thumbnail(enhanced_path, thumb_530_path, (530, 530)):
                    log_message(f"  ✓ Created 530x530 thumbnail: {thumb_530_path.name}")
                else:
                    log_message(f"  ✗ Failed to create 530x530 thumbnail")

                # Copy enhanced image as main image (convert PNG to WebP if needed)
                main_image_path = MOTOR_DIR / f"{original_name}.webp"
                enhanced_path_png = enhanced_path

                # Convert PNG to WebP for storage optimization
                img = Image.open(enhanced_path_png)
                if img.mode == 'RGBA':
                    # Save with transparency as PNG
                    img.save(main_image_path.with_suffix('.png'), 'PNG', optimize=True)
                    main_image_path = main_image_path.with_suffix('.png')
                else:
                    img = img.convert('RGB')
                    img.save(main_image_path, 'WEBP', quality=95, method=6)

                log_message(f"  ✓ Deployed main image: {main_image_path.name}")
                successful += 1

            except Exception as e:
                log_message(f"  ✗ Error processing {original_name}: {str(e)}")
                failed += 1

        log_message(f"\n{'=' * 100}")
        log_message(f"IMAGE SETUP SUMMARY:")
        log_message(f"  Total images processed: {len(enhanced_images)}")
        log_message(f"  Successful: {successful}")
        log_message(f"  Failed: {failed}")
        log_message(f"  Success rate: {(successful/len(enhanced_images)*100):.1f}%")
        log_message(f"\nBackup Information:")
        log_message(f"  Original backups saved in: {ORIGINAL_BACKUP_DIR}")
        log_message(f"  Compressed backup file: motor_images_backup.tar.gz")
        log_message(f"\nTo restore originals if needed:")
        log_message(f"  1. Delete enhanced images: rm -rf {ENHANCED_DIR}")
        log_message(f"  2. Restore from backup: tar -xzf motor_images_backup.tar.gz")
        log_message(f"{'=' * 100}\n")

        return successful, failed

    except Exception as e:
        log_message(f"Fatal error: {str(e)}")
        import traceback
        log_message(traceback.format_exc())
        sys.exit(1)

if __name__ == "__main__":
    main()
