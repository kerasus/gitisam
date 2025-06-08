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
        Schema::create('transaction_invoice_distributions', function (Blueprint $table) {
            $table->id();

            // Columns for foreign keys
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('invoice_distribution_id');

            $table->unsignedBigInteger('amount')->nullable(); // Amount allocated to this distribution
            $table->unsignedBigInteger('paid_amount')->default(0); // Paid amount for this distribution

            $table->timestamps();
        });

        // Explicitly define foreign key constraints with custom names
        Schema::table('transaction_invoice_distributions', function (Blueprint $table) {
            $table->foreign('transaction_id', 'tid_foreign')
                ->references('id')->on('transactions')
                ->onDelete('cascade');

            $table->foreign('invoice_distribution_id', 'iid_foreign')
                ->references('id')->on('invoice_distributions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_invoice_distributions');
    }
};
