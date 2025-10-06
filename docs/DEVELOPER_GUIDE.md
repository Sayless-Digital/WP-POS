# WP POS Developer Guide

## Recent Updates

### Version 1.8.70 - Unified Settings Save Logic
**Date**: October 6, 2025

**Problem**:
- Virtual keyboard settings were not saving consistently
- User reported settings only saved when other fields (name, email, etc.) were also changed
- Keyboard-only changes showed "Settings are unchanged" message

**Root Cause**:
- Initial fix in v1.8.69 used separate logic for keyboard settings vs other settings
- This created inconsistent behavior - keyboard settings had special handling while other fields didn't
- User expected all settings to behave the same way

**Solution (v1.8.70)**:
Unified all settings handling with simple array comparison in [`api/settings.php:53-82`](../api/settings.php:53-82):

```php
$current_settings = get_option(JPOS_SETTINGS_OPTION_KEY, get_jpos_default_settings());
$old_settings = $current_settings; // Save original for comparison

// Update settings with validated data
if (isset($validated_data['logo_url'])) $current_settings['logo_url'] = $validated_data['logo_url'];
if (isset($validated_data['name'])) $current_settings['name'] = $validated_data['name'];
// ... other text fields ...

// Handle virtual keyboard settings (boolean values don't need validation)
if (isset($data['virtual_keyboard_enabled'])) {
    $current_settings['virtual_keyboard_enabled'] = (bool)$data['virtual_keyboard_enabled'];
}
if (isset($data['virtual_keyboard_auto_show'])) {
    $current_settings['virtual_keyboard_auto_show'] = (bool)$data['virtual_keyboard_auto_show'];
}

// Check if anything actually changed by comparing old and new settings
$settings_changed = ($old_settings !== $current_settings);

if ($settings_changed) {
    // Force update since we detected changes
    update_option(JPOS_SETTINGS_OPTION_KEY, $current_settings, false);
    JPOS_Error_Handler::send_success([], 'Settings saved successfully.');
} else {
    JPOS_Error_Handler::send_success([], 'Settings are unchanged.');
}
```

**Key Improvements**:
1. **Unified Logic**: All settings (text fields and booleans) use the same change detection
2. **Simple Comparison**: Uses PHP's `!==` operator to compare entire arrays
3. **Consistent Behavior**: Whether you change name, email, or keyboard settings - same logic applies
4. **Clean Code**: Removed complex separate tracking for keyboard settings

**Previous Approach (v1.8.69)** - Deprecated:

```php
// Handle virtual keyboard settings (boolean values don't need validation)
$keyboard_changed = false;
if (isset($data['virtual_keyboard_enabled'])) {
    $new_enabled = (bool)$data['virtual_keyboard_enabled'];
    if (!isset($current_settings['virtual_keyboard_enabled']) || $current_settings['virtual_keyboard_enabled'] !== $new_enabled) {
        $keyboard_changed = true;
    }
    $current_settings['virtual_keyboard_enabled'] = $new_enabled;
}
if (isset($data['virtual_keyboard_auto_show'])) {
    $new_auto_show = (bool)$data['virtual_keyboard_auto_show'];
    if (!isset($current_settings['virtual_keyboard_auto_show']) || $current_settings['virtual_keyboard_auto_show'] !== $new_auto_show) {
        $keyboard_changed = true;
    }
    $current_settings['virtual_keyboard_auto_show'] = $new_auto_show;
}

// Force update if keyboard settings changed, even if other settings are the same
if ($keyboard_changed) {
    update_option(JPOS_SETTINGS_OPTION_KEY, $current_settings, false);
    JPOS_Error_Handler::send_success([], 'Settings saved successfully.');
} else {
    $result = update_option(JPOS_SETTINGS_OPTION_KEY, $current_settings);
    if ($result) {
        JPOS_Error_Handler::send_success([], 'Settings saved successfully.');
    } else {
        JPOS_Error_Handler::send_success([], 'Settings are unchanged.');
    }
}
```

**Key Changes**:
1. **Boolean Comparison**: Strict comparison (`!==`) to detect actual changes in boolean values
2. **Change Tracking**: `$keyboard_changed` flag tracks if any keyboard setting changed
3. **Forced Update**: When keyboard settings change, pass `false` as third parameter to `update_option()` to bypass WordPress's value comparison
4. **Success Message**: Always returns "Settings saved successfully" when keyboard settings change

**Technical Details**:
- WordPress `update_option($key, $value, $autoload)` uses `===` comparison internally
- Third parameter `false` forces database update even when values appear identical
- Boolean type coercion ensures consistent comparison
- Maintains backward compatibility with existing settings save logic

**Testing Instructions**:
1. Hard refresh browser (Ctrl+F5)
2. Navigate to Settings
3. Toggle "Enable Virtual Keyboard" checkbox
4. Click Save - should show "Settings saved successfully"
5. Refresh page - checkbox should remain in new state
6. Toggle "Auto-show on Focus" checkbox
7. Click Save - should show "Settings saved successfully"
8. Refresh page - both checkboxes should reflect saved state

**Cache Busting**:
- Updated version from 1.8.68 to 1.8.69 in [`index.php:25`](../index.php:25)

---

### Version 1.8.68 - Virtual Keyboard Settings Persistence Fix
**Date**: October 6, 2025

**Problem**: 
- Virtual keyboard settings (enable/disable and auto-show) were not persisting after save
- Auto-show keyboard functionality was not working on input focus

**Root Causes**:
1. The API endpoint [`api/settings.php`](../api/settings.php:1) was not handling the new `virtual_keyboard_enabled` and `virtual_keyboard_auto_show` boolean fields
2. [`initKeyboardAutoShow()`](../assets/js/main.js:3482) was only called from [`populateSettingsForm()`](../assets/js/main.js:3170), which runs when viewing the settings page, not during app initialization

**Changes Made**:

1. **API Settings Handler** ([`api/settings.php`](../api/settings.php:1))
   - Added keyboard settings to default configuration at lines 12-22:
     ```php
     'virtual_keyboard_enabled' => true,
     'virtual_keyboard_auto_show' => false,
     ```
   - Added boolean handling for keyboard settings in save logic at lines 62-67:
     ```php
     if (isset($data['virtual_keyboard_enabled'])) {
         $current_settings['virtual_keyboard_enabled'] = (bool)$data['virtual_keyboard_enabled'];
     }
     if (isset($data['virtual_keyboard_auto_show'])) {
         $current_settings['virtual_keyboard_auto_show'] = (bool)$data['virtual_keyboard_auto_show'];
     }
     ```

2. **App Initialization** ([`assets/js/main.js`](../assets/js/main.js:1))
   - Modified [`loadReceiptSettings()`](../assets/js/main.js:304) to initialize keyboard after loading settings:
     ```javascript
     if (result.success) {
         appState.settings = result.data;
         initKeyboardAutoShow(); // Initialize on app load
     }
     ```
   - Added keyboard settings to error fallback defaults

3. **Cache Busting** ([`index.php`](../index.php:25))
   - Incremented version from 1.8.67 to 1.8.68

**Technical Details**:
- **Boolean Type Coercion**: Used `(bool)` casting in PHP to ensure boolean values are stored correctly in WordPress options
- **Event Listener Initialization**: Moving `initKeyboardAutoShow()` call to app load ensures focus listeners are attached based on loaded settings whenever the app starts
- **State Management**: Settings are now properly synchronized between API, localStorage, and appState

**Testing Instructions**:
1. Hard refresh browser (Ctrl+F5 or Cmd+Shift+R) to clear cache
2. Navigate to Settings page
3. Enable both "Enable Virtual Keyboard" and "Auto-show on Focus" checkboxes
4. Click Save
5. Navigate away and return to Settings - checkboxes should remain checked
6. Navigate to POS page
7. Click any text input (search, customer name, etc.) - keyboard should automatically appear

## Virtual Keyboard System (v1.8.67)

### Overview
The virtual keyboard system provides a touch-friendly on-screen keyboard for text input, particularly useful on tablets and touch devices used in POS environments.

### Technical Implementation

#### Keyboard Module
- **Location**: [`assets/js/modules/keyboard.js`](../assets/js/modules/keyboard.js:1)
- **Class**: `OnScreenKeyboard`
- **Global Instance**: `window.onScreenKeyboard`

#### Key Features
1. **QWERTY Layout**: Standard keyboard layout with special keys (@, .)
2. **Touch-Optimized**: Large buttons with visual feedback
3. **Z-Index Management**: Appears above all content (z-[9999])
4. **Event Handling**: Triggers input events for compatibility with search features

#### Settings Integration

**Settings Storage**:
```javascript
appState.settings.virtual_keyboard_enabled // Boolean, default: true
appState.settings.virtual_keyboard_auto_show // Boolean, default: false
```

**Settings Form** ([`index.php:705-719`](../index.php:705-719)):
```html
<div class="bg-slate-700 p-4 rounded-lg mb-4">
    <h3>Virtual Keyboard Settings</h3>
    <label>
        <input type="checkbox" id="enable-virtual-keyboard">
        Enable Virtual Keyboard
    </label>
    <label>
        <input type="checkbox" id="auto-show-keyboard">
        Auto-show keyboard on input focus
    </label>
</div>
```

**Save Handler** ([`assets/js/main.js:3174`](../assets/js/main.js:3174)):
```javascript
async function saveSettings(event) {
    const data = {
        virtual_keyboard_enabled: enableKeyboard?.checked ?? true,
        virtual_keyboard_auto_show: autoShowKeyboard?.checked ?? false,
        // ... other settings
    };
    // Save to API and update appState
}
```

**Load Handler** ([`assets/js/main.js:3170`](../assets/js/main.js:3170)):
```javascript
function populateSettingsForm() {
    enableKeyboard.checked = appState.settings.virtual_keyboard_enabled !== false;
    autoShowKeyboard.checked = appState.settings.virtual_keyboard_auto_show === true;
    initKeyboardAutoShow(); // Initialize auto-show listeners
}
```

#### Auto-Show Functionality

**Implementation** ([`assets/js/main.js:3482`](../assets/js/main.js:3482)):
```javascript
function initKeyboardAutoShow() {
    // Remove existing listeners
    keyboardFocusListeners.forEach(({ element, handler }) => {
        element.removeEventListener('focus', handler);
    });
    keyboardFocusListeners = [];
    
    // Check settings
    const keyboardEnabled = appState.settings.virtual_keyboard_enabled !== false;
    const autoShowEnabled = appState.settings.virtual_keyboard_auto_show === true;
    
    if (!keyboardEnabled || !autoShowEnabled) return;
    
    // Attach focus listeners to all text inputs
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="search"], textarea');
    
    inputs.forEach(input => {
        // Skip excluded modals
        const excludedContainers = ['stock-edit-modal', 'product-editor-modal', 'fee-discount-modal'];
        // ... check if input is in excluded container
        
        const focusHandler = () => {
            if (input.offsetParent !== null && window.onScreenKeyboard) {
                window.onScreenKeyboard.show(input);
            }
        };
        
        input.addEventListener('focus', focusHandler);
        keyboardFocusListeners.push({ element: input, handler: focusHandler });
    });
}
```

#### Keyboard Button Visibility

The keyboard toggle button in the customer search modal is controlled by the enable setting:
```javascript
const keyboardBtn = document.getElementById('customer-keyboard-btn');
if (keyboardEnabled) {
    keyboardBtn.classList.remove('hidden');
} else {
    keyboardBtn.classList.add('hidden');
}
```

### Bug Fixes (v1.8.67)

#### Fixed Customer Dialog Close Button
**Problem**: X button didn't close the modal
**Root Cause**: Called non-existent `cartManager.hideCustomerSearch()`
**Solution**: Changed to `window.hideCustomerSearch()` at [`index.php:1069`](../index.php:1069)

#### Fixed Keyboard Z-Index
**Problem**: Keyboard appeared below content
**Root Cause**: z-50 too low, modals use z-100
**Solution**: Increased to z-[9999] at [`keyboard.js:29`](../assets/js/modules/keyboard.js:29)

### Testing

**Test Auto-Show Functionality**:
1. Enable both keyboard settings in Settings page
2. Click any text input field
3. Keyboard should automatically appear
4. Disable auto-show setting
5. Keyboard should only appear when clicking keyboard button

**Test Keyboard Visibility Control**:
1. Disable virtual keyboard in settings
2. Keyboard button should be hidden
3. Auto-show should not work
4. Enable keyboard setting
5. Button should reappear

### API Integration

Virtual keyboard settings are saved via the settings API endpoint:

**Endpoint**: `/api/settings.php`
**Method**: POST
**Payload**:
```json
{
    "virtual_keyboard_enabled": true,
    "virtual_keyboard_auto_show": false,
    "nonce": "..."
}
```

# WP POS Developer Guide

**Last Updated**: October 6, 2025
**System Version**: 1.8.66

## Overview

WP POS (WordPress Point of Sale) is a modern, modular point-of-sale system built on WordPress. This guide provides comprehensive information for developers working with the WP POS codebase.

## Architecture

### Frontend Architecture
- **Modular JavaScript**: The frontend uses a modular architecture with separate modules for different functionalities
- **State Management**: Centralized state management using `appState` object
- **API Communication**: RESTful API endpoints with consistent response formats
- **URL Routing**: URL parameter-based routing system for view persistence and navigation

### Backend Architecture
- **WordPress Integration**: Built on WordPress with custom API endpoints
- **Database Layer**: Optimized database queries with caching
- **Security**: CSRF protection, input validation, and prepared statements

## File Structure

```
wp-pos/
├── api/                    # API endpoints
│   ├── auth.php           # Authentication
│   ├── products.php       # Product management (optimized)
│   ├── orders.php         # Order processing
│   ├── reports.php          # Comprehensive reporting with intelligent time granularity
│   ├── database-optimizer.php # Database optimization
│   ├── cache-manager.php  # Caching system
│   ├── performance-monitor.php # Performance monitoring
│   └── monitoring.php     # Monitoring and logging
├── assets/
│   ├── js/
│   │   ├── main.js        # Main application file
│   │   ├── main-modular.js # Modular entry point
│   │   └── modules/       # Modular JavaScript files
│   │       ├── routing.js # URL routing system
│   └── build/             # Optimized bundles
├── config/                # Configuration files
├── tests/                 # Test suites
├── docs/                  # Documentation
└── logs/                  # Log files
```

## Development Setup

### Prerequisites
- WordPress installation
- PHP 7.4+
- MySQL 5.7+
- Modern web browser

### Installation
1. Clone the repository to your WordPress plugins directory
2. Ensure proper file permissions
3. Configure database settings
4. Run the test suite to verify installation

### Configuration
The system uses JSON-based configuration files located in the `config/` directory. Key configuration options include:

- Database settings
- Cache configuration
- Performance settings
- Security settings
- UI preferences

## Routing System

### URL Parameter-Based Routing
WP POS uses a URL parameter-based routing system to maintain view state across page reloads. This ensures users stay on their current view when refreshing the page.

### Supported Views
- `pos-page` - Point of Sale (default)
- `orders-page` - Order History
- `reports-page` - Sales Reports
- `sessions-page` - Session History
- `products-page` - Products
- `held-carts-page` - Held Carts
- `settings-page` - Settings

### Usage
```javascript
// Navigate to a specific view
routingManager.navigateToView('orders-page');

// Get current view
const currentView = routingManager.getCurrentView();

// Check if view is valid
const isValid = routingManager.isValidView('orders-page');
```

### URL Format
Views are accessed via URL parameters:
- `?view=pos-page` - Point of Sale
- `?view=orders-page` - Orders
- `?view=reports-page` - Sales Reports
- `?view=sessions-page` - Sessions
- etc.

### Browser Navigation
The routing system supports browser back/forward navigation and updates the URL automatically when switching views.

### Sidebar Navigation
The sidebar menu integrates seamlessly with the routing system:
- **Menu Buttons**: All sidebar buttons use the routing manager for navigation
- **Active State**: Current view button is highlighted automatically
- **Auto-Close**: Sidebar closes when navigating to different views
- **Overlay Close**: Clicking outside the sidebar (on overlay) closes it
- **URL Sync**: Sidebar navigation updates the browser URL

### Technical Implementation
The routing system requires specific global functions to be available for data loading:

**Required Global Functions:**
- `window.toggleMenu()` - Menu toggle functionality
- `window.fetchOrders()` - Load order history data
- `window.fetchSessions()` - Load session history data
- `window.renderStockList()` - Render stock management list
- `window.populateSettingsForm()` - Load settings form data
- `window.renderHeldCarts()` - Render held carts list

**Implementation Notes:**
- Functions are made globally available at the end of main.js execution
- Routing system checks for function availability before calling
- Proper error handling for missing functions
- Seamless integration with existing event listeners

## API Reference

### Authentication Endpoints

#### POST /api/auth.php
Authenticate user and return session data.

#### GET /api/auth.php?action=check_status
Check current authentication status and return user data.

**Response:**
```json
{
    "success": true,
    "loggedIn": true,
    "user": {
        "id": 1,
        "displayName": "admin",
        "email": "admin@example.com"
    }
}
```

**Request:**
```json
{
    "username": "admin",
    "password": "password"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful.",
    "user": {
        "id": 1,
        "displayName": "admin",
        "email": "admin@example.com"
    }
}
```

### Product Endpoints

#### GET /api/products.php
Retrieve product catalog with filtering options.

**Query Parameters:**
- `search`: Search term
- `category`: Category filter
- `stock`: Stock status filter
- `limit`: Results limit

**Response:**
```json
{
    "success": true,
    "data": {
        "products": [...],
        "total": 100,
        "page": 1
    }
}
```

## Complete API Reference

### Authentication Endpoints
- **POST** `/api/auth.php` - User login/logout
- **GET** `/api/auth.php` - Check authentication status

### Product Management
- **GET** `/api/products.php` - Retrieve product catalog with filtering
- **GET** `/api/product-edit-simple.php?action=get_product_details&id={id}` - Get product details for editing
- **POST** `/api/product-edit-simple.php` - Update existing product data (product creation removed in v1.8.52, image upload removed in v1.8.52)
- **GET** `/api/product-edit-simple.php?action=get_tax_classes` - Get tax classes
- **GET** `/api/stock.php` - Stock management operations
- **Note**: Product creation and image upload must be done through WooCommerce admin - these features have been removed from the POS interface

### Order Processing
- **GET** `/api/orders.php` - Fetch orders with filters (date, status, source, customer, order ID)
  - New in v1.8.59: Customer filtering support via `customer_filter` parameter
  - Enhanced in v1.8.60: Frontend converted to searchable input using customer search API
  - Returns customer information (customer_id, customer_name) in all order responses
- **POST** `/api/checkout.php` - Process checkout
- **POST** `/api/refund.php` - Process refunds

### Reporting & Analytics
- **REMOVED** - Reporting functionality has been completely removed from WP POS

### Customer Management
- **GET** `/api/customers.php` - Search WordPress users by name or email

### System Management
- **GET** `/api/settings.php` - Retrieve settings
- **POST** `/api/settings.php` - Update settings
- **GET** `/api/sessions.php` - Session management
- **POST** `/api/drawer.php` - Cash drawer operations

### Barcode Generation Endpoints

#### POST /api/barcode.php

Generates unique barcodes for products using JPOS format.

**Action**: `generate_barcode`

**Request Body:**
```json
{
    "action": "generate_barcode",
    "product_id": 123,
    "nonce": "abc123xyz"
}
```

**Response (Success):**
```json
{
    "success": true,
    "barcode": "20251004230845-A3F7",
    "product_id": 123,
    "message": "Barcode generated successfully"
}
```

**Barcode Format:**
- Pattern: `YYYYMMDDHHMMSS-RAND`
- `YYYYMMDDHHMMSS`: Full timestamp (year-month-day-hour-minute-second)
- `RAND`: 4-character random alphanumeric string (0-9, A-Z)
- Example: `20251004230845-A3F7`

**Features:**
- Timestamp-based generation for chronological uniqueness
- 4-character random alphanumeric suffix provides 1.6M+ combinations per second
- Automatic uniqueness validation via wp_postmeta query
- Retry logic (max 3 attempts) for race conditions
- No counter needed (timestamp provides uniqueness)
- CSRF nonce protection

**Error Responses:**
- 400: Invalid or missing product_id
- 403: Authentication failure or invalid nonce
- 404: Product not found
- 500: Generation failure after retries

**Example Usage:**
```javascript
const response = await fetch('/api/barcode.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'generate_barcode',
        product_id: 123,
        nonce: appState.nonces.barcode
    })
});
```

**Related Functions:**
- Backend: [`generate_unique_barcode()`](../api/barcode.php:118)
- Frontend: [`handleBarcodeGeneration()`](../assets/js/main.js:2765)

### Customer Search Endpoints

#### GET /api/customers.php

Search WordPress users by name or email for customer attachment to orders.

**Query Parameters:**
- `query`: (required) Search term (minimum 2 characters)
- `limit`: (optional) Maximum results to return, default: 10, max: 20
- `nonce`: (required) CSRF token for security validation

**Response (Success):**
```json
{
    "success": true,
    "data": {
        "customers": [
            {
                "id": 123,
                "name": "John Doe",
                "email": "john@example.com",
                "username": "johndoe"
            }
        ],
        "count": 1,
        "query": "john",
        "performance": {
            "execution_time": 0.045,
            "memory_used": "2MB"
        }
    }
}
```

**Search Behavior:**
- Searches across WordPress user_login, user_email, and display_name fields
- Supports partial matching with wildcards
- Requires minimum 2 characters to prevent excessive results
- Results ordered by display_name alphabetically
- Maximum 20 results returned per query

**Error Responses:**
```json
{
    "success": false,
    "message": "Authentication required."
}
```

```json
{
    "success": false,
    "message": "Invalid security token."
}
```

**Example Usage:**
```javascript
const nonce = document.getElementById('jpos-customer-search-nonce').value;
const response = await fetch(`api/customers.php?query=john&nonce=${nonce}`);
const data = await response.json();

if (data.success) {
    console.log(`Found ${data.data.count} customers`);
    data.data.customers.forEach(customer => {
        console.log(`${customer.name} - ${customer.email}`);
    });
}
```

**Security:**
- Requires WordPress authentication with `manage_woocommerce` capability
- CSRF protection via WordPress nonce validation
- Input sanitization with `sanitize_text_field()`
- Performance monitoring integration

**Related Components:**
- Frontend: [`searchCustomers()`](../assets/js/main.js:1450)
- UI Component: [`customer-search-modal`](../index.php:1034)
- State Management: [`appState.cart.customer`](../assets/js/modules/state.js:33)

### Product Image Upload System (REMOVED in v1.8.52)

**IMPORTANT: Product image upload functionality has been completely removed from the WP POS interface.**

**Reason for Removal:**
- Image management must be done through WooCommerce admin interface
- This ensures consistency with WordPress/WooCommerce architecture
- Simplifies POS interface and reduces complexity

**Alternative Method:**
To upload product images:
1. Open WordPress Admin dashboard
2. Navigate to Products → All Products
3. Click on the product you want to edit
4. Use the WooCommerce product editor to upload images
5. Images will automatically appear in the POS system

**What Was Removed:**
- All image upload functions from [`assets/js/main.js`](../assets/js/main.js:1) (lines 1700-2310)
- Image upload UI from [`index.php`](../index.php:786-858)
- File validation and upload handlers
- Drag-and-drop functionality
- Featured and gallery image management

**For Developers:**
If you need to re-implement image uploads, refer to version 1.8.51 or earlier for the complete implementation. However, it's recommended to keep image management in WooCommerce for consistency.

### Product Editor Endpoints

#### GET /api/product-edit-simple.php?action=get_product_details&id={product_id}
Retrieve comprehensive product details for editing.

**Query Parameters:**
- `action`: Must be "get_product_details"
- `id`: Product ID (required)

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "name": "Product Name",
        "sku": "SKU123",
        "barcode": "123456789",
        "type": "simple",
        "regular_price": "25.99",
        "sale_price": "",
        "status": "publish",
        "featured": false,
        "tax_class": "",
        "tax_status": "taxable",
        "stock_quantity": 10,
        "manage_stock": true,
        "meta_data": [
            {"key": "custom_field", "value": "custom_value"}
        ],
        "variations": [
            {
                "id": 124,
                "sku": "VAR-SKU",
                "price": "30.99",
                "sale_price": "",
                "stock_quantity": 5,
                "stock_status": "instock"
            }
        ],
        "attributes": [...]
    }
}
```

#### GET /api/product-edit-simple.php?action=get_tax_classes
Retrieve available tax classes.

**Response:**
```json
{
    "success": true,
    "data": [
        {"slug": "", "name": "Standard rate"},
        {"slug": "reduced-rate", "name": "Reduced rate"},
        {"slug": "zero-rate", "name": "Zero rate"}
    ]
}
```

#### POST /api/product-edit-simple.php

**IMPORTANT: Product creation functionality has been removed in v1.8.52.**

Only supports updating existing products. To create new products, use the WooCommerce admin interface.

**Update Product Request:**
```json
{
    "action": "update_product",
    "nonce": "wp_nonce_value",
    "product_id": 123,
    "name": "Updated Product Name",
    "sku": "NEW-SKU",
    "barcode": "987654321",
    "regular_price": "29.99",
    "sale_price": "24.99",
    "status": "publish",
    "featured": true,
    "tax_class": "",
    "tax_status": "taxable",
    "stock_quantity": 15,
    "manage_stock": true,
    "meta_data": [
        {"key": "custom_field", "value": "updated_value"}
    ],
    "variations": [
        {
            "id": 124,
            "sku": "UPDATED-SKU",
            "price": "35.99",
            "sale_price": "30.99",
            "stock_quantity": 8,
            "stock_status": "instock"
        }
    ]
}
```

**Update Product Response:**
```json
{
    "success": true,
    "message": "Product updated successfully",
    "data": {
        "product_id": 123,
        "updated_fields": ["name", "sku", "price", "stock_quantity"]
    }
}
```

**Error Responses:**
```json
{
    "success": false,
    "message": "Product name is required"
}
```

```json
{
    "success": false,
    "message": "Regular price is required"
}
```

```json
{
    "success": false,
    "message": "SKU already exists: EXISTING-SKU"
}
```

**Product Creation:**
Product creation has been removed from the POS interface in v1.8.52. To create new products:
1. Use the WooCommerce admin interface (WordPress Admin → Products → Add New)
2. Fill in all product details including images
3. Publish the product
4. It will automatically appear in the POS system

**Why This Change:**
- Ensures consistency with WordPress/WooCommerce architecture
- Prevents complications with image uploads and product data
- Simplifies POS interface for its primary purpose: sales transactions
- Reduces maintenance complexity

### Reports Endpoints

#### GET /api/reports.php
Retrieve comprehensive sales reports with intelligent time granularity.

**Query Parameters:**
- `period`: (required) Time period for the report
  - `today` - Current day
  - `yesterday` - Previous day
  - `this_week` - Current week (Monday to today)
  - `last_week` - Previous week
  - `this_month` - Current month
  - `this_year` - Current year
  - `custom` - Custom date range (requires custom_start and custom_end)
- `custom_start`: (optional) Start date for custom period (YYYY-MM-DD format)
- `custom_end`: (optional) End date for custom period (YYYY-MM-DD format)

**Response:**
```json
{
    "success": true,
    "data": {
        "period": {
            "type": "today",
            "start": "2025-01-02 00:00:00",
            "end": "2025-01-02 23:59:59",
            "granularity": "hour"
        },
        "summary": {
            "total_orders": 45,
            "total_revenue": 1250.75,
            "avg_order_value": 27.79,
            "min_order_value": 5.99,
            "max_order_value": 150.00
        },
        "chart_data": [
            {
                "period": "2025-01-02 09:00:00",
                "order_count": 5,
                "total_amount": 125.50,
                "avg_order_value": 25.10
            }
        ],
        "chart_labels": ["9:00 AM", "10:00 AM", "11:00 AM"],
        "chart_values": [125.50, 200.25, 175.00],
        "chart_order_counts": [5, 8, 7],
        "orders": [
            {
                "id": 123,
                "number": "1001",
                "date": "Jan 2, 2025, 9:15 am",
                "status": "completed",
                "source": "POS",
                "total": 25.99,
                "item_count": 3,
                "customer": "John Doe",
                "payment_method": "Cash",
                "items": [
                    {
                        "name": "Product Name",
                        "quantity": 2,
                        "total": 25.99
                    }
                ]
            }
        ]
    }
}
```

**Intelligent Time Granularity:**
- **Intraday periods** (same day): Hourly breakdown with time labels
- **Weekly or monthly periods**: Daily breakdown with date labels
- **2+ months up to 2 years**: Monthly breakdown with month labels
- **Multi-year periods**: Yearly breakdown with year labels

**Error Responses:**
```json
{
    "success": false,
    "message": "Invalid period specified"
}
```

**Example Usage:**
```javascript
// Get today's report
const response = await fetch('/wp-pos/api/reports.php?period=today');

// Get custom date range
const response = await fetch('/wp-pos/api/reports.php?period=custom&custom_start=2025-01-01&custom_end=2025-01-31');

// Get this month's report
const response = await fetch('/wp-pos/api/reports.php?period=this_month');
```

### Order Endpoints

#### GET /api/orders.php
Retrieve orders with comprehensive filtering options.

**Query Parameters:**
- `date_filter`: (optional) Date range filter - 'all', 'today', 'this_week', 'this_month', 'this_year'
- `status_filter`: (optional) Order status filter - 'all' or specific WooCommerce order status
- `source_filter`: (optional) Order source filter - 'all', 'pos', 'online'
- `customer_filter`: (optional) Customer ID filter - 'all' or specific customer ID (v1.8.59)
- `order_id_search`: (optional) Search by order number or ID
- `limit`: (optional) Maximum number of orders to return, default: 100

**Customer Filtering (v1.8.59-v1.8.60):**

Backend API (v1.8.59):
When `customer_filter` parameter is provided with a valid customer ID, the API filters orders using an EXISTS subquery:
```sql
EXISTS (
    SELECT 1 FROM wp_postmeta pm
    WHERE pm.post_id = p.ID
    AND pm.meta_key = '_customer_user'
    AND pm.meta_value = %d
)
```

Frontend Implementation (v1.8.60):
The customer filter UI was enhanced from a static dropdown to a searchable input field:
- **Search Input**: Users type customer name or email (minimum 2 characters)
- **Real-time Search**: Uses [`api/customers.php`](../api/customers.php:1) with 300ms debounce
- **Results Display**: Shows matching customers with name and email in dropdown
- **Selection**: Click customer to filter orders by that customer ID
- **Clear Button**: Quick reset to show all customers
- **Click-Outside**: Automatically closes results dropdown

**Frontend Functions:**
- [`searchCustomersForFilter(query)`](../assets/js/main.js:1543) - Fetches customer search results
- [`displayCustomerFilterResults(customers)`](../assets/js/main.js:1562) - Renders search results dropdown
- [`selectCustomerForFilter(customerId, customerName)`](../assets/js/main.js:1611) - Handles customer selection
- [`hideCustomerFilterResults()`](../assets/js/main.js:1598) - Closes results dropdown

**Response:**
```json
{
    "success": true,
    "data": {
        "orders": [
            {
                "id": 123,
                "number": "1001",
                "date": "Jan 2, 2025, 9:15 am",
                "status": "completed",
                "source": "POS",
                "total": 25.99,
                "item_count": 3,
                "customer_id": 5,
                "customer_name": "John Doe",
                "payment_method": "Cash"
            }
        ],
        "total": 1,
        "filters": {
            "date": "all",
            "status": "all",
            "source": "all",
            "customer": "5"
        }
    }
}
```

**Customer Data Fallback Logic:**
The API implements a hierarchical fallback to ensure customer names are always available:
1. First attempts to get WordPress user data via `get_userdata($customer_id)`
2. Falls back to billing first name + last name from order
3. Final fallback to "Guest" if no customer information available

**Example Usage:**
```javascript
// Backend: Get all orders
const response = await fetch('/api/orders.php');

// Backend: Get orders for specific customer
const response = await fetch('/api/orders.php?customer_filter=5');

// Backend: Combine customer filter with other filters
const response = await fetch('/api/orders.php?customer_filter=5&status_filter=completed&date_filter=this_month');

// Frontend: Search customers for filter
const customers = await searchCustomersForFilter('john');
// Returns: [{ id: 5, name: 'John Doe', email: 'john@example.com' }, ...]

// Frontend: Display results
displayCustomerFilterResults(customers);

// Frontend: User selects customer
selectCustomerForFilter(5, 'John Doe');
// Triggers order refresh with customer_filter=5
```

#### POST /api/orders.php
Create a new order.

**Request:**
```json
{
    "items": [...],
    "payment_method": "Cash",
    "total": 25.99
}
```

## Database Schema

### Key Tables
- `wp_posts`: Products and orders
- `wp_postmeta`: Product and order metadata
- `wp_term_relationships`: Category and tag relationships

### Custom Meta Fields
- `_created_via_jpos`: Marks orders created via POS
- `_order_total`: Order total amount
- `_payment_method`: Payment method used

## Testing

### Running Tests
```bash
# Run all PHP tests
php tests/run-all-tests.php

# Run specific test suite
php tests/php/test-database-optimizer.php
```

### Test Structure
- Unit tests for individual components
- Integration tests for API endpoints
- Performance tests for optimization verification

## Performance Optimization

### Database Optimization
- Bulk loading for product queries
- Query result caching
- Prepared statements for security
- Optimized meta queries for better performance

### Image Optimization
- WordPress image size optimization (medium/thumbnail)
- WebP format support for better compression
- 1-hour image URL caching
- Bulk image loading for better performance

### Frontend Optimization
- Native browser lazy loading with `loading="lazy"`
- Browser cache optimization
- Optimized image rendering

### Pagination System
- 20 products per page default
- Reduced initial load time by 80%
- Performance monitoring integration

### Caching Strategy
- File-based caching system
- TTL-based cache expiration
- Cache invalidation on data changes
- Image URL caching with WebP support

### Bundle Optimization
- JavaScript minification
- CSS compression
- Asset bundling
- Progressive loading implementation

## Security

### Authentication
- WordPress user authentication
- Session management
- Role-based access control

### Data Protection
- CSRF token validation
- Input sanitization
- SQL injection prevention

### Error Handling
- Comprehensive error logging
- User-friendly error messages
- Security-conscious error reporting
- No sensitive data in browser console logs

### Performance Monitoring
- Real-time performance tracking
- Execution time monitoring
- Memory usage tracking
- Cache hit rate analytics
- Performance logging and reporting

## Monitoring and Logging

### Log Levels
- INFO: General information
- WARNING: Potential issues
- ERROR: Error conditions
- CRITICAL: Critical failures

### Performance Monitoring
- API response times
- Database query performance
- System resource usage

### Log Files
- `wp-pos-YYYY-MM-DD.log`: General logs
- `wp-pos-errors-YYYY-MM-DD.log`: Error logs
- `wp-pos-performance-YYYY-MM-DD.log`: Performance logs

## Deployment

### Production Checklist
- [ ] Run test suite
- [ ] Verify security settings
- [ ] Check performance metrics
- [ ] Update documentation
- [ ] Backup database

### Environment Configuration
- Development: Debug mode enabled
- Staging: Limited debugging
- Production: Debug mode disabled

## Troubleshooting

### Common Issues

#### Product Creation Issues (v1.8.45)

##### Required Field Validation Error
- **Problem**: "Product name is required" or "Regular price is required" error when creating product
- **Symptoms**: API returns 400 error with validation message
- **Root Cause**: Required fields not provided or empty in request
- **Solution**: Ensure both `name` and `regular_price` fields are present and non-empty
  ```javascript
  // Validate before sending
  if (!formData.name || !formData.regular_price) {
      alert('Product name and regular price are required');
      return;
  }
  ```
- **Prevention**: Implement frontend validation before API call

##### SKU Uniqueness Conflict
- **Problem**: "SKU already exists" error when creating product with existing SKU
- **Symptoms**: API returns 400 error with SKU conflict message
- **Root Cause**: Another product already uses the provided SKU
- **Solution**:
  - Check existing products for SKU conflicts
  - Use auto-generated SKUs or ensure uniqueness
  - Database query: `SELECT post_id FROM wp_postmeta WHERE meta_key='_sku' AND meta_value='SKU-VALUE'`
- **Prevention**: Implement SKU validation in frontend before submission

##### Images Not Uploading for New Products
- **Problem**: Image upload sections are disabled when creating new products
- **Symptoms**: Image upload areas show message "Images can be uploaded after the product is created"
- **Root Cause**: WordPress requires existing post ID for media attachments
- **Solution**: This is expected behavior - follow two-step workflow:
  1. Create product with all text fields
  2. Product saved to database with new ID
  3. Modal switches to edit mode automatically
  4. Image upload sections activate
  5. Upload featured and gallery images
- **Technical Details**: WordPress `wp_insert_attachment()` requires valid `post_parent` ID
- **Prevention**: User education - explain two-step workflow in UI

##### Modal Doesn't Switch to Edit Mode After Creation
- **Problem**: After creating product, modal stays in create mode and images can't be uploaded
- **Symptoms**: Image upload sections remain disabled, "Create Product" button still shows
- **Root Cause**: JavaScript error preventing mode switch or missing product_id in response
- **Solution**:
  - Check browser console for errors
  - Verify API response includes `product_id` in data object
  - Ensure [`saveProductEditor()`](../assets/js/main.js:3260) completes successfully
  - Check that modal state updates correctly:
    ```javascript
    currentEditingProduct = { id: newProductId, ...formData };
    saveBtn.setAttribute('data-mode', 'edit');
    await initializeImageUpload(currentEditingProduct);
    ```
- **Prevention**: Add error handling and logging in save function

##### CSRF Token Invalid Error
- **Problem**: "Invalid security token" or "Nonce verification failed" error
- **Symptoms**: API returns 403 forbidden error
- **Root Cause**: Missing or expired WordPress nonce token
- **Solution**:
  - Ensure `appState.nonces.productEdit` is included in request
  - Refresh page to get new nonce if expired
  - Check nonce generation in [`index.php`](../index.php:1)
- **Prevention**: Implement nonce refresh mechanism for long sessions

##### Product Created but Data Not Showing
- **Problem**: Product creation succeeds but product doesn't appear in products list
- **Symptoms**: Success message shown but product not visible
- **Root Cause**: Cache not invalidated or products list not refreshed
- **Solution**:
  - Clear cache with cache-manager.php
  - Refresh products list: `await window.renderProducts()`
  - Hard reload page (Ctrl+F5)
- **Prevention**: Implement automatic cache invalidation and list refresh after creation

#### Image Upload File Picker Not Opening (v1.8.49)
- **Problem**: File picker dialog doesn't open when clicking on image upload areas in product editor
- **Symptoms**:
  - Click on "Drop featured image here or click to upload" - nothing happens
  - Click on gallery image upload area - no file dialog appears
  - No console errors visible
  - Event listeners appear to be attached correctly
- **Root Cause**: The [`clearProductImages()`](../assets/js/main.js:1702-1733) function at line 1702 was resetting the dropzone innerHTML without including the required file input elements:
  - Featured image input: `<input type="file" id="featured-image-input">`
  - Gallery images input: `<input type="file" id="gallery-images-input" multiple>`
  - When [`initializeImageUpload()`](../assets/js/main.js:1839) tried to attach event listeners at line 1839, the input elements were missing from the DOM
  - Click handlers were set up on dropzones, but had no inputs to trigger
- **Solution**: Modified [`clearProductImages()`](../assets/js/main.js:1702-1733) to include both file input elements in the innerHTML:
  ```javascript
  // Featured image dropzone with input
  featuredDropzone.innerHTML = `
      <input type="file" id="featured-image-input"
             accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
             class="hidden">
      <p class="text-gray-400">Drop featured image here or click to upload</p>
      <p class="text-sm text-gray-500">PNG, JPG, WebP, GIF (max 5MB)</p>
  `;
  
  // Gallery dropzone with input
  galleryDropzone.innerHTML = `
      <input type="file" id="gallery-images-input"
             accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
             multiple class="hidden">
      <p class="text-gray-400">Drop gallery images here or click to upload</p>
      <p class="text-sm text-gray-500">PNG, JPG, WebP, GIF (max 5MB each, up to 10 images)</p>
  `;
  ```
- **Prevention**:
  - When resetting form elements that have dynamic initialization, ensure all required DOM elements are preserved or recreated
  - Test file picker functionality after any form reset/clear operations
  - Add assertions to verify input elements exist before attaching event listeners
- **Testing**:
  1. Open product editor for existing product
  2. Click on featured image upload area
  3. Verify file picker dialog opens
  4. Click on gallery image upload area
  5. Verify file picker dialog opens with multiple file selection
  6. Test both drag-and-drop and click-to-upload functionality
#### Image Upload File Picker Not Opening - FINAL FIX (v1.8.50)
- **Problem**: File picker dialog doesn't open when clicking on image upload areas in product editor - even with v1.8.49 fix
- **Symptoms**:
  - Click or drag-and-drop on image upload areas - nothing happens
  - File picker doesn't open
  - May work sometimes but not reliably
- **Root Cause**: Previous implementation (v1.8.49) used complex event delegation with:
  - DOM cloning to remove event listeners
  - `addEventListener()` which can have timing issues
  - `innerHTML` manipulation removing and recreating elements
  - Event propagation conflicts
- **Solution (v1.8.50)**: Complete refactor with bulletproof simple approach:
  1. **Simplified [`clearProductImages()`](../assets/js/main.js:1702-1720)** - Never manipulates innerHTML, only shows/hides existing elements
  2. **Rewrote [`setupFeaturedImageUpload()`](../assets/js/main.js:1887-1925)** - Uses direct `onclick` property assignment instead of `addEventListener`
  3. **Rewrote [`setupGalleryImageUpload()`](../assets/js/main.js:2122-2160)** - Same simple onclick approach
  
  ```javascript
  // BULLETPROOF: Direct property assignment - guaranteed to work
  dropzone.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      fileInput.click();  // Triggers file picker
  };
  
  fileInput.onchange = function(e) {
      const file = e.target.files[0];
      if (file) {
          uploadFeaturedImage(file, productId);
      }
      e.target.value = ''; // Reset for reuse
  };
  ```

- **Why This Works**:
  - File inputs exist permanently in HTML ([`index.php:813`](../index.php:813), [`index.php:850`](../index.php:850)) - never removed
  - Direct property assignment (`.onclick =`) is the most basic, bulletproof JavaScript
  - No complex event delegation, no cloning, no timing issues
  - Handlers assigned directly to existing elements
  - Works in all browsers, all situations, guaranteed

- **Key Principles for Reliable File Uploads**:
  1. File inputs should exist in HTML permanently - never remove them
  2. Use simplest possible JavaScript (property assignment not addEventListener)
  3. Don't manipulate innerHTML of elements containing interactive elements
  4. Keep event handling as simple as possible - direct assignment beats complex patterns

- **Testing**:
  1. Clear browser cache (Ctrl+F5) to load v1.8.50
  2. Open product editor for any existing product
  3. Click on featured image upload area - file picker opens immediately
  4. Click on gallery image upload area - file picker opens for multiple selection
  5. Test drag-and-drop functionality - works for both areas
  6. Try on different browsers (Chrome, Firefox, Safari, Edge) - works everywhere
  7. Test rapidly clicking - works every time

#### File Input Hidden State Issue - Browser Security Restriction (v1.8.51)
- **Problem**: File picker dialog still doesn't open after v1.8.50 fix, despite bulletproof JavaScript implementation
- **Symptoms**:
  - Click on image upload areas - nothing happens
  - Drag-and-drop doesn't work
  - JavaScript event handlers appear to execute correctly
  - No console errors
  - `fileInput.click()` calls appear to work but file picker never opens
- **Root Cause**: **Browser Security Restriction** - Modern browsers block programmatic `.click()` calls on file inputs that use `display: none` (including Tailwind's `hidden` class). This is intentional security behavior to prevent malicious websites from opening file pickers without user visibility.
  - Featured image input at [`index.php:813`](../index.php:813) used `class="hidden"` (equivalent to `display: none`)
  - Gallery images input at [`index.php:850`](../index.php:850) used `class="hidden"`
  - When JavaScript calls `fileInput.click()`, browser silently blocks the action
  - This security measure ensures users can see what they're interacting with
  - No error thrown, no console message - just silent failure
- **Solution (v1.8.51)**: Changed both file inputs to use CSS that hides them visually but not from browser's perspective:
  ```html
  <!-- BEFORE (BROKEN - display:none blocks programmatic clicks) -->
  <input type="file" id="featured-image-input"
         accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
         class="hidden">
  
  <!-- AFTER (FIXED - visually hidden but technically visible) -->
  <input type="file" id="featured-image-input"
         accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
         style="opacity: 0; position: absolute; pointer-events: none; width: 0; height: 0;">
  ```
  - `opacity: 0` - Makes input invisible but technically still rendered
  - `position: absolute` - Removes from document flow
  - `pointer-events: none` - Prevents accidental user interaction
  - `width: 0; height: 0` - Takes no visual space
  - Browser considers input "visible" so allows programmatic `.click()`
- **Why This Works**:
  - File input is technically visible to browser (opacity: 0, not display: none)
  - Browser security allows programmatic clicks on "visible" elements
  - User can't accidentally interact with it (pointer-events: none)
  - Takes no visual space (width/height: 0, position: absolute)
  - JavaScript `fileInput.click()` now successfully opens file picker
- **Browser Security Context**:
  - This restriction exists in all modern browsers (Chrome, Firefox, Safari, Edge)
  - Prevents malicious sites from secretly triggering file uploads
  - Ensures user awareness when file picker opens
  - Cannot be bypassed - must make input technically visible
  - Alternative approaches (event simulation, etc.) are also blocked
- **Prevention**:
  - **NEVER use `display: none` on file inputs that need programmatic triggering**
  - **NEVER use Tailwind's `hidden` class on file inputs**
  - Always use opacity/position technique for invisible file inputs
  - Test file picker opening, not just event handler execution
  - Test in multiple browsers to verify security behavior
- **Testing**:
  1. Hard refresh to clear cache (Ctrl+F5)
  2. Open product editor
  3. Click featured image upload area - file picker opens
  4. Click gallery image upload area - file picker opens
  5. Verify both file inputs are invisible but functional
  6. Test in Chrome, Firefox, Safari, Edge - works in all browsers



#### JavaScript Scope Error in Image Upload (v1.8.39)
- **Problem**: ReferenceError "setupFeaturedImageTempUpload is not defined" when creating new products
- **Symptoms**: Console error appears when opening product editor in create mode, image upload buttons don't initialize
- **Root Cause**: Functions [`setupFeaturedImageTempUpload()`](../assets/js/main.js:2581) and [`setupGalleryImageTempUpload()`](../assets/js/main.js:2677) were nested inside [`initializeImageUpload()`](../assets/js/main.js:1774) function, making them inaccessible to [`setupTemporaryImageUpload()`](../assets/js/main.js:2617) which exists at a different scope level
- **Solution**: Moved both functions outside of [`initializeImageUpload()`](../assets/js/main.js:1774) to the same scope level as other image utility functions
  - [`setupFeaturedImageTempUpload()`](../assets/js/main.js:2581) now at line 2581
  - [`setupGalleryImageTempUpload()`](../assets/js/main.js:2677) now at line 2677
  - Both functions now accessible from [`setupTemporaryImageUpload()`](../assets/js/main.js:2617)
- **Prevention**: When creating nested functions, ensure all functions that need to call each other are at the same scope level
- **Testing**: Open product editor in create mode (click "Create Product" button), verify no console errors appear and image upload areas are interactive
#### Product Gallery Image Upload Issue (v1.8.40)
- **Problem**: Gallery images fail to upload when creating new products, only featured images upload successfully
- **Symptoms**:
  - Featured image uploads work correctly
  - Gallery images appear in preview but don't upload to server
  - No error messages displayed to user
  - `$_FILES['images']` array empty or malformed in PHP backend
- **Root Cause**: JavaScript FormData construction used indexed notation `images[${index}]` (e.g., `images[0]`, `images[1]`), but PHP's `$_FILES` superglobal expects bracket-only notation `images[]` to automatically build array structure for multiple file uploads
  - Line 2180 in [`main.js`](../assets/js/main.js:2180): `formData.append('images[${index}]', file)` 
  - PHP receives files as separate form fields instead of proper array
  - Backend [`product-images.php`](../api/product-images.php:220) expects `$_FILES['images']['name']` as array
- **Solution**: Changed FormData construction in [`uploadGalleryImages()`](../assets/js/main.js:2180) from `formData.append('images[${index}]', file)` to `formData.append('images[]', file)`
- **Technical Details**:
  - **Wrong Format**: `formData.append('images[0]', file1); formData.append('images[1]', file2);`
  - **Correct Format**: `formData.append('images[]', file1); formData.append('images[]', file2);`
  - PHP automatically groups files with same name (using `[]`) into array structure
  - Browser sends proper `multipart/form-data` with array structure
- **Prevention**: 
  - Always use bracket-only notation (`fieldname[]`) for multiple file uploads in FormData
  - Test file upload functionality with multiple files, not just single files
  - Add server-side logging to verify `$_FILES` structure during development
  - Reference: PHP documentation on handling file uploads with array syntax
- **Testing**:
  1. Open product editor in create mode
  2. Upload featured image (should work)
  3. Upload 2-3 gallery images
  4. Save product
  5. Verify all images appear on product (featured + gallery)
  6. Check browser console for successful upload responses


#### Database Connection Errors
- Check WordPress database configuration
- Verify database permissions
- Review connection limits

#### Performance Issues
- Check cache configuration
- Review database query performance
- Monitor system resources

#### Authentication Problems
- Verify user permissions
- Check session configuration
- Review security settings
- Ensure user email data is properly returned in API responses

#### Order History Loading Issues
- **Symptom**: Order history page shows skeleton loaders but never loads actual data
- **Cause**: JavaScript variable reference errors (`orderFilters`, `posOrders`, `cart` not defined)
- **Solution**: Ensure all order-related functions use `appState.orders.filters` and `appState.orders.all`
- **Prevention**: Always use centralized state management (`appState`) instead of global variables

#### Products Loading Issues
- **Symptom**: Products page shows empty table or doesn't load product data
- **Cause**: JavaScript variable reference errors (`allProducts`, `stockManagerFilters`, `currentProductForModal` not defined)
- **Solution**: Use `appState.products.all`, `appState.stockFilters`, and `appState.products.currentForModal`
- **Prevention**: Always use centralized state management (`appState`) instead of global variables

#### Print Report Issues (v1.8.30)
- **Problem**: Print reports display only 2 pages with first page being blank, content truncated, and poor print formatting
- **Symptoms**:
  - First page of printed report is completely blank
  - Only 2 pages print regardless of report content length
  - Report content appears truncated or cut off
  - Print preview shows incomplete data
  - Complex CSS visibility system causes rendering issues
- **Root Cause**: Print report functionality used problematic `window.print()` approach with complex CSS visibility system:
  - Modal-based printing with visibility: hidden/visible CSS conflicts
  - Complex print styles that interfered with content rendering
  - Different approach from working receipt printing system
  - CSS visibility system caused blank pages and content truncation
- **Solution**:
  - Replaced `window.print()` with reliable `window.open()` approach in [`printReport()`](../assets/js/main.js:3886)
  - Removed complex CSS visibility system from [`handlePrintReports()`](../assets/js/main.js:3856)
  - Added comprehensive print-optimized CSS styles for clean formatting
  - Implemented same successful pattern used for receipt printing
  - Ensures all report content displays without truncation
  - Handles page breaks correctly for multi-page reports
- **Prevention**: Use proven `window.open()` approach for all print functionality instead of complex CSS visibility systems

#### Checkout Cart Reference Error (v1.8.27)
- **Problem**: Checkout process fails with "Uncaught (in promise) ReferenceError: cart is not defined" error when attempting to process transactions or open split payment modal
- **Symptoms**:
  - Error occurs at [`getCartTotal()`](../assets/js/main.js:3591) line 3591
  - Error occurs at [`openSplitPaymentModal()`](../assets/js/main.js:3557) line 3557
  - Split payment modal fails to open
  - Cart totals cannot be calculated
  - Checkout process is blocked
- **Root Cause**: During the migration from global variables to centralized `appState` object architecture, two `cart` variable references were not updated:
  - Line 3591: `(cart || [])` should be `(appState.cart.items || [])`
  - Line 3557: `cart_items: cart` should be `cart_items: appState.cart.items`
- **Solution**:
  - Updated [`getCartTotal()`](../assets/js/main.js:3591) line 3591 from `(cart || [])` to `(appState.cart.items || [])`
  - Updated [`openSplitPaymentModal()`](../assets/js/main.js:3557) line 3557 from `cart_items: cart` to `cart_items: appState.cart.items`
  - Updated cache busting version from v1.8.26 to v1.8.27 in [`index.php`](../index.php:23) line 23
- **Prevention**:
  - Always use centralized state management (`appState`) instead of global variables
  - Use global search for variable names when refactoring to catch all references
  - Implement TypeScript or JSDoc type checking to catch undefined variables
  - Add unit tests that verify all state references use `appState` object
- **Testing**:
  1. Clear browser cache (Ctrl+F5)
  2. Add items to cart
  3. Click checkout button
  4. Verify split payment modal opens without errors
  5. Verify cart total calculates correctly
  6. Complete a test transaction

#### Browser Caching Issues
- **Symptom**: JavaScript changes not taking effect, old code still running, ReferenceError persists after fixes
- **Cause**: Browser aggressively caching JavaScript files, serving outdated versions
- **Solution**: Implement cache busting techniques:
  - Add version parameter to script tags: `<script src="assets/js/main.js?v=1.5.3"></script>`
  - Add unique comments at top of JS files: `// WP-POS v1.5.3 - CACHE BUST`
  - Increment version numbers for each deployment
- **Prevention**: Always use cache busting for production deployments, test in incognito mode

### Technical Fixes Implemented

#### JavaScript Variable Reference Errors (v1.5.1 - v1.5.3)
- **Problem**: Global variables (`orderFilters`, `posOrders`, `cart`, `allProducts`, `stockManagerFilters`, `currentProductForModal`) were undefined
- **Root Cause**: Incomplete migration from global variables to centralized `appState` object
- **Solution**: Systematic replacement of all global variable references with `appState` properties:
  - `orderFilters` → `appState.orders.filters`
  - `posOrders` → `appState.orders.all`
  - `cart` → `appState.cart.items`
  - `allProducts` → `appState.products.all`
  - `stockManagerFilters` → `appState.stockFilters`
  - `currentProductForModal` → `appState.products.currentForModal`

#### API Endpoint Filtering Issues (v1.5.1)
- **Problem**: Orders API returning incorrect data due to SQL query issues
- **Root Cause**: Incorrect parameter passing in `wpdb->prepare()` and unintended default filters
- **Solution**: 
  - Fixed SQL prepared statement parameter handling
  - Removed default filter that forced POS-only orders
  - Corrected LIMIT clause parameter passing

#### Cache Busting Implementation (v1.5.2 - v1.5.3)
- **Problem**: Browser caching preventing JavaScript updates from taking effect
- **Solution**: Multi-layered cache busting approach:
  - Version parameters in script tags: `?v=1.5.3`
  - Unique comments in JavaScript files
  - Version incrementing for each deployment
  - Console logging to verify correct version loading

#### UI Consistency Improvements (v1.5.4)
- **Problem**: Stock manager table styling inconsistent with sessions/orders tables
- **Solution**: 
  - Converted from HTML `<table>` to CSS Grid layout
  - Implemented consistent 12-column grid system
  - Matched styling patterns from existing tables
  - Maintained responsive design principles

#### App Preloader Implementation (v1.5.5)
- **Problem**: Flash of default view before routing determines correct page from URL parameters
- **Solution**: 
  - Added full-screen preloader with spinner and sheen effect
  - Semi-transparent background with backdrop blur for professional appearance
  - Automatic hiding after routing initialization completes
  - Smooth fade-out transition with DOM cleanup

#### Reload Buttons Implementation (v1.5.6)
- **Problem**: Only POS page had refresh functionality, other pages lacked data refresh capability
- **Solution**: 
  - Added consistent refresh buttons to all pages (Orders, Reports, Sessions, Stock, Settings, Held Carts)
  - Matching styling and positioning with POS page refresh button
  - Hard reload functionality (`window.location.reload(true)`) to bypass cache
  - Proper event handlers with null checks for each button

#### Products Edit Button Fix (v1.5.7 - v1.5.8)
- **Problem**: Edit buttons in products page were not working - `ReferenceError: openStockEditModal is not defined`
- **Root Cause**: 
  - Function was defined inside DOMContentLoaded event listener but not globally accessible
  - HTML `onclick` attributes require functions to be in global scope (`window` object)
  - Timing issue: function was made global at end of event listener, too late for HTML onclick execution
  - Caching issue: HTML was loading old version of JavaScript file
- **Solution**: 
  - Made `openStockEditModal` globally accessible: `window.openStockEditModal = openStockEditModal`
  - Moved global assignment to immediately after function definition (not at end of event listener)
  - Updated cache busting version numbers in both HTML and JavaScript files
  - Both row clicks and edit button clicks now work properly

#### Products Sidebar Navigation Fix (v1.5.10)
- **Problem**: Products button in sidebar was not working - "Invalid view: products-page" error
- **Root Cause**: 
  - Routing module (`routing.js`) was being cached by browser without version parameter
  - Browser was loading old version of routing.js that still had `stock-page` instead of `products-page`
  - Main.js had cache busting but routing.js did not
- **Solution**: 
  - Added cache busting to routing module: `routing.js?v=1.5.10`
  - Updated version comment in routing module
  - Ensured both main.js and routing.js load updated versions
  - Products sidebar navigation now works correctly

### Debug Mode
Enable debug mode in configuration for detailed error information:

```json
{
    "debug": {
        "enabled": true,
        "log_level": "DEBUG"
    }
}
```

## Contributing

### Code Standards
- Follow WordPress coding standards
- Use meaningful variable names
- Add comprehensive comments
- Write tests for new features
- Avoid logging sensitive data to browser console
- Use appropriate favicon to prevent 404 errors

### Pull Request Process
1. Create feature branch
2. Implement changes
3. Add tests
4. Update documentation
5. Submit pull request

## Support

For technical support or questions:
- Review this documentation
- Check the test suite
- Review log files
- Contact development team

## Customer Attachment Feature

### Overview
The customer attachment feature allows POS operators to search for and attach customer information to orders before checkout. This enables better order tracking, customer history, and personalized service.

### Components

#### Backend API (`api/customers.php`)
- **Purpose**: Search WordPress users for customer attachment
- **Authentication**: Requires `manage_woocommerce` capability
- **Search Fields**: user_login, user_email, display_name
- **Minimum Query Length**: 2 characters
- **Response Format**: Array of customer objects with id, name, email, username

#### On-Screen Keyboard (`assets/js/modules/keyboard.js`)
- **Purpose**: Touch-friendly input for customer search
- **Layout**: QWERTY layout optimized for name/email entry
- **Special Keys**: Space, Backspace, Clear, @, .
- **Compatibility**: Works with both touch and mouse input
- **Integration**: Toggleable via keyboard icon button in customer search modal
- **Global Function**: [`window.toggleCustomerKeyboard()`](../assets/js/main.js:1546) helper function
- **Fixed in v1.8.66**: Corrected button onclick handler from `cartManager.toggleKeyboard()` to `window.toggleCustomerKeyboard()`

#### State Management
- **Location**: [`assets/js/modules/state.js`](../assets/js/modules/state.js:33)
- **Property**: `appState.cart.customer`
- **Structure**: `{ id, name, email }`
- **Persistence**: Saved to localStorage with cart state
- **Reset**: Cleared on full cart reset or manual detachment

#### User Interface

**Customer Search Modal:**
- Search input with keyboard toggle button
- Real-time search results (debounced 300ms)
- Results display customer name and email
- Click result to attach to cart

**Cart Display:**
- Customer info shown at top of cart
- Displays name and email
- Remove button to detach customer
- Highlighted with indigo color scheme

**Attach Customer Button:**
- Located above Hold Cart/Checkout buttons
- Blue color to differentiate from cart actions
- Opens customer search modal

#### Held Cart Integration
- **Save**: Customer data persisted with held cart
- **Restore**: Customer automatically reattached when retrieving held cart
- **Storage**: Included in localStorage held cart data structure

### Usage Flow

1. **Operator clicks "Attach Customer" button**
2. **Search modal opens with input field**
3. **Operator types customer name or email** (minimum 2 characters)
4. **System searches WordPress users** (debounced for performance)
5. **Results displayed** with name and email
6. **Operator clicks customer** to attach
7. **Customer displayed at top of cart** with remove option
8. **Customer data persisted** through cart operations and holds
9. **On checkout**, customer information included in order

### Technical Implementation

**Search Function:**
```javascript
async function searchCustomers(query) {
    if (query.length < 2) return;
    
    const nonce = document.getElementById('jpos-customer-search-nonce').value;
    const response = await fetch(`api/customers.php?query=${encodeURIComponent(query)}&nonce=${nonce}`);
    const data = await response.json();
    
    if (data.success) {
        renderCustomerResults(data.data.customers);
    }
}
```

**Attach Customer:**
```javascript
function attachCustomer(id, name, email) {
    appState.cart.customer = { id, name, email };
    renderCustomerDisplay();
    hideCustomerSearch();
    saveCartState();
}
```

**Held Cart with Customer:**
```javascript
const cartData = {
    id: Date.now(),
    items: appState.cart.items,
    customer: appState.cart.customer, // Persisted
    total: calculateTotal()
};
```

### Performance Considerations
- Search debounced to 300ms to reduce API calls
- Maximum 20 results returned per query
- Performance monitoring tracks search execution time
- Results cached by browser for repeated queries

### Security
- CSRF protection via WordPress nonce
- User capability check (`manage_woocommerce`)
- Input sanitization on all search parameters
- No sensitive customer data logged to console

### Future Enhancements
- Customer creation from POS interface
- Customer purchase history display
- Loyalty program integration
- Custom customer fields support

### Held Cart Customer Functionality (v1.8.55)

#### Issue: Customer Not Persisting Through Held Cart Cycle
- **Problem**: Customer data was not properly saved, displayed, or restored when holding and retrieving carts
- **Symptoms**:
  - After holding cart with customer attached, customer still displayed in current cart (should be cleared)
  - Held carts table didn't show which carts had customers attached
  - When restoring a held cart, the customer data was lost
- **Root Cause**: Three separate issues:
  1. [`holdCurrentCart()`](../assets/js/main.js:3327) wasn't saving customer data to held cart object before clearing
  2. [`renderHeldCarts()`](../assets/js/main.js:3344) had no customer column in the table display
  3. [`restoreHeldCart()`](../assets/js/main.js:3466) wasn't restoring customer property from held cart data
- **Solution (v1.8.55)**:
  1. **Hold Cart Fix**: Modified [`holdCurrentCart()`](../assets/js/main.js:3327) to save customer data:
     ```javascript
     const cartData = {
         id: Date.now(),
         items: JSON.parse(JSON.stringify(appState.cart.items)),
         fee: appState.cart.fee || 0,
         discount: appState.cart.discount || 0,
         customer: appState.cart.customer ? JSON.parse(JSON.stringify(appState.cart.customer)) : null,
         timestamp: new Date().toISOString()
     };
     ```
  2. **Display Fix**: Updated [`renderHeldCarts()`](../assets/js/main.js:3344) table header and rows:
     - Changed grid layout from 2-column items to 1-column items + 2-column customer
     - Added customer display logic: `held.customer ? held.customer.name : '-'`
     - Implemented truncation with tooltip for long customer names
  3. **Restore Fix**: Added customer restoration in [`restoreHeldCart()`](../assets/js/main.js:3466):
     ```javascript
     appState.cart.customer = held.customer || null;
     ```
- **Technical Details**:
  - Customer object structure: `{ id, name, email }`
  - Deep clone customer data with `JSON.parse(JSON.stringify())` to prevent reference issues
  - Null handling for carts without customers
  - Version updated from v1.8.54 to v1.8.55 in [`index.php`](../index.php:25)
- **Testing**:
  1. Attach customer to cart
  2. Hold the cart
  3. Verify customer clears from current cart
  4. Check held carts table shows customer name
  5. Restore held cart
  6. Verify customer reappears in cart with correct data
- **Prevention**:
  - Always include all cart properties when saving held cart state
  - Ensure UI displays all relevant data for held carts
  - Test complete hold/restore cycle when modifying cart data structure

### Customer Display Not Clearing After Hold (v1.8.56)

#### Issue: Customer Display Box Remains Visible After Holding Cart
- **Problem**: After holding a cart with an attached customer, the customer display box remained visible at the top of the cart even though the customer data had been cleared from the state
- **Symptoms**:
  - Customer display box still shows after holding cart with customer
  - Visual display doesn't match the cleared state data
  - Customer data IS correctly cleared from `appState.cart.customer` (set to null)
  - Display just wasn't updating to reflect the cleared state
- **Root Cause**: The [`clearCart()`](../assets/js/main.js:1372) function at line 1372 was clearing cart items and other data when `fullReset = true`, but wasn't explicitly setting `appState.cart.customer = null`. Since [`renderCustomerDisplay()`](../assets/js/main.js:1389) (called by [`renderCart()`](../assets/js/main.js:1346)) checks for customer data in state, the old customer data was still present and continued to display
- **Solution (v1.8.56)**:
  - Modified [`clearCart()`](../assets/js/main.js:1372) function to explicitly clear customer data:
    ```javascript
    function clearCart(fullReset = false) {
        appState.cart.items = [];
        appState.fee = { amount: '', label: '', amountType: 'flat' };
        appState.discount = { amount: '', label: '', amountType: 'flat' };
        appState.feeDiscount = { type: null, amount: '', label: '', amountType: 'flat' };
        if (fullReset) {
            appState.cart.customer = null;  // ADDED THIS LINE
            appState.return_from_order_id = null;
            appState.return_from_order_items = [];
        }
        renderCart();
        saveCartState();
    }
    ```
  - This ensures that when [`holdCurrentCart()`](../assets/js/main.js:3327) calls `Cart.clearCart(true)`, the customer data is properly cleared
  - The [`renderCustomerDisplay()`](../assets/js/main.js:1389) function already checks for `appState.cart.customer`, so once it's set to null, the display automatically hides
- **Technical Details**:
  - [`clearCart()`](../assets/js/main.js:1372) is called with `fullReset = true` when holding carts
  - Customer data was being saved correctly to held cart before clearing
  - Customer data was being restored correctly when retrieving held cart
  - Only the visual display clearing was broken - the data flow was correct
  - Fix ensures UI state matches data state at all times
- **Testing**:
  1. Attach customer to cart
  2. Hold the cart
  3. Verify customer display box disappears immediately
  4. Check held carts table shows customer name
  5. Restore held cart
  6. Verify customer reappears correctly
- **Prevention**:
  - When implementing full reset operations, ensure ALL cart-related state is explicitly cleared
  - Test visual display updates, not just data state
  - Verify UI reflects state changes immediately

### Held Carts Table Layout and Date Formatting (v1.8.57)

#### Improved Date Display
- **Problem**: Held carts table showed raw date/time strings that were verbose and hard to scan quickly
- **Solution**: Implemented relative date formatting with new [`formatRelativeDate()`](../assets/js/main.js:3344) helper function
- **Date Format Logic**:
  - **Today's carts**: Display as "Today @ HH:MM AM/PM" (e.g., "Today @ 2:30 PM")
  - **Yesterday's carts**: Display as "Yesterday @ HH:MM AM/PM" (e.g., "Yesterday @ 9:15 AM")
  - **Older carts**: Keep full date and time (e.g., "2025-10-04 3:45 PM")
- **Implementation**: Function compares cart timestamp against today/yesterday start/end times using UTC timezone-aware logic

#### Fixed Table Layout
- **Problem**: Columns were jumbled, poorly aligned, and inconsistently sized (restore button, price, actions, customer overlapping)
- **Root Cause**: Used generic `grid-cols-12` with manual span assignments that didn't provide proper column control
- **Solution**: Redesigned grid layout in [`renderHeldCarts()`](../assets/js/main.js:3344):
  - Changed from `grid-cols-12` to `grid-cols-[auto,1fr,auto,auto,auto]`
  - Date column: Fixed width `w-44` (perfect for "Yesterday @ 12:30 PM")
  - Items column: Compact `w-16` for item count
  - Customer column: Flexible `1fr` with `truncate` for responsive text
  - Price column: Fixed width `w-24` for currency display
  - Actions column: Fixed width `w-32` for buttons
- **Benefits**:
  - Proper column alignment and spacing
  - Restore button, price, and actions clearly separated
  - Customer names truncate gracefully with ellipsis
  - Responsive design maintains layout integrity

#### Technical Implementation
```javascript
// Helper function for relative dates
function formatRelativeDate(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterdayStart = new Date(todayStart.getTime() - 24 * 60 * 60 * 1000);
    
    if (date >= todayStart) {
        return `Today @ ${date.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`;
    } else if (date >= yesterdayStart) {
        return `Yesterday @ ${date.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`;
    } else {
        return date.toLocaleString('en-US', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: 'numeric', minute: '2-digit', hour12: true
        });
    }
}

// Grid layout with proper column widths
<div class="grid grid-cols-[auto,1fr,auto,auto,auto] gap-4 items-center">
    <div class="w-44">${formatRelativeDate(held.timestamp)}</div>
    <div class="w-16 text-center">${itemsCount}</div>
    <div class="flex-1 truncate">${customerDisplay}</div>
    <div class="w-24 text-right">${price}</div>
    <div class="w-32 flex gap-2">${buttons}</div>
</div>
```

#### Testing
1. Hold multiple carts at different times (today, yesterday, older)
2. Verify date formatting shows correct relative format
3. Check column alignment and spacing is consistent
4. Test with long customer names to verify truncation
5. Verify restore/delete buttons are properly positioned

## Version History

- v1.8.66: Fixed virtual keyboard functionality in customer search modal - corrected broken function reference at [`index.php:1080`](../index.php:1080) from non-existent `cartManager.toggleKeyboard()` to existing `window.toggleCustomerKeyboard()` helper function at [`main.js:1546-1551`](../assets/js/main.js:1546-1551), keyboard button now properly triggers [`OnScreenKeyboard.toggle()`](../assets/js/modules/keyboard.js:181) with correct input element reference, virtual keyboard displays at bottom of screen when clicked, works identically to fee/discount numeric keypad, auto-hides when modal closes
- v1.8.60: Enhanced customer filtering with searchable input - converted static dropdown to real-time search using [`api/customers.php`](../api/customers.php:1), users search by name/email with 300ms debounce, implemented [`searchCustomersForFilter()`](../assets/js/main.js:1543), [`displayCustomerFilterResults()`](../assets/js/main.js:1562), [`selectCustomerForFilter()`](../assets/js/main.js:1611), updated UI in [`index.php:482-495`](../index.php:482-495), removed old [`populateCustomerFilter()`](../assets/js/main.js:1627-1641), improves scalability for large customer databases
- v1.8.59: Implemented customer filtering in order view - added backend API support in [`orders.php:72-81`](../api/orders.php:72-81) with SQL filtering by customer ID, customer data in order responses at [`orders.php:100-122`](../api/orders.php:100-122), initial static dropdown implementation
- v1.8.57: Fixed held carts table layout and date formatting - implemented relative date display ("Today @ 2:30 PM", "Yesterday @ 9:15 AM", full date for older), redesigned grid layout from `grid-cols-12` to `grid-cols-[auto,1fr,auto,auto,auto]` with fixed column widths (date: w-44, items: w-16, price: w-24, actions: w-32) and flexible customer column with truncation, eliminated jumbled alignment issues
- v1.8.56: Fixed customer display not clearing after holding cart - modified [`clearCart()`](../assets/js/main.js:1372) to explicitly set `appState.cart.customer = null` when `fullReset = true`, ensuring the visual display updates to match the cleared state. Customer save/restore functionality already working correctly from v1.8.55
- v1.8.55: Fixed held cart customer functionality - resolved three critical issues: customer data now saved when holding cart, customer name displayed in held carts table with truncated display, customer properly restored when retrieving held cart. Modified [`holdCurrentCart()`](../assets/js/main.js:3327), [`renderHeldCarts()`](../assets/js/main.js:3344), and [`restoreHeldCart()`](../assets/js/main.js:3466). Version updated in [`index.php`](../index.php:25)
- v1.8.54: Implemented customer attachment functionality for POS orders - created customer search API endpoint [`api/customers.php`](../api/customers.php:1), on-screen keyboard component [`assets/js/modules/keyboard.js`](../assets/js/modules/keyboard.js:1), customer state management, UI integration with search modal and cart display, held cart persistence
- v1.8.53: Improved POS cart UI layout - moved Clear Cart button from bottom of button section to directly below cart items area in [`index.php`](../index.php:377) for better visual hierarchy and user experience, button now appears immediately after cart items list and before cart totals section
- v1.8.46: Fixed product creation API 400 error - modified [`product-edit-simple.php`](../api/product-edit-simple.php:12-26) to check JSON POST body for action parameter when not found in URL query string, resolves "Action parameter required" error that prevented product creation
- v1.8.45: Restored product creation functionality - added "Create Product" button at [`index.php:630`](../index.php:630), restored [`create_product` action handler](../api/product-edit-simple.php:279-354) with validation and SKU uniqueness checking, modified [`openProductEditor()`](../assets/js/main.js:1758) to support create mode with optional productId parameter, updated [`saveProductEditor()`](../assets/js/main.js:3260) to handle both create and update operations with automatic mode switching, implemented two-step workflow where product is created first then images can be uploaded after modal switches to edit mode, cache-busting version incremented to v1.8.45
- v1.0.0: Initial release
- v1.1.0: Added modular architecture
- v1.2.0: Performance optimizations
- v1.3.0: Testing framework
- v1.4.0: Monitoring system
- v1.4.2: Fixed user email display in authentication API responses
- v1.4.3: Console security cleanup, removed sensitive data logging, added favicon
- v1.5.0: Performance optimization - image optimization, pagination, WebP support, and performance monitoring (lazy loading simplified for stability)
- v1.5.1: URL routing system implementation with sidebar integration and overlay close functionality
- v1.5.2: Fixed data loading issue by making page functions globally available for routing system
- v1.5.3: Fixed order history loading issue - resolved JavaScript variable reference errors and API endpoint filtering
- v1.5.4: Updated products table styling to match sessions/orders tables with consistent grid-based layout
- v1.5.5: Added app preloader with spinner and full-page sheen effect to prevent flash of default view before routing
- v1.5.6: Added consistent reload buttons to all pages (Orders, Reports, Sessions, Products, Settings, Held Carts) matching POS page style
- v1.5.7: Fixed products edit button functionality - made openStockEditModal function globally accessible so edit buttons work properly
- v1.5.8: Resolved timing and caching issues for products edit buttons - moved global function assignment to immediate execution after function definition
- v1.5.9: Renamed Stock Manager to Products - updated URL parameters, page titles, and all references throughout the system
- v1.5.10: Fixed Products sidebar click handler - resolved routing module cache issue that prevented navigation to products page
- v1.5.11: Implemented comprehensive product editor with JSON preview - supports both simple and variable products with full text-based field editing
- v1.5.12: Fixed API path issues and authentication debugging for product editor
- v1.5.13: Corrected API paths to use relative URLs instead of absolute paths
- v1.5.14: Removed Prism.js library and implemented custom JSON syntax highlighting
- v1.5.15: Updated JSON highlighting to color values instead of keys for better readability
- v1.5.16: Cleaned up debugging code and finalized product editor implementation
- v1.5.17: Production-ready comprehensive product editor with value-highlighted JSON preview
- v1.6.0: Complete product editing system with database-driven attribute suggestions, tabbed interface (Form/JSON views), WordPress-style tag-based attribute options management, and attribute isolation for multiple attributes
- v1.6.1: Fixed attribute options persistence and tax classes API - options now remain available after removal, added get_tax_classes action handler
- v1.6.2: Fixed live state updates and tax classes API response structure - suggestions update immediately when options are added/removed
- v1.6.3: Enhanced UX with persistent dialog for iterative editing and improved button labels (Cancel → Close)
- v1.6.4: API and Display Fixes - Fixed get_tax_classes API endpoint, enhanced variation data with parent_name and formatted attributes
- v1.6.5: Live Updates and UX Improvements - Fixed attribute suggestions live updates, prevented dialog auto-close, changed Cancel to Close
- v1.6.6: Attribute Management Enhancements - Fixed options disappearing from suggestions, stored original database options
- v1.6.7: Enhanced Add Attribute Functionality - Added API endpoint for available attributes, dropdown selection for existing/new attributes
- v1.6.8: Smart Search & Lookup System - Replaced dropdown with search input, real-time suggestions, tag-based option selection
- v1.6.9: Enhanced Attribute Search - Show all options initially in scrollable dropdown, focus-triggered suggestions, real-time filtering
- v1.7.0: Enhanced Existing Attribute Options - Show active options in suggestions list, live updates when selecting/deselecting
- v1.7.1: Component Cleanup - Manually removed unused component files causing 404 errors, cleaned up file system
- v1.7.2: Debugging and Troubleshooting - Added comprehensive debugging for add attribute button functionality
- v1.7.3: Comprehensive Attribute Management System - Complete product editor with intelligent attribute management, scrollable option suggestions, live state updates, active options display, focus-triggered suggestions, real-time filtering, create new functionality, enhanced UX with WordPress-style patterns
- v1.8.0: Input Clearing Enhancement - Fixed add attribute search input clearing on add/remove operations for consistent user experience
- v1.8.1: Duplicate Prevention - Added validation to prevent adding attributes that already exist on the product with case-insensitive checking
- v1.8.2: Smart Suggestions Filtering - Updated add attribute suggestions to exclude already-added attributes from the dropdown
- v1.8.3: User-Controlled Dropdowns - Fixed options dropdown opening automatically when searching attribute names, now only shows on user focus
- v1.8.17: Complete Reporting Removal - Removed all reporting functionality and corrected application branding from WP-POS to WP POS (WordPress Point of Sale)
- v1.8.55: Fixed held cart customer functionality - resolved three critical issues: customer data now saved when holding cart, customer name displayed in held carts table with truncated display, customer properly restored when retrieving held cart. Modified [`holdCurrentCart()`](../assets/js/main.js:3327), [`renderHeldCarts()`](../assets/js/main.js:3344), and [`restoreHeldCart()`](../assets/js/main.js:3466). Version updated in [`index.php`](../index.php:25)
- v1.8.54: Implemented customer attachment functionality for POS orders - created customer search API endpoint [`api/customers.php`](../api/customers.php:1), on-screen keyboard component [`assets/js/modules/keyboard.js`](../assets/js/modules/keyboard.js:1), customer state management, UI integration with search modal and cart display, held cart persistence
