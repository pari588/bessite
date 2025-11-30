#!/bin/bash

# Contact Form Validation Fixes - Restoration Script
# This script restores the contact form files to their original state
# Usage: bash restore-contact-form.sh

BACKUP_DIR="/home/bombayengg/public_html"
BACKUP_DATE="20251031_065119"
BACKUP_DATE_HTML="20251031_065122"

echo "========================================"
echo "Contact Form Restoration Script"
echo "========================================"
echo ""

# Check if backup files exist
if [ ! -f "$BACKUP_DIR/x-contact-us-tpl.php.backup.$BACKUP_DATE" ]; then
    echo "❌ Error: Backend form backup file not found!"
    echo "   Expected: $BACKUP_DIR/x-contact-us-tpl.php.backup.$BACKUP_DATE"
    exit 1
fi

if [ ! -f "$BACKUP_DIR/contact-form.html.backup.$BACKUP_DATE_HTML" ]; then
    echo "❌ Error: HTML form backup file not found!"
    echo "   Expected: $BACKUP_DIR/contact-form.html.backup.$BACKUP_DATE_HTML"
    exit 1
fi

echo "Found backup files:"
echo "✓ Backend form: x-contact-us-tpl.php.backup.$BACKUP_DATE"
echo "✓ HTML form: contact-form.html.backup.$BACKUP_DATE_HTML"
echo ""

# Ask for confirmation
read -p "Are you sure you want to restore the contact form files? (yes/no) " -n 3 -r
echo ""
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "❌ Restoration cancelled."
    exit 1
fi

echo ""
echo "Restoring files..."
echo ""

# Restore backend form
if cp "$BACKUP_DIR/x-contact-us-tpl.php.backup.$BACKUP_DATE" "$BACKUP_DIR/xsite/mod/page/x-contact-us-tpl.php"; then
    echo "✓ Backend form restored: xsite/mod/page/x-contact-us-tpl.php"
else
    echo "❌ Failed to restore backend form!"
    exit 1
fi

# Restore HTML form
if cp "$BACKUP_DIR/contact-form.html.backup.$BACKUP_DATE_HTML" "$BACKUP_DIR/contact-form.html"; then
    echo "✓ HTML form restored: contact-form.html"
else
    echo "❌ Failed to restore HTML form!"
    exit 1
fi

echo ""
echo "========================================"
echo "✓ Restoration completed successfully!"
echo "========================================"
echo ""
echo "Note: Changes have been reverted to the original files."
