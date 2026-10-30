<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('contact_list')) {
            Schema::create('contact_list', function (Blueprint $table) {
                $table->id();
                $table->string('department_name');
                $table->string('department_email')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('contact_list');
    }
};
