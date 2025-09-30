<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GeneralSettings extends Component
{
    public $storeName;
    public $storeEmail;
    public $storePhone;
    public $storeAddress;
    public $currency;
    public $taxRate;
    public $receiptHeader;
    public $receiptFooter;
    public $lowStockThreshold;
    public $autoSyncInterval;
    public $enableOfflineMode;
    public $enableBarcodeScanner;
    public $enableCustomerDisplay;
    public $enableReceiptPrinting;
    public $defaultPaymentMethod;
    public $requireCustomerForSale;
    public $allowNegativeStock;
    public $roundingPrecision;
    
    protected $rules = [
        'storeName' => 'required|string|max:255',
        'storeEmail' => 'required|email|max:255',
        'storePhone' => 'nullable|string|max:20',
        'storeAddress' => 'nullable|string|max:500',
        'currency' => 'required|string|max:3',
        'taxRate' => 'required|numeric|min:0|max:100',
        'receiptHeader' => 'nullable|string|max:500',
        'receiptFooter' => 'nullable|string|max:500',
        'lowStockThreshold' => 'required|integer|min:0',
        'autoSyncInterval' => 'required|integer|min:5|max:1440',
        'enableOfflineMode' => 'boolean',
        'enableBarcodeScanner' => 'boolean',
        'enableCustomerDisplay' => 'boolean',
        'enableReceiptPrinting' => 'boolean',
        'defaultPaymentMethod' => 'required|string|in:cash,card,mobile',
        'requireCustomerForSale' => 'boolean',
        'allowNegativeStock' => 'boolean',
        'roundingPrecision' => 'required|integer|min:0|max:4',
    ];

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $settings = $this->getSettings();
        
        $this->storeName = $settings['store_name'] ?? config('app.name');
        $this->storeEmail = $settings['store_email'] ?? '';
        $this->storePhone = $settings['store_phone'] ?? '';
        $this->storeAddress = $settings['store_address'] ?? '';
        $this->currency = $settings['currency'] ?? 'USD';
        $this->taxRate = $settings['tax_rate'] ?? 0;
        $this->receiptHeader = $settings['receipt_header'] ?? '';
        $this->receiptFooter = $settings['receipt_footer'] ?? '';
        $this->lowStockThreshold = $settings['low_stock_threshold'] ?? 10;
        $this->autoSyncInterval = $settings['auto_sync_interval'] ?? 30;
        $this->enableOfflineMode = $settings['enable_offline_mode'] ?? true;
        $this->enableBarcodeScanner = $settings['enable_barcode_scanner'] ?? true;
        $this->enableCustomerDisplay = $settings['enable_customer_display'] ?? false;
        $this->enableReceiptPrinting = $settings['enable_receipt_printing'] ?? true;
        $this->defaultPaymentMethod = $settings['default_payment_method'] ?? 'cash';
        $this->requireCustomerForSale = $settings['require_customer_for_sale'] ?? false;
        $this->allowNegativeStock = $settings['allow_negative_stock'] ?? false;
        $this->roundingPrecision = $settings['rounding_precision'] ?? 2;
    }

    public function save()
    {
        $this->validate();

        $settings = [
            'store_name' => $this->storeName,
            'store_email' => $this->storeEmail,
            'store_phone' => $this->storePhone,
            'store_address' => $this->storeAddress,
            'currency' => $this->currency,
            'tax_rate' => $this->taxRate,
            'receipt_header' => $this->receiptHeader,
            'receipt_footer' => $this->receiptFooter,
            'low_stock_threshold' => $this->lowStockThreshold,
            'auto_sync_interval' => $this->autoSyncInterval,
            'enable_offline_mode' => $this->enableOfflineMode,
            'enable_barcode_scanner' => $this->enableBarcodeScanner,
            'enable_customer_display' => $this->enableCustomerDisplay,
            'enable_receipt_printing' => $this->enableReceiptPrinting,
            'default_payment_method' => $this->defaultPaymentMethod,
            'require_customer_for_sale' => $this->requireCustomerForSale,
            'allow_negative_stock' => $this->allowNegativeStock,
            'rounding_precision' => $this->roundingPrecision,
        ];

        $this->saveSettings($settings);

        session()->flash('message', 'Settings saved successfully.');
    }

    public function resetToDefaults()
    {
        Cache::forget('pos_settings');
        $this->loadSettings();
        session()->flash('message', 'Settings reset to defaults.');
    }

    private function getSettings()
    {
        return Cache::remember('pos_settings', 3600, function () {
            $settingsFile = storage_path('app/settings.json');
            
            if (file_exists($settingsFile)) {
                return json_decode(file_get_contents($settingsFile), true);
            }
            
            return [];
        });
    }

    private function saveSettings(array $settings)
    {
        $settingsFile = storage_path('app/settings.json');
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        Cache::forget('pos_settings');
    }

    public function render()
    {
        return view('livewire.settings.general-settings');
    }
}