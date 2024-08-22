<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecruitmentProcessOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruitment_process_orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('recruitment_process_id');
            $table->unsignedBigInteger('business_id')->nullable();

            $table->integer('employee_order_no');
            $table->integer('candidate_order_no');

            $table->foreign('recruitment_process_id')->references('id')->on('recruitment_processes')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
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
        Schema::dropIfExists('recruitment_process_orders');
    }
}
