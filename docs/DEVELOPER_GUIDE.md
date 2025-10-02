# JPOS Developer Guide

## Overview

JPOS (WordPress POS) is a modern, modular point-of-sale system built on WordPress. This guide provides comprehensive information for developers working with the JPOS codebase.

## Architecture

### Frontend Architecture
- **Modular JavaScript**: The frontend uses a modular architecture with separate modules for different functionalities
- **State Management**: Centralized state management using `appState` object
- **API Communication**: RESTful API endpoints with consistent response formats

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
