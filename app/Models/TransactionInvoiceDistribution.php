<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionInvoiceDistribution extends Model
{
    use HasFactory;

    // Define fillable fields if needed
    protected $fillable = [
        'amount',
        'paid_amount',
        'transaction_id',
        'invoice_distribution_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'deleted_at' => 'datetime',
    ];

    // Define relationships if needed
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function invoiceDistribution()
    {
        return $this->belongsTo(InvoiceDistribution::class);
    }

    /**
     * Scope to filter transactions that are not soft-deleted.
     */
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }
}
