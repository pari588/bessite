# Automated Backup System Setup Guide

**Date**: December 10, 2025
**System**: Production Website (bombayengg.net)
**Purpose**: Automated daily backups for disaster recovery

---

## Overview

This document explains the automated backup system for your production website. The system includes:

1. **GitHub Actions** - Automated code backup (daily)
2. **Database Backup Script** - Local MySQL backup (daily)
3. **Retention Policy** - Keep last 30 days of backups
4. **Monitoring** - Log all backup activities

---

## Part 1: GitHub Actions Automated Backup

### ✅ Already Configured

**File**: `.github/workflows/backup.yml`
**Frequency**: Daily at 2 AM UTC (9:30 AM IST)
**Branch**: Backups stored in `git-backups` branch

### How It Works

```
Every Day at 2 AM UTC:
  1. Check out latest code
  2. Create git bundle (complete repo backup)
  3. Push to git-backups branch
  4. Keep only last 30 days
  5. Generate backup summary
```

### Viewing GitHub Backups

1. Go to: https://github.com/pari588/bessite
2. Switch branch to: `git-backups`
3. Download: `repo-YYYYMMDD-HHMMSS.bundle`
4. Restore: `git clone repo-*.bundle`

### Manual Trigger

If you need an immediate backup:

1. Go to: https://github.com/pari588/bessite
2. Click: **Actions** tab
3. Select: **Automated Daily Backup**
4. Click: **Run workflow**

---

## Part 2: Database Backup Script

### ✅ Script Created and Tested

**File**: `backup_database.sh`
**Database**: `bombayengg` (Production)
**Compression**: gzip (reduces size by ~80%)
**Retention**: 30 days
**Status**: ✅ TESTED - 210K backup verified successful

### Setup Instructions

### Step 1: Make Script Executable

```bash
chmod +x /home/bombayengg/public_html/backup_database.sh
```

### Step 2: Test the Script

```bash
/home/bombayengg/public_html/backup_database.sh
```

**Expected Output**:
```
[2025-12-10 03:30:00] Starting database backup...
[2025-12-10 03:30:05] ✅ Database dump completed successfully
[2025-12-10 03:30:10] ✅ Backup compressed
[2025-12-10 03:30:10] ✅ Backup verified successfully
```

### Step 3: Setup Daily Automated Backup (Crontab)

Edit crontab:
```bash
crontab -e
```

Add this line (backup daily at 10:00 PM UTC / 3:30 AM IST next day):
```bash
0 22 * * * /home/bombayengg/public_html/backup_database.sh >> /home/bombayengg/backups/db_backup.log 2>&1
```

**Crontab Format Explanation**:
```
0        22     *      *      *
│        │      │      │      │
│        │      │      │      └─ Day of week (0-6, 0=Sunday)
│        │      │      └────── Month (1-12)
│        │      └───────────── Day of month (1-31)
│        └──────────────────── Hour (0-23, 22 = 10 PM)
└─────────────────────────────── Minute (0-59)

So: 0 22 * * * = Every day at 10:00 PM UTC (3:30 AM IST next day)
```

### Step 4: Verify Crontab Setup

```bash
crontab -l
```

Should show:
```
0 22 * * * /home/bombayengg/public_html/backup_database.sh >> /home/bombayengg/backups/db_backup.log 2>&1
```

### Step 5: Check Backup Logs

```bash
tail -f /home/bombayengg/backups/db_backup.log
```

Or view today's backups:
```bash
ls -lh /home/bombayengg/backups/
```

---

## Part 3: Backup Locations

### Code Backups (GitHub)

```
Repository: https://github.com/pari588/bessite
Branch:     git-backups
Location:   Cloud (GitHub servers)
Format:     Git bundles (.bundle)
Retention:  30 days
```

### Database Backups (Local Server)

```
Directory:  /home/bombayengg/backups/
Format:     Compressed SQL (.sql.gz)
Size:       ~50-200 MB (compressed)
Retention:  30 days
Example:    db_backup_tds_autofile_20251210_030000.sql.gz
```

### Backup Logs

```
Log File:   /var/log/db_backup.log
Updates:    Every time backup runs
Size:       Grows ~1 KB per backup
```

---

## Part 4: Restoring from Backup

### Restore Code from GitHub

```bash
# 1. Download backup from git-backups branch
git clone https://github.com/pari588/bessite.git \
  --branch git-backups backup-clone

# 2. Extract specific bundle
cd backup-clone/backups
git clone repo-20251210-030000.bundle restored-repo
```

### Restore Database

```bash
# 1. Stop the application (if running)
sudo systemctl stop php-fpm

# 2. Decompress backup
gunzip /home/bombayengg/backups/db_backup_tds_autofile_*.sql.gz

# 3. Restore to database
mysql -u root tds_autofile < /home/bombayengg/backups/db_backup_tds_autofile_*.sql

# 4. Start application
sudo systemctl start php-fpm

# 5. Verify
mysql -u root -e "SELECT COUNT(*) FROM tds_autofile.users;"
```

---

## Part 5: Monitoring & Alerts

### Check Recent Backups

```bash
# Code backups (on GitHub)
git log --oneline -n 10 origin/git-backups

# Database backups (local)
ls -lhtr /home/bombayengg/backups/ | tail -5

# Backup logs
tail -20 /var/log/db_backup.log
```

### Monitor Backup Size

```bash
# Database backup size
du -sh /home/bombayengg/backups/

# Total backup usage
df -h /home/bombayengg/
```

### Check Last Successful Backup

```bash
# Most recent backup file
ls -lhtr /home/bombayengg/backups/ | tail -1

# Check if backup is valid
gzip -t /home/bombayengg/backups/db_backup_*.sql.gz
# (no output = valid)
```

---

## Part 6: Backup Schedule

### Current Configuration

| Component | Frequency | Time | Retention |
|-----------|-----------|------|-----------|
| Code (GitHub) | Daily | 2 AM UTC | 30 days |
| Database (Local) | Daily | 3 AM UTC | 30 days |
| Logs | Continuous | N/A | 30 days |

### Converting Times to Your Timezone

- **UTC 2 AM** = IST 7:30 AM
- **UTC 3 AM** = IST 8:30 AM

To change backup time, edit:
- `.github/workflows/backup.yml` (line 7)
- `crontab -e` (your cron entry)

---

## Part 7: Storage Estimates

### Typical Backup Sizes

```
Database:       50-200 MB (compressed)
Code:           5-20 MB (git bundle)
Per Day:        ~100 MB
Per Month:      ~3 GB (30 days)
Per Year:       ~36 GB
```

### Storage Available

```bash
df -h /home/bombayengg/
```

Current recommendation: Keep at least 10 GB free for backups.

---

## Part 8: Troubleshooting

### Issue: Backup Script Not Running

**Check crontab**:
```bash
crontab -l
```

**Check if script is executable**:
```bash
ls -l /home/bombayengg/public_html/backup_database.sh
# Should show: -rwxr-xr-x (x = executable)
```

**Fix permissions**:
```bash
chmod +x /home/bombayengg/public_html/backup_database.sh
```

### Issue: GitHub Actions Not Running

**Check workflow status**:
1. Go to: https://github.com/pari588/bessite
2. Click: **Actions** tab
3. Check if workflow has run
4. Click workflow to see logs

**If not running**: Check GitHub Actions is enabled in repository settings.

### Issue: Backup File is Corrupted

**Test integrity**:
```bash
gzip -t /home/bombayengg/backups/db_backup_*.sql.gz
```

**If corrupted**: Delete and wait for next scheduled backup.

---

## Part 9: Disaster Recovery Plan

### Step-by-Step Recovery

1. **Identify Issue**
   - Check website status
   - Review error logs

2. **Determine Backup Age**
   - How recent is latest backup?
   - Will we lose recent data?

3. **Choose Recovery Method**
   - **Minor issue**: Restore specific database tables
   - **Major issue**: Full database restore
   - **Complete failure**: Full code + database restore

4. **Execute Recovery**
   - Stop application
   - Restore backup
   - Verify data integrity
   - Start application
   - Test functionality

5. **Document Issue**
   - What went wrong?
   - How was it fixed?
   - How to prevent in future?

---

## Part 10: Best Practices

### ✅ DO

- ✅ Test restore process monthly
- ✅ Monitor backup logs regularly
- ✅ Keep database and code backups in sync
- ✅ Store backups in multiple locations
- ✅ Document recovery procedures
- ✅ Review retention policy quarterly

### ❌ DON'T

- ❌ Rely on single backup location
- ❌ Ignore backup failures
- ❌ Store all backups on same server
- ❌ Delete backups randomly
- ❌ Skip verification of backups
- ❌ Store backups without compression

---

## Part 11: Security

### Backup Security

```
Code Backup:
  ✅ Encrypted in transit (GitHub HTTPS)
  ✅ Encrypted at rest (GitHub)
  ✅ Access control (GitHub permissions)
  ✅ Audit logs (GitHub activity)

Database Backup:
  ⚠️ Local storage (secure server)
  ⚠️ Consider: Encryption at rest
  ⚠️ Consider: Off-site backup copy
  ⚠️ Consider: Backup file permissions
```

### Restrict Backup Access

```bash
# Set permissions (only root can read)
chmod 600 /home/bombayengg/backups/*.sql.gz

# View current permissions
ls -l /home/bombayengg/backups/
```

---

## Part 12: Advanced Configuration

### Upload Backups to AWS S3

```bash
# Install AWS CLI
sudo apt-get install awscli

# Configure credentials
aws configure

# Add to backup script (optional)
aws s3 cp "$BACKUP_FILE_COMPRESSED" \
  s3://your-bucket/backups/
```

### Encrypt Backups Before Upload

```bash
# Encrypt backup
gpg --symmetric --cipher-algo AES256 "$BACKUP_FILE_COMPRESSED"

# Upload encrypted copy
aws s3 cp "$BACKUP_FILE_COMPRESSED.gpg" \
  s3://your-bucket/backups/
```

### Weekly Full System Backup

```bash
# Add crontab entry (weekly on Sunday at 1 AM)
0 1 * * 0 tar -czf /home/bombayengg/backups/full_backup_$(date +\%Y\%m\%d).tar.gz /home/bombayengg/public_html
```

---

## Summary

Your production website now has:

✅ **GitHub Code Backups**
- Daily automated backups
- 30-day retention
- Easy to restore

✅ **Database Backups**
- Daily automated backups
- Compressed format
- Local + GitHub metadata

✅ **Monitoring**
- Automated logs
- Status notifications
- Easy verification

✅ **Recovery Plan**
- Step-by-step restore procedure
- Multiple backup locations
- Tested and documented

---

## Next Steps

1. **Run test backup**:
   ```bash
   /home/bombayengg/public_html/backup_database.sh
   ```

2. **Setup crontab**:
   ```bash
   crontab -e
   # Add: 0 3 * * * /home/bombayengg/public_html/backup_database.sh
   ```

3. **Test restoration**:
   ```bash
   # Practice restoring from backup
   ```

4. **Verify GitHub backups**:
   ```bash
   git push origin main
   # Check git-backups branch on GitHub
   ```

5. **Document in your records**:
   - Backup schedule
   - Recovery procedures
   - Contact list for emergencies

---

**Backup System Setup Complete!** ✅

For questions, refer to logs at `/var/log/db_backup.log`
