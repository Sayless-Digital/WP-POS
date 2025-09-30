<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WooCommerce Store URL
    |--------------------------------------------------------------------------
    |
    | The URL of your WooCommerce store (without trailing slash)
    | Example: https://yourstore.com
    |
    */
    'store_url' => env('WOOCOMMERCE_STORE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | WooCommerce API Credentials
    |--------------------------------------------------------------------------
    |
    | Your WooCommerce REST API consumer key and secret.
    | Generate these from WooCommerce > Settings > Advanced > REST API
    |
    */
    'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY', ''),
    'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | WooCommerce REST API version to use
    |
    */
    'api_version' => env('WOOCOMMERCE_API_VERSION', 'wc/v3'),

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic synchronization behavior
    |
    */
    'sync' => [
        // Enable/disable automatic sync
        'enabled' => env('WOOCOMMERCE_SYNC_ENABLED', true),

        // Sync interval in minutes
        'interval' => env('WOOCOMMERCE_SYNC_INTERVAL', 15),

        // Sync direction: 'bidirectional', 'import_only', 'export_only'
        'direction' => env('WOOCOMMERCE_SYNC_DIRECTION', 'bidirectional'),

        // What to sync
        'sync_products' => env('WOOCOMMERCE_SYNC_PRODUCTS', true),
        'sync_orders' => env('WOOCOMMERCE_SYNC_ORDERS', true),
        'sync_customers' => env('WOOCOMMERCE_SYNC_CUSTOMERS', true),
        'sync_inventory' => env('WOOCOMMERCE_SYNC_INVENTORY', true),
        'sync_categories' => env('WOOCOMMERCE_SYNC_CATEGORIES', true),

        // Batch size for bulk operations
        'batch_size' => env('WOOCOMMERCE_BATCH_SIZE', 100),

        // Retry failed syncs
        'retry_failed' => env('WOOCOMMERCE_RETRY_FAILED', true),
        'max_retry_attempts' => env('WOOCOMMERCE_MAX_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling for real-time updates
    |
    */
    'webhooks' => [
        // Enable webhook processing
        'enabled' => env('WOOCOMMERCE_WEBHOOKS_ENABLED', true),

        // Webhook secret for validation
        'secret' => env('WOOCOMMERCE_WEBHOOK_SECRET', ''),

        // Events to listen for
        'events' => [
            'product.created',
            'product.updated',
            'product.deleted',
            'order.created',
            'order.updated',
            'order.deleted',
            'customer.created',
            'customer.updated',
            'customer.deleted',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Mapping
    |--------------------------------------------------------------------------
    |
    | Configure how products are mapped between systems
    |
    */
    'product_mapping' => [
        // Sync product images
        'sync_images' => env('WOOCOMMERCE_SYNC_IMAGES', true),

        // Sync product categories
        'sync_categories' => env('WOOCOMMERCE_SYNC_CATEGORIES', true),

        // Sync product variations
        'sync_variations' => env('WOOCOMMERCE_SYNC_VARIATIONS', true),

        // Default product status when importing
        'default_status' => env('WOOCOMMERCE_DEFAULT_PRODUCT_STATUS', 'publish'),

        // Fields to sync
        'fields' => [
            'name',
            'description',
            'short_description',
            'sku',
            'price',
            'regular_price',
            'sale_price',
            'stock_quantity',
            'manage_stock',
            'stock_status',
            'categories',
            'images',
            'attributes',
            'weight',
            'dimensions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Mapping
    |--------------------------------------------------------------------------
    |
    | Configure how orders are mapped between systems
    |
    */
    'order_mapping' => [
        // Sync order status changes
        'sync_status' => env('WOOCOMMERCE_SYNC_ORDER_STATUS', true),

        // Sync order notes
        'sync_notes' => env('WOOCOMMERCE_SYNC_ORDER_NOTES', true),

        // Status mapping (POS => WooCommerce)
        'status_map' => [
            'pending' => 'pending',
            'completed' => 'completed',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled',
        ],

        // Payment method mapping (POS => WooCommerce)
        'payment_method_map' => [
            'cash' => 'cod',
            'card' => 'stripe',
            'mobile' => 'mobile_money',
            'bank_transfer' => 'bacs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Mapping
    |--------------------------------------------------------------------------
    |
    | Configure how customers are mapped between systems
    |
    */
    'customer_mapping' => [
        // Sync customer addresses
        'sync_addresses' => env('WOOCOMMERCE_SYNC_ADDRESSES', true),

        // Sync customer metadata
        'sync_metadata' => env('WOOCOMMERCE_SYNC_METADATA', true),

        // Fields to sync
        'fields' => [
            'email',
            'first_name',
            'last_name',
            'username',
            'billing',
            'shipping',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Sync
    |--------------------------------------------------------------------------
    |
    | Configure inventory synchronization behavior
    |
    */
    'inventory' => [
        // Real-time inventory sync
        'realtime_sync' => env('WOOCOMMERCE_REALTIME_INVENTORY', true),

        // Sync on every sale
        'sync_on_sale' => env('WOOCOMMERCE_SYNC_ON_SALE', true),

        // Low stock threshold
        'low_stock_threshold' => env('WOOCOMMERCE_LOW_STOCK_THRESHOLD', 10),

        // Out of stock threshold
        'out_of_stock_threshold' => env('WOOCOMMERCE_OUT_OF_STOCK_THRESHOLD', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure error handling and logging
    |
    */
    'error_handling' => [
        // Log all API requests
        'log_requests' => env('WOOCOMMERCE_LOG_REQUESTS', false),

        // Log errors only
        'log_errors' => env('WOOCOMMERCE_LOG_ERRORS', true),

        // Email notifications on sync failures
        'email_on_failure' => env('WOOCOMMERCE_EMAIL_ON_FAILURE', false),
        'notification_email' => env('WOOCOMMERCE_NOTIFICATION_EMAIL', ''),

        // Slack notifications
        'slack_notifications' => env('WOOCOMMERCE_SLACK_NOTIFICATIONS', false),
        'slack_webhook_url' => env('WOOCOMMERCE_SLACK_WEBHOOK_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance and caching
    |
    */
    'performance' => [
        // Cache API responses
        'cache_enabled' => env('WOOCOMMERCE_CACHE_ENABLED', true),

        // Cache TTL in seconds
        'cache_ttl' => env('WOOCOMMERCE_CACHE_TTL', 3600),

        // Queue sync operations
        'use_queue' => env('WOOCOMMERCE_USE_QUEUE', true),

        // Queue connection
        'queue_connection' => env('WOOCOMMERCE_QUEUE_CONNECTION', 'database'),

        // API request timeout in seconds
        'timeout' => env('WOOCOMMERCE_TIMEOUT', 30),

        // Max concurrent requests
        'max_concurrent_requests' => env('WOOCOMMERCE_MAX_CONCURRENT_REQUESTS', 5),
    ],
];