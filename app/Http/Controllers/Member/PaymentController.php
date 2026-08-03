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
use App\Services\Juspay\JuspayService;
use App\Services\ClubmanMemberLookup;
use Illuminate\Validation\ValidationException;

use function Symfony\Component\VarDumper\Dumper\esc;

class PaymentController extends Controller
{
    private function renderPaymentStatus(Request $request, array $status, string $defaultView = 'member.paymentstatusotherpgs')
    {
        if ($request->session()->pull('quickaccess.payment_in_progress')) {
            return view('member.quick_access.payment_success', compact('status'));
        }

        return view($defaultView, compact('status'));
    }

    //
    public function payment(Request $request, ClubmanMemberLookup $clubmanMemberLookup)
    {

        //$user = User::where('id', '=', session('LoggedMember'))->first();
        $user = $this->paymentUser();

        if ($user) {
            $amount = $this->validatedPaymentAmount($request, $user, $clubmanMemberLookup, false, true);

            $customer = Customer::make()
                            ->firstName($user->name)
                            ->email($user->email)
                            ->phone($user->phone_number_1 ?? 'NA');
            // This is entirely optional custom attributes
            $attributes = Attributes::make()
                            ->udf1($user->id);


            // Associate the transaction with your invoice
            $transaction = Transaction::make()
                            ->charge($amount)
                            ->for($user->user_code)
                            ->with($attributes) // Only when using any custom attributes
                            ->against($user)
                            ->to($customer);
            //dd($transaction);

            return Payu::initiate($transaction)->redirect(route('member.payment.status'));
        } else {
            abort(401);
        }
    }

    public function status(Request $request)
    {
        $transaction = Payu::capture();

        $status = $transaction->response;

        $user = User::find($status['udf1']);

        if (!empty($user) && $transaction->successful()) {
            $clubmanPostingFailed = false;

            try {
                app(\App\Services\ClubmanPaymentPosting::class)->post(
                    $user->user_code,
                    $status['mihpayid'],
                    (float) $status['amount'],
                    $status['mihpayid']
                );
            } catch (\Throwable $e) {
                $clubmanPostingFailed = true;
                Log::error('Clubman Payment Posting Failed (PayU): ' . $e->getMessage());
            }

            $status['clubman_posting_failed'] = $clubmanPostingFailed;

            $emailInfo = array(
                'greeting' => "Dear, {$user->name}",
                'body'     => "Thank you for making payment of Rs.{$status['amount']}. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
            );

            Notification::send($user, new PayUEmailNotification($emailInfo));

            if (config('auth.logout_after_payment')) {
                Auth::guard('members')->logout();
            }
        }

        return $this->renderPaymentStatus($request, $status, 'member.paymentstatus');
    }

    public function PayWithHdfc(
        PaymentGatewayInterface $hdfcPaymentService,
        Request $request,
        ClubmanMemberLookup $clubmanMemberLookup
    )
    {
        $user = $this->paymentUser();

        if ($user) {
            $amount = $this->validatedPaymentAmount($request, $user, $clubmanMemberLookup, false, true);

            $data = $hdfcPaymentService->processPayment($amount, $user);
            return view('member.hdfcredirectform', $data);
        }

        abort(401);
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

                $dueDetails = MemberDue::where('member_code', $user->user_code)
                                    ->first();

                if ($dueDetails) {
                    DB::table('member_dues')
                        ->where('member_code', $user->user_code)
                        ->update(
                            [
                                'status' => 'paid',
                                'paid_amount' => $status['amount'],
                                'dues_for_this_month' => $dueDetails->outstanding_balance - $status['amount'],
                                'updated_at' => Carbon::now('Asia/Kolkata'),
                            ]
                        );
                }

                $clubmanPostingFailed = false;

                try {
                    app(\App\Services\ClubmanPaymentPosting::class)->post(
                        $user->user_code,
                        $status['transactionid'] ?? $status['mihpayid'] ?? (string) $status['user'],
                        (float) $status['amount'],
                        $status['transactionid'] ?? $status['mihpayid'] ?? (string) $status['user']
                    );
                } catch (\Throwable $e) {
                    $clubmanPostingFailed = true;
                    Log::error('Clubman Payment Posting Failed (HDFC statusForHdfc): ' . $e->getMessage());
                }

                $status['clubman_posting_failed'] = $clubmanPostingFailed;

                $emailInfo = array(
                'greeting' => "Dear, {$user->name}",
                'body'     => "Thank you for making payment of Rs.{$status['amount']}. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );
                Notification::send($user, new PayUEmailNotification($emailInfo));

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }
            }
            return $this->renderPaymentStatus($request, $status);
        }
    }

    public function callback(Request $request)
    {
        // dd($request->all());
        $input = $request->all();

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        // Process the payment callback logic here
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
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

                $dueDetails = MemberDue::where('member_code', $user->user_code)
                                    ->first();

                if ($dueDetails) {
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

                $clubmanPostingFailed = false;

                try {
                    app(\App\Services\ClubmanPaymentPosting::class)->post(
                        $user->user_code,
                        $input['razorpay_payment_id'],
                        (float) $amount,
                        $input['razorpay_payment_id']
                    );
                } catch (\Throwable $e) {
                    $clubmanPostingFailed = true;
                    Log::error('Clubman Payment Posting Failed (Razorpay legacy callback): ' . $e->getMessage());
                }

                $emailInfo = array(
                    'greeting' => "Dear, {$user->name}",
                    'body'     => "Thank you for making payment of Rs.{ $amount }. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );

                Notification::send($user, new PayUEmailNotification($emailInfo));

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }

                $status = [
                    'status' => 'success',
                    'transactionid' => $input['razorpay_payment_id'],
                    'amount' => $amount,
                    'clubman_posting_failed' => $clubmanPostingFailed,
                ];






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

        return $this->renderPaymentStatus($request, $status);

        //Session::put('success', 'Payment successful');
        //dd([$payment, $input]);
        //return redirect()->back();

        //return response()->json(['success' => true]);
    }

    public function checkout(Request $request, ClubmanMemberLookup $clubmanMemberLookup)
    {
        $user = $this->paymentUser();
        if ($user) {
            $amount = $this->validatedPaymentAmount($request, $user, $clubmanMemberLookup, false, true);

            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            $order = $api->order->create([
                'receipt' => 'ord_axis_' . Str::random(10), // Replace with your own unique identifier for the order
                'amount' => (int) round($amount * 100), // Replace with the actual amount from your form or request
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

        abort(401);
    }

    public function razorpay(Request $request, ClubmanMemberLookup $clubmanMemberLookup)
    {
        $user = $this->paymentUser();
        abort_unless($user, 401);

        $amountInPaise = $this->validatedPaymentAmount(
            $request,
            $user,
            $clubmanMemberLookup,
            true
        );
        $api = new Api(env('RAZORPAY_KEY_NEW'), env('RAZORPAY_SECRET_NEW'));

        $order = $api->order->create([
            'receipt' => 'INV_' . rand(10000, 99999),
            'amount' => (int) $amountInPaise,
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
    // public function razorpay(Request $request)
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
                if($dueDetails)
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

                

                $clubmanPostingFailed = false;

                try {
                    $clubmanResponse = app(\App\Services\ClubmanPaymentPosting::class)->post(
                        $user->user_code,
                        $input['razorpay_payment_id'],
                        (float) $amount,
                        $input['razorpay_payment_id']
                    );
                    // dd(['input' => $input, 'payment' => $payment->toArray(), 'amount' => $amount, 'clubmanResponse' => $clubmanResponse]);
                } catch (\Throwable $e) {
                    $clubmanPostingFailed = true;
                    Log::error('Clubman Payment Posting Failed: ' . $e->getMessage());
                    // dd(['input' => $input, 'payment' => $payment->toArray(), 'amount' => $amount, 'error' => $e->getMessage()]);
                }

                $emailInfo = array(
                    'greeting' => "Dear, {$user->name}",
                    'body'     => "Thank you for making payment of Rs.{ $amount }. Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );

                Notification::send($user, new PayUEmailNotification($emailInfo));

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }

                $status = [
                    'status' => 'success',
                    'transactionid' => $input['razorpay_payment_id'],
                    'amount' => $amount,
                    'clubman_posting_failed' => $clubmanPostingFailed,
                ];






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

        return $this->renderPaymentStatus($request, $status);

        //Session::put('success', 'Payment successful');
        //dd([$payment, $input]);
        //return redirect()->back();

        //return response()->json(['success' => true]);
    }

    // public function initiateJuspayPayment(Request $request)
    // {

    //     // $user = User::find(session('LoggedMember'))->first();
    //     $session_user = session('LoggedMember');
    //     $user = User::where('id', $session_user)->first();
    
    //     // Fallback to JSON file
    //     $configPath = storage_path('app/juspay/config.json');
    //     $keyPath = storage_path('app/juspay/');

    //     if (!file_exists($configPath)) {
    //         throw new Exception("Juspay configuration not found");
    //     }


    //     $config = file_get_contents($configPath);
    //     $config = json_decode($config, true);

    //     new ServerEnv($config);

    //     // block:start:read-keys-from-file
    //     // $privateKey = array_key_exists("PRIVATE_KEY", $config) ? $config["PRIVATE_KEY"] : file_get_contents(storage_path('app/juspay/' . $config["PRIVATE_KEY_PATH"]));
    //     // $privateKey = array_key_exists("PRIVATE_KEY", $config) ? file_get_contents('/home/507708.cloudwaysapps.com/mcbnwefrun/public_html/storage/app/juspay/' . $config["PRIVATE_KEY"]) : file_get_contents('/home/507708.cloudwaysapps.com/mcbnwefrun/public_html/storage/app/juspay/' . $config["PRIVATE_KEY_PATH"]);
    //     // $publicKey =  array_key_exists("PUBLIC_KEY_PATH", $config) ? file_get_contents('/home/507708.cloudwaysapps.com/mcbnwefrun/public_html/storage/app/juspay/' . $config["PUBLIC_KEY_PATH"]) : file_get_contents('/home/507708.cloudwaysapps.com/mcbnwefrun/public_html/storage/app/juspay/' . $config["PUBLIC_KEY_PATH"]);
    //     // // block:end:read-keys-from-file

        
    //     $privateKey = array_key_exists("PRIVATE_KEY", $config) ? $config["PRIVATE_KEY"] : file_get_contents($keyPath . $config["PRIVATE_KEY_PATH"]);
    //     $publicKey =  array_key_exists("PUBLIC_KEY", $config) ? $config["PUBLIC_KEY"] : file_get_contents($keyPath . $config["PUBLIC_KEY_PATH"]);

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
    //     // ->withBaseUrl("https://smartgateway.hdfc.bank.in")
    //     ->withBaseUrl("https://smartgateway.hdfcuat.bank.in")
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
    //         // $params = array();
    //         // $params['amount'] = $amount;
    //         // $params['currency'] = "INR";
    //         // $params['order_id'] = $orderId;
    //         // $params['customer_id'] = $user->id;
    //         // $params["merchant_id"] = $config["MERCHANT_ID"]; # Add merchant id
    //         // $params['customer_id'] = "testing-customer-one";
    //         // $params['udf1'] = $user->user_code;
    //         // $params['udf2'] = $user->id;
    //         // $params['payment_page_client_id'] = $config["PAYMENT_PAGE_CLIENT_ID"];
    //         // $params['action'] = "paymentPage";
    //         // $params['return_url'] = route('member.hdfcsmartpaycallback');
    //         // $requestOption = new RequestOptions();
    //         // $requestOption->withCustomerId("testing-customer-one");

    //             $params = [];

    //             $params['amount'] = $amount;
    //             $params['currency'] = "INR";
    //             $params['order_id'] = $orderId;
    //             $params['customer_id'] = $user->id;
    //             // $params['merchant_id'] = $config["MERCHANT_ID"];
    //             $params['merchant_id'] = 'SG3351';
    //             $params['customer_email'] = $user->email ?? 'test@test.com';
    //             $params['customer_phone'] = $user->phone_number_1 ?? '9999999999';
    //             $params['first_name'] = 'Somnath';
    //             $params['last_name'] = 'Shil';
    //             $params['udf1'] = $user->user_code;
    //             $params['udf2'] = $user->id;
    //             // $params['payment_page_client_id'] = $config["PAYMENT_PAGE_CLIENT_ID"];
    //             $params['payment_page_client_id'] = 'hdfcmaster';
    //             $params['action'] = "paymentPage";
    //             $params['return_url'] = route('member.hdfcsmartpaycallback');

                
    //             $requestOption = new RequestOptions();
    //             $requestOption->withCustomerId($user->id);
    //             //$requestOption->withCustomerId($user->id);

    //             // echo '<pre>';print_r($params);
    //             // echo '<pre>';print_r($requestOption);die;
    //             $session = OrderSession::create($params, $requestOption);
    //             // echo '<pre>';print_r($session);die;
            
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
    //         // http_response_code($e->getHttpResponseCode());
    //         // $response = array("message" => $e->getErrorMessage());
    //         // error_log($e->getErrorMessage());
    //             return response()->json([
    //                         'error' => true,
    //                         'message' => $e->getMessage(),
    //                         'error_message' => $e->getErrorMessage(),
    //                         'code' => $e->getCode(),
    //                         'line' => $e->getLine(),
    //                         'file' => $e->getFile(),
    //                         'trace' => $e->getTraceAsString()
    //                     ]);
    //     } catch (Exception $e) {
    //         http_response_code(429);
    //         $response = array("message" => $e->getMessage());
    //         error_log($e->getMessage());
    //     }
    //     \Log::info('Juspay Response:', $response);
    //     // echo json_encode($response);
    //     return response()->json($response);
    // }

    public function initiateJuspayPayment(
        Request $request,
        JuspayService $juspay,
        ClubmanMemberLookup $clubmanMemberLookup
    )
    {
        $user = $this->paymentUser();

        if (! $user) {
            return response()->json([
                'status' => false,
                'error' => 'User not logged in'
            ], 401);
        }

        $amount = $this->validatedPaymentAmount($request, $user, $clubmanMemberLookup);

        try {
            // changed: restore the old pre-callback bookkeeping before returning the AJAX payload.
            $tokenId = $request->token_id ?? null;
            $memberCode = $user->user_code;
            $orderId = uniqid('order_');
            $result = $juspay->createPaymentSession($orderId, route('member.hdfcsmartpaycallback'), $amount, ['member_code' => $user->user_code, 'user_id' => $user->id, 'member_name' => $user->name, 'customer_email' => $user->email, 'customer_phone' => $user->phone_number_1]);
            $payloadToStore = $result['sdk_payload'] ?? [];
            $payloadToStore['settlement_context'] = [
                'token_id' => $tokenId,
                'member_code' => $memberCode,
            ];

            // changed: keep storing the transaction row used later by the callback status-update flow.
            $paymentId = DB::table('payu_transactions')->insertGetId([
                'paid_for_id' => $user->id,
                'paid_for_type' => 'App\Models\User',
                'transaction_id' => $orderId,
                'gateway' => 'HDFC SMART Pay',
                'body' => serialize($payloadToStore),
                'destination' => route('member.hdfcsmartpaycallback'),
                'hash' => '',
                'response' => '',
                'status' => 'pending',
                'created_at' => Carbon::now('Asia/Kolkata'),
                'updated_at' => Carbon::now('Asia/Kolkata'),
            ]);

            // changed: preserve session keys expected by handleJuspayResponse().
            Session::put('hdfcsmartpayTransactionid', $orderId);
            Session::put('hdfcsmartpaycustomerid', $user->id);

            // changed: restore token-payment linkage so callback can settle the correct due later.
            if ($tokenId) {
                $paymentToken = \App\Models\PaymentToken::find($tokenId);

                if ($paymentToken) {
                    $paymentToken->markAsUsed(request()->ip(), request()->userAgent());

                    \App\Models\TokenPayment::create([
                        'payment_id' => $paymentId,
                        'token_id' => $paymentToken->id,
                        'member_code' => $paymentToken->member_code,
                        'member_due_id' => $paymentToken->member_due_id,
                        'payment_method' => 'HDFC SMART Pay',
                        'transaction_id' => $orderId,
                        'amount' => $amount,
                        'payment_status' => 'pending',
                        'payment_date' => Carbon::now('Asia/Kolkata'),
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'data' => $result
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleJuspayResponse(JuspayService $juspay, Request $request)
    {
        // changed: callback now uses the same Juspay config/key resolution as initiateJuspayPayment().
        $config = $juspay->config();
        $jwt = $juspay->jwt();
        $juspay->initialize();


        if (isset($_POST["order_id"])) {
            try {

                $params = array();

                $orderId = $_POST["order_id"];

                $params ['order_id'] = $orderId;

                // changed: reuse the shared JWT instead of reading config.json paths directly in the callback.
                $order = Order::status($params, new RequestOptions($jwt));
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
                    'udf3'          => $order->udf3 ?? '',
                    'udf4'          => $order->udf4 ?? '',
                    'udf5'          => $order->udf5 ?? '',
                    'udf6'          => $order->udf6 ?? '',
                    'udf7'          => $order->udf7 ?? '',
                    'udf8'          => $order->udf8 ?? '',
                    'udf9'          => $order->udf9 ?? '',
                    'udf10'         => $order->udf10 ?? '',
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


                $tokenPayment = \App\Models\TokenPayment::where('transaction_id', $order->orderId)->first();

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
                Log::info('HDFC MAIL DEBUG [1] — user lookup', [
                    'hdfcsmartpaycustomerid' => session::get('hdfcsmartpaycustomerid'),
                ]);
                $user = User::find(session::get('hdfcsmartpaycustomerid'));
                Log::info('HDFC MAIL DEBUG [2] — user found', [
                    'user_found' => !empty($user),
                    'user_id'    => $user->id ?? null,
                    'user_name'  => $user->name ?? null,
                    'user_email' => $user->email ?? 'NO EMAIL',
                    'user_code'  => $user->user_code ?? null,
                ]);

                $amount = $order->amount ?? 0;

                $currentMonth = Carbon::now()->month;
                $currentYear  = Carbon::now()->year;
                //code by deblina to update member dues on payment
                Log::info('HDFC MAIL DEBUG [3] — looking up member due', [
                    'member_code' => $user->user_code ?? null,
                    'month_no'    => $currentMonth,
                    'year'        => $currentYear,
                ]);
                $dueDetails = MemberDue::where('member_code', $user->user_code)
                                        ->where('month_no', $currentMonth)
                                        ->where('year', $currentYear)
                                    ->first();
                Log::info('HDFC MAIL DEBUG [4] — dueDetails result', [
                    'due_found'           => !empty($dueDetails),
                    'outstanding_balance' => $dueDetails->outstanding_balance ?? null,
                    'amount'              => $amount,
                ]);
                // dd($dueDetails);


                if($dueDetails)
                {
                    Log::info('HDFC MAIL DEBUG [5] — updating member_dues');
                    DB::table('member_dues')
                        ->where('member_code', $user->user_code)
                        ->where('month_no', $currentMonth)
                        ->where('year', $currentYear)
                        ->update(
                            [
                                'status' => 'paid',
                                'paid_amount' => $amount,
                                'dues_for_this_month' => $dueDetails->outstanding_balance - $amount,
                                'updated_at' => Carbon::now('Asia/Kolkata'),
                            ]
                        );
                    Log::info('HDFC MAIL DEBUG [6] — member_dues updated successfully');
                }

                $clubmanPostingFailed = false;

                if ($response['order_status'] === "CHARGED") {
                    try {
                        app(\App\Services\ClubmanPaymentPosting::class)->post(
                            $user->user_code,
                            $response['order_id'],
                            (float) $amount,
                            $response['order_id']
                        );
                    } catch (\Throwable $e) {
                        $clubmanPostingFailed = true;
                        Log::error('Clubman Payment Posting Failed (HDFC): ' . $e->getMessage());
                    }
                }

                $emailInfo = array(
                    'greeting' => "Dear, {$user->name}",
                    'body'     => "Thank you for making payment of Rs.$order->amount . Please note that payment is subject to realization and will reflect in your account in the next 24 working hours."
                );

                Log::info('HDFC MAIL DEBUG [7] — attempting to send mail', [
                    'to_email' => $user->email ?? 'NO EMAIL',
                ]);
                try {
                    Notification::send($user, new PayUEmailNotification($emailInfo));
                    Log::info('HDFC MAIL DEBUG [8] — mail sent successfully');
                } catch (\Exception $mailEx) {
                    Log::error('HDFC MAIL DEBUG [8] — mail FAILED', [
                        'error' => $mailEx->getMessage(),
                    ]);
                }

                if (config('auth.logout_after_payment')) {
                    Auth::guard('members')->logout();
                }

                $showstatus = '';
                if($response['order_status'] == "CHARGED"){
                     $showstatus = 'success';
                }else{
                    $showstatus = 'failed';
                 }

                $status = [
                            'status' =>  $showstatus,
                            'transactionid' => $response['order_id'],
                            'amount' => $order->amount ?? 0,
                            'message' => $response['message'],
                            'clubman_posting_failed' => $clubmanPostingFailed,
                        ];


                Session::forget(['hdfcsmartpayTransactionid', 'hdfcsmartpaycustomerid']);


                return $this->renderPaymentStatus($request, $status);





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
            'status' =>  isset($response['order_status']) && $response['order_status'] === 'CHARGED' ? 'success' : ($response['order_status'] ?? 'failed'),
            'transactionid' => $response['order_id'] ?? '',
            'amount' => $order->amount ?? 0,
            'message' => isset($ex) ? $ex->getMessage() : ''
        ];

        Session::forget(['hdfcsmartpayTransactionid', 'hdfcsmartpaycustomerid']);


        return $this->renderPaymentStatus($request, $status);





    }


    private function paymentUser(): ?User
    {
        $sessionMember = session('LoggedMember');
        $userId = is_array($sessionMember) ? ($sessionMember['id'] ?? null) : $sessionMember;

        return $userId ? User::find($userId) : null;
    }

    private function validatedPaymentAmount(
        Request $request,
        User $user,
        ClubmanMemberLookup $clubmanMemberLookup,
        bool $amountIsInPaise = false,
        bool $requireGateway = false
    ): float {
        try {
            $minimumAmount = $clubmanMemberLookup->minimumPaymentAmount($user);
        } catch (\Throwable $exception) {
            Log::warning('Unable to verify the Clubman minimum payment amount.', [
                'member_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'amount' => 'The minimum payment amount could not be verified. Please try again shortly.',
            ]);
        }

        $submittedMinimum = $amountIsInPaise
            ? (int) round($minimumAmount * 100)
            : $minimumAmount;
        $rules = [
            'amount' => [
                'required',
                $amountIsInPaise ? 'integer' : 'numeric',
                'min:' . $submittedMinimum,
            ],
        ];

        if ($requireGateway) {
            $rules['paymentGatewayOptions'] = ['required'];
        }

        $validated = $request->validate($rules, [
            'amount.min' => 'The minimum payment amount is INR ' . number_format($minimumAmount, 2) . '.',
        ]);

        return (float) $validated['amount'];
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
