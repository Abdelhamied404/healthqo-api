<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Section;
use Faker\Generator as Faker;

$factory->define(Section::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->word,
        'image' => "http://asap.api/public/sections/Bones.svg",
        'icon' => "http://asap.api/public/sections/icons/Bones.svg",
    ];
});
