<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->date('dob')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('address')->nullable()->comment('Quarter');
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(true)->comment('Account is active');
            $table->string('profile_picture')->nullable()->comment('Profile picture');
            $table->string('password');

            // type of sign up and os
            $table->string('os')->default('web')->comment('web, tablet, phone');
            $table->string('signup_type')->default('afeshop')->nullable('facebook, google, possum');

            // Country foreign key
            $table->unsignedBigInteger('country_id')->nullable()->default(1)->comment('Users country');
            $table->foreign('country_id')->references('id')
                ->on('world_countries')->onUpdate('cascade')->onDelete('set null');

            // Cities foreign key
            $table->unsignedBigInteger('city_id')->nullable()->comment('Users Cities');
            $table->foreign('city_id')->references('id')
                ->on('world_cities')->onUpdate('cascade')->onDelete('set null');

            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
