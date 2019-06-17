<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use Faker\Generator as Faker;
use App\Chat;


$factory->define(Chat::class, function (Faker $faker) {
    return [
        'title' => $faker->name,
    ];
});
