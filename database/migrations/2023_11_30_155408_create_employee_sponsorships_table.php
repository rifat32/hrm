<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeSponsorshipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_sponsorships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("employee_id");
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->date("date_assigned");
            $table->date("expiry_date");
            $table->enum('status', ['pending', 'approved', 'denied', 'visa_granted'])->default("pending");
            $table->text("note");
            $table->text("certificate_number");
            $table->enum('current_certificate_status', ['pending', 'approved', 'denied'])->default("pending");
            $table->boolean("is_sponsorship_withdrawn");
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
        Schema::dropIfExists('employee_sponsorships');
    }
}
