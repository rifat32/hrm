<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->boolean('is_enabled')->default(false);
            $table->string('is_default')->default(false);



            $table->unsignedBigInteger("business_tier_id")->nullable();
            $table->foreign('business_tier_id')->references('id')->on('business_tiers')->onDelete('cascade');


            $table->unsignedBigInteger("created_by")->nullable();
            $table->timestamps();
        });


        DB::table('modules')
        ->insert(array(
           [
            "name" => "project_and_task_management",
            "is_active" => 1,
            "is_default" => 1,
            "business_tier_id" => NULL,
           ],

           [
            "name" => "user_activity",
            "is_active" => 1,
            "is_default" => 1,
            "business_tier_id" => NULL,
           ],
        ));

        DB::table('modules')
        ->insert(array(
           [
            "name" => "project_and_task_management",
            "is_active" => 1,
            "is_default" => 1,
            "business_tier_id" => 1,
           ],

           [
            "name" => "user_activity",
            "is_active" => 1,
            "is_default" => 1,
            "business_tier_id" => 1,
           ],
        ));

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modules');
    }
}
