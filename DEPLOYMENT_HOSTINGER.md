# Hostinger Deployment Guide for WP-POS

## 🚀 Quick Deployment Steps

### 1. Upload Files
Upload all WP-POS files to your Hostinger hosting account via:
- **File Manager** (in Hostinger control panel)
- **FTP/SFTP** client
- **Git** (if available)

### 2. Set Document Root
In your Hostinger control panel:
1. Go to **Advanced** → **File Manager**
2. Look for **Document Root** settings
3. Set it to point to your Laravel root directory (where `artisan` file is located)
4. **NOT** to the `public/` folder

### 3. Set Permissions
Run these commands via **File Manager Terminal** or **SSH**:
```bash
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

### 4. Run Installation
Visit: `https://yourdomain.com/install`

## 🔧 Configuration Files Included

### Root .htaccess
The project includes a root-level `.htaccess` file that automatically redirects all requests to `public/index.php`, making it compatible with Hostinger's default configuration.

### Laravel Configuration
- `.env` file will be created during installation
- Database migrations will run automatically
- Admin user will be created

## 📋 Hostinger-Specific Settings

### PHP Version
- Ensure PHP 8.1+ is selected in Hostinger control panel
- Recommended: PHP 8.2

### Database
- Create MySQL database in Hostinger control panel
- Note the database credentials for installation

### SSL Certificate
- Enable SSL certificate in Hostinger control panel
- Update `APP_URL` in `.env` to use `https://`

## 🛠️ Troubleshooting

### 404 Errors
If you get 404 errors:
1. Check that document root points to Laravel root (not `public/`)
2. Verify `.htaccess` file exists in root directory
3. Check file permissions

### Installation Issues
1. Verify database credentials
2. Check PHP extensions are enabled
3. Ensure storage directories are writable

### Performance Optimization
After installation, run these commands:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📞 Support

If you encounter issues:
1. Check Hostinger error logs
2. Verify file permissions
3. Test database connection
4. Check PHP error logs

## 🔐 Security Recommendations

After deployment:
1. **Delete installer**: `rm -rf install/`
2. **Set proper permissions**: 
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod -R 775 storage bootstrap/cache
   ```
3. **Enable HTTPS** in Hostinger control panel
4. **Regular backups** using Hostinger backup tools

---

**Happy Deploying!** 🎉
