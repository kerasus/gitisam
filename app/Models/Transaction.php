<?php

namespace App\Models;

use App\Enums\TargetGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'building_id',
        'unit_id',
        'amount',
        'receipt_image',
        'paid_at',
        'redirected_at',
        'verified_at',
        'authority',
        'transactionID',
        'payment_method',
        'gateway_name',
        'transaction_status',
        'callback_status',
        'card_pan',
        'card_hash',
        'fee',
        'currency',
        'mobile',
        'transaction_type',
        'target_group',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'paid_at' => 'datetime',
        'redirected_at' => 'datetime',
        'verified_at' => 'datetime',
        'payment_method' => 'string',
        'transaction_status' => 'string',
        'transaction_type' => 'string',
        'callback_status' => 'string',
        'currency' => 'string',
        'deleted_at' => 'datetime',
        'description' => 'string',
        'target_group' => TargetGroup::class,
    ];

    protected $appends = ['target_group_label'];

    /**
     * Relationship: A transaction belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A transaction belongs to a unit.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function samanTransaction()
    {
        return $this->hasOne(SamanTransaction::class, 'transaction_id', 'id');
    }

    /**
     * Relationship: A transaction belongs to a building.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Relationship: A transaction can have many invoice distributions.
     */
    public function invoiceDistributions(): BelongsToMany
    {
        return $this->belongsToMany(InvoiceDistribution::class, 'transaction_invoice_distributions')
            ->withPivot('amount', 'paid_amount')
            ->withTimestamps();
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get the label for the payment method.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'bank_gateway_zarinpal' => 'پرداخت با درگاه زرین پال',
            'bank_gateway_saman' => 'پرداخت با درگاه بانک سامان',
            'mobile_banking' => 'انتقال وجه موبایل بانک',
            'atm' => 'انتقال وجه ATM',
            'cash' => 'انتقال وجه نقدی',
            'check' => 'انتقال وجه چک',
            default => 'نا مشخص',
        };
    }

    /**
     * Get the label for the transaction status.
     */
    public function getTransactionStatusLabelAttribute(): string
    {
        return match ($this->transaction_status) {
            'transferred_to_pay' => 'انتقال به پرداخت',
            'pending_verification' => 'در انتظار تأیید',
            'expired' => 'منقضی شده',
            'unsuccessful' => 'ناکام',
            'paid' => 'پرداخت شده',
            'unpaid' => 'پرداخت نشده',
            default => 'نا مشخص',
        };
    }

    public function getTargetGroupLabelAttribute(): string
    {
        return match ($this->target_group) {
            TargetGroup::Resident => 'ساکن',
            TargetGroup::Owner => 'مالک',
            default => 'نا مشخص',
        };
    }

    /**
     * Get the label for the callback status.
     */
    public function getCallbackStatusLabelAttribute(): string
    {
        return match ($this->callback_status) {
            'OK' => 'موفق',
            'NOK' => 'ناموفق',
            'pending' => 'در انتظار',
            default => 'نا مشخص',
        };
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('transactions.transaction_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('transactions.transaction_status', 'unpaid');
    }

    public function scopeOwnerGroup($query)
    {
        return $query->where('transactions.target_group', 'owner');
    }

    public function scopeResidentGroup($query)
    {
        return $query->where('transactions.target_group', 'resident');
    }

    public function scopeBuildingIncome($query)
    {
        return $query->where('transactions.transaction_type', 'building_income');
    }

    public function scopeUnitTransaction($query)
    {
        return $query->where('transactions.transaction_type', 'unit_transaction');
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('transactions.deleted_at');
    }

    /**
     * Calculate the unallocated amount of the transaction.
     */
    public function getUnallocatedAmount()
    {
        // Calculate the total paid_amount from all related invoice distributions
        $totalPaidAmountInvoiceDistributions = $this->invoiceDistributions()
            ->selectRaw('SUM(invoice_distributions.paid_amount) as total_paid') // Explicitly specify the table
            ->value('total_paid'); // Retrieve the aggregated value

        // Get the transaction amount
        $transactionAmount = $this->amount;

        // Calculate the unallocated amount
        $unallocatedAmount = $transactionAmount - $totalPaidAmountInvoiceDistributions;

        // Return the unallocated amount if it's greater than zero, otherwise return zero
        return max($unallocatedAmount, 0);
    }
}
