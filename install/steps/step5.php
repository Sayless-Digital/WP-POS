<?php
/**
 * Step 5: WooCommerce Integration (Optional)
 */

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wcEnabled = isset($_POST['wc_enabled']) ? 'true' : 'false';
    $wcUrl = trim($_POST['wc_url'] ?? '');
    $wcConsumerKey = trim($_POST['wc_consumer_key'] ?? '');
    $wcConsumerSecret = trim($_POST['wc_consumer_secret'] ?? '');
    
    // If WooCommerce is enabled, validate the fields
    if ($wcEnabled === 'true') {
        if (empty($wcUrl)) {
            $error = 'WooCommerce URL is required when integration is enabled';
        } elseif (!filter_var($wcUrl, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid WooCommerce URL';
        } elseif (empty($wcConsumerKey)) {
            $error = 'Consumer Key is required when integration is enabled';
        } elseif (empty($wcConsumerSecret)) {
            $error = 'Consumer Secret is required when integration is enabled';
        }
    }
    
    // Test connection if WooCommerce is enabled and no validation errors
    if ($wcEnabled === 'true' && !$error && isset($_POST['test_wc_connection'])) {
        $result = $installer->testWooCommerceConnection($wcUrl, $wcConsumerKey, $wcConsumerSecret);
        
        if ($result['success']) {
            $success = $result['message'];
            $_SESSION['wc_tested'] = true;
            $_SESSION['wc_details'] = $result['details'] ?? [];
        } else {
            $error = $result['message'];
            $_SESSION['wc_tested'] = false;
        }
    }
    
    if (!$error) {
        // Save WooCommerce data
        $_SESSION['install_data']['woocommerce'] = [
            'wc_enabled' => $wcEnabled,
            'wc_url' => $wcUrl,
            'wc_consumer_key' => $wcConsumerKey,
            'wc_consumer_secret' => $wcConsumerSecret,
        ];
        
        if (!isset($_POST['test_wc_connection'])) {
            $success = 'WooCommerce settings saved successfully!';
        }
    }
    
    // Handle back navigation
    if (isset($_POST['prev_step'])) {
        $_SESSION['install_step'] = 4;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Proceed to next step
    if (isset($_POST['next_step']) && !$error) {
        $_SESSION['install_step'] = 6;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get saved values
$savedData = $_SESSION['install_data']['woocommerce'] ?? [];
$wcEnabled = $savedData['wc_enabled'] ?? 'false';
?>

<div class="step-content fade-in">
    <h2 class="step-title">WooCommerce Integration</h2>
    <p class="step-description">Connect your WP-POS system with WooCommerce for seamless online and offline sales management.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <div class="alert-title">Integration Error</div>
                <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <div class="alert-icon">✅</div>
            <div class="alert-content">
                <div class="alert-title">Connection Successful</div>
                <div class="alert-message"><?php echo htmlspecialchars($success); ?></div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['wc_details']) && !empty($_SESSION['wc_details'])): ?>
            <div class="connection-details">
                <h4>Store Details</h4>
                <div class="details-grid">
                    <?php foreach ($_SESSION['wc_details'] as $key => $value): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($value); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" class="installer-form" id="woocommerceForm">
        <div class="form-section">
            <h3 class="section-title">Integration Settings</h3>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="wc_enabled" name="wc_enabled" 
                           <?php echo $wcEnabled === 'true' ? 'checked' : ''; ?>
                           onchange="toggleWooCommerceFields()">
                    <span class="checkmark"></span>
                    Enable WooCommerce Integration
                </label>
                <small>Connect your POS system with your WooCommerce store</small>
            </div>
        </div>

        <div id="woocommerce-fields" class="form-section" style="<?php echo $wcEnabled === 'true' ? '' : 'display: none;'; ?>">
            <h3 class="section-title">WooCommerce API Settings</h3>
            
            <div class="form-group">
                <label for="wc_url">WooCommerce Store URL *</label>
                <input type="url" id="wc_url" name="wc_url" 
                       value="<?php echo htmlspecialchars($savedData['wc_url'] ?? ''); ?>" 
                       data-tooltip="The full URL of your WooCommerce store">
                <small>Example: https://yourstore.com</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="wc_consumer_key">Consumer Key *</label>
                    <input type="text" id="wc_consumer_key" name="wc_consumer_key" 
                           value="<?php echo htmlspecialchars($savedData['wc_consumer_key'] ?? ''); ?>" 
                           data-tooltip="WooCommerce REST API Consumer Key">
                    <small>From WooCommerce > Settings > Advanced > REST API</small>
                </div>

                <div class="form-group">
                    <label for="wc_consumer_secret">Consumer Secret *</label>
                    <div class="password-input">
                        <input type="password" id="wc_consumer_secret" name="wc_consumer_secret" 
                               value="<?php echo htmlspecialchars($savedData['wc_consumer_secret'] ?? ''); ?>" 
                               data-tooltip="WooCommerce REST API Consumer Secret">
                        <button type="button" class="show-password" tabindex="-1">Show</button>
                    </div>
                    <small>Keep this secret secure</small>
                </div>
            </div>

            <div class="test-section">
                <button type="submit" name="test_wc_connection" value="1" class="btn btn-secondary test-connection" data-original-text="🔍 Test WooCommerce Connection">
                    🔍 Test WooCommerce Connection
                </button>
            </div>
        </div>

        <div class="woocommerce-info">
            <h4>WooCommerce Integration Benefits</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Synchronized Inventory</strong>
                    <p>Keep your online and offline inventory in sync automatically.</p>
                </div>
                <div class="info-item">
                    <strong>Unified Sales Reports</strong>
                    <p>View combined sales data from both online and offline channels.</p>
                </div>
                <div class="info-item">
                    <strong>Customer Management</strong>
                    <p>Access customer data from both your store and POS system.</p>
                </div>
                <div class="info-item">
                    <strong>Order Management</strong>
                    <p>Process online orders through your POS system.</p>
                </div>
            </div>
        </div>

        <div class="api-setup-guide">
            <h4>How to Get WooCommerce API Credentials</h4>
            <div class="guide-steps">
                <div class="guide-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <strong>Go to WooCommerce Settings</strong>
                        <p>Navigate to WooCommerce > Settings > Advanced > REST API in your WordPress admin.</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <strong>Create API Key</strong>
                        <p>Click "Add Key" and give it a description like "WP-POS Integration".</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <strong>Set Permissions</strong>
                        <p>Select "Read/Write" permissions for full integration capabilities.</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <strong>Copy Credentials</strong>
                        <p>Copy the Consumer Key and Consumer Secret to use in this installer.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" name="prev_step" value="4" class="btn btn-secondary">
                ← Back
            </button>
            <button type="submit" name="next_step" value="6" class="btn btn-primary">
                Next: Complete Installation →
            </button>
        </div>
    </form>
</div>

<style>
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
    color: var(--gray-700);
}

.checkbox-label input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    margin: 0;
}

.form-section {
    margin-bottom: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.password-input {
    position: relative;
    display: flex;
}

.password-input input {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.password-input .show-password {
    background: var(--gray-200);
    border: 2px solid var(--gray-200);
    border-left: none;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    padding: 0.875rem 1rem;
    font-size: 0.8rem;
    color: var(--gray-600);
    cursor: pointer;
    transition: all 0.3s ease;
}

.password-input .show-password:hover {
    background: var(--gray-300);
    color: var(--gray-700);
}

.test-section {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
}

.woocommerce-info {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.woocommerce-info h4 {
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

.api-setup-guide {
    background: #dbeafe;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    border-left: 4px solid var(--info-color);
}

.api-setup-guide h4 {
    margin-bottom: 1rem;
    color: #1e40af;
}

.guide-steps {
    display: grid;
    gap: 1rem;
}

.guide-step {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.step-number {
    width: 2rem;
    height: 2rem;
    background: var(--info-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
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

.connection-details {
    background: #d1fae5;
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    border-left: 4px solid var(--success-color);
}

.connection-details h4 {
    margin-bottom: 0.5rem;
    color: #065f46;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
}

.detail-label {
    font-weight: 500;
    color: #065f46;
}

.detail-value {
    color: #047857;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .guide-step {
        flex-direction: column;
        text-align: center;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function toggleWooCommerceFields() {
    const checkbox = document.getElementById('wc_enabled');
    const fields = document.getElementById('woocommerce-fields');
    
    if (checkbox.checked) {
        fields.style.display = 'block';
        // Make fields required
        fields.querySelectorAll('input[type="url"], input[type="text"], input[type="password"]').forEach(input => {
            input.required = true;
        });
    } else {
        fields.style.display = 'none';
        // Remove required attribute
        fields.querySelectorAll('input[type="url"], input[type="text"], input[type="password"]').forEach(input => {
            input.required = false;
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleWooCommerceFields();
});
</script>
