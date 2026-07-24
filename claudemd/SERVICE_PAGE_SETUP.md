# Service Page - Admin Setup Guide

## Overview

The Service/Support page displays manufacturer contact information (Crompton, CG Power, etc.) and is fully editable through xadmin.

---

## Database Structure

### Table: `mx_service_manufacturer`

Stores manufacturer contact details.

```sql
CREATE TABLE mx_service_manufacturer (
    manufacturerID INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    tagline VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    cardColor VARCHAR(50) DEFAULT '#003566',
    phoneNumber VARCHAR(50) DEFAULT NULL,
    whatsappNumber VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    sortOrder INT(11) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    dateAdded DATETIME DEFAULT CURRENT_TIMESTAMP,
    dateModified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (manufacturerID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: `mx_page` (pageID = 7)

The "Support" page entry uses:
- `pageContent` - Intro section (title + subtitle)
- `synopsis` - Notice/disclaimer section

---

## xadmin Module Files

Location: `/xadmin/mod/service-manufacturer/`

| File | Purpose |
|------|---------|
| `x-service-manufacturer.inc.php` | Backend logic (add, update, delete) |
| `x-service-manufacturer-list.php` | List view in xadmin |
| `x-service-manufacturer-add-edit.php` | Add/Edit form |

---

## Frontend Template

Location: `/xsite/mod/page/x-service-tpl.php`

The template:
1. Reads `pageContent` and `synopsis` from `mx_page` (pageID=7)
2. Fetches manufacturers from `mx_service_manufacturer` table
3. Dynamically renders manufacturer cards

---

## Menu Setup

### Admin Menu Entry

```sql
INSERT INTO mx_x_admin_menu (menuType, menuTitle, seoUri, xOrder, parentID, status)
VALUES (0, 'Service Manufacturers', 'service-manufacturer', 37, 0, 1);
```

### Role Access (IMPORTANT!)

For the menu to be visible, you MUST add role access:

```sql
-- Get the adminMenuID first
SELECT adminMenuID FROM mx_x_admin_menu WHERE seoUri='service-manufacturer';

-- Add access for Admin role (roleID=1)
INSERT INTO mx_x_admin_role_access (roleID, adminMenuID, accessType, status)
VALUES (1, <adminMenuID>, '["view","add","edit","delete","trash","restore"]', 1);

-- Add for other roles as needed (roleID: 1=Admin, 2=Manager, etc.)
```

**Note:** Without role access entry, the menu will NOT appear even if it exists in `mx_x_admin_menu`.

---

## How to Edit in xadmin

### 1. Edit Manufacturer Contacts

- Go to: **Service Manufacturers**
- Fields available:
  - Name, Tagline, Description
  - Phone, WhatsApp, Email, Website
  - Address
  - Logo (upload image)
  - Card Color (hex code like `#e85d04`)
  - Sort Order (lower = appears first)

### 2. Edit Page Intro & Notice

- Go to: **Page** > Edit "Support" page
- **Content** field: Edit the intro (title + subtitle)
- **Other Content** field: Edit the "Important Notice" section

---

## Upload Directory

Manufacturer logos are stored in:
```
/uploads/service-manufacturer/
```

The template also checks `/uploads/home/` as fallback for logos.

---

## Adding a New Manufacturer

1. Go to xadmin > **Service Manufacturers** > **Add**
2. Fill in:
   - Name (required)
   - Tagline (e.g., "Consumer Electricals")
   - Description
   - Contact details (phone, WhatsApp, email, website)
   - Address
   - Upload logo
   - Set card color (hex)
   - Set sort order
3. Save

---

## Troubleshooting

### Menu not visible in xadmin?

1. Check menu exists:
```sql
SELECT * FROM mx_x_admin_menu WHERE seoUri='service-manufacturer';
```

2. Check role access exists:
```sql
SELECT * FROM mx_x_admin_role_access WHERE adminMenuID=<menuID>;
```

3. Add role access if missing (see "Role Access" section above)

4. Log out and log back in to xadmin

### Manufacturers not showing on frontend?

1. Check status is active:
```sql
SELECT * FROM mx_service_manufacturer WHERE status=1;
```

2. Check template file exists:
```
/xsite/mod/page/x-service-tpl.php
```

3. Verify page entry:
```sql
SELECT * FROM mx_page WHERE pageID=7;
```

---

## File Locations Summary

| Purpose | Path |
|---------|------|
| Frontend template | `/xsite/mod/page/x-service-tpl.php` |
| xadmin module | `/xadmin/mod/service-manufacturer/` |
| Logo uploads | `/uploads/service-manufacturer/` |
| This documentation | `/docs/service-page-setup.md` |

---

## Created

- Date: December 24, 2024
- Tables: `mx_service_manufacturer`
- Menu ID: 68 (Service Manufacturers)
- Page ID: 7 (Support page)
