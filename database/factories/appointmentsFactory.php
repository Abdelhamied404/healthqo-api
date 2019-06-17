<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use Faker\Generator as Faker;
use App\Appointment;

$factory->define(Appointment::class, function (Faker $faker) {
    // $faker->addProvider(new Faker\Provider\en_US\Address($faker));
    return [
        'time' => $faker->dateTime("now", null),
        'checked' => $faker->numberBetween(0,1),
        'doctor_id' => $faker->numberBetween(1,30),
    ];
});
