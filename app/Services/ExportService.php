<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    /**
     * Export data to CSV
     *
     * @param Collection|array $data
     * @param array $headers
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToCSV($data, array $headers, string $filename = 'export.csv')
    {
        $data = $data instanceof Collection ? $data->toArray() : $data;

        $callback = function() use ($data, $headers) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write headers
            fputcsv($file, $headers);
            
            // Write data
            foreach ($data as $row) {
                $csvRow = [];
                foreach ($headers as $header) {
                    $key = $this->headerToKey($header);
                    $csvRow[] = $row[$key] ?? '';
                }
                fputcsv($file, $csvRow);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export data to Excel-compatible CSV
     *
     * @param Collection|array $data
     * @param array $headers
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToExcel($data, array $headers, string $filename = 'export.csv')
    {
        return $this->exportToCSV($data, $headers, $filename);
    }

    /**
     * Export sales report to CSV
     *
     * @param array $salesData
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportSalesReport(array $salesData, string $filename = 'sales_report.csv')
    {
        $headers = [
            'Date',
            'Order Count',
            'Revenue',
            'Tax',
            'Discounts',
            'Net Revenue',
        ];

        return $this->exportToCSV($salesData, $headers, $filename);
    }

    /**
     * Export products to CSV
     *
     * @param Collection $products
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportProducts(Collection $products, string $filename = 'products.csv')
    {
        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'category' => $product->category?->name,
                'tax_rate' => $product->tax_rate,
                'stock' => $product->inventory?->quantity ?? 0,
                'is_active' => $product->is_active ? 'Yes' : 'No',
            ];
        });

        $headers = [
            'ID',
            'SKU',
            'Name',
            'Description',
            'Price',
            'Cost Price',
            'Category',
            'Tax Rate',
            'Stock',
            'Active',
        ];

        return $this->exportToCSV($data, $headers, $filename);
    }

    /**
     * Export customers to CSV
     *
     * @param Collection $customers
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCustomers(Collection $customers, string $filename = 'customers.csv')
    {
        $data = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'postal_code' => $customer->postal_code,
                'customer_group' => $customer->customerGroup?->name,
                'total_spent' => $customer->total_spent,
                'total_orders' => $customer->total_orders,
                'loyalty_points' => $customer->loyalty_points,
                'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $headers = [
            'ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Address',
            'City',
            'Postal Code',
            'Customer Group',
            'Total Spent',
            'Total Orders',
            'Loyalty Points',
            'Registered Date',
        ];

        return $this->exportToCSV($data, $headers, $filename);
    }

    /**
     * Export orders to CSV
     *
     * @param Collection $orders
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportOrders(Collection $orders, string $filename = 'orders.csv')
    {
        $data = $orders->map(function ($order) {
            return [
                'order_number' => $order->order_number,
                'date' => $order->created_at->format('Y-m-d H:i:s'),
                'customer' => $order->customer 
                    ? $order->customer->first_name . ' ' . $order->customer->last_name 
                    : 'Guest',
                'cashier' => $order->user->name,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax_amount,
                'discount' => $order->discount_amount,
                'total' => $order->total,
                'items_count' => $order->items->count(),
            ];
        });

        $headers = [
            'Order Number',
            'Date',
            'Customer',
            'Cashier',
            'Status',
            'Payment Status',
            'Subtotal',
            'Tax',
            'Discount',
            'Total',
            'Items Count',
        ];

        return $this->exportToCSV($data, $headers, $filename);
    }

    /**
     * Export inventory to CSV
     *
     * @param Collection $inventory
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportInventory(Collection $inventory, string $filename = 'inventory.csv')
    {
        $data = $inventory->map(function ($item) {
            $inventoriable = $item->inventoriable;
            return [
                'sku' => $inventoriable->sku ?? '',
                'name' => $inventoriable->name ?? '',
                'quantity' => $item->quantity,
                'reserved' => $item->reserved_quantity,
                'available' => $item->quantity - $item->reserved_quantity,
                'low_stock_threshold' => $item->low_stock_threshold,
                'status' => $item->quantity <= $item->low_stock_threshold ? 'Low Stock' : 'In Stock',
                'last_counted' => $item->last_counted_at?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        });

        $headers = [
            'SKU',
            'Product Name',
            'Quantity',
            'Reserved',
            'Available',
            'Low Stock Threshold',
            'Status',
            'Last Counted',
        ];

        return $this->exportToCSV($data, $headers, $filename);
    }

    /**
     * Export data to PDF
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     * @param array $options
     * @return \Illuminate\Http\Response
     */
    public function exportToPDF(string $view, array $data, string $filename = 'export.pdf', array $options = [])
    {
        $pdf = PDF::loadView($view, $data);

        // Apply options
        if (isset($options['orientation'])) {
            $pdf->setPaper('a4', $options['orientation']);
        }

        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper']);
        }

        if ($options['download'] ?? true) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Export sales report to PDF
     *
     * @param array $reportData
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function exportSalesReportPDF(array $reportData, string $filename = 'sales_report.pdf')
    {
        return $this->exportToPDF('reports.sales-pdf', $reportData, $filename);
    }

    /**
     * Convert header to array key
     *
     * @param string $header
     * @return string
     */
    protected function headerToKey(string $header): string
    {
        return strtolower(str_replace(' ', '_', $header));
    }

    /**
     * Export data to JSON
     *
     * @param Collection|array $data
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function exportToJSON($data, string $filename = 'export.json')
    {
        $data = $data instanceof Collection ? $data->toArray() : $data;
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export data to XML
     *
     * @param Collection|array $data
     * @param string $rootElement
     * @param string $itemElement
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function exportToXML($data, string $rootElement = 'data', string $itemElement = 'item', string $filename = 'export.xml')
    {
        $data = $data instanceof Collection ? $data->toArray() : $data;

        $xml = new \SimpleXMLElement("<{$rootElement}/>");
        
        foreach ($data as $row) {
            $item = $xml->addChild($itemElement);
            foreach ($row as $key => $value) {
                $item->addChild($key, htmlspecialchars($value ?? ''));
            }
        }

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Create a downloadable file from content
     *
     * @param string $content
     * @param string $filename
     * @param string $mimeType
     * @return \Illuminate\Http\Response
     */
    public function createDownload(string $content, string $filename, string $mimeType = 'text/plain')
    {
        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($content),
        ]);
    }

    /**
     * Format currency value for export
     *
     * @param float $value
     * @param string $currency
     * @return string
     */
    protected function formatCurrency(float $value, string $currency = '$'): string
    {
        return $currency . number_format($value, 2);
    }

    /**
     * Format date for export
     *
     * @param \DateTime|string $date
     * @param string $format
     * @return string
     */
    protected function formatDate($date, string $format = 'Y-m-d H:i:s'): string
    {
        if ($date instanceof \DateTime) {
            return $date->format($format);
        }
        
        if (is_string($date)) {
            return date($format, strtotime($date));
        }

        return '';
    }

    /**
     * Sanitize data for export
     *
     * @param mixed $value
     * @return mixed
     */
    protected function sanitize($value)
    {
        if (is_string($value)) {
            // Remove any potential CSV injection characters
            if (in_array(substr($value, 0, 1), ['=', '+', '-', '@'])) {
                $value = "'" . $value;
            }
        }

        return $value;
    }
}