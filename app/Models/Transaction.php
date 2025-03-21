<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'unit_id',
        'invoice_distribution_id',
        'amount',
        'payment_method',
        'receipt_image',
        'authority',
        'transactionID',
        'transaction_status'
    ];

    /**
     * Relationship: An invoice belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A transaction can have many invoice distributions.
     */
    public function invoiceDistributions(): BelongsToMany
    {
        return $this->belongsToMany(InvoiceDistribution::class, 'transaction_invoice_distribution')
            ->withPivot('amount', 'paid_amount')
            ->withTimestamps();
    }

    /**
     * Get the label for the payment method.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'online' => 'Online Payment',
            'atm' => 'ATM',
            'pos' => 'POS',
            'paycheck' => 'Paycheck',
            'wallet' => 'Wallet',
            default => 'Unknown',
        };
    }

    /**
     * Get the label for the transaction status.
     */
    public function getTransactionStatusLabelAttribute(): string
    {
        return match ($this->transaction_status) {
            'transferred_to_pay' => 'Transferred to Pay',
            'unsuccessful' => 'Unsuccessful',
            'successful' => 'Successful',
            'pending' => 'Pending',
            'archived_successful' => 'Archived Successful',
            'unpaid' => 'Unpaid',
            'suspended' => 'Suspended',
            'organizational_unpaid' => 'Organizational Unpaid',
            default => 'Unknown',
        };
    }

    // Scopes
    /**
     * Scope to filter transactions by a specific payment method.
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope to filter transactions by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('transaction_status', $status);
    }

    /**
     * Scope to filter successful transactions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('transaction_status', 'successful');
    }

    /**
     * Scope to filter pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('transaction_status', 'pending');
    }

    /**
     * Scope to filter unpaid transactions.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('transaction_status', 'unpaid');
    }

    /**
     * Scope to filter suspended transactions.
     */
    public function scopeSuspended($query)
    {
        return $query->where('transaction_status', 'suspended');
    }

    /**
     * Scope to filter transactions with a specific amount range.
     */
    public function scopeByAmountRange($query, $min, $max)
    {
        return $query->whereBetween('amount', [$min, $max]);
    }

    /**
     * Scope to filter transactions with an amount greater than a value.
     */
    public function scopeAmountGreaterThan($query, $value)
    {
        return $query->where('amount', '>', $value);
    }

    /**
     * Scope to filter transactions with an amount less than a value.
     */
    public function scopeAmountLessThan($query, $value)
    {
        return $query->where('amount', '<', $value);
    }

    /**
     * Scope to filter transactions by a specific user ID.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter transactions with a receipt image.
     */
    public function scopeWithReceiptImage($query)
    {
        return $query->whereNotNull('receipt_image');
    }

    /**
     * Scope to filter transactions without a receipt image.
     */
    public function scopeWithoutReceiptImage($query)
    {
        return $query->whereNull('receipt_image');
    }

    /**
     * Scope to filter transactions by authority code.
     */
    public function scopeByAuthority($query, $authority)
    {
        return $query->where('authority', $authority);
    }

    /**
     * Scope to filter transactions by transaction ID.
     */
    public function scopeByTransactionID($query, $transactionID)
    {
        return $query->where('transactionID', $transactionID);
    }
}
