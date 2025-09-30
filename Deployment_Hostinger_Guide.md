# Hostinger Deployment Guide

## 7. Deployment Strategy for Hostinger Shared Hosting

### 7.1 Hostinger Constraints & Solutions

**Constraints:**
1. ❌ No SSH/Terminal access
2. ❌ No Composer command line
3. ❌ No Node.js/npm
4. ❌ Limited PHP extensions
5. ❌ No cron job customization (limited)
6. ✅ File Manager access
7. ✅ MySQL database
8. ✅ PHP 8.1+ support
9. ✅ Basic cron jobs

**Solutions:**

| Constraint | Solution |
|------------|----------|
| No SSH | Use local development + FTP/File Manager upload |
| No Composer CLI | Run Composer locally, upload vendor folder |
| No Node.js | Use CDN for Alpine.js, no build step for Livewire |
| Limited extensions | Use pure PHP alternatives, check phpinfo() |
| No custom cron | Use Hostinger's cron + external monitoring |
| File uploads | Use FileZilla or Hostinger File Manager |

### 7.2 Pre-Deployment Checklist

**Local Development Setup:**
```bash
# 1. Install Laravel locally
composer create-project laravel/laravel pos-system
cd pos-system

# 2. Install required packages
composer require livewire/livewire
composer require automattic/woocommerce
composer require barryvdh/laravel-dompdf
composer require spatie/laravel-permission

# 3. Configure for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Optimize autoloader
composer install --optimize-autoloader --no-dev

# 5. Create deployment package
# Exclude: node_modules, .git, .env, storage/logs/*
```

**Files to Prepare:**
- ✅ Complete Laravel application
- ✅ Vendor folder (with dependencies)
- ✅ .env.example (template)
- ✅ Database migration files
- ✅ Seed data
- ✅ Public assets (CSS, JS, images)

### 7.3 Step-by-Step Deployment Process

#### Step 1: Prepare Hostinger Environment

**1.1 Create MySQL Database:**
1. Log into Hostinger control panel
2. Go to "Databases" → "MySQL Databases"
3. Click "Create Database"
4. Database name: `u123456789_pos`
5. Username: `u123456789_pos_user`
6. Password: Generate strong password
7. Save credentials securely

**1.2 Configure PHP Settings:**
1. Go to "Advanced" → "PHP Configuration"
2. Set PHP version to 8.1 or higher
3. Increase limits:
   - `upload_max_filesize`: 64M
   - `post_max_size`: 64M
   - `max_execution_time`: 300
   - `memory_limit`: 256M

**1.3 Check PHP Extensions:**
```php
// Create phpinfo.php in public_html temporarily
<?php phpinfo(); ?>
```

Required extensions:
- ✅ PDO
- ✅ PDO_MySQL
- ✅ OpenSSL
- ✅ Mbstring
- ✅ Tokenizer
- ✅ XML
- ✅ Ctype
- ✅ JSON
- ✅ BCMath
- ✅ Fileinfo
- ✅ GD (for image processing)

#### Step 2: Upload Application Files

**2.1 Using FileZilla (Recommended):**
```
Local Structure:          Remote Structure (Hostinger):
pos-system/              /domains/yourdomain.com/
├── app/                 ├── pos-system/
├── bootstrap/           │   ├── app/
├── config/              │   ├── bootstrap/
├── database/            │   ├── config/
├── public/              │   ├── database/
├── resources/           │   ├── resources/
├── routes/              │   ├── routes/
├── storage/             │   ├── storage/
├── vendor/              │   └── vendor/
└── .env.example         └── public_html/ (symlink to pos-system/public)
```

**2.2 Upload Steps:**
1. Connect via FTP:
   - Host: ftp.yourdomain.com
   - Username: Your Hostinger FTP username
   - Password: Your FTP password
   - Port: 21

2. Upload entire project to `/domains/yourdomain.com/pos-system/`

3. Upload public folder contents to `/domains/yourdomain.com/public_html/`

**2.3 Alternative: File Manager Upload:**
1. Compress project locally: `pos-system.zip`
2. Upload via Hostinger File Manager
3. Extract in `/domains/yourdomain.com/`
4. Move public folder contents to `public_html/`

#### Step 3: Configure Application

**3.1 Create .env File:**
```env
APP_NAME="POS System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_pos
DB_USERNAME=u123456789_pos_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# WooCommerce Integration
WC_STORE_URL=https://your-woocommerce-site.com
WC_CONSUMER_KEY=ck_xxxxxxxxxxxxx
WC_CONSUMER_SECRET=cs_xxxxxxxxxxxxx

# POS Configuration
POS_CURRENCY=USD
POS_TAX_RATE=0.00
POS_LOW_STOCK_THRESHOLD=10
```

**3.2 Generate Application Key:**

Since you can't run `php artisan key:generate`, use this workaround:

```php
// Create generate-key.php in public_html temporarily
<?php
$key = 'base64:' . base64_encode(random_bytes(32));
echo "Copy this to your .env file:\n";
echo "APP_KEY=" . $key;
?>
```

Visit: `https://yourdomain.com/generate-key.php`
Copy the key to `.env`
Delete `generate-key.php`

**3.3 Set Permissions:**

Using File Manager:
1. Set `storage/` to 755 (recursive)
2. Set `bootstrap/cache/` to 755 (recursive)
3. Set `.env` to 644

#### Step 4: Initialize Database

**4.1 Create Migration Script:**

```php
// public_html/install.php
<?php
require __DIR__ . '/../pos-system/vendor/autoload.php';

$app = require_once __DIR__ . '/../pos-system/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Run migrations
echo "Running migrations...\n";
$kernel->call('migrate', ['--force' => true]);

// Run seeders
echo "Running seeders...\n";
$kernel->call('db:seed', ['--force' => true]);

echo "Installation complete!\n";
echo "Please delete this file for security.\n";
?>
```

Visit: `https://yourdomain.com/install.php`
Delete `install.php` after successful run

**4.2 Alternative: phpMyAdmin Import:**

1. Export migrations as SQL from local:
```bash
php artisan migrate --pretend > migrations.sql
```

2. Import via Hostinger phpMyAdmin:
   - Go to "Databases" → "phpMyAdmin"
   - Select your database
   - Click "Import"
   - Upload `migrations.sql`

#### Step 5: Configure Web Server

**5.1 Update .htaccess in public_html:**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect to HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect Trailing Slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    
    # Handle Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Disable directory browsing
Options -Indexes

# Prevent access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect .env file
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

**5.2 Update index.php:**

```php
// public_html/index.php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Adjust path to Laravel installation
require __DIR__.'/../pos-system/vendor/autoload.php';

$app = require_once __DIR__.'/../pos-system/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

#### Step 6: Setup Cron Jobs

**6.1 Configure Hostinger Cron:**

1. Go to "Advanced" → "Cron Jobs"
2. Add new cron job:

```bash
# Run Laravel scheduler every minute
* * * * * cd /home/u123456789/domains/yourdomain.com/pos-system && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

**6.2 Alternative: External Cron Service:**

If Hostinger cron is limited, use external service:

1. Create endpoint:
```php
// routes/web.php
Route::get('/cron/{token}', function($token) {
    if ($token !== config('app.cron_token')) {
        abort(403);
    }
    
    Artisan::call('schedule:run');
    return 'Cron executed';
})->name('cron.run');
```

2. Add to .env:
```env
CRON_TOKEN=your-secret-token-here
```

3. Use service like cron-job.org:
   - URL: `https://yourdomain.com/cron/your-secret-token-here`
   - Interval: Every 5 minutes

#### Step 7: Setup Queue Worker

**7.1 Database Queue Driver:**

Already configured in `.env`:
```env
QUEUE_CONNECTION=database
```

**7.2 Create Queue Table:**

```php
// Include in install.php or run via phpMyAdmin
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX jobs_queue_index (queue)
);

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**7.3 Process Queue via Cron:**

```php
// app/Console/Commands/ProcessQueueJobs.php
class ProcessQueueJobs extends Command
{
    protected $signature = 'queue:process-batch';
    
    public function handle()
    {
        // Process up to 10 jobs
        for ($i = 0; $i < 10; $i++) {
            Artisan::call('queue:work', [
                '--once' => true,
                '--tries' => 3,
            ]);
        }
    }
}
```

Add to scheduler:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('queue:process-batch')->everyMinute();
}
```

### 7.4 Post-Deployment Verification

**Checklist:**

1. ✅ Visit homepage: `https://yourdomain.com`
2. ✅ Test login: `/login`
3. ✅ Check database connection
4. ✅ Test POS terminal: `/pos`
5. ✅ Verify product listing
6. ✅ Test barcode scanning
7. ✅ Complete test order
8. ✅ Check WooCommerce sync
9. ✅ Test offline mode
10. ✅ Verify cron jobs running

**Debug Common Issues:**

```php
// public_html/debug.php (temporary)
<?php
require __DIR__ . '/../pos-system/vendor/autoload.php';

echo "<h2>Environment Check</h2>";

// Check Laravel
echo "Laravel: " . (class_exists('Illuminate\Foundation\Application') ? '✅' : '❌') . "<br>";

// Check database
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=u123456789_pos',
        'u123456789_pos_user',
        'your_password'
    );
    echo "Database: ✅<br>";
} catch (PDOException $e) {
    echo "Database: ❌ " . $e->getMessage() . "<br>";
}

// Check writable directories
$dirs = ['storage', 'bootstrap/cache'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/../pos-system/' . $dir;
    echo "$dir: " . (is_writable($path) ? '✅' : '❌') . "<br>";
}

// Check PHP extensions
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? '✅' : '❌') . "<br>";
}

echo "<br>Delete this file after checking!";
?>
```

### 7.5 Maintenance & Updates

**Update Process:**

1. **Backup Current Installation:**
   - Download entire `pos-system/` folder
   - Export database via phpMyAdmin
   - Save `.env` file

2. **Test Updates Locally:**
   - Update code locally
   - Test thoroughly
   - Run migrations

3. **Deploy Updates:**
   - Upload changed files via FTP
   - Run migrations via install script
   - Clear cache

**Cache Clearing Script:**

```php
// public_html/clear-cache.php
<?php
require __DIR__ . '/../pos-system/vendor/autoload.php';

$app = require_once __DIR__ . '/../pos-system/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('config:clear');
$kernel->call('cache:clear');
$kernel->call('view:clear');
$kernel->call('route:clear');

echo "Cache cleared successfully!";
echo "\nDelete this file for security.";
?>
```

### 7.6 Security Hardening

**1. Protect Sensitive Directories:**

```apache
# pos-system/.htaccess
Order deny,allow
Deny from all
```

**2. Disable Debug Mode:**
```env
APP_DEBUG=false
APP_ENV=production
```

**3. Use HTTPS:**
- Enable SSL in Hostinger
- Force HTTPS in `.htaccess`

**4. Regular Backups:**
- Schedule weekly database backups
- Keep 3 versions of file backups
- Store backups off-server

**5. Monitor Logs:**
```php
// Check storage/logs/laravel.log regularly
// Set up email alerts for critical errors
```

### 7.7 Performance Optimization

**1. Enable OPcache:**
Check if enabled in PHP configuration

**2. Optimize Composer Autoloader:**
```bash
# Run locally before upload
composer install --optimize-autoloader --no-dev
```

**3. Cache Configuration:**
```php
// Run via script after deployment
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**4. Database Indexing:**
Ensure all foreign keys and frequently queried columns are indexed

**5. Image Optimization:**
- Compress product images before upload
- Use appropriate image sizes
- Consider CDN for static assets

### 7.8 Monitoring & Alerts

**Setup Email Alerts:**

```php
// app/Exceptions/Handler.php
public function register()
{
    $this->reportable(function (Throwable $e) {
        if (app()->environment('production')) {
            // Send email to admin
            Mail::to(config('mail.admin_email'))
                ->send(new ErrorAlert($e));
        }
    });
}
```

**Health Check Endpoint:**

```php
// routes/web.php
Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::has('health_check') ? 'working' : 'not_working',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

Monitor with UptimeRobot or similar service.

### 7.9 Troubleshooting Guide

**Issue: 500 Internal Server Error**
- Check `storage/logs/laravel.log`
- Verify file permissions (755 for directories, 644 for files)
- Check `.env` configuration
- Ensure `APP_KEY` is set

**Issue: Database Connection Failed**
- Verify database credentials in `.env`
- Check if database exists
- Ensure database user has proper permissions
- Try `localhost` vs `127.0.0.1` for `DB_HOST`

**Issue: Assets Not Loading**
- Check `APP_URL` in `.env`
- Verify public folder structure
- Check `.htaccess` rewrite rules
- Clear browser cache

**Issue: Cron Jobs Not Running**
- Verify cron job syntax
- Check cron execution logs in Hostinger
- Test scheduler manually via script
- Consider external cron service

**Issue: Queue Jobs Not Processing**
- Check `jobs` table for pending jobs
- Verify queue driver is `database`
- Ensure cron is calling `queue:process-batch`
- Check `failed_jobs` table for errors

### 7.10 Deployment Checklist Summary

**Pre-Deployment:**
- [ ] Test application locally
- [ ] Run all migrations
- [ ] Optimize for production
- [ ] Create deployment package
- [ ] Backup existing installation

**Deployment:**
- [ ] Create database
- [ ] Upload files
- [ ] Configure .env
- [ ] Set permissions
- [ ] Run migrations
- [ ] Setup cron jobs
- [ ] Configure queue worker

**Post-Deployment:**
- [ ] Test all features
- [ ] Verify WooCommerce sync
- [ ] Check offline mode
- [ ] Monitor error logs
- [ ] Setup backups
- [ ] Configure monitoring
- [ ] Document credentials

**Ongoing:**
- [ ] Weekly database backups
- [ ] Monthly security updates
- [ ] Monitor performance
- [ ] Review error logs
- [ ] Test disaster recovery