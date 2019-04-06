<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPlatformKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $keys = array(
            array(
                'name' => 'PUBLIC', 'secret' => env('PUBLIC_KEY'),
                'redirect' => env('APP_URL'),
                'personal_access_client' => 1,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ),
            array(
                'name' => 'ADMIN', 'secret' => env('ADMIN_KEY'),
                'redirect' => env('APP_URL'),
                'personal_access_client' => 1,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ),
            array(
                'name' => 'WEB', 'secret' => env('WEB_KEY'),
                'redirect' => env('APP_URL'),
                'personal_access_client' => 1,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ),
            array(
                'name' => 'ANDROID', 'secret' => env('ANDROID_KEY'),
                'redirect' => env('APP_URL'),
                'personal_access_client' => 1,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ),
            array(
                'name' => 'IOS', 'secret' => env('IOS_KEY'),
                'redirect' => env('APP_URL'),
                'personal_access_client' => 1,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => now(),
                'updated_at' => now()
            )
        );
        DB::table('oauth_clients')->insert($keys);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
