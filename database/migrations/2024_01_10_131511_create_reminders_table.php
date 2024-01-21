<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();

            $table->string('title');


            $table->string('db_table_name');
            $table->string('db_field_name');
            $table->string('entity_name');


            $table->integer('duration');
            $table->enum('duration_unit', ['days', 'weeks', 'months']);
            $table->enum('send_time', ['before_expiry', 'after_expiry']);
            $table->integer('frequency_after_first_reminder')->nullable();
            $table->boolean('keep_sending_until_update');

            $table->unsignedBigInteger("business_id");
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            $table->unsignedBigInteger("created_by")->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders');
    }
}
