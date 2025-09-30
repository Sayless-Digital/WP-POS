<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class OrderDetails extends Component
{
    public Order $order;
    public $showCancelModal = false;
    public $cancelReason = '';
    public $showStatusModal = false;
    public $newStatus = '';
    public $showNotesModal = false;
    public $orderNotes = '';

    protected $rules = [
        'cancelReason' => 'required|string|min:10',
        'newStatus' => 'required|in:pending,processing,completed,cancelled',
        'orderNotes' => 'nullable|string|max:1000',
    ];

    public function mount(Order $order)
    {
        $this->order = $order->load([
            'items.product',
            'items.variant',
            'customer',
            'user',
            'payments',
            'refunds.user'
        ]);
        $this->orderNotes = $order->notes ?? '';
    }

    public function openCancelModal()
    {
        if ($this->order->status === 'cancelled') {
            session()->flash('error', 'Order is already cancelled');
            return;
        }

        if ($this->order->status === 'refunded') {
            session()->flash('error', 'Cannot cancel a refunded order');
            return;
        }

        $this->showCancelModal = true;
        $this->cancelReason = '';
    }

    public function cancelOrder()
    {
        $this->validate(['cancelReason' => 'required|string|min:10']);

        try {
            $orderService = app(OrderService::class);
            $orderService->cancelOrder($this->order, $this->cancelReason);

            $this->order = $this->order->fresh();
            $this->showCancelModal = false;
            
            session()->flash('success', 'Order cancelled successfully');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openStatusModal()
    {
        if ($this->order->status === 'cancelled') {
            session()->flash('error', 'Cannot change status of cancelled order');
            return;
        }

        $this->showStatusModal = true;
        $this->newStatus = $this->order->status;
    }

    public function updateStatus()
    {
        $this->validate(['newStatus' => 'required|in:pending,processing,completed,cancelled']);

        try {
            $orderService = app(OrderService::class);
            $this->order = $orderService->updateStatus($this->order, $this->newStatus);
            
            $this->showStatusModal = false;
            session()->flash('success', 'Order status updated successfully');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openNotesModal()
    {
        $this->showNotesModal = true;
        $this->orderNotes = $this->order->notes ?? '';
    }

    public function saveNotes()
    {
        $this->validate(['orderNotes' => 'nullable|string|max:1000']);

        try {
            $this->order->update(['notes' => $this->orderNotes]);
            $this->order = $this->order->fresh();
            
            $this->showNotesModal = false;
            session()->flash('success', 'Notes updated successfully');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function printOrder()
    {
        // This will be handled by JavaScript to open print dialog
        $this->dispatch('print-order');
    }

    public function duplicateOrder()
    {
        try {
            $orderService = app(OrderService::class);
            $cart = $orderService->duplicateOrderToCart($this->order);
            
            // Store cart in session and redirect to POS
            session(['pos_cart' => $cart]);
            session()->flash('success', 'Order items added to cart');
            
            return redirect()->route('pos.terminal');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function getOrderProfitability()
    {
        $orderService = app(OrderService::class);
        return $orderService->calculateProfitability($this->order);
    }

    public function render()
    {
        $profitability = $this->getOrderProfitability();

        return view('livewire.orders.order-details', [
            'profitability' => $profitability,
        ]);
    }
}