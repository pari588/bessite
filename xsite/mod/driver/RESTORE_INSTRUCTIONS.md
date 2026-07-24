# Driver App Restoration Instructions

## Backup Files Created on 25th December 2025

The following backup files were created before the redesign:

| Original File | Backup File |
|--------------|-------------|
| `x-login.php` | `x-login.php.backup.20251225` |
| `x-home.php` | `x-home.php.backup.20251225` |

---

## How to Restore

### Option 1: Using Terminal/SSH

```bash
cd /home/bombayengg/public_html/xsite/mod/driver

# Restore login page
cp x-login.php.backup.20251225 x-login.php

# Restore home page
cp x-home.php.backup.20251225 x-home.php
```

### Option 2: Using Webmin File Manager

1. Login to Webmin
2. Go to **Tools → File Manager**
3. Navigate to `/home/bombayengg/public_html/xsite/mod/driver/`
4. Delete or rename the current `x-login.php` and `x-home.php`
5. Rename `x-login.php.backup.20251225` to `x-login.php`
6. Rename `x-home.php.backup.20251225` to `x-home.php`

---

## Files Modified During Redesign

### 1. `x-login.php`
- Complete UI redesign with blue branding
- Compact mobile-friendly layout (no scrolling)
- Horizontal PIN input fields
- PIN numbers fully visible with proper line-height

### 2. `x-home.php`
- Complete UI redesign matching login page
- Compact mobile-friendly layout (no scrolling)
- Added logout button in header
- Fixed broken footer image (removed logo-2.png reference)

### 3. `x-driver.inc.php`
- Added `driverLogout()` function
- Added case for `driverLogout` in switch statement

### 4. `js/x-driver.inc.js`
- Added logout button click handler

---

## Restoring JavaScript and PHP Backend

If you also need to remove the logout functionality:

### Revert `x-driver.inc.php`
Remove the `driverLogout()` function (lines 84-94) and the case statement for it.

### Revert `js/x-driver.inc.js`
Remove the logout click handler section (the `$('#driver-logout').click(...)` block).

---

## Notes

- The backup files contain the original design before 25th December 2025 redesign
- After restoration, the logout button will no longer work (button won't exist in restored version)
- Driver app URL: `/driver/login`
