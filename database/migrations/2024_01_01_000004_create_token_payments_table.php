<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokenPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('token_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('token_id');
            $table->string('member_code', 50);
            $table->unsignedBigInteger('member_due_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->unique()->nullable();
            $table->enum('payment_status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->text('gateway_response')->nullable();
            $table->dateTime('payment_date');
            $table->timestamps();

            $table->index('payment_id', 'idx_payment_id');
            $table->index('token_id', 'idx_token_id');
            $table->index('member_code', 'idx_member_code');
            $table->index('member_due_id', 'idx_member_due');
            $table->index('payment_status', 'idx_payment_status');
            $table->index('payment_date', 'idx_payment_date');

            // Assuming a 'payments' table exists
            // $table->foreign('payment_id')->references('id')->on('payments');
            // $table->foreign('token_id')->references('id')->on('payment_tokens');
            // $table->foreign('member_code')->references('member_code')->on('members');
            // $table->foreign('member_due_id')->references('id')->on('member_dues');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('token_payments');
    }
}
