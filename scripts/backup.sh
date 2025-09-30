#!/bin/bash

###############################################################################
# WP-POS Backup Script
# Creates backups of database and important files
###############################################################################

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/var/backups/wp-pos}"
APP_DIR="${APP_DIR:-/var/www/wp-pos}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="$BACKUP_DIR/$TIMESTAMP"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

# Create backup directory
mkdir -p "$BACKUP_PATH"

log_info "Starting backup process..."
log_info "Backup location: $BACKUP_PATH"

# Load environment variables
if [ -f "$APP_DIR/.env" ]; then
    source "$APP_DIR/.env"
else
    log_error ".env file not found!"
    exit 1
fi

# Backup database
log_info "Backing up database..."
mysqldump \
    --user="$DB_USERNAME" \
    --password="$DB_PASSWORD" \
    --host="$DB_HOST" \
    --port="${DB_PORT:-3306}" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    "$DB_DATABASE" | gzip > "$BACKUP_PATH/database.sql.gz"

if [ $? -eq 0 ]; then
    log_info "Database backup completed: database.sql.gz"
else
    log_error "Database backup failed!"
    exit 1
fi

# Backup storage directory
log_info "Backing up storage directory..."
tar -czf "$BACKUP_PATH/storage.tar.gz" -C "$APP_DIR" storage
log_info "Storage backup completed: storage.tar.gz"

# Backup public uploads
log_info "Backing up public uploads..."
if [ -d "$APP_DIR/public/uploads" ]; then
    tar -czf "$BACKUP_PATH/uploads.tar.gz" -C "$APP_DIR/public" uploads
    log_info "Uploads backup completed: uploads.tar.gz"
else
    log_warn "No uploads directory found, skipping..."
fi

# Backup .env file
log_info "Backing up .env file..."
cp "$APP_DIR/.env" "$BACKUP_PATH/.env"
log_info ".env backup completed"

# Create backup manifest
log_info "Creating backup manifest..."
cat > "$BACKUP_PATH/manifest.txt" << EOF
WP-POS Backup Manifest
=====================
Backup Date: $(date)
Timestamp: $TIMESTAMP
Application Version: $(cd "$APP_DIR" && git describe --tags --always 2>/dev/null || echo "unknown")
Database: $DB_DATABASE
Host: $DB_HOST

Files Included:
- database.sql.gz (Database dump)
- storage.tar.gz (Storage directory)
- uploads.tar.gz (Public uploads)
- .env (Environment configuration)

Backup Size:
$(du -sh "$BACKUP_PATH" | cut -f1)
EOF

log_info "Manifest created: manifest.txt"

# Calculate backup size
BACKUP_SIZE=$(du -sh "$BACKUP_PATH" | cut -f1)
log_info "Total backup size: $BACKUP_SIZE"

# Clean old backups
log_info "Cleaning old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -maxdepth 1 -type d -mtime +$RETENTION_DAYS -exec rm -rf {} \;
log_info "Old backups cleaned"

# Create latest symlink
log_info "Creating 'latest' symlink..."
ln -sfn "$BACKUP_PATH" "$BACKUP_DIR/latest"

# Verify backup integrity
log_info "Verifying backup integrity..."
if gzip -t "$BACKUP_PATH/database.sql.gz" 2>/dev/null; then
    log_info "Database backup integrity verified"
else
    log_error "Database backup is corrupted!"
    exit 1
fi

if tar -tzf "$BACKUP_PATH/storage.tar.gz" >/dev/null 2>&1; then
    log_info "Storage backup integrity verified"
else
    log_error "Storage backup is corrupted!"
    exit 1
fi

log_info "Backup completed successfully!"
log_info "Backup location: $BACKUP_PATH"
log_info "Total size: $BACKUP_SIZE"

# Optional: Upload to remote storage (S3, etc.)
if [ -n "$S3_BUCKET" ]; then
    log_info "Uploading to S3..."
    aws s3 sync "$BACKUP_PATH" "s3://$S3_BUCKET/backups/$TIMESTAMP/" --quiet
    log_info "Backup uploaded to S3"
fi

# Send notification (optional)
if [ -n "$NOTIFICATION_EMAIL" ]; then
    echo "Backup completed successfully at $BACKUP_PATH" | \
        mail -s "WP-POS Backup Success - $TIMESTAMP" "$NOTIFICATION_EMAIL"
fi

exit 0