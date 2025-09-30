<?php

namespace App\Services\WooCommerce;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    protected WooCommerceClient $client;

    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }

    /**
     * Export order to WooCommerce
     */
    public function exportOrder(Order $order): array
    {
        try {
            Log::info('Exporting order to WooCommerce', ['order_id' => $order->id]);

            $data = $this->mapOrderToWooCommerce($order);

            if ($order->woocommerce_id) {
                // Update existing order
                $response = $this->client->put("orders/{$order->woocommerce_id}", $data);
            } else {
                // Create new order
                $response = $this->client->post('orders', $data);
            }

            if ($response['success']) {
                $wooOrder = $response['data'];
                
                $order->update([
                    'woocommerce_id' => $wooOrder['id'],
                    'is_synced' => true,
                    'synced_at' => now()
                ]);

                Log::info('Order exported successfully', [
                    'order_id' => $order->id,
                    'woo_order_id' => $wooOrder['id']
                ]);

                return [
                    'success' => true,
                    'order' => $order,
                    'woo_order' => $wooOrder
                ];
            }

            Log::error('Failed to export order', [
                'order_id' => $order->id,
                'response' => $response
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Order export exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Import order from WooCommerce
     */
    public function importOrder(array $wooOrder): array
    {
        DB::beginTransaction();

        try {
            // Find or create order
            $order = Order::where('woocommerce_id', $wooOrder['id'])->first();
            $isNew = !$order;

            if (!$order) {
                $order = new Order();
                $order->order_number = $this->generateOrderNumber();
            }

            // Map customer
            $customerId = null;
            if (!empty($wooOrder['customer_id'])) {
                $customerId = $this->syncCustomer($wooOrder);
            }

            // Map order data
            $order->woocommerce_id = $wooOrder['id'];
            $order->customer_id = $customerId;
            $order->user_id = auth()->id() ?? 1; // Default to admin if no user
            $order->status = $this->mapOrderStatus($wooOrder['status']);
            $order->subtotal = (float) $wooOrder['total'];
            $order->tax_amount = (float) $wooOrder['total_tax'];
            $order->discount_amount = (float) $wooOrder['discount_total'];
            $order->total = (float) $wooOrder['total'];
            $order->payment_status = $wooOrder['status'] === 'completed' ? 'paid' : 'pending';
            $order->notes = $wooOrder['customer_note'] ?? null;
            $order->is_synced = true;
            $order->synced_at = now();
            $order->save();

            // Sync order items
            if (!empty($wooOrder['line_items'])) {
                $this->syncOrderItems($order, $wooOrder['line_items']);
            }

            // Sync payment
            if (!empty($wooOrder['payment_method'])) {
                $this->syncPayment($order, $wooOrder);
            }

            DB::commit();

            return [
                'success' => true,
                'created' => $isNew,
                'order' => $order
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to import order', [
                'woo_order_id' => $wooOrder['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Sync order items
     */
    protected function syncOrderItems(Order $order, array $lineItems): void
    {
        // Clear existing items if reimporting
        if ($order->woocommerce_id) {
            $order->items()->delete();
        }

        foreach ($lineItems as $item) {
            $productId = null;
            $variantId = null;

            // Try to find product by WooCommerce ID
            if (!empty($item['product_id'])) {
                $product = Product::where('woocommerce_id', $item['product_id'])->first();
                $productId = $product?->id;
            }

            // Try to find variant by WooCommerce ID
            if (!empty($item['variation_id'])) {
                $variant = ProductVariant::where('woocommerce_id', $item['variation_id'])->first();
                $variantId = $variant?->id;
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'sku' => $item['sku'] ?? '',
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => (float) $item['price'],
                'tax_rate' => 0,
                'discount_amount' => 0,
                'subtotal' => (float) $item['subtotal'],
                'total' => (float) $item['total'],
            ]);
        }
    }

    /**
     * Sync payment information
     */
    protected function syncPayment(Order $order, array $wooOrder): void
    {
        // Check if payment already exists
        $existingPayment = Payment::where('order_id', $order->id)->first();
        
        if ($existingPayment) {
            return;
        }

        $paymentMethod = $this->mapPaymentMethod($wooOrder['payment_method']);

        Payment::create([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'amount' => (float) $wooOrder['total'],
            'reference' => $wooOrder['transaction_id'] ?? null,
            'notes' => $wooOrder['payment_method_title'] ?? null,
        ]);
    }

    /**
     * Sync customer from order data
     */
    protected function syncCustomer(array $wooOrder): ?int
    {
        $customer = Customer::where('woocommerce_id', $wooOrder['customer_id'])->first();

        if (!$customer && !empty($wooOrder['billing'])) {
            $billing = $wooOrder['billing'];
            
            $customer = Customer::create([
                'woocommerce_id' => $wooOrder['customer_id'],
                'first_name' => $billing['first_name'] ?? '',
                'last_name' => $billing['last_name'] ?? '',
                'email' => $billing['email'] ?? null,
                'phone' => $billing['phone'] ?? null,
                'address' => $billing['address_1'] ?? null,
                'city' => $billing['city'] ?? null,
                'postal_code' => $billing['postcode'] ?? null,
            ]);
        }

        return $customer?->id;
    }

    /**
     * Map local order to WooCommerce format
     */
    protected function mapOrderToWooCommerce(Order $order): array
    {
        $data = [
            'status' => $this->mapOrderStatusToWoo($order->status),
            'currency' => config('app.currency', 'USD'),
            'payment_method' => $this->mapPaymentMethodToWoo($order->payments->first()?->payment_method ?? 'cash'),
            'payment_method_title' => $this->getPaymentMethodTitle($order->payments->first()?->payment_method ?? 'cash'),
            'set_paid' => $order->payment_status === 'paid',
            'line_items' => [],
            'shipping_lines' => [],
        ];

        // Add customer if exists
        if ($order->customer && $order->customer->woocommerce_id) {
            $data['customer_id'] = $order->customer->woocommerce_id;
        } elseif ($order->customer) {
            // Add billing information
            $data['billing'] = [
                'first_name' => $order->customer->first_name,
                'last_name' => $order->customer->last_name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
                'address_1' => $order->customer->address,
                'city' => $order->customer->city,
                'postcode' => $order->customer->postal_code,
            ];
        }

        // Add line items
        foreach ($order->items as $item) {
            $lineItem = [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'total' => (string) $item->total,
            ];

            // Add product ID if synced
            if ($item->product && $item->product->woocommerce_id) {
                $lineItem['product_id'] = $item->product->woocommerce_id;
            }

            // Add variation ID if synced
            if ($item->variant && $item->variant->woocommerce_id) {
                $lineItem['variation_id'] = $item->variant->woocommerce_id;
            }

            $data['line_items'][] = $lineItem;
        }

        // Add customer note
        if ($order->notes) {
            $data['customer_note'] = $order->notes;
        }

        return $data;
    }

    /**
     * Map WooCommerce order status to local status
     */
    protected function mapOrderStatus(string $wooStatus): string
    {
        $statusMap = config('woocommerce.order_mapping.status_map', []);
        
        // Reverse the map for import
        $reverseMap = array_flip($statusMap);
        
        return $reverseMap[$wooStatus] ?? match($wooStatus) {
            'pending', 'on-hold', 'processing' => 'pending',
            'completed' => 'completed',
            'refunded' => 'refunded',
            'cancelled', 'failed' => 'cancelled',
            default => 'pending'
        };
    }

    /**
     * Map local order status to WooCommerce status
     */
    protected function mapOrderStatusToWoo(string $localStatus): string
    {
        $statusMap = config('woocommerce.order_mapping.status_map', []);
        return $statusMap[$localStatus] ?? 'pending';
    }

    /**
     * Map WooCommerce payment method to local method
     */
    protected function mapPaymentMethod(string $wooMethod): string
    {
        $methodMap = config('woocommerce.order_mapping.payment_method_map', []);
        
        // Reverse the map for import
        $reverseMap = array_flip($methodMap);
        
        return $reverseMap[$wooMethod] ?? match($wooMethod) {
            'cod' => 'cash',
            'stripe', 'paypal' => 'card',
            'bacs' => 'bank_transfer',
            default => 'other'
        };
    }

    /**
     * Map local payment method to WooCommerce method
     */
    protected function mapPaymentMethodToWoo(string $localMethod): string
    {
        $methodMap = config('woocommerce.order_mapping.payment_method_map', []);
        return $methodMap[$localMethod] ?? 'cod';
    }

    /**
     * Get payment method title
     */
    protected function getPaymentMethodTitle(string $method): string
    {
        return match($method) {
            'cash' => 'Cash on Delivery',
            'card' => 'Credit Card',
            'mobile' => 'Mobile Money',
            'bank_transfer' => 'Bank Transfer',
            default => 'Other'
        };
    }

    /**
     * Generate unique order number
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Update order status in WooCommerce
     */
    public function updateOrderStatus(Order $order, string $status): array
    {
        if (!$order->woocommerce_id) {
            return [
                'success' => false,
                'message' => 'Order not synced to WooCommerce'
            ];
        }

        try {
            $wooStatus = $this->mapOrderStatusToWoo($status);
            
            $response = $this->client->put("orders/{$order->woocommerce_id}", [
                'status' => $wooStatus
            ]);

            if ($response['success']) {
                $order->update(['synced_at' => now()]);
            }

            return $response;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}