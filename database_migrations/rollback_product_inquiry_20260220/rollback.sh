#!/bin/bash
# Rollback script for product inquiry form changes (2026-02-20)
# Changes rolled back:
#   - Made mountingID, typeOfMotorID, rpm mandatory
#   - Removed rpmD from replacement section (kept poleID)

BASEDIR="$(cd "$(dirname "$0")" && pwd)"
WEBROOT="/home/bombayengg/public_html"

echo "Rolling back product inquiry changes..."

cp "$BASEDIR/x-product-inquiry.php.bak" "$WEBROOT/xsite/mod/product-inquiry/x-product-inquiry.php"
echo "  Restored: xsite/mod/product-inquiry/x-product-inquiry.php"

cp "$BASEDIR/x-product-inquiry.inc.php.bak" "$WEBROOT/xsite/mod/product-inquiry/x-product-inquiry.inc.php"
echo "  Restored: xsite/mod/product-inquiry/x-product-inquiry.inc.php"

cp "$BASEDIR/x-product-inquiry.inc.js.bak" "$WEBROOT/xsite/mod/product-inquiry/inc/js/x-product-inquiry.inc.js"
echo "  Restored: xsite/mod/product-inquiry/inc/js/x-product-inquiry.inc.js"

cp "$BASEDIR/x-product-inquiry-add-edit.php.bak" "$WEBROOT/xadmin/mod/product-inquiry/x-product-inquiry-add-edit.php"
echo "  Restored: xadmin/mod/product-inquiry/x-product-inquiry-add-edit.php"

cp "$BASEDIR/x-product-inquiry-list.php.bak" "$WEBROOT/xadmin/mod/product-inquiry/x-product-inquiry-list.php"
echo "  Restored: xadmin/mod/product-inquiry/x-product-inquiry-list.php"

cp "$BASEDIR/xadmin-x-product-inquiry.inc.js.bak" "$WEBROOT/xadmin/mod/product-inquiry/inc/js/x-product-inquiry.inc.js"
echo "  Restored: xadmin/mod/product-inquiry/inc/js/x-product-inquiry.inc.js"

echo "Rollback complete."
