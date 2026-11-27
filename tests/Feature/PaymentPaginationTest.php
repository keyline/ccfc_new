<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PaymentsController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentPaginationTest extends TestCase
{
    public function test_payments_are_paginated_newest_first_with_member_details(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Gate::shouldReceive('denies')->with('payment_access')->twice()->andReturn(false);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_code');
            $table->softDeletes();
        });
        Schema::create('payu_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paid_for_id');
            $table->string('paid_for_type');
            $table->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Member', 'user_code' => 'M1']);
        for ($id = 1; $id <= 26; $id++) {
            DB::table('payu_transactions')->insert([
                'id' => $id, 'paid_for_id' => 1, 'paid_for_type' => User::class,
            ]);
        }
        DB::table('payu_transactions')->insert([
            'id' => 27, 'paid_for_id' => 999, 'paid_for_type' => User::class,
        ]);

        $controller = new PaymentsController();
        $payments = $controller->index()->getData()['payments'];
        $this->assertSame(26, $payments->total());
        $this->assertCount(25, $payments);
        $this->assertSame(26, $payments->first()->id);
        $this->assertSame(2, $payments->last()->id);
        $this->assertSame('Member', $payments->first()->member_name);
        $this->assertSame('M1', $payments->first()->member_code);

        $this->app['request']->query->set('page', 2);
        $nextPage = $controller->index()->getData()['payments'];
        $this->assertCount(1, $nextPage);
        $this->assertSame(1, $nextPage->first()->id);
    }
}
