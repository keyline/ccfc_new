<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payu_transactions', function (Blueprint $table) {
            $table->string('payment_source', 30)->nullable();
        });
    }

    public function down()
    {
        Schema::table('payu_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_source');
        });
    }
};
