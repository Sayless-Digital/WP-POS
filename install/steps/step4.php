<?php
/**
 * Step 4: Admin Account Creation
 */

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';
    
    // Validation
    if (empty($adminName)) {
        $error = 'Admin name is required';
    } elseif (empty($adminEmail)) {
        $error = 'Admin email is required';
    } elseif (!$installer->validateEmail($adminEmail)) {
        $error = 'Please enter a valid email address';
    } elseif (empty($adminPassword)) {
        $error = 'Admin password is required';
    } elseif ($adminPassword !== $adminPasswordConfirm) {
        $error = 'Passwords do not match';
    } else {
        $passwordValidation = $installer->validatePasswordStrength($adminPassword);
        if (!$passwordValidation['valid']) {
            $error = $passwordValidation['errors'][0];
        }
    }
    
    if (!$error) {
        // Save admin data
        $_SESSION['install_data']['admin'] = [
            'admin_name' => $adminName,
            'admin_email' => $adminEmail,
            'admin_password' => $adminPassword,
        ];
        
        $success = 'Admin account details saved successfully!';
    }
    
    // Handle back navigation
    if (isset($_POST['prev_step'])) {
        $_SESSION['install_step'] = 3;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Proceed to next step
    if (isset($_POST['next_step']) && !$error) {
        $_SESSION['install_step'] = 5;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get saved values
$savedData = $_SESSION['install_data']['admin'] ?? [];
?>

<div class="step-content fade-in">
    <h2 class="step-title">Admin Account</h2>
    <p class="step-description">Create your administrator account to access the WP-POS system.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <div class="alert-title">Validation Error</div>
                <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <div class="alert-icon">✅</div>
            <div class="alert-content">
                <div class="alert-title">Account Details Saved</div>
                <div class="alert-message"><?php echo htmlspecialchars($success); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" class="installer-form" id="adminForm">
        <div class="form-section">
            <h3 class="section-title">Account Information</h3>
            
            <div class="form-group">
                <label for="admin_name">Full Name *</label>
                <input type="text" id="admin_name" name="admin_name" 
                       value="<?php echo htmlspecialchars($savedData['admin_name'] ?? ''); ?>" 
                       required data-tooltip="Your full name for the admin account">
                <small>Your full name for the administrator account</small>
            </div>

            <div class="form-group">
                <label for="admin_email">Email Address *</label>
                <input type="email" id="admin_email" name="admin_email" 
                       value="<?php echo htmlspecialchars($savedData['admin_email'] ?? ''); ?>" 
                       required data-tooltip="This will be your login username">
                <small>This will be your login username</small>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Password Security</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="admin_password">Password *</label>
                    <div class="password-input">
                        <input type="password" id="admin_password" name="admin_password" 
                               required data-tooltip="Choose a strong password">
                        <button type="button" class="show-password" tabindex="-1">Show</button>
                    </div>
                    <small>Choose a strong password</small>
                </div>

                <div class="form-group">
                    <label for="admin_password_confirm">Confirm Password *</label>
                    <div class="password-input">
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm" 
                               required data-tooltip="Re-enter your password">
                        <button type="button" class="show-password" tabindex="-1">Show</button>
                    </div>
                    <small>Re-enter your password</small>
                </div>
            </div>
        </div>

        <div class="password-requirements">
            <h4>Password Requirements</h4>
            <div class="requirements-grid">
                <div class="requirement-item">
                    <span class="requirement-icon">🔢</span>
                    <span class="requirement-text">At least 8 characters long</span>
                </div>
                <div class="requirement-item">
                    <span class="requirement-icon">🔤</span>
                    <span class="requirement-text">Contains uppercase letter</span>
                </div>
                <div class="requirement-item">
                    <span class="requirement-icon">🔡</span>
                    <span class="requirement-text">Contains lowercase letter</span>
                </div>
                <div class="requirement-item">
                    <span class="requirement-icon">🔢</span>
                    <span class="requirement-text">Contains number</span>
                </div>
            </div>
        </div>

        <div class="admin-info">
            <h4>Admin Account Features</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Full System Access</strong>
                    <p>Complete control over all POS features and settings.</p>
                </div>
                <div class="info-item">
                    <strong>User Management</strong>
                    <p>Create and manage other user accounts and permissions.</p>
                </div>
                <div class="info-item">
                    <strong>System Configuration</strong>
                    <p>Access to all system settings and configuration options.</p>
                </div>
                <div class="info-item">
                    <strong>Security Controls</strong>
                    <p>Manage security settings and access controls.</p>
                </div>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" name="prev_step" value="3" class="btn btn-secondary">
                ← Back
            </button>
            <button type="submit" name="next_step" value="5" class="btn btn-primary">
                Next: WooCommerce →
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

.password-requirements {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.password-requirements h4 {
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.requirements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: white;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.requirement-icon {
    font-size: 1.2rem;
}

.requirement-text {
    font-size: 0.9rem;
    color: var(--gray-700);
}

.admin-info {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.admin-info h4 {
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
    
    .requirements-grid {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('admin_password');
    const confirmInput = document.getElementById('admin_password_confirm');
    
    function validatePasswords() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (confirm && password !== confirm) {
            confirmInput.setCustomValidity('Passwords do not match');
        } else {
            confirmInput.setCustomValidity('');
        }
    }
    
    passwordInput.addEventListener('input', validatePasswords);
    confirmInput.addEventListener('input', validatePasswords);
});
</script>
