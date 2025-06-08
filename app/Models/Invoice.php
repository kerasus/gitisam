<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\TargetGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'amount',
        'paid_amount',
        'due_date',
        'invoice_category_id',
        'status',
        'type',
        'target_group',
        'is_covered_by_monthly_charge',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'due_date' => 'date:Y-m-d',
        'status' => InvoiceStatus::class,
        'type' => InvoiceType::class,
        'target_group' => TargetGroup::class,
        'is_covered_by_monthly_charge' => 'boolean'
    ];

    protected $appends = ['target_group_label'];

    /**
     * Relationship: An invoice belongs to an invoice category.
     */
    public function invoiceCategory()
    {
        return $this->belongsTo(InvoiceCategory::class, 'invoice_category_id');
    }

    /**
     * Relationship: An invoice can have many invoice distributions.
     */
    public function invoiceDistributions()
    {
        return $this->hasMany(InvoiceDistribution::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Scope to filter invoices for residents.
     */
    public function scopeForResidents($query)
    {
        return $query->where('target_group', 'resident');
    }

    /**
     * Scope to filter invoices for owners.
     */
    public function scopeForOwners($query)
    {
        return $query->where('target_group', 'owner');
    }

    /**
     * Get the label for the status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            InvoiceStatus::Unpaid => 'پرداخت نشده',
            InvoiceStatus::Paid => 'پرداخت شده',
            InvoiceStatus::Pending => 'در حال بررسی',
            InvoiceStatus::Cancelled => 'لغو شده',
            default => 'نا مشخص',
        };
    }

    /**
     * Get the label for the type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            InvoiceType::MonthlyCharge => 'شارژ ماهیانه',
            InvoiceType::PlannedExpense => 'هزینه‌های پیش‌بینی ‌شده',
            InvoiceType::UnexpectedExpense => 'هزینه های پیش‌بینی‌ نشده',
            default => 'نا مشخص',
        };
    }

    public function getTargetGroupLabelAttribute(): string
    {
        return match ($this->target_group) {
            TargetGroup::Resident => 'ساکن',
            TargetGroup::Owner => 'مالک',
            default => 'نا مشخص',
        };
    }

    /**
     * Check if the invoice is covered by a monthly charge.
     */
    public function getIsCoveredByMonthlyChargeLabelAttribute(): string
    {
        return $this->is_covered_by_monthly_charge ? 'بله' : 'خیر';
    }

    public function updateBalance()
    {
        try {
            // Calculate the total paid amount for this invoice
            $newPaidAmount = $this->invoiceDistributions()
                ->sum('paid_amount');

            // Update the paid_amount of the invoice
            $this->update(['paid_amount' => $newPaidAmount]);

            // Check if the invoice is fully paid
            if ($newPaidAmount >= $this->amount) {
                $this->update(['status' => 'paid']);
            } else {
                $this->update(['status' => 'unpaid']);
            }
        } catch (\Exception $e) {
            \Log::error("Error updating balance for invoice ID {$this->id}: " . $e->getMessage());
            throw $e; // Re-throw the exception if needed
        }
    }
}
