<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageReceiversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_receivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('group_id')->nullable()->default(NULL);
            $table->integer('receiver_id')->nullable()->default(NULL);
            $table->integer('message_id')->nullable()->default(NULL);
            $table->integer('is_read')->nullable()->default(NULL)->comment('0 = not read ,1 = read');
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
        Schema::dropIfExists('message_receivers');
    }
}
