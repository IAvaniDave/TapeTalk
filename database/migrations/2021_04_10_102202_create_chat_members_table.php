<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('group_id')->nullable()->default(NULL);
            $table->integer('user_id')->nullable()->default(NULL);
            $table->timestamp('clear_chat_time')->nullable()->default(NULL);
            $table->bigInteger('is_left')->comment('1 - left group 0 - not left')->default(0);
            $table->timestamp('left_group_at')->nullable()->default(NULL);
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
        Schema::dropIfExists('chat_members');
    }
}
