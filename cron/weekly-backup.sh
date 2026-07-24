#!/bin/bash
###############################################################################
# Weekly full backup for bombayengg.net
#
# Produces TWO things every run:
#   1. main branch  -> refreshes db-backups/bombayengg_latest.sql (code + SQL)
#   2. backup branch-> a COMPLETE snapshot of the entire live tree
#      (code + ALL uploads + config + SQL + everything on disk)
#
# SAFETY: the 'backup' branch is built with a throwaway git index via
# git write-tree / commit-tree — the live working tree is NEVER checked out
# to another branch, so live files can never be deleted or altered here.
#
# Requires: git credential stored in ~/.git-credentials (GitHub PAT) for push.
###############################################################################
set -uo pipefail

REPO="/home/bombayengg/public_html"
DB_USER="bombayengg"
DB_PASS="oCFCrCMwKyy5jzg"
DB_NAME="bombayengg"
STAMP="$(date -u +%Y-%m-%dT%H:%MZ)"

cd "$REPO" || { echo "cannot cd $REPO"; exit 1; }

log() { echo "[$(date -u +%FT%TZ)] $*"; }

# ---------------------------------------------------------------------------
# 1) Fresh DB dump (~6 MB) into the tracked db-backups/ dir
# ---------------------------------------------------------------------------
mkdir -p db-backups
if ! mysqldump -u"$DB_USER" -p"$DB_PASS" --single-transaction --quick \
        --routines --triggers --no-tablespaces "$DB_NAME" \
        > db-backups/bombayengg_latest.sql 2>/dev/null; then
    log "ERROR: mysqldump failed"; exit 1
fi
log "DB dumped ($(du -h db-backups/bombayengg_latest.sql | cut -f1))"

# ---------------------------------------------------------------------------
# 2) Refresh the SQL reference on main (code branch) — SQL only, no other files
# ---------------------------------------------------------------------------
git add db-backups/bombayengg_latest.sql
if ! git diff --cached --quiet -- db-backups/bombayengg_latest.sql 2>/dev/null; then
    git commit -q -m "chore: weekly DB dump refresh ($STAMP)" -- db-backups/bombayengg_latest.sql
    git push -q origin main && log "main: SQL refresh pushed" || log "WARN: main push failed (check creds)"
else
    log "main: SQL unchanged, no commit"
fi

# ---------------------------------------------------------------------------
# 3) FULL snapshot -> backup branch, built via throwaway index (no checkout)
# ---------------------------------------------------------------------------
TMPIDX="$(mktemp)"; rm -f "$TMPIDX"
export GIT_INDEX_FILE="$TMPIDX"
git read-tree --empty
# Add everything on disk EXCEPT: noise (logs, temp) AND files containing live
# secrets that GitHub secret-scanning rejects / that must never be published:
#   - config.inc.php          (DB creds + Brevo API key)
#   - core/wa-handlers.inc.php (hardcoded Brevo API key fallback — pending code fix)
# These stay on the live disk; back them up out-of-band (see notes). -f overrides
# .gitignore so uploads/, .htaccess etc. ARE still captured for restore.
git add -A -f -- . \
    ':(exclude)logs' ':(exclude)logs/**' \
    ':(exclude)uploads/temp' ':(exclude)uploads/temp/**' \
    ':(exclude)tmp' ':(exclude)tmp/**' \
    ':(exclude).git-credentials' \
    ':(exclude)config.inc.php' \
    ':(exclude)core/wa-handlers.inc.php'
TREE="$(git write-tree)"
unset GIT_INDEX_FILE
rm -f "$TMPIDX"

if git rev-parse --verify -q refs/remotes/origin/backup >/dev/null; then
    PARENT="-p origin/backup"
else
    PARENT=""
fi
COMMIT="$(printf 'Full weekly backup %s\n' "$STAMP" | git commit-tree "$TREE" $PARENT)"
git branch -f backup "$COMMIT"
if git push -q origin backup; then
    log "backup: full snapshot pushed -> ${COMMIT:0:10}"
else
    log "WARN: backup push failed (check creds)"
fi

log "weekly backup complete"
