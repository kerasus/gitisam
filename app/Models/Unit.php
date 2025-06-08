<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'user_id',
        'unit_number',
        'type',
        'area',
        'floor',
        'number_of_rooms',
        'number_of_residents',
        'parking_spaces',
        'description',
        'resident_base_balance',
        'owner_base_balance',
        'resident_paid_amount',
        'owner_paid_amount',
        'owner_debt',
        'resident_debt',
        'total_debt'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'area' => 'decimal:2',
        'floor' => 'integer',
        'number_of_rooms' => 'integer',
        'number_of_residents' => 'integer',
        'parking_spaces' => 'integer',
        'resident_base_balance' => 'integer',
        'owner_base_balance' => 'integer',
        'resident_paid_amount' => 'integer',
        'owner_paid_amount' => 'integer',
        'resident_debt' => 'integer',
        'owner_debt' => 'integer',
        'total_debt' => 'integer',
    ];

    protected $appends = ['current_resident_balance', 'current_owner_balance', 'current_balance', 'type_label'];

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

    /**
     * Relationship: A unit can belong to many users (many-to-many).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->withPivot('role') // Include the role in the pivot table
            ->withTimestamps();
    }

    /**
     * Get the resident of the unit.
     */
    public function residents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->wherePivot('role', 'resident')
            ->withTimestamps();
    }

    /**
     * Get the owner of the unit.
     */
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->wherePivot('role', 'owner')
            ->withTimestamps();
    }

    public function invoiceDistributions(): HasMany
    {
        return $this->hasMany(InvoiceDistribution::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function updateBalance()
    {
        try {

            // Calculate the total paid amount for residents and owners separately
            $newResidentPaidAmount = $this->transactions()
                ->notDeleted()
                ->paid()
                ->residentGroup() // Use the residentGroup scope
                ->sum('amount');

            $newOwnerPaidAmount = $this->transactions()
                ->notDeleted()
                ->paid()
                ->ownerGroup() // Use the ownerGroup scope
                ->sum('amount');

            // Update the paid amounts in the unit
            $this->update([
                'resident_paid_amount' => (int)$newResidentPaidAmount,
                'owner_paid_amount' => (int)$newOwnerPaidAmount,
                'total_paid_amount' => (int)($newResidentPaidAmount + $newOwnerPaidAmount),
            ]);
            $this->fresh();

            // Calculate the resident debt and owner debt separately
            $newResidentDebt = $this->invoiceDistributions()
                ->notDeleted()
                ->forResidents()
                ->sum('amount');

            $newOwnerDebt = $this->invoiceDistributions()
                ->notDeleted()
                ->forOwners()
                ->sum('amount');

            // Update resident_debt and owner_debt
            $this->update([
                'resident_debt' => (int)$newResidentDebt,
                'owner_debt' => (int)$newOwnerDebt,
                'total_debt' => (int)($newResidentDebt + $newOwnerDebt),
            ]);

        } catch (\Exception $e) {
            \Log::error("Error updating balance for unit ID {$this->id}: " . $e->getMessage());
            throw $e; // Re-throw the exception if needed
        }
    }

    public function scopeNegativeBalance($query)
    {
        return $query->whereRaw(
            '(CAST(resident_base_balance AS SIGNED) + CAST(owner_base_balance AS SIGNED)) + (CAST(resident_paid_amount AS SIGNED) + CAST(owner_paid_amount AS SIGNED)) - CAST(total_debt AS SIGNED) < 0'
        );
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'residential' => 'مسکونی',
            'commercial' => 'تجاری',
            default => 'نا مشخص',
        };
    }

    public function getCurrentBalanceAttribute()
    {
        return ($this->resident_base_balance + $this->owner_base_balance) + ( $this->resident_paid_amount + $this->owner_paid_amount) - ( $this->resident_debt + $this->owner_debt);
    }

    public function getCurrentResidentBalanceAttribute()
    {
        return $this->resident_base_balance + $this->resident_paid_amount - $this->resident_debt;
    }

    public function getCurrentOwnerBalanceAttribute()
    {
        return $this->owner_base_balance + $this->owner_paid_amount - $this->owner_debt;
    }

    public function allocateUnassignedTransactions()
    {
        try {
            // Fetch all unpaid invoice distributions for the unit
            $unpaidDistributions = $this->invoiceDistributions()
                ->notDeleted()
                ->unpaid()
                ->orderBy('created_at') // Prioritize older unpaid distributions
                ->get();

            // Fetch all paid transactions related to this unit
            $unitPaidTransactions = $this->transactions()->paid()->get();

            foreach ($unitPaidTransactions as $transaction) {
                // Get the unallocated amount of the transaction
                $unallocatedAmount = $transaction->getUnallocatedAmount();
                $transactionTargetGroup = $transaction->target_group;

                if ($unallocatedAmount <= 0) {
                    continue; // Skip if there is no unallocated amount left
                }

                // Loop through unpaid invoice distributions and allocate the remaining amount
                foreach ($unpaidDistributions as $key => $distribution) {
                    if ($unallocatedAmount <= 0) {
                        break; // Stop if the unallocated amount is fully distributed
                    }

                    $invoiceTargetGroup = $distribution->invoice->target_group;

                    if ($invoiceTargetGroup !== $transactionTargetGroup) {
                        break;
                    }

                    // Calculate the remaining amount needed for this distribution
                    $calculatedPaidAmount = $distribution->calculatePaidAmount();
                    $remainingAmountForDistribution = $distribution->amount - $calculatedPaidAmount;

                    if ($remainingAmountForDistribution > 0) {
                        // Determine the amount to allocate to this distribution
                        $allocatedAmount = min($remainingAmountForDistribution, $unallocatedAmount);

                        // Create the transaction_invoice_distribution record
                        TransactionInvoiceDistribution::create([
                            'transaction_id' => $transaction->id,
                            'invoice_distribution_id' => $distribution->id,
                            'amount' => $allocatedAmount,
                            'paid_amount' => $allocatedAmount,
                        ]);

                        // Update the remaining unallocated amount
                        $unallocatedAmount -= $allocatedAmount;

                        // If the distribution is fully paid, update its status and remove it from the list
                        if ($remainingAmountForDistribution === $allocatedAmount) {
                            unset($unpaidDistributions[$key]); // Remove the fully paid distribution
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in allocateUnassignedTransactions: " . $e->getMessage());
            throw $e;
        }
    }
}
