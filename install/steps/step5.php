<?php
/**
 * Step 5: WooCommerce Configuration (Optional)
 */

$error = null;
$success = null;
$testResult = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wooUrl = $_POST['woo_url'] ?? '';
    $wooConsumerKey = $_POST['woo_consumer_key'] ?? '';
    $wooConsumerSecret = $_POST['woo_consumer_secret'] ?? '';
    $wooSyncEnabled = isset($_POST['woo_sync_enabled']) ? '1' : '0';
    
    // Always save the data first
    $_SESSION['install_data']['woocommerce'] = [
        'url' => rtrim($wooUrl, '/'), // Remove trailing slash
        'consumer_key' => $wooConsumerKey,
        'consumer_secret' => $wooConsumerSecret,
        'sync_enabled' => $wooSyncEnabled,
    ];
    
    // Handle connection test
    if (isset($_POST['test_connection'])) {
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
    
    // Handle skip
    if (isset($_POST['skip_woocommerce'])) {
        $_SESSION['install_data']['woocommerce'] = [
            'url' => '',
            'consumer_key' => '',
            'consumer_secret' => '',
            'sync_enabled' => '0',
        ];
        $_SESSION['woocommerce_skipped'] = true;
        $_SESSION['woocommerce_tested'] = true; // Mark as "tested" so we can proceed
    }
    
    // Handle back navigation
    if (isset($_POST['prev_step'])) {
        $_SESSION['install_step'] = 4;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Proceed to next step if WooCommerce is configured or skipped
    if (isset($_POST['next_step']) && (isset($_SESSION['woocommerce_tested']) && $_SESSION['woocommerce_tested'])) {
        $_SESSION['install_step'] = 6;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
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
        <button type="submit" name="skip_woocommerce" value="1" class="btn btn-secondary" style="flex: 1;">
            ⏭️ Skip Setup
        </button>
    </div>

    <div class="alert alert-info">
        <strong>💡 Note:</strong> Test the connection to verify your credentials, or skip this step to configure WooCommerce later from the Settings page.
    </div>

    <div class="buttons">
        <button type="submit" name="prev_step" value="4" class="btn btn-secondary">
            ← Back
        </button>
        <button type="submit" name="next_step" value="6" class="btn btn-primary" 
                <?php echo !isset($_SESSION['woocommerce_tested']) || !$_SESSION['woocommerce_tested'] ? 'disabled' : ''; ?>>
            Next: Complete Installation →
        </button>
    </div>
</form>

<script>
document.getElementById('wooForm').addEventListener('submit', function(e) {
    const submitter = e.submitter;
    const nextStep = submitter.name === 'next_step';
    const backStep = submitter.name === 'prev_step';
    
    // Only validate for next step, not back step
    if (nextStep) {
        const tested = <?php echo isset($_SESSION['woocommerce_tested']) && $_SESSION['woocommerce_tested'] ? 'true' : 'false'; ?>;
        
        if (!tested) {
            e.preventDefault();
            alert('Please test the WooCommerce connection or skip this step!');
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
    
    // Show loading state for test button
    if (submitter.name === 'test_connection') {
        submitter.disabled = true;
        const originalText = submitter.innerHTML;
        submitter.innerHTML = '<div class="loading" style="display: inline-block; margin-right: 5px;"></div> Testing...';
        
        // Re-enable after a timeout if form doesn't submit
        setTimeout(() => {
            submitter.disabled = false;
            submitter.innerHTML = originalText;
        }, 5000);
    }
});
</script>