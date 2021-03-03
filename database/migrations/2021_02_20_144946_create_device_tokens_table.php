<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->tinyInteger('device_type')->comment('1:Android,2:IOS')->default('1');
            $table->string('device_id',150)->unique()->nullable()->default(NULL);
            $table->string('device_name')->nullable()->default(NULL);
            $table->string('fcm_token')->nullable()->default(NULL);
            $table->string('current_version',10)->nullable()->default(NULL);
            $table->dateTime('last_login_at')->nullable()->default(NULL);
            $table->string('api_token')->nullable()->default(NULL);
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
        Schema::dropIfExists('device_tokens');
    }
}
