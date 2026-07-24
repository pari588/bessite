# Voucher Signature Implementation Options

## Current Implementation

Signature image hardcoded in voucher template:
- File: `/xadmin/mod/voucher/inc/x-invoice-priview.php`
- Image: `/xsite/images/pari-sign.jpg`
- Shows same signature for both "Prepared By" and "Authorised By"

---

## Recommended Approaches

### Option 1: Database-Driven Signatures (Recommended)

Store signature settings in `mx_site_setting` table.

**Pros:**
- Change signatures from admin panel without code changes
- Different signatures for different purposes
- Easy to add more signatories later
- No deployment needed for updates

**Implementation:**

1. Add columns to `mx_site_setting`:
```sql
ALTER TABLE mx_site_setting ADD COLUMN preparedBySignature VARCHAR(255) DEFAULT NULL;
ALTER TABLE mx_site_setting ADD COLUMN preparedByName VARCHAR(100) DEFAULT NULL;
ALTER TABLE mx_site_setting ADD COLUMN preparedByDesignation VARCHAR(100) DEFAULT NULL;
ALTER TABLE mx_site_setting ADD COLUMN authorisedBySignature VARCHAR(255) DEFAULT NULL;
ALTER TABLE mx_site_setting ADD COLUMN authorisedByName VARCHAR(100) DEFAULT NULL;
ALTER TABLE mx_site_setting ADD COLUMN authorisedByDesignation VARCHAR(100) DEFAULT NULL;
```

2. Create upload interface in `/xadmin/core-admin/mod/site-setting/`

3. Update voucher template to fetch from database:
```php
$DB->sql = "SELECT preparedBySignature, preparedByName, authorisedBySignature, authorisedByName FROM mx_site_setting WHERE siteSettingID=1";
$signSettings = $DB->dbRow();
```

---

### Option 2: User-Based Signatures

Each admin user has their own signature. Voucher uses creator's signature.

**Pros:**
- Audit trail of who prepared each voucher
- Multiple people can create vouchers with their own signatures
- Professional for larger teams

**Implementation:**

1. Add to `mx_x_admin_user`:
```sql
ALTER TABLE mx_x_admin_user ADD COLUMN signatureImage VARCHAR(255) DEFAULT NULL;
ALTER TABLE mx_x_admin_user ADD COLUMN signatureDesignation VARCHAR(100) DEFAULT NULL;
```

2. Add signature upload in user profile/settings

3. Store `preparedByUserID` and `authorisedByUserID` in `mx_voucher`:
```sql
ALTER TABLE mx_voucher ADD COLUMN preparedByUserID INT DEFAULT NULL;
ALTER TABLE mx_voucher ADD COLUMN authorisedByUserID INT DEFAULT NULL;
```

4. Update voucher template to fetch user signatures

---

### Option 3: Digital Signatures with Full Details

Complete signature block with image, name, designation, and date.

**Example Output:**
```
[Signature Image]
Paritosh Ajmera
Director
10-Jan-2026
```

**Implementation:**

Combine Option 1 or 2 with enhanced template:
```php
<td class="center" valign="bottom">
    <?php if ($signatureImage): ?>
    <img src="<?php echo $signatureImage; ?>" style="height:50px;" />
    <?php endif; ?>
    <div style="font-weight:bold;"><?php echo $signatoryName; ?></div>
    <div style="font-size:10px;"><?php echo $designation; ?></div>
    <div style="font-size:9px;color:#666;"><?php echo date('d-M-Y'); ?></div>
    <h4>PREPARED BY</h4>
</td>
```

---

## File Locations

| File | Purpose |
|------|---------|
| `/xadmin/mod/voucher/inc/x-invoice-priview.php` | Voucher HTML template |
| `/xadmin/mod/voucher/inc/voucher-print.inc.php` | PDF generation logic |
| `/xsite/images/pari-sign.jpg` | Current signature image |
| `/uploads/signatures/` | Recommended folder for multiple signatures |

---

## Quick Implementation Checklist

### For Database-Driven (Option 1):
- [ ] Run SQL to add columns to mx_site_setting
- [ ] Add signature upload fields to site settings form
- [ ] Add file upload handler for signatures
- [ ] Update x-invoice-priview.php to fetch from database
- [ ] Test PDF generation

### For User-Based (Option 2):
- [ ] Run SQL to add columns to mx_x_admin_user and mx_voucher
- [ ] Add signature upload to user profile
- [ ] Modify voucher creation to store preparedByUserID
- [ ] Add authorization workflow (optional)
- [ ] Update x-invoice-priview.php to fetch user signatures
- [ ] Test PDF generation

---

## Notes

- Signature images should be PNG with transparent background for best results
- Recommended size: 200x80 pixels, under 50KB
- JPG works but white background may show on colored paper
- Consider adding signature verification/approval workflow for compliance
