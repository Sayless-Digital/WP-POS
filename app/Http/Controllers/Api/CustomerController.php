<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends ApiController
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $groupId = $request->input('group_id');
        $isVip = $request->input('is_vip');
        $isActive = $request->input('is_active');

        $query = Customer::with(['customerGroup', 'orders']);

        // Apply filters
        if ($search) {
            $query->search($search);
        }

        if ($groupId) {
            $query->where('customer_group_id', $groupId);
        }

        if ($isVip !== null && filter_var($isVip, FILTER_VALIDATE_BOOLEAN)) {
            $query->vip();
        }

        if ($isActive !== null && filter_var($isActive, FILTER_VALIDATE_BOOLEAN)) {
            $query->active();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'first_name');
        $sortOrder = $request->input('sort_order', 'asc');
        
        if ($sortBy === 'total_spent') {
            $query->orderBySpent($sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $customers = $query->paginate($perPage);

        return $this->paginatedResponse($customers, CustomerResource::class);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'notes' => 'nullable|string',
            'woocommerce_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $customer = Customer::create($request->all());
        $customer->load('customerGroup');

        return $this->resourceResponse(
            new CustomerResource($customer),
            'Customer created successfully',
            201
        );
    }

    /**
     * Display the specified customer.
     */
    public function show(string $id): JsonResponse
    {
        $customer = Customer::with(['customerGroup', 'orders.items', 'orders.payments'])
            ->find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        return $this->resourceResponse(new CustomerResource($customer));
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'notes' => 'nullable|string',
            'woocommerce_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $customer->update($request->all());
        $customer->load('customerGroup');

        return $this->resourceResponse(
            new CustomerResource($customer),
            'Customer updated successfully'
        );
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            return $this->errorResponse(
                'Cannot delete customer with existing orders',
                400
            );
        }

        $customer->delete();

        return $this->successResponse(null, 'Customer deleted successfully');
    }

    /**
     * Add loyalty points to customer.
     */
    public function addLoyaltyPoints(Request $request, string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        $validator = Validator::make($request->all(), [
            'points' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $customer->addLoyaltyPoints($request->points);

        return $this->successResponse(
            [
                'loyalty_points' => $customer->loyalty_points,
                'points_added' => $request->points,
            ],
            'Loyalty points added successfully'
        );
    }

    /**
     * Redeem loyalty points.
     */
    public function redeemLoyaltyPoints(Request $request, string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        $validator = Validator::make($request->all(), [
            'points' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->points > $customer->loyalty_points) {
            return $this->errorResponse('Insufficient loyalty points');
        }

        $customer->redeemLoyaltyPoints($request->points);
        $discount = $customer->calculateLoyaltyDiscount($request->points);

        return $this->successResponse(
            [
                'loyalty_points' => $customer->loyalty_points,
                'points_redeemed' => $request->points,
                'discount_amount' => $discount,
            ],
            'Loyalty points redeemed successfully'
        );
    }

    /**
     * Get customer purchase history.
     */
    public function purchaseHistory(Request $request, string $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        $perPage = $request->input('per_page', 15);

        $orders = $customer->orders()
            ->with(['items.product', 'items.variant', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($orders, \App\Http\Resources\OrderResource::class);
    }

    /**
     * Search customers.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $customers = Customer::with('customerGroup')
            ->search($request->query)
            ->limit(10)
            ->get();

        return $this->successResponse(
            CustomerResource::collection($customers)
        );
    }

    /**
     * Get VIP customers.
     */
    public function vip(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $customers = Customer::with(['customerGroup', 'orders'])
            ->vip()
            ->orderBySpent('desc')
            ->paginate($perPage);

        return $this->paginatedResponse($customers, CustomerResource::class);
    }
}