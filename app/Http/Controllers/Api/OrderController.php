<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends ApiController
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $customerId = $request->input('customer_id');
        $userId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Order::with(['customer', 'user', 'items', 'payments', 'refunds']);

        // Apply filters
        if ($status) {
            $query->byStatus($status);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom && $dateTo) {
            $query->dateRange($dateFrom, $dateTo);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($perPage);

        return $this->paginatedResponse($orders, OrderResource::class);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required_without:items.*.variant_id|exists:products,id',
            'items.*.variant_id' => 'required_without:items.*.product_id|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|in:cash,card,mobile,bank_transfer,other',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'status' => 'pending',
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'total' => 0,
                'payment_status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($request->items as $itemData) {
                $orderItem = new OrderItem([
                    'product_id' => $itemData['product_id'] ?? null,
                    'variant_id' => $itemData['variant_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                ]);

                // Get product/variant details
                if ($itemData['variant_id'] ?? null) {
                    $variant = \App\Models\ProductVariant::find($itemData['variant_id']);
                    $orderItem->sku = $variant->sku;
                    $orderItem->name = $variant->full_name;
                    $orderItem->tax_rate = $variant->product->tax_rate;
                } else {
                    $product = \App\Models\Product::find($itemData['product_id']);
                    $orderItem->sku = $product->sku;
                    $orderItem->name = $product->name;
                    $orderItem->tax_rate = $product->tax_rate;
                }

                $orderItem->calculateTotals();
                $order->items()->save($orderItem);

                // Reserve inventory
                $orderItem->reserveInventory();
            }

            // Calculate order totals
            $order->calculateTotals();

            // Add payment if provided
            if ($request->payment_method && $request->payment_amount) {
                $order->addPayment(
                    $request->payment_method,
                    $request->payment_amount
                );
            }

            DB::commit();

            $order->load(['customer', 'user', 'items', 'payments']);

            return $this->resourceResponse(
                new OrderResource($order),
                'Order created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::with(['customer', 'user', 'items.product', 'items.variant', 'payments', 'refunds'])
            ->find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        return $this->resourceResponse(new OrderResource($order));
    }

    /**
     * Update the specified order.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,completed,refunded,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $order->update($request->only(['status', 'notes']));
        $order->load(['customer', 'user', 'items', 'payments', 'refunds']);

        return $this->resourceResponse(
            new OrderResource($order),
            'Order updated successfully'
        );
    }

    /**
     * Complete an order.
     */
    public function complete(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        if ($order->status === 'completed') {
            return $this->errorResponse('Order is already completed');
        }

        try {
            $order->complete();
            $order->load(['customer', 'user', 'items', 'payments', 'refunds']);

            return $this->resourceResponse(
                new OrderResource($order),
                'Order completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to complete order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel an order.
     */
    public function cancel(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        if ($order->status === 'completed') {
            return $this->errorResponse('Cannot cancel a completed order');
        }

        try {
            $order->cancel();
            $order->load(['customer', 'user', 'items', 'payments', 'refunds']);

            return $this->resourceResponse(
                new OrderResource($order),
                'Order cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add payment to order.
     */
    public function addPayment(Request $request, string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cash,card,mobile,bank_transfer,other',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $payment = $order->addPayment(
                $request->payment_method,
                $request->amount,
                $request->reference,
                $request->notes
            );

            return $this->successResponse(
                ['payment_id' => $payment->id],
                'Payment added successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Process refund for order.
     */
    public function refund(Request $request, string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0|max:' . $order->total,
            'reason' => 'required|string',
            'refund_method' => 'required|in:cash,card,store_credit',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $refund = $order->processRefund(
                $request->amount,
                $request->reason,
                $request->refund_method,
                auth()->id()
            );

            return $this->successResponse(
                ['refund_id' => $refund->id],
                'Refund processed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to process refund: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get today's orders.
     */
    public function today(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $orders = Order::with(['customer', 'user', 'items', 'payments'])
            ->today()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($orders, OrderResource::class);
    }
}