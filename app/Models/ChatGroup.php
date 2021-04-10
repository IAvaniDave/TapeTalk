<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatGroup extends Model
{

    use SoftDeletes;

    protected $table = "chat_groups";

    protected $guarded = [];
}
