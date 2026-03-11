<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Tzsk\Payu\Concerns\Attributes;
use Tzsk\Payu\Concerns\Customer;
use Tzsk\Payu\Concerns\Transaction;
use Tzsk\Payu\Facades\Payu;
use Tzsk\Pay\Models\PayuTransaction;
use App\Notifications\PayUEmailNotification;
use Notification;
use App\PaymentGateways\PaymentGatewayInterface;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Juspay\RequestOptions;
use Juspay\Model\OrderSession;
use Juspay\JuspayEnvironment;
use Juspay\Model\JuspayJWT;
use Juspay\Model\Order;
use Juspay\Exception\JuspayException;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\MemberDue;

use function Symfony\Component\VarDumper\Dumper\esc;

class PaymentController extends Controller
{
    //
    public function payment(Request $request)
    {

        //$user = User::where('id', '=', session('LoggedMember'))->first();
        $user = User::find(session('LoggedMember'))->first();

        if ($user) {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'paymentGatewayOptions' => 'required',
            ]);

            $customer = Customer::make()
                            ->firstName($user->name)
                            ->email($user->email)
                            ->phone($user->phone_number_1 ?? 'NA');
            // This is entirely optional custom attributes
            $attributes = Attributes::make()
                            ->udf1($user->id);


            // Associate the transaction with your invoice
            $transaction = Transaction::make()
                            ->charge($request->amount)
                            ->for($user->user_code)
                            ->with($attributes) // Only when using any custom attributes
                            ->against($user)
                            ->to($customer);
            //dd($transaction);

            return Payu::initiate($transaction)->redirect(route('member.payment.status'));
        } else {
            dd($user);
        }
    }

    public function status()
    {
        $transaction = Payu::capture();

        $status = $transaction->response;

        $user = User::find($status['udf1']);

        if (!empty($user) && $transaction->successful()) {
            $emailInfo = array(
                'greeting' => "Dear, {$user->name}",
                'body'     => "Thank you for making payment of Rs.{$status['amount']}. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
            );

            Notification::send($user, new PayUEmailNotification($emailInfo));

            if (config('auth.logout_after_payment')) {
                Auth::guard('members')->logout();
            }
        }

        return view('member.paymentstatus', compact('status'));
    }

    public function PayWithHdfc(PaymentGatewayInterface $hdfcPaymentService, Request $request)
    {
        $user = User::find(session('LoggedMember'))->first();

        if ($user) {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'paymentGatewayOptions' => 'required',
            ]);

            $data = $hdfcPaymentService->processPayment($request->amount, $user);
            return view('member.hdfcredirectform', $data);
        }
    }

    public function statusForHdfc(PaymentGatewayInterface $hdfcPaymentService, Request $request)
    {
        //respData;
        //dd($_POST);
        $paymentStatus = $request->input('respData');
        $status = $hdfcPaymentService->verifyPayment($paymentStatus);
        //dd($status);
        if (!empty($status)) {
            if (array_key_exists('error', $status)) {


            } else {
                //send payment notification to user
                $user = User::find($status['user']);
                $emailInfo = array(
                'greeting' => "Dear, {$user->name}",
                'body'     => "Thank you for making payment of Rs.{$status['amount']}. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );
                Notification::send($user, new PayUEmailNotification($emailInfo));

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }
            }
            return view('member.paymentstatusotherpgs', compact('status'));
        }
    }

    public function callback(Request $request)
    {
        // dd($request->all());
        $input = $request->all();

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        // Process the payment callback logic here
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        dd($payment);

        $amount = number_format($payment->amount / 100, 2, '.', '');

        if (count($input)  && !empty($input['razorpay_payment_id'])) {

            try {
                // Please note that the razorpay order ID must
                // come from a trusted source (session here, but
                // could be database or something else)
                $attributes = array(
                    'razorpay_order_id' => $payment->order_id,
                    'razorpay_payment_id' => $input['razorpay_payment_id'],
                    'razorpay_signature' => $input['razorpay_signature']
                );

                $api->utility->verifyPaymentSignature($attributes);

                DB::table('payu_transactions')
                    ->where('transaction_id', Session::get('axisTransactionId'))
                    ->update(
                        [
                            'response' => $payment->toArray(),
                            'status'	=> 'successful',
                            'updated_at' => Carbon::now('Asia/Kolkata'),

                        ]
                    );
                //find user
                $user = User::find($payment->notes->udf1);



                $emailInfo = array(
                    'greeting' => "Dear, {$user->name}",
                    'body'     => "Thank you for making payment of Rs.{ $amount }. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );

                Notification::send($user, new PayUEmailNotification($emailInfo));

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }

                $status = ['status' => 'success', 'transactionid' => $input['razorpay_payment_id'], 'amount' => $amount];






            } catch (SignatureVerificationError $e) {
                $status = ['status' => 'Failed', 'message' => $e->getMessage(), 'amount' => $amount];

                DB::table('payu_transactions')
                    ->where('transaction_id', Session::get('axisTransactionId'))
                    ->update(
                        [
                            'response' => $payment,
                            'status'	=> 'failed',
                            'updated_at' => Carbon::now('Asia/Kolkata'),

                        ]
                    );


            }

        }

        return view('member.paymentstatusotherpgs', compact('status'));

        //Session::put('success', 'Payment successful');
        //dd([$payment, $input]);
        //return redirect()->back();

        //return response()->json(['success' => true]);
    }

    public function checkout(Request $request)
    {
        $user = User::find(session('LoggedMember'))->first();
        if ($user) {
            $validated = $request->validate([
        'amount' => 'required|numeric|min:1',
        'paymentGatewayOptions' => 'required',
        ]);

            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            $order = $api->order->create([
                'receipt' => 'ord_axis_' . Str::random(10), // Replace with your own unique identifier for the order
                'amount' => $request->amount * 100, // Replace with the actual amount from your form or request
                'currency' => 'INR', // Replace with your desired currency
                'payment_capture' => 1,
                'notes' => [
                'udf1' => $user->id, // User Defined Field 1
                'udf2' => $user->user_code, // User Defined Field 2
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->phone_number_1,
                // Add more UDFs as needed
            ]
            ]);
            // Store the order ID or other necessary details in your database for future reference
            DB::table('payu_transactions')->insert([
            'paid_for_id' => $user->id,
            'paid_for_type' => 'App\Models\User',
            'transaction_id' => $order->id,
            'gateway'		=> 'AXIS Razor Pay',
            'body'			=> serialize($order),
            'destination'	=> route('member.axisstatus'),
            'hash'			=> '',
            'response'		=> '',
            'status'		=> 'pending',
            'created_at'	=> Carbon::now('Asia/Kolkata'),
            'updated_at'	=> Carbon::now('Asia/Kolkata'),

            ]);

            Session::put('axisTransactionId', $order->id);

            return view('member.axisredirectform', ['order' => $order]);

        }



    }

    public function razorpay(Request $request)
    {
        $user = User::find(session('LoggedMember'))->first();
        $api = new Api(env('RAZORPAY_KEY_NEW'), env('RAZORPAY_SECRET_NEW'));

        $order = $api->order->create([
            'receipt' => 'INV_' . rand(10000, 99999),
            'amount' => $request->amount,
            'currency' => 'INR',
            'payment_capture' => 1,
            'notes' => [
            'udf1' => $user->id, // User Defined Field 1
            'udf2' => $user->user_code, // User Defined Field 2
            'name' => $user->name,
            'email' => $user->email,
            // 'email'=> 'deblina@keylines.net',
            'contact' => $user->phone_number_1,
            // Add more UDFs as needed
        ]
        ]);
        // Store the order ID or other necessary details in your database for future reference
        DB::table('payu_transactions')->insert([
        'paid_for_id' => $user->id,
        'paid_for_type' => 'App\Models\User',
        'transaction_id' => $order->id,
        'gateway'		=> 'Razor Pay',
        'body'			=> serialize($order),
        'destination'	=> route('member.razorpaycallback'),
        'hash'			=> '',
        'response'		=> '',
        'status'		=> 'pending',
        'created_at'	=> Carbon::now('Asia/Kolkata'),
        'updated_at'	=> Carbon::now('Asia/Kolkata'),

        ]);



        // ✅ Store order_id in session
        Session::put('razorpayTransactionid', $order['id']);
        return response()->json(['order_id' => $order['id']]);
        // return response()->json(['order_id' => 56789]);
    }
    //     public function razorpay(Request $request)
    // {
    //     try {
    //         $amount = $request->amount;

    //         if (!$amount || $amount <= 0) {
    //             return response()->json(['error' => 'Invalid amount.'], 400);
    //         }

    //         $api = new Api(env('RAZORPAY_KEY_NEW'), env('RAZORPAY_SECRET_NEW'));

    //         $orderData = [
    //             'receipt'         => 'INV_' . rand(10000, 99999),
    //             'amount'          => $amount, // amount in paise
    //             'currency'        => 'INR',
    //             'payment_capture' => 1 // auto capture
    //         ];

    //         $razorpayOrder = $api->order->create($orderData);

    //         // You may log the order or store it in your DB if needed
    //         // Log::info('Razorpay Order Created', $razorpayOrder->toArray());

    //         return response()->json([
    //             'order_id' => $razorpayOrder['id'],
    //             'amount' => $razorpayOrder['amount'],
    //             'currency' => $razorpayOrder['currency']
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Razorpay Order Creation Failed: ' . $e->getMessage());
    //         return response()->json(['error' => 'Order creation failed. Try again later.'], 500);
    //     }
    // }
    public function razorpaycallback(Request $request)
    {
        // dd($request->all());
        $input = $request->all();

        $api = new Api(env('RAZORPAY_KEY_NEW'), env('RAZORPAY_SECRET_NEW'));

        // Process the payment callback logic here
        $payment = $api->payment->fetch($input['razorpay_payment_id']);

        $transactionId = Session::get('razorpayTransactionid');
        // echo $transactionId; die;
        // dd($payment);
        $amount = number_format($payment->amount / 100, 2, '.', '');

        if (count($input)  && !empty($input['razorpay_payment_id'])) {

            try {
                // Please note that the razorpay order ID must
                // come from a trusted source (session here, but
                // could be database or something else)
                $attributes = array(
                    'razorpay_order_id' => $payment->order_id,
                    'razorpay_payment_id' => $input['razorpay_payment_id'],
                    'razorpay_signature' => $input['razorpay_signature']
                );

                $api->utility->verifyPaymentSignature($attributes);

                DB::table('payu_transactions')
                    ->where('transaction_id', Session::get('razorpayTransactionid'))
                    ->update(
                        [
                            'response' => $payment->toArray(),
                            'status'	=> 'successful',
                            'updated_at' => Carbon::now('Asia/Kolkata'),

                        ]
                    );
                //find user
                $user = User::find($payment->notes->udf1);
                // dd($user);

                //code by deblina to update member dues on payment
                $dueDetails = MemberDue::where('member_code', $user->user_code)
                                    ->first();
                // dd($dueDetails);

                // if($dueDetails->outstanding_balance > $amount)
                if(1)
                {                        
                    DB::table('member_dues')
                        ->where('member_code', $user->user_code)
                        ->update(
                            [
                                'status' => 'paid',
                                'paid_amount' => $amount,
                                'dues_for_this_month' => $dueDetails->outstanding_balance - $amount,
                                'updated_at' => Carbon::now('Asia/Kolkata'),
                            ]
                        );
                }


                $emailInfo = array(
                    'greeting' => "Dear, {$user->name}",
                    'body'     => "Thank you for making payment of Rs.{ $amount }. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );

                Notification::send($user, new PayUEmailNotification($emailInfo));

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }

                $status = ['status' => 'success', 'transactionid' => $input['razorpay_payment_id'], 'amount' => $amount];






            } catch (SignatureVerificationError $e) {
                $status = ['status' => 'Failed', 'message' => $e->getMessage(), 'amount' => $amount];

                DB::table('payu_transactions')
                    ->where('transaction_id', Session::get('razorpayTransactionid'))
                    ->update(
                        [
                            'response' => $payment,
                            'status'	=> 'failed',
                            'updated_at' => Carbon::now('Asia/Kolkata'),

                        ]
                    );


            }

        }

        return view('member.paymentstatusotherpgs', compact('status'));

        //Session::put('success', 'Payment successful');
        //dd([$payment, $input]);
        //return redirect()->back();

        //return response()->json(['success' => true]);
    }

    // public function initiateJuspayPayment(Request $request)
    // {

    //     // $user = User::find(session('LoggedMember'))->first();
    //        $user = User::find(session('LoggedMember'));

    //     // Fallback to JSON file
    //     $configPath = storage_path('app/juspay/config.json');

    //     if (!file_exists($configPath)) {
    //         throw new Exception("Juspay configuration not found");
    //     }


    //     $config = file_get_contents($configPath);
    //     $config = json_decode($config, true);

    //     new ServerEnv($config);

    //     // block:start:read-keys-from-file
    //     $privateKey = array_key_exists("PRIVATE_KEY", $config) ? $config["PRIVATE_KEY"] : file_get_contents(storage_path($config["PRIVATE_KEY_PATH"]));
    //     $publicKey =  array_key_exists("PUBLIC_KEY", $config) ? $config["PUBLIC_KEY"] : file_get_contents(storage_path($config["PUBLIC_KEY_PATH"]));
    //     // block:end:read-keys-from-file

    //     if ($privateKey == false || $publicKey == false) {
    //         http_response_code(500);
    //         $response = $privateKey == false ? array("message" => "private key file not found") : array("message" => "public key file not found");
    //         echo json_encode($response);
    //         if ($privateKey == false) {
    //             error_log("private key file not found");
    //             throw new Exception("private key file not found");
    //         } else {
    //             error_log("public key file not found");
    //             throw new Exception("public key file not found");
    //         }
    //     }


    //     // block:start:initialize-juspay-config
    //     JuspayEnvironment::init()
    //     ->withBaseUrl("https://smartgatewayuat.hdfcbank.com")
    //     //->withBaseUrl("https://smartgateway.hdfcbank.com/")
    //     ->withMerchantId($config["MERCHANT_ID"])
    //     ->withJuspayJWT(new JuspayJWT($config["KEY_UUID"], $publicKey, $privateKey)); #Add key id
    //     // block:end:initialize-juspay-config

    //     $config = ServerEnv::$config;

    //     $inputJSON = file_get_contents('php://input');
    //     $input = json_decode($inputJSON, true);
    //     header('Content-Type: application/json');
    //     $orderId = uniqid();
    //     $amount = $input['amount'];
    //     $tokenId = $input['token_id'] ?? null;

    //     try {
    //         if (!$user) {
    //             throw new Exception("User not logged in", 1);

    //         }
    //         $params = array();
    //         $params['amount'] = $amount;
    //         $params['currency'] = "INR";
    //         $params['order_id'] = $orderId;
    //         //$params['customer_id'] = $user->id;
    //         $params["merchant_id"] = $config["MERCHANT_ID"]; # Add merchant id
    //         $params['customer_id'] = "testing-customer-one";
    //         $params['udf1'] = $user->user_code;
    //         //$params['udf2'] = $user->id;
    //         $params['payment_page_client_id'] = $config["PAYMENT_PAGE_CLIENT_ID"];
    //         $params['action'] = "paymentPage";
    //         $params['return_url'] = route('member.hdfcsmartpaycallback');
    //         $requestOption = new RequestOptions();
    //         $requestOption->withCustomerId("testing-customer-one");

    //         //$requestOption->withCustomerId($user->id);

    //         $session = OrderSession::create($params, $requestOption);
    //         if ($session->status == "NEW") {
    //             $response = array("orderId" => $session->orderId, "id" => $session->id, "status" => $session->status, "paymentLinks" =>  $session->paymentLinks, "sdkPayload" => $session->sdkPayload );

    //             // Store the order ID or other necessary details in your database for future reference
    //             $paymentId = DB::table('payu_transactions')->insertGetId([
    //             'paid_for_id' => $user->id,
    //             'paid_for_type' => 'App\Models\User',
    //             'transaction_id' => $session->orderId,
    //             'gateway'		=> 'HDFC SMART Pay',
    //             'body'			=> serialize($session->sdkPayload),
    //             'destination'	=> route('member.hdfcsmartpaycallback'),
    //             'hash'			=> '',
    //             'response'		=> '',
    //             'status'		=> 'pending',
    //             'created_at'	=> Carbon::now('Asia/Kolkata'),
    //             'updated_at'	=> Carbon::now('Asia/Kolkata'),

    //             ]);

    //             Session::put('hdfcsmartpayTransactionid', $session->orderId);

    //             Session::put('hdfcsmartpaycustomerid', $user->id);

    //             if ($tokenId) {
    //                 //mark token as used
    //                 $paymentToken = \App\Models\PaymentToken::find($tokenId);
    //                 if ($paymentToken) {
    //                     $paymentToken->markAsUsed(request()->ip(), request()->userAgent());
    //                 }

    //                 //insert into TokenPayment
    //                 $tokenPaymentId = \App\Models\TokenPayment::create([
    //                     'payment_id' => $paymentId,
    //                     'token_id' => $paymentToken->id,
    //                     'member_code' => $paymentToken->member_code,
    //                     'member_due_id' => $paymentToken->member_due_id,
    //                     'payment_method' => 'HDFC SMART Pay',
    //                     'transaction_id' => $session->orderId,
    //                     'amount' => $amount,
    //                     'payment_status' => 'pending',
    //                     'payment_date' => Carbon::now('Asia/Kolkata'),
    //                 ]);
    //             }


    //         } else {
    //             http_response_code(500);
    //             $response = array("message" => "session status: " . $session->status);
    //         }
    //     } catch (JuspayException $e) {
    //         http_response_code($e->getHttpResponseCode());
    //         $response = array("message" => $e->getErrorMessage());
    //         error_log($e->getErrorMessage());
    //     } catch (Exception $e) {
    //         http_response_code(429);
    //         $response = array("message" => $e->getMessage());
    //         error_log($e->getMessage());
    //     }
    //     \Log::info('Juspay Response:', $response);
    //     // echo json_encode($response);
    //     return response()->json($response);
    // }

    public function initiateJuspayPayment(Request $request)
    {
        try {

            /* -----------------------------
            | 1. Get Logged User
            ------------------------------*/
            $userId = session('LoggedMember');
            $user   = User::where('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not logged in'
                ], 401);
            }


            /* -----------------------------
            | 2. Read Juspay Config
            ------------------------------*/
            $configPath = storage_path('app/juspay/config.json');

            if (!file_exists($configPath)) {
                return response()->json([
                    'message' => 'Juspay configuration not found'
                ], 500);
            }

            $config = json_decode(file_get_contents($configPath), true);
            new ServerEnv($config);


            // /* -----------------------------
            // | 3. Read Keys
            // ------------------------------*/
            $privateKey = $config["PRIVATE_KEY"] 
                ?? file_get_contents(storage_path($config["PRIVATE_KEY_PATH"]));

            $publicKey = $config["PUBLIC_KEY"] 
                ?? file_get_contents(storage_path($config["PUBLIC_KEY_PATH"]));

            if (!$privateKey || !$publicKey) {
                return response()->json([
                    'message' => 'Key files missing'
                ], 500);
            }


            // /* -----------------------------
            // | 4. Initialize Juspay
            // ------------------------------*/
            // JuspayEnvironment::init()
            //     ->withBaseUrl("https://smartgatewayuat.hdfcbank.com")
            //     //->withBaseUrl("https://smartgateway.hdfcbank.com")
            //     ->withMerchantId($config["MERCHANT_ID"])
            //     ->withJuspayJWT(
            //         new JuspayJWT(
            //             $config["KEY_UUID"],
            //             $publicKey,
            //             $privateKey
            //         )
            //     );
            JuspayEnvironment::init()
                // ->withBaseUrl("https://smartgatewayuat.hdfcbank.com")
                ->withBaseUrl("https://smartgateway.hdfcbank.com")
                ->withMerchantId($config["MERCHANT_ID"])
                ->withJuspayJWT(
                    new JuspayJWT(
                        $config["KEY_UUID"],
                        $publicKey,
                        $privateKey
                    )
                );


            // /* -----------------------------
            // | 5. Read Request Data
            // ------------------------------*/
            $amount  = $request->amount;
            $tokenId = $request->token_id ?? null;
            $orderId = uniqid();


            // /* -----------------------------
            // | 6. Prepare Juspay Parameters
            // ------------------------------*/
            $params = [
                'amount'                  => $amount,
                'currency'                => 'INR',
                'order_id'                => $orderId,
                'merchant_id'             => 'SG3351',
                'customer_id'             => "testing-customer-one",
                'udf1'                    => $user->user_code,
                'payment_page_client_id'  => 'hdfcmaster',
                'action'                  => 'paymentPage',
                'return_url'              => route('member.hdfcsmartpaycallback')
            ];

            $requestOption = new RequestOptions();
            $requestOption->withCustomerId("testing-customer-one");

             
            // /* -----------------------------
            // | 7. Create Juspay Session
            // ------------------------------*/
            try {

                    $session = OrderSession::create($params, $requestOption);

                    return response()->json([
                        "success" => true,
                        "session_object" => $session
                    ]);

                    } catch (\Exception $e) {

                        return response()->json([
                            "success" => false,
                            "error_message" => $e->getMessage(),
                            "line" => $e->getLine(),
                            "file" => $e->getFile()
                        ]);
                    }
            // $session = OrderSession::create($params, $requestOption);

            // if ($session->status !== "NEW") {
            //     return response()->json([
            //         'message' => 'Session status: '.$session->status
            //     ], 500);
            // }
        //    return response()->json(['status' => $session->status, "orderId" => $session->orderId, 'paymentLinks' =>  $session->paymentLinks]);

            // /* -----------------------------
            // | 8. Store Transaction
            // ------------------------------*/
            $paymentId = DB::table('payu_transactions')->insertGetId([
                'paid_for_id'   => $user->id,
                'paid_for_type' => 'App\Models\User',
                'transaction_id'=> $session->orderId,
                'gateway'       => 'HDFC SMART Pay',
                'body'          => serialize($session->sdkPayload),
                'destination'   => route('member.hdfcsmartpaycallback'),
                'hash'          => '',
                'response'      => '',
                'status'        => 'pending',
                'created_at'    => Carbon::now('Asia/Kolkata'),
                'updated_at'    => Carbon::now('Asia/Kolkata')
            ]);


            // /* -----------------------------
            // | 9. Store Session
            // ------------------------------*/
            Session::put('hdfcsmartpayTransactionid', $session->orderId);
            Session::put('hdfcsmartpaycustomerid', $user->id);


            // /* -----------------------------
            // | 10. Token Payment Handling
            // ------------------------------*/
            if ($tokenId) {

                $paymentToken = \App\Models\PaymentToken::find($tokenId);

                if ($paymentToken) {
                    $paymentToken->markAsUsed(request()->ip(), request()->userAgent());
                }

                \App\Models\TokenPayment::create([
                    'payment_id'     => $paymentId,
                    'token_id'       => $paymentToken->id,
                    'member_code'    => $paymentToken->member_code,
                    'member_due_id'  => $paymentToken->member_due_id,
                    'payment_method' => 'HDFC SMART Pay',
                    'transaction_id' => $session->orderId,
                    'amount'         => $amount,
                    'payment_status' => 'pending',
                    'payment_date'   => Carbon::now('Asia/Kolkata')
                ]);
            }


            // /* -----------------------------
            // | 11. Return Response
            // ------------------------------*/
            $response = [
                "status"       => $session->status,
                "orderId"      => $session->orderId,
                "paymentLinks" => $session->paymentLinks
            ];

            // \Log::info("Juspay Response", $response);
            // $userId = session('LoggedMember');
            // $user   = User::where('id', $userId)->first();
            // $paymentLink  = "https://smartgatewayuat.hdfcbank.com/smartgateway/pgui/jsp/paymentrequest.jsp?orderId=12345";

            return response()->json($response);
            //  return response()->json(['user' => $user, 'status' => 'NEW', 'paymentLinks' => ['web' => $paymentLink]]);

        }catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function handleJuspayResponse()
    {


        // Fallback to JSON file
        $configPath = storage_path('app/juspay/config.json');

        if (!file_exists($configPath)) {
            throw new Exception("Juspay configuration not found");
        }


        $config = file_get_contents($configPath);
        $config = json_decode($config, true);



        new ServerEnv($config);


        // block:start:read-keys-from-file
        $privateKey = array_key_exists("PRIVATE_KEY", $config) ? $config["PRIVATE_KEY"] : file_get_contents(storage_path($config["PRIVATE_KEY_PATH"]));
        $publicKey =  array_key_exists("PUBLIC_KEY", $config) ? $config["PUBLIC_KEY"] : file_get_contents(storage_path($config["PUBLIC_KEY_PATH"]));
        // block:end:read-keys-from-file

        if ($privateKey == false || $publicKey == false) {
            http_response_code(500);
            $response = $privateKey == false ? array("message" => "private key file not found") : array("message" => "public key file not found");
            echo json_encode($response);
            if ($privateKey == false) {
                error_log("private key file not found");
                throw new Exception("private key file not found");
            } else {
                error_log("public key file not found");
                throw new Exception("public key file not found");
            }
        }


        // block:start:initialize-juspay-config
        JuspayEnvironment::init()
        ->withBaseUrl("https://smartgatewayuat.hdfcbank.com")
        //->withBaseUrl("https://smartgateway.hdfcbank.com/")
        ->withMerchantId($config["MERCHANT_ID"])
        ->withJuspayJWT(new JuspayJWT($config["KEY_UUID"], $publicKey, $privateKey)); #Add key id
        // block:end:initialize-juspay-config

        $config = ServerEnv::$config;


        if (isset($_POST["order_id"])) {
            try {

                $params = array();

                $orderId = $_POST["order_id"];

                $params ['order_id'] = $orderId;

                $order = Order::status($params, new RequestOptions(new JuspayJWT($config["KEY_UUID"], $publicKey, $privateKey)));
                if ($order == null || $order->orderId  != $orderId) {
                    throw new Exception("Order not found", 1);
                }

                //$order = $this->JpgetOrder($orderId, $config);
                $response = $this->JporderStatusMessage($order);

                //Build array and store in database
                $myOrderData = [
                    'orderId'       => $order->orderId,
                    'merchantId'    => $order->merchantId,
                    'txnId'         => $order->txnId ?? null,
                    'amount'        => $order->amount ,
                    'txn_amount'    => $order->amount ,
                    'customerId'    => $order->customerId ?? '',
                    'customerEmail' => $order->customerEmail ?? '',
                    'returnUrl'     => $order->returnUrl,
                    'udf1'          => $order->udf1 ?? '',
                    'udf2'          => $order->udf2 ?? '',
                    'statusId'      => $order->statusId,
                    'status'        => $order->status,
                    'bankErrorCode' => $order->bankErrorCode ?? '',
                    'bankErrorMessage' => $order->bankErrorMessage ?? '',
                    'paymentMethodType' => $order->paymentMethodType ?? '',
                    'paymentMethod'     => $order->paymentMethod
                ];

                //Update DB Layer
                $payment = DB::table('payu_transactions')
                                    ->where('transaction_id', $order->orderId)->first();

                $requestLoad = @unserialize($payment->body ?? '');

                if (!$payment) {
                    throw new Exception("Payment record not found", 1);
                }



                if (is_array($requestLoad)) {
                    $amountFromPayload = $requestLoad['payload']['amount'] ?? null;

                    // Compare with your expected DB amount
                    if ((float)$order->amount !== (float)$amountFromPayload) {
                        Log::error("Amount mismatch for {$orderId}: expected {$order->amount}, got {$amountFromPayload}");
                        throw new Exception("Amount mismatch", 1);
                        //return response('Amount Mismatch', 400);
                    }
                }


                DB::table('payu_transactions')
                ->where('transaction_id', $order->orderId)
                ->update(
                    [
                        'response' => $myOrderData,
                        'status'	=> $response['order_status'] === "CHARGED"
                                        ? 'successful'
                                        : (
                                            $response['order_status'] == 'PENDING' || $response['order_status'] == 'PENDING_VBV'
                                            ? 'pending'
                                            : 'failed'
                                        ),
                        'updated_at' => Carbon::now('Asia/Kolkata'),

                    ]
                );


                $tokenPayment = \App\Models\TokenPayment::where('transaction_id', $order->orderId)->firstOrFail();

                if ($tokenPayment) {

                    \App\Models\TokenPayment::where('transaction_id', $order->orderId)
                                        ->update(
                                            [
                                                'payment_status'	=> $response['order_status'] === "CHARGED"
                                                                ? 'successful'
                                                                : (
                                                                    $response['order_status'] == 'PENDING' || $response['order_status'] == 'PENDING_VBV'
                                                                    ? 'pending'
                                                                    : 'failed'
                                                                ),
                                                'payment_date' => Carbon::now('Asia/Kolkata'),
                                                'gateway_response' => $myOrderData,

                                            ]
                                        );

                    if ($response['order_status'] === "CHARGED") {
                        \App\Models\MemberDue::processPayment($tokenPayment->member_due_id, $tokenPayment->amount);
                    }


                }


                //find user
                $user = User::find(session::get('hdfcsmartpaycustomerid'));



                $emailInfo = array(
                    'greeting' => "Dear, {$user->name}",
                    'body'     => "Thank you for making payment of Rs.{ $order->amount }. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );

                Notification::send($user, new PayUEmailNotification($emailInfo));

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }

                $status = [
                            'status' =>  $response['order_status'] === "CHARGED" ? 'success' : 'failed',
                            'transactionid' => $response['order_id'],
                            'amount' => $order->amount ?? 0,
                            'message' => $response['message']
                        ];


                Session::forget(['hdfcsmartpayTransactionid', 'hdfcsmartpaycustomerid']);


                return view('member.paymentstatusotherpgs', compact('status'));





            } catch (JuspayException $ex) {
                http_response_code(500);
                //$response = array("message" => $e->getErrorMessage());
                error_log($ex->getMessage());
            } catch (Exception $ex) {
                http_response_code(429);
                //$response_1 = array("message" => $ex->getMessage());
                error_log($ex->getMessage());
            }
        } else {
            http_response_code(400);
            $response = array('message' => 'order id not found');
        }


        //header('Content-Type: application/json');
        //echo json_encode($response);
        //convert data to generalize form


        http_response_code(400);

        $status = [
            'status' =>  $response['order_status'],
            'transactionid' => $response['order_id'] ?? '',
            'amount' => $order->amount ?? 0,
            'message' => $ex->getMessage() ?? ''
        ];

        Session::forget(['hdfcsmartpayTransactionid', 'hdfcsmartpaycustomerid']);


        return view('member.paymentstatusotherpgs', compact('status'));





    }


    private function JpgetOrder($orderId, $config)
    {
        try {
            $params = array();
            $params ['order_id'] = $orderId;
            $requestOption = new RequestOptions();

            $requestOption->withCustomerId("testing-customer-one");

            //$requestOption->withCustomerId(session::get('hdfcsmartpaycustomerid'));
            return Order::status($params, $requestOption);
        } catch (JuspayException $e) {
            http_response_code($e->getHttpResponseCode());
            $response = array("message" => $e->getErrorMessage());
            error_log($e->getErrorMessage());
            echo json_encode($response);
            throw new Exception($e->getErrorMessage());
        }
    }

    private function JporderStatusMessage($order)
    {
        $response = array("order_id" => $order->orderId);
        switch ($order->status) {
            case "CHARGED":
                $response += ["message" => "order payment done successfully"];
                break;
            case "PENDING":
            case "PENDING_VBV":
                $response += ["message" => "order payment pending"];
                break;
            case "AUTHENTICATION_FAILED":
                $response += ["message" => "authentication failed"];
                break;
            case "AUTHORIZATION_FAILED":
                $response += ["message" => "order payment authorization failed"];
                break;
            default:
                $response += ["message" => "order status " . $order->status];
        }
        $response += ["order_status" => $order->status];
        return $response;
    }

}


class ServerEnv
{
    public function __construct($config)
    {
        self::$config = $config;
    }
    public static $config;
}
