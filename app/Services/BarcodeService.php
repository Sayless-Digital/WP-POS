<?php

namespace App\Services;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class BarcodeService
{
    /**
     * Generate a unique barcode
     *
     * @param string $type
     * @return string
     */
    public function generateBarcode(string $type = 'EAN13'): string
    {
        switch ($type) {
            case 'EAN13':
                return $this->generateEAN13();
            case 'EAN8':
                return $this->generateEAN8();
            case 'UPC':
                return $this->generateUPC();
            case 'CODE128':
                return $this->generateCODE128();
            default:
                return $this->generateEAN13();
        }
    }

    /**
     * Generate EAN-13 barcode
     *
     * @return string
     */
    protected function generateEAN13(): string
    {
        do {
            // Generate 12 random digits
            $code = '';
            for ($i = 0; $i < 12; $i++) {
                $code .= rand(0, 9);
            }

            // Calculate check digit
            $checkDigit = $this->calculateEAN13CheckDigit($code);
            $barcode = $code . $checkDigit;

        } while (Barcode::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Generate EAN-8 barcode
     *
     * @return string
     */
    protected function generateEAN8(): string
    {
        do {
            // Generate 7 random digits
            $code = '';
            for ($i = 0; $i < 7; $i++) {
                $code .= rand(0, 9);
            }

            // Calculate check digit
            $checkDigit = $this->calculateEAN8CheckDigit($code);
            $barcode = $code . $checkDigit;

        } while (Barcode::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Generate UPC barcode
     *
     * @return string
     */
    protected function generateUPC(): string
    {
        do {
            // Generate 11 random digits
            $code = '';
            for ($i = 0; $i < 11; $i++) {
                $code .= rand(0, 9);
            }

            // Calculate check digit
            $checkDigit = $this->calculateUPCCheckDigit($code);
            $barcode = $code . $checkDigit;

        } while (Barcode::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Generate CODE128 barcode
     *
     * @return string
     */
    protected function generateCODE128(): string
    {
        do {
            // Generate alphanumeric code
            $length = rand(8, 20);
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $barcode = '';
            
            for ($i = 0; $i < $length; $i++) {
                $barcode .= $characters[rand(0, strlen($characters) - 1)];
            }

        } while (Barcode::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Calculate EAN-13 check digit
     *
     * @param string $code
     * @return int
     */
    protected function calculateEAN13CheckDigit(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $multiplier = ($i % 2 === 0) ? 1 : 3;
            $sum += (int)$code[$i] * $multiplier;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }

    /**
     * Calculate EAN-8 check digit
     *
     * @param string $code
     * @return int
     */
    protected function calculateEAN8CheckDigit(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $multiplier = ($i % 2 === 0) ? 3 : 1;
            $sum += (int)$code[$i] * $multiplier;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }

    /**
     * Calculate UPC check digit
     *
     * @param string $code
     * @return int
     */
    protected function calculateUPCCheckDigit(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $multiplier = ($i % 2 === 0) ? 3 : 1;
            $sum += (int)$code[$i] * $multiplier;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }

    /**
     * Validate barcode format
     *
     * @param string $barcode
     * @param string $type
     * @return bool
     */
    public function validateBarcode(string $barcode, string $type = 'EAN13'): bool
    {
        switch ($type) {
            case 'EAN13':
                return $this->validateEAN13($barcode);
            case 'EAN8':
                return $this->validateEAN8($barcode);
            case 'UPC':
                return $this->validateUPC($barcode);
            case 'CODE128':
                return $this->validateCODE128($barcode);
            default:
                return false;
        }
    }

    /**
     * Validate EAN-13 barcode
     *
     * @param string $barcode
     * @return bool
     */
    protected function validateEAN13(string $barcode): bool
    {
        if (strlen($barcode) !== 13 || !ctype_digit($barcode)) {
            return false;
        }

        $code = substr($barcode, 0, 12);
        $checkDigit = (int)substr($barcode, 12, 1);
        
        return $this->calculateEAN13CheckDigit($code) === $checkDigit;
    }

    /**
     * Validate EAN-8 barcode
     *
     * @param string $barcode
     * @return bool
     */
    protected function validateEAN8(string $barcode): bool
    {
        if (strlen($barcode) !== 8 || !ctype_digit($barcode)) {
            return false;
        }

        $code = substr($barcode, 0, 7);
        $checkDigit = (int)substr($barcode, 7, 1);
        
        return $this->calculateEAN8CheckDigit($code) === $checkDigit;
    }

    /**
     * Validate UPC barcode
     *
     * @param string $barcode
     * @return bool
     */
    protected function validateUPC(string $barcode): bool
    {
        if (strlen($barcode) !== 12 || !ctype_digit($barcode)) {
            return false;
        }

        $code = substr($barcode, 0, 11);
        $checkDigit = (int)substr($barcode, 11, 1);
        
        return $this->calculateUPCCheckDigit($code) === $checkDigit;
    }

    /**
     * Validate CODE128 barcode
     *
     * @param string $barcode
     * @return bool
     */
    protected function validateCODE128(string $barcode): bool
    {
        // CODE128 can contain alphanumeric characters
        return strlen($barcode) >= 1 && strlen($barcode) <= 128;
    }

    /**
     * Create barcode for a product or variant
     *
     * @param Product|ProductVariant $item
     * @param string|null $barcode
     * @param string $type
     * @return Barcode
     */
    public function createBarcode($item, ?string $barcode = null, string $type = 'EAN13'): Barcode
    {
        // Generate barcode if not provided
        if (empty($barcode)) {
            $barcode = $this->generateBarcode($type);
        }

        // Validate barcode
        if (!$this->validateBarcode($barcode, $type)) {
            throw new \InvalidArgumentException("Invalid {$type} barcode format");
        }

        // Check if barcode already exists
        if (Barcode::where('barcode', $barcode)->exists()) {
            throw new \InvalidArgumentException("Barcode already exists");
        }

        return Barcode::create([
            'barcodeable_type' => get_class($item),
            'barcodeable_id' => $item->id,
            'barcode' => $barcode,
            'type' => $type,
        ]);
    }

    /**
     * Find item by barcode
     *
     * @param string $barcode
     * @return Product|ProductVariant|null
     */
    public function findByBarcode(string $barcode)
    {
        $barcodeRecord = Barcode::where('barcode', $barcode)->first();

        if (!$barcodeRecord) {
            return null;
        }

        return $barcodeRecord->barcodeable()->with(['inventory', 'category'])->first();
    }

    /**
     * Update barcode for an item
     *
     * @param Barcode $barcodeRecord
     * @param string $newBarcode
     * @param string|null $type
     * @return Barcode
     */
    public function updateBarcode(Barcode $barcodeRecord, string $newBarcode, ?string $type = null): Barcode
    {
        $type = $type ?? $barcodeRecord->type;

        // Validate new barcode
        if (!$this->validateBarcode($newBarcode, $type)) {
            throw new \InvalidArgumentException("Invalid {$type} barcode format");
        }

        // Check if new barcode already exists
        if (Barcode::where('barcode', $newBarcode)->where('id', '!=', $barcodeRecord->id)->exists()) {
            throw new \InvalidArgumentException("Barcode already exists");
        }

        $barcodeRecord->update([
            'barcode' => $newBarcode,
            'type' => $type,
        ]);

        return $barcodeRecord;
    }

    /**
     * Delete barcode
     *
     * @param Barcode $barcode
     * @return bool
     */
    public function deleteBarcode(Barcode $barcode): bool
    {
        return $barcode->delete();
    }

    /**
     * Get all barcodes for an item
     *
     * @param Product|ProductVariant $item
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBarcodesForItem($item)
    {
        return Barcode::where('barcodeable_type', get_class($item))
            ->where('barcodeable_id', $item->id)
            ->get();
    }

    /**
     * Bulk generate barcodes for products without barcodes
     *
     * @param string $type
     * @return int Number of barcodes generated
     */
    public function bulkGenerateBarcodes(string $type = 'EAN13'): int
    {
        $count = 0;

        DB::transaction(function () use ($type, &$count) {
            // Generate for products without barcodes
            $products = Product::whereDoesntHave('barcodes')->get();
            
            foreach ($products as $product) {
                $this->createBarcode($product, null, $type);
                $count++;
            }

            // Generate for variants without barcodes
            $variants = ProductVariant::whereDoesntHave('barcodes')->get();
            
            foreach ($variants as $variant) {
                $this->createBarcode($variant, null, $type);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Check if barcode exists
     *
     * @param string $barcode
     * @return bool
     */
    public function barcodeExists(string $barcode): bool
    {
        return Barcode::where('barcode', $barcode)->exists();
    }
}