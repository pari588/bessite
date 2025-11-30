# CG GLOBAL MOTORS - DATA EXTRACTION REPORT

## Executive Summary

Successfully extracted comprehensive product catalog from CG Global's High/Low Voltage AC & DC Motors section. The extraction captured **33 products** across **7 major categories** with complete product information including names, descriptions, images, and product links.

---

## Extraction Results

### Statistics

| Metric | Value |
|--------|-------|
| **Total Categories** | 7 |
| **Total Products Extracted** | 33 |
| **High Voltage Motors** | 7 products |
| **Low Voltage Motors** | 10 products |
| **Energy Efficient Motors** | 3 products |
| **Hazardous Area Motors (LV)** | 3 products |
| **DC Motors** | 2 products |
| **Hazardous Area Motors (HV)** | 4 products |
| **Special Application Motors** | 4 products |
| **Data Files Generated** | 6 files |

---

## Category Breakdown

### 1. High Voltage Motors (7 products)
**Category Description:** CG offers Squirrel Cage motors in horizontal (IMB3), vertical (IMV1) and inclined (IMB5, IMV5) mounting options.

**Products:**
1. Air Cooled Induction Motors - IC 6A1A1, IC 6A1A6, IC 6A6A6 (CACA)
2. Double Cage Motor for Cement Mill
3. Water Cooled Induction Motors - IC 8A1W7 (CACW)
4. Open Air Type Induction Motor - IC 0A1, IC 0A6 (SPDP)
5. Tube Ventilated Induction Motor - IC 5A1A1, IC 5A1A6 (TETV)
6. Fan Cooled Induction Motor - IC 4A1A1, IC 4A1A6 (TEFC)
7. Energy Efficient Motors HV - N Series

### 2. Low Voltage Motors (10 products)
**Category Description:** Industrial motors designed to facilitate, control and optimise processes.

**Products:**
1. AXELERA Process Performance Motors
2. Flame Proof Motors Ex 'db' (LV)
3. SMARTOR–CG Smart Motors
4. Non Sparking Motor Ex 'nA' / Ex 'ec' (LV)
5. Increased Safety Motors Ex 'eb' (LV)
6. Cast Iron enclosure motors (NEMA)
7. Aluminum enclosure motors (NEMA)
8. Cast Iron enclosure motors - Safe Area (IEC)
9. Aluminium enclosure motors - Safe area (IEC)
10. Slip Ring Motors (LV)

### 3. Energy Efficient Motors (3 products)
**Category Description:** Motors enabling facilities to minimize production costs and stay competitive.

**Products:**
1. International Efficiency IE2 /IE3-Apex series
2. Super Premium IE4 Efficiency –Apex Series
3. Totally Enclosed Fan Cooled Induction Motor - NG Series

### 4. Motors for Hazardous Area (LV) (3 products)
**Category Description:** Largest manufacturing range of low voltage motors suitable for hazardous area.

**Products:**
1. Flame Proof Motors Ex 'd' (LV)
2. Increased Safety Motors Ex 'e' (LV)
3. Non Sparking Motor Ex 'n' (LV)

### 5. DC Motors (2 products)
**Category Description:** World Class DC Motors in IEC frame up to 710, for constant and variable speed requirements.

**Products:**
1. Large DC Machines
2. DC Motors (2.2 kW to 1500 kW, IEC 100-630)

### 6. Motors for Hazardous Areas (HV) (4 products)
**Category Description:** High voltage motors for hazardous area applications.

**Products:**
1. Flame Proof Large Motors Ex 'd' (HV)
2. Increased Safety Motors Ex 'e' (HV)
3. Non Sparking Motor Ex 'n' (HV)
4. Pressurized Motor Ex 'p' (HV)

### 7. Special Application Motors (4 products)
**Category Description:** Motors for specific applications including brake motors, oil well pumps, and mill motors.

**Products:**
1. Double Cage Motor for Cement Mill
2. Brake Motors (GD Series)
3. Oil Well Pump Motor
4. Re-Rolling Mill Motor

---

## Data Structure

### JSON Format
Each product record contains:
```json
{
  "name": "Product Name",
  "link": "https://www.cgglobal.com/...",
  "image": "https://www.cgglobal.com/admin/uploads/..."
}
```

Category structure:
```json
{
  "category": "Category Name",
  "description": "Category description text",
  "products": [...],
  "product_count": 7
}
```

---

## Files Generated

| File Name | Size | Description |
|-----------|------|-------------|
| **cg_motors_extracted_v2.json** | 13K | Primary JSON data file with complete catalog |
| **cg_motors_extracted.txt** | 12K | Human-readable text format |
| **CG_MOTORS_COMPLETE_CATALOG.md** | 17K | Comprehensive markdown documentation |
| **CG_MOTORS_QUICK_SUMMARY.txt** | 7.1K | Quick reference guide |
| **product_details_sample.json** | 11K | Sample detailed specifications |
| **EXTRACTION_REPORT.md** | This file | Extraction summary and methodology |

**Location:** `/home/bombayengg/public_html/`

---

## Extraction Methodology

### Step 1: Page Download
Used `wget` with `--no-check-certificate` flag to download category pages:
- High Voltage Motors
- Low Voltage Motors
- Energy Efficient Motors
- Motors for Hazardous Area (LV)
- DC Motors
- Motors for Hazardous Areas (HV)
- Special Application Motors

### Step 2: HTML Parsing
Created Python script (`extract_cg_motors_v2.py`) to:
- Parse HTML structure
- Identify product containers using regex patterns
- Extract product names from H4 tags
- Extract product links from anchor tags
- Extract product images from img tags
- Extract category descriptions

### Step 3: Data Validation
- Filtered out navigation elements
- Verified product URLs contain motor-related paths
- Removed duplicate entries
- Validated image URLs

### Step 4: Sample Detail Extraction
Downloaded sample product pages to extract:
- Detailed specifications
- Technical features
- Application information
- Standards compliance

### Step 5: Output Generation
Generated multiple output formats:
- Structured JSON for database import
- Plain text for easy reading
- Markdown for documentation
- Quick reference summary

---

## Data Quality

### Coverage
- ✅ All 7 categories successfully extracted
- ✅ All products have names
- ✅ All products have direct links
- ✅ All products have images
- ✅ Category descriptions captured

### Accuracy
- Product names extracted verbatim from source
- URLs are direct links to product detail pages
- Images are hosted URLs from CG Global's server
- Category descriptions are official text from pages

### Completeness
- **Product Names:** 100% captured
- **Product Links:** 100% captured
- **Product Images:** 100% captured
- **Category Descriptions:** 100% captured
- **Detailed Specifications:** Sample set extracted (4 products)

---

## Technical Specifications Captured

### Sample: Air Cooled Induction Motors
- Shaft Height IMB3: 315 to 1400 mm
- Frame Size IMV1: 740 to 2500
- Insulation Class: F with VPI
- Degree of Protection: IP55
- Standards: IEC 60034 / IS 325
- Cooling: IC6A1A1/ IC6A6A6/ IC6A1A6
- Rotor Construction: Squirrel Cage/ slip ring

### Sample: IE4 Super Premium Motors
- Highest efficiency for energy savings
- CSA approved lab testing
- 0.2 class instrumentation
- Same frame size for easy replacement
- Extended motor life

### Sample: AXELERA Motors
- Durable, rugged construction
- High torque output
- Advanced sealing technology
- Corrosion-resistant coatings
- VSD compatibility

### Sample: DC Motors
- Power Range: 2.2 kW to 1500 kW
- Frame: IEC 100 to 630
- Laminated Yoke Construction
- Easy fitting and retro-fitting

---

## Product Categories Analysis

### By Protection Type
- **Flame Proof (Ex 'd'/'db'):** 3 products (2 LV, 1 HV)
- **Increased Safety (Ex 'e'/'eb'):** 3 products (2 LV, 1 HV)
- **Non Sparking (Ex 'n'/'nA'/'ec'):** 3 products (2 LV, 1 HV)
- **Pressurized (Ex 'p'):** 1 product (HV)

### By Cooling Method
- **Air Cooled (CACA):** 1 product
- **Water Cooled (CACW):** 1 product
- **Fan Cooled (TEFC):** 2 products
- **Tube Ventilated (TETV):** 1 product
- **Open Air (SPDP):** 1 product

### By Efficiency Class
- **IE2:** 1 product
- **IE3:** 1 product
- **IE4:** 1 product
- **High Voltage Energy Efficient:** 1 product

### By Enclosure Type
- **Cast Iron:** 2 products
- **Aluminum/Aluminium:** 2 products
- **Various (HV Motors):** 7 products

---

## Use Cases for Extracted Data

### 1. Database Import
The JSON files can be directly imported into:
- MySQL/PostgreSQL databases
- MongoDB collections
- Elasticsearch indexes
- Product catalog systems

### 2. E-commerce Integration
Data structure supports:
- Product listing pages
- Category navigation
- Search functionality
- Product detail pages
- Image galleries

### 3. Comparison Tools
Enable creation of:
- Motor selection guides
- Specification comparison tables
- Application-based recommendations
- Efficiency calculators

### 4. Documentation
Support for:
- Technical documentation
- Product catalogs
- Sales presentations
- Training materials

---

## Key Terminology Reference

### Cooling Codes (IC)
- **IC 0A1/0A6 (SPDP)** - Screen Protected Drip Proof
- **IC 4A1A1/4A1A6 (TEFC)** - Totally Enclosed Fan Cooled
- **IC 5A1A1/5A1A6 (TETV)** - Totally Enclosed Tube Ventilated
- **IC 6A1A1/6A1A6/6A6A6 (CACA)** - Closed Air Circuit Air Cooled
- **IC 8A1W7 (CACW)** - Closed Air Circuit Water Cooled

### Explosion Protection (Ex)
- **Ex 'd'/'db'** - Flameproof enclosure
- **Ex 'e'/'eb'** - Increased safety
- **Ex 'n'/'nA'** - Non-sparking
- **Ex 'p'** - Pressurized enclosure
- **Ex 'ec'** - Enclosed break

### Efficiency (IE)
- **IE2** - High Efficiency
- **IE3** - Premium Efficiency
- **IE4** - Super Premium Efficiency

### Mounting (IM)
- **IMB3** - Horizontal with feet
- **IMV1** - Vertical shaft down
- **IMB5/IMV5** - Flange mounting

---

## Recommendations for Next Steps

### 1. Complete Detail Extraction
Download and parse all individual product pages to extract:
- Complete specification tables
- Performance curves
- Dimension drawings
- Application notes
- Installation guidelines

### 2. Database Schema Design
Create normalized database schema with tables for:
- Products
- Categories
- Specifications
- Images
- Applications
- Standards

### 3. Data Enhancement
Add additional information:
- Pricing (if available)
- Availability status
- Lead times
- Related products
- Accessories

### 4. Search Optimization
Implement search features:
- Full-text search across all fields
- Faceted search by specifications
- Filter by category, efficiency, protection type
- Power range filters

### 5. API Development
Create REST API endpoints:
- GET /categories
- GET /products
- GET /products/:id
- GET /products/search?q=
- GET /products/filter?category=&efficiency=

---

## Conclusion

Successfully extracted comprehensive motor product catalog from CG Global website with 100% coverage of accessible product listings. The data is structured, validated, and ready for integration into various systems including databases, e-commerce platforms, and product comparison tools.

All extracted data maintains original product information accuracy and includes direct links to detailed product pages for additional specification retrieval.

---

**Report Generated:** November 9, 2025
**Extraction Tool:** Custom Python scripts with wget
**Source:** https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors
**Data Format:** JSON, TXT, Markdown
**Files Location:** /home/bombayengg/public_html/
