#!/bin/bash
# Rollback script for pump inquiry form changes (2026-02-20)
# Changes rolled back:
#   - Removed pumpingDistance field (distance in meters)
#   - Removed heightDifference field (height in meters)
#   - Changed requiredDischarge placeholder to "LPM (Liters Per Minute)"
#   - Removed operatingHours field (hours per day)

BASEDIR="$(cd "$(dirname "$0")" && pwd)"
WEBROOT="/home/bombayengg/public_html"

echo "Rolling back pump inquiry changes..."

cp "$BASEDIR/x-pump-inquiry.php.bak" "$WEBROOT/xsite/mod/pump-inquiry/x-pump-inquiry.php"
echo "  Restored: xsite/mod/pump-inquiry/x-pump-inquiry.php"

cp "$BASEDIR/x-pump-inquiry-inc.php.bak" "$WEBROOT/xsite/mod/pump-inquiry/x-pump-inquiry-inc.php"
echo "  Restored: xsite/mod/pump-inquiry/x-pump-inquiry-inc.php"

cp "$BASEDIR/x-pump-inquiry.inc.js.bak" "$WEBROOT/xsite/mod/pump-inquiry/inc/js/x-pump-inquiry.inc.js"
echo "  Restored: xsite/mod/pump-inquiry/inc/js/x-pump-inquiry.inc.js"

cp "$BASEDIR/x-pump-inquiry-list.php.bak" "$WEBROOT/xadmin/mod/pump-inquiry/x-pump-inquiry-list.php"
echo "  Restored: xadmin/mod/pump-inquiry/x-pump-inquiry-list.php"

echo "Rollback complete."
