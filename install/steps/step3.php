<?php
/**
 * Step 3: Application Configuration
 */

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = $_POST['app_name'] ?? 'WP-POS';
    $appUrl = $_POST['app_url'] ?? '';
    $appEnv = $_POST['app_env'] ?? 'production';
    $appDebug = $_POST['app_debug'] ?? 'false';
    
    // Always save the data first
    $_SESSION['install_data']['config'] = [
        'app_name' => $appName,
        'app_url' => rtrim($appUrl, '/'),
        'app_env' => $appEnv,
        'app_debug' => $appDebug,
    ];
    
    // Validate URL
    if (empty($appUrl) || !filter_var($appUrl, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid application URL';
    } else {
        // Merge with database config
        $configData = array_merge(
            $_SESSION['install_data']['database'] ?? [],
            $_SESSION['install_data']['config']
        );
        
        // Create .env file
        $result = $installer->createEnvFile($configData);
        
        if ($result['success']) {
            // Generate app key
            $keyResult = $installer->generateAppKey();
            
            if ($keyResult['success']) {
                $success = 'Configuration saved successfully!';
                $_SESSION['config_saved'] = true;
            } else {
                $error = 'Failed to generate application key: ' . $keyResult['message'];
                $_SESSION['config_saved'] = false;
            }
        } else {
            $error = $result['message'];
            $_SESSION['config_saved'] = false;
        }
    }
    
    // Proceed to next step if config is saved successfully
    if (isset($_POST['next_step']) && isset($_SESSION['config_saved']) && $_SESSION['config_saved']) {
        $_SESSION['install_step'] = 4;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get saved values or detect current URL
$savedData = $_SESSION['install_data']['config'] ?? [];
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$currentUrl = str_replace('/install', '', $currentUrl);
?>

<h2 class="step-title">Application Configuration</h2>
<p class="step-description">Configure your WP-POS application settings.</p>

<?php if ($error): ?>
    <div class="alert alert-error">
        <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>✅ Success:</strong> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<form method="POST" id="configForm">
    <div class="form-group">
        <label for="app_name">Application Name *</label>
        <input type="text" id="app_name" name="app_name" value="<?php echo htmlspecialchars($savedData['app_name'] ?? 'WP-POS'); ?>" required>
        <small>The name of your POS system</small>
    </div>

    <div class="form-group">
        <label for="app_url">Application URL *</label>
        <input type="url" id="app_url" name="app_url" value="<?php echo htmlspecialchars($savedData['app_url'] ?? $currentUrl); ?>" required>
        <small>The full URL where your POS will be accessible (e.g., https://pos.yourstore.com)</small>
    </div>

    <div class="form-group">
        <label for="app_env">Environment *</label>
        <select id="app_env" name="app_env" required>
            <option value="production" <?php echo ($savedData['app_env'] ?? 'production') === 'production' ? 'selected' : ''; ?>>Production</option>
            <option value="local" <?php echo ($savedData['app_env'] ?? '') === 'local' ? 'selected' : ''; ?>>Local/Development</option>
        </select>
        <small>Select 'Production' for live server, 'Local' for development</small>
    </div>

    <div class="form-group">
        <label for="app_debug">Debug Mode *</label>
        <select id="app_debug" name="app_debug" required>
            <option value="false" <?php echo ($savedData['app_debug'] ?? 'false') === 'false' ? 'selected' : ''; ?>>Disabled (Recommended)</option>
            <option value="true" <?php echo ($savedData['app_debug'] ?? '') === 'true' ? 'selected' : ''; ?>>Enabled</option>
        </select>
        <small>Keep disabled in production for security</small>
    </div>

    <div class="alert alert-info">
        <strong>ℹ️ Note:</strong> These settings will be saved to your .env file. You can change them later by editing the .env file directly.
    </div>

    <div class="buttons">
        <button type="submit" name="prev_step" value="2" class="btn btn-secondary">
            ← Back
        </button>
        <button type="submit" name="next_step" value="4" class="btn btn-primary" <?php echo !isset($_SESSION['config_saved']) || !$_SESSION['config_saved'] ? 'disabled' : ''; ?>>
            Next: Admin Account →
        </button>
    </div>
</form>

<script>
document.getElementById('configForm').addEventListener('submit', function(e) {
    const nextStep = e.submitter.name === 'next_step';
    if (nextStep && !<?php echo isset($_SESSION['config_saved']) && $_SESSION['config_saved'] ? 'true' : 'false'; ?>) {
        e.preventDefault();
        alert('Please fill in all required fields and try again!');
    }
});
</script>