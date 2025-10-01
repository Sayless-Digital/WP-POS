<?php
/**
 * WP-POS Modern Web Installer
 * A beautiful, step-by-step installation wizard
 */

// Start session
session_start();

// Include installer class
require_once __DIR__ . '/includes/Installer.php';

// Initialize installer
$installer = new Installer();

// Check if already installed
if ($installer->isLocked() && !isset($_GET['force'])) {
    $lockData = $installer->getLockData();
    $installDate = $lockData['installed_at'] ?? 'Unknown';
    
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Already Installed - WP-POS</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                padding: 60px 40px;
                box-shadow: 0 25px 50px rgba(0,0,0,0.25);
                text-align: center;
                max-width: 500px;
                width: 100%;
            }
            .icon { font-size: 80px; margin-bottom: 20px; }
            h1 { color: #10b981; margin-bottom: 15px; font-size: 28px; }
            p { color: #6b7280; margin-bottom: 30px; line-height: 1.6; }
            .info { background: #f3f4f6; padding: 15px; border-radius: 10px; margin: 20px 0; font-size: 14px; color: #374151; }
            .btn {
                display: inline-block;
                padding: 15px 30px;
                background: #3b82f6;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                transition: all 0.3s;
                margin: 10px;
            }
            .btn:hover { background: #2563eb; transform: translateY(-2px); }
            .btn-secondary { background: #6b7280; }
            .btn-secondary:hover { background: #4b5563; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">✅</div>
            <h1>Already Installed</h1>
            <p>WP-POS has already been installed on this server.</p>
            <div class="info">
                <strong>Installed:</strong> ' . htmlspecialchars($installDate) . '<br>
                <strong>Version:</strong> ' . ($lockData['version'] ?? 'Unknown') . '
            </div>
            <a href="../" class="btn">Go to Application</a>
            <a href="?force=1" class="btn btn-secondary">Reinstall</a>
        </div>
    </body>
    </html>
    ');
}

// Initialize session variables
if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 1;
    $_SESSION['install_data'] = [];
}

$currentStep = $_SESSION['install_step'];

// Handle step navigation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next_step'])) {
        $nextStep = (int)$_POST['next_step'];
        if ($nextStep > $currentStep && $nextStep <= 6) {
            $_SESSION['install_step'] = $nextStep;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } elseif (isset($_POST['prev_step'])) {
        $prevStep = (int)$_POST['prev_step'];
        if ($prevStep < $currentStep && $prevStep >= 1) {
            $_SESSION['install_step'] = $prevStep;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP-POS Installer</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="installer-container">
        <!-- Header -->
        <div class="installer-header">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">🛒</div>
                    <div class="logo-text">
                        <h1>WP-POS</h1>
                        <p>Point of Sale System</p>
                    </div>
                </div>
                <div class="installer-badge">
                    <span class="badge-text">Installer v2.0</span>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="progress-step <?php echo $currentStep >= $i ? 'active' : ''; ?> <?php echo $currentStep > $i ? 'completed' : ''; ?>">
                        <div class="step-circle">
                            <?php if ($currentStep > $i): ?>
                                ✓
                            <?php else: ?>
                                <?php echo $i; ?>
                            <?php endif; ?>
                        </div>
                        <div class="step-label">
                            <?php
                            $labels = [
                                1 => 'Requirements',
                                2 => 'Database',
                                3 => 'Configuration',
                                4 => 'Admin Account',
                                5 => 'WooCommerce',
                                6 => 'Complete'
                            ];
                            echo $labels[$i];
                            ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="installer-content">
            <div class="content-wrapper">
                <?php
                // Load the appropriate step
                $stepFile = __DIR__ . '/steps/step' . $currentStep . '.php';
                if (file_exists($stepFile)) {
                    include $stepFile;
                } else {
                    echo '<div class="error-message">Step file not found!</div>';
                }
                ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="installer-footer">
            <div class="footer-content">
                <p>&copy; 2024 WP-POS. Modern Point of Sale System.</p>
                <div class="footer-links">
                    <a href="#" onclick="showHelp()">Help</a>
                    <a href="#" onclick="showAbout()">About</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Installation Help</h3>
                <span class="close" onclick="closeModal('helpModal')">&times;</span>
            </div>
            <div class="modal-body">
                <h4>System Requirements</h4>
                <ul>
                    <li>PHP 8.1 or higher</li>
                    <li>MySQL 5.7+ or MariaDB 10.3+</li>
                    <li>Required PHP extensions: PDO, PDO MySQL, Mbstring, OpenSSL, JSON, cURL</li>
                    <li>Writable directories: storage/, bootstrap/cache/, root directory</li>
                </ul>
                
                <h4>Common Issues</h4>
                <ul>
                    <li><strong>Permission errors:</strong> Set proper file permissions (755 for directories, 644 for files)</li>
                    <li><strong>Database connection:</strong> Ensure database exists and user has proper privileges</li>
                    <li><strong>Missing extensions:</strong> Install required PHP extensions via package manager</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- About Modal -->
    <div id="aboutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>About WP-POS</h3>
                <span class="close" onclick="closeModal('aboutModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>WP-POS is a modern, feature-rich Point of Sale system built with Laravel and Livewire.</p>
                <h4>Features</h4>
                <ul>
                    <li>Complete POS functionality</li>
                    <li>Inventory management</li>
                    <li>Customer management</li>
                    <li>Sales reporting</li>
                    <li>WooCommerce integration</li>
                    <li>Multi-user support</li>
                </ul>
                <p><strong>Version:</strong> 2.0<br>
                <strong>Framework:</strong> Laravel 10<br>
                <strong>Frontend:</strong> Livewire + Tailwind CSS</p>
            </div>
        </div>
    </div>

    <script src="assets/js/installer.js"></script>
</body>
</html>
