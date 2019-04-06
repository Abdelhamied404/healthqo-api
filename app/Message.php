<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'body', 'user_id', 'chat_id'
    ];

    public function User()
    {
        return $this->belongsTo("App\User");
    }
    public function Chat()
    {
        return $this->belongsTo("App\Chat");
    }
}
