<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitUser extends Model
{
    use HasFactory;

    protected $table = 'unit_user';

    protected $fillable = [
        'user_id', 'unit_id'
    ];

    // Relationship: A unit_user belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: A unit_user belongs to a unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
