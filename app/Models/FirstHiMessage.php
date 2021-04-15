<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirstHiMessage extends Model
{
    protected $table = "first_hi_message";

    protected $guarded = [];

    public function sender(){
        return $this->belongsTo('App\User','sender_id', 'id')->select('id','username','status','email','deleted_at','logo');
    }

    public function receiver(){
        return $this->belongsTo('App\User','receiver_id', 'id')->select('id','username','status','email','deleted_at','logo');
    }
}
