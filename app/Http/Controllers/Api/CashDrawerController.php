<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CashDrawerSessionResource;
use App\Http\Resources\CashMovementResource;
use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CashDrawerController extends ApiController
{
    /**
     * Display a listing of cash drawer sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');
        $userId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = CashDrawerSession::with(['user', 'cashMovements']);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->byUser($userId);
        }

        if ($dateFrom && $dateTo) {
            $query->dateRange($dateFrom, $dateTo);
        }

        // Sort
        $query->orderBy('opened_at', 'desc');

        $sessions = $query->paginate($perPage);

        return $this->paginatedResponse($sessions, CashDrawerSessionResource::class);
    }

    /**
     * Open a new cash drawer session.
     */
    public function open(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Check if user already has an open session
        $existingSession = CashDrawerSession::where('user_id', $request->user_id)
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return $this->errorResponse('User already has an open cash drawer session');
        }

        $session = CashDrawerSession::create([
            'user_id' => $request->user_id,
            'opening_balance' => $request->opening_balance,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $request->notes,
        ]);

        $session->load('user');

        return $this->resourceResponse(
            new CashDrawerSessionResource($session),
            'Cash drawer session opened successfully',
            201
        );
    }

    /**
     * Display the specified cash drawer session.
     */
    public function show(string $id): JsonResponse
    {
        $session = CashDrawerSession::with(['user', 'cashMovements.user', 'orders'])
            ->find($id);

        if (!$session) {
            return $this->notFoundResponse('Cash drawer session not found');
        }

        return $this->resourceResponse(new CashDrawerSessionResource($session));
    }

    /**
     * Close a cash drawer session.
     */
    public function close(Request $request, string $id): JsonResponse
    {
        $session = CashDrawerSession::find($id);

        if (!$session) {
            return $this->notFoundResponse('Cash drawer session not found');
        }

        if ($session->status === 'closed') {
            return $this->errorResponse('Cash drawer session is already closed');
        }

        $validator = Validator::make($request->all(), [
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $session->close($request->closing_balance, $request->notes);
            $session->load(['user', 'cashMovements']);

            return $this->resourceResponse(
                new CashDrawerSessionResource($session),
                'Cash drawer session closed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to close session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get current open session for a user.
     */
    public function current(string $userId): JsonResponse
    {
        $session = CashDrawerSession::where('user_id', $userId)
            ->where('status', 'open')
            ->with(['user', 'cashMovements', 'orders'])
            ->first();

        if (!$session) {
            return $this->notFoundResponse('No open cash drawer session found for this user');
        }

        return $this->resourceResponse(new CashDrawerSessionResource($session));
    }

    /**
     * Add cash movement to session.
     */
    public function addMovement(Request $request, string $id): JsonResponse
    {
        $session = CashDrawerSession::find($id);

        if (!$session) {
            return $this->notFoundResponse('Cash drawer session not found');
        }

        if ($session->status === 'closed') {
            return $this->errorResponse('Cannot add movement to a closed session');
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|in:sale,refund,payout,bank_deposit,petty_cash,other',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $movement = CashMovement::create([
            'session_id' => $session->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'user_id' => auth()->id(),
        ]);

        $movement->load('user');

        return $this->resourceResponse(
            new CashMovementResource($movement),
            'Cash movement added successfully',
            201
        );
    }

    /**
     * Get cash movements for a session.
     */
    public function movements(Request $request, string $id): JsonResponse
    {
        $session = CashDrawerSession::find($id);

        if (!$session) {
            return $this->notFoundResponse('Cash drawer session not found');
        }

        $perPage = $request->input('per_page', 15);
        $type = $request->input('type');
        $reason = $request->input('reason');

        $query = $session->cashMovements()->with('user');

        if ($type) {
            $query->where('type', $type);
        }

        if ($reason) {
            $query->byReason($reason);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->paginatedResponse($movements, CashMovementResource::class);
    }

    /**
     * Get sessions with discrepancies.
     */
    public function withDiscrepancies(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $sessions = CashDrawerSession::with(['user', 'cashMovements'])
            ->withDiscrepancies()
            ->orderBy('closed_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($sessions, CashDrawerSessionResource::class);
    }

    /**
     * Get today's sessions.
     */
    public function today(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $sessions = CashDrawerSession::with(['user', 'cashMovements'])
            ->today()
            ->orderBy('opened_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($sessions, CashDrawerSessionResource::class);
    }

    /**
     * Get session summary.
     */
    public function summary(string $id): JsonResponse
    {
        $session = CashDrawerSession::with(['cashMovements', 'orders.payments'])
            ->find($id);

        if (!$session) {
            return $this->notFoundResponse('Cash drawer session not found');
        }

        $summary = [
            'opening_balance' => (float) $session->opening_balance,
            'closing_balance' => $session->closing_balance ? (float) $session->closing_balance : null,
            'expected_balance' => $session->expected_balance ? (float) $session->expected_balance : null,
            'difference' => $session->difference ? (float) $session->difference : null,
            'total_cash_in' => (float) $session->total_cash_in,
            'total_cash_out' => (float) $session->total_cash_out,
            'total_cash_sales' => (float) $session->total_cash_sales,
            'total_orders' => $session->orders->count(),
            'duration_minutes' => $session->duration_minutes,
            'has_discrepancy' => $session->hasDiscrepancy(),
            'is_over' => $session->isOver(),
            'is_short' => $session->isShort(),
        ];

        return $this->successResponse($summary);
    }
}