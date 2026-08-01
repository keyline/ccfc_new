<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CCFC :: Quick Payment</title>

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/responsive.css') }}">

    <style>
        :root {
            --primaryColor: #be1f24;
            --secondaryColor: #c23233;
            --trirdColor: #000;
            --textColor: #2f2f2f;
        }

        body {
            background: #f4f4f4;
        }

        .quickpay-wrap {
            max-width: 480px;
            margin: 0 auto;
            padding: 24px 16px 60px;
        }

        .quickpay-card {
            background: #fff;
            border-radius: 10px;
            padding: 24px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .quickpay-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .quickpay-header img {
            max-height: 56px;
            margin-bottom: 10px;
        }

        .quickpay-header h4 {
            margin: 0;
            color: var(--primaryColor);
            font-weight: 700;
        }

        .clubman-financial-summary {
            margin: 0 0 20px;
            padding: 0;
        }

        .clubman-financial-summary p {
            margin: 0 0 6px;
        }

        .clubman-financial-summary small {
            color: #666;
            display: block;
        }

        .invoicepayment_section .invocie_paymentlogo ul {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px 22px;
            margin: 14px 0 0;
            padding: 0;
            list-style: none;
        }

        .invoicepayment_section .invocie_paymentlogo li {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .invoicepayment_section .invocie_paymentlogo .form-check-input {
            margin: 0;
            position: static;
            flex-shrink: 0;
        }

        .invoicepayment_section .invocie_paymentlogo .form-check-label {
            display: inline-flex;
            align-items: center;
            margin: 0;
            cursor: pointer;
        }

        .invoicepayment_section .invocie_paymentlogo img {
            max-height: 28px;
            width: auto;
            display: block;
        }

        @media (max-width: 480px) {
            .quickpay-wrap {
                padding: 12px 8px 40px;
            }

            .quickpay-card {
                padding: 18px 14px;
            }
        }
    </style>
</head>

<body>
    <div class="quickpay-wrap">
        <div class="quickpay-card">
            <div class="quickpay-header">
                <img src="{{ asset('img/logo.png') }}" class="img-fluid" alt="CCFC" />
                <h4>{{ $userData->name }}</h4>
                <small>Member Code: {{ $userData->user_code }}</small>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php($clubmanMinimumDue = $memberFinancials['minimum_due_amount'] ?? null)
            @php($clubmanMinimumPayment = $clubmanMinimumDue === null ? 1 : max(1, (float) $clubmanMinimumDue))

            <div class="clubman-financial-summary" aria-live="polite">
                <p><strong>Outstanding:</strong> INR
                    <span id="clubman-outstanding">
                        {{ $memberFinancials ? number_format((float) $memberFinancials['outstanding'], 2) : 'Unavailable' }}
                    </span>
                </p>
                <p><strong>Minimum Due Amount:</strong> INR
                    <span id="clubman-minimum-due">
                        {{ $clubmanMinimumDue !== null ? number_format((float) $clubmanMinimumDue, 2) : 'Unavailable' }}
                    </span>
                </p>
                <small>
                    {{ $memberFinancials ? 'Showing the latest available Clubman balance.' : 'Clubman balance is temporarily unavailable. You may still make a payment below.' }}
                </small>
            </div>

            <div class="invoicepayment_section">
                <div class="invoice_outstading_payment">
                    <form action="" method="POST" id="payment-form">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="active_token_id" value="{{ session()->get('tokenPayment.active_id') }}">
                        <input type="hidden" name="member_code" value="{{ $userData->user_code }}">

                        @csrf
                        <div class="invoice_input_bank">
                            <div class="invoice_input_feild">
                                <input type="number" name="amount" id="payment-amount"
                                    value="{{ old('amount', $clubmanMinimumDue !== null ? number_format((float) $clubmanMinimumDue, 2, '.', '') : '') }}"
                                    min="{{ number_format($clubmanMinimumPayment, 2, '.', '') }}"
                                    step="0.01" inputmode="decimal"
                                    data-minimum-payment="{{ number_format($clubmanMinimumPayment, 2, '.', '') }}"
                                    data-user-edited="{{ old('amount') !== null ? 'true' : 'false' }}"
                                    placeholder="Enter amount being paid">
                            </div>
                            <div class="invocie_paymentlogo">
                                <ul>
                                    <li>
                                        <input class="form-check-input" type="radio" name="paymentGatewayOptions"
                                            id="exampleRadios5" onclick="hdfcSmartSubmit(this);">
                                        <label class="form-check-label" for="exampleRadios5">
                                            <img class="img-fluid" src="{{ asset('img/HdfcLogo.svg') }}" alt="" />
                                        </label>
                                    </li>
                                    <li>
                                        <input class="form-check-input" type="radio" name="paymentGatewayOptions"
                                            id="exampleRadios1" value="{{ route('member.payment') }}"
                                            onclick="setPaymentAction('payu')">
                                        <label class="form-check-label" for="exampleRadios1">
                                            <img class="img-fluid" src="{{ asset('img/invoice_payu_logo.png') }}"
                                                alt="" />
                                        </label>
                                    </li>
                                    <li>
                                        <input class="form-check-input" type="radio" name="paymentGatewayOptions"
                                            id="exampleRadios4" onclick="razorpaySubmit(this);">
                                        <label class="form-check-label" for="exampleRadios4">
                                            <img class="img-fluid" src="{{ asset('img/invoice_razorpay_logo.png') }}"
                                                alt="" />
                                        </label>
                                    </li>
                                    <li>
                                        <input class="form-check-input" type="radio" name="paymentGatewayOptions"
                                            id="exampleRadios3" value="{{ route('member.axischeckout') }}"
                                            onclick="setPaymentAction('axis')">
                                        <label class="form-check-label" for="exampleRadios3">
                                            <img class="img-fluid" src="{{ asset('img/invoice_axis_logo.jpg') }}"
                                                alt="" />
                                        </label>
                                    </li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block mt-3">Pay Now</button>
                            <pre id="log"></pre>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setPaymentAction(gateway) {
            const form = document.getElementById('payment-form');
            const radios = document.getElementsByName('paymentGatewayOptions');
            for (let i = 0; i < radios.length; i++) {
                if (radios[i].checked) {
                    form.action = radios[i].value;
                }
            }
        }

        const form = document.getElementById("payment-form");
        const log = document.querySelector("#log");

        form.addEventListener("submit", (event) => {
            event.preventDefault();
            let errorMsg = [];
            let messageHtml = "";
            const data = new FormData(form);
            let amountInput = data.get('amount');
            let route = getCheckedPG('paymentGatewayOptions');

            if (route === null) {
                errorMsg.push("Please check one of payment gateway before making payment");
            }

            const amountError = paymentAmountValidationMessage(amountInput);
            if (amountError) {
                errorMsg.push(amountError);
            }

            if (Array.isArray(errorMsg) && !errorMsg.length) {
                form.action = route;
                form.submit();
            }

            errorMsg.forEach(function(message) {
                messageHtml += "<li>" + message + "</li>";
            });

            log.innerHTML = messageHtml;
        }, false);

        function getCheckedPG(groupName) {
            var radios = document.getElementsByName(groupName);
            for (i = 0; i < radios.length; i++) {
                if (radios[i].checked) {
                    return radios[i].value;
                }
            }
            return null;
        }

        function paymentAmountValidationMessage(amount) {
            const amountRegex = /^\d+(\.\d{1,2})?$/;
            const amountInput = document.getElementById('payment-amount');
            const minimum = parseFloat(amountInput?.dataset.minimumPayment || '1');
            const numericAmount = parseFloat(amount);

            if (!amountRegex.test(amount) || !Number.isFinite(numericAmount) || numericAmount <= 0) {
                return 'Please enter a valid payment amount.';
            }

            if (numericAmount + Number.EPSILON < minimum) {
                return 'The minimum payment amount is INR ' + minimum.toFixed(2) + '.';
            }

            return '';
        }
    </script>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        function razorpaySubmit(el) {
            if (!el.checked) return;

            const payNowButton = document.querySelector('.btn-primary');
            payNowButton.style.display = 'none';

            let amountInput = document.querySelector('input[name="amount"]');
            let amountValue = parseFloat(amountInput.value);

            if (!amountValue || amountValue <= 0) {
                alert("Please enter a valid amount before choosing Razorpay.");
                el.checked = false;
                payNowButton.style.display = '';
                return;
            }

            let amountInPaise = Math.round(amountValue * 100);

            fetch("{{ route('member.razorpay') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        amount: amountInPaise
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.order_id) {
                        alert("Failed to initiate Razorpay order");
                        return;
                    }

                    var options = {
                        key: "{{ env('RAZORPAY_KEY_NEW') }}",
                        amount: amountInPaise,
                        currency: "INR",
                        name: "CALCUTTA CRICKET & FOOTBALL CLUB",
                        description: "Invoice Payment",
                        image: "{{ asset('img/logo.png') }}",
                        order_id: data.order_id,
                        handler: function(response) {
                            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                            document.getElementById('razorpay_signature').value = response.razorpay_signature;
                            document.getElementById('payment-form').action =
                                "{{ route('member.razorpaycallback') }}";
                            document.getElementById('payment-form').submit();
                        },
                        prefill: {
                            name: "{{ $userData->name ?? 'Guest' }}",
                            email: "{{ $userData->email ?? 'NA' }}",
                            contact: "{{ $userData->phone_number_1 ?? '' }}"
                        },
                        notes: {
                            contact: "{{ $userData->phone_number_1 ?? '' }}",
                            udf1: "{{ $userData->id ?? '0' }}",
                            udf2: "{{ $userData->user_code ?? 'N/A' }}"
                        },
                        theme: {
                            color: "#4c0c0e"
                        }
                    };

                    let rzp = new Razorpay(options);
                    rzp.open();
                })
                .catch(err => {
                    el.checked = false;
                    console.error(err);
                    alert("Error connecting to Razorpay.");
                });
        }
    </script>

    <script>
        function hdfcSmartSubmit(el) {
            if (!el.checked) {
                return;
            }

            let loader = document.getElementById('hdfc-smart-loader');
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'hdfc-smart-loader';
                loader.innerHTML =
                    '<div style="display:flex;flex-direction:column;align-items:center;gap:12px;color:#fff;font-family:Arial,sans-serif;">' +
                    '<div style="width:42px;height:42px;border:4px solid rgba(255,255,255,0.35);border-top-color:#ffffff;border-radius:50%;animation:hdfcSmartSpin 0.8s linear infinite;"></div>' +
                    '<div style="font-size:16px;font-weight:600;">Please wait, redirecting to payment gateway...</div>' +
                    '</div>';
                loader.style.cssText =
                    'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;padding:20px;';
                document.body.appendChild(loader);

                const loaderStyle = document.createElement('style');
                loaderStyle.innerHTML =
                    '@keyframes hdfcSmartSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
                document.head.appendChild(loaderStyle);
            }

            loader.style.display = 'flex';

            const payNowButton = document.querySelector('.btn-primary');
            payNowButton.style.display = 'none';
            let amountInput = document.querySelector('input[name="amount"]');
            let amountValue = parseFloat(amountInput.value);
            let tokenPayment = document.querySelector('input[name="active_token_id"]');
            let memberCode = document.querySelector('input[name="member_code"]');

            if (!amountValue || amountValue <= 0) {
                alert("Please enter a valid amount before choosing HDFC Smart gateway.");
                el.checked = false;
                loader.style.display = 'none';
                payNowButton.style.display = '';
                return;
            }

            fetch("{{ route('member.hdfcsmartpg') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json;charset=UTF-8",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        amount: amountValue,
                        token_id: tokenPayment?.value || null,
                        member_code: memberCode?.value || null
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.errors?.amount?.[0] || data.message ||
                                `HTTP ${response.status}: Request failed`);
                        });
                    }

                    return response.json();
                })
                .then(data => {
                    if (data.data && data.data.order_status === 'NEW') {
                        const url = data.data.payment_link;
                        return window.location.href = url;
                    }
                    console.log(data);
                    loader.style.display = 'none';
                    payNowButton.style.display = '';
                    alert(data.error || `Unexpected status: ${data.status}`);
                })
                .catch(err => {
                    el.checked = false;
                    loader.style.display = 'none';
                    payNowButton.style.display = '';
                    console.error(err);
                    alert(err.message || "Error connecting to hdfcsmartpay.");
                });
        }
    </script>
</body>

</html>
