<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Models\Payment;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderRefund extends Component
{
    public Order $order;
    public $refundAmount;
    public $refundReason = '';
    public $refundType = 'full'; // full or partial
    public $selectedPaymentId;
    public $restockItems = true;

    protected $rules = [
        'refundAmount' => 'required|numeric|min:0.01',
        'refundReason' => 'required|string|min:10',
        'selectedPaymentId' => 'required|exists:payments,id',
    ];

    protected $messages = [
        'refundAmount.required' => 'Please enter a refund amount',
        'refundAmount.min' => 'Refund amount must be at least $0.01',
        'refundReason.required' => 'Please provide a reason for the refund',
        'refundReason.min' => 'Reason must be at least 10 characters',
        'selectedPaymentId.required' => 'Please select a payment to refund',
    ];

    public function mount(Order $order)
    {
        $this->order = $order->load(['payments', 'refunds', 'items.product', 'items.variant']);
        
        if ($this->order->payments->count() === 0) {
            session()->flash('error', 'No payments found for this order');
            return redirect()->route('orders.details', $order);
        }

        $this->refundAmount = $this->order->total - $this->order->total_refunded;
        $this->selectedPaymentId = $this->order->payments->first()->id;
    }

    public function updatedRefundType()
    {
        if ($this->refundType === 'full') {
            $this->refundAmount = $this->order->total - $this->order->total_refunded;
        } else {
            $this->refundAmount = 0;
        }
    }

    public function processRefund()
    {
        $this->validate();

        // Additional validation
        $maxRefundable = $this->order->total - $this->order->total_refunded;
        if ($this->refundAmount > $maxRefundable) {
            $this->addError('refundAmount', "Refund amount cannot exceed $" . number_format($maxRefundable, 2));
            return;
        }

        $payment = Payment::findOrFail($this->selectedPaymentId);
        if ($payment->order_id !== $this->order->id) {
            session()->flash('error', 'Invalid payment selected');
            return;
        }

        DB::beginTransaction();

        try {
            // Create refund record
            $refund = $this->order->refunds()->create([
                'payment_id' => $this->selectedPaymentId,
                'amount' => $this->refundAmount,
                'reason' => $this->refundReason,
                'user_id' => Auth::id(),
            ]);

            // Update order status if fully refunded
            if ($this->order->total_refunded >= $this->order->total) {
                $this->order->update([
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                ]);
            }

            // Restock items if requested
            if ($this->restockItems) {
                foreach ($this->order->items as $item) {
                    $inventoriable = $item->variant ?? $item->product;
                    
                    if ($inventoriable && $inventoriable->inventory && $inventoriable->track_inventory) {
                        $inventoriable->inventory->adjust(
                            $item->quantity,
                            'add',
                            "Refund for Order #{$this->order->order_number}",
                            Auth::id()
                        );
                    }
                }
            }

            DB::commit();

            session()->flash('success', 'Refund processed successfully');
            return redirect()->route('orders.details', $this->order);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }

    public function getMaxRefundableAttribute()
    {
        return $this->order->total - $this->order->total_refunded;
    }

    public function getRefundablePaymentsAttribute()
    {
        return $this->order->payments->filter(function ($payment) {
            $refunded = $this->order->refunds()
                ->where('payment_id', $payment->id)
                ->sum('amount');
            return $refunded < $payment->amount;
        });
    }

    public function render()
    {
        return view('livewire.orders.order-refund', [
            'maxRefundable' => $this->maxRefundable,
            'refundablePayments' => $this->refundablePayments,
        ]);
    }
}