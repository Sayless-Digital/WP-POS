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
- `stock-page` - Stock Manager
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

#### Stock Manager Loading Issues
- **Symptom**: Stock manager page shows empty table or doesn't load product data
- **Cause**: JavaScript variable reference errors (`allProducts`, `stockManagerFilters`, `currentProductForModal` not defined)
- **Solution**: Use `appState.products.all`, `appState.stockFilters`, and `appState.products.currentForModal`
- **Prevention**: Always use centralized state management (`appState`) instead of global variables

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
