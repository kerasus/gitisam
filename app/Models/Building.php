<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'city', 'district', 'latitude', 'longitude', 'base_balance', 'total_income', 'paid_amount', 'total_debt'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_balance' => 'integer',
        'total_income' => 'integer',
        'paid_amount' => 'integer',
        'total_debt' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
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


    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Update the building's balances.
     */
    public function updateBalance()
    {
        try {
            // Calculate base_balance, paid_amount, and total_debt from units

            $residentBaseBalance = $this->units()->sum('resident_base_balance');
            $ownerBaseBalance = $this->units()->sum('owner_base_balance');
            $residentPaidAmount = $this->units()->sum('resident_paid_amount');
            $ownerPaidAmount = $this->units()->sum('owner_paid_amount');
            $residentDebt = $this->units()->sum('resident_debt');
            $ownerDebt = $this->units()->sum('owner_debt');

            $this->base_balance = $residentBaseBalance + $ownerBaseBalance;
            $this->paid_amount = $residentPaidAmount + $ownerPaidAmount;
            $this->total_debt = $residentDebt + $ownerDebt;

            // Calculate total income from building-related transactions
            $this->total_income = $this->transactions()
                ->notDeleted()
                ->buildingIncome()
                ->paid()
                ->sum('amount');

            $this->save();
        } catch (\Exception $e) {
            \Log::error("Error updating balance for building ID {$this->id}: " . $e->getMessage());
            throw $e; // Re-throw the exception if needed
        }
    }

    /**
     * Get the remaining balance of the building.
     */
//    public function getRemainingBalanceAttribute()
//    {
//        return max($this->total_amount - $this->paid_amount, 0);
//    }

    /**
     * Get the current balance of the building.
     */
    public function getCurrentBalanceAttribute()
    {
        return $this->base_balance + $this->paid_amount + $this->total_income - $this->total_debt;
    }
}
