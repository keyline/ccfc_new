<?php

namespace App\Http\Controllers;

use App\Services\Juspay\JuspayService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(JuspayService $juspay)
    {
        return view('payment', [
            'config' => $juspay->config(),
        ]);
    }

    public function initiate(JuspayService $juspay)
    {
        try {
            $orderId = uniqid('order_');
            $result = $juspay->createPaymentSession($orderId, route('payment.success'));

            return view('payment-result', [
                'orderId' => $orderId,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return back()->with('payment_error', [
                'message' => method_exists($e, 'getErrorMessage') ? $e->getErrorMessage() : $e->getMessage(),
                'code' => method_exists($e, 'getErrorCode') ? $e->getErrorCode() : null,
            ]);
        }
    }

    public function success(Request $request)
    {
        return view('success', [
            'data' => $request->all(),
        ]);
    }
}
