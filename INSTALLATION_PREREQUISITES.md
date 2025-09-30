# Installation Prerequisites for Laravel POS System

## Required Software Installation

Before implementing the POS system, you need to install the following software on your Ubuntu/Linux system:

### 1. Install PHP 8.1+

```bash
# Update package list
sudo apt update

# Install PHP and required extensions
sudo apt install -y php8.1-cli php8.1-common php8.1-mysql php8.1-xml php8.1-curl php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-gd php8.1-intl

# Verify installation
php -v
```

### 2. Install Composer (PHP Package Manager)

```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php

# Move to global location
sudo mv composer.phar /usr/local/bin/composer

# Verify installation
composer -V
```

### 3. Install MySQL/MariaDB

```bash
# Install MySQL Server
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Verify installation
mysql --version
```

### 4. Install Git (if not already installed)

```bash
# Install Git
sudo apt install -y git

# Verify installation
git --version
```

### 5. Install Node.js and NPM (Optional, for frontend assets)

```bash
# Install Node.js and NPM
sudo apt install -y nodejs npm

# Verify installation
node -v
npm -v
```

## Quick Installation Script

You can run this script to install all prerequisites at once:

```bash
#!/bin/bash

echo "Installing Laravel POS Prerequisites..."

# Update system
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.1-cli php8.1-common php8.1-mysql php8.1-xml php8.1-curl php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-gd php8.1-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL
sudo apt install -y mysql-server

# Install Git
sudo apt install -y git

# Install Node.js and NPM
sudo apt install -y nodejs npm

echo "Installation complete! Verifying..."
php -v
composer -V
mysql --version
git --version
node -v
npm -v

echo "All prerequisites installed successfully!"
```

## After Installation

Once all prerequisites are installed, you can proceed with:

1. Creating the Laravel project
2. Configuring the database
3. Installing project dependencies
4. Running migrations
5. Starting development

## Next Steps

After installing the prerequisites, run:

```bash
# Navigate to project directory
cd /home/mercury/Documents/Projects/WP-POS

# The implementation will continue automatically
```

## Troubleshooting

### PHP not found
```bash
sudo apt install php-cli
```

### Composer permission issues
```bash
sudo chown -R $USER:$USER ~/.composer
```

### MySQL connection issues
```bash
sudo systemctl start mysql
sudo systemctl enable mysql
```

### Port 8000 already in use
```bash
php artisan serve --port=8001
```

## System Requirements Summary

- **PHP**: 8.1 or higher
- **Composer**: Latest version
- **MySQL**: 5.7 or higher (or MariaDB 10.3+)
- **Git**: Any recent version
- **Disk Space**: At least 500MB free
- **RAM**: Minimum 2GB recommended

## Contact & Support

If you encounter any issues during installation, refer to:
- Laravel Documentation: https://laravel.com/docs/10.x
- PHP Installation Guide: https://www.php.net/manual/en/install.php
- Composer Documentation: https://getcomposer.org/doc/