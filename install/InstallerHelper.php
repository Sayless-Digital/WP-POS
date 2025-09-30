<?php
/**
 * Installer Helper Class
 * Handles all installation logic
 */

class InstallerHelper
{
    private $rootPath;
    private $errors = [];
    
    public function __construct()
    {
        $this->rootPath = dirname(__DIR__);
    }
    
    /**
     * Check system requirements
     */
    public function checkRequirements()
    {
        $requirements = [
            'php_version' => [
                'name' => 'PHP Version (>= 8.1)',
                'required' => true,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'message' => 'Current: ' . PHP_VERSION
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'required' => true,
                'status' => extension_loaded('pdo'),
                'message' => extension_loaded('pdo') ? 'Installed' : 'Not installed'
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL Extension',
                'required' => true,
                'status' => extension_loaded('pdo_mysql'),
                'message' => extension_loaded('pdo_mysql') ? 'Installed' : 'Not installed'
            ],
            'mbstring' => [
                'name' => 'Mbstring Extension',
                'required' => true,
                'status' => extension_loaded('mbstring'),
                'message' => extension_loaded('mbstring') ? 'Installed' : 'Not installed'
            ],
            'openssl' => [
                'name' => 'OpenSSL Extension',
                'required' => true,
                'status' => extension_loaded('openssl'),
                'message' => extension_loaded('openssl') ? 'Installed' : 'Not installed'
            ],
            'json' => [
                'name' => 'JSON Extension',
                'required' => true,
                'status' => extension_loaded('json'),
                'message' => extension_loaded('json') ? 'Installed' : 'Not installed'
            ],
            'curl' => [
                'name' => 'cURL Extension',
                'required' => true,
                'status' => extension_loaded('curl'),
                'message' => extension_loaded('curl') ? 'Installed' : 'Not installed'
            ],
            'gd' => [
                'name' => 'GD Extension',
                'required' => false,
                'status' => extension_loaded('gd'),
                'message' => extension_loaded('gd') ? 'Installed' : 'Not installed (optional)'
            ],
            'zip' => [
                'name' => 'ZIP Extension',
                'required' => false,
                'status' => extension_loaded('zip'),
                'message' => extension_loaded('zip') ? 'Installed' : 'Not installed (optional)'
            ],
            'storage_writable' => [
                'name' => 'Storage Directory Writable',
                'required' => true,
                'status' => is_writable($this->rootPath . '/storage'),
                'message' => is_writable($this->rootPath . '/storage') ? 'Writable' : 'Not writable'
            ],
            'bootstrap_writable' => [
                'name' => 'Bootstrap/Cache Directory Writable',
                'required' => true,
                'status' => is_writable($this->rootPath . '/bootstrap/cache'),
                'message' => is_writable($this->rootPath . '/bootstrap/cache') ? 'Writable' : 'Not writable'
            ],
            'env_writable' => [
                'name' => 'Root Directory Writable (for .env)',
                'required' => true,
                'status' => is_writable($this->rootPath),
                'message' => is_writable($this->rootPath) ? 'Writable' : 'Not writable'
            ],
        ];
        
        return $requirements;
    }
    
    /**
     * Test database connection
     */
    public function testDatabaseConnection($host, $port, $database, $username, $password)
    {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            return ['success' => true, 'message' => 'Connection successful!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create .env file
     */
    public function createEnvFile($data)
    {
        $envExample = $this->rootPath . '/.env.example';
        $envFile = $this->rootPath . '/.env';
        
        if (!file_exists($envExample)) {
            return ['success' => false, 'message' => '.env.example file not found'];
        }
        
        $envContent = file_get_contents($envExample);
        
        // Replace placeholders
        $replacements = [
            'APP_NAME=Laravel' => 'APP_NAME="' . ($data['app_name'] ?? 'WP-POS') . '"',
            'APP_ENV=local' => 'APP_ENV=' . ($data['app_env'] ?? 'production'),
            'APP_DEBUG=true' => 'APP_DEBUG=' . ($data['app_debug'] ?? 'false'),
            'APP_URL=http://localhost' => 'APP_URL=' . ($data['app_url'] ?? 'http://localhost'),
            
            'DB_CONNECTION=mysql' => 'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1' => 'DB_HOST=' . $data['db_host'],
            'DB_PORT=3306' => 'DB_PORT=' . $data['db_port'],
            'DB_DATABASE=laravel' => 'DB_DATABASE=' . $data['db_database'],
            'DB_USERNAME=root' => 'DB_USERNAME=' . $data['db_username'],
            'DB_PASSWORD=' => 'DB_PASSWORD=' . $data['db_password'],
        ];
        
        foreach ($replacements as $search => $replace) {
            $envContent = str_replace($search, $replace, $envContent);
        }
        
        // Add installer completion flag
        $envContent .= "\n\n# Installer\nINSTALLER_COMPLETED=true\n";
        
        if (file_put_contents($envFile, $envContent) === false) {
            return ['success' => false, 'message' => 'Could not write .env file'];
        }
        
        return ['success' => true, 'message' => '.env file created successfully'];
    }
    
    /**
     * Generate application key
     */
    public function generateAppKey()
    {
        $key = 'base64:' . base64_encode(random_bytes(32));
        
        $envFile = $this->rootPath . '/.env';
        if (!file_exists($envFile)) {
            return ['success' => false, 'message' => '.env file not found'];
        }
        
        $envContent = file_get_contents($envFile);
        $envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $envContent);
        
        if (file_put_contents($envFile, $envContent) === false) {
            return ['success' => false, 'message' => 'Could not update .env file'];
        }
        
        return ['success' => true, 'message' => 'Application key generated', 'key' => $key];
    }
    
    /**
     * Run artisan command
     */
    public function runArtisan($command)
    {
        $artisan = $this->rootPath . '/artisan';
        if (!file_exists($artisan)) {
            return ['success' => false, 'message' => 'Artisan file not found'];
        }
        
        $output = [];
        $returnVar = 0;
        
        exec("php {$artisan} {$command} 2>&1", $output, $returnVar);
        
        return [
            'success' => $returnVar === 0,
            'message' => implode("\n", $output),
            'output' => $output
        ];
    }
    
    /**
     * Create admin user
     */
    public function createAdminUser($name, $email, $password)
    {
        // This will be called via artisan command after migrations
        $command = "db:seed --class=AdminUserSeeder --name=\"{$name}\" --email=\"{$email}\" --password=\"{$password}\"";
        return $this->runArtisan($command);
    }
    
    /**
     * Lock installer
     */
    public function lockInstaller()
    {
        $lockFile = __DIR__ . '/.installed';
        return file_put_contents($lockFile, date('Y-m-d H:i:s')) !== false;
    }
    
    /**
     * Check if installer is locked
     */
    public function isLocked()
    {
        return file_exists(__DIR__ . '/.installed');
    }
    
    /**
     * Get errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Add error
     */
    public function addError($error)
    {
        $this->errors[] = $error;
    }
}