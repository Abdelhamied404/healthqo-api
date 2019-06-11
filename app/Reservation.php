<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public $fillable = ["code", "user_id", "appointment_id"];
    public $hidden = ["user_id", "appointment_id"];

    public function appointment()
    {
        return $this->belongsTo("App\Appointment");
    }
    public function user()
    {
        return $this->belongsTo("App\User");
    }
}
