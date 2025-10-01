<?php
/**
 * WP-POS Web Installer
 * 
 * A step-by-step installation wizard for WP-POS
 */

// Start session
session_start();

// Check if already installed
if (file_exists(__DIR__ . '/../.env') && !isset($_GET['force'])) {
    $envContent = file_get_contents(__DIR__ . '/../.env');
    if (strpos($envContent, 'APP_KEY=base64:') !== false && strpos($envContent, 'INSTALLER_COMPLETED=true') !== false) {
        die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Already Installed</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
                .message { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
                .message h1 { color: #10b981; margin: 0 0 20px 0; }
                .message a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="message">
                <h1>✅ Already Installed</h1>
                <p>WP-POS is already installed on this server.</p>
                <a href="../">Go to Application</a>
            </div>
        </body>
        </html>
        ');
    }
}

// Initialize session variables
if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 1;
    $_SESSION['install_data'] = [];
}

// Step navigation is now handled by individual step files
// This prevents conflicts and data loss

$currentStep = $_SESSION['install_step'];

// Include the installer class
require_once __DIR__ . '/InstallerHelper.php';
$installer = new InstallerHelper();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP-POS Installer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 0;
        }

        .progress-step.active:not(:last-child)::after,
        .progress-step.completed:not(:last-child)::after {
            background: #10b981;
        }

        .progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 1;
            margin-bottom: 10px;
        }

        .progress-step.active .progress-circle {
            background: #3b82f6;
            color: white;
        }

        .progress-step.completed .progress-circle {
            background: #10b981;
            color: white;
        }

        .progress-label {
            font-size: 12px;
            color: #6c757d;
        }

        .progress-step.active .progress-label {
            color: #3b82f6;
            font-weight: 600;
        }

        .content {
            padding: 40px;
        }

        .step-title {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .step-description {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 12px;
        }

        .requirement-list {
            list-style: none;
        }

        .requirement-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .requirement-item.success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }

        .requirement-item.error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }

        .requirement-item.warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .status-icon {
            font-size: 20px;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 WP-POS Installer</h1>
            <p>Complete Point of Sale System - Easy Setup Wizard</p>
        </div>

        <div class="progress-bar">
            <div class="progress-step <?php echo $currentStep >= 1 ? 'active' : ''; ?> <?php echo $currentStep > 1 ? 'completed' : ''; ?>">
                <div class="progress-circle">1</div>
                <div class="progress-label">Requirements</div>
            </div>
            <div class="progress-step <?php echo $currentStep >= 2 ? 'active' : ''; ?> <?php echo $currentStep > 2 ? 'completed' : ''; ?>">
                <div class="progress-circle">2</div>
                <div class="progress-label">Database</div>
            </div>
            <div class="progress-step <?php echo $currentStep >= 3 ? 'active' : ''; ?> <?php echo $currentStep > 3 ? 'completed' : ''; ?>">
                <div class="progress-circle">3</div>
                <div class="progress-label">Configuration</div>
            </div>
            <div class="progress-step <?php echo $currentStep >= 4 ? 'active' : ''; ?> <?php echo $currentStep > 4 ? 'completed' : ''; ?>">
                <div class="progress-circle">4</div>
                <div class="progress-label">Admin Account</div>
            </div>
            <div class="progress-step <?php echo $currentStep >= 5 ? 'active' : ''; ?> <?php echo $currentStep > 5 ? 'completed' : ''; ?>">
                <div class="progress-circle">5</div>
                <div class="progress-label">WooCommerce</div>
            </div>
            <div class="progress-step <?php echo $currentStep >= 6 ? 'active' : ''; ?> <?php echo $currentStep > 6 ? 'completed' : ''; ?>">
                <div class="progress-circle">6</div>
                <div class="progress-label">Complete</div>
            </div>
        </div>

        <div class="content">
            <?php
            // Load the appropriate step
            $stepFile = __DIR__ . '/steps/step' . $currentStep . '.php';
            if (file_exists($stepFile)) {
                include $stepFile;
            } else {
                echo '<div class="alert alert-error">Step file not found!</div>';
            }
            ?>
        </div>
    </div>

    <script>
        // Form validation and AJAX handling
        document.addEventListener('DOMContentLoaded', function() {
            // Add any JavaScript functionality here
            console.log('WP-POS Installer loaded');
        });
    </script>
</body>
</html>