<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'city', 'district', 'latitude', 'longitude'
    ];

    // Relationship: A building has many units
    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    // Relationship: A building can have many images (polymorphic)
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
