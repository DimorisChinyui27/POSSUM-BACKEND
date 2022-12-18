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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->string('slug')->unique();
            $table->foreignId('user_id')->constrained()->on('users');
            $table->float('gift')->default(0.0);
            $table->boolean('has_correct_answer')->default(false);
            $table->enum('target', ['experts', 'nearby', 'user']);
            $table->boolean('is_anonymous')->default(false);
            $table->softDeletes();
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
        Schema::dropIfExists('questions');
    }
};
