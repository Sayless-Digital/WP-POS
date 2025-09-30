# WP-POS Web Installer

A beautiful, step-by-step web-based installer for WP-POS that makes deployment as easy as 1-2-3!

## 🎯 Features

✅ **Visual Progress Tracking** - See exactly where you are in the installation process
✅ **System Requirements Check** - Automatically verifies your server meets all requirements
✅ **Database Connection Testing** - Test your database before proceeding
✅ **Automatic Configuration** - Generates `.env` file automatically
✅ **Admin Account Creation** - Set up your first user during installation
✅ **One-Click Installation** - Complete setup with a single click
✅ **Error Handling** - Clear error messages and troubleshooting guidance
✅ **Security** - Locks itself after successful installation

## 📋 Installation Steps

### Step 1: Upload Files

1. Upload all WP-POS files to your web server
2. Make sure the following directories are writable:
   - `storage/`
   - `bootstrap/cache/`
   - Root directory (for `.env` file)

### Step 2: Access Installer

Visit your website's install directory:
```
https://yoursite.com/install
```

### Step 3: Follow the Wizard

The installer will guide you through 5 easy steps:

#### 🔍 Step 1: Requirements Check
- Verifies PHP version (>= 8.1)
- Checks required PHP extensions
- Validates directory permissions
- Shows warnings for optional features

#### 💾 Step 2: Database Configuration
- Enter database credentials
- Test connection before proceeding
- Validates database accessibility

#### ⚙️ Step 3: Application Configuration
- Set application name
- Configure application URL
- Choose environment (production/local)
- Set debug mode

#### 👤 Step 4: Admin Account
- Create your administrator account
- Set secure password
- Configure admin email

#### 🚀 Step 5: Complete Installation
- Reviews all settings
- Runs database migrations
- Seeds initial data
- Creates admin user
- Optimizes application
- Locks installer

## 🔒 Security Features

### Automatic Locking
After successful installation, the installer automatically locks itself to prevent re-installation.

### Password Requirements
- Minimum 8 characters
- Recommended: Mix of uppercase, lowercase, numbers, and special characters

### Environment Protection
- Debug mode disabled by default in production
- Secure .env file generation
- Application key auto-generated

## 🛠️ Troubleshooting

### Common Issues

#### 1. "Requirements Not Met"
**Solution:** Install missing PHP extensions or update PHP version
```bash
# Ubuntu/Debian
sudo apt install php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl

# CentOS/RHEL
sudo yum install php82-mysql php82-mbstring php82-xml php82-curl
```

#### 2. "Directory Not Writable"
**Solution:** Set correct permissions
```bash
chmod -R 755 storage bootstrap/cache
chmod 755 .
```

#### 3. "Database Connection Failed"
**Solutions:**
- Verify database exists
- Check database credentials
- Ensure MySQL is running
- Verify database user has proper privileges

#### 4. "Migration Failed"
**Solutions:**
- Check database user has CREATE/ALTER privileges
- Verify database is empty or use fresh database
- Check MySQL version (>= 5.7 required)

#### 5. "Already Installed" Message
**Solution:** To reinstall, either:
- Delete `.env` file and `install/.installed` file
- Or add `?force=1` to installer URL

### Manual Installation

If the web installer fails, you can install manually:

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Edit .env with your settings
nano .env

# 3. Generate application key
php artisan key:generate

# 4. Run migrations
php artisan migrate --force

# 5. Seed database
php artisan db:seed --force

# 6. Create storage link
php artisan storage:link

# 7. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📁 File Structure

```
install/
├── index.php              # Main installer interface
├── InstallerHelper.php    # Installation logic
├── README.md             # This file
├── steps/
│   ├── step1.php         # Requirements check
│   ├── step2.php         # Database configuration
│   ├── step3.php         # Application configuration
│   ├── step4.php         # Admin account creation
│   └── step5.php         # Complete installation
└── .installed            # Lock file (created after installation)
```

## 🔧 System Requirements

### Required
- PHP >= 8.1
- MySQL >= 5.7 or MariaDB >= 10.3
- PHP Extensions:
  - PDO
  - PDO MySQL
  - Mbstring
  - OpenSSL
  - JSON
  - cURL

### Recommended
- PHP Extensions:
  - GD (for image processing)
  - ZIP (for backups)
- Redis (for caching)
- Composer (for updates)

## 🎨 Customization

### Changing Installer Appearance

Edit the CSS in `install/index.php` to customize colors and styling.

### Adding Custom Steps

1. Create new step file in `install/steps/`
2. Update progress bar in `install/index.php`
3. Add step logic to `InstallerHelper.php`

## 🚀 After Installation

Once installation is complete:

1. **Login** to your admin panel
2. **Configure Settings** - Set up your store details
3. **Add Products** - Start adding your inventory
4. **Configure WooCommerce** (optional) - Connect to your online store
5. **Train Staff** - Show your team how to use the POS
6. **Start Selling!** 🎉

## 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review server error logs
3. Consult the main documentation
4. Check file permissions

## 🔐 Security Recommendations

After installation:

1. **Delete installer** (optional but recommended):
   ```bash
   rm -rf install/
   ```

2. **Set proper file permissions**:
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod -R 775 storage bootstrap/cache
   ```

3. **Enable HTTPS** - Always use SSL in production

4. **Regular Backups** - Use the backup script:
   ```bash
   ./scripts/backup.sh
   ```

5. **Keep Updated** - Regularly update dependencies

## 📝 License

This installer is part of the WP-POS project.

---

**Happy Installing!** 🎉

For more information, visit the main [README.md](../README.md)