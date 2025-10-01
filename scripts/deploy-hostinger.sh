#!/bin/bash

# WP-POS Hostinger Deployment Script
# This script prepares the application for Hostinger deployment

echo "🚀 Preparing WP-POS for Hostinger deployment..."

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Run this script from the Laravel root directory"
    exit 1
fi

# Create deployment package
echo "📦 Creating deployment package..."

# Copy files to deployment directory
DEPLOY_DIR="deploy-hostinger"
rm -rf $DEPLOY_DIR
mkdir $DEPLOY_DIR

# Copy all necessary files
cp -r app bootstrap config database public resources routes storage vendor artisan composer.json composer.lock .env.example $DEPLOY_DIR/

# Copy deployment files
cp .htaccess $DEPLOY_DIR/
cp DEPLOYMENT_HOSTINGER.md $DEPLOY_DIR/

# Copy installer
cp -r install $DEPLOY_DIR/

# Set proper permissions
echo "🔐 Setting file permissions..."
find $DEPLOY_DIR -type f -exec chmod 644 {} \;
find $DEPLOY_DIR -type d -exec chmod 755 {} \;
chmod -R 775 $DEPLOY_DIR/storage
chmod -R 775 $DEPLOY_DIR/bootstrap/cache
chmod 755 $DEPLOY_DIR/artisan

# Create deployment info
cat > $DEPLOY_DIR/DEPLOYMENT_INFO.txt << 'INFO'
WP-POS Deployment Package for Hostinger
========================================

Created: $(date)
Version: $(git describe --tags 2>/dev/null || echo "latest")

Files included:
- Laravel application files
- Installer (install/ directory)
- Root .htaccess for Hostinger compatibility
- Deployment guide (DEPLOYMENT_HOSTINGER.md)

Next steps:
1. Upload all files to your Hostinger hosting
2. Set document root to this directory (not public/)
3. Visit yourdomain.com/install
4. Follow the installation wizard

Support: Check DEPLOYMENT_HOSTINGER.md for detailed instructions
INFO

echo "✅ Deployment package created in: $DEPLOY_DIR/"
echo "📋 Next steps:"
echo "   1. Upload the contents of $DEPLOY_DIR/ to your Hostinger hosting"
echo "   2. Set document root to the uploaded directory"
echo "   3. Visit yourdomain.com/install"
echo ""
echo "📖 See DEPLOYMENT_HOSTINGER.md for detailed instructions"
