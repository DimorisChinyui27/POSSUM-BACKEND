<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('external_id')->unique();
            $table->string('type')->default('BOUNCE');
            $table->float('amount');
            $table->float('fee')->default(0.0);
            $table->enum('status', ['cancelled', 'pending', 'failed', 'complete'])->default('pending');
            $table->foreignId('user_id')->constrained()->on('users');
            $table->foreignId('question_id')->nullable()->constrained()->on('questions');
            $table->foreignId('answer_id')->nullable()->constrained()->on('answers');
            $table->foreignId('payment_method_id')->constrained()->on('payment_methods');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
