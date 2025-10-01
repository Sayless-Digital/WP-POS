<?php
/**
 * Step 3: Application Configuration
 */

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = $_POST['app_name'] ?? 'WP-POS';
    $appEnv = $_POST['app_env'] ?? 'production';
    $appDebug = $_POST['app_debug'] ?? 'false';
    $appUrl = $_POST['app_url'] ?? '';
    
    // Validate URL
    if (!empty($appUrl)) {
        if (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid application URL';
        } else {
            // Ensure URL doesn't end with slash
            $appUrl = rtrim($appUrl, '/');
        }
    }
    
    if (!$error) {
        // Save configuration data
        $_SESSION['install_data']['configuration'] = [
            'app_name' => $appName,
            'app_env' => $appEnv,
            'app_debug' => $appDebug,
            'app_url' => $appUrl,
        ];
        
        $success = 'Configuration saved successfully!';
    }
    
    // Handle back navigation
    if (isset($_POST['prev_step'])) {
        $_SESSION['install_step'] = 2;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Proceed to next step
    if (isset($_POST['next_step']) && !$error) {
        $_SESSION['install_step'] = 4;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get saved values
$savedData = $_SESSION['install_data']['configuration'] ?? [];

// Auto-detect application URL if not set
if (empty($savedData['app_url'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['REQUEST_URI']);
    $savedData['app_url'] = $protocol . '://' . $host . str_replace('/install', '', $path);
}
?>

<div class="step-content fade-in">
    <h2 class="step-title">Application Configuration</h2>
    <p class="step-description">Configure your WP-POS application settings.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <div class="alert-title">Configuration Error</div>
                <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <div class="alert-icon">✅</div>
            <div class="alert-content">
                <div class="alert-title">Configuration Saved</div>
                <div class="alert-message"><?php echo htmlspecialchars($success); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" class="installer-form">
        <div class="form-section">
            <h3 class="section-title">Basic Settings</h3>
            
            <div class="form-group">
                <label for="app_name">Application Name *</label>
                <input type="text" id="app_name" name="app_name" 
                       value="<?php echo htmlspecialchars($savedData['app_name'] ?? 'WP-POS'); ?>" 
                       required data-tooltip="This will appear in browser tabs and emails">
                <small>The name of your POS system</small>
            </div>

            <div class="form-group">
                <label for="app_url">Application URL *</label>
                <input type="url" id="app_url" name="app_url" 
                       value="<?php echo htmlspecialchars($savedData['app_url'] ?? ''); ?>" 
                       required data-tooltip="The full URL where your application will be accessible">
                <small>The full URL where your POS system will be accessible</small>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Environment Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="app_env">Environment *</label>
                    <select id="app_env" name="app_env" required data-tooltip="Choose the environment for your application">
                        <option value="production" <?php echo ($savedData['app_env'] ?? 'production') === 'production' ? 'selected' : ''; ?>>Production</option>
                        <option value="staging" <?php echo ($savedData['app_env'] ?? '') === 'staging' ? 'selected' : ''; ?>>Staging</option>
                        <option value="local" <?php echo ($savedData['app_env'] ?? '') === 'local' ? 'selected' : ''; ?>>Local Development</option>
                    </select>
                    <small>Environment affects error reporting and caching</small>
                </div>

                <div class="form-group">
                    <label for="app_debug">Debug Mode *</label>
                    <select id="app_debug" name="app_debug" required data-tooltip="Enable detailed error messages (not recommended for production)">
                        <option value="false" <?php echo ($savedData['app_debug'] ?? 'false') === 'false' ? 'selected' : ''; ?>>Disabled (Recommended)</option>
                        <option value="true" <?php echo ($savedData['app_debug'] ?? '') === 'true' ? 'selected' : ''; ?>>Enabled</option>
                    </select>
                    <small>Show detailed error messages</small>
                </div>
            </div>
        </div>

        <div class="configuration-info">
            <h4>Configuration Details</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Production Environment</strong>
                    <p>Optimized for live use with error logging and caching enabled.</p>
                </div>
                <div class="info-item">
                    <strong>Debug Mode</strong>
                    <p>Disabled by default for security. Only enable for troubleshooting.</p>
                </div>
                <div class="info-item">
                    <strong>Application URL</strong>
                    <p>Used for generating links and API endpoints in your application.</p>
                </div>
                <div class="info-item">
                    <strong>Environment Variables</strong>
                    <p>These settings will be saved to your .env configuration file.</p>
                </div>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" name="prev_step" value="2" class="btn btn-secondary">
                ← Back
            </button>
            <button type="submit" name="next_step" value="4" class="btn btn-primary">
                Next: Admin Account →
            </button>
        </div>
    </form>
</div>

<style>
.form-section {
    margin-bottom: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.configuration-info {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.configuration-info h4 {
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.info-item strong {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--gray-800);
}

.info-item p {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin: 0;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
