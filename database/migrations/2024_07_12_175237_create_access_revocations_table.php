<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccessRevocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_revocations', function (Blueprint $table) {
            $table->id();


            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('email_access_revoked');
            $table->date('system_access_revoked_date')->nullable();



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
        Schema::dropIfExists('access_revocations');
    }
}
