@extends("mail.layouts.soundblock")

@section("content")
    <x-head-line class="soundblock-email-headline" title="{{ $subject }}"/>

    @if ($status)
        <x-p text="We successfully processed the monthy charge for your account."/>
    @else
        <x-p text="We would like to inform you that the monthy charge for your account was declined. Please update your payment method and try again or contact customer service if you need assistance."/>
    @endif

    @foreach ($account as $accountKey => $accountValue)
        <span class="font-book">
            <b>{{ $accountKey }} - </b> {{ $accountValue }}<br>
        </span>
    @endforeach

    @if(!empty($charge))
        <h3 class="font-medium" style="text-align: center;">CHARGE DETAILS</h3>
        @foreach ($charge as $chargeType => $chargeDetails)
            <div class="font-large" style="margin-top: 1em;">{{ $chargeType }}</div>
            <span class="font-book">
                Quantity - {{ $chargeDetails["quantity"] }}<br>
                Price - ${{ $chargeDetails["total"] }}<br>
            </span>
        @endforeach

        <hr>
        <span class="font-book" style="float: right;">
            <b>Total: </b> ${{ $totalAmount }}<br>
        </span>
    @endif

    <p style="text-align: center;margin-top: 3.5em;">
        <x-button class="soundblock-email-button" text="Soundblock Accounts" :link="$account_link"/>
        <x-button class="soundblock-email-button" text="Soundblock Support" :link="$support_link"/>
    </p>
    <p>
        <small><a style="color:dodgerblue;" href="{{ $account_link }}">{{ $account_link }}</a></small>
        <small><a style="color:dodgerblue;" href="{{ $support_link }}">{{ $support_link }}</a></small>
    </p>
@endsection
