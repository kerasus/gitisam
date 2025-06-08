<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\InvoiceDistributionCalculatorService;
use Illuminate\Support\Facades\Log;

class InvoiceDistribution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id', 'unit_id', 'distribution_method', 'description', 'amount', 'paid_amount', 'status',
    ];

    protected $appends = ['current_balance'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'distribution_method' => 'string',
        'status' => 'string',
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: An invoice distribution belongs to an invoice.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Relationship: An invoice distribution belongs to a unit.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relationship: An invoice distribution can belong to many transactions.
     */
    public function transactions()
    {
        return $this->belongsToMany(Transaction::class, 'transaction_invoice_distributions')
            ->withPivot('amount', 'paid_amount')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope to filter distributions for resident-related invoices.
     */
    public function scopeForResidents($query)
    {
        return $query->whereHas('invoice', function ($q) {
            $q->forResidents();
        });
    }

    /**
     * Scope to filter distributions for owner-related invoices.
     */
    public function scopeForOwners($query)
    {
        return $query->whereHas('invoice', function ($q) {
            $q->forOwners();
        });
    }

    public function getCurrentBalanceAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    /**
     * Get the label for the status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'unpaid' => 'پرداخت نشده',
            'paid' => 'پرداخت شده',
            'pending' => 'در حال بررسی',
            'cancelled' => 'لغو شده',
            default => 'نا مشخص',
        };
    }

    /**
     * Get the label for the distribution_method.
     */
    public function getDistributionMethodLabelAttribute(): string
    {
        return match ($this->distribution_method) {
            'equal' => 'برابر',
            'per_person' => 'بر اساس تعداد نفرات',
            'area' => 'بر اساس متراژ',
            'parking' => 'بر اساس پارکینگ',
            'custom' => 'دلخواه',
            default => 'نا مشخص',
        };
    }

    public function calculatePaidAmount(): int
    {
        return (int)$this->transactions()
            ->notDeleted()
            ->paid()
            ->sum('transaction_invoice_distributions.paid_amount');
    }

    public function updateBalance()
    {
        try {
            // Calculate the total paid amount for this invoice distribution
            $newPaidAmount = $this->calculatePaidAmount();

            // Update the paid_amount of the invoice distribution
            $this->update(['paid_amount' => $newPaidAmount]);

            // Check if the distribution is fully paid
            if ($newPaidAmount >= $this->amount) {
                $this->update(['status' => 'paid']);
            } else {
                $this->update(['status' => 'unpaid']);
            }
        } catch (\Exception $e) {
            \Log::error("Error updating balance for invoice distribution ID {$this->id}: " . $e->getMessage());
            throw $e; // Re-throw the exception if needed
        }
    }

    /**
     * Handle the deletion of an invoice distribution.
     */
    public function handleDeletion()
    {
        DB::beginTransaction();
        try {
            // Get the related invoice and its total amount
            $invoice = $this->invoice;
            if (!$invoice) {
                throw new \Exception("Invoice not found for invoice distribution ID {$this->id}");
            }

            $totalInvoiceAmount = $invoice->amount;

            // Get all remaining distributions for the invoice
            $remainingDistributions = $invoice->invoiceDistributions()
                ->where('id', '!=', $this->id)
                ->get();

            $count = $remainingDistributions->count();
            if ($count === 0) {
                $this->delete();
                DB::commit();
                return;
            }

            // Recalculate amounts and descriptions using the service
            $calculatorService = new InvoiceDistributionCalculatorService();
            $newDistributions = $calculatorService->calculate(
                $remainingDistributions->first()->distribution_method,
                $remainingDistributions->pluck('unit_id')->toArray(),
                $totalInvoiceAmount
            );

            // Update each remaining distribution
            foreach ($remainingDistributions as $distribution) {
                $newData = $newDistributions->firstWhere('unit_id', $distribution->unit_id);
                $distribution->update([
                    'amount' => $newData['amount'],
                    'description' => $newData['description'],
                ]);
            }

            // Soft delete the current invoice distribution
            $this->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error handling deletion for invoice distribution ID {$this->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
