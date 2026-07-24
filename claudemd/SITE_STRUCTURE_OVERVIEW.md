# Bombay Engineering Syndicate - Complete Site Structure Overview

**Last Updated:** November 21, 2025

---

## 1. DIRECTORY STRUCTURE

```
/home/bombayengg/public_html/
├── config.inc.php                # Main configuration file (DB credentials, URLs, constants)
├── core/                         # Core application logic
│   ├── core.inc.php             # Main bootstrap file
│   ├── db.inc.php               # Database connection class
│   ├── common.inc.php           # Shared utility functions (core-wide)
│   ├── brevo.inc.php            # Email service integration
│   ├── form.inc.php             # Form handling
│   ├── file.inc.php             # File operations
│   ├── image.inc.php            # Image processing
│   ├── validate.inc.php         # Form validation
│   ├── formating.inc.php        # Data formatting functions
│   ├── paging.class.inc.php     # Pagination class
│   ├── export.inc.php           # Export functionality
│   ├── jwt.inc.php              # JWT token handling
│   └── js/                       # JavaScript utilities
├── xsite/                        # FRONTEND (Customer-facing website)
│   ├── index.php                # Main frontend router
│   ├── core-site/               # Frontend-specific core files
│   │   ├── tpl.class.inc.php   # Template management class
│   │   ├── common.inc.php       # Frontend utility functions
│   │   └── pump-schema.inc.php  # JSON-LD schema for pumps
│   ├── inc/
│   │   └── site.inc.php         # Frontend routing & category logic
│   ├── mod/                     # Frontend modules
│   │   ├── pumps/               # Pump products
│   │   │   ├── x-pumps.php      # Pump listing page
│   │   │   ├── x-pumps.inc.php  # Pump functions (getPumpProducts, getPDetail)
│   │   │   └── x-detail.php     # Individual pump detail page
│   │   ├── motors/              # Motor products
│   │   │   ├── x-motors.php
│   │   │   ├── x-motors.inc.php
│   │   │   └── x-detail.php
│   │   ├── pump-inquiry/        # Pump inquiry form
│   │   │   ├── x-pump-inquiry.php
│   │   │   ├── x-pump-inquiry-inc.php
│   │   │   └── inc/js/x-pump-inquiry.inc.js
│   │   ├── product-inquiry/     # Product inquiry form
│   │   ├── knowledge-center/    # Blog/Knowledge articles
│   │   ├── page/                # Static pages
│   │   ├── home/                # Home page
│   │   ├── header.php           # Global header template
│   │   ├── footer.php           # Global footer template
│   │   └── driver/              # Driver portal
│   ├── css/                     # Stylesheets
│   ├── vendors/                 # Third-party libraries
│   ├── images/                  # Static images
│   └── sitemap.xml              # SEO sitemap
├── xadmin/                       # BACKEND (Admin panel)
│   ├── index.php                # Main admin router
│   ├── core-admin/              # Admin-specific core files
│   │   ├── tpl.class.inc.php   # Admin template management
│   │   ├── common.inc.php       # Admin utility functions
│   │   ├── ajax.inc.php         # AJAX endpoints
│   │   ├── settings.inc.php     # Admin settings
│   │   ├── header.php           # Admin header
│   │   ├── footer.php           # Admin footer
│   │   ├── x-login.php          # Login page
│   │   └── x-404.php            # 404 page
│   ├── inc/
│   │   └── site.inc.php         # Admin routing & menu logic
│   ├── mod/                     # Admin modules (36 total)
│   │   ├── pump/                # Pump management
│   │   │   ├── x-pump-list.php  # List pumps
│   │   │   ├── x-pump-add-edit.php # Add/Edit form
│   │   │   └── x-pump.inc.php   # Pump functions
│   │   ├── pump-category/       # Pump category management
│   │   ├── motor/               # Motor management
│   │   ├── motor-category/      # Motor category management
│   │   ├── pump-inquiry/        # View pump inquiries (admin)
│   │   ├── product-inquiry/     # View product inquiries (admin)
│   │   ├── contact-us/          # View contact messages
│   │   ├── knowledge-center/    # Manage knowledge articles
│   │   ├── page/                # Manage static pages
│   │   ├── user/                # User management
│   │   ├── dashboard/           # Admin dashboard
│   │   └── [others]             # Employee, driver, leave, expense modules
│   ├── css/                     # Admin stylesheets
│   └── images/                  # Admin images
├── uploads/                     # User-uploaded files
│   ├── pump/                    # Pump images
│   │   ├── {filename}.webp      # Original (main image)
│   │   ├── 530_530_crop_100/    # Large size for detail pages
│   │   └── 235_235_crop_100/    # Thumbnails for listing
│   ├── motor/                   # Motor images
│   ├── knowledge-center/        # Knowledge center images
│   └── [others]                 # Other category images
├── lib/                         # Third-party libraries
│   ├── mobile-detect.php        # Mobile detection
│   ├── jwt/                     # JWT library
│   ├── js/ckeditor/             # CKEditor WYSIWYG
│   └── [others]
├── vendor/                      # Composer packages
│   ├── mpdf/                    # PDF generation
│   ├── psr/                     # PSR standards
│   └── [others]
├── claudemd/                    # Documentation (markdown files)
├── claudetodo/                  # Historical todos/notes
└── database_backups/            # Database backup files
```

---

## 2. CONFIGURATION FILES

### 2.1 config.inc.php
**Location:** `/home/bombayengg/public_html/config.inc.php`

**Key Configurations:**
```php
MXCON = 'LIVE'                          // Environment: LIVE or MX
FOLDER = ''                             // URL path prefix
DBHOST = 'localhost'                    // Database host
DBNAME = 'bombayengg'                   // Database name
DBUSER = 'bombayengg'                   // Database user
DBPASS = 'oCFCrCMwKyy5jzg'             // Database password
SITEURL = 'https://www.bombayengg.com' // Main website URL
ADMINDIR = 'xadmin'                     // Admin panel directory
SITEDIR = 'xsite'                       // Frontend directory
UPLOADDIR = 'uploads'                   // Upload directory
COREDIR = 'core'                        // Core files directory
```

**Key Constants Defined:**
- `ROOTPATH` - Absolute path to public_html
- `SITEPATH` - Path to xsite directory
- `ADMINPATH` - Path to xadmin directory
- `UPLOADPATH/UPLOADURL` - Upload file paths
- `SITEURL` - Website root URL
- `ISMOBILE` - Mobile device detection flag

---

## 3. CORE FILES EXPLAINED

### 3.1 core/core.inc.php
**Bootstrap file that initializes the application**

```php
// 1. Includes config.inc.php
// 2. Creates DB connection ($DB object)
// 3. Requires utility files (formating, common, file)
// 4. Loads settings from database
// 5. Sets timezone
```

**Used by:** Both xsite/index.php and xadmin/index.php

---

### 3.2 core/db.inc.php
**MySQL Database Connection Class - mxDb**

**Key Methods:**
- `dbRow()` - Fetch single row
- `dbRows()` - Fetch multiple rows
- `dbModify()` - Insert/Update/Delete operations
- `numRows` - Property with result count

**Usage:**
```php
global $DB;
$DB->vals = array(1, 'value');
$DB->types = "is";  // i=int, s=string, d=double
$DB->sql = "SELECT * FROM table WHERE status=? AND name=?";
$result = $DB->dbRow();
```

---

### 3.3 core/common.inc.php
**Shared utility functions used across entire application**

**Key Functions:**
- `getSetting()` - Get all settings from database
- `getLanguages()` - Get language list
- `mxSetLogIcon()` - Set menu icons for admin
- `mxValidateLogin()` - Check admin authentication
- Date/time formatting functions
- String utility functions

---

### 3.4 xsite/core-site/common.inc.php
**Frontend-specific utility functions**

**Key Functions:**
- Category navigation functions
- Product display helpers
- Template processing functions

---

### 3.5 xsite/inc/site.inc.php
**Frontend routing and logic**

**Key Functions:**
- `getCatChilds($categoryID)` - Get child categories (3 levels deep)
- `getSideNav()` - Generate sidebar navigation with category hierarchy
- `getPageHeader()` - Generate page header section
- Category path resolution and mapping

**Variables:**
- `$MXSHOWREC = 9` - Records per page for frontend
- `$ARRCAT = []` - Array of category IDs to display

---

## 4. FRONTEND (XSITE) ARCHITECTURE

### 4.0 IMPORTANT: XSITE IS THE ROOT

**The `xsite/` folder serves as the public website root.** When users visit `https://www.bombayengg.com/`, they are accessing the files in the `xsite/` directory. This is configured via the web server (Apache/Nginx) to serve `xsite/` as the document root for the public domain.

**Key Points:**
- Users never see `/xsite/` in URLs - it's transparent
- `xsite/index.php` is the main entry point for all public-facing requests
- All relative paths within xsite use `../` to access parent directory files (core/, lib/, vendor/, etc.)
- `/xadmin/` is a separate administrative interface accessed via `https://www.bombayengg.com/xadmin/`

**Directory Serving:**
```
Web Root (https://www.bombayengg.com/) → /xsite/
Admin Panel (https://www.bombayengg.com/xadmin/) → /xadmin/
```

---

### 4.1 xsite/index.php - Main Router

```
Request Flow:
1. Includes core/core.inc.php (initialization)
2. Includes template management (tpl.class.inc.php)
3. Includes frontend common functions (core-site/common.inc.php)
4. Loads module-specific includes (from mod/ directories)
5. Sets up OG meta tags for WhatsApp/social sharing
6. Includes header.php
7. Includes module template file
8. Includes footer.php
9. Closes DB connection
```

**OG Meta Tags Generation** (lines 35-136):
- For pump detail pages: Dynamic title, image, description
- For knowledge center articles: Article-specific meta
- For motor detail pages: Motor-specific meta

---

### 4.2 Frontend Modules (xsite/mod/)

**Pumps Module:**
- `x-pumps.php` - Listing page with sidebar & pagination
- `x-pumps.inc.php` - Functions:
  - `getPumpProducts()` - Fetch pump list for category
  - `getPDetail($pumpID)` - Fetch pump specifications/variants
- `x-detail.php` - Individual pump detail page

**Motors Module:**
- `x-motors.php` - Motor listing
- `x-motors.inc.php` - Motor functions
- `x-detail.php` - Motor detail page

**Inquiry Forms:**
- `pump-inquiry/` - Pump inquiry with JavaScript handling
- `product-inquiry/` - Product/motor inquiry
- Both integrate with Brevo email service

**Other Modules:**
- `knowledge-center/` - Blog/articles
- `page/` - Static pages
- `home/` - Home page
- `driver/` - Driver portal

---

### 4.3 URL Structure & Routing

**Pattern:** `{SITEURL}/{category-seoUri}/{product-seoUri}/`

**Examples:**
- `/pumps/residential-pumps/` - Pump category listing
- `/pumps/residential-pumps/mini-pumps/` - Subcategory
- `/pumps/residential-pumps/mini-pumps/swj1/` - Individual pump detail

**Resolution:**
1. tpl.class.inc.php parses URL segments (uriArr)
2. site.inc.php resolves segments to category/product IDs
3. Module template loads with appropriate data
4. Frontend displays with hierarchy breadcrumbs

---

## 5. BACKEND (XADMIN) ARCHITECTURE

### 5.1 xadmin/index.php - Admin Router

```
Request Flow:
1. Includes core/core.inc.php (initialization)
2. Checks logout request (?xAction=xLogout)
3. Includes admin settings & common functions
4. Creates manageTemplate object (tpl.class.inc.php)
5. Validates user login (if not /login page)
6. Sets records per page: $MXSHOWREC = 20
7. Handles offset for pagination
8. Includes module-specific logic
9. Includes admin header
10. Includes module template
11. Includes admin footer
```

---

### 5.2 Admin Modules (xadmin/mod/)

**Product Management (36 modules total):**

1. **Pump Management:**
   - `pump/` - Add/Edit/Delete pump products
   - `pump-category/` - Manage pump categories
   - `pump-inquiry/` - View customer inquiries

2. **Motor Management:**
   - `motor/` - Add/Edit/Delete motor products
   - `motor-category/` - Manage motor categories

3. **Content Management:**
   - `page/` - Manage static pages
   - `knowledge-center/` - Manage articles

4. **Customer Communication:**
   - `contact-us/` - View contact form submissions
   - `product-inquiry/` - View product inquiries

5. **HR & Operations:**
   - `user/` - User management
   - `driver-management/` - Driver info
   - `employee-leave/` - Leave applications
   - `expense-entry/` - Expense tracking
   - [and 10+ more modules]

**Module Structure:**
```
xadmin/mod/{module-name}/
├── x-{module-name}-list.php      # Listing page
├── x-{module-name}-add-edit.php  # Add/Edit form
└── x-{module-name}.inc.php       # Module functions
```

---

### 5.3 Admin AJAX Endpoints

**Location:** `xadmin/core-admin/ajax.inc.php`

**Handles:**
- Form submissions
- Category hierarchy updates
- Status toggles
- Quick edits
- File uploads
- Validation responses

---

## 6. DATABASE TABLES (Key Ones)

### Pump-Related:
- `mx_pump` - Main pump products (89 records)
- `mx_pump_detail` - Pump specifications/variants
- `mx_pump_category` - Category hierarchy
- `mx_pump_inquiry` - Customer inquiries

### Motor-Related:
- `mx_motor` - Motor products
- `mx_motor_detail` - Motor specifications
- `mx_motor_category` - Category hierarchy

### Content:
- `mx_page` - Static pages
- `mx_knowledge_center` - Blog articles

### Communications:
- `mx_contact_us` - Contact form submissions
- `mx_product_inquiry` - Product inquiries

### System:
- `mx_admin_user` - Admin users
- `mx_settings` - Site settings
- `mx_language` - Language support

---

## 7. TEMPLATE CLASS (tpl.class.inc.php)

**Location:** `xsite/core-site/tpl.class.inc.php` and `xadmin/core-admin/tpl.class.inc.php`

**Main Properties:**
- `$modName` - Current module name (e.g., "pumps", "motors")
- `$pageType` - Page type (e.g., "list", "detail", "edit")
- `$pageUri` - Full page URI path
- `$uriArr` - Array of URL segments
- `$data` - Data for current page
- `$tplFile` - Template file to include
- `$tplInc` - Module-specific include file

**Methods:**
- `setTemplate()` / `setPage()` - Parse request and set properties
- `setPageType()` - Determine if listing, detail, or edit page

---

## 8. KEY PATTERNS & CONVENTIONS

### File Naming:
- **List pages:** `x-{module}-list.php`
- **Detail pages:** `x-detail.php`
- **Add/Edit forms:** `x-{module}-add-edit.php`
- **Include files:** `x-{module}.inc.php`
- **Core files:** `{function}.inc.php`

### Function Naming:
- List functions: `get{Plural}()` or `get{Singular}List()`
- Detail functions: `get{Singular}Detail()` or `get{Singular}()`
- Add/Edit functions: `add{Singular}()` or `edit{Singular}()`
- Delete functions: `delete{Singular}()`

### Database:
- Table prefix: `mx_` or `_live_`
- Primary keys: `{entity}ID` (e.g., `pumpID`, `motorID`)
- Status field: `status` (1=active, 0=inactive)
- SEO field: `seoUri` (URL-friendly slug)

### Images:
- **Listings:** 235x235 thumbnails
- **Detail pages:** 530x530 main images
- **Format:** WebP (optimized)
- **Locations:** `/uploads/{category}/{size}/{filename}.webp`

---

## 9. SECURITY FEATURES

1. **CSRF Protection:**
   - Token defined: `$MXSET["TOKENID"] = "CSRF_TOKEN"`
   - Used in forms

2. **Prepared Statements:**
   - `$DB->vals` and `$DB->types` for parameterized queries
   - Prevents SQL injection

3. **Session Management:**
   - `session_start()` in config.inc.php
   - Admin login validation in common.inc.php

4. **Mobile Detection:**
   - Mobile_Detect library loaded
   - `ISMOBILE` constant set

5. **Email Security:**
   - AWS SES for transactional emails
   - Brevo (Sendinblue) for marketing emails

---

## 10. CRITICAL INCLUDE DEPENDENCIES

### Frontend (xsite/index.php):
```php
1. ../core/core.inc.php                    // Initialize
2. core-site/tpl.class.inc.php            // Template management
3. core-site/common.inc.php               // Frontend utils
4. ../core/form.inc.php                   // Form handling
5. ../core/validate.inc.php               // Validation
6. inc/site.inc.php                       // Routing logic
7. {module}/x-{module}.inc.php            // Module functions
8. mod/header.php                         // Header template
9. mod/{module}/x-{page}.php              // Page content
10. mod/footer.php                        // Footer template
```

### Backend (xadmin/index.php):
```php
1. ../core/core.inc.php                   // Initialize
2. core-admin/settings.inc.php            // Admin settings
3. core-admin/common.inc.php              // Admin utils
4. core-admin/tpl.class.inc.php          // Template management
5. inc/site.inc.php                       // Admin routing
6. core/form.inc.php                      // Form handling
7. mod/{module}/x-{module}.inc.php        // Module functions
8. core-admin/header.php                  // Admin header
9. mod/{module}/x-{module}-list.php       // Module content
10. core-admin/footer.php                 // Admin footer
```

---

## 11. QUICK REFERENCE

### Database Connection:
```php
global $DB;
$DB->vals = array(value1, value2);
$DB->types = "is";  // i=int, s=string, d=double, b=blob
$DB->sql = "SELECT * FROM " . $DB->pre . "table WHERE id=? AND name=?";
$row = $DB->dbRow();  // Single row
$rows = $DB->dbRows();  // Multiple rows
echo $DB->numRows;  // Row count
```

### Template Data:
```php
global $TPL;
echo $TPL->modName;      // Module name
echo $TPL->pageType;     // Page type
echo $TPL->data['field']; // Page data
```

### URLs:
```php
echo SITEURL;            // https://www.bombayengg.com
echo UPLOADURL;          // https://www.bombayengg.com/uploads
echo ADMINURL;           // https://www.bombayengg.com/xadmin
```

### Image Paths:
```php
// Large image (detail page)
{UPLOADURL}/pump/530_530_crop_100/{filename}.webp

// Thumbnail (listing)
{UPLOADURL}/pump/235_235_crop_100/{filename}.webp
```

---

## 12. RECENT UPDATES (Nov 2025)

1. **Brevo Email Integration**
   - API key: Defined in config.inc.php
   - Implementation: core/brevo.inc.php
   - Used for: Inquiry notifications

2. **WhatsApp OG Meta Tags**
   - Pump detail pages: Dynamic product info
   - Knowledge center: Article-specific meta
   - Motor detail pages: Motor-specific meta
   - Implementation: xsite/index.php lines 35-136

3. **Image Optimization**
   - 16 pump products updated with Crompton images
   - WebP format, optimized sizes
   - Thumbnails: 235x235, Large: 530x530

4. **Schema.org Implementation**
   - pump-schema.inc.php for structured data
   - JSON-LD format for search engines

---

## 13. COMMON DEVELOPMENT TASKS

### Adding a New Pump Product:
1. Add record to `mx_pump` table (via xadmin/pump module)
2. Upload image to `/uploads/pump/`
3. Generate thumbnails (235x235, 530x530)
4. Create detail specifications in `mx_pump_detail`
5. Clear cache (OPcache, file cache)

### Adding a New Category:
1. Add to `mx_pump_category` via xadmin
2. Set parent category ID
3. Define seoUri for clean URLs
4. Upload category image
5. Update navigation logic in site.inc.php if needed

### Creating an Admin Module:
> **Detailed Guide:** [XAdmin Module Creation Guide](XADMIN_MODULE_CREATION.md)

1. Create directory: `xadmin/mod/{module-name}/`
2. Create files:
   - `x-{module-name}-list.php` (listing)
   - `x-{module-name}-add-edit.php` (form)
   - `x-{module-name}.inc.php` (functions)
3. Create database table: `mx_{module_name}`
4. Add menu entry in admin dashboard

---

## 14. GITHUB & DEPLOYMENT

### GitHub Repository
- **URL:** https://github.com/pari588/bessite
- **Main Branch:** main
- **Remote:** origin (https://github.com/pari588/bessite.git)

### GitHub Authentication
**Setup:**
- Username: `pari588`
- Personal Access Token: Stored securely (see credentials file)
- Scope: repo, write:packages

**Git Configuration:**
```bash
# Set credential helper
git config --global credential.helper store

# Git user (for commits)
git config --global user.name "Claude Code"
git config --global user.email "noreply@anthropic.com"
```

**Push Command Format:**
```bash
git push https://{username}:{PAT}@github.com/pari588/bessite.git main
```

**Store Credentials:**
After first push with PAT, git stores credentials locally. Use credential helper:
```bash
git config --global credential.helper store
```

### Database Backups
- **Location:** `/home/bombayengg/public_html/database_backups/`
- **Format:** SQL dump files with timestamp
- **Latest Backup:** `bombayengg_20251205_174319.sql` (1.2 MB)
- **Include:** Complete schema, all tables, and data

**Restore Command:**
```bash
mysql -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg < database_backups/bombayengg_YYYYMMDD_HHMMSS.sql
```

---

## 15. DEPLOYMENT AND BACKUP POLICY (MANDATORY)

### Required Reading
📖 **[DEPLOYMENT_AND_BACKUP_POLICY.md](DEPLOYMENT_AND_BACKUP_POLICY.md)** - MANDATORY for all developers

### Key Points
- **Every change requires:** File backup + Database backup + GitHub commit
- **Deployment sequence:** Backup → Change → GitHub commit → Deploy → Test
- **Restore capability:** Can revert to any previous version via GitHub or file backups
- **Emergency restore:** Complete procedures documented for instant recovery

### Quick Backup Commands

**Before making any changes:**
```bash
# Website backup
tar -czf backups/website_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude=uploads/fuel-expense --exclude=uploads/voucher --exclude=.git \
  /home/bombayengg/public_html/

# Database backup
mysqldump -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg > \
  database_backups/bombayengg_$(date +%Y%m%d_%H%M%S).sql
```

**After making changes:**
```bash
# Commit to GitHub
git add {files}
git commit -m "Description of changes"
git push origin main
```

### Restore Procedures
- **Git revert:** `git revert {commit-hash}`
- **File restore:** `tar -xzf backups/website_backup_*.tar.gz`
- **Database restore:** `mysql ... < database_backups/bombayengg_*.sql`

---

**End of Site Structure Overview**

*This document provides complete understanding of the Bombay Engineering Syndicate website architecture as of December 5, 2025.*
