#!/usr/bin/env python3
"""
Extract detailed motor product information from CG Global HTML files
"""
import re
import json
from html.parser import HTMLParser
from pathlib import Path

class MotorHTMLParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.in_product = False
        self.in_description = False
        self.in_specs = False
        self.current_tag = None
        self.current_attrs = {}
        self.text_buffer = []
        self.products = []
        self.current_product = {}

    def handle_starttag(self, tag, attrs):
        self.current_tag = tag
        self.current_attrs = dict(attrs)

        # Look for product containers
        if tag in ['div', 'section', 'article']:
            class_name = self.current_attrs.get('class', '')
            if any(word in class_name.lower() for word in ['product', 'item', 'motor', 'content']):
                self.in_product = True

    def handle_endtag(self, tag):
        if tag in ['div', 'section', 'article'] and self.in_product:
            if self.current_product:
                self.products.append(self.current_product.copy())
                self.current_product = {}
            self.in_product = False

    def handle_data(self, data):
        data = data.strip()
        if data and self.in_product:
            self.text_buffer.append(data)

def extract_from_html(filepath):
    """Extract motor details from HTML file"""
    print(f"\n{'='*80}")
    print(f"Processing: {filepath.name}")
    print('='*80)

    try:
        html_content = filepath.read_text(encoding='utf-8', errors='ignore')
    except Exception as e:
        print(f"Error reading file: {e}")
        return []

    # Remove script and style tags
    html_content = re.sub(r'<script[^>]*>.*?</script>', '', html_content, flags=re.DOTALL | re.IGNORECASE)
    html_content = re.sub(r'<style[^>]*>.*?</style>', '', html_content, flags=re.DOTALL | re.IGNORECASE)

    products = []

    # Extract main content section
    content_match = re.search(r'<div[^>]*class="[^"]*(?:content|main|product)[^"]*"[^>]*>(.*?)</div>',
                             html_content, re.DOTALL | re.IGNORECASE)

    # Look for product names/titles (h1, h2, h3, h4)
    headings = re.findall(r'<h[1-4][^>]*>([^<]+)</h[1-4]>', html_content, re.IGNORECASE)

    # Extract paragraphs with substantial content
    paragraphs = re.findall(r'<p[^>]*>([^<]{50,})</p>', html_content, re.IGNORECASE)

    # Extract list items
    list_items = re.findall(r'<li[^>]*>([^<]+)</li>', html_content, re.IGNORECASE)

    # Extract table data
    tables = re.findall(r'<table[^>]*>(.*?)</table>', html_content, re.DOTALL | re.IGNORECASE)

    print(f"\nFound {len(headings)} headings")
    print(f"Found {len(paragraphs)} paragraphs")
    print(f"Found {len(list_items)} list items")
    print(f"Found {len(tables)} tables")

    # Display headings
    if headings:
        print("\n--- HEADINGS ---")
        for i, heading in enumerate(headings[:20], 1):
            clean_heading = re.sub(r'\s+', ' ', heading).strip()
            if len(clean_heading) > 5 and 'CG' not in clean_heading[:5]:
                print(f"{i}. {clean_heading}")

    # Display meaningful paragraphs
    if paragraphs:
        print("\n--- DESCRIPTIONS ---")
        for i, para in enumerate(paragraphs[:10], 1):
            clean_para = re.sub(r'\s+', ' ', para).strip()
            clean_para = re.sub(r'&[a-z]+;', '', clean_para)
            if len(clean_para) > 50:
                print(f"\n{i}. {clean_para[:200]}...")

    # Display table content
    if tables:
        print("\n--- TABLES ---")
        for i, table in enumerate(tables[:5], 1):
            print(f"\nTable {i}:")
            rows = re.findall(r'<tr[^>]*>(.*?)</tr>', table, re.DOTALL | re.IGNORECASE)
            for row in rows[:10]:
                cells = re.findall(r'<t[dh][^>]*>([^<]*)</t[dh]>', row, re.IGNORECASE)
                if cells:
                    clean_cells = [re.sub(r'\s+', ' ', c).strip() for c in cells]
                    clean_cells = [c for c in clean_cells if c]
                    if clean_cells:
                        print(f"  {' | '.join(clean_cells)}")

    # Extract specifications patterns
    print("\n--- SPECIFICATIONS FOUND ---")

    # Output/Power
    outputs = re.findall(r'(?:Output|Power|Rating)[:\s]*([0-9.]+\s*(?:kW|HP|W|MW)(?:\s*(?:to|-)\s*[0-9.]+\s*(?:kW|HP|W|MW))?)',
                        html_content, re.IGNORECASE)
    if outputs:
        print(f"Output/Power: {', '.join(list(set(outputs))[:5])}")

    # Voltage
    voltages = re.findall(r'(?:Voltage|V)[:\s]*([0-9.]+\s*(?:V|kV|volt)(?:\s*(?:to|-|,)\s*[0-9.]+\s*(?:V|kV|volt))?)',
                         html_content, re.IGNORECASE)
    if voltages:
        print(f"Voltages: {', '.join(list(set(voltages))[:5])}")

    # Frame sizes
    frames = re.findall(r'(?:Frame|Size)[:\s]*([0-9]+(?:\s*(?:to|-)\s*[0-9]+)?(?:\s*mm)?)',
                       html_content, re.IGNORECASE)
    if frames:
        print(f"Frame Sizes: {', '.join(list(set(frames))[:5])}")

    # Standards
    standards = re.findall(r'\b(IEC\s*[0-9]+|IS\s*[0-9]+|NEMA\s*[A-Z0-9]+|ATEX|BIS|CCOE|CMRI|IEEE\s*[0-9]+)\b',
                          html_content, re.IGNORECASE)
    if standards:
        print(f"Standards: {', '.join(list(set(standards))[:10])}")

    return products

def main():
    base_dir = Path('/home/bombayengg/public_html')

    html_files = [
        'high_voltage_motors.html',
        'low_voltage_motors.html',
        'energy_efficient_motors.html',
        'motors_hazardous_lv.html',
        'motors_hazardous_hv.html',
        'dc_motors.html',
        'special_application_motors.html',
        'product_air_cooled.html',
        'product_axelera.html',
        'product_ie4.html',
        'product_dc.html'
    ]

    all_data = {}

    for html_file in html_files:
        filepath = base_dir / html_file
        if filepath.exists():
            products = extract_from_html(filepath)
            all_data[html_file] = products
        else:
            print(f"File not found: {html_file}")

    print("\n" + "="*80)
    print("EXTRACTION COMPLETE")
    print("="*80)

if __name__ == '__main__':
    main()
