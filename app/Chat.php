<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    public $timestamps = false;
    protected $fillable = ['title'];

    public function Messages()
    {
        return $this->hasMany("App\Message");
    }

    public function Recipients()
    {
        return $this->hasMany("App\Recipient");
    }

}
