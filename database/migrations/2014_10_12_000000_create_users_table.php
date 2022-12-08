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
            $table->string('firstname');
            $table->string('lastname');
            $table->string('password');
            $table->enum('role', array('admin','listener','announcer'));
            $table->date('birthdate');
            $table->string('country');
            $table->string('phone');
            $table->string('email')->unique();
            $table->integer('joins')->default(1);
            $table->date('joins_updated_at')->default('2000-01-01');
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
};
