<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Juspay\JuspayEnvironment;
use Juspay\Request\OrderCreateRequest;
use Juspay\Model\Order;
use Juspay\Model\JuspayJWT;
use Juspay\RequestOptions;
use Juspay\Model\OrderSession;
use Juspay\Exception\JuspayException;
use Illuminate\Support\Facades\Session;
use App\Models\MemberDue;
use Exception;

class HdfcGatewayTestingController extends Controller
{
        // public function index()
        // {

        //     $configPath = storage_path('app/juspay/config.json');
        //     $keyPath = storage_path('app/juspay/');

        //     $config = file_get_contents($configPath);
        //     $config = json_decode($config, true);

        //     $privateKey = array_key_exists("PRIVATE_KEY", $config) ? $config["PRIVATE_KEY"] : file_get_contents($keyPath . $config["PRIVATE_KEY_PATH"]);
        //     $publicKey =  array_key_exists("PUBLIC_KEY", $config) ? $config["PUBLIC_KEY"] : file_get_contents($keyPath . $config["PUBLIC_KEY_PATH"]);

        //     // JuspayEnvironment::init()
        //     // ->withBaseUrl("https://smartgateway.hdfcuat.bank.in")
        //     // ->withMerchantId($config["MERCHANT_ID"])
        //     // ->withJuspayJWT(new JuspayJWT($config["KEY_UUID"], $publicKey, $privateKey)); 

        //     JuspayEnvironment::init()
        //         ->withBaseUrl("https://smartgateway.hdfcuat.bank.in")
        //         ->withMerchantId($config["MERCHANT_ID"])
        //         ->withJuspayJWT(
        //             new JuspayJWT(
        //                 $config["KEY_UUID"],
        //                 $publicKey,
        //                 $privateKey
        //             )
        //         );
        //             $orderId = "ORDER_" . time();
        //             $amount = 100;

        //             $user = auth()->user() ?? (object)[
        //                 'id' => 1,
        //                 'email' => 'test@test.com',
        //                 'phone_number_1' => '9999999999',
        //                 'user_code' => 'TEST123'
        //             ];

        //             $params = [];

        //             $params['amount'] = $amount;
        //             $params['currency'] = "INR";
        //             $params['order_id'] = $orderId;
        //             // $params['customer_id'] = (string)$user->id;
        //             // $params['merchant_id'] = $config["MERCHANT_ID"];
        //             // $params['merchant_id'] = 'SG3351';
        //             $params['customer_email'] = $user->email ?? 'test@test.com';
        //             $params['customer_phone'] = $user->phone_number_1 ?? '9999999999';
        //             $params['first_name'] = 'Somnath';
        //             $params['last_name'] = 'Shil';
        //             $params['udf1'] = $user->user_code;
        //             $params['udf2'] = $user->id;
        //             $params['payment_page_client_id'] = $config["PAYMENT_PAGE_CLIENT_ID"];
        //             // $params['payment_page_client_id'] = 'hdfcmaster';
        //             // $params['action'] = "paymentPage";
        //             // $params['return_url'] = route('member.hdfcsmartpaycallback');

                    
        //             $requestOption = new RequestOptions();
        //             $requestOption->withCustomerId($user->id);
        //             try {
        //                $session = OrderSession::create($params, $requestOption);
        //                } catch ( JuspayException $e ) {
        //                 echo "error code" . $e->getHttpResponseCode() . PHP_EOL;
        //                 echo "error message: " . $e->getErrorMessage() . PHP_EOL;
        //                 echo "error code" . $e->getErrorCode() . PHP_EOL;
        //             }
    
        //             // } catch (JuspayException $e){
        //             //     $error = [
        //             //         'message' => $e->getMessage(),
        //             //         'error_message' => $e->getErrorMessage(),
        //             //         'code' => $e->getCode(),
        //             //         'params' => $params,
        //             //     ];
        //             //     dd($error); die;
        //             // }

        //             // dd($session); die;
        //             return view('hdfc-testing/hdfc-demo');

        // }

    // public function index()
    // {
    //     $configPath = storage_path('app/juspay/config.json');
    //     $keyPath = storage_path('app/juspay/');

    //     $config = json_decode(file_get_contents($configPath), true);

    //     JuspayEnvironment::init()
    //         ->withBaseUrl("https://smartgateway.hdfcuat.bank.in")
    //         ->withMerchantId($config["MERCHANT_ID"])
    //         ->withPrivateKey(
    //             $keyPath . $config["PRIVATE_KEY_PATH"],
    //             $config["KEY_UUID"]
    //         );

    //     $orderId = "ORDER_" . time();
    //     $amount = 100;

    //     $user = auth()->user() ?? (object)[
    //         'id' => 1,
    //         'email' => 'test@test.com',
    //         'phone_number_1' => '9999999999',
    //         'user_code' => 'TEST123'
    //     ];

    //     $params = [];
    //     $params['amount'] = $amount;
    //     $params['currency'] = "INR";
    //     $params['order_id'] = $orderId;
    //     $params['customer_id'] = $user->id;
    //     $params['merchant_id'] = $config["MERCHANT_ID"];
    //     $params['customer_email'] = $user->email;
    //     $params['customer_phone'] = $user->phone_number_1;
    //     $params['first_name'] = 'Somnath';
    //     $params['last_name'] = 'Shil';
    //     $params['udf1'] = $user->user_code;
    //     $params['udf2'] = $user->id;
    //     $params['payment_page_client_id'] = $config["PAYMENT_PAGE_CLIENT_ID"];
    //     $params['action'] = "paymentPage";
    //     $params['return_url'] = route('member.hdfcsmartpaycallback');

    //     $requestOption = new RequestOptions();
    //     $requestOption->withCustomerId($user->id);

    //     $session = OrderSession::create($params, $requestOption);

    //     return view('hdfc_demo', compact('session'));
    // }

    public function index(){
        $user = User::find(session::get('hdfcsmartpaycustomerid'));
        $dueDetails = MemberDue::where('member_code', $user->user_code)
                                    ->first();
                print_r($dueDetails);die;
    }
}
