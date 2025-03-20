<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'unit_id', 'distribution_method', 'amount'
    ];

    /**
     * Relationship: An invoice distribution belongs to an invoice.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Relationship: An invoice distribution belongs to a unit.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relationship: An invoice distribution can belong to many transactions.
     */
    public function transactions()
    {
        return $this->belongsToMany(Transaction::class, 'transaction_invoice_distribution')
            ->withPivot('amount', 'paid_amount')
            ->withTimestamps();
    }

    /**
     * Get the label for the status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'unpaid' => 'Unpaid',
            'paid' => 'Paid',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    // Scopes
    /**
     * Scope to filter unpaid distributions.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Scope to filter paid distributions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to filter pending distributions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter cancelled distributions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to filter distributions by a specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter distributions by amount range.
     */
    public function scopeByAmountRange($query, $min, $max)
    {
        return $query->whereBetween('amount', [$min, $max]);
    }

    /**
     * Scope to filter distributions with an amount greater than a value.
     */
    public function scopeAmountGreaterThan($query, $value)
    {
        return $query->where('amount', '>', $value);
    }

    /**
     * Scope to filter distributions with an amount less than a value.
     */
    public function scopeAmountLessThan($query, $value)
    {
        return $query->where('amount', '<', $value);
    }

    /**
     * Scope to filter distributions by invoice ID.
     */
    public function scopeByInvoiceId($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope to filter distributions by unit ID.
     */
    public function scopeByUnitId($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }
}
