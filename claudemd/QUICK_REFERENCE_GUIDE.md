# Quick Reference Guide - Bombay Engineering Syndicate

**Last Updated:** November 21, 2025
**Version:** 1.0

---

## 📚 Documentation Files

### Core Architecture
- **SITE_STRUCTURE_OVERVIEW.md** - Complete site structure and architecture
  - Use: Understanding how the system is organized
  - Sections: 13 comprehensive sections

### Image Optimization
- **KNOWLEDGE_CENTER_IMAGE_OPTIMIZATION.md** - Knowledge center image optimization report
  - Use: Understanding image optimization, rollback procedures
  - Results: 73% size reduction (8.64 MB saved)

### Session Records
- **SESSION_SUMMARY_NOV21_2025.md** - Complete session activities
  - Use: Understanding what was done and why
  - Contains: All activities, results, next steps

---

## 🔧 Scripts & Tools

### Image Optimization Script
```bash
php /home/bombayengg/public_html/optimize_knowledge_center_images_v2.php
```

**Usage:**
- Optimizes images using ImageMagick
- Creates automatic backups
- Works with PNG, JPG, JPEG, GIF, WebP
- Can be reused for pump and motor images

**Features:**
- Automatic format detection
- Backup creation
- Detailed reporting
- Rollback capability

---

## 🗂️ Directory Structure

```
/home/bombayengg/public_html/
├── config.inc.php              # Database & site configuration
├── core/                        # Shared functions
│   ├── core.inc.php            # Bootstrap
│   ├── db.inc.php              # Database class
│   ├── common.inc.php          # Utility functions
│   └── [other utilities]
├── xsite/                       # Frontend (customer site)
│   ├── index.php               # Main router
│   ├── core-site/              # Frontend utilities
│   ├── mod/                    # Modules (pumps, motors, etc)
│   └── inc/site.inc.php        # Frontend routing
├── xadmin/                      # Backend (admin panel)
│   ├── index.php               # Admin router
│   ├── core-admin/             # Admin utilities
│   ├── mod/                    # Admin modules (36 total)
│   └── inc/site.inc.php        # Admin routing
├── uploads/                     # User content
│   ├── knowledge-center/       # Knowledge center images
│   │   ├── [13 optimized images]
│   │   └── backup_original/    # Original backups
│   ├── pump/                   # Pump images
│   ├── motor/                  # Motor images
│   └── [other uploads]
└── claudemd/                    # Documentation (this)
    ├── SITE_STRUCTURE_OVERVIEW.md
    ├── KNOWLEDGE_CENTER_IMAGE_OPTIMIZATION.md
    ├── SESSION_SUMMARY_NOV21_2025.md
    └── QUICK_REFERENCE_GUIDE.md (this file)
```

---

## 🗄️ Database Quick Access

### Connection Details
```php
$DBHOST = 'localhost'
$DBNAME = 'bombayengg'
$DBUSER = 'bombayengg'
$DBPASS = 'oCFCrCMwKyy5jzg'
$DBTABLE_PREFIX = '_live_'
```

### Key Tables
```
_live_pump                  # Main pump products
_live_pump_detail           # Pump specifications
_live_pump_category         # Pump categories
_live_pump_inquiry          # Customer inquiries
_live_motor                 # Motor products
_live_motor_detail          # Motor specifications
_live_knowledge_center      # Blog/knowledge articles
_live_page                  # Static pages
_live_admin_user            # Admin users
```

### Common Queries
```sql
-- List all pumps
SELECT * FROM _live_pump WHERE status=1;

-- List knowledge center articles
SELECT * FROM _live_knowledge_center WHERE status=1;

-- Get pump inquiries
SELECT * FROM _live_pump_inquiry ORDER BY dateAdded DESC;

-- Get image filenames in use
SELECT pumpImage FROM _live_pump WHERE pumpImage IS NOT NULL;
SELECT knowledgeCenterImage FROM _live_knowledge_center WHERE knowledgeCenterImage IS NOT NULL;
```

---

## 🌐 Frontend (xsite) Modules

| Module | Purpose | URL Pattern |
|--------|---------|------------|
| pumps | Pump products | /pumps/[category]/[product]/ |
| motors | Motor products | /motors/[category]/[product]/ |
| knowledge-center | Blog/articles | /knowledge-center/[article]/ |
| page | Static pages | /page/[page-name]/ |
| home | Homepage | / |
| pump-inquiry | Pump inquiry form | /pump-inquiry/ |
| product-inquiry | Product inquiry form | /product-inquiry/ |
| driver | Driver portal | /driver/ |

---

## ⚙️ Backend (xadmin) Modules (36 Total)

### Product Management
- pump (Add/Edit/Delete pump products)
- pump-category (Manage pump categories)
- motor (Manage motor products)
- motor-category (Manage motor categories)
- product-sku (Product SKU management)

### Inquiries & Contact
- pump-inquiry (View pump inquiries)
- product-inquiry (View product inquiries)
- contact-us (View contact messages)

### Content
- page (Static page management)
- knowledge-center (Blog/article management)

### Operations
- user (Admin user management)
- dashboard (Admin dashboard)
- [HR modules] (Leave, expense, driver, etc)

---

## 🖼️ Image Management

### Image Locations
```
/uploads/pump/                    # Pump images
/uploads/motor/                   # Motor images
/uploads/knowledge-center/        # Knowledge center images
/uploads/page/                    # Page images
/uploads/home/                    # Home page images
```

### Image Sizes (Conventions)
```
Original:       {filename}.{ext}          (Main image)
Detail Page:    530_530_crop_100/{file}   (Large)
Listing Page:   235_235_crop_100/{file}   (Thumbnail)
```

### Recent Optimization
- Knowledge center: 73% size reduction (11.78 MB → 3.14 MB)
- Technique: ImageMagick with 85% quality, metadata stripping
- Backups: /uploads/knowledge-center/backup_original/

---

## 🔐 Security & Authentication

### CSRF Protection
```php
$MXSET["TOKENID"] = "CSRF_TOKEN"  // Defined in config
```

### Database Queries
```php
// Secure parameterized queries
$DB->vals = array(value1, value2);
$DB->types = "is";  // i=int, s=string, d=double
$DB->sql = "SELECT * FROM table WHERE id=? AND name=?";
$row = $DB->dbRow();
```

### Admin Login
- Controlled by: common.inc.php (mxValidateLogin function)
- Session-based authentication
- Required for xadmin/* access

---

## 📱 Frontend Development

### Template System
```php
// tpl.class.inc.php provides:
$TPL->modName       // Current module name
$TPL->pageType      // "list", "detail", or "edit"
$TPL->data          // Page data array
$TPL->uriArr        // URL segments
```

### Common Functions
```php
// Category navigation
getCatChilds($categoryID)           // Get child categories
getSideNav()                        // Generate sidebar

// Product retrieval
getPumpProducts()                   // Get pump list
getPDetail($pumpID)                 // Get pump details
getMotorProducts()                  // Get motor list
```

### URL Routing
- Pattern: `/{category-seoUri}/{product-seoUri}/`
- Handled by: site.inc.php (frontend routing)
- Database: Uses seoUri field for SEO-friendly URLs

---

## 🛠️ Backend Development

### Module Structure
```
xadmin/mod/{module}/
├── x-{module}-list.php            # List view
├── x-{module}-add-edit.php        # Add/Edit form
└── x-{module}.inc.php             # Module functions
```

### Form Handling
```php
// Include form library
require_once(COREPATH . "/form.inc.php");

// Create form elements
mxForm::createInput(...)            // Text input
mxForm::createTextarea(...)         // Textarea
mxForm::createSelect(...)           // Dropdown
```

### AJAX Endpoints
- File: xadmin/core-admin/ajax.inc.php
- Used for: Form submissions, quick updates, validations

---

## 🚀 Common Tasks

### Add a New Pump Product
1. Go to xadmin/pump-list
2. Click "Add New Pump"
3. Fill form (Title, Features, Specs)
4. Upload image
5. Generate thumbnails (automatic)
6. Save

### Optimize Images
```bash
# For knowledge center (already done)
php optimize_knowledge_center_images_v2.php

# For pumps (if needed)
# Modify script path and run
```

### View Database
```bash
# Connect via command line
mysql -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg

# Or use phpMyAdmin
http://www.bombayengg.com/phpmyadmin/
```

### Clear Cache
```php
// OPcache
php clear_opcache.php

// File cache
php clear_cache.php

// Full
php clear_all_cache.php
```

---

## 📊 Performance Metrics

### Current Status
- Knowledge center images: Optimized (73% reduction)
- Pump images: Original sizes
- Motor images: Original sizes
- Page load: Acceptable

### Optimization Opportunities
1. Pump images (can use same script)
2. Motor images (can use same script)
3. Home page images (can use same script)
4. Database indexing review
5. Query optimization audit

---

## 🔄 Rollback Procedures

### Rollback Knowledge Center Images
```bash
# Single image
cp /uploads/knowledge-center/backup_original/filename.png \
   /uploads/knowledge-center/filename.png

# All images
cp /uploads/knowledge-center/backup_original/* \
   /uploads/knowledge-center/
```

### Clear Cache After Rollback
- Browser: Ctrl+Shift+Delete
- Server: php clear_all_cache.php
- CDN: (if applicable)

---

## 📞 Contact & Support

### Documentation
- Architecture: SITE_STRUCTURE_OVERVIEW.md
- Images: KNOWLEDGE_CENTER_IMAGE_OPTIMIZATION.md
- Session: SESSION_SUMMARY_NOV21_2025.md

### Database
- Host: localhost
- User: bombayengg
- Database: bombayengg
- PHPMyAdmin: http://www.bombayengg.com/phpmyadmin/

### Backups
- Location: /uploads/knowledge-center/backup_original/
- Type: Original image files
- Size: 12 MB

---

## ✅ Checklist for New Development

### Before Starting
- [ ] Read SITE_STRUCTURE_OVERVIEW.md
- [ ] Understand current architecture
- [ ] Check database schema
- [ ] Review similar modules

### During Development
- [ ] Use prepared statements (secure)
- [ ] Follow naming conventions
- [ ] Create backups (if modifying images)
- [ ] Test thoroughly

### After Development
- [ ] Test all pages
- [ ] Check performance
- [ ] Clear caches
- [ ] Commit to git
- [ ] Document changes

---

## 🎯 Quick Links

### Important Files
- Configuration: `/config.inc.php`
- Bootstrap: `/core/core.inc.php`
- Frontend Router: `/xsite/index.php`
- Backend Router: `/xadmin/index.php`

### Important Folders
- Frontend: `/xsite/`
- Backend: `/xadmin/`
- Core: `/core/`
- Uploads: `/uploads/`
- Docs: `/claudemd/`

### Important URLs
- Website: https://www.bombayengg.com
- Admin: https://www.bombayengg.com/xadmin/
- PHPMyAdmin: http://www.bombayengg.com/phpmyadmin/

---

## 📝 Notes

- All changes committed to git
- Rollback capability available for all changes
- Documentation updated regularly
- No data loss incidents
- Zero downtime deployments

---

**This guide provides quick access to critical information about the Bombay Engineering Syndicate website.**

For detailed information, refer to the full documentation in `/claudemd/` folder.

Last updated: November 21, 2025 ✅
