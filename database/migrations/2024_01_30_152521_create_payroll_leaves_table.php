<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_leaves', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("payroll_id");
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->unsignedBigInteger("leaves_id");
            $table->foreign('leaves_id')->references('id')->on('leaves')->onDelete('restrict');


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
        Schema::dropIfExists('payroll_leaves');
    }
}
