<?php

namespace App\Livewire\CashDrawer;

use App\Models\CashDrawerSession as CashDrawerSessionModel;
use App\Models\CashMovement;
use Livewire\Component;
use Livewire\Attributes\Computed;

class CashDrawerSession extends Component
{
    public $openingAmount = 0;
    public $closingAmount = 0;
    public $notes = '';
    public $showOpenModal = false;
    public $showCloseModal = false;
    public $activeSession = null;

    protected $rules = [
        'openingAmount' => 'required|numeric|min:0',
        'closingAmount' => 'required|numeric|min:0',
        'notes' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        $this->loadActiveSession();
    }

    public function loadActiveSession()
    {
        $this->activeSession = CashDrawerSessionModel::where('user_id', auth()->id())
            ->open()
            ->latest()
            ->first();
    }

    #[Computed]
    public function hasActiveSession()
    {
        return $this->activeSession !== null;
    }

    #[Computed]
    public function sessionStats()
    {
        if (!$this->activeSession) {
            return null;
        }

        $session = $this->activeSession;
        
        // Get cash sales during session
        $cashSales = $session->orders()
            ->whereHas('payments', function ($query) {
                $query->where('payment_method', 'cash');
            })
            ->with('payments')
            ->get()
            ->sum(function ($order) {
                return $order->payments->where('payment_method', 'cash')->sum('amount');
            });

        // Get cash movements
        $cashIn = $session->cashMovements()->cashIn()->sum('amount');
        $cashOut = $session->cashMovements()->cashOut()->sum('amount');

        // Calculate expected amount
        $expectedAmount = $session->opening_amount + $cashSales + $cashIn - $cashOut;

        return [
            'opening_amount' => $session->opening_amount,
            'cash_sales' => $cashSales,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'expected_amount' => $expectedAmount,
            'current_balance' => $expectedAmount,
            'duration' => $session->opened_at->diffForHumans(null, true),
        ];
    }

    #[Computed]
    public function recentSessions()
    {
        return CashDrawerSessionModel::where('user_id', auth()->id())
            ->closed()
            ->with('user')
            ->latest('closed_at')
            ->take(10)
            ->get();
    }

    public function openDrawer()
    {
        // Check if user already has an open session
        if ($this->hasActiveSession) {
            session()->flash('error', 'You already have an open cash drawer session.');
            return;
        }

        $this->showOpenModal = true;
        $this->openingAmount = 0;
        $this->notes = '';
    }

    public function confirmOpen()
    {
        $this->validate([
            'openingAmount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $session = CashDrawerSessionModel::create([
                'user_id' => auth()->id(),
                'opening_amount' => $this->openingAmount,
                'notes' => $this->notes,
            ]);

            // Create opening float movement
            CashMovement::cashIn(
                $session->id,
                $this->openingAmount,
                'opening_float',
                auth()->id(),
                'Opening cash drawer'
            );

            $this->loadActiveSession();
            $this->showOpenModal = false;
            $this->reset(['openingAmount', 'notes']);

            session()->flash('success', 'Cash drawer opened successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to open cash drawer: ' . $e->getMessage());
        }
    }

    public function closeDrawer()
    {
        if (!$this->hasActiveSession) {
            session()->flash('error', 'No active cash drawer session found.');
            return;
        }

        $this->showCloseModal = true;
        $this->closingAmount = 0;
        $this->notes = '';
    }

    public function confirmClose()
    {
        $this->validate([
            'closingAmount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $session = $this->activeSession;
            
            // Close the session
            $session->close($this->closingAmount, $this->notes);

            // Create closing float movement
            CashMovement::cashOut(
                $session->id,
                $this->closingAmount,
                'closing_float',
                auth()->id(),
                'Closing cash drawer'
            );

            $this->loadActiveSession();
            $this->showCloseModal = false;
            $this->reset(['closingAmount', 'notes']);

            session()->flash('success', 'Cash drawer closed successfully.');
            
            // Show discrepancy warning if needed
            if ($session->hasDiscrepancy()) {
                $type = $session->isOver() ? 'over' : 'short';
                $amount = abs($session->difference);
                session()->flash('warning', "Cash drawer is {$type} by $" . number_format($amount, 2));
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to close cash drawer: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.cash-drawer.cash-drawer-session');
    }
}