<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Reservation;
use Faker\Generator as Faker;

$factory->define(Reservation::class, function (Faker $faker) {
    return [
        'code' => $faker->Uuid(),
        'user_id' => $faker->numberBetween(1,100),
        'appointment_id' => $faker->numberBetween(1,10)
    ];
});
