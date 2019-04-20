<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            // required
            $table->bigIncrements('id');
            $table->boolean('is_trusted')->default(false);
            $table->integer('rate')->unsigned()->default(0);
            
            // extras
            $table->text('certificate')->nullable();
            $table->text('clinic_address')->nullable();
            $table->text('hospital_address')->nullable();

            /**
             * foreigns
             */
            // on user
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
            // on section
            $table->unsignedBigInteger('section_id');
            $table->foreign('section_id')
                ->references('id')->on('sections')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctors');
    }
}
