<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'body', 'tags', 'user_id', 'votes'
    ];

    public function user()
    {
        return $this->belongsTo("App\User");
    }

    public function comments()
    {
        return $this->hasMany("App\Comment");
    }

    public function votes()
    {
        return $this->hasMany("App\Vote");
    }

}
