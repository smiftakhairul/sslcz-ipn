<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sms_id')->unsigned()->index();;
            $table->longText('request')->nullable();
            $table->longText('response')->nullable();
            $table->timestamps();

            $table->foreign('sms_id')->references('id')->on('sms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropForeign(['sms_id']);
        });

        Schema::dropIfExists('sms_logs');
    }
}
