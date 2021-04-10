<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMember extends Model
{
    use SoftDeletes;

    protected $table = "chat_members";

    protected $guarded = [];

    public function group(){
        return $this->belongsTo('App\Models\ChatGroup','group_id');
    }

    public function user(){
        return $this->belongsTo('App\User','user_id');
    }
}
