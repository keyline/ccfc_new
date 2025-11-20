<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id');
            $table->string('member_code', 50);
            $table->enum('notification_type', ['sms', 'email']);
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('message_body');
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'bounced'])->default('queued');
            $table->string('provider_id', 100)->nullable();
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index('token_id', 'idx_token_id');
            $table->index('member_code', 'idx_member_code');
            $table->index('notification_type', 'idx_notification_type');
            $table->index('status', 'idx_status');
            $table->index('scheduled_at', 'idx_scheduled_at');

            // $table->foreign('token_id')->references('id')->on('payment_tokens');
            // $table->foreign('member_code')->references('member_code')->on('members');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_log');
    }
}
