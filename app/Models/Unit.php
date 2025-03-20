<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'unit_number', 'type', 'area', 'floor', 'number_of_rooms',
        'parking_spaces', 'resident_name', 'resident_phone', 'owner_name', 'owner_phone'
    ];

    // Relationship: A unit belongs to a building
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    // Relationship: A unit can have many images (polymorphic)
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // Relationship: A unit can have many transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Relationship: A unit can belong to many users (many-to-many)
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    // Relationship: A unit can have many invoice distributions
    public function invoiceDistributions()
    {
        return $this->hasMany(InvoiceDistribution::class);
    }
}
