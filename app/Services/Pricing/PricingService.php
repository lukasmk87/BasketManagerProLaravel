<?php

namespace App\Services\Pricing;

use App\Services\Settings\SystemSettingsService;

class PricingService
{
    public function __construct(
        protected SystemSettingsService $settings
    ) {}

    /**
     * Calculate display price based on net price and current settings.
     *
     * @param  float  $netPrice  Net price from database
     * @param  float|null  $taxRate  Optional custom tax rate
     * @return array{
     *     display_price: float,
     *     net_price: float,
     *     gross_price: float,
     *     tax_amount: float,
     *     tax_rate: float,
     *     is_small_business: bool,
     *     price_label: string,
     *     small_business_notice: string|null
     * }
     */
    public function calculateDisplayPrice(float $netPrice, ?float $taxRate = null): array
    {
        $taxRate = $taxRate ?? $this->settings->getDefaultTaxRate();
        $isSmallBusiness = $this->settings->isSmallBusiness();
        $displayGross = $this->settings->displayPricesGross();

        // Small business: No VAT
        if ($isSmallBusiness) {
            return [
                'display_price' => $netPrice,
                'net_price' => $netPrice,
                'gross_price' => $netPrice, // For small business: gross = net
                'tax_amount' => 0.00,
                'tax_rate' => 0.00,
                'is_small_business' => true,
                'price_label' => '',
                'small_business_notice' => $this->getSmallBusinessNotice(),
            ];
        }

        $taxAmount = round($netPrice * ($taxRate / 100), 2);
        $grossPrice = round($netPrice + $taxAmount, 2);

        return [
            'display_price' => $displayGross ? $grossPrice : $netPrice,
            'net_price' => $netPrice,
            'gross_price' => $grossPrice,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'is_small_business' => false,
            'price_label' => $displayGross ? 'inkl. MwSt.' : 'zzgl. MwSt.',
            'small_business_notice' => null,
        ];
    }

    /**
     * Format a price for display.
     */
    public function formatPrice(float $amount, string $currency = 'EUR'): string
    {
        return number_format($amount, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Get the full display price string including label.
     */
    public function getDisplayPriceString(float $netPrice, string $currency = 'EUR'): string
    {
        $pricing = $this->calculateDisplayPrice($netPrice);
        $formatted = $this->formatPrice($pricing['display_price'], $currency);

        if ($pricing['price_label']) {
            $formatted .= ' ' . $pricing['price_label'];
        }

        return $formatted;
    }

    /**
     * Get the small business notice text.
     */
    public function getSmallBusinessNotice(): string
    {
        return 'Gemäß §19 UStG wird keine Umsatzsteuer berechnet.';
    }

    /**
     * Check if the operator is a small business.
     */
    public function isSmallBusiness(): bool
    {
        return $this->settings->isSmallBusiness();
    }

    /**
     * Check if prices should be displayed gross.
     */
    public function displayPricesGross(): bool
    {
        return $this->settings->displayPricesGross();
    }

    /**
     * Get the default tax rate.
     */
    public function getDefaultTaxRate(): float
    {
        return $this->settings->getDefaultTaxRate();
    }

    /**
     * Get pricing data for frontend (Inertia props).
     */
    public function getFrontendPricingData(): array
    {
        return [
            'display_gross' => $this->settings->displayPricesGross(),
            'is_small_business' => $this->settings->isSmallBusiness(),
            'default_tax_rate' => $this->settings->getDefaultTaxRate(),
            'small_business_notice' => $this->settings->isSmallBusiness() ? $this->getSmallBusinessNotice() : null,
        ];
    }

    /**
     * Calculate invoice amounts considering small business status.
     */
    public function calculateInvoiceAmounts(float $netAmount): array
    {
        $isSmallBusiness = $this->settings->isSmallBusiness();
        $taxRate = $isSmallBusiness ? 0.00 : $this->settings->getDefaultTaxRate();

        $taxAmount = round($netAmount * ($taxRate / 100), 2);
        $grossAmount = round($netAmount + $taxAmount, 2);

        return [
            'net_amount' => $netAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'gross_amount' => $grossAmount,
            'is_small_business' => $isSmallBusiness,
        ];
    }
}
