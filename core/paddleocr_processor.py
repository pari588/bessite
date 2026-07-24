#!/usr/bin/env python3
"""
PaddleOCR Processor for Fuel Bills
Extracts text from images/PDFs with high accuracy
Optimized for financial documents (dates, amounts)

Usage: python3 paddleocr_processor.py <image_path> [<output_format>]
Output: JSON with text and confidence scores
"""

import sys
import json
import os
from pathlib import Path
import warnings

# Suppress all warnings (PaddleOCR generates many non-critical warnings)
warnings.filterwarnings('ignore')

# Redirect stderr to suppress PaddleOCR/Paddle noise and shell output
import io
import subprocess

# Suppress shell commands from outputting to stderr
os.environ['PYTHONWARNINGS'] = 'ignore'

# Save original stderr
_stderr = sys.stderr
# Create null writer to capture all warnings and shell output
sys.stderr = io.StringIO()

# Handle PDF conversion if needed
def convert_pdf_to_image(pdf_path):
    """Convert PDF to image using available tools"""
    import subprocess

    # Try pdftoppm first (best quality)
    temp_image = f"/tmp/paddle_ocr_{os.getpid()}.png"
    try:
        result = subprocess.run(
            ['/bin/pdftoppm', '-singlefile', '-png', pdf_path, temp_image[:-4]],  # Remove .png from prefix
            capture_output=True,
            timeout=30
        )
        if result.returncode == 0 and os.path.exists(temp_image):
            return temp_image
    except Exception as e:
        pass

    # Try ImageMagick convert as fallback
    try:
        result = subprocess.run(
            ['/bin/convert', '-density', '150', pdf_path, temp_image],
            capture_output=True,
            timeout=30
        )
        if result.returncode == 0 and os.path.exists(temp_image):
            return temp_image
    except Exception as e:
        pass

    return None

def process_with_paddleocr(image_path):
    """Process image with PaddleOCR"""
    try:
        from paddleocr import PaddleOCR

        # Initialize PaddleOCR with English language
        # use_textline_orientation=True helps with rotated text (newer parameter)
        # Note: use_textline_orientation and use_angle_cls are mutually exclusive
        ocr = PaddleOCR(
            use_textline_orientation=True,
            lang='en',
            cpu_threads=4
        )

        # Process the image
        # Use predict() method (ocr() is deprecated in newer versions)
        result = ocr.predict(image_path)

        return result
    except Exception as e:
        return {'error': str(e)}

def extract_text_and_confidence(ocr_result):
    """Extract text and confidence scores from PaddleOCR result"""
    if isinstance(ocr_result, dict) and 'error' in ocr_result:
        return {
            'status': 'error',
            'message': ocr_result['error'],
            'text': '',
            'blocks': []
        }

    if not ocr_result or not isinstance(ocr_result, list) or len(ocr_result) == 0:
        return {
            'status': 'error',
            'message': 'No OCR result or empty result',
            'text': '',
            'blocks': []
        }

    text_parts = []
    blocks = []

    # New PaddleOCR 3.3.2 structure uses 'rec_texts' and 'rec_scores' inside result dictionary
    for result_item in ocr_result:
        if isinstance(result_item, dict):
            # Check if this is the new format
            if 'rec_texts' in result_item and 'rec_scores' in result_item:
                rec_texts = result_item['rec_texts']
                rec_scores = result_item['rec_scores']

                # Pair up texts with their confidence scores
                for i, text in enumerate(rec_texts):
                    if i < len(rec_scores):
                        confidence_score = float(rec_scores[i])
                        text_parts.append(text)
                        blocks.append({
                            'text': text,
                            'confidence': round(confidence_score * 100, 2),  # Convert to percentage
                        })
            # Check if this is old format (list of lines with word boxes)
            elif isinstance(result_item, list):
                for word_info in result_item:
                    if len(word_info) >= 2:
                        text_confidence = word_info[1]
                        if isinstance(text_confidence, tuple) and len(text_confidence) >= 2:
                            text = text_confidence[0]
                            confidence = float(text_confidence[1])
                            text_parts.append(text)
                            blocks.append({
                                'text': text,
                                'confidence': round(confidence * 100, 2),
                            })

    full_text = ' '.join(text_parts)

    return {
        'status': 'success',
        'text': full_text,
        'blocks': blocks,
        'block_count': len(blocks),
        'avg_confidence': round(sum(b['confidence'] for b in blocks) / len(blocks), 2) if blocks else 0
    }

def main():
    try:
        if len(sys.argv) < 2:
            sys.stderr = _stderr  # Restore stderr before output
            print(json.dumps({
                'status': 'error',
                'message': 'Usage: paddleocr_processor.py <image_path>'
            }))
            sys.exit(1)

        image_path = sys.argv[1]

        # Validate file exists
        if not os.path.exists(image_path):
            sys.stderr = _stderr  # Restore stderr before output
            print(json.dumps({
                'status': 'error',
                'message': f'File not found: {image_path}'
            }))
            sys.exit(1)

        # Check if PDF and convert if needed
        if image_path.lower().endswith('.pdf'):
            converted_image = convert_pdf_to_image(image_path)
            if converted_image:
                image_path = converted_image
            else:
                sys.stderr = _stderr  # Restore stderr before output
                print(json.dumps({
                    'status': 'error',
                    'message': 'PDF conversion failed - no PDF tools available'
                }))
                sys.exit(1)

        # Process with PaddleOCR
        ocr_result = process_with_paddleocr(image_path)

        # Extract and format results
        output = extract_text_and_confidence(ocr_result)

        # Restore stderr and output as JSON (clean output)
        sys.stderr = _stderr
        print(json.dumps(output, ensure_ascii=False, indent=2))
    except Exception as e:
        sys.stderr = _stderr  # Restore stderr
        print(json.dumps({
            'status': 'error',
            'message': str(e)
        }))
        sys.exit(1)

if __name__ == '__main__':
    main()
