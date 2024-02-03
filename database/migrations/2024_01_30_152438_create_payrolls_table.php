<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("payrun_id")->nullable();
            $table->foreign('payrun_id')->references('id')->on('payruns')->onDelete('set null');

            $table->unsignedBigInteger("user_id");
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->double('total_holiday_hours');
            $table->double('total_leave_hours');
            $table->double('total_regular_attendance_hours');
            $table->double('total_overtime_attendance_hours');
            $table->double('regular_hours');
            $table->double('overtime_hours');
            $table->double('holiday_hours_salary');
            $table->double('leave_hours_salary');
            $table->double('regular_attendance_hours_salary');
            $table->double('overtime_attendance_hours_salary');
            $table->double('regular_hours_salary');
            $table->double('overtime_hours_salary');


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
        Schema::dropIfExists('payrolls');
    }
}
