<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("location")->nullable();
            $table->text("description")->nullable();
            $table->boolean("is_active")->default(true);

            $table->unsignedBigInteger("manager_id");
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger("parent_id")->nullable(true);
            $table->foreign('parent_id')->references('id')->on('departments')->onDelete('cascade');
            $table->unsignedBigInteger("business_id");
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
        Schema::dropIfExists('departments');
    }
}