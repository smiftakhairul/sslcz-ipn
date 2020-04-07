<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sms_log_id')->unsigned()->index();
            $table->string('sender')->nullable();
            $table->string('recipient');
            $table->text('content');
            $table->enum('status', ['processing', 'success', 'failed'])->default('processing');
            $table->integer('retry_attempts')->default(0);
            $table->integer('failed_attempts')->default(0);
            $table->timestamps();

            $table->foreign('sms_log_id')->references('id')->on('sms_logs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms');
    }
}
