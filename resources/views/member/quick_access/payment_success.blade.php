<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CCFC :: Payment Receipt</title>

    <style>
        :root {
            --primaryColor: #be1f24;
            --secondaryColor: #c23233;
            --textColor: #2f2f2f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #f0f1f3;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            color: var(--textColor);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .receipt-wrap {
            width: 100%;
            max-width: 420px;
        }

        .receipt-card {
            background: #fff;
            border-radius: 18px;
            padding: 32px 28px;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.12);
        }

        .receipt-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 30px;
            color: #fff;
        }

        .receipt-icon.success {
            background: #4caf50;
        }

        .receipt-icon.failed {
            background: var(--primaryColor);
        }

        .receipt-heading {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .receipt-subheading {
            text-align: center;
            font-size: 13.5px;
            color: #888;
            margin-bottom: 24px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #eaeaea;
            font-size: 14px;
        }

        .receipt-row:last-of-type {
            border-bottom: none;
        }

        .receipt-row .label {
            color: #888;
        }

        .receipt-row .value {
            font-weight: 600;
            color: var(--textColor);
            text-align: right;
        }

        .receipt-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0 6px;
            margin-top: 6px;
            border-top: 1px solid #eee;
        }

        .receipt-total .label {
            font-size: 14px;
            font-weight: 700;
            color: var(--textColor);
        }

        .receipt-total .value {
            font-size: 20px;
            font-weight: 700;
            color: var(--textColor);
        }

        .receipt-badge {
            display: inline-block;
            margin-top: 18px;
            padding: 8px 22px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-align: center;
            width: 100%;
        }

        .receipt-badge.success {
            background: #e5f7e8;
            color: #2e8b47;
        }

        .receipt-badge.failed {
            background: #fdecea;
            color: #c0392b;
        }

        .receipt-note {
            text-align: center;
            font-size: 12.5px;
            color: #999;
            margin-top: 16px;
            line-height: 1.5;
        }

        .receipt-actions {
            margin-top: 22px;
        }

        .receipt-btn {
            display: block;
            width: 100%;
            text-align: center;
            border: none;
            background: var(--primaryColor);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            padding: 14px;
            border-radius: 10px;
            text-decoration: none;
        }

        .receipt-btn:hover,
        .receipt-btn:focus {
            background: var(--secondaryColor);
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    @php($isSuccess = strtolower((string) ($status['status'] ?? '')) === 'success')
    @php($transactionRef = $status['transactionid'] ?? $status['mihpayid'] ?? '')
    @php($clubmanPostingFailed = !empty($status['clubman_posting_failed']))

    <div class="receipt-wrap">
        <div class="receipt-card">
            <div class="receipt-icon {{ $isSuccess ? 'success' : 'failed' }}">
                {!! $isSuccess ? '&#10003;' : '&#10005;' !!}
            </div>

            @if ($isSuccess)
                <div class="receipt-heading">Thank you!</div>
                <div class="receipt-subheading">Your transaction was successful</div>
            @else
                <div class="receipt-heading">Payment Failed</div>
                <div class="receipt-subheading">{{ $status['message'] ?? 'We could not process your payment.' }}</div>
            @endif

            <div class="receipt-row">
                <span class="label">Date</span>
                <span class="value">{{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Time</span>
                <span class="value">{{ now()->format('h:i A') }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Reference No.</span>
                <span class="value">{{ $transactionRef }}</span>
            </div>

            <div class="receipt-total">
                <span class="label">Amount</span>
                <span class="value">&#8377;{{ number_format((float) ($status['amount'] ?? 0), 2) }}</span>
            </div>

            @if ($isSuccess)
                <div class="receipt-badge success">&#10003; PAID</div>

                @if ($clubmanPostingFailed)
                    <p class="receipt-note">
                        Payment is successful, but your payment details could not be updated due to a network issue.
                        Please contact admin.
                    </p>
                @else
                    <p class="receipt-note">
                        The paid amount will reflect in your account within the next 24 working hours.
                    </p>
                @endif
            @else
                <div class="receipt-badge failed">&#10005; NOT PAID</div>

                <div class="receipt-actions">
                    <a href="{{ route('member.quickaccess.start') }}" class="receipt-btn">Try Again</a>
                </div>
            @endif
        </div>
    </div>
</body>

</html>
