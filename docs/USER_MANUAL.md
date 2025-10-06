# WP POS User Manual

## Virtual Keyboard (v1.8.68)

### What is the Virtual Keyboard?

The virtual keyboard is a touch-friendly on-screen keyboard that appears at the bottom of your screen. It's especially useful when using the POS system on tablets or touch devices where a physical keyboard isn't available.

### Accessing the Virtual Keyboard

There are two ways to use the virtual keyboard:

1. **Manual Mode**: Click the keyboard icon button when you need it (e.g., in customer search)
2. **Auto-Show Mode**: The keyboard automatically appears when you click any text input field

### Keyboard Settings

You can control the virtual keyboard from the Settings page.

#### To Access Keyboard Settings:

1. Click the menu button (☰) in the top-left corner
2. Select "Settings" from the menu
3. Scroll to the "Virtual Keyboard Settings" section

#### Available Settings:

**Enable Virtual Keyboard**
- When checked: Virtual keyboard is available for use
- When unchecked: Virtual keyboard is completely disabled and hidden
- Default: Enabled

**Auto-show keyboard on input focus**
- When checked: Keyboard automatically appears when you click any text field
- When unchecked: You must click the keyboard button to show it manually
- Default: Disabled
- **Note**: This setting only works if "Enable Virtual Keyboard" is also checked

#### Recommended Settings:

**For Touch Devices (Tablets)**:
- ✅ Enable Virtual Keyboard: Checked
- ✅ Auto-show keyboard: Checked
- This provides the most convenient experience with automatic keyboard display

**For Desktop/Laptop with Physical Keyboard**:
- ✅ Enable Virtual Keyboard: Checked
- ⬜ Auto-show keyboard: Unchecked
- This keeps the keyboard available but doesn't show it automatically

**To Disable Completely**:
- ⬜ Enable Virtual Keyboard: Unchecked
- ⬜ Auto-show keyboard: Unchecked (automatically disabled)
- Use this if you never want to see the virtual keyboard

### Using the Virtual Keyboard

#### Keyboard Layout:
- **Letter Keys**: Standard QWERTY layout for typing names and text
- **Space**: Adds a space between words
- **Backspace**: Deletes the last character
- **Clear**: Clears all text in the input field
- **Special Keys**: @ and . for typing email addresses

#### Tips for Use:
- The keyboard appears at the bottom of the screen
- Click the X button in the top-right corner to hide the keyboard
- The keyboard automatically hides when you close a modal/dialog
- Text you type appears immediately in the focused input field

### Troubleshooting

**Settings not saving (Fixed in v1.8.68):**
- **Status**: This issue has been resolved in version 1.8.68
- **What was fixed**: Virtual keyboard settings now properly save and persist across page reloads
- **Expected behavior**: When you enable settings and save, they remain checked when you return to Settings page
- **If you still experience issues**:
  1. Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R) to clear cache
  2. Check that version 1.8.68 or higher is loaded
  3. Try saving settings again
  4. Contact support if problem persists

**Auto-show not working (Fixed in v1.8.68):**
- **Status**: This issue has been resolved in version 1.8.68
- **What was fixed**: Auto-show keyboard now properly initializes when you load the page
- **Expected behavior**: With both settings enabled, keyboard automatically appears when you click any text input
- **If you still experience issues**:
  1. Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R) to clear cache
  2. Verify both "Enable Virtual Keyboard" and "Auto-show keyboard" are checked in Settings
  3. Save your settings and refresh the page
  4. Try clicking a text input field (customer search, product search, etc.)
  5. Contact support if problem persists

**Keyboard doesn't appear when clicking input fields:**
- Check that "Enable Virtual Keyboard" is checked in Settings
- Check that "Auto-show keyboard" is checked in Settings
- Try clicking the keyboard button manually (if available)
- Hard refresh browser (Ctrl+F5) if you recently updated

**Keyboard appears below content:**
- This has been fixed in v1.8.67
- If you still see this issue, try refreshing your browser (Ctrl+F5)

**Keyboard button is hidden:**
- Check that "Enable Virtual Keyboard" is checked in Settings
- Save your settings and refresh the page

**X button doesn't close customer dialog:**
- This has been fixed in v1.8.67
- If you still see this issue, try refreshing your browser (Ctrl+F5)

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
- **Reports**: View sales reports and analytics
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

### Generating Product Barcodes (New in v1.8.34)

WP-POS can automatically generate unique barcodes for your products:

1. **Open Product Editor**: Click any product row or the edit button
2. **Locate Barcode Field**: Find the "Barcode" field in the Basic Information section
3. **Click Generate Button**: Click the "Generate" button next to the barcode input field
4. **Wait for Generation**: The button shows a spinner while generating
5. **Barcode Populated**: The unique barcode automatically fills the input field
6. **Save Product**: Click "Save Product" to save the barcode

**Barcode Format:**
- Format: `20251004230845-A3F7` (example)
- Each barcode is guaranteed to be unique
- Uses full timestamp with random suffix for maximum uniqueness

**Manual Entry:**
You can still manually type barcodes if you prefer. The generate button is optional.

**Note**: Generated barcodes are permanent once saved. You can regenerate if needed, but the old barcode will be replaced.

### Creating Products (Removed in v1.8.52)

**IMPORTANT: Product creation has been removed from the WP POS interface.**

To create new products, you must use the WooCommerce admin interface:

1. **Access WooCommerce Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to **Products → Add New**

2. **Fill in Product Details**
   - Enter all product information (name, price, SKU, etc.)
   - Upload product images (featured and gallery)
   - Set inventory, tax, and other settings
   - Click **Publish** to create the product

3. **Product Appears in POS**
   - The product will automatically appear in the WP POS system
   - You can immediately use it for sales transactions

**Why This Change?**
- Product creation is now managed through WooCommerce for consistency
- Ensures proper integration with WordPress/WooCommerce architecture
- Simplifies the POS interface for its primary purpose: sales
- All product management features available in WooCommerce admin

### Product Editing
WP POS includes a comprehensive product editor that allows you to edit all text-based fields for both simple and variable products.

#### Accessing the Product Editor
1. Navigate to the Products page
2. Click on any existing product row or the edit button
3. The comprehensive product editor will open in a modal

#### Managing Product Images (Use WooCommerce Admin)

**IMPORTANT: Image upload functionality has been removed from the WP POS interface in v1.8.52.**

To manage product images, use the WooCommerce admin interface:

1. **Access WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to **Products → All Products**

2. **Edit Product**
   - Click on the product you want to edit
   - Scroll to the **Product Image** section (featured image)
   - Scroll to the **Product Gallery** section (gallery images)

3. **Upload Images**
   - Click **Set product image** for featured image
   - Click **Add product gallery images** for gallery
   - Upload your images using the WordPress media library
   - Click **Update** to save changes

4. **Images Appear in POS**
   - Images will automatically display in the POS system
   - No additional steps needed

**Why This Change?**
- Ensures consistency with WordPress/WooCommerce standards
- Prevents upload complications and errors
- Simplifies POS interface for sales operations
- All image management features available in WooCommerce admin

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

### Attaching Customers to Orders (New in v1.8.54)

You can now attach customer information to orders for better tracking and customer service.

#### How to Attach a Customer

1. **Click "Attach Customer" Button**
   - Located above the Hold Cart and Checkout buttons in the cart sidebar
   - Blue button with user icon

2. **Search for Customer**
   - Customer search modal appears
   - Enter at least 2 characters of customer name or email address
   
3. **Use On-Screen Keyboard** (Optional - Fixed in v1.8.66)
   - Click the keyboard icon in the search box
   - Touch-friendly keyboard appears at bottom of screen
   - Type customer name or email using on-screen keys
   - Compatible with both touch and mouse input
   - Press Space, Backspace, or Clear as needed
   - **Note**: Keyboard functionality was fixed in version 1.8.66

4. **Select Customer from Results**
   - Search results appear as you type (with 300ms delay)
   - Each result shows customer name and email
   - Click on the desired customer to attach to cart

5. **Customer Attached**
   - Customer information appears at top of cart
   - Shows customer name and email in a blue highlighted box
   - Customer persists through all cart operations

#### Managing Attached Customers

**To Remove a Customer:**
- Click the X button next to the customer name in the cart
- Customer is detached from the order
- Can attach a different customer if needed

**Customer Persistence:**
- Customer information is saved when you hold a cart
- When you restore a held cart, the customer is automatically reattached
- Customer data is included in the order when you complete checkout
- Customer is cleared when you clear the cart or complete a sale

### Adding Items to Cart
1. Search or browse for products
2. Click on the desired product
3. Adjust quantity if needed
4. Item appears in the cart

### Cart Management
- View all items in current transaction
- **Attach customer** to order (optional - see above)
- Modify quantities or remove items
- See running total with tax calculations
- Clear Cart button is located directly below the cart items for easy access (v1.8.53)

### Payment Processing
1. Review cart contents and total
2. **Verify attached customer** (if applicable)
3. Select payment method (Cash, Card, etc.)
4. Enter payment amount
5. Confirm transaction

### Receipt Generation
- Automatic receipt generation
- Print or email receipts
- Receipt includes transaction details and customer information

## Order Management

### Viewing Orders
- Access order history from the Orders section
- Filter orders by date, status, payment method, or customer (new in v1.8.59)
- Search for specific orders or customers

### Filtering Orders by Customer (Enhanced in v1.8.60)
You can filter the order list to show only orders from a specific customer using the searchable customer filter:

1. **Access Customer Filter**
   - Navigate to the Orders page
   - Look for the customer search field in the filter bar (next to date/status filters)
   - The field shows placeholder text "Search customer..."

2. **Search for Customer**
   - **Type to Search**: Enter at least 2 characters of the customer's name or email
   - **View Results**: Search results appear automatically in a dropdown below the input
   - **Customer Details**: Each result shows the customer's full name and email address
   - **Fast Search**: Results appear as you type with a short delay for performance

3. **Select Customer**
   - **Click to Select**: Click on any customer from the search results
   - **Instant Filter**: Order list immediately updates to show only that customer's orders
   - **Visual Feedback**: Selected customer's name appears in the search field
   - **Results Close**: Dropdown automatically closes after selection

4. **Clear Filter**
   - **X Button**: Click the X button that appears when a customer is selected
   - **Show All**: Order list returns to displaying all orders
   - **Quick Reset**: Filter clears instantly with one click

5. **Additional Features**
   - **Click Outside**: Click anywhere outside the dropdown to close it without selecting
   - **Real-time Search**: Search updates as you type (300ms delay for performance)
   - **Works with Filters**: Combine with date, status, and source filters
   - **Filter Persistence**: Selection remains when refreshing order data

**Search Tips:**
- Type customer's first or last name
- Search by email address works too
- Minimum 2 characters required to start search
- Partial matches work (e.g., "john" finds "John Doe")
- Search is case-insensitive

**Why Searchable?**
- Faster for stores with many customers
- Easy to find specific customers
- No need to scroll through long dropdown lists
- Type what you remember (name or email)

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

## Sales Reports

### Accessing Reports
1. Click on "Reports" in the side menu
2. The reports page will load with today's data by default
3. Use the period selector to choose different time ranges

### Period Selection
Choose from preset periods or create custom ranges:
- **Today**: Current day's sales
- **Yesterday**: Previous day's sales
- **This Week**: Monday to today
- **Last Week**: Previous week's sales
- **This Month**: Current month's sales
- **This Year**: Current year's sales
- **Custom Range**: Select specific start and end dates

### Report Components

#### 1. Sales Chart
- **Full-width visualization** showing sales trends
- **Dual-axis chart** displaying both revenue ($) and order counts
- **Intelligent time granularity**:
  - Same day: Hourly breakdown (9:00 AM, 10:00 AM, etc.)
  - Weekly/Monthly: Daily breakdown (Jan 1, Jan 2, etc.)
  - Multi-month: Monthly breakdown (Jan 2025, Feb 2025, etc.)
  - Multi-year: Yearly breakdown (2023, 2024, etc.)

#### 2. Summary Statistics
Four key metrics displayed in cards:
- **Total Orders**: Number of completed transactions
- **Total Revenue**: Sum of all sales in the period
- **Average Order Value**: Revenue divided by number of orders
- **Period**: Current time range being displayed

#### 3. Order Details List
Comprehensive list showing all orders for the selected period:
- **Order #**: Unique order identifier
- **Date**: When the order was placed
- **Source**: POS (in-store) or Online
- **Status**: Order status (completed, processing, etc.)
- **Items**: Number of items in the order
- **Total**: Order total amount
- **Customer**: Customer name (if available)

### Print Reports
1. Click the **Print** button in the top navigation bar
2. A modal window opens with a formatted report
3. The report includes:
   - Store name and report title
   - Period information
   - Summary statistics
   - Complete order details table
4. Click **Print** to print the report or **Close** to return

### Report Features
- **Real-time data**: Reports show current information
- **Refresh button**: Update data manually with the refresh icon
- **Responsive design**: Works on all screen sizes
- **Print-optimized**: Reports format properly for printing
- **Receipt-style formatting**: Professional layout matching POS receipts

### Custom Date Ranges
1. Select "Custom Range" from the period dropdown
2. Choose your start date using the date picker
3. Choose your end date using the date picker
4. The report will automatically update when both dates are selected
5. Default custom range is set to the last 30 days

### Troubleshooting Reports

#### **Problem**: Chart not displaying
- **Solution**: Refresh the page or click the refresh button
- **Check**: Ensure you have internet connection for Chart.js library

#### **Problem**: No data showing
- **Solution**: Verify the date range has orders, or try a different period
- **Check**: Ensure orders exist for the selected time period

#### **Problem**: Print button not working
- **Solution**: Enable pop-ups for the site, or try right-click → Print
- **Check**: Ensure JavaScript is enabled in your browser

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
### Customer Search Issues (New in v1.8.54)

#### No Results Found
- **Problem**: Search returns no results
- **Solutions**:
  1. Check that you've entered at least 2 characters
  2. Try searching by email instead of name
  3. Verify the customer exists in WordPress users
  4. Try a different spelling or partial name

#### Search Not Working
- **Problem**: Search field doesn't respond
- **Solutions**:
  1. Refresh the page (F5)
  2. Close and reopen the customer search modal
  3. Check internet connection
  4. Contact support if issue persists

#### On-Screen Keyboard Not Appearing (Fixed in v1.8.66)
- **Status**: This issue has been resolved in version 1.8.66
- **What was fixed**: Virtual keyboard now properly appears when clicking the keyboard button in the customer search modal
- **Expected behavior**:
  1. Click the keyboard icon in the customer search field
  2. Touch-friendly QWERTY keyboard appears at bottom of screen
  3. Type customer name or email using on-screen keys
  4. Click keyboard icon again or close modal to hide keyboard
- **If you still experience issues**:
  1. Refresh the page (F5 or Ctrl+F5) to ensure you have the latest version
  2. Check that version 1.8.66 or higher is loaded
  3. Try clicking the keyboard icon again to toggle
  4. Use regular keyboard as alternative if needed
  5. Contact support if problem persists

#### Customer Not Attaching
- **Problem**: Clicking customer doesn't attach them
- **Solutions**:
  1. Ensure you clicked directly on the customer result
  2. Check that cart has items (recommended but not required)
  3. Try searching again and selecting customer
  4. Refresh page and try again

#### Customer Display After Holding Cart (Fixed in v1.8.56)
- **Status**: This issue has been resolved in version 1.8.56
- **What was fixed**: Customer display now properly clears when holding a cart and restores correctly when retrieving the held cart
- **Expected behavior**:
  1. When you hold a cart with a customer attached, the customer display disappears from the current cart
  2. The held cart shows the customer name in the Held Carts list
  3. When you restore the held cart, the customer is automatically reattached and displays correctly
- **If you still experience issues**:
  1. Refresh the page (F5 or Ctrl+F5) to ensure you have the latest version
  2. Check that version 1.8.56 or higher is loaded
  3. Contact support if problem persists


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

#### Print Report Issues (Fixed in v1.8.30)
- **Problem**: Print reports show blank first page or incomplete content
- **Solution**: This issue has been resolved - print reports now display all content correctly
- **What was fixed**:
  - Eliminated blank first page issue
  - All report content now prints without truncation
  - Improved print formatting and page breaks
  - Reports now use the same reliable printing method as receipts
- **How to use**: Simply click the Print button in the Reports section - it will now work correctly

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

### Product Creation (Removed in v1.8.52)

**Product creation has been removed from the WP POS interface.**

To create new products:
1. Use the WooCommerce admin interface (WordPress Admin → Products → Add New)
2. Fill in all product details
3. Upload images through WooCommerce
4. Publish the product
5. It will automatically appear in POS

**Need Help?**
- Refer to WooCommerce documentation for product creation
- Contact your site administrator
- Use WordPress support resources

### Product Editor Issues

#### Attribute Options Not Showing
- **Problem**: Attribute suggestions are empty or not loading
- **Solution**: Ensure you're logged in as an admin user with proper permissions
- **Check**: Verify the product has existing attributes in WordPress admin

#### Suggestions Not Updating Live
- **Problem**: When you add/remove options, suggestions don't update immediately
- **Solution**: Refresh the page (Ctrl+F5) to ensure you have the latest version (v1.8.3+)
- **Check**: Look for "WP-POS v1.8.3 loaded" in browser console

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
### Image Upload (Removed in v1.8.52)

**Image upload functionality has been removed from the WP POS interface.**

To upload or manage product images:
1. Use the WooCommerce admin interface (WordPress Admin → Products)
2. Edit the product you want to update
3. Use the WordPress media library to upload images
4. Images will automatically display in POS

**Why This Change?**
- Simplifies POS interface for sales operations
- Ensures consistency with WordPress/WooCommerce
- Prevents upload errors and complications

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

## Held Carts (Updated in v1.8.57)

### Viewing Held Carts

The Held Carts page displays all carts you've temporarily saved for later:

1. **Navigate to Held Carts**: Click "Held Carts" in the side menu
2. **View Your Held Carts**: See all carts with important information at a glance

### Held Cart Information Display

Each held cart shows:
- **Date/Time**: When the cart was held
  - Carts held today show as "Today @ 2:30 PM" (easy to spot recent holds)
  - Yesterday's carts show as "Yesterday @ 9:15 AM"
  - Older carts show full date like "2025-10-04 3:45 PM"
- **Items**: Number of items in the cart
- **Customer**: Customer name if one was attached (or "-" if no customer)
- **Price**: Total cart value
- **Actions**: Restore or Delete buttons

### Managing Held Carts

**To Restore a Cart:**
1. Click the "Restore" button on the desired cart
2. The cart contents, customer info, and totals are automatically loaded
3. Continue with the transaction where you left off

**To Delete a Cart:**
1. Click the "Delete" button (trash icon)
2. The held cart is permanently removed
3. This action cannot be undone

### Tips for Using Held Carts

- Use held carts when a customer needs to step away temporarily
- Attach customers to carts before holding so you remember who they're for
- Check the date/time to identify which cart belongs to which customer
- Held carts are saved in your browser's local storage
- Clearing browser data will remove held carts

## Version Information

- Current Version: 1.8.68
- Last Updated: October 6, 2025
- Latest Update: WP POS v1.8.68 - Fixed virtual keyboard settings persistence and auto-show initialization - settings now properly save and reload, auto-show keyboard now works correctly on all text inputs when enabled
- Previous Updates:
  - v1.8.67 - Enhanced virtual keyboard system with comprehensive settings and auto-show functionality
  - v1.8.66 - Fixed virtual keyboard functionality in customer search modal
  - v1.8.60 - Enhanced customer filtering with searchable input - users can now search for customers by name or email instead of selecting from a dropdown
  - v1.8.59 - Implemented customer filtering in order view with initial static dropdown
  - v1.8.57 - Fixed held carts table layout and date formatting
  - v1.8.56 - Fixed customer display not clearing from cart after holding
  - v1.8.55 - Fixed held cart customer functionality - customer data now properly saved, displayed in held carts table, and restored when retrieving cart
  - v1.8.54 - Implemented customer attachment functionality for POS orders with search, on-screen keyboard, and held cart persistence
  - v1.8.53 - Improved POS cart UI layout by moving Clear Cart button to directly below cart items for better visual hierarchy and easier access
  - v1.8.52 - Removed product creation and image upload functionality - these features must now be managed through WooCommerce admin interface
  - v1.8.51 - Fixed product image upload file picker (functionality now removed in v1.8.52)
  - v1.8.17 - Removed reporting functionality completely and corrected application branding
  - v1.8.3 - Advanced Attribute Management System
- Next Update: Q1 2026
