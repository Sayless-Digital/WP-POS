#!/bin/bash

###############################################################################
# WP-POS Deployment Script
# This script handles deployment to production servers
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/wp-pos}"
BACKUP_PATH="${BACKUP_PATH:-/var/backups/wp-pos}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_requirements() {
    log_info "Checking requirements..."
    
    command -v $PHP_BIN >/dev/null 2>&1 || { log_error "PHP is required but not installed."; exit 1; }
    command -v $COMPOSER_BIN >/dev/null 2>&1 || { log_error "Composer is required but not installed."; exit 1; }
    command -v $NPM_BIN >/dev/null 2>&1 || { log_error "NPM is required but not installed."; exit 1; }
    command -v git >/dev/null 2>&1 || { log_error "Git is required but not installed."; exit 1; }
    
    log_info "All requirements met."
}

create_backup() {
    log_info "Creating backup..."
    
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_DIR="$BACKUP_PATH/$TIMESTAMP"
    
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    if [ -f "$DEPLOY_PATH/.env" ]; then
        source "$DEPLOY_PATH/.env"
        mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/database.sql"
        log_info "Database backed up to $BACKUP_DIR/database.sql"
    fi
    
    # Backup files
    tar -czf "$BACKUP_DIR/files.tar.gz" -C "$DEPLOY_PATH" \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='.git' \
        storage public
    
    log_info "Files backed up to $BACKUP_DIR/files.tar.gz"
    
    # Keep only last 5 backups
    cd "$BACKUP_PATH"
    ls -t | tail -n +6 | xargs -r rm -rf
    
    echo "$BACKUP_DIR" > "$DEPLOY_PATH/.last_backup"
}

enable_maintenance_mode() {
    log_info "Enabling maintenance mode..."
    cd "$DEPLOY_PATH"
    $PHP_BIN artisan down --retry=60
}

disable_maintenance_mode() {
    log_info "Disabling maintenance mode..."
    cd "$DEPLOY_PATH"
    $PHP_BIN artisan up
}

pull_latest_code() {
    log_info "Pulling latest code..."
    cd "$DEPLOY_PATH"
    git fetch origin
    git reset --hard origin/main
    log_info "Code updated successfully."
}

install_dependencies() {
    log_info "Installing Composer dependencies..."
    cd "$DEPLOY_PATH"
    $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction
    
    log_info "Installing NPM dependencies..."
    $NPM_BIN ci
    
    log_info "Building assets..."
    $NPM_BIN run build
}

run_migrations() {
    log_info "Running database migrations..."
    cd "$DEPLOY_PATH"
    $PHP_BIN artisan migrate --force
}

optimize_application() {
    log_info "Optimizing application..."
    cd "$DEPLOY_PATH"
    
    # Clear all caches
    $PHP_BIN artisan cache:clear
    $PHP_BIN artisan config:clear
    $PHP_BIN artisan route:clear
    $PHP_BIN artisan view:clear
    
    # Cache configuration
    $PHP_BIN artisan config:cache
    $PHP_BIN artisan route:cache
    $PHP_BIN artisan view:cache
    
    # Optimize autoloader
    $COMPOSER_BIN dump-autoload --optimize
    
    # Optimize application
    $PHP_BIN artisan optimize
    
    log_info "Application optimized."
}

restart_services() {
    log_info "Restarting services..."
    cd "$DEPLOY_PATH"
    
    # Restart queue workers
    $PHP_BIN artisan queue:restart
    
    # Restart PHP-FPM (if available)
    if command -v systemctl >/dev/null 2>&1; then
        sudo systemctl restart php8.2-fpm 2>/dev/null || true
    fi
    
    log_info "Services restarted."
}

set_permissions() {
    log_info "Setting permissions..."
    cd "$DEPLOY_PATH"
    
    # Set ownership
    sudo chown -R www-data:www-data storage bootstrap/cache
    
    # Set permissions
    chmod -R 755 storage bootstrap/cache
    
    log_info "Permissions set."
}

verify_deployment() {
    log_info "Verifying deployment..."
    cd "$DEPLOY_PATH"
    
    # Check if application is responding
    if curl -f -s -o /dev/null "http://localhost"; then
        log_info "Application is responding."
    else
        log_error "Application is not responding!"
        return 1
    fi
    
    # Run health checks
    $PHP_BIN artisan health:check || log_warn "Health checks failed"
    
    log_info "Deployment verified."
}

rollback() {
    log_error "Deployment failed! Rolling back..."
    
    if [ -f "$DEPLOY_PATH/.last_backup" ]; then
        BACKUP_DIR=$(cat "$DEPLOY_PATH/.last_backup")
        
        if [ -d "$BACKUP_DIR" ]; then
            # Restore database
            if [ -f "$BACKUP_DIR/database.sql" ]; then
                source "$DEPLOY_PATH/.env"
                mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$BACKUP_DIR/database.sql"
                log_info "Database restored."
            fi
            
            # Restore files
            if [ -f "$BACKUP_DIR/files.tar.gz" ]; then
                tar -xzf "$BACKUP_DIR/files.tar.gz" -C "$DEPLOY_PATH"
                log_info "Files restored."
            fi
            
            # Revert git
            cd "$DEPLOY_PATH"
            git reset --hard HEAD~1
            
            log_info "Rollback completed."
        else
            log_error "Backup directory not found!"
        fi
    else
        log_error "No backup information found!"
    fi
    
    disable_maintenance_mode
    exit 1
}

# Main deployment process
main() {
    log_info "Starting deployment..."
    
    # Set trap for errors
    trap rollback ERR
    
    check_requirements
    create_backup
    enable_maintenance_mode
    pull_latest_code
    install_dependencies
    run_migrations
    optimize_application
    set_permissions
    restart_services
    disable_maintenance_mode
    verify_deployment
    
    log_info "Deployment completed successfully!"
}

# Run main function
main "$@"