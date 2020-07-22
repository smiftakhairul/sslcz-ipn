<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\NotifyType;

class CreatePushNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('notify_log_id')->unsigned()->index();
            $table->string('sender')->nullable();
            $table->string('title')->nullable();
            $table->enum('notify_type', [NotifyType::$_SINGLE, NotifyType::$_MULTIPLE])->default(NotifyType::$_SINGLE);
            $table->longText('recipient');
            $table->text('content');
            $table->enum('status', ['processing', 'success', 'failed'])->default('processing');
            $table->integer('retry_attempts')->default(0);
            $table->integer('failed_attempts')->default(0);
            $table->timestamps();

            $table->foreign('notify_log_id')->references('id')->on('push_notification_logs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('push_notifications');
    }
}
