<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeRotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_rotas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('type', ['regular', 'scheduled', 'flexible'])->default("regular");


            $table->unsignedInteger('attendances_count')->default(0);



            $table->boolean("is_default")->default(false);

            $table->boolean("is_active")->default(true);
            $table->boolean("is_business_default")->default(false);
            $table->boolean("is_personal")->default(false);




            $table->unsignedBigInteger("business_id")->nullable();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            $table->unsignedBigInteger("created_by")->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');


            $table->softDeletes();
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
        Schema::dropIfExists('employee_rotas');
    }
}
