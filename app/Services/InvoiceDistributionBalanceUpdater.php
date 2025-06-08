<?php
namespace App\Services;

use App\Models\InvoiceDistribution;
use App\Models\TransactionInvoiceDistribution;
use App\Models\Unit;
use Illuminate\Support\Facades\Log;

class InvoiceDistributionBalanceUpdater
{
    public function updateBalances(
        InvoiceDistribution $invoiceDistribution = null,
        bool $shouldAllocateUnassigned = true,
        Unit $unitToUpdateBalance = null,
        $resetTransactionInvoiceDistribution = false
    )
    {
        try {
            // Determine the unit to update
            $unit = $invoiceDistribution?->unit ?? $unitToUpdateBalance;

            if (!$unit) {
                throw new \Exception("No valid unit found for balance update.");
            }

            if ($resetTransactionInvoiceDistribution) {
                $invoiceDistributionIds = InvoiceDistribution::where('unit_id', $unit->id)
                    ->pluck('id');

                TransactionInvoiceDistribution::whereIn('invoice_distribution_id', $invoiceDistributionIds)->delete();

                InvoiceDistribution::where('unit_id', $unit->id)
                    ->update(['paid_amount' => 0, 'status' => 'unpaid']);
            }

            if ($shouldAllocateUnassigned) {
                $unit->allocateUnassignedTransactions();
            }

            if ($invoiceDistribution) {
                // Update the balance of the invoice distribution itself
                $invoiceDistribution->updateBalance();

                // Update the balance of the associated invoice
                $invoice = $invoiceDistribution->invoice;
                $invoice?->updateBalance();
            } else {
                // update all invoice distributions of unit
                $unit->invoiceDistributions()
                    ->notDeleted()
                    ->get()
                    ->map(function ($invoiceDistribution) {
                        $invoiceDistribution->updateBalance();
                        // Update the balance of the associated invoice
                        $invoice = $invoiceDistribution->invoice;
                        $invoice?->updateBalance();
                    });
            }

            // Update the balance of the unit
            $unit->updateBalance();

            // Update the balance of the associated building
            $building = $unit->building;
            $building?->updateBalance();
        } catch (\Exception $e) {
            Log::error("Error updating balances: " . $e->getMessage());
            throw $e; // Re-throw the exception if needed
        }
    }
}
