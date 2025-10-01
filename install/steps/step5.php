<?php
/**
 * Step 5: WooCommerce Configuration (Optional)
 */

$error = null;
$success = null;
$testResult = null;

// Handle connection test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    $wooUrl = $_POST['woo_url'] ?? '';
    $wooConsumerKey = $_POST['woo_consumer_key'] ?? '';
    $wooConsumerSecret = $_POST['woo_consumer_secret'] ?? '';
    
    if (empty($wooUrl) || empty($wooConsumerKey) || empty($wooConsumerSecret)) {
        $error = 'Please fill in all WooCommerce credentials to test the connection';
    } else {
        // Validate URL format
        if (!filter_var($wooUrl, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid WooCommerce store URL';
        } else {
            // Test the connection
            $testResult = $installer->testWooCommerceConnection($wooUrl, $wooConsumerKey, $wooConsumerSecret);
            
            if ($testResult['success']) {
                $success = 'Connection successful! Your WooCommerce credentials are valid.';
                $_SESSION['woocommerce_tested'] = true;
            } else {
                $error = 'Connection failed: ' . $testResult['message'];
                $_SESSION['woocommerce_tested'] = false;
            }
        }
    }
}

// Handle form submission for saving data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_woocommerce'])) {
    $wooUrl = $_POST['woo_url'] ?? '';
    $wooConsumerKey = $_POST['woo_consumer_key'] ?? '';
    $wooConsumerSecret = $_POST['woo_consumer_secret'] ?? '';
    $wooSyncEnabled = isset($_POST['woo_sync_enabled']) ? '1' : '0';
    
    // Validation
    if (empty($wooUrl) || empty($wooConsumerKey) || empty($wooConsumerSecret)) {
        $error = 'All WooCommerce fields are required';
    } elseif (!filter_var($wooUrl, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid WooCommerce store URL';
    } elseif (!isset($_SESSION['woocommerce_tested']) || !$_SESSION['woocommerce_tested']) {
        $error = 'Please test the connection first before saving';
    } else {
        // Save WooCommerce data for final step
        $_SESSION['install_data']['woocommerce'] = [
            'url' => rtrim($wooUrl, '/'), // Remove trailing slash
            'consumer_key' => $wooConsumerKey,
            'consumer_secret' => $wooConsumerSecret,
            'sync_enabled' => $wooSyncEnabled,
        ];
        $success = 'WooCommerce configuration saved successfully!';
    }
}

// Handle skip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skip_woocommerce'])) {
    $_SESSION['install_data']['woocommerce'] = [
        'url' => '',
        'consumer_key' => '',
        'consumer_secret' => '',
        'sync_enabled' => '0',
    ];
    $_SESSION['woocommerce_skipped'] = true;
}

// Get saved values
$savedData = $_SESSION['install_data']['woocommerce'] ?? [];
$wasSkipped = $_SESSION['woocommerce_skipped'] ?? false;
?>

<h2 class="step-title">WooCommerce Integration (Optional)</h2>
<p class="step-description">Connect your WP-POS system with WooCommerce to synchronize products, inventory, and orders. You can skip this step and configure it later.</p>

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

<?php if ($testResult && $testResult['success']): ?>
    <div class="alert alert-success">
        <strong>✅ Connection Test Successful!</strong><br>
        <?php echo htmlspecialchars($testResult['message']); ?>
    </div>
<?php endif; ?>

<?php if ($wasSkipped): ?>
    <div class="alert alert-info">
        <strong>ℹ️ WooCommerce Setup Skipped:</strong> You can configure WooCommerce integration later from the Settings page.
    </div>
<?php endif; ?>

<form method="POST" id="wooForm">
    <div class="alert alert-info" style="margin-bottom: 25px;">
        <strong>📋 How to get your WooCommerce credentials:</strong><br>
        1. Log in to your WordPress admin panel<br>
        2. Go to <strong>WooCommerce → Settings → Advanced → REST API</strong><br>
        3. Click <strong>"Add Key"</strong> and create new API credentials<br>
        4. Set permissions to <strong>"Read/Write"</strong><br>
        5. Copy the Consumer Key and Consumer Secret here
    </div>

    <div class="form-group">
        <label for="woo_url">WooCommerce Store URL *</label>
        <input type="url" id="woo_url" name="woo_url" 
               value="<?php echo htmlspecialchars($savedData['url'] ?? ''); ?>" 
               placeholder="https://yourstore.com">
        <small>Your WooCommerce store URL (e.g., https://example.com)</small>
    </div>

    <div class="form-group">
        <label for="woo_consumer_key">Consumer Key *</label>
        <input type="text" id="woo_consumer_key" name="woo_consumer_key" 
               value="<?php echo htmlspecialchars($savedData['consumer_key'] ?? ''); ?>" 
               placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
        <small>Your WooCommerce REST API Consumer Key</small>
    </div>

    <div class="form-group">
        <label for="woo_consumer_secret">Consumer Secret *</label>
        <input type="text" id="woo_consumer_secret" name="woo_consumer_secret" 
               value="<?php echo htmlspecialchars($savedData['consumer_secret'] ?? ''); ?>" 
               placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
        <small>Your WooCommerce REST API Consumer Secret</small>
    </div>

    <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
        <input type="checkbox" id="woo_sync_enabled" name="woo_sync_enabled" 
               <?php echo (!isset($savedData['sync_enabled']) || $savedData['sync_enabled'] === '1') ? 'checked' : ''; ?> 
               style="width: auto; margin: 0;">
        <label for="woo_sync_enabled" style="margin: 0;">Enable automatic synchronization</label>
    </div>
    <small style="display: block; margin: -10px 0 20px 0; color: #6b7280;">
        Products and inventory will sync automatically between WP-POS and WooCommerce
    </small>

    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <button type="submit" name="test_connection" value="1" class="btn btn-secondary" style="flex: 1;">
            🔍 Test Connection
        </button>
        <button type="submit" name="save_woocommerce" value="1" class="btn btn-primary" style="flex: 1;">
            💾 Save Configuration
        </button>
    </div>

    <div class="alert alert-info">
        <strong>💡 Note:</strong> Testing the connection is required before saving. If you don't have WooCommerce credentials yet, you can skip this step and configure it later from the Settings page.
    </div>

    <button type="submit" name="skip_woocommerce" value="1" class="btn btn-secondary" style="width: 100%; margin-bottom: 20px;">
        ⏭️ Skip WooCommerce Setup
    </button>

    <div class="buttons">
        <button type="submit" name="prev_step" value="4" class="btn btn-secondary">
            ← Back
        </button>
        <button type="submit" name="next_step" value="6" class="btn btn-primary" 
                <?php echo (!$success && !$wasSkipped) ? 'disabled' : ''; ?>>
            Next: Complete Installation →
        </button>
    </div>
</form>

<script>
document.getElementById('wooForm').addEventListener('submit', function(e) {
    const submitter = e.submitter;
    
    // Validate next step
    if (submitter.name === 'next_step') {
        const hasSuccess = <?php echo $success ? 'true' : 'false'; ?>;
        const wasSkipped = <?php echo $wasSkipped ? 'true' : 'false'; ?>;
        
        if (!hasSuccess && !wasSkipped) {
            e.preventDefault();
            alert('Please save the WooCommerce configuration or skip this step!');
        }
    }
    
    // Validate test connection
    if (submitter.name === 'test_connection') {
        const url = document.getElementById('woo_url').value.trim();
        const key = document.getElementById('woo_consumer_key').value.trim();
        const secret = document.getElementById('woo_consumer_secret').value.trim();
        
        if (!url || !key || !secret) {
            e.preventDefault();
            alert('Please fill in all WooCommerce credentials before testing!');
        }
    }
    
    // Validate save
    if (submitter.name === 'save_woocommerce') {
        const tested = <?php echo isset($_SESSION['woocommerce_tested']) && $_SESSION['woocommerce_tested'] ? 'true' : 'false'; ?>;
        
        if (!tested) {
            e.preventDefault();
            alert('Please test the connection first!');
        }
    }
    
    // Show loading state for test and save buttons
    if (submitter.name === 'test_connection' || submitter.name === 'save_woocommerce') {
        submitter.disabled = true;
        const originalText = submitter.innerHTML;
        submitter.innerHTML = '<div class="loading" style="display: inline-block; margin-right: 5px;"></div> ' + 
                             (submitter.name === 'test_connection' ? 'Testing...' : 'Saving...');
        
        // Re-enable after a timeout if form doesn't submit
        setTimeout(() => {
            submitter.disabled = false;
            submitter.innerHTML = originalText;
        }, 5000);
    }
});
</script>