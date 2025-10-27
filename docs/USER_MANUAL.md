## Latest Updates

### v1.9.167 - Improved Product Editor Save Button (2025-10-25)

When you save changes in the Product Editor, you'll now see clear visual feedback that your changes are being saved!

**What's New:**
- ✅ **Spinning icon** appears next to button text while saving
- ✅ **"Saving..." text** replaces the button text to show progress
- ✅ **Button becomes disabled** so you can't accidentally click it twice
- ✅ **Visual styling changes** (button appears slightly faded) to indicate it's processing
- ✅ **Automatic restoration** - button returns to normal when save completes

**Why This Matters:**
- You no longer need to wonder if the system received your save request
- Clear feedback prevents accidental double-clicks that could cause errors
- Professional user experience that matches modern app standards
- Works for both successful saves and error cases

**How to See It:**
1. Open any product in the Product Editor
2. Make a change (edit name, price, etc.)
3. Click "Save Product"
4. Watch the button change to show "Saving..." with a spinning icon
5. Button returns to normal when save completes

**Note:** This improvement works for both the form view and JSON view save buttons!

### v1.9.133 - Refunds & Exchanges Reports
Track all refunds and exchanges processed through your POS system with comprehensive reporting and filtering options.

---

## Version 1.9.34 - Smart Fee/Discount Tab Switching (2025-10-07)

### What's New
The fee/discount modal now smartly remembers separate values for dollar amounts and percentages!

### How It Works
1. **Two Tabs**: Choose between "$ Flat" (dollar amounts) or "% Percentage"
2. **Separate Memory**: Each tab remembers its own value
3. **Smart Switching**: Switch between tabs without losing your entered values
4. **Proper Display**: Cart shows "$5.00" for flat amounts and "10%" for percentages

### Example Usage
1. Open "Add Fee" or "Add Discount"
2. Enter "5" in the Flat tab → shows as "+$5.00" in cart
3. Click "% Percentage" tab
4. Enter "10" → your "5" is still saved in Flat tab
5. Switch back to "$ Flat" → "5" is still there!
6. Click "Apply" → only the active tab's value is used

### Benefits
- ✅ No more losing your entered values when switching tabs
- ✅ Easy to compare flat vs percentage amounts
- ✅ Clear visual formatting ($ vs %)
- ✅ Modal always starts with "$ Flat" tab selected

---

## Version 1.9.33 - Improved Fee/Discount Entry (2025-10-07)

### What's Fixed
The fee/discount numpad now works correctly without the bugs from v1.9.32:

#### Fixed Issues:
1. ✅ **No more double entry** - Numbers no longer appear twice when you click numpad buttons
2. ✅ **Empty input field** - Modal opens with a clean empty field instead of "0.00"
3. ✅ **Better backspace** - Backspace now clears to empty instead of leaving "0"

### How to Use (Updated)
1. Click "Add Fee" or "Add Discount" button
2. Input field opens **empty and ready**
3. Type numbers using keyboard OR click numpad buttons
4. Click "Apply" to add the fee or discount

### What Changed
- Input field now starts completely empty
- You'll see the "0.00" placeholder text until you start typing
- No need to clear anything before entering your amount
- Numpad clicks work reliably without duplicates

---

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

### Creating Products (Restored in v1.9.145)

**Product creation has been restored to the WP POS interface!**

You can now create new products directly from the POS system with all text-based product information. For the best experience, images should still be managed through WooCommerce.

#### How to Create a New Product

1. **Access Product Creation**
   - Navigate to the **Products** page
   - Click the **Create Product** button (green button in the top-right corner)
   - The product editor modal will open in "create mode"

2. **Fill in Product Details**
   - **Product Name** (Required): Enter the product name
   - **Regular Price** (Required): Enter the base price
   - **SKU** (Optional): Enter a unique stock keeping unit
   - **Barcode** (Optional): Enter barcode or click "Generate" for automatic barcode
   - **Sale Price** (Optional): Enter discounted price if applicable
   - **Product Status**: Choose from Publish, Draft, Pending, or Private
   - **Tax Settings**: Select tax class and tax status
   - **Inventory**: Set stock quantity and stock management options
   - **Description**: Add product description and short description
   - **Meta Data**: Add custom fields if needed

3. **Save the Product**
   - Review all entered information
   - Click **Create Product** button at the bottom
   - System will create the product and show success message
   - Modal automatically switches to edit mode with the new product ID

4. **Add Product Images** (Via WooCommerce)
   - After creating the product, you'll see a message: "Image upload functionality has been disabled. Please use WooCommerce to manage product images."
   - To add images:
     - Open WordPress Admin dashboard
     - Navigate to **Products → All Products**
     - Find and edit your newly created product
     - Upload featured image and gallery images using WooCommerce editor
     - Click **Update** to save
   - Images will automatically appear in the POS system

#### Important Notes About Product Creation

**What Works in POS:**
- ✅ Create products with all text-based fields
- ✅ Set pricing, SKU, and barcode
- ✅ Configure inventory and stock management
- ✅ Set tax settings and product status
- ✅ Add descriptions and meta data
- ✅ Generate unique barcodes automatically

**What Requires WooCommerce:**
- 📷 **Featured image upload** - Must use WooCommerce admin
- 📷 **Gallery image upload** - Must use WooCommerce admin
- ℹ️ Clear instructions provided in the product editor

**Why Image Uploads are Disabled:**
- Previous implementations (v1.8.37-v1.8.51) had persistent issues with image uploads
- Multiple fix attempts were unsuccessful
- Managing images through WooCommerce ensures consistency and reliability
- This approach follows WordPress/WooCommerce best practices

#### Tips for Efficient Product Creation

1. **Batch Create Products**: Create multiple products with all text information first
2. **Add Images Later**: Upload all images in one session via WooCommerce admin
3. **Use Barcode Generator**: Let the system create unique barcodes automatically
4. **Check SKU Uniqueness**: System will alert you if SKU already exists
5. **Set Stock Management**: Enable stock tracking during creation to avoid issues later

### Product Editing
WP POS includes a comprehensive product editor that allows you to edit all text-based fields for both simple and variable products.

#### Accessing the Product Editor
1. Navigate to the Products page
2. Click on any existing product row or the edit button
3. The comprehensive product editor will open in a modal

#### Managing Product Images (Use WooCommerce Admin)

**IMPORTANT: Image upload functionality is disabled in the WP POS interface (v1.9.145).**

Product creation is now available in POS for all text-based fields, but image management should be done through WooCommerce admin for reliability.

**To manage product images:**

1. **Access WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to **Products → All Products**

2. **Edit Product**
   - Find the product you want to add images to
   - Click to edit the product
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
   - Product grid will show updated images immediately

**Why Images are Managed in WooCommerce:**
- Ensures consistency with WordPress/WooCommerce standards
- Previous POS image upload implementations had persistent issues (v1.8.37-v1.8.51)
- Prevents upload complications and errors
- Simplifies POS interface for its primary purpose: sales operations
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
- **Attributes**: Product attributes with WordPress-style tag-based options management (see below)
- **Variations**: For variable products, edit individual variation details

### Creating Product Attributes (New in v1.9.148)

You can now create custom product attributes directly from the POS product editor!

#### How to Add Attributes

1. **Open Product Editor**
   - Navigate to Products page
   - Click on an existing product or create a new one
   - The product editor modal opens

2. **Find Attributes Section**
   - Scroll down to the "Attributes" section
   - Click **"Add Attribute"** button (green button)
   - A new attribute form appears

3. **Enter Attribute Name**
   - Type the attribute name (e.g., "Color", "Size", "Material")
   - The system will automatically format it properly
   - Names are converted to lowercase with underscores

4. **Add Attribute Options**
   - Click in the "Options" input field
   - Type an option value (e.g., "Red" for Color attribute)
   - **Press Enter or comma** to add the option
   - The option appears as a blue tag with an × button
   - Repeat to add more options (e.g., "Blue", "Green", "Yellow")

5. **Set Attribute Properties**
   - **Visible on product page**: Check to show this attribute on the product page
   - **Used for variations**: Check if this attribute creates product variations
   - Both options are useful for different purposes

6. **Save the Attribute**
   - Click **"Save Product"** at the bottom
   - The attribute is now attached to the product
   - You can add multiple attributes by clicking "Add Attribute" again

#### Managing Attribute Options

**Adding Options:**
- Type the option name in the input field
- Press **Enter** or type a **comma** to add it
- The option appears as a colored tag
- Continue adding as many options as needed

**Removing Options:**
- Click the **×** button on any blue tag
- The option is removed from the attribute
- You can add it back if needed

**Removing Entire Attribute:**
- Click the **trash icon** (🗑️) at the top-right of the attribute form
- The entire attribute form is removed
- Changes only take effect when you save the product

#### Attribute Examples

**Example 1: Color Attribute**
- **Name**: Color
- **Options**: Red, Blue, Green, Black, White
- **Visible**: ✅ Checked (show colors on product page)
- **Variation**: ✅ Checked (customers can choose color)

**Example 2: Material Attribute**
- **Name**: Material
- **Options**: Cotton, Polyester, Silk, Wool
- **Visible**: ✅ Checked (display material info)
- **Variation**: ❌ Unchecked (informational only)

**Example 3: Size Attribute**
- **Name**: Size
- **Options**: Small, Medium, Large, X-Large
- **Visible**: ✅ Checked
- **Variation**: ✅ Checked (customers select size)

#### Tips for Using Attributes

1. **Attribute Names**: Use clear, simple names like "Color", "Size", "Style"
2. **Multiple Attributes**: You can add many attributes to one product
3. **Variations**: Check "Used for variations" only for attributes that create product variations
4. **Option Order**: Add options in the order you want them displayed
5. **Duplicate Check**: The system prevents adding the same option twice
6. **Case Insensitive**: "Red" and "red" are treated as the same option

#### Troubleshooting Attributes

**Problem**: Can't add attribute option
- **Solution**: Make sure you pressed Enter or comma after typing
- **Check**: Verify the option isn't already added (duplicates are prevented)

**Problem**: Attribute doesn't save
- **Solution**: Ensure you clicked "Save Product" at the bottom
- **Check**: Verify attribute has both a name and at least one option

**Problem**: Attribute name looks different than I typed
- **Solution**: This is normal - names are formatted to lowercase with underscores
- **Example**: "Product Color" becomes "product_color"

**Problem**: Can't see attribute on product page
- **Solution**: Check the "Visible on product page" checkbox
- **Save**: Don't forget to save the product after changing

**Problem**: Variations not working
- **Solution**: Check the "Used for variations" checkbox
- **Note**: You can now create variations directly in the POS editor (see below)

### Creating Product Variations (New in v1.9.149)

You can now create product variations directly from the POS product editor! This allows you to add new size, color, or other variations without leaving the POS system.

#### What are Variations?

Variations are different versions of the same product. For example:
- **T-Shirt Product**: Has variations for Size (Small, Medium, Large) and Color (Red, Blue, Green)
- **Coffee Product**: Has variations for Size (8oz, 12oz, 16oz) and Type (Regular, Decaf)
- Each variation can have its own price, SKU, and stock quantity

#### Prerequisites

Before you can create variations, your product must:
1. **Be a Variable Product**: Product type must be set to "variable"
2. **Have Attributes**: Product needs attributes marked "Used for variations"

**If you don't have attributes yet:**
- Follow the "Creating Product Attributes" instructions above
- Make sure to check "Used for variations" when creating attributes
- You need at least one attribute with options to create variations

#### How to Create Variations

**Step 1: Open Product Editor**
1. Navigate to Products page
2. Click on a variable product (or create one)
3. The product editor modal opens

**Step 2: Verify Attributes**
1. Scroll to the "Attributes" section
2. Confirm you have at least one attribute with "Used for variations" checked
3. Example: "Size" attribute with options: Small, Medium, Large

**Step 3: Navigate to Variations Section**
1. Scroll down to the "Variations" section
2. You'll see any existing variations listed
3. Look for the **"Add Variation"** button (green button)

**Step 4: Click "Add Variation"**
1. Click the **"Add Variation"** button
2. A new variation form appears below existing variations
3. The form shows dropdowns for each variation-enabled attribute

**Step 5: Select Attribute Values**
1. **Choose Options**: Select a value for each attribute dropdown
   - Example: Size = "Large", Color = "Red"
   - All attributes must have a selection
2. **Combination**: Each variation must have a unique combination
   - Can't create two variations with the same Size + Color combination

**Step 6: Enter Variation Details**

**Required Field:**
- **Regular Price**: Enter the base price for this variation (e.g., 29.99)

**Optional Fields:**
- **SKU**: Enter a unique identifier (e.g., TSHIRT-LRG-RED)
- **Sale Price**: Enter a discounted price if applicable
- **Stock Quantity**: Enter available stock for this variation
- **Enabled**: Check to make variation available (checked by default)

**Step 7: Add More Variations (Optional)**
1. Click "Add Variation" again to create another variation
2. Select different attribute combinations
3. Enter pricing and stock for each
4. You can add as many variations as needed

**Step 8: Save the Product**
1. Review all variation details
2. Scroll to the bottom
3. Click **"Save Product"** button
4. Success message appears when variations are created
5. Variations are now available for sale in POS

#### Example: Creating T-Shirt Variations

**Product Setup:**
- Product Type: Variable
- Attributes: Size (Small, Medium, Large), Color (Red, Blue)
- Both marked "Used for variations"

**Creating Variations:**

**Variation 1:**
- Size: Large
- Color: Red
- Regular Price: $29.99
- SKU: TSHIRT-LRG-RED
- Stock: 50 units
- Enabled: ✅ Checked

**Variation 2:**
- Size: Medium
- Color: Blue
- Regular Price: $27.99
- SKU: TSHIRT-MED-BLU
- Stock: 30 units
- Enabled: ✅ Checked

**Result**: Two variations created! Customers can now choose between:
- Large Red T-Shirt ($29.99)
- Medium Blue T-Shirt ($27.99)

#### Managing Variations

**Viewing Variations:**
- All variations appear in the Variations section
- Shows attribute combinations, price, SKU, and stock
- Can edit existing variations (click to expand)

**Editing Variations:**
- Click on an existing variation to edit it
- Change price, stock, or other details
- Click "Save Product" to update

**Removing Variations:**
- Click the remove button on unwanted variation
- Changes take effect when you save the product

**Enabling/Disabling:**
- Use the "Enabled" checkbox on each variation
- Disabled variations won't appear in POS but remain in the database
- Useful for seasonal products or temporary stock issues

#### Tips for Success

1. **Plan Your Attributes**: Set up all attributes before creating variations
2. **Unique SKUs**: Use descriptive SKUs that include attribute values
3. **Price Strategy**: Variations can have different prices based on size/options
4. **Stock Management**: Track stock separately for each variation
5. **Test First**: Create one variation, save, then verify it works before adding many

#### Common Questions

**Q: Why can't I click "Add Variation"?**
A: Your product must be type "variable" and have at least one attribute marked "Used for variations"

**Q: What if I don't select all attributes?**
A: The variation won't be created. All variation-enabled attributes must have values selected.

**Q: Can I change attribute values after creating a variation?**
A: No, but you can delete the variation and create a new one with different values.

**Q: How many variations can I create?**
A: There's no hard limit, but consider usability. Most products have 2-20 variations.

**Q: Do variations appear in WooCommerce?**
A: Yes! Variations created in POS appear correctly in WooCommerce admin.

**Q: Can I upload images for variations?**
A: Not in POS. Use WooCommerce admin to upload variation-specific images.

**Q: What happens if I don't provide a SKU?**
A: The variation is created without a SKU. You can add one later by editing.

**Q: Can variations have different stock quantities?**
A: Yes! Each variation has independent stock management.

#### Troubleshooting Variations

**Problem: "Add Variation" button missing**
- **Solution**: Check that product type is "variable"
- **Check**: Verify you have attributes marked "Used for variations"

**Problem: Can't select attribute values**
- **Solution**: Add options to the attribute first
- **Check**: Ensure attribute has "Used for variations" enabled

**Problem: "Regular price is required" error**
- **Solution**: Enter a price in the Regular Price field
- **Note**: This is the only required field for variations

**Problem: Variation not saving**
- **Solution**: Ensure all attribute dropdowns have selections
- **Check**: Verify you clicked "Save Product" at the bottom

**Problem: Can't create duplicate combination**
- **Solution**: Each variation must have unique attribute values
- **Example**: Can't create two "Large + Red" variations

**Problem: Variation doesn't show in POS**
- **Solution**: Check the "Enabled" checkbox is checked
- **Check**: Save the product after enabling
- **Refresh**: Reload the products page

**Problem: Stock not updating**
- **Solution**: Enter stock quantity when creating variation
- **Check**: Ensure the variation has stock management enabled

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


## Refunds & Exchanges Reports

### Overview
The Refunds & Exchanges page provides comprehensive tracking of all refunds and exchanges processed through your POS system. This feature automatically integrates with WordPress/WooCommerce, keeping complete records of all return transactions.

### Accessing Refund Reports

1. Click **Refunds & Exchanges** in the side menu
2. The reports page will load showing today's refunds by default

### Viewing Refund Data

#### Summary Statistics
At the top of the page, you'll see four summary cards:
- **Total Refunds**: Total number of refund transactions
- **Total Refunded**: Total dollar amount refunded
- **Total Exchanges**: Number of exchange transactions (refund + new order)
- **Avg Refund**: Average refund amount

#### Refund List
The main table shows all refund transactions with:
- **Refund #**: Unique refund ID number
- **Date**: When the refund was processed
- **Type**: Badge showing "Refund" or "Exchange"
- **Original Order**: The order number that was refunded
- **Amount**: Refund amount (shown in red as negative)
- **Customer**: Customer name or "Guest"
- **Actions**: Eye icon to view detailed information

### Filtering Refunds

#### Period Selection
Use the dropdown menu to filter refunds by time period:
- **Today**: Refunds processed today
- **Yesterday**: Yesterday's refunds
- **This Week**: Current week (Monday to today)
- **Last Week**: Previous week (Monday to Sunday)
- **This Month**: Current month to date
- **This Year**: Year to date
- **Custom Range**: Choose specific start and end dates

#### Custom Date Range
1. Select **Custom Range** from the period dropdown
2. Two date picker fields will appear
3. Select your start date
4. Select your end date
5. Report will automatically update

### Viewing Refund Details

To see complete information about a specific refund:

1. Click the **eye icon** (👁️) in the Actions column
2. A modal will open showing:
   - **Refund Number**: Unique ID
   - **Type**: Refund or Exchange
   - **Original Order**: The order that was refunded
   - **Customer**: Customer name
   - **Date**: When processed
   - **Reason**: Refund reason (if provided)
   - **Refunded Items**: List of items and quantities returned
   - **Exchange Information**: If an exchange, shows the new order number
   - **Total Refunded**: Final refund amount

3. Click **Close** to return to the reports list

### Understanding Refund Types

#### Simple Refund
- Customer returns items for money back
- Inventory automatically restored to WordPress
- Shows purple "Refund" badge
- No new order created

#### Exchange
- Customer returns items AND purchases new items
- Shows blue "Exchange" badge
- Links to both the refund and the new order
- Inventory updated for both transactions
- Exchange information shows new order number

### Exporting Refund Data

To export your refund data to CSV:

1. Click the **green download icon** in the header
2. A CSV file will download automatically
3. The file includes:
   - Refund number
   - Date and time
   - Type (Refund/Exchange)
   - Original order number
   - Refund amount
   - Customer name
   - Refund reason

4. Open the file in Excel, Google Sheets, or any spreadsheet software

### Refreshing Data

Click the **refresh icon** (🔄) in the header to reload the current report with the latest data.

### Common Use Cases

#### End-of-Day Reconciliation
1. Select **Today** from the period dropdown
2. Review total refunded amount
3. Compare with cash drawer closing
4. Export to CSV for records

#### Monthly Reporting
1. Select **This Month** to see month-to-date refunds
2. Check total exchanges vs. simple refunds
3. Review average refund amount trends
4. Export for accounting records

#### Customer Service Analysis
1. Use custom date range for specific periods
2. Click refund details to review reasons
3. Identify frequent returners
4. Track exchange patterns

### WordPress Integration

All refund data is automatically synchronized with WordPress/WooCommerce:
- Refunds are stored as `shop_order_refund` post types
- Inventory levels are automatically updated
- Original orders are linked to their refunds
- Exchange orders are created as new orders
- All transactions maintain full audit trail

### Tips

- **Exchange Detection**: System automatically identifies exchanges by analyzing order notes
- **Real-Time Data**: Reports update instantly when refunds are processed
- **Historical Records**: All refunds remain in the system indefinitely
- **Customer Tracking**: Customer information preserved for all refunds
- **Audit Trail**: Complete record of who processed each refund and when

### Troubleshooting

**Problem**: No refunds showing
- **Check**: Verify you have refunds in the selected time period
- **Solution**: Try selecting a different date range or "This Year"

**Problem**: Exchange not showing exchange order
- **Check**: Exchange order may not have been created during refund process
- **Solution**: Review original order notes in WordPress admin

**Problem**: CSV export is empty
- **Check**: Ensure there is data in the current view before exporting
- **Solution**: Adjust filters to show refunds, then export

**Problem**: Wrong customer showing
- **Check**: Verify customer was attached to original order
- **Solution**: Customer data comes from original order - update there if needed

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
- **Continuous timelines**: Chart shows all periods in the selected range, even days/hours with no sales (displayed as zero)
  - Example: "This Week" shows all 7 days from Monday to Sunday, not just days with sales
  - Empty periods appear as zero values on the chart for better visualization
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

## User Management (New in v1.9.119)

### What is User Management?

The User Management feature allows administrators to create, edit, and manage WordPress users directly from the POS interface. This is useful for adding new cashiers, managers, or other staff members without leaving the POS system.

### Accessing User Management

1. **Open Side Menu**: Click the menu button (☰) in the top-left corner
2. **Select Users**: Click "Users" from the menu
3. **Users Page Loads**: You'll see a list of all WordPress users

**Note**: User Management requires administrator privileges (`manage_options` capability).

### Viewing Users

The Users page displays all WordPress users in a table format:

**User Information Displayed:**
- **Name**: User's full name (first name + last name)
- **Email**: User's email address
- **Roles**: All roles assigned to the user (e.g., "Administrator", "JPOS Cashier")
- **Registered**: When the user account was created
- **Actions**: Edit and Delete buttons

### Searching and Filtering Users

**Search Users:**
1. Use the search box at the top of the page
2. Type any part of the user's name, email, or username
3. Results update automatically as you type
4. Clear the search to show all users again

**Filter by Role:**
1. Click the "Filter by Role" dropdown
2. Select a role (e.g., "JPOS Cashier", "Administrator")
3. Only users with that role will be displayed
4. Select "All Roles" to show everyone

**Refresh User List:**
- Click the refresh button (🔄) to reload the user list
- Useful after making changes or if the list seems outdated

### Creating New Users

**To Create a New User:**

1. **Click "Create User"**: Button in the top-right corner
2. **User Dialog Opens**: A form appears for entering user details

**Fill in Required Fields:**
- **Username** (Required): Unique username for login (alphanumeric, underscores, hyphens)
- **Email** (Required): Valid email address (must be unique)
- **Password** (Required): Secure password (minimum 8 characters recommended)
- **First Name** (Optional): User's first name
- **Last Name** (Optional): User's last name

**Assign Roles:**
- Check one or more role checkboxes
- Common roles:
  - **JPOS Cashier**: For POS operators
  - **JPOS Manager**: For store managers
  - **JPOS Administrator**: For system administrators
  - **Customer**: For customer accounts
- You can assign multiple roles to one user

**Save the User:**
1. Review all entered information
2. Click "Save User" button
3. Success message appears when user is created
4. New user appears in the user list

**Important Notes:**
- Username cannot be changed after creation
- Email must be unique across all users
- Strong passwords are recommended for security

### Editing Existing Users

**To Edit a User:**

1. **Find the User**: Search or scroll to find the user
2. **Click Edit Button**: Click the pencil icon in the Actions column
3. **User Dialog Opens**: Pre-filled with current user information

**What You Can Edit:**
- **Email**: Change user's email address (must remain unique)
- **First Name**: Update user's first name
- **Last Name**: Update user's last name
- **Password**: Change user's password (leave blank to keep current password)
- **Roles**: Add or remove role assignments

**What You Cannot Edit:**
- **Username**: Username is permanent and cannot be changed
- **Registration Date**: Date user was created

**Save Changes:**
1. Modify the desired fields
2. Click "Save User" button
3. Success message appears when changes are saved
4. User list updates with new information

**Changing Passwords:**
- Only fill in the password field if you want to change it
- Leave password field empty to keep the current password
- New password takes effect immediately
- User will need to use new password for next login

### Assigning and Managing Roles

**Understanding Roles:**
- Users can have multiple roles assigned
- Roles determine what features users can access
- Common POS roles:
  - **JPOS Cashier**: Can process sales, view products, manage cart
  - **JPOS Manager**: Cashier permissions + reports, sessions, settings
  - **JPOS Administrator**: Full system access including user management
  - **Administrator**: WordPress administrator (full site access)

**To Assign Roles:**
1. Open the user editor (create or edit)
2. Scroll to the Roles section
3. Check the boxes for roles you want to assign
4. Uncheck boxes for roles you want to remove
5. Click "Save User"

**Role Assignment Tips:**
- Assign minimum necessary roles for security
- Most POS users only need "JPOS Cashier" role
- Managers need "JPOS Manager" for reports access
- Only trusted staff should have administrator roles
- You can combine roles (e.g., Cashier + Customer)

### Deleting Users

**To Delete a User:**

1. **Find the User**: Search or scroll to find the user
2. **Click Delete Button**: Click the trash icon (🗑️) in the Actions column
3. **Confirmation Dialog**: A warning dialog appears

**Confirmation Dialog Shows:**
- User name and email being deleted
- Warning that this action cannot be undone
- Content reassignment information

**Confirm Deletion:**
1. Review the information carefully
2. Click "Delete" to proceed
3. Click "Cancel" to abort

**Important Safety Features:**

**Cannot Delete:**
- ❌ **Administrator Users**: Users with the "administrator" role cannot be deleted for security
- ❌ **Your Own Account**: You cannot delete your own user account
- ❌ **Without Reassignment**: All user content will be reassigned to the site admin

**Content Reassignment:**
- When a user is deleted, their orders and content are reassigned
- Content is automatically reassigned to the site administrator (user ID 1)
- This prevents orphaned orders and maintains data integrity

**After Deletion:**
- User is permanently removed from the system
- User can no longer log in
- All their content is preserved under the reassigned user
- Action cannot be undone

### User Management Best Practices

**Security Recommendations:**
1. **Strong Passwords**: Enforce minimum 8-character passwords with mixed characters
2. **Unique Usernames**: Use meaningful, unique usernames for easy identification
3. **Role Assignment**: Only assign necessary roles to each user
4. **Regular Review**: Periodically review user list and remove inactive accounts
5. **Email Verification**: Verify email addresses are correct for password reset functionality

**Operational Tips:**
1. **Clear Naming**: Use first and last names for easy identification
2. **Role Documentation**: Document which roles have which permissions
3. **Training**: Train new users on their specific role capabilities
4. **Backup Admin**: Always have at least 2 administrator accounts
5. **Access Control**: Regularly audit who has access to user management

### Troubleshooting User Management

#### Cannot Create User

**Problem**: Error when trying to create new user

**Common Solutions:**
- **"Username already exists"**: Try a different username
- **"Email already exists"**: Try a different email or check if user already exists
- **"Username is required"**: Fill in the username field
- **"Email is required"**: Fill in the email field
- **"Password is required"**: Fill in the password field
- **"Invalid email format"**: Check email address is properly formatted (e.g., user@example.com)

#### Cannot Edit User

**Problem**: Changes don't save or error occurs

**Common Solutions:**
- **"Email already exists"**: Another user is using that email address
- **"User not found"**: Refresh the page and try again
- **Changes don't save**: Check for error messages, verify internet connection

#### Cannot Delete User

**Problem**: Delete button doesn't work or shows error

**Common Solutions:**
- **"Cannot delete administrator users"**: Administrator-role users cannot be deleted for security
- **"Cannot delete your own account"**: Log in as a different admin to delete your account
- **"User not found"**: Refresh the page and try again

#### Roles Not Showing

**Problem**: Role checkboxes are empty or not loading

**Common Solutions:**
1. Refresh the page (F5)
2. Check internet connection
3. Verify you have administrator privileges
4. Contact system administrator if problem persists

#### Search Not Working

**Problem**: Search doesn't return results

**Common Solutions:**
1. Check spelling of search term
2. Try searching by email instead of name
3. Clear search and try again
4. Refresh the page
5. Verify users exist in the system

#### Permission Denied

**Problem**: "Unauthorized access" error when accessing User Management

**Solutions:**
- **Verify Admin Access**: Only administrators can manage users
- **Check User Role**: Ensure your account has `manage_options` capability
- **Contact Administrator**: Request administrator privileges if needed
- **Re-login**: Log out and log back in to refresh permissions

### Common Questions

**Q: Can I change a username after creating a user?**
A: No, usernames are permanent. You'll need to create a new user with the desired username.

**Q: What happens if I forget a user's password?**
A: Edit the user and enter a new password in the password field.

**Q: Can one user have multiple roles?**
A: Yes, users can have multiple roles assigned simultaneously.

**Q: What's the difference between JPOS Cashier and JPOS Manager?**
A: Cashiers can process sales. Managers have additional access to reports, sessions, and settings.

**Q: Why can't I delete an administrator?**
A: This is a security feature to prevent accidental deletion of critical admin accounts.

**Q: Do changes take effect immediately?**
A: Yes, all changes (role assignments, passwords, etc.) take effect immediately.

**Q: Can I bulk delete multiple users?**
A: No, users must be deleted individually for safety.

**Q: Where do deleted users' orders go?**
A: Orders are automatically reassigned to the site administrator to preserve data.

### User Management Keyboard Shortcuts

While there are no dedicated keyboard shortcuts for user management, you can use standard browser shortcuts:
- **Ctrl+F**: Search within the page
- **Tab**: Navigate between form fields
- **Enter**: Submit form (when focus is on Save button)
- **Esc**: Close dialog (when dialog is open)

---

## Settings and Configuration

### UI Scale (New in v1.9.145)

The UI Scale feature allows you to adjust the size of the entire POS interface to match your device and viewing preferences.

#### Accessing UI Scale Settings

1. Click the menu button (☰) in the top-left corner
2. Select **Settings** from the menu
3. Click the **General** tab
4. Find the **UI Scale** slider

#### How to Adjust UI Scale

**Using the Slider:**
1. Locate the UI Scale slider in General settings
2. Drag the slider left to decrease size (50% minimum)
3. Drag the slider right to increase size (150% maximum)
4. The percentage updates as you move the slider
5. Changes apply immediately as a live preview
6. Click **Save Settings** to persist your preference

**Scale Range:**
- **Minimum**: 50% (Half size - useful for very large displays)
- **Default**: 100% (Standard size)
- **Maximum**: 150% (1.5x larger - useful for tablets or high-DPI displays)
- **Increment**: 5% steps for fine-tuned control

#### When to Use UI Scale

**Decrease Scale (50-95%):**
- When using large desktop monitors or TV displays
- To see more content at once
- For users who prefer compact layouts
- When training multiple people around one screen

**Standard Scale (100%):**
- Default setting for most devices
- Balanced between readability and content density
- Recommended for standard laptop screens

**Increase Scale (105-150%):**
- For tablets and touch-based POS terminals
- High-resolution displays (4K, Retina)
- Users who prefer larger text and buttons
- Accessibility needs for improved readability
- Outdoor kiosks with bright sunlight

#### Features

**Live Preview:**
- See changes immediately as you adjust the slider
- No need to save to test different scales
- Smooth transitions between scale levels

**Persistent Settings:**
- Scale preference saves across browser sessions
- Loads automatically when you log in
- Syncs with WordPress user preferences

**Device Compatibility:**
- Works on all modern browsers
- Optimized for both desktop and touch devices
- Scales all interface elements proportionally
- Maintains layout integrity at all scale levels

#### Tips for Best Results

1. **Find Your Ideal Scale**: Start at 100% and adjust until comfortable
2. **Consider Your Device**: Tablets often work best at 110-130%
3. **Test Touch Targets**: At higher scales, ensure buttons aren't too large
4. **Check Content Fit**: Make sure important information stays visible
5. **Save Your Setting**: Don't forget to click "Save Settings"

#### Troubleshooting

**Problem**: Scale doesn't apply after saving
- **Solution**: Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)
- **Check**: Verify settings were saved successfully
- **Check**: Look for success message after clicking Save

**Problem**: Interface looks distorted at certain scales
- **Solution**: Try a different scale percentage (usually multiples of 10% work best)
- **Check**: Ensure browser zoom is set to 100% (browser zoom conflicts with UI scale)

**Problem**: Can't see all content at high scale
- **Solution**: Reduce scale percentage or scroll to see hidden content
- **Check**: Some elements may wrap differently at extreme scales

**Problem**: Scale resets after logout
- **Solution**: Ensure you clicked "Save Settings" before logging out
- **Check**: Scale should persist in localStorage even without saving

**Problem**: Buttons too small at 50% scale
- **Solution**: Increase scale or use default 100%
- **Note**: Very low scales (50-70%) are intended for large displays only

#### Keyboard Shortcuts

While there are no dedicated shortcuts for adjusting UI scale:
- Use **Tab** to navigate to the slider
- Use **Arrow Keys** (← →) to adjust scale when slider is focused
- Use **Enter** to save settings when Save button is focused

### Auto-Refresh (New in v1.9.194)

The Auto-Refresh feature automatically reloads the POS interface at regular intervals to ensure you always see the most current data. This is especially useful when multiple users are working in the system, or when stock levels and prices change frequently.

#### What is Auto-Refresh?

Auto-Refresh keeps your POS interface up-to-date by automatically reloading the page at intervals you choose. This means:
- You'll always see current stock levels
- Price changes appear immediately
- New products show up automatically
- No need to manually refresh the browser

#### How to Enable Auto-Refresh

1. **Open Settings**
   - Click the menu button (☰) in the top-left corner
   - Select **Settings** from the menu
   - Click the **General** tab

2. **Find Auto-Refresh Settings**
   - Scroll to the "Auto-Refresh Settings" section
   - You'll see an enable checkbox and interval configuration

3. **Enable Auto-Refresh**
   - Check the **"Enable Auto-Refresh"** checkbox
   - The interface will start refreshing automatically

4. **Set Refresh Interval**
   - Choose how often the page should refresh (1-60 minutes)
   - **Type a Custom Value**: Enter any number between 1 and 60 minutes
   - **Use Quick Presets**: Click one of the preset buttons (1, 5, 10, or 30 minutes)

5. **Save Your Settings**
   - Click **Save Settings** at the bottom
   - Auto-refresh starts immediately
   - A countdown indicator appears at the bottom-left of your screen

#### Understanding the Countdown Indicator

When auto-refresh is enabled, you'll see a small indicator at the bottom-left corner of every page:

**What It Shows:**
- 🔄 **Refresh Icon**: Shows auto-refresh is active
- **Countdown Timer**: Displays time remaining until next refresh (MM:SS format)
- **Example**: "Auto-refresh in: 05:00" means 5 minutes remaining

**Where It Appears:**
- Visible on ALL pages (POS, Orders, Products, Reports, etc.)
- Always at bottom-left corner
- Stays visible while you work
- Updates every second

**What Happens When Timer Reaches 0:00:**
1. You'll see a brief notification: "Auto-refreshing..."
2. The page reloads automatically after 0.5 seconds
3. You're returned to the same page you were on
4. The countdown starts again from your chosen interval

#### Recommended Refresh Intervals

**Every 1 Minute** (Aggressive):
- ✅ Best for: Multiple users making frequent changes
- ✅ Use when: Stock levels change rapidly
- ⚠️ Note: Most aggressive setting, reloads often

**Every 5 Minutes** (Standard - Default):
- ✅ Best for: Normal POS operations
- ✅ Use when: Occasional updates needed
- ✅ Balanced between freshness and convenience

**Every 10 Minutes** (Relaxed):
- ✅ Best for: Stable inventory with few changes
- ✅ Use when: You prefer fewer interruptions
- ✅ Good for single-user environments

**Every 30 Minutes** (Extended):
- ✅ Best for: Very stable environments
- ✅ Use when: Data rarely changes
- ✅ Minimal interruption to workflow

**Custom Interval:**
- Type any value from 1 to 60 minutes
- Fine-tune to match your specific needs
- Consider your data update frequency

#### Tips for Using Auto-Refresh

1. **Start with 5 Minutes**: The default setting works well for most stores
2. **Adjust Based on Usage**: Increase frequency if data changes often
3. **Watch for Interruptions**: If refreshes are disruptive, increase the interval
4. **Multi-User Stores**: Use shorter intervals (1-5 minutes) when multiple users are active
5. **Single User**: Longer intervals (10-30 minutes) work fine for solo operations
6. **Save Your Work First**: The system refreshes automatically - complete actions before countdown ends

#### When Auto-Refresh Occurs

**Timer Resets When:**
- ✅ You navigate to a different page (full interval starts over)
- ✅ You log in (timer starts fresh)
- ✅ You change the interval setting (new interval applied)

**Timer Continues When:**
- You add items to cart
- You edit products
- You view orders
- You make sales
- Timer keeps counting down during all normal operations

#### Disabling Auto-Refresh

If you prefer manual control:

1. Open **Settings**
2. Find **Auto-Refresh Settings**
3. **Uncheck** "Enable Auto-Refresh"
4. Click **Save Settings**
5. Countdown indicator disappears
6. Use browser refresh (F5) or page refresh buttons when needed

#### How It Works with Different Pages

Auto-refresh works seamlessly across all pages:

**POS Page:**
- Products reload with current stock levels
- Cart contents preserved (not cleared)
- Current transaction continues

**Products Page:**
- Product list refreshes
- Filters and search remain applied
- Current selections maintained

**Orders Page:**
- Order list updates with latest orders
- Filters remain active
- Pagination position maintained

**Reports Page:**
- Report data refreshes
- Selected date range preserved
- Charts update with new data

**All Pages:**
- You stay on the same page after refresh
- URL parameters maintained
- Your login session continues

#### Troubleshooting Auto-Refresh

**Problem: Countdown indicator not showing**
- **Check**: Ensure "Enable Auto-Refresh" is checked in Settings
- **Solution**: Save settings and hard refresh browser (Ctrl+F5)
- **Verify**: Version 1.9.194 or higher is loaded

**Problem: Timer doesn't reset when changing pages**
- **Check**: Ensure you're on version 1.9.194 or higher
- **Solution**: Hard refresh browser (Ctrl+F5) to clear cache
- **Expected**: Timer should restart with full interval on every page change

**Problem: Page refreshes too often/not often enough**
- **Check**: Verify interval setting in Settings page
- **Solution**: Adjust interval to match your needs (1-60 minutes)
- **Save**: Don't forget to click "Save Settings"

**Problem: Timer appears after login but not immediately**
- **Solution**: Fixed in v1.9.194 - timer now shows immediately
- **Check**: Hard refresh to get latest version
- **Verify**: Timer should display within 1 second of login

**Problem: Settings don't save**
- **Check**: Look for success message after clicking Save
- **Try**: Save again and wait for confirmation
- **Verify**: Reload settings page to confirm settings persisted

**Problem: Timer stops counting**
- **Check**: Ensure browser tab is active (browsers throttle inactive tabs)
- **Solution**: Keep POS tab active or click to reactivate
- **Note**: Timer slows in background tabs but resumes when active

**Problem: Refresh interrupts my work**
- **Solution 1**: Increase refresh interval to reduce interruptions
- **Solution 2**: Disable auto-refresh and use manual refresh (F5)
- **Solution 3**: Time your work to complete before countdown ends

#### Common Questions

**Q: Will auto-refresh clear my cart?**
A: No, your cart contents are preserved through the refresh.

**Q: Does it work on all pages?**
A: Yes, the countdown appears on every page and refreshes work everywhere.

**Q: Can I see how much time is left?**
A: Yes, the countdown indicator shows remaining time in MM:SS format.

**Q: What happens if I'm in the middle of typing?**
A: The page will refresh when the timer hits zero. Save your work or increase the interval.

**Q: Does this use more data/battery?**
A: Minimal impact - only a full page reload at your chosen interval.

**Q: Can different users have different intervals?**
A: Yes, settings are per-user and saved to your account.

**Q: Will it log me out?**
A: No, your session persists through refreshes. You stay logged in.

**Q: Can I temporarily disable it without changing settings?**
A: No, you need to uncheck "Enable Auto-Refresh" and save. Re-enable later.

**Q: Does it work offline?**
A: No, page refresh requires internet connection.

**Q: What if I have a slow connection?**
A: Page takes longer to load after refresh, but countdown waits until fully loaded.

#### Best Practices

**For Busy Stores:**
- Use 1-5 minute intervals
- Ensures data stays current
- Multiple users see each other's changes

**For Quiet Stores:**
- Use 10-30 minute intervals
- Less interruption to workflow
- Still keeps data reasonably fresh

**For Single-User Stores:**
- Consider disabling auto-refresh
- Use manual refresh (F5) when needed
- Avoids unnecessary reloads

**For Training Sessions:**
- Disable auto-refresh temporarily
- Prevents interruptions during learning
- Re-enable for normal operations

**General Tips:**
- Monitor the countdown indicator
- Complete actions before timer expires
- Adjust interval based on experience
- Use longer intervals if refreshes are annoying
- Disable if you prefer full manual control

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

#### Search Not Working (Fixed in v1.9.27)
- **Status**: This issue has been resolved in version 1.9.27
- **What was fixed**: Customer search now properly connects to the API and displays results
- **Expected behavior**:
  1. Type at least 2 characters in the search field
  2. Results appear automatically showing customer names and emails
  3. Click any result to attach that customer to the cart
- **If you still experience issues**:
  1. Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R) to clear cache
  2. Check that version 1.9.27 or higher is loaded
  3. Verify you have internet connection
  4. Check browser console for errors
  5. Contact support if problem persists

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

### Product Creation (Restored in v1.9.145)

Product creation is now available directly in the POS interface! Here are solutions to common issues:

#### Cannot Create Product

**Problem**: Error when trying to create new product

**Common Solutions:**
- **"Product name is required"**: Fill in the product name field (required)
- **"Regular price is required"**: Fill in the regular price field (required)
- **"SKU already exists"**: Change the SKU to a unique value or leave blank for auto-generation
- **"Sale price must be less than regular price"**: Ensure sale price is lower than regular price
- **"Invalid security token"**: Refresh the page to get a new security token

#### Create Product Button Not Working

**Problem**: Clicking "Create Product" button does nothing

**Solutions:**
1. Hard refresh browser (Ctrl+F5 or Cmd+Shift+R) to clear cache
2. Check browser console for JavaScript errors
3. Verify you have edit_products capability
4. Try logging out and back in

#### Product Created But Not Showing Images

**Problem**: Product created successfully but no images

**Solution**: This is expected behavior! Image uploads are disabled in POS.

**To add images:**
1. Go to WordPress Admin → Products → All Products
2. Find your newly created product
3. Edit the product
4. Upload images using WooCommerce image upload
5. Images will appear in POS automatically

#### Modal Doesn't Switch to Edit Mode

**Problem**: After creating product, modal stays in create mode

**Solutions:**
1. Check browser console for errors
2. Verify API returned product ID in response
3. Refresh page and edit product manually if needed
4. Report issue if persists

#### Required Fields Not Highlighted

**Problem**: Can't tell which fields are required

**Solution**: Only two fields are required:
- Product Name (marked with *)
- Regular Price (marked with *)
- All other fields are optional

**Need More Help?**
- Check DEVELOPER_GUIDE.md for API documentation
- Contact your site administrator
- Review browser console for detailed error messages

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
### Image Upload (Disabled in v1.9.145)

**Image upload functionality is disabled in the WP POS interface.**

Product creation is available in POS, but image management should be done through WooCommerce for reliability.

**To upload or manage product images:**
1. Open WordPress Admin → Products → All Products
2. Find and edit the product you want to update
3. Use the WordPress media library to upload featured and gallery images
4. Click Update to save
5. Images will automatically display in POS

**Why Images are Managed in WooCommerce:**
- Previous POS implementations had persistent upload issues (v1.8.37-v1.8.51)
- Ensures consistency with WordPress/WooCommerce standards
- Prevents upload errors and complications
- Simplifies POS interface for its primary purpose: sales operations

**What You Can Do in POS:**
- ✅ Create products with all text information
- ✅ Edit all product fields except images
- ✅ Generate barcodes automatically
- ✅ Manage inventory and pricing
- ❌ Upload featured images (use WooCommerce)
- ❌ Upload gallery images (use WooCommerce)

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

- Current Version: 1.9.145
- Last Updated: October 9, 2025
- Latest Update: WP POS v1.9.145 - Restored product creation functionality - users can now create new products directly from POS with all text-based fields (pricing, inventory, tax, SKU, barcode, meta data). Image uploads remain disabled and should be managed through WooCommerce admin for reliability. Product editor supports both create and edit modes with automatic mode switching after creation.
- Previous Update: WP POS v1.9.119 - Added comprehensive User Management system - administrators can now create, edit, and delete WordPress users directly from the POS interface with role assignment, search/filter capabilities, and safety guards against accidental administrator deletion
- Previous Updates:
  - v1.9.27 - Fixed customer search functionality - search now properly displays results when typing customer names or emails in the "Attach Customer" dialog, with full API integration and error handling
  - v1.8.67 - Enhanced virtual keyboard system with comprehensive settings and auto-show functionality
  - v1.8.66 - Fixed virtual keyboard functionality in customer search modal
  - v1.8.60 - Enhanced customer filtering with searchable input - users can now search for customers by name or email instead of selecting from a dropdown
  - v1.8.59 - Implemented customer filtering in order view with initial static dropdown
  - v1.8.57 - Fixed held carts table layout and date formatting
  - v1.8.56 - Fixed customer display not clearing from cart after holding
  - v1.8.55 - Fixed held cart customer functionality - customer data now properly saved, displayed in held carts table, and restored when retrieving cart
  - v1.8.54 - Implemented customer attachment functionality for POS orders with search, on-screen keyboard, and held cart persistence
  - v1.8.53 - Improved POS cart UI layout by moving Clear Cart button to directly below cart items for better visual hierarchy and easier access
  - v1.9.145 - Restored product creation with improved implementation - text-based fields fully functional, images managed via WooCommerce admin
  - v1.8.52 - Product creation temporarily removed due to image upload issues (restored in v1.9.145)
  - v1.8.51 - Fixed product image upload file picker (functionality now removed in v1.8.52)
  - v1.8.17 - Removed reporting functionality completely and corrected application branding
  - v1.8.3 - Advanced Attribute Management System
- Next Update: Q1 2026

## Version 1.9.32 - Improved Numpad Functionality (2025-10-07)

### What's New
The numpad in the Fee/Discount modal now works more intuitively! You can now click any numpad button and it will automatically insert the number into the amount field - no need to click on the input field first.

### How to Use
1. Click "Add Fee" or "Add Discount" button
2. **NEW**: Click any numpad button - numbers insert automatically
3. Click decimal point (.) to add decimals
4. Click backspace (←) to remove last digit
5. Click "Apply" to add the fee or discount

### Benefits
- ✅ Faster data entry on touchscreen devices
- ✅ No need to manually focus the input field
- ✅ Works great for tablets and POS terminals
- ✅ Still supports keyboard typing if you prefer

### Tips
- The numpad still validates your input (numbers and decimal only)
- You can mix numpad clicks and keyboard typing
- Only one decimal point is allowed

---
