<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class PaymentService
{
    /**
     * Process a payment for an order
     *
     * @param Order $order
     * @param string $method
     * @param float $amount
     * @param array $options
     * @return Payment
     * @throws \Exception
     */
    public function processPayment(
        Order $order,
        string $method,
        float $amount,
        array $options = []
    ): Payment {
        DB::beginTransaction();

        try {
            // Validate payment method
            if (!$this->isValidPaymentMethod($method)) {
                throw new \Exception("Invalid payment method: {$method}");
            }

            // Validate amount
            if ($amount <= 0) {
                throw new \Exception("Payment amount must be greater than zero");
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $method,
                'amount' => $amount,
                'reference' => $options['reference'] ?? null,
                'notes' => $options['notes'] ?? null,
            ]);

            // Update order payment status
            $this->updateOrderPaymentStatus($order);

            // Record cash drawer movement if cash payment
            if ($method === 'cash') {
                $this->recordCashMovement($order, $amount, 'sale');
            }

            DB::commit();

            return $payment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process split payment
     *
     * @param Order $order
     * @param array $payments Array of ['method' => string, 'amount' => float, ...]
     * @return Collection
     * @throws \Exception
     */
    public function processSplitPayment(Order $order, array $payments): Collection
    {
        DB::beginTransaction();

        try {
            $totalPaid = collect($payments)->sum('amount');

            if ($totalPaid < $order->total) {
                throw new \Exception("Total payment amount is less than order total");
            }

            $paymentRecords = collect();

            foreach ($payments as $paymentData) {
                $payment = $this->processPayment(
                    $order,
                    $paymentData['method'],
                    $paymentData['amount'],
                    [
                        'reference' => $paymentData['reference'] ?? null,
                        'notes' => $paymentData['notes'] ?? null,
                    ]
                );
                $paymentRecords->push($payment);
            }

            DB::commit();

            return $paymentRecords;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update order payment status based on payments
     *
     * @param Order $order
     * @return void
     */
    protected function updateOrderPaymentStatus(Order $order): void
    {
        $totalPaid = $order->payments()->sum('amount');
        $orderTotal = $order->total;

        if ($totalPaid >= $orderTotal) {
            $order->update(['payment_status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $order->update(['payment_status' => 'partial']);
        } else {
            $order->update(['payment_status' => 'pending']);
        }
    }

    /**
     * Get payment summary for an order
     *
     * @param Order $order
     * @return array
     */
    public function getPaymentSummary(Order $order): array
    {
        $payments = $order->payments;
        $totalPaid = $payments->sum('amount');
        $remaining = max(0, $order->total - $totalPaid);

        return [
            'order_total' => $order->total,
            'total_paid' => $totalPaid,
            'remaining' => $remaining,
            'payment_status' => $order->payment_status,
            'payments' => $payments->groupBy('payment_method')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Validate payment method
     *
     * @param string $method
     * @return bool
     */
    public function isValidPaymentMethod(string $method): bool
    {
        $validMethods = ['cash', 'card', 'mobile', 'bank_transfer', 'other'];
        return in_array($method, $validMethods);
    }

    /**
     * Get available payment methods
     *
     * @return array
     */
    public function getAvailablePaymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'card' => 'Credit/Debit Card',
            'mobile' => 'Mobile Payment',
            'bank_transfer' => 'Bank Transfer',
            'other' => 'Other',
        ];
    }

    /**
     * Record cash drawer movement
     *
     * @param Order $order
     * @param float $amount
     * @param string $type
     * @return void
     */
    protected function recordCashMovement(Order $order, float $amount, string $type): void
    {
        $session = CashDrawerSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        if ($session) {
            CashMovement::create([
                'session_id' => $session->id,
                'type' => $type,
                'amount' => $amount,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Payment for Order #{$order->order_number}",
            ]);
        }
    }

    /**
     * Get payment statistics for date range
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getPaymentStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $payments = Payment::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed');
        })->get();

        $byMethod = $payments->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
                'average' => $group->avg('amount'),
            ];
        });

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'average_payment' => $payments->avg('amount'),
            'by_method' => $byMethod->toArray(),
        ];
    }

    /**
     * Get cash payments for a session
     *
     * @param CashDrawerSession $session
     * @return Collection
     */
    public function getCashPaymentsForSession(CashDrawerSession $session): Collection
    {
        return Payment::where('payment_method', 'cash')
            ->whereHas('order', function ($query) use ($session) {
                $query->where('user_id', $session->user_id)
                    ->whereBetween('created_at', [
                        $session->opened_at,
                        $session->closed_at ?? now(),
                    ]);
            })
            ->with('order')
            ->get();
    }

    /**
     * Calculate expected cash in drawer
     *
     * @param CashDrawerSession $session
     * @return float
     */
    public function calculateExpectedCash(CashDrawerSession $session): float
    {
        $cashPayments = $this->getCashPaymentsForSession($session);
        $cashRefunds = $this->getCashRefundsForSession($session);
        
        $opening = $session->opening_balance;
        $sales = $cashPayments->sum('amount');
        $refunds = $cashRefunds;
        
        return $opening + $sales - $refunds;
    }

    /**
     * Get cash refunds for a session
     *
     * @param CashDrawerSession $session
     * @return float
     */
    protected function getCashRefundsForSession(CashDrawerSession $session): float
    {
        return \App\Models\Refund::where('refund_method', 'cash')
            ->whereHas('order', function ($query) use ($session) {
                $query->where('user_id', $session->user_id)
                    ->whereBetween('created_at', [
                        $session->opened_at,
                        $session->closed_at ?? now(),
                    ]);
            })
            ->sum('amount');
    }

    /**
     * Void a payment
     *
     * @param Payment $payment
     * @param string|null $reason
     * @return bool
     * @throws \Exception
     */
    public function voidPayment(Payment $payment, ?string $reason = null): bool
    {
        DB::beginTransaction();

        try {
            // Check if order is still in valid state
            if ($payment->order->status === 'refunded') {
                throw new \Exception("Cannot void payment for refunded order");
            }

            // Delete payment
            $payment->delete();

            // Update order payment status
            $this->updateOrderPaymentStatus($payment->order);

            // Add note to order
            $payment->order->update([
                'notes' => $payment->order->notes . "\nPayment voided: " . ($reason ?? 'No reason provided'),
            ]);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get payment trends
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param string $groupBy 'day', 'week', 'month'
     * @return array
     */
    public function getPaymentTrends(\DateTime $startDate, \DateTime $endDate, string $groupBy = 'day'): array
    {
        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return Payment::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed');
        })
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, payment_method, COUNT(*) as count, SUM(amount) as total")
            ->groupBy('period', 'payment_method')
            ->orderBy('period')
            ->get()
            ->groupBy('period')
            ->map(function ($group) {
                return $group->mapWithKeys(function ($item) {
                    return [$item->payment_method => [
                        'count' => $item->count,
                        'total' => $item->total,
                    ]];
                })->toArray();
            })
            ->toArray();
    }
}