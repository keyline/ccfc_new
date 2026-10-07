<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobilePayuClubmanPostingsTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_payu_clubman_postings', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('member_code');
            $table->decimal('amount', 14, 2);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_payu_clubman_postings');
    }
}
