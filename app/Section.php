<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'image', 'icon'];

    public function doctors()
    {
        return $this->hasMany('App\Doctor');
    }
}
