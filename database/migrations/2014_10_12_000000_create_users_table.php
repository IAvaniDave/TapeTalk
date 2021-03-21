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
            $table->bigIncrements('id')->autoIncrement();
            $table->string('username')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('logo')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1:active, 0:inactive');
            $table->tinyInteger('gender')->default(1)->comment('1:male, 2:female');
            $table->string('ip_address', 15)->nullable();
            $table->string('provider_id')->nullable();
            $table->string('provider_name')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
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
