<?php

use App\User;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {

    $male_pics = [
        "http://asap.api/public/profile_pics/default/male1.png" ,
        "http://asap.api/public/profile_pics/default/male2.png" ,
    ];
    $female_pics = [
        "http://asap.api/public/profile_pics/default/female.png",
    ];

    $rand_gen = $faker->numberBetween(0,1)?"male":"female";
    $rand_pic = $rand_gen == "male" ? $male_pics[array_rand($male_pics)] : $female_pics[array_rand($female_pics)];

    $rand_name = $faker->firstName($rand_gen);

    static $i = 1;

    return [
        'name' => $rand_name,
        'username' => $rand_name."-".$i++,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt("123456"), // password
        'address' => $faker->word,
        'state' => $faker->word,
        'country' => $faker->word,
        'phone' => $faker->randomNumber(null, false),
        'gender' => $rand_gen,
        'avatar' => $rand_pic,
        'remember_token' => Str::random(10),
    ];
});
