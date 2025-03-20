<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'unit_id', 'title', 'description', 'amount', 'due_date',
        'invoice_type_id', 'status'
    ];

    // Relationship: An invoice belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: An invoice belongs to a unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Relationship: An invoice belongs to an invoice type
    public function invoiceType()
    {
        return $this->belongsTo(InvoiceType::class);
    }

    // Relationship: An invoice can have many invoice distributions
    public function invoiceDistributions()
    {
        return $this->hasMany(InvoiceDistribution::class);
    }
}
