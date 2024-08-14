<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeFeedbackNullableInCandidates extends Migration
{

   public function up()
   {
       Schema::table('candidates', function (Blueprint $table) {
           $table->text('feedback')->nullable()->change();
       });
   }

   /**
    * Reverse the migrations.
    *
    * @return void
    */
   public function down()
   {
       Schema::table('candidates', function (Blueprint $table) {
           $table->text('feedback')->nullable(false)->change();
       });
   }
}
