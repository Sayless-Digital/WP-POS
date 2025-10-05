# WP POS Developer Guide

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
- **POST** `/api/product-edit-simple.php` - Update existing product data (product creation removed in v1.8.41)
- **GET** `/api/product-edit-simple.php?action=get_tax_classes` - Get tax classes
- **GET** `/api/stock.php` - Stock management operations

### Order Processing
- **GET** `/api/orders.php` - Fetch orders with filters
- **POST** `/api/checkout.php` - Process checkout
- **POST** `/api/refund.php` - Process refunds

### Reporting & Analytics
- **REMOVED** - Reporting functionality has been completely removed from WP POS

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

### Product Image Upload System

#### Image Uploads for Existing Products
WP POS supports uploading product images for existing products during editing. Images are uploaded directly to products that already exist in the database.

**Note:** Product creation functionality was removed in v1.8.41. Images can only be uploaded to existing products during the edit process.

**Technical Implementation:**
- **Direct Upload**: Images uploaded to existing product IDs
- **File Validation**: 5MB max file size, supports PNG, JPG, JPEG, WebP, GIF formats
- **Gallery Limit**: Maximum 10 images in gallery

**Workflow:**
1. User opens product editor for existing product
2. [`initializeImageUpload()`](../assets/js/main.js:1819) initializes upload system with product data
3. User uploads images via drag-and-drop or file picker
4. Images uploaded directly to the product via API
5. Product editor updates to show new images

**Key Functions:**
- [`initializeImageUpload()`](../assets/js/main.js:1819) - Initializes upload system for existing products
- [`setupFeaturedImageUpload()`](../assets/js/main.js:2268) - Handles featured image upload
- [`setupGalleryImageUpload()`](../assets/js/main.js:2360) - Handles gallery image uploads

**File Validation:**
```javascript
function validateImageFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
    
    if (!allowedTypes.includes(file.type)) {
        return { valid: false, error: 'Invalid file type' };
    }
    if (file.size > maxSize) {
        return { valid: false, error: 'File size exceeds 5MB' };
    }
    return { valid: true };
}
```

**Limitations:**
- Product must exist in database before uploading images
- Maximum 5MB per image file
- Maximum 10 images in gallery
- Requires modern browser with File API support

**Related Endpoints:**
- [`/api/product-images.php`](../api/product-images.php:1) - Image upload API for existing products

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
Update existing product with comprehensive data. Product creation functionality removed in v1.8.41 - only updates to existing products are supported.

**Request:**
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

**Note:** The `product_id` field is required. The `create_product` action has been removed.

**Response:**
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

## Version History

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
