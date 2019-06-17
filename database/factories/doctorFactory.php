<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Doctor;
use Faker\Generator as Faker;

$factory->define(Doctor::class, function (Faker $faker) {
    return [
        'is_trusted' => $faker->numberBetween(0,1),
        'rate' => $faker->numberBetween(0,5),
        'certificate' => $faker->sentence(7,true),
        'clinic_address' => $faker->sentence(7,true),
        'hospital_address' => $faker->sentence(7,true),
        'user_id' => $faker->unique()->numberBetween(1,100),
        'section_id' => $faker->numberBetween(1,7),
    ];
});
