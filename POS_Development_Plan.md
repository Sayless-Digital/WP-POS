
# Laravel POS System - Complete Development Plan
**Target Environment:** Hostinger Shared Hosting (No SSH/Terminal Access)  
**Tech Stack:** Laravel 10 + Livewire 3 + Alpine.js + MySQL  
**Integration:** WooCommerce REST API  
**Skill Level:** Beginner-Friendly with Detailed Guidance

---

## Table of Contents
1. [System Architecture](#1-system-architecture)
2. [Module & Component Breakdown](#2-module--component-breakdown)
3. [Database Schema](#3-database-schema)
4. [Feature Implementation Plan](#4-feature-implementation-plan)
5. [WooCommerce Integration](#5-woocommerce-integration)
6. [Offline Mode Strategy](#6-offline-mode-strategy)
7. [Deployment Plan](#7-deployment-plan)
8. [Development Roadmap](#8-development-roadmap)
9. [Hostinger Constraints & Solutions](#9-hostinger-constraints--solutions)

---

## 1. System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     POS Frontend (Browser)                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Livewire    │  │  Alpine.js   │  │  IndexedDB   │      │
│  │  Components  │  │  (UI Logic)  │  │  (Offline)   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTPS
┌─────────────────────────────────────────────────────────────┐
│              Laravel Backend (Hostinger)                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Routes     │  │  Controllers │  │   Services   │      │
│  │  (Livewire)  │  │   (Logic)    │  │  (Business)  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Models     │  │  Middleware  │  │    Jobs      │      │
│  │  (Eloquent)  │  │   (Auth)     │  │  (Queues)    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↕
┌─────────────────────────────────────────────────────────────┐
│                    MySQL Database                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Products   │  │    Orders    │  │  Customers   │      │
│  │  Inventory   │  │  Payments    │  │    Users     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↕
┌─────────────────────────────────────────────────────────────┐
│              WooCommerce (WordPress)                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  REST API    │  │   Products   │  │   Orders     │      │
│  │  (OAuth)     │  │  (Sync)      │  │   (Sync)     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow Diagram

```
POS Terminal → Add to Cart → Calculate Total → Process Payment
                                                      ↓
                                              Save to Local DB
                                                      ↓
                                         Check Internet Connection
                                                      ↓
                                    ┌─────────────────┴─────────────────┐
                                    ↓                                   ↓
                            Online: Sync to WooCommerce        Offline: Queue for Sync
                                    ↓                                   ↓
                            Update Inventory                    Store in IndexedDB
                                    ↓                                   ↓
                            Generate Receipt                    Mark as Pending
                                                                        ↓
                                                        When Online: Auto-Sync
```

---

## 2. Module & Component Breakdown

### Core Modules

#### A. Authentication & Authorization Module
**Purpose:** Manage user access and permissions

**Components:**
- [`app/Http/Middleware/CheckRole.php`](app/Http/Middleware/CheckRole.php) - Role-based access control
- [`app/Models/User.php`](app/Models/User.php) - User model with roles
- [`app/Models/Role.php`](app/Models/Role.php) - Role definitions
- [`app/Models/Permission.php`](app/Models/Permission.php) - Permission management

**Livewire Components:**
- [`app/Livewire/Auth/Login.php`](app/Livewire/Auth/Login.php) - Login form
- [`app/Livewire/Auth/PinLogin.php`](app/Livewire/Auth/PinLogin.php) - Quick PIN login for cashiers

**User Roles:**
1. **Cashier** - Process sales, view products, basic customer lookup
2. **Manager** - All cashier + reports, discounts, refunds, user management
3. **Storekeeper** - Inventory management, stock adjustments, receiving

#### B. Product Management Module
**Purpose:** Handle product catalog, variants, and barcodes

**Models:**
- [`app/Models/Product.php`](app/Models/Product.php) - Main product model
- [`app/Models/ProductVariant.php`](app/Models/ProductVariant.php) - Product variations
- [`app/Models/ProductCategory.php`](app/Models/ProductCategory.php) - Categories
- [`app/Models/Barcode.php`](app/Models/Barcode.php) - Barcode associations

**Livewire Components:**
- [`app/Livewire/Products/ProductList.php`](app/Livewire/Products/ProductList.php) - Product listing with search
- [`app/Livewire/Products/ProductForm.php`](app/Livewire/Products/ProductForm.php) - Create/edit products
- [`app/Livewire/Products/BarcodeScanner.php`](app/Livewire/Products/BarcodeScanner.php) - Barcode input handler

**Services:**
- [`app/Services/ProductService.php`](app/Services/ProductService.php) - Product business logic
- [`app/Services/BarcodeService.php`](app/Services/BarcodeService.php) - Barcode generation/lookup

#### C. Inventory Management Module
**Purpose:** Track stock levels and movements

**Models:**
- [`app/Models/Inventory.php`](app/Models/Inventory.php) - Stock levels
- [`app/Models/StockMovement.php`](app/Models/StockMovement.php) - Stock history
- [`app/Models/StockAdjustment.php`](app/Models/StockAdjustment.php) - Manual adjustments

**Livewire Components:**
- [`app/Livewire/Inventory/StockList.php`](app/Livewire/Inventory/StockList.php) - Current stock view
- [`app/Livewire/Inventory/StockAdjustment.php`](app/Livewire/Inventory/StockAdjustment.php) - Adjust stock
- [`app/Livewire/Inventory/LowStockAlert.php`](app/Livewire/Inventory/LowStockAlert.php) - Low stock warnings

**Services:**
- [`app/Services/InventoryService.php`](app/Services/InventoryService.php) - Inventory operations
- [`app/Services/StockSyncService.php`](app/Services/StockSyncService.php) - Sync with WooCommerce

#### D. Customer Management Module
**Purpose:** Manage customer profiles and history

**Models:**
- [`app/Models/Customer.php`](app/Models/Customer.php) - Customer data
- [`app/Models/CustomerGroup.php`](app/Models/CustomerGroup.php) - Customer segments
- [`app/Models/LoyaltyPoint.php`](app/Models/LoyaltyPoint.php) - Loyalty program

**Livewire Components:**
- [`app/Livewire/Customers/CustomerSearch.php`](app/Livewire/Customers/CustomerSearch.php) - Quick search
- [`app/Livewire/Customers/CustomerProfile.php`](app/Livewire/Customers/CustomerProfile.php) - Customer details
- [`app/Livewire/Customers/PurchaseHistory.php`](app/Livewire/Customers/PurchaseHistory.php) - Order history

**Services:**
- [`app/Services/CustomerService.php`](app/Services/CustomerService.php) - Customer operations
- [`app/Services/LoyaltyService.php`](app/Services/LoyaltyService.php) - Points calculation

#### E. POS Frontend Module
**Purpose:** Main point-of-sale interface

**Livewire Components:**
- [`app/Livewire/Pos/PosTerminal.php`](app/Livewire/Pos/PosTerminal.php) - Main POS screen
- [`app/Livewire/Pos/Cart.php`](app/Livewire/Pos/Cart.php) - Shopping cart
- [`app/Livewire/Pos/Checkout.php`](app/Livewire/Pos/Checkout.php) - Payment processing
- [`app/Livewire/Pos/HeldOrders.php`](app/Livewire/Pos/HeldOrders.php) - Parked transactions
- [`app/Livewire/Pos/PaymentModal.php`](app/Livewire/Pos/PaymentModal.php) - Payment methods

**Alpine.js Components:**
- [`resources/js/pos/cart-manager.js`](resources/js/pos/cart-manager.js) - Cart state management
- [`resources/js/pos/barcode-listener.js`](resources/js/pos/barcode-listener.js) - Barcode scanner listener
- [`resources/js/pos/keyboard-shortcuts.js`](resources/js/pos/keyboard-shortcuts.js) - Hotkeys

**Services:**
- [`app/Services/CartService.php`](app/Services/CartService.php) - Cart operations
- [`app/Services/CheckoutService.php`](app/Services/CheckoutService.php) - Checkout logic
- [`app/Services/DiscountService.php`](app/Services/DiscountService.php) - Discount calculations

#### F. Order Management Module
**Purpose:** Handle sales transactions

**Models:**
- [`app/Models/Order.php`](app/Models/Order.php) - Order header
- [`app/Models/OrderItem.php`](app/Models/OrderItem.php) - Order line items
- [`app/Models/Payment.php`](app/Models/Payment.php) - Payment records
- [`app/Models/Refund.php`](app/Models/Refund.php) - Refund transactions

**Livewire Components:**
- [`app/Livewire/Orders/OrderList.php`](app/Livewire/Orders/OrderList.php) - Order history
- [`app/Livewire/Orders/OrderDetail.php`](app/Livewire/Orders/OrderDetail.php) - Order view
- [`app/Livewire/Orders/RefundForm.php`](app/Livewire/Orders/RefundForm.php) - Process refunds

**Services:**
- [`app/Services/OrderService.php`](app/Services/OrderService.php) - Order processing
- [`app/Services/PaymentService.php`](app/Services/PaymentService.php) - Payment handling
- [`app/Services/RefundService.php`](app/Services/RefundService.php) - Refund logic

#### G. Offline Mode Module
**Purpose:** Enable offline operation and sync

**Services:**
- [`app/Services/OfflineQueueService.php`](app/Services/OfflineQueueService.php) - Queue management
- [`app/Services/SyncService.php`](app/Services/SyncService.php) - Data synchronization

**JavaScript:**
- [`resources/js/offline/service-worker.js`](resources/js/offline/service-worker.js) - PWA service worker
- [`resources/js/offline/indexed-db.js`](resources/js/offline/indexed-db.js) - Local storage
- [`resources/js/offline/sync-manager.js`](resources/js/offline/sync-manager.js) - Auto-sync logic

**Models:**
- [`app/Models/SyncQueue.php`](app/Models/SyncQueue.php) - Pending sync items
- [`app/Models/SyncLog.php`](app/Models/SyncLog.php) - Sync history

#### H. Reporting Module
**Purpose:** Generate business reports

**Livewire Components:**
- [`app/Livewire/Reports/SalesSummary.php`](app/Livewire/Reports/SalesSummary.php) - Sales reports
- [`app/Livewire/Reports/InventoryReport.php`](app/Livewire/Reports/InventoryReport.php) - Stock reports
- [`app/Livewire/Reports/CashierReport.php`](app/Livewire/Reports/CashierReport.php) - Cashier performance
- [`app/Livewire/Reports/CashDrawer.php`](app/Livewire/Reports/CashDrawer.php) - Cash management

**Services:**
- [`app/Services/ReportService.php`](app/Services/ReportService.php) - Report generation
- [`app/Services/ExportService.php`](app/Services/ExportService.php) - CSV/PDF exports

#### I. Receipt & Invoice Module
**Purpose:** Generate printable receipts

**Services:**
- [`app/Services/ReceiptService.php`](app/Services/ReceiptService.php) - Receipt generation
- [`app/Services/PdfService.php`](app/Services/PdfService.php) - PDF creation with Dompdf

**Views:**
- [`resources/views/receipts/standard.blade.php`](resources/views/receipts/standard.blade.php) - Receipt template
- [`resources/views/receipts/invoice.blade.php`](resources/views/receipts/invoice.blade.php) - Invoice template

#### J. WooCommerce Integration Module
**Purpose:** Sync with WooCommerce

**Services:**
- [`app/Services/WooCommerce/WooCommerceClient.php`](app/Services/WooCommerce/WooCommerceClient.php) - API client
- [`app/Services/WooCommerce/ProductSyncService.php`](app/Services/WooCommerce/ProductSyncService.php) - Product sync
- [`app/Services/WooCommerce/OrderSyncService.php`](app/Services/WooCommerce/OrderSyncService.php) - Order sync
- [`app/Services/WooCommerce/CustomerSyncService.php`](app/Services/WooCommerce/CustomerSyncService.php) - Customer sync

**Jobs:**
- [`app/Jobs/SyncProductsFromWooCommerce.php`](app/Jobs/SyncProductsFromWooCommerce.php) - Import products
- [`app/Jobs/SyncOrderToWooCommerce.php`](app/Jobs/SyncOrderToWooCommerce.php) - Export orders
- [`app/Jobs/SyncInventoryToWooCommerce.php`](app/Jobs/SyncInventoryToWooCommerce.php) - Update stock

---

## 3. Database Schema

### Core Tables

#### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    pin VARCHAR(6) NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

#### roles
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### permissions
```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### role_permissions
```sql
CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

#### products
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    woocommerce_id BIGINT UNSIGNED NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('simple', 'variable') DEFAULT 'simple',
    price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2) NULL,
    category_id BIGINT UNSIGNED NULL,
    tax_rate DECIMAL(5, 2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    track_inventory BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    synced_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_woocommerce_id (woocommerce_id)
);
```

#### product_variants
```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    woocommerce_id BIGINT UNSIGNED NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    attributes JSON NULL,
    price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_sku (sku)
);
```

#### product_categories
```sql
CREATE TABLE product_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    woocommerce_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    parent_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL
);
```

#### barcodes
```sql
CREATE TABLE barcodes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    barcodeable_type VARCHAR(50) NOT NULL,
    barcodeable_id BIGINT UNSIGNED NOT NULL,
    barcode VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(20) DEFAULT 'EAN13',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_barcode (barcode),
    INDEX idx_barcodeable (barcodeable_type, barcodeable_id)
);
```

#### inventory
```sql
CREATE TABLE inventory (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inventoriable_type VARCHAR(50) NOT NULL,
    inventoriable_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    reserved_quantity INT NOT NULL DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    last_counted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_inventory (inventoriable_type, inventoriable_id),
    INDEX idx_quantity (quantity)
);
```

#### stock_movements
```sql
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inventoriable_type VARCHAR(50) NOT NULL,
    inventoriable_id BIGINT UNSIGNED NOT NULL,
    type ENUM('sale', 'purchase', 'adjustment', 'return', 'transfer') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_inventoriable (inventoriable_type, inventoriable_id),
    INDEX idx_created_at (created_at)
);
```

#### customers
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    woocommerce_id BIGINT UNSIGNED NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    customer_group_id BIGINT UNSIGNED NULL,
    loyalty_points INT DEFAULT 0,
    total_spent DECIMAL(10, 2) DEFAULT 0.00,
    total_orders INT DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    synced_at TIMESTAMP NULL,
    FOREIGN KEY (customer_group_id) REFERENCES customer_groups(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_woocommerce_id (woocommerce_id)
);
```

#### customer_groups
```sql
CREATE TABLE customer_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    discount_percentage DECIMAL(5, 2) DEFAULT 0.00,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### orders
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    woocommerce_id BIGINT UNSIGNED NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'completed', 'refunded', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'partial', 'refunded') DEFAULT 'pending',
    notes TEXT NULL,
    is_synced BOOLEAN DEFAULT FALSE,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_woocommerce_id (woocommerce_id)
);
```

#### order_items
```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    variant_id BIGINT UNSIGNED NULL,
    sku VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    tax_rate DECIMAL(5, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    subtotal DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id)
);
```

#### payments
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile', 'bank_transfer', 'other') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reference VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
);
```

#### refunds
```sql
CREATE TABLE refunds (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT NULL,
    refund_method ENUM('cash', 'card', 'store_credit') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_order_id (order_id)
);
```

#### held_orders
```sql
CREATE TABLE held_orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    cart_data JSON NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);
```

#### sync_queue
```sql
CREATE TABLE sync_queue (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    syncable_type VARCHAR(50) NOT NULL,
    syncable_id BIGINT UNSIGNED NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    payload JSON NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_syncable (syncable_type, syncable_id)
);
```

#### sync_logs
```sql
CREATE TABLE sync_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    direction ENUM('import', 'export') NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    records_processed INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    error_message TEXT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
);
```

#### cash_drawer_sessions
```sql
CREATE TABLE cash_drawer_sessions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    opening_balance DECIMAL(10, 2) NOT NULL,
    closing_balance DECIMAL(10, 2) NULL,
    expected_balance DECIMAL(10, 2) NULL,
    difference DECIMAL(10, 2) NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

#### cash_movements
```sql
CREATE TABLE cash_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    type ENUM('sale', 'refund', 'withdrawal', 'deposit') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES cash_drawer_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id)
);
```

### Database Relationships

```
users ──┬─→ orders (cashier)
        ├─→ cash_drawer_sessions
        └─→ stock_movements

roles ──→ users
      └─→ role_permissions ──→ permissions

products ──┬─→ product_variants
           ├─→ inventory (polymorphic)
           ├─→ barcodes (polymorphic)
           └─→ order_items

product_variants ──┬─→ inventory (polymorphic)
                   ├─→ barcodes (polymorphic)
                   └─→ order_items

customers ──┬─→ orders
            └─→ customer_groups

orders ──┬─→ order_items
         ├─→ payments
         └─→ refunds

sync_queue ──→ any model (polymorphic)
```

---

##