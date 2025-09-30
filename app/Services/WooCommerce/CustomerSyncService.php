<?php

namespace App\Services\WooCommerce;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerSyncService
{
    protected WooCommerceClient $client;

    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }

    /**
     * Import all customers from WooCommerce
     */
    public function importAll(): array
    {
        $startTime = now();
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            Log::info('Starting WooCommerce customer import');

            $response = $this->client->getAll('customers', [
                'orderby' => 'id',
                'order' => 'asc'
            ]);

            if (!$response['success']) {
                throw new \Exception($response['message'] ?? 'Failed to fetch customers');
            }

            $customers = $response['data'];
            $stats['total'] = count($customers);

            foreach ($customers as $wooCustomer) {
                try {
                    $result = $this->importCustomer($wooCustomer);
                    
                    if ($result['created']) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $stats['errors'][] = [
                        'customer_id' => $wooCustomer['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to import customer', [
                        'woo_customer_id' => $wooCustomer['id'] ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logSync('customer', 'import', $stats, $startTime);

            Log::info('WooCommerce customer import completed', $stats);

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (\Exception $e) {
            Log::error('Customer import failed', [
                'error' => $e->getMessage()
            ]);

            $this->logSync('customer', 'import', $stats, $startTime, 'failed', $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $stats
            ];
        }
    }

    /**
     * Import a single customer from WooCommerce
     */
    public function importCustomer(array $wooCustomer): array
    {
        DB::beginTransaction();

        try {
            $customer = Customer::where('woocommerce_id', $wooCustomer['id'])->first();
            $isNew = !$customer;

            if (!$customer) {
                $customer = new Customer();
            }

            // Map WooCommerce data to local customer
            $customer->woocommerce_id = $wooCustomer['id'];
            $customer->first_name = $wooCustomer['first_name'] ?? '';
            $customer->last_name = $wooCustomer['last_name'] ?? '';
            $customer->email = $wooCustomer['email'] ?? null;
            
            // Get billing information
            $billing = $wooCustomer['billing'] ?? [];
            $customer->phone = $billing['phone'] ?? null;
            $customer->address = $billing['address_1'] ?? null;
            $customer->city = $billing['city'] ?? null;
            $customer->postal_code = $billing['postcode'] ?? null;
            
            // Set totals from WooCommerce
            $customer->total_spent = (float) ($wooCustomer['total_spent'] ?? 0);
            $customer->total_orders = (int) ($wooCustomer['orders_count'] ?? 0);
            
            $customer->synced_at = now();
            $customer->save();

            DB::commit();

            return [
                'success' => true,
                'created' => $isNew,
                'customer' => $customer
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Export customer to WooCommerce
     */
    public function exportCustomer(Customer $customer): array
    {
        try {
            $data = $this->mapCustomerToWooCommerce($customer);

            if ($customer->woocommerce_id) {
                // Update existing customer
                $response = $this->client->put("customers/{$customer->woocommerce_id}", $data);
            } else {
                // Create new customer
                $response = $this->client->post('customers', $data);
            }

            if ($response['success']) {
                $wooCustomer = $response['data'];
                
                $customer->update([
                    'woocommerce_id' => $wooCustomer['id'],
                    'synced_at' => now()
                ]);

                return [
                    'success' => true,
                    'customer' => $customer,
                    'woo_customer' => $wooCustomer
                ];
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to export customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Map local customer to WooCommerce format
     */
    protected function mapCustomerToWooCommerce(Customer $customer): array
    {
        $data = [
            'email' => $customer->email,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'username' => $customer->email ? explode('@', $customer->email)[0] : null,
        ];

        // Add billing information
        $data['billing'] = [
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address_1' => $customer->address,
            'city' => $customer->city,
            'postcode' => $customer->postal_code,
        ];

        // Add shipping information (same as billing by default)
        $data['shipping'] = $data['billing'];

        return $data;
    }

    /**
     * Sync customer purchase statistics
     */
    public function syncCustomerStats(Customer $customer): array
    {
        if (!$customer->woocommerce_id) {
            return [
                'success' => false,
                'message' => 'Customer not synced to WooCommerce'
            ];
        }

        try {
            $response = $this->client->get("customers/{$customer->woocommerce_id}");

            if ($response['success']) {
                $wooCustomer = $response['data'];
                
                $customer->update([
                    'total_spent' => (float) ($wooCustomer['total_spent'] ?? 0),
                    'total_orders' => (int) ($wooCustomer['orders_count'] ?? 0),
                    'synced_at' => now()
                ]);

                return [
                    'success' => true,
                    'customer' => $customer
                ];
            }

            return $response;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Log sync operation
     */
    protected function logSync(
        string $type,
        string $direction,
        array $stats,
        $startTime,
        string $status = 'success',
        ?string $errorMessage = null
    ): void {
        SyncLog::create([
            'type' => $type,
            'direction' => $direction,
            'status' => $status,
            'records_processed' => $stats['created'] + $stats['updated'],
            'records_failed' => $stats['failed'],
            'error_message' => $errorMessage,
            'started_at' => $startTime,
            'completed_at' => now(),
        ]);
    }
}