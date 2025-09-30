<?php
/**
 * Step 4: Create Admin Account
 */

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $adminName = $_POST['admin_name'] ?? '';
    $adminEmail = $_POST['admin_email'] ?? '';
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';
    
    // Validation
    if (empty($adminName) || empty($adminEmail) || empty($adminPassword)) {
        $error = 'All fields are required';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($adminPassword) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($adminPassword !== $adminPasswordConfirm) {
        $error = 'Passwords do not match';
    } else {
        // Save admin data for final step
        $_SESSION['install_data']['admin'] = [
            'name' => $adminName,
            'email' => $adminEmail,
            'password' => $adminPassword,
        ];
        $success = 'Admin account details saved!';
    }
}

// Get saved values
$savedData = $_SESSION['install_data']['admin'] ?? [];
?>

<h2 class="step-title">Create Admin Account</h2>
<p class="step-description">Create the administrator account that will have full access to the system.</p>

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

<form method="POST" id="adminForm">
    <div class="form-group">
        <label for="admin_name">Full Name *</label>
        <input type="text" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($savedData['name'] ?? ''); ?>" required>
        <small>Your full name (e.g., John Doe)</small>
    </div>

    <div class="form-group">
        <label for="admin_email">Email Address *</label>
        <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($savedData['email'] ?? ''); ?>" required>
        <small>This will be your login username</small>
    </div>

    <div class="form-group">
        <label for="admin_password">Password *</label>
        <input type="password" id="admin_password" name="admin_password" required minlength="8">
        <small>At least 8 characters (use a strong password)</small>
    </div>

    <div class="form-group">
        <label for="admin_password_confirm">Confirm Password *</label>
        <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="8">
        <small>Re-enter your password</small>
    </div>

    <div class="alert alert-info">
        <strong>🔒 Security Tip:</strong> Use a strong password with a mix of uppercase, lowercase, numbers, and special characters. You can change this password later from your profile settings.
    </div>

    <button type="submit" name="create_admin" value="1" class="btn btn-secondary" style="width: 100%; margin-bottom: 20px;">
        👤 Save Admin Details
    </button>

    <div class="buttons">
        <button type="submit" name="prev_step" value="3" class="btn btn-secondary">
            ← Back
        </button>
        <button type="submit" name="next_step" value="5" class="btn btn-primary" <?php echo !$success ? 'disabled' : ''; ?>>
            Next: Complete Installation →
        </button>
    </div>
</form>

<script>
document.getElementById('adminForm').addEventListener('submit', function(e) {
    const nextStep = e.submitter.name === 'next_step';
    if (nextStep && !<?php echo $success ? 'true' : 'false'; ?>) {
        e.preventDefault();
        alert('Please save the admin details first!');
    }
    
    // Password match validation
    if (e.submitter.name === 'create_admin') {
        const password = document.getElementById('admin_password').value;
        const confirm = document.getElementById('admin_password_confirm').value;
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    }
});
</script>