<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Message;
use Faker\Generator as Faker;

$factory->define(Message::class, function (Faker $faker) {
    return [
        'body' => $faker->sentence,
        'user_id' => $faker->numberBetween(1,100),
        'chat_id' => $faker->numberBetween(1,20),
    ];
});
