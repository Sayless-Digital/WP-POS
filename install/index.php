<?php
/**
 * WP-POS Simple Installer
 * Compact and minimal installation wizard
 */

session_start();

// Check if already installed
if (file_exists(__DIR__ . '/../.env') && !isset($_GET['force'])) {
    $envContent = file_get_contents(__DIR__ . '/../.env');
    if (strpos($envContent, 'APP_KEY=base64:') !== false) {
        die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Already Installed</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: system-ui; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f8fafc; }
                .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
                .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; margin-top: 1rem; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>✅ Already Installed</h1>
                <p>WP-POS is already installed.</p>
                <a href="../" class="btn">Go to Application</a>
            </div>
        </body>
        </html>
        ');
    }
}

// Initialize session
if (!isset($_SESSION['step'])) {
    $_SESSION['step'] = 1;
    $_SESSION['data'] = [];
}

$step = $_SESSION['step'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next'])) {
        // Save data
        if ($step === 1) {
            $_SESSION['data']['db'] = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'database' => $_POST['db_database'] ?? '',
                'username' => $_POST['db_username'] ?? 'root',
                'password' => $_POST['db_password'] ?? ''
            ];
        } elseif ($step === 2) {
            $_SESSION['data']['app'] = [
                'name' => $_POST['app_name'] ?? 'WP-POS',
                'url' => $_POST['app_url'] ?? '',
                'admin_name' => $_POST['admin_name'] ?? '',
                'admin_email' => $_POST['admin_email'] ?? '',
                'admin_password' => $_POST['admin_password'] ?? ''
            ];
        }
        
        $_SESSION['step'] = $step + 1;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['back'])) {
        $_SESSION['step'] = max(1, $step - 1);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['install'])) {
        // Run installation
        try {
            // Create .env file
            $envContent = file_get_contents(__DIR__ . '/../.env.example');
            $db = $_SESSION['data']['db'];
            $app = $_SESSION['data']['app'];
            
            $envContent = str_replace([
                'APP_NAME=Laravel',
                'APP_URL=http://localhost',
                'DB_HOST=127.0.0.1',
                'DB_DATABASE=laravel',
                'DB_USERNAME=root',
                'DB_PASSWORD='
            ], [
                'APP_NAME="' . $app['name'] . '"',
                'APP_URL=' . $app['url'],
                'DB_HOST=' . $db['host'],
                'DB_DATABASE=' . $db['database'],
                'DB_USERNAME=' . $db['username'],
                'DB_PASSWORD=' . $db['password']
            ], $envContent);
            
            file_put_contents(__DIR__ . '/../.env', $envContent);
            
            // Generate app key
            $key = 'base64:' . base64_encode(random_bytes(32));
            $envContent = str_replace('APP_KEY=', 'APP_KEY=' . $key, $envContent);
            file_put_contents(__DIR__ . '/../.env', $envContent);
            
            // Run artisan commands
            chdir(__DIR__ . '/..');
            exec('php artisan migrate --force 2>&1', $output);
            exec('php artisan db:seed --force 2>&1', $output);
            exec('php artisan storage:link 2>&1', $output);
            
            // Create admin user
            exec('php artisan db:seed --class=AdminUserSeeder --name="' . $app['admin_name'] . '" --email="' . $app['admin_email'] . '" --password="' . $app['admin_password'] . '" 2>&1', $output);
            
            // Lock installer
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
            
            $_SESSION['installed'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

if (isset($_SESSION['installed'])) {
    // Installation complete
    unset($_SESSION['installed']);
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Installation Complete</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: system-ui; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f8fafc; }
            .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
            .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #10b981; color: white; text-decoration: none; border-radius: 6px; margin-top: 1rem; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>🎉 Installation Complete!</h1>
            <p>WP-POS has been successfully installed.</p>
            <a href="../" class="btn">Go to Application</a>
        </div>
    </body>
    </html>
    ');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP-POS Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui; background: #f8fafc; min-height: 100vh; padding: 1rem; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #3b82f6; color: white; padding: 1.5rem; text-align: center; }
        .header h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .progress { display: flex; padding: 1rem; background: #f1f5f9; }
        .step { flex: 1; text-align: center; font-size: 0.875rem; color: #64748b; }
        .step.active { color: #3b82f6; font-weight: 600; }
        .content { padding: 2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.25rem; font-weight: 500; color: #374151; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
        .form-group input:focus { outline: none; border-color: #3b82f6; }
        .buttons { display: flex; gap: 1rem; margin-top: 2rem; }
        .btn { flex: 1; padding: 0.75rem; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-success { background: #10b981; color: white; }
        .error { background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 WP-POS Installer</h1>
            <p>Simple 3-step installation</p>
        </div>
        
        <div class="progress">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1. Database</div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2. Settings</div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3. Install</div>
        </div>
        
        <div class="content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <h2>Database Configuration</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="<?php echo htmlspecialchars($_SESSION['data']['db']['host'] ?? 'localhost'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_database" value="<?php echo htmlspecialchars($_SESSION['data']['db']['database'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="db_username" value="<?php echo htmlspecialchars($_SESSION['data']['db']['username'] ?? 'root'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="db_password" value="<?php echo htmlspecialchars($_SESSION['data']['db']['password'] ?? ''); ?>">
                    </div>
                    <div class="buttons">
                        <button type="submit" name="next" class="btn btn-primary">Next →</button>
                    </div>
                </form>
                
            <?php elseif ($step === 2): ?>
                <h2>Application Settings</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>App Name</label>
                        <input type="text" name="app_name" value="<?php echo htmlspecialchars($_SESSION['data']['app']['name'] ?? 'WP-POS'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>App URL</label>
                        <input type="url" name="app_url" value="<?php echo htmlspecialchars($_SESSION['data']['app']['url'] ?? 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI'])); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Name</label>
                        <input type="text" name="admin_name" value="<?php echo htmlspecialchars($_SESSION['data']['app']['admin_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($_SESSION['data']['app']['admin_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_password" value="<?php echo htmlspecialchars($_SESSION['data']['app']['admin_password'] ?? ''); ?>" required>
                    </div>
                    <div class="buttons">
                        <button type="submit" name="back" class="btn btn-secondary">← Back</button>
                        <button type="submit" name="next" class="btn btn-primary">Next →</button>
                    </div>
                </form>
                
            <?php elseif ($step === 3): ?>
                <h2>Ready to Install</h2>
                <p>Review your settings and click Install to complete the setup.</p>
                
                <div style="background: #f1f5f9; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                    <strong>Database:</strong> <?php echo htmlspecialchars($_SESSION['data']['db']['database']); ?><br>
                    <strong>App Name:</strong> <?php echo htmlspecialchars($_SESSION['data']['app']['name']); ?><br>
                    <strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['data']['app']['admin_email']); ?>
                </div>
                
                <form method="POST">
                    <div class="buttons">
                        <button type="submit" name="back" class="btn btn-secondary">← Back</button>
                        <button type="submit" name="install" class="btn btn-success">🚀 Install</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>