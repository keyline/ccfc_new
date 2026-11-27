<?php

namespace App\Http\Controllers;

use App\Services\PayuTransactionSync;
use Illuminate\Http\Request;

class PayuWebhookController extends Controller
{
    public function __invoke(Request $request, PayuTransactionSync $sync)
    {
        $sync->receive($request->all());

        return response()->json(['received' => true]);
    }
}
