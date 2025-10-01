<?php
/**
 * Step 1: System Requirements Check
 */

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next_step'])) {
        $_SESSION['install_step'] = 2;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$requirements = $installer->checkRequirements();
$allRequired = true;
$hasWarnings = false;

foreach ($requirements as $req) {
    if ($req['required'] && !$req['status']) {
        $allRequired = false;
    }
    if (!$req['required'] && !$req['status']) {
        $hasWarnings = true;
    }
}
?>

<div class="step-content fade-in">
    <h2 class="step-title">System Requirements</h2>
    <p class="step-description">We'll check if your server meets all the requirements for WP-POS.</p>

    <?php if ($allRequired): ?>
        <div class="alert alert-success">
            <div class="alert-icon">✅</div>
            <div class="alert-content">
                <div class="alert-title">All Requirements Met!</div>
                <div class="alert-message">Your server is ready for WP-POS installation.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-error">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <div class="alert-title">Requirements Not Met</div>
                <div class="alert-message">Please fix the issues below before continuing with the installation.</div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($hasWarnings): ?>
        <div class="alert alert-warning">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <div class="alert-title">Optional Features</div>
                <div class="alert-message">Some optional features are not available, but the system will still work.</div>
            </div>
        </div>
    <?php endif; ?>

    <div class="requirements-section">
        <h3 class="section-title">Server Requirements</h3>
        <ul class="requirements-list">
            <?php foreach ($requirements as $key => $req): ?>
                <li class="requirement-item <?php echo $req['status'] ? 'success' : ($req['required'] ? 'error' : 'warning'); ?>">
                    <div class="requirement-info">
                        <div class="requirement-name">
                            <?php echo htmlspecialchars($req['name']); ?>
                            <?php if ($req['required']): ?>
                                <span class="requirement-badge required">Required</span>
                            <?php else: ?>
                                <span class="requirement-badge optional">Optional</span>
                            <?php endif; ?>
                        </div>
                        <div class="requirement-description">
                            <?php echo htmlspecialchars($req['description']); ?>
                        </div>
                        <?php if (isset($req['current'])): ?>
                            <div class="requirement-details">
                                <strong>Current:</strong> <?php echo htmlspecialchars($req['current']); ?>
                                <?php if (isset($req['minimum'])): ?>
                                    <strong>Minimum:</strong> <?php echo htmlspecialchars($req['minimum']); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="requirement-status">
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
    </div>

    <div class="requirements-info">
        <h4>What's Required?</h4>
        <div class="info-grid">
            <div class="info-item">
                <strong>PHP 8.1+</strong>
                <p>Modern PHP version with improved performance and security features.</p>
            </div>
            <div class="info-item">
                <strong>MySQL 5.7+</strong>
                <p>Reliable database system for storing your POS data.</p>
            </div>
            <div class="info-item">
                <strong>PHP Extensions</strong>
                <p>Essential extensions for Laravel framework and POS functionality.</p>
            </div>
            <div class="info-item">
                <strong>File Permissions</strong>
                <p>Proper permissions for application files and directories.</p>
            </div>
        </div>
    </div>

    <form method="POST" class="installer-form">
        <div class="button-group">
            <div></div>
            <button type="submit" name="next_step" value="2" class="btn btn-primary" <?php echo !$allRequired ? 'disabled' : ''; ?>>
                Next: Database Setup →
            </button>
        </div>
    </form>
</div>

<style>
.requirements-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 1rem;
}

.requirement-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.requirement-badge.required {
    background: #fee2e2;
    color: #991b1b;
}

.requirement-badge.optional {
    background: #fef3c7;
    color: #92400e;
}

.requirement-details {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: var(--gray-600);
}

.requirement-details strong {
    margin-right: 0.5rem;
}

.requirements-info {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.requirements-info h4 {
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
</style>
