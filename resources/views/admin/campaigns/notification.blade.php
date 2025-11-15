<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- <title>This is test mail</title> -->
</head>
<body>
    <div style="margin: 0 auto; display:table;"><a href="{{ url()->current() }}"><img class="img-fluid" src="{{URL::to('/img/email-logo.png')}}" alt="" /></a></div>
    <div style="font-weight: 600; font-size:20px; text-align: center; font-family:Arial, Helvetica, sans-serif;">Calcutta Cricket & Football Club</div>
    <div style="height: 2px; background: #be1f24;"></div>
    <div style="margin: 0 auto; display:table;">
        <p style="text-align: center; font-family:Arial, Helvetica, sans-serif;"></p>
        <p style="font-size: 14px; color:#000; font-family:Arial, Helvetica, sans-serif; text-align: center;"> {!! $body !!}</p>
    </div>

    
    <div style="padding-top:20px; width:100%; margin: 0 auto; display:table; text-align:center; font-family:Arial, Helvetica, sans-serif;">
        <div style="height: 2px; background: #000; "></div>
        <p style="margin: 10px 0 5px;">19/1, Gurusaday Road, Kolkata - 700 019</p>
        <p style="margin: 0 0 5px;"><strong>Phone:</strong> 24615058/60 &nbsp;</p>
        <p style="margin: 0 0 5px;"><strong>E-mail:</strong> <a style="color: #000; text-decoration:none;" href="mailto:ccfcsecretary@ccfc1792.com"> ccfcsecretary@ccfc1792.com</a>  <strong>Website:</strong> <a style="color: #000; text-decoration:none;" href="{{ url()->current() }}" target="_blank">www.ccfc1792.com</a></p>
        <p style="margin: 0 0 5px;"><strong>CIN :</strong> U92412WB2003NPL096325</p>
    </div>

    <!-- Social Media Icons -->
    <div style="padding-top:20px; width:100%; margin: 0 auto; display:table; text-align:center; font-family:Arial, Helvetica, sans-serif;">
        <a href="https://www.facebook.com/groups/162257794181483" target="_blank" style="margin:0 5px; text-decoration:none;">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/facebook.svg" alt="Facebook" width="20" height="20" style="vertical-align:middle; border:0;">
        </a>
        <a href="https://www.instagram.com/ccfcofficial2024" target="_blank" style="margin:0 5px; text-decoration:none;">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/instagram.svg" alt="Instagram" width="20" height="20" style="vertical-align:middle; border:0;">
        </a>
        <a href="https://www.youtube.com/@CalcuttaCricketFootballClub" target="_blank" style="margin:0 5px; text-decoration:none;">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/youtube.svg" alt="YouTube" width="20" height="20" style="vertical-align:middle; border:0;">
        </a>
        <p style="margin-top:10px; font-size:12px; color:#555;">© {{ date('Y') }} Calcutta Cricket & Football Club. All rights reserved.</p>
    </div>
    
    

    
</body>
</html>