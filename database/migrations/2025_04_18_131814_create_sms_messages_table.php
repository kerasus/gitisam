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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->nullable(); // Optional relation to Unit model
            $table->string('mobile'); // Recipient mobile number
            $table->text('message');
            $table->string('template_id')->nullable(); // Template ID for SMS.ir
            $table->string('status')->default('pending'); // Status: pending, sent, failed
            $table->string('message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
