<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Vote;
use Faker\Generator as Faker;

$factory->define(Vote::class, function (Faker $faker) {
    return [
        'vote' => $faker->numberBetween(10,100),
        'user_id' => $faker->numberBetween(1,100),
        'post_id' => $faker->numberBetween(1,50)
    ];
});
