<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Relationship: A transaction belongs to a unit.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relationship: A transaction belongs to an invoice distribution.
     */
    public function invoiceDistribution()
    {
        return $this->belongsTo(InvoiceDistribution::class);
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
}
