<?php

namespace App\Observers;

use App\Models\InvoiceDistribution;
use App\Services\InvoiceDistributionBalanceUpdater;
use Illuminate\Support\Facades\DB;

class InvoiceDistributionObserver
{
    protected InvoiceDistributionBalanceUpdater $balanceUpdater;

    public function __construct(InvoiceDistributionBalanceUpdater $balanceUpdater)
    {
        $this->balanceUpdater = $balanceUpdater;
    }

    /**
     * Handle the InvoiceDistribution "created" event.
     */
    public function created(InvoiceDistribution $invoiceDistribution): void
    {
        // Do not call allocateUnassignedTransactions because of bulk store
    }

    /**
     * Handle the InvoiceDistribution "updated" event.
     */
    public function updated(InvoiceDistribution $invoiceDistribution): void
    {
        // Do not call allocateUnassignedTransactions because of update method in updateRelatedBalances
//        $this->updateRelatedBalances($invoiceDistribution);
    }

    /**
     * Handle the InvoiceDistribution "deleted" event.
     */
    public function deleted(InvoiceDistribution $invoiceDistribution): void
    {
        $this->updateRelatedBalances($invoiceDistribution);
    }

    /**
     * Handle the InvoiceDistribution "restored" event.
     */
    public function restored(InvoiceDistribution $invoiceDistribution): void
    {
        $this->updateRelatedBalances($invoiceDistribution);
    }

    /**
     * Handle the InvoiceDistribution "force deleted" event.
     */
    public function forceDeleted(InvoiceDistribution $invoiceDistribution): void
    {
        $this->updateRelatedBalances($invoiceDistribution);
    }

    /**
     * Update balances of related Unit and Building.
     */
    private function updateRelatedBalances(InvoiceDistribution $invoiceDistribution)
    {
        DB::beginTransaction();
        try {
             $this->balanceUpdater->updateBalances($invoiceDistribution);
            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();
            \Log::error(".....: " . $e->getMessage());
            return response()->json([
                'message' => '.....',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
