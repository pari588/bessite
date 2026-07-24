# Pump System - Complete Analysis

## Database Configuration

**Host:** localhost
**Database:** bombayengg
**User:** bombayengg
**Password:** oCFCrCMwKyy5jzg

Database prefix: `mx_`

---

## Database Tables Structure

### 1. **mx_pump_category** - Pump Categories
Hierarchical category structure for pumps.

| Field | Type | Key | Description |
|-------|------|-----|-------------|
| categoryPID | int(11) | PK | Primary key - Category ID |
| parentID | int(11) | - | Parent category ID (0 = root) |
| categoryTitle | varchar(250) | - | Category name |
| imageName | varchar(255) | - | Category image |
| synopsis | text | - | Category description |
| templateFile | varchar(100) | - | Custom template file |
| xOrder | int(11) | - | Display order |
| seoUri | varchar(255) | - | SEO-friendly URL path |
| status | tinyint(1) | - | 1 = Active, 0 = Inactive |

### 2. **mx_pump** - Pump Products
Main pump product table.

| Field | Type | Key | Description |
|-------|------|-----|-------------|
| pumpID | int(11) | PK | Primary key - Pump ID |
| categoryPID | int(11) | FK | Category ID (links to mx_pump_category) |
| pumpTitle | varchar(50) | - | Product name |
| seoUri | varchar(200) | - | SEO-friendly URL |
| pumpImage | varchar(200) | - | Image filename |
| pumpFeatures | text | - | Features/Description |
| kwhp | varchar(10) | - | KW/HP specification |
| supplyPhase | varchar(10) | - | Supply phase (1PH/3PH) |
| deliveryPipe | varchar(10) | - | Delivery pipe diameter |
| noOfStage | varchar(10) | - | Number of stages |
| isi | varchar(10) | - | ISI certification |
| mnre | varchar(10) | - | MNRE certification |
| pumpType | varchar(50) | - | Type of pump |
| status | tinyint(1) | - | 1 = Active, 0 = Inactive |

### 3. **mx_pump_detail** - Pump Specifications
Detailed variants/specifications for each pump.

| Field | Type | Key | Description |
|-------|------|-----|-------------|
| pumpDID | int(11) | PK | Detail ID |
| pumpID | int(11) | FK | References mx_pump.pumpID |
| categoryref | varchar(250) | - | Model code/reference |
| powerKw | double | - | Power in Kilowatts |
| powerHp | double | - | Power in Horsepower |
| supplyPhaseD | int(11) | - | Supply phase (1=1PH, 3=3PH) |
| pipePhase | double | - | Pipe diameter (mm) |
| noOfStageD | int(11) | - | Number of stages |
| headRange | double | - | Head range (meters) |
| dischargeRange | varchar(100) | - | Discharge capacity |
| mrp | varchar(100) | - | Price in INR |
| warrenty | varchar(100) | - | Warranty period |
| status | tinyint(1) | - | 1 = Active, 0 = Inactive |

---

## Category Hierarchy

### Root Categories (parentID = 0)
1. **Pump** (categoryPID: 1)
   - **Agricultural Pump** (parentID: 1)
     - **Borewell** - 1 pump
     - **CentriFugial** - 1 pump
     - **Open Well** - 1 pump
     - Testing categories (3 empty test categories)

2. **home pump** (categoryPID: 22)
   - **Residential Pumps** (parentID: 1, categoryPID: 23)
     - **Mini Pumps** - 36 pumps
     - **DMB-CMB Pumps** - 4 pumps
     - **Shallow Well Pumps** - 3 pumps
     - **3-Inch Borewell** - 3 pumps
     - **4-Inch Borewell** - 3 pumps
     - **Openwell Pumps** - 2 pumps
     - **Booster Pumps** - 2 pumps
     - **Control Panels** - 2 pumps

### Total Statistics
- **Total Categories:** 18
- **Total Active Products:** 60 pumps
- **Main Category:** Pump
- **Sub-Categories:** Residential Pumps has the most products (60/62 pumps)

---

## Image Storage Structure

### Directory Layout
```
/home/bombayengg/public_html/uploads/pump/
├── 235_235_crop_100/       # Thumbnail images (235x235 px, 100% quality)
│   ├── pump_21.webp
│   ├── pump_22.webp
│   ├── aquagold-dura-150.webp
│   ├── mini-everest.webp
│   └── ... (36 files)
│
├── 530_530_crop_100/       # Large product images (530x530 px, 100% quality)
│   ├── pump_30.webp
│   ├── pump_31.webp
│   ├── mini-everest.webp
│   ├── aquagold-dura-150.webp
│   └── ... (13 files)
│
├── tmp/                    # Temporary/processed images
│   ├── 235_235_crop_*.png
│   ├── 530_530_crop_*.png
│   ├── 50_50_ratio_*.png
│   └── ... (70+ temporary files)
│
├── crompton_images/        # Crompton product images directory
│
└── Main image files        # Original uploaded images
    ├── *.webp files
    ├── *.png files
    ├── *.jpg files
    └── service images
```

### Image Naming Convention
- **Original:** `product-name__530x530.webp` or `product-name__530x530.png`
- **Thumbnail:** Stored in `235_235_crop_100/` directory with numeric names (pump_21.webp, etc.)
- **Large:** Stored in `530_530_crop_100/` directory with numeric names
- **Temporary:** `{size}_{style}_{filename}` in `tmp/` directory

### Image Formats
- Primary: `.webp` (modern format)
- Fallback: `.png`, `.jpg` (legacy)
- Service images: `.jpeg`

---

## Frontend Pages

### Main Pump Listing Page
**File:** `/xsite/mod/pumps/x-pumps.php`
- URL: `{category-seoUri}`
- Displays products in grid (3 columns)
- Uses thumbnails: `UPLOADURL/pump/235_235_crop_100/{pumpImage}`
- Products fetched from `getPumpProducts()` function

### Pump Detail Page
**File:** `/xsite/mod/pumps/x-detail.php`
- URL: `{category-seoUri}/{product-seoUri}`
- Displays large image: `UPLOADURL/pump/530_530_crop_100/{pumpImage}`
- Shows pump specifications from mx_pump_detail
- Additional fields: KWHP, Supply Phase, Delivery Pipe, Stages, ISI, MNRE

### URL Structure
- List page: `/pump/residential-pumps/mini-pumps/`
- Detail page: `/pump/residential-pumps/mini-pumps/mini-everest-mini-pump/`

---

## Admin Management

### Pump Admin Files
- **List:** `/xadmin/mod/pump/x-pump-list.php`
- **Add/Edit:** `/xadmin/mod/pump/x-pump-add-edit.php`
- **Functions:** `/xadmin/mod/pump/x-pump.inc.php`

### Pump Category Admin Files
- **List:** `/xadmin/mod/pump-category/x-pump-category-list.php`
- **Add/Edit:** `/xadmin/mod/pump-category/x-pump-category-add-edit.php`
- **Functions:** `/xadmin/mod/pump-category/x-pump-category.inc.php`

### Admin Form Fields (Pump Add/Edit)

**Basic Information:**
- Pump Title (required)
- Category Selection (required, hierarchical dropdown)
- Features (HTML editor)
- Image (jpg|jpeg|png|gif)

**Additional Information:**
- KWHP
- Supply Phase
- Delivery Pipe
- Number of Stages
- ISI Certification
- MNRE Certification
- Pump Type

**Specifications (Dynamic - Add/Remove Rows):**
- Category Reference (Model Code)
- Power (KW)
- Power (HP)
- Supply Phase Detail
- Pipe Phase (mm)
- Number of Stages Detail
- Head Range (m)
- Discharge Range
- MRP (Price in INR)
- Warranty

---

## Key Functions

### Frontend Functions (`/xsite/mod/pumps/x-pumps.inc.php`)
```php
getPumpProducts()    // Fetches pump list by category with pagination
getPDetail($pumpID)  // Fetches detailed specifications for a pump
```

### Admin Functions (`/xadmin/mod/pump/x-pump.inc.php`)
```php
addPump()                     // Save new pump
updatePump()                  // Update existing pump
addUpdatePumpDetail($pumpID)  // Add/update pump specifications
```

### Category Functions (`/xadmin/mod/pump-category/x-pump-category.inc.php`)
```php
addCategory()               // Add new category
updateCategory()            // Update category
getParentSeouri($parentID)  // Get parent category SEO URL
getCatSeoUri(...)           // Generate SEO URL for category
getCatTemplates()           // Get available category templates
```

---

## API Endpoints

All admin operations go through POST requests with `xAction` parameter:

```
POST /xadmin/mod/pump/x-pump.inc.php
  xAction=ADD|UPDATE|mxDelFile

POST /xadmin/mod/pump-category/x-pump-category.inc.php
  xAction=ADD|UPDATE|mxDelFile
```

---

## Upload Directories Configuration

In `/xadmin/mod/pump/x-pump.inc.php` (line 145):
```php
setModVars(array(
    "TBL" => "pump",
    "PK" => "pumpID",
    "UDIR" => array("pumpImage" => "pump")  // Uploads go to /uploads/pump/
));
```

In `/xadmin/mod/pump-category/x-pump-category.inc.php` (line 136):
```php
setModVars(array(
    "TBL" => "pump_category",
    "PK" => "categoryPID",
    "UDIR" => array(
        "imageName" => "pump_category",
        "categoryDelightboxImage" => "category_delightbox_slider"
    )
));
```

---

## Image Processing

### Current Implementation
- Images stored as `.webp` format
- Two size variants:
  1. **Thumbnail (235x235)** - For listing pages
  2. **Large (530x530)** - For detail pages
- Quality: 100% JPEG quality maintained
- Temp files created during processing (in `/uploads/pump/tmp/`)

### Image Resize Logic
- Uses `convert` command (ImageMagick) for resizing
- Crop or scale depending on requirements
- Numbered naming for processed images (pump_21.webp, etc.)

---

## Summary Table

| Aspect | Details |
|--------|---------|
| **Total Pumps** | 60 active products |
| **Total Categories** | 18 (3 in Agriculture, 8 in Residential, 3 test) |
| **Main Products** | Mini Pumps (36), followed by DMB-CMB (4), Shallow Well (3) |
| **Image Storage** | `/uploads/pump/` directory with subdirectories for sizes |
| **Image Formats** | WebP (primary), PNG & JPG (fallback) |
| **Database** | bombayengg (host: localhost) |
| **Table Prefix** | `mx_` |
| **Admin Path** | `/xadmin/` |
| **Site Path** | `/xsite/` |

---

## Notes for Development

1. **SEO URLs** are automatically generated from titles and maintain hierarchy
2. **Categories support 3+ levels** of nesting (parentID system)
3. **Each pump can have multiple variants** stored in `mx_pump_detail`
4. **Image naming** follows pattern with size and quality indicators
5. **Thumbnail generation** happens on image upload via admin
6. **Batch processing** possible using CLI scripts (see: `/generate_pump_thumbnails.php`)
7. **WebP conversion** preferred for modern browsers with fallback support
