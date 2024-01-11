<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("attendance_id")->nullable();
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('set null');
            $table->unsignedBigInteger("actor_id")->nullable();
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
            $table->string('action');
            $table->date('attendance_created_at');
            $table->date('attendance_updated_at');


            $table->text('note')->nullable();
            $table->string('in_geolocation')->nullable();
            $table->string('out_geolocation')->nullable();




            $table->unsignedBigInteger("user_id");
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');


            $table->date('in_date');

            $table->boolean("does_break_taken");


            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();

            $table->integer('capacity_hours');

            $table->enum('behavior', ['absent', 'late','regular','early']);


            $table->integer('work_hours_delta');
            $table->integer('regular_work_hours');
            $table->integer('total_paid_hours');

            $table->enum('break_type', ['paid', 'unpaid']);
            $table->integer('break_hours');

            $table->enum('status', ['pending_approval', 'approved','rejected'])->default("pending_approval");









            $table->boolean("is_active")->default(true);

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
        Schema::dropIfExists('attendance_histories');
    }
}
