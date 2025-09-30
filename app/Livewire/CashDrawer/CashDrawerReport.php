<?php

namespace App\Livewire\CashDrawer;

use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Computed;

class CashDrawerReport extends Component
{
    public $period = 'today';
    public $startDate = '';
    public $endDate = '';
    public $userId = 'all';

    public function mount()
    {
        $this->startDate = now()->startOfDay()->format('Y-m-d');
        $this->endDate = now()->endOfDay()->format('Y-m-d');
    }

    #[Computed]
    public function dateRange()
    {
        return match($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'custom' => [$this->startDate, $this->endDate],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    #[Computed]
    public function sessions()
    {
        [$start, $end] = $this->dateRange;

        $query = CashDrawerSession::with('user')
            ->whereBetween('opened_at', [$start, $end]);

        if ($this->userId !== 'all') {
            $query->where('user_id', $this->userId);
        }

        return $query->latest('opened_at')->get();
    }

    #[Computed]
    public function statistics()
    {
        [$start, $end] = $this->dateRange;

        $sessionsQuery = CashDrawerSession::whereBetween('opened_at', [$start, $end]);
        
        if ($this->userId !== 'all') {
            $sessionsQuery->where('user_id', $this->userId);
        }

        $sessions = $sessionsQuery->get();
        $sessionIds = $sessions->pluck('id');

        // Cash movements
        $movements = CashMovement::whereIn('cash_drawer_session_id', $sessionIds)->get();
        
        // Cash sales
        $cashSales = Payment::whereHas('order', function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->where('payment_method', 'cash')
            ->sum('amount');

        return [
            'total_sessions' => $sessions->count(),
            'open_sessions' => $sessions->where('closed_at', null)->count(),
            'closed_sessions' => $sessions->whereNotNull('closed_at')->count(),
            'total_opening' => $sessions->sum('opening_amount'),
            'total_closing' => $sessions->whereNotNull('closing_amount')->sum('closing_amount'),
            'total_expected' => $sessions->whereNotNull('expected_amount')->sum('expected_amount'),
            'total_difference' => $sessions->whereNotNull('difference')->sum('difference'),
            'cash_in' => $movements->where('type', 'in')->sum('amount'),
            'cash_out' => $movements->where('type', 'out')->sum('amount'),
            'cash_sales' => $cashSales,
            'sessions_with_discrepancy' => $sessions->filter(function ($session) {
                return $session->hasDiscrepancy();
            })->count(),
        ];
    }

    #[Computed]
    public function userPerformance()
    {
        [$start, $end] = $this->dateRange;

        return CashDrawerSession::select(
                'user_id',
                DB::raw('COUNT(*) as session_count'),
                DB::raw('SUM(opening_amount) as total_opening'),
                DB::raw('SUM(closing_amount) as total_closing'),
                DB::raw('SUM(expected_amount) as total_expected'),
                DB::raw('SUM(difference) as total_difference'),
                DB::raw('AVG(ABS(difference)) as avg_discrepancy')
            )
            ->with('user')
            ->whereBetween('opened_at', [$start, $end])
            ->whereNotNull('closed_at')
            ->groupBy('user_id')
            ->get();
    }

    #[Computed]
    public function discrepancyAnalysis()
    {
        [$start, $end] = $this->dateRange;

        $query = CashDrawerSession::whereBetween('opened_at', [$start, $end])
            ->whereNotNull('closed_at');

        if ($this->userId !== 'all') {
            $query->where('user_id', $this->userId);
        }

        $sessions = $query->get();

        return [
            'over' => $sessions->filter(fn($s) => $s->isOver())->count(),
            'short' => $sessions->filter(fn($s) => $s->isShort())->count(),
            'exact' => $sessions->filter(fn($s) => !$s->hasDiscrepancy())->count(),
            'total_over_amount' => $sessions->filter(fn($s) => $s->isOver())->sum('difference'),
            'total_short_amount' => abs($sessions->filter(fn($s) => $s->isShort())->sum('difference')),
        ];
    }

    #[Computed]
    public function users()
    {
        return \App\Models\User::orderBy('name')->get();
    }

    public function exportCsv()
    {
        $sessions = $this->sessions;
        
        $filename = 'cash-drawer-report-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($sessions) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Session ID',
                'User',
                'Opened At',
                'Closed At',
                'Opening Amount',
                'Expected Amount',
                'Closing Amount',
                'Difference',
                'Status',
                'Duration (minutes)',
            ]);

            // Data
            foreach ($sessions as $session) {
                fputcsv($file, [
                    $session->id,
                    $session->user->name,
                    $session->opened_at->format('Y-m-d H:i:s'),
                    $session->closed_at?->format('Y-m-d H:i:s') ?? 'Open',
                    number_format($session->opening_amount, 2),
                    number_format($session->expected_amount ?? 0, 2),
                    number_format($session->closing_amount ?? 0, 2),
                    number_format($session->difference ?? 0, 2),
                    $session->closed_at ? 'Closed' : 'Open',
                    $session->duration_minutes ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updatedPeriod()
    {
        if ($this->period !== 'custom') {
            [$start, $end] = $this->dateRange;
            $this->startDate = $start->format('Y-m-d');
            $this->endDate = $end->format('Y-m-d');
        }
    }

    public function render()
    {
        return view('livewire.cash-drawer.cash-drawer-report');
    }
}