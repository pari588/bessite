#!/bin/bash

# SSL Certificate Installation Script for bombayengg.net
# Run this script as root to install the complete SSL certificate chain
# Usage: sudo bash install_ssl_certificate.sh

set -e

echo "=========================================="
echo "SSL Certificate Installation Script"
echo "Domain: bombayengg.net"
echo "=========================================="
echo ""

CERT_DIR="/home/bombayengg/public_html"
VIRTUALMIN_DIR="/etc/ssl/virtualmin/174704938526080"
WEBMIN_DIR="/etc/webmin"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "ERROR: This script must be run as root"
   echo "Usage: sudo bash $0"
   exit 1
fi

# Check if certificate files exist
if [[ ! -f "$CERT_DIR/bombayengg_net.crt" ]]; then
   echo "ERROR: Certificate files not found in $CERT_DIR"
   exit 1
fi

echo "Step 1: Backing up current certificates..."
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
cp "$VIRTUALMIN_DIR/ssl.cert" "$VIRTUALMIN_DIR/ssl.cert.backup.$BACKUP_DATE"
cp "$WEBMIN_DIR/bombayengg.net.ca" "$WEBMIN_DIR/bombayengg.net.ca.backup.$BACKUP_DATE"
echo "✓ Backups created with timestamp: $BACKUP_DATE"
echo ""

echo "Step 2: Creating complete certificate chain..."
cat "$CERT_DIR/bombayengg_net.crt" \
    "$CERT_DIR/SectigoPublicServerAuthenticationCADVR36.crt" \
    "$CERT_DIR/SectigoPublicServerAuthenticationRootR46_USERTrust.crt" \
    "$CERT_DIR/USERTrustRSACertificationAuthority.crt" > /tmp/complete_chain.crt
echo "✓ Certificate chain created"
echo ""

echo "Step 3: Installing certificate to Virtualmin..."
cp /tmp/complete_chain.crt "$VIRTUALMIN_DIR/ssl.cert"
chmod 600 "$VIRTUALMIN_DIR/ssl.cert"
echo "✓ Certificate installed to: $VIRTUALMIN_DIR/ssl.cert"
echo ""

echo "Step 4: Installing certificate chain to Webmin..."
cat "$CERT_DIR/SectigoPublicServerAuthenticationCADVR36.crt" \
    "$CERT_DIR/SectigoPublicServerAuthenticationRootR46_USERTrust.crt" \
    "$CERT_DIR/USERTrustRSACertificationAuthority.crt" > "$WEBMIN_DIR/bombayengg.net.ca"
chmod 600 "$WEBMIN_DIR/bombayengg.net.ca"
echo "✓ Certificate chain installed to: $WEBMIN_DIR/bombayengg.net.ca"
echo ""

echo "Step 5: Restarting Apache..."
if command -v systemctl &> /dev/null; then
    systemctl restart httpd 2>/dev/null || systemctl restart apache2 2>/dev/null
    echo "✓ Apache restarted"
else
    service httpd restart 2>/dev/null || service apache2 restart 2>/dev/null
    echo "✓ Apache restarted"
fi
echo ""

echo "=========================================="
echo "✓ SSL Certificate Installation Complete!"
echo "=========================================="
echo ""

echo "Verification:"
echo "You can verify the chain with:"
echo "  openssl s_client -connect www.bombayengg.net:443 -servername www.bombayengg.net"
echo ""
echo "You should see:"
echo "  - depth=2 (root)"
echo "  - depth=1 (intermediate)"
echo "  - depth=0 (your certificate)"
echo ""

# Cleanup
rm -f /tmp/complete_chain.crt

echo "Backups saved (in case of rollback needed):"
echo "  - $VIRTUALMIN_DIR/ssl.cert.backup.$BACKUP_DATE"
echo "  - $WEBMIN_DIR/bombayengg.net.ca.backup.$BACKUP_DATE"
