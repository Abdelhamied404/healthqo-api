<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'is_trusted', 'rate', 'certificate', 'clinic_address', 'hospital_address', 'user_id', 'section_id'
    ];
    protected $hidden = [
        'user_id', 'section_id'
    ];

    public function section()
    {
        return $this->belongsTo("App\Section");
    }

    public function user()
    {
        return $this->belongsTo("App\User");
    }

}
