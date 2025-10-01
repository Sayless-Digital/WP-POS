<?php
/**
 * Step 6: Complete Installation
 */

$installationComplete = false;
$errors = [];
$steps = [];

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_installation'])) {
    set_time_limit(300); // 5 minutes
    
    // Step 1: Run migrations
    $steps[] = ['name' => 'Running database migrations...', 'status' => 'running'];
    $result = $installer->runArtisan('migrate --force');
    if ($result['success']) {
        $steps[count($steps) - 1]['status'] = 'success';
        $steps[count($steps) - 1]['message'] = 'Database tables created successfully';
    } else {
        $steps[count($steps) - 1]['status'] = 'error';
        $steps[count($steps) - 1]['message'] = $result['message'];
        $errors[] = 'Migration failed: ' . $result['message'];
    }
    
    // Step 2: Seed database
    if (empty($errors)) {
        $steps[] = ['name' => 'Seeding initial data...', 'status' => 'running'];
        $result = $installer->runArtisan('db:seed --force');
        if ($result['success']) {
            $steps[count($steps) - 1]['status'] = 'success';
            $steps[count($steps) - 1]['message'] = 'Initial data seeded successfully';
        } else {
            $steps[count($steps) - 1]['status'] = 'warning';
            $steps[count($steps) - 1]['message'] = 'Some seeders may have failed (this is usually okay)';
        }
    }
    
    // Step 3: Create admin user (manual SQL insert)
    if (empty($errors) && isset($_SESSION['install_data']['admin'])) {
        $steps[] = ['name' => 'Creating admin user...', 'status' => 'running'];
        
        try {
            $dbData = $_SESSION['install_data']['database'];
            $adminData = $_SESSION['install_data']['admin'];
            
            $pdo = new PDO(
                "mysql:host={$dbData['db_host']};port={$dbData['db_port']};dbname={$dbData['db_database']}",
                $dbData['db_username'],
                $dbData['db_password']
            );
            
            $hashedPassword = password_hash($adminData['password'], PASSWORD_BCRYPT);
            $now = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $adminData['name'],
                $adminData['email'],
                $hashedPassword,
                $now,
                $now,
                $now
            ]);
            
            $steps[count($steps) - 1]['status'] = 'success';
            $steps[count($steps) - 1]['message'] = 'Admin user created successfully';
        } catch (Exception $e) {
            $steps[count($steps) - 1]['status'] = 'error';
            $steps[count($steps) - 1]['message'] = $e->getMessage();
            $errors[] = 'Failed to create admin user: ' . $e->getMessage();
        }
    }
    
    // Step 3.5: Save WooCommerce configuration to .env
    if (empty($errors) && isset($_SESSION['install_data']['woocommerce'])) {
        $steps[] = ['name' => 'Configuring WooCommerce integration...', 'status' => 'running'];
        
        try {
            $wooData = $_SESSION['install_data']['woocommerce'];
            $envFile = dirname(__DIR__, 2) . '/.env';
            
            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                
                // Add WooCommerce configuration
                $wooConfig = "\n# WooCommerce Integration\n";
                $wooConfig .= "WOOCOMMERCE_URL=" . $wooData['url'] . "\n";
                $wooConfig .= "WOOCOMMERCE_CONSUMER_KEY=" . $wooData['consumer_key'] . "\n";
                $wooConfig .= "WOOCOMMERCE_CONSUMER_SECRET=" . $wooData['consumer_secret'] . "\n";
                $wooConfig .= "WOOCOMMERCE_SYNC_ENABLED=" . $wooData['sync_enabled'] . "\n";
                
                // Append to .env file
                file_put_contents($envFile, $envContent . $wooConfig);
                
                $steps[count($steps) - 1]['status'] = 'success';
                if (!empty($wooData['url'])) {
                    $steps[count($steps) - 1]['message'] = 'WooCommerce integration configured';
                } else {
                    $steps[count($steps) - 1]['message'] = 'WooCommerce integration skipped';
                }
            } else {
                $steps[count($steps) - 1]['status'] = 'warning';
                $steps[count($steps) - 1]['message'] = '.env file not found, skipping WooCommerce config';
            }
        } catch (Exception $e) {
            $steps[count($steps) - 1]['status'] = 'warning';
            $steps[count($steps) - 1]['message'] = 'WooCommerce config warning: ' . $e->getMessage();
        }
    }
    
    // Step 4: Create storage link
    if (empty($errors)) {
        $steps[] = ['name' => 'Creating storage link...', 'status' => 'running'];
        $result = $installer->runArtisan('storage:link');
        $steps[count($steps) - 1]['status'] = 'success';
        $steps[count($steps) - 1]['message'] = 'Storage link created';
    }
    
    // Step 5: Optimize application
    if (empty($errors)) {
        $steps[] = ['name' => 'Optimizing application...', 'status' => 'running'];
        $installer->runArtisan('config:cache');
        $installer->runArtisan('route:cache');
        $installer->runArtisan('view:cache');
        $steps[count($steps) - 1]['status'] = 'success';
        $steps[count($steps) - 1]['message'] = 'Application optimized';
    }
    
    // Mark installation as complete
    if (empty($errors)) {
        $installer->lockInstaller();
        $installationComplete = true;
        
        // Clear session
        session_destroy();
    }
}
?>

<h2 class="step-title">Complete Installation</h2>
<p class="step-description">Ready to finalize your WP-POS installation. This will set up the database and create your admin account.</p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>❌ Installation Failed</strong>
        <ul style="margin: 10px 0 0 20px;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($installationComplete): ?>
    <div class="alert alert-success">
        <strong>🎉 Installation Complete!</strong> Your WP-POS system is now ready to use.
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Your Login Credentials</h3>
        <p style="margin: 5px 0;"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['install_data']['admin']['email'] ?? 'N/A'); ?></p>
        <p style="margin: 5px 0;"><strong>Password:</strong> (the password you set)</p>
        <p style="margin: 15px 0 0 0; font-size: 14px; color: #6b7280;">
            <strong>⚠️ Important:</strong> Please save these credentials in a secure location.
        </p>
    </div>
    
    <div class="alert alert-info">
        <strong>🚀 Next Steps:</strong>
        <ol style="margin: 10px 0 0 20px;">
            <li>Log in to your admin panel</li>
            <li>Configure your store settings</li>
            <li>Add products and categories</li>
            <li>Set up WooCommerce integration (optional)</li>
            <li>Start processing sales!</li>
        </ol>
    </div>
    
    <div class="buttons">
        <div></div>
        <a href="../" class="btn btn-success" style="text-align: center;">
            🎯 Go to Dashboard
        </a>
    </div>
    
<?php elseif (!empty($steps)): ?>
    <h3 style="margin: 20px 0;">Installation Progress</h3>
    <ul class="requirement-list">
        <?php foreach ($steps as $step): ?>
            <li class="requirement-item <?php echo $step['status'] === 'success' ? 'success' : ($step['status'] === 'error' ? 'error' : 'warning'); ?>">
                <div>
                    <strong><?php echo htmlspecialchars($step['name']); ?></strong>
                    <?php if (isset($step['message'])): ?>
                        <br><small><?php echo htmlspecialchars($step['message']); ?></small>
                    <?php endif; ?>
                </div>
                <div class="status-icon">
                    <?php if ($step['status'] === 'success'): ?>
                        ✅
                    <?php elseif ($step['status'] === 'error'): ?>
                        ❌
                    <?php elseif ($step['status'] === 'running'): ?>
                        <div class="loading"></div>
                    <?php else: ?>
                        ⚠️
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <?php if (!empty($errors)): ?>
        <div class="buttons">
            <form method="POST" style="width: 100%;">
                <button type="submit" name="prev_step" value="5" class="btn btn-secondary">
                    ← Back to Fix Issues
                </button>
            </form>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Installation Summary</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>Application:</strong> <?php echo htmlspecialchars($_SESSION['install_data']['config']['app_name'] ?? 'WP-POS'); ?>
            </li>
            <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>URL:</strong> <?php echo htmlspecialchars($_SESSION['install_data']['config']['app_url'] ?? 'N/A'); ?>
            </li>
            <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>Database:</strong> <?php echo htmlspecialchars($_SESSION['install_data']['database']['db_database'] ?? 'N/A'); ?>
            </li>
            <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>Admin Email:</strong> <?php echo htmlspecialchars($_SESSION['install_data']['admin']['email'] ?? 'N/A'); ?>
            </li>
            <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>Environment:</strong> <?php echo htmlspecialchars($_SESSION['install_data']['config']['app_env'] ?? 'production'); ?>
            </li>
            <li style="padding: 8px 0;">
                <strong>WooCommerce:</strong>
                <?php
                $wooData = $_SESSION['install_data']['woocommerce'] ?? [];
                if (!empty($wooData['url'])) {
                    echo 'Configured (' . htmlspecialchars($wooData['url']) . ')';
                } else {
                    echo 'Not configured (can be set up later)';
                }
                ?>
            </li>
        </ul>
    </div>
    
    <div class="alert alert-info">
        <strong>⏱️ This may take a few minutes.</strong> Please do not close this window or refresh the page during installation.
    </div>
    
    <form method="POST" id="installForm">
        <button type="submit" name="run_installation" value="1" class="btn btn-success" style="width: 100%; font-size: 16px; padding: 15px;">
            🚀 Run Installation
        </button>
        
        <div class="buttons" style="margin-top: 20px;">
            <button type="submit" name="prev_step" value="5" class="btn btn-secondary">
                ← Back
            </button>
            <div></div>
        </div>
    </form>
    
    <script>
    document.getElementById('installForm').addEventListener('submit', function(e) {
        if (e.submitter.name === 'run_installation') {
            if (!confirm('Ready to install? This will create database tables and set up your admin account.')) {
                e.preventDefault();
            } else {
                e.submitter.disabled = true;
                e.submitter.innerHTML = '<div class="loading" style="display: inline-block; margin-right: 10px;"></div> Installing...';
            }
        }
    });
    </script>
<?php endif; ?>