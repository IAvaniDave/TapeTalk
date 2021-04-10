<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReceivers extends Model
{
    protected $table = "message_receivers";

    protected $guarded = [];

    public function user(){
        return $this->belongsTo('App\User','receiver_id', 'id')->select('id','username','logo','status');
    }
}
