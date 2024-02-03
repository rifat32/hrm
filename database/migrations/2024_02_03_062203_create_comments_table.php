<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('comment_text');
            $table->text('attachments')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');
            $table->string('visibility')->default('public');
            $table->text('mentions')->nullable();
            $table->text('tags')->nullable();
            $table->text('resolution')->nullable();
            $table->json('feedback')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('history')->nullable();

            $table->unsignedBigInteger('related_task_id')->nullable();
            $table->foreign('related_task_id')->references('id')->on('tasks');
            $table->unsignedBigInteger('task_id');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            // $table->foreign('user_id')->references('id')->on('users');

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
        Schema::dropIfExists('comments');
    }
}
