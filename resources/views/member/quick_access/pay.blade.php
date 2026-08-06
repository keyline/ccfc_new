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

        * {
            box-sizing: border-box;
        }

        body {
            background: #f0f1f3;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            color: var(--textColor);
        }

        .quickpay-wrap {
            max-width: 460px;
            margin: 0 auto;
            padding: 0 0 40px;
        }

        .quickpay-topbar {
            background: linear-gradient(135deg, var(--primaryColor), var(--secondaryColor));
            padding: 28px 20px 46px;
            text-align: center;
            color: #fff;
            border-radius: 0 0 24px 24px;
        }

        .quickpay-logo-badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .quickpay-logo-badge img.quickpay-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .quickpay-logo-badge img.quickpay-crest {
            max-height: 50px;
            max-width: 50px;
            width: auto;
            object-fit: contain;
        }

        .quickpay-topbar h4 {
            margin: 4px 0 2px;
            font-weight: 700;
            font-size: 18px;
        }

        .quickpay-topbar .member-code-pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.18);
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .quickpay-body {
            padding: 0 16px;
            margin-top: -32px;
        }

        .quickpay-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
            margin-bottom: 16px;
        }

        .balance-card {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .balance-item {
            flex: 1;
            text-align: center;
        }

        .balance-item + .balance-item {
            border-left: 1px solid #eee;
        }

        .balance-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .balance-value {
            font-size: 19px;
            font-weight: 700;
            color: var(--textColor);
        }

        .balance-value.negative {
            color: #1a9e56;
        }

        .balance-value.positive-due {
            color: var(--primaryColor);
        }

        .balance-note {
            display: block;
            text-align: center;
            color: #999;
            font-size: 11.5px;
            margin-top: 12px;
        }

        .section-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--textColor);
            margin-bottom: 10px;
        }

        .amount-input-group {
            position: relative;
            margin-bottom: 4px;
        }

        .amount-input-group .currency-prefix {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 700;
            color: #888;
            font-size: 16px;
            pointer-events: none;
        }

        .amount-input-group input {
            width: 100%;
            padding: 14px 14px 14px 40px;
            border: 1.5px solid #e2e2e2;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            color: var(--textColor);
            transition: border-color 0.15s ease;
        }

        .amount-input-group input:focus {
            outline: none;
            border-color: var(--primaryColor);
        }

        .gateway-list {
            list-style: none;
            margin: 14px 0 0;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        @media (max-width: 380px) {
            .gateway-list {
                grid-template-columns: 1fr 1fr;
            }
        }

        .gateway-list li {
            margin: 0;
        }

        .gateway-option {
            position: relative;
            display: block;
        }

        .gateway-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
            z-index: 2;
        }

        .gateway-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 52px;
            border: 1.5px solid #e2e2e2;
            border-radius: 10px;
            padding: 6px 10px;
            margin: 0;
            cursor: pointer;
            background: #fafafa;
            transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
        }

        .gateway-option input[type="radio"]:checked + label {
            border-color: var(--primaryColor);
            background: #fff;
            box-shadow: 0 0 0 1px var(--primaryColor);
        }

        .gateway-option img {
            max-height: 24px;
            max-width: 100%;
            width: auto;
            display: block;
        }

        .pay-now-btn {
            width: 100%;
            border: none;
            background: var(--primaryColor);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            padding: 15px;
            border-radius: 10px;
            margin-top: 18px;
            box-shadow: 0 6px 16px rgba(190, 31, 36, 0.3);
            transition: background 0.15s ease;
        }

        .pay-now-btn:hover,
        .pay-now-btn:focus {
            background: var(--secondaryColor);
            color: #fff;
        }

        .secure-note {
            text-align: center;
            font-size: 11.5px;
            color: #aaa;
            margin-top: 14px;
        }

        #log {
            font-size: 12px;
            color: var(--primaryColor);
            background: none;
            border: none;
            padding: 0;
            margin: 8px 0 0;
            white-space: pre-wrap;
        }

        .payment-alert-modal {
            position: fixed;
            inset: 0;
            z-index: 100000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 22px;
        }

        .payment-alert-modal.show {
            display: flex;
        }

        .payment-alert-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(18, 20, 24, 0.58);
            backdrop-filter: blur(4px);
        }

        .payment-alert-dialog {
            position: relative;
            width: min(100%, 390px);
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 24px 70px rgba(18, 20, 24, 0.28);
            overflow: hidden;
            transform: translateY(10px) scale(0.98);
            opacity: 0;
            transition: transform 0.16s ease, opacity 0.16s ease;
        }

        .payment-alert-modal.show .payment-alert-dialog {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .payment-alert-top {
            height: 5px;
            background: linear-gradient(90deg, var(--primaryColor), #ef8b35);
        }

        .payment-alert-content {
            padding: 24px 22px 20px;
            text-align: center;
        }

        .payment-alert-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            background: #fff3f3;
            color: var(--primaryColor);
            border: 1px solid rgba(190, 31, 36, 0.18);
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }

        .payment-alert-title {
            margin: 0 0 8px;
            font-size: 19px;
            line-height: 1.25;
            font-weight: 800;
            color: #20242a;
        }

        .payment-alert-message {
            margin: 0;
            color: #5d626b;
            font-size: 14px;
            line-height: 1.55;
        }

        .payment-alert-actions {
            display: flex;
            gap: 10px;
            padding: 0 22px 22px;
        }

        .payment-alert-btn {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 13px 16px;
            background: var(--primaryColor);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(190, 31, 36, 0.24);
        }

        .payment-alert-btn:focus,
        .payment-alert-btn:hover {
            outline: none;
            background: var(--secondaryColor);
        }

        @media (max-width: 380px) {
            .balance-value {
                font-size: 16px;
            }

            .quickpay-topbar {
                padding: 22px 14px 40px;
            }

            .payment-alert-content {
                padding: 22px 18px 18px;
            }

            .payment-alert-actions {
                padding: 0 18px 18px;
            }
        }
    </style>
</head>

<body>
    <div class="quickpay-wrap">
        @php($memberDetails = $userData->userCodeUserDetails->first())
        <div class="quickpay-topbar">
            <div class="quickpay-logo-badge">
                @if ($memberDetails && $memberDetails->has_member_image)
                    <img class="quickpay-photo" src="{{ route('member.profile-image') }}" loading="lazy" decoding="async" alt="{{ $userData->name }}" />
                @else
                    <img class="quickpay-crest" src="{{ asset('img/logo.png') }}" alt="CCFC" />
                @endif
            </div>
            <h4>{{ $userData->name }}</h4>
            <span class="member-code-pill">{{ $userData->user_code }}</span>
        </div>

        <div class="quickpay-body">
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
            @php($outstandingValue = $memberFinancials ? (float) $memberFinancials['outstanding'] : null)

            <div class="quickpay-card">
                <div class="balance-card" aria-live="polite">
                    <div class="balance-item">
                        <div class="balance-label">Outstanding</div>
                        <div class="balance-value {{ $outstandingValue !== null && $outstandingValue < 0 ? 'negative' : ($outstandingValue > 0 ? 'positive-due' : '') }}"
                            id="clubman-outstanding">
                            {{ $memberFinancials ? 'INR ' . number_format($outstandingValue, 2) : 'Unavailable' }}
                        </div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">Minimum Due</div>
                        <div class="balance-value" id="clubman-minimum-due">
                            {{ $clubmanMinimumDue !== null ? 'INR ' . number_format((float) $clubmanMinimumDue, 2) : 'Unavailable' }}
                        </div>
                    </div>
                </div>
                {{-- <small class="balance-note">
                    {{ $memberFinancials ? 'Showing the latest available Clubman balance.' : 'Clubman balance is temporarily unavailable. You may still make a payment below.' }}
                </small> --}}
            </div>

            <div class="quickpay-card invoicepayment_section">
                <div class="invoice_outstading_payment">
                    <form action="" method="POST" id="payment-form">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="active_token_id" value="{{ session()->get('tokenPayment.active_id') }}">
                        <input type="hidden" name="member_code" value="{{ $userData->user_code }}">

                        @csrf

                        <div class="section-label">Payment Amount</div>
                        <div class="amount-input-group">
                            <span class="currency-prefix">&#8377;</span>
                            <input type="number" name="amount" id="payment-amount"
                                value="{{ old('amount', $clubmanMinimumDue !== null ? number_format((float) $clubmanMinimumDue, 2, '.', '') : '') }}"
                                min="{{ number_format($clubmanMinimumPayment, 2, '.', '') }}"
                                step="0.01" inputmode="decimal"
                                data-minimum-payment="{{ number_format($clubmanMinimumPayment, 2, '.', '') }}"
                                data-user-edited="{{ old('amount') !== null ? 'true' : 'false' }}"
                                placeholder="Enter amount">
                        </div>

                        <div class="section-label mt-3">Select Payment Method</div>
                        <div class="invocie_paymentlogo">
                            <ul class="gateway-list">
                                <li>
                                    <div class="gateway-option">
                                        <input type="radio" name="paymentGatewayOptions" id="exampleRadios5"
                                            onclick="hdfcSmartSubmit(this);">
                                        <label for="exampleRadios5">
                                            <img class="img-fluid" src="{{ asset('img/HdfcLogo.svg') }}" alt="HDFC SmartGateway" />
                                        </label>
                                    </div>
                                </li>
                                <li>
                                    <div class="gateway-option">
                                        <input type="radio" name="paymentGatewayOptions" id="exampleRadios1"
                                            value="{{ route('member.payment') }}" onclick="setPaymentAction('payu')">
                                        <label for="exampleRadios1">
                                            <img class="img-fluid" src="{{ asset('img/invoice_payu_logo.png') }}" alt="PayU" />
                                        </label>
                                    </div>
                                </li>
                                <li>
                                    <div class="gateway-option">
                                        <input type="radio" name="paymentGatewayOptions" id="exampleRadios4"
                                            onclick="razorpaySubmit(this);">
                                        <label for="exampleRadios4">
                                            <img class="img-fluid" src="{{ asset('img/invoice_razorpay_logo.png') }}" alt="Razorpay" />
                                        </label>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <button type="submit" class="pay-now-btn">Pay Now</button>
                        <p class="secure-note">&#128274; Payments are securely processed by your selected gateway.</p>
                        <pre id="log"></pre>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function closePaymentPopup() {
            const modal = document.getElementById('payment-alert-modal');
            if (!modal) return;

            modal.classList.remove('show');
            document.body.style.overflow = '';
            document.removeEventListener('keydown', closePaymentPopupOnEsc);
        }

        function closePaymentPopupOnEsc(event) {
            if (event.key === 'Escape') {
                closePaymentPopup();
            }
        }

        function showPaymentPopup(message, title = 'Payment Notice') {
            let modal = document.getElementById('payment-alert-modal');

            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'payment-alert-modal';
                modal.className = 'payment-alert-modal';
                modal.innerHTML =
                    '<div class="payment-alert-backdrop" data-payment-popup-close></div>' +
                    '<div class="payment-alert-dialog" role="dialog" aria-modal="true" aria-labelledby="payment-alert-title">' +
                    '<div class="payment-alert-top"></div>' +
                    '<div class="payment-alert-content">' +
                    '<div class="payment-alert-icon" aria-hidden="true">!</div>' +
                    '<h2 class="payment-alert-title" id="payment-alert-title"></h2>' +
                    '<p class="payment-alert-message" id="payment-alert-message"></p>' +
                    '</div>' +
                    '<div class="payment-alert-actions">' +
                    '<button type="button" class="payment-alert-btn" data-payment-popup-close>Review Amount</button>' +
                    '</div>' +
                    '</div>';
                document.body.appendChild(modal);

                modal.querySelectorAll('[data-payment-popup-close]').forEach(function(button) {
                    button.addEventListener('click', closePaymentPopup);
                });
            }

            modal.querySelector('#payment-alert-title').textContent = title;
            modal.querySelector('#payment-alert-message').textContent = message;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', closePaymentPopupOnEsc);
            modal.querySelector('.payment-alert-btn').focus();
        }

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

            if (errorMsg.length) {
                showPaymentPopup(errorMsg[0], 'Payment Attention');
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

            const payNowButton = document.querySelector('.pay-now-btn');
            payNowButton.style.display = 'none';

            let amountInput = document.querySelector('input[name="amount"]');
            let amountValue = parseFloat(amountInput.value);

            const amountError = paymentAmountValidationMessage(amountInput.value);
            if (amountError) {
                showPaymentPopup(amountError, 'Payment Amount');
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
                        showPaymentPopup("Failed to initiate Razorpay order", 'Payment Gateway');
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
                    showPaymentPopup("Error connecting to Razorpay.", 'Payment Gateway');
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

            const payNowButton = document.querySelector('.pay-now-btn');
            payNowButton.style.display = 'none';
            let amountInput = document.querySelector('input[name="amount"]');
            let amountValue = parseFloat(amountInput.value);
            let tokenPayment = document.querySelector('input[name="active_token_id"]');
            let memberCode = document.querySelector('input[name="member_code"]');

            const amountError = paymentAmountValidationMessage(amountInput.value);
            if (amountError) {
                showPaymentPopup(amountError, 'Payment Amount');
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
                    showPaymentPopup(data.error || `Unexpected status: ${data.status}`, 'Payment Gateway');
                })
                .catch(err => {
                    el.checked = false;
                    loader.style.display = 'none';
                    payNowButton.style.display = '';
                    console.error(err);
                    showPaymentPopup(err.message || "Error connecting to hdfcsmartpay.", 'Payment Gateway');
                });
        }
    </script>
</body>

</html>
