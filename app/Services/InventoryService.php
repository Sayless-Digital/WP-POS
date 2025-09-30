<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    /**
     * Adjust stock for a product or variant
     *
     * @param Product|ProductVariant $item
     * @param int $quantity Positive for increase, negative for decrease
     * @param string $type Type of movement (sale, purchase, adjustment, return, transfer)
     * @param array $options Additional options (reference_type, reference_id, notes, user_id)
     * @return Inventory
     * @throws \Exception
     */
    public function adjustStock($item, int $quantity, string $type, array $options = []): Inventory
    {
        DB::beginTransaction();

        try {
            // Get or create inventory record
            $inventory = $item->inventory;
            
            if (!$inventory) {
                $inventory = Inventory::create([
                    'inventoriable_type' => get_class($item),
                    'inventoriable_id' => $item->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => config('pos.low_stock_threshold', 10),
                ]);
            }

            // Update quantity
            $oldQuantity = $inventory->quantity;
            $inventory->increment('quantity', $quantity);

            // Record stock movement
            StockMovement::create([
                'inventoriable_type' => get_class($item),
                'inventoriable_id' => $item->id,
                'type' => $type,
                'quantity' => $quantity,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
                'notes' => $options['notes'] ?? null,
                'user_id' => $options['user_id'] ?? Auth::id(),
            ]);

            // Check for low stock alert
            if ($inventory->quantity <= $inventory->low_stock_threshold && $oldQuantity > $inventory->low_stock_threshold) {
                // Trigger low stock event/notification
                event(new \App\Events\LowStockAlert($item, $inventory));
            }

            DB::commit();

            return $inventory->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reserve stock for an order
     *
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @return bool
     * @throws \Exception
     */
    public function reserveStock($item, int $quantity): bool
    {
        DB::beginTransaction();

        try {
            $inventory = $item->inventory;

            if (!$inventory) {
                throw new \Exception("No inventory record found for item");
            }

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $quantity) {
                throw new \Exception("Insufficient stock available. Available: {$available}, Requested: {$quantity}");
            }

            $inventory->increment('reserved_quantity', $quantity);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Release reserved stock
     *
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @return bool
     */
    public function releaseReservedStock($item, int $quantity): bool
    {
        DB::beginTransaction();

        try {
            $inventory = $item->inventory;

            if (!$inventory) {
                throw new \Exception("No inventory record found for item");
            }

            $inventory->decrement('reserved_quantity', $quantity);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process sale - decrease stock and release reservation
     *
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @param array $options
     * @return Inventory
     * @throws \Exception
     */
    public function processSale($item, int $quantity, array $options = []): Inventory
    {
        DB::beginTransaction();

        try {
            $inventory = $item->inventory;

            if (!$inventory) {
                throw new \Exception("No inventory record found for item");
            }

            // Decrease actual quantity
            $inventory->decrement('quantity', $quantity);

            // Decrease reserved quantity if it was reserved
            if ($inventory->reserved_quantity >= $quantity) {
                $inventory->decrement('reserved_quantity', $quantity);
            }

            // Record stock movement
            StockMovement::create([
                'inventoriable_type' => get_class($item),
                'inventoriable_id' => $item->id,
                'type' => 'sale',
                'quantity' => -$quantity,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
                'notes' => $options['notes'] ?? null,
                'user_id' => $options['user_id'] ?? Auth::id(),
            ]);

            DB::commit();

            return $inventory->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process return - increase stock
     *
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @param array $options
     * @return Inventory
     */
    public function processReturn($item, int $quantity, array $options = []): Inventory
    {
        return $this->adjustStock($item, $quantity, 'return', $options);
    }

    /**
     * Get stock level for an item
     *
     * @param Product|ProductVariant $item
     * @return int
     */
    public function getStockLevel($item): int
    {
        $inventory = $item->inventory;
        
        if (!$inventory) {
            return 0;
        }

        return $inventory->quantity;
    }

    /**
     * Get available stock (quantity - reserved)
     *
     * @param Product|ProductVariant $item
     * @return int
     */
    public function getAvailableStock($item): int
    {
        $inventory = $item->inventory;
        
        if (!$inventory) {
            return 0;
        }

        return max(0, $inventory->quantity - $inventory->reserved_quantity);
    }

    /**
     * Check if item is in stock
     *
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @return bool
     */
    public function isInStock($item, int $quantity = 1): bool
    {
        if (!$item->track_inventory) {
            return true;
        }

        return $this->getAvailableStock($item) >= $quantity;
    }

    /**
     * Check if item is low on stock
     *
     * @param Product|ProductVariant $item
     * @return bool
     */
    public function isLowStock($item): bool
    {
        $inventory = $item->inventory;
        
        if (!$inventory) {
            return false;
        }

        return $inventory->quantity <= $inventory->low_stock_threshold;
    }

    /**
     * Get low stock items
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockItems()
    {
        return Inventory::whereRaw('quantity <= low_stock_threshold')
            ->with('inventoriable')
            ->get()
            ->map(function ($inventory) {
                return $inventory->inventoriable;
            })
            ->filter();
    }

    /**
     * Get out of stock items
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOutOfStockItems()
    {
        return Inventory::where('quantity', '<=', 0)
            ->with('inventoriable')
            ->get()
            ->map(function ($inventory) {
                return $inventory->inventoriable;
            })
            ->filter();
    }

    /**
     * Get stock movement history for an item
     *
     * @param Product|ProductVariant $item
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockHistory($item, int $limit = 50)
    {
        return StockMovement::where('inventoriable_type', get_class($item))
            ->where('inventoriable_id', $item->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Perform stock count/audit
     *
     * @param Product|ProductVariant $item
     * @param int $countedQuantity
     * @param string|null $notes
     * @return Inventory
     * @throws \Exception
     */
    public function performStockCount($item, int $countedQuantity, ?string $notes = null): Inventory
    {
        DB::beginTransaction();

        try {
            $inventory = $item->inventory;

            if (!$inventory) {
                throw new \Exception("No inventory record found for item");
            }

            $difference = $countedQuantity - $inventory->quantity;

            if ($difference !== 0) {
                // Adjust stock to match counted quantity
                $this->adjustStock($item, $difference, 'adjustment', [
                    'notes' => $notes ?? "Stock count adjustment. Counted: {$countedQuantity}, System: {$inventory->quantity}",
                ]);
            }

            // Update last counted timestamp
            $inventory->update([
                'last_counted_at' => now(),
            ]);

            DB::commit();

            return $inventory->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Bulk stock adjustment
     *
     * @param array $adjustments Array of ['item' => Product|ProductVariant, 'quantity' => int, 'notes' => string]
     * @param string $type
     * @return int Number of adjustments made
     * @throws \Exception
     */
    public function bulkAdjustStock(array $adjustments, string $type = 'adjustment'): int
    {
        DB::beginTransaction();

        try {
            $count = 0;

            foreach ($adjustments as $adjustment) {
                $this->adjustStock(
                    $adjustment['item'],
                    $adjustment['quantity'],
                    $type,
                    ['notes' => $adjustment['notes'] ?? null]
                );
                $count++;
            }

            DB::commit();

            return $count;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update low stock threshold
     *
     * @param Product|ProductVariant $item
     * @param int $threshold
     * @return Inventory
     */
    public function updateLowStockThreshold($item, int $threshold): Inventory
    {
        $inventory = $item->inventory;

        if (!$inventory) {
            $inventory = Inventory::create([
                'inventoriable_type' => get_class($item),
                'inventoriable_id' => $item->id,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'low_stock_threshold' => $threshold,
            ]);
        } else {
            $inventory->update([
                'low_stock_threshold' => $threshold,
            ]);
        }

        return $inventory;
    }

    /**
     * Get inventory value for an item
     *
     * @param Product|ProductVariant $item
     * @return float
     */
    public function getInventoryValue($item): float
    {
        $quantity = $this->getStockLevel($item);
        $costPrice = $item->cost_price ?? $item->price;

        return $quantity * $costPrice;
    }

    /**
     * Get total inventory value
     *
     * @return float
     */
    public function getTotalInventoryValue(): float
    {
        $total = 0;

        // Calculate for products
        $products = Product::with('inventory')->where('track_inventory', true)->get();
        foreach ($products as $product) {
            $total += $this->getInventoryValue($product);
        }

        // Calculate for variants
        $variants = ProductVariant::with('inventory')->get();
        foreach ($variants as $variant) {
            $total += $this->getInventoryValue($variant);
        }

        return $total;
    }

    /**
     * Get stock movement summary for a date range
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getStockMovementSummary(\DateTime $startDate, \DateTime $endDate): array
    {
        $movements = StockMovement::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('type, SUM(ABS(quantity)) as total_quantity, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        return $movements->mapWithKeys(function ($movement) {
            return [$movement->type => [
                'quantity' => $movement->total_quantity,
                'count' => $movement->count,
            ]];
        })->toArray();
    }
}