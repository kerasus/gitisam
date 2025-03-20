<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'amount',
        'due_date',
        'invoice_type_id',
        'status',
    ];

    /**
     * Relationship: An invoice belongs to an invoice type.
     */
    public function invoiceType()
    {
        return $this->belongsTo(InvoiceType::class);
    }

    /**
     * Relationship: An invoice can have many invoice distributions.
     */
    public function invoiceDistributions()
    {
        return $this->hasMany(InvoiceDistribution::class);
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
     * Scope to filter unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Scope to filter paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to filter pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter cancelled invoices.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to filter invoices by a specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter invoices by title.
     */
    public function scopeByTitle($query, $title)
    {
        return $query->where('title', 'like', '%' . $title . '%');
    }

    /**
     * Scope to filter invoices by amount range.
     */
    public function scopeByAmountRange($query, $min, $max)
    {
        return $query->whereBetween('amount', [$min, $max]);
    }

    /**
     * Scope to filter invoices with an amount greater than a value.
     */
    public function scopeAmountGreaterThan($query, $value)
    {
        return $query->where('amount', '>', $value);
    }

    /**
     * Scope to filter invoices with an amount less than a value.
     */
    public function scopeAmountLessThan($query, $value)
    {
        return $query->where('amount', '<', $value);
    }

    /**
     * Scope to filter invoices by due date range.
     */
    public function scopeByDueDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }
}
