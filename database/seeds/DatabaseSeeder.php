<?php

use Illuminate\Database\Seeder;
use App\Appointment;
use App\Chat;
use App\Comment;
use App\User;
use App\Doctor;
use App\Post;
use App\Section;
use App\Message;
use App\Recipient;
use App\Vote;
use App\Reservation;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        factory(User::class, 100)->create();
        factory(Section::class, 7)->create();
        factory(Doctor::class, 30)->create();
        factory(Post::class, 50)->create();
        factory(Comment::class, 100)->create();
        factory(Appointment::class, 10)->create();
        factory(Chat::class, 20)->create();
        factory(Message::class, 200)->create();
        factory(Recipient::class, 40)->create();
        factory(Vote::class, 20)->create();
        factory(Reservation::class, 20)->create();
    }
}
