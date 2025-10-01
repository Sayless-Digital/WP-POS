<?php
/**
 * WP-POS Modern Installer
 * A clean, modern installation wizard for WP-POS
 */

class Installer
{
    private $rootPath;
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct()
    {
        $this->rootPath = dirname(dirname(__DIR__));
    }
    
    /**
     * Check system requirements
     */
    public function checkRequirements()
    {
        $requirements = [
            'php_version' => [
                'name' => 'PHP Version',
                'required' => true,
                'current' => PHP_VERSION,
                'minimum' => '8.1.0',
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'description' => 'PHP 8.1 or higher is required'
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'required' => true,
                'status' => extension_loaded('pdo'),
                'description' => 'Required for database connectivity'
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL Extension',
                'required' => true,
                'status' => extension_loaded('pdo_mysql'),
                'description' => 'Required for MySQL database support'
            ],
            'mbstring' => [
                'name' => 'Mbstring Extension',
                'required' => true,
                'status' => extension_loaded('mbstring'),
                'description' => 'Required for string manipulation'
            ],
            'openssl' => [
                'name' => 'OpenSSL Extension',
                'required' => true,
                'status' => extension_loaded('openssl'),
                'description' => 'Required for encryption and security'
            ],
            'json' => [
                'name' => 'JSON Extension',
                'required' => true,
                'status' => extension_loaded('json'),
                'description' => 'Required for data processing'
            ],
            'curl' => [
                'name' => 'cURL Extension',
                'required' => true,
                'status' => extension_loaded('curl'),
                'description' => 'Required for external API communication'
            ],
            'fileinfo' => [
                'name' => 'Fileinfo Extension',
                'required' => true,
                'status' => extension_loaded('fileinfo'),
                'description' => 'Required for file type detection'
            ],
            'tokenizer' => [
                'name' => 'Tokenizer Extension',
                'required' => true,
                'status' => extension_loaded('tokenizer'),
                'description' => 'Required for Laravel framework'
            ],
            'xml' => [
                'name' => 'XML Extension',
                'required' => true,
                'status' => extension_loaded('xml'),
                'description' => 'Required for XML processing'
            ],
            'gd' => [
                'name' => 'GD Extension',
                'required' => false,
                'status' => extension_loaded('gd'),
                'description' => 'Recommended for image processing'
            ],
            'zip' => [
                'name' => 'ZIP Extension',
                'required' => false,
                'status' => extension_loaded('zip'),
                'description' => 'Recommended for backup functionality'
            ],
            'storage_writable' => [
                'name' => 'Storage Directory',
                'required' => true,
                'status' => is_writable($this->rootPath . '/storage'),
                'description' => 'Must be writable for file storage'
            ],
            'bootstrap_writable' => [
                'name' => 'Bootstrap Cache Directory',
                'required' => true,
                'status' => is_writable($this->rootPath . '/bootstrap/cache'),
                'description' => 'Must be writable for caching'
            ],
            'root_writable' => [
                'name' => 'Root Directory',
                'required' => true,
                'status' => is_writable($this->rootPath),
                'description' => 'Must be writable for .env file creation'
            ],
            'composer_autoload' => [
                'name' => 'Composer Autoload',
                'required' => true,
                'status' => file_exists($this->rootPath . '/vendor/autoload.php'),
                'description' => 'Dependencies must be installed'
            ],
            'artisan_file' => [
                'name' => 'Artisan Command File',
                'required' => true,
                'status' => file_exists($this->rootPath . '/artisan'),
                'description' => 'Laravel command line tool'
            ],
            'env_example' => [
                'name' => 'Environment Template',
                'required' => true,
                'status' => file_exists($this->rootPath . '/.env.example'),
                'description' => 'Configuration template file'
            ]
        ];
        
        return $requirements;
    }
    
    /**
     * Test database connection
     */
    public function testDatabaseConnection($host, $port, $database, $username, $password)
    {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
            
            // Test if we can create a table (check permissions)
            $testTable = 'installer_test_' . uniqid();
            $pdo->exec("CREATE TABLE {$testTable} (id INT PRIMARY KEY)");
            $pdo->exec("DROP TABLE {$testTable}");
            
            return [
                'success' => true,
                'message' => 'Database connection successful! All permissions verified.',
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'charset' => 'utf8mb4'
                ]
            ];
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            $userMessage = 'Database connection failed: ';
            
            switch ($errorCode) {
                case 1045:
                    $userMessage .= 'Invalid username or password';
                    break;
                case 1049:
                    $userMessage .= 'Database does not exist';
                    break;
                case 2002:
                    $userMessage .= 'Cannot connect to database server';
                    break;
                case 1044:
                    $userMessage .= 'Access denied for user';
                    break;
                default:
                    $userMessage .= $errorMessage;
            }
            
            return [
                'success' => false,
                'message' => $userMessage,
                'technical' => $errorMessage
            ];
        }
    }
    
    /**
     * Test WooCommerce API connection
     */
    public function testWooCommerceConnection($url, $consumer_key, $consumer_secret)
    {
        try {
            // Clean URL
            $url = rtrim($url, '/');
            
            // Test system status endpoint
            $endpoint = $url . '/wp-json/wc/v3/system_status';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
                    'Content-Type: application/json',
                    'User-Agent: WP-POS-Installer/1.0'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($response === false) {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $curlError
                ];
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['environment'])) {
                    return [
                        'success' => true,
                        'message' => 'Successfully connected to WooCommerce!',
                        'details' => [
                            'store_url' => $data['environment']['site_url'] ?? $url,
                            'wc_version' => $data['environment']['version'] ?? 'Unknown',
                            'wp_version' => $data['environment']['wp_version'] ?? 'Unknown'
                        ]
                    ];
                }
                return [
                    'success' => true,
                    'message' => 'Connected to WooCommerce API successfully!'
                ];
            } elseif ($httpCode === 401) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed. Please check your Consumer Key and Consumer Secret.'
                ];
            } elseif ($httpCode === 404) {
                return [
                    'success' => false,
                    'message' => 'WooCommerce REST API not found. Please ensure WooCommerce is installed and REST API is enabled.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Unexpected response (HTTP {$httpCode}). Please verify your store URL and credentials."
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
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
        $envContent .= "\n\n# Installer\nINSTALLER_COMPLETED=true\nINSTALLER_VERSION=2.0\n";
        
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
        
        // Change to project directory for proper execution
        $oldCwd = getcwd();
        chdir($this->rootPath);
        
        exec("php artisan {$command} 2>&1", $output, $returnVar);
        
        chdir($oldCwd);
        
        return [
            'success' => $returnVar === 0,
            'message' => implode("\n", $output),
            'output' => $output,
            'command' => $command
        ];
    }
    
    /**
     * Create admin user
     */
    public function createAdminUser($name, $email, $password)
    {
        $command = "db:seed --class=AdminUserSeeder --name=\"{$name}\" --email=\"{$email}\" --password=\"{$password}\"";
        return $this->runArtisan($command);
    }
    
    /**
     * Lock installer
     */
    public function lockInstaller()
    {
        $lockFile = __DIR__ . '/../.installed';
        $lockData = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '2.0',
            'installer_path' => __DIR__ . '/../'
        ];
        
        return file_put_contents($lockFile, json_encode($lockData, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Check if installer is locked
     */
    public function isLocked()
    {
        $lockFile = __DIR__ . '/../.installed';
        return file_exists($lockFile);
    }
    
    /**
     * Get lock file data
     */
    public function getLockData()
    {
        $lockFile = __DIR__ . '/../.installed';
        if (file_exists($lockFile)) {
            $content = file_get_contents($lockFile);
            return json_decode($content, true);
        }
        return null;
    }
    
    /**
     * Validate email
     */
    public function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password)
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Add error message
     */
    public function addError($message)
    {
        $this->errors[] = $message;
    }
    
    /**
     * Add warning message
     */
    public function addWarning($message)
    {
        $this->warnings[] = $message;
    }
    
    /**
     * Add success message
     */
    public function addSuccess($message)
    {
        $this->success[] = $message;
    }
    
    /**
     * Get all messages
     */
    public function getMessages()
    {
        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'success' => $this->success
        ];
    }
    
    /**
     * Clear all messages
     */
    public function clearMessages()
    {
        $this->errors = [];
        $this->warnings = [];
        $this->success = [];
    }
}
