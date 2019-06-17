<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Post;
use Faker\Generator as Faker;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'title' => $faker->word,
        'body' => $faker->paragraph(3,true),
        'tags' => json_encode($faker->words(3,false)),
        'user_id' => $faker->numberBetween(1,100),
        'vote' => $faker->numberBetween(1,100),
    ];
});
