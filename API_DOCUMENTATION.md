# Laravel POS System - API Documentation

**Version:** 1.0.0  
**Base URL:** `/api/v1`  
**Authentication:** Bearer Token (Laravel Sanctum)

---

## Table of Contents

1. [Authentication](#authentication)
2. [Products](#products)
3. [Orders](#orders)
4. [Customers](#customers)
5. [Inventory](#inventory)
6. [Cash Drawer](#cash-drawer)
7. [Response Format](#response-format)
8. [Error Handling](#error-handling)

---

## Authentication

### Login
**POST** `/api/v1/login`

Generate an API token for authentication.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "device_name": "POS Terminal 1" // optional
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "roles": ["cashier"],
      "permissions": ["pos.access", "orders.create"]
    }
  }
}
```

### Logout
**POST** `/api/v1/logout`

Revoke the current access token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

### Get Current User
**GET** `/api/v1/me`

Get authenticated user information.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "roles": ["cashier"],
    "permissions": ["pos.access"]
  }
}
```

---

## Products

### List Products
**GET** `/api/v1/products`

Get paginated list of products.

**Query Parameters:**
- `per_page` (int, default: 15) - Items per page
- `search` (string) - Search by name or SKU
- `category_id` (int) - Filter by category
- `is_active` (boolean) - Filter by active status
- `type` (string) - Filter by type (simple/variable)
- `in_stock` (boolean) - Filter by stock availability
- `sort_by` (string, default: name) - Sort field
- `sort_order` (string, default: asc) - Sort direction

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "PROD-001",
      "name": "Product Name",
      "price": 29.99,
      "stock_quantity": 100,
      "is_active": true
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

### Create Product
**POST** `/api/v1/products`

Create a new product.

**Request Body:**
```json
{
  "sku": "PROD-001",
  "name": "Product Name",
  "description": "Product description",
  "type": "simple",
  "price": 29.99,
  "cost_price": 15.00,
  "category_id": 1,
  "tax_rate": 13.00,
  "is_active": true,
  "track_inventory": true
}
```

### Get Product
**GET** `/api/v1/products/{id}`

Get a single product by ID.

### Update Product
**PUT/PATCH** `/api/v1/products/{id}`

Update an existing product.

### Delete Product
**DELETE** `/api/v1/products/{id}`

Delete a product.

### Search by Barcode
**GET** `/api/v1/products/search-barcode?barcode={barcode}`

Find a product by barcode.

### Low Stock Products
**GET** `/api/v1/products/low-stock`

Get products with low stock levels.

---

## Orders

### List Orders
**GET** `/api/v1/orders`

Get paginated list of orders.

**Query Parameters:**
- `per_page` (int)
- `status` (string) - pending, completed, refunded, cancelled
- `payment_status` (string) - pending, paid, partial, refunded
- `customer_id` (int)
- `user_id` (int)
- `date_from` (date)
- `date_to` (date)

### Create Order
**POST** `/api/v1/orders`

Create a new order.

**Request Body:**
```json
{
  "customer_id": 1,
  "user_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "price": 29.99,
      "discount_amount": 0
    }
  ],
  "discount_amount": 0,
  "notes": "Order notes",
  "payment_method": "cash",
  "payment_amount": 59.98
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20250930-0001",
    "customer_id": 1,
    "status": "pending",
    "subtotal": 59.98,
    "tax_amount": 7.80,
    "total": 67.78,
    "items": [...]
  }
}
```

### Get Order
**GET** `/api/v1/orders/{id}`

Get order details.

### Complete Order
**POST** `/api/v1/orders/{id}/complete`

Mark order as completed.

### Cancel Order
**POST** `/api/v1/orders/{id}/cancel`

Cancel an order.

### Add Payment
**POST** `/api/v1/orders/{id}/payment`

Add payment to an order.

**Request Body:**
```json
{
  "payment_method": "cash",
  "amount": 67.78,
  "reference": "REF-123",
  "notes": "Payment notes"
}
```

### Process Refund
**POST** `/api/v1/orders/{id}/refund`

Process a refund for an order.

**Request Body:**
```json
{
  "amount": 67.78,
  "reason": "Customer request",
  "refund_method": "cash"
}
```

### Today's Orders
**GET** `/api/v1/orders/today`

Get orders created today.

---

## Customers

### List Customers
**GET** `/api/v1/customers`

Get paginated list of customers.

**Query Parameters:**
- `per_page` (int)
- `search` (string) - Search by name, email, or phone
- `group_id` (int) - Filter by customer group
- `is_vip` (boolean)
- `is_active` (boolean)

### Create Customer
**POST** `/api/v1/customers`

Create a new customer.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "1234567890",
  "address": "123 Main St",
  "city": "City",
  "postal_code": "12345",
  "customer_group_id": 1,
  "notes": "Customer notes"
}
```

### Get Customer
**GET** `/api/v1/customers/{id}`

Get customer details.

### Update Customer
**PUT/PATCH** `/api/v1/customers/{id}`

Update customer information.

### Delete Customer
**DELETE** `/api/v1/customers/{id}`

Delete a customer (only if no orders exist).

### Search Customers
**GET** `/api/v1/customers/search?query={search}`

Quick search for customers.

### Add Loyalty Points
**POST** `/api/v1/customers/{id}/loyalty-points/add`

Add loyalty points to customer.

**Request Body:**
```json
{
  "points": 100,
  "reason": "Purchase reward"
}
```

### Redeem Loyalty Points
**POST** `/api/v1/customers/{id}/loyalty-points/redeem`

Redeem customer loyalty points.

**Request Body:**
```json
{
  "points": 50
}
```

### Purchase History
**GET** `/api/v1/customers/{id}/purchase-history`

Get customer's order history.

### VIP Customers
**GET** `/api/v1/customers/vip`

Get list of VIP customers.

---

## Inventory

### List Inventory
**GET** `/api/v1/inventory`

Get paginated inventory list.

**Query Parameters:**
- `per_page` (int)
- `low_stock` (boolean)
- `out_of_stock` (boolean)
- `in_stock` (boolean)

### Get Inventory
**GET** `/api/v1/inventory/{type}/{id}`

Get inventory for a product or variant.

**Parameters:**
- `type` - "product" or "variant"
- `id` - Product or variant ID

### Adjust Inventory
**POST** `/api/v1/inventory/{type}/{id}/adjust`

Adjust inventory quantity.

**Request Body:**
```json
{
  "quantity": 10,
  "reason": "purchase",
  "notes": "Received shipment"
}
```

**Reasons:**
- `purchase` - Stock received
- `sale` - Sold items
- `adjustment` - Manual adjustment
- `return` - Customer return
- `transfer` - Transfer between locations
- `damage` - Damaged items
- `theft` - Stolen items
- `count` - Physical count adjustment

### Physical Count
**POST** `/api/v1/inventory/{type}/{id}/physical-count`

Perform physical inventory count.

**Request Body:**
```json
{
  "counted_quantity": 95,
  "notes": "Monthly inventory count"
}
```

### Reserve Inventory
**POST** `/api/v1/inventory/{type}/{id}/reserve`

Reserve inventory for an order.

**Request Body:**
```json
{
  "quantity": 5
}
```

### Release Inventory
**POST** `/api/v1/inventory/{type}/{id}/release`

Release reserved inventory.

**Request Body:**
```json
{
  "quantity": 5
}
```

### Stock Movements
**GET** `/api/v1/inventory/{type}/{id}/movements`

Get inventory movement history.

### Low Stock Items
**GET** `/api/v1/inventory/low-stock`

Get items with low stock.

### Out of Stock Items
**GET** `/api/v1/inventory/out-of-stock`

Get out of stock items.

---

## Cash Drawer

### List Sessions
**GET** `/api/v1/cash-drawer`

Get paginated list of cash drawer sessions.

**Query Parameters:**
- `per_page` (int)
- `status` (string) - open, closed
- `user_id` (int)
- `date_from` (date)
- `date_to` (date)

### Open Session
**POST** `/api/v1/cash-drawer/open`

Open a new cash drawer session.

**Request Body:**
```json
{
  "user_id": 1,
  "opening_balance": 100.00,
  "notes": "Morning shift"
}
```

### Get Session
**GET** `/api/v1/cash-drawer/{id}`

Get cash drawer session details.

### Close Session
**POST** `/api/v1/cash-drawer/{id}/close`

Close a cash drawer session.

**Request Body:**
```json
{
  "closing_balance": 450.00,
  "notes": "End of shift"
}
```

### Current Session
**GET** `/api/v1/cash-drawer/current/{userId}`

Get current open session for a user.

### Add Movement
**POST** `/api/v1/cash-drawer/{id}/movement`

Add cash movement to session.

**Request Body:**
```json
{
  "type": "cash_in",
  "amount": 50.00,
  "reason": "sale",
  "notes": "Cash sale"
}
```

**Types:**
- `cash_in` - Money added
- `cash_out` - Money removed

**Reasons:**
- `sale` - Cash sale
- `refund` - Customer refund
- `payout` - Cash payout
- `bank_deposit` - Bank deposit
- `petty_cash` - Petty cash
- `other` - Other reason

### Get Movements
**GET** `/api/v1/cash-drawer/{id}/movements`

Get cash movements for a session.

### Session Summary
**GET** `/api/v1/cash-drawer/{id}/summary`

Get session summary with totals.

### Today's Sessions
**GET** `/api/v1/cash-drawer/today`

Get today's cash drawer sessions.

### Sessions with Discrepancies
**GET** `/api/v1/cash-drawer/with-discrepancies`

Get sessions with cash discrepancies.

---

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Paginated Response
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  },
  "links": {
    "first": "http://api.example.com/v1/products?page=1",
    "last": "http://api.example.com/v1/products?page=5",
    "prev": null,
    "next": "http://api.example.com/v1/products?page=2"
  }
}
```

---

## Error Handling

### Error Response Format
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

### Common Errors

**Unauthorized (401)**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Validation Error (422)**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Not Found (404)**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

---

## Rate Limiting

API requests are rate-limited to prevent abuse:

- **Default:** 60 requests per minute per IP
- **Authenticated:** 100 requests per minute per user

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1609459200
```

---

## Authentication

All protected endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {your-token-here}
```

### Getting a Token

1. Call `/api/v1/login` with valid credentials
2. Store the returned token securely
3. Include the token in all subsequent requests

### Token Expiration

Tokens do not expire by default. To revoke a token:
- Call `/api/v1/logout` to revoke current token
- Call `/api/v1/logout-all` to revoke all tokens

---

## Best Practices

1. **Always use HTTPS** in production
2. **Store tokens securely** - Never expose in client-side code
3. **Handle errors gracefully** - Check response status codes
4. **Implement retry logic** for failed requests
5. **Use pagination** for large datasets
6. **Cache responses** when appropriate
7. **Validate input** before sending requests

---

## Support

For API support or questions:
- Email: support@example.com
- Documentation: https://docs.example.com
- GitHub: https://github.com/example/pos-system

---

**Last Updated:** 2025-09-30  
**API Version:** 1.0.0