<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeVisaDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_visa_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("employee_passport_details_id");
            $table->foreign('employee_passport_details_id')->references('id')->on('employee_passport_details')->onDelete('cascade');
            $table->string("BRP_number");
            $table->date("visa_issue_date");
            $table->date("visa_expiry_date");
            $table->string("place_of_issue");
            $table->json("visa_docs");


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
        Schema::dropIfExists('employee_visa_details');
    }
}
