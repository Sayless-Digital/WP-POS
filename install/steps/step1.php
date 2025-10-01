<?php
/**
 * Step 1: System Requirements Check
 */

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle back navigation (shouldn't happen on step 1, but just in case)
    if (isset($_POST['prev_step'])) {
        $_SESSION['install_step'] = 1; // Stay on step 1
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Proceed to next step
    if (isset($_POST['next_step'])) {
        $_SESSION['install_step'] = 2;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$requirements = $installer->checkRequirements();
$allRequired = true;

foreach ($requirements as $req) {
    if ($req['required'] && !$req['status']) {
        $allRequired = false;
        break;
    }
}
?>

<h2 class="step-title">System Requirements</h2>
<p class="step-description">Checking if your server meets the minimum requirements for WP-POS.</p>

<?php if ($allRequired): ?>
    <div class="alert alert-success">
        <strong>✅ Great!</strong> Your server meets all the required specifications.
    </div>
<?php else: ?>
    <div class="alert alert-error">
        <strong>❌ Requirements Not Met</strong> Please fix the issues below before continuing.
    </div>
<?php endif; ?>

<ul class="requirement-list">
    <?php foreach ($requirements as $key => $req): ?>
        <li class="requirement-item <?php echo $req['status'] ? 'success' : ($req['required'] ? 'error' : 'warning'); ?>">
            <div>
                <strong><?php echo htmlspecialchars($req['name']); ?></strong>
                <?php if ($req['required']): ?>
                    <span style="color: #ef4444; font-size: 12px;">(Required)</span>
                <?php else: ?>
                    <span style="color: #f59e0b; font-size: 12px;">(Optional)</span>
                <?php endif; ?>
                <br>
                <small><?php echo htmlspecialchars($req['message']); ?></small>
            </div>
            <div class="status-icon">
                <?php if ($req['status']): ?>
                    ✅
                <?php elseif ($req['required']): ?>
                    ❌
                <?php else: ?>
                    ⚠️
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<div class="alert alert-info">
    <strong>ℹ️ Note:</strong> Optional requirements are recommended but not mandatory. The system will work without them, but some features may be limited.
</div>

<form method="POST">
    <div class="buttons">
        <div></div>
        <button type="submit" name="next_step" value="2" class="btn btn-primary" <?php echo !$allRequired ? 'disabled' : ''; ?>>
            Next: Database Setup →
        </button>
    </div>
</form>