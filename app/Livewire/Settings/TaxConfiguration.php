<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class TaxConfiguration extends Component
{
    public $taxRates = [];
    public $newTaxName;
    public $newTaxRate;
    public $newTaxType = 'percentage';
    public $defaultTaxId;
    public $enableTax = true;
    public $taxInclusive = false;
    public $showTaxOnReceipt = true;
    public $compoundTax = false;

    protected $rules = [
        'taxRates.*.name' => 'required|string|max:100',
        'taxRates.*.rate' => 'required|numeric|min:0|max:100',
        'taxRates.*.type' => 'required|in:percentage,fixed',
        'taxRates.*.active' => 'boolean',
        'newTaxName' => 'nullable|string|max:100',
        'newTaxRate' => 'nullable|numeric|min:0|max:100',
        'newTaxType' => 'required|in:percentage,fixed',
        'enableTax' => 'boolean',
        'taxInclusive' => 'boolean',
        'showTaxOnReceipt' => 'boolean',
        'compoundTax' => 'boolean',
    ];

    public function mount()
    {
        $this->loadTaxConfiguration();
    }

    public function loadTaxConfiguration()
    {
        $config = $this->getTaxConfiguration();
        
        $this->taxRates = $config['tax_rates'] ?? $this->getDefaultTaxRates();
        $this->defaultTaxId = $config['default_tax_id'] ?? null;
        $this->enableTax = $config['enable_tax'] ?? true;
        $this->taxInclusive = $config['tax_inclusive'] ?? false;
        $this->showTaxOnReceipt = $config['show_tax_on_receipt'] ?? true;
        $this->compoundTax = $config['compound_tax'] ?? false;
    }

    private function getDefaultTaxRates()
    {
        return [
            [
                'id' => uniqid(),
                'name' => 'Standard VAT',
                'rate' => 12.5,
                'type' => 'percentage',
                'active' => true,
            ],
            [
                'id' => uniqid(),
                'name' => 'Zero Rate',
                'rate' => 0,
                'type' => 'percentage',
                'active' => true,
            ],
        ];
    }

    public function addTaxRate()
    {
        $this->validate([
            'newTaxName' => 'required|string|max:100',
            'newTaxRate' => 'required|numeric|min:0|max:100',
        ]);

        $this->taxRates[] = [
            'id' => uniqid(),
            'name' => $this->newTaxName,
            'rate' => $this->newTaxRate,
            'type' => $this->newTaxType,
            'active' => true,
        ];

        $this->reset(['newTaxName', 'newTaxRate', 'newTaxType']);
        session()->flash('message', 'Tax rate added successfully.');
    }

    public function removeTaxRate($index)
    {
        unset($this->taxRates[$index]);
        $this->taxRates = array_values($this->taxRates);
        session()->flash('message', 'Tax rate removed successfully.');
    }

    public function toggleTaxRate($index)
    {
        $this->taxRates[$index]['active'] = !$this->taxRates[$index]['active'];
    }

    public function setDefaultTax($taxId)
    {
        $this->defaultTaxId = $taxId;
        session()->flash('message', 'Default tax rate updated.');
    }

    public function save()
    {
        $this->validate();

        $config = [
            'tax_rates' => $this->taxRates,
            'default_tax_id' => $this->defaultTaxId,
            'enable_tax' => $this->enableTax,
            'tax_inclusive' => $this->taxInclusive,
            'show_tax_on_receipt' => $this->showTaxOnReceipt,
            'compound_tax' => $this->compoundTax,
        ];

        $this->saveTaxConfiguration($config);
        session()->flash('message', 'Tax configuration saved successfully.');
    }

    public function resetToDefaults()
    {
        Cache::forget('tax_configuration');
        $this->loadTaxConfiguration();
        session()->flash('message', 'Tax configuration reset to defaults.');
    }

    private function getTaxConfiguration()
    {
        return Cache::remember('tax_configuration', 3600, function () {
            $configFile = storage_path('app/tax_config.json');
            
            if (file_exists($configFile)) {
                return json_decode(file_get_contents($configFile), true);
            }
            
            return [];
        });
    }

    private function saveTaxConfiguration(array $config)
    {
        $configFile = storage_path('app/tax_config.json');
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        Cache::forget('tax_configuration');
    }

    public function calculateTax($amount, $taxId = null)
    {
        if (!$this->enableTax) {
            return 0;
        }

        $taxId = $taxId ?? $this->defaultTaxId;
        $taxRate = collect($this->taxRates)->firstWhere('id', $taxId);

        if (!$taxRate || !$taxRate['active']) {
            return 0;
        }

        if ($taxRate['type'] === 'percentage') {
            return $amount * ($taxRate['rate'] / 100);
        }

        return $taxRate['rate'];
    }

    public function render()
    {
        return view('livewire.settings.tax-configuration');
    }
}