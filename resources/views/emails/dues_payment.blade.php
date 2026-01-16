<!DOCTYPE html>
<html>
<head>
    <title>Your Monthly Dues Payment Link</title>
</head>
<body>
    <h1>Dear Member,</h1>
    <p>Click on the link below to pay your club bill.</p>
    {{-- <p>Please find your payment link for the dues of {{ $token->memberDue->month_name }} {{ $token->memberDue->year }}.</p> --}}
    <p><a href="{{ url('/member/magic-login/' . $plainTextToken) }}">Click Here to Pay</a></p>
    <p>Please note, displayed amount does not include late fee charges, if applicable. Payment less than displayed amount will not be accepted by the system.</p>
    <p>Thank you,</p>
</body>
</html>
