<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- ?php include 'assets/inc/header.php';?> -->

    <!-- header -->
    @include('common.invoice_header')
    <!-- ********|| RIGHT PART START ||******** -->

    <div class="col-lg-9 col-md-7 p-0">
        <div class="right-body">
            <!-- ********|| BANNER PART START ||******** -->
            <section class="banner">

                <div class="banner-box">

                    <div id="innerpage-banner" class="owl-carousel owl-theme">

                        <div class="item">

                            <div class="about-img">

                                <img class="img-fluid" src="{{ asset('img/past-president/banner1.jpg') }}" alt="" />

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

                                    @if($userData->userCodeUserDetails[0]['member_image'] == '')


                                    <div class="member_profileimg">
                                        <img class="img-fluid ifnotpic" src="{{ asset('img/Profile-Icon-01.svg') }}"
                                            alt="" />
                                    </div>

                                    @else


                                    <div class="member_profileimg">
                                        <img class="img-fluid" src="data:image/png;base64,                          
                                        {{ $userData->userCodeUserDetails[0]->member_image}} " alt="" />
                                    </div>


                                    @endif


                                </div>
                                <div class="col-lg-8 col-md-7">
                                    <div class="member_profiletop">
                                        <h4>Welcome</h4>
                                        <h2>{{ $userData->name}}</h2>

                                        <p><strong>Ph No:</strong>{{ $userData->userCodeUserDetails[0]->mobile_no }}
                                        </p>
                                        <p><strong>Mail ID:</strong>{{ $userData->email}}
                                        </p>
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
                                <h2>Due for the month of : {{ $balanceFortheMonth }}</h2>
                                <h3>Total Payable Amount : INR. <span id="comparable_amount"
                                    style="font-size: 22px;
                                        font-weight: 600;
                                        font-family: 'IBM Plex Serif', serif;
                                        color: #be1f24;
                                        margin-bottom: 0;"
                                    >{{ $outstandingBalance }}</span></h3>
                                {{-- <h3>Total due till date : INR. {{ $dues_for_this_month }} </h3> --}}
                                
                                <p>(As of last updated from club admin)</p>
                                
                                <div class="invoice_outstading_payment">
									<form action="" method="POST" id="payment-form">  
                                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
                                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="active_token_id" value="{{ session()->get('tokenPayment.active_id') }}">
                                      
                                        @csrf
										<div class="invoice_input_bank">
                                            <div class="invoice_input_feild">
												<input type="text" name="amount" placeholder="Enter amount being paid" id="compare_with_amount">												
											</div>
											<div class="invocie_paymentlogo">
												<ul>
													<li>
														<input class="form-check-input" type="radio" name="paymentGatewayOptions" id="exampleRadios1" value="{{ route('member.payment')}}" onclick="setPaymentAction('payu')">
														<label class="form-check-label" for="exampleRadios1">
															 <img class="img-fluid" src="{{ asset('img/invoice_payu_logo.png') }}" alt="" />
														</label>
													</li>
													<!-- <li>
														<input class="form-check-input" type="radio" name="paymentGatewayOptions" id="exampleRadios2" value="{{ route('member.paywithhdfc')}}" onclick="setPaymentAction('hdfc')">
														<label class="form-check-label" for="exampleRadio2">
															 <img class="img-fluid" src="{{ asset('img/invoice_hdfc_logo.jpg') }}" alt="" />
														</label>
													</li> -->
													<li>
														<input class="form-check-input" type="radio" name="paymentGatewayOptions" id="exampleRadios3" value="{{ route('member.axischeckout')}}" onclick="setPaymentAction('axis')">
														<label class="form-check-label" for="exampleRadios3">
															 <img class="img-fluid" src="{{ asset('img/invoice_axis_logo.jpg') }}" alt="" />
														</label>
													</li>
                                                    <!-- ?php if($userData->user_code == 'B47CEO') { ?> -->
                                                    <li>
														<input class="form-check-input" type="radio" name="paymentGatewayOptions" id="exampleRadios4" onclick="razorpaySubmit(this);">
														<label class="form-check-label" for="exampleRadios4">
															 <img class="img-fluid" src="{{ asset('img/invoice_razorpay_logo.png') }}" alt="" />
														</label>
													</li>
                                                    <!-- ?php } ?> -->
                                                    <?php if ($userData->user_code == 'B47CEO') { ?>
                                                    <li>
														<input class="form-check-input" type="radio" name="paymentGatewayOptions" id="exampleRadios5" onclick="hdfcSmartSubmit(this);">
														<label class="form-check-label" for="exampleRadios5">
															 <img class="img-fluid" src="{{ asset('img/HdfcLogo.svg') }}" alt="" />
														</label>
													</li>
                                                    <?php } ?>
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
            if (route === null ) {
                errorMsg.push("Please check one of payment gateway before making payment");
            }
            //console.log(checkAmount(amountInput));
            if (! checkAmount(amountInput)) {
                errorMsg.push("Amount not valid!");
            }

            if(Array.isArray(errorMsg) && !errorMsg.length){
                form.action= route;
                form.submit();
            }

            errorMsg.forEach(function (message) {
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

function checkAmount(amount) {
    
    //const amountRegex = /^(?!0)\d+$/;
    const amountRegex =/^\d+(\.\d{1,2})?$/;
    return amountRegex.test(amount);
}
            </script>                                                 
            </div>
            </div>
        </section>

        <section class="member_details_section">
            <div class="container">
                <div class="row">
                    

                </div>





            </div>
        </section>
        <!-- ********|| HISTORY END ||******** -->




        @include('common.footer')
        
        <!-- ?php include 'assets/inc/footer.php';?> -->


        </body>

</html>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>    
function razorpaySubmit(el) {
	if (!el.checked) return;

    const payNowButton = document.querySelector('.btn-primary');
    payNowButton.style.display = 'none'; // hide for Razorpay

	// Get amount from the input
	let amountInput = document.querySelector('input[name="amount"]');
	let amountValue = parseFloat(amountInput.value);

	if (!amountValue || amountValue <= 0) {
		alert("Please enter a valid amount before choosing Razorpay.");
		el.checked = false;
		return;
	}

	// Convert to paise (e.g., ₹100 -> 10000)
	let amountInPaise = Math.round(amountValue * 100);

	fetch("{{ route('member.razorpay') }}", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
			"X-CSRF-TOKEN": "{{ csrf_token() }}"
		},
		body: JSON.stringify({ amount: amountInPaise })
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
			handler: function (response) {
                debugger;
				// Fill hidden fields and submit form
				document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
				document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
				document.getElementById('razorpay_signature').value = response.razorpay_signature;
                // 🔥 Set Razorpay callback route dynamically here
                document.getElementById('payment-form').action = "{{ route('member.razorpaycallback') }}";
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
        el.checked=false;
		console.error(err);
		alert("Error connecting to Razorpay.");
	});
}
</script>
<!--block:start:open-paymentpage for HDFC Smart PG-->
<script>
    function hdfcSmartSubmit(el)
    {
        if (!el.checked) {
            return;
        }
        const payNowButton = document.querySelector('.btn-primary');
        payNowButton.style.display='none';
        // Get amount from the input
        let amountInput = document.querySelector('input[name="amount"]');
        let amountValue = parseFloat(amountInput.value);

        let tokenPayment = document.querySelector('input[name="active_token_id"]');

        if (!amountValue || amountValue <= 0) {
            alert("Please enter a valid amount before choosing HDFC Smart gateway.");
            el.checked = false;
            //payNowButton.disabled =false;
            return;
        }

        fetch("{{ route('member.hdfcsmartpg') }}", {
		method: "POST",
		headers: {
			"Content-Type": "application/json;charset=UTF-8",
			"X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
		},
		body: JSON.stringify({ amount: amountValue, token_id: tokenPayment.value})
	})
	.then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(`HTTP ${response.status}: ${data.message || 'Request failed'}`);
            });
        }
        return response.json();

    })
    .then(data => {
        if (data.status === 'NEW') {
            const url = data.paymentLinks.web;
            return window.location.href = url;
        }
        alert(`Unexpected status: ${data.status}`);

    })
    .catch(err => {
        el.checked=false;
		console.error(err);
		alert("Error connecting to hdfcsmartpay.");
	});



    }
    

    </script>

    
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const payableEl = document.getElementById('comparable_amount');
        const payInput  = document.getElementById('compare_with_amount');

        if (!payableEl || !payInput) return;

        // Normalize value ONLY for comparison
        function normalizeForCompare(value) {
            value = value.trim();

            // Allow: 158 | 158.00 | 158.000
            if (!/^\d+(\.\d+)?$/.test(value)) {
                return null;
            }

            let parts = value.split('.');
            let integerPart = parts[0];
            let decimalPart = parts[1] || '';

            // Normalize to max 3 decimal places (safe upper bound)
            decimalPart = decimalPart.padEnd(3, '0').slice(0, 3);

            return integerPart + decimalPart;
        }

        const payableCompareValue = normalizeForCompare(payableEl.innerText);

        payInput.addEventListener('change', function () {

            const enteredCompareValue = normalizeForCompare(this.value);

            if (enteredCompareValue === null) {
                alert('Please enter a valid amount.');
                this.value = '';
                this.focus();
                return;
            }

            // BigInt comparison ONLY
            if (BigInt(enteredCompareValue) < BigInt(payableCompareValue)) {
                alert('Amount must be equal to or greater than the payable amount.');
                this.value = '';
                this.focus();
            }

        });

    }); 
</script>



<!--block:end:open-paymentpage-->
