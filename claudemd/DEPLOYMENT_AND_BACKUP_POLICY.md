# Deployment and Backup Policy - Bombay Engineering Syndicate

**Last Updated:** December 5, 2025
**Version:** 1.0

---

## 📋 OVERVIEW

This document establishes the mandatory procedures for any code changes, file modifications, and deployments to the Bombay Engineering Syndicate website. The policy ensures:

- ✅ Complete backup of all files before changes
- ✅ All code changes committed to GitHub
- ✅ Ability to restore to previous versions if issues occur
- ✅ Audit trail of all modifications
- ✅ Zero data loss

---

## 🔄 MANDATORY WORKFLOW FOR ANY CODE CHANGES

### Step 1: Create Pre-Change Backup (REQUIRED)

**Before making ANY changes to code/files:**

```bash
# Create timestamped backup of entire website
tar -czf backups/website_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude=uploads/fuel-expense \
  --exclude=uploads/voucher \
  --exclude=node_modules \
  --exclude=.git \
  /home/bombayengg/public_html/

# Create database backup
mysqldump -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg > \
  database_backups/bombayengg_$(date +%Y%m%d_%H%M%S).sql

echo "✅ Backups created successfully"
```

**Backup includes:**
- All code files (xsite/, xadmin/, core/)
- All configuration files
- All stylesheets and assets
- Documentation files
- Excludes: Large upload files, node_modules, git history

---

### Step 2: Make Code Changes

Make the requested modifications to the files.

**Important:**
- Use prepared statements for database queries (security)
- Follow existing code conventions
- Test changes locally if possible
- Document what was changed

---

### Step 3: Commit to GitHub (MANDATORY)

**All code changes MUST be committed to GitHub before deployment.**

```bash
# Stage all changed files
git add {modified-files}

# Commit with descriptive message
git commit -m "Brief description of changes

- Detailed point 1
- Detailed point 2
- Detailed point 3

Fixes: #issue-number (if applicable)

🤖 Generated with Claude Code

Co-Authored-By: Claude <noreply@anthropic.com>"

# Push to main branch (REQUIRED)
git push https://pari588:{PAT}@github.com/pari588/bessite.git main
```

**Commit message format:**
```
{One-line summary of changes}

{Detailed explanation of what was changed and why}
- Bullet point 1
- Bullet point 2

🤖 Generated with Claude Code

Co-Authored-By: Claude <noreply@anthropic.com>
```

---

### Step 4: Backup Database (MANDATORY)

**After any database schema changes or modifications:**

```bash
# Create timestamped database backup
mysqldump -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg > \
  database_backups/bombayengg_$(date +%Y%m%d_%H%M%S).sql

# Add to git if significant changes
git add database_backups/bombayengg_*.sql
git commit -m "Add database backup - {description of changes}"
git push origin main
```

---

### Step 5: Test Changes (RECOMMENDED)

- Test the modified functionality on the live site
- Check console for errors
- Verify database operations if applicable
- Check file permissions (755 for dirs, 644 for files)

---

## 🚨 IF AN ISSUE OCCURS - RESTORE PROCEDURE

### Option 1: Restore to Previous Git Commit

**If issue is detected immediately:**

```bash
# See recent commits
git log --oneline -10

# View changes in a specific commit
git show {commit-hash}

# Revert to previous commit
git revert {commit-hash}

# Or force reset (use with caution)
git reset --hard {previous-commit-hash}
git push origin main --force
```

### Option 2: Restore from File Backup

**If you need to restore entire website to previous state:**

```bash
# List available backups
ls -lh backups/website_backup_*.tar.gz

# Extract specific backup
cd /
tar -xzf /home/bombayengg/public_html/backups/website_backup_20251205_174319.tar.gz

# Verify files were restored
ls -la /home/bombayengg/public_html/
```

### Option 3: Restore Database

**To restore database to previous state:**

```bash
# List available database backups
ls -lh database_backups/bombayengg_*.sql

# Restore from specific backup
mysql -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg < \
  database_backups/bombayengg_20251205_174319.sql

# Verify restoration
mysql -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg -e "SELECT COUNT(*) FROM _live_pump;"
```

---

## 📊 BACKUP LOCATIONS & RETENTION

### File Backups
- **Location:** `/home/bombayengg/public_html/backups/`
- **Naming:** `website_backup_YYYYMMDD_HHMMSS.tar.gz`
- **Retention:** Keep last 10 backups (minimum 2 GB space)
- **Size:** ~200-300 MB each (compressed)

### Database Backups
- **Location:** `/home/bombayengg/public_html/database_backups/`
- **Naming:** `bombayengg_YYYYMMDD_HHMMSS.sql`
- **Retention:** Keep all backups (easy to manage, ~1-2 MB each)
- **Frequency:** After every significant change

### GitHub History
- **Repository:** https://github.com/pari588/bessite
- **Branch:** main
- **Retention:** Unlimited (GitHub keeps all history)
- **Access:** anytime, any commit

---

## 🔐 IMPORTANT: ALWAYS FOLLOW THIS SEQUENCE

```
┌─────────────────────────────────┐
│  1. Create File Backup          │
│     (website + database)        │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│  2. Make Code Changes           │
│     (modify files as needed)    │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│  3. Commit to GitHub (REQUIRED) │
│     (git add, commit, push)     │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│  4. Backup Database             │
│     (if schema changes)         │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│  5. Test Changes                │
│     (verify everything works)   │
└────────────┬────────────────────┘
             │
             ▼
    ✅ DONE - Safe to Continue
```

---

## 📝 CHANGE LOG TEMPLATE

When requesting changes, create a record:

```
DATE: 2025-12-05
TIME: 17:45:00
CHANGED BY: Claude Code / Developer Name
CHANGES: Brief description of changes made
FILES MODIFIED:
  - xsite/index.php (line 45-50)
  - core/common.inc.php (function updateSetting)
GITHUB COMMIT: cf6126b
FILE BACKUP: website_backup_20251205_174500.tar.gz
DB BACKUP: bombayengg_20251205_174500.sql
STATUS: ✅ DEPLOYED
TESTING: All features tested, no issues found
```

---

## 🔄 GITHUB COMMANDS QUICK REFERENCE

### Check Status
```bash
git status                          # Show changed files
git log --oneline -10              # Show last 10 commits
git diff                           # Show differences
```

### Commit & Push
```bash
git add .                          # Stage all changes
git commit -m "Commit message"     # Create commit
git push origin main               # Push to GitHub
```

### Undo Changes
```bash
git revert {commit-hash}           # Create opposite commit (safe)
git reset --soft HEAD~1            # Undo last commit, keep changes
git reset --hard {commit-hash}     # Force reset to specific commit
```

### View History
```bash
git log --oneline --graph          # Visual commit history
git show {commit-hash}             # Show specific commit details
git diff {commit1} {commit2}       # Compare two commits
```

---

## ✅ DEPLOYMENT CHECKLIST

Before considering a change "complete", verify:

- [ ] File backup created (`backups/website_backup_*.tar.gz`)
- [ ] Database backup created (`database_backups/bombayengg_*.sql`)
- [ ] All changes committed to GitHub
- [ ] GitHub push successful (check https://github.com/pari588/bessite)
- [ ] Code tested and working
- [ ] No errors in PHP logs (`/var/log/php-fpm/error.log`)
- [ ] No database errors in logs
- [ ] File permissions correct (755 dirs, 644 files)
- [ ] Change documented in change log

---

## 🚨 EMERGENCY RESTORE PROCEDURE

**If production is broken and needs immediate restore:**

```bash
# STEP 1: Stop the website (if needed)
# sudo systemctl stop apache2

# STEP 2: Check most recent backups
ls -lth backups/website_backup_*.tar.gz | head -3
ls -lth database_backups/bombayengg_*.sql | head -3

# STEP 3: Restore files
cd /
sudo tar -xzf /home/bombayengg/public_html/backups/website_backup_20251205_174319.tar.gz

# STEP 4: Restore database
mysql -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg < \
  /home/bombayengg/public_html/database_backups/bombayengg_20251205_174319.sql

# STEP 5: Fix permissions
find /home/bombayengg/public_html -type d -exec chmod 755 {} \;
find /home/bombayengg/public_html -type f -exec chmod 644 {} \;
chmod 755 /home/bombayengg/public_html/core/paddleocr_processor.py

# STEP 6: Restart services
# sudo systemctl start apache2

# STEP 7: Verify
curl -I https://www.bombayengg.com
```

---

## 📞 SUPPORT & DOCUMENTATION

### Key Files
- **Backup Policy:** This file (`DEPLOYMENT_AND_BACKUP_POLICY.md`)
- **Site Architecture:** `SITE_STRUCTURE_OVERVIEW.md`
- **Credentials:** `.git-credentials-info.txt` (local, not in git)

### Database Connection
```
Host: localhost
User: bombayengg
Password: oCFCrCMwKyy5jzg
Database: bombayengg
```

### GitHub Details
```
Repository: https://github.com/pari588/bessite
Username: pari588
Branch: main
```

---

## 🎯 POLICY ENFORCEMENT

**This policy is MANDATORY for all code changes:**

1. **No exceptions** - Every change requires backup + GitHub commit
2. **Automatic procedure** - Should be done before making any modifications
3. **Version control** - GitHub is the single source of truth
4. **Restore capability** - Any issue can be reverted to previous working state
5. **Audit trail** - Complete history of who changed what and when

---

## 📅 LAST REVIEWED

- **Date:** December 5, 2025
- **Next Review:** As needed
- **Status:** ✅ ACTIVE

---

**Remember:** Backups are useless if you don't test them. Always verify restore procedures work before you actually need them in an emergency!

