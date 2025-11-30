#!/usr/bin/env python3
"""
Extract detailed product information from CG Global product pages
"""
import re
import json
from pathlib import Path

def extract_product_details(html_content, product_name):
    """Extract specifications, features, and applications from product page"""

    # Remove scripts and styles
    html_clean = re.sub(r'<script[^>]*>.*?</script>', '', html_content, flags=re.DOTALL | re.IGNORECASE)
    html_clean = re.sub(r'<style[^>]*>.*?</style>', '', html_clean, flags=re.DOTALL | re.IGNORECASE)

    details = {
        'name': product_name,
        'description': '',
        'features': [],
        'applications': [],
        'specifications': {},
        'technical_data': []
    }

    # Extract main description
    desc_patterns = [
        r'<p[^>]*class="[^"]*details[^"]*"[^>]*>(.*?)</p>',
        r'<div[^>]*class="[^"]*description[^"]*"[^>]*>(.*?)</div>',
        r'<p[^>]*class="[^"]*description[^"]*"[^>]*>(.*?)</p>'
    ]

    for pattern in desc_patterns:
        matches = re.finditer(pattern, html_clean, re.DOTALL | re.IGNORECASE)
        for match in matches:
            text = re.sub(r'<[^>]+>', ' ', match.group(1)).strip()
            text = re.sub(r'\s+', ' ', text)
            if text and len(text) > 30 and text not in details['description']:
                if details['description']:
                    details['description'] += '\n\n' + text
                else:
                    details['description'] = text

    # Extract features (usually in lists)
    feature_section_pattern = r'<h[2-6][^>]*>.*?(?:feature|advantage|benefit).*?</h[2-6]>(.*?)(?=<h[2-6]|<div class="product|$)'
    feature_matches = re.finditer(feature_section_pattern, html_clean, re.DOTALL | re.IGNORECASE)

    for match in feature_matches:
        section_html = match.group(1)
        # Extract list items
        li_pattern = r'<li[^>]*>(.*?)</li>'
        li_matches = re.finditer(li_pattern, section_html, re.DOTALL | re.IGNORECASE)
        for li_match in li_matches:
            feature_text = re.sub(r'<[^>]+>', ' ', li_match.group(1)).strip()
            feature_text = re.sub(r'\s+', ' ', feature_text)
            if feature_text and len(feature_text) > 5:
                details['features'].append(feature_text)

    # Extract applications
    app_section_pattern = r'<h[2-6][^>]*>.*?application.*?</h[2-6]>(.*?)(?=<h[2-6]|<div class="product|$)'
    app_matches = re.finditer(app_section_pattern, html_clean, re.DOTALL | re.IGNORECASE)

    for match in app_matches:
        section_html = match.group(1)
        # Extract list items
        li_pattern = r'<li[^>]*>(.*?)</li>'
        li_matches = re.finditer(li_pattern, section_html, re.DOTALL | re.IGNORECASE)
        for li_match in li_matches:
            app_text = re.sub(r'<[^>]+>', ' ', li_match.group(1)).strip()
            app_text = re.sub(r'\s+', ' ', app_text)
            if app_text and len(app_text) > 5:
                details['applications'].append(app_text)

    # Extract all list items that aren't already captured
    all_li_pattern = r'<li[^>]*>(.*?)</li>'
    all_li_matches = re.finditer(all_li_pattern, html_clean, re.DOTALL | re.IGNORECASE)

    for li_match in all_li_matches:
        li_text = re.sub(r'<[^>]+>', ' ', li_match.group(1)).strip()
        li_text = re.sub(r'\s+', ' ', li_text)
        if li_text and len(li_text) > 10:
            # Check if it looks like a specification
            if ':' in li_text or '–' in li_text or '-' in li_text:
                parts = re.split(r'[:\-–]', li_text, 1)
                if len(parts) == 2:
                    key = parts[0].strip()
                    value = parts[1].strip()
                    if key and value:
                        details['specifications'][key] = value
            # Otherwise, if not already in features/apps, add to features
            elif li_text not in details['features'] and li_text not in details['applications']:
                details['features'].append(li_text)

    # Extract tables (specifications)
    table_pattern = r'<table[^>]*>(.*?)</table>'
    table_matches = re.finditer(table_pattern, html_clean, re.DOTALL | re.IGNORECASE)

    for table_match in table_matches:
        table_html = table_match.group(1)
        # Extract rows
        row_pattern = r'<tr[^>]*>(.*?)</tr>'
        row_matches = re.finditer(row_pattern, table_html, re.DOTALL | re.IGNORECASE)

        for row_match in row_matches:
            row_html = row_match.group(1)
            # Extract cells
            cell_pattern = r'<t[dh][^>]*>(.*?)</t[dh]>'
            cells = re.findall(cell_pattern, row_html, re.DOTALL | re.IGNORECASE)

            if len(cells) >= 2:
                key = re.sub(r'<[^>]+>', '', cells[0]).strip()
                value = re.sub(r'<[^>]+>', '', cells[1]).strip()
                if key and value and len(key) < 100:
                    details['specifications'][key] = value
            elif len(cells) > 2:
                # Multi-column table, store as row
                row_data = [re.sub(r'<[^>]+>', '', cell).strip() for cell in cells]
                details['technical_data'].append(row_data)

    # Extract paragraphs that might contain additional info
    para_pattern = r'<p[^>]*>(.*?)</p>'
    para_matches = re.finditer(para_pattern, html_clean, re.DOTALL | re.IGNORECASE)

    additional_info = []
    for para_match in para_matches:
        para_text = re.sub(r'<[^>]+>', ' ', para_match.group(1)).strip()
        para_text = re.sub(r'\s+', ' ', para_text)
        if para_text and len(para_text) > 50 and para_text not in details['description']:
            # Check if it's meaningful content
            if any(word in para_text.lower() for word in ['motor', 'power', 'voltage', 'speed', 'rating', 'efficiency', 'cooling', 'protection', 'standard', 'frame']):
                additional_info.append(para_text)

    if additional_info and not details['description']:
        details['description'] = '\n\n'.join(additional_info[:3])

    return details

# Test with sample files
sample_files = [
    ('product_air_cooled.html', 'Air Cooled Induction Motors - IC 6A1A1, IC 6A1A6, IC 6A6A6 (CACA)'),
    ('product_axelera.html', 'AXELERA Process Performance Motors'),
    ('product_ie4.html', 'Super Premium IE4 Efficiency –Apex Series'),
    ('product_dc.html', 'DC Motors')
]

all_product_details = []

for filename, product_name in sample_files:
    filepath = Path(filename)
    if filepath.exists():
        print(f"\n{'='*80}")
        print(f"Extracting: {product_name}")
        print(f"{'='*80}")

        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            html_content = f.read()

        details = extract_product_details(html_content, product_name)

        print(f"\nDescription length: {len(details['description'])} chars")
        print(f"Features found: {len(details['features'])}")
        print(f"Applications found: {len(details['applications'])}")
        print(f"Specifications found: {len(details['specifications'])}")
        print(f"Technical data rows: {len(details['technical_data'])}")

        if details['features']:
            print(f"\nSample features:")
            for feat in details['features'][:5]:
                print(f"  - {feat}")

        if details['specifications']:
            print(f"\nSample specifications:")
            for i, (key, value) in enumerate(list(details['specifications'].items())[:5]):
                print(f"  {key}: {value}")

        all_product_details.append(details)
    else:
        print(f"File not found: {filename}")

# Save to JSON
output_file = 'product_details_sample.json'
with open(output_file, 'w', encoding='utf-8') as f:
    json.dump(all_product_details, f, indent=2, ensure_ascii=False)

print(f"\n{'='*80}")
print(f"Sample product details saved to: {output_file}")
print(f"{'='*80}")
