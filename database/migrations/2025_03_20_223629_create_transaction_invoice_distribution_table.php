<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_invoice_distribution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->onDelete('cascade');
            $table->foreignId('invoice_distribution_id')
                ->constrained('invoice_distribution')
                ->onDelete('cascade');
            $table->decimal('amount', 10, 2)
                ->comment('The total amount allocated to this invoice distribution in the transaction');
            $table->decimal('paid_amount', 10, 2)
                ->default(0)
                ->comment('The amount that has been paid for this invoice distribution');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_invoice_distribution');
    }
};
