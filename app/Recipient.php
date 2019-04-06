<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'chat_id'
    ];
    protected $hidden = [
        'chat_id', 'id', 'user_id'
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
