# Backup and Deploy Script Guide

**Last Updated:** December 5, 2025
**Script Location:** `/home/bombayengg/public_html/backup_and_deploy.sh`

---

## 📋 Overview

The `backup_and_deploy.sh` script automates the entire backup and GitHub deployment workflow. It ensures every code change is:

1. ✅ Properly backed up (files + database)
2. ✅ Committed to GitHub
3. ✅ Logged for audit trail
4. ✅ Easily reversible if issues occur

---

## 🚀 Quick Start

### Basic Usage

```bash
# Navigate to website directory
cd /home/bombayengg/public_html

# Run the script with a description
./backup_and_deploy.sh "Your description of changes"
```

### Examples

```bash
# Fix a bug
./backup_and_deploy.sh "Fix pump detail page styling issues"

# Add new feature
./backup_and_deploy.sh "Add new fuel expense report module"

# Update configuration
./backup_and_deploy.sh "Update email settings for Brevo integration"

# Database changes
./backup_and_deploy.sh "Add new fields to pump specifications table"
```

---

## 📝 What the Script Does

### Step 1: Validates Environment
- Checks git is installed
- Verifies we're in git repository
- Confirms GitHub credentials are available

### Step 2: Creates File Backup
```bash
tar -czf backups/website_backup_YYYYMMDD_HHMMSS.tar.gz \
  --exclude=uploads/fuel-expense \
  --exclude=uploads/voucher \
  --exclude=.git \
  /home/bombayengg/public_html/
```

**Size:** ~200-300 MB (compressed)
**Location:** `/home/bombayengg/public_html/backups/`

### Step 3: Creates Database Backup
```bash
mysqldump -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg > \
  database_backups/bombayengg_YYYYMMDD_HHMMSS.sql
```

**Size:** ~1-2 MB
**Location:** `/home/bombayengg/public_html/database_backups/`

### Step 4: Commits to Git
Creates a commit with:
- Your change description
- Timestamp
- Backup file references
- Git commit hash

### Step 5: Pushes to GitHub
Pushes to main branch at: `https://github.com/pari588/bessite`

### Step 6: Creates Deployment Log
Records the entire deployment in `DEPLOYMENT_LOG.txt`

---

## 📊 Script Output Example

```
╔════════════════════════════════════════════════════════╗
║  BACKUP AND DEPLOY WORKFLOW
╚════════════════════════════════════════════════════════╝

ℹ️  Checking git configuration...
✅ Git is available
ℹ️  Creating website backup...
✅ Website backup created: /home/bombayengg/public_html/backups/website_backup_20251205_174500.tar.gz (285M)
ℹ️  Creating database backup...
✅ Database backup created: /home/bombayengg/public_html/database_backups/bombayengg_20251205_174500.sql (1.2M)
ℹ️  Checking git status...
ℹ️  Files to be committed:
   M xsite/css/style.css
   M core/common.inc.php
   A claudemd/NEW_FEATURE.md
ℹ️  Committing changes to git...
✅ Committed with hash: a1b2c3d
ℹ️  Pushing to GitHub...
✅ Pushed to GitHub successfully
ℹ️  Creating deployment log...
✅ Deployment log updated

╔════════════════════════════════════════════════════════╗
║  DEPLOYMENT COMPLETE
╚════════════════════════════════════════════════════════╝

Summary:
  Timestamp:       2025-12-05 17:45:00
  Description:     Fix pump detail page styling issues
  Git Commit:      a1b2c3d
  GitHub:          https://github.com/pari588/bessite/commit/a1b2c3d

Backups Created:
  Files:           /home/bombayengg/public_html/backups/website_backup_20251205_174500.tar.gz (285M)
  Database:        /home/bombayengg/public_html/database_backups/bombayengg_20251205_174500.sql (1.2M)

Restore Commands:
  Git Revert:      git revert a1b2c3d
  File Restore:    tar -xzf /home/bombayengg/public_html/backups/website_backup_20251205_174500.tar.gz
  DB Restore:      mysql -u bombayengg -p bombayengg < /home/bombayengg/public_html/database_backups/bombayengg_20251205_174500.sql

✅ Workflow completed successfully!
⚠️  Remember to test the changes on the live site
```

---

## ✅ Pre-Requisites

### Requirements
- Bash shell (installed on all Linux servers)
- Git (must be installed)
- MySQL/MariaDB (for database backups)
- Read/write permissions to `/home/bombayengg/public_html/`

### Configuration Files (Required)
- `.git-credentials-info.txt` - Contains GitHub PAT
- `.gitignore` - Includes `.git-credentials-info.txt` to prevent exposure

### Database Credentials
Script uses hardcoded credentials (can be customized):
```
User: bombayengg
Password: oCFCrCMwKyy5jzg
Database: bombayengg
```

---

## 🔍 What Gets Backed Up

### Website Files
```
✅ All code files (xsite/, xadmin/, core/)
✅ Configuration and settings
✅ CSS, JavaScript, images
✅ Documentation (claudemd/)
✅ Upload folders (except fuel-expense and voucher)

❌ Excluded:
  - uploads/fuel-expense/ (sensitive files)
  - uploads/voucher/ (sensitive files)
  - .git/ (GitHub history)
  - node_modules/ (if any)
  - .git-credentials-info.txt (credentials)
```

### Database
```
✅ Complete MySQL dump
✅ All tables with data
✅ Schema and structure
✅ Admin users and settings
✅ All product data
✅ Customer records
```

---

## 📂 Backup File Naming

### Website Backups
```
website_backup_20251205_174500.tar.gz
                  │││││││ │││││││
                  │││││││ └ Seconds (UTC)
                  │││││││
                  └ YYYYMMdd_HHMMSS (timestamp)
```

Location: `/home/bombayengg/public_html/backups/`

### Database Backups
```
bombayengg_20251205_174500.sql
           │││││││ │││││││
           │││││││ └ Seconds (UTC)
           │││││││
           └ YYYYMMdd_HHMMSS (timestamp)
```

Location: `/home/bombayengg/public_html/database_backups/`

---

## 🔄 Restore Procedures

### If You Need to Restore

#### Option 1: Revert Last Commit (SAFEST)
```bash
cd /home/bombayengg/public_html
git revert a1b2c3d
git push origin main
```

#### Option 2: Restore File Backup
```bash
# Extract backup to root filesystem
cd /
tar -xzf /home/bombayengg/public_html/backups/website_backup_20251205_174500.tar.gz

# Fix permissions
find /home/bombayengg/public_html -type d -exec chmod 755 {} \;
find /home/bombayengg/public_html -type f -exec chmod 644 {} \;
```

#### Option 3: Restore Database
```bash
mysql -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg < \
  /home/bombayengg/public_html/database_backups/bombayengg_20251205_174500.sql
```

---

## 🛠️ Manual Script Execution (Advanced)

If you need to manually run parts of the workflow:

```bash
# Create file backup
tar -czf backups/website_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude=uploads/fuel-expense \
  --exclude=uploads/voucher \
  --exclude=.git \
  /home/bombayengg/public_html/

# Create database backup
mysqldump -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg > \
  database_backups/bombayengg_$(date +%Y%m%d_%H%M%S).sql

# Stage and commit
git add .
git commit -m "Your commit message"

# Push to GitHub
git push origin main
```

---

## 📋 Deployment Log

All deployments are logged in: `/home/bombayengg/public_html/DEPLOYMENT_LOG.txt`

### Log Entry Example
```
===============================================================
DEPLOYMENT LOG ENTRY
===============================================================
Timestamp: 2025-12-05 17:45:00
Description: Fix pump detail page styling issues
Git Commit: a1b2c3d
File Backup: website_backup_20251205_174500.tar.gz (285M)
DB Backup: bombayengg_20251205_174500.sql (1.2M)
Status: ✅ DEPLOYED TO GITHUB
Github: https://github.com/pari588/bessite/commit/a1b2c3d
```

---

## ⚙️ Customization

### Modify Excluded Files

Edit the tar command in the script:
```bash
tar -czf "$BACKUP_FILE" \
    --exclude=uploads/fuel-expense \
    --exclude=uploads/voucher \
    --exclude=uploads/OTHER_FOLDER \  # Add here
    --exclude=node_modules \
    --exclude=.git \
    "$WEBSITE_ROOT/"
```

### Change Database Credentials

Edit these variables in the script:
```bash
DB_USER="bombayengg"
DB_NAME="bombayengg"
DB_PASS="oCFCrCMwKyy5jzg"
```

### Change GitHub Repository

Edit the GITHUB push in script or use:
```bash
git remote set-url origin https://github.com/new-username/new-repo.git
```

---

## 🚨 Troubleshooting

### Script fails with "not in a git repository"
```bash
cd /home/bombayengg/public_html
git status
```

### GitHub push fails
- Check `.git-credentials-info.txt` exists
- Verify GitHub PAT is valid
- Test: `ping github.com`

### Backup space issues
```bash
# Check available space
df -h /home/bombayengg/

# Remove old backups
ls -lth backups/ | tail -20
rm backups/website_backup_old_*.tar.gz
```

### Database backup fails
- Check MySQL is running: `sudo systemctl status mariadb`
- Verify credentials: `mysql -u bombayengg -p`

---

## 📞 Support

### Key Files
- **Script:** `/home/bombayengg/public_html/backup_and_deploy.sh`
- **Policy:** `/home/bombayengg/public_html/claudemd/DEPLOYMENT_AND_BACKUP_POLICY.md`
- **Log:** `/home/bombayengg/public_html/DEPLOYMENT_LOG.txt`

### Common Tasks
```bash
# Check recent deployments
tail -50 DEPLOYMENT_LOG.txt

# List recent backups
ls -lth backups/ | head -10
ls -lth database_backups/ | head -10

# View recent git commits
git log --oneline -10
```

---

## ✅ Best Practices

1. **Always use the script** - Manual deployments increase risk
2. **Test changes first** - Before pushing to production
3. **Clear commit messages** - Describe what changed and why
4. **Monitor the site** - After each deployment
5. **Keep backups** - Don't delete old backups too quickly
6. **Review GitHub** - Check commits at https://github.com/pari588/bessite

---

**Status:** ✅ ACTIVE
**Last Updated:** December 5, 2025
**Version:** 1.0

