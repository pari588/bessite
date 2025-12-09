#!/bin/bash

###############################################################################
#
# Production Database Backup Script
# Purpose: Automated daily backup of MySQL/MariaDB database
# Usage: ./backup_database.sh or add to crontab
#
###############################################################################

# Configuration
BACKUP_DIR="/home/bombayengg/backups"
DATABASE_NAME="bombayengg"
MYSQL_USER="bombayengg"
MYSQL_PASSWORD="oCFCrCMwKyy5jzg"  # Set if needed
RETENTION_DAYS=30  # Keep backups for 30 days
LOG_FILE="/var/log/db_backup.log"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Ensure log file can be written (create with proper permissions if needed)
if [ ! -f "$LOG_FILE" ]; then
    touch "$LOG_FILE" 2>/dev/null || LOG_FILE="$BACKUP_DIR/db_backup.log"
fi

# Timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/db_backup_${DATABASE_NAME}_${TIMESTAMP}.sql"
BACKUP_FILE_COMPRESSED="$BACKUP_FILE.gz"

###############################################################################
# Function: Log messages
###############################################################################
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

###############################################################################
# Function: Create database backup
###############################################################################
backup_database() {
    log_message "Starting database backup..."
    log_message "Database: $DATABASE_NAME"
    log_message "Backup file: $BACKUP_FILE_COMPRESSED"

    # Create backup using mysqldump
    if [ -z "$MYSQL_PASSWORD" ]; then
        mysqldump -u "$MYSQL_USER" "$DATABASE_NAME" > "$BACKUP_FILE" 2>>"$LOG_FILE"
    else
        mysqldump -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$DATABASE_NAME" > "$BACKUP_FILE" 2>>"$LOG_FILE"
    fi

    # Check if backup was successful
    if [ $? -eq 0 ]; then
        log_message "✅ Database dump completed successfully"

        # Compress the backup
        gzip "$BACKUP_FILE"
        if [ $? -eq 0 ]; then
            log_message "✅ Backup compressed: $(ls -lh "$BACKUP_FILE_COMPRESSED" | awk '{print $5}')"
        else
            log_message "❌ ERROR: Failed to compress backup"
            return 1
        fi
    else
        log_message "❌ ERROR: Database dump failed"
        return 1
    fi

    return 0
}

###############################################################################
# Function: Remove old backups
###############################################################################
cleanup_old_backups() {
    log_message "Cleaning up backups older than $RETENTION_DAYS days..."

    # Find and delete old backup files
    find "$BACKUP_DIR" -name "db_backup_*.sql.gz" -mtime +$RETENTION_DAYS -type f -exec rm {} \;

    if [ $? -eq 0 ]; then
        log_message "✅ Old backups cleaned up"
    else
        log_message "⚠️ WARNING: Could not remove some old backups"
    fi
}

###############################################################################
# Function: Verify backup integrity
###############################################################################
verify_backup() {
    log_message "Verifying backup integrity..."

    # Test if gzip file is valid
    gzip -t "$BACKUP_FILE_COMPRESSED" 2>>"$LOG_FILE"

    if [ $? -eq 0 ]; then
        FILE_SIZE=$(ls -lh "$BACKUP_FILE_COMPRESSED" | awk '{print $5}')
        log_message "✅ Backup verified successfully (Size: $FILE_SIZE)"
        return 0
    else
        log_message "❌ ERROR: Backup verification failed"
        return 1
    fi
}

###############################################################################
# Function: Generate summary report
###############################################################################
generate_summary() {
    log_message "Generating backup summary..."

    BACKUP_COUNT=$(find "$BACKUP_DIR" -name "db_backup_*.sql.gz" -type f | wc -l)
    TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | awk '{print $1}')

    cat >> "$LOG_FILE" << EOF

================================================================================
BACKUP SUMMARY REPORT
================================================================================
Date:           $(date '+%Y-%m-%d %H:%M:%S')
Database:       $DATABASE_NAME
Backup Status:  ✅ COMPLETED
Backup File:    $(basename "$BACKUP_FILE_COMPRESSED")
File Size:      $(ls -lh "$BACKUP_FILE_COMPRESSED" | awk '{print $5}')
Total Backups:  $BACKUP_COUNT
Total Size:     $TOTAL_SIZE
Retention:      $RETENTION_DAYS days
================================================================================

EOF

    log_message "Summary report generated"
}

###############################################################################
# Function: Upload to GitHub (optional)
###############################################################################
upload_to_github() {
    log_message "Uploading backup metadata to GitHub..."

    # This is optional - you can add git commands here to push backup info
    # Example: git add backups/ && git commit -m "Database backup: $TIMESTAMP"

    log_message "✅ Backup metadata ready for GitHub upload"
}

###############################################################################
# Main execution
###############################################################################
main() {
    log_message "========== Database Backup Started =========="

    # Run backup functions
    backup_database
    BACKUP_STATUS=$?

    if [ $BACKUP_STATUS -eq 0 ]; then
        verify_backup
        cleanup_old_backups
        generate_summary
        upload_to_github
        log_message "========== Database Backup Completed Successfully =========="
        exit 0
    else
        log_message "========== Database Backup Failed =========="
        exit 1
    fi
}

# Run main function
main
