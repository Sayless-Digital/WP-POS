# WP-POS Installation Troubleshooting Guide

## Common Installation Issues and Solutions

### 1. Fatal Error: Call to undefined function exec()

**Error Message:**
```
Fatal error: Uncaught Error: Call to undefined function exec() in /path/to/install/index.php:249
```

**Cause:**
Your hosting provider has disabled the `exec()` function for security reasons. This is common on shared hosting providers like Hostinger, GoDaddy, etc.

**Solution:**
The installer has been updated to automatically detect when `exec()` is disabled and use an alternative method. Simply run the installer again - it will now work without the `exec()` function.

**What the installer does:**
- Detects if `exec()` is available
- If not available, uses direct PHP execution to run Laravel commands
- Creates admin users directly through the database
- Handles all installation steps without requiring shell access

### 2. Database Connection Issues

**Common Errors:**
- "Connection failed: Access denied"
- "Connection failed: Unknown database"
- "Connection failed: Can't connect to MySQL server"

**Solutions:**
1. **Verify Database Credentials:**
   - Double-check host, database name, username, and password
   - For shared hosting, host is usually `localhost`
   - Database name should match what you created in your hosting control panel

2. **Create Database First:**
   - Log into your hosting control panel (cPanel, Plesk, etc.)
   - Create a new MySQL database
   - Create a database user with full privileges to that database
   - Use these credentials in the installer

3. **Check Database Server:**
   - Some hosts use different ports (3306, 3307, etc.)
   - Contact your hosting provider for correct database settings

### 3. WooCommerce Connection Issues

**Common Errors:**
- "Connection failed (HTTP 401)"
- "Connection failed (HTTP 404)"
- "Connection failed (HTTP 403)"

**Solutions:**
1. **Check API Credentials:**
   - Ensure Consumer Key starts with `ck_`
   - Ensure Consumer Secret starts with `cs_`
   - Verify credentials are active and not expired

2. **Check Store URL:**
   - Use the full URL (https://yourstore.com)
   - Ensure WooCommerce is installed and active
   - Make sure WooCommerce REST API is enabled

3. **Test API Access:**
   - Go to WooCommerce → Settings → Advanced → REST API
   - Ensure "Enable REST API" is checked
   - Test the endpoint manually

### 4. File Permission Issues

**Error:**
- "Failed to write .env file. Check file permissions."
- "Failed to lock installer. Check file permissions."

**Solutions:**
1. **Set Correct Permissions:**
   ```bash
   chmod 755 /path/to/your/project
   chmod 644 /path/to/your/project/.env
   ```

2. **Via Hosting Control Panel:**
   - Use File Manager in cPanel/Plesk
   - Right-click on files/folders → Permissions
   - Set folders to 755, files to 644

### 5. PHP Requirements Not Met

**Error:**
- Missing required PHP extensions

**Required Extensions:**
- pdo
- pdo_mysql
- openssl
- mbstring
- tokenizer
- xml
- ctype
- json
- bcmath
- curl

**Solutions:**
1. **Contact Hosting Provider:**
   - Ask them to enable missing extensions
   - Most shared hosts can enable these via control panel

2. **Check PHP Version:**
   - Requires PHP 8.0 or higher
   - Some hosts default to older PHP versions

### 6. Installation Stuck or Slow

**Causes:**
- Large database seeding
- Network timeouts
- Server resource limits

**Solutions:**
1. **Increase Time Limits:**
   - Contact hosting provider to increase execution time
   - Some shared hosts have 30-second limits

2. **Check Server Resources:**
   - Ensure sufficient memory (512MB minimum)
   - Check disk space availability

### 7. Admin User Creation Fails

**Error:**
- "Admin user creation failed"

**Solutions:**
1. **Check Database Connection:**
   - Ensure database is accessible
   - Verify tables were created properly

2. **Manual Admin Creation:**
   - Access your database directly
   - Insert admin user manually if needed
   - Use Laravel's built-in user management

### 8. Storage Link Issues

**Warning:**
- "Storage link warning"

**Solutions:**
1. **Manual Storage Link:**
   - This is not critical for basic functionality
   - Can be created manually later via SSH or file manager

2. **Symlink Alternative:**
   - Copy storage/app/public contents to public/storage
   - This achieves the same result

## Installation Process Overview

The installer now works in two modes:

### Mode 1: Full Shell Access (exec() available)
- Uses Laravel Artisan commands directly
- Faster and more reliable
- Available on VPS/dedicated servers

### Mode 2: Shared Hosting (exec() disabled)
- Uses direct PHP execution
- Bootstraps Laravel application directly
- Creates database records without shell commands
- Works on all shared hosting providers

## Getting Help

If you continue to have issues:

1. **Check the installer's system information** (shown on step 1)
2. **Review error logs** in your hosting control panel
3. **Contact your hosting provider** for PHP/database issues
4. **Check Laravel logs** in storage/logs/laravel.log after installation

## Post-Installation

After successful installation:

1. **Remove install directory** for security
2. **Set up SSL certificate** if not already done
3. **Configure backups** of your database
4. **Test all functionality** before going live

The installer creates a `.installed` file to prevent reinstallation. Delete this file to reinstall if needed.
