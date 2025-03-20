<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description'
    ];

    // Relationship: An invoice type has many invoices
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
