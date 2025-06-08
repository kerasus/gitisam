<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSamanTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saman_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('mid')->nullable();
            $table->string('state')->nullable();
            $table->integer('status')->nullable();
            $table->string('rrn')->nullable();
            $table->string('ref_num')->nullable();
            $table->string('res_num')->nullable();
            $table->string('terminal_id')->nullable();
            $table->string('trace_no')->nullable();
            $table->string('wage')->nullable();
            $table->string('secure_pan')->nullable();
            $table->string('hashed_card_number')->nullable();

            $table->string('result_code')->nullable();
            $table->string('result_description')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedBigInteger('original_amount')->nullable();
            $table->unsignedBigInteger('affective_amount')->nullable();
            $table->date('s_trace_date')->nullable();
            $table->string('s_trace_no')->nullable();

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saman_transactions');
    }
}
