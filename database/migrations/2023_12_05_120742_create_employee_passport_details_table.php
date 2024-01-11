<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeePassportDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_passport_details', function (Blueprint $table) {
            $table->id();
         $table->unsignedBigInteger("user_id");
         $table->unsignedBigInteger("business_id");
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string("passport_number");
            $table->date("passport_issue_date");
            $table->date("passport_expiry_date");
            $table->string("place_of_issue");

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
        Schema::dropIfExists('employee_passport_details');
    }
}
