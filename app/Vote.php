<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $fillable = ['vote', 'user_id', 'post_id'];
    protected $hidden = ['user_id', 'post_id'];

    public function user()
    {
        return $this->belongsTo("App\User");
    }

    public function post()
    {
        return $this->belongsTo("App\Post");
    }
}
