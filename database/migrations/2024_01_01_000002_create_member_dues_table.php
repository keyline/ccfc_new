<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberDuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_dues', function (Blueprint $table) {
            $table->id();
            $table->string('member_code', 50);
            $table->string('upload_batch_id', 50);
            $table->decimal('outstanding_balance', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('dues_for_this_month', 10, 2)->storedAs('outstanding_balance - paid_amount');
            $table->enum('status', ['pending', 'partial', 'paid', 'error', 'cancelled'])->default('pending');
            $table->tinyInteger('month_no');
            $table->string('month_name', 20);
            $table->smallInteger('year');
            $table->timestamps();

            $table->unique(['member_code', 'upload_batch_id'], 'unique_member_month');
            $table->index('member_code', 'idx_member_code');
            $table->index('status', 'idx_status');
            $table->index(['month_no', 'year'], 'idx_month_year');
            $table->index('upload_batch_id', 'idx_batch');

            // Assuming members table exists and has a member_code column.
            // $table->foreign('member_code')->references('member_code')->on('members')->onDelete('cascade');
            // $table->foreign('upload_batch_id')->references('batch_id')->on('dues_upload_batches');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_dues');
    }
}
