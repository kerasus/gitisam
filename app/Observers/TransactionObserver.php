<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use App\Services\InvoiceDistributionBalanceUpdater;

class TransactionObserver
{
    protected InvoiceDistributionBalanceUpdater $balanceUpdater;

    public function __construct(InvoiceDistributionBalanceUpdater $balanceUpdater)
    {
        $this->balanceUpdater = $balanceUpdater;
    }

    /**
     * Handle the "created" event.
     */
    public function created(Transaction $transaction)
    {
        $this->updateRelatedBalances($transaction);
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Transaction $transaction)
    {
        $this->updateRelatedBalances($transaction);
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Transaction $transaction)
    {
        $this->updateRelatedBalances($transaction);
    }

    /**
     * Handle the InvoiceDistribution "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        $this->updateRelatedBalances($transaction);
    }

    /**
     * Handle the InvoiceDistribution "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        $this->updateRelatedBalances($transaction);
    }

    /**
     * Update balances of related Unit and Building.
     */
    private function updateRelatedBalances(Transaction $transaction)
    {
        try {
//            $startTime = microtime(true); // شروع زمان‌سنجی

            $invoiceDistributions = $transaction->invoiceDistributions;

            if ($invoiceDistributions->isNotEmpty()) {
//                Log::info("Processing invoice distributions for transaction ID {$transaction->id}");

                // Update balances for each invoice distribution
                foreach ($invoiceDistributions as $index => $invoiceDistribution) {
//                    $startDistributionTime = microtime(true);
                    $this->balanceUpdater->updateBalances($invoiceDistribution);
//                    $endDistributionTime = microtime(true);

//                    Log::info("Processed invoice distribution #{$index} in " . ($endDistributionTime - $startDistributionTime) . " seconds");
                }
            } else {
//                Log::info("No invoice distributions found for transaction ID {$transaction->id}. Updating unit balance directly.");

                // If no invoice distributions exist, update balances for the unit directly
                $unit = $transaction->unit;

                if ($unit) {
//                    $startUnitUpdateTime = microtime(true);
                    $this->balanceUpdater->updateBalances(null, true, $unit);
//                    $endUnitUpdateTime = microtime(true);

//                    Log::info("Updated unit balance in " . ($endUnitUpdateTime - $startUnitUpdateTime) . " seconds");
                }
//                else {
//                    Log::warning("No unit or invoice distributions found for transaction ID {$transaction->id}");
//                }
            }

//            $endTime = microtime(true); // پایان زمان‌سنجی
//            Log::info("Total time for updateRelatedBalances: " . ($endTime - $startTime) . " seconds");
        } catch (\Exception $e) {
            Log::error("Error updating balances for transaction ID {$transaction->id}: " . $e->getMessage());
            throw $e; // Re-throw the exception if needed
        }
    }
}
