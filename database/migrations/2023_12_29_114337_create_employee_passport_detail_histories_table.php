<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeePassportDetailHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_passport_detail_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("employee_id");
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->string("passport_number");
            $table->date("passport_issue_date");
            $table->date("passport_expiry_date");
            $table->string("place_of_issue");


            $table->date("from_date");
            $table->date("to_date")->nullable();

            $table->unsignedBigInteger("employee_passport_detail_id")->nullable();
            $table->foreign('employee_passport_detail_id')->references('id')->on('users')->onDelete('set null');

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
        Schema::dropIfExists('employee_passport_detail_histories');
    }
}
