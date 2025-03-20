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

    // Relationship: An invoice distribution belongs to an invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Relationship: An invoice distribution belongs to a unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relationship: An invoice distribution has many transactions.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
