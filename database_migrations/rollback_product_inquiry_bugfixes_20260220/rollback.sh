#!/bin/bash
# Rollback script for product inquiry bug fixes (2026-02-20)
# Bugs fixed:
#   1. Voltage array duplicate key "4" (460 lost, overwritten by 480)
#   2. Undefined $expectedDeliveryTimeID variable in admin add-edit
#   3. Email sending raw IDs instead of human-readable labels
#   4. reCAPTCHA effectively optional (empty tokens allowed, dummy bypass)
#   5. requirementForRplcArr inconsistency (0 vs 2 for "No")

BASEDIR="$(cd "$(dirname "$0")" && pwd)"
WEBROOT="/home/bombayengg/public_html"

echo "Rolling back product inquiry bug fixes..."

cp "$BASEDIR/x-product-inquiry.php.bak" "$WEBROOT/xsite/mod/product-inquiry/x-product-inquiry.php"
echo "  Restored: xsite/mod/product-inquiry/x-product-inquiry.php"

cp "$BASEDIR/x-product-inquiry.inc.php.bak" "$WEBROOT/xsite/mod/product-inquiry/x-product-inquiry.inc.php"
echo "  Restored: xsite/mod/product-inquiry/x-product-inquiry.inc.php"

cp "$BASEDIR/x-product-inquiry-add-edit.php.bak" "$WEBROOT/xadmin/mod/product-inquiry/x-product-inquiry-add-edit.php"
echo "  Restored: xadmin/mod/product-inquiry/x-product-inquiry-add-edit.php"

cp "$BASEDIR/x-product-inquiry-list.php.bak" "$WEBROOT/xadmin/mod/product-inquiry/x-product-inquiry-list.php"
echo "  Restored: xadmin/mod/product-inquiry/x-product-inquiry-list.php"

cp "$BASEDIR/brevo.inc.php.bak" "$WEBROOT/core/brevo.inc.php"
echo "  Restored: core/brevo.inc.php"

echo "Rollback complete."
