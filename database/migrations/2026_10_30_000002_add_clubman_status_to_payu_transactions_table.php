<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payu_transactions', function (Blueprint $table) {
            $table->string('clubman_status', 20)->nullable();
            $table->timestamp('clubman_checked_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('payu_transactions', function (Blueprint $table) {
            $table->dropColumn(['clubman_status', 'clubman_checked_at']);
        });
    }
};
