#!/bin/bash

################################################################################
# BACKUP AND DEPLOY SCRIPT
# Bombay Engineering Syndicate
#
# Purpose: Automate the backup, commit, and GitHub push workflow
# Usage: ./backup_and_deploy.sh "Description of changes"
#
# This script ensures:
# - Files are backed up before changes
# - Database is backed up
# - Changes are committed to GitHub
# - Proper logging and documentation
#
################################################################################

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
WEBSITE_ROOT="/home/bombayengg/public_html"
BACKUP_DIR="$WEBSITE_ROOT/backups"
DB_BACKUP_DIR="$WEBSITE_ROOT/database_backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_USER="bombayengg"
DB_NAME="bombayengg"
DB_PASS="oCFCrCMwKyy5jzg"
GITHUB_USERNAME="pari588"
GITHUB_PAT_FILE="$WEBSITE_ROOT/.git-credentials-info.txt"

################################################################################
# FUNCTIONS
################################################################################

print_header() {
    echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC}  $1"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ ERROR: $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

################################################################################
# MAIN SCRIPT
################################################################################

# Check if description was provided
if [ -z "$1" ]; then
    print_error "No description provided"
    echo ""
    echo "Usage: ./backup_and_deploy.sh \"Description of changes\""
    echo ""
    echo "Example:"
    echo "  ./backup_and_deploy.sh \"Fix pump detail page styling\""
    exit 1
fi

CHANGE_DESCRIPTION="$1"

print_header "BACKUP AND DEPLOY WORKFLOW"

# Step 1: Check Git is available
print_info "Checking git configuration..."
if ! command -v git &> /dev/null; then
    print_error "Git is not installed"
    exit 1
fi
print_success "Git is available"

# Step 2: Create website backup
print_info "Creating website backup..."
mkdir -p "$BACKUP_DIR"

BACKUP_FILE="$BACKUP_DIR/website_backup_${TIMESTAMP}.tar.gz"

tar -czf "$BACKUP_FILE" \
    --exclude=uploads/fuel-expense \
    --exclude=uploads/voucher \
    --exclude=node_modules \
    --exclude=.git \
    --exclude=.git-credentials-info.txt \
    "$WEBSITE_ROOT/" 2>/dev/null

BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
print_success "Website backup created: $BACKUP_FILE ($BACKUP_SIZE)"

# Step 3: Create database backup
print_info "Creating database backup..."
DB_BACKUP_FILE="$DB_BACKUP_DIR/bombayengg_${TIMESTAMP}.sql"
mkdir -p "$DB_BACKUP_DIR"

mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DB_BACKUP_FILE" 2>/dev/null

DB_BACKUP_SIZE=$(du -h "$DB_BACKUP_FILE" | cut -f1)
print_success "Database backup created: $DB_BACKUP_FILE ($DB_BACKUP_SIZE)"

# Step 4: Navigate to git repo
cd "$WEBSITE_ROOT"

# Step 5: Check Git status
print_info "Checking git status..."
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    print_error "Not in a git repository"
    exit 1
fi

GIT_STATUS=$(git status --short)
if [ -z "$GIT_STATUS" ]; then
    print_warning "No changes detected in working directory"
    print_info "To add backups, run: git add database_backups/$TIMESTAMP"
    exit 0
fi

# Step 6: Show changes
print_info "Files to be committed:"
echo "$GIT_STATUS" | sed 's/^/  /'

# Step 7: Commit changes
print_info "Committing changes to git..."

# Build commit message
COMMIT_MSG="$CHANGE_DESCRIPTION

Automated backup and deploy workflow
- File backup: website_backup_${TIMESTAMP}.tar.gz
- Database backup: bombayengg_${TIMESTAMP}.sql
- Changes committed and ready for deployment

🤖 Generated with Claude Code

Co-Authored-By: Claude <noreply@anthropic.com>"

git add -A
git commit -m "$COMMIT_MSG" 2>/dev/null

COMMIT_HASH=$(git rev-parse --short HEAD)
print_success "Committed with hash: $COMMIT_HASH"

# Step 8: Push to GitHub
print_info "Pushing to GitHub..."

# Extract PAT from credentials file if it exists
if [ -f "$GITHUB_PAT_FILE" ]; then
    GITHUB_PAT=$(grep "ghp_" "$GITHUB_PAT_FILE" | head -1 | awk '{print $NF}')
else
    print_error "GitHub credentials file not found"
    print_warning "Commit was created locally but not pushed"
    exit 1
fi

if [ -z "$GITHUB_PAT" ]; then
    print_error "Could not find GitHub PAT in credentials file"
    exit 1
fi

PUSH_URL="https://${GITHUB_USERNAME}:${GITHUB_PAT}@github.com/pari588/bessite.git"

if git push "$PUSH_URL" main 2>/dev/null; then
    print_success "Pushed to GitHub successfully"
else
    print_error "Failed to push to GitHub"
    exit 1
fi

# Step 9: Create deployment log
print_info "Creating deployment log..."

LOG_FILE="$WEBSITE_ROOT/DEPLOYMENT_LOG.txt"
{
    echo "==============================================================="
    echo "DEPLOYMENT LOG ENTRY"
    echo "==============================================================="
    echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Description: $CHANGE_DESCRIPTION"
    echo "Git Commit: $COMMIT_HASH"
    echo "File Backup: website_backup_${TIMESTAMP}.tar.gz ($BACKUP_SIZE)"
    echo "DB Backup: bombayengg_${TIMESTAMP}.sql ($DB_BACKUP_SIZE)"
    echo "Status: ✅ DEPLOYED TO GITHUB"
    echo "Github: https://github.com/pari588/bessite/commit/$COMMIT_HASH"
    echo ""
} >> "$LOG_FILE"

print_success "Deployment log updated"

# Step 10: Summary
print_header "DEPLOYMENT COMPLETE"

echo ""
echo -e "${GREEN}Summary:${NC}"
echo "  Timestamp:       $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Description:     $CHANGE_DESCRIPTION"
echo "  Git Commit:      $COMMIT_HASH"
echo "  GitHub:          https://github.com/pari588/bessite/commit/$COMMIT_HASH"
echo ""
echo -e "${GREEN}Backups Created:${NC}"
echo "  Files:           $BACKUP_FILE ($BACKUP_SIZE)"
echo "  Database:        $DB_BACKUP_FILE ($DB_BACKUP_SIZE)"
echo ""
echo -e "${GREEN}Restore Commands:${NC}"
echo "  Git Revert:      git revert $COMMIT_HASH"
echo "  File Restore:    tar -xzf $BACKUP_FILE"
echo "  DB Restore:      mysql -u $DB_USER -p $DB_NAME < $DB_BACKUP_FILE"
echo ""

print_success "Workflow completed successfully!"
print_warning "Remember to test the changes on the live site"

exit 0
