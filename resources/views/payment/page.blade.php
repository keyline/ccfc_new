@extends('layouts.app') {{-- Assuming a default layout file exists --}}

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dues Payment</div>

                <div class="card-body">
                    <p><strong>Member Code:</strong> {{ $due->member_code }}</p>
                    <p><strong>Month:</strong> {{ $due->month_name }} {{ $due->year }}</p>
                    <p><strong>Outstanding Balance:</strong> {{ $due->outstanding_balance }}</p>
                    <p><strong>Paid Amount:</strong> {{ $due->paid_amount }}</p>
                    <p><strong>Dues for this month:</strong> {{ $due->dues_for_this_month }}</p>

                    <hr>

                    {{-- Placeholder for the payment form --}}
                    <form action="#" method="POST">
                        @csrf
                        <script
                            src="https://checkout.razorpay.com/v1/checkout.js"
                            data-key="YOUR_KEY_ID"
                            data-amount="{{ $due->dues_for_this_month * 100 }}"
                            data-currency="INR"
                            data-order_id=""
                            data-buttontext="Pay Now"
                            data-name="Your Club Name"
                            data-description="Dues Payment"
                            data-image="/images/logo.png"
                            data-prefill.name=""
                            data-prefill.email=""
                            data-theme.color="#F37254"
                        ></script>
                        <input type="hidden" custom="Hidden Element" name="hidden">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
