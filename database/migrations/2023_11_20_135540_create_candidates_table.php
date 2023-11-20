<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCandidatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("email");
            $table->string("phone");
            $table->integer("experience_years");
            $table->string("education_level");
            $table->string("cover_letter")->nullable();
            $table->date("application_date");
            $table->date("interview_date")->nullable();
            $table->text("feedback");
            $table->enum('status', ['review', 'interviewed', 'hired']);


            $table->unsignedBigInteger("job_listing_id");
            $table->foreign('job_listing_id')->references('id')->on('job_listings')->onDelete('restrict');
            $table->json('attachments')->nullable();

            $table->boolean("is_active")->default(true);
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
        Schema::dropIfExists('candidates');
    }
}
