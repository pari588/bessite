#!/usr/bin/env python3
"""
Extract CG Global Motor Products from HTML files
"""
import re
import json
from html.parser import HTMLParser
from pathlib import Path

class MotorProductParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.products = []
        self.current_product = {}
        self.in_product = False
        self.in_product_name = False
        self.in_product_desc = False
        self.in_product_specs = False
        self.current_tag_attrs = []
        self.text_buffer = []

    def handle_starttag(self, tag, attrs):
        attrs_dict = dict(attrs)
        self.current_tag_attrs = attrs

        # Look for product containers
        if tag == 'div':
            classes = attrs_dict.get('class', '')
            if 'product' in classes.lower() or 'item' in classes.lower():
                self.in_product = True
                self.current_product = {}

        # Look for product images
        if tag == 'img' and self.in_product:
            src = attrs_dict.get('src', '')
            alt = attrs_dict.get('alt', '')
            if src and 'product' in src.lower() or 'motor' in src.lower() or alt:
                if 'image' not in self.current_product:
                    self.current_product['image'] = src
                if alt and 'name' not in self.current_product:
                    self.current_product['name'] = alt

        # Look for links
        if tag == 'a' and self.in_product:
            href = attrs_dict.get('href', '')
            if href and ('product' in href.lower() or 'motor' in href.lower()):
                self.current_product['link'] = href

        # Look for headings (product names)
        if tag in ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']:
            if self.in_product:
                self.in_product_name = True
                self.text_buffer = []

        # Look for paragraphs (descriptions)
        if tag == 'p' and self.in_product:
            self.in_product_desc = True
            self.text_buffer = []

    def handle_endtag(self, tag):
        if tag == 'div' and self.in_product:
            if self.current_product:
                self.products.append(self.current_product.copy())
            self.in_product = False
            self.current_product = {}

        if tag in ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] and self.in_product_name:
            text = ''.join(self.text_buffer).strip()
            if text and len(text) > 2:
                self.current_product['name'] = text
            self.in_product_name = False
            self.text_buffer = []

        if tag == 'p' and self.in_product_desc:
            text = ''.join(self.text_buffer).strip()
            if text and len(text) > 10:
                if 'description' not in self.current_product:
                    self.current_product['description'] = text
                elif 'specs' not in self.current_product:
                    self.current_product['specs'] = text
            self.in_product_desc = False
            self.text_buffer = []

    def handle_data(self, data):
        if self.in_product_name or self.in_product_desc:
            self.text_buffer.append(data)

def extract_products_simple(html_content):
    """Simple extraction using regex patterns"""
    products = []

    # Pattern 1: Look for product divs with class containing 'product' or 'item'
    product_pattern = r'<div[^>]*class="[^"]*(?:product|item|card)[^"]*"[^>]*>(.*?)</div>'
    matches = re.finditer(product_pattern, html_content, re.DOTALL | re.IGNORECASE)

    for match in matches:
        product_html = match.group(1)
        product = {}

        # Extract name from headings
        name_match = re.search(r'<h[1-6][^>]*>(.*?)</h[1-6]>', product_html, re.DOTALL)
        if name_match:
            product['name'] = re.sub(r'<[^>]+>', '', name_match.group(1)).strip()

        # Extract image
        img_match = re.search(r'<img[^>]*src="([^"]+)"[^>]*>', product_html)
        if img_match:
            product['image'] = img_match.group(1)

        # Extract link
        link_match = re.search(r'<a[^>]*href="([^"]+)"[^>]*>', product_html)
        if link_match:
            product['link'] = link_match.group(1)

        # Extract description from paragraphs
        desc_match = re.search(r'<p[^>]*>(.*?)</p>', product_html, re.DOTALL)
        if desc_match:
            product['description'] = re.sub(r'<[^>]+>', '', desc_match.group(1)).strip()

        if product.get('name'):
            products.append(product)

    return products

def extract_all_text_blocks(html_content):
    """Extract all meaningful text blocks from HTML"""
    # Remove scripts and styles
    html_clean = re.sub(r'<script[^>]*>.*?</script>', '', html_content, flags=re.DOTALL | re.IGNORECASE)
    html_clean = re.sub(r'<style[^>]*>.*?</style>', '', html_clean, flags=re.DOTALL | re.IGNORECASE)

    # Find all headings
    headings = re.findall(r'<h[1-6][^>]*>(.*?)</h[1-6]>', html_clean, re.DOTALL | re.IGNORECASE)

    # Find all paragraphs
    paragraphs = re.findall(r'<p[^>]*>(.*?)</p>', html_clean, re.DOTALL | re.IGNORECASE)

    # Find all list items
    list_items = re.findall(r'<li[^>]*>(.*?)</li>', html_clean, re.DOTALL | re.IGNORECASE)

    # Find all images with alt text
    images = re.findall(r'<img[^>]*src="([^"]+)"[^>]*alt="([^"]*)"[^>]*>', html_clean, re.IGNORECASE)
    images += re.findall(r'<img[^>]*alt="([^"]*)"[^>]*src="([^"]+)"[^>]*>', html_clean, re.IGNORECASE)

    # Find all links
    links = re.findall(r'<a[^>]*href="([^"]+)"[^>]*>(.*?)</a>', html_clean, re.DOTALL | re.IGNORECASE)

    return {
        'headings': [re.sub(r'<[^>]+>', '', h).strip() for h in headings if re.sub(r'<[^>]+>', '', h).strip()],
        'paragraphs': [re.sub(r'<[^>]+>', '', p).strip() for p in paragraphs if len(re.sub(r'<[^>]+>', '', p).strip()) > 20],
        'list_items': [re.sub(r'<[^>]+>', '', li).strip() for li in list_items if re.sub(r'<[^>]+>', '', li).strip()],
        'images': images,
        'links': [(href, re.sub(r'<[^>]+>', '', text).strip()) for href, text in links if re.sub(r'<[^>]+>', '', text).strip()]
    }

def process_category_file(filepath, category_name):
    """Process a single category HTML file"""
    print(f"\n{'='*80}")
    print(f"Processing: {category_name}")
    print(f"{'='*80}")

    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            html_content = f.read()

        # Extract structured data
        data = extract_all_text_blocks(html_content)

        # Try to identify products from the structure
        products = []

        # Look for product-related headings
        motor_keywords = ['motor', 'hp', 'kw', 'rpm', 'voltage', 'frame', 'series', 'phase']

        for i, heading in enumerate(data['headings']):
            # Check if this heading looks like a product name
            heading_lower = heading.lower()
            is_product = any(keyword in heading_lower for keyword in motor_keywords)

            if is_product or (len(heading.split()) <= 8 and len(heading) > 5):
                product = {
                    'name': heading,
                    'description': '',
                    'specs': [],
                    'image': '',
                    'link': ''
                }

                # Look for associated description in next paragraphs
                if i < len(data['paragraphs']):
                    product['description'] = data['paragraphs'][i] if i < len(data['paragraphs']) else ''

                # Look for associated links
                for href, link_text in data['links']:
                    if heading.lower() in link_text.lower() or link_text.lower() in heading.lower():
                        product['link'] = href
                        break

                # Look for associated images
                for img_src, img_alt in data['images']:
                    if heading.lower() in img_alt.lower() or img_alt.lower() in heading.lower():
                        product['image'] = img_src if isinstance(img_src, str) else img_alt
                        break

                products.append(product)

        # If no products found via headings, try different approach
        if not products:
            print("No products found via headings, trying alternative extraction...")
            # Just list all relevant text content
            products = [{
                'raw_headings': data['headings'][:20],
                'raw_paragraphs': data['paragraphs'][:20],
                'raw_links': [(h, t) for h, t in data['links'][:20]]
            }]

        return {
            'category': category_name,
            'products': products,
            'total_headings': len(data['headings']),
            'total_paragraphs': len(data['paragraphs']),
            'total_images': len(data['images']),
            'total_links': len(data['links'])
        }

    except Exception as e:
        print(f"Error processing {filepath}: {e}")
        return None

# Main execution
if __name__ == "__main__":
    categories = [
        ('high_voltage_motors.html', 'High Voltage Motors'),
        ('low_voltage_motors.html', 'Low Voltage Motors'),
        ('energy_efficient_motors.html', 'Energy Efficient Motors'),
        ('motors_hazardous_lv.html', 'Motors for Hazardous Area (LV)'),
        ('dc_motors.html', 'DC Motors'),
        ('motors_hazardous_hv.html', 'Motors for Hazardous Areas (HV)'),
        ('special_application_motors.html', 'Special Application Motors')
    ]

    all_data = []

    for filename, category_name in categories:
        filepath = Path(filename)
        if filepath.exists():
            result = process_category_file(filepath, category_name)
            if result:
                all_data.append(result)
                print(f"\nFound {len(result['products'])} products")
                print(f"Total headings: {result['total_headings']}")
                print(f"Total paragraphs: {result['total_paragraphs']}")
        else:
            print(f"File not found: {filename}")

    # Save to JSON
    output_file = 'cg_motors_extracted.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(all_data, f, indent=2, ensure_ascii=False)

    print(f"\n{'='*80}")
    print(f"Data saved to: {output_file}")
    print(f"{'='*80}")

    # Print summary
    print("\n\nSUMMARY BY CATEGORY:")
    print("="*80)
    for data in all_data:
        print(f"\n{data['category']}:")
        print(f"  - Products found: {len(data['products'])}")
        if data['products'] and 'name' in data['products'][0]:
            print(f"  - Sample products:")
            for prod in data['products'][:5]:
                print(f"    * {prod.get('name', 'N/A')}")
