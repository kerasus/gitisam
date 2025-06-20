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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->string('unit_number');
            $table->enum('type', ['residential', 'commercial']);
            $table->decimal('area', 8, 2);
            $table->integer('floor');
            $table->integer('number_of_rooms');
            $table->integer('number_of_residents');
            $table->integer('parking_spaces');
            $table->bigInteger('resident_base_balance')->default(0);
            $table->bigInteger('owner_base_balance')->default(0);
            $table->unsignedBigInteger('resident_paid_amount')->default(0);
            $table->unsignedBigInteger('owner_paid_amount')->default(0);
            $table->unsignedBigInteger('total_paid_amount')->default(0);
            $table->unsignedBigInteger('resident_debt')->default(0);
            $table->unsignedBigInteger('owner_debt')->default(0);
            $table->unsignedBigInteger('total_debt')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
