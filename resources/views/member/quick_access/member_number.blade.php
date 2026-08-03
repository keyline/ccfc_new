<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- header -->
    @include('common.invoice_header')
    <!-- ********|| RIGHT PART START ||******** -->

    <div class="col-lg-9 col-md-7 p-0">
        <div class="right-body">
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

            <section class="inner_belowbanner invoice_section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 col-lg-6 mx-auto">
                            <div class="title-sec pt-5">
                                <div class="title mb-3">
                                    Quick Access
                                </div>
                                <p>Enter your member number to view and pay your outstanding dues.</p>
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

                            <form action="{{ route('member.quickaccess.check') }}" method="POST">
                                @csrf
                                <div class="invoice_input_feild mb-3">
                                    <input type="text" name="member_code" class="form-control"
                                        placeholder="Enter your member number" value="{{ old('member_code') }}"
                                        autocomplete="off" autofocus required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            @include('common.footer')

        </div>
    </div>

    </body>

    <style>
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
            color: var(--textColor, #2f2f2f);
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
        <script>
            $(function () {
                $('#quickAccessConfirmModal').modal('show');
            });
        </script>
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
        <script>
            $(function () {
                $('#quickAccessBlockedModal').modal('show');
            });
        </script>
    @endif

</html>
