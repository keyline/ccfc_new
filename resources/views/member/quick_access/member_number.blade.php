<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CCFC :: Quick Access</title>

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">

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
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 0 16px 40px;
        }

        .qa-wrap {
            width: 100%;
            max-width: 420px;
        }

        .qa-topbar {
            background: linear-gradient(135deg, var(--primaryColor), var(--secondaryColor));
            padding: 32px 20px 46px;
            text-align: center;
            color: #fff;
            border-radius: 0 0 24px 24px;
            margin: 0 -16px;
        }

        .qa-logo-badge {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .qa-logo-badge img {
            max-height: 56px;
            max-width: 56px;
            width: auto;
            object-fit: contain;
        }

        .qa-topbar .qa-title {
            color: #fff;
            margin-bottom: 4px;
        }

        .qa-topbar .qa-topbar-subtitle {
            font-size: 12.5px;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.5px;
        }

        .qa-body {
            margin-top: -32px;
        }

        .qa-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.10);
        }

        .qa-title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            color: var(--primaryColor);
            letter-spacing: 0.5px;
        }

        .qa-subtitle {
            text-align: center;
            font-size: 13.5px;
            color: #777;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .qa-label {
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 8px;
        }

        .qa-input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #e2e2e2;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            color: var(--textColor);
            transition: border-color 0.15s ease;
            margin-bottom: 18px;
        }

        .qa-input:focus {
            outline: none;
            border-color: var(--primaryColor);
        }

        .qa-submit-btn {
            width: 100%;
            border: none;
            background: var(--primaryColor);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(190, 31, 36, 0.3);
            transition: background 0.15s ease;
        }

        .qa-submit-btn:hover,
        .qa-submit-btn:focus {
            background: var(--secondaryColor);
            color: #fff;
        }

        .qa-alert {
            background: #fdecea;
            color: #c0392b;
            border: 1px solid #f6cfcb;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13.5px;
            margin-bottom: 18px;
        }

        .qa-alert ul {
            margin: 0;
            padding-left: 18px;
        }

        .qa-footer-note {
            text-align: center;
            font-size: 11.5px;
            color: #aaa;
            margin-top: 22px;
        }

        /* Confirmation / blocked modal */
        .qa-modal .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        .qa-modal .modal-header {
            border-bottom: none;
            padding: 24px 28px 0;
        }

        .qa-modal .modal-header .close {
            margin: -8px -8px -8px auto;
            opacity: 0.5;
        }

        .qa-modal .modal-body {
            padding: 8px 28px 28px;
            text-align: center;
        }

        .qa-modal .modal-footer {
            border-top: none;
            padding: 0 28px 28px;
            justify-content: center;
            gap: 10px;
        }

        .qa-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primaryColor), var(--secondaryColor));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            margin: 4px auto 16px;
            letter-spacing: 0.5px;
        }

        .qa-prompt {
            font-size: 14px;
            color: #777;
            margin-bottom: 4px;
        }

        .qa-member-name {
            font-size: 19px;
            font-weight: 700;
            color: var(--textColor);
            margin-bottom: 4px;
        }

        .qa-member-code {
            display: inline-block;
            background: #f4e9ea;
            color: var(--primaryColor);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 4px;
        }

        .qa-modal .btn {
            border-radius: 8px;
            padding: 10px 26px;
            font-weight: 600;
            font-size: 14px;
        }

        .qa-modal .btn-primary {
            background: var(--primaryColor);
            border-color: var(--primaryColor);
        }

        .qa-modal .btn-primary:hover,
        .qa-modal .btn-primary:focus {
            background: var(--secondaryColor);
            border-color: var(--secondaryColor);
        }

        .qa-modal .btn-outline-secondary {
            border-color: #ddd;
            color: #666;
        }

        .qa-blocked-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fdecea;
            color: #c0392b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 4px auto 18px;
        }

        .qa-blocked-message {
            font-size: 15px;
            color: #444;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <div class="qa-wrap">
        <div class="qa-topbar">
            <div class="qa-logo-badge">
                <img src="{{ asset('img/CCFC-Logo.png') }}" alt="CCFC" />
            </div>
            <div class="qa-title">Quick Access</div>
            <div class="qa-topbar-subtitle">THE CC&amp;FC CLUB AT KOLKATA</div>
        </div>

        <div class="qa-body">
        <div class="qa-card">
            <p class="qa-subtitle">Enter your member number to view and pay your outstanding dues.</p>

            @if ($errors->any())
                <div class="qa-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('member.quickaccess.check') }}" method="POST">
                @csrf
                <div class="qa-label">Member Number</div>
                <input type="text" name="member_code" class="qa-input" placeholder="e.g. B47CEO"
                    value="{{ old('member_code') }}" autocomplete="off" autofocus required>
                <button type="submit" class="qa-submit-btn">Login</button>
            </form>

            <p class="qa-footer-note">&copy; {{ now()->year }} The CC&amp;FC Club at Kolkata. All Rights Reserved.</p>
        </div>
        </div>
    </div>

    @if (session('quickaccess_confirm'))
        @php($confirmData = session('quickaccess_confirm'))
        @php($initials = collect(explode(' ', trim($confirmData['name'])))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode(''))
        <div class="modal fade qa-modal" id="quickAccessConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div></div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="qa-avatar">{{ strtoupper($initials) }}</div>
                        <p class="qa-prompt">Please confirm this is you</p>
                        <div class="qa-member-name">{{ $confirmData['name'] }}</div>
                        <span class="qa-member-code">{{ $confirmData['member_code'] }}</span>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <form action="{{ route('member.quickaccess.confirm') }}" method="POST" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-primary">Continue</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session('quickaccess_blocked'))
        @php($blockedData = session('quickaccess_blocked'))
        <div class="modal fade qa-modal" id="quickAccessBlockedModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div></div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="qa-blocked-icon">&#9888;</div>
                        <p class="qa-blocked-message">{{ $blockedData['message'] }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    @if (session('quickaccess_confirm'))
        <script>
            $(function () {
                $('#quickAccessConfirmModal').modal('show');
            });
        </script>
    @endif

    @if (session('quickaccess_blocked'))
        <script>
            $(function () {
                $('#quickAccessBlockedModal').modal('show');
            });
        </script>
    @endif
</body>

</html>
