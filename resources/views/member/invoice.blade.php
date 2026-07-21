<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        /* File: resources/views/member/invoice.blade.php | Improve spacing and alignment for payment gateway radio options only */
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

        .clubman-financial-summary {
            margin-top: 12px;
            padding: 0;
        }

        .clubman-financial-summary p {
            margin: 0 0 6px;
        }

        .clubman-financial-summary p:last-of-type {
            margin-bottom: 4px;
        }

        .clubman-financial-summary small {
            color: #666;
            display: block;
        }
    </style>

    <!-- ?php include 'assets/inc/header.php';?> -->

    <!-- header -->
    @include('common.home_header')
    <!-- ********|| RIGHT PART START ||******** -->

    <div class="col-lg-9 col-md-7 p-0">
        <div class="right-body">
            <!-- ********|| BANNER PART START ||******** -->
            <section class="banner">

                <div class="banner-box">

                    <div id="innerpage-banner" class="owl-carousel owl-theme">

                        <div class="item">

                            <div class="about-img">

                                <img class="img-fluid" src="{{ asset('img/past-president/banner1.jpg') }}"
                                    alt="" />

                            </div>

                        </div>

                    </div>

                </div>

            </section>
            <!-- ********|| BANNER PART END ||******** -->

            <!-- ********|| HISTORY START ||******** -->
            <section class="inner_belowbanner invoice_section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 col-lg-6">
                            <div class="row">
                                <div class="col-lg-4 col-md-5">
                                    <!-- <div class="member_profileimg">
                                        <img class="img-fluid" src="{{ asset('img/demopic.png') }}" alt="" />
                                    </div> -->

                                    @php($memberDetails = $userData->userCodeUserDetails->first())
                                    @if (!$memberDetails || !$memberDetails->has_member_image)
                                        <div class="member_profileimg">
                                            <img class="img-fluid ifnotpic" src="{{ asset('img/Profile-Icon-01.svg') }}"
                                                alt="" />
                                        </div>
                                    @else
                                        <div class="member_profileimg">
                                            <img class="img-fluid" src="{{ route('member.profile-image') }}"
                                                loading="lazy" decoding="async" alt="" />
                                        </div>
                                    @endif


                                </div>
                                <div class="col-lg-8 col-md-7">
                                    <div class="member_profiletop">
                                        <h4>Welcome</h4>
                                        <h2>{{ $userData->name }}</h2>

                                        <p><strong>Ph No:</strong>{{ optional($memberDetails)->mobile_no }}
                                        </p>
                                        <p><strong>Mail ID:</strong>{{ $userData->email }}
                                        </p>
                                        @php($clubmanMinimumDue = $memberFinancials['minimum_due_amount'] ?? null)
                                        @php($clubmanMinimumPayment = $clubmanMinimumDue === null ? 1 : max(1, (float) $clubmanMinimumDue))
                                        <div class="clubman-financial-summary" aria-live="polite">
                                            <p><strong>Outstanding:</strong> INR
                                                <span id="clubman-outstanding">
                                                    {{ $memberFinancials ? number_format((float) $memberFinancials['outstanding'], 2) : 'Loading...' }}
                                                </span>
                                            </p>
                                            <p><strong>Minimum Due Amount:</strong> INR
                                                <span id="clubman-minimum-due">
                                                    {{ $clubmanMinimumDue !== null ? number_format((float) $clubmanMinimumDue, 2) : 'Loading...' }}
                                                </span>
                                            </p>
                                            <small id="member-financials-status">
                                                {{ $memberFinancials ? 'Showing the latest available Clubman balance.' : 'Updating balance from Clubman...' }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-12">
                                    <div class="invoice_line"></div>
                                    <h2>For the month of : {{ $balanceFortheMonth }}</h2>
                                    <h3>Total current outstanding : INR. {{ $outstandingBalance }}</h3>
                                    <p>(As of last usage 24 hours ago as updated from club servers)</p>
                                </div>     -->

                            </div>
                        </div>

                        <div class="col-md-12 col-lg-6">
                            <div class="invoicepayment_section">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @php($latestTransaction = $userTransactions[0] ?? null)
                                <h3>Total current outstanding : INR.
                                    <span id="invoice-outstanding-balance">
                                        {{ $latestTransaction['Balance'] ?? 'Loading...' }}
                                    </span>
                                </h3>
                                <p id="invoice-data-status">Updating invoice data from club servers...</p>

                                <div class="invoice_outstading_payment">
                                    <form action="" method="POST" id="payment-form">
                                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
                                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="active_token_id"
                                            value="{{ session()->get('tokenPayment.active_id') }}">
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
                                                    placeholder="Enter amount being paid"
                                                    aria-describedby="member-financials-status">
                                            </div>
                                            <div class="invocie_paymentlogo">
                                                <ul>
                                                {{-- </?php if ($userData->user_code == 'B47CEO') { ?> --}}
                                                    <li>
                                                        <input class="form-check-input" type="radio"
                                                            name="paymentGatewayOptions" id="exampleRadios5"
                                                            onclick="hdfcSmartSubmit(this);">
                                                        <label class="form-check-label" for="exampleRadios5">
                                                            <img class="img-fluid"
                                                                src="{{ asset('img/HdfcLogo.svg') }}"
                                                                alt="" />
                                                        </label>
                                                    </li>
                                                    {{-- </?php } ?> --}}
                                                    <li>
                                                        <input class="form-check-input" type="radio"
                                                            name="paymentGatewayOptions" id="exampleRadios1"
                                                            value="{{ route('member.payment') }}"
                                                            onclick="setPaymentAction('payu')">
                                                        <label class="form-check-label" for="exampleRadios1">
                                                            <img class="img-fluid"
                                                                src="{{ asset('img/invoice_payu_logo.png') }}"
                                                                alt="" />
                                                        </label>
                                                    </li>
                                                    <!-- ?php if($userData->user_code == 'B47CEO') { ?> -->
                                                    <li>
                                                        <input class="form-check-input" type="radio"
                                                            name="paymentGatewayOptions" id="exampleRadios4"
                                                            onclick="razorpaySubmit(this);">
                                                        <label class="form-check-label" for="exampleRadios4">
                                                            <img class="img-fluid"
                                                                src="{{ asset('img/invoice_razorpay_logo.png') }}"
                                                                alt="" />
                                                        </label>
                                                    </li>
                                                    <!-- ?php } ?> -->
                                                    <li>
                                                        <input class="form-check-input" type="radio"
                                                            name="paymentGatewayOptions" id="exampleRadios3"
                                                            value="{{ route('member.axischeckout') }}"
                                                            onclick="setPaymentAction('axis')">
                                                        <label class="form-check-label" for="exampleRadios3">
                                                            <img class="img-fluid"
                                                                src="{{ asset('img/invoice_axis_logo.jpg') }}"
                                                                alt="" />
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Pay Now</button>
                                            <pre id="log"></pre>
                                        </div>
                                    </form>
                                </div>

                                <script type="text/javascript">
                                    const form = document.getElementById("payment-form");
                                    const log = document.querySelector("#log");

                                    form.addEventListener(
                                        "submit",
                                        (event) => {
                                            // debugger;
                                            event.preventDefault();
                                            let errorMsg = new Array();
                                            let messageHtml = "";
                                            const data = new FormData(form);
                                            let amountInput = data.get('amount');
                                            let route = getCheckedPG('paymentGatewayOptions');
                                            //console.log(typeof(route));
                                            if (route === null) {
                                                errorMsg.push("Please check one of payment gateway before making payment");
                                            }
                                            //console.log(checkAmount(amountInput));
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



                                        },
                                        false
                                    );

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

                                    function validateEnteredPaymentAmount() {
                                        const input = document.getElementById('payment-amount');
                                        const message = paymentAmountValidationMessage(input?.value || '');

                                        if (message) {
                                            alert(message);
                                            return false;
                                        }

                                        return true;
                                    }
                                </script>
                            </div>
                        </div>
                    </div>
            </section>

            <section class="member_details_section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 pl-0">
                            <div class="table-responsive">

                                <!-- <pre><code>{{ json_encode($userTransactions, JSON_PRETTY_PRINT) }}</code></pre> -->


                                <table class="table table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th scope="col">Month</th>
                                            <th scope="col">Opening Balance</th>
                                            <th scope="col">Total of receipts & Adjustment</th>
                                            <th scope="col">Total of Invoice & Adjustment</th>
                                            <th scope="col">Closing Balance</th>
                                            <th scope="col">View Summarized bill</th>
                                            <th scope="col">View Detailed bill</th>
                                            <!-- <th scope="col">Status</th> -->
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-transactions-body">
                                        @forelse ($userTransactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction['Month'] ?? '-' }}</td>
                                                <td>{{ $transaction['LastBalance'] ?? '-' }}</td>
                                                <td>{{ $transaction['paidamount'] ?? '-' }}</td>
                                                <td>{{ $transaction['debitamount'] ?? '-' }}</td>
                                                <td>{{ $transaction['Balance'] ?? '-' }}</td>
                                                <!-- summary -->
                                                <td>
                                                    @if (!empty($transaction['summary_bill_url']))
                                                        <a href="{{ $transaction['summary_bill_url'] }}"
                                                            target="_blank"><img class="img-fluid"
                                                                src="{{ asset('img/invoice_pdficon.png') }}"
                                                                alt="" /></a>
                                                    @else
                                                        <span>&#8211;</span>
                                                    @endif
                                                </td>
                                                <!-- Detail -->
                                                <td>
                                                    @if (!empty($transaction['detail_bill_url']))
                                                        <a href="{{ $transaction['detail_bill_url'] }}"
                                                            target="_blank"><img class="img-fluid"
                                                                src="{{ asset('img/invoice_pdficon.png') }}"
                                                                alt="" /></a>
                                                    @else
                                                        <span>&#8211;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="invoice-loading-row">
                                                <td colspan="7" class="text-center">Loading invoice data...</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
            <!-- ********|| HISTORY END ||******** -->
            @include('common.footer')
            <!-- ?php include 'assets/inc/footer.php';?> -->
            </body>

</html>
<script>
    (function() {
        const invoiceDataUrl = @json(route('member.invoice.data'));
        const memberFinancialsUrl = @json(route('member.invoice.financials'));
        const pdfIconUrl = @json(asset('img/invoice_pdficon.png'));
        const paymentAmountInput = document.getElementById('payment-amount');

        paymentAmountInput?.addEventListener('input', function() {
            paymentAmountInput.dataset.userEdited = 'true';
        });

        function textCell(value) {
            const cell = document.createElement('td');
            cell.textContent = value === null || value === undefined || value === '' ? '\u2013' : value;
            return cell;
        }

        function billCell(url) {
            const cell = document.createElement('td');

            if (!url) {
                cell.textContent = '\u2013';
                return cell;
            }

            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';

            const icon = document.createElement('img');
            icon.className = 'img-fluid';
            icon.src = pdfIconUrl;
            icon.alt = '';

            link.appendChild(icon);
            cell.appendChild(link);

            return cell;
        }

        function renderTransactions(transactions) {
            const body = document.getElementById('invoice-transactions-body');
            const balance = document.getElementById('invoice-outstanding-balance');

            if (!body || !balance) return;

            body.textContent = '';

            if (!Array.isArray(transactions) || transactions.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'text-center';
                cell.textContent = 'Invoice data is temporarily unavailable. Please try again shortly.';
                row.appendChild(cell);
                body.appendChild(row);
                balance.textContent = '\u2013';
                return;
            }

            transactions.forEach(function(transaction) {
                const row = document.createElement('tr');
                row.appendChild(textCell(transaction.Month));
                row.appendChild(textCell(transaction.LastBalance));
                row.appendChild(textCell(transaction.paidamount));
                row.appendChild(textCell(transaction.debitamount));
                row.appendChild(textCell(transaction.Balance));
                row.appendChild(billCell(transaction.summary_bill_url));
                row.appendChild(billCell(transaction.detail_bill_url));
                body.appendChild(row);
            });

            balance.textContent = transactions[0].Balance ?? '\u2013';
        }

        function money(value) {
            const amount = Number(value);

            return Number.isFinite(amount) ? amount.toFixed(2) : '\u2013';
        }

        function renderMemberFinancials(financials) {
            if (!financials || typeof financials !== 'object') return;

            const outstanding = document.getElementById('clubman-outstanding');
            const minimumDue = document.getElementById('clubman-minimum-due');
            const apiMinimum = Math.max(0, Number(financials.minimum_due_amount) || 0);
            const gatewayMinimum = Math.max(1, apiMinimum);

            if (outstanding) outstanding.textContent = money(financials.outstanding);
            if (minimumDue) minimumDue.textContent = money(apiMinimum);

            if (paymentAmountInput) {
                paymentAmountInput.min = gatewayMinimum.toFixed(2);
                paymentAmountInput.dataset.minimumPayment = gatewayMinimum.toFixed(2);

                if (paymentAmountInput.dataset.userEdited !== 'true') {
                    paymentAmountInput.value = apiMinimum.toFixed(2);
                }
            }
        }

        async function refreshInvoiceData() {
            const status = document.getElementById('invoice-data-status');

            try {
                const response = await fetch(invoiceDataUrl, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const payload = await response.json();
                renderTransactions(payload.transactions);

                if (status) {
                    status.textContent = 'As of last usage 24 hours ago as updated from club servers.';
                }
            } catch (error) {
                if (status) {
                    status.textContent = 'Showing the last available invoice data. Refresh again shortly.';
                }
                console.error('Unable to refresh invoice data.', error);
            }
        }

        async function refreshMemberFinancials() {
            const status = document.getElementById('member-financials-status');

            try {
                const response = await fetch(memberFinancialsUrl, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const payload = await response.json();
                renderMemberFinancials(payload.financials);

                if (status) {
                    status.textContent = 'Updated from Clubman.';
                }
            } catch (error) {
                if (status) {
                    status.textContent = 'Balance is temporarily unavailable. Please refresh shortly.';
                }
                console.error('Unable to refresh member balances.', error);
            }
        }

        function refreshPageData() {
            refreshInvoiceData();
            refreshMemberFinancials();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', refreshPageData);
        } else {
            refreshPageData();
        }
    })();
</script>
<script>
    let razorpayCheckoutPromise;

    function loadRazorpayCheckout() {
        if (window.Razorpay) return Promise.resolve();
        if (razorpayCheckoutPromise) return razorpayCheckoutPromise;

        razorpayCheckoutPromise = new Promise(function(resolve, reject) {
            const script = document.createElement('script');
            script.src = 'https://checkout.razorpay.com/v1/checkout.js';
            script.async = true;
            script.onload = resolve;
            script.onerror = function() {
                razorpayCheckoutPromise = null;
                reject(new Error('Unable to load Razorpay checkout.'));
            };
            document.head.appendChild(script);
        });

        return razorpayCheckoutPromise;
    }

    function razorpaySubmit(el) {
        if (!el.checked) return;

        if (!validateEnteredPaymentAmount()) {
            el.checked = false;
            return;
        }

        const payNowButton = document.querySelector('.btn-primary');
        payNowButton.style.display = 'none'; // hide for Razorpay

        // Get amount from the input
        let amountInput = document.querySelector('input[name="amount"]');
        let amountValue = parseFloat(amountInput.value);

        // Convert to paise (e.g., ₹100 -> 10000)
        let amountInPaise = Math.round(amountValue * 100);

        loadRazorpayCheckout().then(function() {
            return fetch("{{ route('member.razorpay') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    amount: amountInPaise
                })
            });
        })
            .then(async res => {
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.errors?.amount?.[0] || data.message || 'Payment request failed.');
                }

                return data;
            })
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
                        debugger;
                        // Fill hidden fields and submit form
                        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                        document.getElementById('razorpay_signature').value = response.razorpay_signature;
                        // 🔥 Set Razorpay callback route dynamically here
                        document.getElementById('payment-form').action =
                            "{{ route('member.razorpaycallback') }}";
                        document.getElementById('payment-form').submit();
                    },
                    prefill: {
                        name: "{{ Auth::user()->name ?? 'Guest' }}",
                        email: "{{ Auth::user()->email ?? 'NA' }}",
                        contact: "{{ Auth::user()->phone ?? '' }}"
                    },
                    notes: {
                        contact: "{{ Auth::user()->phone ?? '' }}",
                        udf1: "{{ Auth::id() ?? '0' }}",
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
                payNowButton.style.display = '';
                console.error(err);
                alert(err.message || "Error connecting to Razorpay.");
            });
    }
</script>
<!--block:start:open-paymentpage for HDFC Smart PG-->
<script>
    function hdfcSmartSubmit(el) {
        if (!el.checked) {
            return;
        }

        if (!validateEnteredPaymentAmount()) {
            el.checked = false;
            return;
        }

        // changed: lightweight loader so the user sees progress before gateway redirection.
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
            loaderStyle.innerHTML = '@keyframes hdfcSmartSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
            document.head.appendChild(loaderStyle);
        }

        loader.style.display = 'flex';

        const payNowButton = document.querySelector('.btn-primary');
        payNowButton.style.display = 'none';
        // Get amount from the input
        let amountInput = document.querySelector('input[name="amount"]');
        let amountValue = parseFloat(amountInput.value);
        let tokenPayment = document.querySelector('input[name="active_token_id"]');
        let memberCode = document.querySelector('input[name="member_code"]');

        fetch("{{ route('member.hdfcsmartpg') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json;charset=UTF-8",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
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
                        throw new Error(data.errors?.amount?.[0] || data.message || `HTTP ${response.status}: Request failed`);
                    });
                }
                
                return response.json();

            })
            .then(data => {
                if (data.data.order_status === 'NEW') {
                    const url = data.data.payment_link;
                    return window.location.href = url;
                    console.log("yes");
                }
                console.log(data);
                loader.style.display = 'none';
                alert(`Unexpected status: ${data.status}`);
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
<!--block:end:open-paymentpage-->
