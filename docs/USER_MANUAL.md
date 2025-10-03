# WP POS User Manual

## Getting Started

### Login
1. Open your web browser and navigate to the WP POS system
2. Enter your username and password
3. Click "Login" to access the system
4. Your user information (name and email) will be displayed in the side menu

### Navigation
The WP POS interface is designed for easy navigation with clear sections:
- **Products**: Browse and search products
- **Cart**: Manage current transaction
- **Orders**: View order history
- **Sessions**: View session history
- **Settings**: Configure system preferences

### User Information Display
After logging in, your user information is displayed in the side menu:
- **Display Name**: Shows your WordPress display name
- **Email Address**: Shows your registered email address
- This information is automatically loaded from your WordPress profile

## Product Management

### Browsing Products
- Use the search bar to find specific products
- Filter by category or stock status
- Click on a product to add it to the cart

### Product Information
Each product displays:
- Product name and description
- Price and stock status
- Optimized product image with fast loading
- SKU and category information
- Images load progressively for better performance

### Stock Management
- View current stock levels
- Low stock items are highlighted
- Stock updates automatically after sales

### Product Editing
WP POS includes a comprehensive product editor that allows you to edit all text-based fields for both simple and variable products:

#### Accessing the Product Editor
1. Navigate to the Products page
2. Click on any product row or the edit button
3. The comprehensive product editor will open in a modal

#### Tabbed Interface
The editor features two main views:
- **Form View**: Traditional form interface for editing fields
- **JSON View**: Real-time JSON preview with syntax highlighting

#### Available Fields
- **Basic Information**: Product name, SKU, barcode
- **Pricing**: Regular price, sale price
- **Status**: Published, draft, private
- **Tax Settings**: Tax class and tax status
- **Inventory**: Stock quantity, stock management settings
- **Meta Data**: Custom fields and additional product information (collapsible accordion)
- **Attributes**: Product attributes with WordPress-style tag-based options management
- **Variations**: For variable products, edit individual variation details

#### Advanced Attribute Management (v1.8.3)
- **Intelligent Search**: Type to search through available attributes with real-time suggestions
- **Duplicate Prevention**: Cannot add attributes that already exist on the product
- **Filtered Suggestions**: Add attribute suggestions exclude already-added attributes
- **Automatic Input Clearing**: Search inputs clear automatically when adding/removing options
- **User-Controlled Dropdowns**: Options suggestions only show when you focus on the input field
- **Scrollable Options**: All available options displayed in scrollable dropdown initially
- **Active Options Display**: Visual indication of selected vs available options (green background + checkmark)
- **Live State Updates**: Real-time updates when selecting/deselecting attribute options
- **Focus-Triggered Suggestions**: Shows all options when input is focused for better discoverability
- **Real-Time Filtering**: Filter options as you type
- **Create New Options**: Ability to create new attributes/options if they don't exist
- **Tag-based Interface**: Add/remove attribute options using tags
- **Database-driven**: All suggestions come from your actual product data (no hardcoded lists)
- **Persistent Options**: Removed options remain available for re-adding
- **Multiple Attributes**: Each attribute maintains its own isolated options
- **Visual Feedback**: Green checkmarks show already-added options

#### JSON Preview
The JSON view includes:
- All product data in a structured format
- Custom syntax highlighting with colored values for easy reading
- Live updates as you modify fields
- Support for both simple and variable products

#### Saving Changes
1. Make your desired changes to any fields
2. Switch between Form View and JSON View as needed
3. Review the JSON preview to verify your changes
4. Click "Save Changes" to update the product
5. The system will confirm successful updates
6. **Dialog stays open** for additional edits - no need to reopen
7. Click "Close" when finished editing

## Sales Process

### Adding Items to Cart
1. Search or browse for products
2. Click on the desired product
3. Adjust quantity if needed
4. Item appears in the cart

### Cart Management
- View all items in current transaction
- Modify quantities or remove items
- See running total with tax calculations

### Payment Processing
1. Review cart contents and total
2. Select payment method (Cash, Card, etc.)
3. Enter payment amount
4. Confirm transaction

### Receipt Generation
- Automatic receipt generation
- Print or email receipts
- Receipt includes transaction details

## Order Management

### Viewing Orders
- Access order history from the Orders section
- Filter orders by date, status, or payment method
- Search for specific orders

### Order Details
Each order shows:
- Order number and date
- Items purchased
- Payment method used
- Total amount

### Order Status
Orders can have different statuses:
- **Completed**: Successfully processed
- **Processing**: Being prepared
- **Cancelled**: Order cancelled

## Session Management

### Session History
Access comprehensive session tracking:
- View all user sessions
- Track login/logout times
- Monitor system activity
- Review user interactions

### Session Details
Each session shows:
- User information
- Login and logout times
- Session duration
- Activity summary

## Settings and Configuration

### Receipt Settings
Configure receipt appearance and content:
- Company information
- Logo placement
- Receipt format
- Printer settings

### Payment Methods
Manage accepted payment methods:
- Cash
- Credit/Debit cards
- Checks
- Other methods

### Inventory Settings
Configure inventory management:
- Low stock alerts
- Stock tracking options
- Automatic stock updates

### User Preferences
Customize the interface:
- Theme selection
- Display preferences
- Notification settings

## Security Features

### User Authentication
- Secure login system
- Role-based access control
- Session management

### Data Protection
- Encrypted data transmission
- Secure payment processing
- Regular data backups
- No sensitive business data exposed in browser console
- Optimized image loading with WebP support for better security and performance

### Audit Trail
- Complete transaction logging
- User activity tracking
- System access records

## Troubleshooting

### Common Issues

#### Login Problems
- Verify username and password
- Check account permissions
- Contact administrator if locked out
- Ensure your WordPress profile has a valid email address

#### Product Display Issues
- Refresh the page
- Check internet connection
- Clear browser cache
- Images now load progressively for better performance
- Product catalog loads in pages of 20 items for faster browsing

#### Payment Processing Errors
- Verify payment method
- Check network connection
- Review transaction details

#### Receipt Printing Issues
- Check printer connection
- Verify receipt settings
- Test printer functionality

### Getting Help
- Check this manual for common solutions
- Contact your system administrator
- Review system logs for error details

## Best Practices

### Daily Operations
- Start each day with a cash drawer count
- Regular product inventory checks
- Monitor system performance
- Backup important data

### Security
- Log out when finished
- Use strong passwords
- Report suspicious activity
- Keep system updated

### Performance
- Close unused browser tabs
- Clear cache regularly
- Monitor system resources
- Report slow performance

## System Requirements

### Browser Compatibility
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Network Requirements
- Stable internet connection
- Minimum 10 Mbps speed
- Low latency preferred

### Hardware Requirements
- Modern computer or tablet
- Minimum 4GB RAM
- 100MB free disk space
- Receipt printer (optional)

## Updates and Maintenance

### System Updates
- Automatic updates when available
- Manual update option
- Update notifications

### Data Backup
- Automatic daily backups
- Manual backup option
- Backup verification

### Maintenance Schedule
- Weekly system checks
- Monthly performance reviews
- Quarterly security audits

## Support and Training

### Training Resources
- Video tutorials
- User guides
- Best practice documentation

### Technical Support
- Online help system
- Email support
- Phone support (if available)

### Community
- User forums
- Knowledge base
- Feature requests

## Troubleshooting

### Product Editor Issues

#### Attribute Options Not Showing
- **Problem**: Attribute suggestions are empty or not loading
- **Solution**: Ensure you're logged in as an admin user with proper permissions
- **Check**: Verify the product has existing attributes in WordPress admin

#### Suggestions Not Updating Live
- **Problem**: When you add/remove options, suggestions don't update immediately
- **Solution**: Refresh the page (Ctrl+F5) to ensure you have the latest version (v1.8.3+)
- **Check**: Look for "JPOS v1.8.3 loaded" in browser console

#### Dialog Closes After Save
- **Problem**: Product editor closes automatically after saving
- **Solution**: Update to version 1.8.3+ where dialog stays open for iterative editing
- **Check**: Version should show "Keep dialog open after save" in console

#### Tax Classes Not Loading
- **Problem**: Tax classes dropdown is empty or shows error
- **Solution**: Ensure WooCommerce is properly installed and configured

#### Options Dropdown Opens Automatically
- **Problem**: Options suggestions appear when typing attribute names
- **Solution**: Update to version 1.8.3+ where options only show when you focus on the options input
- **Check**: Version should show "User-controlled dropdowns" in console

#### Duplicate Attributes
- **Problem**: Trying to add an attribute that already exists
- **Solution**: Update to version 1.8.3+ which prevents duplicate attributes with clear error messages
- **Check**: System will show "Attribute already exists" message
- **Check**: Verify tax classes are set up in WooCommerce settings

#### JSON View Not Highlighting
- **Problem**: JSON preview shows plain text without colors
- **Solution**: Refresh the page to reload the custom syntax highlighting
- **Check**: Ensure JavaScript is enabled in your browser

### General Issues

#### Login Problems
- **Problem**: Cannot log in to WP POS
- **Solution**: Verify WordPress credentials and user permissions
- **Check**: Ensure user has 'manage_woocommerce' capability

#### Performance Issues
- **Problem**: System is slow or unresponsive
- **Solution**: Clear browser cache and check server resources
- **Check**: Monitor system performance in the monitoring section

## Glossary

**API**: Application Programming Interface
**CSV**: Comma-Separated Values
**PDF**: Portable Document Format
**POS**: Point of Sale
**SKU**: Stock Keeping Unit
**TTL**: Time To Live (cache expiration)

## Contact Information

For technical support or questions:
- Email: support@example.com
- Phone: (555) 123-4567
- Website: https://example.com/support

## Version Information

- Current Version: 1.8.17
- Last Updated: January 2, 2025
- Latest Update: WP POS v1.8.17 - Removed reporting functionality completely and corrected application branding from JPOS to WP POS (WordPress Point of Sale)
- Previous Updates: Advanced Attribute Management System v1.8.3 - Complete product editor with intelligent attribute management, duplicate prevention, filtered suggestions, automatic input clearing, and user-controlled dropdown behavior
- Next Update: Q1 2025
