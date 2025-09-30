<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use Livewire\Component;

class OrderInvoice extends Component
{
    public Order $order;
    public $companyInfo = [
        'name' => 'Your Company Name',
        'address' => '123 Business Street',
        'city' => 'City, State 12345',
        'phone' => '(555) 123-4567',
        'email' => 'info@yourcompany.com',
        'website' => 'www.yourcompany.com',
        'tax_id' => 'TAX-123456',
    ];

    public function mount(Order $order)
    {
        $this->order = $order->load([
            'items.product',
            'items.variant',
            'customer',
            'user',
            'payments'
        ]);

        // Load company info from config or database
        $this->loadCompanyInfo();
    }

    public function loadCompanyInfo()
    {
        // You can load this from config, database, or environment variables
        $this->companyInfo = [
            'name' => config('app.name', 'WP-POS'),
            'address' => env('COMPANY_ADDRESS', '123 Business Street'),
            'city' => env('COMPANY_CITY', 'City, State 12345'),
            'phone' => env('COMPANY_PHONE', '(555) 123-4567'),
            'email' => env('COMPANY_EMAIL', 'info@yourcompany.com'),
            'website' => env('COMPANY_WEBSITE', 'www.yourcompany.com'),
            'tax_id' => env('COMPANY_TAX_ID', 'TAX-123456'),
        ];
    }

    public function print()
    {
        $this->dispatch('print-invoice');
    }

    public function downloadPdf()
    {
        // This would require a PDF library like DomPDF or Snappy
        // For now, we'll just trigger the print dialog
        $this->dispatch('print-invoice');
    }

    public function emailInvoice()
    {
        if (!$this->order->customer || !$this->order->customer->email) {
            session()->flash('error', 'Customer email not available');
            return;
        }

        try {
            // Here you would send the invoice via email
            // Mail::to($this->order->customer->email)->send(new OrderInvoice($this->order));
            
            session()->flash('success', 'Invoice sent to ' . $this->order->customer->email);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send invoice: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.orders.order-invoice');
    }
}