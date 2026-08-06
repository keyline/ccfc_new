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
            align-items: flex-start;
            justify-content: center;
            padding: 0 16px 40px;
        }

        .receipt-wrap {
            width: 100%;
            max-width: 420px;
        }

        .receipt-topbar {
            padding: 32px 20px 46px;
            text-align: center;
            color: #fff;
            border-radius: 0 0 24px 24px;
            margin: 0 -16px;
        }

        .receipt-topbar.success {
            background: linear-gradient(135deg, #43a047, #2e8b47);
        }

        .receipt-topbar.failed {
            background: linear-gradient(135deg, var(--primaryColor), var(--secondaryColor));
        }

        .receipt-topbar-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 28px;
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
        }

        .receipt-topbar-title {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .receipt-topbar-subtitle {
            font-size: 12.5px;
            color: rgba(255, 255, 255, 0.85);
        }

        .receipt-body {
            margin-top: -32px;
        }

        .receipt-card {
            background: #fff;
            border-radius: 18px;
            padding: 28px 24px;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.12);
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
        <div class="receipt-topbar {{ $isSuccess ? 'success' : 'failed' }}">
            <div class="receipt-topbar-icon">
                {!! $isSuccess ? '&#10003;' : '&#10005;' !!}
            </div>
            @if ($isSuccess)
                <div class="receipt-topbar-title">Thank you!</div>
                <div class="receipt-topbar-subtitle">Your transaction was successful</div>
            @else
                <div class="receipt-topbar-title">Payment Failed</div>
                <div class="receipt-topbar-subtitle">{{ $status['message'] ?? 'We could not process your payment.' }}</div>
            @endif
        </div>

        <div class="receipt-body">
        <div class="receipt-card">
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

                <div class="receipt-actions">
                    <a href="{{ route('member.quickaccess.pay') }}" class="receipt-btn">Make Another Payment</a>
                </div>
            @else
                <div class="receipt-badge failed">&#10005; NOT PAID</div>

                <div class="receipt-actions">
                    <a href="{{ route('member.quickaccess.start') }}" class="receipt-btn">Try Again</a>
                </div>
            @endif
        </div>
        </div>
    </div>
</body>

</html>
