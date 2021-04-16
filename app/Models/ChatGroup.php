<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class ChatGroup extends Model
{

    use SoftDeletes;

    
    protected $table = "chat_groups";

    protected $user = "fghfh";

    protected $guarded = [];

    public function chatMembers(){
        return $this->hasMany('App\Models\ChatMember','group_id');
    }
    public function chatMessages(){
        return $this->hasMany('App\Models\ChatMessage','group_id')->orderBy('updated_at','DESC');
    }

    public function unreadMessage(){
        return $this->hasMany('App\Models\MessageReceivers','group_id')->where('is_read',0);
    }
}
