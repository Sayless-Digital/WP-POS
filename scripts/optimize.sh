#!/bin/bash

###############################################################################
# WP-POS Production Optimization Script
# Optimizes the application for production performance
###############################################################################

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

# Clear all caches
log_info "Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Optimize configuration
log_info "Caching configuration..."
php artisan config:cache

# Optimize routes
log_info "Caching routes..."
php artisan route:cache

# Optimize views
log_info "Caching views..."
php artisan view:cache

# Optimize events
log_info "Caching events..."
php artisan event:cache

# Optimize autoloader
log_info "Optimizing Composer autoloader..."
composer dump-autoload --optimize --classmap-authoritative

# Optimize application
log_info "Running Laravel optimization..."
php artisan optimize

# Clear and optimize OPcache
log_info "Optimizing OPcache..."
php artisan opcache:clear 2>/dev/null || log_warn "OPcache not available"
php artisan opcache:compile 2>/dev/null || log_warn "OPcache compile not available"

# Optimize database
log_info "Optimizing database..."
php artisan db:optimize 2>/dev/null || log_warn "Database optimization not available"

# Generate sitemap (if applicable)
log_info "Generating sitemap..."
php artisan sitemap:generate 2>/dev/null || log_warn "Sitemap generation not available"

# Warm up cache
log_info "Warming up cache..."
php artisan cache:warmup 2>/dev/null || log_warn "Cache warmup not available"

# Set permissions
log_info "Setting optimal permissions..."
chmod -R 755 storage bootstrap/cache
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;

log_info "Optimization complete!"
log_info "Application is ready for production."