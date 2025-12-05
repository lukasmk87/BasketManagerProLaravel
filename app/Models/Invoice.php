<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Invoice Model
 *
 * Polymorphes Rechnungsmodell für Clubs und Tenants.
 * Unterstützt Bank-Überweisung und Stripe-Zahlungen.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $invoiceable_type
 * @property string $invoiceable_id
 * @property string|null $subscription_plan_type
 * @property string|null $subscription_plan_id
 * @property string $payment_method
 * @property string|null $stripe_invoice_id
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_hosted_invoice_url
 * @property string|null $stripe_invoice_pdf
 * @property string $invoice_number
 * @property string $status
 * @property float $net_amount
 * @property float $tax_rate
 * @property float $tax_amount
 * @property float $gross_amount
 * @property bool $is_small_business
 * @property string $currency
 * @property string|null $billing_period
 * @property string|null $description
 * @property array|null $line_items
 * @property string $billing_name
 * @property string $billing_email
 * @property array|null $billing_address
 * @property string|null $vat_number
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property Carbon|null $paid_at
 * @property string|null $payment_reference
 * @property string|null $payment_notes
 * @property int $reminder_count
 * @property Carbon|null $last_reminder_sent_at
 * @property string|null $pdf_path
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read Model $invoiceable
 * @property-read Model|null $subscriptionPlan
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $table = 'invoices';

    protected $fillable = [
        'tenant_id',
        'invoiceable_type',
        'invoiceable_id',
        'subscription_plan_type',
        'subscription_plan_id',
        'payment_method',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'stripe_hosted_invoice_url',
        'stripe_invoice_pdf',
        'invoice_number',
        'status',
        'net_amount',
        'tax_rate',
        'tax_amount',
        'gross_amount',
        'is_small_business',
        'currency',
        'billing_period',
        'description',
        'line_items',
        'billing_name',
        'billing_email',
        'billing_address',
        'vat_number',
        'issue_date',
        'due_date',
        'paid_at',
        'payment_reference',
        'payment_notes',
        'reminder_count',
        'last_reminder_sent_at',
        'pdf_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'net_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'is_small_business' => 'boolean',
        'line_items' => 'array',
        'billing_address' => 'array',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'last_reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    // Payment method constants
    public const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    public const PAYMENT_METHOD_STRIPE = 'stripe';

    // Invoiceable type constants
    public const TYPE_CLUB = 'App\\Models\\Club';
    public const TYPE_TENANT = 'App\\Models\\Tenant';

    /**
     * Get the tenant that owns this invoice.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the invoiceable entity (Club or Tenant).
     */
    public function invoiceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the subscription plan (polymorph).
     */
    public function subscriptionPlan(): MorphTo
    {
        return $this->morphTo('subscription_plan', 'subscription_plan_type', 'subscription_plan_id');
    }

    /**
     * Get the user who created this invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this invoice.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==================== Scopes ====================

    /**
     * Scope: Filter to draft invoices.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Filter to sent invoices.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope: Filter to paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope: Filter to overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    /**
     * Scope: Filter to cancelled invoices.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope: Filter to pending invoices (sent or overdue - awaiting payment).
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    /**
     * Scope: Filter to invoices that are due (payment expected).
     */
    public function scopeDueForPayment($query)
    {
        return $query->where('status', self::STATUS_SENT)
            ->where('due_date', '<=', now());
    }

    /**
     * Scope: Filter invoices by date range.
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter invoices from a specific year.
     */
    public function scopeFromYear($query, int $year)
    {
        return $query->whereYear('issue_date', $year);
    }

    /**
     * Scope: Filter by invoiceable type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('invoiceable_type', $type);
    }

    /**
     * Scope: Filter to club invoices.
     */
    public function scopeForClubs($query)
    {
        return $query->forType(self::TYPE_CLUB);
    }

    /**
     * Scope: Filter to tenant invoices.
     */
    public function scopeForTenants($query)
    {
        return $query->forType(self::TYPE_TENANT);
    }

    /**
     * Scope: Filter by payment method.
     */
    public function scopePaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope: Filter to bank transfer invoices.
     */
    public function scopeBankTransfer($query)
    {
        return $query->paymentMethod(self::PAYMENT_METHOD_BANK_TRANSFER);
    }

    /**
     * Scope: Filter to Stripe invoices.
     */
    public function scopeStripe($query)
    {
        return $query->paymentMethod(self::PAYMENT_METHOD_STRIPE);
    }

    // ==================== Helpers ====================

    /**
     * Check if this is a club invoice.
     */
    public function isClubInvoice(): bool
    {
        return $this->invoiceable_type === self::TYPE_CLUB;
    }

    /**
     * Check if this is a tenant invoice.
     */
    public function isTenantInvoice(): bool
    {
        return $this->invoiceable_type === self::TYPE_TENANT;
    }

    /**
     * Check if this is a bank transfer invoice.
     */
    public function isBankTransfer(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_BANK_TRANSFER;
    }

    /**
     * Check if this is a Stripe invoice.
     */
    public function isStripeInvoice(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_STRIPE;
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Get the number of days until the invoice is due (negative if overdue).
     */
    public function daysUntilDue(): int
    {
        return now()->startOfDay()->diffInDays($this->due_date, false);
    }

    /**
     * Get the number of days the invoice is overdue.
     */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    /**
     * Check if the invoice can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the invoice can be sent.
     */
    public function canBeSent(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the invoice can be marked as paid.
     */
    public function canBeMarkedAsPaid(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    /**
     * Check if a reminder can be sent.
     */
    public function canSendReminder(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    /**
     * Check if the invoice can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    /**
     * Get formatted amounts.
     */
    public function getFormattedAmountsAttribute(): array
    {
        $formatter = new \NumberFormatter('de_DE', \NumberFormatter::CURRENCY);

        return [
            'net' => $formatter->formatCurrency((float) $this->net_amount, $this->currency),
            'tax' => $formatter->formatCurrency((float) $this->tax_amount, $this->currency),
            'gross' => $formatter->formatCurrency((float) $this->gross_amount, $this->currency),
        ];
    }

    /**
     * Get the status label in German.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Entwurf',
            self::STATUS_SENT => 'Versendet',
            self::STATUS_PAID => 'Bezahlt',
            self::STATUS_OVERDUE => 'Überfällig',
            self::STATUS_CANCELLED => 'Storniert',
            default => $this->status,
        };
    }

    /**
     * Get the status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SENT => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get the invoiceable type label.
     */
    public function getInvoiceableTypeLabelAttribute(): string
    {
        return match ($this->invoiceable_type) {
            self::TYPE_CLUB => 'Club',
            self::TYPE_TENANT => 'Tenant',
            default => 'Unbekannt',
        };
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_METHOD_BANK_TRANSFER => 'Banküberweisung',
            self::PAYMENT_METHOD_STRIPE => 'Stripe',
            default => $this->payment_method,
        };
    }

    /**
     * Generate a unique invoice number for a tenant.
     */
    public static function generateNumber(string $tenantId, ?string $type = null): string
    {
        $prefix = config('invoices.number_prefix', 'INV');

        // Optional: Type-specific prefix
        if ($type === self::TYPE_TENANT) {
            $prefix = config('invoices.types.tenant.number_prefix', 'TEN');
        } elseif ($type === self::TYPE_CLUB) {
            $prefix = config('invoices.types.club.number_prefix', 'CLUB');
        }

        $year = now()->year;

        // Get the next sequence number for this tenant and year
        $lastInvoice = static::where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%d-%05d', $prefix, $year, $sequence);
    }

    /**
     * Calculate amounts from net amount and tax rate.
     */
    public static function calculateAmounts(float $netAmount, float $taxRate = 19.00, bool $isSmallBusiness = false): array
    {
        if ($isSmallBusiness) {
            $taxRate = 0.00;
        }

        $taxAmount = round($netAmount * ($taxRate / 100), 2);
        $grossAmount = round($netAmount + $taxAmount, 2);

        return [
            'net_amount' => $netAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'gross_amount' => $grossAmount,
        ];
    }

    /**
     * Get the default payment reference for bank transfers.
     */
    public function getPaymentReferenceAttribute($value): string
    {
        return $value ?? $this->invoice_number;
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Entwurf',
            self::STATUS_SENT => 'Versendet',
            self::STATUS_PAID => 'Bezahlt',
            self::STATUS_OVERDUE => 'Überfällig',
            self::STATUS_CANCELLED => 'Storniert',
        ];
    }

    /**
     * Get all available payment methods.
     */
    public static function getPaymentMethods(): array
    {
        return [
            self::PAYMENT_METHOD_BANK_TRANSFER => 'Banküberweisung',
            self::PAYMENT_METHOD_STRIPE => 'Stripe',
        ];
    }

    /**
     * Get all available invoiceable types.
     */
    public static function getInvoiceableTypes(): array
    {
        return [
            self::TYPE_CLUB => 'Club',
            self::TYPE_TENANT => 'Tenant',
        ];
    }
}
