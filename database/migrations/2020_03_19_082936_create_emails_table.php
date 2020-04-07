<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('email_log_id')->unsigned()->index();

            $table->string('type')->nullable();
            $table->string('sender');
            $table->text('recipient');
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->string('subject');
            $table->longText('content');
            $table->enum('status', ['processing', 'success', 'failed'])->default('processing');
            $table->integer('retry_attempts')->default(0);
            $table->integer('failed_attempts')->default(0);
            $table->timestamps();

            $table->foreign('email_log_id')->references('id')->on('email_logs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emails');
    }
}
