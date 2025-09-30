<?php

namespace App\Livewire\CashDrawer;

use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class CashMovements extends Component
{
    use WithPagination;

    public $type = 'in'; // 'in' or 'out'
    public $amount = 0;
    public $reason = '';
    public $notes = '';
    public $showModal = false;
    public $filterType = 'all';
    public $filterReason = 'all';
    public $search = '';

    protected $rules = [
        'type' => 'required|in:in,out',
        'amount' => 'required|numeric|min:0.01',
        'reason' => 'required|string',
        'notes' => 'nullable|string|max:500',
    ];

    protected $queryString = [
        'filterType' => ['except' => 'all'],
        'filterReason' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    #[Computed]
    public function activeSession()
    {
        return CashDrawerSession::where('user_id', auth()->id())
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
    public function movements()
    {
        if (!$this->hasActiveSession) {
            return collect();
        }

        $query = CashMovement::where('cash_drawer_session_id', $this->activeSession->id)
            ->with('user')
            ->latest('created_at');

        // Apply filters
        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterReason !== 'all') {
            $query->where('reason', $this->filterReason);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('notes', 'like', '%' . $this->search . '%')
                  ->orWhere('reason', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(15);
    }

    #[Computed]
    public function statistics()
    {
        if (!$this->hasActiveSession) {
            return null;
        }

        $sessionId = $this->activeSession->id;

        return [
            'total_in' => CashMovement::where('cash_drawer_session_id', $sessionId)
                ->cashIn()
                ->sum('amount'),
            'total_out' => CashMovement::where('cash_drawer_session_id', $sessionId)
                ->cashOut()
                ->sum('amount'),
            'count_in' => CashMovement::where('cash_drawer_session_id', $sessionId)
                ->cashIn()
                ->count(),
            'count_out' => CashMovement::where('cash_drawer_session_id', $sessionId)
                ->cashOut()
                ->count(),
        ];
    }

    #[Computed]
    public function reasonOptions()
    {
        return [
            'opening_float' => 'Opening Float',
            'closing_float' => 'Closing Float',
            'bank_deposit' => 'Bank Deposit',
            'expense' => 'Expense',
            'refund' => 'Refund',
            'correction' => 'Correction',
            'petty_cash' => 'Petty Cash',
            'change_fund' => 'Change Fund',
            'other' => 'Other',
        ];
    }

    public function openModal($type = 'in')
    {
        if (!$this->hasActiveSession) {
            session()->flash('error', 'No active cash drawer session. Please open a session first.');
            return;
        }

        $this->type = $type;
        $this->amount = 0;
        $this->reason = '';
        $this->notes = '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if (!$this->hasActiveSession) {
            session()->flash('error', 'No active cash drawer session found.');
            return;
        }

        try {
            if ($this->type === 'in') {
                CashMovement::cashIn(
                    $this->activeSession->id,
                    $this->amount,
                    $this->reason,
                    auth()->id(),
                    $this->notes
                );
            } else {
                CashMovement::cashOut(
                    $this->activeSession->id,
                    $this->amount,
                    $this->reason,
                    auth()->id(),
                    $this->notes
                );
            }

            $this->showModal = false;
            $this->reset(['amount', 'reason', 'notes']);
            
            session()->flash('success', 'Cash movement recorded successfully.');
            
            // Reset pagination to show the new movement
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to record cash movement: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->reset(['filterType', 'filterReason', 'search']);
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterReason()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.cash-drawer.cash-movements');
    }
}