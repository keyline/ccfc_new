<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Juspay\JuspayEnvironment;
use Juspay\Request\OrderCreateRequest;
use Juspay\Model\Order;
use Juspay\Util\JuspayJWT;

class HdfcGatewayTestingController extends Controller
{
    public function index()
    {

        $config = [
            "MERCHANT_ID" => "SG3351",
            "KEY_UUID" => "key_3de48dc05cda42faac6d0ba1f3a80ab2",
            "PRIVATE_KEY_PATH" => "MIIEowIBAAKCAQEAnP+ce3eVdc0Fr2Xwv+QJl7/L7dQJrT3ojWKSAgapIq6BO6Qt
                                    EhekZyUulMwUZaLA+S6Qd76T+BoBw/X3etHCYqTUR9jOiNGBZYRII9cloS18D3Vi
                                    NdvXeapzXfiGghGFrRrDMwrM3+/pZcWUV70JFPxkIHwfnmngc3x02q12tjrCZV7D
                                    jHBhn4fFeo2wY9MTzAtAtPTqmYNvN75nGRfGzcljI57oaSfoqBEmublZmt2F2ubd
                                    ACBmfI7IUcm9398kmfbXR7NGD3xBNUqMlZPVW2lxX6YXNeyQf83pYpkZUfCShGLG
                                    PY8laCdFqTOGJcDdJplsiJ1IwokoKEYAFTlo5wIDAQABAoIBAF1gfm7E4kUtSttW
                                    k0leVQJHlf//JD5A2wb0gIjp80Dob37Ml+3x19ttNvZTJzKICaRrOIuv8wwWU8R8
                                    j9Qh3C6VgJi276hai06YzdtbREtH5UjAdUg5WEBJy9IxVbcYutwJVd4O52mtpLDe
                                    QeupSDSOJPNZP+kVaeTmOg4yK7AX83ar50iAhz5Ff5ZCU3ahzkefX7Gpkyca+/+d
                                    8tmKYZikQxbMHVWjoGvnjs8XRLF8XjgTnll5lhGeiAsXlCG85tlKkjCvqtkqOm83
                                    b91vM0SI154RefiZA4eDlp0UnD1bNgRk+BVdNipXkVM6/as+mIdr7P+xDqZWIREN
                                    zm16gHECgYEA6WzWg+33QDfR3TDeSWH6S4S47fRp8/HdvTeJnog+ZUEbkUfQd/Fq
                                    8t6N0EOua4W20SFoLWOI6zZEO257gzkFCc/vpir/9saHgHnarJyVgKSqIlPAzURU
                                    Z78afUN7dtfxYTCchvnmyMkfNI4h0pnR91EX8M5JlluyXo23zuMVQpkCgYEArC6Y
                                    OKeYy3Srk9EDDmPtGLxmCYd+mrrPiytB5V5G3NmowgRflVIDjHcLF7xjXwqC3x9S
                                    E2ZX7ET69Zd4trSwlIaRsZEejJajOY3aFGPKGAfWU81Rjl1qV65+niE1mQ8L5F0Z
                                    vQacCZU6T7rArVNU4AB34p51x9sI/pVrnfDit38CgYEAukSQtbyKJiOlA3YAm9xk
                                    iCjEDZaRignCoUCVP/2GlPQslHUTJPNwHZh83+lyYPjV1vJqmHWqB9BJCIf0ZdV1
                                    cMwOd7IuiXUJIfubBUz6fCpqXXQqEWLqW9OCxty3xDEzvBO3hHocsLLVhPG6ib0X
                                    cNy0VwO2cxZ/MrayK5TIHVkCgYAyoo0/dAIaKxheIwRcEgTi1lzHhqIzzKZGThVV
                                    57C9OAFJ9VFKr1C92TBY7ZznkUbFaQeRDvLiV7LZ0I6+ZErdkul7p6qtO4uK3G16
                                    u5HuGTftcx791/jzCizQQgHqHiOoJ7zu+ueeZdU9JzWMg0odieW3rlQCzyZJABYi
                                    33k87wKBgALH+GCrzVjz3NOEA0cIpxt8+KQsGk+/evm5xG4bgb3fKgmLPxg1wxVU
                                    tyJDhHXpnzxJYQZaAtQ65jxAb2Hjn9/SF6YUQufbOmQVW5L3CBXFkcPHW32sQj6+
                                    a/Y2RLO7/IGWR0lRf9L4iKKW5WP6yB1If4AWgBBl4RT+2MsBHWCb",
        ];
        $amount = 100.00;

        try {

            JuspayEnvironment::init()
                ->withBaseUrl("https://smartgateway.hdfcbank.com")
                ->withMerchantId($config["MERCHANT_ID"])
                ->withJuspayJWT(
                    new JuspayJWT(
                        $config["KEY_UUID"],
                        $config["PRIVATE_KEY_PATH"]
                    )
                );

            $orderRequest = new OrderCreateRequest();

            $order = new Order();
            $order->setOrderId($orderId);
            $order->setAmount($amount * 100); 
            $order->setCurrency("INR");

            $orderRequest->setOrder($order);

            $response = $orderRequest->create();

            echo "<h3> Order Created Successfully</h3>";
            echo "<pre>";
            print_r($response);
            echo "</pre>";

        } catch (\Exception $e) {
            echo "<h3> Error:</h3>";
            echo $e->getMessage();
        }
        return view('hdfc-testing.hdfc-demo');
    }
}