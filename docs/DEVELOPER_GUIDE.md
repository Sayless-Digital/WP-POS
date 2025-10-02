# JPOS Developer Guide

## Overview

JPOS (WordPress POS) is a modern, modular point-of-sale system built on WordPress. This guide provides comprehensive information for developers working with the JPOS codebase.

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
│   ├── reports.php        # Reporting
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
JPOS uses a URL parameter-based routing system to maintain view state across page reloads. This ensures users stay on their current view when refreshing the page.

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
const isValid = routingManager.isValidView('reports-page');
```

### URL Format
Views are accessed via URL parameters:
- `?view=pos-page` - Point of Sale
- `?view=orders-page` - Orders
- `?view=reports-page` - Reports
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
- `window.fetchReportsData()` - Load sales reports data
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
- **POST** `/api/product-edit-simple.php` - Update product data
- **GET** `/api/product-edit-simple.php?action=get_tax_classes` - Get tax classes
- **GET** `/api/stock.php` - Stock management operations

### Order Processing
- **GET** `/api/orders.php` - Fetch orders with filters
- **POST** `/api/checkout.php` - Process checkout
- **POST** `/api/refund.php` - Process refunds

### Reporting & Analytics
- **GET** `/api/reports.php` - Generate reports
- **GET** `/api/reports-optimized.php` - Optimized reporting

### System Management
- **GET** `/api/settings.php` - Retrieve settings
- **POST** `/api/settings.php` - Update settings
- **GET** `/api/sessions.php` - Session management
- **POST** `/api/drawer.php` - Cash drawer operations

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
Update product with comprehensive data.

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
- `jpos-YYYY-MM-DD.log`: General logs
- `jpos-errors-YYYY-MM-DD.log`: Error logs
- `jpos-performance-YYYY-MM-DD.log`: Performance logs

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

#### Browser Caching Issues
- **Symptom**: JavaScript changes not taking effect, old code still running, ReferenceError persists after fixes
- **Cause**: Browser aggressively caching JavaScript files, serving outdated versions
- **Solution**: Implement cache busting techniques:
  - Add version parameter to script tags: `<script src="assets/js/main.js?v=1.5.3"></script>`
  - Add unique comments at top of JS files: `// JPOS v1.5.3 - CACHE BUST`
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
