<?php
/**
 * Step 6: Complete Installation
 */

$error = null;
$success = null;
$installationComplete = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle back navigation
    if (isset($_POST['prev_step'])) {
        $_SESSION['install_step'] = 5;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Start installation process
    if (isset($_POST['start_installation'])) {
        try {
            $installer->clearMessages();
            
            // Step 1: Create .env file
            $envData = array_merge(
                $_SESSION['install_data']['database'] ?? [],
                $_SESSION['install_data']['configuration'] ?? []
            );
            
            $result = $installer->createEnvFile($envData);
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            $installer->addSuccess('Environment file created');
            
            // Step 2: Generate application key
            $result = $installer->generateAppKey();
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            $installer->addSuccess('Application key generated');
            
            // Step 3: Run database migrations
            $result = $installer->runArtisan('migrate --force');
            if (!$result['success']) {
                throw new Exception('Migration failed: ' . $result['message']);
            }
            $installer->addSuccess('Database migrations completed');
            
            // Step 4: Seed database
            $result = $installer->runArtisan('db:seed --force');
            if (!$result['success']) {
                throw new Exception('Database seeding failed: ' . $result['message']);
            }
            $installer->addSuccess('Database seeded with initial data');
            
            // Step 5: Create admin user
            $adminData = $_SESSION['install_data']['admin'] ?? [];
            if (!empty($adminData)) {
                $result = $installer->createAdminUser(
                    $adminData['admin_name'],
                    $adminData['admin_email'],
                    $adminData['admin_password']
                );
                if (!$result['success']) {
                    throw new Exception('Admin user creation failed: ' . $result['message']);
                }
                $installer->addSuccess('Admin user created successfully');
            }
            
            // Step 6: Create storage link
            $result = $installer->runArtisan('storage:link');
            if (!$result['success']) {
                $installer->addWarning('Storage link creation failed: ' . $result['message']);
            } else {
                $installer->addSuccess('Storage link created');
            }
            
            // Step 7: Optimize application
            $optimizeCommands = [
                'config:cache' => 'Configuration cached',
                'route:cache' => 'Routes cached',
                'view:cache' => 'Views cached'
            ];
            
            foreach ($optimizeCommands as $command => $message) {
                $result = $installer->runArtisan($command);
                if ($result['success']) {
                    $installer->addSuccess($message);
                } else {
                    $installer->addWarning($message . ' failed: ' . $result['message']);
                }
            }
            
            // Step 8: Lock installer
            if ($installer->lockInstaller()) {
                $installer->addSuccess('Installer locked successfully');
            } else {
                $installer->addWarning('Could not lock installer');
            }
            
            $installationComplete = true;
            $success = 'Installation completed successfully!';
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            $installer->addError($error);
        }
    }
}

// Get installation data for review
$databaseData = $_SESSION['install_data']['database'] ?? [];
$configData = $_SESSION['install_data']['configuration'] ?? [];
$adminData = $_SESSION['install_data']['admin'] ?? [];
$wcData = $_SESSION['install_data']['woocommerce'] ?? [];

$messages = $installer->getMessages();
?>

<div class="step-content fade-in">
    <h2 class="step-title">Complete Installation</h2>
    <p class="step-description">Review your settings and complete the WP-POS installation.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <div class="alert-title">Installation Failed</div>
                <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <div class="alert-icon">✅</div>
            <div class="alert-content">
                <div class="alert-title">Installation Complete!</div>
                <div class="alert-message"><?php echo htmlspecialchars($success); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($messages['errors'])): ?>
        <div class="alert alert-error">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <div class="alert-title">Installation Errors</div>
                <div class="alert-message">
                    <ul style="margin: 0; padding-left: 1rem;">
                        <?php foreach ($messages['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($messages['warnings'])): ?>
        <div class="alert alert-warning">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <div class="alert-title">Installation Warnings</div>
                <div class="alert-message">
                    <ul style="margin: 0; padding-left: 1rem;">
                        <?php foreach ($messages['warnings'] as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($messages['success'])): ?>
        <div class="alert alert-success">
            <div class="alert-icon">✅</div>
            <div class="alert-content">
                <div class="alert-title">Installation Progress</div>
                <div class="alert-message">
                    <ul style="margin: 0; padding-left: 1rem;">
                        <?php foreach ($messages['success'] as $success): ?>
                            <li><?php echo htmlspecialchars($success); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$installationComplete): ?>
        <!-- Installation Review -->
        <div class="installation-review">
            <h3 class="section-title">Installation Summary</h3>
            
            <div class="review-sections">
                <div class="review-section">
                    <h4>Database Configuration</h4>
                    <div class="review-details">
                        <div class="detail-item">
                            <span class="detail-label">Host:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($databaseData['db_host'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Port:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($databaseData['db_port'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Database:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($databaseData['db_database'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Username:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($databaseData['db_username'] ?? 'Not set'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="review-section">
                    <h4>Application Settings</h4>
                    <div class="review-details">
                        <div class="detail-item">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($configData['app_name'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">URL:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($configData['app_url'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Environment:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($configData['app_env'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Debug Mode:</span>
                            <span class="detail-value"><?php echo ($configData['app_debug'] ?? 'false') === 'true' ? 'Enabled' : 'Disabled'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="review-section">
                    <h4>Admin Account</h4>
                    <div class="review-details">
                        <div class="detail-item">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($adminData['admin_name'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($adminData['admin_email'] ?? 'Not set'); ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($wcData) && $wcData['wc_enabled'] === 'true'): ?>
                <div class="review-section">
                    <h4>WooCommerce Integration</h4>
                    <div class="review-details">
                        <div class="detail-item">
                            <span class="detail-label">Store URL:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($wcData['wc_url'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Consumer Key:</span>
                            <span class="detail-value"><?php echo htmlspecialchars(substr($wcData['wc_consumer_key'] ?? '', 0, 8) . '...'); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" class="installer-form">
            <div class="button-group">
                <button type="submit" name="prev_step" value="5" class="btn btn-secondary">
                    ← Back
                </button>
                <button type="submit" name="start_installation" value="1" class="btn btn-success" id="installBtn">
                    🚀 Start Installation
                </button>
            </div>
        </form>

    <?php else: ?>
        <!-- Installation Complete -->
        <div class="installation-complete">
            <div class="complete-icon">🎉</div>
            <h3>Installation Complete!</h3>
            <p>Your WP-POS system has been successfully installed and configured.</p>
            
            <div class="next-steps">
                <h4>What's Next?</h4>
                <div class="steps-grid">
                    <div class="step-item">
                        <div class="step-icon">🔐</div>
                        <div class="step-content">
                            <strong>Login to Admin Panel</strong>
                            <p>Use your admin credentials to access the system</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon">⚙️</div>
                        <div class="step-content">
                            <strong>Configure Settings</strong>
                            <p>Set up your store details and preferences</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon">📦</div>
                        <div class="step-content">
                            <strong>Add Products</strong>
                            <p>Start adding your inventory items</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon">👥</div>
                        <div class="step-content">
                            <strong>Create Users</strong>
                            <p>Add staff members and set permissions</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="access-info">
                <h4>Access Your System</h4>
                <div class="access-buttons">
                    <a href="../" class="btn btn-primary">
                        🏠 Go to Application
                    </a>
                    <a href="../login" class="btn btn-secondary">
                        🔐 Admin Login
                    </a>
                </div>
            </div>

            <div class="security-notice">
                <h4>Security Recommendations</h4>
                <ul>
                    <li>Delete the installer directory for security</li>
                    <li>Set proper file permissions on your server</li>
                    <li>Enable HTTPS in production</li>
                    <li>Regularly backup your database</li>
                    <li>Keep your system updated</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.installation-review {
    background: var(--gray-50);
    padding: 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.review-sections {
    display: grid;
    gap: 1.5rem;
}

.review-section {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.review-section h4 {
    margin-bottom: 1rem;
    color: var(--gray-800);
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 0.5rem;
}

.review-details {
    display: grid;
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: var(--gray-600);
}

.detail-value {
    color: var(--gray-800);
    font-family: monospace;
    font-size: 0.9rem;
}

.installation-complete {
    text-align: center;
    padding: 2rem;
}

.complete-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.installation-complete h3 {
    font-size: 2rem;
    color: var(--success-color);
    margin-bottom: 1rem;
}

.installation-complete p {
    font-size: 1.1rem;
    color: var(--gray-600);
    margin-bottom: 2rem;
}

.next-steps {
    background: var(--gray-50);
    padding: 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    text-align: left;
}

.next-steps h4 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: var(--gray-800);
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.step-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.step-content strong {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--gray-800);
}

.step-content p {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin: 0;
}

.access-info {
    background: #dbeafe;
    padding: 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    border-left: 4px solid var(--info-color);
}

.access-info h4 {
    margin-bottom: 1rem;
    color: #1e40af;
}

.access-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.security-notice {
    background: #fef3c7;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    border-left: 4px solid var(--warning-color);
    text-align: left;
}

.security-notice h4 {
    margin-bottom: 1rem;
    color: #92400e;
}

.security-notice ul {
    margin: 0;
    padding-left: 1.5rem;
}

.security-notice li {
    margin-bottom: 0.5rem;
    color: #92400e;
}

@media (max-width: 768px) {
    .steps-grid {
        grid-template-columns: 1fr;
    }
    
    .access-buttons {
        flex-direction: column;
    }
    
    .review-sections {
        gap: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const installBtn = document.getElementById('installBtn');
    
    if (installBtn) {
        installBtn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to start the installation? This process cannot be undone.')) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            installBtn.disabled = true;
            installBtn.innerHTML = '<span class="loading-spinner"></span> Installing...';
            
            // Add progress indicator
            const progressDiv = document.createElement('div');
            progressDiv.className = 'alert alert-info';
            progressDiv.innerHTML = `
                <div class="alert-icon">⏳</div>
                <div class="alert-content">
                    <div class="alert-title">Installation in Progress</div>
                    <div class="alert-message">Please wait while we set up your WP-POS system...</div>
                </div>
            `;
            
            const content = document.querySelector('.step-content');
            content.insertBefore(progressDiv, content.firstChild);
        });
    }
});
</script>
