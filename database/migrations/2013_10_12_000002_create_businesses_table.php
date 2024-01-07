<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("about")->nullable();
            $table->string("web_page")->nullable();
            $table->string("phone")->nullable();
            $table->string("email")->nullable()->unique();
            $table->text("additional_information")->nullable();
            $table->string("address_line_1")->nullable();
            $table->string("address_line_2")->nullable();
            $table->string("lat")->nullable();
            $table->string("long")->nullable();
            $table->string("country");
            $table->string("city");
            $table->string("postcode")->nullable();
            $table->string("currency")->nullable();


            $table->string("logo")->nullable();
            $table->string("image")->nullable();
            $table->string("background_image")->nullable();


            $table->unsignedBigInteger("business_tier_id")->default(1);
            $table->foreign('business_tier_id')->references('id')->on('business_tiers')->onDelete('restrict');



            $table->string('status')->default("pending");
            // $table->enum('status', ['status1', 'status2',  'status3'])->default("status1");
            $table->boolean("is_active")->default(false);
            $table->unsignedBigInteger("owner_id");
            $table->unsignedBigInteger("created_by");
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
        Schema::dropIfExists('businesses');
    }
}
