<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Recipient;
use Faker\Generator as Faker;

$factory->define(Recipient::class, function (Faker $faker) {
    return [
        'user_id' => $faker->unique()->numberBetween(1,100),
        'chat_id' => $faker->numberBetween(1,20),
    ];
});
