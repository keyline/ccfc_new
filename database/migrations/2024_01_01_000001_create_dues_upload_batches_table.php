<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDuesUploadBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dues_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 50)->unique();
            $table->tinyInteger('month_no');
            $table->string('month_name', 20);
            $table->smallInteger('year');
            $table->dateTime('upload_date');
            $table->unsignedBigInteger('uploaded_by');
            $table->integer('total_records')->default(0);
            $table->string('file_name')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->timestamps();

            $table->index(['month_no', 'year'], 'idx_month_year');
            $table->index('upload_date', 'idx_upload_date');

            // Assuming admin_users table exists and has an id column.
            // If not, this foreign key needs to be adjusted.
            // $table->foreign('uploaded_by')->references('id')->on('admin_users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dues_upload_batches');
    }
}
