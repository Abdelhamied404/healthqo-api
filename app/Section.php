<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'image'];

    public function doctor()
    {
        return $this->hasOne('App\Doctor');
    }
}
