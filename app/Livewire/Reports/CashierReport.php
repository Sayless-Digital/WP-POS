<?php

namespace App\Livewire\Reports;

use App\Models\Order;
use App\Models\User;
use App\Models\CashDrawerSession;
use Carbon\Carbon;
use Livewire\Component;

class CashierReport extends Component
{
    public $period = 'today';
    public $startDate;
    public $endDate;
    public $selectedUser;

    public function mount()
    {
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    public function updatedPeriod($value)
    {
        switch ($value) {
            case 'today':
                $this->startDate = Carbon::today()->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = Carbon::yesterday()->format('Y-m-d');
                $this->endDate = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'last_week':
                $this->startDate = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->endDate = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
        }
    }

    public function getCashierPerformanceProperty()
    {
        $query = User::withCount(['orders' => function ($q) {
            $q->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])->where('status', 'completed');
        }])->with(['orders' => function ($q) {
            $q->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])->where('status', 'completed');
        }]);

        if ($this->selectedUser) {
            $query->where('id', $this->selectedUser);
        }

        $users = $query->get()->map(function ($user) {
            $orders = $user->orders;
            $totalSales = $orders->sum('total');
            $totalOrders = $orders->count();
            $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
            $totalDiscount = $orders->sum('discount_amount');
            $totalItems = $orders->sum(function ($order) {
                return $order->items->sum('quantity');
            });

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'average_order_value' => $averageOrderValue,
                'total_discount' => $totalDiscount,
                'total_items' => $totalItems,
            ];
        })->sortByDesc('total_sales');

        return $users;
    }

    public function getCashDrawerSessionsProperty()
    {
        $query = CashDrawerSession::with('user')
            ->whereBetween('opened_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);

        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        return $query->orderBy('opened_at', 'desc')->get();
    }

    public function getTopPerformersProperty()
    {
        return $this->cashierPerformance->take(5);
    }

    public function getOverallStatisticsProperty()
    {
        $performance = $this->cashierPerformance;

        return [
            'total_cashiers' => $performance->count(),
            'total_sales' => $performance->sum('total_sales'),
            'total_orders' => $performance->sum('total_orders'),
            'total_discounts' => $performance->sum('total_discount'),
            'average_sales_per_cashier' => $performance->count() > 0 ? $performance->sum('total_sales') / $performance->count() : 0,
        ];
    }

    public function exportCsv()
    {
        $performance = $this->cashierPerformance;
        $sessions = $this->cashDrawerSessions;

        $filename = 'cashier_report_' . $this->startDate . '_to_' . $this->endDate . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($performance, $sessions) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['Cashier Performance Report']);
            fputcsv($file, ['Period', $this->startDate . ' to ' . $this->endDate]);
            fputcsv($file, []);

            // Performance section
            fputcsv($file, ['Cashier Performance']);
            fputcsv($file, ['Cashier Name', 'Email', 'Total Sales', 'Total Orders', 'Avg Order Value', 'Total Discount', 'Items Sold']);
            
            foreach ($performance as $cashier) {
                fputcsv($file, [
                    $cashier['name'],
                    $cashier['email'],
                    number_format($cashier['total_sales'], 2),
                    $cashier['total_orders'],
                    number_format($cashier['average_order_value'], 2),
                    number_format($cashier['total_discount'], 2),
                    $cashier['total_items'],
                ]);
            }
            fputcsv($file, []);

            // Cash drawer sessions
            fputcsv($file, ['Cash Drawer Sessions']);
            fputcsv($file, ['Cashier', 'Opened At', 'Closed At', 'Opening Balance', 'Closing Balance', 'Expected Balance', 'Difference', 'Status']);
            
            foreach ($sessions as $session) {
                fputcsv($file, [
                    $session->user->name,
                    $session->opened_at,
                    $session->closed_at ?? 'Open',
                    number_format($session->opening_balance, 2),
                    $session->closing_balance ? number_format($session->closing_balance, 2) : 'N/A',
                    $session->expected_balance ? number_format($session->expected_balance, 2) : 'N/A',
                    $session->difference ? number_format($session->difference, 2) : 'N/A',
                    ucfirst($session->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.reports.cashier-report', [
            'cashierPerformance' => $this->cashierPerformance,
            'cashDrawerSessions' => $this->cashDrawerSessions,
            'topPerformers' => $this->topPerformers,
            'overallStatistics' => $this->overallStatistics,
            'users' => User::orderBy('name')->get(),
        ]);
    }
}