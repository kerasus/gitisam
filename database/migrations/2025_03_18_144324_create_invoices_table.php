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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->date('due_date');
            $table->string('image')->nullable();
            $table->foreignId('invoice_category_id')->constrained('invoice_categories')->onDelete('cascade');
            $table->enum('status', ['unpaid', 'paid', 'pending', 'cancelled'])->default('unpaid');
            $table->enum('type', ['monthly_charge', 'planned_expense', 'unexpected_expense'])->default('monthly_charge');
            $table->enum('target_group', ['resident', 'owner'])->default('resident');
            $table->boolean('is_covered_by_monthly_charge')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
