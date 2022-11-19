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
        Schema::create('users_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->on('users');
            $table->integer('credit_score')->default(24);
            $table->foreignId('topic_id')->constrained()->on('topics');
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
        Schema::dropIfExists('topics_users');
    }
};
