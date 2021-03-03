<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = "device_tokens";

    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'device_type', 'device_id', 'device_name', 'fcm_token', 'current_version', 'last_login_at', 'api_token'
    ];

    public function deviceToken(){
        return $this->belongsTo('App\User','user_id');
    }
}
