<!DOCTYPE html>
<html>
<head>
    <title>Your Monthly Dues Payment Link</title>
</head>
<body>
    <h1>Dear Member,</h1>
    <p>Please find your payment link for the dues of {{ $token->memberDue->month_name }} {{ $token->memberDue->year }}.</p>
    <p><a href="{{ url('/payment/' . $plainTextToken) }}">Pay Now</a></p>
    <p>If you have any questions, please contact us.</p>
    <p>Thank you,</p>
    <p>Your Club</p>
</body>
</html>
