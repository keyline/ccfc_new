<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('member_code', 50);
            $table->unsignedBigInteger('member_due_id');
            $table->dateTime('generated_at');
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->enum('status', ['active', 'used', 'expired', 'revoked'])->default('active');
            $table->boolean('sms_sent')->default(false);
            $table->dateTime('sms_sent_at')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->dateTime('email_sent_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->dateTime('last_accessed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('token', 'idx_token');
            $table->index('member_code', 'idx_member_code');
            $table->index('status', 'idx_status');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('member_due_id', 'idx_member_due');

            // $table->foreign('member_code')->references('member_code')->on('members');
            // $table->foreign('member_due_id')->references('id')->on('member_dues')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_tokens');
    }
}
