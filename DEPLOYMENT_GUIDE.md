# WP-POS Deployment Guide

Complete guide for deploying WP-POS to production environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Deployment Methods](#deployment-methods)
4. [Configuration](#configuration)
5. [Security](#security)
6. [Optimization](#optimization)
7. [Monitoring](#monitoring)
8. [Backup & Recovery](#backup--recovery)
9. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software

- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher (or MariaDB 10.3+)
- **Redis**: 6.0 or higher
- **Node.js**: 18.x or higher
- **Composer**: 2.x
- **Git**: Latest version

### PHP Extensions

```bash
php -m | grep -E 'pdo_mysql|mbstring|xml|ctype|json|bcmath|redis|zip|gd'
```

Required extensions:
- pdo_mysql
- mbstring
- xml
- ctype
- json
- bcmath
- redis
- zip
- gd

---

## Server Requirements

### Minimum Specifications

- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 20GB SSD
- **Bandwidth**: 100Mbps

### Recommended Specifications

- **CPU**: 4+ cores
- **RAM**: 8GB+
- **Storage**: 50GB+ SSD
- **Bandwidth**: 1Gbps

### Operating System

- Ubuntu 22.04 LTS (recommended)
- Debian 11+
- CentOS 8+
- RHEL 8+

---

## Deployment Methods

### Method 1: Docker Deployment (Recommended)

#### 1. Clone Repository

```bash
git clone https://github.com/yourusername/wp-pos.git
cd wp-pos
```

#### 2. Configure Environment

```bash
cp .env.example .env
nano .env
```

Update these critical variables:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_DATABASE=wp_pos
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password

WOOCOMMERCE_STORE_URL=https://your-store.com
WOOCOMMERCE_CONSUMER_KEY=ck_xxxxx
WOOCOMMERCE_CONSUMER_SECRET=cs_xxxxx
```

#### 3. Build and Start Containers

```bash
docker-compose up -d --build
```

#### 4. Initialize Application

```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan storage:link
```

#### 5. Optimize for Production

```bash
docker-compose exec app php artisan optimize
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Method 2: Traditional Server Deployment

#### 1. Prepare Server

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-zip php8.2-gd

# Install MySQL
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### 2. Clone and Setup Application

```bash
# Create directory
sudo mkdir -p /var/www/wp-pos
sudo chown $USER:$USER /var/www/wp-pos

# Clone repository
cd /var/www/wp-pos
git clone https://github.com/yourusername/wp-pos.git .

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Configure environment
cp .env.example .env
nano .env

# Generate key
php artisan key:generate

# Set permissions
sudo chown -R www-data:www-data /var/www/wp-pos
sudo chmod -R 755 /var/www/wp-pos/storage
sudo chmod -R 755 /var/www/wp-pos/bootstrap/cache
```

#### 3. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/wp-pos
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/wp-pos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/wp-pos /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 4. Setup Database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE wp_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wp_pos_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON wp_pos.* TO 'wp_pos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 5. Run Migrations

```bash
cd /var/www/wp-pos
php artisan migrate --force
php artisan db:seed
```

#### 6. Setup Queue Worker

```bash
sudo nano /etc/systemd/system/wp-pos-queue.service
```

```ini
[Unit]
Description=WP-POS Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/wp-pos
ExecStart=/usr/bin/php /var/www/wp-pos/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable wp-pos-queue
sudo systemctl start wp-pos-queue
```

#### 7. Setup Scheduler

```bash
crontab -e
```

Add:
```cron
* * * * * cd /var/www/wp-pos && php artisan schedule:run >> /dev/null 2>&1
```

### Method 3: Automated Deployment Script

```bash
# Make script executable
chmod +x scripts/deploy.sh

# Run deployment
./scripts/deploy.sh
```

---

## Configuration

### SSL/TLS Setup (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Environment Variables

Critical production settings:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Security
APP_KEY=base64:generated_key_here

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wp_pos
DB_USERNAME=wp_pos_user
DB_PASSWORD=secure_password

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls

# WooCommerce
WOOCOMMERCE_ENABLED=true
WOOCOMMERCE_STORE_URL=https://your-store.com
WOOCOMMERCE_CONSUMER_KEY=ck_xxxxx
WOOCOMMERCE_CONSUMER_SECRET=cs_xxxxx
```

---

## Security

### 1. Firewall Configuration

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. Secure MySQL

```bash
sudo mysql_secure_installation
```

### 3. Secure Redis

```bash
sudo nano /etc/redis/redis.conf
```

Set:
```conf
requirepass your_redis_password
bind 127.0.0.1
```

### 4. File Permissions

```bash
sudo chown -R www-data:www-data /var/www/wp-pos
sudo find /var/www/wp-pos -type f -exec chmod 644 {} \;
sudo find /var/www/wp-pos -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/wp-pos/storage
sudo chmod -R 775 /var/www/wp-pos/bootstrap/cache
```

### 5. Security Headers

Already configured in Nginx, but verify:
- X-Frame-Options
- X-Content-Type-Options
- X-XSS-Protection
- Referrer-Policy

---

## Optimization

### 1. PHP-FPM Tuning

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### 2. OPcache Configuration

```bash
sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 3. MySQL Optimization

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 200
```

### 4. Redis Optimization

```bash
sudo nano /etc/redis/redis.conf
```

```conf
maxmemory 1gb
maxmemory-policy allkeys-lru
```

### 5. Application Optimization

```bash
# Run optimization script
chmod +x scripts/optimize.sh
./scripts/optimize.sh
```

---

## Monitoring

### 1. Application Monitoring

Install Laravel Telescope (development only):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 2. Server Monitoring

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Monitor processes
htop

# Monitor disk I/O
iotop

# Monitor network
nethogs
```

### 3. Log Monitoring

```bash
# Application logs
tail -f storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

### 4. Performance Monitoring

```bash
# Install New Relic (optional)
# Follow: https://docs.newrelic.com/docs/apm/agents/php-agent/

# Or use Laravel Debugbar (development only)
composer require barryvdh/laravel-debugbar --dev
```

---

## Backup & Recovery

### Automated Backups

```bash
# Make backup script executable
chmod +x scripts/backup.sh

# Setup cron for daily backups
crontab -e
```

Add:
```cron
0 2 * * * /var/www/wp-pos/scripts/backup.sh >> /var/log/wp-pos-backup.log 2>&1
```

### Manual Backup

```bash
./scripts/backup.sh
```

### Restore from Backup

```bash
# Restore database
gunzip < /var/backups/wp-pos/TIMESTAMP/database.sql.gz | mysql -u wp_pos_user -p wp_pos

# Restore storage
tar -xzf /var/backups/wp-pos/TIMESTAMP/storage.tar.gz -C /var/www/wp-pos/

# Restore uploads
tar -xzf /var/backups/wp-pos/TIMESTAMP/uploads.tar.gz -C /var/www/wp-pos/public/

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

```bash
# Check logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### 2. Database Connection Failed

```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check MySQL status
sudo systemctl status mysql

# Verify credentials in .env
```

#### 3. Queue Not Processing

```bash
# Check queue worker status
sudo systemctl status wp-pos-queue

# Restart queue worker
sudo systemctl restart wp-pos-queue

# Check failed jobs
php artisan queue:failed
```

#### 4. Redis Connection Error

```bash
# Check Redis status
sudo systemctl status redis

# Test connection
redis-cli ping

# Restart Redis
sudo systemctl restart redis
```

#### 5. Permission Denied

```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/wp-pos
sudo chmod -R 755 storage bootstrap/cache
```

### Performance Issues

```bash
# Check server resources
htop
df -h
free -m

# Optimize application
./scripts/optimize.sh

# Clear all caches
php artisan optimize:clear

# Restart services
sudo systemctl restart nginx php8.2-fpm redis mysql
```

### Debug Mode (Use Carefully)

```bash
# Enable debug temporarily
php artisan down
nano .env  # Set APP_DEBUG=true
php artisan config:clear
php artisan up

# Remember to disable after debugging
nano .env  # Set APP_DEBUG=false
php artisan config:cache
```

---

## Maintenance

### Regular Tasks

**Daily:**
- Monitor logs
- Check disk space
- Verify backups

**Weekly:**
- Review performance metrics
- Update dependencies (if needed)
- Check security updates

**Monthly:**
- Full system backup
- Security audit
- Performance optimization
- Database optimization

### Update Procedure

```bash
# 1. Backup
./scripts/backup.sh

# 2. Enable maintenance mode
php artisan down

# 3. Pull latest code
git pull origin main

# 4. Update dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 5. Run migrations
php artisan migrate --force

# 6. Clear and cache
php artisan optimize:clear
php artisan optimize

# 7. Restart services
sudo systemctl restart wp-pos-queue

# 8. Disable maintenance mode
php artisan up
```

---

## Support

For issues and questions:
- **Documentation**: [README.md](README.md)
- **Issues**: GitHub Issues
- **Email**: support@yourcompany.com

---

## License

This deployment guide is part of the WP-POS project.