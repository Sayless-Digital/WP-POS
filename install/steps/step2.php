<?php
/**
 * Step 2: Database Configuration
 */

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? '';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbDatabase = $_POST['db_database'] ?? '';
    $dbUsername = $_POST['db_username'] ?? '';
    $dbPassword = $_POST['db_password'] ?? '';
    
    // Always save the data first
    $_SESSION['install_data']['database'] = [
        'db_host' => $dbHost,
        'db_port' => $dbPort,
        'db_database' => $dbDatabase,
        'db_username' => $dbUsername,
        'db_password' => $dbPassword,
    ];
    
    // Test connection if requested
    if (isset($_POST['test_connection'])) {
        $result = $installer->testDatabaseConnection($dbHost, $dbPort, $dbDatabase, $dbUsername, $dbPassword);
        
        if ($result['success']) {
            $success = $result['message'];
            $_SESSION['database_tested'] = true;
        } else {
            $error = $result['message'];
            $_SESSION['database_tested'] = false;
        }
    }
    
    // Proceed to next step if connection is tested and valid
    if (isset($_POST['next_step']) && isset($_SESSION['database_tested']) && $_SESSION['database_tested']) {
        $_SESSION['install_step'] = 3;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get saved values
$savedData = $_SESSION['install_data']['database'] ?? [];
?>

<h2 class="step-title">Database Configuration</h2>
<p class="step-description">Enter your database connection details. Make sure the database exists before proceeding.</p>

<?php if ($error): ?>
    <div class="alert alert-error">
        <strong>❌ Connection Failed:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>✅ Success:</strong> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<form method="POST" id="databaseForm">
    <div class="form-group">
        <label for="db_host">Database Host *</label>
        <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($savedData['db_host'] ?? '127.0.0.1'); ?>" required>
        <small>Usually 'localhost' or '127.0.0.1'</small>
    </div>

    <div class="form-group">
        <label for="db_port">Database Port *</label>
        <input type="number" id="db_port" name="db_port" value="<?php echo htmlspecialchars($savedData['db_port'] ?? '3306'); ?>" required>
        <small>Default MySQL port is 3306</small>
    </div>

    <div class="form-group">
        <label for="db_database">Database Name *</label>
        <input type="text" id="db_database" name="db_database" value="<?php echo htmlspecialchars($savedData['db_database'] ?? 'wp_pos'); ?>" required>
        <small>The database must already exist</small>
    </div>

    <div class="form-group">
        <label for="db_username">Database Username *</label>
        <input type="text" id="db_username" name="db_username" value="<?php echo htmlspecialchars($savedData['db_username'] ?? 'root'); ?>" required>
        <small>Database user with full privileges</small>
    </div>

    <div class="form-group">
        <label for="db_password">Database Password</label>
        <input type="password" id="db_password" name="db_password" value="<?php echo htmlspecialchars($savedData['db_password'] ?? ''); ?>">
        <small>Leave empty if no password is set</small>
    </div>

    <div class="alert alert-info">
        <strong>ℹ️ Important:</strong> Make sure you have created the database before testing the connection. You can create it using phpMyAdmin or MySQL command line.
    </div>

    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <button type="submit" name="test_connection" value="1" class="btn btn-secondary" style="flex: 1;">
            🔍 Test Connection
        </button>
        <button type="submit" name="next_step" value="3" class="btn btn-primary" style="flex: 1;" <?php echo !isset($_SESSION['database_tested']) || !$_SESSION['database_tested'] ? 'disabled' : ''; ?>>
            Next: Configuration →
        </button>
    </div>

    <div class="buttons">
        <button type="submit" name="prev_step" value="1" class="btn btn-secondary">
            ← Back
        </button>
        <div></div>
    </div>
</form>

<script>
document.getElementById('databaseForm').addEventListener('submit', function(e) {
    const nextStep = e.submitter.name === 'next_step';
    if (nextStep && !<?php echo isset($_SESSION['database_tested']) && $_SESSION['database_tested'] ? 'true' : 'false'; ?>) {
        e.preventDefault();
        alert('Please test the database connection first!');
    }
});
</script>