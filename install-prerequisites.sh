#!/bin/bash

# Laravel POS System - Prerequisites Installation Script
# This script installs all required software for the Laravel POS system

set -e  # Exit on error

echo "=========================================="
echo "Laravel POS System - Prerequisites Setup"
echo "=========================================="
echo ""

# Check if running with sudo
if [ "$EUID" -ne 0 ]; then 
    echo "Please run with sudo: sudo bash install-prerequisites.sh"
    exit 1
fi

# Get the actual user (not root)
ACTUAL_USER=${SUDO_USER:-$USER}
ACTUAL_HOME=$(eval echo ~$ACTUAL_USER)

echo "Installing for user: $ACTUAL_USER"
echo ""

# Update package list
echo "Step 1/6: Updating package list..."
apt update

# Install PHP and extensions
echo ""
echo "Step 2/6: Installing PHP 8.1 and extensions..."
apt install -y php8.1-cli php8.1-common php8.1-mysql php8.1-xml php8.1-curl \
    php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-gd php8.1-intl php8.1-dom

# Install Composer
echo ""
echo "Step 3/6: Installing Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    echo "Composer installed successfully"
else
    echo "Composer already installed"
fi

# Install MySQL
echo ""
echo "Step 4/6: Installing MySQL Server..."
apt install -y mysql-server
systemctl start mysql
systemctl enable mysql

# Install Git
echo ""
echo "Step 5/6: Installing Git..."
apt install -y git

# Install Node.js and NPM (optional but recommended)
echo ""
echo "Step 6/6: Installing Node.js and NPM..."
apt install -y nodejs npm

# Set proper permissions
echo ""
echo "Setting proper permissions..."
if [ -d "$ACTUAL_HOME/.composer" ]; then
    chown -R $ACTUAL_USER:$ACTUAL_USER $ACTUAL_HOME/.composer
fi

# Verify installations
echo ""
echo "=========================================="
echo "Verification"
echo "=========================================="
echo ""

echo "PHP Version:"
php -v | head -n 1
echo ""

echo "Composer Version:"
sudo -u $ACTUAL_USER composer -V
echo ""

echo "MySQL Version:"
mysql --version
echo ""

echo "Git Version:"
git --version
echo ""

echo "Node.js Version:"
node -v
echo ""

echo "NPM Version:"
npm -v
echo ""

echo "=========================================="
echo "Installation Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Configure MySQL root password (if not done):"
echo "   sudo mysql_secure_installation"
echo ""
echo "2. Create database for POS system:"
echo "   sudo mysql -e \"CREATE DATABASE pos_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
echo ""
echo "3. Create MySQL user:"
echo "   sudo mysql -e \"CREATE USER 'pos_user'@'localhost' IDENTIFIED BY 'your_password';\""
echo "   sudo mysql -e \"GRANT ALL PRIVILEGES ON pos_system.* TO 'pos_user'@'localhost';\""
echo "   sudo mysql -e \"FLUSH PRIVILEGES;\""
echo ""
echo "4. Return to your project directory and continue implementation:"
echo "   cd /home/mercury/Documents/Projects/WP-POS"
echo ""