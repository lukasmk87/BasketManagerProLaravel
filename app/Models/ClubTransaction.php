<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClubTransaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Transaction types.
     */
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    /**
     * Transaction categories.
     */
    public const CATEGORY_MEMBERSHIP_FEE = 'membership_fee';
    public const CATEGORY_EQUIPMENT = 'equipment';
    public const CATEGORY_FACILITY = 'facility';
    public const CATEGORY_EVENT = 'event';
    public const CATEGORY_SPONSOR = 'sponsor';
    public const CATEGORY_TRAVEL = 'travel';
    public const CATEGORY_SALARY = 'salary';
    public const CATEGORY_INSURANCE = 'insurance';
    public const CATEGORY_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'club_id',
        'type',
        'category',
        'amount',
        'currency',
        'description',
        'transaction_date',
        'reference_number',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /**
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the club that owns the transaction.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the user who created the transaction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include income transactions.
     */
    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_MEMBERSHIP_FEE => 'Mitgliedsbeiträge',
            self::CATEGORY_EQUIPMENT => 'Ausrüstung',
            self::CATEGORY_FACILITY => 'Hallenmiete',
            self::CATEGORY_EVENT => 'Veranstaltungen',
            self::CATEGORY_SPONSOR => 'Sponsoring',
            self::CATEGORY_TRAVEL => 'Reisekosten',
            self::CATEGORY_SALARY => 'Gehälter/Honorare',
            self::CATEGORY_INSURANCE => 'Versicherungen',
            self::CATEGORY_OTHER => 'Sonstiges',
        ];
    }

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    /**
     * Check if transaction is income.
     */
    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }

    /**
     * Get signed amount (positive for income, negative for expense).
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->isIncome() ? $this->amount : -$this->amount;
    }
}
