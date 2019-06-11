<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public $fillable = ["time", "doctor_id"];
    public $hidden = ["doctor_id"];

    public function doctor()
    {
        return $this->belongsTo("App\Doctor");
    }
    public function reservation()
    {
        return $this->hasOne("App\Reservation");
    }
}
