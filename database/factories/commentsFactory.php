<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use Faker\Generator as Faker;
use App\Comment;


$factory->define(Comment::class, function (Faker $faker) {
    return [
        'body' => $faker->sentence(),
        'user_id' => $faker->numberBetween(1,100),
        'post_id' => $faker->numberBetween(1,50)
    ];
});
