# Laravel POS System - Setup Instructions

## Current Status

The Laravel POS system architecture and documentation have been created, but the actual Laravel application needs to be installed first. Your system is missing the required prerequisites.

## Prerequisites Not Installed

The following software is required but not currently installed on your system:
- ✗ PHP 8.1+
- ✗ Composer (PHP package manager)
- ✗ MySQL/MariaDB

## Installation Options

### Option 1: Automated Installation (Recommended)

Run the provided installation script:

```bash
# Make sure you're in the project directory
cd /home/mercury/Documents/Projects/WP-POS

# Run the installation script with sudo
sudo bash install-prerequisites.sh
```

This script will automatically install:
- PHP 8.1 with all required extensions
- Composer
- MySQL Server
- Git
- Node.js and NPM

### Option 2: Manual Installation

If you prefer to install manually, follow these steps:

#### 1. Install PHP 8.1+
```bash
sudo apt update
sudo apt install -y php8.1-cli php8.1-common php8.1-mysql php8.1-xml php8.1-curl php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-gd php8.1-intl php8.1-dom
```

#### 2. Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

#### 3. Install MySQL
```bash
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql
```

#### 4. Secure MySQL Installation
```bash
sudo mysql_secure_installation
```

## After Prerequisites Installation

Once all prerequisites are installed, follow these steps:

### Step 1: Create Laravel Project

```bash
# Navigate to project directory
cd /home/mercury/Documents/Projects/WP-POS

# Move documentation files to a temp directory
mkdir -p ../WP-POS-docs
mv *.md ../WP-POS-docs/
mv install-prerequisites.sh ../WP-POS-docs/

# Go back one level
cd ..

# Remove the empty directory
rm -rf WP-POS

# Create fresh Laravel project
composer create-project laravel/laravel WP-POS

# Move back into project
cd WP-POS

# Restore documentation
mv ../WP-POS-docs/* .
rm -rf ../WP-POS-docs
```

### Step 2: Configure Database

```bash
# Create database
sudo mysql -e "CREATE DATABASE pos_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create database user
sudo mysql -e "CREATE USER 'pos_user'@'localhost' IDENTIFIED BY 'SecurePassword123!';"
sudo mysql -e "GRANT ALL PRIVILEGES ON pos_system.* TO 'pos_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### Step 3: Configure Environment

Edit `.env` file:
```env
APP_NAME="POS System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_system
DB_USERNAME=pos_user
DB_PASSWORD=SecurePassword123!
```

### Step 4: Install Dependencies

```bash
# Install Livewire
composer require livewire/livewire

# Install WooCommerce SDK
composer require automattic/woocommerce

# Install PDF generator
composer require barryvdh/laravel-dompdf

# Install permissions package
composer require spatie/laravel-permission
```

### Step 5: Install Authentication

```bash
# Install Laravel Breeze
composer require laravel/breeze --dev

# Install Breeze with Livewire stack
php artisan breeze:install livewire

# Run migrations
php artisan migrate
```

### Step 6: Verify Installation

```bash
# Start development server
php artisan serve

# Visit http://localhost:8000 in your browser
```

## Implementation Phases

After successful installation, the implementation will proceed in these phases:

1. ✓ **Documentation Created** - Architecture and planning documents
2. ⏳ **Prerequisites Installation** - PHP, Composer, MySQL (CURRENT STEP)
3. ⏳ **Laravel Installation** - Create base Laravel project
4. ⏳ **Database Schema** - Create migrations and models
5. ⏳ **Authentication** - User roles and permissions
6. ⏳ **Core Features** - POS terminal, products, inventory
7. ⏳ **WooCommerce Integration** - Sync with online store
8. ⏳ **Offline Mode** - PWA and local storage
9. ⏳ **Reporting** - Sales and inventory reports
10. ⏳ **Testing & Deployment** - Final testing and Hostinger deployment

## Quick Start Command Summary

```bash
# 1. Install prerequisites
sudo bash install-prerequisites.sh

# 2. Setup Laravel (after prerequisites)
cd /home/mercury/Documents/Projects
mkdir WP-POS-temp
cd WP-POS-temp
composer create-project laravel/laravel .
cd ..
rm -rf WP-POS
mv WP-POS-temp WP-POS
cd WP-POS

# 3. Install dependencies
composer require livewire/livewire automattic/woocommerce barryvdh/laravel-dompdf spatie/laravel-permission
composer require laravel/breeze --dev
php artisan breeze:install livewire

# 4. Configure and run
php artisan migrate
php artisan serve
```

## Troubleshooting

### "Composer not found"
```bash
# Reinstall Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### "MySQL connection refused"
```bash
# Start MySQL service
sudo systemctl start mysql
sudo systemctl status mysql
```

### "Permission denied"
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

### "Port 8000 already in use"
```bash
# Use different port
php artisan serve --port=8001
```

## Need Help?

- Check [`INSTALLATION_PREREQUISITES.md`](INSTALLATION_PREREQUISITES.md) for detailed installation guide
- Review [`Quick_Start_Guide.md`](Quick_Start_Guide.md) for step-by-step implementation
- Consult [`POS_Development_Plan.md`](POS_Development_Plan.md) for architecture details

## Next Steps

1. **Run the installation script**: `sudo bash install-prerequisites.sh`
2. **Verify installations**: Check that PHP, Composer, and MySQL are working
3. **Create Laravel project**: Follow Step 1 above
4. **Continue with implementation**: The system will guide you through remaining phases

---

**Important**: Do not proceed with Laravel installation until all prerequisites are successfully installed and verified.