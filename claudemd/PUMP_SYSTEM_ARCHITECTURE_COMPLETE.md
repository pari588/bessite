# Pump Management System - Complete Architecture Documentation

## 1. DATABASE CONFIGURATION

### Credentials
- **Host:** localhost
- **Database:** bombayengg
- **Username:** bombayengg
- **Password:** oCFCrCMwKyy5jzg
- **Table Prefix:** mx_ (and _live_ for some operations)

### Config File
- **Location:** `/home/bombayengg/public_html/config.inc.php`
- **Environment:** LIVE (Production)
- **Folder:** '' (root)
- **SITEURL:** https://www.bombayengg.com
- **ADMINDIR:** xadmin
- **SITEDIR:** xsite
- **UPLOADDIR:** uploads

---

## 2. DATABASE SCHEMA

### A. PUMP TABLE (mx_pump)
Main pump product information table.

| Field | Type | Null | Key | Default | Notes |
|-------|------|------|-----|---------|-------|
| pumpID | int(11) | NO | PRI | AUTO_INCREMENT | Primary Key |
| categoryPID | int(11) | NO | - | 0 | Foreign Key to pump_category |
| pumpTitle | varchar(50) | YES | - | NULL | Product name (max 50 chars) |
| seoUri | varchar(200) | YES | - | NULL | SEO-friendly URL slug |
| pumpImage | varchar(200) | YES | - | NULL | Image filename |
| pumpFeatures | text | YES | - | NULL | HTML content with features |
| kwhp | varchar(10) | YES | - | NULL | Power rating (e.g., "1HP", "2HP") |
| supplyPhase | varchar(10) | YES | - | NULL | Phase (1-phase, 3-phase) |
| deliveryPipe | varchar(10) | YES | - | NULL | Delivery pipe size |
| noOfStage | varchar(10) | YES | - | NULL | Number of pump stages |
| isi | varchar(10) | YES | - | NULL | ISI certification status |
| mnre | varchar(10) | YES | - | NULL | MNRE certification status |
| pumpType | varchar(50) | YES | - | NULL | Type classification (Residential, Agricultural, etc.) |
| status | tinyint(1) | NO | - | 1 | 1=Active, 0=Inactive |

**Current Record Count:** 89 active pumps

---

### B. PUMP_DETAIL TABLE (mx_pump_detail)
Specifications and detailed information for each pump variant.

| Field | Type | Null | Key | Default | Notes |
|-------|------|------|-----|---------|-------|
| pumpDID | int(11) | NO | PRI | AUTO_INCREMENT | Primary Key |
| pumpID | int(11) | NO | - | 0 | Foreign Key to pump table |
| categoryref | varchar(250) | YES | - | NULL | Product/Category reference code |
| powerKw | double | YES | - | NULL | Power in Kilowatts |
| powerHp | double | YES | - | NULL | Power in Horsepower |
| supplyPhaseD | int(11) | YES | - | NULL | Supply phase (numeric) |
| pipePhase | double | YES | - | NULL | Pipe diameter in millimeters |
| noOfStageD | int(11) | YES | - | NULL | Number of stages |
| headRange | double | YES | - | NULL | Pumping head in meters |
| dischargeRange | varchar(100) | YES | - | NULL | Discharge capacity |
| mrp | varchar(100) | YES | - | NULL | Maximum Retail Price |
| warrenty | varchar(100) | YES | - | NULL | Warranty period |
| status | tinyint(1) | NO | - | 1 | 1=Active, 0=Inactive |

**Relationship:** One pump can have multiple detailed specifications (variants/models)

---

### C. PUMP_CATEGORY TABLE (mx_pump_category)
Hierarchical category structure for organizing pumps.

| Field | Type | Null | Key | Default | Notes |
|-------|------|------|-----|---------|-------|
| categoryPID | int(11) | NO | PRI | AUTO_INCREMENT | Primary Key |
| parentID | int(11) | NO | - | 0 | Parent category ID (0=root) |
| categoryTitle | varchar(250) | YES | - | NULL | Category name |
| imageName | varchar(255) | YES | - | NULL | Category image |
| synopsis | text | YES | - | NULL | Category description |
| templateFile | varchar(100) | YES | - | NULL | Custom template file (if any) |
| xOrder | int(11) | NO | - | 0 | Sort order |
| seoUri | varchar(255) | YES | - | NULL | SEO-friendly URL |
| langCode | varchar(4) | YES | - | NULL | Language code |
| langChild | varchar(150) | YES | - | NULL | Language-specific child info |
| parentLID | int(11) | YES | - | 0 | Parent language ID |
| status | tinyint(1) | NO | - | 1 | 1=Active, 0=Inactive |

**Sample Categories:**
- Pump (ID: 1)
- Agricultural Pump (ID: 2)
- Centrifugal Monoset (ID: 4)
- Residential Pumps (ID: 23)
- Mini Pumps (ID: 24)
- DMB-CMB Pumps (ID: 25)
- Shallow Well Pumps (ID: 26)
- 3-Inch Borewell (ID: 27)
- 4-Inch Borewell (ID: 28)

---

## 3. FRONTEND (XSITE) - CUSTOMER-FACING

### File Structure
```
xsite/mod/pumps/
├── x-pumps.php          # Main pump listing page
├── x-pumps.inc.php      # Pump functions & includes
└── x-detail.php         # Individual pump detail page
```

### Key Files

#### 3.1 x-pumps.php (Listing Page)
**Route:** `/pumps/` (category-based routing)
**Function:** Displays paginated list of pumps in a category

**Features:**
- Sidebar navigation (category tree)
- Grid layout (3 columns on desktop, responsive)
- Product cards showing:
  - Thumbnail image (235x235 px)
  - Pump title
  - First 20 characters of features
  - "Know More" button

**Key Logic:**
```php
$motorProductsArr = getPumpProducts(); // Get products for selected category
// Link format: {SITEURL}/{category-seoUri}/{pump-seoUri}/
```

#### 3.2 x-pumps.inc.php (Backend Logic)
**Location:** `/home/bombayengg/public_html/xsite/mod/pumps/x-pumps.inc.php`

**Key Functions:**

**a) getPumpProducts()**
- Fetches pump list for active categories
- Requires: $ARRCAT (array of category IDs) and $MXTOTREC (for pagination)
- Returns: Array with:
  - `strPaging`: Pagination HTML
  - `productList`: Array of pumps with fields:
    - seoUri (pump URL slug)
    - pumpTitle (pump name)
    - pumpFeatures (HTML content)
    - pumpImage (filename)
    - cseoUri (category URL slug)

**b) getPDetail($pumpID)**
- Fetches detailed specifications from pump_detail table
- Returns: Array of all specifications for a pump
- Used in detail page for specifications table

#### 3.3 x-detail.php (Product Detail Page)
**Route:** `/{category-seoUri}/{pump-seoUri}/`
**Function:** Display full pump details with specifications

**Layout:**
- Page header with breadcrumb
- Two-column layout:
  - Left: Large product image (530x530 px)
  - Right:
    - Product title
    - Features (HTML content)
    - Additional info list (KwHp, Supply Phase, Delivery Pipe, Stages, ISI, MNRE, Type)

**Specifications Table:**
- Displayed if pump has detail records
- Columns: Catref, Power(KW), Power(HP), Supply Phase, Pipe Size, Stages, Head Range, Discharge, MRP, Warranty
- Dynamically generated from pump_detail table

**Contact Button:**
- "Contact us" button triggers pump inquiry modal
- JavaScript function: `volid(0)` - opens contact form with pump context

---

## 4. ADMIN PANEL (XADMIN) - MANAGEMENT

### File Structure
```
xadmin/mod/
├── pump/                          # Pump CRUD operations
│   ├── x-pump-list.php           # Pump listing
│   ├── x-pump-add-edit.php       # Add/Edit form
│   └── x-pump.inc.php            # Backend operations
└── pump-category/                 # Category management
    ├── x-pump-category-list.php
    ├── x-pump-category-add-edit.php
    └── x-pump-category.inc.php
```

### 4.1 x-pump-list.php (Admin Listing)
**Location:** `/home/bombayengg/public_html/xadmin/mod/pump/x-pump-list.php`

**Features:**
- Search by: ID, Title, KwHp, Supply Phase, Delivery Pipe, No. of Stage, ISI, MNRE, Pump Type
- Paginated table display
- Columns shown:
  - #ID
  - Image thumbnail
  - Pump Title (clickable)
  - Category Name
  - KwHp
  - Supply Phase
  - Delivery Pipe
  - No of Stage
  - ISI
  - MNRE
  - Residential Pump Type

**Actions:** Edit, Delete, View (via getMAction helper)

---

### 4.2 x-pump-add-edit.php (Admin Form)
**Location:** `/home/bombayengg/public_html/xadmin/mod/pump/x-pump-add-edit.php`

**Form Sections:**

**Section 1: Basic Information**
- Pump Title (required, text)
- Category (required, dropdown with hierarchy)
- Features (HTML editor, basic toolbar, 150px height)
- Image (file upload - jpg, jpeg, png, gif)

**Section 2: Additional Information**
- KwHp (text)
- Supply Phase (text)
- Delivery Pipe (text)
- No. of Stage (text)
- ISI (text)
- MNRE (text)
- Pump Type (text)

**Section 3: Specifications (Grid)**
- Multiple rows for variants/models
- Add/Delete rows functionality
- Fields per row:
  - pumpDID (hidden)
  - categoryref (reference code)
  - powerKw (kilowatts)
  - powerHp (horsepower)
  - supplyPhaseD (phase number)
  - pipePhase (pipe size mm)
  - noOfStageD (number of stages)
  - headRange (head in meters)
  - dischargeRange (discharge capacity)
  - mrp (price)
  - warrenty (warranty)

---

### 4.3 x-pump.inc.php (Backend Operations)
**Location:** `/home/bombayengg/public_html/xadmin/mod/pump/x-pump.inc.php`

**Functions:**

**a) addPump()**
- Creates new pump product
- Input validation & sanitization:
  - `cleanTitle()` - HTML entity encoding
  - `cleanHtml()` - HTML sanitization
  - `mxGetFileName()` - File upload handling
  - `makeSeoUri()` - Generate URL slug from title
- Creates record in mx_pump table
- Gets insertID and calls addUpdatePumpDetail()
- Returns JSON response

**b) updatePump()**
- Updates existing pump
- Same validation as addPump
- Deletes old specifications (pump_detail records)
- Calls addUpdatePumpDetail() to insert new specs
- Updates related contact form references
- Returns JSON response

**c) addUpdatePumpDetail($pumpID)**
- Inserts multiple specification rows
- Processes array data from form grid
- Iterates through specification rows
- Validates at least one spec field is filled
- Inserts each row into pump_detail table

**File Deletion:**
- Handles via `mxDelFile()` with parameters:
  - dir: "pump"
  - tbl: "pump"
  - pk: "pumpID"

---

## 5. IMAGE HANDLING

### Image Directories
```
uploads/pump/
├── [original image files]
├── 235_235_crop_100/          # Thumbnail images (for listings)
├── 530_530_crop_100/          # Large images (for detail pages)
└── crompton_images/           # Crompton product images
```

### Image Sizes
- **Listings:** 235x235 pixels (thumbnails)
- **Details:** 530x530 pixels (large)
- **Format:** WebP (optimized)

### Image Processing
- Images uploaded through admin form
- Stored with filename in database
- Auto-generated thumbnails via image generation scripts
- Two size variants created per upload

---

## 6. DATA FLOW DIAGRAM

### Frontend Flow
```
URL: /category-seoUri/pump-seoUri/
  ↓
Load x-detail.php
  ↓
Get pumpID from URL routing
  ↓
Query mx_pump table (main details)
  ↓
Query mx_pump_detail table (specifications)
  ↓
Render HTML with images from uploads/pump/530_530_crop_100/
  ↓
Display specifications table (if data exists)
  ↓
Show "Contact us" button → pump inquiry form
```

### Admin Flow
```
Admin visits /xadmin/mod/pump/
  ↓
x-pump-list.php displays all pumps
  ↓
Admin clicks "Add" or "Edit"
  ↓
x-pump-add-edit.php loads form
  ↓
Form data submitted to x-pump.inc.php
  ↓
addPump() or updatePump() executed
  ↓
Data inserted/updated in mx_pump
  ↓
addUpdatePumpDetail() inserts specifications
  ↓
JSON response returned
  ↓
Redirect to list or detail view
```

---

## 7. KEY RELATIONSHIPS

```
mx_pump_category
    ↓ (parentID = 0 for root categories)
    └─→ mx_pump_category (hierarchy)

mx_pump_category
    ↑ (categoryPID)
    └─ mx_pump (categoryPID)

mx_pump
    ↑ (pumpID)
    └─ mx_pump_detail (pumpID)
```

---

## 8. SECURITY & VALIDATION

### Input Validation Functions
- `cleanTitle()` - Entity encoding for text fields
- `cleanHtml()` - HTML sanitization for rich text
- `makeSeoUri()` - URL-safe slug generation
- `mxGetFileName()` - Secure file handling

### Database
- Prepared statements with parameterized queries
- Type binding (i=int, s=string, d=double)
- CSRF token validation via $MXSET["TOKENID"]

### File Uploads
- Extension validation (jpg, jpeg, png, gif)
- Server-side file handling via mxGetFileName()
- Directory isolation (uploads/pump/)

---

## 9. PAGINATION

### Implementation
- Uses `getPaging()` function from core
- Paging parameters in URL query string
- Records per page configured in core settings
- Pagination HTML generated in getPumpProducts()

### Database Query
- Counts total records matching filter
- Applies LIMIT clause via `mxQryLimit()`
- Shows first N records per page

---

## 10. SEO URLS

### Structure
- **Category URL:** `/category-seoUri/`
- **Product URL:** `/category-seoUri/product-seoUri/`
- **Pattern:** Slug-based, human-readable
- **Generation:** `makeSeoUri()` function converts titles to slugs

### Benefits
- Search engine friendly
- User-friendly URLs
- Easy sharing

---

## 11. TEMPLATE SYSTEM

### Page Structure
```
Header (getPageHeader())
    ├── Navigation
    ├── Logo
    └── Search

Main Content
    ├── Sidebar (getSideNav()) → Category tree
    └── Main Area
        ├── Product Grid (listings)
        └── Product Details + Specs (detail pages)

Footer (getPageFooter())
    └── Links, Copyright, etc.
```

### Template Variables
- `$TPL->data` - Current product data
- `$TPL->dataM` - Master/category data
- `$TPL->pageType` - (view, edit, add)
- `SITEURL` - Base URL constant
- `UPLOADURL` - Upload directory URL

---

## 12. INQUIRY INTEGRATION

### Pump Inquiry Module
- **Table:** mx_pump_inquiry
- **Fields:** Contact name, email, phone, message, pump selection
- **Trigger:** "Contact us" button on detail page
- **Related:** js/x-pump-inquiry.inc.js handles form submission

### Contact Form Link
- Passes pumpID via hidden field
- Passes categoryTitle via AJAX
- Triggers inquiry submission workflow

---

## 13. IMPORTANT NOTES

1. **Image Storage:** All pump images stored in uploads/pump/ with two sizes:
   - Thumbnails: 235x235_crop_100
   - Large: 530x530_crop_100

2. **Specifications:** Multiple specifications per pump stored in separate table for flexibility

3. **Categories:** Hierarchical structure supports nested categories (parent-child relationships)

4. **Status Flag:** All tables use status (1=active, 0=inactive) for soft delete

5. **SEO URI:** Both pumps and categories have independent URL slugs

6. **Mobile Detection:** Config includes Mobile_Detect for responsive handling

---

## 14. USEFUL COMMANDS

### Database Queries

**Get all pumps in a category:**
```sql
SELECT p.pumpID, p.pumpTitle, p.seoUri
FROM mx_pump p
WHERE p.status=1 AND p.categoryPID=?
ORDER BY p.pumpID DESC;
```

**Get pump with specifications:**
```sql
SELECT p.*, COUNT(d.pumpDID) as spec_count
FROM mx_pump p
LEFT JOIN mx_pump_detail d ON p.pumpID = d.pumpID
WHERE p.pumpID=? AND p.status=1
GROUP BY p.pumpID;
```

**Get category tree:**
```sql
SELECT * FROM mx_pump_category
WHERE status=1
ORDER BY parentID, xOrder, categoryTitle;
```

---

## 15. FILES REFERENCE

### Frontend (xsite) Paths
- Pumps listing: `/xsite/mod/pumps/x-pumps.php`
- Pump detail: `/xsite/mod/pumps/x-detail.php`
- Functions: `/xsite/mod/pumps/x-pumps.inc.php`

### Admin (xadmin) Paths
- Pump list: `/xadmin/mod/pump/x-pump-list.php`
- Pump edit: `/xadmin/mod/pump/x-pump-add-edit.php`
- Functions: `/xadmin/mod/pump/x-pump.inc.php`
- Category list: `/xadmin/mod/pump-category/x-pump-category-list.php`
- Category edit: `/xadmin/mod/pump-category/x-pump-category-add-edit.php`
- Category functions: `/xadmin/mod/pump-category/x-pump-category.inc.php`

### Config & Core
- Database config: `/config.inc.php`
- Core functions: `/core/core.inc.php`
- Site includes: `/xsite/core-site/common.inc.php`
- Admin includes: `/xadmin/core-admin/common.inc.php`

---

## 16. API ENDPOINTS (Form-based)

### Pump Operations
- **Action:** POST to `/xadmin/mod/pump/x-pump.inc.php`
- **Parameters:**
  - `xAction`: ADD | UPDATE | mxDelFile
  - `pumpID`: (for UPDATE/DELETE)
  - Form data: pumpTitle, categoryPID, pumpFeatures, etc.

### Response Format
- JSON: `{"err": 0, "param": "id=X"}` (success)
- JSON: `{"err": 1}` (failure)

---

## Summary Statistics

- **Total Pumps:** 89 active records
- **Categories:** 9 active pump categories
- **Database Prefix:** mx_ (with variants _live_)
- **Image Formats:** WebP (optimized), PNG/JPG (original uploads)
- **Admin Modules:** Pump + Pump Category management
- **Frontend Modules:** Pumps listing + Detail page

---

*Last Updated: 2025-11-08*
*System Version: 2.8*
*Environment: LIVE (Production)*
