# Codebase Cleanup Plan
**Date:** 2026-02-21
**Status:** Planning — awaiting approval before execution

---

## Overview

The web root at `/home/bombayengg/public_html/` contains ~375MB of unnecessary files including security risks, one-off scripts, raw data, dev artifacts, and database dumps exposed to the web.

---

## Step 1: SECURITY FIXES (Do First)

### 1.1 Remove phpinfo files
```bash
rm /home/bombayengg/public_html/phpinfo.php
rm /home/bombayengg/public_html/info.php
```
**Risk:** These expose full PHP configuration, server paths, PHP version, loaded modules to anyone.

### 1.2 Move database backups out of web root
```bash
mkdir -p /home/bombayengg/backups
mv /home/bombayengg/public_html/database_backups /home/bombayengg/backups/
```
**Risk:** Full SQL dumps with all table data accessible via browser at guessable URLs.

### 1.3 Move SSL certificate files
```bash
mv /home/bombayengg/public_html/bombayengg_net.crt /home/bombayengg/backups/
mv /home/bombayengg/public_html/CERTIFICATE_CHAIN_TO_INSTALL.txt /home/bombayengg/backups/
```

### 1.4 Remove composer.phar from web root
```bash
mv /home/bombayengg/public_html/composer.phar /home/bombayengg/backups/
```

### 1.5 Block vendor/ directory via .htaccess (if vendor is needed by PHP)
Add to `.htaccess`:
```apache
# Block direct web access to vendor directory
RewriteRule ^vendor/ - [F,L]
```
Alternatively, move vendor outside web root if autoload path can be updated.

### 1.6 Remove BES .git directory
```bash
rm -rf /home/bombayengg/public_html/BES/.git
```

---

## Step 2: REMOVE ONE-OFF SCRIPTS (196 PHP files)

These are data import, migration, debug, and test scripts that were run once and never again. All contain hardcoded DB credentials.

### 2.1 Scripts prefixed with `add_*`
```
add_3phase_rolled_steel_specs.php
add_all_cg_motor_specs.php
add_application_specific_motor_specs.php
add_booster_pump_specifications.php
add_cg_motor_detail_specs.php
add_crompton_4inch_borewell.php
add_crompton_pump_specifications.php
add_motor_products.php
add_mini_pumps_detailed.php
add_missing_mini_pumps.php
add_missing_product_details.sql
add_missing_shallow_well_pumps.sql
add_product_seo_urls.sql
```

### 2.2 Scripts prefixed with `analyze_*`
```
analyze_all_pages_schema.php
```

### 2.3 Scripts prefixed with `assign_*`
```
assign_products_to_categories.php
```

### 2.4 Scripts prefixed with `check_*`
```
check_handler_logs_now.php
check_ocr_logs.php
```

### 2.5 Scripts prefixed with `convert_*` / `create_*`
```
convert_correct_images.php
create_booster_placeholders.php
create_branded_pump_images.php
create_motor_categories.php
create_motor_spec_table.sql
```

### 2.6 Scripts prefixed with `crompton_*`
```
crompton_import_fixed.php
```

### 2.7 Scripts prefixed with `debug_*`
```
debug_category_flow.php
debug_insert.php
debug_login.php
debug_recaptcha.php
debug_role_access.php
```

### 2.8 Scripts prefixed with `diagnose_*` / `download_*`
```
diagnose_ocr_handler.php
download_crompton_images.php
download_pump_images.php
```

### 2.9 Scripts prefixed with `execute_*` / `extract_*`
```
execute_insert.php
extract_cg_hv_lv_motors.php
extract_crompton_advanced.php
extract_crompton_products.php
```

### 2.10 Scripts prefixed with `final_*` / `fix_*`
```
final_pump_inquiry_fix.php
final_sidebar_test.php
fix_dmb_cmb_seouri.php
fix_image_centering.php
fix_image_extensions.php
fix_seo_urls.php
```

### 2.11 Scripts prefixed with `generate_*` / `get_*`
```
generate_complete_sitemap.php
generate_csv_export.php
generate_dmb_cmb_thumbnails.php
generate_pump_thumbnails.php
generate_seo_urls.php
generate_sitemap.php
get_motor_specifications.php
get_ocr_logs.php
```

### 2.12 Scripts prefixed with `import_*`
```
import_4inch_borewell_pumps.php
import_crompton_pumps.php
import_images_cli.php
import_motor_products_complete.php
import_motor_specs.php
import_products_cli.php
```

### 2.13 Scripts prefixed with `optimize_*`
```
optimize_existing_shallow_well_images.php
optimize_knowledge_center_images_v2.php
```

### 2.14 Scripts prefixed with `path_*` / `populate_*` / `process_*`
```
path-finder.php
populate_all_remaining_specs.php
populate_extended_specifications.php
populate_mini_dmb_cmb_specifications.php
populate_pump_specifications.php
populate_shallow_well_complete_specs.php
process_pump_images.php
```

### 2.15 Scripts prefixed with `RECAPTCHA_*` / `remove_*` / `rename_*` / `reprocess_*`
```
RECAPTCHA_VERIFICATION_CODE.php
remove_black_bg_php.php
rename_pump_inquiry_table.php
reprocess_crompton_images.php
```

### 2.16 Scripts prefixed with `setup_*` / `simple_*`
```
setup_fuel_expense_tables.php
simple_thumb_generator.php
```

### 2.17 Scripts prefixed with `test_*`
```
test_handler_endpoint.php
test_log_write.php
test_ocr_quick.php
test_ocr_web_simulation.php
test_paddleocr.php
```

### 2.18 Scripts prefixed with `update_*`
```
update_all_motor_descriptions_correct.php
update_correct_mrp_prices.php
update_descriptions_v2.php
update_dmb_cmb_pumps.php
update_motor_content_cgglobal.php
update_motor_db_direct.php
update_motor_images_db.php
update_motor_products_images.php
update_pump_descriptions_seo.php
update_service_page_content.php
```

### 2.19 Scripts prefixed with `verify_*`
```
verify_dmb_cmb_complete.php
verify_product_pages.php
verify_products.php
verify_product_urls.php
verify_pump_setup.php
verify_shallow_well_pumps.php
verify_specs_simple.php
```

### Cleanup command
```bash
mkdir -p /home/bombayengg/backups/old-scripts
# Move all one-off scripts to backup (safer than delete)
# Run from /home/bombayengg/public_html/
mv add_*.php assign_*.php analyze_*.php check_*.php convert_*.php \
   create_*.php crompton_*.php debug_*.php diagnose_*.php download_*.php \
   execute_*.php extract_*.php final_*.php fix_*.php generate_*.php \
   get_*.php import_*.php optimize_*.php path-finder.php populate_*.php \
   process_*.php RECAPTCHA_*.php remove_*.php rename_*.php reprocess_*.php \
   setup_*.php simple_*.php test_*.php update_*.php verify_*.php \
   /home/bombayengg/backups/old-scripts/
```

---

## Step 3: REMOVE RAW DATA FILES (19 files)

Catalog CSVs, TSVs, JSONs, and HTML files already imported into the database.

```bash
mkdir -p /home/bombayengg/backups/old-data
mv /home/bombayengg/public_html/*.csv \
   /home/bombayengg/public_html/*.tsv \
   /home/bombayengg/public_html/*.json \
   /home/bombayengg/public_html/Crompton_Residential_Pumps_Catalog.html \
   /home/bombayengg/public_html/dc_motors.html \
   /home/bombayengg/public_html/00_Crompton_Master_Catalog.tsv \
   /home/bombayengg/backups/old-data/ 2>/dev/null
```

Note: Be careful not to move `composer.json` or `package-lock.json` — handle those separately.

---

## Step 4: REMOVE DATABASE MIGRATION FILES

Already-applied SQL files. Archive for reference.

```bash
mv /home/bombayengg/public_html/database_migrations /home/bombayengg/backups/
```

---

## Step 5: REMOVE DEPLOYMENT/ROLLBACK FILES

```bash
mv /home/bombayengg/public_html/rollback_seo_phase1.sh /home/bombayengg/backups/
mv /home/bombayengg/public_html/rollback_seo_phase2.sh /home/bombayengg/backups/
mv /home/bombayengg/public_html/rollback_seo_phase3.sh /home/bombayengg/backups/
mv /home/bombayengg/public_html/backup_and_deploy.sh /home/bombayengg/backups/
mv /home/bombayengg/public_html/backup_database.sh /home/bombayengg/backups/
mv /home/bombayengg/public_html/BACKUP_SETUP_GUIDE.md /home/bombayengg/backups/
```

---

## Step 6: REMOVE MISCELLANEOUS JUNK

```bash
# Old config backup
mv /home/bombayengg/public_html/config.inc.php-og /home/bombayengg/backups/

# Old/test files
mv /home/bombayengg/public_html/buffered_login.php /home/bombayengg/backups/
mv /home/bombayengg/public_html/contact-form.html /home/bombayengg/backups/
rm /home/bombayengg/public_html/test.txt 2>/dev/null
rm /home/bombayengg/public_html/pump_inquiry_debug.log 2>/dev/null
rm /home/bombayengg/public_html/ocr-debug.php 2>/dev/null

# Redundant archive
rm /home/bombayengg/public_html/lib/tinypdf.zip

# Zip archive (investigate first)
mv /home/bombayengg/public_html/instant_code-php-mysql.zip /home/bombayengg/backups/

# macOS metadata
find /home/bombayengg/public_html -name ".DS_Store" -delete

# npm lockfile (not used)
rm /home/bombayengg/public_html/package-lock.json 2>/dev/null

# Old SQL files in root
mv /home/bombayengg/public_html/*.sql /home/bombayengg/backups/old-data/ 2>/dev/null

# Test file in cron
mv /home/bombayengg/public_html/cron/test-attendance-email.php /home/bombayengg/backups/

# Google verification HTML (no longer needed)
rm /home/bombayengg/public_html/xsite/ftwycg9d6hqcqu5gqnv1d4r75xxnls.html
```

---

## Step 7: REMOVE BES DIRECTORY (Confirmed Dead)

```bash
mv /home/bombayengg/public_html/BES /home/bombayengg/backups/
```

---

## Step 8: CLEAN DEV FILES (Keep CLAUDE.md + claudemd/)

```bash
# Remove unused dev artifacts (CLAUDE.md and claudemd/ stay)
mv /home/bombayengg/public_html/claudetodo /home/bombayengg/backups/
mv /home/bombayengg/public_html/.claude /home/bombayengg/backups/

# Remove Google verification file (no longer needed)
rm /home/bombayengg/public_html/xsite/ftwycg9d6hqcqu5gqnv1d4r75xxnls.html
```

---

## Step 9: VERIFY (Post-Cleanup)

After cleanup, verify:
1. Site loads correctly: `curl -I https://www.bombayengg.net/`
2. Admin works: `curl -I https://www.bombayengg.net/xadmin/`
3. Product pages load: `curl -I https://www.bombayengg.net/motor/`
4. Pump pages load: `curl -I https://www.bombayengg.net/pump/`
5. Contact page works: `curl -I https://www.bombayengg.net/contact-us/`
6. Landing pages work: `curl -I https://www.bombayengg.net/motors-in-mumbai`
7. HRMS works (if applicable)
8. Cron jobs still fire correctly

---

## Items NOT to Remove

| Item | Reason |
|------|--------|
| `xsite/` | Live frontend |
| `xadmin/` | Admin panel |
| `core/` | Shared PHP includes (review CAMS files separately) |
| `cron/` | Active HRMS cron jobs |
| `uploads/` | User-uploaded files |
| `lib/` | Libraries (except tinypdf.zip) |
| `config/` | Site configuration |
| `hrms-sw.js` | HRMS service worker (if PWA active) |
| `robots.txt` | SEO file |
| `.htaccess` | Server config |
| `sitemap_index.xml` | SEO file |
| CAMS files in `core/` | Active biometric integration (confirmed) |
| `cams-callback.php` (root) | Biometric device callback (confirmed active) |
| `cams-integration/` | CAMS integration module (confirmed active) |
| `CLAUDE.md` | Development instructions (keep) |
| `claudemd/` | Development documentation (keep) |

---

## Estimated Space Recovery

| Category | Size | Priority |
|----------|------|----------|
| vendor/ (block access) | 111MB | CRITICAL |
| Database backups | 5.6MB | CRITICAL |
| composer.phar | 3.2MB | HIGH |
| One-off PHP scripts | ~1.2MB | HIGH |
| claudemd/ + claudetodo/ | 2.4MB | LOW |
| Raw data files | ~165KB | MEDIUM |
| Migration SQL files | ~384KB | MEDIUM |
| BES directory | 728KB | MEDIUM |
| Misc junk | ~2MB | LOW |
| **TOTAL** | **~375MB** | |

---

## Decisions (Confirmed 2026-02-21)

1. **BES directory** — DEAD. Archive to backups and remove from web root.
2. **CAMS integration** — ACTIVE. Keep all CAMS files in core/, cams-callback.php, and cams-integration/.
3. **Dev files** — KEEP `CLAUDE.md` and `claudemd/` on this server. Remove `claudetodo/` and `.claude/` settings.
4. **Google verification file** — REMOVE. No longer needed.
5. **composer.json/lock** — Keep for dependency management. Block vendor/ via .htaccess.
