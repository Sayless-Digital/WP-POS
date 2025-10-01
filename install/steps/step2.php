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
            $_SESSION['database_details'] = $result['details'] ?? [];
        } else {
            $error = $result['message'];
            $_SESSION['database_tested'] = false;
        }
    }
    
    // Handle back navigation
    if (isset($_POST['prev_step'])) {
        $_SESSION['install_step'] = 1;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
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

<div class="step-content fade-in">
    <h2 class="step-title">Database Configuration</h2>
    <p class="step-description">Enter your database connection details. Make sure the database exists before proceeding.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <div class="alert-title">Connection Failed</div>
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
        
        <?php if (isset($_SESSION['database_details']) && !empty($_SESSION['database_details'])): ?>
            <div class="connection-details">
                <h4>Connection Details</h4>
                <div class="details-grid">
                    <?php foreach ($_SESSION['database_details'] as $key => $value): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($value); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" class="installer-form" id="databaseForm">
        <div class="form-section">
            <h3 class="section-title">Database Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="db_host">Database Host *</label>
                    <input type="text" id="db_host" name="db_host" 
                           value="<?php echo htmlspecialchars($savedData['db_host'] ?? '127.0.0.1'); ?>" 
                           required data-tooltip="Usually 'localhost' or '127.0.0.1'">
                    <small>Usually 'localhost' or '127.0.0.1'</small>
                </div>

                <div class="form-group">
                    <label for="db_port">Database Port *</label>
                    <input type="number" id="db_port" name="db_port" 
                           value="<?php echo htmlspecialchars($savedData['db_port'] ?? '3306'); ?>" 
                           required min="1" max="65535" data-tooltip="Default MySQL port is 3306">
                    <small>Default MySQL port is 3306</small>
                </div>
            </div>

            <div class="form-group">
                <label for="db_database">Database Name *</label>
                <input type="text" id="db_database" name="db_database" 
                       value="<?php echo htmlspecialchars($savedData['db_database'] ?? 'wp_pos'); ?>" 
                       required data-tooltip="The database must already exist">
                <small>The database must already exist</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="db_username">Database Username *</label>
                    <input type="text" id="db_username" name="db_username" 
                           value="<?php echo htmlspecialchars($savedData['db_username'] ?? 'root'); ?>" 
                           required data-tooltip="Database user with full privileges">
                    <small>Database user with full privileges</small>
                </div>

                <div class="form-group">
                    <label for="db_password">Database Password</label>
                    <div class="password-input">
                        <input type="password" id="db_password" name="db_password" 
                               value="<?php echo htmlspecialchars($savedData['db_password'] ?? ''); ?>"
                               data-tooltip="Leave empty if no password is set">
                        <button type="button" class="show-password" tabindex="-1">Show</button>
                    </div>
                    <small>Leave empty if no password is set</small>
                </div>
            </div>
        </div>

        <div class="database-info">
            <h4>Database Requirements</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>MySQL 5.7+</strong>
                    <p>Or MariaDB 10.3+ for optimal performance and features.</p>
                </div>
                <div class="info-item">
                    <strong>UTF8MB4 Support</strong>
                    <p>Full Unicode support for international characters.</p>
                </div>
                <div class="info-item">
                    <strong>User Privileges</strong>
                    <p>CREATE, ALTER, INSERT, UPDATE, DELETE, SELECT permissions.</p>
                </div>
                <div class="info-item">
                    <strong>Empty Database</strong>
                    <p>Use a fresh database or ensure it's empty for clean installation.</p>
                </div>
            </div>
        </div>

        <div class="test-section">
            <button type="submit" name="test_connection" value="1" class="btn btn-secondary test-connection" data-original-text="🔍 Test Connection">
                🔍 Test Connection
            </button>
        </div>

        <div class="button-group">
            <button type="submit" name="prev_step" value="1" class="btn btn-secondary">
                ← Back
            </button>
            <button type="submit" name="next_step" value="3" class="btn btn-primary" 
                    <?php echo !isset($_SESSION['database_tested']) || !$_SESSION['database_tested'] ? 'disabled' : ''; ?>>
                Next: Configuration →
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

.database-info {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.database-info h4 {
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

.test-section {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
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
    
    .details-grid {
        grid-template-columns: 1fr;
    }
}
</style>
