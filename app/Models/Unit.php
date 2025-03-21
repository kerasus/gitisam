<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'unit_number', 'type', 'area', 'floor', 'number_of_rooms',
        'parking_spaces', 'resident_name', 'resident_phone', 'owner_name', 'owner_phone'
    ];

    // Relationship: A unit belongs to a building
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    // Relationship: A unit can have many images (polymorphic)
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // Relationship: A unit can have many transactions
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Relationship: A unit belongs to one UnitUser
    public function unitUser(): BelongsTo
    {
        return $this->belongsTo(UnitUser::class);
    }

    // Relationship: A unit can have many invoice distributions
    public function invoiceDistributions(): HasMany
    {
        return $this->hasMany(InvoiceDistribution::class);
    }
}
