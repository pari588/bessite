# 🚀 START HERE - Deployment & Backup Workflow

**Welcome!** This is your quick-start guide to safely make changes to the Bombay Engineering Syndicate website.

---

## ⚡ Quick Start (30 seconds)

### For any code changes you want to make:

```bash
# 1. Navigate to website
cd /home/bombayengg/public_html

# 2. Run the automation script with your description
./backup_and_deploy.sh "Description of what you're changing"

# 3. That's it! The script handles:
#    ✅ File backup
#    ✅ Database backup
#    ✅ Git commit
#    ✅ GitHub push
#    ✅ Logging
```

### Example:
```bash
./backup_and_deploy.sh "Fix pump detail page layout"
```

---

## 📋 What Happens Automatically

When you run the script, it:

```
1. Creates backup of all files
   └─ Stored in: backups/website_backup_YYYYMMDD_HHMMSS.tar.gz

2. Creates database backup
   └─ Stored in: database_backups/bombayengg_YYYYMMDD_HHMMSS.sql

3. Commits your changes to git
   └─ With timestamp and description

4. Pushes to GitHub
   └─ Visible at: https://github.com/pari588/bessite

5. Records in deployment log
   └─ For audit trail: DEPLOYMENT_LOG.txt
```

---

## ✅ The Three Mandatory Rules

### Rule #1: Always Create Backups Before Changes
- File backup (entire website)
- Database backup (if needed)
- **Automated by script** ✅

### Rule #2: Always Commit to GitHub
- All changes must go to GitHub
- No exceptions
- **Automated by script** ✅

### Rule #3: Always Be Able to Restore
- Any version can be restored instantly
- Either via GitHub or file backups
- **Commands provided by script** ✅

---

## 🔄 Typical Workflow

### You make a change and want to deploy it:

```
┌─────────────────────────────────┐
│  Make your code changes         │
│  (edit files in xsite/, etc)    │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│  Run deployment script          │
│  ./backup_and_deploy.sh "desc"  │
└────────────┬────────────────────┘
             │
             ▼  (Automatic)
      ┌──────┴──────┐
      │             │
      ▼             ▼
┌──────────┐  ┌──────────────┐
│ Backups  │  │ Git Commit   │
│ Created  │  │ + GitHub     │
│          │  │ Push         │
└──────────┘  └──────────────┘
      │             │
      └─────┬───────┘
            ▼
     ✅ DONE & SAFE

Can restore anytime!
```

---

## 🆘 If Something Goes Wrong

### Problem: Changes broke the site

```bash
# The script will show you restore options
# For example, to undo last change:

git revert a1b2c3d
git push origin main

# Or restore from file backup:
cd /
tar -xzf /home/bombayengg/public_html/backups/website_backup_20251205_174500.tar.gz
```

### Problem: Database got corrupted

```bash
# Restore database from backup
mysql -u bombayengg -p'oCFCrCMwKyy5jzg' bombayengg < \
  database_backups/bombayengg_20251205_174500.sql
```

---

## 📚 Documentation Files

| File | Purpose | When to Read |
|------|---------|-------------|
| **DEPLOYMENT_AND_BACKUP_POLICY.md** | Mandatory workflow details | Before making ANY changes |
| **BACKUP_AND_DEPLOY_SCRIPT.md** | Script usage guide | To understand automation |
| **SITE_STRUCTURE_OVERVIEW.md** | System architecture | To understand the codebase |
| **QUICK_REFERENCE_GUIDE.md** | Quick lookup | For database/module info |

---

## 🎯 For Different Tasks

### Adding a new feature
```bash
./backup_and_deploy.sh "Add new pump inquiry module"
```

### Fixing a bug
```bash
./backup_and_deploy.sh "Fix email sending in inquiry form"
```

### Updating configuration
```bash
./backup_and_deploy.sh "Update Brevo API settings"
```

### Database changes
```bash
./backup_and_deploy.sh "Add new field to pump specifications"
```

### Multiple file changes
```bash
./backup_and_deploy.sh "Refactor pump listing CSS and optimize queries"
```

---

## 🔍 How to Verify Everything Worked

### Check GitHub
```bash
# See your commit on GitHub
https://github.com/pari588/bessite

# Look for your commit with the description you provided
```

### Check Local Backups
```bash
# View recent backups
ls -lth backups/ | head -5
ls -lth database_backups/ | head -5
```

### Check Deployment Log
```bash
# View all past deployments
tail -20 DEPLOYMENT_LOG.txt
```

### Check Git History
```bash
# See recent commits
git log --oneline -10
```

---

## ⚠️ Important Notes

### Before Making Changes
- ✅ You don't need to manually create backups (script does it)
- ✅ You don't need to manually commit to git (script does it)
- ✅ You don't need to remember backup filenames (script shows them)

### During Changes
- ✅ Follow existing code patterns
- ✅ Use prepared statements for database queries (security)
- ✅ Test changes on the live site after deployment

### After Deployment
- ✅ Monitor the site for errors
- ✅ Check browser console (F12) for JavaScript errors
- ✅ Check PHP error log if needed

---

## 🔐 Security

### Your backups are safe
- Stored locally on server
- Timestamped and organized
- Can be accessed anytime

### GitHub stores everything
- All code history
- Complete change tracking
- Easy to see what changed when

### Credentials are protected
- PAT never exposed in documentation
- Stored in `.git-credentials-info.txt`
- File is in `.gitignore` (never pushed to GitHub)

---

## 📞 Need Help?

### Common Questions

**Q: What if the script fails?**
A: Check the error message. Most common issues:
- Git not found: Install git
- GitHub unreachable: Check internet
- Database issue: Check MySQL is running

**Q: Can I use the script on anything?**
A: Yes! It works for any file changes to the website code.

**Q: How long does it take?**
A: Usually 10-30 seconds depending on website size.

**Q: Do I lose the backups?**
A: No! They stay in the backup folders forever. You control when to delete them.

**Q: What if I make a mistake?**
A: No problem! Just restore from backup. You have multiple restore options.

---

## 🚀 You're Ready!

**Next steps:**

1. Read: [DEPLOYMENT_AND_BACKUP_POLICY.md](DEPLOYMENT_AND_BACKUP_POLICY.md)
2. Understand: The workflow diagram in that file
3. Practice: Run the script once with a test change
4. Deploy: Make your actual changes using the script

---

## 📋 Checklist Before First Use

- [ ] You understand the three mandatory rules
- [ ] You know where backup files go
- [ ] You know how to restore if needed
- [ ] You've read DEPLOYMENT_AND_BACKUP_POLICY.md
- [ ] You've seen examples of restore commands
- [ ] You're ready to make changes safely!

---

## ✅ You Got This!

The policy and script are designed to make it **impossible** to lose code or data. You can:

✅ Make changes confidently
✅ Know everything is backed up
✅ Restore instantly if needed
✅ See complete history on GitHub

**Happy coding!** 🎉

---

**Script Location:** `/home/bombayengg/public_html/backup_and_deploy.sh`

**Usage:** `./backup_and_deploy.sh "Your description here"`

**Status:** ✅ ACTIVE and TESTED

