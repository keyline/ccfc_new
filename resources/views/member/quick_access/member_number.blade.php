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
                                <p>Enter your member number to receive a one-time password (OTP) and view your payment details.</p>
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

    @if (session('quickaccess_confirm'))
        @php($confirmData = session('quickaccess_confirm'))
        <div class="modal fade" id="quickAccessConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Your Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Is this you?</p>
                        <p><strong>Name:</strong> {{ $confirmData['name'] }}</p>
                        <p><strong>Member Number:</strong> {{ $confirmData['member_code'] }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
        <div class="modal fade" id="quickAccessBlockedModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Unable to Continue</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>{{ $blockedData['message'] }}</p>
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
