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
    if (isset($_POST['test_db'])) {
        // Test database connection
        $host = $_POST['db_host'] ?? 'localhost';
        $database = $_POST['db_database'] ?? '';
        $username = $_POST['db_username'] ?? 'root';
        $password = $_POST['db_password'] ?? '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $_SESSION['db_tested'] = true;
            $_SESSION['db_success'] = 'Database connection successful!';
        } catch (PDOException $e) {
            $_SESSION['db_tested'] = false;
            $_SESSION['db_error'] = 'Connection failed: ' . $e->getMessage();
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['test_wc'])) {
        // Test WooCommerce connection
        $url = rtrim($_POST['wc_url'] ?? '', '/');
        $key = $_POST['wc_key'] ?? '';
        $secret = $_POST['wc_secret'] ?? '';
        
        try {
            $endpoint = $url . '/wp-json/wc/v3/system_status';
            $auth = base64_encode($key . ':' . $secret);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . $auth,
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $_SESSION['wc_tested'] = true;
                $_SESSION['wc_success'] = 'WooCommerce connection successful!';
            } else {
                $_SESSION['wc_tested'] = false;
                $_SESSION['wc_error'] = 'Connection failed (HTTP ' . $httpCode . ')';
            }
        } catch (Exception $e) {
            $_SESSION['wc_tested'] = false;
            $_SESSION['wc_error'] = 'Connection failed: ' . $e->getMessage();
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['next'])) {
        // Save data
        if ($step === 1) {
            $_SESSION['data']['db'] = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'database' => $_POST['db_database'] ?? '',
                'username' => $_POST['db_username'] ?? 'root',
                'password' => $_POST['db_password'] ?? ''
            ];
            
            // Check if database was tested
            if (!isset($_SESSION['db_tested']) || !$_SESSION['db_tested']) {
                $_SESSION['error'] = 'Please test database connection first';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } elseif ($step === 2) {
            $_SESSION['data']['app'] = [
                'name' => $_POST['app_name'] ?? 'WP-POS',
                'url' => $_POST['app_url'] ?? ''
            ];
        } elseif ($step === 3) {
            $_SESSION['data']['admin'] = [
                'name' => $_POST['admin_name'] ?? '',
                'email' => $_POST['admin_email'] ?? '',
                'password' => $_POST['admin_password'] ?? ''
            ];
        } elseif ($step === 4) {
            $_SESSION['data']['wc'] = [
                'enabled' => isset($_POST['wc_enabled']) ? 'true' : 'false',
                'url' => $_POST['wc_url'] ?? '',
                'key' => $_POST['wc_key'] ?? '',
                'secret' => $_POST['wc_secret'] ?? ''
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
    
    if (isset($_POST['jump_to_step'])) {
        $jumpTo = (int)$_POST['jump_to_step'];
        // Allow jumping to any step (1-5)
        if ($jumpTo >= 1 && $jumpTo <= 5) {
            $_SESSION['step'] = $jumpTo;
        }
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
            $wc = $_SESSION['data']['wc'] ?? ['enabled' => 'false', 'url' => '', 'key' => '', 'secret' => ''];
            
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
            
            // Add WooCommerce settings
            if ($wc['enabled'] === 'true') {
                $envContent .= "\n# WooCommerce Integration\n";
                $envContent .= "WOOCOMMERCE_URL=" . $wc['url'] . "\n";
                $envContent .= "WOOCOMMERCE_CONSUMER_KEY=" . $wc['key'] . "\n";
                $envContent .= "WOOCOMMERCE_CONSUMER_SECRET=" . $wc['secret'] . "\n";
            }
            
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
            $admin = $_SESSION['data']['admin'];
            exec('php artisan db:seed --class=AdminUserSeeder --name="' . $admin['name'] . '" --email="' . $admin['email'] . '" --password="' . $admin['password'] . '" 2>&1', $output);
            
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
        .container { max-width: 90%; width: 100%; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 1.5rem 2rem; text-align: center; }
        .header h1 { font-size: 1.5rem; margin-bottom: 0.25rem; font-weight: 700; }
        .header p { font-size: 0.875rem; opacity: 0.9; margin: 0; }
        .progress { display: flex; padding: 0.75rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .step { flex: 1; text-align: center; font-size: 0.8rem; color: #64748b; cursor: pointer; padding: 0.5rem 0.25rem; border-radius: 6px; transition: all 0.2s ease; font-weight: 500; margin: 0 0.25rem; }
        .step.active { color: #3b82f6; font-weight: 600; background: #dbeafe; }
        .step.completed { color: #10b981; font-weight: 600; }
        .step:hover { background: #e2e8f0; color: #374151; }
        .step.active:hover { background: #bfdbfe; color: #3b82f6; }
        .step.completed:hover { background: #d1fae5; color: #10b981; }
        .content { padding: 1.5rem 2rem; }
        .content h2 { font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem; }
        .content p { color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem; }
        .form-group input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; transition: all 0.2s ease; }
        .form-group input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .buttons { display: flex; gap: 1rem; margin-top: 2rem; }
        .btn { flex: 1; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; font-size: 0.875rem; transition: all 0.2s ease; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #e5e7eb; transform: translateY(-1px); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-1px); }
        .btn:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; transform: none; }
        .btn:disabled:hover { transform: none; }
        .error { background: #fef2f2; color: #dc2626; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #dc2626; font-size: 0.875rem; }
        
        /* Responsive design */
        @media (min-width: 768px) {
            .container { max-width: 600px; }
            .header h1 { font-size: 1.75rem; }
            .content { padding: 2rem 2.5rem; }
        }
        
        @media (min-width: 1024px) {
            .container { max-width: 700px; }
            .header h1 { font-size: 2rem; }
            .content { padding: 2.5rem 3rem; }
        }
        
        @media (max-width: 480px) {
            .container { max-width: 95%; }
            .header { padding: 1rem 1.5rem; }
            .header h1 { font-size: 1.25rem; }
            .content { padding: 1rem 1.5rem; }
            .progress { padding: 0.5rem 0.75rem; flex-wrap: wrap; }
            .step { font-size: 0.7rem; min-width: 60px; padding: 0.4rem 0.2rem; }
            .buttons { gap: 0.75rem; }
            .btn { padding: 0.75rem 1rem; font-size: 0.8rem; }
        }
        
        @media (max-width: 360px) {
            .step { font-size: 0.65rem; min-width: 50px; }
            .content { padding: 0.75rem 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 WP-POS Installer</h1>
            <p>Simple 5-step installation</p>
        </div>
        
        <div class="progress">
            <div class="step <?php echo $step == 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">1. Database</div>
            <div class="step <?php echo $step == 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">2. Settings</div>
            <div class="step <?php echo $step == 3 ? 'active' : ($step > 3 ? 'completed' : ''); ?>">3. Admin</div>
            <div class="step <?php echo $step == 4 ? 'active' : ($step > 4 ? 'completed' : ''); ?>">4. WooCommerce</div>
            <div class="step <?php echo $step == 5 ? 'active' : ''; ?>">5. Install</div>
        </div>
        
        <div class="content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <h2>Database Configuration</h2>
                
                <?php if (isset($_SESSION['db_success'])): ?>
                    <div style="background: #d1fae5; color: #065f46; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                        ✅ <?php echo htmlspecialchars($_SESSION['db_success']); unset($_SESSION['db_success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['db_error'])): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                        ❌ <?php echo htmlspecialchars($_SESSION['db_error']); unset($_SESSION['db_error']); ?>
                    </div>
                <?php endif; ?>
                
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
                        <button type="submit" name="test_db" class="btn btn-secondary">🔍 Test Connection</button>
                        <button type="submit" name="next" class="btn btn-primary" <?php echo !isset($_SESSION['db_tested']) || !$_SESSION['db_tested'] ? 'disabled' : ''; ?>>Next →</button>
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
                    <div class="buttons">
                        <button type="submit" name="back" class="btn btn-secondary">← Back</button>
                        <button type="submit" name="next" class="btn btn-primary">Next →</button>
                    </div>
                </form>
                
            <?php elseif ($step === 3): ?>
                <h2>Admin Account</h2>
                <p>Create your administrator account to access the system.</p>
                <form method="POST">
                    <div class="form-group">
                        <label>Admin Name</label>
                        <input type="text" name="admin_name" value="<?php echo htmlspecialchars($_SESSION['data']['admin']['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($_SESSION['data']['admin']['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_password" value="<?php echo htmlspecialchars($_SESSION['data']['admin']['password'] ?? ''); ?>" required>
                    </div>
                    <div class="buttons">
                        <button type="submit" name="back" class="btn btn-secondary">← Back</button>
                        <button type="submit" name="next" class="btn btn-primary">Next →</button>
                    </div>
                </form>
                
            <?php elseif ($step === 4): ?>
                <h2>WooCommerce Integration</h2>
                <p>Optional WooCommerce connection.</p>
                
                <?php if (isset($_SESSION['wc_success'])): ?>
                    <div style="background: #d1fae5; color: #065f46; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                        ✅ <?php echo htmlspecialchars($_SESSION['wc_success']); unset($_SESSION['wc_success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['wc_error'])): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                        ❌ <?php echo htmlspecialchars($_SESSION['wc_error']); unset($_SESSION['wc_error']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="wc_enabled" <?php echo ($_SESSION['data']['wc']['enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?> onchange="toggleWcFields()" style="margin: 0;">
                            Enable WooCommerce Integration
                        </label>
                    </div>
                    
                    <div id="wc-fields" style="<?php echo ($_SESSION['data']['wc']['enabled'] ?? 'false') === 'true' ? '' : 'display: none;'; ?>">
                        <div class="form-group">
                            <label>WooCommerce Store URL</label>
                            <input type="url" name="wc_url" value="<?php echo htmlspecialchars($_SESSION['data']['wc']['url'] ?? ''); ?>" placeholder="https://yourstore.com">
                        </div>
                        <div class="form-group">
                            <label>Consumer Key</label>
                            <input type="text" name="wc_key" value="<?php echo htmlspecialchars($_SESSION['data']['wc']['key'] ?? ''); ?>" placeholder="ck_...">
                        </div>
                        <div class="form-group">
                            <label>Consumer Secret</label>
                            <input type="password" name="wc_secret" value="<?php echo htmlspecialchars($_SESSION['data']['wc']['secret'] ?? ''); ?>" placeholder="cs_...">
                        </div>
                        <div style="text-align: center; margin: 1rem 0;">
                            <button type="submit" name="test_wc" class="btn btn-secondary">🔍 Test Connection</button>
                        </div>
                    </div>
                    
                    <div class="buttons">
                        <button type="submit" name="back" class="btn btn-secondary">← Back</button>
                        <button type="submit" name="next" class="btn btn-primary">Next →</button>
                    </div>
                </form>
                
            <?php elseif ($step === 5): ?>
                <h2>Ready to Install</h2>
                <p>Review your settings and click Install to complete the setup.</p>
                
                <div style="background: #f1f5f9; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                    <strong>Database:</strong> <?php echo htmlspecialchars($_SESSION['data']['db']['database']); ?><br>
                    <strong>App Name:</strong> <?php echo htmlspecialchars($_SESSION['data']['app']['name']); ?><br>
                    <strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['data']['admin']['email']); ?><br>
                    <strong>WooCommerce:</strong> <?php echo ($_SESSION['data']['wc']['enabled'] ?? 'false') === 'true' ? 'Enabled' : 'Disabled'; ?>
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
    
    <script>
        function toggleWcFields() {
            const checkbox = document.querySelector('input[name="wc_enabled"]');
            const fields = document.getElementById('wc-fields');
            
            if (checkbox.checked) {
                fields.style.display = 'block';
            } else {
                fields.style.display = 'none';
            }
        }
        
        function jumpToStep(stepNumber) {
            // Allow jumping to any step
            // Create a form to submit the step change
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'jump_to_step';
            input.value = stepNumber;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Add click handlers to step elements
        document.addEventListener('DOMContentLoaded', function() {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                const stepNumber = index + 1;
                step.addEventListener('click', function() {
                    jumpToStep(stepNumber);
                });
            });
        });
    </script>
</body>
</html>