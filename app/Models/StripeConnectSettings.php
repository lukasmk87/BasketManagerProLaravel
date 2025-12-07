<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeConnectSettings extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stripe_connect_settings';

    protected $fillable = [
        'tenant_id',
        'application_fee_percent',
        'application_fee_fixed',
        'fee_currency',
        'payout_schedule',
        'payout_delay_days',
        'allow_direct_charges',
        'allow_destination_charges',
        'require_onboarding_complete',
    ];

    protected $casts = [
        'application_fee_percent' => 'decimal:2',
        'application_fee_fixed' => 'decimal:2',
        'payout_delay_days' => 'integer',
        'allow_direct_charges' => 'boolean',
        'allow_destination_charges' => 'boolean',
        'require_onboarding_complete' => 'boolean',
    ];

    /**
     * Get the tenant that owns these settings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get platform-wide default settings.
     */
    public static function getPlatformDefaults(): ?self
    {
        return static::whereNull('tenant_id')->first();
    }

    /**
     * Get settings for a tenant, falling back to platform defaults.
     */
    public static function getForTenant(?Tenant $tenant): ?self
    {
        if ($tenant) {
            $tenantSettings = static::where('tenant_id', $tenant->id)->first();
            if ($tenantSettings) {
                return $tenantSettings;
            }
        }

        return static::getPlatformDefaults();
    }

    /**
     * Calculate application fee for a given amount.
     */
    public function calculateFee(int $amountInCents): int
    {
        $percentFee = (int) round($amountInCents * ($this->application_fee_percent / 100));
        $fixedFee = (int) round($this->application_fee_fixed * 100);

        return $percentFee + $fixedFee;
    }
}
