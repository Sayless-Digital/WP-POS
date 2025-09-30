<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptService
{
    /**
     * Generate receipt for an order
     *
     * @param Order $order
     * @param string $format 'html' or 'pdf'
     * @return string|array
     */
    public function generateReceipt(Order $order, string $format = 'html')
    {
        $data = $this->prepareReceiptData($order);

        if ($format === 'pdf') {
            return $this->generatePDF($data);
        }

        return $this->generateHTML($data);
    }

    /**
     * Prepare receipt data
     *
     * @param Order $order
     * @return array
     */
    protected function prepareReceiptData(Order $order): array
    {
        return [
            'order' => $order->load(['items', 'payments', 'customer', 'user']),
            'store_name' => config('pos.store_name', config('app.name')),
            'store_address' => config('pos.store_address', ''),
            'store_phone' => config('pos.store_phone', ''),
            'store_email' => config('pos.store_email', ''),
            'tax_id' => config('pos.tax_id', ''),
            'receipt_footer' => config('pos.receipt_footer', 'Thank you for your business!'),
            'currency_symbol' => config('pos.currency_symbol', '$'),
            'show_logo' => config('pos.show_logo_on_receipt', false),
            'logo_url' => config('pos.logo_url', ''),
        ];
    }

    /**
     * Generate HTML receipt
     *
     * @param array $data
     * @return string
     */
    protected function generateHTML(array $data): string
    {
        $order = $data['order'];
        $currency = $data['currency_symbol'];

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - ' . $order->order_number . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            max-width: 300px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .store-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-info {
            font-size: 10px;
            color: #666;
        }
        .order-info {
            margin: 15px 0;
            font-size: 11px;
        }
        .order-info div {
            margin: 3px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            font-size: 11px;
        }
        .items-table td {
            padding: 5px 0;
            border-bottom: 1px dashed #ccc;
        }
        .item-name {
            font-weight: bold;
        }
        .item-details {
            font-size: 10px;
            color: #666;
        }
        .totals {
            margin: 15px 0;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .total-row.grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 10px;
        }
        .payments {
            margin: 15px 0;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 11px;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #000;
            font-size: 11px;
        }
        .barcode {
            text-align: center;
            margin: 15px 0;
            font-family: "Courier New", monospace;
            font-size: 10px;
        }
    </style>
</head>
<body>';

        // Header
        $html .= '<div class="receipt-header">';
        if ($data['show_logo'] && $data['logo_url']) {
            $html .= '<img src="' . $data['logo_url'] . '" alt="Logo" style="max-width: 150px; margin-bottom: 10px;">';
        }
        $html .= '<div class="store-name">' . htmlspecialchars($data['store_name']) . '</div>';
        if ($data['store_address']) {
            $html .= '<div class="store-info">' . nl2br(htmlspecialchars($data['store_address'])) . '</div>';
        }
        if ($data['store_phone']) {
            $html .= '<div class="store-info">Tel: ' . htmlspecialchars($data['store_phone']) . '</div>';
        }
        if ($data['tax_id']) {
            $html .= '<div class="store-info">Tax ID: ' . htmlspecialchars($data['tax_id']) . '</div>';
        }
        $html .= '</div>';

        // Order Info
        $html .= '<div class="order-info">';
        $html .= '<div><strong>Receipt #:</strong> ' . htmlspecialchars($order->order_number) . '</div>';
        $html .= '<div><strong>Date:</strong> ' . $order->created_at->format('Y-m-d H:i:s') . '</div>';
        $html .= '<div><strong>Cashier:</strong> ' . htmlspecialchars($order->user->name) . '</div>';
        if ($order->customer) {
            $html .= '<div><strong>Customer:</strong> ' . htmlspecialchars($order->customer->first_name . ' ' . $order->customer->last_name) . '</div>';
        }
        $html .= '</div>';

        // Items
        $html .= '<table class="items-table">';
        $html .= '<thead><tr><th>Item</th><th style="text-align: right;">Qty</th><th style="text-align: right;">Price</th><th style="text-align: right;">Total</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($order->items as $item) {
            $html .= '<tr>';
            $html .= '<td><div class="item-name">' . htmlspecialchars($item->name) . '</div>';
            $html .= '<div class="item-details">SKU: ' . htmlspecialchars($item->sku) . '</div></td>';
            $html .= '<td style="text-align: right;">' . $item->quantity . '</td>';
            $html .= '<td style="text-align: right;">' . $currency . number_format($item->price, 2) . '</td>';
            $html .= '<td style="text-align: right;">' . $currency . number_format($item->total, 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';

        // Totals
        $html .= '<div class="totals">';
        $html .= '<div class="total-row"><span>Subtotal:</span><span>' . $currency . number_format($order->subtotal, 2) . '</span></div>';
        
        if ($order->discount_amount > 0) {
            $html .= '<div class="total-row"><span>Discount:</span><span>-' . $currency . number_format($order->discount_amount, 2) . '</span></div>';
        }
        
        if ($order->tax_amount > 0) {
            $html .= '<div class="total-row"><span>Tax:</span><span>' . $currency . number_format($order->tax_amount, 2) . '</span></div>';
        }
        
        $html .= '<div class="total-row grand-total"><span>TOTAL:</span><span>' . $currency . number_format($order->total, 2) . '</span></div>';
        $html .= '</div>';

        // Payments
        if ($order->payments->count() > 0) {
            $html .= '<div class="payments">';
            $html .= '<div style="font-weight: bold; margin-bottom: 5px;">Payment Details:</div>';
            
            foreach ($order->payments as $payment) {
                $html .= '<div class="payment-row">';
                $html .= '<span>' . ucfirst($payment->payment_method) . ':</span>';
                $html .= '<span>' . $currency . number_format($payment->amount, 2) . '</span>';
                $html .= '</div>';
            }
            
            $totalPaid = $order->payments->sum('amount');
            if ($totalPaid > $order->total) {
                $change = $totalPaid - $order->total;
                $html .= '<div class="payment-row" style="font-weight: bold; margin-top: 5px;">';
                $html .= '<span>Change:</span>';
                $html .= '<span>' . $currency . number_format($change, 2) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        // Barcode
        $html .= '<div class="barcode">';
        $html .= '<div>*' . $order->order_number . '*</div>';
        $html .= '</div>';

        // Footer
        $html .= '<div class="receipt-footer">';
        $html .= '<div>' . nl2br(htmlspecialchars($data['receipt_footer'])) . '</div>';
        $html .= '<div style="margin-top: 10px; font-size: 10px;">Powered by ' . config('app.name') . '</div>';
        $html .= '</div>';

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Generate PDF receipt
     *
     * @param array $data
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generatePDF(array $data)
    {
        $html = $this->generateHTML($data);
        
        return PDF::loadHTML($html)
            ->setPaper([0, 0, 226.77, 841.89], 'portrait') // 80mm width thermal paper
            ->setOption('margin-top', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0);
    }

    /**
     * Generate and download PDF receipt
     *
     * @param Order $order
     * @param string|null $filename
     * @return \Illuminate\Http\Response
     */
    public function downloadReceipt(Order $order, ?string $filename = null)
    {
        $filename = $filename ?? "receipt-{$order->order_number}.pdf";
        $pdf = $this->generateReceipt($order, 'pdf');
        
        return $pdf->download($filename);
    }

    /**
     * Generate and stream PDF receipt
     *
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function streamReceipt(Order $order)
    {
        $pdf = $this->generateReceipt($order, 'pdf');
        
        return $pdf->stream("receipt-{$order->order_number}.pdf");
    }

    /**
     * Generate email-friendly receipt
     *
     * @param Order $order
     * @return string
     */
    public function generateEmailReceipt(Order $order): string
    {
        $data = $this->prepareReceiptData($order);
        return $this->generateHTML($data);
    }

    /**
     * Generate thermal printer receipt (plain text)
     *
     * @param Order $order
     * @return string
     */
    public function generateThermalReceipt(Order $order): string
    {
        $data = $this->prepareReceiptData($order);
        $order = $data['order'];
        $currency = $data['currency_symbol'];
        $width = 32; // Characters width for 80mm thermal paper

        $receipt = '';
        
        // Header
        $receipt .= $this->centerText($data['store_name'], $width) . "\n";
        if ($data['store_address']) {
            $receipt .= $this->centerText($data['store_address'], $width) . "\n";
        }
        if ($data['store_phone']) {
            $receipt .= $this->centerText('Tel: ' . $data['store_phone'], $width) . "\n";
        }
        $receipt .= str_repeat('=', $width) . "\n\n";

        // Order info
        $receipt .= "Receipt #: " . $order->order_number . "\n";
        $receipt .= "Date: " . $order->created_at->format('Y-m-d H:i:s') . "\n";
        $receipt .= "Cashier: " . $order->user->name . "\n";
        if ($order->customer) {
            $receipt .= "Customer: " . $order->customer->first_name . ' ' . $order->customer->last_name . "\n";
        }
        $receipt .= str_repeat('-', $width) . "\n\n";

        // Items
        foreach ($order->items as $item) {
            $receipt .= wordwrap($item->name, $width) . "\n";
            $receipt .= sprintf("  %d x %s%-8s %s%8s\n",
                $item->quantity,
                $currency,
                number_format($item->price, 2),
                $currency,
                number_format($item->total, 2)
            );
        }
        
        $receipt .= str_repeat('-', $width) . "\n";

        // Totals
        $receipt .= sprintf("%-20s %s%10s\n", "Subtotal:", $currency, number_format($order->subtotal, 2));
        if ($order->discount_amount > 0) {
            $receipt .= sprintf("%-20s -%s%9s\n", "Discount:", $currency, number_format($order->discount_amount, 2));
        }
        if ($order->tax_amount > 0) {
            $receipt .= sprintf("%-20s %s%10s\n", "Tax:", $currency, number_format($order->tax_amount, 2));
        }
        $receipt .= str_repeat('=', $width) . "\n";
        $receipt .= sprintf("%-20s %s%10s\n", "TOTAL:", $currency, number_format($order->total, 2));
        $receipt .= str_repeat('=', $width) . "\n\n";

        // Payments
        if ($order->payments->count() > 0) {
            $receipt .= "Payment Details:\n";
            foreach ($order->payments as $payment) {
                $receipt .= sprintf("  %-18s %s%10s\n",
                    ucfirst($payment->payment_method) . ':',
                    $currency,
                    number_format($payment->amount, 2)
                );
            }
            
            $totalPaid = $order->payments->sum('amount');
            if ($totalPaid > $order->total) {
                $change = $totalPaid - $order->total;
                $receipt .= sprintf("  %-18s %s%10s\n", "Change:", $currency, number_format($change, 2));
            }
            $receipt .= "\n";
        }

        // Footer
        $receipt .= str_repeat('=', $width) . "\n";
        $receipt .= $this->centerText($data['receipt_footer'], $width) . "\n";
        $receipt .= $this->centerText('Powered by ' . config('app.name'), $width) . "\n";
        $receipt .= "\n\n\n"; // Feed paper

        return $receipt;
    }

    /**
     * Center text for thermal printer
     *
     * @param string $text
     * @param int $width
     * @return string
     */
    protected function centerText(string $text, int $width): string
    {
        $padding = max(0, ($width - strlen($text)) / 2);
        return str_repeat(' ', (int)$padding) . $text;
    }
}