<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = "chat_messages";

    protected $guarded = [];

    public function user(){
        return $this->belongsTo('App\User','sender_id', 'id')->select('id','username','status','email','deleted_at','logo');
    }

    public function group(){
        return $this->belongsTo('App\Models\ChatGroup','group_id', 'id');
    }
}
