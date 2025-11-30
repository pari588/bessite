#!/usr/bin/env python3
"""
CG Global - High/Low Voltage AC & DC Motors Specification Extractor
Extracts: Description, Output Power, Voltages, Frame Size, Standards
"""

import urllib.request
import re
from html.parser import HTMLParser
from html import unescape
import time

class HTMLSpecExtractor(HTMLParser):
    def __init__(self):
        super().__init__()
        self.in_table = False
        self.in_td = False
        self.in_p = False
        self.current_key = None
        self.current_value = None
        self.specs = {}
        self.buffer = []

    def handle_starttag(self, tag, attrs):
        if tag == 'table':
            self.in_table = True
        elif tag == 'td' and self.in_table:
            self.in_td = True
            self.buffer = []
        elif tag == 'p' and self.in_td:
            self.in_p = True

    def handle_endtag(self, tag):
        if tag == 'table':
            self.in_table = False
        elif tag == 'td' and self.in_table:
            self.in_td = False
            text = ''.join(self.buffer).strip()
            if text:
                if self.current_key is None:
                    self.current_key = text
                else:
                    self.current_value = text
                    self.specs[self.current_key] = self.current_value
                    self.current_key = None
                    self.current_value = None
        elif tag == 'p' and self.in_td:
            self.in_p = False

    def handle_data(self, data):
        if self.in_td:
            self.buffer.append(data.strip())

# Product links to extract
products = {
    'Air Cooled Induction Motors': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Air-Cooled-Induction-Motors-IC-6A1A1-IC-6A1A6-IC-6A6A6-CACA',
    'Water Cooled Induction Motors': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Water-Cooled-Induction-Motors-IC-8A1W7-CACW',
    'Open Air Type Induction Motor': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Open-Air-Type-Induction-Motor-IC-0A1-IC-0A6-SPDP',
    'Tube Ventilated Induction Motor': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Tube-Ventilated-Induction-Motor-IC-5A1A1-IC-5A1A6-TETV',
    'Fan Cooled Induction Motor': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Fan-Cooled-Induction-Motor-IC-4A1A1-IC-4A1A6-TEFC',
    'Energy Efficient Motors HV - N Series': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Energy-Efficient-Motors-HV-N-Series',
    'Double Cage Motor for Cement Mill': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Double-Cage-Motor-for-Cement-Mill',
    'AXELERA Process Performance Motors': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/AXELERA-Process-Performance-Motors',
    'Flame Proof Motors LV': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/Flame-Proof-Motors-Ex-db-LV',
    'IEC Cast Iron Motors': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/IEC-Cast-Iron-Motors',
    'Slip Ring Motors': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/Slip-Ring-Motors',
    'DC Motors': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/DC-Motors/DC-Motors',
    'Large DC Machines': 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/DC-Motors/Large-DC-Machines',
}

all_specs = []
output_file = '/tmp/cg_hv_lv_detailed_specs.txt'
tsv_file = '/tmp/cg_hv_lv_detailed_specs.tsv'

print("="*80)
print("CG GLOBAL - HIGH/LOW VOLTAGE AC & DC MOTORS SPECIFICATION EXTRACTOR")
print("="*80)
print()

tsv_lines = ["Product Name\tCategory\tDescription\tOutput Power\tVoltages\tFrame Size\tStandards"]

for product_name, url in products.items():
    print(f"Fetching: {product_name}")
    print("-" * 80)

    try:
        # Fetch page
        headers = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'}
        request = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(request, timeout=10) as response:
            html_content = response.read().decode('utf-8', errors='ignore')

        # Extract description
        desc_match = re.search(r'<p class="details">(.*?)</p>', html_content, re.DOTALL)
        description = ""
        if desc_match:
            # Remove HTML tags
            desc_text = desc_match.group(1)
            desc_text = re.sub(r'<[^>]+>', '', desc_text)
            desc_text = unescape(desc_text)
            desc_text = re.sub(r'\s+', ' ', desc_text).strip()
            description = desc_text[:200]

        print(f"  Description: {description[:80]}...")

        # Extract specs from table
        specs = {}
        table_matches = re.finditer(r'<tr>(.*?)</tr>', html_content, re.DOTALL)

        for match in table_matches:
            row = match.group(1)
            tds = re.findall(r'<td[^>]*>(.*?)</td>', row, re.DOTALL)

            if len(tds) >= 2:
                key = re.sub(r'<[^>]+>', '', tds[0])
                key = unescape(key).strip()
                val = re.sub(r'<[^>]+>', '', tds[1])
                val = unescape(val).strip()

                if key and val and len(val) > 2:
                    specs[key] = val
                    if any(keyword in key.lower() for keyword in ['output', 'voltage', 'frame', 'standard']):
                        print(f"  ✓ {key}: {val}")

        # Compile specifications
        output = ""
        output += f"\n{'='*80}\n"
        output += f"Product: {product_name}\n"
        output += f"URL: {url}\n"
        output += f"{'='*80}\n\n"
        output += f"Description:\n{description}\n\n"
        output += f"Specifications:\n"

        for key, val in specs.items():
            output += f"  {key}: {val}\n"

            # Try to extract specific specs for TSV
            if 'output' in key.lower() and not specs.get('output_power'):
                specs['output_power'] = val
            if 'voltage' in key.lower() and not specs.get('voltage'):
                specs['voltage'] = val
            if 'frame' in key.lower() and not specs.get('frame'):
                specs['frame'] = val
            if 'standard' in key.lower() and not specs.get('standard'):
                specs['standard'] = val

        # Save to file
        with open(output_file, 'a') as f:
            f.write(output + "\n")

        # Add to TSV
        tsv_lines.append(f"{product_name}\tHigh/Low Voltage AC & DC Motors\t{description}\t{specs.get('output_power', '')}\t{specs.get('voltage', '')}\t{specs.get('frame', '')}\t{specs.get('standard', '')}")

        print("  ✓ Extracted\n")
        time.sleep(2)  # Be respectful to server

    except Exception as e:
        print(f"  ERROR: {str(e)}\n")

# Write TSV file
with open(tsv_file, 'w', encoding='utf-8') as f:
    f.write('\n'.join(tsv_lines))

print("="*80)
print(f"✓ Text output: {output_file}")
print(f"✓ TSV output: {tsv_file}")
print("="*80)
