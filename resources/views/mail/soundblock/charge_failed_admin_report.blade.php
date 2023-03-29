<html lang="en">
<head>
    <title>New Deployments</title>
</head>
<body>
We would like to inform you about failed account plan {{ $charge_type }} charge.

<hr>
@foreach ($account as $accountKey => $accountValue)
    <span class="font-book">
            <b>{{ $accountKey }} - </b> {{ $accountValue }}<br>
    </span>
@endforeach

<a href="{{$link}}">Office Support Desk</a>
</body>
</html>
