<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Barcode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'barcodeable_type',
        'barcodeable_id',
        'barcode',
        'type',
    ];

    /**
     * Get the parent barcodeable model (Product or ProductVariant)
     */
    public function barcodeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if barcode is valid format
     */
    public function isValidFormat(): bool
    {
        return match($this->type) {
            'EAN13' => $this->isValidEAN13(),
            'EAN8' => $this->isValidEAN8(),
            'UPC' => $this->isValidUPC(),
            'CODE128' => $this->isValidCODE128(),
            default => true,
        };
    }

    /**
     * Validate EAN-13 barcode
     */
    protected function isValidEAN13(): bool
    {
        if (strlen($this->barcode) !== 13 || !ctype_digit($this->barcode)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$this->barcode[$i] * (($i % 2 === 0) ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit === (int)$this->barcode[12];
    }

    /**
     * Validate EAN-8 barcode
     */
    protected function isValidEAN8(): bool
    {
        if (strlen($this->barcode) !== 8 || !ctype_digit($this->barcode)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$this->barcode[$i] * (($i % 2 === 0) ? 3 : 1);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit === (int)$this->barcode[7];
    }

    /**
     * Validate UPC barcode
     */
    protected function isValidUPC(): bool
    {
        if (strlen($this->barcode) !== 12 || !ctype_digit($this->barcode)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += (int)$this->barcode[$i] * (($i % 2 === 0) ? 3 : 1);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit === (int)$this->barcode[11];
    }

    /**
     * Validate CODE128 barcode
     */
    protected function isValidCODE128(): bool
    {
        // CODE128 can contain alphanumeric characters
        return strlen($this->barcode) > 0 && strlen($this->barcode) <= 128;
    }

    /**
     * Scope to find by barcode value
     */
    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * Scope to get barcodes for products
     */
    public function scopeForProducts($query)
    {
        return $query->where('barcodeable_type', Product::class);
    }

    /**
     * Scope to get barcodes for variants
     */
    public function scopeForVariants($query)
    {
        return $query->where('barcodeable_type', ProductVariant::class);
    }

    /**
     * Scope to get by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}