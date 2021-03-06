<?php

namespace App;

use Laravel\Passport\HasApiTokens;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'password', 'address', 'state', 'country', 'gender', 'phone', 'avatar',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->hasOne("App\Doctor");
    }

    public function post()
    {
        return $this->hasMany("App\Post");
    }

    public function Recipient()
    {
        return $this->hasOne("App\Recipient");
    }
    public function Message()
    {
        return $this->hasMany("App\Message");
    }
    public function votes()
    {
        return $this->hasMany("App\Vote");
    }

    public function reservations()
    {
        return $this->hasMany("App\Reservation");
    }
}
