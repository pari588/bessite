#!/usr/bin/env python3
"""
Extract CG Global Motor Products from HTML files - Version 2
Focused on actual product listings
"""
import re
import json
from pathlib import Path

def extract_products_from_html(html_content):
    """Extract products using specific HTML structure patterns"""
    products = []

    # Pattern to find product blocks with image, title, and link
    # Looking for the structure: <a href="..."><img src="..."><h4>Product Name</h4></a>

    # Find all product links
    product_pattern = r'<a\s+href="([^"]+)"\s+class="hover-effect[^"]*"[^>]*>.*?<img\s+src="([^"]+)"[^>]*>.*?<h4[^>]*>\s*([^<]+?)\s*</h4>.*?</a>'
    matches = re.finditer(product_pattern, html_content, re.DOTALL | re.IGNORECASE)

    for match in matches:
        product_url = match.group(1).strip()
        image_url = match.group(2).strip()
        product_name = match.group(3).strip()

        # Skip if product name looks like navigation or generic text
        skip_names = ['read more', 'call us', 'email us', 'market', 'home', 'contact']
        if any(skip in product_name.lower() for skip in skip_names):
            continue

        # Skip if URL doesn't look like a product URL
        if not ('High-Low-Voltage' in product_url or 'Motors' in product_url):
            continue

        products.append({
            'name': product_name,
            'link': product_url,
            'image': image_url
        })

    return products

def extract_category_description(html_content):
    """Extract the main category description"""
    # Look for the category subtitle
    desc_pattern = r'<h4\s+class="[^"]*subtitle[^"]*pagesubtitle[^"]*"[^>]*>\s*([^<]+)\s*</h4>'
    match = re.search(desc_pattern, html_content, re.IGNORECASE)
    if match:
        return match.group(1).strip()

    # Alternative: look for details paragraph
    details_pattern = r'<p\s+class="details">\s*<p>([^<]+)</p>'
    match = re.search(details_pattern, html_content, re.IGNORECASE)
    if match:
        return match.group(1).strip()

    return ""

def process_category_file(filepath, category_name):
    """Process a single category HTML file"""
    print(f"\n{'='*80}")
    print(f"Processing: {category_name}")
    print(f"{'='*80}")

    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            html_content = f.read()

        # Extract category description
        description = extract_category_description(html_content)

        # Extract products
        products = extract_products_from_html(html_content)

        print(f"Found {len(products)} products")
        if products:
            print("\nProducts:")
            for i, prod in enumerate(products, 1):
                print(f"  {i}. {prod['name']}")

        return {
            'category': category_name,
            'description': description,
            'products': products,
            'product_count': len(products)
        }

    except Exception as e:
        print(f"Error processing {filepath}: {e}")
        import traceback
        traceback.print_exc()
        return None

def download_product_details(product_url):
    """Download individual product detail page"""
    import subprocess

    # Extract product slug from URL
    slug = product_url.split('/')[-1]
    filename = f"product_{slug}.html"

    cmd = f'wget --no-check-certificate -q -O {filename} "{product_url}"'
    try:
        subprocess.run(cmd, shell=True, check=True, timeout=30)
        return filename
    except:
        return None

def extract_product_details(html_content):
    """Extract detailed information from product detail page"""
    details = {}

    # Extract specifications table
    specs_pattern = r'<table[^>]*>(.*?)</table>'
    matches = re.finditer(specs_pattern, html_content, re.DOTALL | re.IGNORECASE)

    specifications = []
    for match in matches:
        table_html = match.group(1)
        # Extract rows
        row_pattern = r'<tr[^>]*>(.*?)</tr>'
        rows = re.finditer(row_pattern, table_html, re.DOTALL | re.IGNORECASE)
        for row in rows:
            row_html = row.group(1)
            # Extract cells
            cell_pattern = r'<t[dh][^>]*>(.*?)</t[dh]>'
            cells = re.findall(cell_pattern, row_html, re.DOTALL | re.IGNORECASE)
            if len(cells) >= 2:
                key = re.sub(r'<[^>]+>', '', cells[0]).strip()
                value = re.sub(r'<[^>]+>', '', cells[1]).strip()
                if key and value:
                    specifications.append({key: value})

    # Extract description paragraphs
    para_pattern = r'<p[^>]*class="[^"]*details[^"]*"[^>]*>(.*?)</p>'
    matches = re.finditer(para_pattern, html_content, re.DOTALL | re.IGNORECASE)

    descriptions = []
    for match in matches:
        para_text = re.sub(r'<[^>]+>', '', match.group(1)).strip()
        if para_text and len(para_text) > 20:
            descriptions.append(para_text)

    # Extract bullet points/features
    li_pattern = r'<li[^>]*>(.*?)</li>'
    matches = re.finditer(li_pattern, html_content, re.DOTALL | re.IGNORECASE)

    features = []
    for match in matches:
        feature_text = re.sub(r'<[^>]+>', '', match.group(1)).strip()
        if feature_text and len(feature_text) > 5:
            features.append(feature_text)

    return {
        'specifications': specifications,
        'descriptions': descriptions,
        'features': features
    }

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
        else:
            print(f"File not found: {filename}")

    # Save to JSON
    output_file = 'cg_motors_extracted_v2.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(all_data, f, indent=2, ensure_ascii=False)

    print(f"\n{'='*80}")
    print(f"Data saved to: {output_file}")
    print(f"{'='*80}")

    # Create formatted text output
    output_txt = 'cg_motors_extracted.txt'
    with open(output_txt, 'w', encoding='utf-8') as f:
        f.write("CG GLOBAL - MOTOR PRODUCTS CATALOG\n")
        f.write("="*80 + "\n\n")

        for category_data in all_data:
            f.write(f"\n{'='*80}\n")
            f.write(f"CATEGORY: {category_data['category']}\n")
            f.write(f"{'='*80}\n\n")

            if category_data['description']:
                f.write(f"Description:\n{category_data['description']}\n\n")

            f.write(f"Total Products: {category_data['product_count']}\n\n")

            if category_data['products']:
                f.write("PRODUCTS:\n")
                f.write("-"*80 + "\n\n")

                for i, product in enumerate(category_data['products'], 1):
                    f.write(f"{i}. {product['name']}\n")
                    f.write(f"   Link: {product['link']}\n")
                    f.write(f"   Image: {product['image']}\n")
                    f.write("\n")

    print(f"\nText output saved to: {output_txt}")

    # Print summary
    print("\n\nSUMMARY BY CATEGORY:")
    print("="*80)
    total_products = 0
    for data in all_data:
        print(f"\n{data['category']}:")
        print(f"  Products: {data['product_count']}")
        total_products += data['product_count']

    print(f"\n{'='*80}")
    print(f"TOTAL PRODUCTS ACROSS ALL CATEGORIES: {total_products}")
    print(f"{'='*80}\n")
